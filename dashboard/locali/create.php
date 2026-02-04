<?php
require_once __DIR__ . '/../../config/config.php';

require_once __DIR__ . '/../../classes/Database.php';
require_once __DIR__ . '/../../classes/Model.php';
require_once __DIR__ . '/../../classes/Helpers.php';
require_once __DIR__ . '/../../classes/models/User.php';
require_once __DIR__ . '/../../classes/models/LocaleRestaurant.php';

Helpers::requireLogin();

$user = Helpers::getUser();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $localeModel = new LocaleRestaurant();
    
    $data = [
        'user_id' => $user['id'],
        'nome' => Helpers::sanitizeInput($_POST['nome'] ?? ''),
        'tipo' => $_POST['tipo'] ?? 'ristorante',
        'indirizzo' => Helpers::sanitizeInput($_POST['indirizzo'] ?? ''),
        'citta' => Helpers::sanitizeInput($_POST['citta'] ?? ''),
        'cap' => Helpers::sanitizeInput($_POST['cap'] ?? ''),
        'telefono' => Helpers::sanitizeInput($_POST['telefono'] ?? ''),
        'whatsapp' => Helpers::sanitizeInput($_POST['whatsapp'] ?? ''),
        'email' => Helpers::sanitizeInput($_POST['email'] ?? ''),
        'descrizione' => Helpers::sanitizeInput($_POST['descrizione'] ?? ''),
        'attivo' => isset($_POST['attivo']) ? 1 : 0
    ];
    
    if (empty($data['nome'])) {
        $error = 'Il nome è obbligatorio';
    } else {
        $data['slug'] = $localeModel->generateSlug($data['nome']);
        
        if (!empty($_FILES['logo']['name'])) {
            $upload = Helpers::uploadImage($_FILES['logo'], 'uploads/locali/');
            if ($upload['success']) {
                $data['logo'] = $upload['file_path'];
            } else {
                $error = $upload['message'];
            }
        }
        
        if (empty($error)) {
            $localeId = $localeModel->insert($data);
            
            if ($localeId) {
                $success = 'Locale creato con successo';
                header('Location: index.php');
                exit;
            } else {
                $error = 'Errore durante la creazione';
            }
        }
    }
}

$pageTitle = 'Nuovo Locale';
include __DIR__ . '/../includes/header.php';
?>

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data">
                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <label for="nome" class="form-label">Nome Locale *</label>
                            <input type="text" class="form-control" id="nome" name="nome" 
                                   value="<?php echo $_POST['nome'] ?? ''; ?>" required>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label for="tipo" class="form-label">Tipo</label>
                            <select class="form-select" id="tipo" name="tipo">
                                <option value="ristorante">Ristorante</option>
                                <option value="pizzeria">Pizzeria</option>
                                <option value="bar">Bar</option>
                                <option value="pub">Pub</option>
                                <option value="hotel">Hotel</option>
                                <option value="gelateria">Gelateria</option>
                                <option value="altro">Altro</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="descrizione" class="form-label">Descrizione</label>
                        <textarea class="form-control" id="descrizione" name="descrizione" 
                                  rows="3"><?php echo $_POST['descrizione'] ?? ''; ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="indirizzo" class="form-label">Indirizzo</label>
                        <input type="text" class="form-control" id="indirizzo" name="indirizzo" 
                               value="<?php echo $_POST['indirizzo'] ?? ''; ?>">
                    </div>

                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <label for="citta" class="form-label">Città</label>
                            <input type="text" class="form-control" id="citta" name="citta" 
                                   value="<?php echo $_POST['citta'] ?? ''; ?>">
                        </div>

                        <div class="col-md-4 mb-3">
                            <label for="cap" class="form-label">CAP</label>
                            <input type="text" class="form-control" id="cap" name="cap" 
                                   value="<?php echo $_POST['cap'] ?? ''; ?>">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="telefono" class="form-label">Telefono</label>
                            <input type="tel" class="form-control" id="telefono" name="telefono" 
                                   value="<?php echo $_POST['telefono'] ?? ''; ?>">
                        </div>

                        <div class="col-md-4 mb-3">
                            <label for="whatsapp" class="form-label">WhatsApp</label>
                            <input type="tel" class="form-control" id="whatsapp" name="whatsapp" 
                                   value="<?php echo $_POST['whatsapp'] ?? ''; ?>">
                        </div>

                        <div class="col-md-4 mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?php echo $_POST['email'] ?? ''; ?>">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="logo" class="form-label">Logo</label>
                        <input type="file" class="form-control" id="logo" name="logo" 
                               accept="image/*" data-preview="#logoPreview">
                        <img id="logoPreview" class="image-upload-preview mt-2" style="display:none;">
                    </div>

                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="attivo" name="attivo" checked>
                        <label class="form-check-label" for="attivo">Locale attivo</label>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle me-2"></i>Salva
                        </button>
                        <a href="index.php" class="btn btn-secondary">
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
                    Compila i dati del tuo locale. Il nome verrà utilizzato per generare 
                    l'URL univoco del menu digitale.
                </p>
                <hr>
                <h6>Campi obbligatori</h6>
                <ul class="text-muted">
                    <li>Nome Locale</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>