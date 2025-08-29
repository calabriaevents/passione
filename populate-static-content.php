<?php
/**
 * Script per Popolare Contenuti Statici
 * 
 * Popola il database con contenuti statici di base per testare
 * il sistema di traduzione preventiva.
 */

$dbPath = __DIR__ . '/passione_calabria.db';

try {
    $pdo = new PDO('sqlite:' . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "🚀 Popolamento contenuti statici per sistema traduzione preventiva...\n\n";
    
    // Assicura che le tabelle esistano
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS static_content (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            content_key VARCHAR(255) NOT NULL UNIQUE,
            content_it TEXT NOT NULL,
            context_info TEXT,
            page_location VARCHAR(255),
            created_at TEXT DEFAULT (datetime('now')),
            updated_at TEXT DEFAULT (datetime('now'))
        )
    ");
    
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS static_content_translations (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            static_content_id INTEGER NOT NULL,
            language_code VARCHAR(5) NOT NULL,
            translated_content TEXT NOT NULL,
            translated_at TEXT DEFAULT (datetime('now')),
            updated_at TEXT DEFAULT (datetime('now')),
            FOREIGN KEY (static_content_id) REFERENCES static_content(id) ON DELETE CASCADE,
            UNIQUE(static_content_id, language_code)
        )
    ");
    
    // Contenuti statici di base
    $staticContents = [
        // Homepage Hero
        ['hero-title', 'Scopri la Calabria', 'Titolo principale homepage', 'homepage-hero'],
        ['hero-subtitle', 'Mare cristallino e storia millenaria', 'Sottotitolo hero section', 'homepage-hero'],
        ['hero-description', 'Immergiti nella bellezza della Calabria, con le sue spiagge da sogno, borghi medievali e panorami mozzafiato.', 'Descrizione hero section', 'homepage-hero'],
        
        // Navigazione
        ['nav-home', 'Home', 'Link navigazione home', 'navigation'],
        ['nav-categories', 'Categorie', 'Link navigazione categorie', 'navigation'],
        ['nav-provinces', 'Province', 'Link navigazione province', 'navigation'],
        ['nav-map', 'Mappa', 'Link navigazione mappa', 'navigation'],
        ['nav-register', 'Iscrivi la tua attività', 'Link registrazione business', 'navigation'],
        
        // Pulsanti e azioni
        ['discover-calabria-btn', 'Scopri la Calabria', 'Pulsante principale homepage', 'homepage-buttons'],
        ['view-map-btn', 'Visualizza Mappa', 'Pulsante mappa homepage', 'homepage-buttons'],
        ['search-btn', 'Cerca', 'Pulsante ricerca', 'search-form'],
        ['explore', 'Esplora', 'Pulsante generico esplora', 'global-buttons'],
        
        // Ricerca
        ['search-what', 'Cosa stai cercando?', 'Titolo widget ricerca', 'search-form'],
        ['search-label', 'Luoghi, eventi, tradizioni...', 'Label campo ricerca', 'search-form'],
        ['search-placeholder', 'Inserisci quello che vuoi esplorare', 'Placeholder campo ricerca', 'search-form'],
        ['province-label', 'Provincia', 'Label select provincia', 'search-form'],
        ['all-provinces', 'Tutte le province', 'Opzione default select provincia', 'search-form'],
        
        // Sezioni contenuto
        ['explore-by-category', 'Esplora per Categoria', 'Titolo sezione categorie', 'homepage-sections'],
        ['category-description', 'Scopri la Calabria attraverso le sue diverse sfaccettature: dalla natura incontaminata alla ricca tradizione culturale.', 'Descrizione sezione categorie', 'homepage-sections'],
        ['provinces-title', 'Esplora le Province', 'Titolo sezione province', 'homepage-sections'],
        ['provinces-description', 'Ogni provincia calabrese custodisce tesori unici: dalla costa tirrenica a quella ionica, dai monti della Sila all\'Aspromonte.', 'Descrizione sezione province', 'homepage-sections'],
        ['map-title', 'Mappa Interattiva', 'Titolo sezione mappa', 'homepage-sections'],
        ['map-description', 'Naviga attraverso la Calabria con la nostra mappa interattiva. Scopri luoghi, eventi e punti d\'interesse.', 'Descrizione mappa', 'homepage-sections'],
        
        // Eventi e app
        ['events-app', 'Eventi e App', 'Titolo sezione eventi', 'homepage-events'],
        ['app-description', 'Scarica la nostra app per rimanere sempre aggiornato sugli eventi in Calabria.', 'Descrizione app', 'homepage-events'],
        ['go-to-app', 'Vai all\'App', 'Pulsante vai app', 'homepage-events'],
        ['suggest-event', 'Suggerisci Evento', 'Pulsante suggerisci evento', 'homepage-events'],
        ['suggest-event-description', 'Hai un evento da condividere? Segnalacelo e lo valuteremo per includerlo nella nostra piattaforma.', 'Descrizione suggerisci evento', 'homepage-events'],
        
        // Contenuti generici
        ['contents', 'contenuti', 'Testo generico per contenuti', 'global-text'],
        ['articles-count', 'articoli', 'Testo conteggio articoli', 'global-text'],
        ['with-photo', 'Con foto', 'Badge con foto', 'global-badges'],
        ['main-locations', 'LOCALITÀ PRINCIPALI:', 'Label località principali', 'location-labels'],
        ['see-all-categories', 'Vedi Tutte le Categorie', 'Pulsante tutte categorie', 'homepage-buttons'],
        
        // Newsletter
        ['newsletter-description', 'Iscriviti alla nostra newsletter per ricevere i migliori contenuti e non perdere mai gli eventi più interessanti della regione.', 'Descrizione newsletter', 'homepage-newsletter'],
        ['newsletter-button', 'Iscriviti Gratis', 'Pulsante newsletter', 'homepage-newsletter'],
        
        // Footer e vari
        ['site-tagline', 'La tua guida alla Calabria', 'Tagline del sito', 'global-branding'],
        ['welcome-message', 'Benvenuto in Passione Calabria', 'Messaggio benvenuto', 'header'],
        ['current-language', 'Lingua', 'Label lingua corrente', 'language-selector'],
        
        // Sistema traduzione
        ['translation-system-active', 'Sistema di Traduzione Preventiva Attivo', 'Stato sistema traduzione', 'system-status'],
        ['api-configured', 'API Configurata', 'Stato API', 'system-status'],
        ['auto-translation-enabled', 'Traduzione Automatica Abilitata', 'Stato traduzione automatica', 'system-status']
    ];
    
    $stmt = $pdo->prepare("INSERT OR REPLACE INTO static_content (content_key, content_it, context_info, page_location) VALUES (?, ?, ?, ?)");
    
    $inserted = 0;
    foreach ($staticContents as $content) {
        if ($stmt->execute($content)) {
            $inserted++;
            echo "✅ Inserito: {$content[0]} = \"{$content[1]}\"\n";
        }
    }
    
    echo "\n📊 STATISTICHE:\n";
    echo "   - Contenuti statici inseriti: $inserted\n";
    echo "   - Lingue da tradurre: 4 (EN, FR, DE, ES)\n";
    echo "   - Traduzioni totali da generare: " . ($inserted * 4) . "\n\n";
    
    // Traduzioni di esempio per dimostrare il sistema (senza API)
    $exampleTranslations = [
        // Traduzioni inglese
        ['hero-title', 'en', 'Discover Calabria'],
        ['hero-subtitle', 'en', 'Crystal clear sea and millenary history'],
        ['hero-description', 'en', 'Immerse yourself in the beauty of Calabria, with its dream beaches, medieval villages and breathtaking panoramas.'],
        ['nav-home', 'en', 'Home'],
        ['nav-categories', 'en', 'Categories'],
        ['nav-provinces', 'en', 'Provinces'],
        ['nav-map', 'en', 'Map'],
        ['discover-calabria-btn', 'en', 'Discover Calabria'],
        ['view-map-btn', 'en', 'View Map'],
        ['search-btn', 'en', 'Search'],
        ['explore', 'en', 'Explore'],
        ['search-what', 'en', 'What are you looking for?'],
        ['search-placeholder', 'en', 'Enter what you want to explore'],
        ['all-provinces', 'en', 'All provinces'],
        
        // Traduzioni francese
        ['hero-title', 'fr', 'Découvrez la Calabre'],
        ['hero-subtitle', 'fr', 'Mer cristalline et histoire millénaire'],
        ['nav-home', 'fr', 'Accueil'],
        ['nav-categories', 'fr', 'Catégories'],
        ['nav-provinces', 'fr', 'Provinces'],
        ['discover-calabria-btn', 'fr', 'Découvrez la Calabre'],
        ['search-btn', 'fr', 'Rechercher'],
        ['explore', 'fr', 'Explorer'],
        
        // Traduzioni tedesco
        ['hero-title', 'de', 'Entdecken Sie Kalabrien'],
        ['hero-subtitle', 'de', 'Kristallklares Meer und jahrtausendealte Geschichte'],
        ['nav-home', 'de', 'Startseite'],
        ['nav-categories', 'de', 'Kategorien'],
        ['discover-calabria-btn', 'de', 'Entdecken Sie Kalabrien'],
        ['search-btn', 'de', 'Suchen'],
        
        // Traduzioni spagnolo
        ['hero-title', 'es', 'Descubre Calabria'],
        ['hero-subtitle', 'es', 'Mar cristalino e historia milenaria'],
        ['nav-home', 'es', 'Inicio'],
        ['nav-categories', 'es', 'Categorías'],
        ['discover-calabria-btn', 'es', 'Descubre Calabria'],
        ['search-btn', 'es', 'Buscar']
    ];
    
    echo "🌐 Inserimento traduzioni di esempio...\n";
    
    $translationStmt = $pdo->prepare("
        INSERT OR REPLACE INTO static_content_translations (static_content_id, language_code, translated_content) 
        SELECT sc.id, ?, ? FROM static_content sc WHERE sc.content_key = ?
    ");
    
    $translationsInserted = 0;
    foreach ($exampleTranslations as $translation) {
        list($contentKey, $langCode, $translatedText) = $translation;
        if ($translationStmt->execute([$langCode, $translatedText, $contentKey])) {
            $translationsInserted++;
            echo "✅ $contentKey ($langCode): \"$translatedText\"\n";
        }
    }
    
    echo "\n📈 TRADUZIONI INSERITE: $translationsInserted\n";
    
    // Statistiche finali
    $countStmt = $pdo->query("SELECT COUNT(*) FROM static_content");
    $totalStatic = $countStmt->fetchColumn();
    
    $countTransStmt = $pdo->query("SELECT COUNT(*) FROM static_content_translations");
    $totalTranslations = $countTransStmt->fetchColumn();
    
    echo "\n🎉 COMPLETATO CON SUCCESSO!\n";
    echo "=====================================\n";
    echo "📝 Contenuti statici totali: $totalStatic\n";
    echo "🌐 Traduzioni disponibili: $totalTranslations\n";
    echo "🔧 Sistema: PRONTO PER L'USO\n";
    echo "🌍 Test URL: http://localhost:8080/index-temp.php\n";
    echo "⚙️  Admin URL: http://localhost:8080/admin/api-config.php\n";
    echo "=====================================\n\n";
    
    echo "PROSSIMI PASSI:\n";
    echo "1. Configura una API key nel pannello admin\n";
    echo "2. Visita il sito per testare il rilevamento lingua\n";
    echo "3. Le traduzioni verranno caricate automaticamente\n\n";
    
} catch (Exception $e) {
    echo "❌ ERRORE: " . $e->getMessage() . "\n";
    exit(1);
}
?>