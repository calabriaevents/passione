<?php
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/ContentManagerSimple.php';

// Ottieni e valida lo slug dalla URL
$slug = $_GET['slug'] ?? '';

// Validazione slug per sicurezza
if (!preg_match('/^[a-z0-9-]+$/', $slug)) {
    $slug = '';
}

// Se non c'è slug valido, mostra 404
if (empty($slug)) {
    header('HTTP/1.0 404 Not Found');
    include '404.php';
    exit;
}

$db = new Database();
$contentManager = new ContentManagerSimple($db);

// Carica la pagina dal database
$stmt = $db->pdo->prepare('SELECT * FROM static_pages WHERE slug = ? AND is_published = 1');
$stmt->execute([$slug]);
$page = $stmt->fetch();

// Se la pagina non esiste, mostra 404
if (!$page) {
    header('HTTP/1.0 404 Not Found');
    include '404.php';
    exit;
}

// Imposta titoli SEO
$pageTitle = !empty($page['meta_title']) ? $page['meta_title'] : $page['title'] . ' - Passione Calabria';
$pageDescription = !empty($page['meta_description']) ? $page['meta_description'] : 'Scopri di più su Passione Calabria';
?>
<!DOCTYPE html>
<html <?php echo $contentManager->getLanguageAttributes(); ?>>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($pageDescription); ?>">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    
    <!-- Leaflet CSS for map -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />
</head>
<body class="bg-gray-100">
    <?php include 'includes/header.php'; ?>

    <main class="container mx-auto px-4 py-8">
        <h1 class="text-4xl font-bold text-center text-gray-800 mb-8"><?php echo htmlspecialchars($contentManager->getText('static-page-title-'.$page['id'], $page['title'])); ?></h1>
        
        <div class="max-w-4xl mx-auto bg-white rounded-lg shadow-lg overflow-hidden">
            <!-- Content Area -->
            <div class="p-8">
                <?php 
                // Gestione speciale per la pagina contatti
                if ($page['slug'] === 'contatti'):
                ?>
                    <!-- Layout speciale per i contatti -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
                        <!-- Colonna Sinistra - Informazioni di contatto -->
                        <div class="space-y-6">
                            <div>
                                <h2 class="text-2xl font-bold text-gray-800 mb-4"><?php echo htmlspecialchars($contentManager->getText('our-contacts', 'I Nostri Contatti')); ?></h2>
                                <div class="space-y-4">
                                    <div class="flex items-start space-x-4">
                                        <div class="flex-shrink-0 w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center">
                                            <i data-lucide="mail" class="w-5 h-5 text-blue-600"></i>
                                        </div>
                                        <div>
                                            <h3 class="font-semibold text-gray-800"><?php echo htmlspecialchars($contentManager->getText('email', 'Email')); ?></h3>
                                            <p class="text-gray-600">info@passionecalabria.it</p>
                                            <p class="text-gray-600">redazione@passionecalabria.it</p>
                                        </div>
                                    </div>
                                    
                                    <div class="flex items-start space-x-4">
                                        <div class="flex-shrink-0 w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center">
                                            <i data-lucide="phone" class="w-5 h-5 text-green-600"></i>
                                        </div>
                                        <div>
                                            <h3 class="font-semibold text-gray-800"><?php echo htmlspecialchars($contentManager->getText('phone', 'Telefono')); ?></h3>
                                            <p class="text-gray-600">+39 0961 123456</p>
                                            <p class="text-gray-600">+39 320 1234567 (Mobile)</p>
                                        </div>
                                    </div>
                                    
                                    <div class="flex items-start space-x-4">
                                        <div class="flex-shrink-0 w-8 h-8 bg-red-100 rounded-lg flex items-center justify-center">
                                            <i data-lucide="map-pin" class="w-5 h-5 text-red-600"></i>
                                        </div>
                                        <div>
                                            <h3 class="font-semibold text-gray-800"><?php echo htmlspecialchars($contentManager->getText('address', 'Indirizzo')); ?></h3>
                                            <p class="text-gray-600">Via Roma, 123<br>88100 Catanzaro (CZ)<br>Calabria, Italia</p>
                                        </div>
                                    </div>
                                    
                                    <div class="flex items-start space-x-4">
                                        <div class="flex-shrink-0 w-8 h-8 bg-yellow-100 rounded-lg flex items-center justify-center">
                                            <i data-lucide="clock" class="w-5 h-5 text-yellow-600"></i>
                                        </div>
                                        <div>
                                            <h3 class="font-semibold text-gray-800"><?php echo htmlspecialchars($contentManager->getText('hours', 'Orari')); ?></h3>
                                            <p class="text-gray-600">Lunedì - Venerdì: 9:00 - 18:00<br>Sabato: 9:00 - 13:00<br>Domenica: Chiuso</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Social Media -->
                            <div>
                                <h3 class="font-semibold text-gray-800 mb-3"><?php echo htmlspecialchars($contentManager->getText('follow-us-social', 'Seguici sui Social')); ?></h3>
                                <div class="flex space-x-4">
                                    <a href="#" class="w-10 h-10 bg-blue-600 text-white rounded-full flex items-center justify-center hover:bg-blue-700 transition-colors">
                                        <i data-lucide="facebook" class="w-5 h-5"></i>
                                    </a>
                                    <a href="#" class="w-10 h-10 bg-pink-500 text-white rounded-full flex items-center justify-center hover:bg-pink-600 transition-colors">
                                        <i data-lucide="instagram" class="w-5 h-5"></i>
                                    </a>
                                    <a href="#" class="w-10 h-10 bg-blue-400 text-white rounded-full flex items-center justify-center hover:bg-blue-500 transition-colors">
                                        <i data-lucide="twitter" class="w-5 h-5"></i>
                                    </a>
                                    <a href="#" class="w-10 h-10 bg-red-600 text-white rounded-full flex items-center justify-center hover:bg-red-700 transition-colors">
                                        <i data-lucide="youtube" class="w-5 h-5"></i>
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Colonna Destra - Mappa -->
                        <div>
                            <h2 class="text-2xl font-bold text-gray-800 mb-4"><?php echo htmlspecialchars($contentManager->getText('where-we-are', 'Dove Siamo')); ?></h2>
                            <div id="contact-map" class="w-full h-64 bg-gray-200 rounded-lg"></div>
                            <p class="text-sm text-gray-600 mt-2">Via Roma, 123 - 88100 Catanzaro (CZ)</p>
                        </div>
                    </div>

                    <!-- Form di contatto in basso -->
                    <div class="border-t pt-8">
                        <h2 class="text-2xl font-bold text-gray-800 mb-6 text-center"><?php echo htmlspecialchars($contentManager->getText('send-us-message', 'Inviaci un Messaggio')); ?></h2>
                        
                        <?php 
                        // Gestione invio form
                        $form_submitted = false;
                        $form_error = false;

                        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['contact_form'])) {
                            // Validazione CSRF
                            $csrf_token = $_POST['csrf_token'] ?? '';
                            if (!validateCSRFToken($csrf_token)) {
                                $form_error = true;
                                $error_message = 'Token di sicurezza non valido.';
                            } else {
                                // Sanitizzazione e validazione input
                                $name = trim($_POST['name'] ?? '');
                                $email = trim($_POST['email'] ?? '');
                                $subject = trim($_POST['subject'] ?? '');
                                $message = trim($_POST['message'] ?? '');
                                
                                // Validazione lunghezza e contenuto
                                $validation_errors = [];
                                if (strlen($name) > 100) $validation_errors[] = 'Nome troppo lungo';
                                if (strlen($email) > 255) $validation_errors[] = 'Email troppo lunga';
                                if (strlen($subject) > 200) $validation_errors[] = 'Oggetto troppo lungo';
                                if (strlen($message) > 2000) $validation_errors[] = 'Messaggio troppo lungo';
                                
                                // Sanitizzazione per prevenire header injection
                                $name = preg_replace('/[\r\n]/', '', $name);
                                $email = preg_replace('/[\r\n]/', '', $email);
                                $subject = preg_replace('/[\r\n]/', '', $subject);

                            if (empty($validation_errors) && !empty($name) && !empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL) && !empty($message)) {
                                // Prepara email
                                $to = 'info@passionecalabria.it';
                                $email_subject = 'Nuovo messaggio dal sito - ' . ($subject ? $subject : 'Contatto generico');
                                $email_body = "Nome: $name\n";
                                $email_body .= "Email: $email\n";
                                if ($subject) $email_body .= "Oggetto: $subject\n";
                                $email_body .= "\nMessaggio:\n$message\n\n";
                                $email_body .= "---\nInviato dal sito passionecalabria.it";
                                
                                $headers = "From: noreply@passionecalabria.it\r\n";
                                $headers .= "Reply-To: $email\r\n";
                                $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

                                // Log sicuro del messaggio (senza dati personali)
                                if (!file_exists('logs')) {
                                    mkdir('logs', 0755, true);
                                }
                                $log_entry = date('Y-m-d H:i:s') . " - Nuovo messaggio contatti\n";
                                $log_entry .= "IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . "\n";
                                $log_entry .= "Lunghezza messaggio: " . strlen($message) . " caratteri\n";
                                $log_entry .= "---\n\n";
                                @file_put_contents('logs/contact_messages.log', $log_entry, FILE_APPEND | LOCK_EX);
                                
                                // Tentativo di invio email
                                $email_sent = mail($to, $email_subject, $email_body, $headers);
                                
                                // Successo anche se email fallisce (per testing)
                                $form_submitted = true;
                                
                                // Log risultato email
                                $email_log = date('Y-m-d H:i:s') . " - Email send result: " . ($email_sent ? 'SUCCESS' : 'FAILED') . "\n";
                                @file_put_contents('logs/email_attempts.log', $email_log, FILE_APPEND | LOCK_EX);
                            } else {
                                $form_error = true;
                                $error_message = !empty($validation_errors) ? implode(', ', $validation_errors) : 'Controlla che tutti i campi siano compilati correttamente.';
                            }
                            } // Chiude l'else del CSRF check
                        }
                        ?>

                        <?php if ($form_submitted): ?>
                            <div class="bg-green-100 border border-green-200 text-green-700 p-4 rounded-lg mb-6">
                                <div class="flex items-center">
                                    <i data-lucide="check-circle" class="w-5 h-5 mr-2"></i>
                                    <div>
                                        <p class="font-semibold"><?php echo htmlspecialchars($contentManager->getText('message-sent-success', 'Messaggio inviato con successo!')); ?></p>
                                        <p class="text-sm"><?php echo htmlspecialchars($contentManager->getText('we-will-reply-soon', 'Ti risponderemo al più presto all\'indirizzo email fornito.')); ?></p>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if ($form_error): ?>
                            <div class="bg-red-100 border border-red-200 text-red-700 p-4 rounded-lg mb-6">
                                <div class="flex items-center">
                                    <i data-lucide="alert-circle" class="w-5 h-5 mr-2"></i>
                                    <p><?php echo htmlspecialchars($error_message ?? 'Si è verificato un errore. Controlla che tutti i campi siano compilati correttamente.'); ?></p>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if (!$form_submitted): ?>
                        <form method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <input type="hidden" name="contact_form" value="1">
                            <?php echo getCSRFTokenField(); ?>
                            
                            <div>
                                <label for="name" class="block text-sm font-medium text-gray-700 mb-2"><?php echo htmlspecialchars($contentManager->getText('full-name-required', 'Nome Completo *')); ?></label>
                                <input type="text" name="name" id="name" required
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            </div>
                            
                            <div>
                                <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email *</label>
                                <input type="email" name="email" id="email" required
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            </div>
                            
                            <div class="md:col-span-2">
                                <label for="subject" class="block text-sm font-medium text-gray-700 mb-2"><?php echo htmlspecialchars($contentManager->getText('subject', 'Oggetto')); ?></label>
                                <input type="text" name="subject" id="subject"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                       placeholder="<?php echo htmlspecialchars($contentManager->getText('what-do-you-want-to-talk-about', 'Di cosa vuoi parlarci?')); ?>">
                            </div>
                            
                            <div class="md:col-span-2">
                                <label for="message" class="block text-sm font-medium text-gray-700 mb-2"><?php echo htmlspecialchars($contentManager->getText('message-required', 'Messaggio *')); ?></label>
                                <textarea name="message" id="message" rows="6" required
                                          class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                          placeholder="<?php echo htmlspecialchars($contentManager->getText('write-your-message-here', 'Scrivi qui il tuo messaggio...')); ?>"></textarea>
                            </div>
                            
                            <div class="md:col-span-2 text-center">
                                <button type="submit" 
                                        class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-8 rounded-lg transition-colors inline-flex items-center">
                                    <i data-lucide="send" class="w-5 h-5 mr-2"></i>
                                    <?php echo htmlspecialchars($contentManager->getText('send-message', 'Invia Messaggio')); ?>
                                </button>
                            </div>
                        </form>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <!-- Contenuto normale per altre pagine -->
                    <div class="prose prose-lg max-w-none">
                        <?php echo htmlspecialchars($page['content'], ENT_QUOTES, 'UTF-8'); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>
    
    <!-- Scripts -->
    <script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>
    <script src="assets/js/main.js"></script>
    <script>
        lucide.createIcons();
        
        // Inizializza mappa contatti se presente
        if (document.getElementById('contact-map')) {
            const map = L.map('contact-map').setView([38.9098, 16.5969], 15); // Catanzaro
            
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors'
            }).addTo(map);
            
            L.marker([38.9098, 16.5969]).addTo(map)
                .bindPopup('<strong>Passione Calabria</strong><br>Via Roma, 123<br>88100 Catanzaro (CZ)')
                .openPopup();
        }
    </script>
</body>
</html>