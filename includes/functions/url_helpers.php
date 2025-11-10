<?php
/**
 * URL Helper Functions
 * Provides clean URL generation and management
 */

// Prevent direct access
if (!defined('AC_APP')) {
    die('Direct access not permitted');
}

/**
 * Generate clean admin URL
 * 
 * @param string $path The path after admin/
 * @param array $params Optional query parameters
 * @return string Clean URL
 */
function admin_url($path = '', $params = []) {
    $url = ADMIN_URL . '/' . ltrim($path, '/');
    
    if (!empty($params)) {
        $url .= '?' . http_build_query($params);
    }
    
    return $url;
}

/**
 * Generate clean user URL
 * 
 * @param string $path The path after user/
 * @param array $params Optional query parameters
 * @return string Clean URL
 */
function user_url($path = '', $params = []) {
    $url = USER_URL . '/' . ltrim($path, '/');
    
    if (!empty($params)) {
        $url .= '?' . http_build_query($params);
    }
    
    return $url;
}

/**
 * Generate clean API URL
 * 
 * @param string $path The path after api/
 * @param array $params Optional query parameters
 * @return string Clean URL
 */
function api_url($path = '', $params = []) {
    $url = API_URL . '/' . ltrim($path, '/');
    
    if (!empty($params)) {
        $url .= '?' . http_build_query($params);
    }
    
    return $url;
}

/**
 * Generate clean public URL
 * 
 * @param string $path The path after public/
 * @return string Clean URL
 */
function public_url($path = '') {
    return PUBLIC_URL . '/' . ltrim($path, '/');
}

/**
 * Generate clean asset URL
 * 
 * @param string $type Asset type (css, js, img, uploads)
 * @param string $file File name
 * @return string Clean URL
 */
function asset_url($type, $file) {
    switch ($type) {
        case 'css':
            return CSS_URL . '/' . ltrim($file, '/');
        case 'js':
            return JS_URL . '/' . ltrim($file, '/');
        case 'img':
            return IMG_URL . '/' . ltrim($file, '/');
        case 'uploads':
            return UPLOAD_URL . '/' . ltrim($file, '/');
        default:
            return PUBLIC_URL . '/' . ltrim($file, '/');
    }
}

/**
 * Generate clean product URL (hides ID from query string)
 * 
 * @param int $product_id Product ID
 * @param bool $use_clean_url Whether to use clean URL format (default: true)
 * @param bool $encrypt Whether to encrypt URL parameters (default: true)
 * @param string|null $from Source parameter (optional, only used when encrypting)
 * @return string Clean product URL
 */
function product_url($product_id, $use_clean_url = true, $encrypt = true, $from = null) {
    // Validate product ID
    $product_id = (int)$product_id;
    if ($product_id <= 0) {
        // Return products index if invalid ID
        return defined('USER_URL') ? USER_URL . '/products/' : (defined('BASE_URL') ? BASE_URL : '') . '/user/products/';
    }
    
    // Use encrypted URL if encryption is enabled
    if ($encrypt) {
        return encrypted_product_url($product_id, $from);
    }
    
    if (!defined('USER_URL')) {
        // Fallback if USER_URL is not defined
        $base = defined('BASE_URL') ? BASE_URL : '';
        // Temporarily use query string if constants not loaded
        $url = $base . "/user/products/details.php?id={$product_id}";
        if ($from !== null) {
            $url .= "&from=" . urlencode($from);
        }
        return $url;
    }
    
    // Temporarily disable clean URLs if causing 500 errors
    // Set to false to use query strings instead
    $enable_clean_urls = true;
    
    if ($use_clean_url && $enable_clean_urls) {
        // Clean URL: /user/products/123 instead of /user/products/details.php?id=123
        return USER_URL . "/products/{$product_id}";
    }
    // Fallback to query string format with .php extension
    $url = USER_URL . "/products/details.php?id={$product_id}";
    if ($from !== null) {
        $url .= "&from=" . urlencode($from);
    }
    return $url;
}

