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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = Helpers::sanitizeInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = 'Inserisci email e password';
    } else {
        $userModel = new User();
        $user = $userModel->authenticate($email, $password);
        
        if ($user) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_nome'] = $user['nome'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_tipo'] = $user['tipo'];
            
            Helpers::redirect(BASE_URL . '/dashboard/');
        } else {
            $error = 'Credenziali non valide';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Menu Digitale</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center align-items-center min-vh-100">
            <div class="col-md-5">
                <div class="card shadow-lg border-0">
                    <div class="card-body p-5">
                        <div class="text-center mb-4">
                            <i class="bi bi-qr-code display-4 text-primary"></i>
                            <h2 class="mt-3">Menu Digitale</h2>
                            <p class="text-muted">Accedi al tuo account</p>
                        </div>

                        <?php if ($error): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="bi bi-exclamation-triangle me-2"></i>
                                <?php echo $error; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="">
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
                                    <input type="password" class="form-control" id="password" name="password" required>
                                </div>
                            </div>

                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="remember">
                                <label class="form-check-label" for="remember">Ricordami</label>
                            </div>

                            <button type="submit" class="btn btn-primary w-100 py-2 mb-3">
                                <i class="bi bi-box-arrow-in-right me-2"></i>Accedi
                            </button>

                            <div class="text-center">
                                <a href="forgot-password.php" class="text-decoration-none">Password dimenticata?</a>
                            </div>
                        </form>

                        <hr class="my-4">

                        <div class="text-center">
                            <p class="mb-0">Non hai un account? <a href="register.php" class="fw-bold">Registrati</a></p>
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
