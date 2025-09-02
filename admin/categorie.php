<?php
require_once '../includes/config.php';
require_once '../includes/database.php';

$db = new Database();
$message = '';
$messageType = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $name = $_POST['name'] ?? '';
        $description = $_POST['description'] ?? '';
        $icon = $_POST['icon'] ?? '';
        $iconImage = null;
        
        // Handle file upload
        if (isset($_FILES['icon_image']) && $_FILES['icon_image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = '../uploads/categories/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $fileName = time() . '_' . basename($_FILES['icon_image']['name']);
            $uploadPath = $uploadDir . $fileName;
            
            if (move_uploaded_file($_FILES['icon_image']['tmp_name'], $uploadPath)) {
                $iconImage = 'uploads/categories/' . $fileName;
            }
        }
        
        switch ($_POST['action']) {
            case 'create':
                // Verifica se la categoria esiste già
                if ($db->categoryExists($name)) {
                    $message = 'Elemento già esistente: Una categoria con questo nome esiste già';
                    $messageType = 'error';
                } else {
                    $result = $db->pdo->prepare("INSERT INTO categories (name, description, icon, icon_image, created_at) VALUES (?, ?, ?, ?, datetime('now'))");
                    $success = $result->execute([$name, $description, $icon, $iconImage]);
                    $message = $success ? 'Categoria creata con successo!' : 'Errore nella creazione della categoria';
                    $messageType = $success ? 'success' : 'error';
                }
                break;
                
            case 'update':
                // Verifica se esiste già una categoria con lo stesso nome (escludendo quella corrente)
                if ($db->categoryExists($name, $_POST['id'])) {
                    $message = 'Elemento già esistente: Una categoria con questo nome esiste già';
                    $messageType = 'error';
                } else {
                    if ($iconImage) {
                        $result = $db->pdo->prepare("UPDATE categories SET name = ?, description = ?, icon = ?, icon_image = ? WHERE id = ?");
                        $success = $result->execute([$name, $description, $icon, $iconImage, $_POST['id']]);
                    } else {
                        $result = $db->pdo->prepare("UPDATE categories SET name = ?, description = ?, icon = ? WHERE id = ?");
                        $success = $result->execute([$name, $description, $icon, $_POST['id']]);
                    }
                    $message = $success ? 'Categoria aggiornata con successo!' : 'Errore nell\'aggiornamento della categoria';
                    $messageType = $success ? 'success' : 'error';
                }
                break;
                
            case 'delete':
                // Check if category has associated articles
                $stmt = $db->pdo->prepare("SELECT COUNT(*) as count FROM articles WHERE category_id = ?");
                $stmt->execute([$_POST['id']]);
                $count = $stmt->fetch()['count'];
                
                if ($count > 0) {
                    $message = 'Impossibile eliminare la categoria: ci sono ' . $count . ' articoli associati';
                    $messageType = 'error';
                } else {
                    $result = $db->pdo->prepare("DELETE FROM categories WHERE id = ?");
                    $success = $result->execute([$_POST['id']]);
                    $message = $success ? 'Categoria eliminata con successo!' : 'Errore nell\'eliminazione della categoria';
                    $messageType = $success ? 'success' : 'error';
                }
                break;
        }
    }
}

// Get action and ID from URL
$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? null;

// Load specific category for editing
$currentCategory = null;
if ($action === 'edit' && $id) {
    $stmt = $db->pdo->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->execute([$id]);
    $currentCategory = $stmt->fetch();
}

