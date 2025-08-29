<?php
/**
 * Pannello Admin Semplificato - Impostazioni API Traduzione
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

$message = '';
$error = '';

// Percorsi assoluti per evitare errori
$basePath = dirname(__DIR__);
require_once $basePath . '/includes/config.php';
require_once $basePath . '/includes/database.php';

try {
    $db = new Database();
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
        if ($_POST['action'] === 'save_api_settings') {
            // Salva Google API Key
            if (!empty($_POST['google_api_key'])) {
                $stmt = $db->prepare("INSERT OR REPLACE INTO translation_config (id, api_provider, api_key, is_enabled, daily_quota, current_daily_usage, last_reset_date, created_at, updated_at) VALUES (1, 'google', ?, 1, 10000, 0, date('now'), datetime('now'), datetime('now'))");
                $stmt->execute([$_POST['google_api_key']]);
                $message .= "✅ Google API Key salvata! ";
            }
            
            // Salva DeepL API Key
            if (!empty($_POST['deepl_api_key'])) {
                $stmt = $db->prepare("INSERT OR REPLACE INTO translation_config (id, api_provider, api_key, is_enabled, daily_quota, current_daily_usage, last_reset_date, created_at, updated_at) VALUES (2, 'deepl', ?, 0, 500000, 0, date('now'), datetime('now'), datetime('now'))");
                $stmt->execute([$_POST['deepl_api_key']]);
                $message .= "✅ DeepL API Key salvata! ";
            }
            
            // Salva Yandex API Key  
            if (!empty($_POST['yandex_api_key'])) {
                $stmt = $db->prepare("INSERT OR REPLACE INTO translation_config (id, api_provider, api_key, is_enabled, daily_quota, current_daily_usage, last_reset_date, created_at, updated_at) VALUES (3, 'yandex', ?, 0, 10000, 0, date('now'), datetime('now'), datetime('now'))");
                $stmt->execute([$_POST['yandex_api_key']]);
                $message .= "✅ Yandex API Key salvata! ";
            }
            
            if (empty($message)) {
                $message = "⚠️ Nessuna API key fornita.";
            }
        }
    }
    
    // Carica configurazioni esistenti
    $configs = [];
    try {
        $stmt = $db->prepare("SELECT * FROM translation_config ORDER BY api_provider");
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($results as $config) {
            $configs[$config['api_provider']] = $config;
        }
    } catch (Exception $e) {
        // Tabelle potrebbero non esistere ancora
        $error = "Database non ancora inizializzato. Esegui prima la migrazione.";
    }
    
} catch (Exception $e) {
    $error = "Errore database: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurazione API Traduzione - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
</head>
<body class="bg-gray-100 min-h-screen p-6">
    <div class="max-w-4xl mx-auto">
        <div class="bg-white rounded-lg shadow-lg p-6">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Configurazione API Traduzione</h1>
                    <p class="text-gray-600">Sistema di Traduzione Preventiva - Passione Calabria</p>
                </div>
                <a href="../index-temp.php" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                    🌐 Visualizza Sito
                </a>
            </div>

            <!-- Messaggi -->
            <?php if ($message): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                <?php echo htmlspecialchars($message); ?>
            </div>
            <?php endif; ?>

            <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                <?php echo htmlspecialchars($error); ?>
            </div>
            <?php endif; ?>

            <!-- Form di Configurazione -->
            <form method="POST" class="space-y-6">
                <input type="hidden" name="action" value="save_api_settings">

                <!-- Google Translate -->
                <div class="border border-gray-200 rounded-lg p-4">
                    <div class="flex items-center mb-3">
                        <span class="text-2xl mr-3">🌐</span>
                        <div>
                            <h3 class="text-lg font-semibold">Google Translate API</h3>
                            <p class="text-sm text-gray-600">Servizio leader con oltre 100 lingue</p>
                        </div>
                        <div class="ml-auto">
                            <?php if (isset($configs['google']) && $configs['google']['is_enabled']): ?>
                                <span class="px-2 py-1 bg-green-100 text-green-800 text-xs rounded-full">ATTIVO</span>
                            <?php else: ?>
                                <span class="px-2 py-1 bg-gray-100 text-gray-800 text-xs rounded-full">INATTIVO</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <input type="password" name="google_api_key" 
                                   placeholder="AIzaSy..." 
                                   value="<?php echo isset($configs['google']) ? htmlspecialchars($configs['google']['api_key']) : ''; ?>"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        </div>
                        <div class="text-sm text-gray-500">
                            <a href="https://console.cloud.google.com/apis/credentials" target="_blank" class="text-blue-600 hover:underline">
                                📖 Come ottenere la API Key
                            </a>
                        </div>
                    </div>
                </div>

                <!-- DeepL -->
                <div class="border border-gray-200 rounded-lg p-4">
                    <div class="flex items-center mb-3">
                        <span class="text-2xl mr-3">🧠</span>
                        <div>
                            <h3 class="text-lg font-semibold">DeepL API</h3>
                            <p class="text-sm text-gray-600">Traduzioni di alta qualità con AI neurale</p>
                        </div>
                        <div class="ml-auto">
                            <?php if (isset($configs['deepl']) && $configs['deepl']['is_enabled']): ?>
                                <span class="px-2 py-1 bg-green-100 text-green-800 text-xs rounded-full">ATTIVO</span>
                            <?php else: ?>
                                <span class="px-2 py-1 bg-gray-100 text-gray-800 text-xs rounded-full">INATTIVO</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <input type="password" name="deepl_api_key" 
                                   placeholder="xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx:fx" 
                                   value="<?php echo isset($configs['deepl']) ? htmlspecialchars($configs['deepl']['api_key']) : ''; ?>"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        </div>
                        <div class="text-sm text-gray-500">
                            <a href="https://www.deepl.com/pro-api" target="_blank" class="text-blue-600 hover:underline">
                                📖 Come ottenere la API Key
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Yandex -->
                <div class="border border-gray-200 rounded-lg p-4">
                    <div class="flex items-center mb-3">
                        <span class="text-2xl mr-3">🔴</span>
                        <div>
                            <h3 class="text-lg font-semibold">Yandex Translate API</h3>
                            <p class="text-sm text-gray-600">Ottimo per lingue europee e slave</p>
                        </div>
                        <div class="ml-auto">
                            <?php if (isset($configs['yandex']) && $configs['yandex']['is_enabled']): ?>
                                <span class="px-2 py-1 bg-green-100 text-green-800 text-xs rounded-full">ATTIVO</span>
                            <?php else: ?>
                                <span class="px-2 py-1 bg-gray-100 text-gray-800 text-xs rounded-full">INATTIVO</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <input type="password" name="yandex_api_key" 
                                   placeholder="AQVNxxxxxxxxxx..." 
                                   value="<?php echo isset($configs['yandex']) ? htmlspecialchars($configs['yandex']['api_key']) : ''; ?>"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        </div>
                        <div class="text-sm text-gray-500">
                            <a href="https://cloud.yandex.com/docs/translate/" target="_blank" class="text-blue-600 hover:underline">
                                📖 Come ottenere la API Key
                            </a>
                        </div>
                    </div>
                </div>

                <div class="flex justify-center">
                    <button type="submit" class="px-8 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-lg font-semibold">
                        💾 Salva Configurazione
                    </button>
                </div>
            </form>

            <!-- Statistiche -->
            <div class="mt-8 grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-blue-50 p-4 rounded-lg text-center">
                    <div class="text-2xl mb-2">🚀</div>
                    <div class="text-lg font-bold text-blue-600">Veloce</div>
                    <div class="text-sm text-gray-600">Traduzione preventiva</div>
                </div>
                <div class="bg-green-50 p-4 rounded-lg text-center">
                    <div class="text-2xl mb-2">🎯</div>
                    <div class="text-lg font-bold text-green-600">Preciso</div>
                    <div class="text-sm text-gray-600">Fallback intelligente</div>
                </div>
                <div class="bg-purple-50 p-4 rounded-lg text-center">
                    <div class="text-2xl mb-2">⚙️</div>
                    <div class="text-lg font-bold text-purple-600">Automatico</div>
                    <div class="text-sm text-gray-600">Zero configurazione utente</div>
                </div>
            </div>

            <!-- Collegamenti -->
            <div class="mt-6 text-center space-y-2">
                <div>
                    <a href="../index-temp.php" class="text-blue-600 hover:underline mr-4">🏠 Homepage</a>
                    <a href="../admin/" class="text-blue-600 hover:underline mr-4">🏛️ Admin Principale</a>
                    <a href="../migrate_to_preventive_translations.php" class="text-purple-600 hover:underline">🔄 Avvia Migrazione</a>
                </div>
                <div class="text-sm text-gray-500">
                    Sistema di Traduzione Preventiva - Implementazione Completa
                </div>
            </div>
        </div>
    </div>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>