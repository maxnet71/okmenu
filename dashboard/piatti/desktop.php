<?php
/**
 * Dashboard Piatti Desktop
 * Versione desktop/tablet per gestione piatti
 * 
 * POSIZIONE: /dashboard/piatti/desktop.php
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../classes/Database.php';
require_once __DIR__ . '/../../classes/Model.php';
require_once __DIR__ . '/../../classes/Helpers.php';
require_once __DIR__ . '/../../classes/DeviceDetector.php';
require_once __DIR__ . '/../../classes/models/User.php';
require_once __DIR__ . '/../../classes/models/LocaleRestaurant.php';
require_once __DIR__ . '/../../classes/models/Menu.php';
require_once __DIR__ . '/../../classes/models/Categoria.php';
require_once __DIR__ . '/../../classes/models/Piatto.php';

Helpers::requireLogin();

$user = Helpers::getUser();
$localeModel = new LocaleRestaurant();
$menuModel = new Menu();
$categoriaModel = new Categoria();
$piattoModel = new Piatto();

$categoriaId = intval($_GET['categoria'] ?? 0);
$localeId = intval($_GET['locale'] ?? 0);

$categoria = null;
$menu = null;
$locale = null;

if ($categoriaId) {
    $categoria = $categoriaModel->getById($categoriaId);
    
    if (!$categoria) {
        Helpers::redirect(BASE_URL . '/dashboard/menu/');
    }
    
    $menu = $menuModel->getById($categoria['menu_id']);
    $locale = $localeModel->getById($menu['locale_id']);
    
    if (!$locale || $locale['user_id'] != $user['id']) {
        Helpers::redirect(BASE_URL . '/dashboard/menu/');
    }
    
    $piatti = $piattoModel->getByCategoriaId($categoriaId);
} else {
    $locali = $localeModel->getByUserId($user['id']);
    $piatti = [];
}

$pageTitle = isset($categoria) ? 'Piatti - ' . $categoria['nome'] : 'Tutti i Piatti';
include __DIR__ . '/../includes/header.php';
?>

<!-- Link Switch Mobile/Desktop -->
<div class="d-flex justify-content-between align-items-center mb-3">
    <?php if (isset($categoria)): ?>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="../menu/?locale=<?php echo $locale['id']; ?>">Menu</a></li>
            <li class="breadcrumb-item"><a href="../categorie/?menu=<?php echo $menu['id']; ?>"><?php echo htmlspecialchars($menu['nome']); ?></a></li>
            <li class="breadcrumb-item active"><?php echo htmlspecialchars($categoria['nome']); ?></li>
        </ol>
    </nav>
    <?php else: ?>
    <h2>Gestione Piatti</h2>
    <?php endif; ?>
    
    <div class="btn-group">
        <?php if (DeviceDetector::isMobile()): ?>
        <a href="?<?php echo http_build_query(array_merge($_GET, ['view' => 'mobile'])); ?>" 
           class="btn btn-outline-primary btn-sm">
            <i class="bi bi-phone"></i> Vista Mobile
        </a>
        <?php endif; ?>
        
        <?php if ($categoriaId): ?>
        <a href="../piatti/create.php?categoria=<?php echo $categoriaId; ?>" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-circle"></i> Nuovo Piatto
        </a>
        <?php endif; ?>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <?php if (empty($piatti)): ?>
            <div class="text-center py-5">
                <i class="bi bi-egg-fried display-1 text-muted"></i>
                <h4 class="mt-3">Nessun piatto configurato</h4>
                <p class="text-muted">Inizia ad aggiungere i piatti al menu</p>
                <?php if ($categoriaId): ?>
                    <a href="../piatti/create.php?categoria=<?php echo $categoriaId; ?>" class="btn btn-primary btn-lg">
                        <i class="bi bi-plus-circle me-2"></i>Crea Primo Piatto
                    </a>
                <?php else: ?>
                    <p class="text-muted">Seleziona prima una categoria dal menu</p>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th width="80">Foto</th>
                            <th>Nome</th>
                            <th>Prezzo</th>
                            <th width="120">Disponibile</th>
                            <th width="150">Azioni</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($piatti as $piatto): ?>
                        <tr>
                            <td>
                                <?php if ($piatto['immagine']): ?>
                                <img src="<?php echo BASE_URL . '/' . $piatto['immagine']; ?>" 
                                     alt="<?php echo htmlspecialchars($piatto['nome']); ?>" 
                                     class="img-thumbnail" 
                                     style="width: 60px; height: 60px; object-fit: cover;">
                                <?php else: ?>
                                <div class="bg-light d-flex align-items-center justify-content-center" 
                                     style="width: 60px; height: 60px; border-radius: 5px;">
                                    <i class="bi bi-image text-muted"></i>
                                </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($piatto['nome']); ?></strong>
                                <?php if ($piatto['descrizione']): ?>
                                <br><small class="text-muted"><?php echo Helpers::truncate($piatto['descrizione'], 60); ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong>€<?php echo number_format($piatto['prezzo'], 2, ',', '.'); ?></strong>
                                <?php if (isset($piatto['prezzo_scontato']) && $piatto['prezzo_scontato'] > 0): ?>
                                <br><small class="text-success">€<?php echo number_format($piatto['prezzo_scontato'], 2, ',', '.'); ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" 
                                           type="checkbox" 
                                           <?php echo $piatto['disponibile'] ? 'checked' : ''; ?>
                                           onchange="toggleDisponibilita(<?php echo $piatto['id']; ?>)">
                                </div>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="../piatti/edit.php?id=<?php echo $piatto['id']; ?>" 
                                       class="btn btn-outline-primary" 
                                       title="Modifica">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <button onclick="deletePiatto(<?php echo $piatto['id']; ?>)" 
                                            class="btn btn-outline-danger" 
                                            title="Elimina">
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
function toggleDisponibilita(id) {
    fetch('<?php echo BASE_URL; ?>/api/piatti/toggle-disponibilita.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'id=' + id
    })
    .then(response => response.json())
    .then(data => {
        if (!data.success) {
            alert('Errore: ' + data.message);
            location.reload();
        }
    });
}

function deletePiatto(id) {
    if (confirm('Eliminare definitivamente questo piatto?')) {
        fetch('<?php echo BASE_URL; ?>/api/piatti/delete.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'id=' + id
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