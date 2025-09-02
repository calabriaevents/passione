// Progress Bar Handler AJAX per Traduzioni REALI
let isOperationRunning = false;
let currentOperation = null;

// Funzione per iniziare traduzione con progress AJAX reale
function startTranslationWithProgress(action, confirmMessage) {
    if (isOperationRunning) {
        alert('Un\'operazione è già in corso. Attendere il completamento.');
        return false;
    }

    if (!confirm(confirmMessage)) {
        return false;
    }

    isOperationRunning = true;
    currentOperation = action;
    showProgressModal();
    
    // Avvia operazione AJAX reale
    executeTranslationWithRealProgress(action, 0);
    
    return false; // Impedisce l'invio del form normale
}

function executeTranslationWithRealProgress(action, step) {
    // Prepara richiesta AJAX
    const formData = new FormData();
    formData.append('action', action);
    formData.append('step', step);

    fetch('translation-progress.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.error) {
            // Errore nell'operazione
            updateProgress(100, '❌ Errore', `<div class="p-2 bg-red-50 border border-red-200 rounded text-sm text-red-800">${data.message || data.error}</div>`);
            const closeBtn = document.getElementById('closeButton');
            if (closeBtn) closeBtn.classList.remove('hidden');
            isOperationRunning = false;
        } else if (data.completed) {
            // Operazione completata
            updateProgress(100, data.message || 'Completato!', `<div class="p-2 bg-green-50 border border-green-200 rounded text-sm text-green-800">${data.message || 'Operazione completata!'}</div>`);
            const closeBtn = document.getElementById('closeButton');
            if (closeBtn) closeBtn.classList.remove('hidden');
            
            // Auto-chiudi dopo 3 secondi e ricarica pagina
            setTimeout(() => {
                closeProgressModal();
                location.reload();
            }, 3000);
            
        } else if (data.success && data.next_step !== undefined) {
            // Step intermedio - aggiorna UI e continua
            updateProgress(data.progress, data.message, `<div class="p-2 mb-1 bg-blue-50 border border-blue-200 rounded text-sm text-blue-800">${data.message}</div>`);
            
            // Continua con il prossimo step dopo un breve delay
            setTimeout(() => {
                executeTranslationWithRealProgress(action, data.next_step);
            }, 500);
        } else {
            // Risposta inaspettata
            console.error('Risposta inaspettata:', data);
            updateProgress(100, '❌ Errore di comunicazione', `<div class="p-2 bg-yellow-50 border border-yellow-200 rounded text-sm text-yellow-800">Risposta del server non valida</div>`);
            const closeBtn = document.getElementById('closeButton');
            if (closeBtn) closeBtn.classList.remove('hidden');
            isOperationRunning = false;
        }
    })
    .catch(error => {
        console.error('Errore AJAX:', error);
        updateProgress(100, '❌ Errore di rete', `<div class="p-2 bg-red-50 border border-red-200 rounded text-sm text-red-800">Errore di comunicazione con il server: ${error.message}</div>`);
        const closeBtn = document.getElementById('closeButton');
        if (closeBtn) closeBtn.classList.remove('hidden');
        isOperationRunning = false;
    });
}

function showProgressModal() {
    const modal = document.getElementById('progressModal');
    if (modal) {
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        
        // Reset UI
        updateProgress(0, 'Inizializzazione operazione...', '');
        document.getElementById('progressMessages').innerHTML = '<div class="p-2 bg-blue-50 rounded text-sm">🚀 Avvio operazione in corso...</div>';
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
    currentOperation = null;
}

function updateProgress(percentage, text, newMessage = null) {
    const progressBar = document.getElementById('progressBar');
    const progressPercentage = document.getElementById('progressPercentage');
    const progressText = document.getElementById('progressText');
    
    if (progressBar) {
        progressBar.style.width = percentage + '%';
        
        // Cambia colore in base al progress
        if (percentage === 100 && !text.includes('❌')) {
            progressBar.className = 'bg-green-600 h-3 rounded-full transition-all duration-500';
        } else if (text.includes('❌')) {
            progressBar.className = 'bg-red-600 h-3 rounded-full transition-all duration-500';
        } else {
            progressBar.className = 'bg-blue-600 h-3 rounded-full transition-all duration-500';
        }
    }
    
    if (progressPercentage) progressPercentage.textContent = percentage + '%';
    if (progressText) progressText.textContent = text;
    
    if (newMessage) {
        const messages = document.getElementById('progressMessages');
        if (messages) {
            // Aggiungi nuovo messaggio
            messages.insertAdjacentHTML('beforeend', newMessage);
            messages.scrollTop = messages.scrollHeight;
        }
    }
}

// Funzione per gestire cancellazione operazione (futura)
function cancelOperation() {
    if (isOperationRunning && currentOperation) {
        if (confirm('Sei sicuro di voler annullare l\'operazione in corso?')) {
            isOperationRunning = false;
            currentOperation = null;
            updateProgress(0, 'Operazione annullata', '<div class="p-2 bg-yellow-50 border border-yellow-200 rounded text-sm text-yellow-800">⚠️ Operazione annullata dall\'utente</div>');
            const closeBtn = document.getElementById('closeButton');
            if (closeBtn) closeBtn.classList.remove('hidden');
        }
    }
}

// Gestione chiusura modal con ESC
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && !isOperationRunning) {
        closeProgressModal();
    }
});

// Debug: Log per verificare che il file sia caricato
console.log('Progress Handler AJAX caricato - versione con traduzioni DeepL reali!');