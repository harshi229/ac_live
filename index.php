<?php
// Set page metadata
$pageTitle = 'Home';
$pageDescription = 'Welcome to Akash Enterprise - Your trusted partner for premium air conditioning solutions since 1962. Quality AC sales, installation, and maintenance services.';
$pageKeywords = 'air conditioning, AC sales, AC installation, AC maintenance, split AC, window AC, commercial AC, residential AC, AMC services, best AC dealer';

require_once __DIR__ . '/includes/config/init.php';
include INCLUDES_PATH . '/templates/header.php';

// Add homepage loader directly to ensure it shows
echo '<div class="page-loader" id="pageLoader">
    <div class="loader-container">
        <div class="loader-ring"></div>
        <div class="loader-ring"></div>
        <div class="loader-ring"></div>
        <div class="loader-ring"></div>
        <div class="loader-logo">
            <i class="fas fa-snowflake"></i>
        </div>
    </div>
    <div class="loader-text">Akash Enterprise</div>
    <div class="loader-subtext">Loading your AC experience...</div>
    <div class="loader-progress">
        <div class="loader-progress-bar"></div>
    </div>
</div>

<style>
/* Homepage-specific loader fixes with maximum specificity */
.page-loader#pageLoader {
    position: fixed !important;
    top: 0 !important;
    left: 0 !important;
    width: 100% !important;
    height: 100% !important;
    background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #334155 100%) !important;
    display: flex !important;
    flex-direction: column !important;
    justify-content: center !important;
    align-items: center !important;
    z-index: 9999 !important;
    transition: opacity 0.5s ease-out, visibility 0.5s ease-out !important;
}

.page-loader#pageLoader .loader-container {
    position: relative !important;
    width: 120px !important;
    height: 120px !important;
    margin-bottom: 30px !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
}

.page-loader#pageLoader .loader-ring {
    position: absolute !important;
    border: 3px solid transparent !important;
    border-radius: 50% !important;
    animation: spin 2s linear infinite !important;
}

.page-loader#pageLoader .loader-ring:nth-child(1) {
    width: 100% !important;
    height: 100% !important;
    top: 0 !important;
    left: 0 !important;
    border-top-color: #3b82f6 !important;
    animation-duration: 2s !important;
}

.page-loader#pageLoader .loader-ring:nth-child(2) {
    width: 90% !important;
    height: 90% !important;
    top: 5% !important;
    left: 5% !important;
    border-right-color: #8b5cf6 !important;
    animation-duration: 1.5s !important;
    animation-direction: reverse !important;
}

.page-loader#pageLoader .loader-ring:nth-child(3) {
    width: 80% !important;
    height: 80% !important;
    top: 10% !important;
    left: 10% !important;
    border-bottom-color: #06b6d4 !important;
    animation-duration: 1s !important;
}

.page-loader#pageLoader .loader-ring:nth-child(4) {
    width: 70% !important;
    height: 70% !important;
    top: 15% !important;
    left: 15% !important;
    border-left-color: #10b981 !important;
    animation-duration: 0.8s !important;
    animation-direction: reverse !important;
}

.page-loader#pageLoader .loader-logo {
    position: absolute !important;
    top: 50% !important;
    left: 50% !important;
    transform: translate(-50%, -50%) !important;
    width: 50px !important;
    height: 50px !important;
    background: linear-gradient(135deg, #3b82f6, #8b5cf6) !important;
    border-radius: 12px !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    animation: homepagePulse 2s ease-in-out infinite !important;
    box-shadow: 0 0 30px rgba(59, 130, 246, 0.5) !important;
    z-index: 100 !important;
}

.page-loader#pageLoader .loader-logo i {
    color: white !important;
    font-size: 24px !important;
    animation: homepageBounce 1s ease-in-out infinite !important;
    display: block !important;
    line-height: 1 !important;
    margin: 0 !important;
    padding: 0 !important;
}

.page-loader#pageLoader .loader-text {
    color: white !important;
    font-size: 1.2rem !important;
    font-weight: 600 !important;
    margin-bottom: 10px !important;
    opacity: 0 !important;
    animation: fadeInUp 0.8s ease-out 0.5s forwards !important;
}

.page-loader#pageLoader .loader-subtext {
    color: #94a3b8 !important;
    font-size: 0.9rem !important;
    opacity: 0 !important;
    animation: fadeInUp 0.8s ease-out 0.8s forwards !important;
}

.page-loader#pageLoader .loader-progress {
    width: 200px !important;
    height: 4px !important;
    background: rgba(255, 255, 255, 0.1) !important;
    border-radius: 2px !important;
    overflow: hidden !important;
    margin-top: 20px !important;
    opacity: 0 !important;
    animation: fadeInUp 0.8s ease-out 1.1s forwards !important;
}

.page-loader#pageLoader .loader-progress-bar {
    height: 100% !important;
    background: linear-gradient(90deg, #3b82f6, #8b5cf6, #06b6d4) !important;
    border-radius: 2px !important;
    width: 0% !important;
    animation: progress 3s ease-out forwards !important;
    position: relative !important;
}

@keyframes homepagePulse {
    0%, 100% { transform: translate(-50%, -50%) scale(1); }
    50% { transform: translate(-50%, -50%) scale(1.1); }
}

@keyframes homepageBounce {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-5px); }
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes progress {
    0% { width: 0%; }
    100% { width: 100%; }
}
</style>';

// Add homepage enhancement files
echo '<link rel="stylesheet" type="text/css" href="' . CSS_URL . '/homepage-enhancements.css?v=' . APP_VERSION . '">';

// Add image preloading for immediate display
echo '<link rel="preload" as="image" href="' . IMG_URL . '/slider11.png">';
echo '<link rel="preload" as="image" href="' . IMG_URL . '/slider2.png">';
echo '<link rel="preload" as="image" href="' . IMG_URL . '/slider3.png">';
echo '<link rel="preload" as="image" href="' . IMG_URL . '/placeholder-product.png">';

