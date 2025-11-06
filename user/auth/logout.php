<?php
require_once __DIR__ . '/../../includes/config/init.php';
require_once __DIR__ . '/../../includes/functions/security_helpers.php';

// Log logout event
if (isset($_SESSION['user_id'])) {
    logSecurityEvent('user_logout', [
        'user_id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'] ?? 'unknown'
    ]);
}

// Clear all session variables
$_SESSION = array();

// Delete the session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Clear remember me cookies and tokens if they exist
if (isset($_COOKIE['remember_token'])) {
    deleteRememberToken($_COOKIE['remember_token'], $pdo);
    setcookie('remember_token', '', time() - 3600, '/', '', true, true);
}
if (isset($_COOKIE['user_id'])) {
    setcookie('user_id', '', time() - 3600, '/', '', true, true);
}

// Destroy the session
session_destroy();

// Redirect to homepage after logout
header("Location: " . BASE_URL . "/index.php");
exit();
?>

