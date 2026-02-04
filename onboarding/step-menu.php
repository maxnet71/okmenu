<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/Model.php';
require_once __DIR__ . '/../classes/models/LocaleRestaurant.php';

// Gestisci sia onboarding che utenti loggati
$isOnboarding = isset($_SESSION['onboarding_user_id']);
$isLoggedIn = isset($_SESSION['user_id']);

if (!$isOnboarding && !$isLoggedIn) {
    header('Location: ' . BASE_URL . '/signup.php');
    exit;
}

$userId = $isOnboarding ? $_SESSION['onboarding_user_id'] : $_SESSION['user_id'];

// Carica info utente
$db = Database::getInstance()->getConnection();
$stmt = $db->prepare("SELECT * FROM users WHERE id = :id");
$stmt->execute(['id' => $userId]);
$user = $stmt->fetch();

if (!$user) {
    header('Location: ' . BASE_URL . '/signup.php');
    exit;
}

if ($user['onboarding_completed']) {
    header('Location: ' . BASE_URL . '/dashboard/');
    exit;
}

$localeModel = new LocaleRestaurant();
$locali = $localeModel->getByUserId($userId);
$locale = $locali[0] ?? null;

if (!$locale) {
    header('Location: ' . BASE_URL . '/signup.php');
    exit;
}

$pageTitle = 'Carica il Tuo Menu';
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - OkMenu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        .method-card {
            cursor: pointer;
            transition: all 0.3s;
            border: 3px solid transparent;
            height: 100%;
        }
        .method-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            border-color: #667eea;
        }
        .method-icon {
            font-size: 4rem;
            color: #667eea;
        }
        .progress-bar-onboarding {
            height: 8px;
            background: #e9ecef;
            border-radius: 10px;
            overflow: hidden;
        }
        .progress-bar-onboarding .fill {
            height: 100%;
            background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
        }
    </style>
</head>
<body class="bg-light">

<div class="container py-5">
    <!-- Progress -->
    <div class="row justify-content-center mb-4">
        <div class="col-md-8">
            <div class="text-center mb-3">
                <small class="text-muted">Passo 3 di 3</small>
            </div>
            <div class="progress-bar-onboarding">
                <div class="fill" style="width: 100%;"></div>
            </div>
        </div>
    </div>

    <!-- Header -->
    <div class="text-center mb-5">
        <h1 class="display-5 fw-bold mb-3"><?php echo $pageTitle; ?></h1>
        <p class="lead text-muted">
            Scegli come vuoi creare il tuo menu digitale
        </p>
    </div>

    <!-- Methods -->
    <div class="row g-4 mb-5">
        <!-- AI Upload -->
        <div class="col-md-6">
            <div class="card method-card" onclick="showAIUpload()">
                <div class="card-body text-center p-5">
                    <i class="bi bi-magic method-icon"></i>
                    <h3 class="mt-4 mb-3">Carica Menu Cartaceo</h3>
                    <p class="text-muted mb-4">
                        Scatta foto del tuo menu o carica un PDF.<br>
                        L'intelligenza artificiale lo digitalizzerà automaticamente.
                    </p>
                    <div class="badge bg-success mb-3">Più Veloce</div>
                    <ul class="list-unstyled text-start">
                        <li class="mb-2"><i class="bi bi-check-circle text-success me-2"></i>Setup in 2 minuti</li>
                        <li class="mb-2"><i class="bi bi-check-circle text-success me-2"></i>Riconoscimento automatico</li>
                        <li class="mb-2"><i class="bi bi-check-circle text-success me-2"></i>Estrae prezzi e descrizioni</li>
                        <li class="mb-2"><i class="bi bi-check-circle text-success me-2"></i>1 caricamento gratuito</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Manual Entry -->
        <div class="col-md-6">
            <div class="card method-card" onclick="showManualEntry()">
                <div class="card-body text-center p-5">
                    <i class="bi bi-pencil-square method-icon"></i>
                    <h3 class="mt-4 mb-3">Inserisci Manualmente</h3>
                    <p class="text-muted mb-4">
                        Crea il menu da zero inserendo categorie e piatti uno alla volta.
                    </p>
                    <div class="badge bg-info mb-3">Controllo Totale</div>
                    <ul class="list-unstyled text-start">
                        <li class="mb-2"><i class="bi bi-check-circle text-success me-2"></i>Massima precisione</li>
                        <li class="mb-2"><i class="bi bi-check-circle text-success me-2"></i>Personalizzazione completa</li>
                        <li class="mb-2"><i class="bi bi-check-circle text-success me-2"></i>Nessun limite</li>
                        <li class="mb-2"><i class="bi bi-check-circle text-success me-2"></i>Ideale per menu piccoli</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Skip Option -->
    <div class="text-center">
        <p class="text-muted mb-3">Oppure</p>
        <button type="button" class="btn btn-outline-secondary" onclick="skipForNow()">
            <i class="bi bi-skip-forward me-2"></i>Salta per Ora
        </button>
        <br>
        <small class="text-muted">Potrai aggiungere il menu in seguito dalla dashboard</small>
    </div>
