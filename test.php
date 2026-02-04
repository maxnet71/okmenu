<?php
// Test save-manual-menu.php - Mostra tutti gli errori
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/classes/Database.php';
require_once __DIR__ . '/Model.php';
require_once __DIR__ . '/classes/models/Menu.php';
require_once __DIR__ . '/classes/models/Categoria.php';
require_once __DIR__ . '/classes/models/Piatto.php';


echo "<h2>Test save-manual-menu.php</h2>";
echo "<hr>";

// Test 1: Verifica file
echo "<h3>1. Verifica File</h3>";
$files = ['Model.php', '/classes/models/Menu.php', '/classes/models/Categoria.php', '/classes/models/Piatto.php'];
foreach ($files as $file) {
    $path = __DIR__ . '/' . $file;
    echo file_exists($path) ? "✅ $file esiste<br>" : "❌ $file NON esiste<br>";
}

// Test 2: Verifica classi
echo "<h3>2. Verifica Classi</h3>";
$classes = ['Model', 'Menu', 'Categoria', 'Piatto'];
foreach ($classes as $class) {
    echo class_exists($class) ? "✅ $class caricata<br>" : "❌ $class NON caricata<br>";
}

// Test 3: Verifica Database
echo "<h3>3. Verifica Database</h3>";
try {
    $db = Database::getInstance()->getConnection();
    echo "✅ Database connesso<br>";
} catch (Exception $e) {
    echo "❌ Errore database: " . $e->getMessage() . "<br>";
    exit;
}

// Test 4: Verifica tabelle
echo "<h3>4. Verifica Tabelle</h3>";
$tables = ['menu', 'categorie', 'piatti'];
foreach ($tables as $table) {
    try {
        $stmt = $db->query("SHOW TABLES LIKE '$table'");
        echo $stmt->rowCount() > 0 ? "✅ Tabella $table esiste<br>" : "❌ Tabella $table NON esiste<br>";
    } catch (Exception $e) {
        echo "❌ Errore $table: " . $e->getMessage() . "<br>";
    }
}

// Test 5: Simulazione inserimento
echo "<h3>5. Test Inserimento</h3>";

// Dati test
$testData = [
    'locale_id' => 1,
    'menu_data' => [
        'categories' => [
            [
                'name' => 'Antipasti Test',
                'dishes' => [
                    [
                        'name' => 'Bruschetta Test',
                        'description' => 'Test desc',
                        'price' => '5.00',
                        'vegetarian' => true
                    ]
                ]
            ]
        ]
    ]
];

echo "<strong>Dati test:</strong><br>";
echo "<pre>" . print_r($testData, true) . "</pre>";

try {
    // Simula creazione menu
    $menuModel = new Menu();
    echo "✅ Menu model creato<br>";
    
    $categoriaModel = new Categoria();
    echo "✅ Categoria model creato<br>";
    
    $piattoModel = new Piatto();
    echo "✅ Piatto model creato<br>";
    
    // Test insert (senza commit)
    $db->beginTransaction();
    
    $menuId = $menuModel->insert([
        'locale_id' => $testData['locale_id'],
        'tipo' => 'principale',
        'pubblicato' => 1
    ]);
    echo "✅ Menu inserito - ID: $menuId<br>";
    
    $catData = $testData['menu_data']['categories'][0];
    $categoriaId = $categoriaModel->insert([
        'menu_id' => $menuId,
        'nome' => $catData['name'],
        'ordinamento' => 0
    ]);
    echo "✅ Categoria inserita - ID: $categoriaId<br>";
    
    $dishData = $catData['dishes'][0];
    $piattoId = $piattoModel->insert([
        'categoria_id' => $categoriaId,
        'nome' => $dishData['name'],
        'descrizione' => $dishData['description'],
        'prezzo' => floatval($dishData['price']),
        'vegetariano' => 1,
        'ordinamento' => 0,
        'disponibile' => 1
    ]);
    echo "✅ Piatto inserito - ID: $piattoId<br>";
    
    $db->rollBack(); // Rollback per non sporcare il DB
    echo "✅ Test completato (rollback effettuato)<br>";
    
} catch (Exception $e) {
    $db->rollBack();
    echo "❌ <strong>ERRORE:</strong> " . $e->getMessage() . "<br>";
    echo "<pre>File: " . $e->getFile() . ":" . $e->getLine() . "</pre>";
    echo "<pre>Trace:\n" . $e->getTraceAsString() . "</pre>";
}

echo "<hr>";
echo "<a href='onboarding/manual-menu.php'>← Torna a Manual Menu</a>";