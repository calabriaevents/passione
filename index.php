<?php
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/ContentManagerSimple.php';

// Inizializza database e gestore contenuti multilingue
$db = new Database();
$contentManager = new ContentManagerSimple();
$currentLang = $contentManager->getCurrentLanguage();
$langInfo = $contentManager->getCurrentLanguageInfo();

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
    $category['articles'] = $db->getArticlesByCategory($category['id'], 6); // Max 6 articoli per slider
    $category['article_count'] = $db->getArticleCountByCategory($category['id']);
}
unset($category); // Unset reference

// Trova sezione hero
$heroSection = null;
foreach ($homeSections as $section) {
    if ($section['section_name'] === 'hero') {
        $heroSection = $section;
        break;
    }
}

// Funzione helper per testi tradotti
function getSimpleText($key, $default) {
    global $contentManager;
    if ($contentManager->getCurrentLanguage() === 'it') {
        return $default; // Lingua italiana, usa default
    }
    // Per altre lingue, prova a ottenere traduzione o fallback
    return $contentManager->getText($key, $default);
}
?>
<!DOCTYPE html>
<html <?php echo $contentManager->getLanguageAttributes(); ?>>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Passione Calabria - La tua guida alla Calabria</title>
    <meta name="description" content="Scopri la bellezza della Calabria: mare cristallino, borghi medievali, gastronomia unica e tradizioni millenarie.">

    <!-- Tailwind CSS via CDN -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>

    <!-- Font Google -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/translation-system.css">

    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />

    <!-- Configurazione Tailwind personalizzata -->
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    },
                    colors: {
                        'calabria-blue': {
                            50: '#eff6ff',
                            500: '#3b82f6',
                            600: '#2563eb',
                            700: '#1d4ed8'
                        },
                        'calabria-gold': {
                            50: '#fffbeb',
                            500: '#f59e0b',
                            600: '#d97706'
                        }
                    }
                }
            }
        }
    </script>
