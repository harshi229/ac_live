<?php
/**
 * Email Helper Functions for Akash Enterprise AC System
 * Simple functions to send various types of emails
 */

require_once __DIR__ . '/../config/email_config.php';

/**
 * Send Order Confirmation Email
 */
function sendOrderConfirmationEmail($userId, $orderId) {
    global $pdo;
    
    try {
        // Get user information
        $userStmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $userStmt->execute([$userId]);
        $user = $userStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            error_log("User not found for order confirmation email: User ID " . $userId);
            return false;
        }
        
        // Get order information with items
        $orderStmt = $pdo->prepare("
            SELECT o.*, u.username, u.email, u.phone_number
            FROM orders o
            LEFT JOIN users u ON o.user_id = u.id
            WHERE o.id = ?
        ");
        $orderStmt->execute([$orderId]);
        $order = $orderStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$order) {
            error_log("Order not found for confirmation email: Order ID " . $orderId);
            return false;
        }
        
        // Get order items with product details
        $itemsStmt = $pdo->prepare("
            SELECT oi.*, p.*, b.name as brand_name, cat.name as category_name
            FROM order_items oi
            JOIN products p ON oi.product_id = p.id
            LEFT JOIN brands b ON p.brand_id = b.id
            LEFT JOIN categories cat ON p.category_id = cat.id
            WHERE oi.order_id = ?
            ORDER BY oi.id
        ");
        $itemsStmt->execute([$orderId]);
        $items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Prepare order data for email
        $orderData = [
            'order_number' => $order['order_number'],
            'created_at' => $order['created_at'],
            'payment_method' => $order['payment_method'],
            'order_status' => $order['order_status'],
            'payment_status' => $order['payment_status'],
            'delivery_date' => $order['delivery_date'],
            'total_price' => $order['total_price'],
            'shipping_cost' => 0, // Add shipping cost if needed
            'address' => $order['address'],
            'installation_required' => $order['installation_required'],
            'items' => $items
        ];
        
        // Send email
        $emailManager = new EmailManager();
        $result = $emailManager->sendOrderConfirmation($user['email'], $user['username'], $orderData);
        
        if ($result) {
            // Log email sent in database
            $logStmt = $pdo->prepare("
                INSERT INTO email_logs (user_id, order_id, email_type, recipient_email, status, sent_at)
                VALUES (?, ?, 'order_confirmation', ?, 'sent', NOW())
            ");
            $logStmt->execute([$userId, $orderId, $user['email']]);
            
            return true;
        } else {
            // Log email failed
            $logStmt = $pdo->prepare("
                INSERT INTO email_logs (user_id, order_id, email_type, recipient_email, status, sent_at)
                VALUES (?, ?, 'order_confirmation', ?, 'failed', NOW())
            ");
            $logStmt->execute([$userId, $orderId, $user['email']]);
            
            return false;
        }
        
    } catch (Exception $e) {
        error_log("Error sending order confirmation email: " . $e->getMessage());
        return false;
    }
}

/**
 * Send Order Status Update Email
 */
function sendOrderStatusUpdateEmail($userId, $orderId, $newStatus) {
    global $pdo;
    
    try {
        // Get user information
        $userStmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $userStmt->execute([$userId]);
        $user = $userStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            error_log("User not found for status update email: User ID " . $userId);
            return false;
        }
        
        // Get order information
        $orderStmt = $pdo->prepare("
            SELECT o.*, u.username, u.email
            FROM orders o
            LEFT JOIN users u ON o.user_id = u.id
            WHERE o.id = ?
        ");
        $orderStmt->execute([$orderId]);
        $order = $orderStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$order) {
            error_log("Order not found for status update email: Order ID " . $orderId);
            return false;
        }
        
        // Update order status in email data
        $order['order_status'] = $newStatus;
        
        // Send email
        $emailManager = new EmailManager();
        $result = $emailManager->sendOrderStatusUpdate($user['email'], $user['username'], $order);
        
        if ($result) {
            // Log email sent in database
            $logStmt = $pdo->prepare("
                INSERT INTO email_logs (user_id, order_id, email_type, recipient_email, status, sent_at)
                VALUES (?, ?, 'status_update', ?, 'sent', NOW())
            ");
            $logStmt->execute([$userId, $orderId, $user['email']]);
            
            return true;
        } else {
            // Log email failed
            $logStmt = $pdo->prepare("
                INSERT INTO email_logs (user_id, order_id, email_type, recipient_email, status, sent_at)
                VALUES (?, ?, 'status_update', ?, 'failed', NOW())
            ");
            $logStmt->execute([$userId, $orderId, $user['email']]);
            
            return false;
        }
        
    } catch (Exception $e) {
        error_log("Error sending status update email: " . $e->getMessage());
        return false;
    }
}

/**
 * Send Welcome Email for New Users
 */
function sendWelcomeEmail($userId) {
    global $pdo;
    
    try {
        // Get user information
        $userStmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $userStmt->execute([$userId]);
        $user = $userStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            error_log("User not found for welcome email: User ID " . $userId);
            return false;
        }
        
        // Send email
        $emailManager = new EmailManager();
        $result = $emailManager->sendWelcomeEmail($user['email'], $user['username']);
        
        if ($result) {
            // Log email sent in database
            $logStmt = $pdo->prepare("
                INSERT INTO email_logs (user_id, order_id, email_type, recipient_email, status, sent_at)
                VALUES (?, NULL, 'welcome', ?, 'sent', NOW())
            ");
            $logStmt->execute([$userId, $user['email']]);
            
            return true;
        } else {
            // Log email failed
            $logStmt = $pdo->prepare("
                INSERT INTO email_logs (user_id, order_id, email_type, recipient_email, status, sent_at)
                VALUES (?, NULL, 'welcome', ?, 'failed', NOW())
            ");
            $logStmt->execute([$userId, $user['email']]);
            
            return false;
        }
        
    } catch (Exception $e) {
        error_log("Error sending welcome email: " . $e->getMessage());
        return false;
    }
}

/**
 * Simple Email Function - Generic email sender
 */
function sendEmail($to, $subject, $message, $isHTML = true) {
    try {
        $emailManager = new EmailManager();
        return $emailManager->sendGenericEmail($to, $subject, $message, $isHTML);
    } catch (Exception $e) {
        error_log("Email sending error: " . $e->getMessage());
        return false;
    }
}

/**
 * Test Email Functionality
 */
function testEmailSystem() {
    try {
        $emailManager = new EmailManager();
        
        // Test with sample data
        $testOrderData = [
            'order_number' => 'TEST123',
            'created_at' => date('Y-m-d H:i:s'),
            'payment_method' => 'Cash on Delivery',
            'order_status' => 'Pending',
            'payment_status' => 'Pending',
            'delivery_date' => date('Y-m-d', strtotime('+7 days')),
            'total_price' => 25000.00,
            'shipping_cost' => 40.00,
            'address' => 'Test Address, Test City, 123456',
            'installation_required' => true,
            'items' => [
                [
                    'product_name' => 'Test AC Unit',
                    'brand_name' => 'Test Brand',
                    'model_name' => 'Test Model',
                    'model_number' => 'TM001',
                    'quantity' => 1,
                    'total_price' => 25000.00,
                    'product_image' => 'default-product.jpg'
                ]
            ]
        ];
        
        $result = $emailManager->sendOrderConfirmation('test@example.com', 'Test User', $testOrderData);
        
        if ($result) {
            return "Email system test successful!";
        } else {
            return "Email system test failed!";
        }
        
    } catch (Exception $e) {
        return "Email system test error: " . $e->getMessage();
    }
}
