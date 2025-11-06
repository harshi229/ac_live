<?php
// Configure secure session settings BEFORE starting session
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.gc_maxlifetime', 1800); // 30 minutes
ini_set('session.cookie_lifetime', 0); // Session cookie

// Only enable secure cookies for HTTPS (production)
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    ini_set('session.cookie_secure', 1);
} else {
    ini_set('session.cookie_secure', 0); // Disabled for localhost/HTTP
}

session_start();
// Database connection now in init.php
include INCLUDES_PATH . '/templates/header.php';

// Fetch the most recent order for the user
$order_query = $pdo->prepare("
    SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC LIMIT 1
");
$order_query->execute([$_SESSION['user_id']]);
$order = $order_query->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    echo "No recent order found.";
    exit();
}


?>

<!-- Custom Styles for Order Confirmation -->
<style>
    .confirmation-container {
        max-width: 600px;
        margin: 50px auto;
        padding: 20px;
    }
    .confirmation-card {
        width: 35rem;
        border-radius: 10px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }
</style>
<main>
<div class="container confirmation-container">
    <div class="card confirmation-card">
        <div class="card-body text-center">
            <h1 class="card-title">Order Confirmation</h1>
            <p class="lead">Thank you for your order!</p>
            <p class="text-muted">Your order ID is: <strong><?php echo $order['id']; ?></strong></p>
            <p>Total Price: <strong>₹<?php echo $order['total_price']; ?></strong></p>
            <p>Shipping to: <strong><?php echo htmlspecialchars($order['address']); ?></strong></p>
        </div>
        <div class="card-footer text-center">
            <a href="shop.php" class="btn btn-primary">Continue Shopping</a>
        </div>
    </div>
</div>

</main>
<?php
include INCLUDES_PATH . '/templates/footer.php';
?>

