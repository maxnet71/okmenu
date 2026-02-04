<?php
/**
 * SYNC STRIPE KEYS - Sincronizza chiavi da config.php al database
 * Carica le chiavi Stripe definite in config.php nella tabella ordini_configurazioni
 * 
 * POSIZIONE: /sync-stripe-keys.php
 * URL: https://www.trendpronostici.it/okmenu/sync-stripe-keys.php?locale=X
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/classes/Database.php';

$localeId = intval($_GET['locale'] ?? 0);

echo "<h1>Sync Stripe Keys</h1>";
echo "<p>Sincronizza chiavi da config.php al database</p>";
echo "<hr>";

if (!$localeId) {
    echo "<p style='color:red'>❌ Specifica un locale: ?locale=ID</p>";
    echo "<p>Esempio: sync-stripe-keys.php?locale=1</p>";
    exit;
}

echo "<h3>1. Verifica config.php</h3>";

// Verifica modalità
$testMode = defined('STRIPE_TEST_MODE') ? STRIPE_TEST_MODE : true;
echo "Modalità: <strong>" . ($testMode ? 'TEST' : 'LIVE (PRODUZIONE)') . "</strong><br>";

// Ottieni chiavi da config.php
if ($testMode) {
    $publishableKey = defined('STRIPE_TEST_PUBLISHABLE_KEY') ? STRIPE_TEST_PUBLISHABLE_KEY : '';
    $secretKey = defined('STRIPE_TEST_SECRET_KEY') ? STRIPE_TEST_SECRET_KEY : '';
    $webhookSecret = defined('STRIPE_WEBHOOK_SECRET_TEST') ? STRIPE_WEBHOOK_SECRET_TEST : '';
} else {
    $publishableKey = defined('STRIPE_LIVE_PUBLISHABLE_KEY') ? STRIPE_LIVE_PUBLISHABLE_KEY : '';
    $secretKey = defined('STRIPE_LIVE_SECRET_KEY') ? STRIPE_LIVE_SECRET_KEY : '';
    $webhookSecret = defined('STRIPE_WEBHOOK_SECRET_LIVE') ? STRIPE_WEBHOOK_SECRET_LIVE : '';
}

echo "<br><strong>Chiavi trovate in config.php:</strong><br>";

if (empty($publishableKey)) {
    echo "❌ <strong>PUBLISHABLE KEY MANCANTE!</strong><br>";
    echo "<p style='background:#f8d7da;padding:15px;border-radius:5px;'>";
    echo "Devi aggiungere le chiavi in /config/config.php:<br><br>";
    echo "<code>define('STRIPE_TEST_PUBLISHABLE_KEY', 'pk_test_...');</code><br>";
    echo "<code>define('STRIPE_TEST_SECRET_KEY', 'sk_test_...');</code><br>";
    echo "</p>";
    exit;
} else {
    echo "✅ Publishable Key: " . substr($publishableKey, 0, 20) . "...<br>";
}

if (empty($secretKey)) {
    echo "❌ <strong>SECRET KEY MANCANTE!</strong><br>";
    exit;
} else {
    echo "✅ Secret Key: " . substr($secretKey, 0, 15) . "... (nascosta)<br>";
}

if (empty($webhookSecret)) {
    echo "⚠️ Webhook Secret: NON configurato (opzionale, ma consigliato)<br>";
} else {
    echo "✅ Webhook Secret: configurato<br>";
}

echo "<hr>";

// Aggiorna database
echo "<h3>2. Aggiornamento Database</h3>";

$db = Database::getInstance()->getConnection();

// Verifica se configurazione esiste
$sql = "SELECT * FROM ordini_configurazioni WHERE locale_id = :locale_id";
$stmt = $db->prepare($sql);
$stmt->execute(['locale_id' => $localeId]);
$config = $stmt->fetch();

if (!$config) {
    echo "<p style='color:orange'>⚠️ Nessuna configurazione trovata per locale $localeId</p>";
    echo "<p>Creazione nuova configurazione...</p>";
    
    // Crea configurazione base
    $sql = "INSERT INTO ordini_configurazioni 
            (locale_id, ordini_attivi, ordini_asporto_attivi, ordini_consegna_attivi,
             tempo_scadenza_conferma, max_ordini_simultanei, tempo_preparazione_default,
             gateway_pagamento, modalita_test,
             stripe_publishable_key, stripe_secret_key, stripe_webhook_secret)
            VALUES 
            (:locale_id, 1, 1, 1, 15, 50, 30, 'stripe', :modalita_test,
             :publishable_key, :secret_key, :webhook_secret)";
    
    $stmt = $db->prepare($sql);
    $result = $stmt->execute([
        'locale_id' => $localeId,
        'modalita_test' => $testMode ? 1 : 0,
        'publishable_key' => $publishableKey,
        'secret_key' => $secretKey,
        'webhook_secret' => $webhookSecret
    ]);
    
    if ($result) {
        echo "✅ Configurazione creata con successo!<br>";
    } else {
        echo "❌ Errore creazione configurazione<br>";
        exit;
    }
} else {
    echo "✅ Configurazione esistente trovata<br>";
    echo "Aggiornamento chiavi Stripe...<br>";
    
    // Aggiorna solo le chiavi Stripe
    $sql = "UPDATE ordini_configurazioni 
            SET stripe_publishable_key = :publishable_key,
                stripe_secret_key = :secret_key,
                stripe_webhook_secret = :webhook_secret,
                modalita_test = :modalita_test,
                gateway_pagamento = 'stripe'
            WHERE locale_id = :locale_id";
    
    $stmt = $db->prepare($sql);
    $result = $stmt->execute([
        'locale_id' => $localeId,
        'modalita_test' => $testMode ? 1 : 0,
        'publishable_key' => $publishableKey,
        'secret_key' => $secretKey,
        'webhook_secret' => $webhookSecret
    ]);
    
    if ($result) {
        echo "✅ Chiavi aggiornate con successo!<br>";
    } else {
        echo "❌ Errore aggiornamento chiavi<br>";
        exit;
    }
}

echo "<hr>";

// Verifica finale
echo "<h3>3. Verifica Finale</h3>";

$stmt = $db->prepare("SELECT * FROM ordini_configurazioni WHERE locale_id = :locale_id");
$stmt->execute(['locale_id' => $localeId]);
$configAggiornato = $stmt->fetch();

echo "<strong>Configurazione nel database:</strong><br>";
echo "- Locale ID: {$configAggiornato['locale_id']}<br>";
echo "- Gateway: {$configAggiornato['gateway_pagamento']}<br>";
echo "- Modalità Test: " . ($configAggiornato['modalita_test'] ? 'SI' : 'NO') . "<br>";
echo "- Publishable Key: " . substr($configAggiornato['stripe_publishable_key'], 0, 20) . "...<br>";
echo "- Secret Key: " . (strlen($configAggiornato['stripe_secret_key']) > 0 ? '✅ Presente' : '❌ Mancante') . "<br>";
echo "- Webhook Secret: " . (strlen($configAggiornato['stripe_webhook_secret']) > 0 ? '✅ Presente' : '⚠️ Non configurato') . "<br>";

echo "<hr>";

echo "<h3>✅ SINCRONIZZAZIONE COMPLETATA!</h3>";
echo "<p style='background:#d4edda;padding:20px;border:2px solid #28a745;border-radius:10px;'>";
echo "<strong>Le chiavi Stripe sono state caricate nel database!</strong><br><br>";
echo "<strong>Prossimi passi:</strong><br>";
echo "1. Vai su: <a href='" . BASE_URL . "/public/ordina.php?locale=$localeId'>Pagina Ordini</a><br>";
echo "2. Apri Console Browser (F12)<br>";
echo "3. Verifica che non ci siano errori 'chiave non configurata'<br>";
echo "4. Prova a procedere al checkout<br>";
echo "</p>";

echo "<hr>";

echo "<h3>🧪 Test Veloce</h3>";
echo "<p>Testa subito la configurazione:</p>";
echo "<a href='" . BASE_URL . "/test-stripe.php?locale=$localeId' class='btn btn-primary' target='_blank'>Test Stripe</a> ";
echo "<a href='" . BASE_URL . "/public/ordina.php?locale=$localeId' class='btn btn-success' target='_blank'>Prova Ordine</a>";

echo "<style>
body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
code { background: #f4f4f4; padding: 2px 6px; border-radius: 3px; }
.btn { display: inline-block; padding: 10px 20px; text-decoration: none; border-radius: 5px; color: white; margin: 5px; }
.btn-primary { background: #007bff; }
.btn-success { background: #28a745; }
</style>";