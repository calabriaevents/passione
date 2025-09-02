<?php
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/ContentManagerSimple.php';
require_once 'includes/PreventiveTranslationService_DeepL.php';

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

// Carica articolo (traduzione temporaneamente disabilitata)
$article = $db->getArticleBySlug($slug);

if (!$article) {
    header('HTTP/1.0 404 Not Found');
    echo '<div>' . htmlspecialchars($contentManager->getText('article-not-found', 'Articolo non trovato')) . '</div>';
    exit;
}

$db->incrementArticleViews($article['id']);

// Get category-specific data
$categoryFields = $db->getCategoryFields($article['category_id']);
$categoryData = $db->getArticleCategoryData($article['id']);

// Handle comment submission
$commentMessage = '';
$commentError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_comment'])) {
    $author_name = trim($_POST['author_name'] ?? '');
    $author_email = trim($_POST['author_email'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $rating = (int)($_POST['rating'] ?? 0);
    
    // Validation
    if (empty($author_name) || empty($author_email) || empty($content)) {
        $commentError = $contentManager->getText('validation-required-fields', 'Tutti i campi sono obbligatori.');
    } elseif (!filter_var($author_email, FILTER_VALIDATE_EMAIL)) {
        $commentError = $contentManager->getText('validation-invalid-email', 'Email non valida.');
    } elseif (strlen($content) < 10) {
        $commentError = $contentManager->getText('validation-comment-length', 'Il commento deve essere di almeno 10 caratteri.');
    } elseif ($rating < 1 || $rating > 5) {
        $commentError = $contentManager->getText('validation-rating-required', 'Devi selezionare una valutazione da 1 a 5 stelle.');
    } else {
        try {
            $stmt = $db->prepare("
                INSERT INTO comments (article_id, author_name, author_email, content, rating, status, created_at)
                VALUES (?, ?, ?, ?, ?, 'approved', datetime('now'))
            ");
            
            if ($stmt->execute([$article['id'], $author_name, $author_email, $content, $rating])) {
                $commentMessage = $contentManager->getText('comment-success', 'Commento inviato con successo!');
                // Clear form data
                $_POST = [];
            } else {
                $commentError = $contentManager->getText('comment-error', 'Errore durante l\'invio del commento.');
            }
        } catch (Exception $e) {
            $commentError = $contentManager->getText('error-prefix', 'Errore: ') . $e->getMessage();
        }
    }
}

// Load existing comments
$comments = [];
try {
    $stmt = $db->prepare("
        SELECT * FROM comments 
        WHERE article_id = ? AND status = 'approved' 
        ORDER BY created_at DESC
    ");
    $stmt->execute([$article['id']]);
    $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log('Error loading comments: ' . $e->getMessage());
}
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

            <!-- Sezione Commenti -->
            <div class="mt-16 border-t border-gray-200 pt-12">
                <h3 class="text-3xl font-bold text-gray-900 mb-8 flex items-center">
                    <i data-lucide="message-circle" class="w-8 h-8 mr-3 text-blue-600"></i>
                    <?php echo htmlspecialchars($contentManager->getText('comments', 'Commenti')); ?> <span class="text-lg text-gray-500 ml-2">(<?php echo count($comments); ?>)</span>
                </h3>

                <!-- Form per Nuovo Commento -->
                <div class="bg-gray-50 p-6 rounded-lg mb-8">
                    <h4 class="text-xl font-semibold text-gray-900 mb-4"><?php echo htmlspecialchars($contentManager->getText('leave-comment', 'Lascia un Commento')); ?></h4>
                    
                    <?php if ($commentMessage): ?>
                        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                            <?php echo htmlspecialchars($commentMessage); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($commentError): ?>
                        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                            <?php echo htmlspecialchars($commentError); ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="author_name" class="block text-sm font-medium text-gray-700 mb-2"><?php echo htmlspecialchars($contentManager->getText('name-required', 'Nome')); ?> *</label>
                                <input type="text" id="author_name" name="author_name" required
                                       value="<?php echo htmlspecialchars($_POST['author_name'] ?? ''); ?>"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            <div>
                                <label for="author_email" class="block text-sm font-medium text-gray-700 mb-2"><?php echo htmlspecialchars($contentManager->getText('email-required', 'Email')); ?> *</label>
                                <input type="email" id="author_email" name="author_email" required
                                       value="<?php echo htmlspecialchars($_POST['author_email'] ?? ''); ?>"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>
                        </div>
                        
                        <div>
                            <label for="rating" class="block text-sm font-medium text-gray-700 mb-2"><?php echo htmlspecialchars($contentManager->getText('rating-required', 'Valutazione')); ?> *</label>
                            <div class="flex items-center space-x-1">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <label class="cursor-pointer">
                                        <input type="radio" name="rating" value="<?php echo $i; ?>" class="sr-only" 
                                               <?php echo (($_POST['rating'] ?? 0) == $i) ? 'checked' : ''; ?>
                                               required>
                                        <i data-lucide="star" class="w-6 h-6 text-gray-300 hover:text-yellow-400 transition-colors star-rating" data-rating="<?php echo $i; ?>"></i>
                                    </label>
                                <?php endfor; ?>
                            </div>
                            <p class="text-sm text-gray-500 mt-1"><?php echo htmlspecialchars($contentManager->getText('rating-instruction', 'Clicca sulle stelle per dare una valutazione')); ?></p>
                        </div>
                        
                        <div>
                            <label for="content" class="block text-sm font-medium text-gray-700 mb-2"><?php echo htmlspecialchars($contentManager->getText('comment-required', 'Commento')); ?> *</label>
                            <textarea id="content" name="content" rows="4" required placeholder="<?php echo htmlspecialchars($contentManager->getText('comment-placeholder', 'Scrivi il tuo commento...')); ?>"
                                      class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"><?php echo htmlspecialchars($_POST['content'] ?? ''); ?></textarea>
                        </div>
                        
                        <div>
                            <button type="submit" name="submit_comment" 
                                    class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-semibold transition-colors inline-flex items-center">
                                <i data-lucide="send" class="w-4 h-4 mr-2"></i>
                                <?php echo htmlspecialchars($contentManager->getText('submit-comment', 'Invia Commento')); ?>
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Lista Commenti -->
                <?php if (!empty($comments)): ?>
                    <div class="space-y-6">
                        <?php foreach ($comments as $comment): ?>
                            <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
                                <div class="flex items-start justify-between mb-4">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                                            <i data-lucide="user" class="w-5 h-5 text-blue-600"></i>
                                        </div>
                                        <div>
                                            <h5 class="font-semibold text-gray-900"><?php echo htmlspecialchars($comment['author_name']); ?></h5>
                                            <p class="text-sm text-gray-500"><?php echo date('d M Y - H:i', strtotime($comment['created_at'])); ?></p>
                                        </div>
                                    </div>
                                    
                                    <!-- Rating Stars -->
                                    <?php if ($comment['rating'] > 0): ?>
                                        <div class="flex items-center space-x-1">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <i data-lucide="star" class="w-4 h-4 <?php echo $i <= $comment['rating'] ? 'text-yellow-400 fill-current' : 'text-gray-300'; ?>"></i>
                                            <?php endfor; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="text-gray-700 leading-relaxed">
                                    <?php echo nl2br(htmlspecialchars($comment['content'])); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-8 bg-gray-50 rounded-lg">
                        <i data-lucide="message-circle" class="w-12 h-12 text-gray-400 mx-auto mb-4"></i>
                        <p class="text-gray-500 text-lg"><?php echo htmlspecialchars($contentManager->getText('no-comments-yet', 'Nessun commento ancora. Sii il primo a commentare!')); ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </article>
    </main>

    <?php include 'includes/footer.php'; ?>
    
    <script>
        lucide.createIcons();
        
        // Star Rating System
        document.addEventListener('DOMContentLoaded', function() {
            const starRatings = document.querySelectorAll('.star-rating');
            
            starRatings.forEach(star => {
                star.addEventListener('click', function() {
                    const rating = parseInt(this.getAttribute('data-rating'));
                    const ratingInput = document.querySelector('input[name="rating"][value="' + rating + '"]');
                    if (ratingInput) {
                        ratingInput.checked = true;
                    }
                    
                    // Update visual stars
                    starRatings.forEach((s, index) => {
                        if (index < rating) {
                            s.classList.remove('text-gray-300');
                            s.classList.add('text-yellow-400');
                        } else {
                            s.classList.remove('text-yellow-400');
                            s.classList.add('text-gray-300');
                        }
                    });
                });
                
                star.addEventListener('mouseenter', function() {
                    const rating = parseInt(this.getAttribute('data-rating'));
                    starRatings.forEach((s, index) => {
                        if (index < rating) {
                            s.classList.add('text-yellow-300');
                        }
                    });
                });
                
                star.addEventListener('mouseleave', function() {
                    starRatings.forEach(s => {
                        s.classList.remove('text-yellow-300');
                    });
                });
            });
            
            // Initialize stars based on current selection
            const checkedRating = document.querySelector('input[name="rating"]:checked');
            if (checkedRating) {
                const rating = parseInt(checkedRating.value);
                starRatings.forEach((s, index) => {
                    if (index < rating) {
                        s.classList.remove('text-gray-300');
                        s.classList.add('text-yellow-400');
                    }
                });
            }
        });
    </script>
</body>
</html>