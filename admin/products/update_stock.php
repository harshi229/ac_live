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
            case 'update_single_stock':
                $product_id = intval($_POST['product_id']);
                $new_stock = intval($_POST['stock']);
                $reason = trim($_POST['reason']);
                
                if ($product_id > 0 && $new_stock >= 0) {
                    try {
                        // Get current stock
                        $current_stock_query = $pdo->prepare("SELECT stock FROM products WHERE id = ?");
                        $current_stock_query->execute([$product_id]);
                        $current_stock = $current_stock_query->fetchColumn();
                        
                        if ($current_stock !== false) {
                            // Update product stock
                            $update_query = $pdo->prepare("UPDATE products SET stock = ? WHERE id = ?");
                            $update_query->execute([$new_stock, $product_id]);
                            
                            // Log the inventory change
                            $change_type = $new_stock > $current_stock ? 'purchase' : 
                                          ($new_stock < $current_stock ? 'adjustment' : 'adjustment');
                            $quantity_changed = $new_stock - $current_stock;
                            
                            $log_query = $pdo->prepare("
                                INSERT INTO inventory_logs (product_id, change_type, quantity_changed, old_stock, new_stock, reason, admin_id) 
                                VALUES (?, ?, ?, ?, ?, ?, ?)
                            ");
                            $log_query->execute([
                                $product_id, 
                                $change_type, 
                                $quantity_changed, 
                                $current_stock, 
                                $new_stock, 
                                $reason, 
                                $_SESSION['admin_id']
                            ]);
                            
                            $success_message = "Stock updated successfully! Changed from $current_stock to $new_stock units.";
                        } else {
                            $error_message = "Product not found!";
                        }
                    } catch (PDOException $e) {
                        $error_message = "Database error: " . $e->getMessage();
                    }
                } else {
                    $error_message = "Invalid product ID or stock quantity!";
                }
                break;
                
            case 'bulk_stock_update':
                $updates = $_POST['bulk_updates'];
                $updated_count = 0;
                
                try {
                    $pdo->beginTransaction();
                    
                    foreach ($updates as $product_id => $data) {
                        $new_stock = intval($data['stock']);
                        $reason = trim($data['reason']);
                        
                        if ($new_stock >= 0 && !empty($reason)) {
                            // Get current stock
                            $current_stock_query = $pdo->prepare("SELECT stock FROM products WHERE id = ?");
                            $current_stock_query->execute([$product_id]);
                            $current_stock = $current_stock_query->fetchColumn();
                            
                            if ($current_stock !== false && $current_stock != $new_stock) {
                                // Update stock
                                $update_query = $pdo->prepare("UPDATE products SET stock = ? WHERE id = ?");
                                $update_query->execute([$new_stock, $product_id]);
                                
                                // Log change
                                $change_type = $new_stock > $current_stock ? 'purchase' : 'adjustment';
                                $quantity_changed = $new_stock - $current_stock;
                                
                                $log_query = $pdo->prepare("
                                    INSERT INTO inventory_logs (product_id, change_type, quantity_changed, old_stock, new_stock, reason, admin_id) 
                                    VALUES (?, ?, ?, ?, ?, ?, ?)
                                ");
                                $log_query->execute([
                                    $product_id, 
                                    $change_type, 
                                    $quantity_changed, 
                                    $current_stock, 
                                    $new_stock, 
                                    $reason, 
                                    $_SESSION['admin_id']
                                ]);
                                
                                $updated_count++;
                            }
                        }
                    }
                    
                    $pdo->commit();
                    $success_message = "Bulk update completed! Updated $updated_count products.";
                    
                } catch (PDOException $e) {
                    $pdo->rollBack();
                    $error_message = "Error during bulk update: " . $e->getMessage();
                }
                break;
        }
    }
}

// Get filter parameters
$brand_filter = isset($_GET['brand']) ? intval($_GET['brand']) : '';
$category_filter = isset($_GET['category']) ? intval($_GET['category']) : '';
$stock_filter = isset($_GET['stock_level']) ? $_GET['stock_level'] : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Debug: Log filter parameters
error_log("Update Stock Filter parameters - Brand: $brand_filter, Category: $category_filter, Stock: $stock_filter, Search: $search");

// Build query conditions
$conditions = ["p.status = 'active'"];
$params = [];

if ($brand_filter) {
    $conditions[] = "p.brand_id = ?";
    $params[] = $brand_filter;
}

if ($category_filter) {
    $conditions[] = "p.category_id = ?";
    $params[] = $category_filter;
}

