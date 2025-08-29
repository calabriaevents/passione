/**
 * PASSIONE CALABRIA - TRANSLATION SYSTEM FRONTEND
 * 
 * JavaScript avanzato per gestione traduzioni lato client
 * 
 * Features:
 * - Bandierine lingua interattive
 * - Richieste asincrone batch
 * - Loading states e feedback
 * - Cache locale per performance
 * - Gestione errori graceful
 * - Support per elementi dinamici
 * 
 * @author Passione Calabria Team
 * @version 2.0
 */

class PassioneTranslationSystem {
    constructor(options = {}) {
        this.options = {
            apiEndpoint: '/api/translate.php',
            defaultSourceLang: 'it',
            translateableClass: 'translatable',
            languageSelectors: '.language-btn',
            excludeClasses: ['no-translate', 'untranslatable'],
            loadingClass: 'translation-loading',
            errorClass: 'translation-error',
            batchSize: 10,
            batchDelay: 100, // ms between batch requests
            cacheExpiry: 30 * 60 * 1000, // 30 minutes
            showProgress: true,
            preserveFormatting: true,
            enableLocalStorage: true,
            debugMode: false,
            ...options
        };
        
        this.currentLanguage = this.options.defaultSourceLang;
        this.translationCache = new Map();
        this.pendingRequests = new Map();
        this.isTranslating = false;
        this.activeElements = new Set();
        this.mutationObserver = null;
        
        this.init();
    }
    
    /**
     * Inizializza il sistema di traduzione
     */
    init() {
        this.log('🌐 Inizializzando sistema traduzioni...');
        
        // Carica cache da localStorage
        this.loadCacheFromStorage();
        
        // Setup event listeners
        this.setupEventListeners();
        
        // Inizializza bandierine lingua
        this.initLanguageSelectors();
        
        // Rileva lingua corrente dal URL o browser
        this.detectCurrentLanguage();
        
        // Setup observer per elementi dinamici
        this.setupMutationObserver();
        
        this.log('✅ Sistema traduzioni inizializzato');
        
        // Auto-applica lingua salvata se diversa da default
        if (this.currentLanguage !== this.options.defaultSourceLang) {
            this.log('🔄 Auto-applicando lingua salvata:', this.currentLanguage);
            this.translateToLanguage(this.currentLanguage);
        } else {
            // Mostra stato iniziale
            this.updateUI();
        }
    }
    
    /**
     * Setup event listeners globali
     */
    setupEventListeners() {
        // Language selector clicks
        document.addEventListener('click', (e) => {
            const langSelector = e.target.closest('[data-lang]');
            if (langSelector) {
                e.preventDefault();
                const targetLang = langSelector.dataset.lang;
                this.translateToLanguage(targetLang);
            }
        });
        
        // Window events
        window.addEventListener('beforeunload', () => {
            this.saveCacheToStorage();
        });
        
        // Storage event per sync tra tabs
        window.addEventListener('storage', (e) => {
            if (e.key === 'passione_translation_cache') {
                this.loadCacheFromStorage();
            }
        });
        
        // Error boundary per richieste fallite
        window.addEventListener('unhandledrejection', (e) => {
            if (e.reason && e.reason.translation) {
                this.log('❌ Unhandled translation error:', e.reason);
                this.showError('Errore di traduzione imprevisto');
            }
        });
    }
    
    /**
     * Inizializza selettori lingua (bandierine)
     */
    initLanguageSelectors() {
        const selectors = document.querySelectorAll(this.options.languageSelectors);
        
        selectors.forEach(selector => {
            // Aggiungi stati hover e attivo
            selector.classList.add('translation-lang-selector');
            
            // Marca lingua corrente come attiva
            const lang = selector.dataset.lang;
            if (lang === this.currentLanguage) {
                selector.classList.add('active');
            }
        });
        
        this.log(`🏳️ Inizializzati ${selectors.length} selettori lingua`);
    }
    
