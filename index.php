<?php
// 1. INIZIALIZZAZIONE E SICUREZZA
require_once 'db_config.php'; // Carica sessione, connessione e variabili di sicurezza

// --- A. PROTEZIONE ANTI-FLOOD & LOGGING ---
// Verifica se l'IP ha superato il limite di 5 tentativi falliti in 2 minuti
$check_flood = $connessione->query("
    SELECT COUNT(*) as totale FROM tentativi_accesso 
    WHERE ip_address = '$ip_utente' 
    AND tentativo_timestamp > (NOW() - INTERVAL $intervallo_minuti MINUTE)
    AND esito = 'fallimento'
");
$dati_flood = $check_flood->fetch_assoc();

if ($dati_flood['totale'] >= $limite_tentativi) {
    die("Troppi tentativi falliti. Il tuo IP ($ip_utente) è temporaneamente bloccato.");
}

// --- B. GESTIONE LOGOUT ---
if (isset($_POST['logout'])) {
    setcookie("utenti_id", "", time() - 3600); // Rimuove il cookie di sessione
    header("Location: index.php");
    exit;
}

// --- C. ANNULLAMENTO 2FA ---
if (isset($_GET['abort_2fa'])) {
    unset($_SESSION['pending_user_id']); // Resetta lo stato di login pendente
    header("Location: index.php");
    exit;
}

// --- D. ELABORAZIONE REGISTRAZIONE (Hardening & CAPTCHA) ---
if (isset($_POST['register_username'])) {
    $username = $_POST['register_username'];
    $password = $_POST['register_password'];
    $captcha_input = $_POST['captcha_input'];
    $controllo_reg = false;

    // 1. Validazione Password (8-12 char, Maiusc, Minusc, Num, Spec)
    $pattern = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@#!$])[A-Za-z\d@#!$]{8,12}$/';
    if (!preg_match($pattern, $password)) {
        echo "<script>alert('Criteri password non rispettati.');</script>";
        $controllo_reg = true;
    }

    // 2. Verifica CAPTCHA
    if ($captcha_input != $_SESSION['captcha_ans']) {
        echo "<script>alert('CAPTCHA errato!');</script>";
        $controllo_reg = true;
    }

    if (!$controllo_reg) {
        $risultato = $connessione->query("SELECT * FROM utenti WHERE utenti_username = '$username'");
        if ($risultato->num_rows > 0) {
            echo "<script>alert('Username non disponibile');</script>";
        } else {
            // Inserimento nuovo utente e inizializzazione tabella partite
            if ($connessione->query("INSERT INTO utenti SET utenti_username='$username', utenti_password='$password'")) {
                $nuovo_id = $connessione->insert_id;
                $connessione->query("INSERT INTO partite (utenti_id, partite_vinte, partite_perse, partite_pareggiate) VALUES ($nuovo_id, 0, 0, 0)");
                echo "<script>alert('Registrazione effettuata! Ora puoi accedere.');</script>";
                unset($_SESSION['captcha_ans']);
            }
        }
    }
}

// --- E. LOGIN FASE 1: VERIFICA CREDENZIALI E LOGGING ---
if (isset($_POST['login_username'])) {
    $username = $_POST['login_username'];
    $password = $_POST['login_password'];

    // Ricerca dell'utente nel database
    $risultato = $connessione->query("SELECT * FROM utenti WHERE utenti_username = '$username' AND utenti_password = '$password'");
    $riga = $risultato->fetch_assoc();

    if (isset($riga['utenti_username'])) {
        // 1. GENERAZIONE CODICE OTP PER FASE 2
        $otp = rand(100000, 999999);
        $scadenza = date("Y-m-d H:i:s", strtotime("+10 minutes")); // Validità 10 min
        $user_id = $riga['utenti_id'];

        // Salvo l'OTP nel database per la successiva verifica
        $connessione->query("UPDATE utenti SET otp_code = '$otp', otp_expiry = '$scadenza' WHERE utenti_id = '$user_id'");
        
        // Imposto la sessione pendente per attivare l'interfaccia 2FA
        $_SESSION['pending_user_id'] = $user_id; 

        // 2. LOGGING: Successo credenziali (in attesa di 2FA)
        // Memorizziamo IP, esito e username senza mai salvare la password
        $connessione->query("INSERT INTO tentativi_accesso (ip_address, esito, username_coinvolto) 
                             VALUES ('$ip_utente', 'attesa_2fa', '$username')");

        $messaggio_otp = "Codice di verifica (Simulazione): $otp"; 
    } else {
        // 3. LOGGING: Fallimento credenziali
        // Questo log alimenterà il sistema Anti-Flood per il conteggio dei tentativi
        $connessione->query("INSERT INTO tentativi_accesso (ip_address, esito, username_coinvolto) 
                             VALUES ('$ip_utente', 'fallimento', '$username')");
        
        echo "<script>alert('Credenziali errate.');</script>";
    }
}

// --- F. LOGIN FASE 2: VERIFICA OTP (2FA) CON LOGGING ---
if (isset($_POST['verify_2fa'])) {
    $otp_inserito = $_POST['otp_input'];
    $user_id = $_SESSION['pending_user_id'];

    // Recuperiamo i dati dell'utente per la verifica e per recuperare l'username per il log
    $risultato = $connessione->query("SELECT utenti_username, otp_code, otp_expiry FROM utenti WHERE utenti_id = '$user_id'");
    $riga = $risultato->fetch_assoc();

    $messaggio_otp = "Codice di verifica (Simulazione): " . $riga['otp_code'];
    
    $username_coinvolto = $riga['utenti_username'];
    $otp_corretto = $riga['otp_code'];
    $scadenza = $riga['otp_expiry'];
    $adesso = date("Y-m-d H:i:s");

    if ($otp_inserito == $otp_corretto && $adesso <= $scadenza) {
        // --- 1. LOGGING: SUCCESSO FINALE ---
        // L'utente ha superato entrambi i fattori di autenticazione
        $connessione->query("INSERT INTO tentativi_accesso (ip_address, esito, username_coinvolto) 
                             VALUES ('$ip_utente', 'successo', '$username_coinvolto')");

        // Impostiamo il cookie di login (valido 30 giorni) per l'accesso alle aree protette
        setcookie("utenti_id", $user_id, time() + (86400 * 30));
        
        // Pulizia dei dati temporanei di sicurezza
        unset($_SESSION['pending_user_id']);
        $connessione->query("UPDATE utenti SET otp_code=NULL, otp_expiry=NULL WHERE utenti_id='$user_id'");
        
        header("Location: index.php");
        exit;
    } else {
        // --- 2. LOGGING: FALLIMENTO OTP ---
        // Registriamo che le credenziali erano corrette ma il secondo fattore è fallito
        $connessione->query("INSERT INTO tentativi_accesso (ip_address, esito, username_coinvolto) 
                             VALUES ('$ip_utente', 'fallimento_otp', '$username_coinvolto')");
        
        echo "<script>alert('Codice errato o scaduto. Riprova.');</script>";
    }
}

// --- G. RECUPERO DATI UTENTE ---
$cookie_riga = null;
if (isset($_COOKIE['utenti_id'])) {
    $id = $_COOKIE['utenti_id'];
    $cookie_risultato = $connessione->query("SELECT * FROM utenti WHERE utenti_id='$id'");
    $cookie_riga = $cookie_risultato->fetch_assoc();
}

// --- H. GENERAZIONE CAPTCHA ---
if (!isset($_SESSION['captcha_string']) || isset($_POST['register_username'])) {
    $n1 = rand(1, 10); $n2 = rand(1, 10);
    $_SESSION['captcha_ans'] = $n1 + $n2;
    $_SESSION['captcha_string'] = "$n1 + $n2 = ?";
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tic Tac Toe</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body class="bg-dark text-light">

<!-- MESSAGGIO BENVENUTO IN ALTO A DESTRA -->
<?php if(isset($_COOKIE['utenti_id'])): ?>
    <div class="user-welcome-top">
        <i class="fas fa-user-circle text-info me-2"></i>
        <span class="small fw-bold me-3"><?php echo $cookie_riga['utenti_username']; ?></span>
        <form method="POST" class="d-inline">
            <button type="submit" name="logout" class="btn btn-outline-danger btn-sm border-0" title="Esci">
                <i class="fas fa-power-off"></i>
            </button>
        </form>
    </div>
<?php endif; ?>

<div class="container">
    <!-- SCHERMATA 0: ACCESSO (SOLO SE NON LOGGATO) -->
    <?php if (!isset($_COOKIE['utenti_id'])): ?>
        <div class="schermata-0">
            <div class="auth-card shadow-lg">
                <?php if (isset($_SESSION['pending_user_id'])): ?>
                    <!-- FASE 2: VERIFICA OTP -->
                    <div class="text-center">
                        <i class="fas fa-user-shield fa-3x text-info mb-3"></i>
                        <h3>Verifica 2FA</h3>
                        <?php if (isset($messaggio_otp)) echo "<div class='alert alert-info py-2 small'>$messaggio_otp</div>"; ?>
                        <form method="POST" class="mt-4">
                            <input type="text" name="otp_input" class="form-control form-control-lg text-center mb-4 form-control-custom" placeholder="000000" maxlength="6" required autofocus>
                            <button type="submit" name="verify_2fa" class="btn btn-info w-100 fw-bold py-2 text-dark">Verifica Codice</button>
                        </form>
                        <div class="mt-3"><a href="index.php?abort_2fa=1" class="text-decoration-none small text-muted">Indietro</a></div>
                    </div>
                <?php else: ?>
                    <!-- FASE 1: REG / LOGIN -->
                    <div class="row g-4">
                        <div class="col-md-6 border-end border-secondary pe-md-4">
                            <strong><i class="fas fa-user-plus me-2"></i>Registrati</strong>
                            <form method="POST" class="mt-3">
                                <input type="text" name="register_username" class="form-control mb-3 form-control-custom" placeholder="Username" required>
                                <input type="password" name="register_password" id="reg_password" class="form-control mb-2 form-control-custom" placeholder="Password" required>
                                <div id="password-feedback" class="mb-3">
                                    <div id="len" class="invalid mb-1"><i class="fas fa-circle-xmark me-2"></i>8-12 caratteri</div>
                                    <div id="upper" class="invalid mb-1"><i class="fas fa-circle-xmark me-2"></i>Maiuscola</div>
                                    <div id="lower" class="invalid mb-1"><i class="fas fa-circle-xmark me-2"></i>Minuscola</div>
                                    <div id="num" class="invalid mb-1"><i class="fas fa-circle-xmark me-2"></i>Numero</div>
                                    <div id="spec" class="invalid"><i class="fas fa-circle-xmark me-2"></i>Speciale (@, #, !, $)</div>
                                </div>
                                <div class="mb-3">
                                    <label>Risolvi: <?php echo $_SESSION['captcha_string'] ?? "Generazione..."; ?></label>
                                    <input type="number" name="captcha_input" class="form-control form-control-custom" required>
                                </div>
                                <button type="submit" id="reg_submit" class="btn btn-success w-100 fw-bold" disabled>Registrati</button>
                            </form>
                        </div>
                        <div class="col-md-6 ps-md-4">
                            <strong><i class="fas fa-sign-in-alt me-2"></i>Login</strong>
                            <form method="POST" class="mt-3">
                                <input type="text" name="login_username" class="form-control mb-3 form-control-custom" placeholder="Username" required>
                                <input type="password" name="login_password" class="form-control mb-3 form-control-custom" placeholder="Password" required>
                                <button type="submit" class="btn btn-info w-100 fw-bold text-dark">Accedi</button>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- SCHERMATA 1: PREPARAZIONE (CENTRATA) -->
    <div class="schermata-1 text-center <?php echo !isset($_COOKIE['utenti_id']) ? 'hidden' : ''; ?>">
        <div class="logo mb-5">
            <h1 class="display-1 fw-bold">TIC TAC TOE</h1>
            <p class="fs-4 text-secondary">Version <span class="badge bg-info text-dark">Modern Edition</span> by Pietro "Saetta12"</p>
        </div>
        <div class="row justify-content-center mb-4 w-100">
            <div class="col-md-5">
                <input type="text" id="player1" class="form-control form-control-custom text-center" value="<?php echo $cookie_riga['utenti_username'] ?? '' ?>" disabled>
            </div>
            <div class="col-md-1 align-self-center py-2">VS</div>
            <div class="col-md-5">
                <input type="text" id="player2" class="form-control form-control-custom text-center" value="Ospite" disabled>
            </div>
        </div>
        <button id="startBtn" class="btn btn-info btn-lg px-5 fw-bold text-dark mt-4 shadow">Avvia Sfida</button>
    </div>

    <!-- SCHERMATA 2: GRIGLIA -->
    <div class="schermata-2 container py-5 hidden">
        <div class="row justify-content-between mb-5 p-3 bg-card rounded-4 border border-secondary shadow align-items-center">
            <div class="col-auto text-center"><div class="giocatore1 fw-bold fs-4 text-accent-x"></div><div class="punteggio1 badge bg-dark fs-5 mt-2 border border-accent-x">0</div></div>
            <div class="col-auto text-center"><p class="text-muted small mb-0">ROUND</p><p id="round" class="display-6 fw-bold mb-0">1</p></div>
            <div class="col-auto text-center"><div class="giocatore2 fw-bold fs-4 text-accent-o"></div><div class="punteggio2 badge bg-dark fs-5 mt-2 border border-accent-o">0</div></div>
        </div>
        <div class="d-flex flex-column align-items-center">
            <div class="griglia">
                <?php for($i=1; $i<=9; $i++) echo "<div class='cella' id='item$i'></div>"; ?>
            </div>
            <div class="turn-container mt-4 p-3 bg-dark rounded-pill border border-secondary">Turno di: <span id="turnOf" class="fw-bold ms-2 fs-5"></span></div>
        </div>
    </div>

    <!-- SCHERMATA 3: RISULTATI -->
    <div class="schermata-3 container py-5 hidden">
        <div class="result-card mx-auto text-center p-5 shadow-lg">
            <h1 class="display-3 fw-bold mb-4 text-accent-x italic"><i>Risultato</i></h1>
            <div id="player-winner" class="fs-2 mb-4"></div>
            <div class="row justify-content-center g-4 mb-5">
                <div class="col-5"><div id="labelX" class="text-accent-x fw-bold mb-2 small"></div><div id="scoreX" class="bg-dark border border-accent-x py-2 fs-3 rounded-4">0</div></div>
                <div class="col-5"><div id="labelO" class="text-accent-o fw-bold mb-2 small"></div><div id="scoreO" class="bg-dark border border-accent-o py-2 fs-3 rounded-4">0</div></div>
            </div>
            <hr class="border-secondary opacity-50 mb-4">
            <h3 class="fw-light mb-4"><i class="fas fa-history me-2"></i>Cronologia</h3>
            <div class="row g-3 text-start mb-5">
                <?php for($i=1; $i<=3; $i++): ?>
                    <div class="col-md-4"><div class="round-box h-100"><div class="badge bg-info text-dark mb-2">Round <?php echo $i; ?></div><p id="round<?php echo $i; ?>hystory" class="small text-muted mb-0"></p></div></div>
                <?php endfor; ?>
            </div>
            <button class="btn btn-info btn-lg px-5 py-3 fw-bold text-dark btn-ricomincia"><i class="fas fa-redo-alt me-2"></i>Nuova Sfida</button>
        </div>
    </div>
</div>

<!-- ALERT AUTOMATICO -->
<div id="customAlert" class="alert-custom hidden">
    <p id="alertText" class="mb-0 text-center fs-5 fw-bold"></p>
</div>
<div class="overlay"></div>

<script src="script.js"></script>
</body>
</html>