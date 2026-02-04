<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/Model.php';
require_once __DIR__ . '/../classes/models/User.php';
require_once __DIR__ . '/../classes/models/LocaleRestaurant.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Dati non validi');
    }

    $required = ['nome', 'email', 'password', 'partita_iva', 'locale_nome', 'locale_tipo', 'locale_indirizzo', 'locale_citta', 'locale_cap'];
    foreach ($required as $field) {
        if (empty($input[$field])) {
            throw new Exception("Campo obbligatorio mancante: {$field}");
        }
    }

    $email = filter_var($input['email'], FILTER_VALIDATE_EMAIL);
    if (!$email) {
        throw new Exception('Email non valida');
    }

    if (!preg_match('/^[0-9]{11}$/', $input['partita_iva'])) {
        throw new Exception('Partita IVA non valida (deve essere di 11 cifre)');
    }

    if (!preg_match('/^[0-9]{5}$/', $input['locale_cap'])) {
        throw new Exception('CAP non valido (deve essere di 5 cifre)');
    }

    $db = Database::getInstance()->getConnection();

    $stmt = $db->prepare("SELECT id FROM users WHERE email = :email");
    $stmt->execute(['email' => $email]);
    if ($stmt->fetch()) {
        throw new Exception('Email già registrata');
    }

    $stmt = $db->prepare("SELECT id FROM users WHERE partita_iva = :partita_iva");
    $stmt->execute(['partita_iva' => $input['partita_iva']]);
    if ($stmt->fetch()) {
        throw new Exception('Partita IVA già registrata');
    }

    $verificationToken = bin2hex(random_bytes(32));
    $verificationSentAt = date('Y-m-d H:i:s');

    $db->beginTransaction();

    try {
        $stmt = $db->prepare("
            INSERT INTO users (nome, email, telefono, partita_iva, password, tipo, piano, email_verified, 
                               verification_token, verification_sent_at, onboarding_step, attivo)
            VALUES (:nome, :email, :telefono, :partita_iva, :password, 'ristoratore', 'free', 0, 
                    :token, :sent_at, 2, 0)
        ");
        
        $stmt->execute([
            'nome' => $input['nome'],
            'email' => $email,
            'telefono' => $input['telefono'] ?? null,
            'partita_iva' => $input['partita_iva'],
            'password' => password_hash($input['password'], PASSWORD_DEFAULT),
            'token' => $verificationToken,
            'sent_at' => $verificationSentAt
        ]);
        
        $userId = $db->lastInsertId();

        $localeSlug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $input['locale_nome'])));
        $localeSlug = $localeSlug . '-' . substr(md5($userId . time()), 0, 6);

        $stmt = $db->prepare("
            INSERT INTO locali (user_id, nome, tipo, indirizzo, citta, cap, slug, is_default, setup_completed, attivo)
            VALUES (:user_id, :nome, :tipo, :indirizzo, :citta, :cap, :slug, 1, 0, 1)
        ");
        
        $stmt->execute([
            'user_id' => $userId,
            'nome' => $input['locale_nome'],
            'tipo' => $input['locale_tipo'],
            'indirizzo' => $input['locale_indirizzo'],
            'citta' => $input['locale_citta'],
            'cap' => $input['locale_cap'],
            'slug' => $localeSlug
        ]);

        $verificationLink = BASE_URL . '/verify-email.php?token=' . $verificationToken;
        
        $emailSubject = 'Verifica il tuo account OkMenu';
        $emailBody = "
        <html>
        <body style='font-family: Arial, sans-serif; line-height: 1.6;'>
            <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                <h2 style='color: #667eea;'>Benvenuto su OkMenu!</h2>
                <p>Ciao <strong>{$input['nome']}</strong>,</p>
                <p>Grazie per esserti registrato. Per completare la registrazione, verifica il tuo indirizzo email cliccando sul pulsante qui sotto:</p>
                <p style='text-align: center; margin: 30px 0;'>
                    <a href='{$verificationLink}' style='background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; display: inline-block;'>
                        Verifica Email
                    </a>
                </p>
                <p>Oppure copia e incolla questo link nel tuo browser:</p>
                <p style='background: #f5f5f5; padding: 10px; word-break: break-all;'>{$verificationLink}</p>
                <p><small>Questo link scadrà tra 24 ore.</small></p>
                <hr style='margin: 30px 0; border: 1px solid #eee;'>
                <p style='color: #999; font-size: 12px;'>
                    Se non hai richiesto questa registrazione, ignora questa email.
                </p>
            </div>
        </body>
        </html>
        ";
        
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8\r\n";
        $headers .= "From: OkMenu <noreply@okmenu.cloud>\r\n";
        
        mail($email, $emailSubject, $emailBody, $headers);

        $db->commit();

        $response['success'] = true;
        $response['message'] = 'Registrazione completata! Controlla la tua email per verificare l\'account.';
        $response['user_id'] = $userId;

    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);