<?php
require_once dirname(dirname(__DIR__)) . '/includes/config/init.php';
require_once INCLUDES_PATH . '/functions/security_helpers.php';

// Ensure the admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: ' . admin_url('login'));
    exit();
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . admin_url('products'));
    exit();
}

// Validate CSRF token
if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
    $_SESSION['error_message'] = 'Invalid security token. Please try again.';
    header('Location: ' . admin_url('products'));
    exit();
}

// Get product ID from URL or form
$product_id = isset($_GET['id']) ? intval($_GET['id']) : (isset($_POST['product_id']) ? intval($_POST['product_id']) : 0);

if ($product_id <= 0) {
    $_SESSION['error_message'] = 'Invalid product ID.';
    header('Location: ' . admin_url('products'));
    exit();
}

// Validate and sanitize input data
$product_name = trim($_POST['product_name'] ?? '');
$price = floatval($_POST['price'] ?? 0);
$original_price = floatval($_POST['original_price'] ?? 0);
$discount_percentage = floatval($_POST['discount_percentage'] ?? 0);
$category_id = intval($_POST['category_id'] ?? 0);
$sub_category_id = intval($_POST['sub_category_id'] ?? 0);
$model_name = trim($_POST['model_name'] ?? '');
$model_number = trim($_POST['model_number'] ?? '');
$star_rating = intval($_POST['star_rating'] ?? 0);
$energy_rating = trim($_POST['energy_rating'] ?? '');
$warranty_years = intval($_POST['warranty_years'] ?? 1);
$warranty_compressor_5 = isset($_POST['warranty_compressor_5']) ? 1 : 0;
$warranty_compressor_10 = isset($_POST['warranty_compressor_10']) ? 1 : 0;
$warranty_pcb_5 = isset($_POST['warranty_pcb_5']) ? 1 : 0;
$description = trim($_POST['description'] ?? '');
$show_on_homepage = isset($_POST['show_on_homepage']) ? 1 : 0;
$show_on_product_page = isset($_POST['show_on_product_page']) ? 1 : 0;

// Validate required fields
$errors = [];

if (empty($product_name)) {
    $errors[] = 'Product name is required.';
}

if ($price <= 0) {
    $errors[] = 'Special price must be greater than 0.';
}

if ($original_price <= 0) {
    $errors[] = 'MRP must be greater than 0.';
}

if ($price > $original_price) {
    $errors[] = 'Special price cannot be higher than MRP.';
}

if ($category_id <= 0) {
    $errors[] = 'Please select a valid category.';
}

if (empty($model_name)) {
    $errors[] = 'Model name is required.';
}

if (empty($model_number)) {
    $errors[] = 'Model number is required.';
}

if ($star_rating <= 0 || $star_rating > 5) {
    $errors[] = 'Please select a valid star rating (1-5).';
}

if (empty($energy_rating)) {
    $errors[] = 'Energy rating is required.';
}

if (empty($description)) {
    $errors[] = 'Description is required.';
}

// If there are validation errors, redirect back to edit form
if (!empty($errors)) {
    $_SESSION['error_message'] = implode(' ', $errors);
    header('Location: ' . admin_url('products/edit?id=' . $product_id));
    exit();
}

