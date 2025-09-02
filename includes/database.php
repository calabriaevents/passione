<?php
class Database {
    public $pdo;
    private $dbPath;

    public function __construct() {
        $this->dbPath = DB_PATH;

        try {
            // Assicurati che la directory del database esista
            $dbDir = dirname($this->dbPath);
            if (!is_dir($dbDir)) {
                mkdir($dbDir, 0755, true);
            }

            // Verifica permessi di scrittura
            if (!is_writable($dbDir)) {
                throw new Exception("Directory database non scrivibile: $dbDir");
            }

            $this->pdo = new PDO('sqlite:' . $this->dbPath);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

            // Abilita chiavi esterne
            $this->pdo->exec('PRAGMA foreign_keys = ON');

            // Inizializza database se necessario
            $this->initDatabase();
        } catch (PDOException $e) {
            die('Errore connessione database PDO: ' . $e->getMessage());
        } catch (Exception $e) {
            die('Errore setup database: ' . $e->getMessage());
        }
    }

    public function initDatabase() {
        try {
            // Tabella impostazioni sistema
            $this->pdo->exec("
                CREATE TABLE IF NOT EXISTS settings (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    key TEXT NOT NULL UNIQUE,
                    value TEXT,
                    type TEXT DEFAULT 'text',
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
                )
            ");

            // Tabella caricamenti foto utenti
            $this->pdo->exec("
                CREATE TABLE IF NOT EXISTS user_uploads (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    article_id INTEGER,
                    user_name TEXT NOT NULL,
                    user_email TEXT NOT NULL,
                    image_path TEXT NOT NULL,
                    original_filename TEXT,
                    description TEXT,
                    status TEXT DEFAULT 'pending',
                    admin_notes TEXT,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (article_id) REFERENCES articles (id) ON DELETE CASCADE
                )
            ");

            // Tabella servizi/pacchetti business
            $this->pdo->exec("
                CREATE TABLE IF NOT EXISTS business_packages (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    name TEXT NOT NULL,
                    description TEXT,
                    price REAL NOT NULL,
                    duration_months INTEGER DEFAULT 12,
                    features TEXT,
                    is_active BOOLEAN DEFAULT 1,
                    sort_order INTEGER DEFAULT 0,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                )
            ");

            // Tabella sottoscrizioni
            $this->pdo->exec("
                CREATE TABLE IF NOT EXISTS subscriptions (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    business_id INTEGER NOT NULL,
                    package_id INTEGER NOT NULL,
                    stripe_subscription_id TEXT,
                    status TEXT DEFAULT 'pending',
                    start_date DATETIME,
                    end_date DATETIME,
                    amount REAL,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (business_id) REFERENCES businesses (id) ON DELETE CASCADE,
                    FOREIGN KEY (package_id) REFERENCES business_packages (id) ON DELETE CASCADE
                )
            ");

            // Tabella gestione home sections
            $this->pdo->exec("
                CREATE TABLE IF NOT EXISTS home_sections (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    section_name TEXT NOT NULL UNIQUE,
                    title TEXT,
                    subtitle TEXT,
                    description TEXT,
                    image_path TEXT,
                    is_visible BOOLEAN DEFAULT 1,
                    sort_order INTEGER DEFAULT 0,
                    custom_data TEXT,
                    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
                )
            ");

            // Tabella categorie
            $this->pdo->exec("
                CREATE TABLE IF NOT EXISTS categories (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    name TEXT NOT NULL UNIQUE,
                    description TEXT,
                    icon TEXT,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                )
            ");

            // Tabella province
            $this->pdo->exec("
                CREATE TABLE IF NOT EXISTS provinces (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    name TEXT NOT NULL UNIQUE,
                    description TEXT,
                    image_path TEXT,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                )
            ");
            
            // Aggiorna tabella province esistente per aggiungere image_path se non esiste
            // Solo per database esistenti che non hanno ancora la colonna
            try {
                $this->pdo->exec("SELECT image_path FROM provinces LIMIT 1");
            } catch (PDOException $e) {
                // La colonna non esiste, aggiungiamola
                if (strpos($e->getMessage(), 'no such column') !== false) {
                    $this->pdo->exec("ALTER TABLE provinces ADD COLUMN image_path TEXT DEFAULT NULL");
                }
            }

            // Tabella città
            $this->pdo->exec("
                CREATE TABLE IF NOT EXISTS cities (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    name TEXT NOT NULL,
                    province_id INTEGER NOT NULL,
                    latitude REAL,
                    longitude REAL,
                    description TEXT,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (province_id) REFERENCES provinces (id) ON DELETE CASCADE
                )
            ");

            // Tabella articoli
            $this->pdo->exec("
                CREATE TABLE IF NOT EXISTS articles (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    title TEXT NOT NULL,
                    slug TEXT NOT NULL UNIQUE,
                    content TEXT NOT NULL,
                    excerpt TEXT,
                    featured_image TEXT,
                    gallery_images TEXT,
                    category_id INTEGER NOT NULL,
                    province_id INTEGER,
                    city_id INTEGER,
                    author TEXT,
                    status TEXT DEFAULT 'published',
                    featured BOOLEAN DEFAULT 0,
                    views INTEGER DEFAULT 0,
                    latitude REAL,
                    longitude REAL,
                    allow_user_uploads BOOLEAN DEFAULT 1,
                    seo_title TEXT,
                    seo_description TEXT,
                    seo_keywords TEXT,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (category_id) REFERENCES categories (id) ON DELETE CASCADE,
                    FOREIGN KEY (province_id) REFERENCES provinces (id) ON DELETE SET NULL,
                    FOREIGN KEY (city_id) REFERENCES cities (id) ON DELETE SET NULL
                )
            ");

            // Aggiungi campi SEO ai database esistenti se non esistono
            try {
                $this->pdo->exec("SELECT seo_title FROM articles LIMIT 1");
            } catch (PDOException $e) {
                if (strpos($e->getMessage(), 'no such column') !== false) {
                    $this->pdo->exec("ALTER TABLE articles ADD COLUMN seo_title TEXT DEFAULT NULL");
                    $this->pdo->exec("ALTER TABLE articles ADD COLUMN seo_description TEXT DEFAULT NULL");
                    $this->pdo->exec("ALTER TABLE articles ADD COLUMN seo_keywords TEXT DEFAULT NULL");
                }
            }

            // Tabella utenti
            $this->pdo->exec("
                CREATE TABLE IF NOT EXISTS users (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    email TEXT NOT NULL UNIQUE,
                    password TEXT NOT NULL,
                    first_name TEXT,
                    last_name TEXT,
                    name TEXT NOT NULL,
                    role TEXT DEFAULT 'user',
                    status TEXT DEFAULT 'active',
                    avatar TEXT,
                    last_login DATETIME,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                )
            ");

            // Tabella business/aziende
            $this->pdo->exec("
                CREATE TABLE IF NOT EXISTS businesses (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    name TEXT NOT NULL,
                    email TEXT NOT NULL,
                    phone TEXT,
                    website TEXT,
                    description TEXT,
                    category_id INTEGER,
                    province_id INTEGER,
                    city_id INTEGER,
                    address TEXT,
                    latitude REAL,
                    longitude REAL,
                    status TEXT DEFAULT 'pending',
                    subscription_type TEXT DEFAULT 'free',
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (category_id) REFERENCES categories (id) ON DELETE SET NULL,
                    FOREIGN KEY (province_id) REFERENCES provinces (id) ON DELETE SET NULL,
                    FOREIGN KEY (city_id) REFERENCES cities (id) ON DELETE SET NULL
                )
            ");

            // Tabella eventi
            $this->pdo->exec("
                CREATE TABLE IF NOT EXISTS events (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    title TEXT NOT NULL,
                    description TEXT,
                    start_date DATETIME NOT NULL,
                    end_date DATETIME,
                    location TEXT,
                    category_id INTEGER,
                    province_id INTEGER,
                    city_id INTEGER,
                    organizer TEXT,
                    contact_email TEXT,
                    contact_phone TEXT,
                    website TEXT,
                    featured_image TEXT,
                    price REAL DEFAULT 0,
                    latitude REAL,
                    longitude REAL,
                    status TEXT DEFAULT 'pending',
                    source TEXT DEFAULT 'admin',
                    business_id INTEGER,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (category_id) REFERENCES categories (id) ON DELETE SET NULL,
                    FOREIGN KEY (province_id) REFERENCES provinces (id) ON DELETE SET NULL,
                    FOREIGN KEY (city_id) REFERENCES cities (id) ON DELETE SET NULL,
                    FOREIGN KEY (business_id) REFERENCES businesses (id) ON DELETE SET NULL
                )
            ");

            // Tabella commenti
            $this->pdo->exec("
                CREATE TABLE IF NOT EXISTS comments (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    article_id INTEGER NOT NULL,
                    user_id INTEGER,
                    author_name TEXT NOT NULL,
                    author_email TEXT NOT NULL,
                    content TEXT NOT NULL,
                    rating INTEGER CHECK (rating >= 1 AND rating <= 5),
                    status TEXT DEFAULT 'pending',
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (article_id) REFERENCES articles (id) ON DELETE CASCADE,
                    FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL
                )
            ");

            // Tabella suggerimenti luoghi
            $this->pdo->exec("
                CREATE TABLE IF NOT EXISTS place_suggestions (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    name TEXT NOT NULL,
                    description TEXT,
                    category_id INTEGER,
                    province_id INTEGER,
                    city_id INTEGER,
                    address TEXT,
                    latitude REAL,
                    longitude REAL,
                    suggested_by_name TEXT NOT NULL,
                    suggested_by_email TEXT NOT NULL,
                    images TEXT,
                    status TEXT DEFAULT 'pending',
                    admin_notes TEXT,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (category_id) REFERENCES categories (id) ON DELETE SET NULL,
                    FOREIGN KEY (province_id) REFERENCES provinces (id) ON DELETE SET NULL,
                    FOREIGN KEY (city_id) REFERENCES cities (id) ON DELETE SET NULL
                )
            ");

            // Tabella pagine statiche
            $this->pdo->exec("
                CREATE TABLE IF NOT EXISTS static_pages (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    slug TEXT NOT NULL UNIQUE,
                    title TEXT NOT NULL,
                    content TEXT NOT NULL,
                    meta_title TEXT,
                    meta_description TEXT,
                    is_published BOOLEAN DEFAULT 1,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
                )
            ");

            // Tabella gallerie province
            $this->pdo->exec("
                CREATE TABLE IF NOT EXISTS province_gallery (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    province_id INTEGER NOT NULL,
                    image_path TEXT NOT NULL,
                    title TEXT NOT NULL,
                    description TEXT,
                    sort_order INTEGER DEFAULT 0,
                    is_approved BOOLEAN DEFAULT 1,
                    uploaded_by TEXT DEFAULT 'admin',
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (province_id) REFERENCES provinces (id) ON DELETE CASCADE
                )
            ");

            // Tabella per definire campi specifici per ogni categoria
            $this->pdo->exec("
                CREATE TABLE IF NOT EXISTS category_fields (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    category_id INTEGER NOT NULL,
                    field_name TEXT NOT NULL,
                    field_label TEXT NOT NULL,
                    field_type TEXT NOT NULL DEFAULT 'text',
                    field_options TEXT,
                    is_required BOOLEAN DEFAULT 0,
                    sort_order INTEGER DEFAULT 0,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (category_id) REFERENCES categories (id) ON DELETE CASCADE
                )
            ");

            // Tabella per i valori dei campi categoria per ogni articolo
            $this->pdo->exec("
                CREATE TABLE IF NOT EXISTS article_category_data (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    article_id INTEGER NOT NULL,
                    field_name TEXT NOT NULL,
                    field_value TEXT,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (article_id) REFERENCES articles (id) ON DELETE CASCADE
                )
            ");

            $this->populateDefaultData();

        } catch (PDOException $e) {
            die('Errore inizializzazione database: ' . $e->getMessage());
        }
    }

    private function populateDefaultData() {
        // Controlla se dati già esistono
        $stmt = $this->pdo->prepare('SELECT COUNT(*) as count FROM settings');
        $stmt->execute();
        $existingSettings = $stmt->fetch()['count'];

        $stmt = $this->pdo->prepare('SELECT COUNT(*) as count FROM categories');
        $stmt->execute();
        $existingCategories = $stmt->fetch()['count'];

        if ($existingSettings == 0) {
            // Inserimento impostazioni di default
            $defaultSettings = [
                // API Keys e sicurezza
                ['google_recaptcha_v2_site_key', '', 'text'],
                ['google_recaptcha_v2_secret_key', '', 'password'],
                ['google_recaptcha_v3_site_key', '', 'text'],
                ['google_recaptcha_v3_secret_key', '', 'password'],
                ['stripe_publishable_key', '', 'text'],
                ['stripe_secret_key', '', 'password'],
                ['google_analytics_id', '', 'text'],
                
                // App Store e download
                ['app_store_link', '', 'url'],
                ['app_store_image', '', 'text'],
                ['play_store_link', '', 'url'],
                ['play_store_image', '', 'text'],
                ['vai_app_link', '', 'url'],
                ['suggerisci_evento_link', 'suggerisci-evento.php', 'url'],
                
                // Hero section
                ['hero_title', 'Esplora la Calabria', 'text'],
                ['hero_subtitle', 'Mare cristallino e storia millenaria', 'text'],
                ['hero_description', 'Immergiti nella bellezza della Calabria, con le sue spiagge da sogno, il centro storico affascinante e i panorami mozzafiato dalla rupe.', 'textarea'],
                ['hero_image', '/placeholder-hero.jpg', 'text'],
                
                // Eventi section
                ['events_title', 'Eventi e App', 'text'],
                ['events_description', 'Scarica la nostra app per rimanere sempre aggiornato sugli eventi in Calabria.', 'textarea'],
                
                // Categorie section
                ['categories_title', 'Esplora per Categoria', 'text'],
                ['categories_description', 'Scopri la Calabria attraverso le sue diverse sfaccettature: dalla natura incontaminata alla ricca tradizione culturale.', 'textarea'],
                ['categories_button_text', 'Vedi Tutte le Categorie', 'text'],
                
                // Province section
                ['provinces_title', 'Esplora le Province', 'text'],
                ['provinces_description', 'Ogni provincia calabrese custodisce tesori unici: dalla costa tirrenica a quella ionica, dai monti della Sila all\'Aspromonte.', 'textarea'],
                
                // Mappa section
                ['map_title', 'Esplora la Mappa Interattiva', 'text'],
                ['map_description', 'Naviga attraverso la Calabria con la nostra mappa interattiva. Scopri luoghi, eventi e punti d\'interesse in tempo reale.', 'textarea'],
                ['map_full_link_text', 'Visualizza mappa completa', 'text'],
                
                // CTA section
                ['cta_title', 'Vuoi far Conoscere la Tua Calabria?', 'text'],
                ['cta_description', 'Unisciti alla nostra community! Condividi i tuoi luoghi del cuore, le tue tradizioni e le tue storie.', 'textarea'],
                ['cta_button1_text', 'Collabora con Noi', 'text'],
                ['cta_button1_link', 'collabora.php', 'url'],
                ['cta_button2_text', 'Suggerisci un Luogo', 'text'],
                ['cta_button2_link', 'suggerisci.php', 'url'],
                
                // Newsletter section
                ['newsletter_title', 'Resta Connesso con la Calabria', 'text'],
                ['newsletter_description', 'Iscriviti alla nostra newsletter per ricevere i migliori contenuti e non perdere mai gli eventi più interessanti della regione.', 'textarea'],
                ['newsletter_placeholder', 'Inserisci la tua email', 'text'],
                ['newsletter_button', 'Iscriviti Gratis', 'text'],
                ['newsletter_privacy', 'Rispettiamo la tua privacy. Niente spam, solo contenuti di qualità.', 'text'],
                ['newsletter_form_action', 'api/newsletter.php', 'url'],
                
                // Social Media
                ['social_follow_text', 'Seguici sui social media', 'text'],
                ['social_facebook', '', 'url'],
                ['social_instagram', '', 'url'],
                ['social_twitter', '', 'url'],
                ['social_youtube', '', 'url']
            ];

            $stmt = $this->pdo->prepare('INSERT INTO settings (key, value, type) VALUES (?, ?, ?)');
            foreach ($defaultSettings as $setting) {
                $stmt->execute($setting);
            }
        }

        if ($existingCategories == 0) {
            // Sezioni home di default
            $defaultHomeSections = [
                ['hero', 'Esplora la Calabria', 'Mare cristallino e storia millenaria', 'Immergiti nella bellezza della Calabria', '/placeholder-hero.jpg', 1, 1],
                ['categories', 'Esplora per Categoria', '', 'Scopri la Calabria attraverso le sue diverse sfaccettature', '', 1, 2],
                ['provinces', 'Esplora le Province', '', 'Ogni provincia calabrese custodisce tesori unici', '', 1, 3],
                ['map', 'Mappa Interattiva', '', 'Naviga attraverso la Calabria con la nostra mappa interattiva', '', 1, 4],
                ['cta', 'Vuoi far Conoscere la Tua Calabria?', '', 'Unisciti alla nostra community!', '', 1, 5],
                ['newsletter', 'Resta Connesso con la Calabria', '', 'Iscriviti alla nostra newsletter', '', 1, 6]
            ];

            $stmt = $this->pdo->prepare('INSERT INTO home_sections (section_name, title, subtitle, description, image_path, is_visible, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?)');
            foreach ($defaultHomeSections as $section) {
                $stmt->execute($section);
            }

            // Categorie di default
            $defaultCategories = [
                ['Natura e Paesaggi', 'Scopri la bellezza naturale della Calabria', '🌿'],
                ['Storia e Cultura', 'Immergiti nella ricca storia calabrese', '🏛️'],
                ['Gastronomia', 'Assapora i sapori autentici della tradizione', '🍝'],
                ['Mare e Coste', 'Le più belle spiagge e località balneari', '🏖️'],
                ['Montagne e Escursioni', 'Avventure tra i monti calabresi', '⛰️'],
                ['Borghi e Tradizioni', 'Alla scoperta dei borghi più belli', '🏘️'],
                ['Arte e Musei', 'Tesori artistici e culturali', '🎨'],
                ['Feste e Eventi', 'Celebrazioni e manifestazioni locali', '🎭'],
                ['Artigianato', 'Mestieri e prodotti della tradizione', '🛠️'],
                ['Terme e Benessere', 'Relax e cure naturali', '♨️'],
                ['Parchi e Riserve', 'Aree protette e natura incontaminata', '🌲'],
                ['Architettura Religiosa', 'Chiese, monasteri e luoghi sacri', '⛪'],
                ['Archeologia', 'Siti archeologici e antiche testimonianze', '🏺'],
                ['Sport e Avventura', 'Attività sportive e outdoor', '🚴'],
                ['Enogastronomia', 'Vini e prodotti tipici locali', '🍷'],
                ['Fotografia', 'I luoghi più fotogenici della regione', '📸'],
                ['Musica e Spettacoli', 'Eventi culturali e artistici', '🎵'],
                ['Famiglia e Bambini', 'Attività e luoghi per famiglie', '👨‍👩‍👧‍👦']
            ];

            $stmt = $this->pdo->prepare('INSERT INTO categories (name, description, icon) VALUES (?, ?, ?)');
            foreach ($defaultCategories as $category) {
                $stmt->execute($category);
            }

            // Province di default
            $defaultProvinces = [
                ['Catanzaro', 'Capoluogo di regione, cuore della Calabria tra due mari'],
                ['Cosenza', 'La provincia più estesa, ricca di storia e natura'],
                ['Crotone', 'Terra di Pitagora, tra mare cristallino e archeologia'],
                ['Reggio Calabria', 'La punta dello stivale, affacciata sullo Stretto di Messina'],
                ['Vibo Valentia', 'Piccola provincia ricca di tradizioni marinare e gastronomiche']
            ];

            $stmt = $this->pdo->prepare('INSERT INTO provinces (name, description) VALUES (?, ?)');
            foreach ($defaultProvinces as $province) {
                $stmt->execute($province);
            }

            // Città principali di default
            $defaultCities = [
                ['Catanzaro', 1, 38.9098, 16.5969, 'Capoluogo di regione'],
                ['Lamezia Terme', 1, 38.9648, 16.3129, 'Importante centro della piana'],
                ['Soverato', 1, 38.6918, 16.5513, 'Perla dello Ionio'],
                ['Cosenza', 2, 39.2948, 16.2542, 'Città dei Bruzi'],
                ['Rossano', 2, 39.5761, 16.6314, 'Città della liquirizia'],
                ['Paola', 2, 39.3656, 16.0378, 'Città di San Francesco'],
                ['Scalea', 2, 39.8147, 15.7939, 'Riviera dei Cedri'],
                ['Crotone', 3, 39.0847, 17.1252, 'Antica Kroton'],
                ['Cirò Marina', 3, 39.3726, 17.1283, 'Terra del vino Cirò'],
                ['Reggio Calabria', 4, 38.1098, 15.6516, 'Città dei Bronzi'],
                ['Tropea', 5, 38.6773, 15.8976, 'Perla del Tirreno'],
                ['Vibo Valentia', 5, 38.6759, 16.1018, 'Antica Hipponion'],
                ['Pizzo', 5, 38.7347, 16.1569, 'Città del tartufo']
            ];

            $stmt = $this->pdo->prepare('INSERT INTO cities (name, province_id, latitude, longitude, description) VALUES (?, ?, ?, ?, ?)');
            foreach ($defaultCities as $city) {
                $stmt->execute($city);
            }

            // Pacchetti business di default
            $defaultPackages = [
                ['Gratuito', 'Inserimento base della tua attività', 0, 12, '["Scheda attività base","Contatti e orari","Visibilità nella ricerca"]', 1, 1],
                ['Business', 'Pacchetto completo per la tua attività', 29.99, 12, '["Tutto del piano Gratuito","Foto illimitate","Descrizione estesa","Badge verificato","Statistiche visualizzazioni"]', 1, 2],
                ['Premium', 'Massima visibilità e funzionalità avanzate', 59.99, 12, '["Tutto del piano Business","Posizione privilegiata","Articoli sponsorizzati","Analytics avanzate","Supporto prioritario","Eventi promozionali"]', 1, 3]
            ];

            $stmt = $this->pdo->prepare('INSERT INTO business_packages (name, description, price, duration_months, features, is_active, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?)');
            foreach ($defaultPackages as $package) {
                $stmt->execute($package);
            }

            // Pagine statiche di default
            $defaultStaticPages = [
                ['chi-siamo', 'Chi Siamo', '<h1>Chi Siamo</h1><p>Benvenuti in Passione Calabria, il portale dedicato alla scoperta di una delle regioni più affascinanti d\'Italia.</p>', 'Chi Siamo - Passione Calabria', 'Scopri chi siamo e la nostra missione per promuovere la bellezza e le tradizioni della Calabria.', 1],
                ['privacy-policy', 'Privacy Policy', '<h1>Privacy Policy</h1><p>Questa privacy policy descrive come raccogliamo, utilizziamo e proteggiamo le tue informazioni personali.</p>', 'Privacy Policy - Passione Calabria', 'La nostra politica sulla privacy e protezione dei dati personali.', 1],
                ['termini-servizio', 'Termini di Servizio', '<h1>Termini di Servizio</h1><p>Questi termini e condizioni governano il tuo uso del nostro sito web e servizi.</p>', 'Termini di Servizio - Passione Calabria', 'I termini e condizioni per l\'utilizzo del nostro sito web e servizi.', 1],
                ['contatti', 'Contatti', '<h1>Contatti</h1><p>Siamo sempre felici di sentire da voi! Ecco come potete raggiungerci:</p>', 'Contatti - Passione Calabria', 'Come contattarci per informazioni, collaborazioni o segnalazioni.', 1],
                ['cookie-policy', 'Cookie Policy', '<h1>Cookie Policy</h1><p>Questo sito utilizza cookies per migliorare la tua esperienza di navigazione.</p>', 'Cookie Policy - Passione Calabria', 'La nostra politica sui cookies e come li utilizziamo.', 1]
            ];

            $stmt = $this->pdo->prepare('INSERT INTO static_pages (slug, title, content, meta_title, meta_description, is_published) VALUES (?, ?, ?, ?, ?, ?)');
            foreach ($defaultStaticPages as $page) {
                $stmt->execute($page);
            }

            // Articoli di esempio
            $this->populateSampleArticles();
            
            // Campi specifici per categoria
            $this->populateCategoryFields();
        }
    }

    private function populateSampleArticles() {
        $sampleArticles = [
            [
                'title' => 'La Sila: Il Cuore Verde della Calabria',
                'slug' => 'la-sila-il-cuore-verde-della-calabria',
                'content' => 'L\'Altopiano della Sila rappresenta uno dei tesori naturalistici più preziosi della Calabria. Con i suoi 150.000 ettari di territorio, questo polmone verde offre paesaggi mozzafiato, laghi cristallini e una biodiversità unica.',
                'excerpt' => 'Scopri l\'Altopiano della Sila, polmone verde della Calabria con laghi cristallini e foreste secolari.',
                'category_id' => 1,
                'province_id' => 2,
                'author' => 'Marco Rossi',
                'featured' => 1,
                'latitude' => 39.3000,
                'longitude' => 16.5000
            ],
            [
                'title' => 'I Bronzi di Riace: Capolavori della Magna Grecia',
                'slug' => 'i-bronzi-di-riace-capolavori-della-magna-grecia',
                'content' => 'I Bronzi di Riace sono due statue di bronzo di epoca greca classica, rinvenute nel 1972 nei fondali marini antistanti Riace Marina. Questi capolavori dell\'arte antica, datati al V secolo a.C., rappresentano due guerrieri in posizione eretta.',
                'excerpt' => 'I celebri Bronzi di Riace, capolavori della scultura greca del V secolo a.C.',
                'category_id' => 2,
                'province_id' => 4,
                'city_id' => 10,
                'author' => 'Elena Greco',
                'featured' => 1,
                'latitude' => 38.1113,
                'longitude' => 15.6442
            ],
            [
                'title' => 'La \'Nduja: Il Piccante Orgoglio di Spilinga',
                'slug' => 'la-nduja-il-piccante-orgoglio-di-spilinga',
                'content' => 'La \'nduja è un salume piccante spalmabile originario di Spilinga, piccolo borgo in provincia di Vibo Valentia. Questa prelibatezza, ottenuta da carni suine e peperoncino calabrese, rappresenta l\'essenza della tradizione gastronomica locale.',
                'excerpt' => 'La \'nduja di Spilinga, salume piccante simbolo della gastronomia calabrese.',
                'category_id' => 3,
                'province_id' => 5,
                'author' => 'Giuseppe Calabrese',
                'featured' => 1,
                'latitude' => 38.6500,
                'longitude' => 15.9000
            ],
            [
                'title' => 'Tropea: La Perla del Tirreno',
                'slug' => 'tropea-la-perla-del-tirreno',
                'content' => 'Tropea è universalmente riconosciuta come una delle località balneari più belle d\'Italia. Arroccata su un promontorio a strapiombo sul mare, offre uno dei panorami più suggestivi della Calabria.',
                'excerpt' => 'Tropea, perla del Tirreno con spiagge da sogno e centro storico mozzafiato.',
                'category_id' => 4,
                'province_id' => 4,
                'city_id' => 11,
                'author' => 'Maria Costantino',
                'featured' => 1,
                'latitude' => 38.6773,
                'longitude' => 15.8984
            ],
            [
                'title' => 'Gerace: Il Borgo Medievale di Pietra',
                'slug' => 'gerace-il-borgo-medievale-di-pietra',
                'content' => 'Gerace è uno dei borghi medievali più belli e meglio conservati della Calabria. Arroccato su una rupe a 500 metri di altitudine, domina la vallata del fiume Novito e offre panorami spettacolari sulla costa ionica.',
                'excerpt' => 'Gerace, borgo medievale arroccato su una rupe con tesori architettonici unici.',
                'category_id' => 6,
                'province_id' => 4,
                'city_id' => 12,
                'author' => 'Antonio Meduri',
                'featured' => 0,
                'latitude' => 38.2709,
                'longitude' => 16.2198
            ]
        ];

        $stmt = $this->pdo->prepare('
            INSERT INTO articles (title, slug, content, excerpt, category_id, province_id, city_id, author, featured, latitude, longitude, allow_user_uploads)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)
        ');

        foreach ($sampleArticles as $article) {
            $stmt->execute([
                $article['title'],
                $article['slug'],
                $article['content'],
                $article['excerpt'],
                $article['category_id'],
                $article['province_id'],
                $article['city_id'] ?? null,
                $article['author'],
                $article['featured'],
                $article['latitude'] ?? null,
                $article['longitude'] ?? null
            ]);
        }
    }

    private function populateCategoryFields() {
        // Controlla se i campi categoria sono già stati popolati
        $stmt = $this->pdo->prepare('SELECT COUNT(*) as count FROM category_fields');
        $stmt->execute();
        $existingFields = $stmt->fetch()['count'];
        
        if ($existingFields > 0) {
            return; // Già popolati
        }

        // Mappa delle categorie con i loro ID (assumendo l'ordine di inserimento)
        $categoryFields = [
            // 1. Alloggio
            1 => [
                ['stelle', 'Stelle (1-5)', 'select', '1,2,3,4,5', true, 1],
                ['servizi', 'Servizi Offerti', 'textarea', null, false, 2],
                ['prezzi', 'Fascia di Prezzo', 'select', 'economico,medio,alto,lusso', false, 3],
                ['checkin', 'Orario Check-in', 'text', null, false, 4],
                ['checkout', 'Orario Check-out', 'text', null, false, 5],
                ['contatto_booking', 'Contatto Prenotazioni', 'text', null, false, 6]
            ],
            // 2. Arte e Cultura  
            2 => [
                ['artista', 'Artista/Autore', 'text', null, false, 1],
                ['periodo', 'Periodo Storico', 'text', null, false, 2],
                ['stile', 'Stile Artistico', 'text', null, false, 3],
                ['orari_visita', 'Orari di Visita', 'textarea', null, false, 4],
                ['biglietti', 'Informazioni Biglietti', 'textarea', null, false, 5]
            ],
            // 3. Attività Sportive e Avventura
            3 => [
                ['tipo_sport', 'Tipo di Sport', 'text', null, true, 1],
                ['difficolta', 'Livello di Difficoltà', 'select', 'principiante,intermedio,avanzato,esperto', false, 2],
                ['durata', 'Durata Attività', 'text', null, false, 3],
                ['attrezzatura', 'Attrezzatura Necessaria', 'textarea', null, false, 4],
                ['stagione', 'Stagione Consigliata', 'text', null, false, 5],
                ['costo', 'Costo Indicativo', 'text', null, false, 6]
            ],
            // 4. Benessere e Relax
            4 => [
                ['trattamenti', 'Trattamenti Disponibili', 'textarea', null, false, 1],
                ['prezzi_trattamenti', 'Listino Prezzi', 'textarea', null, false, 2],
                ['orari_apertura', 'Orari di Apertura', 'textarea', null, false, 3],
                ['prenotazioni', 'Come Prenotare', 'text', null, false, 4],
                ['strutture', 'Strutture Disponibili', 'textarea', null, false, 5]
            ],
            // 5. Chiese e Santuari
            5 => [
                ['periodo_costruzione', 'Periodo di Costruzione', 'text', null, false, 1],
                ['stile_architettonico', 'Stile Architettonico', 'text', null, false, 2],
                ['orari_messe', 'Orari delle Messe', 'textarea', null, false, 3],
                ['festa_patrono', 'Festa del Patrono', 'text', null, false, 4],
                ['opere_arte', 'Opere d\'Arte Principali', 'textarea', null, false, 5]
            ],
            // 6. Gastronomia
            6 => [
                ['specialita', 'Specialità Principali', 'textarea', null, true, 1],
                ['ingredienti', 'Ingredienti Tipici', 'textarea', null, false, 2],
                ['ricetta', 'Ricetta Tradizionale', 'textarea', null, false, 3],
                ['dove_trovarlo', 'Dove Assaggiarlo', 'textarea', null, false, 4],
                ['storia', 'Storia del Piatto', 'textarea', null, false, 5]
            ],
            // 7. Itinerari Tematici
            7 => [
                ['durata_itinerario', 'Durata Consigliata', 'text', null, true, 1],
                ['difficolta', 'Difficoltà', 'select', 'facile,medio,difficile', false, 2],
                ['punti_interesse', 'Punti di Interesse', 'textarea', null, false, 3],
                ['stagione_migliore', 'Stagione Migliore', 'text', null, false, 4],
                ['cosa_portare', 'Cosa Portare', 'textarea', null, false, 5]
            ],
            // 8. Musei e Gallerie
            8 => [
                ['collezione', 'Collezione Principale', 'textarea', null, false, 1],
                ['orari_apertura', 'Orari di Apertura', 'textarea', null, true, 2],
                ['prezzi_biglietti', 'Prezzi Biglietti', 'textarea', null, false, 3],
                ['mostre_temporanee', 'Mostre Temporanee', 'textarea', null, false, 4],
                ['visite_guidate', 'Visite Guidate', 'text', null, false, 5]
            ],
            // 9. Parchi e Aree Verdi
            9 => [
                ['superficie', 'Superficie', 'text', null, false, 1],
                ['fauna', 'Fauna Principale', 'textarea', null, false, 2],
                ['flora', 'Flora Caratteristica', 'textarea', null, false, 3],
                ['sentieri', 'Sentieri e Percorsi', 'textarea', null, false, 4],
                ['servizi_park', 'Servizi del Parco', 'textarea', null, false, 5]
            ],
            // 10. Patrimonio Storico
            10 => [
                ['epoca_storica', 'Epoca Storica', 'text', null, false, 1],
                ['stile_architettonico', 'Stile Architettonico', 'text', null, false, 2],
                ['restauri', 'Restauri Principali', 'textarea', null, false, 3],
                ['visite_guidate', 'Visite Guidate', 'text', null, false, 4],
                ['importanza_storica', 'Importanza Storica', 'textarea', null, false, 5]
            ],
            // 11. Piazze e Vie Storiche
            11 => [
                ['epoca_costruzione', 'Epoca di Costruzione', 'text', null, false, 1],
                ['eventi_storici', 'Eventi Storici', 'textarea', null, false, 2],
                ['monumenti_presenti', 'Monumenti Presenti', 'textarea', null, false, 3],
                ['mercati_eventi', 'Mercati e Eventi', 'textarea', null, false, 4],
                ['negozi_storici', 'Negozi Storici', 'textarea', null, false, 5]
            ],
            // 12. Ristorazione
            12 => [
                ['tipo_cucina', 'Tipo di Cucina', 'text', null, true, 1],
                ['fascia_prezzo', 'Fascia di Prezzo', 'select', 'economica,media,alta,gourmet', false, 2],
                ['orari_apertura', 'Orari di Apertura', 'textarea', null, false, 3],
                ['specialita_casa', 'Specialità della Casa', 'textarea', null, false, 4],
                ['prenotazioni', 'Prenotazioni', 'text', null, false, 5],
                ['posti_disponibili', 'Numero Posti', 'text', null, false, 6]
            ],
            // 13. Shopping e Artigianato
            13 => [
                ['prodotti_tipici', 'Prodotti Tipici', 'textarea', null, true, 1],
                ['artigiani_locali', 'Artigiani Locali', 'textarea', null, false, 2],
                ['orari_apertura', 'Orari di Apertura', 'textarea', null, false, 3],
                ['prezzi_medi', 'Prezzi Medi', 'textarea', null, false, 4],
                ['tecniche_lavorazione', 'Tecniche di Lavorazione', 'textarea', null, false, 5]
            ],
            // 14. Siti Archeologici
            14 => [
                ['epoca_storica', 'Epoca Storica', 'text', null, false, 1],
                ['civilta', 'Civiltà', 'text', null, false, 2],
                ['scavi_recenti', 'Scavi Recenti', 'textarea', null, false, 3],
                ['reperti_importanti', 'Reperti Importanti', 'textarea', null, false, 4],
                ['visite_guidate', 'Visite Guidate', 'text', null, false, 5]
            ],
            // 15. Spiagge
            15 => [
                ['tipo_spiaggia', 'Tipo di Spiaggia', 'select', 'sabbia,ghiaia,scoglio,mista', false, 1],
                ['servizi_balneari', 'Servizi Balneari', 'textarea', null, false, 2],
                ['accesso_mare', 'Tipo di Accesso', 'text', null, false, 3],
                ['bandiera_blu', 'Bandiera Blu', 'select', 'si,no', false, 4],
                ['sport_acquatici', 'Sport Acquatici', 'textarea', null, false, 5],
                ['parcheggio', 'Parcheggio Disponibile', 'select', 'gratuito,pagamento,non_disponibile', false, 6]
            ],
            // 16. Stabilimenti Balneari
            16 => [
                ['servizi_stabilimento', 'Servizi dello Stabilimento', 'textarea', null, true, 1],
                ['prezzi_ombrelloni', 'Prezzi Ombrelloni/Lettini', 'textarea', null, false, 2],
                ['ristorante_bar', 'Ristorante/Bar', 'select', 'si,no', false, 3],
                ['animazione', 'Animazione', 'select', 'si,no', false, 4],
                ['sport_spiaggia', 'Sport in Spiaggia', 'textarea', null, false, 5],
                ['parcheggio_privato', 'Parcheggio Privato', 'select', 'si,no', false, 6]
            ],
            // 17. Teatri e Anfiteatri
            17 => [
                ['capienza_teatro', 'Capienza', 'text', null, false, 1],
                ['programmazione', 'Programmazione', 'textarea', null, false, 2],
                ['prezzi_biglietti', 'Prezzi Biglietti', 'textarea', null, false, 3],
                ['storia_teatro', 'Storia del Teatro', 'textarea', null, false, 4],
                ['architetto', 'Architetto', 'text', null, false, 5]
            ],
            // 18. Tour e Guide
            18 => [
                ['durata_tour', 'Durata del Tour', 'text', null, true, 1],
                ['prezzo_tour', 'Prezzo del Tour', 'text', null, false, 2],
                ['lingue_disponibili', 'Lingue Disponibili', 'text', null, false, 3],
                ['gruppo_max', 'Gruppo Massimo', 'text', null, false, 4],
                ['prenotazioni_tour', 'Come Prenotare', 'text', null, false, 5],
                ['punto_ritrovo', 'Punto di Ritrovo', 'text', null, false, 6]
            ]
        ];

        // Inserisce i campi per ogni categoria
        $stmt = $this->pdo->prepare('
            INSERT INTO category_fields (category_id, field_name, field_label, field_type, field_options, is_required, sort_order)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ');

        foreach ($categoryFields as $categoryId => $fields) {
            foreach ($fields as $field) {
                $stmt->execute([
                    $categoryId,
                    $field[0], // field_name
                    $field[1], // field_label
                    $field[2], // field_type
                    $field[3], // field_options
                    $field[4] ? 1 : 0, // is_required
                    $field[5]  // sort_order
                ]);
            }
        }
    }

    // Metodi per categorie
    public function getCategories() {
        $stmt = $this->pdo->prepare('SELECT * FROM categories ORDER BY name');
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getCategoryById($id) {
        $stmt = $this->pdo->prepare('SELECT * FROM categories WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function createCategory($name, $description, $icon) {
        $stmt = $this->pdo->prepare('INSERT INTO categories (name, description, icon) VALUES (?, ?, ?)');
        return $stmt->execute([$name, $description, $icon]);
    }

    public function updateCategory($id, $name, $description, $icon) {
        $stmt = $this->pdo->prepare('UPDATE categories SET name = ?, description = ?, icon = ? WHERE id = ?');
        return $stmt->execute([$name, $description, $icon, $id]);
    }

    public function deleteCategory($id) {
        // Prima controlla se ci sono articoli associati a questa categoria
        $stmt = $this->pdo->prepare('SELECT COUNT(*) as count FROM articles WHERE category_id = ?');
        $stmt->execute([$id]);
        $count = $stmt->fetch()['count'];
        
        if ($count > 0) {
            return false; // Non eliminare se ci sono articoli associati
        }
        
        $stmt = $this->pdo->prepare('DELETE FROM categories WHERE id = ?');
        return $stmt->execute([$id]);
    }

    // Metodi per province
    public function getProvinces() {
        $stmt = $this->pdo->prepare('SELECT * FROM provinces ORDER BY name');
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getProvinceById($id) {
        $stmt = $this->pdo->prepare('SELECT * FROM provinces WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    public function createProvince($name, $description, $image_path = null) {
        $stmt = $this->pdo->prepare('INSERT INTO provinces (name, description, image_path) VALUES (?, ?, ?)');
        return $stmt->execute([$name, $description, $image_path]);
    }
    
    public function updateProvince($id, $name, $description, $image_path = null) {
        if ($image_path !== null) {
            $stmt = $this->pdo->prepare('UPDATE provinces SET name = ?, description = ?, image_path = ? WHERE id = ?');
            return $stmt->execute([$name, $description, $image_path, $id]);
        } else {
            $stmt = $this->pdo->prepare('UPDATE provinces SET name = ?, description = ? WHERE id = ?');
            return $stmt->execute([$name, $description, $id]);
        }
    }
    
    public function deleteProvince($id) {
        // Prima elimina l'immagine se esiste
        $province = $this->getProvinceById($id);
        if ($province && !empty($province['image_path'])) {
            $imagePath = '../' . $province['image_path'];
            if (file_exists($imagePath)) {
                unlink($imagePath);
            }
        }
        
        $stmt = $this->pdo->prepare('DELETE FROM provinces WHERE id = ?');
        return $stmt->execute([$id]);
    }

    // Metodi per gallerie province
    public function addProvinceGalleryImage($province_id, $image_path, $title, $description = '') {
        $stmt = $this->pdo->prepare('
            INSERT INTO province_gallery (province_id, image_path, title, description, uploaded_by) 
            VALUES (?, ?, ?, ?, ?)
        ');
        return $stmt->execute([$province_id, $image_path, $title, $description, 'admin']);
    }

    public function getProvinceGalleryImages($province_id) {
        $stmt = $this->pdo->prepare('
            SELECT * FROM province_gallery 
            WHERE province_id = ? AND is_approved = 1 
            ORDER BY sort_order ASC, created_at DESC
        ');
        $stmt->execute([$province_id]);
        return $stmt->fetchAll();
    }

    public function deleteProvinceGalleryImage($id) {
        // Prima recupera l'immagine per eliminarla dal filesystem
        $stmt = $this->pdo->prepare('SELECT image_path FROM province_gallery WHERE id = ?');
        $stmt->execute([$id]);
        $image = $stmt->fetch();
        
        if ($image) {
            $imagePath = '../' . $image['image_path'];
            if (file_exists($imagePath)) {
                unlink($imagePath);
            }
            
            // Poi elimina il record dal database
            $stmt = $this->pdo->prepare('DELETE FROM province_gallery WHERE id = ?');
            return $stmt->execute([$id]);
        }
        
        return false;
    }

    public function updateProvinceGalleryImage($id, $title, $description = '') {
        $stmt = $this->pdo->prepare('
            UPDATE province_gallery 
            SET title = ?, description = ? 
            WHERE id = ?
        ');
        return $stmt->execute([$title, $description, $id]);
    }

    // Metodi per città
    public function getCities() {
        $stmt = $this->pdo->prepare('
            SELECT c.*, p.name as province_name
            FROM cities c
            LEFT JOIN provinces p ON c.province_id = p.id
            ORDER BY c.name
        ');
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getCitiesByProvince($provinceId) {
        $stmt = $this->pdo->prepare('SELECT * FROM cities WHERE province_id = ? ORDER BY name');
        $stmt->execute([$provinceId]);
        return $stmt->fetchAll();
    }

    public function getCitiesFiltered($provinceId = null, $searchQuery = null) {
        $sql = '
            SELECT c.*, p.name as province_name
            FROM cities c
            LEFT JOIN provinces p ON c.province_id = p.id
            WHERE 1=1
        ';
        $params = [];

        if ($provinceId) {
            $sql .= ' AND c.province_id = ?';
            $params[] = $provinceId;
        }

        if ($searchQuery) {
            $sql .= ' AND (c.name LIKE ? OR c.description LIKE ?)';
            $params[] = "%$searchQuery%";
            $params[] = "%$searchQuery%";
        }

        $sql .= ' ORDER BY c.name';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getCityById($id) {
        $stmt = $this->pdo->prepare('
            SELECT c.*, p.name as province_name, p.id as province_id
            FROM cities c
            LEFT JOIN provinces p ON c.province_id = p.id
            WHERE c.id = ?
        ');
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function getArticleCountByCity($cityId) {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) as count FROM articles WHERE city_id = ? AND status = ?');
        $stmt->execute([$cityId, 'published']);
        return $stmt->fetch()['count'];
    }

    public function getArticlesByCity($cityId, $limit = null) {
        $sql = '
            SELECT a.*, c.name as category_name, p.name as province_name
            FROM articles a
            LEFT JOIN categories c ON a.category_id = c.id
            LEFT JOIN provinces p ON a.province_id = p.id
            WHERE a.city_id = ? AND a.status = ?
            ORDER BY a.created_at DESC
        ';

        if ($limit) {
            $sql .= ' LIMIT ' . (int)$limit;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$cityId, 'published']);
        return $stmt->fetchAll();
    }

    // CRUD methods for cities
    public function createCity($name, $province_id, $description = '', $latitude = null, $longitude = null) {
        $stmt = $this->pdo->prepare('
            INSERT INTO cities (name, province_id, description, latitude, longitude, created_at) 
            VALUES (?, ?, ?, ?, ?, CURRENT_TIMESTAMP)
        ');
        return $stmt->execute([$name, $province_id, $description, $latitude, $longitude]);
    }

    public function updateCity($id, $name, $province_id, $description = '', $latitude = null, $longitude = null) {
        $stmt = $this->pdo->prepare('
            UPDATE cities 
            SET name = ?, province_id = ?, description = ?, latitude = ?, longitude = ? 
            WHERE id = ?
        ');
        return $stmt->execute([$name, $province_id, $description, $latitude, $longitude, $id]);
    }

    public function deleteCity($id) {
        // Prima controlla se ci sono articoli collegati
        $stmt = $this->pdo->prepare('SELECT COUNT(*) as count FROM articles WHERE city_id = ?');
        $stmt->execute([$id]);
        $articleCount = $stmt->fetch()['count'];
        
        if ($articleCount > 0) {
            // Se ci sono articoli, non eliminare ma segnalare l'errore
            return false;
        }
        
        $stmt = $this->pdo->prepare('DELETE FROM cities WHERE id = ?');
        return $stmt->execute([$id]);
    }

    // Metodi per articoli
    public function getArticles($limit = null, $offset = 0) {
        $sql = '
            SELECT a.*, c.name as category_name, p.name as province_name, ci.name as city_name
            FROM articles a
            LEFT JOIN categories c ON a.category_id = c.id
            LEFT JOIN provinces p ON a.province_id = p.id
            LEFT JOIN cities ci ON a.city_id = ci.id
            WHERE a.status = ?
            ORDER BY a.created_at DESC
        ';

        if ($limit) {
            $sql .= ' LIMIT ' . (int)$limit . ' OFFSET ' . (int)$offset;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['published']);
        return $stmt->fetchAll();
    }

    public function getFeaturedArticles($limit = 6) {
        $stmt = $this->pdo->prepare('
            SELECT a.*, c.name as category_name
            FROM articles a
            LEFT JOIN categories c ON a.category_id = c.id
            WHERE a.featured = 1 AND a.status = ?
            ORDER BY a.views DESC
            LIMIT ?
        ');
        $stmt->execute(['published', $limit]);
        return $stmt->fetchAll();
    }

    public function getArticlesByCategory($categoryId, $limit = null) {
        $sql = '
            SELECT a.*, c.name as category_name, p.name as province_name
            FROM articles a
            LEFT JOIN categories c ON a.category_id = c.id
            LEFT JOIN provinces p ON a.province_id = p.id
            WHERE a.category_id = ? AND a.status = ?
            ORDER BY a.created_at DESC
        ';

        if ($limit) {
            $sql .= ' LIMIT ' . (int)$limit;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$categoryId, 'published']);
        return $stmt->fetchAll();
    }

    public function getArticlesByProvince($provinceId, $limit = null) {
        $sql = '
            SELECT a.*, c.name as category_name, p.name as province_name
            FROM articles a
            LEFT JOIN categories c ON a.category_id = c.id
            LEFT JOIN provinces p ON a.province_id = p.id
            WHERE a.province_id = ? AND a.status = ?
            ORDER BY a.created_at DESC
        ';

        if ($limit) {
            $sql .= ' LIMIT ' . (int)$limit;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$provinceId, 'published']);
        return $stmt->fetchAll();
    }

    public function getArticleBySlug($slug) {
        $stmt = $this->pdo->prepare('
            SELECT a.*, c.name as category_name, p.name as province_name, ci.name as city_name
            FROM articles a
            LEFT JOIN categories c ON a.category_id = c.id
            LEFT JOIN provinces p ON a.province_id = p.id
            LEFT JOIN cities ci ON a.city_id = ci.id
            WHERE a.slug = ?
        ');
        $stmt->execute([$slug]);
        return $stmt->fetch();
    }

    public function getArticleCountByCategory($categoryId) {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) as count FROM articles WHERE category_id = ? AND status = ?');
        $stmt->execute([$categoryId, 'published']);
        return $stmt->fetch()['count'];
    }

    public function getArticleCountByProvince($provinceId) {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) as count FROM articles WHERE province_id = ? AND status = ?');
        $stmt->execute([$provinceId, 'published']);
        return $stmt->fetch()['count'];
    }

    public function searchArticles($query, $provinceId = null) {
        $sql = '
            SELECT a.*, c.name as category_name, p.name as province_name
            FROM articles a
            LEFT JOIN categories c ON a.category_id = c.id
            LEFT JOIN provinces p ON a.province_id = p.id
            WHERE (a.title LIKE ? OR a.content LIKE ? OR a.excerpt LIKE ?)
            AND a.status = ?
        ';

        $params = ["%$query%", "%$query%", "%$query%", 'published'];

        if ($provinceId) {
            $sql .= ' AND a.province_id = ?';
            $params[] = $provinceId;
        }

        $sql .= ' ORDER BY a.created_at DESC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function incrementArticleViews($id) {
        $stmt = $this->pdo->prepare('UPDATE articles SET views = views + 1 WHERE id = ?');
        $stmt->execute([$id]);
    }
    
    // CRUD methods for articles
    public function createArticle($title, $slug, $content, $excerpt, $category_id, $province_id = null, $city_id = null, $status = 'draft', $featured_image = null, $gallery_images = null, $author = 'Admin', $seo_title = null, $seo_description = null, $seo_keywords = null) {
        $stmt = $this->pdo->prepare('
            INSERT INTO articles (title, slug, content, excerpt, category_id, province_id, city_id, status, featured_image, gallery_images, author, seo_title, seo_description, seo_keywords, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
        ');
        if ($stmt->execute([$title, $slug, $content, $excerpt, $category_id, $province_id, $city_id, $status, $featured_image, $gallery_images, $author, $seo_title, $seo_description, $seo_keywords])) {
            return $this->pdo->lastInsertId();
        }
        return false;
    }
    
    public function updateArticle($id, $title, $slug, $content, $excerpt, $category_id, $province_id = null, $city_id = null, $status = 'draft', $featured_image = null, $gallery_images = null, $seo_title = null, $seo_description = null, $seo_keywords = null) {
        $stmt = $this->pdo->prepare('
            UPDATE articles SET 
                title = ?, slug = ?, content = ?, excerpt = ?, category_id = ?, province_id = ?, city_id = ?, 
                status = ?, featured_image = ?, gallery_images = ?, seo_title = ?, seo_description = ?, seo_keywords = ?, updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ');
        return $stmt->execute([$title, $slug, $content, $excerpt, $category_id, $province_id, $city_id, $status, $featured_image, $gallery_images, $seo_title, $seo_description, $seo_keywords, $id]);
    }
    
    public function getArticleById($id) {
        $stmt = $this->pdo->prepare('
            SELECT a.*, c.name as category_name, p.name as province_name, ci.name as city_name
            FROM articles a
            LEFT JOIN categories c ON a.category_id = c.id
            LEFT JOIN provinces p ON a.province_id = p.id
            LEFT JOIN cities ci ON a.city_id = ci.id
            WHERE a.id = ?
        ');
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    public function deleteArticle($id) {
        // Prima elimina le immagini associate se esistono
        $article = $this->getArticleById($id);
        if ($article) {
            if (!empty($article['featured_image'])) {
                $imagePath = '../' . $article['featured_image'];
                if (file_exists($imagePath)) {
                    unlink($imagePath);
                }
            }
            
            if (!empty($article['gallery_images'])) {
                $galleryImages = json_decode($article['gallery_images'], true);
                if (is_array($galleryImages)) {
                    foreach ($galleryImages as $imagePath) {
                        $fullPath = '../' . $imagePath;
                        if (file_exists($fullPath)) {
                            unlink($fullPath);
                        }
                    }
                }
            }
        }
        
        $stmt = $this->pdo->prepare('DELETE FROM articles WHERE id = ?');
        return $stmt->execute([$id]);
    }

    // Metodi per sezioni home
    public function getHomeSections() {
        $stmt = $this->pdo->prepare('SELECT * FROM home_sections WHERE is_visible = 1 ORDER BY sort_order');
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // Metodi per eventi
    public function getUpcomingEvents($limit = 10) {
        $stmt = $this->pdo->prepare('
            SELECT e.*, c.name as category_name, p.name as province_name
            FROM events e
            LEFT JOIN categories c ON e.category_id = c.id
            LEFT JOIN provinces p ON e.province_id = p.id
            WHERE e.start_date >= datetime("now") AND e.status = ?
            ORDER BY e.start_date ASC
            LIMIT ?
        ');
        $stmt->execute(['active', $limit]);
        return $stmt->fetchAll();
    }

    // Metodi per business
    public function getBusinesses($limit = null, $includeAll = true) {
        $whereClause = $includeAll ? '' : 'WHERE b.status = ?';
        $sql = "
            SELECT b.*, c.name as category_name, p.name as province_name, ci.name as city_name
            FROM businesses b
            LEFT JOIN categories c ON b.category_id = c.id
            LEFT JOIN provinces p ON b.province_id = p.id
            LEFT JOIN cities ci ON b.city_id = ci.id
            $whereClause
            ORDER BY b.name
        ";

        if ($limit) {
            $sql .= ' LIMIT ' . (int)$limit;
        }

        $stmt = $this->pdo->prepare($sql);
        if ($includeAll) {
            $stmt->execute();
        } else {
            $stmt->execute(['approved']);
        }
        return $stmt->fetchAll();
    }

    public function getBusinessById($id) {
        $stmt = $this->pdo->prepare("
            SELECT b.*, c.name as category_name, p.name as province_name, ci.name as city_name
            FROM businesses b
            LEFT JOIN categories c ON b.category_id = c.id
            LEFT JOIN provinces p ON b.province_id = p.id
            LEFT JOIN cities ci ON b.city_id = ci.id
            WHERE b.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function createBusiness($name, $email, $phone, $website, $description, $category_id, $province_id, $city_id, $address, $status) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO businesses (name, email, phone, website, description, category_id, province_id, city_id, address, status, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, datetime('now'))
            ");
            return $stmt->execute([
                $name, $email, $phone, $website, $description,
                $category_id ?: null, $province_id ?: null, $city_id ?: null,
                $address, $status
            ]);
        } catch (Exception $e) {
            error_log("Error creating business: " . $e->getMessage());
            return false;
        }
    }

    public function updateBusiness($id, $name, $email, $phone, $website, $description, $category_id, $province_id, $city_id, $address, $status) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE businesses 
                SET name = ?, email = ?, phone = ?, website = ?, description = ?, 
                    category_id = ?, province_id = ?, city_id = ?, address = ?, status = ?,
                    updated_at = datetime('now')
                WHERE id = ?
            ");
            return $stmt->execute([
                $name, $email, $phone, $website, $description,
                $category_id ?: null, $province_id ?: null, $city_id ?: null,
                $address, $status, $id
            ]);
        } catch (Exception $e) {
            error_log("Error updating business: " . $e->getMessage());
            return false;
        }
    }

    public function deleteBusiness($id) {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM businesses WHERE id = ?");
            return $stmt->execute([$id]);
        } catch (Exception $e) {
            error_log("Error deleting business: " . $e->getMessage());
            return false;
        }
    }

    // Metodi per impostazioni
    public function getSetting($key) {
        $stmt = $this->pdo->prepare('SELECT value FROM settings WHERE key = ?');
        $stmt->execute([$key]);
        $result = $stmt->fetch();
        return $result ? $result['value'] : null;
    }

    public function setSetting($key, $value, $type = 'text') {
        $stmt = $this->pdo->prepare('INSERT OR REPLACE INTO settings (key, value, type, updated_at) VALUES (?, ?, ?, CURRENT_TIMESTAMP)');
        $stmt->execute([$key, $value, $type]);
    }

    public function getSettings() {
        $stmt = $this->pdo->prepare('SELECT * FROM settings ORDER BY key');
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // Metodi per statistiche database
    public function getDatabaseHealth() {
        $dbPath = $this->dbPath;
        $dbSize = file_exists($dbPath) ? filesize($dbPath) : 0;

        // Conteggi tabelle
        $tables = ['articles', 'categories', 'provinces', 'cities', 'comments', 'users', 'businesses', 'events', 'user_uploads', 'business_packages', 'settings', 'home_sections', 'static_pages'];
        $counts = [];

        foreach ($tables as $table) {
            $stmt = $this->pdo->prepare("SELECT COUNT(*) as count FROM $table");
            $stmt->execute();
            $counts[$table] = $stmt->fetch()['count'];
        }

        // Statistiche articoli
        $stmt = $this->pdo->prepare('SELECT COUNT(*) as count FROM articles WHERE featured = 1');
        $stmt->execute();
        $featuredArticles = $stmt->fetch()['count'];

        $stmt = $this->pdo->prepare('SELECT COUNT(*) as count FROM articles WHERE status = ?');
        $stmt->execute(['published']);
        $publishedArticles = $stmt->fetch()['count'];

        $stmt = $this->pdo->prepare('SELECT SUM(views) as total FROM articles');
        $stmt->execute();
        $totalViews = $stmt->fetch()['total'] ?: 0;

        // Controlli integrità
        try {
            $stmt = $this->pdo->prepare('PRAGMA integrity_check');
            $stmt->execute();
            $integrityCheck = $stmt->fetch();
            $integrityOk = $integrityCheck['integrity_check'] === 'ok';
        } catch (Exception $e) {
            $integrityOk = false;
        }

        return [
            'database' => [
                'path' => $dbPath,
                'size' => number_format($dbSize / (1024 * 1024), 2) . ' MB',
                'sizeBytes' => $dbSize,
                'lastModified' => date('c', filemtime($dbPath))
            ],
            'counts' => $counts,
            'statistics' => [
                'articles' => [
                    'total' => $counts['articles'],
                    'published' => $publishedArticles,
                    'featured' => $featuredArticles,
                    'totalViews' => $totalViews
                ]
            ],
            'health' => [
                'checks' => [
                    'databaseAccessible' => true,
                    'integrityOk' => $integrityOk,
                    'hasCategories' => $counts['categories'] > 0,
                    'hasProvinces' => $counts['provinces'] > 0,
                    'hasCities' => $counts['cities'] > 0
                ]
            ]
        ];
    }

    // Backup del database
    public function createBackup() {
        $backupDir = dirname($this->dbPath) . '/backups';
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        $timestamp = date('Y-m-d_H-i-s');
        $backupFile = $backupDir . "/passione_calabria_backup_$timestamp.db";

        if (copy($this->dbPath, $backupFile)) {
            return $backupFile;
        }

        return false;
    }

    public function getBackups() {
        $backupDir = dirname($this->dbPath) . '/backups';
        if (!is_dir($backupDir)) {
            return [];
        }

        $backups = [];
        $files = glob($backupDir . '/*.db');

        foreach ($files as $file) {
            $backups[] = [
                'filename' => basename($file),
                'size' => filesize($file),
                'created' => date('c', filemtime($file)),
                'sizeFormatted' => number_format(filesize($file) / (1024 * 1024), 2) . ' MB'
            ];
        }

        // Ordina per data decrescente
        usort($backups, function($a, $b) {
            return strtotime($b['created']) - strtotime($a['created']);
        });

        return $backups;
    }

    public function deleteBackup($filename) {
        // Validazione nome file per sicurezza
        if (empty($filename) || strpos($filename, '..') !== false || !preg_match('/^[a-zA-Z0-9_\-\.]+\.db$/', $filename)) {
            return false;
        }

        $backupDir = dirname($this->dbPath) . '/backups';
        $backupFile = $backupDir . '/' . $filename;

        // Verifica che il file esista e sia nella directory backups
        if (!file_exists($backupFile) || !is_file($backupFile)) {
            return false;
        }

        // Verifica che il file sia effettivamente nella directory backups (sicurezza aggiuntiva)
        if (dirname(realpath($backupFile)) !== realpath($backupDir)) {
            return false;
        }

        // Elimina il file
        return unlink($backupFile);
    }

    // Metodo per suggerimenti luoghi
    public function createPlaceSuggestion($name, $description, $location, $suggested_by_name, $suggested_by_email) {
        $stmt = $this->pdo->prepare('
            INSERT INTO place_suggestions (name, description, address, suggested_by_name, suggested_by_email, status)
            VALUES (?, ?, ?, ?, ?, ?)
        ');
        return $stmt->execute([$name, $description, $location, $suggested_by_name, $suggested_by_email, 'pending']);
    }

    // Metodo per ottenere i suggerimenti luoghi (per admin)
    public function getPlaceSuggestions($status = null) {
        $sql = 'SELECT * FROM place_suggestions';
        $params = [];
        
        if ($status) {
            $sql .= ' WHERE status = ?';
            $params[] = $status;
        }
        
        $sql .= ' ORDER BY created_at DESC';
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    // Metodo per suggerimenti eventi
    public function createEventSuggestion($title, $description, $start_date, $end_date, $location, $category_id, $province_id, $organizer, $contact_email, $contact_phone = null, $website = null, $price = 0) {
        $stmt = $this->pdo->prepare('
            INSERT INTO events (title, description, start_date, end_date, location, category_id, province_id, organizer, contact_email, contact_phone, website, price, status, source)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ');
        
        return $stmt->execute([
            $title, 
            $description, 
            $start_date, 
            $end_date ?: null, 
            $location, 
            $category_id ?: null, 
            $province_id ?: null, 
            $organizer, 
            $contact_email, 
            $contact_phone, 
            $website, 
            $price, 
            'pending', 
            'suggestion'
        ]);
    }

    // Metodo per ottenere i suggerimenti eventi (per admin)
    public function getEventSuggestions($status = null) {
        $sql = '
            SELECT e.*, c.name as category_name, p.name as province_name
            FROM events e
            LEFT JOIN categories c ON e.category_id = c.id
            LEFT JOIN provinces p ON e.province_id = p.id
            WHERE e.source = ?
        ';
        $params = ['suggestion'];
        
        if ($status) {
            $sql .= ' AND e.status = ?';
            $params[] = $status;
        }
        
        $sql .= ' ORDER BY e.created_at DESC';
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    // Metodi per gestire sezioni home
    public function updateHomeSection($sectionName, $data) {
        $stmt = $this->pdo->prepare('
            INSERT OR REPLACE INTO home_sections (section_name, title, subtitle, description, image_path, custom_data, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)
        ');
        
        return $stmt->execute([
            $sectionName,
            $data['title'] ?? '',
            $data['subtitle'] ?? '',
            $data['description'] ?? '',
            $data['image_path'] ?? '',
            $data['custom_data'] ?? ''
        ]);
    }

    // Metodi per campi dinamici categorie
    public function getCategoryFields($categoryId) {
        $stmt = $this->pdo->prepare('
            SELECT * FROM category_fields 
            WHERE category_id = ? 
            ORDER BY sort_order ASC, field_label ASC
        ');
        $stmt->execute([$categoryId]);
        return $stmt->fetchAll();
    }

    public function createCategoryField($categoryId, $fieldName, $fieldLabel, $fieldType, $fieldOptions = null, $isRequired = false, $sortOrder = 0) {
        $stmt = $this->pdo->prepare('
            INSERT INTO category_fields (category_id, field_name, field_label, field_type, field_options, is_required, sort_order)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ');
        return $stmt->execute([$categoryId, $fieldName, $fieldLabel, $fieldType, $fieldOptions, $isRequired ? 1 : 0, $sortOrder]);
    }

    /**
     * Recupera i dati categoria per un articolo con processing intelligente
     */
    public function getArticleCategoryData($articleId) {
        $stmt = $this->pdo->prepare('
            SELECT acd.field_name, acd.field_value, cf.field_type
            FROM article_category_data acd
            LEFT JOIN category_fields cf ON acd.field_name = cf.id
            WHERE acd.article_id = ?
        ');
        $stmt->execute([$articleId]);
        $results = $stmt->fetchAll();
        
        // Converte in array associativo con processing per tipo campo
        $data = [];
        foreach ($results as $row) {
            $fieldId = $row['field_name'];
            $fieldValue = $row['field_value'];
            $fieldType = $row['field_type'];
            
            // Processa il valore in base al tipo
            switch ($fieldType) {
                case 'checkbox':
                    // Converte stringa separata da virgole in array per l'interfaccia
                    $data[$fieldId] = !empty($fieldValue) ? explode(',', $fieldValue) : [];
                    break;
                case 'number':
                    // Converte in numero se possibile
                    $data[$fieldId] = is_numeric($fieldValue) ? (float)$fieldValue : $fieldValue;
                    break;
                default:
                    $data[$fieldId] = $fieldValue;
            }
        }
        return $data;
    }

    /**
     * Salva i dati specifici della categoria per un articolo
     * Gestisce tutti i tipi di campo inclusi checkbox multipli, file, ecc.
     */
    public function saveArticleCategoryData($articleId, $categoryId, $categoryData) {
        try {
            // Inizia transazione per sicurezza
            $this->pdo->beginTransaction();
            
            // Prima elimina i dati esistenti per questo articolo
            $stmt = $this->pdo->prepare('DELETE FROM article_category_data WHERE article_id = ?');
            $stmt->execute([$articleId]);
            
            // Recupera i metadati dei campi per la categoria
            $categoryFields = $this->getCategoryFields($categoryId);
            $fieldMap = [];
            foreach ($categoryFields as $field) {
                $fieldMap[$field['id']] = $field;
            }
            
            // Prepara statement per inserimento
            $stmt = $this->pdo->prepare('
                INSERT INTO article_category_data (article_id, field_name, field_value, created_at, updated_at)
                VALUES (?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
            ');
            
            foreach ($categoryData as $fieldId => $fieldValue) {
                // Ottiene info sul campo
                $fieldInfo = $fieldMap[$fieldId] ?? null;
                if (!$fieldInfo) {
                    continue; // Salta campi non validi
                }
                
                // Processa il valore in base al tipo di campo
                $processedValue = $this->processFieldValue($fieldValue, $fieldInfo['field_type']);
                
                // Salva solo valori non vuoti (ma permette 0 come valore valido)
                if ($processedValue !== '' && $processedValue !== null) {
                    $stmt->execute([$articleId, $fieldId, $processedValue]);
                }
            }
            
            // Commit transazione
            $this->pdo->commit();
            return true;
            
        } catch (Exception $e) {
            // Rollback in caso di errore
            $this->pdo->rollBack();
            error_log('Errore salvataggio dati categoria: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Processa il valore del campo in base al tipo
     */
    private function processFieldValue($value, $fieldType) {
        switch ($fieldType) {
            case 'checkbox':
                // Per checkbox multipli, il valore può essere un array
                if (is_array($value)) {
                    // Filtra valori vuoti e converte in stringa separata da virgole
                    $filteredValues = array_filter($value, function($v) { return !empty(trim($v)); });
                    return implode(',', $filteredValues);
                } else if (is_string($value)) {
                    // Se è già una stringa, pulisce e ritorna
                    $cleanValues = array_filter(explode(',', $value), function($v) { return !empty(trim($v)); });
                    return implode(',', $cleanValues);
                }
                return $value;
                
            case 'number':
                // Assicura che sia un numero valido
                return is_numeric($value) ? (string)$value : '';
                
            case 'datetime-local':
                // Valida formato data
                if (DateTime::createFromFormat('Y-m-d\TH:i', $value)) {
                    return $value;
                }
                return '';
                
            case 'email':
                // Valida email
                return filter_var($value, FILTER_VALIDATE_EMAIL) ? $value : '';
                
            case 'url':
                // Valida URL
                return filter_var($value, FILTER_VALIDATE_URL) ? $value : '';
                
            case 'file':
                // Per i file, il valore dovrebbe essere già il path del file caricato
                return is_string($value) ? $value : '';
                
            case 'text':
            case 'textarea':
            case 'select':
            default:
                // Sanitizza HTML di base e ritorna
                return is_string($value) ? strip_tags($value, '<b><i><u><strong><em><br><p>') : (string)$value;
        }
    }

    // Metodi per gestire errori duplicati con messaggi user-friendly
    public function categoryExists($name, $excludeId = null) {
        $sql = 'SELECT COUNT(*) as count FROM categories WHERE LOWER(name) = LOWER(?)';
        $params = [trim($name)];
        
        if ($excludeId) {
            $sql .= ' AND id != ?';
            $params[] = $excludeId;
        }
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch()['count'] > 0;
    }

    public function provinceExists($name, $excludeId = null) {
        $sql = 'SELECT COUNT(*) as count FROM provinces WHERE LOWER(name) = LOWER(?)';
        $params = [trim($name)];
        
        if ($excludeId) {
            $sql .= ' AND id != ?';
            $params[] = $excludeId;
        }
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch()['count'] > 0;
    }

    public function cityExists($name, $provinceId, $excludeId = null) {
        $sql = 'SELECT COUNT(*) as count FROM cities WHERE LOWER(name) = LOWER(?) AND province_id = ?';
        $params = [trim($name), $provinceId];
        
        if ($excludeId) {
            $sql .= ' AND id != ?';
            $params[] = $excludeId;
        }
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch()['count'] > 0;
    }

    public function articleSlugExists($slug, $excludeId = null) {
        $sql = 'SELECT COUNT(*) as count FROM articles WHERE slug = ?';
        $params = [$slug];
        
        if ($excludeId) {
            $sql .= ' AND id != ?';
            $params[] = $excludeId;
        }
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch()['count'] > 0;
    }

    /**
     * NUOVI METODI PER RIPRISTINO E BACKUP COMPLETO
     */

    /**
     * Ripristina il database da un backup
     */
    public function restoreFromBackup($backupFilename) {
        try {
            // Validazione nome file per sicurezza
            if (empty($backupFilename) || strpos($backupFilename, '..') !== false || !preg_match('/^[a-zA-Z0-9_\-\.]+\.db$/', $backupFilename)) {
                return ['success' => false, 'message' => 'Nome file backup non valido'];
            }

            $backupDir = dirname($this->dbPath) . '/backups';
            $backupFilePath = $backupDir . '/' . $backupFilename;

            // Verifica che il backup esista
            if (!file_exists($backupFilePath) || !is_file($backupFilePath)) {
                return ['success' => false, 'message' => 'File di backup non trovato'];
            }

            // Crea backup di sicurezza del database corrente
            $safetyBackup = $this->createEmergencyBackup();
            if (!$safetyBackup) {
                return ['success' => false, 'message' => 'Impossibile creare backup di sicurezza'];
            }

            // Chiudi la connessione corrente
            $this->pdo = null;

            // Sostituisci il database corrente con il backup
            if (!copy($backupFilePath, $this->dbPath)) {
                return ['success' => false, 'message' => 'Errore nella copia del file di backup'];
            }

            // Riconnetti al database ripristinato
            $this->pdo = new PDO('sqlite:' . $this->dbPath);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $this->pdo->exec('PRAGMA foreign_keys = ON');

            // Verifica integrità del database ripristinato
            $integrityCheck = $this->pdo->query('PRAGMA integrity_check')->fetch();
            if ($integrityCheck['integrity_check'] !== 'ok') {
                // Se il backup è corrotto, ripristina il database originale
                copy($safetyBackup, $this->dbPath);
                $this->__construct(); // Riconnetti
                unlink($safetyBackup);
                return ['success' => false, 'message' => 'Il backup è corrotto. Database originale ripristinato.'];
            }

            // Rimuovi backup di sicurezza se tutto è andato bene
            unlink($safetyBackup);

            return ['success' => true, 'message' => "Database ripristinato con successo da $backupFilename"];

        } catch (Exception $e) {
            // In caso di errore, cerca di ripristinare il backup di sicurezza
            if (isset($safetyBackup) && file_exists($safetyBackup)) {
                copy($safetyBackup, $this->dbPath);
                unlink($safetyBackup);
                try {
                    $this->__construct(); // Riconnetti
                } catch (Exception $reconnectError) {
                    // Log dell'errore ma non lanciare eccezione
                    error_log('Errore riconnessione database: ' . $reconnectError->getMessage());
                }
            }
            return ['success' => false, 'message' => 'Errore durante il ripristino: ' . $e->getMessage()];
        }
    }

    /**
     * Crea un backup di emergenza prima del ripristino
     */
    private function createEmergencyBackup() {
        $timestamp = date('Y-m-d_H-i-s');
        $emergencyBackupPath = dirname($this->dbPath) . "/emergency_backup_$timestamp.db";
        
        if (copy($this->dbPath, $emergencyBackupPath)) {
            return $emergencyBackupPath;
        }
        
        return false;
    }

    /**
     * Gestisce l'upload di un file di backup
     */
    public function handleBackupUpload($uploadedFile) {
        try {
            // Verifica errori upload
            if ($uploadedFile['error'] !== UPLOAD_ERR_OK) {
                return ['success' => false, 'message' => 'Errore durante l\'upload del file'];
            }

            // Verifica dimensione file (max 100MB)
            $maxSize = 100 * 1024 * 1024; // 100MB
            if ($uploadedFile['size'] > $maxSize) {
                return ['success' => false, 'message' => 'File troppo grande (max 100MB)'];
            }

            // Verifica estensione
            $fileName = $uploadedFile['name'];
            if (!preg_match('/\.db$/i', $fileName)) {
                return ['success' => false, 'message' => 'Il file deve avere estensione .db'];
            }

            // Sanitizza nome file
            $sanitizedName = 'uploaded_backup_' . date('Y-m-d_H-i-s') . '.db';
            $backupDir = dirname($this->dbPath) . '/backups';
            
            // Crea directory backup se non esiste
            if (!is_dir($backupDir)) {
                mkdir($backupDir, 0755, true);
            }

            $destinationPath = $backupDir . '/' . $sanitizedName;

            // Sposta file temporaneo nella directory backup
            if (!move_uploaded_file($uploadedFile['tmp_name'], $destinationPath)) {
                return ['success' => false, 'message' => 'Errore nel salvataggio del file'];
            }

            // Verifica che sia un database SQLite valido
            try {
                $testPdo = new PDO('sqlite:' . $destinationPath);
                $testPdo->query('SELECT name FROM sqlite_master WHERE type="table" LIMIT 1');
                $testPdo = null; // Chiudi connessione test
            } catch (Exception $e) {
                unlink($destinationPath); // Rimuovi file non valido
                return ['success' => false, 'message' => 'Il file caricato non è un database SQLite valido'];
            }

            return [
                'success' => true, 
                'message' => 'File di backup caricato con successo',
                'filename' => $sanitizedName
            ];

        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Errore durante l\'upload: ' . $e->getMessage()];
        }
    }

    /**
     * Crea un backup completo del progetto (database + file importanti)
     */
    public function createFullProjectBackup() {
        try {
            $timestamp = date('Y-m-d_H-i-s');
            $backupName = "passione_calabria_full_backup_$timestamp";
            $tempDir = sys_get_temp_dir() . '/' . $backupName;
            
            // Crea directory temporanea
            if (!mkdir($tempDir, 0755, true)) {
                return ['success' => false, 'message' => 'Impossibile creare directory temporanea'];
            }

            // Copia database
            $dbBackupPath = $tempDir . '/database.db';
            if (!copy($this->dbPath, $dbBackupPath)) {
                $this->cleanupTempDir($tempDir);
                return ['success' => false, 'message' => 'Errore nella copia del database'];
            }

            // Copia TUTTO il progetto (escludendo solo .git)
            $projectRoot = dirname($this->dbPath);
            $this->copyCompleteProject($projectRoot, $tempDir);

            // Crea file informativo
            $infoContent = "=== BACKUP COMPLETO PASSIONE CALABRIA ===\n";
            $infoContent .= "Data creazione: " . date('Y-m-d H:i:s') . "\n";
            $infoContent .= "Versione: 2.0 (Backup Completo Ottimizzato)\n\n";
            $infoContent .= "CONTENUTO DEL BACKUP:\n";
            $infoContent .= "✓ Database SQLite principale (passione_calabria.db)\n";
            $infoContent .= "✓ Database MySQL schema (database_mysql.sql)\n";
            $infoContent .= "✓ Pannello di amministrazione (admin/)\n";
            $infoContent .= "✓ API del progetto (api/)\n";
            $infoContent .= "✓ File di configurazione (includes/)\n";
            $infoContent .= "✓ Risorse statiche (assets/)\n";
            $infoContent .= "✓ File caricati e immagini (uploads/)\n";
            $infoContent .= "✓ Backup precedenti (backups/)\n";
            $infoContent .= "✓ File di log (logs/ se presenti)\n";
            $infoContent .= "✓ Tutti i file PHP del sito\n";
            $infoContent .= "✓ Tutti gli altri file del progetto\n\n";
            $infoContent .= "ISTRUZIONI PER IL RIPRISTINO:\n";
            $infoContent .= "1. Estrai TUTTI i file di questo archivio nella directory del server\n";
            $infoContent .= "2. Assicurati che il file 'passione_calabria.db' sia nella root\n";
            $infoContent .= "3. Verifica i permessi delle directory (755 per directory, 644 per file)\n";
            $infoContent .= "4. Configura il server web (Apache/Nginx) per puntare alla directory\n";
            $infoContent .= "5. Verifica che PHP abbia le estensioni SQLite e ZIP abilitate\n";
            $infoContent .= "6. Il sito dovrebbe funzionare immediatamente\n\n";
            $infoContent .= "NOTA: Questo backup include TUTTO il necessario per ripristinare\n";
            $infoContent .= "il sito completo con database, foto, articoli, commenti e configurazioni.\n";
            
            file_put_contents($tempDir . '/LEGGIMI.txt', $infoContent);

            // Crea archivio ZIP
            $zipPath = dirname($this->dbPath) . "/backups/$backupName.zip";
            if (!$this->createZipArchive($tempDir, $zipPath)) {
                $this->cleanupTempDir($tempDir);
                return ['success' => false, 'message' => 'Errore nella creazione dell\'archivio ZIP'];
            }

            // Pulisci directory temporanea
            $this->cleanupTempDir($tempDir);

            return [
                'success' => true, 
                'message' => 'Backup completo creato con successo',
                'filename' => basename($zipPath),
                'size' => filesize($zipPath)
            ];

        } catch (Exception $e) {
            if (isset($tempDir) && is_dir($tempDir)) {
                $this->cleanupTempDir($tempDir);
            }
            return ['success' => false, 'message' => 'Errore durante il backup completo: ' . $e->getMessage()];
        }
    }

    /**
     * Copia ricorsivamente una directory
     */
    private function copyDirectory($source, $destination) {
        if (!is_dir($source)) {
            return false;
        }

        if (!is_dir($destination)) {
            mkdir($destination, 0755, true);
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            $destPath = $destination . '/' . $iterator->getSubPathName();
            
            if ($item->isDir()) {
                if (!is_dir($destPath)) {
                    mkdir($destPath, 0755, true);
                }
            } else {
                copy($item, $destPath);
            }
        }

        return true;
    }

    /**
     * Copia l'intero progetto escludendo solo .git e directory temporanee
     */
    private function copyCompleteProject($source, $destination) {
        if (!is_dir($source)) {
            return false;
        }

        if (!is_dir($destination)) {
            mkdir($destination, 0755, true);
        }

        // Lista di file e directory da escludere
        $excludePatterns = [
            '.git',
            '.gitignore',
            '.DS_Store',
            'Thumbs.db',
            '.htaccess.bak',
            'error_log',
            'tmp',
            'temp'
        ];

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            $relativePath = $iterator->getSubPathName();
            $shouldExclude = false;

            // Controlla se il file/directory è da escludere
            foreach ($excludePatterns as $pattern) {
                if (strpos($relativePath, $pattern) === 0 || strpos($relativePath, '/' . $pattern) !== false) {
                    $shouldExclude = true;
                    break;
                }
            }

            // Escludi anche i backup che stiamo creando ora
            if (strpos($relativePath, 'passione_calabria_full_backup_') === 0) {
                $shouldExclude = true;
            }

            if ($shouldExclude) {
                continue;
            }

            $destPath = $destination . '/' . $relativePath;
            
            if ($item->isDir()) {
                if (!is_dir($destPath)) {
                    mkdir($destPath, 0755, true);
                }
            } else {
                // Crea la directory di destinazione se non esiste
                $destDir = dirname($destPath);
                if (!is_dir($destDir)) {
                    mkdir($destDir, 0755, true);
                }
                copy($item->getRealPath(), $destPath);
            }
        }

        return true;
    }

    /**
     * Crea un archivio ZIP da una directory
     */
    private function createZipArchive($sourceDir, $zipPath) {
        if (!class_exists('ZipArchive')) {
            return false; // Estensione ZIP non disponibile
        }

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
            return false;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($sourceDir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            $filePath = $file->getRealPath();
            $relativePath = substr($filePath, strlen($sourceDir) + 1);

            if ($file->isDir()) {
                $zip->addEmptyDir($relativePath);
            } else {
                $zip->addFile($filePath, $relativePath);
            }
        }

        $zip->close();
        return true;
    }

    /**
     * Pulisce una directory temporanea
     */
    private function cleanupTempDir($tempDir) {
        if (!is_dir($tempDir)) {
            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($tempDir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $file) {
            if ($file->isDir()) {
                rmdir($file->getRealPath());
            } else {
                unlink($file->getRealPath());
            }
        }

        rmdir($tempDir);
    }

    /**
     * Ottiene informazioni su un backup
     */
    public function getBackupInfo($filename) {
        $backupDir = dirname($this->dbPath) . '/backups';
        $backupPath = $backupDir . '/' . $filename;
        
        if (!file_exists($backupPath)) {
            return null;
        }

        $info = [
            'filename' => $filename,
            'size' => filesize($backupPath),
            'sizeFormatted' => number_format(filesize($backupPath) / (1024 * 1024), 2) . ' MB',
            'created' => date('c', filemtime($backupPath)),
            'type' => pathinfo($filename, PATHINFO_EXTENSION) === 'zip' ? 'full' : 'database'
        ];

        // Per backup database, verifica integrità
        if ($info['type'] === 'database') {
            try {
                $testPdo = new PDO('sqlite:' . $backupPath);
                $integrityCheck = $testPdo->query('PRAGMA integrity_check')->fetch();
                $info['integrity'] = $integrityCheck['integrity_check'] === 'ok' ? 'ok' : 'corrupted';
                $testPdo = null;
            } catch (Exception $e) {
                $info['integrity'] = 'corrupted';
            }
        }

        return $info;
    }

    public function searchCategories($query, $limit = 5) {
        $stmt = $this->pdo->prepare('
            SELECT * FROM categories
            WHERE name LIKE ? OR description LIKE ?
            ORDER BY name
            LIMIT ?
        ');
        $stmt->execute(["%$query%", "%$query%", $limit]);
        return $stmt->fetchAll();
    }

    public function getNewsletterSubscriber($email) {
        $stmt = $this->pdo->prepare('SELECT id, email, status FROM newsletter_subscribers WHERE email = ?');
        $stmt->execute([$email]);
        return $stmt->fetch();
    }

    /**
     * Metodi di delega per PDO (necessari per il servizio di traduzione)
     */
    public function prepare($statement, $options = []) {
        return $this->pdo->prepare($statement, $options);
    }

    public function query($statement, $mode = null, ...$fetch_mode_args) {
        if ($mode === null) {
            return $this->pdo->query($statement);
        }
        return $this->pdo->query($statement, $mode, ...$fetch_mode_args);
    }

    public function exec($statement) {
        return $this->pdo->exec($statement);
    }

    public function lastInsertId($name = null) {
        return $this->pdo->lastInsertId($name);
    }

    public function beginTransaction() {
        return $this->pdo->beginTransaction();
    }

    public function commit() {
        return $this->pdo->commit();
    }

    public function rollback() {
        return $this->pdo->rollback();
    }

    public function inTransaction() {
        return $this->pdo->inTransaction();
    }

    public function getAttribute($attribute) {
        return $this->pdo->getAttribute($attribute);
    }

    public function setAttribute($attribute, $value) {
        return $this->pdo->setAttribute($attribute, $value);
    }

    public function getArticleRatingStats($articleId) {
        try {
            $stmt = $this->prepare("
                SELECT 
                    AVG(rating) as average_rating,
                    COUNT(*) as total_ratings
                FROM comments 
                WHERE article_id = ? AND status = 'approved' AND rating > 0
            ");
            $stmt->execute([$articleId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return [
                'average_rating' => $result['average_rating'] ? round($result['average_rating'], 1) : 0,
                'total_ratings' => (int)$result['total_ratings']
            ];
        } catch (Exception $e) {
            error_log('Error getting article rating stats: ' . $e->getMessage());
            return ['average_rating' => 0, 'total_ratings' => 0];
        }
    }

    public function getArticlesWithRatings() {
        try {
            $stmt = $this->prepare("
                SELECT a.*, c.category_name,
                    AVG(com.rating) as average_rating,
                    COUNT(com.rating) as total_ratings
                FROM articles a
                LEFT JOIN categories c ON a.category_id = c.id
                LEFT JOIN comments com ON a.id = com.article_id AND com.status = 'approved' AND com.rating > 0
                WHERE a.status = 'published' 
                GROUP BY a.id
                ORDER BY a.created_at DESC
            ");
            $stmt->execute();
            $articles = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Format the rating data
            foreach ($articles as &$article) {
                $article['average_rating'] = $article['average_rating'] ? round($article['average_rating'], 1) : 0;
                $article['total_ratings'] = (int)$article['total_ratings'];
            }
            
            return $articles;
        } catch (Exception $e) {
            error_log('Error getting articles with ratings: ' . $e->getMessage());
            return $this->getArticles(); // Fallback to regular articles
        }
    }

    // ========== GESTIONE COMMENTI ADMIN ==========

    public function getComments($status = null) {
        try {
            $sql = "
                SELECT c.*, a.title as article_title
                FROM comments c
                LEFT JOIN articles a ON c.article_id = a.id
            ";
            
            if ($status) {
                $sql .= " WHERE c.status = ?"; 
                $stmt = $this->prepare($sql . " ORDER BY c.created_at DESC");
                $stmt->execute([$status]);
            } else {
                $stmt = $this->prepare($sql . " ORDER BY c.created_at DESC");
                $stmt->execute();
            }
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('Error getting comments: ' . $e->getMessage());
            return [];
        }
    }

    public function getCommentById($id) {
        try {
            $stmt = $this->prepare("SELECT * FROM comments WHERE id = ?");
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('Error getting comment by ID: ' . $e->getMessage());
            return null;
        }
    }

    public function updateCommentStatus($id, $status) {
        try {
            $stmt = $this->prepare("UPDATE comments SET status = ? WHERE id = ?");
            return $stmt->execute([$status, $id]);
        } catch (Exception $e) {
            error_log('Error updating comment status: ' . $e->getMessage());
            return false;
        }
    }

    public function updateCommentContent($id, $content) {
        try {
            $stmt = $this->prepare("UPDATE comments SET content = ? WHERE id = ?");
            return $stmt->execute([$content, $id]);
        } catch (Exception $e) {
            error_log('Error updating comment content: ' . $e->getMessage());
            return false;
        }
    }

    public function deleteComment($id) {
        try {
            $stmt = $this->prepare("DELETE FROM comments WHERE id = ?");
            return $stmt->execute([$id]);
        } catch (Exception $e) {
            error_log('Error deleting comment: ' . $e->getMessage());
            return false;
        }
    }

    // =====================================================
    // USER MANAGEMENT METHODS
    // =====================================================

    public function getUsers() {
        try {
            $stmt = $this->pdo->prepare("
                SELECT id, email, name, first_name, last_name, role, status, avatar, last_login, created_at
                FROM users 
                ORDER BY created_at DESC
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('Error getting users: ' . $e->getMessage());
            return [];
        }
    }

    public function getUserById($id) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT id, email, name, first_name, last_name, role, status, avatar, last_login, created_at
                FROM users 
                WHERE id = ?
            ");
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('Error getting user by ID: ' . $e->getMessage());
            return null;
        }
    }

    public function createUser($email, $password, $name, $role = 'user', $status = 'active') {
        try {
            // Hash the password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt = $this->pdo->prepare("
                INSERT INTO users (email, password, name, role, status, created_at) 
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            
            $result = $stmt->execute([$email, $hashedPassword, $name, $role, $status]);
            
            if ($result) {
                return $this->pdo->lastInsertId();
            }
            return false;
        } catch (Exception $e) {
            error_log('Error creating user: ' . $e->getMessage());
            return false;
        }
    }

    public function updateUser($id, $email, $password, $name, $role, $status) {
        try {
            // If password is provided and not empty, update it too
            if (!empty($password)) {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $this->pdo->prepare("
                    UPDATE users 
                    SET email = ?, password = ?, name = ?, role = ?, status = ?
                    WHERE id = ?
                ");
                $result = $stmt->execute([$email, $hashedPassword, $name, $role, $status, $id]);
            } else {
                // Update without changing password
                $stmt = $this->pdo->prepare("
                    UPDATE users 
                    SET email = ?, name = ?, role = ?, status = ?
                    WHERE id = ?
                ");
                $result = $stmt->execute([$email, $name, $role, $status, $id]);
            }
            
            return $result;
        } catch (Exception $e) {
            error_log('Error updating user: ' . $e->getMessage());
            return false;
        }
    }

    public function deleteUser($id) {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM users WHERE id = ?");
            return $stmt->execute([$id]);
        } catch (Exception $e) {
            error_log('Error deleting user: ' . $e->getMessage());
            return false;
        }
    }
}
?>
