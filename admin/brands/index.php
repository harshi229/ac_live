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

            if (!empty($brand_name)) {
                try {
                    // Check if brand already exists
                    $check_stmt = $pdo->prepare("SELECT id FROM brands WHERE name = ?");
                    $check_stmt->execute([$brand_name]);

                    if ($check_stmt->rowCount() > 0) {
                        $error_message = "Brand '$brand_name' already exists!";
                    } else {
                        $insert_stmt = $pdo->prepare("INSERT INTO brands (name, description, logo, status, created_at) VALUES (?, ?, NULL, 'active', NOW())");
                        $insert_stmt->execute([$brand_name, $description]);
                        $success_message = "Brand '$brand_name' added successfully!";
                    }
                } catch (PDOException $e) {
                    $error_message = "Database error: " . $e->getMessage();
                }
            } else {
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

        case 'delete_brand':
            $brand_id = isset($_POST['brand_id']) ? intval($_POST['brand_id']) : 0;

            if ($brand_id > 0) {
                try {
                    // Check if brand has products
                    $check_products = $pdo->prepare("SELECT COUNT(*) FROM products WHERE brand_id = ?");
                    $check_products->execute([$brand_id]);
                    $product_count = $check_products->fetchColumn();

                    if ($product_count > 0) {
                        $error_message = "Cannot delete brand. It has $product_count products associated with it.";
                    } else {
                        $delete_stmt = $pdo->prepare("DELETE FROM brands WHERE id = ?");
                        $delete_stmt->execute([$brand_id]);
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
    .brand-logo { width: 50px; height: 50px; object-fit: cover; border-radius: 5px; }
    .description-cell { max-width: 250px; word-wrap: break-word; }
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
        <form method="POST" class="row g-3">
            <input type="hidden" name="action" value="add_brand">
            <div class="col-md-4">
                <label for="brand_name" class="form-label">Brand Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control" name="brand_name" id="brand_name" placeholder="e.g., Hitachi, Daikin" required>
            </div>
            <div class="col-md-6">
                <label for="description" class="form-label">Description (Optional)</label>
                <textarea class="form-control" name="description" id="description" placeholder="Brief description about the brand"></textarea>
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
                    <th width="20%">Brand Name</th>
                    <th width="30%">Description</th>
                    <th width="15%">Products</th>
                    <th width="10%">Status</th>
                    <th width="12%">Created</th>
                    <th width="8%">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($brands)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-4">
                            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                            <p class="text-muted">No brands found. Add your first brand above.</p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($brands as $brand): ?>
                        <tr>
                            <td><?= $brand['id'] ?></td>
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

