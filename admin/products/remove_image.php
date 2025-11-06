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
    
    // Check if this is the only image for the product
    $count_stmt = $pdo->prepare("SELECT COUNT(*) as count FROM product_images WHERE product_id = ?");
    $count_stmt->execute([$product_id]);
    $image_count = $count_stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    if ($image_count <= 1) {
        echo json_encode(['success' => false, 'message' => 'Cannot remove the last image. Products must have at least one image.']);
        exit();
    }
    
    // Start transaction
    $pdo->beginTransaction();
    
    // Remove the image from database
    $delete_stmt = $pdo->prepare("DELETE FROM product_images WHERE product_id = ? AND image_filename = ?");
    $delete_result = $delete_stmt->execute([$product_id, $image_filename]);
    
    if (!$delete_result) {
        throw new Exception('Failed to remove image from database');
    }
    
    // If we removed the primary image, make the first remaining image primary
    if ($image_data['is_primary']) {
        $update_primary_stmt = $pdo->prepare("
            UPDATE product_images 
            SET is_primary = 1 
            WHERE product_id = ? 
            ORDER BY sort_order ASC, id ASC 
            LIMIT 1
        ");
        $update_primary_stmt->execute([$product_id]);
        
        // Also update the main product_image field for backward compatibility
        $get_new_primary_stmt = $pdo->prepare("
            SELECT image_filename 
            FROM product_images 
            WHERE product_id = ? 
            ORDER BY sort_order ASC, id ASC 
            LIMIT 1
        ");
        $get_new_primary_stmt->execute([$product_id]);
        $new_primary = $get_new_primary_stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($new_primary) {
            $update_product_stmt = $pdo->prepare("UPDATE products SET product_image = ? WHERE id = ?");
            $update_product_stmt->execute([$new_primary['image_filename'], $product_id]);
        }
    }
    
    // Delete the physical file
    $file_path = UPLOAD_PATH . '/' . $image_filename;
    if (file_exists($file_path)) {
        unlink($file_path);
    }
    
    // Log the action
    logSecurityEvent('product_image_removed', [
        'admin_id' => $_SESSION['admin_id'],
        'product_id' => $product_id,
        'image_filename' => $image_filename
    ]);
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true, 
        'message' => 'Image removed successfully',
        'was_primary' => $image_data['is_primary']
    ]);
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollback();
    }
    
    error_log("Image removal error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred while removing the image']);
}
?>