// Fetch featured products, categories, and brands
try {
    // Get featured products
    $featured_stmt = $pdo->prepare("
        SELECT p.*, b.name as brand_name, c.name as category_name
        FROM products p
        LEFT JOIN brands b ON p.brand_id = b.id
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE p.status = 'active'
        ORDER BY p.created_at DESC
        LIMIT 6
    ");
    $featured_stmt->execute();
    $featured_products = $featured_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get categories with product counts
    $categories_stmt = $pdo->prepare("
        SELECT c.*, COUNT(p.id) as product_count
        FROM categories c
        LEFT JOIN products p ON c.id = p.category_id AND p.status = 'active'
        WHERE c.status = 'active'
        GROUP BY c.id
        ORDER BY product_count DESC
        LIMIT 6
    ");
    $categories_stmt->execute();
    $categories = $categories_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get brands
    $brands_stmt = $pdo->query("SELECT * FROM brands WHERE status = 'active' LIMIT 8");
    $brands = $brands_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get real statistics
    $stats_stmt = $pdo->query("
        SELECT 
            (SELECT COUNT(*) FROM products WHERE status = 'active') as total_products,
            (SELECT COUNT(*) FROM users WHERE status = 'active') as total_customers,
            (SELECT COUNT(*) FROM orders WHERE status != 'cancelled') as total_orders,
            (SELECT COUNT(*) FROM reviews WHERE status = 'approved') as total_reviews
    ");
    $stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
    
    // Add dynamic preloading for category images - with consistent cache buster
    if (!empty($categories)) {
        $preloadCacheBuster = time(); // Consistent timestamp for preload
        foreach (array_slice($categories, 0, 6) as $category) {
            if (!empty($category['image'])) {
                // Build the same image path logic as in the display code
                $preloadPath = '';
                if (strpos($category['image'], 'http') === 0) {
                    $urlParts = parse_url($category['image']);
                    $fullPath = $urlParts['path'] ?? '';
                    $path = preg_replace('#^/ac#', '', $fullPath);
                    $path = preg_replace('#^/public#', '', $path);
                    if (preg_match('#/img/uploads/(.+)$#', $path, $matches)) {
                        $preloadPath = BASE_URL . '/public/image.php?file=' . urlencode($matches[1]) . '&v=' . $preloadCacheBuster;
                    }
                } else {
                    $normalizedPath = str_replace('\\', '/', $category['image']);
                    if (strpos($normalizedPath, 'categories/') !== false) {
                        if (strpos($normalizedPath, '/categories/') !== false) {
                            $parts = explode('/categories/', $normalizedPath);
                            $normalizedPath = 'categories/' . end($parts);
                        }
                        $preloadPath = BASE_URL . '/public/image.php?file=' . urlencode($normalizedPath) . '&v=' . $preloadCacheBuster;
                    }
                }
                if ($preloadPath) {
                    echo '<link rel="preload" as="image" href="' . htmlspecialchars($preloadPath) . '">';
                }
            }
        }
    }
    
    // Add dynamic preloading for product images
    if (!empty($featured_products)) {
        foreach (array_slice($featured_products, 0, 6) as $product) {
            echo '<link rel="preload" as="image" href="' . BASE_URL . '/public/image.php?file=' . urlencode($product['product_image']) . '">';
        }
    }
    
} catch (PDOException $e) {
    error_log("Error fetching homepage data: " . $e->getMessage());
}
?>

<!-- Carousel styles moved to homepage-enhancements.css -->

<!-- Hero Carousel Section -->
<style>
.hero-carousel-section {
    height: 100vh;
    position: relative;
    overflow: hidden;
}

.hero-carousel-section .carousel {
    height: 100%;
}

.hero-carousel-section .carousel-inner {
    height: 100%;
}

.hero-carousel-section .carousel-item {
    height: 100%;
    position: relative;
}

.hero-carousel-section .carousel-item img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    object-position: center;
    opacity: 1 !important;
    display: block !important;
    visibility: visible !important;
}

.carousel-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(135deg, rgba(15, 23, 42, 0.8) 0%, rgba(30, 41, 59, 0.6) 50%, rgba(51, 65, 85, 0.4) 100%);
    display: flex;
    align-items: center;
    z-index: 2;
}

.carousel-content {
    color: white;
    position: relative;
    z-index: 3;
    animation: fadeInUp 1s ease-out;
}

.carousel-badge {
    display: inline-block;
    background: rgba(59, 130, 246, 0.2);
    border: 2px solid rgba(59, 130, 246, 0.5);
    padding: 8px 20px;
    border-radius: 30px;
    font-size: 0.9rem;
    font-weight: 600;
    margin-bottom: 25px;
    backdrop-filter: blur(10px);
}

.carousel-title {
    font-size: 4rem;
    font-weight: 800;
    line-height: 1.1;
    margin-bottom: 25px;
    background: linear-gradient(135deg, #3b82f6, #22c55e, #8b5cf6);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.carousel-subtitle {
    font-size: 1.4rem;
    color: #cbd5e1;
    margin-bottom: 40px;
    max-width: 600px;
    line-height: 1.6;
}

.carousel-buttons {
    display: flex;
    gap: 20px;
    flex-wrap: wrap;
}

.carousel-btn {
    padding: 16px 40px;
    font-size: 1.1rem;
    font-weight: 600;
    border-radius: 50px;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 10px;
    text-decoration: none;
}

.carousel-btn-primary {
    background: linear-gradient(135deg, #3b82f6, #1d4ed8);
    color: white;
    border: none;
}

.carousel-btn-primary:hover {
    transform: translateY(-3px);
    box-shadow: 0 15px 40px rgba(59, 130, 246, 0.5);
    color: white;
}

.carousel-btn-secondary {
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(10px);
    color: white;
    border: 2px solid rgba(255, 255, 255, 0.3);
}

.carousel-btn-secondary:hover {
    background: rgba(255, 255, 255, 0.2);
    border-color: rgba(255, 255, 255, 0.5);
    transform: translateY(-3px);
    color: white;
}

/* Carousel Controls */
.hero-carousel-section .carousel-control-prev,
.hero-carousel-section .carousel-control-next {
    width: 60px;
    height: 60px;
    background: rgba(0, 0, 0, 0.3);
    border-radius: 50%;
    top: 50%;
    transform: translateY(-50%);
    backdrop-filter: blur(10px);
    border: 2px solid rgba(255, 255, 255, 0.2);
    transition: all 0.3s ease;
}

.hero-carousel-section .carousel-control-prev {
    left: 30px;
}

.hero-carousel-section .carousel-control-next {
    right: 30px;
}

.hero-carousel-section .carousel-control-prev:hover,
.hero-carousel-section .carousel-control-next:hover {
    background: rgba(59, 130, 246, 0.5);
    border-color: rgba(59, 130, 246, 0.8);
    transform: translateY(-50%) scale(1.1);
}

.hero-carousel-section .carousel-control-prev-icon,
.hero-carousel-section .carousel-control-next-icon {
    width: 20px;
    height: 20px;
}

/* Carousel Indicators */
.hero-carousel-section .carousel-indicators {
    bottom: 30px;
    margin-bottom: 0;
}

.hero-carousel-section .carousel-indicators button {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.3);
    border: 2px solid rgba(255, 255, 255, 0.5);
    margin: 0 8px;
    transition: all 0.3s ease;
}

.hero-carousel-section .carousel-indicators button.active {
    background: #3b82f6;
    border-color: #3b82f6;
    transform: scale(1.2);
}

.hero-carousel-section .carousel-indicators button:hover {
    background: rgba(59, 130, 246, 0.7);
    border-color: #3b82f6;
}

/* Carousel Animation */
.carousel-item {
    transition: transform 0.8s ease-in-out;
}

.carousel-item.active .carousel-content {
    animation: fadeInUp 1s ease-out 0.3s both;
}

.carousel-item.active .carousel-badge {
    animation: fadeInDown 0.8s ease-out 0.1s both;
}

.carousel-item.active .carousel-title {
    animation: fadeInUp 0.8s ease-out 0.2s both;
}

.carousel-item.active .carousel-subtitle {
    animation: fadeInUp 0.8s ease-out 0.4s both;
}

.carousel-item.active .carousel-buttons {
    animation: fadeInUp 0.8s ease-out 0.6s both;
}

/* Stats Section */
.stats-section {
    background: linear-gradient(180deg, #ffffff 0%, #f8f9fa 100%);
    padding: 80px 0;
    margin-top: -60px;
    position: relative;
    z-index: 3;
}

.stats-container {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 30px;
    max-width: 1200px;
    margin: 0 auto;
}

.stat-card {
    background: white;
    border-radius: 20px;
    padding: 40px 30px;
    text-align: center;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
    transition: all 0.3s ease;
    border: 2px solid transparent;
}

.stat-card:hover {
    transform: translateY(-10px);
    border-color: #3b82f6;
    box-shadow: 0 20px 60px rgba(59, 130, 246, 0.2);
}

.stat-icon {
    width: 80px;
    height: 80px;
    background: linear-gradient(135deg, #3b82f6, #1d4ed8);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 20px;
    font-size: 2rem;
    color: white;
}

.stat-number {
    font-size: 3rem;
    font-weight: 800;
    color: #1e293b;
    margin-bottom: 8px;
    display: block;
}

.stat-label {
    font-size: 1rem;
    color: #64748b;
    font-weight: 500;
}

/* Categories Section */
.categories-section {
    padding: 80px 0;
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    position: relative;
    overflow: hidden;
}

.categories-section::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -10%;
    width: 500px;
    height: 500px;
    background: radial-gradient(circle, rgba(59, 130, 246, 0.1) 0%, transparent 70%);
    border-radius: 50%;
    pointer-events: none;
}

.categories-section::after {
    content: '';
    position: absolute;
    bottom: -30%;
    left: -5%;
    width: 400px;
    height: 400px;
    background: radial-gradient(circle, rgba(147, 51, 234, 0.08) 0%, transparent 70%);
    border-radius: 50%;
    pointer-events: none;
}

/* Section Header */
.section-header {
    text-align: center;
    margin-bottom: 60px;
    position: relative;
    z-index: 1;
}

.section-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 20px;
    background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
    color: white;
    border-radius: 50px;
    font-size: 14px;
    font-weight: 600;
    margin-bottom: 20px;
    box-shadow: 0 4px 15px rgba(59, 130, 246, 0.3);
    animation: fadeInDown 0.6s ease;
}

.section-title {
    font-size: 42px;
    font-weight: 800;
    color: #1e293b;
    margin-bottom: 15px;
    line-height: 1.2;
    animation: fadeInUp 0.6s ease 0.1s both;
}

.section-subtitle {
    font-size: 18px;
    color: #64748b;
    max-width: 600px;
    margin: 0 auto;
    animation: fadeInUp 0.6s ease 0.2s both;
}

/* Categories Grid */
.categories-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 30px;
    position: relative;
    z-index: 1;
}

/* Category Card */
.category-card {
    position: relative;
    background: white;
    border-radius: 20px;
    overflow: hidden;
    text-decoration: none;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
    animation: fadeInUp 0.6s ease both;
    cursor: pointer;
}

.category-card::before {
    content: '';
    position: absolute;
    inset: 0;
    border-radius: 20px;
    padding: 2px;
    background: linear-gradient(135deg, #3b82f6, #8b5cf6);
    -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
    -webkit-mask-composite: xor;
    mask-composite: exclude;
    opacity: 0;
    transition: opacity 0.4s ease;
}

.category-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.12);
}

