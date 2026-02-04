<?php
require_once __DIR__ . '/../../config/config.php';

require_once __DIR__ . '/../../classes/Database.php';
require_once __DIR__ . '/../../classes/Model.php';
require_once __DIR__ . '/../../classes/Helpers.php';
require_once __DIR__ . '/../../classes/models/User.php';
require_once __DIR__ . '/../../classes/models/LocaleRestaurant.php';
require_once __DIR__ . '/../../classes/models/Menu.php';
require_once __DIR__ . '/../../classes/models/Categoria.php';
require_once __DIR__ . '/../../classes/models/Piatto.php';

Helpers::requireLogin();

$user = Helpers::getUser();
$menuId = intval($_GET['menu'] ?? 0);
$modello = $_GET['modello'] ?? 'classico';
$mostraPrezzi = intval($_GET['prezzi'] ?? 1);
$mostraAllergeni = intval($_GET['allergeni'] ?? 1);
$mostraDescrizioni = intval($_GET['descrizioni'] ?? 1);
$mostraIngredienti = intval($_GET['ingredienti'] ?? 0);

$menuModel = new Menu();
$categoriaModel = new Categoria();
$piattoModel = new Piatto();
$localeModel = new LocaleRestaurant();

$menu = $menuModel->getById($menuId);
if (!$menu) {
    die('Menu non trovato');
}

$locale = $localeModel->getById($menu['locale_id']);
if (!$locale || $locale['user_id'] != $user['id']) {
    die('Non autorizzato');
}

$categorie = $categoriaModel->getByMenuId($menuId);
$categorieConPiatti = [];

foreach ($categorie as $cat) {
    $piatti = $piattoModel->getByCategoriaId($cat['id']);
    if (!empty($piatti)) {
        $categorieConPiatti[] = [
            'categoria' => $cat,
            'piatti' => $piatti
        ];
    }
}

$modelli = [
    'classico' => ['font' => 'Georgia, serif', 'colore' => '#000000', 'bg' => '#ffffff', 'accent' => '#333333'],
    'moderno' => ['font' => 'Arial, sans-serif', 'colore' => '#333333', 'bg' => '#f8f9fa', 'accent' => '#007bff'],
    'elegante' => ['font' => 'Garamond, serif', 'colore' => '#2c2c2c', 'bg' => '#fffef7', 'accent' => '#d4af37'],
    'minimalista' => ['font' => 'Helvetica, sans-serif', 'colore' => '#1a1a1a', 'bg' => '#ffffff', 'accent' => '#999999'],
    'colorato' => ['font' => 'Comic Sans MS, cursive', 'colore' => '#ff6b35', 'bg' => '#ffe8d6', 'accent' => '#ff6b35'],
    'vintage' => ['font' => 'Courier New, monospace', 'colore' => '#5c4033', 'bg' => '#f4e8d8', 'accent' => '#8b6914'],
    'bistrot' => ['font' => 'Brush Script MT, cursive', 'colore' => '#000000', 'bg' => '#ffffff', 'accent' => '#dc143c'],
    'rustico' => ['font' => 'Trebuchet MS, sans-serif', 'colore' => '#3e2723', 'bg' => '#fdf6ec', 'accent' => '#8d6e63'],
    'sushi' => ['font' => 'MS Mincho, serif', 'colore' => '#000000', 'bg' => '#ffffff', 'accent' => '#c41e3a'],
    'street' => ['font' => 'Impact, sans-serif', 'colore' => '#000000', 'bg' => '#ffffff', 'accent' => '#ffd700'],
    'vegano' => ['font' => 'Verdana, sans-serif', 'colore' => '#2e7d32', 'bg' => '#f1f8e9', 'accent' => '#4caf50'],
    'lusso' => ['font' => 'Times New Roman, serif', 'colore' => '#1a1a1a', 'bg' => '#000000', 'accent' => '#c9a961']
];

