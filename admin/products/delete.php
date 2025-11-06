<?php
require_once dirname(dirname(__DIR__)) . '/includes/config/init.php';
require_once INCLUDES_PATH . '/functions/security_helpers.php';

// Ensure the admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: ' . admin_url('login'));
    exit();
}

// Get product ID from URL
$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($product_id <= 0) {
    $_SESSION['error_message'] = 'Invalid product ID.';
    header('Location: ' . admin_url('products'));
    exit();
}

try {
    // Check if product exists
    $check_sql = "SELECT id, product_name, product_image FROM products WHERE id = ?";
    $check_stmt = $pdo->prepare($check_sql);
    $check_stmt->execute([$product_id]);
    $product = $check_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
        $_SESSION['error_message'] = 'Product not found.';
        header('Location: ' . admin_url('products'));
        exit();
    }
    
    // Check if product is referenced in any orders
    $order_check_sql = "SELECT COUNT(*) FROM order_items WHERE product_id = ?";
    $order_check_stmt = $pdo->prepare($order_check_sql);
    $order_check_stmt->execute([$product_id]);
    $order_count = $order_check_stmt->fetchColumn();
    
    if ($order_count > 0) {
        $_SESSION['error_message'] = 'Cannot delete product. It is referenced in ' . $order_count . ' order(s). Consider deactivating it instead.';
        header('Location: ' . admin_url('products'));
        exit();
    }
    
    // Delete the product
    $delete_sql = "DELETE FROM products WHERE id = ?";
    $delete_stmt = $pdo->prepare($delete_sql);
    $result = $delete_stmt->execute([$product_id]);
    
    if ($result) {
        // Delete associated image file if it exists
        if (!empty($product['product_image'])) {
            $image_path = UPLOAD_PATH . '/' . $product['product_image'];
            if (file_exists($image_path)) {
                unlink($image_path);
            }
        }
        
        // Log the deletion action
        logSecurityEvent('product_deleted', [
            'admin_id' => $_SESSION['admin_id'],
            'product_id' => $product_id,
            'product_name' => $product['product_name']
        ]);
        
        $_SESSION['success_message'] = 'Product "' . htmlspecialchars($product['product_name']) . '" deleted successfully!';
    } else {
        throw new Exception('Failed to delete product from database.');
    }
    
} catch (Exception $e) {
    error_log("Product deletion error: " . $e->getMessage());
    $_SESSION['error_message'] = 'An error occurred while deleting the product. Please try again.';
}

// Redirect back to products list
header('Location: ' . admin_url('products'));
exit();
?>
