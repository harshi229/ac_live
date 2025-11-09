<?php
require_once dirname(dirname(__DIR__)) . '/includes/config/init.php';

// Ensure the admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: ' . admin_url('login'));
    exit();
}

include INCLUDES_PATH . '/templates/admin_header.php';

// Handle form submissions safely
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';

    switch ($action) {
        case 'add_brand':
            $brand_name = isset($_POST['brand_name']) ? trim($_POST['brand_name']) : '';
            $description = isset($_POST['description']) ? trim($_POST['description']) : '';
            $logo_path = null;

            // Handle logo upload
            if (isset($_FILES['brand_logo']) && $_FILES['brand_logo']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = UPLOAD_PATH . '/brands/';
                
                // Create directory if it doesn't exist
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                $file_extension = strtolower(pathinfo($_FILES['brand_logo']['name'], PATHINFO_EXTENSION));
                $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                
                if (in_array($file_extension, $allowed_extensions)) {
                    // Check file size (max 5MB)
                    if ($_FILES['brand_logo']['size'] <= 5242880) {
                        $new_filename = 'brand_' . time() . '_' . uniqid() . '.' . $file_extension;
                        $upload_path = $upload_dir . $new_filename;
                        
                        if (move_uploaded_file($_FILES['brand_logo']['tmp_name'], $upload_path)) {
                            $logo_path = 'brands/' . $new_filename;
                        } else {
                            $error_message = "Failed to upload logo. Please try again.";
                        }
                    } else {
                        $error_message = "Logo file is too large. Maximum size is 5MB.";
                    }
                } else {
                    $error_message = "Invalid file type. Please upload JPG, PNG, GIF, or WebP images only.";
                }
            }

            if (!empty($brand_name) && !isset($error_message)) {
                try {
                    // Check if brand already exists
                    $check_stmt = $pdo->prepare("SELECT id FROM brands WHERE name = ?");
                    $check_stmt->execute([$brand_name]);

                    if ($check_stmt->rowCount() > 0) {
                        $error_message = "Brand '$brand_name' already exists!";
                    } else {
                        $insert_stmt = $pdo->prepare("INSERT INTO brands (name, description, logo, status, created_at) VALUES (?, ?, ?, 'active', NOW())");
                        $insert_stmt->execute([$brand_name, $description, $logo_path]);
                        $success_message = "Brand '$brand_name' added successfully!";
                    }
                } catch (PDOException $e) {
                    $error_message = "Database error: " . $e->getMessage();
                }
            } elseif (empty($brand_name)) {
                $error_message = "Brand name is required!";
            }
            break;

        case 'toggle_status':
            $brand_id = isset($_POST['brand_id']) ? intval($_POST['brand_id']) : 0;
            $new_status = (isset($_POST['new_status']) && $_POST['new_status'] === 'active') ? 'active' : 'inactive';

            if ($brand_id > 0) {
                try {
                    $update_stmt = $pdo->prepare("UPDATE brands SET status = ? WHERE id = ?");
                    $update_stmt->execute([$new_status, $brand_id]);
                    $success_message = "Brand status updated successfully!";
                } catch (PDOException $e) {
                    $error_message = "Error updating brand status: " . $e->getMessage();
                }
            }
            break;

        case 'edit_brand':
            $brand_id = isset($_POST['brand_id']) ? intval($_POST['brand_id']) : 0;
            $brand_name = isset($_POST['brand_name']) ? trim($_POST['brand_name']) : '';
            $description = isset($_POST['description']) ? trim($_POST['description']) : '';
            $logo_path = null;

            if ($brand_id > 0 && !empty($brand_name)) {
                try {
                    // Check if brand exists
                    $check_exists = $pdo->prepare("SELECT id, logo FROM brands WHERE id = ?");
                    $check_exists->execute([$brand_id]);
                    $existing_brand = $check_exists->fetch(PDO::FETCH_ASSOC);
                    
                    if ($existing_brand) {
                        // Handle logo upload if provided
                        if (isset($_FILES['brand_logo']) && $_FILES['brand_logo']['error'] === UPLOAD_ERR_OK) {
                            $upload_dir = UPLOAD_PATH . '/brands/';
                            
                            // Create directory if it doesn't exist
                            if (!file_exists($upload_dir)) {
                                mkdir($upload_dir, 0755, true);
                            }
                            
                            $file_extension = strtolower(pathinfo($_FILES['brand_logo']['name'], PATHINFO_EXTENSION));
                            $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                            
                            if (in_array($file_extension, $allowed_extensions)) {
                                // Check file size (max 5MB)
                                if ($_FILES['brand_logo']['size'] <= 5242880) {
                                    // Get old logo path to delete it later
                                    $old_logo = $existing_brand['logo'];
                                    
                                    $new_filename = 'brand_' . $brand_id . '_' . time() . '_' . uniqid() . '.' . $file_extension;
                                    $upload_path = $upload_dir . $new_filename;
                                    
                                    if (move_uploaded_file($_FILES['brand_logo']['tmp_name'], $upload_path)) {
                                        $logo_path = 'brands/' . $new_filename;
                                        
                                        // Delete old logo if exists
                                        if (!empty($old_logo) && file_exists(UPLOAD_PATH . '/' . $old_logo)) {
                                            @unlink(UPLOAD_PATH . '/' . $old_logo);
                                        }
                                    } else {
                                        $error_message = "Failed to upload logo. Please try again.";
                                    }
                                } else {
                                    $error_message = "Logo file is too large. Maximum size is 5MB.";
                                }
                            } else {
                                $error_message = "Invalid file type. Please upload JPG, PNG, GIF, or WebP images only.";
                            }
                        }
                        
                        // If no error with logo upload, proceed with brand update
                        if (!isset($error_message)) {
                            // Check if new name conflicts with another brand
                            $check_name = $pdo->prepare("SELECT id FROM brands WHERE name = ? AND id != ?");
                            $check_name->execute([$brand_name, $brand_id]);
                            
                            if ($check_name->rowCount() > 0) {
                                $error_message = "Brand name '$brand_name' already exists!";
                            } else {
                                // Update brand (with or without logo)
                                if ($logo_path !== null) {
                                    $update_stmt = $pdo->prepare("UPDATE brands SET name = ?, description = ?, logo = ? WHERE id = ?");
                                    $update_stmt->execute([$brand_name, $description, $logo_path, $brand_id]);
                                } else {
                                    $update_stmt = $pdo->prepare("UPDATE brands SET name = ?, description = ? WHERE id = ?");
                                    $update_stmt->execute([$brand_name, $description, $brand_id]);
                                }
                                $success_message = "Brand updated successfully!";
                            }
                        }
                    } else {
                        $error_message = "Brand not found.";
                    }
                } catch (PDOException $e) {
                    $error_message = "Database error: " . $e->getMessage();
                }
            } elseif (empty($brand_name)) {
                $error_message = "Brand name is required!";
            } else {
                $error_message = "Invalid brand ID.";
            }
            break;

        case 'update_logo':
            $brand_id = isset($_POST['brand_id']) ? intval($_POST['brand_id']) : 0;
            $logo_path = null;

            if ($brand_id > 0) {
                // Handle logo upload
                if (isset($_FILES['brand_logo']) && $_FILES['brand_logo']['error'] === UPLOAD_ERR_OK) {
                    $upload_dir = UPLOAD_PATH . '/brands/';
                    
                    // Create directory if it doesn't exist
                    if (!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0755, true);
                    }
                    
                    $file_extension = strtolower(pathinfo($_FILES['brand_logo']['name'], PATHINFO_EXTENSION));
                    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                    
                    if (in_array($file_extension, $allowed_extensions)) {
                        // Check file size (max 5MB)
                        if ($_FILES['brand_logo']['size'] <= 5242880) {
                            // Get old logo path to delete it later
                            $old_logo_stmt = $pdo->prepare("SELECT logo FROM brands WHERE id = ?");
                            $old_logo_stmt->execute([$brand_id]);
                            $old_logo = $old_logo_stmt->fetchColumn();
                            
                            $new_filename = 'brand_' . $brand_id . '_' . time() . '_' . uniqid() . '.' . $file_extension;
                            $upload_path = $upload_dir . $new_filename;
                            
                            if (move_uploaded_file($_FILES['brand_logo']['tmp_name'], $upload_path)) {
                                $logo_path = 'brands/' . $new_filename;
                                
                                // Delete old logo if exists
                                if (!empty($old_logo) && file_exists(UPLOAD_PATH . '/' . $old_logo)) {
                                    @unlink(UPLOAD_PATH . '/' . $old_logo);
                                }
                                
                                // Update database
                                $update_stmt = $pdo->prepare("UPDATE brands SET logo = ? WHERE id = ?");
                                $update_stmt->execute([$logo_path, $brand_id]);
                                $success_message = "Brand logo updated successfully!";
                            } else {
                                $error_message = "Failed to upload logo. Please try again.";
                            }
                        } else {
                            $error_message = "Logo file is too large. Maximum size is 5MB.";
                        }
                    } else {
                        $error_message = "Invalid file type. Please upload JPG, PNG, GIF, or WebP images only.";
                    }
                } else {
                    $error_message = "Please select a logo file to upload.";
                }
            } else {
                $error_message = "Invalid brand ID.";
            }
            break;

        case 'delete_brand':
            $brand_id = isset($_POST['brand_id']) ? intval($_POST['brand_id']) : 0;

            if ($brand_id > 0) {
                try {
                    // Get logo path before deleting
                    $logo_stmt = $pdo->prepare("SELECT logo FROM brands WHERE id = ?");
                    $logo_stmt->execute([$brand_id]);
                    $logo_path = $logo_stmt->fetchColumn();
                    
                    // Check if brand has products
                    $check_products = $pdo->prepare("SELECT COUNT(*) FROM products WHERE brand_id = ?");
                    $check_products->execute([$brand_id]);
                    $product_count = $check_products->fetchColumn();

                    if ($product_count > 0) {
                        $error_message = "Cannot delete brand. It has $product_count products associated with it.";
                    } else {
                        $delete_stmt = $pdo->prepare("DELETE FROM brands WHERE id = ?");
                        $delete_stmt->execute([$brand_id]);
                        
                        // Delete logo file if exists
                        if (!empty($logo_path) && file_exists(UPLOAD_PATH . '/' . $logo_path)) {
                            @unlink(UPLOAD_PATH . '/' . $logo_path);
                        }
                        
                        $success_message = "Brand deleted successfully!";
                    }
                } catch (PDOException $e) {
                    $error_message = "Error deleting brand: " . $e->getMessage();
                }
            }
            break;
    }
}

