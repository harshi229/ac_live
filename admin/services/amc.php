<?php
require_once __DIR__ . '/../../includes/config/init.php';

// Ensure the admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: ' . admin_url('login'));
    exit();
}

include INCLUDES_PATH . '/templates/admin_header.php';

$message = '';
$error = '';

// Handle Mark Completed action for AMC
if (isset($_POST['mark_complete'])) {
    $service_id = intval($_POST['service_id']);
    
    try {
        $stmt = $pdo->prepare("UPDATE services SET service_status='Completed', updated_at=CURRENT_TIMESTAMP WHERE id=? AND service_type='AMC'");
        if ($stmt->execute([$service_id])) {
            $message = "AMC service marked as completed successfully!";
        } else {
            $error = "Failed to update service status.";
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
        $stmt = $pdo->prepare("UPDATE services SET service_date=?, service_time=?, service_status='Rescheduled', updated_at=CURRENT_TIMESTAMP WHERE id=? AND service_type='AMC'");
        if ($stmt->execute([$new_date, $new_time, $service_id])) {
            $message = "AMC service rescheduled successfully!";
        } else {
            $error = "Failed to reschedule service.";
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
        $stmt = $pdo->prepare("UPDATE services SET technician_name=?, technician_phone=?, updated_at=CURRENT_TIMESTAMP WHERE id=? AND service_type='AMC'");
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
if (isset($_POST['cancel_service'])) {
    $service_id = intval($_POST['service_id']);
    
    try {
        $stmt = $pdo->prepare("UPDATE services SET service_status='Cancelled', updated_at=CURRENT_TIMESTAMP WHERE id=? AND service_type='AMC'");
        if ($stmt->execute([$service_id])) {
            $message = "AMC service cancelled successfully!";
        } else {
            $error = "Failed to cancel service.";
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

// Build query with filters
$where_conditions = ["s.service_type='AMC'"];
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
    $where_conditions[] = "(u.username LIKE ? OR u.email LIKE ? OR p.product_name LIKE ? OR s.technician_name LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
}

$where_clause = implode(' AND ', $where_conditions);

// Fetch AMC service data with filters
$query = $pdo->prepare("
    SELECT 
        s.id AS service_id,
        s.user_id,
        s.product_id,
        s.order_id,
        u.username,
        u.email,
        u.phone_number,
        p.product_name,
        p.model_name,
        b.name as brand_name,
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
    LEFT JOIN products p ON s.product_id = p.id
    LEFT JOIN brands b ON p.brand_id = b.id
    WHERE $where_clause
    ORDER BY 
        CASE WHEN s.service_status = 'Scheduled' THEN 1
             WHEN s.service_status = 'Rescheduled' THEN 2
             WHEN s.service_status = 'In Progress' THEN 3
             WHEN s.service_status = 'Completed' THEN 4
             WHEN s.service_status = 'Cancelled' THEN 5
        END,
        s.service_date ASC
");
$query->execute($params);
$amc_services = $query->fetchAll(PDO::FETCH_ASSOC);

// Get statistics
$stats_query = $pdo->prepare("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN service_status = 'Scheduled' THEN 1 ELSE 0 END) as scheduled,
        SUM(CASE WHEN service_status = 'In Progress' THEN 1 ELSE 0 END) as in_progress,
        SUM(CASE WHEN service_status = 'Completed' THEN 1 ELSE 0 END) as completed,
        SUM(CASE WHEN service_status = 'Cancelled' THEN 1 ELSE 0 END) as cancelled,
        SUM(CASE WHEN service_date < CURDATE() AND service_status IN ('Scheduled', 'Rescheduled') THEN 1 ELSE 0 END) as overdue
    FROM services 
    WHERE service_type='AMC'
");
$stats_query->execute();
$stats = $stats_query->fetch(PDO::FETCH_ASSOC);

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
?>

<style>
    .stats-card {
        background: linear-gradient(45deg, #007bff, #0056b3);
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
    .overdue-row {
        background-color: #fff2f2 !important;
    }
    .modal-header {
        background: linear-gradient(45deg, #007bff, #0056b3);
        color: white;
    }
</style>

<main class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <h2 class="mb-4"><i class="fas fa-tools"></i> AMC Management Dashboard</h2>
            
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
                        <small>Total AMC</small>
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
                <div class="col-md-2">
                    <div class="stats-card text-center bg-secondary">
                        <h4><?= $stats['cancelled'] ?></h4>
                        <small>Cancelled</small>
                    </div>
                </div>
            </div>

            <!-- Filter Section -->
            <div class="filter-card">
                <h5><i class="fas fa-filter"></i> Filters</h5>
                <form method="GET" class="row g-3">
                    <div class="col-md-3">
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
                    <div class="col-md-3">
                        <label for="search" class="form-label">Search</label>
                        <input type="text" name="search" id="search" class="form-control" placeholder="Customer, Email, Product, Technician..." value="<?= htmlspecialchars($search) ?>">
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary me-2"><i class="fas fa-search"></i> Filter</button>
                        <a href="amc_management.php" class="btn btn-secondary"><i class="fas fa-refresh"></i> Reset</a>
                    </div>
                </form>
            </div>

            <!-- AMC Services Table -->
            <div class="table-responsive">
                <table class="table table-hover table-striped">
                    <thead class="table-dark">
                        <tr>
                            <th>Service ID</th>
                            <th>Customer Details</th>
                            <th>Product Details</th>
                            <th>Service Schedule</th>
                            <th>Technician</th>
                            <th>Status</th>
                            <th>Charges</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if(count($amc_services) > 0): ?>
                        <?php foreach($amc_services as $amc): ?>
                            <tr <?= ($amc['display_status'] === 'Overdue') ? 'class="overdue-row"' : '' ?>>
                                <td>
                                    <strong>#<?= htmlspecialchars($amc['service_id']); ?></strong>
                                    <?php if ($amc['display_status'] === 'Overdue'): ?>
                                        <br><small class="text-danger"><i class="fas fa-exclamation-triangle"></i> OVERDUE</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong><?= htmlspecialchars($amc['username']); ?></strong><br>
                                    <small class="text-muted"><?= htmlspecialchars($amc['email']); ?></small><br>
                                    <small class="text-muted"><i class="fas fa-phone"></i> <?= htmlspecialchars($amc['phone_number']); ?></small>
                                </td>
                                <td>
                                    <strong><?= htmlspecialchars($amc['product_name']); ?></strong><br>
                                    <?php if ($amc['brand_name']): ?>
                                        <small class="text-muted">Brand: <?= htmlspecialchars($amc['brand_name']); ?></small><br>
                                    <?php endif; ?>
                                    <?php if ($amc['model_name']): ?>
                                        <small class="text-muted">Model: <?= htmlspecialchars($amc['model_name']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong><?= date('d M Y', strtotime($amc['service_date'])); ?></strong><br>
                                    <?php if ($amc['service_time']): ?>
                                        <small class="text-muted"><i class="fas fa-clock"></i> <?= date('h:i A', strtotime($amc['service_time'])); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($amc['technician_name']): ?>
                                        <strong><?= htmlspecialchars($amc['technician_name']); ?></strong><br>
                                        <small class="text-muted"><i class="fas fa-phone"></i> <?= htmlspecialchars($amc['technician_phone']); ?></small>
                                    <?php else: ?>
                                        <small class="text-muted">Not Assigned</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge <?= getStatusBadgeClass($amc['display_status']); ?>">
                                        <?= htmlspecialchars($amc['display_status']); ?>
                                    </span>
                                    <?php if ($amc['rating']): ?>
                                        <br><small class="text-warning">
                                            <?php for($i = 1; $i <= 5; $i++): ?>
                                                <i class="fas fa-star<?= $i <= $amc['rating'] ? '' : '-o' ?>"></i>
                                            <?php endfor; ?>
                                        </small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong>₹<?= number_format($amc['service_charges'], 2); ?></strong>
                                </td>
                                <td>
                                    <div class="btn-group-vertical btn-group-sm" role="group">
                                        <?php if($amc['service_status'] === 'Scheduled' || $amc['service_status'] === 'Rescheduled' || $amc['service_status'] === 'In Progress'): ?>
                                            <!-- Mark Complete Button -->
                                            <form method="POST" style="display:inline-block;">
                                                <input type="hidden" name="service_id" value="<?= $amc['service_id']; ?>">
                                                <button type="submit" name="mark_complete" class="btn btn-success btn-sm" 
                                                        onclick="return confirm('Mark this service as completed?')">
                                                    <i class="fas fa-check"></i> Complete
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                        
                                        <?php if($amc['service_status'] !== 'Completed' && $amc['service_status'] !== 'Cancelled'): ?>
                                            <!-- Reschedule Button -->
                                            <button type="button" class="btn btn-warning btn-sm" data-bs-toggle="modal" 
                                                    data-bs-target="#rescheduleModal<?= $amc['service_id']; ?>">
                                                <i class="fas fa-calendar-alt"></i> Reschedule
                                            </button>
                                            
                                            <!-- Update Technician Button -->
                                            <button type="button" class="btn btn-info btn-sm" data-bs-toggle="modal" 
                                                    data-bs-target="#technicianModal<?= $amc['service_id']; ?>">
                                                <i class="fas fa-user-cog"></i> Technician
                                            </button>
                                            
                                            <!-- Cancel Button -->
                                            <form method="POST" style="display:inline-block;">
                                                <input type="hidden" name="service_id" value="<?= $amc['service_id']; ?>">
                                                <button type="submit" name="cancel_service" class="btn btn-danger btn-sm" 
                                                        onclick="return confirm('Cancel this service?')">
                                                    <i class="fas fa-times"></i> Cancel
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                        
                                        <!-- View Details Button -->
                                        <button type="button" class="btn btn-secondary btn-sm" data-bs-toggle="modal" 
                                                data-bs-target="#detailsModal<?= $amc['service_id']; ?>">
                                            <i class="fas fa-eye"></i> Details
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <!-- Reschedule Modal -->
                            <div class="modal fade" id="rescheduleModal<?= $amc['service_id']; ?>" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Reschedule Service #<?= $amc['service_id']; ?></h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <form method="POST">
                                            <div class="modal-body">
                                                <input type="hidden" name="service_id" value="<?= $amc['service_id']; ?>">
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
                                                <button type="submit" name="reschedule" class="btn btn-warning">Reschedule</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <!-- Technician Modal -->
                            <div class="modal fade" id="technicianModal<?= $amc['service_id']; ?>" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Update Technician - Service #<?= $amc['service_id']; ?></h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <form method="POST">
                                            <div class="modal-body">
                                                <input type="hidden" name="service_id" value="<?= $amc['service_id']; ?>">
                                                <div class="mb-3">
                                                    <label for="technician_name" class="form-label">Technician Name</label>
                                                    <input type="text" name="technician_name" class="form-control" 
                                                           value="<?= htmlspecialchars($amc['technician_name']); ?>" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label for="technician_phone" class="form-label">Technician Phone</label>
                                                    <input type="tel" name="technician_phone" class="form-control" 
                                                           value="<?= htmlspecialchars($amc['technician_phone']); ?>" required>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                <button type="submit" name="update_technician" class="btn btn-info">Update</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <!-- Details Modal -->
                            <div class="modal fade" id="detailsModal<?= $amc['service_id']; ?>" tabindex="-1">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Service Details #<?= $amc['service_id']; ?></h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <h6>Customer Information</h6>
                                                    <p><strong>Name:</strong> <?= htmlspecialchars($amc['username']); ?></p>
                                                    <p><strong>Email:</strong> <?= htmlspecialchars($amc['email']); ?></p>
                                                    <p><strong>Phone:</strong> <?= htmlspecialchars($amc['phone_number']); ?></p>
                                                </div>
                                                <div class="col-md-6">
                                                    <h6>Service Information</h6>
                                                    <p><strong>Status:</strong> <span class="badge <?= getStatusBadgeClass($amc['service_status']); ?>"><?= $amc['service_status']; ?></span></p>
                                                    <p><strong>Charges:</strong> ₹<?= number_format($amc['service_charges'], 2); ?></p>
                                                    <p><strong>Created:</strong> <?= date('d M Y, h:i A', strtotime($amc['created_at'])); ?></p>
                                                </div>
                                            </div>
                                            <?php if ($amc['description']): ?>
                                                <h6>Description</h6>
                                                <p><?= nl2br(htmlspecialchars($amc['description'])); ?></p>
                                            <?php endif; ?>
                                            <?php if ($amc['customer_feedback']): ?>
                                                <h6>Customer Feedback</h6>
                                                <p><?= nl2br(htmlspecialchars($amc['customer_feedback'])); ?></p>
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
                            <td colspan="8" class="text-center py-4">
                                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                <p class="text-muted">No AMC services found matching your criteria.</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
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
</script>

<?php
include INCLUDES_PATH . '/templates/admin_footer.php';
?>
