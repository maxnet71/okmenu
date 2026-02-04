# TROUBLESHOOTING - Menu Digitale

## ERRORE: Call to undefined method Locale::getByUserId()

### CAUSA
Il file `classes/models/Locale.php` non è stato caricato correttamente o l'autoloader non funziona.

### SOLUZIONE

#### 1. VERIFICA FILE
Accedi via FTP e controlla che esistano:
```
/okmenu/classes/models/Locale.php
/okmenu/autoload.php
/okmenu/config/config.php
```

#### 2. USA SCRIPT DI VERIFICA
Carica `check.php` nella root e accedi a:
```
https://www.trendpronostici.it/okmenu/check.php
```
Ti mostrerà tutti i problemi.

#### 3. VERIFICA AUTOLOAD
Apri `dashboard/index.php` e aggiungi all'inizio (dopo require config):
```php
echo '<pre>';
print_r(get_declared_classes());
echo '</pre>';
exit;
```

Se non vedi "Locale" nell'elenco, il problema è l'autoload.

#### 4. FIX TEMPORANEO
Nel file `dashboard/index.php`, aggiungi PRIMA di usare Locale:
```php
require_once __DIR__ . '/../classes/models/Locale.php';
require_once __DIR__ . '/../classes/models/Menu.php';
require_once __DIR__ . '/../classes/models/Ordine.php';
require_once __DIR__ . '/../classes/models/Statistica.php';
```

#### 5. CONFIGURA DATABASE
Nel file `config/config.php` imposta:
```php
define('DB_HOST', 'localhost'); // o IP database
define('DB_NAME', 'tuo_database');
define('DB_USER', 'tuo_user');
define('DB_PASS', 'tua_password');
```

#### 6. IMPORTA DATABASE
Vai su phpMyAdmin e importa:
```
database/menu_digitale.sql
```

#### 7. PERMESSI DIRECTORY
Crea e imposta permessi:
```bash
mkdir -p uploads/images uploads/locali uploads/piatti uploads/qrcodes
chmod -R 777 uploads/
```

## ERRORI COMUNI

### "Session already started"
**Fix**: Nel `config/config.php` è già corretto con:
```php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
```

### "Database connection failed"
**Fix**: Verifica credenziali in `config/config.php`

### "Cannot write to uploads"
**Fix**: `chmod -R 777 uploads/`

### "Class not found"
**Fix**: Verifica che `autoload.php` sia richiamato in ogni file

## FILE DA CARICARE PRIMA

1. `config/config.php` - CONFIGURALO!
2. `autoload.php`
3. `classes/Database.php`
4. `classes/Model.php`
5. `classes/Helpers.php`
6. Tutti i file in `classes/models/`
7. Database SQL

## QUICK FIX DASHBOARD

Sostituisci l'intero file `dashboard/index.php` con questo:

```php
<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../autoload.php';

// FIX: Richiedi manualmente i model
require_once __DIR__ . '/../classes/models/User.php';
require_once __DIR__ . '/../classes/models/Locale.php';
require_once __DIR__ . '/../classes/models/Menu.php';
require_once __DIR__ . '/../classes/models/Ordine.php';
require_once __DIR__ . '/../classes/models/Statistica.php';

Helpers::requireLogin();

$user = Helpers::getUser();
$localeModel = new Locale();
$menuModel = new Menu();

// Verifica che il metodo esista
if (!method_exists($localeModel, 'getByUserId')) {
    die('ERRORE: Metodo getByUserId non trovato. Ri-carica il file Locale.php');
}

$locali = $localeModel->getByUserId($user['id']);
$totaleLocali = count($locali);

// ... resto del codice
?>
```

## VERIFICA RAPIDA

Crea un file `test.php` nella root:
```php
<?php
require_once 'config/config.php';
require_once 'autoload.php';

echo "Autoload: OK<br>";

$locale = new Locale();
echo "Classe Locale: OK<br>";

if (method_exists($locale, 'getByUserId')) {
    echo "Metodo getByUserId: OK<br>";
} else {
    echo "Metodo getByUserId: MANCANTE!<br>";
}
```

Accedi a `https://www.trendpronostici.it/okmenu/test.php`

## SUPPORTO

Se il problema persiste:
1. Scarica di nuovo il file `classes/models/Locale.php` dallo ZIP
2. Ricaricalo via FTP
3. Controlla permessi file (644)
4. Svuota cache PHP se presente