// Get all categories with article counts
$categories = $db->pdo->query("
    SELECT c.*, 
           COUNT(a.id) as article_count 
    FROM categories c 
    LEFT JOIN articles a ON c.id = a.category_id 
    GROUP BY c.id 
    ORDER BY c.name
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestione Categorie - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="min-h-screen bg-gray-100 flex">
    <!-- Sidebar -->
    <div class="bg-gray-900 text-white w-64 flex flex-col">
        <!-- Header -->
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

        <!-- Navigation -->
        <nav class="flex-1 p-4 overflow-y-auto">
            <ul class="space-y-2">
                <li>
                    <a href="index.php" class="flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-gray-700 transition-colors">
                        <i data-lucide="home" class="w-5 h-5"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="gestione-home.php" class="flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-gray-700 transition-colors">
                        <i data-lucide="layout" class="w-5 h-5"></i>
                        <span>Gestione Home</span>
                    </a>
                </li>
                <li>
                    <a href="articoli.php" class="flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-gray-700 transition-colors">
                        <i data-lucide="file-text" class="w-5 h-5"></i>
                        <span>Articoli</span>
                    </a>
                </li>
                <li>
                    <a href="categorie.php" class="flex items-center space-x-3 px-3 py-2 rounded-lg bg-gray-700 text-white">
                        <i data-lucide="tags" class="w-5 h-5"></i>
                        <span>Categorie</span>
                    </a>
                </li>
                <li>
                    <a href="pagine-statiche.php" class="flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-gray-700 transition-colors">
                        <i data-lucide="file-text" class="w-5 h-5"></i>
                        <span>Pagine Statiche</span>
                    </a>
                </li>
                <li>
                    <a href="province.php" class="flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-gray-700 transition-colors">
                        <i data-lucide="map-pin" class="w-5 h-5"></i>
                        <span>Province & Città</span>
                    </a>
                </li>
                <li>
                    <a href="commenti.php" class="flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-gray-700 transition-colors">
                        <i data-lucide="message-square" class="w-5 h-5"></i>
                        <span>Commenti</span>
                    </a>
                </li>
                <li>
                    <a href="business.php" class="flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-gray-700 transition-colors">
                        <i data-lucide="building-2" class="w-5 h-5"></i>
                        <span>Business</span>
                    </a>
                </li>
                <li>
                    <a href="abbonamenti.php" class="flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-gray-700 transition-colors">
                        <i data-lucide="credit-card" class="w-5 h-5"></i>
                        <span>Abbonamenti</span>
                    </a>
                </li>
                <li>
                    <a href="utenti.php" class="flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-gray-700 transition-colors">
                        <i data-lucide="users" class="w-5 h-5"></i>
                        <span>Utenti</span>
                    </a>
                </li>
                <li>
                    <a href="gestione-traduzione.php" class="flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-gray-700 transition-colors">
                        <i data-lucide="globe" class="w-5 h-5"></i>
                        <span>Gestione Traduzione</span>
                    </a>
                </li>
                <li>
                    <a href="database.php" class="flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-gray-700 transition-colors">
                        <i data-lucide="database" class="w-5 h-5"></i>
                        <span>Monitoraggio DB</span>
                    </a>
                </li>
                <li>
                    <a href="impostazioni.php" class="flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-gray-700 transition-colors">
                        <i data-lucide="settings" class="w-5 h-5"></i>
                        <span>Impostazioni</span>
                    </a>
                </li>
            </ul>
        </nav>

        <!-- Footer -->
        <div class="p-4 border-t border-gray-700">
            <a href="../index.php" class="flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-gray-700 transition-colors">
                <i data-lucide="log-out" class="w-5 h-5"></i>
                <span>Torna al Sito</span>
            </a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="flex-1 flex flex-col overflow-hidden">
        <!-- Top Bar -->
        <header class="bg-white shadow-sm border-b border-gray-200 px-6 py-4">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Gestione Categorie</h1>
                    <p class="text-sm text-gray-500">Gestisci le categorie degli articoli</p>
                </div>
                <div class="flex items-center space-x-4">
                    <?php if ($action === 'list'): ?>
                    <a href="?action=new" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors flex items-center">
                        <i data-lucide="plus" class="w-4 h-4 mr-2"></i>
                        Nuova Categoria
                    </a>
                    <?php else: ?>
                    <a href="categorie.php" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors flex items-center">
                        <i data-lucide="arrow-left" class="w-4 h-4 mr-2"></i>
                        Torna alla Lista
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </header>

        <!-- Page Content -->
        <main class="flex-1 overflow-auto p-6">
            <?php if ($message): ?>
            <div class="mb-6 p-4 rounded-lg <?php echo $messageType === 'success' ? 'bg-green-100 text-green-700 border border-green-200' : 'bg-red-100 text-red-700 border border-red-200'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
            <?php endif; ?>

            <?php if ($action === 'list'): ?>
            <!-- List View -->
            <div class="bg-white rounded-lg shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="text-left py-3 px-6 font-medium text-gray-700">Categoria</th>
                                <th class="text-left py-3 px-6 font-medium text-gray-700">Descrizione</th>
                                <th class="text-left py-3 px-6 font-medium text-gray-700">Articoli</th>
                                <th class="text-left py-3 px-6 font-medium text-gray-700">Creata</th>
                                <th class="text-left py-3 px-6 font-medium text-gray-700">Azioni</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php foreach ($categories as $category): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="py-4 px-6">
                                    <div class="flex items-center space-x-3">
                                        <?php if ($category['icon_image']): ?>
                                            <img src="../<?php echo htmlspecialchars($category['icon_image']); ?>" 
                                                 alt="<?php echo htmlspecialchars($category['name']); ?>"
                                                 class="w-8 h-8 object-cover rounded">
                                        <?php elseif ($category['icon']): ?>
                                            <span class="text-2xl"><?php echo htmlspecialchars($category['icon']); ?></span>
                                        <?php else: ?>
                                            <div class="w-8 h-8 bg-gray-200 rounded flex items-center justify-center">
                                                <i data-lucide="folder" class="w-4 h-4 text-gray-400"></i>
                                            </div>
                                        <?php endif; ?>
                                        <div>
                                            <div class="font-medium text-gray-900"><?php echo htmlspecialchars($category['name']); ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-4 px-6">
                                    <div class="text-sm text-gray-600">
                                        <?php echo htmlspecialchars(substr($category['description'], 0, 60)) . (strlen($category['description']) > 60 ? '...' : ''); ?>
                                    </div>
                                </td>
                                <td class="py-4 px-6">
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                        <?php echo $category['article_count']; ?> articoli
                                    </span>
                                </td>
                                <td class="py-4 px-6 text-sm text-gray-600">
                                    <?php echo formatDateTime($category['created_at']); ?>
                                </td>
                                <td class="py-4 px-6">
                                    <div class="flex items-center space-x-2">
                                        <a href="?action=edit&id=<?php echo $category['id']; ?>" 
                                           class="text-gray-600 hover:text-gray-700" title="Modifica">
                                            <i data-lucide="edit" class="w-4 h-4"></i>
                                        </a>
                                        <button onclick="deleteCategory(<?php echo $category['id']; ?>, '<?php echo htmlspecialchars($category['name'], ENT_QUOTES); ?>', <?php echo $category['article_count']; ?>)"
                                                class="text-red-600 hover:text-red-700" title="Elimina">
                                            <i data-lucide="trash" class="w-4 h-4"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <?php elseif ($action === 'new' || $action === 'edit'): ?>
            <!-- Form View -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <form method="POST" enctype="multipart/form-data" class="space-y-6">
                    <input type="hidden" name="action" value="<?php echo $action === 'edit' ? 'update' : 'create'; ?>">
                    <?php if ($action === 'edit'): ?>
                    <input type="hidden" name="id" value="<?php echo $currentCategory['id']; ?>">
                    <?php endif; ?>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Nome Categoria</label>
                            <input type="text" name="name" required 
                                   value="<?php echo htmlspecialchars($currentCategory['name'] ?? ''); ?>"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Icona Emoji</label>
                            <input type="text" name="icon" 
                                   value="<?php echo htmlspecialchars($currentCategory['icon'] ?? ''); ?>"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                                   placeholder="🌿">
                            <p class="text-xs text-gray-500 mt-1">Emoji che rappresenta la categoria</p>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Descrizione</label>
                        <textarea name="description" rows="3" 
                                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                                  placeholder="Descrizione della categoria..."><?php echo htmlspecialchars($currentCategory['description'] ?? ''); ?></textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Immagine Categoria</label>
                        <div class="flex items-center space-x-4">
                            <?php if ($action === 'edit' && $currentCategory['icon_image']): ?>
                            <div class="flex-shrink-0">
                                <img src="../<?php echo htmlspecialchars($currentCategory['icon_image']); ?>" 
                                     alt="Current image" 
                                     class="w-16 h-16 object-cover rounded-lg border">
                            </div>
                            <?php endif; ?>
                            <div class="flex-1">
                                <input type="file" name="icon_image" accept="image/*"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                                <p class="text-xs text-gray-500 mt-1">
                                    Formati supportati: JPG, PNG, GIF. Max 2MB.
                                    <?php if ($action === 'edit'): ?>Lascia vuoto per mantenere l'immagine attuale.<?php endif; ?>
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-between pt-4">
                        <a href="categorie.php" class="text-gray-600 hover:text-gray-700">
                            Annulla
                        </a>
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg font-medium transition-colors">
                            <?php echo $action === 'edit' ? 'Aggiorna Categoria' : 'Crea Categoria'; ?>
                        </button>
                    </div>
                </form>
            </div>
            <?php endif; ?>
        </main>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Conferma Eliminazione</h3>
            <p class="text-gray-600 mb-2">Sei sicuro di voler eliminare la categoria "<span id="categoryName"></span>"?</p>
            <p id="articleWarning" class="text-red-600 text-sm mb-6 hidden">
                <i data-lucide="alert-triangle" class="w-4 h-4 inline mr-1"></i>
                Questa categoria ha <span id="articleCount"></span> articoli associati e non può essere eliminata.
            </p>
            <div class="flex justify-end space-x-4">
                <button onclick="closeDeleteModal()" class="px-4 py-2 text-gray-600 hover:text-gray-700">
                    Annulla
                </button>
                <form method="POST" style="display: inline;" id="deleteForm">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" id="deleteCategoryId">
                    <button type="submit" id="confirmDeleteBtn" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg">
                        Elimina
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script src="../assets/js/main.js"></script>
    <script>
        lucide.createIcons();

        function deleteCategory(id, name, articleCount) {
            document.getElementById('deleteCategoryId').value = id;
            document.getElementById('categoryName').textContent = name;
            document.getElementById('articleCount').textContent = articleCount;
            
            const warning = document.getElementById('articleWarning');
            const deleteBtn = document.getElementById('confirmDeleteBtn');
            const deleteForm = document.getElementById('deleteForm');
            
            if (articleCount > 0) {
                warning.classList.remove('hidden');
                deleteBtn.style.display = 'none';
            } else {
                warning.classList.add('hidden');
                deleteBtn.style.display = 'block';
            }
            
            document.getElementById('deleteModal').classList.remove('hidden');
            document.getElementById('deleteModal').classList.add('flex');
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.add('hidden');
            document.getElementById('deleteModal').classList.remove('flex');
        }

        // Close modal on outside click
        document.getElementById('deleteModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeDeleteModal();
            }
        });
    </script>
</body>
</html>