<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/Database.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

try {
    // Gestisci sia onboarding che utenti loggati
    $isOnboarding = isset($_SESSION['onboarding_user_id']);
    $isLoggedIn = isset($_SESSION['user_id']);
    
    if (!$isOnboarding && !$isLoggedIn) {
        throw new Exception('Non autorizzato');
    }
    
    $userId = $isOnboarding ? $_SESSION['onboarding_user_id'] : $_SESSION['user_id'];
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (empty($input['template_id'])) {
        throw new Exception('Template non selezionato');
    }
    
    $templateId = (int)$input['template_id'];
    
    $db = Database::getInstance()->getConnection();
    
    // Verifica che il template esista
    $stmt = $db->prepare("SELECT * FROM menu_templates WHERE id = :id AND attivo = 1");
    $stmt->execute(['id' => $templateId]);
    $template = $stmt->fetch();
    
    if (!$template) {
        throw new Exception('Template non valido');
    }
    
    // Salva la scelta in sessione (verrà applicata al menu quando verrà creato)
    $_SESSION['selected_template'] = $template;
    
    // Aggiorna step onboarding
    $stmt = $db->prepare("UPDATE users SET onboarding_step = 3 WHERE id = :user_id");
    $stmt->execute(['user_id' => $userId]);
    
    $response['success'] = true;
    $response['message'] = 'Template salvato con successo';
    
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    error_log("Errore save-template: " . $e->getMessage());
}

echo json_encode($response);