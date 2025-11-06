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

// Get current admin details
$admin_query = $pdo->prepare("SELECT * FROM admins WHERE id = ?");
$admin_query->execute([$_SESSION['admin_id']]);
$admin = $admin_query->fetch(PDO::FETCH_ASSOC);

if (!$admin) {
    $error = "Admin not found.";
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validate username
    if (empty($username)) {
        $error = "Username is required.";
    } else {
        // Check if username is already taken by another admin
        $username_check = $pdo->prepare("SELECT id FROM admins WHERE username = ? AND id != ?");
        $username_check->execute([$username, $_SESSION['admin_id']]);
        if ($username_check->fetch()) {
            $error = "Username is already taken.";
        } else {
            // Update username
            $update_username = $pdo->prepare("UPDATE admins SET username = ? WHERE id = ?");
            $update_username->execute([$username, $_SESSION['admin_id']]);
            $message = "Profile updated successfully!";
        }
    }
    
    // Handle password change if provided
    if (!empty($new_password)) {
        if (empty($current_password)) {
            $error = "Current password is required to change password.";
        } elseif (!password_verify($current_password, $admin['password'])) {
            $error = "Current password is incorrect.";
        } elseif ($new_password !== $confirm_password) {
            $error = "New passwords do not match.";
        } elseif (strlen($new_password) < 6) {
            $error = "New password must be at least 6 characters long.";
        } else {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update_password = $pdo->prepare("UPDATE admins SET password = ? WHERE id = ?");
            $update_password->execute([$hashed_password, $_SESSION['admin_id']]);
            $message = "Password updated successfully!";
        }
    }
    
    // Refresh admin data
    $admin_query->execute([$_SESSION['admin_id']]);
    $admin = $admin_query->fetch(PDO::FETCH_ASSOC);
}
?>

<style>
    .profile-container {
        max-width: 800px;
        margin: 0 auto;
        padding: 20px;
    }
    
    .profile-card {
        background: white;
        border-radius: 15px;
        padding: 30px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        margin-bottom: 30px;
    }
    
    .profile-header {
        text-align: center;
        margin-bottom: 30px;
        padding-bottom: 20px;
        border-bottom: 2px solid #e9ecef;
    }
    
    .profile-avatar {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 20px;
        font-size: 3rem;
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
    
    .info-item {
        background: white;
        padding: 15px;
        border-radius: 8px;
        margin: 10px 0;
        border-left: 4px solid #3498db;
    }
    
    .info-item strong {
        color: #2c3e50;
    }
</style>

<div class="profile-container">
    <div class="profile-card">
        <div class="profile-header">
            <div class="profile-avatar">
                <i class="fas fa-user-shield"></i>
            </div>
            <h1>Admin Profile</h1>
            <p class="text-muted">Manage your account settings and preferences</p>
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

        <!-- Profile Information -->
        <div class="form-section">
            <h4><i class="fas fa-info-circle"></i> Profile Information</h4>
            <div class="info-item">
                <strong>Admin ID:</strong> <?php echo $admin['id']; ?>
            </div>
            <div class="info-item">
                <strong>Username:</strong> <?php echo htmlspecialchars($admin['username']); ?>
            </div>
            <div class="info-item">
                <strong>Account Created:</strong> <?php echo date('F j, Y \a\t g:i A', strtotime($admin['created_at'])); ?>
            </div>
        </div>

        <!-- Edit Profile Form -->
        <form method="POST">
            <div class="form-section">
                <h4><i class="fas fa-user-edit"></i> Edit Profile</h4>
                
                <div class="row">
                    <div class="col-md-6">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" name="username" id="username" 
                               value="<?php echo htmlspecialchars($admin['username']); ?>" required>
                    </div>
                </div>
                
                <div class="text-center mt-3">
                    <button type="submit" name="update_profile" class="btn btn-primary">
                        <i class="fas fa-save"></i> Update Profile
                    </button>
                </div>
            </div>
        </form>

        <!-- Change Password Form -->
        <form method="POST">
            <div class="form-section">
                <h4><i class="fas fa-lock"></i> Change Password</h4>
                
                <div class="row">
                    <div class="col-md-6">
                        <label for="current_password" class="form-label">Current Password</label>
                        <input type="password" class="form-control" name="current_password" id="current_password">
                    </div>
                    <div class="col-md-6">
                        <label for="new_password" class="form-label">New Password</label>
                        <input type="password" class="form-control" name="new_password" id="new_password" minlength="6">
                    </div>
                </div>
                
                <div class="row mt-3">
                    <div class="col-md-6">
                        <label for="confirm_password" class="form-label">Confirm New Password</label>
                        <input type="password" class="form-control" name="confirm_password" id="confirm_password" minlength="6">
                    </div>
                </div>
                
                <div class="text-center mt-3">
                    <button type="submit" name="change_password" class="btn btn-primary">
                        <i class="fas fa-key"></i> Change Password
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

<script>
// Password confirmation validation
document.getElementById('confirm_password').addEventListener('input', function() {
    const newPassword = document.getElementById('new_password').value;
    const confirmPassword = this.value;
    
    if (newPassword !== confirmPassword) {
        this.setCustomValidity('Passwords do not match');
    } else {
        this.setCustomValidity('');
    }
});

document.getElementById('new_password').addEventListener('input', function() {
    const confirmPassword = document.getElementById('confirm_password');
    if (confirmPassword.value) {
        confirmPassword.dispatchEvent(new Event('input'));
    }
});
</script>

<?php include INCLUDES_PATH . '/templates/admin_footer.php'; ?>

