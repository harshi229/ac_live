<?php
require_once dirname(dirname(__DIR__)) . '/includes/config/init.php';

// Ensure the admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: ' . admin_url('login'));
    exit();
}

include INCLUDES_PATH . '/templates/admin_header.php';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_status':
                $order_id = intval($_POST['order_id']);
                $new_status = $_POST['order_status'];
                $payment_status = $_POST['payment_status'];
                $send_email = isset($_POST['send_email']) ? true : false;
                
                try {
                    // Get current order info before updating
                    $currentOrderStmt = $pdo->prepare("SELECT user_id, order_status, payment_status FROM orders WHERE id = ?");
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
                $order_id = intval($_POST['order_id']);
                
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
                
            case 'bulk_update':
                if (isset($_POST['selected_orders']) && $_POST['bulk_status']) {
                    $order_ids = $_POST['selected_orders'];
                    $bulk_status = $_POST['bulk_status'];
                    $send_emails = isset($_POST['send_emails']) ? true : false;
                    
                    try {
                        require_once INCLUDES_PATH . '/functions/email_helpers.php';
                        $emailsSent = 0;
                        $emailsFailed = 0;
                        
                        foreach ($order_ids as $order_id) {
                            // Get current order info
                            $currentOrderStmt = $pdo->prepare("SELECT user_id, order_status FROM orders WHERE id = ?");
                            $currentOrderStmt->execute([$order_id]);
                            $currentOrder = $currentOrderStmt->fetch(PDO::FETCH_ASSOC);
                            
                            // Update order status
                            $stmt = $pdo->prepare("UPDATE orders SET order_status = ? WHERE id = ?");
                            $stmt->execute([$bulk_status, $order_id]);
                            
                            // Send email if requested or if status changed
                            if ($send_emails || ($currentOrder && $currentOrder['order_status'] !== $bulk_status)) {
                                $emailSent = sendOrderStatusUpdateEmail($currentOrder['user_id'], $order_id, $bulk_status);
                                if ($emailSent) {
                                    $emailsSent++;
                                } else {
                                    $emailsFailed++;
                                }
                            }
                        }
                        
                        $success_message = count($order_ids) . " orders updated successfully!";
                        if ($emailsSent > 0) {
                            $success_message .= " $emailsSent customers notified via email.";
                        }
                        if ($emailsFailed > 0) {
                            $success_message .= " ($emailsFailed email notifications failed)";
                        }
                    } catch (PDOException $e) {
                        $error_message = "Error updating orders: " . $e->getMessage();
                    }
                }
                break;
                
            case 'bulk_send_emails':
                if (isset($_POST['selected_orders'])) {
                    $order_ids = $_POST['selected_orders'];
                    $email_type = $_POST['email_type'];
                    
                    try {
                        require_once INCLUDES_PATH . '/functions/email_helpers.php';
                        $emailsSent = 0;
                        $emailsFailed = 0;
                        
                        foreach ($order_ids as $order_id) {
                            $orderStmt = $pdo->prepare("SELECT user_id FROM orders WHERE id = ?");
                            $orderStmt->execute([$order_id]);
                            $order = $orderStmt->fetch(PDO::FETCH_ASSOC);
                            
                            if ($order) {
                                $emailSent = false;
                                
                                if ($email_type === 'confirmation') {
                                    $emailSent = sendOrderConfirmationEmail($order['user_id'], $order_id);
                                } elseif ($email_type === 'status_update') {
                                    $statusStmt = $pdo->prepare("SELECT order_status FROM orders WHERE id = ?");
                                    $statusStmt->execute([$order_id]);
                                    $status = $statusStmt->fetchColumn();
                                    $emailSent = sendOrderStatusUpdateEmail($order['user_id'], $order_id, $status);
                                }
                                
                                if ($emailSent) {
                                    $emailsSent++;
                                } else {
                                    $emailsFailed++;
                                }
                            }
                        }
                        
                        $success_message = "$emailsSent emails sent successfully!";
                        if ($emailsFailed > 0) {
                            $success_message .= " ($emailsFailed emails failed)";
                        }
                    } catch (Exception $e) {
                        $error_message = "Error sending emails: " . $e->getMessage();
                    }
                }
                break;
        }
    }
}

