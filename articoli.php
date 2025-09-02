<?php
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/ContentManagerSimple.php';

$db = new Database();
$contentManager = new ContentManagerSimple();
$articles = $db->getArticlesWithRatings();
?>
<!DOCTYPE html>
<html <?php echo $contentManager->getLanguageAttributes(); ?>>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($contentManager->getText('articles-page-title', 'Articoli')); ?> - <?php echo htmlspecialchars($contentManager->getText('site-name', 'Passione Calabria')); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-gray-100">
    <?php include 'includes/header.php'; ?>

    <main class="container mx-auto px-4 py-8">
        <h1 class="text-4xl font-bold text-center text-gray-800 mb-8"><?php echo htmlspecialchars($contentManager->getText('all-articles-title', 'Tutti gli Articoli')); ?></h1>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <?php foreach ($articles as $article): ?>
                <div class="bg-white rounded-lg shadow-lg overflow-hidden">
                    <?php if ($article['featured_image']): ?>
                        <a href="articolo.php?slug=<?php echo $article['slug']; ?>">
                            <img src="<?php echo htmlspecialchars($article['featured_image']); ?>" alt="<?php echo htmlspecialchars($article['title']); ?>" class="w-full h-48 object-cover">
                        </a>
                    <?php endif; ?>
                    <div class="p-6">
                        <h2 class="text-2xl font-bold text-gray-900 mb-2">
                            <a href="articolo.php?slug=<?php echo $article['slug']; ?>" class="hover:text-blue-600">
                                <?php echo htmlspecialchars($article['title']); ?>
                            </a>
                        </h2>
                        
                        <!-- Rating Display -->
                        <?php if ($article['total_ratings'] > 0): ?>
                            <div class="flex items-center mb-3">
                                <div class="flex items-center space-x-1">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <i data-lucide="star" class="w-4 h-4 <?php echo $i <= round($article['average_rating']) ? 'text-yellow-400 fill-current' : 'text-gray-300'; ?>"></i>
                                    <?php endfor; ?>
                                </div>
                                <span class="text-sm text-gray-600 ml-2">
                                    <?php echo $article['average_rating']; ?>/5 (<?php echo $article['total_ratings']; ?> <?php echo htmlspecialchars($contentManager->getText($article['total_ratings'] == 1 ? 'vote-singular' : 'votes-plural', $article['total_ratings'] == 1 ? 'voto' : 'voti')); ?>)
                                </span>
                            </div>
                        <?php endif; ?>
                        
                        <p class="text-gray-600 mb-4"><?php echo htmlspecialchars($article['excerpt']); ?></p>
                        <a href="articolo.php?slug=<?php echo $article['slug']; ?>" class="text-blue-600 hover:underline"><?php echo htmlspecialchars($contentManager->getText('read-more', 'Leggi di più')); ?></a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>
    <script>
        lucide.createIcons();
    </script>
</body>
</html>
