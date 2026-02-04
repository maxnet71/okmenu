<?php
require_once __DIR__ . '/../../config/config.php';

require_once __DIR__ . '/../../classes/Database.php';
require_once __DIR__ . '/../../classes/Model.php';
require_once __DIR__ . '/../../classes/Helpers.php';
require_once __DIR__ . '/../../classes/models/User.php';
require_once __DIR__ . '/../../classes/models/LocaleRestaurant.php';

Helpers::requireLogin();

$user = Helpers::getUser();
$localeModel = new LocaleRestaurant();

$id = intval($_GET['id'] ?? 0);
$locale = $localeModel->getById($id);

if (!$locale || $locale['user_id'] != $user['id']) {
    Helpers::redirect(BASE_URL . '/dashboard/locali/');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
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
        if ($data['nome'] != $locale['nome']) {
            $data['slug'] = $localeModel->generateSlug($data['nome'], $id);
        }
        
        if (!empty($_FILES['logo']['name'])) {
            $upload = Helpers::uploadImage($_FILES['logo'], 'uploads/locali/');
            if ($upload['success']) {
                if ($locale['logo']) {
                    Helpers::deleteFile($locale['logo']);
                }
                $data['logo'] = $upload['file_path'];
            } else {
                $error = $upload['message'];
            }
        }
        
        if (empty($error)) {
            if ($localeModel->update($id, $data)) {
                $success = 'Locale aggiornato con successo';
                $locale = $localeModel->getById($id);
            } else {
                $error = 'Errore durante l\'aggiornamento';
            }
        }
    }
}

$pageTitle = 'Modifica Locale';
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

                <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <i class="bi bi-check-circle me-2"></i>
                        <?php echo $success; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data">
                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <label for="nome" class="form-label">Nome Locale *</label>
                            <input type="text" class="form-control" id="nome" name="nome" 
                                   value="<?php echo $locale['nome']; ?>" required>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label for="tipo" class="form-label">Tipo</label>
                            <select class="form-select" id="tipo" name="tipo">
                                <option value="ristorante" <?php echo $locale['tipo'] == 'ristorante' ? 'selected' : ''; ?>>Ristorante</option>
                                <option value="pizzeria" <?php echo $locale['tipo'] == 'pizzeria' ? 'selected' : ''; ?>>Pizzeria</option>
                                <option value="bar" <?php echo $locale['tipo'] == 'bar' ? 'selected' : ''; ?>>Bar</option>
                                <option value="pub" <?php echo $locale['tipo'] == 'pub' ? 'selected' : ''; ?>>Pub</option>
                                <option value="hotel" <?php echo $locale['tipo'] == 'hotel' ? 'selected' : ''; ?>>Hotel</option>
                                <option value="gelateria" <?php echo $locale['tipo'] == 'gelateria' ? 'selected' : ''; ?>>Gelateria</option>
                                <option value="altro" <?php echo $locale['tipo'] == 'altro' ? 'selected' : ''; ?>>Altro</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="descrizione" class="form-label">Descrizione</label>
                        <textarea class="form-control" id="descrizione" name="descrizione" 
                                  rows="3"><?php echo $locale['descrizione']; ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="indirizzo" class="form-label">Indirizzo</label>
                        <input type="text" class="form-control" id="indirizzo" name="indirizzo" 
                               value="<?php echo $locale['indirizzo']; ?>">
                    </div>

                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <label for="citta" class="form-label">Città</label>
                            <input type="text" class="form-control" id="citta" name="citta" 
                                   value="<?php echo $locale['citta']; ?>">
                        </div>

                        <div class="col-md-4 mb-3">
                            <label for="cap" class="form-label">CAP</label>
                            <input type="text" class="form-control" id="cap" name="cap" 
                                   value="<?php echo $locale['cap']; ?>">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="telefono" class="form-label">Telefono</label>
                            <input type="tel" class="form-control" id="telefono" name="telefono" 
                                   value="<?php echo $locale['telefono']; ?>">
                        </div>

                        <div class="col-md-4 mb-3">
                            <label for="whatsapp" class="form-label">WhatsApp</label>
                            <input type="tel" class="form-control" id="whatsapp" name="whatsapp" 
                                   value="<?php echo $locale['whatsapp']; ?>">
                        </div>

                        <div class="col-md-4 mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?php echo $locale['email']; ?>">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="logo" class="form-label">Logo</label>
                        <?php if ($locale['logo']): ?>
                            <div class="mb-2">
                                <img src="<?php echo UPLOAD_URL . $locale['logo']; ?>" 
                                     alt="Logo attuale" style="max-height: 100px;">
                            </div>
                        <?php endif; ?>
                        <input type="file" class="form-control" id="logo" name="logo" 
                               accept="image/*" data-preview="#logoPreview">
                        <img id="logoPreview" class="image-upload-preview mt-2" style="display:none;">
                    </div>

                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="attivo" name="attivo" 
                               <?php echo $locale['attivo'] ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="attivo">Locale attivo</label>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle me-2"></i>Salva Modifiche
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
                    Modifica i dati del tuo locale. Se cambi il nome, verrà generato 
                    un nuovo URL univoco.
                </p>
                <hr>
                <h6>URL Menu</h6>
                <div class="input-group">
                    <input type="text" class="form-control" 
                           value="<?php echo BASE_URL . '/menu/' . $locale['slug']; ?>" readonly>
                    <button class="btn btn-outline-secondary copy-link" 
                            data-link="<?php echo BASE_URL . '/menu/' . $locale['slug']; ?>">
                        <i class="bi bi-clipboard"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>