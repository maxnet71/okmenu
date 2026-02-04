<?php
require_once __DIR__ . '/config/config.php';

$email = $_GET['email'] ?? '';

if (!$email) {
    header('Location: ' . BASE_URL . '/signup.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reinvia Verifica - OkMenu</title>
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
        <div class="col-md-5">
            <div class="card shadow-lg">
                <div class="card-body p-5 text-center">
                    <i class="bi bi-envelope-exclamation text-warning" style="font-size: 5rem;"></i>
                    <h3 class="mt-4 mb-3">Email Non Verificata</h3>
                    <p class="text-muted mb-4">
                        L'indirizzo email:<br>
                        <strong><?php echo htmlspecialchars($email); ?></strong><br>
                        non è ancora stato verificato.
                    </p>
                    
                    <div class="alert alert-info text-start">
                        <small>
                            <i class="bi bi-info-circle me-2"></i>
                            Clicca il pulsante qui sotto per ricevere una nuova email di verifica.
                        </small>
                    </div>
                    
                    <form method="POST" action="<?php echo BASE_URL; ?>/api/resend-verification.php" id="resendForm">
                        <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">
                        <button type="submit" class="btn btn-primary btn-lg w-100" id="btnResend">
                            <i class="bi bi-arrow-clockwise me-2"></i>
                            Reinvia Email di Verifica
                        </button>
                    </form>
                    
                    <div id="loading" class="mt-3" style="display:none;">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Invio...</span>
                        </div>
                        <p class="text-muted mt-2">Invio in corso...</p>
                    </div>
                    
                    <hr class="my-4">
                    
                    <small class="text-muted">
                        Email sbagliata? <a href="<?php echo BASE_URL; ?>/signup.php">Registrati di nuovo</a>
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById('resendForm').addEventListener('submit', function() {
    document.getElementById('btnResend').disabled = true;
    document.getElementById('loading').style.display = 'block';
});
</script>
</body>
</html>