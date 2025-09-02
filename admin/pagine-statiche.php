<?php
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/PreventiveTranslationService_DeepL.php';

$db = new Database();
$translationService = new PreventiveTranslationService($db);
$message = '';
$messageType = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create':
                $result = $db->pdo->prepare("INSERT INTO static_pages (slug, title, content, meta_title, meta_description, is_published, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, datetime('now'), datetime('now'))");
                $success = $result->execute([
                    $_POST['slug'],
                    $_POST['title'],
                    $_POST['content'],
                    $_POST['meta_title'],
                    $_POST['meta_description'],
                    isset($_POST['is_published']) ? 1 : 0
                ]);
                
                if ($success) {
                    $pageId = $db->pdo->lastInsertId();
                    
                    // Traduzione temporaneamente disabilitata
                    $message = 'Pagina creata con successo!';
                    $messageType = 'success';
                } else {
                    $message = 'Errore nella creazione della pagina';
                    $messageType = 'error';
                }
                break;
                
            case 'update':
                $result = $db->pdo->prepare("UPDATE static_pages SET slug = ?, title = ?, content = ?, meta_title = ?, meta_description = ?, is_published = ?, updated_at = datetime('now') WHERE id = ?");
                $success = $result->execute([
                    $_POST['slug'],
                    $_POST['title'],
                    $_POST['content'],
                    $_POST['meta_title'],
                    $_POST['meta_description'],
                    isset($_POST['is_published']) ? 1 : 0,
                    $_POST['id']
                ]);
                
                if ($success) {
                    // Traduzione temporaneamente disabilitata
                    $message = 'Pagina aggiornata con successo!';
                    $messageType = 'success';
                } else {
                    $message = 'Errore nell\'aggiornamento della pagina';
                    $messageType = 'error';
                }
                break;
                
            case 'delete':
                $result = $db->pdo->prepare("DELETE FROM static_pages WHERE id = ?");
                $success = $result->execute([$_POST['id']]);
                $message = $success ? 'Pagina eliminata con successo!' : 'Errore nell\'eliminazione della pagina';
                $messageType = $success ? 'success' : 'error';
                break;
        }
    }
}

// Get action and ID from URL
$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? null;

// Load specific page for editing
$currentPage = null;
if ($action === 'edit' && $id) {
    $stmt = $db->pdo->prepare("SELECT * FROM static_pages WHERE id = ?");
    $stmt->execute([$id]);
    $currentPage = $stmt->fetch();
}

