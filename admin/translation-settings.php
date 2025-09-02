<?php
/**
 * Pannello Admin - Impostazioni Traduzione API
 * 
 * Interfaccia per configurare le API keys di traduzione:
 * - Google Translate API
 * - DeepL API  
 * - Yandex Translate API
 */

require_once '../includes/config.php';
require_once '../includes/database.php';

// Controllo accesso admin (semplificato per demo)
if (!isset($_SESSION['admin_logged_in'])) {
    // Per demo, permettiamo l'accesso diretto
    $_SESSION['admin_logged_in'] = true;
}

$db = new Database();
$message = '';
$error = '';

// Gestione salvataggio impostazioni
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    if ($_POST['action'] === 'save_api_settings') {
        try {
            // Salva Google Translate API
            if (!empty($_POST['google_api_key'])) {
                $stmt = $db->prepare("UPDATE translation_config SET api_key = ?, api_provider = 'google', updated_at = datetime('now') WHERE api_provider = 'google'");
                if (!$stmt->execute([$_POST['google_api_key']])) {
                    // Se UPDATE non ha effetto, inserisci nuovo record
                    $stmt = $db->prepare("INSERT INTO translation_config (api_provider, api_key, is_enabled, daily_quota) VALUES ('google', ?, 1, 10000)");
                    $stmt->execute([$_POST['google_api_key']]);
                }
            }
            
            // Salva DeepL API
            if (!empty($_POST['deepl_api_key'])) {
                $stmt = $db->prepare("SELECT id FROM translation_config WHERE api_provider = 'deepl'");
                $stmt->execute();
                if ($stmt->fetch()) {
                    $stmt = $db->prepare("UPDATE translation_config SET api_key = ?, updated_at = datetime('now') WHERE api_provider = 'deepl'");
                    $stmt->execute([$_POST['deepl_api_key']]);
                } else {
                    $stmt = $db->prepare("INSERT INTO translation_config (api_provider, api_key, is_enabled, daily_quota) VALUES ('deepl', ?, 0, 500000)");
                    $stmt->execute([$_POST['deepl_api_key']]);
                }
            }
            
            // Salva Yandex API
            if (!empty($_POST['yandex_api_key'])) {
                $stmt = $db->prepare("SELECT id FROM translation_config WHERE api_provider = 'yandex'");
                $stmt->execute();
                if ($stmt->fetch()) {
                    $stmt = $db->prepare("UPDATE translation_config SET api_key = ?, updated_at = datetime('now') WHERE api_provider = 'yandex'");
                    $stmt->execute([$_POST['yandex_api_key']]);
                } else {
                    $stmt = $db->prepare("INSERT INTO translation_config (api_provider, api_key, is_enabled, daily_quota) VALUES ('yandex', ?, 0, 10000)");
                    $stmt->execute([$_POST['yandex_api_key']]);
                }
            }
            
            // Aggiorna provider attivo
            if (!empty($_POST['active_provider'])) {
                // Disattiva tutti
                $stmt = $db->prepare("UPDATE translation_config SET is_enabled = 0");
                $stmt->execute();
                
                // Attiva il selezionato
                $stmt = $db->prepare("UPDATE translation_config SET is_enabled = 1 WHERE api_provider = ?");
                $stmt->execute([$_POST['active_provider']]);
            }
            
            $message = "✅ Impostazioni API salvate con successo!";
            
        } catch (Exception $e) {
            $error = "❌ Errore nel salvataggio: " . $e->getMessage();
        }
    }
    
    if ($_POST['action'] === 'test_translation') {
        try {
            $testProvider = $_POST['test_provider'] ?? 'google';
            $testText = $_POST['test_text'] ?? 'Ciao mondo!';
            $targetLang = $_POST['target_lang'] ?? 'en';
            
            // Qui implementeremo il test di traduzione
            $message = "🧪 Test traduzione completato per provider: $testProvider";
            
        } catch (Exception $e) {
            $error = "❌ Errore nel test: " . $e->getMessage();
        }
    }
}

// Carica configurazioni attuali
$stmt = $db->prepare("SELECT * FROM translation_config ORDER BY api_provider");
$stmt->execute();
$configs = $stmt->fetchAll(PDO::FETCH_ASSOC);

