<?php
/**
 * DeviceDetector - Rileva tipo dispositivo
 * 
 * POSIZIONE: /classes/DeviceDetector.php
 */

class DeviceDetector
{
    private static $userAgent;
    
    public static function init()
    {
        self::$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    }
    
    /**
     * Rileva se è un dispositivo mobile
     */
    public static function isMobile()
    {
        if (empty(self::$userAgent)) {
            self::init();
        }
        
        // Pattern mobile device
        $mobilePattern = '/(android|webos|iphone|ipad|ipod|blackberry|iemobile|opera mini|mobile)/i';
        
        return preg_match($mobilePattern, self::$userAgent);
    }
    
    /**
     * Rileva se è tablet
     */
    public static function isTablet()
    {
        if (empty(self::$userAgent)) {
            self::init();
        }
        
        $tabletPattern = '/(ipad|tablet|playbook|silk)|(android(?!.*mobile))/i';
        
        return preg_match($tabletPattern, self::$userAgent);
    }
    
    /**
     * Rileva se è desktop
     */
    public static function isDesktop()
    {
        return !self::isMobile() && !self::isTablet();
    }
    
    /**
     * Rileva se è iOS
     */
    public static function isIOS()
    {
        if (empty(self::$userAgent)) {
            self::init();
        }
        
        return preg_match('/(iphone|ipad|ipod)/i', self::$userAgent);
    }
    
    /**
     * Rileva se è Android
     */
    public static function isAndroid()
    {
        if (empty(self::$userAgent)) {
            self::init();
        }
        
        return preg_match('/android/i', self::$userAgent);
    }
    
    /**
     * Ottieni dimensione schermo stimata
     */
    public static function getScreenSize()
    {
        if (self::isDesktop()) {
            return 'large';
        } elseif (self::isTablet()) {
            return 'medium';
        } else {
            return 'small';
        }
    }
    
    /**
     * Redirect automatico basato su dispositivo
     */
    public static function autoRedirect($desktopUrl, $mobileUrl)
    {
        // Controlla se c'è un override manuale via GET
        if (isset($_GET['view'])) {
            if ($_GET['view'] === 'desktop') {
                $_SESSION['force_desktop'] = true;
                return $desktopUrl;
            } elseif ($_GET['view'] === 'mobile') {
                $_SESSION['force_mobile'] = true;
                return $mobileUrl;
            }
        }
        
        // Controlla se c'è una preferenza salvata in sessione
        if (isset($_SESSION['force_desktop']) && $_SESSION['force_desktop']) {
            return $desktopUrl;
        }
        
        if (isset($_SESSION['force_mobile']) && $_SESSION['force_mobile']) {
            return $mobileUrl;
        }
        
        // Auto-detect
        return self::isMobile() ? $mobileUrl : $desktopUrl;
    }
    
    /**
     * Ottieni info complete dispositivo
     */
    public static function getDeviceInfo()
    {
        if (empty(self::$userAgent)) {
            self::init();
        }
        
        return [
            'user_agent' => self::$userAgent,
            'is_mobile' => self::isMobile(),
            'is_tablet' => self::isTablet(),
            'is_desktop' => self::isDesktop(),
            'is_ios' => self::isIOS(),
            'is_android' => self::isAndroid(),
            'screen_size' => self::getScreenSize()
        ];
    }
}