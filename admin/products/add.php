<?php
require_once dirname(dirname(__DIR__)) . '/includes/config/init.php';
require_once INCLUDES_PATH . '/functions/security_helpers.php';

// Ensure the admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

include INCLUDES_PATH . '/templates/admin_header.php';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF Protection
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        $error_message = "Invalid request. Please try again.";
        logSecurityEvent('CSRF_TOKEN_INVALID', ['admin_id' => $_SESSION['admin_id'], 'action' => 'add_product']);
    } else {
    // Get and sanitize form data
    $brand_id = intval($_POST['brand_id']);
    $category_id = intval($_POST['category_id']);
    $sub_category_id = intval($_POST['sub_category_id']);
    $product_name = trim($_POST['product_name']);
    $model_name = trim($_POST['model_name']);
    $model_number = trim($_POST['model_number']);
    $inverter = $_POST['inverter'];
    $star_rating = intval($_POST['star_rating']);
    $energy_rating = trim($_POST['energy_rating']);
    $capacity = trim($_POST['capacity']);
    $price = floatval($_POST['price']);
    $original_price = floatval($_POST['original_price']);
    $discount_percentage = floatval($_POST['discount_percentage']);
    $warranty_years = intval($_POST['warranty_years']);
    $amc_available = isset($_POST['amc_available']) ? 1 : 0;
    $show_on_homepage = isset($_POST['show_on_homepage']) ? 1 : 0;
    $show_on_product_page = isset($_POST['show_on_product_page']) ? 1 : 0;
    $description = trim($_POST['description']);
    $selected_features = isset($_POST['features']) ? $_POST['features'] : [];

    // Handle multiple file uploads
    $product_images = $_FILES['product_images'];
    $uploaded_images = [];
    $upload_success = false;
    $image_name = '';

    // Check if any images were uploaded
    if (!empty($product_images['name'][0])) {
        $target_dir = UPLOAD_PATH . "/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0755, true);
        }
        
        $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
        $max_file_size = 5000000; // 5MB
        
        // Process each uploaded file
        for ($i = 0; $i < count($product_images['name']); $i++) {
            if ($product_images['error'][$i] === 0) {
                $imageFileType = strtolower(pathinfo($product_images['name'][$i], PATHINFO_EXTENSION));
                
                if (in_array($imageFileType, $allowed_types) && $product_images['size'][$i] < $max_file_size) {
                    $image_name = time() . '_' . $i . '_' . basename($product_images["name"][$i]);
                    $target_file = $target_dir . $image_name;
                    
                    if (move_uploaded_file($product_images["tmp_name"][$i], $target_file)) {
                        $uploaded_images[] = [
                            'filename' => $image_name,
                            'alt_text' => $product_name . ' - Image ' . ($i + 1),
                            'sort_order' => $i + 1,
                            'is_primary' => $i === 0 ? 1 : 0
                        ];
                        $upload_success = true;
                        
                        // Set the first image as the main product image for backward compatibility
                        if ($i === 0) {
                            $image_name = $image_name;
                        }
                    }
                } else {
                    $error_message = "Invalid file type or file too large. Please use JPG, JPEG, PNG, or GIF files under 5MB.";
                    break;
                }
            }
        }
    } else {
        $error_message = "Please upload at least one product image.";
    }

    if (!isset($error_message)) {
        if ($upload_success || empty($product_image['name'])) {
            try {
                $pdo->beginTransaction();

                // Calculate discount amount
                $discount_amount = $original_price > 0 ? $original_price - $price : 0;
                
                // Insert product
                $sql = "INSERT INTO products (brand_id, category_id, sub_category_id, product_name, model_name, model_number, inverter, star_rating, energy_rating, capacity, price, original_price, discount_percentage, discount_amount, stock, warranty_years, installation, amc_available, description, product_image, status, show_on_homepage, show_on_product_page) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, ?, 'No', ?, ?, ?, 'active', ?, ?)";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    $brand_id, $category_id, $sub_category_id, $product_name, $model_name, 
                    $model_number, $inverter, $star_rating, $energy_rating, $capacity, $price, 
                    $original_price, $discount_percentage, $discount_amount, 
                    $warranty_years, $amc_available, $description, $image_name,
                    $show_on_homepage, $show_on_product_page
                ]);

                $product_id = $pdo->lastInsertId();

                // Insert product features
                if (!empty($selected_features)) {
                    $feature_sql = "INSERT INTO product_features (product_id, feature_id) VALUES (?, ?)";
                    $feature_stmt = $pdo->prepare($feature_sql);
                    
                    foreach ($selected_features as $feature_id) {
                        $feature_stmt->execute([$product_id, intval($feature_id)]);
                    }
                }

                // Insert product images into the new table
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

                $pdo->commit();
                $success_message = "Product added successfully!";
                
                // Clear form data on success
                $_POST = [];
                
            } catch (PDOException $e) {
                $pdo->rollback();
                $error_message = "Database error: " . $e->getMessage();
            }
        } else {
            $error_message = "Error uploading image. Please try again.";
        }
    }
    }
}

