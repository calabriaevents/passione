-- ============================================================================
-- PASSIONE CALABRIA - SISTEMA TRADUZIONE PREVENTIVA
-- Schema semplificato per traduzione preventiva con lingua base Italiana
-- ============================================================================

-- Tabella per le lingue supportate (semplificata)
CREATE TABLE IF NOT EXISTS preventive_languages (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    code VARCHAR(5) NOT NULL UNIQUE, -- it, en, fr, de, es
    name VARCHAR(50) NOT NULL,
    native_name VARCHAR(50) NOT NULL,
    is_default INTEGER DEFAULT 0, -- 1 per italiano (lingua base)
    is_fallback INTEGER DEFAULT 0, -- 1 per inglese (lingua fallback)
    is_active INTEGER DEFAULT 1,
    created_at TEXT DEFAULT (datetime('now'))
);

-- Tabella per traduzioni degli articoli
CREATE TABLE IF NOT EXISTS article_translations (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    article_id INTEGER NOT NULL,
    language_code VARCHAR(5) NOT NULL,
    title TEXT NOT NULL,
    content TEXT NOT NULL,
    excerpt TEXT,
    meta_description TEXT,
    slug VARCHAR(500),
    translated_at TEXT DEFAULT (datetime('now')),
    updated_at TEXT DEFAULT (datetime('now')),
    FOREIGN KEY (article_id) REFERENCES articles(id) ON DELETE CASCADE,
    UNIQUE(article_id, language_code)
);

-- Tabella per contenuti statici (menu, labels, buttons, etc)
CREATE TABLE IF NOT EXISTS static_content (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    content_key VARCHAR(255) NOT NULL UNIQUE, -- chiave identificativa es: 'hero-title', 'search-btn'
    content_it TEXT NOT NULL, -- contenuto in italiano (lingua base)
    context_info TEXT, -- informazioni di contesto per traduttore
    page_location VARCHAR(255), -- dove si trova il contenuto
    created_at TEXT DEFAULT (datetime('now')),
    updated_at TEXT DEFAULT (datetime('now'))
);

-- Tabella per traduzioni dei contenuti statici
CREATE TABLE IF NOT EXISTS static_content_translations (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    static_content_id INTEGER NOT NULL,
    language_code VARCHAR(5) NOT NULL,
    translated_content TEXT NOT NULL,
    translated_at TEXT DEFAULT (datetime('now')),
    updated_at TEXT DEFAULT (datetime('now')),
    FOREIGN KEY (static_content_id) REFERENCES static_content(id) ON DELETE CASCADE,
    UNIQUE(static_content_id, language_code)
);

-- Tabella per configurazione API traduzioni
CREATE TABLE IF NOT EXISTS translation_config (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    api_provider VARCHAR(20) DEFAULT 'google', -- google, deepl, etc
    api_key TEXT,
    is_enabled INTEGER DEFAULT 1,
    daily_quota INTEGER DEFAULT 10000,
    current_daily_usage INTEGER DEFAULT 0,
    last_reset_date TEXT DEFAULT (date('now')),
    created_at TEXT DEFAULT (datetime('now')),
    updated_at TEXT DEFAULT (datetime('now'))
);

-- Indici per performance
CREATE INDEX IF NOT EXISTS idx_article_translations_article_lang ON article_translations(article_id, language_code);
CREATE INDEX IF NOT EXISTS idx_article_translations_lang ON article_translations(language_code);
CREATE INDEX IF NOT EXISTS idx_static_translations_content_lang ON static_content_translations(static_content_id, language_code);
CREATE INDEX IF NOT EXISTS idx_static_content_key ON static_content(content_key);

-- Trigger per aggiornare updated_at
CREATE TRIGGER IF NOT EXISTS update_article_translations_timestamp
AFTER UPDATE ON article_translations
FOR EACH ROW
BEGIN
    UPDATE article_translations SET updated_at = datetime('now') WHERE id = NEW.id;
END;

CREATE TRIGGER IF NOT EXISTS update_static_content_timestamp
AFTER UPDATE ON static_content
FOR EACH ROW
BEGIN
    UPDATE static_content SET updated_at = datetime('now') WHERE id = NEW.id;
END;

CREATE TRIGGER IF NOT EXISTS update_static_translations_timestamp
AFTER UPDATE ON static_content_translations
FOR EACH ROW
BEGIN
    UPDATE static_content_translations SET updated_at = datetime('now') WHERE id = NEW.id;
END;

-- Dati iniziali - Lingue supportate
INSERT OR REPLACE INTO preventive_languages (code, name, native_name, is_default, is_fallback, is_active) VALUES
('it', 'Italiano', 'Italiano', 1, 0, 1), -- lingua base/default
('en', 'English', 'English', 0, 1, 1),   -- lingua fallback
('fr', 'Français', 'Français', 0, 0, 1),
('de', 'Deutsch', 'Deutsch', 0, 0, 1),
('es', 'Español', 'Español', 0, 0, 1);

