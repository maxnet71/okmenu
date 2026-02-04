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

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'menu_id' => $menuId,
        'nome' => Helpers::sanitizeInput($_POST['nome'] ?? ''),
        'descrizione' => Helpers::sanitizeInput($_POST['descrizione'] ?? ''),
        'ordinamento' => intval($_POST['ordinamento'] ?? 0),
        'attivo' => isset($_POST['attivo']) ? 1 : 0
    ];
    
    if (empty($data['nome'])) {
        $error = 'Il nome è obbligatorio';
    } else {
        $catId = $categoriaModel->insert($data);
        if ($catId) {
            Helpers::redirect(BASE_URL . '/dashboard/categorie/?menu=' . $menuId);
        } else {
            $error = 'Errore durante la creazione';
        }
    }
}

$pageTitle = 'Nuova Categoria';
include __DIR__ . '/../includes/header.php';
?>

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header bg-white">
                <h5 class="mb-0">Nuova Categoria - <?php echo $menu['nome']; ?></h5>
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
                        <label for="nome" class="form-label">Nome Categoria *</label>
                        <input type="text" class="form-control" id="nome" name="nome" 
                               value="<?php echo $_POST['nome'] ?? ''; ?>" 
                               placeholder="es: Antipasti, Primi, Secondi, Dolci" required>
                    </div>

                    <div class="mb-3">
                        <label for="descrizione" class="form-label">Descrizione</label>
                        <textarea class="form-control" id="descrizione" name="descrizione" 
                                  rows="2" placeholder="Descrizione opzionale della categoria"><?php echo $_POST['descrizione'] ?? ''; ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="ordinamento" class="form-label">Ordinamento</label>
                        <input type="number" class="form-control" id="ordinamento" name="ordinamento" 
                               value="<?php echo $_POST['ordinamento'] ?? 0; ?>" min="0">
                        <small class="text-muted">Numero più basso = mostra prima</small>
                    </div>

                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="attivo" name="attivo" checked>
                        <label class="form-check-label" for="attivo">Categoria attiva</label>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle me-2"></i>Crea Categoria
                        </button>
                        <a href="index.php?menu=<?php echo $menuId; ?>" class="btn btn-secondary">
                            <i class="bi bi-x-circle me-2"></i>Annulla
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Informazioni</h5>
                <p class="text-muted">
                    Le categorie organizzano i piatti del menu. Esempi tipici:
                </p>
                <ul class="text-muted">
                    <li>Antipasti</li>
                    <li>Primi Piatti</li>
                    <li>Secondi Piatti</li>
                    <li>Contorni</li>
                    <li>Dolci</li>
                    <li>Bevande</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
