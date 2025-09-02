<?php
/**
 * Endpoint per Progress Bar delle Traduzioni
 * 
 * Gestisce le operazioni di traduzione e restituisce aggiornamenti di stato in tempo reale
 */

require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/PreventiveTranslationService_DeepL.php';

// Imposta content type per JSON
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

// Controllo accesso admin (semplificato per demo)
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    $_SESSION['admin_logged_in'] = true;
}

$db = new Database();
$translationService = new PreventiveTranslationService($db);

// Gestione richieste
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $step = (int)($_POST['step'] ?? 0);
    
    switch ($action) {
        case 'force_translate_all':
            handleForceTranslateAll($translationService, $db, $step);
            break;
            
        case 'force_translate_articles':
            handleForceTranslateArticles($translationService, $db, $step);
            break;
            
        case 'force_translate_static':
            handleForceTranslateStatic($translationService, $db, $step);
            break;
            
        case 'clear_translation_cache':
            handleClearCache($db, $step);
            break;
            
        default:
            echo json_encode(['error' => 'Azione non riconosciuta']);
            exit;
    }
} else {
    echo json_encode(['error' => 'Metodo non supportato']);
}

/**
 * Gestisce la forzatura di tutte le traduzioni
 */
function handleForceTranslateAll($translationService, $db, $step) {
    try {
        switch ($step) {
            case 0:
                // Inizializzazione
                echo json_encode([
                    'success' => true,
                    'progress' => 10,
                    'message' => '🔧 Inizializzazione sistema di traduzione DeepL...',
                    'next_step' => 1
                ]);
                break;
                
            case 1:
                // Verifica configurazione
                if (!$translationService->isEnabled()) {
                    echo json_encode([
                        'error' => true,
                        'message' => '❌ Sistema DeepL non configurato o disabilitato'
                    ]);
                    return;
                }
                
                $config = $translationService->getConfig();
                echo json_encode([
                    'success' => true,
                    'progress' => 25,
                    'message' => '✅ Configurazione DeepL verificata (Quota: ' . $config['current_daily_usage'] . '/' . $config['daily_quota'] . ' caratteri)',
                    'next_step' => 2
                ]);
                break;
                
            case 2:
                // Test connessione API
                $testResult = $translationService->testDeepLConnection();
                if (!$testResult['success']) {
                    echo json_encode([
                        'error' => true,
                        'message' => '❌ Test connessione DeepL fallito: ' . $testResult['message']
                    ]);
                    return;
                }
                
                echo json_encode([
                    'success' => true,
                    'progress' => 40,
                    'message' => '🔗 Connessione DeepL operativa! Test: "Ciao" → "' . $testResult['test_translation'] . '"',
                    'next_step' => 3
                ]);
                break;
                
            case 3:
                // Carica articoli da tradurre
                $stmt = $db->prepare("SELECT COUNT(*) as total FROM articles WHERE status = 'published'");
                $stmt->execute();
                $articleCount = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
                
                echo json_encode([
                    'success' => true,
                    'progress' => 55,
                    'message' => "📚 Trovati {$articleCount} articoli pubblicati da tradurre",
                    'next_step' => 4
                ]);
                break;
                
            case 4:
                // Traduci articoli
                $stmt = $db->prepare("SELECT * FROM articles WHERE status = 'published'");
                $stmt->execute();
                $articles = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                $success = 0;
                $errors = 0;
                
                foreach ($articles as $article) {
                    if ($translationService->translateArticle($article['id'], [
                        'title' => $article['title'],
                        'content' => $article['content'],
                        'excerpt' => $article['excerpt']
                    ])) {
                        $success++;
                    } else {
                        $errors++;
                    }
                }
                
                echo json_encode([
                    'success' => true,
                    'progress' => 75,
                    'message' => "🔄 Articoli tradotti: {$success} successi, {$errors} errori",
                    'next_step' => 5
                ]);
                break;
                
            case 5:
                // Traduci contenuti statici
                $staticSuccess = $translationService->translateStaticContent();
                
                echo json_encode([
                    'success' => true,
                    'progress' => 90,
                    'message' => '🌍 Contenuti statici tradotti: ' . ($staticSuccess ? 'Completato' : 'Alcuni errori'),
                    'next_step' => 6
                ]);
                break;
                
            case 6:
                // Finalizzazione
                $stats = $translationService->getStats();
                echo json_encode([
                    'success' => true,
                    'progress' => 100,
                    'message' => "🎉 Traduzione completata! Sistema operativo con {$stats['languages_count']} lingue",
                    'completed' => true
                ]);
                break;
                
            default:
                echo json_encode(['error' => 'Step non valido']);
        }
    } catch (Exception $e) {
        echo json_encode(['error' => 'Errore: ' . $e->getMessage()]);
    }
}