    /**
     * Rileva lingua corrente da URL, storage o forza italiano come default
     */
    detectCurrentLanguage() {
        // 1. Da URL parameter
        const urlParams = new URLSearchParams(window.location.search);
        const urlLang = urlParams.get('lang');
        if (urlLang) {
            this.currentLanguage = urlLang;
            this.log(`🔍 Lingua rilevata da URL: ${this.currentLanguage}`);
            return;
        }
        
        // 2. Da localStorage
        if (this.options.enableLocalStorage) {
            const storedLang = localStorage.getItem('passione_language');
            if (storedLang) {
                this.currentLanguage = storedLang;
                this.log(`🔍 Lingua rilevata da storage: ${this.currentLanguage}`);
                return;
            }
        }
        
        // 3. Forza italiano come lingua default (non più rilevazione browser)
        // Il sistema deve partire sempre da italiano per permettere le traduzioni
        this.currentLanguage = this.options.defaultSourceLang;
        
        this.log(`🔍 Lingua forzata a default: ${this.currentLanguage}`);
    }
    
    /**
     * Setup MutationObserver per elementi dinamici
     */
    setupMutationObserver() {
        if (!window.MutationObserver) return;
        
        let observerTimeout = null;
        
        const observer = new MutationObserver((mutations) => {
            // Evita loop infinito se stiamo già traducendo
            if (this.isTranslating) return;
            
            let hasNewTranslatableContent = false;
            
            mutations.forEach((mutation) => {
                if (mutation.type === 'childList') {
                    mutation.addedNodes.forEach((node) => {
                        if (node.nodeType === Node.ELEMENT_NODE) {
                            // Ignora elementi aggiunti dal sistema di traduzione stesso
                            if (node.id === 'translation-progress' || 
                                node.classList?.contains('translation-progress-bar') ||
                                node.classList?.contains('translated') ||
                                node.classList?.contains('translation-loading')) {
                                return;
                            }
                            
                            if (node.classList?.contains(this.options.translateableClass) || 
                                node.querySelector?.(`.${this.options.translateableClass}`)) {
                                hasNewTranslatableContent = true;
                            }
                        }
                    });
                }
            });
            
            // Debounce le traduzioni per evitare chiamate multiple
            if (hasNewTranslatableContent && this.currentLanguage !== this.options.defaultSourceLang) {
                if (observerTimeout) {
                    clearTimeout(observerTimeout);
                }
                
                observerTimeout = setTimeout(() => {
                    this.log('🔄 Nuovo contenuto rilevato, traducendo...');
                    this.translateToLanguage(this.currentLanguage);
                }, 500);
            }
        });
        
        observer.observe(document.body, {
            childList: true,
            subtree: true,
            // Ignora modifiche agli attributi e al testo che potrebbero essere causate dalla traduzione
            attributes: false,
            characterData: false
        });
        
        this.mutationObserver = observer;
    }
    
    /**
     * Traduci pagina verso lingua specifica
     */
    async translateToLanguage(targetLang) {
        if (this.isTranslating) {
            this.log('⏳ Traduzione già in corso, saltando...');
            return;
        }
        
        if (targetLang === this.currentLanguage) {
            this.log(`ℹ️ Lingua già attiva: ${targetLang}`);
            return;
        }
        
        this.log(`🚀 Avvio traduzione verso: ${targetLang}`);
        
        try {
            this.isTranslating = true;
            this.updateUI();
            
            // Se stiamo tornando alla lingua originale, usa resetToOriginal
            if (targetLang === this.options.defaultSourceLang) {
                await this.resetToOriginal();
                return;
            }
            
            // Trova tutti gli elementi traducibili
            const elements = this.findTranslatableElements();
            
            if (elements.length === 0) {
                this.log('⚠️ Nessun elemento traducibile trovato');
                return;
            }
            
            this.log(`📝 Trovati ${elements.length} elementi da tradurre`);
            
            // Mostra progress se abilitato
            if (this.options.showProgress) {
                this.showProgress(0, elements.length);
            }
            
            // Traduci in batch
            await this.translateElementsBatch(elements, targetLang);
            
            // Aggiorna lingua corrente
            this.currentLanguage = targetLang;
            
            // Salva preferenza
            if (this.options.enableLocalStorage) {
                localStorage.setItem('passione_language', targetLang);
            }
            
            // Aggiorna URL se necessario
            this.updateURL(targetLang);
            
            this.log(`✅ Traduzione completata verso ${targetLang}`);
            
        } catch (error) {
            this.log('❌ Errore durante la traduzione:', error);
            this.showError('Errore durante la traduzione. Riprova tra qualche secondo.');
        } finally {
            this.isTranslating = false;
            this.updateUI();
            this.hideProgress();
        }
    }
    
