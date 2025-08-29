<?php
require_once '../includes/config.php';
require_once '../includes/database.php';

// Initialize database
$db = new Database();

// Get Stripe webhook secret
$webhookSecret = $db->getSetting('stripe_webhook_secret');

// Get the payload and signature
$payload = file_get_contents('php://input');
$sigHeader = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

// CRITICAL: Always verify webhook signature for security
if (!$webhookSecret) {
    error_log('Stripe webhook secret not configured');
    http_response_code(500);
    echo json_encode(['error' => 'Webhook not configured']);
    exit;
}

// Verify webhook signature
if (!verifyStripeSignature($payload, $sigHeader, $webhookSecret)) {
    error_log('Invalid Stripe webhook signature');
    http_response_code(400);
    echo json_encode(['error' => 'Invalid signature']);
    exit;
}

// Log webhook safely (without sensitive data)
error_log('Stripe Webhook received from verified source');

try {
    $event = json_decode($payload, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON in webhook payload');
    }
    
    if (!$event) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON']);
        exit;
    }
    
    // Handle different event types
    switch ($event['type'] ?? '') {
        case 'checkout.session.completed':
            handleCheckoutCompleted($db, $event['data']['object'] ?? []);
            break;
            
        case 'invoice.payment_succeeded':
            handlePaymentSucceeded($db, $event['data']['object'] ?? []);
            break;
            
        case 'customer.subscription.deleted':
            handleSubscriptionCancelled($db, $event['data']['object'] ?? []);
            break;
            
        case 'customer.subscription.updated':
            handleSubscriptionUpdated($db, $event['data']['object'] ?? []);
            break;
            
        default:
            error_log("Unhandled webhook event type: " . ($event['type'] ?? 'unknown'));
            break;
    }
    
    http_response_code(200);
    echo json_encode(['received' => true]);
    
} catch (Exception $e) {
    // Log error safely without exposing internal details
    error_log('Stripe Webhook processing error: ' . $e->getMessage());
    http_response_code(400);
    echo json_encode(['error' => 'Webhook processing failed']);
}

function verifyStripeSignature($payload, $sigHeader, $webhookSecret) {
    $elements = explode(',', $sigHeader);
    $signatures = [];
    $timestamp = null;
    
    foreach ($elements as $element) {
        $parts = explode('=', $element, 2);
        if (count($parts) === 2) {
            if ($parts[0] === 't') {
                $timestamp = (int)$parts[1];
            } elseif ($parts[0] === 'v1') {
                $signatures[] = $parts[1];
            }
        }
    }
    
    if (!$timestamp || empty($signatures)) {
        return false;
    }
    
    // Check timestamp (prevent replay attacks)
    $tolerance = 300; // 5 minutes
    if (abs(time() - $timestamp) > $tolerance) {
        error_log('Stripe webhook timestamp outside tolerance');
        return false;
    }
    
    // Verify signature
    $expectedSignature = hash_hmac('sha256', $timestamp . '.' . $payload, $webhookSecret);
    
    foreach ($signatures as $signature) {
        if (hash_equals($expectedSignature, $signature)) {
            return true;
        }
    }
    
    return false;
}