.category-card:hover::before {
    opacity: 1;
}

/* Image Wrapper */
.category-image-wrapper {
    position: relative;
    height: 220px;
    overflow: hidden;
}

.category-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.6s cubic-bezier(0.4, 0, 0.2, 1);
}

.category-card:hover .category-image {
    transform: scale(1.1);
}

.category-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(180deg, transparent 0%, rgba(0, 0, 0, 0.4) 100%);
    opacity: 0;
    transition: opacity 0.4s ease;
}

.category-card:hover .category-overlay {
    opacity: 1;
}

.category-badge-count {
    position: absolute;
    top: 15px;
    right: 15px;
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    padding: 8px 16px;
    border-radius: 50px;
    font-size: 14px;
    font-weight: 700;
    color: #3b82f6;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    transform: translateY(-5px);
    opacity: 0;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}

.category-card:hover .category-badge-count {
    transform: translateY(0);
    opacity: 1;
}

/* Category Content */
.category-content {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 25px;
    position: relative;
}

.category-icon {
    flex-shrink: 0;
    width: 50px;
    height: 50px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
    color: white;
    border-radius: 12px;
    font-size: 22px;
    transition: all 0.4s ease;
}

.category-card:hover .category-icon {
    transform: rotate(10deg) scale(1.1);
    box-shadow: 0 8px 20px rgba(59, 130, 246, 0.4);
}

.category-info {
    flex: 1;
}

.category-name {
    font-size: 20px;
    font-weight: 700;
    color: #1e293b;
    margin: 0 0 5px 0;
    transition: color 0.3s ease;
}

.category-card:hover .category-name {
    color: #3b82f6;
}

.category-description {
    font-size: 14px;
    color: #64748b;
    margin: 0;
}

.category-arrow {
    flex-shrink: 0;
    width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f1f5f9;
    border-radius: 50%;
    color: #3b82f6;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}

.category-card:hover .category-arrow {
    background: #3b82f6;
    color: white;
    transform: translateX(5px);
}

/* Empty State */
.empty-state {
    grid-column: 1 / -1;
    text-align: center;
    padding: 80px 20px;
}

.empty-icon {
    font-size: 64px;
    color: #cbd5e1;
    margin-bottom: 20px;
}

.empty-text {
    font-size: 20px;
    font-weight: 600;
    color: #475569;
    margin-bottom: 10px;
}

.empty-subtext {
    font-size: 16px;
    color: #94a3b8;
}

/* Categories Footer */
.categories-footer {
    text-align: center;
    margin-top: 60px;
    position: relative;
    z-index: 1;
}

.btn-view-all-categories {
    display: inline-flex;
    align-items: center;
    gap: 12px;
    padding: 16px 40px;
    background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
    color: white;
    font-size: 16px;
    font-weight: 600;
    border-radius: 50px;
    text-decoration: none;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 4px 15px rgba(59, 130, 246, 0.3);
    position: relative;
    overflow: hidden;
}

.btn-view-all-categories::before {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
    opacity: 0;
    transition: opacity 0.4s ease;
}

.btn-view-all-categories:hover::before {
    opacity: 1;
}

.btn-view-all-categories:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(59, 130, 246, 0.4);
}

.btn-view-all-categories span,
.btn-view-all-categories i {
    position: relative;
    z-index: 1;
}

.btn-view-all-categories i {
    transition: transform 0.4s ease;
}

.btn-view-all-categories:hover i {
    transform: translateX(5px);
}

