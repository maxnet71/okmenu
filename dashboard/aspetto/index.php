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

$menuId = intval($_GET['menu'] ?? 0);

if (!$menuId) {
    Helpers::redirect(BASE_URL . '/dashboard/menu/');
}

$menu = $menuModel->getById($menuId);
if (!$menu) {
    Helpers::redirect(BASE_URL . '/dashboard/menu/');
}

$locale = $localeModel->getById($menu['locale_id']);
if (!$locale || $locale['user_id'] != $user['id']) {
    Helpers::redirect(BASE_URL . '/dashboard/menu/');
}

$db = Database::getInstance()->getConnection();
$stmt = $db->prepare("SELECT * FROM menu_temi WHERE menu_id = ?");
$stmt->execute([$menuId]);
$tema = $stmt->fetch();

if (!$tema) {
    $tema = [
        'tema_preset' => 'moderno',
        'colore_primario' => '#007bff',
        'colore_secondario' => '#6c757d',
        'colore_sfondo' => '#ffffff',
        'colore_testo' => '#333333',
        'font_titoli' => 'Poppins',
        'font_testo' => 'Open Sans',
        'stile_layout' => 'card',
        'mostra_immagini' => 1,
        'mostra_descrizioni' => 1,
        'mostra_allergeni' => 1
    ];
}

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'menu_id' => $menuId,
        'tema_preset' => $_POST['tema_preset'] ?? 'moderno',
        'colore_primario' => $_POST['colore_primario'] ?? '#007bff',
        'colore_secondario' => $_POST['colore_secondario'] ?? '#6c757d',
        'colore_sfondo' => $_POST['colore_sfondo'] ?? '#ffffff',
        'colore_testo' => $_POST['colore_testo'] ?? '#333333',
        'font_titoli' => $_POST['font_titoli'] ?? 'Poppins',
        'font_testo' => $_POST['font_testo'] ?? 'Open Sans',
        'stile_layout' => $_POST['stile_layout'] ?? 'card',
        'mostra_immagini' => isset($_POST['mostra_immagini']) ? 1 : 0,
        'mostra_descrizioni' => isset($_POST['mostra_descrizioni']) ? 1 : 0,
        'mostra_allergeni' => isset($_POST['mostra_allergeni']) ? 1 : 0
    ];
    
    if ($tema && isset($tema['id'])) {
        $set = [];
        $params = [];
        foreach ($data as $key => $value) {
            if ($key !== 'menu_id') {
                $set[] = "$key = ?";
                $params[] = $value;
            }
        }
        $params[] = $tema['id'];
        $sql = "UPDATE menu_temi SET " . implode(', ', $set) . " WHERE id = ?";
        $stmt = $db->prepare($sql);
        if ($stmt->execute($params)) {
            $success = 'Aspetto aggiornato con successo';
        }
    } else {
        $fields = array_keys($data);
        $placeholders = array_fill(0, count($data), '?');
        $sql = "INSERT INTO menu_temi (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $placeholders) . ")";
        $stmt = $db->prepare($sql);
        if ($stmt->execute(array_values($data))) {
            $success = 'Aspetto salvato con successo';
        }
    }
    
    $stmt = $db->prepare("SELECT * FROM menu_temi WHERE menu_id = ?");
    $stmt->execute([$menuId]);
    $tema = $stmt->fetch();
}

$temiPreset = [
    'moderno' => ['nome' => 'Moderno', 'primario' => '#007bff', 'secondario' => '#6c757d', 'sfondo' => '#ffffff', 'testo' => '#333333'],
    'scuro' => ['nome' => 'Scuro', 'primario' => '#00d4ff', 'secondario' => '#666666', 'sfondo' => '#1a1a1a', 'testo' => '#ffffff'],
    'elegante' => ['nome' => 'Elegante', 'primario' => '#d4af37', 'secondario' => '#8b7355', 'sfondo' => '#fffef7', 'testo' => '#2c2c2c'],
    'natura' => ['nome' => 'Natura', 'primario' => '#4caf50', 'secondario' => '#8bc34a', 'sfondo' => '#f1f8e9', 'testo' => '#2e7d32'],
    'oceano' => ['nome' => 'Oceano', 'primario' => '#00bcd4', 'secondario' => '#0097a7', 'sfondo' => '#e0f7fa', 'testo' => '#006064'],
    'tramonto' => ['nome' => 'Tramonto', 'primario' => '#ff6b35', 'secondario' => '#f7931e', 'sfondo' => '#fff3e0', 'testo' => '#e65100']
];

