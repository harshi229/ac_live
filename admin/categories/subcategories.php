<?php
require_once dirname(dirname(__DIR__)) . '/includes/config/init.php';

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
            case 'add_subcategory':
                $category_id = intval($_POST['category_id']);
                $subcategory_name = trim($_POST['subcategory_name']);
                $description = trim($_POST['description']);
                
                if (!empty($subcategory_name) && $category_id > 0) {
                    try {
                        // Check if subcategory already exists in this category
                        $check_stmt = $pdo->prepare("SELECT id FROM sub_categories WHERE name = ? AND category_id = ?");
                        $check_stmt->execute([$subcategory_name, $category_id]);
                        
                        if ($check_stmt->rowCount() > 0) {
                            $error_message = "Subcategory '$subcategory_name' already exists in this category!";
                        } else {
                            $insert_stmt = $pdo->prepare("INSERT INTO sub_categories (category_id, name, description, status) VALUES (?, ?, ?, 'active')");
                            $insert_stmt->execute([$category_id, $subcategory_name, $description]);
                            $success_message = "Subcategory '$subcategory_name' added successfully!";
                        }
                    } catch (PDOException $e) {
                        $error_message = "Database error: " . $e->getMessage();
                    }
                } else {
                    $error_message = "Subcategory name and category are required!";
                }
                break;
                
            case 'toggle_status':
                $subcategory_id = intval($_POST['subcategory_id']);
                $new_status = $_POST['new_status'] === 'active' ? 'active' : 'inactive';
                
                try {
                    $update_stmt = $pdo->prepare("UPDATE sub_categories SET status = ? WHERE id = ?");
                    $update_stmt->execute([$new_status, $subcategory_id]);
                    $success_message = "Subcategory status updated successfully!";
                } catch (PDOException $e) {
                    $error_message = "Error updating subcategory status: " . $e->getMessage();
                }
                break;
                
            case 'delete_subcategory':
                $subcategory_id = intval($_POST['subcategory_id']);
                
                try {
                    // Check if subcategory has products
                    $check_products = $pdo->prepare("SELECT COUNT(*) FROM products WHERE sub_category_id = ?");
                    $check_products->execute([$subcategory_id]);
                    $product_count = $check_products->fetchColumn();
                    
                    if ($product_count > 0) {
                        $error_message = "Cannot delete subcategory. It has $product_count products associated with it.";
                    } else {
                        $delete_stmt = $pdo->prepare("DELETE FROM sub_categories WHERE id = ?");
                        $delete_stmt->execute([$subcategory_id]);
                        $success_message = "Subcategory deleted successfully!";
                    }
                } catch (PDOException $e) {
                    $error_message = "Error deleting subcategory: " . $e->getMessage();
                }
                break;
        }
    }
}

