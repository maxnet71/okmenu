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
        $path = ltrim($path, '/');
        if (strpos($path, 'uploads/') === 0) {
            $path = substr($path, 8);
        }
        
        $uploadPath = __DIR__ . '/../uploads/' . $path;
        
        if (!file_exists($uploadPath)) {
            mkdir($uploadPath, 0755, true);
        }

        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        
        if (!in_array($file['type'], $allowedTypes)) {
            return ['success' => false, 'message' => 'Tipo di file non consentito'];
        }

        $maxSize = 10 * 1024 * 1024;
        if ($file['size'] > $maxSize) {
            return ['success' => false, 'message' => 'File troppo grande (max 10MB)'];
        }

        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $fileName = uniqid() . '_' . time() . '.' . $extension;
        $filePath = $uploadPath . $fileName;

        if (move_uploaded_file($file['tmp_name'], $filePath)) {
            self::resizeImage($filePath, 1200);
            self::compressImage($filePath, 500 * 1024);
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
        
        if ($currentSize <= $maxSize) {
            return;
        }
        
        list($width, $height, $type) = getimagesize($filePath);
        
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
                return;
        }
        
        if (!$source) return;
        
        $quality = 85;
        $tempFile = $filePath . '.tmp';
        
        while ($quality >= 50 && filesize($filePath) > $maxSize) {
            switch ($type) {
                case IMAGETYPE_JPEG:
                    imagejpeg($source, $tempFile, $quality);
                    break;
                case IMAGETYPE_PNG:
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

    /**
     * Flash Messages
     */
    public static function setFlashMessage($message, $type = 'info')
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['flash_message'] = [
            'message' => $message,
            'type' => $type
        ];
    }

    public static function hasFlashMessage()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return isset($_SESSION['flash_message']);
    }

    public static function getFlashMessage()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (isset($_SESSION['flash_message'])) {
            $message = $_SESSION['flash_message'];
            unset($_SESSION['flash_message']);
            return $message;
        }
        
        return null;
    }

    /**
     * CSRF Token
     */
    public static function generateCsrfToken()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = self::generateToken(32);
        }
        
        return $_SESSION['csrf_token'];
    }

    public static function verifyCsrfToken($token)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }

    public static function requireCsrfToken()
    {
        $token = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '';
        
        if (!self::verifyCsrfToken($token)) {
            http_response_code(403);
            die('CSRF token validation failed');
        }
    }
	
	
	/**
     * Tronca una stringa alla lunghezza specificata
     * 
     * @param string $text Testo da troncare
     * @param int $length Lunghezza massima
     * @param string $suffix Suffisso da aggiungere (default: '...')
     * @return string Testo troncato
     */
    public static function truncate($text, $length = 100, $suffix = '...')
    {
        if (empty($text)) {
            return '';
        }
        
        $text = strip_tags($text);
        
        if (mb_strlen($text) <= $length) {
            return $text;
        }
        
        $truncated = mb_substr($text, 0, $length);
        
        // Tronca all'ultima parola completa
        $lastSpace = mb_strrpos($truncated, ' ');
        if ($lastSpace !== false) {
            $truncated = mb_substr($truncated, 0, $lastSpace);
        }
        
        return $truncated . $suffix;
    }
}