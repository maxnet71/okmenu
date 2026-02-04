<?php

class Menu extends Model
{
    protected $table = 'menu';

    public function getByLocaleId($localeId, $published = null)
    {
        $conditions = ['locale_id' => $localeId];
        if ($published !== null) {
            $conditions['pubblicato'] = $published ? 1 : 0;
        }
        return $this->getWhere($conditions, 'ordinamento ASC, nome ASC');
    }

    public function getPublished($localeId)
    {
        $sql = "SELECT * FROM {$this->table} 
                WHERE locale_id = :locale_id 
                AND pubblicato = 1 
                AND (visibile_da IS NULL OR visibile_da <= NOW())
                AND (visibile_a IS NULL OR visibile_a >= NOW())
                ORDER BY ordinamento ASC, nome ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['locale_id' => $localeId]);
        return $stmt->fetchAll();
    }

    public function getMainMenus($localeId)
    {
        return $this->getWhere([
            'locale_id' => $localeId,
            'parent_id' => null
        ], 'ordinamento ASC');
    }

    public function getSubMenus($parentId)
    {
        return $this->getWhere(['parent_id' => $parentId], 'ordinamento ASC');
    }

    public function hasSubMenus($menuId)
    {
        return $this->count(['parent_id' => $menuId]) > 0;
    }

    public function togglePublish($menuId)
    {
        $menu = $this->getById($menuId);
        if ($menu) {
            return $this->update($menuId, ['pubblicato' => $menu['pubblicato'] ? 0 : 1]);
        }
        return false;
    }
}
