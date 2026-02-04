# MENU DIGITALE - SISTEMA GESTIONE RISTORANTI

## STRUTTURA DATABASE CREATA

### Tabelle Principali
- `users` - Utenti sistema (admin, ristoratori, staff)
- `locali` - Ristoranti/Locali registrati
- `menu` - Menu (supporto multilivello)
- `categorie` - Categorie piatti
- `piatti` - Piatti/Prodotti
- `allergeni` - 14 allergeni obbligatori UE
- `caratteristiche` - Badge (Vegano, Bio, etc)
- `varianti` - Varianti piatti (ingredienti aggiuntivi)
- `ordini` + `ordini_dettagli` + `ordini_varianti` - Sistema ordini
- `prenotazioni` - Prenotazione tavoli
- `qrcode` - QR code generati
- `statistiche` - Analytics
- `traduzioni` - Sistema multi-lingua
- `personalizzazioni` - Temi personalizzati

## CLASSI PHP IMPLEMENTATE

### Core
- `Database.php` - Singleton PDO connection
- `Model.php` - Base Model con CRUD operations
- `Helpers.php` - Utilities (upload, auth, validation)
- `autoload.php` - PSR-4 autoloader

### Models
- `User.php` - Autenticazione e gestione utenti
- `Locale.php` - Gestione ristoranti/locali
- `Menu.php` - Gestione menu multilivello
- `Categoria.php` - Gestione categorie
- `Piatto.php` - Gestione piatti con allergeni/caratteristiche
- `Ordine.php` - Sistema ordini completo
- `Prenotazione.php` - Gestione prenotazioni
- `QRCode.php` - Generazione QR con libreria phpqrcode
- `Statistica.php` - Analytics e statistiche

## FUNZIONALITÀ IMPLEMENTATE

### Frontend Pubblico
- Landing page professionale
- Sistema prezzi (Free/Pro/Enterprise)
- Design responsive Bootstrap 5

### Backend (da completare)
- Dashboard amministrazione
- CRUD Menu/Categorie/Piatti
- Upload immagini
- Gestione ordini real-time
- Sistema prenotazioni
- Generazione QR Code
- Statistiche grafiche
- Multi-sede

### Funzionalità Avanzate
- Menu multilivello (menu principale → sottomenu)
- Filtri allergeni automatici
- Caratteristiche piatti (badge)
- Traduzioni multi-lingua
- Ordini (tavolo/asporto/delivery)
- Personalizzazione tema
- Analytics dettagliate

## FILE DA COMPLETARE

### Pagine Autenticazione
- `login.php`
- `register.php`
- `logout.php`
- `forgot-password.php`

### Dashboard Admin
- `dashboard/index.php`
- `dashboard/locali/`
- `dashboard/menu/`
- `dashboard/piatti/`
- `dashboard/ordini/`
- `dashboard/prenotazioni/`
- `dashboard/qrcode/`
- `dashboard/statistiche/`
- `dashboard/impostazioni/`

### API Endpoints
- `api/menu/` - CRUD menu
- `api/piatti/` - CRUD piatti
- `api/ordini/` - Gestione ordini
- `api/prenotazioni/` - Gestione prenotazioni
- `api/qrcode/` - Genera/scarica QR
- `api/upload/` - Upload immagini
- `api/statistiche/` - Dati analytics

### Visualizzazione Pubblica
- `view/{codice-qr}` - Visualizzazione menu cliente
- `menu/{slug}` - Menu pubblico locale

## TECNOLOGIE UTILIZZATE
- PHP 7.4+ (PDO)
- MySQL/MariaDB
- Bootstrap 5.3
- jQuery 3.7
- AJAX
- QR Code generation (phpqrcode)

## INSTALLAZIONE

1. Importa `database/menu_digitale.sql`
2. Configura `config/config.php`
3. Installa phpqrcode: `composer require phpqrcode`
4. Crea cartelle uploads: `uploads/images/`, `uploads/qrcodes/`
5. Imposta permessi 777 su uploads

## PROSSIMI STEP

1. Completare pagine autenticazione
2. Creare dashboard amministratore
3. Implementare CRUD completi
4. Creare API REST endpoints
5. Implementare visualizzazione menu cliente
6. Integrazione pagamenti online
7. Sistema notifiche real-time
8. Testing completo
9. Documentazione API

## NOTE SVILUPPO
- Seguire PSR-12
- Nessun dato hardcoded
- Documentazione docblock obbligatoria
- Nessuna duplicazione codice
- File sempre chiusi correttamente
