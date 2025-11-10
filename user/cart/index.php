<?php
// Set page metadata
$pageTitle = 'Shopping Cart';
$pageDescription = 'Review your selected air conditioning products and proceed to checkout';
$pageKeywords = 'shopping cart, AC products, checkout, air conditioner cart';

require_once __DIR__ . '/../../includes/config/init.php';
include INCLUDES_PATH . '/templates/header.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    // Store current page URL for redirect after login
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    echo "<script>window.location.href='../auth/login.php';</script>";
    exit();
}

$user_id = $_SESSION['user_id'];

// Handle quantity updates
if ($_POST && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_quantity') {
        $product_id = intval($_POST['product_id']);
        $quantity = intval($_POST['quantity']);
        
        if ($quantity > 0) {
            $update_query = $pdo->prepare("UPDATE cart SET quantity = ? WHERE user_id = ? AND product_id = ?");
            $update_query->execute([$quantity, $user_id, $product_id]);
        } else {
            $delete_query = $pdo->prepare("DELETE FROM cart WHERE user_id = ? AND product_id = ?");
            $delete_query->execute([$user_id, $product_id]);
        }
        echo "<script>window.location.href='index.php';</script>";
        exit();
    } elseif ($_POST['action'] === 'remove_item') {
        $product_id = intval($_POST['product_id']);
        $delete_query = $pdo->prepare("DELETE FROM cart WHERE user_id = ? AND product_id = ?");
        $delete_query->execute([$user_id, $product_id]);
        echo "<script>window.location.href='index.php';</script>";
        exit();
    }
}

// Handle shipping selection
$selected_shipping = isset($_POST['shipping_option']) ? $_POST['shipping_option'] : 'standard';
$shipping_cost = 0;
$shipping_label = '';

switch ($selected_shipping) {
    case 'express':
        $shipping_cost = 200;
        $shipping_label = 'Express (1 Day)';
        break;
    case 'fast':
        $shipping_cost = 100;
        $shipping_label = 'Fast (2-3 Days)';
        break;
    case 'standard':
    default:
        $shipping_cost = 40;
        $shipping_label = 'Standard (4-7 Days)';
        break;
}

// Handle coupon application
$applied_coupon = null;
$discount_amount = 0;
if ($_POST && isset($_POST['coupon_code'])) {
    $coupon_code = $_POST['coupon_code'];
    $coupon_query = $pdo->prepare("SELECT * FROM offers WHERE code = ? AND status = 'active' AND (expiry_date IS NULL OR expiry_date > NOW())");
    $coupon_query->execute([$coupon_code]);
    $applied_coupon = $coupon_query->fetch(PDO::FETCH_ASSOC);
    
    if ($applied_coupon) {
        // Calculate discount based on coupon type
        if ($applied_coupon['discount_type'] === 'percentage') {
            $discount_amount = ($subtotal * $applied_coupon['discount_value']) / 100;
        } else {
            $discount_amount = $applied_coupon['discount_value'];
        }
    }
}

// Check if user has saved cart items
$check_saved = $pdo->prepare("SELECT COUNT(*) as count FROM saved_carts WHERE user_id = ?");
$check_saved->execute([$_SESSION['user_id']]);
$saved_count = $check_saved->fetch(PDO::FETCH_ASSOC)['count'];
$cart_items_query = $pdo->prepare("
    SELECT c.*, 
           p.*,
           b.name as brand_name,
           cat.name as category_name,
           sc.name as subcategory_name,
           (p.price * c.quantity) AS subtotal
    FROM cart c
    JOIN products p ON c.product_id = p.id
    LEFT JOIN brands b ON p.brand_id = b.id
    LEFT JOIN categories cat ON p.category_id = cat.id
    LEFT JOIN sub_categories sc ON p.sub_category_id = sc.id
    WHERE c.user_id = ? AND p.status = 'active'
    ORDER BY c.added_at DESC
");
$cart_items_query->execute([$user_id]);
$cart_items = $cart_items_query->fetchAll(PDO::FETCH_ASSOC);

// Calculate totals
$total_items = 0;
$subtotal = 0;
foreach ($cart_items as $item) {
    $total_items += $item['quantity'];
    $subtotal += $item['subtotal'];
}

$tax = ($subtotal - $discount_amount) * 0.28; // 28% GST
$grand_total = $subtotal - $discount_amount + $shipping_cost + $tax;
?>

<style>
/* E-commerce Cart Page */


/* Main Cart Container */
.cart-container {
    background: #fff;
    padding: 30px 0;
}

.cart-layout {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 30px;
    margin-bottom: 40px;
}

/* Cart Items Section */
.cart-items {
    background: #fff;
}

.cart-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 2px solid #e9ecef;
}

