<?php
/**
 * Newsletter Subscription Handler
 * Handles newsletter subscription and unsubscription
 */

// Configure secure session settings BEFORE starting session
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.gc_maxlifetime', 1800); // 30 minutes
ini_set('session.cookie_lifetime', 0); // Session cookie

// Only enable secure cookies for HTTPS (production)
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    ini_set('session.cookie_secure', 1);
} else {
    ini_set('session.cookie_secure', 0); // Disabled for localhost/HTTP
}

session_start();
// Database connection now in init.php

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
    
    if (!$email) {
        $response['message'] = 'Please enter a valid email address.';
        echo json_encode($response);
        exit();
    }
    
    try {
        switch ($action) {
            case 'subscribe':
                $firstName = trim($_POST['first_name'] ?? '');
                $lastName = trim($_POST['last_name'] ?? '');
                $source = $_POST['source'] ?? 'website';
                
                // Generate unsubscribe token
                $unsubscribeToken = bin2hex(random_bytes(32));
                
                // Check if email already exists
                $checkStmt = $pdo->prepare("SELECT id, subscription_status FROM newsletter_subscribers WHERE email = ?");
                $checkStmt->execute([$email]);
                $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);
                
                if ($existing) {
                    if ($existing['subscription_status'] === 'active') {
                        $response['message'] = 'This email is already subscribed to our newsletter.';
                    } else {
                        // Reactivate subscription
                        $updateStmt = $pdo->prepare("
                            UPDATE newsletter_subscribers 
                            SET subscription_status = 'active', 
                                first_name = ?, 
                                last_name = ?, 
                                unsubscribe_token = ?,
                                updated_at = NOW()
                            WHERE email = ?
                        ");
                        $updateStmt->execute([$firstName, $lastName, $unsubscribeToken, $email]);
                        
                        $response['success'] = true;
                        $response['message'] = 'Welcome back! Your newsletter subscription has been reactivated.';
                    }
                } else {
                    // New subscription
                    $insertStmt = $pdo->prepare("
                        INSERT INTO newsletter_subscribers (email, first_name, last_name, subscription_status, unsubscribe_token, source)
                        VALUES (?, ?, ?, 'active', ?, ?)
                    ");
                    $insertStmt->execute([$email, $firstName, $lastName, $unsubscribeToken, $source]);
                    
                    $response['success'] = true;
                    $response['message'] = 'Thank you for subscribing to our newsletter! You will receive updates about our latest products and offers.';
                }
                break;
                
            case 'unsubscribe':
                $token = $_POST['token'] ?? '';
                
                if ($token) {
                    // Unsubscribe by token
                    $updateStmt = $pdo->prepare("
                        UPDATE newsletter_subscribers 
                        SET subscription_status = 'unsubscribed', updated_at = NOW()
                        WHERE unsubscribe_token = ?
                    ");
                    $updateStmt->execute([$token]);
                    
                    if ($updateStmt->rowCount() > 0) {
                        $response['success'] = true;
                        $response['message'] = 'You have been successfully unsubscribed from our newsletter.';
                    } else {
                        $response['message'] = 'Invalid unsubscribe link.';
                    }
                } else {
                    // Unsubscribe by email
                    $updateStmt = $pdo->prepare("
                        UPDATE newsletter_subscribers 
                        SET subscription_status = 'unsubscribed', updated_at = NOW()
                        WHERE email = ?
                    ");
                    $updateStmt->execute([$email]);
                    
                    if ($updateStmt->rowCount() > 0) {
                        $response['success'] = true;
                        $response['message'] = 'You have been successfully unsubscribed from our newsletter.';
                    } else {
                        $response['message'] = 'Email address not found in our newsletter list.';
                    }
                }
                break;
                
            default:
                $response['message'] = 'Invalid action.';
        }
        
    } catch (Exception $e) {
        error_log("Newsletter subscription error: " . $e->getMessage());
        $response['message'] = 'An error occurred. Please try again later.';
    }
} else {
    $response['message'] = 'Invalid request method.';
}

echo json_encode($response);
?>

