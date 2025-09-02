<?php
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/ContentManagerSimple.php';

$db = new Database();
$contentManager = new ContentManagerSimple();
$currentLang = $contentManager->getCurrentLanguage();

// Carica le categorie con gestione errori
try {
    $categories = $db->getCategories();
} catch (Exception $e) {
    error_log('Errore caricamento categorie: ' . $e->getMessage());
    $categories = [];
}

// Carica i conteggi degli articoli per ogni categoria
foreach ($categories as &$category) {
    $category['article_count'] = $db->getArticleCountByCategory($category['id']);
    $category['recent_articles'] = $db->getArticlesByCategory($category['id'], 3);
}
?>
<!DOCTYPE html>
<html <?php echo $contentManager->getLanguageAttributes(); ?>>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($contentManager->getText('categories-page-title', 'Categorie')); ?> - <?php echo htmlspecialchars($contentManager->getText('site-name', 'Passione Calabria')); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($contentManager->getText('categories-page-description', 'Esplora tutte le categorie di Passione Calabria: natura, cultura, gastronomia, mare e molto altro.')); ?>">

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
                <span class="breadcrumb-item"><a href="index.php" class="text-blue-600 hover:text-blue-700">Home</a></span>
                <span class="breadcrumb-item text-gray-900 font-medium"><?php echo htmlspecialchars($contentManager->getText('nav-categories', 'Categorie')); ?></span>
            </nav>
        </div>
    </div>

    <!-- Main Content -->
    <main class="py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Page Header -->
            <div class="text-center mb-16">
                <h1 class="text-4xl md:text-5xl font-bold text-gray-900 mb-6">
                    <?php echo htmlspecialchars($contentManager->getText('explore-categories-title', 'Esplora le')); ?> <span class="text-calabria-gradient"><?php echo htmlspecialchars($contentManager->getText('nav-categories', 'Categorie')); ?></span>
                </h1>
                <p class="text-xl text-gray-600 max-w-3xl mx-auto leading-relaxed">
                    <?php echo htmlspecialchars($contentManager->getText('categories-intro', 'Scopri la Calabria attraverso le sue diverse sfaccettature: dalla natura incontaminata alla ricca tradizione culturale, dalla gastronomia unica agli eventi che animano la regione. Ogni categoria racconta una storia diversa della nostra terra.')); ?>
                </p>
            </div>

            <!-- CTA Section (moved to top) -->
            <div class="mb-16 bg-gradient-to-r from-blue-600 via-teal-500 to-yellow-500 rounded-2xl p-12 text-center text-white">
                <h2 class="text-3xl font-bold mb-4"><?php echo htmlspecialchars($contentManager->getText('not-found-content', 'Non Trovi Quello Che Cerchi?')); ?></h2>
                <p class="text-xl mb-8 opacity-90">
                    <?php echo htmlspecialchars($contentManager->getText('help-improve', 'Aiutaci a migliorare Passione Calabria suggerendoci nuovi contenuti e categorie')); ?>
                </p>
                <div class="flex justify-center">
                    <a href="suggerisci.php" class="bg-white text-blue-600 px-8 py-3 rounded-full font-semibold hover:bg-gray-100 transition-colors">
                        <?php echo htmlspecialchars($contentManager->getText('suggest-place', 'Suggerisci un Luogo')); ?>
                    </a>
                </div>
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-16">
                <div class="bg-white rounded-xl shadow-lg p-6 text-center">
                    <div class="text-3xl font-bold text-blue-600 mb-2"><?php echo count($categories); ?></div>
                    <div class="text-gray-600"><?php echo htmlspecialchars($contentManager->getText('nav-categories', 'Categorie')); ?></div>
                </div>
                <div class="bg-white rounded-xl shadow-lg p-6 text-center">
                    <div class="text-3xl font-bold text-green-600 mb-2">
                        <?php
                        $totalArticles = 0;
                        foreach ($categories as $cat) {
                            $totalArticles += $cat['article_count'];
                        }
                        echo $totalArticles;
                        ?>
                    </div>
                    <div class="text-gray-600"><?php echo htmlspecialchars($contentManager->getText('total-articles', 'Articoli Totali')); ?></div>
                </div>
                <div class="bg-white rounded-xl shadow-lg p-6 text-center">
                    <div class="text-3xl font-bold text-purple-600 mb-2">5</div>
                    <div class="text-gray-600"><?php echo htmlspecialchars($contentManager->getText('provinces', 'Province')); ?></div>
                </div>
                <div class="bg-white rounded-xl shadow-lg p-6 text-center">
                    <div class="text-3xl font-bold text-orange-600 mb-2">13</div>
                    <div class="text-gray-600"><?php echo htmlspecialchars($contentManager->getText('cities', 'Città')); ?></div>
                </div>
            </div>

            <!-- Categories Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <?php foreach ($categories as $category): ?>
                <div class="category-card group">
                    <!-- Category Header -->
                    <div class="aspect-[4/3] bg-gradient-to-br from-blue-500 to-teal-600 relative overflow-hidden">
                        <div class="absolute inset-0 bg-black/20"></div>
                        <div class="absolute top-6 left-6">
                            <div class="bg-white/20 backdrop-blur-sm text-white px-4 py-2 rounded-full text-sm font-medium">
                                <?php echo $category['article_count']; ?> <?php echo htmlspecialchars($contentManager->getText($category['article_count'] == 1 ? 'article-singular' : 'articles-plural', $category['article_count'] == 1 ? 'articolo' : 'articoli')); ?>
                            </div>
                        </div>
                        <div class="absolute bottom-6 left-6 text-white">
                            <div class="text-5xl mb-3"><?php echo $category['icon']; ?></div>
                            <h2 class="text-2xl font-bold mb-2"><?php echo htmlspecialchars($category['name']); ?></h2>
                        </div>
                    </div>

                    <!-- Category Content -->
                    <div class="p-6">
                        <p class="text-gray-600 mb-6 leading-relaxed">
                            <?php echo htmlspecialchars($category['description']); ?>
                        </p>

                        <!-- Recent Articles Preview -->
                        <?php if (!empty($category['recent_articles'])): ?>
                        <div class="mb-6">
                            <h3 class="text-sm font-semibold text-gray-700 mb-3 uppercase tracking-wider"><?php echo htmlspecialchars($contentManager->getText('recent-articles', 'Articoli Recenti')); ?></h3>
                            <div class="space-y-3">
                                <?php foreach (array_slice($category['recent_articles'], 0, 2) as $article): ?>
                                <a href="articolo.php?slug=<?php echo $article['slug']; ?>" class="block group/article">
                                    <div class="flex items-start space-x-3">
                                        <div class="w-16 h-12 bg-gray-200 rounded flex-shrink-0 overflow-hidden">
                                            <?php if ($article['featured_image']): ?>
                                            <img src="<?php echo htmlspecialchars($article['featured_image']); ?>"
                                                 alt="<?php echo htmlspecialchars($article['title']); ?>"
                                                 class="w-full h-full object-cover">
                                            <?php else: ?>
                                            <div class="w-full h-full bg-gradient-to-br from-gray-300 to-gray-400"></div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <h4 class="text-sm font-medium text-gray-900 group-hover/article:text-blue-600 transition-colors line-clamp-2">
                                                <?php echo htmlspecialchars($article['title']); ?>
                                            </h4>
                                            <p class="text-xs text-gray-500 mt-1">
                                                <?php echo formatDate($article['created_at']); ?>
                                            </p>
                                        </div>
                                    </div>
                                </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Action Button -->
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-500 flex items-center">
                                <i data-lucide="file-text" class="w-4 h-4 mr-1"></i>
                                <?php echo $category['article_count']; ?> <?php echo htmlspecialchars($contentManager->getText('contents', 'contenuti')); ?>
                            </span>
                            <a href="categoria.php?id=<?php echo $category['id']; ?>"
                               class="inline-flex items-center text-blue-600 hover:text-blue-700 font-semibold transition-colors">
                                <span><?php echo htmlspecialchars($contentManager->getText('explore', 'Esplora')); ?></span> <i data-lucide="arrow-right" class="w-4 h-4 ml-1"></i>
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>


        </div>
    </main>

    <!-- Footer -->
    <?php include 'includes/footer.php'; ?>

    <!-- JavaScript -->
    <script src="assets/js/main.js"></script>
    <script>
        // Inizializza Lucide icons
        lucide.createIcons();

        // Aggiungi effetti hover alle card
        document.querySelectorAll('.category-card').forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.classList.add('animate-fade-in-up');
            });
        });

        // Lazy loading per le immagini
        if ('IntersectionObserver' in window) {
            const imageObserver = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        if (img.dataset.src) {
                            img.src = img.dataset.src;
                            img.classList.remove('opacity-0');
                            img.classList.add('opacity-100');
                            imageObserver.unobserve(img);
                        }
                    }
                });
            });

            document.querySelectorAll('img[data-src]').forEach(img => {
                imageObserver.observe(img);
            });
        }
    </script>
</body>
</html>
