<?php
require_once '../includes/config.php';
require_once '../includes/database.php';

// Controlla autenticazione (per ora commentiamo)
// // requireLogin(); // DISABILITATO

$db = null;
$dbError = null;
$stats = [];
$totalViews = 0;
$recentArticles = [];
$healthData = [];

try {
    $db = new Database();

    // Carica statistiche dashboard
    $stats = [
        'articles' => $db->pdo->query('SELECT COUNT(*) FROM articles')->fetchColumn(),
        'published_articles' => $db->pdo->query('SELECT COUNT(*) FROM articles WHERE status = "published"')->fetchColumn(),
        'categories' => $db->pdo->query('SELECT COUNT(*) FROM categories')->fetchColumn(),
        'provinces' => $db->pdo->query('SELECT COUNT(*) FROM provinces')->fetchColumn(),
        'cities' => $db->pdo->query('SELECT COUNT(*) FROM cities')->fetchColumn(),
        'users' => $db->pdo->query('SELECT COUNT(*) FROM users')->fetchColumn(),
        'businesses' => $db->pdo->query('SELECT COUNT(*) FROM businesses')->fetchColumn(),
        'events' => $db->pdo->query('SELECT COUNT(*) FROM events')->fetchColumn(),
        'comments' => $db->pdo->query('SELECT COUNT(*) FROM comments')->fetchColumn(),
        'pending_comments' => $db->pdo->query('SELECT COUNT(*) FROM comments WHERE status = "pending"')->fetchColumn()
    ];

    // Statistiche visualizzazioni
    $totalViews = $db->pdo->query('SELECT SUM(views) FROM articles')->fetchColumn() ?: 0;

    // Articoli recenti
    $recentArticles = $db->pdo->query('
        SELECT a.*, c.name as category_name
        FROM articles a
        LEFT JOIN categories c ON a.category_id = c.id
        ORDER BY a.created_at DESC
        LIMIT 5
    ')->fetchAll();

    // Controllo salute database (temporaneamente disabilitato per debug)
    // $healthData = $db->getDatabaseHealth();
    $healthData = [
        'database' => ['size' => 'N/A'],
        'counts' => [],
        'health' => ['checks' => ['integrityOk' => false]]
    ];

} catch (PDOException $e) {
    $dbError = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Passione Calabria</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    }
                }
            }
        }
    </script>
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
                    <a href="index.php" class="flex items-center space-x-3 px-3 py-2 rounded-lg bg-gray-700 text-white">
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
                    <h1 class="text-2xl font-bold text-gray-900">Dashboard</h1>
                    <p class="text-sm text-gray-500">Gestisci i contenuti di Passione Calabria</p>
                </div>
                <div class="flex items-center space-x-4">
                    <div class="flex items-center space-x-2 bg-green-100 px-3 py-1 rounded-full">
                        <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                        <span class="text-sm font-medium text-green-800">Online</span>
                    </div>
                </div>
            </div>
        </header>

        <!-- Page Content -->
        <main class="flex-1 overflow-auto p-6">
            <?php if ($dbError): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4" role="alert">
                <p class="font-bold">Errore di Connessione al Database</p>
                <p><?php echo $dbError; ?></p>
                <div class="mt-4">
                    <p>Assicurati che il file del database SQLite (<strong>passione_calabria.db</strong>) esista e sia scrivibile.</p>
                    <p>Potrebbe essere necessario importare il file <strong>database_mysql.sql</strong> nel tuo database.</p>
                </div>
            </div>
            <?php else: ?>
            <!-- Stats Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <!-- Articoli -->
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Articoli Totali</p>
                            <p class="text-3xl font-bold text-gray-900"><?php echo $stats['articles']; ?></p>
                            <p class="text-sm text-gray-500"><?php echo $stats['published_articles']; ?> pubblicati</p>
                        </div>
                        <div class="bg-blue-100 p-3 rounded-full">
                            <i data-lucide="file-text" class="w-6 h-6 text-blue-600"></i>
                        </div>
                    </div>
                </div>

                <!-- Visualizzazioni -->
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Visualizzazioni</p>
                            <p class="text-3xl font-bold text-gray-900"><?php echo number_format($totalViews); ?></p>
                            <p class="text-sm text-gray-500">Totali</p>
                        </div>
                        <div class="bg-green-100 p-3 rounded-full">
                            <i data-lucide="eye" class="w-6 h-6 text-green-600"></i>
                        </div>
                    </div>
                </div>

                <!-- Commenti -->
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Commenti</p>
                            <p class="text-3xl font-bold text-gray-900"><?php echo $stats['comments']; ?></p>
                            <p class="text-sm text-gray-500"><?php echo $stats['pending_comments']; ?> in attesa</p>
                        </div>
                        <div class="bg-purple-100 p-3 rounded-full">
                            <i data-lucide="message-square" class="w-6 h-6 text-purple-600"></i>
                        </div>
                    </div>
                </div>

                <!-- Business -->
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Business</p>
                            <p class="text-3xl font-bold text-gray-900"><?php echo $stats['businesses']; ?></p>
                            <p class="text-sm text-gray-500">Registrati</p>
                        </div>
                        <div class="bg-orange-100 p-3 rounded-full">
                            <i data-lucide="building-2" class="w-6 h-6 text-orange-600"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
                <!-- Database Health -->
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <h3 class="text-lg font-semibold mb-4 flex items-center">
                        <i data-lucide="database" class="w-5 h-5 mr-2 text-blue-600"></i>
                        Stato Database
                    </h3>

                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600">Dimensione Database</span>
                            <span class="font-medium"><?php echo $healthData['database']['size']; ?></span>
                        </div>

                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600">Tabelle Totali</span>
                            <span class="font-medium"><?php echo count($healthData['counts']); ?></span>
                        </div>

                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600">Integrità</span>
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                <i data-lucide="check-circle" class="w-3 h-3 mr-1"></i>
                                OK
                            </span>
                        </div>

                        <div class="pt-4">
                            <a href="database.php" class="w-full bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors flex items-center justify-center">
                                <i data-lucide="settings" class="w-4 h-4 mr-2"></i>
                                Gestisci Database
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <h3 class="text-lg font-semibold mb-4 flex items-center">
                        <i data-lucide="zap" class="w-5 h-5 mr-2 text-yellow-600"></i>
                        Azioni Rapide
                    </h3>

                    <div class="grid grid-cols-2 gap-4">
                        <a href="articoli.php?action=new" class="flex flex-col items-center p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                            <i data-lucide="plus" class="w-8 h-8 text-blue-600 mb-2"></i>
                            <span class="text-sm font-medium text-gray-900">Nuovo Articolo</span>
                        </a>

                        <a href="commenti.php?filter=pending" class="flex flex-col items-center p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                            <i data-lucide="message-square" class="w-8 h-8 text-purple-600 mb-2"></i>
                            <span class="text-sm font-medium text-gray-900">Commenti</span>
                            <?php if ($stats['pending_comments'] > 0): ?>
                            <span class="bg-red-100 text-red-800 text-xs px-2 py-1 rounded-full mt-1">
                                <?php echo $stats['pending_comments']; ?>
                            </span>
                            <?php endif; ?>
                        </a>

                        <a href="business.php?filter=pending" class="flex flex-col items-center p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                            <i data-lucide="building-2" class="w-8 h-8 text-orange-600 mb-2"></i>
                            <span class="text-sm font-medium text-gray-900">Business</span>
                        </a>

                        <a href="database.php" class="flex flex-col items-center p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                            <i data-lucide="download" class="w-8 h-8 text-green-600 mb-2"></i>
                            <span class="text-sm font-medium text-gray-900">Backup DB</span>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Recent Articles -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-lg font-semibold flex items-center">
                        <i data-lucide="file-text" class="w-5 h-5 mr-2 text-blue-600"></i>
                        Articoli Recenti
                    </h3>
                    <a href="articoli.php" class="text-blue-600 hover:text-blue-700 text-sm font-medium">
                        Vedi tutti
                    </a>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-gray-200">
                                <th class="text-left py-3 px-4 font-medium text-gray-700">Titolo</th>
                                <th class="text-left py-3 px-4 font-medium text-gray-700">Categoria</th>
                                <th class="text-left py-3 px-4 font-medium text-gray-700">Stato</th>
                                <th class="text-left py-3 px-4 font-medium text-gray-700">Visualizzazioni</th>
                                <th class="text-left py-3 px-4 font-medium text-gray-700">Data</th>
                                <th class="text-left py-3 px-4 font-medium text-gray-700">Azioni</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentArticles as $article): ?>
                            <tr class="border-b border-gray-100 hover:bg-gray-50">
                                <td class="py-3 px-4">
                                    <div class="font-medium text-gray-900"><?php echo htmlspecialchars($article['title']); ?></div>
                                    <div class="text-sm text-gray-500"><?php echo truncateText($article['excerpt'], 60); ?></div>
                                </td>
                                <td class="py-3 px-4">
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                        <?php echo htmlspecialchars($article['category_name']); ?>
                                    </span>
                                </td>
                                <td class="py-3 px-4">
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium
                                        <?php echo $article['status'] === 'published' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'; ?>">
                                        <?php echo ucfirst($article['status']); ?>
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-gray-600"><?php echo $article['views']; ?></td>
                                <td class="py-3 px-4 text-gray-600"><?php echo formatDate($article['created_at']); ?></td>
                                <td class="py-3 px-4">
                                    <div class="flex items-center space-x-2">
                                        <a href="../articolo.php?slug=<?php echo $article['slug']; ?>"
                                           class="text-blue-600 hover:text-blue-700" title="Visualizza">
                                            <i data-lucide="eye" class="w-4 h-4"></i>
                                        </a>
                                        <a href="articoli.php?action=edit&id=<?php echo $article['id']; ?>"
                                           class="text-gray-600 hover:text-gray-700" title="Modifica">
                                            <i data-lucide="edit" class="w-4 h-4"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
        </main>
    </div>

    <!-- JavaScript -->
    <script src="../assets/js/main.js"></script>
    <script>
        // Inizializza Lucide icons
        lucide.createIcons();

        // Auto-refresh stats ogni 30 secondi
        setInterval(function() {
            // Qui potresti aggiungere una chiamata AJAX per aggiornare le stats
            console.log('Auto-refresh stats...');
        }, 30000);

        // Notifica per commenti in attesa
        <?php if ($stats['pending_comments'] > 0): ?>
        setTimeout(function() {
            PassioneCalabria.showNotification(
                'Hai <?php echo $stats['pending_comments']; ?> commenti in attesa di approvazione',
                'info'
            );
        }, 2000);
        <?php endif; ?>
    </script>
</body>
</html>