</head>
<body class="min-h-screen bg-gray-50 font-sans">
    <?php include 'includes/header.php'; ?>

    <!-- Hero Section -->
    <section class="relative bg-gradient-to-br from-blue-900 via-blue-700 to-amber-600 text-white py-24 overflow-hidden">
        <!-- Background Image -->
        <div class="absolute inset-0 bg-cover bg-center bg-no-repeat" style="background-image: url('<?php echo $heroSection['image_path'] ?? 'https://images.unsplash.com/photo-1499092346589-b9b6be3e94b2?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80'; ?>')"></div>
        <div class="absolute inset-0 bg-gradient-to-br from-blue-900/80 via-blue-700/70 to-amber-600/60"></div>
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h1 class="text-5xl md:text-6xl lg:text-7xl font-bold mb-6 translatable">
                <?php echo htmlspecialchars(getSimpleText('hero-title', $heroSection['title'] ?? 'Esplora la Calabria')); ?>
            </h1>
            <p class="text-xl md:text-2xl text-yellow-400 mb-8 translatable">
                <?php echo htmlspecialchars(getSimpleText('hero-subtitle', $heroSection['subtitle'] ?? 'Mare cristallino e storia millenaria')); ?>
            </p>
            <p class="text-lg md:text-xl text-gray-200 mb-12 max-w-3xl mx-auto translatable">
                <?php echo htmlspecialchars(getSimpleText('hero-description', $heroSection['description'] ?? 'Immergiti nella bellezza della Calabria, con le sue spiagge da sogno, il centro storico affascinante e i panorami mozzafiato dalla rupe.')); ?>
            </p>

            <div class="flex flex-col sm:flex-row justify-center gap-4 mb-16">
                <a href="categorie.php" class="inline-flex items-center px-8 py-4 bg-blue-600 hover:bg-blue-700 text-white rounded-full font-semibold transition-colors">
                    <i data-lucide="search" class="w-5 h-5 mr-2"></i>
                    <span class="translatable"><?php echo htmlspecialchars(getSimpleText('discover-calabria-btn', 'Scopri la Calabria')); ?></span>
                </a>
                <a href="mappa.php" class="inline-flex items-center px-8 py-4 bg-transparent border-2 border-white hover:bg-white hover:text-gray-800 text-white rounded-full font-semibold transition-colors">
                    <i data-lucide="map-pin" class="w-5 h-5 mr-2"></i>
                    <span class="translatable"><?php echo htmlspecialchars(getSimpleText('view-map-btn', 'Visualizza Mappa')); ?></span>
                </a>
            </div>

            <!-- Search Widget -->
            <div class="max-w-4xl mx-auto bg-white rounded-2xl p-8 shadow-2xl">
                <h2 class="text-2xl font-bold text-gray-800 mb-6 translatable"><?php echo htmlspecialchars(getSimpleText('search-what', 'Cosa stai cercando?')); ?></h2>
                <form action="ricerca.php" method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2 translatable"><?php echo htmlspecialchars(getSimpleText('search-label', 'Luoghi, eventi, tradizioni...')); ?></label>
                        <input
                            type="text"
                            name="q"
                            placeholder="<?php echo htmlspecialchars(getSimpleText('search-placeholder', 'Inserisci quello che vuoi esplorare')); ?>"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-gray-900"
                        >
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2 translatable"><?php echo htmlspecialchars(getSimpleText('province-label', 'Provincia')); ?></label>
                        <select name="provincia" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-gray-900">
                            <option value="" class="translatable"><?php echo htmlspecialchars(getSimpleText('all-provinces', 'Tutte le province')); ?></option>
                            <?php foreach ($provinces as $province): ?>
                            <option value="<?php echo $province['id']; ?>"><?php echo htmlspecialchars($province['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="flex items-end">
                        <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-semibold transition-colors flex items-center justify-center">
                            <i data-lucide="search" class="w-5 h-5 mr-2"></i>
                            <span class="translatable"><?php echo htmlspecialchars(getSimpleText('search-btn', 'Cerca')); ?></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </section>

    <!-- Events Section -->
    <section class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-bold text-gray-900 mb-4 translatable">
                    <?php echo htmlspecialchars(getSimpleText('events-app', $settingsArray['events_title'] ?? 'Eventi e App')); ?>
                </h2>
                <p class="text-xl text-gray-600 max-w-3xl mx-auto translatable">
                    <?php echo htmlspecialchars(getSimpleText('app-description', $settingsArray['events_description'] ?? 'Scarica la nostra app per rimanere sempre aggiornato sugli eventi in Calabria.')); ?>
                </p>
            </div>

            <div class="max-w-4xl mx-auto">
                <!-- App Store Badges -->
                <div class="flex flex-col sm:flex-row justify-center items-center gap-8 mb-12">
                    <?php 
                    $eventSettings = $db->getSettings();
                    $eventData = [];
                    foreach ($eventSettings as $setting) {
                        $eventData[$setting['key']] = $setting['value'];
                    }
                    ?>
                    
                    <?php if (!empty($eventData['app_store_link']) && !empty($eventData['app_store_image'])): ?>
                    <a href="<?php echo htmlspecialchars($eventData['app_store_link']); ?>" target="_blank" class="transition-transform hover:scale-105">
                        <img src="<?php echo htmlspecialchars($eventData['app_store_image']); ?>" alt="<?php echo htmlspecialchars(getSimpleText('download-app-store', 'Scarica su App Store')); ?>" class="h-14 w-auto">
                    </a>
                    <?php endif; ?>
                    
                    <?php if (!empty($eventData['play_store_link']) && !empty($eventData['play_store_image'])): ?>
                    <a href="<?php echo htmlspecialchars($eventData['play_store_link']); ?>" target="_blank" class="transition-transform hover:scale-105">
                        <img src="<?php echo htmlspecialchars($eventData['play_store_image']); ?>" alt="<?php echo htmlspecialchars(getSimpleText('download-google-play', 'Scarica su Google Play')); ?>" class="h-14 w-auto">
                    </a>
                    <?php endif; ?>
                </div>

                <!-- Action Buttons -->
                <div class="flex flex-col sm:flex-row justify-center gap-4">
                    <?php if (!empty($eventData['vai_app_link'])): ?>
                    <a href="<?php echo htmlspecialchars($eventData['vai_app_link']); ?>" target="_blank" class="inline-flex items-center justify-center px-8 py-4 bg-blue-600 hover:bg-blue-700 text-white rounded-full font-semibold transition-colors">
                        <i data-lucide="smartphone" class="w-5 h-5 mr-2"></i>
                        <span class="translatable"><?php echo htmlspecialchars(getSimpleText('go-to-app', 'Vai all\'App')); ?></span>
                    </a>
                    <?php endif; ?>
                    
                    <a href="<?php echo htmlspecialchars($eventData['suggerisci_evento_link'] ?? 'suggerisci-evento.php'); ?>" class="inline-flex items-center justify-center px-8 py-4 bg-amber-500 hover:bg-amber-600 text-white rounded-full font-semibold transition-colors">
                        <i data-lucide="plus-circle" class="w-5 h-5 mr-2"></i>
                        <span class="translatable"><?php echo htmlspecialchars(getSimpleText('suggest-event', 'Suggerisci Evento')); ?></span>
                    </a>
                </div>

                <!-- Info Text -->
                <div class="text-center mt-8">
                    <p class="text-gray-600 translatable">
                        <?php echo htmlspecialchars(getSimpleText('suggest-event-description', 'Hai un evento da condividere? Segnalacelo e lo valuteremo per includerlo nella nostra piattaforma.')); ?>
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Categories Section -->
    <section class="py-20 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-bold text-gray-900 mb-4 translatable">
                    <?php echo htmlspecialchars(getSimpleText('explore-by-category', $settingsArray['categories_title'] ?? 'Esplora per Categoria')); ?>
                </h2>
                <p class="text-xl text-gray-600 max-w-3xl mx-auto translatable">
                    <?php echo htmlspecialchars(getSimpleText('category-description', $settingsArray['categories_description'] ?? 'Scopri la Calabria attraverso le sue diverse sfaccettature: dalla natura incontaminata alla ricca tradizione culturale.')); ?>
                </p>
            </div>

            <!-- Categories Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <?php foreach ($categories as $index => $category): ?>
                <div class="bg-white rounded-2xl shadow-lg overflow-hidden hover:shadow-2xl transition-all duration-300 group">
                    <!-- Category Header -->
                    <div class="bg-gradient-to-br from-blue-500 to-teal-600 relative overflow-hidden p-6">
                        <div class="flex justify-between items-start mb-4">
                            <div class="bg-white/20 backdrop-blur-sm text-white px-3 py-1 rounded-full text-sm font-medium">
                                <span><?php echo $category['article_count']; ?> <span class="translatable"><?php echo htmlspecialchars(getSimpleText('articles-count', 'articoli')); ?></span></span>
                            </div>
                            <div class="text-4xl"><?php echo $category['icon']; ?></div>
                        </div>
                        <h3 class="text-2xl font-bold text-white mb-2 translatable"><?php echo htmlspecialchars(getSimpleText('category-name-'.$category['id'], $category['name'])); ?></h3>
                        <p class="text-blue-100 text-sm translatable"><?php echo htmlspecialchars(getSimpleText('category-desc-'.$category['id'], $category['description'])); ?></p>
                    </div>
                    
                    <!-- Articles Preview -->
                    <?php if (!empty($category['articles'])): ?>
                    <div class="p-6 pb-4">
                        <div class="space-y-3">
                            <?php foreach (array_slice($category['articles'], 0, 2) as $article): ?>
                            <div class="flex items-start space-x-3">
                                <div class="w-12 h-9 bg-gray-200 rounded flex-shrink-0 overflow-hidden">
                                    <?php if ($article['featured_image']): ?>
                                    <img src="<?php echo htmlspecialchars($article['featured_image']); ?>" 
                                         alt="<?php echo htmlspecialchars($article['title']); ?>"
                                         class="w-full h-full object-cover">
                                    <?php else: ?>
                                    <div class="w-full h-full bg-gradient-to-br from-blue-200 to-teal-300 flex items-center justify-center">
                                        <i data-lucide="image" class="w-3 h-3 text-gray-500"></i>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <a href="articolo.php?slug=<?php echo $article['slug']; ?>" class="block">
                                        <h4 class="text-sm font-semibold text-gray-900 hover:text-blue-600 transition-colors line-clamp-2 leading-tight translatable">
                                            <?php echo htmlspecialchars(getSimpleText('article-title-'.$article['id'], $article['title'])); ?>
                                        </h4>
                                        <p class="text-xs text-gray-500 mt-1">
                                            <?php echo formatDate($article['created_at']); ?>
                                        </p>
                                    </a>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="p-6 text-center">
                        <i data-lucide="file-text" class="w-8 h-8 text-gray-300 mx-auto mb-2"></i>
                        <p class="text-sm text-gray-500 mb-4 translatable">Nessun articolo disponibile</p>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Category Footer -->
                    <div class="px-6 pb-6">
                        <div class="border-t pt-4">
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-500 flex items-center">
                                    <i data-lucide="bookmark" class="w-4 h-4 mr-1"></i>
                                    <?php echo $category['article_count']; ?> contenuti
                                </span>
                                <a href="categoria.php?id=<?php echo $category['id']; ?>" class="inline-flex items-center text-blue-600 hover:text-blue-700 font-semibold text-sm transition-colors">
                                    <span><?php echo htmlspecialchars(getSimpleText('explore', 'Esplora')); ?></span> <i data-lucide="arrow-right" class="w-4 h-4 ml-1"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="text-center mt-16">
                <a href="categorie.php" class="inline-flex items-center px-8 py-4 bg-blue-600 hover:bg-blue-700 text-white rounded-full font-semibold transition-colors">
                    <span><?php echo htmlspecialchars($settingsArray['categories_button_text'] ?? 'Vedi Tutte le Categorie'); ?></span> <i data-lucide="arrow-right" class="w-5 h-5 ml-2"></i>
                </a>
            </div>
        </div>
    </section>

    <!-- Provinces Section -->
    <section class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-bold text-gray-900 mb-4">
                    <?php echo htmlspecialchars(getSimpleText('provinces-title', $settingsArray['provinces_title'] ?? 'Esplora le Province')); ?>
                </h2>
                <p class="text-xl text-gray-600 max-w-3xl mx-auto">
                    <?php echo htmlspecialchars(getSimpleText('provinces-description', $settingsArray['provinces_description'] ?? 'Ogni provincia calabrese custodisce tesori unici: dalla costa tirrenica a quella ionica, dai monti della Sila all\'Aspromonte.')); ?>
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <?php foreach ($provinces as $index => $province):
                    $colors = ['blue', 'orange', 'green', 'purple', 'orange'];
                    $color = $colors[$index % count($colors)];
                    $articleCount = $db->getArticleCountByProvince($province['id']);
                    $cities = $db->getCitiesByProvince($province['id']);
                ?>
                <div class="bg-white rounded-2xl shadow-lg overflow-hidden hover:shadow-2xl transition-all duration-300 group">
                    <div class="aspect-[4/3] relative overflow-hidden">
                        <?php if (!empty($province['image_path'])): ?>
                        <img src="<?php echo htmlspecialchars($province['image_path']); ?>" 
                             alt="<?php echo htmlspecialchars($province['name']); ?>" 
                             class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                        <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-black/20 to-black/10"></div>
                        <?php else: ?>
                        <div class="w-full h-full bg-gradient-to-br from-<?php echo $color; ?>-500 to-<?php echo $color; ?>-600"></div>
                        <?php endif; ?>
                        
                        <div class="absolute top-4 left-4">
                            <span class="bg-<?php echo $color; ?>-600 text-white px-3 py-1 rounded-full text-sm font-medium shadow-lg">
                                <?php echo htmlspecialchars($province['name']); ?>
                            </span>
                        </div>
                        
                        <div class="absolute top-4 right-4">
                            <span class="bg-white/20 backdrop-blur-sm text-white px-3 py-1 rounded-full text-sm shadow-lg">
                                <?php echo $articleCount; ?> <span class="translatable">contenuti</span>
                            </span>
                        </div>
                    </div>
                    <div class="p-6">
                        <h3 class="text-2xl font-bold text-gray-900 mb-2"><?php echo htmlspecialchars(getSimpleText('province-name-'.$province['id'], $province['name'])); ?></h3>
                        <p class="text-gray-600 mb-4"><?php echo htmlspecialchars(getSimpleText('province-desc-'.$province['id'], $province['description'])); ?></p>

                        <div class="mb-4">
                            <h4 class="font-semibold text-gray-900 mb-2"><?php echo htmlspecialchars(getSimpleText('main-locations', 'LOCALITÀ PRINCIPALI:')); ?></h4>
                            <div class="flex flex-wrap gap-2">
                                <?php foreach (array_slice($cities, 0, 3) as $city): ?>
                                <span class="bg-gray-100 text-gray-700 px-3 py-1 rounded-full text-sm"><?php echo htmlspecialchars($city['name']); ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-500 flex items-center">
                                <i data-lucide="map-pin" class="w-4 h-4 mr-1"></i>
                                <?php echo $articleCount; ?> <span class="translatable">contenuti</span>
                            </span>
                            <a href="provincia.php?id=<?php echo $province['id']; ?>" class="text-<?php echo $color; ?>-600 hover:text-<?php echo $color; ?>-700 font-semibold flex items-center">
                                <span class="translatable">Esplora</span> <i data-lucide="arrow-right" class="w-4 h-4 ml-1"></i>
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="py-20 bg-gradient-to-r from-blue-600 via-teal-500 to-yellow-500 text-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h2 class="text-4xl font-bold mb-6 translatable"><?php echo htmlspecialchars($settingsArray['cta_title'] ?? 'Vuoi far Conoscere la Tua Calabria?'); ?></h2>
            <p class="text-xl mb-8 max-w-3xl mx-auto translatable">
                <?php echo htmlspecialchars($settingsArray['cta_description'] ?? 'Unisciti alla nostra community! Condividi i tuoi luoghi del cuore, le tue tradizioni e le tue storie.'); ?>
            </p>
            <div class="flex flex-col sm:flex-row justify-center gap-4">
                <a href="<?php echo htmlspecialchars($settingsArray['cta_button1_link'] ?? 'collabora.php'); ?>" class="inline-flex items-center px-8 py-4 bg-white text-blue-600 rounded-full font-semibold hover:bg-gray-100 transition-colors">
                    <i data-lucide="users" class="w-5 h-5 mr-2"></i>
                    <span class="translatable"><?php echo htmlspecialchars($settingsArray['cta_button1_text'] ?? 'Collabora con Noi'); ?></span>
                </a>
                <a href="<?php echo htmlspecialchars($settingsArray['cta_button2_link'] ?? 'suggerisci.php'); ?>" class="inline-flex items-center px-8 py-4 bg-transparent border-2 border-white text-white rounded-full font-semibold hover:bg-white hover:text-blue-600 transition-colors">
                    <i data-lucide="map-pin" class="w-5 h-5 mr-2"></i>
                    <span class="translatable"><?php echo htmlspecialchars($settingsArray['cta_button2_text'] ?? 'Suggerisci un Luogo'); ?></span>
                </a>
            </div>
        </div>
    </section>

    <!-- Newsletter Section -->
    <section class="py-20 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h2 class="text-4xl font-bold text-gray-900 mb-4 translatable">
                <?php echo htmlspecialchars($settingsArray['newsletter_title'] ?? 'Resta Connesso con la Calabria'); ?>
            </h2>
            <p class="text-xl text-gray-600 mb-8 max-w-3xl mx-auto translatable">
                <?php echo htmlspecialchars(getSimpleText('newsletter-description', $settingsArray['newsletter_description'] ?? 'Iscriviti alla nostra newsletter per ricevere i migliori contenuti e non perdere mai gli eventi più interessanti della regione.')); ?>
            </p>

            <form action="<?php echo htmlspecialchars($settingsArray['newsletter_form_action'] ?? 'api/newsletter.php'); ?>" method="POST" class="max-w-md mx-auto flex gap-4">
                <input
                    type="email"
                    name="email"
                    placeholder="<?php echo htmlspecialchars($settingsArray['newsletter_placeholder'] ?? 'Inserisci la tua email'); ?>"
                    required
                    class="flex-1 px-6 py-4 border border-gray-300 rounded-full focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                >
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-8 py-4 rounded-full font-semibold transition-colors">
                    <span class="translatable"><?php echo htmlspecialchars(getSimpleText('newsletter-button', $settingsArray['newsletter_button'] ?? 'Iscriviti Gratis')); ?></span>
                </button>
            </form>
            <p class="text-sm text-gray-500 mt-4 translatable">
                <?php echo htmlspecialchars($settingsArray['newsletter_privacy'] ?? 'Rispettiamo la tua privacy. Niente spam, solo contenuti di qualità.'); ?>
            </p>

            <!-- Social Media -->
            <div class="mt-12">
                <p class="text-gray-600 mb-6 translatable"><?php echo htmlspecialchars($settingsArray['social_follow_text'] ?? 'Seguici sui social media'); ?></p>
                <div class="flex justify-center space-x-6">
                    <?php if (!empty($settingsArray['social_facebook'])): ?>
                    <a href="<?php echo htmlspecialchars($settingsArray['social_facebook']); ?>" target="_blank" class="w-12 h-12 bg-blue-600 text-white rounded-full flex items-center justify-center hover:bg-blue-700 transition-colors">
                        <i data-lucide="facebook" class="w-6 h-6"></i>
                    </a>
                    <?php endif; ?>
                    <?php if (!empty($settingsArray['social_instagram'])): ?>
                    <a href="<?php echo htmlspecialchars($settingsArray['social_instagram']); ?>" target="_blank" class="w-12 h-12 bg-pink-500 text-white rounded-full flex items-center justify-center hover:bg-pink-600 transition-colors">
                        <i data-lucide="instagram" class="w-6 h-6"></i>
                    </a>
                    <?php endif; ?>
                    <?php if (!empty($settingsArray['social_twitter'])): ?>
                    <a href="<?php echo htmlspecialchars($settingsArray['social_twitter']); ?>" target="_blank" class="w-12 h-12 bg-blue-400 text-white rounded-full flex items-center justify-center hover:bg-blue-500 transition-colors">
                        <i data-lucide="twitter" class="w-6 h-6"></i>
                    </a>
                    <?php endif; ?>
                    <?php if (!empty($settingsArray['social_youtube'])): ?>
                    <a href="<?php echo htmlspecialchars($settingsArray['social_youtube']); ?>" target="_blank" class="w-12 h-12 bg-red-600 text-white rounded-full flex items-center justify-center hover:bg-red-700 transition-colors">
                        <i data-lucide="youtube" class="w-6 h-6"></i>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <?php include 'includes/footer.php'; ?>

    <!-- JavaScript -->
    <script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>
    <script src="assets/js/main.js"></script>
    
    <!-- Sistema di Traduzione Unificato -->
    <script src="assets/js/unified-translation-system.js"></script>
    <script src="assets/js/preventive-language-detection.js"></script>
    <script>
        console.log('[Translation] Sistema unificato di traduzione attivato');
    </script>
    
    <script>
        // Inizializza Lucide icons
        lucide.createIcons();
        
        // Mobile menu toggle
        document.getElementById('mobile-menu-btn')?.addEventListener('click', function() {
            const menu = document.getElementById('mobile-menu');
            menu.classList.toggle('hidden');
        });
    </script>
</body>
</html>