    /**
     * Trova elementi traducibili nella pagina
     */
    findTranslatableElements() {
        const elements = document.querySelectorAll(`.${this.options.translateableClass}`);
        
        return Array.from(elements).filter(element => {
            // Salta elementi nascosti
            if (element.offsetParent === null) return false;
            
            // Salta elementi con classi escluse
            if (this.options.excludeClasses.some(cls => element.classList.contains(cls))) {
                return false;
            }
            
            // Controlla se ha contenuto testuale o attributi da tradurre
            const text = this.extractTextContent(element);
            const attributes = this.extractTranslatableAttributes(element);
            
            if (!text.trim() && Object.keys(attributes).length === 0) {
                return false;
            }
            
            // Per ri-traduzioni, permetti elementi già processati se la lingua target è diversa
            if (element.dataset.translationProcessed === 'true' && 
                element.dataset.translatedTo === this.currentLanguage) {
                return false;
            }
            
            return true;
        });
    }
    
    /**
     * Traduci elementi in batch per performance
     */
    async translateElementsBatch(elements, targetLang) {
        const batches = this.chunkArray(elements, this.options.batchSize);
        let completed = 0;
        
        for (let i = 0; i < batches.length; i++) {
            const batch = batches[i];
            
            // Processa batch in parallelo
            const promises = batch.map(element => this.translateElement(element, targetLang));
            
            await Promise.allSettled(promises);
            
            completed += batch.length;
            
            // Aggiorna progress
            if (this.options.showProgress) {
                this.showProgress(completed, elements.length);
            }
            
            // Delay tra batch per non sovraccaricare API
            if (i < batches.length - 1) {
                await this.delay(this.options.batchDelay);
            }
        }
    }
    
    /**
     * Traduci singolo elemento
     */
    async translateElement(element, targetLang) {
        try {
            // Estrai testo originale e attributi
            const originalText = this.extractTextContent(element);
            const originalAttributes = this.extractTranslatableAttributes(element);
            
            // Se non c'è niente da tradurre, salta
            if (!originalText.trim() && Object.keys(originalAttributes).length === 0) {
                return;
            }
            
            // Mostra loading state
            this.showElementLoading(element, true);
            
            // Traduci contenuto testuale
            if (originalText.trim()) {
                await this.translateTextContent(element, originalText, targetLang);
            }
            
            // Traduci attributi
            if (Object.keys(originalAttributes).length > 0) {
                await this.translateAttributes(element, originalAttributes, targetLang);
            }
            
            // Marca come processato
            element.dataset.translationProcessed = 'true';
            element.dataset.translatedTo = this.currentLanguage;
            element.classList.add('translated');
            element.classList.remove(this.options.errorClass);
            
        } catch (error) {
            this.log(`❌ Errore traduzione elemento:`, error);
            this.showElementError(element);
        } finally {
            this.showElementLoading(element, false);
        }
    }
    