$fonts = [
    'Poppins' => 'Poppins',
    'Open Sans' => 'Open Sans',
    'Roboto' => 'Roboto',
    'Playfair Display' => 'Playfair Display',
    'Lora' => 'Lora',
    'Montserrat' => 'Montserrat',
    'Raleway' => 'Raleway',
    'Merriweather' => 'Merriweather'
];

$pageTitle = 'Personalizza Aspetto Menu';
include __DIR__ . '/../includes/header.php';
?>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&family=Open+Sans&family=Roboto&family=Playfair+Display&family=Lora&family=Montserrat&family=Raleway&family=Merriweather&display=swap" rel="stylesheet">

<div class="row mb-4">
    <div class="col">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../menu/?locale=<?php echo $locale['id']; ?>">Menu</a></li>
                <li class="breadcrumb-item active"><?php echo $menu['nome']; ?> - Aspetto</li>
            </ol>
        </nav>
    </div>
</div>

<?php if ($success): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="bi bi-check-circle me-2"></i><?php echo $success; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<form method="POST" id="temaForm">
    <div class="row">
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Tema Predefinito</h5>
                </div>
                <div class="card-body">
                    <div class="row row-cols-2 row-cols-md-3 g-3">
                        <?php foreach ($temiPreset as $key => $preset): ?>
                            <div class="col">
                                <div class="tema-preset-card" data-tema="<?php echo $key; ?>" 
                                     data-primario="<?php echo $preset['primario']; ?>"
                                     data-secondario="<?php echo $preset['secondario']; ?>"
                                     data-sfondo="<?php echo $preset['sfondo']; ?>"
                                     data-testo="<?php echo $preset['testo']; ?>">
                                    <input type="radio" name="tema_preset" value="<?php echo $key; ?>" 
                                           id="tema_<?php echo $key; ?>" 
                                           <?php echo ($tema['tema_preset'] ?? 'moderno') == $key ? 'checked' : ''; ?>>
                                    <label for="tema_<?php echo $key; ?>">
                                        <div class="preview-box" style="background: <?php echo $preset['sfondo']; ?>; border: 2px solid <?php echo $preset['primario']; ?>;">
                                            <div style="background: <?php echo $preset['primario']; ?>; height: 30px; margin-bottom: 5px;"></div>
                                            <div style="background: <?php echo $preset['secondario']; ?>; height: 15px;"></div>
                                        </div>
                                        <strong><?php echo $preset['nome']; ?></strong>
                                    </label>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Colori Personalizzati</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Colore Primario</label>
                            <input type="color" class="form-control form-control-color" 
                                   name="colore_primario" id="colore_primario" 
                                   value="<?php echo $tema['colore_primario']; ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Colore Secondario</label>
                            <input type="color" class="form-control form-control-color" 
                                   name="colore_secondario" id="colore_secondario" 
                                   value="<?php echo $tema['colore_secondario']; ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Colore Sfondo</label>
                            <input type="color" class="form-control form-control-color" 
                                   name="colore_sfondo" id="colore_sfondo" 
                                   value="<?php echo $tema['colore_sfondo']; ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Colore Testo</label>
                            <input type="color" class="form-control form-control-color" 
                                   name="colore_testo" id="colore_testo" 
                                   value="<?php echo $tema['colore_testo']; ?>">
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Tipografia</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Font Titoli</label>
                            <select class="form-select" name="font_titoli" id="font_titoli">
                                <?php foreach ($fonts as $key => $font): ?>
                                    <option value="<?php echo $key; ?>" 
                                            style="font-family: '<?php echo $font; ?>'"
                                            <?php echo ($tema['font_titoli'] ?? 'Poppins') == $key ? 'selected' : ''; ?>>
                                        <?php echo $font; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Font Testo</label>
                            <select class="form-select" name="font_testo" id="font_testo">
                                <?php foreach ($fonts as $key => $font): ?>
                                    <option value="<?php echo $key; ?>" 
                                            style="font-family: '<?php echo $font; ?>'"
                                            <?php echo ($tema['font_testo'] ?? 'Open Sans') == $key ? 'selected' : ''; ?>>
                                        <?php echo $font; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Layout e Visualizzazione</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Stile Layout</label>
                        <select class="form-select" name="stile_layout" id="stile_layout">
                            <option value="card" <?php echo ($tema['stile_layout'] ?? 'card') == 'card' ? 'selected' : ''; ?>>Card Moderne</option>
                            <option value="lista" <?php echo ($tema['stile_layout'] ?? '') == 'lista' ? 'selected' : ''; ?>>Lista Classica</option>
                            <option value="grid" <?php echo ($tema['stile_layout'] ?? '') == 'grid' ? 'selected' : ''; ?>>Griglia</option>
                        </select>
                    </div>
                    
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" name="mostra_immagini" id="mostra_immagini" 
                               <?php echo ($tema['mostra_immagini'] ?? 1) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="mostra_immagini">
                            Mostra immagini piatti
                        </label>
                    </div>
                    
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" name="mostra_descrizioni" id="mostra_descrizioni" 
                               <?php echo ($tema['mostra_descrizioni'] ?? 1) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="mostra_descrizioni">
                            Mostra descrizioni piatti
                        </label>
                    </div>
                    
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="mostra_allergeni" id="mostra_allergeni" 
                               <?php echo ($tema['mostra_allergeni'] ?? 1) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="mostra_allergeni">
                            Mostra allergeni
                        </label>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-lg">
                <i class="bi bi-check-circle me-2"></i>Salva Modifiche
            </button>
        </div>

        <div class="col-lg-4">
            <div class="card sticky-top" style="top: 20px;">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Anteprima</h5>
                </div>
                <div class="card-body p-0">
                    <div id="preview" style="min-height: 400px;">
                        <!-- Anteprima dinamica -->
                    </div>
                </div>
                <div class="card-footer bg-white">
                    <a href="<?php echo BASE_URL; ?>/menu/<?php echo $locale['slug']; ?>?menu=<?php echo $menuId; ?>" target="_blank" class="btn btn-outline-primary w-100">
                        <i class="bi bi-eye me-2"></i>Visualizza Menu Pubblico
                    </a>
                </div>
            </div>
        </div>
    </div>
