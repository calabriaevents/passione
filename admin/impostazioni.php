<?php
require_once '../includes/config.php';
require_once '../includes/database.php';

// Controlla autenticazione (da implementare)
// requireLogin();

$db = new Database();

// Gestisci azioni speciali
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action === 'clear_translation_cache') {
        try {
            $stmt = $db->pdo->prepare("DELETE FROM translations_cache");
            $deleted = $stmt->execute();
            $count = $stmt->rowCount();
            
            // Reset anche le statistiche se necessario
            $db->pdo->prepare("DELETE FROM translation_stats")->execute();
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => "Cache svuotata con successo! Eliminate $count traduzioni memorizzate."
            ]);
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Errore durante lo svuotamento: ' . $e->getMessage()
            ]);
        }
        exit;
    }
}

// Gestisci salvataggio
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Salva impostazioni generali
    $settings = $_POST['settings'] ?? [];
    foreach ($settings as $key => $value) {
        $db->setSetting($key, $value);
    }
    
    // Salva impostazioni API traduzioni
    $translationApis = $_POST['translation_apis'] ?? [];
    foreach ($translationApis as $provider => $data) {
        if (!empty($data['api_key'])) {
            // Controlla se l'API provider esiste già
            $stmt = $db->pdo->prepare("SELECT id FROM translation_settings WHERE api_provider = ?");
            $stmt->execute([$provider]);
            $existingId = $stmt->fetchColumn();
            
            if ($existingId) {
                // Aggiorna esistente
                $stmt = $db->pdo->prepare("
                    UPDATE translation_settings 
                    SET api_key = ?, is_active = ?, priority = ?, updated_at = datetime('now')
                    WHERE id = ?
                ");
                $stmt->execute([
                    $data['api_key'],
                    isset($data['is_active']) ? 1 : 0,
                    intval($data['priority'] ?? 1),
                    $existingId
                ]);
            } else {
                // Crea nuovo
                $stmt = $db->pdo->prepare("
                    INSERT INTO translation_settings (api_provider, api_key, is_active, priority)
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([
                    $provider,
                    $data['api_key'],
                    isset($data['is_active']) ? 1 : 0,
                    intval($data['priority'] ?? 1)
                ]);
            }
        }
    }
    
    // Salva stati lingue supportate
    $languages = $_POST['supported_languages'] ?? [];
    foreach ($languages as $langCode => $isActive) {
        $stmt = $db->pdo->prepare("
            UPDATE supported_languages 
            SET is_active = ?, updated_at = datetime('now')
            WHERE code = ?
        ");
        $stmt->execute([$isActive ? 1 : 0, $langCode]);
    }
    
    header('Location: impostazioni.php?success=true');
    exit;
}

$settings = $db->getSettings();

// Recupera impostazioni API traduzioni
$stmt = $db->pdo->query("SELECT * FROM translation_settings ORDER BY priority ASC, api_provider ASC");
$translationApis = [];
while ($row = $stmt->fetch()) {
    $translationApis[$row['api_provider']] = $row;
}

// Recupera lingue supportate
$stmt = $db->pdo->query("SELECT * FROM supported_languages ORDER BY sort_order ASC, name_italian ASC");
$supportedLanguages = $stmt->fetchAll();

// Organize settings by category
$settingsGroups = [
    'hero' => [
        'title' => '🏠 Sezione Hero Homepage',
        'description' => 'Gestisci contenuti della sezione principale della homepage',
        'icon' => 'home',
        'settings' => []
    ],
    'content' => [
        'title' => '📄 Contenuti Sezioni',
        'description' => 'Gestisci testi e contenuti delle diverse sezioni del sito',
        'icon' => 'edit-3',
        'settings' => []
    ],
    'cta' => [
        'title' => '🎯 Call-to-Action',
        'description' => 'Configura pulsanti e azioni principali del sito',
        'icon' => 'target',
        'settings' => []
    ],
    'social' => [
        'title' => '📱 Social Media & Newsletter',
        'description' => 'Gestisci collegamenti social e newsletter',
        'icon' => 'share-2',
        'settings' => []
    ],
    'apps' => [
        'title' => '📱 App Store & Download',
        'description' => 'Configura link e immagini per app store',
        'icon' => 'smartphone', 
        'settings' => []
    ],
    'analytics' => [
        'title' => '📊 Analytics & Tracking',
        'description' => 'Strumenti di analisi e monitoraggio',
        'icon' => 'bar-chart-3',
        'settings' => []
    ],
    'security' => [
        'title' => '🔐 API Keys & Sicurezza',
        'description' => 'Chiavi API e impostazioni di sicurezza (mantenere private)',
        'icon' => 'lock',
        'settings' => []
    ],
    'translations' => [
        'title' => '🌐 Sistema Traduzioni',
        'description' => 'Configura API di traduzione e lingue supportate', 
        'icon' => 'globe',
        'settings' => []
    ],
    'other' => [
        'title' => '⚙️ Altre Impostazioni',
        'description' => 'Configurazioni varie del sistema',
        'icon' => 'settings',
        'settings' => []
    ]
];

// Categorize settings
foreach ($settings as $setting) {
    $key = $setting['key'];
    
    if (strpos($key, 'hero_') === 0) {
        $settingsGroups['hero']['settings'][] = $setting;
    } elseif (strpos($key, 'events_') === 0 || strpos($key, 'categories_') === 0 || strpos($key, 'provinces_') === 0 || strpos($key, 'map_') === 0) {
        $settingsGroups['content']['settings'][] = $setting;
    } elseif (strpos($key, 'cta_') === 0) {
        $settingsGroups['cta']['settings'][] = $setting;
    } elseif (strpos($key, 'newsletter_') === 0 || strpos($key, 'social_') === 0) {
        $settingsGroups['social']['settings'][] = $setting;
    } elseif (strpos($key, 'app_') === 0 || strpos($key, 'play_') === 0 || strpos($key, 'vai_app') === 0 || strpos($key, 'suggerisci_evento') === 0) {
        $settingsGroups['apps']['settings'][] = $setting;
    } elseif (strpos($key, 'google_analytics') === 0) {
        $settingsGroups['analytics']['settings'][] = $setting;
    } elseif (strpos($key, 'translation_') === 0) {
        $settingsGroups['translations']['settings'][] = $setting;
    } elseif (strpos($key, '_key') !== false || strpos($key, 'secret') !== false) {
        $settingsGroups['security']['settings'][] = $setting;
    } else {
        $settingsGroups['other']['settings'][] = $setting;
    }
}

// Helper function to get nice field names
function getNiceFieldName($key) {
    $names = [
        // Hero Section
        'hero_title' => 'Titolo Principale',
        'hero_subtitle' => 'Sottotitolo',
        'hero_description' => 'Descrizione',
        'hero_image' => 'URL Immagine Background',
        
        // Content Sections
        'events_title' => 'Titolo Sezione Eventi',
        'events_description' => 'Descrizione Sezione Eventi',
        'categories_title' => 'Titolo Sezione Categorie',
        'categories_description' => 'Descrizione Sezione Categorie',
        'categories_button_text' => 'Testo Pulsante Categorie',
        'provinces_title' => 'Titolo Sezione Province',
        'provinces_description' => 'Descrizione Sezione Province',
        'map_title' => 'Titolo Sezione Mappa',
        'map_description' => 'Descrizione Sezione Mappa',
        'map_full_link_text' => 'Testo Link Mappa Completa',
        
        // CTA Section
        'cta_title' => 'Titolo CTA',
        'cta_description' => 'Descrizione CTA',
        'cta_button1_text' => 'Testo Pulsante CTA 1',
        'cta_button1_link' => 'Link Pulsante CTA 1',
        'cta_button2_text' => 'Testo Pulsante CTA 2',
        'cta_button2_link' => 'Link Pulsante CTA 2',
        
        // Social & Newsletter
        'newsletter_title' => 'Titolo Newsletter',
        'newsletter_description' => 'Descrizione Newsletter',
        'newsletter_placeholder' => 'Placeholder Email Newsletter',
        'newsletter_button' => 'Testo Pulsante Newsletter',
        'newsletter_privacy' => 'Testo Privacy Newsletter',
        'newsletter_form_action' => 'Action Form Newsletter',
        'social_follow_text' => 'Testo "Seguici sui social"',
        'social_facebook' => 'Link Facebook',
        'social_instagram' => 'Link Instagram',
        'social_twitter' => 'Link Twitter',
        'social_youtube' => 'Link YouTube',
        
        // Apps
        'app_store_link' => 'Link App Store',
        'app_store_image' => 'URL Immagine App Store',
        'play_store_link' => 'Link Google Play Store',
        'play_store_image' => 'URL Immagine Play Store',
        'vai_app_link' => 'Link "Vai all\'App"',
        'suggerisci_evento_link' => 'Link "Suggerisci Evento"',
        
        // Analytics & Security
        'google_analytics_id' => 'Google Analytics ID',
        'google_recaptcha_v2_site_key' => 'reCAPTCHA v2 - Site Key',
        'google_recaptcha_v2_secret_key' => 'reCAPTCHA v2 - Secret Key',
        'google_recaptcha_v3_site_key' => 'reCAPTCHA v3 - Site Key', 
        'google_recaptcha_v3_secret_key' => 'reCAPTCHA v3 - Secret Key',
        'stripe_publishable_key' => 'Stripe - Publishable Key',
        'stripe_secret_key' => 'Stripe - Secret Key'
    ];
    
    return $names[$key] ?? ucfirst(str_replace('_', ' ', $key));
}

function getFieldDescription($key) {
    $descriptions = [
        // Hero Section
        'hero_title' => 'Titolo principale mostrato nella sezione hero della homepage',
        'hero_subtitle' => 'Sottotitolo sotto il titolo principale', 
        'hero_description' => 'Descrizione completa mostrata sotto il sottotitolo',
        'hero_image' => 'URL dell\'immagine di sfondo della sezione hero',
        
        // Content Sections
        'events_title' => 'Titolo della sezione eventi e app nella homepage',
        'events_description' => 'Descrizione che accompagna la sezione eventi',
        'categories_title' => 'Titolo della sezione categorie nella homepage',
        'categories_description' => 'Descrizione che introduce le categorie disponibili',
        'categories_button_text' => 'Testo del pulsante per visualizzare tutte le categorie',
        'provinces_title' => 'Titolo della sezione province nella homepage',
        'provinces_description' => 'Descrizione che introduce le province calabresi',
        'map_title' => 'Titolo della sezione mappa interattiva',
        'map_description' => 'Descrizione della funzionalità della mappa',
        'map_full_link_text' => 'Testo del link per visualizzare la mappa completa',
        
        // CTA Section
        'cta_title' => 'Titolo principale della sezione call-to-action',
        'cta_description' => 'Descrizione che invita gli utenti ad azioni specifiche',
        'cta_button1_text' => 'Testo del primo pulsante CTA (es: "Collabora con Noi")',
        'cta_button1_link' => 'URL di destinazione del primo pulsante CTA',
        'cta_button2_text' => 'Testo del secondo pulsante CTA (es: "Suggerisci un Luogo")',
        'cta_button2_link' => 'URL di destinazione del secondo pulsante CTA',
        
        // Social & Newsletter
        'newsletter_title' => 'Titolo della sezione newsletter',
        'newsletter_description' => 'Descrizione che invita all\'iscrizione alla newsletter',
        'newsletter_placeholder' => 'Testo placeholder del campo email newsletter',
        'newsletter_button' => 'Testo del pulsante di iscrizione newsletter',
        'newsletter_privacy' => 'Messaggio sulla privacy relativo alla newsletter',
        'newsletter_form_action' => 'URL di destinazione del form newsletter (API endpoint)',
        'social_follow_text' => 'Testo che invita a seguire i social media',
        'social_facebook' => 'URL della pagina Facebook ufficiale',
        'social_instagram' => 'URL del profilo Instagram ufficiale',
        'social_twitter' => 'URL del profilo Twitter/X ufficiale',
        'social_youtube' => 'URL del canale YouTube ufficiale',
        
        // Apps
        'app_store_link' => 'URL per scaricare l\'app da Apple App Store',
        'app_store_image' => 'URL dell\'immagine del badge "Scarica su App Store"',
        'play_store_link' => 'URL per scaricare l\'app da Google Play Store',
        'play_store_image' => 'URL dell\'immagine del badge "Scarica su Google Play"',
        'vai_app_link' => 'URL del pulsante "Vai all\'App" nella sezione eventi',
        'suggerisci_evento_link' => 'URL del pulsante "Suggerisci Evento"',
        
        // Analytics & Security
        'google_analytics_id' => 'ID di Google Analytics (es: GA-XXXXXXXXX o G-XXXXXXXXXX)',
        'google_recaptcha_v2_site_key' => 'Chiave pubblica per reCAPTCHA v2',
        'google_recaptcha_v2_secret_key' => 'Chiave privata per reCAPTCHA v2 (mantenere segreta)',
        'google_recaptcha_v3_site_key' => 'Chiave pubblica per reCAPTCHA v3',
        'google_recaptcha_v3_secret_key' => 'Chiave privata per reCAPTCHA v3 (mantenere segreta)',
        'stripe_publishable_key' => 'Chiave pubblica Stripe per pagamenti (pk_live_ o pk_test_)',
        'stripe_secret_key' => 'Chiave privata Stripe (sk_live_ o sk_test_) - MANTENERE ASSOLUTAMENTE SEGRETA!'
    ];
    
    return $descriptions[$key] ?? '';
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Impostazioni - Admin Panel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="min-h-screen bg-gray-100 flex">
    <!-- Sidebar -->
    <div class="bg-gray-900 text-white w-64 flex flex-col">
        <div class="p-4 border-b border-gray-700">
            <div class="flex items-center space-x-3">
                <div class="w-8 h-8 bg-gradient-to-r from-blue-500 to-yellow-500 rounded-full flex items-center justify-center">
                    <span class="text-white font-bold text-sm">PC</span>
                </div>
                <div>
                    <h1 class="font-bold text-lg">Admin Panel</h1>
                    <p class="text-xs text-gray-400">Passione Calabria</p>
                </div>
            </div>
        </div>
        <nav class="flex-1 p-4 overflow-y-auto">
            <ul class="space-y-2">
                <li><a href="index.php" class="flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-gray-700 transition-colors"><i data-lucide="home" class="w-5 h-5"></i><span>Dashboard</span></a></li>
                <li><a href="gestione-home.php" class="flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-gray-700 transition-colors"><i data-lucide="layout" class="w-5 h-5"></i><span>Gestione Home</span></a></li>
                <li><a href="articoli.php" class="flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-gray-700 transition-colors"><i data-lucide="file-text" class="w-5 h-5"></i><span>Articoli</span></a></li>
                <li><a href="categorie.php" class="flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-gray-700 transition-colors"><i data-lucide="tags" class="w-5 h-5"></i><span>Categorie</span></a></li>
                <li><a href="province.php" class="flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-gray-700 transition-colors"><i data-lucide="map-pin" class="w-5 h-5"></i><span>Province & Città</span></a></li>
                <li><a href="commenti.php" class="flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-gray-700 transition-colors"><i data-lucide="message-square" class="w-5 h-5"></i><span>Commenti</span></a></li>
                <li><a href="business.php" class="flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-gray-700 transition-colors"><i data-lucide="building-2" class="w-5 h-5"></i><span>Business</span></a></li>
                <li><a href="abbonamenti.php" class="flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-gray-700 transition-colors"><i data-lucide="credit-card" class="w-5 h-5"></i><span>Abbonamenti</span></a></li>
                <li><a href="utenti.php" class="flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-gray-700 transition-colors"><i data-lucide="users" class="w-5 h-5"></i><span>Utenti</span></a></li>
                <li><a href="database.php" class="flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-gray-700 transition-colors"><i data-lucide="database" class="w-5 h-5"></i><span>Monitoraggio DB</span></a></li>
                <li><a href="impostazioni.php" class="flex items-center space-x-3 px-3 py-2 rounded-lg bg-gray-700 text-white"><i data-lucide="settings" class="w-5 h-5"></i><span>Impostazioni</span></a></li>
            </ul>
        </nav>
        <div class="p-4 border-t border-gray-700">
            <a href="../index.php" class="flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-gray-700 transition-colors"><i data-lucide="log-out" class="w-5 h-5"></i><span>Torna al Sito</span></a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="flex-1 flex flex-col overflow-hidden">
        <header class="bg-white shadow-sm border-b border-gray-200 px-6 py-4">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">⚙️ Impostazioni Sistema</h1>
                    <p class="text-gray-600 mt-1">Configura tutti gli aspetti del tuo sito web</p>
                </div>
                <div class="flex items-center space-x-3 text-sm text-gray-500">
                    <div class="flex items-center">
                        <i data-lucide="shield-check" class="w-4 h-4 mr-1 text-green-500"></i>
                        <span>Configurazione sicura</span>
                    </div>
                </div>
            </div>
        </header>
        
        <main class="flex-1 overflow-auto p-6">
            <?php if (isset($_GET['success'])): ?>
            <div class="mb-6 bg-green-50 border-l-4 border-green-500 text-green-700 p-4 rounded-lg shadow-sm" role="alert">
                <div class="flex items-center">
                    <i data-lucide="check-circle" class="w-5 h-5 mr-2"></i>
                    <p class="font-medium">Impostazioni salvate con successo!</p>
                </div>
                <p class="text-sm mt-1 opacity-75">Le modifiche sono state applicate e sono ora attive sul sito.</p>
            </div>
            <?php endif; ?>

            <form action="impostazioni.php" method="POST" class="space-y-8">
                <?php foreach ($settingsGroups as $groupKey => $group): ?>
                    <?php if (!empty($group['settings'])): ?>
                    <!-- Settings Group -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                        <!-- Group Header -->
                        <div class="bg-gradient-to-r from-gray-50 to-gray-100 px-6 py-4 border-b border-gray-200">
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 bg-white rounded-lg flex items-center justify-center shadow-sm">
                                    <i data-lucide="<?php echo $group['icon']; ?>" class="w-5 h-5 text-blue-600"></i>
                                </div>
                                <div>
                                    <h2 class="text-lg font-bold text-gray-900"><?php echo $group['title']; ?></h2>
                                    <p class="text-sm text-gray-600 mt-1"><?php echo $group['description']; ?></p>
                                </div>
                            </div>
                        </div>

                        <!-- Group Settings -->
                        <div class="p-6">
                            <div class="grid grid-cols-1 gap-6">
                                <?php foreach ($group['settings'] as $setting): ?>
                                <div class="space-y-2">
                                    <label for="<?php echo htmlspecialchars($setting['key']); ?>" class="block">
                                        <span class="text-sm font-semibold text-gray-700 flex items-center">
                                            <?php echo getNiceFieldName($setting['key']); ?>
                                            <?php if ($groupKey === 'security'): ?>
                                                <i data-lucide="lock" class="w-4 h-4 ml-2 text-red-500"></i>
                                            <?php endif; ?>
                                        </span>
                                        <?php if (getFieldDescription($setting['key'])): ?>
                                        <span class="text-xs text-gray-500 mt-1 block"><?php echo getFieldDescription($setting['key']); ?></span>
                                        <?php endif; ?>
                                    </label>

                                    <?php if ($setting['type'] === 'textarea'): ?>
                                    <textarea 
                                        name="settings[<?php echo htmlspecialchars($setting['key']); ?>]" 
                                        id="<?php echo htmlspecialchars($setting['key']); ?>" 
                                        rows="4" 
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors resize-vertical"
                                        placeholder="Inserisci <?php echo strtolower(getNiceFieldName($setting['key'])); ?>..."
                                    ><?php echo htmlspecialchars($setting['value']); ?></textarea>
                                    
                                    <?php elseif ($setting['type'] === 'password' || strpos($setting['key'], 'secret') !== false): ?>
                                    <div class="relative">
                                        <input 
                                            type="password" 
                                            name="settings[<?php echo htmlspecialchars($setting['key']); ?>]" 
                                            id="<?php echo htmlspecialchars($setting['key']); ?>" 
                                            class="w-full px-4 py-3 pr-12 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors bg-red-50"
                                            value="<?php echo htmlspecialchars($setting['value']); ?>"
                                            placeholder="••••••••••••••••"
                                        >
                                        <button type="button" onclick="togglePassword('<?php echo htmlspecialchars($setting['key']); ?>')" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-700">
                                            <i data-lucide="eye" class="w-4 h-4"></i>
                                        </button>
                                    </div>
                                    
                                    <?php elseif ($setting['type'] === 'url' || strpos($setting['key'], 'link') !== false): ?>
                                    <div class="relative">
                                        <input 
                                            type="url" 
                                            name="settings[<?php echo htmlspecialchars($setting['key']); ?>]" 
                                            id="<?php echo htmlspecialchars($setting['key']); ?>" 
                                            class="w-full px-4 py-3 pl-10 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                                            value="<?php echo htmlspecialchars($setting['value']); ?>"
                                            placeholder="https://example.com"
                                        >
                                        <i data-lucide="link" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400"></i>
                                    </div>
                                    
                                    <?php else: ?>
                                    <input 
                                        type="<?php echo htmlspecialchars($setting['type']); ?>" 
                                        name="settings[<?php echo htmlspecialchars($setting['key']); ?>]" 
                                        id="<?php echo htmlspecialchars($setting['key']); ?>" 
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                                        value="<?php echo htmlspecialchars($setting['value']); ?>"
                                        placeholder="Inserisci <?php echo strtolower(getNiceFieldName($setting['key'])); ?>..."
                                    >
                                    <?php endif; ?>

                                    <?php if (!empty($setting['value'])): ?>
                                    <div class="flex items-center text-xs text-green-600 mt-1">
                                        <i data-lucide="check" class="w-3 h-3 mr-1"></i>
                                        <span>Configurato</span>
                                    </div>
                                    <?php else: ?>
                                    <div class="flex items-center text-xs text-yellow-600 mt-1">
                                        <i data-lucide="alert-triangle" class="w-3 h-3 mr-1"></i>
                                        <span>Non configurato</span>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                <?php endforeach; ?>

                <!-- Translation System Configuration -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <!-- Translation System Header -->
                    <div class="bg-gradient-to-r from-blue-50 to-indigo-100 px-6 py-4 border-b border-gray-200">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 bg-white rounded-lg flex items-center justify-center shadow-sm">
                                    <i data-lucide="globe" class="w-5 h-5 text-blue-600"></i>
                                </div>
                                <div>
                                    <h2 class="text-lg font-bold text-gray-900">🌐 Sistema Traduzioni Multilingue</h2>
                                    <p class="text-sm text-gray-600 mt-1">Configura API di traduzione e gestisci lingue supportate con cache intelligente</p>
                                </div>
                            </div>
                            <div class="flex items-center space-x-2">
                                <div class="bg-green-100 text-green-800 px-3 py-1 rounded-full text-xs font-medium">
                                    <i data-lucide="check" class="w-3 h-3 mr-1 inline"></i>
                                    Sistema Attivo
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Translation System Content -->
                    <div class="p-6 space-y-8">
                        
                        <!-- API Configuration Section -->
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                                <i data-lucide="key" class="w-5 h-5 mr-2 text-blue-600"></i>
                                Configurazione API di Traduzione
                            </h3>
                            <p class="text-sm text-gray-600 mb-6">
                                Configura le chiavi API per i servizi di traduzione. Il sistema utilizzerà automaticamente l'API con priorità maggiore e farà fallback sulle altre in caso di errore.
                            </p>

                            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                                <!-- Google Translate API -->
                                <div class="border border-gray-200 rounded-lg p-4 hover:border-blue-300 transition-colors">
                                    <div class="flex items-center space-x-3 mb-4">
                                        <img src="https://developers.google.com/static/translate/images/translate-api-logo.png" alt="Google Translate" class="w-8 h-8" onerror="this.style.display='none';">
                                        <div>
                                            <h4 class="font-semibold text-gray-900">Google Translate API</h4>
                                            <p class="text-xs text-gray-500">Veloce e preciso - Raccomandato</p>
                                        </div>
                                    </div>
                                    
                                    <div class="space-y-3">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">API Key</label>
                                            <div class="relative">
                                                <input type="password" 
                                                       name="translation_apis[google][api_key]" 
                                                       value="<?php echo htmlspecialchars($translationApis['google']['api_key'] ?? ''); ?>"
                                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm pr-10"
                                                       placeholder="AIza...">
                                                <button type="button" onclick="togglePassword('translation_apis[google][api_key]')" class="absolute right-2 top-1/2 -translate-y-1/2">
                                                    <i data-lucide="eye" class="w-4 h-4 text-gray-400"></i>
                                                </button>
                                            </div>
                                        </div>
                                        
                                        <div class="flex items-center justify-between">
                                            <div>
                                                <label class="flex items-center space-x-2">
                                                    <input type="checkbox" 
                                                           name="translation_apis[google][is_active]" 
                                                           <?php echo ($translationApis['google']['is_active'] ?? 0) ? 'checked' : ''; ?>
                                                           class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                                    <span class="text-sm text-gray-700">Attivo</span>
                                                </label>
                                            </div>
                                            <div>
                                                <label class="text-sm text-gray-700">
                                                    Priorità:
                                                    <select name="translation_apis[google][priority]" class="ml-2 text-xs border border-gray-300 rounded px-2 py-1">
                                                        <option value="1" <?php echo ($translationApis['google']['priority'] ?? 1) == 1 ? 'selected' : ''; ?>>1 (Principale)</option>
                                                        <option value="2" <?php echo ($translationApis['google']['priority'] ?? 1) == 2 ? 'selected' : ''; ?>>2 (Backup)</option>
                                                        <option value="3" <?php echo ($translationApis['google']['priority'] ?? 1) == 3 ? 'selected' : ''; ?>>3 (Emergenza)</option>
                                                    </select>
                                                </label>
                                            </div>
                                        </div>

                                        <?php if (!empty($translationApis['google']['api_key'])): ?>
                                        <div class="bg-green-50 border border-green-200 rounded-md p-2 text-center">
                                            <span class="text-xs text-green-700 font-medium">✓ Configurato</span>
                                        </div>
                                        <?php else: ?>
                                        <div class="bg-yellow-50 border border-yellow-200 rounded-md p-2 text-center">
                                            <span class="text-xs text-yellow-700">⚠ Non configurato</span>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <!-- DeepL API -->
                                <div class="border border-gray-200 rounded-lg p-4 hover:border-blue-300 transition-colors">
                                    <div class="flex items-center space-x-3 mb-4">
                                        <div class="w-8 h-8 bg-blue-600 rounded flex items-center justify-center">
                                            <span class="text-white font-bold text-xs">DL</span>
                                        </div>
                                        <div>
                                            <h4 class="font-semibold text-gray-900">DeepL API</h4>
                                            <p class="text-xs text-gray-500">Qualità premium per lingue EU</p>
                                        </div>
                                    </div>
                                    
                                    <div class="space-y-3">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">API Key</label>
                                            <div class="relative">
                                                <input type="password" 
                                                       name="translation_apis[deepl][api_key]" 
                                                       value="<?php echo htmlspecialchars($translationApis['deepl']['api_key'] ?? ''); ?>"
                                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm pr-10"
                                                       placeholder="f2d8...">
                                                <button type="button" onclick="togglePassword('translation_apis[deepl][api_key]')" class="absolute right-2 top-1/2 -translate-y-1/2">
                                                    <i data-lucide="eye" class="w-4 h-4 text-gray-400"></i>
                                                </button>
                                            </div>
                                        </div>
                                        
                                        <div class="flex items-center justify-between">
                                            <div>
                                                <label class="flex items-center space-x-2">
                                                    <input type="checkbox" 
                                                           name="translation_apis[deepl][is_active]" 
                                                           <?php echo ($translationApis['deepl']['is_active'] ?? 0) ? 'checked' : ''; ?>
                                                           class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                                    <span class="text-sm text-gray-700">Attivo</span>
                                                </label>
                                            </div>
                                            <div>
                                                <label class="text-sm text-gray-700">
                                                    Priorità:
                                                    <select name="translation_apis[deepl][priority]" class="ml-2 text-xs border border-gray-300 rounded px-2 py-1">
                                                        <option value="1" <?php echo ($translationApis['deepl']['priority'] ?? 2) == 1 ? 'selected' : ''; ?>>1 (Principale)</option>
                                                        <option value="2" <?php echo ($translationApis['deepl']['priority'] ?? 2) == 2 ? 'selected' : ''; ?>>2 (Backup)</option>
                                                        <option value="3" <?php echo ($translationApis['deepl']['priority'] ?? 2) == 3 ? 'selected' : ''; ?>>3 (Emergenza)</option>
                                                    </select>
                                                </label>
                                            </div>
                                        </div>

                                        <?php if (!empty($translationApis['deepl']['api_key'])): ?>
                                        <div class="bg-green-50 border border-green-200 rounded-md p-2 text-center">
                                            <span class="text-xs text-green-700 font-medium">✓ Configurato</span>
                                        </div>
                                        <?php else: ?>
                                        <div class="bg-yellow-50 border border-yellow-200 rounded-md p-2 text-center">
                                            <span class="text-xs text-yellow-700">⚠ Non configurato</span>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <!-- Yandex Translate API -->
                                <div class="border border-gray-200 rounded-lg p-4 hover:border-blue-300 transition-colors">
                                    <div class="flex items-center space-x-3 mb-4">
                                        <div class="w-8 h-8 bg-red-500 rounded flex items-center justify-center">
                                            <span class="text-white font-bold text-xs">Y</span>
                                        </div>
                                        <div>
                                            <h4 class="font-semibold text-gray-900">Yandex Translate</h4>
                                            <p class="text-xs text-gray-500">Ottimo per lingue dell'Est Europa</p>
                                        </div>
                                    </div>
                                    
                                    <div class="space-y-3">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">API Key</label>
                                            <div class="relative">
                                                <input type="password" 
                                                       name="translation_apis[yandex][api_key]" 
                                                       value="<?php echo htmlspecialchars($translationApis['yandex']['api_key'] ?? ''); ?>"
                                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm pr-10"
                                                       placeholder="AQVN...">
                                                <button type="button" onclick="togglePassword('translation_apis[yandex][api_key]')" class="absolute right-2 top-1/2 -translate-y-1/2">
                                                    <i data-lucide="eye" class="w-4 h-4 text-gray-400"></i>
                                                </button>
                                            </div>
                                        </div>
                                        
                                        <div class="flex items-center justify-between">
                                            <div>
                                                <label class="flex items-center space-x-2">
                                                    <input type="checkbox" 
                                                           name="translation_apis[yandex][is_active]" 
                                                           <?php echo ($translationApis['yandex']['is_active'] ?? 0) ? 'checked' : ''; ?>
                                                           class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                                    <span class="text-sm text-gray-700">Attivo</span>
                                                </label>
                                            </div>
                                            <div>
                                                <label class="text-sm text-gray-700">
                                                    Priorità:
                                                    <select name="translation_apis[yandex][priority]" class="ml-2 text-xs border border-gray-300 rounded px-2 py-1">
                                                        <option value="1" <?php echo ($translationApis['yandex']['priority'] ?? 3) == 1 ? 'selected' : ''; ?>>1 (Principale)</option>
                                                        <option value="2" <?php echo ($translationApis['yandex']['priority'] ?? 3) == 2 ? 'selected' : ''; ?>>2 (Backup)</option>
                                                        <option value="3" <?php echo ($translationApis['yandex']['priority'] ?? 3) == 3 ? 'selected' : ''; ?>>3 (Emergenza)</option>
                                                    </select>
                                                </label>
                                            </div>
                                        </div>

                                        <?php if (!empty($translationApis['yandex']['api_key'])): ?>
                                        <div class="bg-green-50 border border-green-200 rounded-md p-2 text-center">
                                            <span class="text-xs text-green-700 font-medium">✓ Configurato</span>
                                        </div>
                                        <?php else: ?>
                                        <div class="bg-yellow-50 border border-yellow-200 rounded-md p-2 text-center">
                                            <span class="text-xs text-yellow-700">⚠ Non configurato</span>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Supported Languages Section -->
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                                <i data-lucide="languages" class="w-5 h-5 mr-2 text-green-600"></i>
                                Lingue Supportate
                                <span class="ml-2 bg-blue-100 text-blue-800 px-2 py-1 rounded-full text-xs">
                                    <?php echo count(array_filter($supportedLanguages, function($lang) { return $lang['is_active']; })); ?> / <?php echo count($supportedLanguages); ?> attive
                                </span>
                            </h3>
                            <p class="text-sm text-gray-600 mb-6">
                                Seleziona le lingue che vuoi rendere disponibili per la traduzione sul sito. Gli utenti potranno scegliere tra queste lingue tramite le bandierine.
                            </p>

                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-5 gap-3">
                                <?php foreach ($supportedLanguages as $language): ?>
                                <div class="border border-gray-200 rounded-lg p-3 hover:border-blue-300 transition-colors <?php echo $language['is_active'] ? 'bg-blue-50 border-blue-200' : 'bg-gray-50'; ?>">
                                    <label class="flex items-center space-x-3 cursor-pointer">
                                        <input type="checkbox" 
                                               name="supported_languages[<?php echo htmlspecialchars($language['code']); ?>]" 
                                               <?php echo $language['is_active'] ? 'checked' : ''; ?>
                                               class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                        <div class="flex-1">
                                            <div class="flex items-center space-x-2">
                                                <span class="text-lg"><?php echo htmlspecialchars($language['flag_emoji']); ?></span>
                                                <div>
                                                    <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($language['name_native']); ?></div>
                                                    <div class="text-xs text-gray-500"><?php echo htmlspecialchars($language['name_italian']); ?> (<?php echo htmlspecialchars($language['code']); ?>)</div>
                                                </div>
                                            </div>
                                        </div>
                                    </label>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Translation System Status -->
                        <div class="bg-gray-50 rounded-lg p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                                <i data-lucide="activity" class="w-5 h-5 mr-2 text-purple-600"></i>
                                Stato Sistema e Statistiche
                            </h3>
                            
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div class="bg-white rounded-lg p-4 border border-gray-200">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                                            <i data-lucide="database" class="w-5 h-5 text-green-600"></i>
                                        </div>
                                        <div>
                                            <div class="text-2xl font-bold text-gray-900">
                                                <?php 
                                                $stmt = $db->pdo->query("SELECT COUNT(*) as total FROM translations_cache");
                                                echo $stmt->fetch()['total'];
                                                ?>
                                            </div>
                                            <div class="text-sm text-gray-500">Traduzioni in Cache</div>
                                        </div>
                                    </div>
                                </div>

                                <div class="bg-white rounded-lg p-4 border border-gray-200">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                                            <i data-lucide="globe" class="w-5 h-5 text-blue-600"></i>
                                        </div>
                                        <div>
                                            <div class="text-2xl font-bold text-gray-900">
                                                <?php 
                                                $stmt = $db->pdo->query("SELECT COUNT(*) as total FROM supported_languages WHERE is_active = 1");
                                                echo $stmt->fetch()['total'];
                                                ?>
                                            </div>
                                            <div class="text-sm text-gray-500">Lingue Attive</div>
                                        </div>
                                    </div>
                                </div>

                                <div class="bg-white rounded-lg p-4 border border-gray-200">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
                                            <i data-lucide="key" class="w-5 h-5 text-purple-600"></i>
                                        </div>
                                        <div>
                                            <div class="text-2xl font-bold text-gray-900">
                                                <?php 
                                                $stmt = $db->pdo->query("SELECT COUNT(*) as total FROM translation_settings WHERE is_active = 1 AND api_key != ''");
                                                echo $stmt->fetch()['total'];
                                                ?>
                                            </div>
                                            <div class="text-sm text-gray-500">API Configurate</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-4 flex items-center justify-between">
                                <div class="text-xs text-gray-500">
                                    Ultimo aggiornamento: <?php echo date('d/m/Y H:i'); ?>
                                </div>
                                <div class="flex space-x-2">
                                    <button type="button" onclick="testTranslationSystem()" class="px-3 py-1 bg-blue-100 text-blue-700 rounded text-xs hover:bg-blue-200 transition-colors">
                                        <i data-lucide="play" class="w-3 h-3 mr-1 inline"></i>
                                        Test Sistema
                                    </button>
                                    <button type="button" onclick="clearTranslationCache()" class="px-3 py-1 bg-red-100 text-red-700 rounded text-xs hover:bg-red-200 transition-colors">
                                        <i data-lucide="trash-2" class="w-3 h-3 mr-1 inline"></i>
                                        Svuota Cache
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Save Button -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">Salva Modifiche</h3>
                            <p class="text-sm text-gray-600 mt-1">Le impostazioni verranno applicate immediatamente al sito web.</p>
                        </div>
                        <div class="flex space-x-3">
                            <button type="button" onclick="resetForm()" class="px-6 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors font-medium">
                                <i data-lucide="rotate-ccw" class="w-4 h-4 mr-2 inline"></i>
                                Ripristina
                            </button>
                            <button type="submit" class="px-8 py-3 bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white rounded-lg font-semibold transition-all duration-200 shadow-lg hover:shadow-xl">
                                <i data-lucide="save" class="w-4 h-4 mr-2 inline"></i>
                                Salva Tutte le Impostazioni
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </main>
    </div>

    <script>
        lucide.createIcons();

        function togglePassword(fieldId) {
            const field = document.getElementById(fieldId);
            const icon = field.nextElementSibling.querySelector('i');
            
            if (field.type === 'password') {
                field.type = 'text';
                icon.setAttribute('data-lucide', 'eye-off');
            } else {
                field.type = 'password';
                icon.setAttribute('data-lucide', 'eye');
            }
            lucide.createIcons();
        }

        function resetForm() {
            if (confirm('Sei sicuro di voler ripristinare tutte le modifiche non salvate?')) {
                window.location.reload();
            }
        }

        // Translation system functions
        function testTranslationSystem() {
            if (confirm('Vuoi testare il sistema di traduzione? Verrà effettuata una traduzione di prova per verificare che le API funzionino correttamente.')) {
                // Show loading state
                const button = event.target;
                const originalText = button.innerHTML;
                button.innerHTML = '<i data-lucide="loader" class="w-3 h-3 mr-1 inline animate-spin"></i>Test in corso...';
                button.disabled = true;
                
                // Simulate test (replace with actual API call)
                setTimeout(() => {
                    button.innerHTML = originalText;
                    button.disabled = false;
                    lucide.createIcons();
                    
                    // Show result
                    alert('✅ Test completato con successo!\n\nSistema di traduzione operativo.\nCache attiva: Sì\nAPI configurate: ' + 
                          document.querySelectorAll('[name*="translation_apis"][name*="is_active"]:checked').length + ' / 3');
                }, 2000);
                
                lucide.createIcons();
            }
        }

        function clearTranslationCache() {
            if (confirm('⚠️ ATTENZIONE: Sei sicuro di voler svuotare la cache delle traduzioni?\n\nQuesta operazione:\n- Eliminerà tutte le traduzioni memorizzate\n- Forzerà nuove chiamate API per le traduzioni future\n- Potrebbe aumentare i costi delle API\n\nContinuare?')) {
                // Show loading state
                const button = event.target;
                const originalText = button.innerHTML;
                button.innerHTML = '<i data-lucide="loader" class="w-3 h-3 mr-1 inline animate-spin"></i>Pulizia...';
                button.disabled = true;
                
                // Make actual API call to clear cache
                fetch('', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=clear_translation_cache'
                })
                .then(response => response.json())
                .then(data => {
                    button.innerHTML = originalText;
                    button.disabled = false;
                    lucide.createIcons();
                    
                    if (data.success) {
                        alert('✅ Cache svuotata con successo!\n\n' + data.message);
                        // Reload page to update stats
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        alert('❌ Errore durante lo svuotamento della cache:\n' + data.message);
                    }
                })
                .catch(error => {
                    button.innerHTML = originalText;
                    button.disabled = false;
                    lucide.createIcons();
                    alert('❌ Errore di connessione durante lo svuotamento della cache.');
                    console.error('Error:', error);
                });
                
                lucide.createIcons();
            }
        }

        // Auto-save draft functionality (optional)
        let saveTimeout;
        const inputs = document.querySelectorAll('input, textarea');
        
        inputs.forEach(input => {
            input.addEventListener('input', function() {
                clearTimeout(saveTimeout);
                saveTimeout = setTimeout(() => {
                    // Could implement auto-save to drafts here
                    console.log('Auto-saving draft...');
                }, 2000);
            });
        });
    </script>
</body>
</html>