<?php
// Set page metadata
$pageTitle = 'Order History';
$pageDescription = 'View your order history and track your air conditioning purchases';
$pageKeywords = 'order history, purchase history, order tracking, AC orders';

require_once __DIR__ . '/../../includes/config/init.php';
include INCLUDES_PATH . '/templates/header.php';
// Database connection now in init.php

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // Store current page URL for redirect after login
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    echo "<script>window.location.href='../auth/login.php';</script>";
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch user's orders
$orders_query = $pdo->prepare("
    SELECT o.*, 
           COUNT(oi.id) as item_count,
           SUM(oi.total_price) as calculated_total
    FROM orders o
    LEFT JOIN order_items oi ON o.id = oi.order_id
    WHERE o.user_id = ?
    GROUP BY o.id
    ORDER BY o.created_at DESC
");
$orders_query->execute([$user_id]);
$orders = $orders_query->fetchAll(PDO::FETCH_ASSOC);
?>

<style>
/* Order History Page - Modern & Professional Design */

/* Hero Section */
.order-history-hero {
    background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
    position: relative;
    padding: 120px 0 80px;
    overflow: hidden;
}

.order-history-hero::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: 
        radial-gradient(circle at 20% 50%, rgba(59, 130, 246, 0.1) 0%, transparent 50%),
        radial-gradient(circle at 80% 80%, rgba(168, 85, 247, 0.08) 0%, transparent 50%);
    pointer-events: none;
}

.order-history-hero .container {
    position: relative;
    z-index: 1;
        text-align: center;
    color: white;
}

.order-history-hero h1 {
    font-size: 3.5rem;
    font-weight: 800;
    margin-bottom: 20px;
    background: linear-gradient(135deg, #3b82f6, #8b5cf6);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.order-history-hero p {
    font-size: 1.3rem;
    color: #cbd5e1;
    max-width: 600px;
    margin: 0 auto;
}

/* Orders Content */
.orders-content {
    padding: 80px 0;
    background: linear-gradient(180deg, #ffffff 0%, #f8f9fa 100%);
}

.orders-container {
    max-width: 1200px;
    margin: 0 auto;
}

.order-card {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(15px);
    border: 1px solid rgba(59, 130, 246, 0.1);
    border-radius: 20px;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
    padding: 30px;
    margin-bottom: 30px;
    position: relative;
    overflow: hidden;
        transition: all 0.3s ease;
    }
    
.order-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 25px 50px rgba(0, 0, 0, 0.15);
}

.order-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(135deg, #3b82f6, #8b5cf6);
}

.order-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 20px;
    flex-wrap: wrap;
    gap: 15px;
}

.order-info {
    flex: 1;
    min-width: 250px;
}

.order-number {
    font-size: 1.3rem;
    font-weight: 700;
    color: #1e293b;
    margin-bottom: 8px;
}

.order-date {
    color: #6b7280;
    font-size: 0.95rem;
    margin-bottom: 5px;
}

.order-items {
    color: #6b7280;
    font-size: 0.9rem;
}

.order-status {
    text-align: right;
    flex-shrink: 0;
}

