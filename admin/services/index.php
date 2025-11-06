<?php
require_once __DIR__ . '/../../includes/config/init.php';

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
            case 'add_service':
                $user_id = intval($_POST['user_id']);
                $product_id = $_POST['product_id'] ? intval($_POST['product_id']) : null;
                $order_id = $_POST['order_id'] ? intval($_POST['order_id']) : null;
                $service_type = $_POST['service_type'];
                $service_date = $_POST['service_date'];
                $service_time = $_POST['service_time'];
                $technician_name = trim($_POST['technician_name']);
                $technician_phone = trim($_POST['technician_phone']);
                $service_charges = floatval($_POST['service_charges']);
                $description = trim($_POST['description']);
                
                // Validate order_id if provided
                if ($order_id) {
                    $order_check = $pdo->prepare("SELECT id FROM orders WHERE id = ?");
                    $order_check->execute([$order_id]);
                    if (!$order_check->fetch()) {
                        $error_message = "Invalid order ID. Please select a valid order or leave it empty.";
                        break;
                    }
                }
                
                // Validate product_id if provided
                if ($product_id) {
                    $product_check = $pdo->prepare("SELECT id FROM products WHERE id = ?");
                    $product_check->execute([$product_id]);
                    if (!$product_check->fetch()) {
                        $error_message = "Invalid product ID. Please select a valid product or leave it empty.";
                        break;
                    }
                }
                
                // Validate user_id
                $user_check = $pdo->prepare("SELECT id FROM users WHERE id = ?");
                $user_check->execute([$user_id]);
                if (!$user_check->fetch()) {
                    $error_message = "Invalid user ID. Please select a valid customer.";
                    break;
                }
                
                try {
                    $stmt = $pdo->prepare("
                        INSERT INTO services (user_id, product_id, order_id, service_type, service_date, service_time, 
                                            technician_name, technician_phone, service_charges, description, service_status) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Scheduled')
                    ");
                    $stmt->execute([$user_id, $product_id, $order_id, $service_type, $service_date, $service_time, 
                                  $technician_name, $technician_phone, $service_charges, $description]);
                    $success_message = "Service scheduled successfully!";
                } catch (PDOException $e) {
                    $error_message = "Error scheduling service: " . $e->getMessage();
                }
                break;
                
            case 'update_status':
                $service_id = intval($_POST['service_id']);
                $new_status = $_POST['service_status'];
                $customer_feedback = $_POST['customer_feedback'] ?? '';
                $rating = $_POST['rating'] ? intval($_POST['rating']) : null;
                
                try {
                    $stmt = $pdo->prepare("
                        UPDATE services 
                        SET service_status = ?, customer_feedback = ?, rating = ? 
                        WHERE id = ?
                    ");
                    $stmt->execute([$new_status, $customer_feedback, $rating, $service_id]);
                    $success_message = "Service status updated successfully!";
                } catch (PDOException $e) {
                    $error_message = "Error updating service: " . $e->getMessage();
                }
                break;
                
            case 'bulk_update':
                if (isset($_POST['selected_services']) && $_POST['bulk_status']) {
                    $service_ids = $_POST['selected_services'];
                    $bulk_status = $_POST['bulk_status'];
                    
                    try {
                        $placeholders = str_repeat('?,', count($service_ids) - 1) . '?';
                        $params = array_merge([$bulk_status], $service_ids);
                        
                        $stmt = $pdo->prepare("UPDATE services SET service_status = ? WHERE id IN ($placeholders)");
                        $stmt->execute($params);
                        $success_message = count($service_ids) . " services updated successfully!";
                    } catch (PDOException $e) {
                        $error_message = "Error updating services: " . $e->getMessage();
                    }
                }
                break;
        }
    }
}

