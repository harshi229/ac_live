<?php
// Set page metadata
$pageTitle = 'Login';
$pageDescription = 'Login to your Akash Enterprise account to access exclusive features and manage your orders';
$pageKeywords = 'login, sign in, account access, user login, AC account';

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

// Handle login form submission
$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login'])) {
    // CSRF Protection - Check if CSRF token exists and is valid
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error_message = "Security validation failed. Please try again.";
    } else {
        $username = trim($_POST['username']);
        $password = $_POST['password'];
        $remember_me = isset($_POST['remember']) ? true : false;

    // Rate limiting check
    $client_ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $rate_limit_file = sys_get_temp_dir() . '/login_rate_limit_' . md5($client_ip) . '.txt';

    if (file_exists($rate_limit_file)) {
        $rate_data = json_decode(file_get_contents($rate_limit_file), true);
        if ($rate_data && (time() - $rate_data['first_attempt']) <= 900) { // 15 minutes
            if ($rate_data['attempts'] >= 5) {
                $error_message = "Too many login attempts. Please try again in 15 minutes.";
            }
        }
    }

    if (empty($error_message)) {
        if (empty($username) || empty($password)) {
            $error_message = "Please fill in all fields.";
        } else {
        try {
            // Check if user exists and is active
            $stmt = $pdo->prepare("SELECT * FROM users WHERE (username = ? OR email = ?) AND status = 'active'");
            $stmt->execute([$username, $username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && password_verify($password, $user['password'])) {
                // IMPORTANT: Regenerate session ID for security
                session_regenerate_id(true);
                
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['first_name'] = $user['first_name'] ?? '';
                $_SESSION['last_name'] = $user['last_name'] ?? '';
                $_SESSION['last_activity'] = time();
                $_SESSION['created'] = time();
                $_SESSION['logged_in'] = true; // Extra flag
                
                // Verify session was set
                if (!isset($_SESSION['user_id'])) {
                    $error_message = "Login error: Session not created. Please try again.";
                } else {
                    // Handle remember me functionality
                    if ($remember_me) {
                        $remember_token = createRememberToken($user['id'], $pdo);
                        if ($remember_token) {
                            $expiry = time() + (30 * 24 * 60 * 60); // 30 days
                            setcookie('remember_token', $remember_token, $expiry, '/', '', false, true);
                        }
                    }
                    
                    // Clear rate limiting on successful login
                    if (file_exists($rate_limit_file)) {
                        unlink($rate_limit_file);
                    }

                    // Success message
                    $success_message = "Login successful! Redirecting...";

                    // CRITICAL: Determine redirect URL
                    $redirect_url = isset($_SESSION['redirect_after_login']) ? $_SESSION['redirect_after_login'] : USER_URL . '/profile/index.php';
                    unset($_SESSION['redirect_after_login']);
                    
                    // Use proper PHP header redirect
                    header("Location: " . $redirect_url);
                    exit();
                }
            } else {
                $error_message = "Invalid username/email or password.";

                // Record failed attempt for rate limiting
                $rate_data = [
                    'attempts' => 1,
                    'first_attempt' => time(),
                    'last_attempt' => time()
                ];

                if (file_exists($rate_limit_file)) {
                    $existing_data = json_decode(file_get_contents($rate_limit_file), true);
                    if ($existing_data && (time() - $existing_data['first_attempt']) <= 900) {
                        $rate_data['attempts'] = $existing_data['attempts'] + 1;
                        $rate_data['first_attempt'] = $existing_data['first_attempt'];
                    }
                }

                file_put_contents($rate_limit_file, json_encode($rate_data));
            }
        } catch (PDOException $e) {
            $error_message = "Login failed. Please try again.";
        }
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
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/login.css">
</head>
<body>

<div class="main-content">
    <div class="login-wrapper">
        <!-- Welcome Section -->
        <div class="welcome-section">
            <div class="welcome-content">
                <h1>Welcome Back</h1>
                <p>Login to your account to access exclusive features and manage your orders</p>
                <div class="welcome-features">
                    <div class="feature-item">
                        <i class="fas fa-shopping-cart"></i>
                        <span>Manage Orders</span>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-heart"></i>
                        <span>Wishlist</span>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-user"></i>
                        <span>Profile Settings</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Login Section -->
        <div class="login-section">
            <div class="login-container">
                <div class="login-card">
                <h2>Sign In</h2>
                
                <?php if ($error_message): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_message); ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($success_message): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="" id="loginForm">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <div class="form-group">
                        <label for="username" class="form-label">Username or Email</label>
                        <div class="input-group">
                            <input type="text" 
                                   id="username" 
                                   name="username" 
                                   class="form-control" 
                                   placeholder="Enter your username or email"
                                   value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                                   autocomplete="username"
                                   required>
                            <i class="fas fa-user input-icon"></i>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="password" class="form-label">Password</label>
                        <div class="input-group">
                            <input type="password" 
                                   id="password" 
                                   name="password" 
                                   class="form-control" 
                                   placeholder="Enter your password"
                                   autocomplete="current-password"
                                   required>
                            <i class="fas fa-lock input-icon"></i>
                            <button type="button" class="password-toggle" onclick="togglePassword('password')">
                                <i class="fas fa-eye" id="password-icon"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="remember-me">
                        <input type="checkbox" id="remember" name="remember">
                        <label for="remember">Remember me for 30 days</label>
                    </div>
                    
                    <button type="submit" name="login" class="btn btn-primary" id="loginBtn">
                        <i class="fas fa-sign-in-alt me-2"></i> Sign In
                    </button>
                </form>
                
                <?php if (isGoogleOAuthConfigured()): ?>
                <div class="divider">
                    <span>OR</span>
                </div>
                
                <a href="<?php echo getGoogleAuthUrl(); ?>" class="btn-google">
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M19.8055 10.2292C19.8055 9.55639 19.7502 8.88639 19.6326 8.23181H10.2002V12.0137H15.6014C15.3773 13.2858 14.6571 14.4156 13.6025 15.1358V17.5716H16.8252C18.713 15.8354 19.8055 13.2722 19.8055 10.2292Z" fill="#4285F4"/>
                        <path d="M10.2002 19.9311C12.9591 19.9311 15.2694 19.0273 16.8252 17.5715L13.6025 15.1357C12.7055 15.7509 11.5543 16.1034 10.2002 16.1034C7.53677 16.1034 5.28657 14.3473 4.4918 11.9656H1.16016V14.4831C2.74577 17.6285 6.30977 19.9311 10.2002 19.9311Z" fill="#34A853"/>
                        <path d="M4.49195 11.9655C4.03355 10.6934 4.03355 9.30992 4.49195 8.03779V5.52026H1.16031C-0.386772 8.58352 -0.386772 12.4198 1.16031 15.483L4.49195 11.9655Z" fill="#FBBC04"/>
                        <path d="M10.2002 3.89891C11.6284 3.87606 13.0087 4.42163 14.0362 5.40853L16.8938 2.55087C15.1838 0.941412 12.9316 0.0652466 10.2002 0.0926561C6.30977 0.0926561 2.74577 2.39525 1.16016 5.54053L4.49179 8.05806C5.28657 5.67631 7.53677 3.89891 10.2002 3.89891Z" fill="#EA4335"/>
                    </svg>
                    Sign in with Google
                </a>
                <?php endif; ?>
                
                <a href="register.php" class="btn-register">
                    <i class="fas fa-user-plus"></i>
                    Create Account
                </a>
                
                    <div class="login-links">
                        <p>Don't have an account? <a href="register.php" class="register-link">Create one here</a></p>
                        <p><a href="forgot_password.php">Forgot your password?</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div><!-- /main-content -->

<script>
// Smooth page transition for registration link
document.addEventListener('DOMContentLoaded', function() {
    const registerLinks = document.querySelectorAll('a[href="register.php"].btn-register');
    
    registerLinks.forEach(link => {
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