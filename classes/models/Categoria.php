<?php

class Categoria extends Model
{
    protected $table = 'categorie';

    public function getByMenuId($menuId)
    {
        return $this->getWhere(['menu_id' => $menuId, 'attivo' => 1], 'ordinamento ASC');
    }

    public function getWithPiatti($categoriaId)
    {
        $categoria = $this->getById($categoriaId);
        if ($categoria) {
            $piattiModel = new Piatto();
            $categoria['piatti'] = $piattiModel->getByCategoriaId($categoriaId);
        }
        return $categoria;
    }

    public function countPiatti($categoriaId)
    {
        $sql = "SELECT COUNT(*) as total FROM piatti WHERE categoria_id = :categoria_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['categoria_id' => $categoriaId]);
        $result = $stmt->fetch();
        return $result['total'];
    }
}
