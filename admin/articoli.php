<?php
require_once '../includes/config.php';
require_once '../includes/database.php';
// require_once '../includes/PreventiveTranslationService.php';

// Controlla autenticazione (da implementare)
// // requireLogin(); // DISABILITATO

$db = new Database();
// $translationService = new PreventiveTranslationService($db);
$message = '';
$messageType = '';

$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? null;

// Gestione delle azioni POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'] ?? '';
    $slug = $_POST['slug'] ?? '';
    $content = $_POST['content'] ?? '';
    $excerpt = $_POST['excerpt'] ?? '';
    $category_id = $_POST['category_id'] ?? null;
    $province_id = $_POST['province_id'] ?? null;
    $city_id = $_POST['city_id'] ?? null;
    $status = $_POST['status'] ?? 'draft';
    $author = $_POST['author'] ?? 'Admin';
    
    // SEO fields
    $seo_title = $_POST['seo_title'] ?? '';
    $seo_description = $_POST['seo_description'] ?? '';
    $seo_keywords = $_POST['seo_keywords'] ?? '';
    
    // Handle image uploads
    $featured_image = null;
    $gallery_images = null;
    
    // Handle featured image upload
    if (isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../uploads/articles/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $fileExtension = strtolower(pathinfo($_FILES['featured_image']['name'], PATHINFO_EXTENSION));
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
        
        if (in_array($fileExtension, $allowedExtensions)) {
            $fileName = uniqid() . '.' . $fileExtension;
            $targetPath = $uploadDir . $fileName;
            
            if (move_uploaded_file($_FILES['featured_image']['tmp_name'], $targetPath)) {
                $featured_image = 'uploads/articles/' . $fileName;
            }
        }
    }
    
    // Handle gallery images upload
    if (isset($_FILES['gallery_images']) && !empty($_FILES['gallery_images']['name'][0])) {
        $uploadDir = '../uploads/articles/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $galleryPaths = [];
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
        
        foreach ($_FILES['gallery_images']['tmp_name'] as $key => $tmpName) {
            if ($_FILES['gallery_images']['error'][$key] === UPLOAD_ERR_OK) {
                $fileExtension = strtolower(pathinfo($_FILES['gallery_images']['name'][$key], PATHINFO_EXTENSION));
                
                if (in_array($fileExtension, $allowedExtensions)) {
                    $fileName = uniqid() . '.' . $fileExtension;
                    $targetPath = $uploadDir . $fileName;
                    
                    if (move_uploaded_file($tmpName, $targetPath)) {
                        $galleryPaths[] = 'uploads/articles/' . $fileName;
                    }
                }
            }
        }
        
        if (!empty($galleryPaths)) {
            $gallery_images = json_encode($galleryPaths);
        }
    }
    
    // Handle category-specific fields
    $category_fields_data = [];
    if ($category_id) {
        $categoryFields = $db->getCategoryFields($category_id);
        foreach ($categoryFields as $field) {
            $fieldName = 'category_field_' . $field['id'];
            if (isset($_POST[$fieldName])) {
                $category_fields_data[$field['id']] = $_POST[$fieldName];
            }
        }
    }
    
    // Check for duplicate slugs
    if ($action === 'edit' && $id) {
        if ($db->articleSlugExists($slug, $id)) {
            $message = 'Elemento già esistente: Un articolo con questo slug esiste già';
            $messageType = 'error';
        } else {
            $existingArticle = $db->getArticleById($id);
            if ($featured_image === null) {
                $featured_image = $existingArticle['featured_image'];
            }
            if ($gallery_images === null) {
                $gallery_images = $existingArticle['gallery_images'];
            }
            if ($db->updateArticle($id, $title, $slug, $content, $excerpt, $category_id, $province_id, $city_id, $status, $featured_image, $gallery_images, $seo_title, $seo_description, $seo_keywords)) {
                // Save category-specific data
                if (!empty($category_fields_data)) {
                    $db->saveArticleCategoryData($id, $category_id, $category_fields_data);
                }
                
                // Traduzione temporaneamente disabilitata
                $message = 'Articolo aggiornato con successo!';
                $messageType = 'success';
            } else {
                $message = 'Errore nell\'aggiornamento dell\'articolo';
                $messageType = 'error';
            }
        }
    } else {
        if ($db->articleSlugExists($slug)) {
            $message = 'Elemento già esistente: Un articolo con questo slug esiste già';
            $messageType = 'error';
        } else {
            $articleId = $db->createArticle($title, $slug, $content, $excerpt, $category_id, $province_id, $city_id, $status, $featured_image, $gallery_images, $author, $seo_title, $seo_description, $seo_keywords);
            if ($articleId) {
                // Save category-specific data
                if (!empty($category_fields_data)) {
                    $db->saveArticleCategoryData($articleId, $category_id, $category_fields_data);
                }
                
                // Traduzione temporaneamente disabilitata
                $message = 'Articolo creato con successo!';
                $messageType = 'success';
            } else {
                $message = 'Errore nella creazione dell\'articolo';
                $messageType = 'error';
            }
        }
    }
    
    if ($messageType === 'success') {
        header('Location: articoli.php');
        exit;
    }
}

