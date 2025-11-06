<?php
// Disable error reporting for clean JSON output
error_reporting(0);
ini_set('display_errors', 0);

// Include necessary files
require_once __DIR__ . '/../../includes/config/init.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    // Check if this is an AJAX request
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'You must be logged in to add items to the cart.']);
    } else {
        echo "You must be logged in to add items to the cart.";
    }
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Debug: Log the incoming data
    error_log('Cart Add - POST data: ' . print_r($_POST, true));
    error_log('Cart Add - Product ID: ' . ($_POST['product_id'] ?? 'NOT SET'));
    error_log('Cart Add - Quantity: ' . ($_POST['quantity'] ?? 'NOT SET'));
    
    // Sanitize and validate input
    $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : false;
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : false;
    $buy_now = isset($_POST['buy_now']) ? true : false;
    $user_id = $_SESSION['user_id']; // Get user ID from the session

    error_log('Cart Add - Filtered Product ID: ' . ($product_id === false ? 'FALSE' : $product_id));
    error_log('Cart Add - Filtered Quantity: ' . ($quantity === false ? 'FALSE' : $quantity));

    // Check if inputs are valid
    if ($product_id === false || $product_id <= 0 || $quantity === false || $quantity <= 0) {
        error_log('Cart Add - Validation failed: product_id=' . ($product_id === false ? 'FALSE' : $product_id) . ', quantity=' . ($quantity === false ? 'FALSE' : $quantity));
        // Check if this is an AJAX request
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid product ID or quantity.']);
        } else {
            echo "Invalid product ID or quantity.";
        }
        exit();
    }

    try {
        // Begin a transaction
        $pdo->beginTransaction();

        // Check current stock and status of the product
        $check_stock = $pdo->prepare("SELECT stock, status FROM products WHERE id = ?");
        $check_stock->execute([$product_id]);
        $product = $check_stock->fetch(PDO::FETCH_ASSOC);

        // If the product doesn't exist or stock is insufficient
        if (!$product) {
            $pdo->rollBack(); // Rollback transaction on error
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Product not found.']);
            } else {
                $product_url = product_url($product_id) . "?error=product_not_found";
                echo "<script>window.location.href='" . $product_url . "';</script>";
            }
            exit();
        }
        
        if ($product['status'] !== 'active') {
            $pdo->rollBack(); // Rollback transaction on error
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Product is not available.']);
            } else {
                $product_url = product_url($product_id) . "?error=product_inactive";
                echo "<script>window.location.href='" . $product_url . "';</script>";
            }
            exit();
        }
        
        if ($product['stock'] < $quantity) {
            $pdo->rollBack(); // Rollback transaction on error
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Insufficient stock available.']);
            } else {
                echo "<script>window.location.href='../products/details.php?id=" . $product_id . "&error=insufficient_stock';</script>";
            }
            exit();
        }

        // Check if the product is already in the cart
        $check_cart = $pdo->prepare("SELECT quantity FROM cart WHERE user_id = ? AND product_id = ?");
        $check_cart->execute([$user_id, $product_id]);

        if ($buy_now) {
            // For Buy Now: Save current cart, clear it, and add only this product
            
            // First, save current cart to saved_carts table
            $save_cart = $pdo->prepare("INSERT INTO saved_carts (user_id, product_id, quantity, saved_at) 
                                        SELECT user_id, product_id, quantity, NOW() FROM cart WHERE user_id = ?");
            $save_cart->execute([$user_id]);
            
            // Clear existing cart
            $clear_cart = $pdo->prepare("DELETE FROM cart WHERE user_id = ?");
            $clear_cart->execute([$user_id]);
            
            // Insert the Buy Now product
            $add_to_cart = $pdo->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)");
            $add_to_cart->execute([$user_id, $product_id, $quantity]);
        } else {
            // Regular Add to Cart behavior
            if ($check_cart->rowCount() > 0) {
                // If product exists, update the quantity in the cart
                $existing_item = $check_cart->fetch(PDO::FETCH_ASSOC);
                $new_quantity = $existing_item['quantity'] + $quantity;

                // Check if the new quantity exceeds stock
                if ($new_quantity > $product['stock']) {
                    $pdo->rollBack(); // Rollback transaction on error
                    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                        http_response_code(400);
                        echo json_encode(['success' => false, 'message' => 'Insufficient stock available.']);
                    } else {
                        $product_url = product_url($product_id) . "?error=insufficient_stock";
                        echo "<script>window.location.href='" . $product_url . "';</script>";
                    }
                    exit();
                }

                // Update cart quantity
                $update_cart = $pdo->prepare("UPDATE cart SET quantity = ? WHERE user_id = ? AND product_id = ?");
                $update_cart->execute([$new_quantity, $user_id, $product_id]);
            } else {
                // If product does not exist in the cart, insert a new row
                if ($quantity > $product['stock']) {
                    $pdo->rollBack(); // Rollback transaction on error
                    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                        http_response_code(400);
                        echo json_encode(['success' => false, 'message' => 'Insufficient stock available.']);
                    } else {
                        $product_url = product_url($product_id) . "?error=insufficient_stock";
                        echo "<script>window.location.href='" . $product_url . "';</script>";
                    }
                    exit();
                }

                // Insert new product into the cart
                $add_to_cart = $pdo->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)");
                $add_to_cart->execute([$user_id, $product_id, $quantity]);
            }
        }

        // Note: Stock is not reduced here as it should only be reduced when order is placed
        // Stock validation is done above to ensure sufficient stock is available

        // Commit the transaction
        $pdo->commit();

        // Check if this is an AJAX request
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            echo json_encode(['success' => true, 'message' => 'Product added to cart successfully!']);
        } else {
            // Redirect based on buy_now parameter
            if ($buy_now) {
                // Redirect to checkout for buy now
                echo "<script>window.location.href='../orders/checkout.php';</script>";
            } else {
                // Redirect to the cart page after adding the product
                echo "<script>window.location.href='index.php?success=1';</script>";
            }
        }
        exit();

    } catch (Exception $e) {
        // Rollback transaction on error
        $pdo->rollBack();
        error_log("Cart error: " . $e->getMessage());
        
        // Check if this is an AJAX request
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'An error occurred while adding to cart.']);
        } else {
            $product_url = product_url($product_id) . "?error=1";
            echo "<script>window.location.href='" . $product_url . "';</script>";
        }
        exit();
    }
} else {
    // Handle non-POST requests
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    } else {
        echo "Invalid request method.";
    }
}
?>

