<!-- Footer -->
<footer class="bg-gray-900 text-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
            <!-- About Section -->
            <div class="space-y-6">
                <div class="flex items-center space-x-3">
                    <div class="w-12 h-12 bg-gradient-to-r from-blue-500 to-yellow-500 rounded-full flex items-center justify-center">
                        <span class="text-white font-bold text-lg">PC</span>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold">
                            Passione <span class="text-yellow-400">Calabria</span>
                        </h3>
                        <p class="text-blue-200 text-sm translatable">La tua guida alla Calabria</p>
                    </div>
                </div>
                <p class="text-gray-300 leading-relaxed translatable">
                    Il portale dedicato alla scoperta della Calabria autentica: luoghi, tradizioni,
                    sapori e storie che rendono unica la nostra terra.
                </p>
                <div class="flex space-x-4">
                    <a href="#" class="text-gray-400 hover:text-white transition-colors">
                        <i data-lucide="facebook" class="w-5 h-5"></i>
                    </a>
                    <a href="#" class="text-gray-400 hover:text-white transition-colors">
                        <i data-lucide="instagram" class="w-5 h-5"></i>
                    </a>
                    <a href="#" class="text-gray-400 hover:text-white transition-colors">
                        <i data-lucide="twitter" class="w-5 h-5"></i>
                    </a>
                    <a href="#" class="text-gray-400 hover:text-white transition-colors">
                        <i data-lucide="youtube" class="w-5 h-5"></i>
                    </a>
                </div>
            </div>

            <!-- Esplora -->
            <div>
                <h4 class="text-lg font-semibold mb-6 text-yellow-400 translatable">Esplora</h4>
                <ul class="space-y-3">
                    <li><a href="categorie.php" class="text-gray-300 hover:text-white transition-colors translatable">Tutte le Categorie</a></li>
                    <li><a href="province.php" class="text-gray-300 hover:text-white transition-colors translatable">Le Province</a></li>
                    <li><a href="mappa.php" class="text-gray-300 hover:text-white transition-colors translatable">Mappa Interattiva</a></li>
                    <li><a href="articoli.php" class="text-gray-300 hover:text-white transition-colors translatable">Tutti gli Articoli</a></li>
                </ul>
            </div>

            <!-- Informazioni -->
            <div>
                <h4 class="text-lg font-semibold mb-6 text-yellow-400 translatable">Informazioni</h4>
                <ul class="space-y-3">
                    <li><a href="chi-siamo.php" class="text-gray-300 hover:text-white transition-colors translatable">Chi Siamo</a></li>
                    <li><a href="collabora.php" class="text-gray-300 hover:text-white transition-colors translatable">Collabora con Noi</a></li>
                    <li><a href="suggerisci.php" class="text-gray-300 hover:text-white transition-colors translatable">Suggerisci un Luogo</a></li>
                    <li><a href="contatti.php" class="text-gray-300 hover:text-white transition-colors translatable">Contatti</a></li>
                    <li><a href="privacy-policy.php" class="text-gray-300 hover:text-white transition-colors translatable">Privacy Policy</a></li>
                </ul>
            </div>

            <!-- Contatti -->
            <div>
                <h4 class="text-lg font-semibold mb-6 text-yellow-400 translatable">Contatti</h4>
                <div class="space-y-4">
                    <div class="flex items-center space-x-3">
                        <i data-lucide="map-pin" class="w-5 h-5 text-blue-400"></i>
                        <div>
                            <div class="font-medium translatable">Calabria, Italia</div>
                            <div class="text-sm text-gray-400 translatable">La terra tra due mari</div>
                        </div>
                    </div>
                    <div class="flex items-center space-x-3">
                        <i data-lucide="mail" class="w-5 h-5 text-blue-400"></i>
                        <a href="mailto:info@passionecalabria.it" class="text-gray-300 hover:text-white transition-colors">
                            info@passionecalabria.it
                        </a>
                    </div>
                    <div class="flex items-center space-x-3">
                        <i data-lucide="phone" class="w-5 h-5 text-blue-400"></i>
                        <a href="tel:+393001234567" class="text-gray-300 hover:text-white transition-colors">
                            +39 300 123 4567
                        </a>
                    </div>
                </div>

                <!-- Newsletter -->
                <div class="mt-8">
                    <h5 class="font-semibold mb-4 text-yellow-400 translatable">Newsletter</h5>
                    <p class="text-sm text-gray-400 mb-4 translatable">
                        Ricevi aggiornamenti sui nuovi contenuti e eventi.
                    </p>
                    <form action="api/newsletter.php" method="POST" class="flex">
                        <input
                            type="email"
                            name="email"
                            placeholder="La tua email"
                            data-translate-placeholder="La tua email"
                            required
                            class="flex-1 px-4 py-2 bg-gray-800 border border-gray-700 rounded-l-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-white placeholder-gray-400"
                        >
                        <button
                            type="submit"
                            class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-r-lg transition-colors"
                        >
                            <i data-lucide="send" class="w-4 h-4"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Bottom Footer -->
        <div class="border-t border-gray-800 mt-12 pt-8">
            <div class="flex flex-col md:flex-row justify-between items-center">
                <div class="text-gray-400 text-sm translatable">
                    © 2024 Passione Calabria. Fatto con <span class="text-red-500">♥</span> in Calabria.
                </div>
                <div class="flex space-x-6 mt-4 md:mt-0">
                    <a href="termini-servizio.php" class="text-gray-400 hover:text-white text-sm transition-colors translatable">Termini di Servizio</a>
                    <a href="privacy-policy.php" class="text-gray-400 hover:text-white text-sm transition-colors translatable">Privacy</a>
                    <button onclick="scrollToTop()" class="text-gray-400 hover:text-white transition-colors" title="Torna in alto">
                        <i data-lucide="arrow-up" class="w-4 h-4"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</footer>