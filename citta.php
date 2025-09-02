<?php
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/ContentManagerSimple.php';

$db = new Database();
$contentManager = new ContentManagerSimple($db);

// Filtri
$provinceFilter = $_GET['provincia'] ?? '';
$searchQuery = $_GET['q'] ?? '';

// Carica tutte le città con filtri
$cities = $db->getCitiesFiltered($provinceFilter, $searchQuery);
$provinces = $db->getProvinces();

// Statistiche
$totalCities = count($db->getCities());
$cityCount = count($cities);
?>
<!DOCTYPE html>
<html <?php echo $contentManager->getLanguageAttributes(); ?>>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($contentManager->getText('cities-page-title', 'Città della Calabria - Passione Calabria')); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($contentManager->getText('cities-meta-description', 'Esplora tutte le città della Calabria: dalle grandi metropoli ai piccoli borghi, scopri la diversità urbana calabrese.')); ?>">

    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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
                <span class="breadcrumb-item text-gray-900 font-medium"><?php echo htmlspecialchars($contentManager->getText('nav-cities', 'Città')); ?></span>
            </nav>
        </div>
    </div>

    <!-- Hero -->
    <div class="bg-gradient-to-r from-blue-600 via-teal-500 to-yellow-500 text-white py-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <div class="text-6xl mb-6">🏘️</div>
            <h1 class="text-4xl md:text-5xl font-bold mb-4">
                <?php echo htmlspecialchars($contentManager->getText('cities-title', 'Città della Calabria')); ?>
            </h1>
            <p class="text-xl text-blue-100 max-w-3xl mx-auto mb-8">
                <?php echo htmlspecialchars($contentManager->getText('cities-description', 'Dalle grandi metropoli costiere ai piccoli borghi montani, scopri la diversità urbana che caratterizza la Calabria. Ogni città racconta una storia unica fatta di tradizioni, cultura e bellezze naturali.')); ?>
            </p>
            <div class="flex justify-center gap-4 flex-wrap">
                <span class="bg-white/20 backdrop-blur-sm text-white px-4 py-2 rounded-full">
                    <?php echo $totalCities; ?> <?php echo htmlspecialchars($contentManager->getText('cities-total', 'città totali')); ?>
                </span>
                <span class="bg-white/20 backdrop-blur-sm text-white px-4 py-2 rounded-full">
                    5 <?php echo htmlspecialchars($contentManager->getText('provinces', 'province')); ?>
                </span>
            </div>
        </div>
    </div>

    <!-- Search & Filters -->
    <div class="bg-white border-b border-gray-200 py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2"><?php echo htmlspecialchars($contentManager->getText('search-cities', 'Cerca città')); ?></label>
                    <input
                        type="text"
                        name="q"
                        value="<?php echo htmlspecialchars($searchQuery); ?>"
                        placeholder="Nome città, descrizione..."
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    >
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2"><?php echo htmlspecialchars($contentManager->getText('filter-province', 'Filtra per provincia')); ?></label>
                    <select name="provincia" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="">Tutte le province</option>
                        <?php foreach ($provinces as $province): ?>
                        <option value="<?php echo $province['id']; ?>" <?php echo $provinceFilter == $province['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($province['name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="md:col-span-3 text-right">
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-8 py-3 rounded-lg font-semibold transition-colors inline-flex items-center">
                        <i data-lucide="search" class="w-5 h-5 mr-2"></i>
                        <?php echo htmlspecialchars($contentManager->getText('search', 'Cerca')); ?>
                    </button>
                    <?php if ($searchQuery || $provinceFilter): ?>
                    <a href="citta.php" class="ml-4 text-gray-600 hover:text-gray-800 font-semibold">
                        <?php echo htmlspecialchars($contentManager->getText('clear-filters', 'Cancella filtri')); ?>
                    </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <!-- Results -->
    <main class="py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            
            <!-- Results Header -->
            <div class="flex justify-between items-center mb-8">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">
                        <?php if ($searchQuery || $provinceFilter): ?>
                            <?php echo htmlspecialchars($contentManager->getText('search-results', 'Risultati ricerca')); ?>
                            <?php if ($searchQuery): ?>
                            <span class="text-blue-600">"<?php echo htmlspecialchars($searchQuery); ?>"</span>
                            <?php endif; ?>
                        <?php else: ?>
                            <?php echo htmlspecialchars($contentManager->getText('all-cities', 'Tutte le Città')); ?>
                        <?php endif; ?>
                    </h2>
                    <p class="text-gray-600"><?php echo $cityCount; ?> <?php echo htmlspecialchars($contentManager->getText('cities-found', 'città trovate')); ?></p>
                </div>
                
                <!-- View Toggle -->
                <div class="flex bg-gray-100 rounded-lg p-1">
                    <button id="grid-view" class="px-4 py-2 rounded-md bg-white shadow text-blue-600 font-semibold">
                        <i data-lucide="grid-3x3" class="w-4 h-4 inline mr-1"></i>
                        <?php echo htmlspecialchars($contentManager->getText('grid-view', 'Griglia')); ?>
                    </button>
                    <button id="list-view" class="px-4 py-2 rounded-md text-gray-600 hover:text-gray-900">
                        <i data-lucide="list" class="w-4 h-4 inline mr-1"></i>
                        <?php echo htmlspecialchars($contentManager->getText('list-view', 'Lista')); ?>
                    </button>
                </div>
            </div>

            <?php if (empty($cities)): ?>
            <!-- Empty State -->
            <div class="text-center py-20">
                <div class="text-6xl mb-6">🔍</div>
                <h3 class="text-2xl font-bold text-gray-900 mb-4">
                    <?php echo htmlspecialchars($contentManager->getText('no-cities-found', 'Nessuna città trovata')); ?>
                </h3>
                <p class="text-gray-600 mb-8 max-w-md mx-auto">
                    <?php echo htmlspecialchars($contentManager->getText('no-cities-description', 'Prova a modificare i filtri di ricerca o esplora tutte le città disponibili.')); ?>
                </p>
                <a href="citta.php" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-full font-semibold transition-colors">
                    <?php echo htmlspecialchars($contentManager->getText('see-all-cities', 'Vedi Tutte le Città')); ?>
                </a>
            </div>
            <?php else: ?>
            
            <!-- Cities Grid -->
            <div id="cities-grid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <?php foreach ($cities as $city): 
                    $articleCount = $db->getArticleCountByCity($city['id']);
                ?>
                <div class="bg-white rounded-2xl shadow-lg overflow-hidden hover:shadow-2xl transition-all duration-300 group">
                    <div class="aspect-[4/3] relative overflow-hidden bg-gradient-to-br from-blue-500 to-teal-600">
                        <!-- City Image Placeholder -->
                        <div class="absolute inset-0 bg-gradient-to-br from-blue-500 to-teal-600"></div>
                        
                        <!-- Province Badge -->
                        <div class="absolute top-4 left-4">
                            <span class="bg-white/20 backdrop-blur-sm text-white px-3 py-1 rounded-full text-sm font-medium">
                                <?php echo htmlspecialchars($city['province_name']); ?>
                            </span>
                        </div>
                        
                        <!-- Article Count Badge -->
                        <div class="absolute top-4 right-4">
                            <span class="bg-yellow-500 text-white px-3 py-1 rounded-full text-sm font-medium">
                                <?php echo $articleCount; ?> <?php echo htmlspecialchars($contentManager->getText('articles', 'articoli')); ?>
                            </span>
                        </div>
                        
                        <!-- Coordinates Info -->
                        <?php if ($city['latitude'] && $city['longitude']): ?>
                        <div class="absolute bottom-4 left-4">
                            <div class="flex items-center bg-black/40 backdrop-blur-sm text-white px-2 py-1 rounded-full text-xs">
                                <i data-lucide="map-pin" class="w-3 h-3 mr-1"></i>
                                <span><?php echo number_format($city['latitude'], 2); ?>, <?php echo number_format($city['longitude'], 2); ?></span>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="p-6">
                        <h3 class="text-2xl font-bold text-gray-900 mb-2 group-hover:text-blue-600 transition-colors">
                            <?php echo htmlspecialchars($city['name']); ?>
                        </h3>
                        <p class="text-gray-600 mb-4">
                            <?php echo htmlspecialchars($contentManager->getText('city-description-' . $city['id'], $city['description'] ?: 'Città di ' . $city['province_name'])); ?>
                        </p>
                        
                        <div class="flex items-center justify-between">
                            <div class="flex items-center text-sm text-gray-500">
                                <i data-lucide="map-pin" class="w-4 h-4 mr-1"></i>
                                <span><?php echo htmlspecialchars($city['province_name']); ?></span>
                            </div>
                            <a href="citta-dettaglio.php?id=<?php echo $city['id']; ?>" class="text-blue-600 hover:text-blue-700 font-semibold flex items-center transition-colors">
                                <?php echo htmlspecialchars($contentManager->getText('explore', 'Esplora')); ?> <i data-lucide="arrow-right" class="w-4 h-4 ml-1"></i>
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Cities List (Hidden by default) -->
            <div id="cities-list" class="space-y-4 hidden">
                <?php foreach ($cities as $city): 
                    $articleCount = $db->getArticleCountByCity($city['id']);
                ?>
                <div class="bg-white rounded-xl shadow-sm p-6 hover:shadow-lg transition-shadow">
                    <div class="flex items-center justify-between">
                        <div class="flex-1">
                            <div class="flex items-center space-x-4 mb-2">
                                <h3 class="text-xl font-bold text-gray-900">
                                    <?php echo htmlspecialchars($city['name']); ?>
                                </h3>
                                <span class="bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-sm font-medium">
                                    <?php echo htmlspecialchars($city['province_name']); ?>
                                </span>
                            </div>
                            <p class="text-gray-600 mb-3">
                                <?php echo htmlspecialchars($contentManager->getText('city-description-' . $city['id'], $city['description'] ?: 'Città di ' . $city['province_name'])); ?>
                            </p>
                            <div class="flex items-center space-x-6 text-sm text-gray-500">
                                <span class="flex items-center">
                                    <i data-lucide="file-text" class="w-4 h-4 mr-1"></i>
                                    <?php echo $articleCount; ?> <?php echo htmlspecialchars($contentManager->getText('articles', 'articoli')); ?>
                                </span>
                                <?php if ($city['latitude'] && $city['longitude']): ?>
                                <span class="flex items-center">
                                    <i data-lucide="navigation" class="w-4 h-4 mr-1"></i>
                                    <?php echo number_format($city['latitude'], 3); ?>, <?php echo number_format($city['longitude'], 3); ?>
                                </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="ml-6">
                            <a href="citta-dettaglio.php?id=<?php echo $city['id']; ?>" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg font-semibold transition-colors inline-flex items-center">
                                <?php echo htmlspecialchars($contentManager->getText('explore', 'Esplora')); ?>
                                <i data-lucide="arrow-right" class="w-4 h-4 ml-2"></i>
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <?php endif; ?>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>


    <script>
        // Inizializza Lucide icons
        lucide.createIcons();

        // View Toggle
        const gridView = document.getElementById('grid-view');
        const listView = document.getElementById('list-view');
        const citiesGrid = document.getElementById('cities-grid');
        const citiesList = document.getElementById('cities-list');

        gridView.addEventListener('click', () => {
            citiesGrid.classList.remove('hidden');
            citiesList.classList.add('hidden');
            
            gridView.classList.add('bg-white', 'shadow', 'text-blue-600');
            gridView.classList.remove('text-gray-600');
            
            listView.classList.remove('bg-white', 'shadow', 'text-blue-600');
            listView.classList.add('text-gray-600');
        });

        listView.addEventListener('click', () => {
            citiesGrid.classList.add('hidden');
            citiesList.classList.remove('hidden');
            
            listView.classList.add('bg-white', 'shadow', 'text-blue-600');
            listView.classList.remove('text-gray-600');
            
            gridView.classList.remove('bg-white', 'shadow', 'text-blue-600');
            gridView.classList.add('text-gray-600');
        });

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
    </script>
</body>
</html>