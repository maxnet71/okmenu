<?php

/**
 * Class NotificationManager
 * Gestisce l'invio di notifiche email/SMS per ordini
 */
class NotificationManager
{
    private $db;
    
    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Invia notifica nuovo ordine al ristoratore
     * 
     * @param array $ordine Dati ordine completo
     * @return bool
     */
    public function inviaNotificaNuovoOrdine($ordine)
    {
        $locale = $this->getLocale($ordine['locale_id']);
        
        $oggetto = "🔔 NUOVO ORDINE #{$ordine['numero_ordine']}";
        
        $corpo = $this->renderTemplate('nuovo_ordine_ristoratore', [
            'ordine' => $ordine,
            'locale' => $locale,
            'link_conferma' => BASE_URL . "/dashboard/ordini/view.php?id={$ordine['id']}",
            'scadenza' => date('H:i', strtotime($ordine['scadenza_conferma']))
        ]);
        
        return $this->inviaEmail(
            $ordine['id'],
            'ristoratore',
            'nuovo_ordine',
            $locale['email'],
            $oggetto,
            $corpo
        );
    }
    
    /**
     * Invia conferma ordine al cliente
     */
    public function inviaConfermaCliente($ordine)
    {
        $locale = $this->getLocale($ordine['locale_id']);
        
        $oggetto = "✅ Ordine #{$ordine['numero_ordine']} Confermato!";
        
        $corpo = $this->renderTemplate('conferma_ordine_cliente', [
            'ordine' => $ordine,
            'locale' => $locale,
            'link_tracking' => $this->getTrackingLink($ordine['tracking_token'])
        ]);
        
        return $this->inviaEmail(
            $ordine['id'],
            'cliente',
            'ordine_confermato',
            $ordine['email_cliente'],
            $oggetto,
            $corpo
        );
    }
    
    /**
     * Invia rifiuto ordine al cliente
     */
    public function inviaRifiutoCliente($ordine)
    {
        $locale = $this->getLocale($ordine['locale_id']);
        
        $oggetto = "❌ Ordine #{$ordine['numero_ordine']} - Aggiornamento";
        
        $corpo = $this->renderTemplate('rifiuto_ordine_cliente', [
            'ordine' => $ordine,
            'locale' => $locale,
            'motivo' => $ordine['motivo_rifiuto'] ?? 'Il ristorante non può evadere il tuo ordine.'
        ]);
        
        return $this->inviaEmail(
            $ordine['id'],
            'cliente',
            'ordine_rifiutato',
            $ordine['email_cliente'],
            $oggetto,
            $corpo
        );
    }
    
    /**
     * Invia notifica scadenza al cliente
     */
    public function inviaScadenzaCliente($ordine)
    {
        $locale = $this->getLocale($ordine['locale_id']);
        
        $oggetto = "⏰ Ordine #{$ordine['numero_ordine']} - Timeout";
        
        $corpo = $this->renderTemplate('scadenza_ordine_cliente', [
            'ordine' => $ordine,
            'locale' => $locale
        ]);
        
        return $this->inviaEmail(
            $ordine['id'],
            'cliente',
            'ordine_scaduto',
            $ordine['email_cliente'],
            $oggetto,
            $corpo
        );
    }
    
    /**
     * Invia notifica ordine pronto
     */
    public function inviaOrdineProto($ordine)
    {
        $locale = $this->getLocale($ordine['locale_id']);
        
        $oggetto = "🍕 Ordine #{$ordine['numero_ordine']} Pronto!";
        
        $corpo = $this->renderTemplate('ordine_pronto_cliente', [
            'ordine' => $ordine,
            'locale' => $locale,
            'link_tracking' => $this->getTrackingLink($ordine['tracking_token'])
        ]);
        
        return $this->inviaEmail(
            $ordine['id'],
            'cliente',
            'ordine_pronto',
            $ordine['email_cliente'],
            $oggetto,
            $corpo
        );
    }
    
    /**
     * Invia notifica ordine in consegna
     */
    public function inviaOrdineInConsegna($ordine)
    {
        $locale = $this->getLocale($ordine['locale_id']);
        
        $oggetto = "🚚 Ordine #{$ordine['numero_ordine']} In Consegna";
        
        $corpo = $this->renderTemplate('ordine_consegna_cliente', [
            'ordine' => $ordine,
            'locale' => $locale,
            'link_tracking' => $this->getTrackingLink($ordine['tracking_token'])
        ]);
        
        return $this->inviaEmail(
            $ordine['id'],
            'cliente',
            'ordine_spedito',
            $ordine['email_cliente'],
            $oggetto,
            $corpo
        );
    }
    
