<?php
require_once __DIR__ . '/../../includes/config/init.php';

// Ensure only admins can access this functionality
if (!isset($_SESSION['admin_id'])) {
    echo "Unauthorized access.";
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $order_id = $_POST['order_id'];
    $new_status = $_POST['order_status'];
    $send_email = isset($_POST['send_email']) ? true : false;

    try {
        // Get current order info before updating
        $currentOrderStmt = $pdo->prepare("SELECT user_id, order_status FROM orders WHERE id = ?");
        $currentOrderStmt->execute([$order_id]);
        $currentOrder = $currentOrderStmt->fetch(PDO::FETCH_ASSOC);
        
        // Update the order status in the database
        $update_status_query = $pdo->prepare("
            UPDATE orders SET order_status = ? WHERE id = ?
        ");
        $update_status_query->execute([$new_status, $order_id]);

        // Send status update email ONLY if explicitly requested
        if ($send_email) {
            require_once 'include/email_helpers.php';
            $emailSent = sendOrderStatusUpdateEmail($currentOrder['user_id'], $order_id, $new_status);
            
            if ($emailSent) {
                echo "Order status updated successfully! Customer has been notified via email.";
            } else {
                echo "Order status updated successfully! (Email notification failed)";
            }
        } else {
            echo "Order status updated successfully!";
        }
        
    } catch (Exception $e) {
        echo "Error updating order: " . $e->getMessage();
    }
    
    header('Location: order_management.php'); // Redirect back to order management page
    exit();
}