// Fetch data for dropdowns
try {
    $brands = $pdo->query("SELECT * FROM brands WHERE status = 'active' ORDER BY name")->fetchAll();
    $categories = $pdo->query("SELECT * FROM categories WHERE status = 'active' ORDER BY name")->fetchAll();
    $subcategories = $pdo->query("SELECT * FROM sub_categories WHERE status = 'active' ORDER BY name")->fetchAll();
    $features = $pdo->query("SELECT * FROM features ORDER BY name")->fetchAll();
} catch (PDOException $e) {
    $error_message = "Error loading form data: " . $e->getMessage();
}
?>

<style>
    body {
        background-color: #f8f9fa;
    }
    .container {
        background-color: #ffffff;
        padding: 40px;
        border-radius: 8px;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        margin: 30px auto;
        max-width: 900px;
    }
    .form-label {
        color: #333;
        font-weight: 500;
    }
    .required {
        color: #dc3545;
    }
    .form-control, .form-select {
        border: 1px solid #ced4da;
        border-radius: 5px;
        padding: 10px;
    }
    .form-control:focus, .form-select:focus {
        border-color: #007bff;
        box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
    }
    .feature-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 10px;
        max-height: 200px;
        overflow-y: auto;
        border: 1px solid #ced4da;
        border-radius: 5px;
        padding: 15px;
        background-color: #f8f9fa;
    }
    .form-check {
        margin-bottom: 8px;
    }
    .alert {
        margin-bottom: 20px;
    }
    .btn-group {
        gap: 10px;
    }
</style>

