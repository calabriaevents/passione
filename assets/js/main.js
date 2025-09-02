// Passione Calabria - JavaScript Principale

document.addEventListener('DOMContentLoaded', function() {
    // Inizializza tutte le funzionalità
    initMobileMenu();
    initSearch();
    initSmoothScroll();
    initNewsletterForm();
    initLazyLoading();
    initTooltips();
    initCategoriesSlider();
    initArticlesSliders();
    
    // Inizializza mappa homepage se presente
    if (document.getElementById('homepage-map')) {
        initHomepageMap();
    }

    // Inizializza Lucide icons
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
});

// Mobile Menu
function initMobileMenu() {
    const mobileMenuBtn = document.getElementById('mobile-menu-btn');
    const mobileMenu = document.getElementById('mobile-menu');

    if (mobileMenuBtn && mobileMenu) {
        // Assicurati che il menu sia nascosto all'inizio
        mobileMenu.classList.add('hidden');
        
        mobileMenuBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            mobileMenu.classList.toggle('hidden');

            // Cambia icona
            const icon = mobileMenuBtn.querySelector('[data-lucide]');
            if (icon) {
                const isOpen = !mobileMenu.classList.contains('hidden');
                icon.setAttribute('data-lucide', isOpen ? 'x' : 'menu');
                if (typeof lucide !== 'undefined') {
                    lucide.createIcons();
                }
            }
        });

        // Chiudi menu quando si clicca su un link
        const menuLinks = mobileMenu.querySelectorAll('a');
        menuLinks.forEach(link => {
            link.addEventListener('click', (e) => {
                mobileMenu.classList.add('hidden');
                const icon = mobileMenuBtn.querySelector('[data-lucide]');
                if (icon) {
                    icon.setAttribute('data-lucide', 'menu');
                    if (typeof lucide !== 'undefined') {
                        lucide.createIcons();
                    }
                }
            });
        });

        // Chiudi menu quando si clicca fuori
        document.addEventListener('click', function(e) {
            if (!mobileMenuBtn.contains(e.target) && !mobileMenu.contains(e.target)) {
                mobileMenu.classList.add('hidden');
                const icon = mobileMenuBtn.querySelector('[data-lucide]');
                if (icon) {
                    icon.setAttribute('data-lucide', 'menu');
                    if (typeof lucide !== 'undefined') {
                        lucide.createIcons();
                    }
                }
            }
        });
    }
}

// Search functionality
function initSearch() {
    const searchInputs = document.querySelectorAll('input[type="text"][placeholder*="Cerca"]');

    searchInputs.forEach(input => {
        input.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                performSearch(this.value);
            }
        });
    });

    // Live search (debounced)
    let searchTimeout;
    searchInputs.forEach(input => {
        input.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            const query = this.value.trim();

            if (query.length >= 3) {
                searchTimeout = setTimeout(() => {
                    performLiveSearch(query);
                }, 300);
            }
        });
    });
}

function performSearch(query) {
    if (query.trim()) {
        window.location.href = `ricerca.php?q=${encodeURIComponent(query)}`;
    }
}

