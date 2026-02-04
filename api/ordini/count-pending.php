<?php
/**
 * API: Count Pending Orders
 * Conta ordini in attesa per badge notifica
 * 
 * POSIZIONE: /api/ordini/count-pending.php
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../classes/Database.php';

header('Content-Type: application/json');

$localeId = intval($_GET['locale'] ?? 0);

if (!$localeId) {
    echo json_encode(['count' => 0]);
    exit;
}

try {
    $sql = "SELECT COUNT(*) as count FROM ordini 
            WHERE locale_id = :locale_id 
            AND stato = 'attesa_conferma'";
    
    $stmt = Database::getInstance()->getConnection()->prepare($sql);
    $stmt->execute(['locale_id' => $localeId]);
    $result = $stmt->fetch();
    
    echo json_encode(['count' => intval($result['count'] ?? 0)]);
    
} catch (Exception $e) {
    echo json_encode(['count' => 0]);
}