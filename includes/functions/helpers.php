<?php
/**
 * Helper Functions
 * Common utility functions used throughout the application
 */

// Prevent direct access
if (!defined('AC_APP')) {
    die('Direct access not permitted');
}

/**
 * Sanitize input data
 * 
 * @param mixed $data Input data to sanitize
 * @return mixed Sanitized data
 */
function sanitize_input($data) {
    if (is_array($data)) {
        return array_map('sanitize_input', $data);
    }
    
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    
    return $data;
}

/**
 * Validate email address
 * 
 * @param string $email Email address to validate
 * @return bool True if valid, false otherwise
 */
function validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Generate a random token
 * 
 * @param int $length Length of the token
 * @return string Random token
 */
function generate_token($length = 32) {
    return bin2hex(random_bytes($length / 2));
}

/**
 * Format price in Indian currency format
 * 
 * @param float $amount Amount to format
 * @param bool $show_symbol Whether to show rupee symbol
 * @return string Formatted price
 */
function format_price($amount, $show_symbol = true) {
    $formatted = number_format($amount, 0);
    
    return $show_symbol ? '₹' . $formatted : $formatted;
}

/**
 * Format date in readable format
 * 
 * @param string $date Date string
 * @param string $format Output format
 * @return string Formatted date
 */
function format_date($date, $format = 'M j, Y') {
    return date($format, strtotime($date));
}

/**
 * Format datetime in readable format
 * 
 * @param string $datetime Datetime string
 * @param string $format Output format
 * @return string Formatted datetime
 */
function format_datetime($datetime, $format = 'M j, Y - g:i A') {
    return date($format, strtotime($datetime));
}

/**
 * Get time ago format
 * 
 * @param string $datetime Datetime string
 * @return string Time ago format
 */
function time_ago($datetime) {
    $timestamp = strtotime($datetime);
    $difference = time() - $timestamp;
    
    $periods = [
        'year' => 31536000,
        'month' => 2592000,
        'week' => 604800,
        'day' => 86400,
        'hour' => 3600,
        'minute' => 60,
        'second' => 1
    ];
    
    foreach ($periods as $period => $seconds) {
        $count = floor($difference / $seconds);
        
        if ($count > 0) {
            return $count . ' ' . $period . ($count > 1 ? 's' : '') . ' ago';
        }
    }
    
    return 'just now';
}

/**
 * Redirect to a URL
 * 
 * @param string $url URL to redirect to
 * @param int $status_code HTTP status code
 */
function redirect($url, $status_code = 302) {
    header('Location: ' . $url, true, $status_code);
    exit();
}

/**
 * Check if user is logged in
 * 
 * @return bool True if logged in, false otherwise
 */
function is_logged_in() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Check if admin is logged in
 * 
 * @return bool True if logged in, false otherwise
 */
function is_admin_logged_in() {
    return isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id']);
}

/**
 * Get current user ID
 * 
 * @return int|null User ID or null if not logged in
 */
function get_user_id() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Get current admin ID
 * 
 * @return int|null Admin ID or null if not logged in
 */
function get_admin_id() {
    return $_SESSION['admin_id'] ?? null;
}

/**
 * Generate pagination HTML
 * 
 * @param int $current_page Current page number
 * @param int $total_pages Total number of pages
 * @param string $base_url Base URL for pagination links
 * @return string Pagination HTML
 */
function generate_pagination($current_page, $total_pages, $base_url) {
    if ($total_pages <= 1) {
        return '';
    }
    
    $html = '<nav aria-label="Page navigation"><ul class="pagination justify-content-center">';
    
    // Previous button
    if ($current_page > 1) {
        $html .= '<li class="page-item"><a class="page-link" href="' . $base_url . '&page=' . ($current_page - 1) . '">Previous</a></li>';
    } else {
        $html .= '<li class="page-item disabled"><span class="page-link">Previous</span></li>';
    }
    
    // Page numbers
    $start = max(1, $current_page - 2);
    $end = min($total_pages, $current_page + 2);
    
    if ($start > 1) {
        $html .= '<li class="page-item"><a class="page-link" href="' . $base_url . '&page=1">1</a></li>';
        if ($start > 2) {
            $html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
        }
    }
    
    for ($i = $start; $i <= $end; $i++) {
        if ($i == $current_page) {
            $html .= '<li class="page-item active"><span class="page-link">' . $i . '</span></li>';
        } else {
            $html .= '<li class="page-item"><a class="page-link" href="' . $base_url . '&page=' . $i . '">' . $i . '</a></li>';
        }
    }
    
    if ($end < $total_pages) {
        if ($end < $total_pages - 1) {
            $html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
        }
        $html .= '<li class="page-item"><a class="page-link" href="' . $base_url . '&page=' . $total_pages . '">' . $total_pages . '</a></li>';
    }
    
    // Next button
    if ($current_page < $total_pages) {
        $html .= '<li class="page-item"><a class="page-link" href="' . $base_url . '&page=' . ($current_page + 1) . '">Next</a></li>';
    } else {
        $html .= '<li class="page-item disabled"><span class="page-link">Next</span></li>';
    }
    
    $html .= '</ul></nav>';
    
    return $html;
}

