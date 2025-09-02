<?php
/**
 * Gestore Contenuti Multilingue
 * 
 * Classe principale per la gestione automatica dei contenuti tradotti.
 * Si occupa di rilevare la lingua dell'utente e caricare i contenuti
 * appropriati dal database con sistema di fallback.
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/DatabaseWithTranslations.php';

class ContentManager {
    
    private $db;
    private $currentLanguage;
    private $defaultLanguage = 'it';
    private $fallbackLanguage = 'en';
    private $supportedLanguages = ['it', 'en', 'fr', 'de', 'es'];
    private $contentCache = [];
    
    public function __construct() {
        $this->db = new DatabaseWithTranslations();
        $this->currentLanguage = $this->detectUserLanguage();
        
        // Log per debug
        error_log("ContentManager inizializzato con lingua: " . $this->currentLanguage);
    }
    
    /**
     * Rileva la lingua preferita dell'utente
     */
    private function detectUserLanguage() {
        // 1. Prima controlla parametro URL
        if (isset($_GET['lang']) && in_array($_GET['lang'], $this->supportedLanguages)) {
            $this->setUserLanguage($_GET['lang']);
            error_log("ContentManager: Lingua da URL: " . $_GET['lang']);
            return $_GET['lang'];
        }
        
        // 2. Controlla sessione
        if (isset($_SESSION['user_language']) && in_array($_SESSION['user_language'], $this->supportedLanguages)) {
            error_log("ContentManager: Lingua da sessione: " . $_SESSION['user_language']);
            return $_SESSION['user_language'];
        }
        
        // 3. Controlla cookie
        if (isset($_COOKIE['site_language']) && in_array($_COOKIE['site_language'], $this->supportedLanguages)) {
            $this->setUserLanguage($_COOKIE['site_language']);
            error_log("ContentManager: Lingua da cookie: " . $_COOKIE['site_language']);
            return $_COOKIE['site_language'];
        }
        
        // 4. Rilevamento migliorato da Accept-Language header del browser
        if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            $acceptLanguage = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
            error_log("ContentManager: Accept-Language header: " . $acceptLanguage);
            
            // Parse Accept-Language header più accurato
            $acceptLanguages = [];
            if (preg_match_all('/([a-z]{1,8}(?:-[a-z]{1,8})?)(?:;q=([0-9.]+))?/i', $acceptLanguage, $matches)) {
                $languages = $matches[1];
                $qualities = $matches[2];
                
                foreach ($languages as $i => $lang) {
                    $quality = $qualities[$i] !== '' ? (float) $qualities[$i] : 1.0;
                    $langCode = strtolower(substr($lang, 0, 2));
                    $acceptLanguages[] = ['code' => $langCode, 'quality' => $quality, 'full' => $lang];
                }
                
                // Ordina per qualità (priorità)
                usort($acceptLanguages, function($a, $b) {
                    return $b['quality'] <=> $a['quality'];
                });
            }
            
            // Cerca la prima lingua supportata
            foreach ($acceptLanguages as $langInfo) {
                $langCode = $langInfo['code'];
                error_log("ContentManager: Controllo lingua browser: " . $langCode . " (quality: " . $langInfo['quality'] . ")");
                
                if (in_array($langCode, $this->supportedLanguages) && $langCode !== $this->defaultLanguage) {
                    $this->setUserLanguage($langCode);
                    error_log("ContentManager: Lingua rilevata dal browser: " . $langCode);
                    return $langCode;
                }
            }
            
            error_log("ContentManager: Nessuna lingua supportata trovata nel browser");
        }
        
        // 5. Fallback alla lingua di default (italiano)
        error_log("ContentManager: Usando lingua di default: " . $this->defaultLanguage);
        return $this->defaultLanguage;
    }
    
    /**
     * Imposta la lingua utente in sessione e cookie
     */
    public function setUserLanguage($language) {
        if (in_array($language, $this->supportedLanguages)) {
            $_SESSION['user_language'] = $language;
            
            // Cookie per 30 giorni
            $cookieExpiration = time() + (30 * 24 * 60 * 60);
            setcookie('site_language', $language, $cookieExpiration, '/');
            
            $this->currentLanguage = $language;
            
            // Pulisci cache quando cambia lingua
            $this->contentCache = [];
            
            error_log("Lingua utente impostata: $language");
        }
    }
    
    /**
     * Ottiene articolo nella lingua corrente
     */
    public function getArticle($articleId) {
        $cacheKey = "article_{$articleId}_{$this->currentLanguage}";
        
        if (isset($this->contentCache[$cacheKey])) {
            return $this->contentCache[$cacheKey];
        }
        
        $article = $this->db->getArticleByLanguage($articleId, $this->currentLanguage);
        
        if (!$article && $this->currentLanguage !== $this->fallbackLanguage) {
            // Fallback alla lingua inglese
            $article = $this->db->getArticleByLanguage($articleId, $this->fallbackLanguage);
        }
        
        if (!$article && $this->currentLanguage !== $this->defaultLanguage) {
            // Ultimo fallback alla lingua italiana (default)
            $article = $this->db->getArticleByLanguage($articleId, $this->defaultLanguage);
        }
        
        // Cache risultato
        $this->contentCache[$cacheKey] = $article;
        
        return $article;
    }
    
    /**
     * Ottiene articolo per slug nella lingua corrente
     */
    public function getArticleBySlug($slug) {
        $cacheKey = "article_slug_{$slug}_{$this->currentLanguage}";
        
        if (isset($this->contentCache[$cacheKey])) {
            return $this->contentCache[$cacheKey];
        }
        
        // Per lingue diverse dall'italiano, prova prima con slug tradotto
        if ($this->currentLanguage !== $this->defaultLanguage) {
            $translatedSlug = $slug . '-' . $this->currentLanguage;
            $article = $this->db->getArticleBySlugAndLanguage($translatedSlug, $this->currentLanguage);
            
            if ($article) {
                $this->contentCache[$cacheKey] = $article;
                return $article;
            }
        }
        
        // Prova con slug originale
        $article = $this->db->getArticleBySlugAndLanguage($slug, $this->currentLanguage);
        
        if (!$article && $this->currentLanguage !== $this->fallbackLanguage) {
            // Fallback alla lingua inglese
            $fallbackSlug = $slug . '-' . $this->fallbackLanguage;
            $article = $this->db->getArticleBySlugAndLanguage($fallbackSlug, $this->fallbackLanguage);
        }
        
        if (!$article && $this->currentLanguage !== $this->defaultLanguage) {
            // Ultimo fallback alla lingua italiana
            $article = $this->db->getArticleBySlugAndLanguage($slug, $this->defaultLanguage);
        }
        
        // Cache risultato
        $this->contentCache[$cacheKey] = $article;
        
        return $article;
    }
    
    /**
     * Ottiene testo statico tradotto
     */
    public function getText($contentKey, $fallbackText = null) {
        $cacheKey = "text_{$contentKey}_{$this->currentLanguage}";
        
        if (isset($this->contentCache[$cacheKey])) {
            return $this->contentCache[$cacheKey];
        }
        
        $text = $this->db->getStaticContent($contentKey, $this->currentLanguage);
        
        // Se non trova traduzione, usa il fallback text o il content key stesso
        if (!$text || $text === $contentKey) {
            $text = $fallbackText ?? $contentKey;
        }
        
        // Cache risultato
        $this->contentCache[$cacheKey] = $text;
        
        return $text;
    }
    
    /**
     * Ottiene elenco articoli per categoria nella lingua corrente
     */
    public function getArticlesByCategory($categoryId, $limit = null, $offset = 0) {
        $cacheKey = "articles_cat_{$categoryId}_{$this->currentLanguage}_{$limit}_{$offset}";
        
        if (isset($this->contentCache[$cacheKey])) {
            return $this->contentCache[$cacheKey];
        }
        
        $articles = $this->db->getArticlesByCategoryWithTranslations($categoryId, $this->currentLanguage, $limit, $offset);
        
        // Cache risultato
        $this->contentCache[$cacheKey] = $articles;
        
        return $articles;
    }
    
    /**
     * Ottiene elenco articoli in evidenza nella lingua corrente
     */
    public function getFeaturedArticles($limit = 6) {
        $cacheKey = "featured_articles_{$this->currentLanguage}_{$limit}";
        
        if (isset($this->contentCache[$cacheKey])) {
            return $this->contentCache[$cacheKey];
        }
        
        try {
            if ($this->currentLanguage === $this->defaultLanguage) {
                // Per italiano, usa metodo originale
                $articles = $this->db->getFeaturedArticles($limit);
            } else {
                // Per altre lingue, cerca nelle traduzioni
                $stmt = $this->db->prepare("
                    SELECT at.*, a.category_id, a.province_id, a.city_id, a.author, a.status, a.featured_image, a.views, a.latitude, a.longitude, a.created_at
                    FROM article_translations at
                    JOIN articles a ON at.article_id = a.id
                    WHERE a.featured = 1 AND a.status = 'published' AND at.language_code = ?
                    ORDER BY a.created_at DESC
                    LIMIT ?
                ");
                $stmt->execute([$this->currentLanguage, $limit]);
                $articles = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Se non trova abbastanza articoli tradotti, completa con versioni inglesi o italiane
                if (count($articles) < $limit && $this->currentLanguage !== $this->fallbackLanguage) {
                    $remaining = $limit - count($articles);
                    $excludeIds = array_column($articles, 'article_id');
                    
                    // Ottieni articoli aggiuntivi in inglese o italiano
                    $fallbackArticles = $this->getFallbackFeaturedArticles($remaining, $excludeIds);
                    $articles = array_merge($articles, $fallbackArticles);
                }
            }
            
            // Cache risultato
            $this->contentCache[$cacheKey] = $articles;
            
            return $articles;
            
        } catch (Exception $e) {
            error_log("Errore recupero articoli in evidenza: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Ottiene articoli in evidenza di fallback quando non ci sono abbastanza traduzioni
     */
    private function getFallbackFeaturedArticles($limit, $excludeIds = []) {
        try {
            $excludeClause = '';
            if (!empty($excludeIds)) {
                $placeholders = str_repeat('?,', count($excludeIds) - 1) . '?';
                $excludeClause = "AND a.id NOT IN ($placeholders)";
            }
            
            $stmt = $this->db->prepare("
                SELECT a.*, a.title, a.content, a.excerpt
                FROM articles a
                WHERE a.featured = 1 AND a.status = 'published' $excludeClause
                ORDER BY a.created_at DESC
                LIMIT ?
            ");
            
            $params = array_merge($excludeIds, [$limit]);
            $stmt->execute($params);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Errore recupero articoli fallback: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Genera URL con lingua corrente
     */
    public function getLanguageUrl($path = '') {
        if ($this->currentLanguage === $this->defaultLanguage) {
            return SITE_URL . '/' . ltrim($path, '/');
        }
        
        $url = SITE_URL . '/' . ltrim($path, '/');
        $separator = strpos($url, '?') !== false ? '&' : '?';
        
        return $url . $separator . 'lang=' . $this->currentLanguage;
    }
    
    /**
     * Aggiunge tag HTML per indicare la lingua corrente
     */
    public function getLanguageAttributes() {
        return sprintf('lang="%s" data-lang="%s"', $this->currentLanguage, $this->currentLanguage);
    }
    
    /**
     * Ottiene informazioni sulla lingua corrente
     */
    public function getCurrentLanguageInfo() {
        $languageNames = [
            'it' => ['name' => 'Italiano', 'native' => 'Italiano'],
            'en' => ['name' => 'English', 'native' => 'English'],
            'fr' => ['name' => 'French', 'native' => 'Français'],
            'de' => ['name' => 'German', 'native' => 'Deutsch'],
            'es' => ['name' => 'Spanish', 'native' => 'Español']
        ];
        
        return [
            'code' => $this->currentLanguage,
            'name' => $languageNames[$this->currentLanguage]['name'] ?? $this->currentLanguage,
            'native_name' => $languageNames[$this->currentLanguage]['native'] ?? $this->currentLanguage,
            'is_default' => $this->currentLanguage === $this->defaultLanguage,
            'is_fallback' => $this->currentLanguage === $this->fallbackLanguage
        ];
    }
    
    /**
     * Ottiene lingua corrente
     */
    public function getCurrentLanguage() {
        return $this->currentLanguage;
    }
    
    /**
     * Ottiene lingue supportate
     */
    public function getSupportedLanguages() {
        return $this->supportedLanguages;
    }
    
    /**
     * Controlla se è necessario attivare il sistema di traduzione JavaScript
     */
    public function shouldActivateLanguageDetection() {
        // Attiva solo se:
        // 1. Non è già stata impostata una preferenza lingua
        // 2. La lingua rilevata dal browser non è italiano
        
        $hasLanguagePreference = isset($_SESSION['user_language']) || 
                                isset($_COOKIE['site_language']) || 
                                isset($_GET['lang']);
        
        return !$hasLanguagePreference;
    }
    
    /**
     * Pulisce cache contenuti
     */
    public function clearCache() {
        $this->contentCache = [];
    }
    
    /**
     * Ottiene statistiche sulla traduzione dei contenuti
     */
    public function getTranslationStats() {
        return $this->db->getTranslationStats();
    }
}

/**
 * Funzione di utilità globale per ottenere testo tradotto
 */
function t($contentKey, $fallbackText = null) {
    global $contentManager;
    
    if (!isset($contentManager)) {
        $contentManager = new ContentManager();
    }
    
    return $contentManager->getText($contentKey, $fallbackText);
}
?>