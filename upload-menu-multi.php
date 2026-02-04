<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/classes/Database.php';
require_once __DIR__ . '/classes/Model.php';
require_once __DIR__ . '/classes/Helpers.php';
require_once __DIR__ . '/classes/models/User.php';
require_once __DIR__ . '/classes/models/LocaleRestaurant.php';

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

$pageTitle = 'Carica Menu AI - Multi Pagina';
require_once __DIR__ . '/dashboard/includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="bi bi-cloud-upload"></i> Carica Menu Cartaceo (Multi-Pagina)</h5>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i> 
                    Carica tutte le pagine del tuo menu. Puoi scattare foto direttamente o caricare file esistenti.
                </div>

                <!-- Area Upload -->
                <div class="mb-4">
                    <input type="hidden" id="localeId" value="<?php echo $localeSelezionato['id']; ?>">
                    
                    <div class="d-flex gap-2 mb-3">
                        <button type="button" class="btn btn-primary" id="btnCamera">
                            <i class="bi bi-camera"></i> Scatta Foto
                        </button>
                        <button type="button" class="btn btn-secondary" onclick="document.getElementById('fileInput').click()">
                            <i class="bi bi-file-earmark-image"></i> Carica File
                        </button>
                        <input type="file" id="fileInput" accept="image/*,.pdf" style="display:none" multiple>
                    </div>

                    <!-- Video Camera (nascosto inizialmente) -->
                    <div id="cameraContainer" style="display:none;" class="mb-3">
                        <video id="video" width="100%" style="max-width:500px; border:2px solid #ddd; border-radius:8px;" autoplay></video>
                        <div class="mt-2">
                            <button type="button" class="btn btn-success" id="btnCapture">
                                <i class="bi bi-camera-fill"></i> Scatta
                            </button>
                            <button type="button" class="btn btn-danger" id="btnCloseCamera">
                                <i class="bi bi-x-circle"></i> Chiudi
                            </button>
                        </div>
                        <canvas id="canvas" style="display:none;"></canvas>
                    </div>
                </div>

                <!-- Pagine Caricate -->
                <div id="pagesContainer" class="mb-4">
                    <h6>Pagine Caricate: <span id="pageCount">0</span></h6>
                    <div id="pagesList" class="row g-3"></div>
                </div>

                <!-- Pulsante Elabora -->
                <div class="d-grid gap-2">
                    <button type="button" class="btn btn-success btn-lg" id="btnProcess" disabled>
                        <i class="bi bi-magic"></i> Elabora Menu con AI
                    </button>
                </div>

                <!-- Progress -->
                <div id="uploadProgress" class="mt-3" style="display:none;">
                    <div class="progress">
                        <div class="progress-bar progress-bar-striped progress-bar-animated" 
                             role="progressbar" style="width: 0%">0%</div>
                    </div>
                    <p class="text-center mt-2" id="progressText"></p>
                </div>
            </div>
        </div>

        <!-- Istruzioni -->
        <div class="card shadow-sm mt-3">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-info-circle"></i> Come Funziona</h6>
            </div>
            <div class="card-body">
                <ol class="mb-0">
                    <li><strong>Carica tutte le pagine</strong> del menu (foto o file)</li>
                    <li><strong>Riordina le pagine</strong> trascinandole se necessario</li>
                    <li><strong>Clicca "Elabora"</strong> per analizzare con AI</li>
                    <li><strong>Rivedi e modifica</strong> il risultato nella preview</li>
                    <li><strong>Salva il menu</strong> nel database</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<script>
const pages = [];
let stream = null;

// Camera
document.getElementById('btnCamera').addEventListener('click', async function() {
    try {
        stream = await navigator.mediaDevices.getUserMedia({ 
            video: { facingMode: 'environment' },
            audio: false 
        });
        document.getElementById('video').srcObject = stream;
        document.getElementById('cameraContainer').style.display = 'block';
    } catch (error) {
        alert('Impossibile accedere alla camera: ' + error.message);
    }
});

document.getElementById('btnCloseCamera').addEventListener('click', function() {
    if (stream) {
        stream.getTracks().forEach(track => track.stop());
        stream = null;
    }
    document.getElementById('cameraContainer').style.display = 'none';
});

