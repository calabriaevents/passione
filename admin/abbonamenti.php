<?php
require_once '../includes/config.php';
require_once '../includes/database.php';

// Controlla autenticazione (per ora commentiamo)
// // requireLogin(); // DISABILITATO

$db = new Database();

// Handle actions
$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? null;
$filter = $_GET['filter'] ?? 'all';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'update_status') {
        $subscription_id = $_POST['subscription_id'] ?? null;
        $new_status = $_POST['status'] ?? null;
        
        if ($subscription_id && $new_status) {
            $stmt = $db->pdo->prepare('UPDATE subscriptions SET status = ? WHERE id = ?');
            $stmt->execute([$new_status, $subscription_id]);
            
            header('Location: abbonamenti.php?message=status_updated');
            exit;
        }
    }
}

// Get subscription statistics
$stats = [
    'total' => 0,
    'active' => 0,
    'expiring' => 0,
    'expired' => 0,
    'cancelled' => 0,
    'total_revenue' => 0
];

try {
    // Total subscriptions
    $stmt = $db->pdo->query('SELECT COUNT(*) as count FROM subscriptions');
    $stats['total'] = $stmt->fetch()['count'];
    
    // Active subscriptions
    $stmt = $db->pdo->query("SELECT COUNT(*) as count FROM subscriptions WHERE status = 'active'");
    $stats['active'] = $stmt->fetch()['count'];
    
    // Expiring soon (next 30 days)
    $stmt = $db->pdo->query("SELECT COUNT(*) as count FROM subscriptions WHERE status = 'active' AND end_date <= date('now', '+30 days') AND end_date > date('now')");
    $stats['expiring'] = $stmt->fetch()['count'];
    
    // Expired
    $stmt = $db->pdo->query("SELECT COUNT(*) as count FROM subscriptions WHERE status = 'expired' OR (status = 'active' AND end_date < date('now'))");
    $stats['expired'] = $stmt->fetch()['count'];
    
    // Cancelled
    $stmt = $db->pdo->query("SELECT COUNT(*) as count FROM subscriptions WHERE status = 'cancelled'");
    $stats['cancelled'] = $stmt->fetch()['count'];
    
    // Total revenue
    $stmt = $db->pdo->query("SELECT SUM(amount) as total FROM subscriptions WHERE status IN ('active', 'expired', 'cancelled')");
    $stats['total_revenue'] = $stmt->fetch()['total'] ?: 0;
    
} catch (Exception $e) {
    // Handle error
}

// Get subscriptions with filters
$sql = "
    SELECT s.*, 
           bp.name as package_name, 
           bp.price as package_price,
           b.name as business_name,
           b.email as business_email,
           CASE 
               WHEN s.status = 'active' AND s.end_date < date('now') THEN 'expired'
               WHEN s.status = 'active' AND s.end_date <= date('now', '+30 days') AND s.end_date > date('now') THEN 'expiring'
               ELSE s.status
           END as computed_status
    FROM subscriptions s
    LEFT JOIN business_packages bp ON s.package_id = bp.id
    LEFT JOIN businesses b ON s.business_id = b.id
";

$params = [];
$whereConditions = [];

if ($filter !== 'all') {
    switch ($filter) {
        case 'active':
            $whereConditions[] = "s.status = 'active' AND (s.end_date IS NULL OR s.end_date > date('now'))";
            break;
        case 'expiring':
            $whereConditions[] = "s.status = 'active' AND s.end_date <= date('now', '+30 days') AND s.end_date > date('now')";
            break;
        case 'expired':
            $whereConditions[] = "(s.status = 'expired' OR (s.status = 'active' AND s.end_date < date('now')))";
            break;
        case 'cancelled':
            $whereConditions[] = "s.status = 'cancelled'";
            break;
    }
}

if (!empty($whereConditions)) {
    $sql .= ' WHERE ' . implode(' AND ', $whereConditions);
}

$sql .= ' ORDER BY s.created_at DESC';

