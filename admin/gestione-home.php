<?php
require_once '../includes/config.php';
require_once '../includes/database.php';

// Controlla autenticazione (da implementare)
// // requireLogin(); // DISABILITATO

$db = new Database();

// Gestione upload immagini
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['image'])) {
    $uploadDir = '../uploads/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    $file = $_FILES['image'];
    $fileName = time() . '_' . $file['name'];
    $uploadPath = $uploadDir . $fileName;
    
    if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
        echo json_encode(['success' => true, 'path' => '/uploads/' . $fileName]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Errore upload']);
    }
    exit;
}

// Gestione aggiornamenti sezioni
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'update_hero':
            $db->updateHomeSection('hero', [
                'title' => $_POST['hero_title'],
                'subtitle' => $_POST['hero_subtitle'], 
                'description' => $_POST['hero_description'],
                'image_path' => $_POST['hero_image'],
                'custom_data' => json_encode([
                    'button1_text' => $_POST['button1_text'],
                    'button1_link' => $_POST['button1_link'],
                    'button2_text' => $_POST['button2_text'],
                    'button2_link' => $_POST['button2_link']
                ])
            ]);
            break;
            
        case 'update_events':
            $settings = [
                'app_store_link' => $_POST['app_store_link'] ?? '',
                'play_store_link' => $_POST['play_store_link'] ?? '',
                'app_store_image' => $_POST['app_store_image'] ?? '',
                'play_store_image' => $_POST['play_store_image'] ?? '',
                'vai_app_link' => $_POST['vai_app_link'] ?? '',
                'suggerisci_evento_link' => $_POST['suggerisci_evento_link'] ?? ''
            ];
            
            foreach ($settings as $key => $value) {
                $db->setSetting($key, $value);
            }
            break;
            
        case 'update_categories':
            $settings = [
                'categories_title' => $_POST['categories_title'] ?? 'Esplora per Categoria',
                'categories_description' => $_POST['categories_description'] ?? 'Scopri la Calabria attraverso le sue diverse sfaccettature',
                'categories_button_text' => $_POST['categories_button_text'] ?? 'Vedi Tutte le Categorie',
                'categories_bg_image' => $_POST['categories_bg_image'] ?? ''
            ];
            foreach ($settings as $key => $value) {
                $db->setSetting($key, $value);
            }
            break;
            
        case 'update_provinces':
            $settings = [
                'provinces_title' => $_POST['provinces_title'] ?? 'Esplora le Province',
                'provinces_description' => $_POST['provinces_description'] ?? 'Ogni provincia calabrese custodisce tesori unici',
                'provinces_bg_image' => $_POST['provinces_bg_image'] ?? ''
            ];
            foreach ($settings as $key => $value) {
                $db->setSetting($key, $value);
            }
            break;
            
        case 'update_map':
            $settings = [
                'map_title' => $_POST['map_title'] ?? 'Esplora la Mappa Interattiva',
                'map_description' => $_POST['map_description'] ?? 'Naviga attraverso la Calabria con la nostra mappa interattiva',
                'map_full_link_text' => $_POST['map_full_link_text'] ?? 'Visualizza mappa completa'
            ];
            foreach ($settings as $key => $value) {
                $db->setSetting($key, $value);
            }
            break;
            
        case 'update_cta':
            $settings = [
                'cta_title' => $_POST['cta_title'] ?? 'Vuoi far Conoscere la Tua Calabria?',
                'cta_description' => $_POST['cta_description'] ?? 'Unisciti alla nostra community! Condividi i tuoi luoghi del cuore',
                'cta_button1_text' => $_POST['cta_button1_text'] ?? 'Collabora con Noi',
                'cta_button1_link' => $_POST['cta_button1_link'] ?? 'collabora.php',
                'cta_button2_text' => $_POST['cta_button2_text'] ?? 'Suggerisci un Luogo',
                'cta_button2_link' => $_POST['cta_button2_link'] ?? 'suggerisci.php',
                'cta_bg_image' => $_POST['cta_bg_image'] ?? ''
            ];
            foreach ($settings as $key => $value) {
                $db->setSetting($key, $value);
            }
            break;
            
        case 'update_newsletter':
            $settings = [
                'newsletter_title' => $_POST['newsletter_title'] ?? 'Resta Connesso con la Calabria',
                'newsletter_description' => $_POST['newsletter_description'] ?? 'Iscriviti alla nostra newsletter per ricevere i migliori contenuti',
                'newsletter_placeholder' => $_POST['newsletter_placeholder'] ?? 'Inserisci la tua email',
                'newsletter_button' => $_POST['newsletter_button'] ?? 'Iscriviti Gratis',
                'newsletter_privacy' => $_POST['newsletter_privacy'] ?? 'Rispettiamo la tua privacy. Niente spam, solo contenuti di qualità.',
                'newsletter_form_action' => $_POST['newsletter_form_action'] ?? 'api/newsletter.php',
                'newsletter_bg_image' => $_POST['newsletter_bg_image'] ?? ''
            ];
            foreach ($settings as $key => $value) {
                $db->setSetting($key, $value);
            }
            break;
            
        case 'update_social':
            $settings = [
                'social_facebook' => $_POST['social_facebook'] ?? '',
                'social_instagram' => $_POST['social_instagram'] ?? '',
                'social_twitter' => $_POST['social_twitter'] ?? '',
                'social_youtube' => $_POST['social_youtube'] ?? '',
                'social_follow_text' => $_POST['social_follow_text'] ?? 'Seguici sui social media'
            ];
            foreach ($settings as $key => $value) {
                $db->setSetting($key, $value);
            }
            break;
    }
    
    $success = true;
}

