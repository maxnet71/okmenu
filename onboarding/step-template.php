<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/Model.php';

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

// Se onboarding completato, redirect dashboard
if ($user['onboarding_completed']) {
    header('Location: ' . BASE_URL . '/dashboard/');
    exit;
}

// Carica template
$stmt = $db->query("SELECT * FROM menu_templates WHERE attivo = 1 ORDER BY ordinamento");
$templates = $stmt->fetchAll();

$pageTitle = 'Scegli lo Stile del Menu';
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
        .template-card {
            cursor: pointer;
            transition: all 0.3s;
            border: 3px solid transparent;
        }
        .template-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        .template-card.selected {
            border-color: #667eea;
            box-shadow: 0 0 20px rgba(102, 126, 234, 0.3);
        }
        .template-preview {
            height: 300px;
            background-size: cover;
            background-position: center;
            border-radius: 8px 8px 0 0;
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
            transition: width 0.3s;
        }
    </style>
</head>
<body class="bg-light">

<div class="container py-5">
    <!-- Progress -->
    <div class="row justify-content-center mb-4">
        <div class="col-md-8">
            <div class="text-center mb-3">
                <small class="text-muted">Passo 2 di 3</small>
            </div>
            <div class="progress-bar-onboarding">
                <div class="fill" style="width: 66%;"></div>
            </div>
        </div>
    </div>

    <!-- Header -->
    <div class="text-center mb-5">
        <h1 class="display-5 fw-bold mb-3"><?php echo $pageTitle; ?></h1>
        <p class="lead text-muted">
            Seleziona il design che meglio rappresenta il tuo locale.<br>
            Potrai personalizzare colori e font in seguito.
        </p>
    </div>

    <!-- Templates Grid -->
    <div class="row g-4 mb-5">
        <?php foreach ($templates as $template): ?>
        <div class="col-md-6 col-lg-3">
            <div class="card template-card h-100" onclick="selectTemplate(<?php echo $template['id']; ?>, '<?php echo $template['stile']; ?>')">
                <div class="template-preview" style="background: linear-gradient(135deg, <?php echo $template['colore_primario']; ?> 0%, <?php echo $template['colore_secondario']; ?> 100%);">
                    <div class="d-flex align-items-center justify-content-center h-100 text-white">
                        <div class="text-center p-4">
                            <h3 style="font-family: <?php echo $template['font_titoli']; ?>;">Menu</h3>
                            <p style="font-family: <?php echo $template['font_testo']; ?>;" class="mb-0">Esempio Piatto</p>
                            <small style="font-family: <?php echo $template['font_testo']; ?>;">€12.50</small>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <h5 class="card-title"><?php echo htmlspecialchars($template['nome']); ?></h5>
                    <p class="card-text text-muted small"><?php echo htmlspecialchars($template['descrizione']); ?></p>
                    <div class="d-flex gap-2 mt-3">
                        <span class="badge bg-light text-dark border"><?php echo $template['font_titoli']; ?></span>
                        <div class="flex-grow-1"></div>
                        <div style="width: 30px; height: 30px; background: <?php echo $template['colore_primario']; ?>; border-radius: 50%;"></div>
                        <div style="width: 30px; height: 30px; background: <?php echo $template['colore_secondario']; ?>; border-radius: 50%; border: 1px solid #dee2e6;"></div>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Actions -->
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="d-flex gap-3">
                <button type="button" class="btn btn-outline-secondary flex-fill" onclick="window.history.back()">
                    <i class="bi bi-arrow-left me-2"></i>Indietro
                </button>
                <button type="button" class="btn btn-primary flex-fill" id="btnNext" disabled>
                    Continua <i class="bi bi-arrow-right ms-2"></i>
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
let selectedTemplate = null;

function selectTemplate(id, style) {
    document.querySelectorAll('.template-card').forEach(card => {
        card.classList.remove('selected');
    });
    
    event.currentTarget.classList.add('selected');
    selectedTemplate = { id, style };
    
    document.getElementById('btnNext').disabled = false;
}

document.getElementById('btnNext').addEventListener('click', async function() {
    if (!selectedTemplate) return;
    
    this.disabled = true;
    this.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Salvataggio...';
    
    try {
        const response = await fetch('<?php echo BASE_URL; ?>/api/save-template.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ template_id: selectedTemplate.id, style: selectedTemplate.style })
        });
        
        const result = await response.json();
        
        if (result.success) {
            window.location.href = '<?php echo BASE_URL; ?>/onboarding/step-menu.php';
        } else {
            throw new Error(result.message || 'Errore durante il salvataggio');
        }
    } catch (error) {
        alert('Errore: ' + error.message);
        this.disabled = false;
        this.innerHTML = 'Continua <i class="bi bi-arrow-right ms-2"></i>';
    }
});
</script>
</body>
</html>