<?php
/**
 * Google OAuth Callback Handler
 * Handles the callback from Google OAuth and creates/logs in users
 */

require_once __DIR__ . '/../../includes/config/init.php';
require_once __DIR__ . '/../../includes/config/google_oauth.php';
require_once __DIR__ . '/../../includes/functions/security_helpers.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$error_message = '';
$success_message = '';

try {
    // Check if Google OAuth is configured
    if (!isGoogleOAuthConfigured()) {
        throw new Exception('Google OAuth is not properly configured. Please contact the administrator.');
    }
    
    // Check for authorization code
    if (!isset($_GET['code'])) {
        throw new Exception('Authorization code not received from Google.');
    }
    
    // Verify state parameter for CSRF protection
    if (!isset($_GET['state']) || !verifyCSRFState($_GET['state'])) {
        throw new Exception('Invalid state parameter. Possible CSRF attack.');
    }
    
    $code = $_GET['code'];
    
    // Exchange authorization code for access token
    $tokenData = exchangeCodeForToken($code);
    if (!$tokenData || isset($tokenData['error'])) {
        throw new Exception('Failed to exchange authorization code for access token: ' . 
                          ($tokenData['error_description'] ?? 'Unknown error'));
    }
    
    // Get user information from Google
    $userInfo = getGoogleUserInfo($tokenData['access_token']);
    if (!$userInfo || isset($userInfo['error'])) {
        throw new Exception('Failed to get user information from Google: ' . 
                          ($userInfo['error_description'] ?? 'Unknown error'));
    }
    
    // Validate required user information
    if (empty($userInfo['email']) || empty($userInfo['id'])) {
        throw new Exception('Incomplete user information received from Google.');
    }
    
    // Check if user already exists
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? OR google_id = ?");
    $stmt->execute([$userInfo['email'], $userInfo['id']]);
    $existingUser = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existingUser) {
        // Update existing user with Google ID if not already set
        if (empty($existingUser['google_id'])) {
            $updateStmt = $pdo->prepare("UPDATE users SET google_id = ? WHERE id = ?");
            $updateStmt->execute([$userInfo['id'], $existingUser['id']]);
        }
        
        $user = $existingUser;
    } else {
        // Create new user account
        $username = generateUsernameFromEmail($userInfo['email']);
        $firstName = $userInfo['given_name'] ?? '';
        $lastName = $userInfo['family_name'] ?? '';
        $fullName = trim($firstName . ' ' . $lastName);
        
        // Ensure username is unique
        $username = ensureUniqueUsername($username, $pdo);
        
        $insertStmt = $pdo->prepare("
            INSERT INTO users (
                username, 
                email, 
                first_name, 
                last_name, 
                google_id, 
                status, 
                created_at
            ) VALUES (?, ?, ?, ?, ?, 'active', NOW())
        ");
        
        $insertStmt->execute([
            $username,
            $userInfo['email'],
            $firstName,
            $lastName,
            $userInfo['id']
        ]);
        
        $userId = $pdo->lastInsertId();
        
        // Get the newly created user
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Log successful registration
        logSecurityEvent('google_user_registered', [
            'user_id' => $userId,
            'email' => $userInfo['email'],
            'google_id' => $userInfo['id']
        ]);
    }
    
    // Check if user account is active
    if ($user['status'] !== 'active') {
        throw new Exception('Your account is not active. Please contact support.');
    }
    
    // Regenerate session ID for security
    session_regenerate_id(true);
    
    // Set session variables
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['first_name'] = $user['first_name'];
    $_SESSION['last_name'] = $user['last_name'];
    $_SESSION['login_method'] = 'google';
    $_SESSION['last_activity'] = time();
    $_SESSION['created'] = time();
    
    // Log successful login
    logSecurityEvent('google_login_successful', [
        'user_id' => $user['id'],
        'email' => $user['email'],
        'google_id' => $userInfo['id']
    ]);
    
    // Clear the OAuth state
    unset($_SESSION['google_oauth_state']);
    
    // Redirect to intended page or profile
    $redirect_url = isset($_SESSION['redirect_after_login']) ? $_SESSION['redirect_after_login'] : '../profile/index.php';
    unset($_SESSION['redirect_after_login']);
    
    header('Location: ' . $redirect_url);
    exit();
    
} catch (Exception $e) {
    $error_message = $e->getMessage();
    
    // Log the error
    logSecurityEvent('google_oauth_error', [
        'error' => $error_message,
        'code' => $_GET['code'] ?? null,
        'state' => $_GET['state'] ?? null
    ]);
    
    // Clear the OAuth state
    unset($_SESSION['google_oauth_state']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Google Login - Akash Enterprise</title>
    
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
        display: flex;
        align-items: center;
        justify-content: center;
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
    
    .callback-container {
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(15px);
        border: 1px solid rgba(59, 130, 246, 0.1);
        border-radius: 20px;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        padding: 40px;
        max-width: 500px;
        width: 90%;
        text-align: center;
        position: relative;
        z-index: 1;
    }
    
    .callback-container::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(135deg, #3b82f6, #8b5cf6);
        border-radius: 20px 20px 0 0;
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
    
    .icon-wrapper i {
        font-size: 35px;
        color: white;
    }
    
    .callback-container h2 {
        color: #1e293b;
        font-size: 2rem;
        font-weight: 700;
        margin-bottom: 15px;
    }
    
    .callback-container p {
        color: #64748b;
        margin-bottom: 30px;
        font-size: 1rem;
        line-height: 1.6;
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
    
    .btn-primary {
        background: linear-gradient(135deg, #3b82f6, #1d4ed8);
        border: none;
        padding: 15px 35px;
        border-radius: 25px;
        font-weight: 600;
        transition: all 0.3s ease;
        box-shadow: 0 8px 20px rgba(59, 130, 246, 0.3);
        color: white;
        text-decoration: none;
        display: inline-block;
        margin: 10px;
    }
    
    .btn-primary:hover {
        background: linear-gradient(135deg, #2563eb, #1e40af);
        transform: translateY(-2px);
        box-shadow: 0 12px 25px rgba(59, 130, 246, 0.4);
        color: white;
        text-decoration: none;
    }
    
    .spinner {
        display: inline-block;
        width: 20px;
        height: 20px;
        border: 3px solid #ffffff;
        border-radius: 50%;
        border-top-color: transparent;
        animation: spin 1s ease-in-out infinite;
    }
    
    @keyframes spin {
        to { transform: rotate(360deg); }
    }
    
    @media (max-width: 768px) {
        .callback-container {
            padding: 30px 20px;
            margin: 20px;
        }
        
        .callback-container h2 {
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
    <div class="callback-container">
        <?php if ($error_message): ?>
            <div class="icon-wrapper error">
                <i class="fas fa-times"></i>
            </div>
            <h2>Login Failed</h2>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_message); ?>
            </div>
            <p>There was an error with your Google login. Please try again or use the regular login form.</p>
            <a href="login.php" class="btn-primary">
                <i class="fas fa-arrow-left me-2"></i> Back to Login
            </a>
        <?php else: ?>
            <div class="icon-wrapper success">
                <i class="fas fa-check"></i>
            </div>
            <h2>Login Successful!</h2>
            <p>You have been successfully logged in with your Google account. Redirecting...</p>
            <div class="spinner"></div>
        <?php endif; ?>
    </div>
    
    <script>
    // Auto-redirect after 3 seconds if successful
    <?php if (empty($error_message)): ?>
    setTimeout(function() {
        window.location.href = '../profile/index.php';
    }, 3000);
    <?php endif; ?>
    </script>
</body>
</html>

<?php
/**
 * Helper function to generate username from email
 */
function generateUsernameFromEmail($email) {
    $username = explode('@', $email)[0];
    $username = preg_replace('/[^a-zA-Z0-9_]/', '', $username);
    return $username ?: 'user' . time();
}

/**
 * Helper function to ensure username is unique
 */
function ensureUniqueUsername($username, $pdo) {
    $originalUsername = $username;
    $counter = 1;
    
    while (true) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        
        if (!$stmt->fetch()) {
            return $username;
        }
        
        $username = $originalUsername . $counter;
        $counter++;
    }
}
?>
