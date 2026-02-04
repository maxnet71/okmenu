<?php

/**
 * Class Ordine (ESTESO)
 * Gestione ordini con supporto pagamento anticipato e workflow conferma
 * 
 * POSIZIONE: /classes/models/Ordine.php
 */
class Ordine extends Model
{
    protected $table = 'ordini';

    // ========== METODI ESISTENTI (mantenuti) ==========

    public function getByLocaleId($localeId, $filters = [])
    {
        $sql = "SELECT * FROM {$this->table} WHERE locale_id = :locale_id";
        $params = ['locale_id' => $localeId];

        if (!empty($filters['stato'])) {
            $sql .= " AND stato = :stato";
            $params['stato'] = $filters['stato'];
        }

        if (!empty($filters['tipo'])) {
            $sql .= " AND tipo = :tipo";
            $params['tipo'] = $filters['tipo'];
        }

        if (!empty($filters['data_da'])) {
            $sql .= " AND DATE(created_at) >= :data_da";
            $params['data_da'] = $filters['data_da'];
        }

        if (!empty($filters['data_a'])) {
            $sql .= " AND DATE(created_at) <= :data_a";
            $params['data_a'] = $filters['data_a'];
        }

        $sql .= " ORDER BY created_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getWithDetails($ordineId)
    {
        $ordine = $this->getById($ordineId);
        if ($ordine) {
            $ordine['dettagli'] = $this->getDettagli($ordineId);
        }
        return $ordine;
    }

    public function getDettagli($ordineId)
    {
        $sql = "SELECT od.*, p.immagine as piatto_immagine
                FROM ordini_dettagli od
                LEFT JOIN piatti p ON od.piatto_id = p.id
                WHERE od.ordine_id = :ordine_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['ordine_id' => $ordineId]);
        $dettagli = $stmt->fetchAll();

        foreach ($dettagli as &$dettaglio) {
            $dettaglio['varianti'] = $this->getDettaglioVarianti($dettaglio['id']);
        }

        return $dettagli;
    }

    public function getDettaglioVarianti($dettaglioId)
    {
        $sql = "SELECT * FROM ordini_varianti WHERE ordine_dettaglio_id = :dettaglio_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['dettaglio_id' => $dettaglioId]);
        return $stmt->fetchAll();
    }

