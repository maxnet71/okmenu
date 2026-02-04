<?php
require_once __DIR__ . '/config/config.php';

require_once __DIR__ . '/classes/Database.php';
require_once __DIR__ . '/classes/Model.php';
require_once __DIR__ . '/classes/Helpers.php';
require_once __DIR__ . '/classes/models/LocaleRestaurant.php';
require_once __DIR__ . '/classes/models/Menu.php';
require_once __DIR__ . '/classes/models/Categoria.php';
require_once __DIR__ . '/classes/models/Piatto.php';
require_once __DIR__ . '/classes/models/QRCode.php';

$slug = $_GET['slug'] ?? '';
$codice = $_GET['codice'] ?? '';
$menuParam = intval($_GET['menu'] ?? 0);

// GESTIONE COMPATIBILITÀ LEGACY URL
// Se arriva un parametro che sembra un vecchio link, cerca QR con quel legacy_url
$queryString = $_SERVER['QUERY_STRING'] ?? '';
if ($queryString && !$codice && !$slug) {
    // Costruisci la query completa come legacy_url
    $possibleLegacyUrl = $queryString; // es: "qrIdentity=paradiso" o "id=123"
    
    // Cerca QR che ha questo come legacy_url
    $qrcodeModel = new QRCode();
    $qrFromLegacy = $qrcodeModel->getByLegacyUrl($possibleLegacyUrl);
    
    if ($qrFromLegacy && $qrFromLegacy['attivo']) {
        // Trovato! Redirect al nuovo sistema
        $codice = $qrFromLegacy['codice'];
    }
}

$localeModel = new LocaleRestaurant();
$menuModel = new Menu();
$locale = null;
$menuPreselezionato = null;

if ($codice) {
    $qrcodeModel = new QRCode();
    $qr = $qrcodeModel->getByCode($codice);
    
    if (!$qr || !$qr['attivo']) {
        die('QR Code non valido');
    }
    
    $locale = $localeModel->getById($qr['locale_id']);
    if ($qr['menu_id']) {
        $menuPreselezionato = $menuModel->getById($qr['menu_id']);
    }
} elseif ($slug) {
    $locale = $localeModel->getBySlug($slug);
} else {
    die('Parametri non validi');
}

if (!$locale || !$locale['attivo']) {
    die('Locale non disponibile');
}

$categoriaModel = new Categoria();
$piattoModel = new Piatto();

$menuList = $menuModel->getByLocaleId($locale['id']);
$menuList = array_filter($menuList, function($m) { return $m['pubblicato']; });

if (empty($menuList)) {
    die('Nessun menu disponibile');
}

$menuSelezionato = null;
if ($menuPreselezionato) {
    $menuSelezionato = $menuPreselezionato;
} elseif ($menuParam) {
    foreach ($menuList as $m) {
        if ($m['id'] == $menuParam) {
            $menuSelezionato = $m;
            break;
        }
    }
}

if (!$menuSelezionato) {
    $menuSelezionato = $menuList[0];
}

$db = Database::getInstance()->getConnection();
$stmt = $db->prepare("SELECT * FROM menu_temi WHERE menu_id = ?");
$stmt->execute([$menuSelezionato['id']]);
$tema = $stmt->fetch();

if (!$tema) {
    $tema = [
        'colore_primario' => '#007bff',
        'colore_secondario' => '#6c757d',
        'colore_sfondo' => '#f8f9fa',
        'colore_testo' => '#333333',
        'font_titoli' => 'Poppins',
        'font_testo' => 'Open Sans',
        'mostra_immagini' => 1,
        'mostra_descrizioni' => 1,
        'mostra_allergeni' => 1
    ];
}

$categorie = $categoriaModel->getByMenuId($menuSelezionato['id']);
$categorie = array_filter($categorie, function($c) { return $c['attivo']; });