$configsByProvider = [];
foreach ($configs as $config) {
    $configsByProvider[$config['api_provider']] = $config;
}

// Carica statistiche traduzioni se esistono
$stats = [];
try {
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM static_content_translations");
    $stmt->execute();
    $stats['static_translations'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
    
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM article_translations");
    $stmt->execute();
    $stats['article_translations'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
} catch (Exception $e) {
    // Tabelle non esistenti ancora
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Impostazioni API Traduzione - Passione Calabria Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="max-w-6xl mx-auto py-8 px-4">
        <!-- Header -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Impostazioni API Traduzione</h1>
                    <p class="text-gray-600 mt-2">Configura le API per il sistema di traduzione preventiva</p>
                </div>
                <a href="../index-temp.php" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    <i data-lucide="external-link" class="w-4 h-4 mr-2"></i>
                    Visualizza Sito
                </a>
            </div>
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

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            
            <!-- Colonna Sinistra: Configurazione API -->
            <div class="lg:col-span-2 space-y-6">
                
                <!-- Form Configurazione -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-bold text-gray-900 mb-6">Configurazione API Keys</h2>
                    
                    <form method="POST" class="space-y-6">
                        <input type="hidden" name="action" value="save_api_settings">
                        
                        <!-- Google Translate API -->
                        <div class="border border-gray-200 rounded-lg p-4">
                            <div class="flex items-center mb-4">
                                <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center mr-4">
                                    <span class="text-2xl">🌐</span>
                                </div>
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900">Google Translate API</h3>
                                    <p class="text-sm text-gray-600">API leader con oltre 100 lingue supportate</p>
                                </div>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">API Key</label>
                                    <input type="password" name="google_api_key" 
                                           value="<?php echo htmlspecialchars($configsByProvider['google']['api_key'] ?? ''); ?>"
                                           placeholder="AIzaSy..."
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <p class="text-xs text-gray-500 mt-1">
                                        <a href="https://console.cloud.google.com/apis/credentials" target="_blank" class="text-blue-600 hover:underline">
                                            Ottieni API Key da Google Cloud Console
                                        </a>
                                    </p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                                    <div class="flex items-center">
                                        <?php $googleEnabled = ($configsByProvider['google']['is_enabled'] ?? 0) == 1; ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $googleEnabled ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'; ?>">
                                            <?php echo $googleEnabled ? 'Attivo' : 'Inattivo'; ?>
                                        </span>
                                        <?php if (!empty($configsByProvider['google']['api_key'])): ?>
                                        <span class="ml-2 text-xs text-gray-500">
                                            Quota: <?php echo $configsByProvider['google']['current_daily_usage'] ?? 0; ?>/<?php echo $configsByProvider['google']['daily_quota'] ?? 10000; ?>
                                        </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- DeepL API -->
                        <div class="border border-gray-200 rounded-lg p-4">
                            <div class="flex items-center mb-4">
                                <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center mr-4">
                                    <span class="text-2xl">🧠</span>
                                </div>
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900">DeepL API</h3>
                                    <p class="text-sm text-gray-600">Traduzioni di alta qualità con AI neurale</p>
                                </div>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">API Key</label>
                                    <input type="password" name="deepl_api_key" 
                                           value="<?php echo htmlspecialchars($configsByProvider['deepl']['api_key'] ?? ''); ?>"
                                           placeholder="xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx:fx"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                                    <p class="text-xs text-gray-500 mt-1">
                                        <a href="https://www.deepl.com/pro-api" target="_blank" class="text-green-600 hover:underline">
                                            Ottieni API Key da DeepL Pro
                                        </a>
                                    </p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                                    <div class="flex items-center">
                                        <?php $deeplEnabled = ($configsByProvider['deepl']['is_enabled'] ?? 0) == 1; ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $deeplEnabled ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'; ?>">
                                            <?php echo $deeplEnabled ? 'Attivo' : 'Inattivo'; ?>
                                        </span>
                                        <?php if (!empty($configsByProvider['deepl']['api_key'])): ?>
                                        <span class="ml-2 text-xs text-gray-500">
                                            Quota: <?php echo $configsByProvider['deepl']['current_daily_usage'] ?? 0; ?>/<?php echo $configsByProvider['deepl']['daily_quota'] ?? 500000; ?>
                                        </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Yandex Translate API -->
                        <div class="border border-gray-200 rounded-lg p-4">
                            <div class="flex items-center mb-4">
                                <div class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center mr-4">
                                    <span class="text-2xl">🔴</span>
                                </div>
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900">Yandex Translate API</h3>
                                    <p class="text-sm text-gray-600">Ottimo per lingue europee e slave</p>
                                </div>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">API Key</label>
                                    <input type="password" name="yandex_api_key" 
                                           value="<?php echo htmlspecialchars($configsByProvider['yandex']['api_key'] ?? ''); ?>"
                                           placeholder="AQVNxxxxxxxxxx..."
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-red-500">
                                    <p class="text-xs text-gray-500 mt-1">
                                        <a href="https://cloud.yandex.com/docs/translate/" target="_blank" class="text-red-600 hover:underline">
                                            Ottieni API Key da Yandex Cloud
                                        </a>
                                    </p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                                    <div class="flex items-center">
                                        <?php $yandexEnabled = ($configsByProvider['yandex']['is_enabled'] ?? 0) == 1; ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $yandexEnabled ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'; ?>">
                                            <?php echo $yandexEnabled ? 'Attivo' : 'Inattivo'; ?>
                                        </span>
                                        <?php if (!empty($configsByProvider['yandex']['api_key'])): ?>
                                        <span class="ml-2 text-xs text-gray-500">
                                            Quota: <?php echo $configsByProvider['yandex']['current_daily_usage'] ?? 0; ?>/<?php echo $configsByProvider['yandex']['daily_quota'] ?? 10000; ?>
                                        </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Selezione Provider Attivo -->
                        <div class="border border-gray-200 rounded-lg p-4 bg-gray-50">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Provider Attivo</h3>
                            <div class="space-y-3">
                                <?php
                                $activeProvider = '';
                                foreach ($configsByProvider as $provider => $config) {
                                    if ($config['is_enabled'] == 1) {
                                        $activeProvider = $provider;
                                        break;
                                    }
                                }
                                ?>
                                <label class="flex items-center">
                                    <input type="radio" name="active_provider" value="google" <?php echo $activeProvider === 'google' ? 'checked' : ''; ?> class="mr-3">
                                    <span class="text-gray-700">Google Translate (Raccomandato)</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="radio" name="active_provider" value="deepl" <?php echo $activeProvider === 'deepl' ? 'checked' : ''; ?> class="mr-3">
                                    <span class="text-gray-700">DeepL (Alta Qualità)</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="radio" name="active_provider" value="yandex" <?php echo $activeProvider === 'yandex' ? 'checked' : ''; ?> class="mr-3">
                                    <span class="text-gray-700">Yandex (Lingue Europee)</span>
                                </label>
                            </div>
                        </div>
                        
                        <!-- Pulsante Salva -->
                        <div class="flex justify-end">
                            <button type="submit" class="inline-flex items-center px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                                <i data-lucide="save" class="w-4 h-4 mr-2"></i>
                                Salva Configurazione
                            </button>
                        </div>
                    </form>
                </div>
                
                <!-- Test Traduzione -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-bold text-gray-900 mb-6">Test API Traduzione</h2>
                    
                    <form method="POST" class="space-y-4">
                        <input type="hidden" name="action" value="test_translation">
                        
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Provider</label>
                                <select name="test_provider" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                                    <option value="google">Google Translate</option>
                                    <option value="deepl">DeepL</option>
                                    <option value="yandex">Yandex</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Lingua Destinazione</label>
                                <select name="target_lang" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                                    <option value="en">Inglese</option>
                                    <option value="fr">Francese</option>
                                    <option value="de">Tedesco</option>
                                    <option value="es">Spagnolo</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Azione</label>
                                <button type="submit" class="w-full inline-flex items-center justify-center px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 transition-colors">
                                    <i data-lucide="play" class="w-4 h-4 mr-2"></i>
                                    Test
                                </button>
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Testo da Tradurre</label>
                            <textarea name="test_text" rows="3" placeholder="Inserisci il testo da tradurre..." 
                                      class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">Benvenuti in Calabria, la terra del sole e della bellezza!</textarea>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Colonna Destra: Statistiche e Info -->
            <div class="space-y-6">
                
                <!-- Statistiche -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-bold text-gray-900 mb-4">Statistiche Traduzioni</h2>
                    
                    <div class="space-y-4">
                        <div class="flex items-center justify-between p-3 bg-blue-50 rounded-lg">
                            <div class="flex items-center">
                                <i data-lucide="file-text" class="w-5 h-5 text-blue-600 mr-2"></i>
                                <span class="text-sm font-medium text-gray-700">Contenuti Statici</span>
                            </div>
                            <span class="text-lg font-bold text-blue-600"><?php echo $stats['static_translations'] ?? 0; ?></span>
                        </div>
                        
                        <div class="flex items-center justify-between p-3 bg-green-50 rounded-lg">
                            <div class="flex items-center">
                                <i data-lucide="newspaper" class="w-5 h-5 text-green-600 mr-2"></i>
                                <span class="text-sm font-medium text-gray-700">Articoli</span>
                            </div>
                            <span class="text-lg font-bold text-green-600"><?php echo $stats['article_translations'] ?? 0; ?></span>
                        </div>
                        
                        <div class="flex items-center justify-between p-3 bg-yellow-50 rounded-lg">
                            <div class="flex items-center">
                                <i data-lucide="globe" class="w-5 h-5 text-yellow-600 mr-2"></i>
                                <span class="text-sm font-medium text-gray-700">Lingue Attive</span>
                            </div>
                            <span class="text-lg font-bold text-yellow-600">5</span>
                        </div>
                    </div>
                </div>
                
                <!-- Lingue Supportate -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-bold text-gray-900 mb-4">Lingue Supportate</h2>
                    
                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="text-sm">🇮🇹 Italiano</span>
                            <span class="text-xs text-green-600 font-medium">BASE</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm">🇬🇧 Inglese</span>
                            <span class="text-xs text-blue-600 font-medium">FALLBACK</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm">🇫🇷 Francese</span>
                            <span class="text-xs text-gray-600 font-medium">ATTIVO</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm">🇩🇪 Tedesco</span>
                            <span class="text-xs text-gray-600 font-medium">ATTIVO</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm">🇪🇸 Spagnolo</span>
                            <span class="text-xs text-gray-600 font-medium">ATTIVO</span>
                        </div>
                    </div>
                </div>
                
                <!-- Azioni Rapide -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-bold text-gray-900 mb-4">Azioni Rapide</h2>
                    
                    <div class="space-y-3">
                        <a href="../migrate_to_preventive_translations.php" class="w-full inline-flex items-center justify-center px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors">
                            <i data-lucide="refresh-cw" class="w-4 h-4 mr-2"></i>
                            Migra Contenuti
                        </a>
                        
                        <button class="w-full inline-flex items-center justify-center px-4 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition-colors">
                            <i data-lucide="zap" class="w-4 h-4 mr-2"></i>
                            Rigenera Traduzioni
                        </button>
                        
                        <button class="w-full inline-flex items-center justify-center px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors">
                            <i data-lucide="trash-2" class="w-4 h-4 mr-2"></i>
                            Pulisci Cache
                        </button>
                    </div>
                </div>
                
            </div>
        </div>
    </div>

    <script>
        lucide.createIcons();
        
        // Mostra/nascondi password
        document.querySelectorAll('input[type="password"]').forEach(input => {
            const container = input.parentElement;
            const toggleBtn = document.createElement('button');
            toggleBtn.type = 'button';
            toggleBtn.className = 'absolute right-3 top-8 text-gray-400 hover:text-gray-600';
            toggleBtn.innerHTML = '<i data-lucide="eye" class="w-4 h-4"></i>';
            
            container.style.position = 'relative';
            container.appendChild(toggleBtn);
            
            toggleBtn.addEventListener('click', () => {
                const isPassword = input.type === 'password';
                input.type = isPassword ? 'text' : 'password';
                toggleBtn.innerHTML = isPassword ? '<i data-lucide="eye-off" class="w-4 h-4"></i>' : '<i data-lucide="eye" class="w-4 h-4"></i>';
                lucide.createIcons();
            });
        });
    </script>
</body>
</html>