// Get all static pages
$pages = $db->pdo->query("SELECT * FROM static_pages ORDER BY title")->fetchAll();
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestione Pagine Statiche - Admin</title>
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
                    <a href="categorie.php" class="flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-gray-700 transition-colors">
                        <i data-lucide="tags" class="w-5 h-5"></i>
                        <span>Categorie</span>
                    </a>
                </li>
                <li>
                    <a href="pagine-statiche.php" class="flex items-center space-x-3 px-3 py-2 rounded-lg bg-gray-700 text-white">
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
                    <h1 class="text-2xl font-bold text-gray-900">Gestione Pagine Statiche</h1>
                    <p class="text-sm text-gray-500">Gestisci le pagine statiche del sito</p>
                </div>
                <div class="flex items-center space-x-4">
                    <?php if ($action === 'list'): ?>
                    <a href="?action=new" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors flex items-center">
                        <i data-lucide="plus" class="w-4 h-4 mr-2"></i>
                        Nuova Pagina
                    </a>
                    <?php else: ?>
                    <a href="pagine-statiche.php" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors flex items-center">
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
                                <th class="text-left py-3 px-6 font-medium text-gray-700">Titolo</th>
                                <th class="text-left py-3 px-6 font-medium text-gray-700">Slug</th>
                                <th class="text-left py-3 px-6 font-medium text-gray-700">Stato</th>
                                <th class="text-left py-3 px-6 font-medium text-gray-700">Ultimo Aggiornamento</th>
                                <th class="text-left py-3 px-6 font-medium text-gray-700">Azioni</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php foreach ($pages as $page): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="py-4 px-6">
                                    <div class="font-medium text-gray-900"><?php echo htmlspecialchars($page['title']); ?></div>
                                    <div class="text-sm text-gray-500"><?php echo htmlspecialchars(substr($page['content'], 0, 100)) . '...'; ?></div>
                                </td>
                                <td class="py-4 px-6">
                                    <code class="text-sm bg-gray-100 px-2 py-1 rounded"><?php echo htmlspecialchars($page['slug']); ?></code>
                                </td>
                                <td class="py-4 px-6">
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium <?php echo $page['is_published'] ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'; ?>">
                                        <?php echo $page['is_published'] ? 'Pubblicata' : 'Bozza'; ?>
                                    </span>
                                </td>
                                <td class="py-4 px-6 text-sm text-gray-600">
                                    <?php echo formatDateTime($page['updated_at']); ?>
                                </td>
                                <td class="py-4 px-6">
                                    <div class="flex items-center space-x-2">
                                        <a href="../<?php echo htmlspecialchars($page['slug']); ?>.php" 
                                           class="text-blue-600 hover:text-blue-700" title="Visualizza" target="_blank">
                                            <i data-lucide="external-link" class="w-4 h-4"></i>
                                        </a>
                                        <a href="?action=edit&id=<?php echo $page['id']; ?>" 
                                           class="text-gray-600 hover:text-gray-700" title="Modifica">
                                            <i data-lucide="edit" class="w-4 h-4"></i>
                                        </a>
                                        <button onclick="deletePage(<?php echo $page['id']; ?>, '<?php echo htmlspecialchars($page['title'], ENT_QUOTES); ?>')"
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
                <form method="POST" class="space-y-6">
                    <input type="hidden" name="action" value="<?php echo $action === 'edit' ? 'update' : 'create'; ?>">
                    <?php if ($action === 'edit'): ?>
                    <input type="hidden" name="id" value="<?php echo $currentPage['id']; ?>">
                    <?php endif; ?>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Titolo</label>
                            <input type="text" name="title" required 
                                   value="<?php echo htmlspecialchars($currentPage['title'] ?? ''); ?>"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Slug URL</label>
                            <input type="text" name="slug" required 
                                   value="<?php echo htmlspecialchars($currentPage['slug'] ?? ''); ?>"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                                   placeholder="es: chi-siamo">
                            <p class="text-xs text-gray-500 mt-1">URL della pagina (senza .php)</p>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Contenuto</label>
                        <textarea name="content" rows="12" required
                                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                                  placeholder="Inserisci il contenuto HTML della pagina..."><?php echo htmlspecialchars($currentPage['content'] ?? ''); ?></textarea>
                        <p class="text-xs text-gray-500 mt-1">Puoi utilizzare HTML per formattare il contenuto</p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Meta Title</label>
                            <input type="text" name="meta_title"
                                   value="<?php echo htmlspecialchars($currentPage['meta_title'] ?? ''); ?>"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                                   placeholder="Titolo per SEO">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Meta Description</label>
                            <input type="text" name="meta_description"
                                   value="<?php echo htmlspecialchars($currentPage['meta_description'] ?? ''); ?>"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                                   placeholder="Descrizione per SEO">
                        </div>
                    </div>

                    <div>
                        <label class="flex items-center">
                            <input type="checkbox" name="is_published" <?php echo ($currentPage['is_published'] ?? 1) ? 'checked' : ''; ?>
                                   class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                            <span class="ml-2 text-sm text-gray-700">Pubblicata</span>
                        </label>
                    </div>

                    <div class="flex items-center justify-between pt-4">
                        <a href="pagine-statiche.php" class="text-gray-600 hover:text-gray-700">
                            Annulla
                        </a>
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg font-medium transition-colors">
                            <?php echo $action === 'edit' ? 'Aggiorna Pagina' : 'Crea Pagina'; ?>
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
            <p class="text-gray-600 mb-6">Sei sicuro di voler eliminare la pagina "<span id="pageTitle"></span>"?</p>
            <div class="flex justify-end space-x-4">
                <button onclick="closeDeleteModal()" class="px-4 py-2 text-gray-600 hover:text-gray-700">
                    Annulla
                </button>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" id="deletePageId">
                    <button type="submit" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg">
                        Elimina
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script src="../assets/js/main.js"></script>
    <script>
        lucide.createIcons();

        function deletePage(id, title) {
            document.getElementById('deletePageId').value = id;
            document.getElementById('pageTitle').textContent = title;
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