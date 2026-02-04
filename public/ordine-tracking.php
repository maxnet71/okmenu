<?php
/**
 * Tracking Ordine Pubblico
 * Permette ai clienti di tracciare il loro ordine
 * 
 * POSIZIONE: /public/ordine-tracking.php
 * URL: https://tuodominio.com/public/ordine-tracking.php?token=XXX
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/Model.php';
require_once __DIR__ . '/../classes/models/Ordine.php';
require_once __DIR__ . '/../classes/models/LocaleRestaurant.php';

$token = $_GET['token'] ?? '';

if (!$token) {
    die('Token non valido');
}

$ordineModel = new Ordine();
$ordine = $ordineModel->getByTrackingToken($token);

if (!$ordine) {
    die('Ordine non trovato');
}

$ordine = $ordineModel->getWithDetails($ordine['id']);

$localeModel = new LocaleRestaurant();
$locale = $localeModel->getById($ordine['locale_id']);

// Mappa stati
$statiMap = [
    'creato' => ['label' => 'Ordine Creato', 'icon' => 'bi-receipt', 'class' => 'info'],
    'pagamento_autorizzato' => ['label' => 'Pagamento Autorizzato', 'icon' => 'bi-credit-card', 'class' => 'info'],
    'attesa_conferma' => ['label' => 'In Attesa Conferma', 'icon' => 'bi-clock', 'class' => 'warning'],
    'confermato' => ['label' => 'Confermato', 'icon' => 'bi-check-circle', 'class' => 'success'],
    'rifiutato' => ['label' => 'Rifiutato', 'icon' => 'bi-x-circle', 'class' => 'danger'],
    'scaduto' => ['label' => 'Scaduto', 'icon' => 'bi-clock-history', 'class' => 'secondary'],
    'in_preparazione' => ['label' => 'In Preparazione', 'icon' => 'bi-hourglass-split', 'class' => 'info'],
    'pronto_ritiro' => ['label' => 'Pronto per il Ritiro', 'icon' => 'bi-bag-check', 'class' => 'primary'],
    'in_consegna' => ['label' => 'In Consegna', 'icon' => 'bi-truck', 'class' => 'info'],
    'completato' => ['label' => 'Completato', 'icon' => 'bi-check-all', 'class' => 'success'],
    'annullato' => ['label' => 'Annullato', 'icon' => 'bi-x-circle', 'class' => 'danger']
];

$statoCorrente = $statiMap[$ordine['stato']] ?? ['label' => 'Sconosciuto', 'icon' => 'bi-question', 'class' => 'secondary'];

// Timeline stati
$sql = "SELECT * FROM ordini_stati_log WHERE ordine_id = :ordine_id ORDER BY created_at ASC";
$stmt = Database::getInstance()->getConnection()->prepare($sql);
$stmt->execute(['ordine_id' => $ordine['id']]);
$timeline = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tracking Ordine #<?php echo $ordine['numero_ordine']; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <meta http-equiv="refresh" content="30">
    <style>
        .stato-badge-grande {
            font-size: 1.5em;
            padding: 1em 2em;
        }
        .timeline-vertical {
            position: relative;
            padding-left: 50px;
        }
        .timeline-vertical::before {
            content: '';
            position: absolute;
            left: 20px;
            top: 0;
            bottom: 0;
            width: 3px;
            background: #ddd;
        }
        .timeline-item {
            position: relative;
            padding-bottom: 30px;
        }
        .timeline-item::before {
            content: '';
            position: absolute;
            left: -36px;
            top: 8px;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: #007bff;
            border: 3px solid #fff;
            box-shadow: 0 0 0 3px #007bff;
        }
        .timeline-item.active::before {
            background: #28a745;
            box-shadow: 0 0 0 3px #28a745;
        }
    </style>
</head>
<body class="bg-light">
    <!-- Header -->
    <nav class="navbar navbar-dark bg-dark">
        <div class="container">
            <span class="navbar-brand">
                <?php if ($locale['logo']): ?>
                    <img src="<?php echo UPLOAD_URL . '/locali/' . $locale['logo']; ?>" alt="Logo" height="40">
                <?php endif; ?>
                <?php echo htmlspecialchars($locale['nome']); ?>
            </span>
        </div>
    </nav>

    <div class="container my-5">
        <!-- Stato Attuale -->
        <div class="card mb-4">
            <div class="card-body text-center py-5">
                <i class="<?php echo $statoCorrente['icon']; ?> display-1 text-<?php echo $statoCorrente['class']; ?>"></i>
                <h2 class="mt-3"><?php echo $statoCorrente['label']; ?></h2>
                <p class="lead">Ordine #<?php echo $ordine['numero_ordine']; ?></p>
                <span class="badge stato-badge-grande bg-<?php echo $statoCorrente['class']; ?>">
                    <?php echo $statoCorrente['label']; ?>
                </span>
            </div>
        </div>

        <div class="row">
            <div class="col-md-8">
                <!-- Dettagli Ordine -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-receipt"></i> Dettagli Ordine</h5>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <p class="mb-1"><strong>Tipo:</strong> <?php echo $ordine['tipo'] === 'asporto' ? 'Asporto' : 'Consegna'; ?></p>
                                <p class="mb-1"><strong>Data:</strong> <?php echo date('d/m/Y H:i', strtotime($ordine['created_at'])); ?></p>
                            </div>
                            <div class="col-md-6 text-end">
                                <h3 class="text-primary mb-0">€<?php echo number_format($ordine['totale'], 2, ',', '.'); ?></h3>
                                <small class="text-muted">Totale</small>
                            </div>
                        </div>

                        <?php if ($ordine['tipo'] === 'delivery' && $ordine['indirizzo_consegna']): ?>
                        <div class="alert alert-info">
                            <strong><i class="bi bi-geo-alt"></i> Indirizzo Consegna:</strong><br>
                            <?php echo nl2br(htmlspecialchars($ordine['indirizzo_consegna'])); ?>
                        </div>
                        <?php endif; ?>

                        <h6 class="border-bottom pb-2 mb-3">Piatti Ordinati</h6>
                        <table class="table table-sm">
                            <tbody>
                                <?php foreach ($ordine['dettagli'] as $dettaglio): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($dettaglio['nome_piatto']); ?></strong>
                                        <?php if ($dettaglio['note']): ?>
                                            <br><small class="text-muted"><?php echo htmlspecialchars($dettaglio['note']); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center"><?php echo $dettaglio['quantita']; ?>x</td>
                                    <td class="text-end">€<?php echo number_format($dettaglio['prezzo_unitario'] * $dettaglio['quantita'], 2, ',', '.'); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>

                        <?php if ($ordine['note']): ?>
                        <div class="alert alert-secondary">
                            <strong>Note:</strong> <?php echo nl2br(htmlspecialchars($ordine['note'])); ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Timeline -->
                <?php if (!empty($timeline)): ?>
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-clock-history"></i> Cronologia</h5>
                    </div>
                    <div class="card-body">
                        <div class="timeline-vertical">
                            <?php foreach ($timeline as $index => $log): 
                                $isLast = ($index === count($timeline) - 1);
                                $stato = $statiMap[$log['stato_nuovo']] ?? null;
                            ?>
                            <div class="timeline-item <?php echo $isLast ? 'active' : ''; ?>">
                                <p class="mb-1">
                                    <?php if ($stato): ?>
                                        <i class="<?php echo $stato['icon']; ?> text-<?php echo $stato['class']; ?>"></i>
                                    <?php endif; ?>
                                    <strong><?php echo ucfirst(str_replace('_', ' ', $log['stato_nuovo'])); ?></strong>
                                </p>
                                <small class="text-muted"><?php echo date('d/m/Y H:i:s', strtotime($log['created_at'])); ?></small>
                                <?php if ($log['note']): ?>
                                    <br><small class="text-muted"><?php echo htmlspecialchars($log['note']); ?></small>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <div class="col-md-4">
                <!-- Info Pagamento -->
                <?php if ($ordine['pagamento_stato']): ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="bi bi-credit-card"></i> Pagamento</h6>
                    </div>
                    <div class="card-body">
                        <p class="mb-2">
                            <strong>Stato:</strong>
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
                        </p>

                        <?php if ($ordine['pagamento_stato'] === 'authorized'): ?>
                        <div class="alert alert-warning small">
                            <i class="bi bi-info-circle"></i> Il pagamento è stato autorizzato ma non ancora addebitato. L'addebito avverrà solo dopo la conferma del ristorante.
                        </div>
                        <?php elseif ($ordine['pagamento_stato'] === 'captured'): ?>
                        <div class="alert alert-success small">
                            <i class="bi bi-check-circle"></i> Pagamento completato con successo il <?php echo date('d/m/Y H:i', strtotime($ordine['pagamento_catturato_at'])); ?>
                        </div>
                        <?php elseif ($ordine['pagamento_stato'] === 'voided'): ?>
                        <div class="alert alert-secondary small">
                            <i class="bi bi-x-circle"></i> L'autorizzazione del pagamento è stata annullata. Non sei stato addebitato.
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Contatti -->
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="bi bi-telephone"></i> Contatti</h6>
                    </div>
                    <div class="card-body">
                        <?php if ($locale['telefono']): ?>
                        <p class="mb-2">
                            <i class="bi bi-telephone"></i>
                            <a href="tel:<?php echo $locale['telefono']; ?>"><?php echo htmlspecialchars($locale['telefono']); ?></a>
                        </p>
                        <?php endif; ?>
                        <?php if ($locale['email']): ?>
                        <p class="mb-2">
                            <i class="bi bi-envelope"></i>
                            <a href="mailto:<?php echo $locale['email']; ?>"><?php echo htmlspecialchars($locale['email']); ?></a>
                        </p>
                        <?php endif; ?>
                        <?php if ($locale['whatsapp']): ?>
                        <p class="mb-0">
                            <i class="bi bi-whatsapp"></i>
                            <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $locale['whatsapp']); ?>" target="_blank">WhatsApp</a>
                        </p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="text-center mt-4">
            <small class="text-muted">Pagina aggiornata automaticamente ogni 30 secondi</small>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>