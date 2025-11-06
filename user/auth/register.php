<?php
// Set page metadata
$pageTitle = 'Register';
$pageDescription = 'Create your Akash Enterprise account to access exclusive features and manage your orders';
$pageKeywords = 'register, sign up, create account, user registration, AC account';

require_once __DIR__ . '/../../includes/config/init.php';
require_once __DIR__ . '/../../includes/config/google_oauth.php';

// Make sure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if already logged in
if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
    header('Location: ' . USER_URL . '/profile/index.php');
    exit();
}

// Handle registration form submission
$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['register'])) {
    // CSRF Protection - Check if CSRF token exists and is valid
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error_message = "Security validation failed. Please try again.";
    } else {
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        $phone_number = trim($_POST['phone_number']);
        $address = trim($_POST['address']);
        $terms_accepted = isset($_POST['terms']) ? true : false;
    
        // Enhanced validation
        if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
            $error_message = "Please fill in all required fields.";
        } elseif (!preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username)) {
            $error_message = "Username must be 3-20 characters long and contain only letters, numbers, and underscores.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error_message = "Please enter a valid email address.";
        } elseif (strlen($password) < 6) {
            $error_message = "Password must be at least 6 characters long.";
        } elseif ($password !== $confirm_password) {
            $error_message = "Passwords do not match.";
        } elseif (!empty($phone_number) && !preg_match('/^[\+]?[1-9]\d{0,15}$/', preg_replace('/[\s\-\(\)]/', '', $phone_number))) {
            $error_message = "Please enter a valid phone number.";
        } elseif (!$terms_accepted) {
            $error_message = "Please accept the Terms of Service and Privacy Policy.";
        } else {
            try {
                // Check if username or email already exists
                $check_stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
                $check_stmt->execute([$username, $email]);
                
                if ($check_stmt->fetch()) {
                    $error_message = "Username or email already exists.";
                } else {
                    // Hash password with high cost
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT, ['cost' => 12]);
                    
                    // Insert user
                    $stmt = $pdo->prepare("INSERT INTO users (username, email, password, phone_number, address, status, created_at) VALUES (?, ?, ?, ?, ?, 'active', NOW())");
                    $result = $stmt->execute([$username, $email, $hashed_password, $phone_number, $address]);
                    
                    if ($result) {
                        $user_id = $pdo->lastInsertId();
                        
                        // Log successful registration
                        error_log("NEW USER REGISTERED: " . $username . " (" . $email . ") with ID: " . $user_id);
                        
                        $success_message = "Registration successful! Redirecting to login page...";
                        
                        // Clear form data
                        $_POST = array();
                    } else {
                        error_log("REGISTRATION FAILED - database insert returned false for user: " . $username);
                        $error_message = "Registration failed. Please try again.";
                    }
                }
            } catch (PDOException $e) {
                error_log("REGISTRATION DATABASE ERROR: " . $e->getMessage());
                $error_message = "Registration failed. Please try again.";
            }
        }
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
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/register.css">
</head>
<body>