if ($action === 'delete' && $id) {
    $db->deleteArticle($id);
    header('Location: articoli.php');
    exit;
}

?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestione Articoli - Admin Panel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="min-h-screen bg-gray-100 flex">
    <!-- Sidebar -->
    <div class="bg-gray-900 text-white w-64 flex flex-col">
        <div class="p-4 border-b border-gray-700">
            <div class="flex items-center space-x-3">
                <div class="w-8 h-8 bg-gradient-to-r from-blue-500 to-yellow-500 rounded-full flex items-center justify-center">
                    <span class="text-white font-bold text-sm">PC</span>
                </div>
                <div>
                    <h1 class="font-bold text-lg">Admin Panel</h1>
                    <p class="text-xs text-gray-400">Passione Calabria</p>
                </div>
            </div>
        </div>
        <nav class="flex-1 p-4 overflow-y-auto">
            <ul class="space-y-2">
                <li><a href="index.php" class="flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-gray-700 transition-colors"><i data-lucide="home" class="w-5 h-5"></i><span>Dashboard</span></a></li>
                <li><a href="gestione-home.php" class="flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-gray-700 transition-colors"><i data-lucide="layout" class="w-5 h-5"></i><span>Gestione Home</span></a></li>
                <li><a href="articoli.php" class="flex items-center space-x-3 px-3 py-2 rounded-lg bg-gray-700 text-white"><i data-lucide="file-text" class="w-5 h-5"></i><span>Articoli</span></a></li>
                <li><a href="categorie.php" class="flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-gray-700 transition-colors"><i data-lucide="tags" class="w-5 h-5"></i><span>Categorie</span></a></li>
                <li><a href="province.php" class="flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-gray-700 transition-colors"><i data-lucide="map-pin" class="w-5 h-5"></i><span>Province & Città</span></a></li>
                <li><a href="commenti.php" class="flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-gray-700 transition-colors"><i data-lucide="message-square" class="w-5 h-5"></i><span>Commenti</span></a></li>
                <li><a href="business.php" class="flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-gray-700 transition-colors"><i data-lucide="building-2" class="w-5 h-5"></i><span>Business</span></a></li>
                <li><a href="abbonamenti.php" class="flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-gray-700 transition-colors"><i data-lucide="credit-card" class="w-5 h-5"></i><span>Abbonamenti</span></a></li>
                <li><a href="utenti.php" class="flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-gray-700 transition-colors"><i data-lucide="users" class="w-5 h-5"></i><span>Utenti</span></a></li>
                <li><a href="database.php" class="flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-gray-700 transition-colors"><i data-lucide="database" class="w-5 h-5"></i><span>Monitoraggio DB</span></a></li>
                <li><a href="impostazioni.php" class="flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-gray-700 transition-colors"><i data-lucide="settings" class="w-5 h-5"></i><span>Impostazioni</span></a></li>
            </ul>
        </nav>
        <div class="p-4 border-t border-gray-700">
            <a href="../index.php" class="flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-gray-700 transition-colors"><i data-lucide="log-out" class="w-5 h-5"></i><span>Torna al Sito</span></a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="flex-1 flex flex-col overflow-hidden">
        <header class="bg-white shadow-sm border-b border-gray-200 px-6 py-4">
            <div class="flex justify-between items-center">
                <h1 class="text-2xl font-bold text-gray-900">Gestione Articoli</h1>
                <?php if ($action === 'list'): ?>
                <a href="articoli.php?action=new" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg">Nuovo Articolo</a>
                <?php endif; ?>
            </div>
        </header>
        <main class="flex-1 overflow-auto p-6">
            <?php if ($message): ?>
            <div class="mb-6 p-4 rounded-lg <?php echo $messageType === 'success' ? 'bg-green-100 text-green-700 border border-green-200' : 'bg-red-100 text-red-700 border border-red-200'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
            <?php endif; ?>
            
            <?php if ($action === 'list'): ?>
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h2 class="text-lg font-semibold mb-4">Elenco Articoli</h2>
                <table class="w-full">
                    <thead>
                        <tr class="border-b bg-gray-50">
                            <th class="text-left py-3 px-2 font-semibold text-gray-700">Articolo</th>
                            <th class="text-left py-3 px-2 font-semibold text-gray-700">Categoria</th>
                            <th class="text-left py-3 px-2 font-semibold text-gray-700">Stato</th>
                            <th class="text-left py-3 px-2 font-semibold text-gray-700">Azioni</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $articles = $db->getArticles(null, 0, false); // Get all articles
                        foreach ($articles as $article):
                        ?>
                        <tr class="border-b hover:bg-gray-50">
                            <td class="py-3 px-2">
                                <div class="flex items-center space-x-3">
                                    <?php if (!empty($article['featured_image'])): ?>
                                    <img src="../<?php echo htmlspecialchars($article['featured_image']); ?>" alt="<?php echo htmlspecialchars($article['title']); ?>" class="w-12 h-12 object-cover rounded-lg border">
                                    <?php else: ?>
                                    <div class="w-12 h-12 bg-gray-200 rounded-lg border flex items-center justify-center">
                                        <i data-lucide="image" class="w-5 h-5 text-gray-400"></i>
                                    </div>
                                    <?php endif; ?>
                                    <div>
                                        <div class="font-medium"><?php echo htmlspecialchars($article['title']); ?></div>
                                        <div class="text-sm text-gray-500">di <?php echo htmlspecialchars($article['author']); ?></div>
                                    </div>
                                </div>
                            </td>
                            <td class="py-3 px-2">
                                <span class="inline-block px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                                    <?php echo htmlspecialchars($article['category_name']); ?>
                                </span>
                            </td>
                            <td class="py-3 px-2">
                                <?php 
                                $statusColors = [
                                    'published' => 'bg-green-100 text-green-800',
                                    'draft' => 'bg-yellow-100 text-yellow-800',
                                    'archived' => 'bg-gray-100 text-gray-800'
                                ];
                                $statusClass = $statusColors[$article['status']] ?? 'bg-gray-100 text-gray-800';
                                ?>
                                <span class="inline-block px-2 py-1 text-xs font-semibold rounded-full <?php echo $statusClass; ?>">
                                    <?php echo ucfirst($article['status']); ?>
                                </span>
                            </td>
                            <td class="py-3 px-2">
                                <div class="flex space-x-2">
                                    <a href="articoli.php?action=edit&id=<?php echo $article['id']; ?>" 
                                       class="inline-flex items-center px-3 py-1 text-xs font-medium text-blue-600 bg-blue-100 rounded-lg hover:bg-blue-200 transition-colors">
                                        <i data-lucide="edit" class="w-3 h-3 mr-1"></i>
                                        Modifica
                                    </a>
                                    <a href="articoli.php?action=delete&id=<?php echo $article['id']; ?>" 
                                       class="inline-flex items-center px-3 py-1 text-xs font-medium text-red-600 bg-red-100 rounded-lg hover:bg-red-200 transition-colors"
                                       onclick="return confirm('Sei sicuro di voler eliminare questo articolo? Tutte le immagini associate verranno eliminate.');">    
                                        <i data-lucide="trash-2" class="w-3 h-3 mr-1"></i>
                                        Elimina
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php elseif ($action === 'new' || $action === 'edit'):
                $article = null;
                if ($action === 'edit' && $id) {
                    $article = $db->getArticleById($id);
                }
                $categories = $db->getCategories();
                $provinces = $db->getProvinces();
            ?>
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h2 class="text-lg font-semibold mb-4"><?php echo $action === 'edit' ? 'Modifica Articolo' : 'Nuovo Articolo'; ?></h2>
                <form action="articoli.php?action=<?php echo $action; ?><?php if ($id) echo '&id='.$id; ?>" method="POST" enctype="multipart/form-data" class="space-y-6">
                    
                    <!-- Basic Article Information -->
                    <div class="bg-white p-6 rounded-lg border border-gray-200">
                        <h3 class="text-lg font-semibold mb-4 text-gray-800 flex items-center">
                            <i data-lucide="file-text" class="w-5 h-5 mr-2 text-blue-600"></i>
                            Informazioni Articolo
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="title" class="block text-gray-700 font-semibold mb-2">Titolo *</label>
                                <input type="text" name="title" id="title" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" value="<?php echo htmlspecialchars($article['title'] ?? ''); ?>" required>
                            </div>
                            <div>
                                <label for="slug" class="block text-gray-700 font-semibold mb-2">Slug *</label>
                                <input type="text" name="slug" id="slug" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" value="<?php echo htmlspecialchars($article['slug'] ?? ''); ?>" required>
                            </div>
                        </div>
                        <div class="mt-4">
                            <label for="content" class="block text-gray-700 font-semibold mb-2">Contenuto</label>
                            <textarea name="content" id="content" rows="10" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"><?php echo htmlspecialchars($article['content'] ?? ''); ?></textarea>
                        </div>
                        <div class="mt-4">
                            <label for="excerpt" class="block text-gray-700 font-semibold mb-2">Estratto</label>
                            <textarea name="excerpt" id="excerpt" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"><?php echo htmlspecialchars($article['excerpt'] ?? ''); ?></textarea>
                        </div>
                    </div>
                    
                    <!-- Image Upload Section -->
                    <div class="bg-white p-6 rounded-lg border border-gray-200">
                        <h3 class="text-lg font-semibold mb-4 text-gray-800 flex items-center">
                            <i data-lucide="image" class="w-5 h-5 mr-2 text-blue-600"></i>
                            Gestione Immagini
                        </h3>
                        
                        <!-- Featured Image -->
                        <div class="mb-6">
                            <label for="featured_image" class="block text-gray-700 font-bold mb-2">
                                <i data-lucide="image" class="inline w-4 h-4 mr-2"></i>Immagine in evidenza
                            </label>
                            <div class="flex items-center space-x-4">
                                <input type="file" name="featured_image" id="featured_image" 
                                       class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100" 
                                       accept="image/jpeg,image/jpg,image/png,image/webp">
                                <?php if (isset($article) && !empty($article['featured_image'])): ?>
                                <div class="flex items-center text-sm text-gray-600">
                                    <i data-lucide="check-circle" class="w-4 h-4 text-green-600 mr-1"></i>
                                    <span>Immagine corrente: <?php echo basename($article['featured_image']); ?></span>
                                </div>
                                <?php endif; ?>
                            </div>
                            <p class="text-xs text-gray-500 mt-1">Formato: JPG, PNG, WebP. Massimo 5MB.</p>
                        </div>
                        
                        <!-- Gallery Images -->
                        <div class="mb-4">
                            <label for="gallery_images" class="block text-gray-700 font-bold mb-2">
                                <i data-lucide="images" class="inline w-4 h-4 mr-2"></i>Galleria immagini
                            </label>
                            <div class="flex items-center space-x-4">
                                <input type="file" name="gallery_images[]" id="gallery_images" 
                                       class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-green-50 file:text-green-700 hover:file:bg-green-100" 
                                       accept="image/jpeg,image/jpg,image/png,image/webp" multiple>
                                <?php 
                                if (isset($article) && !empty($article['gallery_images'])) {
                                    $galleryImages = json_decode($article['gallery_images'], true);
                                    if (is_array($galleryImages) && !empty($galleryImages)):
                                ?>
                                <div class="flex items-center text-sm text-gray-600">
                                    <i data-lucide="check-circle" class="w-4 h-4 text-green-600 mr-1"></i>
                                    <span><?php echo count($galleryImages); ?> immagini presenti</span>
                                </div>
                                <?php endif; } ?>
                            </div>
                            <p class="text-xs text-gray-500 mt-1">Formato: JPG, PNG, WebP. Massimo 5MB per file. Seleziona più file tenendo premuto Ctrl/Cmd.</p>
                        </div>
                        
                        <?php if (isset($article) && (!empty($article['featured_image']) || !empty($article['gallery_images']))): ?>
                        <!-- Current Images Preview -->
                        <div class="mt-4">
                            <h4 class="font-semibold text-gray-700 mb-3">🔍 Anteprima immagini correnti:</h4>
                            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                                <?php if (!empty($article['featured_image'])): ?>
                                <div class="relative group">
                                    <img src="../<?php echo htmlspecialchars($article['featured_image']); ?>" 
                                         alt="Immagine in evidenza" class="w-full h-24 object-cover rounded-lg border">
                                    <div class="absolute top-1 left-1 bg-blue-500 text-white text-xs px-2 py-1 rounded">Evidenza</div>
                                </div>
                                <?php endif; ?>
                                
                                <?php 
                                if (!empty($article['gallery_images'])) {
                                    $galleryImages = json_decode($article['gallery_images'], true);
                                    if (is_array($galleryImages)) {
                                        foreach ($galleryImages as $index => $imagePath):
                                ?>
                                <div class="relative group">
                                    <img src="../<?php echo htmlspecialchars($imagePath); ?>" 
                                         alt="Galleria <?php echo $index + 1; ?>" class="w-full h-24 object-cover rounded-lg border">
                                    <div class="absolute top-1 left-1 bg-green-500 text-white text-xs px-2 py-1 rounded">Galleria</div>
                                </div>
                                <?php endforeach; }} ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Article Configuration -->
                    <div class="bg-white p-6 rounded-lg border border-gray-200">
                        <h3 class="text-lg font-semibold mb-4 text-gray-800 flex items-center">
                            <i data-lucide="settings" class="w-5 h-5 mr-2 text-blue-600"></i>
                            Configurazione Articolo
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <label for="author" class="block text-gray-700 font-semibold mb-2">Autore</label>
                                <input type="text" name="author" id="author" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" value="<?php echo htmlspecialchars($article['author'] ?? 'Admin'); ?>">
                            </div>
                            <div>
                                <label for="status" class="block text-gray-700 font-semibold mb-2">Stato</label>
                                <select name="status" id="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    <option value="draft" <?php if (isset($article) && $article['status'] === 'draft') echo 'selected'; ?>>Bozza</option>
                                    <option value="published" <?php if (isset($article) && $article['status'] === 'published') echo 'selected'; ?>>Pubblicato</option>
                                    <option value="archived" <?php if (isset($article) && $article['status'] === 'archived') echo 'selected'; ?>>Archiviato</option>
                                </select>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label for="category_id" class="block text-gray-700 font-semibold mb-2">Categoria *</label>
                                <select name="category_id" id="category_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" onchange="loadCategoryFields()">
                                    <option value="">Seleziona categoria...</option>
                                    <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>" <?php if (isset($article) && $article['category_id'] == $category['id']) echo 'selected'; ?>>
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label for="province_id" class="block text-gray-700 font-semibold mb-2">Provincia</label>
                                <select name="province_id" id="province_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" onchange="loadCities()">
                                    <option value="">Nessuna</option>
                                    <?php foreach ($provinces as $province): ?>
                                    <option value="<?php echo $province['id']; ?>" <?php if (isset($article) && $article['province_id'] == $province['id']) echo 'selected'; ?>>
                                        <?php echo htmlspecialchars($province['name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label for="city_id" class="block text-gray-700 font-semibold mb-2">Città</label>
                                <select name="city_id" id="city_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    <option value="">Nessuna</option>
                                    <?php 
                                    if (isset($article) && $article['province_id']) {
                                        $cities = $db->getCitiesByProvince($article['province_id']);
                                        foreach ($cities as $city): ?>
                                    <option value="<?php echo $city['id']; ?>" <?php if (isset($article) && $article['city_id'] == $city['id']) echo 'selected'; ?>>
                                        <?php echo htmlspecialchars($city['name']); ?>
                                    </option>
                                    <?php endforeach; 
                                    } ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <!-- SEO Section -->
                    <div class="bg-white p-6 rounded-lg border border-gray-200">
                        <h3 class="text-lg font-semibold mb-4 text-gray-800 flex items-center">
                            <i data-lucide="search" class="w-5 h-5 mr-2 text-blue-600"></i>
                            Ottimizzazione SEO
                        </h3>
                        <p class="text-sm text-gray-600 mb-4">Ottimizza questo contenuto per i motori di ricerca e i social media</p>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <label for="seo_title" class="block text-gray-700 font-semibold mb-2">
                                    📊 Titolo SEO <span class="text-sm text-gray-500">(max 60 caratteri)</span>
                                </label>
                                <input type="text" name="seo_title" id="seo_title" 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                                       value="<?php echo htmlspecialchars($article['seo_title'] ?? ''); ?>" 
                                       maxlength="60" placeholder="Titolo ottimizzato per Google">
                                <div class="flex justify-between items-center mt-1">
                                    <span id="seo_title_counter" class="text-xs text-gray-500">0/60 caratteri</span>
                                    <span id="seo_title_status" class="text-xs"></span>
                                </div>
                            </div>
                            
                            <div>
                                <label for="seo_keywords" class="block text-gray-700 font-semibold mb-2">
                                    🏷️ Parole Chiave <span class="text-sm text-gray-500">(separate da virgola)</span>
                                </label>
                                <input type="text" name="seo_keywords" id="seo_keywords" 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                                       value="<?php echo htmlspecialchars($article['seo_keywords'] ?? ''); ?>" 
                                       placeholder="calabria, mare, turismo, borghi">
                                <p class="text-xs text-gray-500 mt-1">Separate le parole chiave con virgole</p>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <label for="seo_description" class="block text-gray-700 font-semibold mb-2">
                                📝 Meta Descrizione <span class="text-sm text-gray-500">(max 160 caratteri)</span>
                            </label>
                            <textarea name="seo_description" id="seo_description" rows="3"
                                      class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                                      maxlength="160" placeholder="Descrizione accattivante che apparirà nei risultati di ricerca"><?php echo htmlspecialchars($article['seo_description'] ?? ''); ?></textarea>
                            <div class="flex justify-between items-center mt-1">
                                <span id="seo_description_counter" class="text-xs text-gray-500">0/160 caratteri</span>
                                <span id="seo_description_status" class="text-xs"></span>
                            </div>
                        </div>
                        
                        <!-- SEO Preview -->
                        <div class="mt-6 p-4 bg-white rounded-lg border">
                            <h4 class="font-semibold text-gray-700 mb-3">🔍 Anteprima nei risultati di ricerca:</h4>
                            <div class="border-l-4 border-blue-500 pl-4">
                                <div id="seo_preview_title" class="text-lg text-blue-600 hover:underline cursor-pointer font-medium">
                                    <?php echo htmlspecialchars($article['seo_title'] ?? $article['title'] ?? 'Titolo articolo'); ?>
                                </div>
                                <div id="seo_preview_url" class="text-sm text-green-600 mt-1">
                                    <?php echo SITE_URL; ?>/articolo.php?slug=<span id="preview_slug"><?php echo htmlspecialchars($article['slug'] ?? 'slug-articolo'); ?></span>
                                </div>
                                <div id="seo_preview_description" class="text-sm text-gray-600 mt-2">
                                    <?php echo htmlspecialchars($article['seo_description'] ?? $article['excerpt'] ?? 'Descrizione dell\'articolo che apparirà nei risultati di ricerca...'); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Category-specific fields -->
                    <div id="category-fields-container" class="bg-white p-6 rounded-lg border border-gray-200" style="display: none;">
                        <h3 class="text-lg font-semibold mb-4 text-gray-800 flex items-center">
                            <i data-lucide="layers" class="w-5 h-5 mr-2 text-blue-600"></i>
                            Campi Specifici per Categoria
                        </h3>
                        <div id="category-fields" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- I campi verranno caricati dinamicamente via JavaScript -->
                        </div>
                    </div>
                    
                    <!-- Form Actions -->
                    <div class="bg-white p-6 rounded-lg border border-gray-200">
                        <div class="flex justify-between items-center">
                            <a href="articoli.php" class="inline-flex items-center px-4 py-2 text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors">
                                <i data-lucide="arrow-left" class="w-4 h-4 mr-2"></i>
                                Torna all'elenco
                            </a>
                            <button type="submit" class="inline-flex items-center px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition-colors">
                                <i data-lucide="save" class="w-4 h-4 mr-2"></i>
                                Salva Articolo
                            </button>
                        </div>
                    </div>
                </form>
            </div>
            <?php endif; ?>
        </main>
    </div>

    <script>
        lucide.createIcons();
        
        // Funzione per caricare le città in base alla provincia selezionata
        function loadCities() {
            const provinceSelect = document.getElementById('province_id');
            const citySelect = document.getElementById('city_id');
            const provinceId = provinceSelect.value;
            
            // Reset città
            citySelect.innerHTML = '<option value="">Nessuna</option>';
            
            if (provinceId === '') {
                return;
            }
            
            // Mostra loading
            citySelect.innerHTML = '<option value="">Caricamento...</option>';
            
            // Chiamata AJAX per caricare le città
            fetch(`api_cities.php?province_id=${provinceId}`)
                .then(response => response.json())
                .then(cities => {
                    // Reset opzioni
                    citySelect.innerHTML = '<option value="">Nessuna</option>';
                    
                    // Aggiungi le città
                    cities.forEach(city => {
                        const option = document.createElement('option');
                        option.value = city.id;
                        option.textContent = city.name;
                        citySelect.appendChild(option);
                    });
                })
                .catch(error => {
                    console.error('Errore nel caricamento delle città:', error);
                    citySelect.innerHTML = '<option value="">Errore caricamento</option>';
                });
        }
        
        // Funzione per caricare i campi specifici della categoria
        function loadCategoryFields() {
            const categorySelect = document.getElementById('category_id');
            const categoryId = categorySelect.value;
            const container = document.getElementById('category-fields-container');
            const fieldsContainer = document.getElementById('category-fields');
            
            if (!categoryId) {
                container.style.display = 'none';
                return;
            }
            
            // Mostra loading
            fieldsContainer.innerHTML = '<div class="col-span-full text-center py-4"><p class="text-gray-600">Caricamento campi...</p></div>';
            container.style.display = 'block';
            
            // Chiamata AJAX per caricare i campi della categoria
            fetch(`api_category_fields.php?category_id=${categoryId}`)
                .then(response => response.json())
                .then(data => {
                    fieldsContainer.innerHTML = '';
                    
                    if (!data.success || data.fields.length === 0) {
                        fieldsContainer.innerHTML = '<div class="col-span-full text-center py-4"><p class="text-gray-600">Nessun campo specifico per questa categoria</p></div>';
                        return;
                    }
                    
                    data.fields.forEach(field => {
                        const fieldDiv = document.createElement('div');
                        fieldDiv.className = 'mb-4';
                        
                        let fieldHtml = '';
                        const fieldName = `category_field_${field.id}`;
                        const isRequired = field.is_required ? 'required' : '';
                        const requiredStar = field.is_required ? '<span class="text-red-500">*</span>' : '';
                        
                        // Get existing value if editing
                        let existingValue = '';
                        <?php if (isset($article) && $article): ?>
                        const existingData = <?php 
                            if (isset($article) && $article) {
                                $existingData = $db->getArticleCategoryData($article['id']);
                                echo json_encode($existingData ?: []);
                            } else {
                                echo '[]';
                            }
                        ?>;
                        if (existingData[field.id]) {
                            existingValue = existingData[field.id];
                        }
                        <?php endif; ?>
                        
                        switch (field.field_type) {
                            case 'text':
                                fieldHtml = `
                                    <label for="${fieldName}" class="block text-gray-700 font-semibold mb-2">
                                        ${field.field_label} ${requiredStar}
                                    </label>
                                    <input type="text" name="${fieldName}" id="${fieldName}" 
                                           class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                                           value="${existingValue}" ${isRequired}>
                                `;
                                break;
                                
                            case 'email':
                                fieldHtml = `
                                    <label for="${fieldName}" class="block text-gray-700 font-semibold mb-2">
                                        ${field.field_label} ${requiredStar}
                                    </label>
                                    <input type="email" name="${fieldName}" id="${fieldName}" 
                                           class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                                           value="${existingValue}" ${isRequired} placeholder="esempio@email.com">
                                `;
                                break;
                                
                            case 'url':
                                fieldHtml = `
                                    <label for="${fieldName}" class="block text-gray-700 font-semibold mb-2">
                                        ${field.field_label} ${requiredStar}
                                    </label>
                                    <input type="url" name="${fieldName}" id="${fieldName}" 
                                           class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                                           value="${existingValue}" ${isRequired} placeholder="https://esempio.com">
                                `;
                                break;
                                
                            case 'number':
                                fieldHtml = `
                                    <label for="${fieldName}" class="block text-gray-700 font-semibold mb-2">
                                        ${field.field_label} ${requiredStar}
                                    </label>
                                    <input type="number" name="${fieldName}" id="${fieldName}" 
                                           class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                                           value="${existingValue}" ${isRequired} step="any">
                                `;
                                break;
                                
                            case 'datetime-local':
                                fieldHtml = `
                                    <label for="${fieldName}" class="block text-gray-700 font-semibold mb-2">
                                        ${field.field_label} ${requiredStar}
                                    </label>
                                    <input type="datetime-local" name="${fieldName}" id="${fieldName}" 
                                           class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                                           value="${existingValue}" ${isRequired}>
                                `;
                                break;
                                
                            case 'file':
                                const acceptTypes = field.field_options || 'image/*';
                                const currentFileDisplay = existingValue ? 
                                    `<div class="mt-2 text-sm text-gray-600">
                                        <i class="inline-block w-4 h-4 text-green-600 mr-1">✓</i>
                                        File corrente: ${existingValue.split('/').pop()}
                                     </div>` : '';
                                
                                fieldHtml = `
                                    <label for="${fieldName}" class="block text-gray-700 font-semibold mb-2">
                                        ${field.field_label} ${requiredStar}
                                    </label>
                                    <input type="file" name="${fieldName}" id="${fieldName}" 
                                           class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 
                                                  file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 
                                                  file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 
                                                  hover:file:bg-blue-100" 
                                           accept="${acceptTypes}" ${isRequired}>
                                    <p class="text-xs text-gray-500 mt-1">Formati supportati: ${acceptTypes}</p>
                                    ${currentFileDisplay}
                                `;
                                break;
                                
                            case 'textarea':
                                fieldHtml = `
                                    <label for="${fieldName}" class="block text-gray-700 font-semibold mb-2">
                                        ${field.field_label} ${requiredStar}
                                    </label>
                                    <textarea name="${fieldName}" id="${fieldName}" rows="3"
                                              class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                                              ${isRequired}>${existingValue}</textarea>
                                `;
                                break;
                                
                            case 'select':
                                let options = '';
                                if (field.field_options) {
                                    const optionsList = field.field_options.split(',').map(opt => opt.trim());
                                    optionsList.forEach(option => {
                                        const selected = existingValue === option ? 'selected' : '';
                                        options += `<option value="${option}" ${selected}>${option}</option>`;
                                    });
                                }
                                fieldHtml = `
                                    <label for="${fieldName}" class="block text-gray-700 font-semibold mb-2">
                                        ${field.field_label} ${requiredStar}
                                    </label>
                                    <select name="${fieldName}" id="${fieldName}" 
                                            class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                                            ${isRequired}>
                                        <option value="">Seleziona...</option>
                                        ${options}
                                    </select>
                                `;
                                break;
                                
                            case 'checkbox':
                                let checkboxOptions = '';
                                if (field.field_options) {
                                    const optionsList = field.field_options.split(',').map(opt => opt.trim());
                                    const selectedOptions = existingValue ? existingValue.split(',').map(opt => opt.trim()) : [];
                                    
                                    optionsList.forEach((option, index) => {
                                        const isChecked = selectedOptions.includes(option) ? 'checked' : '';
                                        const checkboxId = `${fieldName}_${index}`;
                                        checkboxOptions += `
                                            <label class="flex items-center space-x-2 mr-6 mb-2">
                                                <input type="checkbox" 
                                                       name="${fieldName}[]" 
                                                       id="${checkboxId}"
                                                       value="${option}" 
                                                       class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                                       ${isChecked}>
                                                <span class="text-gray-700 text-sm">${option}</span>
                                            </label>
                                        `;
                                    });
                                }
                                
                                fieldHtml = `
                                    <div>
                                        <label class="block text-gray-700 font-semibold mb-2">
                                            ${field.field_label} ${requiredStar}
                                        </label>
                                        <div class="flex flex-wrap">
                                            ${checkboxOptions}
                                        </div>
                                    </div>
                                `;
                                break;
                                
                            default:
                                fieldHtml = `
                                    <label for="${fieldName}" class="block text-gray-700 font-semibold mb-2">
                                        ${field.field_label} ${requiredStar}
                                    </label>
                                    <input type="text" name="${fieldName}" id="${fieldName}" 
                                           class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                                           value="${existingValue}" ${isRequired}>
                                `;
                        }
                        
                        fieldDiv.innerHTML = fieldHtml;
                        fieldsContainer.appendChild(fieldDiv);
                    });
                })
                .catch(error => {
                    console.error('Errore nel caricamento dei campi categoria:', error);
                    fieldsContainer.innerHTML = '<div class="col-span-full text-center py-4"><p class="text-red-600">Errore nel caricamento dei campi</p></div>';
                });
        }
        
        // Auto-genera slug dal titolo
        const titleField = document.getElementById('title');
        if (titleField) {
            titleField.addEventListener('input', function() {
                const title = this.value;
                const slugField = document.getElementById('slug');
                
                // Genera slug automatico solo se il campo slug è vuoto
                if (slugField && slugField.value === '') {
                    const slug = title
                        .toLowerCase()
                        .replace(/[àáâãäå]/g, 'a')
                        .replace(/[èéêë]/g, 'e')
                        .replace(/[ìíîï]/g, 'i')
                        .replace(/[òóôõö]/g, 'o')
                        .replace(/[ùúûü]/g, 'u')
                        .replace(/[ç]/g, 'c')
                        .replace(/[ñ]/g, 'n')
                        .replace(/[^a-z0-9\s-]/g, '')
                        .replace(/[\s_-]+/g, '-')
                        .replace(/^-+|-+$/g, '');
                    
                    slugField.value = slug;
                }
            });
        }
        
        // SEO Functions
        function initSEOFeatures() {
            const seoTitle = document.getElementById('seo_title');
            const seoDescription = document.getElementById('seo_description');
            const titleField = document.getElementById('title');
            const slugField = document.getElementById('slug');
            const excerptField = document.getElementById('excerpt');
            
            // Exit early if SEO elements don't exist (we're not on form page)
            if (!seoTitle || !seoDescription || !titleField) {
                return;
            }
            
            // Character counters and validation
            function updateCharacterCounter(field, counterId, statusId, maxLength) {
                const counter = document.getElementById(counterId);
                const status = document.getElementById(statusId);
                const length = field.value.length;
                
                counter.textContent = `${length}/${maxLength} caratteri`;
                
                if (length === 0) {
                    status.textContent = '';
                    status.className = 'text-xs';
                } else if (length < maxLength * 0.3) {
                    status.textContent = 'Troppo corto';
                    status.className = 'text-xs text-orange-600';
                } else if (length > maxLength * 0.9) {
                    status.textContent = length === maxLength ? 'Limite raggiunto' : 'Quasi al limite';
                    status.className = 'text-xs text-red-600';
                } else {
                    status.textContent = 'Lunghezza ottimale';
                    status.className = 'text-xs text-green-600';
                }
            }
            
            // Update SEO preview
            function updateSEOPreview() {
                const previewTitle = document.getElementById('seo_preview_title');
                const previewUrl = document.getElementById('preview_slug');
                const previewDescription = document.getElementById('seo_preview_description');
                
                // Update title
                const displayTitle = seoTitle.value || titleField.value || 'Titolo articolo';
                previewTitle.textContent = displayTitle;
                
                // Update URL
                const displaySlug = slugField.value || 'slug-articolo';
                previewUrl.textContent = displaySlug;
                
                // Update description
                const displayDescription = seoDescription.value || excerptField.value || 'Descrizione dell\'articolo che apparirà nei risultati di ricerca...';
                previewDescription.textContent = displayDescription;
            }
            
            // Event listeners for SEO title
            if (seoTitle) {
                seoTitle.addEventListener('input', function() {
                    updateCharacterCounter(this, 'seo_title_counter', 'seo_title_status', 60);
                    updateSEOPreview();
                });
                // Initialize counter
                updateCharacterCounter(seoTitle, 'seo_title_counter', 'seo_title_status', 60);
            }
            
            // Event listeners for SEO description
            if (seoDescription) {
                seoDescription.addEventListener('input', function() {
                    updateCharacterCounter(this, 'seo_description_counter', 'seo_description_status', 160);
                    updateSEOPreview();
                });
                // Initialize counter
                updateCharacterCounter(seoDescription, 'seo_description_counter', 'seo_description_status', 160);
            }
            
            // Update preview when main fields change
            if (titleField) {
                titleField.addEventListener('input', updateSEOPreview);
            }
            if (slugField) {
                slugField.addEventListener('input', updateSEOPreview);
            }
            if (excerptField) {
                excerptField.addEventListener('input', updateSEOPreview);
            }
            
            // Initialize preview
            updateSEOPreview();
            
            // Auto-populate SEO fields if empty
            function autoPopulateSEO() {
                if (!seoTitle.value && titleField.value) {
                    seoTitle.value = titleField.value.substring(0, 60);
                    updateCharacterCounter(seoTitle, 'seo_title_counter', 'seo_title_status', 60);
                }
                
                if (!seoDescription.value && excerptField.value) {
                    seoDescription.value = excerptField.value.substring(0, 160);
                    updateCharacterCounter(seoDescription, 'seo_description_counter', 'seo_description_status', 160);
                }
                
                updateSEOPreview();
            }
            
            // Auto-populate button (hidden, triggered by focus events)
            if (titleField) {
                titleField.addEventListener('blur', function() {
                    if (this.value && !seoTitle.value) {
                        setTimeout(autoPopulateSEO, 100);
                    }
                });
            }
            
            if (excerptField) {
                excerptField.addEventListener('blur', function() {
                    if (this.value && !seoDescription.value) {
                        setTimeout(autoPopulateSEO, 100);
                    }
                });
            }
        }
        
        // Carica i campi categoria se stiamo modificando un articolo
        document.addEventListener('DOMContentLoaded', function() {
            const categorySelect = document.getElementById('category_id');
            if (categorySelect && categorySelect.value) {
                loadCategoryFields();
            }
            
            // Initialize SEO features
            initSEOFeatures();
        });
    </script>
</body>
</html>