$stile = $modelli[$modello] ?? $modelli['classico'];
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $menu['nome']; ?> - <?php echo $locale['nome']; ?></title>
    <style>
        @page {
            size: A4;
            margin: 15mm;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: <?php echo $stile['font']; ?>;
            color: <?php echo $stile['colore']; ?>;
            background: <?php echo $stile['bg']; ?>;
            line-height: 1.6;
            font-size: 11pt;
        }
        
        .page {
            width: 210mm;
            min-height: 297mm;
            padding: 15mm;
            margin: 0 auto;
            background: white;
            page-break-after: always;
            position: relative;
        }
        
        .page:last-child {
            page-break-after: auto;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid <?php echo $stile['colore']; ?>;
        }
        
        .header h1 {
            font-size: 28pt;
            margin-bottom: 10px;
            <?php if ($modello === 'elegante'): ?>
            font-variant: small-caps;
            letter-spacing: 2px;
            <?php endif; ?>
        }
        
        .header .subtitle {
            font-size: 14pt;
            color: #666;
            <?php if ($modello === 'colorato'): ?>
            color: #ff6b35;
            font-weight: bold;
            <?php endif; ?>
        }
        
        .categoria {
            page-break-inside: avoid;
            margin-bottom: 25px;
        }
        
        .categoria-header {
            font-size: 18pt;
            font-weight: bold;
            margin-bottom: 15px;
            padding-bottom: 8px;
            border-bottom: 1px solid #ccc;
            <?php if ($modello === 'moderno'): ?>
            background: linear-gradient(to right, #007bff, transparent);
            color: white;
            padding: 10px;
            border-bottom: none;
            <?php endif; ?>
            <?php if ($modello === 'elegante'): ?>
            text-align: center;
            border-bottom: 2px solid #d4af37;
            color: #d4af37;
            <?php endif; ?>
            <?php if ($modello === 'colorato'): ?>
            background: #ff6b35;
            color: white;
            padding: 10px;
            border-radius: 5px;
            border-bottom: none;
            <?php endif; ?>
        }
        
        .categoria-descrizione {
            font-style: italic;
            color: #666;
            margin-bottom: 10px;
            font-size: 10pt;
        }
        
        .piatto {
            page-break-inside: avoid;
            margin-bottom: 15px;
            padding: 8px 0;
            <?php if ($modello === 'moderno' || $modello === 'colorato'): ?>
            padding: 12px;
            background: #f8f9fa;
            border-radius: 8px;
            margin-bottom: 10px;
            <?php endif; ?>
        }
        
        .piatto-header {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            margin-bottom: 5px;
        }
        
        .piatto-nome {
            font-weight: bold;
            font-size: 12pt;
            <?php if ($modello === 'elegante'): ?>
            font-variant: small-caps;
            <?php endif; ?>
        }
        
        .piatto-prezzo {
            font-weight: bold;
            font-size: 12pt;
            white-space: nowrap;
            margin-left: 10px;
            <?php if ($modello === 'moderno'): ?>
            color: #007bff;
            <?php endif; ?>
            <?php if ($modello === 'elegante'): ?>
            color: #d4af37;
            <?php endif; ?>
            <?php if ($modello === 'colorato'): ?>
            color: #ff6b35;
            font-size: 14pt;
            <?php endif; ?>
        }
        
        .piatto-descrizione {
            color: #555;
            font-size: 9.5pt;
            margin: 5px 0;
            line-height: 1.4;
        }
        
        .piatto-ingredienti {
            color: #777;
            font-size: 9pt;
            font-style: italic;
            margin: 3px 0;
        }
        
        .allergeni {
            display: inline-flex;
            flex-wrap: wrap;
            gap: 4px;
            margin-top: 5px;
        }
        
        .allergene {
            display: inline-block;
            background: #ffc107;
            color: #000;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 8pt;
            font-weight: bold;
        }
        
        .caratteristiche {
            display: inline-flex;
            flex-wrap: wrap;
            gap: 4px;
            margin-top: 5px;
        }
        
        .caratteristica {
            display: inline-block;
            background: #28a745;
            color: white;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 8pt;
        }
        
        .footer {
            position: absolute;
            bottom: 10mm;
            left: 15mm;
            right: 15mm;
            text-align: center;
            font-size: 9pt;
            color: #999;
            padding-top: 10px;
            border-top: 1px solid #ddd;
        }
        
        @media print {
            body {
                background: white;
            }
            .page {
                margin: 0;
                padding: 15mm;
            }
            .no-print {
                display: none;
            }
        }
        
        <?php if ($modello === 'minimalista'): ?>
        .categoria-header {
            font-weight: 300;
            text-transform: uppercase;
            letter-spacing: 3px;
            font-size: 14pt;
        }
        .piatto-nome {
            font-weight: 400;
        }
        <?php endif; ?>
        
        <?php if ($modello === 'vintage'): ?>
        body { background: linear-gradient(135deg, #f4e8d8 0%, #e8d4c0 100%); }
        .page { border: 5px double #8b6914; }
        .header h1 {
            font-family: 'Courier New', monospace;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
        }
        .categoria-header {
            background: #8b6914;
            color: #f4e8d8;
            padding: 8px 15px;
            text-align: center;
            border-radius: 15px;
        }
        .piatto { border-bottom: 1px dashed #8b6914; }
        <?php endif; ?>
        
        <?php if ($modello === 'bistrot'): ?>
        .header h1 {
            font-family: 'Brush Script MT', cursive;
            color: #dc143c;
            font-size: 32pt;
        }
        .categoria-header {
            color: #dc143c;
            font-style: italic;
            border-bottom: 2px solid #000;
        }
        .piatto-prezzo { color: #dc143c !important; font-weight: bold; }
        .page { border-left: 5px solid #dc143c; }
        <?php endif; ?>
        
        <?php if ($modello === 'rustico'): ?>
        body { background: #fdf6ec; }
        .page {
            background: linear-gradient(to bottom, #fdf6ec 0%, #f5ebe0 100%);
            box-shadow: inset 0 0 30px rgba(62, 39, 35, 0.1);
        }
        .header {
            background: linear-gradient(135deg, #8d6e63 0%, #5d4037 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 25px;
        }
        .header h1 { color: white; border: none; }
        .categoria-header {
            background: #8d6e63;
            color: white;
            padding: 10px;
            border-radius: 5px;
        }
        <?php endif; ?>
        
        <?php if ($modello === 'sushi'): ?>
        .page { border: 2px solid #c41e3a; }
        .header {
            background: linear-gradient(90deg, #000 0%, #c41e3a 50%, #000 100%);
            color: white;
            padding: 20px;
        }
        .header h1 {
            color: #fff;
            border: none;
            font-weight: 300;
            letter-spacing: 5px;
        }
        .categoria-header {
            background: #000;
            color: #c41e3a;
            padding: 10px;
            text-align: center;
            border: 1px solid #c41e3a;
        }
        .piatto-prezzo { color: #c41e3a !important; }
        <?php endif; ?>
        
        <?php if ($modello === 'street'): ?>
        body { background: #1a1a1a; }
        .page {
            background: white;
            border: 5px solid #000;
            box-shadow: 5px 5px 0 #ffd700;
        }
        .header h1 {
            text-transform: uppercase;
            font-weight: 900;
            letter-spacing: 3px;
            -webkit-text-stroke: 2px #ffd700;
            color: #000;
        }
        .categoria-header {
            background: #000;
            color: #ffd700;
            padding: 12px;
            text-transform: uppercase;
            font-weight: bold;
            letter-spacing: 2px;
        }
        .piatto-nome { font-weight: 900; text-transform: uppercase; }
        .piatto-prezzo {
            background: #ffd700;
            color: #000;
            padding: 5px 10px;
            font-weight: 900;
        }
        <?php endif; ?>
        
        <?php if ($modello === 'vegano'): ?>
        body { background: linear-gradient(135deg, #f1f8e9 0%, #dcedc8 100%); }
        .header {
            background: linear-gradient(135deg, #4caf50 0%, #2e7d32 100%);
            color: white;
            padding: 20px;
            border-radius: 15px;
        }
        .header h1 { color: white; border: none; }
        .categoria-header {
            background: #4caf50;
            color: white;
            padding: 10px 15px;
            border-radius: 25px;
            text-align: center;
        }
        .piatto {
            background: rgba(76, 175, 80, 0.05);
            border-left: 4px solid #4caf50;
            padding: 12px;
            margin-bottom: 10px;
        }
        .piatto-prezzo { color: #2e7d32 !important; }
        <?php endif; ?>
        
        <?php if ($modello === 'lusso'): ?>
        body { background: #000; }
        .page {
            background: linear-gradient(135deg, #1a1a1a 0%, #000 100%);
            color: #c9a961;
            border: 3px solid #c9a961;
            box-shadow: 0 0 30px rgba(201, 169, 97, 0.3);
        }
        .header {
            border-bottom: 3px solid #c9a961;
            padding: 30px 20px;
        }
        .header h1 {
            color: #c9a961;
            font-variant: small-caps;
            letter-spacing: 5px;
            text-shadow: 0 0 20px rgba(201, 169, 97, 0.5);
            border: none;
        }
        .header .subtitle { color: #999; }
        .categoria-header {
            color: #c9a961;
            text-align: center;
            font-variant: small-caps;
            letter-spacing: 3px;
            border-top: 1px solid #c9a961;
            border-bottom: 1px solid #c9a961;
            padding: 15px 0;
        }
        .piatto {
            border-bottom: 1px solid #333;
            padding: 15px 0;
        }
        .piatto-nome { color: #fff; }
        .piatto-descrizione { color: #999; }
        .piatto-prezzo {
            color: #c9a961 !important;
            font-size: 14pt;
        }
        .footer {
            color: #666;
            border-top: 1px solid #333;
        }
        <?php endif; ?>
    </style>
</head>
<body>
    <div class="no-print" style="position: fixed; top: 20px; right: 20px; z-index: 1000;">
        <button onclick="window.print()" style="padding: 10px 20px; font-size: 14px; cursor: pointer; background: #007bff; color: white; border: none; border-radius: 5px;">
            <span style="margin-right: 5px;">🖨️</span> Stampa
        </button>
        <button onclick="window.close()" style="padding: 10px 20px; font-size: 14px; cursor: pointer; background: #6c757d; color: white; border: none; border-radius: 5px; margin-left: 10px;">
            Chiudi
        </button>
    </div>

<?php
$contenutoPerPagina = [];
$paginaCorrente = [];
$altezzaPagina = 250;
$altezzaCorrente = 0;
$altezzaHeader = 35;
$altezzaFooter = 15;

$altezzaCorrente += $altezzaHeader;

foreach ($categorieConPiatti as $catData) {
    $cat = $catData['categoria'];
    $piatti = $catData['piatti'];
    
    $altezzaCategoria = 12;
    if (!empty($cat['descrizione'])) {
        $altezzaCategoria += 5;
    }
    
    $altezzaPiatti = 0;
    foreach ($piatti as $piatto) {
        $altezzaPiatto = 8;
        if ($mostraDescrizioni && !empty($piatto['descrizione'])) {
            $righeDesc = ceil(strlen($piatto['descrizione']) / 80);
            $altezzaPiatto += $righeDesc * 4;
        }
        if ($mostraIngredienti && !empty($piatto['ingredienti'])) {
            $altezzaPiatto += 4;
        }
        if ($mostraAllergeni) {
            $allergeni = $piattoModel->getAllergeni($piatto['id']);
            if (!empty($allergeni)) {
                $altezzaPiatto += 6;
            }
        }
        $altezzaPiatti += $altezzaPiatto;
    }
    
    $altezzaTotaleCategoria = $altezzaCategoria + $altezzaPiatti;
    
    if ($altezzaCorrente + $altezzaTotaleCategoria > $altezzaPagina) {
        $contenutoPerPagina[] = $paginaCorrente;
        $paginaCorrente = [];
        $altezzaCorrente = $altezzaHeader;
    }
    
    $paginaCorrente[] = $catData;
    $altezzaCorrente += $altezzaTotaleCategoria;
}

if (!empty($paginaCorrente)) {
    $contenutoPerPagina[] = $paginaCorrente;
}

foreach ($contenutoPerPagina as $indicePagina => $categorieP): ?>
    <div class="page">
        <?php if ($indicePagina === 0): ?>
        <div class="header">
            <h1><?php echo $locale['nome']; ?></h1>
            <div class="subtitle"><?php echo $menu['nome']; ?></div>
            <?php if ($menu['descrizione']): ?>
                <div class="subtitle" style="font-size: 10pt; margin-top: 10px;"><?php echo $menu['descrizione']; ?></div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <?php foreach ($categorieP as $catData): 
            $cat = $catData['categoria'];
            $piatti = $catData['piatti'];
        ?>
            <div class="categoria">
                <div class="categoria-header">
                    <?php echo $cat['nome']; ?>
                </div>
                
                <?php if (!empty($cat['descrizione'])): ?>
                    <div class="categoria-descrizione"><?php echo $cat['descrizione']; ?></div>
                <?php endif; ?>
                
                <?php foreach ($piatti as $piatto): 
                    if (!$piatto['disponibile']) continue;
                    $allergeni = $mostraAllergeni ? $piattoModel->getAllergeni($piatto['id']) : [];
                    $caratteristiche = $piattoModel->getCaratteristiche($piatto['id']);
                ?>
                    <div class="piatto">
                        <div class="piatto-header">
                            <span class="piatto-nome"><?php echo $piatto['nome']; ?></span>
                            <?php if ($mostraPrezzi && $piatto['mostra_prezzo']): ?>
                                <span class="piatto-prezzo"><?php echo Helpers::formatPrice($piatto['prezzo']); ?></span>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($mostraDescrizioni && !empty($piatto['descrizione'])): ?>
                            <div class="piatto-descrizione"><?php echo $piatto['descrizione']; ?></div>
                        <?php endif; ?>
                        
                        <?php if ($mostraIngredienti && !empty($piatto['ingredienti'])): ?>
                            <div class="piatto-ingredienti">Ingredienti: <?php echo $piatto['ingredienti']; ?></div>
                        <?php endif; ?>
                        
                        <?php if (!empty($caratteristiche)): ?>
                            <div class="caratteristiche">
                                <?php foreach ($caratteristiche as $car): ?>
                                    <span class="caratteristica" style="background-color: <?php echo $car['colore']; ?>">
                                        <?php echo $car['icona']; ?> <?php echo $car['nome']; ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($mostraAllergeni && !empty($allergeni)): ?>
                            <div class="allergeni">
                                <?php foreach ($allergeni as $all): ?>
                                    <span class="allergene"><?php echo $all['codice']; ?></span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
        
        <div class="footer">
            <?php echo $locale['nome']; ?>
            <?php if ($locale['indirizzo']): ?>
                - <?php echo $locale['indirizzo']; ?>, <?php echo $locale['citta']; ?>
            <?php endif; ?>
            <?php if ($locale['telefono']): ?>
                - Tel: <?php echo $locale['telefono']; ?>
            <?php endif; ?>
        </div>
    </div>
<?php endforeach; ?>

</body>
</html>