<?php
/**
 * Servizio per Traduzione Preventiva con DeepL API
 * 
 * Versione completa del servizio di traduzione utilizzando DeepL API
 * con sistema di cache, fallback e gestione errori robusto.
 * 
 * @author Passione Calabria
 * @version 2.0 - DeepL Edition
 */

require_once __DIR__ . '/config.php';

class PreventiveTranslationService {
    
    private $db;
    private $config;
    private $supportedLanguages;
    private $defaultLanguage = 'it';
    private $fallbackLanguage = 'en';
    private $initialized = false;
    private $preventiveTranslationEnabled = false; // DISATTIVATO - Solo traduzione manuale
    
    // Mapping lingue DeepL
    private $deeplLanguageMap = [
        'it' => 'IT',
        'en' => 'EN',
        'fr' => 'FR',
        'de' => 'DE',
        'es' => 'ES',
        'pt' => 'PT',
        'ru' => 'RU',
        'pl' => 'PL',
        'nl' => 'NL'
    ];
    
    public function __construct(Database $database) {
        $this->db = $database;
        // Inizializzazione lazy per evitare timeout
    }
    
    /**
     * Inizializzazione sicura del servizio
     */
    private function initialize() {
        if ($this->initialized) {
            return true;
        }
        
        try {
            $this->loadConfigSafe();
            $this->loadSupportedLanguagesSafe();
            $this->initialized = true;
            return true;
        } catch (Exception $e) {
            error_log("Errore inizializzazione PreventiveTranslationService: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Carica configurazione API da database in modo sicuro
     */
    private function loadConfigSafe() {
        try {
            $stmt = $this->db->prepare("SELECT * FROM translation_config WHERE api_provider = 'deepl' AND is_enabled = 1 LIMIT 1");
            if (!$stmt) {
                throw new Exception("Errore preparazione query config");
            }
            
            $stmt->execute();
            $this->config = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$this->config) {
                // Configurazione di default DeepL
                $this->config = [
                    'id' => 1,
                    'api_provider' => 'deepl',
                    'api_key' => null,
                    'is_enabled' => 0,
                    'daily_quota' => 500000, // DeepL ha quota più alta in caratteri
                    'current_daily_usage' => 0,
                    'last_reset_date' => date('Y-m-d')
                ];
            }
        } catch (Exception $e) {
            error_log("Errore caricamento config DeepL: " . $e->getMessage());
            // Configurazione fallback
            $this->config = [
                'id' => 0,
                'api_provider' => 'disabled',
                'api_key' => null,
                'is_enabled' => 0,
                'daily_quota' => 0,
                'current_daily_usage' => 0,
                'last_reset_date' => date('Y-m-d')
            ];
        }
    }
    
    /**
     * Carica lingue supportate da database in modo sicuro
     */
    private function loadSupportedLanguagesSafe() {
        try {
            $stmt = $this->db->prepare("SELECT code, name, native_name, is_default, is_fallback FROM preventive_languages WHERE is_active = 1 LIMIT 10");
            if (!$stmt) {
                throw new Exception("Errore preparazione query lingue");
            }
            
            $stmt->execute();
            $languages = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $this->supportedLanguages = [];
            foreach ($languages as $lang) {
                $this->supportedLanguages[$lang['code']] = $lang;
                if ($lang['is_default'] == 1) {
                    $this->defaultLanguage = $lang['code'];
                }
                if ($lang['is_fallback'] == 1) {
                    $this->fallbackLanguage = $lang['code'];
                }
            }
            
            // Se non ci sono lingue nel DB, usa default supportate da DeepL
            if (empty($this->supportedLanguages)) {
                $this->supportedLanguages = [
                    'it' => ['code' => 'it', 'name' => 'Italiano', 'native_name' => 'Italiano', 'is_default' => 1],
                    'en' => ['code' => 'en', 'name' => 'English', 'native_name' => 'English', 'is_fallback' => 1],
                    'fr' => ['code' => 'fr', 'name' => 'Français', 'native_name' => 'Français'],
                    'de' => ['code' => 'de', 'name' => 'Deutsch', 'native_name' => 'Deutsch'],
                    'es' => ['code' => 'es', 'name' => 'Español', 'native_name' => 'Español']
                ];
            }
        } catch (Exception $e) {
            error_log("Errore caricamento lingue: " . $e->getMessage());
            // Lingue fallback compatibili DeepL
            $this->supportedLanguages = [
                'it' => ['code' => 'it', 'name' => 'Italiano', 'is_default' => 1],
                'en' => ['code' => 'en', 'name' => 'English', 'is_fallback' => 1]
            ];
        }
    }
    
    /**
     * Traduce testo utilizzando DeepL API
     * 
     * @param string $text Testo da tradurre
     * @param string $targetLang Lingua di destinazione
     * @param string $sourceLang Lingua sorgente (default: it)
     * @return string|false Testo tradotto o false in caso di errore
     */
    private function translateWithDeepLAPI($text, $targetLang, $sourceLang = 'it') {
        if (!$this->config || empty($this->config['api_key']) || $this->config['api_key'] === 'your-deepl-api-key-here') {
            error_log("API key DeepL non configurata correttamente");
            return false;
        }
        
        // Controlla quota giornaliera
        if (!$this->checkDailyQuota()) {
            error_log("Quota giornaliera DeepL superata");
            return false;
        }
        
        try {
            // Converte codici lingua al formato DeepL
            $sourceDeepL = $this->deeplLanguageMap[$sourceLang] ?? strtoupper($sourceLang);
            $targetDeepL = $this->deeplLanguageMap[$targetLang] ?? strtoupper($targetLang);
            
            $apiKey = $this->config['api_key'];
            $url = 'https://api-free.deepl.com/v2/translate'; // Usa API Free, cambiare se Pro
            
            // Prepara dati per DeepL
            $data = [
                'auth_key' => $apiKey,
                'text' => $text,
                'source_lang' => $sourceDeepL,
                'target_lang' => $targetDeepL,
                'preserve_formatting' => '1', // Preserva formattazione HTML
                'tag_handling' => 'html' // Gestione tag HTML
            ];
            
            $options = [
                'http' => [
                    'header' => "Content-Type: application/x-www-form-urlencoded\r\n" .
                               "User-Agent: PassioneCalabria/1.0\r\n",
                    'method' => 'POST',
                    'content' => http_build_query($data),
                    'timeout' => 30
                ]
            ];
            
            $context = stream_context_create($options);
            $result = file_get_contents($url, false, $context);
            
            if ($result === false) {
                throw new Exception("Errore nella chiamata API DeepL");
            }
            
            $response = json_decode($result, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception("Errore parsing risposta JSON: " . json_last_error_msg());
            }
            
            // Gestione errori DeepL
            if (isset($response['message'])) {
                throw new Exception("Errore API DeepL: " . $response['message']);
            }
            
            if (!isset($response['translations'][0]['text'])) {
                throw new Exception("Formato risposta DeepL non valido");
            }
            
            $translatedText = $response['translations'][0]['text'];
            
            // Aggiorna contatore quota (in caratteri)
            $this->incrementDailyUsage(strlen($text));
            
            return $translatedText;
            
        } catch (Exception $e) {
            error_log("Errore traduzione DeepL: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Controlla se la quota giornaliera è stata superata
     */
    private function checkDailyQuota() {
        try {
            if (!$this->initialize()) {
                return false;
            }
            
            $today = date('Y-m-d');
            
            // Reset contatore se è un nuovo giorno
            if ($this->config['last_reset_date'] !== $today) {
                $stmt = $this->db->prepare("UPDATE translation_config SET current_daily_usage = 0, last_reset_date = ? WHERE id = ?");
                $stmt->execute([$today, $this->config['id']]);
                $this->config['current_daily_usage'] = 0;
                $this->config['last_reset_date'] = $today;
            }
            
            return $this->config['current_daily_usage'] < $this->config['daily_quota'];
            
        } catch (Exception $e) {
            error_log("Errore controllo quota DeepL: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Incrementa il contatore dell'uso giornaliero
     */
    private function incrementDailyUsage($characters = 1) {
        try {
            if (!$this->initialize()) {
                return;
            }
            
            $stmt = $this->db->prepare("UPDATE translation_config SET current_daily_usage = current_daily_usage + ? WHERE id = ?");
            $stmt->execute([$characters, $this->config['id']]);
            $this->config['current_daily_usage'] += $characters;
        } catch (Exception $e) {
            error_log("Errore incremento usage DeepL: " . $e->getMessage());
        }
    }
    
    /**
     * Traduce un articolo in tutte le lingue supportate - SOLO MANUALE
     * 
     * @param int $articleId ID dell'articolo
     * @param array $articleData Dati dell'articolo (title, content, excerpt)
     * @param bool $forceTranslation Se true, forza la traduzione (chiamata manuale)
     * @return bool Successo dell'operazione
     */
    public function translateArticle($articleId, $articleData, $forceTranslation = false) {
        if (!$this->initialize() || !$this->isEnabled()) {
            error_log("Servizio traduzione non disponibile per articolo {$articleId}");
            return false;
        }
        
        // CONTROLLO CRITICO: Solo traduzioni manuali sono permesse
        if (!$forceTranslation && !$this->preventiveTranslationEnabled) {
            error_log("Traduzione preventiva DISATTIVATA per articolo {$articleId} - Usa traduzione manuale");
            return false;
        }
        
        $success = true;
        $translatedLanguages = [];
        
        foreach ($this->supportedLanguages as $langCode => $langInfo) {
            // Salta la lingua italiana (quella di base)
            if ($langCode === $this->defaultLanguage) {
                continue;
            }
            
            try {
                // Controlla se traduzione già esiste
                if ($this->articleTranslationExists($articleId, $langCode)) {
                    // Aggiorna traduzione esistente
                    if ($this->updateArticleTranslation($articleId, $langCode, $articleData)) {
                        $translatedLanguages[] = $langCode;
                    }
                } else {
                    // Crea nuova traduzione
                    if ($this->createArticleTranslation($articleId, $langCode, $articleData)) {
                        $translatedLanguages[] = $langCode;
                    }
                }
            } catch (Exception $e) {
                error_log("Errore traduzione articolo {$articleId} in {$langCode}: " . $e->getMessage());
                $success = false;
            }
        }
        
        // Log risultato
        $translatedCount = count($translatedLanguages);
        $totalLanguages = count($this->supportedLanguages) - 1; // Escludi lingua base
        
        error_log("Articolo {$articleId} tradotto in {$translatedCount}/{$totalLanguages} lingue: " . implode(', ', $translatedLanguages));
        
        return $success && $translatedCount > 0;
    }
    
    /**
     * Controlla se esiste già una traduzione per l'articolo nella lingua specificata
     */
    private function articleTranslationExists($articleId, $langCode) {
        try {
            $stmt = $this->db->prepare("SELECT id FROM article_translations WHERE article_id = ? AND language_code = ?");
            $stmt->execute([$articleId, $langCode]);
            return $stmt->fetch() !== false;
        } catch (Exception $e) {
            error_log("Errore controllo esistenza traduzione: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Crea nuova traduzione per articolo usando DeepL
     */
    private function createArticleTranslation($articleId, $langCode, $articleData) {
        try {
            // Traduci i campi necessari
            $translatedTitle = $this->translateWithDeepLAPI($articleData['title'], $langCode);
            $translatedContent = $this->translateWithDeepLAPI($articleData['content'], $langCode);
            $translatedExcerpt = !empty($articleData['excerpt']) ? $this->translateWithDeepLAPI($articleData['excerpt'], $langCode) : '';
            
            // Se la traduzione fallisce, usa il fallback (testo originale per ora)
            if ($translatedTitle === false) $translatedTitle = $articleData['title'];
            if ($translatedContent === false) $translatedContent = $articleData['content'];
            if ($translatedExcerpt === false) $translatedExcerpt = $articleData['excerpt'] ?? '';
            
            // Genera slug tradotto
            $translatedSlug = $this->createSlug($translatedTitle) . '-' . $langCode;
            
            $stmt = $this->db->prepare("
                INSERT INTO article_translations 
                (article_id, language_code, title, content, excerpt, slug, translated_at) 
                VALUES (?, ?, ?, ?, ?, ?, datetime('now'))
            ");
            
            return $stmt->execute([
                $articleId,
                $langCode,
                $translatedTitle,
                $translatedContent,
                $translatedExcerpt,
                $translatedSlug
            ]);
            
        } catch (Exception $e) {
            error_log("Errore creazione traduzione: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Aggiorna traduzione esistente per articolo
     */
    private function updateArticleTranslation($articleId, $langCode, $articleData) {
        try {
            // Traduci i campi necessari
            $translatedTitle = $this->translateWithDeepLAPI($articleData['title'], $langCode);
            $translatedContent = $this->translateWithDeepLAPI($articleData['content'], $langCode);
            $translatedExcerpt = !empty($articleData['excerpt']) ? $this->translateWithDeepLAPI($articleData['excerpt'], $langCode) : '';
            
            // Se la traduzione fallisce, mantieni quella esistente
            if ($translatedTitle === false || $translatedContent === false) {
                error_log("Traduzione fallita per articolo {$articleId} in {$langCode}, mantengo versione esistente");
                return false;
            }
            
            if ($translatedExcerpt === false) $translatedExcerpt = $articleData['excerpt'] ?? '';
            
            // Genera nuovo slug
            $translatedSlug = $this->createSlug($translatedTitle) . '-' . $langCode;
            
            $stmt = $this->db->prepare("
                UPDATE article_translations 
                SET title = ?, content = ?, excerpt = ?, slug = ?, translated_at = datetime('now'), updated_at = datetime('now')
                WHERE article_id = ? AND language_code = ?
            ");
            
            return $stmt->execute([
                $translatedTitle,
                $translatedContent,
                $translatedExcerpt,
                $translatedSlug,
                $articleId,
                $langCode
            ]);
            
        } catch (Exception $e) {
            error_log("Errore aggiornamento traduzione: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Traduce contenuti statici in tutte le lingue supportate - SOLO MANUALE
     * 
     * @param bool $forceTranslation Se true, forza la traduzione (chiamata manuale)
     */
    public function translateStaticContent($forceTranslation = false) {
        if (!$this->initialize() || !$this->isEnabled()) {
            error_log("Servizio traduzione non disponibile per contenuti statici");
            return false;
        }
        
        // CONTROLLO CRITICO: Solo traduzioni manuali sono permesse
        if (!$forceTranslation && !$this->preventiveTranslationEnabled) {
            error_log("Traduzione preventiva DISATTIVATA per contenuti statici - Usa traduzione manuale");
            return false;
        }
        
        try {
            // Ottieni tutti i contenuti statici
            $stmt = $this->db->prepare("SELECT * FROM static_content ORDER BY id");
            $stmt->execute();
            $staticContents = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $translatedCount = 0;
            $totalCount = 0;
            
            foreach ($staticContents as $content) {
                foreach ($this->supportedLanguages as $langCode => $langInfo) {
                    // Salta la lingua italiana (quella di base)
                    if ($langCode === $this->defaultLanguage) {
                        continue;
                    }
                    
                    $totalCount++;
                    
                    try {
                        if ($this->translateStaticContentItem($content['id'], $langCode, $content['content_it'])) {
                            $translatedCount++;
                        }
                    } catch (Exception $e) {
                        error_log("Errore traduzione contenuto statico {$content['content_key']} in {$langCode}: " . $e->getMessage());
                    }
                }
            }
            
            error_log("Contenuti statici tradotti: {$translatedCount}/{$totalCount}");
            return $translatedCount > 0;
            
        } catch (Exception $e) {
            error_log("Errore traduzione contenuti statici: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Traduce singolo elemento di contenuto statico
     */
    private function translateStaticContentItem($staticContentId, $langCode, $originalText) {
        try {
            // Controlla se traduzione esiste già
            $stmt = $this->db->prepare("SELECT id FROM static_content_translations WHERE static_content_id = ? AND language_code = ?");
            $stmt->execute([$staticContentId, $langCode]);
            $exists = $stmt->fetch();
            
            // Traduci il testo con DeepL
            $translatedText = $this->translateWithDeepLAPI($originalText, $langCode);
            
            if ($translatedText === false) {
                // Fallback: usa testo originale
                $translatedText = $originalText;
            }
            
            if ($exists) {
                // Aggiorna traduzione esistente
                $stmt = $this->db->prepare("
                    UPDATE static_content_translations 
                    SET translated_content = ?, translated_at = datetime('now'), updated_at = datetime('now')
                    WHERE static_content_id = ? AND language_code = ?
                ");
                return $stmt->execute([$translatedText, $staticContentId, $langCode]);
            } else {
                // Crea nuova traduzione
                $stmt = $this->db->prepare("
                    INSERT INTO static_content_translations 
                    (static_content_id, language_code, translated_content, translated_at) 
                    VALUES (?, ?, ?, datetime('now'))
                ");
                return $stmt->execute([$staticContentId, $langCode, $translatedText]);
            }
            
        } catch (Exception $e) {
            error_log("Errore traduzione item statico: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Ottiene traduzione articolo per lingua specificata
     */
    public function getArticleTranslation($articleId, $langCode) {
        try {
            if ($langCode === $this->defaultLanguage) {
                // Ritorna articolo originale per lingua italiana
                $stmt = $this->db->prepare("SELECT * FROM articles WHERE id = ?");
                $stmt->execute([$articleId]);
                return $stmt->fetch(PDO::FETCH_ASSOC);
            }
            
            // Cerca traduzione specifica
            $stmt = $this->db->prepare("
                SELECT at.*, a.category_id, a.province_id, a.city_id, a.author, a.status, a.featured_image, a.views, a.latitude, a.longitude, a.created_at
                FROM article_translations at
                JOIN articles a ON at.article_id = a.id
                WHERE at.article_id = ? AND at.language_code = ?
            ");
            $stmt->execute([$articleId, $langCode]);
            $translation = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$translation && $langCode !== $this->fallbackLanguage) {
                // Fallback alla lingua inglese
                return $this->getArticleTranslation($articleId, $this->fallbackLanguage);
            }
            
            return $translation;
            
        } catch (Exception $e) {
            error_log("Errore recupero traduzione articolo: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Ottiene traduzione contenuto statico
     */
    public function getStaticContentTranslation($contentKey, $langCode) {
        try {
            if ($langCode === $this->defaultLanguage) {
                // Ritorna contenuto originale italiano
                $stmt = $this->db->prepare("SELECT content_it as content FROM static_content WHERE content_key = ?");
                $stmt->execute([$contentKey]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                return $result ? $result['content'] : $contentKey;
            }
            
            // Cerca traduzione specifica
            $stmt = $this->db->prepare("
                SELECT sct.translated_content as content
                FROM static_content_translations sct
                JOIN static_content sc ON sct.static_content_id = sc.id
                WHERE sc.content_key = ? AND sct.language_code = ?
            ");
            $stmt->execute([$contentKey, $langCode]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$result && $langCode !== $this->fallbackLanguage) {
                // Fallback alla lingua inglese
                return $this->getStaticContentTranslation($contentKey, $this->fallbackLanguage);
            }
            
            return $result ? $result['content'] : $contentKey;
            
        } catch (Exception $e) {
            error_log("Errore recupero traduzione statica: " . $e->getMessage());
            return $contentKey; // Fallback al key stesso
        }
    }
    
    /**
     * Genera slug per URL
     */
    private function createSlug($text) {
        // Rimuovi HTML tags
        $text = strip_tags($text);
        // Converti caratteri speciali
        $text = strtolower($text);
        $text = preg_replace('/[àáâãäå]/', 'a', $text);
        $text = preg_replace('/[èéêë]/', 'e', $text);
        $text = preg_replace('/[ìíîï]/', 'i', $text);
        $text = preg_replace('/[òóôõö]/', 'o', $text);
        $text = preg_replace('/[ùúûü]/', 'u', $text);
        $text = preg_replace('/[ç]/', 'c', $text);
        $text = preg_replace('/[ñ]/', 'n', $text);
        // Rimuovi caratteri non alfanumerici
        $text = preg_replace('/[^a-z0-9\\s-]/', '', $text);
        // Sostituisci spazi e caratteri multipli con dash
        $text = preg_replace('/[\\s_-]+/', '-', $text);
        // Rimuovi dash iniziali e finali
        $text = trim($text, '-');
        
        return substr($text, 0, 100); // Limita lunghezza
    }
    
    /**
     * Ottiene lingue supportate
     */
    public function getSupportedLanguages() {
        if (!$this->initialize()) {
            return ['it' => ['code' => 'it', 'name' => 'Italiano']];
        }
        return $this->supportedLanguages;
    }
    
    /**
     * Ottiene lingua di default
     */
    public function getDefaultLanguage() {
        if (!$this->initialize()) {
            return 'it';
        }
        return $this->defaultLanguage;
    }
    
    /**
     * Ottiene lingua di fallback
     */
    public function getFallbackLanguage() {
        if (!$this->initialize()) {
            return 'en';
        }
        return $this->fallbackLanguage;
    }
    
    /**
     * Verifica se il servizio è abilitato - VERSIONE MIGLIORATA
     */
    public function isEnabled() {
        if (!$this->initialize()) {
            error_log('[DEEPL] Servizio disabilitato - Inizializzazione fallita');
            return false;
        }
        
        $isConfigValid = $this->config && 
                       $this->config['is_enabled'] == 1 && 
                       !empty($this->config['api_key']) && 
                       $this->config['api_key'] !== 'your-deepl-api-key-here' &&
                       $this->config['api_key'] !== 'your-api-key-here';
                       
        if (!$isConfigValid) {
            error_log('[DEEPL] Servizio disabilitato - Config: ' . json_encode([
                'config_exists' => !!$this->config,
                'is_enabled' => $this->config['is_enabled'] ?? 'N/A',
                'has_api_key' => !empty($this->config['api_key']),
                'api_key_valid' => ($this->config['api_key'] ?? '') !== 'your-deepl-api-key-here'
            ]));
        }
        
        return $isConfigValid;
    }
    
    /**
     * Ottiene configurazione corrente
     */
    public function getConfig() {
        if (!$this->initialize()) {
            return null;
        }
        return $this->config;
    }
    
    /**
     * Statistiche di base
     */
    public function getStats() {
        if (!$this->initialize()) {
            return [
                'languages_count' => 0,
                'is_enabled' => false,
                'api_provider' => 'disabled'
            ];
        }
        
        return [
            'languages_count' => count($this->supportedLanguages),
            'is_enabled' => $this->isEnabled(),
            'api_provider' => $this->config['api_provider'],
            'daily_usage' => $this->config['current_daily_usage'] ?? 0,
            'daily_quota' => $this->config['daily_quota'] ?? 0,
            'quota_percentage' => round(($this->config['current_daily_usage'] / max($this->config['daily_quota'], 1)) * 100, 2)
        ];
    }
    
    /**
     * Testa connessione DeepL API - VERSIONE ESTESA CON DIAGNOSTICA
     */
    public function testDeepLConnection() {
        $diagnostics = [];
        
        // 1. Controllo inizializzazione
        if (!$this->initialize()) {
            return [
                'success' => false, 
                'message' => 'Inizializzazione servizio fallita',
                'diagnostics' => ['Errore caricamento configurazione o database']
            ];
        }
        
        $diagnostics[] = '✅ Servizio inizializzato correttamente';
        
        // 2. Controllo configurazione API
        if (!$this->config || empty($this->config['api_key']) || $this->config['api_key'] === 'your-deepl-api-key-here') {
            $diagnostics[] = '❌ Chiave API DeepL non configurata';
            return [
                'success' => false, 
                'message' => 'Chiave API DeepL non configurata o non valida',
                'diagnostics' => $diagnostics
            ];
        }
        
        $diagnostics[] = '✅ Chiave API DeepL presente';
        
        // 3. Controllo abilitazione servizio
        if (!$this->isEnabled()) {
            $diagnostics[] = '❌ Servizio traduzione disabilitato';
            return [
                'success' => false,
                'message' => 'Servizio traduzione disabilitato in configurazione',
                'diagnostics' => $diagnostics
            ];
        }
        
        $diagnostics[] = '✅ Servizio abilitato';
        
        // 4. Controllo quota
        if (!$this->checkDailyQuota()) {
            $diagnostics[] = '❌ Quota giornaliera esaurita';
            return [
                'success' => false,
                'message' => 'Quota giornaliera API DeepL esaurita',
                'diagnostics' => $diagnostics
            ];
        }
        
        $diagnostics[] = '✅ Quota disponibile';
        
        // 5. Test traduzione reale
        try {
            $testText = "Ciao mondo!";
            $startTime = microtime(true);
            
            $result = $this->translateWithDeepLAPI($testText, 'en');
            
            $executionTime = round((microtime(true) - $startTime) * 1000, 2);
            
            if ($result && $result !== false && strtolower($result) !== strtolower($testText)) {
                $diagnostics[] = "✅ Traduzione test riuscita in {$executionTime}ms";
                $diagnostics[] = "📝 '{$testText}' → '{$result}'";
                
                return [
                    'success' => true, 
                    'message' => 'Connessione DeepL API completamente operativa!',
                    'test_translation' => $result,
                    'execution_time_ms' => $executionTime,
                    'diagnostics' => $diagnostics
                ];
            } else {
                $diagnostics[] = "❌ Traduzione test fallita dopo {$executionTime}ms";
                $diagnostics[] = "📝 Risultato ricevuto: '" . ($result ?: 'null') . "'";
                
                return [
                    'success' => false, 
                    'message' => 'Test traduzione fallito - API non risponde correttamente',
                    'diagnostics' => $diagnostics
                ];
            }
        } catch (Exception $e) {
            $diagnostics[] = '❌ Eccezione durante test: ' . $e->getMessage();
            
            return [
                'success' => false, 
                'message' => 'Errore durante test connessione: ' . $e->getMessage(),
                'diagnostics' => $diagnostics
            ];
        }
    }
    
    /**
     * Ottiene diagnostica dettagliata del sistema traduzione
     */
    public function getSystemDiagnostics() {
        $diagnostics = [];
        
        try {
            // Stato inizializzazione
            $diagnostics['initialization'] = $this->initialized ? 'OK' : 'FALLITA';
            
            // Configurazione database
            $diagnostics['database'] = $this->db ? 'CONNESSO' : 'DISCONNESSO';
            
            // Configurazione API
            if ($this->config) {
                $diagnostics['api_provider'] = $this->config['api_provider'];
                $diagnostics['api_enabled'] = $this->config['is_enabled'] ? 'SÌ' : 'NO';
                $diagnostics['api_key_configured'] = !empty($this->config['api_key']) && $this->config['api_key'] !== 'your-deepl-api-key-here' ? 'SÌ' : 'NO';
                $diagnostics['daily_quota'] = $this->config['daily_quota'] . ' caratteri';
                $diagnostics['daily_usage'] = $this->config['current_daily_usage'] . ' caratteri';
                $diagnostics['quota_remaining'] = ($this->config['daily_quota'] - $this->config['current_daily_usage']) . ' caratteri';
            } else {
                $diagnostics['config_status'] = 'NON CARICATA';
            }
            
            // Lingue supportate
            $diagnostics['supported_languages'] = count($this->supportedLanguages);
            $diagnostics['default_language'] = $this->defaultLanguage;
            $diagnostics['fallback_language'] = $this->fallbackLanguage;
            
            // Controllo stato preventive translation
            $diagnostics['preventive_translation'] = $this->preventiveTranslationEnabled ? 'ABILITATO' : 'DISABILITATO (Solo manuale)';
            
        } catch (Exception $e) {
            $diagnostics['error'] = $e->getMessage();
        }
        
        return $diagnostics;
    }
}
?>