/**
 * Upload file to server
 * 
 * @param array $file File from $_FILES
 * @param string $destination Destination directory
 * @param array $allowed_types Allowed MIME types
 * @return array Result with 'success' and 'filename' or 'error'
 */
function upload_file($file, $destination = UPLOAD_PATH, $allowed_types = ALLOWED_IMAGE_TYPES) {
    // Check if file was uploaded
    if (!isset($file['error']) || is_array($file['error'])) {
        return ['success' => false, 'error' => 'Invalid file upload'];
    }
    
    // Check for upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'File upload error: ' . $file['error']];
    }
    
    // Check file size
    if ($file['size'] > MAX_FILE_SIZE) {
        return ['success' => false, 'error' => 'File too large. Maximum size: ' . (MAX_FILE_SIZE / 1024 / 1024) . 'MB'];
    }
    
    // Check file type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mime_type, $allowed_types)) {
        return ['success' => false, 'error' => 'Invalid file type'];
    }
    
    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = time() . '_' . bin2hex(random_bytes(8)) . '.' . $extension;
    $filepath = $destination . '/' . $filename;
    
    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        return ['success' => false, 'error' => 'Failed to move uploaded file'];
    }
    
    return ['success' => true, 'filename' => $filename];
}

/**
 * Delete file from server
 * 
 * @param string $filename Filename to delete
 * @param string $directory Directory where file is located
 * @return bool True if deleted, false otherwise
 */
function delete_file($filename, $directory = UPLOAD_PATH) {
    $filepath = $directory . '/' . $filename;
    
    if (file_exists($filepath)) {
        return unlink($filepath);
    }
    
    return false;
}

/**
 * Generate slug from string
 * 
 * @param string $string String to convert to slug
 * @return string Slug
 */
function generate_slug($string) {
    $string = strtolower($string);
    $string = preg_replace('/[^a-z0-9\s-]/', '', $string);
    $string = preg_replace('/[\s-]+/', '-', $string);
    $string = trim($string, '-');
    
    return $string;
}

/**
 * Truncate text to specified length
 * 
 * @param string $text Text to truncate
 * @param int $length Maximum length
 * @param string $suffix Suffix to add if truncated
 * @return string Truncated text
 */
function truncate_text($text, $length = 100, $suffix = '...') {
    if (strlen($text) <= $length) {
        return $text;
    }
    
    return substr($text, 0, $length) . $suffix;
}

/**
 * Get order status badge class
 * 
 * @param string $status Order status
 * @return string Bootstrap badge class
 */
function get_status_badge_class($status) {
    $classes = [
        'Pending' => 'bg-warning text-dark',
        'Confirmed' => 'bg-info',
        'Shipped' => 'bg-primary',
        'Delivered' => 'bg-success',
        'Cancelled' => 'bg-danger'
    ];
    
    return $classes[$status] ?? 'bg-secondary';
}

/**
 * Send JSON response
 * 
 * @param array $data Data to send
 * @param int $status_code HTTP status code
 */
function send_json_response($data, $status_code = 200) {
    http_response_code($status_code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit();
}

/**
 * Log error to file
 * 
 * @param string $message Error message
 * @param string $file Log file
 */
function log_error($message, $file = 'errors.log') {
    $log_dir = ROOT_PATH . '/logs';
    
    if (!file_exists($log_dir)) {
        mkdir($log_dir, 0755, true);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $log_message = "[{$timestamp}] {$message}\n";
    
    file_put_contents($log_dir . '/' . $file, $log_message, FILE_APPEND);
}


