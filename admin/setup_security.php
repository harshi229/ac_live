<?php
/**
 * Security Tables Setup Script
 * Run this file once to create the required security monitoring tables
 */

require_once __DIR__ . '/../includes/config/init.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: ' . admin_url('login'));
    exit();
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_tables'])) {
    try {
        // Read the SQL file
        $sql_file = __DIR__ . '/database/security_tables.sql';
        if (!file_exists($sql_file)) {
            throw new Exception('Security tables SQL file not found.');
        }
        
        $sql_content = file_get_contents($sql_file);
        
        // Split SQL into individual statements
        $statements = array_filter(array_map('trim', explode(';', $sql_content)));
        
        $success_count = 0;
        foreach ($statements as $statement) {
            if (!empty($statement) && !preg_match('/^--/', $statement)) {
                $pdo->exec($statement);
                $success_count++;
            }
        }
        
        $message = "Security tables created successfully! ($success_count statements executed)";
        
    } catch (Exception $e) {
        $error = "Error creating security tables: " . $e->getMessage();
    }
}

// Check if tables already exist
$tables_exist = false;
try {
    $tables_exist = $pdo->query("SHOW TABLES LIKE 'admin_login_logs'")->rowCount() > 0;
} catch (PDOException $e) {
    $tables_exist = false;
}

include INCLUDES_PATH . '/templates/admin_header.php';
?>

<main class="container mt-4">
    <h2 class="mb-4"><i class="fas fa-database"></i> Security Tables Setup</h2>
    
    <?php if ($message): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>
    
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-info-circle"></i> Security Monitoring Setup</h5>
        </div>
        <div class="card-body">
            <?php if ($tables_exist): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check"></i> <strong>Security tables already exist!</strong>
                    <p class="mb-0 mt-2">Your security monitoring system is ready to use.</p>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <a href="<?= admin_url('security_monitor') ?>" class="btn btn-primary">
                            <i class="fas fa-shield-alt"></i> Go to Security Monitor
                        </a>
                    </div>
                    <div class="col-md-6">
                        <a href="<?= admin_url() ?>" class="btn btn-secondary">
                            <i class="fas fa-home"></i> Back to Dashboard
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i> <strong>Security tables not found!</strong>
                    <p class="mb-0 mt-2">You need to create the security monitoring tables to use the security features.</p>
                </div>
                
                <h6>What will be created:</h6>
                <ul class="list-group mb-4">
                    <li class="list-group-item">
                        <i class="fas fa-table text-primary"></i> <strong>admin_login_logs</strong> - Track admin login attempts
                    </li>
                    <li class="list-group-item">
                        <i class="fas fa-table text-primary"></i> <strong>user_login_logs</strong> - Track user login attempts
                    </li>
                    <li class="list-group-item">
                        <i class="fas fa-table text-primary"></i> <strong>security_events</strong> - Log security events
                    </li>
                    <li class="list-group-item">
                        <i class="fas fa-table text-primary"></i> <strong>remember_tokens</strong> - Secure remember me functionality
                    </li>
                </ul>
                
                <form method="POST">
                    <button type="submit" name="create_tables" class="btn btn-success btn-lg">
                        <i class="fas fa-database"></i> Create Security Tables
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>
    
    <?php if ($tables_exist): ?>
    <div class="card mt-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-chart-line"></i> Security Monitoring Features</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h6><i class="fas fa-shield-alt text-primary"></i> Real-time Monitoring</h6>
                    <ul>
                        <li>Failed login attempt tracking</li>
                        <li>Admin activity logging</li>
                        <li>Suspicious activity detection</li>
                        <li>IP address monitoring</li>
                    </ul>
                </div>
                <div class="col-md-6">
                    <h6><i class="fas fa-chart-bar text-success"></i> Security Analytics</h6>
                    <ul>
                        <li>Login pattern analysis</li>
                        <li>User behavior tracking</li>
                        <li>Security event reporting</li>
                        <li>Audit trail management</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</main>

<?php include INCLUDES_PATH . '/templates/admin_footer.php'; ?>
