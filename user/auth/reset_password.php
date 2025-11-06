<?php
// Set page metadata
$pageTitle = 'Reset Password';
$pageDescription = 'Reset your Akash Enterprise account password';
$pageKeywords = 'reset password, new password, change password';

require_once __DIR__ . '/../../includes/config/init.php';
// Standalone page - no full header/footer for cleaner auth experience

// Get token from URL
$token = isset($_GET['token']) ? $_GET['token'] : '';
$token_valid = false;
$user_id = null;

// Verify token
if ($token) {
    try {
        $stmt = $pdo->prepare("SELECT id, email, username, reset_token_expiry FROM users WHERE reset_token = ?");
        $stmt->execute([$token]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            // Check if token is expired
            if (strtotime($user['reset_token_expiry']) > time()) {
                $token_valid = true;
                $user_id = $user['id'];
            }
        }
    } catch (PDOException $e) {
        error_log("Token verification error: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= htmlspecialchars($pageDescription); ?>">
    <meta name="keywords" content="<?= htmlspecialchars($pageKeywords); ?>">
    <title><?= htmlspecialchars($pageTitle); ?> - Akash Enterprise</title>
    
    <!-- Stylesheets -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }
    
    body {
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #334155 100%);
        min-height: 100vh;
        position: relative;
    }
    
    body::before {
        content: '';
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: 
            radial-gradient(circle at 20% 20%, rgba(56, 189, 248, 0.1) 0%, transparent 50%),
            radial-gradient(circle at 80% 80%, rgba(168, 85, 247, 0.08) 0%, transparent 50%);
        pointer-events: none;
        z-index: 0;
    }
    
    /* Minimal Header */
    .auth-header {
        background: rgba(15, 23, 42, 0.95);
        backdrop-filter: blur(20px);
        padding: 1.5rem 0;
        border-bottom: 1px solid rgba(248, 250, 252, 0.1);
        position: relative;
        z-index: 10;
    }
    
    .auth-header .container {
        display: flex;
        justify-content: space-between;
        align-items: center;
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 20px;
    }
    
    .auth-logo img {
        height: 50px;
        width: auto;
        filter: brightness(1.1);
    }
    
    .back-home {
        color: #e2e8f0;
        text-decoration: none;
        padding: 10px 20px;
        border-radius: 25px;
        border: 1px solid rgba(248, 250, 252, 0.2);
        transition: all 0.3s ease;
        font-weight: 500;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    
    .back-home:hover {
        background: rgba(59, 130, 246, 0.1);
        border-color: #3b82f6;
        color: #3b82f6;
        text-decoration: none;
    }
    
    /* Main Content */
    .main-content {
        position: relative;
        z-index: 1;
        padding: 80px 20px;
        min-height: calc(100vh - 82px);
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .reset-password-container {
        max-width: 500px;
        width: 100%;
    }
    
    .reset-password-card {
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(15px);
        border: 1px solid rgba(59, 130, 246, 0.1);
        border-radius: 20px;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        padding: 40px;
        position: relative;
        overflow: hidden;
    }
    
    .reset-password-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(135deg, #3b82f6, #8b5cf6);
    }
    
    .icon-wrapper {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 30px;
        box-shadow: 0 10px 30px rgba(59, 130, 246, 0.3);
    }
    
    .icon-wrapper.success {
        background: linear-gradient(135deg, #22c55e, #16a34a);
    }
    
    .icon-wrapper.error {
        background: linear-gradient(135deg, #ef4444, #dc2626);
    }
    
    .icon-wrapper.default {
        background: linear-gradient(135deg, #3b82f6, #8b5cf6);
    }
    
    .icon-wrapper i {
        font-size: 35px;
        color: white;
    }
    
    .reset-password-card h2 {
        color: #1e293b;
        font-size: 2rem;
        font-weight: 700;
        margin-bottom: 15px;
        text-align: center;
    }
    
    .reset-password-card p {
        color: #64748b;
        text-align: center;
        margin-bottom: 30px;
        font-size: 1rem;
        line-height: 1.6;
    }
    
    .form-group {
        margin-bottom: 25px;
    }
    
    .form-label {
        font-weight: 600;
        color: #374151;
        margin-bottom: 8px;
        display: block;
    }
    
    .form-control {
        width: 100%;
        padding: 15px 20px;
        border: 2px solid #e5e7eb;
        border-radius: 12px;
        font-size: 1.1rem;
        transition: all 0.3s ease;
        background: rgba(255, 255, 255, 0.9);
    }
    
    .form-control:focus {
        outline: none;
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        background: white;
    }
    
    .input-group {
        position: relative;
    }
    
    .input-icon {
        position: absolute;
        left: 18px;
        top: 50%;
        transform: translateY(-50%);
        color: #9ca3af;
        font-size: 1.1rem;
        z-index: 2;
    }
    
    .form-control {
        width: 100%;
        padding: 15px 20px 15px 50px;
        border: 2px solid #e5e7eb;
        border-radius: 12px;
        font-size: 1.1rem;
        transition: all 0.3s ease;
        background: rgba(255, 255, 255, 0.9);
    }
    
    .password-toggle {
        position: absolute;
        right: 18px;
        top: 50%;
        transform: translateY(-50%);
        background: none;
        border: none;
        color: #9ca3af;
        cursor: pointer;
        padding: 5px;
        font-size: 1.1rem;
        transition: color 0.3s ease;
        z-index: 2;
    }
    
    .password-toggle:hover {
        color: #3b82f6;
    }
    
    .password-strength {
        margin-top: 10px;
        height: 4px;
        background: #e5e7eb;
        border-radius: 2px;
        overflow: hidden;
        display: none;
    }
    
    .password-strength.show {
        display: block;
    }
    
    .password-strength-bar {
        height: 100%;
        transition: all 0.3s ease;
        border-radius: 2px;
    }
    
    .strength-weak {
        width: 33%;
        background: #ef4444;
    }
    
    .strength-medium {
        width: 66%;
        background: #f59e0b;
    }
    
    .strength-strong {
        width: 100%;
        background: #22c55e;
    }
    
    .password-strength-text {
        font-size: 0.85rem;
        margin-top: 5px;
        font-weight: 500;
    }
    
    .validation-message {
        font-size: 0.85rem;
        margin-top: 5px;
        display: none;
    }
    
    .validation-message.show {
        display: block;
    }
    
    .validation-message.error {
        color: #ef4444;
    }
    
    .validation-message.success {
        color: #22c55e;
    }
    
    .password-requirements {
        background: rgba(59, 130, 246, 0.05);
        border-left: 3px solid #3b82f6;
        padding: 15px;
        border-radius: 8px;
        margin-top: 15px;
        font-size: 0.9rem;
    }
    
    .password-requirements ul {
        margin: 10px 0 0 20px;
        padding: 0;
        color: #64748b;
    }
    
    .password-requirements ul li {
        margin-bottom: 5px;
    }
    
    .btn-primary {
        background: linear-gradient(135deg, #3b82f6, #1d4ed8);
        border: none;
        padding: 15px 35px;
        border-radius: 25px;
        font-weight: 600;
        transition: all 0.3s ease;
        box-shadow: 0 8px 20px rgba(59, 130, 246, 0.3);
        color: white;
        width: 100%;
        font-size: 1.1rem;
        cursor: pointer;
    }
    
    .btn-primary:hover {
        background: linear-gradient(135deg, #2563eb, #1e40af);
        transform: translateY(-2px);
        box-shadow: 0 12px 25px rgba(59, 130, 246, 0.4);
    }
    
    .btn-success {
        background: linear-gradient(135deg, #22c55e, #16a34a);
        border: none;
        padding: 15px 35px;
        border-radius: 25px;
        font-weight: 600;
        color: white;
        width: 100%;
        font-size: 1.1rem;
        text-decoration: none;
        display: inline-block;
        text-align: center;
        transition: all 0.3s ease;
    }
    
    .btn-success:hover {
        background: linear-gradient(135deg, #16a34a, #15803d);
        transform: translateY(-2px);
        color: white;
        text-decoration: none;
    }
    
    .alert {
        padding: 15px 20px;
        border-radius: 12px;
        margin-bottom: 25px;
        border: none;
        font-weight: 500;
    }
    
    .alert-danger {
        background: rgba(239, 68, 68, 0.1);
        color: #dc2626;
        border-left: 4px solid #ef4444;
    }
    
    .alert-success {
        background: rgba(34, 197, 94, 0.1);
        color: #059669;
        border-left: 4px solid #22c55e;
    }
    
    .back-to-login {
        text-align: center;
        margin-top: 25px;
        padding-top: 25px;
        border-top: 1px solid #e5e7eb;
    }
    
    .back-to-login a {
        color: #3b82f6;
        text-decoration: none;
        font-weight: 500;
        transition: color 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    
    .back-to-login a:hover {
        color: #1d4ed8;
        text-decoration: underline;
    }
    
    /* Responsive Design */
    @media (max-width: 768px) {
        .main-content {
            padding: 40px 20px;
        }
        
        .reset-password-card {
            padding: 30px 20px;
        }
        
        .reset-password-card h2 {
            font-size: 1.75rem;
        }
        
        .icon-wrapper {
            width: 70px;
            height: 70px;
        }
        
        .icon-wrapper i {
            font-size: 30px;
        }
    }
    </style>
</head>
<body>

<!-- Minimal Header -->
<header class="auth-header">
    <div class="container">
        <a href="<?php echo BASE_URL; ?>/index.php" class="auth-logo">
            <img src="<?php echo IMG_URL; ?>/full-logo.png" alt="Akash Enterprise">
        </a>
        <a href="<?php echo BASE_URL; ?>/index.php" class="back-home">
            <i class="fas fa-arrow-left"></i> Back to Home
        </a>
    </div>
</header>

<div class="main-content">
    <div class="reset-password-container">
        <div class="reset-password-card">
            
            <?php
            $error_message = '';
            $success_message = '';
            $password_reset = false;
            
            // Handle password reset
            if ($token_valid && $_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['reset_password'])) {
                $new_password = $_POST['new_password'];
                $confirm_password = $_POST['confirm_password'];
                
                if (empty($new_password) || empty($confirm_password)) {
                    $error_message = "Please fill in all fields.";
                } elseif ($new_password !== $confirm_password) {
                    $error_message = "Passwords do not match.";
                } elseif (strlen($new_password) < 6) {
                    $error_message = "Password must be at least 6 characters long.";
                } else {
                    try {
                        // Hash new password
                        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                        
                        // Update password and clear token
                        $stmt = $pdo->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_token_expiry = NULL WHERE id = ?");
                        $stmt->execute([$hashed_password, $user_id]);
                        
                        $password_reset = true;
                        $success_message = "Your password has been reset successfully!";
                    } catch (PDOException $e) {
                        $error_message = "Failed to reset password. Please try again.";
                        error_log("Password reset error: " . $e->getMessage());
                    }
                }
            }
            
            // Display appropriate content
            if ($password_reset): ?>
                <div class="icon-wrapper success">
                    <i class="fas fa-check"></i>
                </div>
                <h2>Password Reset Successful!</h2>
                <p>Your password has been changed successfully. You can now log in with your new password.</p>
                <a href="login.php" class="btn-success">
                    <i class="fas fa-sign-in-alt me-2"></i> Go to Login
                </a>
                
            <?php elseif (!$token_valid): ?>
                <div class="icon-wrapper error">
                    <i class="fas fa-times"></i>
                </div>
                <h2>Invalid or Expired Link</h2>
                <p>This password reset link is invalid or has expired. Reset links are valid for 1 hour only.</p>
                <a href="forgot_password.php" class="btn-primary" style="text-decoration: none; display: block;">
                    <i class="fas fa-redo me-2"></i> Request New Link
                </a>
                
            <?php else: ?>
                <div class="icon-wrapper default">
                    <i class="fas fa-key"></i>
                </div>
                <h2>Reset Your Password</h2>
                <p>Please enter your new password below.</p>
                
                <?php if ($error_message): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="" id="resetPasswordForm">
                    <div class="form-group">
                        <label for="new_password" class="form-label">New Password</label>
                        <div class="input-group">
                            <input type="password" 
                                   id="new_password" 
                                   name="new_password" 
                                   class="form-control" 
                                   placeholder="Enter new password"
                                   autocomplete="new-password"
                                   required>
                            <i class="fas fa-lock input-icon"></i>
                            <button type="button" class="password-toggle" onclick="togglePassword('new_password')">
                                <i class="fas fa-eye" id="new_password-icon"></i>
                            </button>
                        </div>
                        <div class="password-strength" id="password-strength">
                            <div class="password-strength-bar" id="password-strength-bar"></div>
                        </div>
                        <div class="password-strength-text" id="password-strength-text"></div>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password" class="form-label">Confirm New Password</label>
                        <div class="input-group">
                            <input type="password" 
                                   id="confirm_password" 
                                   name="confirm_password" 
                                   class="form-control" 
                                   placeholder="Confirm new password"
                                   autocomplete="new-password"
                                   required>
                            <i class="fas fa-lock input-icon"></i>
                            <button type="button" class="password-toggle" onclick="togglePassword('confirm_password')">
                                <i class="fas fa-eye" id="confirm_password-icon"></i>
                            </button>
                        </div>
                        <div class="validation-message" id="confirm-password-validation"></div>
                    </div>
                    
                    <div class="password-requirements">
                        <strong style="color: #1e293b;">Password Requirements:</strong>
                        <ul>
                            <li>Minimum 6 characters long</li>
                            <li>Use a mix of letters and numbers</li>
                            <li>Include special characters for extra security</li>
                        </ul>
                    </div>
                    
                    <button type="submit" name="reset_password" class="btn-primary">
                        <i class="fas fa-check me-2"></i> Reset Password
                    </button>
                </form>
            <?php endif; ?>
            
            <div class="back-to-login">
                <a href="login.php">
                    <i class="fas fa-arrow-left"></i> Back to Login
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="<?php echo CSS_URL; ?>/../js/bootstrap.bundle.min.js"></script>

<script>
// Password Toggle Function
function togglePassword(inputId) {
    const input = document.getElementById(inputId);
    const icon = document.getElementById(inputId + '-icon');
    
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}

// Password Strength Checker
const passwordInput = document.getElementById('new_password');
const strengthBar = document.getElementById('password-strength-bar');
const strengthText = document.getElementById('password-strength-text');
const strengthContainer = document.getElementById('password-strength');

if (passwordInput) {
    passwordInput.addEventListener('input', function() {
        const password = this.value;
        const strength = calculatePasswordStrength(password);
        
        if (password.length > 0) {
            strengthContainer.classList.add('show');
            updatePasswordStrength(strength);
        } else {
            strengthContainer.classList.remove('show');
            strengthText.textContent = '';
        }
    });
}

function calculatePasswordStrength(password) {
    let strength = 0;
    
    if (password.length >= 6) strength++;
    if (password.length >= 10) strength++;
    if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
    if (/\d/.test(password)) strength++;
    if (/[^a-zA-Z\d]/.test(password)) strength++;
    
    return strength;
}

function updatePasswordStrength(strength) {
    strengthBar.className = 'password-strength-bar';
    
    if (strength <= 2) {
        strengthBar.classList.add('strength-weak');
        strengthText.textContent = 'Weak password';
        strengthText.style.color = '#ef4444';
    } else if (strength <= 3) {
        strengthBar.classList.add('strength-medium');
        strengthText.textContent = 'Medium password';
        strengthText.style.color = '#f59e0b';
    } else {
        strengthBar.classList.add('strength-strong');
        strengthText.textContent = 'Strong password';
        strengthText.style.color = '#22c55e';
    }
}

// Confirm password validation
const confirmPasswordInput = document.getElementById('confirm_password');
if (confirmPasswordInput && passwordInput) {
    confirmPasswordInput.addEventListener('input', function() {
        const password = passwordInput.value;
        const confirmPassword = this.value;
        const validationMsg = document.getElementById('confirm-password-validation');
        
        if (confirmPassword.length > 0) {
            if (password !== confirmPassword) {
                this.classList.add('invalid');
                this.classList.remove('valid');
                validationMsg.textContent = 'Passwords do not match';
                validationMsg.classList.add('show', 'error');
                validationMsg.classList.remove('success');
            } else {
                this.classList.add('valid');
                this.classList.remove('invalid');
                validationMsg.textContent = 'Passwords match';
                validationMsg.classList.add('show', 'success');
                validationMsg.classList.remove('error');
            }
        }
    });
}

// Form validation
const resetForm = document.getElementById('resetPasswordForm');
if (resetForm) {
    resetForm.addEventListener('submit', function(e) {
        const newPassword = passwordInput.value;
        const confirmPassword = confirmPasswordInput.value;
        
        // Clear previous validation messages
        document.querySelectorAll('.validation-message').forEach(msg => {
            msg.classList.remove('show');
        });
        
        let isValid = true;
        
        // Validate new password
        if (newPassword.length === 0) {
            passwordInput.classList.add('invalid');
            isValid = false;
        } else if (newPassword.length < 6) {
            passwordInput.classList.add('invalid');
            isValid = false;
        }
        
        // Validate confirm password
        if (confirmPassword.length === 0) {
            confirmPasswordInput.classList.add('invalid');
            document.getElementById('confirm-password-validation').textContent = 'Please confirm your password';
            document.getElementById('confirm-password-validation').classList.add('show', 'error');
            isValid = false;
        } else if (newPassword !== confirmPassword) {
            confirmPasswordInput.classList.add('invalid');
            document.getElementById('confirm-password-validation').textContent = 'Passwords do not match';
            document.getElementById('confirm-password-validation').classList.add('show', 'error');
            isValid = false;
        }
        
        if (!isValid) {
            e.preventDefault();
            return false;
        }
    });
}

// Focus on password field when page loads
document.addEventListener('DOMContentLoaded', function() {
    if (passwordInput) {
        passwordInput.focus();
    }
});

// Handle Enter key navigation
if (passwordInput) {
    passwordInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            if (confirmPasswordInput) {
                confirmPasswordInput.focus();
            }
        }
    });
}
</script>

</body>
</html>

