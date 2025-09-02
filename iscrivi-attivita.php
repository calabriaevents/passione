<?php
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/ContentManagerSimple.php';

// Inizializza database e content manager
$db = new Database();
$contentManager = new ContentManagerSimple($db);

// Carica i pacchetti business attivi
$stmt = $db->pdo->prepare('SELECT * FROM business_packages WHERE is_active = 1 ORDER BY sort_order');
$stmt->execute();
$packages = $stmt->fetchAll();

// Carica impostazioni
$settings = $db->getSettings();
$settingsArray = [];
foreach ($settings as $setting) {
    $settingsArray[$setting['key']] = $setting['value'];
}
?>
<!DOCTYPE html>
<html <?php echo $contentManager->getLanguageAttributes(); ?>>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($contentManager->getText('register-business-title', 'Iscrivi la tua Attività - Passione Calabria')); ?></title>
    <meta name="description" content="Registra la tua attività su Passione Calabria e raggiungi migliaia di turisti e visitatori. Scegli il piano più adatto alle tue esigenze.">

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
    <section class="relative bg-gradient-to-br from-blue-900 via-blue-700 to-amber-600 text-white py-20">
        <div class="absolute inset-0 bg-gradient-to-br from-blue-900/90 via-blue-700/80 to-amber-600/70"></div>
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <div class="mb-8">
                <span class="bg-white/20 backdrop-blur-sm text-white px-4 py-2 rounded-full text-sm font-medium">
                    🏢 Per Business e Attività
                </span>
            </div>
            <h1 class="text-4xl md:text-5xl lg:text-6xl font-bold mb-6">
                <?php echo htmlspecialchars($contentManager->getText('register-your-business', 'Iscrivi la Tua Attività')); ?>
            </h1>
            <p class="text-xl md:text-2xl text-yellow-400 mb-8">
                Raggiungi migliaia di visitatori e turisti in Calabria
            </p>
            <p class="text-lg text-gray-200 mb-12 max-w-4xl mx-auto">
                Passione Calabria è la piattaforma leader per la promozione turistica della regione. Unisciti alla nostra community di attività locali e fai conoscere la tua azienda a un pubblico sempre crescente di turisti, appassionati e locali.
            </p>

            <!-- Key Stats -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-12">
                <div class="text-center">
                    <div class="text-3xl font-bold text-yellow-400">10.000+</div>
                    <div class="text-sm text-gray-300">Visitatori Mensili</div>
                </div>
                <div class="text-center">
                    <div class="text-3xl font-bold text-yellow-400">500+</div>
                    <div class="text-sm text-gray-300">Attività Registrate</div>
                </div>
                <div class="text-center">
                    <div class="text-3xl font-bold text-yellow-400">50+</div>
                    <div class="text-sm text-gray-300">Località Coperte</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Perché scegliere Passione Calabria -->
    <section class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-bold text-gray-900 mb-4">Perché Scegliere Passione Calabria?</h2>
                <p class="text-xl text-gray-600 max-w-3xl mx-auto">
                    La nostra piattaforma offre tutto quello di cui hai bisogno per far crescere la tua attività e raggiungere nuovi clienti.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <div class="text-center p-6">
                    <div class="w-16 h-16 bg-blue-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
                        <i data-lucide="users" class="w-8 h-8 text-blue-600"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-2">Pubblico Qualificato</h3>
                    <p class="text-gray-600">I nostri visitatori sono turisti e appassionati della Calabria, il tuo target ideale.</p>
                </div>

                <div class="text-center p-6">
                    <div class="w-16 h-16 bg-green-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
                        <i data-lucide="trending-up" class="w-8 h-8 text-green-600"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-2">Crescita Costante</h3>
                    <p class="text-gray-600">La nostra piattaforma registra una crescita mensile del 15% nel traffico turistico.</p>
                </div>

                <div class="text-center p-6">
                    <div class="w-16 h-16 bg-purple-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
                        <i data-lucide="map-pin" class="w-8 h-8 text-purple-600"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-2">Focus Locale</h3>
                    <p class="text-gray-600">Ci concentriamo esclusivamente sulla Calabria, garantendo massima rilevanza geografica.</p>
                </div>

                <div class="text-center p-6">
                    <div class="w-16 h-16 bg-orange-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
                        <i data-lucide="smartphone" class="w-8 h-8 text-orange-600"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-2">Multi-Canale</h3>
                    <p class="text-gray-600">Presenza su web, app mobile e social media per massima visibilità.</p>
                </div>

                <div class="text-center p-6">
                    <div class="w-16 h-16 bg-red-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
                        <i data-lucide="headphones" class="w-8 h-8 text-red-600"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-2">Supporto Dedicato</h3>
                    <p class="text-gray-600">Un team di esperti ti aiuterà a ottimizzare la presenza della tua attività.</p>
                </div>

                <div class="text-center p-6">
                    <div class="w-16 h-16 bg-indigo-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
                        <i data-lucide="bar-chart-3" class="w-8 h-8 text-indigo-600"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-2">Analytics Dettagliate</h3>
                    <p class="text-gray-600">Monitora le performance della tua attività con statistiche complete e aggiornate.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Pacchetti Abbonamento -->
    <section class="py-20 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-bold text-gray-900 mb-4">Scegli il Piano Perfetto per Te</h2>
                <p class="text-xl text-gray-600 max-w-3xl mx-auto">
                    Offriamo soluzioni flessibili per ogni tipo di attività, dai piccoli business alle grandi aziende.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <?php foreach ($packages as $index => $package): 
                    $features = json_decode($package['features'], true) ?: [];
                    $isPopular = $package['name'] === 'Business'; // Piano più popolare
                    $cardColors = [
                        'Gratuito' => ['border' => 'border-gray-200', 'bg' => 'bg-gray-50', 'button' => 'bg-gray-600 hover:bg-gray-700', 'badge' => 'bg-gray-100 text-gray-800'],
                        'Business' => ['border' => 'border-blue-500 ring-2 ring-blue-200', 'bg' => 'bg-white', 'button' => 'bg-blue-600 hover:bg-blue-700', 'badge' => 'bg-blue-100 text-blue-800'],
                        'Premium' => ['border' => 'border-gradient-to-r from-purple-500 to-amber-500', 'bg' => 'bg-white', 'button' => 'bg-gradient-to-r from-purple-600 to-amber-600 hover:from-purple-700 hover:to-amber-700', 'badge' => 'bg-gradient-to-r from-purple-100 to-amber-100 text-purple-800']
                    ];
                    $colors = $cardColors[$package['name']] ?? $cardColors['Business'];
                ?>
                <div class="relative <?php echo $colors['bg']; ?> rounded-3xl shadow-xl <?php echo $colors['border']; ?> overflow-hidden">
                    <?php if ($isPopular): ?>
                    <div class="absolute top-0 left-0 right-0 bg-blue-600 text-white text-center py-2 text-sm font-medium">
                        🌟 PIÙ POPOLARE
                    </div>
                    <div class="pt-12 pb-8 px-8">
                    <?php else: ?>
                    <div class="p-8">
                    <?php endif; ?>
                        
                        <!-- Header del Pacchetto -->
                        <div class="text-center mb-8">
                            <h3 class="text-2xl font-bold text-gray-900 mb-2"><?php echo htmlspecialchars($package['name']); ?></h3>
                            <p class="text-gray-600 mb-6"><?php echo htmlspecialchars($package['description']); ?></p>
                            
                            <!-- Prezzo -->
                            <div class="mb-6">
                                <?php if ($package['price'] == 0): ?>
                                <div class="text-4xl font-bold text-gray-900">Gratuito</div>
                                <div class="text-sm text-gray-500">Per sempre</div>
                                <?php else: ?>
                                <div class="text-4xl font-bold text-gray-900">
                                    €<?php echo number_format($package['price'], 2); ?>
                                    <span class="text-lg text-gray-500">/anno</span>
                                </div>
                                <div class="text-sm text-gray-500">
                                    <?php echo $package['duration_months']; ?> mesi di servizio
                                </div>
                                <?php endif; ?>
                            </div>

                            <!-- Badge del Pacchetto -->
                            <div class="inline-flex items-center <?php echo $colors['badge']; ?> px-4 py-2 rounded-full text-sm font-medium mb-6">
                                <?php if ($package['name'] === 'Gratuito'): ?>
                                <i data-lucide="gift" class="w-4 h-4 mr-2"></i>
                                Inizia Subito
                                <?php elseif ($package['name'] === 'Business'): ?>
                                <i data-lucide="zap" class="w-4 h-4 mr-2"></i>
                                Consigliato
                                <?php else: ?>
                                <i data-lucide="crown" class="w-4 h-4 mr-2"></i>
                                Massima Visibilità
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Lista Funzionalità -->
                        <ul class="space-y-4 mb-8">
                            <?php foreach ($features as $feature): ?>
                            <li class="flex items-start">
                                <i data-lucide="check" class="w-5 h-5 text-green-500 mr-3 mt-0.5 flex-shrink-0"></i>
                                <span class="text-gray-700"><?php echo htmlspecialchars($feature); ?></span>
                            </li>
                            <?php endforeach; ?>
                        </ul>

                        <!-- CTA Button -->
                        <div class="text-center">
                            <button 
                                onclick="selectPackage(<?php echo $package['id']; ?>, '<?php echo htmlspecialchars($package['name']); ?>', <?php echo $package['price']; ?>)"
                                class="w-full <?php echo $colors['button']; ?> text-white py-4 px-6 rounded-xl font-semibold transition-all transform hover:scale-105 shadow-lg"
                            >
                                <?php if ($package['price'] == 0): ?>
                                Registrati Gratuitamente
                                <?php else: ?>
                                Scegli <?php echo htmlspecialchars($package['name']); ?>
                                <?php endif; ?>
                            </button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Garanzie e Note -->
            <div class="mt-16 text-center">
                <div class="bg-white rounded-2xl p-8 shadow-lg max-w-4xl mx-auto">
                    <h3 class="text-2xl font-bold text-gray-900 mb-4">Le Nostre Garanzie</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 text-sm text-gray-600">
                        <div class="flex items-center justify-center">
                            <i data-lucide="shield-check" class="w-5 h-5 text-green-500 mr-2"></i>
                            <span>Nessun costo nascosto</span>
                        </div>
                        <div class="flex items-center justify-center">
                            <i data-lucide="clock" class="w-5 h-5 text-blue-500 mr-2"></i>
                            <span>Attivazione immediata</span>
                        </div>
                        <div class="flex items-center justify-center">
                            <i data-lucide="phone" class="w-5 h-5 text-purple-500 mr-2"></i>
                            <span>Supporto incluso</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- FAQ Section -->
    <section class="py-20 bg-white">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-bold text-gray-900 mb-4">Domande Frequenti</h2>
                <p class="text-xl text-gray-600">Risposte alle domande più comuni sui nostri servizi</p>
            </div>

            <div class="space-y-6">
                <div class="bg-gray-50 rounded-2xl p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Come funziona la registrazione?</h3>
                    <p class="text-gray-600">Dopo aver scelto il piano, ti guideremo attraverso un processo semplice e veloce per inserire i dati della tua attività. Il piano gratuito è attivo immediatamente, mentre per i piani a pagamento dovrai completare il pagamento tramite Stripe.</p>
                </div>

                <div class="bg-gray-50 rounded-2xl p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Posso cambiare piano in qualsiasi momento?</h3>
                    <p class="text-gray-600">Sì, puoi effettuare l'upgrade o il downgrade del tuo piano in qualsiasi momento dal tuo pannello di controllo. Le modifiche saranno applicate immediatamente.</p>
                </div>

                <div class="bg-gray-50 rounded-2xl p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Che tipo di supporto offrite?</h3>
                    <p class="text-gray-600">Tutti i piani includono supporto via email. I clienti Premium hanno accesso al supporto prioritario con tempi di risposta garantiti entro 24 ore.</p>
                </div>

                <div class="bg-gray-50 rounded-2xl p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Posso cancellare l'abbonamento quando voglio?</h3>
                    <p class="text-gray-600">Certamente. Non ci sono vincoli contrattuali e puoi cancellare l'abbonamento in qualsiasi momento. Il servizio rimarrà attivo fino alla fine del periodo già pagato.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Final -->
    <section class="py-20 bg-gradient-to-r from-blue-600 to-purple-600 text-white">
        <div class="max-w-4xl mx-auto text-center px-4 sm:px-6 lg:px-8">
            <h2 class="text-4xl font-bold mb-6">Pronto a Far Crescere la Tua Attività?</h2>
            <p class="text-xl mb-8 text-blue-100">
                Unisciti a centinaia di attività che hanno già scelto Passione Calabria per la loro promozione online.
            </p>
            <div class="flex flex-col sm:flex-row justify-center gap-4">
                <button 
                    onclick="selectPackage(1, 'Gratuito', 0)"
                    class="bg-white text-blue-600 px-8 py-4 rounded-xl font-semibold hover:bg-gray-100 transition-colors"
                >
                    Inizia Gratis
                </button>
                <button 
                    onclick="selectPackage(2, 'Business', 29.99)"
                    class="bg-transparent border-2 border-white text-white px-8 py-4 rounded-xl font-semibold hover:bg-white hover:text-blue-600 transition-colors"
                >
                    Scegli Business
                </button>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <?php include 'includes/footer.php'; ?>

    <!-- JavaScript -->
    <script src="assets/js/main.js"></script>
    
    <script>
        // Inizializza Lucide icons
        lucide.createIcons();
        
        // Funzione per selezionare un pacchetto
        function selectPackage(packageId, packageName, price) {
            // Redirect to registration form with selected package
            window.location.href = 'registra-business.php?package=' + packageId;
        }

        // Effetti scroll per le card dei prezzi
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('[class*="rounded-3xl"]');
            
            const observer = new IntersectionObserver(function(entries) {
                entries.forEach(function(entry) {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('animate-fade-in');
                    }
                });
            }, {
                threshold: 0.1
            });

            cards.forEach(function(card) {
                observer.observe(card);
            });
        });
    </script>

    <style>
        .animate-fade-in {
            animation: fadeInUp 0.6s ease-out forwards;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</body>
</html>