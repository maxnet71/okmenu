<?php
require_once __DIR__ . '/../../config/config.php';

require_once __DIR__ . '/../../classes/Database.php';
require_once __DIR__ . '/../../classes/Model.php';
require_once __DIR__ . '/../../classes/Helpers.php';
require_once __DIR__ . '/../../classes/models/User.php';
require_once __DIR__ . '/../../classes/models/LocaleRestaurant.php';

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
$localeModel = new LocaleRestaurant();
$locale = $localeModel->getById($id);

if (!$locale || $locale['user_id'] != $user['id']) {
    Helpers::json(['success' => false, 'message' => 'Locale non trovato o non autorizzato']);
}

if ($localeModel->delete($id)) {
    if ($locale['logo']) {
        Helpers::deleteFile($locale['logo']);
    }
    
    Helpers::json(['success' => true, 'message' => 'Locale eliminato con successo']);
} else {
    Helpers::json(['success' => false, 'message' => 'Errore durante l\'eliminazione']);
}
