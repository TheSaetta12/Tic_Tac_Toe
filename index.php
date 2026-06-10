<?php
// Richiamo della configurazione centralizzata
require_once 'db_config.php';

// --- PROTEZIONE ANTI-FLOOD ---
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

// --- LOGOUT ---
if(isset($_POST['logout'])){
    setcookie("utenti_id", "", strtotime("-1 hour"));
    header("Location: index.php");
    exit;
}

// --- ANNULLAMENTO 2FA (RESET) ---
if(isset($_GET['abort_2fa'])) {
    unset($_SESSION['pending_user_id']);
    header("Location: index.php");
    exit;
}

// --- ELABORAZIONE REGISTRAZIONE (Hardening & CAPTCHA) ---
if(isset($_POST['register_username'])) {
    $username = $_POST['register_username'];
    $password = $_POST['register_password'];
    $captcha_input = $_POST['captcha_input'];
    $controllo_reg = false;

    // Pattern complessità: 8-12 char, Maiusc, Minusc, Num, Spec (@#!$)
    $pattern = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@#!$])[A-Za-z\d@#!$]{8,12}$/';
    if (!preg_match($pattern, $password)) {
        echo "<script>alert('La password non rispetta i criteri di sicurezza.');</script>";
        $controllo_reg = true;
    }

    if ($captcha_input != $_SESSION['captcha_ans']) {
        echo "<script>alert('CAPTCHA errato!');</script>";
        $controllo_reg = true;
    }

    if (!$controllo_reg) {
        $risultato = $connessione->query("SELECT * FROM utenti WHERE utenti_username = '$username'");
        if ($risultato->num_rows > 0) {
            echo "<script>alert('Username non disponibile');</script>";
        } else {
            if($connessione->query("INSERT INTO utenti SET utenti_username='$username', utenti_password='$password'")){
                echo "<script>alert('Registrazione effettuata!');</script>";
                unset($_SESSION['captcha_ans']);
            }
        }
    }
}

// --- LOGIN (Generazione OTP & Logging) ---
if(isset($_POST['login_username'])) {
    $username = $_POST['login_username'];
    $password = $_POST['login_password'];

    $risultato = $connessione->query("SELECT * FROM utenti WHERE utenti_username = '$username' && utenti_password = '$password'");
    $riga = $risultato->fetch_assoc();

    if (isset($riga['utenti_username'])) {
        $otp = rand(100000, 999999);
        $scadenza = date("Y-m-d H:i:s", strtotime("+10 minutes"));
        $id_utente = $riga['utenti_id'];

        $connessione->query("UPDATE utenti SET otp_code = '$otp', otp_expiry = '$scadenza' WHERE utenti_id = '$id_utente'");
        $_SESSION['pending_user_id'] = $id_utente;
        
        // Log dell'attesa 2FA
        $connessione->query("INSERT INTO tentativi_accesso (ip_address, esito, username_coinvolto) VALUES ('$ip_utente', 'attesa_2fa', '$username')");
        $messaggio_otp = "Codice di verifica 2FA: $otp"; 
    } else {
        // Log del fallimento credenziali
        $connessione->query("INSERT INTO tentativi_accesso (ip_address, esito, username_coinvolto) VALUES ('$ip_utente', 'fallimento', '$username')");
        echo "<script>alert('Credenziali errate.');</script>";
    }
}

// --- 2FA(Verifica finale) ---
if(isset($_POST['verify_2fa'])) {
    $otp_inserito = $_POST['otp_input'];
    $user_id = $_SESSION['pending_user_id'];

    $risultato = $connessione->query("SELECT * FROM utenti WHERE utenti_id = '$user_id'");
    $riga = $risultato->fetch_assoc();
    $adesso = date("Y-m-d H:i:s");

    if ($otp_inserito == $riga['otp_code'] && $adesso <= $riga['otp_expiry']) {
        setcookie("utenti_id", $user_id, strtotime("+1 year"));
        $connessione->query("INSERT INTO tentativi_accesso (ip_address, esito, username_coinvolto) VALUES ('$ip_utente', 'successo', '".$riga['utenti_username']."')");
        
        unset($_SESSION['pending_user_id']);
        $connessione->query("UPDATE utenti SET otp_code=NULL, otp_expiry=NULL WHERE utenti_id='$user_id'");
        
        header("location: index.php");
        exit;
    } else {
        $connessione->query("INSERT INTO tentativi_accesso (ip_address, esito, username_coinvolto) VALUES ('$ip_utente', 'fallimento_otp', '".$riga['utenti_username']."')");
        echo "<script>alert('Codice errato o scaduto.');</script>";
    }
}

// --- RECUPERO DATI UTENTE ---
$cookie_riga = null;
if(isset($_COOKIE['utenti_id'])){
    $id = $_COOKIE['utenti_id'];
    $cookie_risultato = $connessione->query("SELECT * FROM utenti WHERE utenti_id='$id'");
    $cookie_riga = $cookie_risultato->fetch_assoc();
}