    /**
     * Invia promemoria scadenza al ristoratore
     */
    public function inviaPromemoriaScadenza($ordine)
    {
        $locale = $this->getLocale($ordine['locale_id']);
        
        $minuti = round((strtotime($ordine['scadenza_conferma']) - time()) / 60);
        
        $oggetto = "⏰ URGENTE: Ordine #{$ordine['numero_ordine']} - {$minuti} minuti rimanenti";
        
        $corpo = $this->renderTemplate('promemoria_scadenza', [
            'ordine' => $ordine,
            'locale' => $locale,
            'minuti' => $minuti,
            'link_conferma' => BASE_URL . "/dashboard/ordini/view.php?id={$ordine['id']}"
        ]);
        
        return $this->inviaEmail(
            $ordine['id'],
            'ristoratore',
            'promemoria_scadenza',
            $locale['email'],
            $oggetto,
            $corpo
        );
    }
    
    /**
     * Invia email e salva log nel database
     */
    private function inviaEmail($ordineId, $tipoDestinatario, $tipoNotifica, $destinatario, $oggetto, $corpo)
    {
        // Salva notifica nel database
        $notificaId = $this->salvaNotifica($ordineId, $tipoDestinatario, $tipoNotifica, $destinatario, $oggetto, $corpo);
        
        // Invia email
        $headers = [
            'From: ' . (defined('MAIL_FROM') ? MAIL_FROM : 'noreply@' . $_SERVER['HTTP_HOST']),
            'Reply-To: ' . (defined('MAIL_REPLY_TO') ? MAIL_REPLY_TO : 'info@' . $_SERVER['HTTP_HOST']),
            'Content-Type: text/html; charset=UTF-8',
            'X-Mailer: PHP/' . phpversion()
        ];
        
        $success = mail($destinatario, $oggetto, $corpo, implode("\r\n", $headers));
        
        // Aggiorna stato notifica
        $this->aggiornaStatoNotifica($notificaId, $success ? 'sent' : 'failed', $success ? null : 'Mail function failed');
        
        return $success;
    }
    
    /**
     * Salva notifica nel database
     */
    private function salvaNotifica($ordineId, $tipoDestinatario, $tipoNotifica, $destinatario, $oggetto, $corpo)
    {
        $sql = "INSERT INTO ordini_notifiche 
                (ordine_id, tipo_destinatario, tipo_notifica, canale, destinatario, oggetto, corpo, created_at)
                VALUES (:ordine_id, :tipo_dest, :tipo_not, 'email', :dest, :ogg, :corpo, NOW())";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'ordine_id' => $ordineId,
            'tipo_dest' => $tipoDestinatario,
            'tipo_not' => $tipoNotifica,
            'dest' => $destinatario,
            'ogg' => $oggetto,
            'corpo' => $corpo
        ]);
        
        return $this->db->lastInsertId();
    }
    
    /**
     * Aggiorna stato notifica
     */
    private function aggiornaStatoNotifica($notificaId, $stato, $errore = null)
    {
        $sql = "UPDATE ordini_notifiche 
                SET stato_invio = :stato, 
                    errore = :errore,
                    tentativi_invio = tentativi_invio + 1,
                    inviato_at = IF(:stato = 'sent', NOW(), inviato_at)
                WHERE id = :id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'stato' => $stato,
            'errore' => $errore,
            'id' => $notificaId
        ]);
    }
    
    /**
     * Carica dati locale
     */
    private function getLocale($localeId)
    {
        $sql = "SELECT * FROM locali WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $localeId]);
        return $stmt->fetch();
    }
    
    /**
     * Genera link tracking pubblico
     */
    private function getTrackingLink($token)
    {
        return BASE_URL . "/ordine-tracking.php?token={$token}";
    }
    
    /**
     * Render template email
     */
    private function renderTemplate($template, $data)
    {
        extract($data);
        
        ob_start();
        
        // Template inline per semplicità - in produzione usare file separati
        switch ($template) {
            case 'nuovo_ordine_ristoratore':
                include __DIR__ . '/../templates/email/nuovo_ordine_ristoratore.php';
                break;
                
            case 'conferma_ordine_cliente':
                include __DIR__ . '/../templates/email/conferma_ordine_cliente.php';
                break;
                
            case 'rifiuto_ordine_cliente':
                include __DIR__ . '/../templates/email/rifiuto_ordine_cliente.php';
                break;
                
            case 'scadenza_ordine_cliente':
                include __DIR__ . '/../templates/email/scadenza_ordine_cliente.php';
                break;
                
            case 'ordine_pronto_cliente':
                include __DIR__ . '/../templates/email/ordine_pronto_cliente.php';
                break;
                
            case 'ordine_consegna_cliente':
                include __DIR__ . '/../templates/email/ordine_consegna_cliente.php';
                break;
                
            case 'promemoria_scadenza':
                include __DIR__ . '/../templates/email/promemoria_scadenza.php';
                break;
                
            default:
                return $this->renderTemplateDefault($data);
        }
        
        return ob_get_clean();
    }
    
    /**
     * Template email default semplice
     */
    private function renderTemplateDefault($data)
    {
        return '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h2>Notifica Ordine</h2>
        <p>' . print_r($data, true) . '</p>
    </div>
</body>
</html>';
    }
}