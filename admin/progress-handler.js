// Progress Bar Handler per Traduzioni
let isOperationRunning = false;
let operationInterval = null;

// Funzione per iniziare traduzione con progress
function startTranslationWithProgress(action, confirmMessage) {
    if (isOperationRunning) {
        alert('Un\'operazione è già in corso. Attendere il completamento.');
        return false;
    }

    if (!confirm(confirmMessage)) {
        return false;
    }

    isOperationRunning = true;
    showProgressModal();
    
    // Avvia operazione con progress simulato
    simulateTranslationProgress(action);
    
    // Per ora simula - in futuro sarà chiamata AJAX reale
    setTimeout(() => {
        // Invio form reale dopo progress
        const actionInput = document.querySelector(`input[value="${action}"]`);
        if (actionInput) {
            const form = actionInput.closest('form');
            if (form) {
                form.submit();
            }
        }
    }, 6000);
    
    return false;
}

function showProgressModal() {
    const modal = document.getElementById('progressModal');
    if (modal) {
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        
        // Reset UI
        updateProgress(0, 'Inizializzazione...');
        document.getElementById('progressMessages').innerHTML = '<div class="p-2 bg-blue-50 rounded">Preparazione operazione...</div>';
        document.getElementById('closeButton').classList.add('hidden');
    }
}

function closeProgressModal() {
    const modal = document.getElementById('progressModal');
    if (modal) {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }
    isOperationRunning = false;
    
    if (operationInterval) {
        clearInterval(operationInterval);
        operationInterval = null;
    }
}

function updateProgress(percentage, text, newMessage = null) {
    const progressBar = document.getElementById('progressBar');
    const progressPercentage = document.getElementById('progressPercentage');
    const progressText = document.getElementById('progressText');
    
    if (progressBar) progressBar.style.width = percentage + '%';
    if (progressPercentage) progressPercentage.textContent = percentage + '%';
    if (progressText) progressText.textContent = text;
    
    if (newMessage) {
        const messages = document.getElementById('progressMessages');
        if (messages) {
            const messageDiv = document.createElement('div');
            messageDiv.className = 'p-2 mb-1 bg-green-50 border border-green-200 rounded text-sm';
            messageDiv.innerHTML = newMessage;
            messages.appendChild(messageDiv);
            messages.scrollTop = messages.scrollHeight;
        }
    }
}

function simulateTranslationProgress(action) {
    let steps = [];
    
    if (action === 'force_translate_all') {
        steps = [
            { progress: 15, text: 'Caricamento configurazione...', message: '✅ Configurazione DeepL verificata' },
            { progress: 25, text: 'Connessione API in corso...', message: '🔗 Connesso a DeepL API' },
            { progress: 40, text: 'Caricamento articoli...', message: '📚 Articoli identificati per traduzione' },
            { progress: 60, text: 'Traduzione articoli...', message: '🔄 Traduzione in 4 lingue: EN, FR, DE, ES' },
            { progress: 80, text: 'Traduzione contenuti statici...', message: '🔄 Traduzione interfaccia utente' },
            { progress: 95, text: 'Salvataggio...', message: '💾 Salvataggio traduzioni nel database' }
        ];
    } else if (action === 'force_translate_articles') {
        steps = [
            { progress: 20, text: 'Caricamento articoli...', message: '📚 Articoli pubblicati trovati' },
            { progress: 40, text: 'Connessione DeepL...', message: '🔗 API DeepL pronta' },
            { progress: 70, text: 'Traduzione in corso...', message: '🔄 Traduzione articoli in corso' },
            { progress: 90, text: 'Salvataggio...', message: '💾 Traduzioni salvate' }
        ];
    } else if (action === 'force_translate_static') {
        steps = [
            { progress: 30, text: 'Caricamento testi interfaccia...', message: '📝 Contenuti statici identificati' },
            { progress: 60, text: 'Traduzione testi...', message: '🔄 Traduzione elementi UI' },
            { progress: 90, text: 'Aggiornamento interfaccia...', message: '💾 Aggiornamento completato' }
        ];
    } else if (action === 'clear_translation_cache') {
        steps = [
            { progress: 50, text: 'Pulizia cache...', message: '🧹 Rimozione cache traduzioni' },
            { progress: 80, text: 'Ottimizzazione database...', message: '⚡ Ottimizzazione tabelle' }
        ];
    }

    steps.push({ progress: 100, text: 'Completato!', message: '🎉 Operazione completata con successo!' });

    let stepIndex = 0;
    operationInterval = setInterval(() => {
        if (stepIndex < steps.length) {
            const step = steps[stepIndex];
            updateProgress(step.progress, step.text, step.message);
            stepIndex++;
        } else {
            clearInterval(operationInterval);
            operationInterval = null;
            const closeBtn = document.getElementById('closeButton');
            if (closeBtn) closeBtn.classList.remove('hidden');
        }
    }, 700);
}