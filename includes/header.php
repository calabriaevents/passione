<?php
// Header comune per tutte le pagine SENZA BANDIERINE
?>
<!-- Header -->
<header class="bg-gradient-to-r from-blue-600 via-teal-500 to-yellow-500 text-white">
    <!-- Top Bar -->
    <div class="bg-black/20 py-2">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center text-sm">
                <div class="flex items-center space-x-2">
                    <i data-lucide="map-pin" class="w-4 h-4"></i>
                    <span class="translatable" data-translate="discover-calabria">Scopri la Calabria</span>
                </div>
                
                <!-- Language Indicator (senza switcher, traduzione preventiva) -->
                <div class="flex items-center space-x-3">
                    <span class="text-xs text-blue-200">
                        <?php 
                        global $contentManager;
                        if (isset($contentManager)) {
                            $langInfo = $contentManager->getCurrentLanguageInfo();
                            echo 'Lingua: <span class="font-semibold text-yellow-200">' . htmlspecialchars($langInfo['native_name']) . '</span>';
                        } else {
                            echo 'Lingua: <span class="font-semibold text-yellow-200">Italiano</span>';
                        }
                        ?>
                    </span>
                </div>
                
                <div class="hidden sm:block">
                    <span class="translatable" data-translate="welcome-message">Benvenuto in Passione Calabria</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Navigation -->
    <nav class="relative">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center py-4">
                <!-- Logo -->
                <div class="flex items-center space-x-3">
                    <a href="index.php" class="flex items-center space-x-3">
                        <div class="w-12 h-12 bg-gradient-to-r from-blue-500 to-yellow-500 rounded-full flex items-center justify-center">
                            <span class="text-white font-bold text-lg">PC</span>
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold">
                                Passione <span class="text-yellow-300">Calabria</span>
                            </h1>
                            <p class="text-blue-100 text-sm">La tua guida alla Calabria</p>
                        </div>
                    </a>
                </div>

                <!-- Navigation Links - Centered -->
                <div class="hidden lg:flex items-center justify-center flex-1">
                    <div class="flex items-center space-x-8">
                        <a href="index.php" class="hover:text-yellow-300 transition-colors font-medium translatable" data-translate="nav-home">Home</a>
                        <a href="categorie.php" class="hover:text-yellow-300 transition-colors font-medium translatable" data-translate="nav-categories">Categorie</a>
                        <a href="province.php" class="hover:text-yellow-300 transition-colors font-medium translatable" data-translate="nav-provinces">Province</a>
                        <a href="mappa.php" class="hover:text-yellow-300 transition-colors font-medium translatable" data-translate="nav-map">Mappa</a>
                        <a href="iscrivi-attivita.php" class="bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded-full transition-colors font-medium translatable" data-translate="nav-register">Iscrivi la tua attività</a>
                        <a href="admin/" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-full transition-colors font-medium">Admin</a>
                    </div>
                </div>
                
                <!-- Spacer for centering -->
                <div class="hidden lg:block w-32"></div>

                <!-- Mobile Menu Button -->
                <button id="mobile-menu-btn" class="lg:hidden p-2">
                    <i data-lucide="menu" class="w-6 h-6"></i>
                </button>
            </div>
        </div>

        <!-- Mobile Menu -->
        <div id="mobile-menu" class="lg:hidden hidden bg-black/20 backdrop-blur-sm">
            <div class="px-4 py-4 space-y-3">
                <a href="index.php" class="block py-2 hover:text-yellow-300 translatable" data-translate="nav-home">Home</a>
                <a href="categorie.php" class="block py-2 hover:text-yellow-300 translatable" data-translate="nav-categories">Categorie</a>
                <a href="province.php" class="block py-2 hover:text-yellow-300 translatable" data-translate="nav-provinces">Province</a>
                <a href="mappa.php" class="block py-2 hover:text-yellow-300 translatable" data-translate="nav-map">Mappa</a>
                <a href="iscrivi-attivita.php" class="block py-2 hover:text-yellow-300 translatable" data-translate="nav-register">Iscrivi la tua attività</a>
                <a href="admin/" class="block py-2 hover:text-yellow-300">Admin</a>
            </div>
        </div>
    </nav>
</header>

<!-- JavaScript per funzionalità header -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Mobile menu toggle
    const mobileMenuBtn = document.getElementById('mobile-menu-btn');
    const mobileMenu = document.getElementById('mobile-menu');
    
    if (mobileMenuBtn && mobileMenu) {
        mobileMenuBtn.addEventListener('click', function() {
            mobileMenu.classList.toggle('hidden');
        });
    }
});
</script>