if ($stock_filter) {
    switch ($stock_filter) {
        case 'low':
            $conditions[] = "p.stock <= 5";
            break;
        case 'medium':
            $conditions[] = "p.stock BETWEEN 6 AND 20";
            break;
        case 'high':
            $conditions[] = "p.stock > 20";
            break;
        case 'out':
            $conditions[] = "p.stock = 0";
            break;
    }
}

if ($search) {
    $conditions[] = "(p.product_name LIKE ? OR p.model_name LIKE ? OR p.model_number LIKE ? OR p.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

try {
    // Get products for stock management
    $sql = "SELECT p.id, p.product_name, p.model_name, p.model_number, p.stock, p.product_image,
                   b.name as brand_name, c.name as category_name
            FROM products p
            LEFT JOIN brands b ON p.brand_id = b.id
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE " . implode(' AND ', $conditions) . "
            ORDER BY p.stock ASC, p.product_name ASC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get filter data
    $brands = $pdo->query("SELECT * FROM brands WHERE status = 'active' ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
    $categories = $pdo->query("SELECT * FROM categories WHERE status = 'active' ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
    
    // Debug: Log brands and categories
    error_log("Update Stock - Brands found: " . count($brands));
    error_log("Update Stock - Categories found: " . count($categories));
    
    // Get stock statistics
    $stock_stats = $pdo->query("
        SELECT 
            COUNT(*) as total_products,
            SUM(CASE WHEN stock = 0 THEN 1 ELSE 0 END) as out_of_stock,
            SUM(CASE WHEN stock BETWEEN 1 AND 5 THEN 1 ELSE 0 END) as low_stock,
            SUM(CASE WHEN stock BETWEEN 6 AND 20 THEN 1 ELSE 0 END) as medium_stock,
            SUM(CASE WHEN stock > 20 THEN 1 ELSE 0 END) as high_stock,
            SUM(stock) as total_inventory_value
        FROM products WHERE status = 'active'
    ")->fetch(PDO::FETCH_ASSOC);
    
    // Get recent inventory logs
    $recent_logs = $pdo->query("
        SELECT il.*, p.product_name, a.username as admin_name
        FROM inventory_logs il
        JOIN products p ON il.product_id = p.id
        JOIN admins a ON il.admin_id = a.id
        ORDER BY il.created_at DESC
        LIMIT 10
    ")->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error_message = "Error fetching data: " . $e->getMessage();
    $products = [];
    $brands = [];
    $categories = [];
    $stock_stats = [];
    $recent_logs = [];
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
        transition: transform 0.3s ease;
    }
    
    .stats-card:hover {
        transform: translateY(-3px);
    }
    
    .stats-card.danger { background: linear-gradient(135deg, #dc3545, #c82333); }
    .stats-card.warning { background: linear-gradient(135deg, #ffc107, #e0a800); }
    .stats-card.info { background: linear-gradient(135deg, #17a2b8, #138496); }
    .stats-card.success { background: linear-gradient(135deg, #28a745, #1e7e34); }
    
    .filter-section {
        background-color: #f8f9fa;
        padding: 20px;
        border-radius: 8px;
        border: 1px solid #dee2e6;
        margin-bottom: 30px;
    }
    
    .stock-indicator {
        display: inline-block;
        width: 12px;
        height: 12px;
        border-radius: 50%;
        margin-right: 8px;
    }
    
    .stock-out { background-color: #dc3545; }
    .stock-low { background-color: #ffc107; }
    .stock-medium { background-color: #17a2b8; }
    .stock-high { background-color: #28a745; }
    
    .product-card {
        background: white;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 15px;
        transition: box-shadow 0.3s ease;
    }
    
    .product-card:hover {
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }
    
    .product-image-small {
        width: 60px;
        height: 60px;
        object-fit: cover;
        border-radius: 5px;
    }
    
    .stock-input {
        width: 80px;
        display: inline-block;
    }
    
    .reason-input {
        width: 200px;
        display: inline-block;
    }
    
    .update-form {
        background-color: #f8f9fa;
        padding: 20px;
        border-radius: 8px;
        border: 1px solid #dee2e6;
        margin-bottom: 30px;
    }
    
    .logs-section {
        background-color: #f8f9fa;
        padding: 20px;
        border-radius: 8px;
        margin-top: 30px;
    }
    
    .log-item {
        padding: 10px;
        border-bottom: 1px solid #dee2e6;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .log-item:last-child {
        border-bottom: none;
    }
    
    .change-positive { color: #28a745; font-weight: 600; }
    .change-negative { color: #dc3545; font-weight: 600; }
    .change-neutral { color: #6c757d; font-weight: 600; }
</style>

<main>
    <div class="container">
        <div class="page-header">
            <h1 class="mb-0">Stock Management</h1>
            <p class="text-muted mt-2">Monitor and update product inventory levels</p>
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

        <!-- Stock Statistics -->
        <div class="row mb-4">
            <div class="col-lg-3 col-md-6">
                <div class="stats-card danger">
                    <h3><?php echo $stock_stats['out_of_stock']; ?></h3>
                    <p class="mb-0">Out of Stock</p>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stats-card warning">
                    <h3><?php echo $stock_stats['low_stock']; ?></h3>
                    <p class="mb-0">Low Stock (1-5)</p>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stats-card info">
                    <h3><?php echo $stock_stats['medium_stock']; ?></h3>
                    <p class="mb-0">Medium Stock (6-20)</p>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stats-card success">
                    <h3><?php echo $stock_stats['high_stock']; ?></h3>
                    <p class="mb-0">High Stock (20+)</p>
                </div>
            </div>
        </div>

        <!-- Quick Update Form -->
        <div class="update-form">
            <h4 class="mb-3"><i class="fas fa-edit"></i> Quick Stock Update</h4>
            <form method="POST" class="row g-3">
                <input type="hidden" name="action" value="update_single_stock">
                
                <div class="col-md-4">
                    <label for="product_id" class="form-label">Select Product</label>
                    <select class="form-select" name="product_id" id="product_id" required>
                        <option value="">Choose Product</option>
                        <?php foreach ($products as $product): ?>
                            <option value="<?= $product['id'] ?>">
                                <?= htmlspecialchars($product['product_name']) ?> - 
                                <?= htmlspecialchars($product['model_name']) ?> 
                                (Current: <?= $product['stock'] ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-2">
                    <label for="stock" class="form-label">New Stock</label>
                    <input type="number" class="form-control" name="stock" id="stock" min="0" required>
                </div>
                
                <div class="col-md-4">
                    <label for="reason" class="form-label">Reason</label>
                    <input type="text" class="form-control" name="reason" id="reason" 
                           placeholder="e.g., New delivery, Inventory adjustment" required>
                </div>
                
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-save"></i> Update
                    </button>
                </div>
            </form>
        </div>

        <!-- Filter Section -->
        <div class="filter-section">
            <h5 class="mb-3"><i class="fas fa-filter"></i> Filter Products</h5>
            <form method="GET" action="<?php echo admin_url('products/update_stock'); ?>" id="filterForm" class="row g-3" autocomplete="off">
                <div class="col-md-3">
                    <label for="search" class="form-label">Search</label>
                    <input type="text" class="form-control" name="search" id="search" 
                           value="<?= htmlspecialchars($search) ?>" placeholder="Search by product name, model, or description...">
                </div>
                
                <div class="col-md-2">
                    <label for="brand" class="form-label">Brand</label>
                    <select class="form-select" name="brand" id="brand">
                        <option value="">All Brands</option>
                        <?php foreach ($brands as $brand): ?>
                            <option value="<?= $brand['id'] ?>" <?= $brand_filter == $brand['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($brand['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-2">
                    <label for="category" class="form-label">Category</label>
                    <select class="form-select" name="category" id="category">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?= $category['id'] ?>" <?= $category_filter == $category['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($category['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-3">
                    <label for="stock_level" class="form-label">Stock Level</label>
                    <select class="form-select" name="stock_level" id="stock_level">
                        <option value="">All Levels</option>
                        <option value="out" <?= $stock_filter == 'out' ? 'selected' : '' ?>>Out of Stock</option>
                        <option value="low" <?= $stock_filter == 'low' ? 'selected' : '' ?>>Low Stock (1-5)</option>
                        <option value="medium" <?= $stock_filter == 'medium' ? 'selected' : '' ?>>Medium Stock (6-20)</option>
                        <option value="high" <?= $stock_filter == 'high' ? 'selected' : '' ?>>High Stock (20+)</option>
                    </select>
                </div>
                
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">Apply Filters</button>
                        <a href="<?php echo admin_url('products/update_stock'); ?>" class="btn btn-outline-secondary">Clear All</a>
                    </div>
                </div>
            </form>
        </div>

        <!-- Debug Information (remove in production) -->
        <?php if (isset($_GET['search']) || isset($_GET['brand']) || isset($_GET['category']) || isset($_GET['stock_level'])): ?>
        <div class="alert alert-info">
            <strong>Active Filters:</strong>
            <?php if ($search): ?>
                <span class="badge bg-primary me-1">Search: "<?= htmlspecialchars($search) ?>"</span>
            <?php endif; ?>
            <?php if ($brand_filter): ?>
                <?php 
                $brand_name = 'Unknown';
                foreach ($brands as $brand) {
                    if ($brand['id'] == $brand_filter) {
                        $brand_name = $brand['name'];
                        break;
                    }
                }
                ?>
                <span class="badge bg-secondary me-1">Brand: <?= htmlspecialchars($brand_name) ?></span>
            <?php endif; ?>
            <?php if ($category_filter): ?>
                <?php 
                $category_name = 'Unknown';
                foreach ($categories as $category) {
                    if ($category['id'] == $category_filter) {
                        $category_name = $category['name'];
                        break;
                    }
                }
                ?>
                <span class="badge bg-info me-1">Category: <?= htmlspecialchars($category_name) ?></span>
            <?php endif; ?>
            <?php if ($stock_filter): ?>
                <span class="badge bg-warning me-1">Stock: <?= ucwords(str_replace('_', ' ', $stock_filter)) ?></span>
            <?php endif; ?>
            <small class="text-muted ms-2">(<?= count($products) ?> products found)</small>
        </div>
        <?php endif; ?>

        <!-- Products List -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="mb-0">Product Inventory (<?= count($products) ?> items)</h4>
            <div>
                <button type="button" class="btn btn-success" onclick="toggleBulkUpdate()">
                    <i class="fas fa-edit"></i> Bulk Update Mode
                </button>
            </div>
        </div>

        <!-- Bulk Update Form -->
        <div id="bulkUpdateForm" style="display: none;">
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> 
                <strong>Bulk Update Mode:</strong> Modify stock levels and click "Save All Changes" when done.
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="bulk_stock_update">
                
                <?php if (empty($products)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-box fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">No products found</h5>
                        <p class="text-muted">Try adjusting your filters</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($products as $product): ?>
                        <div class="product-card">
                            <div class="row align-items-center">
                                <div class="col-md-1">
                                    <img src="<?= UPLOAD_URL ?>/<?= htmlspecialchars($product['product_image']) ?>" 
                                         alt="Product" class="product-image-small"
                                         onerror="this.src='<?= IMG_URL ?>/no-image.png'">
                                </div>
                                <div class="col-md-4">
                                    <strong><?= htmlspecialchars($product['product_name']) ?></strong><br>
                                    <small class="text-muted">
                                        <?= htmlspecialchars($product['brand_name']) ?> - 
                                        <?= htmlspecialchars($product['model_name']) ?>
                                    </small>
                                </div>
                                <div class="col-md-2">
                                    <?php
                                    $stock_class = 'stock-out';
                                    if ($product['stock'] > 20) $stock_class = 'stock-high';
                                    elseif ($product['stock'] > 5) $stock_class = 'stock-medium';
                                    elseif ($product['stock'] > 0) $stock_class = 'stock-low';
                                    ?>
                                    <span class="stock-indicator <?= $stock_class ?>"></span>
                                    Current: <strong><?= $product['stock'] ?></strong>
                                </div>
                                <div class="col-md-2">
                                    <input type="number" 
                                           class="form-control stock-input" 
                                           name="bulk_updates[<?= $product['id'] ?>][stock]" 
                                           value="<?= $product['stock'] ?>" 
                                           min="0">
                                </div>
                                <div class="col-md-3">
                                    <input type="text" 
                                           class="form-control reason-input" 
                                           name="bulk_updates[<?= $product['id'] ?>][reason]" 
                                           placeholder="Reason for change">
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <div class="text-center mt-4">
                        <button type="submit" class="btn btn-success btn-lg">
                            <i class="fas fa-save"></i> Save All Changes
                        </button>
                        <button type="button" class="btn btn-secondary btn-lg ms-2" onclick="toggleBulkUpdate()">
                            Cancel
                        </button>
                    </div>
                <?php endif; ?>
            </form>
        </div>

        <!-- Normal View -->
        <div id="normalView">
            <?php if (empty($products)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-box fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">No products found</h5>
                    <p class="text-muted">Try adjusting your filters</p>
                </div>
            <?php else: ?>
                <?php foreach ($products as $product): ?>
                    <div class="product-card">
                        <div class="row align-items-center">
                            <div class="col-md-1">
                                <img src="<?= UPLOAD_URL ?>/<?= htmlspecialchars($product['product_image']) ?>" 
                                     alt="Product" class="product-image-small"
                                     onerror="this.src='<?= IMG_URL ?>/no-image.png'">
                            </div>
                            <div class="col-md-5">
                                <strong><?= htmlspecialchars($product['product_name']) ?></strong><br>
                                <small class="text-muted">
                                    <?= htmlspecialchars($product['brand_name']) ?> - 
                                    <?= htmlspecialchars($product['model_name']) ?><br>
                                    Model: <?= htmlspecialchars($product['model_number']) ?>
                                </small>
                            </div>
                            <div class="col-md-3">
                                <?php
                                $stock_class = 'stock-out';
                                $stock_text = 'Out of Stock';
                                if ($product['stock'] > 20) {
                                    $stock_class = 'stock-high';
                                    $stock_text = 'High Stock';
                                } elseif ($product['stock'] > 5) {
                                    $stock_class = 'stock-medium';
                                    $stock_text = 'Medium Stock';
                                } elseif ($product['stock'] > 0) {
                                    $stock_class = 'stock-low';
                                    $stock_text = 'Low Stock';
                                }
                                ?>
                                <span class="stock-indicator <?= $stock_class ?>"></span>
                                <strong><?= $product['stock'] ?></strong> units
                                <br><small class="text-muted"><?= $stock_text ?></small>
                            </div>
                            <div class="col-md-3 text-end">
                                <a href="<?php echo admin_url('products/edit?id=' . $product['id']); ?>" class="btn btn-outline-primary btn-sm">
                                    <i class="fas fa-edit"></i> Edit Product
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Recent Stock Changes -->
        <?php if (!empty($recent_logs)): ?>
            <div class="logs-section">
                <h5 class="mb-3"><i class="fas fa-history"></i> Recent Stock Changes</h5>
                <?php foreach ($recent_logs as $log): ?>
                    <div class="log-item">
                        <div>
                            <strong><?= htmlspecialchars($log['product_name']) ?></strong>
                            <br><small class="text-muted"><?= htmlspecialchars($log['reason']) ?></small>
                        </div>
                        <div class="text-end">
                            <span class="<?= $log['quantity_changed'] > 0 ? 'change-positive' : ($log['quantity_changed'] < 0 ? 'change-negative' : 'change-neutral') ?>">
                                <?= $log['quantity_changed'] > 0 ? '+' : '' ?><?= $log['quantity_changed'] ?>
                            </span>
                            <br><small class="text-muted">
                                <?= date('M j, g:i A', strtotime($log['created_at'])) ?> by <?= htmlspecialchars($log['admin_name']) ?>
                            </small>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const filterForm = document.getElementById('filterForm');
    
    // Ensure filter form submission works
    if (filterForm) {
        // Add change event listeners to dropdowns for immediate filtering
        const brandSelect = document.getElementById('brand');
        const categorySelect = document.getElementById('category');
        const stockSelect = document.getElementById('stock_level');
        
        if (brandSelect) {
            brandSelect.addEventListener('change', function() {
                showLoading();
                filterForm.submit();
            });
        }
        
        if (categorySelect) {
            categorySelect.addEventListener('change', function() {
                showLoading();
                filterForm.submit();
            });
        }
        
        if (stockSelect) {
            stockSelect.addEventListener('change', function() {
                showLoading();
                filterForm.submit();
            });
        }
        
        function showLoading() {
            const submitBtn = filterForm.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Filtering...';
                submitBtn.disabled = true;
            }
        }
        
        // Add real-time search functionality
        const searchInput = document.getElementById('search');
        if (searchInput) {
            let searchTimeout;
            searchInput.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    // Auto-submit form after 1 second of no typing
                    if (this.value.length >= 2 || this.value.length === 0) {
                        showLoading();
                        filterForm.submit();
                    }
                }, 1000);
            });
        }
    }
});

function toggleBulkUpdate() {
    const bulkForm = document.getElementById('bulkUpdateForm');
    const normalView = document.getElementById('normalView');
    
    if (bulkForm.style.display === 'none') {
        bulkForm.style.display = 'block';
        normalView.style.display = 'none';
    } else {
        bulkForm.style.display = 'none';
        normalView.style.display = 'block';
    }
}
</script>

<?php
include INCLUDES_PATH . '/templates/admin_footer.php';
?>
