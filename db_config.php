<?php
// Avvio della sessione (necessario sia per CAPTCHA che per 2FA)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Parametri di connessione al database
$db_host = "localhost";
$db_user = "root";
$db_pass = "root";
$db_name = "tic_tac_toe";

// Variabili globali di sicurezza
$ip_utente = $_SERVER['REMOTE_ADDR'];
$limite_tentativi = 5;
$intervallo_minuti = 2;

try {
    // Creazione della connessione tramite mysqli
    $connessione = new mysqli($db_host, $db_user, $db_pass, $db_name);
    
    // Imposta il set di caratteri per evitare problemi con accenti
    $connessione->set_charset("utf8mb4");

} catch (mysqli_sql_exception $e) {
    // In caso di errore, interrompiamo l'esecuzione con un messaggio chiaro
    die("Errore critico di connessione al database: " . $e->getMessage());
}
?>