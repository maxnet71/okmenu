<?php

class Helpers
{
    public static function formatPrice($price)
    {
        return number_format($price, 2, ',', '.') . ' €';
    }

    public static function formatDate($date, $format = 'd/m/Y')
    {
        return date($format, strtotime($date));
    }

    public static function formatDateTime($datetime, $format = 'd/m/Y H:i')
    {
        return date($format, strtotime($datetime));
    }

    public static function sanitizeInput($data)
    {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $data[$key] = self::sanitizeInput($value);
            }
        } else {
            $data = trim($data);
            $data = stripslashes($data);
            $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
        }
        return $data;
    }

    public static function redirect($url)
    {
        header("Location: {$url}");
        exit;
    }

    public static function json($data, $statusCode = 200)
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    public static function uploadImage($file, $path = 'uploads/images/')
    {
        // Rimuovo uploads/ iniziale se presente in $path per evitare doppi percorsi
        $path = ltrim($path, '/');
        if (strpos($path, 'uploads/') === 0) {
            $path = substr($path, 8); // Rimuove 'uploads/'
        }
        
        $uploadPath = __DIR__ . '/../uploads/' . $path;
        
        if (!file_exists($uploadPath)) {
            mkdir($uploadPath, 0755, true);
        }

        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        
        if (!in_array($file['type'], $allowedTypes)) {
            return ['success' => false, 'message' => 'Tipo di file non consentito'];
        }

        $maxSize = 10 * 1024 * 1024; // 10MB max
        if ($file['size'] > $maxSize) {
            return ['success' => false, 'message' => 'File troppo grande (max 10MB)'];
        }

        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $fileName = uniqid() . '_' . time() . '.' . $extension;
        $filePath = $uploadPath . $fileName;

        if (move_uploaded_file($file['tmp_name'], $filePath)) {
            // Ridimensiona se troppo grande (max 1200px larghezza)
            self::resizeImage($filePath, 1200);
            
            // Comprimi se troppo pesante (max 500KB)
            self::compressImage($filePath, 500 * 1024);
            
            // Ritorna path relativo senza uploads/ iniziale (viene aggiunto da BASE_URL)
            return ['success' => true, 'file_path' => $path . $fileName];
        }

        return ['success' => false, 'message' => 'Errore durante il caricamento'];
    }

    private static function resizeImage($filePath, $maxWidth)
    {
        list($width, $height, $type) = getimagesize($filePath);

        if ($width <= $maxWidth) {
            return;
        }

        $ratio = $maxWidth / $width;
        $newWidth = $maxWidth;
        $newHeight = (int)($height * $ratio);

        switch ($type) {
            case IMAGETYPE_JPEG:
                $source = imagecreatefromjpeg($filePath);
                break;
            case IMAGETYPE_PNG:
                $source = imagecreatefrompng($filePath);
                break;
            case IMAGETYPE_GIF:
                $source = imagecreatefromgif($filePath);
                break;
            case IMAGETYPE_WEBP:
                $source = imagecreatefromwebp($filePath);
                break;
            default:
                return;
        }

        $destination = imagecreatetruecolor($newWidth, $newHeight);

        if ($type == IMAGETYPE_PNG || $type == IMAGETYPE_GIF) {
            imagealphablending($destination, false);
            imagesavealpha($destination, true);
            $transparent = imagecolorallocatealpha($destination, 255, 255, 255, 127);
            imagefilledrectangle($destination, 0, 0, $newWidth, $newHeight, $transparent);
        }

        imagecopyresampled($destination, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

        switch ($type) {
            case IMAGETYPE_JPEG:
                imagejpeg($destination, $filePath, 85);
                break;
            case IMAGETYPE_PNG:
                imagepng($destination, $filePath, 8);
                break;
            case IMAGETYPE_GIF:
                imagegif($destination, $filePath);
                break;
            case IMAGETYPE_WEBP:
                imagewebp($destination, $filePath, 85);
                break;
        }

        imagedestroy($source);
        imagedestroy($destination);
    }
    
    private static function compressImage($filePath, $maxSize)
    {
        $currentSize = filesize($filePath);
        
        // Se già sotto il limite, non fare nulla
        if ($currentSize <= $maxSize) {
            return;
        }
        
        list($width, $height, $type) = getimagesize($filePath);
        
        // Carica immagine
        switch ($type) {
            case IMAGETYPE_JPEG:
                $source = imagecreatefromjpeg($filePath);
                break;
            case IMAGETYPE_PNG:
                $source = imagecreatefrompng($filePath);
                break;
            case IMAGETYPE_WEBP:
                $source = imagecreatefromwebp($filePath);
                break;
            default:
                return; // GIF non compressa
        }
        
        if (!$source) return;
        
        // Prova compressione progressiva
        $quality = 85;
        $tempFile = $filePath . '.tmp';
        
        while ($quality >= 50 && filesize($filePath) > $maxSize) {
            switch ($type) {
                case IMAGETYPE_JPEG:
                    imagejpeg($source, $tempFile, $quality);
                    break;
                case IMAGETYPE_PNG:
                    // PNG: 0 (no compression) a 9 (max compression)
                    $pngQuality = round(9 - ($quality / 10));
                    imagepng($source, $tempFile, $pngQuality);
                    break;
                case IMAGETYPE_WEBP:
                    imagewebp($source, $tempFile, $quality);
                    break;
            }
            
            if (file_exists($tempFile)) {
                $newSize = filesize($tempFile);
                
                if ($newSize <= $maxSize || $quality <= 50) {
                    // Usa file compresso
                    unlink($filePath);
                    rename($tempFile, $filePath);
                    break;
                } else {
                    unlink($tempFile);
                    $quality -= 10;
                }
            } else {
                break;
            }
        }
        
        imagedestroy($source);
    }

    public static function deleteFile($filePath)
    {
        $fullPath = UPLOAD_PATH . $filePath;
        if (file_exists($fullPath)) {
            return unlink($fullPath);
        }
        return false;
    }

    public static function generateToken($length = 32)
    {
        return bin2hex(random_bytes($length / 2));
    }

    public static function validateEmail($email)
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }

    public static function validatePhone($phone)
    {
        return preg_match('/^[+]?[\d\s\-().]{8,20}$/', $phone);
    }

    public static function isLoggedIn()
    {
        return isset($_SESSION['user_id']);
    }

    public static function requireLogin()
    {
        if (!self::isLoggedIn()) {
            self::redirect(BASE_URL . '/login.php');
        }
    }

    public static function getUser()
    {
        if (self::isLoggedIn()) {
            $userModel = new User();
            return $userModel->getById($_SESSION['user_id']);
        }
        return null;
    }

    public static function hasPermission($requiredType)
    {
        $user = self::getUser();
        if (!$user) {
            return false;
        }

        $hierarchy = ['admin' => 3, 'ristoratore' => 2, 'staff' => 1];
        
        return isset($hierarchy[$user['tipo']]) && 
               isset($hierarchy[$requiredType]) && 
               $hierarchy[$user['tipo']] >= $hierarchy[$requiredType];
    }
}