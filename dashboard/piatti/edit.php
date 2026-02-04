<?php
require_once __DIR__ . '/../../config/config.php';

require_once __DIR__ . '/../../classes/Database.php';
require_once __DIR__ . '/../../classes/Model.php';
require_once __DIR__ . '/../../classes/Helpers.php';
require_once __DIR__ . '/../../classes/models/User.php';
require_once __DIR__ . '/../../classes/models/LocaleRestaurant.php';
require_once __DIR__ . '/../../classes/models/Menu.php';
require_once __DIR__ . '/../../classes/models/Categoria.php';
require_once __DIR__ . '/../../classes/models/Piatto.php';

Helpers::requireLogin();

$user = Helpers::getUser();
$piattoModel = new Piatto();
$categoriaModel = new Categoria();
$menuModel = new Menu();
$localeModel = new LocaleRestaurant();

$id = intval($_GET['id'] ?? 0);
$piatto = $piattoModel->getById($id);

if (!$piatto) {
    Helpers::redirect(BASE_URL . '/dashboard/piatti/');
}

$categoria = $categoriaModel->getById($piatto['categoria_id']);
$menu = $menuModel->getById($categoria['menu_id']);
$locale = $localeModel->getById($menu['locale_id']);

if (!$locale || $locale['user_id'] != $user['id']) {
    Helpers::redirect(BASE_URL . '/dashboard/piatti/');
}

$db = Database::getInstance()->getConnection();

$stmtAllergeni = $db->prepare("SELECT * FROM allergeni ORDER BY ordinamento ASC");
$stmtAllergeni->execute();
$allergeni = $stmtAllergeni->fetchAll();

$stmtCaratteristiche = $db->prepare("SELECT * FROM caratteristiche ORDER BY ordinamento ASC");
$stmtCaratteristiche->execute();
$caratteristiche = $stmtCaratteristiche->fetchAll();

$allergeniPiatto = $piattoModel->getAllergeni($id);
$caratteristichePiatto = $piattoModel->getCaratteristiche($id);

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'nome' => Helpers::sanitizeInput($_POST['nome'] ?? ''),
        'descrizione' => Helpers::sanitizeInput($_POST['descrizione'] ?? ''),
        'ingredienti' => Helpers::sanitizeInput($_POST['ingredienti'] ?? ''),
        'prezzo' => floatval($_POST['prezzo'] ?? 0),
        'ordinamento' => intval($_POST['ordinamento'] ?? 0),
        'disponibile' => isset($_POST['disponibile']) ? 1 : 0,
        'mostra_prezzo' => isset($_POST['mostra_prezzo']) ? 1 : 0
    ];
    
    if (empty($data['nome'])) {
        $error = 'Il nome è obbligatorio';
    } else {
        if (!empty($_FILES['immagine']['name'])) {
            $upload = Helpers::uploadImage($_FILES['immagine'], 'uploads/piatti/');
            if ($upload['success']) {
                if ($piatto['immagine']) {
                    Helpers::deleteFile($piatto['immagine']);
                }
                $data['immagine'] = $upload['file_path'];
            } else {
                $error = $upload['message'];
            }
        }
        
        if (empty($error)) {
            if ($piattoModel->update($id, $data)) {
                $piattoModel->setAllergeni($id, $_POST['allergeni'] ?? []);
                $piattoModel->setCaratteristiche($id, $_POST['caratteristiche'] ?? []);
                
                $success = 'Piatto aggiornato con successo';
                $piatto = $piattoModel->getById($id);
                $allergeniPiatto = $piattoModel->getAllergeni($id);
                $caratteristichePiatto = $piattoModel->getCaratteristiche($id);
            } else {
                $error = 'Errore durante l\'aggiornamento';
            }
        }
    }
}

