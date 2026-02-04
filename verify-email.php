<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/classes/Database.php';

$token = $_GET['token'] ?? '';
$message = '';
$success = false;

if ($token) {
    try {
        $db = Database::getInstance()->getConnection();
        
        $stmt = $db->prepare("
            SELECT u.id, u.email, u.nome, u.onboarding_step
            FROM users u
            WHERE u.verification_token = :token
            AND u.email_verified = 0
            AND NOW() < DATE_ADD(u.verification_sent_at, INTERVAL 24 HOUR)
        ");
        $stmt->execute(['token' => $token]);
        $user = $stmt->fetch();
        
        if ($user) {
            $stmt = $db->prepare("
                UPDATE users 
                SET email_verified = 1, 
                    verification_token = NULL,
                    attivo = 1
                WHERE id = :user_id
            ");
            $stmt->execute(['user_id' => $user['id']]);
            
            $success = true;
            $message = 'Email verificata con successo!';
            
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['onboarding_step'] = $user['onboarding_step'];
            
        } else {
            $message = 'Link non valido o scaduto. Richiedi un nuovo link di verifica.';
        }
    } catch (Exception $e) {
        $message = 'Errore durante la verifica: ' . $e->getMessage();
    }
}

if (!$token && isset($_GET['email'])) {
    $email = $_GET['email'];
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifica Email - OkMenu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-lg">
                <div class="card-body p-5 text-center">
                    <?php if ($success): ?>
                        <i class="bi bi-check-circle-fill text-success" style="font-size: 5rem;"></i>
                        <h2 class="mt-4 mb-3">Email Verificata!</h2>
                        <p class="text-muted mb-4"><?php echo htmlspecialchars($message); ?></p>
                        
                        <div class="alert alert-info">
                            <p class="mb-0">
                                <i class="bi bi-info-circle me-2"></i>
                                Ora completa la configurazione del tuo menu digitale
                            </p>
                        </div>
                        
                        <a href="<?php echo BASE_URL; ?>/onboarding/step-template.php" class="btn btn-primary btn-lg w-100">
                            <i class="bi bi-arrow-right-circle me-2"></i>Continua Setup
                        </a>
                        
                    <?php elseif ($token): ?>
                        <i class="bi bi-x-circle-fill text-danger" style="font-size: 5rem;"></i>
                        <h2 class="mt-4 mb-3">Verifica Fallita</h2>
                        <p class="text-muted mb-4"><?php echo htmlspecialchars($message); ?></p>
                        
                        <form method="POST" action="<?php echo BASE_URL; ?>/resend-verification.php">
                            <div class="mb-3">
                                <input type="email" class="form-control" name="email" placeholder="La tua email" required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-envelope me-2"></i>Invia Nuovo Link
                            </button>
                        </form>
                        
                    <?php else: ?>
                        <i class="bi bi-envelope-check" style="font-size: 5rem; color: #667eea;"></i>
                        <h2 class="mt-4 mb-3">Controlla la Tua Email</h2>
                        <p class="text-muted mb-4">
                            Abbiamo inviato un link di verifica a:<br>
                            <strong><?php echo isset($email) ? htmlspecialchars($email) : 'la tua email'; ?></strong>
                        </p>
                        
                        <div class="alert alert-warning">
                            <small>
                                <i class="bi bi-exclamation-triangle me-2"></i>
                                Non hai ricevuto l'email? Controlla anche la cartella SPAM
                            </small>
                        </div>
                        
                        <form method="POST" action="<?php echo BASE_URL; ?>/resend-verification.php" class="mt-4">
                            <input type="hidden" name="email" value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>">
                            <button type="submit" class="btn btn-outline-primary w-100">
                                <i class="bi bi-arrow-clockwise me-2"></i>Reinvia Email di Verifica
                            </button>
                        </form>
                    <?php endif; ?>
                    
                    <hr class="my-4">
                    
                    <div class="text-center">
                        <a href="<?php echo BASE_URL; ?>/login.php" class="text-muted">
                            <i class="bi bi-box-arrow-in-right me-2"></i>Torna al Login
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>