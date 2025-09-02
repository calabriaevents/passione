<?php
require_once 'includes/config.php';
require_once 'includes/database.php';

$db = new Database();
$provinces = $db->getProvinces();
$categories = $db->getCategories();

// Gestione invio form
$success = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $start_date = $_POST['start_date'] ?? '';
        $end_date = $_POST['end_date'] ?? '';
        $location = trim($_POST['location'] ?? '');
        $category_id = $_POST['category_id'] ?? null;
        $province_id = $_POST['province_id'] ?? null;
        $organizer = trim($_POST['organizer'] ?? '');
        $contact_email = trim($_POST['contact_email'] ?? '');
        $contact_phone = trim($_POST['contact_phone'] ?? '');
        $website = trim($_POST['website'] ?? '');
        $price = $_POST['price'] ?? 0;
        
        // Validazione campi obbligatori
        if (empty($title) || empty($description) || empty($start_date) || empty($location) || empty($organizer) || empty($contact_email)) {
            throw new Exception('Tutti i campi obbligatori devono essere compilati.');
        }
        
        if (!filter_var($contact_email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Indirizzo email non valido.');
        }
        
        // Crea il suggerimento evento
        if ($db->createEventSuggestion($title, $description, $start_date, $end_date, $location, $category_id, $province_id, $organizer, $contact_email, $contact_phone, $website, $price)) {
            $success = true;
            // Reset form
            $_POST = [];
        } else {
            $error = 'Errore durante l\'invio del suggerimento. Riprova più tardi.';
        }
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title class="translatable" data-translate="suggest-event-title">Suggerisci un Evento - Passione Calabria</title>
    <meta name="description" content="Condividi un evento della Calabria con la nostra community. Feste, sagre, concerti, mostre e molto altro.">

    <!-- Tailwind CSS via CDN -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>

    <!-- Font Google -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/translation-system.css">

    <!-- Configurazione Tailwind personalizzata -->
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    },
                    colors: {
                        'calabria-blue': {
                            50: '#eff6ff',
                            500: '#3b82f6',
                            600: '#2563eb',
                            700: '#1d4ed8'
                        },
                        'calabria-gold': {
                            50: '#fffbeb',
                            500: '#f59e0b',
                            600: '#d97706'
                        }
                    }
                }
            }
        }
    </script>
