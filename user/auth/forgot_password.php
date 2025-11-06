<?php
// Set page metadata
$pageTitle = 'Forgot Password';
$pageDescription = 'Reset your Akash Enterprise account password';
$pageKeywords = 'forgot password, password reset, recover account';

require_once __DIR__ . '/../../includes/config/init.php';
require_once __DIR__ . '/../../includes/functions/email_helpers.php';
// Standalone page - no full header/footer for cleaner auth experience
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
    
    .forgot-password-container {
        max-width: 500px;
        width: 100%;
    }
    
    .forgot-password-card {
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(15px);
        border: 1px solid rgba(59, 130, 246, 0.1);
        border-radius: 20px;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        padding: 40px;
        position: relative;
        overflow: hidden;
    }
    
    .forgot-password-card::before {
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
        background: linear-gradient(135deg, #3b82f6, #8b5cf6);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 30px;
        box-shadow: 0 10px 30px rgba(59, 130, 246, 0.3);
    }
    
    .icon-wrapper i {
        font-size: 35px;
        color: white;
    }
    
    .forgot-password-card h2 {
        color: #1e293b;
        font-size: 2rem;
        font-weight: 700;
        margin-bottom: 15px;
        text-align: center;
    }
    
    .forgot-password-card p {
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
        
        .forgot-password-card {
            padding: 30px 20px;
        }
        
        .forgot-password-card h2 {
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
    <div class="forgot-password-container">
        <div class="forgot-password-card">
            <div class="icon-wrapper">
                <i class="fas fa-lock"></i>
            </div>
            
            <h2>Forgot Password?</h2>
            <p>No worries! Enter your email address below and we'll send you a link to reset your password.</p>
            
            <?php
            $error_message = '';
            $success_message = '';
            
            if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['reset_request'])) {
                $email = trim($_POST['email']);
                
                if (empty($email)) {
                    $error_message = "Please enter your email address.";
                } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $error_message = "Please enter a valid email address.";
                } else {
                    try {
                        // Check if email exists
                        $stmt = $pdo->prepare("SELECT id, username, email FROM users WHERE email = ?");
                        $stmt->execute([$email]);
                        $user = $stmt->fetch(PDO::FETCH_ASSOC);
                        
                        if ($user) {
                            // Generate reset token
                            $token = bin2hex(random_bytes(32));
                            $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
                            
                            // Store token in database
                            $update_stmt = $pdo->prepare("UPDATE users SET reset_token = ?, reset_token_expiry = ? WHERE id = ?");
                            $update_stmt->execute([$token, $expiry, $user['id']]);
                            
                            // Create reset link
                            $reset_link = BASE_URL . "/user/auth/reset_password.php?token=" . $token;
                            
                            // Send email
                            $subject = "Password Reset Request - Akash Enterprise";
                            $message = "
                            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                                <h2 style='color: #3b82f6;'>Password Reset Request</h2>
                                <p>Hello " . htmlspecialchars($user['username']) . ",</p>
                                <p>We received a request to reset your password. Click the button below to reset it:</p>
                                <div style='text-align: center; margin: 30px 0;'>
                                    <a href='" . $reset_link . "' style='background: linear-gradient(135deg, #3b82f6, #1d4ed8); color: white; padding: 15px 35px; text-decoration: none; border-radius: 25px; display: inline-block; font-weight: 600;'>Reset Password</a>
                                </div>
                                <p>Or copy and paste this link into your browser:</p>
                                <p style='background: #f3f4f6; padding: 10px; border-radius: 5px; word-break: break-all;'>" . $reset_link . "</p>
                                <p style='color: #dc2626; font-weight: 600;'>This link will expire in 1 hour.</p>
                                <p>If you didn't request a password reset, please ignore this email or contact support if you have concerns.</p>
                                <hr style='margin: 30px 0; border: none; border-top: 1px solid #e5e7eb;'>
                                <p style='color: #6b7280; font-size: 0.9rem;'>Akash Enterprise - Air Conditioning Solutions</p>
                            </div>
                            ";
                            
                            if (sendEmail($email, $subject, $message)) {
                                $success_message = "Password reset instructions have been sent to your email!";
                            } else {
                                $error_message = "Failed to send email. Please try again later.";
                            }
                        } else {
                            // For security, show success even if email doesn't exist
                            $success_message = "If that email exists in our system, you'll receive password reset instructions shortly.";
                        }
                    } catch (PDOException $e) {
                        $error_message = "An error occurred. Please try again later.";
                        error_log("Forgot password error: " . $e->getMessage());
                    }
                }
            }
            
            if ($error_message): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success_message): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
                </div>
            <?php endif; ?>
            
            <?php if (!$success_message): ?>
            <form method="POST" action="">
                <div class="form-group">
                    <label for="email" class="form-label">Email Address</label>
                    <input type="email" 
                           id="email" 
                           name="email" 
                           class="form-control" 
                           placeholder="Enter your email address"
                           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                           required>
                </div>
                
                <button type="submit" name="reset_request" class="btn btn-primary">
                    <i class="fas fa-paper-plane me-2"></i> Send Reset Link
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

</body>
</html>

