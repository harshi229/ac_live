<?php
/**
 * Dynamic XML Sitemap Generator
 * Generates sitemap for all public pages and products
 */

require_once __DIR__ . '/includes/config/init.php';

// Set XML header
header('Content-Type: application/xml; charset=utf-8');

// Start XML output
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

// Helper function to output URL
function outputUrl($loc, $lastmod = null, $changefreq = 'monthly', $priority = '0.5') {
    $lastmod = $lastmod ?: date('Y-m-d');
    echo "  <url>\n";
    echo "    <loc>" . htmlspecialchars($loc) . "</loc>\n";
    echo "    <lastmod>" . $lastmod . "</lastmod>\n";
    echo "    <changefreq>" . $changefreq . "</changefreq>\n";
    echo "    <priority>" . $priority . "</priority>\n";
    echo "  </url>\n";
}

try {
    // Homepage (highest priority)
    outputUrl(BASE_URL . '/', date('Y-m-d'), 'daily', '1.0');
    
    // Static pages
    outputUrl(BASE_URL . '/public/pages/about.php', date('Y-m-d'), 'monthly', '0.8');
    outputUrl(BASE_URL . '/public/pages/contact.php', date('Y-m-d'), 'monthly', '0.8');
    outputUrl(BASE_URL . '/public/pages/privacy.php', date('Y-m-d'), 'yearly', '0.3');
    outputUrl(BASE_URL . '/public/pages/terms.php', date('Y-m-d'), 'yearly', '0.3');
    
    // Products page
    outputUrl(BASE_URL . '/user/products/', date('Y-m-d'), 'daily', '0.9');
    
    // Services page
    outputUrl(BASE_URL . '/user/services/', date('Y-m-d'), 'monthly', '0.8');
    
    // Individual products
    $products_stmt = $pdo->query("SELECT id, product_name, created_at FROM products WHERE status = 'active' ORDER BY created_at DESC");
    while ($product = $products_stmt->fetch(PDO::FETCH_ASSOC)) {
        $product_url = BASE_URL . '/user/products/details.php?id=' . $product['id'];
        $lastmod = date('Y-m-d', strtotime($product['created_at']));
        outputUrl($product_url, $lastmod, 'weekly', '0.7');
    }
    
    // Categories
    $categories_stmt = $pdo->query("SELECT id, name, created_at FROM categories WHERE status = 'active'");
    while ($category = $categories_stmt->fetch(PDO::FETCH_ASSOC)) {
        $category_url = BASE_URL . '/user/products/?category=' . $category['id'];
        $lastmod = date('Y-m-d', strtotime($category['created_at']));
        outputUrl($category_url, $lastmod, 'weekly', '0.6');
    }
    
    // Brands
    $brands_stmt = $pdo->query("SELECT id, name FROM brands WHERE status = 'active'");
    while ($brand = $brands_stmt->fetch(PDO::FETCH_ASSOC)) {
        $brand_url = BASE_URL . '/user/products/?brand=' . $brand['id'];
        outputUrl($brand_url, date('Y-m-d'), 'monthly', '0.5');
    }

} catch (PDOException $e) {
    error_log("Sitemap generation error: " . $e->getMessage());
}

echo '</urlset>';
?>
