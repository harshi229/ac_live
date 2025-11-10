<?php
// Set page metadata
$pageTitle = 'Product Details';
$pageDescription = 'View detailed information about our premium air conditioning products';
$pageKeywords = 'product details, AC specifications, air conditioner features, product information';

require_once __DIR__ . '/../../includes/config/init.php';
// Database connection now in init.php

// Get the product ID from the URL (support both encrypted and plain parameters)
$product_id = 0;
$from = null;

// Check for encrypted token first
if (isset($_GET['token'])) {
    require_once __DIR__ . '/../../includes/functions/url_helpers.php';
    $decrypted_params = decrypt_url_params($_GET['token']);
    if ($decrypted_params !== false) {
        $product_id = isset($decrypted_params['id']) ? intval($decrypted_params['id']) : 0;
        $from = isset($decrypted_params['from']) ? $decrypted_params['from'] : null;
    }
}

// Fallback to plain parameters if no encrypted token or decryption failed
if (!$product_id && isset($_GET['id'])) {
    $product_id = intval($_GET['id']);
    $from = isset($_GET['from']) ? $_GET['from'] : null;
}

if (!$product_id) {
    echo "<script>window.location.href='index.php';</script>";
    exit();
}

try {
    // Fetch complete product details with relationships
    $sql = "SELECT p.*, 
                   b.name as brand_name,
                   c.name as category_name,
                   sc.name as subcategory_name,
                   GROUP_CONCAT(DISTINCT f.name ORDER BY f.name SEPARATOR '|') as features,
                   GROUP_CONCAT(DISTINCT f.description ORDER BY f.name SEPARATOR '|') as feature_descriptions
            FROM products p
            LEFT JOIN brands b ON p.brand_id = b.id
            LEFT JOIN categories c ON p.category_id = c.id
            LEFT JOIN sub_categories sc ON p.sub_category_id = sc.id
            LEFT JOIN product_features pf ON p.id = pf.product_id
            LEFT JOIN features f ON pf.feature_id = f.id
            WHERE p.id = ? AND p.status = 'active'
            GROUP BY p.id";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        echo "<script>window.location.href='index.php';</script>";
        exit();
    }

    // Include header after successful product fetch
    include INCLUDES_PATH . '/templates/header.php';
    
    // Debug: Log successful product fetch
    error_log("Product details page: Successfully loaded product ID $product_id - " . $product['product_name']);

    // Get product images
    $images_stmt = $pdo->prepare("
        SELECT image_filename, image_alt_text, sort_order, is_primary
        FROM product_images 
        WHERE product_id = ? 
        ORDER BY sort_order ASC, id ASC
    ");
    $images_stmt->execute([$product_id]);
    $product_images = $images_stmt->fetchAll(PDO::FETCH_ASSOC);

    // If no images in the new table, fall back to the old product_image field
    if (empty($product_images) && !empty($product['product_image'])) {
        $product_images = [[
            'image_filename' => $product['product_image'],
            'image_alt_text' => $product['product_name'] . ' - Main Image',
            'sort_order' => 1,
            'is_primary' => 1
        ]];
    }

    // Get product reviews
    $review_stmt = $pdo->prepare("
        SELECT r.*, u.username, u.created_at as user_since
        FROM reviews r 
        JOIN users u ON r.user_id = u.id 
        WHERE r.product_id = ? AND r.status = 'approved'
        ORDER BY r.created_at DESC
    ");
    $review_stmt->execute([$product_id]);
    $reviews = $review_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get related products (same category, different models)
    // Only show products with show_on_product_page = 1
    $related_stmt = $pdo->prepare("
        SELECT p.*, b.name as brand_name
        FROM products p
        LEFT JOIN brands b ON p.brand_id = b.id
        WHERE p.category_id = ? AND p.id != ? AND p.status = 'active'
        AND (p.show_on_product_page = 1 OR p.show_on_product_page IS NULL)
        ORDER BY RAND()
        LIMIT 4
    ");
    $related_stmt->execute([$product['category_id'], $product_id]);
    $related_products = $related_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculate average rating
    $avg_rating = 0;
    $total_reviews = count($reviews);
    if ($total_reviews > 0) {
        $total_rating = array_sum(array_column($reviews, 'rating'));
        $avg_rating = round($total_rating / $total_reviews, 1);
    }

} catch (PDOException $e) {
    $error_message = "Error fetching product details: " . $e->getMessage();
    // Include header even if there's an error
    include INCLUDES_PATH . '/templates/header.php';
}
?>

<style>
/* E-commerce Product Details Page */


/* Main Product Container */
.product-details-container {
    background: #fff;
    padding: 100px 0 30px; /* Extra top padding to account for fixed navbar (70px + buffer) */
    margin-top: 0;
}

.product-main-content {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 40px;
    margin-bottom: 50px;
}

/* Product Image Gallery */
.product-image-gallery {
    position: sticky;
    top: 20px;
}

.main-image-container {
    border: 1px solid #e9ecef;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 15px;
    background: #fff;
    text-align: center;
}

.main-image {
    max-width: 100%;
    height: 400px;
    object-fit: contain;
    cursor: zoom-in;
    transition: transform 0.3s ease;
}

.main-image:hover {
    transform: scale(1.02);
}

.zoom-indicator {
    position: absolute;
    top: 10px;
    right: 10px;
    background: rgba(0, 0, 0, 0.7);
    color: white;
    padding: 8px 12px;
    border-radius: 20px;
    font-size: 12px;
    display: flex;
    align-items: center;
    gap: 5px;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.main-image-container:hover .zoom-indicator {
    opacity: 1;
}

.thumbnail-gallery {
    display: flex;
    gap: 10px;
    overflow-x: auto;
    padding: 10px 0;
}

.thumbnail {
    width: 80px;
    height: 80px;
    object-fit: cover;
    border: 2px solid #e9ecef;
    border-radius: 4px;
    cursor: pointer;
    transition: border-color 0.3s;
}

.thumbnail:hover,
.thumbnail.active {
    border-color: #007bff;
}

/* Product Information */
.product-info {
    padding: 0 20px;
}

.product-brand {
    color: #495057;
    font-size: 14px;
    font-weight: 500;
    margin-bottom: 8px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.product-title {
    font-size: 28px;
    font-weight: 600;
    color: #212529;
    margin-bottom: 10px;
    line-height: 1.3;
}

.product-model {
    color: #495057;
    font-size: 16px;
    margin-bottom: 15px;
}

.product-rating {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 20px;
    padding: 10px 0;
    border-top: 1px solid #e9ecef;
    border-bottom: 1px solid #e9ecef;
}

.rating-stars {
    display: flex;
    gap: 2px;
}

.star {
    color: #ffc107;
    font-size: 18px;
}

.rating-text {
    color: #495057;
    font-size: 14px;
}

.product-price-section {
    margin-bottom: 25px;
}

.current-price {
    font-size: 32px;
    font-weight: 700;
    color: #28a745;
    margin-bottom: 5px;
}

.price-label {
    font-size: 14px;
    color: #495057;
}

/* Discount Pricing Styles */
.pricing-container {
    margin-bottom: 10px;
}

.price-row {
    display: flex;
    align-items: center;
    gap: 15px;
    margin-bottom: 8px;
    flex-wrap: wrap;
}

.current-price {
    font-size: 36px;
    font-weight: 700;
    color: #28a745;
    margin: 0;
}

.original-price {
    font-size: 24px;
    font-weight: 500;
    color: #6c757d;
    text-decoration: line-through;
    margin: 0;
}

.discount-badge {
    background: linear-gradient(135deg, #dc3545, #c82333);
    color: white;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 14px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    box-shadow: 0 2px 4px rgba(220, 53, 69, 0.3);
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.05); }
    100% { transform: scale(1); }
}

.savings-text {
    font-size: 16px;
    font-weight: 600;
    color: #28a745;
    margin-bottom: 5px;
}

.savings-text::before {
    content: "💰 ";
    margin-right: 5px;
}

/* Stock and Availability */
.stock-info {
    margin-bottom: 25px;
}

.stock-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 15px;
    border-radius: 20px;
    font-size: 14px;
    font-weight: 500;
}

.stock-badge.in-stock {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.stock-badge.low-stock {
    background: #fff3cd;
    color: #856404;
    border: 1px solid #ffeaa7;
}

.stock-badge.out-of-stock {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

/* Quantity and Actions */
.quantity-section {
    margin-bottom: 25px;
}

.quantity-label {
    font-weight: 600;
    margin-bottom: 10px;
    color: #495057;
}

.quantity-controls {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 20px;
}

.quantity-input {
    width: 80px;
    padding: 8px 12px;
    border: 1px solid #ced4da;
    border-radius: 4px;
    text-align: center;
    font-size: 16px;
    color: #495057;
}

.quantity-btn {
    width: 35px;
    height: 35px;
    border: 1px solid #ced4da;
    background: #fff;
    border-radius: 4px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
}

.quantity-btn:hover {
    background: #f8f9fa;
}

.product-actions {
    display: flex;
    gap: 15px;
    margin-bottom: 25px;
}

.btn-add-cart {
    background: #007bff;
    color: white;
    border: none;
    padding: 12px 30px;
    border-radius: 4px;
    font-weight: 600;
    font-size: 16px;
    cursor: pointer;
    transition: background-color 0.3s;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.btn-add-cart:hover {
    background: #0056b3;
    color: white;
    text-decoration: none;
}

.btn-buy-now {
    background: #28a745;
    color: white;
    border: none;
    padding: 12px 30px;
    border-radius: 4px;
    font-weight: 600;
    font-size: 16px;
    cursor: pointer;
    transition: background-color 0.3s;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.btn-buy-now:hover {
    background: #1e7e34;
    color: white;
    text-decoration: none;
}

/* Product Features */
.product-features {
    margin-bottom: 30px;
}

.features-title {
    font-size: 18px;
    font-weight: 600;
    margin-bottom: 15px;
    color: #495057;
}

.features-list {
    list-style: none;
    padding: 0;
}

.feature-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 8px 0;
    border-bottom: 1px solid #f8f9fa;
    color: #495057;
}

.feature-item:last-child {
    border-bottom: none;
}

.feature-item i {
    color: #28a745;
    font-size: 14px;
}

/* Product Description */
.product-description {
    margin-bottom: 30px;
    padding: 20px;
    background: #f8f9fa;
    border-radius: 8px;
}

.description-title {
    font-size: 18px;
    font-weight: 600;
    margin-bottom: 15px;
    color: #495057;
}

.description-text {
    color: #495057;
    line-height: 1.6;
}

/* Specifications Section */
.specifications-section {
    background: #fff;
    padding: 40px 0;
    border-top: 1px solid #e9ecef;
    margin-top: 20px;
}

.specs-title {
    font-size: 24px;
    font-weight: 600;
    margin-bottom: 30px;
    color: #495057;
}

.specs-table {
    width: 100%;
    border-collapse: collapse;
    background: #fff;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.specs-table th,
.specs-table td {
    padding: 15px 20px;
    text-align: left;
    border-bottom: 1px solid #e9ecef;
    font-size: 16px;
    line-height: 1.5;
}

.specs-table th {
    background: #e9ecef;
    font-weight: 600;
    color: #212529;
    width: 40%;
}

.specs-table td {
    color: #495057;
    font-weight: 500;
}

.specs-table tr:last-child td {
    border-bottom: none;
}

/* Reviews Section */
.reviews-section {
    background: #fff;
    padding: 40px 0;
    border-top: 1px solid #e9ecef;
}

.reviews-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
    flex-wrap: wrap;
    gap: 15px;
}

.reviews-title {
    font-size: 24px;
    font-weight: 600;
    color: #495057;
    margin: 0;
}

.btn-review {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 10px 20px;
    border-radius: 6px;
    text-decoration: none;
    font-weight: 600;
    font-size: 14px;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.3s ease;
    box-shadow: 0 2px 4px rgba(102, 126, 234, 0.3);
}

.btn-review:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(102, 126, 234, 0.4);
    color: white;
    text-decoration: none;
}

.reviewed-badge {
    background: #28a745;
    color: white;
    padding: 10px 20px;
    border-radius: 6px;
    font-weight: 600;
    font-size: 14px;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.no-reviews {
    text-align: center;
    padding: 40px 20px;
    color: #666;
    font-style: italic;
}

.review-item {
    padding: 20px;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    margin-bottom: 20px;
    background: #fff;
}

.review-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.reviewer-info {
    display: flex;
    align-items: center;
    gap: 10px;
}

.reviewer-name {
    font-weight: 600;
    color: #495057;
}

.review-date {
    color: #495057;
    font-size: 14px;
}

.review-rating {
    display: flex;
    gap: 2px;
}

.review-content {
    margin-top: 10px;
}

.review-title {
    font-weight: 600;
    color: #333;
    margin-bottom: 8px;
    font-size: 16px;
}

.review-text {
    color: #495057;
    line-height: 1.6;
    font-size: 14px;
}

/* Back to Products Button */
.btn-back:hover {
    background: #e9ecef !important;
    color: #212529 !important;
    text-decoration: none !important;
    transform: translateX(-2px);
}

/* Related Products */
.related-products-section {
    background: #f8f9fa;
    padding: 40px 0;
}

.related-title {
    font-size: 24px;
    font-weight: 600;
    margin-bottom: 30px;
    color: #495057;
    text-align: center;
}

.related-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
}

.related-product-card {
    background: #fff;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    padding: 20px;
    text-align: center;
    transition: box-shadow 0.3s;
    text-decoration: none;
    color: inherit;
}

.related-product-card:hover {
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    text-decoration: none;
    color: inherit;
}

.related-product-image {
    width: 100%;
    height: 200px;
    object-fit: contain;
    margin-bottom: 15px;
    border-radius: 4px;
}

.related-product-title {
    font-size: 16px;
    font-weight: 600;
    margin-bottom: 10px;
    color: #495057;
}

.related-product-price {
    font-size: 18px;
    font-weight: 700;
    color: #28a745;
}

.related-current-price {
    font-size: 18px;
    font-weight: 700;
    color: #28a745;
    margin: 0;
}

.related-original-price {
    font-size: 14px;
    font-weight: 500;
    color: #6c757d;
    text-decoration: line-through;
    margin: 0;
}

/* Responsive Design */
@media (max-width: 768px) {
    .reviews-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }
    
    .reviews-title {
        font-size: 20px;
    }
    
    .btn-review {
        align-self: flex-start;
    }
    
    .product-main-content {
        grid-template-columns: 1fr;
        gap: 30px;
    }
    
    .product-image-gallery {
        position: static;
    }
    
    .main-image {
        height: 300px;
    }
    
    .product-title {
        font-size: 24px;
    }
    
    .current-price {
        font-size: 28px;
    }
    
    .product-actions {
        flex-direction: column;
    }
    
    .btn-add-cart,
    .btn-buy-now {
        width: 100%;
        justify-content: center;
    }
    
    .related-grid {
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    }
    
    .specs-table th,
    .specs-table td {
        padding: 10px 15px;
        font-size: 14px;
    }
}

/* Lightbox Styles */
.lightbox-modal {
    display: none;
    position: fixed;
    z-index: 9999;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.9);
    justify-content: center;
    align-items: center;
}

.lightbox-content {
    position: relative;
    max-width: 90%;
    max-height: 90%;
    display: flex;
    flex-direction: column;
    align-items: center;
}

.lightbox-image-container {
    position: relative;
    display: flex;
    justify-content: center;
    align-items: center;
}

.lightbox-image {
    max-width: 100%;
    max-height: 80vh;
    object-fit: contain;
    border-radius: 8px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.5);
}

.lightbox-close {
    position: absolute;
    top: -40px;
    right: 0;
    color: white;
    font-size: 30px;
    font-weight: bold;
    cursor: pointer;
    z-index: 10000;
    background: rgba(0, 0, 0, 0.5);
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background-color 0.3s ease;
}

.lightbox-close:hover {
    background: rgba(0, 0, 0, 0.8);
}

.lightbox-nav {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    width: 100%;
    display: flex;
    justify-content: space-between;
    pointer-events: none;
}

.lightbox-prev,
.lightbox-next {
    background: rgba(0, 0, 0, 0.5);
    color: white;
    border: none;
    padding: 15px 20px;
    font-size: 18px;
    cursor: pointer;
    border-radius: 50%;
    width: 50px;
    height: 50px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background-color 0.3s ease;
    pointer-events: all;
}

.lightbox-prev:hover,
.lightbox-next:hover {
    background: rgba(0, 0, 0, 0.8);
}

.lightbox-prev {
    margin-left: 20px;
}

.lightbox-next {
    margin-right: 20px;
}

.lightbox-counter {
    position: absolute;
    bottom: -40px;
    left: 50%;
    transform: translateX(-50%);
    color: white;
    background: rgba(0, 0, 0, 0.5);
    padding: 8px 16px;
    border-radius: 20px;
    font-size: 14px;
}

/* Enhanced thumbnail styles */
.thumbnail-gallery {
    display: flex;
    gap: 10px;
    overflow-x: auto;
    padding: 10px 0;
    scrollbar-width: thin;
    scrollbar-color: #ccc transparent;
}

.thumbnail-gallery::-webkit-scrollbar {
    height: 6px;
}

.thumbnail-gallery::-webkit-scrollbar-track {
    background: transparent;
}

.thumbnail-gallery::-webkit-scrollbar-thumb {
    background: #ccc;
    border-radius: 3px;
}

.thumbnail-gallery::-webkit-scrollbar-thumb:hover {
    background: #999;
}

.thumbnail {
    width: 80px;
    height: 80px;
    object-fit: cover;
    border: 2px solid #e9ecef;
    border-radius: 4px;
    cursor: pointer;
    transition: all 0.3s ease;
    flex-shrink: 0;
}

.thumbnail:hover {
    border-color: #007bff;
    transform: scale(1.05);
}

.thumbnail.active {
    border-color: #007bff;
    box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.25);
}
</style>


