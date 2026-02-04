<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/Database.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

try {
    $isOnboarding = isset($_SESSION['onboarding_user_id']);
    
    if (!$isOnboarding) {
        throw new Exception('Non autorizzato');
    }
    
    $userId = $_SESSION['onboarding_user_id'];
    
    $db = Database::getInstance()->getConnection();
    
    // Verifica utente
    $stmt = $db->prepare("SELECT id FROM users WHERE id = :id AND email_verified = 1");
    $stmt->execute(['id' => $userId]);
    if (!$stmt->fetch()) {
        throw new Exception('Utente non valido');
    }
    
    // Marca onboarding come completato senza menu
    $stmt = $db->prepare("
        UPDATE users 
        SET onboarding_step = 4
        WHERE id = :user_id
    ");
    $stmt->execute(['user_id' => $userId]);
    
    $response['success'] = true;
    $response['message'] = 'Onboarding aggiornato';
    
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    error_log("Errore complete-onboarding: " . $e->getMessage());
}

echo json_encode($response);