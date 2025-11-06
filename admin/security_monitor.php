<?php
require_once __DIR__ . '/../includes/config/init.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: ' . admin_url('login'));
    exit();
}

include INCLUDES_PATH . '/templates/admin_header.php';

// Get security statistics
try {
    // Check if security tables exist
    $tables_exist = $pdo->query("SHOW TABLES LIKE 'admin_login_logs'")->rowCount() > 0;
    
    if (!$tables_exist) {
        $error_message = "Security tables not found. Please run the security_tables.sql file to create required tables.";
    } else {
        // Admin login attempts (last 24 hours)
        $login_attempts = $pdo->query("
            SELECT 
                COUNT(*) as total_attempts,
                SUM(CASE WHEN success = 1 THEN 1 ELSE 0 END) as successful_logins,
                SUM(CASE WHEN success = 0 THEN 1 ELSE 0 END) as failed_logins
            FROM admin_login_logs 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
        ")->fetch(PDO::FETCH_ASSOC);

    // Recent failed login attempts
    $failed_logins = $pdo->query("
        SELECT username, ip_address, created_at, user_agent
        FROM admin_login_logs 
        WHERE success = 0 
        ORDER BY created_at DESC 
        LIMIT 10
    ")->fetchAll(PDO::FETCH_ASSOC);

    // Admin activity logs (last 7 days)
    $admin_activities = $pdo->query("
        SELECT aal.*, a.username, a.name
        FROM admin_activity_log aal
        JOIN admins a ON aal.admin_id = a.id
        WHERE aal.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        ORDER BY aal.created_at DESC
        LIMIT 20
    ")->fetchAll(PDO::FETCH_ASSOC);

    // Suspicious activities
    $suspicious_activities = $pdo->query("
        SELECT 
            COUNT(*) as total_suspicious,
            SUM(CASE WHEN action = 'failed_login' THEN 1 ELSE 0 END) as failed_logins,
            SUM(CASE WHEN action = 'unusual_access' THEN 1 ELSE 0 END) as unusual_access,
            SUM(CASE WHEN action = 'data_export' THEN 1 ELSE 0 END) as data_exports
        FROM admin_activity_log 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        AND action IN ('failed_login', 'unusual_access', 'data_export')
    ")->fetch(PDO::FETCH_ASSOC);

    // User security stats
    $user_security = $pdo->query("
        SELECT 
            COUNT(*) as total_users,
            SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_users,
            SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive_users,
            SUM(CASE WHEN last_login < DATE_SUB(NOW(), INTERVAL 30 DAY) OR last_login IS NULL THEN 1 ELSE 0 END) as inactive_30_days
        FROM users
    ")->fetch(PDO::FETCH_ASSOC);

    // Recent user registrations
    $recent_registrations = $pdo->query("
        SELECT username, email, created_at, status
        FROM users 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        ORDER BY created_at DESC
        LIMIT 10
    ")->fetchAll(PDO::FETCH_ASSOC);
    }

} catch (PDOException $e) {
    $error_message = "Error fetching security data: " . $e->getMessage();
}

?>

<style>
    .security-card {
        border-left: 4px solid #dc3545;
        transition: transform 0.2s;
    }
    .security-card:hover {
        transform: translateY(-2px);
    }
    .security-card.safe {
        border-left-color: #28a745;
    }
    .security-card.warning {
        border-left-color: #ffc107;
    }
    .security-card.danger {
        border-left-color: #dc3545;
    }
    .activity-item {
        border-left: 3px solid #007bff;
        padding: 10px;
        margin-bottom: 10px;
        background: #f8f9fa;
    }
    .activity-item.warning {
        border-left-color: #ffc107;
    }
    .activity-item.danger {
        border-left-color: #dc3545;
    }
    .ip-address {
        font-family: monospace;
        background: #e9ecef;
        padding: 2px 6px;
        border-radius: 3px;
    }
</style>

<main class="container mt-4">
    <h2 class="mb-4"><i class="fas fa-shield-alt"></i> Security Monitor</h2>

    <?php if (isset($error_message)): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($error_message) ?>
        </div>
    <?php endif; ?>

    <!-- Security Overview Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card security-card <?= $login_attempts['failed_logins'] > 5 ? 'danger' : ($login_attempts['failed_logins'] > 2 ? 'warning' : 'safe') ?>">
                <div class="card-body text-center">
                    <i class="fas fa-user-shield fa-2x text-primary mb-2"></i>
                    <h5>Admin Security</h5>
                    <h3 class="text-primary"><?= $login_attempts['failed_logins'] ?? 0 ?></h3>
                    <small class="text-muted">Failed Logins (24h)</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card security-card <?= $suspicious_activities['total_suspicious'] > 10 ? 'danger' : ($suspicious_activities['total_suspicious'] > 5 ? 'warning' : 'safe') ?>">
                <div class="card-body text-center">
                    <i class="fas fa-exclamation-triangle fa-2x text-warning mb-2"></i>
                    <h5>Suspicious Activity</h5>
                    <h3 class="text-warning"><?= $suspicious_activities['total_suspicious'] ?? 0 ?></h3>
                    <small class="text-muted">Events (7 days)</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card security-card safe">
                <div class="card-body text-center">
                    <i class="fas fa-users fa-2x text-info mb-2"></i>
                    <h5>User Security</h5>
                    <h3 class="text-info"><?= $user_security['active_users'] ?? 0 ?></h3>
                    <small class="text-muted">Active Users</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card security-card <?= $user_security['inactive_30_days'] > 50 ? 'warning' : 'safe' ?>">
                <div class="card-body text-center">
                    <i class="fas fa-clock fa-2x text-secondary mb-2"></i>
                    <h5>Inactive Users</h5>
                    <h3 class="text-secondary"><?= $user_security['inactive_30_days'] ?? 0 ?></h3>
                    <small class="text-muted">30+ Days</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Failed Login Attempts -->
    <?php if (!empty($failed_logins)): ?>
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-ban"></i> Recent Failed Login Attempts</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Username</th>
                            <th>IP Address</th>
                            <th>Time</th>
                            <th>User Agent</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($failed_logins as $attempt): ?>
                        <tr class="table-danger">
                            <td><?= htmlspecialchars($attempt['username']) ?></td>
                            <td><span class="ip-address"><?= htmlspecialchars($attempt['ip_address']) ?></span></td>
                            <td><?= date('M d, Y H:i:s', strtotime($attempt['created_at'])) ?></td>
                            <td><small><?= htmlspecialchars(substr($attempt['user_agent'], 0, 50)) ?>...</small></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Admin Activity Log -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-history"></i> Recent Admin Activity</h5>
        </div>
        <div class="card-body">
            <?php if (!empty($admin_activities)): ?>
                <?php foreach ($admin_activities as $activity): ?>
                <div class="activity-item <?= in_array($activity['action'], ['failed_login', 'unusual_access']) ? 'danger' : (in_array($activity['action'], ['data_export', 'bulk_action']) ? 'warning' : '') ?>">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <strong><?= htmlspecialchars($activity['username']) ?></strong>
                            <span class="badge bg-primary ms-2"><?= htmlspecialchars($activity['action']) ?></span>
                            <br>
                            <small class="text-muted"><?= htmlspecialchars($activity['description']) ?></small>
                        </div>
                        <div class="text-end">
                            <small class="text-muted"><?= date('M d, H:i', strtotime($activity['created_at'])) ?></small>
                            <br>
                            <span class="ip-address"><?= htmlspecialchars($activity['ip_address']) ?></span>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="text-muted">No recent admin activity found.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Recent User Registrations -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-user-plus"></i> Recent User Registrations</h5>
        </div>
        <div class="card-body">
            <?php if (!empty($recent_registrations)): ?>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Status</th>
                                <th>Registered</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_registrations as $user): ?>
                            <tr>
                                <td><?= htmlspecialchars($user['username']) ?></td>
                                <td><?= htmlspecialchars($user['email']) ?></td>
                                <td>
                                    <span class="badge <?= $user['status'] == 'active' ? 'bg-success' : 'bg-secondary' ?>">
                                        <?= ucfirst($user['status']) ?>
                                    </span>
                                </td>
                                <td><?= date('M d, Y', strtotime($user['created_at'])) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-muted">No recent user registrations found.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Security Actions -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-tools"></i> Security Actions</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    <a href="<?= admin_url('users') ?>" class="btn btn-outline-primary w-100 mb-2">
                        <i class="fas fa-users"></i> Manage Users
                    </a>
                </div>
                <div class="col-md-4">
                    <a href="<?= admin_url('settings') ?>" class="btn btn-outline-warning w-100 mb-2">
                        <i class="fas fa-cog"></i> System Settings
                    </a>
                </div>
                <div class="col-md-4">
                    <button onclick="refreshSecurityData()" class="btn btn-outline-info w-100 mb-2">
                        <i class="fas fa-sync"></i> Refresh Data
                    </button>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
function refreshSecurityData() {
    location.reload();
}

// Auto-refresh every 5 minutes
setTimeout(function() {
    location.reload();
}, 300000);
</script>

<?php include INCLUDES_PATH . '/templates/admin_footer.php'; ?>
