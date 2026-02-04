<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/Model.php';
require_once __DIR__ . '/../classes/models/LocaleRestaurant.php';

// Gestisci sia onboarding che utenti loggati
$isOnboarding = isset($_SESSION['onboarding_user_id']);
$isLoggedIn = isset($_SESSION['user_id']);

if (!$isOnboarding && !$isLoggedIn) {
    header('Location: ' . BASE_URL . '/signup.php');
    exit;
}

$userId = $isOnboarding ? $_SESSION['onboarding_user_id'] : $_SESSION['user_id'];

$db = Database::getInstance()->getConnection();
$localeModel = new LocaleRestaurant();
$locali = $localeModel->getByUserId($userId);
$locale = $locali[0] ?? null;

if (!$locale) {
    header('Location: ' . BASE_URL . '/signup.php');
    exit;
}

$localeId = $locale['id'];
$pageTitle = 'Crea il Tuo Menu';
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - OkMenu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        .category-card { border-left: 4px solid #667eea; }
        .dish-item { border-left: 3px solid #ddd; transition: all 0.2s; }
        .dish-item:hover { border-left-color: #667eea; background: #f8f9fa; }
        .remove-btn { opacity: 0; transition: opacity 0.2s; }
        .dish-item:hover .remove-btn { opacity: 1; }
    </style>
</head>
<body class="bg-light">

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <!-- Header -->
            <div class="text-center mb-4">
                <h1 class="mb-2"><?php echo $pageTitle; ?></h1>
                <p class="text-muted">Aggiungi categorie e piatti al tuo menu</p>
            </div>

            <!-- Menu Builder -->
            <div class="card shadow-sm mb-4">
                <div class="card-body p-4">
                    <h5 class="mb-4"><i class="bi bi-plus-circle me-2"></i>Nuova Categoria</h5>
                    
                    <form id="categoryForm" class="mb-4">
                        <div class="row g-3">
                            <div class="col-md-8">
                                <input type="text" class="form-control" id="categoryName" 
                                       placeholder="Es: Antipasti, Primi, Secondi..." required>
                            </div>
                            <div class="col-md-4">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="bi bi-plus-lg me-1"></i>Aggiungi Categoria
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Categories List -->
            <div id="categoriesList"></div>

            <!-- Actions -->
            <div class="card shadow-sm">
                <div class="card-body p-4 text-center">
                    <p class="text-muted mb-3">
                        Hai creato <strong id="totalCount">0</strong> piatti in <strong id="catCount">0</strong> categorie
                    </p>
                    <button class="btn btn-success btn-lg" id="btnSave" disabled>
                        <i class="bi bi-check-circle me-2"></i>Salva e Continua
                    </button>
                    <div id="progress" class="mt-3" style="display:none;">
                        <div class="progress"><div class="progress-bar progress-bar-striped progress-bar-animated w-100"></div></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Add Dish -->
<div class="modal fade" id="modalDish" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Aggiungi Piatto</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="dishForm">
                    <input type="hidden" id="dishCategoryId">
                    <div class="mb-3">
                        <label class="form-label">Nome Piatto *</label>
                        <input type="text" class="form-control" id="dishName" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Descrizione</label>
                        <textarea class="form-control" id="dishDesc" rows="2"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Prezzo (€)</label>
                        <input type="number" class="form-control" id="dishPrice" step="0.01" min="0">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                <button type="button" class="btn btn-primary" onclick="saveDish()">Aggiungi</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
let menuData = { categories: [] };
let currentCategoryId = 0;
let currentDishId = 0;

// Add category
document.getElementById('categoryForm').onsubmit = (e) => {
    e.preventDefault();
    const name = document.getElementById('categoryName').value.trim();
    if (!name) return;
    
    const cat = {
        id: ++currentCategoryId,
        name: name,
        dishes: []
    };
    
    menuData.categories.push(cat);
    document.getElementById('categoryName').value = '';
    render();
};

// Show add dish modal
function addDish(catId) {
    document.getElementById('dishCategoryId').value = catId;
    document.getElementById('dishForm').reset();
    new bootstrap.Modal(document.getElementById('modalDish')).show();
}

// Save dish
function saveDish() {
    const catId = parseInt(document.getElementById('dishCategoryId').value);
    const name = document.getElementById('dishName').value.trim();
    
    if (!name) return alert('Nome piatto obbligatorio');
    
    const cat = menuData.categories.find(c => c.id === catId);
    if (!cat) return;
    
    cat.dishes.push({
        id: ++currentDishId,
        name: name,
        description: document.getElementById('dishDesc').value.trim(),
        price: document.getElementById('dishPrice').value || null
    });
    
    bootstrap.Modal.getInstance(document.getElementById('modalDish')).hide();
    render();
}

// Remove category
function removeCat(catId) {
    if (!confirm('Eliminare questa categoria e tutti i suoi piatti?')) return;
    menuData.categories = menuData.categories.filter(c => c.id !== catId);
    render();
}

// Remove dish
function removeDish(catId, dishId) {
    const cat = menuData.categories.find(c => c.id === catId);
    if (!cat) return;
    cat.dishes = cat.dishes.filter(d => d.id !== dishId);
    render();
}

// Render UI
function render() {
    const list = document.getElementById('categoriesList');
    list.innerHTML = '';
    
    let totalDishes = 0;
    
    menuData.categories.forEach(cat => {
        totalDishes += cat.dishes.length;
        
        const card = document.createElement('div');
        card.className = 'card category-card shadow-sm mb-3';
        card.innerHTML = `
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">${cat.name}</h5>
                <div>
                    <button class="btn btn-sm btn-outline-primary" onclick="addDish(${cat.id})">
                        <i class="bi bi-plus-lg"></i> Piatto
                    </button>
                    <button class="btn btn-sm btn-outline-danger" onclick="removeCat(${cat.id})">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                ${cat.dishes.length === 0 ? 
                    '<p class="text-muted mb-0">Nessun piatto. Clicca "+ Piatto" per aggiungerne uno.</p>' :
                    cat.dishes.map(d => `
                        <div class="dish-item p-3 mb-2 rounded position-relative">
                            <div class="d-flex justify-content-between">
                                <div class="flex-grow-1">
                                    <strong>${d.name}</strong>
                                    ${d.description ? `<p class="text-muted mb-0 small mt-1">${d.description}</p>` : ''}
                                </div>
                                <div class="text-end">
                                    ${d.price ? `<strong>€${parseFloat(d.price).toFixed(2)}</strong>` : ''}
                                    <button class="btn btn-sm btn-outline-danger ms-2 remove-btn" onclick="removeDish(${cat.id}, ${d.id})">
                                        <i class="bi bi-x-lg"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    `).join('')
                }
            </div>
        `;
        list.appendChild(card);
    });
    
    document.getElementById('totalCount').textContent = totalDishes;
    document.getElementById('catCount').textContent = menuData.categories.length;
    document.getElementById('btnSave').disabled = totalDishes === 0;
}

// Save menu
document.getElementById('btnSave').onclick = async () => {
    if (menuData.categories.length === 0) return alert('Aggiungi almeno una categoria');
    
    const totalDishes = menuData.categories.reduce((sum, cat) => sum + cat.dishes.length, 0);
    if (totalDishes === 0) return alert('Aggiungi almeno un piatto');
    
    document.getElementById('btnSave').disabled = true;
    document.getElementById('progress').style.display = 'block';
    
    try {
        const res = await fetch('<?php echo BASE_URL; ?>/api/save-manual-menu.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                locale_id: <?php echo $localeId; ?>,
                menu_data: menuData
            })
        });
        
        const data = await res.json();
        
        if (data.success) {
            window.location.href = '<?php echo BASE_URL; ?>/onboarding/create-password.php';
        } else {
            throw new Error(data.message);
        }
    } catch (e) {
        alert('Errore: ' + e.message);
        document.getElementById('btnSave').disabled = false;
        document.getElementById('progress').style.display = 'none';
    }
};

render();
</script>
</body>
</html>