<!-- Product Details -->
<div class="product-details-container">
    <div class="container">
        <!-- Back to Products Link -->
        <div class="back-to-products" style="margin-bottom: 20px;">
            <a href="index.php?from=product" class="btn-back" style="display: inline-flex; align-items: center; gap: 8px; padding: 10px 20px; background: #f8f9fa; color: #495057; text-decoration: none; border-radius: 8px; border: 1px solid #dee2e6; transition: all 0.3s ease;">
                <i class="fas fa-arrow-left"></i>
                <span>Back to Products</span>
            </a>
        </div>
        
        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error_message) ?></div>
        <?php else: ?>
            
            <div class="product-main-content">
                <!-- Product Image Gallery -->
                <div class="product-image-gallery">
                    <div class="main-image-container">
                        <?php if (!empty($product_images)): ?>
                            <img src="<?php echo BASE_URL; ?>/public/image.php?file=<?php echo urlencode($product_images[0]['image_filename']); ?>" 
                                 alt="<?php echo htmlspecialchars($product_images[0]['image_alt_text']); ?>" 
                                 class="main-image"
                                 id="mainImage"
                                 onclick="openLightbox(0)">
                        <?php else: ?>
                            <img src="<?php echo IMG_URL; ?>/placeholder-product.png" 
                                 alt="<?php echo htmlspecialchars($product['product_name']); ?>" 
                                 class="main-image"
                                 id="mainImage">
                        <?php endif; ?>
                        
                        <!-- Zoom indicator -->
                        <div class="zoom-indicator">
                            <i class="fas fa-search-plus"></i>
                            <span>Click to zoom</span>
                        </div>
                    </div>
                    
                    <!-- Thumbnail Gallery -->
                    <div class="thumbnail-gallery">
                        <?php if (!empty($product_images)): ?>
                            <?php foreach ($product_images as $index => $image): ?>
                                <img src="<?php echo BASE_URL; ?>/public/image.php?file=<?php echo urlencode($image['image_filename']); ?>" 
                                     alt="<?php echo htmlspecialchars($image['image_alt_text']); ?>" 
                                     class="thumbnail <?php echo $index === 0 ? 'active' : ''; ?>"
                                     onclick="changeImage(this, <?php echo $index; ?>)"
                                     data-index="<?php echo $index; ?>">
                            <?php endforeach; ?>
                        <?php else: ?>
                            <img src="<?php echo IMG_URL; ?>/placeholder-product.png" 
                                 alt="No Image Available" 
                                 class="thumbnail active"
                                 onclick="changeImage(this, 0)"
                                 data-index="0">
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Product Information -->
                <div class="product-info">
                    <div class="product-brand"><?php echo htmlspecialchars($product['brand_name']); ?></div>
                    <h1 class="product-title"><?php echo htmlspecialchars($product['product_name']); ?></h1>
                    <div class="product-model"><?php echo htmlspecialchars($product['model_name'] . ' - ' . $product['model_number']); ?></div>
                    
                    <!-- Rating -->
                    <div class="product-rating">
                        <div class="rating-stars">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <span class="star"><?php echo $i <= $avg_rating ? '★' : '☆'; ?></span>
                            <?php endfor; ?>
                        </div>
                        <span class="rating-text"><?php echo $avg_rating; ?> (<?php echo $total_reviews; ?> reviews)</span>
                    </div>
                    
                    <!-- Price -->
                    <div class="product-price-section">
                        <?php if ($product['original_price'] && $product['original_price'] > $product['price']): ?>
                            <!-- Discount pricing display -->
                            <div class="pricing-container">
                                <div class="price-row">
                                    <span class="current-price">₹<?php echo number_format($product['price'], 0); ?></span>
                                    <span class="original-price">₹<?php echo number_format($product['original_price'], 0); ?></span>
                                    <span class="discount-badge"><?php echo number_format($product['discount_percentage'], 0); ?>% OFF</span>
                                </div>
                                <div class="savings-text">You save ₹<?php echo number_format($product['discount_amount'], 0); ?></div>
                                <div class="price-label">Inclusive of all taxes</div>
                            </div>
                        <?php else: ?>
                            <!-- Regular pricing -->
                            <div class="pricing-container">
                                <div class="current-price">₹<?php echo number_format($product['price'], 0); ?></div>
                                <div class="price-label">Inclusive of all taxes</div>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($product['amc_available']): ?>
                    <div class="stock-info">
                        <div class="stock-badge" style="background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; margin-top: 10px;">
                            <i class="fas fa-shield-alt"></i>
                            <span>AMC Available</span>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Quantity Selector -->
                    <div class="quantity-section">
                        <div class="quantity-label">Quantity</div>
                        <div class="quantity-controls">
                            <button class="quantity-btn" onclick="decreaseQuantity()">-</button>
                            <input type="number" class="quantity-input" id="quantity" value="1" min="1">
                            <button class="quantity-btn" onclick="increaseQuantity()">+</button>
                        </div>
                    </div>
                    
                    <!-- Action Buttons -->
                    <div class="product-actions">
                        <a href="#" class="btn-add-cart" onclick="addToCart()">
                            <i class="fas fa-shopping-cart"></i>
                            Add to Cart
                        </a>
                        <a href="#" class="btn-buy-now" onclick="buyNow()">
                            <i class="fas fa-bolt"></i>
                            Buy Now
                        </a>
                    </div>
                    
                    <!-- Key Features -->
                    <div class="product-features">
                        <h3 class="features-title">Key Features</h3>
                        <ul class="features-list">
                            <?php if (!empty($product['features'])): ?>
                                <?php 
                                $features = explode('|', $product['features']);
                                foreach ($features as $feature): 
                                    if (!empty(trim($feature))):
                                ?>
                                    <li class="feature-item">
                                        <i class="fas fa-check"></i>
                                        <span><?php echo htmlspecialchars(trim($feature)); ?></span>
                                    </li>
                                <?php 
                                    endif;
                                endforeach; ?>
                            <?php else: ?>
                                <!-- Default features when none are specified -->
                                <li class="feature-item">
                                    <i class="fas fa-check"></i>
                                    <span>Energy Efficient <?php echo $product['energy_rating'] ?? $product['star_rating'] . ' Star'; ?> Rating</span>
                                </li>
                                <li class="feature-item">
                                    <i class="fas fa-check"></i>
                                    <span><?php echo $product['inverter']; ?> Inverter Technology</span>
                                </li>
                                <li class="feature-item">
                                    <i class="fas fa-check"></i>
                                    <span><?php echo $product['capacity']; ?> Cooling Capacity</span>
                                </li>
                                <li class="feature-item">
                                    <i class="fas fa-check"></i>
                                    <span><?php echo $product['warranty_years']; ?> Year Warranty</span>
                                </li>
                                <?php if ($product['amc_available']): ?>
                                <li class="feature-item">
                                    <i class="fas fa-check"></i>
                                    <span>AMC (Annual Maintenance Contract) Available</span>
                                </li>
                                <?php endif; ?>
                            <?php endif; ?>
                        </ul>
                    </div>
                    
                    <!-- Product Description -->
                    <div class="product-description">
                        <h3 class="description-title">Description</h3>
                        <div class="description-text">
                            <?php if (!empty($product['description'])): ?>
                                <?php echo nl2br(htmlspecialchars($product['description'])); ?>
                            <?php else: ?>
                                This premium air conditioning unit offers excellent cooling performance with energy-efficient technology. 
                                Perfect for residential and commercial spaces, providing comfort and reliability year-round.
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
                
            <!-- Technical Specifications Section -->
            <div class="specifications-section">
                <div class="container">
                    <h2 class="specs-title">Technical Specifications</h2>
                    <table class="specs-table">
                        <tbody>
                            <tr>
                                <th>Category</th>
                                <td><?php echo htmlspecialchars($product['category_name'] ?? 'N/A'); ?></td>
                            </tr>
                            <tr>
                                <th>Sub Category</th>
                                <td><?php echo htmlspecialchars($product['subcategory_name'] ?? 'N/A'); ?></td>
                            </tr>
                            <tr>
                                <th>Brand</th>
                                <td><?php echo htmlspecialchars($product['brand_name'] ?? 'N/A'); ?></td>
                            </tr>
                            <tr>
                                <th>Model Name</th>
                                <td><?php echo htmlspecialchars($product['model_name'] ?? 'N/A'); ?></td>
                            </tr>
                            <tr>
                                <th>Model Number</th>
                                <td><?php echo htmlspecialchars($product['model_number'] ?? 'N/A'); ?></td>
                            </tr>
                            <tr>
                                <th>Inverter Technology</th>
                                <td><?php echo htmlspecialchars($product['inverter'] ?? 'N/A'); ?></td>
                            </tr>
                            <tr>
                                <th>Energy Rating</th>
                                <td><?php echo htmlspecialchars($product['energy_rating'] ?? $product['star_rating'] . ' Star' ?? 'N/A'); ?></td>
                            </tr>
                            <tr>
                                <th>Capacity</th>
                                <td><?php echo htmlspecialchars($product['capacity'] ?? 'N/A'); ?></td>
                            </tr>
                            <tr>
                                <th>Warranty Period</th>
                                <td><?php echo htmlspecialchars($product['warranty_years']); ?> Years</td>
                            </tr>
                            <tr>
                                <th>AMC Available</th>
                                <td><?php echo $product['amc_available'] ? 'Yes' : 'No'; ?></td>
                            </tr>
                            <tr>
                                <th>Product Status</th>
                                <td><?php echo ucfirst(str_replace('_', ' ', $product['status'])); ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
                
            <!-- Reviews Section -->
            <div class="reviews-section">
                <div class="container">
                    <div class="reviews-header">
                        <h2 class="reviews-title">Customer Reviews</h2>
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <?php
                            // Check if user has already reviewed this product
                            $user_review_check = $pdo->prepare("SELECT id FROM reviews WHERE product_id = ? AND user_id = ?");
                            $user_review_check->execute([$product_id, $_SESSION['user_id']]);
                            $user_has_reviewed = $user_review_check->fetch();
                            ?>
                            <?php if (!$user_has_reviewed): ?>
                                <a href="reviews.php?product=<?= $product_id; ?>" class="btn-review">
                                    <i class="fas fa-star"></i>
                                    Write a Review
                                </a>
                            <?php else: ?>
                                <span class="reviewed-badge">
                                    <i class="fas fa-check-circle"></i>
                                    You've reviewed this product
                                </span>
                            <?php endif; ?>
                        <?php else: ?>
                            <a href="../auth/login.php?redirect=<?= urlencode('products/reviews.php?product=' . $product_id); ?>" class="btn-review">
                                <i class="fas fa-star"></i>
                                Login to Review
                            </a>
                        <?php endif; ?>
                    </div>
            <?php if (!empty($reviews)): ?>
                        <?php foreach ($reviews as $review): ?>
                            <div class="review-item">
                                <div class="review-header">
                                    <div class="reviewer-info">
                                        <div>
                                            <div class="reviewer-name"><?php echo htmlspecialchars($review['username']); ?></div>
                                            <div class="review-date"><?php echo date('M d, Y', strtotime($review['created_at'])); ?></div>
                                        </div>
                                    </div>
                                    <div class="review-rating">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <span class="star"><?php echo $i <= $review['rating'] ? '★' : '☆'; ?></span>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                                <div class="review-content">
                                    <?php if (!empty($review['review_title'])): ?>
                                        <div class="review-title"><?php echo htmlspecialchars($review['review_title']); ?></div>
                                    <?php endif; ?>
                                    <div class="review-text"><?php echo nl2br(htmlspecialchars($review['review_text'])); ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
            <?php else: ?>
                <div class="no-reviews">
                    <p>No reviews yet. Be the first to review this product!</p>
                </div>
            <?php endif; ?>
                </div>
            </div>
            
            <!-- Related Products Section -->
            <?php if (!empty($related_products)): ?>
                <div class="related-products-section">
                    <div class="container">
                        <h2 class="related-title">You May Also Like</h2>
                        <div class="related-grid">
                            <?php foreach ($related_products as $related): ?>
                                <a href="<?= product_url($related['id']) ?>" class="related-product-card">
                                    <img src="<?php echo BASE_URL; ?>/public/image.php?file=<?php echo urlencode($related['product_image']); ?>" 
                                         alt="<?php echo htmlspecialchars($related['product_name']); ?>" 
                                         class="related-product-image">
                                    <h3 class="related-product-title"><?php echo htmlspecialchars($related['product_name']); ?></h3>
                                    <div class="related-product-price">
                                        <?php if ($related['original_price'] && $related['original_price'] > $related['price']): ?>
                                            <div class="related-current-price">₹<?php echo number_format($related['price'], 0); ?></div>
                                            <div class="related-original-price">₹<?php echo number_format($related['original_price'], 0); ?></div>
                                        <?php else: ?>
                                            <div class="related-current-price">₹<?php echo number_format($related['price'], 0); ?></div>
                                        <?php endif; ?>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
                
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Image Lightbox Modal -->
<div id="imageLightbox" class="lightbox-modal">
    <div class="lightbox-content">
        <span class="lightbox-close" onclick="closeLightbox()">&times;</span>
        <div class="lightbox-image-container">
            <img id="lightboxImage" src="" alt="" class="lightbox-image">
            <div class="lightbox-nav">
                <button class="lightbox-prev" onclick="previousImage()">
                    <i class="fas fa-chevron-left"></i>
                </button>
                <button class="lightbox-next" onclick="nextImage()">
                    <i class="fas fa-chevron-right"></i>
                </button>
            </div>
        </div>
        <div class="lightbox-counter">
            <span id="imageCounter">1 / 1</span>
        </div>
    </div>
