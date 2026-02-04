<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'Dashboard'; ?> - Menu Digitale</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
    <style>
        .badge-ordini-pending {
            position: absolute;
            top: 5px;
            right: 5px;
            background: #dc3545;
            border-radius: 10px;
            padding: 2px 6px;
            font-size: 0.7rem;
            animation: pulse 1s infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.6; }
        }
    </style>
</head>
<body>
    <?php
    if (!isset($user)) {
        $user = Helpers::getUser();
    }
    
    if (!class_exists('LocaleRestaurant')) {
        require_once __DIR__ . '/../../classes/models/LocaleRestaurant.php';
    }
    if (!class_exists('Menu')) {
        require_once __DIR__ . '/../../classes/models/Menu.php';
    }
    
    $localeModel = new LocaleRestaurant();
    $menuModel = new Menu();
    
    $locali = $localeModel->getByUserId($user['id']);
    
    $localeSelezionato = null;
    $localeId = intval($_GET['locale'] ?? 0);
    
    if ($localeId) {
        foreach ($locali as $loc) {
            if ($loc['id'] == $localeId) {
                $localeSelezionato = $loc;
                break;
            }
        }
    }
    
    if (!$localeSelezionato && !empty($locali)) {
        $localeSelezionato = $locali[0];
    }
    
    $menuSelezionato = null;
    $menuId = intval($_GET['menu'] ?? 0);
    $menuList = [];
    
    if ($localeSelezionato) {
        $menuList = $menuModel->getByLocaleId($localeSelezionato['id']);
        
        if ($menuId) {
            foreach ($menuList as $m) {
                if ($m['id'] == $menuId) {
                    $menuSelezionato = $m;
                    break;
                }
            }
        }
        
        if (!$menuSelezionato && !empty($menuList)) {
            $menuSelezionato = $menuList[0];
        }
    }
    
    $categoriaId = intval($_GET['categoria'] ?? 0);
    
    $ordiniInAttesa = 0;
    if ($localeSelezionato) {
        try {
            $sql = "SELECT COUNT(*) as count FROM ordini 
                    WHERE locale_id = :locale_id 
                    AND stato = 'attesa_conferma'";
            $stmt = Database::getInstance()->getConnection()->prepare($sql);
            $stmt->execute(['locale_id' => $localeSelezionato['id']]);
            $result = $stmt->fetch();
            $ordiniInAttesa = $result['count'] ?? 0;
        } catch (Exception $e) {
            $ordiniInAttesa = 0;
        }
    }
    ?>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top">
        <div class="container-fluid">
            <a class="navbar-brand" href="<?php echo BASE_URL; ?>/dashboard/">
                <i class="bi bi-qr-code"></i> Menu Digitale
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo BASE_URL; ?>/dashboard/">
                            <i class="bi bi-speedometer2"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo BASE_URL; ?>/dashboard/locali/">
                            <i class="bi bi-shop"></i> Locali
                        </a>
                    </li>
                    
                    <?php if ($localeSelezionato): ?>
                    <li class="nav-item position-relative">
                        <a class="nav-link" href="<?php echo BASE_URL; ?>/dashboard/ordini/?locale=<?php echo $localeSelezionato['id']; ?>">
                            <i class="bi bi-receipt"></i> Ordini
                            <?php if ($ordiniInAttesa > 0): ?>
                            <span class="badge-ordini-pending"><?php echo $ordiniInAttesa; ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo BASE_URL; ?>/dashboard/qrcode/?locale=<?php echo $localeSelezionato['id']; ?>">
                            <i class="bi bi-qr-code-scan"></i> QR Code
                        </a>
                    </li>
                    <?php endif; ?>
                    
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo BASE_URL; ?>/dashboard/stampa/">
                            <i class="bi bi-printer"></i> Stampa Menu
                        </a>
                    </li>
                    
                    <?php if ($localeSelezionato): ?>
                    <li class="nav-item">
						<a class="nav-link" href="<?php echo BASE_URL; ?>/upload-menu-multi.php?locale=<?php echo $localeSelezionato['id']; ?>">
							<i class="bi bi-magic"></i> Carica Menu AI
						</a>
					</li>
                    <?php endif; ?>
                </ul>
				
				<?php if ($localeSelezionato): ?>
                <div class="d-flex align-items-center me-3">
                    <div class="dropdown">
                        <button class="btn btn-outline-light btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <i class="bi bi-shop"></i> <?php echo htmlspecialchars($localeSelezionato['nome']); ?>
                        </button>
                        <ul class="dropdown-menu">
                            <?php foreach ($locali as $loc): ?>
                                <li>
                                    <a class="dropdown-item <?php echo $loc['id'] == $localeSelezionato['id'] ? 'active' : ''; ?>" 
                                       href="?locale=<?php echo $loc['id']; ?>">
                                        <?php echo htmlspecialchars($loc['nome']); ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/dashboard/locali/create.php"><i class="bi bi-plus-circle me-2"></i>Nuovo Locale</a></li>
                        </ul>
                    </div>

                    <?php if (!empty($menuList)): ?>
                    <div class="dropdown ms-2">
    <button class="btn btn-outline-info btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
        <i class="bi bi-journal-text"></i> 
        <?php echo $menuSelezionato ? htmlspecialchars($menuSelezionato['nome'] ?? '') : 'Seleziona Menu'; ?>
    </button>
    <ul class="dropdown-menu">
        <?php foreach ($menuList as $m): ?>
            <li>
                <a class="dropdown-item <?php echo $menuSelezionato && $m['id'] == $menuSelezionato['id'] ? 'active' : ''; ?>" 
                   href="?locale=<?php echo $localeSelezionato['id']; ?>&menu=<?php echo $m['id']; ?>">
                    <?php echo htmlspecialchars($m['nome'] ?? ''); ?>
                </a>
            </li>
        <?php endforeach; ?>
        <li><hr class="dropdown-divider"></li>
        <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/dashboard/menu/create.php?locale=<?php echo $localeSelezionato['id']; ?>">
            <i class="bi bi-plus-circle me-2"></i>Nuovo Menu
        </a></li>
        <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/dashboard/menu/?locale=<?php echo $localeSelezionato['id']; ?>">
            <i class="bi bi-list me-2"></i>Gestisci Menu
        </a></li>
    </ul>
