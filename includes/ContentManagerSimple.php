<?php
/**
 * Gestore Contenuti Multilingue Semplificato
 * 
 * Versione semplificata per il sistema di traduzione preventiva
 * che funziona senza estendere le classi esistenti
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database.php';

class ContentManagerSimple {
    
    private $db;
    private $currentLanguage;
    private $defaultLanguage = 'it';
    private $fallbackLanguage = 'en';
    private $supportedLanguages = ['it', 'en', 'fr', 'de', 'es'];
    private $contentCache = [];
    
    public function __construct() {
        $this->db = new Database();
        $this->currentLanguage = $this->detectUserLanguage();
        
        // Log per debug
        error_log("ContentManagerSimple inizializzato con lingua: " . $this->currentLanguage);
    }
    
    /**
     * Rileva la lingua preferita dell'utente
     */
    private function detectUserLanguage() {
        // 1. Prima controlla parametro URL
        if (isset($_GET['lang']) && in_array($_GET['lang'], $this->supportedLanguages)) {
            $this->setUserLanguage($_GET['lang']);
            return $_GET['lang'];
        }
        
        // 2. Controlla sessione
        if (isset($_SESSION['user_language']) && in_array($_SESSION['user_language'], $this->supportedLanguages)) {
            return $_SESSION['user_language'];
        }
        
        // 3. Controlla cookie
        if (isset($_COOKIE['site_language']) && in_array($_COOKIE['site_language'], $this->supportedLanguages)) {
            $this->setUserLanguage($_COOKIE['site_language']);
            return $_COOKIE['site_language'];
        }
        
        // 4. Prova a rilevare da Accept-Language header del browser
        if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            $acceptLanguages = explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']);
            
            foreach ($acceptLanguages as $lang) {
                $langCode = strtolower(substr(trim($lang), 0, 2));
                if (in_array($langCode, $this->supportedLanguages) && $langCode !== $this->defaultLanguage) {
                    // Salva la preferenza rilevata
                    $this->setUserLanguage($langCode);
                    return $langCode;
                }
            }
        }
        
        // 5. Fallback alla lingua di default (italiano)
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
     * Ottiene testo statico tradotto (versione semplificata)
     */
    public function getText($contentKey, $fallbackText = null) {
        $cacheKey = "text_{$contentKey}_{$this->currentLanguage}";
        
        if (isset($this->contentCache[$cacheKey])) {
            return $this->contentCache[$cacheKey];
        }
        
        // Per ora usa solo fallback text (da implementare con DB)
        $text = $fallbackText ?? $contentKey;
        
        // Prova a cercare nel database se le tabelle esistono
        try {
            if ($this->currentLanguage === $this->defaultLanguage) {
                // Ritorna contenuto originale italiano
                $stmt = $this->db->prepare("SELECT content_it as content FROM static_content WHERE content_key = ?");
                $stmt->execute([$contentKey]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($result) {
                    $text = $result['content'];
                }
            } else {
                // Cerca traduzione specifica
                $stmt = $this->db->prepare("
                    SELECT sct.translated_content as content
                    FROM static_content_translations sct
                    JOIN static_content sc ON sct.static_content_id = sc.id
                    WHERE sc.content_key = ? AND sct.language_code = ?
                ");
                $stmt->execute([$contentKey, $this->currentLanguage]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($result) {
                    $text = $result['content'];
                } else if ($this->currentLanguage !== $this->fallbackLanguage) {
                    // Fallback alla lingua inglese
                    $stmt->execute([$contentKey, $this->fallbackLanguage]);
                    $result = $stmt->fetch(PDO::FETCH_ASSOC);
                    if ($result) {
                        $text = $result['content'];
                    }
                }
            }
        } catch (Exception $e) {
            error_log("Errore recupero traduzione statica: " . $e->getMessage());
            // Usa fallback text in caso di errore
            $text = $fallbackText ?? $contentKey;
        }
        
        // Cache risultato
        $this->contentCache[$cacheKey] = $text;
        
        return $text;
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
}

/**
 * Funzione di utilità globale per ottenere testo tradotto
 */
function t($contentKey, $fallbackText = null) {
    global $contentManager;
    
    if (!isset($contentManager)) {
        $contentManager = new ContentManagerSimple();
    }
    
    return $contentManager->getText($contentKey, $fallbackText);
}
?>