</div>

<!-- AI Upload Modal -->
<div class="modal fade" id="modalAI" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-magic me-2"></i>Carica Menu con AI</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">
                    <i class="bi bi-info-circle me-2"></i>
                    Carica tutte le pagine del tuo menu. L'AI analizzerà automaticamente categorie, piatti e prezzi.
                </div>

                <div class="mb-4">
                    <input type="hidden" id="localeId" value="<?php echo $locale['id']; ?>">
                    
                    <div class="d-flex gap-2 mb-3">
                        <button type="button" class="btn btn-primary" id="btnCamera">
                            <i class="bi bi-camera"></i> Scatta Foto
                        </button>
                        <button type="button" class="btn btn-secondary" onclick="document.getElementById('fileInput').click()">
                            <i class="bi bi-file-earmark-image"></i> Carica File
                        </button>
                        <input type="file" id="fileInput" accept="image/*,.pdf" style="display:none" multiple>
                    </div>

                    <div id="cameraContainer" style="display:none;" class="mb-3">
                        <video id="video" width="100%" style="max-width:500px; border:2px solid #ddd; border-radius:8px;" autoplay></video>
                        <div class="mt-2">
                            <button type="button" class="btn btn-success" id="btnCapture">Scatta</button>
                            <button type="button" class="btn btn-danger" id="btnCloseCamera">Chiudi</button>
                        </div>
                        <canvas id="canvas" style="display:none;"></canvas>
                    </div>
                </div>

                <div id="pagesContainer">
                    <h6>Pagine Caricate: <span id="pageCount">0</span></h6>
                    <div id="pagesList" class="row g-2"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                <button type="button" class="btn btn-primary" id="btnProcess" disabled>
                    <i class="bi bi-magic"></i> Elabora con AI
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?php echo BASE_URL; ?>/assets/js/upload-menu-camera.js"></script>
<script>
function showAIUpload() {
    new bootstrap.Modal(document.getElementById('modalAI')).show();
}

function showManualEntry() {
    window.location.href = '<?php echo BASE_URL; ?>/onboarding/manual-menu.php';
}

async function skipForNow() {
    if (!confirm('Vuoi completare il setup senza aggiungere il menu? Potrai farlo dopo dalla dashboard.')) return;
    
    try {
        const response = await fetch('<?php echo BASE_URL; ?>/api/complete-onboarding.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ skip_menu: true })
        });
        
        const result = await response.json();
        
        if (result.success) {
            window.location.href = '<?php echo BASE_URL; ?>/dashboard/';
        } else {
            throw new Error(result.message || 'Errore');
        }
    } catch (error) {
        alert('Errore: ' + error.message);
    }
}

document.getElementById('btnProcess')?.addEventListener('click', function() {
    // Reindirizza al processo multi-pagina esistente
    const form = new FormData();
    form.append('locale_id', document.getElementById('localeId').value);
    
    pages.forEach(page => {
        form.append('pages[]', page.file);
    });
    
    fetch('<?php echo BASE_URL; ?>/process-menu-multi.php', {
        method: 'POST',
        body: form
    })
    .then(res => res.json())
    .then(result => {
        if (result.success) {
            window.location.href = '<?php echo BASE_URL; ?>/preview-menu.php?session=' + result.session_id + '&onboarding=1';
        } else {
            alert('Errore: ' + result.message);
        }
    });
});
</script>
</body>
</html>