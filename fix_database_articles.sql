-- FIX per errore 500 salvataggio articoli
-- Aggiunge campi SEO mancanti alla tabella articles

-- Aggiungi campi SEO alla tabella articles
ALTER TABLE `articles` 
ADD COLUMN IF NOT EXISTS `seo_title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER `author`,
ADD COLUMN IF NOT EXISTS `seo_description` text COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER `seo_title`,
ADD COLUMN IF NOT EXISTS `seo_keywords` text COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER `seo_description`;

-- Verifica se esistono già le tabelle per category fields
CREATE TABLE IF NOT EXISTS `category_fields` (
  `id` int NOT NULL AUTO_INCREMENT,
  `category_id` int NOT NULL,
  `field_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `field_label` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `field_type` enum('text','textarea','email','url','number','datetime-local','file','select','checkbox') COLLATE utf8mb4_unicode_ci DEFAULT 'text',
  `field_options` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_required` tinyint(1) DEFAULT 0,
  `sort_order` int DEFAULT 0,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `category_id` (`category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `article_category_data` (
  `id` int NOT NULL AUTO_INCREMENT,
  `article_id` int NOT NULL,
  `category_field_id` int NOT NULL,
  `field_value` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `article_field` (`article_id`, `category_field_id`),
  KEY `article_id` (`article_id`),
  KEY `category_field_id` (`category_field_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Foreign keys per le nuove tabelle
ALTER TABLE `category_fields` 
ADD CONSTRAINT `fk_category_fields_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE;

ALTER TABLE `article_category_data` 
ADD CONSTRAINT `fk_article_category_data_article` FOREIGN KEY (`article_id`) REFERENCES `articles` (`id`) ON DELETE CASCADE,
ADD CONSTRAINT `fk_article_category_data_field` FOREIGN KEY (`category_field_id`) REFERENCES `category_fields` (`id`) ON DELETE CASCADE;

COMMIT;