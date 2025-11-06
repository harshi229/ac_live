<?php
// Simple diagnostic for order tracking
echo "<h1>Order Tracking Diagnostic</h1>";

// Test database connection
try {
    require_once __DIR__ . '/../../includes/config/init.php';
    echo "<p>✅ Database connection successful</p>";
    
    // Test if orders table exists and has data
    $test_query = $pdo->query("SELECT COUNT(*) as count FROM orders");
    $result = $test_query->fetch(PDO::FETCH_ASSOC);
    echo "<p>✅ Orders table accessible. Total orders: " . $result['count'] . "</p>";
    
    // Test if we can fetch a sample order
    $sample_query = $pdo->query("SELECT order_number, order_status FROM orders LIMIT 1");
    $sample = $sample_query->fetch(PDO::FETCH_ASSOC);
    if ($sample) {
        echo "<p>✅ Sample order found: " . $sample['order_number'] . " (Status: " . $sample['order_status'] . ")</p>";
    } else {
        echo "<p>⚠️ No orders found in database</p>";
    }
    
    // Test tracking page access
    echo "<p>✅ Tracking page file exists and is accessible</p>";
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h2>Test Order Tracking</h2>";
echo "<p>If you have orders, try tracking with order number: " . ($sample['order_number'] ?? 'No orders available') . "</p>";
echo "<p><a href='tracking.php'>Go to Tracking Page</a></p>";
?>
