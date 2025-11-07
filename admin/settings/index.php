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

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_system_settings':
                $site_name = trim($_POST['site_name']);
                $site_email = trim($_POST['site_email']);
                $site_phone = trim($_POST['site_phone']);
                $site_address = trim($_POST['site_address']);
                $currency = trim($_POST['currency']);
                $timezone = trim($_POST['timezone']);
                
                try {
                    // Create or update system settings
                    $settings = [
                        'site_name' => $site_name,
                        'site_email' => $site_email,
                        'site_phone' => $site_phone,
                        'site_address' => $site_address,
                        'currency' => $currency,
                        'timezone' => $timezone
                    ];
                    
                    foreach ($settings as $key => $value) {
                        $stmt = $pdo->prepare("
                            INSERT INTO system_settings (setting_key, setting_value) 
                            VALUES (?, ?) 
                            ON DUPLICATE KEY UPDATE setting_value = ?
                        ");
                        $stmt->execute([$key, $value, $value]);
                    }
                    
                    $message = "System settings updated successfully!";
                } catch (PDOException $e) {
                    $error = "Error updating settings: " . $e->getMessage();
                }
                break;
                
            case 'update_notification_settings':
                $email_notifications = isset($_POST['email_notifications']) ? 1 : 0;
                $order_notifications = isset($_POST['order_notifications']) ? 1 : 0;
                $service_notifications = isset($_POST['service_notifications']) ? 1 : 0;
                $low_stock_alerts = isset($_POST['low_stock_alerts']) ? 1 : 0;
                $low_stock_threshold = intval($_POST['low_stock_threshold']);
                
                try {
                    $notification_settings = [
                        'email_notifications' => $email_notifications,
                        'order_notifications' => $order_notifications,
                        'service_notifications' => $service_notifications,
                        'low_stock_alerts' => $low_stock_alerts,
                        'low_stock_threshold' => $low_stock_threshold
                    ];
                    
                    foreach ($notification_settings as $key => $value) {
                        $stmt = $pdo->prepare("
                            INSERT INTO system_settings (setting_key, setting_value) 
                            VALUES (?, ?) 
                            ON DUPLICATE KEY UPDATE setting_value = ?
                        ");
                        $stmt->execute([$key, $value, $value]);
                    }
                    
                    $message = "Notification settings updated successfully!";
                } catch (PDOException $e) {
                    $error = "Error updating notification settings: " . $e->getMessage();
                }
                break;
        }
    }
}

// Get current settings
$settings_query = $pdo->query("SELECT setting_key, setting_value FROM system_settings");
$settings = [];
while ($row = $settings_query->fetch(PDO::FETCH_ASSOC)) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

// Default values
$defaults = [
    'site_name' => 'AC Management System',
    'site_email' => 'aakashjamnagar@gmail.com',
    'site_phone' => '+1-234-567-8900',
    'site_address' => 'Your Business Address',
    'currency' => '₹',
    'timezone' => 'Asia/Kolkata',
    'email_notifications' => '1',
    'order_notifications' => '1',
    'service_notifications' => '1',
    'low_stock_alerts' => '1',
    'low_stock_threshold' => '10'
];

foreach ($defaults as $key => $value) {
    if (!isset($settings[$key])) {
        $settings[$key] = $value;
    }
}
?>

<style>
    .settings-container {
        max-width: 1000px;
        margin: 0 auto;
        padding: 20px;
    }
    
    .settings-card {
        background: white;
        border-radius: 15px;
        padding: 30px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        margin-bottom: 30px;
    }
    
    .settings-header {
        text-align: center;
        margin-bottom: 30px;
        padding-bottom: 20px;
        border-bottom: 2px solid #e9ecef;
    }
    
    .settings-icon {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 20px;
        font-size: 2rem;
        color: white;
    }
    
    .form-section {
        background: #f8f9fa;
        padding: 25px;
        border-radius: 10px;
        margin: 20px 0;
    }
    
    .form-section h4 {
        color: #2c3e50;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
    }
    
    .form-section h4 i {
        margin-right: 10px;
        color: #3498db;
    }
    
    .alert {
        border-radius: 10px;
        padding: 15px 20px;
        margin-bottom: 20px;
    }
    
    .btn-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border: none;
        padding: 12px 30px;
        border-radius: 25px;
        font-weight: 600;
        transition: all 0.3s ease;
    }
    
    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
    }
    
    .btn-secondary {
        background: #6c757d;
        border: none;
        padding: 12px 30px;
        border-radius: 25px;
        font-weight: 600;
    }
    
    .form-control:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
    }
    
    .form-check-input:checked {
        background-color: #667eea;
        border-color: #667eea;
    }
    
    .setting-item {
        background: white;
        padding: 15px;
        border-radius: 8px;
        margin: 10px 0;
        border-left: 4px solid #3498db;
    }
    
    .setting-item strong {
        color: #2c3e50;
    }
</style>