/**
 * Generate clean admin product URL
 * 
 * @param int $product_id Product ID
 * @param string $action Action (edit, delete, view)
 * @return string Clean admin product URL
 */
function admin_product_url($product_id, $action = 'view') {
    switch ($action) {
        case 'edit':
            return admin_url("products/edit/{$product_id}");
        case 'delete':
            return admin_url("products/delete/{$product_id}");
        case 'view':
        default:
            return admin_url("products");
    }
}

/**
 * Generate clean category URL
 * 
 * @param int $category_id Category ID
 * @return string Clean category URL
 */
function category_url($category_id) {
    return user_url("products?category={$category_id}");
}

/**
 * Generate clean order URL (hides ID from query string)
 * 
 * @param int $order_id Order ID
 * @param bool $use_clean_url Whether to use clean URL format (default: true)
 * @return string Clean order URL
 */
function order_url($order_id, $use_clean_url = true) {
    if (!defined('USER_URL')) {
        // Fallback if USER_URL is not defined
        $base = defined('BASE_URL') ? BASE_URL : '';
        return $base . "/user/orders/{$order_id}";
    }
    
    if ($use_clean_url) {
        // Clean URL: /user/orders/123 instead of /user/orders/order_details.php?id=123
        return USER_URL . "/orders/{$order_id}";
    }
    // Fallback to query string format
    return user_url("orders/order_details", ['id' => $order_id]);
}

/**
 * Generate clean admin order URL
 * 
 * @param int $order_id Order ID
 * @return string Clean admin order URL
 */
function admin_order_url($order_id) {
    return admin_url("orders/details/{$order_id}");
}

/**
 * Get current clean URL
 * 
 * @return string Current clean URL
 */
function current_url() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $uri = $_SERVER['REQUEST_URI'];
    
    return $protocol . '://' . $host . $uri;
}

/**
 * Redirect to clean URL
 * 
 * @param string $url URL to redirect to
 * @param int $status_code HTTP status code
 */
function redirect_to($url, $status_code = 302) {
    header("Location: {$url}", true, $status_code);
    exit();
}

/**
 * Redirect to admin URL
 * 
 * @param string $path Path after admin/
 * @param array $params Optional query parameters
 * @param int $status_code HTTP status code
 */
function redirect_admin($path = '', $params = [], $status_code = 302) {
    redirect_to(admin_url($path, $params), $status_code);
}

/**
 * Redirect to user URL
 * 
 * @param string $path Path after user/
 * @param array $params Optional query parameters
 * @param int $status_code HTTP status code
 */
function redirect_user($path = '', $params = [], $status_code = 302) {
    redirect_to(user_url($path, $params), $status_code);
}

/**
 * Check if current URL is admin URL
 * 
 * @return bool True if admin URL
 */
function is_admin_url() {
    return strpos($_SERVER['REQUEST_URI'], '/admin/') !== false;
}

/**
 * Check if current URL is user URL
 * 
 * @return bool True if user URL
 */
function is_user_url() {
    return strpos($_SERVER['REQUEST_URI'], '/user/') !== false;
}

/**
 * Check if current URL is API URL
 * 
 * @return bool True if API URL
 */
function is_api_url() {
    return strpos($_SERVER['REQUEST_URI'], '/api/') !== false;
}

/**
 * Generate breadcrumb navigation
 * 
 * @param array $items Array of breadcrumb items [['title' => 'Home', 'url' => '/']]
 * @return string HTML breadcrumb
 */
function breadcrumb($items) {
    if (empty($items)) {
        return '';
    }
    
    $html = '<nav aria-label="breadcrumb"><ol class="breadcrumb">';
    
    foreach ($items as $index => $item) {
        $is_last = ($index === count($items) - 1);
        
        if ($is_last) {
            $html .= '<li class="breadcrumb-item active" aria-current="page">' . htmlspecialchars($item['title']) . '</li>';
        } else {
            $html .= '<li class="breadcrumb-item"><a href="' . htmlspecialchars($item['url']) . '">' . htmlspecialchars($item['title']) . '</a></li>';
        }
    }
    
    $html .= '</ol></nav>';
    
    return $html;
}

