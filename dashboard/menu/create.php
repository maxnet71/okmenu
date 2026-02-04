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

$localeId = intval($_GET['locale'] ?? 0);
$locale = $localeModel->getById($localeId);

if (!$locale || $locale['user_id'] != $user['id']) {
    Helpers::redirect(BASE_URL . '/dashboard/menu/');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'locale_id' => $localeId,
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
        $menuId = $menuModel->insert($data);
        if ($menuId) {
            Helpers::redirect(BASE_URL . '/dashboard/menu/?locale=' . $localeId);
        } else {
            $error = 'Errore durante la creazione';
        }
    }
}

$menuPrincipali = $menuModel->getMainMenus($localeId);

$pageTitle = 'Nuovo Menu';
include __DIR__ . '/../includes/header.php';
?>

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header bg-white">
                <h5 class="mb-0">Crea Nuovo Menu - <?php echo $locale['nome']; ?></h5>
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
                        <label for="nome" class="form-label">Nome Menu *</label>
                        <input type="text" class="form-control" id="nome" name="nome" 
                               value="<?php echo $_POST['nome'] ?? ''; ?>" 
                               placeholder="es: Menu Principale, Carta Vini, Menu Pranzo" required>
                    </div>

                    <div class="mb-3">
                        <label for="descrizione" class="form-label">Descrizione</label>
                        <textarea class="form-control" id="descrizione" name="descrizione" 
                                  rows="2"><?php echo $_POST['descrizione'] ?? ''; ?></textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="tipo" class="form-label">Tipo Menu</label>
                            <select class="form-select" id="tipo" name="tipo">
                                <option value="principale">Menu Principale</option>
                                <option value="sottomenu">Sotto-menu</option>
                                <option value="carta_vini">Carta Vini</option>
                                <option value="carta_birre">Carta Birre</option>
                                <option value="carta_cocktail">Carta Cocktail</option>
                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="parent_id" class="form-label">Menu Padre (opzionale)</label>
                            <select class="form-select" id="parent_id" name="parent_id">
                                <option value="">Nessuno</option>
                                <?php foreach ($menuPrincipali as $mp): ?>
                                    <option value="<?php echo $mp['id']; ?>"><?php echo $mp['nome']; ?></option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted">Per creare menu multilivello</small>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="ordinamento" class="form-label">Ordinamento</label>
                            <input type="number" class="form-control" id="ordinamento" name="ordinamento" 
                                   value="<?php echo $_POST['ordinamento'] ?? 0; ?>" min="0">
                            <small class="text-muted">Numero più basso = mostra prima</small>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label for="visibile_da" class="form-label">Visibile Da</label>
                            <input type="datetime-local" class="form-control" id="visibile_da" name="visibile_da" 
                                   value="<?php echo $_POST['visibile_da'] ?? ''; ?>">
                        </div>

                        <div class="col-md-4 mb-3">
                            <label for="visibile_a" class="form-label">Visibile Fino A</label>
                            <input type="datetime-local" class="form-control" id="visibile_a" name="visibile_a" 
                                   value="<?php echo $_POST['visibile_a'] ?? ''; ?>">
                        </div>
                    </div>

                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="pubblicato" name="pubblicato">
                        <label class="form-check-label" for="pubblicato">
                            Pubblica immediatamente
                        </label>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle me-2"></i>Crea Menu
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
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Informazioni</h5>
                <p class="text-muted">
                    Un menu è il contenitore principale dei tuoi piatti, organizzati in categorie.
                </p>
                <hr>
                <h6>Tipi di Menu</h6>
                <ul class="text-muted">
                    <li><strong>Principale:</strong> Menu base del locale</li>
                    <li><strong>Sotto-menu:</strong> Menu collegato a un altro</li>
                    <li><strong>Carte:</strong> Per vini, birre, cocktail</li>
                </ul>
                <hr>
                <h6>Visibilità Programmata</h6>
                <p class="text-muted small">
                    Puoi programmare quando il menu sarà visibile (es: Menu Pranzo solo 12:00-15:00)
                </p>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