function performLiveSearch(query) {
    // Validazione input per sicurezza
    if (!query || query.length > 200 || query.length < 2) {
        return;
    }
    
    // Sanitizza query per caratteri pericolosi
    const cleanQuery = query.replace(/[<>"'&]/g, '');
    
    // Implementazione ricerca live con AJAX
    fetch(`api/search.php?q=${encodeURIComponent(cleanQuery)}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network error');
            }
            return response.json();
        })
        .then(data => {
            showSearchResults(data);
        })
        .catch(error => {
            // Log sicuro senza stack trace
            console.warn('Errore ricerca live');
            // Non mostrare errori all'utente per ricerca live
        });
}

function showSearchResults(results) {
    // Mostra risultati in un dropdown o modal
    console.log('Risultati ricerca:', results);
}

// Smooth scroll
function initSmoothScroll() {
    const links = document.querySelectorAll('a[href^="#"]');

    links.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();

            const targetId = this.getAttribute('href').substring(1);
            const targetElement = document.getElementById(targetId);

            if (targetElement) {
                targetElement.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
}

// Scroll to top
function scrollToTop() {
    window.scrollTo({
        top: 0,
        behavior: 'smooth'
    });
}

// Newsletter form
function initNewsletterForm() {
    const newsletterForms = document.querySelectorAll('form[action*="newsletter"]');

    newsletterForms.forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);
            const email = formData.get('email');

            if (!isValidEmail(email)) {
                showNotification('Inserisci un indirizzo email valido', 'error');
                return;
            }

            // Aggiungi CSRF token se disponibile
            const csrfToken = document.querySelector('meta[name="csrf-token"]');
            if (csrfToken) {
                formData.append('csrf_token', csrfToken.getAttribute('content'));
            }

            // Invia richiesta
            fetch(this.action, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin' // Include cookies per sessione
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('Iscrizione avvenuta con successo!', 'success');
                    this.reset();
                } else {
                    showNotification(data.message || 'Errore durante l\'iscrizione', 'error');
                }
            })
            .catch(error => {
                // Log sicuro senza dettagli
                console.warn('Newsletter subscription error');
                showNotification('Errore di connessione', 'error');
            });
        });
    });
}

// Lazy loading images
function initLazyLoading() {
    const images = document.querySelectorAll('img[data-src]');

    if ('IntersectionObserver' in window) {
        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.dataset.src;
                    img.classList.remove('opacity-0');
                    img.classList.add('opacity-100', 'transition-opacity', 'duration-300');
                    imageObserver.unobserve(img);
                }
            });
        });

        images.forEach(img => imageObserver.observe(img));
    } else {
        // Fallback per browser più vecchi
        images.forEach(img => {
            img.src = img.dataset.src;
        });
    }
}

// Tooltips
function initTooltips() {
    const tooltipElements = document.querySelectorAll('[data-tooltip]');

    tooltipElements.forEach(element => {
        element.addEventListener('mouseenter', showTooltip);
        element.addEventListener('mouseleave', hideTooltip);
    });
}

function showTooltip(e) {
    const text = e.target.getAttribute('data-tooltip');
    if (!text || text.length > 200) return; // Validazione sicurezza
    
    const tooltip = document.createElement('div');
    tooltip.className = 'absolute z-50 px-2 py-1 text-sm text-white bg-gray-900 rounded shadow-lg pointer-events-none';
    // Usa textContent per prevenire XSS
    tooltip.textContent = text;
    tooltip.id = 'tooltip';

    document.body.appendChild(tooltip);

    const rect = e.target.getBoundingClientRect();
    tooltip.style.left = rect.left + (rect.width / 2) - (tooltip.offsetWidth / 2) + 'px';
    tooltip.style.top = rect.top - tooltip.offsetHeight - 8 + 'px';
}

function hideTooltip() {
    const tooltip = document.getElementById('tooltip');
    if (tooltip) {
        tooltip.remove();
    }
}

// Notifications
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    const bgColor = {
        'success': 'bg-green-500',
        'error': 'bg-red-500',
        'warning': 'bg-yellow-500',
        'info': 'bg-blue-500'
    }[type] || 'bg-blue-500';

    notification.className = `fixed top-4 right-4 z-50 px-6 py-3 text-white rounded-lg shadow-lg transform transition-all duration-300 ${bgColor}`;
    notification.textContent = message;

    document.body.appendChild(notification);

    // Animazione entrata
    setTimeout(() => {
        notification.classList.add('translate-x-0');
    }, 100);

    // Auto-remove dopo 5 secondi
    setTimeout(() => {
        notification.classList.add('translate-x-full', 'opacity-0');
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 300);
    }, 5000);
}

// Utility functions
function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('it-IT', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
}

function formatDateTime(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('it-IT', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Loading state management
function showLoading(element) {
    element.classList.add('opacity-50', 'pointer-events-none');
    const loader = document.createElement('div');
    loader.className = 'absolute inset-0 flex items-center justify-center';
    loader.innerHTML = '<div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>';
    loader.id = 'loading-' + Date.now();

    element.style.position = 'relative';
    element.appendChild(loader);

    return loader.id;
}

function hideLoading(element, loaderId) {
    element.classList.remove('opacity-50', 'pointer-events-none');
    const loader = document.getElementById(loaderId);
    if (loader) {
        loader.remove();
    }
}

// Form utilities
function serializeForm(form) {
    const formData = new FormData(form);
    const data = {};

    for (let [key, value] of formData.entries()) {
        if (data[key]) {
            if (Array.isArray(data[key])) {
                data[key].push(value);
            } else {
                data[key] = [data[key], value];
            }
        } else {
            data[key] = value;
        }
    }

    return data;
}

function validateForm(form) {
    const requiredFields = form.querySelectorAll('[required]');
    let isValid = true;

    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            field.classList.add('border-red-500');
            isValid = false;
        } else {
            field.classList.remove('border-red-500');
        }
    });

    return isValid;
}

// Articles Sliders inside Category Cards
function initArticlesSliders() {
    const sliders = document.querySelectorAll('.articles-slider[data-category]');
    
    sliders.forEach(sliderElement => {
        const categoryIdValue = sliderElement.getAttribute('data-category');
        if (!categoryIdValue) return;
        
        const slider = document.querySelector(`.articles-slider[data-category="${categoryIdValue}"]`);
        const prevBtn = document.querySelector(`.articles-prev[data-category="${categoryIdValue}"]`);
        const nextBtn = document.querySelector(`.articles-next[data-category="${categoryIdValue}"]`);
        const indicators = document.querySelectorAll(`.article-indicator[data-category="${categoryIdValue}"]`);
        
        if (!slider || !prevBtn || !nextBtn) return;
        
        let currentSlide = 0;
        const totalSlides = slider.children.length;
        
        // Update slider position
        function updateArticlesSlider() {
            const translateX = -(currentSlide * 100);
            slider.style.transform = `translateX(${translateX}%)`;
            
            // Update indicators
            indicators.forEach((indicator, index) => {
                if (index === currentSlide) {
                    indicator.classList.remove('bg-gray-300');
                    indicator.classList.add('bg-blue-500');
                } else {
                    indicator.classList.remove('bg-blue-500');
                    indicator.classList.add('bg-gray-300');
                }
            });
            
            // Update navigation buttons
            prevBtn.style.opacity = currentSlide === 0 ? '0.5' : '1';
            nextBtn.style.opacity = currentSlide === totalSlides - 1 ? '0.5' : '1';
        }
        
        // Navigate to specific slide
        function goToArticleSlide(slideIndex) {
            if (slideIndex < 0) slideIndex = totalSlides - 1;
            if (slideIndex >= totalSlides) slideIndex = 0;
            currentSlide = slideIndex;
            updateArticlesSlider();
        }
        
        // Event listeners
        prevBtn.addEventListener('click', (e) => {
            e.preventDefault();
            goToArticleSlide(currentSlide - 1);
        });
        
        nextBtn.addEventListener('click', (e) => {
            e.preventDefault();
            goToArticleSlide(currentSlide + 1);
        });
        
        // Indicator click events
        indicators.forEach((indicator, index) => {
            indicator.addEventListener('click', (e) => {
                e.preventDefault();
                goToArticleSlide(index);
            });
        });
        
        // Initialize
        updateArticlesSlider();
        
        // Auto-scroll every 8 seconds (slower than main slider)
        let autoSlideInterval = setInterval(() => {
            goToArticleSlide(currentSlide + 1);
        }, 8000);
        
        // Pause on hover
        const categoryCard = slider.closest('.group');
        if (categoryCard) {
            categoryCard.addEventListener('mouseenter', () => {
                clearInterval(autoSlideInterval);
            });
            
            categoryCard.addEventListener('mouseleave', () => {
                autoSlideInterval = setInterval(() => {
                    goToArticleSlide(currentSlide + 1);
                }, 8000);
            });
        }
    });
}

// Export functions for global use
window.PassioneCalabria = {
    showNotification,
    performSearch,
    scrollToTop,
    showLoading,
    hideLoading,
    formatDate,
    formatDateTime,
    isValidEmail,
    validateForm
};

// Back to top button
window.addEventListener('scroll', function() {
    const backToTop = document.getElementById('back-to-top');
    if (backToTop) {
        if (window.pageYOffset > 300) {
            backToTop.classList.remove('hidden');
        } else {
            backToTop.classList.add('hidden');
        }
    }
});

// Print functionality
function printPage() {
    window.print();
}

// Share functionality
function shareArticle(url, title) {
    if (navigator.share) {
        navigator.share({
            title: title,
            url: url
        }).catch(console.error);
    } else {
        // Fallback
        const shareUrl = `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(url)}`;
        window.open(shareUrl, '_blank', 'width=600,height=400');
    }
}

// Categories Slider functionality
function initCategoriesSlider() {
    const slider = document.getElementById('categories-slider');
    const prevBtn = document.getElementById('categories-prev');
    const nextBtn = document.getElementById('categories-next');
    const indicators = document.querySelectorAll('.slider-indicator');
    
    if (!slider || !prevBtn || !nextBtn) return;
    
    const cards = slider.children;
    const totalCards = cards.length;
    let cardsPerSlide = getCardsPerSlide();
    let totalSlides = Math.ceil(totalCards / cardsPerSlide);
    let currentSlide = 0;
    let autoSlideInterval;
    
    // Responsive cards per slide
    function getCardsPerSlide() {
        if (window.innerWidth >= 1024) return 4; // lg
        if (window.innerWidth >= 768) return 2;  // md  
        return 1; // sm
    }
    
    // Update slider position
    function updateSlider() {
        const translateX = -(currentSlide * 100);
        slider.style.transform = `translateX(${translateX}%)`;
        
        // Update indicators
        indicators.forEach((indicator, index) => {
            if (index === currentSlide) {
                indicator.classList.remove('bg-gray-300');
                indicator.classList.add('bg-blue-500');
            } else {
                indicator.classList.remove('bg-blue-500');
                indicator.classList.add('bg-gray-300');
            }
        });
        
        // Update navigation buttons
        prevBtn.style.opacity = currentSlide === 0 ? '0.5' : '1';
        nextBtn.style.opacity = currentSlide === totalSlides - 1 ? '0.5' : '1';
    }
    
    // Navigate to specific slide
    function goToSlide(slideIndex) {
        if (slideIndex < 0) slideIndex = totalSlides - 1;
        if (slideIndex >= totalSlides) slideIndex = 0;
        currentSlide = slideIndex;
        updateSlider();
    }
    
    // Auto-scroll every 15 seconds
    function startAutoSlide() {
        stopAutoSlide();
        autoSlideInterval = setInterval(() => {
            goToSlide(currentSlide + 1);
        }, 15000);
    }
    
    function stopAutoSlide() {
        if (autoSlideInterval) {
            clearInterval(autoSlideInterval);
        }
    }
    
    // Event listeners
    prevBtn.addEventListener('click', () => {
        goToSlide(currentSlide - 1);
        stopAutoSlide();
        setTimeout(startAutoSlide, 5000); // Restart auto-slide after 5 seconds
    });
    
    nextBtn.addEventListener('click', () => {
        goToSlide(currentSlide + 1);
        stopAutoSlide();
        setTimeout(startAutoSlide, 5000); // Restart auto-slide after 5 seconds
    });
    
    // Indicator click events
    indicators.forEach((indicator, index) => {
        indicator.addEventListener('click', () => {
            goToSlide(index);
            stopAutoSlide();
            setTimeout(startAutoSlide, 5000);
        });
    });
    
    // Handle window resize
    window.addEventListener('resize', debounce(() => {
        cardsPerSlide = getCardsPerSlide();
        totalSlides = Math.ceil(totalCards / cardsPerSlide);
        if (currentSlide >= totalSlides) {
            currentSlide = totalSlides - 1;
        }
        updateSlider();
    }, 250));
    
    // Pause auto-scroll on hover
    slider.parentElement.addEventListener('mouseenter', stopAutoSlide);
    slider.parentElement.addEventListener('mouseleave', startAutoSlide);
    
    // Initialize
    updateSlider();
    startAutoSlide();
}

// Homepage Map functionality
function initHomepageMap() {
    const mapContainer = document.getElementById('homepage-map');
    if (!mapContainer) {
        console.log('Homepage map container not found');
        return;
    }

    // Check if already initialized (fix race condition)
    if (mapContainer._leaflet_id || mapContainer.dataset.mapInitialized) {
        console.log('Map already initialized, skipping...');
        return;
    }
    
    // Mark as initializing
    mapContainer.dataset.mapInitialized = 'true';

    // Check if Leaflet is available, retry if not
    if (typeof L === 'undefined') {
        console.log('Leaflet not yet loaded, retrying in 500ms...');
        setTimeout(initHomepageMap, 500);
        return;
    }

    console.log('Initializing homepage map...');

    try {
        // Inizializza mappa centrata sulla Calabria
        const map = L.map('homepage-map').setView([39.0, 16.5], 8);

        // Aggiungi tile layer
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(map);

        console.log('Map tiles loaded successfully');

    // Definisci colori per le categorie
    const categoryColors = {
        'natura': '#10b981',      // green-500
        'storia': '#8b5cf6',      // violet-500
        'gastronomia': '#f59e0b', // amber-500
        'eventi': '#ef4444',      // red-500
        'mare': '#06b6d4',        // cyan-500
        'montagna': '#84cc16',    // lime-500
        'cultura': '#6366f1',     // indigo-500
        'arte': '#ec4899',        // pink-500
        'default': '#3b82f6'      // blue-500
    };

        // Fetch dati città e articoli via API
        fetch('api/search.php?map_data=1')
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                console.log('Map data received:', data);
                
                if (data.success && data.cities && data.cities.length > 0) {
                    data.cities.forEach(city => {
                        if (city.lat && city.lng) {
                            // Crea marker colorato
                            const categoryName = city.main_category ? city.main_category.toLowerCase() : 'default';
                            const color = categoryColors[categoryName] || categoryColors['default'];
                            
                            const customIcon = L.divIcon({
                                html: `<div style="background-color: ${color}; width: 20px; height: 20px; border-radius: 50%; border: 3px solid white; box-shadow: 0 2px 4px rgba(0,0,0,0.3);"></div>`,
                                className: 'custom-marker',
                                iconSize: [20, 20],
                                iconAnchor: [10, 10]
                            });

                            const marker = L.marker([city.lat, city.lng], { icon: customIcon }).addTo(map);
                            
                            // Popup con informazioni
                            const popupContent = `
                                <div class="p-2 min-w-48">
                                    <h3 class="font-bold text-lg text-gray-800 mb-1">${city.name}</h3>
                                    <p class="text-sm text-gray-600 mb-2">${city.province_name}</p>
                                    ${city.main_category ? `<span class="inline-block px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded-full mb-2">${city.main_category}</span>` : ''}
                                    <div class="mt-2">
                                        <a href="provincia.php?id=${city.province_id}" class="text-blue-600 hover:text-blue-700 text-sm font-medium">
                                            Esplora ${city.province_name} →
                                        </a>
                                    </div>
                                </div>
                            `;
                            
                            marker.bindPopup(popupContent);
                        }
                    });
                    
                    console.log(`Added ${data.cities.length} markers to map`);
                } else {
                    console.log('No city data available, using fallback');
                    addFallbackMarkers(map);
                }
            })
            .catch(error => {
                // Log sicuro senza stack trace
                console.warn('Map data loading failed, using fallback');
                addFallbackMarkers(map);
            });
            
    } catch (error) {
        console.error('Error initializing map:', error);
    }
}

// Fallback markers function
function addFallbackMarkers(map) {
    const mainCities = [
        {name: 'Catanzaro', lat: 38.9097, lng: 16.5947, province: 'Catanzaro'},
        {name: 'Cosenza', lat: 39.2947, lng: 16.2542, province: 'Cosenza'},
        {name: 'Reggio Calabria', lat: 38.1113, lng: 15.6619, province: 'Reggio Calabria'},
        {name: 'Crotone', lat: 39.0847, lng: 17.1256, province: 'Crotone'},
        {name: 'Vibo Valentia', lat: 38.6783, lng: 16.1019, province: 'Vibo Valentia'}
    ];

    mainCities.forEach(city => {
        const marker = L.marker([city.lat, city.lng]).addTo(map);
        marker.bindPopup(`<b>${city.name}</b><br>${city.province}`);
    });
    
    console.log('Added fallback markers to map');
}

// Map functionality placeholder
function initMap(containerId, articles = []) {
    console.log('Inizializzazione mappa per container:', containerId);
    console.log('Articoli da visualizzare:', articles.length);

    // Qui andrà l'implementazione della mappa con Leaflet
    // Per ora mostra un placeholder
    const container = document.getElementById(containerId);
    if (container) {
        container.innerHTML = `
            <div class="w-full h-full bg-gradient-to-br from-blue-100 to-green-100 rounded-lg flex items-center justify-center">
                <div class="text-center">
                    <div class="w-16 h-16 mx-auto mb-4 text-blue-500">
                        <svg fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/>
                        </svg>
                    </div>
                    <p class="text-gray-600">Mappa interattiva della Calabria</p>
                    <p class="text-sm text-gray-500">${articles.length} luoghi da esplorare</p>
                </div>
            </div>
        `;
    }
}
