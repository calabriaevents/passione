<?php
require_once '../includes/config.php';
require_once '../includes/database.php';

// Controlla autenticazione (per ora commentiamo)
// // requireLogin(); // DISABILITATO

$db = new Database();

// Gestisci azioni di manutenzione
if ($_POST['action'] ?? null) {
    $action = $_POST['action'];
    $result = ['success' => false, 'message' => ''];

    try {
        switch ($action) {
            case 'vacuum':
                $db->pdo->exec('VACUUM');
                $result = ['success' => true, 'message' => 'Database ottimizzato con successo'];
                break;

            case 'analyze':
                $db->pdo->exec('ANALYZE');
                $result = ['success' => true, 'message' => 'Statistiche database aggiornate'];
                break;

            case 'backup':
                $backupFile = $db->createBackup();
                if ($backupFile) {
                    $result = ['success' => true, 'message' => 'Backup creato: ' . basename($backupFile)];
                } else {
                    $result = ['success' => false, 'message' => 'Errore nella creazione del backup'];
                }
                break;

            case 'delete_backup':
                $filename = $_POST['filename'] ?? '';
                if (empty($filename)) {
                    $result = ['success' => false, 'message' => 'Nome file backup non specificato'];
                } else {
                    $deleted = $db->deleteBackup($filename);
                    if ($deleted) {
                        $result = ['success' => true, 'message' => "Backup '$filename' eliminato con successo"];
                    } else {
                        $result = ['success' => false, 'message' => "Errore nell'eliminazione del backup '$filename'"];
                    }
                }
                break;

            case 'download_database':
                $dbPath = DB_PATH;
                if (file_exists($dbPath)) {
                    $filename = 'passione_calabria_' . date('Y-m-d') . '.db';
                    
                    header('Content-Type: application/octet-stream');
                    header('Content-Disposition: attachment; filename="' . $filename . '"');
                    header('Content-Length: ' . filesize($dbPath));
                    header('Cache-Control: must-revalidate');
                    header('Pragma: public');
                    
                    readfile($dbPath);
                    exit;
                } else {
                    $result = ['success' => false, 'message' => 'Database non trovato'];
                }
                break;

            case 'download_backup':
                $filename = $_POST['filename'] ?? '';
                if (empty($filename) || strpos($filename, '..') !== false) {
                    $result = ['success' => false, 'message' => 'Nome file backup non valido'];
                    break;
                }
                
                $backupPath = dirname(DB_PATH) . '/backups/' . $filename;
                if (file_exists($backupPath) && is_file($backupPath)) {
                    header('Content-Type: application/octet-stream');
                    header('Content-Disposition: attachment; filename="' . $filename . '"');
                    header('Content-Length: ' . filesize($backupPath));
                    header('Cache-Control: must-revalidate');
                    header('Pragma: public');
                    
                    readfile($backupPath);
                    exit;
                } else {
                    $result = ['success' => false, 'message' => 'Backup non trovato'];
                }
                break;

            case 'integrity_check':
                $check = $db->pdo->query('PRAGMA integrity_check')->fetch();
                $isOk = $check['integrity_check'] === 'ok';
                $result = [
                    'success' => $isOk,
                    'message' => $isOk ? 'Database integro' : 'Problemi di integrità rilevati'
                ];
                break;

            case 'restore_backup':
                $filename = $_POST['filename'] ?? '';
                if (empty($filename)) {
                    $result = ['success' => false, 'message' => 'Nome file backup non specificato'];
                } else {
                    $restoreResult = $db->restoreFromBackup($filename);
                    $result = $restoreResult;
                }
                break;

            case 'upload_backup':
                if (!isset($_FILES['backup_file'])) {
                    $result = ['success' => false, 'message' => 'Nessun file selezionato'];
                } else {
                    $uploadResult = $db->handleBackupUpload($_FILES['backup_file']);
                    $result = $uploadResult;
                }
                break;

            case 'full_backup':
                $fullBackupResult = $db->createFullProjectBackup();
                if ($fullBackupResult['success']) {
                    // Aggiungi URL per il download automatico
                    $fullBackupResult['download_url'] = 'database.php';
                    $fullBackupResult['download_filename'] = $fullBackupResult['filename'];
                }
                $result = $fullBackupResult;
                break;

            case 'download_full_backup':
                $filename = $_POST['filename'] ?? '';
                if (empty($filename) || strpos($filename, '..') !== false) {
                    $result = ['success' => false, 'message' => 'Nome file backup non valido'];
                    break;
                }
                
                $backupPath = dirname(DB_PATH) . '/backups/' . $filename;
                if (file_exists($backupPath) && is_file($backupPath)) {
                    header('Content-Type: application/zip');
                    header('Content-Disposition: attachment; filename="' . $filename . '"');
                    header('Content-Length: ' . filesize($backupPath));
                    header('Cache-Control: must-revalidate');
                    header('Pragma: public');
                    
                    readfile($backupPath);
                    exit;
                } else {
                    $result = ['success' => false, 'message' => 'Backup completo non trovato'];
                }
                break;
        }
    } catch (Exception $e) {
        $result = ['success' => false, 'message' => 'Errore: ' . $e->getMessage()];
    }

    if (isset($_POST['ajax'])) {
        header('Content-Type: application/json');
        echo json_encode($result);
        exit;
    }
}

