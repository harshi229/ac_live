<?php
require_once dirname(dirname(__DIR__)) . '/includes/config/init.php';
require_once INCLUDES_PATH . '/functions/security_helpers.php';

// Set content type to JSON
header('Content-Type: application/json');

// Ensure the admin is logged in
if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authorized']);
    exit();
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

// Validate CSRF token
if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
    echo json_encode(['success' => false, 'message' => 'Invalid security token']);
    exit();
}

try {
    // Get product ID and image filename
    $product_id = intval($_POST['product_id'] ?? 0);
    $image_filename = trim($_POST['image_filename'] ?? '');
    
    if ($product_id <= 0 || empty($image_filename)) {
        echo json_encode(['success' => false, 'message' => 'Invalid product ID or image filename']);
        exit();
    }
    
    // Check if the image exists in the database
    $check_stmt = $pdo->prepare("
        SELECT id, is_primary 
        FROM product_images 
        WHERE product_id = ? AND image_filename = ?
    ");
    $check_stmt->execute([$product_id, $image_filename]);
    $image_data = $check_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$image_data) {
        echo json_encode(['success' => false, 'message' => 'Image not found']);
        exit();
    }
    
    // If already primary, no need to change
    if ($image_data['is_primary']) {
        echo json_encode(['success' => false, 'message' => 'This image is already the primary image']);
        exit();
    }
    
    // Start transaction
    $pdo->beginTransaction();
    
    // Remove primary status from all images for this product
    $remove_primary_stmt = $pdo->prepare("UPDATE product_images SET is_primary = 0 WHERE product_id = ?");
    $remove_primary_stmt->execute([$product_id]);
    
    // Set the selected image as primary
    $set_primary_stmt = $pdo->prepare("UPDATE product_images SET is_primary = 1 WHERE product_id = ? AND image_filename = ?");
    $set_primary_result = $set_primary_stmt->execute([$product_id, $image_filename]);
    
    if (!$set_primary_result) {
        throw new Exception('Failed to set image as primary');
    }
    
    // Update the main product_image field for backward compatibility
    $update_product_stmt = $pdo->prepare("UPDATE products SET product_image = ? WHERE id = ?");
    $update_product_stmt->execute([$image_filename, $product_id]);
    
    // Log the action
    logSecurityEvent('product_primary_image_changed', [
        'admin_id' => $_SESSION['admin_id'],
        'product_id' => $product_id,
        'image_filename' => $image_filename
    ]);
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true, 
        'message' => 'Primary image updated successfully'
    ]);
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollback();
    }
    
    error_log("Set primary image error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred while setting primary image']);
}
?>
