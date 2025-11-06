<?php
require_once dirname(__DIR__) . '/includes/config/init.php';

// Ensure only admins can access this API
if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

// Get search query
$query = isset($_GET['q']) ? trim($_GET['q']) : '';

if (strlen($query) < 2) {
    echo json_encode(['results' => []]);
    exit();
}

try {
    $results = [];
    
    // Search products
    $productStmt = $pdo->prepare("
        SELECT 
            p.id,
            p.product_name as title,
            'product' as type,
            CONCAT('admin/products/edit?id=', p.id) as url,
            CONCAT('Product: ', p.product_name) as description
        FROM products p 
        WHERE p.product_name LIKE ? OR p.model_name LIKE ? OR p.model_number LIKE ?
        LIMIT 5
    ");
    $searchTerm = "%$query%";
    $productStmt->execute([$searchTerm, $searchTerm, $searchTerm]);
    $products = $productStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Search orders
    $orderStmt = $pdo->prepare("
        SELECT 
            o.id,
            CONCAT('Order #', o.order_number) as title,
            'order' as type,
            CONCAT('admin/orders/details/', o.id) as url,
            CONCAT('Order: ', o.order_number, ' - ₹', FORMAT(o.total_price, 0)) as description
        FROM orders o 
        WHERE o.order_number LIKE ? OR o.id LIKE ?
        LIMIT 5
    ");
    $orderStmt->execute([$searchTerm, $searchTerm]);
    $orders = $orderStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Search users
    $userStmt = $pdo->prepare("
        SELECT 
            u.id,
            COALESCE(
                CASE 
                    WHEN u.first_name IS NOT NULL AND u.last_name IS NOT NULL 
                    THEN CONCAT(u.first_name, ' ', u.last_name)
                    WHEN u.first_name IS NOT NULL 
                    THEN u.first_name
                    WHEN u.last_name IS NOT NULL 
                    THEN u.last_name
                    ELSE u.username
                END, 
                u.username
            ) as title,
            'user' as type,
            CONCAT('admin/users/edit?id=', u.id) as url,
            CONCAT('User: ', u.username, ' (', u.email, ')') as description
        FROM users u 
        WHERE u.username LIKE ? OR u.email LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ?
        LIMIT 5
    ");
    $userStmt->execute([$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
    $users = $userStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Combine results
    $results = array_merge($products, $orders, $users);
    
    // Limit total results
    $results = array_slice($results, 0, 10);
    
    echo json_encode(['results' => $results]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Search failed: ' . $e->getMessage()]);
}
?>
