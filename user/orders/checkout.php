<?php
// Set page metadata
$pageTitle = 'Checkout';
$pageDescription = 'Complete your air conditioning purchase with secure checkout';
$pageKeywords = 'checkout, purchase, payment, order, AC purchase';

require_once __DIR__ . '/../../includes/config/init.php';
include INCLUDES_PATH . '/templates/header.php';

if (!isset($_SESSION['user_id'])) {
    // Store current page URL for redirect after login
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    echo "<script>window.location.href='../auth/login.php';</script>";
    exit();
}

$user_id = $_SESSION['user_id'];

// Get shipping option from cart or default to standard
$selected_shipping = isset($_GET['shipping']) ? $_GET['shipping'] : 'standard';
$shipping_cost = 0;
$shipping_label = '';

switch ($selected_shipping) {
    case 'express':
        $shipping_cost = 200;
        $shipping_label = 'Express (1 Day)';
        break;
    case 'fast':
        $shipping_cost = 100;
        $shipping_label = 'Fast (2-3 Days)';
        break;
    case 'standard':
    default:
        $shipping_cost = 40;
        $shipping_label = 'Standard (4-7 Days)';
        break;
}

// Fetch detailed cart items with all product information
$cart_items_query = $pdo->prepare("
    SELECT c.*, 
           p.*,
           b.name as brand_name,
           cat.name as category_name,
           sc.name as subcategory_name,
           (p.price * c.quantity) AS subtotal
    FROM cart c
    JOIN products p ON c.product_id = p.id
    LEFT JOIN brands b ON p.brand_id = b.id
    LEFT JOIN categories cat ON p.category_id = cat.id
    LEFT JOIN sub_categories sc ON p.sub_category_id = sc.id
    WHERE c.user_id = ? AND p.status = 'active'
    ORDER BY c.added_at DESC
");
$cart_items_query->execute([$user_id]);
$cart_items = $cart_items_query->fetchAll(PDO::FETCH_ASSOC);

// Check if cart is empty
if (empty($cart_items)) {
    echo "<script>window.location.href='cart.php?empty=1';</script>";
    exit();
}

// Get user information
$user_query = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$user_query->execute([$user_id]);
$user = $user_query->fetch(PDO::FETCH_ASSOC);

// Get user's saved addresses (with error handling)
$saved_addresses = [];
$default_address = null;

try {
    $addresses_query = $pdo->prepare("
        SELECT * FROM user_addresses 
        WHERE user_id = ? 
        ORDER BY is_default DESC, created_at DESC
    ");
    $addresses_query->execute([$user_id]);
    $saved_addresses = $addresses_query->fetchAll(PDO::FETCH_ASSOC);

    // Get default address
    foreach ($saved_addresses as $addr) {
        if ($addr['is_default']) {
            $default_address = $addr;
            break;
        }
    }
    if (!$default_address && !empty($saved_addresses)) {
        $default_address = $saved_addresses[0];
    }
} catch (Exception $e) {
    // If user_addresses table doesn't exist yet, continue with empty addresses
    $saved_addresses = [];
    $default_address = null;
}

// Calculate totals
$total_items = 0;
$subtotal = 0;
foreach ($cart_items as $item) {
    $total_items += $item['quantity'];
    $subtotal += $item['subtotal'];
}

$tax = $subtotal * 0.28; // 28% GST
$grand_total = $subtotal + $shipping_cost + $tax;

// Handle coupon application
$applied_coupon = null;
$discount_amount = 0;
if ($_POST && isset($_POST['coupon_code'])) {
    $coupon_code = $_POST['coupon_code'];
    $coupon_query = $pdo->prepare("SELECT * FROM offers WHERE code = ? AND status = 'active' AND (expiry_date IS NULL OR expiry_date > NOW())");
    $coupon_query->execute([$coupon_code]);
    $applied_coupon = $coupon_query->fetch(PDO::FETCH_ASSOC);
    
    if ($applied_coupon) {
        if ($applied_coupon['discount_type'] === 'percentage') {
            $discount_amount = ($subtotal * $applied_coupon['discount_value']) / 100;
        } else {
            $discount_amount = $applied_coupon['discount_value'];
        }
        $tax = ($subtotal - $discount_amount) * 0.28;
        $grand_total = $subtotal - $discount_amount + $shipping_cost + $tax;
    }
}

// Initialize variables
$success_message = "";
$payment_success = false;

// Handle form submission for placing order
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    // Debug: Log form submission
    error_log("Checkout form submitted successfully");
    // Get address information
    $delivery_name = trim($_POST['delivery_name'] ?? '');
    $delivery_mobile = trim($_POST['delivery_mobile'] ?? '');
    $delivery_address_line_1 = trim($_POST['delivery_address_line_1'] ?? '');
    $delivery_address_line_2 = trim($_POST['delivery_address_line_2'] ?? '');
    $delivery_landmark = trim($_POST['delivery_landmark'] ?? '');
    $delivery_city = trim($_POST['delivery_city'] ?? '');
    $delivery_state = trim($_POST['delivery_state'] ?? '');
    $delivery_pincode = trim($_POST['delivery_pincode'] ?? '');
    $delivery_country = trim($_POST['delivery_country'] ?? 'India');
    $address_type = $_POST['address_type'] ?? 'Home';
    $save_address = isset($_POST['save_address']) ? 1 : 0;
    $use_saved_address = $_POST['use_saved_address'] ?? '';
    
    $payment_method = $_POST['payment_method'] ?? '';
    $notes = trim($_POST['notes'] ?? '');
    
    // Get payment details based on selected payment method - Commented out for non-COD methods
    $payment_details = null;
    
    /* Commented out Credit/Debit Card payment processing
    if ($payment_method === 'Credit/Debit Card') {
        $card_number = filter_input(INPUT_POST, 'card_number', FILTER_SANITIZE_STRING);
        $card_holder = trim(filter_input(INPUT_POST, 'card_holder', FILTER_SANITIZE_STRING));
        $expiry_date = filter_input(INPUT_POST, 'expiry_date', FILTER_SANITIZE_STRING);
        $cvv = filter_input(INPUT_POST, 'cvv', FILTER_SANITIZE_STRING);
        $billing_address = trim(filter_input(INPUT_POST, 'billing_address', FILTER_SANITIZE_STRING));
        $billing_city = trim(filter_input(INPUT_POST, 'billing_city', FILTER_SANITIZE_STRING));
        $billing_pincode = filter_input(INPUT_POST, 'billing_pincode', FILTER_SANITIZE_STRING);
        
        // Validate card details
        if (empty($card_number) || empty($card_holder) || empty($expiry_date) || empty($cvv) || 
            empty($billing_address) || empty($billing_city) || empty($billing_pincode)) {
            $error_message = "Please fill in all card payment details.";
        } else {
            // Mask card number for security (show only last 4 digits)
            $masked_card_number = '**** **** **** ' . substr(str_replace(' ', '', $card_number), -4);
            
            $payment_details = [
                'type' => 'card',
                'card_number' => $masked_card_number,
                'card_holder' => $card_holder,
                'expiry_date' => $expiry_date,
                'billing_address' => $billing_address,
                'billing_city' => $billing_city,
                'billing_pincode' => $billing_pincode
            ];
        }
    } elseif ($payment_method === 'UPI') {
        $upi_id = trim(filter_input(INPUT_POST, 'upi_id', FILTER_SANITIZE_STRING));
        $upi_app = filter_input(INPUT_POST, 'upi_app', FILTER_SANITIZE_STRING);
        $upi_mobile = filter_input(INPUT_POST, 'upi_mobile', FILTER_SANITIZE_STRING);
        
        // Validate UPI details
        if (empty($upi_id) || empty($upi_app) || empty($upi_mobile)) {
            $error_message = "Please fill in all UPI payment details.";
        } else {
            $payment_details = [
                'type' => 'upi',
                'upi_id' => $upi_id,
                'upi_app' => $upi_app,
                'upi_mobile' => $upi_mobile
            ];
        }
    } elseif ($payment_method === 'Net Banking') {
        $bank_name = filter_input(INPUT_POST, 'bank_name', FILTER_SANITIZE_STRING);
        $account_holder = trim(filter_input(INPUT_POST, 'account_holder', FILTER_SANITIZE_STRING));
        $account_number = filter_input(INPUT_POST, 'account_number', FILTER_SANITIZE_STRING);
        $ifsc_code = trim(filter_input(INPUT_POST, 'ifsc_code', FILTER_SANITIZE_STRING));
        
        // Validate Net Banking details
        if (empty($bank_name) || empty($account_holder) || empty($account_number) || empty($ifsc_code)) {
            $error_message = "Please fill in all Net Banking payment details.";
        } else {
            $payment_details = [
                'type' => 'netbanking',
                'bank_name' => $bank_name,
                'account_holder' => $account_holder,
                'account_number' => '****' . $account_number, // Mask account number
                'ifsc_code' => $ifsc_code
            ];
        }
    }
    */
    
    // Get AMC selections for each product
    $amc_selections = array();
    foreach ($cart_items as $item) {
        $amc_selections[$item['product_id']] = isset($_POST['amc_' . $item['product_id']]) ? 1 : 0;
    }
    
    // Handle saved address selection
    if ($use_saved_address && $use_saved_address !== 'new') {
        try {
            $saved_addr_query = $pdo->prepare("SELECT * FROM user_addresses WHERE id = ? AND user_id = ?");
            $saved_addr_query->execute([$use_saved_address, $user_id]);
            $selected_address = $saved_addr_query->fetch(PDO::FETCH_ASSOC);
            
            if ($selected_address) {
                $delivery_name = $selected_address['full_name'];
                $delivery_mobile = $selected_address['mobile_number'];
                $delivery_address_line_1 = $selected_address['address_line_1'];
                $delivery_address_line_2 = $selected_address['address_line_2'];
                $delivery_landmark = $selected_address['landmark'];
                $delivery_city = $selected_address['city'];
                $delivery_state = $selected_address['state'];
                $delivery_pincode = $selected_address['pincode'];
                $delivery_country = $selected_address['country'];
                $address_type = $selected_address['address_type'];
            }
        } catch (Exception $e) {
            // If user_addresses table doesn't exist, continue with form data
        }
    }
    
    // Validate required address fields
    if (empty($delivery_name)) {
        $error_message = "Please enter the delivery name.";
    } elseif (empty($delivery_mobile) || strlen($delivery_mobile) < 10) {
        $error_message = "Please enter a valid mobile number (10 digits).";
    } elseif (empty($delivery_address_line_1) || strlen($delivery_address_line_1) < 5) {
        $error_message = "Please enter a complete address (at least 5 characters).";
    } elseif (empty($delivery_city)) {
        $error_message = "Please enter the city.";
    } elseif (empty($delivery_state)) {
        $error_message = "Please enter the state.";
    } elseif (empty($delivery_pincode) || strlen($delivery_pincode) !== 6) {
        $error_message = "Please enter a valid 6-digit pincode.";
    } elseif (empty($payment_method)) {
        $error_message = "Please select a payment method.";
    } elseif (in_array($payment_method, ['Credit/Debit Card', 'UPI', 'Net Banking']) && $payment_details === null) {
        // Commented out validation for non-COD payment methods
        // $error_message = "Please fill in all payment details for the selected method.";
    } else {
        try {
            // Debug: Log order processing start
            error_log("Starting order processing for user: " . $user_id);
            // Begin transaction
            $pdo->beginTransaction();

            // Calculate delivery date based on shipping method
            $delivery_days = 7; // Default for standard
            if ($selected_shipping === 'express') {
                $delivery_days = 1;
            } elseif ($selected_shipping === 'fast') {
                $delivery_days = 3;
            }
            $delivery_date = date('Y-m-d', strtotime("+$delivery_days days"));
            
            // Generate unique order number
            $order_number = 'ORD' . date('Ymd') . rand(1000, 9999);

            // Determine payment status based on method - Only COD for now
            $payment_status = 'Pending';
            if ($payment_method === 'Cash on Delivery') {
                $payment_status = 'COD';
            }
            /* Commented out online payment processing
            elseif (in_array($payment_method, ['Credit/Debit Card', 'UPI', 'Net Banking'])) {
                // For online payments, we'll simulate processing
                // In a real application, you would integrate with payment gateways
                $payment_status = 'Paid'; // Simulate successful payment
            }
            */

            // Prepare order notes with payment details - Only COD for now
            $order_notes = $notes;
            /* Commented out payment details for non-COD methods
            if ($payment_details) {
                $payment_info = "\n\nPayment Details:\n";
                
                if ($payment_details['type'] === 'card') {
                    $payment_info .= "Payment Method: Credit/Debit Card\n";
                    $payment_info .= "Card: " . $payment_details['card_number'] . "\n";
                    $payment_info .= "Holder: " . $payment_details['card_holder'] . "\n";
                    $payment_info .= "Expiry: " . $payment_details['expiry_date'] . "\n";
                    $payment_info .= "Billing Address: " . $payment_details['billing_address'] . "\n";
                    $payment_info .= "City: " . $payment_details['billing_city'] . "\n";
                    $payment_info .= "Pincode: " . $payment_details['billing_pincode'];
                } elseif ($payment_details['type'] === 'upi') {
                    $payment_info .= "Payment Method: UPI\n";
                    $payment_info .= "UPI ID: " . $payment_details['upi_id'] . "\n";
                    $payment_info .= "UPI App: " . $payment_details['upi_app'] . "\n";
                    $payment_info .= "Mobile: " . $payment_details['upi_mobile'];
                } elseif ($payment_details['type'] === 'netbanking') {
                    $payment_info .= "Payment Method: Net Banking\n";
                    $payment_info .= "Bank: " . $payment_details['bank_name'] . "\n";
                    $payment_info .= "Account Holder: " . $payment_details['account_holder'] . "\n";
                    $payment_info .= "Account Number: " . $payment_details['account_number'] . "\n";
                    $payment_info .= "IFSC Code: " . $payment_details['ifsc_code'];
                }
                
                $order_notes .= $payment_info;
            }
            */

            // Prepare payment details as JSON - Only COD for now
            $payment_details_json = null;
            /* Commented out payment details JSON for non-COD methods
            if ($payment_details) {
                $payment_details_json = json_encode($payment_details);
            }
            */

            // Create full address string
            $full_address = $delivery_address_line_1;
            if (!empty($delivery_address_line_2)) {
                $full_address .= ', ' . $delivery_address_line_2;
            }
            if (!empty($delivery_landmark)) {
                $full_address .= ', ' . $delivery_landmark;
            }
            $full_address .= ', ' . $delivery_city . ', ' . $delivery_state . ' - ' . $delivery_pincode . ', ' . $delivery_country;

            // Insert order (simplified for existing database structure)
            $order_stmt = $pdo->prepare("
                INSERT INTO orders (
                    user_id, order_number, total_price, payment_method, 
                    order_status, payment_status, delivery_date, address, 
                    notes, payment_details, created_at
                ) VALUES (?, ?, ?, ?, 'Pending', ?, ?, ?, ?, ?, NOW())
            ");
            $order_stmt->execute([
                $user_id, 
                $order_number, 
                $grand_total, 
                $payment_method, 
                $payment_status, 
                $delivery_date, 
                $full_address,
                $notes,
                $payment_details_json
            ]);

            $order_id = $pdo->lastInsertId();

            // Insert order items
            foreach ($cart_items as $item) {
                // Insert order item
                $order_item_stmt = $pdo->prepare("
                    INSERT INTO order_items (order_id, product_id, quantity, unit_price, total_price, amc_opted)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $order_item_stmt->execute([
                    $order_id, 
                    $item['product_id'], 
                    $item['quantity'], 
                    $item['price'], 
                    $item['subtotal'],
                    $amc_selections[$item['product_id']]
                ]);

            }

            // Clear cart
            $clear_cart_stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ?");
            $clear_cart_stmt->execute([$user_id]);

            // Commit transaction
            $pdo->commit();

            // Send order confirmation email
            require_once '../../includes/functions/email_helpers.php';
            $emailSent = sendOrderConfirmationEmail($user_id, $order_id);
            
            // Set success message based on payment method and email status
            if ($payment_method === 'Cash on Delivery') {
                if ($emailSent) {
                    $success_message = "Order placed successfully! You will pay ₹" . number_format($grand_total, 2) . " when your order is delivered. A confirmation email has been sent to your registered email address.";
                } else {
                    $success_message = "Order placed successfully! You will pay ₹" . number_format($grand_total, 2) . " when your order is delivered. Note: Email confirmation could not be sent.";
                }
                $payment_success = true;
            } elseif (in_array($payment_method, ['Credit/Debit Card', 'UPI', 'Net Banking'])) {
                // Commented out online payment success messages
                /*
                $payment_type = '';
                if ($payment_method === 'Credit/Debit Card') {
                    $payment_type = 'card payment';
                } elseif ($payment_method === 'UPI') {
                    $payment_type = 'UPI payment';
                } elseif ($payment_method === 'Net Banking') {
                    $payment_type = 'net banking payment';
                }
                
                if ($emailSent) {
                    $success_message = "Payment processed successfully! Your " . $payment_type . " of ₹" . number_format($grand_total, 2) . " has been charged. Order Number: " . $order_number . ". A confirmation email has been sent to your registered email address.";
                } else {
                    $success_message = "Payment processed successfully! Your " . $payment_type . " of ₹" . number_format($grand_total, 2) . " has been charged. Order Number: " . $order_number . ". Note: Email confirmation could not be sent.";
                }
                */
                $payment_success = true;
            } else {
                if ($emailSent) {
                    $success_message = "Payment successful! Your order has been placed successfully. A confirmation email has been sent to your registered email address.";
                } else {
                    $success_message = "Payment successful! Your order has been placed successfully. Note: Email confirmation could not be sent.";
                }
                $payment_success = true;
            }
            
            // Store order ID for redirect
            $_SESSION['last_order_id'] = $order_id;

        } catch (Exception $e) {
            // Rollback transaction on error
            $pdo->rollBack();
            $error_message = "Error processing order: " . $e->getMessage();
        }
    }
}
?>

<style>
/* E-commerce Checkout Page */


/* Main Checkout Container */
.checkout-container {
    background: #fff;
    padding: 30px 0;
}

.checkout-layout {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 40px;
    margin-bottom: 40px;
}

/* Checkout Steps */
.checkout-steps {
    display: flex;
    justify-content: center;
    margin-bottom: 40px;
    padding: 20px 0;
    background: #f8f9fa;
    border-radius: 8px;
}

.step {
    display: flex;
    align-items: center;
    padding: 10px 20px;
    margin: 0 10px;
    border-radius: 20px;
    font-weight: 500;
    transition: all 0.3s;
}

.step.active {
    background: #007bff;
    color: white;
}

.step.completed {
    background: #28a745;
    color: white;
}

.step-number {
    width: 30px;
    height: 30px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.2);
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 10px;
    font-weight: 600;
}

/* Section Cards */
.checkout-section {
    background: #fff;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    margin-bottom: 30px;
    overflow: hidden;
}

.section-header {
    background: #f8f9fa;
    padding: 20px;
    border-bottom: 1px solid #e9ecef;
}

.section-title {
    font-size: 20px;
    font-weight: 600;
    color: #495057;
    margin: 0;
}

.section-content {
    padding: 30px;
}

/* Customer Information Section */
.customer-info-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-bottom: 20px;
}

.info-field {
    margin-bottom: 20px;
}

.info-label {
    font-weight: 600;
    color: #495057;
    margin-bottom: 8px;
    display: block;
}

.info-value {
    color: #6c757d;
    font-size: 16px;
    padding: 10px 15px;
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 6px;
}

.edit-address-btn {
    background: #007bff;
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 6px;
    cursor: pointer;
    font-weight: 500;
    transition: background-color 0.3s;
}

.edit-address-btn:hover {
    background: #0056b3;
}

/* Order Items Section */
.order-item {
    display: grid;
    grid-template-columns: 80px 1fr auto;
    gap: 20px;
    padding: 20px 0;
    border-bottom: 1px solid #e9ecef;
    align-items: start;
}

.order-item:last-child {
    border-bottom: none;
}

.item-image {
    width: 80px;
    height: 80px;
    object-fit: contain;
    border: 1px solid #e9ecef;
    border-radius: 6px;
    background: #f8f9fa;
}

.item-info {
    flex: 1;
}

.item-name {
    font-size: 16px;
    font-weight: 600;
    color: #495057;
    margin-bottom: 5px;
}

.item-model {
    color: #6c757d;
    font-size: 14px;
    margin-bottom: 5px;
}

.item-brand {
    color: #007bff;
    font-size: 14px;
    font-weight: 500;
    margin-bottom: 10px;
}

.item-specs {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-bottom: 10px;
}

.spec-badge {
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 4px;
    padding: 2px 6px;
    font-size: 12px;
    color: #495057;
}

.amc-option {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-top: 10px;
}

.amc-checkbox {
    transform: scale(1.2);
}

.amc-label {
    font-size: 14px;
    color: #495057;
    font-weight: 500;
}

.item-price-info {
    text-align: right;
}

.price-per-unit {
    color: #6c757d;
    font-size: 14px;
    margin-bottom: 5px;
}

.item-subtotal {
    font-size: 18px;
    font-weight: 700;
    color: #28a745;
}

/* Checkout Discount Pricing Styles */
.price-per-unit {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
    margin-bottom: 5px;
}

.current-price {
    font-size: 16px;
    font-weight: 700;
    color: #28a745;
}

.original-price {
    font-size: 14px;
    font-weight: 500;
    color: #6c757d;
    text-decoration: line-through;
}

.discount-badge {
    background: linear-gradient(135deg, #dc3545, #c82333);
    color: white;
    padding: 3px 8px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.3px;
}

/* Payment Section */
.payment-methods {
    display: grid;
    gap: 15px;
    margin-bottom: 25px;
}

.payment-option {
    position: relative;
}

.payment-option input[type="radio"] {
    position: absolute;
    opacity: 0;
    cursor: pointer;
}

.payment-label {
    display: flex;
    align-items: center;
    padding: 15px 20px;
    border: 2px solid #e9ecef;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s;
    background: #fff;
}

.payment-option input[type="radio"]:checked + .payment-label {
    border-color: #007bff;
    background: #e7f3ff;
}

.payment-icon {
    margin-right: 15px;
    font-size: 20px;
    color: #007bff;
}

.payment-info {
    flex: 1;
}

.payment-name {
    font-weight: 600;
    color: #495057;
    margin-bottom: 2px;
}

.payment-description {
    font-size: 14px;
    color: #6c757d;
}

.payment-status {
    font-size: 12px;
    font-weight: 500;
    padding: 4px 8px;
    border-radius: 12px;
    background: #fff3cd;
    color: #856404;
}

/* Order Summary */
.order-summary {
    background: #fff;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    padding: 25px;
    position: sticky;
    top: 20px;
    height: fit-content;
}

.summary-title {
    font-size: 20px;
    font-weight: 600;
    margin-bottom: 20px;
    color: #495057;
}

.summary-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 12px;
    padding: 8px 0;
}

.summary-row.total {
    border-top: 2px solid #e9ecef;
    margin-top: 15px;
    padding-top: 15px;
    font-weight: 700;
    font-size: 18px;
    color: #495057;
}

.summary-label {
    color: #6c757d;
}

.summary-value {
    font-weight: 600;
    color: #495057;
}

.summary-value.discount {
    color: #28a745;
}

.summary-value.total {
    color: #007bff;
    font-size: 18px;
}

/* Delivery Info */
.delivery-info {
    background: #e7f3ff;
    border: 1px solid #b3d9ff;
    border-radius: 6px;
    padding: 15px;
    margin: 20px 0;
}

.delivery-info h5 {
    color: #004085;
    margin-bottom: 8px;
    font-weight: 600;
}

.delivery-info p {
    color: #004085;
    font-size: 14px;
    margin: 0;
}

/* Notes Section */
.notes-section {
    margin: 20px 0;
}

.notes-label {
    font-weight: 600;
    color: #495057;
    margin-bottom: 8px;
    display: block;
}

.notes-textarea {
    width: 100%;
    padding: 12px 15px;
    border: 1px solid #ced4da;
    border-radius: 6px;
    font-size: 14px;
    resize: vertical;
    min-height: 80px;
}

.notes-textarea:focus {
    outline: none;
    border-color: #007bff;
    box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.25);
}

