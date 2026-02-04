<?php
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
$locale = $localeModel->getById($localeId);

if (!$locale || $locale['user_id'] != $user['id']) {
    Helpers::redirect(BASE_URL . '/dashboard/qrcode/');
}

$menuList = $menuModel->getByLocaleId($localeId);

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tipo = $_POST['tipo'] ?? 'menu';
    $menuId = !empty($_POST['menu_id']) ? intval($_POST['menu_id']) : null;
    $tavolo = !empty($_POST['tavolo']) ? Helpers::sanitizeInput($_POST['tavolo']) : null;
    
    $codice = bin2hex(random_bytes(8));
    
    $data = [
        'locale_id' => $localeId,
        'codice' => $codice,
        'tipo' => $tipo,
        'attivo' => 1
    ];
    
    if ($menuId) {
        $data['menu_id'] = $menuId;
    }
    
    if ($tavolo) {
        $data['tavolo'] = $tavolo;
    }
    
    $qrId = $qrcodeModel->insert($data);
    
    if ($qrId) {
        $qrDir = __DIR__ . '/../../uploads/qrcode/';
        if (!is_dir($qrDir)) {
            mkdir($qrDir, 0755, true);
        }
        
        $url = BASE_URL . '/view?codice=' . $codice;
        $filename = 'qr_' . $codice . '.png';
        $filepath = $qrDir . $filename;
        
        $qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' . urlencode($url);
        $qrImage = file_get_contents($qrUrl);
        
        if ($qrImage !== false) {
            file_put_contents($filepath, $qrImage);
            $qrcodeModel->update($qrId, ['file_path' => 'uploads/qrcode/' . $filename]);
        }
        
        Helpers::redirect(BASE_URL . '/dashboard/qrcode/?locale=' . $localeId);
    } else {
        $error = 'Errore durante la generazione del QR Code';
    }
}

$pageTitle = 'Genera QR Code';
include __DIR__ . '/../includes/header.php';
?>

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header bg-white">
                <h5 class="mb-0">Genera QR Code - <?php echo $locale['nome']; ?></h5>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <div class="mb-3">
                        <label for="tipo" class="form-label">Tipo QR Code</label>
                        <select class="form-select" id="tipo" name="tipo" required onchange="toggleFields()">
                            <option value="menu">Menu</option>
                            <option value="tavolo">Tavolo</option>
                            <option value="asporto">Asporto</option>
                        </select>
                    </div>

                    <div class="mb-3" id="menu_field" style="display:none;">
                        <label for="menu_id" class="form-label">Seleziona Menu</label>
                        <select class="form-select" id="menu_id" name="menu_id">
                            <option value="">Nessuno</option>
                            <?php foreach ($menuList as $m): ?>
                                <option value="<?php echo $m['id']; ?>"><?php echo $m['nome']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3" id="tavolo_field" style="display:none;">
                        <label for="tavolo" class="form-label">Numero Tavolo</label>
                        <input type="text" class="form-control" id="tavolo" name="tavolo" 
                               placeholder="es: 1, A1">
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-qr-code me-2"></i>Genera QR Code
                        </button>
                        <a href="index.php?locale=<?php echo $localeId; ?>" class="btn btn-secondary">
                            <i class="bi bi-x-circle me-2"></i>Annulla
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card mb-3">
            <div class="card-body">
                <h5 class="card-title">Tipi di QR Code</h5>
                <ul class="text-muted mb-0">
                    <li><strong>Menu:</strong> Visualizza un menu specifico</li>
                    <li><strong>Tavolo:</strong> QR per un tavolo</li>
                    <li><strong>Asporto:</strong> QR per asporto</li>
                </ul>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Come funziona</h5>
                <p class="text-muted small">
                    I clienti scansionano il QR Code con lo smartphone 
                    e visualizzano il menu digitale.
                </p>
            </div>
        </div>
    </div>
</div>

<script>
function toggleFields() {
    const tipo = document.getElementById('tipo').value;
    const menuField = document.getElementById('menu_field');
    const tavoloField = document.getElementById('tavolo_field');
    
    menuField.style.display = 'none';
    tavoloField.style.display = 'none';
    
    if (tipo === 'menu') {
        menuField.style.display = 'block';
    } else if (tipo === 'tavolo') {
        tavoloField.style.display = 'block';
    }
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>