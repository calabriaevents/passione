<?php
require_once '../includes/config.php';
require_once '../includes/database.php';

// Rate limiting per newsletter
session_start();
$client_ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$rate_limit_key = 'newsletter_rate_limit_' . $client_ip;
$current_time = time();
$rate_limit = 5; // 5 iscrizioni per ora

if (!isset($_SESSION[$rate_limit_key])) {
    $_SESSION[$rate_limit_key] = [];
}

$_SESSION[$rate_limit_key] = array_filter($_SESSION[$rate_limit_key], function($timestamp) use ($current_time) {
    return ($current_time - $timestamp) < 3600; // 1 ora
});

if (count($_SESSION[$rate_limit_key]) >= $rate_limit) {
    http_response_code(429);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Troppe iscrizioni. Riprova tra un\'ora.']);
    exit;
}

header('Content-Type: application/json');
// CORS più restrittivo - solo dal dominio principale
$allowed_origins = [SITE_URL, 'http://localhost', 'https://localhost'];
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowed_origins)) {
    header("Access-Control-Allow-Origin: $origin");
}
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse([
        'success' => false,
        'message' => 'Metodo non consentito'
    ], 405);
}

try {
    $db = new Database();

    // Validazione CSRF
    $csrf_token = $_POST[CSRF_TOKEN_NAME] ?? '';
    if (!validateCSRFToken($csrf_token)) {
        jsonResponse([
            'success' => false,
            'message' => 'Token di sicurezza non valido'
        ], 403);
    }

    // Ottieni e valida dati dal form
    $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
    $name = isset($_POST['name']) ? sanitize($_POST['name']) : '';
    $interests = isset($_POST['interests']) && is_array($_POST['interests']) ? array_map('sanitize', $_POST['interests']) : [];
    
    // Validazione aggiuntiva
    if (strlen($name) > 100) {
        jsonResponse([
            'success' => false,
            'message' => 'Nome troppo lungo (max 100 caratteri)'
        ], 400);
    }
    
    if (count($interests) > 10) {
        jsonResponse([
            'success' => false,
            'message' => 'Troppi interessi selezionati (max 10)'
        ], 400);
    }

    // Validazione
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        jsonResponse([
            'success' => false,
            'message' => 'Indirizzo email non valido'
        ], 400);
    }

    // Controlla se l'email è già registrata (usa metodo sicuro)
    $existing = $db->getNewsletterSubscriber($email);

    if ($existing) {
        jsonResponse([
            'success' => false,
            'message' => 'Questo indirizzo email è già iscritto alla newsletter'
        ], 400);
    }

    // Crea tabella newsletter se non esiste
    $db->pdo->exec("
        CREATE TABLE IF NOT EXISTS newsletter_subscribers (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            email TEXT NOT NULL UNIQUE,
            name TEXT,
            interests TEXT,
            status TEXT DEFAULT 'active',
            confirmation_token TEXT,
            confirmed_at DATETIME,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");

    // Genera token di conferma
    $confirmationToken = bin2hex(random_bytes(32));

    // Inserisci nuovo iscritto
    $stmt = $db->pdo->prepare('
        INSERT INTO newsletter_subscribers (email, name, interests, confirmation_token)
        VALUES (?, ?, ?, ?)
    ');

    $interestsJson = json_encode($interests);
    $stmt->execute([$email, $name, $interestsJson, $confirmationToken]);

    $subscriberId = $db->pdo->lastInsertId();
    
    // Aggiorna rate limiting
    $_SESSION[$rate_limit_key][] = $current_time;

    // Invia email di conferma (simulata)
    $confirmationLink = SITE_URL . "/conferma-newsletter.php?token=$confirmationToken";

    // Log sicuro dell'iscrizione (senza email completa)
    $emailHash = substr(md5($email), 0, 8);
    error_log("Nuova iscrizione newsletter: hash_$emailHash (ID: $subscriberId)");

    jsonResponse([
        'success' => true,
        'message' => 'Iscrizione avvenuta con successo! Controlla la tua email per confermare.',
        'subscriber_id' => $subscriberId,
        'confirmation_required' => true
    ]);

} catch (PDOException $e) {
    error_log('Errore database newsletter: ' . $e->getMessage());

    if ($e->getCode() == 23000) { // Constraint violation
        jsonResponse([
            'success' => false,
            'message' => 'Questo indirizzo email è già iscritto alla newsletter'
        ], 400);
    }

    jsonResponse([
        'success' => false,
        'message' => 'Errore durante l\'iscrizione. Riprova più tardi.'
    ], 500);

} catch (Exception $e) {
    error_log('Errore API newsletter: ' . $e->getMessage());

    jsonResponse([
        'success' => false,
        'message' => 'Errore interno del server'
    ], 500);
}

// Funzione per inviare email di conferma (placeholder)
function sendConfirmationEmail($email, $name, $confirmationLink) {
    // Qui andrebbe l'implementazione dell'invio email
    // Usando PHPMailer, SwiftMailer, o servizi come SendGrid, Mailgun, etc.

    $subject = 'Conferma la tua iscrizione a Passione Calabria';
    $message = "
        Ciao " . ($name ?: 'amico') . ",

        Grazie per esserti iscritto alla newsletter di Passione Calabria!

        Per completare l'iscrizione, clicca sul link seguente:
        $confirmationLink

        Se non hai richiesto questa iscrizione, ignora questa email.

        A presto,
        Il team di Passione Calabria
    ";

    // Simulazione invio email
    return true;
}

// Funzione per gestire la disiscrizione
function unsubscribe($email, $token = null) {
    global $db;

    try {
        if ($token) {
            // Disiscrizione tramite token
            $stmt = $db->pdo->prepare('
                UPDATE newsletter_subscribers
                SET status = ?, updated_at = CURRENT_TIMESTAMP
                WHERE email = ? AND confirmation_token = ?
            ');
            $stmt->execute(['unsubscribed', $email, $token]);
        } else {
            // Disiscrizione diretta
            $stmt = $db->pdo->prepare('
                UPDATE newsletter_subscribers
                SET status = ?, updated_at = CURRENT_TIMESTAMP
                WHERE email = ?
            ');
            $stmt->execute(['unsubscribed', $email]);
        }

        return $stmt->rowCount() > 0;

    } catch (Exception $e) {
        error_log('Errore disiscrizione newsletter: ' . $e->getMessage());
        return false;
    }
}

// API endpoint per disiscrizione
if (isset($_GET['action']) && $_GET['action'] === 'unsubscribe') {
    $email = filter_var($_GET['email'] ?? '', FILTER_VALIDATE_EMAIL);
    $token = $_GET['token'] ?? '';

    if (!$email) {
        jsonResponse([
            'success' => false,
            'message' => 'Email non valida'
        ], 400);
    }

    if (unsubscribe($email, $token)) {
        jsonResponse([
            'success' => true,
            'message' => 'Disiscrizione avvenuta con successo'
        ]);
    } else {
        jsonResponse([
            'success' => false,
            'message' => 'Errore durante la disiscrizione'
        ], 500);
    }
}

// API endpoint per conferma iscrizione
if (isset($_GET['action']) && $_GET['action'] === 'confirm') {
    $token = $_GET['token'] ?? '';

    if (empty($token)) {
        jsonResponse([
            'success' => false,
            'message' => 'Token di conferma mancante'
        ], 400);
    }

    try {
        $stmt = $db->pdo->prepare('
            UPDATE newsletter_subscribers
            SET status = ?, confirmed_at = CURRENT_TIMESTAMP, updated_at = CURRENT_TIMESTAMP
            WHERE confirmation_token = ? AND status = ?
        ');
        $stmt->execute(['confirmed', $token, 'active']);

        if ($stmt->rowCount() > 0) {
            jsonResponse([
                'success' => true,
                'message' => 'Iscrizione confermata con successo!'
            ]);
        } else {
            jsonResponse([
                'success' => false,
                'message' => 'Token non valido o già utilizzato'
            ], 400);
        }

    } catch (Exception $e) {
        error_log('Errore conferma newsletter: ' . $e->getMessage());
        jsonResponse([
            'success' => false,
            'message' => 'Errore durante la conferma'
        ], 500);
    }
}
?>