    public function create($data, $carrello)
    {
        $this->db->beginTransaction();

        try {
            $data['numero_ordine'] = $this->generateNumeroOrdine($data['locale_id']);
            $ordineId = $this->insert($data);

            foreach ($carrello as $item) {
                $dettaglioData = [
                    'ordine_id' => $ordineId,
                    'piatto_id' => $item['piatto_id'],
                    'nome_piatto' => $item['nome'],
                    'quantita' => $item['quantita'],
                    'prezzo_unitario' => $item['prezzo'],
                    'note' => $item['note'] ?? null
                ];
                
                $sql = "INSERT INTO ordini_dettagli (ordine_id, piatto_id, nome_piatto, quantita, prezzo_unitario, note) 
                        VALUES (:ordine_id, :piatto_id, :nome_piatto, :quantita, :prezzo_unitario, :note)";
                $stmt = $this->db->prepare($sql);
                $stmt->execute($dettaglioData);
                $dettaglioId = $this->db->lastInsertId();

                if (!empty($item['varianti'])) {
                    foreach ($item['varianti'] as $variante) {
                        $varianteData = [
                            'ordine_dettaglio_id' => $dettaglioId,
                            'variante_id' => $variante['id'],
                            'nome_variante' => $variante['nome'],
                            'prezzo' => $variante['prezzo_aggiuntivo']
                        ];
                        $sql = "INSERT INTO ordini_varianti (ordine_dettaglio_id, variante_id, nome_variante, prezzo) 
                                VALUES (:ordine_dettaglio_id, :variante_id, :nome_variante, :prezzo)";
                        $stmt = $this->db->prepare($sql);
                        $stmt->execute($varianteData);
                    }
                }
            }

            $this->db->commit();
            return $ordineId;
        } catch (Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }

    public function updateStato($ordineId, $stato)
    {
        return $this->update($ordineId, ['stato' => $stato]);
    }

    private function generateNumeroOrdine($localeId)
    {
        $prefix = 'ORD';
        $date = date('Ymd');
        $sql = "SELECT COUNT(*) as count FROM {$this->table} 
                WHERE locale_id = :locale_id AND DATE(created_at) = CURDATE()";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['locale_id' => $localeId]);
        $result = $stmt->fetch();
        $numero = str_pad($result['count'] + 1, 4, '0', STR_PAD_LEFT);
        return "{$prefix}-{$date}-{$numero}";
    }

    public function getStatistiche($localeId, $dataInizio, $dataFine)
    {
        $sql = "SELECT 
                    COUNT(*) as totale_ordini,
                    SUM(totale) as fatturato_totale,
                    AVG(totale) as scontrino_medio,
                    SUM(CASE WHEN stato = 'consegnato' THEN 1 ELSE 0 END) as ordini_completati,
                    SUM(CASE WHEN stato = 'annullato' THEN 1 ELSE 0 END) as ordini_annullati
                FROM {$this->table}
                WHERE locale_id = :locale_id
                AND created_at BETWEEN :data_inizio AND :data_fine";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'locale_id' => $localeId,
            'data_inizio' => $dataInizio,
            'data_fine' => $dataFine
        ]);
        return $stmt->fetch();
    }

    // ========== NUOVI METODI PER ORDINI ASPORTO/CONSEGNA ==========

    /**
     * Crea ordine asporto/consegna con pagamento anticipato
     * 
     * @param array $data Dati ordine
     * @param array $carrello Piatti ordinati
     * @param bool $autorizzaPagamento Se true, autorizza pagamento
     * @return int|false ID ordine o false
     */
    public function createOrdineAsporto($data, $carrello, $autorizzaPagamento = true)
    {
        $this->db->beginTransaction();

        try {
            // Genera numero ordine e tracking token
            $data['numero_ordine'] = $this->generateNumeroOrdine($data['locale_id']);
            $data['stato'] = 'creato';
            
            // Calcola scadenza conferma
            $config = $this->getConfigurazioneLocale($data['locale_id']);
            $minutiScadenza = $config['tempo_scadenza_conferma'] ?? 15;
            $data['scadenza_conferma'] = date('Y-m-d H:i:s', strtotime("+{$minutiScadenza} minutes"));
            
            // Crea ordine
            $ordineId = $this->create($data, $carrello);
            
            if (!$ordineId) {
                throw new Exception("Errore creazione ordine");
            }
            
            // Autorizza pagamento se richiesto
            if ($autorizzaPagamento) {
                $pagamento = new Pagamento($config['gateway_pagamento'] ?? 'stripe', $config['modalita_test'] ?? true);
                
                $authResult = $pagamento->autorizza($data['totale'], [
                    'ordine_id' => $ordineId,
                    'ordine_numero' => $data['numero_ordine'],
                    'locale_id' => $data['locale_id'],
                    'locale_nome' => $this->getLocaleNome($data['locale_id']),
                    'cliente_email' => $data['email_cliente']
                ]);
                
                if (!$authResult) {
                    throw new Exception("Errore autorizzazione pagamento");
                }
                
                // Aggiorna ordine con dati pagamento
                $this->update($ordineId, [
                    'pagamento_id' => $authResult['auth_id'],
                    'pagamento_provider' => $config['gateway_pagamento'] ?? 'stripe',
                    'pagamento_stato' => 'authorized',
                    'pagamento_importo' => $data['totale'],
                    'pagamento_prenotato_at' => date('Y-m-d H:i:s'),
                    'stato' => 'pagamento_autorizzato'
                ]);
                
                // Log cambio stato
                $this->logCambioStato($ordineId, 'creato', 'pagamento_autorizzato', null, 'Pagamento autorizzato con successo');
            }
            
            // Cambia stato a "attesa_conferma"
            $this->cambiaStato($ordineId, 'attesa_conferma', null, 'In attesa conferma ristoratore');
            
            // Invia notifica al ristoratore
            $notificationManager = new NotificationManager();
            $ordine = $this->getWithDetails($ordineId);
            $notificationManager->inviaNotificaNuovoOrdine($ordine);
            
            $this->db->commit();
            return $ordineId;
            
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Errore createOrdineAsporto: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Conferma ordine da parte del ristoratore
     * 
     * @param int $ordineId ID ordine
     * @param int $userId ID utente che conferma
     * @param int|null $tempoPreparazione Minuti preparazione
     * @return bool
     */
    public function confermaOrdine($ordineId, $userId, $tempoPreparazione = null)
    {
        $this->db->beginTransaction();

        try {
            $ordine = $this->getById($ordineId);
            
            if (!$ordine) {
                throw new Exception("Ordine non trovato");
            }
            
            if ($ordine['stato'] !== 'attesa_conferma') {
                throw new Exception("Ordine non in stato attesa_conferma");
            }
            
            // Lock per evitare doppia conferma
            $sql = "SELECT id FROM {$this->table} WHERE id = :id FOR UPDATE";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['id' => $ordineId]);
            
            // Cattura pagamento
            if ($ordine['pagamento_id'] && $ordine['pagamento_stato'] === 'authorized') {
                $config = $this->getConfigurazioneLocale($ordine['locale_id']);
                $pagamento = new Pagamento($config['gateway_pagamento'] ?? 'stripe', $config['modalita_test'] ?? true);
                
                $captureResult = $pagamento->cattura($ordine['pagamento_id']);
                
                if (!$captureResult) {
                    throw new Exception("Errore cattura pagamento");
                }
                
                // Aggiorna dati pagamento
                $this->update($ordineId, [
                    'pagamento_stato' => 'captured',
                    'pagamento_catturato_at' => date('Y-m-d H:i:s')
                ]);
            }
            
            // Aggiorna ordine
            $updateData = [
                'stato' => 'confermato',
                'confermato_at' => date('Y-m-d H:i:s'),
                'confermato_da' => $userId
            ];
            
            if ($tempoPreparazione) {
                $updateData['tempo_preparazione_stimato'] = $tempoPreparazione;
            }
            
            $this->update($ordineId, $updateData);
            
            // Log cambio stato
            $this->logCambioStato($ordineId, 'attesa_conferma', 'confermato', $userId, 'Ordine confermato dal ristoratore');
            
            // Invia email conferma al cliente
            $notificationManager = new NotificationManager();
            $ordineAggiornato = $this->getWithDetails($ordineId);
            $notificationManager->inviaConfermaCliente($ordineAggiornato);
            
            $this->db->commit();
            return true;
            
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Errore confermaOrdine: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Rifiuta ordine da parte del ristoratore
     * 
     * @param int $ordineId ID ordine
     * @param int $userId ID utente che rifiuta
     * @param string $motivo Motivo rifiuto
     * @return bool
     */
    public function rifiutaOrdine($ordineId, $userId, $motivo = null)
    {
        $this->db->beginTransaction();

        try {
            $ordine = $this->getById($ordineId);
            
            if (!$ordine) {
                throw new Exception("Ordine non trovato");
            }
            
            if ($ordine['stato'] !== 'attesa_conferma') {
                throw new Exception("Ordine non in stato attesa_conferma");
            }
            
            // Lock per evitare doppia operazione
            $sql = "SELECT id FROM {$this->table} WHERE id = :id FOR UPDATE";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['id' => $ordineId]);
            
            // Annulla autorizzazione pagamento
            if ($ordine['pagamento_id'] && $ordine['pagamento_stato'] === 'authorized') {
                $config = $this->getConfigurazioneLocale($ordine['locale_id']);
                $pagamento = new Pagamento($config['gateway_pagamento'] ?? 'stripe', $config['modalita_test'] ?? true);
                
                $voidResult = $pagamento->annulla($ordine['pagamento_id']);
                
                if (!$voidResult) {
                    throw new Exception("Errore annullamento pagamento");
                }
                
                // Aggiorna dati pagamento
                $this->update($ordineId, [
                    'pagamento_stato' => 'voided',
                    'pagamento_annullato_at' => date('Y-m-d H:i:s')
                ]);
            }
            
            // Aggiorna ordine
            $this->update($ordineId, [
                'stato' => 'rifiutato',
                'rifiutato_at' => date('Y-m-d H:i:s'),
                'rifiutato_da' => $userId,
                'motivo_rifiuto' => $motivo
            ]);
            
            // Log cambio stato
            $this->logCambioStato($ordineId, 'attesa_conferma', 'rifiutato', $userId, "Ordine rifiutato: {$motivo}");
            
            // Invia email rifiuto al cliente
            $notificationManager = new NotificationManager();
            $ordineAggiornato = $this->getWithDetails($ordineId);
            $notificationManager->inviaRifiutoCliente($ordineAggiornato);
            
            $this->db->commit();
            return true;
            
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Errore rifiutaOrdine: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Gestisce ordini scaduti (chiamato da cron)
     * 
     * @return int Numero ordini gestiti
     */
    public function gestisciOrdiniScaduti()
    {
        $sql = "SELECT * FROM {$this->table} 
                WHERE stato = 'attesa_conferma' 
                AND scadenza_conferma < NOW()
                AND pagamento_stato = 'authorized'";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $ordiniScaduti = $stmt->fetchAll();
        
        $count = 0;
        foreach ($ordiniScaduti as $ordine) {
            if ($this->annullaOrdineScaduto($ordine['id'])) {
                $count++;
            }
        }
        
        return $count;
    }

    /**
     * Annulla ordine scaduto
     */
    private function annullaOrdineScaduto($ordineId)
    {
        $this->db->beginTransaction();

        try {
            $ordine = $this->getById($ordineId);
            
            // Annulla autorizzazione pagamento
            if ($ordine['pagamento_id'] && $ordine['pagamento_stato'] === 'authorized') {
                $config = $this->getConfigurazioneLocale($ordine['locale_id']);
                $pagamento = new Pagamento($config['gateway_pagamento'] ?? 'stripe', $config['modalita_test'] ?? true);
                
                $pagamento->annulla($ordine['pagamento_id']);
                
                $this->update($ordineId, [
                    'pagamento_stato' => 'voided',
                    'pagamento_annullato_at' => date('Y-m-d H:i:s')
                ]);
            }
            
            // Aggiorna ordine
            $this->update($ordineId, [
                'stato' => 'scaduto'
            ]);
            
            // Log cambio stato
            $this->logCambioStato($ordineId, 'attesa_conferma', 'scaduto', null, 'Ordine scaduto per mancata conferma');
            
            // Invia email al cliente
            $notificationManager = new NotificationManager();
            $ordineAggiornato = $this->getWithDetails($ordineId);
            $notificationManager->inviaScadenzaCliente($ordineAggiornato);
            
            $this->db->commit();
            return true;
            
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Errore annullaOrdineScaduto: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Cambia stato ordine con log
     * 
     * @param int $ordineId
     * @param string $nuovoStato
     * @param int|null $userId
     * @param string|null $note
     * @return bool
     */
    public function cambiaStato($ordineId, $nuovoStato, $userId = null, $note = null)
    {
        $ordine = $this->getById($ordineId);
        if (!$ordine) {
            return false;
        }
        
        $statoVecchio = $ordine['stato'];
        
        // Aggiorna stato
        $this->update($ordineId, ['stato' => $nuovoStato]);
        
        // Log cambio stato
        $this->logCambioStato($ordineId, $statoVecchio, $nuovoStato, $userId, $note);
        
        return true;
    }

    /**
     * Salva log cambio stato
     */
    private function logCambioStato($ordineId, $statoVecchio, $statoNuovo, $userId = null, $note = null)
    {
        $sql = "INSERT INTO ordini_stati_log 
                (ordine_id, stato_precedente, stato_nuovo, user_id, note, ip_address, user_agent, created_at)
                VALUES (:ordine_id, :stato_vecchio, :stato_nuovo, :user_id, :note, :ip, :ua, NOW())";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'ordine_id' => $ordineId,
            'stato_vecchio' => $statoVecchio,
            'stato_nuovo' => $statoNuovo,
            'user_id' => $userId,
            'note' => $note,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
            'ua' => $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
    }

    /**
     * Ottiene ordini in attesa di conferma per un locale
     */
    public function getOrdiniInAttesa($localeId)
    {
        $sql = "SELECT * FROM {$this->table} 
                WHERE locale_id = :locale_id 
                AND stato = 'attesa_conferma'
                ORDER BY created_at ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['locale_id' => $localeId]);
        return $stmt->fetchAll();
    }

    /**
     * Ottiene configurazione locale per ordini
     */
    private function getConfigurazioneLocale($localeId)
    {
        $sql = "SELECT * FROM ordini_configurazioni WHERE locale_id = :locale_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['locale_id' => $localeId]);
        return $stmt->fetch() ?: [];
    }

    /**
     * Ottiene nome locale
     */
    private function getLocaleNome($localeId)
    {
        $sql = "SELECT nome FROM locali WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $localeId]);
        $result = $stmt->fetch();
        return $result['nome'] ?? 'Ristorante';
    }

    /**
     * Ottiene ordine tramite tracking token pubblico
     */
    public function getByTrackingToken($token)
    {
        $sql = "SELECT * FROM {$this->table} WHERE tracking_token = :token";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['token' => $token]);
        return $stmt->fetch();
    }
}