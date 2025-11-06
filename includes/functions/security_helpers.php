<?php
/**
 * Security Helper Functions
 * Provides security-related utilities for the authentication system
 */

/**
 * Generate a secure random token
 * @param int $length Token length in bytes
 * @return string Hexadecimal token
 */
function generateSecureToken($length = 32) {
    return bin2hex(random_bytes($length));
}

/**
 * Validate password strength
 * @param string $password
 * @return array ['valid' => bool, 'errors' => array]
 */
function validatePasswordStrength($password) {
    $errors = [];
    
    if (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters long";
    }
    
    if (strlen($password) > 128) {
        $errors[] = "Password must be less than 128 characters";
    }
    
    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = "Password must contain at least one lowercase letter";
    }
    
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = "Password must contain at least one uppercase letter";
    }
    
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = "Password must contain at least one number";
    }
    
    if (!preg_match('/[^a-zA-Z0-9]/', $password)) {
        $errors[] = "Password must contain at least one special character";
    }
    
    return [
        'valid' => empty($errors),
        'errors' => $errors
    ];
}

/**
 * Sanitize input data
 * @param mixed $data
 * @return mixed
 */
function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate email address
 * @param string $email
 * @return bool
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate username
 * @param string $username
 * @return array ['valid' => bool, 'error' => string|null]
 */
function validateUsername($username) {
    if (empty($username)) {
        return ['valid' => false, 'error' => 'Username is required'];
    }
    
    if (strlen($username) < 3) {
        return ['valid' => false, 'error' => 'Username must be at least 3 characters long'];
    }
    
    if (strlen($username) > 20) {
        return ['valid' => false, 'error' => 'Username must be less than 20 characters'];
    }
    
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        return ['valid' => false, 'error' => 'Username can only contain letters, numbers, and underscores'];
    }
    
    return ['valid' => true, 'error' => null];
}

/**
 * Validate phone number
 * @param string $phone
 * @return bool
 */
function isValidPhone($phone) {
    $cleaned = preg_replace('/[\s\-\(\)]/', '', $phone);
    return preg_match('/^[\+]?[1-9][\d]{0,15}$/', $cleaned);
}

/**
 * Rate limiting for login attempts
 * @param string $identifier (IP address or username)
 * @param int $max_attempts Maximum attempts allowed
 * @param int $time_window Time window in seconds
 * @return bool True if rate limit exceeded
 */
function isRateLimited($identifier, $max_attempts = 5, $time_window = 900) {
    $cache_file = sys_get_temp_dir() . '/rate_limit_' . md5($identifier) . '.txt';
    
    if (!file_exists($cache_file)) {
        return false;
    }
    
    $data = json_decode(file_get_contents($cache_file), true);
    
    if (!$data || (time() - $data['first_attempt']) > $time_window) {
        unlink($cache_file);
        return false;
    }
    
    return $data['attempts'] >= $max_attempts;
}

/**
 * Get remaining login attempts for an identifier
 * @param string $identifier
 * @param int $max_attempts
 * @param int $time_window
 * @return array ['remaining' => int, 'time_remaining' => int]
 */
function getRemainingAttempts($identifier, $max_attempts = 5, $time_window = 900) {
    $cache_file = sys_get_temp_dir() . '/rate_limit_' . md5($identifier) . '.txt';
    
    if (!file_exists($cache_file)) {
        return ['remaining' => $max_attempts, 'time_remaining' => 0];
    }
    
    $data = json_decode(file_get_contents($cache_file), true);
    
    if (!$data || (time() - $data['first_attempt']) > $time_window) {
        unlink($cache_file);
        return ['remaining' => $max_attempts, 'time_remaining' => 0];
    }
    
    $time_remaining = $time_window - (time() - $data['first_attempt']);
    $remaining = max(0, $max_attempts - $data['attempts']);
    
    return [
        'remaining' => $remaining,
        'time_remaining' => max(0, $time_remaining)
    ];
}

/**
 * Record failed login attempt
 * @param string $identifier
 */
function recordFailedAttempt($identifier) {
    $cache_file = sys_get_temp_dir() . '/rate_limit_' . md5($identifier) . '.txt';
    
    $data = [
        'attempts' => 1,
        'first_attempt' => time(),
        'last_attempt' => time()
    ];
    
    if (file_exists($cache_file)) {
        $existing_data = json_decode(file_get_contents($cache_file), true);
        if ($existing_data && (time() - $existing_data['first_attempt']) <= 900) {
            $data['attempts'] = $existing_data['attempts'] + 1;
            $data['first_attempt'] = $existing_data['first_attempt'];
        }
    }
    
    file_put_contents($cache_file, json_encode($data));
}

/**
 * Clear failed attempts for an identifier
 * @param string $identifier
 */
