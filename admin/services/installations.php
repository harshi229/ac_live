<?php
require_once __DIR__ . '/../../includes/config/init.php';

// Ensure admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: ' . admin_url('login'));
    exit();
}

include INCLUDES_PATH . '/templates/admin_header.php';

$message = '';
$error = '';

// Handle Mark Complete action
if (isset($_POST['mark_complete'])) {
    $service_id = intval($_POST['service_id']);
    
    try {
        $stmt = $pdo->prepare("UPDATE services SET service_status='Completed', updated_at=CURRENT_TIMESTAMP WHERE id=? AND service_type='Installation'");
        if ($stmt->execute([$service_id])) {
            $message = "Installation marked as completed successfully!";
        } else {
            $error = "Failed to update installation status.";
        }
    } catch (PDOException $e) {
        $error = "Database error: " . $e->getMessage();
    }
}

// Handle Start Installation action
if (isset($_POST['start_installation'])) {
    $service_id = intval($_POST['service_id']);
    
    try {
        $stmt = $pdo->prepare("UPDATE services SET service_status='In Progress', updated_at=CURRENT_TIMESTAMP WHERE id=? AND service_type='Installation'");
        if ($stmt->execute([$service_id])) {
            $message = "Installation started successfully!";
        } else {
            $error = "Failed to start installation.";
        }
    } catch (PDOException $e) {
        $error = "Database error: " . $e->getMessage();
    }
}

// Handle Reschedule action
if (isset($_POST['reschedule'])) {
    $service_id = intval($_POST['service_id']);
    $new_date = $_POST['new_date'];
    $new_time = $_POST['new_time'];
    
    try {
        $stmt = $pdo->prepare("UPDATE services SET service_date=?, service_time=?, service_status='Rescheduled', updated_at=CURRENT_TIMESTAMP WHERE id=? AND service_type='Installation'");
        if ($stmt->execute([$new_date, $new_time, $service_id])) {
            $message = "Installation rescheduled successfully!";
        } else {
            $error = "Failed to reschedule installation.";
        }
    } catch (PDOException $e) {
        $error = "Database error: " . $e->getMessage();
    }
}

// Handle Update Technician action
if (isset($_POST['update_technician'])) {
    $service_id = intval($_POST['service_id']);
    $technician_name = trim($_POST['technician_name']);
    $technician_phone = trim($_POST['technician_phone']);
    
    try {
        $stmt = $pdo->prepare("UPDATE services SET technician_name=?, technician_phone=?, updated_at=CURRENT_TIMESTAMP WHERE id=? AND service_type='Installation'");
        if ($stmt->execute([$technician_name, $technician_phone, $service_id])) {
            $message = "Technician details updated successfully!";
        } else {
            $error = "Failed to update technician details.";
        }
    } catch (PDOException $e) {
        $error = "Database error: " . $e->getMessage();
    }
}

// Handle Cancel action
if (isset($_POST['cancel_installation'])) {
    $service_id = intval($_POST['service_id']);
    
    try {
        $stmt = $pdo->prepare("UPDATE services SET service_status='Cancelled', updated_at=CURRENT_TIMESTAMP WHERE id=? AND service_type='Installation'");
        if ($stmt->execute([$service_id])) {
            $message = "Installation cancelled successfully!";
        } else {
            $error = "Failed to cancel installation.";
        }
    } catch (PDOException $e) {
        $error = "Database error: " . $e->getMessage();
    }
}

// Filter parameters
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$technician_filter = isset($_GET['technician']) ? $_GET['technician'] : '';

// Build query with filters
$where_conditions = ["s.service_type='Installation'"];
$params = [];

if (!empty($status_filter)) {
    $where_conditions[] = "s.service_status = ?";
    $params[] = $status_filter;
}

if (!empty($date_from)) {
    $where_conditions[] = "s.service_date >= ?";
    $params[] = $date_from;
}

if (!empty($date_to)) {
    $where_conditions[] = "s.service_date <= ?";
    $params[] = $date_to;
}