// Fetch all brands with product count
try {
    $brands = $pdo->query("
        SELECT b.*,
               COUNT(p.id) as product_count,
               COUNT(CASE WHEN p.status = 'active' THEN 1 END) as active_products
        FROM brands b
        LEFT JOIN products p ON b.id = p.brand_id
        GROUP BY b.id
        ORDER BY b.created_at DESC
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = "Error fetching brands: " . $e->getMessage();
    $brands = [];
}
?>
<!-- Styles -->
<style>
    body { background-color: #f8f9fa; }
    .container { margin-top: 30px; padding: 30px; background-color: #fff; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
    .page-header { border-bottom: 2px solid #007bff; padding-bottom: 15px; margin-bottom: 30px; }
    .stats-card { background: linear-gradient(135deg, #007bff, #0056b3); color: white; padding: 20px; border-radius: 8px; text-align: center; margin-bottom: 20px; }
    .add-brand-form { background-color: #f8f9fa; padding: 25px; border-radius: 8px; border: 1px solid #dee2e6; margin-bottom: 30px; }
    .table th { background-color: #343a40; color: white; border: none; }
    .btn-action { margin: 2px; padding: 5px 10px; font-size: 0.875rem; }
    .table-hover tbody tr:hover { background-color: #f8f9fa; }
    .alert { margin-bottom: 20px; }
    .brand-logo { width: 60px; height: 60px; object-fit: contain; border-radius: 5px; border: 1px solid #dee2e6; padding: 5px; background: #fff; }
    .description-cell { max-width: 250px; word-wrap: break-word; }
    
    /* Fix modal z-index and interaction issues */
    .modal { z-index: 1055 !important; }
    .modal-backdrop { 
        z-index: 1050 !important; 
        background-color: rgba(0, 0, 0, 0.5) !important;
        pointer-events: auto !important;
    }
    .modal-dialog { 
        z-index: 1056 !important; 
        pointer-events: none;
    }
    .modal-content { 
        z-index: 1056 !important; 
        position: relative;
        pointer-events: auto;
    }
    .modal.show { display: block !important; }
    .modal.fade .modal-dialog { transition: transform 0.3s ease-out; }
    
    /* Ensure modal is clickable */
    .modal-body,
    .modal-header,
    .modal-footer {
        pointer-events: auto;
    }
</style>

<main>
<div class="container">
    <div class="page-header">
        <h1 class="mb-0">Brand Management</h1>
        <p class="text-muted mt-2">Manage AC brands and their information</p>
    </div>

    <!-- Display messages -->
    <?php if (!empty($success_message)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle"></i> <?= htmlspecialchars($success_message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if (!empty($error_message)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error_message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="stats-card">
                <h3><?= count($brands) ?></h3>
                <p class="mb-0">Total Brands</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card" style="background: linear-gradient(135deg, #28a745, #1e7e34);">
                <h3><?= count(array_filter($brands, fn($b)=>$b['status']==='active')) ?></h3>
                <p class="mb-0">Active Brands</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card" style="background: linear-gradient(135deg, #ffc107, #e0a800);">
                <h3><?= array_sum(array_column($brands,'product_count')) ?></h3>
                <p class="mb-0">Total Products</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card" style="background: linear-gradient(135deg, #17a2b8, #138496);">
                <h3><?= array_sum(array_column($brands,'active_products')) ?></h3>
                <p class="mb-0">Active Products</p>
            </div>
        </div>
    </div>

    <!-- Add Brand Form -->
    <div class="add-brand-form">
        <h4 class="mb-3"><i class="fas fa-plus-circle"></i> Add New Brand</h4>
        <form method="POST" class="row g-3" enctype="multipart/form-data">
            <input type="hidden" name="action" value="add_brand">
            <div class="col-md-3">
                <label for="brand_name" class="form-label">Brand Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control" name="brand_name" id="brand_name" placeholder="e.g., Hitachi, Daikin" required>
            </div>
            <div class="col-md-4">
                <label for="description" class="form-label">Description (Optional)</label>
                <textarea class="form-control" name="description" id="description" placeholder="Brief description about the brand"></textarea>
            </div>
            <div class="col-md-3">
                <label for="brand_logo" class="form-label">Brand Logo (Optional)</label>
                <input type="file" class="form-control" name="brand_logo" id="brand_logo" accept="image/jpeg,image/png,image/gif,image/webp">
                <small class="text-muted">Max 5MB, JPG/PNG/GIF/WebP</small>
            </div>
            <div class="col-md-2">
                <label class="form-label">&nbsp;</label>
                <button type="submit" class="btn btn-primary w-100"><i class="fas fa-plus"></i> Add Brand</button>
            </div>
        </form>
    </div>

    <!-- Brands Table -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">All Brands</h4>
        <div class="btn-group">
            <a href="category_management.php" class="btn btn-outline-primary btn-sm"><i class="fas fa-list"></i> Categories</a>
            <a href="index.php" class="btn btn-outline-secondary btn-sm"><i class="fas fa-dashboard"></i> Dashboard</a>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-hover table-bordered">
            <thead>
                <tr>
                    <th width="5%">ID</th>
                    <th width="8%">Logo</th>
                    <th width="18%">Brand Name</th>
                    <th width="25%">Description</th>
                    <th width="12%">Products</th>
                    <th width="10%">Status</th>
                    <th width="10%">Created</th>
                    <th width="12%">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($brands)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-4">
                            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                            <p class="text-muted">No brands found. Add your first brand above.</p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($brands as $brand): ?>
                        <tr>
                            <td><?= $brand['id'] ?></td>
                            <td>
                                <?php if (!empty($brand['logo'])): 
                                    $logo_url = BASE_URL . '/public/image.php?file=' . urlencode($brand['logo']);
                                ?>
                                    <img src="<?= $logo_url ?>" alt="<?= htmlspecialchars($brand['name']) ?>" class="brand-logo" onerror="this.src='<?= IMG_URL ?>/placeholder-product.png'; this.onerror=null;">
                                <?php else: ?>
                                    <span class="text-muted"><i class="fas fa-image"></i> No logo</span>
                                <?php endif; ?>
                            </td>
                            <td><strong><?= htmlspecialchars($brand['name']) ?></strong></td>
                            <td class="description-cell">
                                <?= !empty($brand['description']) ? htmlspecialchars($brand['description']) : '<em class="text-muted">No description</em>' ?>
                            </td>
                            <td>
                                <span class="badge bg-info"><?= $brand['product_count'] ?> total</span>
                                <?php if ($brand['active_products'] > 0): ?>
                                    <br><small class="text-success"><?= $brand['active_products'] ?> active</small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="action" value="toggle_status">
                                    <input type="hidden" name="brand_id" value="<?= $brand['id'] ?>">
                                    <input type="hidden" name="new_status" value="<?= $brand['status']==='active'?'inactive':'active' ?>">
                                    <button type="submit" class="btn btn-sm <?= $brand['status']==='active'?'btn-success':'btn-warning' ?>" onclick="return confirm('Are you sure you want to change the status?')">
                                        <?= ucfirst($brand['status']) ?>
                                    </button>
                                </form>
                            </td>
                            <td><small class="text-muted"><?= date('M d, Y', strtotime($brand['created_at'])) ?></small></td>
                            <td>
                                <div class="btn-group">
                                    <button type="button" class="btn btn-outline-success btn-action" title="Edit Brand" data-bs-toggle="modal" data-bs-target="#editModal<?= $brand['id'] ?>">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <?php if ($brand['product_count'] == 0): ?>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="action" value="delete_brand">
                                            <input type="hidden" name="brand_id" value="<?= $brand['id'] ?>">
                                            <button type="submit" class="btn btn-outline-danger btn-action" title="Delete Brand" onclick="return confirm('Are you sure you want to delete this brand? This action cannot be undone.')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <button class="btn btn-outline-secondary btn-action" title="Cannot delete - has products" disabled>
                                            <i class="fas fa-lock"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        
                        <!-- Edit Brand Modal -->
                        <div class="modal fade" id="editModal<?= $brand['id'] ?>" tabindex="-1" aria-labelledby="editModalLabel<?= $brand['id'] ?>" aria-hidden="true" role="dialog">
                            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="editModalLabel<?= $brand['id'] ?>">Edit Brand: <?= htmlspecialchars($brand['name']) ?></h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <form method="POST" enctype="multipart/form-data" id="editForm<?= $brand['id'] ?>">
                                        <div class="modal-body">
                                            <input type="hidden" name="action" value="edit_brand">
                                            <input type="hidden" name="brand_id" value="<?= $brand['id'] ?>">
                                            
                                            <div class="mb-3">
                                                <label for="edit_brand_name_<?= $brand['id'] ?>" class="form-label">Brand Name <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" name="brand_name" id="edit_brand_name_<?= $brand['id'] ?>" value="<?= htmlspecialchars($brand['name']) ?>" required>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label for="edit_description_<?= $brand['id'] ?>" class="form-label">Description</label>
                                                <textarea class="form-control" name="description" id="edit_description_<?= $brand['id'] ?>" rows="4" placeholder="Brief description about the brand"><?= htmlspecialchars($brand['description'] ?? '') ?></textarea>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label for="edit_brand_logo_<?= $brand['id'] ?>" class="form-label">Brand Logo</label>
                                                <?php if (!empty($brand['logo'])): 
                                                    $logo_url = BASE_URL . '/public/image.php?file=' . urlencode($brand['logo']);
                                                ?>
                                                    <div class="mb-2 text-center">
                                                        <p class="mb-2"><strong>Current Logo:</strong></p>
                                                        <img src="<?= $logo_url ?>" alt="<?= htmlspecialchars($brand['name']) ?>" class="img-thumbnail" style="max-width: 150px; max-height: 150px;" onerror="this.src='<?= IMG_URL ?>/placeholder-product.png'; this.onerror=null;">
                                                    </div>
                                                    <p class="text-muted small mb-2">Upload a new logo to replace the current one (optional)</p>
                                                <?php else: ?>
                                                    <p class="text-muted small mb-2">No logo currently set. Upload a logo (optional)</p>
                                                <?php endif; ?>
                                                <input type="file" class="form-control" name="brand_logo" id="edit_brand_logo_<?= $brand['id'] ?>" accept="image/jpeg,image/png,image/gif,image/webp">
                                                <small class="text-muted">Max 5MB, JPG/PNG/GIF/WebP. Leave empty to keep current logo.</small>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" class="btn btn-primary">Update Brand</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Quick Tips -->
    <div class="mt-4 p-3 bg-light rounded">
        <h6><i class="fas fa-lightbulb text-warning"></i> Brand Management Tips:</h6>
        <ul class="mb-0 small">
            <li><strong>Popular AC Brands:</strong> Hitachi, O General, Daikin, Mitsubishi, LG, Samsung, Blue Star, Voltas</li>
            <li>Brands with products cannot be deleted - deactivate them instead</li>
            <li>Inactive brands won't appear in product creation forms</li>
            <li>Use clear, recognizable brand names for better customer experience</li>
            <li>Add descriptions to help staff understand brand positioning</li>
        </ul>
    </div>
</div>
</main>

<?php
include INCLUDES_PATH . '/templates/admin_footer.php';
?>

<script>
// Fix modal interaction issues
document.addEventListener('DOMContentLoaded', function() {
    // Ensure all modals are properly initialized and clickable
    document.querySelectorAll('.modal').forEach(function(modalElement) {
        // When modal is shown, ensure content is clickable
        modalElement.addEventListener('shown.bs.modal', function() {
            // Force pointer events on modal content
            const modalContent = this.querySelector('.modal-content');
            if (modalContent) {
                modalContent.style.pointerEvents = 'auto';
                modalContent.style.position = 'relative';
                modalContent.style.zIndex = '1056';
            }
            
            // Ensure all interactive elements are clickable
            const interactiveElements = this.querySelectorAll('input, textarea, button, select, a');
            interactiveElements.forEach(function(el) {
                el.style.pointerEvents = 'auto';
            });
            
            // Focus on first input
            const firstInput = this.querySelector('input[type="text"], textarea');
            if (firstInput) {
                setTimeout(function() {
                    firstInput.focus();
                }, 150);
            }
        });
        
        // Clean up when modal is hidden
        modalElement.addEventListener('hidden.bs.modal', function() {
            // Remove any lingering backdrop
            const backdrops = document.querySelectorAll('.modal-backdrop');
            backdrops.forEach(function(backdrop) {
                backdrop.remove();
            });
            
            // Clean up body classes
            document.body.classList.remove('modal-open');
            document.body.style.overflow = '';
            document.body.style.paddingRight = '';
        });
    });
    
    // Handle edit button clicks
    document.querySelectorAll('[data-bs-target^="#editModal"]').forEach(function(button) {
        button.addEventListener('click', function() {
            // Small delay to ensure Bootstrap processes the click
            setTimeout(function() {
                const targetId = button.getAttribute('data-bs-target');
                const modal = document.querySelector(targetId);
                if (modal) {
                    const modalContent = modal.querySelector('.modal-content');
                    if (modalContent) {
                        modalContent.style.pointerEvents = 'auto';
                    }
                }
            }, 50);
        });
    });
});
</script>