function handleCheckoutCompleted($db, $session) {
    $sessionId = $session['id'] ?? '';
    
    if (!$sessionId) {
        error_log("No session ID in checkout completed event");
        return;
    }
    
    try {
        // Get session data from database
        $stmt = $db->pdo->prepare('SELECT * FROM stripe_sessions WHERE session_id = ?');
        $stmt->execute([$sessionId]);
        $sessionData = $stmt->fetch();
        
        if (!$sessionData) {
            error_log("Session not found: " . $sessionId);
            return;
        }
        
        $businessData = json_decode($sessionData['business_data'], true);
        $packageId = $sessionData['package_id'];
        
        // Get package details
        $stmt = $db->pdo->prepare('SELECT * FROM business_packages WHERE id = ?');
        $stmt->execute([$packageId]);
        $package = $stmt->fetch();
        
        if (!$package) {
            error_log("Package not found: " . $packageId);
            return;
        }
        
        // Create business record
        $businessId = createBusiness($db, $businessData);
        
        // Create subscription
        $stmt = $db->pdo->prepare('
            INSERT INTO subscriptions (
                business_id, 
                package_id, 
                stripe_subscription_id, 
                status, 
                start_date, 
                end_date, 
                amount, 
                created_at
            ) VALUES (?, ?, ?, ?, datetime("now"), datetime("now", "+' . $package['duration_months'] . ' months"), ?, datetime("now"))
        ');
        
        $stmt->execute([
            $businessId,
            $packageId,
            $session['subscription'] ?? null,
            'active',
            $sessionData['amount']
        ]);
        
        // Update session status
        $stmt = $db->pdo->prepare('UPDATE stripe_sessions SET status = "completed" WHERE session_id = ?');
        $stmt->execute([$sessionId]);
        
        // Send confirmation email (placeholder)
        sendSubscriptionConfirmation($businessData, $package);
        
        // Log safely without exposing business data
        error_log("Subscription created successfully for business ID: " . $businessId);
        
    } catch (Exception $e) {
        error_log("Error handling checkout completed: " . $e->getMessage());
    }
}

function handlePaymentSucceeded($db, $invoice) {
    $subscriptionId = $invoice['subscription'] ?? '';
    
    if (!$subscriptionId) {
        return;
    }
    
    try {
        // Update subscription status
        $stmt = $db->pdo->prepare('UPDATE subscriptions SET status = "active" WHERE stripe_subscription_id = ?');
        $stmt->execute([$subscriptionId]);
        
        error_log("Payment succeeded for subscription ID: " . substr(md5($subscriptionId), 0, 8));
        
    } catch (Exception $e) {
        error_log("Error handling payment succeeded: " . $e->getMessage());
    }
}

function handleSubscriptionCancelled($db, $subscription) {
    $subscriptionId = $subscription['id'] ?? '';
    
    if (!$subscriptionId) {
        return;
    }
    
    try {
        // Update subscription status
        $stmt = $db->pdo->prepare('UPDATE subscriptions SET status = "cancelled" WHERE stripe_subscription_id = ?');
        $stmt->execute([$subscriptionId]);
        
        error_log("Subscription cancelled: " . substr(md5($subscriptionId), 0, 8));
        
    } catch (Exception $e) {
        error_log("Error handling subscription cancelled: " . $e->getMessage());
    }
}

function handleSubscriptionUpdated($db, $subscription) {
    $subscriptionId = $subscription['id'] ?? '';
    $status = $subscription['status'] ?? '';
    
    if (!$subscriptionId) {
        return;
    }
    
    try {
        // Map Stripe status to our status
        $mappedStatus = match($status) {
            'active' => 'active',
            'canceled' => 'cancelled',
            'past_due' => 'expired',
            'unpaid' => 'expired',
            default => 'pending'
        };
        
        // Update subscription status
        $stmt = $db->pdo->prepare('UPDATE subscriptions SET status = ? WHERE stripe_subscription_id = ?');
        $stmt->execute([$mappedStatus, $subscriptionId]);
        
        error_log("Subscription updated: " . substr(md5($subscriptionId), 0, 8) . " -> " . $mappedStatus);
        
    } catch (Exception $e) {
        error_log("Error handling subscription updated: " . $e->getMessage());
    }
}

function createBusiness($db, $businessData) {
    $stmt = $db->pdo->prepare('
        INSERT INTO businesses (
            name, 
            email, 
            phone, 
            website, 
            description, 
            category_id, 
            province_id, 
            city_id, 
            address, 
            status, 
            subscription_type, 
            created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, "pending", "premium", datetime("now"))
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

function sendSubscriptionConfirmation($businessData, $package) {
    // Placeholder for email sending
    // In a real environment, you would send an email here
    error_log("Sending confirmation email to: " . $businessData['email'] . " for package: " . $package['name']);
    
    // You could use PHPMailer, mail() function, or an email service API
    /*
    $to = $businessData['email'];
    $subject = "Abbonamento Attivato - Passione Calabria";
    $message = "Il tuo abbonamento " . $package['name'] . " è stato attivato con successo!";
    mail($to, $subject, $message);
    */
}
?>