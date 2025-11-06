<?php
// AJAX endpoint for wishlist operations
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Configure secure session settings BEFORE starting session (only if session not already active)
if (session_status() === PHP_SESSION_NONE) {
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
}

// Ensure session is properly started and available
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set session cookie parameters to ensure it's available for AJAX
if (!isset($_COOKIE[session_name()])) {
    // Session cookie might not be set, try to start a new session
    session_regenerate_id(true);
    session_start();
}

require_once __DIR__ . '/../../includes/config/init.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'You must be logged in to manage your wishlist',
        'debug' => [
            'session_exists' => isset($_SESSION),
            'session_id' => session_id(),
            'user_id' => $_SESSION['user_id'] ?? 'not set',
            'cookies' => $_COOKIE,
            'session_status' => session_status()
        ]
    ]);
    exit();
}

$user_id = $_SESSION['user_id'];

// Parse JSON data from request body
$input = file_get_contents('php://input');
$data = [];

// Debug raw input
error_log('Wishlist AJAX - Raw input: ' . $input);

// Try to decode JSON input
if (!empty($input)) {
    $decoded = json_decode($input, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        $data = $decoded;
        error_log('Wishlist AJAX - JSON decoded successfully: ' . print_r($data, true));
    } else {
        error_log('Wishlist AJAX - JSON decode error: ' . json_last_error_msg());
    }
} else {
    error_log('Wishlist AJAX - No input data received');
}

// Extract data from JSON (primary) or POST (fallback for compatibility)
$action = $data['action'] ?? $_POST['action'] ?? '';
$product_id = isset($data['product_id']) ? intval($data['product_id']) : (isset($_POST['product_id']) ? intval($_POST['product_id']) : 0);

// Debug logging
error_log('Wishlist AJAX - Action: ' . $action . ', Product ID: ' . $product_id);
error_log('Wishlist AJAX - JSON data: ' . print_r($data, true));
error_log('Wishlist AJAX - POST data: ' . print_r($_POST, true));

if (!$product_id) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid or missing product ID',
        'debug' => [
            'action' => $action,
            'product_id' => $product_id,
            'json_data' => $data,
            'post_data' => $_POST,
            'raw_input' => $input
        ]
    ]);
    exit();
}

try {
    if ($action === 'add') {
        // Check if product is already in wishlist
        $check_stmt = $pdo->prepare("SELECT id FROM wishlist WHERE user_id = ? AND product_id = ?");
        $check_stmt->execute([$user_id, $product_id]);

        if ($check_stmt->rowCount() > 0) {
            echo json_encode([
                'success' => false,
                'message' => 'Product is already in your wishlist'
            ]);
            exit();
        }

        // Check if product exists and is active
        $product_stmt = $pdo->prepare("SELECT id, product_name FROM products WHERE id = ? AND status = 'active'");
        $product_stmt->execute([$product_id]);
        $product = $product_stmt->fetch(PDO::FETCH_ASSOC);

        if (!$product) {
            echo json_encode([
                'success' => false,
                'message' => 'Product not found or unavailable'
            ]);
            exit();
        }

        // Add to wishlist
        $insert_stmt = $pdo->prepare("INSERT INTO wishlist (user_id, product_id) VALUES (?, ?)");
        $insert_stmt->execute([$user_id, $product_id]);

        echo json_encode([
            'success' => true,
            'message' => 'Product added to wishlist',
            'action' => 'added'
        ]);

    } elseif ($action === 'remove') {
        // Remove from wishlist
        $delete_stmt = $pdo->prepare("DELETE FROM wishlist WHERE user_id = ? AND product_id = ?");
        $delete_stmt->execute([$user_id, $product_id]);

        if ($delete_stmt->rowCount() > 0) {
            echo json_encode([
                'success' => true,
                'message' => 'Product removed from wishlist',
                'action' => 'removed'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Product was not in your wishlist'
            ]);
        }

    } elseif ($action === 'check') {
        // Check if product is in wishlist
        $check_stmt = $pdo->prepare("SELECT id FROM wishlist WHERE user_id = ? AND product_id = ?");
        $check_stmt->execute([$user_id, $product_id]);

        echo json_encode([
            'success' => true,
            'in_wishlist' => $check_stmt->rowCount() > 0
        ]);

    } else {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Invalid action'
        ]);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
