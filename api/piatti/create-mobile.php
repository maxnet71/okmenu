<?php
/**
 * API: Create Piatto Mobile
 * Crea piatto con ottimizzazione automatica immagini da mobile
 * 
 * POSIZIONE: /api/piatti/create-mobile.php
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../classes/Database.php';
require_once __DIR__ . '/../../classes/Model.php';
require_once __DIR__ . '/../../classes/Helpers.php';
require_once __DIR__ . '/../../classes/models/User.php';
require_once __DIR__ . '/../../classes/models/Piatto.php';

header('Content-Type: application/json');

if (!Helpers::isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Non autorizzato']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Metodo non consentito']);
    exit;
}

try {
    $user = Helpers::getUser();
    
    // Validazione input
    $nome = trim($_POST['nome'] ?? '');
    $categoriaId = intval($_POST['categoria_id'] ?? 0);
    $descrizione = trim($_POST['descrizione'] ?? '');
    $ingredienti = trim($_POST['ingredienti'] ?? '');
    $prezzo = floatval($_POST['prezzo'] ?? 0);
    $prezzoScontato = !empty($_POST['prezzo_scontato']) ? floatval($_POST['prezzo_scontato']) : null;
    $disponibile = isset($_POST['disponibile']) ? 1 : 0;
    
    $caratteristiche = $_POST['caratteristiche'] ?? [];
    $allergeni = $_POST['allergeni'] ?? [];
    
    if (empty($nome)) {
        throw new Exception('Nome piatto obbligatorio');
    }
    
    if (!$categoriaId) {
        throw new Exception('Categoria obbligatoria');
    }
    
    if ($prezzo <= 0) {
        throw new Exception('Prezzo non valido');
    }
    
    // Verifica proprietà categoria
    $db = Database::getInstance()->getConnection();
    $sql = "SELECT c.*, m.locale_id 
            FROM categorie c
            INNER JOIN menu m ON c.menu_id = m.id
            INNER JOIN locali l ON m.locale_id = l.id
            WHERE c.id = :categoria_id AND l.user_id = :user_id";
    $stmt = $db->prepare($sql);
    $stmt->execute([
        'categoria_id' => $categoriaId,
        'user_id' => $user['id']
    ]);
    $categoria = $stmt->fetch();
    
    if (!$categoria) {
        throw new Exception('Categoria non trovata o non autorizzato');
    }
    
    // Upload e ottimizzazione immagine
    $immaginePath = null;
    if (isset($_FILES['immagine']) && $_FILES['immagine']['error'] === UPLOAD_ERR_OK) {
        $immaginePath = uploadAndOptimizeImage($_FILES['immagine'], $categoria['locale_id']);
    }
    
    // Crea piatto
    $piattoModel = new Piatto();
    $piattoData = [
        'categoria_id' => $categoriaId,
        'nome' => $nome,
        'descrizione' => $descrizione,
        'ingredienti' => $ingredienti,
        'prezzo' => $prezzo,
        'prezzo_scontato' => $prezzoScontato,
        'immagine' => $immaginePath,
        'disponibile' => $disponibile,
        'ordinamento' => 999
    ];
    
    $piattoId = $piattoModel->insert($piattoData);
    
    if (!$piattoId) {
        throw new Exception('Errore creazione piatto');
    }
    
    // Associa caratteristiche
    if (!empty($caratteristiche)) {
        foreach ($caratteristiche as $carId) {
            $sql = "INSERT INTO piatti_caratteristiche (piatto_id, caratteristica_id) VALUES (:piatto_id, :car_id)";
            $stmt = $db->prepare($sql);
            $stmt->execute([
                'piatto_id' => $piattoId,
                'car_id' => intval($carId)
            ]);
        }
    }
    
    // Associa allergeni
    if (!empty($allergeni)) {
        foreach ($allergeni as $allId) {
            $sql = "INSERT INTO piatti_allergeni (piatto_id, allergene_id) VALUES (:piatto_id, :all_id)";
            $stmt = $db->prepare($sql);
            $stmt->execute([
                'piatto_id' => $piattoId,
                'all_id' => intval($allId)
            ]);
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Piatto creato con successo',
        'piatto_id' => $piattoId
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

/**
 * Upload e ottimizza immagine da mobile
 */
