<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/Model.php';
require_once __DIR__ . '/../classes/Helpers.php';
require_once __DIR__ . '/../classes/models/User.php';
require_once __DIR__ . '/../classes/models/LocaleRestaurant.php';
require_once __DIR__ . '/../classes/models/Menu.php';
require_once __DIR__ . '/../classes/PlanLimits.php';

Helpers::requireLogin();

$user = Helpers::getUser();
$planLimits = new PlanLimits($user['id'], $user['piano']);
$usage = $planLimits->getCurrentUsage();
$limits = $planLimits->getLimits();

$localeModel = new LocaleRestaurant();
$locali = $localeModel->getByUserId($user['id']);
$locale = $locali[0] ?? null;

$pageTitle = 'Dashboard';
require_once __DIR__ . '/includes/header.php';
?>

<?php if ($user['piano'] === 'free'): ?>
<!-- Upgrade Banner -->
<div class="alert alert-gradient border-0 mb-4" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h5 class="mb-1"><i class="bi bi-star-fill me-2"></i>Piano FREE</h5>
            <p class="mb-0 opacity-75">
                Sblocca tutte le funzionalità con Premium: locali illimitati, AI illimitato, ordini online e molto altro
            </p>
        </div>
        <a href="<?php echo BASE_URL; ?>/upgrade.php" class="btn btn-light btn-sm">
            <i class="bi bi-rocket-takeoff me-2"></i>Passa a Premium
        </a>
    </div>
</div>

<!-- Usage Stats -->
<div class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <small class="text-muted">Locali</small>
                        <h3 class="mb-0"><?php echo $usage['locali']; ?> / <?php echo $limits['locali']; ?></h3>
                    </div>
                    <div class="text-primary" style="font-size: 2.5rem;">
                        <i class="bi bi-shop"></i>
                    </div>
                </div>
                <?php if ($usage['locali'] >= $limits['locali']): ?>
                <div class="mt-2">
                    <span class="badge bg-warning">Limite raggiunto</span>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <small class="text-muted">Menu</small>
                        <h3 class="mb-0"><?php echo $usage['menu']; ?> / <?php echo $limits['menu_per_locale']; ?></h3>
                    </div>
                    <div class="text-success" style="font-size: 2.5rem;">
                        <i class="bi bi-journal-text"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <small class="text-muted">Upload AI</small>
                        <h3 class="mb-0"><?php echo $usage['ai_uploads']; ?> / <?php echo $limits['ai_uploads']; ?></h3>
                    </div>
                    <div class="text-info" style="font-size: 2.5rem;">
                        <i class="bi bi-magic"></i>
                    </div>
                </div>
                <?php if ($usage['ai_uploads'] >= $limits['ai_uploads']): ?>
                <div class="mt-2">
                    <span class="badge bg-warning">Limite raggiunto</span>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Quick Actions -->
