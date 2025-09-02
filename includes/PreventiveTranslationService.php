<?php
/**
 * Servizio per Traduzione Preventiva
 * 
 * Gestisce le traduzioni automatiche preventive utilizzando Google Translate API
 * con sistema di cache, fallback e gestione errori robusto.
 * 
 * @author Passione Calabria
 * @version 1.0
 */

require_once __DIR__ . '/config.php';

class PreventiveTranslationService {
    
    private $db;
    private $config;
    private $supportedLanguages;
    private $defaultLanguage = 'it';
    private $fallbackLanguage = 'en';
    
    public function __construct(Database $database) {
        $this->db = $database;
        $this->loadConfig();
        $this->loadSupportedLanguages();
    }
    
    /**
     * Carica configurazione API da database
     */
    private function loadConfig() {
        try {
            $stmt = $this->db->prepare("SELECT * FROM translation_config WHERE is_enabled = 1 LIMIT 1");
            $stmt->execute();
            $this->config = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$this->config) {
                throw new Exception("Nessuna configurazione API attiva trovata");
            }
        } catch (Exception $e) {
            error_log("Errore caricamento config traduzione: " . $e->getMessage());
            $this->config = null;
        }
    }
    
    /**
     * Carica lingue supportate da database
     */
    private function loadSupportedLanguages() {
        try {
            $stmt = $this->db->prepare("SELECT code, name, native_name, is_default, is_fallback FROM preventive_languages WHERE is_active = 1");
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
        } catch (Exception $e) {
            error_log("Errore caricamento lingue: " . $e->getMessage());
            $this->supportedLanguages = ['it' => ['code' => 'it', 'name' => 'Italiano'], 'en' => ['code' => 'en', 'name' => 'English']];
        }
    }
    
    /**
     * Traduce testo utilizzando Google Translate API
     * 
     * @param string $text Testo da tradurre
     * @param string $targetLang Lingua di destinazione
     * @param string $sourceLang Lingua sorgente (default: it)
     * @return string|false Testo tradotto o false in caso di errore
     */
    private function translateWithGoogleAPI($text, $targetLang, $sourceLang = 'it') {
        if (!$this->config || empty($this->config['api_key'])) {
            error_log("API key Google Translate non configurata");
            return false;
        }
        
        // Controlla quota giornaliera
        if (!$this->checkDailyQuota()) {
            error_log("Quota giornaliera Google Translate superata");
            return false;
        }
        
        try {
            $apiKey = $this->config['api_key'];
            $url = 'https://translation.googleapis.com/language/translate/v2?key=' . $apiKey;
            
            $data = [
                'q' => $text,
                'target' => $targetLang,
                'source' => $sourceLang,
                'format' => 'html' // Preserva HTML tags
            ];
            
            $options = [
                'http' => [
                    'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
                    'method' => 'POST',
                    'content' => http_build_query($data),
                    'timeout' => 30
                ]
            ];
            
            $context = stream_context_create($options);
            $result = file_get_contents($url, false, $context);
            
            if ($result === false) {
                throw new Exception("Errore nella chiamata API Google Translate");
            }
            
            $response = json_decode($result, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception("Errore parsing risposta JSON: " . json_last_error_msg());
            }
            
            if (isset($response['error'])) {
                throw new Exception("Errore API Google: " . $response['error']['message']);
            }
            
            if (!isset($response['data']['translations'][0]['translatedText'])) {
                throw new Exception("Formato risposta API non valido");
            }
            
            $translatedText = $response['data']['translations'][0]['translatedText'];
            
            // Aggiorna contatore quota
            $this->incrementDailyUsage();
            
            return $translatedText;
            
        } catch (Exception $e) {
            error_log("Errore traduzione Google: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Controlla se la quota giornaliera è stata superata
     */
    private function checkDailyQuota() {
        try {
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
            error_log("Errore controllo quota: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Incrementa il contatore dell'uso giornaliero
     */
    private function incrementDailyUsage() {
        try {
            $stmt = $this->db->prepare("UPDATE translation_config SET current_daily_usage = current_daily_usage + 1 WHERE id = ?");
            $stmt->execute([$this->config['id']]);
            $this->config['current_daily_usage']++;
        } catch (Exception $e) {
            error_log("Errore incremento usage: " . $e->getMessage());
        }
    }
    
    /**
     * Traduce un articolo in tutte le lingue supportate
     * 
     * @param int $articleId ID dell'articolo
     * @param array $articleData Dati dell'articolo (title, content, excerpt)
     * @return bool Successo dell'operazione
     */
    public function translateArticle($articleId, $articleData) {
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
     * Crea nuova traduzione per articolo
     */
    private function createArticleTranslation($articleId, $langCode, $articleData) {
        try {
            // Traduci i campi necessari
            $translatedTitle = $this->translateWithGoogleAPI($articleData['title'], $langCode);
            $translatedContent = $this->translateWithGoogleAPI($articleData['content'], $langCode);
            $translatedExcerpt = !empty($articleData['excerpt']) ? $this->translateWithGoogleAPI($articleData['excerpt'], $langCode) : '';
            
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
            $translatedTitle = $this->translateWithGoogleAPI($articleData['title'], $langCode);
            $translatedContent = $this->translateWithGoogleAPI($articleData['content'], $langCode);
            $translatedExcerpt = !empty($articleData['excerpt']) ? $this->translateWithGoogleAPI($articleData['excerpt'], $langCode) : '';
            
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
     * Traduce contenuti statici in tutte le lingue supportate
     */
    public function translateStaticContent() {
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
            
            // Traduci il testo
            $translatedText = $this->translateWithGoogleAPI($originalText, $langCode);
            
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
        $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
        // Sostituisci spazi e caratteri multipli con dash
        $text = preg_replace('/[\s_-]+/', '-', $text);
        // Rimuovi dash iniziali e finali
        $text = trim($text, '-');
        
        return substr($text, 0, 100); // Limita lunghezza
    }
    
    /**
     * Ottiene lingue supportate
     */
    public function getSupportedLanguages() {
        return $this->supportedLanguages;
    }
    
    /**
     * Ottiene lingua di default
     */
    public function getDefaultLanguage() {
        return $this->defaultLanguage;
    }
    
    /**
     * Ottiene lingua di fallback
     */
    public function getFallbackLanguage() {
        return $this->fallbackLanguage;
    }
    
    /**
     * Traduce una pagina statica in tutte le lingue supportate
     * 
     * @param int $staticPageId ID della pagina statica
     * @param array $pageData Dati della pagina (title, content, meta_title, meta_description)
     * @return bool Successo dell'operazione
     */
    public function translateStaticPage($staticPageId, $pageData) {
        $success = true;
        $translatedLanguages = [];
        
        foreach ($this->supportedLanguages as $langCode => $langInfo) {
            // Salta la lingua italiana (quella di base)
            if ($langCode === $this->defaultLanguage) {
                continue;
            }
            
            try {
                // Controlla se traduzione già esiste
                if ($this->staticPageTranslationExists($staticPageId, $langCode)) {
                    // Aggiorna traduzione esistente
                    if ($this->updateStaticPageTranslation($staticPageId, $langCode, $pageData)) {
                        $translatedLanguages[] = $langCode;
                    }
                } else {
                    // Crea nuova traduzione
                    if ($this->createStaticPageTranslation($staticPageId, $langCode, $pageData)) {
                        $translatedLanguages[] = $langCode;
                    }
                }
            } catch (Exception $e) {
                error_log("Errore traduzione pagina statica {$staticPageId} in {$langCode}: " . $e->getMessage());
                $success = false;
            }
        }
        
        // Log risultato
        $translatedCount = count($translatedLanguages);
        $totalLanguages = count($this->supportedLanguages) - 1; // Escludi lingua base
        
        error_log("Pagina statica {$staticPageId} tradotta in {$translatedCount}/{$totalLanguages} lingue: " . implode(', ', $translatedLanguages));
        
        return $success && $translatedCount > 0;
    }
    
    /**
     * Controlla se esiste già una traduzione per la pagina statica nella lingua specificata
     */
    private function staticPageTranslationExists($staticPageId, $langCode) {
        try {
            $stmt = $this->db->prepare("SELECT id FROM static_pages_translations WHERE static_page_id = ? AND language_code = ?");
            $stmt->execute([$staticPageId, $langCode]);
            return $stmt->fetch() !== false;
        } catch (Exception $e) {
            error_log("Errore controllo esistenza traduzione pagina statica: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Crea nuova traduzione per pagina statica
     */
    private function createStaticPageTranslation($staticPageId, $langCode, $pageData) {
        try {
            // Traduci i campi necessari
            $translatedTitle = $this->translateWithGoogleAPI($pageData['title'], $langCode);
            $translatedContent = $this->translateWithGoogleAPI($pageData['content'], $langCode);
            $translatedMetaTitle = !empty($pageData['meta_title']) ? $this->translateWithGoogleAPI($pageData['meta_title'], $langCode) : '';
            $translatedMetaDescription = !empty($pageData['meta_description']) ? $this->translateWithGoogleAPI($pageData['meta_description'], $langCode) : '';
            
            // Se la traduzione fallisce, usa il fallback
            if ($translatedTitle === false) $translatedTitle = $pageData['title'];
            if ($translatedContent === false) $translatedContent = $pageData['content'];
            if ($translatedMetaTitle === false) $translatedMetaTitle = $pageData['meta_title'] ?? '';
            if ($translatedMetaDescription === false) $translatedMetaDescription = $pageData['meta_description'] ?? '';
            
            // Genera slug tradotto basato sul titolo tradotto
            $baseSlug = $pageData['slug'] ?? $this->createSlug($pageData['title']);
            $translatedSlug = $baseSlug . '-' . $langCode;
            
            $stmt = $this->db->prepare("
                INSERT INTO static_pages_translations 
                (static_page_id, language_code, title, content, meta_title, meta_description, slug, translated_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, datetime('now'))
            ");
            
            return $stmt->execute([
                $staticPageId,
                $langCode,
                $translatedTitle,
                $translatedContent,
                $translatedMetaTitle,
                $translatedMetaDescription,
                $translatedSlug
            ]);
            
        } catch (Exception $e) {
            error_log("Errore creazione traduzione pagina statica: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Aggiorna traduzione esistente per pagina statica
     */
    private function updateStaticPageTranslation($staticPageId, $langCode, $pageData) {
        try {
            // Traduci i campi necessari
            $translatedTitle = $this->translateWithGoogleAPI($pageData['title'], $langCode);
            $translatedContent = $this->translateWithGoogleAPI($pageData['content'], $langCode);
            $translatedMetaTitle = !empty($pageData['meta_title']) ? $this->translateWithGoogleAPI($pageData['meta_title'], $langCode) : '';
            $translatedMetaDescription = !empty($pageData['meta_description']) ? $this->translateWithGoogleAPI($pageData['meta_description'], $langCode) : '';
            
            // Se la traduzione fallisce, mantieni quella esistente
            if ($translatedTitle === false || $translatedContent === false) {
                error_log("Traduzione fallita per pagina statica {$staticPageId} in {$langCode}, mantengo versione esistente");
                return false;
            }
            
            if ($translatedMetaTitle === false) $translatedMetaTitle = $pageData['meta_title'] ?? '';
            if ($translatedMetaDescription === false) $translatedMetaDescription = $pageData['meta_description'] ?? '';
            
            // Genera nuovo slug
            $baseSlug = $pageData['slug'] ?? $this->createSlug($pageData['title']);
            $translatedSlug = $baseSlug . '-' . $langCode;
            
            $stmt = $this->db->prepare("
                UPDATE static_pages_translations 
                SET title = ?, content = ?, meta_title = ?, meta_description = ?, slug = ?, 
                    translated_at = datetime('now'), updated_at = datetime('now')
                WHERE static_page_id = ? AND language_code = ?
            ");
            
            return $stmt->execute([
                $translatedTitle,
                $translatedContent,
                $translatedMetaTitle,
                $translatedMetaDescription,
                $translatedSlug,
                $staticPageId,
                $langCode
            ]);
            
        } catch (Exception $e) {
            error_log("Errore aggiornamento traduzione pagina statica: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Ottiene traduzione pagina statica per lingua specificata
     */
    public function getStaticPageTranslation($staticPageId, $langCode) {
        try {
            if ($langCode === $this->defaultLanguage) {
                // Ritorna pagina originale per lingua italiana
                $stmt = $this->db->prepare("SELECT * FROM static_pages WHERE id = ?");
                $stmt->execute([$staticPageId]);
                return $stmt->fetch(PDO::FETCH_ASSOC);
            }
            
            // Cerca traduzione specifica
            $stmt = $this->db->prepare("
                SELECT spt.*, sp.slug as original_slug, sp.is_published, sp.created_at
                FROM static_pages_translations spt
                JOIN static_pages sp ON spt.static_page_id = sp.id
                WHERE spt.static_page_id = ? AND spt.language_code = ?
            ");
            $stmt->execute([$staticPageId, $langCode]);
            $translation = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$translation && $langCode !== $this->fallbackLanguage) {
                // Fallback alla lingua inglese
                return $this->getStaticPageTranslation($staticPageId, $this->fallbackLanguage);
            }
            
            return $translation;
            
        } catch (Exception $e) {
            error_log("Errore recupero traduzione pagina statica: " . $e->getMessage());
            return false;
        }
    }
}