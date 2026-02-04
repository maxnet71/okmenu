<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/classes/Database.php';

$token = $_GET['token'] ?? '';
$email = $_GET['email'] ?? '';
$verified = false;
$error = '';
$flashMessage = '';
$flashType = '';

// Recupera flash message se presente
if (isset($_SESSION['flash_message'])) {
    $flashMessage = $_SESSION['flash_message'];
    $flashType = $_SESSION['flash_type'] ?? 'info';
    unset($_SESSION['flash_message']);
    unset($_SESSION['flash_type']);
}

// Se c'è il token, verifica subito
if ($token) {
    try {
        $db = Database::getInstance()->getConnection();
        
        $stmt = $db->prepare("
            SELECT u.id, u.email, u.nome, u.verification_expires
            FROM users u
            WHERE u.verification_token = :token
            AND u.email_verified = 0
        ");
        $stmt->execute(['token' => $token]);
        $user = $stmt->fetch();
        
        if (!$user) {
            $error = 'Link non valido o già utilizzato.';
        } elseif (strtotime($user['verification_expires']) < time()) {
            $error = 'Link scaduto. Richiedi un nuovo link di verifica.';
        } else {
            // Verifica email
            $stmt = $db->prepare("
                UPDATE users 
                SET email_verified = 1,
                    verification_token = NULL,
                    verification_expires = NULL
                WHERE id = :user_id
            ");
            $stmt->execute(['user_id' => $user['id']]);
            
            $verified = true;
            $email = $user['email'];
            
            // Salva ID in sessione per gli step successivi
            $_SESSION['onboarding_user_id'] = $user['id'];
            $_SESSION['onboarding_email'] = $user['email'];
        }
    } catch (Exception $e) {
        $error = 'Errore durante la verifica: ' . $e->getMessage();
        error_log($error);
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $verified ? '✅ Email Verificata' : '📧 Verifica Email'; ?> - OkMenu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            padding: 20px;
        }
        .verify-icon {
            font-size: 6rem;
            margin-bottom: 20px;
        }
        .countdown {
            font-size: 1.5rem;
            font-weight: bold;
            color: #667eea;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-lg border-0">
                <div class="card-body p-5 text-center">
                    
                    <?php if ($flashMessage): ?>
                        <!-- Flash Message -->
                        <div class="alert alert-<?php echo htmlspecialchars($flashType); ?> alert-dismissible fade show">
                            <?php echo $flashMessage; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($verified): ?>
                        <!-- EMAIL VERIFICATA - SUCCESSO -->
                        <i class="bi bi-check-circle-fill text-success verify-icon"></i>
                        <h2 class="mb-3">Email Verificata!</h2>
                        <p class="lead text-muted mb-4">
                            Perfetto! Il tuo account è stato verificato con successo.
                        </p>
                        
                        <div class="alert alert-info">
                            <h5><i class="bi bi-arrow-right-circle me-2"></i>Prossimi Step</h5>
                            <ol class="text-start mb-0">
                                <li>Scegli lo stile del tuo menu</li>
                                <li>Carica il menu (AI o manuale)</li>
                                <li>Imposta la tua password</li>
                                <li>Il tuo menu sarà online!</li>
                            </ol>
                        </div>
                        
                        <p class="text-muted mb-4">
                            Reindirizzamento automatico tra <span class="countdown" id="countdown">5</span> secondi...
                        </p>
                        
                        <a href="<?php echo BASE_URL; ?>/onboarding/step-template.php" class="btn btn-primary btn-lg w-100">
                            <i class="bi bi-rocket-takeoff me-2"></i>Continua Ora
                        </a>
                        
                        <script>
                        let seconds = 5;
                        const countdownEl = document.getElementById('countdown');
                        const interval = setInterval(() => {
                            seconds--;
                            countdownEl.textContent = seconds;
                            if (seconds <= 0) {
                                clearInterval(interval);
                                window.location.href = '<?php echo BASE_URL; ?>/onboarding/step-template.php';
                            }
                        }, 1000);
                        </script>
                        
                    <?php elseif ($error): ?>
                        <!-- ERRORE VERIFICA -->
                        <i class="bi bi-x-circle-fill text-danger verify-icon"></i>
                        <h2 class="mb-3">Verifica Fallita</h2>
                        <p class="text-danger mb-4"><?php echo htmlspecialchars($error); ?></p>
                        
                        <div class="alert alert-warning">
                            <i class="bi bi-info-circle me-2"></i>
                            Hai bisogno di un nuovo link di verifica?
                        </div>
                        
                        <form method="POST" action="<?php echo BASE_URL; ?>/api/resend-verification.php" class="mb-3">
                            <div class="input-group mb-3">
                                <input type="email" class="form-control" name="email" 
                                       placeholder="La tua email" required 
                                       value="<?php echo htmlspecialchars($email); ?>">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-arrow-clockwise me-2"></i>Reinvia
                                </button>
                            </div>
                        </form>
                        
                        <a href="signup.php" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left me-2"></i>Torna alla Registrazione
                        </a>
                        
                    <?php else: ?>
                        <!-- IN ATTESA VERIFICA -->
                        <i class="bi bi-envelope-check verify-icon text-primary"></i>
                        <h2 class="mb-3">Controlla la Tua Email</h2>
                        <p class="lead text-muted mb-4">
                            Abbiamo inviato un link di verifica a:<br>
                            <strong><?php echo htmlspecialchars($email); ?></strong>
                        </p>
                        
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            <strong>Non trovi l'email?</strong><br>
                            Controlla anche la cartella SPAM o Promozioni
                        </div>
                        
                        <div class="card bg-light mb-4">
                            <div class="card-body">
                                <h6 class="fw-bold mb-3">
                                    <i class="bi bi-list-check me-2"></i>Prossimi Step
                                </h6>
                                <ol class="text-start mb-0 small">
                                    <li>Clicca sul link nell'email</li>
                                    <li>Scegli lo stile del menu</li>
                                    <li>Carica il tuo menu</li>
                                    <li>Crea la password</li>
                                    <li>Il tuo menu sarà online!</li>
                                </ol>
                            </div>
                        </div>
                        
                        <form method="POST" action="<?php echo BASE_URL; ?>/api/resend-verification.php">
                            <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">
                            <button type="submit" class="btn btn-outline-primary w-100">
                                <i class="bi bi-arrow-clockwise me-2"></i>
                                Reinvia Email di Verifica
                            </button>
                        </form>
                        
                        <hr class="my-4">
                        
                        <small class="text-muted">
                            Email sbagliata? <a href="signup.php">Registrati di nuovo</a>
                        </small>
                    <?php endif; ?>
                    
                </div>
            </div>
            
            <!-- Help -->
            <div class="text-center mt-4">
                <small class="text-white">
                    <i class="bi bi-question-circle me-1"></i>
                    Hai problemi? <a href="mailto:support@okmenu.cloud" class="text-white fw-bold">Contattaci</a>
                </small>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>