-- ============================================================================
-- PASSIONE CALABRIA - DATABASE MYSQL
-- File da importare in phpMyAdmin
-- ============================================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- Database: dbs14504718
CREATE DATABASE IF NOT EXISTS `dbs14504718` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `dbs14504718`;

-- ============================================================================
-- STRUTTURA TABELLE
-- ============================================================================

-- Tabella impostazioni sistema
CREATE TABLE IF NOT EXISTS `settings` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `key` varchar(255) NOT NULL,
    `value` text DEFAULT NULL,
    `type` varchar(50) DEFAULT 'text',
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `key` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabella categorie
CREATE TABLE IF NOT EXISTS `categories` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(255) NOT NULL,
    `description` text DEFAULT NULL,
    `icon` varchar(10) DEFAULT NULL,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabella province
CREATE TABLE IF NOT EXISTS `provinces` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(255) NOT NULL,
    `description` text DEFAULT NULL,
    `image_path` varchar(500) DEFAULT NULL,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabella città
CREATE TABLE IF NOT EXISTS `cities` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(255) NOT NULL,
    `province_id` int(11) NOT NULL,
    `latitude` decimal(10,8) DEFAULT NULL,
    `longitude` decimal(11,8) DEFAULT NULL,
    `description` text DEFAULT NULL,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `province_id` (`province_id`),
    CONSTRAINT `cities_ibfk_1` FOREIGN KEY (`province_id`) REFERENCES `provinces` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabella articoli
CREATE TABLE IF NOT EXISTS `articles` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `title` varchar(500) NOT NULL,
    `slug` varchar(500) NOT NULL,
    `content` longtext NOT NULL,
    `excerpt` text DEFAULT NULL,
    `featured_image` varchar(500) DEFAULT NULL,
    `gallery_images` json DEFAULT NULL,
    `category_id` int(11) NOT NULL,
    `province_id` int(11) DEFAULT NULL,
    `city_id` int(11) DEFAULT NULL,
    `author` varchar(255) DEFAULT NULL,
    `status` enum('draft','published','archived') DEFAULT 'published',
    `featured` tinyint(1) DEFAULT 0,
    `views` int(11) DEFAULT 0,
    `latitude` decimal(10,8) DEFAULT NULL,
    `longitude` decimal(11,8) DEFAULT NULL,
    `allow_user_uploads` tinyint(1) DEFAULT 1,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `slug` (`slug`),
    KEY `category_id` (`category_id`),
    KEY `province_id` (`province_id`),
    KEY `city_id` (`city_id`),
    KEY `status` (`status`),
    KEY `featured` (`featured`),
    CONSTRAINT `articles_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE,
    CONSTRAINT `articles_ibfk_2` FOREIGN KEY (`province_id`) REFERENCES `provinces` (`id`) ON DELETE SET NULL,
    CONSTRAINT `articles_ibfk_3` FOREIGN KEY (`city_id`) REFERENCES `cities` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabella utenti
CREATE TABLE IF NOT EXISTS `users` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `email` varchar(255) NOT NULL,
    `password` varchar(255) NOT NULL,
    `first_name` varchar(100) DEFAULT NULL,
    `last_name` varchar(100) DEFAULT NULL,
    `name` varchar(255) NOT NULL,
    `role` enum('user','admin','editor') DEFAULT 'user',
    `status` enum('active','inactive','banned') DEFAULT 'active',
    `avatar` varchar(500) DEFAULT NULL,
    `last_login` timestamp NULL DEFAULT NULL,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabella business/aziende
CREATE TABLE IF NOT EXISTS `businesses` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(255) NOT NULL,
    `email` varchar(255) NOT NULL,
    `phone` varchar(50) DEFAULT NULL,
    `website` varchar(500) DEFAULT NULL,
    `description` text DEFAULT NULL,
    `category_id` int(11) DEFAULT NULL,
    `province_id` int(11) DEFAULT NULL,
    `city_id` int(11) DEFAULT NULL,
    `address` text DEFAULT NULL,
    `latitude` decimal(10,8) DEFAULT NULL,
    `longitude` decimal(11,8) DEFAULT NULL,
    `status` enum('pending','approved','rejected','suspended') DEFAULT 'pending',
    `subscription_type` enum('free','basic','premium') DEFAULT 'free',
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `category_id` (`category_id`),
    KEY `province_id` (`province_id`),
    KEY `city_id` (`city_id`),
    KEY `status` (`status`),
    CONSTRAINT `businesses_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL,
    CONSTRAINT `businesses_ibfk_2` FOREIGN KEY (`province_id`) REFERENCES `provinces` (`id`) ON DELETE SET NULL,
    CONSTRAINT `businesses_ibfk_3` FOREIGN KEY (`city_id`) REFERENCES `cities` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabella eventi
