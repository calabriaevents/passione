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
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $name = $_POST['name'] ?? '';
    $role = $_POST['role'] ?? 'user';
    $status = $_POST['status'] ?? 'active';

    if ($action === 'edit' && $id) {
        $db->updateUser($id, $email, $password, $name, $role, $status);
    } else {
        $db->createUser($email, $password, $name, $role, $status);
    }
    header('Location: utenti.php');
    exit;
}

if ($action === 'delete' && $id) {
    $db->deleteUser($id);
    header('Location: utenti.php');
    exit;
}

?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestione Utenti - Admin Panel</title>
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
                <li><a href="business.php" class="flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-gray-700 transition-colors"><i data-lucide="building-2" class="w-5 h-5"></i><span>Business</span></a></li>
                <li><a href="abbonamenti.php" class="flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-gray-700 transition-colors"><i data-lucide="credit-card" class="w-5 h-5"></i><span>Abbonamenti</span></a></li>
                <li><a href="utenti.php" class="flex items-center space-x-3 px-3 py-2 rounded-lg bg-gray-700 text-white"><i data-lucide="users" class="w-5 h-5"></i><span>Utenti</span></a></li>
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
                <h1 class="text-2xl font-bold text-gray-900">Gestione Utenti</h1>
                <?php if ($action === 'list'): ?>
                <a href="utenti.php?action=new" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg">Nuovo Utente</a>
                <?php endif; ?>
            </div>
        </header>
        <main class="flex-1 overflow-auto p-6">
            <?php if ($action === 'list'): ?>
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h2 class="text-lg font-semibold mb-4">Elenco Utenti</h2>
                <table class="w-full">
                    <thead>
                        <tr class="border-b">
                            <th class="text-left py-2">Nome</th>
                            <th class="text-left py-2">Email</th>
                            <th class="text-left py-2">Ruolo</th>
                            <th class="text-left py-2">Stato</th>
                            <th class="text-left py-2">Azioni</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $users = $db->getUsers();
                        foreach ($users as $user):
                        ?>
                        <tr class="border-b">
                            <td class="py-2"><?php echo htmlspecialchars($user['name']); ?></td>
                            <td class="py-2"><?php echo htmlspecialchars($user['email']); ?></td>
                            <td class="py-2"><?php echo htmlspecialchars($user['role']); ?></td>
                            <td class="py-2"><?php echo htmlspecialchars($user['status']); ?></td>
                            <td class="py-2">
                                <a href="utenti.php?action=edit&id=<?php echo $user['id']; ?>" class="text-blue-600 hover:underline">Modifica</a>
                                <a href="utenti.php?action=delete&id=<?php echo $user['id']; ?>" class="text-red-600 hover:underline ml-4" onclick="return confirm('Sei sicuro di voler eliminare questo utente?');">Elimina</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php elseif ($action === 'new' || $action === 'edit'):
                $user = null;
                if ($action === 'edit' && $id) {
                    $user = $db->getUserById($id);
                }
            ?>
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h2 class="text-lg font-semibold mb-4"><?php echo $action === 'edit' ? 'Modifica Utente' : 'Nuovo Utente'; ?></h2>
                <form action="utenti.php?action=<?php echo $action; ?><?php if ($id) echo '&id='.$id; ?>" method="POST">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label for="name" class="block text-gray-700 font-bold mb-2">Nome</label>
                            <input type="text" name="name" id="name" class="w-full px-3 py-2 border rounded-lg" value="<?php echo htmlspecialchars($user['name'] ?? ''); ?>" required>
                        </div>
                        <div>
                            <label for="email" class="block text-gray-700 font-bold mb-2">Email</label>
                            <input type="email" name="email" id="email" class="w-full px-3 py-2 border rounded-lg" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" required>
                        </div>
                    </div>
                    <div class="mb-4">
                        <label for="password" class="block text-gray-700 font-bold mb-2">Password</label>
                        <input type="password" name="password" id="password" class="w-full px-3 py-2 border rounded-lg" <?php if ($action === 'new') echo 'required'; ?>>
                        <?php if ($action === 'edit'): ?>
                        <p class="text-sm text-gray-500">Lascia vuoto per non modificare la password.</p>
                        <?php endif; ?>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label for="role" class="block text-gray-700 font-bold mb-2">Ruolo</label>
                            <select name="role" id="role" class="w-full px-3 py-2 border rounded-lg">
                                <option value="user" <?php if (isset($user) && $user['role'] === 'user') echo 'selected'; ?>>User</option>
                                <option value="editor" <?php if (isset($user) && $user['role'] === 'editor') echo 'selected'; ?>>Editor</option>
                                <option value="admin" <?php if (isset($user) && $user['role'] === 'admin') echo 'selected'; ?>>Admin</option>
                            </select>
                        </div>
                        <div>
                            <label for="status" class="block text-gray-700 font-bold mb-2">Stato</label>
                            <select name="status" id="status" class="w-full px-3 py-2 border rounded-lg">
                                <option value="active" <?php if (isset($user) && $user['status'] === 'active') echo 'selected'; ?>>Attivo</option>
                                <option value="inactive" <?php if (isset($user) && $user['status'] === 'inactive') echo 'selected'; ?>>Inattivo</option>
                                <option value="banned" <?php if (isset($user) && $user['status'] === 'banned') echo 'selected'; ?>>Bannato</option>
                            </select>
                        </div>
                    </div>
                    <div class="text-right">
                        <a href="utenti.php" class="text-gray-600 hover:underline mr-4">Annulla</a>
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg">Salva Utente</button>
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
