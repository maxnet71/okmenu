<?php
/**
 * API: Rifiuta Ordine
 * Endpoint per rifiutare ordine e annullare prenotazione pagamento
 * 
 * POSIZIONE: /api/ordini/rifiuta.php
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../classes/Database.php';
require_once __DIR__ . '/../../classes/Model.php';
require_once __DIR__ . '/../../classes/Helpers.php';
require_once __DIR__ . '/../../classes/models/User.php';
require_once __DIR__ . '/../../classes/models/Ordine.php';
require_once __DIR__ . '/../../classes/models/Pagamento.php';
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

// Verifica CSRF
if (!Helpers::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Token CSRF non valido']);
    exit;
}

try {
    $ordineId = intval($_POST['ordine_id'] ?? 0);
    $motivo = Helpers::sanitizeInput($_POST['motivo'] ?? 'Ordine non disponibile');
    
    if (!$ordineId) {
        throw new Exception('ID ordine mancante');
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
    
    // Rifiuta ordine
    $success = $ordineModel->rifiutaOrdine($ordineId, $user['id'], $motivo);
    
    if (!$success) {
        throw new Exception('Errore durante il rifiuto dell\'ordine');
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Ordine rifiutato con successo',
        'ordine_id' => $ordineId
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}