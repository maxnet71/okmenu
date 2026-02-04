-- Database: menu_digitale

CREATE DATABASE IF NOT EXISTS menu_digitale CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE menu_digitale;

CREATE TABLE `users` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `nome` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) UNIQUE NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `tipo` ENUM('admin', 'ristoratore', 'staff') DEFAULT 'ristoratore',
  `attivo` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `locali` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NOT NULL,
  `nome` VARCHAR(200) NOT NULL,
  `tipo` ENUM('ristorante', 'pizzeria', 'bar', 'pub', 'hotel', 'gelateria', 'altro') DEFAULT 'ristorante',
  `indirizzo` VARCHAR(255),
  `citta` VARCHAR(100),
  `cap` VARCHAR(10),
  `telefono` VARCHAR(20),
  `whatsapp` VARCHAR(20),
  `email` VARCHAR(150),
  `logo` VARCHAR(255),
  `descrizione` TEXT,
  `slug` VARCHAR(255) UNIQUE NOT NULL,
  `attivo` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  INDEX `idx_user` (`user_id`),
  INDEX `idx_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `orari` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `locale_id` INT UNSIGNED NOT NULL,
  `giorno` TINYINT(1) NOT NULL COMMENT '0=Dom, 1=Lun, 2=Mar, 3=Mer, 4=Gio, 5=Ven, 6=Sab',
  `aperto` TINYINT(1) DEFAULT 1,
  `apertura_mattina` TIME,
  `chiusura_mattina` TIME,
  `apertura_sera` TIME,
  `chiusura_sera` TIME,
  FOREIGN KEY (`locale_id`) REFERENCES `locali`(`id`) ON DELETE CASCADE,
  INDEX `idx_locale` (`locale_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `menu` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `locale_id` INT UNSIGNED NOT NULL,
  `parent_id` INT UNSIGNED NULL COMMENT 'Per menu multilivello',
  `nome` VARCHAR(200) NOT NULL,
  `descrizione` TEXT,
  `tipo` ENUM('principale', 'sottomenu', 'carta_vini', 'carta_birre', 'carta_cocktail') DEFAULT 'principale',
  `ordinamento` INT DEFAULT 0,
  `pubblicato` TINYINT(1) DEFAULT 0,
  `visibile_da` DATETIME,
  `visibile_a` DATETIME,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`locale_id`) REFERENCES `locali`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`parent_id`) REFERENCES `menu`(`id`) ON DELETE SET NULL,
  INDEX `idx_locale` (`locale_id`),
  INDEX `idx_parent` (`parent_id`),
  INDEX `idx_pubblicato` (`pubblicato`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `categorie` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `menu_id` INT UNSIGNED NOT NULL,
  `nome` VARCHAR(150) NOT NULL,
  `descrizione` TEXT,
  `icona` VARCHAR(100),
  `ordinamento` INT DEFAULT 0,
  `attivo` TINYINT(1) DEFAULT 1,
  FOREIGN KEY (`menu_id`) REFERENCES `menu`(`id`) ON DELETE CASCADE,
  INDEX `idx_menu` (`menu_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `allergeni` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `codice` VARCHAR(10) UNIQUE NOT NULL,
  `nome_it` VARCHAR(100) NOT NULL,
  `nome_en` VARCHAR(100),
  `icona` VARCHAR(255),
  `ordinamento` INT DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `caratteristiche` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `nome` VARCHAR(100) NOT NULL,
  `icona` VARCHAR(255),
  `colore` VARCHAR(7) DEFAULT '#000000',
  `ordinamento` INT DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `piatti` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `categoria_id` INT UNSIGNED NOT NULL,
  `nome` VARCHAR(200) NOT NULL,
  `descrizione` TEXT,
  `ingredienti` TEXT,
  `prezzo` DECIMAL(10,2),
  `mostra_prezzo` TINYINT(1) DEFAULT 1,
  `immagine` VARCHAR(255),
  `disponibile` TINYINT(1) DEFAULT 1,
  `ordinamento` INT DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`categoria_id`) REFERENCES `categorie`(`id`) ON DELETE CASCADE,
  INDEX `idx_categoria` (`categoria_id`),
  INDEX `idx_disponibile` (`disponibile`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `piatti_allergeni` (
  `piatto_id` INT UNSIGNED NOT NULL,
  `allergene_id` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`piatto_id`, `allergene_id`),
  FOREIGN KEY (`piatto_id`) REFERENCES `piatti`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`allergene_id`) REFERENCES `allergeni`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `piatti_caratteristiche` (
  `piatto_id` INT UNSIGNED NOT NULL,
  `caratteristica_id` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`piatto_id`, `caratteristica_id`),
  FOREIGN KEY (`piatto_id`) REFERENCES `piatti`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`caratteristica_id`) REFERENCES `caratteristiche`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `varianti` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `piatto_id` INT UNSIGNED NOT NULL,
  `nome` VARCHAR(150) NOT NULL,
  `prezzo_aggiuntivo` DECIMAL(10,2) DEFAULT 0.00,
  `disponibile` TINYINT(1) DEFAULT 1,
  `ordinamento` INT DEFAULT 0,
  FOREIGN KEY (`piatto_id`) REFERENCES `piatti`(`id`) ON DELETE CASCADE,
  INDEX `idx_piatto` (`piatto_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `traduzioni` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `tabella` VARCHAR(50) NOT NULL,
  `campo` VARCHAR(50) NOT NULL,
  `record_id` INT UNSIGNED NOT NULL,
  `lingua` VARCHAR(5) NOT NULL,
  `testo` TEXT NOT NULL,
  UNIQUE KEY `unique_traduzione` (`tabella`, `campo`, `record_id`, `lingua`),
  INDEX `idx_record` (`tabella`, `record_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `personalizzazioni` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `locale_id` INT UNSIGNED NOT NULL,
  `tema` VARCHAR(50) DEFAULT 'default',
  `colore_primario` VARCHAR(7) DEFAULT '#333333',
  `colore_secondario` VARCHAR(7) DEFAULT '#ffffff',
  `font_titoli` VARCHAR(100) DEFAULT 'Arial',
  `font_testo` VARCHAR(100) DEFAULT 'Arial',
  `sfondo_tipo` ENUM('colore', 'immagine') DEFAULT 'colore',
  `sfondo_valore` VARCHAR(255) DEFAULT '#ffffff',
  `mostra_logo` TINYINT(1) DEFAULT 1,
  `mostra_contatti` TINYINT(1) DEFAULT 1,
  `mostra_orari` TINYINT(1) DEFAULT 1,
  FOREIGN KEY (`locale_id`) REFERENCES `locali`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `unique_locale` (`locale_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `ordini` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `locale_id` INT UNSIGNED NOT NULL,
  `numero_ordine` VARCHAR(50) UNIQUE NOT NULL,
  `tipo` ENUM('tavolo', 'asporto', 'delivery') NOT NULL,
  `tavolo` VARCHAR(20),
  `nome_cliente` VARCHAR(150),
  `telefono_cliente` VARCHAR(20),
  `email_cliente` VARCHAR(150),
  `indirizzo_consegna` TEXT,
  `note` TEXT,
  `subtotale` DECIMAL(10,2) NOT NULL,
  `costi_aggiuntivi` DECIMAL(10,2) DEFAULT 0.00,
  `totale` DECIMAL(10,2) NOT NULL,
  `stato` ENUM('ricevuto', 'in_preparazione', 'pronto', 'consegnato', 'annullato') DEFAULT 'ricevuto',
  `pagato` TINYINT(1) DEFAULT 0,
  `metodo_pagamento` VARCHAR(50),
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`locale_id`) REFERENCES `locali`(`id`) ON DELETE CASCADE,
  INDEX `idx_locale` (`locale_id`),
  INDEX `idx_stato` (`stato`),
  INDEX `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `ordini_dettagli` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `ordine_id` INT UNSIGNED NOT NULL,
  `piatto_id` INT UNSIGNED NOT NULL,
  `nome_piatto` VARCHAR(200) NOT NULL,
  `quantita` INT NOT NULL,
  `prezzo_unitario` DECIMAL(10,2) NOT NULL,
  `note` TEXT,
  FOREIGN KEY (`ordine_id`) REFERENCES `ordini`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`piatto_id`) REFERENCES `piatti`(`id`) ON DELETE RESTRICT,
  INDEX `idx_ordine` (`ordine_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `ordini_varianti` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `ordine_dettaglio_id` INT UNSIGNED NOT NULL,
  `variante_id` INT UNSIGNED NOT NULL,
  `nome_variante` VARCHAR(150) NOT NULL,
  `prezzo` DECIMAL(10,2) NOT NULL,
  FOREIGN KEY (`ordine_dettaglio_id`) REFERENCES `ordini_dettagli`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`variante_id`) REFERENCES `varianti`(`id`) ON DELETE RESTRICT,
  INDEX `idx_dettaglio` (`ordine_dettaglio_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `prenotazioni` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `locale_id` INT UNSIGNED NOT NULL,
  `nome` VARCHAR(150) NOT NULL,
  `telefono` VARCHAR(20) NOT NULL,
  `email` VARCHAR(150),
  `data_prenotazione` DATE NOT NULL,
  `ora_prenotazione` TIME NOT NULL,
  `numero_persone` INT NOT NULL,
  `note` TEXT,
  `stato` ENUM('confermata', 'in_attesa', 'annullata') DEFAULT 'in_attesa',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`locale_id`) REFERENCES `locali`(`id`) ON DELETE CASCADE,
  INDEX `idx_locale` (`locale_id`),
  INDEX `idx_data` (`data_prenotazione`, `ora_prenotazione`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `statistiche` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `locale_id` INT UNSIGNED NOT NULL,
  `data` DATE NOT NULL,
  `visualizzazioni_menu` INT DEFAULT 0,
  `scansioni_qr` INT DEFAULT 0,
  `ordini` INT DEFAULT 0,
  `fatturato` DECIMAL(10,2) DEFAULT 0.00,
  FOREIGN KEY (`locale_id`) REFERENCES `locali`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `unique_locale_data` (`locale_id`, `data`),
  INDEX `idx_data` (`data`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `qrcode` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `locale_id` INT UNSIGNED NOT NULL,
  `menu_id` INT UNSIGNED,
  `codice` VARCHAR(255) UNIQUE NOT NULL,
  `tipo` ENUM('menu', 'tavolo', 'asporto') DEFAULT 'menu',
  `tavolo` VARCHAR(20),
  `file_path` VARCHAR(255),
  `attivo` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`locale_id`) REFERENCES `locali`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`menu_id`) REFERENCES `menu`(`id`) ON DELETE SET NULL,
  INDEX `idx_locale` (`locale_id`),
  INDEX `idx_codice` (`codice`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `allergeni` (`codice`, `nome_it`, `nome_en`, `ordinamento`) VALUES
('A1', 'Cereali contenenti glutine', 'Cereals containing gluten', 1),
('A2', 'Crostacei', 'Crustaceans', 2),
('A3', 'Uova', 'Eggs', 3),
('A4', 'Pesce', 'Fish', 4),
('A5', 'Arachidi', 'Peanuts', 5),
('A6', 'Soia', 'Soybeans', 6),
('A7', 'Latte', 'Milk', 7),
('A8', 'Frutta a guscio', 'Nuts', 8),
('A9', 'Sedano', 'Celery', 9),
('A10', 'Senape', 'Mustard', 10),
('A11', 'Semi di sesamo', 'Sesame seeds', 11),
('A12', 'Anidride solforosa e solfiti', 'Sulphur dioxide and sulphites', 12),
('A13', 'Lupini', 'Lupin', 13),
('A14', 'Molluschi', 'Molluscs', 14);

INSERT INTO `caratteristiche` (`nome`, `colore`, `ordinamento`) VALUES
('Vegano', '#4CAF50', 1),
('Vegetariano', '#8BC34A', 2),
('Senza Glutine', '#FF9800', 3),
('Bio', '#795548', 4),
('Piccante', '#F44336', 5),
('Novità', '#2196F3', 6);