<div class="main-content">
    <div class="register-wrapper">
        <!-- Welcome Section -->
        <div class="welcome-section">
            <div class="welcome-content">
        <h1>Join Akash Enterprise</h1>
        <p>Create your account to access exclusive features and manage your orders</p>
                <div class="welcome-features">
                    <div class="feature-item">
                        <i class="fas fa-shopping-cart"></i>
                        <span>Easy Ordering</span>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-heart"></i>
                        <span>Save Favorites</span>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-truck"></i>
                        <span>Fast Delivery</span>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-headset"></i>
                        <span>24/7 Support</span>
                    </div>
                </div>
            </div>
    </div>

        <!-- Registration Section -->
        <div class="register-section">
        <div class="register-container">
            <div class="register-card">
                <h2>Create Account</h2>
                
                <?php if ($error_message): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_message); ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($success_message): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?>
                        <div style="margin-top: 15px;">
                            <a href="login.php" style="color: #059669; font-weight: 600; text-decoration: underline;">Go to Login →</a>
                        </div>
                    </div>
                    <script>
                        // Auto-redirect after 3 seconds
                        setTimeout(function() {
                            window.location.href = 'login.php?registered=1';
                        }, 3000);
                    </script>
                <?php endif; ?>
                
                <?php if (isGoogleOAuthConfigured() && !$success_message): ?>
                <a href="<?php echo getGoogleAuthUrl(); ?>" class="btn-google">
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M19.8055 10.2292C19.8055 9.55639 19.7502 8.88639 19.6326 8.23181H10.2002V12.0137H15.6014C15.3773 13.2858 14.6571 14.4156 13.6025 15.1358V17.5716H16.8252C18.713 15.8354 19.8055 13.2722 19.8055 10.2292Z" fill="#4285F4"/>
                        <path d="M10.2002 19.9311C12.9591 19.9311 15.2694 19.0273 16.8252 17.5715L13.6025 15.1357C12.7055 15.7509 11.5543 16.1034 10.2002 16.1034C7.53677 16.1034 5.28657 14.3473 4.4918 11.9656H1.16016V14.4831C2.74577 17.6285 6.30977 19.9311 10.2002 19.9311Z" fill="#34A853"/>
                        <path d="M4.49195 11.9655C4.03355 10.6934 4.03355 9.30992 4.49195 8.03779V5.52026H1.16031C-0.386772 8.58352 -0.386772 12.4198 1.16031 15.483L4.49195 11.9655Z" fill="#FBBC04"/>
                        <path d="M10.2002 3.89891C11.6284 3.87606 13.0087 4.42163 14.0362 5.40853L16.8938 2.55087C15.1838 0.941412 12.9316 0.0652466 10.2002 0.0926561C6.30977 0.0926561 2.74577 2.39525 1.16016 5.54053L4.49179 8.05806C5.28657 5.67631 7.53677 3.89891 10.2002 3.89891Z" fill="#EA4335"/>
                    </svg>
                    Sign up with Google
                </a>
                
                <div class="divider">
                    <span>OR</span>
                </div>
                <?php endif; ?>
                
                <?php if (!$success_message): ?>
                <form method="POST" action="" id="registerForm">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="username" class="form-label">Username *</label>
                            <div class="input-group">
                                <input type="text" 
                                       id="username" 
                                       name="username" 
                                       class="form-control" 
                                       placeholder="Choose a username"
                                       value="<?php echo isset($_POST['username']) && !$success_message ? htmlspecialchars($_POST['username']) : ''; ?>"
                                       autocomplete="username"
                                       required>
                                <i class="fas fa-user input-icon"></i>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="email" class="form-label">Email *</label>
                            <div class="input-group">
                                <input type="email" 
                                       id="email" 
                                       name="email" 
                                       class="form-control" 
                                       placeholder="Enter your email"
                                       value="<?php echo isset($_POST['email']) && !$success_message ? htmlspecialchars($_POST['email']) : ''; ?>"
                                       autocomplete="email"
                                       required>
                                <i class="fas fa-envelope input-icon"></i>
                            </div>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="password" class="form-label">Password *</label>
                            <div class="input-group">
                                <input type="password" 
                                       id="password" 
                                       name="password" 
                                       class="form-control" 
                                       placeholder="Create a password"
                                       autocomplete="new-password"
                                       required>
                                <i class="fas fa-lock input-icon"></i>
                                <button type="button" class="password-toggle" onclick="togglePassword('password')">
                                    <i class="fas fa-eye" id="password-icon"></i>
                                </button>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="confirm_password" class="form-label">Confirm Password *</label>
                            <div class="input-group">
                                <input type="password" 
                                       id="confirm_password" 
                                       name="confirm_password" 
                                       class="form-control" 
                                       placeholder="Confirm your password"
                                       autocomplete="new-password"
                                       required>
                                <i class="fas fa-lock input-icon"></i>
                                <button type="button" class="password-toggle" onclick="togglePassword('confirm_password')">
                                    <i class="fas fa-eye" id="confirm_password-icon"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="phone_number" class="form-label">Phone Number</label>
                        <div class="input-group">
                            <input type="tel" 
                                   id="phone_number" 
                                   name="phone_number" 
                                   class="form-control" 
                                   placeholder="Enter your phone number"
                                   autocomplete="tel"
                                   value="<?php echo isset($_POST['phone_number']) && !$success_message ? htmlspecialchars($_POST['phone_number']) : ''; ?>">
                            <i class="fas fa-phone input-icon"></i>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="address" class="form-label">Address</label>
                        <div class="input-group">
                            <textarea id="address" 
                                      name="address" 
                                      class="form-control" 
                                      rows="3" 
                                      style="padding-left: 50px;"
                                      placeholder="Enter your address"><?php echo isset($_POST['address']) && !$success_message ? htmlspecialchars($_POST['address']) : ''; ?></textarea>
                            <i class="fas fa-map-marker-alt input-icon"></i>
                        </div>
                    </div>

                    <div class="terms-checkbox">
                        <input type="checkbox" id="terms" name="terms" required>
                        <label for="terms">
                            I agree to the <a href="<?php echo BASE_URL; ?>/public/pages/terms.php" target="_blank">Terms of Service</a> and <a href="<?php echo BASE_URL; ?>/public/pages/privacy.php" target="_blank">Privacy Policy</a>
                        </label>
                    </div>

                    <button type="submit" name="register" class="btn btn-primary" id="registerBtn">
                        <i class="fas fa-user-plus me-2"></i> Create Account
                    </button>
                </form>
                <?php endif; ?>
                
                <div class="register-links">
                    <p>Already have an account? <a href="login.php" class="login-link">Sign in here</a></p>
                </div>
            </div>
        </div>
    </div>
</div><!-- /main-content -->

<!-- Login Link in Top Corner -->
<div class="top-corner-login">
    <a href="login.php" class="login-corner-link" title="Sign In">
        <i class="fas fa-sign-in-alt"></i>
        <span>Login</span>
    </a>
</div>

<script>
// Smooth page transition for login link
document.addEventListener('DOMContentLoaded', function() {
    const loginLinks = document.querySelectorAll('.login-link, .login-corner-link');
    
    loginLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Add fade out animation
            document.body.style.transition = 'opacity 0.3s ease-out';
            document.body.style.opacity = '0';
            
            // Navigate after animation
            setTimeout(() => {
                window.location.href = this.href;
            }, 300);
        });
    });
});

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
</script>

</body>
</html>