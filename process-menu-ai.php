<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/classes/Database.php';
require_once __DIR__ . '/classes/Model.php';
require_once __DIR__ . '/classes/Helpers.php';
require_once __DIR__ . '/classes/models/User.php';
require_once __DIR__ . '/classes/models/LocaleRestaurant.php';
require_once __DIR__ . '/classes/models/Menu.php';
require_once __DIR__ . '/classes/models/Categoria.php';
require_once __DIR__ . '/classes/models/Piatto.php';
require_once __DIR__ . '/MenuAI.php';

header('Content-Type: application/json');

if (!Helpers::isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Non autorizzato']);
    exit;
}

$user = Helpers::getUser();
$response = ['success' => false, 'message' => ''];

try {
    if (!isset($_FILES['menu_file']) || $_FILES['menu_file']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Errore durante il caricamento del file');
    }

    $localeId = intval($_POST['locale_id'] ?? 0);
    
    if ($localeId <= 0) {
        throw new Exception('Locale non valido');
    }

    $localeModel = new LocaleRestaurant();
    $locale = $localeModel->getById($localeId);
    
    if (!$locale || $locale['user_id'] != $user['id']) {
        throw new Exception('Non autorizzato');
    }

    $file = $_FILES['menu_file'];
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'application/pdf'];
    $maxSize = 10 * 1024 * 1024;

    if (!in_array($file['type'], $allowedTypes)) {
        throw new Exception('Tipo di file non supportato');
    }

    if ($file['size'] > $maxSize) {
        throw new Exception('File troppo grande (max 10MB)');
    }

    $uploadDir = __DIR__ . '/uploads/menu-ai/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid('menu_') . '.' . $extension;
    $filepath = $uploadDir . $filename;

    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        throw new Exception('Errore nel salvataggio del file');
    }

    $menuAI = new MenuAI();
    
    $uploadId = $menuAI->createUpload([
        'locale_id' => $localeId,
        'user_id' => $user['id'],
        'filename' => $file['name'],
        'filepath' => $filepath,
        'file_type' => $file['type'],
        'status' => 'processing'
    ]);

    try {
        $aiResponse = null;
        
        if ($file['type'] === 'application/pdf') {
            $aiResponse = $menuAI->extractTextFromPDF($filepath);
        } else {
            $aiResponse = $menuAI->extractTextFromImage($filepath);
        }

        $menuData = $menuAI->parseMenuJSON($aiResponse);
        
        if (!$menuData) {
            throw new Exception('Formato risposta AI non valido');
        }

        $itemsCreated = $menuAI->insertMenuData($localeId, $menuData);

        $menuAI->updateStatus($uploadId, 'completed', [
            'ai_response' => $aiResponse,
            'items_created' => $itemsCreated
        ]);

        $response['success'] = true;
        $response['message'] = "Menu creato con successo! {$itemsCreated} elementi aggiunti.";
        $response['items_created'] = $itemsCreated;
        
    } catch (Exception $e) {
        $menuAI->updateStatus($uploadId, 'failed', [
            'error_message' => $e->getMessage()
        ]);
        
        throw $e;
    }

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);