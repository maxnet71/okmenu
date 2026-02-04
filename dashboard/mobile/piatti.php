<?php
/**
 * Dashboard Mobile - Gestione Piatti Rapida
 * Interfaccia ottimizzata mobile per aggiungere piatti con foto da smartphone
 * 
 * POSIZIONE: /dashboard/mobile/piatti.php
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../classes/Database.php';
require_once __DIR__ . '/../../classes/Model.php';
require_once __DIR__ . '/../../classes/Helpers.php';
require_once __DIR__ . '/../../classes/DeviceDetector.php';
require_once __DIR__ . '/../../classes/models/User.php';
require_once __DIR__ . '/../../classes/models/LocaleRestaurant.php';
require_once __DIR__ . '/../../classes/models/Menu.php';
require_once __DIR__ . '/../../classes/models/Categoria.php';
require_once __DIR__ . '/../../classes/models/Piatto.php';

Helpers::requireLogin();

$user = Helpers::getUser();
$localeModel = new LocaleRestaurant();
$menuModel = new Menu();
$categoriaModel = new Categoria();
$piattoModel = new Piatto();

$localeId = intval($_GET['locale'] ?? 0);
$locali = $localeModel->getByUserId($user['id']);

if (empty($locali)) {
    Helpers::redirect(BASE_URL . '/dashboard/locali/create.php');
}

if (!$localeId) {
    $localeId = $locali[0]['id'];
}

$locale = $localeModel->getById($localeId);
if (!$locale || $locale['user_id'] != $user['id']) {
    Helpers::redirect(BASE_URL . '/dashboard/mobile/piatti.php');
}

// Ottieni menu e categorie
$menus = $menuModel->getByLocaleId($localeId);
$menuId = !empty($menus) ? $menus[0]['id'] : 0;

if ($menuId) {
    $categorie = $categoriaModel->getByMenuId($menuId);
} else {
    $categorie = [];
}

// Ottieni allergeni e caratteristiche
$db = Database::getInstance()->getConnection();

$sqlAllergeni = "SELECT * FROM allergeni ORDER BY ordinamento";
$stmtAllergeni = $db->query($sqlAllergeni);
$allergeni = $stmtAllergeni->fetchAll();

$sqlCaratteristiche = "SELECT * FROM caratteristiche ORDER BY ordinamento";
$stmtCaratteristiche = $db->query($sqlCaratteristiche);
$caratteristiche = $stmtCaratteristiche->fetchAll();

$pageTitle = 'Aggiungi Piatto Mobile';
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?php echo $pageTitle; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        :root {
            --primary: #007bff;
            --success: #28a745;
            --danger: #dc3545;
            --warning: #ffc107;
        }
        
        * {
            -webkit-tap-highlight-color: transparent;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: #f8f9fa;
            padding-bottom: 80px;
            overflow-x: hidden;
        }
        
        /* HEADER MOBILE */
        .mobile-header {
            position: sticky;
            top: 0;
            background: white;
            border-bottom: 1px solid #dee2e6;
            padding: 15px;
            z-index: 1000;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .mobile-header h1 {
            font-size: 1.3rem;
            margin: 0;
            font-weight: 700;
        }
        
        .mobile-header small {
            color: #6c757d;
            font-size: 0.85rem;
        }
        
        /* FORM MOBILE */
        .mobile-form {
            padding: 0;
        }
        
        .form-section {
            background: white;
            margin-bottom: 15px;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        }
        
        .section-header {
            background: linear-gradient(135deg, var(--primary), #0056b3);
            color: white;
            padding: 15px 20px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .section-header i {
            font-size: 1.3rem;
        }
        
        .section-body {
            padding: 20px;
        }
        
        /* CAMERA INPUT */
        .camera-container {
            position: relative;
            width: 100%;
            aspect-ratio: 4/3;
            background: #f8f9fa;
            border-radius: 15px;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            border: 3px dashed #dee2e6;
            transition: all 0.3s;
        }
        
        .camera-container:active {
            transform: scale(0.98);
        }
        
        .camera-container.has-image {
            border: none;
        }
        
        .camera-icon {
            font-size: 4rem;
            color: #6c757d;
            margin-bottom: 10px;
        }
        
        .camera-text {
            text-align: center;
            color: #6c757d;
        }
        
        .camera-text h3 {
            font-size: 1.2rem;
            margin-bottom: 5px;
        }
        
        .camera-text p {
            font-size: 0.9rem;
            margin: 0;
        }
        
        .preview-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: none;
        }
        
        .preview-image.show {
            display: block;
        }
        
        .remove-image {
            position: absolute;
            top: 10px;
            right: 10px;
            background: rgba(220, 53, 69, 0.9);
            color: white;
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: none;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            cursor: pointer;
            z-index: 10;
        }
        
        .remove-image.show {
            display: flex;
        }
        
        #imageInput {
            display: none;
        }
        
        /* FORM CONTROLS */
        .form-control, .form-select {
            border-radius: 12px;
            padding: 14px 16px;
            font-size: 1rem;
            border: 2px solid #e9ecef;
            transition: all 0.3s;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(0,123,255,0.1);
        }
        
        .form-label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 8px;
            font-size: 0.95rem;
        }
        
        textarea.form-control {
            min-height: 100px;
            resize: vertical;
        }
        
        /* CHECKBOX/BADGE GRID */
        .badges-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
            gap: 10px;
        }
        
        .badge-checkbox {
            position: relative;
        }
        
        .badge-checkbox input {
            position: absolute;
            opacity: 0;
            pointer-events: none;
        }
        
        .badge-checkbox label {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 12px;
            border: 2px solid #dee2e6;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 0.9rem;
            font-weight: 500;
            background: white;
            gap: 6px;
        }
        
        .badge-checkbox input:checked + label {
            border-color: var(--primary);
            background: var(--primary);
            color: white;
            transform: scale(1.05);
        }
        
        /* ALLERGENI GRID */
        .allergeni-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(60px, 1fr));
            gap: 8px;
        }
        
        .allergene-checkbox label {
            padding: 10px 8px;
            font-size: 0.85rem;
            text-align: center;
        }
        
        /* PREZZI GRID */
        .prezzi-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        
        /* BOTTOM BAR */
        .bottom-bar {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: white;
            border-top: 1px solid #dee2e6;
            padding: 15px;
            box-shadow: 0 -2px 20px rgba(0,0,0,0.1);
            z-index: 1000;
        }
        
        .btn-save {
            width: 100%;
            padding: 16px;
            font-size: 1.1rem;
            font-weight: 700;
            border-radius: 12px;
            border: none;
            background: linear-gradient(135deg, var(--success), #218838);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: all 0.3s;
        }
        
        .btn-save:active {
            transform: scale(0.98);
        }
        
        .btn-save:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        /* LOADING */
        .spinner {
            display: none;
        }
        
        .spinner.show {
            display: inline-block;
        }
        
        /* TOAST */
        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
        }
        
        .custom-toast {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
            padding: 15px 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 280px;
            animation: slideIn 0.3s ease;
        }
        
        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        .custom-toast.success {
            border-left: 4px solid var(--success);
        }
        
        .custom-toast.error {
            border-left: 4px solid var(--danger);
        }
        
        .custom-toast i {
            font-size: 1.5rem;
        }
        
        .custom-toast.success i {
            color: var(--success);
        }
        
        .custom-toast.error i {
            color: var(--danger);
        }
        
        /* DISPONIBILITÀ TOGGLE */
        .disponibilita-toggle {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 12px;
        }
        
        .switch {
            position: relative;
            display: inline-block;
            width: 60px;
            height: 34px;
        }
        
        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        
        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: 0.4s;
            border-radius: 34px;
        }
        
        .slider:before {
            position: absolute;
            content: "";
            height: 26px;
            width: 26px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: 0.4s;
            border-radius: 50%;
        }
        
        input:checked + .slider {
            background-color: var(--success);
        }
        
        input:checked + .slider:before {
            transform: translateX(26px);
        }
    </style>
