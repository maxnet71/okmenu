<?php
require_once __DIR__ . '/../../config/config.php';

require_once __DIR__ . '/../../classes/Database.php';
require_once __DIR__ . '/../../classes/Model.php';
require_once __DIR__ . '/../../classes/Helpers.php';
require_once __DIR__ . '/../../classes/models/User.php';
require_once __DIR__ . '/../../classes/models/LocaleRestaurant.php';
require_once __DIR__ . '/../../classes/models/QRCode.php';

header('Content-Type: application/json');

if (!Helpers::isLoggedIn()) {
    Helpers::json(['success' => false, 'message' => 'Non autorizzato'], 401);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Helpers::json(['success' => false, 'message' => 'Metodo non consentito'], 405);
}

$id = intval($_POST['id'] ?? 0);

if (!$id) {
    Helpers::json(['success' => false, 'message' => 'ID non valido']);
}

$user = Helpers::getUser();
$qrcodeModel = new QRCode();
$qrcode = $qrcodeModel->getById($id);

if (!$qrcode) {
    Helpers::json(['success' => false, 'message' => 'QR Code non trovato']);
}

$localeModel = new LocaleRestaurant();
$locale = $localeModel->getById($qrcode['locale_id']);

if (!$locale || $locale['user_id'] != $user['id']) {
    Helpers::json(['success' => false, 'message' => 'Non autorizzato']);
}

$newStatus = $qrcode['attivo'] ? 0 : 1;

if ($qrcodeModel->update($id, ['attivo' => $newStatus])) {
    $message = $newStatus ? 'QR Code attivato' : 'QR Code disattivato';
    Helpers::json(['success' => true, 'message' => $message]);
} else {
    Helpers::json(['success' => false, 'message' => 'Errore durante l\'aggiornamento']);
}