// Carica dati attuali
$homeSections = $db->getHomeSections();
$heroSection = null;
foreach ($homeSections as $section) {
    if ($section['section_name'] === 'hero') {
        $heroSection = $section;
        break;
    }
}

$heroData = $heroSection ? json_decode($heroSection['custom_data'], true) : [];
$settings = $db->getSettings();
$settingsArray = [];
foreach ($settings as $setting) {
    $settingsArray[$setting['key']] = $setting['value'];
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestione Home - Admin Panel</title>
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
                    <p class="text-xs text-gray-400">Gestione Home</p>
                </div>
            </div>
        </div>
        <nav class="flex-1 p-4 overflow-y-auto">
            <ul class="space-y-2">
                <li>
                    <a href="index.php" class="flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-gray-700 transition-colors">
                        <i data-lucide="home" class="w-5 h-5"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="gestione-home.php" class="flex items-center space-x-3 px-3 py-2 rounded-lg bg-gray-700 text-white">
                        <i data-lucide="layout" class="w-5 h-5"></i>
                        <span>Gestione Home</span>
                    </a>
                </li>
                <li>
                    <a href="articoli.php" class="flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-gray-700 transition-colors">
                        <i data-lucide="file-text" class="w-5 h-5"></i>
                        <span>Articoli</span>
                    </a>
                </li>
                <li>
                    <a href="categorie.php" class="flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-gray-700 transition-colors">
                        <i data-lucide="tags" class="w-5 h-5"></i>
                        <span>Categorie</span>
                    </a>
                </li>
                <li>
                    <a href="pagine-statiche.php" class="flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-gray-700 transition-colors">
                        <i data-lucide="file-text" class="w-5 h-5"></i>
                        <span>Pagine Statiche</span>
                    </a>
                </li>
                <li>
                    <a href="province.php" class="flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-gray-700 transition-colors">
                        <i data-lucide="map-pin" class="w-5 h-5"></i>
                        <span>Province & Città</span>
                    </a>
                </li>
                <li>
                    <a href="commenti.php" class="flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-gray-700 transition-colors">
                        <i data-lucide="message-square" class="w-5 h-5"></i>
                        <span>Commenti</span>
                    </a>
                </li>
                <li>
                    <a href="business.php" class="flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-gray-700 transition-colors">
                        <i data-lucide="building-2" class="w-5 h-5"></i>
                        <span>Business</span>
                    </a>
                </li>
                <li>
                    <a href="abbonamenti.php" class="flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-gray-700 transition-colors">
                        <i data-lucide="credit-card" class="w-5 h-5"></i>
                        <span>Abbonamenti</span>
                    </a>
                </li>
                <li>
                    <a href="utenti.php" class="flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-gray-700 transition-colors">
                        <i data-lucide="users" class="w-5 h-5"></i>
                        <span>Utenti</span>
                    </a>
                </li>
                <li>
                    <a href="gestione-traduzione.php" class="flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-gray-700 transition-colors">
                        <i data-lucide="globe" class="w-5 h-5"></i>
                        <span>Gestione Traduzione</span>
                    </a>
                </li>
                <li>
                    <a href="database.php" class="flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-gray-700 transition-colors">
                        <i data-lucide="database" class="w-5 h-5"></i>
                        <span>Monitoraggio DB</span>
                    </a>
                </li>
                <li>
                    <a href="impostazioni.php" class="flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-gray-700 transition-colors">
                        <i data-lucide="settings" class="w-5 h-5"></i>
                        <span>Impostazioni</span>
                    </a>
                </li>
            </ul>
        </nav>
        <div class="p-4 border-t border-gray-700">
            <a href="../index.php" class="flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-gray-700 transition-colors"><i data-lucide="log-out" class="w-5 h-5"></i><span>Torna al Sito</span></a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="flex-1 flex flex-col overflow-hidden">
        <header class="bg-white shadow-sm border-b border-gray-200 px-6 py-4">
            <h1 class="text-2xl font-bold text-gray-900">Gestione Homepage</h1>
            <p class="text-gray-600">Modifica i contenuti e le impostazioni della homepage</p>
        </header>
        
        <main class="flex-1 overflow-auto p-6">
            <?php if (isset($success)): ?>
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6" role="alert">
                <p>✅ Modifiche salvate con successo!</p>
            </div>
            <?php endif; ?>

            <!-- Sezione Hero -->
            <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
                <h2 class="text-xl font-bold text-gray-900 mb-4">🎯 Sezione Hero</h2>
                <form action="" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="update_hero">
                    
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Titolo Principale</label>
                                <input type="text" name="hero_title" value="<?php echo htmlspecialchars($heroSection['title'] ?? 'Esplora la Calabria'); ?>" class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Sottotitolo</label>
                                <input type="text" name="hero_subtitle" value="<?php echo htmlspecialchars($heroSection['subtitle'] ?? 'Mare cristallino e storia millenaria'); ?>" class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Descrizione</label>
                                <textarea name="hero_description" rows="3" class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"><?php echo htmlspecialchars($heroSection['description'] ?? ''); ?></textarea>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Immagine di Sfondo</label>
                                <div class="flex items-center space-x-2">
                                    <input type="text" name="hero_image" value="<?php echo htmlspecialchars($heroSection['image_path'] ?? ''); ?>" class="flex-1 px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="URL immagine o percorso">
                                    <button type="button" onclick="uploadImage('hero_image')" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                                        <i data-lucide="upload" class="w-4 h-4"></i>
                                    </button>
                                </div>
                                <input type="file" id="hero_image_upload" accept="image/*" style="display: none;" onchange="handleImageUpload(this, 'hero_image')">
                            </div>
                        </div>
                        
                        <div class="space-y-4">
                            <h3 class="font-semibold text-gray-900">Pulsanti Hero</h3>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Testo Pulsante 1</label>
                                <input type="text" name="button1_text" value="<?php echo htmlspecialchars($heroData['button1_text'] ?? 'Scopri la Calabria'); ?>" class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Link Pulsante 1</label>
                                <input type="text" name="button1_link" value="<?php echo htmlspecialchars($heroData['button1_link'] ?? 'categorie.php'); ?>" class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Testo Pulsante 2</label>
                                <input type="text" name="button2_text" value="<?php echo htmlspecialchars($heroData['button2_text'] ?? 'Visualizza Mappa'); ?>" class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Link Pulsante 2</label>
                                <input type="text" name="button2_link" value="<?php echo htmlspecialchars($heroData['button2_link'] ?? 'mappa.php'); ?>" class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-6 flex justify-end">
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg font-semibold transition-colors">
                            💾 Salva Sezione Hero
                        </button>
                    </div>
                </form>
            </div>

            <!-- Sezione Eventi/App Store -->
            <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
                <h2 class="text-xl font-bold text-gray-900 mb-4">📱 Sezione Eventi & App Store</h2>
                <form action="" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="update_events">
                    
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <div class="space-y-4">
                            <h3 class="font-semibold text-gray-900">App Store</h3>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Link App Store</label>
                                <input type="url" name="app_store_link" value="<?php echo htmlspecialchars($settingsArray['app_store_link'] ?? ''); ?>" class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="https://apps.apple.com/...">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Immagine App Store</label>
                                <div class="flex items-center space-x-2">
                                    <input type="text" name="app_store_image" value="<?php echo htmlspecialchars($settingsArray['app_store_image'] ?? ''); ?>" class="flex-1 px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="URL immagine">
                                    <button type="button" onclick="uploadImage('app_store_image')" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                                        <i data-lucide="upload" class="w-4 h-4"></i>
                                    </button>
                                </div>
                                <input type="file" id="app_store_image_upload" accept="image/*" style="display: none;" onchange="handleImageUpload(this, 'app_store_image')">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Link Play Store</label>
                                <input type="url" name="play_store_link" value="<?php echo htmlspecialchars($settingsArray['play_store_link'] ?? ''); ?>" class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="https://play.google.com/...">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Immagine Play Store</label>
                                <div class="flex items-center space-x-2">
                                    <input type="text" name="play_store_image" value="<?php echo htmlspecialchars($settingsArray['play_store_image'] ?? ''); ?>" class="flex-1 px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="URL immagine">
                                    <button type="button" onclick="uploadImage('play_store_image')" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                                        <i data-lucide="upload" class="w-4 h-4"></i>
                                    </button>
                                </div>
                                <input type="file" id="play_store_image_upload" accept="image/*" style="display: none;" onchange="handleImageUpload(this, 'play_store_image')">
                            </div>
                        </div>
                        
                        <div class="space-y-4">
                            <h3 class="font-semibold text-gray-900">Altri Link</h3>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Link "Vai all'App"</label>
                                <input type="url" name="vai_app_link" value="<?php echo htmlspecialchars($settingsArray['vai_app_link'] ?? ''); ?>" class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="https://app.tuodominio.com">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Link "Suggerisci Evento"</label>
                                <input type="url" name="suggerisci_evento_link" value="<?php echo htmlspecialchars($settingsArray['suggerisci_evento_link'] ?? '/suggerisci-evento.php'); ?>" class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="/suggerisci-evento.php">
                            </div>
                            
                            <div class="bg-blue-50 p-4 rounded-lg">
                                <h4 class="font-medium text-blue-900 mb-2">💡 Nota</h4>
                                <p class="text-blue-700 text-sm">I pulsanti verranno mostrati solo se i relativi link sono compilati. Il pulsante "Suggerisci Evento" sarà sempre visibile.</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-6 flex justify-end">
                        <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-lg font-semibold transition-colors">
                            📱 Salva Sezione Eventi
                        </button>
                    </div>
                </form>
            </div>

            <!-- Sezione Categorie -->
            <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
                <h2 class="text-xl font-bold text-gray-900 mb-4">🏷️ Sezione Categorie</h2>
                <form action="" method="POST">
                    <input type="hidden" name="action" value="update_categories">
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Titolo Sezione</label>
                            <input type="text" name="categories_title" value="<?php echo htmlspecialchars($settingsArray['categories_title'] ?? 'Esplora per Categoria'); ?>" class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Descrizione</label>
                            <textarea name="categories_description" rows="2" class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"><?php echo htmlspecialchars($settingsArray['categories_description'] ?? 'Scopri la Calabria attraverso le sue diverse sfaccettature: dalla natura incontaminata alla ricca tradizione culturale.'); ?></textarea>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Testo Pulsante</label>
                            <input type="text" name="categories_button_text" value="<?php echo htmlspecialchars($settingsArray['categories_button_text'] ?? 'Vedi Tutte le Categorie'); ?>" class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Immagine di Sfondo Sezione (opzionale)</label>
                            <div class="flex items-center space-x-2">
                                <input type="text" name="categories_bg_image" value="<?php echo htmlspecialchars($settingsArray['categories_bg_image'] ?? ''); ?>" class="flex-1 px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="URL immagine di sfondo">
                                <button type="button" onclick="uploadImage('categories_bg_image')" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                                    <i data-lucide="upload" class="w-4 h-4"></i>
                                </button>
                            </div>
                            <input type="file" id="categories_bg_image_upload" accept="image/*" style="display: none;" onchange="handleImageUpload(this, 'categories_bg_image')">
                            <p class="text-xs text-gray-500 mt-1">Per personalizzare lo sfondo della sezione categorie</p>
                        </div>
                    </div>
                    
                    <div class="mt-6 flex justify-end">
                        <button type="submit" class="bg-purple-600 hover:bg-purple-700 text-white px-6 py-2 rounded-lg font-semibold transition-colors">
                            🏷️ Salva Sezione Categorie
                        </button>
                    </div>
                </form>
            </div>

            <!-- Sezione Province -->
            <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
                <h2 class="text-xl font-bold text-gray-900 mb-4">🏛️ Sezione Province</h2>
                <form action="" method="POST">
                    <input type="hidden" name="action" value="update_provinces">
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Titolo Sezione</label>
                            <input type="text" name="provinces_title" value="<?php echo htmlspecialchars($settingsArray['provinces_title'] ?? 'Esplora le Province'); ?>" class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Descrizione</label>
                            <textarea name="provinces_description" rows="2" class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"><?php echo htmlspecialchars($settingsArray['provinces_description'] ?? 'Ogni provincia calabrese custodisce tesori unici: dalla costa tirrenica a quella ionica, dai monti della Sila all\'Aspromonte.'); ?></textarea>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Immagine di Sfondo Sezione (opzionale)</label>
                            <div class="flex items-center space-x-2">
                                <input type="text" name="provinces_bg_image" value="<?php echo htmlspecialchars($settingsArray['provinces_bg_image'] ?? ''); ?>" class="flex-1 px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="URL immagine di sfondo">
                                <button type="button" onclick="uploadImage('provinces_bg_image')" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                                    <i data-lucide="upload" class="w-4 h-4"></i>
                                </button>
                            </div>
                            <input type="file" id="provinces_bg_image_upload" accept="image/*" style="display: none;" onchange="handleImageUpload(this, 'provinces_bg_image')">
                            <p class="text-xs text-gray-500 mt-1">Per personalizzare lo sfondo della sezione province</p>
                        </div>
                    </div>
                    
                    <div class="mt-6 flex justify-end">
                        <button type="submit" class="bg-orange-600 hover:bg-orange-700 text-white px-6 py-2 rounded-lg font-semibold transition-colors">
                            🏛️ Salva Sezione Province
                        </button>
                    </div>
                </form>
            </div>

            <!-- Sezione Mappa -->
            <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
                <h2 class="text-xl font-bold text-gray-900 mb-4">🗺️ Sezione Mappa</h2>
                <form action="" method="POST">
                    <input type="hidden" name="action" value="update_map">
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Titolo Sezione</label>
                            <input type="text" name="map_title" value="<?php echo htmlspecialchars($settingsArray['map_title'] ?? 'Esplora la Mappa Interattiva'); ?>" class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Descrizione</label>
                            <textarea name="map_description" rows="2" class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"><?php echo htmlspecialchars($settingsArray['map_description'] ?? 'Naviga attraverso la Calabria con la nostra mappa interattiva. Scopri luoghi, eventi e punti d\'interesse in tempo reale.'); ?></textarea>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Testo Link Mappa Completa</label>
                            <input type="text" name="map_full_link_text" value="<?php echo htmlspecialchars($settingsArray['map_full_link_text'] ?? 'Visualizza mappa completa'); ?>" class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                    </div>
                    
                    <div class="mt-6 flex justify-end">
                        <button type="submit" class="bg-teal-600 hover:bg-teal-700 text-white px-6 py-2 rounded-lg font-semibold transition-colors">
                            🗺️ Salva Sezione Mappa
                        </button>
                    </div>
                </form>
            </div>

            <!-- Sezione CTA -->
            <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
                <h2 class="text-xl font-bold text-gray-900 mb-4">📢 Sezione Call-to-Action</h2>
                <form action="" method="POST">
                    <input type="hidden" name="action" value="update_cta">
                    
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Titolo CTA</label>
                                <input type="text" name="cta_title" value="<?php echo htmlspecialchars($settingsArray['cta_title'] ?? 'Vuoi far Conoscere la Tua Calabria?'); ?>" class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Descrizione</label>
                                <textarea name="cta_description" rows="3" class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"><?php echo htmlspecialchars($settingsArray['cta_description'] ?? 'Unisciti alla nostra community! Condividi i tuoi luoghi del cuore, le tue tradizioni e le tue storie.'); ?></textarea>
                            </div>
                        </div>
                        
                        <div class="space-y-4">
                            <h3 class="font-semibold text-gray-900">Pulsanti CTA</h3>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Testo Pulsante 1</label>
                                <input type="text" name="cta_button1_text" value="<?php echo htmlspecialchars($settingsArray['cta_button1_text'] ?? 'Collabora con Noi'); ?>" class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Link Pulsante 1</label>
                                <input type="text" name="cta_button1_link" value="<?php echo htmlspecialchars($settingsArray['cta_button1_link'] ?? 'collabora.php'); ?>" class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Testo Pulsante 2</label>
                                <input type="text" name="cta_button2_text" value="<?php echo htmlspecialchars($settingsArray['cta_button2_text'] ?? 'Suggerisci un Luogo'); ?>" class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Link Pulsante 2</label>
                                <input type="text" name="cta_button2_link" value="<?php echo htmlspecialchars($settingsArray['cta_button2_link'] ?? 'suggerisci.php'); ?>" class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Immagine di Sfondo CTA (opzionale)</label>
                                <div class="flex items-center space-x-2">
                                    <input type="text" name="cta_bg_image" value="<?php echo htmlspecialchars($settingsArray['cta_bg_image'] ?? ''); ?>" class="flex-1 px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="URL immagine di sfondo">
                                    <button type="button" onclick="uploadImage('cta_bg_image')" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                                        <i data-lucide="upload" class="w-4 h-4"></i>
                                    </button>
                                </div>
                                <input type="file" id="cta_bg_image_upload" accept="image/*" style="display: none;" onchange="handleImageUpload(this, 'cta_bg_image')">
                                <p class="text-xs text-gray-500 mt-1">Sostituirà il gradiente colorato di default</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-6 flex justify-end">
                        <button type="submit" class="bg-yellow-600 hover:bg-yellow-700 text-white px-6 py-2 rounded-lg font-semibold transition-colors">
                            📢 Salva Sezione CTA
                        </button>
                    </div>
                </form>
            </div>

            <!-- Sezione Newsletter -->
            <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
                <h2 class="text-xl font-bold text-gray-900 mb-4">📧 Sezione Newsletter</h2>
                <form action="" method="POST">
                    <input type="hidden" name="action" value="update_newsletter">
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Titolo Newsletter</label>
                            <input type="text" name="newsletter_title" value="<?php echo htmlspecialchars($settingsArray['newsletter_title'] ?? 'Resta Connesso con la Calabria'); ?>" class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Descrizione</label>
                            <textarea name="newsletter_description" rows="2" class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"><?php echo htmlspecialchars($settingsArray['newsletter_description'] ?? 'Iscriviti alla nostra newsletter per ricevere i migliori contenuti e non perdere mai gli eventi più interessanti della regione.'); ?></textarea>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Placeholder Email</label>
                                <input type="text" name="newsletter_placeholder" value="<?php echo htmlspecialchars($settingsArray['newsletter_placeholder'] ?? 'Inserisci la tua email'); ?>" class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Testo Pulsante</label>
                                <input type="text" name="newsletter_button" value="<?php echo htmlspecialchars($settingsArray['newsletter_button'] ?? 'Iscriviti Gratis'); ?>" class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Testo Privacy</label>
                            <input type="text" name="newsletter_privacy" value="<?php echo htmlspecialchars($settingsArray['newsletter_privacy'] ?? 'Rispettiamo la tua privacy. Niente spam, solo contenuti di qualità.'); ?>" class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Action Form (API Endpoint)</label>
                            <input type="text" name="newsletter_form_action" value="<?php echo htmlspecialchars($settingsArray['newsletter_form_action'] ?? 'api/newsletter.php'); ?>" class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Immagine di Sfondo Newsletter (opzionale)</label>
                            <div class="flex items-center space-x-2">
                                <input type="text" name="newsletter_bg_image" value="<?php echo htmlspecialchars($settingsArray['newsletter_bg_image'] ?? ''); ?>" class="flex-1 px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="URL immagine di sfondo">
                                <button type="button" onclick="uploadImage('newsletter_bg_image')" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                                    <i data-lucide="upload" class="w-4 h-4"></i>
                                </button>
                            </div>
                            <input type="file" id="newsletter_bg_image_upload" accept="image/*" style="display: none;" onchange="handleImageUpload(this, 'newsletter_bg_image')">
                            <p class="text-xs text-gray-500 mt-1">Per personalizzare lo sfondo della sezione newsletter</p>
                        </div>
                    </div>
                    
                    <div class="mt-6 flex justify-end">
                        <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-2 rounded-lg font-semibold transition-colors">
                            📧 Salva Sezione Newsletter
                        </button>
                    </div>
                </form>
            </div>

            <!-- Sezione Social Media -->
            <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
                <h2 class="text-xl font-bold text-gray-900 mb-4">📱 Social Media</h2>
                <form action="" method="POST">
                    <input type="hidden" name="action" value="update_social">
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Testo "Seguici"</label>
                            <input type="text" name="social_follow_text" value="<?php echo htmlspecialchars($settingsArray['social_follow_text'] ?? 'Seguici sui social media'); ?>" class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Facebook URL</label>
                                <input type="url" name="social_facebook" value="<?php echo htmlspecialchars($settingsArray['social_facebook'] ?? ''); ?>" class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="https://facebook.com/...">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Instagram URL</label>
                                <input type="url" name="social_instagram" value="<?php echo htmlspecialchars($settingsArray['social_instagram'] ?? ''); ?>" class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="https://instagram.com/...">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Twitter URL</label>
                                <input type="url" name="social_twitter" value="<?php echo htmlspecialchars($settingsArray['social_twitter'] ?? ''); ?>" class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="https://twitter.com/...">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">YouTube URL</label>
                                <input type="url" name="social_youtube" value="<?php echo htmlspecialchars($settingsArray['social_youtube'] ?? ''); ?>" class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="https://youtube.com/...">
                            </div>
                        </div>
                        
                        <div class="bg-blue-50 p-4 rounded-lg">
                            <h4 class="font-medium text-blue-900 mb-2">💡 Nota</h4>
                            <p class="text-blue-700 text-sm">I link social verranno mostrati solo se compilati. Lascia vuoti i campi per nascondere i relativi pulsanti.</p>
                        </div>
                    </div>
                    
                    <div class="mt-6 flex justify-end">
                        <button type="submit" class="bg-pink-600 hover:bg-pink-700 text-white px-6 py-2 rounded-lg font-semibold transition-colors">
                            📱 Salva Social Media
                        </button>
                    </div>
                </form>
            </div>

            <!-- Anteprima -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h2 class="text-xl font-bold text-gray-900 mb-4">👁️ Anteprima</h2>
                <div class="space-y-4">
                    <a href="../index.php" target="_blank" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                        <i data-lucide="external-link" class="w-4 h-4 mr-2"></i>
                        Visualizza Homepage
                    </a>
                    
                    <div class="text-sm text-gray-600">
                        Apri la homepage in una nuova scheda per vedere le modifiche applicate.
                    </div>
                    
                    <div class="bg-green-50 p-4 rounded-lg">
                        <h4 class="font-medium text-green-900 mb-2">✅ Tutte le Sezioni Homepage</h4>
                        <ul class="text-green-700 text-sm space-y-1">
                            <li>• 🎯 Sezione Hero (titolo, <strong>immagini</strong>, pulsanti)</li>
                            <li>• 📱 Eventi & App Store (link app, <strong>immagini app store</strong>, pulsanti)</li>
                            <li>• 🏷️ Sezione Categorie (titoli, descrizioni, <strong>sfondo personalizzabile</strong>)</li>
                            <li>• 🏛️ Sezione Province (titoli, descrizioni, <strong>sfondo personalizzabile</strong>)</li>
                            <li>• 🗺️ Sezione Mappa (titoli, link mappa completa)</li>
                            <li>• 📢 Call-to-Action (titoli, pulsanti, link, <strong>sfondo personalizzabile</strong>)</li>
                            <li>• 📧 Newsletter (form, testi, privacy, <strong>sfondo personalizzabile</strong>)</li>
                            <li>• 📱 Social Media (link Facebook, Instagram, Twitter, YouTube)</li>
                            <li class="font-semibold text-green-800">✨ <strong>Upload Immagini</strong>: Hero, App Store badges, sfondi sezioni</li>
                        </ul>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        lucide.createIcons();
        
        function uploadImage(targetInput) {
            document.getElementById(targetInput + '_upload').click();
        }
        
        function handleImageUpload(input, targetInput) {
            if (input.files && input.files[0]) {
                const formData = new FormData();
                formData.append('image', input.files[0]);
                
                // Show loading
                const button = input.parentElement.querySelector('button');
                const originalText = button.innerHTML;
                button.innerHTML = '<i data-lucide="loader-2" class="w-4 h-4 animate-spin"></i>';
                
                fetch('', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.querySelector(`input[name="${targetInput}"]`).value = data.path;
                        alert('Immagine caricata con successo!');
                    } else {
                        alert('Errore nel caricamento: ' + (data.error || 'Errore sconosciuto'));
                    }
                })
                .catch(error => {
                    alert('Errore nel caricamento: ' + error.message);
                })
                .finally(() => {
                    button.innerHTML = originalText;
                    lucide.createIcons();
                });
            }
        }
    </script>
</body>
</html>