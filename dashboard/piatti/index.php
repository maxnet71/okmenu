<?php
/**
 * Router Dashboard Piatti
 * Rileva dispositivo e redirect automatico a versione corretta
 * 
 * POSIZIONE: /dashboard/piatti/index.php
 * SOSTITUISCE: File esistente
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../classes/Database.php';
require_once __DIR__ . '/../../classes/Model.php';
require_once __DIR__ . '/../../classes/Helpers.php';
require_once __DIR__ . '/../../classes/DeviceDetector.php';
require_once __DIR__ . '/../../classes/models/User.php';

Helpers::requireLogin();

$user = Helpers::getUser();
$localeId = intval($_GET['locale'] ?? 0);
$action = $_GET['action'] ?? 'list';

// DEVICE DETECTION E ROUTING
DeviceDetector::init();

// Parametri da preservare
$queryParams = [];
if ($localeId) $queryParams['locale'] = $localeId;
if (isset($_GET['categoria'])) $queryParams['categoria'] = $_GET['categoria'];
if (isset($_GET['menu'])) $queryParams['menu'] = $_GET['menu'];

$queryString = !empty($queryParams) ? '?' . http_build_query($queryParams) : '';

// URL delle versioni
$desktopUrl = BASE_URL . '/dashboard/piatti/desktop.php' . $queryString;
$mobileUrl = BASE_URL . '/dashboard/mobile/piatti.php' . $queryString;

// Auto-redirect basato su dispositivo
$targetUrl = DeviceDetector::autoRedirect($desktopUrl, $mobileUrl);

// Redirect
header('Location: ' . $targetUrl);
exit;