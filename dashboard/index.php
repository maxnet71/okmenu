<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/Helpers.php';
require_once __DIR__ . '/../classes/PlanLimits.php';
require_once __DIR__ . '/../Model.php';
require_once __DIR__ . '/../Menu.php';
require_once __DIR__ . '/../Categoria.php';
require_once __DIR__ . '/../Piatto.php';

Helpers::requireLogin();

$user = Helpers::getUser();
$userId = $user['id'];
$piano = $user['piano'] ?? 'free';
$planLimits = new PlanLimits($userId, $piano);

$db = Database::getInstance()->getConnection();

// Carica statistiche
$stmt = $db->prepare("SELECT COUNT(*) as count FROM locali WHERE user_id = :user_id");
$stmt->execute(['user_id' => $userId]);
$totaleLocali = $stmt->fetch()['count'];

$stmt = $db->prepare("
    SELECT COUNT(DISTINCT m.id) as count 
    FROM menu m 
    JOIN locali l ON m.locale_id = l.id 
    WHERE l.user_id = :user_id
");
$stmt->execute(['user_id' => $userId]);
$totaleMenu = $stmt->fetch()['count'];

$stmt = $db->prepare("
    SELECT ai_uploads_used, ai_uploads_limit 
    FROM piano_limiti 
    WHERE user_id = :user_id
");
$stmt->execute(['user_id' => $userId]);
$aiUsage = $stmt->fetch();

$pageTitle = 'Dashboard';
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - OkMenu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container-fluid">
        <a class="navbar-brand" href="<?php echo BASE_URL; ?>/dashboard/">
            <i class="bi bi-qr-code-scan"></i> OkMenu
        </a>
        <div class="navbar-nav ms-auto">
            <span class="navbar-text me-3">
                <?php echo htmlspecialchars($user['nome']); ?>
                <?php if ($piano === 'free'): ?>
                    <span class="badge bg-warning">FREE</span>
                <?php else: ?>
                    <span class="badge bg-success">PREMIUM</span>
                <?php endif; ?>
            </span>
            <a href="<?php echo BASE_URL; ?>/logout.php" class="btn btn-outline-light btn-sm">
                <i class="bi bi-box-arrow-right"></i> Esci
            </a>
        </div>
    </div>
</nav>

<div class="container py-5">
    <?php if ($piano === 'free'): ?>
    <!-- Banner Upgrade -->
    <div class="alert alert-warning alert-dismissible fade show">
        <h5><i class="bi bi-star me-2"></i>Passa a Premium</h5>
        <p class="mb-2">Sblocca locali illimitati, menu illimitati, AI illimitato e molto altro!</p>
        <a href="<?php echo BASE_URL; ?>/upgrade.php" class="btn btn-sm btn-dark">
            Upgrade a €9/mese
        </a>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <div class="row mb-4">
        <div class="col">
            <h1>Dashboard</h1>
            <p class="text-muted">Benvenuto nel tuo menu digitale</p>
        </div>
    </div>

    <!-- Statistiche Utilizzo -->
    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="text-muted mb-2">Locali</h6>
                    <div class="d-flex justify-content-between align-items-center">
                        <h3 class="mb-0"><?php echo $totaleLocali; ?> / <?php echo $planLimits::LIMITS[$piano]['locali']; ?></h3>
                        <?php if ($totaleLocali >= $planLimits::LIMITS[$piano]['locali']): ?>
                            <span class="badge bg-danger">Limite raggiunto</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="text-muted mb-2">Menu</h6>
                    <div class="d-flex justify-content-between align-items-center">
                        <h3 class="mb-0"><?php echo $totaleMenu; ?></h3>
                        <span class="text-muted">Totali</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="text-muted mb-2">AI Upload</h6>
                    <div class="d-flex justify-content-between align-items-center">
                        <h3 class="mb-0">
                            <?php echo $aiUsage['ai_uploads_used'] ?? 0; ?> / 
                            <?php echo $aiUsage['ai_uploads_limit'] ?? 1; ?>
                        </h3>
                        <?php if (($aiUsage['ai_uploads_used'] ?? 0) >= ($aiUsage['ai_uploads_limit'] ?? 1)): ?>
                            <span class="badge bg-danger">Limite raggiunto</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Azioni Rapide -->
    <div class="row g-4 mb-4">
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-body">
                    <h5><i class="bi bi-shop me-2"></i>I Tuoi Locali</h5>
                    <p class="text-muted">Gestisci i tuoi ristoranti</p>
                    <?php if ($planLimits->canCreateLocale()): ?>
                        <a href="<?php echo BASE_URL; ?>/dashboard/locali/create.php" class="btn btn-primary">
                            <i class="bi bi-plus-lg me-2"></i>Nuovo Locale
                        </a>
                    <?php else: ?>
                        <button class="btn btn-secondary" disabled>
                            <i class="bi bi-lock me-2"></i>Limite FREE Raggiunto
                        </button>
                        <a href="<?php echo BASE_URL; ?>/upgrade.php" class="btn btn-warning btn-sm">
                            Upgrade
                        </a>
                    <?php endif; ?>
                    <a href="<?php echo BASE_URL; ?>/dashboard/locali/" class="btn btn-outline-primary">
                        <i class="bi bi-list me-2"></i>Vedi Tutti
                    </a>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-body">
                    <h5><i class="bi bi-book me-2"></i>I Tuoi Menu</h5>
                    <p class="text-muted">Gestisci i tuoi menu digitali</p>
                    <a href="<?php echo BASE_URL; ?>/dashboard/menu/" class="btn btn-outline-primary">
                        <i class="bi bi-list me-2"></i>Vedi Tutti
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- QR Code -->
    <div class="card">
        <div class="card-body">
            <h5><i class="bi bi-qr-code me-2"></i>QR Code Menu</h5>
            <p class="text-muted">Scarica e stampa il QR code del tuo menu</p>
            <a href="<?php echo BASE_URL; ?>/dashboard/qrcode/" class="btn btn-outline-primary">
                <i class="bi bi-download me-2"></i>Genera QR Code
            </a>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>