<?php
session_start();
try {

    // Avvio la connessione al database MySQL tramite la classe mysqli di PHP nella quale specifico 4 parametri (host,username,password,nome_database)
    $connessione = new mysqli("localhost", "root", "root", "tic_tac_toe");

} catch (mysqli_sql_exception $e) {

    echo "Errore di connessione: " . $e->getMessage();
}

// Verifica se l'utente è loggato
if (!isset($_COOKIE['utenti_id'])) {
    echo json_encode(['success' => false, 'message' => 'Utente non autenticato']);
    exit;
}

$utentiId = $_COOKIE['utenti_id'];

// Legge i dati inviati via Fetch (formato JSON)
$data = json_decode(file_get_contents('php://input'), true);
$esito = $data['esito']; // 'vittoria', 'sconfitta' o 'pareggio'

// Logica di aggiornamento DB (esempio semplificato su tabella utenti)
if ($esito == 'vittoria') {
    $sql = "UPDATE partite SET partite_vinte = partite_vinte + 1 WHERE utenti_id = '".$utentiId ."'";
} elseif ($esito == 'pareggio') {
    $sql = "UPDATE partite SET partite_pareggiate = partite_pareggiate + 1 WHERE utenti_id = '".$utentiId ."'";
} elseif ($esito == 'sconfitta') {
    $sql = "UPDATE partite SET partite_perse = partite_perse + 1 WHERE utenti_id  = '".$utentiId ."'";
}

$connessione->query($sql);

// Recupera le statistiche aggiornate per l'interfaccia
$risultato = $connessione->query("SELECT partite_vinte, partite_perse, partite_pareggiate FROM partite WHERE utenti_id = '".$utentiId ."'");
$stats = $risultato->fetch_assoc();


// Restituisce le nuove statistiche in JSON
echo json_encode(['success' => true, 'partite_vinte' => $stats['partite_vinte'], 'partite_pareggiate' => $stats['partite_pareggiate'], 'partite_perse' => $stats['partite_perse']]);
?>