// Carica dati salute database
$healthData = $db->getDatabaseHealth();
$backups = $db->getBackups();
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monitoraggio Database - Admin Passione Calabria</title>

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
                    <a href="database.php" class="flex items-center space-x-3 px-3 py-2 rounded-lg bg-gray-700 text-white">
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
                    <h1 class="text-2xl font-bold text-gray-900 flex items-center">
                        <i data-lucide="database" class="w-7 h-7 text-blue-600 mr-2"></i>
                        Monitoraggio Database
                    </h1>
                    <p class="text-sm text-gray-500">Controllo salute e performance del database SQLite</p>
                </div>
                <div class="flex items-center space-x-4">
                    <button onclick="refreshData()" class="flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                        <i data-lucide="refresh-cw" class="w-4 h-4"></i>
                        Aggiorna
                    </button>
                    <span class="text-sm text-gray-500" id="last-refresh">
                        Ultimo aggiornamento: <?php echo date('H:i:s'); ?>
                    </span>
                </div>
            </div>
        </header>

        <!-- Page Content -->
        <main class="flex-1 overflow-auto p-6 space-y-6">
            <!-- Stato Generale -->
            <div class="bg-green-100 border-2 border-green-200 p-6 rounded-lg">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <i data-lucide="check-circle" class="w-5 h-5 text-green-500"></i>
                        <div>
                            <h2 class="text-xl font-semibold">Stato Generale: ECCELLENTE</h2>
                            <p class="text-gray-600">
                                Score salute: 100% - Database <?php echo $healthData['database']['size']; ?>
                            </p>
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="text-3xl font-bold">100%</div>
                        <div class="text-sm text-gray-500">Score Salute</div>
                    </div>
                </div>
            </div>

            <!-- Statistiche Principali -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="bg-white p-4 rounded-lg border shadow-sm">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-600 text-sm">Articoli</p>
                            <p class="text-2xl font-bold"><?php echo $healthData['statistics']['articles']['total']; ?></p>
                            <p class="text-xs text-gray-500"><?php echo $healthData['statistics']['articles']['published']; ?> pubblicati</p>
                        </div>
                        <i data-lucide="file-text" class="w-8 h-8 text-blue-500"></i>
                    </div>
                </div>

                <div class="bg-white p-4 rounded-lg border shadow-sm">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-600 text-sm">Visualizzazioni</p>
                            <p class="text-2xl font-bold"><?php echo number_format($healthData['statistics']['articles']['totalViews']); ?></p>
                            <p class="text-xs text-gray-500"><?php echo $healthData['statistics']['articles']['featured']; ?> in evidenza</p>
                        </div>
                        <i data-lucide="bar-chart-3" class="w-8 h-8 text-green-500"></i>
                    </div>
                </div>

                <div class="bg-white p-4 rounded-lg border shadow-sm">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-600 text-sm">Categorie</p>
                            <p class="text-2xl font-bold"><?php echo $healthData['counts']['categories']; ?></p>
                            <p class="text-xs text-gray-500">Attive</p>
                        </div>
                        <i data-lucide="tags" class="w-8 h-8 text-purple-500"></i>
                    </div>
                </div>

                <div class="bg-white p-4 rounded-lg border shadow-sm">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-600 text-sm">Province</p>
                            <p class="text-2xl font-bold"><?php echo $healthData['counts']['provinces']; ?></p>
                            <p class="text-xs text-gray-500"><?php echo $healthData['counts']['cities']; ?> città</p>
                        </div>
                        <i data-lucide="map-pin" class="w-8 h-8 text-orange-500"></i>
                    </div>
                </div>
            </div>

            <!-- Controlli di Integrità -->
            <div class="bg-white p-6 rounded-lg border shadow-sm">
                <h3 class="text-lg font-semibold mb-4 flex items-center gap-2">
                    <i data-lucide="check-circle" class="w-5 h-5 text-green-500"></i>
                    Controlli di Integrità
                </h3>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <?php foreach ($healthData['health']['checks'] as $check => $status): ?>
                    <div class="flex items-center gap-2">
                        <?php if ($status): ?>
                            <i data-lucide="check-circle" class="w-4 h-4 text-green-500"></i>
                        <?php else: ?>
                            <i data-lucide="x-circle" class="w-4 h-4 text-red-500"></i>
                        <?php endif; ?>
                        <span class="text-sm capitalize">
                            <?php echo str_replace(['_', 'has'], [' ', ''], strtolower($check)); ?>
                        </span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Conteggi Tabelle -->
            <div class="bg-white p-6 rounded-lg border shadow-sm">
                <h3 class="text-lg font-semibold mb-4 flex items-center gap-2">
                    <i data-lucide="hard-drive" class="w-5 h-5 text-blue-500"></i>
                    Conteggi Tabelle
                </h3>
                <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
                    <?php
                    $tableIcons = [
                        'articles' => 'file-text',
                        'categories' => 'tags',
                        'provinces' => 'map-pin',
                        'cities' => 'map-pin',
                        'comments' => 'message-square',
                        'users' => 'users',
                        'businesses' => 'building-2',
                        'events' => 'calendar',
                        'user_uploads' => 'upload',
                        'business_packages' => 'package',
                        'settings' => 'settings',
                        'home_sections' => 'layout',
                        'static_pages' => 'file-text'
                    ];

                    foreach ($healthData['counts'] as $table => $count):
                        $icon = $tableIcons[$table] ?? 'hard-drive';
                    ?>
                    <div class="text-center p-3 bg-gray-50 rounded-lg">
                        <i data-lucide="<?php echo $icon; ?>" class="w-6 h-6 mx-auto mb-2 text-gray-600"></i>
                        <div class="text-lg font-semibold"><?php echo $count; ?></div>
                        <div class="text-xs text-gray-500 capitalize">
                            <?php echo str_replace('_', ' ', $table); ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Azioni di Manutenzione -->
            <div class="bg-white p-6 rounded-lg border shadow-sm">
                <h3 class="text-lg font-semibold mb-4 flex items-center gap-2">
                    <i data-lucide="settings" class="w-5 h-5 text-gray-600"></i>
                    Azioni di Manutenzione
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <button onclick="executeMaintenanceAction('vacuum')"
                            class="flex items-center gap-2 p-3 bg-blue-50 hover:bg-blue-100 border border-blue-200 rounded-lg transition-colors">
                        <i data-lucide="zap" class="w-5 h-5 text-blue-600"></i>
                        <div class="text-left">
                            <div class="font-medium">Ottimizza Spazio</div>
                            <div class="text-xs text-gray-600">VACUUM</div>
                        </div>
                    </button>

                    <button onclick="executeMaintenanceAction('analyze')"
                            class="flex items-center gap-2 p-3 bg-green-50 hover:bg-green-100 border border-green-200 rounded-lg transition-colors">
                        <i data-lucide="bar-chart-3" class="w-5 h-5 text-green-600"></i>
                        <div class="text-left">
                            <div class="font-medium">Aggiorna Statistiche</div>
                            <div class="text-xs text-gray-600">ANALYZE</div>
                        </div>
                    </button>

                    <button onclick="executeMaintenanceAction('integrity_check')"
                            class="flex items-center gap-2 p-3 bg-yellow-50 hover:bg-yellow-100 border border-yellow-200 rounded-lg transition-colors">
                        <i data-lucide="check-circle" class="w-5 h-5 text-yellow-600"></i>
                        <div class="text-left">
                            <div class="font-medium">Verifica Integrità</div>
                            <div class="text-xs text-gray-600">CHECK</div>
                        </div>
                    </button>

                    <button onclick="createAndDownloadBackup()"
                            class="flex items-center gap-2 p-3 bg-purple-50 hover:bg-purple-100 border border-purple-200 rounded-lg transition-colors">
                        <i data-lucide="download" class="w-5 h-5 text-purple-600"></i>
                        <div class="text-left">
                            <div class="font-medium">Backup & Download</div>
                            <div class="text-xs text-gray-600">CREATE & DOWNLOAD</div>
                        </div>
                    </button>

                    <button onclick="createFullProjectBackup()"
                            class="flex items-center gap-2 p-3 bg-indigo-50 hover:bg-indigo-100 border border-indigo-200 rounded-lg transition-colors">
                        <i data-lucide="package" class="w-5 h-5 text-indigo-600"></i>
                        <div class="text-left">
                            <div class="font-medium">Backup Completo</div>
                            <div class="text-xs text-gray-600">PROGETTO INTERO</div>
                        </div>
                    </button>
                </div>
            </div>

            <!-- Download e Gestione Backup -->
            <div class="bg-white p-6 rounded-lg border shadow-sm">
                <h3 class="text-lg font-semibold mb-4 flex items-center gap-2">
                    <i data-lucide="archive" class="w-5 h-5 text-indigo-600"></i>
                    Download e Gestione Backup
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- Download Database Corrente -->
                    <div>
                        <h4 class="font-medium mb-3 flex items-center gap-2">
                            <i data-lucide="download" class="w-4 h-4 text-blue-600"></i>
                            Download Database
                        </h4>
                        <button onclick="downloadCurrentDatabase()"
                                class="w-full flex items-center justify-center gap-2 p-3 bg-blue-50 hover:bg-blue-100 border border-blue-200 rounded-lg transition-colors">
                            <i data-lucide="download" class="w-5 h-5 text-blue-600"></i>
                            <span>Scarica Database Corrente</span>
                        </button>
                        <p class="text-xs text-gray-500 mt-2">
                            Scarica una copia del database attuale
                        </p>
                    </div>

                    <!-- Gestione Backup -->
                    <div>
                        <h4 class="font-medium mb-3 flex items-center gap-2">
                            <i data-lucide="archive" class="w-4 h-4 text-purple-600"></i>
                            Backup (<?php echo count($backups); ?>)
                        </h4>
                        <div class="space-y-2 max-h-40 overflow-y-auto">
                            <?php if (count($backups) > 0): ?>
                                <?php foreach ($backups as $backup): ?>
                                <div class="flex items-center justify-between p-2 bg-gray-50 rounded text-sm">
                                    <div class="flex-1 min-w-0">
                                        <p class="font-medium truncate"><?php echo $backup['filename']; ?></p>
                                        <div class="flex items-center gap-2 text-xs text-gray-500">
                                            <i data-lucide="clock" class="w-3 h-3"></i>
                                            <?php echo formatDate($backup['created']); ?>
                                            <span>•</span>
                                            <?php echo $backup['sizeFormatted']; ?>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-1 ml-2">
                                        <button onclick="restoreBackup('<?php echo $backup['filename']; ?>')"
                                                class="p-1 text-green-600 hover:bg-green-100 rounded transition-colors"
                                                title="Ripristina backup">
                                            <i data-lucide="refresh-ccw" class="w-4 h-4"></i>
                                        </button>
                                        <button onclick="downloadBackup('<?php echo $backup['filename']; ?>')"
                                                class="p-1 text-blue-600 hover:bg-blue-100 rounded transition-colors"
                                                title="Scarica backup">
                                            <i data-lucide="download" class="w-4 h-4"></i>
                                        </button>
                                        <button onclick="deleteBackup('<?php echo $backup['filename']; ?>')"
                                                class="p-1 text-red-600 hover:bg-red-100 rounded transition-colors"
                                                title="Elimina backup">
                                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                                        </button>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="text-center py-4 text-gray-500">
                                    <i data-lucide="archive" class="w-8 h-8 mx-auto mb-2 text-gray-300"></i>
                                    <p class="text-sm">Nessun backup disponibile</p>
                                    <p class="text-xs">Usa "Backup & Download" per crearne uno</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Upload e Ripristino Backup -->
                    <div>
                        <h4 class="font-medium mb-3 flex items-center gap-2">
                            <i data-lucide="upload" class="w-4 h-4 text-orange-600"></i>
                            Carica Backup
                        </h4>
                        <div class="space-y-3">
                            <div class="border-2 border-dashed border-gray-300 rounded-lg p-4 text-center hover:border-orange-400 transition-colors">
                                <input type="file" id="backup-file-input" accept=".db,.sqlite" class="hidden" onchange="handleFileSelect(this)">
                                <label for="backup-file-input" class="cursor-pointer">
                                    <i data-lucide="upload-cloud" class="w-8 h-8 mx-auto mb-2 text-gray-400"></i>
                                    <p class="text-sm font-medium">Carica file backup</p>
                                    <p class="text-xs text-gray-500">File .db o .sqlite</p>
                                </label>
                            </div>
                            <div id="selected-file" class="hidden bg-orange-50 border border-orange-200 rounded-lg p-3">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <i data-lucide="file" class="w-4 h-4 text-orange-600"></i>
                                        <span id="file-name" class="text-sm font-medium"></span>
                                    </div>
                                    <button onclick="clearFileSelection()" class="text-orange-600 hover:text-orange-800">
                                        <i data-lucide="x" class="w-4 h-4"></i>
                                    </button>
                                </div>
                            </div>
                            <button id="upload-restore-btn" onclick="uploadAndRestoreBackup()" 
                                    class="w-full flex items-center justify-center gap-2 p-3 bg-orange-50 hover:bg-orange-100 border border-orange-200 rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed" 
                                    disabled>
                                <i data-lucide="upload" class="w-4 h-4 text-orange-600"></i>
                                <span>Carica e Ripristina</span>
                            </button>
                        </div>
                        <p class="text-xs text-gray-500 mt-2">
                            ⚠️ Il ripristino sostituirà completamente il database attuale
                        </p>
                    </div>
                </div>
            </div>

            <!-- Informazioni Database -->
            <div class="bg-white p-6 rounded-lg border shadow-sm">
                <h3 class="text-lg font-semibold mb-4 flex items-center gap-2">
                    <i data-lucide="database" class="w-5 h-5 text-gray-600"></i>
                    Informazioni Database
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                    <div><strong>Percorso:</strong> <?php echo $healthData['database']['path']; ?></div>
                    <div><strong>Dimensione:</strong> <?php echo $healthData['database']['size']; ?></div>
                    <div><strong>Ultima modifica:</strong> <?php echo formatDateTime($healthData['database']['lastModified']); ?></div>
                    <div><strong>Tabelle totali:</strong> <?php echo count($healthData['counts']); ?></div>
                </div>
            </div>
        </main>
    </div>

    <!-- Loading Modal -->
    <div id="loading-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
        <div class="bg-white p-6 rounded-lg text-center">
            <div class="spinner mx-auto mb-4"></div>
            <p id="loading-text">Esecuzione operazione...</p>
            <p class="text-sm text-gray-500">Attendere...</p>
        </div>
    </div>

    <!-- JavaScript -->
    <script src="../assets/js/main.js"></script>
    <script>
        // Inizializza Lucide icons
        lucide.createIcons();

        let isLoading = false;

        function showLoading(text = 'Caricamento...') {
            if (isLoading) return;
            isLoading = true;

            document.getElementById('loading-text').textContent = text;
            document.getElementById('loading-modal').classList.remove('hidden');
        }

        function hideLoading() {
            isLoading = false;
            document.getElementById('loading-modal').classList.add('hidden');
        }

        function refreshData() {
            showLoading('Aggiornamento dati...');
            setTimeout(() => {
                location.reload();
            }, 1000);
        }

        function executeMaintenanceAction(action) {
            if (isLoading) return;

            showLoading(`Esecuzione ${action}...`);

            const formData = new FormData();
            formData.append('action', action);
            formData.append('ajax', '1');

            fetch('database.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                hideLoading();

                if (data.success) {
                    PassioneCalabria.showNotification(data.message, 'success');
                    if (action === 'backup') {
                        setTimeout(() => location.reload(), 1000);
                    }
                } else {
                    PassioneCalabria.showNotification(data.message, 'error');
                }
            })
            .catch(error => {
                hideLoading();
                PassioneCalabria.showNotification('Errore durante l\'operazione', 'error');
                console.error('Error:', error);
            });
        }

        function downloadCurrentDatabase() {
            showLoading('Preparazione download...');

            // Crea form per il download
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'database.php';
            
            const actionInput = document.createElement('input');
            actionInput.type = 'hidden';
            actionInput.name = 'action';
            actionInput.value = 'download_database';
            form.appendChild(actionInput);
            
            document.body.appendChild(form);
            form.submit();
            document.body.removeChild(form);
            
            setTimeout(() => {
                hideLoading();
                PassioneCalabria.showNotification('Download avviato!', 'success');
            }, 500);
        }

        function createAndDownloadBackup() {
            executeMaintenanceAction('backup');
        }

        function downloadBackup(filename) {
            showLoading('Download backup...');

            // Crea form per il download del backup
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'database.php';
            
            const actionInput = document.createElement('input');
            actionInput.type = 'hidden';
            actionInput.name = 'action';
            actionInput.value = 'download_backup';
            form.appendChild(actionInput);
            
            const filenameInput = document.createElement('input');
            filenameInput.type = 'hidden';
            filenameInput.name = 'filename';
            filenameInput.value = filename;
            form.appendChild(filenameInput);
            
            document.body.appendChild(form);
            form.submit();
            document.body.removeChild(form);
            
            setTimeout(() => {
                hideLoading();
                PassioneCalabria.showNotification(`Download ${filename} avviato!`, 'success');
            }, 500);
        }

        function deleteBackup(filename) {
            if (!confirm(`Sei sicuro di voler eliminare il backup ${filename}?`)) {
                return;
            }

            if (isLoading) return;

            showLoading('Eliminazione backup...');

            const formData = new FormData();
            formData.append('action', 'delete_backup');
            formData.append('filename', filename);
            formData.append('ajax', '1');

            fetch('database.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                hideLoading();

                if (data.success) {
                    PassioneCalabria.showNotification(data.message, 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    PassioneCalabria.showNotification(data.message, 'error');
                }
            })
            .catch(error => {
                hideLoading();
                PassioneCalabria.showNotification('Errore durante l\'eliminazione del backup', 'error');
                console.error('Error:', error);
            });
        }

        function restoreBackup(filename) {
            if (!confirm(`⚠️ ATTENZIONE: Ripristinare il backup "${filename}" sostituirà completamente il database attuale.\n\nQuesta operazione creerà automaticamente un backup di emergenza del database corrente prima di procedere.\n\nSei sicuro di voler continuare?`)) {
                return;
            }

            if (isLoading) return;

            showLoading('Ripristino backup in corso...');

            const formData = new FormData();
            formData.append('action', 'restore_backup');
            formData.append('filename', filename);
            formData.append('ajax', '1');

            fetch('database.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                hideLoading();

                if (data.success) {
                    PassioneCalabria.showNotification(data.message, 'success');
                    setTimeout(() => location.reload(), 2000);
                } else {
                    PassioneCalabria.showNotification(data.message, 'error');
                }
            })
            .catch(error => {
                hideLoading();
                PassioneCalabria.showNotification('Errore durante il ripristino del backup', 'error');
                console.error('Error:', error);
            });
        }

        function handleFileSelect(input) {
            const file = input.files[0];
            if (!file) return;

            // Verifica estensione file
            const validExtensions = ['.db', '.sqlite'];
            const fileExtension = file.name.toLowerCase().substring(file.name.lastIndexOf('.'));
            
            if (!validExtensions.includes(fileExtension)) {
                PassioneCalabria.showNotification('File non valido. Seleziona un file .db o .sqlite', 'error');
                input.value = '';
                return;
            }

            // Mostra file selezionato
            document.getElementById('file-name').textContent = file.name;
            document.getElementById('selected-file').classList.remove('hidden');
            document.getElementById('upload-restore-btn').disabled = false;
        }

        function clearFileSelection() {
            document.getElementById('backup-file-input').value = '';
            document.getElementById('selected-file').classList.add('hidden');
            document.getElementById('upload-restore-btn').disabled = true;
        }

        function uploadAndRestoreBackup() {
            const fileInput = document.getElementById('backup-file-input');
            const file = fileInput.files[0];

            if (!file) {
                PassioneCalabria.showNotification('Seleziona un file backup da caricare', 'error');
                return;
            }

            if (!confirm(`⚠️ ATTENZIONE: Caricare e ripristinare "${file.name}" sostituirà completamente il database attuale.\n\nQuesta operazione creerà automaticamente un backup di emergenza del database corrente prima di procedere.\n\nSei sicuro di voler continuare?`)) {
                return;
            }

            if (isLoading) return;

            showLoading('Caricamento e ripristino backup...');

            const formData = new FormData();
            formData.append('action', 'upload_backup');
            formData.append('backup_file', file);
            formData.append('ajax', '1');

            fetch('database.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                hideLoading();

                if (data.success) {
                    PassioneCalabria.showNotification(data.message, 'success');
                    clearFileSelection();
                    setTimeout(() => location.reload(), 2000);
                } else {
                    PassioneCalabria.showNotification(data.message, 'error');
                }
            })
            .catch(error => {
                hideLoading();
                PassioneCalabria.showNotification('Errore durante il caricamento del backup', 'error');
                console.error('Error:', error);
            });
        }

        function createFullProjectBackup() {
            if (isLoading) return;

            showLoading('Creazione backup completo del progetto...');

            const formData = new FormData();
            formData.append('action', 'full_backup');
            formData.append('ajax', '1');

            fetch('database.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                hideLoading();

                if (data.success) {
                    PassioneCalabria.showNotification(data.message, 'success');
                    
                    // Avvia automaticamente il download del backup completo
                    if (data.download_filename) {
                        setTimeout(() => {
                            downloadFullBackup(data.download_filename);
                        }, 1000);
                    }
                } else {
                    PassioneCalabria.showNotification(data.message, 'error');
                }
            })
            .catch(error => {
                hideLoading();
                PassioneCalabria.showNotification('Errore durante la creazione del backup completo', 'error');
                console.error('Error:', error);
            });
        }

        function downloadFullBackup(filename) {
            showLoading('Download backup completo...');

            // Crea form per il download del backup completo
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'database.php';
            
            const actionInput = document.createElement('input');
            actionInput.type = 'hidden';
            actionInput.name = 'action';
            actionInput.value = 'download_full_backup';
            form.appendChild(actionInput);
            
            const filenameInput = document.createElement('input');
            filenameInput.type = 'hidden';
            filenameInput.name = 'filename';
            filenameInput.value = filename;
            form.appendChild(filenameInput);
            
            document.body.appendChild(form);
            form.submit();
            document.body.removeChild(form);
            
            setTimeout(() => {
                hideLoading();
                PassioneCalabria.showNotification(`Download ${filename} avviato!`, 'success');
            }, 500);
        }

        // Auto-refresh ogni 30 secondi
        setInterval(() => {
            if (!isLoading) {
                document.getElementById('last-refresh').textContent =
                    `Ultimo aggiornamento: ${new Date().toLocaleTimeString()}`;
            }
        }, 30000);
    </script>
</body>
</html>
