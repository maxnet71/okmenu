<?php
/**
 * Dashboard QR Code - Integrato con Ordini
 * Estende dashboard QR code esistente con funzionalità ordini
 * 
 * POSIZIONE: /dashboard/qrcode/index.php
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../classes/Database.php';
require_once __DIR__ . '/../../classes/Model.php';
require_once __DIR__ . '/../../classes/Helpers.php';
require_once __DIR__ . '/../../classes/models/User.php';
require_once __DIR__ . '/../../classes/models/LocaleRestaurant.php';
require_once __DIR__ . '/../../classes/models/Menu.php';
require_once __DIR__ . '/../../classes/models/QRCode.php';

Helpers::requireLogin();

$user = Helpers::getUser();
$localeModel = new LocaleRestaurant();
$menuModel = new Menu();
$qrcodeModel = new QRCode();

$localeId = intval($_GET['locale'] ?? 0);
$locali = $localeModel->getByUserId($user['id']);

if (empty($locali)) {
    Helpers::redirect(BASE_URL . '/dashboard/locali/create.php');
}

if (!$localeId) {
    $localeId = $locali[0]['id'];
}

$locale = $localeModel->getById($localeId);
if (!$locale || $locale['user_id'] != $user['id']) {
    Helpers::redirect(BASE_URL . '/dashboard/qrcode/');
}

// Gestione creazione QR
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'genera_qr') {
        $tipo = $_POST['tipo'] ?? 'menu';
        $menuId = intval($_POST['menu_id'] ?? 0);
        $tavolo = $_POST['tavolo'] ?? null;
        $abilitaOrdini = isset($_POST['abilita_ordini']) ? 1 : 0;
        $legacyUrl = trim($_POST['legacy_url'] ?? '');
        
        // Se menu_id è 0, passa NULL per evitare foreign key error
        $menuIdParam = $menuId > 0 ? $menuId : null;
        
        $qr = $qrcodeModel->generate($localeId, $menuIdParam, $tipo, $tavolo, $abilitaOrdini, $legacyUrl);
        
        if ($qr) {
            Helpers::setFlashMessage('QR Code generato con successo!', 'success');
        } else {
            Helpers::setFlashMessage('Errore generazione QR Code', 'danger');
        }
        
        Helpers::redirect($_SERVER['PHP_SELF'] . '?locale=' . $localeId);
    }
}

$menu = $menuModel->getByLocaleId($localeId);
$qrcodes = $qrcodeModel->getByLocaleId($localeId);

// Statistiche QR
$sql = "SELECT 
            COUNT(*) as totale_qr,
            SUM(CASE WHEN attivo = 1 THEN 1 ELSE 0 END) as qr_attivi,
            SUM(CASE WHEN abilita_ordini = 1 THEN 1 ELSE 0 END) as qr_con_ordini,
            SUM(scansioni) as totale_scansioni
        FROM qrcode WHERE locale_id = :locale_id";
$stmt = Database::getInstance()->getConnection()->prepare($sql);
$stmt->execute(['locale_id' => $localeId]);
$stats = $stmt->fetch();

// Ottieni configurazione ordini
$sql = "SELECT * FROM ordini_configurazioni WHERE locale_id = :locale_id";
$stmt = Database::getInstance()->getConnection()->prepare($sql);
$stmt->execute(['locale_id' => $localeId]);
$configOrdini = $stmt->fetch();

$pageTitle = 'Gestione QR Code';
include __DIR__ . '/../includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-12">
        <h2>QR Code - <?php echo htmlspecialchars($locale['nome']); ?></h2>
    </div>
</div>

<!-- Statistiche -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <h3 class="text-primary"><?php echo $stats['totale_qr'] ?? 0; ?></h3>
                <small>QR Totali</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <h3 class="text-success"><?php echo $stats['qr_attivi'] ?? 0; ?></h3>
                <small>QR Attivi</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <h3 class="text-info"><?php echo $stats['qr_con_ordini'] ?? 0; ?></h3>
                <small>QR con Ordini</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <h3 class="text-warning"><?php echo $stats['totale_scansioni'] ?? 0; ?></h3>
                <small>Scansioni</small>
            </div>
        </div>
    </div>
</div>

<!-- Genera Nuovo QR -->
<div class="card mb-4">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0"><i class="bi bi-qr-code"></i> Genera Nuovo QR Code</h5>
    </div>
    <div class="card-body">
        <form method="POST">
            <input type="hidden" name="action" value="genera_qr">
            
            <div class="row">
                <div class="col-md-3 mb-3">
                    <label class="form-label">Tipo QR</label>
                    <select name="tipo" class="form-select" required>
                        <option value="menu">Menu Completo</option>
                        <option value="tavolo">Tavolo Specifico</option>
                    </select>
                </div>
                
                <div class="col-md-3 mb-3">
                    <label class="form-label">Menu</label>
                    <select name="menu_id" class="form-select">
                        <option value="">Tutti i menu</option>
                        <?php foreach ($menu as $m): ?>
                        <option value="<?php echo $m['id']; ?>"><?php echo htmlspecialchars($m['nome']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-2 mb-3">
                    <label class="form-label">Tavolo (opzionale)</label>
                    <input type="text" name="tavolo" class="form-control" placeholder="es: T1">
                </div>
                
                <div class="col-md-3 mb-3">
                    <label class="form-label">Link Vecchio (opzionale)</label>
                    <input type="text" name="legacy_url" class="form-control" placeholder="index.asp?qrIdentity=...">
                    <small class="text-muted">Per compatibilità sistema precedente</small>
                </div>
                
                <div class="col-md-2 mb-3">
                    <label class="form-label d-block">Opzioni</label>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="abilita_ordini" id="abilitaOrdini" 
                               <?php echo ($configOrdini && $configOrdini['ordini_attivi']) ? '' : 'disabled'; ?>>
                        <label class="form-check-label" for="abilitaOrdini">
                            Abilita Ordini
                        </label>
                    </div>
                </div>
                
                <div class="col-md-2 mb-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-plus-circle"></i> Genera
                    </button>
                </div>
            </div>
            
            <?php if (!$configOrdini || !$configOrdini['ordini_attivi']): ?>
            <div class="alert alert-info mb-0">
                <i class="bi bi-info-circle"></i> Per abilitare ordini sui QR code, 
                <a href="<?php echo BASE_URL; ?>/dashboard/ordini/settings.php?locale=<?php echo $localeId; ?>">
                    configura il modulo ordini
                </a>
            </div>
            <?php endif; ?>
        </form>
    </div>
</div>

<!-- Lista QR Code -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">QR Code Generati</h5>
    </div>
    <div class="card-body">
        <?php if (empty($qrcodes)): ?>
        <p class="text-center text-muted">Nessun QR Code generato</p>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Anteprima</th>
                        <th>Tipo</th>
                        <th>Menu</th>
                        <th>Tavolo</th>
                        <th>Legacy URL</th>
                        <th>Ordini</th>
                        <th>Scansioni</th>
                        <th>Stato</th>
                        <th>Azioni</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($qrcodes as $qr): 
                        $menuQr = $qr['menu_id'] ? $menuModel->getById($qr['menu_id']) : null;
                    ?>
                    <tr>
                        <td>
                            <?php if ($qr['file_path']): ?>
                            <img src="<?php echo BASE_URL . '/' . $qr['file_path']; ?>" 
                                 alt="QR" width="80" height="80" class="rounded">
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge bg-<?php echo $qr['tipo'] === 'tavolo' ? 'info' : 'primary'; ?>">
                                <?php echo ucfirst($qr['tipo']); ?>
                            </span>
                        </td>
                        <td><?php echo $menuQr ? htmlspecialchars($menuQr['nome']) : 'Tutti'; ?></td>
                        <td><?php echo $qr['tavolo'] ? htmlspecialchars($qr['tavolo']) : '-'; ?></td>
                        <td>
                            <?php if (!empty($qr['legacy_url'])): ?>
                            <small class="text-muted" title="<?php echo htmlspecialchars($qr['legacy_url']); ?>">
                                <?php echo substr($qr['legacy_url'], 0, 30); ?><?php echo strlen($qr['legacy_url']) > 30 ? '...' : ''; ?>
                            </small>
                            <?php else: ?>
                            <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($qr['abilita_ordini']): ?>
                            <span class="badge bg-success"><i class="bi bi-check"></i> Abilitati</span>
                            <?php else: ?>
                            <span class="badge bg-secondary">Disabilitati</span>
                            <?php endif; ?>
                        </td>
                        <td><strong><?php echo $qr['scansioni'] ?? 0; ?></strong></td>
                        <td>
                            <span class="badge bg-<?php echo $qr['attivo'] ? 'success' : 'secondary'; ?>">
                                <?php echo $qr['attivo'] ? 'Attivo' : 'Disattivo'; ?>
                            </span>
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="<?php echo BASE_URL; ?>/view.php?codice=<?php echo $qr['codice']; ?>" 
                                   target="_blank" class="btn btn-outline-primary" title="Visualizza">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="<?php echo BASE_URL . '/' . $qr['file_path']; ?>" 
                                   download class="btn btn-outline-success" title="Scarica">
                                    <i class="bi bi-download"></i>
                                </a>
                                <button onclick="toggleQR(<?php echo $qr['id']; ?>)" 
                                        class="btn btn-outline-warning" title="Attiva/Disattiva">
                                    <i class="bi bi-power"></i>
                                </button>
                                <button onclick="deleteQR(<?php echo $qr['id']; ?>)" 
                                        class="btn btn-outline-danger" title="Elimina">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
function toggleQR(id) {
    if (confirm('Attivare/Disattivare questo QR Code?')) {
        fetch('<?php echo BASE_URL; ?>/api/qrcode/toggle.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'qr_id=' + id
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Errore: ' + data.message);
            }
        });
    }
}

function deleteQR(id) {
    if (confirm('Eliminare definitivamente questo QR Code?')) {
        fetch('<?php echo BASE_URL; ?>/api/qrcode/delete.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'qr_id=' + id
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Errore: ' + data.message);
            }
        });
    }
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>