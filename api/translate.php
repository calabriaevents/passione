<?php
/**
 * PASSIONE CALABRIA - SISTEMA TRADUZIONE MULTILINGUE
 * 
 * API Endpoint per traduzioni intelligenti con cache e fallback
 * 
 * Features:
 * - Cache intelligente con hash SHA-256
 * - Sistema fallback multi-provider (Google, DeepL, Yandex)
 * - Rate limiting e controllo quote
 * - Statistiche dettagliate
 * - Gestione errori robusta
 * - Preservazione HTML tags
 * 
 * @author Passione Calabria Team
 * @version 2.0
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'Only POST requests allowed',
        'code' => 'METHOD_NOT_ALLOWED'
    ]);
    exit;
}

require_once '../includes/config.php';
require_once '../includes/database.php';

/**
 * Translation System Class
 * Gestisce tutte le operazioni di traduzione con cache intelligente
 */
class TranslationSystem {
    private $db;
    private $pdo;
    private $rateLimitWindow = 60; // seconds
    private $maxRequestsPerWindow = 100;
    
    public function __construct() {
        $this->db = new Database();
        $this->pdo = $this->db->pdo;
    }
    
    /**
     * Processa richiesta di traduzione
     */
    public function processTranslationRequest() {
        try {
            // Valida input
            $input = $this->validateInput();
            if (!$input['valid']) {
                return $this->errorResponse($input['error'], 'INVALID_INPUT');
            }
            
            $text = $input['text'];
            $targetLang = $input['target_lang'];
            $sourceLang = $input['source_lang'] ?? 'it';
            $context = $input['context'] ?? [];
            
            // Controlla rate limiting
            if (!$this->checkRateLimit()) {
                return $this->errorResponse('Rate limit exceeded. Try again later.', 'RATE_LIMIT_EXCEEDED');
            }
            
            // Genera hash del testo per cache
            $textHash = $this->generateTextHash($text);
            
            // Controlla cache
            $cachedTranslation = $this->getCachedTranslation($textHash, $targetLang);
            if ($cachedTranslation) {
                // Aggiorna statistiche cache hit
                $this->updateCacheStatistics($cachedTranslation['api_provider'], $targetLang, true);
                
                return $this->successResponse([
                    'translated_text' => $cachedTranslation['translated_text'],
                    'source_lang' => $sourceLang,
                    'target_lang' => $targetLang,
                    'provider' => $cachedTranslation['api_provider'],
                    'from_cache' => true,
                    'confidence_score' => floatval($cachedTranslation['confidence_score']),
                    'cached_at' => $cachedTranslation['created_at']
                ]);
            }
            
            // Cache miss - traduci tramite API
            $translationResult = $this->translateViaAPI($text, $sourceLang, $targetLang);
            
            if (!$translationResult['success']) {
                return $this->errorResponse($translationResult['error'], $translationResult['code'] ?? 'TRANSLATION_FAILED');
            }
            
            // Salva in cache
            $this->cacheTranslation(
                $textHash,
                $text,
                $translationResult['translated_text'],
                $sourceLang,
                $targetLang,
                $translationResult['provider'],
                $translationResult['confidence_score'] ?? 1.0,
                $context
            );
            
            // Aggiorna statistiche API call
            $this->updateCacheStatistics($translationResult['provider'], $targetLang, false);
            
            return $this->successResponse([
                'translated_text' => $translationResult['translated_text'],
                'source_lang' => $sourceLang,
                'target_lang' => $targetLang,
                'provider' => $translationResult['provider'],
                'from_cache' => false,
                'confidence_score' => $translationResult['confidence_score'] ?? 1.0,
                'processing_time' => $translationResult['processing_time'] ?? 0
            ]);
            
        } catch (Exception $e) {
            error_log("Translation System Error: " . $e->getMessage());
            return $this->errorResponse('Internal server error', 'INTERNAL_ERROR');
        }
    }
    