/* Installation Option */
.installation-option {
    display: flex;
    align-items: center;
    gap: 10px;
    margin: 20px 0;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 6px;
    border: 1px solid #e9ecef;
}

.installation-checkbox {
    transform: scale(1.2);
}

.installation-label {
    font-weight: 500;
    color: #495057;
}

/* Action Buttons */
.checkout-actions {
    margin-top: 30px;
    display: flex;
    gap: 15px;
}

.btn-place-order {
    background: #28a745;
    color: white;
    border: none;
    padding: 15px 30px;
    border-radius: 6px;
    font-weight: 600;
    font-size: 16px;
    cursor: pointer;
    transition: background-color 0.3s;
    flex: 1;
}

.btn-place-order:hover {
    background: #1e7e34;
}

.btn-back-cart {
    background: #6c757d;
    color: white;
    border: none;
    padding: 15px 30px;
    border-radius: 6px;
    font-weight: 600;
    cursor: pointer;
    transition: background-color 0.3s;
    text-decoration: none;
    text-align: center;
}

.btn-back-cart:hover {
    background: #545b62;
    color: white;
    text-decoration: none;
}

/* Success/Error Messages */
.alert {
    padding: 15px 20px;
    border-radius: 6px;
    margin-bottom: 20px;
    border: none;
    font-weight: 500;
}

