<?php
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/ContentManagerSimple.php';
require_once 'includes/PreventiveTranslationService.php';

if (!isset($_GET['slug'])) {
    header('Location: index.php');
    exit;
}

// Inizializza database e gestore contenuti multilingue
$db = new Database();
$contentManager = new ContentManagerSimple();
$translationService = new PreventiveTranslationService($db);
$currentLang = $contentManager->getCurrentLanguage();

$slug = $_GET['slug'];

// 🌍 TRADUZIONE: Cerca articolo nella lingua corrente
$article = null;
if ($currentLang === 'it') {
    // Per italiano, usa metodo originale
    $article = $db->getArticleBySlug($slug);
} else {
    // Per altre lingue, cerca prima nelle traduzioni
    $article = $translationService->getArticleTranslation(null, $currentLang);
    // Se non trova, prova con slug originale
    if (!$article) {
        $originalArticle = $db->getArticleBySlug($slug);
        if ($originalArticle) {
            $article = $translationService->getArticleTranslation($originalArticle['id'], $currentLang);
        }
    }
}

if (!$article) {
    header('HTTP/1.0 404 Not Found');
    echo '<div>' . htmlspecialchars($contentManager->getText('article-not-found', 'Articolo non trovato')) . '</div>';
    exit;
}

$db->incrementArticleViews($article['id']);