$categorieConPiatti = [];
foreach ($categorie as $cat) {
    $piatti = $piattoModel->getByCategoriaId($cat['id']);
    $piatti = array_filter($piatti, function($p) { return $p['disponibile']; });
    
    if (!empty($piatti)) {
        foreach ($piatti as &$piatto) {
            $piatto['allergeni'] = $tema['mostra_allergeni'] ? $piattoModel->getAllergeni($piatto['id']) : [];
            $piatto['caratteristiche'] = $piattoModel->getCaratteristiche($piatto['id']);
        }
        
        $categorieConPiatti[] = [
            'categoria' => $cat,
            'piatti' => $piatti
        ];
    }
}

$categorieJSON = json_encode($categorieConPiatti);

// Verifica configurazione ordini
$sql = "SELECT ordini_attivi, ordini_asporto_attivi, ordini_consegna_attivi 
        FROM ordini_configurazioni WHERE locale_id = :locale_id";
$stmt = $db->prepare($sql);
$stmt->execute(['locale_id' => $locale['id']]);
$configOrdini = $stmt->fetch();

$ordiniAttivi = $configOrdini && $configOrdini['ordini_attivi'];

// Verifica se QR ha ordini abilitati
$qrAbilitaOrdini = false;
if ($codice) {
    $sql = "SELECT abilita_ordini FROM qrcode WHERE codice = :codice";
    $stmt = $db->prepare($sql);
    $stmt->execute(['codice' => $codice]);
    $qrData = $stmt->fetch();
    $qrAbilitaOrdini = $qrData && $qrData['abilita_ordini'];
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?php echo htmlspecialchars($locale['nome']); ?> - Menu</title>
    <link href="https://fonts.googleapis.com/css2?family=<?php echo urlencode($tema['font_titoli']); ?>:wght@600;700&family=<?php echo urlencode($tema['font_testo']); ?>:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <style>
        :root {
            --primario: <?php echo $tema['colore_primario']; ?>;
            --secondario: <?php echo $tema['colore_secondario']; ?>;
            --sfondo: <?php echo $tema['colore_sfondo']; ?>;
            --testo: <?php echo $tema['colore_testo']; ?>;
            --font-titoli: '<?php echo $tema['font_titoli']; ?>', sans-serif;
            --font-testo: '<?php echo $tema['font_testo']; ?>', sans-serif;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            -webkit-tap-highlight-color: transparent;
        }
        
        html, body {
            width: 100%;
            height: 100%;
            overflow-x: hidden;
            position: relative;
        }
        
        body {
            font-family: var(--font-testo);
            background: var(--sfondo);
            color: var(--testo);
            -webkit-overflow-scrolling: touch;
        }
        
        /* HEADER */
        .header {
            background: linear-gradient(135deg, var(--primario), var(--secondario));
            color: white;
            padding: 20px 16px;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .header h1 {
            font-family: var(--font-titoli);
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 4px;
        }
        
        .header .subtitle {
            font-size: 0.9rem;
            opacity: 0.9;
        }
        
        /* VIEWS CONTAINER - FIX SCROLL MOBILE */
        .views-container {
            position: relative;
            width: 100%;
            min-height: calc(100vh - 70px);
        }
        
        .view {
            width: 100%;
            min-height: calc(100vh - 70px);
            background: var(--sfondo);
            padding: 16px;
            padding-bottom: 100px;
            display: none;
        }
        
        .view.active {
            display: block;
        }
        
        /* CATEGORIE */
        .categoria-card {
            background: white;
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            cursor: pointer;
            transition: all 0.2s;
            position: relative;
            overflow: hidden;
        }
        
        .categoria-card::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 5px;
            background: var(--primario);
        }
        
        .categoria-card:active {
            transform: scale(0.98);
        }
        
        .categoria-card-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .categoria-nome {
            font-family: var(--font-titoli);
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--testo);
            margin-bottom: 4px;
        }
        
        .categoria-count {
            font-size: 0.9rem;
            color: var(--secondario);
        }
        
        .categoria-arrow {
            font-size: 1.5rem;
            color: var(--primario);
        }
        
        /* PIATTI HEADER */
        .piatti-header {
            margin-bottom: 20px;
            padding-bottom: 16px;
            border-bottom: 2px solid rgba(0,0,0,0.1);
        }
        
        .back-button {
            background: white;
            border: 2px solid var(--primario);
            color: var(--primario);
            padding: 10px 20px;
            border-radius: 25px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            margin-bottom: 16px;
            transition: all 0.2s;
        }
        
        .back-button:active {
            transform: scale(0.95);
        }
        
        .categoria-title {
            font-family: var(--font-titoli);
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--testo);
        }
        
        /* PIATTI */
        .piatto-card {
            background: white;
            border-radius: 16px;
            margin-bottom: 16px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            overflow: hidden;
        }
        
        .piatto-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }
        
        .piatto-body {
            padding: 16px;
        }
        
        .piatto-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            gap: 12px;
            margin-bottom: 8px;
        }
        
        .piatto-nome {
            font-family: var(--font-titoli);
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--testo);
            flex: 1;
        }
        
        .piatto-prezzo {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--primario);
            white-space: nowrap;
        }
        
        .piatto-descrizione {
            color: var(--secondario);
            font-size: 0.95rem;
            line-height: 1.5;
            margin-bottom: 12px;
        }
        
        .piatto-ingredienti {
            color: var(--secondario);
            font-size: 0.9rem;
            font-style: italic;
            margin-bottom: 12px;
            padding-left: 20px;
            position: relative;
        }
        
        .piatto-ingredienti::before {
            content: '•';
            position: absolute;
            left: 0;
        }
        
        .badges {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
        }
        
        .badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .badge-allergene {
            background: #ffc107;
            color: #000;
        }
        
        .badge-caratteristica {
            color: white;
        }
        
        /* SPLASH SCREEN */
        .splash-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: calc(100vh - 70px);
            padding: 40px 20px;
            text-align: center;
        }
        
        .splash-logo {
            max-width: 250px;
            max-height: 250px;
            width: 80%;
            height: auto;
            object-fit: contain;
            margin-bottom: 30px;
            border-radius: 20px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.12);
            cursor: pointer;
            transition: transform 0.3s ease;
        }
        
        .splash-logo:hover {
            transform: scale(1.05);
        }
        
        .splash-logo-placeholder {
            width: 200px;
            height: 200px;
            background: linear-gradient(135deg, var(--primario), var(--secondario));
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 30px;
            cursor: pointer;
            box-shadow: 0 8px 30px rgba(0,0,0,0.12);
            transition: transform 0.3s ease;
        }
        
        .splash-logo-placeholder i {
            font-size: 100px;
            color: white;
        }
        
        .splash-logo-placeholder:hover {
            transform: scale(1.05);
        }
        
        .splash-title {
            font-family: var(--font-titoli);
            font-size: 2rem;
            font-weight: 700;
            color: var(--testo);
            margin-bottom: 15px;
        }
        
        .splash-description {
            color: var(--secondario);
            font-size: 1.1rem;
            margin-bottom: 40px;
            max-width: 600px;
            line-height: 1.6;
        }
        
        .splash-button {
            background: var(--primario);
            color: white;
            border: none;
            padding: 18px 40px;
            border-radius: 50px;
            font-size: 1.2rem;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 12px;
        }
        
        .splash-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 25px rgba(0,0,0,0.2);
        }
        
        .splash-button:active {
            transform: translateY(0);
        }
        
        @media (max-width: 768px) {
            .splash-logo {
                max-width: 180px;
                max-height: 180px;
            }
            
            .splash-logo-placeholder {
                width: 150px;
                height: 150px;
            }
            
            .splash-logo-placeholder i {
                font-size: 70px;
            }
            
            .splash-title {
                font-size: 1.5rem;
            }
            
            .splash-description {
                font-size: 1rem;
            }
            
            .splash-button {
                padding: 15px 30px;
                font-size: 1.1rem;
            }
        }
        
        /* EMPTY STATE */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--secondario);
        }
        
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 16px;
            opacity: 0.3;
        }
        
        /* PULSANTE ORDINA FLOATING */
        .ordina-floating-btn {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 9999;
            animation: pulse-btn 2s infinite;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.3s;
        }
        
        .ordina-floating-btn.visible {
            opacity: 1;
            pointer-events: auto;
        }
        
        .ordina-floating-btn .btn-ordina {
            background: #28a745;
            color: white;
            border: none;
            border-radius: 50px;
            padding: 15px 30px;
            font-size: 1.1em;
            font-weight: bold;
            box-shadow: 0 4px 15px rgba(0,0,0,0.3);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s;
        }
        
        .ordina-floating-btn .btn-ordina:active {
            transform: scale(0.95);
        }
        
        @keyframes pulse-btn {
            0%, 100% {
                transform: scale(1);
                box-shadow: 0 4px 15px rgba(0,0,0,0.3);
            }
            50% {
                transform: scale(1.05);
                box-shadow: 0 6px 20px rgba(40, 167, 69, 0.5);
            }
        }
        
        @media (max-width: 768px) {
            .ordina-floating-btn {
                bottom: 10px;
                right: 10px;
            }
            .ordina-floating-btn .btn-ordina {
                padding: 12px 24px;
                font-size: 1em;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1><?php echo htmlspecialchars($locale['nome']); ?></h1>
        <div class="subtitle"><?php echo htmlspecialchars($menuSelezionato['nome']); ?></div>
    </div>

    <div class="views-container">
        <!-- SPLASH SCREEN - Logo Ristorante -->
        <div class="view active" id="splashView">
            <div class="splash-container">
                <?php if (!empty($locale['logo'])): ?>
                <img src="<?php echo BASE_URL . '/uploads/' . $locale['logo']; ?>" 
                     alt="<?php echo htmlspecialchars($locale['nome']); ?>" 
                     class="splash-logo"
                     onclick="mostraCategorie()">
                <?php else: ?>
                <div class="splash-logo-placeholder" onclick="mostraCategorie()">
                    <i class="bi bi-shop"></i>
                </div>
                <?php endif; ?>
                <h1 class="splash-title"><?php echo htmlspecialchars($locale['nome']); ?></h1>
                <?php if (!empty($locale['descrizione'])): ?>
                <p class="splash-description"><?php echo htmlspecialchars($locale['descrizione']); ?></p>
                <?php endif; ?>
                <button class="splash-button" onclick="mostraCategorie()">
                    <i class="bi bi-journal-text"></i> Visualizza Menu
                </button>
            </div>
        </div>

        <!-- CATEGORIE VIEW -->
        <div class="view" id="categorieView">
            <div id="categorieList"></div>
        </div>

        <!-- PIATTI VIEW -->
        <div class="view" id="piattiView">
            <div class="piatti-header">
                <button class="back-button" onclick="tornaCategorie()">
                    <i class="bi bi-arrow-left"></i>
                    <span>Indietro</span>
                </button>
                <h2 class="categoria-title" id="categoriaNome"></h2>
            </div>
            <div id="piattiList"></div>
        </div>
    </div>

    <?php if ($ordiniAttivi && $qrAbilitaOrdini): ?>
    <!-- Pulsante Floating Ordina -->
    <div class="ordina-floating-btn">
        <a href="<?php echo BASE_URL; ?>/public/ordina.php?locale=<?php echo $locale['id']; ?><?php echo $codice ? '&qr=' . urlencode($codice) : ''; ?>" 
           class="btn-ordina">
            <i class="bi bi-cart-plus-fill"></i> Ordina Ora
        </a>
    </div>
    <?php endif; ?>

    <script>
        const BASE_URL = '<?php echo BASE_URL; ?>';
        const mostraImmagini = <?php echo $tema['mostra_immagini'] ? 'true' : 'false'; ?>;
        const mostraDescrizioni = <?php echo $tema['mostra_descrizioni'] ? 'true' : 'false'; ?>;
        const mostraAllergeni = <?php echo $tema['mostra_allergeni'] ? 'true' : 'false'; ?>;
        
        const categorie = <?php echo $categorieJSON; ?>;
        let categoriaCorrente = null;

        // Mostra categorie (transizione da splash screen)
        function mostraCategorie() {
            document.getElementById('splashView').classList.remove('active');
            document.getElementById('categorieView').classList.add('active');
            
            // Mostra pulsante ordina
            const ordinaBtn = document.querySelector('.ordina-floating-btn');
            if (ordinaBtn) ordinaBtn.classList.add('visible');
            
            window.scrollTo(0, 0);
        }

        // Render categorie
        function renderCategorie() {
            const container = document.getElementById('categorieList');
            
            if (categorie.length === 0) {
                container.innerHTML = `
                    <div class="empty-state">
                        <i class="bi bi-journal-x"></i>
                        <h3>Nessuna categoria disponibile</h3>
                    </div>
                `;
                return;
            }
            
            container.innerHTML = categorie.map(cat => `
                <div class="categoria-card" onclick="mostraPiatti(${cat.categoria.id})">
                    <div class="categoria-card-content">
                        <div>
                            <div class="categoria-nome">${escapeHtml(cat.categoria.nome)}</div>
                            <div class="categoria-count">${cat.piatti.length} piatti</div>
                        </div>
                        <div class="categoria-arrow">
                            <i class="bi bi-chevron-right"></i>
                        </div>
                    </div>
                </div>
            `).join('');
        }

        // Mostra piatti categoria
        function mostraPiatti(categoriaId) {
            const cat = categorie.find(c => c.categoria.id == categoriaId);
            if (!cat) return;
            
            categoriaCorrente = cat;
            
            document.getElementById('categoriaNome').textContent = cat.categoria.nome;
            
            const container = document.getElementById('piattiList');
            container.innerHTML = cat.piatti.map(piatto => `
                <div class="piatto-card">
                    ${mostraImmagini && piatto.immagine ? `
                        <img src="${BASE_URL}/${piatto.immagine}" 
                             alt="${escapeHtml(piatto.nome)}" 
                             class="piatto-image"
                             loading="lazy">
                    ` : ''}
                    
                    <div class="piatto-body">
                        <div class="piatto-header">
                            <div class="piatto-nome">${escapeHtml(piatto.nome)}</div>
                            ${piatto.mostra_prezzo ? `
                                <div class="piatto-prezzo">${formatPrice(piatto.prezzo)}</div>
                            ` : ''}
                        </div>
                        
                        ${mostraDescrizioni && piatto.descrizione ? `
                            <div class="piatto-descrizione">${escapeHtml(piatto.descrizione)}</div>
                        ` : ''}
                        
                        ${piatto.ingredienti ? `
                            <div class="piatto-ingredienti">${escapeHtml(piatto.ingredienti)}</div>
                        ` : ''}
                        
                        ${(piatto.caratteristiche && piatto.caratteristiche.length > 0) || (mostraAllergeni && piatto.allergeni && piatto.allergeni.length > 0) ? `
                            <div class="badges">
                                ${piatto.caratteristiche.map(car => `
                                    <span class="badge badge-caratteristica" style="background-color: ${car.colore}">
                                        ${car.icona} ${escapeHtml(car.nome)}
                                    </span>
                                `).join('')}
                                
                                ${mostraAllergeni ? piatto.allergeni.map(all => `
                                    <span class="badge badge-allergene">${escapeHtml(all.codice)}</span>
                                `).join('') : ''}
                            </div>
                        ` : ''}
                    </div>
                </div>
            `).join('');
            
            // Cambia view
            document.getElementById('categorieView').classList.remove('active');
            document.getElementById('piattiView').classList.add('active');
            
            window.scrollTo(0, 0);
        }

        // Torna a categorie
        function tornaCategorie() {
            document.getElementById('piattiView').classList.remove('active');
            document.getElementById('categorieView').classList.add('active');
            
            window.scrollTo(0, 0);
        }

        // Format price
        function formatPrice(price) {
            return '€' + parseFloat(price).toFixed(2).replace('.', ',');
        }

        // Escape HTML
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Init
        renderCategorie();
        
        // Previeni comportamento default scroll su iOS
        document.addEventListener('touchmove', function(e) {
            if (e.target.closest('.views-container')) {
                e.stopPropagation();
            }
        }, { passive: true });
    </script>
</body>
</html>