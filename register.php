<?php
require_once 'config/config.php';

require_once 'classes/Database.php';
require_once 'classes/Model.php';
require_once 'classes/Helpers.php';
require_once 'classes/models/User.php';

if (Helpers::isLoggedIn()) {
    Helpers::redirect(BASE_URL . '/dashboard/');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = Helpers::sanitizeInput($_POST['nome'] ?? '');
    $email = Helpers::sanitizeInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';
    
    if (empty($nome) || empty($email) || empty($password)) {
        $error = 'Tutti i campi sono obbligatori';
    } elseif (!Helpers::validateEmail($email)) {
        $error = 'Email non valida';
    } elseif (strlen($password) < 6) {
        $error = 'La password deve contenere almeno 6 caratteri';
    } elseif ($password !== $password_confirm) {
        $error = 'Le password non corrispondono';
    } else {
        $userModel = new User();
        
        if ($userModel->emailExists($email)) {
            $error = 'Email già registrata';
        } else {
            $userId = $userModel->register([
                'nome' => $nome,
                'email' => $email,
                'password' => $password,
                'tipo' => 'ristoratore'
            ]);
            
            if ($userId) {
                $success = 'Registrazione completata! Effettua il login.';
            } else {
                $error = 'Errore durante la registrazione';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrazione - Menu Digitale</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center align-items-center min-vh-100 py-5">
            <div class="col-md-6">
                <div class="card shadow-lg border-0">
                    <div class="card-body p-5">
                        <div class="text-center mb-4">
                            <i class="bi bi-qr-code display-4 text-primary"></i>
                            <h2 class="mt-3">Crea Account</h2>
                            <p class="text-muted">Inizia gratis in pochi secondi</p>
                        </div>

                        <?php if ($error): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="bi bi-exclamation-triangle me-2"></i>
                                <?php echo $error; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <?php if ($success): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="bi bi-check-circle me-2"></i>
                                <?php echo $success; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                            <div class="text-center">
                                <a href="login.php" class="btn btn-primary">Vai al Login</a>
                            </div>
                        <?php else: ?>
                            <form method="POST" action="">
                                <div class="mb-3">
                                    <label for="nome" class="form-label">Nome Completo</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-person"></i></span>
                                        <input type="text" class="form-control" id="nome" name="nome" 
                                               value="<?php echo $_POST['nome'] ?? ''; ?>" required>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="email" class="form-label">Email</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                        <input type="email" class="form-control" id="email" name="email" 
                                               value="<?php echo $_POST['email'] ?? ''; ?>" required>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="password" class="form-label">Password</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                        <input type="password" class="form-control" id="password" 
                                               name="password" required minlength="6">
                                    </div>
                                    <small class="text-muted">Minimo 6 caratteri</small>
                                </div>

                                <div class="mb-3">
                                    <label for="password_confirm" class="form-label">Conferma Password</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                                        <input type="password" class="form-control" id="password_confirm" 
                                               name="password_confirm" required>
                                    </div>
                                </div>

                                <div class="mb-3 form-check">
                                    <input type="checkbox" class="form-check-input" id="terms" required>
                                    <label class="form-check-label" for="terms">
                                        Accetto i <a href="#" target="_blank">Termini e Condizioni</a>
                                    </label>
                                </div>

                                <button type="submit" class="btn btn-primary w-100 py-2 mb-3">
                                    <i class="bi bi-person-plus me-2"></i>Registrati
                                </button>
                            </form>
                        <?php endif; ?>

                        <hr class="my-4">

                        <div class="text-center">
                            <p class="mb-0">Hai già un account? <a href="login.php" class="fw-bold">Accedi</a></p>
                        </div>
                    </div>
                </div>

                <div class="text-center mt-3">
                    <a href="<?php echo BASE_URL; ?>" class="text-muted text-decoration-none">
                        <i class="bi bi-arrow-left me-2"></i>Torna alla home
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