// Get category-specific data
$categoryFields = $db->getCategoryFields($article['category_id']);
$categoryData = $db->getArticleCategoryData($article['id']);
?>
<!DOCTYPE html>
<html <?php echo $contentManager->getLanguageAttributes(); ?>>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($article['title']); ?> - <?php echo htmlspecialchars($contentManager->getText('site-name', 'Passione Calabria')); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-gray-100">
    <?php include 'includes/header.php'; ?>

    <main class="container mx-auto px-4 py-8">
        <article class="max-w-4xl mx-auto bg-white p-8 rounded-lg shadow-lg">
            <h1 class="text-4xl font-bold text-gray-900 mb-4"><?php echo htmlspecialchars($article['title']); ?></h1>
            <div class="flex items-center text-gray-500 text-sm mb-6">
                <span><?php echo htmlspecialchars($contentManager->getText('by-author', 'di')); ?> <?php echo htmlspecialchars($article['author']); ?></span>
                <span class="mx-2">&bull;</span>
                <span><?php echo date('d M Y', strtotime($article['created_at'])); ?></span>
                <span class="mx-2">&bull;</span>
                <span><?php echo htmlspecialchars($article['category_name']); ?></span>
            </div>
            <?php if ($article['featured_image']): ?>
                <div class="relative mb-8">
                    <img src="<?php echo htmlspecialchars($article['featured_image']); ?>" alt="<?php echo htmlspecialchars($article['title']); ?>" class="w-full h-auto rounded-lg">
                    
                    <!-- Pulsante Visita la Città -->
                    <?php if ($article['city_name']): ?>
                    <div class="absolute bottom-4 right-4">
                        <a href="citta-dettaglio.php?id=<?php echo $article['city_id'] ?? ''; ?>" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-full font-semibold shadow-lg transition-colors inline-flex items-center">
                            <i data-lucide="map-pin" class="w-4 h-4 mr-2"></i>
                            <span><?php echo htmlspecialchars($contentManager->getText('visit-city', 'Visita')); ?></span> <?php echo htmlspecialchars($article['city_name']); ?>
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            <div class="prose max-w-none">
                <?php echo $article['content']; ?>
            </div>
            
            <!-- Informazioni Specifiche per Categoria -->
            <?php if (!empty($categoryFields) && !empty($categoryData)): ?>
            <div class="mt-12 bg-blue-50 p-6 rounded-lg border border-blue-200">
                <h3 class="text-2xl font-bold text-blue-800 mb-6 flex items-center">
                    <i data-lucide="info" class="w-6 h-6 mr-2"></i>
                    <span><?php echo htmlspecialchars($contentManager->getText('category-info', 'Informazioni su')); ?></span> <?php echo htmlspecialchars($article['category_name']); ?>
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <?php foreach ($categoryFields as $field): 
                        $fieldValue = $categoryData[$field['id']] ?? null;
                        if (!empty($fieldValue)): ?>
                        <div class="bg-white p-4 rounded-lg shadow-sm border">
                            <h4 class="font-semibold text-gray-800 mb-2 flex items-center">
                                <?php 
                                // Icona basata sul tipo di campo
                                $fieldIcon = 'info';
                                switch ($field['field_type']) {
                                    case 'text': $fieldIcon = 'type'; break;
                                    case 'textarea': $fieldIcon = 'file-text'; break;
                                    case 'select': $fieldIcon = 'list'; break;
                                    case 'number': $fieldIcon = 'hash'; break;
                                    default: $fieldIcon = 'info';
                                }
                                ?>
                                <i data-lucide="<?php echo $fieldIcon; ?>" class="w-4 h-4 mr-2 text-blue-600"></i>
                                <?php echo htmlspecialchars($field['field_label']); ?>
                            </h4>
                            <div class="text-gray-700">
                                <?php 
                                if ($field['field_type'] === 'textarea') {
                                    echo nl2br(htmlspecialchars($fieldValue));
                                } else {
                                    echo htmlspecialchars($fieldValue);
                                }
                                ?>
                            </div>
                        </div>
                    <?php endif; endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Galleria Immagini -->
            <?php if (!empty($article['gallery_images'])): 
                $galleryImages = json_decode($article['gallery_images'], true);
                if (is_array($galleryImages) && count($galleryImages) > 0): ?>
                <div class="mt-12">
                    <h3 class="text-2xl font-bold text-gray-900 mb-6"><?php echo htmlspecialchars($contentManager->getText('photo-gallery', 'Galleria Fotografica')); ?></h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <?php foreach ($galleryImages as $index => $imagePath): ?>
                            <div class="relative group cursor-pointer" onclick="openGalleryModal(<?php echo $index; ?>)">
                                <img src="<?php echo htmlspecialchars($imagePath); ?>" 
                                     alt="<?php echo htmlspecialchars($contentManager->getText('gallery-image', 'Galleria')); ?> <?php echo $index + 1; ?>" 
                                     class="w-full h-64 object-cover rounded-lg group-hover:opacity-90 transition-opacity">
                                <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-20 rounded-lg transition-opacity flex items-center justify-center">
                                    <i data-lucide="zoom-in" class="w-8 h-8 text-white opacity-0 group-hover:opacity-100 transition-opacity"></i>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Modal per Galleria -->
                <div id="galleryModal" class="fixed inset-0 bg-black bg-opacity-75 hidden z-50 flex items-center justify-center p-4">
                    <div class="relative max-w-4xl max-h-full">
                        <img id="modalImage" src="" alt="" class="max-w-full max-h-full object-contain rounded-lg">
                        <button onclick="closeGalleryModal()" class="absolute top-4 right-4 text-white hover:text-gray-300 text-3xl font-bold" title="<?php echo htmlspecialchars($contentManager->getText('close', 'Chiudi')); ?>">
                            ×
                        </button>
                        <button onclick="prevImage()" class="absolute left-4 top-1/2 transform -translate-y-1/2 text-white hover:text-gray-300 text-3xl font-bold">
                            ‹
                        </button>
                        <button onclick="nextImage()" class="absolute right-4 top-1/2 transform -translate-y-1/2 text-white hover:text-gray-300 text-3xl font-bold">
                            ›
                        </button>
                    </div>
                </div>

                <script>
                    const galleryImages = <?php echo json_encode($galleryImages); ?>;
                    let currentImageIndex = 0;

                    function openGalleryModal(index) {
                        currentImageIndex = index;
                        document.getElementById('modalImage').src = galleryImages[index];
                        document.getElementById('galleryModal').classList.remove('hidden');
                    }

                    function closeGalleryModal() {
                        document.getElementById('galleryModal').classList.add('hidden');
                    }

                    function nextImage() {
                        currentImageIndex = (currentImageIndex + 1) % galleryImages.length;
                        document.getElementById('modalImage').src = galleryImages[currentImageIndex];
                    }

                    function prevImage() {
                        currentImageIndex = (currentImageIndex - 1 + galleryImages.length) % galleryImages.length;
                        document.getElementById('modalImage').src = galleryImages[currentImageIndex];
                    }

                    // Chiudi modal con ESC
                    document.addEventListener('keydown', function(e) {
                        if (e.key === 'Escape') {
                            closeGalleryModal();
                        }
                    });
                </script>
            <?php endif; endif; ?>
        </article>
    </main>

    <?php include 'includes/footer.php'; ?>
    
    <script>
        lucide.createIcons();
    </script>
</body>
</html>
