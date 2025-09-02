<?php
/**
 * API Endpoint per Gestione Lingua Utente
 * 
 * Riceve richieste AJAX dal sistema di rilevamento lingua JavaScript
 * e gestisce la sessione/cookie per memorizzare la preferenza lingua.
 */

require_once __DIR__ . '/../includes/config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Lingue supportate
$supportedLanguages = ['it', 'en', 'fr', 'de', 'es'];
$defaultLanguage = 'it';
$fallbackLanguage = 'en';

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';
        
        switch ($action) {
            case 'set_language':
                $language = $_POST['language'] ?? '';
                
                // Valida lingua
                if (!in_array($language, $supportedLanguages)) {
                    throw new Exception("Lingua non supportata: $language");
                }
                
                // Salva in sessione
                $_SESSION['user_language'] = $language;
                
                // Salva in cookie (30 giorni)
                $cookieExpiration = time() + (30 * 24 * 60 * 60);
                setcookie('site_language', $language, $cookieExpiration, '/');
                
                // Log per debug
                error_log("Lingua utente impostata: $language");
                
                echo json_encode([
                    'success' => true,
                    'language' => $language,
                    'message' => 'Lingua impostata con successo',
                    'timestamp' => date('Y-m-d H:i:s')
                ]);
                break;
                
            case 'get_language':
                $currentLanguage = getCurrentUserLanguage();
                
                echo json_encode([
                    'success' => true,
                    'language' => $currentLanguage,
                    'supported_languages' => $supportedLanguages,
                    'default_language' => $defaultLanguage,
                    'fallback_language' => $fallbackLanguage
                ]);
                break;
                
            default:
                throw new Exception("Azione non supportata: $action");
        }
        
    } elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // GET per ottenere lingua corrente
        $currentLanguage = getCurrentUserLanguage();
        
        echo json_encode([
            'success' => true,
            'language' => $currentLanguage,
            'supported_languages' => $supportedLanguages,
            'default_language' => $defaultLanguage,
            'fallback_language' => $fallbackLanguage
        ]);
        
    } else {
        throw new Exception('Metodo HTTP non supportato');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
    // Log errore
    error_log("Errore API language.php: " . $e->getMessage());
}

/**
 * Ottiene la lingua corrente dell'utente con sistema di fallback
 */
function getCurrentUserLanguage() {
    global $supportedLanguages, $defaultLanguage, $fallbackLanguage;
    
    // 1. Prima controlla parametro URL
    if (isset($_GET['lang']) && in_array($_GET['lang'], $supportedLanguages)) {
        return $_GET['lang'];
    }
    
    // 2. Controlla sessione
    if (isset($_SESSION['user_language']) && in_array($_SESSION['user_language'], $supportedLanguages)) {
        return $_SESSION['user_language'];
    }
    
    // 3. Controlla cookie
    if (isset($_COOKIE['site_language']) && in_array($_COOKIE['site_language'], $supportedLanguages)) {
        return $_COOKIE['site_language'];
    }
    
    // 4. Prova a rilevare da Accept-Language header
    if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
        $acceptLanguages = explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']);
        
        foreach ($acceptLanguages as $lang) {
            $langCode = strtolower(substr(trim($lang), 0, 2));
            if (in_array($langCode, $supportedLanguages)) {
                return $langCode;
            }
        }
    }
    
    // 5. Fallback alla lingua di default
    return $defaultLanguage;
}
?>