function clearFailedAttempts($identifier) {
    $cache_file = sys_get_temp_dir() . '/rate_limit_' . md5($identifier) . '.txt';
    if (file_exists($cache_file)) {
        unlink($cache_file);
    }
}

/**
 * Generate CSRF token
 * @return string
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = generateSecureToken();
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validate CSRF token
 * @param string $token
 * @return bool
 */
function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && secure_equals($_SESSION['csrf_token'], $token);
}

/**
 * Secure string comparison - compatible with older PHP versions
 * @param string $a
 * @param string $b
 * @return bool
 */
function secure_equals($a, $b) {
    // Use hash_equals if available (PHP 5.6+)
    if (function_exists('hash_equals')) {
        return hash_equals($a, $b);
    }

    // Fallback for older PHP versions
    $a_len = strlen($a);
    $b_len = strlen($b);

    if ($a_len !== $b_len) {
        return false;
    }

    $result = 0;
    for ($i = 0; $i < $a_len; $i++) {
        $result |= ord($a[$i]) ^ ord($b[$i]);
    }

    return $result === 0;
}

/**
 * Secure session configuration
 */
function configureSecureSession() {
    // Only configure session if not already started
    if (session_status() === PHP_SESSION_NONE) {
        // Set secure session parameters
        ini_set('session.cookie_httponly', 1);
        ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
        ini_set('session.use_strict_mode', 1);
        ini_set('session.cookie_samesite', 'Strict');
        
        // Set session timeout (30 minutes)
        ini_set('session.gc_maxlifetime', 1800);
        
        // Start session
        session_start();
    }
}

/**
 * Check if user is logged in and session is valid
 * @return bool
 */
function isUserLoggedIn() {
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['last_activity'])) {
        return false;
    }
    
    // Check session timeout (30 minutes)
    if ((time() - $_SESSION['last_activity']) > 1800) {
        session_unset();
        session_destroy();
        return false;
    }
    
    // Update last activity
    $_SESSION['last_activity'] = time();
    
    return true;
}

/**
 * Log security events
 * @param string $event
 * @param array $data
 */
function logSecurityEvent($event, $data = []) {
    $log_entry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'event' => $event,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        'data' => $data
    ];
    
    error_log("SECURITY: " . json_encode($log_entry));
}

/**
 * Hash password with current best practices
 * @param string $password
 * @return string
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT, ['cost' => 12]);
}

/**
 * Verify password against hash
 * @param string $password
 * @param string $hash
 * @return bool
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Create a secure remember me token
 * @param int $user_id
 * @param PDO $pdo
 * @return string|false Token or false on error
 */
function createRememberToken($user_id, $pdo) {
    try {
        // Generate secure token
        $token = bin2hex(random_bytes(32));
        $token_hash = hash('sha256', $token);
        
        // Set expiry (30 days)
        $expires_at = date('Y-m-d H:i:s', time() + (30 * 24 * 60 * 60));
        
        // Store in database
        $stmt = $pdo->prepare("
            INSERT INTO remember_tokens (user_id, token_hash, expires_at, user_agent, ip_address) 
            VALUES (?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $user_id,
            $token_hash,
            $expires_at,
            $_SERVER['HTTP_USER_AGENT'] ?? '',
            $_SERVER['REMOTE_ADDR'] ?? ''
        ]);
        
        // Clean up old tokens for this user
        cleanupOldRememberTokens($user_id, $pdo);
        
        return $token;
    } catch (PDOException $e) {
        error_log("Error creating remember token: " . $e->getMessage());
        return false;
    }
}

/**
 * Validate remember me token
 * @param string $token
 * @param PDO $pdo
 * @return array|false User data or false if invalid
 */