// Get filter parameters
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$payment_filter = isset($_GET['payment']) ? $_GET['payment'] : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$sort_by = isset($_GET['sort']) ? $_GET['sort'] : 'newest';

// Build query conditions
$conditions = ["1=1"];
$params = [];

if ($status_filter) {
    $conditions[] = "o.order_status = ?";
    $params[] = $status_filter;
}

if ($payment_filter) {
    $conditions[] = "o.payment_status = ?";
    $params[] = $payment_filter;
}

if ($date_from) {
    $conditions[] = "DATE(o.created_at) >= ?";
    $params[] = $date_from;
}

if ($date_to) {
    $conditions[] = "DATE(o.created_at) <= ?";
    $params[] = $date_to;
}

if ($search) {
    $conditions[] = "(o.order_number LIKE ? OR u.username LIKE ? OR u.email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

// Build ORDER BY clause
$order_clause = "ORDER BY ";
switch ($sort_by) {
    case 'oldest':
        $order_clause .= "o.created_at ASC";
        break;
    case 'amount_high':
        $order_clause .= "o.total_price DESC";
        break;
    case 'amount_low':
        $order_clause .= "o.total_price ASC";
        break;
    case 'customer':
        $order_clause .= "u.username ASC";
        break;
    case 'newest':
    default:
        $order_clause .= "o.created_at DESC";
        break;
}

try {
    // Main orders query
    $sql = "SELECT o.*, u.username, u.email, u.phone_number,
                   COUNT(oi.id) as item_count,
                   GROUP_CONCAT(DISTINCT p.product_name SEPARATOR ', ') as products
            FROM orders o
            JOIN users u ON o.user_id = u.id
            LEFT JOIN order_items oi ON o.id = oi.order_id
            LEFT JOIN products p ON oi.product_id = p.id
            WHERE " . implode(' AND ', $conditions) . "
            GROUP BY o.id
            $order_clause";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get statistics
    $stats = [
        'total' => $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn(),
        'pending' => $pdo->query("SELECT COUNT(*) FROM orders WHERE order_status = 'Pending'")->fetchColumn(),
        'confirmed' => $pdo->query("SELECT COUNT(*) FROM orders WHERE order_status = 'Confirmed'")->fetchColumn(),
        'shipped' => $pdo->query("SELECT COUNT(*) FROM orders WHERE order_status = 'Shipped'")->fetchColumn(),
        'delivered' => $pdo->query("SELECT COUNT(*) FROM orders WHERE order_status = 'Delivered'")->fetchColumn(),
        'cancelled' => $pdo->query("SELECT COUNT(*) FROM orders WHERE order_status = 'Cancelled'")->fetchColumn(),
        'total_revenue' => $pdo->query("SELECT COALESCE(SUM(total_price), 0) FROM orders WHERE order_status != 'Cancelled'")->fetchColumn(),
        'pending_revenue' => $pdo->query("SELECT COALESCE(SUM(total_price), 0) FROM orders WHERE order_status = 'Pending'")->fetchColumn(),
        'cod_orders' => $pdo->query("SELECT COUNT(*) FROM orders WHERE payment_method = 'Cash on Delivery'")->fetchColumn(),
        'paid_orders' => $pdo->query("SELECT COUNT(*) FROM orders WHERE payment_status = 'Paid'")->fetchColumn()
    ];
    
} catch (PDOException $e) {
    $error_message = "Error fetching orders: " . $e->getMessage();
    $orders = [];
    $stats = ['total' => 0, 'pending' => 0, 'confirmed' => 0, 'shipped' => 0, 'delivered' => 0, 'cancelled' => 0, 'total_revenue' => 0, 'pending_revenue' => 0, 'cod_orders' => 0, 'paid_orders' => 0];
}
?>

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
    
    .page-header p {
        font-size: 1.1rem;
        opacity: 0.9;
        margin: 0;
    }
    
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }
    
    .stat-card {
        background: white;
        padding: 25px;
        border-radius: 15px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        text-align: center;
        transition: all 0.3s ease;
        border-left: 4px solid;
    }
    
    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 35px rgba(0,0,0,0.15);
    }
    
    .stat-card.primary { border-left-color: #3b82f6; }
    .stat-card.success { border-left-color: #10b981; }
    .stat-card.warning { border-left-color: #f59e0b; }
    .stat-card.info { border-left-color: #06b6d4; }
    .stat-card.danger { border-left-color: #ef4444; }
    .stat-card.purple { border-left-color: #8b5cf6; }
    
    .stat-number {
        font-size: 2.5rem;
        font-weight: 700;
        margin-bottom: 5px;
    }
    
    .stat-label {
        color: #6b7280;
        font-size: 0.9rem;
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .filter-section {
        background: white;
        padding: 25px;
        border-radius: 15px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        margin-bottom: 30px;
    }
    
    .filter-section h5 {
        color: #374151;
        font-weight: 600;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .filter-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 15px;
        align-items: end;
    }
    
    .form-label {
        font-weight: 600;
        color: #374151;
        margin-bottom: 8px;
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
    
    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(59, 130, 246, 0.4);
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
    
    .btn-danger {
        background: linear-gradient(135deg, #ef4444, #dc2626);
        box-shadow: 0 4px 15px rgba(239, 68, 68, 0.3);
    }
    
    .bulk-actions {
        background: linear-gradient(135deg, #f3f4f6, #e5e7eb);
        padding: 20px;
        border-radius: 15px;
        margin-bottom: 20px;
        display: none;
        border: 2px solid #d1d5db;
    }
    
    .bulk-actions.show {
        display: block;
        animation: slideDown 0.3s ease;
    }
    
    @keyframes slideDown {
        from { opacity: 0; transform: translateY(-10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .order-card {
        background: white;
        border-radius: 15px;
        margin-bottom: 20px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        transition: all 0.3s ease;
        overflow: hidden;
    }
    
    .order-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 15px 35px rgba(0,0,0,0.15);
    }
    
    .order-header {
        background: linear-gradient(135deg, #f8fafc, #f1f5f9);
        padding: 20px 25px;
        border-bottom: 1px solid #e2e8f0;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 15px;
    }
    
    .order-body {
        padding: 25px;
    }
    
    .order-details {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
    }
    
    .detail-item {
        background: #f8fafc;
        padding: 15px;
        border-radius: 10px;
        border-left: 4px solid #3b82f6;
    }
    
    .detail-label {
        font-size: 0.8rem;
        color: #6b7280;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 5px;
    }
    
    .detail-value {
        font-size: 1rem;
        color: #1f2937;
        font-weight: 500;
    }
    
    .status-badge {
        padding: 8px 16px;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .status-pending { background: #fef3c7; color: #92400e; }
    .status-confirmed { background: #dbeafe; color: #1e40af; }
    .status-shipped { background: #d1fae5; color: #065f46; }
    .status-delivered { background: #dcfce7; color: #166534; }
    .status-cancelled { background: #fee2e2; color: #991b1b; }
    
    .payment-pending { background: #fef3c7; color: #92400e; }
    .payment-paid { background: #dcfce7; color: #166534; }
    .payment-failed { background: #fee2e2; color: #991b1b; }
    
    .quick-edit {
        background: linear-gradient(135deg, #fef3c7, #fde68a);
        border: 2px solid #f59e0b;
        border-radius: 15px;
        padding: 20px;
        margin-top: 20px;
        display: none;
    }
    
    .quick-edit.show {
        display: block;
        animation: slideDown 0.3s ease;
    }
    
    .action-buttons {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
    }
    
    .btn-sm {
        padding: 8px 16px;
        font-size: 0.875rem;
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
    
    .empty-state {
        text-align: center;
        padding: 60px 20px;
        background: white;
        border-radius: 15px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.08);
    }
    
    .empty-state i {
        font-size: 4rem;
        color: #d1d5db;
        margin-bottom: 20px;
    }
    
    .empty-state h5 {
        color: #6b7280;
        margin-bottom: 10px;
    }
    
    .empty-state p {
        color: #9ca3af;
        margin: 0;
    }
    
    .results-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        padding: 15px 20px;
        background: white;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    }
    
    .results-count {
        font-weight: 600;
        color: #374151;
    }
    
    .results-count strong {
        color: #3b82f6;
        font-size: 1.2rem;
    }
    
    @media (max-width: 768px) {
        .filter-row {
            grid-template-columns: 1fr;
        }
        
        .order-header {
            flex-direction: column;
            text-align: center;
        }
        
        .order-details {
            grid-template-columns: 1fr;
        }
        
        .action-buttons {
            justify-content: center;
        }
        
        .stats-grid {
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        }
    }
</style>

<main>
    <div class="container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <h1><i class="fas fa-shopping-cart me-3"></i>Order Management</h1>
            <p>Comprehensive order management system with email notifications and status tracking</p>
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

        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card primary">
                <div class="stat-number"><?php echo $stats['total']; ?></div>
                <div class="stat-label">Total Orders</div>
                </div>
                <div class="stat-card warning">
                <div class="stat-number"><?php echo $stats['pending']; ?></div>
                <div class="stat-label">Pending</div>
                </div>
                <div class="stat-card info">
                <div class="stat-number"><?php echo $stats['confirmed']; ?></div>
                <div class="stat-label">Confirmed</div>
                </div>
                <div class="stat-card success">
                <div class="stat-number"><?php echo $stats['delivered']; ?></div>
                <div class="stat-label">Delivered</div>
                </div>
            <div class="stat-card danger">
                <div class="stat-number"><?php echo $stats['cancelled']; ?></div>
                <div class="stat-label">Cancelled</div>
            </div>
            <div class="stat-card purple">
                <div class="stat-number">₹<?php echo number_format($stats['total_revenue'], 0); ?></div>
                <div class="stat-label">Total Revenue</div>
                </div>
            <div class="stat-card info">
                <div class="stat-number"><?php echo $stats['cod_orders']; ?></div>
                <div class="stat-label">COD Orders</div>
            </div>
            <div class="stat-card success">
                <div class="stat-number"><?php echo $stats['paid_orders']; ?></div>
                <div class="stat-label">Paid Orders</div>
            </div>
        </div>

        <!-- Filter Section -->
        <div class="filter-section">
            <h5><i class="fas fa-filter"></i> Filter & Search Orders</h5>
            <form method="GET">
                <div class="filter-row">
                    <div>
                        <label for="search" class="form-label">Search</label>
                        <input type="text" 
                               class="form-control" 
                               name="search" 
                               id="search" 
                               value="<?= htmlspecialchars($search) ?>" 
                               placeholder="Order number, customer name, email...">
                    </div>
                    
                    <div>
                        <label for="status" class="form-label">Order Status</label>
                        <select class="form-select" name="status" id="status">
                            <option value="">All Status</option>
                            <option value="Pending" <?= $status_filter == 'Pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="Confirmed" <?= $status_filter == 'Confirmed' ? 'selected' : '' ?>>Confirmed</option>
                            <option value="Shipped" <?= $status_filter == 'Shipped' ? 'selected' : '' ?>>Shipped</option>
                            <option value="Delivered" <?= $status_filter == 'Delivered' ? 'selected' : '' ?>>Delivered</option>
                            <option value="Cancelled" <?= $status_filter == 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
                        </select>
                    </div>
                    
                    <div>
                        <label for="payment" class="form-label">Payment Status</label>
                        <select class="form-select" name="payment" id="payment">
                            <option value="">All Payments</option>
                            <option value="Pending" <?= $payment_filter == 'Pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="Paid" <?= $payment_filter == 'Paid' ? 'selected' : '' ?>>Paid</option>
                            <option value="Failed" <?= $payment_filter == 'Failed' ? 'selected' : '' ?>>Failed</option>
                        </select>
                    </div>
                    
                    <div>
                        <label for="date_from" class="form-label">From Date</label>
                        <input type="date" 
                               class="form-control" 
                               name="date_from" 
                               id="date_from" 
                               value="<?= htmlspecialchars($date_from) ?>">
                    </div>
                    
                    <div>
                        <label for="date_to" class="form-label">To Date</label>
                        <input type="date" 
                               class="form-control" 
                               name="date_to" 
                               id="date_to" 
                               value="<?= htmlspecialchars($date_to) ?>">
                    </div>
                    
                    <div>
                        <label for="sort" class="form-label">Sort By</label>
                        <select class="form-select" name="sort" id="sort">
                            <option value="newest" <?= $sort_by == 'newest' ? 'selected' : '' ?>>Newest First</option>
                            <option value="oldest" <?= $sort_by == 'oldest' ? 'selected' : '' ?>>Oldest First</option>
                            <option value="amount_high" <?= $sort_by == 'amount_high' ? 'selected' : '' ?>>Amount High-Low</option>
                            <option value="amount_low" <?= $sort_by == 'amount_low' ? 'selected' : '' ?>>Amount Low-High</option>
                            <option value="customer" <?= $sort_by == 'customer' ? 'selected' : '' ?>>Customer Name</option>
                        </select>
                    </div>
                    
                    <div>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-search me-2"></i>Filter
                        </button>
                    </div>
                    
                    <div>
                        <a href="<?php echo admin_url('orders'); ?>" class="btn btn-outline-secondary w-100">
                            <i class="fas fa-refresh me-2"></i>Reset
                        </a>
                    </div>
                </div>
            </form>
        </div>

        <!-- Results Header -->
        <div class="results-header">
            <div class="results-count">
                <strong><?php echo count($orders); ?></strong> orders found
            </div>
            <div>
                <button type="button" class="btn btn-outline-primary" id="toggleBulkActions">
                    <i class="fas fa-tasks me-2"></i>Bulk Actions
                </button>
            </div>
        </div>

        <!-- Bulk Actions -->
        <div class="bulk-actions" id="bulkActions">
            <form method="POST" id="bulkForm">
                <input type="hidden" name="action" value="bulk_update">
                <div class="row align-items-center">
                    <div class="col-md-3">
                        <label class="form-label">Update Status</label>
                        <select class="form-select" name="bulk_status" required>
                            <option value="">Choose Status</option>
                            <option value="Confirmed">Confirm Selected</option>
                            <option value="Shipped">Mark as Shipped</option>
                            <option value="Delivered">Mark as Delivered</option>
                            <option value="Cancelled">Cancel Selected</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="send_emails" id="sendEmails">
                            <label class="form-check-label" for="sendEmails">
                                Send Emails
                            </label>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-warning" onclick="return confirm('Update status for selected orders?')">
                            Apply to Selected
                        </button>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Send Emails</label>
                        <select class="form-select" name="email_type" form="bulkEmailForm">
                            <option value="confirmation">Order Confirmation</option>
                            <option value="status_update">Status Update</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" form="bulkEmailForm" class="btn btn-info" onclick="return confirm('Send emails to selected orders?')">
                            Send Emails
                        </button>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-12">
                        <span id="selectedCount" class="badge bg-primary">0</span> orders selected
                    </div>
                </div>
            </form>
            
            <form method="POST" id="bulkEmailForm" style="display: none;">
                <input type="hidden" name="action" value="bulk_send_emails">
                <input type="hidden" name="email_type" value="confirmation">
            </form>
        </div>

        <!-- Orders List -->
        <?php if (empty($orders)): ?>
            <div class="empty-state">
                <i class="fas fa-shopping-cart"></i>
                <h5>No orders found</h5>
                <p>Try adjusting your filters or check back later</p>
            </div>
        <?php else: ?>
            <?php foreach ($orders as $order): ?>
                <div class="order-card">
                    <div class="order-header">
                        <div class="d-flex align-items-center gap-3">
                            <input type="checkbox" name="selected_orders[]" value="<?= $order['id'] ?>" class="form-check-input order-checkbox">
                            <div>
                                <h4 class="mb-1">Order #<?= htmlspecialchars($order['order_number'] ?: $order['id']) ?></h4>
                                <small class="text-muted"><?= date('M j, Y g:i A', strtotime($order['created_at'])) ?></small>
                            </div>
                        </div>
                        <div class="d-flex gap-2 align-items-center flex-wrap">
                            <span class="status-badge status-<?= strtolower($order['order_status']) ?>">
                                <?= htmlspecialchars($order['order_status']) ?>
                            </span>
                            <span class="status-badge payment-<?= strtolower($order['payment_status']) ?>">
                                <?= htmlspecialchars($order['payment_status']) ?>
                            </span>
                            <div class="action-buttons">
                                <button type="button" class="btn btn-outline-primary btn-sm" onclick="toggleQuickEdit(<?= $order['id'] ?>)">
                                    <i class="fas fa-edit me-1"></i>Quick Edit
                                </button>
                                <a href="<?php echo admin_url('orders/details/' . $order['id']); ?>" class="btn btn-outline-info btn-sm">
                                    <i class="fas fa-eye me-1"></i>Details
                                </a>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Send confirmation email to this customer?')">
                                    <input type="hidden" name="action" value="send_confirmation_email">
                                    <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                    <button type="submit" class="btn btn-success btn-sm">
                                        <i class="fas fa-envelope me-1"></i>Send Email
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <div class="order-body">
                        <div class="order-details">
                            <div class="detail-item">
                                <div class="detail-label">Customer</div>
                                <div class="detail-value"><?= htmlspecialchars($order['username']) ?></div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Contact</div>
                                <div class="detail-value">
                                    <?= htmlspecialchars($order['phone_number']) ?><br>
                                    <small><?= htmlspecialchars($order['email']) ?></small>
                                </div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Items</div>
                                <div class="detail-value"><?= $order['item_count'] ?> item(s)</div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Total Amount</div>
                                <div class="detail-value">
                                    <strong>₹<?= number_format($order['total_price'], 2) ?></strong>
                                </div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Payment Method</div>
                                <div class="detail-value"><?= htmlspecialchars($order['payment_method']) ?></div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Products</div>
                                <div class="detail-value">
                                    <small><?= htmlspecialchars($order['products'] ? substr($order['products'], 0, 100) . (strlen($order['products']) > 100 ? '...' : '') : 'N/A') ?></small>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Quick Edit Form -->
                        <div class="quick-edit" id="quickEdit_<?= $order['id'] ?>">
                            <form method="POST" class="row g-3">
                                <input type="hidden" name="action" value="update_status">
                                <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                
                                <div class="col-md-4">
                                    <label class="form-label">Order Status</label>
                                    <select class="form-select" name="order_status" required>
                                        <option value="Pending" <?= $order['order_status'] == 'Pending' ? 'selected' : '' ?>>Pending</option>
                                        <option value="Confirmed" <?= $order['order_status'] == 'Confirmed' ? 'selected' : '' ?>>Confirmed</option>
                                        <option value="Shipped" <?= $order['order_status'] == 'Shipped' ? 'selected' : '' ?>>Shipped</option>
                                        <option value="Delivered" <?= $order['order_status'] == 'Delivered' ? 'selected' : '' ?>>Delivered</option>
                                        <option value="Cancelled" <?= $order['order_status'] == 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                    </select>
                                </div>
                                
                                <div class="col-md-4">
                                    <label class="form-label">Payment Status</label>
                                    <select class="form-select" name="payment_status" required>
                                        <option value="Pending" <?= $order['payment_status'] == 'Pending' ? 'selected' : '' ?>>Pending</option>
                                        <option value="Paid" <?= $order['payment_status'] == 'Paid' ? 'selected' : '' ?>>Paid</option>
                                        <option value="Failed" <?= $order['payment_status'] == 'Failed' ? 'selected' : '' ?>>Failed</option>
                                    </select>
                                </div>
                                
                                <div class="col-md-4">
                                    <label class="form-label">&nbsp;</label>
                                    <div class="d-flex gap-2">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="send_email" id="sendEmail_<?= $order['id'] ?>">
                                            <label class="form-check-label" for="sendEmail_<?= $order['id'] ?>">
                                                Send Email
                                            </label>
                                        </div>
                                    </div>
                                    <div class="d-flex gap-2 mt-2">
                                        <button type="submit" class="btn btn-success">Update</button>
                                        <button type="button" class="btn btn-secondary" onclick="toggleQuickEdit(<?= $order['id'] ?>)">Cancel</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const orderCheckboxes = document.querySelectorAll('.order-checkbox');
    const bulkActions = document.getElementById('bulkActions');
    const selectedCount = document.getElementById('selectedCount');
    const bulkForm = document.getElementById('bulkForm');
    const bulkEmailForm = document.getElementById('bulkEmailForm');
    const toggleBulkBtn = document.getElementById('toggleBulkActions');

    // Toggle bulk actions panel
    toggleBulkBtn.addEventListener('click', function() {
        if (bulkActions.classList.contains('show')) {
            bulkActions.classList.remove('show');
            this.innerHTML = '<i class="fas fa-tasks me-2"></i>Bulk Actions';
        } else {
            bulkActions.classList.add('show');
            this.innerHTML = '<i class="fas fa-times me-2"></i>Hide Bulk Actions';
        }
    });

    // Checkbox change handler
    orderCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updateBulkActions);
    });

    function updateBulkActions() {
        const checkedBoxes = document.querySelectorAll('.order-checkbox:checked');
        const count = checkedBoxes.length;
        
        selectedCount.textContent = count;
        
        // Add hidden inputs for selected orders to both forms
        const existingInputs = bulkForm.querySelectorAll('input[name="selected_orders[]"]');
        existingInputs.forEach(input => input.remove());
        
        const existingEmailInputs = bulkEmailForm.querySelectorAll('input[name="selected_orders[]"]');
        existingEmailInputs.forEach(input => input.remove());
        
        checkedBoxes.forEach(checkbox => {
            const hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = 'selected_orders[]';
            hiddenInput.value = checkbox.value;
            bulkForm.appendChild(hiddenInput);
            
            const hiddenEmailInput = document.createElement('input');
            hiddenEmailInput.type = 'hidden';
            hiddenEmailInput.name = 'selected_orders[]';
            hiddenEmailInput.value = checkbox.value;
            bulkEmailForm.appendChild(hiddenEmailInput);
        });
    }
});

function toggleQuickEdit(orderId) {
    const quickEditDiv = document.getElementById('quickEdit_' + orderId);
    if (quickEditDiv.classList.contains('show')) {
        quickEditDiv.classList.remove('show');
    } else {
        // Close all other quick edit forms
        document.querySelectorAll('.quick-edit.show').forEach(el => {
            el.classList.remove('show');
        });
        quickEditDiv.classList.add('show');
    }
}
</script>

<?php
include INCLUDES_PATH . '/templates/admin_footer.php';
?>
