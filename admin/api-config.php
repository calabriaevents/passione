<?php
/**
 * Configurazione API Traduzione - Versione Ultra Semplificata
 * 
 * Interfaccia diretta per configurare le API keys senza dipendenze complesse
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Connessione diretta al database
$dbPath = dirname(__DIR__) . '/passione_calabria.db';
$message = '';
$error = '';

try {
    $pdo = new PDO('sqlite:' . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Assicura che le tabelle esistano
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS translation_config (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            api_provider VARCHAR(20) DEFAULT 'google',
            api_key TEXT,
            is_enabled INTEGER DEFAULT 1,
            daily_quota INTEGER DEFAULT 10000,
            current_daily_usage INTEGER DEFAULT 0,
            last_reset_date TEXT DEFAULT (date('now')),
            created_at TEXT DEFAULT (datetime('now')),
            updated_at TEXT DEFAULT (datetime('now'))
        )
    ");
    
    // Gestione salvataggio
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        
        if (isset($_POST['google_key']) && !empty(trim($_POST['google_key']))) {
            $stmt = $pdo->prepare("INSERT OR REPLACE INTO translation_config (id, api_provider, api_key, is_enabled, daily_quota) VALUES (1, 'google', ?, 1, 10000)");
            $stmt->execute([trim($_POST['google_key'])]);
            $message .= "✅ Google Translate API configurata! ";
        }
        
        if (isset($_POST['deepl_key']) && !empty(trim($_POST['deepl_key']))) {
            $stmt = $pdo->prepare("INSERT OR REPLACE INTO translation_config (id, api_provider, api_key, is_enabled, daily_quota) VALUES (2, 'deepl', ?, 0, 500000)");
            $stmt->execute([trim($_POST['deepl_key'])]);
            $message .= "✅ DeepL API configurata! ";
        }
        
        if (isset($_POST['yandex_key']) && !empty(trim($_POST['yandex_key']))) {
            $stmt = $pdo->prepare("INSERT OR REPLACE INTO translation_config (id, api_provider, api_key, is_enabled, daily_quota) VALUES (3, 'yandex', ?, 0, 10000)");
            $stmt->execute([trim($_POST['yandex_key'])]);
            $message .= "✅ Yandex API configurata! ";
        }
        
        // Attivazione provider
        if (isset($_POST['activate_provider'])) {
            $provider = $_POST['activate_provider'];
            $pdo->exec("UPDATE translation_config SET is_enabled = 0"); // Disattiva tutti
            $stmt = $pdo->prepare("UPDATE translation_config SET is_enabled = 1 WHERE api_provider = ?");
            $stmt->execute([$provider]);
            $message .= "✅ Provider $provider attivato! ";
        }
        
        if (empty(trim($message))) {
            $message = "⚠️ Nessuna modifica effettuata.";
        }
    }
    
    // Carica configurazioni correnti
    $stmt = $pdo->query("SELECT * FROM translation_config ORDER BY api_provider");
    $configs = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $configs[$row['api_provider']] = $row;
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
    <title>🌐 Configurazione API Traduzione | Passione Calabria</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; padding: 20px; }
        .container { max-width: 900px; margin: 0 auto; background: white; border-radius: 15px; box-shadow: 0 20px 40px rgba(0,0,0,0.1); overflow: hidden; }
        .header { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white; padding: 30px; text-align: center; }
        .header h1 { font-size: 2.5rem; margin-bottom: 10px; }
        .header p { opacity: 0.9; font-size: 1.1rem; }
        .content { padding: 40px; }
        .message { padding: 15px; border-radius: 8px; margin-bottom: 20px; font-weight: bold; }
        .message.success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .message.error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .api-section { border: 2px solid #e9ecef; border-radius: 12px; padding: 25px; margin-bottom: 25px; transition: all 0.3s ease; }
        .api-section:hover { border-color: #007bff; box-shadow: 0 5px 15px rgba(0,123,255,0.1); }
        .api-header { display: flex; align-items: center; margin-bottom: 20px; }
        .api-icon { font-size: 2.5rem; margin-right: 15px; }
        .api-info h3 { color: #333; font-size: 1.4rem; margin-bottom: 5px; }
        .api-info p { color: #666; font-size: 0.95rem; }
        .api-status { margin-left: auto; padding: 8px 16px; border-radius: 20px; font-size: 0.85rem; font-weight: bold; }
        .status-active { background: #d4edda; color: #155724; }
        .status-inactive { background: #f8f9fa; color: #6c757d; }
        .form-row { display: grid; grid-template-columns: 1fr auto auto; gap: 15px; align-items: end; }
        .form-group { display: flex; flex-direction: column; }
        .form-group label { font-weight: 600; color: #333; margin-bottom: 8px; display: flex; align-items: center; }
        .form-group input { padding: 12px 15px; border: 2px solid #e9ecef; border-radius: 8px; font-size: 1rem; transition: all 0.3s ease; }
        .form-group input:focus { outline: none; border-color: #007bff; box-shadow: 0 0 0 3px rgba(0,123,255,0.1); }
        .btn { padding: 12px 20px; border: none; border-radius: 8px; font-size: 1rem; font-weight: 600; cursor: pointer; transition: all 0.3s ease; text-decoration: none; display: inline-block; text-align: center; }
        .btn-primary { background: #007bff; color: white; }
        .btn-primary:hover { background: #0056b3; transform: translateY(-2px); box-shadow: 0 5px 15px rgba(0,123,255,0.3); }
        .btn-success { background: #28a745; color: white; }
        .btn-success:hover { background: #1e7e34; }
        .btn-secondary { background: #6c757d; color: white; }
        .btn-secondary:hover { background: #545b62; }
        .provider-selector { background: #f8f9fa; padding: 20px; border-radius: 10px; margin-top: 30px; }
        .provider-selector h3 { margin-bottom: 15px; color: #333; }
        .radio-group { display: flex; gap: 20px; flex-wrap: wrap; }
        .radio-item { display: flex; align-items: center; }
        .radio-item input[type="radio"] { margin-right: 8px; transform: scale(1.2); }
        .radio-item label { font-weight: 500; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-top: 30px; }
        .stat-card { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 12px; text-align: center; }
        .stat-number { font-size: 2rem; font-weight: bold; margin-bottom: 5px; }
        .stat-label { opacity: 0.9; }
        .links { text-align: center; margin-top: 30px; }
        .links a { color: #007bff; text-decoration: none; margin: 0 15px; font-weight: 500; }
        .links a:hover { text-decoration: underline; }
        .help-text { font-size: 0.85rem; color: #666; margin-top: 5px; }
        .help-text a { color: #007bff; text-decoration: none; }
        .help-text a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🌐 Sistema Traduzione Preventiva</h1>
            <p>Configurazione API per Traduzioni Automatiche</p>
        </div>
        
        <div class="content">
            <?php if ($message): ?>
                <div class="message success"><?php echo $message; ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="message error"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST">
                <!-- Google Translate -->
                <div class="api-section">
                    <div class="api-header">
                        <div class="api-icon">🌐</div>
                        <div class="api-info">
                            <h3>Google Translate API</h3>
                            <p>Il servizio di traduzione più utilizzato al mondo con oltre 100 lingue supportate</p>
                        </div>
                        <div class="api-status <?php echo (isset($configs['google']) && $configs['google']['is_enabled']) ? 'status-active' : 'status-inactive'; ?>">
                            <?php echo (isset($configs['google']) && $configs['google']['is_enabled']) ? '✅ ATTIVO' : '⭕ INATTIVO'; ?>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>🔑 API Key:</label>
                            <input type="password" name="google_key" placeholder="AIzaSy..." 
                                   value="<?php echo isset($configs['google']) ? htmlspecialchars($configs['google']['api_key']) : ''; ?>">
                            <div class="help-text">
                                <a href="https://console.cloud.google.com/apis/credentials" target="_blank">📖 Come ottenere la chiave API</a>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>📊 Quota:</label>
                            <div style="padding: 12px; background: #f8f9fa; border-radius: 8px; font-weight: bold;">
                                <?php echo isset($configs['google']) ? ($configs['google']['current_daily_usage'] . '/' . $configs['google']['daily_quota']) : '0/10000'; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- DeepL -->
                <div class="api-section">
                    <div class="api-header">
                        <div class="api-icon">🧠</div>
                        <div class="api-info">
                            <h3>DeepL API</h3>
                            <p>Traduzioni di alta qualità alimentate da reti neurali avanzate</p>
                        </div>
                        <div class="api-status <?php echo (isset($configs['deepl']) && $configs['deepl']['is_enabled']) ? 'status-active' : 'status-inactive'; ?>">
                            <?php echo (isset($configs['deepl']) && $configs['deepl']['is_enabled']) ? '✅ ATTIVO' : '⭕ INATTIVO'; ?>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>🔑 API Key:</label>
                            <input type="password" name="deepl_key" placeholder="xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx:fx" 
                                   value="<?php echo isset($configs['deepl']) ? htmlspecialchars($configs['deepl']['api_key']) : ''; ?>">
                            <div class="help-text">
                                <a href="https://www.deepl.com/pro-api" target="_blank">📖 Come ottenere la chiave API</a>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>📊 Quota:</label>
                            <div style="padding: 12px; background: #f8f9fa; border-radius: 8px; font-weight: bold;">
                                <?php echo isset($configs['deepl']) ? ($configs['deepl']['current_daily_usage'] . '/' . $configs['deepl']['daily_quota']) : '0/500K'; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Yandex -->
                <div class="api-section">
                    <div class="api-header">
                        <div class="api-icon">🔴</div>
                        <div class="api-info">
                            <h3>Yandex Translate API</h3>
                            <p>Servizio russo eccellente per lingue europee, slave e asiatiche</p>
                        </div>
                        <div class="api-status <?php echo (isset($configs['yandex']) && $configs['yandex']['is_enabled']) ? 'status-active' : 'status-inactive'; ?>">
                            <?php echo (isset($configs['yandex']) && $configs['yandex']['is_enabled']) ? '✅ ATTIVO' : '⭕ INATTIVO'; ?>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>🔑 API Key:</label>
                            <input type="password" name="yandex_key" placeholder="AQVNxxxxxxxxxx..." 
                                   value="<?php echo isset($configs['yandex']) ? htmlspecialchars($configs['yandex']['api_key']) : ''; ?>">
                            <div class="help-text">
                                <a href="https://cloud.yandex.com/docs/translate/" target="_blank">📖 Come ottenere la chiave API</a>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>📊 Quota:</label>
                            <div style="padding: 12px; background: #f8f9fa; border-radius: 8px; font-weight: bold;">
                                <?php echo isset($configs['yandex']) ? ($configs['yandex']['current_daily_usage'] . '/' . $configs['yandex']['daily_quota']) : '0/10K'; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Selezione Provider Attivo -->
                <div class="provider-selector">
                    <h3>⚡ Seleziona Provider Attivo:</h3>
                    <div class="radio-group">
                        <?php 
                        $activeProvider = '';
                        foreach ($configs as $provider => $config) {
                            if ($config['is_enabled']) {
                                $activeProvider = $provider;
                                break;
                            }
                        }
                        ?>
                        <div class="radio-item">
                            <input type="radio" name="activate_provider" value="google" <?php echo $activeProvider === 'google' ? 'checked' : ''; ?> id="google_radio">
                            <label for="google_radio">🌐 Google (Raccomandato)</label>
                        </div>
                        <div class="radio-item">
                            <input type="radio" name="activate_provider" value="deepl" <?php echo $activeProvider === 'deepl' ? 'checked' : ''; ?> id="deepl_radio">
                            <label for="deepl_radio">🧠 DeepL (Alta Qualità)</label>
                        </div>
                        <div class="radio-item">
                            <input type="radio" name="activate_provider" value="yandex" <?php echo $activeProvider === 'yandex' ? 'checked' : ''; ?> id="yandex_radio">
                            <label for="yandex_radio">🔴 Yandex (Lingue EU)</label>
                        </div>
                    </div>
                </div>

                <div style="text-align: center; margin-top: 30px;">
                    <button type="submit" class="btn btn-primary" style="font-size: 1.2rem; padding: 15px 40px;">
                        💾 Salva Configurazione
                    </button>
                </div>
            </form>

            <!-- Statistiche -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number">5</div>
                    <div class="stat-label">Lingue Supportate</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">3</div>
                    <div class="stat-label">API Configurabili</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">0ms</div>
                    <div class="stat-label">Latenza Utente</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">✓</div>
                    <div class="stat-label">Sistema Attivo</div>
                </div>
            </div>

            <div class="links">
                <a href="../index-temp.php">🏠 Visualizza Sito</a>
                <a href="../admin/">🏛️ Admin Principale</a>
                <a href="../migrate_to_preventive_translations.php">🔄 Migra Contenuti</a>
            </div>
        </div>
    </div>

    <script>
        // Toggle password visibility
        document.querySelectorAll('input[type="password"]').forEach(input => {
            const container = input.parentElement;
            const toggle = document.createElement('span');
            toggle.innerHTML = '👁️';
            toggle.style.cssText = 'position: absolute; right: 12px; top: 35px; cursor: pointer; user-select: none;';
            container.style.position = 'relative';
            container.appendChild(toggle);
            
            toggle.addEventListener('click', () => {
                const isPassword = input.type === 'password';
                input.type = isPassword ? 'text' : 'password';
                toggle.innerHTML = isPassword ? '🙈' : '👁️';
            });
        });

        console.log('🌐 Sistema di Traduzione Preventiva - Pannello Admin Caricato');
    </script>
</body>
</html>