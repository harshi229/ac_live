<?php
require_once dirname(dirname(__DIR__)) . '/includes/config/init.php';

// Ensure the admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: ' . admin_url('login'));
    exit();
}

include INCLUDES_PATH . '/templates/admin_header.php';

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'toggle_status') {
        $category_id = intval($_POST['category_id']);
        $new_status = $_POST['new_status'] === 'active' ? 'active' : 'inactive';
        
        try {
            $update_stmt = $pdo->prepare("UPDATE categories SET status = ? WHERE id = ?");
            $update_stmt->execute([$new_status, $category_id]);
            $success_message = "Category status updated successfully!";
        } catch (PDOException $e) {
            $error_message = "Error updating category status: " . $e->getMessage();
        }
    }
}

// Handle delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $category_id = intval($_GET['delete']);
    
    try {
        // Check if category has products
        $check_products = $pdo->prepare("SELECT COUNT(*) FROM products WHERE category_id = ?");
        $check_products->execute([$category_id]);
        $product_count = $check_products->fetchColumn();
        
        if ($product_count > 0) {
            $error_message = "Cannot delete category. It has $product_count products associated with it.";
        } else {
            $delete_stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
            $delete_stmt->execute([$category_id]);
            $success_message = "Category deleted successfully!";
        }
    } catch (PDOException $e) {
        $error_message = "Error deleting category: " . $e->getMessage();
    }
}

