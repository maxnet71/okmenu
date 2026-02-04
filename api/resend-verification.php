<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/EmailSender.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

try {
    // Accetta sia POST JSON che POST form
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    if (strpos($contentType, 'application/json') !== false) {
        $input = json_decode(file_get_contents('php://input'), true);
    } else {
        $input = $_POST;
    }
    
    if (empty($input['email'])) {
        throw new Exception('Email obbligatoria');
    }

    $email = filter_var($input['email'], FILTER_VALIDATE_EMAIL);
    if (!$email) {
        throw new Exception('Email non valida');
    }

    $db = Database::getInstance()->getConnection();

    $stmt = $db->prepare("
        SELECT id, nome, email 
        FROM users 
        WHERE email = :email 
        AND email_verified = 0
    ");
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch();

    if (!$user) {
        throw new Exception('Email non trovata o già verificata');
    }

    $newToken = bin2hex(random_bytes(32));
    $newExpires = date('Y-m-d H:i:s', strtotime('+24 hours'));

    $stmt = $db->prepare("
        UPDATE users 
        SET verification_token = :token,
            verification_expires = :expires
        WHERE id = :user_id
    ");
    $stmt->execute([
        'token' => $newToken,
        'expires' => $newExpires,
        'user_id' => $user['id']
    ]);

    $verificationLink = BASE_URL . '/verify-signup.php?token=' . $newToken;
    
    $emailSubject = '🔄 Nuovo Link di Verifica - OkMenu';
    $emailBody = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
            .content { background: #f9f9f9; padding: 30px; }
            .button { display: inline-block; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white !important; padding: 15px 40px; text-decoration: none; border-radius: 50px; font-weight: bold; margin: 20px 0; }
            .footer { background: #333; color: white; padding: 20px; text-align: center; border-radius: 0 0 10px 10px; font-size: 12px; }
            .link-box { background: #e9ecef; padding: 15px; border-radius: 8px; word-break: break-all; font-family: monospace; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1 style='margin: 0;'>🔄 Nuovo Link di Verifica</h1>
            </div>
            <div class='content'>
                <p>Ciao <strong>" . htmlspecialchars($user['nome']) . "</strong>,</p>
                
                <p>Hai richiesto un nuovo link di verifica per il tuo account OkMenu.</p>
                
                <p>Clicca sul pulsante qui sotto per verificare il tuo indirizzo email:</p>
                
                <p style='text-align: center;'>
                    <a href='{$verificationLink}' class='button'>
                        ✅ Verifica Email
                    </a>
                </p>
                
                <p>Oppure copia e incolla questo link nel tuo browser:</p>
                <div class='link-box'>{$verificationLink}</div>
                
                <p><strong>⏰ Questo link scadrà tra 24 ore.</strong></p>
                
                <hr style='margin: 30px 0; border: none; border-top: 1px solid #ddd;'>
                
                <p><small><strong>Non hai richiesto questo?</strong> Ignora questa email.</small></p>
            </div>
            <div class='footer'>
                <p style='margin: 0;'>© 2025 OkMenu - Menu Digitali per Ristoratori</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    // Invia email con EmailSender
    try {
        $emailSender = new EmailSender();
        $emailSender->send($email, $emailSubject, $emailBody);
    } catch (Exception $e) {
        error_log("Errore invio email: " . $e->getMessage());
        // Non bloccare, continua
    }

    $response['success'] = true;
    $response['message'] = 'Email di verifica inviata! Controlla la tua casella di posta.';

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    error_log("Errore resend verification: " . $e->getMessage());
}

// Se è POST form, fai sempre redirect alla pagina bella
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_SESSION['flash_message'] = $response['message'];
    $_SESSION['flash_type'] = $response['success'] ? 'success' : 'danger';
    
    $redirectUrl = BASE_URL . '/verify-signup.php';
    if (!empty($input['email'])) {
        $redirectUrl .= '?email=' . urlencode($input['email']);
    }
    
    header('Location: ' . $redirectUrl);
    exit;
}

// Solo per chiamate API dirette
echo json_encode($response);