.cart-title {
    font-size: 24px;
    font-weight: 600;
    color: #495057;
}

.items-count {
    color: #6c757d;
    font-size: 14px;
}

/* Cart Item Card */
.cart-item {
    border: 1px solid #e9ecef;
    border-radius: 8px;
    margin-bottom: 20px;
    padding: 20px;
    background: #fff;
    transition: box-shadow 0.3s;
}

.cart-item:hover {
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.item-content {
    display: grid;
    grid-template-columns: 120px 1fr auto;
    gap: 20px;
    align-items: start;
}

.item-image {
    width: 120px;
    height: 120px;
    object-fit: contain;
    border: 1px solid #e9ecef;
    border-radius: 6px;
    background: #f8f9fa;
}

.item-details {
    flex: 1;
}

.item-name {
    font-size: 18px;
    font-weight: 600;
    color: #495057;
    margin-bottom: 5px;
    text-decoration: none;
}

.item-name:hover {
    color: #007bff;
    text-decoration: none;
}

.item-model {
    color: #6c757d;
    font-size: 14px;
    margin-bottom: 8px;
}

.item-brand {
    color: #007bff;
    font-size: 14px;
    font-weight: 500;
    margin-bottom: 8px;
}

.item-category {
    color: #6c757d;
    font-size: 12px;
    margin-bottom: 10px;
}

.item-specs {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    margin-bottom: 15px;
}

.spec-badge {
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 4px;
    padding: 4px 8px;
    font-size: 12px;
    color: #495057;
}

.stock-status {
    display: flex;
    align-items: center;
    gap: 5px;
    margin-bottom: 10px;
}

.stock-badge {
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 500;
}

.stock-badge.in-stock {
    background: #d4edda;
    color: #155724;
}

.stock-badge.low-stock {
    background: #fff3cd;
    color: #856404;
}

.stock-badge.out-of-stock {
    background: #f8d7da;
    color: #721c24;
}

.item-actions {
    display: flex;
    align-items: center;
    gap: 15px;
}

.quantity-controls {
    display: flex;
    align-items: center;
    border: 1px solid #ced4da;
    border-radius: 4px;
    overflow: hidden;
}

.quantity-btn {
    width: 35px;
    height: 35px;
    border: none;
    background: #fff;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    color: #495057;
}

.quantity-btn:hover {
    background: #f8f9fa;
}

.quantity-input {
    width: 60px;
    height: 35px;
    border: none;
    text-align: center;
    font-weight: 600;
    outline: none;
}

.item-price {
    text-align: right;
}

.price-per-unit {
    color: #6c757d;
    font-size: 14px;
    margin-bottom: 5px;
}

.item-subtotal {
    font-size: 18px;
    font-weight: 700;
    color: #28a745;
}

/* Cart Discount Pricing Styles */
.price-per-unit {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
    margin-bottom: 5px;
}

.current-price {
    font-size: 16px;
    font-weight: 700;
    color: #28a745;
}

.original-price {
    font-size: 14px;
    font-weight: 500;
    color: #6c757d;
    text-decoration: line-through;
}

.discount-badge {
    background: linear-gradient(135deg, #dc3545, #c82333);
    color: white;
    padding: 3px 8px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.3px;
}

.remove-btn {
    background: none;
    border: none;
    color: #dc3545;
    cursor: pointer;
    padding: 5px;
    border-radius: 4px;
    transition: background-color 0.3s;
}

.remove-btn:hover {
    background: #f8d7da;
}

/* Cart Summary */
.cart-summary {
    background: #fff;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    padding: 25px;
    position: sticky;
    top: 20px;
    height: fit-content;
}

.summary-title {
    font-size: 20px;
    font-weight: 600;
    margin-bottom: 20px;
    color: #495057;
}

.summary-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 12px;
    padding: 8px 0;
}

.summary-row.total {
    border-top: 2px solid #e9ecef;
    margin-top: 15px;
    padding-top: 15px;
    font-weight: 700;
    font-size: 18px;
    color: #495057;
}

.summary-label {
    color: #6c757d;
}

.summary-value {
    font-weight: 600;
    color: #495057;
}

.summary-value.discount {
    color: #28a745;
}

.summary-value.total {
    color: #007bff;
    font-size: 18px;
}

/* Coupon Section */
.coupon-section {
    margin: 20px 0;
    padding: 20px;
    background: #f8f9fa;
    border-radius: 6px;
    border: 1px solid #e9ecef;
}

.coupon-title {
    font-size: 16px;
    font-weight: 600;
    margin-bottom: 15px;
    color: #495057;
}

.coupon-form {
    display: flex;
    gap: 10px;
}

.coupon-input {
    flex: 1;
    padding: 10px 12px;
    border: 1px solid #ced4da;
    border-radius: 4px;
    font-size: 14px;
}

.coupon-btn {
    background: #007bff;
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 4px;
    cursor: pointer;
    font-weight: 600;
    transition: background-color 0.3s;
}

.coupon-btn:hover {
    background: #0056b3;
}

.applied-coupon {
    background: #d4edda;
    border: 1px solid #c3e6cb;
    color: #155724;
    padding: 10px 15px;
    border-radius: 4px;
    margin-top: 10px;
    font-size: 14px;
}

/* Action Buttons */
.cart-actions {
    margin-top: 30px;
    display: flex;
    gap: 15px;
    flex-direction: column;
}

.btn-checkout {
    background: #28a745;
    color: white;
    border: none;
    padding: 15px 25px;
    border-radius: 6px;
    font-weight: 600;
    font-size: 16px;
    cursor: pointer;
    transition: background-color 0.3s;
    text-decoration: none;
    text-align: center;
    display: block;
}

.btn-checkout:hover {
    background: #1e7e34;
    color: white;
    text-decoration: none;
}

.btn-continue {
    background: #6c757d;
    color: white;
    border: none;
    padding: 12px 25px;
    border-radius: 6px;
    font-weight: 600;
    cursor: pointer;
    transition: background-color 0.3s;
    text-decoration: none;
    text-align: center;
    display: block;
}

.btn-continue:hover {
    background: #545b62;
    color: white;
    text-decoration: none;
}

/* Empty Cart */
.empty-cart {
    text-align: center;
    padding: 60px 20px;
    background: #fff;
    border: 1px solid #e9ecef;
    border-radius: 8px;
}

.empty-cart-icon {
    font-size: 64px;
    color: #6c757d;
    margin-bottom: 20px;
}

.empty-cart h3 {
    font-size: 24px;
    margin-bottom: 15px;
    color: #495057;
}

.empty-cart p {
    font-size: 16px;
    color: #6c757d;
    margin-bottom: 30px;
}

/* Shipping Options */
.shipping-section {
    margin: 20px 0;
    padding: 20px;
    background: #f8f9fa;
    border-radius: 6px;
    border: 1px solid #e9ecef;
}

.shipping-title {
    font-size: 16px;
    font-weight: 600;
    margin-bottom: 15px;
    color: #495057;
}

.shipping-options {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.shipping-option {
    display: flex;
    align-items: center;
    padding: 12px 15px;
    border: 2px solid #e9ecef;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.3s;
    background: #fff;
}

.shipping-option:hover {
    border-color: #007bff;
}

.shipping-option.selected {
    border-color: #007bff;
    background: #e7f3ff;
}

.shipping-option input[type="radio"] {
    margin-right: 12px;
    transform: scale(1.2);
}

.shipping-info {
    flex: 1;
}

.shipping-type {
    font-weight: 600;
    color: #495057;
    margin-bottom: 2px;
}

.shipping-duration {
    font-size: 14px;
    color: #6c757d;
}

.shipping-price {
    font-weight: 700;
    color: #28a745;
    font-size: 16px;
}

/* Installation Note */
.installation-note {
    background: #e7f3ff;
    border: 1px solid #b3d9ff;
    border-radius: 6px;
    padding: 15px;
    margin-top: 20px;
}

.installation-note h5 {
    color: #004085;
    margin-bottom: 8px;
    font-weight: 600;
}

.installation-note p {
    color: #004085;
    font-size: 14px;
    margin: 0;
}

/* Responsive Design */
@media (max-width: 768px) {
    .cart-layout {
        grid-template-columns: 1fr;
        gap: 20px;
    }
    
    .cart-summary {
        position: static;
    }
    
    .item-content {
        grid-template-columns: 1fr;
        gap: 15px;
    }
    
    .item-image {
        width: 100px;
        height: 100px;
    }
    
    .item-actions {
        flex-direction: column;
        align-items: stretch;
        gap: 10px;
    }
    
    .coupon-form {
        flex-direction: column;
    }
    
    .cart-actions {
        margin-top: 20px;
    }
}
</style>


<!-- Cart Content -->
<div class="cart-container">
    <div class="container">
            <?php if (empty($cart_items)): ?>
                <div class="empty-cart">
                <div class="empty-cart-icon">🛒</div>
                    <h3>Your cart is empty</h3>
                    <p>Add some amazing air conditioning products to get started!</p>
                <a href="<?php echo USER_URL; ?>/products/" class="btn-checkout">Browse Products</a>
                </div>
            <?php else: ?>
            <div class="cart-layout">
                <!-- Cart Items Section -->
                <div class="cart-items">
                    <div class="cart-header">
                        <h2 class="cart-title">Shopping Cart</h2>
                        <span class="items-count"><?php echo $total_items; ?> item(s)</span>
                    </div>
                    
                    <?php if ($saved_count > 0): ?>
                    <div class="alert alert-info" style="margin: 20px 0; padding: 15px; background: #e3f2fd; border: 1px solid #2196f3; border-radius: 5px; color: #1976d2;">
                        <i class="fas fa-info-circle"></i>
                        <strong>Previous Cart Items Available:</strong> You have <?php echo $saved_count; ?> items from a previous cart. 
                        <button onclick="restoreCart()" class="btn btn-sm btn-primary" style="margin-left: 10px; padding: 5px 15px; background: #2196f3; color: white; border: none; border-radius: 3px; cursor: pointer;">
                            <i class="fas fa-undo"></i> Restore Previous Cart
                        </button>
                    </div>
                    <?php endif; ?>
                    
                        <?php foreach ($cart_items as $item): ?>
                        <div class="cart-item">
                            <div class="item-content">
                                <!-- Product Image -->
                                <img src="<?php echo UPLOAD_URL; ?>/<?php echo urlencode($item['product_image']); ?>" 
                                     alt="<?php echo htmlspecialchars($item['product_name']); ?>" 
                                     class="item-image">
                                
                                <!-- Product Details -->
                                <div class="item-details">
                                    <a href="<?= product_url($item['product_id'], false, true, 'cart') ?>" class="item-name">
                                        <?php echo htmlspecialchars($item['product_name']); ?>
                                    </a>
                                    <div class="item-model"><?php echo htmlspecialchars($item['model_name'] . ' - ' . $item['model_number']); ?></div>
                                    <div class="item-brand"><?php echo htmlspecialchars($item['brand_name']); ?></div>
                                    <div class="item-category"><?php echo htmlspecialchars($item['category_name'] . ' / ' . $item['subcategory_name']); ?></div>
                                    
                                    <!-- Product Specifications -->
                                    <div class="item-specs">
                                        <span class="spec-badge"><?php echo htmlspecialchars($item['star_rating']); ?> Star</span>
                                        <span class="spec-badge"><?php echo htmlspecialchars($item['inverter']); ?> Inverter</span>
                                        <span class="spec-badge"><?php echo htmlspecialchars($item['capacity']); ?></span>
                                        <span class="spec-badge"><?php echo htmlspecialchars($item['warranty_years']); ?> Year Warranty</span>
                                        <?php if ($item['amc_available']): ?>
                                            <span class="spec-badge">AMC Available</span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <!-- Quantity Controls -->
                                    <div class="item-actions">
                                        <div class="quantity-controls">
                                            <button class="quantity-btn" onclick="updateQuantity(<?php echo $item['product_id']; ?>, <?php echo $item['quantity'] - 1; ?>)">-</button>
                                            <input type="number" class="quantity-input" value="<?php echo $item['quantity']; ?>" 
                                                   min="1" 
                                                   onchange="updateQuantity(<?php echo $item['product_id']; ?>, this.value)">
                                            <button class="quantity-btn" onclick="updateQuantity(<?php echo $item['product_id']; ?>, <?php echo $item['quantity'] + 1; ?>)">+</button>
                                        </div>
                                        <button class="remove-btn" onclick="removeItem(<?php echo $item['product_id']; ?>)" title="Remove Item">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                                
                                <!-- Price Information -->
                                <div class="item-price">
                                    <?php if ($item['original_price'] && $item['original_price'] > $item['price']): ?>
                                        <!-- Discount pricing display -->
                                        <div class="price-per-unit">
                                            <span class="current-price">₹<?php echo number_format($item['price'], 0); ?></span>
                                            <span class="original-price">₹<?php echo number_format($item['original_price'], 0); ?></span>
                                            <span class="discount-badge"><?php echo number_format($item['discount_percentage'], 0); ?>% OFF</span>
                                        </div>
                                        <div class="item-subtotal">₹<?php echo number_format($item['subtotal'], 0); ?></div>
                                    <?php else: ?>
                                        <!-- Regular pricing -->
                                        <div class="price-per-unit">₹<?php echo number_format($item['price'], 0); ?> per unit</div>
                                        <div class="item-subtotal">₹<?php echo number_format($item['subtotal'], 0); ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                </div>
                
                <!-- Cart Summary -->
                <div class="cart-summary">
                    <h3 class="summary-title">Order Summary</h3>
                    
                    <div class="summary-row">
                        <span class="summary-label">Items (<?php echo $total_items; ?>):</span>
                        <span class="summary-value">₹<?php echo number_format($subtotal, 2); ?></span>
                    </div>
                    
                    <div class="summary-row">
                        <span class="summary-label">Shipping:</span>
                        <span class="summary-value">₹<?php echo number_format($shipping_cost, 2); ?></span>
                    </div>
                    
                    <?php if ($discount_amount > 0): ?>
                        <div class="summary-row">
                            <span class="summary-label">Discount:</span>
                            <span class="summary-value discount">-₹<?php echo number_format($discount_amount, 2); ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <div class="summary-row">
                        <span class="summary-label">GST (28%):</span>
                        <span class="summary-value">₹<?php echo number_format($tax, 2); ?></span>
                    </div>
                    
                    <div class="summary-row total">
                        <span class="summary-label">Total:</span>
                        <span class="summary-value total">₹<?php echo number_format($grand_total, 2); ?></span>
                    </div>
                    
                    <!-- Shipping Options -->
                    <div class="shipping-section">
                        <h4 class="shipping-title">Delivery Options</h4>
                        <form method="POST" class="shipping-options">
                            <label class="shipping-option <?php echo $selected_shipping === 'standard' ? 'selected' : ''; ?>">
                                <input type="radio" name="shipping_option" value="standard" <?php echo $selected_shipping === 'standard' ? 'checked' : ''; ?> onchange="this.form.submit()">
                                <div class="shipping-info">
                                    <div class="shipping-type">Standard Delivery</div>
                                    <div class="shipping-duration">4-7 business days</div>
                                </div>
                                <div class="shipping-price">₹40</div>
                            </label>
                            
                            <label class="shipping-option <?php echo $selected_shipping === 'fast' ? 'selected' : ''; ?>">
                                <input type="radio" name="shipping_option" value="fast" <?php echo $selected_shipping === 'fast' ? 'checked' : ''; ?> onchange="this.form.submit()">
                                <div class="shipping-info">
                                    <div class="shipping-type">Fast Delivery</div>
                                    <div class="shipping-duration">2-3 business days</div>
                                </div>
                                <div class="shipping-price">₹100</div>
                            </label>
                            
                            <label class="shipping-option <?php echo $selected_shipping === 'express' ? 'selected' : ''; ?>">
                                <input type="radio" name="shipping_option" value="express" <?php echo $selected_shipping === 'express' ? 'checked' : ''; ?> onchange="this.form.submit()">
                                <div class="shipping-info">
                                    <div class="shipping-type">Express Delivery</div>
                                    <div class="shipping-duration">1 business day</div>
                                </div>
                                <div class="shipping-price">₹200</div>
                            </label>
                        </form>
                    </div>
                    
                    <!-- Coupon Section -->
                    <div class="coupon-section">
                        <h4 class="coupon-title">Have a coupon?</h4>
                        <form method="POST" class="coupon-form">
                            <input type="text" name="coupon_code" class="coupon-input" placeholder="Enter coupon code" 
                                   value="<?php echo isset($_POST['coupon_code']) ? htmlspecialchars($_POST['coupon_code']) : ''; ?>">
                            <button type="submit" class="coupon-btn">Apply</button>
                        </form>
                        <?php if ($applied_coupon): ?>
                            <div class="applied-coupon">
                                ✓ Coupon "<?php echo htmlspecialchars($applied_coupon['code']); ?>" applied successfully!
                            </div>
                        <?php endif; ?>
                </div>
                
                    <!-- Action Buttons -->
                <div class="cart-actions">
                        <a href="../orders/checkout.php" class="btn-checkout">Proceed to Checkout</a>
                        <a href="<?php echo USER_URL; ?>/products/" class="btn-continue">Continue Shopping</a>
                    </div>
                    
                    <!-- Installation Note -->
                    <div class="installation-note">
                        <h5><i class="fas fa-tools"></i> Installation Required</h5>
                        <p>Professional installation is required for all air conditioning units. Installation charges may apply.</p>
                    </div>
                </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

<script>
// Cart Page JavaScript

// Update quantity function
function updateQuantity(productId, quantity) {
    if (quantity < 1) {
        if (confirm('Are you sure you want to remove this item from your cart?')) {
            removeItem(productId);
        }
        return;
    }
    
    // Create form and submit
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'index.php';
    
    const actionInput = document.createElement('input');
    actionInput.type = 'hidden';
    actionInput.name = 'action';
    actionInput.value = 'update_quantity';
    
    const productIdInput = document.createElement('input');
    productIdInput.type = 'hidden';
    productIdInput.name = 'product_id';
    productIdInput.value = productId;
    
    const quantityInput = document.createElement('input');
    quantityInput.type = 'hidden';
    quantityInput.name = 'quantity';
    quantityInput.value = quantity;
    
    // Preserve shipping option
    const shippingOption = document.querySelector('input[name="shipping_option"]:checked');
    if (shippingOption) {
        const shippingInput = document.createElement('input');
        shippingInput.type = 'hidden';
        shippingInput.name = 'shipping_option';
        shippingInput.value = shippingOption.value;
        form.appendChild(shippingInput);
    }
    
    form.appendChild(actionInput);
    form.appendChild(productIdInput);
    form.appendChild(quantityInput);
    document.body.appendChild(form);
    form.submit();
}

// Remove item function
function removeItem(productId) {
    if (confirm('Are you sure you want to remove this item from your cart?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'index.php';
        
        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action';
        actionInput.value = 'remove_item';
        
        const productIdInput = document.createElement('input');
        productIdInput.type = 'hidden';
        productIdInput.name = 'product_id';
        productIdInput.value = productId;
        
        // Preserve shipping option
        const shippingOption = document.querySelector('input[name="shipping_option"]:checked');
        if (shippingOption) {
            const shippingInput = document.createElement('input');
            shippingInput.type = 'hidden';
            shippingInput.name = 'shipping_option';
            shippingInput.value = shippingOption.value;
            form.appendChild(shippingInput);
        }
        
        form.appendChild(actionInput);
        form.appendChild(productIdInput);
        document.body.appendChild(form);
        form.submit();
    }
}

// Restore cart function
function restoreCart() {
    if (confirm('This will replace your current cart with your previous cart items. Continue?')) {
        fetch('restore.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Show success notification
                alert(data.message);
                setTimeout(() => location.reload(), 1000);
            } else {
                alert(data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while restoring cart.');
        });
    }
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
    const quantityInputs = document.querySelectorAll('.quantity-input');
    quantityInputs.forEach(input => {
        input.addEventListener('input', function() {
            const value = parseInt(this.value);
            const max = parseInt(this.getAttribute('max'));
            const min = parseInt(this.getAttribute('min'));
            
            if (value > max) {
                this.value = max;
                alert('Quantity cannot exceed available stock (' + max + ' units)');
            } else if (value < min) {
                this.value = min;
            }
        });
    });
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
});
</script>

<?php include INCLUDES_PATH . '/templates/footer.php'; ?>