</div>

<script>
// Product Details Page JavaScript

// Image gallery functionality
let currentImageIndex = 0;
let productImages = <?php echo json_encode($product_images ?: []); ?>;

function changeImage(thumbnail, index) {
    const mainImage = document.getElementById('mainImage');
    const thumbnails = document.querySelectorAll('.thumbnail');
    
    // Remove active class from all thumbnails
    thumbnails.forEach(thumb => thumb.classList.remove('active'));
    
    // Add active class to clicked thumbnail
    thumbnail.classList.add('active');
    
    // Change main image source
    mainImage.src = thumbnail.src;
    mainImage.alt = thumbnail.alt;
    
    // Update current image index
    currentImageIndex = index;
}

// Lightbox functionality
function openLightbox(index) {
    if (productImages.length === 0) return;
    
    currentImageIndex = index;
    const lightbox = document.getElementById('imageLightbox');
    const lightboxImage = document.getElementById('lightboxImage');
    const imageCounter = document.getElementById('imageCounter');
    
    lightboxImage.src = productImages[index].image_filename ? 
        '<?php echo BASE_URL; ?>/public/image.php?file=' + encodeURIComponent(productImages[index].image_filename) :
        '<?php echo IMG_URL; ?>/placeholder-product.png';
    lightboxImage.alt = productImages[index].image_alt_text || 'Product Image';
    
    imageCounter.textContent = `${index + 1} / ${productImages.length}`;
    
    lightbox.style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function closeLightbox() {
    const lightbox = document.getElementById('imageLightbox');
    lightbox.style.display = 'none';
    document.body.style.overflow = 'auto';
}

function previousImage() {
    if (productImages.length <= 1) return;
    
    currentImageIndex = (currentImageIndex - 1 + productImages.length) % productImages.length;
    const lightboxImage = document.getElementById('lightboxImage');
    const imageCounter = document.getElementById('imageCounter');
    
    lightboxImage.src = productImages[currentImageIndex].image_filename ? 
        '<?php echo BASE_URL; ?>/public/image.php?file=' + encodeURIComponent(productImages[currentImageIndex].image_filename) :
        '<?php echo IMG_URL; ?>/placeholder-product.png';
    lightboxImage.alt = productImages[currentImageIndex].image_alt_text || 'Product Image';
    
    imageCounter.textContent = `${currentImageIndex + 1} / ${productImages.length}`;
}

function nextImage() {
    if (productImages.length <= 1) return;
    
    currentImageIndex = (currentImageIndex + 1) % productImages.length;
    const lightboxImage = document.getElementById('lightboxImage');
    const imageCounter = document.getElementById('imageCounter');
    
    lightboxImage.src = productImages[currentImageIndex].image_filename ? 
        '<?php echo BASE_URL; ?>/public/image.php?file=' + encodeURIComponent(productImages[currentImageIndex].image_filename) :
        '<?php echo IMG_URL; ?>/placeholder-product.png';
    lightboxImage.alt = productImages[currentImageIndex].image_alt_text || 'Product Image';
    
    imageCounter.textContent = `${currentImageIndex + 1} / ${productImages.length}`;
}

// Close lightbox when clicking outside the image
document.getElementById('imageLightbox').addEventListener('click', function(e) {
    if (e.target === this) {
        closeLightbox();
    }
});

// Keyboard navigation
document.addEventListener('keydown', function(e) {
    const lightbox = document.getElementById('imageLightbox');
    if (lightbox.style.display === 'flex') {
        if (e.key === 'Escape') {
            closeLightbox();
        } else if (e.key === 'ArrowLeft') {
            previousImage();
        } else if (e.key === 'ArrowRight') {
            nextImage();
        }
    }
});

// Quantity controls
function increaseQuantity() {
    const quantityInput = document.getElementById('quantity');
    const maxQuantity = parseInt(quantityInput.getAttribute('max'));
    const currentQuantity = parseInt(quantityInput.value);
    
    if (currentQuantity < maxQuantity) {
        quantityInput.value = currentQuantity + 1;
    }
}

function decreaseQuantity() {
    const quantityInput = document.getElementById('quantity');
    const currentQuantity = parseInt(quantityInput.value);
    
    if (currentQuantity > 1) {
        quantityInput.value = currentQuantity - 1;
    }
}

// Add to cart functionality
function addToCart() {
    const productId = <?php echo $product['id']; ?>;
    const quantity = document.getElementById('quantity').value;
    
    // Create form and submit
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '../cart/add.php';
    
    const productIdInput = document.createElement('input');
    productIdInput.type = 'hidden';
    productIdInput.name = 'product_id';
    productIdInput.value = productId;
    
    const quantityInput = document.createElement('input');
    quantityInput.type = 'hidden';
    quantityInput.name = 'quantity';
    quantityInput.value = quantity;
    
    form.appendChild(productIdInput);
    form.appendChild(quantityInput);
    document.body.appendChild(form);
    form.submit();
}

// Buy now functionality
function buyNow() {
    const productId = <?php echo $product['id']; ?>;
    const quantity = document.getElementById('quantity').value;
    
    // Check if cart has items
    fetch('../cart/count.php')
        .then(response => response.json())
        .then(data => {
            if (data.count > 0) {
                if (confirm(`You have ${data.count} items in your cart. Buy Now will clear your cart and purchase only this item. Continue?`)) {
                    proceedWithBuyNow(productId, quantity);
                }
            } else {
                proceedWithBuyNow(productId, quantity);
            }
        })
        .catch(error => {
            console.error('Error checking cart count:', error);
            proceedWithBuyNow(productId, quantity);
        });
}

function proceedWithBuyNow(productId, quantity) {
    // Create form and submit via POST to add to cart first
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '../cart/add.php';
    
    const productIdInput = document.createElement('input');
    productIdInput.type = 'hidden';
    productIdInput.name = 'product_id';
    productIdInput.value = productId;
    
    const quantityInput = document.createElement('input');
    quantityInput.type = 'hidden';
    quantityInput.name = 'quantity';
    quantityInput.value = quantity;
    
    // Add a hidden field to indicate this is a buy-now action
    const buyNowInput = document.createElement('input');
    buyNowInput.type = 'hidden';
    buyNowInput.name = 'buy_now';
    buyNowInput.value = '1';
    
    form.appendChild(productIdInput);
    form.appendChild(quantityInput);
    form.appendChild(buyNowInput);
    document.body.appendChild(form);
    form.submit();
}

// Image loading with fallback
document.addEventListener('DOMContentLoaded', function() {
    const images = document.querySelectorAll('img');
    
    images.forEach(img => {
        img.addEventListener('error', function() {
            if (this.src !== '<?php echo IMG_URL; ?>/placeholder-product.png') {
                this.src = '<?php echo IMG_URL; ?>/placeholder-product.png';
            }
        });
        
        img.addEventListener('load', function() {
            this.style.opacity = '1';
        });
        
        // Set initial loading state
        if (img.complete) {
            img.style.opacity = '1';
        } else {
            img.style.opacity = '0.7';
            img.style.transition = 'opacity 0.3s ease';
        }
    });
    
    // Initialize quantity input validation
    const quantityInput = document.getElementById('quantity');
    if (quantityInput) {
        quantityInput.addEventListener('input', function() {
            const value = parseInt(this.value);
            const max = parseInt(this.getAttribute('max'));
            const min = parseInt(this.getAttribute('min'));
            
            if (value > max) {
                this.value = max;
            } else if (value < min) {
                this.value = min;
            }
        });
    }
});

// Smooth scrolling for anchor links
document.addEventListener('DOMContentLoaded', function() {
    const links = document.querySelectorAll('a[href^="#"]');
    
    links.forEach(link => {
        link.addEventListener('click', function(e) {
            const targetId = this.getAttribute('href').substring(1);
            const targetElement = document.getElementById(targetId);
            
            if (targetElement) {
                e.preventDefault();
                targetElement.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
    
    // Scroll to product content on page load (for navigation from homepage)
    // Small delay to ensure page is fully rendered
    setTimeout(function() {
        const productContent = document.querySelector('.product-details-container');
        if (productContent && window.scrollY === 0) {
            window.scrollTo({
                top: 0,
                behavior: 'instant' // Instant scroll on initial load
            });
        }
    }, 100);
});
</script>

<?php include INCLUDES_PATH . '/templates/footer.php'; ?>
