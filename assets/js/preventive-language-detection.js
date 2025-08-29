/**
 * Sistema di Rilevamento Lingua Browser per Traduzioni Preventive
 * 
 * Rileva automaticamente la lingua del browser utente e comunica al server
 * per caricare i contenuti nella lingua appropriata senza intervento dell'utente.
 * 
 * @author Passione Calabria
 * @version 1.0
 */

class PreventiveLanguageDetector {
    constructor() {
        this.supportedLanguages = ['it', 'en', 'fr', 'de', 'es']; // Lingue supportate
        this.defaultLanguage = 'it';
        this.fallbackLanguage = 'en';
        this.currentLanguage = null;
        this.cookieName = 'site_language';
        this.cookieExpiration = 30; // giorni
        
        this.init();
    }
    
    /**
     * Inizializza il sistema di rilevamento lingua
     */
    init() {
        console.log('[PreventiveLanguageDetector] Inizializzazione...');
        
        // 1. Prima controlla se esiste già una preferenza salvata
        const savedLanguage = this.getSavedLanguage();
        
        if (savedLanguage) {
            console.log(`[PreventiveLanguageDetector] Lingua salvata trovata: ${savedLanguage}`);
            this.currentLanguage = savedLanguage;
        } else {
            // 2. Rileva lingua dal browser
            const detectedLanguage = this.detectBrowserLanguage();
            console.log(`[PreventiveLanguageDetector] Lingua rilevata dal browser: ${detectedLanguage}`);
            this.currentLanguage = detectedLanguage;
            
            // 3. Salva la preferenza
            this.saveLanguagePreference(detectedLanguage);
        }
        
        // 4. Comunica al server la lingua corrente
        this.notifyServerLanguage();
        
        // 5. Se la lingua non è italiano, ricarica la pagina per ottenere contenuti tradotti
        if (this.currentLanguage !== this.defaultLanguage && !this.isLanguageAlreadyLoaded()) {
            this.reloadWithLanguage();
        }
    }
    
    /**
     * Rileva la lingua preferita del browser
     */
    detectBrowserLanguage() {
        // Ottieni lingua dal browser
        const browserLanguage = navigator.language || navigator.userLanguage || navigator.browserLanguage || navigator.systemLanguage;
        
        console.log(`[PreventiveLanguageDetector] Lingua browser raw: ${browserLanguage}`);
        
        if (!browserLanguage) {
            return this.defaultLanguage;
        }
        
        // Estrai codice lingua (es: 'it-IT' -> 'it')
        const langCode = browserLanguage.toLowerCase().split('-')[0];
        
        console.log(`[PreventiveLanguageDetector] Codice lingua estratto: ${langCode}`);
        
        // Controlla se è supportata
        if (this.supportedLanguages.includes(langCode)) {
            return langCode;
        }
        
        // Se non supportata, usa inglese come fallback (se non è italiano)
        console.log(`[PreventiveLanguageDetector] Lingua non supportata, uso fallback: ${this.fallbackLanguage}`);
        return this.fallbackLanguage;
    }
    
    /**
     * Ottiene lingua salvata dal cookie
     */
    getSavedLanguage() {
        const cookie = this.getCookie(this.cookieName);
        
        if (cookie && this.supportedLanguages.includes(cookie)) {
            return cookie;
        }
        
        return null;
    }
    
    /**
     * Salva preferenza lingua in cookie
     */
    saveLanguagePreference(language) {
        if (this.supportedLanguages.includes(language)) {
            this.setCookie(this.cookieName, language, this.cookieExpiration);
            console.log(`[PreventiveLanguageDetector] Preferenza lingua salvata: ${language}`);
        }
    }
    
