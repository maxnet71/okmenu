<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/classes/Database.php';
require_once __DIR__ . '/classes/Model.php';
require_once __DIR__ . '/classes/Helpers.php';
require_once __DIR__ . '/classes/models/User.php';

Helpers::requireLogin();

$sessionId = $_GET['session'] ?? '';
$sessionKey = 'menu_preview_' . $sessionId;

if (!isset($_SESSION[$sessionKey])) {
    $_SESSION['flash_message'] = 'Sessione non valida o scaduta';
    $_SESSION['flash_type'] = 'danger';
    header('Location: ' . BASE_URL . '/upload-menu-multi.php');
    exit;
}

$sessionData = $_SESSION[$sessionKey];
$menuData = $sessionData['menu_data'];
$localeId = $sessionData['locale_id'];

$pageTitle = 'Preview Menu - Verifica e Modifica';
require_once __DIR__ . '/dashboard/includes/header.php';
?>

<style>
.editable-cell {
    min-height: 35px;
    padding: 8px;
    border: 1px solid transparent;
    cursor: text;
}
.editable-cell:hover {
    background: #f8f9fa;
    border-color: #dee2e6;
}
.editable-cell:focus {
    outline: 2px solid #0d6efd;
    background: #fff;
}
.category-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 15px;
    border-radius: 8px;
    margin-top: 20px;
}
.dish-row {
    transition: all 0.2s;
}
.dish-row:hover {
    background: #f8f9fa;
}
.btn-sm {
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
}
</style>

<div class="row">
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-0"><i class="bi bi-eye"></i> Preview Menu</h5>
                    <small>Verifica e modifica i dati estratti dall'AI</small>
                </div>
                <div>
                    <button type="button" class="btn btn-light btn-sm me-2" onclick="window.history.back()">
                        <i class="bi bi-arrow-left"></i> Indietro
                    </button>
                    <button type="button" class="btn btn-warning btn-sm" id="btnSave">
                        <i class="bi bi-save"></i> Salva Menu
                    </button>
                </div>
            </div>
            <div class="card-body">
                <!-- Info Menu -->
                <div class="mb-4">
                    <label class="form-label fw-bold">Nome Menu</label>
                    <input type="text" class="form-control form-control-lg" id="menuNome" 
                           value="<?php echo htmlspecialchars($menuData['menu']['nome']); ?>">
                    
                    <label class="form-label fw-bold mt-3">Descrizione Menu (opzionale)</label>
                    <textarea class="form-control" id="menuDescrizione" rows="2"><?php echo htmlspecialchars($menuData['menu']['descrizione'] ?? ''); ?></textarea>
                </div>

                <hr>

                <!-- Statistiche -->
                <div class="alert alert-info">
                    <strong>Estratti:</strong> 
                    <?php echo count($menuData['categorie']); ?> categorie, 
                    <?php 
                    $totalPiatti = 0;
                    foreach ($menuData['categorie'] as $cat) {
                        $totalPiatti += count($cat['piatti']);
                    }
                    echo $totalPiatti;
                    ?> piatti
                </div>

                <!-- Toolbar -->
                <div class="mb-3">
                    <button type="button" class="btn btn-primary btn-sm" onclick="addCategory()">
                        <i class="bi bi-plus-circle"></i> Aggiungi Categoria
                    </button>
                    <button type="button" class="btn btn-secondary btn-sm" onclick="mergeDuplicates()">
                        <i class="bi bi-diagram-3"></i> Unisci Duplicati
                    </button>
                </div>

                <!-- Categorie e Piatti -->
                <div id="categoriesContainer"></div>
            </div>
        </div>
    </div>
</div>

<script>
let menuData = <?php echo json_encode($menuData); ?>;

function renderMenu() {
    const container = document.getElementById('categoriesContainer');
    container.innerHTML = '';
    
    menuData.categorie.forEach((categoria, catIndex) => {
        const catDiv = document.createElement('div');
        catDiv.className = 'mb-4';
        catDiv.innerHTML = `
            <div class="category-header d-flex justify-content-between align-items-center">
                <div class="flex-grow-1">
                    <input type="text" class="form-control form-control-sm bg-white border-0" 
                           value="${categoria.nome}" 
                           onchange="updateCategoryName(${catIndex}, this.value)"
                           style="display:inline-block; width:auto; min-width:200px;">
                    <small class="d-block mt-1 opacity-75">${categoria.piatti.length} piatti</small>
                </div>
                <div>
                    <button class="btn btn-sm btn-light" onclick="addDish(${catIndex})">
                        <i class="bi bi-plus"></i> Piatto
                    </button>
                    <button class="btn btn-sm btn-danger" onclick="removeCategory(${catIndex})">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </div>
            
            <div class="table-responsive mt-2">
                <table class="table table-hover table-sm">
                    <thead>
                        <tr>
                            <th width="40%">Nome Piatto</th>
                            <th width="30%">Descrizione</th>
                            <th width="20%">Prezzo (€)</th>
                            <th width="10%">Azioni</th>
                        </tr>
                    </thead>
                    <tbody id="dishes-${catIndex}">
                        ${categoria.piatti.map((piatto, dishIndex) => renderDish(catIndex, dishIndex, piatto)).join('')}
                    </tbody>
                </table>
            </div>
        `;
        container.appendChild(catDiv);
    });
}

