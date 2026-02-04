<?php
require_once __DIR__ . '/config/config.php';

if (isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/dashboard/');
    exit;
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crea il Tuo Menu Digitale Gratuito - OkMenu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        :root {
            --primary: #667eea;
            --secondary: #764ba2;
        }
        
        body {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            padding: 40px 0;
        }
        
        .signup-container {
            max-width: 600px;
            margin: 0 auto;
        }
        
        .feature-badge {
            display: inline-block;
            padding: 8px 16px;
            background: rgba(255,255,255,0.2);
            border-radius: 20px;
            color: white;
            font-size: 0.9rem;
            margin: 5px;
        }
        
        .form-floating label {
            color: #6c757d;
        }
        
        .btn-cta {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            border: none;
            padding: 15px;
            font-size: 1.1rem;
            font-weight: 600;
            transition: transform 0.2s;
        }
        
        .btn-cta:hover {
            transform: scale(1.02);
        }
    </style>
</head>
<body>

<div class="container">
    <div class="signup-container">
        <!-- Header -->
        <div class="text-center mb-4">
            <h1 class="text-white fw-bold mb-3">
                <i class="bi bi-qr-code-scan"></i> OkMenu
            </h1>
            <h2 class="text-white mb-3">Menu Digitale Gratuito</h2>
            <p class="text-white opacity-75 mb-4">
                Crea il tuo menu digitale professionale in 5 minuti.<br>
                Nessuna carta di credito richiesta.
            </p>
            
            <!-- Features -->
            <div class="mb-4">
                <span class="feature-badge"><i class="bi bi-check-circle me-1"></i>Setup in 5 minuti</span>
                <span class="feature-badge"><i class="bi bi-check-circle me-1"></i>AI incluso</span>
                <span class="feature-badge"><i class="bi bi-check-circle me-1"></i>QR Code gratis</span>
            </div>
        </div>
        
        <!-- Form Card -->
        <div class="card shadow-lg border-0">
            <div class="card-body p-4 p-md-5">
                <h4 class="text-center mb-4">Inizia Gratis</h4>
                
                <!-- Alert Errore/Successo -->
                <div id="alertContainer" style="display:none;">
                    <div class="alert alert-dismissible fade show" role="alert" id="alertBox">
                        <span id="alertMessage"></span>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                </div>
                
                <div class="alert alert-info mb-4">
                    <i class="bi bi-info-circle me-2"></i>
                    Ti invieremo un'email per verificare il tuo indirizzo prima di continuare
                </div>
                
                <form id="signupForm">
                    <!-- Nome Locale -->
                    <div class="form-floating mb-3">
                        <input type="text" class="form-control" id="locale_nome" name="locale_nome" 
                               placeholder="Nome Locale" required>
                        <label for="locale_nome">
                            <i class="bi bi-shop me-2"></i>Nome del Locale *
                        </label>
                    </div>
                    
                    <!-- Tipo Locale -->
                    <div class="form-floating mb-3">
                        <select class="form-select" id="locale_tipo" name="locale_tipo" required>
                            <option value="">Seleziona tipo...</option>
                            <option value="ristorante">Ristorante</option>
                            <option value="pizzeria">Pizzeria</option>
                            <option value="bar">Bar / Caffetteria</option>
                            <option value="pub">Pub / Birreria</option>
                            <option value="trattoria">Trattoria</option>
                            <option value="osteria">Osteria</option>
                            <option value="hotel">Hotel / B&B</option>
                            <option value="gelateria">Gelateria</option>
                            <option value="pasticceria">Pasticceria</option>
                            <option value="altro">Altro</option>
                        </select>
                        <label for="locale_tipo">
                            <i class="bi bi-tag me-2"></i>Tipo di Locale *
                        </label>
                    </div>
                    
                    <hr class="my-4">
                    
                    <!-- Referente -->
                    <div class="form-floating mb-3">
                        <input type="text" class="form-control" id="referente_nome" name="referente_nome" 
                               placeholder="Nome Referente" required>
                        <label for="referente_nome">
                            <i class="bi bi-person me-2"></i>Nome e Cognome Referente *
                        </label>
                    </div>
                    
                    <!-- Email -->
                    <div class="form-floating mb-3">
                        <input type="email" class="form-control" id="email" name="email" 
                               placeholder="Email" required>
                        <label for="email">
                            <i class="bi bi-envelope me-2"></i>Email *
                        </label>
                        <small class="text-muted">Useremo questa email per accedere al tuo account</small>
                    </div>
                    
                    <!-- Telefono -->
                    <div class="form-floating mb-4">
                        <input type="tel" class="form-control" id="telefono" name="telefono" 
                               placeholder="Telefono">
                        <label for="telefono">
                            <i class="bi bi-telephone me-2"></i>Telefono (opzionale)
                        </label>
                    </div>
                    
                    <!-- Privacy -->
                    <div class="form-check mb-4">
                        <input class="form-check-input" type="checkbox" id="privacy" required>
                        <label class="form-check-label" for="privacy">
                            Accetto i <a href="#" target="_blank">Termini di Servizio</a> e 
                            la <a href="#" target="_blank">Privacy Policy</a> *
                        </label>
                    </div>
                    
                    <!-- Submit -->
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary btn-cta" id="btnSubmit">
                            <i class="bi bi-arrow-right-circle me-2"></i>
                            Continua - È Gratis!
                        </button>
                    </div>
                    
                    <!-- Progress -->
                    <div id="submitProgress" class="mt-3" style="display:none;">
                        <div class="progress">
                            <div class="progress-bar progress-bar-striped progress-bar-animated" 
                                 role="progressbar" style="width: 100%"></div>
                        </div>
                        <p class="text-center mt-2 text-muted">
                            <i class="bi bi-hourglass-split me-2"></i>
                            Creazione account in corso...
                        </p>
                    </div>
                    
                    <!-- Login Link -->
                    <div class="text-center mt-4">
                        <small class="text-muted">
                            Hai già un account? 
                            <a href="login.php" class="fw-bold">Accedi</a>
                        </small>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Trust Badges -->
        <div class="text-center mt-4">
            <small class="text-white opacity-75">
                <i class="bi bi-shield-check me-1"></i>I tuoi dati sono al sicuro
                <span class="mx-2">|</span>
                <i class="bi bi-credit-card me-1"></i>Nessun pagamento richiesto
                <span class="mx-2">|</span>
                <i class="bi bi-x-circle me-1"></i>Cancellazione in qualsiasi momento
            </small>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById('signupForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    // Nascondi eventuali errori precedenti
    document.getElementById('alertContainer').style.display = 'none';
    
    const formData = new FormData(this);
    const data = Object.fromEntries(formData.entries());
    
    // Validazione
    if (!data.locale_nome || !data.locale_tipo || !data.referente_nome || !data.email) {
        showError('Compila tutti i campi obbligatori');
        return;
    }
    
    if (!document.getElementById('privacy').checked) {
        showError('Devi accettare i Termini di Servizio e la Privacy Policy');
        return;
    }
    
    const btnSubmit = document.getElementById('btnSubmit');
    const progress = document.getElementById('submitProgress');
    
    btnSubmit.disabled = true;
    progress.style.display = 'block';
    
    try {
        const response = await fetch('<?php echo BASE_URL; ?>/api/signup-step1.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (result.success) {
            // Redirect a pagina verifica email
            window.location.href = '<?php echo BASE_URL; ?>/verify-signup.php?email=' + 
                                   encodeURIComponent(data.email);
        } else {
            throw new Error(result.message || 'Errore durante la registrazione');
        }
    } catch (error) {
        // Mostra errore nella pagina
        const alertContainer = document.getElementById('alertContainer');
        const alertBox = document.getElementById('alertBox');
        const alertMessage = document.getElementById('alertMessage');
        
        alertContainer.style.display = 'block';
        alertBox.className = 'alert alert-danger alert-dismissible fade show';
        alertMessage.innerHTML = error.message;
        
        // Scroll to alert
        alertContainer.scrollIntoView({ behavior: 'smooth', block: 'center' });
        
        btnSubmit.disabled = false;
        progress.style.display = 'none';
    }
});

// Auto-format telefono
document.getElementById('telefono').addEventListener('input', function(e) {
    let value = e.target.value.replace(/\D/g, '');
    if (value.length > 0) {
        e.target.value = value;
    }
});

// Helper per mostrare errori
function showError(message) {
    const alertContainer = document.getElementById('alertContainer');
    const alertBox = document.getElementById('alertBox');
    const alertMessage = document.getElementById('alertMessage');
    
    alertContainer.style.display = 'block';
    alertBox.className = 'alert alert-danger alert-dismissible fade show';
    alertMessage.innerHTML = message;
    
    alertContainer.scrollIntoView({ behavior: 'smooth', block: 'center' });
}
</script>
</body>
</html>