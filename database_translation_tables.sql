-- TABELLE AGGIUNTIVE PER IL SISTEMA DI TRADUZIONE COMPLETO
-- Da eseguire per completare il sistema di traduzioni

-- Tabella per la cache delle traduzioni (utilizzata dall'API)
CREATE TABLE IF NOT EXISTS `translations_cache` (
  `id` int NOT NULL AUTO_INCREMENT,
  `original_text_hash` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `original_text` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `translated_text` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `source_lang` varchar(5) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'it',
  `target_lang` varchar(5) COLLATE utf8mb4_unicode_ci NOT NULL,
  `api_provider` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `confidence_score` decimal(3,2) DEFAULT 1.00,
  `usage_count` int DEFAULT 1,
  `page_url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `element_selector` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `context_info` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `last_used_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_cache_hash_target` (`original_text_hash`, `target_lang`),
  KEY `idx_cache_target_lang` (`target_lang`),
  KEY `idx_cache_provider` (`api_provider`),
  KEY `idx_cache_created` (`created_at`),
  KEY `idx_cache_usage` (`usage_count`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabella per le impostazioni dei provider di traduzione
CREATE TABLE IF NOT EXISTS `translation_settings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `api_provider` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `api_key` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `api_url` varchar(300) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `priority` int DEFAULT 1,
  `is_active` tinyint(1) DEFAULT 1,
  `daily_quota` int DEFAULT 100000,
  `current_usage` int DEFAULT 0,
  `usage_reset_date` date DEFAULT (curdate()),
  `config_data` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `api_provider` (`api_provider`),
  KEY `idx_provider_active` (`is_active`, `priority`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabella per le lingue supportate (compatibile con l'API)
CREATE TABLE IF NOT EXISTS `supported_languages` (
  `id` int NOT NULL AUTO_INCREMENT,
  `code` varchar(5) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `native_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_default` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `sort_order` int DEFAULT 0,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`),
  KEY `idx_lang_active` (`is_active`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabella per le statistiche di traduzione
CREATE TABLE IF NOT EXISTS `translation_stats` (
  `id` int NOT NULL AUTO_INCREMENT,
  `date` date NOT NULL,
  `api_provider` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `target_lang` varchar(5) COLLATE utf8mb4_unicode_ci NOT NULL,
  `api_calls` int DEFAULT 0,
  `cache_hits` int DEFAULT 0,
  `total_characters` int DEFAULT 0,
  `avg_response_time` decimal(8,2) DEFAULT 0.00,
  `error_count` int DEFAULT 0,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_daily_stat` (`date`, `api_provider`, `target_lang`),
  KEY `idx_stats_date` (`date`),
  KEY `idx_stats_provider` (`api_provider`),
  KEY `idx_stats_lang` (`target_lang`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- INSERIMENTO DATI INIZIALI

-- Lingue supportate (sincronizza con preventive_languages esistente)
INSERT IGNORE INTO `supported_languages` (`code`, `name`, `native_name`, `is_default`, `is_active`, `sort_order`) VALUES
('it', 'Italiano', 'Italiano', 1, 1, 1),
('en', 'English', 'English', 0, 1, 2),
('fr', 'Français', 'Français', 0, 1, 3),
('de', 'Deutsch', 'Deutsch', 0, 1, 4),
('es', 'Español', 'Español', 0, 1, 5);

-- Provider di traduzione di esempio (da configurare con chiavi API reali)
INSERT IGNORE INTO `translation_settings` (`api_provider`, `api_key`, `priority`, `is_active`, `daily_quota`) VALUES
('deepl', '', 1, 0, 500000),
('google', '', 2, 0, 1000000),
('yandex', '', 3, 0, 100000);

-- Indici aggiuntivi per performance
CREATE INDEX IF NOT EXISTS `idx_articles_slug_status` ON `articles` (`slug`, `status`);
CREATE INDEX IF NOT EXISTS `idx_static_content_key_active` ON `static_content` (`content_key`);

-- Commit delle modifiche
COMMIT;"