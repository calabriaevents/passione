<?php
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/ContentManagerSimple.php';

$contentManager = new ContentManagerSimple();
$currentLang = $contentManager->getCurrentLanguage();

$form_submitted = false;
$form_error = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if (!empty($name) && !empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL) && !empty($message)) {
        // Qui andrebbe il codice per inviare l'email
        // Per ora, mostriamo solo un messaggio di successo
        $form_submitted = true;
    } else {
        $form_error = true;
    }
}
?>
<!DOCTYPE html>
<html <?php echo $contentManager->getLanguageAttributes(); ?>>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($contentManager->getText('collaborate-title', 'Collabora')); ?> - <?php echo htmlspecialchars($contentManager->getText('site-name', 'Passione Calabria')); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-gray-100">
    <?php include 'includes/header.php'; ?>

    <main class="container mx-auto px-4 py-8">
        <h1 class="text-4xl font-bold text-center text-gray-800 mb-8"><?php echo htmlspecialchars($contentManager->getText('collaborate-us', 'Collabora con Noi')); ?></h1>
        <div class="max-w-2xl mx-auto bg-white p-8 rounded-lg shadow-lg">
            <?php if ($form_submitted): ?>
                <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4" role="alert">
                    <p class="font-bold"><?php echo htmlspecialchars($contentManager->getText('thank-you', 'Grazie!')); ?></p>
                    <p><?php echo htmlspecialchars($contentManager->getText('collaboration-sent', 'La tua proposta di collaborazione è stata inviata con successo. Ti contatteremo al più presto.')); ?></p>
                </div>
            <?php else: ?>
                <p class="text-gray-600 mb-6"><?php echo htmlspecialchars($contentManager->getText('collaboration-intro', 'Sei un appassionato della Calabria e vuoi contribuire al nostro progetto? Compila il modulo sottostante per inviarci la tua proposta di collaborazione.')); ?></p>
                <?php if ($form_error): ?>
                    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
                        <p><?php echo htmlspecialchars($contentManager->getText('form-validation-error', 'Per favore, compila tutti i campi correttamente.')); ?></p>
                    </div>
                <?php endif; ?>
                <form action="collabora.php" method="POST">
                    <div class="mb-4">
                        <label for="name" class="block text-gray-700 font-bold mb-2"><?php echo htmlspecialchars($contentManager->getText('name-label', 'Nome')); ?></label>
                        <input type="text" name="name" id="name" class="w-full px-3 py-2 border rounded-lg" required>
                    </div>
                    <div class="mb-4">
                        <label for="email" class="block text-gray-700 font-bold mb-2"><?php echo htmlspecialchars($contentManager->getText('email-label', 'Email')); ?></label>
                        <input type="email" name="email" id="email" class="w-full px-3 py-2 border rounded-lg" required>
                    </div>
                    <div class="mb-4">
                        <label for="message" class="block text-gray-700 font-bold mb-2"><?php echo htmlspecialchars($contentManager->getText('message-label', 'Messaggio')); ?></label>
                        <textarea name="message" id="message" rows="5" class="w-full px-3 py-2 border rounded-lg" required></textarea>
                    </div>
                    <div class="text-center">
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg"><?php echo htmlspecialchars($contentManager->getText('send-button', 'Invia')); ?></button>
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
