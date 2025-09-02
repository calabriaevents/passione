<?php
require_once '../includes/config.php';
require_once '../includes/database.php';

// Controlla autenticazione (da implementare)
// // requireLogin(); // DISABILITATO

$db = new Database();
$message = '';
$messageType = '';

$entity = $_GET['entity'] ?? 'provinces';
$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? null;

// Gestione delle azioni POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($entity === 'provinces') {
        $name = $_POST['name'] ?? '';
        $description = $_POST['description'] ?? '';
        $image_path = $_POST['existing_image_path'] ?? null;

        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../uploads/provinces/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            $filename = uniqid() . '-' . basename($_FILES['image']['name']);
            $target_file = $upload_dir . $filename;
            if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                $image_path = 'uploads/provinces/' . $filename;
            }
        }

        if ($action === 'edit' && $id) {
            if ($db->provinceExists($name, $id)) {
                $message = 'Elemento già esistente: Una provincia con questo nome esiste già';
                $messageType = 'error';
            } else {
                if ($db->updateProvince($id, $name, $description, $image_path)) {
                    $message = 'Provincia aggiornata con successo!';
                    $messageType = 'success';
                } else {
                    $message = 'Errore nell\'aggiornamento della provincia';
                    $messageType = 'error';
                }
            }
        } else {
            if ($db->provinceExists($name)) {
                $message = 'Elemento già esistente: Una provincia con questo nome esiste già';
                $messageType = 'error';
            } else {
                if ($db->createProvince($name, $description, $image_path)) {
                    $message = 'Provincia creata con successo!';
                    $messageType = 'success';
                } else {
                    $message = 'Errore nella creazione della provincia';
                    $messageType = 'error';
                }
            }
        }
    } elseif ($entity === 'cities') {
        $name = $_POST['city_name'] ?? '';
        $province_id = $_POST['city_province_id'] ?? '';
        $description = $_POST['city_description'] ?? '';
        $latitude = !empty($_POST['city_latitude']) ? (float)$_POST['city_latitude'] : null;
        $longitude = !empty($_POST['city_longitude']) ? (float)$_POST['city_longitude'] : null;

        if ($action === 'edit' && $id) {
            if ($db->cityExists($name, $province_id, $id)) {
                $message = 'Elemento già esistente: Una città con questo nome esiste già in questa provincia';
                $messageType = 'error';
            } else {
                if ($db->updateCity($id, $name, $province_id, $description, $latitude, $longitude)) {
                    $message = 'Città aggiornata con successo!';
                    $messageType = 'success';
                } else {
                    $message = 'Errore nell\'aggiornamento della città';
                    $messageType = 'error';
                }
            }
        } else {
            if ($db->cityExists($name, $province_id)) {
                $message = 'Elemento già esistente: Una città con questo nome esiste già in questa provincia';
                $messageType = 'error';
            } else {
                if ($db->createCity($name, $province_id, $description, $latitude, $longitude)) {
                    $message = 'Città creata con successo!';
                    $messageType = 'success';
                } else {
                    $message = 'Errore nella creazione della città';
                    $messageType = 'error';
                }
            }
        }
    }
    
    // Gestione upload immagini galleria
    if ($entity === 'gallery' && isset($_POST['province_id'])) {
        $province_id = (int)$_POST['province_id'];
        $title = $_POST['title'] ?? '';
        $description = $_POST['description'] ?? '';
        
        if (isset($_FILES['gallery_image']) && $_FILES['gallery_image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../uploads/galleries/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            $filename = uniqid() . '-' . basename($_FILES['gallery_image']['name']);
            $target_file = $upload_dir . $filename;
            if (move_uploaded_file($_FILES['gallery_image']['tmp_name'], $target_file)) {
                $image_path = 'uploads/galleries/' . $filename;
                $db->addProvinceGalleryImage($province_id, $image_path, $title, $description);
            }
        }
    }
    
    if (empty($upload_error)) {
        header('Location: province.php?' . http_build_query($_GET));
        exit;
    }
}