CREATE TABLE IF NOT EXISTS `events` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `title` varchar(500) NOT NULL,
    `description` text DEFAULT NULL,
    `start_date` datetime NOT NULL,
    `end_date` datetime DEFAULT NULL,
    `location` varchar(500) DEFAULT NULL,
    `category_id` int(11) DEFAULT NULL,
    `province_id` int(11) DEFAULT NULL,
    `city_id` int(11) DEFAULT NULL,
    `organizer` varchar(255) DEFAULT NULL,
    `contact_email` varchar(255) DEFAULT NULL,
    `contact_phone` varchar(50) DEFAULT NULL,
    `website` varchar(500) DEFAULT NULL,
    `featured_image` varchar(500) DEFAULT NULL,
    `price` decimal(10,2) DEFAULT 0.00,
    `latitude` decimal(10,8) DEFAULT NULL,
    `longitude` decimal(11,8) DEFAULT NULL,
    `status` enum('pending','active','cancelled','completed') DEFAULT 'pending',
    `source` enum('admin','user_submission') DEFAULT 'admin',
    `business_id` int(11) DEFAULT NULL,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `category_id` (`category_id`),
    KEY `province_id` (`province_id`),
    KEY `city_id` (`city_id`),
    KEY `business_id` (`business_id`),
    KEY `start_date` (`start_date`),
    KEY `status` (`status`),
    CONSTRAINT `events_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL,
    CONSTRAINT `events_ibfk_2` FOREIGN KEY (`province_id`) REFERENCES `provinces` (`id`) ON DELETE SET NULL,
    CONSTRAINT `events_ibfk_3` FOREIGN KEY (`city_id`) REFERENCES `cities` (`id`) ON DELETE SET NULL,
    CONSTRAINT `events_ibfk_4` FOREIGN KEY (`business_id`) REFERENCES `businesses` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabella commenti
CREATE TABLE IF NOT EXISTS `comments` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `article_id` int(11) NOT NULL,
    `user_id` int(11) DEFAULT NULL,
    `author_name` varchar(255) NOT NULL,
    `author_email` varchar(255) NOT NULL,
    `content` text NOT NULL,
    `rating` int(1) DEFAULT NULL CHECK (`rating` >= 1 AND `rating` <= 5),
    `status` enum('pending','approved','rejected','spam') DEFAULT 'pending',
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `article_id` (`article_id`),
    KEY `user_id` (`user_id`),
    KEY `status` (`status`),
    CONSTRAINT `comments_ibfk_1` FOREIGN KEY (`article_id`) REFERENCES `articles` (`id`) ON DELETE CASCADE,
    CONSTRAINT `comments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabella business packages
CREATE TABLE IF NOT EXISTS `business_packages` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(255) NOT NULL,
    `description` text DEFAULT NULL,
    `price` decimal(10,2) NOT NULL,
    `duration_months` int(11) DEFAULT 12,
    `features` json DEFAULT NULL,
    `is_active` tinyint(1) DEFAULT 1,
    `sort_order` int(11) DEFAULT 0,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabella sottoscrizioni
