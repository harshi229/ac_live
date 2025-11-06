<?php
require_once dirname(dirname(__DIR__)) . '/includes/config/init.php';

// Ensure only admins can access this page
if (!isset($_SESSION['admin_id'])) {
    echo "Unauthorized access.";
    exit();
}

// Get order ID from URL path or query parameters
$order_id = 0;

// Check for path-based parameter (e.g., /admin/orders/details/35)
$request_uri = $_SERVER['REQUEST_URI'] ?? '';
$path_parts = explode('/', trim($request_uri, '/'));
$details_index = array_search('details', $path_parts);
if ($details_index !== false && isset($path_parts[$details_index + 1])) {
    $order_id = intval($path_parts[$details_index + 1]);
}

// Fallback to query parameters if path-based not found
if ($order_id <= 0) {
$order_id = isset($_GET['id']) ? intval($_GET['id']) : (isset($_GET['order_id']) ? intval($_GET['order_id']) : 0);
}

if ($order_id <= 0) {
    echo "Invalid order ID. Please check the URL and try again.";
    exit();
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_status':
                $new_status = $_POST['order_status'];
                $payment_status = $_POST['payment_status'];
                $send_email = isset($_POST['send_email']) ? true : false;
                
                try {
                    // Get current order info before updating
                    $currentOrderStmt = $pdo->prepare("SELECT user_id, order_status FROM orders WHERE id = ?");
                    $currentOrderStmt->execute([$order_id]);
                    $currentOrder = $currentOrderStmt->fetch(PDO::FETCH_ASSOC);
                    
                    // Update the order status
                    $stmt = $pdo->prepare("UPDATE orders SET order_status = ?, payment_status = ? WHERE id = ?");
                    $stmt->execute([$new_status, $payment_status, $order_id]);
                    
                    $success_message = "Order status updated successfully!";
                    
                    // Send status update email ONLY if explicitly requested
                    if ($send_email) {
                        require_once INCLUDES_PATH . '/functions/email_helpers.php';
                        $emailSent = sendOrderStatusUpdateEmail($currentOrder['user_id'], $order_id, $new_status);
                        
                        if ($emailSent) {
                            $success_message .= " Customer has been notified via email.";
                        } else {
                            $success_message .= " (Email notification failed)";
                        }
                    }
                } catch (PDOException $e) {
                    $error_message = "Error updating order: " . $e->getMessage();
                }
                break;
                
            case 'send_confirmation_email':
                try {
                    require_once INCLUDES_PATH . '/functions/email_helpers.php';
                    $orderStmt = $pdo->prepare("SELECT user_id FROM orders WHERE id = ?");
                    $orderStmt->execute([$order_id]);
                    $order = $orderStmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($order) {
                        $emailSent = sendOrderConfirmationEmail($order['user_id'], $order_id);
                        
                        if ($emailSent) {
                            $success_message = "Order confirmation email sent successfully!";
                        } else {
                            $error_message = "Failed to send confirmation email. Please try again.";
                        }
                    } else {
                        $error_message = "Order not found.";
                    }
                } catch (Exception $e) {
                    $error_message = "Error sending email: " . $e->getMessage();
                }
                break;
        }
    }
}

// Fetch order details including the delivery date
$order_query = $pdo->prepare("
    SELECT o.*, u.username, u.email, u.phone_number
    FROM orders o 
    JOIN users u ON o.user_id = u.id
    WHERE o.id = ?
");
$order_query->execute([$order_id]);
$order = $order_query->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    echo "Order with ID $order_id not found. Please check if the order exists.";
    exit();
}

