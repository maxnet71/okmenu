<?php

/**
 * EmailSender - Classe per invio email tramite SMTP nativo PHP
 * Nessuna libreria esterna richiesta
 */
class EmailSender
{
    private $smtpHost;
    private $smtpPort;
    private $smtpUser;
    private $smtpPass;
    private $smtpSecure;
    private $fromEmail;
    private $fromName;
    private $socket;
    
    public function __construct()
    {
        // Carica configurazione da config.php
        $this->smtpHost = defined('SMTP_HOST') ? SMTP_HOST : 'smtps.aruba.it';
        $this->smtpPort = defined('SMTP_PORT') ? SMTP_PORT : 587;
        $this->smtpUser = defined('SMTP_USER') ? SMTP_USER : 'info@okmenu.cloud';
        $this->smtpPass = defined('SMTP_PASS') ? SMTP_PASS : '$wcYcn424b%1';
        $this->smtpSecure = defined('SMTP_SECURE') ? SMTP_SECURE : 'ssl';
        $this->fromEmail = defined('MAIL_FROM') ? MAIL_FROM : 'info@okmenu.cloud';
        $this->fromName = defined('MAIL_FROM_NAME') ? MAIL_FROM_NAME : 'OkMenu';
        
        if (empty($this->smtpUser) || empty($this->smtpPass)) {
            throw new Exception('SMTP non configurato. Aggiungi SMTP_USER e SMTP_PASS in config.php');
        }
    }
    
    /**
     * Invia email
     */
    public function send($to, $subject, $bodyHtml, $bodyText = null)
    {
        try {
            $this->connect();
            $this->authenticate();
            $this->sendMessage($to, $subject, $bodyHtml, $bodyText);
            $this->disconnect();
            return true;
        } catch (Exception $e) {
            $this->disconnect();
            error_log("Errore invio email: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Connessione al server SMTP
     */
    private function connect()
    {
        $errno = 0;
        $errstr = '';
        
        // Connessione iniziale
        if ($this->smtpSecure === 'ssl') {
            $host = 'ssl://' . $this->smtpHost;
            $port = 465;
        } else {
            $host = $this->smtpHost;
            $port = $this->smtpPort;
        }
        
        $this->socket = @fsockopen($host, $port, $errno, $errstr, 30);
        
        if (!$this->socket) {
            throw new Exception("Impossibile connettersi a $host:$port - $errstr ($errno)");
        }
        
        stream_set_timeout($this->socket, 30);
        
        // Leggi risposta server
        $response = $this->readResponse();
        if (substr($response, 0, 3) !== '220') {
            throw new Exception("Errore connessione SMTP: $response");
        }
        
        // EHLO
        $this->sendCommand("EHLO " . $_SERVER['SERVER_NAME'] ?? 'localhost');
        
        // STARTTLS se richiesto
        if ($this->smtpSecure === 'tls') {
            $this->sendCommand("STARTTLS");
            
            if (!stream_socket_enable_crypto($this->socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                throw new Exception("Impossibile avviare TLS");
            }
            
            // Re-EHLO dopo STARTTLS
            $this->sendCommand("EHLO " . $_SERVER['SERVER_NAME'] ?? 'localhost');
        }
    }
    
    /**
     * Autenticazione SMTP
     */
    private function authenticate()
    {
        $this->sendCommand("AUTH LOGIN");
        $this->sendCommand(base64_encode($this->smtpUser));
        $this->sendCommand(base64_encode($this->smtpPass));
    }
    
    /**
     * Invio messaggio
     */
    private function sendMessage($to, $subject, $bodyHtml, $bodyText = null)
    {
        // MAIL FROM
        $this->sendCommand("MAIL FROM:<{$this->fromEmail}>");
        
        // RCPT TO
        $this->sendCommand("RCPT TO:<{$to}>");
        
        // DATA
        $this->sendCommand("DATA");
        
        // Headers
        $boundary = md5(time());
        $headers = [];
        $headers[] = "From: {$this->fromName} <{$this->fromEmail}>";
        $headers[] = "To: {$to}";
        $headers[] = "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=";
        $headers[] = "MIME-Version: 1.0";
        $headers[] = "Content-Type: multipart/alternative; boundary=\"{$boundary}\"";
        $headers[] = "Date: " . date('r');
        $headers[] = "Message-ID: <" . md5(uniqid(time())) . "@{$this->smtpHost}>";
        
        // Body
        $message = implode("\r\n", $headers) . "\r\n\r\n";
        
        // Parte testo
        if ($bodyText) {
            $message .= "--{$boundary}\r\n";
            $message .= "Content-Type: text/plain; charset=UTF-8\r\n";
            $message .= "Content-Transfer-Encoding: quoted-printable\r\n\r\n";
            $message .= quoted_printable_encode($bodyText) . "\r\n\r\n";
        }
        
        // Parte HTML
        $message .= "--{$boundary}\r\n";
        $message .= "Content-Type: text/html; charset=UTF-8\r\n";
        $message .= "Content-Transfer-Encoding: quoted-printable\r\n\r\n";
        $message .= quoted_printable_encode($bodyHtml) . "\r\n\r\n";
        
        $message .= "--{$boundary}--\r\n";
        
        // Invia messaggio
        fputs($this->socket, $message . "\r\n.\r\n");
        
        $response = $this->readResponse();
        if (substr($response, 0, 3) !== '250') {
            throw new Exception("Errore invio messaggio: $response");
        }
    }
    
    /**
     * Disconnessione
     */
    private function disconnect()
    {
        if ($this->socket) {
            @fputs($this->socket, "QUIT\r\n");
            @fclose($this->socket);
            $this->socket = null;
        }
    }
    
    /**
     * Invia comando SMTP
     */
    private function sendCommand($command)
    {
        fputs($this->socket, $command . "\r\n");
        $response = $this->readResponse();
        
        // Codici di successo: 2xx e 3xx
        $code = substr($response, 0, 1);
        if ($code !== '2' && $code !== '3') {
            throw new Exception("Errore SMTP: $response (comando: $command)");
        }
        
        return $response;
    }
    
    /**
     * Leggi risposta dal server
     */
    private function readResponse()
    {
        $response = '';
        
        while ($line = fgets($this->socket, 515)) {
            $response .= $line;
            
            // Ultima riga ha formato "XXX messaggio"
            if (preg_match('/^\d{3} /', $line)) {
                break;
            }
        }
        
        return trim($response);
    }
    
    /**
     * Helper per validare email
     */
    public static function validateEmail($email)
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
}