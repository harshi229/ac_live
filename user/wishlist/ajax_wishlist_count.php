<?php
// AJAX endpoint for getting wishlist count
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

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
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'User not logged in',
        'debug' => [
            'session_exists' => isset($_SESSION),
            'session_id' => session_id(),
            'user_id' => $_SESSION['user_id'] ?? 'not set',
            'cookies' => $_COOKIE
        ]
    ]);
    exit();
}

$user_id = $_SESSION['user_id'];

// Debug session information
error_log('Wishlist Count - Session ID: ' . session_id());
error_log('Wishlist Count - User ID: ' . $user_id);
error_log('Wishlist Count - Session data: ' . print_r($_SESSION, true));

// Parse JSON data from request body
$input = file_get_contents('php://input');
$data = [];

// Debug raw input
error_log('Wishlist Count - Raw input: ' . $input);

// Try to decode JSON input
if (!empty($input)) {
    $decoded = json_decode($input, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        $data = $decoded;
        error_log('Wishlist Count - JSON decoded successfully: ' . print_r($data, true));
    } else {
        error_log('Wishlist Count - JSON decode error: ' . json_last_error_msg());
    }
}

// Use session user ID since it's already validated above
$request_user_id = $user_id;

error_log('Wishlist Count - Using session user ID: ' . $request_user_id);

try {
    // Get wishlist count
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM wishlist WHERE user_id = ?");
    $stmt->execute([$request_user_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'count' => $result['count'] ?? 0
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
