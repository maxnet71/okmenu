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
$menuModel = new Menu();
$categoriaModel = new Categoria();
$localeModel = new LocaleRestaurant();

$menuId = intval($_GET['menu'] ?? 0);
$menu = $menuModel->getById($menuId);

if (!$menu) {
    Helpers::redirect(BASE_URL . '/dashboard/menu/');
}

$locale = $localeModel->getById($menu['locale_id']);
if (!$locale || $locale['user_id'] != $user['id']) {
    Helpers::redirect(BASE_URL . '/dashboard/menu/');
}

$categorie = $categoriaModel->getByMenuId($menuId);

$pageTitle = 'Categorie - ' . $menu['nome'];
$pageActions = '<a href="create.php?menu=' . $menuId . '" class="btn btn-primary"><i class="bi bi-plus-circle me-2"></i>Nuova Categoria</a>';
include __DIR__ . '/../includes/header.php';
?>

<div class="row mb-3">
    <div class="col">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../menu/?locale=<?php echo $locale['id']; ?>">Menu</a></li>
                <li class="breadcrumb-item active"><?php echo $menu['nome']; ?></li>
            </ol>
        </nav>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <?php if (empty($categorie)): ?>
            <div class="text-center py-5">
                <i class="bi bi-list-ul display-1 text-muted"></i>
                <h4 class="mt-3">Nessuna categoria configurata</h4>
                <p class="text-muted">Le categorie organizzano i piatti del menu (es: Antipasti, Primi, Secondi)</p>
                <a href="create.php?menu=<?php echo $menuId; ?>" class="btn btn-primary btn-lg">
                    <i class="bi bi-plus-circle me-2"></i>Crea Prima Categoria
                </a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Ordine</th>
                            <th>Nome</th>
                            <th>Descrizione</th>
                            <th>Piatti</th>
                            <th>Stato</th>
                            <th class="text-end">Azioni</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categorie as $cat): ?>
                            <tr>
                                <td><?php echo $cat['ordinamento']; ?></td>
                                <td><strong><?php echo $cat['nome']; ?></strong></td>
                                <td><?php echo $cat['descrizione'] ?? '-'; ?></td>
                                <td><?php echo $categoriaModel->countPiatti($cat['id']); ?></td>
                                <td>
                                    <?php if ($cat['attivo']): ?>
                                        <span class="badge bg-success">Attiva</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Non Attiva</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <a href="edit.php?id=<?php echo $cat['id']; ?>" class="btn btn-sm btn-primary" title="Modifica">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <a href="../piatti/?categoria=<?php echo $cat['id']; ?>" class="btn btn-sm btn-success" title="Piatti">
                                        <i class="bi bi-egg-fried"></i>
                                    </a>
                                    <button type="button" class="btn btn-sm btn-danger" 
                                            onclick="deleteCategoria(<?php echo $cat['id']; ?>)" title="Elimina">
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

<script>
function deleteCategoria(id) {
    if (confirm('Sei sicuro di voler eliminare questa categoria? Verranno eliminati anche tutti i piatti associati.')) {
        $.ajax({
            url: '../../api/categorie/delete.php',
            type: 'POST',
            data: { id: id },
            success: function(response) {
                if (response.success) {
                    showToast('Successo', 'Categoria eliminata', 'success');
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
