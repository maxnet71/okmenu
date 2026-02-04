<?php
/**
 * Dashboard Ordini - Dettaglio
 * Visualizza dettaglio ordine e permette conferma/rifiuto
 * 
 * POSIZIONE: /dashboard/ordini/view.php
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../classes/Database.php';
require_once __DIR__ . '/../../classes/Model.php';
require_once __DIR__ . '/../../classes/Helpers.php';
require_once __DIR__ . '/../../classes/models/User.php';
require_once __DIR__ . '/../../classes/models/LocaleRestaurant.php';
require_once __DIR__ . '/../../classes/models/Ordine.php';

Helpers::requireLogin();

$user = Helpers::getUser();
$ordineModel = new Ordine();
$localeModel = new LocaleRestaurant();

// Ottieni ordine
$ordineId = intval($_GET['id'] ?? 0);
$ordine = $ordineModel->getWithDetails($ordineId);

if (!$ordine) {
    Helpers::redirect(BASE_URL . '/dashboard/ordini/');
}

// Verifica proprietà
$locale = $localeModel->getById($ordine['locale_id']);
if (!$locale || $locale['user_id'] != $user['id']) {
    Helpers::redirect(BASE_URL . '/dashboard/ordini/');
}

// Ottieni log stati
$sql = "SELECT * FROM ordini_stati_log WHERE ordine_id = :ordine_id ORDER BY created_at DESC";
$stmt = Database::getInstance()->getConnection()->prepare($sql);
$stmt->execute(['ordine_id' => $ordineId]);
$logStati = $stmt->fetchAll();

$pageTitle = 'Ordine #' . $ordine['numero_ordine'];
include __DIR__ . '/../includes/header.php';
?>

<style>
.stato-badge {
    font-size: 1.2em;
    padding: 0.5em 1em;
}
.timeline {
    position: relative;
    padding-left: 30px;
}
.timeline::before {
    content: '';
    position: absolute;
    left: 10px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: #ddd;
}
.timeline-item {
    position: relative;
    padding-bottom: 20px;
}
.timeline-item::before {
    content: '';
    position: absolute;
    left: -24px;
    top: 5px;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background: #007bff;
    border: 2px solid #fff;
    box-shadow: 0 0 0 2px #007bff;
}
.timer-grande {
    font-size: 2em;
    font-weight: bold;
}
.timer-critico {
    color: #dc3545;
    animation: pulse 1s infinite;
}
</style>

<div class="row mb-4">
    <div class="col-md-12">
        <a href="index.php?locale=<?php echo $locale['id']; ?>" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Torna alla lista
        </a>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <!-- Dettaglio Ordine -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0">
                    <i class="bi bi-receipt"></i> Ordine #<?php echo $ordine['numero_ordine']; ?>
                </h4>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <p class="mb-1"><strong>Cliente:</strong> <?php echo htmlspecialchars($ordine['nome_cliente']); ?></p>
                        <p class="mb-1"><strong>Telefono:</strong> <?php echo htmlspecialchars($ordine['telefono_cliente']); ?></p>
                        <?php if ($ordine['email_cliente']): ?>
                        <p class="mb-1"><strong>Email:</strong> <?php echo htmlspecialchars($ordine['email_cliente']); ?></p>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6 text-end">
                        <p class="mb-1"><strong>Data:</strong> <?php echo date('d/m/Y H:i', strtotime($ordine['created_at'])); ?></p>
                        <p class="mb-1"><strong>Tipo:</strong> 
                            <span class="badge bg-secondary">
                                <?php echo $ordine['tipo'] === 'asporto' ? 'Asporto' : 'Consegna'; ?>
                            </span>
                        </p>
                        <p class="mb-1"><strong>Stato:</strong> 
                            <span class="badge stato-badge bg-<?php 
                                echo match($ordine['stato']) {
                                    'attesa_conferma' => 'warning',
                                    'confermato' => 'success',
                                    'in_preparazione' => 'info',
                                    'pronto_ritiro' => 'primary',
                                    'in_consegna' => 'info',
                                    'completato' => 'secondary',
                                    'rifiutato', 'annullato' => 'danger',
                                    default => 'light'
                                };
                            ?>">
                                <?php echo ucfirst(str_replace('_', ' ', $ordine['stato'])); ?>
                            </span>
                        </p>
                    </div>
                </div>
                
                <?php if ($ordine['tipo'] === 'delivery' && $ordine['indirizzo_consegna']): ?>
                <div class="alert alert-info">
                    <strong><i class="bi bi-geo-alt"></i> Indirizzo Consegna:</strong><br>
                    <?php echo nl2br(htmlspecialchars($ordine['indirizzo_consegna'])); ?>
                </div>
                <?php endif; ?>
                
                <?php if ($ordine['note']): ?>
                <div class="alert alert-warning">
                    <strong><i class="bi bi-chat-left-text"></i> Note Cliente:</strong><br>
                    <?php echo nl2br(htmlspecialchars($ordine['note'])); ?>
                </div>
                <?php endif; ?>
                
                <!-- Dettagli Piatti -->
                <h5 class="border-bottom pb-2 mb-3">Dettagli Ordine</h5>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Piatto</th>
                            <th class="text-center">Q.tà</th>
                            <th class="text-end">Prezzo</th>
                            <th class="text-end">Totale</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($ordine['dettagli'] as $dettaglio): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($dettaglio['nome_piatto']); ?></strong>
                                <?php if (!empty($dettaglio['varianti'])): ?>
                                    <br><small class="text-muted">
                                        <?php foreach ($dettaglio['varianti'] as $variante): ?>
                                            + <?php echo htmlspecialchars($variante['nome_variante']); ?> 
                                            (+€<?php echo number_format($variante['prezzo'], 2, ',', '.'); ?>)<br>
                                        <?php endforeach; ?>
                                    </small>
                                <?php endif; ?>
                                <?php if ($dettaglio['note']): ?>
                                    <br><small class="text-info"><i class="bi bi-info-circle"></i> <?php echo htmlspecialchars($dettaglio['note']); ?></small>
                                <?php endif; ?>
                            </td>
                            <td class="text-center"><?php echo $dettaglio['quantita']; ?></td>
                            <td class="text-end">€<?php echo number_format($dettaglio['prezzo_unitario'], 2, ',', '.'); ?></td>
                            <td class="text-end">€<?php echo number_format($dettaglio['prezzo_unitario'] * $dettaglio['quantita'], 2, ',', '.'); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <?php if ($ordine['costi_aggiuntivi'] > 0): ?>
                        <tr>
                            <td colspan="3" class="text-end"><strong>Subtotale:</strong></td>
                            <td class="text-end">€<?php echo number_format($ordine['subtotale'], 2, ',', '.'); ?></td>
                        </tr>
                        <tr>
                            <td colspan="3" class="text-end"><strong>Costi Consegna:</strong></td>
                            <td class="text-end">€<?php echo number_format($ordine['costi_aggiuntivi'], 2, ',', '.'); ?></td>
                        </tr>
                        <?php endif; ?>
                        <tr class="table-primary">
                            <td colspan="3" class="text-end"><strong>TOTALE:</strong></td>
                            <td class="text-end"><strong>€<?php echo number_format($ordine['totale'], 2, ',', '.'); ?></strong></td>
                        </tr>
                    </tfoot>
                </table>
                
                <?php if ($ordine['pagamento_stato']): ?>
                <div class="alert alert-info">
                    <strong><i class="bi bi-credit-card"></i> Pagamento:</strong> 
                    <span class="badge bg-<?php 
                        echo match($ordine['pagamento_stato']) {
                            'authorized' => 'warning',
                            'captured' => 'success',
                            'voided' => 'secondary',
                            'failed' => 'danger',
                            default => 'light'
                        };
                    ?>">
                        <?php echo ucfirst($ordine['pagamento_stato']); ?>
                    </span>
                    <?php if ($ordine['pagamento_prenotato_at']): ?>
                        <br><small>Autorizzato: <?php echo date('d/m/Y H:i', strtotime($ordine['pagamento_prenotato_at'])); ?></small>
                    <?php endif; ?>
                    <?php if ($ordine['pagamento_catturato_at']): ?>
                        <br><small>Incassato: <?php echo date('d/m/Y H:i', strtotime($ordine['pagamento_catturato_at'])); ?></small>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Log Stati -->
        <?php if (!empty($logStati)): ?>
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-clock-history"></i> Cronologia</h5>
            </div>
            <div class="card-body">
                <div class="timeline">
                    <?php foreach ($logStati as $log): ?>
                    <div class="timeline-item">
                        <small class="text-muted"><?php echo date('d/m/Y H:i:s', strtotime($log['created_at'])); ?></small>
                        <p class="mb-0">
                            <strong><?php echo ucfirst(str_replace('_', ' ', $log['stato_nuovo'])); ?></strong>
                            <?php if ($log['note']): ?>
                                <br><small class="text-muted"><?php echo htmlspecialchars($log['note']); ?></small>
                            <?php endif; ?>
                        </p>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <div class="col-md-4">
        <!-- Azioni Rapide -->
        <div class="card mb-4">
            <div class="card-header bg-warning text-dark">
                <h5 class="mb-0"><i class="bi bi-lightning"></i> Azioni Rapide</h5>
            </div>
            <div class="card-body">
                <?php if ($ordine['stato'] === 'attesa_conferma'): ?>
                    <?php 
                    $scadenzaTimestamp = strtotime($ordine['scadenza_conferma']);
                    $oraTimestamp = time();
                    $scadenzaMinuti = round(($scadenzaTimestamp - $oraTimestamp) / 60);
                    ?>
                    
                    <div class="alert alert-danger text-center mb-4">
                        <div class="timer-grande <?php echo $scadenzaMinuti < 5 ? 'timer-critico' : ''; ?>">
                            <?php echo $scadenzaMinuti; ?> min
                        </div>
                        <small>Tempo rimanente</small>
                    </div>
                    
                    <form id="formConferma" method="POST" action="<?php echo BASE_URL; ?>/api/ordini/conferma.php">
                        <input type="hidden" name="csrf_token" value="<?php echo Helpers::generateCsrfToken(); ?>">
                        <input type="hidden" name="ordine_id" value="<?php echo $ordine['id']; ?>">
                        
                        <div class="mb-3">
                            <label class="form-label">Tempo preparazione (minuti)</label>
                            <input type="number" name="tempo_preparazione" class="form-control" value="30" min="10" max="180">
                        </div>
                        
                        <button type="button" onclick="confermaOrdine()" class="btn btn-success btn-lg w-100 mb-2">
                            <i class="bi bi-check-circle"></i> CONFERMA ORDINE
                        </button>
                    </form>
                    
                    <button type="button" class="btn btn-danger btn-lg w-100" data-bs-toggle="modal" data-bs-target="#modalRifiuta">
                        <i class="bi bi-x-circle"></i> RIFIUTA
                    </button>
                    
                <?php elseif ($ordine['stato'] === 'confermato'): ?>
                    <button onclick="cambiaStato('in_preparazione')" class="btn btn-info btn-lg w-100">
                        <i class="bi bi-hourglass-split"></i> Inizia Preparazione
                    </button>
                    
                <?php elseif ($ordine['stato'] === 'in_preparazione'): ?>
                    <?php if ($ordine['tipo'] === 'asporto'): ?>
                        <button onclick="cambiaStato('pronto_ritiro')" class="btn btn-primary btn-lg w-100">
                            <i class="bi bi-check2-circle"></i> Pronto per Ritiro
                        </button>
                    <?php else: ?>
                        <button onclick="cambiaStato('in_consegna')" class="btn btn-primary btn-lg w-100">
                            <i class="bi bi-truck"></i> Avvia Consegna
                        </button>
                    <?php endif; ?>
                    
                <?php elseif ($ordine['stato'] === 'pronto_ritiro' || $ordine['stato'] === 'in_consegna'): ?>
                    <button onclick="cambiaStato('completato')" class="btn btn-success btn-lg w-100">
                        <i class="bi bi-check-all"></i> Segna Completato
                    </button>
                    
                <?php elseif ($ordine['stato'] === 'completato'): ?>
                    <div class="alert alert-success text-center">
                        <i class="bi bi-check-circle display-4"></i>
                        <h5 class="mt-2">Ordine Completato</h5>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Info Aggiuntive -->
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">Informazioni</h6>
            </div>
            <div class="card-body">
                <p class="small mb-2">
                    <strong>Tracking Token:</strong><br>
                    <code><?php echo $ordine['tracking_token']; ?></code>
                </p>
                <p class="small mb-2">
                    <strong>Link Tracking Cliente:</strong><br>
                    <a href="<?php echo BASE_URL; ?>/ordine-tracking.php?token=<?php echo $ordine['tracking_token']; ?>" target="_blank" class="small">
                        Visualizza <i class="bi bi-box-arrow-up-right"></i>
                    </a>
                </p>
                <?php if ($ordine['confermato_at']): ?>
                <p class="small mb-0">
                    <strong>Confermato:</strong><br>
                    <?php echo date('d/m/Y H:i', strtotime($ordine['confermato_at'])); ?>
                </p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Modal Rifiuta -->
<div class="modal fade" id="modalRifiuta" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">Rifiuta Ordine</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="formRifiuta" method="POST" action="<?php echo BASE_URL; ?>/api/ordini/rifiuta.php">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo Helpers::generateCsrfToken(); ?>">
                    <input type="hidden" name="ordine_id" value="<?php echo $ordine['id']; ?>">
                    
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle"></i>
                        L'autorizzazione del pagamento verrà annullata e il cliente NON verrà addebitato.
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Motivo rifiuto *</label>
                        <select name="motivo" class="form-select mb-2" onchange="if(this.value==='altro') document.getElementById('motivoCustom').style.display='block'; else document.getElementById('motivoCustom').style.display='none';">
                            <option value="Troppi ordini in corso">Troppi ordini in corso</option>
                            <option value="Ingredienti non disponibili">Ingredienti non disponibili</option>
                            <option value="Chiusura anticipata">Chiusura anticipata</option>
                            <option value="Impossibile consegnare in zona">Impossibile consegnare in zona</option>
                            <option value="altro">Altro...</option>
                        </select>
                        <textarea id="motivoCustom" name="motivo_custom" class="form-control" rows="3" placeholder="Specifica il motivo..." style="display:none;"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                    <button type="button" onclick="rifiutaOrdine()" class="btn btn-danger">
                        <i class="bi bi-x-circle"></i> Conferma Rifiuto
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function confermaOrdine() {
    if (!confirm('Confermare l\'ordine? Il pagamento verrà incassato.')) return;
    
    const form = document.getElementById('formConferma');
    const formData = new FormData(form);
    
    fetch(form.action, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Ordine confermato con successo!');
            location.reload();
        } else {
            alert('Errore: ' + data.message);
        }
    })
    .catch(error => {
        alert('Errore di connessione');
        console.error(error);
    });
}

function rifiutaOrdine() {
    const form = document.getElementById('formRifiuta');
    const motivoSelect = form.querySelector('select[name="motivo"]');
    const motivoCustom = form.querySelector('textarea[name="motivo_custom"]');
    
    let motivo = motivoSelect.value;
    if (motivo === 'altro' && motivoCustom.value.trim()) {
        motivo = motivoCustom.value.trim();
    }
    
    if (!motivo || motivo === 'altro') {
        alert('Specifica un motivo per il rifiuto');
        return;
    }
    
    if (!confirm('Rifiutare definitivamente l\'ordine?')) return;
    
    const formData = new FormData(form);
    formData.set('motivo', motivo);
    
    fetch(form.action, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Ordine rifiutato. Il cliente è stato notificato.');
            location.reload();
        } else {
            alert('Errore: ' + data.message);
        }
    })
    .catch(error => {
        alert('Errore di connessione');
        console.error(error);
    });
}

function cambiaStato(nuovoStato) {
    if (!confirm('Cambiare lo stato dell\'ordine?')) return;
    
    const formData = new FormData();
    formData.append('csrf_token', '<?php echo Helpers::generateCsrfToken(); ?>');
    formData.append('ordine_id', '<?php echo $ordine['id']; ?>');
    formData.append('stato', nuovoStato);
    
    fetch('<?php echo BASE_URL; ?>/api/ordini/cambio-stato.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Errore: ' + data.message);
        }
    })
    .catch(error => {
        alert('Errore di connessione');
        console.error(error);
    });
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>