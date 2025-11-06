<?php
// Disable error reporting for clean JSON output
error_reporting(0);
ini_set('display_errors', 0);

require_once __DIR__ . '/../../includes/config/init.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'You must be logged in.']);
    exit();
}

$user_id = $_SESSION['user_id'];

// Check if this is a dismiss action
$input = json_decode(file_get_contents('php://input'), true);
$is_dismiss = isset($input['action']) && $input['action'] === 'dismiss';

try {
    if ($is_dismiss) {
        // Just clear saved cart items without restoring
        $clear_saved = $pdo->prepare("DELETE FROM saved_carts WHERE user_id = ?");
        $clear_saved->execute([$user_id]);
        
        echo json_encode(['success' => true, 'message' => 'Previous cart items dismissed.']);
        exit();
    }
    
    $pdo->beginTransaction();
    
    // Get saved cart items
    $saved_items = $pdo->prepare("SELECT product_id, quantity FROM saved_carts WHERE user_id = ? ORDER BY saved_at DESC");
    $saved_items->execute([$user_id]);
    $items = $saved_items->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($items)) {
        echo json_encode(['success' => false, 'message' => 'No saved cart items found.']);
        exit();
    }
    
    // Clear current cart
    $clear_cart = $pdo->prepare("DELETE FROM cart WHERE user_id = ?");
    $clear_cart->execute([$user_id]);
    
    // Restore saved items
    $restore_stmt = $pdo->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)");
    foreach ($items as $item) {
        $restore_stmt->execute([$user_id, $item['product_id'], $item['quantity']]);
    }
    
    // Clear saved cart
    $clear_saved = $pdo->prepare("DELETE FROM saved_carts WHERE user_id = ?");
    $clear_saved->execute([$user_id]);
    
    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'Previous cart items restored successfully!']);
    
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Error restoring cart: ' . $e->getMessage()]);
}
?>
