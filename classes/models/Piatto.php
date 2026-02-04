<?php

class Piatto extends Model
{
    protected $table = 'piatti';

    public function getByCategoriaId($categoriaId, $disponibile = null)
    {
        $conditions = ['categoria_id' => $categoriaId];
        if ($disponibile !== null) {
            $conditions['disponibile'] = $disponibile ? 1 : 0;
        }
        return $this->getWhere($conditions, 'ordinamento ASC, nome ASC');
    }

    public function getWithDetails($piattoId)
    {
        $sql = "SELECT p.*, c.nome as categoria_nome, c.menu_id
                FROM piatti p
                INNER JOIN categorie c ON p.categoria_id = c.id
                WHERE p.id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $piattoId]);
        $piatto = $stmt->fetch();

        if ($piatto) {
            $piatto['allergeni'] = $this->getAllergeni($piattoId);
            $piatto['caratteristiche'] = $this->getCaratteristiche($piattoId);
            $piatto['varianti'] = $this->getVarianti($piattoId);
        }

        return $piatto;
    }

    public function getAllergeni($piattoId)
    {
        $sql = "SELECT a.* FROM allergeni a
                INNER JOIN piatti_allergeni pa ON a.id = pa.allergene_id
                WHERE pa.piatto_id = :piatto_id
                ORDER BY a.ordinamento";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['piatto_id' => $piattoId]);
        return $stmt->fetchAll();
    }

    public function getCaratteristiche($piattoId)
    {
        $sql = "SELECT c.* FROM caratteristiche c
                INNER JOIN piatti_caratteristiche pc ON c.id = pc.caratteristica_id
                WHERE pc.piatto_id = :piatto_id
                ORDER BY c.ordinamento";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['piatto_id' => $piattoId]);
        return $stmt->fetchAll();
    }

    public function getVarianti($piattoId)
    {
        $sql = "SELECT * FROM varianti 
                WHERE piatto_id = :piatto_id AND disponibile = 1
                ORDER BY ordinamento";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['piatto_id' => $piattoId]);
        return $stmt->fetchAll();
    }

    public function setAllergeni($piattoId, $allergeniIds)
    {
        $this->db->prepare("DELETE FROM piatti_allergeni WHERE piatto_id = :piatto_id")
            ->execute(['piatto_id' => $piattoId]);

        if (!empty($allergeniIds)) {
            $sql = "INSERT INTO piatti_allergeni (piatto_id, allergene_id) VALUES (:piatto_id, :allergene_id)";
            $stmt = $this->db->prepare($sql);
            foreach ($allergeniIds as $allergeneId) {
                $stmt->execute([
                    'piatto_id' => $piattoId,
                    'allergene_id' => $allergeneId
                ]);
            }
        }
        return true;
    }

    public function setCaratteristiche($piattoId, $caratteristicheIds)
    {
        $this->db->prepare("DELETE FROM piatti_caratteristiche WHERE piatto_id = :piatto_id")
            ->execute(['piatto_id' => $piattoId]);

        if (!empty($caratteristicheIds)) {
            $sql = "INSERT INTO piatti_caratteristiche (piatto_id, caratteristica_id) VALUES (:piatto_id, :caratteristica_id)";
            $stmt = $this->db->prepare($sql);
            foreach ($caratteristicheIds as $caratteristicaId) {
                $stmt->execute([
                    'piatto_id' => $piattoId,
                    'caratteristica_id' => $caratteristicaId
                ]);
            }
        }
        return true;
    }

    public function toggleDisponibilita($piattoId)
    {
        $piatto = $this->getById($piattoId);
        if ($piatto) {
            return $this->update($piattoId, ['disponibile' => $piatto['disponibile'] ? 0 : 1]);
        }
        return false;
    }

    public function search($localeId, $filters = [])
    {
        $sql = "SELECT DISTINCT p.*, c.nome as categoria_nome
                FROM piatti p
                INNER JOIN categorie c ON p.categoria_id = c.id
                INNER JOIN menu m ON c.menu_id = m.id
                WHERE m.locale_id = :locale_id AND p.disponibile = 1";

        $params = ['locale_id' => $localeId];

        if (!empty($filters['categoria'])) {
            $sql .= " AND p.categoria_id = :categoria_id";
            $params['categoria_id'] = $filters['categoria'];
        }

        if (!empty($filters['escludiAllergeni'])) {
            $allergeniIds = implode(',', array_map('intval', $filters['escludiAllergeni']));
            $sql .= " AND p.id NOT IN (
                SELECT piatto_id FROM piatti_allergeni 
                WHERE allergene_id IN ({$allergeniIds})
            )";
        }

        if (!empty($filters['caratteristiche'])) {
            $caratteristicheIds = implode(',', array_map('intval', $filters['caratteristiche']));
            $sql .= " AND p.id IN (
                SELECT piatto_id FROM piatti_caratteristiche 
                WHERE caratteristica_id IN ({$caratteristicheIds})
            )";
        }

        if (!empty($filters['cerca'])) {
            $sql .= " AND (p.nome LIKE :cerca OR p.descrizione LIKE :cerca OR p.ingredienti LIKE :cerca)";
            $params['cerca'] = '%' . $filters['cerca'] . '%';
        }

        $sql .= " ORDER BY p.nome ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