/* Animations */
@keyframes fadeInDown {
    from {
        opacity: 0;
        transform: translateY(-20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Responsive Design */
@media (max-width: 1024px) {
    .categories-grid {
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 25px;
    }
}

@media (max-width: 768px) {
    .categories-section {
        padding: 60px 0;
    }
    
    .section-title {
        font-size: 32px;
    }
    
    .section-subtitle {
        font-size: 16px;
    }
    
    .categories-grid {
        grid-template-columns: 1fr;
        gap: 20px;
    }
    
    .category-image-wrapper {
        height: 200px;
    }
    
    .category-content {
        padding: 20px;
    }
    
    .btn-view-all-categories {
        padding: 14px 32px;
        font-size: 15px;
    }
}

@media (max-width: 480px) {
    .section-title {
        font-size: 28px;
    }
    
    .category-name {
        font-size: 18px;
    }
    
    .category-image-wrapper {
        height: 180px;
    }
}

/* Products Section */
.products-section {
    padding: 100px 0;
    background: linear-gradient(180deg, #f8f9fa 0%, #e9ecef 100%);
}

.products-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 30px;
    margin-top: 60px;
}

.product-card {
    background: white;
    border-radius: 20px;
    overflow: hidden;
    transition: all 0.4s ease;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
    display: flex;
    flex-direction: column;
    height: 100%;
}

.product-card:hover {
    transform: translateY(-10px);
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
}

.product-image-wrapper {
    position: relative;
    height: 250px;
    background: #f8f9fa;
    overflow: hidden;
}

.product-image {
    width: 100%;
    height: 100%;
    object-fit: contain;
    padding: 20px;
    transition: transform 0.4s ease;
}

.product-card:hover .product-image {
    transform: scale(1.05);
}

.product-badge {
    position: absolute;
    top: 15px;
    right: 15px;
    background: linear-gradient(135deg, #22c55e, #16a34a);
    color: white;
    padding: 6px 15px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
}

.product-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.7);
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: opacity 0.3s ease;
    gap: 10px;
}

.product-card:hover .product-overlay {
    opacity: 1;
}

.overlay-btn {
    padding: 10px 20px;
    border-radius: 25px;
    border: none;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.overlay-btn-primary {
    background: linear-gradient(135deg, #3b82f6, #1d4ed8);
    color: white;
}

.overlay-btn-primary:hover {
    transform: scale(1.05);
    color: white;
}

.product-body {
    padding: 25px;
    flex-grow: 1;
    display: flex;
    flex-direction: column;
}

.product-brand {
    color: #3b82f6;
    font-size: 0.85rem;
    font-weight: 600;
    text-transform: uppercase;
    margin-bottom: 8px;
}

.product-name {
    font-size: 1.3rem;
    font-weight: 700;
    color: #1e293b;
    margin-bottom: 15px;
    line-height: 1.3;
}

.product-price {
    font-size: 2rem;
    font-weight: 800;
    color: #3b82f6;
    margin-top: auto;
    margin-bottom: 15px;
}

.product-actions {
    display: flex;
    gap: 10px;
}

.product-btn {
    flex: 1;
    padding: 12px;
    border-radius: 10px;
    border: none;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.product-btn-primary {
    background: linear-gradient(135deg, #3b82f6, #1d4ed8);
    color: white;
}

.product-btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 20px rgba(59, 130, 246, 0.4);
    color: white;
}

/* Services Section */
.services-section {
    padding: 100px 0;
    background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
    color: white;
}

.services-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 30px;
    margin-top: 60px;
}

.service-card {
    background: rgba(255, 255, 255, 0.05);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 20px;
    padding: 40px;
    text-align: center;
    transition: all 0.4s ease;
}

.service-card:hover {
    transform: translateY(-10px);
    background: rgba(255, 255, 255, 0.1);
    border-color: rgba(59, 130, 246, 0.5);
}

.service-icon {
    width: 80px;
    height: 80px;
    background: linear-gradient(135deg, #3b82f6, #1d4ed8);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 25px;
    font-size: 2rem;
}

.service-title {
    font-size: 1.5rem;
    font-weight: 700;
    margin-bottom: 15px;
}

.service-description {
    color: #cbd5e1;
    line-height: 1.7;
    margin-bottom: 20px;
}

.service-link {
    color: #3b82f6;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.service-link:hover {
    color: #60a5fa;
    transform: translateX(5px);
}

/* Brands Section */
.brands-section {
    padding: 80px 0;
    background: white;
}

.brands-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 30px;
    margin-top: 60px;
}

.brand-card {
    background: #f8f9fa;
    border-radius: 15px;
    padding: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
    height: 120px;
    transition: all 0.3s ease;
    border: 2px solid transparent;
}

.brand-card:hover {
    background: white;
    border-color: #3b82f6;
    transform: translateY(-5px);
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
}

.brand-name {
    font-size: 1.3rem;
    font-weight: 700;
    color: #1e293b;
}

/* Why Choose Us */
.why-choose-section {
    padding: 100px 0;
    background: linear-gradient(180deg, #f8f9fa 0%, #ffffff 100%);
}

.features-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 30px;
    margin-top: 60px;
}

.feature-card {
    text-align: center;
    padding: 30px;
    background: white;
    border-radius: 15px;
    transition: all 0.3s ease;
    box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
}

.feature-card:hover {
    transform: translateY(-10px);
    box-shadow: 0 15px 40px rgba(0, 0, 0, 0.1);
}

.feature-icon-wrapper {
    width: 70px;
    height: 70px;
    background: linear-gradient(135deg, #3b82f6, #1d4ed8);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 20px;
    font-size: 1.8rem;
    color: white;
}

.feature-title {
    font-size: 1.2rem;
    font-weight: 700;
    color: #1e293b;
    margin-bottom: 10px;
}

.feature-description {
    font-size: 0.95rem;
    color: #64748b;
    line-height: 1.6;
}

/* CTA Section */
.cta-section {
    padding: 100px 0;
    background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
    color: white;
    text-align: center;
    position: relative;
    overflow: hidden;
}

.cta-section::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: 
        radial-gradient(circle at 30% 40%, rgba(255, 255, 255, 0.1) 0%, transparent 50%),
        radial-gradient(circle at 70% 60%, rgba(255, 255, 255, 0.1) 0%, transparent 50%);
}

.cta-content {
    position: relative;
    z-index: 1;
}

.cta-title {
    font-size: 3rem;
    font-weight: 800;
    margin-bottom: 20px;
}

.cta-subtitle {
    font-size: 1.3rem;
    margin-bottom: 40px;
    opacity: 0.95;
}

.cta-buttons {
    display: flex;
    gap: 20px;
    justify-content: center;
    flex-wrap: wrap;
}

.cta-btn {
    padding: 18px 45px;
    font-size: 1.1rem;
    font-weight: 600;
    border-radius: 50px;
    transition: all 0.3s ease;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 10px;
}

.cta-btn-white {
    background: white;
    color: #3b82f6;
}

.cta-btn-white:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
    color: #3b82f6;
}

.cta-btn-outline {
    background: transparent;
    color: white;
    border: 2px solid white;
}

.cta-btn-outline:hover {
    background: white;
    color: #3b82f6;
    transform: translateY(-3px);
}

/* Animations */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes fadeInDown {
    from {
        opacity: 0;
        transform: translateY(-30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Responsive Design */
@media (max-width: 1200px) {
    .products-grid,
    .categories-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 992px) {
    .carousel-title {
        font-size: 3rem;
    }
    
    .carousel-subtitle {
        font-size: 1.2rem;
    }
    
    .stats-container,
    .services-grid,
    .features-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .brands-grid {
        grid-template-columns: repeat(3, 1fr);
    }
}

@media (max-width: 768px) {
    .hero-carousel-section {
        height: 80vh;
        min-height: 500px;
    }
    
    .carousel-title {
        font-size: 2.5rem;
    }
    
    .carousel-subtitle {
        font-size: 1.1rem;
    }
    
    .carousel-buttons {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .carousel-btn {
        width: 100%;
        justify-content: center;
    }
    
    .hero-carousel-section .carousel-control-prev,
    .hero-carousel-section .carousel-control-next {
        width: 50px;
        height: 50px;
    }
    
    .categories-grid {
        grid-template-columns: 1fr;
        gap: 20px;
    }
    
    .category-card {
        height: 250px;
    }
    
    .hero-carousel-section .carousel-control-prev {
        left: 15px;
    }
    
    .hero-carousel-section .carousel-control-next {
        right: 15px;
    }
    
    .stats-container,
    .categories-grid,
    .products-grid,
    .services-grid,
    .features-grid {
        grid-template-columns: 1fr;
    }
    
    .brands-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .cta-title {
        font-size: 2rem;
    }
}

@media (max-width: 576px) {
    .hero-carousel-section {
        height: 70vh;
        min-height: 400px;
    }
    
    .carousel-title {
        font-size: 2rem;
    }
    
    .categories-grid {
        grid-template-columns: 1fr;
        gap: 15px;
    }
    
    .category-card {
        height: 200px;
    }
    
    .carousel-subtitle {
        font-size: 1rem;
    }
    
    .carousel-badge {
        font-size: 0.8rem;
        padding: 6px 15px;
    }
    
    .carousel-btn {
        padding: 12px 30px;
        font-size: 1rem;
    }
}
</style>

<!-- Hero Carousel Section -->
<section class="hero-carousel-section">
    <div id="heroCarousel" class="carousel slide" data-bs-ride="carousel" data-bs-interval="5000">
        <!-- Carousel Indicators -->
        <div class="carousel-indicators">
            <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="0" class="active" aria-current="true" aria-label="Slide 1"></button>
            <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="1" aria-label="Slide 2"></button>
            <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="2" aria-label="Slide 3"></button>
        </div>

        <!-- Carousel Inner -->
        <div class="carousel-inner">
            <!-- Slide 1: Premium AC Solutions -->
            <div class="carousel-item active">
                <img src="<?php echo IMG_URL; ?>/slider11.png" class="d-block w-100" alt="Premium AC Solutions" loading="eager" onerror="this.src='<?php echo IMG_URL; ?>/placeholder-product.png'" style="opacity: 1; display: block; visibility: visible;">
                <div class="carousel-overlay">
                    <div class="container">
                        <div class="row align-items-center h-100">
                            <div class="col-lg-8">
                                <div class="carousel-content">
                                    <span class="carousel-badge">
                                        <i class="fas fa-star me-2"></i>Trusted Since 1995
                                    </span>
                                    <h1 class="carousel-title">
                                        Perfect Air Conditioning Solutions
                                    </h1>
                                    <p class="carousel-subtitle">
                                        Experience ultimate comfort with our energy-efficient AC systems. Expert installation, 
                                        comprehensive maintenance, and 24/7 support for homes and businesses.
                                    </p>
                                    <div class="carousel-buttons">
                                        <a href="<?php echo USER_URL; ?>/products/" class="carousel-btn carousel-btn-primary">
                                            <i class="fas fa-shopping-bag"></i> Shop Products
                                        </a>
                                        <a href="<?php echo USER_URL; ?>/services/" class="carousel-btn carousel-btn-secondary">
                                            <i class="fas fa-tools"></i> Our Services
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Slide 2: Installation Services -->
            <div class="carousel-item">
                <img src="<?php echo IMG_URL; ?>/slider2.png" class="d-block w-100" alt="Professional Installation" loading="eager" onerror="this.src='<?php echo IMG_URL; ?>/placeholder-product.png'" style="opacity: 1; display: block; visibility: visible;">
                <div class="carousel-overlay">
                    <div class="container">
                        <div class="row align-items-center h-100">
                            <div class="col-lg-8">
                                <div class="carousel-content">
                                    <span class="carousel-badge">
                                        <i class="fas fa-wrench me-2"></i>Professional Service
                                    </span>
                                    <h1 class="carousel-title">
                                        Expert Installation & Service
                                    </h1>
                                    <p class="carousel-subtitle">
                                        Our certified technicians provide professional AC installation and maintenance services 
                                        with comprehensive warranty coverage and 24/7 emergency support.
                                    </p>
                                    <div class="carousel-buttons">
                                        <a href="<?php echo USER_URL; ?>/services/" class="carousel-btn carousel-btn-primary">
                                            <i class="fas fa-calendar-check"></i> Book Service
                                        </a>
                                        <a href="<?php echo PUBLIC_URL; ?>/pages/contact.php" class="carousel-btn carousel-btn-secondary">
                                            <i class="fas fa-phone"></i> Get Quote
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Slide 3: Energy Efficiency -->
            <div class="carousel-item">
                <img src="<?php echo IMG_URL; ?>/slider3.png" class="d-block w-100" alt="Energy Efficient AC" loading="eager" onerror="this.src='<?php echo IMG_URL; ?>/placeholder-product.png'" style="opacity: 1; display: block; visibility: visible;">
                <div class="carousel-overlay">
                    <div class="container">
                        <div class="row align-items-center h-100">
                            <div class="col-lg-8">
                                <div class="carousel-content">
                                    <span class="carousel-badge">
                                        <i class="fas fa-bolt me-2"></i>Energy Efficient
                                    </span>
                                    <h1 class="carousel-title">
                                        Smart & Energy Efficient
                                    </h1>
                                    <p class="carousel-subtitle">
                                        Discover our range of inverter And Fixspeed AC systems that deliver superior cooling performance 
                                        while reducing energy consumption and electricity bills.
                                    </p>
                                    <div class="carousel-buttons">
                                        <a href="<?php echo USER_URL; ?>/products/?feature=inverter" class="carousel-btn carousel-btn-primary">
                                            <i class="fas fa-leaf"></i> View Inverter ACs
                                        </a>
                                        <a href="<?php echo USER_URL; ?>/products/" class="carousel-btn carousel-btn-secondary">
                                            <i class="fas fa-search"></i> Browse All
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Carousel Controls -->
        <button class="carousel-control-prev" type="button" data-bs-target="#heroCarousel" data-bs-slide="prev">
            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Previous</span>
        </button>
        <button class="carousel-control-next" type="button" data-bs-target="#heroCarousel" data-bs-slide="next">
            <span class="carousel-control-next-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Next</span>
        </button>
    </div>
</section>

<!-- Stats Section -->
<section class="stats-section">
    <div class="container">
        <div class="stats-container">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-history"></i>
                </div>
                <span class="stat-number">30+</span>
                <span class="stat-label">Years Experience</span>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-users"></i>
                </div>
                <span class="stat-number"><?= isset($stats['total_customers']) ? number_format($stats['total_customers']) : '30000K+' ?></span>
                <span class="stat-label">Happy Customers</span>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-box"></i>
                </div>
                <span class="stat-number"><?= isset($stats['total_products']) ? number_format($stats['total_products']) : '200+' ?></span>
                <span class="stat-label">Products</span>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-headset"></i>
                </div>
                <span class="stat-number">24/7</span>
                <span class="stat-label">Support</span>
            </div>
        </div>
    </div>
</section>

<!-- Enhanced Categories Section -->
<section class="categories-section">
    <div class="container">
        <div class="section-header">
            <span class="section-badge">
                <i class="fas fa-th-large"></i> Browse Categories
            </span>
            <h2 class="section-title">Shop by Category</h2>
            <p class="section-subtitle">Find the perfect AC solution for your space</p>
        </div>
        
        <div class="categories-grid">
            <?php if (!empty($categories)): ?>
                <?php foreach (array_slice($categories, 0, 6) as $index => $category): ?>
                <a href="<?php echo USER_URL; ?>/products/?category=<?= $category['id'] ?>" 
                   class="category-card" 
                   data-product-count="<?= $category['product_count'] ?>" 
                   style="animation-delay: <?= $index * 0.1 ?>s">
                    <?php
                    // Image path logic - improved handling
                    $imagePath = '';
                    
                    if (!empty($category['image'])) {
                        // If it's a full URL
                        if (strpos($category['image'], 'http') === 0) {
                            // Extract path from URL
                            $urlParts = parse_url($category['image']);
                            $fullPath = $urlParts['path'];
                            
                            // Remove /ac base path if present
                            $path = preg_replace('#^/ac#', '', $fullPath);
                            
                            // Remove /public prefix if present
                            $path = preg_replace('#^/public#', '', $path);
                            
                            // Extract the part after /img/uploads/
                            if (preg_match('#/img/uploads/(.+)$#', $path, $matches)) {
                                $relativePath = $matches[1];
                                $imagePath = BASE_URL . '/public/image.php?file=' . urlencode($relativePath);
                            } else {
                                // Fallback: use original URL
                                $imagePath = $category['image'];
                            }
                        } else {
                            // It's a relative path or filename
                            // Normalize path separators
                            $normalizedPath = str_replace('\\', '/', $category['image']);
                            
                            // If it contains categories/ or starts with it
                            if (strpos($normalizedPath, 'categories/') !== false) {
                                // Remove any leading paths, keep only categories/filename
                                if (strpos($normalizedPath, '/categories/') !== false) {
                                    $parts = explode('/categories/', $normalizedPath);
                                    $normalizedPath = 'categories/' . end($parts);
                                }
                                $imagePath = BASE_URL . '/public/image.php?file=' . urlencode($normalizedPath);
                            } elseif (strpos($normalizedPath, 'img/') === 0 || strpos($normalizedPath, 'uploads/') === 0) {
                                // Direct path to img or uploads folder
                                $imagePath = BASE_URL . '/public/' . $normalizedPath;
                            } else {
                                // Just a filename - try in uploads
                                $imagePath = BASE_URL . '/public/image.php?file=' . urlencode($normalizedPath);
                            }
                        }
                    }
                    
                    // Final fallback to static images based on category name
                    if (empty($imagePath)) {
                        $imageSlug = strtolower(str_replace(' ', '-', trim($category['name'])));
                        
                        $imageMapping = [
                            'residential-ac' => 'residential-ac',
                            'commercial-ac' => 'commercial-ac',
                            'split-ac' => 'residential-ac',
                            'window-ac' => 'residential-ac',
                            'cassette-ac' => 'cassette-ac',
                            'cassate' => 'cassette-ac',
                            'ductless-ac' => 'cassette-ac',
                            'vrf-system' => 'commercial-ac',
                            'packaged-ac' => 'commercial-ac'
                        ];
                        
                        $imageSlug = $imageMapping[$imageSlug] ?? 'residential-ac';
                        $imagePath = IMG_URL . "/{$imageSlug}.png";
                    }
                    
                    // Dynamic icon based on category name
                    $categoryLower = strtolower($category['name']);
                    $iconClass = 'fa-snowflake';
                    if (strpos($categoryLower, 'commercial') !== false) {
                        $iconClass = 'fa-building';
                    } elseif (strpos($categoryLower, 'residential') !== false) {
                        $iconClass = 'fa-home';
                    } elseif (strpos($categoryLower, 'split') !== false) {
                        $iconClass = 'fa-wind';
                    } elseif (strpos($categoryLower, 'window') !== false) {
                        $iconClass = 'fa-window-maximize';
                    } elseif (strpos($categoryLower, 'cassette') !== false) {
                        $iconClass = 'fa-th';
                    } elseif (strpos($categoryLower, 'vrf') !== false || strpos($categoryLower, 'packaged') !== false) {
                        $iconClass = 'fa-cogs';
                    }
                    ?>
                    
                    <div class="category-image-wrapper">
                        <?php
                        // Use a consistent cache-busting value for all images on this page load
                        // This prevents timing issues with multiple concurrent requests
                        static $cacheBuster = null;
                        if ($cacheBuster === null) {
                            $cacheBuster = time();
                        }
                        $imageUrl = $imagePath . (strpos($imagePath, '?') !== false ? '&' : '?') . 'v=' . $cacheBuster;
                        $fallbackUrl = IMG_URL . '/placeholder-product.png?v=' . $cacheBuster;
                        ?>
                        <img src="<?= htmlspecialchars($imageUrl) ?>" 
                             alt="<?= htmlspecialchars($category['name']) ?>" 
                             class="category-image"
                             loading="lazy"
                             data-src="<?= htmlspecialchars($imageUrl) ?>"
                             data-fallback="<?= htmlspecialchars($fallbackUrl) ?>"
                             onerror="this.onerror=null; if(this.src !== this.dataset.fallback) { this.src = this.dataset.fallback; } else { this.style.display='none'; }">
                        <div class="category-overlay"></div>
                        <div class="category-badge-count">
                            <span><?= $category['product_count'] ?></span>
                        </div>
                    </div>
                    
                    <div class="category-content">
                        <div class="category-icon">
                            <i class="fas <?= $iconClass ?>"></i>
                        </div>
                        <div class="category-info">
                            <h3 class="category-name"><?= htmlspecialchars($category['name']) ?></h3>
                            <p class="category-description">
                                Explore <?= $category['product_count'] ?> premium products
                            </p>
                        </div>
                        <div class="category-arrow">
                            <i class="fas fa-arrow-right"></i>
                        </div>
                    </div>
                </a>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-icon">
                        <i class="fas fa-box-open"></i>
                    </div>
                    <p class="empty-text">No categories available at the moment</p>
                    <p class="empty-subtext">Check back soon for updates</p>
                </div>
            <?php endif; ?>
        </div>
        
        <?php if (!empty($categories) && count($categories) > 6): ?>
        <div class="categories-footer">
            <a href="<?php echo USER_URL; ?>/products/" class="btn-view-all-categories">
                <span>View All <?= count($categories) ?> Categories</span>
                <i class="fas fa-arrow-right"></i>
            </a>
        </div>
        <?php endif; ?>
    </div>
</section>

<!-- Featured Products Section -->
<section class="products-section">
    <div class="container">
        <div class="section-header">
            <span class="section-badge">Best Sellers</span>
            <h2 class="section-title">Featured Products</h2>
            <p class="section-subtitle">Handpicked premium air conditioning solutions</p>
        </div>
        
        <div class="products-grid">
            <?php if (!empty($featured_products)): ?>
                <?php foreach (array_slice($featured_products, 0, 6) as $product): ?>
                <div class="product-card enhanced-product-card" data-product-id="<?= $product['id'] ?>">
                    <div class="product-image-wrapper">
                        <img src="<?php echo BASE_URL; ?>/public/image.php?file=<?= urlencode($product['product_image']) ?>" 
                             alt="<?= htmlspecialchars($product['product_name']) ?>" 
                             class="product-image"
                             loading="eager"
                             onerror="this.src='<?php echo IMG_URL; ?>/placeholder-product.png'">
                        <?php if ($product['inverter'] == 'Yes'): ?>
                        <span class="product-badge">
                            <i class="fas fa-bolt me-1"></i>Inverter
                        </span>
                        <?php endif; ?>
                        <div class="product-overlay">
                            <a href="<?php echo USER_URL; ?>/products/details.php?id=<?= $product['id'] ?>" class="overlay-btn overlay-btn-primary">
                                <i class="fas fa-eye"></i> Quick View
                            </a>
                        </div>
                    </div>
                    <div class="product-body">
                        <div class="product-brand"><?= htmlspecialchars($product['brand_name']) ?></div>
                        <h3 class="product-name"><?= htmlspecialchars($product['product_name']) ?></h3>
                        <div class="product-price">₹<?= number_format($product['price'], 0) ?></div>
                        <div class="product-actions">
                            <a href="<?php echo USER_URL; ?>/products/details.php?id=<?= $product['id'] ?>" class="product-btn product-btn-primary">
                                <i class="fas fa-shopping-cart"></i> View Details
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12 text-center py-5">
                    <p class="text-muted">No products available</p>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="text-center mt-5">
            <a href="<?php echo USER_URL; ?>/products/" class="hero-btn hero-btn-primary">
                View All Products <i class="fas fa-arrow-right ms-2"></i>
            </a>
        </div>
    </div>
</section>

<!-- Services Section -->
<section class="services-section">
    <div class="container">
        <div class="section-header">
            <span class="section-badge">What We Offer</span>
            <h2 class="section-title text-white">Our Services</h2>
            <p class="section-subtitle" style="color: #cbd5e1;">Comprehensive AC solutions for all your needs</p>
        </div>
        
        <div class="services-grid">
            <div class="service-card">
                <div class="service-icon">
                    <i class="fas fa-wrench"></i>
                </div>
                    <h3 class="service-title">Installation</h3>
                <p class="service-description">
                    Professional AC installation by certified technicians with comprehensive warranty coverage.
                </p>
                <a href="<?php echo USER_URL; ?>/services/" class="service-link">
                    Learn More <i class="fas fa-arrow-right"></i>
                </a>
            </div>
            
            <div class="service-card">
                <div class="service-icon">
                    <i class="fas fa-tools"></i>
                </div>
                <h3 class="service-title">Repair & Service</h3>
                <p class="service-description">
                    Quick and efficient repair services with 24/7 emergency support for urgent issues.
                </p>
                <a href="<?php echo USER_URL; ?>/services/" class="service-link">
                    Learn More <i class="fas fa-arrow-right"></i>
                </a>
            </div>
            
            <div class="service-card">
                <div class="service-icon">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <h3 class="service-title">AMC Plans</h3>
                <p class="service-description">
                    Annual maintenance contracts for hassle-free year-round AC care and priority support.
                </p>
                <a href="<?php echo USER_URL; ?>/services/#amc-plans" class="service-link">
                    Learn More <i class="fas fa-arrow-right"></i>
                </a>
            </div>
        </div>
    </div>
</section>

<!-- Brands Section -->
<?php if (!empty($brands)): ?>
<section class="brands-section">
    <div class="container">
        <div class="section-header">
            <span class="section-badge">Authorized Dealers</span>
            <h2 class="section-title">Top Brands We Carry</h2>
            <p class="section-subtitle">Premium quality from World Famous Brands manufacturers</p>
        </div>
        
        <div class="brands-grid">
            <?php foreach (array_slice($brands, 0, 8) as $brand): ?>
            <div class="brand-card">
                <h4 class="brand-name"><?= htmlspecialchars($brand['name']) ?></h4>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Why Choose Us Section -->
<section class="why-choose-section">
    <div class="container">
        <div class="section-header">
            <span class="section-badge">Why Choose Us</span>
            <h2 class="section-title">What Makes Us Different</h2>
            <p class="section-subtitle">Excellence in every aspect of service</p>
        </div>
        
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon-wrapper">
                    <i class="fas fa-certificate"></i>
                </div>
                <h3 class="feature-title">30+ Years Legacy</h3>
                <p class="feature-description">Trusted expertise since 1995 with proven track record</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon-wrapper">
                    <i class="fas fa-user-shield"></i>
                </div>
                <h3 class="feature-title">Expert Team</h3>
                <p class="feature-description">Certified technicians with extensive training</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon-wrapper">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <h3 class="feature-title">Quality Guarantee</h3>
                <p class="feature-description">Comprehensive warranty on all products and services</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon-wrapper">
                    <i class="fas fa-headphones-alt"></i>
                </div>
                <h3 class="feature-title">24/7 Support</h3>
                <p class="feature-description">Round-the-clock assistance for all your needs</p>
            </div>
        </div>
    </div>
</section>

<!-- FAQ Section -->
<section class="faq-section">
    <div class="container">
        <div class="section-header">
            <span class="section-badge">Got Questions?</span>
            <h2 class="section-title">Frequently Asked Questions</h2>
            <p class="section-subtitle">Find answers to common questions about our AC products and services</p>
        </div>
        
        <div class="faq-container">
            <div class="faq-item">
                <button class="faq-question">
                    What types of AC systems do you offer?
                    <i class="fas fa-chevron-down faq-icon"></i>
                </button>
                <div class="faq-answer">
                    <p>We offer a comprehensive range of air conditioning systems including split ACs, window ACs, cassette ACs, VRF systems, and commercial AC solutions. All our products come from trusted brands and include professional installation services.</p>
                </div>
            </div>
            
            <div class="faq-item">
                <button class="faq-question">
                    Do you provide installation services?
                    <i class="fas fa-chevron-down faq-icon"></i>
                </button>
                <div class="faq-answer">
                    <p>Yes, we provide professional installation services by certified technicians. Installation is included with most of our AC purchases, and we offer comprehensive warranty coverage on both products and installation work.</p>
                </div>
            </div>
            
            <div class="faq-item">
                <button class="faq-question">
                    What warranty do you offer on your products?
                    <i class="fas fa-chevron-down faq-icon"></i>
                </button>
                <div class="faq-answer">
                    <p>We offer comprehensive warranty coverage ranging from 1-5 years depending on the product. This includes manufacturer warranty plus our additional service warranty. We also provide AMC (Annual Maintenance Contract) plans for ongoing support.</p>
                </div>
            </div>
            
            <div class="faq-item">
                <button class="faq-question">
                    How do I choose the right AC capacity for my room?
                    <i class="fas fa-chevron-down faq-icon"></i>
                </button>
                <div class="faq-answer">
                    <p>AC capacity depends on room size, ceiling height, insulation, and local climate. As a general rule: 1 ton for 100-120 sq ft, 1.5 ton for 120-180 sq ft, 2 ton for 180-250 sq ft. Our experts can help you choose the perfect capacity during consultation.</p>
                </div>
            </div>
            
            <div class="faq-item">
                <button class="faq-question">
                    Do you offer maintenance and repair services?
                    <i class="fas fa-chevron-down faq-icon"></i>
                </button>
                <div class="faq-answer">
                    <p>Yes, we provide comprehensive maintenance and repair services including regular cleaning, filter replacement, gas refilling, and emergency repairs. We offer 24/7 support and have AMC plans for hassle-free maintenance.</p>
                </div>
            </div>
            
            <div class="faq-item">
                <button class="faq-question">
                    What payment options do you accept?
                    <i class="fas fa-chevron-down faq-icon"></i>
                </button>
                <div class="faq-answer">
                    <p>We accept all major credit/debit cards, net banking, UPI payments, and EMI options. We also offer flexible payment plans and financing options to make your AC purchase more affordable.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="cta-section">
    <div class="container">
        <div class="cta-content">
            <h2 class="cta-title">Ready for Perfect Cooling?</h2>
            <p class="cta-subtitle">
                Get expert consultation and find the ideal AC solution for your space today
            </p>
            <div class="cta-buttons">
                <a href="<?php echo PUBLIC_URL; ?>/pages/contact.php" class="cta-btn cta-btn-white">
                    <i class="fas fa-phone"></i> Get Free Quote
                </a>
                <a href="<?php echo USER_URL; ?>/products/" class="cta-btn cta-btn-outline">
                    <i class="fas fa-shopping-bag"></i> Shop Now
                </a>
            </div>
        </div>
    </div>
</section>

<script>
// Smooth animations on scroll
document.addEventListener('DOMContentLoaded', function() {
    // Intersection Observer for scroll animations
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -100px 0px'
    };
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, observerOptions);
    
    // Observe all cards and sections
    document.querySelectorAll('.stat-card, .category-card, .product-card, .service-card, .feature-card, .brand-card').forEach(el => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(30px)';
        el.style.transition = 'all 0.6s ease';
        observer.observe(el);
    });
    
    // Animate stats numbers
    const animateValue = (element, start, end, duration) => {
        const range = end - start;
        const increment = range / (duration / 16);
        let current = start;
        
        const timer = setInterval(() => {
            current += increment;
            if (current >= end) {
                element.textContent = end + (element.textContent.includes('+') ? '+' : '');
                clearInterval(timer);
            } else {
                element.textContent = Math.floor(current) + (element.textContent.includes('+') ? '+' : '');
            }
        }, 16);
    };
    
    // Trigger stat animation when visible
    const statObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const number = entry.target;
                const endValue = parseInt(number.textContent.replace(/\D/g, ''));
                animateValue(number, 0, endValue, 2000);
                statObserver.unobserve(entry.target);
            }
        });
    }, observerOptions);
    
    document.querySelectorAll('.stat-number').forEach(stat => {
        statObserver.observe(stat);
    });
});


