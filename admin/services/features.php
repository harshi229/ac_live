<?php
require_once __DIR__ . '/../../includes/config/init.php';

// Ensure the admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: ' . admin_url('login'));
    exit();
}

include INCLUDES_PATH . '/templates/admin_header.php';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_feature':
                $feature_name = trim($_POST['feature_name']);
                $description = trim($_POST['description']);
                
                if (!empty($feature_name)) {
                    try {
                        // Check if feature already exists
                        $check_stmt = $pdo->prepare("SELECT id FROM features WHERE name = ?");
                        $check_stmt->execute([$feature_name]);
                        
                        if ($check_stmt->rowCount() > 0) {
                            $error_message = "Feature '$feature_name' already exists!";
                        } else {
                            $insert_stmt = $pdo->prepare("INSERT INTO features (name, description) VALUES (?, ?)");
                            $insert_stmt->execute([$feature_name, $description]);
                            $success_message = "Feature '$feature_name' added successfully!";
                        }
                    } catch (PDOException $e) {
                        $error_message = "Database error: " . $e->getMessage();
                    }
                } else {
                    $error_message = "Feature name is required!";
                }
                break;
                
            case 'edit_feature':
                $feature_id = intval($_POST['feature_id']);
                $feature_name = trim($_POST['feature_name']);
                $description = trim($_POST['description']);
                
                if (!empty($feature_name) && $feature_id > 0) {
                    try {
                        // Check if feature name already exists (excluding current feature)
                        $check_stmt = $pdo->prepare("SELECT id FROM features WHERE name = ? AND id != ?");
                        $check_stmt->execute([$feature_name, $feature_id]);
                        
                        if ($check_stmt->rowCount() > 0) {
                            $error_message = "Feature '$feature_name' already exists!";
                        } else {
                            $update_stmt = $pdo->prepare("UPDATE features SET name = ?, description = ? WHERE id = ?");
                            $update_stmt->execute([$feature_name, $description, $feature_id]);
                            $success_message = "Feature updated successfully!";
                        }
                    } catch (PDOException $e) {
                        $error_message = "Database error: " . $e->getMessage();
                    }
                } else {
                    $error_message = "Feature name is required!";
                }
                break;
                
            case 'delete_feature':
                $feature_id = intval($_POST['feature_id']);
                
                try {
                    // Check if feature is used by any products
                    $check_products = $pdo->prepare("SELECT COUNT(*) FROM product_features WHERE feature_id = ?");
                    $check_products->execute([$feature_id]);
                    $usage_count = $check_products->fetchColumn();
                    
                    if ($usage_count > 0) {
                        $error_message = "Cannot delete feature. It is used by $usage_count products.";
                    } else {
                        $delete_stmt = $pdo->prepare("DELETE FROM features WHERE id = ?");
                        $delete_stmt->execute([$feature_id]);
                        $success_message = "Feature deleted successfully!";
                    }
                } catch (PDOException $e) {
                    $error_message = "Error deleting feature: " . $e->getMessage();
                }
                break;
        }
    }
}

// Fetch all features with usage statistics
try {
    $features = $pdo->query("
        SELECT f.*, 
               COUNT(pf.product_id) as product_count,
               GROUP_CONCAT(DISTINCT p.product_name SEPARATOR ', ') as used_in_products
        FROM features f 
        LEFT JOIN product_features pf ON f.id = pf.feature_id
        LEFT JOIN products p ON pf.product_id = p.id
        GROUP BY f.id 
        ORDER BY f.name ASC
    ")->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error_message = "Error fetching features: " . $e->getMessage();
    $features = [];
}

// Get edit feature data if editing
$edit_feature = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    foreach ($features as $feature) {
        if ($feature['id'] == $edit_id) {
            $edit_feature = $feature;
            break;
        }
    }
}
?>

