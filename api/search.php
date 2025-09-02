<?php
require_once '../includes/config.php';

// Rate limiting per sicurezza
session_start();
$client_ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$rate_limit_key = 'search_rate_limit_' . $client_ip;
$current_time = time();
$rate_limit = 60; // 60 richieste per minuto

if (!isset($_SESSION[$rate_limit_key])) {
    $_SESSION[$rate_limit_key] = [];
}

// Pulisce vecchie richieste (oltre 1 minuto)
$_SESSION[$rate_limit_key] = array_filter($_SESSION[$rate_limit_key], function($timestamp) use ($current_time) {
    return ($current_time - $timestamp) < 60;
});

// Controlla rate limiting
if (count($_SESSION[$rate_limit_key]) >= $rate_limit) {
    http_response_code(429);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Troppe richieste. Riprova tra un minuto.']);
    exit;
}

$_SESSION[$rate_limit_key][] = $current_time;
require_once '../includes/database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

try {
    $db = new Database();

    // Ottieni e valida parametri di ricerca
    $query = isset($_GET['q']) ? sanitize($_GET['q']) : '';
    $provinceId = isset($_GET['province']) && is_numeric($_GET['province']) ? (int)$_GET['province'] : null;
    $categoryId = isset($_GET['category']) && is_numeric($_GET['category']) ? (int)$_GET['category'] : null;
    $mapData = isset($_GET['map_data']) ? (bool)$_GET['map_data'] : null;
    $limit = isset($_GET['limit']) && is_numeric($_GET['limit']) ? max(1, min(100, (int)$_GET['limit'])) : 20;
    $offset = isset($_GET['offset']) && is_numeric($_GET['offset']) ? max(0, (int)$_GET['offset']) : 0;
    
    // Validazione lunghezza query
    if (strlen($query) > 200) {
        jsonResponse([
            'success' => false,
            'message' => 'Query troppo lunga (max 200 caratteri)'
        ], 400);
    }

    // Gestione speciale per dati mappa
    if ($mapData) {
        handleMapDataRequest($db);
        exit;
    }

    if (empty($query) && !$provinceId && !$categoryId) {
        jsonResponse([
            'success' => false,
            'message' => 'Parametri di ricerca mancanti'
        ], 400);
    }

    $results = [];

    if ($query) {
        // Ricerca testuale
        $articles = $db->searchArticles($query, $provinceId);

        foreach ($articles as $article) {
            $results[] = [
                'type' => 'article',
                'id' => $article['id'],
                'title' => $article['title'],
                'excerpt' => $article['excerpt'],
                'slug' => $article['slug'],
                'category' => $article['category_name'],
                'province' => $article['province_name'],
                'featured_image' => $article['featured_image'],
                'created_at' => $article['created_at'],
                'views' => $article['views']
            ];
        }

        // Cerca anche nelle categorie (usando metodo sicuro)
        $categories = $db->searchCategories($query, 5);

        foreach ($categories as $category) {
            $articleCount = $db->getArticleCountByCategory($category['id']);
            $results[] = [
                'type' => 'category',
                'id' => $category['id'],
                'title' => $category['name'],
                'description' => $category['description'],
                'icon' => $category['icon'],
                'article_count' => $articleCount,
                'url' => "categoria.php?id={$category['id']}"
            ];
        }

        // Cerca nelle province
        $stmt = $db->pdo->prepare('
            SELECT * FROM provinces
            WHERE name LIKE ? OR description LIKE ?
            LIMIT 5
        ');
        $stmt->execute(["%$query%", "%$query%"]);
        $provinces = $stmt->fetchAll();

        foreach ($provinces as $province) {
            $articleCount = $db->getArticleCountByProvince($province['id']);
            $results[] = [
                'type' => 'province',
                'id' => $province['id'],
                'title' => $province['name'],
                'description' => $province['description'],
                'article_count' => $articleCount,
                'url' => "provincia.php?id={$province['id']}"
            ];
        }

        // Cerca nelle città
        $stmt = $db->pdo->prepare('
            SELECT c.*, p.name as province_name
            FROM cities c
            LEFT JOIN provinces p ON c.province_id = p.id
            WHERE c.name LIKE ? OR c.description LIKE ?
            LIMIT 5
        ');
        $stmt->execute(["%$query%", "%$query%"]);
        $cities = $stmt->fetchAll();

        foreach ($cities as $city) {
            $results[] = [
                'type' => 'city',
                'id' => $city['id'],
                'title' => $city['name'],
                'description' => $city['description'],
                'province' => $city['province_name'],
                'latitude' => $city['latitude'],
                'longitude' => $city['longitude']
            ];
        }

    } elseif ($categoryId) {
        // Ricerca per categoria
        $articles = $db->getArticlesByCategory($categoryId, $limit);

        foreach ($articles as $article) {
            $results[] = [
                'type' => 'article',
                'id' => $article['id'],
                'title' => $article['title'],
                'excerpt' => $article['excerpt'],
                'slug' => $article['slug'],
                'category' => $article['category_name'],
                'province' => $article['province_name'],
                'featured_image' => $article['featured_image'],
                'created_at' => $article['created_at'],
                'views' => $article['views']
            ];
        }

    } elseif ($provinceId) {
        // Ricerca per provincia
        $articles = $db->getArticlesByProvince($provinceId, $limit);

        foreach ($articles as $article) {
            $results[] = [
                'type' => 'article',
                'id' => $article['id'],
                'title' => $article['title'],
                'excerpt' => $article['excerpt'],
                'slug' => $article['slug'],
                'category' => $article['category_name'],
                'province' => $article['province_name'],
                'featured_image' => $article['featured_image'],
                'created_at' => $article['created_at'],
                'views' => $article['views']
            ];
        }
    }

    // Applica limit e offset
    $totalResults = count($results);
    $results = array_slice($results, $offset, $limit);

    jsonResponse([
        'success' => true,
        'results' => $results,
        'total' => $totalResults,
        'query' => $query,
        'filters' => [
            'province_id' => $provinceId,
            'category_id' => $categoryId
        ],
        'pagination' => [
            'limit' => $limit,
            'offset' => $offset,
            'has_more' => ($offset + $limit) < $totalResults
        ]
    ]);

} catch (Exception $e) {
    error_log('Errore API ricerca: ' . $e->getMessage());

    jsonResponse([
        'success' => false,
        'message' => 'Errore interno del server',
        'error' => $e->getMessage()
    ], 500);
}

// Funzione per gestire richieste dati mappa
function handleMapDataRequest($db) {
    try {
        // Ottieni tutte le città con le loro coordinate e informazioni
        $stmt = $db->pdo->prepare('
            SELECT 
                c.*, 
                p.name as province_name,
                p.id as province_id,
                (
                    SELECT cat.name 
                    FROM categories cat
                    JOIN articles a ON cat.id = a.category_id
                    WHERE a.city_id = c.id
                    GROUP BY cat.id
                    ORDER BY COUNT(*) DESC
                    LIMIT 1
                ) as main_category
            FROM cities c
            LEFT JOIN provinces p ON c.province_id = p.id
            WHERE c.latitude IS NOT NULL 
            AND c.longitude IS NOT NULL
        ');
        $stmt->execute();
        $cities = $stmt->fetchAll();

        // Converti coordinate in numeri
        foreach ($cities as &$city) {
            $city['lat'] = (float)$city['latitude'];
            $city['lng'] = (float)$city['longitude'];
        }

        jsonResponse([
            'success' => true,
            'cities' => $cities
        ]);

    } catch (Exception $e) {
        error_log('Errore dati mappa: ' . $e->getMessage());
        jsonResponse([
            'success' => false,
            'message' => 'Errore caricamento dati mappa'
        ], 500);
    }
}

?>
