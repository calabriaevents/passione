<?php
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/ContentManagerSimple.php';

$db = new Database();
$contentManager = new ContentManagerSimple();
$currentLang = $contentManager->getCurrentLanguage();

// Verifica se l'ID categoria è fornito
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: categorie.php");
    exit;
}

$categoryId = (int)$_GET['id'];

// Carica dati categoria
$category = $db->getCategoryById($categoryId);
if (!$category) {
    header("Location: categorie.php");
    exit;
}

// Carica articoli della categoria
$articles = $db->getArticlesByCategory($categoryId);
$articleCount = $db->getArticleCountByCategory($categoryId);
?>
<!DOCTYPE html>
<html <?php echo $contentManager->getLanguageAttributes(); ?>>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($category['name']); ?> - <?php echo htmlspecialchars($contentManager->getText('site-name', 'Passione Calabria')); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($category['description']); ?>">

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
                <span class="breadcrumb-item"><a href="categorie.php" class="text-blue-600 hover:text-blue-700"><?php echo htmlspecialchars($contentManager->getText('nav-categories', 'Categorie')); ?></a></span>
                <span class="breadcrumb-item text-gray-900 font-medium"><?php echo htmlspecialchars($contentManager->getText('category-name-'.$category['id'], $category['name'])); ?></span>
            </nav>
        </div>
    </div>

    <!-- Category Hero -->
    <div class="bg-gradient-to-r from-blue-600 via-teal-500 to-yellow-500 text-white py-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <div class="text-6xl mb-6"><?php echo $category['icon']; ?></div>
            <h1 class="text-4xl md:text-5xl font-bold mb-4">
                <?php echo htmlspecialchars($category['name']); ?>
            </h1>
            <p class="text-xl text-blue-100 max-w-3xl mx-auto">
                <?php echo htmlspecialchars($category['description']); ?>
            </p>
            <div class="mt-8">
                <span class="bg-white/20 backdrop-blur-sm text-white px-4 py-2 rounded-full text-lg">
                    <?php echo $articleCount; ?> <?php echo htmlspecialchars($contentManager->getText($articleCount == 1 ? 'article-singular' : 'articles-plural', $articleCount == 1 ? 'articolo' : 'articoli')); ?>
                </span>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <main class="py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            
            <?php if (!empty($articles)): ?>
            <!-- Articles Grid -->
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
                                    <?php echo htmlspecialchars($article['province_name'] ?? 'Calabria'); ?>
                                </span>
                                <span class="bg-yellow-500 text-white px-3 py-1 rounded-full text-sm font-medium">
                                    <?php echo $article['views']; ?> <?php echo htmlspecialchars($contentManager->getText('views', 'visite')); ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="absolute bottom-4 left-4 right-4 text-white">
                            <h2 class="text-xl font-bold mb-2 line-clamp-2">
                                <?php echo htmlspecialchars($article['title']); ?>
                            </h2>
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
                            <span><?php echo htmlspecialchars($contentManager->getText('read-more', 'Leggi di più')); ?></span> <i data-lucide="arrow-right" class="w-4 h-4 ml-1"></i>
                        </a>
                    </div>
                </article>
                <?php endforeach; ?>
            </div>
            
            <?php else: ?>
            <!-- Empty State -->
            <div class="text-center py-20">
                <div class="text-6xl mb-6"><?php echo $category['icon']; ?></div>
                <h2 class="text-2xl font-bold text-gray-900 mb-4">
                    <?php echo htmlspecialchars($contentManager->getText('no-articles', 'Nessun articolo disponibile')); ?>
                </h2>
                <p class="text-gray-600 mb-8 max-w-md mx-auto">
                    <?php echo htmlspecialchars($contentManager->getText('no-articles-category', 'Non ci sono ancora articoli in questa categoria, ma ne stiamo preparando di fantastici!')); ?>
                </p>
                <div class="flex flex-col sm:flex-row justify-center gap-4">
                    <a href="categorie.php" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-full font-semibold transition-colors">
                        <?php echo htmlspecialchars($contentManager->getText('explore-other-categories', 'Esplora Altre Categorie')); ?>
                    </a>
                    <a href="suggerisci.php" class="border-2 border-blue-600 text-blue-600 hover:bg-blue-600 hover:text-white px-6 py-3 rounded-full font-semibold transition-colors">
                        <?php echo htmlspecialchars($contentManager->getText('suggest-content', 'Suggerisci Contenuti')); ?>
                    </a>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Related Categories -->
            <div class="mt-16">
                <h3 class="text-2xl font-bold text-gray-900 mb-8 text-center">
                    <?php echo htmlspecialchars($contentManager->getText('explore-other-categories', 'Esplora Altre Categorie')); ?>
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <?php 
                    $otherCategories = array_filter($db->getCategories(), function($cat) use ($categoryId) {
                        return $cat['id'] != $categoryId;
                    });
                    $randomCategories = array_slice($otherCategories, 0, 3);
                    ?>
                    <?php foreach ($randomCategories as $relatedCategory): ?>
                    <a href="categoria.php?id=<?php echo $relatedCategory['id']; ?>" 
                       class="block bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition-all group">
                        <div class="text-4xl mb-3"><?php echo $relatedCategory['icon']; ?></div>
                        <h4 class="text-lg font-bold text-gray-900 mb-2 group-hover:text-blue-600 transition-colors">
                            <?php echo htmlspecialchars($relatedCategory['name']); ?>
                        </h4>
                        <p class="text-gray-600 text-sm mb-4">
                            <?php echo htmlspecialchars(substr($relatedCategory['description'], 0, 100)); ?>...
                        </p>
                        <div class="flex items-center text-blue-600 font-semibold">
                            <span><?php echo htmlspecialchars($contentManager->getText('explore', 'Esplora')); ?></span> <i data-lucide="arrow-right" class="w-4 h-4 ml-1"></i>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>


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

        document.querySelectorAll('article').forEach(article => {
            observer.observe(article);
        });
    </script>
</body>
</html>