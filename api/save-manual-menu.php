<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../Model.php';
require_once __DIR__ . '/../classes/models/Menu.php';
require_once __DIR__ . '/../classes/models/Categoria.php';
require_once __DIR__ . '/../classes/models/Piatto.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

try {
    $isOnboarding = isset($_SESSION['onboarding_user_id']);
    $isLoggedIn = isset($_SESSION['user_id']);
    
    if (!$isOnboarding && !$isLoggedIn) {
        throw new Exception('Non autorizzato');
    }
    
    $userId = $isOnboarding ? $_SESSION['onboarding_user_id'] : $_SESSION['user_id'];
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (empty($input['locale_id']) || empty($input['menu_data'])) {
        throw new Exception('Dati non validi');
    }
    
    $localeId = (int)$input['locale_id'];
    $menuData = $input['menu_data'];
    
    $db = Database::getInstance()->getConnection();
    
    // Verifica proprietà locale
    $stmt = $db->prepare("SELECT id FROM locali WHERE id = :id AND user_id = :user_id");
    $stmt->execute(['id' => $localeId, 'user_id' => $userId]);
    if (!$stmt->fetch()) {
        throw new Exception('Locale non autorizzato');
    }
    
    $db->beginTransaction();
    
    try {
        // Crea menu
        $template = $_SESSION['selected_template'] ?? null;
        
        $menuModel = new Menu();
        $menuId = $menuModel->insert([
            'locale_id' => $localeId,
            'tipo' => 'principale',
            'template_style' => $template['stile'] ?? 'classic',
            'pubblicato' => 1
        ]);
        
        $categoriaModel = new Categoria();
        $piattoModel = new Piatto();
        
        $itemsCreated = 0;
        
        // Crea categorie e piatti
        foreach ($menuData['categories'] as $catIndex => $catData) {
            $categoriaId = $categoriaModel->insert([
                'menu_id' => $menuId,
                'nome' => $catData['name'],
                'ordinamento' => $catIndex
            ]);
            
            foreach ($catData['dishes'] as $dishIndex => $dishData) {
                $piattoModel->insert([
                    'categoria_id' => $categoriaId,
                    'nome' => $dishData['name'],
                    'descrizione' => $dishData['description'] ?? null,
                    'prezzo' => $dishData['price'] ? floatval($dishData['price']) : null,
                    'vegetariano' => !empty($dishData['vegetarian']) ? 1 : 0,
                    'ordinamento' => $dishIndex,
                    'disponibile' => 1
                ]);
                $itemsCreated++;
            }
        }
        
        $db->commit();
        
        $response['success'] = true;
        $response['message'] = "Menu creato con successo! {$itemsCreated} piatti aggiunti.";
        $response['menu_id'] = $menuId;
        $response['is_onboarding'] = $isOnboarding;
        
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }
    
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    error_log("Errore save-manual-menu: " . $e->getMessage());
}

echo json_encode($response);