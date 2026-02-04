<?php

class PlanLimits
{
    private $db;
    private $userId;
    private $piano;
    
    const LIMITS = [
        'free' => [
            'locali' => 1,
            'menu_per_locale' => 1,
            'ai_uploads' => 1,
            'ordini_online' => false,
            'prenotazioni' => false,
            'statistiche_avanzate' => false,
            'template_premium' => false,
            'supporto_prioritario' => false
        ],
        'premium' => [
            'locali' => 999,
            'menu_per_locale' => 999,
            'ai_uploads' => 999,
            'ordini_online' => true,
            'prenotazioni' => true,
            'statistiche_avanzate' => true,
            'template_premium' => true,
            'supporto_prioritario' => true
        ]
    ];
    
    public function __construct(int $userId, string $piano = 'free')
    {
        $this->db = Database::getInstance()->getConnection();
        $this->userId = $userId;
        $this->piano = $piano;
    }
    
    public function canCreateLocale(): bool
    {
        if ($this->piano === 'premium') {
            return true;
        }
        
        $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM locali WHERE user_id = :user_id");
        $stmt->execute(['user_id' => $this->userId]);
        $result = $stmt->fetch();
        
        return $result['count'] < self::LIMITS['free']['locali'];
    }
    
    public function canCreateMenu(int $localeId): bool
    {
        if ($this->piano === 'premium') {
            return true;
        }
        
        $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM menu WHERE locale_id = :locale_id");
        $stmt->execute(['locale_id' => $localeId]);
        $result = $stmt->fetch();
        
        return $result['count'] < self::LIMITS['free']['menu_per_locale'];
    }
    
    public function canUseAI(): bool
    {
        if ($this->piano === 'premium') {
            return true;
        }
        
        $stmt = $this->db->prepare("
            SELECT ai_uploads_used, ai_uploads_limit 
            FROM piano_limiti 
            WHERE user_id = :user_id
        ");
        $stmt->execute(['user_id' => $this->userId]);
        $result = $stmt->fetch();
        
        if (!$result) {
            $this->db->prepare("
                INSERT INTO piano_limiti (user_id, ai_uploads_limit) 
                VALUES (:user_id, 1)
            ")->execute(['user_id' => $this->userId]);
            return true;
        }
        
        return $result['ai_uploads_used'] < $result['ai_uploads_limit'];
    }
    
    public function incrementAIUsage(): void
    {
        if ($this->piano === 'premium') {
            return;
        }
        
        $stmt = $this->db->prepare("
            UPDATE piano_limiti 
            SET ai_uploads_used = ai_uploads_used + 1 
            WHERE user_id = :user_id
        ");
        $stmt->execute(['user_id' => $this->userId]);
    }
    
    public function hasFeature(string $feature): bool
    {
        return self::LIMITS[$this->piano][$feature] ?? false;
    }
    
    public function getLimits(): array
    {
        return self::LIMITS[$this->piano];
    }
    
    public function getCurrentUsage(): array
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as locali_count FROM locali WHERE user_id = :user_id
        ");
        $stmt->execute(['user_id' => $this->userId]);
        $localiCount = $stmt->fetch()['locali_count'];
        
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as menu_count 
            FROM menu m 
            INNER JOIN locali l ON m.locale_id = l.id 
            WHERE l.user_id = :user_id
        ");
        $stmt->execute(['user_id' => $this->userId]);
        $menuCount = $stmt->fetch()['menu_count'];
        
        $stmt = $this->db->prepare("
            SELECT ai_uploads_used 
            FROM piano_limiti 
            WHERE user_id = :user_id
        ");
        $stmt->execute(['user_id' => $this->userId]);
        $aiUsage = $stmt->fetch()['ai_uploads_used'] ?? 0;
        
        return [
            'locali' => $localiCount,
            'menu' => $menuCount,
            'ai_uploads' => $aiUsage
        ];
    }
    
    public function getUpgradeMessage(string $feature): string
    {
        $messages = [
            'locali' => 'Hai raggiunto il limite di 1 locale. Passa a Premium per creare locali illimitati!',
            'menu' => 'Hai raggiunto il limite di 1 menu per locale. Passa a Premium per menu illimitati!',
            'ai_uploads' => 'Hai usato il caricamento AI gratuito. Passa a Premium per upload AI illimitati!',
            'ordini_online' => 'Gli ordini online sono disponibili solo con Premium!',
            'prenotazioni' => 'Le prenotazioni sono disponibili solo con Premium!',
            'statistiche_avanzate' => 'Le statistiche avanzate sono disponibili solo con Premium!',
            'template_premium' => 'Questo template è disponibile solo con Premium!'
        ];
        
        return $messages[$feature] ?? 'Funzionalità disponibile solo con Premium!';
    }
}