<?php
require_once dirname(dirname(__DIR__)) . '/includes/config/init.php';
require_once INCLUDES_PATH . '/functions/security_helpers.php';

// Ensure the admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: ' . admin_url('login'));
    exit();
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . admin_url('products'));
    exit();
}

// Validate CSRF token
if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
    $_SESSION['error_message'] = 'Invalid security token. Please try again.';
    header('Location: ' . admin_url('products'));
    exit();
}

// Get product ID
$product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;

if ($product_id <= 0) {
    $_SESSION['error_message'] = 'Invalid product ID.';
    header('Location: ' . admin_url('products'));
    exit();
}

// First, get current values from database to preserve unchanged checkboxes
$current_sql = "SELECT show_on_homepage, show_on_product_page FROM products WHERE id = ?";
$current_stmt = $pdo->prepare($current_sql);
$current_stmt->execute([$product_id]);
$current = $current_stmt->fetch(PDO::FETCH_ASSOC);

// Get checkbox values - use POST if present (can be 0 or 1), otherwise keep current value
// Check if the key exists in POST (even if value is 0) vs not being sent at all
// Note: When checkbox is unchecked, JavaScript sends hidden input with value "0"
$show_on_homepage = isset($_POST['show_on_homepage']) ? intval($_POST['show_on_homepage']) : (isset($current['show_on_homepage']) ? intval($current['show_on_homepage']) : 0);
$show_on_product_page = isset($_POST['show_on_product_page']) ? intval($_POST['show_on_product_page']) : (isset($current['show_on_product_page']) ? intval($current['show_on_product_page']) : 0);

// Debug: Log what we received
error_log("POST data - show_on_homepage: " . (isset($_POST['show_on_homepage']) ? $_POST['show_on_homepage'] : 'not set') . ", show_on_product_page: " . (isset($_POST['show_on_product_page']) ? $_POST['show_on_product_page'] : 'not set'));
error_log("Current DB values - show_on_homepage: " . ($current['show_on_homepage'] ?? 'NULL') . ", show_on_product_page: " . ($current['show_on_product_page'] ?? 'NULL'));
error_log("Final values - show_on_homepage: $show_on_homepage, show_on_product_page: $show_on_product_page");

try {
    // Update the product display options
    $sql = "UPDATE products SET 
            show_on_homepage = ?, 
            show_on_product_page = ?,
            updated_at = NOW()
            WHERE id = ?";
    
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([$show_on_homepage, $show_on_product_page, $product_id]);
    
    // Log the update for debugging
    error_log("Display options update - Product ID: $product_id, Homepage: $show_on_homepage, Product Page: $show_on_product_page");
    
    if ($result) {
        $_SESSION['success_message'] = 'Display options updated successfully!';
    } else {
        $_SESSION['error_message'] = 'Failed to update display options.';
    }
} catch (PDOException $e) {
    error_log("Display options update error: " . $e->getMessage());
    $_SESSION['error_message'] = 'An error occurred while updating display options.';
}

// Redirect back to products list
header('Location: ' . admin_url('products'));
exit();
?>

