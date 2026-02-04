# MENU DIGITALE - GUIDA INSTALLAZIONE E DEPLOY

## REQUISITI SISTEMA

### Server
- PHP >= 7.4
- MySQL/MariaDB >= 5.7
- Apache/Nginx con mod_rewrite
- 100MB spazio disco minimo

### Estensioni PHP Richieste
- PDO
- pdo_mysql
- GD (per resize immagini)
- JSON
- mbstring

## INSTALLAZIONE PASSO-PASSO

### 1. Upload File
Carica tutti i file sul server via FTP/SFTP nella directory del dominio (es: public_html)

### 2. Creazione Database
```sql
CREATE DATABASE menu_digitale CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 3. Importazione Schema
Importa il file `database/menu_digitale.sql` tramite phpMyAdmin o command line:
```bash
mysql -u username -p menu_digitale < database/menu_digitale.sql
```

### 4. Configurazione
Modifica `config/config.php` con le tue credenziali:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'menu_digitale');
define('DB_USER', 'tuo_username');
define('DB_PASS', 'tua_password');

define('BASE_URL', 'https://tuodominio.com');
```

### 5. Permessi Directory
Imposta permessi di scrittura sulle directory uploads:
```bash
chmod -R 777 uploads/
mkdir -p uploads/images uploads/locali uploads/piatti uploads/qrcodes
chmod -R 777 uploads/*
```

### 6. Libreria QR Code
Scarica phpqrcode da: https://sourceforge.net/projects/phpqrcode/
Estrai in `vendor/phpqrcode/`

Oppure via Composer:
```bash
composer require phpqrcode/phpqrcode
```

### 7. Verifica Installazione
Accedi a: `https://tuodominio.com/install.php`
Verifica che tutti i requisiti siano soddisfatti.

### 8. Primo Accesso
1. Vai su `https://tuodominio.com/register.php`
2. Crea il primo account
3. Accedi alla dashboard
4. **IMPORTANTE**: Elimina `install.php` per sicurezza

## CONFIGURAZIONE APACHE

### .htaccess
Il file `.htaccess` è già configurato. Verifica che `mod_rewrite` sia abilitato:
```bash
sudo a2enmod rewrite
sudo systemctl restart apache2
```

### Virtual Host (Opzionale)
```apache
<VirtualHost *:80>
    ServerName tuodominio.com
    DocumentRoot /var/www/html/menu-digitale
    
    <Directory /var/www/html/menu-digitale>
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/menu-digitale-error.log
    CustomLog ${APACHE_LOG_DIR}/menu-digitale-access.log combined
</VirtualHost>
```

## CONFIGURAZIONE NGINX

```nginx
server {
    listen 80;
    server_name tuodominio.com;
    root /var/www/html/menu-digitale;
    index index.php index.html;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.ht {
        deny all;
    }
}
```

## SICUREZZA

### 1. File da Proteggere
- `config/config.php` - Credenziali database
- `.htaccess` - Regole Apache
- `install.php` - ELIMINARE dopo installazione

### 2. Directory da Proteggere
```apache
<Directory "/path/to/config">
    Require all denied
</Directory>
```

### 3. SSL/HTTPS
Installa certificato SSL (Let's Encrypt):
```bash
sudo apt install certbot python3-certbot-apache
sudo certbot --apache -d tuodominio.com
```

### 4. Backup Automatico
```bash
#!/bin/bash
# backup.sh
DATE=$(date +%Y%m%d)
mysqldump -u username -p menu_digitale > backup_$DATE.sql
tar -czf backup_$DATE.tar.gz uploads/ backup_$DATE.sql
rm backup_$DATE.sql
```

## OTTIMIZZAZIONE PRESTAZIONI

### 1. PHP OpCache
In `php.ini`:
```ini
opcache.enable=1
opcache.memory_consumption=128
opcache.max_accelerated_files=10000
opcache.revalidate_freq=2
```

### 2. Compressione
Già configurato in `.htaccess` con mod_deflate

### 3. Cache Immagini
Le immagini sono automaticamente ridimensionate a max 1200px

### 4. Database
```sql
-- Ottimizza tabelle periodicamente
OPTIMIZE TABLE users, locali, menu, piatti, ordini;

-- Aggiungi indici se necessario
ALTER TABLE piatti ADD INDEX idx_disponibile (disponibile);
```

## MANUTENZIONE

### Backup Database
```bash
# Giornaliero
mysqldump -u username -p menu_digitale > backup_$(date +%Y%m%d).sql
```

### Pulizia File Upload
```bash
# Rimuovi file vecchi (>30 giorni)
find uploads/temp -type f -mtime +30 -delete
```

### Log Monitoraggio
```bash
tail -f /var/log/apache2/menu-digitale-error.log
```

## AGGIORNAMENTI

### Database Migration
Quando aggiungi nuove funzionalità:
1. Crea file SQL in `database/migrations/`
2. Applica manualmente o tramite script
3. Testa su ambiente staging prima

### Codice
1. Backup completo prima di aggiornare
2. Upload nuovi file
3. Verifica funzionalità
4. Monitora log errori

## TROUBLESHOOTING

### Errore "Database connection failed"
- Verifica credenziali in `config/config.php`
- Controlla che MySQL sia attivo
- Verifica permessi utente database

### Immagini non caricate
- Controlla permessi directory `uploads/`
- Verifica estensione GD abilitata
- Controlla limite upload in `php.ini`:
  ```ini
  upload_max_filesize = 10M
  post_max_size = 10M
  ```

### QR Code non generati
- Verifica presenza libreria phpqrcode
- Controlla permessi `uploads/qrcodes/`
- Verifica log errori PHP

### URL Rewrite non funziona
- Abilita mod_rewrite Apache
- Controlla sintassi `.htaccess`
- Per Nginx, usa configurazione apposita

## SUPPORTO

Per assistenza:
- Email: support@example.com
- Documentazione: https://docs.example.com
- Issue Tracker: https://github.com/example/menu-digitale

## LICENZA

Proprietaria - Tutti i diritti riservati
