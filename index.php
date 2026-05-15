<?php
    error_reporting(0);
?>

<!DOCTYPE html>
<html lang="it">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tic Tac Toe</title>
    <!-- CSS -->
    <link rel="stylesheet" href="style.css">

    <!-- FAVICON -->
    <link rel="shortcut icon" href="favicon-32x32.png" type="image/x-icon">

    <!-- FONT -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Merriweather:ital,opsz,wght@0,18..144,300..900;1,18..144,300..900&display=swap"
        rel="stylesheet">
</head>

<body>
    <!-- SCHERMATA 0 -->
    <div class="schermata-0">
        
        <?php
            if(!isset($_COOKIE['utenti_id'])){
                ?>
                <form method="POST">
                    Registrati
                    <input type="text" name="register_username" placeholder="username" required>
                    <input type="text" name="register_password" placeholder="password" required>
                    <button type="submit">Invia</button>
                </form>
                <form method="POST">
                    Login
                    <input type="text" name="login_username" placeholder="username" required>
                    <input type="text" name="login_password" placeholder="password" required>
                    <button type="submit">Invia</button>
                </form>
                <?php
            }
        ?>
        
    </div>

    <?php
        try {

            // Avvio la connessione al database MySQL tramite la classe mysqli di PHP nella quale specifico 4 parametri (host,username,password,nome_database)
            $connessione = new mysqli("localhost", "root", "root", "tic_tac_toe");

        } catch (mysqli_sql_exception $e) {

            echo "Errore di connessione: " . $e->getMessage();
        }

        $controllo = false;

        if(isset($_POST['register_username'])) {

            $username=$_POST['register_username'];
            $password=$_POST['register_password'];
            $risultato = $connessione->query("SELECT * FROM utenti");

            while ($riga = $risultato->fetch_assoc()){
                if($username==$riga['utenti_username']){
                    echo "username non disponibile";
                    $controllo = true;
                    break;
                }
            }

            // Invio la query per l'inserimento dei dati nel database
            if ($controllo == false) {

                if($connessione->query("INSERT INTO utenti SET utenti_username='$username', utenti_password='$password'")){
                
                    echo "Registrazione effettuata";
                
                } 
            }
            else {
                $controllo = false;
            }
            
        }

        if(isset($_POST['login_username'])) {

            $username=$_POST['login_username'];
            $password=$_POST['login_password'];

            $risultato = $connessione->query("SELECT * FROM utenti WHERE utenti_username = '$username' && utenti_password = '$password'");

            $riga = $risultato->fetch_assoc();

            if (isset($riga['utenti_username'])) {

                setcookie("utenti_id", $riga['utenti_id'], strtotime("+1 year"));

                header("location: index.php");
                
            } else {
                
                echo "Credenziali errate";

            }

        }

        if(!isset($_POST['login_username'])){

            if(isset($_COOKIE['utenti_id']))
            {
                $id = $_COOKIE['utenti_id'];
                $cookie_risultato = $connessione->query("SELECT * FROM utenti WHERE utenti_id='$id'");
                $cookie_riga = $cookie_risultato->fetch_assoc();

                echo "Benvenuto utente " . $cookie_riga['utenti_username'] . "<br>";

                echo "<form METHOD = 'POST'>
                        <button type='submit' name='logout'> logout </button>
                    </form>";
            }

        }

        if(isset($_POST['logout'])){

            setcookie("utenti_id", "", strtotime("-1 hour"));
            header("Location: index.php");
            exit;
        }

        // Chiudo la connessione
	    $connessione->close();
    ?>

    <!-- SCHERMATA 1 -->
    <div class="schermata-1">
        <div class="logo">
            <h1>TIC TAC TOE</h1>
            <p><span>BlackGroup</span> Edition</p>
        </div>

        <div class="user-input">
            <input type="text" id="player1" placeholder="Player 1" value="<?php echo $cookie_riga['utenti_username'] ?>" disabled>
            <input type="text" id="player2" placeholder="Player 2" value="Ospite" autocomplete="off" maxlength="12" disabled>
        </div>

        <div class="startBtn-container">
            <button id="startBtn">Avvia Partita</button>
        </div>
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