<?php
require_once dirname(dirname(__DIR__)) . '/includes/config/init.php';

// Ensure the admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: ' . admin_url('login'));
    exit();
}

include INCLUDES_PATH . '/templates/admin_header.php';
    
    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $category_name = trim($_POST['category_name']);
        $description = trim($_POST['description']);
        $image_path = null;

        // Handle image upload
        if (isset($_FILES['category_image']) && $_FILES['category_image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = UPLOAD_PATH . '/categories/';
            
            // Create directory if it doesn't exist
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $file_extension = strtolower(pathinfo($_FILES['category_image']['name'], PATHINFO_EXTENSION));
            $allowed_extensions = ALLOWED_IMAGE_EXTENSIONS;
            
            if (in_array($file_extension, $allowed_extensions)) {
                $new_filename = 'category_' . time() . '_' . uniqid() . '.' . $file_extension;
                $upload_path = $upload_dir . $new_filename;
                
                if (move_uploaded_file($_FILES['category_image']['tmp_name'], $upload_path)) {
                    $image_path = UPLOAD_URL . '/categories/' . $new_filename;
                } else {
                    $error_message = "Failed to upload image. Please try again.";
                }
            } else {
                $error_message = "Invalid file type. Please upload JPG, PNG, GIF, or WebP images only.";
            }
        }

        if (!empty($category_name) && !isset($error_message)) {
            try {
                // Insert into categories table with correct column names
                $sql = "INSERT INTO categories (name, description, image, status) VALUES (:name, :description, :image, 'active')";
                $stmt = $pdo->prepare($sql);
                $success = $stmt->execute([
                    'name' => $category_name,
                    'description' => $description,
                    'image' => $image_path
                ]);

                if ($success) {
                    $success_message = "Category added successfully!";
                } else {
                    $error_message = "Error adding category. Please try again.";
                }
            } catch (PDOException $e) {
                $error_message = "Database error: " . $e->getMessage();
            }
        } elseif (empty($category_name)) {
            $error_message = "Category name is required.";
        }
    }
?>

<style>
    /* Custom CSS */
    body {
        background-color: #f8f9fa;
    }
    .container {
        background-color: #ffffff;
        padding: 30px;
        min-height: 80vh;
        width: 100%;
        max-width: 800px;
        border-radius: 8px;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        margin: 50px auto;
    }
    .form-label {
        color: #333;
        font-weight: 500;
    }
    .alert {
        margin-bottom: 20px;
    }
    .btn-primary {
        background-color: #007bff;
        border-color: #007bff;
        padding: 12px 30px;
    }
    .btn-secondary {
        padding: 12px 30px;
        margin-left: 10px;
    }
    .form-control {
        border: 1px solid #ced4da;
        border-radius: 5px;
        padding: 12px;
    }
    .form-control:focus {
        border-color: #007bff;
        box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
    }
</style>

<main>
    <div class="container">
        <h1 class="text-center mb-4">Add New Category</h1>
        
        <!-- Display success or error messages -->
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success" role="alert">
                <?php echo htmlspecialchars($success_message); ?>
                <br>
                <a href="category_management.php" class="btn btn-sm btn-outline-success mt-2">View All Categories</a>
            </div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger" role="alert">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <form action="" method="POST" enctype="multipart/form-data">
            <div class="mb-3">
                <label for="category_name" class="form-label">Category Name <span class="text-danger">*</span></label>
                <input type="text" 
                       class="form-control" 
                       name="category_name" 
                       id="category_name" 
                       placeholder="Enter category name (e.g., Residential AC, Commercial AC)" 
                       value="<?php echo isset($_POST['category_name']) ? htmlspecialchars($_POST['category_name']) : ''; ?>"
                       required>
                <small class="form-text text-muted">Choose a clear, descriptive name for the category</small>
            </div>
            
            <div class="mb-3">
                <label for="description" class="form-label">Description (Optional)</label>
                <textarea class="form-control" 
                          name="description" 
                          id="description" 
                          rows="3"
                          placeholder="Enter a brief description of this category"><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                <small class="form-text text-muted">Provide additional details about this category</small>
            </div>

            <div class="mb-4">
                <label for="category_image" class="form-label">Category Image (Optional)</label>
                <input type="file" 
                       class="form-control" 
                       name="category_image" 
                       id="category_image" 
                       accept="image/*">
                <small class="form-text text-muted">Upload an image for this category (JPG, PNG, GIF, WebP - Max 2MB)</small>
                <div id="image-preview" class="mt-2" style="display: none;">
                    <img id="preview-img" src="" alt="Preview" style="max-width: 200px; max-height: 150px; border-radius: 5px;">
                </div>
            </div>
            
            <div class="d-flex justify-content-between">
                <div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add Category
                    </button>
                    <a href="category_management.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Categories
                    </a>
                </div>
            </div>
        </form>

        <!-- Quick Actions -->
        <div class="mt-4 p-3 bg-light rounded">
            <h5>Quick Actions</h5>
            <div class="btn-group" role="group">
                <a href="add_subcategory.php" class="btn btn-outline-primary btn-sm">Add Subcategory</a>
                <a href="category_management.php" class="btn btn-outline-secondary btn-sm">Manage Categories</a>
                <a href="index.php" class="btn btn-outline-info btn-sm">Dashboard</a>
            </div>
        </div>
    </div>
</main>

<script>
document.getElementById('category_image').addEventListener('change', function(e) {
    const file = e.target.files[0];
    const preview = document.getElementById('image-preview');
    const previewImg = document.getElementById('preview-img');
    
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            previewImg.src = e.target.result;
            preview.style.display = 'block';
        };
        reader.readAsDataURL(file);
    } else {
        preview.style.display = 'none';
    }
});
</script>

<?php
include INCLUDES_PATH . '/templates/admin_footer.php';
?>