function uploadAndOptimizeImage($file, $localeId)
{
    // Validazione
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
    $maxSize = 10 * 1024 * 1024; // 10MB
    
    if (!in_array($file['type'], $allowedTypes)) {
        throw new Exception('Formato immagine non valido. Usa JPG, PNG o WebP');
    }
    
    if ($file['size'] > $maxSize) {
        throw new Exception('Immagine troppo grande. Max 10MB');
    }
    
    // Directory upload
    $uploadDir = __DIR__ . '/../../uploads/piatti/' . $localeId . '/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Nome file univoco
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid('piatto_', true) . '.' . $extension;
    $filepath = $uploadDir . $filename;
    
    // Carica immagine originale
    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        throw new Exception('Errore upload immagine');
    }
    
    // Ottimizza immagine
    try {
        optimizeImage($filepath, 1200, 800, 85);
    } catch (Exception $e) {
        // Se ottimizzazione fallisce, usa immagine originale
        error_log('Errore ottimizzazione: ' . $e->getMessage());
    }
    
    // Ritorna path relativo
    return 'uploads/piatti/' . $localeId . '/' . $filename;
}

/**
 * Ottimizza immagine: resize + compressione
 */
function optimizeImage($filepath, $maxWidth = 1200, $maxHeight = 800, $quality = 85)
{
    // Ottieni info immagine
    $imageInfo = getimagesize($filepath);
    if (!$imageInfo) {
        throw new Exception('File non è un\'immagine valida');
    }
    
    list($width, $height, $type) = $imageInfo;
    
    // Carica immagine in base al tipo
    switch ($type) {
        case IMAGETYPE_JPEG:
            $image = imagecreatefromjpeg($filepath);
            break;
        case IMAGETYPE_PNG:
            $image = imagecreatefrompng($filepath);
            break;
        case IMAGETYPE_WEBP:
            $image = imagecreatefromwebp($filepath);
            break;
        default:
            throw new Exception('Tipo immagine non supportato');
    }
    
    if (!$image) {
        throw new Exception('Impossibile caricare immagine');
    }
    
    // Calcola nuove dimensioni mantenendo aspect ratio
    $ratio = min($maxWidth / $width, $maxHeight / $height, 1);
    
    if ($ratio < 1) {
        $newWidth = round($width * $ratio);
        $newHeight = round($height * $ratio);
        
        // Crea immagine ridimensionata
        $resized = imagecreatetruecolor($newWidth, $newHeight);
        
        // Preserva trasparenza per PNG
        if ($type === IMAGETYPE_PNG) {
            imagealphablending($resized, false);
            imagesavealpha($resized, true);
        }
        
        // Resize con qualità alta
        imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        
        // Salva immagine ottimizzata
        switch ($type) {
            case IMAGETYPE_JPEG:
                imagejpeg($resized, $filepath, $quality);
                break;
            case IMAGETYPE_PNG:
                // PNG: compressione 0-9 (9 = max compressione)
                $pngQuality = round(9 - ($quality / 100 * 9));
                imagepng($resized, $filepath, $pngQuality);
                break;
            case IMAGETYPE_WEBP:
                imagewebp($resized, $filepath, $quality);
                break;
        }
        
        imagedestroy($resized);
    } else {
        // Immagine già piccola, solo compressione
        switch ($type) {
            case IMAGETYPE_JPEG:
                imagejpeg($image, $filepath, $quality);
                break;
            case IMAGETYPE_PNG:
                $pngQuality = round(9 - ($quality / 100 * 9));
                imagepng($image, $filepath, $pngQuality);
                break;
            case IMAGETYPE_WEBP:
                imagewebp($image, $filepath, $quality);
                break;
        }
    }
    
    imagedestroy($image);
    
    // Ottimizza ulteriormente con compressione progressiva (JPEG)
    if ($type === IMAGETYPE_JPEG) {
        $img = imagecreatefromjpeg($filepath);
        imageinterlace($img, 1); // Progressive JPEG
        imagejpeg($img, $filepath, $quality);
        imagedestroy($img);
    }
}