// Get filter parameters
$status_filter = $_GET['status'] ?? '';
$type_filter = $_GET['type'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$technician_filter = $_GET['technician'] ?? '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build query conditions
$conditions = ["1=1"];
$params = [];

if ($status_filter) {
    $conditions[] = "s.service_status = ?";
    $params[] = $status_filter;
}

if ($type_filter) {
    $conditions[] = "s.service_type = ?";
    $params[] = $type_filter;
}

if ($date_from) {
    $conditions[] = "s.service_date >= ?";
    $params[] = $date_from;
}

if ($date_to) {
    $conditions[] = "s.service_date <= ?";
    $params[] = $date_to;
}

if ($technician_filter) {
    $conditions[] = "s.technician_name LIKE ?";
    $params[] = "%$technician_filter%";
}

if ($search) {
    $conditions[] = "(u.username LIKE ? OR p.product_name LIKE ? OR s.technician_name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

try {
    // Main services query
    $sql = "SELECT s.*, u.username, u.phone_number as user_phone, 
                   p.product_name, p.model_name,
                   o.order_number
            FROM services s
            JOIN users u ON s.user_id = u.id
            LEFT JOIN products p ON s.product_id = p.id
            LEFT JOIN orders o ON s.order_id = o.id
            WHERE " . implode(' AND ', $conditions) . "
            ORDER BY s.service_date DESC, s.created_at DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $services = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get filter data
    $customers = $pdo->query("SELECT id, username FROM users WHERE status = 'active' ORDER BY username")->fetchAll();
    $products = $pdo->query("SELECT id, product_name, model_name FROM products WHERE status = 'active' ORDER BY product_name")->fetchAll();
    $technicians = $pdo->query("SELECT DISTINCT technician_name FROM services WHERE technician_name IS NOT NULL ORDER BY technician_name")->fetchAll();
    
    // Get statistics
    $stats = [
        'total' => $pdo->query("SELECT COUNT(*) FROM services")->fetchColumn(),
        'scheduled' => $pdo->query("SELECT COUNT(*) FROM services WHERE service_status = 'Scheduled'")->fetchColumn(),
        'in_progress' => $pdo->query("SELECT COUNT(*) FROM services WHERE service_status = 'In Progress'")->fetchColumn(),
        'completed' => $pdo->query("SELECT COUNT(*) FROM services WHERE service_status = 'Completed'")->fetchColumn(),
        'today_services' => $pdo->query("SELECT COUNT(*) FROM services WHERE service_date = CURDATE()")->fetchColumn(),
        'pending_installation' => $pdo->query("SELECT COUNT(*) FROM services WHERE service_type = 'Installation' AND service_status != 'Completed'")->fetchColumn()
    ];
    
} catch (PDOException $e) {
    $error_message = "Error fetching services: " . $e->getMessage();
    $services = [];
    $customers = [];
    $products = [];
    $technicians = [];
    $stats = ['total' => 0, 'scheduled' => 0, 'in_progress' => 0, 'completed' => 0, 'today_services' => 0, 'pending_installation' => 0];
}
?>

<style>
    body {
        background-color: #f8f9fa;
    }
    
    .container {
        margin-top: 30px;
        padding: 30px;
        background-color: #ffffff;
        border-radius: 8px;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
    }
    
    .page-header {
        border-bottom: 2px solid #007bff;
        padding-bottom: 15px;
        margin-bottom: 30px;
    }
    
    .stat-card {
        background: linear-gradient(135deg, #007bff, #0056b3);
        color: white;
        padding: 20px;
        border-radius: 8px;
        text-align: center;
        margin-bottom: 20px;
        transition: transform 0.3s ease;
    }
    
    .stat-card:hover {
        transform: translateY(-3px);
    }
    
    .stat-card.success { background: linear-gradient(135deg, #28a745, #1e7e34); }
    .stat-card.warning { background: linear-gradient(135deg, #ffc107, #e0a800); }
    .stat-card.info { background: linear-gradient(135deg, #17a2b8, #138496); }
    .stat-card.danger { background: linear-gradient(135deg, #dc3545, #c82333); }
    .stat-card.dark { background: linear-gradient(135deg, #6f42c1, #5a2d91); }
    
    .add-service-form {
        background-color: #f8f9fa;
        padding: 25px;
        border-radius: 8px;
        border: 1px solid #dee2e6;
        margin-bottom: 30px;
    }
    
    .service-card {
        border: 1px solid #dee2e6;
        border-radius: 8px;
        margin-bottom: 20px;
        transition: box-shadow 0.3s ease;
    }
    
    .service-card:hover {
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }
    
    .service-header {
        background-color: #f8f9fa;
        padding: 15px 20px;
        border-bottom: 1px solid #dee2e6;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
    }
    
    .service-body {
        padding: 20px;
    }
    
    .service-details {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 15px;
        margin-bottom: 15px;
    }
    
    .detail-item {
        border-left: 3px solid #007bff;
        padding-left: 10px;
    }
    
    .detail-label {
        font-size: 0.8rem;
        color: #6c757d;
        font-weight: 600;
        text-transform: uppercase;
    }
    
    .detail-value {
        font-size: 1rem;
        color: #333;
        margin-top: 2px;
    }
    
    .status-badge {
        padding: 4px 12px;
        border-radius: 12px;
        font-size: 0.8rem;
        font-weight: 600;
        border: none;
        cursor: pointer;
    }
    
    .status-scheduled { background-color: #cce5ff; color: #0056b3; }
    .status-in_progress { background-color: #fff3cd; color: #856404; }
    .status-completed { background-color: #d4edda; color: #155724; }
    .status-cancelled { background-color: #f8d7da; color: #721c24; }
    .status-rescheduled { background-color: #e2e3e5; color: #383d41; }
    
    .service-type-installation { border-left-color: #28a745; }
    .service-type-repair { border-left-color: #dc3545; }
    .service-type-maintenance { border-left-color: #ffc107; }
    .service-type-amc { border-left-color: #17a2b8; }
    .service-type-inspection { border-left-color: #6f42c1; }
    
    .quick-edit {
        background-color: #fff3cd;
        border: 1px solid #ffeaa7;
        border-radius: 8px;
        padding: 15px;
        margin-top: 15px;
        display: none;
    }
    
    .filter-section {
        background-color: #f8f9fa;
        padding: 20px;
        border-radius: 8px;
        border: 1px solid #dee2e6;
        margin-bottom: 30px;
    }
    
    .filter-row {
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
        align-items: end;
    }
    
    .filter-group {
        flex: 1;
        min-width: 150px;
    }
    
    .bulk-actions {
        background-color: #e9ecef;
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 20px;
        display: none;
    }
    
    .bulk-actions.show {
        display: block;
    }
    
    .rating-stars {
        color: #ffc107;
    }
    
    @media (max-width: 768px) {
        .filter-row {
            flex-direction: column;
        }
        
        .filter-group {
            width: 100%;
        }
        
        .service-header {
            flex-direction: column;
            gap: 10px;
            text-align: center;
        }
        
        .service-details {
            grid-template-columns: 1fr;
        }
    }
</style>

<main>
    <div class="container">
        <div class="page-header">
            <h1 class="mb-0">Service Management</h1>
            <p class="text-muted mt-2">Manage installations, repairs, maintenance, and AMC services</p>
        </div>

        <!-- Display messages -->
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-lg-2 col-md-4 col-6">
                <div class="stat-card">
                    <h4><?php echo $stats['total']; ?></h4>
                    <p class="mb-0">Total Services</p>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 col-6">
                <div class="stat-card info">
                    <h4><?php echo $stats['scheduled']; ?></h4>
                    <p class="mb-0">Scheduled</p>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 col-6">
                <div class="stat-card warning">
                    <h4><?php echo $stats['in_progress']; ?></h4>
                    <p class="mb-0">In Progress</p>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 col-6">
                <div class="stat-card success">
                    <h4><?php echo $stats['completed']; ?></h4>
                    <p class="mb-0">Completed</p>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 col-6">
                <div class="stat-card danger">
                    <h4><?php echo $stats['today_services']; ?></h4>
                    <p class="mb-0">Today's Services</p>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 col-6">
                <div class="stat-card dark">
                    <h4><?php echo $stats['pending_installation']; ?></h4>
                    <p class="mb-0">Pending Installs</p>
                </div>
            </div>
        </div>

        <!-- Add New Service Form -->
        <div class="add-service-form">
            <h4 class="mb-3"><i class="fas fa-plus-circle"></i> Schedule New Service</h4>
            <form method="POST" class="row g-3">
                <input type="hidden" name="action" value="add_service">
                
                <div class="col-md-3">
                    <label for="user_id" class="form-label">Customer <span class="text-danger">*</span></label>
                    <select class="form-select" name="user_id" required>
                        <option value="">Select Customer</option>
                        <?php foreach ($customers as $customer): ?>
                            <option value="<?= $customer['id'] ?>"><?= htmlspecialchars($customer['username']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-2">
                    <label for="service_type" class="form-label">Service Type <span class="text-danger">*</span></label>
                    <select class="form-select" name="service_type" required>
                        <option value="">Select Type</option>
                        <option value="Installation">Installation</option>
                        <option value="Repair">Repair</option>
                        <option value="Maintenance">Maintenance</option>
                        <option value="AMC">AMC</option>
                        <option value="Inspection">Inspection</option>
                    </select>
                </div>
                
                <div class="col-md-2">
                    <label for="service_date" class="form-label">Service Date <span class="text-danger">*</span></label>
                    <input type="date" class="form-control" name="service_date" required min="<?= date('Y-m-d') ?>">
                </div>
                
                <div class="col-md-2">
                    <label for="service_time" class="form-label">Time</label>
                    <input type="time" class="form-control" name="service_time">
                </div>
                
                <div class="col-md-3">
                    <label for="product_id" class="form-label">Product (Optional)</label>
                    <select class="form-select" name="product_id">
                        <option value="">Select Product</option>
                        <?php foreach ($products as $product): ?>
                            <option value="<?= $product['id'] ?>"><?= htmlspecialchars($product['product_name']) ?> - <?= htmlspecialchars($product['model_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-3">
                    <label for="technician_name" class="form-label">Technician Name</label>
                    <input type="text" class="form-control" name="technician_name" placeholder="e.g., Rajesh Kumar">
                </div>
                
                <div class="col-md-3">
                    <label for="technician_phone" class="form-label">Technician Phone</label>
                    <input type="tel" class="form-control" name="technician_phone" placeholder="e.g., 9876543210">
                </div>
                
                <div class="col-md-2">
                    <label for="service_charges" class="form-label">Charges (₹)</label>
                    <input type="number" class="form-control" name="service_charges" step="0.01" min="0" value="0">
                </div>
                
                <div class="col-md-3">
                    <label for="order_id" class="form-label">Related Order</label>
                    <select class="form-select" name="order_id">
                        <option value="">Select Order (Optional)</option>
                        <?php 
                        $orders_query = $pdo->query("SELECT o.id, o.order_number, u.username FROM orders o JOIN users u ON o.user_id = u.id ORDER BY o.created_at DESC LIMIT 50");
                        $orders = $orders_query->fetchAll(PDO::FETCH_ASSOC);
                        foreach ($orders as $order): ?>
                            <option value="<?= $order['id'] ?>"><?= htmlspecialchars($order['order_number']) ?> - <?= htmlspecialchars($order['username']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-12">
                    <label for="description" class="form-label">Description</label>
                    <textarea class="form-control" name="description" rows="2" placeholder="Service details, customer requirements, etc."></textarea>
                </div>
                
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-calendar-plus"></i> Schedule Service
                    </button>
                </div>
            </form>
        </div>

        <!-- Filter Section -->
        <div class="filter-section">
            <h5 class="mb-3"><i class="fas fa-filter"></i> Filter Services</h5>
            <form method="GET">
                <div class="filter-row">
                    <div class="filter-group">
                        <label for="search" class="form-label">Search</label>
                        <input type="text" class="form-control" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Customer, product, technician...">
                    </div>
                    
                    <div class="filter-group">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" name="status">
                            <option value="">All Status</option>
                            <option value="Scheduled" <?= $status_filter == 'Scheduled' ? 'selected' : '' ?>>Scheduled</option>
                            <option value="In Progress" <?= $status_filter == 'In Progress' ? 'selected' : '' ?>>In Progress</option>
                            <option value="Completed" <?= $status_filter == 'Completed' ? 'selected' : '' ?>>Completed</option>
                            <option value="Cancelled" <?= $status_filter == 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
                            <option value="Rescheduled" <?= $status_filter == 'Rescheduled' ? 'selected' : '' ?>>Rescheduled</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="type" class="form-label">Service Type</label>
                        <select class="form-select" name="type">
                            <option value="">All Types</option>
                            <option value="Installation" <?= $type_filter == 'Installation' ? 'selected' : '' ?>>Installation</option>
                            <option value="Repair" <?= $type_filter == 'Repair' ? 'selected' : '' ?>>Repair</option>
                            <option value="Maintenance" <?= $type_filter == 'Maintenance' ? 'selected' : '' ?>>Maintenance</option>
                            <option value="AMC" <?= $type_filter == 'AMC' ? 'selected' : '' ?>>AMC</option>
                            <option value="Inspection" <?= $type_filter == 'Inspection' ? 'selected' : '' ?>>Inspection</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="date_from" class="form-label">From Date</label>
                        <input type="date" class="form-control" name="date_from" value="<?= htmlspecialchars($date_from) ?>">
                    </div>
                    
                    <div class="filter-group">
                        <label for="date_to" class="form-label">To Date</label>
                        <input type="date" class="form-control" name="date_to" value="<?= htmlspecialchars($date_to) ?>">
                    </div>
                    
                    <div class="filter-group">
                        <label for="technician" class="form-label">Technician</label>
                        <select class="form-select" name="technician">
                            <option value="">All Technicians</option>
                            <?php foreach ($technicians as $tech): ?>
                                <option value="<?= htmlspecialchars($tech['technician_name']) ?>" <?= $technician_filter == $tech['technician_name'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($tech['technician_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-search"></i> Filter
                        </button>
                    </div>
                    
                    <div class="filter-group">
                        <a href="service_management.php" class="btn btn-outline-secondary w-100">
                            <i class="fas fa-refresh"></i> Reset
                        </a>
                    </div>
                </div>
            </form>
        </div>

        <!-- Results Header -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <strong><?php echo count($services); ?></strong> services found
            </div>
            <div>
                <button type="button" class="btn btn-outline-primary btn-sm" id="toggleBulkActions">
                    <i class="fas fa-tasks"></i> Bulk Actions
                </button>
            </div>
        </div>

        <!-- Bulk Actions -->
        <div class="bulk-actions" id="bulkActions">
            <form method="POST" id="bulkForm">
                <input type="hidden" name="action" value="bulk_update">
                <div class="row align-items-center">
                    <div class="col-md-3">
                        <select class="form-select" name="bulk_status" required>
                            <option value="">Choose Status</option>
                            <option value="In Progress">Mark In Progress</option>
                            <option value="Completed">Mark Completed</option>
                            <option value="Cancelled">Cancel Selected</option>
                            <option value="Rescheduled">Mark Rescheduled</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-warning" onclick="return confirm('Update status for selected services?')">
                            Apply to Selected
                        </button>
                    </div>
                    <div class="col-md-6">
                        <span id="selectedCount">0</span> services selected
                    </div>
                </div>
            </form>
        </div>

        <!-- Services List -->
        <?php if (empty($services)): ?>
            <div class="text-center py-5">
                <i class="fas fa-tools fa-4x text-muted mb-3"></i>
                <h5 class="text-muted">No services found</h5>
                <p class="text-muted">Schedule your first service using the form above</p>
            </div>
        <?php else: ?>
            <?php foreach ($services as $service): ?>
                <div class="service-card">
                    <div class="service-header">
                        <div class="d-flex align-items-center gap-3">
                            <input type="checkbox" name="selected_services[]" value="<?= $service['id'] ?>" class="form-check-input service-checkbox">
                            <div>
                                <strong><?= ucfirst(htmlspecialchars($service['service_type'])) ?> Service</strong>
                                <br><small class="text-muted">Scheduled: <?= date('M j, Y', strtotime($service['service_date'])) ?><?= $service['service_time'] ? ' at ' . date('g:i A', strtotime($service['service_time'])) : '' ?></small>
                            </div>
                        </div>
                        <div class="d-flex gap-2 align-items-center">
                            <span class="status-badge status-<?= strtolower(str_replace(' ', '_', $service['service_status'])) ?>">
                                <?= htmlspecialchars($service['service_status']) ?>
                            </span>
                            <div class="btn-group">
                                <button type="button" class="btn btn-outline-primary btn-sm" onclick="toggleQuickEdit(<?= $service['id'] ?>)">
                                    <i class="fas fa-edit"></i> Update
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="service-body">
                        <div class="service-details">
                            <div class="detail-item service-type-<?= strtolower($service['service_type']) ?>">
                                <div class="detail-label">Customer</div>
                                <div class="detail-value">
                                    <?= htmlspecialchars($service['username']) ?>
                                    <?php if ($service['user_phone']): ?>
                                        <br><small><?= htmlspecialchars($service['user_phone']) ?></small>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <?php if ($service['product_name']): ?>
                                <div class="detail-item">
                                    <div class="detail-label">Product</div>
                                    <div class="detail-value">
                                        <?= htmlspecialchars($service['product_name']) ?>
                                        <?php if ($service['model_name']): ?>
                                            <br><small><?= htmlspecialchars($service['model_name']) ?></small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($service['technician_name']): ?>
                                <div class="detail-item">
                                    <div class="detail-label">Technician</div>
                                    <div class="detail-value">
                                        <?= htmlspecialchars($service['technician_name']) ?>
                                        <?php if ($service['technician_phone']): ?>
                                            <br><small><?= htmlspecialchars($service['technician_phone']) ?></small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <div class="detail-item">
                                <div class="detail-label">Service Charges</div>
                                <div class="detail-value">
                                    <?php if ($service['service_charges'] > 0): ?>
                                        ₹<?= number_format($service['service_charges'], 2) ?>
                                    <?php else: ?>
                                        <span class="text-muted">Free</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <?php if ($service['order_number']): ?>
                                <div class="detail-item">
                                    <div class="detail-label">Related Order</div>
                                    <div class="detail-value">#<?= htmlspecialchars($service['order_number']) ?></div>
                                </div>
                            <?php endif; ?>
                            
                            <div class="detail-item">
                                <div class="detail-label">Created</div>
                                <div class="detail-value"><?= date('M j, Y', strtotime($service['created_at'])) ?></div>
                            </div>
                        </div>
                        
                        <?php if ($service['description']): ?>
                            <div class="mt-3">
                                <strong>Description:</strong>
                                <p class="text-muted mb-0"><?= htmlspecialchars($service['description']) ?></p>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($service['customer_feedback']): ?>
                            <div class="mt-3">
                                <strong>Customer Feedback:</strong>
                                <p class="text-muted mb-2"><?= htmlspecialchars($service['customer_feedback']) ?></p>
                                <?php if ($service['rating']): ?>
                                    <div class="rating-stars">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="fas fa-star<?= $i <= $service['rating'] ? '' : '-o' ?>"></i>
                                        <?php endfor; ?>
                                        <span class="ms-2"><?= $service['rating'] ?>/5</span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Quick Edit Form -->
                        <div class="quick-edit" id="quickEdit_<?= $service['id'] ?>">
                            <form method="POST" class="row g-3">
                                <input type="hidden" name="action" value="update_status">
                                <input type="hidden" name="service_id" value="<?= $service['id'] ?>">
                                
                                <div class="col-md-3">
                                    <label class="form-label">Service Status</label>
                                    <select class="form-select" name="service_status" required>
                                        <option value="Scheduled" <?= $service['service_status'] == 'Scheduled' ? 'selected' : '' ?>>Scheduled</option>
                                        <option value="In Progress" <?= $service['service_status'] == 'In Progress' ? 'selected' : '' ?>>In Progress</option>
                                        <option value="Completed" <?= $service['service_status'] == 'Completed' ? 'selected' : '' ?>>Completed</option>
                                        <option value="Cancelled" <?= $service['service_status'] == 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                        <option value="Rescheduled" <?= $service['service_status'] == 'Rescheduled' ? 'selected' : '' ?>>Rescheduled</option>
                                    </select>
                                </div>
                                
                                <div class="col-md-2">
                                    <label class="form-label">Rating (1-5)</label>
                                    <select class="form-select" name="rating">
                                        <option value="">No Rating</option>
                                        <option value="1" <?= $service['rating'] == 1 ? 'selected' : '' ?>>1 Star</option>
                                        <option value="2" <?= $service['rating'] == 2 ? 'selected' : '' ?>>2 Stars</option>
                                        <option value="3" <?= $service['rating'] == 3 ? 'selected' : '' ?>>3 Stars</option>
                                        <option value="4" <?= $service['rating'] == 4 ? 'selected' : '' ?>>4 Stars</option>
                                        <option value="5" <?= $service['rating'] == 5 ? 'selected' : '' ?>>5 Stars</option>
                                    </select>
                                </div>
                                
                                <div class="col-md-5">
                                    <label class="form-label">Customer Feedback</label>
                                    <textarea class="form-control" name="customer_feedback" rows="2" placeholder="Customer feedback or service notes"><?= htmlspecialchars($service['customer_feedback']) ?></textarea>
                                </div>
                                
                                <div class="col-md-2">
                                    <label class="form-label">&nbsp;</label>
                                    <div class="d-flex flex-column gap-1">
                                        <button type="submit" class="btn btn-success btn-sm">Update</button>
                                        <button type="button" class="btn btn-secondary btn-sm" onclick="toggleQuickEdit(<?= $service['id'] ?>)">Cancel</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
        
        <!-- Service Type Guide -->
        <div class="mt-4 p-3 bg-light rounded">
            <h6><i class="fas fa-info-circle text-info"></i> Service Types Guide:</h6>
            <div class="row small">
                <div class="col-md-2">
                    <strong>Installation:</strong> New AC setup and commissioning
                </div>
                <div class="col-md-2">
                    <strong>Repair:</strong> Fix issues and component replacement
                </div>
                <div class="col-md-2">
                    <strong>Maintenance:</strong> Regular cleaning and tune-ups
                </div>
                <div class="col-md-2">
                    <strong>AMC:</strong> Annual maintenance contract services
                </div>
                <div class="col-md-2">
                    <strong>Inspection:</strong> Assessment and diagnostics
                </div>
                <div class="col-md-2">
                    <strong>Status Colors:</strong> 
                    <span class="status-badge status-scheduled">Scheduled</span>
                    <span class="status-badge status-in_progress">In Progress</span>
                    <span class="status-badge status-completed">Completed</span>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const serviceCheckboxes = document.querySelectorAll('.service-checkbox');
    const bulkActions = document.getElementById('bulkActions');
    const selectedCount = document.getElementById('selectedCount');
    const bulkForm = document.getElementById('bulkForm');
    const toggleBulkBtn = document.getElementById('toggleBulkActions');

    // Toggle bulk actions panel
    toggleBulkBtn.addEventListener('click', function() {
        if (bulkActions.classList.contains('show')) {
            bulkActions.classList.remove('show');
            this.innerHTML = '<i class="fas fa-tasks"></i> Bulk Actions';
        } else {
            bulkActions.classList.add('show');
            this.innerHTML = '<i class="fas fa-times"></i> Hide Bulk Actions';
        }
    });

    // Checkbox change handler
    serviceCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updateBulkActions);
    });

    function updateBulkActions() {
        const checkedBoxes = document.querySelectorAll('.service-checkbox:checked');
        const count = checkedBoxes.length;
        
        selectedCount.textContent = count;
        
        // Add hidden inputs for selected services to bulk form
        const existingInputs = bulkForm.querySelectorAll('input[name="selected_services[]"]');
        existingInputs.forEach(input => input.remove());
        
        checkedBoxes.forEach(checkbox => {
            const hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = 'selected_services[]';
            hiddenInput.value = checkbox.value;
            bulkForm.appendChild(hiddenInput);
        });
    }
});

function toggleQuickEdit(serviceId) {
    const quickEditDiv = document.getElementById('quickEdit_' + serviceId);
    if (quickEditDiv.style.display === 'none' || quickEditDiv.style.display === '') {
        quickEditDiv.style.display = 'block';
    } else {
        quickEditDiv.style.display = 'none';
    }
}
</script>

<?php
include INCLUDES_PATH . '/templates/admin_footer.php';
?>
