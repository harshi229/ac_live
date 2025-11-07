<?php
/**
 * Test script to verify product query is working correctly
 * Run this to see what products are being returned
 */

require_once __DIR__ . '/includes/config/init.php';
require_once INCLUDES_PATH . '/classes/ProductQueryBuilder.php';

echo "<h2>Testing Product Query</h2>";

// Test 1: Check database values
echo "<h3>1. Database Values:</h3>";
$check_sql = "SELECT id, product_name, show_on_product_page, show_on_homepage, status 
              FROM products 
              WHERE status = 'active'";
$check_stmt = $pdo->query($check_sql);
$all_products = $check_stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<table border='1' cellpadding='5'>";
echo "<tr><th>ID</th><th>Product Name</th><th>Show on Product Page</th><th>Show on Homepage</th><th>Status</th></tr>";
foreach ($all_products as $p) {
    echo "<tr>";
    echo "<td>{$p['id']}</td>";
    echo "<td>{$p['product_name']}</td>";
    echo "<td>" . ($p['show_on_product_page'] ?? 'NULL') . "</td>";
    echo "<td>" . ($p['show_on_homepage'] ?? 'NULL') . "</td>";
    echo "<td>{$p['status']}</td>";
    echo "</tr>";
}
echo "</table>";

// Test 2: Test ProductQueryBuilder
echo "<h3>2. ProductQueryBuilder Results:</h3>";
$queryBuilder = new ProductQueryBuilder($pdo);
$queryBuilder->addProductPageFilter();
$queryBuilder->setPagination(1, 12);

$total = $queryBuilder->getTotalCount();
$products = $queryBuilder->getProducts();

echo "<p><strong>Total products found:</strong> $total</p>";
echo "<p><strong>Products returned:</strong> " . count($products) . "</p>";

if (count($products) > 0) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Product Name</th></tr>";
    foreach ($products as $p) {
        echo "<tr><td>{$p['id']}</td><td>{$p['product_name']}</td></tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: red;'><strong>No products found!</strong></p>";
    echo "<p>This is expected if all products have <code>show_on_product_page = 0</code></p>";
    echo "<p>To fix: Check the 'Show on Product Page' checkbox in admin panel, or run:</p>";
    echo "<pre>UPDATE products SET show_on_product_page = 1 WHERE status = 'active';</pre>";
}

// Test 3: Check if column exists
echo "<h3>3. Column Check:</h3>";
try {
    $col_check = $pdo->query("SHOW COLUMNS FROM products LIKE 'show_on_product_page'");
    if ($col_check->rowCount() > 0) {
        echo "<p style='color: green;'>✓ Column 'show_on_product_page' exists</p>";
    } else {
        echo "<p style='color: red;'>✗ Column 'show_on_product_page' does NOT exist</p>";
    }
} catch (PDOException $e) {
    echo "<p style='color: red;'>Error checking column: " . $e->getMessage() . "</p>";
}
?>

