-- phpMyAdmin SQL Dump corretto con constraint per rating
-- version 4.9.11
-- https://www.phpmyadmin.net/
--
-- Host: db5018301966.hosting-data.io
-- Creato il: Set 02, 2025 alle 09:59
-- Versione del server: 8.0.36
-- Versione PHP: 7.4.33

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `dbs14504718`
--
CREATE DATABASE IF NOT EXISTS `dbs14504718` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `dbs14504718`;

-- --------------------------------------------------------

--
-- Struttura della tabella `articles`
--

CREATE TABLE `articles` (
  `id` int NOT NULL,
  `title` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `excerpt` text COLLATE utf8mb4_unicode_ci,
  `featured_image` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `gallery_images` json DEFAULT NULL,
  `category_id` int NOT NULL,
  `province_id` int DEFAULT NULL,
  `city_id` int DEFAULT NULL,
  `author` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('draft','published','archived') COLLATE utf8mb4_unicode_ci DEFAULT 'published',
  `featured` tinyint(1) DEFAULT '0',
  `views` int DEFAULT '0',
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `allow_user_uploads` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dump dei dati per la tabella `articles`
--

INSERT INTO `articles` (`id`, `title`, `slug`, `content`, `excerpt`, `featured_image`, `gallery_images`, `category_id`, `province_id`, `city_id`, `author`, `status`, `featured`, `views`, `latitude`, `longitude`, `allow_user_uploads`, `created_at`) VALUES
(1, 'La Sila: Il Cuore Verde della Calabria', 'la-sila-il-cuore-verde-della-calabria', 'L\'Altopiano della Sila rappresenta uno dei tesori naturalistici più preziosi della Calabria. Con i suoi 150.000 ettari di territorio, questo polmone verde offre paesaggi mozzafiato, laghi cristallini e una biodiversità unica. I tre parchi che compongono la Sila - Sila Grande, Sila Piccola e Sila Greca - custodiscono foreste secolari di pini larici e faggi, praterie alpine e una fauna ricca che include lupi, caprioli e l\'aquila reale.', 'Scopri l\'Altopiano della Sila, polmone verde della Calabria con laghi cristallini e foreste secolari.', NULL, NULL, 1, 2, NULL, 'Marco Rossi', 'published', 1, 0, '39.30000000', '16.50000000', 1, '2025-09-01 09:55:19'),
(2, 'I Bronzi di Riace: Capolavori della Magna Grecia', 'i-bronzi-di-riace-capolavori-della-magna-grecia', 'I Bronzi di Riace sono due statue di bronzo di epoca greca classica, rinvenute nel 1972 nei fondali marini antistanti Riace Marina. Questi capolavori dell\'arte antica, datati al V secolo a.C., rappresentano due guerrieri in posizione eretta e sono considerati tra le più significative testimonianze della scultura greca pervenute fino a noi. Oggi custoditi presso il Museo Archeologico Nazionale di Reggio Calabria, attirano visitatori da tutto il mondo.', 'I celebri Bronzi di Riace, capolavori della scultura greca del V secolo a.C.', NULL, NULL, 2, 4, 10, 'Elena Greco', 'published', 1, 0, '38.11130000', '15.64420000', 1, '2025-09-01 09:55:19'),
(3, 'La \'Nduja: Il Piccante Orgoglio di Spilinga', 'la-nduja-il-piccante-orgoglio-di-spilinga', 'La \'nduja è un salume piccante spalmabile originario di Spilinga, piccolo borgo in provincia di Vibo Valentia. Questa prelibatezza, ottenuta da carni suine e peperoncino calabrese, rappresenta l\'essenza della tradizione gastronomica locale. La sua preparazione segue ancora oggi ricette tramandate di generazione in generazione, utilizzando solo ingredienti locali di altissima qualità.', 'La \'nduja di Spilinga, salume piccante simbolo della gastronomia calabrese.', NULL, NULL, 3, 5, NULL, 'Giuseppe Calabrese', 'published', 1, 0, '38.65000000', '15.90000000', 1, '2025-09-01 09:55:19'),
(4, 'Tropea: La Perla del Tirreno', 'tropea-la-perla-del-tirreno', 'Tropea è universalmente riconosciuta come una delle località balneari più belle d\'Italia. Arroccata su un promontorio a strapiombo sul mare, offre uno dei panorami più suggestivi della Calabria. Le sue spiagge di sabbia bianca, bagnate da acque cristalline, e il centro storico ricco di chiese e palazzi nobiliari, fanno di Tropea una meta imperdibile per chi visita la regione.', 'Tropea, perla del Tirreno con spiagge da sogno e centro storico mozzafiato.', NULL, NULL, 4, 4, 11, 'Maria Costantino', 'published', 1, 0, '38.67730000', '15.89840000', 1, '2025-09-01 09:55:19'),
(5, 'Gerace: Il Borgo Medievale di Pietra', 'gerace-il-borgo-medievale-di-pietra', 'Gerace è uno dei borghi medievali più belli e meglio conservati della Calabria. Arroccato su una rupe a 500 metri di altitudine, domina la vallata del fiume Novito e offre panorami spettacolari sulla costa ionica. Il suo centro storico, dichiarato Monumento Nazionale, custodisce tesori architettonici di inestimabile valore, tra cui la maestosa Cattedrale normanna.', 'Gerace, borgo medievale arroccato su una rupe con tesori architettonici unici.', NULL, NULL, 6, 4, NULL, 'Antonio Meduri', 'published', 0, 0, '38.27090000', '16.21980000', 1, '2025-09-01 09:55:19');

-- --------------------------------------------------------

--
-- Struttura della tabella `article_translations`
--

CREATE TABLE `article_translations` (
  `id` int NOT NULL,
  `article_id` int NOT NULL,
  `language_code` varchar(5) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `excerpt` text COLLATE utf8mb4_unicode_ci,
  `meta_description` text COLLATE utf8mb4_unicode_ci,
  `slug` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `translated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `businesses`
--

CREATE TABLE `businesses` (
  `id` int NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `website` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `category_id` int DEFAULT NULL,
  `province_id` int DEFAULT NULL,
  `city_id` int DEFAULT NULL,
  `address` text COLLATE utf8mb4_unicode_ci,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `status` enum('pending','approved','rejected','suspended') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `subscription_type` enum('free','basic','premium') COLLATE utf8mb4_unicode_ci DEFAULT 'free',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `business_packages`
--

CREATE TABLE `business_packages` (
  `id` int NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `price` decimal(10,2) NOT NULL,
  `duration_months` int DEFAULT '12',
  `features` json DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `sort_order` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dump dei dati per la tabella `business_packages`
--

INSERT INTO `business_packages` (`id`, `name`, `description`, `price`, `duration_months`, `features`, `is_active`, `sort_order`, `created_at`) VALUES
(1, 'Gratuito', 'Inserimento base della tua attività', '0.00', 12, '[\"Scheda attività base\", \"Contatti e orari\", \"Visibilità nella ricerca\"]', 1, 1, '2025-09-01 09:55:19'),
(2, 'Business', 'Pacchetto completo per la tua attività', '29.99', 12, '[\"Tutto del piano Gratuito\", \"Foto illimitate\", \"Descrizione estesa\", \"Badge verificato\", \"Statistiche visualizzazioni\"]', 1, 2, '2025-09-01 09:55:19'),
(3, 'Premium', 'Massima visibilità e funzionalità avanzate', '59.99', 12, '[\"Tutto del piano Business\", \"Posizione privilegiata\", \"Articoli sponsorizzati\", \"Analytics avanzate\", \"Supporto prioritario\", \"Eventi promozionali\"]', 1, 3, '2025-09-01 09:55:19');

-- --------------------------------------------------------

--
-- Struttura della tabella `categories`
--

CREATE TABLE `categories` (
  `id` int NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `icon` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dump dei dati per la tabella `categories`
--

INSERT INTO `categories` (`id`, `name`, `description`, `icon`, `created_at`) VALUES
(1, 'Natura e Paesaggi', 'Scopri la bellezza naturale della Calabria', '🌿', '2025-09-01 09:55:19'),
(2, 'Storia e Cultura', 'Immergiti nella ricca storia calabrese', '🏛️', '2025-09-01 09:55:19'),
(3, 'Gastronomia', 'Assapora i sapori autentici della tradizione', '🍝', '2025-09-01 09:55:19'),
(4, 'Mare e Coste', 'Le più belle spiagge e località balneari', '🏖️', '2025-09-01 09:55:19'),
(5, 'Montagne e Escursioni', 'Avventure tra i monti calabresi', '⛰️', '2025-09-01 09:55:19'),
(6, 'Borghi e Tradizioni', 'Alla scoperta dei borghi più belli', '🏘️', '2025-09-01 09:55:19'),
(7, 'Arte e Musei', 'Tesori artistici e culturali', '🎨', '2025-09-01 09:55:19'),
(8, 'Feste e Eventi', 'Celebrazioni e manifestazioni locali', '🎭', '2025-09-01 09:55:19'),
(9, 'Artigianato', 'Mestieri e prodotti della tradizione', '🛠️', '2025-09-01 09:55:19'),
(10, 'Terme e Benessere', 'Relax e cure naturali', '♨️', '2025-09-01 09:55:19'),
(11, 'Parchi e Riserve', 'Aree protette e natura incontaminata', '🌲', '2025-09-01 09:55:19'),
(12, 'Architettura Religiosa', 'Chiese, monasteri e luoghi sacri', '⛪', '2025-09-01 09:55:19'),
(13, 'Archeologia', 'Siti archeologici e antiche testimonianze', '🏺', '2025-09-01 09:55:19'),
(14, 'Sport e Avventura', 'Attività sportive e outdoor', '🚴', '2025-09-01 09:55:19'),
(15, 'Enogastronomia', 'Vini e prodotti tipici locali', '🍷', '2025-09-01 09:55:19'),
(16, 'Fotografia', 'I luoghi più fotogenici della regione', '📸', '2025-09-01 09:55:19'),
(17, 'Musica e Spettacoli', 'Eventi culturali e artistici', '🎵', '2025-09-01 09:55:19'),
(18, 'Famiglia e Bambini', 'Attività e luoghi per famiglie', '👨‍👩‍👧‍👦', '2025-09-01 09:55:19');

-- --------------------------------------------------------

--
-- Struttura della tabella `cities`
--

CREATE TABLE `cities` (
  `id` int NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `province_id` int NOT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dump dei dati per la tabella `cities`
--

INSERT INTO `cities` (`id`, `name`, `province_id`, `latitude`, `longitude`, `description`, `created_at`) VALUES
(1, 'Catanzaro', 1, '38.90980000', '16.59690000', 'Capoluogo di regione', '2025-09-01 09:55:19'),
(2, 'Lamezia Terme', 1, '38.96480000', '16.31290000', 'Importante centro della piana', '2025-09-01 09:55:19'),
(3, 'Soverato', 1, '38.69180000', '16.55130000', 'Perla dello Ionio', '2025-09-01 09:55:19'),
(4, 'Cosenza', 2, '39.29480000', '16.25420000', 'Città dei Bruzi', '2025-09-01 09:55:19'),
(5, 'Rossano', 2, '39.57610000', '16.63140000', 'Città della liquirizia', '2025-09-01 09:55:19'),
(6, 'Paola', 2, '39.36560000', '16.03780000', 'Città di San Francesco', '2025-09-01 09:55:19'),
(7, 'Scalea', 2, '39.81470000', '15.79390000', 'Riviera dei Cedri', '2025-09-01 09:55:19'),
(8, 'Crotone', 3, '39.08470000', '17.12520000', 'Antica Kroton', '2025-09-01 09:55:19'),
(9, 'Cirò Marina', 3, '39.37260000', '17.12830000', 'Terra del vino Cirò', '2025-09-01 09:55:19'),
(10, 'Reggio Calabria', 4, '38.10980000', '15.65160000', 'Città dei Bronzi', '2025-09-01 09:55:19'),
(11, 'Tropea', 5, '38.67730000', '15.89760000', 'Perla del Tirreno', '2025-09-01 09:55:19'),
(12, 'Vibo Valentia', 5, '38.67590000', '16.10180000', 'Antica Hipponion', '2025-09-01 09:55:19'),
(13, 'Pizzo', 5, '38.73470000', '16.15690000', 'Città del tartufo', '2025-09-01 09:55:19');

-- --------------------------------------------------------

--
-- Struttura della tabella `comments`
-- CORRETTA: con constraint per rating 1-5
--

CREATE TABLE `comments` (
  `id` int NOT NULL,
  `article_id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `author_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `author_email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `rating` int DEFAULT NULL CHECK (`rating` >= 1 AND `rating` <= 5),
  `status` enum('pending','approved','rejected','spam') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `events`
--

CREATE TABLE `events` (
  `id` int NOT NULL,
  `title` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `start_date` datetime NOT NULL,
  `end_date` datetime DEFAULT NULL,
  `location` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `category_id` int DEFAULT NULL,
  `province_id` int DEFAULT NULL,
  `city_id` int DEFAULT NULL,
  `organizer` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contact_email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contact_phone` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `website` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `featured_image` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `price` decimal(10,2) DEFAULT '0.00',
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `status` enum('pending','active','cancelled','completed') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `source` enum('admin','user_submission') COLLATE utf8mb4_unicode_ci DEFAULT 'admin',
  `business_id` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `home_sections`
--

CREATE TABLE `home_sections` (
  `id` int NOT NULL,
  `section_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `subtitle` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `image_path` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_visible` tinyint(1) DEFAULT '1',
  `sort_order` int DEFAULT '0',
  `custom_data` json DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dump dei dati per la tabella `home_sections`
--

INSERT INTO `home_sections` (`id`, `section_name`, `title`, `subtitle`, `description`, `image_path`, `is_visible`, `sort_order`, `custom_data`) VALUES
(1, 'hero', 'Esplora la Calabria', 'Mare cristallino e storia millenaria', 'Immergiti nella bellezza della Calabria', '/placeholder-hero.jpg', 1, 1, NULL),
(2, 'categories', 'Esplora per Categoria', '', 'Scopri la Calabria attraverso le sue diverse sfaccettature', '', 1, 2, NULL),
(3, 'provinces', 'Esplora le Province', '', 'Ogni provincia calabrese custodisce tesori unici', '', 1, 3, NULL),
(4, 'map', 'Mappa Interattiva', '', 'Naviga attraverso la Calabria con la nostra mappa interattiva', '', 1, 4, NULL),
(5, 'cta', 'Vuoi far Conoscere la Tua Calabria?', '', 'Unisciti alla nostra community!', '', 1, 5, NULL),
(6, 'newsletter', 'Resta Connesso con la Calabria', '', 'Iscriviti alla nostra newsletter', '', 1, 6, NULL);

-- --------------------------------------------------------

--
-- Struttura della tabella `newsletter_subscribers`
--

CREATE TABLE `newsletter_subscribers` (
  `id` int NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `interests` json DEFAULT NULL,
  `status` enum('active','confirmed','unsubscribed') COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `confirmation_token` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `confirmed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `place_suggestions`
--

CREATE TABLE `place_suggestions` (
  `id` int NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `category_id` int DEFAULT NULL,
  `province_id` int DEFAULT NULL,
  `city_id` int DEFAULT NULL,
  `address` text COLLATE utf8mb4_unicode_ci,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `suggested_by_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `suggested_by_email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `images` json DEFAULT NULL,
  `status` enum('pending','approved','rejected') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `admin_notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `preventive_languages`
--

CREATE TABLE `preventive_languages` (
  `id` int NOT NULL,
  `code` varchar(5) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `native_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_default` tinyint(1) DEFAULT '0',
  `is_fallback` tinyint(1) DEFAULT '0',
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dump dei dati per la tabella `preventive_languages`
--

INSERT INTO `preventive_languages` (`id`, `code`, `name`, `native_name`, `is_default`, `is_fallback`, `is_active`, `created_at`) VALUES
(1, 'it', 'Italiano', 'Italiano', 1, 0, 1, '2025-09-01 09:55:19'),
(2, 'en', 'English', 'English', 0, 1, 1, '2025-09-01 09:55:19'),
(3, 'fr', 'Français', 'Français', 0, 0, 1, '2025-09-01 09:55:19'),
(4, 'de', 'Deutsch', 'Deutsch', 0, 0, 1, '2025-09-01 09:55:19'),
(5, 'es', 'Español', 'Español', 0, 0, 1, '2025-09-01 09:55:19');

-- --------------------------------------------------------

--
-- Struttura della tabella `provinces`
--

CREATE TABLE `provinces` (
  `id` int NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `image_path` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dump dei dati per la tabella `provinces`
--

INSERT INTO `provinces` (`id`, `name`, `description`, `image_path`, `created_at`) VALUES
(1, 'Catanzaro', 'Capoluogo di regione, cuore della Calabria tra due mari', NULL, '2025-09-01 09:55:19'),
(2, 'Cosenza', 'La provincia più estesa, ricca di storia e natura', NULL, '2025-09-01 09:55:19'),
(3, 'Crotone', 'Terra di Pitagora, tra mare cristallino e archeologia', NULL, '2025-09-01 09:55:19'),
(4, 'Reggio Calabria', 'La punta dello stivale, affacciata sullo Stretto di Messina', NULL, '2025-09-01 09:55:19'),
(5, 'Vibo Valentia', 'Piccola provincia ricca di tradizioni marinare e gastronomiche', NULL, '2025-09-01 09:55:19');

-- --------------------------------------------------------

--
-- Struttura della tabella `settings`
--

CREATE TABLE `settings` (
  `id` int NOT NULL,
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` text COLLATE utf8mb4_unicode_ci,
  `type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'text',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dump dei dati per la tabella `settings`
--

INSERT INTO `settings` (`id`, `key`, `value`, `type`, `created_at`) VALUES
(1, 'google_recaptcha_v2_site_key', '', 'text', '2025-09-01 09:55:19'),
(2, 'google_recaptcha_v2_secret_key', '', 'password', '2025-09-01 09:55:19'),
(3, 'google_recaptcha_v3_site_key', '', 'text', '2025-09-01 09:55:19'),
(4, 'google_recaptcha_v3_secret_key', '', 'password', '2025-09-01 09:55:19'),
(5, 'stripe_publishable_key', '', 'text', '2025-09-01 09:55:19'),
(6, 'stripe_secret_key', '', 'password', '2025-09-01 09:55:19'),
(7, 'google_analytics_id', '', 'text', '2025-09-01 09:55:19'),
(8, 'app_store_link', '', 'url', '2025-09-01 09:55:19'),
(9, 'app_store_image', '', 'text', '2025-09-01 09:55:19'),
(10, 'play_store_link', '', 'url', '2025-09-01 09:55:19'),
(11, 'play_store_image', '', 'text', '2025-09-01 09:55:19'),
(12, 'vai_app_link', '', 'url', '2025-09-01 09:55:19'),
(13, 'suggerisci_evento_link', '', 'url', '2025-09-01 09:55:19'),
(14, 'hero_title', 'Esplora la Calabria', 'text', '2025-09-01 09:55:19'),
(15, 'hero_subtitle', 'Mare cristallino e storia millenaria', 'text', '2025-09-01 09:55:19'),
(16, 'hero_description', 'Immergiti nella bellezza della Calabria, con le sue spiagge da sogno, il centro storico affascinante e i panorami mozzafiato dalla rupe.', 'textarea', '2025-09-01 09:55:19'),
(17, 'hero_image', '/placeholder-hero.jpg', 'text', '2025-09-01 09:55:19');

-- --------------------------------------------------------

--
-- Struttura della tabella `static_content`
--

CREATE TABLE `static_content` (
  `id` int NOT NULL,
  `content_key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `content_it` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `context_info` text COLLATE utf8mb4_unicode_ci,
  `page_location` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dump dei dati per la tabella `static_content`
--

INSERT INTO `static_content` (`id`, `content_key`, `content_it`, `context_info`, `page_location`, `created_at`) VALUES
(1, 'hero-title', 'Esplora la Calabria', 'Titolo principale della homepage', 'homepage-hero', '2025-09-01 09:55:19'),
(2, 'hero-subtitle', 'Mare cristallino e storia millenaria', 'Sottotitolo hero section', 'homepage-hero', '2025-09-01 09:55:19'),
(3, 'hero-description', 'Immergiti nella bellezza della Calabria, con le sue spiagge da sogno, il centro storico affascinante e i panorami mozzafiato dalla rupe.', 'Descrizione hero section', 'homepage-hero', '2025-09-01 09:55:19'),
(4, 'discover-calabria-btn', 'Scopri la Calabria', 'Pulsante principale homepage', 'homepage-hero', '2025-09-01 09:55:19'),
(5, 'view-map-btn', 'Visualizza Mappa', 'Pulsante mappa homepage', 'homepage-hero', '2025-09-01 09:55:19'),
(6, 'search-what', 'Cosa stai cercando?', 'Titolo widget ricerca', 'homepage-search', '2025-09-01 09:55:19'),
(7, 'search-label', 'Luoghi, eventi, tradizioni...', 'Label campo ricerca', 'homepage-search', '2025-09-01 09:55:19'),
(8, 'search-placeholder', 'Inserisci quello che vuoi esplorare', 'Placeholder campo ricerca', 'homepage-search', '2025-09-01 09:55:19'),
(9, 'province-label', 'Provincia', 'Label select provincia', 'homepage-search', '2025-09-01 09:55:19'),
(10, 'all-provinces', 'Tutte le province', 'Opzione default select provincia', 'homepage-search', '2025-09-01 09:55:19'),
(11, 'search-btn', 'Cerca', 'Pulsante ricerca', 'homepage-search', '2025-09-01 09:55:19'),
(12, 'events-app', 'Eventi e App', 'Titolo sezione eventi', 'homepage-events', '2025-09-01 09:55:19'),
(13, 'app-description', 'Scarica la nostra app per rimanere sempre aggiornato sugli eventi in Calabria.', 'Descrizione app', 'homepage-events', '2025-09-01 09:55:19'),
(14, 'download-app-store', 'Scarica su App Store', 'Alt text App Store', 'homepage-events', '2025-09-01 09:55:19'),
(15, 'download-google-play', 'Scarica su Google Play', 'Alt text Google Play', 'homepage-events', '2025-09-01 09:55:19'),
(16, 'go-to-app', 'Vai all\'App', 'Pulsante vai app', 'homepage-events', '2025-09-01 09:55:19'),
(17, 'suggest-event', 'Suggerisci Evento', 'Pulsante suggerisci evento', 'homepage-events', '2025-09-01 09:55:19'),
(18, 'suggest-event-description', 'Hai un evento da condividere? Segnalacelo e lo valuteremo per includerlo nella nostra piattaforma.', 'Descrizione suggerisci evento', 'homepage-events', '2025-09-01 09:55:19'),
(19, 'explore-by-category', 'Esplora per Categoria', 'Titolo sezione categorie', 'homepage-categories', '2025-09-01 09:55:19'),
(20, 'category-description', 'Scopri la Calabria attraverso le sue diverse sfaccettature: dalla natura incontaminata alla ricca tradizione culturale.', 'Descrizione sezione categorie', 'homepage-categories', '2025-09-01 09:55:19'),
(21, 'articles-count', 'articoli', 'Testo conteggio articoli', 'homepage-categories', '2025-09-01 09:55:19'),
(22, 'contents', 'contenuti', 'Testo generico per contenuti', 'global', '2025-09-01 09:55:19'),
(23, 'explore', 'Esplora', 'Pulsante esplora', 'global', '2025-09-01 09:55:19'),
(24, 'provinces-title', 'Esplora le Province', 'Titolo sezione province', 'homepage-provinces', '2025-09-01 09:55:19'),
(25, 'provinces-description', 'Ogni provincia calabrese custodisce tesori unici: dalla costa tirrenica a quella ionica, dai monti della Sila all\'Aspromonte.', 'Descrizione sezione province', 'homepage-provinces', '2025-09-01 09:55:19'),
(26, 'main-locations', 'LOCALITÀ PRINCIPALI:', 'Label località principali', 'homepage-provinces', '2025-09-01 09:55:19'),
(27, 'with-photo', 'Con foto', 'Badge con foto', 'homepage-provinces', '2025-09-01 09:55:19'),
(28, 'map-title', 'Esplora la Mappa Interattiva', 'Titolo sezione mappa', 'homepage-map', '2025-09-01 09:55:19'),
(29, 'map-description', 'Naviga attraverso la Calabria con la nostra mappa interattiva. Scopri luoghi, eventi e punti d\'interesse in tempo reale.', 'Descrizione mappa', 'homepage-map', '2025-09-01 09:55:19'),
(30, 'see-all-categories', 'Vedi Tutte le Categorie', 'Pulsante tutte categorie', 'homepage-categories', '2025-09-01 09:55:19'),
(31, 'newsletter-description', 'Iscriviti alla nostra newsletter per ricevere i migliori contenuti e non perdere mai gli eventi più interessanti della regione.', 'Descrizione newsletter', 'homepage-newsletter', '2025-09-01 09:55:19'),
(32, 'newsletter-button', 'Iscriviti Gratis', 'Pulsante newsletter', 'homepage-newsletter', '2025-09-01 09:55:19');

-- --------------------------------------------------------

--
-- Struttura della tabella `static_content_translations`
--

CREATE TABLE `static_content_translations` (
  `id` int NOT NULL,
  `static_content_id` int NOT NULL,
  `language_code` varchar(5) COLLATE utf8mb4_unicode_ci NOT NULL,
  `translated_content` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `translated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `static_pages`
--

CREATE TABLE `static_pages` (
  `id` int NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `meta_title` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `meta_description` text COLLATE utf8mb4_unicode_ci,
  `is_published` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dump dei dati per la tabella `static_pages`
--

INSERT INTO `static_pages` (`id`, `slug`, `title`, `content`, `meta_title`, `meta_description`, `is_published`, `created_at`) VALUES
(1, 'chi-siamo', 'Chi Siamo', '<h1>Chi Siamo</h1><p>Benvenuti in Passione Calabria, il portale dedicato alla scoperta di una delle regioni più affascinanti d\'Italia.</p><p>La nostra missione è far conoscere la vera essenza della Calabria: dalle spiagge cristalline della Costa degli Dei ai borghi medievali dell\'entroterra, dalle tradizioni gastronomiche millenarie alle meraviglie naturali dei parchi nazionali.</p>', 'Chi Siamo - Passione Calabria', 'Scopri chi siamo e la nostra missione per promuovere la bellezza e le tradizioni della Calabria.', 1, '2025-09-01 09:55:19'),
(2, 'privacy-policy', 'Privacy Policy', '<h1>Privacy Policy</h1><p>Questa privacy policy descrive come raccogliamo, utilizziamo e proteggiamo le tue informazioni personali.</p><h2>Raccolta delle Informazioni</h2><p>Raccogliamo informazioni quando ti registri al nostro sito, ti iscrivi alla newsletter o compili un modulo.</p>', 'Privacy Policy - Passione Calabria', 'La nostra politica sulla privacy e protezione dei dati personali.', 1, '2025-09-01 09:55:19'),
(3, 'termini-servizio', 'Termini di Servizio', '<h1>Termini di Servizio</h1><p>Questi termini e condizioni governano il tuo uso del nostro sito web e servizi.</p><h2>Accettazione dei Termini</h2><p>Utilizzando il nostro sito, accetti di essere vincolato da questi termini di servizio.</p>', 'Termini di Servizio - Passione Calabria', 'I termini e condizioni per l\'utilizzo del nostro sito web e servizi.', 1, '2025-09-01 09:55:19'),
(4, 'contatti', 'Contatti', '<h1>Contatti</h1><p>Siamo sempre felici di sentire da voi! Ecco come potete raggiungerci:</p><h2>Informazioni di Contatto</h2><p><strong>Email:</strong> info@passionecalabria.it</p><p><strong>Telefono:</strong> +39 XXX XXX XXXX</p><p><strong>Indirizzo:</strong> Via Roma, 123 - 88100 Catanzaro (CZ)</p>', 'Contatti - Passione Calabria', 'Come contattarci per informazioni, collaborazioni o segnalazioni.', 1, '2025-09-01 09:55:19'),
(5, 'cookie-policy', 'Cookie Policy', '<h1>Cookie Policy</h1><p>Questo sito utilizza cookies per migliorare la tua esperienza di navigazione.</p><h2>Cosa sono i Cookies</h2><p>I cookies sono piccoli file di testo che vengono memorizzati sul tuo dispositivo quando visiti un sito web.</p>', 'Cookie Policy - Passione Calabria', 'La nostra politica sui cookies e come li utilizziamo.', 1, '2025-09-01 09:55:19');

-- --------------------------------------------------------

--
-- Struttura della tabella `subscriptions`
--

CREATE TABLE `subscriptions` (
  `id` int NOT NULL,
  `business_id` int NOT NULL,
  `package_id` int NOT NULL,
  `stripe_subscription_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('pending','active','cancelled','expired') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `start_date` datetime DEFAULT NULL,
  `end_date` datetime DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `translation_config`
--

CREATE TABLE `translation_config` (
  `id` int NOT NULL,
  `api_provider` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'google',
  `api_key` text COLLATE utf8mb4_unicode_ci,
  `is_enabled` tinyint(1) DEFAULT '1',
  `daily_quota` int DEFAULT '10000',
  `current_daily_usage` int DEFAULT '0',
  `last_reset_date` date DEFAULT (curdate()),
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dump dei dati per la tabella `translation_config`
--

INSERT INTO `translation_config` (`id`, `api_provider`, `api_key`, `is_enabled`, `daily_quota`, `current_daily_usage`, `last_reset_date`, `created_at`) VALUES
(1, 'google', NULL, 1, 10000, 0, '2025-09-01', '2025-09-01 09:55:19');

-- --------------------------------------------------------

--
-- Struttura della tabella `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `first_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('user','admin','editor') COLLATE utf8mb4_unicode_ci DEFAULT 'user',
  `status` enum('active','inactive','banned') COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `avatar` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_login` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `user_uploads`
--

CREATE TABLE `user_uploads` (
  `id` int NOT NULL,
  `article_id` int DEFAULT NULL,
  `user_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `image_path` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `original_filename` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `status` enum('pending','approved','rejected') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `admin_notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indici per le tabelle scaricate
--

--
-- Indici per le tabelle `articles`
--
ALTER TABLE `articles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `province_id` (`province_id`),
  ADD KEY `city_id` (`city_id`),
  ADD KEY `status` (`status`),
  ADD KEY `featured` (`featured`),
  ADD KEY `idx_articles_status_featured` (`status`,`featured`),
  ADD KEY `idx_articles_category_status` (`category_id`,`status`),
  ADD KEY `idx_articles_province_status` (`province_id`,`status`),
  ADD KEY `idx_articles_created_at` (`created_at`),
  ADD KEY `idx_articles_views` (`views`);

--
-- Indici per le tabelle `article_translations`
--
ALTER TABLE `article_translations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `article_id_lang` (`article_id`,`language_code`),
  ADD KEY `idx_article_translations_article_lang` (`article_id`,`language_code`),
  ADD KEY `idx_article_translations_lang` (`language_code`);

--
-- Indici per le tabelle `businesses`
--
ALTER TABLE `businesses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `province_id` (`province_id`),
  ADD KEY `city_id` (`city_id`),
  ADD KEY `status` (`status`),
  ADD KEY `idx_businesses_status` (`status`);

--
-- Indici per le tabelle `business_packages`
--
ALTER TABLE `business_packages`
  ADD PRIMARY KEY (`id`);

--
-- Indici per le tabelle `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indici per le tabelle `cities`
--
ALTER TABLE `cities`
  ADD PRIMARY KEY (`id`),
  ADD KEY `province_id` (`province_id`);

--
-- Indici per le tabelle `comments`
--
ALTER TABLE `comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `article_id` (`article_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `status` (`status`),
  ADD KEY `idx_comments_status` (`status`),
  ADD KEY `idx_comments_created_at` (`created_at`);

--
-- Indici per le tabelle `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `province_id` (`province_id`),
  ADD KEY `city_id` (`city_id`),
  ADD KEY `business_id` (`business_id`),
  ADD KEY `start_date` (`start_date`),
  ADD KEY `status` (`status`),
  ADD KEY `idx_events_start_date_status` (`start_date`,`status`);

--
-- Indici per le tabelle `home_sections`
--
ALTER TABLE `home_sections`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `section_name` (`section_name`);

--
-- Indici per le tabelle `newsletter_subscribers`
--
ALTER TABLE `newsletter_subscribers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indici per le tabelle `place_suggestions`
--
ALTER TABLE `place_suggestions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `province_id` (`province_id`),
  ADD KEY `city_id` (`city_id`);

--
-- Indici per le tabelle `preventive_languages`
--
ALTER TABLE `preventive_languages`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indici per le tabelle `provinces`
--
ALTER TABLE `provinces`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indici per le tabelle `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `key` (`key`);

--
-- Indici per le tabelle `static_content`
--
ALTER TABLE `static_content`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `content_key` (`content_key`),
  ADD KEY `idx_static_content_key` (`content_key`);

--
-- Indici per le tabelle `static_content_translations`
--
ALTER TABLE `static_content_translations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `static_content_id_lang` (`static_content_id`,`language_code`),
  ADD KEY `idx_static_translations_content_lang` (`static_content_id`,`language_code`);

--
-- Indici per le tabelle `static_pages`
--
ALTER TABLE `static_pages`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Indici per le tabelle `subscriptions`
--
ALTER TABLE `subscriptions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `business_id` (`business_id`),
  ADD KEY `package_id` (`package_id`);

--
-- Indici per le tabelle `translation_config`
--
ALTER TABLE `translation_config`
  ADD PRIMARY KEY (`id`);

--
-- Indici per le tabelle `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indici per le tabelle `user_uploads`
--
ALTER TABLE `user_uploads`
  ADD PRIMARY KEY (`id`),
  ADD KEY `article_id` (`article_id`);

--
-- AUTO_INCREMENT per le tabelle scaricate
--

--
-- AUTO_INCREMENT per la tabella `articles`
--
ALTER TABLE `articles`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT per la tabella `article_translations`
--
ALTER TABLE `article_translations`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT per la tabella `businesses`
--
ALTER TABLE `businesses`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT per la tabella `business_packages`
--
ALTER TABLE `business_packages`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT per la tabella `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT per la tabella `cities`
--
ALTER TABLE `cities`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT per la tabella `comments`
--
ALTER TABLE `comments`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT per la tabella `events`
--
ALTER TABLE `events`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT per la tabella `home_sections`
--
ALTER TABLE `home_sections`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT per la tabella `newsletter_subscribers`
--
ALTER TABLE `newsletter_subscribers`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT per la tabella `place_suggestions`
--
ALTER TABLE `place_suggestions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT per la tabella `preventive_languages`
--
ALTER TABLE `preventive_languages`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT per la tabella `provinces`
--
ALTER TABLE `provinces`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT per la tabella `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT per la tabella `static_content`
--
ALTER TABLE `static_content`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT per la tabella `static_content_translations`
--
ALTER TABLE `static_content_translations`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT per la tabella `static_pages`
--
ALTER TABLE `static_pages`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT per la tabella `subscriptions`
--
ALTER TABLE `subscriptions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT per la tabella `translation_config`
--
ALTER TABLE `translation_config`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT per la tabella `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT per la tabella `user_uploads`
--
ALTER TABLE `user_uploads`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- Limiti per le tabelle scaricate
--

--
-- Limiti per la tabella `articles`
--
ALTER TABLE `articles`
  ADD CONSTRAINT `articles_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `articles_ibfk_2` FOREIGN KEY (`province_id`) REFERENCES `provinces` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `articles_ibfk_3` FOREIGN KEY (`city_id`) REFERENCES `cities` (`id`) ON DELETE SET NULL;

--
-- Limiti per la tabella `article_translations`
--
ALTER TABLE `article_translations`
  ADD CONSTRAINT `article_translations_ibfk_1` FOREIGN KEY (`article_id`) REFERENCES `articles` (`id`) ON DELETE CASCADE;

--
-- Limiti per la tabella `businesses`
--
ALTER TABLE `businesses`
  ADD CONSTRAINT `businesses_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `businesses_ibfk_2` FOREIGN KEY (`province_id`) REFERENCES `provinces` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `businesses_ibfk_3` FOREIGN KEY (`city_id`) REFERENCES `cities` (`id`) ON DELETE SET NULL;

--
-- Limiti per la tabella `cities`
--
ALTER TABLE `cities`
  ADD CONSTRAINT `cities_ibfk_1` FOREIGN KEY (`province_id`) REFERENCES `provinces` (`id`) ON DELETE CASCADE;

--
-- Limiti per la tabella `comments`
--
ALTER TABLE `comments`
  ADD CONSTRAINT `comments_ibfk_1` FOREIGN KEY (`article_id`) REFERENCES `articles` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `comments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Limiti per la tabella `events`
--
ALTER TABLE `events`
  ADD CONSTRAINT `events_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `events_ibfk_2` FOREIGN KEY (`province_id`) REFERENCES `provinces` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `events_ibfk_3` FOREIGN KEY (`city_id`) REFERENCES `cities` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `events_ibfk_4` FOREIGN KEY (`business_id`) REFERENCES `businesses` (`id`) ON DELETE SET NULL;

--
-- Limiti per la tabella `place_suggestions`
--
ALTER TABLE `place_suggestions`
  ADD CONSTRAINT `place_suggestions_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `place_suggestions_ibfk_2` FOREIGN KEY (`province_id`) REFERENCES `provinces` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `place_suggestions_ibfk_3` FOREIGN KEY (`city_id`) REFERENCES `cities` (`id`) ON DELETE SET NULL;

--
-- Limiti per la tabella `static_content_translations`
--
ALTER TABLE `static_content_translations`
  ADD CONSTRAINT `static_content_translations_ibfk_1` FOREIGN KEY (`static_content_id`) REFERENCES `static_content` (`id`) ON DELETE CASCADE;

--
-- Limiti per la tabella `subscriptions`
--
ALTER TABLE `subscriptions`
  ADD CONSTRAINT `subscriptions_ibfk_1` FOREIGN KEY (`business_id`) REFERENCES `businesses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `subscriptions_ibfk_2` FOREIGN KEY (`package_id`) REFERENCES `business_packages` (`id`) ON DELETE CASCADE;

--
-- Limiti per la tabella `user_uploads`
--
ALTER TABLE `user_uploads`
  ADD CONSTRAINT `user_uploads_ibfk_1` FOREIGN KEY (`article_id`) REFERENCES `articles` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;