.status-badge {
    display: inline-block;
    padding: 8px 16px;
    border-radius: 20px;
    font-weight: 600;
    font-size: 0.9rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.status-pending {
    background: rgba(251, 191, 36, 0.1);
    color: #d97706;
    border: 1px solid rgba(251, 191, 36, 0.3);
}

.status-processing {
    background: rgba(59, 130, 246, 0.1);
    color: #2563eb;
    border: 1px solid rgba(59, 130, 246, 0.3);
}

.status-shipped {
    background: rgba(34, 197, 94, 0.1);
    color: #059669;
    border: 1px solid rgba(34, 197, 94, 0.3);
}

.status-delivered {
    background: rgba(16, 185, 129, 0.1);
    color: #047857;
    border: 1px solid rgba(16, 185, 129, 0.3);
}

.status-cancelled {
    background: rgba(239, 68, 68, 0.1);
    color: #dc2626;
    border: 1px solid rgba(239, 68, 68, 0.3);
}

.order-details {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 25px;
    padding: 20px;
    background: rgba(59, 130, 246, 0.02);
    border-radius: 12px;
}

.detail-item {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.detail-label {
    font-weight: 600;
    color: #374151;
    font-size: 0.9rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.detail-value {
    color: #1f2937;
    font-weight: 500;
}

.order-total {
    font-size: 1.2rem;
    font-weight: 700;
    color: #059669;
}

.order-actions {
    display: flex;
    gap: 15px;
    justify-content: flex-end;
    flex-wrap: wrap;
}

.btn-primary {
    background: linear-gradient(135deg, #3b82f6, #1d4ed8);
    border: none;
    padding: 10px 25px;
    border-radius: 20px;
    font-weight: 600;
    transition: all 0.3s ease;
    box-shadow: 0 6px 15px rgba(59, 130, 246, 0.3);
    color: white;
    text-decoration: none;
    display: inline-block;
    font-size: 0.9rem;
}

.btn-primary:hover {
    background: linear-gradient(135deg, #2563eb, #1e40af);
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(59, 130, 246, 0.4);
    color: white;
}

.btn-secondary {
    background: linear-gradient(135deg, #6b7280, #4b5563);
    border: none;
    padding: 10px 25px;
    border-radius: 20px;
    font-weight: 600;
    transition: all 0.3s ease;
    box-shadow: 0 6px 15px rgba(107, 114, 128, 0.3);
    color: white;
    text-decoration: none;
    display: inline-block;
    font-size: 0.9rem;
}

.btn-secondary:hover {
    background: linear-gradient(135deg, #4b5563, #374151);
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(107, 114, 128, 0.4);
    color: white;
}

.empty-orders {
    text-align: center;
    padding: 80px 20px;
    color: #6b7280;
}

.empty-orders h3 {
    font-size: 1.8rem;
    margin-bottom: 15px;
    color: #374151;
}

.empty-orders p {
    font-size: 1.1rem;
    margin-bottom: 30px;
}

/* Responsive Design */
@media (max-width: 768px) {
    .order-history-hero h1 {
        font-size: 2.5rem;
    }
    
    .orders-container {
        margin: 20px;
    }
    
    .order-card {
        padding: 25px 20px;
    }
    
    .order-header {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .order-status {
        text-align: left;
        margin-top: 10px;
    }
    
    .order-details {
        grid-template-columns: 1fr;
        gap: 15px;
    }
    
    .order-actions {
        justify-content: center;
        margin-top: 20px;
    }
    
    .btn-primary, .btn-secondary {
        flex: 1;
        max-width: 150px;
        text-align: center;
    }
}

@media (max-width: 480px) {
    .order-actions {
        flex-direction: column;
    }
    
    .btn-primary, .btn-secondary {
        max-width: none;
    }
}
</style>

<!-- Hero Section -->
<section class="order-history-hero">
    <div class="container">
        <h1>Order History</h1>
        <p>Track your air conditioning purchases and order status</p>
    </div>
</section>

<!-- Orders Content -->
<section class="orders-content">
    <div class="container">
        <div class="orders-container">
            <?php if (empty($orders)): ?>
                <div class="empty-orders">
                    <h3>No orders found</h3>
                    <p>You haven't placed any orders yet. Start shopping for amazing air conditioning products!</p>
                    <a href="<?php echo USER_URL; ?>/products/" class="btn btn-primary">Browse Products</a>
                </div>
            <?php else: ?>
                <?php foreach ($orders as $order): ?>
                    <div class="order-card">
                        <div class="order-header">
                                <div class="order-info">
                                <div class="order-number">Order #<?php echo htmlspecialchars($order['order_number']); ?></div>
                                <div class="order-date"><?php echo date('M d, Y', strtotime($order['created_at'])); ?></div>
                                <div class="order-items"><?php echo $order['item_count']; ?> item(s)</div>
                                </div>
                            <div class="order-status">
                                <span class="status-badge status-<?php echo strtolower($order['order_status']); ?>">
                                    <?php echo htmlspecialchars($order['order_status']); ?>
                                    </span>
                                </div>
                        </div>
                        
                        <div class="order-details">
                            <div class="detail-item">
                                <span class="detail-label">Payment Method</span>
                                <span class="detail-value"><?php echo htmlspecialchars($order['payment_method']); ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Payment Status</span>
                                <span class="detail-value"><?php echo htmlspecialchars($order['payment_status']); ?></span>
                                </div>
                            <?php if ($order['delivery_date']): ?>
                                <div class="detail-item">
                                    <span class="detail-label">Delivery Date</span>
                                    <span class="detail-value"><?php echo date('M d, Y', strtotime($order['delivery_date'])); ?></span>
                                </div>
                                    <?php endif; ?>
                            <div class="detail-item">
                                <span class="detail-label">Total Amount</span>
                                <span class="detail-value order-total">₹<?php echo number_format($order['total_price'], 2); ?></span>
                                </div>
        </div>
                        
                        <div class="order-actions">
                            <a href="<?= order_url($order['id']) ?>" class="btn btn-primary">View Details</a>
                            <a href="tracking.php?order=<?php echo $order['id']; ?>" class="btn btn-secondary">Track Order</a>
            </div>
        </div>
                <?php endforeach; ?>
    <?php endif; ?>
</div>
    </div>
</section>

<?php
include INCLUDES_PATH . '/templates/footer.php';
?>

