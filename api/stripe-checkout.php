<?php
require_once '../includes/config.php';
require_once '../includes/database.php';

// Enable CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Initialize database
$db = new Database();

// Get Stripe settings
$stripePublishableKey = $db->getSetting('stripe_publishable_key');
$stripeSecretKey = $db->getSetting('stripe_secret_key');

if (!$stripeSecretKey) {
    http_response_code(400);
    echo json_encode([
        'error' => 'Stripe not configured',
        'message' => 'Le chiavi Stripe non sono configurate. Contatta l\'amministratore.'
    ]);
    exit;
}

// Simulate Stripe PHP SDK (since we can't install it)
// In a real environment, you would use: require_once 'vendor/autoload.php'; \Stripe\Stripe::setApiKey($stripeSecretKey);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $packageId = $input['package_id'] ?? null;
    $businessData = $input['business_data'] ?? [];
    
    if (!$packageId) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing package_id']);
        exit;
    }
    
    // Get package details
    try {
        $stmt = $db->pdo->prepare('SELECT * FROM business_packages WHERE id = ? AND is_active = 1');
        $stmt->execute([$packageId]);
        $package = $stmt->fetch();
        
        if (!$package) {
            http_response_code(404);
            echo json_encode(['error' => 'Package not found']);
            exit;
        }
        
        // For free packages, create subscription directly
        if ($package['price'] == 0) {
            // Create business record
            $businessId = createBusiness($db, $businessData);
            
            // Create free subscription
            $stmt = $db->pdo->prepare('
                INSERT INTO subscriptions (business_id, package_id, status, start_date, end_date, amount, created_at)
                VALUES (?, ?, ?, datetime("now"), datetime("now", "+' . $package['duration_months'] . ' months"), 0, datetime("now"))
            ');
            $stmt->execute([$businessId, $packageId, 'active']);
            
            echo json_encode([
                'success' => true,
                'type' => 'free',
                'message' => 'Registrazione gratuita completata!',
                'subscription_id' => $db->pdo->lastInsertId()
            ]);
            exit;
        }
        
        // For paid packages, create Stripe checkout session
        // In a real environment, this would create an actual Stripe checkout session
        $sessionData = [
            'id' => 'cs_test_' . uniqid(),
            'url' => 'https://checkout.stripe.com/pay/cs_test_' . uniqid() . '#fidkdWxOYHwnPyd1blpxYHZxWjA0TGhsRTFxNXJyV0g9VHNEfXVxY3xxf2tNQFJqTFFwTGhLS3BKZmI2YU05T01UU1N0fFRQckRnS2xAMVRiMHJ9YGNubFNsazJHT2pUNHxxTG9BPTR9bGhDSldxMEtqcCcpJ3VpbGtuQH11anZgYUxhJz8nYGlANEx1Tml1bDZBNEA2PScpJ2hsYXYnP3F3cGApJ2lkfGpwcVF8dWAneCUl',
            'package_id' => $packageId,
            'business_data' => $businessData,
            'amount' => $package['price'],
            'currency' => 'eur'
        ];
        
        // Store session data for webhook processing
        $stmt = $db->pdo->prepare('
            INSERT INTO stripe_sessions (session_id, package_id, business_data, amount, status, created_at)
            VALUES (?, ?, ?, ?, "pending", datetime("now"))
        ');
        
        // Create stripe_sessions table if it doesn't exist
        $db->pdo->exec('
            CREATE TABLE IF NOT EXISTS stripe_sessions (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                session_id TEXT UNIQUE,
                package_id INTEGER,
                business_data TEXT,
                amount REAL,
                status TEXT,
                created_at DATETIME,
                FOREIGN KEY (package_id) REFERENCES business_packages (id)
            )
        ');
        
        $stmt->execute([$sessionData['id'], $packageId, json_encode($businessData), $package['price']]);
        
        echo json_encode([
            'success' => true,
            'type' => 'paid',
            'checkout_url' => $sessionData['url'],
            'session_id' => $sessionData['id']
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'error' => 'Internal error',
            'message' => 'Errore durante la creazione della sessione di pagamento.'
        ]);
    }
}

function createBusiness($db, $businessData) {
    $stmt = $db->pdo->prepare('
        INSERT INTO businesses (name, email, phone, website, description, category_id, province_id, city_id, address, status, subscription_type, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, "pending", "premium", datetime("now"))
    ');
    
    $stmt->execute([
        $businessData['name'] ?? '',
        $businessData['email'] ?? '',
        $businessData['phone'] ?? '',
        $businessData['website'] ?? '',
        $businessData['description'] ?? '',
        $businessData['category_id'] ?? null,
        $businessData['province_id'] ?? null,
        $businessData['city_id'] ?? null,
        $businessData['address'] ?? ''
    ]);
    
    return $db->pdo->lastInsertId();
}

function validateBusinessData($data) {
    $required = ['name', 'email'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            return false;
        }
    }
    return filter_var($data['email'], FILTER_VALIDATE_EMAIL) !== false;
}
?>