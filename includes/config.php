<?php
// Configurazione generale
define('SITE_NAME', 'Passione Calabria');
define('SITE_DESCRIPTION', 'La tua guida alla Calabria');
if (!defined('SITE_URL')) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)) ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $script_dir = str_replace(basename($_SERVER['SCRIPT_NAME']), '', $_SERVER['SCRIPT_NAME']);
    // Rimuovi 'includes/' dal path se presente, dato che config.php è in 'includes'
    $base_path = rtrim(preg_replace('/\/includes\/?$/', '/', $script_dir), '/');
    define('SITE_URL', $protocol . $host . $base_path);
}
define('ADMIN_EMAIL', 'admin@passionecalabria.it');

// Configurazione database
define('DB_PATH', __DIR__ . '/../passione_calabria.db');

// Configurazione upload
define('UPLOAD_PATH', __DIR__ . '/../uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB

// Configurazione sicurezza
define('ADMIN_PASSWORD_HASH', '$2y$10$MBplPXCsXkMXnftP0d1gF.V8gIh2vOuivmxLl/StMxMmeXD27..u.'); // Password: "CalabriaSicura2024!" (SICURA)
define('SESSION_LIFETIME', 3600); // 1 ora
define('CSRF_TOKEN_NAME', 'csrf_token');

// Sicurezza sessione migliorata (solo se headers non sono stati inviati)
if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', 1); // Sempre sicuro in produzione - usa HTTPS
}

// Timezone
date_default_timezone_set('Europe/Rome');

// Encoding (mbstring non disponibile, rimosso temporaneamente)
// mb_internal_encoding('UTF-8');

// Gestione errori
if ($_ENV['ENVIRONMENT'] ?? 'production' === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Avvia sessione sicura se non già avviata
if (session_status() === PHP_SESSION_NONE) {
    // Rigenera ID sessione per prevenire session fixation
    session_start();
    if (!isset($_SESSION['initiated'])) {
        session_regenerate_id(true);
        $_SESSION['initiated'] = true;
    }
    // Controlla scadenza sessione
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_LIFETIME)) {
        session_unset();
        session_destroy();
        session_start();
    }
    $_SESSION['last_activity'] = time();
}

// Funzioni di utility
if (!function_exists('sanitize')) {
    function sanitize($input) {
        if (is_array($input)) {
            return array_map('sanitize', $input);
        }
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('sanitizeSQL')) {
    function sanitizeSQL($input) {
        // Per SQLite, escape delle virgolette singole
        return str_replace("'", "''", $input);
    }
}

if (!function_exists('generateCSRFToken')) {
    function generateCSRFToken() {
        if (!isset($_SESSION[CSRF_TOKEN_NAME])) {
            $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
        }
        return $_SESSION[CSRF_TOKEN_NAME];
    }
}

if (!function_exists('validateCSRFToken')) {
    function validateCSRFToken($token) {
        return isset($_SESSION[CSRF_TOKEN_NAME]) && hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
    }
}

if (!function_exists('getCSRFTokenField')) {
    function getCSRFTokenField() {
        return '<input type="hidden" name="' . CSRF_TOKEN_NAME . '" value="' . generateCSRFToken() . '">';
    }
}

if (!function_exists('createSlug')) {
    function createSlug($text) {
        $text = strtolower($text);
        $text = preg_replace('/[àáâãäå]/', 'a', $text);
        $text = preg_replace('/[èéêë]/', 'e', $text);
        $text = preg_replace('/[ìíîï]/', 'i', $text);
        $text = preg_replace('/[òóôõö]/', 'o', $text);
        $text = preg_replace('/[ùúûü]/', 'u', $text);
        $text = preg_replace('/[ç]/', 'c', $text);
        $text = preg_replace('/[ñ]/', 'n', $text);
        $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
        $text = preg_replace('/[\s_-]+/', '-', $text);
        $text = trim($text, '-');
        return $text;
    }
}

if (!function_exists('formatDate')) {
    function formatDate($date) {
        return date('d/m/Y', strtotime($date));
    }
}

if (!function_exists('formatDateTime')) {
    function formatDateTime($datetime) {
        return date('d/m/Y H:i', strtotime($datetime));
    }
}

if (!function_exists('truncateText')) {
    function truncateText($text, $length = 150) {
        if (strlen($text) <= $length) {
            return $text;
        }
        return substr($text, 0, $length) . '...';
    }
}

if (!function_exists('isLoggedIn')) {
    function isLoggedIn() {
        return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
    }
}

if (!function_exists('requireLogin')) {
    function requireLogin() {
        // LOGIN DISABILITATO - Accesso libero a tutte le funzioni admin
        return true;
    }
}

if (!function_exists('redirectTo')) {
    function redirectTo($url) {
        header("Location: $url");
        exit;
    }
}

if (!function_exists('jsonResponse')) {
    function jsonResponse($data, $status = 200) {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}
?>