document.getElementById('btnCapture').addEventListener('click', function() {
    const video = document.getElementById('video');
    const canvas = document.getElementById('canvas');
    
    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;
    
    const ctx = canvas.getContext('2d');
    ctx.drawImage(video, 0, 0);
    
    canvas.toBlob(blob => {
        const file = new File([blob], `foto_${Date.now()}.jpg`, { type: 'image/jpeg' });
        addPage(file);
    }, 'image/jpeg', 0.9);
});

// File Input
document.getElementById('fileInput').addEventListener('change', function(e) {
    Array.from(e.target.files).forEach(file => addPage(file));
    e.target.value = '';
});

function addPage(file) {
    const pageNum = pages.length + 1;
    
    const pageData = {
        id: Date.now() + Math.random(),
        file: file,
        name: file.name,
        preview: URL.createObjectURL(file)
    };
    
    pages.push(pageData);
    renderPages();
    updateUI();
}

function renderPages() {
    const container = document.getElementById('pagesList');
    container.innerHTML = '';
    
    pages.forEach((page, index) => {
        const div = document.createElement('div');
        div.className = 'col-md-3';
        div.innerHTML = `
            <div class="card">
                <img src="${page.preview}" class="card-img-top" style="height:200px; object-fit:cover;">
                <div class="card-body p-2">
                    <small class="text-muted d-block">Pagina ${index + 1}</small>
                    <small class="text-truncate d-block">${page.name}</small>
                    <div class="btn-group btn-group-sm mt-2 w-100">
                        ${index > 0 ? `<button class="btn btn-outline-secondary" onclick="movePage(${index}, -1)"><i class="bi bi-arrow-left"></i></button>` : ''}
                        ${index < pages.length - 1 ? `<button class="btn btn-outline-secondary" onclick="movePage(${index}, 1)"><i class="bi bi-arrow-right"></i></button>` : ''}
                        <button class="btn btn-outline-danger" onclick="removePage(${index})"><i class="bi bi-trash"></i></button>
                    </div>
                </div>
            </div>
        `;
        container.appendChild(div);
    });
}

function movePage(index, direction) {
    const newIndex = index + direction;
    if (newIndex >= 0 && newIndex < pages.length) {
        [pages[index], pages[newIndex]] = [pages[newIndex], pages[index]];
        renderPages();
    }
}

function removePage(index) {
    URL.revokeObjectURL(pages[index].preview);
    pages.splice(index, 1);
    renderPages();
    updateUI();
}

function updateUI() {
    document.getElementById('pageCount').textContent = pages.length;
    document.getElementById('btnProcess').disabled = pages.length === 0;
}

// Elabora
document.getElementById('btnProcess').addEventListener('click', async function() {
    if (pages.length === 0) return;
    
    const progressDiv = document.getElementById('uploadProgress');
    const progressBar = progressDiv.querySelector('.progress-bar');
    const progressText = document.getElementById('progressText');
    const btnProcess = this;
    
    btnProcess.disabled = true;
    progressDiv.style.display = 'block';
    progressBar.style.width = '0%';
    progressText.textContent = 'Caricamento pagine...';
    
    try {
        const formData = new FormData();
        formData.append('locale_id', document.getElementById('localeId').value);
        
        pages.forEach((page, index) => {
            formData.append('pages[]', page.file);
        });
        
        progressBar.style.width = '20%';
        progressText.textContent = `Elaborazione AI in corso... (${pages.length} pagine)`;
        
        const response = await fetch('<?php echo BASE_URL; ?>/process-menu-multi.php', {
            method: 'POST',
            body: formData
        });
        
        progressBar.style.width = '80%';
        
        const result = await response.json();
        
        if (result.success) {
            progressBar.style.width = '100%';
            progressBar.classList.remove('progress-bar-animated');
            progressBar.classList.add('bg-success');
            progressText.textContent = 'Elaborazione completata! Reindirizzamento...';
            
            setTimeout(() => {
                window.location.href = '<?php echo BASE_URL; ?>/preview-menu.php?session=' + result.session_id;
            }, 1500);
        } else {
            throw new Error(result.message || 'Errore durante elaborazione');
        }
    } catch (error) {
        progressBar.classList.remove('progress-bar-animated');
        progressBar.classList.add('bg-danger');
        progressText.textContent = 'Errore: ' + error.message;
        btnProcess.disabled = false;
    }
});
</script>

<?php require_once __DIR__ . '/dashboard/includes/footer.php'; ?>