<?php
/**
 * Gestore Contenuti Multilingue Semplificato - VERSIONE CORRETTA E FUNZIONANTE
 * 
 * Versione aggiornata che USA il database per caricare le traduzioni esistenti
 * Integra con il sistema di traduzione DeepL già presente
 */

require_once __DIR__ . '/config.php';

class ContentManagerSimple {
    
    private $currentLanguage;
    private $defaultLanguage = 'it';
    private $fallbackLanguage = 'en';
    private $supportedLanguages = ['it', 'en', 'fr', 'de', 'es'];
    private $contentCache = [];
    private $db = null;
    
    public function __construct() {
        // Inizializza sessione se non già attiva
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        // Inizializza database
        try {
            require_once __DIR__ . '/database.php';
            $this->db = new Database();
        } catch (Exception $e) {
            error_log("Errore inizializzazione database in ContentManagerSimple: " . $e->getMessage());
            $this->db = null;
        }
        
        $this->currentLanguage = $this->detectUserLanguage();
        
        // Log dettagliato per debug sistema lingua
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        $acceptLang = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? 'Not set';
        error_log("[LINGUA] ContentManagerSimple inizializzato:");
        error_log("  - Lingua rilevata: " . $this->currentLanguage);
        error_log("  - User Agent: " . substr($userAgent, 0, 100));
        error_log("  - Accept-Language: " . $acceptLang);
        error_log("  - Database: " . ($this->db ? "OK" : "ERRORE"));
    }
    
    /**
     * Rileva la lingua preferita dell'utente - VERSIONE MIGLIORATA
     * Compatibile con Chrome, Firefox, Safari, Opera, Edge
     */
    private function detectUserLanguage() {
        // 1. Prima controlla parametro URL (priorità assoluta)
        if (isset($_GET['lang']) && in_array($_GET['lang'], $this->supportedLanguages)) {
            $this->setUserLanguage($_GET['lang']);
            error_log("Lingua rilevata da URL: " . $_GET['lang']);
            return $_GET['lang'];
        }
        
        // 2. Controlla sessione esistente
        if (isset($_SESSION['user_language']) && in_array($_SESSION['user_language'], $this->supportedLanguages)) {
            error_log("Lingua rilevata da sessione: " . $_SESSION['user_language']);
            return $_SESSION['user_language'];
        }
        
        // 3. Controlla cookie persistente
        if (isset($_COOKIE['site_language']) && in_array($_COOKIE['site_language'], $this->supportedLanguages)) {
            $this->setUserLanguage($_COOKIE['site_language']);
            error_log("Lingua rilevata da cookie: " . $_COOKIE['site_language']);
            return $_COOKIE['site_language'];
        }
        
        // 4. RILEVAMENTO MIGLIORATO da Accept-Language header (tutti i browser)
        if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) && !empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            $browserLanguages = $this->parseAcceptLanguageHeader($_SERVER['HTTP_ACCEPT_LANGUAGE']);
            
            foreach ($browserLanguages as $langInfo) {
                $langCode = strtolower($langInfo['code']);
                
                // Controlla se è una lingua supportata e NON è la lingua di default
                if (in_array($langCode, $this->supportedLanguages) && $langCode !== $this->defaultLanguage) {
                    $this->setUserLanguage($langCode);
                    error_log("Lingua rilevata da browser: {$langCode} (quality: {$langInfo['quality']}, header: {$_SERVER['HTTP_ACCEPT_LANGUAGE']})");
                    return $langCode;
                }
            }
            
