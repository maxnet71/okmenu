<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/classes/Database.php';
require_once __DIR__ . '/classes/Model.php';
require_once __DIR__ . '/classes/Helpers.php';
require_once __DIR__ . '/classes/models/User.php';
require_once __DIR__ . '/classes/models/LocaleRestaurant.php';
require_once __DIR__ . '/MenuAI.php';

header('Content-Type: application/json');

if (!Helpers::isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Non autorizzato']);
    exit;
}

$user = Helpers::getUser();
$response = ['success' => false, 'message' => ''];

try {
    if (empty($_FILES['pages'])) {
        throw new Exception('Nessuna pagina caricata');
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

    $uploadDir = __DIR__ . '/uploads/menu-ai/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $sessionId = uniqid('menu_session_');
    $sessionDir = $uploadDir . $sessionId . '/';
    mkdir($sessionDir, 0755, true);

    $menuAI = new MenuAI();
    $allMenuData = [
        'menu' => ['nome' => 'Menu da AI', 'descrizione' => null],
        'categorie' => []
    ];
    
    $lastCategory = null;
    $pageFiles = [];

    $files = $_FILES['pages'];
    $fileCount = is_array($files['name']) ? count($files['name']) : 1;

    for ($i = 0; $i < $fileCount; $i++) {
        $fileName = is_array($files['name']) ? $files['name'][$i] : $files['name'];
        $fileTmp = is_array($files['tmp_name']) ? $files['tmp_name'][$i] : $files['tmp_name'];
        $fileType = is_array($files['type']) ? $files['type'][$i] : $files['type'];
        $fileError = is_array($files['error']) ? $files['error'][$i] : $files['error'];

        if ($fileError !== UPLOAD_ERR_OK) {
            continue;
        }

        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'application/pdf'];
        if (!in_array($fileType, $allowedTypes)) {
            continue;
        }

        $extension = pathinfo($fileName, PATHINFO_EXTENSION);
        $filename = 'page_' . ($i + 1) . '_' . time() . '.' . $extension;
        $filepath = $sessionDir . $filename;

        if (!move_uploaded_file($fileTmp, $filepath)) {
            continue;
        }

        $pageFiles[] = $filepath;

        try {
            if ($fileType === 'application/pdf') {
                $aiResponse = $menuAI->extractTextFromPDF($filepath);
            } else {
                $aiResponse = $menuAI->extractTextFromImage($filepath);
            }

            $pageData = $menuAI->parseMenuJSON($aiResponse);
            
            if (!$pageData || empty($pageData['categorie'])) {
                continue;
            }

            foreach ($pageData['categorie'] as $catData) {
                if (empty($catData['piatti'])) {
                    continue;
                }

                $hasValidCategory = !empty(trim($catData['nome'] ?? ''));
                
                if (!$hasValidCategory && $lastCategory !== null) {
                    foreach ($catData['piatti'] as $piatto) {
                        $allMenuData['categorie'][$lastCategory]['piatti'][] = $piatto;
                    }
                } else {
                    $categoryName = $hasValidCategory ? $catData['nome'] : 'Varie';
                    
                    $existingCatIndex = null;
                    foreach ($allMenuData['categorie'] as $idx => $existing) {
                        if (strcasecmp($existing['nome'], $categoryName) === 0) {
                            $existingCatIndex = $idx;
                            break;
                        }
                    }
                    
                    if ($existingCatIndex !== null) {
                        foreach ($catData['piatti'] as $piatto) {
                            $allMenuData['categorie'][$existingCatIndex]['piatti'][] = $piatto;
                        }
                        $lastCategory = $existingCatIndex;
                    } else {
                        $allMenuData['categorie'][] = [
                            'nome' => $categoryName,
                            'descrizione' => $catData['descrizione'] ?? null,
                            'piatti' => $catData['piatti']
                        ];
                        $lastCategory = count($allMenuData['categorie']) - 1;
                    }
                }
            }

            if (!empty($pageData['menu']['nome']) && $allMenuData['menu']['nome'] === 'Menu da AI') {
                $allMenuData['menu']['nome'] = $pageData['menu']['nome'];
            }

        } catch (Exception $e) {
            error_log("Errore elaborazione pagina " . ($i + 1) . ": " . $e->getMessage());
            continue;
        }
    }

    if (empty($allMenuData['categorie'])) {
        throw new Exception('Nessun dato estratto dalle pagine');
    }

    $_SESSION['menu_preview_' . $sessionId] = [
        'locale_id' => $localeId,
        'menu_data' => $allMenuData,
        'page_files' => $pageFiles,
        'created_at' => time()
    ];

    $response['success'] = true;
    $response['session_id'] = $sessionId;
    $response['message'] = 'Menu elaborato con successo';
    $response['stats'] = [
        'categorie' => count($allMenuData['categorie']),
        'piatti' => array_sum(array_map(function($cat) { 
            return count($cat['piatti']); 
        }, $allMenuData['categorie']))
    ];

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);