</head>
<body>
    <div class="mobile-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1><i class="bi bi-plus-circle"></i> Nuovo Piatto</h1>
                <small><?php echo htmlspecialchars($locale['nome']); ?></small>
            </div>
            <a href="?<?php echo http_build_query(array_merge($_GET, ['view' => 'desktop'])); ?>" 
               class="btn btn-sm btn-outline-light">
                <i class="bi bi-pc-display"></i>
            </a>
        </div>
    </div>

    <form id="piattoForm" class="mobile-form" enctype="multipart/form-data">
        <input type="hidden" name="locale_id" value="<?php echo $localeId; ?>">
        <input type="hidden" name="menu_id" value="<?php echo $menuId; ?>">

        <!-- FOTO -->
        <div class="form-section">
            <div class="section-header">
                <i class="bi bi-camera-fill"></i>
                <span>Foto Piatto</span>
            </div>
            <div class="section-body">
                <div class="camera-container" onclick="document.getElementById('imageInput').click()">
                    <div class="camera-content">
                        <div class="camera-icon">
                            <i class="bi bi-camera"></i>
                        </div>
                        <div class="camera-text">
                            <h3>Scatta o Carica Foto</h3>
                            <p>Tocca per aprire la fotocamera</p>
                        </div>
                    </div>
                    <img id="previewImage" class="preview-image" alt="Preview">
                    <button type="button" class="remove-image" id="removeImageBtn">
                        <i class="bi bi-x"></i>
                    </button>
                </div>
                <input type="file" 
                       id="imageInput" 
                       name="immagine" 
                       accept="image/*" 
                       capture="environment">
            </div>
        </div>

        <!-- INFORMAZIONI BASE -->
        <div class="form-section">
            <div class="section-header">
                <i class="bi bi-pencil-fill"></i>
                <span>Informazioni</span>
            </div>
            <div class="section-body">
                <div class="mb-3">
                    <label class="form-label">Nome Piatto *</label>
                    <input type="text" 
                           name="nome" 
                           class="form-control" 
                           placeholder="es: Pasta Carbonara" 
                           required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Categoria *</label>
                    <select name="categoria_id" class="form-select" required>
                        <option value="">Seleziona categoria</option>
                        <?php foreach ($categorie as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>">
                            <?php echo htmlspecialchars($cat['nome']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Descrizione</label>
                    <textarea name="descrizione" 
                              class="form-control" 
                              placeholder="Descrivi il piatto..."></textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label">Ingredienti</label>
                    <textarea name="ingredienti" 
                              class="form-control" 
                              placeholder="Elenca gli ingredienti..."></textarea>
                </div>
            </div>
        </div>

        <!-- PREZZI -->
        <div class="form-section">
            <div class="section-header">
                <i class="bi bi-currency-euro"></i>
                <span>Prezzi</span>
            </div>
            <div class="section-body">
                <div class="prezzi-grid">
                    <div>
                        <label class="form-label">Prezzo Normale *</label>
                        <input type="number" 
                               name="prezzo" 
                               class="form-control" 
                               step="0.01" 
                               min="0" 
                               placeholder="0.00" 
                               required>
                    </div>
                    <div>
                        <label class="form-label">Prezzo Scontato</label>
                        <input type="number" 
                               name="prezzo_scontato" 
                               class="form-control" 
                               step="0.01" 
                               min="0" 
                               placeholder="0.00">
                    </div>
                </div>
            </div>
        </div>

        <!-- CARATTERISTICHE -->
        <?php if (!empty($caratteristiche)): ?>
        <div class="form-section">
            <div class="section-header">
                <i class="bi bi-star-fill"></i>
                <span>Caratteristiche</span>
            </div>
            <div class="section-body">
                <div class="badges-grid">
                    <?php foreach ($caratteristiche as $car): ?>
                    <div class="badge-checkbox">
                        <input type="checkbox" 
                               name="caratteristiche[]" 
                               value="<?php echo $car['id']; ?>" 
                               id="car_<?php echo $car['id']; ?>">
                        <label for="car_<?php echo $car['id']; ?>" 
                               style="background-color: <?php echo htmlspecialchars($car['colore']); ?>20; 
                                      border-color: <?php echo htmlspecialchars($car['colore']); ?>;">
                            <?php if ($car['icona']): ?>
                            <span><?php echo $car['icona']; ?></span>
                            <?php endif; ?>
                            <span><?php echo htmlspecialchars($car['nome']); ?></span>
                        </label>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- ALLERGENI -->
        <?php if (!empty($allergeni)): ?>
        <div class="form-section">
            <div class="section-header">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <span>Allergeni</span>
            </div>
            <div class="section-body">
                <div class="allergeni-grid badges-grid">
                    <?php foreach ($allergeni as $all): ?>
                    <div class="badge-checkbox allergene-checkbox">
                        <input type="checkbox" 
                               name="allergeni[]" 
                               value="<?php echo $all['id']; ?>" 
                               id="all_<?php echo $all['id']; ?>">
                        <label for="all_<?php echo $all['id']; ?>" 
                               title="<?php echo htmlspecialchars($all['nome_it']); ?>">
                            <?php echo htmlspecialchars($all['codice']); ?>
                        </label>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- DISPONIBILITÀ -->
        <div class="form-section">
            <div class="section-header">
                <i class="bi bi-check-circle-fill"></i>
                <span>Stato</span>
            </div>
            <div class="section-body">
                <div class="disponibilita-toggle">
                    <div>
                        <strong>Disponibile</strong>
                        <br>
                        <small class="text-muted">Il piatto sarà visibile nel menu</small>
                    </div>
                    <label class="switch">
                        <input type="checkbox" name="disponibile" checked>
                        <span class="slider"></span>
                    </label>
                </div>
            </div>
        </div>
    </form>

    <!-- BOTTOM BAR -->
    <div class="bottom-bar">
        <button type="button" class="btn-save" id="saveBtn" onclick="salvaPiatto()">
            <span class="spinner spinner-border spinner-border-sm"></span>
            <span class="btn-text">
                <i class="bi bi-check-circle-fill"></i> Salva Piatto
            </span>
        </button>
    </div>

    <!-- TOAST CONTAINER -->
    <div class="toast-container"></div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const BASE_URL = '<?php echo BASE_URL; ?>';
        
        // Preview immagine
        document.getElementById('imageInput').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(event) {
                    const img = document.getElementById('previewImage');
                    img.src = event.target.result;
                    img.classList.add('show');
                    
                    document.querySelector('.camera-content').style.display = 'none';
                    document.getElementById('removeImageBtn').classList.add('show');
                    document.querySelector('.camera-container').classList.add('has-image');
                };
                reader.readAsDataURL(file);
            }
        });
        
        // Rimuovi immagine
        document.getElementById('removeImageBtn').addEventListener('click', function(e) {
            e.stopPropagation();
            
            document.getElementById('imageInput').value = '';
            document.getElementById('previewImage').src = '';
            document.getElementById('previewImage').classList.remove('show');
            document.querySelector('.camera-content').style.display = 'block';
            this.classList.remove('show');
            document.querySelector('.camera-container').classList.remove('has-image');
        });
        
        // Salva piatto
        async function salvaPiatto() {
            const form = document.getElementById('piattoForm');
            const saveBtn = document.getElementById('saveBtn');
            const spinner = saveBtn.querySelector('.spinner');
            const btnText = saveBtn.querySelector('.btn-text');
            
            // Validazione
            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }
            
            // Loading
            saveBtn.disabled = true;
            spinner.classList.add('show');
            btnText.style.opacity = '0.7';
            
            try {
                const formData = new FormData(form);
                
                const response = await fetch(BASE_URL + '/api/piatti/create-mobile.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showToast('Piatto salvato con successo!', 'success');
                    
                    // Reset form dopo 1 secondo
                    setTimeout(() => {
                        form.reset();
                        document.getElementById('removeImageBtn').click();
                        window.scrollTo(0, 0);
                    }, 1000);
                } else {
                    throw new Error(result.message || 'Errore durante il salvataggio');
                }
                
            } catch (error) {
                console.error('Errore:', error);
                showToast('Errore: ' + error.message, 'error');
            } finally {
                saveBtn.disabled = false;
                spinner.classList.remove('show');
                btnText.style.opacity = '1';
            }
        }
        
        // Toast notification
        function showToast(message, type = 'success') {
            const container = document.querySelector('.toast-container');
            const icon = type === 'success' ? 'check-circle-fill' : 'exclamation-circle-fill';
            
            const toast = document.createElement('div');
            toast.className = `custom-toast ${type}`;
            toast.innerHTML = `
                <i class="bi bi-${icon}"></i>
                <div>${message}</div>
            `;
            
            container.appendChild(toast);
            
            setTimeout(() => {
                toast.style.animation = 'slideIn 0.3s ease reverse';
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }
        
        // Previeni zoom su input focus (iOS)
        document.querySelectorAll('input, textarea, select').forEach(element => {
            element.addEventListener('focus', function() {
                document.querySelector('meta[name="viewport"]').setAttribute('content', 
                    'width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no');
            });
            
            element.addEventListener('blur', function() {
                document.querySelector('meta[name="viewport"]').setAttribute('content', 
                    'width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no');
            });
        });
    </script>
</body>
</html>