<?php
/**
 * API: Create Payment Intent
 * Crea PaymentIntent Stripe per autorizzazione pagamento
 * 
 * POSIZIONE: /api/ordini/create-payment-intent.php
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../classes/Database.php';
require_once __DIR__ . '/../../classes/Model.php';
require_once __DIR__ . '/../../classes/models/LocaleRestaurant.php';
require_once __DIR__ . '/../../classes/models/Pagamento.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Metodo non consentito']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $localeId = intval($input['locale_id'] ?? 0);
    $carrello = $input['carrello'] ?? [];
    $totale = floatval($input['totale'] ?? 0);
    
    if (!$localeId || empty($carrello) || $totale <= 0) {
        throw new Exception('Parametri mancanti o non validi');
    }
    
    // Verifica locale
    $localeModel = new LocaleRestaurant();
    $locale = $localeModel->getById($localeId);
    
    if (!$locale || !$locale['attivo']) {
        throw new Exception('Locale non trovato o non attivo');
    }
    
    // Ottieni configurazione
    $sql = "SELECT * FROM ordini_configurazioni WHERE locale_id = :locale_id";
    $stmt = Database::getInstance()->getConnection()->prepare($sql);
    $stmt->execute(['locale_id' => $localeId]);
    $config = $stmt->fetch();
    
    if (!$config || !$config['ordini_attivi']) {
        throw new Exception('Ordini non attivi per questo locale');
    }
    
    // Verifica chiavi Stripe
    if (empty($config['stripe_secret_key'])) {
        throw new Exception('Chiavi Stripe non configurate nel database. Esegui sync-stripe-keys.php');
    }
    
    // Crea Payment Intent
    try {
        $pagamento = new Pagamento(
            $config['gateway_pagamento'] ?? 'stripe',
            $config['modalita_test'] ?? true,
            $localeId
        );
        
        $result = $pagamento->autorizza($totale, [
            'locale_id' => $localeId,
            'locale_nome' => $locale['nome'],
            'num_items' => count($carrello)
        ]);
        
        if (!$result) {
            throw new Exception('Errore creazione PaymentIntent: autorizza() ha restituito false');
        }
    } catch (Exception $e) {
        throw new Exception('Errore Stripe: ' . $e->getMessage());
    }
    
    echo json_encode([
        'success' => true,
        'id' => $result['auth_id'],
        'client_secret' => $result['client_secret'],
        'amount' => $result['amount']
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}