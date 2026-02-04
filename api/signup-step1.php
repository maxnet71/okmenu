<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/EmailSender.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Dati non validi');
    }

    // Validazione campi obbligatori
    $required = ['locale_nome', 'locale_tipo', 'referente_nome', 'email'];
    foreach ($required as $field) {
        if (empty($input[$field])) {
            throw new Exception("Campo obbligatorio mancante: {$field}");
        }
    }

    $email = filter_var($input['email'], FILTER_VALIDATE_EMAIL);
    if (!$email) {
        throw new Exception('Email non valida');
    }

    $db = Database::getInstance()->getConnection();

    // Verifica se email già esistente
    $stmt = $db->prepare("SELECT id, email_verified FROM users WHERE email = :email");
    $stmt->execute(['email' => $email]);
    $existingUser = $stmt->fetch();
    
    if ($existingUser) {
        if ($existingUser['email_verified']) {
            throw new Exception('Email già registrata. <a href="' . BASE_URL . '/login.php" class="alert-link fw-bold">Accedi qui</a>');
        } else {
            throw new Exception('Email già registrata ma non verificata. <a href="' . BASE_URL . '/resend-email.php?email=' . urlencode($email) . '" class="alert-link fw-bold">Reinvia email di verifica</a>');
        }
    }

    // Genera token verifica
    $verificationToken = bin2hex(random_bytes(32));
    $verificationExpires = date('Y-m-d H:i:s', strtotime('+24 hours'));

    $db->beginTransaction();

    try {
        // Crea slug per locale
        $localeSlug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $input['locale_nome'])));
        $localeSlug = $localeSlug . '-' . substr(md5($email . time()), 0, 6);

        // Inserisci user SENZA password (la imposterà dopo verifica email)
        $stmt = $db->prepare("
            INSERT INTO users (
                nome, email, telefono, password, tipo, piano, 
                email_verified, verification_token, verification_expires, 
                onboarding_step, attivo
            ) VALUES (
                :nome, :email, :telefono, '', 'ristoratore', 'free',
                0, :token, :expires, 2, 0
            )
        ");
        
        $stmt->execute([
            'nome' => $input['referente_nome'],
            'email' => $email,
            'telefono' => $input['telefono'] ?? null,
            'token' => $verificationToken,
            'expires' => $verificationExpires
        ]);
        
        $userId = $db->lastInsertId();

        // Crea locale di default
        $stmt = $db->prepare("
            INSERT INTO locali (
                user_id, nome, tipo, slug, is_default, attivo
            ) VALUES (
                :user_id, :nome, :tipo, :slug, 1, 1
            )
        ");
        
        $stmt->execute([
            'user_id' => $userId,
            'nome' => $input['locale_nome'],
            'tipo' => $input['locale_tipo'],
            'slug' => $localeSlug
        ]);
        
        $localeId = $db->lastInsertId();

        // Invia email verifica
        $verificationLink = BASE_URL . '/verify-signup.php?token=' . $verificationToken;
        
        $emailSubject = '🎉 Verifica il tuo account OkMenu';
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
                    <h1 style='margin: 0;'>🎉 Benvenuto su OkMenu!</h1>
                </div>
                <div class='content'>
                    <p>Ciao <strong>" . htmlspecialchars($input['referente_nome']) . "</strong>,</p>
                    
                    <p>Grazie per aver scelto OkMenu per il tuo <strong>" . htmlspecialchars($input['locale_nome']) . "</strong>!</p>
                    
                    <p>Per completare la registrazione e iniziare a creare il tuo menu digitale, verifica il tuo indirizzo email cliccando sul pulsante qui sotto:</p>
                    
                    <p style='text-align: center;'>
                        <a href='{$verificationLink}' class='button'>
                            ✅ Verifica Email e Continua
                        </a>
                    </p>
                    
                    <p>Oppure copia e incolla questo link nel tuo browser:</p>
                    <div class='link-box'>{$verificationLink}</div>
                    
                    <p><strong>⏰ Questo link scadrà tra 24 ore.</strong></p>
                    
                    <hr style='margin: 30px 0; border: none; border-top: 1px solid #ddd;'>
                    
                    <p><strong>🚀 Cosa succederà dopo:</strong></p>
                    <ol>
                        <li>Sceglierai lo stile del tuo menu</li>
                        <li>Caricherai il menu (con AI o manualmente)</li>
                        <li>Creerai la tua password</li>
                        <li>Il tuo menu sarà online!</li>
                    </ol>
                    
                    <p>Tutto in meno di 5 minuti. ⚡</p>
                </div>
                <div class='footer'>
                    <p style='margin: 0;'>Se non hai richiesto questa registrazione, ignora questa email.</p>
                    <p style='margin: 10px 0 0 0;'>© 2025 OkMenu - Menu Digitali per Ristoratori</p>
                </div>
            </div>
        </body>
        </html>
        ";
        
        // Invia email con EmailSender (SMTP Aruba)
        try {
            $emailSender = new EmailSender();
            $emailSender->send($email, $emailSubject, $emailBody);
        } catch (Exception $e) {
            // Log errore ma non blocca registrazione
            error_log("Errore invio email: " . $e->getMessage());
        }

        $db->commit();

        $response['success'] = true;
        $response['message'] = 'Account creato! Controlla la tua email per continuare.';
        $response['verification_token'] = $verificationToken;
        $response['user_id'] = $userId;
        $response['locale_id'] = $localeId;

    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    $response['error_details'] = [
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ];
    error_log("Errore signup step1: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());
}

echo json_encode($response);