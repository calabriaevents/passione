/**
 * SISTEMA SEMPLIFICATO DI RILEVAMENTO LINGUA BROWSER
 * 
 * Rileva automaticamente la lingua del browser dell'utente in modo affidabile
 * senza causare loop infiniti o problemi di performance.
 * 
 * @author Passione Calabria
 * @version 2.1 - Semplificato e Ottimizzato
 */

class SimpleBrowserLanguageDetector {
    constructor() {
        this.supportedLanguages = ['it', 'en', 'fr', 'de', 'es'];
        this.defaultLanguage = 'it';
        this.currentLanguage = this.defaultLanguage;
        this.cookieName = 'passione_language';
        this.cookieExpiration = 30; // giorni
        
        // Flag per evitare loop infiniti
        this.initialized = false;
        this.redirecting = false;
        
        this.init();
    }
    
    /**
     * Inizializza il sistema in modo sicuro
     */
    init() {
        // Evita doppie inizializzazioni
        if (this.initialized) {
            console.log('[LanguageDetector] Già inizializzato, saltando...');
            return;
        }
        
        this.initialized = true;
        console.log('[LanguageDetector] Inizializzazione sistema di rilevamento lingua...');
        
        try {
            // 1. Controlla se c'è già un parametro lingua nell'URL
            const urlLang = this.getLanguageFromURL();
            if (urlLang) {
                console.log(`[LanguageDetector] Lingua già specificata nell'URL: ${urlLang}`);
                this.currentLanguage = urlLang;
                this.saveLanguagePreference(urlLang);
                this.setupLanguageButtons();
                return;
            }
            
            // 2. Controlla se c'è una preferenza salvata
            const savedLang = this.getSavedLanguage();
            if (savedLang) {
                console.log(`[LanguageDetector] Lingua salvata trovata: ${savedLang}`);
                this.currentLanguage = savedLang;
                
                // Se non siamo già nella lingua salvata, redirigi UNA SOLA VOLTA
                if (savedLang !== this.defaultLanguage && !this.hasRedirected()) {
                    this.redirectToLanguage(savedLang);
                    return;
                }
            } else {
                // 3. Rileva lingua dal browser solo se non c'è nessuna preferenza
                const detectedLang = this.detectBrowserLanguage();
                console.log(`[LanguageDetector] Lingua rilevata dal browser: ${detectedLang}`);
                
                if (detectedLang !== this.defaultLanguage && !this.hasRedirected()) {
                    console.log(`[LanguageDetector] Reindirizzamento verso lingua rilevata: ${detectedLang}`);
                    this.saveLanguagePreference(detectedLang);
                    this.redirectToLanguage(detectedLang);
                    return;
                }
                
                this.currentLanguage = detectedLang;
                this.saveLanguagePreference(detectedLang);
            }
            
            // 4. Setup UI
            this.setupLanguageButtons();
            
        } catch (error) {
            console.error('[LanguageDetector] Errore durante inizializzazione:', error);
            // Fallback: usa lingua italiana
            this.currentLanguage = this.defaultLanguage;
            this.setupLanguageButtons();
        }
    }
    
    /**
     * Ottiene lingua dall'URL
     */
    getLanguageFromURL() {
        const urlParams = new URLSearchParams(window.location.search);
        const langParam = urlParams.get('lang');
        
        if (langParam && this.supportedLanguages.includes(langParam)) {
            return langParam;
        }
        
        return null;
    }
    