// Fetch all categories with additional info
try {
    $categories = $pdo->query("
        SELECT c.*, 
               COUNT(p.id) as product_count,
               COUNT(CASE WHEN p.status = 'active' THEN 1 END) as active_products
        FROM categories c 
        LEFT JOIN products p ON c.id = p.category_id 
        GROUP BY c.id 
        ORDER BY c.created_at DESC
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = "Error fetching categories: " . $e->getMessage();
    $categories = [];
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
    .table th {
        background-color: #343a40;
        color: white;
        border: none;
    }
    .badge-status {
        font-size: 0.9em;
        padding: 6px 12px;
    }
    .btn-action {
        margin: 2px;
        padding: 5px 10px;
        font-size: 0.875rem;
    }
    .table-hover tbody tr:hover {
        background-color: #f8f9fa;
    }
    .description-cell {
        max-width: 300px;
        word-wrap: break-word;
    }
    .alert {
        margin-bottom: 20px;
    }
    .action-buttons {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
    }
    @media (max-width: 768px) {
        .action-buttons {
            flex-direction: column;
        }
        .table-responsive {
            font-size: 0.875rem;
        }
    }
</style>

<main>
    <div class="container">
        <div class="page-header">
            <h1 class="mb-0">Category Management</h1>
            <p class="text-muted mt-2">Manage product categories and their settings</p>
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

        <!-- Statistics Card -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stats-card">
                    <h3><?php echo count($categories); ?></h3>
                    <p class="mb-0">Total Categories</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card" style="background: linear-gradient(135deg, #28a745, #1e7e34);">
                    <h3><?php echo count(array_filter($categories, function($c) { return $c['status'] === 'active'; })); ?></h3>
                    <p class="mb-0">Active Categories</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card" style="background: linear-gradient(135deg, #ffc107, #e0a800);">
                    <h3><?php echo array_sum(array_column($categories, 'product_count')); ?></h3>
                    <p class="mb-0">Total Products</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card" style="background: linear-gradient(135deg, #17a2b8, #138496);">
                    <h3><?php echo array_sum(array_column($categories, 'active_products')); ?></h3>
                    <p class="mb-0">Active Products</p>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div class="action-buttons">
                <a href="add.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Add New Category
                </a>
                <a href="subcategories.php" class="btn btn-outline-primary">
                    <i class="fas fa-list"></i> Manage Subcategories
                </a>
                <a href="index.php" class="btn btn-outline-secondary">
                    <i class="fas fa-dashboard"></i> Dashboard
                </a>
            </div>
        </div>

        <!-- Categories Table -->
        <div class="table-responsive">
            <table class="table table-hover table-bordered">
                <thead>
                    <tr>
                        <th width="5%">ID</th>
                        <th width="15%">Image</th>
                        <th width="20%">Category Name</th>
                        <th width="25%">Description</th>
                        <th width="8%">Products</th>
                        <th width="8%">Status</th>
                        <th width="12%">Created</th>
                        <th width="7%">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($categories)): ?>
                        <tr>
                            <td colspan="8" class="text-center py-4">
                                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                <p class="text-muted">No categories found. <a href="add.php">Add your first category</a></p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($categories as $category): ?>
                            <tr>
                                <td><?php echo $category['id']; ?></td>
                                <td>
                                    <?php if (!empty($category['image'])): ?>
                                        <img src="<?php echo htmlspecialchars($category['image']); ?>" 
                                             alt="<?php echo htmlspecialchars($category['name']); ?>" 
                                             style="width: 60px; height: 60px; object-fit: cover; border-radius: 5px; border: 1px solid #ddd;">
                                    <?php else: ?>
                                        <div style="width: 60px; height: 60px; background-color: #f8f9fa; border: 1px solid #ddd; border-radius: 5px; display: flex; align-items: center; justify-content: center;">
                                            <i class="fas fa-image text-muted"></i>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($category['name'] ?? ''); ?></strong>
                                </td>
                                <td class="description-cell">
                                    <?php
                                    $description = htmlspecialchars($category['description'] ?? '');
                                    echo !empty($description) ? (strlen($description) > 100 ? substr($description, 0, 100) . '...' : $description) : '<em class="text-muted">No description</em>';
                                    ?>
                                </td>
                                <td>
                                    <span class="badge bg-info"><?php echo $category['product_count']; ?> total</span>
                                    <?php if ($category['active_products'] > 0): ?>
                                        <br><small class="text-success"><?php echo $category['active_products']; ?> active</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="toggle_status">
                                        <input type="hidden" name="category_id" value="<?php echo $category['id']; ?>">
                                        <input type="hidden" name="new_status" value="<?php echo $category['status'] === 'active' ? 'inactive' : 'active'; ?>">
                                        <button type="submit" class="btn btn-sm <?php echo $category['status'] === 'active' ? 'btn-success' : 'btn-warning'; ?>" 
                                                onclick="return confirm('Are you sure you want to change the status of this category?')">
                                            <?php echo $category['status'] === 'active' ? 'Active' : 'Inactive'; ?>
                                        </button>
                                    </form>
                                </td>
                                <td>
                                    <small class="text-muted">
                                        <?php echo date('M d, Y', strtotime($category['created_at'])); ?>
                                    </small>
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="<?php echo admin_url('categories/edit?id=' . $category['id']); ?>" 
                                           class="btn btn-outline-warning btn-action" title="Edit Category">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <?php if ($category['product_count'] == 0): ?>
                                            <a href="?delete=<?php echo $category['id']; ?>" 
                                               class="btn btn-outline-danger btn-action" 
                                               title="Delete Category"
                                               onclick="return confirm('Are you sure you want to delete this category? This action cannot be undone.')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        <?php else: ?>
                                            <button class="btn btn-outline-secondary btn-action" 
                                                    title="Cannot delete - has products" disabled>
                                                <i class="fas fa-lock"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Quick Tips -->
        <div class="mt-4 p-3 bg-light rounded">
            <h6><i class="fas fa-lightbulb text-warning"></i> Quick Tips:</h6>
            <ul class="mb-0 small">
                <li>Categories with products cannot be deleted - deactivate them instead</li>
                <li>Inactive categories won't appear in product creation forms</li>
                <li>Always add a description to help identify the category's purpose</li>
                <li>Consider organizing subcategories under main categories for better structure</li>
            </ul>
        </div>
    </div>
</main>

<?php
include INCLUDES_PATH . '/templates/admin_footer.php';
?>
