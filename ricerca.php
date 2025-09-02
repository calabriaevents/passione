<?php
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/ContentManagerSimple.php';

$db = new Database();
$contentManager = new ContentManagerSimple($db);

// Ottieni parametri di ricerca
$query = sanitize($_GET['q'] ?? '');
$provinceId = (int)($_GET['provincia'] ?? 0);
$categoryId = (int)($_GET['categoria'] ?? 0);
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 12;
$offset = ($page - 1) * $limit;

// Esegui ricerca
$results = [];
$totalResults = 0;

if ($query || $provinceId || $categoryId) {
    if ($query) {
        $results = $db->searchArticles($query, $provinceId ?: null);
    } elseif ($categoryId) {
        $results = $db->getArticlesByCategory($categoryId);
    } elseif ($provinceId) {
        $results = $db->getArticlesByProvince($provinceId);
    }

    $totalResults = count($results);
    $results = array_slice($results, $offset, $limit);
}

// Carica dati per i filtri
$categories = $db->getCategories();
$provinces = $db->getProvinces();

// Calcola paginazione
$totalPages = ceil($totalResults / $limit);
?>
<!DOCTYPE html>
<html <?php echo $contentManager->getLanguageAttributes(); ?>>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($contentManager->getText('search-page-title', ($query ? "Risultati per \"$query\"" : "Ricerca") . " - Passione Calabria")); ?></title>
    <meta name="description" content="Cerca contenuti su Passione Calabria: luoghi, tradizioni, eventi e tutto sulla Calabria.">

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
<body class="min-h-screen bg-gray-50 font-sans">
    <?php include 'includes/header.php'; ?>

    <!-- Search Header -->
    <div class="bg-white shadow-sm border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold text-gray-900 mb-4">
                    <?php if ($query): ?>
                        Risultati per "<?php echo htmlspecialchars($query); ?>"
                    <?php else: ?>
                        <?php echo htmlspecialchars($contentManager->getText('search-in-passione', 'Ricerca in Passione Calabria')); ?>
                    <?php endif; ?>
                </h1>
                <p class="text-gray-600">
                    <?php if ($totalResults > 0): ?>
                        Trovati <?php echo $totalResults; ?> risultati
                    <?php else: ?>
                        Cerca tra i nostri contenuti sulla Calabria
                    <?php endif; ?>
                </p>
            </div>

            <!-- Search Form -->
            <form method="GET" action="ricerca.php" class="max-w-4xl mx-auto">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div class="md:col-span-2">
                        <input
                            type="text"
                            name="q"
                            value="<?php echo htmlspecialchars($query); ?>"
                            placeholder="<?php echo htmlspecialchars($contentManager->getText('what-are-you-looking-for', 'Cosa stai cercando?')); ?>"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        >
                    </div>
                    <div>
                        <select name="categoria" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value=""><?php echo htmlspecialchars($contentManager->getText('all-categories', 'Tutte le categorie')); ?></option>
                            <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>" <?php echo $categoryId == $category['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <select name="provincia" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value=""><?php echo htmlspecialchars($contentManager->getText('all-provinces', 'Tutte le province')); ?></option>
                            <?php foreach ($provinces as $province): ?>
                            <option value="<?php echo $province['id']; ?>" <?php echo $provinceId == $province['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($province['name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="text-center mt-6">
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-8 py-3 rounded-lg font-semibold transition-colors">
                        <i data-lucide="search" class="w-5 h-5 mr-2 inline"></i>
                        <?php echo htmlspecialchars($contentManager->getText('search', 'Cerca')); ?>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Results -->
    <main class="py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <?php if ($query || $provinceId || $categoryId): ?>
                <?php if (count($results) > 0): ?>
                    <!-- Results Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 mb-12">
                        <?php foreach ($results as $article): ?>
                        <article class="article-card">
                            <div class="aspect-[4/3] bg-gray-200 relative overflow-hidden">
                                <?php if ($article['featured_image']): ?>
                                <img src="<?php echo htmlspecialchars($article['featured_image']); ?>"
                                     alt="<?php echo htmlspecialchars($article['title']); ?>"
                                     class="w-full h-full object-cover">
                                <?php else: ?>
                                <div class="w-full h-full bg-gradient-to-br from-gray-300 to-gray-400 flex items-center justify-center">
                                    <i data-lucide="image" class="w-16 h-16 text-gray-500"></i>
                                </div>
                                <?php endif; ?>

                                <div class="absolute top-4 left-4">
                                    <span class="bg-blue-600 text-white px-3 py-1 rounded-full text-sm font-medium">
                                        <?php echo htmlspecialchars($article['category_name']); ?>
                                    </span>
                                </div>

                                <?php if ($article['views']): ?>
                                <div class="absolute top-4 right-4">
                                    <span class="bg-black/50 text-white px-2 py-1 rounded text-sm flex items-center">
                                        <i data-lucide="eye" class="w-3 h-3 mr-1"></i>
                                        <?php echo $article['views']; ?>
                                    </span>
                                </div>
                                <?php endif; ?>
                            </div>

                            <div class="p-6">
                                <h2 class="text-xl font-bold text-gray-900 mb-3 line-clamp-2">
                                    <a href="articolo.php?slug=<?php echo $article['slug']; ?>" class="hover:text-blue-600 transition-colors">
                                        <?php echo htmlspecialchars($article['title']); ?>
                                    </a>
                                </h2>

                                <p class="text-gray-600 mb-4 line-clamp-3">
                                    <?php echo htmlspecialchars($article['excerpt']); ?>
                                </p>

                                <div class="flex items-center justify-between text-sm text-gray-500">
                                    <div class="flex items-center space-x-4">
                                        <?php if ($article['province_name']): ?>
                                        <span class="flex items-center">
                                            <i data-lucide="map-pin" class="w-4 h-4 mr-1"></i>
                                            <?php echo htmlspecialchars($article['province_name']); ?>
                                        </span>
                                        <?php endif; ?>
                                        <span class="flex items-center">
                                            <i data-lucide="calendar" class="w-4 h-4 mr-1"></i>
                                            <?php echo formatDate($article['created_at']); ?>
                                        </span>
                                    </div>

                                    <a href="articolo.php?slug=<?php echo $article['slug']; ?>"
                                       class="text-blue-600 hover:text-blue-700 font-medium">
                                        <?php echo htmlspecialchars($contentManager->getText('read', 'Leggi')); ?>
                                    </a>
                                </div>
                            </div>
                        </article>
                        <?php endforeach; ?>
                    </div>

                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                    <div class="pagination">
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <?php
                            $pageUrl = "ricerca.php?";
                            $params = [];
                            if ($query) $params[] = "q=" . urlencode($query);
                            if ($provinceId) $params[] = "provincia=$provinceId";
                            if ($categoryId) $params[] = "categoria=$categoryId";
                            $params[] = "page=$i";
                            $pageUrl .= implode('&', $params);
                            ?>
                            <a href="<?php echo $pageUrl; ?>"
                               class="pagination-item <?php echo $i == $page ? 'active' : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>
                    </div>
                    <?php endif; ?>

                <?php else: ?>
                    <!-- No Results -->
                    <div class="text-center py-20">
                        <div class="w-24 h-24 bg-gray-200 rounded-full flex items-center justify-center mx-auto mb-6">
                            <i data-lucide="search-x" class="w-12 h-12 text-gray-400"></i>
                        </div>
                        <h2 class="text-2xl font-bold text-gray-900 mb-4"><?php echo htmlspecialchars($contentManager->getText('no-results-found', 'Nessun risultato trovato')); ?></h2>
                        <p class="text-gray-600 mb-8">
                            <?php echo htmlspecialchars($contentManager->getText('no-results-description', 'Non abbiamo trovato contenuti che corrispondano alla tua ricerca. Prova con termini diversi o esplora le nostre categorie.')); ?>
                        </p>
                        <div class="flex flex-col sm:flex-row justify-center gap-4">
                            <a href="categorie.php" class="bg-blue-600 hover:bg-blue-700 text-white px-8 py-3 rounded-lg font-semibold transition-colors">
                                <?php echo htmlspecialchars($contentManager->getText('explore-categories', 'Esplora Categorie')); ?>
                            </a>
                            <a href="province.php" class="border-2 border-blue-600 text-blue-600 hover:bg-blue-600 hover:text-white px-8 py-3 rounded-lg font-semibold transition-colors">
                                <?php echo htmlspecialchars($contentManager->getText('discover-provinces', 'Scopri Province')); ?>
                            </a>
                        </div>
                    </div>
                <?php endif; ?>

            <?php else: ?>
                <!-- Welcome Search -->
                <div class="text-center py-20">
                    <div class="w-24 h-24 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-6">
                        <i data-lucide="search" class="w-12 h-12 text-blue-600"></i>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-900 mb-4"><?php echo htmlspecialchars($contentManager->getText('what-discover-calabria', 'Cosa vuoi scoprire della Calabria?')); ?></h2>
                    <p class="text-gray-600 mb-8">
                        <?php echo htmlspecialchars($contentManager->getText('search-description', 'Usa il modulo di ricerca qui sopra per trovare luoghi, tradizioni, eventi e tutto quello che ti interessa sulla nostra regione.')); ?>
                    </p>

                    <!-- Suggested Searches -->
                    <div class="max-w-2xl mx-auto">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4"><?php echo htmlspecialchars($contentManager->getText('suggested-searches', 'Ricerche suggerite:')); ?></h3>
                        <div class="flex flex-wrap justify-center gap-3">
                            <a href="ricerca.php?q=Tropea" class="bg-blue-100 text-blue-700 px-4 py-2 rounded-full text-sm hover:bg-blue-200 transition-colors">Tropea</a>
                            <a href="ricerca.php?q=nduja" class="bg-blue-100 text-blue-700 px-4 py-2 rounded-full text-sm hover:bg-blue-200 transition-colors">'Nduja</a>
                            <a href="ricerca.php?q=Bronzi" class="bg-blue-100 text-blue-700 px-4 py-2 rounded-full text-sm hover:bg-blue-200 transition-colors">Bronzi di Riace</a>
                            <a href="ricerca.php?q=Sila" class="bg-blue-100 text-blue-700 px-4 py-2 rounded-full text-sm hover:bg-blue-200 transition-colors">Sila</a>
                            <a href="ricerca.php?q=Gerace" class="bg-blue-100 text-blue-700 px-4 py-2 rounded-full text-sm hover:bg-blue-200 transition-colors">Gerace</a>
                            <a href="ricerca.php?q=sagre" class="bg-blue-100 text-blue-700 px-4 py-2 rounded-full text-sm hover:bg-blue-200 transition-colors">Sagre</a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Footer -->
    <?php include 'includes/footer.php'; ?>

    <!-- JavaScript -->
    <script src="assets/js/main.js"></script>
    <script>
        // Inizializza Lucide icons
        lucide.createIcons();

        // Auto-submit form on select change
        document.querySelectorAll('select[name="categoria"], select[name="provincia"]').forEach(select => {
            select.addEventListener('change', function() {
                // Solo se c'è già una query o un altro filtro
                const form = this.closest('form');
                const query = form.querySelector('input[name="q"]').value;
                const categoria = form.querySelector('select[name="categoria"]').value;
                const provincia = form.querySelector('select[name="provincia"]').value;

                if (query || categoria || provincia) {
                    form.submit();
                }
            });
        });

        // Evidenzia termini di ricerca
        function highlightSearchTerms() {
            const query = '<?php echo addslashes($query); ?>';
            if (query.length >= 3) {
                const regex = new RegExp(`(${query})`, 'gi');
                const elements = document.querySelectorAll('.article-card h2, .article-card p');

                elements.forEach(element => {
                    element.innerHTML = element.innerHTML.replace(regex, '<mark class="bg-yellow-200">$1</mark>');
                });
            }
        }

        // Esegui highlight dopo il caricamento
        if ('<?php echo $query; ?>') {
            highlightSearchTerms();
        }
    </script>
</body>
</html>