<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="card h-100 hover-card">
            <div class="card-body text-center">
                <i class="bi bi-qr-code-scan text-primary" style="font-size: 3rem;"></i>
                <h5 class="mt-3">QR Code</h5>
                <p class="text-muted">Scarica il QR code del tuo menu</p>
                <?php if ($locale): ?>
                <a href="<?php echo BASE_URL; ?>/dashboard/qrcode/?locale=<?php echo $locale['id']; ?>" class="btn btn-primary btn-sm">
                    Vai <i class="bi bi-arrow-right ms-1"></i>
                </a>
                <?php else: ?>
                <span class="text-muted"><small>Nessun locale</small></span>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card h-100 hover-card">
            <div class="card-body text-center">
                <i class="bi bi-palette text-success" style="font-size: 3rem;"></i>
                <h5 class="mt-3">Personalizza</h5>
                <p class="text-muted">Modifica colori e stile</p>
                <?php if ($locale): ?>
                <a href="<?php echo BASE_URL; ?>/dashboard/aspetto/?locale=<?php echo $locale['id']; ?>" class="btn btn-success btn-sm">
                    Vai <i class="bi bi-arrow-right ms-1"></i>
                </a>
                <?php else: ?>
                <span class="text-muted"><small>Nessun locale</small></span>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card h-100 hover-card">
            <div class="card-body text-center">
                <i class="bi bi-eye text-info" style="font-size: 3rem;"></i>
                <h5 class="mt-3">Anteprima</h5>
                <p class="text-muted">Visualizza menu pubblico</p>
                <?php if ($locale): ?>
                <a href="<?php echo BASE_URL; ?>/view.php?slug=<?php echo $locale['slug']; ?>" target="_blank" class="btn btn-info btn-sm">
                    Apri <i class="bi bi-box-arrow-up-right ms-1"></i>
                </a>
                <?php else: ?>
                <span class="text-muted"><small>Nessun locale</small></span>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card h-100 hover-card <?php echo $user['piano'] === 'free' ? 'opacity-50' : ''; ?>">
            <div class="card-body text-center position-relative">
                <?php if ($user['piano'] === 'free'): ?>
                <span class="badge bg-warning position-absolute top-0 end-0 m-2">Premium</span>
                <?php endif; ?>
                <i class="bi bi-graph-up text-warning" style="font-size: 3rem;"></i>
                <h5 class="mt-3">Statistiche</h5>
                <p class="text-muted">Analizza le performance</p>
                <?php if ($user['piano'] === 'premium'): ?>
                <a href="<?php echo BASE_URL; ?>/dashboard/statistiche/" class="btn btn-warning btn-sm">
                    Vai <i class="bi bi-arrow-right ms-1"></i>
                </a>
                <?php else: ?>
                <a href="<?php echo BASE_URL; ?>/upgrade.php" class="btn btn-outline-warning btn-sm">
                    Sblocca
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Recent Activity -->
<?php if ($locale): ?>
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Il Tuo Menu</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <h6 class="fw-bold"><?php echo htmlspecialchars($locale['nome']); ?></h6>
                <p class="text-muted mb-2"><?php echo ucfirst($locale['tipo']); ?></p>
                
                <?php
                $menuModel = new Menu();
                $menus = $menuModel->getByLocaleId($locale['id']);
                ?>
                
                <?php if (!empty($menus)): ?>
                <p class="mb-2">
                    <i class="bi bi-journal-text me-2"></i>
                    <?php echo count($menus); ?> menu caricato
                </p>
                <?php else: ?>
                <div class="alert alert-warning">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    Nessun menu caricato ancora
                </div>
                <?php endif; ?>
            </div>
            
            <div class="col-md-6 text-md-end">
                <a href="<?php echo BASE_URL; ?>/dashboard/menu/?locale=<?php echo $locale['id']; ?>" class="btn btn-primary">
                    <i class="bi bi-pencil me-2"></i>Gestisci Menu
                </a>
                
                <?php if ($user['piano'] === 'free' && $planLimits->canUseAI()): ?>
                <a href="<?php echo BASE_URL; ?>/upload-menu-multi.php?locale=<?php echo $locale['id']; ?>" class="btn btn-success">
                    <i class="bi bi-magic me-2"></i>Carica con AI
                </a>
                <?php elseif ($user['piano'] === 'free'): ?>
                <button class="btn btn-outline-secondary" disabled>
                    <i class="bi bi-lock me-2"></i>AI Utilizzato
                </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php else: ?>
<div class="alert alert-info text-center">
    <h5><i class="bi bi-info-circle me-2"></i>Completa il Setup</h5>
    <p class="mb-3">Crea il tuo primo locale per iniziare</p>
    <a href="<?php echo BASE_URL; ?>/dashboard/locali/create.php" class="btn btn-primary">
        <i class="bi bi-plus-circle me-2"></i>Crea Locale
    </a>
</div>
<?php endif; ?>

<style>
.hover-card {
    transition: all 0.3s;
}
.hover-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.1);
}
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>