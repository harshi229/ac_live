<?php
/**
 * Application Constants
 * Define all path and URL constants for the application
 */

// Prevent direct access
if (!defined('AC_APP')) {
    die('Direct access not permitted');
}

// Base paths - Fixed to use correct directory structure
define('ROOT_PATH', dirname(dirname(__DIR__)));
define('PUBLIC_PATH', ROOT_PATH . '/public');
define('INCLUDES_PATH', ROOT_PATH . '/includes');
define('ADMIN_PATH', ROOT_PATH . '/admin');
define('USER_PATH', ROOT_PATH . '/user');
define('API_PATH', ROOT_PATH . '/api');
define('DATABASE_PATH', ROOT_PATH . '/database');

// URL paths - Update these according to your server configuration
// ====================================================================
// PRODUCTION URL CONFIGURATION
// ====================================================================
// If your live server has incorrect HTTP_HOST/SERVER_NAME values,
// you can manually set the production URL by creating a file called 
// '.production_url' in the includes/config/ directory with the URL,
// OR by setting an environment variable PRODUCTION_URL
// Example: https://akashaircon.com
// ====================================================================

// First, detect if we're on localhost to prevent production URL override
$host = $_SERVER['SERVER_NAME'] ?? $_SERVER['HTTP_HOST'] ?? 'localhost';
$http_host = $_SERVER['HTTP_HOST'] ?? '';

$is_local = (
    $host === 'localhost' || 
    $host === '127.0.0.1' || 
    strpos($host, 'localhost') !== false ||
    strpos($host, '127.0.0.1') !== false ||
    strpos($http_host, 'localhost') !== false ||
    strpos($http_host, '127.0.0.1') !== false ||
    strpos($host, '192.168.') !== false ||
    strpos($host, '10.') !== false ||
    strpos($host, 'public_html') !== false
);

// Only use production URL override if we're NOT on localhost
$production_url = null;
if (!$is_local) {
    // Check for manual override from environment variable first
    $production_url = getenv('PRODUCTION_URL');
    
    // Check for .production_url file (safer than env vars in some setups)
    if (!$production_url && file_exists(__DIR__ . '/.production_url')) {
        $production_url = trim(file_get_contents(__DIR__ . '/.production_url'));
    }
    
    // Check for manual override constant
    if (defined('FORCE_PRODUCTION_URL')) {
        $production_url = FORCE_PRODUCTION_URL;
    }
}

// Use manual override if set (and not on localhost)
if (!empty($production_url) && !$is_local) {
    define('BASE_URL', rtrim($production_url, '/'));
} else {
    // Determine protocol (https or http)
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    // Also check if forwarded from a proxy (common in production)
    if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
        $protocol = 'https';
    }
    
    // If we're on localhost, we're definitely NOT in production
    if ($is_local) {
        $is_production = false;
    } else {
        // Only check for production domain if we're NOT on localhost
        // Check if we're on production domain (akashaircon.com or www.akashaircon.com)
        // Only check HTTP_HOST and SERVER_NAME - ignore other server variables
        $is_production = (
            strpos($host, 'akashaircon.com') !== false || 
            strpos($host, 'www.akashaircon.com') !== false ||
            strpos($http_host, 'akashaircon.com') !== false ||
            strpos($http_host, 'www.akashaircon.com') !== false
        );
    }
    
    // Use root path for production domain, /public_html for local development
    $base = '';
    if (!$is_production) {
        $base = '/public_html'; // Local development path
    }
    
    // Final BASE_URL construction
    define('BASE_URL', $protocol . '://' . $host . $base);
}
define('PUBLIC_URL', BASE_URL . '/public');
define('ADMIN_URL', BASE_URL . '/admin');
define('USER_URL', BASE_URL . '/user');
define('API_URL', BASE_URL . '/api');

// Asset URLs
define('CSS_URL', PUBLIC_URL . '/css');
define('JS_URL', PUBLIC_URL . '/js');
define('IMG_URL', PUBLIC_URL . '/img');
define('UPLOAD_URL', PUBLIC_URL . '/img/uploads');
define('UPLOAD_PATH', PUBLIC_PATH . '/img/uploads');

// Application settings
define('APP_NAME', 'Akash Enterprise - AC System');
define('APP_VERSION', '2.0.0');

// Detect environment automatically
// Check if we're on localhost/local development by checking $_SERVER directly
$detect_host = $_SERVER['SERVER_NAME'] ?? $_SERVER['HTTP_HOST'] ?? 'localhost';
$detect_http_host = $_SERVER['HTTP_HOST'] ?? '';

$is_local_env = (
    $detect_host === 'localhost' || 
    $detect_host === '127.0.0.1' || 
    strpos($detect_host, 'localhost') !== false ||
    strpos($detect_host, '127.0.0.1') !== false ||
    strpos($detect_http_host, 'localhost') !== false ||
    strpos($detect_http_host, '127.0.0.1') !== false ||
    strpos($detect_host, '192.168.') !== false || // Local network IPs
    strpos($detect_host, '10.') !== false || // Local network IPs
    strpos($detect_host, 'public_html') !== false // If host contains public_html, it's local
);

// Set environment based on detection
if ($is_local_env) {
    define('APP_ENV', 'development');
} else {
    define('APP_ENV', 'production');
}

// Session settings
define('SESSION_NAME', 'ac_session');
define('SESSION_LIFETIME', 3600); // 1 hour in seconds

// Pagination
define('ITEMS_PER_PAGE', 12);
define('ADMIN_ITEMS_PER_PAGE', 20);

// File upload settings
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp']);
define('ALLOWED_IMAGE_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'webp']);

// Email settings
define('ADMIN_EMAIL', 'admin@akashenterprise.com');
define('SUPPORT_EMAIL', 'support@akashenterprise.com');
define('NO_REPLY_EMAIL', 'noreply@akashenterprise.com');

// Order status options
define('ORDER_STATUS_PENDING', 'Pending');
define('ORDER_STATUS_CONFIRMED', 'Confirmed');
define('ORDER_STATUS_SHIPPED', 'Shipped');
define('ORDER_STATUS_DELIVERED', 'Delivered');
define('ORDER_STATUS_CANCELLED', 'Cancelled');

// User roles
define('ROLE_ADMIN', 'admin');
define('ROLE_STAFF', 'staff');
define('ROLE_TECHNICIAN', 'technician');
define('ROLE_USER', 'user');

// Error reporting based on environment
if (APP_ENV === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', ROOT_PATH . '/logs/php_errors.log');
}

// Timezone
date_default_timezone_set('Asia/Kolkata');

// Security headers
header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('X-XSS-Protection: 1; mode=block');

if (APP_ENV === 'production') {
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
}


