<?php
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/ContentManagerSimple.php';

$db = new Database();
$contentManager = new ContentManagerSimple();
$currentLang = $contentManager->getCurrentLanguage();

// Verifica se l'ID città è fornito
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: citta.php");
    exit;
}

$cityId = (int)$_GET['id'];

// Carica dati città
$city = $db->getCityById($cityId);
if (!$city) {
    header("Location: citta.php");
    exit;
}

// Carica articoli della città
$articles = $db->getArticlesByCity($cityId);
$articleCount = $db->getArticleCountByCity($cityId);

// Carica altre città della stessa provincia
$relatedCities = array_filter($db->getCitiesByProvince($city['province_id']), function($c) use ($cityId) {
    return $c['id'] != $cityId;
});
?>
<!DOCTYPE html>
<html <?php echo $contentManager->getLanguageAttributes(); ?>>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($city['name']); ?> - <?php echo htmlspecialchars($contentManager->getText('cities-of-calabria', 'Città della Calabria')); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($city['description'] ?: $contentManager->getText('discover-city-desc', 'Scopri') . ' ' . $city['name'] . ', ' . $contentManager->getText('city-of-province', 'città della provincia di') . ' ' . $city['province_name'] . ' ' . $contentManager->getText('in-calabria', 'in Calabria.')); ?>">

    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />
    <link rel="stylesheet" href="assets/css/style.css">

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
<body class="bg-gray-50">
    <?php include 'includes/header.php'; ?>

    <!-- Breadcrumb -->
    <div class="bg-white border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
            <nav class="breadcrumb">
                <span class="breadcrumb-item"><a href="index.php" class="text-blue-600 hover:text-blue-700"><?php echo htmlspecialchars($contentManager->getText('nav-home', 'Home')); ?></a></span>
                <span class="breadcrumb-item"><a href="citta.php" class="text-blue-600 hover:text-blue-700"><?php echo htmlspecialchars($contentManager->getText('nav-cities', 'Città')); ?></a></span>
                <span class="breadcrumb-item"><a href="provincia.php?id=<?php echo $city['province_id']; ?>" class="text-blue-600 hover:text-blue-700"><?php echo htmlspecialchars($city['province_name']); ?></a></span>
                <span class="breadcrumb-item text-gray-900 font-medium"><?php echo htmlspecialchars($contentManager->getText('city-name-'.$city['id'], $city['name'])); ?></span>
            </nav>
        </div>
    </div>

    <!-- City Hero -->
    <div class="bg-gradient-to-r from-blue-600 via-teal-500 to-yellow-500 text-white py-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <div class="text-6xl mb-6">🏙️</div>
            <h1 class="text-4xl md:text-5xl font-bold mb-4">
                <?php echo htmlspecialchars($city['name']); ?>
            </h1>
            <p class="text-xl text-blue-100 max-w-3xl mx-auto mb-8">
                <?php echo htmlspecialchars($city['description'] ?: 'Città della provincia di ' . $city['province_name']); ?>
            </p>
            <div class="flex justify-center gap-4 flex-wrap">
                <span class="bg-white/20 backdrop-blur-sm text-white px-4 py-2 rounded-full">
                    <i data-lucide="map-pin" class="w-4 h-4 inline mr-1"></i>
                    <?php echo htmlspecialchars($city['province_name']); ?>
                </span>
                <span class="bg-white/20 backdrop-blur-sm text-white px-4 py-2 rounded-full">
                    <i data-lucide="file-text" class="w-4 h-4 inline mr-1"></i>
                    <?php echo $articleCount; ?> <?php echo htmlspecialchars($contentManager->getText($articleCount == 1 ? 'article-singular' : 'articles-plural', $articleCount == 1 ? 'articolo' : 'articoli')); ?>
                </span>
                <?php if ($city['latitude'] && $city['longitude']): ?>
                <span class="bg-white/20 backdrop-blur-sm text-white px-4 py-2 rounded-full">
                    <i data-lucide="navigation" class="w-4 h-4 inline mr-1"></i>
                    <?php echo number_format($city['latitude'], 4); ?>, <?php echo number_format($city['longitude'], 4); ?>
                </span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <main class="py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            
            <?php if ($city['latitude'] && $city['longitude']): ?>
            <!-- City Map -->
            <div class="mb-16">
                <h2 class="text-3xl font-bold text-gray-900 mb-8 text-center">
                    <span class="translatable" data-translate="city-location">Posizione di</span> <?php echo htmlspecialchars($city['name']); ?>
                </h2>
                <div class="bg-white rounded-2xl shadow-lg p-8">
                    <div class="mb-6">
                        <p class="text-gray-600 text-center mb-4 translatable" data-translate="city-map-description">
                            Esplora la posizione di <?php echo htmlspecialchars($city['name']); ?> sulla mappa interattiva.
                        </p>
                    </div>
                    <div id="city-map" class="w-full h-96 bg-gray-100 rounded-lg overflow-hidden">
                        <!-- Mappa Leaflet città specifica -->
                    </div>
                    <div class="mt-4 text-center">
                        <p class="text-sm text-gray-500">
                            <span class="translatable" data-translate="coordinates">Coordinate</span>: <?php echo $city['latitude']; ?>, <?php echo $city['longitude']; ?>
                        </p>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Articles Section -->
            <?php if (!empty($articles)): ?>
            <div class="mb-16">
                <h2 class="text-3xl font-bold text-gray-900 mb-8 text-center">
                    <span class="translatable" data-translate="articles-of">Articoli di</span> <?php echo htmlspecialchars($city['name']); ?>
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    <?php foreach ($articles as $article): ?>
                    <article class="bg-white rounded-xl shadow-lg overflow-hidden hover:shadow-2xl transition-all duration-300 group">
                        <!-- Article Image -->
                        <div class="aspect-[4/3] bg-gradient-to-br from-blue-500 to-teal-600 relative overflow-hidden">
                            <?php if ($article['featured_image']): ?>
                            <img src="<?php echo htmlspecialchars($article['featured_image']); ?>" 
                                 alt="<?php echo htmlspecialchars($article['title']); ?>"
                                 class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                            <?php else: ?>
                            <div class="absolute inset-0 bg-gradient-to-br from-blue-500 to-teal-600"></div>
                            <?php endif; ?>
                            
                            <div class="absolute inset-0 bg-black/40"></div>
                            
                            <!-- Article Meta -->
                            <div class="absolute top-4 left-4 right-4">
                                <div class="flex justify-between items-start">
                                    <span class="bg-white/20 backdrop-blur-sm text-white px-3 py-1 rounded-full text-sm">
                                        <?php echo htmlspecialchars($article['category_name'] ?? 'Articolo'); ?>
                                    </span>
                                    <span class="bg-yellow-500 text-white px-3 py-1 rounded-full text-sm font-medium">
                                        <?php echo $article['views']; ?> <span class="translatable" data-translate="views">visite</span>
                                    </span>
                                </div>
                            </div>
                            
                            <div class="absolute bottom-4 left-4 right-4 text-white">
                                <h3 class="text-xl font-bold mb-2 line-clamp-2">
                                    <?php echo htmlspecialchars($article['title']); ?>
                                </h3>
                            </div>
                        </div>

                        <!-- Article Content -->
                        <div class="p-6">
                            <p class="text-gray-600 mb-4 line-clamp-3">
                                <?php echo htmlspecialchars($article['excerpt']); ?>
                            </p>
                            
                            <div class="flex items-center justify-between mb-4">
                                <div class="flex items-center space-x-4 text-sm text-gray-500">
                                    <span class="flex items-center">
                                        <i data-lucide="calendar" class="w-4 h-4 mr-1"></i>
                                        <?php echo formatDate($article['created_at']); ?>
                                    </span>
                                    <span class="flex items-center">
                                        <i data-lucide="user" class="w-4 h-4 mr-1"></i>
                                        <?php echo htmlspecialchars($article['author']); ?>
                                    </span>
                                </div>
                            </div>
                            
                            <a href="articolo.php?slug=<?php echo $article['slug']; ?>" 
                               class="inline-flex items-center text-blue-600 hover:text-blue-700 font-semibold transition-colors">
                                <span class="translatable" data-translate="read-more">Leggi di più</span> <i data-lucide="arrow-right" class="w-4 h-4 ml-1"></i>
                            </a>
                        </div>
                    </article>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php else: ?>
            <!-- Empty Articles State -->
            <div class="text-center py-20">
                <div class="text-6xl mb-6">📝</div>
                <h2 class="text-2xl font-bold text-gray-900 mb-4 translatable" data-translate="no-articles">
                    Nessun articolo disponibile
                </h2>
                <p class="text-gray-600 mb-8 max-w-md mx-auto translatable" data-translate="no-articles-city-desc">
                    Non ci sono ancora articoli per <?php echo htmlspecialchars($city['name']); ?>, ma ne stiamo preparando di fantastici!
                </p>
                <div class="flex flex-col sm:flex-row justify-center gap-4">
                    <a href="citta.php" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-full font-semibold transition-colors translatable" data-translate="explore-cities">
                        Esplora Altre Città
                    </a>
                    <a href="suggerisci.php" class="border-2 border-blue-600 text-blue-600 hover:bg-blue-600 hover:text-white px-6 py-3 rounded-full font-semibold transition-colors translatable" data-translate="suggest-content">
                        Suggerisci Contenuti
                    </a>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Related Cities -->
            <?php if (!empty($relatedCities)): ?>
            <div class="mt-16">
                <h3 class="text-2xl font-bold text-gray-900 mb-8 text-center">
                    <span class="translatable" data-translate="other-cities-province">Altre Città di</span> <?php echo htmlspecialchars($city['province_name']); ?>
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <?php foreach (array_slice($relatedCities, 0, 4) as $relatedCity): 
                        $relatedArticleCount = $db->getArticleCountByCity($relatedCity['id']);
                    ?>
                    <a href="citta-dettaglio.php?id=<?php echo $relatedCity['id']; ?>" 
                       class="block bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition-all group">
                        <div class="text-4xl mb-3">🏙️</div>
                        <h4 class="text-lg font-bold text-gray-900 mb-2 group-hover:text-blue-600 transition-colors">
                            <?php echo htmlspecialchars($relatedCity['name']); ?>
                        </h4>
                        <p class="text-gray-600 text-sm mb-4">
                            <?php echo htmlspecialchars(substr($relatedCity['description'] ?: 'Città di ' . $city['province_name'], 0, 80)); ?>...
                        </p>
                        <div class="flex items-center justify-between">
                            <span class="text-xs text-gray-500">
                                <?php echo $relatedArticleCount; ?> <span class="translatable" data-translate="articles">articoli</span>
                            </span>
                            <div class="flex items-center text-blue-600 font-semibold">
                                <span class="translatable" data-translate="explore">Esplora</span> <i data-lucide="arrow-right" class="w-4 h-4 ml-1"></i>
                            </div>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>

    <!-- Leaflet JavaScript -->
    <script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>
    

    <script src="assets/js/main.js"></script>
    <script>
        // Inizializza Lucide icons
        lucide.createIcons();

        // Animazioni scroll
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animate-fade-in-up');
                }
            });
        }, observerOptions);

        document.querySelectorAll('.bg-white').forEach(card => {
            observer.observe(card);
        });
        
        // Inizializza mappa città se presente
        document.addEventListener('DOMContentLoaded', function() {
            if (document.getElementById('city-map')) {
                initCityMap();
            }
        });
        
        function initCityMap() {
            <?php if ($city['latitude'] && $city['longitude']): ?>
            const cityData = {
                name: <?php echo json_encode($city['name']); ?>,
                description: <?php echo json_encode($city['description'] ?: 'Città di ' . $city['province_name']); ?>,
                latitude: <?php echo $city['latitude']; ?>,
                longitude: <?php echo $city['longitude']; ?>,
                province: <?php echo json_encode($city['province_name']); ?>
            };
            
            const map = L.map('city-map').setView([cityData.latitude, cityData.longitude], 13);
            
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap contributors'
            }).addTo(map);
            
            // Aggiungi marker per la città
            const marker = L.marker([cityData.latitude, cityData.longitude]).addTo(map);
            marker.bindPopup(`
                <div class="p-2">
                    <h3 class="font-bold text-lg">${cityData.name}</h3>
                    <p class="text-gray-600 mb-2">${cityData.description}</p>
                    <p class="text-sm text-gray-500">Provincia di ${cityData.province}</p>
                </div>
            `).openPopup();
            <?php else: ?>
            document.getElementById('city-map').innerHTML = `
                <div class="w-full h-full flex items-center justify-center text-gray-500">
                    <div class="text-center">
                        <i data-lucide="map-off" class="w-16 h-16 mx-auto mb-4"></i>
                        <p>Mappa non disponibile per questa città</p>
                    </div>
                </div>
            `;
            lucide.createIcons();
            <?php endif; ?>
        }
    </script>
</body>
</html>