try {
    // Check if product exists
    $check_sql = "SELECT id FROM products WHERE id = ?";
    $check_stmt = $pdo->prepare($check_sql);
    $check_stmt->execute([$product_id]);
    
    if (!$check_stmt->fetch()) {
        $_SESSION['error_message'] = 'Product not found.';
        header('Location: ' . admin_url('products'));
        exit();
    }
    
    // Handle multiple file uploads if new images are provided
    $uploaded_images = [];
    if (isset($_FILES['product_images']) && !empty($_FILES['product_images']['name'][0])) {
        $upload_dir = UPLOAD_PATH . '/';
        
        // Create upload directory if it doesn't exist
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $max_file_size = 5 * 1024 * 1024; // 5MB
        
        // Process each uploaded file
        for ($i = 0; $i < count($_FILES['product_images']['name']); $i++) {
            if ($_FILES['product_images']['error'][$i] === UPLOAD_ERR_OK) {
                $file_info = pathinfo($_FILES['product_images']['name'][$i]);
                $extension = strtolower($file_info['extension']);
                
                // Validate file type
                if (!in_array($extension, $allowed_extensions)) {
                    $_SESSION['error_message'] = 'Invalid file type. Only JPG, PNG, GIF, and WebP images are allowed.';
                    header('Location: ' . admin_url('products/edit?id=' . $product_id));
                    exit();
                }
                
                // Validate file size
                if ($_FILES['product_images']['size'][$i] > $max_file_size) {
                    $_SESSION['error_message'] = 'File size too large. Maximum size is 5MB per file.';
                    header('Location: ' . admin_url('products/edit?id=' . $product_id));
                    exit();
                }
                
                // Generate unique filename
                $filename = 'product_' . $product_id . '_' . time() . '_' . $i . '.' . $extension;
                
                // Move uploaded file
                if (move_uploaded_file($_FILES['product_images']['tmp_name'][$i], $upload_dir . $filename)) {
                    $uploaded_images[] = [
                        'filename' => $filename,
                        'alt_text' => $product_name . ' - Image ' . ($i + 1),
                        'sort_order' => $i + 1,
                        'is_primary' => $i === 0 ? 1 : 0
                    ];
                } else {
                    $_SESSION['error_message'] = 'Failed to upload image: ' . $_FILES['product_images']['name'][$i];
                    header('Location: ' . admin_url('products/edit?id=' . $product_id));
                    exit();
                }
            }
        }
    }
    
    // Calculate discount amount
    $discount_amount = $original_price > 0 ? $original_price - $price : 0;
    
    // Prepare update query
    $update_fields = [
        'product_name' => $product_name,
        'price' => $price,
        'original_price' => $original_price,
        'discount_percentage' => $discount_percentage,
        'discount_amount' => $discount_amount,
        'category_id' => $category_id,
        'sub_category_id' => $sub_category_id,
        'model_name' => $model_name,
        'model_number' => $model_number,
        'star_rating' => $star_rating,
        'energy_rating' => $energy_rating,
        'warranty_years' => $warranty_years,
        'warranty_compressor_5' => $warranty_compressor_5,
        'warranty_compressor_10' => $warranty_compressor_10,
        'warranty_pcb_5' => $warranty_pcb_5,
        'description' => $description,
        'show_on_homepage' => $show_on_homepage,
        'show_on_product_page' => $show_on_product_page,
        'updated_at' => date('Y-m-d H:i:s')
    ];
    
    // Add image path if new images were uploaded (for backward compatibility)
    if (!empty($uploaded_images)) {
        $update_fields['product_image'] = $uploaded_images[0]['filename'];
    }
    
    // Build SQL query
    $set_clauses = [];
    $params = [];
    
    foreach ($update_fields as $field => $value) {
        $set_clauses[] = "{$field} = ?";
        $params[] = $value;
    }
    
    $params[] = $product_id; // For WHERE clause
    
    $sql = "UPDATE products SET " . implode(', ', $set_clauses) . " WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute($params);
    
    if ($result) {
        // Insert new images into product_images table
        if (!empty($uploaded_images)) {
            $image_sql = "INSERT INTO product_images (product_id, image_filename, image_alt_text, sort_order, is_primary) VALUES (?, ?, ?, ?, ?)";
            $image_stmt = $pdo->prepare($image_sql);
            
            foreach ($uploaded_images as $image) {
                $image_stmt->execute([
                    $product_id,
                    $image['filename'],
                    $image['alt_text'],
                    $image['sort_order'],
                    $image['is_primary']
                ]);
            }
        }
        
        // Log the update action
        logSecurityEvent('product_updated', [
            'admin_id' => $_SESSION['admin_id'],
            'product_id' => $product_id,
            'product_name' => $product_name
        ]);
        
        $_SESSION['success_message'] = 'Product updated successfully!';
        header('Location: ' . admin_url('products'));
        exit();
    } else {
        throw new Exception('Failed to update product in database.');
    }
    
} catch (Exception $e) {
    error_log("Product update error: " . $e->getMessage());
    $_SESSION['error_message'] = 'An error occurred while updating the product. Please try again.';
    header('Location: ' . admin_url('products/edit?id=' . $product_id));
    exit();
}
?>