</form>

<style>
.tema-preset-card {
    cursor: pointer;
    text-align: center;
}
.tema-preset-card input[type="radio"] {
    display: none;
}
.tema-preset-card label {
    cursor: pointer;
    display: block;
    padding: 10px;
    border: 2px solid #ddd;
    border-radius: 8px;
    transition: all 0.3s;
}
.tema-preset-card input[type="radio"]:checked + label {
    border-color: #007bff;
    box-shadow: 0 0 0 3px rgba(0,123,255,0.25);
}
.preview-box {
    height: 80px;
    border-radius: 5px;
    padding: 10px;
    margin-bottom: 10px;
}
</style>

<script>
function aggiornaAnteprima() {
    const primario = document.getElementById('colore_primario').value;
    const secondario = document.getElementById('colore_secondario').value;
    const sfondo = document.getElementById('colore_sfondo').value;
    const testo = document.getElementById('colore_testo').value;
    const fontTitoli = document.getElementById('font_titoli').value;
    const fontTesto = document.getElementById('font_testo').value;
    const layout = document.getElementById('stile_layout').value;
    
    const preview = document.getElementById('preview');
    preview.style.background = sfondo;
    preview.style.color = testo;
    preview.style.fontFamily = fontTesto;
    
    let html = `
        <div style="padding: 20px;">
            <h3 style="color: ${primario}; font-family: '${fontTitoli}'; margin-bottom: 15px;">Antipasti</h3>
            ${layout === 'card' ? `
                <div style="background: white; padding: 15px; border-radius: 8px; margin-bottom: 10px; border-left: 4px solid ${primario};">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <strong style="color: ${testo};">Bruschetta</strong>
                        <span style="color: ${primario}; font-weight: bold;">€8.00</span>
                    </div>
                    <p style="font-size: 0.9em; color: ${secondario}; margin: 5px 0 0 0;">Pomodoro fresco, basilico</p>
                </div>
            ` : `
                <div style="padding: 10px 0; border-bottom: 1px solid #eee;">
                    <div style="display: flex; justify-content: space-between;">
                        <strong>Bruschetta</strong>
                        <span style="color: ${primario};">€8.00</span>
                    </div>
                    <p style="font-size: 0.9em; color: ${secondario}; margin: 5px 0 0 0;">Pomodoro fresco, basilico</p>
                </div>
            `}
        </div>
    `;
    
    preview.innerHTML = html;
}

document.querySelectorAll('input[type="color"], select').forEach(el => {
    el.addEventListener('change', aggiornaAnteprima);
});

document.querySelectorAll('.tema-preset-card').forEach(card => {
    card.addEventListener('click', function() {
        const radio = this.querySelector('input[type="radio"]');
        radio.checked = true;
        
        document.getElementById('colore_primario').value = this.dataset.primario;
        document.getElementById('colore_secondario').value = this.dataset.secondario;
        document.getElementById('colore_sfondo').value = this.dataset.sfondo;
        document.getElementById('colore_testo').value = this.dataset.testo;
        
        aggiornaAnteprima();
    });
});

aggiornaAnteprima();
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>