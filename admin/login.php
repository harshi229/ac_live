<?php
require_once dirname(__DIR__) . '/includes/config/init.php';
require_once INCLUDES_PATH . '/functions/security_helpers.php';

// Configure secure session
configureSecureSession();

$error = '';
$success = '';

// Check for remember me token
if (!isset($_SESSION['admin_id']) && isset($_COOKIE['admin_remember_token'])) {
    $remember_token = $_COOKIE['admin_remember_token'];
    $user_data = validateRememberToken($remember_token, $pdo);
    
    if ($user_data) {
        // Regenerate session ID for security
        session_regenerate_id(true);
        
        // Store admin data in session
        $_SESSION['admin_id'] = $user_data['id'];
        $_SESSION['admin_username'] = $user_data['username'];
        $_SESSION['last_activity'] = time();
        $_SESSION['created'] = time();
        
        // Log automatic login
        logSecurityEvent('ADMIN_AUTO_LOGIN', ['admin_id' => $user_data['id'], 'username' => $user_data['username']]);
        
        header('Location: ' . admin_url());
        exit();
    } else {
        // Invalid token, clear cookie
        setcookie('admin_remember_token', '', time() - 3600, '/', '', isset($_SERVER['HTTPS']), true);
    }
}

// Handle session expired/inactive messages
if (isset($_GET['session_expired'])) {
    $error = "Your session has expired. Please login again.";
}
if (isset($_GET['account_inactive'])) {
    $error = "Your account is inactive. Please contact an administrator.";
}
if (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case '1':
            $error = "A system error occurred. Please try again or contact an administrator.";
            break;
        case '2':
            $error = "Access denied. Please login to continue.";
            break;
        default:
            $error = "An error occurred. Please try again.";
    }
}
if (isset($_GET['registered'])) {
    $success = "Admin account created successfully! Please login with your credentials.";
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // CSRF Protection
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        $error = "Invalid request. Please try again.";
        logSecurityEvent('CSRF_TOKEN_INVALID', ['ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown']);
    } else {
        $username = sanitizeInput($_POST['username']);
        $password = $_POST['password'];
        
        // Input validation
        $username_validation = validateUsername($username);
        if (!$username_validation['valid']) {
            $error = $username_validation['error'];
        } elseif (empty($password)) {
            $error = "Password is required";
        } else {
            // Rate limiting check
            $client_ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            if (isRateLimited($client_ip)) {
                $cache_file = sys_get_temp_dir() . '/rate_limit_' . md5($client_ip) . '.txt';
                $time_remaining = 0;
                
                if (file_exists($cache_file)) {
                    $data = json_decode(file_get_contents($cache_file), true);
                    if ($data) {
                        $time_remaining = 900 - (time() - $data['first_attempt']);
                        if ($time_remaining < 0) $time_remaining = 0;
                    }
                }
                
                $minutes = ceil($time_remaining / 60);
                $error = "Too many login attempts. Please try again in $minutes minute(s). <a href='clear_rate_limit.php' class='text-decoration-none'>Clear rate limit</a>";
                logSecurityEvent('RATE_LIMIT_EXCEEDED', ['ip' => $client_ip, 'username' => $username]);
            } else {
                // Query to fetch the admin user
                $query = $pdo->prepare("SELECT * FROM admins WHERE username = ? AND status = 'active'");
                $query->execute([$username]);
                $admin = $query->fetch(PDO::FETCH_ASSOC);

                if ($admin && password_verify($password, $admin['password'])) {
                    // Clear any failed attempts
                    clearFailedAttempts($client_ip);
                    
                    // Regenerate session ID for security
                    session_regenerate_id(true);
                    
                    // Store admin data in session
                    $_SESSION['admin_id'] = $admin['id'];
                    $_SESSION['admin_username'] = $admin['username'];
                    $_SESSION['last_activity'] = time();
                    $_SESSION['created'] = time();
                    
                    // Handle remember me functionality
                    if (isset($_POST['remember_me']) && $_POST['remember_me'] == '1') {
                        $remember_token = createRememberToken($admin['id'], $pdo);
                        if ($remember_token) {
                            setcookie('admin_remember_token', $remember_token, time() + (30 * 24 * 60 * 60), '/', '', isset($_SERVER['HTTPS']), true);
                        }
                    }
                    
                    // Log successful login
                    logSecurityEvent('ADMIN_LOGIN_SUCCESS', ['admin_id' => $admin['id'], 'username' => $username]);
                    
                    header('Location: ' . admin_url());
                    exit();
                } else {
                    // Record failed attempt
                    recordFailedAttempt($client_ip);
                    $error = "Invalid username or password";
                    logSecurityEvent('ADMIN_LOGIN_FAILED', ['username' => $username, 'ip' => $client_ip]);
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
    <title>Admin Login - Air Conditioning System</title>
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 0;
            overflow-x: hidden;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .login-container {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }
        .form-section {
            background: rgba(255, 255, 255, 0.95);
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            max-width: 450px;
            width: 100%;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        .logo-section {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo-icon {
            font-size: 3rem;
            color: #667eea;
            margin-bottom: 10px;
        }
        h1 {
            color: #333;
            font-weight: 700;
            margin-bottom: 5px;
        }
        .subtitle {
            color: #666;
            font-size: 0.9rem;
        }
        .alert {
            margin-bottom: 20px;
            border-radius: 8px;
            border: none;
        }
        .form-label {
            color: #333;
            font-weight: 600;
            margin-bottom: 8px;
        }
        .form-control {
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            padding: 12px 15px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        .form-control:focus {
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
            border-color: #667eea;
        }
        .input-group-text {
            background: #f8f9fa;
            border: 2px solid #e1e5e9;
            border-right: none;
            border-radius: 8px 0 0 8px;
        }
        .input-group .form-control {
            border-left: none;
            border-radius: 0 8px 8px 0;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 8px;
            padding: 12px;
            font-weight: 600;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        .btn-outline-secondary {
            border: 2px solid #e1e5e9;
            color: #666;
            border-radius: 8px;
            padding: 12px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-outline-secondary:hover {
            background: #f8f9fa;
            border-color: #667eea;
            color: #667eea;
        }
        .password-toggle {
            cursor: pointer;
            color: #666;
            transition: color 0.3s ease;
        }
        .password-toggle:hover {
            color: #667eea;
        }
        .remember-me {
            display: flex;
            align-items: center;
            margin: 15px 0;
        }
        .remember-me input[type="checkbox"] {
            margin-right: 8px;
        }
        .forgot-password {
            text-align: right;
            margin-top: 10px;
        }
        .forgot-password a {
            color: #667eea;
            text-decoration: none;
            font-size: 0.9rem;
        }
        .forgot-password a:hover {
            text-decoration: underline;
        }
        .register-link {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #e1e5e9;
        }
        .register-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }
        .register-link a:hover {
            text-decoration: underline;
        }
        .loading {
            display: none;
        }
        .loading.show {
            display: inline-block;
        }
        @media (max-width: 768px) {
            .form-section {
                margin: 10px;
                padding: 30px 20px;
            }
            .logo-icon {
                font-size: 2.5rem;
            }
        }
    </style>
</head>
<body>
<main class="login-container">
    <div class="form-section">
        <div class="logo-section">
            <div class="logo-icon">
                <i class="fas fa-crown"></i>
            </div>
            <h1>Admin Login</h1>
            <p class="subtitle">Air Conditioning Management System</p>
        </div>

        <?php if (isset($error) && !empty($error)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle me-2"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($success) && !empty($success)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle me-2"></i>
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <form method="post" id="loginForm">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            
            <div class="mb-3">
                <label for="username" class="form-label">
                    <i class="fas fa-user me-1"></i> Username
                </label>
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="fas fa-user"></i>
                    </span>
                    <input type="text" class="form-control" name="username" id="username" 
                           required maxlength="20" pattern="[a-zA-Z0-9_]+" 
                           title="Username can only contain letters, numbers, and underscores"
                           placeholder="Enter your username"
                           value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                </div>
            </div>
            
            <div class="mb-3">
                <label for="password" class="form-label">
                    <i class="fas fa-lock me-1"></i> Password
                </label>
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="fas fa-lock"></i>
                    </span>
                    <input type="password" class="form-control" name="password" id="password" 
                           required minlength="6" maxlength="128"
                           placeholder="Enter your password">
                    <span class="input-group-text password-toggle" onclick="togglePassword()">
                        <i class="fas fa-eye" id="passwordToggleIcon"></i>
                    </span>
                </div>
            </div>

            <div class="remember-me">
                <input type="checkbox" id="remember_me" name="remember_me" value="1">
                <label for="remember_me" class="form-label mb-0">Remember me for 30 days</label>
            </div>

            <div class="forgot-password">
                <a href="#" onclick="showForgotPassword()">Forgot your password?</a>
            </div>
            
            <button type="submit" class="btn btn-primary w-100" id="loginBtn">
                <span class="login-text">
                    <i class="fas fa-sign-in-alt me-2"></i>Login
                </span>
                <span class="loading">
                    <i class="fas fa-spinner fa-spin me-2"></i>Logging in...
                </span>
            </button>
        </form>

        <div class="register-link">
            <p class="mb-0">Don't have an account?</p>
            <a href="register.php">
                <i class="fas fa-user-plus me-1"></i>Create Admin Account
            </a>
        </div>
    </div>
</main>

<script>
function togglePassword() {
    const passwordField = document.getElementById('password');
    const toggleIcon = document.getElementById('passwordToggleIcon');
    
    if (passwordField.type === 'password') {
        passwordField.type = 'text';
        toggleIcon.classList.remove('fa-eye');
        toggleIcon.classList.add('fa-eye-slash');
    } else {
        passwordField.type = 'password';
        toggleIcon.classList.remove('fa-eye-slash');
        toggleIcon.classList.add('fa-eye');
    }
}

function showForgotPassword() {
    alert('Password reset functionality will be implemented soon. Please contact the system administrator.');
}

document.getElementById('loginForm').addEventListener('submit', function() {
    const loginBtn = document.getElementById('loginBtn');
    const loginText = loginBtn.querySelector('.login-text');
    const loading = loginBtn.querySelector('.loading');
    
    loginText.classList.add('loading');
    loading.classList.add('show');
    loginBtn.disabled = true;
});

// Auto-focus on username field
document.getElementById('username').focus();
</script>
</body>
</html>

