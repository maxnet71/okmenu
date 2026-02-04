<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Verifica Installazione Menu Digitale</h1>";
echo "<hr>";

echo "<h2>1. Verifica File</h2>";
$requiredFiles = [
    'config/config.php',
    'autoload.php',
    'classes/Database.php',
    'classes/Model.php',
    'classes/Helpers.php',
    'classes/models/User.php',
    'classes/models/LocaleRestaurant.php',
    'classes/models/Menu.php',
    'classes/models/Categoria.php',
    'classes/models/Piatto.php',
    'classes/models/Ordine.php',
    'classes/models/Prenotazione.php',
    'classes/models/QRCode.php',
    'classes/models/Statistica.php'
];

foreach ($requiredFiles as $file) {
    $exists = file_exists(__DIR__ . '/' . $file);
    echo ($exists ? '✓' : '✗') . " {$file}<br>";
}

echo "<hr>";
echo "<h2>2. Verifica Configurazione</h2>";

if (file_exists(__DIR__ . '/config/config.php')) {
    require_once __DIR__ . '/config/config.php';
    echo "✓ File config caricato<br>";
    echo "DB_HOST: " . (defined('DB_HOST') ? DB_HOST : 'NON DEFINITO') . "<br>";
    echo "DB_NAME: " . (defined('DB_NAME') ? DB_NAME : 'NON DEFINITO') . "<br>";
    echo "DB_USER: " . (defined('DB_USER') ? DB_USER : 'NON DEFINITO') . "<br>";
    echo "BASE_URL: " . (defined('BASE_URL') ? BASE_URL : 'NON DEFINITO') . "<br>";
} else {
    echo "✗ File config non trovato<br>";
}

echo "<hr>";
echo "<h2>3. Verifica Autoload</h2>";

if (file_exists(__DIR__ . '/autoload.php')) {
    require_once __DIR__ . '/autoload.php';
    echo "✓ Autoload caricato<br>";
} else {
    echo "✗ Autoload non trovato<br>";
}

echo "<hr>";
echo "<h2>4. Verifica Classi</h2>";

$classes = [
    'Database',
    'Model',
    'Helpers',
    'User',
    'Locale',
    'Menu',
    'Categoria',
    'Piatto'
];

foreach ($classes as $class) {
    $exists = class_exists($class);
    echo ($exists ? '✓' : '✗') . " {$class}";
    
    if ($exists && $class === 'Locale') {
        $locale = new LocaleRestaurant();
        $hasMethod = method_exists($locale, 'getByUserId');
        echo " - getByUserId: " . ($hasMethod ? '✓' : '✗');
    }
    echo "<br>";
}

echo "<hr>";
echo "<h2>5. Verifica Database</h2>";

try {
    if (class_exists('Database')) {
        $db = Database::getInstance();
        echo "✓ Connessione database OK<br>";
        
        $conn = $db->getConnection();
        $tables = $conn->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        echo "✓ Tabelle trovate: " . count($tables) . "<br>";
        echo "<ul>";
        foreach ($tables as $table) {
            echo "<li>{$table}</li>";
        }
        echo "</ul>";
    } else {
        echo "✗ Classe Database non disponibile<br>";
    }
} catch (Exception $e) {
    echo "✗ Errore connessione: " . $e->getMessage() . "<br>";
}

echo "<hr>";
echo "<h2>6. Verifica Directory</h2>";

$dirs = ['uploads', 'uploads/images', 'uploads/locali', 'uploads/piatti', 'uploads/qrcodes'];
foreach ($dirs as $dir) {
    $path = __DIR__ . '/' . $dir;
    $exists = is_dir($path);
    $writable = $exists ? is_writable($path) : false;
    echo ($exists ? '✓' : '✗') . " {$dir}";
    if ($exists) {
        echo " - scrivibile: " . ($writable ? '✓' : '✗');
    }
    echo "<br>";
}

echo "<hr>";
echo "<h2>7. Verifica Estensioni PHP</h2>";

$extensions = ['pdo', 'pdo_mysql', 'gd', 'json', 'mbstring'];
foreach ($extensions as $ext) {
    $loaded = extension_loaded($ext);
    echo ($loaded ? '✓' : '✗') . " {$ext}<br>";
}

echo "<hr>";
echo "<h3>Versione PHP: " . PHP_VERSION . "</h3>";

if (version_compare(PHP_VERSION, '7.4.0') >= 0) {
    echo "<p style='color: green; font-weight: bold;'>✓ Sistema pronto per l'uso!</p>";
} else {
    echo "<p style='color: red; font-weight: bold;'>✗ PHP 7.4+ richiesto</p>";
}
?>