$pageTitle = 'Modifica Piatto';
include __DIR__ . '/../includes/header.php';
?>

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header bg-white">
                <h5 class="mb-0">Modifica Piatto - <?php echo $categoria['nome']; ?></h5>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <i class="bi bi-check-circle me-2"></i>
                        <?php echo $success; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="nome" class="form-label">Nome Piatto *</label>
                        <input type="text" class="form-control" id="nome" name="nome" 
                               value="<?php echo $piatto['nome']; ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="descrizione" class="form-label">Descrizione</label>
                        <textarea class="form-control" id="descrizione" name="descrizione" 
                                  rows="3"><?php echo $piatto['descrizione']; ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="ingredienti" class="form-label">Ingredienti</label>
                        <textarea class="form-control" id="ingredienti" name="ingredienti" 
                                  rows="2" placeholder="Lista ingredienti separati da virgola"><?php echo $piatto['ingredienti']; ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="prezzo" class="form-label">Prezzo (€) *</label>
                        <input type="number" class="form-control" id="prezzo" name="prezzo" 
                               value="<?php echo $piatto['prezzo']; ?>" 
                               step="0.01" min="0" required>
                    </div>

                    <div class="mb-3">
                        <label for="immagine" class="form-label">Immagine Piatto</label>
                        <?php if ($piatto['immagine']): ?>
                            <div class="mb-2">
                                <img src="<?php echo UPLOAD_URL . $piatto['immagine']; ?>" 
                                     alt="Immagine attuale" style="max-height: 200px;">
                            </div>
                        <?php endif; ?>
                        <input type="file" class="form-control" id="immagine" name="immagine" 
                               accept="image/*" data-preview="#imgPreview">
                        <img id="imgPreview" class="image-upload-preview mt-2" style="display:none; max-height: 200px;">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Allergeni</label>
                        <div class="row">
                            <?php foreach ($allergeni as $all): ?>
                                <div class="col-md-6 col-lg-4 mb-2">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" 
                                               name="allergeni[]" value="<?php echo $all['id']; ?>" 
                                               id="all_<?php echo $all['id']; ?>"
                                               <?php echo in_array($all['id'], $allergeniPiatto) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="all_<?php echo $all['id']; ?>">
                                            <span class="badge bg-warning text-dark"><?php echo $all['codice']; ?></span>
                                            <?php echo $all['nome_it']; ?>
                                        </label>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Caratteristiche</label>
                        <div class="row">
                            <?php foreach ($caratteristiche as $car): ?>
                                <div class="col-md-6 col-lg-4 mb-2">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" 
                                               name="caratteristiche[]" value="<?php echo $car['id']; ?>" 
                                               id="car_<?php echo $car['id']; ?>"
                                               <?php echo in_array($car['id'], $caratteristichePiatto) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="car_<?php echo $car['id']; ?>">
                                            <span class="badge" style="background-color: <?php echo $car['colore']; ?>">
                                                <?php echo $car['icona']; ?>
                                            </span>
                                            <?php echo $car['nome']; ?>
                                        </label>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="ordinamento" class="form-label">Ordinamento</label>
                        <input type="number" class="form-control" id="ordinamento" name="ordinamento" 
                               value="<?php echo $piatto['ordinamento']; ?>" min="0">
                    </div>

                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="disponibile" name="disponibile" 
                               <?php echo $piatto['disponibile'] ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="disponibile">Piatto disponibile</label>
                    </div>

                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="mostra_prezzo" name="mostra_prezzo" 
                               <?php echo $piatto['mostra_prezzo'] ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="mostra_prezzo">Mostra prezzo nel menu</label>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle me-2"></i>Salva Modifiche
                        </button>
                        <a href="index.php?categoria=<?php echo $categoria['id']; ?>" class="btn btn-secondary">
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
                <h5 class="card-title">Informazioni</h5>
                <p class="text-muted">
                    <strong>Categoria:</strong> <?php echo $categoria['nome']; ?><br>
                    <strong>Menu:</strong> <?php echo $menu['nome']; ?><br>
                    <strong>Locale:</strong> <?php echo $locale['nome']; ?>
                </p>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <h5 class="card-title text-danger">Zona Pericolosa</h5>
                <p class="text-muted small">
                    L'eliminazione è definitiva e non può essere annullata.
                </p>
                <button type="button" class="btn btn-danger w-100" 
                        onclick="deletePiatto(<?php echo $id; ?>)">
                    <i class="bi bi-trash me-2"></i>Elimina Piatto
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function deletePiatto(id) {
    if (confirm('Sei sicuro di voler eliminare questo piatto?')) {
        $.ajax({
            url: '../../api/piatti/delete.php',
            type: 'POST',
            data: { id: id },
            success: function(response) {
                if (response.success) {
                    showToast('Successo', 'Piatto eliminato', 'success');
                    setTimeout(() => window.location.href = 'index.php?categoria=<?php echo $categoria['id']; ?>', 1000);
                } else {
                    showToast('Errore', response.message, 'danger');
                }
            }
        });
    }
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>