<?php
/**
 * Servizio per Traduzione Preventiva - Versione Sicura
 * 
 * Versione ottimizzata del servizio di traduzione che evita timeout
 * e gestisce gli errori in modo più robusto.
 */

require_once __DIR__ . '/config.php';

class PreventiveTranslationService {
    
    private $db;
    private $config;
    private $supportedLanguages;
    private $defaultLanguage = 'it';
    private $fallbackLanguage = 'en';
    private $initialized = false;
    
    public function __construct(Database $database) {
        $this->db = $database;
        // Inizializzazione lazy - solo quando necessaria
    }
    
    /**
     * Inizializzazione lazy del servizio
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
            // Query semplice con timeout implicito
            $stmt = $this->db->prepare("SELECT * FROM translation_config WHERE is_enabled = 1 LIMIT 1");
            if (!$stmt) {
                throw new Exception("Errore preparazione query config");
            }
            
            $stmt->execute();
            $this->config = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$this->config) {
                // Configurazione di default se non trovata
                $this->config = [
                    'id' => 1,
                    'api_provider' => 'google',
                    'api_key' => null,
                    'is_enabled' => 0,
                    'daily_quota' => 1000,
                    'current_daily_usage' => 0,
                    'last_reset_date' => date('Y-m-d')
                ];
            }
        } catch (Exception $e) {
            error_log("Errore caricamento config traduzione: " . $e->getMessage());
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
            
            // Se non ci sono lingue nel DB, usa default
            if (empty($this->supportedLanguages)) {
                $this->supportedLanguages = [
                    'it' => ['code' => 'it', 'name' => 'Italiano', 'is_default' => 1],
                    'en' => ['code' => 'en', 'name' => 'English', 'is_fallback' => 1],
                    'fr' => ['code' => 'fr', 'name' => 'Français'],
                    'de' => ['code' => 'de', 'name' => 'Deutsch'],
                    'es' => ['code' => 'es', 'name' => 'Español']
                ];
            }
        } catch (Exception $e) {
            error_log("Errore caricamento lingue: " . $e->getMessage());
            // Lingue fallback
            $this->supportedLanguages = [
                'it' => ['code' => 'it', 'name' => 'Italiano', 'is_default' => 1],
                'en' => ['code' => 'en', 'name' => 'English', 'is_fallback' => 1]
            ];
        }
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
     * Verifica se il servizio è abilitato
     */
    public function isEnabled() {
        if (!$this->initialize()) {
            return false;
        }
        return $this->config && $this->config['is_enabled'] == 1;
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
            'is_enabled' => $this->config['is_enabled'] == 1,
            'api_provider' => $this->config['api_provider'],
            'daily_usage' => $this->config['current_daily_usage'] ?? 0,
            'daily_quota' => $this->config['daily_quota'] ?? 0
        ];
    }
    
    /**
     * Placeholder per funzioni avanzate (da implementare quando necessario)
     */
    public function translateArticle($articleId, $articleData) {
        if (!$this->isEnabled()) {
            error_log("Servizio traduzione non abilitato per articolo {$articleId}");
            return false;
        }
        
        // Implementazione futura
        return true;
    }
    
    public function translateStaticContent() {
        if (!$this->isEnabled()) {
            error_log("Servizio traduzione non abilitato per contenuti statici");
            return false;
        }
        
        // Implementazione futura  
        return true;
    }
    
    public function getArticleTranslation($articleId, $langCode) {
        // Implementazione base - ritorna null per ora
        return null;
    }
    
    public function getStaticContentTranslation($contentKey, $langCode) {
        // Implementazione base - ritorna la chiave per ora
        return $contentKey;
    }
}
?>