/**
 * Generate pagination URL
 * 
 * @param int $page Page number
 * @param array $params Additional parameters
 * @return string Pagination URL
 */
function pagination_url($page, $params = []) {
    $params['page'] = $page;
    return current_url() . '?' . http_build_query($params);
}

/**
 * Generate secure URL with encrypted parameter (for sensitive data like user IDs)
 * 
 * @param string $base_path Base path (e.g., 'profile/view')
 * @param int $id ID to encrypt
 * @param string $param_name Parameter name (default: 'id')
 * @return string URL with encrypted parameter
 */
function secure_url($base_path, $id, $param_name = 'id') {
    require_once __DIR__ . '/security_helpers.php';
    $encrypted = encryptUrlParam((string)$id);
    return user_url($base_path, [$param_name => $encrypted]);
}

/**
 * Generate signed URL parameter (for time-limited, tamper-proof URLs)
 * 
 * @param string $base_path Base path
 * @param int $id ID to sign
 * @param string $param_name Parameter name (default: 'token')
 * @return string URL with signed parameter
 */
function signed_url($base_path, $id, $param_name = 'token') {
    require_once __DIR__ . '/security_helpers.php';
    $signed = signUrlParam($id);
    return user_url($base_path, [$param_name => $signed]);
}

/**
 * Decode encrypted URL parameter
 * 
 * @param string $encrypted Encrypted parameter value
 * @return int|false Decoded ID or false on failure
 */
function decode_secure_param($encrypted) {
    require_once __DIR__ . '/security_helpers.php';
    $decrypted = decryptUrlParam($encrypted);
    return $decrypted !== false ? (int)$decrypted : false;
}

/**
 * Verify and decode signed URL parameter
 * 
 * @param string $signed Signed parameter value
 * @param int $max_age Maximum age in seconds
 * @return int|false Decoded ID or false on failure
 */
function verify_signed_param($signed, $max_age = 3600) {
    require_once __DIR__ . '/security_helpers.php';
    return verifySignedUrlParam($signed, $max_age);
}

/**
 * Generate clean search URL
 * 
 * @param string $query Search query
 * @param array $filters Additional filters
 * @return string Search URL
 */
function search_url($query, $filters = []) {
    $params = array_merge(['search' => $query], $filters);
    return user_url('products', $params);
}

/**
 * Encrypt multiple URL parameters into a single encrypted token
 * 
 * @param array $params Array of parameters to encrypt (e.g., ['id' => 6, 'from' => 'product'])
 * @return string Encrypted token
 */
function encrypt_url_params($params) {
    require_once __DIR__ . '/security_helpers.php';
    $data = json_encode($params);
    return encryptUrlParam($data);
}

/**
 * Decrypt URL parameters from encrypted token
 * 
 * @param string $encrypted Encrypted token
 * @return array|false Decrypted parameters or false on failure
 */
function decrypt_url_params($encrypted) {
    require_once __DIR__ . '/security_helpers.php';
    $decrypted = decryptUrlParam($encrypted);
    if ($decrypted === false) {
        return false;
    }
    $params = json_decode($decrypted, true);
    return is_array($params) ? $params : false;
}

/**
 * Generate encrypted product detail URL
 * 
 * @param int $product_id Product ID
 * @param string $from Source parameter (optional)
 * @return string URL with encrypted parameters
 */
function encrypted_product_url($product_id, $from = null) {
    $params = ['id' => (int)$product_id];
    if ($from !== null) {
        $params['from'] = $from;
    }
    $encrypted = encrypt_url_params($params);
    return user_url('products/details.php', ['token' => $encrypted]);
}