    /**
     * Richiesta API di traduzione
     */
    async requestTranslation(text, targetLang, context = {}) {
        const requestData = {
            text: text,
            target_lang: targetLang,
            source_lang: this.options.defaultSourceLang,
            context: context
        };
        
        const response = await fetch(this.options.apiEndpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(requestData)
        });
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        return await response.json();
    }
    
    /**
     * Applica traduzione all'elemento
     */
    applyTranslation(element, translatedText, originalText) {
        // Salva testo originale se non già salvato
        if (!element.dataset.originalText) {
            element.dataset.originalText = originalText;
        }
        
        // Applica traduzione preservando formattazione
        if (this.options.preserveFormatting && element.innerHTML !== element.textContent) {
            // Elemento ha HTML, sostituisci solo il testo
            element.innerHTML = element.innerHTML.replace(originalText, translatedText);
        } else {
            // Elemento di solo testo
            element.textContent = translatedText;
        }
    }
    
    /**
     * Estrai contenuto testuale da elemento
     */
    extractTextContent(element) {
        if (element.dataset.originalText) {
            return element.dataset.originalText;
        }
        
        // Se ha figli con testo, concatena
        let text = '';
        const walker = document.createTreeWalker(
            element,
            NodeFilter.SHOW_TEXT,
            null,
            false
        );
        
        let node;
        while (node = walker.nextNode()) {
            text += node.textContent;
        }
        
        return text.trim() || element.textContent.trim();
    }
    
    /**
     * Genera chiave cache
     */
    generateCacheKey(text, targetLang) {
        const normalizedText = text.trim().replace(/\s+/g, ' ');
        return `${targetLang}:${btoa(normalizedText).substring(0, 32)}`;
    }
    
    /**
     * Gestione cache
     */
    getFromCache(key) {
        const cached = this.translationCache.get(key);
        if (!cached) return null;
        
        // Controlla scadenza
        if (Date.now() - cached.timestamp > this.options.cacheExpiry) {
            this.translationCache.delete(key);
            return null;
        }
        
        return cached;
    }
    
    saveToCache(key, data) {
        this.translationCache.set(key, {
            ...data,
            timestamp: Date.now()
        });
        
        // Salva su localStorage periodicamente
        if (this.options.enableLocalStorage) {
            this.saveCacheToStorage();
        }
    }
    
    /**
     * Persistenza cache su localStorage
     */
    loadCacheFromStorage() {
        if (!this.options.enableLocalStorage) return;
        
        try {
            const stored = localStorage.getItem('passione_translation_cache');
            if (stored) {
                const data = JSON.parse(stored);
                this.translationCache = new Map(data);
                this.log(`💾 Cache caricata: ${this.translationCache.size} elementi`);
            }
        } catch (error) {
            this.log('⚠️ Errore caricamento cache:', error);
        }
    }
    
    saveCacheToStorage() {
        if (!this.options.enableLocalStorage) return;
        
        try {
            const data = Array.from(this.translationCache.entries());
            localStorage.setItem('passione_translation_cache', JSON.stringify(data));
        } catch (error) {
            this.log('⚠️ Errore salvataggio cache:', error);
        }
    }
    
    /**
     * UI States e Feedback
     */
    showElementLoading(element, show) {
        if (show) {
            element.classList.add(this.options.loadingClass);
            this.activeElements.add(element);
        } else {
            element.classList.remove(this.options.loadingClass);
            this.activeElements.delete(element);
        }
    }
    
    showElementError(element) {
        element.classList.add(this.options.errorClass);
        element.title = 'Errore durante la traduzione';
    }
    
    showProgress(completed, total) {
        const percent = Math.round((completed / total) * 100);
        
        // Crea/aggiorna progress bar
        let progressBar = document.getElementById('translation-progress');
        if (!progressBar) {
            progressBar = document.createElement('div');
            progressBar.id = 'translation-progress';
            progressBar.className = 'translation-progress-bar';
            progressBar.innerHTML = `
                <div class="progress-container">
                    <div class="progress-bar">
                        <div class="progress-fill"></div>
                    </div>
                    <div class="progress-text">Traduzione in corso...</div>
                </div>
            `;
            document.body.appendChild(progressBar);
        }
        
        const fill = progressBar.querySelector('.progress-fill');
        const text = progressBar.querySelector('.progress-text');
        
        fill.style.width = `${percent}%`;
        text.textContent = `Traduzione in corso... ${percent}% (${completed}/${total})`;
        
        progressBar.style.display = 'block';
    }
    
    hideProgress() {
        const progressBar = document.getElementById('translation-progress');
        if (progressBar) {
            progressBar.style.display = 'none';
        }
    }
    
    showError(message) {
        // Implementa notifica errore
        console.error('Translation Error:', message);
        
        // Potresti integrare con il sistema di notifiche esistente
        if (window.PassioneCalabria && window.PassioneCalabria.showNotification) {
            window.PassioneCalabria.showNotification(message, 'error');
        }
    }
    
    /**
     * Aggiorna UI generale
     */
    updateUI() {
        // Aggiorna selettori lingua
        document.querySelectorAll('[data-lang]').forEach(selector => {
            selector.classList.toggle('active', selector.dataset.lang === this.currentLanguage);
        });
        
        // Aggiorna body class
        document.body.setAttribute('data-language', this.currentLanguage);
        document.body.classList.toggle('translating', this.isTranslating);
    }
    
    /**
     * Aggiorna URL con lingua corrente e tutti i link della pagina
     */
    updateURL(targetLang) {
        if (targetLang === this.options.defaultSourceLang) {
            // Rimuovi parametro lingua per lingua default
            const url = new URL(window.location);
            url.searchParams.delete('lang');
            window.history.replaceState({}, '', url);
            this.updateAllLinks(null); // Rimuovi lang da tutti i link
        } else {
            // Aggiungi parametro lingua
            const url = new URL(window.location);
            url.searchParams.set('lang', targetLang);
            window.history.replaceState({}, '', url);
            this.updateAllLinks(targetLang); // Aggiungi lang a tutti i link
        }
    }
    
    /**
     * Aggiorna tutti i link interni della pagina con il parametro lingua
     */
    updateAllLinks(targetLang) {
        const links = document.querySelectorAll('a[href]');
        
        links.forEach(link => {
            const href = link.getAttribute('href');
            
            // Skip external links, anchors, and javascript links
            if (!href || href.startsWith('#') || href.startsWith('javascript:') || 
                href.startsWith('mailto:') || href.startsWith('tel:') || 
                href.startsWith('http://') || href.startsWith('https://')) {
                return;
            }
            
            try {
                const url = new URL(href, window.location.origin);
                
                if (targetLang && targetLang !== this.options.defaultSourceLang) {
                    url.searchParams.set('lang', targetLang);
                } else {
                    url.searchParams.delete('lang');
                }
                
                // Update the link href (use pathname + search to keep it relative)
                const newHref = url.pathname + url.search + url.hash;
                link.setAttribute('href', newHref);
                
            } catch (e) {
                // If URL parsing fails, try simple string manipulation
                if (targetLang && targetLang !== this.options.defaultSourceLang) {
                    if (href.includes('?')) {
                        if (href.includes('lang=')) {
                            link.setAttribute('href', href.replace(/lang=[^&]*/, `lang=${targetLang}`));
                        } else {
                            link.setAttribute('href', href + `&lang=${targetLang}`);
                        }
                    } else {
                        link.setAttribute('href', href + `?lang=${targetLang}`);
                    }
                } else {
                    // Remove lang parameter
                    let newHref = href.replace(/[?&]lang=[^&]*/, '');
                    newHref = newHref.replace(/\?$/, ''); // Remove trailing ?
                    link.setAttribute('href', newHref);
                }
            }
        });
        
        this.log(`🔗 Aggiornati ${links.length} link con lingua: ${targetLang || 'default'}`);
    }
    
    /**
     * Utility functions
     */
    chunkArray(array, chunkSize) {
        const chunks = [];
        for (let i = 0; i < array.length; i += chunkSize) {
            chunks.push(array.slice(i, i + chunkSize));
        }
        return chunks;
    }
    
    delay(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    }
    
    generateElementSelector(element) {
        if (element.id) return `#${element.id}`;
        if (element.className) return `.${element.className.split(' ')[0]}`;
        return element.tagName.toLowerCase();
    }
    
    log(...args) {
        if (this.options.debugMode) {
            console.log('[Translation]', ...args);
        }
    }
    
    /**
     * Estrai attributi traducibili da elemento
     */
    extractTranslatableAttributes(element) {
        const attributes = {};
        const translateableAttributes = {
            'data-translate-placeholder': 'placeholder',
            'data-translate-alt': 'alt',
            'data-translate-title': 'title',
            'data-translate-aria-label': 'aria-label'
        };
        
        Object.entries(translateableAttributes).forEach(([dataAttr, realAttr]) => {
            if (element.hasAttribute(dataAttr) && element.hasAttribute(realAttr)) {
                const value = element.getAttribute(realAttr);
                if (value && value.trim()) {
                    attributes[realAttr] = value.trim();
                }
            }
        });
        
        return attributes;
    }
    
    /**
     * Traduci contenuto testuale
     */
    async translateTextContent(element, originalText, targetLang) {
        const cacheKey = this.generateCacheKey(originalText, targetLang);
        const cached = this.getFromCache(cacheKey);
        
        if (cached) {
            this.applyTranslation(element, cached.translatedText, originalText);
            this.log(`💾 Cache hit testo: ${originalText.substring(0, 50)}...`);
            return;
        }
        
        if (this.pendingRequests.has(cacheKey)) {
            const result = await this.pendingRequests.get(cacheKey);
            this.applyTranslation(element, result.translatedText, originalText);
            return;
        }
        
        const translationPromise = this.requestTranslation(originalText, targetLang, {
            page_url: window.location.href,
            element_selector: this.generateElementSelector(element)
        });
        
        this.pendingRequests.set(cacheKey, translationPromise);
        const result = await translationPromise;
        this.pendingRequests.delete(cacheKey);
        
        if (result.success) {
            this.applyTranslation(element, result.data.translated_text, originalText);
            this.saveToCache(cacheKey, {
                translatedText: result.data.translated_text,
                provider: result.data.provider,
                timestamp: Date.now()
            });
            this.log(`✅ Tradotto testo: ${originalText.substring(0, 50)}...`);
        } else {
            throw new Error(result.error || 'Text translation failed');
        }
    }
    
    /**
     * Traduci attributi
     */
    async translateAttributes(element, originalAttributes, targetLang) {
        const translationPromises = [];
        
        Object.entries(originalAttributes).forEach(([attrName, attrValue]) => {
            const cacheKey = this.generateCacheKey(`attr:${attrName}:${attrValue}`, targetLang);
            const cached = this.getFromCache(cacheKey);
            
            if (cached) {
                this.applyAttributeTranslation(element, attrName, cached.translatedText, attrValue);
                this.log(`💾 Cache hit attributo ${attrName}: ${attrValue.substring(0, 30)}...`);
            } else {
                const promise = this.translateSingleAttribute(element, attrName, attrValue, targetLang, cacheKey);
                translationPromises.push(promise);
            }
        });
        
        if (translationPromises.length > 0) {
            await Promise.allSettled(translationPromises);
        }
    }
    
    /**
     * Traduci singolo attributo
     */
    async translateSingleAttribute(element, attrName, attrValue, targetLang, cacheKey) {
        if (this.pendingRequests.has(cacheKey)) {
            const result = await this.pendingRequests.get(cacheKey);
            this.applyAttributeTranslation(element, attrName, result.translatedText, attrValue);
            return;
        }
        
        const translationPromise = this.requestTranslation(attrValue, targetLang, {
            page_url: window.location.href,
            element_selector: this.generateElementSelector(element),
            attribute_name: attrName
        });
        
        this.pendingRequests.set(cacheKey, translationPromise);
        
        try {
            const result = await translationPromise;
            this.pendingRequests.delete(cacheKey);
            
            if (result.success) {
                this.applyAttributeTranslation(element, attrName, result.data.translated_text, attrValue);
                this.saveToCache(cacheKey, {
                    translatedText: result.data.translated_text,
                    provider: result.data.provider,
                    timestamp: Date.now()
                });
                this.log(`✅ Tradotto attributo ${attrName}: ${attrValue.substring(0, 30)}...`);
            } else {
                throw new Error(result.error || 'Attribute translation failed');
            }
        } catch (error) {
            this.pendingRequests.delete(cacheKey);
            this.log(`❌ Errore traduzione attributo ${attrName}:`, error);
        }
    }
    
    /**
     * Applica traduzione all'attributo
     */
    applyAttributeTranslation(element, attrName, translatedValue, originalValue) {
        // Salva valore originale se non già salvato
        const originalDataAttr = `data-original-${attrName.replace(':', '-')}`;
        if (!element.hasAttribute(originalDataAttr)) {
            element.setAttribute(originalDataAttr, originalValue);
        }
        
        // Applica traduzione all'attributo
        element.setAttribute(attrName, translatedValue);
    }
    
    /**
     * API pubblica
     */
    
    // Forza traduzione di elemento specifico
    async translateElementPublic(element, targetLang = null) {
        targetLang = targetLang || this.currentLanguage;
        await this.translateElement(element, targetLang);
    }
    
    // Ottieni lingua corrente
    getCurrentLanguage() {
        return this.currentLanguage;
    }
    
    // Ottieni lingue supportate
    getSupportedLanguages() {
        return Array.from(document.querySelectorAll('[data-lang]')).map(el => el.dataset.lang);
    }
    
    // Pulisci cache
    clearCache() {
        this.translationCache.clear();
        if (this.options.enableLocalStorage) {
            localStorage.removeItem('passione_translation_cache');
        }
        this.log('🗑️ Cache pulita');
    }
    
    // Reset alla lingua originale
    async resetToOriginal() {
        this.log('🔄 Resettando alla lingua originale...');
        
        // Ripristina tutti gli elementi tradotti al testo originale
        document.querySelectorAll('[data-original-text]').forEach(element => {
            const originalText = element.dataset.originalText;
            if (originalText) {
                // Preserve HTML structure if needed
                if (this.options.preserveFormatting && element.innerHTML !== element.textContent) {
                    element.innerHTML = element.innerHTML.replace(element.textContent.trim(), originalText);
                } else {
                    element.textContent = originalText;
                }
            }
            
            // Ripristina attributi originali
            Array.from(element.attributes).forEach(attr => {
                if (attr.name.startsWith('data-original-')) {
                    const realAttrName = attr.name.replace('data-original-', '').replace('-', ':');
                    const originalValue = attr.value;
                    if (originalValue) {
                        element.setAttribute(realAttrName, originalValue);
                    }
                    element.removeAttribute(attr.name);
                }
            });
            
            // Clean up translation attributes
            element.removeAttribute('data-translation-processed');
            element.removeAttribute('data-translated-to');
            element.classList.remove('translated', this.options.loadingClass, this.options.errorClass);
        });
        
        // Update current language
        this.currentLanguage = this.options.defaultSourceLang;
        
        // Save preference
        if (this.options.enableLocalStorage) {
            localStorage.setItem('passione_language', this.options.defaultSourceLang);
        }
        
        // Update URL and links
        this.updateURL(this.options.defaultSourceLang);
        
        // Update UI
        this.updateUI();
        
        this.log('✅ Reset completato alla lingua originale');
    }
    
    /**
     * Forza re-traduzione di tutta la pagina (utile per risolvere problemi)
     */
    async forceRetranslate() {
        if (this.currentLanguage === this.options.defaultSourceLang) {
            this.log('ℹ️ Già in lingua originale, nessuna ri-traduzione necessaria');
            return;
        }
        
        this.log('🔄 Forzando ri-traduzione completa...');
        
        // Reset tutti gli elementi processati
        document.querySelectorAll('[data-translation-processed="true"]').forEach(element => {
            element.removeAttribute('data-translation-processed');
            element.classList.remove('translated');
        });
        
        // Ri-traduci
        await this.translateToLanguage(this.currentLanguage);
    }
}

// Auto-initialize quando DOM è pronto
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        // Inizializza solo se ci sono elementi da tradurre
        if (document.querySelector('.translatable, [data-lang]')) {
            window.PassioneTranslation = new PassioneTranslationSystem({
                debugMode: window.location.search.includes('translation_debug=true')
            });
            
            // Esponi funzioni globali per debugging
            window.forceRetranslate = () => window.PassioneTranslation?.forceRetranslate();
            window.resetTranslation = () => window.PassioneTranslation?.resetToOriginal();
        }
    });
} else {
    // DOM già pronto
    if (document.querySelector('.translatable, [data-lang]')) {
        window.PassioneTranslation = new PassioneTranslationSystem({
            debugMode: window.location.search.includes('translation_debug=true')
        });
        
        // Esponi funzioni globali per debugging
        window.forceRetranslate = () => window.PassioneTranslation?.forceRetranslate();
        window.resetTranslation = () => window.PassioneTranslation?.resetToOriginal();
    }
}