<?php
/**
 * Pagina Ordina - Carrello Cliente
 * Permette ai clienti di ordinare e pagare con Stripe
 * 
 * POSIZIONE: /public/ordina.php
 * URL: https://tuodominio.com/public/ordina.php?locale=X
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/Model.php';
require_once __DIR__ . '/../classes/Helpers.php';
require_once __DIR__ . '/../classes/models/LocaleRestaurant.php';
require_once __DIR__ . '/../classes/models/Menu.php';
require_once __DIR__ . '/../classes/models/Categoria.php';
require_once __DIR__ . '/../classes/models/Piatto.php';

$localeId = intval($_GET['locale'] ?? 0);

if (!$localeId) {
    die('Locale non specificato');
}

$localeModel = new LocaleRestaurant();
$locale = $localeModel->getById($localeId);

if (!$locale || !$locale['attivo']) {
    die('Locale non trovato o non attivo');
}

// Ottieni configurazione ordini
$sql = "SELECT * FROM ordini_configurazioni WHERE locale_id = :locale_id";
$stmt = Database::getInstance()->getConnection()->prepare($sql);
$stmt->execute(['locale_id' => $localeId]);
$config = $stmt->fetch();

if (!$config || !$config['ordini_attivi']) {
    die('Gli ordini sono temporaneamente disabilitati');
}

// Ottieni menu pubblicati
$menuModel = new Menu();
$categoriaModel = new Categoria();
$piattoModel = new Piatto();

$sql = "SELECT * FROM menu WHERE locale_id = :locale_id AND pubblicato = 1 ORDER BY ordinamento ASC LIMIT 1";
$stmt = Database::getInstance()->getConnection()->prepare($sql);
$stmt->execute(['locale_id' => $localeId]);
$menu = $stmt->fetch();

if (!$menu) {
    die('Nessun menu disponibile');
}

$categorie = $categoriaModel->getByMenuId($menu['id']);
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ordina da <?php echo htmlspecialchars($locale['nome']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <script src="https://js.stripe.com/v3/"></script>
    <style>
        .piatto-card {
            cursor: pointer;
            transition: all 0.3s;
            border: 2px solid transparent;
        }
        .piatto-card:hover {
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
            border-color: #007bff;
        }
        .piatto-card img {
            height: 200px;
            object-fit: cover;
        }
        .carrello-badge {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 1000;
        }
        .carrello-floating {
            position: fixed;
            bottom: 80px;
            right: 20px;
            width: 400px;
            max-height: 600px;
            overflow-y: auto;
            z-index: 999;
            box-shadow: 0 4px 20px rgba(0,0,0,0.2);
        }
        @media (max-width: 768px) {
            .carrello-floating {
                width: 90%;
                right: 5%;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="#">
                <?php if ($locale['logo']): ?>
                    <img src="<?php echo UPLOAD_URL . '/locali/' . $locale['logo']; ?>" alt="Logo" height="40">
                <?php endif; ?>
                <?php echo htmlspecialchars($locale['nome']); ?>
            </a>
            <span class="navbar-text text-white">
                <i class="bi bi-telephone"></i> <?php echo htmlspecialchars($locale['telefono']); ?>
            </span>
        </div>
    </nav>

    <div class="container my-5">
        <div class="row">
            <div class="col-md-8">
                <h2>Menu</h2>
                
                <!-- Tabs Categorie -->
                <ul class="nav nav-tabs mb-4" id="categorieTabs" role="tablist">
                    <?php foreach ($categorie as $index => $categoria): ?>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link <?php echo $index === 0 ? 'active' : ''; ?>" 
                                id="cat-<?php echo $categoria['id']; ?>-tab" 
                                data-bs-toggle="tab" 
                                data-bs-target="#cat-<?php echo $categoria['id']; ?>" 
                                type="button">
                            <?php echo htmlspecialchars($categoria['nome']); ?>
                        </button>
                    </li>
                    <?php endforeach; ?>
                </ul>

                <!-- Content Categorie -->
                <div class="tab-content" id="categorieContent">
                    <?php foreach ($categorie as $index => $categoria): 
                        $piatti = $piattoModel->getByCategoriaId($categoria['id']);
                    ?>
                    <div class="tab-pane fade <?php echo $index === 0 ? 'show active' : ''; ?>" 
                         id="cat-<?php echo $categoria['id']; ?>">
                        
                        <?php if ($categoria['descrizione']): ?>
                        <p class="text-muted"><?php echo htmlspecialchars($categoria['descrizione']); ?></p>
                        <?php endif; ?>
                        
                        <div class="row row-cols-1 row-cols-md-2 g-4">
                            <?php foreach ($piatti as $piatto): ?>
                            <?php if ($piatto['disponibile']): ?>
                            <div class="col">
                                <div class="card piatto-card h-100" onclick="aggiungiAlCarrello(<?php echo htmlspecialchars(json_encode($piatto), ENT_QUOTES); ?>)">
                                    <?php if ($piatto['immagine']): ?>
                                    <img src="<?php echo UPLOAD_URL . '/piatti/' . $piatto['immagine']; ?>" 
                                         class="card-img-top" 
                                         alt="<?php echo htmlspecialchars($piatto['nome']); ?>">
                                    <?php endif; ?>
                                    <div class="card-body">
                                        <h5 class="card-title"><?php echo htmlspecialchars($piatto['nome']); ?></h5>
                                        <?php if ($piatto['descrizione']): ?>
                                        <p class="card-text text-muted small"><?php echo htmlspecialchars($piatto['descrizione']); ?></p>
                                        <?php endif; ?>
                                        <?php if ($piatto['mostra_prezzo']): ?>
                                        <p class="h4 text-primary mb-0">€<?php echo number_format($piatto['prezzo'], 2, ',', '.'); ?></p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="col-md-4">
                <div class="sticky-top" style="top: 20px;">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="bi bi-cart3"></i> Il Tuo Ordine</h5>
                        </div>
                        <div class="card-body" id="carrelloContainer">
                            <p class="text-center text-muted">Il carrello è vuoto</p>
                        </div>
                        <div class="card-footer">
                            <div class="d-flex justify-content-between mb-3">
                                <strong>Totale:</strong>
                                <strong id="totaleCarrello">€0,00</strong>
                            </div>
                            <button id="btnProcedi" class="btn btn-success w-100" onclick="mostraCheckout()" disabled>
                                <i class="bi bi-credit-card"></i> Procedi al Pagamento
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Checkout -->
    <div class="modal fade" id="modalCheckout" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Completa il Tuo Ordine</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="formCheckout">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nome e Cognome *</label>
                                <input type="text" name="nome_cliente" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Telefono *</label>
                                <input type="tel" name="telefono_cliente" class="form-control" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Email *</label>
                            <input type="email" name="email_cliente" class="form-control" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Tipo Ordine *</label>
                            <select name="tipo" class="form-select" id="tipoOrdine" onchange="toggleIndirizzo()">
                                <?php if ($config['ordini_asporto_attivi']): ?>
                                <option value="asporto">Asporto</option>
                                <?php endif; ?>
                                <?php if ($config['ordini_consegna_attivi']): ?>
                                <option value="delivery">Consegna a Domicilio</option>
                                <?php endif; ?>
                            </select>
                        </div>
                        
                        <div id="indirizzoGroup" style="display:none;">
                            <div class="mb-3">
                                <label class="form-label">Indirizzo Completo *</label>
                                <textarea name="indirizzo_consegna" class="form-control" rows="3"></textarea>
                                <small class="text-muted">Via, numero civico, piano, citofono, città, CAP</small>
                            </div>
                            <?php if ($config['costo_consegna_fisso'] > 0): ?>
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle"></i> Costo consegna: €<?php echo number_format($config['costo_consegna_fisso'], 2, ',', '.'); ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Note (opzionale)</label>
                            <textarea name="note" class="form-control" rows="2" placeholder="Es: senza cipolle, allergie, ecc."></textarea>
                        </div>

                        <hr>

                        <h6>Riepilogo</h6>
                        <div id="riepilogoPiatti"></div>
                        <div class="d-flex justify-content-between mt-3">
                            <strong>Totale da Pagare:</strong>
                            <strong id="totaleCheckout">€0,00</strong>
                        </div>

                        <div class="alert alert-warning mt-3">
                            <i class="bi bi-info-circle"></i> L'importo verrà <strong>prenotato</strong> ma <strong>NON addebitato</strong> fino alla conferma del ristorante.
                        </div>

                        <!-- Stripe Payment Element -->
                        <div id="payment-element" class="mt-3"></div>
                        <div id="payment-message" class="text-danger mt-2"></div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                    <button type="button" id="btnConfermaOrdine" class="btn btn-success" onclick="confermaOrdine()">
                        <span id="btnText">Conferma e Paga</span>
                        <span id="btnSpinner" class="spinner-border spinner-border-sm d-none"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    let carrello = [];
    let stripe = null;
    let elements = null;
    let paymentIntent = null;

    // Inizializza Stripe
    const STRIPE_KEY = '<?php echo $config['stripe_publishable_key'] ?? ''; ?>';
    
    if (!STRIPE_KEY || STRIPE_KEY === '') {
        console.error('ERRORE: Chiave Stripe non configurata!');
        alert('ERRORE: Sistema pagamenti non configurato. Contatta il ristorante.');
    } else {
        try {
            stripe = Stripe(STRIPE_KEY);
            console.log('Stripe inizializzato correttamente');
        } catch (error) {
            console.error('Errore inizializzazione Stripe:', error);
            alert('Errore inizializzazione sistema pagamenti');
        }
    }

    function aggiungiAlCarrello(piatto) {
        const index = carrello.findIndex(item => item.piatto_id === piatto.id);
        
        if (index >= 0) {
            carrello[index].quantita++;
        } else {
            carrello.push({
                piatto_id: piatto.id,
                nome: piatto.nome,
                prezzo: parseFloat(piatto.prezzo),
                quantita: 1,
                varianti: []
            });
        }
        
        aggiornaCarrello();
    }

    function rimuoviDalCarrello(index) {
        carrello.splice(index, 1);
        aggiornaCarrello();
    }

    function modificaQuantita(index, delta) {
        carrello[index].quantita += delta;
        if (carrello[index].quantita <= 0) {
            rimuoviDalCarrello(index);
        } else {
            aggiornaCarrello();
        }
    }

    function aggiornaCarrello() {
        const container = document.getElementById('carrelloContainer');
        const btnProcedi = document.getElementById('btnProcedi');
        
        if (carrello.length === 0) {
            container.innerHTML = '<p class="text-center text-muted">Il carrello è vuoto</p>';
            btnProcedi.disabled = true;
            document.getElementById('totaleCarrello').textContent = '€0,00';
            return;
        }
        
        let html = '<div class="list-group list-group-flush">';
        let totale = 0;
        
        carrello.forEach((item, index) => {
            const subtotale = item.prezzo * item.quantita;
            totale += subtotale;
            
            html += `
                <div class="list-group-item px-0">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1">
                            <strong>${item.nome}</strong><br>
                            <small class="text-muted">€${item.prezzo.toFixed(2)}</small>
                        </div>
                        <div class="text-end">
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-outline-secondary" onclick="modificaQuantita(${index}, -1)">-</button>
                                <button class="btn btn-outline-secondary" disabled>${item.quantita}</button>
                                <button class="btn btn-outline-secondary" onclick="modificaQuantita(${index}, 1)">+</button>
                            </div>
                            <div class="mt-1">
                                <strong>€${subtotale.toFixed(2)}</strong>
                                <button class="btn btn-sm btn-link text-danger p-0 ms-2" onclick="rimuoviDalCarrello(${index})">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        });
        
        html += '</div>';
        container.innerHTML = html;
        document.getElementById('totaleCarrello').textContent = '€' + totale.toFixed(2).replace('.', ',');
        btnProcedi.disabled = false;
    }

    function toggleIndirizzo() {
        const tipo = document.getElementById('tipoOrdine').value;
        const gruppo = document.getElementById('indirizzoGroup');
        gruppo.style.display = tipo === 'delivery' ? 'block' : 'none';
    }

    async function mostraCheckout() {
        const modal = new bootstrap.Modal(document.getElementById('modalCheckout'));
        
        // Riepilogo
        let html = '';
        let totale = 0;
        carrello.forEach(item => {
            const subtotale = item.prezzo * item.quantita;
            totale += subtotale;
            html += `<div class="d-flex justify-content-between mb-1">
                <span>${item.quantita}x ${item.nome}</span>
                <span>€${subtotale.toFixed(2)}</span>
            </div>`;
        });
        document.getElementById('riepilogoPiatti').innerHTML = html;
        document.getElementById('totaleCheckout').textContent = '€' + totale.toFixed(2).replace('.', ',');
        
        // Inizializza Stripe Payment Element
        if (stripe) {
            // Crea Payment Intent sul server
            try {
                console.log('Chiamata API create-payment-intent...');
                const response = await fetch('<?php echo BASE_URL; ?>/api/ordini/create-payment-intent.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        locale_id: <?php echo $localeId; ?>,
                        carrello: carrello,
                        totale: totale
                    })
                });
                
                console.log('Response status:', response.status);
                
                if (!response.ok) {
                    const errorText = await response.text();
                    console.error('Risposta errore:', errorText);
                    throw new Error('Errore HTTP ' + response.status + ': ' + errorText.substring(0, 100));
                }
                
                const responseText = await response.text();
                console.log('Response text:', responseText);
                
                let data;
                try {
                    data = JSON.parse(responseText);
                } catch (e) {
                    console.error('Errore parsing JSON:', e);
                    console.error('Testo ricevuto:', responseText);
                    throw new Error('Risposta non valida dal server (non JSON)');
                }
                
                console.log('Data ricevuta:', data);
                
                if (!data.success) {
                    throw new Error(data.error || 'Errore sconosciuto');
                }
                
                if (!data.client_secret) {
                    throw new Error('Client secret mancante nella risposta');
                }
                
                paymentIntent = data;
                
                elements = stripe.elements({clientSecret: data.client_secret});
                const paymentElement = elements.create('payment');
                paymentElement.mount('#payment-element');
                
                console.log('✅ Payment Element montato con successo');
                
            } catch (error) {
                console.error('❌ Errore creazione Payment Intent:', error);
                alert('Errore inizializzazione pagamento: ' + error.message + '\n\nContatta il ristorante.');
                return;
            }
        }
        
        modal.show();
        toggleIndirizzo();
    }

    async function confermaOrdine() {
        const form = document.getElementById('formCheckout');
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }
        
        // Verifica Stripe sia inizializzato
        if (!stripe || !elements) {
            alert('Errore: Sistema pagamenti non disponibile. Ricarica la pagina.');
            return;
        }
        
        const btn = document.getElementById('btnConfermaOrdine');
        const btnText = document.getElementById('btnText');
        const btnSpinner = document.getElementById('btnSpinner');
        
        btn.disabled = true;
        btnText.classList.add('d-none');
        btnSpinner.classList.remove('d-none');
        
        try {
            // Conferma pagamento con Stripe
            const {error} = await stripe.confirmPayment({
                elements,
                confirmParams: {
                    return_url: '<?php echo BASE_URL; ?>/public/ordine-success.php',
                },
                redirect: 'if_required'
            });
            
            if (error) {
                document.getElementById('payment-message').textContent = error.message;
                throw new Error(error.message);
            }
            
            // Crea ordine sul server
            const formData = new FormData(form);
            formData.append('locale_id', <?php echo $localeId; ?>);
            formData.append('carrello', JSON.stringify(carrello));
            formData.append('payment_intent_id', paymentIntent.id);
            
            const response = await fetch('<?php echo BASE_URL; ?>/api/ordini/create.php', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                window.location.href = '<?php echo BASE_URL; ?>/public/ordine-tracking.php?token=' + result.tracking_token;
            } else {
                throw new Error(result.message);
            }
            
        } catch (err) {
            alert('Errore: ' + err.message);
        } finally {
            btn.disabled = false;
            btnText.classList.remove('d-none');
            btnSpinner.classList.add('d-none');
        }
    }
    </script>
</body>
</html>