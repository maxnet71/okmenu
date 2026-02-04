<?php

class Prenotazione extends Model
{
    protected $table = 'prenotazioni';

    public function getByLocaleId($localeId, $filters = [])
    {
        $sql = "SELECT * FROM {$this->table} WHERE locale_id = :locale_id";
        $params = ['locale_id' => $localeId];

        if (!empty($filters['stato'])) {
            $sql .= " AND stato = :stato";
            $params['stato'] = $filters['stato'];
        }

        if (!empty($filters['data'])) {
            $sql .= " AND data_prenotazione = :data";
            $params['data'] = $filters['data'];
        }

        if (!empty($filters['data_da'])) {
            $sql .= " AND data_prenotazione >= :data_da";
            $params['data_da'] = $filters['data_da'];
        }

        if (!empty($filters['data_a'])) {
            $sql .= " AND data_prenotazione <= :data_a";
            $params['data_a'] = $filters['data_a'];
        }

        $sql .= " ORDER BY data_prenotazione DESC, ora_prenotazione ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getByData($localeId, $data)
    {
        return $this->getWhere([
            'locale_id' => $localeId,
            'data_prenotazione' => $data
        ], 'ora_prenotazione ASC');
    }

    public function conferma($prenotazioneId)
    {
        return $this->update($prenotazioneId, ['stato' => 'confermata']);
    }

    public function annulla($prenotazioneId)
    {
        return $this->update($prenotazioneId, ['stato' => 'annullata']);
    }

    public function checkDisponibilita($localeId, $data, $ora, $numeroPersone)
    {
        $sql = "SELECT COUNT(*) as count, SUM(numero_persone) as totale_persone
                FROM {$this->table}
                WHERE locale_id = :locale_id
                AND data_prenotazione = :data
                AND ora_prenotazione BETWEEN 
                    DATE_SUB(:ora, INTERVAL 1 HOUR) AND 
                    DATE_ADD(:ora, INTERVAL 1 HOUR)
                AND stato != 'annullata'";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'locale_id' => $localeId,
            'data' => $data,
            'ora' => $ora
        ]);
        return $stmt->fetch();
    }
}