<div class="settings-container">
    <div class="settings-card">
        <div class="settings-header">
            <div class="settings-icon">
                <i class="fas fa-cog"></i>
            </div>
            <h1>System Settings</h1>
            <p class="text-muted">Configure your system preferences and notifications</p>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <!-- System Information -->
        <div class="form-section">
            <h4><i class="fas fa-info-circle"></i> System Information</h4>
            <div class="setting-item">
                <strong>PHP Version:</strong> <?php echo PHP_VERSION; ?>
            </div>
            <div class="setting-item">
                <strong>Database:</strong> MySQL
            </div>
            <div class="setting-item">
                <strong>Server Time:</strong> <?php echo date('Y-m-d H:i:s'); ?>
            </div>
        </div>

        <!-- System Settings Form -->
        <form method="POST">
            <input type="hidden" name="action" value="update_system_settings">
            
            <div class="form-section">
                <h4><i class="fas fa-globe"></i> General Settings</h4>
                
                <div class="row">
                    <div class="col-md-6">
                        <label for="site_name" class="form-label">Site Name</label>
                        <input type="text" class="form-control" name="site_name" id="site_name" 
                               value="<?php echo htmlspecialchars($settings['site_name']); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label for="site_email" class="form-label">Site Email</label>
                        <input type="email" class="form-control" name="site_email" id="site_email" 
                               value="<?php echo htmlspecialchars($settings['site_email']); ?>" required>
                    </div>
                </div>
                
                <div class="row mt-3">
                    <div class="col-md-6">
                        <label for="site_phone" class="form-label">Site Phone</label>
                        <input type="text" class="form-control" name="site_phone" id="site_phone" 
                               value="<?php echo htmlspecialchars($settings['site_phone']); ?>">
                    </div>
                    <div class="col-md-6">
                        <label for="currency" class="form-label">Currency</label>
                        <select class="form-select" name="currency" id="currency">
                            <option value="₹" <?php echo $settings['currency'] == '₹' ? 'selected' : ''; ?>>₹ (Indian Rupee)</option>
                            <option value="$" <?php echo $settings['currency'] == '$' ? 'selected' : ''; ?>>$ (US Dollar)</option>
                            <option value="€" <?php echo $settings['currency'] == '€' ? 'selected' : ''; ?>>€ (Euro)</option>
                            <option value="£" <?php echo $settings['currency'] == '£' ? 'selected' : ''; ?>>£ (British Pound)</option>
                        </select>
                    </div>
                </div>
                
                <div class="row mt-3">
                    <div class="col-md-6">
                        <label for="timezone" class="form-label">Timezone</label>
                        <select class="form-select" name="timezone" id="timezone">
                            <option value="Asia/Kolkata" <?php echo $settings['timezone'] == 'Asia/Kolkata' ? 'selected' : ''; ?>>Asia/Kolkata (IST)</option>
                            <option value="America/New_York" <?php echo $settings['timezone'] == 'America/New_York' ? 'selected' : ''; ?>>America/New_York (EST)</option>
                            <option value="Europe/London" <?php echo $settings['timezone'] == 'Europe/London' ? 'selected' : ''; ?>>Europe/London (GMT)</option>
                            <option value="Asia/Tokyo" <?php echo $settings['timezone'] == 'Asia/Tokyo' ? 'selected' : ''; ?>>Asia/Tokyo (JST)</option>
                        </select>
                    </div>
                </div>
                
                <div class="row mt-3">
                    <div class="col-md-12">
                        <label for="site_address" class="form-label">Site Address</label>
                        <textarea class="form-control" name="site_address" id="site_address" rows="3"><?php echo htmlspecialchars($settings['site_address']); ?></textarea>
                    </div>
                </div>
                
                <div class="text-center mt-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Update General Settings
                    </button>
                </div>
            </div>
        </form>

        <!-- Notification Settings Form -->
        <form method="POST">
            <input type="hidden" name="action" value="update_notification_settings">
            
            <div class="form-section">
                <h4><i class="fas fa-bell"></i> Notification Settings</h4>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="email_notifications" id="email_notifications" 
                                   <?php echo $settings['email_notifications'] ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="email_notifications">
                                Email Notifications
                            </label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="order_notifications" id="order_notifications" 
                                   <?php echo $settings['order_notifications'] ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="order_notifications">
                                Order Notifications
                            </label>
                        </div>
                    </div>
                </div>
                
                <div class="row mt-3">
                    <div class="col-md-6">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="service_notifications" id="service_notifications" 
                                   <?php echo $settings['service_notifications'] ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="service_notifications">
                                Service Notifications
                            </label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="low_stock_alerts" id="low_stock_alerts" 
                                   <?php echo $settings['low_stock_alerts'] ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="low_stock_alerts">
                                Low Stock Alerts
                            </label>
                        </div>
                    </div>
                </div>
                
                <div class="row mt-3">
                    <div class="col-md-6">
                        <label for="low_stock_threshold" class="form-label">Low Stock Threshold</label>
                        <input type="number" class="form-control" name="low_stock_threshold" id="low_stock_threshold" 
                               value="<?php echo $settings['low_stock_threshold']; ?>" min="1" max="100">
                        <small class="form-text text-muted">Alert when stock falls below this number</small>
                    </div>
                </div>
                
                <div class="text-center mt-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Update Notification Settings
                    </button>
                </div>
            </div>
        </form>

        <!-- Action Buttons -->
        <div class="text-center mt-4">
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>
</div>

<?php include INCLUDES_PATH . '/templates/admin_footer.php'; ?>

