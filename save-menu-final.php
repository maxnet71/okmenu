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

header('Content-Type: application/json');

// Gestisci sia utenti loggati che in onboarding
$isOnboarding = isset($_SESSION['onboarding_user_id']);
$isLoggedIn = Helpers::isLoggedIn();

if (!$isOnboarding && !$isLoggedIn) {
    echo json_encode(['success' => false, 'message' => 'Non autorizzato']);
    exit;
}

$userId = $isOnboarding ? $_SESSION['onboarding_user_id'] : Helpers::getUser()['id'];
$response = ['success' => false, 'message' => ''];

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['session_id']) || !isset($input['menu_data'])) {
        throw new Exception('Dati non validi');
    }

    $sessionId = $input['session_id'];
    $sessionKey = 'menu_preview_' . $sessionId;
    
    if (!isset($_SESSION[$sessionKey])) {
        throw new Exception('Sessione non valida');
    }

    $sessionData = $_SESSION[$sessionKey];
    $localeId = $sessionData['locale_id'];
    $menuData = $input['menu_data'];

    $localeModel = new LocaleRestaurant();
    $locale = $localeModel->getById($localeId);
    
    if (!$locale || $locale['user_id'] != $userId) {
        throw new Exception('Non autorizzato');
    }

    $db = Database::getInstance()->getConnection();
    $db->beginTransaction();

    try {
        $menuModel = new Menu();
        $categoriaModel = new Categoria();
        $piattoModel = new Piatto();

        $menuNome = $menuData['menu']['nome'] ?? 'Menu da AI';
        $menuDescrizione = $menuData['menu']['descrizione'] ?? null;

        $menuId = $menuModel->insert([
            'locale_id' => $localeId,
            'nome' => $menuNome,
            'descrizione' => $menuDescrizione,
            'tipo' => 'principale',
            'pubblicato' => 0,
            'ordinamento' => 0
        ]);

        $allergeniMap = [];
        $stmt = $db->query("SELECT id, codice FROM allergeni");
        while ($row = $stmt->fetch()) {
            $allergeniMap[$row['codice']] = $row['id'];
        }

        $caratteristicheMap = [];
        $stmt = $db->query("SELECT id, nome FROM caratteristiche");
        while ($row = $stmt->fetch()) {
            $caratteristicheMap[$row['nome']] = $row['id'];
        }

        $itemsCreated = 0;

        foreach ($menuData['categorie'] ?? [] as $idx => $catData) {
            if (empty($catData['piatti'])) {
                continue;
            }

            $categoriaId = $categoriaModel->insert([
                'menu_id' => $menuId,
                'nome' => $catData['nome'],
                'descrizione' => $catData['descrizione'] ?? null,
                'ordinamento' => $idx,
                'attivo' => 1
            ]);

            $itemsCreated++;

            foreach ($catData['piatti'] ?? [] as $piattoIdx => $piattoData) {
                if (empty($piattoData['nome'])) {
                    continue;
                }

                $piattoId = $piattoModel->insert([
                    'categoria_id' => $categoriaId,
                    'nome' => $piattoData['nome'],
                    'descrizione' => $piattoData['descrizione'] ?? null,
                    'ingredienti' => $piattoData['ingredienti'] ?? null,
                    'prezzo' => floatval($piattoData['prezzo'] ?? 0),
                    'mostra_prezzo' => (floatval($piattoData['prezzo'] ?? 0) > 0) ? 1 : 0,
                    'disponibile' => 1,
                    'ordinamento' => $piattoIdx
                ]);

                $itemsCreated++;

                if (!empty($piattoData['allergeni'])) {
                    foreach ($piattoData['allergeni'] as $allergeneCode) {
                        if (isset($allergeniMap[$allergeneCode])) {
                            $stmt = $db->prepare(
                                "INSERT INTO piatti_allergeni (piatto_id, allergene_id) VALUES (:piatto_id, :allergene_id)"
                            );
                            $stmt->execute([
                                'piatto_id' => $piattoId,
                                'allergene_id' => $allergeniMap[$allergeneCode]
                            ]);
                        }
                    }
                }

                if (!empty($piattoData['caratteristiche'])) {
                    foreach ($piattoData['caratteristiche'] as $caratteristicaNome) {
                        if (isset($caratteristicheMap[$caratteristicaNome])) {
                            $stmt = $db->prepare(
                                "INSERT INTO piatti_caratteristiche (piatto_id, caratteristica_id) VALUES (:piatto_id, :caratteristica_id)"
                            );
                            $stmt->execute([
                                'piatto_id' => $piattoId,
                                'caratteristica_id' => $caratteristicheMap[$caratteristicaNome]
                            ]);
                        }
                    }
                }
            }
        }

        $db->commit();

        unset($_SESSION[$sessionKey]);

        if (!empty($sessionData['page_files'])) {
            foreach ($sessionData['page_files'] as $file) {
                if (file_exists($file)) {
                    @unlink($file);
                }
            }
            $sessionDir = dirname($sessionData['page_files'][0]);
            if (is_dir($sessionDir)) {
                @rmdir($sessionDir);
            }
        }

        $response['success'] = true;
        $response['message'] = "Menu salvato con successo! {$itemsCreated} elementi creati.";
        $response['menu_id'] = $menuId;
        $response['is_onboarding'] = $isOnboarding;
        
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);