    /**
     * Valida input della richiesta
     */
    private function validateInput() {
        $rawInput = file_get_contents('php://input');
        $input = json_decode($rawInput, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return ['valid' => false, 'error' => 'Invalid JSON input'];
        }
        
        if (empty($input['text'])) {
            return ['valid' => false, 'error' => 'Text parameter is required'];
        }
        
        if (empty($input['target_lang'])) {
            return ['valid' => false, 'error' => 'Target language parameter is required'];
        }
        
        // Controlla lunghezza testo (max 5000 caratteri)
        if (strlen($input['text']) > 5000) {
            return ['valid' => false, 'error' => 'Text too long (max 5000 characters)'];
        }
        
        // Verifica che la lingua target sia supportata
        $stmt = $this->pdo->prepare("SELECT 1 FROM supported_languages WHERE code = ? AND is_active = 1");
        $stmt->execute([$input['target_lang']]);
        if (!$stmt->fetch()) {
            return ['valid' => false, 'error' => 'Target language not supported or inactive'];
        }
        
        return [
            'valid' => true,
            'text' => trim($input['text']),
            'target_lang' => $input['target_lang'],
            'source_lang' => $input['source_lang'] ?? 'it',
            'context' => $input['context'] ?? []
        ];
    }
    
    /**
     * Controlla rate limiting
     */
    private function checkRateLimit() {
        $clientIP = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $windowStart = time() - $this->rateLimitWindow;
        
        // Per ora implementazione semplice con sessione
        if (!isset($_SESSION)) {
            session_start();
        }
        
        $sessionKey = 'translation_requests_' . $clientIP;
        $requests = $_SESSION[$sessionKey] ?? [];
        
        // Rimuovi richieste fuori dalla finestra temporale
        $requests = array_filter($requests, function($timestamp) use ($windowStart) {
            return $timestamp > $windowStart;
        });
        
        if (count($requests) >= $this->maxRequestsPerWindow) {
            return false;
        }
        
        // Aggiungi richiesta corrente
        $requests[] = time();
        $_SESSION[$sessionKey] = $requests;
        
        return true;
    }
    
    /**
     * Genera hash SHA-256 del testo per cache
     */
    private function generateTextHash($text) {
        // Normalizza il testo per cache consistente
        $normalizedText = trim(preg_replace('/\s+/', ' ', $text));
        return hash('sha256', $normalizedText);
    }
    
