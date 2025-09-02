<?php
/**
 * Pannello Admin - Gestione Traduzione (VERSIONE PULITA)
 * 
 * Interfaccia per gestire le traduzioni del sito
 */

require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/PreventiveTranslationService_DeepL.php';

// Controllo accesso admin - DISABILITATO
// // requireLogin(); // DISABILITATO

$db = new Database();
$translationService = new PreventiveTranslationService($db);
$message = '';
$error = '';

// Gestione azioni - SISTEMA TRADUZIONE MANUALE CORRETTO
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $startTime = microtime(true);
    
    switch ($_POST['action']) {
        case 'force_translate_all':
            try {
                $success = 0;
                $errors = 0;
                $detailedErrors = [];
                
                // TRADUZIONE MANUALE FORZATA - Traduci tutti gli articoli
                $stmt = $db->prepare("SELECT * FROM articles WHERE status = 'published'");
                $stmt->execute();
                $articles = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                foreach ($articles as $article) {
                    try {
                        if ($translationService->translateArticle($article['id'], [
                            'title' => $article['title'],
                            'content' => $article['content'],
                            'excerpt' => $article['excerpt']
                        ], true)) { // FORZA TRADUZIONE = true
                            $success++;
                        } else {
                            $errors++;
                            $detailedErrors[] = "Articolo ID {$article['id']}: Traduzione fallita";
                        }
                    } catch (Exception $e) {
                        $errors++;
                        $detailedErrors[] = "Articolo ID {$article['id']}: " . $e->getMessage();
                    }
                }
                
                // TRADUZIONE MANUALE FORZATA - Traduci contenuti statici
                try {
                    $staticSuccess = $translationService->translateStaticContent(true); // FORZA TRADUZIONE = true
                } catch (Exception $e) {
                    $staticSuccess = false;
                    $detailedErrors[] = "Contenuti statici: " . $e->getMessage();
                }
                
                $executionTime = round(microtime(true) - $startTime, 2);
                $message = "✅ TRADUZIONE MANUALE COMPLETATA! <br>";
                $message .= "📊 Risultati: {$success} successi, {$errors} errori in {$executionTime}s<br>";
                $message .= "📄 Contenuti statici: " . ($staticSuccess ? "✅ OK" : "❌ Errore") . "<br>";
                
                if (!empty($detailedErrors)) {
                    $message .= "<br>🔍 Dettagli errori:<br>" . implode('<br>', array_slice($detailedErrors, 0, 5));
                    if (count($detailedErrors) > 5) {
                        $message .= "<br>... e altri " . (count($detailedErrors) - 5) . " errori";
                    }
                }
                
            } catch (Exception $e) {
                $error = "❌ ERRORE CRITICO durante traduzione manuale: <br>" . htmlspecialchars($e->getMessage()) . "<br><br>";
                $error .= "🔧 Controlla:<br>• Configurazione API DeepL<br>• Connessione database<br>• Log di sistema";
            }
            break;
            
        case 'test_api_connection':
            try {
                $testResult = $translationService->testDeepLConnection();
                
                if ($testResult['success']) {
                    $message = "✅ CONNESSIONE API DEEPL OPERATIVA!<br>";
                    $message .= "🧪 Test traduzione: \"Ciao\" → \"" . $testResult['test_translation'] . "\"";
                } else {
                    $error = "❌ CONNESSIONE API DEEPL FALLITA:<br>" . htmlspecialchars($testResult['message']);
                }
                
            } catch (Exception $e) {
                $error = "❌ ERRORE test connessione: <br>" . htmlspecialchars($e->getMessage());
            }
            break;
            
        case 'save_api_config':
            try {
                $apiKey = trim($_POST['deepl_api_key'] ?? '');
                $isEnabled = isset($_POST['api_enabled']) ? 1 : 0;
                $dailyQuota = intval($_POST['daily_quota'] ?? 500000);
                
                if (empty($apiKey)) {
                    $error = "❌ ERRORE: Chiave API DeepL non può essere vuota";
                    break;
                }
                
                // Controlla se configurazione esiste
                $stmt = $db->prepare("SELECT id FROM translation_config WHERE api_provider = 'deepl' LIMIT 1");
                $stmt->execute();
                $configExists = $stmt->fetch();
                
                if ($configExists) {
                    // Aggiorna configurazione esistente
                    $stmt = $db->prepare("
                        UPDATE translation_config 
                        SET api_key = ?, is_enabled = ?, daily_quota = ?, updated_at = datetime('now') 
                        WHERE api_provider = 'deepl'
                    ");
                    $result = $stmt->execute([$apiKey, $isEnabled, $dailyQuota]);
                } else {
                    // Crea nuova configurazione
                    $stmt = $db->prepare("
                        INSERT INTO translation_config (api_provider, api_key, is_enabled, daily_quota, current_daily_usage, last_reset_date)
                        VALUES ('deepl', ?, ?, ?, 0, DATE('now'))
                    ");
                    $result = $stmt->execute([$apiKey, $isEnabled, $dailyQuota]);
                }
                
                if ($result) {
                    // Test immediato della connessione se abilitata
                    if ($isEnabled) {
                        $testResult = $translationService->testDeepLConnection();
                        if ($testResult['success']) {
                            $message = "✅ CONFIGURAZIONE API SALVATA E TESTATA!<br>";
                            $message .= "🔑 Chiave API DeepL configurata correttamente<br>";
                            $message .= "🧪 Test connessione: \"Ciao\" → \"" . $testResult['test_translation'] . "\"<br>";
                            $message .= "📊 Quota giornaliera: " . number_format($dailyQuota) . " caratteri";
                        } else {
                            $message = "⚠️ CONFIGURAZIONE SALVATA ma test fallito:<br>" . htmlspecialchars($testResult['message']);
                        }
                    } else {
                        $message = "✅ CONFIGURAZIONE API SALVATA<br>🔒 API DeepL disabilitata (puoi abilitarla quando vuoi)";
                    }
                } else {
                    $error = "❌ ERRORE durante il salvataggio della configurazione";
                }
                
            } catch (Exception $e) {
                $error = "❌ ERRORE salvataggio configurazione: <br>" . htmlspecialchars($e->getMessage());
            }
            break;
    }
}

// Carica configurazione API per il form
$currentApiConfig = null;
try {
    $stmt = $db->prepare("SELECT * FROM translation_config WHERE api_provider = 'deepl' LIMIT 1");
    $stmt->execute();
    $currentApiConfig = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Errore caricamento config API: " . $e->getMessage());
}

// Carica statistiche traduzioni
$stats = [
    'articles_total' => 0,
    'articles_translated' => 0,
    'static_total' => 0,
    'static_translated' => 0,
    'languages_count' => 5
];

try {
    // Statistiche articoli
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM articles WHERE status = 'published'");
    $stmt->execute();
    $stats['articles_total'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    $stmt = $db->prepare("
        SELECT COUNT(DISTINCT article_id) as translated 
        FROM article_translations 
        WHERE article_id IN (SELECT id FROM articles WHERE status = 'published')
    ");
    $stmt->execute();
    $stats['articles_translated'] = $stmt->fetch(PDO::FETCH_ASSOC)['translated'];
    
    // Statistiche contenuti statici
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM static_content");
    $stmt->execute();
    $stats['static_total'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    $stmt = $db->prepare("
        SELECT COUNT(DISTINCT static_content_id) as translated 
        FROM static_content_translations
    ");
    $stmt->execute();
    $stats['static_translated'] = $stmt->fetch(PDO::FETCH_ASSOC)['translated'];
    
} catch (Exception $e) {
    error_log("Errore caricamento statistiche: " . $e->getMessage());
}

// Calcola percentuali
$stats['articles_percentage'] = $stats['articles_total'] > 0 ? round(($stats['articles_translated'] / $stats['articles_total']) * 100, 1) : 0;
$stats['static_percentage'] = $stats['static_total'] > 0 ? round(($stats['static_translated'] / $stats['static_total']) * 100, 1) : 0;

?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestione Traduzione - Admin Passione Calabria</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="min-h-screen bg-gray-100">
    <div class="max-w-7xl mx-auto px-4 py-8">
        <!-- Header -->
        <div class="mb-8">
            <div class="flex items-center space-x-3 mb-4">
                <a href="index.php" class="text-blue-600 hover:text-blue-700">
                    <i data-lucide="arrow-left" class="w-5 h-5"></i>
                </a>
                <h1 class="text-3xl font-bold text-gray-900">Gestione Traduzioni</h1>
            </div>
            <p class="text-gray-600">Monitora e gestisci le traduzioni del sito</p>
        </div>

        <!-- Messaggi di Sistema -->
        <?php if ($message): ?>
        <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-6">
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <i data-lucide="check-circle" class="w-5 h-5 text-green-500 mt-0.5"></i>
                </div>
                <div class="ml-3 text-green-800">
                    <?php echo $message; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($error): ?>
        <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <i data-lucide="alert-triangle" class="w-5 h-5 text-red-500 mt-0.5"></i>
                </div>
                <div class="ml-3 text-red-800">
                    <?php echo $error; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Statistiche -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold mb-2">Articoli Tradotti</h3>
                <p class="text-3xl font-bold text-blue-600"><?php echo $stats['articles_translated']; ?></p>
                <p class="text-gray-500"><?php echo $stats['articles_total']; ?> totali (<?php echo $stats['articles_percentage']; ?>%)</p>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold mb-2">Contenuti Statici</h3>
                <p class="text-3xl font-bold text-green-600"><?php echo $stats['static_translated']; ?></p>
                <p class="text-gray-500"><?php echo $stats['static_total']; ?> totali (<?php echo $stats['static_percentage']; ?>%)</p>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold mb-2">Lingue Supportate</h3>
                <p class="text-3xl font-bold text-purple-600"><?php echo $stats['languages_count']; ?></p>
                <p class="text-gray-500">Italiano + 4 traduzioni</p>
            </div>
        </div>

        <!-- Configurazione API DeepL -->
        <div class="bg-white rounded-lg shadow p-6 mb-8">
            <h3 class="text-lg font-semibold mb-4 flex items-center">
                <i data-lucide="key" class="w-5 h-5 mr-2 text-green-600"></i>
                Configurazione API DeepL
            </h3>
            <p class="text-gray-600 mb-6">
                Configura la chiave API DeepL per abilitare le traduzioni automatiche. 
                Puoi ottenere una chiave gratuita su <a href="https://www.deepl.com/pro-api" target="_blank" class="text-blue-600 hover:underline">deepl.com/pro-api</a>.
            </p>

            <form method="POST" class="space-y-6">
                <input type="hidden" name="action" value="save_api_config">
                
                <!-- API Key Field -->
                <div>
                    <label for="deepl_api_key" class="block text-sm font-medium text-gray-700 mb-2">
                        <i data-lucide="key" class="w-4 h-4 inline mr-1"></i>
                        Chiave API DeepL
                    </label>
                    <input 
                        type="text" 
                        id="deepl_api_key" 
                        name="deepl_api_key" 
                        value="<?php echo htmlspecialchars($currentApiConfig['api_key'] ?? ''); ?>" 
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                        placeholder="Inserisci la tua chiave API DeepL..."
                        required
                    >
                    <p class="mt-1 text-xs text-gray-500">
                        Formato: <code>xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx:fx</code> (chiave gratuita) o senza ":fx" (chiave pro)
                    </p>
                </div>

                <!-- Enable/Disable Toggle -->
                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                    <div>
                        <label for="api_enabled" class="text-sm font-medium text-gray-700 flex items-center">
                            <i data-lucide="power" class="w-4 h-4 mr-2 text-green-600"></i>
                            Abilita API DeepL
                        </label>
                        <p class="text-xs text-gray-500 mt-1">
                            Attiva o disattiva il servizio di traduzione DeepL
                        </p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input 
                            type="checkbox" 
                            id="api_enabled" 
                            name="api_enabled" 
                            value="1" 
                            <?php echo ($currentApiConfig && $currentApiConfig['is_enabled']) ? 'checked' : ''; ?>
                            class="sr-only peer"
                        >
                        <div class="relative w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                    </label>
                </div>

                <!-- Daily Quota Field -->
                <div>
                    <label for="daily_quota" class="block text-sm font-medium text-gray-700 mb-2">
                        <i data-lucide="bar-chart-3" class="w-4 h-4 inline mr-1"></i>
                        Quota Giornaliera (caratteri)
                    </label>
                    <select 
                        id="daily_quota" 
                        name="daily_quota" 
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    >
                        <option value="500000" <?php echo (!$currentApiConfig || $currentApiConfig['daily_quota'] == 500000) ? 'selected' : ''; ?>>
                            500.000 caratteri/giorno (Piano Gratuito)
                        </option>
                        <option value="1000000" <?php echo ($currentApiConfig && $currentApiConfig['daily_quota'] == 1000000) ? 'selected' : ''; ?>>
                            1.000.000 caratteri/giorno (Piano Starter)
                        </option>
                        <option value="5000000" <?php echo ($currentApiConfig && $currentApiConfig['daily_quota'] == 5000000) ? 'selected' : ''; ?>>
                            5.000.000 caratteri/giorno (Piano Advanced)
                        </option>
                        <option value="50000000" <?php echo ($currentApiConfig && $currentApiConfig['daily_quota'] == 50000000) ? 'selected' : ''; ?>>
                            50.000.000 caratteri/giorno (Piano Ultimate)
                        </option>
                    </select>
                    <p class="mt-1 text-xs text-gray-500">
                        Imposta il limite giornaliero in base al tuo piano DeepL
                    </p>
                </div>

                <!-- Current Usage Info -->
                <?php if ($currentApiConfig && $currentApiConfig['is_enabled']): ?>
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <h4 class="font-medium text-blue-900 mb-2 flex items-center">
                        <i data-lucide="info" class="w-4 h-4 mr-2"></i>
                        Utilizzo Corrente
                    </h4>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-sm">
                        <div class="text-center">
                            <p class="text-blue-600 font-bold text-lg"><?php echo number_format($currentApiConfig['current_daily_usage']); ?></p>
                            <p class="text-blue-700">Caratteri usati oggi</p>
                        </div>
                        <div class="text-center">
                            <p class="text-blue-600 font-bold text-lg"><?php echo number_format($currentApiConfig['daily_quota'] - $currentApiConfig['current_daily_usage']); ?></p>
                            <p class="text-blue-700">Caratteri rimanenti</p>
                        </div>
                        <div class="text-center">
                            <p class="text-blue-600 font-bold text-lg"><?php echo round(($currentApiConfig['current_daily_usage'] / $currentApiConfig['daily_quota']) * 100, 1); ?>%</p>
                            <p class="text-blue-700">Percentuale usata</p>
                        </div>
                    </div>
                    <div class="mt-3">
                        <div class="w-full bg-blue-200 rounded-full h-2">
                            <div 
                                class="bg-blue-600 h-2 rounded-full transition-all duration-300" 
                                style="width: <?php echo min(100, round(($currentApiConfig['current_daily_usage'] / $currentApiConfig['daily_quota']) * 100, 1)); ?>%"
                            ></div>
                        </div>
                        <p class="text-xs text-blue-600 mt-1">Reset quotidiano: <?php echo $currentApiConfig['last_reset_date']; ?></p>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Action Buttons -->
                <div class="flex flex-col sm:flex-row gap-4 pt-4 border-t border-gray-200">
                    <button 
                        type="submit" 
                        class="flex-1 bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg font-medium transition-colors flex items-center justify-center"
                    >
                        <i data-lucide="save" class="w-5 h-5 mr-2"></i>
                        💾 Salva Configurazione API
                    </button>
                    
                    <?php if ($currentApiConfig && $currentApiConfig['is_enabled']): ?>
                    <div class="flex gap-2">
                        <form method="POST" class="inline-flex">
                            <input type="hidden" name="action" value="test_api_connection">
                            <button 
                                type="submit" 
                                class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-3 rounded-lg font-medium transition-colors flex items-center"
                            >
                                <i data-lucide="zap" class="w-4 h-4 mr-2"></i>
                                🧪 Testa
                            </button>
                        </form>
                    </div>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Azioni di Traduzione -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold mb-4 flex items-center">
                <i data-lucide="zap" class="w-5 h-5 mr-2 text-red-600"></i>
                Azioni di Traduzione
            </h3>
            <p class="text-gray-600 mb-6">
                Forza la traduzione di contenuti che potrebbero non essere stati tradotti automaticamente.
                <strong>Attenzione:</strong> Questa operazione consuma quota API.
            </p>

            <div class="space-y-4">
                <form method="POST" onsubmit="return confirm('Sei sicuro di voler tradurre TUTTO? Questa operazione può richiedere molto tempo e consumare quota API.');">
                    <input type="hidden" name="action" value="force_translate_all">
                    <button type="submit" class="w-full bg-red-600 hover:bg-red-700 text-white px-6 py-3 rounded-lg font-medium transition-colors flex items-center justify-center">
                        <i data-lucide="zap" class="w-5 h-5 mr-2"></i>
                        🔥 TRADUCI TUTTO (Articoli + Contenuti)
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>