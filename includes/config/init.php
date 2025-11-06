<?php
/**
 * Application Initialization File
 * Include this file at the beginning of every PHP file
 */

// Define application constant
define('AC_APP', true);

// Start session first if not already started (skip if session already active)
if (session_status() === PHP_SESSION_NONE) {
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
}

// Set base path - Fixed to match ROOT_PATH
define('BASE_PATH', dirname(dirname(__DIR__)));

// Include constants
require_once __DIR__ . '/constants.php';

// Include security configuration
require_once __DIR__ . '/security.php';

// Include database connection
require_once __DIR__ . '/database.php';

// Include helper functions
require_once __DIR__ . '/../functions/helpers.php';

// Include security helper functions
require_once __DIR__ . '/../functions/security_helpers.php';

// Include URL helper functions
require_once __DIR__ . '/../functions/url_helpers.php';

// Include URL configuration
require_once __DIR__ . '/urls.php';

// Include SEO configuration
require_once __DIR__ . '/seo_config.php';

// Include email helpers if exists
if (file_exists(__DIR__ . '/../functions/email_helpers.php')) {
    require_once __DIR__ . '/../functions/email_helpers.php';
}

// Set default timezone
date_default_timezone_set('Asia/Kolkata');

// Set no-cache headers for PHP files to prevent 304 responses
// Skip for image.php and sitemap.php which have their own cache headers
$current_script = basename($_SERVER['PHP_SELF'] ?? '');
$request_uri = $_SERVER['REQUEST_URI'] ?? '';
$script_name = $_SERVER['SCRIPT_NAME'] ?? '';

// Check if this is image.php or sitemap.php by multiple methods
$skip_cache_headers = (
    $current_script === 'image.php' || 
    $current_script === 'sitemap.php' ||
    strpos($request_uri, '/image.php') !== false ||
    strpos($request_uri, '/sitemap.php') !== false ||
    strpos($script_name, 'image.php') !== false ||
    strpos($script_name, 'sitemap.php') !== false
);

if (!headers_sent() && !$skip_cache_headers) {
    header('Cache-Control: no-cache, no-store, must-revalidate, max-age=0, private');
    header('Pragma: no-cache');
    header('Expires: 0');
    // Remove ETag and Last-Modified headers if they exist
    header_remove('ETag');
    header_remove('Last-Modified');
}

// Error handling based on environment
if (APP_ENV === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
    
    // Custom error handler
    set_error_handler(function($errno, $errstr, $errfile, $errline) {
        $error_message = "Error [$errno]: $errstr in $errfile on line $errline";
        log_error($error_message);
        
        // Show user-friendly error page
        if (APP_ENV === 'production') {
            include PUBLIC_PATH . '/500.html';
            exit();
        }
    });
    
    // Custom exception handler
    set_exception_handler(function($exception) {
        $error_message = "Exception: " . $exception->getMessage() . " in " . 
                        $exception->getFile() . " on line " . $exception->getLine();
        log_error($error_message);
        
        // Show user-friendly error page
        if (APP_ENV === 'production') {
            include PUBLIC_PATH . '/500.html';
            exit();
        }
    });
}

// CSRF Protection
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = generateSecureToken();
}

// Auto-login with remember me token if no session exists
if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_token'])) {
    $user_data = validateRememberToken($_COOKIE['remember_token'], $pdo);
    if ($user_data) {
        // Regenerate session ID for security
        session_regenerate_id(true);
        
        // Set session variables
        $_SESSION['user_id'] = $user_data['id'];
        $_SESSION['username'] = $user_data['username'];
        $_SESSION['email'] = $user_data['email'];
        $_SESSION['last_activity'] = time();
        $_SESSION['created'] = time();
        $_SESSION['login_method'] = 'remember_token';
        
        // Log auto-login
        logSecurityEvent('auto_login_remember_token', [
            'user_id' => $user_data['id'],
            'username' => $user_data['username']
        ]);
        
        error_log("Auto-login successful via remember token for user: " . $user_data['username']);
    } else {
        // Invalid token, delete cookie
        setcookie('remember_token', '', time() - 3600, '/', '', true, true);
    }
}

/**
 * Generate CSRF token field for forms
 * 
 * @return string HTML input field with CSRF token
 */
function csrf_field() {
    return '<input type="hidden" name="csrf_token" value="' . $_SESSION['csrf_token'] . '">';
}

// CSRF validation function is now in security_helpers.php

// Auto-load composer dependencies if available
if (file_exists(ROOT_PATH . '/vendor/autoload.php')) {
    require_once ROOT_PATH . '/vendor/autoload.php';
}


