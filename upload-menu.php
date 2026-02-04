<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/classes/Database.php';
require_once __DIR__ . '/classes/Model.php';
require_once __DIR__ . '/classes/Helpers.php';
require_once __DIR__ . '/classes/models/User.php';
require_once __DIR__ . '/classes/models/LocaleRestaurant.php';
require_once __DIR__ . '/classes/models/Menu.php';
require_once __DIR__ . '/classes/models/Categoria.php';
require_once __DIR__ . '/classes/models/Piatto.php';
require_once __DIR__ . '/MenuAI.php';

Helpers::requireLogin();

$user = Helpers::getUser();
$localeModel = new LocaleRestaurant();
$locali = $localeModel->getByUserId($user['id']);

if (empty($locali)) {
    $_SESSION['flash_message'] = 'Devi prima creare un locale';
    $_SESSION['flash_type'] = 'warning';
    header('Location: ' . BASE_URL . '/dashboard/locali/create.php');
    exit;
}

$localeId = intval($_GET['locale'] ?? 0);
$localeSelezionato = null;

if ($localeId) {
    foreach ($locali as $loc) {
        if ($loc['id'] == $localeId) {
            $localeSelezionato = $loc;
            break;
        }
    }
}

if (!$localeSelezionato) {
    $localeSelezionato = $locali[0];
}

$menuAI = new MenuAI();
$uploads = $menuAI->getByLocaleId($localeSelezionato['id']);

$pageTitle = 'Carica Menu con AI';
require_once __DIR__ . '/dashboard/includes/header.php';
?>

<div class="row">
    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="bi bi-cloud-upload"></i> Carica Menu Cartaceo</h5>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i> 
                    Carica un'immagine o PDF del tuo menu cartaceo. L'AI analizzerà automaticamente il contenuto e creerà il menu digitale.
                </div>

                <form id="uploadForm" enctype="multipart/form-data">
                    <input type="hidden" name="locale_id" value="<?php echo $localeSelezionato['id']; ?>">
                    
                    <div class="mb-3">
                        <label for="menuFile" class="form-label">File Menu</label>
                        <input type="file" class="form-control" id="menuFile" name="menu_file" 
                               accept=".jpg,.jpeg,.png,.gif,.webp,.pdf" required>
                        <small class="text-muted">Formati supportati: JPG, PNG, GIF, WEBP, PDF (max 10MB)</small>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="bi bi-magic"></i> Elabora con AI
                        </button>
                    </div>
                </form>

                <div id="uploadProgress" class="mt-3" style="display:none;">
                    <div class="progress">
                        <div class="progress-bar progress-bar-striped progress-bar-animated" 
                             role="progressbar" style="width: 0%">0%</div>
                    </div>
                    <p class="text-center mt-2" id="progressText">Caricamento in corso...</p>
                </div>
            </div>
        </div>

        <div class="card shadow-sm mt-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-question-circle"></i> Come Funziona</h5>
            </div>
            <div class="card-body">
                <ol>
                    <li>Carica un'immagine chiara o PDF del tuo menu cartaceo</li>
                    <li>L'AI Claude analizza il testo e la struttura del menu</li>
                    <li>Vengono estratti automaticamente: categorie, piatti, prezzi, descrizioni, allergeni</li>
                    <li>Il menu digitale viene creato e salvato nel database</li>
                    <li>Puoi modificare e personalizzare il risultato dalla sezione Menu</li>
                </ol>
                
                <div class="alert alert-warning">
                    <strong>Suggerimenti per risultati ottimali:</strong>
                    <ul class="mb-0">
                        <li>Usa immagini ben illuminate e nitide</li>
                        <li>Assicurati che il testo sia leggibile</li>
                        <li>Evita riflessi o ombre eccessive</li>
                        <li>Per menu multi-pagina, carica una pagina alla volta</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card shadow-sm">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-clock-history"></i> Upload Recenti</h5>
            </div>
            <div class="card-body">
                <?php if (empty($uploads)): ?>
                    <p class="text-muted text-center">Nessun upload effettuato</p>
                <?php else: ?>
                    <div class="list-group">
                        <?php foreach ($uploads as $upload): ?>
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1"><?php echo htmlspecialchars($upload['filename']); ?></h6>
                                        <small class="text-muted">
                                            <?php echo date('d/m/Y H:i', strtotime($upload['created_at'])); ?>
                                        </small>
                                    </div>
                                    <div>
                                        <?php
                                        $badges = [
                                            'pending' => '<span class="badge bg-secondary">In attesa</span>',
                                            'processing' => '<span class="badge bg-warning">Elaborazione</span>',
                                            'completed' => '<span class="badge bg-success">Completato</span>',
                                            'failed' => '<span class="badge bg-danger">Errore</span>'
                                        ];
                                        echo $badges[$upload['status']] ?? '';
                                        ?>
                                    </div>
                                </div>
                                <?php if ($upload['status'] === 'completed'): ?>
                                    <small class="text-success">
                                        <i class="bi bi-check-circle"></i> 
                                        <?php echo $upload['items_created']; ?> elementi creati
                                    </small>
                                <?php elseif ($upload['status'] === 'failed'): ?>
                                    <small class="text-danger">
                                        <i class="bi bi-exclamation-circle"></i> 
                                        <?php echo htmlspecialchars($upload['error_message'] ?? 'Errore sconosciuto'); ?>
                                    </small>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('uploadForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const progressDiv = document.getElementById('uploadProgress');
    const progressBar = progressDiv.querySelector('.progress-bar');
    const progressText = document.getElementById('progressText');
    const submitBtn = this.querySelector('button[type="submit"]');
    
    submitBtn.disabled = true;
    progressDiv.style.display = 'block';
    progressBar.style.width = '10%';
    progressBar.textContent = '10%';
    progressText.textContent = 'Caricamento file...';
    
    try {
        const response = await fetch('<?php echo BASE_URL; ?>/process-menu-ai.php', {
            method: 'POST',
            body: formData
        });
        
        progressBar.style.width = '50%';
        progressBar.textContent = '50%';
        progressText.textContent = 'Elaborazione AI in corso...';
        
        const result = await response.json();
        
        if (result.success) {
            progressBar.style.width = '100%';
            progressBar.textContent = '100%';
            progressBar.classList.remove('progress-bar-animated');
            progressBar.classList.add('bg-success');
            progressText.textContent = result.message;
            
            setTimeout(() => {
                window.location.href = '<?php echo BASE_URL; ?>/dashboard/menu/?locale=<?php echo $localeSelezionato['id']; ?>';
            }, 2000);
        } else {
            throw new Error(result.message || 'Errore durante elaborazione');
        }
    } catch (error) {
        progressBar.classList.remove('progress-bar-animated');
        progressBar.classList.add('bg-danger');
        progressText.textContent = 'Errore: ' + error.message;
        submitBtn.disabled = false;
    }
});
</script>

<?php require_once __DIR__ . '/dashboard/includes/footer.php'; ?>