-- Configurazione iniziale API
INSERT OR REPLACE INTO translation_config (api_provider, is_enabled, daily_quota) VALUES
('google', 1, 10000);

-- Contenuti statici principali (estratti dall'homepage)
INSERT OR REPLACE INTO static_content (content_key, content_it, context_info, page_location) VALUES
('hero-title', 'Esplora la Calabria', 'Titolo principale della homepage', 'homepage-hero'),
('hero-subtitle', 'Mare cristallino e storia millenaria', 'Sottotitolo hero section', 'homepage-hero'),
('hero-description', 'Immergiti nella bellezza della Calabria, con le sue spiagge da sogno, il centro storico affascinante e i panorami mozzafiato dalla rupe.', 'Descrizione hero section', 'homepage-hero'),
('discover-calabria-btn', 'Scopri la Calabria', 'Pulsante principale homepage', 'homepage-hero'),
('view-map-btn', 'Visualizza Mappa', 'Pulsante mappa homepage', 'homepage-hero'),
('search-what', 'Cosa stai cercando?', 'Titolo widget ricerca', 'homepage-search'),
('search-label', 'Luoghi, eventi, tradizioni...', 'Label campo ricerca', 'homepage-search'),
('search-placeholder', 'Inserisci quello che vuoi esplorare', 'Placeholder campo ricerca', 'homepage-search'),
('province-label', 'Provincia', 'Label select provincia', 'homepage-search'),
('all-provinces', 'Tutte le province', 'Opzione default select provincia', 'homepage-search'),
('search-btn', 'Cerca', 'Pulsante ricerca', 'homepage-search'),
('events-app', 'Eventi e App', 'Titolo sezione eventi', 'homepage-events'),
('app-description', 'Scarica la nostra app per rimanere sempre aggiornato sugli eventi in Calabria.', 'Descrizione app', 'homepage-events'),
('download-app-store', 'Scarica su App Store', 'Alt text App Store', 'homepage-events'),
('download-google-play', 'Scarica su Google Play', 'Alt text Google Play', 'homepage-events'),
('go-to-app', 'Vai all''App', 'Pulsante vai app', 'homepage-events'),
('suggest-event', 'Suggerisci Evento', 'Pulsante suggerisci evento', 'homepage-events'),
('suggest-event-description', 'Hai un evento da condividere? Segnalacelo e lo valuteremo per includerlo nella nostra piattaforma.', 'Descrizione suggerisci evento', 'homepage-events'),
('explore-by-category', 'Esplora per Categoria', 'Titolo sezione categorie', 'homepage-categories'),
('category-description', 'Scopri la Calabria attraverso le sue diverse sfaccettature: dalla natura incontaminata alla ricca tradizione culturale.', 'Descrizione sezione categorie', 'homepage-categories'),
('articles-count', 'articoli', 'Testo conteggio articoli', 'homepage-categories'),
('contents', 'contenuti', 'Testo generico per contenuti', 'global'),
('explore', 'Esplora', 'Pulsante esplora', 'global'),
('provinces-title', 'Esplora le Province', 'Titolo sezione province', 'homepage-provinces'),
('provinces-description', 'Ogni provincia calabrese custodisce tesori unici: dalla costa tirrenica a quella ionica, dai monti della Sila all''Aspromonte.', 'Descrizione sezione province', 'homepage-provinces'),
('main-locations', 'LOCALITÀ PRINCIPALI:', 'Label località principali', 'homepage-provinces'),
('with-photo', 'Con foto', 'Badge con foto', 'homepage-provinces'),
('map-title', 'Esplora la Mappa Interattiva', 'Titolo sezione mappa', 'homepage-map'),
('map-description', 'Naviga attraverso la Calabria con la nostra mappa interattiva. Scopri luoghi, eventi e punti d''interesse in tempo reale.', 'Descrizione mappa', 'homepage-map'),
('see-all-categories', 'Vedi Tutte le Categorie', 'Pulsante tutte categorie', 'homepage-categories'),
('newsletter-description', 'Iscriviti alla nostra newsletter per ricevere i migliori contenuti e non perdere mai gli eventi più interessanti della regione.', 'Descrizione newsletter', 'homepage-newsletter'),
('newsletter-button', 'Iscriviti Gratis', 'Pulsante newsletter', 'homepage-newsletter');

-- Schema creato con successo!
-- Prossimi passaggi:
-- 1. Implementare servizio traduzione automatica
-- 2. Creare hooks per traduzione al salvataggio
-- 3. Aggiornare frontend per caricare traduzioni dal DB