    /**
     * Rileva lingua dal browser in modo più affidabile
     */
    detectBrowserLanguage() {
        try {
            // Prova diversi metodi per ottenere la lingua del browser
            const browserLangs = [
                navigator.language,
                navigator.userLanguage,
                navigator.browserLanguage,
                navigator.systemLanguage,
                (navigator.languages && navigator.languages[0])
            ].filter(lang => lang); // Rimuovi valori null/undefined
            
            console.log('[LanguageDetector] Lingue browser rilevate:', browserLangs);
            
            for (const browserLang of browserLangs) {
                if (!browserLang) continue;
                
                // Estrai codice lingua (es: 'it-IT' -> 'it')
                const langCode = browserLang.toLowerCase().split('-')[0];
                
                // Controlla se è supportata
                if (this.supportedLanguages.includes(langCode)) {
                    console.log(`[LanguageDetector] Lingua supportata trovata: ${langCode}`);
                    return langCode;
                }\n            }\n            \n            console.log('[LanguageDetector] Nessuna lingua supportata trovata, uso default');\n            return this.defaultLanguage;\n            \n        } catch (error) {\n            console.error('[LanguageDetector] Errore rilevamento lingua browser:', error);\n            return this.defaultLanguage;\n        }\n    }\n    \n    /**\n     * Controlla se abbiamo già fatto un redirect in questa sessione\n     */\n    hasRedirected() {\n        // Usa sessionStorage per tracciare i redirect in questa sessione\n        return sessionStorage.getItem('language_redirect_done') === 'true';\n    }\n    \n    /**\n     * Marca che abbiamo fatto un redirect\n     */\n    markRedirectDone() {\n        sessionStorage.setItem('language_redirect_done', 'true');\n    }\n    \n    /**\n     * Reindirizza verso una lingua specifica (UNA SOLA VOLTA)\n     */\n    redirectToLanguage(targetLang) {\n        if (this.redirecting || this.hasRedirected()) {\n            console.log('[LanguageDetector] Redirect già fatto o in corso, saltando...');\n            return;\n        }\n        \n        this.redirecting = true;\n        this.markRedirectDone();\n        \n        console.log(`[LanguageDetector] Redirect verso lingua: ${targetLang}`);\n        \n        // Aggiungi parametro lingua all'URL corrente\n        const url = new URL(window.location);\n        if (targetLang === this.defaultLanguage) {\n            url.searchParams.delete('lang');\n        } else {\n            url.searchParams.set('lang', targetLang);\n        }\n        \n        // Evita redirect se siamo già nell'URL corretto\n        if (url.toString() !== window.location.toString()) {\n            window.location.href = url.toString();\n        } else {\n            this.redirecting = false;\n        }\n    }\n    \n    /**\n     * Ottiene lingua salvata dal cookie\n     */\n    getSavedLanguage() {\n        const cookie = this.getCookie(this.cookieName);\n        \n        if (cookie && this.supportedLanguages.includes(cookie)) {\n            return cookie;\n        }\n        \n        return null;\n    }\n    \n    /**\n     * Salva preferenza lingua in cookie\n     */\n    saveLanguagePreference(language) {\n        if (this.supportedLanguages.includes(language)) {\n            this.setCookie(this.cookieName, language, this.cookieExpiration);\n            console.log(`[LanguageDetector] Preferenza lingua salvata: ${language}`);\n        }\n    }\n    \n    /**\n     * Setup bottoni lingua\n     */\n    setupLanguageButtons() {\n        const languageButtons = document.querySelectorAll('[data-lang]');\n        \n        languageButtons.forEach(button => {\n            const buttonLang = button.dataset.lang;\n            \n            // Segna lingua attiva\n            if (buttonLang === this.currentLanguage) {\n                button.classList.add('active');\n                button.style.opacity = '1';\n            } else {\n                button.classList.remove('active');\n                button.style.opacity = '0.7';\n            }\n            \n            // Aggiungi event listener se non già presente\n            if (!button.hasAttribute('data-listener-added')) {\n                button.addEventListener('click', (e) => {\n                    e.preventDefault();\n                    this.changeLanguage(buttonLang);\n                });\n                button.setAttribute('data-listener-added', 'true');\n            }\n        });\n        \n        console.log(`[LanguageDetector] Setup ${languageButtons.length} bottoni lingua`);\n    }\n    \n    /**\n     * Cambia lingua manualmente\n     */\n    changeLanguage(newLanguage) {\n        if (!this.supportedLanguages.includes(newLanguage)) {\n            console.warn(`[LanguageDetector] Lingua non supportata: ${newLanguage}`);\n            return;\n        }\n        \n        if (newLanguage === this.currentLanguage) {\n            console.log(`[LanguageDetector] Lingua già attiva: ${newLanguage}`);\n            return;\n        }\n        \n        console.log(`[LanguageDetector] Cambio lingua manuale a: ${newLanguage}`);\n        \n        // Salva nuova preferenza\n        this.currentLanguage = newLanguage;\n        this.saveLanguagePreference(newLanguage);\n        \n        // Reset flag redirect per permettere il cambio\n        sessionStorage.removeItem('language_redirect_done');\n        \n        // Redirect verso nuova lingua\n        this.redirectToLanguage(newLanguage);\n    }\n    \n    /**\n     * Utility: Ottiene valore cookie\n     */\n    getCookie(name) {\n        const value = `; ${document.cookie}`;\n        const parts = value.split(`; ${name}=`);\n        if (parts.length === 2) {\n            return parts.pop().split(';').shift();\n        }\n        return null;\n    }\n    \n    /**\n     * Utility: Imposta cookie\n     */\n    setCookie(name, value, days) {\n        const expires = new Date();\n        expires.setTime(expires.getTime() + (days * 24 * 60 * 60 * 1000));\n        document.cookie = `${name}=${value};expires=${expires.toUTCString()};path=/;SameSite=Lax`;\n    }\n    \n    /**\n     * API pubblica\n     */\n    getCurrentLanguage() {\n        return this.currentLanguage;\n    }\n    \n    getSupportedLanguages() {\n        return [...this.supportedLanguages];\n    }\n}\n\n// Inizializza SOLO quando il DOM è pronto e SOLO UNA VOLTA\nif (document.readyState === 'loading') {\n    document.addEventListener('DOMContentLoaded', function() {\n        // Evita doppie inizializzazioni\n        if (!window.passioneBrowserLanguageDetector) {\n            window.passioneBrowserLanguageDetector = new SimpleBrowserLanguageDetector();\n            \n            // Esponi funzioni globali\n            window.changeLanguage = (lang) => {\n                if (window.passioneBrowserLanguageDetector) {\n                    window.passioneBrowserLanguageDetector.changeLanguage(lang);\n                }\n            };\n        }\n    });\n} else {\n    // DOM già pronto\n    if (!window.passioneBrowserLanguageDetector) {\n        window.passioneBrowserLanguageDetector = new SimpleBrowserLanguageDetector();\n        \n        // Esponi funzioni globali\n        window.changeLanguage = (lang) => {\n            if (window.passioneBrowserLanguageDetector) {\n                window.passioneBrowserLanguageDetector.changeLanguage(lang);\n            }\n        };\n    }\n}\n\n// Esporta per uso come modulo se necessario\nif (typeof module !== 'undefined' && module.exports) {\n    module.exports = SimpleBrowserLanguageDetector;\n}"