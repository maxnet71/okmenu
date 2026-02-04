<?php

/**
 * Class Pagamento
 * Gestisce integrazione pagamenti con provider (Stripe, PayPal, etc)
 * Supporta Authorization + Capture workflow
 */
class Pagamento extends Model
{
    protected $table = 'ordini';
    
    private $provider;
    private $testMode;
    private $apiKey;
    
    /**
     * Costruttore
     * @param string $provider Nome provider (stripe, paypal)
     * @param bool $testMode Modalità test
     */
    public function __construct($provider = 'stripe', $testMode = true)
    {
        parent::__construct();
        $this->provider = $provider;
        $this->testMode = $testMode;
        $this->loadCredentials();
    }
    
    /**
     * Carica credenziali dal database o config
     */
    private function loadCredentials()
    {
        // TODO: Caricare da ordini_configurazioni
        if ($this->provider === 'stripe') {
            $this->apiKey = $this->testMode 
                ? getenv('STRIPE_TEST_SECRET_KEY') 
                : getenv('STRIPE_LIVE_SECRET_KEY');
        }
    }
    
    /**
     * Autorizza pagamento senza catturarlo
     * 
     * @param float $importo Importo da autorizzare
     * @param array $metadata Metadati ordine
     * @return array|false ['auth_id' => ..., 'status' => ...]
     */
    public function autorizza($importo, $metadata = [])
    {
        try {
            if ($this->provider === 'stripe') {
                return $this->stripeAutorizza($importo, $metadata);
            }
            
            throw new Exception("Provider {$this->provider} non supportato");
            
        } catch (Exception $e) {
            error_log("Errore autorizzazione pagamento: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Autorizzazione Stripe (PaymentIntent con capture_method=manual)
     */
    private function stripeAutorizza($importo, $metadata)
    {
        require_once __DIR__ . '/../vendor/autoload.php';
        \Stripe\Stripe::setApiKey($this->apiKey);
        
        try {
            $paymentIntent = \Stripe\PaymentIntent::create([
                'amount' => intval($importo * 100), // Converti in centesimi
                'currency' => 'eur',
                'capture_method' => 'manual', // CHIAVE: non catturare subito
                'metadata' => $metadata,
                'description' => 'Ordine #' . ($metadata['ordine_numero'] ?? 'N/A'),
                'statement_descriptor' => substr($metadata['locale_nome'] ?? 'Ristorante', 0, 22)
            ]);
            
            return [
                'auth_id' => $paymentIntent->id,
                'status' => $paymentIntent->status,
                'amount' => $importo,
                'currency' => 'EUR',
                'client_secret' => $paymentIntent->client_secret
            ];
            
        } catch (\Stripe\Exception\ApiErrorException $e) {
            error_log("Stripe API Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Cattura pagamento precedentemente autorizzato
     * 
     * @param string $authId ID autorizzazione
     * @param float|null $importo Importo da catturare (null = tutto)
     * @return array|false
     */
    public function cattura($authId, $importo = null)
    {
        try {
            if ($this->provider === 'stripe') {
                return $this->stripeCattura($authId, $importo);
            }
            
            throw new Exception("Provider {$this->provider} non supportato");
            
        } catch (Exception $e) {
            error_log("Errore cattura pagamento: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Cattura Stripe PaymentIntent
     */
    private function stripeCattura($authId, $importo = null)
    {
        require_once __DIR__ . '/../vendor/autoload.php';
        \Stripe\Stripe::setApiKey($this->apiKey);
        
        try {
            $params = [];
            if ($importo !== null) {
                $params['amount_to_capture'] = intval($importo * 100);
            }
            
            $paymentIntent = \Stripe\PaymentIntent::retrieve($authId);
            $captured = $paymentIntent->capture($params);
            
            return [
                'capture_id' => $captured->id,
                'status' => $captured->status,
                'amount' => $captured->amount / 100,
                'captured_at' => date('Y-m-d H:i:s', $captured->created)
            ];
            
        } catch (\Stripe\Exception\ApiErrorException $e) {
            error_log("Stripe Capture Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Annulla autorizzazione (void)
     * 
     * @param string $authId ID autorizzazione
     * @return array|false
     */
    public function annulla($authId)
    {
        try {
            if ($this->provider === 'stripe') {
                return $this->stripeAnnulla($authId);
            }
            
            throw new Exception("Provider {$this->provider} non supportato");
            
        } catch (Exception $e) {
            error_log("Errore annullamento pagamento: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Annulla Stripe PaymentIntent
     */
    private function stripeAnnulla($authId)
    {
        require_once __DIR__ . '/../vendor/autoload.php';
        \Stripe\Stripe::setApiKey($this->apiKey);
        
        try {
            $paymentIntent = \Stripe\PaymentIntent::retrieve($authId);
            $canceled = $paymentIntent->cancel();
            
            return [
                'id' => $canceled->id,
                'status' => $canceled->status,
                'canceled_at' => date('Y-m-d H:i:s', $canceled->canceled_at)
            ];
            
        } catch (\Stripe\Exception\ApiErrorException $e) {
            error_log("Stripe Cancel Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Rimborso pagamento già catturato
     * 
     * @param string $captureId ID cattura
     * @param float|null $importo Importo da rimborsare (null = tutto)
     * @return array|false
     */
    public function rimborsa($captureId, $importo = null)
    {
        try {
            if ($this->provider === 'stripe') {
                return $this->stripeRimborsa($captureId, $importo);
            }
            
            throw new Exception("Provider {$this->provider} non supportato");
            
        } catch (Exception $e) {
            error_log("Errore rimborso pagamento: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Rimborso Stripe
     */
    private function stripeRimborsa($captureId, $importo = null)
    {
        require_once __DIR__ . '/../vendor/autoload.php';
        \Stripe\Stripe::setApiKey($this->apiKey);
        
        try {
            $params = ['payment_intent' => $captureId];
            if ($importo !== null) {
                $params['amount'] = intval($importo * 100);
            }
            
            $refund = \Stripe\Refund::create($params);
            
            return [
                'refund_id' => $refund->id,
                'status' => $refund->status,
                'amount' => $refund->amount / 100,
                'refunded_at' => date('Y-m-d H:i:s', $refund->created)
            ];
            
        } catch (\Stripe\Exception\ApiErrorException $e) {
            error_log("Stripe Refund Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Verifica stato pagamento
     * 
     * @param string $paymentId ID pagamento
     * @return array|false
     */
    public function verificaStato($paymentId)
    {
        try {
            if ($this->provider === 'stripe') {
                return $this->stripeVerificaStato($paymentId);
            }
            
            throw new Exception("Provider {$this->provider} non supportato");
            
        } catch (Exception $e) {
            error_log("Errore verifica stato: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Verifica stato Stripe
     */
    private function stripeVerificaStato($paymentId)
    {
        require_once __DIR__ . '/../vendor/autoload.php';
        \Stripe\Stripe::setApiKey($this->apiKey);
        
        try {
            $paymentIntent = \Stripe\PaymentIntent::retrieve($paymentId);
            
            return [
                'id' => $paymentIntent->id,
                'status' => $paymentIntent->status,
                'amount' => $paymentIntent->amount / 100,
                'currency' => $paymentIntent->currency,
                'created_at' => date('Y-m-d H:i:s', $paymentIntent->created)
            ];
            
        } catch (\Stripe\Exception\ApiErrorException $e) {
            error_log("Stripe Status Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Gestisce webhook del provider
     * 
     * @param string $payload JSON payload
     * @param string $signature Firma webhook
     * @return array|false
     */
    public function handleWebhook($payload, $signature)
    {
        try {
            if ($this->provider === 'stripe') {
                return $this->stripeHandleWebhook($payload, $signature);
            }
            
            throw new Exception("Provider {$this->provider} non supportato");
            
        } catch (Exception $e) {
            error_log("Errore gestione webhook: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Gestisce webhook Stripe
     */
    private function stripeHandleWebhook($payload, $signature)
    {
        require_once __DIR__ . '/../vendor/autoload.php';
        
        $webhookSecret = $this->testMode 
            ? getenv('STRIPE_WEBHOOK_SECRET_TEST') 
            : getenv('STRIPE_WEBHOOK_SECRET_LIVE');
        
        try {
            $event = \Stripe\Webhook::constructEvent(
                $payload,
                $signature,
                $webhookSecret
            );
            
            // Log webhook
            $this->logWebhook($event->id, $event->type, $payload, $signature, true);
            
            return [
                'event_id' => $event->id,
                'type' => $event->type,
                'data' => $event->data->object
            ];
            
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            error_log("Stripe Webhook Signature Error: " . $e->getMessage());
            $this->logWebhook(null, 'signature_failed', $payload, $signature, false);
            return false;
        }
    }
    
    /**
     * Salva log webhook nel database
     */
    private function logWebhook($eventId, $eventType, $payload, $signature, $valid)
    {
        $sql = "INSERT INTO pagamenti_webhook_log 
                (provider, event_id, event_type, payload, firma, firma_valida, created_at) 
                VALUES (:provider, :event_id, :event_type, :payload, :firma, :firma_valida, NOW())";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'provider' => $this->provider,
            'event_id' => $eventId ?? 'invalid_' . time(),
            'event_type' => $eventType,
            'payload' => $payload,
            'firma' => $signature,
            'firma_valida' => $valid ? 1 : 0
        ]);
    }
}