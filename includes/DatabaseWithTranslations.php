<?php
/**
 * Wrapper Database con Traduzioni Automatiche
 * 
 * Estende la funzionalità della classe Database esistente aggiungendo
 * hooks automatici per le traduzioni preventive quando articoli e contenuti
 * statici vengono creati o modificati.
 */

require_once __DIR__ . '/database.php';
// require_once __DIR__ . '/PreventiveTranslationService.php';

class DatabaseWithTranslations extends Database {
    
    private $translationService;
    private $translationsEnabled = true;
    
    public function __construct() {
        parent::__construct();
        // $this->translationService = new PreventiveTranslationService($this);
        $this->translationsEnabled = false; // Temporaneamente disabilitato
    }
    
    /**
     * Abilita/disabilita traduzioni automatiche
     */
    public function setTranslationsEnabled($enabled) {
        $this->translationsEnabled = $enabled;
    }
    
    /**
     * Override del metodo saveArticle per aggiungere traduzioni automatiche
     */
    public function saveArticle($data, $id = null) {
        try {
            // Salva l'articolo utilizzando il metodo della classe base
            $articleId = $this->createArticle($data);
            
            if ($articleId && $this->translationsEnabled) {
                // Avvia traduzione automatica in background (asincrono per non rallentare l'utente)
                $this->triggerArticleTranslation($articleId, $data);
            }
            
            return $articleId;
            
        } catch (Exception $e) {
            error_log("Errore salvataggio articolo con traduzioni: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Override del metodo updateArticle per aggiornare traduzioni
     */
    public function updateArticle($id, $data) {
        try {
            // Aggiorna l'articolo utilizzando il metodo della classe base
            $success = $this->updateArticleData($id, $data);
            
            if ($success && $this->translationsEnabled) {
                // Aggiorna traduzioni esistenti
                $this->triggerArticleTranslation($id, $data);
            }
            
            return $success;
            
        } catch (Exception $e) {
            error_log("Errore aggiornamento articolo con traduzioni: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Metodo per creare nuovo articolo con traduzioni
     */
    public function createArticle($data) {
        try {
            // Prepara dati articolo
            $articleData = [
                'title' => $data['title'],
                'slug' => createSlug($data['title']),
                'content' => $data['content'],
                'excerpt' => $data['excerpt'] ?? '',
                'category_id' => $data['category_id'],
                'province_id' => $data['province_id'] ?? null,
                'city_id' => $data['city_id'] ?? null,
                'author' => $data['author'] ?? 'Admin',
                'status' => $data['status'] ?? 'published',
                'featured' => $data['featured'] ?? 0,
                'featured_image' => $data['featured_image'] ?? null,
                'latitude' => $data['latitude'] ?? null,
                'longitude' => $data['longitude'] ?? null
            ];
            
            // Inserisci articolo
            $stmt = $this->prepare("
                INSERT INTO articles (title, slug, content, excerpt, category_id, province_id, city_id, author, status, featured, featured_image, latitude, longitude, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, datetime('now'), datetime('now'))
            ");
            
            $success = $stmt->execute([
                $articleData['title'],
                $articleData['slug'],
                $articleData['content'],
                $articleData['excerpt'],
                $articleData['category_id'],
                $articleData['province_id'],
                $articleData['city_id'],
                $articleData['author'],
                $articleData['status'],
                $articleData['featured'],
                $articleData['featured_image'],
                $articleData['latitude'],
                $articleData['longitude']
            ]);
            
            if ($success) {
                $articleId = $this->lastInsertId();
                
                // Avvia traduzione automatica
                if ($this->translationsEnabled) {
                    $this->triggerArticleTranslation($articleId, $articleData);
                }
                
                return $articleId;
            }
            
            return false;
            
        } catch (Exception $e) {
            error_log("Errore creazione articolo: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Avvia processo di traduzione articolo (può essere asincrono)
     */
    private function triggerArticleTranslation($articleId, $articleData) {
        try {
            // Traduzione temporaneamente disabilitata
            // $this->translationService->translateArticle($articleId, $articleData);
            
            error_log("Traduzioni avviate per articolo ID: {$articleId}");
            
        } catch (Exception $e) {
            error_log("Errore avvio traduzioni articolo {$articleId}: " . $e->getMessage());
        }
    }
    
    /**
     * Salva contenuto statico con traduzione automatica
     */
    public function saveStaticContent($contentKey, $contentItalian, $contextInfo = '', $pageLocation = '') {
        try {
            // Controlla se contenuto esiste già
            $stmt = $this->prepare("SELECT id FROM static_content WHERE content_key = ?");
            $stmt->execute([$contentKey]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existing) {
                // Aggiorna contenuto esistente
                $stmt = $this->prepare("
                    UPDATE static_content 
                    SET content_it = ?, context_info = ?, page_location = ?, updated_at = datetime('now')
                    WHERE content_key = ?
                ");
                $success = $stmt->execute([$contentItalian, $contextInfo, $pageLocation, $contentKey]);
                $staticContentId = $existing['id'];
            } else {
                // Crea nuovo contenuto
                $stmt = $this->prepare("
                    INSERT INTO static_content (content_key, content_it, context_info, page_location, created_at, updated_at)
                    VALUES (?, ?, ?, ?, datetime('now'), datetime('now'))
                ");
                $success = $stmt->execute([$contentKey, $contentItalian, $contextInfo, $pageLocation]);
                $staticContentId = $this->lastInsertId();
            }
            
            if ($success && $this->translationsEnabled) {
                // Traduci in tutte le lingue supportate
                $this->triggerStaticContentTranslation($staticContentId, $contentItalian);
            }
            
            return $success;
            
        } catch (Exception $e) {
            error_log("Errore salvataggio contenuto statico: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Avvia traduzione contenuto statico
     */
    private function triggerStaticContentTranslation($staticContentId, $originalText) {
        try {
            $supportedLanguages = $this->translationService->getSupportedLanguages();
            
            foreach ($supportedLanguages as $langCode => $langInfo) {
                // Salta lingua italiana (quella di base)
                if ($langCode === 'it') {
                    continue;
                }
                
                // Traduzione temporaneamente disabilitata
                // $this->translationService->translateStaticContentItem($staticContentId, $langCode, $originalText);
            }
            
            error_log("Traduzioni contenuto statico avviate per ID: {$staticContentId}");
            
        } catch (Exception $e) {
            error_log("Errore traduzione contenuto statico {$staticContentId}: " . $e->getMessage());
        }
    }
    
    /**
     * Ottiene articolo nella lingua richiesta
     */
    public function getArticleByLanguage($articleId, $langCode = 'it') {
        // Traduzione temporaneamente disabilitata - ritorna articolo originale
        return $this->getArticleById($articleId);
    }
    
    /**
     * Ottiene articolo per slug nella lingua richiesta
     */
    public function getArticleBySlugAndLanguage($slug, $langCode = 'it') {
        try {
            if ($langCode === 'it') {
                // Cerca nell'articolo originale
                $stmt = $this->prepare("SELECT * FROM articles WHERE slug = ? AND status = 'published'");
                $stmt->execute([$slug]);
                return $stmt->fetch(PDO::FETCH_ASSOC);
            } else {
                // Cerca nelle traduzioni
                $stmt = $this->prepare("
                    SELECT at.*, a.category_id, a.province_id, a.city_id, a.author, a.status, a.featured_image, a.views, a.latitude, a.longitude, a.created_at
                    FROM article_translations at
                    JOIN articles a ON at.article_id = a.id
                    WHERE at.slug = ? AND a.status = 'published'
                ");
                $stmt->execute([$slug]);
                $translation = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$translation && $langCode !== 'en') {
                    // Fallback alla lingua inglese
                    return $this->getArticleBySlugAndLanguage($slug . '-en', 'en');
                }
                
                return $translation;
            }
        } catch (Exception $e) {
            error_log("Errore ricerca articolo per slug e lingua: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Ottiene contenuto statico nella lingua richiesta
     */
    public function getStaticContent($contentKey, $langCode = 'it') {
        // Traduzione temporaneamente disabilitata
        return $contentKey;
    }
    
    /**
     * Ottiene elenco articoli con traduzioni per una categoria
     */
    public function getArticlesByCategoryWithTranslations($categoryId, $langCode = 'it', $limit = null, $offset = 0) {
        try {
            if ($langCode === 'it') {
                // Usa query originale per italiano
                return $this->getArticlesByCategory($categoryId, $limit, $offset);
            } else {
                // Cerca traduzioni
                $limitClause = $limit ? "LIMIT {$limit}" : "";
                $offsetClause = $offset ? "OFFSET {$offset}" : "";
                
                $stmt = $this->prepare("
                    SELECT at.*, a.category_id, a.province_id, a.city_id, a.author, a.status, a.featured_image, a.views, a.latitude, a.longitude, a.created_at
                    FROM article_translations at
                    JOIN articles a ON at.article_id = a.id
                    WHERE a.category_id = ? AND a.status = 'published'
                    ORDER BY a.created_at DESC
                    {$limitClause} {$offsetClause}
                ");
                $stmt->execute([$categoryId]);
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
        } catch (Exception $e) {
            error_log("Errore recupero articoli categoria con traduzioni: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Forza traduzione di tutti gli articoli esistenti
     */
    public function translateAllExistingArticles() {
        try {
            $stmt = $this->prepare("SELECT id, title, content, excerpt FROM articles WHERE status = 'published'");
            $stmt->execute();
            $articles = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $translatedCount = 0;
            
            foreach ($articles as $article) {
                // Traduzione temporaneamente disabilitata
                if (false) {
                    $translatedCount++;
                }
                
                // Piccola pausa per non sovraccaricare l'API
                usleep(100000); // 0.1 secondi
            }
            
            error_log("Tradotti {$translatedCount} articoli esistenti");
            return $translatedCount;
            
        } catch (Exception $e) {
            error_log("Errore traduzione articoli esistenti: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Forza traduzione di tutti i contenuti statici
     */
    public function translateAllStaticContent() {
        // Traduzione temporaneamente disabilitata
        return false;
    }
    
    /**
     * Ottiene statistiche traduzioni
     */
    public function getTranslationStats() {
        try {
            // Conta articoli originali
            $stmt = $this->prepare("SELECT COUNT(*) as total FROM articles WHERE status = 'published'");
            $stmt->execute();
            $totalArticles = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Conta traduzioni articoli per lingua
            $stmt = $this->prepare("
                SELECT language_code, COUNT(*) as count 
                FROM article_translations 
                GROUP BY language_code
            ");
            $stmt->execute();
            $articleTranslations = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Conta contenuti statici
            $stmt = $this->prepare("SELECT COUNT(*) as total FROM static_content");
            $stmt->execute();
            $totalStaticContent = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Conta traduzioni contenuti statici per lingua
            $stmt = $this->prepare("
                SELECT language_code, COUNT(*) as count 
                FROM static_content_translations 
                GROUP BY language_code
            ");
            $stmt->execute();
            $staticTranslations = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                'total_articles' => $totalArticles,
                'article_translations' => $articleTranslations,
                'total_static_content' => $totalStaticContent,
                'static_translations' => $staticTranslations
            ];
            
        } catch (Exception $e) {
            error_log("Errore statistiche traduzioni: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Ottiene servizio traduzioni
     */
    public function getTranslationService() {
        return $this->translationService;
    }
}