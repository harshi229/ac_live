<?php
/**
 * Database Configuration
 * Centralized database connection for the application
 */

// Prevent direct access
if (!defined('AC_APP')) {
    die('Direct access not permitted');
}

// Database configuration - Environment-based settings
// Use local credentials for development, production credentials for live site
if (APP_ENV === 'development') {
    // Local development database configuration (XAMPP defaults)
    $db_config = [
        'host' => 'localhost',
        'dbname' => 'air_conditioning_system_new',
        'username' => 'root',
        'password' => '',
        'charset' => 'utf8mb4',
        'options' => [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
        ]
    ];
} else {
    // Production database configuration
    $db_config = [
        'host' => 'localhost',
        'dbname' => 'u835533519_akashaircondb',
        'username' => 'u835533519_akashaircorndb',
        'password' => '9R@#i|lf2Mk',
        'charset' => 'utf8mb4',
        'options' => [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
        ]
    ];
}

try {
    $dsn = "mysql:host={$db_config['host']};dbname={$db_config['dbname']};charset={$db_config['charset']}";
    $pdo = new PDO($dsn, $db_config['username'], $db_config['password'], $db_config['options']);
} catch (PDOException $e) {
    // Log error in production
    if (APP_ENV === 'production') {
        error_log("Database connection failed: " . $e->getMessage());
        die("Database connection failed. Please try again later.");
    } else {
        die("Database connection failed: " . $e->getMessage());
    }
}

/**
 * Get database connection
 * 
 * @return PDO Database connection object
 */
function getDB() {
    global $pdo;
    return $pdo;
}

/**
 * Execute a prepared statement
 * 
 * @param string $sql SQL query
 * @param array $params Parameters for the query
 * @return PDOStatement|false
 */
function executeQuery($sql, $params = []) {
    global $pdo;
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    } catch (PDOException $e) {
        if (APP_ENV === 'development') {
            throw $e;
        } else {
            error_log("Query execution failed: " . $e->getMessage());
            return false;
        }
    }
}

/**
 * Fetch single row
 * 
 * @param string $sql SQL query
 * @param array $params Parameters for the query
 * @return array|false
 */
function fetchRow($sql, $params = []) {
    $stmt = executeQuery($sql, $params);
    return $stmt ? $stmt->fetch() : false;
}

/**
 * Fetch all rows
 * 
 * @param string $sql SQL query
 * @param array $params Parameters for the query
 * @return array|false
 */
function fetchAll($sql, $params = []) {
    $stmt = executeQuery($sql, $params);
    return $stmt ? $stmt->fetchAll() : false;
}

/**
 * Get last insert ID
 * 
 * @return string
 */
function getLastInsertId() {
    global $pdo;
    return $pdo->lastInsertId();
}

/**
 * Begin transaction
 */
function beginTransaction() {
    global $pdo;
    return $pdo->beginTransaction();
}

/**
 * Commit transaction
 */
function commitTransaction() {
    global $pdo;
    return $pdo->commit();
}

/**
 * Rollback transaction
 */
function rollbackTransaction() {
    global $pdo;
    return $pdo->rollBack();
}