if ($action === 'delete' && $id) {
    if ($entity === 'provinces') {
        $db->deleteProvince($id);
    } elseif ($entity === 'cities') {
        $result = $db->deleteCity($id);
        if (!$result) {
            $upload_error = 'Impossibile eliminare la città: ci sono articoli collegati.';
        }
    } elseif ($entity === 'gallery') {
        $db->deleteProvinceGalleryImage($id);
    }
    header('Location: province.php?' . http_build_query(array_filter(['entity' => $_GET['entity'] ?? 'provinces', 'action' => $_GET['back_action'] ?? 'list', 'id' => $_GET['province_id'] ?? null])));
    exit;
}

?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestione Province e Città - Admin Panel</title>
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
                <li><a href="province.php" class="flex items-center space-x-3 px-3 py-2 rounded-lg bg-gray-700 text-white"><i data-lucide="map-pin" class="w-5 h-5"></i><span>Province & Città</span></a></li>
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
            <h1 class="text-2xl font-bold text-gray-900">Gestione Province e Città</h1>
        </header>
        <main class="flex-1 overflow-auto p-6">
            <?php if ($message): ?>
            <div class="mb-6 p-4 rounded-lg <?php echo $messageType === 'success' ? 'bg-green-100 text-green-700 border border-green-200' : 'bg-red-100 text-red-700 border border-red-200'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
            <?php endif; ?>
            
            <div class="bg-white rounded-lg shadow-sm p-6">
                <div class="mb-4 border-b border-gray-200">
                    <nav class="flex space-x-4">
                        <a href="?entity=provinces" class="py-2 px-4 <?php if ($entity === 'provinces') echo 'border-b-2 border-blue-600 font-semibold'; ?>">Province</a>
                        <a href="?entity=cities" class="py-2 px-4 <?php if ($entity === 'cities') echo 'border-b-2 border-blue-600 font-semibold'; ?>">Città</a>
                        <?php if ($entity === 'gallery'): ?>
                        <span class="py-2 px-4 border-b-2 border-blue-600 font-semibold">Galleria Provincia</span>
                        <?php endif; ?>
                    </nav>
                </div>

                <?php if ($entity === 'provinces'): ?>
                    <?php if ($action === 'list'): ?>
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-lg font-semibold">Elenco Province</h2>
                        <a href="?entity=provinces&action=new" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg">Nuova Provincia</a>
                    </div>
                    <table class="w-full">
                        <thead>
                            <tr class="border-b">
                                <th class="text-left py-2">Nome</th>
                                <th class="text-left py-2">Descrizione</th>
                                <th class="text-left py-2">Azioni</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $provinces = $db->getProvinces();
                            foreach ($provinces as $province):
                            ?>
                            <tr class="border-b">
                                <td class="py-2"><?php echo htmlspecialchars($province['name']); ?></td>
                                <td class="py-2"><?php echo htmlspecialchars($province['description']); ?></td>
                                <td class="py-2">
                                    <a href="?entity=provinces&action=edit&id=<?php echo $province['id']; ?>" class="text-blue-600 hover:underline">Modifica</a>
                                    <a href="?entity=gallery&action=manage&province_id=<?php echo $province['id']; ?>" class="text-green-600 hover:underline ml-4">Galleria</a>
                                    <a href="?entity=provinces&action=delete&id=<?php echo $province['id']; ?>" class="text-red-600 hover:underline ml-4" onclick="return confirm('Sei sicuro di voler eliminare questa provincia?');">Elimina</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php elseif ($action === 'new' || $action === 'edit'):
                        $province = null;
                        if ($action === 'edit' && $id) {
                            $province = $db->getProvinceById($id);
                        }
                    ?>
                    <h2 class="text-lg font-semibold mb-4"><?php echo $action === 'edit' ? 'Modifica Provincia' : 'Nuova Provincia'; ?></h2>
                    <form action="?entity=provinces&action=<?php echo $action; ?><?php if ($id) echo '&id='.$id; ?>" method="POST" enctype="multipart/form-data">
                        <div class="mb-4">
                            <label for="name" class="block text-gray-700 font-bold mb-2">Nome</label>
                            <input type="text" name="name" id="name" class="w-full px-3 py-2 border rounded-lg" value="<?php echo htmlspecialchars($province['name'] ?? ''); ?>" required>
                        </div>
                        <div class="mb-4">
                            <label for="description" class="block text-gray-700 font-bold mb-2">Descrizione</label>
                            <textarea name="description" id="description" rows="3" class="w-full px-3 py-2 border rounded-lg"><?php echo htmlspecialchars($province['description'] ?? ''); ?></textarea>
                        </div>
                        <div class="mb-4">
                            <label for="image" class="block text-gray-700 font-bold mb-2">Immagine</label>
                            <input type="file" name="image" id="image" class="w-full px-3 py-2 border rounded-lg">
                            <?php if (isset($province['image_path'])): ?>
                            <img src="../<?php echo htmlspecialchars($province['image_path']); ?>" alt="Immagine provincia" class="w-32 h-32 mt-2">
                            <input type="hidden" name="existing_image_path" value="<?php echo htmlspecialchars($province['image_path']); ?>">
                            <?php endif; ?>
                        </div>
                        <div class="text-right">
                            <a href="?entity=provinces" class="text-gray-600 hover:underline mr-4">Annulla</a>
                            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg">Salva Provincia</button>
                        </div>
                    </form>
                    <?php endif; ?>
                <?php elseif ($entity === 'cities'): ?>
                    <?php if ($action === 'list'): ?>
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-lg font-semibold">Elenco Città</h2>
                        <a href="?entity=cities&action=new" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded-lg">Nuova Città</a>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b">
                                    <th class="text-left py-2">Nome</th>
                                    <th class="text-left py-2">Provincia</th>
                                    <th class="text-left py-2">Coordinate</th>
                                    <th class="text-left py-2">Descrizione</th>
                                    <th class="text-left py-2">Articoli</th>
                                    <th class="text-left py-2">Azioni</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $cities = $db->getCities();
                                foreach ($cities as $city):
                                    $articleCount = $db->getArticleCountByCity($city['id']);
                                ?>
                                <tr class="border-b hover:bg-gray-50">
                                    <td class="py-3 font-medium"><?php echo htmlspecialchars($city['name']); ?></td>
                                    <td class="py-3"><?php echo htmlspecialchars($city['province_name']); ?></td>
                                    <td class="py-3">
                                        <?php if ($city['latitude'] && $city['longitude']): ?>
                                        <span class="text-green-600 text-sm">
                                            <i data-lucide="map-pin" class="w-4 h-4 inline mr-1"></i>
                                            <?php echo number_format($city['latitude'], 3); ?>, <?php echo number_format($city['longitude'], 3); ?>
                                        </span>
                                        <?php else: ?>
                                        <span class="text-gray-400 text-sm">Non disponibili</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-3"><?php echo htmlspecialchars(substr($city['description'] ?: 'Nessuna descrizione', 0, 50)); ?>...</td>
                                    <td class="py-3">
                                        <span class="bg-blue-100 text-blue-800 px-2 py-1 rounded-full text-sm">
                                            <?php echo $articleCount; ?>
                                        </span>
                                    </td>
                                    <td class="py-3">
                                        <a href="?entity=cities&action=edit&id=<?php echo $city['id']; ?>" class="text-blue-600 hover:underline mr-3">Modifica</a>
                                        <a href="?entity=cities&action=delete&id=<?php echo $city['id']; ?>" class="text-red-600 hover:underline" onclick="return confirm('Sei sicuro di voler eliminare questa città?');">Elimina</a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php elseif ($action === 'new' || $action === 'edit'): 
                        $cityData = null;
                        if ($action === 'edit' && $id) {
                            $cityData = $db->getCityById($id);
                        }
                    ?>
                    <div class="max-w-2xl">
                        <div class="flex items-center mb-6">
                            <a href="?entity=cities" class="text-gray-600 hover:text-gray-800 mr-4">
                                <i data-lucide="arrow-left" class="w-5 h-5"></i>
                            </a>
                            <h2 class="text-lg font-semibold"><?php echo $action === 'edit' ? 'Modifica Città' : 'Nuova Città'; ?></h2>
                        </div>
                        
                        <form action="?entity=cities&action=<?php echo $action; ?><?php if ($id) echo '&id='.$id; ?>" method="POST" class="space-y-6">
                            <div>
                                <label for="city_name" class="block text-sm font-medium text-gray-700 mb-2">Nome Città *</label>
                                <input type="text" name="city_name" id="city_name" required 
                                       value="<?php echo htmlspecialchars($cityData['name'] ?? ''); ?>"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            </div>
                            
                            <div>
                                <label for="city_province_id" class="block text-sm font-medium text-gray-700 mb-2">Provincia *</label>
                                <select name="city_province_id" id="city_province_id" required 
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                    <option value="">Seleziona una provincia</option>
                                    <?php 
                                    $provinces = $db->getProvinces();
                                    foreach ($provinces as $prov): 
                                    ?>
                                    <option value="<?php echo $prov['id']; ?>" <?php echo (isset($cityData['province_id']) && $cityData['province_id'] == $prov['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($prov['name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div>
                                <label for="city_description" class="block text-sm font-medium text-gray-700 mb-2">Descrizione</label>
                                <textarea name="city_description" id="city_description" rows="3" 
                                          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" 
                                          placeholder="Breve descrizione della città..."><?php echo htmlspecialchars($cityData['description'] ?? ''); ?></textarea>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="city_latitude" class="block text-sm font-medium text-gray-700 mb-2">Latitudine</label>
                                    <input type="number" name="city_latitude" id="city_latitude" step="any" 
                                           value="<?php echo $cityData['latitude'] ?? ''; ?>"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" 
                                           placeholder="es. 39.0847">
                                </div>
                                <div>
                                    <label for="city_longitude" class="block text-sm font-medium text-gray-700 mb-2">Longitudine</label>
                                    <input type="number" name="city_longitude" id="city_longitude" step="any" 
                                           value="<?php echo $cityData['longitude'] ?? ''; ?>"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" 
                                           placeholder="es. 17.1252">
                                </div>
                            </div>
                            
                            <div class="bg-blue-50 p-4 rounded-lg">
                                <h4 class="font-medium text-blue-900 mb-2">💡 Suggerimenti per le Coordinate</h4>
                                <ul class="text-blue-700 text-sm space-y-1">
                                    <li>• Usa Google Maps per trovare le coordinate precise</li>
                                    <li>• Clic destro sulla mappa → "Cosa c'è qui?" per vedere lat/lng</li>
                                    <li>• Le coordinate sono opzionali ma utili per mappe e geolocalizzazione</li>
                                </ul>
                            </div>
                            
                            <div class="flex justify-end space-x-4">
                                <a href="?entity=cities" class="px-4 py-2 text-gray-600 hover:text-gray-800">Annulla</a>
                                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg font-semibold">
                                    <?php echo $action === 'edit' ? 'Aggiorna' : 'Crea'; ?> Città
                                </button>
                            </div>
                        </form>
                    </div>
                    <?php endif; ?>
                <?php elseif ($entity === 'gallery'): ?>
                    <?php 
                    $province_id = $_GET['province_id'] ?? null;
                    if ($province_id) {
                        $province = $db->getProvinceById($province_id);
                        $gallery_images = $db->getProvinceGalleryImages($province_id);
                    }
                    ?>
                    <?php if ($action === 'manage' && $province_id): ?>
                    <div class="flex justify-between items-center mb-6">
                        <div>
                            <h2 class="text-lg font-semibold">Galleria: <?php echo htmlspecialchars($province['name']); ?></h2>
                            <p class="text-gray-600">Gestisci le immagini della galleria per questa provincia</p>
                        </div>
                        <div class="flex gap-2">
                            <a href="?entity=gallery&action=add&province_id=<?php echo $province_id; ?>" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded-lg">
                                <i data-lucide="plus" class="w-4 h-4 inline mr-1"></i>
                                Aggiungi Immagine
                            </a>
                            <a href="?entity=provinces" class="bg-gray-600 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded-lg">
                                <i data-lucide="arrow-left" class="w-4 h-4 inline mr-1"></i>
                                Torna alle Province
                            </a>
                        </div>
                    </div>
                    
                    <?php if (empty($gallery_images)): ?>
                    <div class="text-center py-12 bg-gray-50 rounded-lg">
                        <i data-lucide="image-off" class="w-16 h-16 text-gray-400 mx-auto mb-4"></i>
                        <h3 class="text-xl font-semibold text-gray-700 mb-2">Nessuna immagine in galleria</h3>
                        <p class="text-gray-500 mb-6">Aggiungi la prima immagine per iniziare a costruire la galleria di <?php echo htmlspecialchars($province['name']); ?></p>
                        <a href="?entity=gallery&action=add&province_id=<?php echo $province_id; ?>" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg inline-flex items-center">
                            <i data-lucide="plus" class="w-4 h-4 mr-2"></i>
                            Aggiungi Prima Immagine
                        </a>
                    </div>
                    <?php else: ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                        <?php foreach ($gallery_images as $image): ?>
                        <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                            <div class="aspect-[4/3] bg-gray-100">
                                <img src="../<?php echo htmlspecialchars($image['image_path']); ?>" 
                                     alt="<?php echo htmlspecialchars($image['title']); ?>" 
                                     class="w-full h-full object-cover">
                            </div>
                            <div class="p-4">
                                <h3 class="font-semibold text-gray-900 mb-1"><?php echo htmlspecialchars($image['title']); ?></h3>
                                <p class="text-sm text-gray-600 mb-3"><?php echo htmlspecialchars($image['description']); ?></p>
                                <div class="flex justify-between items-center">
                                    <span class="text-xs text-gray-500">
                                        <?php echo date('d/m/Y', strtotime($image['created_at'])); ?>
                                    </span>
                                    <a href="?entity=gallery&action=delete&id=<?php echo $image['id']; ?>&province_id=<?php echo $province_id; ?>&back_action=manage" 
                                       class="text-red-600 hover:text-red-700 font-semibold text-sm" 
                                       onclick="return confirm('Sei sicuro di voler eliminare questa immagine?');">Elimina</a>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php elseif ($action === 'add' && $province_id): ?>
                    <div class="max-w-2xl">
                        <div class="flex items-center mb-6">
                            <a href="?entity=gallery&action=manage&province_id=<?php echo $province_id; ?>" class="text-gray-600 hover:text-gray-800 mr-4">
                                <i data-lucide="arrow-left" class="w-5 h-5"></i>
                            </a>
                            <div>
                                <h2 class="text-lg font-semibold">Aggiungi Immagine alla Galleria</h2>
                                <p class="text-gray-600"><?php echo htmlspecialchars($province['name']); ?></p>
                            </div>
                        </div>
                        
                        <form action="?entity=gallery&action=add&province_id=<?php echo $province_id; ?>" method="POST" enctype="multipart/form-data" class="space-y-6">
                            <input type="hidden" name="province_id" value="<?php echo $province_id; ?>">
                            
                            <div>
                                <label for="gallery_image" class="block text-sm font-medium text-gray-700 mb-2">Immagine *</label>
                                <div class="border-2 border-dashed border-gray-300 rounded-lg p-8 text-center hover:border-gray-400 transition-colors" id="upload-area">
                                    <input type="file" name="gallery_image" id="gallery_image" accept="image/*" class="hidden" onchange="previewGalleryImage(this)" required>
                                    <div id="upload-prompt">
                                        <i data-lucide="upload" class="w-12 h-12 text-gray-400 mx-auto mb-4"></i>
                                        <p class="text-gray-600 mb-2">Clicca per selezionare un'immagine o trascinala qui</p>
                                        <p class="text-sm text-gray-500">PNG, JPG, WebP fino a 5MB</p>
                                        <button type="button" onclick="document.getElementById('gallery_image').click()" class="mt-4 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">
                                            Seleziona Immagine
                                        </button>
                                    </div>
                                    <div id="image-preview" class="hidden">
                                        <img id="preview-img" src="" alt="Anteprima" class="max-w-full max-h-64 mx-auto rounded-lg shadow-sm">
                                        <div class="mt-4">
                                            <button type="button" onclick="removeGalleryPreview()" class="text-red-600 hover:text-red-700 font-semibold">
                                                Rimuovi Immagine
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div>
                                <label for="title" class="block text-sm font-medium text-gray-700 mb-2">Titolo *</label>
                                <input type="text" name="title" id="title" required 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" 
                                       placeholder="Es: Panorama di <?php echo htmlspecialchars($province['name']); ?>">
                            </div>
                            
                            <div>
                                <label for="description" class="block text-sm font-medium text-gray-700 mb-2">Descrizione</label>
                                <textarea name="description" id="description" rows="3" 
                                          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" 
                                          placeholder="Descrizione opzionale dell'immagine..."></textarea>
                            </div>
                            
                            <div class="flex justify-end space-x-4">
                                <a href="?entity=gallery&action=manage&province_id=<?php echo $province_id; ?>" 
                                   class="px-4 py-2 text-gray-600 hover:text-gray-800">Annulla</a>
                                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg font-semibold">
                                    Aggiungi Immagine
                                </button>
                            </div>
                        </form>
                    </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script>
        lucide.createIcons();
        
        // Funzioni per l'upload delle immagini
        function previewImage(input) {
            if (input.files && input.files[0]) {
                const file = input.files[0];
                
                // Validazione lato client
                const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
                const maxSize = 5 * 1024 * 1024; // 5MB
                
                if (!allowedTypes.includes(file.type.toLowerCase())) {
                    alert('Tipo di file non supportato. Utilizzare JPEG, PNG o WebP.');
                    input.value = '';
                    return;
                }
                
                if (file.size > maxSize) {
                    alert('Il file è troppo grande. Dimensione massima: 5MB.');
                    input.value = '';
                    return;
                }
                
                const reader = new FileReader();
                reader.onload = function(e) {
                    // Nascondi il prompt di upload
                    document.getElementById('upload-prompt').classList.add('hidden');
                    
                    // Nascondi l'immagine esistente se presente
                    const existingImage = document.getElementById('existing-image');
                    if (existingImage) {
                        existingImage.classList.add('hidden');
                    }
                    
                    // Mostra la preview
                    const preview = document.getElementById('image-preview');
                    const previewImg = document.getElementById('preview-img');
                    
                    previewImg.src = e.target.result;
                    preview.classList.remove('hidden');
                }
                reader.readAsDataURL(file);
            }
        }
        
        function removePreview() {
            // Reset input file
            document.getElementById('image').value = '';
            
            // Nascondi preview
            document.getElementById('image-preview').classList.add('hidden');
            
            // Mostra di nuovo il prompt
            document.getElementById('upload-prompt').classList.remove('hidden');
            
            // Mostra di nuovo l'immagine esistente se presente
            const existingImage = document.getElementById('existing-image');
            if (existingImage) {
                existingImage.classList.remove('hidden');
            }
        }
        
        // Drag & Drop functionality
        const uploadArea = document.getElementById('upload-area');
        if (uploadArea) {
            uploadArea.addEventListener('dragover', function(e) {
                e.preventDefault();
                uploadArea.classList.add('border-blue-500', 'bg-blue-50');
            });
            
            uploadArea.addEventListener('dragleave', function(e) {
                e.preventDefault();
                uploadArea.classList.remove('border-blue-500', 'bg-blue-50');
            });
            
            uploadArea.addEventListener('drop', function(e) {
                e.preventDefault();
                uploadArea.classList.remove('border-blue-500', 'bg-blue-50');
                
                const files = e.dataTransfer.files;
                if (files.length > 0) {
                    document.getElementById('image').files = files;
                    previewImage(document.getElementById('image'));
                }
            });
        }
        
        // Funzioni per la gestione delle immagini galleria
        function previewGalleryImage(input) {
            if (input.files && input.files[0]) {
                const file = input.files[0];
                
                // Validazione lato client
                const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
                const maxSize = 5 * 1024 * 1024; // 5MB
                
                if (!allowedTypes.includes(file.type.toLowerCase())) {
                    alert('Tipo di file non supportato. Utilizzare JPEG, PNG o WebP.');
                    input.value = '';
                    return;
                }
                
                if (file.size > maxSize) {
                    alert('Il file è troppo grande. Dimensione massima: 5MB.');
                    input.value = '';
                    return;
                }
                
                const reader = new FileReader();
                reader.onload = function(e) {
                    // Nascondi il prompt di upload
                    document.getElementById('upload-prompt').classList.add('hidden');
                    
                    // Mostra la preview
                    const preview = document.getElementById('image-preview');
                    const previewImg = document.getElementById('preview-img');
                    
                    previewImg.src = e.target.result;
                    preview.classList.remove('hidden');
                }
                reader.readAsDataURL(file);
            }
        }
        
        function removeGalleryPreview() {
            // Reset input file
            const fileInput = document.getElementById('gallery_image');
            if (fileInput) {
                fileInput.value = '';
            }
            
            // Nascondi preview
            const imagePreview = document.getElementById('image-preview');
            if (imagePreview) {
                imagePreview.classList.add('hidden');
            }
            
            // Mostra di nuovo il prompt
            const uploadPrompt = document.getElementById('upload-prompt');
            if (uploadPrompt) {
                uploadPrompt.classList.remove('hidden');
            }
        }
        
        // Drag & Drop per immagini galleria
        const galleryUploadArea = document.getElementById('upload-area');
        if (galleryUploadArea) {
            galleryUploadArea.addEventListener('dragover', function(e) {
                e.preventDefault();
                galleryUploadArea.classList.add('border-blue-500', 'bg-blue-50');
            });
            
            galleryUploadArea.addEventListener('dragleave', function(e) {
                e.preventDefault();
                galleryUploadArea.classList.remove('border-blue-500', 'bg-blue-50');
            });
            
            galleryUploadArea.addEventListener('drop', function(e) {
                e.preventDefault();
                galleryUploadArea.classList.remove('border-blue-500', 'bg-blue-50');
                
                const files = e.dataTransfer.files;
                if (files.length > 0) {
                    const galleryImageInput = document.getElementById('gallery_image');
                    if (galleryImageInput) {
                        galleryImageInput.files = files;
                        previewGalleryImage(galleryImageInput);
                    }
                }
            });
        }
        
        // Auto-nascondere messaggi di successo dopo 5 secondi
        const successMessage = document.querySelector('.bg-green-50');
        if (successMessage) {
            setTimeout(() => {
                successMessage.style.transition = 'opacity 0.5s';
                successMessage.style.opacity = '0';
                setTimeout(() => {
                    successMessage.remove();
                }, 500);
            }, 5000);
        }
    </script>
</body>
</html>
