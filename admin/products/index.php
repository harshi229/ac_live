<?php
require_once dirname(dirname(__DIR__)) . '/includes/config/init.php';

// Ensure the admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: ' . admin_url('login'));
    exit();
}

include INCLUDES_PATH . '/templates/admin_header.php';

// Handle bulk actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['bulk_action']) && isset($_POST['selected_products'])) {
        $action = $_POST['bulk_action'];
        $product_ids = $_POST['selected_products'];
        
        try {
            switch ($action) {
                case 'activate':
                    $stmt = $pdo->prepare("UPDATE products SET status = 'active' WHERE id IN (" . str_repeat('?,', count($product_ids) - 1) . "?)");
                    $stmt->execute($product_ids);
                    $success_message = count($product_ids) . " products activated successfully!";
                    break;
                    
                case 'deactivate':
                    $stmt = $pdo->prepare("UPDATE products SET status = 'inactive' WHERE id IN (" . str_repeat('?,', count($product_ids) - 1) . "?)");
                    $stmt->execute($product_ids);
                    $success_message = count($product_ids) . " products deactivated successfully!";
                    break;
                    
                case 'delete':
                    $stmt = $pdo->prepare("DELETE FROM products WHERE id IN (" . str_repeat('?,', count($product_ids) - 1) . "?)");
                    $stmt->execute($product_ids);
                    $success_message = count($product_ids) . " products deleted successfully!";
                    break;
            }
        } catch (PDOException $e) {
            $error_message = "Error performing bulk action: " . $e->getMessage();
        }
    }
    
    // Handle individual status toggle
    if (isset($_POST['toggle_status'])) {
        $product_id = intval($_POST['product_id']);
        $new_status = $_POST['new_status'];
        
        try {
            $stmt = $pdo->prepare("UPDATE products SET status = ? WHERE id = ?");
            $stmt->execute([$new_status, $product_id]);
            $success_message = "Product status updated successfully!";
        } catch (PDOException $e) {
            $error_message = "Error updating product status: " . $e->getMessage();
        }
    }
}

// Get filter parameters
$brand_filter = isset($_GET['brand']) ? intval($_GET['brand']) : '';
$category_filter = isset($_GET['category']) ? intval($_GET['category']) : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$sort_by = isset($_GET['sort']) ? $_GET['sort'] : 'newest';

// Debug: Log filter parameters
error_log("Filter parameters - Brand: $brand_filter, Category: $category_filter, Status: $status_filter, Search: $search, Sort: $sort_by");

// Build query conditions
$conditions = ["1=1"];
$params = [];

if ($brand_filter) {
    $conditions[] = "p.brand_id = ?";
    $params[] = $brand_filter;
}

if ($category_filter) {
    $conditions[] = "p.category_id = ?";
    $params[] = $category_filter;
}

if ($status_filter) {
    $conditions[] = "p.status = ?";
    $params[] = $status_filter;
}

