<?php
/**
 * Webhook Stripe Handler
 * POSIZIONE: /api/pagamenti/webhook.php
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../classes/Database.php';
require_once __DIR__ . '/../../classes/Model.php';
require_once __DIR__ . '/../../classes/models/Ordine.php';
require_once __DIR__ . '/../../classes/models/Pagamento.php';

header('Content-Type: application/json');

$payload = @file_get_contents('php://input');
$sigHeader = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

if (!$payload || !$sigHeader) {
    http_response_code(400);
    echo json_encode(['error' => 'Payload o firma mancanti']);
    exit;
}

try {
    $pagamento = new Pagamento('stripe', defined('STRIPE_TEST_MODE') ? STRIPE_TEST_MODE : true);
    $event = $pagamento->handleWebhook($payload, $sigHeader);
    
    if (!$event) {
        throw new Exception('Firma webhook non valida');
    }
    
    $eventType = $event['type'];
    $paymentIntent = $event['data'];
    
    error_log("Webhook Stripe: {$eventType} - {$paymentIntent->id}");
    
    $ordineModel = new Ordine();
    $sql = "SELECT * FROM ordini WHERE pagamento_id = :payment_id LIMIT 1";
    $stmt = Database::getInstance()->getConnection()->prepare($sql);
    $stmt->execute(['payment_id' => $paymentIntent->id]);
    $ordine = $stmt->fetch();
    
    if (!$ordine) {
        error_log("Ordine non trovato per PaymentIntent: {$paymentIntent->id}");
        echo json_encode(['received' => true]);
        exit;
    }
    
    switch ($eventType) {
        case 'payment_intent.succeeded':
            $ordineModel->update($ordine['id'], [
                'pagamento_stato' => 'captured',
                'pagamento_catturato_at' => date('Y-m-d H:i:s')
            ]);
            break;
            
        case 'payment_intent.payment_failed':
            $ordineModel->update($ordine['id'], [
                'pagamento_stato' => 'failed',
                'stato' => 'annullato'
            ]);
            break;
            
        case 'payment_intent.canceled':
            $ordineModel->update($ordine['id'], [
                'pagamento_stato' => 'voided',
                'pagamento_annullato_at' => date('Y-m-d H:i:s')
            ]);
            break;
    }
    
    $sql = "UPDATE pagamenti_webhook_log 
            SET processato = 1, ordine_id = :ordine_id 
            WHERE event_id = :event_id";
    $stmt = Database::getInstance()->getConnection()->prepare($sql);
    $stmt->execute(['ordine_id' => $ordine['id'], 'event_id' => $event['event_id']]);
    
    echo json_encode(['received' => true]);
    
} catch (Exception $e) {
    error_log("Errore webhook: " . $e->getMessage());
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}