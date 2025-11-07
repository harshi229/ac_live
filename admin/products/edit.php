<?php
require_once dirname(dirname(__DIR__)) . '/includes/config/init.php';
require_once INCLUDES_PATH . '/functions/security_helpers.php';

// Ensure the admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: ' . admin_url('login'));
    exit();
}

include INCLUDES_PATH . '/templates/admin_header.php';

// Get the product ID from the URL
$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch the existing product details from the database
$sql = "SELECT * FROM products WHERE id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$product_id]);
$product = $stmt->fetch();

if (!$product) {
    echo "<p>Product not found!</p>";
    exit;
}
?>
<style>
    /* Custom CSS */
    body {
        background-color: #f8f9fa;
    }
    .container {
        background-color: #ffffff;
        padding: 60px;
        border-radius: 8px;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
    }
    label {
        color: black;
    }
    
    /* Image display styling */
    .current-image-container {
        border: 2px dashed #dee2e6;
        padding: 15px;
        border-radius: 8px;
        background-color: #f8f9fa;
        text-align: center;
    }
    
    .current-image-container img {
        border-radius: 4px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    .new-image-upload {
        margin-top: 15px;
        padding-top: 15px;
        border-top: 1px solid #dee2e6;
    }
    
    .no-image .alert {
        margin-bottom: 0;
    }
</style>
<main>
<div class="container mt-5">
    <h1 class="text-center text-black">Edit Product</h1>
    <form action="<?php echo admin_url('products/update?id=' . $product_id); ?>" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
        
        <div class="mb-3">
            <label for="product_name" class="form-label">Product Name:</label>
            <input type="text" class="form-control" name="product_name" id="product_name" value="<?php echo htmlspecialchars($product['product_name'] ?? ''); ?>" required>
        </div>
        
        <!-- Pricing Section -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Pricing Information</h5>
            </div>
            <div class="card-body">
                <!-- Original Price -->
                <div class="mb-3">
                    <label for="original_price" class="form-label">Original Price (₹) <span class="required">*</span></label>
                    <input type="number" class="form-control" name="original_price" id="original_price" step="0.01" min="0" 
                           value="<?php echo htmlspecialchars($product['original_price'] ?? $product['price'] ?? ''); ?>" 
                           placeholder="e.g., 40000" required>
                    <div class="form-text">The actual price of the AC unit</div>
                </div>

                <!-- Selling Price -->
                <div class="mb-3">
                    <label for="price" class="form-label">Selling Price (₹) <span class="required">*</span></label>
                    <input type="number" class="form-control" name="price" id="price" step="0.01" min="0" 
                           value="<?php echo htmlspecialchars($product['price'] ?? ''); ?>" 
                           placeholder="e.g., 37000" required>
                    <div class="form-text">The price customers will pay (discounted price)</div>
                </div>

                <!-- Discount Percentage -->
                <div class="mb-3">
                    <label for="discount_percentage" class="form-label">Discount Percentage (%)</label>
                    <input type="number" class="form-control" name="discount_percentage" id="discount_percentage" step="0.01" min="0" max="100" 
                           value="<?php echo htmlspecialchars($product['discount_percentage'] ?? ''); ?>" 
                           placeholder="e.g., 7.5" readonly>
                    <div class="form-text">Automatically calculated based on original and selling price</div>
                </div>

                <!-- Discount Preview -->
                <div class="alert alert-info" id="discount-preview" style="display: none;">
                    <strong>Discount Preview:</strong>
                    <div id="discount-details"></div>
                </div>
            </div>
        </div>

        <div class="mb-3">
            <label for="stock" class="form-label">Stock:</label>
            <input type="number" class="form-control" name="stock" id="stock" value="<?php echo htmlspecialchars($product['stock'] ?? ''); ?>" required>
        </div>

        <div class="mb-3">
            <label for="category" class="form-label">Category:</label>
            <select class="form-select" name="category_id" id="category" required>
                <?php
                // Fetch categories from the database
                $sql = "SELECT * FROM categories";
                $stmt = $pdo->query($sql);
                $categories = $stmt->fetchAll();

                foreach ($categories as $category) {
                    $selected = $category['id'] == $product['category_id'] ? 'selected' : '';
                    echo '<option value="' . $category['id'] . '" ' . $selected . '>' . htmlspecialchars($category['name'] ?? '') . '</option>';
                }
                ?>
            </select>
        </div>

        <div class="mb-3">
            <label for="sub_category" class="form-label">Subcategory:</label>
            <select class="form-select" name="sub_category_id" id="sub_category" required>
                <?php
                // Fetch subcategories from the database
                $sql = "SELECT * FROM sub_categories";
                $stmt = $pdo->query($sql);
                $subcategories = $stmt->fetchAll();

                foreach ($subcategories as $subcategory) {
                    $selected = $subcategory['id'] == $product['sub_category_id'] ? 'selected' : '';
                    echo '<option value="' . $subcategory['id'] . '" ' . $selected . '>' . htmlspecialchars($subcategory['name'] ?? '') . '</option>';
                }
                ?>
            </select>
        </div>

        <div class="mb-3">
            <label for="model_name" class="form-label">Model Name:</label>
            <input type="text" class="form-control" name="model_name" id="model_name" value="<?php echo htmlspecialchars($product['model_name'] ?? ''); ?>" required>
        </div>

        <div class="mb-3">
            <label for="model_number" class="form-label">Model Number:</label>
            <input type="text" class="form-control" name="model_number" id="model_number" value="<?php echo htmlspecialchars($product['model_number'] ?? ''); ?>" required>
        </div>

        <div class="mb-3">
            <label for="energy_rating" class="form-label">Energy Rating:</label>
            <input type="text" class="form-control" name="energy_rating" id="energy_rating" 
                   value="<?php echo htmlspecialchars($product['energy_rating'] ?? ''); ?>" 
                   placeholder="e.g., 5 Star, 3 Star" required>
            <div class="form-text">Enter the energy efficiency rating (e.g., 5 Star, 3 Star)</div>
        </div>

        <div class="mb-3">
            <label for="installation" class="form-label">Installation:</label>
            <select class="form-select" name="installation" id="installation" required>
                <option value="">Select Installation</option>
                <option value="Yes" <?= ($product['installation'] ?? '') == 'Yes' ? 'selected' : '' ?>>Yes - Included</option>
                <option value="No" <?= ($product['installation'] ?? '') == 'No' ? 'selected' : '' ?>>No - Not Included</option>
            </select>
        </div>
        
        <div class="mb-3">
            <label class="form-label">Product Images:</label>
            
            <!-- Display current images -->
            <?php
            // Get existing images from the new table
            $images_stmt = $pdo->prepare("
                SELECT image_filename, image_alt_text, sort_order, is_primary
                FROM product_images 
                WHERE product_id = ? 
                ORDER BY sort_order ASC, id ASC
            ");
            $images_stmt->execute([$product_id]);
            $existing_images = $images_stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // If no images in new table, show old product_image
            if (empty($existing_images) && !empty($product['product_image'])) {
                $existing_images = [[
                    'image_filename' => $product['product_image'],
                    'image_alt_text' => $product['product_name'] . ' - Main Image',
                    'sort_order' => 1,
                    'is_primary' => 1
                ]];
            }
            ?>
            
            <?php if (!empty($existing_images)): ?>
                <div class="current-images mb-3">
                    <label class="form-label">Current Images:</label>
                    <div class="row">
                        <?php foreach ($existing_images as $index => $image): ?>
                            <div class="col-md-3 mb-2">
                                <div class="current-image-container position-relative">
                                    <img src="<?= BASE_URL ?>/public/image.php?file=<?= urlencode($image['image_filename']) ?>" 
                                         alt="<?= htmlspecialchars($image['image_alt_text']) ?>" 
                                         class="img-thumbnail" 
                                         style="width: 100%; height: 150px; object-fit: cover;"
                                         onerror="this.src='<?= IMG_URL ?>/no-image.png'">
                                    
                                    <!-- Action buttons -->
                                    <div class="position-absolute" style="top: 5px; right: 5px; z-index: 10;">
                                        <!-- Remove button -->
                                        <button type="button" 
                                                class="btn btn-danger btn-sm me-1" 
                                                onclick="removeImage(<?= $product_id ?>, '<?= htmlspecialchars($image['image_filename']) ?>', this)"
                                                title="Remove this image">
                                            <i class="fas fa-times"></i>
                                        </button>
                                        
                                        <!-- Set as Primary button (only for non-primary images) -->
                                        <?php if (!$image['is_primary']): ?>
                                        <button type="button" 
                                                class="btn btn-primary btn-sm" 
                                                onclick="setAsPrimary(<?= $product_id ?>, '<?= htmlspecialchars($image['image_filename']) ?>', this)"
                                                title="Set as primary image">
                                            <i class="fas fa-star"></i>
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="mt-2 text-center">
                                        <small class="text-muted">
                                            <?= $image['is_primary'] ? 'Primary' : 'Secondary' ?> Image
                                        </small>
                                        <br>
                                        <small class="text-muted"><?= htmlspecialchars($image['image_filename']) ?></small>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="no-image mb-3">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> No images currently uploaded for this product.
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- File input for new images -->
            <div class="new-image-upload">
                <label for="product_images" class="form-label">Add More Images (optional):</label>
                <input type="file" class="form-control" name="product_images[]" id="product_images" accept="image/*" multiple>
                <small class="form-text text-muted">
                    Select multiple images to add to this product. First image will be the primary image.
                </small>
                <div id="image-preview" class="mt-3"></div>
            </div>
        </div>
        
        <div class="mb-3">
            <label for="description" class="form-label">Description:</label>
            <textarea class="form-control" name="description" id="description" rows="4" required><?php echo htmlspecialchars($product['description'] ?? ''); ?></textarea>
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
                               <?= (isset($product['show_on_homepage']) && $product['show_on_homepage']) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="show_on_homepage">
                            <strong>Show on Home Page</strong>
                        </label>
                        <small class="form-text text-muted d-block">Check this to display this product on the homepage</small>
                    </div>
                </div>
                <div class="mb-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="show_on_product_page" id="show_on_product_page" value="1"
                               <?= (isset($product['show_on_product_page']) && $product['show_on_product_page']) ? 'checked' : (isset($product['show_on_product_page']) ? '' : 'checked') ?>>
                        <label class="form-check-label" for="show_on_product_page">
                            <strong>Show on Product Page</strong>
                        </label>
                        <small class="form-text text-muted d-block">Check this to display this product on the products listing page</small>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Update Product
            </button>
            <a href="<?php echo admin_url('products'); ?>" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Cancel
            </a>
        </div>
    </form>
</div>
</main>

<script>
// Set image as primary functionality
function setAsPrimary(productId, imageFilename, buttonElement) {
    if (confirm('Set this image as the primary image?')) {
        // Show loading state
        const originalContent = buttonElement.innerHTML;
        buttonElement.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        buttonElement.disabled = true;
        
        // Create form data
        const formData = new FormData();
        formData.append('product_id', productId);
        formData.append('image_filename', imageFilename);
        formData.append('csrf_token', '<?= generateCSRFToken() ?>');
        
        // Send AJAX request
        fetch('<?= admin_url('products/set_primary') ?>', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Reload the page to show updated primary image
                location.reload();
            } else {
                // Show error message
                showAlert('danger', data.message);
                
                // Restore button
                buttonElement.innerHTML = originalContent;
                buttonElement.disabled = false;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('danger', 'An error occurred while setting primary image.');
            
            // Restore button
            buttonElement.innerHTML = originalContent;
            buttonElement.disabled = false;
        });
    }
}

// Image removal functionality
function removeImage(productId, imageFilename, buttonElement) {
    if (confirm('Are you sure you want to remove this image? This action cannot be undone.')) {
        // Show loading state
        const originalContent = buttonElement.innerHTML;
        buttonElement.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        buttonElement.disabled = true;
        
        // Create form data
        const formData = new FormData();
        formData.append('product_id', productId);
        formData.append('image_filename', imageFilename);
        formData.append('csrf_token', '<?= generateCSRFToken() ?>');
        
        // Send AJAX request
        fetch('<?= admin_url('products/remove_image') ?>', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Remove the image container from the page
                const imageContainer = buttonElement.closest('.col-md-3');
                imageContainer.remove();
                
                // Show success message
                showAlert('success', data.message);
                
                // If we removed the primary image, show info about new primary
                if (data.was_primary) {
                    setTimeout(() => {
                        showAlert('info', 'A new primary image has been automatically selected.');
                    }, 1000);
                }
            } else {
                // Show error message
                showAlert('danger', data.message);
                
                // Restore button
                buttonElement.innerHTML = originalContent;
                buttonElement.disabled = false;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('danger', 'An error occurred while removing the image.');
            
            // Restore button
            buttonElement.innerHTML = originalContent;
            buttonElement.disabled = false;
        });
    }
}

// Alert function
function showAlert(type, message) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    // Insert at the top of the form
    const form = document.querySelector('form');
    form.insertBefore(alertDiv, form.firstChild);
    
    // Auto-remove after 5 seconds
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.remove();
        }
    }, 5000);
}

// Image preview functionality for edit form
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
                title.textContent = `New Image ${index + 1}`;
                
                const badge = document.createElement('span');
                badge.className = 'badge bg-success';
                badge.textContent = index === 0 ? 'Will be Primary' : 'Secondary';
                
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
                            <strong>Original Price:</strong> ₹${originalPrice.toLocaleString()}
                        </div>
                        <div class="col-md-6">
                            <strong>Selling Price:</strong> ₹${sellingPrice.toLocaleString()}
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
                alert('Selling price cannot be higher than original price!');
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
    
    // Calculate discount on page load if values exist
    calculateDiscount();
});
</script>

<?php
include INCLUDES_PATH . '/templates/admin_footer.php';
?>