if (!empty($search)) {
    $where_conditions[] = "(u.username LIKE ? OR u.phone_number LIKE ? OR p.product_name LIKE ? OR o.order_number LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
}

if (!empty($technician_filter)) {
    $where_conditions[] = "s.technician_name LIKE ?";
    $params[] = "%$technician_filter%";
}

$where_clause = implode(' AND ', $where_conditions);

// Fetch Installation schedule with filters
$query = $pdo->prepare("
    SELECT 
        s.id AS service_id,
        s.user_id,
        s.product_id,
        s.order_id,
        o.order_number,
        o.order_status,
        o.total_price as order_total,
        u.username,
        u.email,
        u.phone_number,
        u.address,
        u.city,
        u.pincode,
        p.product_name,
        p.model_name,
        p.model_number,
        p.capacity,
        p.star_rating,
        p.warranty_years,
        b.name as brand_name,
        oi.quantity,
        oi.installation_required,
        oi.amc_opted,
        s.service_date,
        s.service_time,
        s.technician_name,
        s.technician_phone,
        s.service_status,
        s.service_charges,
        s.description,
        s.customer_feedback,
        s.rating,
        s.created_at,
        s.updated_at,
        CASE 
            WHEN s.service_date < CURDATE() AND s.service_status IN ('Scheduled', 'Rescheduled') THEN 'Overdue'
            ELSE s.service_status 
        END as display_status
    FROM services s
    JOIN users u ON s.user_id = u.id
    JOIN products p ON s.product_id = p.id
    JOIN brands b ON p.brand_id = b.id
    JOIN orders o ON s.order_id = o.id
    LEFT JOIN order_items oi ON (o.id = oi.order_id AND p.id = oi.product_id)
    WHERE $where_clause
    ORDER BY 
        CASE WHEN s.service_status = 'Scheduled' THEN 1
             WHEN s.service_status = 'Rescheduled' THEN 2
             WHEN s.service_status = 'In Progress' THEN 3
             WHEN s.service_status = 'Completed' THEN 4
             WHEN s.service_status = 'Cancelled' THEN 5
        END,
        s.service_date ASC, s.service_time ASC
");
$query->execute($params);
$installations = $query->fetchAll(PDO::FETCH_ASSOC);

// Get statistics
$stats_query = $pdo->prepare("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN service_status = 'Scheduled' THEN 1 ELSE 0 END) as scheduled,
        SUM(CASE WHEN service_status = 'In Progress' THEN 1 ELSE 0 END) as in_progress,
        SUM(CASE WHEN service_status = 'Completed' THEN 1 ELSE 0 END) as completed,
        SUM(CASE WHEN service_status = 'Cancelled' THEN 1 ELSE 0 END) as cancelled,
        SUM(CASE WHEN service_date < CURDATE() AND service_status IN ('Scheduled', 'Rescheduled') THEN 1 ELSE 0 END) as overdue,
        SUM(CASE WHEN service_date = CURDATE() AND service_status IN ('Scheduled', 'In Progress') THEN 1 ELSE 0 END) as today
    FROM services 
    WHERE service_type='Installation'
");
$stats_query->execute();
$stats = $stats_query->fetch(PDO::FETCH_ASSOC);

// Get technicians for filter dropdown
$tech_query = $pdo->prepare("
    SELECT DISTINCT technician_name 
    FROM services 
    WHERE service_type='Installation' AND technician_name IS NOT NULL AND technician_name != ''
    ORDER BY technician_name
");
$tech_query->execute();
$technicians = $tech_query->fetchAll(PDO::FETCH_COLUMN);

function getStatusBadgeClass($status) {
    switch($status) {
        case 'Scheduled': return 'bg-primary';
        case 'In Progress': return 'bg-info';
        case 'Completed': return 'bg-success';
        case 'Cancelled': return 'bg-danger';
        case 'Rescheduled': return 'bg-warning text-dark';
        case 'Overdue': return 'bg-danger';
        default: return 'bg-secondary';
    }
}

function getPriorityClass($service_date, $status) {
    $today = date('Y-m-d');
    $service_date_obj = new DateTime($service_date);
    $today_obj = new DateTime($today);
    $diff = $today_obj->diff($service_date_obj)->days;
    
    if ($service_date < $today && in_array($status, ['Scheduled', 'Rescheduled'])) {
        return 'table-danger'; // Overdue
    } elseif ($service_date == $today) {
        return 'table-warning'; // Today
    } elseif ($diff <= 2 && $service_date > $today) {
        return 'table-info'; // Upcoming
    }
    return '';
}
?>

<style>
    .stats-card {
        background: linear-gradient(45deg, #28a745, #20c997);
        color: white;
        border-radius: 10px;
        padding: 20px;
        margin-bottom: 20px;
    }
    .filter-card {
        background: #f8f9fa;
        border-radius: 10px;
        padding: 20px;
        margin-bottom: 20px;
    }
    .table-responsive {
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 0 20px rgba(0,0,0,0.1);
    }
    .btn-group-sm .btn {
        margin: 1px;
    }
    .modal-header {
        background: linear-gradient(45deg, #28a745, #20c997);
        color: white;
    }
    .priority-high {
        border-left: 4px solid #dc3545;
    }
    .priority-medium {
        border-left: 4px solid #ffc107;
    }
    .priority-low {
        border-left: 4px solid #17a2b8;
    }
    .address-info {
        font-size: 0.85em;
        line-height: 1.2;
    }
</style>

<main class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <h2 class="mb-4"><i class="fas fa-calendar-check"></i> Installation Schedule Dashboard</h2>
            
            <!-- Alert Messages -->
            <?php if ($message): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle"></i> <?= htmlspecialchars($message) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-md-2">
                    <div class="stats-card text-center">
                        <h4><?= $stats['total'] ?></h4>
                        <small>Total Installations</small>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="stats-card text-center bg-warning text-dark">
                        <h4><?= $stats['today'] ?></h4>
                        <small>Today's Schedule</small>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="stats-card text-center bg-primary">
                        <h4><?= $stats['scheduled'] ?></h4>
                        <small>Scheduled</small>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="stats-card text-center bg-info">
                        <h4><?= $stats['in_progress'] ?></h4>
                        <small>In Progress</small>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="stats-card text-center bg-success">
                        <h4><?= $stats['completed'] ?></h4>
                        <small>Completed</small>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="stats-card text-center bg-danger">
                        <h4><?= $stats['overdue'] ?></h4>
                        <small>Overdue</small>
                    </div>
                </div>
            </div>

            <!-- Filter Section -->
            <div class="filter-card">
                <h5><i class="fas fa-filter"></i> Filters</h5>
                <form method="GET" class="row g-3">
                    <div class="col-md-2">
                        <label for="status" class="form-label">Status</label>
                        <select name="status" id="status" class="form-select">
                            <option value="">All Status</option>
                            <option value="Scheduled" <?= $status_filter === 'Scheduled' ? 'selected' : '' ?>>Scheduled</option>
                            <option value="In Progress" <?= $status_filter === 'In Progress' ? 'selected' : '' ?>>In Progress</option>
                            <option value="Completed" <?= $status_filter === 'Completed' ? 'selected' : '' ?>>Completed</option>
                            <option value="Cancelled" <?= $status_filter === 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
                            <option value="Rescheduled" <?= $status_filter === 'Rescheduled' ? 'selected' : '' ?>>Rescheduled</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="date_from" class="form-label">From Date</label>
                        <input type="date" name="date_from" id="date_from" class="form-control" value="<?= htmlspecialchars($date_from) ?>">
                    </div>
                    <div class="col-md-2">
                        <label for="date_to" class="form-label">To Date</label>
                        <input type="date" name="date_to" id="date_to" class="form-control" value="<?= htmlspecialchars($date_to) ?>">
                    </div>
                    <div class="col-md-2">
                        <label for="technician" class="form-label">Technician</label>
                        <select name="technician" id="technician" class="form-select">
                            <option value="">All Technicians</option>
                            <?php foreach($technicians as $tech): ?>
                                <option value="<?= htmlspecialchars($tech) ?>" <?= $technician_filter === $tech ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($tech) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="search" class="form-label">Search</label>
                        <input type="text" name="search" id="search" class="form-control" 
                               placeholder="Customer, Phone, Product..." value="<?= htmlspecialchars($search) ?>">
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-success me-2"><i class="fas fa-search"></i> Filter</button>
                        <a href="installation_schedule.php" class="btn btn-secondary"><i class="fas fa-refresh"></i> Reset</a>
                    </div>
                </form>
            </div>

            <!-- Installation Schedule Table -->
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>Service Info</th>
                            <th>Customer Details</th>
                            <th>Product Details</th>
                            <th>Installation Schedule</th>
                            <th>Technician</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if(count($installations) > 0): ?>
                        <?php foreach($installations as $inst): ?>
                            <tr class="<?= getPriorityClass($inst['service_date'], $inst['service_status']) ?>">
                                <td>
                                    <strong>Service #<?= htmlspecialchars($inst['service_id']); ?></strong><br>
                                    <small class="text-muted">Order: <?= htmlspecialchars($inst['order_number']); ?></small><br>
                                    <?php if ($inst['display_status'] === 'Overdue'): ?>
                                        <small class="text-danger"><i class="fas fa-exclamation-triangle"></i> OVERDUE</small>
                                    <?php elseif ($inst['service_date'] == date('Y-m-d')): ?>
                                        <small class="text-warning"><i class="fas fa-clock"></i> TODAY</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong><?= htmlspecialchars($inst['username']); ?></strong><br>
                                    <small class="text-muted"><i class="fas fa-phone"></i> <?= htmlspecialchars($inst['phone_number']); ?></small><br>
                                    <small class="text-muted"><i class="fas fa-envelope"></i> <?= htmlspecialchars($inst['email']); ?></small><br>
                                    <?php if ($inst['address']): ?>
                                        <small class="address-info text-muted">
                                            <i class="fas fa-map-marker-alt"></i> 
                                            <?= htmlspecialchars($inst['address']); ?><br>
                                            <?= htmlspecialchars($inst['city']); ?> - <?= htmlspecialchars($inst['pincode']); ?>
                                        </small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong><?= htmlspecialchars($inst['product_name']); ?></strong><br>
                                    <small class="text-muted">Brand: <?= htmlspecialchars($inst['brand_name']); ?></small><br>
                                    <?php if ($inst['model_name']): ?>
                                        <small class="text-muted">Model: <?= htmlspecialchars($inst['model_name']); ?></small><br>
                                    <?php endif; ?>
                                    <small class="text-muted">Capacity: <?= htmlspecialchars($inst['capacity']); ?></small><br>
                                    <small class="text-muted">Qty: <?= htmlspecialchars($inst['quantity'] ?? 1); ?></small>
                                    <?php if ($inst['star_rating']): ?>
                                        <br><small class="text-warning">
                                            <?php for($i = 1; $i <= 5; $i++): ?>
                                                <i class="fas fa-star<?= $i <= $inst['star_rating'] ? '' : '-o' ?>"></i>
                                            <?php endfor; ?>
                                        </small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong><?= date('d M Y', strtotime($inst['service_date'])); ?></strong><br>
                                    <?php if ($inst['service_time']): ?>
                                        <small class="text-muted"><i class="fas fa-clock"></i> <?= date('h:i A', strtotime($inst['service_time'])); ?></small><br>
                                    <?php endif; ?>
                                    <?php if ($inst['amc_opted']): ?>
                                        <small class="text-success"><i class="fas fa-shield-alt"></i> AMC Opted</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($inst['technician_name']): ?>
                                        <strong><?= htmlspecialchars($inst['technician_name']); ?></strong><br>
                                        <small class="text-muted"><i class="fas fa-phone"></i> <?= htmlspecialchars($inst['technician_phone']); ?></small>
                                    <?php else: ?>
                                        <small class="text-muted">Not Assigned</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge <?= getStatusBadgeClass($inst['display_status']); ?>">
                                        <?= htmlspecialchars($inst['display_status']); ?>
                                    </span>
                                    <?php if ($inst['rating']): ?>
                                        <br><small class="text-warning">
                                            <?php for($i = 1; $i <= 5; $i++): ?>
                                                <i class="fas fa-star<?= $i <= $inst['rating'] ? '' : '-o' ?>"></i>
                                            <?php endfor; ?>
                                        </small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group-vertical btn-group-sm" role="group">
                                        <?php if($inst['service_status'] === 'Scheduled' || $inst['service_status'] === 'Rescheduled'): ?>
                                            <!-- Start Installation Button -->
                                            <form method="POST" style="display:inline-block;">
                                                <input type="hidden" name="service_id" value="<?= $inst['service_id']; ?>">
                                                <button type="submit" name="start_installation" class="btn btn-info btn-sm" 
                                                        onclick="return confirm('Start this installation?')">
                                                    <i class="fas fa-play"></i> Start
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                        
                                        <?php if($inst['service_status'] === 'In Progress' || $inst['service_status'] === 'Scheduled' || $inst['service_status'] === 'Rescheduled'): ?>
                                            <!-- Mark Complete Button -->
                                            <form method="POST" style="display:inline-block;">
                                                <input type="hidden" name="service_id" value="<?= $inst['service_id']; ?>">
                                                <button type="submit" name="mark_complete" class="btn btn-success btn-sm" 
                                                        onclick="return confirm('Mark this installation as completed?')">
                                                    <i class="fas fa-check"></i> Complete
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                        
                                        <?php if($inst['service_status'] !== 'Completed' && $inst['service_status'] !== 'Cancelled'): ?>
                                            <!-- Reschedule Button -->
                                            <button type="button" class="btn btn-warning btn-sm" data-bs-toggle="modal" 
                                                    data-bs-target="#rescheduleModal<?= $inst['service_id']; ?>">
                                                <i class="fas fa-calendar-alt"></i> Reschedule
                                            </button>
                                            
                                            <!-- Update Technician Button -->
                                            <button type="button" class="btn btn-secondary btn-sm" data-bs-toggle="modal" 
                                                    data-bs-target="#technicianModal<?= $inst['service_id']; ?>">
                                                <i class="fas fa-user-cog"></i> Technician
                                            </button>
                                            
                                            <!-- Cancel Button -->
                                            <form method="POST" style="display:inline-block;">
                                                <input type="hidden" name="service_id" value="<?= $inst['service_id']; ?>">
                                                <button type="submit" name="cancel_installation" class="btn btn-danger btn-sm" 
                                                        onclick="return confirm('Cancel this installation?')">
                                                    <i class="fas fa-times"></i> Cancel
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                        
                                        <!-- View Details Button -->
                                        <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" 
                                                data-bs-target="#detailsModal<?= $inst['service_id']; ?>">
                                            <i class="fas fa-eye"></i> Details
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <!-- Reschedule Modal -->
                            <div class="modal fade" id="rescheduleModal<?= $inst['service_id']; ?>" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Reschedule Installation #<?= $inst['service_id']; ?></h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <form method="POST">
                                            <div class="modal-body">
                                                <input type="hidden" name="service_id" value="<?= $inst['service_id']; ?>">
                                                <div class="mb-3">
                                                    <label for="new_date" class="form-label">New Date</label>
                                                    <input type="date" name="new_date" class="form-control" required 
                                                           min="<?= date('Y-m-d'); ?>">
                                                </div>
                                                <div class="mb-3">
                                                    <label for="new_time" class="form-label">New Time</label>
                                                    <input type="time" name="new_time" class="form-control">
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                <button type="submit" name="update_technician" class="btn btn-success">Update</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <!-- Details Modal -->
                            <div class="modal fade" id="detailsModal<?= $inst['service_id']; ?>" tabindex="-1">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Installation Details #<?= $inst['service_id']; ?></h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <h6><i class="fas fa-user"></i> Customer Information</h6>
                                                    <p><strong>Name:</strong> <?= htmlspecialchars($inst['username']); ?></p>
                                                    <p><strong>Email:</strong> <?= htmlspecialchars($inst['email']); ?></p>
                                                    <p><strong>Phone:</strong> <?= htmlspecialchars($inst['phone_number']); ?></p>
                                                    <?php if ($inst['address']): ?>
                                                        <p><strong>Address:</strong><br>
                                                        <?= htmlspecialchars($inst['address']); ?><br>
                                                        <?= htmlspecialchars($inst['city']); ?> - <?= htmlspecialchars($inst['pincode']); ?>
                                                        </p>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="col-md-6">
                                                    <h6><i class="fas fa-box"></i> Product Information</h6>
                                                    <p><strong>Product:</strong> <?= htmlspecialchars($inst['product_name']); ?></p>
                                                    <p><strong>Brand:</strong> <?= htmlspecialchars($inst['brand_name']); ?></p>
                                                    <?php if ($inst['model_name']): ?>
                                                        <p><strong>Model:</strong> <?= htmlspecialchars($inst['model_name']); ?></p>
                                                    <?php endif; ?>
                                                    <?php if ($inst['model_number']): ?>
                                                        <p><strong>Model Number:</strong> <?= htmlspecialchars($inst['model_number']); ?></p>
                                                    <?php endif; ?>
                                                    <p><strong>Capacity:</strong> <?= htmlspecialchars($inst['capacity']); ?></p>
                                                    <p><strong>Quantity:</strong> <?= htmlspecialchars($inst['quantity'] ?? 1); ?></p>
                                                    <p><strong>Warranty:</strong> <?= htmlspecialchars($inst['warranty_years']); ?> years</p>
                                                </div>
                                            </div>
                                            
                                            <div class="row mt-3">
                                                <div class="col-md-6">
                                                    <h6><i class="fas fa-calendar"></i> Service Information</h6>
                                                    <p><strong>Status:</strong> <span class="badge <?= getStatusBadgeClass($inst['service_status']); ?>"><?= $inst['service_status']; ?></span></p>
                                                    <p><strong>Scheduled Date:</strong> <?= date('d M Y', strtotime($inst['service_date'])); ?></p>
                                                    <?php if ($inst['service_time']): ?>
                                                        <p><strong>Scheduled Time:</strong> <?= date('h:i A', strtotime($inst['service_time'])); ?></p>
                                                    <?php endif; ?>
                                                    <p><strong>Charges:</strong> ₹<?= number_format($inst['service_charges'], 2); ?></p>
                                                    <p><strong>Created:</strong> <?= date('d M Y, h:i A', strtotime($inst['created_at'])); ?></p>
                                                    <?php if ($inst['updated_at'] != $inst['created_at']): ?>
                                                        <p><strong>Last Updated:</strong> <?= date('d M Y, h:i A', strtotime($inst['updated_at'])); ?></p>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="col-md-6">
                                                    <h6><i class="fas fa-shopping-cart"></i> Order Information</h6>
                                                    <p><strong>Order Number:</strong> <?= htmlspecialchars($inst['order_number']); ?></p>
                                                    <p><strong>Order Status:</strong> <span class="badge bg-info"><?= htmlspecialchars($inst['order_status']); ?></span></p>
                                                    <p><strong>Order Total:</strong> ₹<?= number_format($inst['order_total'], 2); ?></p>
                                                    <?php if ($inst['installation_required']): ?>
                                                        <p><span class="badge bg-warning">Installation Required</span></p>
                                                    <?php endif; ?>
                                                    <?php if ($inst['amc_opted']): ?>
                                                        <p><span class="badge bg-success">AMC Opted</span></p>
                                                    <?php endif; ?>
                                                </div>
                                            </div>

                                            <?php if ($inst['technician_name']): ?>
                                                <div class="row mt-3">
                                                    <div class="col-12">
                                                        <h6><i class="fas fa-user-cog"></i> Technician Information</h6>
                                                        <p><strong>Name:</strong> <?= htmlspecialchars($inst['technician_name']); ?></p>
                                                        <p><strong>Phone:</strong> <?= htmlspecialchars($inst['technician_phone']); ?></p>
                                                    </div>
                                                </div>
                                            <?php endif; ?>

                                            <?php if ($inst['description']): ?>
                                                <div class="row mt-3">
                                                    <div class="col-12">
                                                        <h6><i class="fas fa-sticky-note"></i> Description</h6>
                                                        <p><?= nl2br(htmlspecialchars($inst['description'])); ?></p>
                                                    </div>
                                                </div>
                                            <?php endif; ?>

                                            <?php if ($inst['customer_feedback']): ?>
                                                <div class="row mt-3">
                                                    <div class="col-12">
                                                        <h6><i class="fas fa-comment"></i> Customer Feedback</h6>
                                                        <p><?= nl2br(htmlspecialchars($inst['customer_feedback'])); ?></p>
                                                        <?php if ($inst['rating']): ?>
                                                            <p><strong>Rating:</strong> 
                                                                <span class="text-warning">
                                                                    <?php for($i = 1; $i <= 5; $i++): ?>
                                                                        <i class="fas fa-star<?= $i <= $inst['rating'] ? '' : '-o' ?>"></i>
                                                                    <?php endfor; ?>
                                                                    (<?= $inst['rating']; ?>/5)
                                                                </span>
                                                            </p>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center py-4">
                                <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                                <p class="text-muted">No installation schedules found matching your criteria.</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Quick Actions Panel -->
            <div class="row mt-4">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header bg-light">
                            <h6 class="mb-0"><i class="fas fa-tachometer-alt"></i> Quick Actions</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3">
                                    <a href="?status=Scheduled" class="btn btn-primary btn-sm w-100 mb-2">
                                        <i class="fas fa-calendar"></i> View Scheduled
                                    </a>
                                </div>
                                <div class="col-md-3">
                                    <a href="?date_from=<?= date('Y-m-d') ?>&date_to=<?= date('Y-m-d') ?>" class="btn btn-warning btn-sm w-100 mb-2">
                                        <i class="fas fa-calendar-day"></i> Today's Installations
                                    </a>
                                </div>
                                <div class="col-md-3">
                                    <a href="?status=In Progress" class="btn btn-info btn-sm w-100 mb-2">
                                        <i class="fas fa-cog"></i> In Progress
                                    </a>
                                </div>
                                <div class="col-md-3">
                                    <a href="?date_from=<?= date('Y-m-d', strtotime('-7 days')) ?>&date_to=<?= date('Y-m-d', strtotime('+7 days')) ?>" class="btn btn-success btn-sm w-100 mb-2">
                                        <i class="fas fa-calendar-week"></i> This Week
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Bootstrap JS already loaded in admin footer -->
<script>
// Auto-hide alerts after 5 seconds
setTimeout(function() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(function(alert) {
        if (alert.classList.contains('show')) {
            alert.classList.remove('show');
        }
    });
}, 5000);

// Set minimum date for reschedule to today
document.addEventListener('DOMContentLoaded', function() {
    const dateInputs = document.querySelectorAll('input[name="new_date"]');
    const today = new Date().toISOString().split('T')[0];
    dateInputs.forEach(function(input) {
        input.setAttribute('min', today);
    });
});

// Add confirmation dialogs for critical actions
document.addEventListener('DOMContentLoaded', function() {
    // Confirm completion
    const completeButtons = document.querySelectorAll('button[name="mark_complete"]');
    completeButtons.forEach(function(button) {
        button.addEventListener('click', function(e) {
            if (!confirm('Are you sure you want to mark this installation as completed?')) {
                e.preventDefault();
            }
        });
    });

    // Confirm cancellation
    const cancelButtons = document.querySelectorAll('button[name="cancel_installation"]');
    cancelButtons.forEach(function(button) {
        button.addEventListener('click', function(e) {
            if (!confirm('Are you sure you want to cancel this installation? This action cannot be undone.')) {
                e.preventDefault();
            }
        });
    });
});
</script>

<?php
include INCLUDES_PATH . '/templates/admin_footer.php';
?>