if ($search) {
    $conditions[] = "(p.product_name LIKE ? OR p.model_name LIKE ? OR p.model_number LIKE ? OR p.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

// Build ORDER BY clause
$order_clause = "ORDER BY ";
switch ($sort_by) {
    case 'name':
        $order_clause .= "p.product_name ASC";
        break;
    case 'price_low':
        $order_clause .= "p.price ASC";
        break;
    case 'price_high':
        $order_clause .= "p.price DESC";
        break;
    case 'stock_low':
        $order_clause .= "p.stock ASC";
        break;
    case 'newest':
    default:
        $order_clause .= "p.created_at DESC";
        break;
}

try {
    // Main products query
    $sql = "SELECT p.*, 
                   b.name as brand_name,
                   c.name as category_name,
                   sc.name as subcategory_name
            FROM products p
            LEFT JOIN brands b ON p.brand_id = b.id
            LEFT JOIN categories c ON p.category_id = c.id
            LEFT JOIN sub_categories sc ON p.sub_category_id = sc.id
            WHERE " . implode(' AND ', $conditions) . "
            $order_clause";
    
    // Debug: Log the query and parameters
    error_log("Products Query: " . $sql);
    error_log("Query Parameters: " . print_r($params, true));
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get filter data
    $brands = $pdo->query("SELECT * FROM brands WHERE status = 'active' ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
    $categories = $pdo->query("SELECT * FROM categories WHERE status = 'active' ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
    
    // Debug: Log brands and categories
    error_log("Brands found: " . count($brands));
    error_log("Categories found: " . count($categories));
    
    // Get statistics
    $stats = [
        'total' => $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn(),
        'active' => $pdo->query("SELECT COUNT(*) FROM products WHERE status = 'active'")->fetchColumn(),
        'inactive' => $pdo->query("SELECT COUNT(*) FROM products WHERE status = 'inactive'")->fetchColumn(),
        'low_stock' => $pdo->query("SELECT COUNT(*) FROM products WHERE stock < 5")->fetchColumn()
    ];
    
} catch (PDOException $e) {
    $error_message = "Error fetching products: " . $e->getMessage();
    $products = [];
    $brands = [];
    $categories = [];
    $stats = ['total' => 0, 'active' => 0, 'inactive' => 0, 'low_stock' => 0];
}
?>

<style>
    /* Products page specific overrides - minimal custom styles */
    .product-image-small {
        width: 50px;
        height: 50px;
        object-fit: cover;
        border-radius: var(--radius-md);
        border: 1px solid var(--border-light);
    }
    
    .product-details {
        line-height: 1.4;
    }
    
    .product-name {
        font-weight: 600;
        color: var(--text-primary);
        margin-bottom: var(--spacing-xs);
    }
    
    .product-model {
        color: var(--text-secondary);
        font-size: var(--text-sm);
    }
    
    .stock-indicator {
        display: inline-block;
        width: 8px;
        height: 8px;
        border-radius: 50%;
        margin-right: var(--spacing-xs);
    }
    
    .stock-good { background-color: var(--success-color); }
    .stock-low { background-color: var(--warning-color); }
    .stock-out { background-color: var(--danger-color); }
    
    .bulk-actions {
        display: none;
    }
    
    .bulk-actions.show {
        display: block;
        animation: slideDown 0.3s ease-out;
    }
    
    /* Clean styling for products table card */
    #products-table-card .card-body h5 {
        color: var(--text-primary);
        font-weight: 600;
        margin-bottom: var(--spacing-lg);
    }
    
    /* Restore full admin theme table styling */
    #products-table-card .admin-table {
        width: 100%;
        border-collapse: collapse;
        font-size: var(--text-sm);
        border-radius: var(--radius-lg);
        overflow: hidden;
        box-shadow: none;
    }
    
    #products-table-card .admin-table th {
        background: linear-gradient(135deg, var(--bg-tertiary) 0%, var(--bg-secondary) 100%);
        color: var(--text-secondary);
        font-weight: 600;
        padding: var(--spacing-lg) var(--spacing-md);
        text-align: left;
        border-bottom: 2px solid var(--border-light);
        font-size: var(--text-xs);
        text-transform: uppercase;
        letter-spacing: 0.05em;
        position: relative;
        white-space: nowrap;
    }
    
    #products-table-card .admin-table th.sortable {
        cursor: pointer;
        user-select: none;
        transition: all var(--transition-fast);
    }
    
    #products-table-card .admin-table th.sortable:hover {
        background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
        color: var(--text-white);
    }
    
    #products-table-card .th-content {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: var(--spacing-sm);
    }
    
    #products-table-card .sort-icon {
        opacity: 0.5;
        font-size: var(--text-xs);
        transition: all var(--transition-fast);
    }
    
    #products-table-card .admin-table th.sortable:hover .sort-icon {
        opacity: 1;
        color: var(--text-white);
    }
    
    #products-table-card .admin-table td {
        padding: var(--spacing-lg) var(--spacing-md);
        border-bottom: 1px solid var(--border-light);
        vertical-align: middle;
        transition: all var(--transition-fast);
    }
    
    #products-table-card .admin-table tbody tr {
        transition: all var(--transition-fast);
        position: relative;
    }
    
    #products-table-card .admin-table tbody tr:hover {
        background: linear-gradient(135deg, var(--bg-tertiary) 0%, rgba(99, 102, 241, 0.05) 100%);
        transform: translateX(4px);
        box-shadow: var(--shadow-sm);
    }
    
    #products-table-card .admin-table tbody tr:nth-child(even) {
        background: rgba(248, 250, 252, 0.3);
    }
    
    #products-table-card .admin-table tbody tr:nth-child(even):hover {
        background: linear-gradient(135deg, var(--bg-tertiary) 0%, rgba(99, 102, 241, 0.08) 100%);
    }
    
    #products-table-card .admin-table tbody tr:last-child td {
        border-bottom: none;
    }
    
    @keyframes slideDown {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
</style>

<div class="fade-in">
    <div class="admin-card mb-4">
        <div class="card-body text-center">
            <h1 class="mb-0">Product Management</h1>
            <p class="text-muted mt-2">Manage your AC products inventory and details</p>
        </div>
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
    <div class="stats-grid">
        <div class="stat-card stat-card-products" data-aos="fade-up" data-aos-delay="100">
            <div class="stat-header">
                <div class="stat-icon">
                    <i class="fas fa-boxes"></i>
                    <div class="stat-icon-bg"></div>
                </div>
            </div>
            <div class="stat-content">
                <h3 class="stat-value"><?php echo $stats['total']; ?></h3>
                <p class="stat-label">Total Products</p>
                <div class="stat-subtitle">All inventory items</div>
            </div>
        </div>

        <div class="stat-card stat-card-orders success" data-aos="fade-up" data-aos-delay="200">
            <div class="stat-header">
                <div class="stat-icon success">
                    <i class="fas fa-check-circle"></i>
                    <div class="stat-icon-bg success"></div>
                </div>
            </div>
            <div class="stat-content">
                <h3 class="stat-value"><?php echo $stats['active']; ?></h3>
                <p class="stat-label">Active Products</p>
                <div class="stat-subtitle">Currently available</div>
            </div>
        </div>

        <div class="stat-card stat-card-users warning" data-aos="fade-up" data-aos-delay="300">
            <div class="stat-header">
                <div class="stat-icon warning">
                    <i class="fas fa-pause-circle"></i>
                    <div class="stat-icon-bg warning"></div>
                </div>
            </div>
            <div class="stat-content">
                <h3 class="stat-value"><?php echo $stats['inactive']; ?></h3>
                <p class="stat-label">Inactive Products</p>
                <div class="stat-subtitle">Currently unavailable</div>
                </div>
            </div>

        <div class="stat-card stat-card-revenue info" data-aos="fade-up" data-aos-delay="400">
            <div class="stat-header">
                <div class="stat-icon info">
                    <i class="fas fa-exclamation-triangle"></i>
                    <div class="stat-icon-bg info"></div>
                </div>
            </div>
            <div class="stat-content">
                <h3 class="stat-value"><?php echo $stats['low_stock']; ?></h3>
                <p class="stat-label">Low Stock</p>
                <div class="stat-subtitle">Need restocking</div>
                </div>
            </div>
        </div>

        <!-- Filter Section -->
    <div class="admin-card mb-4">
        <div class="card-header">
            <h5 class="card-title">
                <i class="fas fa-filter"></i>
                Filter & Search Products
            </h5>
        </div>
        <div class="card-body">
            <form method="GET" action="<?php echo admin_url('products'); ?>" id="filterForm" autocomplete="off">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label for="search" class="form-label">Search Products</label>
                        <input type="text" 
                               class="form-control" 
                               name="search" 
                               id="search" 
                               value="<?= htmlspecialchars($search) ?>" 
                               placeholder="Search by name, model...">
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
                    <div class="col-md-2">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" name="status" id="status">
                            <option value="">All Status</option>
                            <option value="active" <?= $status_filter == 'active' ? 'selected' : '' ?>>Active</option>
                            <option value="inactive" <?= $status_filter == 'inactive' ? 'selected' : '' ?>>Inactive</option>
                            <option value="out_of_stock" <?= $status_filter == 'out_of_stock' ? 'selected' : '' ?>>Out of Stock</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="sort" class="form-label">Sort By</label>
                        <select class="form-select" name="sort" id="sort">
                            <option value="newest" <?= $sort_by == 'newest' ? 'selected' : '' ?>>Newest First</option>
                            <option value="name" <?= $sort_by == 'name' ? 'selected' : '' ?>>Name A-Z</option>
                            <option value="price_low" <?= $sort_by == 'price_low' ? 'selected' : '' ?>>Price Low-High</option>
                            <option value="price_high" <?= $sort_by == 'price_high' ? 'selected' : '' ?>>Price High-Low</option>
                            <option value="stock_low" <?= $sort_by == 'stock_low' ? 'selected' : '' ?>>Low Stock First</option>
                        </select>
                    </div>
                    <div class="col-md-1">
                        <label class="form-label">&nbsp;</label>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i>
                        </button>
                    </div>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-12">
                        <a href="<?php echo admin_url('products'); ?>" class="btn btn-outline-secondary">
                            <i class="fas fa-times"></i> Clear Filters
                        </a>
                    </div>
                </div>
            </form>
        </div>
        </div>

        <!-- Debug Information (remove in production) -->
        <?php if (isset($_GET['search']) || isset($_GET['brand']) || isset($_GET['category']) || isset($_GET['status']) || isset($_GET['sort'])): ?>
        <div class="alert alert-info">
            <strong>Active Filters:</strong>
        <div class="filter-badges">
            <?php if ($search): ?>
                <span class="badge bg-primary filter-badge">Search: "<?= htmlspecialchars($search) ?>"</span>
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
                <span class="badge bg-secondary filter-badge">Brand: <?= htmlspecialchars($brand_name) ?></span>
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
                <span class="badge bg-info filter-badge">Category: <?= htmlspecialchars($category_name) ?></span>
            <?php endif; ?>
            <?php if ($status_filter): ?>
                <span class="badge bg-warning filter-badge">Status: <?= ucfirst($status_filter) ?></span>
            <?php endif; ?>
            <?php if ($sort_by && $sort_by !== 'newest'): ?>
                <span class="badge bg-success filter-badge">Sort: <?= ucwords(str_replace('_', ' ', $sort_by)) ?></span>
            <?php endif; ?>
        </div>
        <small class="text-muted">(<?= count($products) ?> products found)</small>
        </div>
        <?php endif; ?>

        <!-- Action Buttons -->
    <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <a href="<?php echo admin_url('products/add'); ?>" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Add New Product
                </a>
                <a href="<?php echo admin_url('products/update_stock'); ?>" class="btn btn-outline-warning">
                    <i class="fas fa-edit"></i> Update Stock
                </a>
            </div>
        <div class="text-muted">
                <strong><?php echo count($products); ?></strong> products found
            </div>
        </div>

        <!-- Bulk Actions -->
    <div class="admin-card mb-4" id="bulkActions" style="display: none;">
        <div class="card-body">
            <form method="POST" id="bulkForm">
                <div class="row align-items-center">
                    <div class="col-md-3">
                        <select class="form-select" name="bulk_action" required>
                            <option value="">Choose Action</option>
                            <option value="activate">Activate Selected</option>
                            <option value="deactivate">Deactivate Selected</option>
                            <option value="delete">Delete Selected</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-warning" onclick="return confirm('Are you sure you want to perform this bulk action?')">
                            Apply to Selected
                        </button>
                    </div>
                    <div class="col-md-6">
                        <span id="selectedCount" class="text-muted">0</span> products selected
                    </div>
                </div>
            </form>
        </div>
        </div>

        <!-- Products Table -->
    <div class="admin-card" id="products-table-card">
        <div class="card-body">
            <?php if (empty($products)): ?>
                <div class="empty-state">
                    <i class="fas fa-box-open"></i>
                    <h5>No products found</h5>
                    <p>Try adjusting your filters or <a href="<?php echo admin_url('products/add'); ?>">add a new product</a></p>
                </div>
            <?php else: ?>
                <!-- Table Title -->
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">
                        <i class="fas fa-boxes"></i>
                        Products List
                    </h5>
                    <div class="d-flex align-items-center gap-2">
                        <input type="checkbox" id="selectAll" class="form-check-input">
                        <small class="text-muted">Select All</small>
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table class="admin-table">
                    <thead>
                        <tr>
                            <th class="sortable" data-column="product_image" width="8%">
                                <div class="th-content">
                                    <span>Image</span>
                                    <i class="fas fa-sort sort-icon"></i>
                                </div>
                            </th>
                            <th class="sortable" data-column="product_name" width="25%">
                                <div class="th-content">
                                    <span>Product Details</span>
                                    <i class="fas fa-sort sort-icon"></i>
                                </div>
                            </th>
                            <th class="sortable" data-column="brand_name" width="10%">
                                <div class="th-content">
                                    <span>Brand</span>
                                    <i class="fas fa-sort sort-icon"></i>
                                </div>
                            </th>
                            <th class="sortable" data-column="price" width="8%">
                                <div class="th-content">
                                    <span>Price</span>
                                    <i class="fas fa-sort sort-icon"></i>
                                </div>
                            </th>
                            <th class="sortable" data-column="stock" width="8%">
                                <div class="th-content">
                                    <span>Stock</span>
                                    <i class="fas fa-sort sort-icon"></i>
                                </div>
                            </th>
                            <th class="sortable" data-column="status" width="8%">
                                <div class="th-content">
                                    <span>Status</span>
                                    <i class="fas fa-sort sort-icon"></i>
                                </div>
                            </th>
                            <th class="sortable" data-column="created_at" width="10%">
                                <div class="th-content">
                                    <span>Created</span>
                                    <i class="fas fa-sort sort-icon"></i>
                                </div>
                            </th>
                            <th width="20%">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $product): ?>
                            <tr>
                                <td>
                                    <img src="<?= UPLOAD_URL ?>/<?= htmlspecialchars($product['product_image'] ?? '') ?>" 
                                         alt="Product Image" 
                                         class="product-image-small"
                                         onerror="this.src='<?= IMG_URL ?>/no-image.png'">
                                </td>
                                <td>
                                    <div class="product-details">
                                        <div class="product-name"><?= htmlspecialchars($product['product_name'] ?? '') ?></div>
                                        <div class="product-model">
                                        Model: <?= htmlspecialchars($product['model_name'] ?? '') ?><br>
                                        <?= htmlspecialchars($product['model_number'] ?? '') ?>
                                        </div>
                                    </div>
                                </td>
                                <td><?= htmlspecialchars($product['brand_name'] ?? '') ?></td>
                                <td>
                                    <?php if ($product['original_price'] && $product['original_price'] > $product['price']): ?>
                                        <!-- Discount pricing display -->
                                        <div class="pricing-info">
                                            <div class="current-price text-success fw-bold">₹<?= number_format($product['price'], 0) ?></div>
                                            <div class="original-price text-muted text-decoration-line-through small">₹<?= number_format($product['original_price'], 0) ?></div>
                                            <div class="discount-badge">
                                                <span class="badge bg-danger"><?= number_format($product['discount_percentage'], 1) ?>% OFF</span>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <!-- Regular pricing -->
                                        <div class="pricing-info">
                                            <div class="current-price fw-bold">₹<?= number_format($product['price'], 0) ?></div>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="stock-indicator <?= $product['stock'] > 10 ? 'stock-good' : ($product['stock'] > 0 ? 'stock-low' : 'stock-out') ?>"></span>
                                    <?= $product['stock'] ?>
                                </td>
                                <td>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                                        <input type="hidden" name="new_status" value="<?= $product['status'] == 'active' ? 'inactive' : 'active' ?>">
                                        <button type="submit" name="toggle_status" 
                                                class="badge badge-<?= $product['status'] == 'active' ? 'success' : ($product['status'] == 'inactive' ? 'danger' : 'warning') ?>"
                                                onclick="return confirm('Change product status?')">
                                            <?= ucfirst($product['status']) ?>
                                        </button>
                                    </form>
                                </td>
                                <td>
                                    <small><?= date('M j, Y', strtotime($product['created_at'])) ?></small>
                                </td>
                                <td>
                                    <div class="table-actions">
                                        <button class="btn-table-action" 
                                                onclick="window.location.href='<?php echo user_url('products/details?id=' . $product['id']); ?>'"
                                           title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="btn-table-action" 
                                                onclick="window.location.href='<?php echo admin_url('products/edit?id=' . $product['id']); ?>'"
                                           title="Edit Product">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn-table-action" 
                                                onclick="if(confirm('Are you sure you want to delete this product?')) window.location.href='<?php echo admin_url('products/delete?id=' . $product['id']); ?>'"
                                                title="Delete Product">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const selectAll = document.getElementById('selectAll');
    const productCheckboxes = document.querySelectorAll('.product-checkbox');
    const bulkActions = document.getElementById('bulkActions');
    const selectedCount = document.getElementById('selectedCount');
    const bulkForm = document.getElementById('bulkForm');
    const filterForm = document.getElementById('filterForm');
    
    // Ensure filter form submission works
    if (filterForm) {
        // Add change event listeners to dropdowns for immediate filtering
        const brandSelect = document.getElementById('brand');
        const categorySelect = document.getElementById('category');
        const statusSelect = document.getElementById('status');
        const sortSelect = document.getElementById('sort');
        
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
        
        if (statusSelect) {
            statusSelect.addEventListener('change', function() {
                showLoading();
                filterForm.submit();
            });
        }
        
        if (sortSelect) {
            sortSelect.addEventListener('change', function() {
                showLoading();
                filterForm.submit();
            });
        }
        
        function showLoading() {
            const submitBtn = filterForm.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Applying...';
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
                        filterForm.submit();
                    }
                }, 1000);
            });
        }
    }

    // Select all functionality
    selectAll.addEventListener('change', function() {
        productCheckboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
        });
        updateBulkActions();
    });

    // Individual checkbox change
    productCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updateBulkActions);
    });

    function updateBulkActions() {
        const checkedBoxes = document.querySelectorAll('.product-checkbox:checked');
        const count = checkedBoxes.length;
        
        selectedCount.textContent = count;
        
        if (count > 0) {
            bulkActions.classList.add('show');
            
            // Add hidden inputs for selected products to bulk form
            const existingInputs = bulkForm.querySelectorAll('input[name="selected_products[]"]');
            existingInputs.forEach(input => input.remove());
            
            checkedBoxes.forEach(checkbox => {
                const hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.name = 'selected_products[]';
                hiddenInput.value = checkbox.value;
                bulkForm.appendChild(hiddenInput);
            });
        } else {
            bulkActions.classList.remove('show');
        }
        
        // Update select all checkbox state
        selectAll.indeterminate = count > 0 && count < productCheckboxes.length;
        selectAll.checked = count === productCheckboxes.length;
    }
});
</script>

<?php
include INCLUDES_PATH . '/templates/admin_footer.php';
?>
