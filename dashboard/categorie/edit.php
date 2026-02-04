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
$categoriaModel = new Categoria();
$menuModel = new Menu();
$localeModel = new LocaleRestaurant();

$id = intval($_GET['id'] ?? 0);
$categoria = $categoriaModel->getById($id);

if (!$categoria) {
    Helpers::redirect(BASE_URL . '/dashboard/categorie/');
}

$menu = $menuModel->getById($categoria['menu_id']);
$locale = $localeModel->getById($menu['locale_id']);

if (!$locale || $locale['user_id'] != $user['id']) {
    Helpers::redirect(BASE_URL . '/dashboard/categorie/');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'nome' => Helpers::sanitizeInput($_POST['nome'] ?? ''),
        'descrizione' => Helpers::sanitizeInput($_POST['descrizione'] ?? ''),
        'ordinamento' => intval($_POST['ordinamento'] ?? 0),
        'attivo' => isset($_POST['attivo']) ? 1 : 0
    ];
    
    if (empty($data['nome'])) {
        $error = 'Il nome è obbligatorio';
    } else {
        if ($categoriaModel->update($id, $data)) {
            $success = 'Categoria aggiornata con successo';
            $categoria = $categoriaModel->getById($id);
        } else {
            $error = 'Errore durante l\'aggiornamento';
        }
    }
}

$pageTitle = 'Modifica Categoria';
include __DIR__ . '/../includes/header.php';
?>

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header bg-white">
                <h5 class="mb-0">Modifica Categoria - <?php echo $menu['nome']; ?></h5>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <?php echo $success; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <div class="mb-3">
                        <label for="nome" class="form-label">Nome Categoria *</label>
                        <input type="text" class="form-control" id="nome" name="nome" 
                               value="<?php echo $categoria['nome']; ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="descrizione" class="form-label">Descrizione</label>
                        <textarea class="form-control" id="descrizione" name="descrizione" 
                                  rows="2"><?php echo $categoria['descrizione']; ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="ordinamento" class="form-label">Ordinamento</label>
                        <input type="number" class="form-control" id="ordinamento" name="ordinamento" 
                               value="<?php echo $categoria['ordinamento']; ?>" min="0">
                    </div>

                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="attivo" name="attivo" 
                               <?php echo $categoria['attivo'] ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="attivo">Categoria attiva</label>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle me-2"></i>Salva Modifiche
                        </button>
                        <a href="index.php?menu=<?php echo $menu['id']; ?>" class="btn btn-secondary">
                            <i class="bi bi-x-circle me-2"></i>Annulla
                        </a>
                        <a href="../piatti/?categoria=<?php echo $id; ?>" class="btn btn-success ms-auto">
                            <i class="bi bi-egg-fried me-2"></i>Gestisci Piatti
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card mb-3">
            <div class="card-body">
                <h5 class="card-title">Statistiche</h5>
                <p><strong>Piatti:</strong> <?php echo $categoriaModel->countPiatti($id); ?></p>
                <p><strong>Menu:</strong> <?php echo $menu['nome']; ?></p>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <h5 class="card-title text-danger">Zona Pericolosa</h5>
                <p class="text-muted small">
                    L'eliminazione della categoria rimuoverà anche tutti i piatti associati.
                </p>
                <button type="button" class="btn btn-danger w-100" 
                        onclick="deleteCategoria(<?php echo $id; ?>)">
                    <i class="bi bi-trash me-2"></i>Elimina Categoria
                </button>
            </div>
        </div>
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
                    setTimeout(() => window.location.href = 'index.php?menu=<?php echo $menu['id']; ?>', 1000);
                } else {
                    showToast('Errore', response.message, 'danger');
                }
            }
        });
    }
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