</div>

                    <?php else: ?>
                    <a href="<?php echo BASE_URL; ?>/dashboard/menu/create.php?locale=<?php echo $localeSelezionato['id']; ?>" class="btn btn-sm btn-outline-info ms-2">
                        <i class="bi bi-plus-circle me-1"></i>Crea Menu
                    </a>
                    <?php endif; ?>

                    <?php if ($menuSelezionato): ?>
                    <div class="btn-group ms-2">
                        <a href="<?php echo BASE_URL; ?>/dashboard/menu/edit.php?id=<?php echo $menuSelezionato['id']; ?>" class="btn btn-sm btn-outline-warning" title="Modifica Menu">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <a href="<?php echo BASE_URL; ?>/dashboard/aspetto/?menu=<?php echo $menuSelezionato['id']; ?>" class="btn btn-sm btn-outline-secondary" title="Aspetto Menu">
                            <i class="bi bi-palette"></i>
                        </a>
                        <a href="<?php echo BASE_URL; ?>/dashboard/categorie/?menu=<?php echo $menuSelezionato['id']; ?>" class="btn btn-sm btn-outline-success" title="Categorie">
                            <i class="bi bi-list-ul"></i>
                        </a>
                    </div>
                    <?php endif; ?>

                    <?php if ($categoriaId > 0): ?>
                    <a href="<?php echo BASE_URL; ?>/dashboard/piatti/?categoria=<?php echo $categoriaId; ?>" class="btn btn-sm btn-outline-primary ms-2" title="Piatti">
                        <i class="bi bi-egg-fried"></i> Piatti
                    </a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($user['nome']); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <a class="dropdown-item" href="<?php echo BASE_URL; ?>/dashboard/profilo.php">
                                    <i class="bi bi-person me-2"></i>Profilo
                                </a>
                            </li>
                            <?php if ($localeSelezionato): ?>
                            <li>
                                <a class="dropdown-item" href="<?php echo BASE_URL; ?>/dashboard/ordini/settings.php?locale=<?php echo $localeSelezionato['id']; ?>">
                                    <i class="bi bi-sliders me-2"></i>Impostazioni Ordini
                                </a>
                            </li>
                            <?php endif; ?>
                            <li>
                                <a class="dropdown-item" href="<?php echo BASE_URL; ?>/dashboard/impostazioni.php">
                                    <i class="bi bi-gear me-2"></i>Impostazioni
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item" href="<?php echo BASE_URL; ?>/logout.php">
                                    <i class="bi bi-box-arrow-right me-2"></i>Logout
                                </a>
                            </li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid py-4">
        <?php 
        if (Helpers::hasFlashMessage()) {
            $flashMsg = Helpers::getFlashMessage();
            if ($flashMsg && isset($flashMsg['message']) && isset($flashMsg['type'])) {
        ?>
        <div class="alert alert-<?php echo htmlspecialchars($flashMsg['type']); ?> alert-dismissible fade show">
            <?php echo htmlspecialchars($flashMsg['message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php 
            }
        }
        ?>

        <?php if (isset($pageTitle)): ?>
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1><?php echo htmlspecialchars($pageTitle); ?></h1>
                <?php if (isset($pageActions)): ?>
                    <div>
                        <?php echo $pageActions; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

<script>
<?php if ($localeSelezionato): ?>
setInterval(function() {
    fetch('<?php echo BASE_URL; ?>/api/ordini/count-pending.php?locale=<?php echo $localeSelezionato['id']; ?>')
        .then(res => res.json())
        .then(data => {
            const badge = document.querySelector('.badge-ordini-pending');
            if (data.count > 0) {
                if (badge) {
                    badge.textContent = data.count;
                } else {
                    const link = document.querySelector('a[href*="dashboard/ordini"]');
                    if (link) {
                        const newBadge = document.createElement('span');
                        newBadge.className = 'badge-ordini-pending';
                        newBadge.textContent = data.count;
                        link.appendChild(newBadge);
                    }
                }
            } else if (badge) {
                badge.remove();
            }
        });
}, 30000);
<?php endif; ?>
</script>