</head>
<body class="min-h-screen bg-gray-50 font-sans">
    <?php include 'includes/header.php'; ?>

    <!-- Hero Section -->
    <section class="bg-gradient-to-br from-amber-500 via-orange-500 to-red-500 text-white py-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h1 class="text-4xl md:text-5xl font-bold mb-4 translatable" data-translate="suggest_event_title">
                <span class="translatable" data-translate="suggest-event">Suggerisci un Evento</span>
            </h1>
            <p class="text-xl md:text-2xl text-yellow-100 mb-6 translatable" data-translate="suggest_event_subtitle">
                Condividi con noi un evento della Calabria
            </p>
            <p class="text-lg text-orange-100 max-w-3xl mx-auto translatable" data-translate="suggest_event_description">
                Hai scoperto una festa, sagra, concerto o evento che merita di essere conosciuto? 
                Aiutaci a arricchire il nostro calendario degli eventi calabresi!
            </p>
        </div>
    </section>

    <!-- Form Section -->
    <section class="py-16 bg-white">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            
            <?php if ($success): ?>
            <div class="mb-8 bg-green-100 border-l-4 border-green-500 text-green-700 p-6 rounded-r-lg">
                <div class="flex items-center">
                    <i data-lucide="check-circle" class="w-6 h-6 mr-3 text-green-500"></i>
                    <div>
                        <h3 class="text-lg font-semibold">Suggerimento Inviato con Successo!</h3>
                        <p class="mt-1">Grazie per aver condiviso questo evento con noi. Il nostro team lo esaminerà e lo pubblicherà presto se ritenuto idoneo.</p>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($error): ?>
            <div class="mb-8 bg-red-100 border-l-4 border-red-500 text-red-700 p-6 rounded-r-lg">
                <div class="flex items-center">
                    <i data-lucide="alert-circle" class="w-6 h-6 mr-3 text-red-500"></i>
                    <div>
                        <h3 class="text-lg font-semibold">Errore</h3>
                        <p class="mt-1"><?php echo htmlspecialchars($error); ?></p>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <div class="bg-white rounded-2xl shadow-xl p-8">
                <form action="" method="POST" class="space-y-6">
                    
                    <!-- Informazioni Evento -->
                    <div class="border-b border-gray-200 pb-6">
                        <h2 class="text-2xl font-bold text-gray-900 mb-4">📅 Informazioni Evento</h2>
                        
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            <div>
                                <label for="title" class="block text-sm font-medium text-gray-700 mb-2">
                                    Nome Evento <span class="text-red-500">*</span>
                                </label>
                                <input type="text" name="title" id="title" required
                                       value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>"
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-transparent"
                                       placeholder="Es. Sagra della 'Nduja">
                            </div>
                            
                            <div>
                                <label for="category_id" class="block text-sm font-medium text-gray-700 mb-2">
                                    Categoria
                                </label>
                                <select name="category_id" id="category_id" 
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-transparent">
                                    <option value="">Seleziona categoria</option>
                                    <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>" <?php echo isset($_POST['category_id']) && $_POST['category_id'] == $category['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="mt-6">
                            <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
                                Descrizione Evento <span class="text-red-500">*</span>
                            </label>
                            <textarea name="description" id="description" rows="4" required
                                      class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-transparent"
                                      placeholder="Descrivi l'evento, le sue particolarità, cosa aspettarsi..."><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                        </div>
                    </div>

                    <!-- Date e Luogo -->
                    <div class="border-b border-gray-200 pb-6">
                        <h2 class="text-2xl font-bold text-gray-900 mb-4">📍 Date e Luogo</h2>
                        
                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                            <div>
                                <label for="start_date" class="block text-sm font-medium text-gray-700 mb-2">
                                    Data Inizio <span class="text-red-500">*</span>
                                </label>
                                <input type="datetime-local" name="start_date" id="start_date" required
                                       value="<?php echo htmlspecialchars($_POST['start_date'] ?? ''); ?>"
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-transparent">
                            </div>
                            
                            <div>
                                <label for="end_date" class="block text-sm font-medium text-gray-700 mb-2">
                                    Data Fine
                                </label>
                                <input type="datetime-local" name="end_date" id="end_date"
                                       value="<?php echo htmlspecialchars($_POST['end_date'] ?? ''); ?>"
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-transparent">
                            </div>

                            <div>
                                <label for="province_id" class="block text-sm font-medium text-gray-700 mb-2">
                                    Provincia
                                </label>
                                <select name="province_id" id="province_id" 
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-transparent">
                                    <option value="">Seleziona provincia</option>
                                    <?php foreach ($provinces as $province): ?>
                                    <option value="<?php echo $province['id']; ?>" <?php echo isset($_POST['province_id']) && $_POST['province_id'] == $province['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($province['name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="mt-6 grid grid-cols-1 lg:grid-cols-2 gap-6">
                            <div>
                                <label for="location" class="block text-sm font-medium text-gray-700 mb-2">
                                    Luogo/Indirizzo <span class="text-red-500">*</span>
                                </label>
                                <input type="text" name="location" id="location" required
                                       value="<?php echo htmlspecialchars($_POST['location'] ?? ''); ?>"
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-transparent"
                                       placeholder="Es. Piazza del Popolo, Tropea (VV)">
                            </div>
                            
                            <div>
                                <label for="price" class="block text-sm font-medium text-gray-700 mb-2">
                                    Prezzo (€)
                                </label>
                                <input type="number" name="price" id="price" step="0.01" min="0"
                                       value="<?php echo htmlspecialchars($_POST['price'] ?? '0'); ?>"
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-transparent"
                                       placeholder="0.00">
                                <p class="mt-1 text-sm text-gray-500">Inserisci 0 se l'evento è gratuito</p>
                            </div>
                        </div>
                    </div>

                    <!-- Contatti Organizzatore -->
                    <div class="border-b border-gray-200 pb-6">
                        <h2 class="text-2xl font-bold text-gray-900 mb-4">👤 Contatti Organizzatore</h2>
                        
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            <div>
                                <label for="organizer" class="block text-sm font-medium text-gray-700 mb-2">
                                    Nome Organizzatore <span class="text-red-500">*</span>
                                </label>
                                <input type="text" name="organizer" id="organizer" required
                                       value="<?php echo htmlspecialchars($_POST['organizer'] ?? ''); ?>"
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-transparent"
                                       placeholder="Nome dell'organizzatore">
                            </div>
                            
                            <div>
                                <label for="contact_email" class="block text-sm font-medium text-gray-700 mb-2">
                                    Email di Contatto <span class="text-red-500">*</span>
                                </label>
                                <input type="email" name="contact_email" id="contact_email" required
                                       value="<?php echo htmlspecialchars($_POST['contact_email'] ?? ''); ?>"
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-transparent"
                                       placeholder="email@esempio.com">
                            </div>
                        </div>

                        <div class="mt-6 grid grid-cols-1 lg:grid-cols-2 gap-6">
                            <div>
                                <label for="contact_phone" class="block text-sm font-medium text-gray-700 mb-2">
                                    Telefono
                                </label>
                                <input type="tel" name="contact_phone" id="contact_phone"
                                       value="<?php echo htmlspecialchars($_POST['contact_phone'] ?? ''); ?>"
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-transparent"
                                       placeholder="+39 xxx xxxxxxx">
                            </div>
                            
                            <div>
                                <label for="website" class="block text-sm font-medium text-gray-700 mb-2">
                                    Sito Web
                                </label>
                                <input type="url" name="website" id="website"
                                       value="<?php echo htmlspecialchars($_POST['website'] ?? ''); ?>"
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-transparent"
                                       placeholder="https://www.evento.com">
                            </div>
                        </div>
                    </div>

                    <!-- Note Importanti -->
                    <div class="bg-amber-50 p-6 rounded-lg border border-amber-200">
                        <h3 class="flex items-center text-lg font-semibold text-amber-800 mb-3">
                            <i data-lucide="info" class="w-5 h-5 mr-2"></i>
                            Note Importanti
                        </h3>
                        <ul class="text-amber-700 text-sm space-y-2">
                            <li>• Il tuo suggerimento sarà sottoposto a moderazione prima della pubblicazione</li>
                            <li>• Ci riserviamo il diritto di modificare o rifiutare eventi che non rispettano le nostre linee guida</li>
                            <li>• Gli eventi devono essere reali e verificabili</li>
                            <li>• Riceverai una conferma via email una volta che l'evento sarà pubblicato</li>
                        </ul>
                    </div>

                    <!-- Submit Button -->
                    <div class="flex justify-center pt-6">
                        <button type="submit" 
                                class="inline-flex items-center px-8 py-4 bg-gradient-to-r from-amber-500 to-orange-500 hover:from-amber-600 hover:to-orange-600 text-white rounded-full font-semibold text-lg transition-all duration-300 transform hover:scale-105 shadow-lg hover:shadow-xl">
                            <i data-lucide="send" class="w-5 h-5 mr-2"></i>
                            Invia Suggerimento
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="py-16 bg-gradient-to-br from-blue-600 to-amber-500 text-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h2 class="text-3xl md:text-4xl font-bold mb-6">
                Hai Altri Suggerimenti?
            </h2>
            <p class="text-xl mb-8 max-w-3xl mx-auto">
                Oltre agli eventi, puoi anche suggerirci luoghi interessanti, ristoranti tipici o esperienze uniche della Calabria.
            </p>
            <div class="flex flex-col sm:flex-row justify-center gap-4">
                <a href="suggerisci.php" class="inline-flex items-center px-6 py-3 bg-white text-blue-600 rounded-full font-semibold hover:bg-gray-100 transition-colors">
                    <i data-lucide="map-pin" class="w-5 h-5 mr-2"></i>
                    Suggerisci un Luogo
                </a>
                <a href="index.php" class="inline-flex items-center px-6 py-3 bg-transparent border-2 border-white text-white rounded-full font-semibold hover:bg-white hover:text-blue-600 transition-colors">
                    <i data-lucide="home" class="w-5 h-5 mr-2"></i>
                    Torna alla Homepage
                </a>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <?php include 'includes/footer.php'; ?>

    <!-- JavaScript -->
    <script src="assets/js/translation-system.js"></script>
    <script src="assets/js/main.js"></script>
    <script>
        // Inizializza Lucide icons
        lucide.createIcons();

        // Auto-set end date when start date changes (optional)
        document.getElementById('start_date').addEventListener('change', function() {
            const startDate = new Date(this.value);
            const endDateInput = document.getElementById('end_date');
            
            if (!endDateInput.value) {
                const endDate = new Date(startDate);
                endDate.setHours(startDate.getHours() + 3); // Default: 3 hours later
                
                const year = endDate.getFullYear();
                const month = String(endDate.getMonth() + 1).padStart(2, '0');
                const day = String(endDate.getDate()).padStart(2, '0');
                const hours = String(endDate.getHours()).padStart(2, '0');
                const minutes = String(endDate.getMinutes()).padStart(2, '0');
                
                endDateInput.value = `${year}-${month}-${day}T${hours}:${minutes}`;
            }
        });
    </script>
</body>
</html>