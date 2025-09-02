<?php
require_once '../includes/config.php';
require_once '../includes/database.php';

// Controlla autenticazione (da implementare)
// // requireLogin(); // DISABILITATO

$db = new Database();

$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? null;

// Gestione delle azioni POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $website = $_POST['website'] ?? '';
    $description = $_POST['description'] ?? '';
    $category_id = $_POST['category_id'] ?? null;
    $province_id = $_POST['province_id'] ?? null;
    $city_id = $_POST['city_id'] ?? null;
    $address = $_POST['address'] ?? '';
    $status = $_POST['status'] ?? 'pending';

    if ($action === 'edit' && $id) {
        $db->updateBusiness($id, $name, $email, $phone, $website, $description, $category_id, $province_id, $city_id, $address, $status);
    } else {
        $db->createBusiness($name, $email, $phone, $website, $description, $category_id, $province_id, $city_id, $address, $status);
    }
    header('Location: business.php');
    exit;
}

if ($action === 'delete' && $id) {
    $db->deleteBusiness($id);
    header('Location: business.php');
    exit;
}

?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestione Business - Admin Panel</title>
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
                <li><a href="province.php" class="flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-gray-700 transition-colors"><i data-lucide="map-pin" class="w-5 h-5"></i><span>Province & Città</span></a></li>
                <li><a href="commenti.php" class="flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-gray-700 transition-colors"><i data-lucide="message-square" class="w-5 h-5"></i><span>Commenti</span></a></li>
                <li><a href="business.php" class="flex items-center space-x-3 px-3 py-2 rounded-lg bg-gray-700 text-white"><i data-lucide="building-2" class="w-5 h-5"></i><span>Business</span></a></li>
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
                <h1 class="text-2xl font-bold text-gray-900">Gestione Business</h1>
                <?php if ($action === 'list'): ?>
                <a href="business.php?action=new" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg">Nuovo Business</a>
                <?php endif; ?>
            </div>
        </header>
        <main class="flex-1 overflow-auto p-6">
            <?php if ($action === 'list'): ?>
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h2 class="text-lg font-semibold mb-4">Elenco Business</h2>
                <table class="w-full">
                    <thead>
                        <tr class="border-b">
                            <th class="text-left py-2">Nome</th>
                            <th class="text-left py-2">Email</th>
                            <th class="text-left py-2">Stato</th>
                            <th class="text-left py-2">Azioni</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $businesses = $db->getBusinesses(null, false); // Get all businesses
                        foreach ($businesses as $business):
                        ?>
                        <tr class="border-b">
                            <td class="py-2"><?php echo htmlspecialchars($business['name']); ?></td>
                            <td class="py-2"><?php echo htmlspecialchars($business['email']); ?></td>
                            <td class="py-2"><?php echo htmlspecialchars($business['status']); ?></td>
                            <td class="py-2">
                                <a href="business.php?action=edit&id=<?php echo $business['id']; ?>" class="text-blue-600 hover:underline">Modifica</a>
                                <a href="business.php?action=delete&id=<?php echo $business['id']; ?>" class="text-red-600 hover:underline ml-4" onclick="return confirm('Sei sicuro di voler eliminare questo business?');">Elimina</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php elseif ($action === 'new' || $action === 'edit'):
                $business = null;
                if ($action === 'edit' && $id) {
                    $business = $db->getBusinessById($id);
                }
                $categories = $db->getCategories();
                $provinces = $db->getProvinces();
            ?>
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h2 class="text-lg font-semibold mb-4"><?php echo $action === 'edit' ? 'Modifica Business' : 'Nuovo Business'; ?></h2>
                <form action="business.php?action=<?php echo $action; ?><?php if ($id) echo '&id='.$id; ?>" method="POST">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label for="name" class="block text-gray-700 font-bold mb-2">Nome</label>
                            <input type="text" name="name" id="name" class="w-full px-3 py-2 border rounded-lg" value="<?php echo htmlspecialchars($business['name'] ?? ''); ?>" required>
                        </div>
                        <div>
                            <label for="email" class="block text-gray-700 font-bold mb-2">Email</label>
                            <input type="email" name="email" id="email" class="w-full px-3 py-2 border rounded-lg" value="<?php echo htmlspecialchars($business['email'] ?? ''); ?>" required>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label for="phone" class="block text-gray-700 font-bold mb-2">Telefono</label>
                            <input type="text" name="phone" id="phone" class="w-full px-3 py-2 border rounded-lg" value="<?php echo htmlspecialchars($business['phone'] ?? ''); ?>">
                        </div>
                        <div>
                            <label for="website" class="block text-gray-700 font-bold mb-2">Sito Web</label>
                            <input type="text" name="website" id="website" class="w-full px-3 py-2 border rounded-lg" value="<?php echo htmlspecialchars($business['website'] ?? ''); ?>">
                        </div>
                    </div>
                    <div class="mb-4">
                        <label for="description" class="block text-gray-700 font-bold mb-2">Descrizione</label>
                        <textarea name="description" id="description" rows="5" class="w-full px-3 py-2 border rounded-lg"><?php echo htmlspecialchars($business['description'] ?? ''); ?></textarea>
                    </div>
                    <div class="mb-4">
                        <label for="address" class="block text-gray-700 font-bold mb-2">Indirizzo</label>
                        <input type="text" name="address" id="address" class="w-full px-3 py-2 border rounded-lg" value="<?php echo htmlspecialchars($business['address'] ?? ''); ?>">
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                        <div>
                            <label for="category_id" class="block text-gray-700 font-bold mb-2">Categoria</label>
                            <select name="category_id" id="category_id" class="w-full px-3 py-2 border rounded-lg">
                                <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>" <?php if (isset($business) && $business['category_id'] == $category['id']) echo 'selected'; ?>>
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label for="province_id" class="block text-gray-700 font-bold mb-2">Provincia</label>
                            <select name="province_id" id="province_id" class="w-full px-3 py-2 border rounded-lg">
                                <option value="">Nessuna</option>
                                <?php foreach ($provinces as $province): ?>
                                <option value="<?php echo $province['id']; ?>" <?php if (isset($business) && $business['province_id'] == $province['id']) echo 'selected'; ?>>
                                    <?php echo htmlspecialchars($province['name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label for="status" class="block text-gray-700 font-bold mb-2">Stato</label>
                            <select name="status" id="status" class="w-full px-3 py-2 border rounded-lg">
                                <option value="pending" <?php if (isset($business) && $business['status'] === 'pending') echo 'selected'; ?>>In attesa</option>
                                <option value="approved" <?php if (isset($business) && $business['status'] === 'approved') echo 'selected'; ?>>Approvato</option>
                                <option value="rejected" <?php if (isset($business) && $business['status'] === 'rejected') echo 'selected'; ?>>Rifiutato</option>
                                <option value="suspended" <?php if (isset($business) && $business['status'] === 'suspended') echo 'selected'; ?>>Sospeso</option>
                            </select>
                        </div>
                    </div>
                    <div class="text-right">
                        <a href="business.php" class="text-gray-600 hover:underline mr-4">Annulla</a>
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg">Salva Business</button>
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
