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

$locali = $localeModel->getByUserId($user['id']);

if (empty($locali)) {
    Helpers::redirect(BASE_URL . '/dashboard/locali/create.php');
}

$localeId = intval($_GET['locale'] ?? 0);
$locale = null;

if ($localeId) {
    $locale = $localeModel->getById($localeId);
    if (!$locale || $locale['user_id'] != $user['id']) {
        Helpers::redirect(BASE_URL . '/dashboard/');
    }
}

if (!$locale && !empty($locali)) {
    $locale = $locali[0];
    $localeId = $locale['id'];
}

$menuList = $menuModel->getByLocaleId($localeId);

$modelli = [
    'classico' => [
        'nome' => 'Classico',
        'descrizione' => 'Design tradizionale con elenco semplice',
        'icona' => 'bi-list-ul',
        'preview' => 'Testo nero su bianco, font serif elegante'
    ],
    'moderno' => [
        'nome' => 'Moderno',
        'descrizione' => 'Layout moderno con card e colori',
        'icona' => 'bi-grid',
        'preview' => 'Card colorate, font sans-serif pulito'
    ],
    'elegante' => [
        'nome' => 'Elegante',
        'descrizione' => 'Stile raffinato per ristoranti gourmet',
        'icona' => 'bi-star',
        'preview' => 'Font decorativi, bordi dorati'
    ],
    'minimalista' => [
        'nome' => 'Minimalista',
        'descrizione' => 'Essenziale e pulito, massima leggibilità',
        'icona' => 'bi-dash-circle',
        'preview' => 'Ampi spazi bianchi, tipografia pulita'
    ],
    'colorato' => [
        'nome' => 'Colorato',
        'descrizione' => 'Vivace e allegro, ideale per pizzerie',
        'icona' => 'bi-palette',
        'preview' => 'Colori vivaci, icone illustrate'
    ],
    'vintage' => [
        'nome' => 'Vintage',
        'descrizione' => 'Stile retrò anni \'50',
        'icona' => 'bi-clock-history',
        'preview' => 'Colori seppia, font retrò, bordi decorativi'
    ],
    'bistrot' => [
        'nome' => 'Bistrot Francese',
        'descrizione' => 'Stile tipico bistrot parigino',
        'icona' => 'bi-cup-hot',
        'preview' => 'Rosso e nero, font corsivo, atmosfera parigina'
    ],
    'rustico' => [
        'nome' => 'Rustico',
        'descrizione' => 'Caldo e accogliente, effetto legno',
        'icona' => 'bi-tree',
        'preview' => 'Toni marroni, font artigianale'
    ],
    'sushi' => [
        'nome' => 'Sushi Bar',
        'descrizione' => 'Stile giapponese zen',
        'icona' => 'bi-moon',
        'preview' => 'Nero rosso oro, font orientale'
    ],
    'street' => [
        'nome' => 'Street Food',
        'descrizione' => 'Urban e dinamico',
        'icona' => 'bi-truck',
        'preview' => 'Nero giallo, font audace, stile urbano'
    ],
    'vegano' => [
        'nome' => 'Green & Vegan',
        'descrizione' => 'Naturale ed eco-friendly',
        'icona' => 'bi-leaf',
        'preview' => 'Verde natura, font organico'
    ],
    'lusso' => [
        'nome' => 'Luxury Fine Dining',
        'descrizione' => 'Massimo lusso ed esclusività',
        'icona' => 'bi-gem',
        'preview' => 'Nero oro argento, font premium'
    ]
];

$pageTitle = 'Stampa Menu Cartaceo';
include __DIR__ . '/../includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-4">
        <label for="localeSelect" class="form-label">Seleziona Locale</label>
        <select class="form-select" id="localeSelect" onchange="window.location.href='?locale='+this.value">
            <?php foreach ($locali as $loc): ?>
                <option value="<?php echo $loc['id']; ?>" <?php echo $loc['id'] == $localeId ? 'selected' : ''; ?>>
                    <?php echo $loc['nome']; ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
