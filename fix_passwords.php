<?php
/**
 * Script per aggiornare le password degli utenti migrati
 * 
 * Uso da terminale: php fix_passwords.php
 * Uso da browser: http://tuodominio.it/fix_passwords.php
 * 
 * IMPORTANTE: Eliminare questo file dopo l'uso per motivi di sicurezza
 */

require_once 'config/config.php';
require_once 'classes/Database.php';
require_once 'classes/Model.php';
require_once 'classes/models/User.php';

$password = 'okmenu2025';
$hash = password_hash($password, PASSWORD_DEFAULT);

try {
    $db = Database::getInstance()->getConnection();
    
    $sql = "UPDATE users SET password = :password WHERE id IN (1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16)";
    $stmt = $db->prepare($sql);
    $stmt->execute(['password' => $hash]);
    
    $affected = $stmt->rowCount();
    
    if (php_sapi_name() === 'cli') {
        echo "✓ Password aggiornate con successo\n";
        echo "✓ Utenti modificati: $affected\n";
        echo "✓ Password: $password\n";
        echo "\nPuoi ora effettuare il login con qualsiasi email e password: $password\n";
        echo "\n⚠ IMPORTANTE: Elimina questo file per sicurezza\n";
    } else {
        echo "<!DOCTYPE html>";
        echo "<html lang='it'>";
        echo "<head>";
        echo "<meta charset='UTF-8'>";
        echo "<meta name='viewport' content='width=device-width, initial-scale=1.0'>";
        echo "<title>Fix Password - Menu Digitale</title>";
        echo "<link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>";
        echo "<link rel='stylesheet' href='https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css'>";
        echo "</head>";
        echo "<body class='bg-light'>";
        echo "<div class='container'>";
        echo "<div class='row justify-content-center align-items-center min-vh-100'>";
        echo "<div class='col-md-6'>";
        echo "<div class='card shadow-lg border-0'>";
        echo "<div class='card-body p-5'>";
        echo "<div class='text-center mb-4'>";
        echo "<i class='bi bi-check-circle-fill display-1 text-success'></i>";
        echo "<h2 class='mt-3'>Password Aggiornate</h2>";
        echo "</div>";
        echo "<hr>";
        echo "<div class='mb-3'>";
        echo "<p class='mb-2'><strong>Utenti modificati:</strong> <span class='badge bg-primary'>$affected</span></p>";
        echo "<p class='mb-2'><strong>Nuova password:</strong> <code class='fs-5'>$password</code></p>";
        echo "</div>";
        echo "<hr>";
        echo "<div class='alert alert-warning'>";
        echo "<i class='bi bi-exclamation-triangle-fill me-2'></i>";
        echo "<strong>IMPORTANTE:</strong> Elimina questo file (fix_passwords.php) per motivi di sicurezza";
        echo "</div>";
        echo "<div class='text-center'>";
        echo "<a href='login.php' class='btn btn-primary btn-lg'>";
        echo "<i class='bi bi-box-arrow-in-right me-2'></i>Vai al Login";
        echo "</a>";
        echo "</div>";
        echo "</div>";
        echo "</div>";
        echo "</div>";
        echo "</div>";
        echo "</div>";
        echo "</body>";
        echo "</html>";
    }
    
} catch (PDOException $e) {
    if (php_sapi_name() === 'cli') {
        echo "✗ Errore: " . $e->getMessage() . "\n";
    } else {
        echo "<!DOCTYPE html>";
        echo "<html lang='it'>";
        echo "<head>";
        echo "<meta charset='UTF-8'>";
        echo "<meta name='viewport' content='width=device-width, initial-scale=1.0'>";
        echo "<title>Errore - Menu Digitale</title>";
        echo "<link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>";
        echo "<link rel='stylesheet' href='https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css'>";
        echo "</head>";
        echo "<body class='bg-light'>";
        echo "<div class='container mt-5'>";
        echo "<div class='alert alert-danger'>";
        echo "<h4><i class='bi bi-x-circle me-2'></i>Errore Database</h4>";
        echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
        echo "</div>";
        echo "<a href='login.php' class='btn btn-secondary'>Torna al Login</a>";
        echo "</div>";
        echo "</body>";
        echo "</html>";
    }
    exit(1);
}