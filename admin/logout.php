<?php
require_once dirname(__DIR__) . '/includes/config/init.php';
require_once INCLUDES_PATH . '/functions/security_helpers.php';

// Start session to access session data
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Log logout event
if (isset($_SESSION['admin_id'])) {
    logSecurityEvent('ADMIN_LOGOUT', ['admin_id' => $_SESSION['admin_id'], 'username' => $_SESSION['admin_username'] ?? 'unknown']);
}

// Clear remember me token if it exists
if (isset($_COOKIE['admin_remember_token'])) {
    // Invalidate the token in database
    try {
        $token_hash = hash('sha256', $_COOKIE['admin_remember_token']);
        $stmt = $pdo->prepare("UPDATE remember_tokens SET is_active = 0 WHERE token_hash = ?");
        $stmt->execute([$token_hash]);
    } catch (PDOException $e) {
        error_log("Error invalidating remember token: " . $e->getMessage());
    }
    
    // Clear the cookie
    setcookie('admin_remember_token', '', time() - 3600, '/', '', isset($_SERVER['HTTPS']), true);
}

// Destroy session
session_destroy();

// Redirect to login page
header('Location: ' . admin_url('login'));
exit();

