<?php
require_once 'includes/config.php';
require_once 'includes/database.php';

$db = new Database();
$articles = $db->getArticles();
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title class="translatable" data-translate="articles-page-title">Articoli - Passione Calabria</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-gray-100">
    <?php include 'includes/header.php'; ?>

    <main class="container mx-auto px-4 py-8">
        <h1 class="text-4xl font-bold text-center text-gray-800 mb-8 translatable" data-translate="all-articles-title">Tutti gli Articoli</h1>
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
                        <p class="text-gray-600 mb-4"><?php echo htmlspecialchars($article['excerpt']); ?></p>
                        <a href="articolo.php?slug=<?php echo $article['slug']; ?>" class="text-blue-600 hover:underline">Leggi di più</a>
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