</div>

<?php if (empty($menuList)): ?>
    <div class="alert alert-warning">
        <i class="bi bi-exclamation-triangle me-2"></i>
        Nessun menu disponibile per questo locale. <a href="../menu/?locale=<?php echo $localeId; ?>">Crea un menu</a>
    </div>
<?php else: ?>
    <div class="row mb-4">
        <div class="col-md-6">
            <label for="menuSelect" class="form-label">Seleziona Menu da Stampare</label>
            <select class="form-select" id="menuSelect" required>
                <option value="">Seleziona un menu...</option>
                <?php foreach ($menuList as $menu): ?>
                    <option value="<?php echo $menu['id']; ?>"><?php echo $menu['nome']; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <h5 class="mb-3">Scegli il Modello di Stampa</h5>
    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4 mb-4">
        <?php foreach ($modelli as $key => $modello): ?>
            <div class="col">
                <div class="card h-100 modello-card" data-modello="<?php echo $key; ?>">
                    <div class="card-body text-center">
                        <i class="bi <?php echo $modello['icona']; ?> display-3 text-primary mb-3"></i>
                        <h5 class="card-title"><?php echo $modello['nome']; ?></h5>
                        <p class="card-text text-muted"><?php echo $modello['descrizione']; ?></p>
                        <small class="text-muted"><?php echo $modello['preview']; ?></small>
                    </div>
                    <div class="card-footer bg-white">
                        <button type="button" class="btn btn-outline-primary w-100 seleziona-modello">
                            <i class="bi bi-check-circle me-2"></i>Seleziona
                        </button>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="card">
        <div class="card-body">
            <h5 class="card-title">Opzioni di Stampa</h5>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="mostraPrezzi" checked>
                        <label class="form-check-label" for="mostraPrezzi">
                            Mostra prezzi
                        </label>
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="mostraAllergeni" checked>
                        <label class="form-check-label" for="mostraAllergeni">
                            Mostra allergeni
                        </label>
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="mostraDescrizioni" checked>
                        <label class="form-check-label" for="mostraDescrizioni">
                            Mostra descrizioni piatti
                        </label>
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="mostraIngredienti">
                        <label class="form-check-label" for="mostraIngredienti">
                            Mostra ingredienti
                        </label>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<style>
.modello-card {
    cursor: pointer;
    transition: all 0.3s;
}
.modello-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}
.modello-card.selected {
    border-color: var(--bs-primary);
    box-shadow: 0 0 0 3px rgba(13,110,253,0.25);
}
</style>

<script>
let menuSelezionato = null;
let modelloSelezionato = null;

document.getElementById('menuSelect')?.addEventListener('change', function() {
    menuSelezionato = this.value;
});

document.querySelectorAll('.seleziona-modello').forEach(btn => {
    btn.addEventListener('click', function() {
        const card = this.closest('.modello-card');
        modelloSelezionato = card.dataset.modello;
        
        if (!menuSelezionato) {
            alert('Seleziona prima un menu da stampare');
            return;
        }
        
        document.querySelectorAll('.modello-card').forEach(c => c.classList.remove('selected'));
        card.classList.add('selected');
        
        const mostraPrezzi = document.getElementById('mostraPrezzi').checked ? 1 : 0;
        const mostraAllergeni = document.getElementById('mostraAllergeni').checked ? 1 : 0;
        const mostraDescrizioni = document.getElementById('mostraDescrizioni').checked ? 1 : 0;
        const mostraIngredienti = document.getElementById('mostraIngredienti').checked ? 1 : 0;
        
        const url = 'stampa.php?menu=' + menuSelezionato + 
                    '&modello=' + modelloSelezionato +
                    '&prezzi=' + mostraPrezzi +
                    '&allergeni=' + mostraAllergeni +
                    '&descrizioni=' + mostraDescrizioni +
                    '&ingredienti=' + mostraIngredienti;
        
        window.open(url, '_blank');
    });
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>