function renderDish(catIndex, dishIndex, piatto) {
    return `
        <tr class="dish-row">
            <td>
                <div class="editable-cell" contenteditable="true" 
                     onblur="updateDish(${catIndex}, ${dishIndex}, 'nome', this.textContent)">
                    ${piatto.nome}
                </div>
            </td>
            <td>
                <div class="editable-cell" contenteditable="true" 
                     onblur="updateDish(${catIndex}, ${dishIndex}, 'descrizione', this.textContent)">
                    ${piatto.descrizione || ''}
                </div>
            </td>
            <td>
                <input type="number" class="form-control form-control-sm" 
                       value="${piatto.prezzo || 0}" step="0.01" min="0"
                       onchange="updateDish(${catIndex}, ${dishIndex}, 'prezzo', this.value)">
            </td>
            <td>
                <button class="btn btn-sm btn-danger" onclick="removeDish(${catIndex}, ${dishIndex})">
                    <i class="bi bi-trash"></i>
                </button>
            </td>
        </tr>
    `;
}

function updateCategoryName(catIndex, newName) {
    menuData.categorie[catIndex].nome = newName.trim();
}

function updateDish(catIndex, dishIndex, field, value) {
    if (field === 'prezzo') {
        menuData.categorie[catIndex].piatti[dishIndex][field] = parseFloat(value) || 0;
    } else {
        menuData.categorie[catIndex].piatti[dishIndex][field] = value.trim();
    }
}

function addCategory() {
    const nome = prompt('Nome nuova categoria:');
    if (!nome) return;
    
    menuData.categorie.push({
        nome: nome,
        descrizione: null,
        piatti: []
    });
    renderMenu();
}

function removeCategory(catIndex) {
    if (!confirm('Eliminare questa categoria e tutti i suoi piatti?')) return;
    menuData.categorie.splice(catIndex, 1);
    renderMenu();
}

function addDish(catIndex) {
    const nome = prompt('Nome nuovo piatto:');
    if (!nome) return;
    
    menuData.categorie[catIndex].piatti.push({
        nome: nome,
        descrizione: '',
        prezzo: 0,
        ingredienti: '',
        allergeni: [],
        caratteristiche: []
    });
    renderMenu();
}

function removeDish(catIndex, dishIndex) {
    if (!confirm('Eliminare questo piatto?')) return;
    menuData.categorie[catIndex].piatti.splice(dishIndex, 1);
    renderMenu();
}

function mergeDuplicates() {
    const categories = {};
    
    menuData.categorie.forEach(cat => {
        const key = cat.nome.toLowerCase().trim();
        if (!categories[key]) {
            categories[key] = cat;
        } else {
            categories[key].piatti.push(...cat.piatti);
        }
    });
    
    menuData.categorie = Object.values(categories);
    renderMenu();
    alert('Categorie duplicate unite!');
}

document.getElementById('btnSave').addEventListener('click', async function() {
    if (!confirm('Salvare il menu nel database?')) return;
    
    const btn = this;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Salvataggio...';
    
    try {
        menuData.menu.nome = document.getElementById('menuNome').value;
        menuData.menu.descrizione = document.getElementById('menuDescrizione').value;
        
        const response = await fetch('<?php echo BASE_URL; ?>/save-menu-final.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                session_id: '<?php echo $sessionId; ?>',
                menu_data: menuData
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            // Se è onboarding, vai a creare password
            if (result.is_onboarding) {
                window.location.href = '<?php echo BASE_URL; ?>/onboarding/create-password.php';
            } else {
                // Altrimenti dashboard normale
                alert('Menu salvato con successo!');
                window.location.href = '<?php echo BASE_URL; ?>/dashboard/menu/?locale=<?php echo $localeId; ?>';
            }
        } else {
            throw new Error(result.message || 'Errore durante il salvataggio');
        }
    } catch (error) {
        alert('Errore: ' + error.message);
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-save"></i> Salva Menu';
    }
});

renderMenu();
</script>

<?php require_once __DIR__ . '/dashboard/includes/footer.php'; ?>