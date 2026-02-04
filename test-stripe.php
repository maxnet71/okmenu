<?php
/**
 * TEST CONFIGURAZIONE STRIPE
 * Verifica che le chiavi Stripe siano configurate correttamente
 * 
 * POSIZIONE: /test-stripe.php
 * URL: https://tuodominio.com/okmenu/test-stripe.php?locale=X
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/classes/Database.php';

$localeId = intval($_GET['locale'] ?? 0);

echo "<h1>Test Configurazione Stripe</h1>";
echo "<p>Locale ID testato: <strong>$localeId</strong></p>";
echo "<hr>";

if (!$localeId) {
    echo "<p style='color:red'>❌ Specifica un locale: ?locale=ID</p>";
    exit;
}

$db = Database::getInstance()->getConnection();

// Ottieni configurazione
$sql = "SELECT * FROM ordini_configurazioni WHERE locale_id = :locale_id";
$stmt = $db->prepare($sql);
$stmt->execute(['locale_id' => $localeId]);
$config = $stmt->fetch();

if (!$config) {
    echo "<p style='color:red'>❌ Nessuna configurazione ordini per locale $localeId</p>";
    echo "<p>👉 Vai su: Dashboard → Ordini → Settings e salva la configurazione</p>";
    exit;
}

echo "<h3>1. Configurazione Database</h3>";
echo "✅ Configurazione trovata<br>";
echo "- Gateway: <strong>{$config['gateway_pagamento']}</strong><br>";
echo "- Modalità Test: <strong>" . ($config['modalita_test'] ? 'SI' : 'NO') . "</strong><br>";
echo "- Stripe Publishable Key (DB): " . (empty($config['stripe_publishable_key']) ? '❌ MANCANTE' : '✅ ' . substr($config['stripe_publishable_key'], 0, 20) . '...') . "<br>";
echo "- Stripe Secret Key (DB): " . (empty($config['stripe_secret_key']) ? '❌ MANCANTE' : '✅ Presente (nascosta)') . "<br>";
echo "<hr>";

echo "<h3>2. Costanti Config.php</h3>";

$testMode = $config['modalita_test'];

if ($testMode) {
    echo "<p><strong>Modalità: TEST</strong></p>";
    
    if (defined('STRIPE_TEST_PUBLISHABLE_KEY')) {
        echo "✅ STRIPE_TEST_PUBLISHABLE_KEY definita<br>";
        echo "   Valore: " . substr(STRIPE_TEST_PUBLISHABLE_KEY, 0, 20) . "...<br>";
    } else {
        echo "❌ STRIPE_TEST_PUBLISHABLE_KEY NON definita in config.php<br>";
    }
    
    if (defined('STRIPE_TEST_SECRET_KEY')) {
        echo "✅ STRIPE_TEST_SECRET_KEY definita<br>";
    } else {
        echo "❌ STRIPE_TEST_SECRET_KEY NON definita in config.php<br>";
    }
} else {
    echo "<p><strong>Modalità: LIVE (PRODUZIONE)</strong></p>";
    
    if (defined('STRIPE_LIVE_PUBLISHABLE_KEY')) {
        echo "✅ STRIPE_LIVE_PUBLISHABLE_KEY definita<br>";
        echo "   Valore: " . substr(STRIPE_LIVE_PUBLISHABLE_KEY, 0, 20) . "...<br>";
    } else {
        echo "❌ STRIPE_LIVE_PUBLISHABLE_KEY NON definita in config.php<br>";
    }
    
    if (defined('STRIPE_LIVE_SECRET_KEY')) {
        echo "✅ STRIPE_LIVE_SECRET_KEY definita<br>";
    } else {
        echo "❌ STRIPE_LIVE_SECRET_KEY NON definita in config.php<br>";
    }
}

echo "<hr>";

echo "<h3>3. Chiave Usata dalla Pagina Ordina</h3>";
$stripeKey = $config['stripe_publishable_key'] ?? '';

if (empty($stripeKey)) {
    echo "<p style='background:#f8d7da;padding:15px;border:2px solid #dc3545;border-radius:5px;'>";
    echo "❌ <strong>PROBLEMA TROVATO!</strong><br><br>";
    echo "Il campo <code>stripe_publishable_key</code> nella tabella <code>ordini_configurazioni</code> è VUOTO.<br><br>";
    echo "<strong>SOLUZIONE:</strong><br>";
    echo "1. Aggiungi le chiavi Stripe in /config/config.php:<br>";
    echo "<pre style='background:#000;color:#0f0;padding:10px;'>";
    echo "define('STRIPE_TEST_PUBLISHABLE_KEY', 'pk_test_...');\n";
    echo "define('STRIPE_TEST_SECRET_KEY', 'sk_test_...');\n";
    echo "</pre><br>";
    echo "2. Vai su Dashboard → Ordini → Settings<br>";
    echo "3. Salva nuovamente la configurazione (carica automaticamente le chiavi da config.php)<br>";
    echo "</p>";
} else {
    echo "✅ Chiave Publishable trovata: " . substr($stripeKey, 0, 20) . "...<br>";
    
    // Verifica formato chiave
    if ($testMode && !str_starts_with($stripeKey, 'pk_test_')) {
        echo "<p style='color:orange'>⚠️ ATTENZIONE: Sei in modalità TEST ma la chiave non inizia con 'pk_test_'</p>";
    } elseif (!$testMode && !str_starts_with($stripeKey, 'pk_live_')) {
        echo "<p style='color:orange'>⚠️ ATTENZIONE: Sei in modalità LIVE ma la chiave non inizia con 'pk_live_'</p>";
    } else {
        echo "✅ Formato chiave corretto<br>";
    }
}

echo "<hr>";

echo "<h3>4. Test JavaScript Stripe</h3>";
echo "<div id='stripe-test'></div>";
echo "<script src='https://js.stripe.com/v3/'></script>";
echo "<script>";
echo "const stripeKey = '" . htmlspecialchars($stripeKey) . "';";
echo "const testDiv = document.getElementById('stripe-test');";
echo "if (!stripeKey || stripeKey === '') {";
echo "  testDiv.innerHTML = '<p style=\"color:red\">❌ Chiave Stripe vuota!</p>';";
echo "} else {";
echo "  try {";
echo "    const stripe = Stripe(stripeKey);";
echo "    testDiv.innerHTML = '<p style=\"color:green\">✅ Stripe inizializzato correttamente in JavaScript!</p>';";
echo "  } catch (error) {";
echo "    testDiv.innerHTML = '<p style=\"color:red\">❌ Errore inizializzazione Stripe: ' + error.message + '</p>';";
echo "  }";
echo "}";
echo "</script>";

echo "<hr>";

echo "<h3>5. Riepilogo</h3>";

$problemi = [];

if (empty($stripeKey)) {
    $problemi[] = "Chiave Stripe publishable mancante nel database";
}

if ($testMode) {
    if (!defined('STRIPE_TEST_SECRET_KEY')) {
        $problemi[] = "STRIPE_TEST_SECRET_KEY non definita in config.php";
    }
} else {
    if (!defined('STRIPE_LIVE_SECRET_KEY')) {
        $problemi[] = "STRIPE_LIVE_SECRET_KEY non definita in config.php";
    }
}

if (empty($problemi)) {
    echo "<p style='background:#d4edda;padding:20px;border:2px solid #28a745;border-radius:10px;'>";
    echo "✅✅✅ <strong>TUTTO OK!</strong><br><br>";
    echo "La configurazione Stripe è corretta.<br>";
    echo "Se continui ad avere errori:<br>";
    echo "1. Svuota cache browser (CTRL+F5)<br>";
    echo "2. Verifica console JavaScript (F12) per errori<br>";
    echo "3. Prova a creare un ordine di test<br>";
    echo "</p>";
} else {
    echo "<p style='background:#f8d7da;padding:20px;border:2px solid #dc3545;border-radius:10px;'>";
    echo "❌ <strong>PROBLEMI TROVATI:</strong><br><ul>";
    foreach ($problemi as $problema) {
        echo "<li>$problema</li>";
    }
    echo "</ul></p>";
}

echo "<hr>";
echo "<p><small>Per supporto, consulta la documentazione Stripe: <a href='https://stripe.com/docs' target='_blank'>https://stripe.com/docs</a></small></p>";