            error_log("Nessuna lingua supportata trovata nel browser. Header: " . $_SERVER['HTTP_ACCEPT_LANGUAGE']);
        } else {
            error_log("Header Accept-Language non presente o vuoto");
        }
        
        // 5. Fallback alla lingua di default (italiano)
        error_log("Fallback alla lingua di default: " . $this->defaultLanguage);
        return $this->defaultLanguage;
    }
    
    /**
     * Parsa l'header Accept-Language in modo robusto
     * Supporta tutti i formati browser moderni
     */
    private function parseAcceptLanguageHeader($acceptLanguage) {
        $languages = [];
        
        // Dividi per virgole e processa ogni lingua
        $langEntries = explode(',', $acceptLanguage);
        
        foreach ($langEntries as $entry) {
            $entry = trim($entry);
            if (empty($entry)) continue;
            
            // Verifica presenza di quality value (;q=0.8)
            if (strpos($entry, ';q=') !== false) {
                list($lang, $quality) = explode(';q=', $entry, 2);
                $quality = (float) $quality;
            } else {
                $lang = $entry;
                $quality = 1.0;
            }
            
            // Estrai solo i primi 2 caratteri per il codice lingua
            $langCode = strtolower(substr(trim($lang), 0, 2));
            
            if (!empty($langCode) && ctype_alpha($langCode)) {
                $languages[] = [
                    'code' => $langCode,
                    'quality' => $quality,
                    'original' => trim($lang)
                ];
            }
        }
        
        // Ordina per qualità (priorità) decrescente
        usort($languages, function($a, $b) {
            return $b['quality'] <=> $a['quality'];
        });
        
        return $languages;
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
     * Ottiene testo tradotto - VERSIONE COMPLETA CON DATABASE ABILITATO
     * 
     * Ora legge le traduzioni dal database se disponibili
     */
    public function getText($contentKey, $fallbackText = null) {
        $cacheKey = "text_{$contentKey}_{$this->currentLanguage}";
        
        if (isset($this->contentCache[$cacheKey])) {
            return $this->contentCache[$cacheKey];
        }
        
        // Se lingua italiana o database non disponibile, usa fallback
        if ($this->currentLanguage === $this->defaultLanguage || !$this->db) {
            $text = $fallbackText ?? $contentKey;
            $this->contentCache[$cacheKey] = $text;
            return $text;
        }
        
        try {
            // Cerca traduzione nel database
            $stmt = $this->db->prepare("
                SELECT sct.translated_content as content
                FROM static_content_translations sct
                JOIN static_content sc ON sct.static_content_id = sc.id
                WHERE sc.content_key = ? AND sct.language_code = ?
            ");
            $stmt->execute([$contentKey, $this->currentLanguage]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result && !empty($result['content'])) {
                $text = $result['content'];
            } else {
                // Fallback alla lingua inglese se disponibile
                if ($this->currentLanguage !== $this->fallbackLanguage) {
                    $stmt->execute([$contentKey, $this->fallbackLanguage]);
                    $result = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($result && !empty($result['content'])) {
                        $text = $result['content'];
                    } else {
                        $text = $fallbackText ?? $contentKey;
                    }
                } else {
                    $text = $fallbackText ?? $contentKey;
                }
            }
            
        } catch (Exception $e) {
            error_log("Errore recupero traduzione per '$contentKey': " . $e->getMessage());
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
            return '/' . ltrim($path, '/');
        }
        
        $url = '/' . ltrim($path, '/');
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
    
    /**
     * Ottiene tutte le lingue supportate con informazioni complete
     */
    public function getAllLanguages() {
        $languages = [];
        
        foreach ($this->supportedLanguages as $code) {
            $languages[] = [
                'code' => $code,
                'name' => $this->getLanguageName($code),
                'native_name' => $this->getLanguageNativeName($code),
                'is_current' => $code === $this->currentLanguage,
                'is_default' => $code === $this->defaultLanguage
            ];
        }
        
        return $languages;
    }
    
    /**
     * Ottiene nome lingua in inglese
     */
    private function getLanguageName($code) {
        $names = [
            'it' => 'Italian',
            'en' => 'English', 
            'fr' => 'French',
            'de' => 'German',
            'es' => 'Spanish'
        ];
        
        return $names[$code] ?? $code;
    }
    
    /**
     * Ottiene nome lingua nativo
     */
    private function getLanguageNativeName($code) {
        $names = [
            'it' => 'Italiano',
            'en' => 'English',
            'fr' => 'Français', 
            'de' => 'Deutsch',
            'es' => 'Español'
        ];
        
        return $names[$code] ?? $code;
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