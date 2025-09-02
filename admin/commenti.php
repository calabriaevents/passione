<?php
require_once '../includes/config.php';
require_once '../includes/database.php';

// Controlla autenticazione (da implementare)
// // requireLogin(); // DISABILITATO

$db = new Database();

$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? null;
$status = $_GET['status'] ?? null;

// Gestione delle azioni
if ($action === 'approve' && $id) {
    $db->updateCommentStatus($id, 'approved');
    header('Location: commenti.php');
    exit;
}

if ($action === 'reject' && $id) {
    $db->updateCommentStatus($id, 'rejected');
    header('Location: commenti.php');
    exit;
}

if ($action === 'delete' && $id) {
    $db->deleteComment($id);
    header('Location: commenti.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'edit' && $id) {
    $content = $_POST['content'] ?? '';
    $db->updateCommentContent($id, $content);
    header('Location: commenti.php');
    exit;
}

?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestione Commenti - Admin Panel</title>
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
                <li><a href="articoli.php" class="flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-gray-700 transition-colors"><i data-lucide="file-text" class="w-5 h-5"></i><span>Articoli</span></a></li>
                <li><a href="categorie.php" class="flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-gray-700 transition-colors"><i data-lucide="tags" class="w-5 h-5"></i><span>Categorie</span></a></li>
                <li><a href="pagine-statiche.php" class="flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-gray-700 transition-colors"><i data-lucide="file-text" class="w-5 h-5"></i><span>Pagine Statiche</span></a></li>
                <li><a href="province.php" class="flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-gray-700 transition-colors"><i data-lucide="map-pin" class="w-5 h-5"></i><span>Province & Città</span></a></li>
                <li><a href="commenti.php" class="flex items-center space-x-3 px-3 py-2 rounded-lg bg-gray-700 text-white"><i data-lucide="message-square" class="w-5 h-5"></i><span>Commenti</span></a></li>
                <li><a href="business.php" class="flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-gray-700 transition-colors"><i data-lucide="building-2" class="w-5 h-5"></i><span>Business</span></a></li>
                <li><a href="abbonamenti.php" class="flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-gray-700 transition-colors"><i data-lucide="credit-card" class="w-5 h-5"></i><span>Abbonamenti</span></a></li>
                <li><a href="utenti.php" class="flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-gray-700 transition-colors"><i data-lucide="users" class="w-5 h-5"></i><span>Utenti</span></a></li>
                <li><a href="gestione-traduzione.php" class="flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-gray-700 transition-colors"><i data-lucide="globe" class="w-5 h-5"></i><span>Gestione Traduzione</span></a></li>
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
            <h1 class="text-2xl font-bold text-gray-900">Gestione Commenti</h1>
        </header>
        <main class="flex-1 overflow-auto p-6">
            <?php if ($action === 'list'): ?>
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h2 class="text-lg font-semibold mb-4">Elenco Commenti</h2>
                <div class="mb-4 border-b border-gray-200">
                    <nav class="flex space-x-4">
                        <a href="?status=" class="py-2 px-4 <?php if (!$status) echo 'border-b-2 border-blue-600 font-semibold'; ?>">Tutti</a>
                        <a href="?status=pending" class="py-2 px-4 <?php if ($status === 'pending') echo 'border-b-2 border-blue-600 font-semibold'; ?>">In attesa</a>
                        <a href="?status=approved" class="py-2 px-4 <?php if ($status === 'approved') echo 'border-b-2 border-blue-600 font-semibold'; ?>">Approvati</a>
                        <a href="?status=rejected" class="py-2 px-4 <?php if ($status === 'rejected') echo 'border-b-2 border-blue-600 font-semibold'; ?>">Rifiutati</a>
                    </nav>
                </div>
                <table class="w-full">
                    <thead>
                        <tr class="border-b">
                            <th class="text-left py-2">Autore</th>
                            <th class="text-left py-2">Commento</th>
                            <th class="text-left py-2">Articolo</th>
                            <th class="text-left py-2">Stato</th>
                            <th class="text-left py-2">Azioni</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $comments = $db->getComments($status);
                        foreach ($comments as $comment):
                        ?>
                        <tr class="border-b">
                            <td class="py-2"><?php echo htmlspecialchars($comment['author_name']); ?></td>
                            <td class="py-2"><?php echo htmlspecialchars(substr($comment['content'], 0, 50)); ?>...</td>
                            <td class="py-2"><?php echo htmlspecialchars($comment['article_title']); ?></td>
                            <td class="py-2"><?php echo htmlspecialchars($comment['status']); ?></td>
                            <td class="py-2">
                                <?php if ($comment['status'] === 'pending'): ?>
                                <a href="?action=approve&id=<?php echo $comment['id']; ?>" class="text-green-600 hover:underline">Approva</a>
                                <a href="?action=reject&id=<?php echo $comment['id']; ?>" class="text-orange-600 hover:underline ml-2">Rifiuta</a>
                                <?php endif; ?>
                                <a href="?action=edit&id=<?php echo $comment['id']; ?>" class="text-blue-600 hover:underline ml-2">Modifica</a>
                                <a href="?action=delete&id=<?php echo $comment['id']; ?>" class="text-red-600 hover:underline ml-2" onclick="return confirm('Sei sicuro di voler eliminare questo commento?');">Elimina</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php elseif ($action === 'edit'):
                $comment = null;
                if ($id) {
                    $comment = $db->getCommentById($id);
                }
            ?>
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h2 class="text-lg font-semibold mb-4">Modifica Commento</h2>
                <form action="?action=edit&id=<?php echo $id; ?>" method="POST">
                    <div class="mb-4">
                        <label for="content" class="block text-gray-700 font-bold mb-2">Contenuto</label>
                        <textarea name="content" id="content" rows="5" class="w-full px-3 py-2 border rounded-lg" required><?php echo htmlspecialchars($comment['content'] ?? ''); ?></textarea>
                    </div>
                    <div class="text-right">
                        <a href="commenti.php" class="text-gray-600 hover:underline mr-4">Annulla</a>
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg">Salva Modifiche</button>
                    </div>
                </form>
            </div>
            <?php endif; ?>
        </main>
    </div>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>
