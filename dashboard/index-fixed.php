<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../config/config.php';

// Carica manualmente le classi nell'ordine corretto
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/Model.php';
require_once __DIR__ . '/../classes/Helpers.php';
require_once __DIR__ . '/../classes/models/User.php';
require_once __DIR__ . '/../classes/models/LocaleRestaurant.php';
require_once __DIR__ . '/../classes/models/Menu.php';
require_once __DIR__ . '/../classes/models/Categoria.php';
require_once __DIR__ . '/../classes/models/Ordine.php';
require_once __DIR__ . '/../classes/models/Statistica.php';

// Verifica che la classe Locale sia caricata correttamente
if (!class_exists('Locale')) {
    die('ERRORE: Classe Locale non trovata. Verifica il file /classes/models/LocaleRestaurant.php');
}

if (!method_exists('Locale', 'getByUserId')) {
    die('ERRORE: Metodo getByUserId non trovato nella classe Locale. Ri-carica il file LocaleRestaurant.php dallo ZIP');
}

Helpers::requireLogin();

$user = Helpers::getUser();
$localeModel = new LocaleRestaurant();
$menuModel = new Menu();
$ordineModel = new Ordine();
$statisticheModel = new Statistica();

$locali = $localeModel->getByUserId($user['id']);
$totaleLocali = count($locali);

$totaleMenu = 0;
$totaleOrdini = 0;
$fatturato = 0;

$dataInizio = date('Y-m-01');
$dataFine = date('Y-m-d');

foreach ($locali as $locale) {
    $menu = $menuModel->getByLocaleId($locale['id']);
    $totaleMenu += count($menu);
    
    $stats = $statisticheModel->getTotali($locale['id'], $dataInizio, $dataFine);
    if ($stats) {
        $totaleOrdini += $stats['totale_ordini'];
        $fatturato += $stats['totale_fatturato'];
    }
}

$pageTitle = 'Dashboard';
include __DIR__ . '/includes/header.php';
?>

<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="dashboard-card card h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-1">Locali</p>
                        <h2 class="mb-0"><?php echo $totaleLocali; ?></h2>
                    </div>
                    <div class="stat-icon bg-primary text-white">
                        <i class="bi bi-shop"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="dashboard-card card h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-1">Menu Attivi</p>
                        <h2 class="mb-0"><?php echo $totaleMenu; ?></h2>
                    </div>
                    <div class="stat-icon bg-success text-white">
                        <i class="bi bi-journal-text"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="dashboard-card card h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-1">Ordini (mese)</p>
                        <h2 class="mb-0"><?php echo $totaleOrdini; ?></h2>
                    </div>
                    <div class="stat-icon bg-warning text-white">
                        <i class="bi bi-cart"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="dashboard-card card h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-1">Fatturato (mese)</p>
                        <h2 class="mb-0"><?php echo Helpers::formatPrice($fatturato); ?></h2>
                    </div>
                    <div class="stat-icon bg-info text-white">
                        <i class="bi bi-graph-up"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header bg-white">
                <h5 class="mb-0">I Tuoi Locali</h5>
            </div>
            <div class="card-body">
                <?php if (empty($locali)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-shop display-1 text-muted"></i>
                        <h4 class="mt-3">Nessun locale configurato</h4>
                        <p class="text-muted">Inizia creando il tuo primo locale</p>
                        <a href="locali/create.php" class="btn btn-primary">
                            <i class="bi bi-plus-circle me-2"></i>Crea Locale
                        </a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Nome</th>
                                    <th>Tipo</th>
                                    <th>Città</th>
                                    <th>Menu</th>
                                    <th>Stato</th>
                                    <th>Azioni</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($locali as $locale): ?>
                                    <?php $menuCount = count($menuModel->getByLocaleId($locale['id'])); ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo $locale['nome']; ?></strong>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary">
                                                <?php echo ucfirst($locale['tipo']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo $locale['citta'] ?? '-'; ?></td>
                                        <td><?php echo $menuCount; ?></td>
                                        <td>
                                            <?php if ($locale['attivo']): ?>
                                                <span class="badge bg-success">Attivo</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">Non Attivo</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="locali/edit.php?id=<?php echo $locale['id']; ?>" 
                                               class="btn btn-sm btn-primary" title="Modifica">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <a href="menu/?locale=<?php echo $locale['id']; ?>" 
                                               class="btn btn-sm btn-success" title="Menu">
                                                <i class="bi bi-journal-text"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="text-end mt-3">
                        <a href="locali/create.php" class="btn btn-primary">
                            <i class="bi bi-plus-circle me-2"></i>Nuovo Locale
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card mb-4">
            <div class="card-header bg-white">
                <h5 class="mb-0">Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="menu/" class="btn btn-outline-primary">
                        <i class="bi bi-journal-text me-2"></i>Gestisci Menu
                    </a>
                    <a href="piatti/" class="btn btn-outline-success">
                        <i class="bi bi-egg-fried me-2"></i>Gestisci Piatti
                    </a>
                    <a href="ordini/" class="btn btn-outline-warning">
                        <i class="bi bi-cart me-2"></i>Visualizza Ordini
                    </a>
                    <a href="qrcode/" class="btn btn-outline-info">
                        <i class="bi bi-qr-code me-2"></i>Genera QR Code
                    </a>
                    <a href="statistiche/" class="btn btn-outline-secondary">
                        <i class="bi bi-graph-up me-2"></i>Statistiche
                    </a>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header bg-white">
                <h5 class="mb-0">Supporto</h5>
            </div>
            <div class="card-body">
                <p class="text-muted">Hai bisogno di aiuto?</p>
                <div class="d-grid gap-2">
                    <a href="#" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-book me-2"></i>Documentazione
                    </a>
                    <a href="#" class="btn btn-sm btn-outline-success">
                        <i class="bi bi-chat-dots me-2"></i>Contatta Supporto
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
