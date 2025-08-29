<?php
require_once 'includes/config.php';
require_once 'includes/database.php';

// Inizializza database
$db = new Database();

// Carica dati per la homepage
$categories = $db->getCategories();
$provinces = $db->getProvinces();
$featuredArticles = $db->getFeaturedArticles();
$homeSections = $db->getHomeSections();

// Carica impostazioni homepage
$settings = $db->getSettings();
$settingsArray = [];
foreach ($settings as $setting) {
    $settingsArray[$setting['key']] = $setting['value'];
}

// Carica articoli per ogni categoria (per i slider)
foreach ($categories as &$category) {
    $category['articles'] = $db->getArticlesByCategory($category['id'], 6);
    $category['article_count'] = $db->getArticleCountByCategory($category['id']);
}
unset($category);

// Trova sezione hero
$heroSection = null;
foreach ($homeSections as $section) {
    if ($section['section_name'] === 'hero') {
        $heroSection = $section;
        break;
    }
}

// Funzione temporanea per traduzioni (fallback)
function t($key, $fallback = null) {
    return $fallback ?? $key;
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Passione Calabria - Sistema di Traduzione Preventiva</title>
    <meta name="description" content="Scopri la bellezza della Calabria con il nuovo sistema di traduzione automatica.">
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    }
                }
            }
        }
    </script>
</head>
<body class="min-h-screen bg-gray-50 font-sans">
    <!-- Header Semplificato -->
    <header class="bg-gradient-to-r from-blue-600 via-teal-500 to-yellow-500 text-white">
        <nav class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
            <div class="flex justify-between items-center">
                <div class="flex items-center space-x-3">
                    <div class="w-12 h-12 bg-gradient-to-r from-blue-500 to-yellow-500 rounded-full flex items-center justify-center">
                        <span class="text-white font-bold text-lg">PC</span>
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold">Passione <span class="text-yellow-300">Calabria</span></h1>
                        <p class="text-blue-100 text-sm">Sistema Traduzione Preventiva</p>
                    </div>
                </div>
                
                <div class="hidden lg:flex items-center space-x-8">
                    <a href="index-temp.php" class="hover:text-yellow-300 transition-colors font-medium">Home</a>
                    <a href="admin/" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-full transition-colors font-medium">Admin</a>
                </div>
            </div>
        </nav>
    </header>

    <!-- Hero Section -->
    <section class="relative bg-gradient-to-br from-blue-900 via-blue-700 to-amber-600 text-white py-24">
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h1 class="text-5xl md:text-6xl lg:text-7xl font-bold mb-6">
                <?php echo htmlspecialchars(t('hero-title', $heroSection['title'] ?? 'Scopri la Calabria')); ?>
            </h1>
            <p class="text-xl md:text-2xl text-yellow-400 mb-8">
                Sistema di Traduzione Preventiva Attivo
            </p>
            <p class="text-lg md:text-xl text-gray-200 mb-12 max-w-3xl mx-auto">
                Il nuovo sistema traduce automaticamente i contenuti quando vengono creati, garantendo velocità e accuratezza.
            </p>

            <div class="flex flex-col sm:flex-row justify-center gap-4 mb-16">
                <a href="admin/" class="inline-flex items-center px-8 py-4 bg-blue-600 hover:bg-blue-700 text-white rounded-full font-semibold transition-colors">
                    <i data-lucide="settings" class="w-5 h-5 mr-2"></i>
                    <span>Configura API Traduzione</span>
                </a>
                <a href="categorie.php" class="inline-flex items-center px-8 py-4 bg-transparent border-2 border-white hover:bg-white hover:text-gray-800 text-white rounded-full font-semibold transition-colors">
                    <i data-lucide="search" class="w-5 h-5 mr-2"></i>
                    <span>Esplora Contenuti</span>
                </a>
            </div>
        </div>
    </section>

    <!-- Statistiche Sistema -->
    <section class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-bold text-gray-900 mb-4">Sistema di Traduzione Preventiva</h2>
                <p class="text-xl text-gray-600 max-w-3xl mx-auto">
                    Traduzioni automatiche immediate senza rallentamenti per l'utente
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="bg-gradient-to-r from-blue-500 to-blue-600 text-white p-8 rounded-2xl text-center">
                    <div class="text-4xl mb-4">🚀</div>
                    <h3 class="text-2xl font-bold mb-2">Velocità</h3>
                    <p>Contenuti pre-tradotti caricati istantaneamente</p>
                </div>
                
                <div class="bg-gradient-to-r from-green-500 to-green-600 text-white p-8 rounded-2xl text-center">
                    <div class="text-4xl mb-4">🎯</div>
                    <h3 class="text-2xl font-bold mb-2">Precisione</h3>
                    <p>Traduzioni di qualità con fallback intelligente</p>
                </div>
                
                <div class="bg-gradient-to-r from-purple-500 to-purple-600 text-white p-8 rounded-2xl text-center">
                    <div class="text-4xl mb-4">⚙️</div>
                    <h3 class="text-2xl font-bold mb-2">Automatico</h3>
                    <p>Traduzione al momento del salvataggio contenuto</p>
                </div>
            </div>
        </div>
    </section>

    <!-- API Configurate -->
    <section class="py-20 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h2 class="text-4xl font-bold text-gray-900 mb-8">API di Traduzione Supportate</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-white p-6 rounded-xl shadow-md">
                    <h3 class="text-xl font-bold text-blue-600 mb-2">Google Translate</h3>
                    <p class="text-gray-600">API leader del mercato con supporto per oltre 100 lingue</p>
                </div>
                
                <div class="bg-white p-6 rounded-xl shadow-md">
                    <h3 class="text-xl font-bold text-green-600 mb-2">DeepL</h3>
                    <p class="text-gray-600">Traduzioni di alta qualità con tecnologia neurale avanzata</p>
                </div>
                
                <div class="bg-white p-6 rounded-xl shadow-md">
                    <h3 class="text-xl font-bold text-red-600 mb-2">Yandex Translate</h3>
                    <p class="text-gray-600">Ottimo supporto per lingue europee e asiatiche</p>
                </div>
            </div>
            
            <div class="mt-12">
                <a href="admin/" class="inline-flex items-center px-8 py-4 bg-blue-600 hover:bg-blue-700 text-white rounded-full font-semibold transition-colors">
                    <i data-lucide="key" class="w-5 h-5 mr-2"></i>
                    <span>Configura API Keys</span>
                </a>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-900 text-white py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <div class="flex items-center justify-center space-x-3 mb-4">
                <div class="w-12 h-12 bg-gradient-to-r from-blue-500 to-yellow-500 rounded-full flex items-center justify-center">
                    <span class="text-white font-bold text-lg">PC</span>
                </div>
                <div>
                    <h1 class="text-2xl font-bold">Passione <span class="text-yellow-300">Calabria</span></h1>
                </div>
            </div>
            <p class="text-gray-400">Sistema di Traduzione Preventiva - Veloce, Accurato, Automatico</p>
            <p class="text-gray-500 text-sm mt-2">© 2024 Passione Calabria. Sistema implementato con successo.</p>
        </div>
    </footer>

    <!-- JavaScript -->
    <script src="assets/js/preventive-language-detection.js"></script>
    <script>
        // Inizializza Lucide icons
        lucide.createIcons();
        
        console.log('Sistema di traduzione preventiva caricato');
    </script>
</body>
</html>