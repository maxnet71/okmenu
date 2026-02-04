<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu Digitale - Sistema di Gestione</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="bi bi-qr-code"></i> Menu Digitale
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="#funzionalita">Funzionalità</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#prezzi">Prezzi</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="login.php">Accedi</a>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-primary ms-2" href="register.php">Registrati</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <section class="hero-section text-white py-5">
        <div class="container py-5">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h1 class="display-4 fw-bold mb-4">Il Menu Digitale per il Tuo Locale</h1>
                    <p class="lead mb-4">
                        Crea menu digitali professionali con QR Code. Aggiorna in tempo reale, 
                        gestisci ordini e prenotazioni, tutto da un unico pannello di controllo.
                    </p>
                    <div class="d-flex gap-3">
                        <a href="register.php" class="btn btn-primary btn-lg">
                            <i class="bi bi-rocket-takeoff"></i> Inizia Gratis
                        </a>
                        <a href="#demo" class="btn btn-outline-light btn-lg">
                            <i class="bi bi-play-circle"></i> Guarda Demo
                        </a>
                    </div>
                </div>
                <div class="col-lg-6 text-center">
                    <img src="assets/images/hero-menu.svg" alt="Menu Digitale" class="img-fluid">
                </div>
            </div>
        </div>
    </section>

    <section id="funzionalita" class="py-5">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="display-5 fw-bold">Funzionalità Complete</h2>
                <p class="lead text-muted">Tutto ciò di cui hai bisogno per digitalizzare il tuo locale</p>
            </div>

            <div class="row g-4">
                <div class="col-md-6 col-lg-4">
                    <div class="feature-card card h-100 border-0 shadow-sm">
                        <div class="card-body p-4">
                            <div class="feature-icon bg-primary text-white rounded-circle mb-3">
                                <i class="bi bi-qr-code fs-3"></i>
                            </div>
                            <h4 class="card-title">QR Code Personalizzati</h4>
                            <p class="card-text text-muted">
                                Genera QR code per menu, tavoli e asporto. Stampa o condividi online.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-lg-4">
                    <div class="feature-card card h-100 border-0 shadow-sm">
                        <div class="card-body p-4">
                            <div class="feature-icon bg-success text-white rounded-circle mb-3">
                                <i class="bi bi-globe fs-3"></i>
                            </div>
                            <h4 class="card-title">Multi-Lingua</h4>
                            <p class="card-text text-muted">
                                Traduci il tuo menu in oltre 60 lingue per clienti internazionali.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-lg-4">
                    <div class="feature-card card h-100 border-0 shadow-sm">
                        <div class="card-body p-4">
                            <div class="feature-icon bg-warning text-white rounded-circle mb-3">
                                <i class="bi bi-exclamation-triangle fs-3"></i>
                            </div>
                            <h4 class="card-title">Allergeni</h4>
                            <p class="card-text text-muted">
                                Gestione automatica dei 14 allergeni obbligatori con icone chiare.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-lg-4">
                    <div class="feature-card card h-100 border-0 shadow-sm">
                        <div class="card-body p-4">
                            <div class="feature-icon bg-info text-white rounded-circle mb-3">
                                <i class="bi bi-cart fs-3"></i>
                            </div>
                            <h4 class="card-title">Ordini Online</h4>
                            <p class="card-text text-muted">
                                Ricevi ordini da tavolo, asporto e delivery direttamente dal menu.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-lg-4">
                    <div class="feature-card card h-100 border-0 shadow-sm">
                        <div class="card-body p-4">
                            <div class="feature-icon bg-danger text-white rounded-circle mb-3">
                                <i class="bi bi-calendar-check fs-3"></i>
                            </div>
                            <h4 class="card-title">Prenotazioni</h4>
                            <p class="card-text text-muted">
                                Sistema di prenotazione tavoli integrato con conferma automatica.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-lg-4">
                    <div class="feature-card card h-100 border-0 shadow-sm">
                        <div class="card-body p-4">
                            <div class="feature-icon bg-secondary text-white rounded-circle mb-3">
                                <i class="bi bi-graph-up fs-3"></i>
                            </div>
                            <h4 class="card-title">Statistiche</h4>
                            <p class="card-text text-muted">
                                Analizza visualizzazioni, ordini e fatturato con grafici dettagliati.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="prezzi" class="py-5 bg-light">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="display-5 fw-bold">Piani Tariffari</h2>
                <p class="lead text-muted">Scegli il piano perfetto per il tuo locale</p>
            </div>

            <div class="row g-4">
                <div class="col-lg-4">
                    <div class="card h-100 border-0 shadow">
                        <div class="card-body p-4">
                            <h3 class="card-title">Free</h3>
                            <div class="display-4 fw-bold my-3">0€</div>
                            <p class="text-muted">per sempre</p>
                            <hr>
                            <ul class="list-unstyled">
                                <li class="mb-2"><i class="bi bi-check-circle text-success"></i> 1 Menu</li>
                                <li class="mb-2"><i class="bi bi-check-circle text-success"></i> QR Code illimitati</li>
                                <li class="mb-2"><i class="bi bi-check-circle text-success"></i> Categorie illimitate</li>
                                <li class="mb-2"><i class="bi bi-check-circle text-success"></i> Allergeni</li>
                                <li class="mb-2"><i class="bi bi-x-circle text-muted"></i> Ordini online</li>
                                <li class="mb-2"><i class="bi bi-x-circle text-muted"></i> Prenotazioni</li>
                            </ul>
                            <a href="register.php" class="btn btn-outline-primary w-100 mt-3">Inizia Gratis</a>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card h-100 border-primary shadow-lg">
                        <div class="card-header bg-primary text-white text-center py-3">
                            <h5 class="mb-0">PIÙ POPOLARE</h5>
                        </div>
                        <div class="card-body p-4">
                            <h3 class="card-title">Pro</h3>
                            <div class="display-4 fw-bold my-3">29€</div>
                            <p class="text-muted">al mese</p>
                            <hr>
                            <ul class="list-unstyled">
                                <li class="mb-2"><i class="bi bi-check-circle text-success"></i> Menu illimitati</li>
                                <li class="mb-2"><i class="bi bi-check-circle text-success"></i> Tutto del piano Free</li>
                                <li class="mb-2"><i class="bi bi-check-circle text-success"></i> Ordini online</li>
                                <li class="mb-2"><i class="bi bi-check-circle text-success"></i> Prenotazioni</li>
                                <li class="mb-2"><i class="bi bi-check-circle text-success"></i> Statistiche avanzate</li>
                                <li class="mb-2"><i class="bi bi-check-circle text-success"></i> Supporto prioritario</li>
                            </ul>
                            <a href="register.php" class="btn btn-primary w-100 mt-3">Prova 30 Giorni</a>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card h-100 border-0 shadow">
                        <div class="card-body p-4">
                            <h3 class="card-title">Enterprise</h3>
                            <div class="display-4 fw-bold my-3">Custom</div>
                            <p class="text-muted">su misura</p>
                            <hr>
                            <ul class="list-unstyled">
                                <li class="mb-2"><i class="bi bi-check-circle text-success"></i> Tutto del piano Pro</li>
                                <li class="mb-2"><i class="bi bi-check-circle text-success"></i> Multi-sede</li>
                                <li class="mb-2"><i class="bi bi-check-circle text-success"></i> API personalizzate</li>
                                <li class="mb-2"><i class="bi bi-check-circle text-success"></i> Branding personalizzato</li>
                                <li class="mb-2"><i class="bi bi-check-circle text-success"></i> Account manager dedicato</li>
                                <li class="mb-2"><i class="bi bi-check-circle text-success"></i> Formazione staff</li>
                            </ul>
                            <a href="contatti.php" class="btn btn-outline-primary w-100 mt-3">Contattaci</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="cta-section py-5 bg-primary text-white">
        <div class="container text-center">
            <h2 class="display-5 fw-bold mb-4">Pronto a Digitalizzare il Tuo Menu?</h2>
            <p class="lead mb-4">Inizia gratuitamente, nessuna carta di credito richiesta</p>
            <a href="register.php" class="btn btn-light btn-lg px-5">
                <i class="bi bi-rocket-takeoff"></i> Inizia Ora
            </a>
        </div>
    </section>

    <footer class="py-4 bg-dark text-white-50">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <p class="mb-0">&copy; 2025 Menu Digitale. Tutti i diritti riservati.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <a href="#" class="text-white-50 text-decoration-none me-3">Privacy</a>
                    <a href="#" class="text-white-50 text-decoration-none me-3">Termini</a>
                    <a href="#" class="text-white-50 text-decoration-none">Contatti</a>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="assets/js/main.js"></script>
</body>
</html>