$stmt = $db->pdo->prepare($sql);
$stmt->execute($params);
$subscriptions = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestione Abbonamenti - Admin Panel</title>

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
                <li><a href="index.php" class="flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-gray-700 transition-colors"><i data-lucide="home" class="w-5 h-5"></i><span>Dashboard</span></a></li>
                <li><a href="gestione-home.php" class="flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-gray-700 transition-colors"><i data-lucide="layout" class="w-5 h-5"></i><span>Gestione Home</span></a></li>
                <li><a href="articoli.php" class="flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-gray-700 transition-colors"><i data-lucide="file-text" class="w-5 h-5"></i><span>Articoli</span></a></li>
                <li><a href="categorie.php" class="flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-gray-700 transition-colors"><i data-lucide="tags" class="w-5 h-5"></i><span>Categorie</span></a></li>
                <li><a href="province.php" class="flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-gray-700 transition-colors"><i data-lucide="map-pin" class="w-5 h-5"></i><span>Province & Città</span></a></li>
                <li><a href="commenti.php" class="flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-gray-700 transition-colors"><i data-lucide="message-square" class="w-5 h-5"></i><span>Commenti</span></a></li>
                <li><a href="business.php" class="flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-gray-700 transition-colors"><i data-lucide="building-2" class="w-5 h-5"></i><span>Business</span></a></li>
                <li><a href="abbonamenti.php" class="flex items-center space-x-3 px-3 py-2 rounded-lg bg-gray-700 text-white"><i data-lucide="credit-card" class="w-5 h-5"></i><span>Abbonamenti</span></a></li>
                <li><a href="utenti.php" class="flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-gray-700 transition-colors"><i data-lucide="users" class="w-5 h-5"></i><span>Utenti</span></a></li>
                <li><a href="gestione-traduzione.php" class="flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-gray-700 transition-colors"><i data-lucide="globe" class="w-5 h-5"></i><span>Gestione Traduzione</span></a></li>
                <li><a href="database.php" class="flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-gray-700 transition-colors"><i data-lucide="database" class="w-5 h-5"></i><span>Monitoraggio DB</span></a></li>
                <li><a href="impostazioni.php" class="flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-gray-700 transition-colors"><i data-lucide="settings" class="w-5 h-5"></i><span>Impostazioni</span></a></li>
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
                    <h1 class="text-2xl font-bold text-gray-900 flex items-center">
                        <i data-lucide="credit-card" class="w-7 h-7 text-blue-600 mr-2"></i>
                        Gestione Abbonamenti
                    </h1>
                    <p class="text-sm text-gray-500">Monitora e gestisci gli abbonamenti delle attività</p>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="gestione-pacchetti.php" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium transition-colors flex items-center">
                        <i data-lucide="package" class="w-4 h-4 mr-2"></i>
                        Gestisci Pacchetti
                    </a>
                </div>
            </div>
        </header>

        <!-- Page Content -->
        <main class="flex-1 overflow-auto p-6">
            <!-- Success Message -->
            <?php if (isset($_GET['message']) && $_GET['message'] === 'status_updated'): ?>
            <div class="mb-6 bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded">
                <p class="font-medium">✅ Stato abbonamento aggiornato con successo!</p>
            </div>
            <?php endif; ?>

            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6 mb-8">
                <!-- Total Subscriptions -->
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Totali</p>
                            <p class="text-3xl font-bold text-gray-900"><?php echo $stats['total']; ?></p>
                            <p class="text-sm text-gray-500">Abbonamenti</p>
                        </div>
                        <div class="bg-blue-100 p-3 rounded-full">
                            <i data-lucide="credit-card" class="w-6 h-6 text-blue-600"></i>
                        </div>
                    </div>
                </div>

                <!-- Active -->
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Attivi</p>
                            <p class="text-3xl font-bold text-green-900"><?php echo $stats['active']; ?></p>
                            <p class="text-sm text-gray-500">In corso</p>
                        </div>
                        <div class="bg-green-100 p-3 rounded-full">
                            <i data-lucide="check-circle" class="w-6 h-6 text-green-600"></i>
                        </div>
                    </div>
                </div>

                <!-- Expiring -->
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">In Scadenza</p>
                            <p class="text-3xl font-bold text-orange-900"><?php echo $stats['expiring']; ?></p>
                            <p class="text-sm text-gray-500">Prossimi 30gg</p>
                        </div>
                        <div class="bg-orange-100 p-3 rounded-full">
                            <i data-lucide="clock" class="w-6 h-6 text-orange-600"></i>
                        </div>
                    </div>
                </div>

                <!-- Expired -->
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Scaduti</p>
                            <p class="text-3xl font-bold text-red-900"><?php echo $stats['expired']; ?></p>
                            <p class="text-sm text-gray-500">Non rinnovati</p>
                        </div>
                        <div class="bg-red-100 p-3 rounded-full">
                            <i data-lucide="x-circle" class="w-6 h-6 text-red-600"></i>
                        </div>
                    </div>
                </div>

                <!-- Revenue -->
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Ricavi</p>
                            <p class="text-3xl font-bold text-purple-900">€<?php echo number_format($stats['total_revenue'], 2); ?></p>
                            <p class="text-sm text-gray-500">Totali</p>
                        </div>
                        <div class="bg-purple-100 p-3 rounded-full">
                            <i data-lucide="euro" class="w-6 h-6 text-purple-600"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="mb-6">
                <div class="bg-white rounded-lg shadow-sm p-4">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="text-sm font-medium text-gray-700">Filtri:</span>
                        <a href="abbonamenti.php?filter=all" 
                           class="px-3 py-1 rounded-full text-sm font-medium transition-colors <?php echo $filter === 'all' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'; ?>">
                            Tutti (<?php echo $stats['total']; ?>)
                        </a>
                        <a href="abbonamenti.php?filter=active" 
                           class="px-3 py-1 rounded-full text-sm font-medium transition-colors <?php echo $filter === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'; ?>">
                            Attivi (<?php echo $stats['active']; ?>)
                        </a>
                        <a href="abbonamenti.php?filter=expiring" 
                           class="px-3 py-1 rounded-full text-sm font-medium transition-colors <?php echo $filter === 'expiring' ? 'bg-orange-100 text-orange-800' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'; ?>">
                            In Scadenza (<?php echo $stats['expiring']; ?>)
                        </a>
                        <a href="abbonamenti.php?filter=expired" 
                           class="px-3 py-1 rounded-full text-sm font-medium transition-colors <?php echo $filter === 'expired' ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'; ?>">
                            Scaduti (<?php echo $stats['expired']; ?>)
                        </a>
                        <a href="abbonamenti.php?filter=cancelled" 
                           class="px-3 py-1 rounded-full text-sm font-medium transition-colors <?php echo $filter === 'cancelled' ? 'bg-gray-200 text-gray-800' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'; ?>">
                            Cancellati (<?php echo $stats['cancelled']; ?>)
                        </a>
                    </div>
                </div>
            </div>

            <!-- Subscriptions List -->
            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold flex items-center">
                        <i data-lucide="list" class="w-5 h-5 mr-2 text-blue-600"></i>
                        <?php 
                        $filterNames = [
                            'all' => 'Tutti gli Abbonamenti',
                            'active' => 'Abbonamenti Attivi',
                            'expiring' => 'Abbonamenti in Scadenza',
                            'expired' => 'Abbonamenti Scaduti',
                            'cancelled' => 'Abbonamenti Cancellati'
                        ];
                        echo $filterNames[$filter] ?? 'Abbonamenti';
                        ?>
                        <span class="ml-2 text-sm font-normal text-gray-500">(<?php echo count($subscriptions); ?>)</span>
                    </h2>
                </div>

                <?php if (count($subscriptions) > 0): ?>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="text-left py-3 px-6 font-semibold text-gray-700">Business</th>
                                <th class="text-left py-3 px-6 font-semibold text-gray-700">Pacchetto</th>
                                <th class="text-left py-3 px-6 font-semibold text-gray-700">Stato</th>
                                <th class="text-left py-3 px-6 font-semibold text-gray-700">Periodo</th>
                                <th class="text-left py-3 px-6 font-semibold text-gray-700">Importo</th>
                                <th class="text-left py-3 px-6 font-semibold text-gray-700">Stripe ID</th>
                                <th class="text-left py-3 px-6 font-semibold text-gray-700">Azioni</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($subscriptions as $subscription): 
                                $statusClasses = [
                                    'active' => 'bg-green-100 text-green-800',
                                    'expiring' => 'bg-orange-100 text-orange-800',
                                    'expired' => 'bg-red-100 text-red-800',
                                    'cancelled' => 'bg-gray-100 text-gray-800',
                                    'pending' => 'bg-yellow-100 text-yellow-800'
                                ];
                                $statusClass = $statusClasses[$subscription['computed_status']] ?? 'bg-gray-100 text-gray-800';
                                
                                $statusLabels = [
                                    'active' => 'Attivo',
                                    'expiring' => 'In Scadenza',
                                    'expired' => 'Scaduto',
                                    'cancelled' => 'Cancellato',
                                    'pending' => 'In Attesa'
                                ];
                                $statusLabel = $statusLabels[$subscription['computed_status']] ?? ucfirst($subscription['status']);
                            ?>
                            <tr class="border-b border-gray-100 hover:bg-gray-50">
                                <td class="py-4 px-6">
                                    <div>
                                        <div class="font-medium text-gray-900"><?php echo htmlspecialchars($subscription['business_name'] ?: 'N/A'); ?></div>
                                        <div class="text-sm text-gray-500"><?php echo htmlspecialchars($subscription['business_email'] ?: 'N/A'); ?></div>
                                    </div>
                                </td>
                                <td class="py-4 px-6">
                                    <div>
                                        <div class="font-medium text-gray-900"><?php echo htmlspecialchars($subscription['package_name'] ?: 'N/A'); ?></div>
                                        <div class="text-sm text-gray-500">€<?php echo number_format($subscription['package_price'] ?: 0, 2); ?>/anno</div>
                                    </div>
                                </td>
                                <td class="py-4 px-6">
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium <?php echo $statusClass; ?>">
                                        <?php echo $statusLabel; ?>
                                    </span>
                                </td>
                                <td class="py-4 px-6 text-sm text-gray-600">
                                    <?php if ($subscription['start_date']): ?>
                                        <div>Inizio: <?php echo formatDate($subscription['start_date']); ?></div>
                                    <?php endif; ?>
                                    <?php if ($subscription['end_date']): ?>
                                        <div>Fine: <?php echo formatDate($subscription['end_date']); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="py-4 px-6">
                                    <div class="font-medium">€<?php echo number_format($subscription['amount'] ?: 0, 2); ?></div>
                                </td>
                                <td class="py-4 px-6">
                                    <?php if ($subscription['stripe_subscription_id']): ?>
                                        <code class="text-xs bg-gray-100 px-2 py-1 rounded"><?php echo htmlspecialchars(substr($subscription['stripe_subscription_id'], 0, 20)); ?>...</code>
                                    <?php else: ?>
                                        <span class="text-gray-400 text-sm">N/A</span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-4 px-6">
                                    <div class="flex items-center space-x-2">
                                        <!-- Status Change Dropdown -->
                                        <div class="relative">
                                            <button onclick="toggleStatusMenu(<?php echo $subscription['id']; ?>)" 
                                                    class="flex items-center px-3 py-1 text-xs font-medium text-gray-600 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">
                                                <i data-lucide="more-horizontal" class="w-4 h-4 mr-1"></i>
                                                Cambia Stato
                                            </button>
                                            <div id="status-menu-<?php echo $subscription['id']; ?>" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border z-10">
                                                <form action="abbonamenti.php?action=update_status" method="POST" class="p-2">
                                                    <input type="hidden" name="subscription_id" value="<?php echo $subscription['id']; ?>">
                                                    <button type="submit" name="status" value="active" class="w-full text-left px-3 py-2 text-sm hover:bg-gray-100 rounded">✅ Attiva</button>
                                                    <button type="submit" name="status" value="cancelled" class="w-full text-left px-3 py-2 text-sm hover:bg-gray-100 rounded">❌ Cancella</button>
                                                    <button type="submit" name="status" value="expired" class="w-full text-left px-3 py-2 text-sm hover:bg-gray-100 rounded">⏰ Scadi</button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <!-- Empty State -->
                <div class="text-center py-12">
                    <i data-lucide="credit-card" class="w-12 h-12 text-gray-300 mx-auto mb-4"></i>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">
                        <?php 
                        if ($filter === 'all') {
                            echo 'Nessun abbonamento presente';
                        } else {
                            echo 'Nessun abbonamento ' . strtolower($filterNames[$filter] ?? '');
                        }
                        ?>
                    </h3>
                    <p class="text-gray-500 mb-6">
                        <?php if ($filter === 'all'): ?>
                            Quando le attività si abboneranno, gli abbonamenti appariranno qui.
                        <?php else: ?>
                            Non ci sono abbonamenti con questo stato al momento.
                        <?php endif; ?>
                    </p>
                    <?php if ($filter !== 'all'): ?>
                        <a href="abbonamenti.php?filter=all" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                            <i data-lucide="list" class="w-4 h-4 mr-2"></i>
                            Vedi Tutti gli Abbonamenti
                        </a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- JavaScript -->
    <script>
        // Inizializza Lucide icons
        lucide.createIcons();

        // Status menu toggle
        function toggleStatusMenu(subscriptionId) {
            // Hide all other menus first
            document.querySelectorAll('[id^="status-menu-"]').forEach(menu => {
                if (menu.id !== `status-menu-${subscriptionId}`) {
                    menu.classList.add('hidden');
                }
            });
            
            // Toggle current menu
            const menu = document.getElementById(`status-menu-${subscriptionId}`);
            menu.classList.toggle('hidden');
        }

        // Close menus when clicking outside
        document.addEventListener('click', function(event) {
            if (!event.target.closest('[onclick^="toggleStatusMenu"]') && !event.target.closest('[id^="status-menu-"]')) {
                document.querySelectorAll('[id^="status-menu-"]').forEach(menu => {
                    menu.classList.add('hidden');
                });
            }
        });

        // Confirm status changes
        document.querySelectorAll('form button[name="status"]').forEach(button => {
            button.addEventListener('click', function(e) {
                const action = this.textContent.trim();
                if (!confirm(`Sei sicuro di voler modificare lo stato a: ${action}?`)) {
                    e.preventDefault();
                }
            });
        });
    </script>
</body>
</html>