<main>
    <div class="container">
        <h1 class="text-center text-dark mb-4">Add New Product</h1>
        
        <!-- Display messages -->
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success" role="alert">
                <?php echo htmlspecialchars($success_message); ?>
                <br><a href="index.php" class="btn btn-sm btn-outline-success mt-2">View All Products</a>
            </div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger" role="alert">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <form action="" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            <div class="row">
                <!-- Left Column -->
                <div class="col-md-6">
                    <!-- Brand Selection -->
                    <div class="mb-3">
                        <label for="brand_id" class="form-label">Brand <span class="required">*</span></label>
                        <select class="form-select" name="brand_id" id="brand_id" required>
                            <option value="">Select Brand</option>
                            <?php foreach ($brands as $brand): ?>
                                <option value="<?= $brand['id'] ?>" <?= (isset($_POST['brand_id']) && $_POST['brand_id'] == $brand['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($brand['name'] ?? '') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Category Selection -->
                    <div class="mb-3">
                        <label for="category_id" class="form-label">Category <span class="required">*</span></label>
                        <select class="form-select" name="category_id" id="category_id" required>
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= $category['id'] ?>" <?= (isset($_POST['category_id']) && $_POST['category_id'] == $category['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($category['name'] ?? '') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Subcategory Selection -->
                    <div class="mb-3">
                        <label for="sub_category_id" class="form-label">Subcategory <span class="required">*</span></label>
                        <select class="form-select" name="sub_category_id" id="sub_category_id" required>
                            <option value="">Select Subcategory</option>
                            <?php foreach ($subcategories as $subcategory): ?>
                                <option value="<?= $subcategory['id'] ?>" <?= (isset($_POST['sub_category_id']) && $_POST['sub_category_id'] == $subcategory['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($subcategory['name'] ?? '') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Product Name -->
                    <div class="mb-3">
                        <label for="product_name" class="form-label">Product Name <span class="required">*</span></label>
                        <input type="text" class="form-control" name="product_name" id="product_name" 
                               value="<?= isset($_POST['product_name']) ? htmlspecialchars($_POST['product_name']) : '' ?>" 
                               placeholder="e.g., Hitachi 5 Star Inverter Split AC" required>
                    </div>

                    <!-- Model Name -->
                    <div class="mb-3">
                        <label for="model_name" class="form-label">Model Name <span class="required">*</span></label>
                        <input type="text" class="form-control" name="model_name" id="model_name" 
                               value="<?= isset($_POST['model_name']) ? htmlspecialchars($_POST['model_name']) : '' ?>" 
                               placeholder="e.g., Yoshi 5600XXL" required>
                    </div>

                    <!-- Model Number -->
                    <div class="mb-3">
                        <label for="model_number" class="form-label">Model Number <span class="required">*</span></label>
                        <input type="text" class="form-control" name="model_number" id="model_number" 
                               value="<?= isset($_POST['model_number']) ? htmlspecialchars($_POST['model_number']) : '' ?>" 
                               placeholder="e.g., RAS.Y518PCAISL1" required>
                    </div>
                </div>

                <!-- Right Column -->
                <div class="col-md-6">
                    <!-- Inverter Type -->
                    <div class="mb-3">
                        <label for="inverter" class="form-label">Inverter Type <span class="required">*</span></label>
                        <select class="form-select" name="inverter" id="inverter" required>
                            <option value="Yes" <?= (isset($_POST['inverter']) && $_POST['inverter'] == 'Yes') ? 'selected' : '' ?>>Inverter</option>
                            <option value="No" <?= (isset($_POST['inverter']) && $_POST['inverter'] == 'No') ? 'selected' : '' ?>>Non-Inverter</option>
                        </select>
                    </div>

                    <!-- Star Rating -->
                    <div class="mb-3">
                        <label for="star_rating" class="form-label">Star Rating <span class="required">*</span></label>
                        <select class="form-select" name="star_rating" id="star_rating" required>
                            <option value="">Select Rating</option>
                            <option value="3" <?= (isset($_POST['star_rating']) && $_POST['star_rating'] == '3') ? 'selected' : '' ?>>3 Star</option>
                            <option value="5" <?= (isset($_POST['star_rating']) && $_POST['star_rating'] == '5') ? 'selected' : '' ?>>5 Star</option>
                        </select>
                    </div>

                    <!-- Energy Rating -->
                    <div class="mb-3">
                        <label for="energy_rating" class="form-label">Energy Rating <span class="required">*</span></label>
                        <input type="text" class="form-control" name="energy_rating" id="energy_rating" 
                               value="<?= htmlspecialchars($_POST['energy_rating'] ?? '') ?>" 
                               placeholder="e.g., 5 Star, 3 Star" required>
                        <div class="form-text">Enter the energy efficiency rating (e.g., 5 Star, 3 Star)</div>
                    </div>

                    <!-- Capacity -->
                    <div class="mb-3">
                        <label for="capacity" class="form-label">Capacity <span class="required">*</span></label>
                        <select class="form-select" name="capacity" id="capacity" required>
                            <option value="">Select Capacity</option>
                            <option value="1 Ton" <?= (isset($_POST['capacity']) && $_POST['capacity'] == '1 Ton') ? 'selected' : '' ?>>1 Ton</option>
                            <option value="1.5 Ton" <?= (isset($_POST['capacity']) && $_POST['capacity'] == '1.5 Ton') ? 'selected' : '' ?>>1.5 Ton</option>
                            <option value="2 Ton" <?= (isset($_POST['capacity']) && $_POST['capacity'] == '2 Ton') ? 'selected' : '' ?>>2 Ton</option>
                            <option value="3 Ton" <?= (isset($_POST['capacity']) && $_POST['capacity'] == '3 Ton') ? 'selected' : '' ?>>3 Ton</option>
                            <option value="5 Ton" <?= (isset($_POST['capacity']) && $_POST['capacity'] == '5 Ton') ? 'selected' : '' ?>>5 Ton</option>
                        </select>
                    </div>

                    <!-- Pricing Section -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Pricing Information</h5>
                        </div>
                        <div class="card-body">
                            <!-- MRP -->
                            <div class="mb-3">
                                <label for="original_price" class="form-label">MRP (₹) <span class="required">*</span></label>
                                <input type="number" class="form-control" name="original_price" id="original_price" step="0.01" min="0" 
                                       value="<?= isset($_POST['original_price']) ? htmlspecialchars($_POST['original_price']) : '' ?>" 
                                       placeholder="e.g., 40000" required>
                                <div class="form-text">The MRP of the AC unit</div>
                            </div>

                            <!-- Special Price -->
                            <div class="mb-3">
                                <label for="price" class="form-label">Special Price (₹) <span class="required">*</span></label>
                                <input type="number" class="form-control" name="price" id="price" step="0.01" min="0" 
                                       value="<?= isset($_POST['price']) ? htmlspecialchars($_POST['price']) : '' ?>" 
                                       placeholder="e.g., 37000" required>
                                <div class="form-text">The special price customers will pay</div>
                            </div>

                            <!-- Discount Percentage -->
                            <div class="mb-3">
                                <label for="discount_percentage" class="form-label">Discount Percentage (%)</label>
                                <input type="number" class="form-control" name="discount_percentage" id="discount_percentage" step="0.01" min="0" max="100" 
                                       value="<?= isset($_POST['discount_percentage']) ? htmlspecialchars($_POST['discount_percentage']) : '' ?>" 
                                       placeholder="e.g., 7.5" readonly>
                                <div class="form-text">Automatically calculated based on MRP and special price</div>
                            </div>

                            <!-- Discount Preview -->
                            <div class="alert alert-info" id="discount-preview" style="display: none;">
                                <strong>Discount Preview:</strong>
                                <div id="discount-details"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Warranty -->
                    <div class="mb-3">
                        <label for="warranty_years" class="form-label">Warranty (Years) <span class="required">*</span></label>
                        <select class="form-select" name="warranty_years" id="warranty_years" required>
                            <option value="">Select Warranty</option>
                            <option value="1" <?= (isset($_POST['warranty_years']) && $_POST['warranty_years'] == '1') ? 'selected' : '' ?>>1 Year</option>
                            <option value="2" <?= (isset($_POST['warranty_years']) && $_POST['warranty_years'] == '2') ? 'selected' : '' ?>>2 Years</option>
                            <option value="3" <?= (isset($_POST['warranty_years']) && $_POST['warranty_years'] == '3') ? 'selected' : '' ?>>3 Years</option>
                            <option value="5" <?= (isset($_POST['warranty_years']) && $_POST['warranty_years'] == '5') ? 'selected' : '' ?>>5 Years</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Full Width Fields -->
            <!-- Features Selection -->
            <div class="mb-3">
                <label class="form-label">Features (Select applicable features)</label>
                <div class="feature-grid">
                    <?php foreach ($features as $feature): ?>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="features[]" 
                                   value="<?= $feature['id'] ?>" id="feature_<?= $feature['id'] ?>"
                                   <?= (isset($_POST['features']) && in_array($feature['id'], $_POST['features'])) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="feature_<?= $feature['id'] ?>">
                                <?= htmlspecialchars($feature['name']) ?>
                            </label>
                            <?php if ($feature['description']): ?>
                                <small class="text-muted d-block"><?= htmlspecialchars($feature['description']) ?></small>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- AMC Available -->
            <div class="mb-3">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="amc_available" id="amc_available" value="1"
                           <?= (isset($_POST['amc_available']) && $_POST['amc_available']) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="amc_available">
                        AMC (Annual Maintenance Contract) Available
                    </label>
                </div>
            </div>

            <!-- Display Options -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Display Options</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="show_on_homepage" id="show_on_homepage" value="1"
                                   <?= (isset($_POST['show_on_homepage']) && $_POST['show_on_homepage']) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="show_on_homepage">
                                <strong>Show on Home Page</strong>
                            </label>
                            <small class="form-text text-muted d-block">Check this to display this product on the homepage</small>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="show_on_product_page" id="show_on_product_page" value="1"
                                   <?= (isset($_POST['show_on_product_page']) && $_POST['show_on_product_page']) ? 'checked' : (isset($_POST['show_on_product_page']) ? '' : 'checked') ?>>
                            <label class="form-check-label" for="show_on_product_page">
                                <strong>Show on Product Page</strong>
                            </label>
                            <small class="form-text text-muted d-block">Check this to display this product on the products listing page</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Product Images -->
            <div class="mb-3">
                <label for="product_images" class="form-label">Product Images <span class="required">*</span></label>
                <input type="file" class="form-control" name="product_images[]" id="product_images" accept="image/*" multiple required>
                <small class="form-text text-muted">Upload multiple JPG, JPEG, PNG, or GIF files. Max size: 5MB per file. First image will be the primary image.</small>
                <div id="image-preview" class="mt-3"></div>
            </div>

            <!-- Description -->
            <div class="mb-4">
                <label for="description" class="form-label">Description <span class="required">*</span></label>
                <textarea class="form-control" name="description" id="description" rows="4" 
                          placeholder="Enter detailed product description including key features and benefits" required><?= isset($_POST['description']) ? htmlspecialchars($_POST['description']) : '' ?></textarea>
            </div>

            <!-- Submit Buttons -->
            <div class="d-flex justify-content-between">
                <div class="btn-group">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="fas fa-plus"></i> Add Product
                    </button>
                    <a href="index.php" class="btn btn-secondary btn-lg">
                        <i class="fas fa-arrow-left"></i> Back to Products
                    </a>
                </div>
            </div>
        </form>
    </div>
</main>

<script>
// Image preview functionality
document.getElementById('product_images').addEventListener('change', function(e) {
    const preview = document.getElementById('image-preview');
    preview.innerHTML = '';
    
    if (e.target.files.length > 0) {
        const row = document.createElement('div');
        row.className = 'row';
        
        Array.from(e.target.files).forEach((file, index) => {
            if (file.type.startsWith('image/')) {
                const col = document.createElement('div');
                col.className = 'col-md-3 mb-2';
                
                const card = document.createElement('div');
                card.className = 'card';
                
                const img = document.createElement('img');
                img.className = 'card-img-top';
                img.style.height = '150px';
                img.style.objectFit = 'cover';
                
                const reader = new FileReader();
                reader.onload = function(e) {
                    img.src = e.target.result;
                };
                reader.readAsDataURL(file);
                
                const cardBody = document.createElement('div');
                cardBody.className = 'card-body p-2';
                
                const title = document.createElement('h6');
                title.className = 'card-title small';
                title.textContent = `Image ${index + 1}`;
                
                const badge = document.createElement('span');
                badge.className = 'badge bg-primary';
                badge.textContent = index === 0 ? 'Primary' : 'Secondary';
                
                cardBody.appendChild(title);
                cardBody.appendChild(badge);
                card.appendChild(img);
                card.appendChild(cardBody);
                col.appendChild(card);
                row.appendChild(col);
            }
        });
        
        preview.appendChild(row);
    }
});

// Discount calculation functionality
document.addEventListener('DOMContentLoaded', function() {
    const originalPriceInput = document.getElementById('original_price');
    const sellingPriceInput = document.getElementById('price');
    const discountPercentageInput = document.getElementById('discount_percentage');
    const discountPreview = document.getElementById('discount-preview');
    const discountDetails = document.getElementById('discount-details');

    function calculateDiscount() {
        const originalPrice = parseFloat(originalPriceInput.value) || 0;
        const sellingPrice = parseFloat(sellingPriceInput.value) || 0;
        
        if (originalPrice > 0 && sellingPrice > 0) {
            if (sellingPrice < originalPrice) {
                const discountAmount = originalPrice - sellingPrice;
                const discountPercentage = ((discountAmount / originalPrice) * 100).toFixed(2);
                
                discountPercentageInput.value = discountPercentage;
                
                // Show discount preview
                discountDetails.innerHTML = `
                    <div class="row">
                        <div class="col-md-6">
                            <strong>MRP:</strong> ₹${originalPrice.toLocaleString()}
                        </div>
                        <div class="col-md-6">
                            <strong>Special Price:</strong> ₹${sellingPrice.toLocaleString()}
                        </div>
                        <div class="col-md-6">
                            <strong>Discount Amount:</strong> ₹${discountAmount.toLocaleString()}
                        </div>
                        <div class="col-md-6">
                            <strong>Discount Percentage:</strong> ${discountPercentage}%
                        </div>
                    </div>
                `;
                discountPreview.style.display = 'block';
            } else if (sellingPrice === originalPrice) {
                discountPercentageInput.value = '0';
                discountDetails.innerHTML = '<div class="text-muted">No discount applied</div>';
                discountPreview.style.display = 'block';
            } else {
                discountPercentageInput.value = '0';
                discountPreview.style.display = 'none';
                alert('Special price cannot be higher than MRP!');
                sellingPriceInput.value = originalPrice;
            }
        } else {
            discountPercentageInput.value = '';
            discountPreview.style.display = 'none';
        }
    }

    // Add event listeners
    originalPriceInput.addEventListener('input', calculateDiscount);
    sellingPriceInput.addEventListener('input', calculateDiscount);
});
</script>

<?php
include INCLUDES_PATH . '/templates/admin_footer.php';
?>
