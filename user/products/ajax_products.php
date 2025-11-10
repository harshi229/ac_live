<?php
// AJAX endpoint for fetching products with filters
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../../includes/config/init.php';
require_once INCLUDES_PATH . '/classes/ProductQueryBuilder.php';

// Get filter parameters
$category_id = isset($_GET['category']) ? intval($_GET['category']) : 0;
$brand_id = isset($_GET['brand']) ? intval($_GET['brand']) : 0;
$subcategory_id = isset($_GET['subcategory']) ? intval($_GET['subcategory']) : 0;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';
$min_price = isset($_GET['min_price']) ? floatval($_GET['min_price']) : 0;
$max_price = isset($_GET['max_price']) ? floatval($_GET['max_price']) : 0;
$inverter_filter = isset($_GET['inverter']) ? $_GET['inverter'] : '';
$star_rating = isset($_GET['star_rating']) ? intval($_GET['star_rating']) : 0;
$capacity_filter = isset($_GET['capacity']) ? $_GET['capacity'] : '';
$warranty_filter = isset($_GET['warranty']) ? intval($_GET['warranty']) : 0;
$amc_filter = isset($_GET['amc']) ? $_GET['amc'] : '';
$feature_filter = isset($_GET['feature']) ? intval($_GET['feature']) : 0;

// Pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$items_per_page = 12;

try {
    // Create ProductQueryBuilder instance
    $queryBuilder = new ProductQueryBuilder($pdo);
    
    // Build query from filters (this resets the builder, so filters must be added after)
    $queryBuilder->buildFromFilters([
        'category_id' => $category_id,
        'brand_id' => $brand_id,
        'subcategory_id' => $subcategory_id,
        'search' => $search,
        'min_price' => $min_price,
        'max_price' => $max_price,
        'inverter_filter' => $inverter_filter,
        'star_rating' => $star_rating,
        'capacity_filter' => $capacity_filter,
        'warranty_filter' => $warranty_filter,
        'amc_filter' => $amc_filter,
        'feature_filter' => $feature_filter,
        'sort' => $sort,
        'page' => $page,
        'items_per_page' => $items_per_page
    ]);
    
    // Add filter to only show products with show_on_product_page = 1
    // Must be called AFTER buildFromFilters() because buildFromFilters() resets the builder
    $queryBuilder->addProductPageFilter();

    // Get total count and products
    $total_products = $queryBuilder->getTotalCount();
    $total_pages = ceil($total_products / $items_per_page);
    $products = $queryBuilder->getProducts();

    // Add encrypted URLs to each product
    require_once INCLUDES_PATH . '/functions/url_helpers.php';
    foreach ($products as &$product) {
        $product['encrypted_url'] = encrypted_product_url($product['id'], 'product');
    }
    unset($product); // Break reference

    // Return JSON response
    echo json_encode([
        'success' => true,
        'products' => $products,
        'total_products' => $total_products,
        'total_pages' => $total_pages,
        'current_page' => $page
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error loading products: ' . $e->getMessage()
    ]);
}
?>
