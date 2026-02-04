<?php
/**
 * API: Cambio Stato Ordine
 * Endpoint generico per cambiare stato ordine
 * 
 * POSIZIONE: /api/ordini/cambio-stato.php
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../classes/Database.php';
require_once __DIR__ . '/../../classes/Model.php';
require_once __DIR__ . '/../../classes/Helpers.php';
require_once __DIR__ . '/../../classes/models/User.php';
require_once __DIR__ . '/../../classes/models/Ordine.php';
require_once __DIR__ . '/../../classes/NotificationManager.php';

header('Content-Type: application/json');

// Verifica autenticazione
Helpers::requireLogin();
$user = Helpers::getUser();

// Verifica metodo POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Metodo non consentito']);
    exit;
}

try {
    $ordineId = intval($_POST['ordine_id'] ?? 0);
    $nuovoStato = $_POST['stato'] ?? '';
    $note = Helpers::sanitizeInput($_POST['note'] ?? '');
    
    if (!$ordineId || !$nuovoStato) {
        throw new Exception('Parametri mancanti');
    }
    
    // Stati permessi per cambio manuale
    $statiPermessi = [
        'in_preparazione',
        'pronto_ritiro',
        'in_consegna',
        'completato',
        'annullato'
    ];
    
    if (!in_array($nuovoStato, $statiPermessi)) {
        throw new Exception('Stato non valido');
    }
    
    $ordineModel = new Ordine();
    $ordine = $ordineModel->getById($ordineId);
    
    if (!$ordine) {
        throw new Exception('Ordine non trovato');
    }
    
    // Verifica che l'ordine appartenga a un locale dell'utente
    $sql = "SELECT user_id FROM locali WHERE id = :locale_id";
    $stmt = Database::getInstance()->getConnection()->prepare($sql);
    $stmt->execute(['locale_id' => $ordine['locale_id']]);
    $locale = $stmt->fetch();
    
    if (!$locale || $locale['user_id'] != $user['id']) {
        throw new Exception('Non autorizzato');
    }
    
    // Cambia stato
    $success = $ordineModel->cambiaStato($ordineId, $nuovoStato, $user['id'], $note);
    
    if (!$success) {
        throw new Exception('Errore durante il cambio stato');
    }
    
    // Invia notifiche se necessario
    $notificationManager = new NotificationManager();
    $ordineAggiornato = $ordineModel->getWithDetails($ordineId);
    
    switch ($nuovoStato) {
        case 'pronto_ritiro':
            $notificationManager->inviaOrdineProto($ordineAggiornato);
            break;
        case 'in_consegna':
            $notificationManager->inviaOrdineInConsegna($ordineAggiornato);
            break;
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Stato aggiornato con successo',
        'ordine_id' => $ordineId,
        'nuovo_stato' => $nuovoStato
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}