<?php

class QRCode extends Model
{
    protected $table = 'qrcode';

    public function getByLocaleId($localeId)
    {
        return $this->getWhere(['locale_id' => $localeId], 'created_at DESC');
    }

    public function getByCodice($codice)
    {
        return $this->getOneWhere(['codice' => $codice, 'attivo' => 1]);
    }
    
    public function getByCode($codice)
    {
        return $this->getByCodice($codice);
    }
    
    public function getByLegacyUrl($legacyUrl)
    {
        return $this->getOneWhere(['legacy_url' => $legacyUrl, 'attivo' => 1]);
    }

    public function generate($localeId, $menuId = null, $tipo = 'menu', $tavolo = null, $abilitaOrdini = 0, $legacyUrl = '')
    {
        $codice = $this->generateUniqueCodice();
        
        $data = [
            'locale_id' => $localeId,
            'menu_id' => $menuId,
            'codice' => $codice,
            'tipo' => $tipo,
            'tavolo' => $tavolo,
            'abilita_ordini' => $abilitaOrdini
        ];
        
        // Aggiungi legacy_url se specificato
        if (!empty($legacyUrl)) {
            $data['legacy_url'] = $legacyUrl;
        }

        $id = $this->insert($data);
        
        if ($id) {
            $this->generateQRImage($id, $codice);
            return $this->getById($id);
        }

        return false;
    }

    private function generateUniqueCodice()
    {
        do {
            $codice = bin2hex(random_bytes(16));
        } while ($this->getByCodice($codice));

        return $codice;
    }

    private function generateQRImage($id, $codice)
    {
        $url = BASE_URL . '/view.php?codice=' . $codice;
        $uploadPath = __DIR__ . '/../../uploads/qrcode/';
        
        if (!file_exists($uploadPath)) {
            mkdir($uploadPath, 0755, true);
        }
        
        $fileName = 'qr_' . $codice . '.png';
        $filePath = $uploadPath . $fileName;
        
        // Usa API esterna invece di phpqrcode
        $qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' . urlencode($url);
        $qrImage = file_get_contents($qrUrl);
        
        if ($qrImage !== false) {
            file_put_contents($filePath, $qrImage);
            $this->update($id, ['file_path' => 'uploads/qrcode/' . $fileName]);
        }
    }

    public function toggleAttivo($id)
    {
        $qr = $this->getById($id);
        if ($qr) {
            return $this->update($id, ['attivo' => $qr['attivo'] ? 0 : 1]);
        }
        return false;
    }
    
    public function toggleAbilitaOrdini($id)
    {
        $qr = $this->getById($id);
        if ($qr) {
            $nuovoValore = isset($qr['abilita_ordini']) && $qr['abilita_ordini'] ? 0 : 1;
            return $this->update($id, ['abilita_ordini' => $nuovoValore]);
        }
        return false;
    }

    public function incrementScansioni($codice)
    {
        $qr = $this->getByCodice($codice);
        if ($qr) {
            $nuoveScansioni = intval($qr['scansioni'] ?? 0) + 1;
            $this->update($qr['id'], ['scansioni' => $nuoveScansioni]);
            
            // Incrementa anche statistiche locale
            $localeId = $qr['locale_id'];
            if ($localeId) {
                try {
                    $statistiche = new Statistica();
                    $statistiche->incrementScansioni($localeId);
                } catch (Exception $e) {
                    // Statistica potrebbe non esistere
                }
            }
        }
    }

    private function getLocaleIdByCodice($codice)
    {
        $qr = $this->getByCodice($codice);
        return $qr ? $qr['locale_id'] : null;
    }
}