function validateRememberToken($token, $pdo) {
    try {
        $token_hash = hash('sha256', $token);
        
        $stmt = $pdo->prepare("
            SELECT rt.user_id, rt.expires_at, a.id, a.username, a.email, a.status
            FROM remember_tokens rt
            JOIN admins a ON rt.user_id = a.id
            WHERE rt.token_hash = ? AND rt.is_active = 1 AND rt.expires_at > NOW()
        ");
        
        $stmt->execute([$token_hash]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result && $result['status'] === 'active') {
            // Update last used timestamp
            $updateStmt = $pdo->prepare("
                UPDATE remember_tokens 
                SET last_used_at = NOW() 
                WHERE token_hash = ?
            ");
            $updateStmt->execute([$token_hash]);
            
            return $result;
        }
        
        return false;
    } catch (PDOException $e) {
        error_log("Error validating remember token: " . $e->getMessage());
        return false;
    }
}

/**
 * Delete remember me token
 * @param string $token
 * @param PDO $pdo
 * @return bool
 */
function deleteRememberToken($token, $pdo) {
    try {
        $token_hash = hash('sha256', $token);
        
        $stmt = $pdo->prepare("DELETE FROM remember_tokens WHERE token_hash = ?");
        return $stmt->execute([$token_hash]);
    } catch (PDOException $e) {
        error_log("Error deleting remember token: " . $e->getMessage());
        return false;
    }
}

/**
 * Delete all remember tokens for a user
 * @param int $user_id
 * @param PDO $pdo
 * @return bool
 */
function deleteAllRememberTokens($user_id, $pdo) {
    try {
        $stmt = $pdo->prepare("DELETE FROM remember_tokens WHERE user_id = ?");
        return $stmt->execute([$user_id]);
    } catch (PDOException $e) {
        error_log("Error deleting all remember tokens: " . $e->getMessage());
        return false;
    }
}

/**
 * Clean up expired remember tokens
 * @param PDO $pdo
 * @return int Number of tokens deleted
 */
function cleanupExpiredRememberTokens($pdo) {
    try {
        $stmt = $pdo->prepare("DELETE FROM remember_tokens WHERE expires_at < NOW()");
        $stmt->execute();
        return $stmt->rowCount();
    } catch (PDOException $e) {
        error_log("Error cleaning up expired remember tokens: " . $e->getMessage());
        return 0;
    }
}

/**
 * Clean up old remember tokens for a specific user (keep only 5 most recent)
 * @param int $user_id
 * @param PDO $pdo
 * @return bool
 */
function cleanupOldRememberTokens($user_id, $pdo) {
    try {
        $stmt = $pdo->prepare("
            DELETE FROM remember_tokens 
            WHERE user_id = ? AND id NOT IN (
                SELECT id FROM (
                    SELECT id FROM remember_tokens 
                    WHERE user_id = ? 
                    ORDER BY created_at DESC 
                    LIMIT 5
                ) AS keep_tokens
            )
        ");
        return $stmt->execute([$user_id, $user_id]);
    } catch (PDOException $e) {
        error_log("Error cleaning up old remember tokens: " . $e->getMessage());
        return false;
    }
}

/**
 * Encrypt sensitive data for URL parameters
 * @param string $data Data to encrypt
 * @return string Base64 encoded encrypted data
 */
function encryptUrlParam($data) {
    // Get encryption key from config or use a default (should be in config)
    $key = defined('ENCRYPTION_KEY') ? ENCRYPTION_KEY : hash('sha256', 'akash_enterprise_encryption_key_2024');
    $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('AES-256-CBC'));
    $encrypted = openssl_encrypt($data, 'AES-256-CBC', $key, 0, $iv);
    return base64_encode($encrypted . '::' . $iv);
}

/**
 * Decrypt sensitive data from URL parameters
 * @param string $encrypted Base64 encoded encrypted data
 * @return string|false Decrypted data or false on failure
 */
function decryptUrlParam($encrypted) {
    $key = defined('ENCRYPTION_KEY') ? ENCRYPTION_KEY : hash('sha256', 'akash_enterprise_encryption_key_2024');
    $data = base64_decode($encrypted);
    list($encrypted_data, $iv) = explode('::', $data, 2);
    return openssl_decrypt($encrypted_data, 'AES-256-CBC', $key, 0, $iv);
}

/**
 * Create a signed URL parameter (HMAC-based, tamper-proof)
 * @param int $id ID to sign
 * @return string Signed token
 */
function signUrlParam($id) {
    $secret = defined('URL_SIGN_SECRET') ? URL_SIGN_SECRET : 'akash_enterprise_url_secret_2024';
    $data = $id . '|' . time(); // Include timestamp for expiration
    $signature = hash_hmac('sha256', $data, $secret);
    return base64_encode($data . '|' . $signature);
}

/**
 * Verify and decode signed URL parameter
 * @param string $token Signed token
 * @param int $max_age Maximum age in seconds (default 3600 = 1 hour)
 * @return int|false Decoded ID or false if invalid/expired
 */
function verifySignedUrlParam($token, $max_age = 3600) {
    $secret = defined('URL_SIGN_SECRET') ? URL_SIGN_SECRET : 'akash_enterprise_url_secret_2024';
    $decoded = base64_decode($token);
    $parts = explode('|', $decoded);
    
    if (count($parts) !== 3) {
        return false;
    }
    
    list($id, $timestamp, $signature) = $parts;
    
    // Check expiration
    if ((time() - (int)$timestamp) > $max_age) {
        return false;
    }
    
    // Verify signature
    $expected_signature = hash_hmac('sha256', $id . '|' . $timestamp, $secret);
    if (!hash_equals($expected_signature, $signature)) {
        return false;
    }
    
    return (int)$id;
}
?>
