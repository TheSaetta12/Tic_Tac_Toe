<?php
// 1. Inclusione della configurazione centralizzata
// Questo file gestisce session_start(), la connessione $connessione e le variabili di sicurezza
require_once 'db_config.php';

// Impostazione dell'header per la risposta JSON richiesta dallo script.js
header('Content-Type: application/json');

// 2. Verifica Autenticazione
// Verifichiamo la presenza del cookie utenti_id, che garantisce che l'utente abbia superato la 2FA
if (!isset($_COOKIE['utenti_id'])) {
    echo json_encode(['success' => false, 'message' => 'Utente non autenticato']);
    exit;
}

$utentiId = $_COOKIE['utenti_id'];

// 3. Recupero e Validazione Dati
// Leggiamo il corpo della richiesta inviata tramite Fetch API
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['esito'])) {
    echo json_encode(['success' => false, 'message' => 'Dati dell\'esito mancanti']);
    exit;
}

$esito = $data['esito']; // 'vittoria', 'sconfitta' o 'pareggio'

// Mappatura sicura degli esiti sulle colonne della tabella 'partite'
$colonne_valide = [
    'vittoria' => 'partite_vinte',
    'sconfitta' => 'partite_perse',
    'pareggio' => 'partite_pareggiate'
];

if (!array_key_exists($esito, $colonne_valide)) {
    echo json_encode(['success' => false, 'message' => 'Esito della partita non valido']);
    exit;
}

$colonna_target = $colonne_valide[$esito];

// 4. Aggiornamento Statistiche
// Utilizziamo i prepared statements per evitare che utenti malintenzionati manipolino il cookie
try {
    $stmt_update = $connessione->prepare("UPDATE partite SET $colonna_target = $colonna_target + 1 WHERE utenti_id = ?");
    $stmt_update->bind_param("i", $utentiId);
    $stmt_update->execute();
    $stmt_update->close();

    // 5. Recupero Statistiche Aggiornate
    // Interroghiamo il DB per restituire i nuovi valori totali all'interfaccia di gioco
    $stmt_select = $connessione->prepare("SELECT partite_vinte, partite_perse, partite_pareggiate FROM partite WHERE utenti_id = ?");
    $stmt_select->bind_param("i", $utentiId);
    $stmt_select->execute();
    $risultato = $stmt_select->get_result();
    $stats = $risultato->fetch_assoc();
    $stmt_select->close();

    // Risposta finale inviata allo script.js
    echo json_encode([
        'success' => true,
        'partite_vinte' => $stats['partite_vinte'],
        'partite_pareggiate' => $stats['partite_pareggiate'],
        'partite_perse' => $stats['partite_perse']
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Errore durante il salvataggio: ' . $e->getMessage()]);
}

// Chiusura della connessione
$connessione->close();
?>