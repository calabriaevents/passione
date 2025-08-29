<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start output buffering to catch any issues
ob_start();

echo "<!-- Starting index-progressive.php -->\n";

try {
    echo "<!-- Step 1: Including files -->\n";
    require_once 'includes/config.php';
    require_once 'includes/database.php';
    echo "<!-- Files included successfully -->\n";

    echo "<!-- Step 2: Creating database instance -->\n";
    $db = new Database();
    echo "<!-- Database instance created -->\n";

    echo "<!-- Step 3: Loading basic data -->\n";
    $categories = $db->getCategories();
    $provinces = $db->getProvinces();
    echo "<!-- Basic data loaded: " . count($categories) . " categories, " . count($provinces) . " provinces -->\n";

    echo "<!-- Step 4: Loading additional data -->\n";
    $featuredArticles = $db->getFeaturedArticles();
    $homeSections = $db->getHomeSections();
    $settings = $db->getSettings();
    echo "<!-- Additional data loaded -->\n";

    echo "<!-- Step 5: Processing settings array -->\n";
    $settingsArray = [];
    foreach ($settings as $setting) {
        $settingsArray[$setting['key']] = $setting['value'];
    }
    echo "<!-- Settings processed: " . count($settingsArray) . " settings -->\n";

    echo "<!-- Step 6: Processing categories with articles -->\n";
    foreach ($categories as &$category) {
        $category['articles'] = $db->getArticlesByCategory($category['id'], 6);
        $category['article_count'] = $db->getArticleCountByCategory($category['id']);
    }
    unset($category);
    echo "<!-- Categories processed with articles -->\n";

    echo "<!-- Step 7: Finding hero section -->\n";
    $heroSection = null;
    foreach ($homeSections as $section) {
        if ($section['section_name'] === 'hero') {
            $heroSection = $section;
            break;
        }
    }
    echo "<!-- Hero section found: " . ($heroSection ? 'YES' : 'NO') . " -->\n";

} catch (Exception $e) {
    echo "<!-- FATAL ERROR: " . $e->getMessage() . " -->\n";
    echo "<!-- Stack trace: " . $e->getTraceAsString() . " -->\n";
    die("Processing failed at step: " . $e->getMessage());
}

echo "<!-- All PHP processing completed successfully -->\n";

// End output buffering and get content
$content = ob_get_clean();

?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Passione Calabria - Progressive Test</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="assets/css/translation-system.css">
</head>
<body class="bg-gray-50">
    
    <!-- Debug Output -->
    <div class="bg-green-100 text-green-800 p-4 mb-4">
        <h2 class="font-bold text-lg mb-2">🔍 PHP Processing Debug:</h2>
        <pre class="text-xs overflow-x-auto"><?php echo htmlspecialchars($content); ?></pre>
    </div>
    
    <!-- Header Test -->
    <div class="bg-blue-100 text-blue-800 p-4 mb-4">
        <h2 class="font-bold text-lg mb-2">🏁 Header Include Test:</h2>
        <?php 
        try {
            include 'includes/header.php'; 
            echo "<p class='text-green-600 font-semibold mt-4'>✅ Header included successfully!</p>";
        } catch (Exception $e) {
            echo "<p class='text-red-600 font-semibold mt-4'>❌ Header failed: " . $e->getMessage() . "</p>";
        }
        ?>
    </div>
    
    <!-- Data Summary -->
    <div class="bg-yellow-100 text-yellow-800 p-4 mb-4">
        <h2 class="font-bold text-lg mb-2">📊 Data Summary:</h2>
        <ul class="space-y-1">
            <li>Categories: <?php echo count($categories ?? []); ?></li>
            <li>Provinces: <?php echo count($provinces ?? []); ?></li>
            <li>Featured Articles: <?php echo count($featuredArticles ?? []); ?></li>
            <li>Home Sections: <?php echo count($homeSections ?? []); ?></li>
            <li>Settings: <?php echo count($settingsArray ?? []); ?></li>
            <li>Hero Section: <?php echo $heroSection ? 'Found' : 'Not found'; ?></li>
        </ul>
    </div>

    <!-- Simple Hero Section Test -->
    <div class="bg-purple-500 text-white p-8 text-center">
        <h1 class="text-4xl font-bold mb-4 translatable" data-translate="hero-title">
            <?php echo htmlspecialchars($heroSection['title'] ?? 'Esplora la Calabria'); ?>
        </h1>
        <p class="text-xl translatable" data-translate="hero-subtitle">
            <?php echo htmlspecialchars($heroSection['subtitle'] ?? 'Mare cristallino e storia millenaria'); ?>
        </p>
        <p class="text-lg mt-4 translatable" data-translate="hero-description">
            Immergiti nella bellezza della Calabria, con le sue spiagge da sogno, il centro storico affascinante e i panorami mozzafiato.
        </p>
    </div>

    <!-- Simple Categories Test -->
    <div class="container mx-auto py-8">
        <h2 class="text-2xl font-bold mb-4">Categories Test (First 3):</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <?php foreach (array_slice($categories ?? [], 0, 3) as $category): ?>
            <div class="bg-white p-4 rounded-lg shadow">
                <h3 class="font-bold"><?php echo htmlspecialchars($category['name']); ?></h3>
                <p class="text-sm text-gray-600"><?php echo htmlspecialchars($category['description']); ?></p>
                <p class="text-xs text-gray-500 mt-2">Articles: <?php echo $category['article_count']; ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Translation System Test -->
    <script src="assets/js/translation-system.js"></script>
    <script src="assets/js/main.js"></script>
    <script>
        console.log('🧪 Progressive test loaded');
        console.log('Translation system available:', typeof PassioneTranslationSystem !== 'undefined');
        console.log('Translation instance before init:', typeof window.PassioneTranslation !== 'undefined');
        
        // Force initialize translation system if not auto-initialized
        if (typeof PassioneTranslationSystem !== 'undefined' && typeof window.PassioneTranslation === 'undefined') {
            console.log('🔧 Force initializing translation system...');
            window.PassioneTranslation = new PassioneTranslationSystem({
                debugMode: true
            });
            console.log('✅ Translation system manually initialized');
        }
        
        console.log('Translation instance after init:', typeof window.PassioneTranslation !== 'undefined');
        
        // Initialize Lucide if available
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    </script>

</body>
</html>