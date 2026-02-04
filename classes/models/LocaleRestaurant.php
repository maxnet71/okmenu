<?php

class LocaleRestaurant extends Model
{
    protected $table = 'locali';

    public function getByUserId($userId)
    {
        return $this->getWhere(['user_id' => $userId], 'nome ASC');
    }

    public function getBySlug($slug)
    {
        return $this->getOneWhere(['slug' => $slug, 'attivo' => 1]);
    }

    public function slugExists($slug, $excludeId = null)
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE slug = :slug";
        if ($excludeId) {
            $sql .= " AND id != :id";
        }
        $stmt = $this->db->prepare($sql);
        $params = ['slug' => $slug];
        if ($excludeId) {
            $params['id'] = $excludeId;
        }
        $stmt->execute($params);
        $result = $stmt->fetch();
        return $result['count'] > 0;
    }

    public function generateSlug($nome, $excludeId = null)
    {
        $slug = $this->sanitizeSlug($nome);
        $originalSlug = $slug;
        $counter = 1;

        while ($this->slugExists($slug, $excludeId)) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    private function sanitizeSlug($string)
    {
        $string = strtolower(trim($string));
        $string = preg_replace('/[^a-z0-9-]/', '-', $string);
        $string = preg_replace('/-+/', '-', $string);
        return trim($string, '-');
    }

    public function getWithDetails($localeId)
    {
        $sql = "SELECT l.*, u.nome as proprietario_nome, u.email as proprietario_email
                FROM locali l
                INNER JOIN users u ON l.user_id = u.id
                WHERE l.id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $localeId]);
        return $stmt->fetch();
    }
}
