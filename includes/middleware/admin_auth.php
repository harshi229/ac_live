<?php
/**
 * Admin Authentication Middleware
 * Ensures only authenticated admin users can access admin pages
 */

require_once dirname(__DIR__) . '/functions/security_helpers.php';

// Ensure database connection is available
if (!isset($pdo)) {
    require_once dirname(__DIR__) . '/config/database.php';
}

// Configure secure session
configureSecureSession();

// Get the current file name
$current_file = basename($_SERVER['PHP_SELF']);

// Pages that don't require authentication
$public_admin_pages = ['login.php'];

// Check if current page requires authentication
if (!in_array($current_file, $public_admin_pages)) {
    // Check if admin is logged in
    if (!isset($_SESSION['admin_id']) || empty($_SESSION['admin_id'])) {
        // Store the intended destination
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? '/admin';
        
        // Redirect to login page
        header('Location: ' . admin_url('login'));
        exit();
    }
    
    // Check if session has expired
    if (isset($_SESSION['last_activity'])) {
        $inactive_time = time() - $_SESSION['last_activity'];
        $session_lifetime = defined('SESSION_LIFETIME') ? SESSION_LIFETIME : 3600; // Default 1 hour
        
        if ($inactive_time > $session_lifetime) {
            // Session expired
            session_unset();
            session_destroy();
            
            header('Location: ' . admin_url('login') . '?session_expired=1');
            exit();
        }
    }
    
    // Check if admin account is still active
    if (isset($_SESSION['admin_id'])) {
        try {
            $admin_check = $pdo->prepare("SELECT status, last_login FROM admins WHERE id = ?");
            $admin_check->execute([$_SESSION['admin_id']]);
            $admin_data = $admin_check->fetch(PDO::FETCH_ASSOC);
            
            if (!$admin_data || $admin_data['status'] !== 'active') {
                // Admin account is inactive or doesn't exist
                session_unset();
                session_destroy();
                
                logSecurityEvent('ADMIN_ACCESS_DENIED_INACTIVE', ['admin_id' => $_SESSION['admin_id'] ?? 'unknown']);
                header('Location: ' . admin_url('login') . '?account_inactive=1');
                exit();
            }
            
            // Update last activity in database (if column exists)
            try {
                $update_stmt = $pdo->prepare("UPDATE admins SET last_activity = NOW() WHERE id = ?");
                $update_stmt->execute([$_SESSION['admin_id']]);
            } catch (PDOException $e) {
                // If last_activity column doesn't exist, just log it and continue
                if (strpos($e->getMessage(), 'last_activity') !== false) {
                    error_log("last_activity column not found - run migration: " . $e->getMessage());
                } else {
                    throw $e; // Re-throw if it's a different error
                }
            }
            
        } catch (PDOException $e) {
            // If database error, log it and deny access for security
            logSecurityEvent('ADMIN_AUTH_DATABASE_ERROR', ['error' => $e->getMessage(), 'admin_id' => $_SESSION['admin_id'] ?? 'unknown']);
            session_unset();
            session_destroy();
            header('Location: ' . admin_url('login') . '?error=1');
            exit();
        }
    }
    
    // Update last activity time
    $_SESSION['last_activity'] = time();
    
    // Optional: Regenerate session ID periodically for security
    if (!isset($_SESSION['created'])) {
        $_SESSION['created'] = time();
    } else if (time() - $_SESSION['created'] > 1800) { // 30 minutes
        session_regenerate_id(true);
        $_SESSION['created'] = time();
    }
}


