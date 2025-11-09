<?php
// Set page metadata
$pageTitle = 'Order Confirmation';
$pageDescription = 'Your order has been placed successfully';
$pageKeywords = 'order confirmation, order placed, thank you';

require_once __DIR__ . '/../../includes/config/init.php';
include INCLUDES_PATH . '/templates/header.php';

if (!isset($_SESSION['user_id'])) {
    echo "<script>window.location.href='login.php';</script>";
    exit();
}

$user_id = $_SESSION['user_id'];

// Get order ID from URL parameter
$orderId = isset($_GET['order']) ? intval($_GET['order']) : 0;

if ($orderId <= 0) {
    echo "<script>window.location.href='order_history.php';</script>";
    exit();
}

// Fetch order details
$orderQuery = $pdo->prepare("
    SELECT o.*, u.username, u.email, u.phone_number
    FROM orders o
    JOIN users u ON o.user_id = u.id
    WHERE o.id = ? AND o.user_id = ?
");
$orderQuery->execute([$orderId, $user_id]);
$order = $orderQuery->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    echo "<script>alert('Order not found or access denied.'); window.location.href='order_history.php';</script>";
    exit();
}

// Fetch order items with product details
$itemsQuery = $pdo->prepare("
    SELECT oi.*, p.*, b.name as brand_name, cat.name as category_name, sc.name as subcategory_name
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    LEFT JOIN brands b ON p.brand_id = b.id
    LEFT JOIN categories cat ON p.category_id = cat.id
    LEFT JOIN sub_categories sc ON p.sub_category_id = sc.id
    WHERE oi.order_id = ?
    ORDER BY oi.id
");
$itemsQuery->execute([$orderId]);
$orderItems = $itemsQuery->fetchAll(PDO::FETCH_ASSOC);

// Send notification
$notificationQuery = $pdo->prepare("
    INSERT INTO notifications (user_id, title, message, type, is_read, created_at)
    VALUES (?, ?, ?, 'order', 0, NOW())
");
$notificationMessage = "Your order " . $order['order_number'] . " has been placed successfully.";
$notificationQuery->execute([$user_id, "Order Placed Successfully", $notificationMessage]);

// Check if user has saved cart items (from Buy Now)
$check_saved = $pdo->prepare("SELECT COUNT(*) as count FROM saved_carts WHERE user_id = ?");
$check_saved->execute([$user_id]);
$saved_count = $check_saved->fetch(PDO::FETCH_ASSOC)['count'];
?>

<style>
/* Modern E-commerce Order Confirmation - Flipkart/Amazon Style */
body {
    background: #f5f5f5;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

.confirmation-page {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
    background: #fff;
    min-height: 100vh;
}

/* Success Header - Amazon Style */
.success-header {
    background: linear-gradient(135deg, #ff9500, #ff6b35);
    color: white;
    padding: 40px 30px;
    border-radius: 8px;
    margin-bottom: 30px;
    text-align: center;
    box-shadow: 0 4px 20px rgba(255, 149, 0, 0.3);
}

.success-icon {
    width: 80px;
    height: 80px;
    background: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 20px;
    font-size: 40px;
    color: #ff9500;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.05); }
    100% { transform: scale(1); }
}

.success-title {
    font-size: 28px;
    font-weight: 700;
    margin-bottom: 10px;
}

.success-subtitle {
    font-size: 18px;
    opacity: 0.9;
    margin-bottom: 20px;
}

.order-number-badge {
    background: rgba(255, 255, 255, 0.2);
    padding: 12px 24px;
    border-radius: 25px;
    font-size: 16px;
    font-weight: 600;
    display: inline-block;
    border: 2px solid rgba(255, 255, 255, 0.3);
}

/* Main Content Layout */
.content-layout {
    display: grid;
    grid-template-columns: 1fr 350px;
    gap: 30px;
    margin-bottom: 30px;
}

/* Order Items Section */
.order-items-section {
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    overflow: hidden;
}

.section-header {
    background: #f8f9fa;
    padding: 20px 25px;
    border-bottom: 1px solid #e9ecef;
    display: flex;
    align-items: center;
    gap: 12px;
}

.section-icon {
    width: 24px;
    height: 24px;
    background: #007bff;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 12px;
}

.section-title {
    font-size: 18px;
    font-weight: 600;
    color: #333;
    margin: 0;
}

.order-item {
    display: flex;
    padding: 20px 25px;
    border-bottom: 1px solid #f0f0f0;
    gap: 20px;
    align-items: flex-start;
}

.order-item:last-child {
    border-bottom: none;
}

.item-image {
    width: 80px;
    height: 80px;
    object-fit: contain;
    border: 1px solid #e9ecef;
    border-radius: 6px;
    background: #fafafa;
    flex-shrink: 0;
}

.item-details {
    flex: 1;
    min-width: 0;
}

.item-title {
    font-size: 16px;
    font-weight: 600;
    color: #333;
    margin-bottom: 6px;
    line-height: 1.4;
}

.item-brand {
    color: #007bff;
    font-size: 14px;
    font-weight: 500;
    margin-bottom: 8px;
}

.item-specs {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-bottom: 12px;
}

.spec-tag {
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 4px;
    padding: 4px 8px;
    font-size: 12px;
    color: #666;
}

.item-options {
    display: flex;
    gap: 12px;
}

.option-tag {
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 11px;
    font-weight: 500;
}

.option-yes {
    background: #d4edda;
    color: #155724;
}

.option-no {
    background: #f8d7da;
    color: #721c24;
}

.item-price-section {
    text-align: right;
    min-width: 120px;
}

.price-unit {
    color: #666;
    font-size: 14px;
    margin-bottom: 4px;
}

.price-total {
    font-size: 18px;
    font-weight: 700;
    color: #28a745;
    margin-bottom: 4px;
}

.price-qty {
    color: #999;
    font-size: 12px;
}

/* Order Summary Sidebar */
.order-summary {
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    overflow: hidden;
    height: fit-content;
    position: sticky;
    top: 20px;
}

.summary-header {
    background: #007bff;
    color: white;
    padding: 20px 25px;
    display: flex;
    align-items: center;
    gap: 12px;
}

.summary-icon {
    width: 24px;
    height: 24px;
    background: rgba(255,255,255,0.2);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
}

.summary-title {
    font-size: 18px;
    font-weight: 600;
    margin: 0;
}

.summary-content {
    padding: 25px;
}

.summary-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 0;
    border-bottom: 1px solid #f0f0f0;
}

.summary-row:last-child {
    border-bottom: none;
}

.summary-row.final {
    border-top: 2px solid #e9ecef;
    margin-top: 15px;
    padding-top: 20px;
    font-weight: 700;
    font-size: 18px;
    color: #333;
}

.summary-label {
    color: #666;
    font-size: 14px;
}

.summary-value {
    font-weight: 600;
    color: #333;
    font-size: 14px;
}

.summary-value.discount {
    color: #28a745;
}

.summary-value.final {
    color: #007bff;
    font-size: 18px;
}

/* Order Info Cards */
.info-cards {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-bottom: 30px;
}

.info-card {
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    overflow: hidden;
}

.card-header {
    background: #f8f9fa;
    padding: 15px 20px;
    border-bottom: 1px solid #e9ecef;
    display: flex;
    align-items: center;
    gap: 10px;
}

.card-icon {
    width: 20px;
    height: 20px;
    background: #28a745;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 10px;
}

.card-title {
    font-size: 16px;
    font-weight: 600;
    color: #333;
    margin: 0;
}

.card-content {
    padding: 20px;
}

.info-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 0;
    border-bottom: 1px solid #f8f9fa;
}

.info-item:last-child {
    border-bottom: none;
}

.info-label {
    font-weight: 500;
    color: #666;
    font-size: 14px;
}

.info-value {
    color: #333;
    font-weight: 500;
    font-size: 14px;
}

.status-badge {
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
}

.status-paid {
    background: #d4edda;
    color: #155724;
}

.status-pending {
    background: #fff3cd;
    color: #856404;
}

.status-cod {
    background: #cce5ff;
    color: #004085;
}

/* Action Buttons */
.action-section {
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    padding: 25px;
    margin-bottom: 30px;
}

.action-title {
    font-size: 18px;
    font-weight: 600;
    color: #333;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.action-buttons {
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
}

.btn {
    padding: 12px 24px;
    border: none;
    border-radius: 6px;
    font-weight: 600;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.3s;
    cursor: pointer;
    font-size: 14px;
}

.btn-primary {
    background: #007bff;
    color: white;
}

.btn-primary:hover {
    background: #0056b3;
    color: white;
    text-decoration: none;
    transform: translateY(-1px);
}

.btn-success {
    background: #28a745;
    color: white;
}

.btn-success:hover {
    background: #1e7e34;
    color: white;
    text-decoration: none;
    transform: translateY(-1px);
}

.btn-secondary {
    background: #6c757d;
    color: white;
}

.btn-secondary:hover {
    background: #545b62;
    color: white;
    text-decoration: none;
    transform: translateY(-1px);
}

/* Timeline */
.timeline {
    list-style: none;
    padding: 0;
    margin: 0;
}

.timeline-item {
    display: flex;
    align-items: center;
    padding: 12px 0;
    border-bottom: 1px solid #f0f0f0;
}

.timeline-item:last-child {
    border-bottom: none;
}

.timeline-dot {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background: #28a745;
    margin-right: 15px;
    flex-shrink: 0;
}

.timeline-dot.pending {
    background: #ffc107;
}

.timeline-dot.future {
    background: #e9ecef;
}

.timeline-text {
    color: #666;
    font-size: 14px;
}

/* Responsive */
@media (max-width: 768px) {
    .content-layout {
        grid-template-columns: 1fr;
        gap: 20px;
    }
    
    .info-cards {
        grid-template-columns: 1fr;
    }
    
    .order-item {
        flex-direction: column;
        gap: 15px;
    }
    
    .item-price-section {
        text-align: left;
    }
    
    .action-buttons {
        flex-direction: column;
    }
    
    .success-title {
        font-size: 24px;
    }
    
    .success-subtitle {
        font-size: 16px;
    }
}
</style>

<div class="confirmation-page">
    <!-- Success Header -->
    <div class="success-header">
        <div class="success-icon">✓</div>
        <h1 class="success-title">Thank You <?php echo htmlspecialchars($order['username']); ?>!</h1>
        <p class="success-subtitle">Your order has been placed successfully</p>
        <div class="order-number-badge">Order #<?php echo htmlspecialchars($order['order_number']); ?></div>
    </div>

    <!-- Info Cards -->
    <div class="info-cards">
        <!-- Order Details Card -->
        <div class="info-card">
            <div class="card-header">
                <div class="card-icon">📋</div>
                <h3 class="card-title">Order Details</h3>
            </div>
            <div class="card-content">
                <div class="info-item">
                    <span class="info-label">Order Date:</span>
                    <span class="info-value"><?php echo date('M d, Y g:i A', strtotime($order['created_at'])); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Payment Method:</span>
                    <span class="info-value"><?php echo htmlspecialchars($order['payment_method']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Payment Status:</span>
                    <span class="info-value">
                        <span class="status-badge status-<?php 
                            $status = strtolower($order['payment_status']); 
                            if ($status === 'paid') echo 'paid';
                            elseif ($status === 'cod') echo 'cod';
                            else echo 'pending';
                        ?>">
                            <?php echo htmlspecialchars($order['payment_status']); ?>
                        </span>
                    </span>
                </div>
                <div class="info-item">
                    <span class="info-label">Order Status:</span>
                    <span class="info-value">
                        <span class="status-badge status-pending">
                            <?php echo htmlspecialchars($order['order_status']); ?>
                        </span>
                    </span>
                </div>
            </div>
        </div>

        <!-- Delivery Card -->
        <div class="info-card">
            <div class="card-header">
                <div class="card-icon">🚚</div>
                <h3 class="card-title">Delivery Information</h3>
            </div>
            <div class="card-content">
                <div class="info-item">
                    <span class="info-label">Address:</span>
                    <span class="info-value"><?php echo htmlspecialchars($order['address']); ?></span>
                </div>
                <?php if ($order['delivery_date']): ?>
                <div class="info-item">
                    <span class="info-label">Expected Delivery:</span>
                    <span class="info-value"><?php echo date('M d, Y', strtotime($order['delivery_date'])); ?></span>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="content-layout">
        <!-- Order Items -->
        <div class="order-items-section">
            <div class="section-header">
                <div class="section-icon">📦</div>
                <h2 class="section-title">Ordered Items (<?php echo count($orderItems); ?>)</h2>
            </div>
            
            <?php foreach ($orderItems as $item): ?>
                <div class="order-item">
                    <img src="<?= UPLOAD_URL ?>/<?php echo htmlspecialchars($item['product_image']); ?>" 
                         alt="<?php echo htmlspecialchars($item['product_name']); ?>" 
                         class="item-image"
                         onerror="this.src='<?= IMG_URL ?>/placeholder-product.png'">
                    
                    <div class="item-details">
                        <h3 class="item-title"><?php echo htmlspecialchars($item['product_name']); ?></h3>
                        <div class="item-brand"><?php echo htmlspecialchars($item['brand_name']); ?></div>
                        
                        <div class="item-specs">
                            <span class="spec-tag"><?php echo htmlspecialchars($item['star_rating']); ?> ⭐</span>
                            <span class="spec-tag"><?php echo htmlspecialchars($item['capacity']); ?></span>
                            <span class="spec-tag"><?php echo htmlspecialchars($item['warranty_years']); ?>Y Warranty</span>
                        </div>
                        
                        <div class="item-options">
                            <span class="option-tag <?php echo $item['amc_opted'] ? 'option-yes' : 'option-no'; ?>">
                                AMC: <?php echo $item['amc_opted'] ? 'Yes' : 'No'; ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="item-price-section">
                        <div class="price-unit">₹<?php echo number_format($item['unit_price'], 2); ?> per unit</div>
                        <div class="price-total">₹<?php echo number_format($item['total_price'], 2); ?></div>
                        <div class="price-qty">Qty: <?php echo $item['quantity']; ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Order Summary -->
        <div class="order-summary">
            <div class="summary-header">
                <div class="summary-icon">💰</div>
                <h3 class="summary-title">Order Summary</h3>
            </div>
            <div class="summary-content">
                <?php
                $subtotal = 0;
                foreach ($orderItems as $item) {
                    $subtotal += $item['total_price'];
                }
                $tax = $subtotal * 0.28;
                $shipping = 40;
                $grand_total = $order['total_price'];
                ?>
                
                <div class="summary-row">
                    <span class="summary-label">Items (<?php echo count($orderItems); ?>):</span>
                    <span class="summary-value">₹<?php echo number_format($subtotal, 2); ?></span>
                </div>
                
                <div class="summary-row">
                    <span class="summary-label">Shipping:</span>
                    <span class="summary-value">₹<?php echo number_format($shipping, 2); ?></span>
                </div>
                
                <div class="summary-row">
                    <span class="summary-label">GST (28%):</span>
                    <span class="summary-value">₹<?php echo number_format($tax, 2); ?></span>
                </div>
                
                <div class="summary-row final">
                    <span class="summary-label">Total Amount:</span>
                    <span class="summary-value final">₹<?php echo number_format($grand_total, 2); ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Action Section -->
    <div class="action-section">
        <h3 class="action-title">📋 What's Next?</h3>
        
        <?php if ($saved_count > 0): ?>
        <!-- Cart Restoration Prompt -->
        <div class="cart-restore-prompt" style="background: #e3f2fd; border: 2px solid #2196f3; border-radius: 10px; padding: 20px; margin: 20px 0; text-align: center;">
            <div style="font-size: 24px; margin-bottom: 10px;">🛒</div>
            <h4 style="color: #1976d2; margin-bottom: 10px;">Previous Cart Items Available!</h4>
            <p style="color: #1976d2; margin-bottom: 15px;">
                You have <?php echo $saved_count; ?> items from your previous cart that were saved when you used "Buy Now". 
                Would you like to restore them?
            </p>
            <div style="display: flex; gap: 10px; justify-content: center; flex-wrap: wrap;">
                <button onclick="restoreCart()" class="btn btn-primary" style="padding: 10px 20px; background: #2196f3; color: white; border: none; border-radius: 5px; cursor: pointer;">
                    <i class="fas fa-undo"></i> Restore Previous Cart
                </button>
                <button onclick="dismissRestore()" class="btn btn-secondary" style="padding: 10px 20px; background: #6c757d; color: white; border: none; border-radius: 5px; cursor: pointer;">
                    <i class="fas fa-times"></i> Keep Current Cart Empty
                </button>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="action-buttons">
            <a href="tracking.php?order=<?php echo $orderId; ?>" class="btn btn-primary">
                <i class="fas fa-search"></i> Track Your Order
            </a>
            
            
            <a href="history.php" class="btn btn-secondary">
                <i class="fas fa-history"></i> View All Orders
            </a>
            
            <a href="../products/index.php" class="btn btn-secondary">
                <i class="fas fa-home"></i> Continue Shopping
            </a>
        </div>
    </div>
</div>

<script>
// Restore cart function
function restoreCart() {
    if (confirm('This will restore your previous cart items. Continue?')) {
        fetch('../cart/restore.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                // Hide the restore prompt
                const prompt = document.querySelector('.cart-restore-prompt');
                if (prompt) {
                    prompt.style.display = 'none';
                }
                // Redirect to cart page
                setTimeout(() => {
                    window.location.href = '../cart/index.php';
                }, 1000);
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

// Dismiss restore prompt
function dismissRestore() {
    if (confirm('Are you sure you want to keep your cart empty? Your previous cart items will be permanently removed.')) {
        // Clear saved cart items
        fetch('../cart/restore.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({action: 'dismiss'})
        })
        .then(response => response.json())
        .then(data => {
            // Hide the restore prompt
            const prompt = document.querySelector('.cart-restore-prompt');
            if (prompt) {
                prompt.style.display = 'none';
            }
        })
        .catch(error => {
            console.error('Error:', error);
        });
    }
}

document.addEventListener('DOMContentLoaded', function() {
    // Image loading with fallback
    const images = document.querySelectorAll('img');
    images.forEach(img => {
        img.addEventListener('error', function() {
            if (this.src !== '<?= IMG_URL ?>/placeholder-product.png') {
                this.src = '<?= IMG_URL ?>/placeholder-product.png';
            }
        });
    });
});
</script>

<?php
include INCLUDES_PATH . '/templates/footer.php';
?>
