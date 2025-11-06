<?php
/**
 * User Authentication Middleware
 * Ensures only authenticated users can access protected user pages
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get the current file name and path
$current_file = basename($_SERVER['PHP_SELF']);
$current_dir = basename(dirname($_SERVER['PHP_SELF']));

// Directories and pages that don't require authentication
$public_dirs = ['auth', 'products', 'services'];
$public_pages = ['login.php', 'register.php', 'index.php', 'details.php', 'search.php', 'reviews.php'];

// Check if authentication is required
$requires_auth = true;

if (in_array($current_dir, $public_dirs) || in_array($current_file, $public_pages)) {
    $requires_auth = false;
}

// If authentication is required and user is not logged in
if ($requires_auth) {
    if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
        // Store the intended destination
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        
        // Redirect to login page
        header('Location: ' . USER_URL . '/auth/login.php');
        exit();
    }
    
    // Optional: Check if session has expired
    if (isset($_SESSION['last_activity'])) {
        $inactive_time = time() - $_SESSION['last_activity'];
        
        if ($inactive_time > SESSION_LIFETIME) {
            // Session expired
            session_unset();
            session_destroy();
            
            header('Location: ' . USER_URL . '/auth/login.php?session_expired=1');
            exit();
        }
    }
    
    // Update last activity time
    $_SESSION['last_activity'] = time();
    
    // Optional: Regenerate session ID periodically for security
    if (!isset($_SESSION['created'])) {
        $_SESSION['created'] = time();
    } else if (time() - $_SESSION['created'] > 1800) { // 30 minutes
        session_regenerate_id(true);
        $_SESSION['created'] = time();
    }
}


