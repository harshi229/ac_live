<?php
require_once dirname(dirname(__DIR__)) . '/includes/config/init.php';

// Ensure the admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: ' . admin_url('login'));
    exit();
}
require_once INCLUDES_PATH . '/middleware/admin_auth.php';
// Check if the category ID is set
if (!isset($_GET['id'])) {
    echo "No category ID provided.";
    exit();
}

// Fetch the category details
$category_id = $_GET['id'];
$stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
$stmt->execute([$category_id]);
$category = $stmt->fetch(PDO::FETCH_ASSOC);

// Check if the category exists
if (!$category) {
    echo "Category not found.";
    exit();
}

// Handle the form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_category_name = trim($_POST['category_name']);
    $new_description = trim($_POST['description']);
    $current_image = $category['image'];
    $new_image_path = $current_image;

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
                // Delete old image if it exists
                if ($current_image && file_exists(str_replace(UPLOAD_URL, UPLOAD_PATH, $current_image))) {
                    unlink(str_replace(UPLOAD_URL, UPLOAD_PATH, $current_image));
                }
                $new_image_path = UPLOAD_URL . '/categories/' . $new_filename;
            } else {
                $error_message = "Failed to upload image. Please try again.";
            }
        } else {
            $error_message = "Invalid file type. Please upload JPG, PNG, GIF, or WebP images only.";
        }
    }

    if (!empty($new_category_name) && !isset($error_message)) {
        try {
            // Update the category in the database
            $update_stmt = $pdo->prepare("UPDATE categories SET name = ?, description = ?, image = ? WHERE id = ?");
            $update_stmt->execute([$new_category_name, $new_description, $new_image_path, $category_id]);

            // Redirect after successful update
            header('Location: ' . admin_url('categories'));
            exit();
        } catch (PDOException $e) {
            $error_message = "Error updating category: " . $e->getMessage();
        }
    } elseif (empty($new_category_name)) {
        $error_message = "Category name is required.";
    }
}

// Include header after all redirect logic
include INCLUDES_PATH . '/templates/admin_header.php';
?>
<main>
    
<div class="container mt-5">
    <h1>Edit Category</h1>

    <?php if (isset($error_message)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
    <?php endif; ?>

    <form action="<?php echo admin_url('categories/edit?id=' . $category_id); ?>" method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <label for="category_name">Category Name</label>
            <input type="text" class="form-control" id="category_name" name="category_name" value="<?php echo htmlspecialchars($category['name'] ?? ''); ?>" required>
        </div>
        <div class="form-group">
            <label for="description">Description</label>
            <textarea class="form-control" id="description" name="description" rows="3"><?php echo htmlspecialchars($category['description'] ?? ''); ?></textarea>
        </div>
        <div class="form-group">
            <label for="category_image">Category Image</label>
            <?php if (!empty($category['image'])): ?>
                <div class="mb-2">
                    <img src="<?php echo htmlspecialchars($category['image']); ?>" alt="Current Image" style="max-width: 200px; max-height: 150px; border-radius: 5px;">
                    <p class="text-muted small">Current image</p>
                </div>
            <?php endif; ?>
            <input type="file" class="form-control" id="category_image" name="category_image" accept="image/*">
            <small class="form-text text-muted">Upload a new image to replace the current one (JPG, PNG, GIF, WebP - Max 2MB)</small>
            <div id="image-preview" class="mt-2" style="display: none;">
                <img id="preview-img" src="" alt="Preview" style="max-width: 200px; max-height: 150px; border-radius: 5px;">
                <p class="text-muted small">New image preview</p>
            </div>
        </div>
        <button type="submit" class="btn btn-success">Update Category</button>
        <a href="<?php echo admin_url('categories'); ?>" class="btn btn-secondary">Cancel</a>
    </form>
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