    /**
     * Recupera traduzione dalla cache
     */
    private function getCachedTranslation($textHash, $targetLang) {
        $stmt = $this->pdo->prepare("
            SELECT * FROM translations_cache 
            WHERE original_text_hash = ? AND target_lang = ?
            ORDER BY created_at DESC 
            LIMIT 1
        ");
        $stmt->execute([$textHash, $targetLang]);
        $result = $stmt->fetch();
        
        if ($result) {
            // Aggiorna contatori utilizzo e last_used
            $updateStmt = $this->pdo->prepare("
                UPDATE translations_cache 
                SET usage_count = usage_count + 1, last_used_at = datetime('now')
                WHERE id = ?
            ");
            $updateStmt->execute([$result['id']]);
        }
        
        return $result;
    }
    
    /**
     * Traduce tramite API con sistema fallback
     */
    private function translateViaAPI($text, $sourceLang, $targetLang) {
        $startTime = microtime(true);
        
        // Ottieni provider configurati in ordine di priorità
        $providers = $this->getActiveProviders();
        
        if (empty($providers)) {
            return [
                'success' => false,
                'error' => 'No translation providers configured',
                'code' => 'NO_PROVIDERS'
            ];
        }
        
        $lastError = null;
        
        // Prova ogni provider in ordine di priorità
        foreach ($providers as $provider) {
            try {
                $result = $this->callTranslationAPI($provider, $text, $sourceLang, $targetLang);
                
                if ($result['success']) {
                    $endTime = microtime(true);
                    $result['processing_time'] = round(($endTime - $startTime) * 1000, 2); // ms
                    return $result;
                }
                
                $lastError = $result['error'] ?? 'Unknown error from ' . $provider['api_provider'];
                
            } catch (Exception $e) {
                $lastError = $provider['api_provider'] . ': ' . $e->getMessage();
                error_log("Translation API Error ({$provider['api_provider']}): " . $e->getMessage());
            }
        }
        
        return [
            'success' => false,
            'error' => 'All translation providers failed. Last error: ' . $lastError,
            'code' => 'ALL_PROVIDERS_FAILED'
        ];
    }
    
    /**
     * Ottieni provider attivi ordinati per priorità
     */
    private function getActiveProviders() {
        $stmt = $this->pdo->query("
            SELECT * FROM translation_settings 
            WHERE is_active = 1 AND api_key != ''
            ORDER BY priority ASC, api_provider ASC
        ");
        return $stmt->fetchAll();
    }
    
    /**
     * Chiama API specifica per traduzione
     */
    private function callTranslationAPI($provider, $text, $sourceLang, $targetLang) {
        switch ($provider['api_provider']) {
            case 'google':
                return $this->callGoogleTranslateAPI($provider['api_key'], $text, $sourceLang, $targetLang);
            
            case 'deepl':
                return $this->callDeepLAPI($provider['api_key'], $text, $sourceLang, $targetLang);
            
            case 'yandex':
                return $this->callYandexTranslateAPI($provider['api_key'], $text, $sourceLang, $targetLang);
            
            default:
                return [
                    'success' => false,
                    'error' => 'Unknown provider: ' . $provider['api_provider']
                ];
        }
    }
    
    /**
     * Google Translate API
     */
    private function callGoogleTranslateAPI($apiKey, $text, $sourceLang, $targetLang) {
        $url = 'https://translation.googleapis.com/language/translate/v2';
        
        $data = [
            'key' => $apiKey,
            'q' => $text,
            'source' => $sourceLang,
            'target' => $targetLang,
            'format' => 'html'
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded'
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            return [
                'success' => false,
                'error' => 'cURL error: ' . $error
            ];
        }
        
        if ($httpCode !== 200) {
            return [
                'success' => false,
                'error' => 'HTTP error: ' . $httpCode
            ];
        }
        
        $result = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE || !isset($result['data']['translations'][0]['translatedText'])) {
            return [
                'success' => false,
                'error' => 'Invalid API response'
            ];
        }
        
        return [
            'success' => true,
            'translated_text' => $result['data']['translations'][0]['translatedText'],
            'provider' => 'google',
            'confidence_score' => 1.0
        ];
    }
    
    /**
     * DeepL API
     */
    private function callDeepLAPI($apiKey, $text, $sourceLang, $targetLang) {
        $url = 'https://api-free.deepl.com/v2/translate';
        
        // DeepL ha codici lingua diversi per alcune lingue
        $deeplTargetLang = $this->mapToDeepLLanguageCode($targetLang);
        $deeplSourceLang = $this->mapToDeepLLanguageCode($sourceLang);
        
        $data = [
            'auth_key' => $apiKey,
            'text' => $text,
            'source_lang' => strtoupper($deeplSourceLang),
            'target_lang' => strtoupper($deeplTargetLang),
            'tag_handling' => 'html'
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded'
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            return [
                'success' => false,
                'error' => 'cURL error: ' . $error
            ];
        }
        
        if ($httpCode !== 200) {
            return [
                'success' => false,
                'error' => 'HTTP error: ' . $httpCode
            ];
        }
        
        $result = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE || !isset($result['translations'][0]['text'])) {
            return [
                'success' => false,
                'error' => 'Invalid API response'
            ];
        }
        
        return [
            'success' => true,
            'translated_text' => $result['translations'][0]['text'],
            'provider' => 'deepl',
            'confidence_score' => 0.95 // DeepL generalmente molto accurato
        ];
    }
    
    /**
     * Yandex Translate API
     */
    private function callYandexTranslateAPI($apiKey, $text, $sourceLang, $targetLang) {
        $url = 'https://translate.api.cloud.yandex.net/translate/v2/translate';
        
        $data = [
            'targetLanguageCode' => $targetLang,
            'sourceLanguageCode' => $sourceLang,
            'texts' => [$text],
            'format' => 'HTML'
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Api-Key ' . $apiKey
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            return [
                'success' => false,
                'error' => 'cURL error: ' . $error
            ];
        }
        
        if ($httpCode !== 200) {
            return [
                'success' => false,
                'error' => 'HTTP error: ' . $httpCode
            ];
        }
        
        $result = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE || !isset($result['translations'][0]['text'])) {
            return [
                'success' => false,
                'error' => 'Invalid API response'
            ];
        }
        
        return [
            'success' => true,
            'translated_text' => $result['translations'][0]['text'],
            'provider' => 'yandex',
            'confidence_score' => 0.85
        ];
    }
    
