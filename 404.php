<?php
require_once 'includes/config.php';
require_once 'includes/ContentManagerSimple.php';

$contentManager = new ContentManagerSimple();
$currentLang = $contentManager->getCurrentLanguage();
?>
<!DOCTYPE html>
<html <?php echo $contentManager->getLanguageAttributes(); ?>>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($contentManager->getText('page-not-found-title', 'Pagina non trovata')); ?> - <?php echo htmlspecialchars($contentManager->getText('site-name', 'Passione Calabria')); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-gray-100">
    <?php include 'includes/header.php'; ?>

    <main class="container mx-auto px-4 py-16 text-center">
        <div class="max-w-2xl mx-auto">
            <div class="mb-8">
                <i data-lucide="map-pin-off" class="w-24 h-24 text-gray-400 mx-auto mb-4"></i>
                <h1 class="text-6xl font-bold text-gray-800 mb-4">404</h1>
                <h2 class="text-2xl font-semibold text-gray-600 mb-4"><?php echo htmlspecialchars($contentManager->getText('page-not-found', 'Pagina non trovata')); ?></h2>
                <p class="text-gray-600 mb-8">
                    <?php echo htmlspecialchars($contentManager->getText('page-not-found-desc', 'La pagina che stai cercando potrebbe essere stata spostata, eliminata o non esistere più.')); ?>
                </p>
            </div>
            
            <div class="space-y-4">
                <a href="index.php" class="inline-flex items-center bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-semibold transition-colors">
                    <i data-lucide="home" class="w-5 h-5 mr-2"></i>
                    <?php echo htmlspecialchars($contentManager->getText('back-to-home', 'Torna alla Homepage')); ?>
                </a>
                
                <div class="flex justify-center space-x-4 text-sm">
                    <a href="categorie.php" class="text-blue-600 hover:text-blue-700"><?php echo htmlspecialchars($contentManager->getText('explore-categories', 'Esplora le Categorie')); ?></a>
                    <span class="text-gray-400">|</span>
                    <a href="province.php" class="text-blue-600 hover:text-blue-700"><?php echo htmlspecialchars($contentManager->getText('discover-provinces', 'Scopri le Province')); ?></a>
                    <span class="text-gray-400">|</span>
                    <a href="page.php?slug=contatti" class="text-blue-600 hover:text-blue-700"><?php echo htmlspecialchars($contentManager->getText('contact-us', 'Contattaci')); ?></a>
                </div>
            </div>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>
    <script>
        lucide.createIcons();
    </script>
</body>
</html>