<?php
require_once __DIR__ . '/../../config/config.php';

require_once __DIR__ . '/../../classes/Database.php';
require_once __DIR__ . '/../../classes/Model.php';
require_once __DIR__ . '/../../classes/Helpers.php';
require_once __DIR__ . '/../../classes/models/User.php';
require_once __DIR__ . '/../../classes/models/LocaleRestaurant.php';
require_once __DIR__ . '/../../classes/models/Menu.php';
require_once __DIR__ . '/../../classes/models/Categoria.php';

Helpers::requireLogin();

$user = Helpers::getUser();
$localeModel = new LocaleRestaurant();
$menuModel = new Menu();

$localeId = intval($_GET['locale'] ?? 0);
$locale = null;

if ($localeId) {
    $locale = $localeModel->getById($localeId);
    if (!$locale || $locale['user_id'] != $user['id']) {
        Helpers::redirect(BASE_URL . '/dashboard/');
    }
}

$locali = $localeModel->getByUserId($user['id']);

if (!$locale && !empty($locali)) {
    $locale = $locali[0];
    $localeId = $locale['id'];
}

$menu = [];
if ($locale) {
    $menu = $menuModel->getByLocaleId($locale['id']);
}

$pageTitle = 'Gestione Menu';
$pageActions = $locale ? '<a href="create.php?locale=' . $locale['id'] . '" class="btn btn-primary"><i class="bi bi-plus-circle me-2"></i>Nuovo Menu</a>' : '';
include __DIR__ . '/../includes/header.php';
?>

<?php if (empty($locali)): ?>
    <div class="alert alert-warning">
        <i class="bi bi-exclamation-triangle me-2"></i>
        Devi prima creare un locale. <a href="../locali/create.php">Crea locale</a>
    </div>
<?php else: ?>
    <div class="row mb-4">
        <div class="col-md-4">
            <label for="localeSelect" class="form-label">Seleziona Locale</label>
            <select class="form-select" id="localeSelect" onchange="window.location.href='?locale='+this.value">
                <?php foreach ($locali as $loc): ?>
                    <option value="<?php echo $loc['id']; ?>" <?php echo $loc['id'] == $localeId ? 'selected' : ''; ?>>
                        <?php echo $loc['nome']; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <?php if (empty($menu)): ?>
                <div class="text-center py-5">
                    <i class="bi bi-journal-text display-1 text-muted"></i>
                    <h4 class="mt-3">Nessun menu configurato</h4>
                    <p class="text-muted">Inizia creando il tuo primo menu</p>
                    <a href="create.php?locale=<?php echo $locale['id']; ?>" class="btn btn-primary btn-lg">
                        <i class="bi bi-plus-circle me-2"></i>Crea il Primo Menu
                    </a>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>Tipo</th>
                                <th>Categorie</th>
                                <th>Pubblicato</th>
                                <th>Visibilità</th>
                                <th class="text-end">Azioni</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($menu as $m): ?>
                                <?php
                                $categoriaModel = new Categoria();
                                $categorie = $categoriaModel->getByMenuId($m['id']);
                                ?>
                                <tr>
                                    <td>
                                        <strong><?php echo $m['nome']; ?></strong>
                                        <?php if ($m['descrizione']): ?>
                                            <br><small class="text-muted"><?php echo $m['descrizione']; ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary">
                                            <?php echo ucfirst(str_replace('_', ' ', $m['tipo'])); ?>
                                        </span>
                                    </td>
                                    <td><?php echo count($categorie); ?></td>
                                    <td>
                                        <?php if ($m['pubblicato']): ?>
                                            <span class="badge bg-success">Pubblicato</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning">Bozza</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($m['visibile_da'] && $m['visibile_a']): ?>
                                            <small class="text-muted">
                                                <?php echo Helpers::formatDate($m['visibile_da']); ?> - 
                                                <?php echo Helpers::formatDate($m['visibile_a']); ?>
                                            </small>
                                        <?php else: ?>
                                            <small class="text-muted">Sempre visibile</small>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <button type="button" class="btn btn-sm btn-<?php echo $m['pubblicato'] ? 'warning' : 'success'; ?>" 
                                                onclick="togglePublish(<?php echo $m['id']; ?>)" title="Pubblica/Nascondi">
                                            <i class="bi bi-<?php echo $m['pubblicato'] ? 'eye-slash' : 'eye'; ?>"></i>
                                        </button>
                                        <a href="edit.php?id=<?php echo $m['id']; ?>" 
                                           class="btn btn-sm btn-primary" title="Modifica">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <a href="../categorie/?menu=<?php echo $m['id']; ?>" 
                                           class="btn btn-sm btn-info" title="Categorie">
                                            <i class="bi bi-list-ul"></i>
                                        </a>
                                        <button type="button" class="btn btn-sm btn-danger" 
                                                onclick="deleteMenu(<?php echo $m['id']; ?>)" title="Elimina">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<script>
function togglePublish(id) {
    $.ajax({
        url: '../../api/menu/toggle-publish.php',
        type: 'POST',
        data: { id: id },
        success: function(response) {
            if (response.success) {
                showToast('Successo', response.message, 'success');
                setTimeout(() => window.location.reload(), 1000);
            } else {
                showToast('Errore', response.message, 'danger');
            }
        }
    });
}

function deleteMenu(id) {
    if (confirm('Sei sicuro di voler eliminare questo menu? Verranno eliminate anche tutte le categorie e i piatti associati.')) {
        $.ajax({
            url: '../../api/menu/delete.php',
            type: 'POST',
            data: { id: id },
            success: function(response) {
                if (response.success) {
                    showToast('Successo', 'Menu eliminato', 'success');
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    showToast('Errore', response.message, 'danger');
                }
            }
        });
    }
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
