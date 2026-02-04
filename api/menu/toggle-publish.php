<?php
require_once __DIR__ . '/../../config/config.php';

require_once __DIR__ . '/../../classes/Database.php';
require_once __DIR__ . '/../../classes/Model.php';
require_once __DIR__ . '/../../classes/Helpers.php';
require_once __DIR__ . '/../../classes/models/User.php';
require_once __DIR__ . '/../../classes/models/LocaleRestaurant.php';
require_once __DIR__ . '/../../classes/models/Menu.php';

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
$menuModel = new Menu();
$menu = $menuModel->getById($id);

if (!$menu) {
    Helpers::json(['success' => false, 'message' => 'Menu non trovato']);
}

$localeModel = new LocaleRestaurant();
$locale = $localeModel->getById($menu['locale_id']);

if (!$locale || $locale['user_id'] != $user['id']) {
    Helpers::json(['success' => false, 'message' => 'Non autorizzato']);
}

if ($menuModel->togglePublish($id)) {
    $newStatus = $menu['pubblicato'] ? 'nascosto' : 'pubblicato';
    Helpers::json(['success' => true, 'message' => 'Menu ' . $newStatus]);
} else {
    Helpers::json(['success' => false, 'message' => 'Errore durante l\'aggiornamento']);
}
