<?php
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/ContentManagerSimple.php';

$contentManager = new ContentManagerSimple();

$form_submitted = false;
$form_error = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validazione CSRF
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!validateCSRFToken($csrf_token)) {
        $form_error = true;
        $error_message = 'Token di sicurezza non valido.';
    } else {
        // Sanitizzazione e validazione input
        $place_name = sanitize($_POST['place_name'] ?? '');
        $location = sanitize($_POST['location'] ?? '');
        $description = sanitize($_POST['description'] ?? '');
        $user_name = sanitize($_POST['user_name'] ?? '');
        $user_email = filter_var($_POST['user_email'] ?? '', FILTER_SANITIZE_EMAIL);
        
        // Validazione lunghezza
        $validation_errors = [];
        if (strlen($place_name) > 200) $validation_errors[] = 'Nome luogo troppo lungo';
        if (strlen($location) > 200) $validation_errors[] = 'Località troppo lunga';
        if (strlen($description) > 2000) $validation_errors[] = 'Descrizione troppo lunga';
        if (strlen($user_name) > 100) $validation_errors[] = 'Nome utente troppo lungo';
        if (strlen($user_email) > 255) $validation_errors[] = 'Email troppo lunga';

        if (empty($validation_errors) && !empty($place_name) && !empty($location) && !empty($description) && !empty($user_name) && !empty($user_email) && filter_var($user_email, FILTER_VALIDATE_EMAIL)) {
            try {
                $db = new Database();
                $db->createPlaceSuggestion($place_name, $description, $location, $user_name, $user_email);
                $form_submitted = true;
            } catch (Exception $e) {
                error_log('Errore salvataggio suggerimento: ' . $e->getMessage());
                $form_error = true;
                $error_message = 'Errore durante il salvataggio. Riprova più tardi.';
            }
        } else {
            $form_error = true;
            $error_message = !empty($validation_errors) ? implode(', ', $validation_errors) : 'Per favore, compila tutti i campi correttamente.';
        }
    } // Chiude else CSRF
}
?>
<!DOCTYPE html>
<html <?php echo $contentManager->getLanguageAttributes(); ?>>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($contentManager->getText('suggest-place-title', 'Suggerisci un Luogo')); ?> - <?php echo htmlspecialchars($contentManager->getText('site-name', 'Passione Calabria')); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-gray-100">
    <?php include 'includes/header.php'; ?>

    <main class="container mx-auto px-4 py-8">
        <h1 class="text-4xl font-bold text-center text-gray-800 mb-8"><?php echo htmlspecialchars($contentManager->getText('suggest-place-title', 'Suggerisci un Luogo')); ?></h1>
        <div class="max-w-2xl mx-auto bg-white p-8 rounded-lg shadow-lg">
            <?php if ($form_submitted): ?>
                <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4" role="alert">
                    <p class="font-bold"><?php echo htmlspecialchars($contentManager->getText('thank-you-suggestion', 'Grazie per il tuo suggerimento!')); ?></p>
                    <p><?php echo htmlspecialchars($contentManager->getText('team-review-soon', 'Il nostro team lo esaminerà al più presto.')); ?></p>
                </div>
            <?php else: ?>
                <p class="text-gray-600 mb-6">Conosci un luogo speciale in Calabria che dovremmo assolutamente includere nel nostro portale? Segnalacelo compilando il modulo qui sotto!</p>
                <?php if ($form_error): ?>
                    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
                        <p><?php echo htmlspecialchars($error_message ?? 'Per favore, compila tutti i campi correttamente.'); ?></p>
                    </div>
                <?php endif; ?>
                <form action="suggerisci.php" method="POST">
                    <?php echo getCSRFTokenField(); ?>
                    <div class="mb-4">
                        <label for="place_name" class="block text-gray-700 font-bold mb-2">Nome del Luogo</label>
                        <input type="text" name="place_name" id="place_name" class="w-full px-3 py-2 border rounded-lg" required>
                    </div>
                    <div class="mb-4">
                        <label for="location" class="block text-gray-700 font-bold mb-2">Località (es. Comune, Provincia)</label>
                        <input type="text" name="location" id="location" class="w-full px-3 py-2 border rounded-lg" required>
                    </div>
                    <div class="mb-4">
                        <label for="description" class="block text-gray-700 font-bold mb-2">Descrizione</label>
                        <textarea name="description" id="description" rows="5" class="w-full px-3 py-2 border rounded-lg" required></textarea>
                    </div>
                    <hr class="my-6">
                    <div class="mb-4">
                        <label for="user_name" class="block text-gray-700 font-bold mb-2">Il tuo Nome</label>
                        <input type="text" name="user_name" id="user_name" class="w-full px-3 py-2 border rounded-lg" required>
                    </div>
                    <div class="mb-4">
                        <label for="user_email" class="block text-gray-700 font-bold mb-2">La tua Email</label>
                        <input type="email" name="user_email" id="user_email" class="w-full px-3 py-2 border rounded-lg" required>
                    </div>
                    <div class="text-center">
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg">Invia Suggerimento</button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>
    <script>
        lucide.createIcons();
    </script>
</body>
</html>
