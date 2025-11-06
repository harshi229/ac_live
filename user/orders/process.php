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

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    echo "You must be logged in to add items to the cart.";
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize and validate input
    $product_id = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
    $quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);
    $user_id = $_SESSION['user_id']; // Get user ID from the session

    // Check if inputs are valid
    if ($product_id === false || $quantity === false || $quantity <= 0) {
        echo "Invalid product ID or quantity.";
        exit();
    }

    try {
        // Begin a transaction
        $pdo->beginTransaction();

        // Check current stock of the product
        $check_stock = $pdo->prepare("SELECT stock FROM products WHERE id = ?");
        $check_stock->execute([$product_id]);
        $product = $check_stock->fetch(PDO::FETCH_ASSOC);

        // If the product doesn't exist or stock is insufficient
        if (!$product || $product['stock'] < $quantity) {
            echo "Insufficient stock available.";
            $pdo->rollBack(); // Rollback transaction on error
            exit();
        }

        // For Buy Now, we'll add to regular cart and redirect to checkout
        // First, clear any existing cart items for this user
        $clear_cart = $pdo->prepare("DELETE FROM cart WHERE user_id = ?");
        $clear_cart->execute([$user_id]);

        // Add the product to cart
        $add_to_cart = $pdo->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)");
        $add_to_cart->execute([$user_id, $product_id, $quantity]);

        // Commit the transaction
        $pdo->commit();

        // Redirect directly to checkout page
        echo "<script>window.location.href='checkout.php?buy_now=1';</script>";
        exit();

    } catch (Exception $e) {
        // Rollback transaction on error
        $pdo->rollBack();
        echo "An error occurred: " . $e->getMessage();
        exit();
    }
} else {
    // Handle non-POST requests
    echo "Invalid request method.";
}
?>

