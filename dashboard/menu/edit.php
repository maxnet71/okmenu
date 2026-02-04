<?php
require_once __DIR__ . '/../../config/config.php';

require_once __DIR__ . '/../../classes/Database.php';
require_once __DIR__ . '/../../classes/Model.php';
require_once __DIR__ . '/../../classes/Helpers.php';
require_once __DIR__ . '/../../classes/models/User.php';
require_once __DIR__ . '/../../classes/models/LocaleRestaurant.php';
require_once __DIR__ . '/../../classes/models/Menu.php';

Helpers::requireLogin();

$user = Helpers::getUser();
$localeModel = new LocaleRestaurant();
$menuModel = new Menu();

$id = intval($_GET['id'] ?? 0);
$menu = $menuModel->getById($id);

if (!$menu) {
    Helpers::redirect(BASE_URL . '/dashboard/menu/');
}

$locale = $localeModel->getById($menu['locale_id']);
if (!$locale || $locale['user_id'] != $user['id']) {
    Helpers::redirect(BASE_URL . '/dashboard/menu/');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'nome' => Helpers::sanitizeInput($_POST['nome'] ?? ''),
        'descrizione' => Helpers::sanitizeInput($_POST['descrizione'] ?? ''),
        'tipo' => $_POST['tipo'] ?? 'principale',
        'parent_id' => !empty($_POST['parent_id']) ? intval($_POST['parent_id']) : null,
        'ordinamento' => intval($_POST['ordinamento'] ?? 0),
        'pubblicato' => isset($_POST['pubblicato']) ? 1 : 0,
        'visibile_da' => !empty($_POST['visibile_da']) ? $_POST['visibile_da'] : null,
        'visibile_a' => !empty($_POST['visibile_a']) ? $_POST['visibile_a'] : null
    ];
    
    if (empty($data['nome'])) {
        $error = 'Il nome è obbligatorio';
    } else {
        if ($menuModel->update($id, $data)) {
            $success = 'Menu aggiornato con successo';
            $menu = $menuModel->getById($id);
        } else {
            $error = 'Errore durante l\'aggiornamento';
        }
    }
}

$menuPrincipali = $menuModel->getMainMenus($locale['id']);

$pageTitle = 'Modifica Menu';
include __DIR__ . '/../includes/header.php';
?>

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header bg-white">
                <h5 class="mb-0">Modifica Menu - <?php echo $locale['nome']; ?></h5>
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
                        <label for="nome" class="form-label">Nome Menu *</label>
                        <input type="text" class="form-control" id="nome" name="nome" 
                               value="<?php echo $menu['nome']; ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="descrizione" class="form-label">Descrizione</label>
                        <textarea class="form-control" id="descrizione" name="descrizione" 
                                  rows="2"><?php echo $menu['descrizione']; ?></textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="tipo" class="form-label">Tipo Menu</label>
                            <select class="form-select" id="tipo" name="tipo">
                                <option value="principale" <?php echo $menu['tipo'] == 'principale' ? 'selected' : ''; ?>>Menu Principale</option>
                                <option value="sottomenu" <?php echo $menu['tipo'] == 'sottomenu' ? 'selected' : ''; ?>>Sotto-menu</option>
                                <option value="carta_vini" <?php echo $menu['tipo'] == 'carta_vini' ? 'selected' : ''; ?>>Carta Vini</option>
                                <option value="carta_birre" <?php echo $menu['tipo'] == 'carta_birre' ? 'selected' : ''; ?>>Carta Birre</option>
                                <option value="carta_cocktail" <?php echo $menu['tipo'] == 'carta_cocktail' ? 'selected' : ''; ?>>Carta Cocktail</option>
                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="parent_id" class="form-label">Menu Padre</label>
                            <select class="form-select" id="parent_id" name="parent_id">
                                <option value="">Nessuno</option>
                                <?php foreach ($menuPrincipali as $mp): ?>
                                    <?php if ($mp['id'] != $id): ?>
                                        <option value="<?php echo $mp['id']; ?>" 
                                                <?php echo $menu['parent_id'] == $mp['id'] ? 'selected' : ''; ?>>
                                            <?php echo $mp['nome']; ?>
                                        </option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="ordinamento" class="form-label">Ordinamento</label>
                            <input type="number" class="form-control" id="ordinamento" name="ordinamento" 
                                   value="<?php echo $menu['ordinamento']; ?>" min="0">
                        </div>

                        <div class="col-md-4 mb-3">
                            <label for="visibile_da" class="form-label">Visibile Da</label>
                            <input type="datetime-local" class="form-control" id="visibile_da" name="visibile_da" 
                                   value="<?php echo $menu['visibile_da'] ? date('Y-m-d\TH:i', strtotime($menu['visibile_da'])) : ''; ?>">
                        </div>

                        <div class="col-md-4 mb-3">
                            <label for="visibile_a" class="form-label">Visibile Fino A</label>
                            <input type="datetime-local" class="form-control" id="visibile_a" name="visibile_a" 
                                   value="<?php echo $menu['visibile_a'] ? date('Y-m-d\TH:i', strtotime($menu['visibile_a'])) : ''; ?>">
                        </div>
                    </div>

                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="pubblicato" name="pubblicato" 
                               <?php echo $menu['pubblicato'] ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="pubblicato">
                            Menu pubblicato (visibile ai clienti)
                        </label>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle me-2"></i>Salva Modifiche
                        </button>
                        <a href="index.php?locale=<?php echo $locale['id']; ?>" class="btn btn-secondary">
                            <i class="bi bi-x-circle me-2"></i>Annulla
                        </a>
                        <a href="../categorie/?menu=<?php echo $id; ?>" class="btn btn-success">
                            <i class="bi bi-list-ul me-2"></i>Gestisci Categorie
                        </a>
                        <a href="../aspetto/?menu=<?php echo $id; ?>" class="btn btn-info ms-auto">
                            <i class="bi bi-palette me-2"></i>Personalizza Aspetto
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card mb-3">
            <div class="card-body">
                <h5 class="card-title">Stato Menu</h5>
                <p><strong>Pubblicato:</strong> 
                    <span class="badge bg-<?php echo $menu['pubblicato'] ? 'success' : 'warning'; ?>">
                        <?php echo $menu['pubblicato'] ? 'Sì' : 'No'; ?>
                    </span>
                </p>
                <p><strong>Sotto-menu:</strong> 
                    <?php echo $menuModel->hasSubMenus($id) ? 'Sì' : 'No'; ?>
                </p>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <h5 class="card-title text-danger">Zona Pericolosa</h5>
                <p class="text-muted small">
                    L'eliminazione del menu rimuoverà anche tutte le categorie e i piatti associati.
                </p>
                <button type="button" class="btn btn-danger w-100" 
                        onclick="deleteMenu(<?php echo $id; ?>)">
                    <i class="bi bi-trash me-2"></i>Elimina Menu
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function deleteMenu(id) {
    if (confirm('Sei sicuro di voler eliminare questo menu? Verranno eliminate anche tutte le categorie e i piatti associati. Questa azione non può essere annullata.')) {
        $.ajax({
            url: '../../api/menu/delete.php',
            type: 'POST',
            data: { id: id },
            success: function(response) {
                if (response.success) {
                    showToast('Successo', 'Menu eliminato', 'success');
                    setTimeout(() => window.location.href = 'index.php?locale=<?php echo $locale['id']; ?>', 1000);
                } else {
                    showToast('Errore', response.message, 'danger');
                }
            }
        });
    }
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>