<style>
    body {
        background-color: #f8f9fa;
    }
    .container {
        margin-top: 30px;
        padding: 30px;
        background-color: #ffffff;
        border-radius: 8px;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
    }
    .page-header {
        border-bottom: 2px solid #007bff;
        padding-bottom: 15px;
        margin-bottom: 30px;
    }
    .stats-card {
        background: linear-gradient(135deg, #007bff, #0056b3);
        color: white;
        padding: 20px;
        border-radius: 8px;
        text-align: center;
        margin-bottom: 20px;
    }
    .add-form {
        background-color: #f8f9fa;
        padding: 25px;
        border-radius: 8px;
        border: 1px solid #dee2e6;
        margin-bottom: 30px;
    }
    .feature-card {
        background: white;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 15px;
        transition: box-shadow 0.3s ease;
    }
    .feature-card:hover {
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }
    .feature-name {
        font-size: 1.1rem;
        font-weight: 600;
        color: #333;
        margin-bottom: 8px;
    }
    .feature-description {
        color: #6c757d;
        font-size: 0.95rem;
        margin-bottom: 10px;
    }
    .feature-usage {
        font-size: 0.85rem;
        color: #28a745;
        font-weight: 500;
    }
    .btn-action {
        margin: 2px;
        padding: 6px 12px;
        font-size: 0.875rem;
    }
    .alert {
        margin-bottom: 20px;
    }
    .edit-form {
        background-color: #fff3cd;
        border: 1px solid #ffeaa7;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 20px;
    }
    .common-features {
        background-color: #e7f3ff;
        border: 1px solid #b3d9ff;
        border-radius: 8px;
        padding: 15px;
        margin-top: 20px;
    }
</style>

<main>
    <div class="container">
        <div class="page-header">
            <h1 class="mb-0">Features Management</h1>
            <p class="text-muted mt-2">Manage AC features and specifications for products</p>
        </div>

        <!-- Display messages -->
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="stats-card">
                    <h3><?php echo count($features); ?></h3>
                    <p class="mb-0">Total Features</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stats-card" style="background: linear-gradient(135deg, #28a745, #1e7e34);">
                    <h3><?php echo count(array_filter($features, function($f) { return $f['product_count'] > 0; })); ?></h3>
                    <p class="mb-0">Features in Use</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stats-card" style="background: linear-gradient(135deg, #ffc107, #e0a800);">
                    <h3><?php echo array_sum(array_column($features, 'product_count')); ?></h3>
                    <p class="mb-0">Total Assignments</p>
                </div>
            </div>
        </div>

        <!-- Edit Feature Form (if editing) -->
        <?php if ($edit_feature): ?>
            <div class="edit-form">
                <h4 class="mb-3"><i class="fas fa-edit"></i> Edit Feature</h4>
                <form method="POST" class="row g-3">
                    <input type="hidden" name="action" value="edit_feature">
                    <input type="hidden" name="feature_id" value="<?= $edit_feature['id'] ?>">
                    
                    <div class="col-md-4">
                        <label for="edit_feature_name" class="form-label">Feature Name <span class="text-danger">*</span></label>
                        <input type="text" 
                               class="form-control" 
                               name="feature_name" 
                               id="edit_feature_name" 
                               value="<?= htmlspecialchars($edit_feature['name']) ?>" 
                               required>
                    </div>
                    
                    <div class="col-md-6">
                        <label for="edit_description" class="form-label">Description</label>
                        <input type="text" 
                               class="form-control" 
                               name="description" 
                               id="edit_description" 
                               value="<?= htmlspecialchars($edit_feature['description']) ?>" 
                               placeholder="Brief description of this feature">
                    </div>
                    
                    <div class="col-md-2">
                        <label class="form-label">&nbsp;</label>
                        <button type="submit" class="btn btn-success w-100">
                            <i class="fas fa-save"></i> Update
                        </button>
                    </div>
                </form>
                <div class="mt-2">
                    <a href="features_management.php" class="btn btn-secondary btn-sm">Cancel Edit</a>
                </div>
            </div>
        <?php endif; ?>

        <!-- Add New Feature Form -->
        <div class="add-form">
            <h4 class="mb-3"><i class="fas fa-plus-circle"></i> Add New Feature</h4>
            <form method="POST" class="row g-3">
                <input type="hidden" name="action" value="add_feature">
                
                <div class="col-md-4">
                    <label for="feature_name" class="form-label">Feature Name <span class="text-danger">*</span></label>
                    <input type="text" 
                           class="form-control" 
                           name="feature_name" 
                           id="feature_name" 
                           placeholder="e.g., WiFi Enabled, Silent Mode" 
                           required>
                </div>
                
                <div class="col-md-6">
                    <label for="description" class="form-label">Description</label>
                    <input type="text" 
                           class="form-control" 
                           name="description" 
                           id="description" 
                           placeholder="Brief description of this feature">
                </div>
                
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-plus"></i> Add
                    </button>
                </div>
            </form>
        </div>

        <!-- Navigation -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="mb-0">All Features</h4>
            <div class="btn-group">
                <a href="product_management.php" class="btn btn-outline-primary btn-sm">
                    <i class="fas fa-boxes"></i> Products
                </a>
                <a href="index.php" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-dashboard"></i> Dashboard
                </a>
            </div>
        </div>

        <!-- Features List -->
        <?php if (empty($features)): ?>
            <div class="text-center py-5">
                <i class="fas fa-star fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">No features found</h5>
                <p class="text-muted">Add your first feature using the form above</p>
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($features as $feature): ?>
                    <div class="col-lg-6">
                        <div class="feature-card">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div class="feature-name"><?php echo htmlspecialchars($feature['name']); ?></div>
                                <div class="btn-group">
                                    <a href="?edit=<?php echo $feature['id']; ?>" 
                                       class="btn btn-outline-warning btn-action" 
                                       title="Edit Feature">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <?php if ($feature['product_count'] == 0): ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="delete_feature">
                                            <input type="hidden" name="feature_id" value="<?php echo $feature['id']; ?>">
                                            <button type="submit" 
                                                    class="btn btn-outline-danger btn-action" 
                                                    title="Delete Feature"
                                                    onclick="return confirm('Are you sure you want to delete this feature?')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <button class="btn btn-outline-secondary btn-action" 
                                                title="Cannot delete - used in products" 
                                                disabled>
                                            <i class="fas fa-lock"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <?php if ($feature['description']): ?>
                                <div class="feature-description"><?php echo htmlspecialchars($feature['description']); ?></div>
                            <?php endif; ?>
                            
                            <div class="feature-usage">
                                <i class="fas fa-tag"></i>
                                <?php if ($feature['product_count'] > 0): ?>
                                    Used in <?php echo $feature['product_count']; ?> product(s)
                                <?php else: ?>
                                    <span class="text-muted">Not used in any products yet</span>
                                <?php endif; ?>
                            </div>
                            
                            <?php if ($feature['used_in_products'] && strlen($feature['used_in_products']) < 100): ?>
                                <div class="mt-2">
                                    <small class="text-muted">Products: <?php echo htmlspecialchars($feature['used_in_products']); ?></small>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Common AC Features Guide -->
        <div class="common-features">
            <h6><i class="fas fa-lightbulb text-warning"></i> Common AC Features to Add:</h6>
            <div class="row small">
                <div class="col-md-3">
                    <strong>Smart Features:</strong>
                    <ul class="mb-0">
                        <li>WiFi Enabled</li>
                        <li>Smart Sensors</li>
                        <li>App Control</li>
                        <li>Voice Control</li>
                    </ul>
                </div>
                <div class="col-md-3">
                    <strong>Performance:</strong>
                    <ul class="mb-0">
                        <li>Fast Cooling</li>
                        <li>Silent Mode</li>
                        <li>Energy Efficient</li>
                        <li>Dual Inverter</li>
                    </ul>
                </div>
                <div class="col-md-3">
                    <strong>Health & Air:</strong>
                    <ul class="mb-0">
                        <li>Anti-Bacterial Filter</li>
                        <li>Air Purification</li>
                        <li>Auto Clean</li>
                        <li>Dehumidifier</li>
                    </ul>
                </div>
                <div class="col-md-3">
                    <strong>Convenience:</strong>
                    <ul class="mb-0">
                        <li>Remote Control</li>
                        <li>Timer Function</li>
                        <li>Sleep Mode</li>
                        <li>Stabilizer Free</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</main>

<?php
include INCLUDES_PATH . '/templates/admin_footer.php';
?>
