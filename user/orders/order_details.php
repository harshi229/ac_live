<?php
// Set page metadata
$pageTitle = 'Order Details';
$pageDescription = 'View detailed information about your order';
$pageKeywords = 'order details, order information, order status, order tracking';

require_once __DIR__ . '/../../includes/config/init.php';

// Ensure only logged-in users can access this page
if (!isset($_SESSION['user_id'])) {
    // Store current page URL for redirect after login
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    echo "<script>window.location.href='../auth/login.php';</script>";
    exit();
}

$user_id = $_SESSION['user_id'];
$order_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($order_id <= 0) {
    echo "<script>alert('Invalid order ID.'); window.location.href='history.php';</script>";
    exit();
}

// Fetch order details - ensure user can only see their own orders
$order_query = $pdo->prepare("
    SELECT o.*, u.username, u.email, u.phone_number
    FROM orders o 
    JOIN users u ON o.user_id = u.id
    WHERE o.id = ? AND o.user_id = ?
");
$order_query->execute([$order_id, $user_id]);
$order = $order_query->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    echo "<script>alert('Order not found or access denied.'); window.location.href='history.php';</script>";
    exit();
}

// Fetch order items with product details
$items_query = $pdo->prepare("
    SELECT oi.*, p.product_name, p.model_name, p.model_number, p.product_image, b.name as brand_name
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    LEFT JOIN brands b ON p.brand_id = b.id
    WHERE oi.order_id = ?
    ORDER BY oi.id
");
$items_query->execute([$order_id]);
$order_items = $items_query->fetchAll(PDO::FETCH_ASSOC);

// Include header after successful order fetch
include INCLUDES_PATH . '/templates/header.php';
?>

<style>
/* Order Details Page Styles */
.order-details-container {
    background: #fff;
    border-radius: 12px;
    padding: 30px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    margin: 20px 0;
    border: 1px solid #e9ecef;
}

.order-header {
    border-bottom: 2px solid #e9ecef;
    padding-bottom: 20px;
    margin-bottom: 30px;
}

.order-header h1 {
    color: #2c3e50;
    font-size: 2rem;
    font-weight: 600;
    margin-bottom: 10px;
}

.order-header h2 {
    color: #3498db;
    font-size: 1.5rem;
    font-weight: 500;
    margin-bottom: 5px;
}

.order-header p {
    color: #6c757d;
    font-size: 1rem;
    margin: 0;
}

.info-section {
    background: #f8f9fa;
    padding: 25px;
    border-radius: 10px;
    margin: 20px 0;
    border-left: 4px solid #3498db;
}

.info-section h3 {
    color: #2c3e50;
    font-size: 1.25rem;
    font-weight: 600;
    margin-bottom: 15px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.info-section h3 i {
    color: #3498db;
    font-size: 1.1rem;
}

.info-section p {
    margin-bottom: 8px;
    color: #495057;
    line-height: 1.5;
}

.info-section p strong {
    color: #2c3e50;
    font-weight: 600;
}

.items-table {
    width: 100%;
    border-collapse: collapse;
    margin: 20px 0;
    background: #fff;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

.items-table th, .items-table td {
    padding: 15px 12px;
    text-align: left;
    border-bottom: 1px solid #dee2e6;
    vertical-align: middle;
}

.items-table th {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    font-weight: 600;
    text-transform: uppercase;
    font-size: 0.85rem;
    letter-spacing: 0.5px;
}

.items-table tbody tr:hover {
    background-color: #f8f9fa;
}

.items-table tbody tr:last-child td {
    border-bottom: none;
}

.status-badge {
    padding: 6px 16px;
    border-radius: 20px;
    font-weight: 600;
    font-size: 0.85rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    display: inline-block;
}

.status-pending { 
    background: linear-gradient(135deg, #fff3cd, #ffeaa7); 
    color: #856404; 
    border: 1px solid #ffeaa7;
}
.status-confirmed { 
    background: linear-gradient(135deg, #d1ecf1, #74b9ff); 
    color: #0c5460; 
    border: 1px solid #74b9ff;
}
.status-shipped { 
    background: linear-gradient(135deg, #cce5ff, #0984e3); 
    color: #004085; 
    border: 1px solid #0984e3;
}
.status-delivered { 
    background: linear-gradient(135deg, #d4edda, #00b894); 
    color: #155724; 
    border: 1px solid #00b894;
}
.status-cancelled { 
    background: linear-gradient(135deg, #f8d7da, #e17055); 
    color: #721c24; 
    border: 1px solid #e17055;
}

.product-image {
    width: 60px;
    height: 60px;
    object-fit: cover;
    border-radius: 8px;
    border: 2px solid #e9ecef;
    transition: transform 0.3s ease;
}

.product-image:hover {
    transform: scale(1.05);
}

.product-info {
    display: flex;
    align-items: center;
    gap: 15px;
}

.product-details {
    flex: 1;
}

.product-name {
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 4px;
    font-size: 1rem;
}

.brand-name {
    color: #6c757d;
    font-size: 0.9rem;
    font-weight: 500;
}

.model-info {
    color: #495057;
    font-size: 0.9rem;
}

.model-number {
    color: #6c757d;
    font-size: 0.8rem;
    font-style: italic;
}

.order-actions {
    margin-top: 40px;
    text-align: center;
    padding-top: 20px;
    border-top: 1px solid #e9ecef;
}

.order-actions .btn {
    margin: 0 8px;
    padding: 12px 24px;
    font-weight: 600;
    border-radius: 8px;
    text-decoration: none;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.order-actions .btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
    color: white;
}

.order-actions .btn-primary:hover {
    background: linear-gradient(135deg, #5a6fd8 0%, #6a4190 100%);
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
}

.order-actions .btn-secondary {
    background: #6c757d;
    border: none;
    color: white;
}

.order-actions .btn-secondary:hover {
    background: #5a6268;
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(108, 117, 125, 0.4);
}

.badge {
    padding: 6px 12px;
    border-radius: 6px;
    font-weight: 600;
    font-size: 0.8rem;
}

.bg-success {
    background-color: #28a745 !important;
}

.bg-warning {
    background-color: #ffc107 !important;
    color: #212529 !important;
}

.text-primary {
    color: #3498db !important;
}

.text-muted {
    color: #6c757d !important;
}

.text-success {
    color: #28a745 !important;
}

/* Responsive Design */
@media (max-width: 768px) {
    .order-details-container {
        padding: 20px 15px;
        margin: 10px 0;
        border-radius: 8px;
    }
    
    .order-header h1 {
        font-size: 1.5rem;
    }
    
    .order-header h2 {
        font-size: 1.25rem;
    }
    
    .info-section {
        padding: 20px 15px;
        margin: 15px 0;
    }
    
    .info-section h3 {
        font-size: 1.1rem;
    }
    
    .items-table {
        font-size: 0.9rem;
        margin: 15px 0;
    }
    
    .items-table th, .items-table td {
        padding: 10px 8px;
    }
    
    .product-info {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }
    
    .product-image {
        width: 50px;
        height: 50px;
    }
    
    .order-actions .btn {
        margin: 5px;
        padding: 10px 20px;
        font-size: 0.9rem;
    }
}

@media (max-width: 576px) {
    .order-details-container {
        padding: 15px 10px;
    }
    
    .items-table {
        font-size: 0.8rem;
    }
    
    .items-table th, .items-table td {
        padding: 8px 6px;
    }
    
    .order-actions .btn {
        display: block;
        width: 100%;
        margin: 5px 0;
    }
}
</style>

<div class="container">
    <div class="order-details-container">
        <div class="order-header">
            <h1><i class="fas fa-receipt"></i> Order Details</h1>
            <h2 class="text-primary"><?php echo htmlspecialchars($order['order_number']); ?></h2>
            <p class="text-muted">Order placed on <?php echo date('F j, Y \a\t g:i A', strtotime($order['created_at'])); ?></p>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="info-section">
                    <h3><i class="fas fa-user"></i> Customer Information</h3>
                    <p><strong>Name:</strong> <?php echo htmlspecialchars($order['username']); ?></p>
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($order['email']); ?></p>
                    <?php if ($order['phone_number']): ?>
                        <p><strong>Phone:</strong> <?php echo htmlspecialchars($order['phone_number']); ?></p>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-md-6">
                <div class="info-section">
                    <h3><i class="fas fa-box"></i> Order Information</h3>
                    <p><strong>Order ID:</strong> #<?php echo $order['id']; ?></p>
                    <p><strong>Status:</strong> 
                        <span class="status-badge status-<?php echo strtolower($order['order_status']); ?>">
                            <?php echo htmlspecialchars($order['order_status']); ?>
                        </span>
                    </p>
                    <p><strong>Payment Status:</strong> 
                        <span class="badge bg-<?php echo $order['payment_status'] === 'Paid' ? 'success' : 'warning'; ?>">
                            <?php echo htmlspecialchars($order['payment_status']); ?>
                        </span>
                    </p>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="info-section">
                    <h3><i class="fas fa-truck"></i> Shipping Information</h3>
                    <p><strong>Shipping Address:</strong></p>
                    <div style="background: white; padding: 15px; border-radius: 5px; border: 1px solid #dee2e6;">
                        <?php echo nl2br(htmlspecialchars($order['address'])); ?>
                    </div>
                    <?php if ($order['delivery_date']): ?>
                        <p class="mt-3"><strong>Estimated Delivery:</strong> 
                            <span class="text-success"><?php echo date('F j, Y', strtotime($order['delivery_date'])); ?></span>
                        </p>
                    <?php else: ?>
                        <p class="mt-3"><strong>Estimated Delivery:</strong> 
                            <span class="text-muted">To be determined</span>
                        </p>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-md-6">
                <div class="info-section">
                    <h3><i class="fas fa-credit-card"></i> Payment Information</h3>
                    <p><strong>Payment Method:</strong> <?php echo htmlspecialchars($order['payment_method']); ?></p>
                    <p><strong>Total Amount:</strong> 
                        <span class="h4 text-success">₹<?php echo number_format($order['total_price'], 2); ?></span>
                    </p>
                </div>
            </div>
        </div>

        <?php if (!empty($order_items)): ?>
            <div class="info-section">
                <h3><i class="fas fa-shopping-cart"></i> Order Items</h3>
                <div class="table-responsive">
                    <table class="items-table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Brand</th>
                                <th>Model</th>
                                <th>Quantity</th>
                                <th>Unit Price</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($order_items as $item): ?>
                                <tr>
                                    <td>
                                        <div class="product-info">
                                            <?php if ($item['product_image']): ?>
                                                <img src="<?php echo UPLOAD_URL; ?>/<?php echo htmlspecialchars($item['product_image']); ?>" 
                                                     alt="<?php echo htmlspecialchars($item['product_name']); ?>" 
                                                     class="product-image">
                                            <?php else: ?>
                                                <img src="<?php echo IMG_URL; ?>/placeholder-product.png" 
                                                     alt="No image" 
                                                     class="product-image">
                                            <?php endif; ?>
                                            <div class="product-details">
                                                <div class="product-name"><?php echo htmlspecialchars($item['product_name']); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="brand-name"><?php echo htmlspecialchars($item['brand_name'] ?? 'N/A'); ?></div>
                                    </td>
                                    <td>
                                        <div class="model-info"><?php echo htmlspecialchars($item['model_name']); ?></div>
                                        <?php if ($item['model_number']): ?>
                                            <div class="model-number"><?php echo htmlspecialchars($item['model_number']); ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo $item['quantity']; ?></td>
                                    <td>₹<?php echo number_format($item['unit_price'], 2); ?></td>
                                    <td><strong>₹<?php echo number_format($item['total_price'], 2); ?></strong></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

        <div class="order-actions">
            <a href="history.php" class="btn btn-primary">
                <i class="fas fa-arrow-left"></i> Back to Order History
            </a>
            <a href="tracking.php?order=<?php echo $order_id; ?>" class="btn btn-secondary">
                <i class="fas fa-truck"></i> Track Order
            </a>
        </div>
    </div>
</div>

<?php
include INCLUDES_PATH . '/templates/footer.php';
?>
