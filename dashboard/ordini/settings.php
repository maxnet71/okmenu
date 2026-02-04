<?php
/**
 * Dashboard Settings Ordini
 * Configurazione modulo ordini per locale
 * 
 * POSIZIONE: /dashboard/ordini/settings.php
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../classes/Database.php';
require_once __DIR__ . '/../../classes/Model.php';
require_once __DIR__ . '/../../classes/Helpers.php';
require_once __DIR__ . '/../../classes/models/User.php';
require_once __DIR__ . '/../../classes/models/LocaleRestaurant.php';

Helpers::requireLogin();

$user = Helpers::getUser();
$localeModel = new LocaleRestaurant();

$localeId = intval($_GET['locale'] ?? 0);
$locali = $localeModel->getByUserId($user['id']);

if (empty($locali)) {
    Helpers::redirect(BASE_URL . '/dashboard/locali/create.php');
}

if (!$localeId) {
    $localeId = $locali[0]['id'];
}

$locale = $localeModel->getById($localeId);
if (!$locale || $locale['user_id'] != $user['id']) {
    Helpers::redirect(BASE_URL . '/dashboard/ordini/settings.php');
}

$db = Database::getInstance()->getConnection();

// Ottieni configurazione esistente
$sql = "SELECT * FROM ordini_configurazioni WHERE locale_id = :locale_id";
$stmt = $db->prepare($sql);
$stmt->execute(['locale_id' => $localeId]);
$config = $stmt->fetch();

// Gestione form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'locale_id' => $localeId,
        'ordini_attivi' => isset($_POST['ordini_attivi']) ? 1 : 0,
        'ordini_asporto_attivi' => isset($_POST['ordini_asporto_attivi']) ? 1 : 0,
        'ordini_consegna_attivi' => isset($_POST['ordini_consegna_attivi']) ? 1 : 0,
        'tempo_scadenza_conferma' => intval($_POST['tempo_scadenza_conferma'] ?? 15),
        'max_ordini_simultanei' => intval($_POST['max_ordini_simultanei'] ?? 50),
        'tempo_preparazione_default' => intval($_POST['tempo_preparazione_default'] ?? 30),
        'costo_consegna_fisso' => floatval($_POST['costo_consegna_fisso'] ?? 0),
        'costo_consegna_per_km' => floatval($_POST['costo_consegna_per_km'] ?? 0),
        'distanza_max_consegna_km' => floatval($_POST['distanza_max_consegna_km'] ?? 10),
        'ordine_minimo_consegna' => floatval($_POST['ordine_minimo_consegna'] ?? 0),
        'messaggio_rifiuto_default' => Helpers::sanitizeInput($_POST['messaggio_rifiuto_default'] ?? ''),
        'gateway_pagamento' => $_POST['gateway_pagamento'] ?? 'stripe',
        'modalita_test' => isset($_POST['modalita_test']) ? 1 : 0
    ];
    
    if ($config) {
        // Update
        $sql = "UPDATE ordini_configurazioni SET 
                ordini_attivi = :ordini_attivi,
                ordini_asporto_attivi = :ordini_asporto_attivi,
                ordini_consegna_attivi = :ordini_consegna_attivi,
                tempo_scadenza_conferma = :tempo_scadenza_conferma,
                max_ordini_simultanei = :max_ordini_simultanei,
                tempo_preparazione_default = :tempo_preparazione_default,
                costo_consegna_fisso = :costo_consegna_fisso,
                costo_consegna_per_km = :costo_consegna_per_km,
                distanza_max_consegna_km = :distanza_max_consegna_km,
                ordine_minimo_consegna = :ordine_minimo_consegna,
                messaggio_rifiuto_default = :messaggio_rifiuto_default,
                gateway_pagamento = :gateway_pagamento,
                modalita_test = :modalita_test
                WHERE locale_id = :locale_id";
    } else {
        // Insert
        $sql = "INSERT INTO ordini_configurazioni 
                (locale_id, ordini_attivi, ordini_asporto_attivi, ordini_consegna_attivi, 
                tempo_scadenza_conferma, max_ordini_simultanei, tempo_preparazione_default,
                costo_consegna_fisso, costo_consegna_per_km, distanza_max_consegna_km,
                ordine_minimo_consegna, messaggio_rifiuto_default, gateway_pagamento, modalita_test)
                VALUES 
                (:locale_id, :ordini_attivi, :ordini_asporto_attivi, :ordini_consegna_attivi,
                :tempo_scadenza_conferma, :max_ordini_simultanei, :tempo_preparazione_default,
                :costo_consegna_fisso, :costo_consegna_per_km, :distanza_max_consegna_km,
                :ordine_minimo_consegna, :messaggio_rifiuto_default, :gateway_pagamento, :modalita_test)";
    }
    
    $stmt = $db->prepare($sql);
    $stmt->execute($data);
    
    Helpers::setFlashMessage('Configurazione salvata con successo!', 'success');
    Helpers::redirect($_SERVER['PHP_SELF'] . '?locale=' . $localeId);
}

// Default values
if (!$config) {
    $config = [
        'ordini_attivi' => 0,
        'ordini_asporto_attivi' => 1,
        'ordini_consegna_attivi' => 1,
        'tempo_scadenza_conferma' => 15,
        'max_ordini_simultanei' => 50,
        'tempo_preparazione_default' => 30,
        'costo_consegna_fisso' => 0,
        'costo_consegna_per_km' => 0,
        'distanza_max_consegna_km' => 10,
        'ordine_minimo_consegna' => 0,
        'messaggio_rifiuto_default' => 'Siamo spiacenti ma non possiamo evadere il tuo ordine.',
        'gateway_pagamento' => 'stripe',
        'modalita_test' => 1
    ];
}

$pageTitle = 'Impostazioni Ordini';
include __DIR__ . '/../includes/header.php';
?>

<div class="row">
    <div class="col-md-8">
        <form method="POST">
            <!-- Stato Ordini -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-toggle-on"></i> Stato Ordini</h5>
                </div>
                <div class="card-body">
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" name="ordini_attivi" id="ordiniAttivi" 
                               <?php echo $config['ordini_attivi'] ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="ordiniAttivi">
                            <strong>Ordini Attivi</strong>
                            <br><small class="text-muted">Abilita/disabilita completamente il modulo ordini</small>
                        </label>
                    </div>
                    
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" name="ordini_asporto_attivi" id="asportoAttivi"
                               <?php echo $config['ordini_asporto_attivi'] ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="asportoAttivi">
                            <strong>Ordini Asporto</strong>
                        </label>
                    </div>
                    
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="ordini_consegna_attivi" id="consegnaAttivi"
                               <?php echo $config['ordini_consegna_attivi'] ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="consegnaAttivi">
                            <strong>Ordini Consegna</strong>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Configurazione Ordini -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-sliders"></i> Configurazione</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Tempo Conferma (minuti)</label>
                            <input type="number" name="tempo_scadenza_conferma" class="form-control" 
                                   value="<?php echo $config['tempo_scadenza_conferma']; ?>" min="5" max="60">
                            <small class="text-muted">Tempo massimo per confermare ordine</small>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Ordini Simultanei Max</label>
                            <input type="number" name="max_ordini_simultanei" class="form-control" 
                                   value="<?php echo $config['max_ordini_simultanei']; ?>" min="1">
                            <small class="text-muted">Numero massimo ordini in preparazione</small>
                        </div>
                        
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Tempo Preparazione Default (minuti)</label>
                            <input type="number" name="tempo_preparazione_default" class="form-control" 
                                   value="<?php echo $config['tempo_preparazione_default']; ?>" min="10">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Consegna -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-truck"></i> Consegna</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Costo Consegna Fisso (€)</label>
                            <input type="number" name="costo_consegna_fisso" class="form-control" 
                                   value="<?php echo $config['costo_consegna_fisso']; ?>" step="0.01" min="0">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Costo per KM (€)</label>
                            <input type="number" name="costo_consegna_per_km" class="form-control" 
                                   value="<?php echo $config['costo_consegna_per_km']; ?>" step="0.01" min="0">
                            <small class="text-muted">Lascia 0 se fisso</small>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Distanza Max (KM)</label>
                            <input type="number" name="distanza_max_consegna_km" class="form-control" 
                                   value="<?php echo $config['distanza_max_consegna_km']; ?>" step="0.1" min="0">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Ordine Minimo Consegna (€)</label>
                            <input type="number" name="ordine_minimo_consegna" class="form-control" 
                                   value="<?php echo $config['ordine_minimo_consegna']; ?>" step="0.01" min="0">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Messaggi -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-chat-text"></i> Messaggi</h5>
                </div>
                <div class="card-body">
                    <label class="form-label">Messaggio Rifiuto Default</label>
                    <textarea name="messaggio_rifiuto_default" class="form-control" rows="3"><?php echo htmlspecialchars($config['messaggio_rifiuto_default']); ?></textarea>
                </div>
            </div>

            <!-- Pagamenti -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-credit-card"></i> Pagamenti</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Gateway Pagamento</label>
                        <select name="gateway_pagamento" class="form-select">
                            <option value="stripe" <?php echo $config['gateway_pagamento'] === 'stripe' ? 'selected' : ''; ?>>Stripe</option>
                            <option value="paypal" <?php echo $config['gateway_pagamento'] === 'paypal' ? 'selected' : ''; ?>>PayPal</option>
                        </select>
                    </div>
                    
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="modalita_test" id="modalitaTest"
                               <?php echo $config['modalita_test'] ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="modalitaTest">
                            <strong>Modalità Test</strong>
                            <br><small class="text-muted">Usa credenziali test per pagamenti (NON addebitare carte reali)</small>
                        </label>
                    </div>
                    
                    <div class="alert alert-warning mt-3">
                        <i class="bi bi-exclamation-triangle"></i> 
                        Le chiavi API Stripe vanno configurate in <code>/config/config.php</code>
                    </div>
                </div>
            </div>

            <div class="text-end">
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="bi bi-save"></i> Salva Configurazione
                </button>
            </div>
        </form>
    </div>

    <div class="col-md-4">
        <!-- Info -->
        <div class="card mb-4">
            <div class="card-header bg-info text-white">
                <h6 class="mb-0"><i class="bi bi-info-circle"></i> Informazioni</h6>
            </div>
            <div class="card-body">
                <p class="small mb-2"><strong>Stato Attuale:</strong></p>
                <span class="badge bg-<?php echo $config['ordini_attivi'] ? 'success' : 'secondary'; ?> mb-3">
                    <?php echo $config['ordini_attivi'] ? 'Ordini Attivi' : 'Ordini Disattivi'; ?>
                </span>
                
                <p class="small mb-1">Con ordini attivi, i clienti che scansionano QR code con flag "Abilita Ordini" vedranno il pulsante "Ordina Ora".</p>
            </div>
        </div>

        <!-- Link Utili -->
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-link-45deg"></i> Link Utili</h6>
            </div>
            <div class="card-body">
                <a href="<?php echo BASE_URL; ?>/dashboard/ordini/?locale=<?php echo $localeId; ?>" class="btn btn-sm btn-outline-primary w-100 mb-2">
                    <i class="bi bi-receipt"></i> Gestione Ordini
                </a>
                <a href="<?php echo BASE_URL; ?>/dashboard/qrcode/?locale=<?php echo $localeId; ?>" class="btn btn-sm btn-outline-secondary w-100">
                    <i class="bi bi-qr-code"></i> QR Code
                </a>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>