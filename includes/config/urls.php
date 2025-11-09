<?php
/**
 * URL Configuration
 * Defines all clean URL patterns for the application
 */

// Prevent direct access
if (!defined('AC_APP')) {
    die('Direct access not permitted');
}

/**
 * URL Patterns Configuration
 * This array defines all the clean URL patterns used in the application
 */
$url_patterns = [
    // Admin URLs
    'admin' => [
        'dashboard' => 'admin/',
        'login' => 'admin/login',
        'logout' => 'admin/logout',
        'notifications' => 'admin/notifications',
        
        // Products
        'products' => 'admin/products',
        'products_add' => 'admin/products/add',
        'products_edit' => 'admin/products/edit/{id}',
        'products_delete' => 'admin/products/delete/{id}',
        
        // Categories
        'categories' => 'admin/categories',
        'categories_add' => 'admin/categories/add',
        'categories_edit' => 'admin/categories/edit/{id}',
        'categories_subcategories' => 'admin/categories/subcategories',
        
        // Brands
        'brands' => 'admin/brands',
        
        // Orders
        'orders' => 'admin/orders',
        'orders_details' => 'admin/orders/details/{id}',
        
        // Users
        'users' => 'admin/users',
        'users_edit' => 'admin/users/edit/{id}',
        
        // Services
        'services' => 'admin/services',
        'services_amc' => 'admin/services/amc',
        'services_features' => 'admin/services/features',
        'services_installations' => 'admin/services/installations',
        
        // Security
        'security_monitor' => 'admin/security_monitor',
        'setup_security' => 'admin/setup_security',
        
        // Reports
        'reports' => 'admin/reports',
        'reports_sales' => 'admin/reports/sales',
        'reports_customers' => 'admin/reports/customers',
        
        // Settings
        'settings' => 'admin/settings',
        'settings_profile' => 'admin/settings/profile',
    ],
    
    // User URLs
    'user' => [
        'dashboard' => 'user/',
        'login' => 'user/login',
        'register' => 'user/register',
        'logout' => 'user/logout',
        'profile' => 'user/profile',
        'cart' => 'user/cart',
        'checkout' => 'user/checkout',
        'orders' => 'user/orders',
        'products' => 'user/products',
        'product_details' => 'user/products/details/{id}',
    ],
    
    // API URLs
    'api' => [
        'index' => 'api/',
        'products' => 'api/products',
        'categories' => 'api/categories',
        'orders' => 'api/orders',
    ],
    
    // Public URLs
    'public' => [
        'home' => '/',
        'about' => 'about',
        'contact' => 'contact',
        'services' => 'services',
    ]
];

/**
 * Get URL pattern by key
 * 
 * @param string $section Section (admin, user, api, public)
 * @param string $key URL key
 * @param array $params Parameters to replace in URL
 * @return string URL pattern
 */
function get_url_pattern($section, $key, $params = []) {
    global $url_patterns;
    
    if (!isset($url_patterns[$section][$key])) {
        return '';
    }
    
    $pattern = $url_patterns[$section][$key];
    
    // Replace parameters in URL pattern
    foreach ($params as $param => $value) {
        $pattern = str_replace('{' . $param . '}', $value, $pattern);
    }
    
    return BASE_URL . '/' . $pattern;
}

/**
 * Generate admin URL
 * 
 * @param string $key URL key
 * @param array $params Parameters
 * @return string Admin URL
 */
function admin_url_pattern($key, $params = []) {
    return get_url_pattern('admin', $key, $params);
}

/**
 * Generate user URL
 * 
 * @param string $key URL key
 * @param array $params Parameters
 * @return string User URL
 */
function user_url_pattern($key, $params = []) {
    return get_url_pattern('user', $key, $params);
}

/**
 * Generate API URL
 * 
 * @param string $key URL key
 * @param array $params Parameters
 * @return string API URL
 */
function api_url_pattern($key, $params = []) {
    return get_url_pattern('api', $key, $params);
}

/**
 * Generate public URL
 * 
 * @param string $key URL key
 * @param array $params Parameters
 * @return string Public URL
 */
function public_url_pattern($key, $params = []) {
    return get_url_pattern('public', $key, $params);
}

/**
 * Check if URL matches pattern
 * 
 * @param string $url URL to check
 * @param string $pattern Pattern to match
 * @return bool True if matches
 */
function url_matches_pattern($url, $pattern) {
    // Convert pattern to regex
    $regex = str_replace(['{', '}'], ['(?P<', '>[^/]+)'], $pattern);
    $regex = '#^' . $regex . '$#';
    
    return preg_match($regex, $url);
}

/**
 * Extract parameters from URL
 * 
 * @param string $url URL to parse
 * @param string $pattern Pattern to match
 * @return array Extracted parameters
 */
function extract_url_params($url, $pattern) {
    // Convert pattern to regex
    $regex = str_replace(['{', '}'], ['(?P<', '>[^/]+)'], $pattern);
    $regex = '#^' . $regex . '$#';
    
    if (preg_match($regex, $url, $matches)) {
        // Remove numeric keys and return only named parameters
        return array_filter($matches, function($key) {
            return is_string($key);
        }, ARRAY_FILTER_USE_KEY);
    }
    
    return [];
}

/**
 * Generate breadcrumb from URL
 * 
 * @param string $url Current URL
 * @return array Breadcrumb items
 */
function generate_breadcrumb_from_url($url) {
    $breadcrumbs = [];
    $segments = explode('/', trim($url, '/'));
    
    $current_path = '';
    foreach ($segments as $segment) {
        $current_path .= '/' . $segment;
        
        // Skip empty segments
        if (empty($segment)) {
            continue;
        }
        
        // Generate title from segment
        $title = ucwords(str_replace(['-', '_'], ' ', $segment));
        
        $breadcrumbs[] = [
            'title' => $title,
            'url' => $current_path
        ];
    }
    
    return $breadcrumbs;
}

/**
 * Get all URL patterns
 * 
 * @return array All URL patterns
 */
function get_all_url_patterns() {
    global $url_patterns;
    return $url_patterns;
}

/**
 * Validate URL pattern
 * 
 * @param string $pattern URL pattern
 * @return bool True if valid
 */
function validate_url_pattern($pattern) {
    // Basic validation - check for valid characters
    return preg_match('/^[a-zA-Z0-9\/\{\}\-\.]+$/', $pattern);
}
