<?php
/**
 * Dashboard Ordini - Lista
 * Lista ordini con filtri e stato real-time
 * 
 * POSIZIONE: /dashboard/ordini/index.php
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../classes/Database.php';
require_once __DIR__ . '/../../classes/Model.php';
require_once __DIR__ . '/../../classes/Helpers.php';
require_once __DIR__ . '/../../classes/models/User.php';
require_once __DIR__ . '/../../classes/models/LocaleRestaurant.php';
require_once __DIR__ . '/../../classes/models/Ordine.php';

Helpers::requireLogin();

$user = Helpers::getUser();
$ordineModel = new Ordine();
$localeModel = new LocaleRestaurant();

// Ottieni locale selezionato
$localeId = intval($_GET['locale'] ?? 0);
$locali = $localeModel->getByUserId($user['id']);

if (empty($locali)) {
    Helpers::redirect(BASE_URL . '/dashboard/locali/create.php');
}

if (!$localeId) {
    $localeId = $locali[0]['id'];
}

// Verifica proprietà locale
$locale = $localeModel->getById($localeId);
if (!$locale || $locale['user_id'] != $user['id']) {
    Helpers::redirect(BASE_URL . '/dashboard/ordini/');
}

// Filtri
$statoFiltro = $_GET['stato'] ?? 'attesa_conferma';
$tipoFiltro = $_GET['tipo'] ?? '';
$dataFiltro = $_GET['data'] ?? date('Y-m-d');

// Ottieni ordini
$filters = ['stato' => $statoFiltro];
if ($tipoFiltro) $filters['tipo'] = $tipoFiltro;
if ($dataFiltro) $filters['data_da'] = $dataFiltro;

$ordini = $ordineModel->getByLocaleId($localeId, $filters);

// Conta ordini per stato
$sql = "SELECT stato, COUNT(*) as count FROM ordini 
        WHERE locale_id = :locale_id AND DATE(created_at) = :data
        GROUP BY stato";
$stmt = Database::getInstance()->getConnection()->prepare($sql);
$stmt->execute(['locale_id' => $localeId, 'data' => $dataFiltro]);
$conteggioStati = [];
while ($row = $stmt->fetch()) {
    $conteggioStati[$row['stato']] = $row['count'];
}

$pageTitle = 'Gestione Ordini';
include __DIR__ . '/../includes/header.php';
?>

<style>
.ordine-card {
    border-left: 4px solid #ddd;
    transition: all 0.3s;
}
.ordine-card:hover {
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}
.ordine-card.urgente {
    border-left-color: #dc3545;
    background-color: #fff5f5;
}
.ordine-card.attesa {
    border-left-color: #ffc107;
}
.ordine-card.confermato {
    border-left-color: #28a745;
}
.timer-scadenza {
    font-size: 0.9em;
    font-weight: bold;
}
.timer-scadenza.critico {
    color: #dc3545;
    animation: pulse 1s infinite;
}
@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.6; }
}
</style>

<div class="row mb-4">
    <div class="col-md-12">
        <div class="d-flex justify-content-between align-items-center">
            <h2>Ordini <?php echo $locale['nome']; ?></h2>
            <div>
                <button class="btn btn-primary" onclick="location.reload()">
                    <i class="bi bi-arrow-clockwise"></i> Aggiorna
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Contatori Stati -->
<div class="row mb-4">
    <div class="col-md-2">
        <div class="card text-center <?php echo $statoFiltro === 'attesa_conferma' ? 'border-warning' : ''; ?>">
            <div class="card-body">
                <h3 class="text-warning"><?php echo $conteggioStati['attesa_conferma'] ?? 0; ?></h3>
                <small>In Attesa</small>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card text-center <?php echo $statoFiltro === 'confermato' ? 'border-success' : ''; ?>">
            <div class="card-body">
                <h3 class="text-success"><?php echo $conteggioStati['confermato'] ?? 0; ?></h3>
                <small>Confermati</small>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card text-center <?php echo $statoFiltro === 'in_preparazione' ? 'border-info' : ''; ?>">
            <div class="card-body">
                <h3 class="text-info"><?php echo $conteggioStati['in_preparazione'] ?? 0; ?></h3>
                <small>In Preparazione</small>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card text-center <?php echo $statoFiltro === 'pronto_ritiro' ? 'border-primary' : ''; ?>">
            <div class="card-body">
                <h3 class="text-primary"><?php echo $conteggioStati['pronto_ritiro'] ?? 0; ?></h3>
                <small>Pronti</small>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card text-center <?php echo $statoFiltro === 'completato' ? 'border-secondary' : ''; ?>">
            <div class="card-body">
                <h3 class="text-secondary"><?php echo $conteggioStati['completato'] ?? 0; ?></h3>
                <small>Completati</small>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card text-center <?php echo $statoFiltro === 'rifiutato' ? 'border-danger' : ''; ?>">
            <div class="card-body">
                <h3 class="text-danger"><?php echo ($conteggioStati['rifiutato'] ?? 0) + ($conteggioStati['annullato'] ?? 0); ?></h3>
                <small>Rifiutati</small>
            </div>
        </div>
    </div>
</div>

<!-- Filtri -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <input type="hidden" name="locale" value="<?php echo $localeId; ?>">
            
            <div class="col-md-3">
                <label class="form-label">Stato</label>
                <select name="stato" class="form-select" onchange="this.form.submit()">
                    <option value="attesa_conferma" <?php echo $statoFiltro === 'attesa_conferma' ? 'selected' : ''; ?>>In Attesa</option>
                    <option value="confermato" <?php echo $statoFiltro === 'confermato' ? 'selected' : ''; ?>>Confermati</option>
                    <option value="in_preparazione" <?php echo $statoFiltro === 'in_preparazione' ? 'selected' : ''; ?>>In Preparazione</option>
                    <option value="pronto_ritiro" <?php echo $statoFiltro === 'pronto_ritiro' ? 'selected' : ''; ?>>Pronti</option>
                    <option value="in_consegna" <?php echo $statoFiltro === 'in_consegna' ? 'selected' : ''; ?>>In Consegna</option>
                    <option value="completato" <?php echo $statoFiltro === 'completato' ? 'selected' : ''; ?>>Completati</option>
                    <option value="" <?php echo $statoFiltro === '' ? 'selected' : ''; ?>>Tutti</option>
                </select>
            </div>
            
            <div class="col-md-3">
                <label class="form-label">Tipo</label>
                <select name="tipo" class="form-select" onchange="this.form.submit()">
                    <option value="">Tutti</option>
                    <option value="asporto" <?php echo $tipoFiltro === 'asporto' ? 'selected' : ''; ?>>Asporto</option>
                    <option value="delivery" <?php echo $tipoFiltro === 'delivery' ? 'selected' : ''; ?>>Consegna</option>
                </select>
            </div>
            
            <div class="col-md-3">
                <label class="form-label">Data</label>
                <input type="date" name="data" class="form-control" value="<?php echo $dataFiltro; ?>" onchange="this.form.submit()">
            </div>
            
            <div class="col-md-3">
                <label class="form-label">&nbsp;</label>
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-funnel"></i> Filtra
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Lista Ordini -->
<?php if (empty($ordini)): ?>
    <div class="card">
        <div class="card-body text-center py-5">
            <i class="bi bi-inbox display-1 text-muted"></i>
            <h4 class="mt-3">Nessun ordine trovato</h4>
            <p class="text-muted">Non ci sono ordini con i filtri selezionati.</p>
        </div>
    </div>
<?php else: ?>
    <div class="row">
        <?php foreach ($ordini as $ordine): 
            $scadenzaMinuti = null;
            $classeUrgenza = '';
            
            if ($ordine['scadenza_conferma'] && $ordine['stato'] === 'attesa_conferma') {
                $scadenzaTimestamp = strtotime($ordine['scadenza_conferma']);
                $oraTimestamp = time();
                $scadenzaMinuti = round(($scadenzaTimestamp - $oraTimestamp) / 60);
                
                if ($scadenzaMinuti < 5) {
                    $classeUrgenza = 'urgente';
                } elseif ($scadenzaMinuti < 10) {
                    $classeUrgenza = 'attesa';
                }
            }
            
            if ($ordine['stato'] === 'confermato') {
                $classeUrgenza = 'confermato';
            }
        ?>
        <div class="col-md-6 mb-3">
            <div class="card ordine-card <?php echo $classeUrgenza; ?> h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <h5 class="card-title mb-1">
                                <a href="view.php?id=<?php echo $ordine['id']; ?>" class="text-decoration-none">
                                    Ordine #<?php echo $ordine['numero_ordine']; ?>
                                </a>
                            </h5>
                            <small class="text-muted">
                                <?php echo date('d/m/Y H:i', strtotime($ordine['created_at'])); ?>
                            </small>
                        </div>
                        <span class="badge bg-<?php 
                            echo match($ordine['stato']) {
                                'attesa_conferma' => 'warning',
                                'confermato' => 'success',
                                'in_preparazione' => 'info',
                                'pronto_ritiro' => 'primary',
                                'in_consegna' => 'info',
                                'completato' => 'secondary',
                                'rifiutato', 'annullato' => 'danger',
                                default => 'light'
                            };
                        ?>">
                            <?php echo ucfirst(str_replace('_', ' ', $ordine['stato'])); ?>
                        </span>
                    </div>
                    
                    <?php if ($scadenzaMinuti !== null && $ordine['stato'] === 'attesa_conferma'): ?>
                    <div class="alert alert-warning py-2 mb-3">
                        <i class="bi bi-clock"></i>
                        <span class="timer-scadenza <?php echo $scadenzaMinuti < 5 ? 'critico' : ''; ?>">
                            Scade tra <?php echo $scadenzaMinuti; ?> minuti
                        </span>
                    </div>
                    <?php endif; ?>
                    
                    <div class="mb-3">
                        <strong><i class="bi bi-person"></i> <?php echo htmlspecialchars($ordine['nome_cliente']); ?></strong><br>
                        <small class="text-muted">
                            <i class="bi bi-telephone"></i> <?php echo htmlspecialchars($ordine['telefono_cliente']); ?>
                        </small>
                    </div>
                    
                    <div class="mb-3">
                        <span class="badge bg-secondary">
                            <?php echo $ordine['tipo'] === 'asporto' ? 'Asporto' : 'Consegna'; ?>
                        </span>
                        <?php if ($ordine['tipo'] === 'delivery' && $ordine['indirizzo_consegna']): ?>
                            <br><small class="text-muted"><i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($ordine['indirizzo_consegna']); ?></small>
                        <?php endif; ?>
                    </div>
                    
                    <div class="d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">€<?php echo number_format($ordine['totale'], 2, ',', '.'); ?></h4>
                        <a href="view.php?id=<?php echo $ordine['id']; ?>" class="btn btn-sm btn-primary">
                            Dettagli <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<script>
// Auto-refresh ogni 30 secondi per ordini in attesa
<?php if ($statoFiltro === 'attesa_conferma'): ?>
setTimeout(function() {
    location.reload();
}, 30000);
<?php endif; ?>

// Audio notifica nuovi ordini (opzionale)
let ultimoOrdineId = <?php echo !empty($ordini) ? $ordini[0]['id'] : 0; ?>;
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>