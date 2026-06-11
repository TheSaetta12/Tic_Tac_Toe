<?php
// 1. Inclusione della configurazione centralizzata
// Gestisce la connessione $connessione e l'avvio della sessione
require_once 'db_config.php';

// Impostazione dell'header per la risposta JSON richiesta dalla Fetch API di script.js
header('Content-Type: application/json');

// 2. Verifica Autenticazione
// Verifichiamo il cookie utenti_id impostato dopo il superamento della 2FA
if (!isset($_COOKIE['utenti_id'])) {
    echo json_encode(['success' => false, 'message' => 'Sessione non valida o utente non autenticato']);
    exit;
}

$utentiId = $_COOKIE['utenti_id'];

// 3. Recupero e Validazione Dati
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['esito'])) {
    echo json_encode(['success' => false, 'message' => 'Dati dell\'esito mancanti']);
    exit;
}

$esito = $data['esito']; // 'vittoria', 'sconfitta' o 'pareggio'

// Mappatura sicura degli esiti sulle colonne effettive della tabella 'partite'
$colonne_valide = [
    'vittoria'  => 'partite_vinte',
    'sconfitta' => 'partite_perse',
    'pareggio'  => 'partite_pareggiate'
];

if (!array_key_exists($esito, $colonne_valide)) {
    echo json_encode(['success' => false, 'message' => 'Esito non valido']);
    exit;
}

$colonna_target = $colonne_valide[$esito];

// 4. Aggiornamento Statistiche con Prepared Statement
// L'uso di "i" nel bind_param garantisce che $utentiId sia trattato solo come intero
try {
    // Eseguiamo l'UPDATE. Nota: la riga dell'utente deve già esistere nella tabella 'partite'
    $stmt_update = $connessione->prepare("UPDATE partite SET $colonna_target = $colonna_target + 1 WHERE utenti_id = ?");
    $stmt_update->bind_param("i", $utentiId);
    $stmt_update->execute();
    
    // Verifichiamo se l'UPDATE ha effettivamente modificato una riga
    if ($stmt_update->affected_rows === 0) {
        // Se non ci sono righe colpite, probabilmente manca il record iniziale per questo ID
        // Creiamo il record per gestire il "problema del salvataggio" in modo dinamico
        $stmt_insert = $connessione->prepare("INSERT INTO partite (utenti_id, $colonna_target) VALUES (?, 1) ON DUPLICATE KEY UPDATE $colonna_target = $colonna_target + 1");
        $stmt_insert->bind_param("i", $utentiId);
        $stmt_insert->execute();
        $stmt_insert->close();
    }
    $stmt_update->close();

    // 5. Recupero Statistiche Aggiornate
    // Necessario per aggiornare l'interfaccia finale (Schermata 3) dello script.js
    $stmt_select = $connessione->prepare("SELECT partite_vinte, partite_perse, partite_pareggiate FROM partite WHERE utenti_id = ?");
    $stmt_select->bind_param("i", $utentiId);
    $stmt_select->execute();
    $risultato = $stmt_select->get_result();
    $stats = $risultato->fetch_assoc();
    $stmt_select->close();

    // 6. Risposta finale al frontend
    echo json_encode([
        'success' => true,
        'partite_vinte' => $stats['partite_vinte'] ?? 0,
        'partite_pareggiate' => $stats['partite_pareggiate'] ?? 0,
        'partite_perse' => $stats['partite_perse'] ?? 0
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Errore database: ' . $e->getMessage()]);
}

// Chiusura connessione
$connessione->close();
?>