// Fetch order items with product details
$items_query = $pdo->prepare("
    SELECT oi.*, p.product_name, p.model_name, p.model_number, p.product_image, b.name as brand_name
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    LEFT JOIN brands b ON p.brand_id = b.id
    WHERE oi.order_id = ?
");
$items_query->execute([$order_id]);
$order_items = $items_query->fetchAll(PDO::FETCH_ASSOC);



?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Details - <?php echo htmlspecialchars($order['order_number']); ?></title>
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/bootstrap.min.css">
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/fontawesome.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        }
        .container-fluid {
            padding: 20px;
        }
        .page-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        .page-header h1 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 10px;
        }
        .order-container {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            margin-bottom: 20px;
        }
        .order-header {
            border-bottom: 2px solid #e9ecef;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .info-section {
            background: #f8fafc;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
            border-left: 4px solid #3b82f6;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .items-table th, .items-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }
        .items-table th {
            background: linear-gradient(135deg, #374151, #1f2937);
            color: white;
            font-weight: 600;
        }
        .items-table tr:hover {
            background-color: #f9fafb;
        }
        .status-badge {
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 0.8rem;
        }
        .status-pending { background: #fef3c7; color: #92400e; }
        .status-confirmed { background: #dbeafe; color: #1e40af; }
        .status-shipped { background: #d1fae5; color: #065f46; }
        .status-delivered { background: #dcfce7; color: #166534; }
        .status-cancelled { background: #fee2e2; color: #991b1b; }
        .payment-pending { background: #fef3c7; color: #92400e; }
        .payment-paid { background: #dcfce7; color: #166534; }
        .payment-failed { background: #fee2e2; color: #991b1b; }
        .btn {
            border-radius: 10px;
            padding: 12px 24px;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
        }
        .btn-primary {
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            box-shadow: 0 4px 15px rgba(59, 130, 246, 0.3);
        }
        .btn-success {
            background: linear-gradient(135deg, #10b981, #059669);
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
        }
        .btn-warning {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            box-shadow: 0 4px 15px rgba(245, 158, 11, 0.3);
        }
        .btn-info {
            background: linear-gradient(135deg, #06b6d4, #0891b2);
            box-shadow: 0 4px 15px rgba(6, 182, 212, 0.3);
        }
        .btn:hover {
            transform: translateY(-2px);
        }
        .alert {
            border-radius: 10px;
            border: none;
            padding: 15px 20px;
            margin-bottom: 20px;
        }
        .alert-success {
            background: linear-gradient(135deg, #d1fae5, #a7f3d0);
            color: #065f46;
            border-left: 4px solid #10b981;
        }
        .alert-danger {
            background: linear-gradient(135deg, #fee2e2, #fecaca);
            color: #991b1b;
            border-left: 4px solid #ef4444;
        }
        .action-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 20px;
        }
        .product-image {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
        }
        .form-control, .form-select {
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            padding: 12px 15px;
            transition: all 0.3s ease;
        }
        .form-control:focus, .form-select:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        .form-label {
            font-weight: 600;
            color: #374151;
            margin-bottom: 8px;
        }
        .status-update-form {
            background: linear-gradient(135deg, #fef3c7, #fde68a);
            border: 2px solid #f59e0b;
            border-radius: 15px;
            padding: 20px;
            margin-top: 20px;
        }
    </style>
</head>
<body>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="page-header">
        <h1><i class="fas fa-shopping-cart me-3"></i>Order Details</h1>
        <h2><?php echo htmlspecialchars($order['order_number']); ?></h2>
        <p>Order placed on <?php echo date('F j, Y \a\t g:i A', strtotime($order['created_at'])); ?></p>
    </div>

    <!-- Display messages -->
    <?php if (isset($success_message)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success_message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($error_message)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error_message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-8">
<div class="order-container">
    <div class="order-header">
                    <h3><i class="fas fa-info-circle me-2"></i>Order Information</h3>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="info-section">
                            <h4><i class="fas fa-user me-2"></i>Customer Information</h4>
                <p><strong>Customer:</strong> <?php echo htmlspecialchars($order['username']); ?></p>
                            <p><strong>Email:</strong> <?php echo htmlspecialchars($order['email']); ?></p>
                            <p><strong>Phone:</strong> <?php echo htmlspecialchars($order['phone_number']); ?></p>
                <p><strong>Customer ID:</strong> <?php echo $order['user_id']; ?></p>
            </div>
        </div>
        <div class="col-md-6">
            <div class="info-section">
                            <h4><i class="fas fa-box me-2"></i>Order Status</h4>
                <p><strong>Order ID:</strong> <?php echo $order['id']; ?></p>
                <p><strong>Status:</strong> 
                    <span class="status-badge status-<?php echo strtolower($order['order_status']); ?>">
                        <?php echo htmlspecialchars($order['order_status']); ?>
                    </span>
                </p>
                <p><strong>Payment Status:</strong> 
                                <span class="status-badge payment-<?php echo strtolower($order['payment_status']); ?>">
                        <?php echo htmlspecialchars($order['payment_status']); ?>
                    </span>
                </p>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="info-section">
                            <h4><i class="fas fa-truck me-2"></i>Shipping Information</h4>
                <p><strong>Shipping Address:</strong></p>
                            <div style="background: white; padding: 15px; border-radius: 8px; border: 1px solid #e5e7eb;">
                    <?php echo nl2br(htmlspecialchars($order['address'])); ?>
                            </div>
                            <p class="mt-2"><strong>Estimated Delivery:</strong> 
                    <?php echo $order['delivery_date'] ? date('F j, Y', strtotime($order['delivery_date'])) : 'To be determined'; ?>
                </p>
            </div>
        </div>
        <div class="col-md-6">
            <div class="info-section">
                            <h4><i class="fas fa-credit-card me-2"></i>Payment Information</h4>
                <p><strong>Payment Method:</strong> <?php echo htmlspecialchars($order['payment_method']); ?></p>
                <p><strong>Total Amount:</strong> <span class="h4 text-success">₹<?php echo number_format($order['total_price'], 2); ?></span></p>
                            <?php if ($order['payment_method'] === 'Cash on Delivery'): ?>
                                <div class="alert alert-info mt-2">
                                    <i class="fas fa-info-circle me-2"></i>
                                    <strong>COD Order:</strong> Payment will be collected upon delivery
                                </div>
                            <?php endif; ?>
            </div>
        </div>
    </div>

    <?php if (!empty($order_items)): ?>
        <div class="info-section">
                        <h4><i class="fas fa-shopping-bag me-2"></i>Order Items</h4>
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
                                            <div class="d-flex align-items-center">
                                                <img src="<?php echo $item['product_image'] ? UPLOAD_URL . '/' . htmlspecialchars($item['product_image']) : IMG_URL . '/no-image.png'; ?>" 
                                                     alt="<?php echo htmlspecialchars($item['product_name']); ?>" 
                                                     class="product-image me-3">
                                                <strong><?php echo htmlspecialchars($item['product_name']); ?></strong>
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars($item['brand_name'] ?? 'N/A'); ?></td>
                            <td>
                                <?php echo htmlspecialchars($item['model_name']); ?>
                                <?php if ($item['model_number']): ?>
                                    <br><small class="text-muted"><?php echo htmlspecialchars($item['model_number']); ?></small>
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
    <?php endif; ?>
            </div>
        </div>

        <div class="col-md-4">
            <div class="order-container">
                <h4><i class="fas fa-cogs me-2"></i>Order Actions</h4>
                
                <!-- Status Update Form -->
                <div class="status-update-form">
                    <h5><i class="fas fa-edit me-2"></i>Update Order Status</h5>
                    <form method="POST">
                        <input type="hidden" name="action" value="update_status">
                        
                        <div class="mb-3">
                            <label class="form-label">Order Status</label>
                            <select class="form-select" name="order_status" required>
                                <option value="Pending" <?= $order['order_status'] == 'Pending' ? 'selected' : '' ?>>Pending</option>
                                <option value="Confirmed" <?= $order['order_status'] == 'Confirmed' ? 'selected' : '' ?>>Confirmed</option>
                                <option value="Shipped" <?= $order['order_status'] == 'Shipped' ? 'selected' : '' ?>>Shipped</option>
                                <option value="Delivered" <?= $order['order_status'] == 'Delivered' ? 'selected' : '' ?>>Delivered</option>
                                <option value="Cancelled" <?= $order['order_status'] == 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Payment Status</label>
                            <select class="form-select" name="payment_status" required>
                                <option value="Pending" <?= $order['payment_status'] == 'Pending' ? 'selected' : '' ?>>Pending</option>
                                <option value="Paid" <?= $order['payment_status'] == 'Paid' ? 'selected' : '' ?>>Paid</option>
                                <option value="Failed" <?= $order['payment_status'] == 'Failed' ? 'selected' : '' ?>>Failed</option>
                            </select>
                        </div>
                        
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" name="send_email" id="sendEmail">
                            <label class="form-check-label" for="sendEmail">
                                Send email notification to customer
                            </label>
                        </div>
                        
                        <button type="submit" class="btn btn-warning w-100">
                            <i class="fas fa-save me-2"></i>Update Status
                        </button>
                    </form>
                </div>

                <!-- Email Actions -->
                <div class="action-buttons">
                    <form method="POST" style="flex: 1;" onsubmit="return confirm('Send confirmation email to this customer?')">
                        <input type="hidden" name="action" value="send_confirmation_email">
                        <button type="submit" class="btn btn-success w-100">
                            <i class="fas fa-envelope me-2"></i>Send Confirmation Email
                        </button>
                    </form>
                    
                    <a href="<?php echo admin_url('orders'); ?>" class="btn btn-primary">
                        <i class="fas fa-arrow-left me-2"></i>Back to Orders
                    </a>
                </div>

                <!-- Order Summary -->
                <div class="info-section mt-3">
                    <h5><i class="fas fa-calculator me-2"></i>Order Summary</h5>
                    <div class="d-flex justify-content-between">
                        <span>Subtotal:</span>
                        <span>₹<?php echo number_format($order['total_price'], 2); ?></span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>Shipping:</span>
                        <span>₹0.00</span>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between">
                        <strong>Total:</strong>
                        <strong>₹<?php echo number_format($order['total_price'], 2); ?></strong>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>

