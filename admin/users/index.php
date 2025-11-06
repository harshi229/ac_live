<?php
require_once dirname(dirname(__DIR__)) . '/includes/config/init.php';

// Ensure only admins can access this page
if (!isset($_SESSION['admin_id'])) {
    header('Location: ' . admin_url('login'));
    exit();
}

include INCLUDES_PATH . '/templates/admin_header.php';

// Handle user status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'toggle_status' && isset($_POST['user_id'])) {
        $user_id = intval($_POST['user_id']);
        $new_status = $_POST['new_status'] === 'active' ? 'active' : 'inactive';
        
        try {
            $stmt = $pdo->prepare("UPDATE users SET status = ? WHERE id = ?");
            $stmt->execute([$new_status, $user_id]);
            $success_message = "User status updated successfully!";
        } catch (PDOException $e) {
            $error_message = "Error updating user status: " . $e->getMessage();
        }
    }
}

// Fetch all users with order statistics
try {
    $users = $pdo->query("
        SELECT u.*, 
               COUNT(DISTINCT o.id) as total_orders,
               COALESCE(SUM(o.total_price), 0) as total_spent,
               MAX(o.created_at) as last_order_date
        FROM users u 
        LEFT JOIN orders o ON u.id = o.user_id 
        GROUP BY u.id 
        ORDER BY u.created_at DESC
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    // Get summary statistics
    $stats = [
        'total_users' => count($users),
        'active_users' => count(array_filter($users, fn($u) => $u['status'] === 'active')),
        'inactive_users' => count(array_filter($users, fn($u) => $u['status'] === 'inactive')),
        'users_with_orders' => count(array_filter($users, fn($u) => $u['total_orders'] > 0))
    ];
    
} catch (PDOException $e) {
    $error_message = "Error fetching users: " . $e->getMessage();
    $users = [];
    $stats = ['total_users' => 0, 'active_users' => 0, 'inactive_users' => 0, 'users_with_orders' => 0];
}
?>

<style>
    body {
        background-color: #f8f9fa;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }
    
    .main-container {
        margin: 30px auto;
        max-width: 1400px;
        padding: 30px;
        background: white;
        border-radius: 15px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    }
    
    .page-header {
        text-align: center;
        margin-bottom: 40px;
        padding-bottom: 20px;
        border-bottom: 2px solid #007bff;
    }
    
    .page-header h1 {
        color: #2c3e50;
        font-size: 2.5rem;
        margin-bottom: 10px;
        font-weight: 700;
    }
    
    .page-header p {
        color: #6c757d;
        font-size: 1.1rem;
        margin: 0;
    }
    
    /* Statistics Cards */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 25px;
        margin-bottom: 40px;
    }
    
    .stat-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 30px;
        border-radius: 15px;
        text-align: center;
        box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
        transition: transform 0.3s ease;
    }
    
    .stat-card:hover {
        transform: translateY(-5px);
    }
    
    .stat-card.success {
        background: linear-gradient(135deg, #56ab2f 0%, #a8e6cf 100%);
        box-shadow: 0 8px 25px rgba(86, 171, 47, 0.3);
    }
    
    .stat-card.warning {
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        box-shadow: 0 8px 25px rgba(240, 147, 251, 0.3);
    }
    
    .stat-card.info {
        background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        box-shadow: 0 8px 25px rgba(79, 172, 254, 0.3);
    }
    
    .stat-number {
        font-size: 2.5rem;
        font-weight: 700;
        margin-bottom: 10px;
        text-shadow: 2px 2px 4px rgba(0,0,0,0.1);
    }
    
    .stat-label {
        font-size: 1rem;
        opacity: 0.9;
        font-weight: 500;
    }
    
    /* Search and Filter Section */
    .controls-section {
        background: #f8f9fa;
        padding: 25px;
        border-radius: 10px;
        margin-bottom: 30px;
        border: 1px solid #e9ecef;
    }
    
    .search-box {
        max-width: 400px;
        margin: 0 auto;
    }
    
    .search-input {
        border: 2px solid #e9ecef;
        border-radius: 25px;
        padding: 12px 20px;
        font-size: 1rem;
        transition: all 0.3s ease;
    }
    
    .search-input:focus {
        border-color: #007bff;
        box-shadow: 0 0 0 0.2rem rgba(0,123,255,0.25);
    }
    
    /* Table Styling */
    .users-table-container {
        background: white;
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 5px 15px rgba(0,0,0,0.08);
    }
    
    .table {
        margin: 0;
        font-size: 0.95rem;
    }
    
    .table thead th {
        background: linear-gradient(135deg, #343a40, #495057);
        color: white;
        border: none;
        padding: 18px 15px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        font-size: 0.85rem;
    }
    
    .table tbody td {
        padding: 18px 15px;
        vertical-align: middle;
        border-bottom: 1px solid #f8f9fa;
    }
    
    .table tbody tr {
        transition: all 0.2s ease;
    }
    
    .table tbody tr:hover {
        background-color: #f8f9fa;
        transform: scale(1.01);
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }
    
    /* Status Badges */
    .status-badge {
        padding: 8px 16px;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        border: none;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    
    .status-active {
        background: linear-gradient(135deg, #28a745, #20c997);
        color: white;
    }
    
    .status-inactive {
        background: linear-gradient(135deg, #dc3545, #fd7e14);
        color: white;
    }
    
    .status-badge:hover {
        transform: scale(1.05);
        box-shadow: 0 4px 12px rgba(0,0,0,0.2);
    }
    
    /* Action Buttons */
    .action-buttons {
        display: flex;
        gap: 8px;
        justify-content: center;
    }
    
    .btn-action {
        padding: 8px 15px;
        border: none;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: 500;
        text-decoration: none;
        transition: all 0.3s ease;
        cursor: pointer;
    }
    
    .btn-edit {
        background: linear-gradient(135deg, #ffc107, #fd7e14);
        color: #212529;
    }
    
    .btn-delete {
        background: linear-gradient(135deg, #dc3545, #c82333);
        color: white;
    }
    
    .btn-view {
        background: linear-gradient(135deg, #007bff, #0056b3);
        color: white;
    }
    
    .btn-action:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        text-decoration: none;
        color: inherit;
    }
    
    /* User Avatar */
    .user-avatar {
        width: 45px;
        height: 45px;
        border-radius: 50%;
        background: linear-gradient(135deg, #667eea, #764ba2);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: bold;
        font-size: 1.1rem;
        margin-right: 15px;
    }
    
    .user-info {
        display: flex;
        align-items: center;
    }
    
    .user-details h6 {
        margin: 0;
        color: #2c3e50;
        font-weight: 600;
    }
    
    .user-details small {
        color: #6c757d;
        font-size: 0.8rem;
    }
    
    /* Empty State */
    .empty-state {
        text-align: center;
        padding: 60px 20px;
        color: #6c757d;
    }
    
    .empty-state i {
        font-size: 4rem;
        margin-bottom: 20px;
        opacity: 0.5;
    }
    
    /* Responsive Design */
    @media (max-width: 768px) {
        .main-container {
            margin: 15px;
            padding: 20px;
        }
        
        .stats-grid {
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        
        .stat-number {
            font-size: 2rem;
        }
        
        .table-responsive {
            font-size: 0.85rem;
        }
        
        .action-buttons {
            flex-direction: column;
            gap: 5px;
        }
        
        .user-info {
            flex-direction: column;
            text-align: center;
        }
        
        .user-avatar {
            margin: 0 0 10px 0;
        }
    }
    
    /* Loading Animation */
    .loading {
        display: inline-block;
        width: 20px;
        height: 20px;
        border: 3px solid rgba(255,255,255,.3);
        border-radius: 50%;
        border-top-color: #fff;
        animation: spin 1s ease-in-out infinite;
    }
    
    @keyframes spin {
        to { transform: rotate(360deg); }
    }
    
    /* Alert Styling */
    .alert {
        border-radius: 10px;
        padding: 20px;
        margin-bottom: 25px;
        border: none;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
    
    .alert-success {
        background: linear-gradient(135deg, #d4edda, #c3e6cb);
        color: #155724;
    }
    
    .alert-danger {
        background: linear-gradient(135deg, #f8d7da, #f5c6cb);
        color: #721c24;
    }
</style>

<main>
    <div class="main-container">
        <!-- Page Header -->
        <div class="page-header">
            <h1><i class="fas fa-users"></i> User Management</h1>
            <p>Manage customer accounts and monitor user activity</p>
        </div>

        <!-- Display Messages -->
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format($stats['total_users']); ?></div>
                <div class="stat-label">Total Users</div>
            </div>
            <div class="stat-card success">
                <div class="stat-number"><?php echo number_format($stats['active_users']); ?></div>
                <div class="stat-label">Active Users</div>
            </div>
            <div class="stat-card warning">
                <div class="stat-number"><?php echo number_format($stats['inactive_users']); ?></div>
                <div class="stat-label">Inactive Users</div>
            </div>
            <div class="stat-card info">
                <div class="stat-number"><?php echo number_format($stats['users_with_orders']); ?></div>
                <div class="stat-label">Users with Orders</div>
            </div>
        </div>

        <!-- Search and Filter -->
        <div class="controls-section">
            <div class="search-box">
                <input type="text" id="userSearch" class="form-control search-input" placeholder="🔍 Search users by name, email, or phone...">
            </div>
        </div>

        <!-- Users Table -->
        <div class="users-table-container">
            <?php if (empty($users)): ?>
                <div class="empty-state">
                    <i class="fas fa-users-slash"></i>
                    <h4>No Users Found</h4>
                    <p>No users have registered yet.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table" id="usersTable">
                        <thead>
                            <tr>
                                <th width="5%">ID</th>
                                <th width="25%">User Details</th>
                                <th width="20%">Contact Info</th>
                                <th width="15%">Orders</th>
                                <th width="12%">Status</th>
                                <th width="13%">Registered</th>
                                <th width="10%">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><strong>#<?php echo $user['id']; ?></strong></td>
                                    <td>
                                        <div class="user-info">
                                            <div class="user-avatar">
                                                <?php echo strtoupper(substr($user['username'], 0, 2)); ?>
                                            </div>
                                            <div class="user-details">
                                                <h6><?php echo htmlspecialchars($user['username']); ?></h6>
                                                <small><?php echo htmlspecialchars($user['email']); ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div>
                                            <i class="fas fa-phone text-muted"></i> 
                                            <?php echo htmlspecialchars($user['phone_number'] ?: 'Not provided'); ?>
                                        </div>
                                        <small class="text-muted">
                                            <i class="fas fa-map-marker-alt"></i> 
                                            <?php echo htmlspecialchars($user['address'] ? (strlen($user['address']) > 30 ? substr($user['address'], 0, 30) . '...' : $user['address']) : 'Not provided'); ?>
                                        </small>
                                    </td>
                                    <td>
                                        <div>
                                            <strong><?php echo $user['total_orders']; ?></strong> orders
                                        </div>
                                        <?php if ($user['total_orders'] > 0): ?>
                                            <small class="text-success">₹<?php echo number_format($user['total_spent'], 0); ?> spent</small><br>
                                            <small class="text-muted">Last: <?php echo $user['last_order_date'] ? date('M j, Y', strtotime($user['last_order_date'])) : 'Never'; ?></small>
                                        <?php else: ?>
                                            <small class="text-muted">No orders yet</small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="toggle_status">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            <input type="hidden" name="new_status" value="<?php echo $user['status'] === 'active' ? 'inactive' : 'active'; ?>">
                                            <button type="submit" 
                                                    class="status-badge <?php echo $user['status'] === 'active' ? 'status-active' : 'status-inactive'; ?>"
                                                    onclick="return confirm('Are you sure you want to change this user\'s status?')">
                                                <?php echo ucfirst($user['status']); ?>
                                            </button>
                                        </form>
                                    </td>
                                    <td>
                                        <strong><?php echo date('M j, Y', strtotime($user['created_at'])); ?></strong><br>
                                        <small class="text-muted"><?php echo date('g:i A', strtotime($user['created_at'])); ?></small>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="<?php echo admin_url('users/edit?id=' . $user['id']); ?>" 
                                               class="btn-action btn-view" 
                                               title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="<?php echo admin_url('users/edit?id=' . $user['id']); ?>" 
                                               class="btn-action btn-edit" 
                                               title="Edit User">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <?php if ($user['total_orders'] == 0): ?>
                                                <a href="<?php echo admin_url('users/delete?id=' . $user['id']); ?>" 
                                                   class="btn-action btn-delete" 
                                                   title="Delete User"
                                                   onclick="return confirm('Are you sure you want to delete this user? This action cannot be undone.')">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            <?php else: ?>
                                                <button class="btn-action" 
                                                        style="background: #6c757d; cursor: not-allowed;" 
                                                        title="Cannot delete - user has orders" 
                                                        disabled>
                                                    <i class="fas fa-lock"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- Quick Actions -->
        <div class="text-center mt-4">
            <a href="index.php" class="btn btn-secondary me-2">
                <i class="fas fa-dashboard"></i> Back to Dashboard
            </a>
            <a href="export_users.php" class="btn btn-success">
                <i class="fas fa-download"></i> Export Users
            </a>
        </div>
    </div>
</main>

<script>
// Search functionality
document.getElementById('userSearch').addEventListener('keyup', function() {
    const searchTerm = this.value.toLowerCase();
    const tableRows = document.querySelectorAll('#usersTable tbody tr');
    
    tableRows.forEach(row => {
        const text = row.textContent.toLowerCase();
        if (text.includes(searchTerm)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
});

// Auto-hide alerts
setTimeout(() => {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        alert.style.transition = 'opacity 0.5s ease';
        alert.style.opacity = '0';
        setTimeout(() => alert.remove(), 500);
    });
}, 5000);

// Enhanced table interactions
document.querySelectorAll('.table tbody tr').forEach(row => {
    row.addEventListener('click', function(e) {
        if (!e.target.closest('button') && !e.target.closest('a')) {
            // Optional: Add row selection functionality
            this.classList.toggle('table-active');
        }
    });
});
</script>

<?php
include INCLUDES_PATH . '/templates/admin_footer.php';
?>
