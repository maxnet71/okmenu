<?php
/**
 * CRON JOB: Verifica Ordini Scaduti
 * Gestisce ordini in attesa che hanno superato il tempo scadenza
 * 
 * POSIZIONE: /cron/verifica-ordini-scaduti.php
 * 
 * CONFIGURAZIONE CRON (ogni 5 minuti):
 * */5 * * * * /usr/bin/php /path/to/okmenu/cron/verifica-ordini-scaduti.php >> /var/log/okmenu-cron.log 2>&1
 */

// Previeni accesso via browser
if (php_sapi_name() !== 'cli') {
    // Accetta solo richieste con token segreto
    $cronToken = $_GET['token'] ?? '';
    $expectedToken = getenv('CRON_SECRET_TOKEN') ?: 'CHANGE_ME_IN_PRODUCTION';
    
    if ($cronToken !== $expectedToken) {
        http_response_code(403);
        die('Access denied');
    }
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/Model.php';
require_once __DIR__ . '/../classes/models/Ordine.php';
require_once __DIR__ . '/../classes/models/Pagamento.php';
require_once __DIR__ . '/../classes/NotificationManager.php';

// Log inizio esecuzione
$startTime = microtime(true);
echo "[" . date('Y-m-d H:i:s') . "] Inizio verifica ordini scaduti\n";

try {
    $ordineModel = new Ordine();
    
    // Gestisci ordini scaduti
    $ordiniGestiti = $ordineModel->gestisciOrdiniScaduti();
    
    // Log risultato
    $executionTime = round(microtime(true) - $startTime, 2);
    echo "[" . date('Y-m-d H:i:s') . "] Completato: {$ordiniGestiti} ordini gestiti in {$executionTime}s\n";
    
    // Opzionale: invia promemoria per ordini in scadenza (5 minuti prima)
    $ordiniInScadenza = $ordineModel->getOrdiniInScadenza(5);
    
    if (!empty($ordiniInScadenza)) {
        $notificationManager = new NotificationManager();
        foreach ($ordiniInScadenza as $ordine) {
            $notificationManager->inviaPromemoriaScadenza($ordine);
        }
        echo "[" . date('Y-m-d H:i:s') . "] Inviati " . count($ordiniInScadenza) . " promemoria scadenza\n";
    }
    
    exit(0);
    
} catch (Exception $e) {
    echo "[" . date('Y-m-d H:i:s') . "] ERRORE: " . $e->getMessage() . "\n";
    error_log("Cron ordini scaduti errore: " . $e->getMessage());
    exit(1);
}