// Homepage loader management
function hideHomepageLoader() {
    const loader = document.getElementById('pageLoader');
    if (loader) {
        loader.classList.add('fade-out');
        setTimeout(() => {
            loader.remove();
        }, 500);
    }
}

// Hide loader when page is fully loaded
window.addEventListener('load', () => {
    setTimeout(() => {
        hideHomepageLoader();
    }, 1000); // Show loader for at least 1 second
});

// Also hide loader after a maximum time
setTimeout(() => {
    hideHomepageLoader();
}, 5000); // Maximum 5 seconds
</script>

<!-- Carousel initialization is handled in homepage-enhancements.js -->

<!-- Expose PHP constants to JavaScript -->
<script>
    // Expose application constants to JavaScript
    window.BASE_URL = '<?php echo BASE_URL; ?>';
    window.PUBLIC_URL = '<?php echo PUBLIC_URL; ?>';
    window.IMG_URL = '<?php echo IMG_URL; ?>';
    window.CSS_URL = '<?php echo CSS_URL; ?>';
    window.JS_URL = '<?php echo JS_URL; ?>';
    window.USER_URL = '<?php echo USER_URL; ?>';
    <?php 
    // Calculate APP_BASE_PATH from BASE_URL
    $appBasePath = '';
    $baseUrlPath = parse_url(BASE_URL, PHP_URL_PATH);
    if ($baseUrlPath && $baseUrlPath !== '/') {
        $appBasePath = $baseUrlPath;
    }
    ?>
    window.APP_BASE_PATH = '<?php echo $appBasePath; ?>';
    window.APP_VERSION = '<?php echo APP_VERSION; ?>';
