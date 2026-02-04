<?php

class Statistica extends Model
{
    protected $table = 'statistiche';

    public function getByLocaleId($localeId, $dataInizio, $dataFine)
    {
        $sql = "SELECT * FROM {$this->table} 
                WHERE locale_id = :locale_id 
                AND data BETWEEN :data_inizio AND :data_fine
                ORDER BY data ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'locale_id' => $localeId,
            'data_inizio' => $dataInizio,
            'data_fine' => $dataFine
        ]);
        return $stmt->fetchAll();
    }

    public function incrementVisualizzazioni($localeId, $data = null)
    {
        if (!$data) {
            $data = date('Y-m-d');
        }
        return $this->incrementField($localeId, $data, 'visualizzazioni_menu');
    }

    public function incrementScansioni($localeId, $data = null)
    {
        if (!$data) {
            $data = date('Y-m-d');
        }
        return $this->incrementField($localeId, $data, 'scansioni_qr');
    }

    public function incrementOrdini($localeId, $importo, $data = null)
    {
        if (!$data) {
            $data = date('Y-m-d');
        }
        
        $this->ensureRecord($localeId, $data);
        
        $sql = "UPDATE {$this->table} 
                SET ordini = ordini + 1, 
                    fatturato = fatturato + :importo
                WHERE locale_id = :locale_id AND data = :data";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'locale_id' => $localeId,
            'data' => $data,
            'importo' => $importo
        ]);
    }

    private function incrementField($localeId, $data, $field)
    {
        $this->ensureRecord($localeId, $data);
        
        $sql = "UPDATE {$this->table} 
                SET {$field} = {$field} + 1 
                WHERE locale_id = :locale_id AND data = :data";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'locale_id' => $localeId,
            'data' => $data
        ]);
    }

    private function ensureRecord($localeId, $data)
    {
        $exists = $this->getOneWhere([
            'locale_id' => $localeId,
            'data' => $data
        ]);

        if (!$exists) {
            $this->insert([
                'locale_id' => $localeId,
                'data' => $data
            ]);
        }
    }

    public function getTotali($localeId, $dataInizio, $dataFine)
    {
        $sql = "SELECT 
                    SUM(visualizzazioni_menu) as totale_visualizzazioni,
                    SUM(scansioni_qr) as totale_scansioni,
                    SUM(ordini) as totale_ordini,
                    SUM(fatturato) as totale_fatturato
                FROM {$this->table}
                WHERE locale_id = :locale_id
                AND data BETWEEN :data_inizio AND :data_fine";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'locale_id' => $localeId,
            'data_inizio' => $dataInizio,
            'data_fine' => $dataFine
        ]);
        return $stmt->fetch();
    }
}