CREATE TABLE IF NOT EXISTS `subscriptions` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `business_id` int(11) NOT NULL,
    `package_id` int(11) NOT NULL,
    `stripe_subscription_id` varchar(255) DEFAULT NULL,
    `status` enum('pending','active','cancelled','expired') DEFAULT 'pending',
    `start_date` datetime DEFAULT NULL,
    `end_date` datetime DEFAULT NULL,
    `amount` decimal(10,2) DEFAULT NULL,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `business_id` (`business_id`),
    KEY `package_id` (`package_id`),
    CONSTRAINT `subscriptions_ibfk_1` FOREIGN KEY (`business_id`) REFERENCES `businesses` (`id`) ON DELETE CASCADE,
    CONSTRAINT `subscriptions_ibfk_2` FOREIGN KEY (`package_id`) REFERENCES `business_packages` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabella home sections
CREATE TABLE IF NOT EXISTS `home_sections` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `section_name` varchar(255) NOT NULL,
    `title` varchar(500) DEFAULT NULL,
    `subtitle` varchar(500) DEFAULT NULL,
    `description` text DEFAULT NULL,
    `image_path` varchar(500) DEFAULT NULL,
    `is_visible` tinyint(1) DEFAULT 1,
    `sort_order` int(11) DEFAULT 0,
    `custom_data` json DEFAULT NULL,
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `section_name` (`section_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabella caricamenti utenti
CREATE TABLE IF NOT EXISTS `user_uploads` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `article_id` int(11) DEFAULT NULL,
    `user_name` varchar(255) NOT NULL,
    `user_email` varchar(255) NOT NULL,
    `image_path` varchar(500) NOT NULL,
    `original_filename` varchar(500) DEFAULT NULL,
    `description` text DEFAULT NULL,
    `status` enum('pending','approved','rejected') DEFAULT 'pending',
    `admin_notes` text DEFAULT NULL,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `article_id` (`article_id`),
    CONSTRAINT `user_uploads_ibfk_1` FOREIGN KEY (`article_id`) REFERENCES `articles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabella suggerimenti luoghi
CREATE TABLE IF NOT EXISTS `place_suggestions` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(255) NOT NULL,
    `description` text DEFAULT NULL,
    `category_id` int(11) DEFAULT NULL,
    `province_id` int(11) DEFAULT NULL,
    `city_id` int(11) DEFAULT NULL,
    `address` text DEFAULT NULL,
    `latitude` decimal(10,8) DEFAULT NULL,
    `longitude` decimal(11,8) DEFAULT NULL,
    `suggested_by_name` varchar(255) NOT NULL,
    `suggested_by_email` varchar(255) NOT NULL,
    `images` json DEFAULT NULL,
    `status` enum('pending','approved','rejected') DEFAULT 'pending',
    `admin_notes` text DEFAULT NULL,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `category_id` (`category_id`),
    KEY `province_id` (`province_id`),
    KEY `city_id` (`city_id`),
    CONSTRAINT `place_suggestions_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL,
    CONSTRAINT `place_suggestions_ibfk_2` FOREIGN KEY (`province_id`) REFERENCES `provinces` (`id`) ON DELETE SET NULL,
    CONSTRAINT `place_suggestions_ibfk_3` FOREIGN KEY (`city_id`) REFERENCES `cities` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabella pagine statiche
CREATE TABLE IF NOT EXISTS `static_pages` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `slug` varchar(255) NOT NULL,
    `title` varchar(500) NOT NULL,
    `content` longtext NOT NULL,
    `meta_title` varchar(500) DEFAULT NULL,
    `meta_description` text DEFAULT NULL,
    `is_published` tinyint(1) DEFAULT 1,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabella newsletter subscribers
CREATE TABLE IF NOT EXISTS `newsletter_subscribers` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `email` varchar(255) NOT NULL,
    `name` varchar(255) DEFAULT NULL,
    `interests` json DEFAULT NULL,
    `status` enum('active','confirmed','unsubscribed') DEFAULT 'active',
    `confirmation_token` varchar(255) DEFAULT NULL,
    `confirmed_at` timestamp NULL DEFAULT NULL,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- DATI DI DEFAULT
-- ============================================================================

-- Inserimento impostazioni di default
INSERT IGNORE INTO `settings` (`key`, `value`, `type`) VALUES
('google_recaptcha_v2_site_key', '', 'text'),
('google_recaptcha_v2_secret_key', '', 'password'),
('google_recaptcha_v3_site_key', '', 'text'),
('google_recaptcha_v3_secret_key', '', 'password'),
('stripe_publishable_key', '', 'text'),
('stripe_secret_key', '', 'password'),
('google_analytics_id', '', 'text'),
('app_store_link', '', 'url'),
('app_store_image', '', 'text'),
('play_store_link', '', 'url'),
('play_store_image', '', 'text'),
('vai_app_link', '', 'url'),
('suggerisci_evento_link', '', 'url'),
('hero_title', 'Esplora la Calabria', 'text'),
('hero_subtitle', 'Mare cristallino e storia millenaria', 'text'),
('hero_description', 'Immergiti nella bellezza della Calabria, con le sue spiagge da sogno, il centro storico affascinante e i panorami mozzafiato dalla rupe.', 'textarea'),
('hero_image', '/placeholder-hero.jpg', 'text');

-- Inserimento sezioni home di default
INSERT IGNORE INTO `home_sections` (`section_name`, `title`, `subtitle`, `description`, `image_path`, `is_visible`, `sort_order`) VALUES
('hero', 'Esplora la Calabria', 'Mare cristallino e storia millenaria', 'Immergiti nella bellezza della Calabria', '/placeholder-hero.jpg', 1, 1),
('categories', 'Esplora per Categoria', '', 'Scopri la Calabria attraverso le sue diverse sfaccettature', '', 1, 2),
('provinces', 'Esplora le Province', '', 'Ogni provincia calabrese custodisce tesori unici', '', 1, 3),
('map', 'Mappa Interattiva', '', 'Naviga attraverso la Calabria con la nostra mappa interattiva', '', 1, 4),
('cta', 'Vuoi far Conoscere la Tua Calabria?', '', 'Unisciti alla nostra community!', '', 1, 5),
('newsletter', 'Resta Connesso con la Calabria', '', 'Iscriviti alla nostra newsletter', '', 1, 6);

-- Inserimento categorie di default
INSERT IGNORE INTO `categories` (`name`, `description`, `icon`) VALUES
('Natura e Paesaggi', 'Scopri la bellezza naturale della Calabria', '🌿'),
('Storia e Cultura', 'Immergiti nella ricca storia calabrese', '🏛️'),
('Gastronomia', 'Assapora i sapori autentici della tradizione', '🍝'),
('Mare e Coste', 'Le più belle spiagge e località balneari', '🏖️'),
('Montagne e Escursioni', 'Avventure tra i monti calabresi', '⛰️'),
('Borghi e Tradizioni', 'Alla scoperta dei borghi più belli', '🏘️'),
('Arte e Musei', 'Tesori artistici e culturali', '🎨'),
('Feste e Eventi', 'Celebrazioni e manifestazioni locali', '🎭'),
('Artigianato', 'Mestieri e prodotti della tradizione', '🛠️'),
('Terme e Benessere', 'Relax e cure naturali', '♨️'),
('Parchi e Riserve', 'Aree protette e natura incontaminata', '🌲'),
('Architettura Religiosa', 'Chiese, monasteri e luoghi sacri', '⛪'),
('Archeologia', 'Siti archeologici e antiche testimonianze', '🏺'),
('Sport e Avventura', 'Attività sportive e outdoor', '🚴'),
('Enogastronomia', 'Vini e prodotti tipici locali', '🍷'),
('Fotografia', 'I luoghi più fotogenici della regione', '📸'),
('Musica e Spettacoli', 'Eventi culturali e artistici', '🎵'),
('Famiglia e Bambini', 'Attività e luoghi per famiglie', '👨‍👩‍👧‍👦');

-- Inserimento province di default
INSERT IGNORE INTO `provinces` (`name`, `description`) VALUES
('Catanzaro', 'Capoluogo di regione, cuore della Calabria tra due mari'),
('Cosenza', 'La provincia più estesa, ricca di storia e natura'),
('Crotone', 'Terra di Pitagora, tra mare cristallino e archeologia'),
('Reggio Calabria', 'La punta dello stivale, affacciata sullo Stretto di Messina'),
('Vibo Valentia', 'Piccola provincia ricca di tradizioni marinare e gastronomiche');

-- Inserimento città principali di default
INSERT IGNORE INTO `cities` (`name`, `province_id`, `latitude`, `longitude`, `description`) VALUES
('Catanzaro', 1, 38.90980000, 16.59690000, 'Capoluogo di regione'),
('Lamezia Terme', 1, 38.96480000, 16.31290000, 'Importante centro della piana'),
('Soverato', 1, 38.69180000, 16.55130000, 'Perla dello Ionio'),
('Cosenza', 2, 39.29480000, 16.25420000, 'Città dei Bruzi'),
('Rossano', 2, 39.57610000, 16.63140000, 'Città della liquirizia'),
('Paola', 2, 39.36560000, 16.03780000, 'Città di San Francesco'),
('Scalea', 2, 39.81470000, 15.79390000, 'Riviera dei Cedri'),
('Crotone', 3, 39.08470000, 17.12520000, 'Antica Kroton'),
('Cirò Marina', 3, 39.37260000, 17.12830000, 'Terra del vino Cirò'),
('Reggio Calabria', 4, 38.10980000, 15.65160000, 'Città dei Bronzi'),
('Tropea', 5, 38.67730000, 15.89760000, 'Perla del Tirreno'),
('Vibo Valentia', 5, 38.67590000, 16.10180000, 'Antica Hipponion'),
('Pizzo', 5, 38.73470000, 16.15690000, 'Città del tartufo');

-- Inserimento pacchetti business di default
INSERT IGNORE INTO `business_packages` (`name`, `description`, `price`, `duration_months`, `features`, `is_active`, `sort_order`) VALUES
('Gratuito', 'Inserimento base della tua attività', 0.00, 12, '["Scheda attività base","Contatti e orari","Visibilità nella ricerca"]', 1, 1),
('Business', 'Pacchetto completo per la tua attività', 29.99, 12, '["Tutto del piano Gratuito","Foto illimitate","Descrizione estesa","Badge verificato","Statistiche visualizzazioni"]', 1, 2),
('Premium', 'Massima visibilità e funzionalità avanzate', 59.99, 12, '["Tutto del piano Business","Posizione privilegiata","Articoli sponsorizzati","Analytics avanzate","Supporto prioritario","Eventi promozionali"]', 1, 3);

-- Inserimento pagine statiche di default
INSERT IGNORE INTO `static_pages` (`slug`, `title`, `content`, `meta_title`, `meta_description`, `is_published`) VALUES
('chi-siamo', 'Chi Siamo', '<h1>Chi Siamo</h1><p>Benvenuti in Passione Calabria, il portale dedicato alla scoperta di una delle regioni più affascinanti d\'Italia.</p><p>La nostra missione è far conoscere la vera essenza della Calabria: dalle spiagge cristalline della Costa degli Dei ai borghi medievali dell\'entroterra, dalle tradizioni gastronomiche millenarie alle meraviglie naturali dei parchi nazionali.</p>', 'Chi Siamo - Passione Calabria', 'Scopri chi siamo e la nostra missione per promuovere la bellezza e le tradizioni della Calabria.', 1),
('privacy-policy', 'Privacy Policy', '<h1>Privacy Policy</h1><p>Questa privacy policy descrive come raccogliamo, utilizziamo e proteggiamo le tue informazioni personali.</p><h2>Raccolta delle Informazioni</h2><p>Raccogliamo informazioni quando ti registri al nostro sito, ti iscrivi alla newsletter o compili un modulo.</p>', 'Privacy Policy - Passione Calabria', 'La nostra politica sulla privacy e protezione dei dati personali.', 1),
('termini-servizio', 'Termini di Servizio', '<h1>Termini di Servizio</h1><p>Questi termini e condizioni governano il tuo uso del nostro sito web e servizi.</p><h2>Accettazione dei Termini</h2><p>Utilizzando il nostro sito, accetti di essere vincolato da questi termini di servizio.</p>', 'Termini di Servizio - Passione Calabria', 'I termini e condizioni per l\'utilizzo del nostro sito web e servizi.', 1),
('contatti', 'Contatti', '<h1>Contatti</h1><p>Siamo sempre felici di sentire da voi! Ecco come potete raggiungerci:</p><h2>Informazioni di Contatto</h2><p><strong>Email:</strong> info@passionecalabria.it</p><p><strong>Telefono:</strong> +39 XXX XXX XXXX</p><p><strong>Indirizzo:</strong> Via Roma, 123 - 88100 Catanzaro (CZ)</p>', 'Contatti - Passione Calabria', 'Come contattarci per informazioni, collaborazioni o segnalazioni.', 1),
('cookie-policy', 'Cookie Policy', '<h1>Cookie Policy</h1><p>Questo sito utilizza cookies per migliorare la tua esperienza di navigazione.</p><h2>Cosa sono i Cookies</h2><p>I cookies sono piccoli file di testo che vengono memorizzati sul tuo dispositivo quando visiti un sito web.</p>', 'Cookie Policy - Passione Calabria', 'La nostra politica sui cookies e come li utilizziamo.', 1);

-- Inserimento articoli di esempio
INSERT IGNORE INTO `articles` (`title`, `slug`, `content`, `excerpt`, `category_id`, `province_id`, `city_id`, `author`, `featured`, `latitude`, `longitude`) VALUES
('La Sila: Il Cuore Verde della Calabria', 'la-sila-il-cuore-verde-della-calabria', 'L\'Altopiano della Sila rappresenta uno dei tesori naturalistici più preziosi della Calabria. Con i suoi 150.000 ettari di territorio, questo polmone verde offre paesaggi mozzafiato, laghi cristallini e una biodiversità unica. I tre parchi che compongono la Sila - Sila Grande, Sila Piccola e Sila Greca - custodiscono foreste secolari di pini larici e faggi, praterie alpine e una fauna ricca che include lupi, caprioli e l\'aquila reale.', 'Scopri l\'Altopiano della Sila, polmone verde della Calabria con laghi cristallini e foreste secolari.', 1, 2, NULL, 'Marco Rossi', 1, 39.30000000, 16.50000000),

('I Bronzi di Riace: Capolavori della Magna Grecia', 'i-bronzi-di-riace-capolavori-della-magna-grecia', 'I Bronzi di Riace sono due statue di bronzo di epoca greca classica, rinvenute nel 1972 nei fondali marini antistanti Riace Marina. Questi capolavori dell\'arte antica, datati al V secolo a.C., rappresentano due guerrieri in posizione eretta e sono considerati tra le più significative testimonianze della scultura greca pervenute fino a noi. Oggi custoditi presso il Museo Archeologico Nazionale di Reggio Calabria, attirano visitatori da tutto il mondo.', 'I celebri Bronzi di Riace, capolavori della scultura greca del V secolo a.C.', 2, 4, 10, 'Elena Greco', 1, 38.11130000, 15.64420000),

('La \'Nduja: Il Piccante Orgoglio di Spilinga', 'la-nduja-il-piccante-orgoglio-di-spilinga', 'La \'nduja è un salume piccante spalmabile originario di Spilinga, piccolo borgo in provincia di Vibo Valentia. Questa prelibatezza, ottenuta da carni suine e peperoncino calabrese, rappresenta l\'essenza della tradizione gastronomica locale. La sua preparazione segue ancora oggi ricette tramandate di generazione in generazione, utilizzando solo ingredienti locali di altissima qualità.', 'La \'nduja di Spilinga, salume piccante simbolo della gastronomia calabrese.', 3, 5, NULL, 'Giuseppe Calabrese', 1, 38.65000000, 15.90000000),

('Tropea: La Perla del Tirreno', 'tropea-la-perla-del-tirreno', 'Tropea è universalmente riconosciuta come una delle località balneari più belle d\'Italia. Arroccata su un promontorio a strapiombo sul mare, offre uno dei panorami più suggestivi della Calabria. Le sue spiagge di sabbia bianca, bagnate da acque cristalline, e il centro storico ricco di chiese e palazzi nobiliari, fanno di Tropea una meta imperdibile per chi visita la regione.', 'Tropea, perla del Tirreno con spiagge da sogno e centro storico mozzafiato.', 4, 4, 11, 'Maria Costantino', 1, 38.67730000, 15.89840000),

('Gerace: Il Borgo Medievale di Pietra', 'gerace-il-borgo-medievale-di-pietra', 'Gerace è uno dei borghi medievali più belli e meglio conservati della Calabria. Arroccato su una rupe a 500 metri di altitudine, domina la vallata del fiume Novito e offre panorami spettacolari sulla costa ionica. Il suo centro storico, dichiarato Monumento Nazionale, custodisce tesori architettonici di inestimabile valore, tra cui la maestosa Cattedrale normanna.', 'Gerace, borgo medievale arroccato su una rupe con tesori architettonici unici.', 6, 4, NULL, 'Antonio Meduri', 0, 38.27090000, 16.21980000);

-- ============================================================================
-- INDICI E OTTIMIZZAZIONI
-- ============================================================================

-- Indici per performance
ALTER TABLE `articles` DROP INDEX IF EXISTS `idx_articles_status_featured`;
CREATE INDEX `idx_articles_status_featured` ON `articles`(`status`, `featured`);
ALTER TABLE `articles` DROP INDEX IF EXISTS `idx_articles_category_status`;
CREATE INDEX `idx_articles_category_status` ON `articles`(`category_id`, `status`);
ALTER TABLE `articles` DROP INDEX IF EXISTS `idx_articles_province_status`;
CREATE INDEX `idx_articles_province_status` ON `articles`(`province_id`, `status`);
ALTER TABLE `articles` DROP INDEX IF EXISTS `idx_articles_created_at`;
CREATE INDEX `idx_articles_created_at` ON `articles`(`created_at`);
ALTER TABLE `articles` DROP INDEX IF EXISTS `idx_articles_views`;
CREATE INDEX `idx_articles_views` ON `articles`(`views`);
ALTER TABLE `comments` DROP INDEX IF EXISTS `idx_comments_status`;
CREATE INDEX `idx_comments_status` ON `comments`(`status`);
ALTER TABLE `comments` DROP INDEX IF EXISTS `idx_comments_created_at`;
CREATE INDEX `idx_comments_created_at` ON `comments`(`created_at`);
ALTER TABLE `businesses` DROP INDEX IF EXISTS `idx_businesses_status`;
CREATE INDEX `idx_businesses_status` ON `businesses`(`status`);
ALTER TABLE `events` DROP INDEX IF EXISTS `idx_events_start_date_status`;
CREATE INDEX `idx_events_start_date_status` ON `events`(`start_date`, `status`);

-- ============================================================================
-- FINALIZZAZIONE
-- ============================================================================

COMMIT;

-- Messaggio di completamento
-- Database Passione Calabria creato con successo!
--
-- STATISTICHE:
-- - 15 tabelle create
-- - 18 categorie inserite
-- - 5 province inserite
-- - 13 città inserite
-- - 5 articoli di esempio
-- - 3 pacchetti business
-- - 5 pagine statiche
-- - Tutte le impostazioni configurate
--
-- Il database è pronto per l'uso con Passione Calabria PHP!
