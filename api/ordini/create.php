<?php
/**
 * API: Create Ordine
 * Crea ordine completo con pagamento già autorizzato
 * 
 * POSIZIONE: /api/ordini/create.php
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../classes/Database.php';
require_once __DIR__ . '/../../classes/Model.php';
require_once __DIR__ . '/../../classes/Helpers.php';
require_once __DIR__ . '/../../classes/models/LocaleRestaurant.php';
require_once __DIR__ . '/../../classes/models/Ordine.php';
require_once __DIR__ . '/../../classes/models/Piatto.php';
require_once __DIR__ . '/../../classes/models/Pagamento.php';
require_once __DIR__ . '/../../classes/NotificationManager.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Metodo non consentito']);
    exit;
}

try {
    $localeId = intval($_POST['locale_id'] ?? 0);
    $carrello = json_decode($_POST['carrello'] ?? '[]', true);
    $paymentIntentId = $_POST['payment_intent_id'] ?? '';
    $nomeCliente = Helpers::sanitizeInput($_POST['nome_cliente'] ?? '');
    $telefonoCliente = Helpers::sanitizeInput($_POST['telefono_cliente'] ?? '');
    $emailCliente = Helpers::sanitizeInput($_POST['email_cliente'] ?? '');
    $tipo = $_POST['tipo'] ?? 'asporto';
    $indirizzoConsegna = Helpers::sanitizeInput($_POST['indirizzo_consegna'] ?? '');
    $note = Helpers::sanitizeInput($_POST['note'] ?? '');
    
    if (!$localeId || empty($carrello) || !$paymentIntentId) {
        throw new Exception('Parametri mancanti');
    }
    
    if (!$nomeCliente || !$telefonoCliente || !$emailCliente) {
        throw new Exception('Dati cliente mancanti');
    }
    
    if (!filter_var($emailCliente, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Email non valida');
    }
    
    if ($tipo === 'delivery' && !$indirizzoConsegna) {
        throw new Exception('Indirizzo consegna obbligatorio');
    }
    
    $localeModel = new LocaleRestaurant();
    $locale = $localeModel->getById($localeId);
    
    if (!$locale || !$locale['attivo']) {
        throw new Exception('Locale non disponibile');
    }
    
    $sql = "SELECT * FROM ordini_configurazioni WHERE locale_id = :locale_id";
    $stmt = Database::getInstance()->getConnection()->prepare($sql);
    $stmt->execute(['locale_id' => $localeId]);
    $config = $stmt->fetch();
    
    if (!$config || !$config['ordini_attivi']) {
        throw new Exception('Ordini non attivi');
    }
    
    if ($tipo === 'asporto' && !$config['ordini_asporto_attivi']) {
        throw new Exception('Ordini asporto non disponibili');
    }
    
    if ($tipo === 'delivery' && !$config['ordini_consegna_attivi']) {
        throw new Exception('Ordini consegna non disponibili');
    }
    
    $piattoModel = new Piatto();
    $subtotale = 0;
    $carelloValidato = [];
    
    foreach ($carrello as $item) {
        $piatto = $piattoModel->getById($item['piatto_id']);
        
        if (!$piatto || !$piatto['disponibile']) {
            throw new Exception("Piatto {$item['nome']} non più disponibile");
        }
        
        $carelloValidato[] = [
            'piatto_id' => $piatto['id'],
            'nome' => $piatto['nome'],
            'prezzo' => floatval($piatto['prezzo']),
            'quantita' => intval($item['quantita']),
            'note' => $item['note'] ?? null,
            'varianti' => []
        ];
        
        $subtotale += floatval($piatto['prezzo']) * intval($item['quantita']);
    }
    
    $costiAggiuntivi = 0;
    if ($tipo === 'delivery' && $config['costo_consegna_fisso'] > 0) {
        $costiAggiuntivi = floatval($config['costo_consegna_fisso']);
    }
    
    if ($tipo === 'delivery' && $config['ordine_minimo_consegna'] > 0) {
        if ($subtotale < $config['ordine_minimo_consegna']) {
            throw new Exception("Ordine minimo per consegna: €" . number_format($config['ordine_minimo_consegna'], 2, ',', '.'));
        }
    }
    
    $totale = $subtotale + $costiAggiuntivi;
    
    $ordineData = [
        'locale_id' => $localeId,
        'tipo' => $tipo,
        'nome_cliente' => $nomeCliente,
        'telefono_cliente' => $telefonoCliente,
        'email_cliente' => $emailCliente,
        'indirizzo_consegna' => $tipo === 'delivery' ? $indirizzoConsegna : null,
        'note' => $note,
        'subtotale' => $subtotale,
        'costi_aggiuntivi' => $costiAggiuntivi,
        'totale' => $totale,
        'pagamento_id' => $paymentIntentId,
        'pagamento_provider' => $config['gateway_pagamento'] ?? 'stripe',
        'pagamento_importo' => $totale,
        'pagamento_valuta' => 'EUR'
    ];
    
    $ordineModel = new Ordine();
    $ordineId = $ordineModel->createOrdineAsporto($ordineData, $carelloValidato, false);
    
    if (!$ordineId) {
        throw new Exception('Errore creazione ordine');
    }
    
    $ordineModel->update($ordineId, [
        'pagamento_id' => $paymentIntentId,
        'pagamento_stato' => 'authorized',
        'pagamento_prenotato_at' => date('Y-m-d H:i:s'),
        'stato' => 'attesa_conferma'
    ]);
    
    $ordine = $ordineModel->getWithDetails($ordineId);
    
    $notificationManager = new NotificationManager();
    $notificationManager->inviaNotificaNuovoOrdine($ordine);
    
    echo json_encode([
        'success' => true,
        'message' => 'Ordine creato con successo',
        'ordine_id' => $ordineId,
        'numero_ordine' => $ordine['numero_ordine'],
        'tracking_token' => $ordine['tracking_token']
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    error_log("Errore creazione ordine: " . $e->getMessage());
}