    /**
     * Comunica al server la lingua corrente tramite AJAX
     */
    notifyServerLanguage() {
        const data = new FormData();
        data.append('language', this.currentLanguage);
        data.append('action', 'set_language');
        
        fetch('api/language.php', {
            method: 'POST',
            body: data
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                console.log(`[PreventiveLanguageDetector] Lingua comunicata al server: ${this.currentLanguage}`);
            } else {
                console.warn('[PreventiveLanguageDetector] Errore comunicazione lingua al server:', result.error);
            }
        })
        .catch(error => {
            console.warn('[PreventiveLanguageDetector] Errore AJAX comunicazione lingua:', error);
        });
    }
    
    /**
     * Controlla se la lingua è già caricata nella pagina corrente
     */
    isLanguageAlreadyLoaded() {
        // Controlla se esiste un parametro URL o header che indica la lingua caricata
        const urlParams = new URLSearchParams(window.location.search);
        const langParam = urlParams.get('lang');
        
        // Oppure controlla un attributo nel body
        const bodyLang = document.body.getAttribute('data-lang');
        
        return (langParam === this.currentLanguage) || (bodyLang === this.currentLanguage);
    }
    
    /**
     * Ricarica la pagina con la lingua appropriata
     */
    reloadWithLanguage() {
        console.log(`[PreventiveLanguageDetector] Ricarico pagina per lingua: ${this.currentLanguage}`);
        
        // Aggiungi parametro lingua all'URL corrente
        const url = new URL(window.location);
        url.searchParams.set('lang', this.currentLanguage);
        
        // Ricarica con il nuovo URL
        window.location.href = url.toString();
    }
    
    /**
     * Permette all'utente di cambiare manualmente la lingua
     */
    changeLanguage(newLanguage) {
        if (this.supportedLanguages.includes(newLanguage)) {
            console.log(`[PreventiveLanguageDetector] Cambio lingua manuale a: ${newLanguage}`);
            
            this.currentLanguage = newLanguage;
            this.saveLanguagePreference(newLanguage);
            this.notifyServerLanguage();
            
            // Ricarica pagina con nuova lingua
            setTimeout(() => {
                this.reloadWithLanguage();
            }, 100);
        }
    }
    
    /**
     * Utility: Ottiene valore cookie
     */
    getCookie(name) {
        const value = `; ${document.cookie}`;
        const parts = value.split(`; ${name}=`);
        if (parts.length === 2) return parts.pop().split(';').shift();
        return null;
    }
    
    /**
     * Utility: Imposta cookie
     */
    setCookie(name, value, days) {
        const expires = new Date();
        expires.setTime(expires.getTime() + (days * 24 * 60 * 60 * 1000));
        document.cookie = `${name}=${value};expires=${expires.toUTCString()};path=/`;
    }
    
    /**
     * Ottiene lingua corrente
     */
    getCurrentLanguage() {
        return this.currentLanguage;
    }
    
    /**
     * Ottiene lingue supportate
     */
    getSupportedLanguages() {
        return this.supportedLanguages;
    }
    
    /**
     * Crea selettore lingue semplificato (opzionale)
     */
    createLanguageSelector(containerId) {
        const container = document.getElementById(containerId);
        if (!container) {
            console.warn(`Container ${containerId} non trovato per selettore lingue`);
            return;
        }
        
        const languageNames = {
            'it': 'Italiano',
            'en': 'English',
            'fr': 'Français',
            'de': 'Deutsch',
            'es': 'Español'
        };
        
        const selector = document.createElement('select');
        selector.className = 'language-selector';
        selector.addEventListener('change', (e) => {
            this.changeLanguage(e.target.value);
        });
        
        this.supportedLanguages.forEach(lang => {
            const option = document.createElement('option');
            option.value = lang;
            option.textContent = languageNames[lang] || lang.toUpperCase();
            option.selected = (lang === this.currentLanguage);
            selector.appendChild(option);
        });
        
        container.appendChild(selector);
    }
}

// Inizializza automaticamente quando il DOM è pronto
document.addEventListener('DOMContentLoaded', function() {
    // Attendi un momento per evitare conflitti con altri script
    setTimeout(() => {
        window.languageDetector = new PreventiveLanguageDetector();
        
        // Esponi globalmente per uso esterno se necessario
        window.changeLanguage = (lang) => window.languageDetector.changeLanguage(lang);
        window.getCurrentLanguage = () => window.languageDetector.getCurrentLanguage();
    }, 100);
});

// Esporta per uso come modulo se necessario
if (typeof module !== 'undefined' && module.exports) {
    module.exports = PreventiveLanguageDetector;
}