// --- GENERAZIONE CAPTCHA ---
$n1 = rand(1, 10); $n2 = rand(1, 10);
$_SESSION['captcha_ans'] = $n1 + $n2;
$_SESSION['captcha_string'] = "$n1 + $n2 = ?";
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Tic Tac Toe - Cyber Security Version</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="schermata-0">
    <?php if(!isset($_COOKIE['utenti_id'])): ?>
        <?php if(isset($_SESSION['pending_user_id'])): ?>
            <!-- FORM OTP -->
            <form method="POST">
                <h3>Verifica Sicurezza (2FA)</h3>
                <?php if(isset($messaggio_otp)) echo "<p style='color:#C1FF72;'>$messaggio_otp</p>"; ?>
                <input type="text" name="otp_input" placeholder="000000" maxlength="6" required autofocus>
                <button type="submit" name="verify_2fa">Verifica Codice</button>
                <hr><p style="font-size: 0.8em;"><a href="index.php?abort_2fa=1" style="color: #C1CBD9;">Torna al Login</a></p>
            </form>
        <?php else: ?>
            <!-- REGISTRAZIONE E LOGIN -->
            <form method="POST">
                <strong>Registrati</strong>
                <input type="text" name="register_username" placeholder="username" required>
                <input type="password" name="register_password" id="reg_password" placeholder="password" required>
                <div style="margin: 10px 0; color: #C1CBD9;">
                    <label>Risolvi: <?php echo $_SESSION['captcha_string']; ?></label>
                    <input type="number" name="captcha_input" required>
                </div>
                <div id="password-feedback" style="font-size: 0.8em; text-align: left; margin: 10px 0;">
                    <p id="len" class="invalid">● 8-12 caratteri</p>
                    <p id="upper" class="invalid">● Una maiuscola</p>
                    <p id="lower" class="invalid">● Una minuscola</p>
                    <p id="num" class="invalid">● Un numero</p>
                    <p id="spec" class="invalid">● Un carattere speciale (@, #, !, $)</p>
                </div>
                <button type="submit" id="reg_submit" disabled>Invia</button>
            </form>
            <hr style="border: 1px solid #444; margin: 20px 0;">
            <form method="POST">
                <strong>Login</strong>
                <input type="text" name="login_username" placeholder="username" required>
                <input type="password" name="login_password" placeholder="password" required>
                <button type="submit">Accedi</button>
            </form>
        <?php endif; ?>
    <?php else: ?>
        <p>Benvenuto utente <strong><?php echo $cookie_riga['utenti_username']; ?></strong></p>
        <form method="POST"><button type="submit" name="logout">Logout</button></form>
    <?php endif; ?>
</div>

<div class="schermata-1 <?php echo !isset($_COOKIE['utenti_id']) ? 'hidden' : ''; ?>">
    <div class="logo"><h1>TIC TAC TOE</h1><p><span>BlackGroup</span> Edition</p></div>
    <div class="user-input">
        <input type="text" id="player1" value="<?php echo isset($cookie_riga['utenti_username']) ? $cookie_riga['utenti_username'] : ''; ?>" disabled>
        <input type="text" id="player2" value="Ospite" disabled>
    </div>
    <div class="startBtn-container"><button id="startBtn">Avvia Partita</button></div>
</div>

<!-- SCHERMATA 2 -->
<div class="schermata-2">
<div class="giocatori">
<div class="container-p1">
<div class="giocatore1"></div>
<div class="punteggio1">0</div>
</div>

<div class="container-round">
<p>ROUND</p>
<p id="round">1</p>
</div>

<div class="container-p2">
<div class="giocatore2"></div>
<div class="punteggio2">0</div>
</div>
</div>

<div class="griglia">
<div class="riga">
<div class="cella" id="item1"></div>
<div class="cella" id="item2"></div>
<div class="cella" id="item3"></div>
</div>
<div class="riga">
<div class="cella" id="item4"></div>
<div class="cella" id="item5"></div>
<div class="cella" id="item6"></div>
</div>
<div class="riga">
<div class="cella" id="item7"></div>
<div class="cella" id="item8"></div>
<div class="cella" id="item9"></div>
</div>

<div class="turn-container">
<p>Turno di :</p>
<p id="turnOf"></p>
</div>
</div>
</div>

<!-- SCHERMATA 3 -->
<div class="schermata-3">
<div class="result-container">

<h1 class="titolo"><i>Risultato</i></h1>

<div id="player-winner"></div>

<div class="player-score-container">

<div class="player-names">
<div class="player-label x-label" id="labelX"></div>
<div class="player-label o-label" id="labelO"></div>
</div>

<div class="player-scores">
<div class="score-circle" id="scoreX"></div>
<div class="score-circle" id="scoreO"></div>
</div>

</div>

<hr class="score-divider">

<h2 class="mosse-title">Mosse:</h2>

<div class="history-container">
<div>
<div class="round">Round 1</div>
<p id="round1hystory"></p>
</div>
<div>
<div class="round">Round 2</div>
<p id="round2hystory"></p>
</div>
<div>
<div class="round">Round 3</div>
<p id="round3hystory"></p>
</div>
</div>

<div id="lista-mosse" class="mosse-list">

</div>

<button class="btn-ricomincia">
Ricomincia
</button>

</div>

</div>


<!-- ALERT PERSONALIZZATA E OVERLAY -->
<div id="customAlert" class="alert">
<p id="alertText"></p>
<button id="alertBtn">OK</button>
</div>

<div class="overlay"></div>

<!-- SCRIPT -->
<script src="script.js"></script>
</body>

</html>