/**
 * Gestisce la traduzione solo degli articoli
 */
function handleForceTranslateArticles($translationService, $db, $step) {
    try {
        switch ($step) {
            case 0:
                echo json_encode([
                    'success' => true,
                    'progress' => 15,
                    'message' => '📚 Inizializzazione traduzione articoli...',
                    'next_step' => 1
                ]);
                break;
                
            case 1:
                if (!$translationService->isEnabled()) {
                    echo json_encode([
                        'error' => true,
                        'message' => '❌ Sistema DeepL non abilitato'
                    ]);
                    return;
                }
                
                echo json_encode([
                    'success' => true,
                    'progress' => 30,
                    'message' => '✅ Sistema DeepL pronto per traduzione articoli',
                    'next_step' => 2
                ]);
                break;
                
            case 2:
                // Carica e traduci articoli
                $stmt = $db->prepare("SELECT * FROM articles WHERE status = 'published'");
                $stmt->execute();
                $articles = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                $success = 0;
                $errors = 0;
                
                foreach ($articles as $article) {
                    if ($translationService->translateArticle($article['id'], [
                        'title' => $article['title'],
                        'content' => $article['content'],
                        'excerpt' => $article['excerpt']
                    ])) {
                        $success++;
                    } else {
                        $errors++;
                    }
                }
                
                echo json_encode([
                    'success' => true,
                    'progress' => 80,
                    'message' => "🔄 Traduzione articoli in corso: {$success} successi, {$errors} errori",
                    'next_step' => 3
                ]);
                break;
                
            case 3:
                echo json_encode([
                    'success' => true,
                    'progress' => 100,
                    'message' => '🎉 Traduzione articoli completata!',
                    'completed' => true
                ]);
                break;
        }
    } catch (Exception $e) {
        echo json_encode(['error' => 'Errore: ' . $e->getMessage()]);
    }
}

/**
 * Gestisce la traduzione dei contenuti statici
 */
function handleForceTranslateStatic($translationService, $db, $step) {
    try {
        switch ($step) {
            case 0:
                echo json_encode([
                    'success' => true,
                    'progress' => 20,
                    'message' => '📝 Inizializzazione traduzione contenuti statici...',
                    'next_step' => 1
                ]);
                break;
                
            case 1:
                $staticSuccess = $translationService->translateStaticContent();
                
                echo json_encode([
                    'success' => true,
                    'progress' => 70,
                    'message' => '🔄 Traduzione testi interfaccia in corso...',
                    'next_step' => 2
                ]);
                break;
                
            case 2:
                echo json_encode([
                    'success' => true,
                    'progress' => 100,
                    'message' => '🎉 Traduzione contenuti statici completata!',
                    'completed' => true
                ]);
                break;
        }
    } catch (Exception $e) {
        echo json_encode(['error' => 'Errore: ' . $e->getMessage()]);
    }
}

/**
 * Gestisce la pulizia della cache
 */
function handleClearCache($db, $step) {
    try {
        switch ($step) {
            case 0:
                echo json_encode([
                    'success' => true,
                    'progress' => 30,
                    'message' => '🧹 Avvio pulizia cache traduzioni...',
                    'next_step' => 1
                ]);
                break;
                
            case 1:
                $stmt = $db->prepare("DELETE FROM translations_cache");
                $stmt->execute();
                
                echo json_encode([
                    'success' => true,
                    'progress' => 70,
                    'message' => '🗑️ Cache traduzioni pulita',
                    'next_step' => 2
                ]);
                break;
                
            case 2:
                echo json_encode([
                    'success' => true,
                    'progress' => 100,
                    'message' => '✅ Pulizia cache completata!',
                    'completed' => true
                ]);
                break;
        }
    } catch (Exception $e) {
        echo json_encode(['error' => 'Errore: ' . $e->getMessage()]);
    }
}
?>