// Fetch data for display
try {
    $categories = $pdo->query("SELECT * FROM categories WHERE status = 'active' ORDER BY name")->fetchAll();
    
    $subcategories = $pdo->query("
        SELECT sc.*, c.name as category_name, 
               COUNT(p.id) as product_count,
               COUNT(CASE WHEN p.status = 'active' THEN 1 END) as active_products
        FROM sub_categories sc 
        JOIN categories c ON sc.category_id = c.id
        LEFT JOIN products p ON sc.id = p.sub_category_id 
        GROUP BY sc.id 
        ORDER BY c.name, sc.name
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    // Group subcategories by category for better display
    $grouped_subcategories = [];
    foreach ($subcategories as $subcat) {
        $grouped_subcategories[$subcat['category_name']][] = $subcat;
    }
    
} catch (PDOException $e) {
    $error_message = "Error fetching data: " . $e->getMessage();
    $categories = [];
    $subcategories = [];
    $grouped_subcategories = [];
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
    .category-section {
        background: white;
        margin-bottom: 25px;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }
    .category-header {
        background: linear-gradient(135deg, #6c757d, #495057);
        color: white;
        padding: 15px 20px;
        margin: 0;
        font-size: 1.1rem;
        font-weight: 500;
    }
    .subcategory-table {
        margin: 0;
    }
    .subcategory-table td {
        border-bottom: 1px solid #dee2e6;
        padding: 12px 20px;
    }
    .subcategory-table tr:last-child td {
        border-bottom: none;
    }
    .subcategory-table tr:hover {
        background-color: #f8f9fa;
    }
    .btn-action {
        margin: 2px;
        padding: 5px 10px;
        font-size: 0.875rem;
    }
    .alert {
        margin-bottom: 20px;
    }
    .status-badge {
        font-size: 0.85rem;
        padding: 4px 12px;
        border-radius: 12px;
        font-weight: 500;
    }
    .description-text {
        color: #6c757d;
        font-size: 0.9rem;
        font-style: italic;
    }
</style>

<main>
    <div class="container">
        <div class="page-header">
            <h1 class="mb-0">Subcategory Management</h1>
            <p class="text-muted mt-2">Organize subcategories under main categories for better product classification</p>
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
            <div class="col-md-3">
                <div class="stats-card">
                    <h3><?php echo count($categories); ?></h3>
                    <p class="mb-0">Active Categories</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card" style="background: linear-gradient(135deg, #28a745, #1e7e34);">
                    <h3><?php echo count($subcategories); ?></h3>
                    <p class="mb-0">Total Subcategories</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card" style="background: linear-gradient(135deg, #ffc107, #e0a800);">
                    <h3><?php echo count(array_filter($subcategories, function($s) { return $s['status'] === 'active'; })); ?></h3>
                    <p class="mb-0">Active Subcategories</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card" style="background: linear-gradient(135deg, #17a2b8, #138496);">
                    <h3><?php echo array_sum(array_column($subcategories, 'product_count')); ?></h3>
                    <p class="mb-0">Total Products</p>
                </div>
            </div>
        </div>

        <!-- Add New Subcategory Form -->
        <div class="add-form">
            <h4 class="mb-3"><i class="fas fa-plus-circle"></i> Add New Subcategory</h4>
            <form method="POST" class="row g-3">
                <input type="hidden" name="action" value="add_subcategory">
                
                <div class="col-md-3">
                    <label for="category_id" class="form-label">Category <span class="text-danger">*</span></label>
                    <select class="form-select" name="category_id" id="category_id" required>
                        <option value="">Select Category</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?= $category['id'] ?>">
                                <?= htmlspecialchars($category['name'] ?? '') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-3">
                    <label for="subcategory_name" class="form-label">Subcategory Name <span class="text-danger">*</span></label>
                    <input type="text" 
                           class="form-control" 
                           name="subcategory_name" 
                           id="subcategory_name" 
                           placeholder="e.g., Split, Window, VRF" 
                           required>
                </div>
                
                <div class="col-md-4">
                    <label for="description" class="form-label">Description (Optional)</label>
                    <input type="text" 
                           class="form-control" 
                           name="description" 
                           id="description" 
                           placeholder="Brief description of this subcategory">
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
            <h4 class="mb-0">Subcategories by Category</h4>
            <div class="btn-group">
                <a href="category_management.php" class="btn btn-outline-primary btn-sm">
                    <i class="fas fa-list"></i> Manage Categories
                </a>
                <a href="brand_management.php" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-tags"></i> Manage Brands
                </a>
                <a href="index.php" class="btn btn-outline-info btn-sm">
                    <i class="fas fa-dashboard"></i> Dashboard
                </a>
            </div>
        </div>

        <!-- Subcategories by Category -->
        <?php if (empty($grouped_subcategories)): ?>
            <div class="text-center py-5">
                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">No subcategories found</h5>
                <p class="text-muted">Add your first subcategory using the form above</p>
            </div>
        <?php else: ?>
            <?php foreach ($grouped_subcategories as $category_name => $subcats): ?>
                <div class="category-section">
                    <h5 class="category-header">
                        <i class="fas fa-folder"></i> <?php echo htmlspecialchars($category_name ?? ''); ?>
                        <span class="badge bg-light text-dark ms-2"><?php echo count($subcats); ?> subcategories</span>
                    </h5>
                    <table class="table subcategory-table mb-0">
                        <?php foreach ($subcats as $subcat): ?>
                            <tr>
                                <td width="25%">
                                    <strong><?php echo htmlspecialchars($subcat['name'] ?? ''); ?></strong>
                                    <?php if (!empty($subcat['description'])): ?>
                                        <br><span class="description-text"><?php echo htmlspecialchars($subcat['description'] ?? ''); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td width="15%">
                                    <span class="badge bg-info"><?php echo $subcat['product_count']; ?> products</span>
                                    <?php if ($subcat['active_products'] > 0): ?>
                                        <br><small class="text-success"><?php echo $subcat['active_products']; ?> active</small>
                                    <?php endif; ?>
                                </td>
                                <td width="15%">
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="toggle_status">
                                        <input type="hidden" name="subcategory_id" value="<?php echo $subcat['id']; ?>">
                                        <input type="hidden" name="new_status" value="<?php echo $subcat['status'] === 'active' ? 'inactive' : 'active'; ?>">
                                        <button type="submit" 
                                                class="btn btn-sm <?php echo $subcat['status'] === 'active' ? 'btn-success' : 'btn-warning'; ?>" 
                                                onclick="return confirm('Change subcategory status?')">
                                            <?php echo $subcat['status'] === 'active' ? 'Active' : 'Inactive'; ?>
                                        </button>
                                    </form>
                                </td>
                                <td width="15%">
                                    <small class="text-muted">
                                        <?php echo date('M j, Y', strtotime($subcat['created_at'])); ?>
                                    </small>
                                </td>
                                <td width="30%">
                                    <div class="btn-group" role="group">
                                        <?php if ($subcat['product_count'] == 0): ?>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="delete_subcategory">
                                                <input type="hidden" name="subcategory_id" value="<?php echo $subcat['id']; ?>">
                                                <button type="submit" 
                                                        class="btn btn-outline-danger btn-action" 
                                                        title="Delete Subcategory"
                                                        onclick="return confirm('Are you sure you want to delete this subcategory? This action cannot be undone.')">
                                                    <i class="fas fa-trash"></i> Delete
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <button class="btn btn-outline-secondary btn-action" 
                                                    title="Cannot delete - has products" 
                                                    disabled>
                                                <i class="fas fa-lock"></i> Protected
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <!-- AC Subcategory Examples -->
        <div class="mt-4 p-3 bg-light rounded">
            <h6><i class="fas fa-lightbulb text-warning"></i> Common AC Subcategories:</h6>
            <div class="row small">
                <div class="col-md-4">
                    <strong>Residential AC:</strong>
                    <ul class="mb-0">
                        <li>Split AC</li>
                        <li>Window AC</li>
                        <li>Portable AC</li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <strong>Commercial AC:</strong>
                    <ul class="mb-0">
                        <li>Ducted AC</li>
                        <li>Ductless AC</li>
                        <li>VRF Systems</li>
                        <li>Packaged AC</li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <strong>Cassette AC:</strong>
                    <ul class="mb-0">
                        <li>4-Way Cassette</li>
                        <li>2-Way Cassette</li>
                        <li>1-Way Cassette</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</main>

<?php
include INCLUDES_PATH . '/templates/admin_footer.php';
?>
