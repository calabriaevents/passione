<?php
/**
 * Script di Migrazione al Sistema di Traduzione Preventiva
 * 
 * Questo script migra i contenuti esistenti dal vecchio sistema di traduzione
 * client-side al nuovo sistema di traduzioni preventive.
 */

require_once 'includes/config.php';
require_once 'includes/ContentManager.php';

// Avvia output buffering per monitorare il progresso
ob_start();
echo "=== MIGRAZIONE AL SISTEMA DI TRADUZIONE PREVENTIVA ===\n";
echo "Data: " . date('Y-m-d H:i:s') . "\n\n";

try {
    // Inizializza Content Manager
    $contentManager = new ContentManager();
    $db = $contentManager->db;
    
    echo "1. MIGRAZIONE CONTENUTI STATICI...\n";
    echo "-----------------------------------\n";
    
    // Forza traduzione di tutti i contenuti statici già inseriti
    $staticTranslationResult = $db->translateAllStaticContent();
    
    if ($staticTranslationResult) {
        echo "✅ Contenuti statici tradotti con successo\n";
    } else {
        echo "⚠️  Alcuni problemi nella traduzione dei contenuti statici\n";
    }
    
    echo "\n2. MIGRAZIONE ARTICOLI ESISTENTI...\n";
    echo "------------------------------------\n";
    
    // Ottieni statistiche pre-migrazione
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM articles WHERE status = 'published'");
    $stmt->execute();
    $totalArticles = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    echo "Articoli pubblicati trovati: $totalArticles\n";
    
    if ($totalArticles > 0) {
        echo "Avvio traduzione articoli esistenti...\n";
        
        // Disabilita traduzione automatica temporaneamente per evitare conflitti
        $db->setTranslationsEnabled(false);
        
        // Traduci tutti gli articoli esistenti
        $translatedCount = $db->translateAllExistingArticles();
        
        // Riabilita traduzioni automatiche
        $db->setTranslationsEnabled(true);
        
        echo "✅ Articoli tradotti: $translatedCount / $totalArticles\n";
        
        if ($translatedCount < $totalArticles) {
            echo "⚠️  Alcuni articoli potrebbero non essere stati tradotti completamente\n";
        }
    }
    
    echo "\n3. VERIFICA CONFIGURAZIONE API...\n";
    echo "----------------------------------\n";
    
    // Controlla configurazione API traduzione
    $stmt = $db->prepare("SELECT * FROM translation_config WHERE is_enabled = 1");
    $stmt->execute();
    $config = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($config) {
        echo "✅ Configurazione API trovata: " . $config['api_provider'] . "\n";
        
        if (empty($config['api_key'])) {
            echo "⚠️  ATTENZIONE: API key non configurata!\n";
            echo "   Per abilitare le traduzioni automatiche:\n";
            echo "   1. Ottieni una Google Translate API key\n";
            echo "   2. Esegui: UPDATE translation_config SET api_key = 'TUA_API_KEY' WHERE id = 1;\n";
        } else {
            echo "✅ API key configurata (prime 10 caratteri): " . substr($config['api_key'], 0, 10) . "...\n";
        }
        
        echo "Quota giornaliera: " . $config['daily_quota'] . "\n";
        echo "Utilizzo corrente: " . $config['current_daily_usage'] . "\n";
    } else {
        echo "⚠️  Configurazione API non trovata!\n";
    }
    
    echo "\n4. STATISTICHE POST-MIGRAZIONE...\n";
    echo "----------------------------------\n";
    
    // Ottieni statistiche traduzioni
    $stats = $db->getTranslationStats();
    
    echo "Articoli totali: " . $stats['total_articles'] . "\n";
    echo "Contenuti statici totali: " . $stats['total_static_content'] . "\n";
    echo "\nTraduzioni articoli per lingua:\n";
    
    if (!empty($stats['article_translations'])) {
        foreach ($stats['article_translations'] as $lang) {
            echo "  - {$lang['language_code']}: {$lang['count']} traduzioni\n";
        }
    } else {
        echo "  Nessuna traduzione articoli trovata\n";
    }
    
    echo "\nTraduzioni contenuti statici per lingua:\n";
    
    if (!empty($stats['static_translations'])) {
        foreach ($stats['static_translations'] as $lang) {
            echo "  - {$lang['language_code']}: {$lang['count']} traduzioni\n";
        }
    } else {
        echo "  Nessuna traduzione contenuti statici trovata\n";
    }
    
    echo "\n5. TEST FUNZIONALITA' BASE...\n";
    echo "------------------------------\n";
    
    // Test basic functionality
    $testText = $contentManager->getText('hero-title', 'Test Fallback');
    echo "Test getText('hero-title'): " . $testText . "\n";
    
    $currentLang = $contentManager->getCurrentLanguage();
    echo "Lingua corrente rilevata: $currentLang\n";
    
    $langInfo = $contentManager->getCurrentLanguageInfo();
    echo "Nome lingua corrente: " . $langInfo['native_name'] . "\n";
    
    echo "\n=== MIGRAZIONE COMPLETATA ===\n";
    echo "Data completamento: " . date('Y-m-d H:i:s') . "\n";
    
    // Test con una lingua diversa (simulazione)
    echo "\n6. TEST CAMBIO LINGUA (SIMULAZIONE)...\n";
    echo "--------------------------------------\n";
    
    // Simula cambio lingua senza modificare sessione reale
    $testLanguages = ['en', 'fr', 'de', 'es'];
    
    foreach ($testLanguages as $testLang) {
        $testTranslation = $db->getStaticContent('hero-title', $testLang);
        echo "Test traduzione '$testLang' per 'hero-title': " . substr($testTranslation, 0, 50) . "...\n";
    }
    
    echo "\n✅ MIGRAZIONE COMPLETATA CON SUCCESSO!\n\n";
    echo "PROSSIMI PASSI:\n";
    echo "1. Configura Google Translate API key se non ancora fatto\n";
    echo "2. Aggiorna l'header del sito con il nuovo sistema\n";
    echo "3. Rimuovi i vecchi file JS di traduzione client-side\n";
    echo "4. Testa il rilevamento automatico della lingua del browser\n\n";
    
} catch (Exception $e) {
    echo "❌ ERRORE DURANTE LA MIGRAZIONE: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

// Output finale
$output = ob_get_contents();
ob_end_clean();

// Scrivi log su file
file_put_contents('migration_log_' . date('Y-m-d_H-i-s') . '.txt', $output);

// Mostra output su schermo
echo $output;

// Se richiamato da riga di comando, mostra anche istruzioni
if (php_sapi_name() === 'cli') {
    echo "\n========================================\n";
    echo "Il log della migrazione è stato salvato in: migration_log_" . date('Y-m-d_H-i-s') . ".txt\n";
    echo "Per testare il sistema, visita: http://localhost:8080\n";
    echo "========================================\n";
}
?>