</script>

<!-- Include homepage enhancements JavaScript -->
<script src="<?php echo JS_URL; ?>/homepage-enhancements.js?v=<?php echo APP_VERSION; ?>"></script>

<!-- Debug carousel images -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(function() {
        const carouselItems = document.querySelectorAll('.carousel-item');
        console.log('Carousel Debug:');
        console.log('Number of carousel items:', carouselItems.length);
        
        carouselItems.forEach((item, index) => {
            const img = item.querySelector('img');
            if (img) {
                console.log(`Slide ${index + 1}:`);
                console.log('- Image src:', img.src);
                console.log('- Image complete:', img.complete);
                console.log('- Image naturalWidth:', img.naturalWidth);
                console.log('- Image naturalHeight:', img.naturalHeight);
                console.log('- Image display style:', window.getComputedStyle(img).display);
                console.log('- Image visibility:', window.getComputedStyle(img).visibility);
                console.log('- Image opacity:', window.getComputedStyle(img).opacity);
                console.log('- Image z-index:', window.getComputedStyle(img).zIndex);
                console.log('- Item class list:', item.classList.toString());
                console.log('- Item display style:', window.getComputedStyle(item).display);
                console.log('- Item visibility:', window.getComputedStyle(item).visibility);
                console.log('- Item opacity:', window.getComputedStyle(item).opacity);
            }
        });
        
        // Check carousel section height
        const carouselSection = document.querySelector('.hero-carousel-section');
        if (carouselSection) {
            console.log('Carousel section height:', window.getComputedStyle(carouselSection).height);
            console.log('Carousel section min-height:', window.getComputedStyle(carouselSection).minHeight);
        }
    }, 2000);
});
</script>

<?php include INCLUDES_PATH . '/templates/footer.php'; ?>

