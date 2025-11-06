<?php
// Set page metadata
$pageTitle = 'Track Your Order';
$pageDescription = 'Track your air conditioning order status and delivery information';
$pageKeywords = 'order tracking, track order, delivery status, AC order status';

require_once __DIR__ . '/../../includes/config/init.php';
include INCLUDES_PATH . '/templates/header.php';

$order = null;
$orderItems = null;
$error_message = '';
$success_message = '';

// Handle order tracking search
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['track_order'])) {
    $order_number = trim(filter_input(INPUT_POST, 'order_number', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
    
    if (empty($order_number)) {
        $error_message = "Please enter an order number.";
    } else {
        // Search for order by order number
        $stmt = $pdo->prepare("
            SELECT o.*, u.username, u.email, u.phone_number
            FROM orders o
            LEFT JOIN users u ON o.user_id = u.id
            WHERE o.order_number = ?
        ");
        $stmt->execute([$order_number]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($order) {
            // Get order items with product details
            $itemsStmt = $pdo->prepare("
                SELECT oi.*, p.*, b.name as brand_name, cat.name as category_name
                FROM order_items oi
                JOIN products p ON oi.product_id = p.id
                LEFT JOIN brands b ON p.brand_id = b.id
                LEFT JOIN categories cat ON p.category_id = cat.id
                WHERE oi.order_id = ?
                ORDER BY oi.id
            ");
            $itemsStmt->execute([$order['id']]);
            $orderItems = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);
            
            $success_message = "Order found successfully!";
        } else {
            $error_message = "Order not found. Please check your order number and try again.";
        }
    }
}

// Handle direct order ID from URL (for logged in users)
if (isset($_GET['order']) && is_numeric($_GET['order'])) {
    $orderId = intval($_GET['order']);
    
    if (isset($_SESSION['user_id'])) {
        // User is logged in, verify ownership
        $stmt = $pdo->prepare("
            SELECT o.*, u.username, u.email, u.phone_number
            FROM orders o
            LEFT JOIN users u ON o.user_id = u.id
            WHERE o.id = ? AND o.user_id = ?
        ");
        $stmt->execute([$orderId, $_SESSION['user_id']]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($order) {
            // Get order items
            $itemsStmt = $pdo->prepare("
                SELECT oi.*, p.*, b.name as brand_name, cat.name as category_name
                FROM order_items oi
                JOIN products p ON oi.product_id = p.id
                LEFT JOIN brands b ON p.brand_id = b.id
                LEFT JOIN categories cat ON p.category_id = cat.id
                WHERE oi.order_id = ?
                ORDER BY oi.id
            ");
            $itemsStmt->execute([$orderId]);
            $orderItems = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }
}
?>

<style>
/* Modern Order Tracking Page - E-commerce Style */
body {
    background: #f5f5f5;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

.tracking-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
    background: #fff;
    min-height: 100vh;
}

/* Search Section */
.search-section {
    background: linear-gradient(135deg, #007bff, #0056b3);
    color: white;
    padding: 40px 30px;
    border-radius: 12px;
    margin-bottom: 30px;
    text-align: center;
}

.search-title {
    font-size: 28px;
    font-weight: 700;
    margin-bottom: 10px;
}

.search-subtitle {
    font-size: 16px;
    opacity: 0.9;
    margin-bottom: 30px;
}

.search-form {
    max-width: 500px;
    margin: 0 auto;
    display: flex;
    gap: 15px;
    align-items: center;
}

.search-input {
    flex: 1;
    padding: 15px 20px;
    border: none;
    border-radius: 8px;
    font-size: 16px;
    outline: none;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.search-btn {
    padding: 15px 30px;
    background: #ff9500;
    color: white;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    font-size: 16px;
    cursor: pointer;
    transition: all 0.3s;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.search-btn:hover {
    background: #e6850e;
    transform: translateY(-2px);
}

/* Messages */
.alert {
    padding: 15px 20px;
    border-radius: 8px;
    margin-bottom: 20px;
    font-weight: 500;
}

.alert-success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.alert-danger {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

/* Order Details Layout */
.order-details-layout {
    display: grid;
    grid-template-columns: 1fr 400px;
    gap: 30px;
    margin-bottom: 30px;
}

/* Order Info */
.order-info-section {
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    overflow: hidden;
}

.order-header {
    background: linear-gradient(135deg, #28a745, #20c997);
    color: white;
    padding: 25px 30px;
    text-align: center;
}

.order-number {
    font-size: 24px;
    font-weight: 700;
    margin-bottom: 10px;
}

.order-date {
    font-size: 16px;
    opacity: 0.9;
}

.order-content {
    padding: 30px;
}

.info-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-bottom: 25px;
}

.info-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 0;
    border-bottom: 1px solid #f0f0f0;
}

.info-item:last-child {
    border-bottom: none;
}

.info-label {
    font-weight: 600;
    color: #666;
    font-size: 14px;
}

.info-value {
    color: #333;
    font-weight: 500;
    font-size: 14px;
    text-align: right;
}

.status-badge {
    padding: 6px 12px;
    border-radius: 15px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
}

.status-pending {
    background: #fff3cd;
    color: #856404;
}

.status-confirmed {
    background: #d1ecf1;
    color: #0c5460;
}

.status-shipped {
    background: #cce5ff;
    color: #004085;
}

.status-delivered {
    background: #d4edda;
    color: #155724;
}

.status-cancelled {
    background: #f8d7da;
    color: #721c24;
}

.status-paid {
    background: #d4edda;
    color: #155724;
}

.status-cod {
    background: #cce5ff;
    color: #004085;
}

/* Timeline Section */
.timeline-section {
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    overflow: hidden;
}

.timeline-header {
    background: #f8f9fa;
    padding: 25px 30px;
    border-bottom: 1px solid #e9ecef;
}

.timeline-title {
    font-size: 20px;
    font-weight: 600;
    color: #333;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 12px;
}

.timeline-content {
    padding: 30px;
}

.timeline {
    position: relative;
    padding: 0;
    margin: 0;
    list-style: none;
}

.timeline::before {
    content: '';
    position: absolute;
    left: 20px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: #e9ecef;
}

.timeline-item {
    position: relative;
    margin-bottom: 30px;
    padding-left: 60px;
}

.timeline-item:last-child {
    margin-bottom: 0;
}

.timeline-dot {
    position: absolute;
    left: 11px;
    top: 8px;
    width: 18px;
    height: 18px;
    border-radius: 50%;
    background: #e9ecef;
    border: 3px solid #fff;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    z-index: 1;
}

.timeline-dot.completed {
    background: #28a745;
}

.timeline-dot.current {
    background: #007bff;
    animation: pulse 2s infinite;
}

.timeline-dot.pending {
    background: #ffc107;
}

@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.1); }
    100% { transform: scale(1); }
}

.timeline-content-item {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 20px;
    border: 1px solid #e9ecef;
}

.timeline-title-item {
    font-size: 16px;
    font-weight: 600;
    color: #333;
    margin-bottom: 8px;
}

.timeline-description {
    color: #666;
    font-size: 14px;
    margin-bottom: 8px;
}

.timeline-date {
    color: #999;
    font-size: 12px;
    font-weight: 500;
}

/* Order Items */
.order-items-section {
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    overflow: hidden;
    margin-bottom: 30px;
}

.items-header {
    background: #f8f9fa;
    padding: 25px 30px;
    border-bottom: 1px solid #e9ecef;
}

.items-title {
    font-size: 20px;
    font-weight: 600;
    color: #333;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 12px;
}

.items-content {
    padding: 30px;
}

.order-item {
    display: flex;
    gap: 20px;
    padding: 20px 0;
    border-bottom: 1px solid #f0f0f0;
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
    border-radius: 8px;
    background: #fafafa;
    flex-shrink: 0;
}

.item-details {
    flex: 1;
}

.item-title {
    font-size: 16px;
    font-weight: 600;
    color: #333;
    margin-bottom: 6px;
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

/* Action Buttons */
.action-section {
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    padding: 30px;
    text-align: center;
}

.action-title {
    font-size: 20px;
    font-weight: 600;
    color: #333;
    margin-bottom: 20px;
}

.action-buttons {
    display: flex;
    gap: 15px;
    justify-content: center;
    flex-wrap: wrap;
}

.btn {
    padding: 12px 24px;
    border: none;
    border-radius: 8px;
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
    transform: translateY(-2px);
}

.btn-success {
    background: #28a745;
    color: white;
}

.btn-success:hover {
    background: #1e7e34;
    color: white;
    text-decoration: none;
    transform: translateY(-2px);
}

.btn-secondary {
    background: #6c757d;
    color: white;
}

.btn-secondary:hover {
    background: #545b62;
    color: white;
    text-decoration: none;
    transform: translateY(-2px);
}

/* Responsive */
@media (max-width: 768px) {
    .order-details-layout {
        grid-template-columns: 1fr;
        gap: 20px;
    }
    
    .info-grid {
        grid-template-columns: 1fr;
    }
    
    .search-form {
        flex-direction: column;
        gap: 15px;
    }
    
    .search-input {
        width: 100%;
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
        align-items: center;
    }
    
    .search-title {
        font-size: 24px;
    }
}
</style>

<div class="tracking-container">
    <!-- Search Section -->
    <div class="search-section">
        <h1 class="search-title">Track Your Order</h1>
        <p class="search-subtitle">Enter your order number to track your delivery status</p>
        
        <form method="POST" class="search-form">
            <input type="text" name="order_number" class="search-input" 
                   placeholder="Enter order number (e.g., ORD20250101001)" 
                   value="<?php echo isset($_POST['order_number']) ? htmlspecialchars($_POST['order_number']) : ''; ?>"
                   required>
            <button type="submit" name="track_order" class="search-btn">
                <i class="fas fa-search"></i> Track Order
            </button>
        </form>
    </div>

    <!-- Messages -->
    <?php if ($success_message): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
        </div>
    <?php endif; ?>

    <?php if ($error_message): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle"></i> <?php echo $error_message; ?>
        </div>
    <?php endif; ?>

    <?php if ($order): ?>
        <!-- Order Details Layout -->
        <div class="order-details-layout">
            <!-- Order Info -->
            <div class="order-info-section">
                <div class="order-header">
                    <div class="order-number"><?php echo htmlspecialchars($order['order_number']); ?></div>
                    <div class="order-date">Placed on <?php echo date('M d, Y', strtotime($order['created_at'])); ?></div>
                </div>
                <div class="order-content">
                    <div class="info-grid">
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
                                <span class="status-badge status-<?php echo strtolower($order['order_status']); ?>">
                                    <?php echo htmlspecialchars($order['order_status']); ?>
                                </span>
                            </span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Total Amount:</span>
                            <span class="info-value">₹<?php echo number_format($order['total_price'], 2); ?></span>
                        </div>
                        <div class="info-item" style="grid-column: 1 / -1;">
                            <span class="info-label">Delivery Address:</span>
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

            <!-- Timeline -->
            <div class="timeline-section">
                <div class="timeline-header">
                    <h3 class="timeline-title">
                        <i class="fas fa-route"></i> Order Timeline
                    </h3>
                </div>
                <div class="timeline-content">
                    <ul class="timeline">
                        <li class="timeline-item">
                            <div class="timeline-dot completed"></div>
                            <div class="timeline-content-item">
                                <div class="timeline-title-item">Order Placed</div>
                                <div class="timeline-description">Your order has been successfully placed</div>
                                <div class="timeline-date"><?php echo date('M d, Y g:i A', strtotime($order['created_at'])); ?></div>
                            </div>
                        </li>
                        
                        <li class="timeline-item">
                            <div class="timeline-dot <?php echo in_array($order['order_status'], ['Confirmed', 'Shipped', 'Delivered']) ? 'completed' : 'current'; ?>"></div>
                            <div class="timeline-content-item">
                                <div class="timeline-title-item">Order Confirmed</div>
                                <div class="timeline-description">Your order has been confirmed and is being prepared</div>
                                <div class="timeline-date"><?php echo in_array($order['order_status'], ['Confirmed', 'Shipped', 'Delivered']) ? date('M d, Y g:i A', strtotime($order['created_at'] . ' +1 day')) : 'Pending'; ?></div>
                            </div>
                        </li>
                        
                        <li class="timeline-item">
                            <div class="timeline-dot <?php echo in_array($order['order_status'], ['Shipped', 'Delivered']) ? 'completed' : ($order['order_status'] === 'Confirmed' ? 'current' : 'pending'); ?>"></div>
                            <div class="timeline-content-item">
                                <div class="timeline-title-item">Preparing for Shipment</div>
                                <div class="timeline-description">Your order is being packed and prepared for shipping</div>
                                <div class="timeline-date"><?php echo in_array($order['order_status'], ['Shipped', 'Delivered']) ? date('M d, Y g:i A', strtotime($order['created_at'] . ' +2 days')) : 'Pending'; ?></div>
                            </div>
                        </li>
                        
                        <li class="timeline-item">
                            <div class="timeline-dot <?php echo $order['order_status'] === 'Delivered' ? 'completed' : ($order['order_status'] === 'Shipped' ? 'current' : 'pending'); ?>"></div>
                            <div class="timeline-content-item">
                                <div class="timeline-title-item">Shipped</div>
                                <div class="timeline-description">Your order has been shipped and is on its way</div>
                                <div class="timeline-date"><?php echo $order['order_status'] === 'Delivered' ? date('M d, Y g:i A', strtotime($order['delivery_date'])) : ($order['order_status'] === 'Shipped' ? date('M d, Y g:i A', strtotime($order['created_at'] . ' +3 days')) : 'Pending'); ?></div>
                            </div>
                        </li>
                        
                        <li class="timeline-item">
                            <div class="timeline-dot <?php echo $order['order_status'] === 'Delivered' ? 'completed' : 'pending'; ?>"></div>
                            <div class="timeline-content-item">
                                <div class="timeline-title-item">Delivered</div>
                                <div class="timeline-description">Your order has been delivered successfully</div>
                                <div class="timeline-date"><?php echo $order['order_status'] === 'Delivered' ? date('M d, Y g:i A', strtotime($order['delivery_date'])) : 'Pending'; ?></div>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Order Items -->
        <?php if ($orderItems): ?>
            <div class="order-items-section">
                <div class="items-header">
                    <h3 class="items-title">
                        <i class="fas fa-box"></i> Ordered Items (<?php echo count($orderItems); ?>)
                    </h3>
                </div>
                <div class="items-content">
                    <?php foreach ($orderItems as $item): ?>
                        <div class="order-item">
                            <img src="<?php echo UPLOAD_URL; ?>/<?php echo htmlspecialchars($item['product_image']); ?>" 
                                 alt="<?php echo htmlspecialchars($item['product_name']); ?>" 
                                 class="item-image"
                                 onerror="this.src='<?php echo IMG_URL; ?>/placeholder-product.png'">
                            
                            <div class="item-details">
                                <h4 class="item-title"><?php echo htmlspecialchars($item['product_name']); ?></h4>
                                <div class="item-brand"><?php echo htmlspecialchars($item['brand_name']); ?></div>
                                
                                <div class="item-specs">
                                    <span class="spec-tag"><?php echo htmlspecialchars($item['star_rating']); ?> ⭐</span>
                                    <span class="spec-tag"><?php echo htmlspecialchars($item['capacity']); ?></span>
                                    <span class="spec-tag"><?php echo htmlspecialchars($item['warranty_years']); ?>Y Warranty</span>
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
            </div>
        <?php endif; ?>

        <!-- Action Section -->
        <div class="action-section">
            <h3 class="action-title">Need Help?</h3>
            <div class="action-buttons">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="order_history.php" class="btn btn-primary">
                        <i class="fas fa-history"></i> View All Orders
                    </a>
                <?php endif; ?>
                
                <a href="<?php echo PUBLIC_URL; ?>/pages/contact.php" class="btn btn-success">
                    <i class="fas fa-headset"></i> Contact Support
                </a>
                
                <a href="<?php echo BASE_URL; ?>/index.php" class="btn btn-secondary">
                    <i class="fas fa-home"></i> Continue Shopping
                </a>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Image loading with fallback
    const images = document.querySelectorAll('img');
    images.forEach(img => {
        img.addEventListener('error', function() {
            if (this.src !== '<?php echo IMG_URL; ?>/placeholder-product.png') {
                this.src = '<?php echo IMG_URL; ?>/placeholder-product.png';
            }
        });
    });
});
</script>

<?php
include INCLUDES_PATH . '/templates/footer.php';
?>

