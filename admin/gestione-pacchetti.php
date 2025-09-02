<?php
require_once '../includes/config.php';
require_once '../includes/database.php';

// Controlla autenticazione (per ora commentiamo)
// // requireLogin(); // DISABILITATO

$db = new Database();

// Handle actions
$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $description = $_POST['description'] ?? '';
    $price = floatval($_POST['price'] ?? 0);
    $duration_months = intval($_POST['duration_months'] ?? 12);
    $features = $_POST['features'] ?? '';
    $stripe_price_id = $_POST['stripe_price_id'] ?? '';
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $sort_order = intval($_POST['sort_order'] ?? 0);
    
    // Convert features to JSON if it's not already
    if (!empty($features) && !is_array($features)) {
        $features = array_map('trim', explode("\n", $features));
    }
    $features_json = json_encode($features);
    
    // Handle image upload
    $image_path = null;
    if (isset($_FILES['package_image']) && $_FILES['package_image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../uploads/packages/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $fileExtension = strtolower(pathinfo($_FILES['package_image']['name'], PATHINFO_EXTENSION));
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
        
        if (in_array($fileExtension, $allowedExtensions)) {
            $fileName = uniqid() . '.' . $fileExtension;
            $targetPath = $uploadDir . $fileName;
            
            if (move_uploaded_file($_FILES['package_image']['tmp_name'], $targetPath)) {
                $image_path = 'uploads/packages/' . $fileName;
            }
        }
    }
    
    try {
        if ($action === 'create') {
            $stmt = $db->pdo->prepare('
                INSERT INTO business_packages (name, description, price, duration_months, features, stripe_price_id, is_active, sort_order, image_path) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ');
            $stmt->execute([$name, $description, $price, $duration_months, $features_json, $stripe_price_id, $is_active, $sort_order, $image_path]);
            header('Location: gestione-pacchetti.php?message=created');
        } elseif ($action === 'edit' && $id) {
            // Get existing package to preserve image if no new one uploaded
            $stmt = $db->pdo->prepare('SELECT image_path FROM business_packages WHERE id = ?');
            $stmt->execute([$id]);
            $existing = $stmt->fetch();
            
            if ($image_path === null && $existing) {
                $image_path = $existing['image_path'];
            }
            
            $stmt = $db->pdo->prepare('
                UPDATE business_packages 
                SET name = ?, description = ?, price = ?, duration_months = ?, features = ?, stripe_price_id = ?, is_active = ?, sort_order = ?, image_path = ?
                WHERE id = ?
            ');
            $stmt->execute([$name, $description, $price, $duration_months, $features_json, $stripe_price_id, $is_active, $sort_order, $image_path, $id]);
            header('Location: gestione-pacchetti.php?message=updated');
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
    exit;
}

if ($action === 'delete' && $id) {
    try {
        // Check if package is used in subscriptions
        $stmt = $db->pdo->prepare('SELECT COUNT(*) as count FROM subscriptions WHERE package_id = ?');
        $stmt->execute([$id]);
        $usageCount = $stmt->fetch()['count'];
        
        if ($usageCount > 0) {
            header('Location: gestione-pacchetti.php?message=delete_error&used_count=' . $usageCount);
        } else {
            // Delete package image if exists
            $stmt = $db->pdo->prepare('SELECT image_path FROM business_packages WHERE id = ?');
            $stmt->execute([$id]);
            $package = $stmt->fetch();
            if ($package && !empty($package['image_path']) && file_exists('../' . $package['image_path'])) {
                unlink('../' . $package['image_path']);
            }
            
            $stmt = $db->pdo->prepare('DELETE FROM business_packages WHERE id = ?');
            $stmt->execute([$id]);
            header('Location: gestione-pacchetti.php?message=deleted');
        }
    } catch (Exception $e) {
        header('Location: gestione-pacchetti.php?message=error');
    }
    exit;
}

if ($action === 'toggle_status' && $id) {
    try {
        $stmt = $db->pdo->prepare('UPDATE business_packages SET is_active = NOT is_active WHERE id = ?');
        $stmt->execute([$id]);
        header('Location: gestione-pacchetti.php?message=status_updated');
    } catch (Exception $e) {
        header('Location: gestione-pacchetti.php?message=error');
    }
    exit;
}

// Get packages
$packages = [];
try {
    $stmt = $db->pdo->prepare('SELECT * FROM business_packages ORDER BY sort_order ASC, created_at ASC');
    $stmt->execute();
    $packages = $stmt->fetchAll();
} catch (Exception $e) {
    $error = $e->getMessage();
}

// Get package for editing
$editPackage = null;
if ($action === 'edit' && $id) {
    try {
        $stmt = $db->pdo->prepare('SELECT * FROM business_packages WHERE id = ?');
        $stmt->execute([$id]);
        $editPackage = $stmt->fetch();
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Get statistics
$stats = [];
try {
    $stmt = $db->pdo->query('SELECT COUNT(*) as total FROM business_packages');
    $stats['total'] = $stmt->fetch()['total'];
    
    $stmt = $db->pdo->query('SELECT COUNT(*) as active FROM business_packages WHERE is_active = 1');
    $stats['active'] = $stmt->fetch()['active'];
    
    $stmt = $db->pdo->query('SELECT COUNT(*) as used FROM business_packages WHERE id IN (SELECT DISTINCT package_id FROM subscriptions)');
    $stats['used'] = $stmt->fetch()['used'];
} catch (Exception $e) {
    $stats = ['total' => 0, 'active' => 0, 'used' => 0];
}

?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestione Pacchetti Abbonamento - Admin Panel</title>

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
                <li><a href="abbonamenti.php" class="flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-gray-700 transition-colors"><i data-lucide="credit-card" class="w-5 h-5"></i><span>Abbonamenti</span></a></li>
                <li><a href="utenti.php" class="flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-gray-700 transition-colors"><i data-lucide="users" class="w-5 h-5"></i><span>Utenti</span></a></li>
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
                        <i data-lucide="package" class="w-7 h-7 text-purple-600 mr-2"></i>
                        Gestione Pacchetti Abbonamento
                    </h1>
                    <p class="text-sm text-gray-500">Crea e gestisci i piani di abbonamento per le attività</p>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="abbonamenti.php" class="text-gray-600 hover:text-gray-800 px-4 py-2 rounded-lg border hover:bg-gray-50 transition-colors flex items-center">
                        <i data-lucide="credit-card" class="w-4 h-4 mr-2"></i>
                        Vedi Abbonamenti
                    </a>
                    <?php if ($action !== 'create' && $action !== 'edit'): ?>
                    <a href="gestione-pacchetti.php?action=create" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg font-medium transition-colors flex items-center">
                        <i data-lucide="plus" class="w-4 h-4 mr-2"></i>
                        Nuovo Pacchetto
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </header>

        <!-- Page Content -->
        <main class="flex-1 overflow-auto p-6">
            <!-- Messages -->
            <?php if (isset($_GET['message'])): ?>
                <?php
                $messages = [
                    'created' => ['type' => 'success', 'text' => '✅ Pacchetto creato con successo!'],
                    'updated' => ['type' => 'success', 'text' => '✅ Pacchetto aggiornato con successo!'],
                    'deleted' => ['type' => 'success', 'text' => '✅ Pacchetto eliminato con successo!'],
                    'status_updated' => ['type' => 'success', 'text' => '✅ Stato pacchetto aggiornato!'],
                    'delete_error' => ['type' => 'error', 'text' => '❌ Impossibile eliminare: pacchetto in uso in ' . ($_GET['used_count'] ?? 0) . ' abbonamenti'],
                    'error' => ['type' => 'error', 'text' => '❌ Si è verificato un errore. Riprova.']
                ];
                $message = $messages[$_GET['message']] ?? null;
                if ($message):
                ?>
                <div class="mb-6 bg-<?php echo $message['type'] === 'success' ? 'green' : 'red'; ?>-100 border-l-4 border-<?php echo $message['type'] === 'success' ? 'green' : 'red'; ?>-500 text-<?php echo $message['type'] === 'success' ? 'green' : 'red'; ?>-700 p-4 rounded">
                    <p class="font-medium"><?php echo htmlspecialchars($message['text']); ?></p>
                </div>
                <?php endif; ?>
            <?php endif; ?>

            <?php if ($action === 'list'): ?>
            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Pacchetti Totali</p>
                            <p class="text-3xl font-bold text-gray-900"><?php echo $stats['total']; ?></p>
                        </div>
                        <div class="bg-purple-100 p-3 rounded-full">
                            <i data-lucide="package" class="w-6 h-6 text-purple-600"></i>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Pacchetti Attivi</p>
                            <p class="text-3xl font-bold text-green-900"><?php echo $stats['active']; ?></p>
                        </div>
                        <div class="bg-green-100 p-3 rounded-full">
                            <i data-lucide="check-circle" class="w-6 h-6 text-green-600"></i>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Pacchetti in Uso</p>
                            <p class="text-3xl font-bold text-blue-900"><?php echo $stats['used']; ?></p>
                        </div>
                        <div class="bg-blue-100 p-3 rounded-full">
                            <i data-lucide="users" class="w-6 h-6 text-blue-600"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Packages Grid -->
            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold flex items-center">
                        <i data-lucide="grid-3x3" class="w-5 h-5 mr-2 text-purple-600"></i>
                        Pacchetti Abbonamento
                        <span class="ml-2 text-sm font-normal text-gray-500">(<?php echo count($packages); ?>)</span>
                    </h2>
                </div>

                <?php if (count($packages) > 0): ?>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <?php foreach ($packages as $package): 
                            $features = json_decode($package['features'], true) ?: [];
                        ?>
                        <div class="border rounded-xl p-6 <?php echo $package['is_active'] ? 'border-gray-200 bg-white' : 'border-gray-100 bg-gray-50'; ?> hover:shadow-lg transition-all">
                            <!-- Package Header -->
                            <div class="flex items-start justify-between mb-4">
                                <div class="flex-1">
                                    <div class="flex items-center gap-2 mb-2">
                                        <h3 class="text-xl font-bold text-gray-900"><?php echo htmlspecialchars($package['name']); ?></h3>
                                        <?php if (!$package['is_active']): ?>
                                            <span class="bg-gray-100 text-gray-600 px-2 py-1 rounded-full text-xs font-medium">Disattivo</span>
                                        <?php endif; ?>
                                    </div>
                                    <p class="text-gray-600 text-sm mb-3"><?php echo htmlspecialchars($package['description']); ?></p>
                                </div>
                                
                                <?php if (!empty($package['image_path'])): ?>
                                <div class="w-16 h-16 ml-4 flex-shrink-0">
                                    <img src="../<?php echo htmlspecialchars($package['image_path']); ?>" 
                                         alt="<?php echo htmlspecialchars($package['name']); ?>"
                                         class="w-full h-full object-cover rounded-lg border">
                                </div>
                                <?php endif; ?>
                            </div>

                            <!-- Price -->
                            <div class="mb-4">
                                <?php if ($package['price'] == 0): ?>
                                <div class="text-3xl font-bold text-gray-900">Gratuito</div>
                                <?php else: ?>
                                <div class="text-3xl font-bold text-gray-900">
                                    €<?php echo number_format($package['price'], 2); ?>
                                    <span class="text-sm font-normal text-gray-500">/<?php echo $package['duration_months']; ?>m</span>
                                </div>
                                <?php endif; ?>
                            </div>

                            <!-- Features -->
                            <?php if (!empty($features)): ?>
                            <div class="mb-6">
                                <ul class="space-y-2">
                                    <?php foreach (array_slice($features, 0, 4) as $feature): ?>
                                    <li class="flex items-start text-sm">
                                        <i data-lucide="check" class="w-4 h-4 text-green-500 mr-2 mt-0.5 flex-shrink-0"></i>
                                        <span class="text-gray-700"><?php echo htmlspecialchars($feature); ?></span>
                                    </li>
                                    <?php endforeach; ?>
                                    <?php if (count($features) > 4): ?>
                                    <li class="text-xs text-gray-500">+<?php echo count($features) - 4; ?> altre funzionalità</li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                            <?php endif; ?>

                            <!-- Stripe Info -->
                            <?php if (!empty($package['stripe_price_id'])): ?>
                            <div class="mb-4 p-3 bg-blue-50 rounded-lg">
                                <div class="flex items-center text-sm">
                                    <i data-lucide="credit-card" class="w-4 h-4 text-blue-600 mr-2"></i>
                                    <span class="text-blue-800 font-medium">Stripe ID:</span>
                                    <code class="ml-2 text-xs bg-blue-100 px-2 py-1 rounded text-blue-900"><?php echo htmlspecialchars(substr($package['stripe_price_id'], 0, 20)); ?>...</code>
                                </div>
                            </div>
                            <?php endif; ?>

                            <!-- Actions -->
                            <div class="flex items-center justify-between pt-4 border-t border-gray-100">
                                <div class="flex items-center gap-2">
                                    <span class="text-xs text-gray-500">Ordine: <?php echo $package['sort_order']; ?></span>
                                </div>
                                
                                <div class="flex items-center space-x-2">
                                    <a href="gestione-pacchetti.php?action=edit&id=<?php echo $package['id']; ?>" 
                                       class="text-blue-600 hover:text-blue-700 p-2 rounded-lg hover:bg-blue-50 transition-colors" title="Modifica">
                                        <i data-lucide="edit" class="w-4 h-4"></i>
                                    </a>
                                    
                                    <a href="gestione-pacchetti.php?action=toggle_status&id=<?php echo $package['id']; ?>" 
                                       class="text-<?php echo $package['is_active'] ? 'orange' : 'green'; ?>-600 hover:text-<?php echo $package['is_active'] ? 'orange' : 'green'; ?>-700 p-2 rounded-lg hover:bg-<?php echo $package['is_active'] ? 'orange' : 'green'; ?>-50 transition-colors" 
                                       title="<?php echo $package['is_active'] ? 'Disattiva' : 'Attiva'; ?>">
                                        <i data-lucide="<?php echo $package['is_active'] ? 'eye-off' : 'eye'; ?>" class="w-4 h-4"></i>
                                    </a>
                                    
                                    <a href="gestione-pacchetti.php?action=delete&id=<?php echo $package['id']; ?>" 
                                       class="text-red-600 hover:text-red-700 p-2 rounded-lg hover:bg-red-50 transition-colors" title="Elimina"
                                       onclick="return confirm('Sei sicuro di voler eliminare questo pacchetto?')">
                                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php else: ?>
                <!-- Empty State -->
                <div class="text-center py-12">
                    <i data-lucide="package" class="w-12 h-12 text-gray-300 mx-auto mb-4"></i>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">Nessun pacchetto presente</h3>
                    <p class="text-gray-500 mb-6">Crea il primo pacchetto abbonamento per iniziare.</p>
                    <a href="gestione-pacchetti.php?action=create" class="inline-flex items-center px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors">
                        <i data-lucide="plus" class="w-4 h-4 mr-2"></i>
                        Crea Primo Pacchetto
                    </a>
                </div>
                <?php endif; ?>
            </div>

            <?php elseif ($action === 'create' || $action === 'edit'): ?>
            <!-- Create/Edit Form -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <div class="mb-6">
                    <h2 class="text-xl font-bold text-gray-900 flex items-center mb-2">
                        <i data-lucide="<?php echo $action === 'create' ? 'plus' : 'edit'; ?>" class="w-6 h-6 mr-2 text-purple-600"></i>
                        <?php echo $action === 'create' ? 'Crea Nuovo Pacchetto' : 'Modifica Pacchetto'; ?>
                    </h2>
                    <p class="text-gray-600">Configura i dettagli del pacchetto abbonamento incluse le funzionalità e l'integrazione Stripe.</p>
                </div>

                <form action="gestione-pacchetti.php?action=<?php echo $action; ?><?php if ($id) echo '&id='.$id; ?>" method="POST" enctype="multipart/form-data" class="space-y-6">
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <!-- Left Column -->
                        <div class="space-y-6">
                            <!-- Basic Info -->
                            <div class="bg-gray-50 p-6 rounded-lg">
                                <h3 class="text-lg font-semibold mb-4">📋 Informazioni Base</h3>
                                
                                <div class="space-y-4">
                                    <div>
                                        <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Nome Pacchetto *</label>
                                        <input type="text" name="name" id="name" required
                                               value="<?php echo htmlspecialchars($editPackage['name'] ?? ''); ?>"
                                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                                               placeholder="es. Business Premium">
                                    </div>

                                    <div>
                                        <label for="description" class="block text-sm font-medium text-gray-700 mb-2">Descrizione *</label>
                                        <textarea name="description" id="description" rows="3" required
                                                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                                                  placeholder="Descrizione breve del pacchetto"><?php echo htmlspecialchars($editPackage['description'] ?? ''); ?></textarea>
                                    </div>

                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label for="price" class="block text-sm font-medium text-gray-700 mb-2">Prezzo (€) *</label>
                                            <input type="number" name="price" id="price" step="0.01" min="0" required
                                                   value="<?php echo $editPackage['price'] ?? '0'; ?>"
                                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                                        </div>
                                        
                                        <div>
                                            <label for="duration_months" class="block text-sm font-medium text-gray-700 mb-2">Durata (mesi)</label>
                                            <input type="number" name="duration_months" id="duration_months" min="1" max="36"
                                                   value="<?php echo $editPackage['duration_months'] ?? '12'; ?>"
                                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                                        </div>
                                    </div>

                                    <div>
                                        <label for="sort_order" class="block text-sm font-medium text-gray-700 mb-2">Ordine di Visualizzazione</label>
                                        <input type="number" name="sort_order" id="sort_order" min="0"
                                               value="<?php echo $editPackage['sort_order'] ?? '0'; ?>"
                                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                                        <p class="text-xs text-gray-500 mt-1">Numeri più bassi appaiono per primi</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Package Image -->
                            <div class="bg-gray-50 p-6 rounded-lg">
                                <h3 class="text-lg font-semibold mb-4">🖼️ Immagine Pacchetto</h3>
                                
                                <div>
                                    <label for="package_image" class="block text-sm font-medium text-gray-700 mb-2">Immagine (opzionale)</label>
                                    <input type="file" name="package_image" id="package_image" 
                                           accept="image/jpeg,image/jpg,image/png,image/webp"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                                    <p class="text-xs text-gray-500 mt-1">JPG, PNG, WebP - Max 2MB</p>
                                    
                                    <?php if ($editPackage && !empty($editPackage['image_path'])): ?>
                                    <div class="mt-3 flex items-center gap-3">
                                        <img src="../<?php echo htmlspecialchars($editPackage['image_path']); ?>" 
                                             alt="Immagine corrente" class="w-16 h-16 object-cover rounded-lg border">
                                        <span class="text-sm text-gray-600">Immagine corrente</span>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Right Column -->
                        <div class="space-y-6">
                            <!-- Features -->
                            <div class="bg-gray-50 p-6 rounded-lg">
                                <h3 class="text-lg font-semibold mb-4">✨ Funzionalità Incluse</h3>
                                
                                <div>
                                    <label for="features" class="block text-sm font-medium text-gray-700 mb-2">Lista Funzionalità</label>
                                    <textarea name="features" id="features" rows="8"
                                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                                              placeholder="Una funzionalità per riga, es:&#10;Scheda attività base&#10;Contatti e orari&#10;Visibilità nella ricerca&#10;Foto illimitate"><?php 
                                    if ($editPackage) {
                                        $features = json_decode($editPackage['features'], true) ?: [];
                                        echo htmlspecialchars(implode("\n", $features));
                                    }
                                    ?></textarea>
                                    <p class="text-xs text-gray-500 mt-1">Una funzionalità per riga</p>
                                </div>
                            </div>

                            <!-- Stripe Integration -->
                            <div class="bg-gray-50 p-6 rounded-lg">
                                <h3 class="text-lg font-semibold mb-4">💳 Integrazione Stripe</h3>
                                
                                <div>
                                    <label for="stripe_price_id" class="block text-sm font-medium text-gray-700 mb-2">Stripe Price ID</label>
                                    <input type="text" name="stripe_price_id" id="stripe_price_id"
                                           value="<?php echo htmlspecialchars($editPackage['stripe_price_id'] ?? ''); ?>"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                                           placeholder="price_1234567890abcdef">
                                    <p class="text-xs text-gray-500 mt-1">ID del prezzo creato in Stripe Dashboard</p>
                                </div>
                            </div>

                            <!-- Status -->
                            <div class="bg-gray-50 p-6 rounded-lg">
                                <h3 class="text-lg font-semibold mb-4">⚙️ Stato</h3>
                                
                                <label class="flex items-center">
                                    <input type="checkbox" name="is_active" value="1" 
                                           <?php echo ($editPackage['is_active'] ?? 1) ? 'checked' : ''; ?>
                                           class="h-4 w-4 text-purple-600 focus:ring-purple-500 border-gray-300 rounded">
                                    <span class="ml-2 text-sm text-gray-700">Pacchetto attivo e visibile agli utenti</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="flex items-center justify-between pt-6 border-t border-gray-200">
                        <a href="gestione-pacchetti.php" class="text-gray-600 hover:text-gray-800 font-medium">
                            ← Torna alla lista
                        </a>
                        <div class="flex items-center space-x-4">
                            <a href="gestione-pacchetti.php" class="px-4 py-2 text-gray-600 hover:text-gray-800 font-medium">
                                Annulla
                            </a>
                            <button type="submit" class="bg-purple-600 hover:bg-purple-700 text-white px-6 py-2 rounded-lg font-medium transition-colors">
                                <?php echo $action === 'create' ? 'Crea Pacchetto' : 'Salva Modifiche'; ?>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
            <?php endif; ?>
        </main>
    </div>

    <!-- JavaScript -->
    <script>
        // Inizializza Lucide icons
        lucide.createIcons();

        // Auto-generate slug from name
        document.getElementById('name')?.addEventListener('input', function() {
            // You can add slug generation logic here if needed
        });

        // Validate price
        document.getElementById('price')?.addEventListener('change', function() {
            if (this.value < 0) {
                this.value = 0;
            }
        });

        // Features counter
        const featuresTextarea = document.getElementById('features');
        if (featuresTextarea) {
            featuresTextarea.addEventListener('input', function() {
                const lines = this.value.split('\n').filter(line => line.trim().length > 0);
                console.log(`${lines.length} funzionalità inserite`);
            });
        }
    </script>
</body>
</html>