.alert-success {
    background: #d4edda;
    color: #155724;
    border-left: 4px solid #28a745;
}

.alert-danger {
    background: #f8d7da;
    color: #721c24;
    border-left: 4px solid #dc3545;
}

/* Security Badge */
.security-badge {
    text-align: center;
    margin-top: 25px;
    padding: 15px;
    background: #d4edda;
    border-radius: 6px;
    border: 1px solid #c3e6cb;
}

.security-badge i {
    color: #28a745;
    font-size: 20px;
    margin-bottom: 8px;
}

.security-badge p {
    color: #155724;
    font-weight: 500;
    margin: 0;
    font-size: 14px;
}

/* Success Popup Modal Styles */
.success-modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.7);
    backdrop-filter: blur(5px);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 9999;
    animation: fadeInOverlay 0.3s ease-out;
}

.success-modal {
    background: white;
    border-radius: 20px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    max-width: 500px;
    width: 90%;
    max-height: 90vh;
    overflow-y: auto;
    animation: slideInModal 0.4s ease-out;
    position: relative;
}

@keyframes fadeInOverlay {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes slideInModal {
    from { 
        opacity: 0; 
        transform: translateY(-50px) scale(0.9); 
    }
    to { 
        opacity: 1; 
        transform: translateY(0) scale(1); 
    }
}

.loading-animation {
    text-align: center;
    padding: 40px 30px;
    animation: fadeIn 0.5s ease-in;
}

.spinner {
    width: 60px;
    height: 60px;
    border: 4px solid #f3f3f3;
    border-top: 4px solid #007bff;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin: 0 auto 20px;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

@keyframes checkmark {
    0% { 
        transform: translate(-50%, -50%) scale(0) rotate(45deg); 
        opacity: 0; 
    }
    50% { 
        transform: translate(-50%, -50%) scale(1.2) rotate(45deg); 
        opacity: 1; 
    }
    100% { 
        transform: translate(-50%, -50%) scale(1) rotate(45deg); 
        opacity: 1; 
    }
}

@keyframes circle {
    0% { transform: scale(0); }
    50% { transform: scale(1.1); }
    100% { transform: scale(1); }
}

.success-check {
    text-align: center;
    padding: 40px 30px;
    animation: fadeIn 0.5s ease-in;
}

.checkmark-container {
    margin-bottom: 30px;
}

.checkmark-circle {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    background: linear-gradient(135deg, #28a745, #20c997);
    margin: 0 auto;
    position: relative;
    animation: circle 0.6s ease-in-out;
    box-shadow: 0 10px 30px rgba(40, 167, 69, 0.3);
}

.checkmark {
    position: absolute;
    left: 50%;
    top: 50%;
    width: 20px;
    height: 35px;
    border: solid white;
    border-width: 0 4px 4px 0;
    opacity: 0;
    animation: checkmark 0.6s ease-in-out 0.3s both;
}

.success-check h3 {
    color: #28a745;
    font-size: 32px;
    font-weight: 700;
    margin-bottom: 20px;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.success-message {
    color: #495057;
    font-size: 18px;
    margin-bottom: 25px;
    line-height: 1.5;
}

.order-details {
    background: #f8f9fa;
    border-radius: 12px;
    padding: 20px;
    margin: 25px 0;
    border: 1px solid #e9ecef;
}

.detail-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 12px;
    padding: 8px 0;
}

.detail-row:last-child {
    margin-bottom: 0;
}

.detail-label {
    font-weight: 600;
    color: #495057;
    font-size: 14px;
}

.detail-value {
    font-weight: 700;
    color: #28a745;
    font-size: 16px;
}

.next-steps {
    background: #e7f3ff;
    border: 1px solid #b3d9ff;
    border-radius: 12px;
    padding: 20px;
    margin: 25px 0;
}

.next-steps p {
    color: #004085;
    font-size: 14px;
    margin-bottom: 10px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.next-steps p:last-child {
    margin-bottom: 0;
}

.next-steps i {
    color: #007bff;
    font-size: 16px;
}

.modal-actions {
    display: flex;
    gap: 15px;
    margin: 25px 0;
    flex-wrap: wrap;
}

.btn-continue-shopping, .btn-view-orders {
    flex: 1;
    min-width: 150px;
    padding: 12px 20px;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    font-size: 14px;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.btn-continue-shopping {
    background: linear-gradient(135deg, #007bff, #0056b3);
    color: white;
}

.btn-continue-shopping:hover {
    background: linear-gradient(135deg, #0056b3, #004085);
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0, 123, 255, 0.3);
}

.btn-view-orders {
    background: #f8f9fa;
    color: #495057;
    border: 2px solid #e9ecef;
}

.btn-view-orders:hover {
    background: #e9ecef;
    border-color: #dee2e6;
    transform: translateY(-2px);
}

.redirect-notice {
    color: #6c757d;
    font-size: 14px;
    text-align: center;
    margin-top: 20px;
    font-style: italic;
}

#countdown {
    font-weight: 700;
    color: #007bff;
}

.loading-animation h3 {
    color: #007bff;
    font-size: 24px;
    font-weight: 600;
    margin-bottom: 10px;
}

.loading-animation p {
    color: #6c757d;
    font-size: 16px;
}

/* Responsive Design */
@media (max-width: 768px) {
    .success-modal {
        width: 95%;
        margin: 20px;
    }
    
    .success-check {
        padding: 30px 20px;
    }
    
    .modal-actions {
        flex-direction: column;
    }
    
    .btn-continue-shopping, .btn-view-orders {
        min-width: auto;
    }
    
    .detail-row {
        flex-direction: column;
        align-items: flex-start;
        gap: 5px;
    }
}

/* Address Form Styles */

.address-form-grid {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.form-row .form-group.full-width {
    grid-column: 1 / -1;
}

.form-group {
    display: flex;
    flex-direction: column;
}

.form-label {
    font-weight: 600;
    color: #495057;
    margin-bottom: 8px;
    font-size: 14px;
}

.form-input {
    padding: 12px 15px;
    border: 2px solid #e9ecef;
    border-radius: 6px;
    font-size: 16px;
    transition: all 0.3s;
    background: #fff;
}

.form-input:focus {
    outline: none;
    border-color: #007bff;
    box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
}

.checkbox-label {
    display: flex;
    align-items: center;
    cursor: pointer;
    font-size: 14px;
    color: #495057;
}

.checkbox-label input[type="checkbox"] {
    margin-right: 10px;
    transform: scale(1.2);
}

/* Delivery Address Summary Styles */
.delivery-summary {
    margin-bottom: 25px;
    padding: 20px;
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 8px;
}

.delivery-summary-title {
    font-size: 16px;
    font-weight: 600;
    color: #495057;
    margin-bottom: 15px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.delivery-summary-title i {
    color: #007bff;
}

.delivery-address-display {
    font-size: 14px;
}

.address-summary .address-name {
    font-weight: 600;
    color: #495057;
    margin-bottom: 5px;
}

.address-summary .address-mobile {
    color: #6c757d;
    margin-bottom: 8px;
}

.address-summary .address-details {
    color: #495057;
    line-height: 1.4;
}


/* Responsive Design */
@media (max-width: 768px) {
    .checkout-layout {
        grid-template-columns: 1fr;
        gap: 20px;
    }
    
    .order-summary {
        position: static;
    }
    
    .customer-info-grid {
        grid-template-columns: 1fr;
    }
    
    .order-item {
        grid-template-columns: 60px 1fr;
        gap: 15px;
    }
    
    .item-image {
        width: 60px;
        height: 60px;
    }
    
    .item-price-info {
        grid-column: 1 / -1;
        text-align: left;
        margin-top: 10px;
    }
    
    .checkout-actions {
        flex-direction: column;
    }
    
    .checkout-steps {
        flex-direction: column;
        gap: 10px;
    }
    
    .step {
        margin: 0;
    }
    
    .form-row {
        grid-template-columns: 1fr;
        gap: 15px;
    }
}
</style>


<!-- Checkout Content -->
<div class="checkout-container">
    <div class="container">
        <!-- Checkout Steps -->
        <div class="checkout-steps">
            <div class="step active">
                <div class="step-number">1</div>
                <span>Review</span>
            </div>
            <div class="step active">
                <div class="step-number">2</div>
                <span>Payment</span>
            </div>
            <div class="step active">
                <div class="step-number">3</div>
                <span>Complete</span>
            </div>
        </div>

        <?php if ($payment_success): ?>
            <!-- Success Popup Modal -->
            <div id="success-modal" class="success-modal-overlay">
                <div class="success-modal">
                <!-- Loading Animation -->
                <div id="loading-animation" class="loading-animation">
                    <div class="spinner"></div>
                    <h3>Processing Your Order...</h3>
                    <p>Please wait while we confirm your payment</p>
                </div>
                
                <!-- Success Animation -->
                <div id="success-check" class="success-check" style="display: none;">
                    <div class="checkmark-container">
                        <div class="checkmark-circle">
                            <div class="checkmark"></div>
                        </div>
                    </div>
                        <h3>🎉 Payment Successful!</h3>
                        <p class="success-message"><?php echo $success_message; ?></p>
                        
                        <div class="order-details">
                            <div class="detail-row">
                                <span class="detail-label">Order Number:</span>
                                <span class="detail-value"><?php echo isset($_SESSION['last_order_id']) ? 'ORD' . date('Ymd') . $_SESSION['last_order_id'] : 'Processing...'; ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Payment Method:</span>
                                <span class="detail-value">Cash on Delivery</span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Total Amount:</span>
                                <span class="detail-value">₹<?php echo number_format($grand_total, 2); ?></span>
                            </div>
                        </div>
                        
                        <div class="next-steps">
                            <p><i class="fas fa-envelope"></i> A confirmation email has been sent to your registered email address</p>
                            <p><i class="fas fa-truck"></i> Your order will be delivered within <?php echo $selected_shipping === 'express' ? '1 day' : ($selected_shipping === 'fast' ? '2-3 days' : '4-7 days'); ?></p>
                        </div>
                        
                        <div class="modal-actions">
                            <button class="btn-continue-shopping" onclick="window.location.href='../products/'">
                                <i class="fas fa-shopping-bag"></i> Continue Shopping
                            </button>
                            <button class="btn-view-orders" onclick="window.location.href='orders.php'">
                                <i class="fas fa-list"></i> View My Orders
                            </button>
                        </div>
                        
                        <p class="redirect-notice">Redirecting to order confirmation in <span id="countdown">3</span> seconds...</p>
                    </div>
                </div>
            </div>
        <?php else: ?>
            
            <!-- Error Message -->
            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i> <?php echo $error_message; ?>
                </div>
            <?php endif; ?>
            
            <!-- Debug Info -->
            <?php if (isset($_POST['place_order'])): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> Form submitted successfully! Processing order...
                </div>
            <?php endif; ?>

            <form id="checkout-form" method="POST" action="">
            <div class="checkout-layout">
                <!-- Main Checkout Form -->
                <div class="checkout-main">
                    
                    <!-- 1. Customer Information Section -->
                    <div class="checkout-section">
                        <div class="section-header">
                            <h3 class="section-title"><i class="fas fa-user"></i> Customer Information</h3>
                        </div>
                        <div class="section-content">
                            <div class="customer-info-grid">
                                <div class="info-field">
                                    <label class="info-label">Name</label>
                                    <div class="info-value"><?php echo htmlspecialchars($user['username']); ?></div>
                                </div>
                                <div class="info-field">
                                    <label class="info-label">Email</label>
                                    <div class="info-value"><?php echo htmlspecialchars($user['email']); ?></div>
                                </div>
                                <div class="info-field">
                                    <label class="info-label">Phone</label>
                                    <div class="info-value"><?php echo htmlspecialchars($user['phone_number'] ?? 'Not provided'); ?></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 2. Delivery Address Section -->
                    <div class="checkout-section">
                        <div class="section-header">
                            <h3 class="section-title"><i class="fas fa-map-marker-alt"></i> Delivery Address</h3>
                        </div>
                        <div class="section-content">
                            <div class="address-form-grid">
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="delivery_name" class="form-label">Full Name *</label>
                                        <input type="text" id="delivery_name" name="delivery_name" class="form-input" 
                                               value="<?php echo htmlspecialchars($user['username']); ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="delivery_mobile" class="form-label">Mobile Number *</label>
                                        <input type="tel" id="delivery_mobile" name="delivery_mobile" class="form-input" 
                                               value="<?php echo htmlspecialchars($user['phone_number'] ?? ''); ?>" 
                                               maxlength="10" required>
                                    </div>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group full-width">
                                        <label for="delivery_address_line_1" class="form-label">Address Line 1 *</label>
                                        <input type="text" id="delivery_address_line_1" name="delivery_address_line_1" class="form-input" 
                                               placeholder="House/Flat No., Building Name" 
                                               value="<?php echo htmlspecialchars($user['address'] ?? ''); ?>" required>
                                    </div>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group full-width">
                                        <label for="delivery_address_line_2" class="form-label">Address Line 2</label>
                                        <input type="text" id="delivery_address_line_2" name="delivery_address_line_2" class="form-input" 
                                               placeholder="Area, Street, Sector">
                                    </div>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="delivery_landmark" class="form-label">Landmark</label>
                                        <input type="text" id="delivery_landmark" name="delivery_landmark" class="form-input" 
                                               placeholder="Near landmark">
                                    </div>
                                    <div class="form-group">
                                        <label for="delivery_city" class="form-label">City *</label>
                                        <input type="text" id="delivery_city" name="delivery_city" class="form-input" 
                                               value="<?php echo htmlspecialchars($user['city'] ?? ''); ?>" required>
                                    </div>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="delivery_state" class="form-label">State *</label>
                                        <select id="delivery_state" name="delivery_state" class="form-input" required>
                                            <option value="">Select State</option>
                                            <option value="Gujarat" <?php echo ($user['state'] ?? '') === 'Gujarat' ? 'selected' : ''; ?>>Gujarat</option>
                                            <option value="Maharashtra">Maharashtra</option>
                                            <option value="Rajasthan">Rajasthan</option>
                                            <option value="Delhi">Delhi</option>
                                            <option value="Karnataka">Karnataka</option>
                                            <option value="Tamil Nadu">Tamil Nadu</option>
                                            <option value="West Bengal">West Bengal</option>
                                            <option value="Uttar Pradesh">Uttar Pradesh</option>
                                            <option value="Punjab">Punjab</option>
                                            <option value="Haryana">Haryana</option>
                                            <option value="Other">Other</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="delivery_pincode" class="form-label">Pincode *</label>
                                        <input type="text" id="delivery_pincode" name="delivery_pincode" class="form-input" 
                                               value="<?php echo htmlspecialchars($user['pincode'] ?? ''); ?>" 
                                               maxlength="6" required>
                                    </div>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="delivery_country" class="form-label">Country</label>
                                        <input type="text" id="delivery_country" name="delivery_country" class="form-input" 
                                               value="India" readonly>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 3. Order Items Section -->
                    <div class="checkout-section">
                        <div class="section-header">
                            <h3 class="section-title"><i class="fas fa-shopping-cart"></i> Order Items</h3>
                        </div>
                        <div class="section-content">
                            <?php foreach ($cart_items as $item): ?>
                                <div class="order-item">
                                    <img src="<?php echo UPLOAD_URL; ?>/<?php echo htmlspecialchars($item['product_image']); ?>" 
                                         alt="<?php echo htmlspecialchars($item['product_name']); ?>" 
                                         class="item-image">
                                    
                                    <div class="item-info">
                                        <div class="item-name"><?php echo htmlspecialchars($item['product_name']); ?></div>
                                        <div class="item-model"><?php echo htmlspecialchars($item['model_name'] . ' - ' . $item['model_number']); ?></div>
                                        <div class="item-brand"><?php echo htmlspecialchars($item['brand_name']); ?></div>
                                        
                                        <div class="item-specs">
                                            <span class="spec-badge"><?php echo htmlspecialchars($item['star_rating']); ?> Star</span>
                                            <span class="spec-badge"><?php echo htmlspecialchars($item['inverter']); ?> Inverter</span>
                                            <span class="spec-badge"><?php echo htmlspecialchars($item['capacity']); ?></span>
                                            <span class="spec-badge"><?php echo htmlspecialchars($item['warranty_years']); ?> Year Warranty</span>
                                        </div>
                                        
                                        <?php if ($item['amc_available']): ?>
                                            <div class="amc-option">
                                                <input type="checkbox" id="amc_<?php echo $item['product_id']; ?>" 
                                                       name="amc_<?php echo $item['product_id']; ?>" 
                                                       class="amc-checkbox" value="1">
                                                <label for="amc_<?php echo $item['product_id']; ?>" class="amc-label">
                                                    Add AMC (Annual Maintenance Contract)
                                                </label>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="item-price-info">
                                        <?php if ($item['original_price'] && $item['original_price'] > $item['price']): ?>
                                            <!-- Discount pricing display -->
                                            <div class="price-per-unit">
                                                <span class="current-price">₹<?php echo number_format($item['price'], 0); ?></span>
                                                <span class="original-price">₹<?php echo number_format($item['original_price'], 0); ?></span>
                                                <span class="discount-badge"><?php echo number_format($item['discount_percentage'], 0); ?>% OFF</span>
                                            </div>
                                        <?php else: ?>
                                            <!-- Regular pricing -->
                                            <div class="price-per-unit">₹<?php echo number_format($item['price'], 0); ?> per unit</div>
                                        <?php endif; ?>
                                        <div class="item-subtotal">₹<?php echo number_format($item['subtotal'], 0); ?></div>
                                        <div style="color: #6c757d; font-size: 12px;">Qty: <?php echo $item['quantity']; ?></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- 4. Payment Section -->
                    <div class="checkout-section">
                        <div class="section-header">
                            <h3 class="section-title"><i class="fas fa-credit-card"></i> Payment Method</h3>
                        </div>
                        <div class="section-content">
                            <div class="payment-methods">
                                <div class="payment-option">
                                    <input type="radio" id="cod" name="payment_method" value="Cash on Delivery" checked>
                                    <label for="cod" class="payment-label">
                                        <i class="fas fa-money-bill-wave payment-icon"></i>
                                        <div class="payment-info">
                                            <div class="payment-name">Cash on Delivery</div>
                                            <div class="payment-description">Pay when your order is delivered</div>
                                        </div>
                                        <div class="payment-status">COD</div>
                                    </label>
                                </div>
                                
                                <!-- UPI Payment Option - Commented Out
                                <div class="payment-option">
                                    <input type="radio" id="upi" name="payment_method" value="UPI">
                                    <label for="upi" class="payment-label">
                                        <i class="fas fa-mobile-alt payment-icon"></i>
                                        <div class="payment-info">
                                            <div class="payment-name">UPI Payment</div>
                                            <div class="payment-description">Pay using UPI apps like PhonePe, Google Pay</div>
                                        </div>
                                        <div class="payment-status">Instant</div>
                                    </label>
                                </div>
                                -->
                                
                                <!-- UPI Payment Form (Hidden by default) - Commented Out
                                <div id="upi-payment-form" class="payment-details-form" style="display: none;">
                                    <div class="payment-form-header">
                                        <h4><i class="fas fa-mobile-alt"></i> UPI Payment Details</h4>
                                        <p>Enter your UPI information for payment</p>
                                    </div>
                                    
                                    <div class="payment-form-content">
                                        <div class="form-row">
                                            <div class="form-group">
                                                <label for="upi_id" class="payment-label">UPI ID *</label>
                                                <input type="text" id="upi_id" name="upi_id" class="payment-input" 
                                                       placeholder="yourname@paytm or 9876543210@upi">
                                                <div class="payment-help">
                                                    <i class="fas fa-info-circle"></i>
                                                    <span>Enter your UPI ID (e.g., yourname@paytm, 9876543210@upi)</span>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="form-row">
                                            <div class="form-group">
                                                <label for="upi_app" class="payment-label">Preferred UPI App *</label>
                                                <select id="upi_app" name="upi_app" class="payment-select">
                                                    <option value="">Select UPI App</option>
                                                    <option value="PhonePe">PhonePe</option>
                                                    <option value="Google Pay">Google Pay</option>
                                                    <option value="Paytm">Paytm</option>
                                                    <option value="BHIM">BHIM</option>
                                                    <option value="Amazon Pay">Amazon Pay</option>
                                                    <option value="WhatsApp Pay">WhatsApp Pay</option>
                                                    <option value="Other">Other</option>
                                                </select>
                                            </div>
                                        </div>
                                        
                                        <div class="form-row">
                                            <div class="form-group">
                                                <label for="upi_mobile" class="payment-label">Mobile Number *</label>
                                                <input type="text" id="upi_mobile" name="upi_mobile" class="payment-input" 
                                                       placeholder="9876543210" maxlength="10">
                                            </div>
                                        </div>
                                        
                                        <div class="payment-notice">
                                            <i class="fas fa-shield-alt"></i>
                                            <span>You will be redirected to your UPI app to complete the payment securely.</span>
                                        </div>
                                    </div>
                                </div>
                                -->
                                
                                <!-- Net Banking Payment Option - Commented Out
                                <div class="payment-option">
                                    <input type="radio" id="netbanking" name="payment_method" value="Net Banking">
                                    <label for="netbanking" class="payment-label">
                                        <i class="fas fa-university payment-icon"></i>
                                        <div class="payment-info">
                                            <div class="payment-name">Net Banking</div>
                                            <div class="payment-description">Pay using your bank account</div>
                                        </div>
                                        <div class="payment-status">Secure</div>
                                    </label>
                                </div>
                                -->
                                
                                <!-- Net Banking Payment Form (Hidden by default) - Commented Out
                                <div id="netbanking-payment-form" class="payment-details-form" style="display: none;">
                                    <div class="payment-form-header">
                                        <h4><i class="fas fa-university"></i> Net Banking Details</h4>
                                        <p>Select your bank for secure online payment</p>
                                    </div>
                                    
                                    <div class="payment-form-content">
                                        <div class="form-row">
                                            <div class="form-group">
                                                <label for="bank_name" class="payment-label">Select Bank *</label>
                                                <select id="bank_name" name="bank_name" class="payment-select">
                                                    <option value="">Select Your Bank</option>
                                                    <option value="State Bank of India">State Bank of India</option>
                                                    <option value="HDFC Bank">HDFC Bank</option>
                                                    <option value="ICICI Bank">ICICI Bank</option>
                                                    <option value="Axis Bank">Axis Bank</option>
                                                    <option value="Kotak Mahindra Bank">Kotak Mahindra Bank</option>
                                                    <option value="Punjab National Bank">Punjab National Bank</option>
                                                    <option value="Bank of Baroda">Bank of Baroda</option>
                                                    <option value="Canara Bank">Canara Bank</option>
                                                    <option value="Union Bank of India">Union Bank of India</option>
                                                    <option value="Indian Bank">Indian Bank</option>
                                                    <option value="Bank of India">Bank of India</option>
                                                    <option value="Central Bank of India">Central Bank of India</option>
                                                    <option value="IDBI Bank">IDBI Bank</option>
                                                    <option value="Yes Bank">Yes Bank</option>
                                                    <option value="IndusInd Bank">IndusInd Bank</option>
                                                    <option value="Federal Bank">Federal Bank</option>
                                                    <option value="South Indian Bank">South Indian Bank</option>
                                                    <option value="Karnataka Bank">Karnataka Bank</option>
                                                    <option value="Other">Other</option>
                                                </select>
                                            </div>
                                        </div>
                                        
                                        <div class="form-row">
                                            <div class="form-group">
                                                <label for="account_holder" class="payment-label">Account Holder Name *</label>
                                                <input type="text" id="account_holder" name="account_holder" class="payment-input" 
                                                       placeholder="Enter account holder name">
                                            </div>
                                        </div>
                                        
                                        <div class="form-row">
                                            <div class="form-group">
                                                <label for="account_number" class="payment-label">Account Number (Last 4 digits) *</label>
                                                <input type="text" id="account_number" name="account_number" class="payment-input" 
                                                       placeholder="1234" maxlength="4">
                                                <div class="payment-help">
                                                    <i class="fas fa-info-circle"></i>
                                                    <span>Enter only the last 4 digits of your account number</span>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="form-row">
                                            <div class="form-group">
                                                <label for="ifsc_code" class="payment-label">IFSC Code *</label>
                                                <input type="text" id="ifsc_code" name="ifsc_code" class="payment-input" 
                                                       placeholder="SBIN0001234" maxlength="11">
                                                <div class="payment-help">
                                                    <i class="fas fa-info-circle"></i>
                                                    <span>Enter your bank's IFSC code (e.g., SBIN0001234)</span>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="payment-notice">
                                            <i class="fas fa-shield-alt"></i>
                                            <span>You will be redirected to your bank's secure payment gateway to complete the transaction.</span>
                                        </div>
                                    </div>
                                </div>
                                -->
                                
                                <!-- Credit/Debit Card Payment Option - Commented Out
                                <div class="payment-option">
                                    <input type="radio" id="card" name="payment_method" value="Credit/Debit Card">
                                    <label for="card" class="payment-label">
                                        <i class="fas fa-credit-card payment-icon"></i>
                                        <div class="payment-info">
                                            <div class="payment-name">Credit/Debit Card</div>
                                            <div class="payment-description">Pay using Visa, MasterCard, RuPay</div>
                                        </div>
                                        <div class="payment-status">Secure</div>
                                    </label>
                                </div>
                                -->
                            </div>
                            
                            <!-- Card Payment Form (Hidden by default) - Commented Out
                            <div id="card-payment-form" class="card-payment-form" style="display: none;">
                                <div class="card-form-header">
                                    <h4><i class="fas fa-credit-card"></i> Card Details</h4>
                                    <p>Enter your card information securely</p>
                                </div>
                                
                                <div class="card-form-content">
                                    <div class="form-row">
                                        <div class="form-group">
                                            <label for="card_number" class="card-label">Card Number *</label>
                                            <input type="text" id="card_number" name="card_number" class="card-input" 
                                                   placeholder="1234 5678 9012 3456" maxlength="19">
                                            <div class="card-icons">
                                                <i class="fab fa-cc-visa"></i>
                                                <i class="fab fa-cc-mastercard"></i>
                                                <i class="fas fa-credit-card"></i>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="form-row">
                                        <div class="form-group">
                                            <label for="card_holder" class="card-label">Cardholder Name *</label>
                                            <input type="text" id="card_holder" name="card_holder" class="card-input" 
                                                   placeholder="John Doe">
                                        </div>
                                    </div>
                                    
                                    <div class="form-row">
                                        <div class="form-group">
                                            <label for="expiry_date" class="card-label">Expiry Date *</label>
                                            <input type="text" id="expiry_date" name="expiry_date" class="card-input" 
                                                   placeholder="MM/YY" maxlength="5">
                                        </div>
                                        <div class="form-group">
                                            <label for="cvv" class="card-label">CVV *</label>
                                            <input type="text" id="cvv" name="cvv" class="card-input" 
                                                   placeholder="123" maxlength="4">
                                            <div class="cvv-info">
                                                <i class="fas fa-info-circle"></i>
                                                <span>3-4 digit security code</span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="form-row">
                                        <div class="form-group">
                                            <label for="billing_address" class="card-label">Billing Address *</label>
                                            <textarea id="billing_address" name="billing_address" class="card-textarea" 
                                                      placeholder="Enter your billing address..."></textarea>
                                        </div>
                                    </div>
                                    
                                    <div class="form-row">
                                        <div class="form-group">
                                            <label for="billing_city" class="card-label">City *</label>
                                            <input type="text" id="billing_city" name="billing_city" class="card-input" 
                                                   placeholder="Enter city">
                                        </div>
                                        <div class="form-group">
                                            <label for="billing_pincode" class="card-label">Pincode *</label>
                                            <input type="text" id="billing_pincode" name="billing_pincode" class="card-input" 
                                                   placeholder="123456" maxlength="6">
                                        </div>
                                    </div>
                                    
                                    <div class="security-notice">
                                        <i class="fas fa-shield-alt"></i>
                                        <span>Your card information is encrypted and secure. We do not store your card details.</span>
                                    </div>
                                </div>
                            </div>
                            -->
                        </div>
                    </div>

                    <!-- 5. Confirmation Section -->
                    <div class="checkout-section">
                        <div class="section-header">
                            <h3 class="section-title"><i class="fas fa-clipboard-check"></i> Order Confirmation</h3>
                        </div>
                        <div class="section-content">
                            <!-- Notes Section -->
                            <div class="notes-section">
                                <label for="notes" class="notes-label">Special Instructions (Optional)</label>
                                <textarea id="notes" name="notes" class="notes-textarea" 
                                          placeholder="Any special instructions for delivery..."></textarea>
                            </div>
                            
                            <!-- Delivery Information -->
                            <div class="delivery-info">
                                <h5><i class="fas fa-truck"></i> Delivery Information</h5>
                                <p><strong>Shipping Method:</strong> <?php echo $shipping_label; ?> - ₹<?php echo number_format($shipping_cost, 2); ?></p>
                                <p><strong>Estimated Delivery:</strong> <?php echo date('M d, Y', strtotime('+' . ($selected_shipping === 'express' ? 1 : ($selected_shipping === 'fast' ? 3 : 7)) . ' days')); ?></p>
                                <p><strong>Installation:</strong> Professional installation service available</p>
                            </div>
                            
                        </div>
                    </div>
                </div>
                
                <!-- Order Summary Sidebar -->
                <div class="order-summary">
                    <h3 class="summary-title">Order Summary</h3>
                    
                    <!-- Delivery Address Summary -->
                    <div class="delivery-summary">
                        <h4 class="delivery-summary-title">
                            <i class="fas fa-map-marker-alt"></i> Delivery Address
                        </h4>
                        <div class="address-summary">
                            <div class="address-name"><?php echo htmlspecialchars($user['username']); ?></div>
                            <div class="address-mobile"><?php echo htmlspecialchars($user['phone_number'] ?? 'Not provided'); ?></div>
                            <div class="address-details">
                                <?php echo htmlspecialchars($user['address'] ?? 'Address will be entered in the form'); ?>
                                <?php if (!empty($user['city'])): ?>
                                    <br><?php echo htmlspecialchars($user['city']); ?><?php echo !empty($user['pincode']) ? ' - ' . htmlspecialchars($user['pincode']) : ''; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="summary-row">
                        <span class="summary-label">Items (<?php echo $total_items; ?>):</span>
                        <span class="summary-value">₹<?php echo number_format($subtotal, 2); ?></span>
                    </div>
                    
                    <div class="summary-row">
                        <span class="summary-label">Shipping:</span>
                        <span class="summary-value">₹<?php echo number_format($shipping_cost, 2); ?></span>
                    </div>
                    
                    <?php if ($discount_amount > 0): ?>
                        <div class="summary-row">
                            <span class="summary-label">Discount:</span>
                            <span class="summary-value discount">-₹<?php echo number_format($discount_amount, 2); ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <div class="summary-row">
                        <span class="summary-label">GST (28%):</span>
                        <span class="summary-value">₹<?php echo number_format($tax, 2); ?></span>
                    </div>
                    
                    <div class="summary-row total">
                        <span class="summary-label">Total:</span>
                        <span class="summary-value total">₹<?php echo number_format($grand_total, 2); ?></span>
                    </div>
                    
                    <!-- Action Buttons -->
                    <div class="checkout-actions">
                        <button type="submit" form="checkout-form" class="btn-place-order">
                            <i class="fas fa-lock"></i>
                            Place Order
                        </button>
                    </div>
                    
                    <div class="security-badge">
                        <i class="fas fa-shield-alt"></i>
                        <p>Secure 256-bit SSL encryption</p>
                    </div>
                </div>
            </div>
            <input type="hidden" name="place_order" value="1">
            </form>
        <?php endif; ?>
    </div>
</div>

<style>
/* Payment Details Form Styles */
.payment-details-form {
    margin-top: 20px;
    padding: 25px;
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 12px;
    animation: slideDown 0.3s ease-out;
}

.card-payment-form {
    margin-top: 20px;
    padding: 25px;
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 12px;
    animation: slideDown 0.3s ease-out;
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.payment-form-header, .card-form-header {
    margin-bottom: 20px;
    text-align: center;
}

.payment-form-header h4, .card-form-header h4 {
    color: #2c3e50;
    margin-bottom: 5px;
    font-size: 18px;
}

.payment-form-header p, .card-form-header p {
    color: #6c757d;
    font-size: 14px;
    margin: 0;
}

.payment-form-content, .card-form-content {
    max-width: 100%;
}

.payment-input, .payment-select {
    width: 100%;
    padding: 12px 15px;
    border: 2px solid #e9ecef;
    border-radius: 8px;
    font-size: 16px;
    transition: all 0.3s ease;
    background: white;
}

.payment-input:focus, .payment-select:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.payment-help {
    display: flex;
    align-items: center;
    gap: 5px;
    margin-top: 5px;
    color: #6c757d;
    font-size: 12px;
}

.payment-notice {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 15px;
    background: #e8f5e8;
    border: 1px solid #c3e6c3;
    border-radius: 8px;
    color: #2d5a2d;
    font-size: 14px;
    margin-top: 20px;
}

.payment-notice i {
    color: #28a745;
    font-size: 16px;
}

.form-row {
    display: flex;
    gap: 15px;
    margin-bottom: 20px;
}

.form-row .form-group {
    flex: 1;
    position: relative;
}

.card-label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: #2c3e50;
    font-size: 14px;
}

.card-input, .card-textarea {
    width: 100%;
    padding: 12px 15px;
    border: 2px solid #e9ecef;
    border-radius: 8px;
    font-size: 16px;
    transition: all 0.3s ease;
    background: white;
}

.card-input:focus, .card-textarea:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.card-textarea {
    min-height: 80px;
    resize: vertical;
}

.card-icons {
    position: absolute;
    right: 15px;
    top: 50%;
    transform: translateY(-50%);
    display: flex;
    gap: 8px;
    color: #6c757d;
    font-size: 18px;
}

.cvv-info {
    position: absolute;
    right: 15px;
    top: 50%;
    transform: translateY(-50%);
    display: flex;
    align-items: center;
    gap: 5px;
    color: #6c757d;
    font-size: 12px;
}

.security-notice {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 15px;
    background: #e8f5e8;
    border: 1px solid #c3e6c3;
    border-radius: 8px;
    color: #2d5a2d;
    font-size: 14px;
    margin-top: 20px;
}

.security-notice i {
    color: #28a745;
    font-size: 16px;
}

/* Card number formatting */
.card-input[data-type="card-number"] {
    letter-spacing: 1px;
}

/* Responsive design */
@media (max-width: 768px) {
    .form-row {
        flex-direction: column;
        gap: 0;
    }
    
    .card-payment-form {
        padding: 20px 15px;
    }
    
    .card-icons, .cvv-info {
        position: static;
        transform: none;
        margin-top: 5px;
        justify-content: flex-end;
    }
}
</style>

<script>
// Checkout Page JavaScript

// Luhn algorithm for card number validation
function validateCardNumber(cardNumber) {
    // Remove all non-digit characters
    const digits = cardNumber.replace(/\D/g, '');
    
    // Check if the number is empty or too short
    if (digits.length < 13 || digits.length > 19) {
        return false;
    }
    
    // Luhn algorithm
    let sum = 0;
    let isEven = false;
    
    // Process digits from right to left
    for (let i = digits.length - 1; i >= 0; i--) {
        let digit = parseInt(digits[i]);
        
        if (isEven) {
            digit *= 2;
            if (digit > 9) {
                digit -= 9;
            }
        }
        
        sum += digit;
        isEven = !isEven;
    }
    
    return sum % 10 === 0;
}

document.addEventListener('DOMContentLoaded', function() {
    // Image loading with fallback
    const images = document.querySelectorAll('img');
    images.forEach(img => {
        img.addEventListener('error', function() {
            if (this.src !== '<?php echo IMG_URL; ?>/placeholder-product.png') {
                this.src = '<?php echo IMG_URL; ?>/placeholder-product.png';
            }
        });
        
        img.addEventListener('load', function() {
            this.style.opacity = '1';
        });
        
        if (img.complete) {
            img.style.opacity = '1';
        } else {
            img.style.opacity = '0.7';
            img.style.transition = 'opacity 0.3s ease';
        }
    });
    
    // Simple address form validation
    
    // Mobile number validation
    const mobileInput = document.getElementById('delivery_mobile');
    if (mobileInput) {
        mobileInput.addEventListener('input', function(e) {
            e.target.value = e.target.value.replace(/[^0-9]/g, '');
        });
    }
    
    // Pincode validation
    const pincodeInput = document.getElementById('delivery_pincode');
    if (pincodeInput) {
        pincodeInput.addEventListener('input', function(e) {
            e.target.value = e.target.value.replace(/[^0-9]/g, '');
        });
    }
    
    // Payment method selection handling - Only COD for now
    const paymentMethods = document.querySelectorAll('input[name="payment_method"]');
    /* Commented out payment forms for non-COD methods
    const cardPaymentForm = document.getElementById('card-payment-form');
    const upiPaymentForm = document.getElementById('upi-payment-form');
    const netbankingPaymentForm = document.getElementById('netbanking-payment-form');
    */
    
    paymentMethods.forEach(method => {
        method.addEventListener('change', function() {
            // Hide all payment detail forms and remove required attributes - Only COD for now
            /* Commented out payment form handling for non-COD methods
            cardPaymentForm.style.display = 'none';
            upiPaymentForm.style.display = 'none';
            netbankingPaymentForm.style.display = 'none';
            
            // Remove required attributes from all payment form fields
            document.getElementById('card_number').required = false;
            document.getElementById('card_holder').required = false;
            document.getElementById('expiry_date').required = false;
            document.getElementById('cvv').required = false;
            document.getElementById('billing_address').required = false;
            document.getElementById('billing_city').required = false;
            document.getElementById('billing_pincode').required = false;
            document.getElementById('upi_id').required = false;
            document.getElementById('upi_app').required = false;
            document.getElementById('upi_mobile').required = false;
            document.getElementById('bank_name').required = false;
            document.getElementById('account_holder').required = false;
            document.getElementById('account_number').required = false;
            document.getElementById('ifsc_code').required = false;
            */
            
            // Update payment status display based on selection
            const paymentStatus = document.querySelectorAll('.payment-status');
            paymentStatus.forEach(status => {
                status.style.background = '#fff3cd';
                status.style.color = '#856404';
                status.textContent = 'Pending';
            });
            
            // Show appropriate payment form based on selection and set required attributes - Only COD for now
            /* Commented out payment form display for non-COD methods
            if (this.value === 'Credit/Debit Card') {
                cardPaymentForm.style.display = 'block';
                // Add required attributes to card form fields
                document.getElementById('card_number').required = true;
                document.getElementById('card_holder').required = true;
                document.getElementById('expiry_date').required = true;
                document.getElementById('cvv').required = true;
                document.getElementById('billing_address').required = true;
                document.getElementById('billing_city').required = true;
                document.getElementById('billing_pincode').required = true;
            } else if (this.value === 'UPI') {
                upiPaymentForm.style.display = 'block';
                // Add required attributes to UPI form fields
                document.getElementById('upi_id').required = true;
                document.getElementById('upi_app').required = true;
                document.getElementById('upi_mobile').required = true;
            } else if (this.value === 'Net Banking') {
                netbankingPaymentForm.style.display = 'block';
                // Add required attributes to Net Banking form fields
                document.getElementById('bank_name').required = true;
                document.getElementById('account_holder').required = true;
                document.getElementById('account_number').required = true;
                document.getElementById('ifsc_code').required = true;
            }
            */
            
            // Highlight selected payment method
            if (this.value === 'Cash on Delivery') {
                this.closest('.payment-option').querySelector('.payment-status').textContent = 'COD';
                this.closest('.payment-option').querySelector('.payment-status').style.background = '#d1ecf1';
                this.closest('.payment-option').querySelector('.payment-status').style.color = '#0c5460';
            } else {
                this.closest('.payment-option').querySelector('.payment-status').textContent = 'Secure';
                this.closest('.payment-option').querySelector('.payment-status').style.background = '#d4edda';
                this.closest('.payment-option').querySelector('.payment-status').style.color = '#155724';
            }
        });
    });
    
    // Card payment form functionality - Commented out
    /* Commented out card payment form handling
    if (cardPaymentForm) {
        // Card number formatting
        const cardNumberInput = document.getElementById('card_number');
        if (cardNumberInput) {
            cardNumberInput.addEventListener('input', function(e) {
                let value = e.target.value.replace(/\s+/g, '').replace(/[^0-9]/gi, '');
                let formattedValue = value.match(/.{1,4}/g)?.join(' ') || value;
                if (formattedValue.length > 19) {
                    formattedValue = formattedValue.substr(0, 19);
                }
                e.target.value = formattedValue;
            });
        }
        
        // Expiry date formatting
        const expiryInput = document.getElementById('expiry_date');
        if (expiryInput) {
            expiryInput.addEventListener('input', function(e) {
                let value = e.target.value.replace(/\D/g, '');
                if (value.length >= 2) {
                    value = value.substring(0, 2) + '/' + value.substring(2, 4);
                }
                e.target.value = value;
            });
        }
        
        // CVV validation
        const cvvInput = document.getElementById('cvv');
        if (cvvInput) {
            cvvInput.addEventListener('input', function(e) {
                e.target.value = e.target.value.replace(/[^0-9]/g, '');
            });
        }
        
        // Pincode validation
        const pincodeInput = document.getElementById('billing_pincode');
        if (pincodeInput) {
            pincodeInput.addEventListener('input', function(e) {
                e.target.value = e.target.value.replace(/[^0-9]/g, '');
            });
        }
        
        // Cardholder name validation
        const cardHolderInput = document.getElementById('card_holder');
        if (cardHolderInput) {
            cardHolderInput.addEventListener('input', function(e) {
                e.target.value = e.target.value.replace(/[^a-zA-Z\s]/g, '');
            });
        }
    }
    */
    
    // UPI payment form functionality - Commented out
    /* Commented out UPI payment form handling
    if (upiPaymentForm) {
        // UPI ID validation
        const upiIdInput = document.getElementById('upi_id');
        if (upiIdInput) {
            upiIdInput.addEventListener('input', function(e) {
                // Allow UPI ID format: name@provider or number@upi
                e.target.value = e.target.value.toLowerCase();
            });
        }
        
        // Mobile number validation
        const upiMobileInput = document.getElementById('upi_mobile');
        if (upiMobileInput) {
            upiMobileInput.addEventListener('input', function(e) {
                e.target.value = e.target.value.replace(/[^0-9]/g, '');
            });
        }
    }
    */
    
    // Net Banking form functionality - Commented out
    /* Commented out Net Banking form handling
    if (netbankingPaymentForm) {
        // Account holder name validation
        const accountHolderInput = document.getElementById('account_holder');
        if (accountHolderInput) {
            accountHolderInput.addEventListener('input', function(e) {
                e.target.value = e.target.value.replace(/[^a-zA-Z\s]/g, '');
            });
        }
        
        // Account number validation (last 4 digits)
        const accountNumberInput = document.getElementById('account_number');
        if (accountNumberInput) {
            accountNumberInput.addEventListener('input', function(e) {
                e.target.value = e.target.value.replace(/[^0-9]/g, '');
            });
        }
        
        // IFSC code validation
        const ifscCodeInput = document.getElementById('ifsc_code');
        if (ifscCodeInput) {
            ifscCodeInput.addEventListener('input', function(e) {
                e.target.value = e.target.value.toUpperCase();
            });
        }
    }
    */
    
    // Form validation before submission
    const placeOrderForm = document.getElementById('checkout-form');
    if (placeOrderForm) {
        placeOrderForm.addEventListener('submit', function(e) {
            const paymentMethod = document.querySelector('input[name="payment_method"]:checked');
            
            // Validate required address fields
            const deliveryName = document.getElementById('delivery_name').value.trim();
            const deliveryMobile = document.getElementById('delivery_mobile').value.trim();
            const deliveryAddressLine1 = document.getElementById('delivery_address_line_1').value.trim();
            const deliveryCity = document.getElementById('delivery_city').value.trim();
            const deliveryState = document.getElementById('delivery_state').value;
            const deliveryPincode = document.getElementById('delivery_pincode').value.trim();
            
            if (!deliveryName) {
                e.preventDefault();
                alert('Please enter the delivery name.');
                document.getElementById('delivery_name').focus();
                return false;
            }
            
            if (!deliveryMobile || deliveryMobile.length !== 10) {
                e.preventDefault();
                alert('Please enter a valid 10-digit mobile number.');
                document.getElementById('delivery_mobile').focus();
                return false;
            }
            
            if (!deliveryAddressLine1 || deliveryAddressLine1.length < 5) {
                e.preventDefault();
                alert('Please enter a complete address (at least 5 characters).');
                document.getElementById('delivery_address_line_1').focus();
                return false;
            }
            
            if (!deliveryCity) {
                e.preventDefault();
                alert('Please enter the city.');
                document.getElementById('delivery_city').focus();
                return false;
            }
            
            if (!deliveryState) {
                e.preventDefault();
                alert('Please select the state.');
                document.getElementById('delivery_state').focus();
                return false;
            }
            
            if (!deliveryPincode || deliveryPincode.length !== 6) {
                e.preventDefault();
                alert('Please enter a valid 6-digit pincode.');
                document.getElementById('delivery_pincode').focus();
                return false;
            }
            
            if (!paymentMethod) {
                e.preventDefault();
                alert('Please select a payment method.');
                return false;
            }
            
            
            // Validate payment details based on selected method - Only COD for now
            /* Commented out payment validation for non-COD methods
            if (paymentMethod.value === 'Credit/Debit Card') {
                const cardNumber = document.getElementById('card_number').value.replace(/\s/g, '');
                const cardHolder = document.getElementById('card_holder').value.trim();
                const expiryDate = document.getElementById('expiry_date').value;
                const cvv = document.getElementById('cvv').value;
                const billingAddress = document.getElementById('billing_address').value.trim();
                const billingCity = document.getElementById('billing_city').value.trim();
                const billingPincode = document.getElementById('billing_pincode').value;
                
                // Card number validation (basic Luhn algorithm check)
                if (!cardNumber || cardNumber.length < 13 || cardNumber.length > 19) {
                    e.preventDefault();
                    alert('Please enter a valid card number.');
                    document.getElementById('card_number').focus();
                    return false;
                }
                
                // Basic Luhn algorithm validation
                if (!validateCardNumber(cardNumber)) {
                    e.preventDefault();
                    alert('Please enter a valid card number.');
                    document.getElementById('card_number').focus();
                    return false;
                }
                
                if (!cardHolder || cardHolder.length < 2) {
                    e.preventDefault();
                    alert('Please enter the cardholder name.');
                    document.getElementById('card_holder').focus();
                    return false;
                }
                
                if (!expiryDate || !/^\d{2}\/\d{2}$/.test(expiryDate)) {
                    e.preventDefault();
                    alert('Please enter a valid expiry date (MM/YY).');
                    document.getElementById('expiry_date').focus();
                    return false;
                }
                
                // Check if expiry date is not in the past
                const [month, year] = expiryDate.split('/');
                const expiryDateObj = new Date(2000 + parseInt(year), parseInt(month) - 1);
                const currentDate = new Date();
                if (expiryDateObj < currentDate) {
                    e.preventDefault();
                    alert('Card has expired. Please enter a valid expiry date.');
                    document.getElementById('expiry_date').focus();
                    return false;
                }
                
                if (!cvv || cvv.length < 3 || cvv.length > 4) {
                    e.preventDefault();
                    alert('Please enter a valid CVV.');
                    document.getElementById('cvv').focus();
                    return false;
                }
                
                if (!billingAddress || billingAddress.length < 10) {
                    e.preventDefault();
                    alert('Please enter your complete billing address.');
                    document.getElementById('billing_address').focus();
                    return false;
                }
                
                if (!billingCity || billingCity.length < 2) {
                    e.preventDefault();
                    alert('Please enter your billing city.');
                    document.getElementById('billing_city').focus();
                    return false;
                }
                
                if (!billingPincode || billingPincode.length !== 6) {
                    e.preventDefault();
                    alert('Please enter a valid 6-digit pincode.');
                    document.getElementById('billing_pincode').focus();
                    return false;
                }
            } else if (paymentMethod.value === 'UPI') {
                const upiId = document.getElementById('upi_id').value.trim();
                const upiApp = document.getElementById('upi_app').value;
                const upiMobile = document.getElementById('upi_mobile').value;
                
                if (!upiId || !upiId.includes('@')) {
                    e.preventDefault();
                    alert('Please enter a valid UPI ID (e.g., yourname@paytm).');
                    document.getElementById('upi_id').focus();
                    return false;
                }
                
                if (!upiApp) {
                    e.preventDefault();
                    alert('Please select your preferred UPI app.');
                    document.getElementById('upi_app').focus();
                    return false;
                }
                
                if (!upiMobile || upiMobile.length !== 10) {
                    e.preventDefault();
                    alert('Please enter a valid 10-digit mobile number.');
                    document.getElementById('upi_mobile').focus();
                    return false;
                }
            } else if (paymentMethod.value === 'Net Banking') {
                const bankName = document.getElementById('bank_name').value;
                const accountHolder = document.getElementById('account_holder').value.trim();
                const accountNumber = document.getElementById('account_number').value;
                const ifscCode = document.getElementById('ifsc_code').value.trim();
                
                if (!bankName) {
                    e.preventDefault();
                    alert('Please select your bank.');
                    document.getElementById('bank_name').focus();
                    return false;
                }
                
                if (!accountHolder || accountHolder.length < 2) {
                    e.preventDefault();
                    alert('Please enter the account holder name.');
                    document.getElementById('account_holder').focus();
                    return false;
                }
                
                if (!accountNumber || accountNumber.length !== 4) {
                    e.preventDefault();
                    alert('Please enter the last 4 digits of your account number.');
                    document.getElementById('account_number').focus();
                    return false;
                }
                
                if (!ifscCode || ifscCode.length !== 11) {
                    e.preventDefault();
                    alert('Please enter a valid 11-character IFSC code.');
                    document.getElementById('ifsc_code').focus();
                    return false;
                }
            }
            */
            
            // Show loading state
            const submitBtn = document.querySelector('.btn-place-order');
            if (submitBtn) {
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
                submitBtn.disabled = true;
            }
        });
    }
    
    
    // AMC checkbox handling
    const amcCheckboxes = document.querySelectorAll('.amc-checkbox');
    amcCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            if (this.checked) {
                // You can add AMC cost calculation here if needed
                console.log('AMC selected for product:', this.name);
            }
        });
    });
    
    // Auto-resize textarea
    const textareas = document.querySelectorAll('textarea');
    textareas.forEach(textarea => {
        textarea.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = this.scrollHeight + 'px';
        });
    });
});

// Success Animation Sequence with Popup Modal
document.addEventListener('DOMContentLoaded', function() {
    const loadingAnimation = document.getElementById('loading-animation');
    const successCheck = document.getElementById('success-check');
    const countdownElement = document.getElementById('countdown');
    
    if (loadingAnimation && successCheck) {
        // Show loading animation for 2.5 seconds
        setTimeout(function() {
            loadingAnimation.style.display = 'none';
            successCheck.style.display = 'block';
            
            // Start countdown timer
            let countdown = 3;
            const countdownInterval = setInterval(function() {
                countdownElement.textContent = countdown;
                countdown--;
                
                if (countdown < 0) {
                    clearInterval(countdownInterval);
                    // Redirect to success page
                window.location.href = 'success.php?order=<?php echo isset($_SESSION['last_order_id']) ? $_SESSION['last_order_id'] : ''; ?>';
                }
            }, 1000);
            
        }, 2500);
    }
    
    // Prevent modal from closing when clicking outside (optional)
    const modalOverlay = document.getElementById('success-modal');
    if (modalOverlay) {
        modalOverlay.addEventListener('click', function(e) {
            if (e.target === modalOverlay) {
                // Uncomment the line below if you want to allow closing by clicking outside
                // modalOverlay.style.display = 'none';
            }
        });
    }
});
</script>

<?php
include INCLUDES_PATH . '/templates/footer.php';
?>