    /**
     * Mappa codici lingua per DeepL
     */
    private function mapToDeepLLanguageCode($langCode) {
        $mapping = [
            'en' => 'EN',
            'de' => 'DE',
            'fr' => 'FR',
            'es' => 'ES',
            'it' => 'IT',
            'pt' => 'PT',
            'pl' => 'PL',
            'nl' => 'NL',
            'ru' => 'RU',
            'ja' => 'JA',
            'zh' => 'ZH'
        ];
        
        return $mapping[$langCode] ?? strtoupper($langCode);
    }
    
    /**
     * Salva traduzione in cache
     */
    private function cacheTranslation($textHash, $originalText, $translatedText, $sourceLang, $targetLang, $provider, $confidenceScore, $context) {
        $stmt = $this->pdo->prepare("
            INSERT INTO translations_cache (
                original_text_hash, original_text, translated_text, 
                source_lang, target_lang, api_provider, confidence_score,
                page_url, element_selector, context_info
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $textHash,
            $originalText,
            $translatedText,
            $sourceLang,
            $targetLang,
            $provider,
            $confidenceScore,
            $context['page_url'] ?? null,
            $context['element_selector'] ?? null,
            json_encode($context)
        ]);
    }
    
    /**
     * Aggiorna statistiche utilizzo
     */
    private function updateCacheStatistics($provider, $targetLang, $fromCache) {
        $today = date('Y-m-d');
        
        // Controlla se esiste già record per oggi
        $stmt = $this->pdo->prepare("
            SELECT id FROM translation_stats 
            WHERE date = ? AND api_provider = ? AND target_lang = ?
        ");
        $stmt->execute([$today, $provider, $targetLang]);
        $existingId = $stmt->fetchColumn();
        
        if ($existingId) {
            // Aggiorna esistente
            if ($fromCache) {
                $stmt = $this->pdo->prepare("
                    UPDATE translation_stats 
                    SET cache_hits = cache_hits + 1, updated_at = datetime('now')
                    WHERE id = ?
                ");
                $stmt->execute([$existingId]);
            } else {
                $stmt = $this->pdo->prepare("
                    UPDATE translation_stats 
                    SET api_calls = api_calls + 1, updated_at = datetime('now')
                    WHERE id = ?
                ");
                $stmt->execute([$existingId]);
            }
        } else {
            // Crea nuovo record
            $stmt = $this->pdo->prepare("
                INSERT INTO translation_stats (date, api_provider, target_lang, cache_hits, api_calls)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$today, $provider, $targetLang, $fromCache ? 1 : 0, $fromCache ? 0 : 1]);
        }
    }
    
    /**
     * Response di successo
     */
    private function successResponse($data) {
        return [
            'success' => true,
            'data' => $data,
            'timestamp' => time()
        ];
    }
    
    /**
     * Response di errore
     */
    private function errorResponse($message, $code = 'GENERIC_ERROR') {
        http_response_code(400);
        return [
            'success' => false,
            'error' => $message,
            'code' => $code,
            'timestamp' => time()
        ];
    }
}

// === MAIN EXECUTION ===

try {
    $translationSystem = new TranslationSystem();
    $result = $translationSystem->processTranslationRequest();
    echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    error_log("Translation System Fatal Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Internal server error',
        'code' => 'SYSTEM_ERROR',
        'timestamp' => time()
    ], JSON_UNESCAPED_UNICODE);
}
?>