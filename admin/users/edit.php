<?php
require_once __DIR__ . '/../../includes/config/init.php';
require_once INCLUDES_PATH . '/functions/security_helpers.php';

// Ensure only admins can access this page
if (!isset($_SESSION['admin_id'])) {
    header('Location: ' . admin_url('login'));
    exit();
}
require_once INCLUDES_PATH . '/middleware/admin_auth.php';
include INCLUDES_PATH . '/templates/admin_header.php';

if (isset($_GET['id'])) {
    $user_id = $_GET['id'];

    // Fetch user data
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo "User not found.";
        exit();
    }

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        // CSRF Protection
        if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
            $error = "Invalid request. Please try again.";
            logSecurityEvent('CSRF_TOKEN_INVALID', ['admin_id' => $_SESSION['admin_id'], 'action' => 'edit_user']);
        } else {
            $username = sanitizeInput($_POST['username']);
            $email = sanitizeInput($_POST['email']);
            $address = sanitizeInput($_POST['address']);
            $mobile_number = sanitizeInput($_POST['phone_number']);

            // Validate inputs
            $username_validation = validateUsername($username);
            if (!$username_validation['valid']) {
                $error = $username_validation['error'];
            } elseif (!isValidEmail($email)) {
                $error = "Invalid email address.";
            } elseif (!isValidPhone($mobile_number)) {
                $error = "Invalid phone number.";
            } else {
                // Update user data
                $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, address = ?, phone_number = ? WHERE id = ?");
                $success = $stmt->execute([$username, $email, $address, $mobile_number, $user_id]);
                
                if ($success) {
                    logSecurityEvent('USER_UPDATED', ['admin_id' => $_SESSION['admin_id'], 'user_id' => $user_id]);
                    header("Location: index.php?success=1");
                    exit();
                } else {
                    $error = "Failed to update user.";
                }
            }
        }
    }
} else {
    echo "User ID is missing.";
    exit();
}
?>

    <style>
        body {
            background-color: #f8f9fa;
        }
        .container {
            margin-top: 50px;
        }
    </style>

    <main>
    <div class="container">
        <h1 class="text-center">Edit User</h1>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            <div class="mb-3">
                <label for="username" class="form-label">Username:</label>
                <input type="text" class="form-control" name="username" id="username" value="<?php echo htmlspecialchars($user['username']); ?>" required maxlength="20" pattern="[a-zA-Z0-9_]+" title="Username can only contain letters, numbers, and underscores">
            </div>

            <div class="mb-3">
                <label for="email" class="form-label">Email:</label>
                <input type="email" class="form-control" name="email" id="email" value="<?php echo htmlspecialchars($user['email']); ?>" required maxlength="255">
            </div>

            <div class="mb-3">
                <label for="address" class="form-label">Address:</label>
                <textarea class="form-control" name="address" id="address" required maxlength="500"><?php echo htmlspecialchars($user['address']); ?></textarea>
            </div>

            <div class="mb-3">
                <label for="mobile_number" class="form-label">Mobile Number:</label>
                <input type="tel" class="form-control" name="phone_number" id="mobile_number" value="<?php echo htmlspecialchars($user['phone_number']); ?>" required pattern="[\+]?[1-9][\d]{0,15}" title="Enter a valid phone number">
            </div>

            <button type="submit" class="btn btn-primary">Update</button>
            <a href="index.php" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
    </main>

   <?php
include INCLUDES_PATH . '/templates/admin_footer.php';
   ?>
