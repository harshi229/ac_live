<?php
// Quick View Modal Endpoint
header('Content-Type: application/json');

require_once __DIR__ . '/../../includes/config/init.php';

// Check if product ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid product ID']);
    exit;
}

$product_id = intval($_GET['id']);

try {
    // Fetch product details with related data
    $stmt = $pdo->prepare("
        SELECT 
            p.*,
            b.name as brand_name,
            c.name as category_name,
            COALESCE(AVG(r.rating), 0) as avg_rating,
            COUNT(r.id) as review_count
        FROM products p
        LEFT JOIN brands b ON p.brand_id = b.id
        LEFT JOIN categories c ON p.category_id = c.id
        LEFT JOIN reviews r ON p.id = r.product_id
        WHERE p.id = ? AND p.status = 'active'
        GROUP BY p.id
    ");
    
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
        echo json_encode(['success' => false, 'message' => 'Product not found']);
        exit;
    }
    
    // Format product data for quick view
    $product_data = [
        'id' => $product['id'],
        'name' => $product['product_name'],
        'brand' => $product['brand_name'],
        'category' => $product['category_name'],
        'price' => floatval($product['price']),
        'image' => UPLOAD_URL . '/' . $product['product_image'],
        'description' => $product['description'],
        'features' => [
            'Capacity: ' . $product['capacity'] . ' Ton',
            'Energy Rating: ' . ($product['energy_rating'] ?? $product['star_rating'] . ' Star'),
            'Warranty: ' . $product['warranty_years'] . ' Years',
            'Inverter: ' . $product['inverter'],
            'Installation: ' . ($product['installation'] == 'Yes' ? 'Included' : 'Not Included'),
            'AMC Available: ' . ($product['amc_available'] ? 'Yes' : 'No')
        ],
        'avg_rating' => round($product['avg_rating'], 1),
        'review_count' => intval($product['review_count']),
        'stock_status' => $product['status'],
        'in_stock' => $product['stock'] > 0
    ];
    
    // Add additional features if available
    if (!empty($product['features'])) {
        $features = explode(',', $product['features']);
        foreach ($features as $feature) {
            $feature = trim($feature);
            if (!empty($feature)) {
                $product_data['features'][] = $feature;
            }
        }
    }
    
    echo json_encode([
        'success' => true,
        'product' => $product_data
    ]);
    
} catch (PDOException $e) {
    error_log("Quick view error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}
?>
