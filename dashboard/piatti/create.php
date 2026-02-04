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

$categoriaId = intval($_GET['categoria'] ?? 0);
$categoria = $categoriaModel->getById($categoriaId);

if (!$categoria) {
    Helpers::redirect(BASE_URL . '/dashboard/piatti/');
}

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

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'categoria_id' => $categoriaId,
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
                $data['immagine'] = $upload['file_path'];
            } else {
                $error = $upload['message'];
            }
        }
        
        if (empty($error)) {
            $piattoId = $piattoModel->insert($data);
            
            if ($piattoId) {
                if (!empty($_POST['allergeni'])) {
                    $piattoModel->setAllergeni($piattoId, $_POST['allergeni']);
                }
                
                if (!empty($_POST['caratteristiche'])) {
                    $piattoModel->setCaratteristiche($piattoId, $_POST['caratteristiche']);
                }
                
                Helpers::redirect(BASE_URL . '/dashboard/piatti/?categoria=' . $categoriaId);
            } else {
                $error = 'Errore durante la creazione';
            }
        }
    }
}

$pageTitle = 'Nuovo Piatto';
include __DIR__ . '/../includes/header.php';
?>

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header bg-white">
                <h5 class="mb-0">Nuovo Piatto - <?php echo $categoria['nome']; ?></h5>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="nome" class="form-label">Nome Piatto *</label>
                        <input type="text" class="form-control" id="nome" name="nome" 
                               value="<?php echo $_POST['nome'] ?? ''; ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="descrizione" class="form-label">Descrizione</label>
                        <textarea class="form-control" id="descrizione" name="descrizione" 
                                  rows="3"><?php echo $_POST['descrizione'] ?? ''; ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="ingredienti" class="form-label">Ingredienti</label>
                        <textarea class="form-control" id="ingredienti" name="ingredienti" 
                                  rows="2" placeholder="Lista ingredienti separati da virgola"><?php echo $_POST['ingredienti'] ?? ''; ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="prezzo" class="form-label">Prezzo (€) *</label>
                        <input type="number" class="form-control" id="prezzo" name="prezzo" 
                               value="<?php echo $_POST['prezzo'] ?? ''; ?>" 
                               step="0.01" min="0" required>
                    </div>

                    <div class="mb-3">
                        <label for="immagine" class="form-label">Immagine Piatto</label>
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
                                               id="all_<?php echo $all['id']; ?>">
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
                                               id="car_<?php echo $car['id']; ?>">
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
                               value="<?php echo $_POST['ordinamento'] ?? 0; ?>" min="0">
                    </div>

                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="disponibile" name="disponibile" checked>
                        <label class="form-check-label" for="disponibile">Piatto disponibile</label>
                    </div>

                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="mostra_prezzo" name="mostra_prezzo" checked>
                        <label class="form-check-label" for="mostra_prezzo">Mostra prezzo nel menu</label>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle me-2"></i>Crea Piatto
                        </button>
                        <a href="index.php?categoria=<?php echo $categoriaId; ?>" class="btn btn-secondary">
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
                    <strong>Categoria:</strong> <?php echo $categoria['nome']; ?><br>
                    <strong>Menu:</strong> <?php echo $menu['nome']; ?>
                </p>
                <hr>
                <h6>Allergeni</h6>
                <p class="text-muted small">
                    Seleziona gli allergeni presenti secondo la normativa UE 1169/2011
                </p>
                <hr>
                <h6>Caratteristiche</h6>
                <p class="text-muted small">
                    Evidenzia caratteristiche speciali (Vegano, Bio, Senza Glutine, etc)
                </p>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>