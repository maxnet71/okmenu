<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/Database.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

try {
    // Verifica sessione onboarding
    if (!isset($_SESSION['onboarding_user_id'])) {
        throw new Exception('Sessione non valida. Effettua nuovamente la registrazione.');
    }
    
    $userId = $_SESSION['onboarding_user_id'];
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (empty($input['password'])) {
        throw new Exception('Password obbligatoria');
    }
    
    $password = $input['password'];
    
    // Validazione password (come nel form)
    if (strlen($password) < 8) {
        throw new Exception('La password deve essere di almeno 8 caratteri');
    }
    
    if (!preg_match('/[0-9]/', $password)) {
        throw new Exception('La password deve contenere almeno un numero');
    }
    
    if (!preg_match('/[a-zA-Z]/', $password)) {
        throw new Exception('La password deve contenere almeno una lettera');
    }
    
    $db = Database::getInstance()->getConnection();
    
    // Verifica che l'utente esista e non abbia già una password
    $stmt = $db->prepare("
        SELECT id, email, password 
        FROM users 
        WHERE id = :user_id 
        AND email_verified = 1
    ");
    $stmt->execute(['user_id' => $userId]);
    $user = $stmt->fetch();
    
    if (!$user) {
        throw new Exception('Utente non trovato o email non verificata');
    }
    
    // Aggiorna password e completa onboarding
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    $stmt = $db->prepare("
        UPDATE users 
        SET password = :password,
            onboarding_completed = 1,
            onboarding_step = 0,
            attivo = 1
        WHERE id = :user_id
    ");
    
    $stmt->execute([
        'password' => $hashedPassword,
        'user_id' => $userId
    ]);
    
    // Login automatico
    $_SESSION['user_id'] = $userId;
    $_SESSION['user_email'] = $user['email'];
    
    // Pulisci sessione onboarding
    unset($_SESSION['onboarding_user_id']);
    unset($_SESSION['onboarding_email']);
    
    $response['success'] = true;
    $response['message'] = 'Password impostata con successo!';
    
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    $response['debug'] = [
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'session_has_onboarding' => isset($_SESSION['onboarding_user_id']),
        'onboarding_id' => $_SESSION['onboarding_user_id'] ?? null
    ];
    error_log("Errore set password: " . $e->getMessage());
}

echo json_encode($response);