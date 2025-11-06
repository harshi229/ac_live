<?php
require_once __DIR__ . '/../../includes/config/init.php';

// Redirect if admin not logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: ' . admin_url('login'));
    exit();
}

include INCLUDES_PATH . '/templates/admin_header.php';

// Handle filters
$category_filter = $_GET['category'] ?? '';
$brand_filter = $_GET['brand'] ?? '';
$status_filter = $_GET['status'] ?? '';
$low_stock_threshold = $_GET['threshold'] ?? 10;

// Build WHERE clause based on filters
$where_conditions = [];
$params = [];

if ($category_filter) {
    $where_conditions[] = "c.id = ?";
    $params[] = $category_filter;
}

if ($brand_filter) {
    $where_conditions[] = "b.id = ?";
    $params[] = $brand_filter;
}

if ($status_filter) {
    $where_conditions[] = "p.status = ?";
    $params[] = $status_filter;
}

$where_clause = '';
if (!empty($where_conditions)) {
    $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
}

// Query stock data
$query = $pdo->prepare("
    SELECT 
        p.id,
        p.product_name,
        p.model_name,
        p.model_number,
        b.name as brand_name,
        c.name as category_name,
        sc.name as sub_category_name,
        p.capacity,
        p.price,
        p.stock,
        p.status,
        p.created_at,
        p.updated_at,
        CASE 
            WHEN p.stock = 0 THEN 'Out of Stock'
            WHEN p.stock <= ? THEN 'Low Stock'
            ELSE 'In Stock'
        END as stock_status
    FROM products p
    JOIN brands b ON p.brand_id = b.id
    JOIN categories c ON p.category_id = c.id
    JOIN sub_categories sc ON p.sub_category_id = sc.id
    $where_clause
    ORDER BY p.stock ASC, p.product_name ASC
");

$query_params = array_merge([$low_stock_threshold], $params);
$query->execute($query_params);
$products = $query->fetchAll(PDO::FETCH_ASSOC);

// Get categories for filter dropdown
$categories = $pdo->query("SELECT id, name FROM categories WHERE status = 'active' ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

// Get brands for filter dropdown
$brands = $pdo->query("SELECT id, name FROM brands WHERE status = 'active' ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

// Calculate summary statistics
$total_products = count($products);
$out_of_stock = count(array_filter($products, fn($p) => $p['stock'] == 0));
$low_stock = count(array_filter($products, fn($p) => $p['stock'] > 0 && $p['stock'] <= $low_stock_threshold));
$in_stock = $total_products - $out_of_stock - $low_stock;
$total_stock_value = array_sum(array_map(fn($p) => $p['stock'] * $p['price'], $products));
?>

<style>
    .stock-card {
        border-left: 4px solid #007bff;
        transition: transform 0.2s;
    }
    .stock-card:hover {
        transform: translateY(-2px);
    }
    .out-of-stock {
        border-left-color: #dc3545;
    }
    .low-stock {
        border-left-color: #ffc107;
    }
    .in-stock {
        border-left-color: #28a745;
    }
    .stock-badge {
        font-size: 0.75rem;
        padding: 0.25rem 0.5rem;
    }
    @media print {
        .no-print { display: none !important; }
    }
</style>
<main class="container mt-4">
    <h2 class="mb-4"><i class="fas fa-chart-bar"></i> Stock Report</h2>

    <!-- Filter Form -->
    <div class="card mb-4 no-print">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-filter"></i> Filters</h5>
        </div>
        <div class="card-body">
            <form class="row g-3" method="GET">
                <div class="col-md-3">
                    <label class="form-label">Category</label>
                    <select class="form-select" name="category">
                        <option value="">All Categories</option>
                        <?php foreach($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= $category_filter == $cat['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Brand</label>
                    <select class="form-select" name="brand">
                        <option value="">All Brands</option>
                        <?php foreach($brands as $brand): ?>
                            <option value="<?= $brand['id'] ?>" <?= $brand_filter == $brand['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($brand['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select class="form-select" name="status">
                        <option value="">All Status</option>
                        <option value="active" <?= $status_filter == 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="inactive" <?= $status_filter == 'inactive' ? 'selected' : '' ?>>Inactive</option>
                        <option value="out_of_stock" <?= $status_filter == 'out_of_stock' ? 'selected' : '' ?>>Out of Stock</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Low Stock Threshold</label>
                    <input type="number" class="form-control" name="threshold" value="<?= htmlspecialchars($low_stock_threshold) ?>" min="1">
                </div>
                <div class="col-md-2 align-self-end">
                    <button type="submit" class="btn btn-primary me-2"><i class="fas fa-search"></i> Filter</button>
                    <a href="stock_report.php" class="btn btn-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card stock-card">
                <div class="card-body text-center">
                    <i class="fas fa-boxes fa-2x text-primary mb-2"></i>
                    <h5>Total Products</h5>
                    <h3 class="text-primary"><?= $total_products ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stock-card in-stock">
                <div class="card-body text-center">
                    <i class="fas fa-check-circle fa-2x text-success mb-2"></i>
                    <h5>In Stock</h5>
                    <h3 class="text-success"><?= $in_stock ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stock-card low-stock">
                <div class="card-body text-center">
                    <i class="fas fa-exclamation-triangle fa-2x text-warning mb-2"></i>
                    <h5>Low Stock</h5>
                    <h3 class="text-warning"><?= $low_stock ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stock-card out-of-stock">
                <div class="card-body text-center">
                    <i class="fas fa-times-circle fa-2x text-danger mb-2"></i>
                    <h5>Out of Stock</h5>
                    <h3 class="text-danger"><?= $out_of_stock ?></h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Total Stock Value -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body text-center">
                    <h5>Total Stock Value</h5>
                    <h2 class="text-success">₹<?= number_format($total_stock_value, 2) ?></h2>
                </div>
            </div>
        </div>
    </div>

    <!-- Export Options -->
    <div class="mb-3 no-print">
        <button onclick="window.print()" class="btn btn-secondary">
            <i class="fas fa-print"></i> Print Report
        </button>
        <button onclick="exportToCSV()" class="btn btn-success">
            <i class="fas fa-file-csv"></i> Export to CSV
        </button>
    </div>

    <!-- Detailed Stock Table -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-list"></i> Detailed Stock Information</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover" id="stockTable">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Product Name</th>
                            <th>Model</th>
                            <th>Brand</th>
                            <th>Category</th>
                            <th>Capacity</th>
                            <th>Price</th>
                            <th>Stock Qty</th>
                            <th>Stock Value</th>
                            <th>Stock Status</th>
                            <th>Product Status</th>
                            <th>Last Updated</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if ($products): ?>
                        <?php foreach ($products as $product): ?>
                            <tr>
                                <td><?= $product['id'] ?></td>
                                <td>
                                    <strong><?= htmlspecialchars($product['product_name']) ?></strong>
                                    <?php if ($product['model_number']): ?>
                                        <br><small class="text-muted"><?= htmlspecialchars($product['model_number']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($product['model_name'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($product['brand_name']) ?></td>
                                <td>
                                    <?= htmlspecialchars($product['category_name']) ?>
                                    <?php if ($product['sub_category_name']): ?>
                                        <br><small class="text-muted"><?= htmlspecialchars($product['sub_category_name']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($product['capacity']) ?></td>
                                <td>₹<?= number_format($product['price'], 2) ?></td>
                                <td class="text-center">
                                    <span class="<?= $product['stock'] == 0 ? 'text-danger' : ($product['stock'] <= $low_stock_threshold ? 'text-warning' : 'text-success') ?>">
                                        <?= $product['stock'] ?>
                                    </span>
                                </td>
                                <td>₹<?= number_format($product['stock'] * $product['price'], 2) ?></td>
                                <td>
                                    <?php
                                    $status_class = '';
                                    switch($product['stock_status']) {
                                        case 'Out of Stock':
                                            $status_class = 'bg-danger';
                                            break;
                                        case 'Low Stock':
                                            $status_class = 'bg-warning text-dark';
                                            break;
                                        case 'In Stock':
                                            $status_class = 'bg-success';
                                            break;
                                    }
                                    ?>
                                    <span class="badge stock-badge <?= $status_class ?>"><?= $product['stock_status'] ?></span>
                                </td>
                                <td>
                                    <span class="badge <?= $product['status'] == 'active' ? 'bg-success' : 'bg-secondary' ?>">
                                        <?= ucfirst($product['status']) ?>
                                    </span>
                                </td>
                                <td><?= date('d-M-Y', strtotime($product['updated_at'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="12" class="text-center">No products found matching the selected criteria.</td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<!-- Bootstrap JS already loaded in admin footer -->
<script>
function exportToCSV() {
    const table = document.getElementById('stockTable');
    let csv = [];
    
    // Get headers
    const headers = [];
    const headerRow = table.querySelector('thead tr');
    headerRow.querySelectorAll('th').forEach(th => {
        headers.push(th.textContent.trim());
    });
    csv.push(headers.join(','));
    
    // Get data rows
    const rows = table.querySelectorAll('tbody tr');
    rows.forEach(row => {
        const rowData = [];
        row.querySelectorAll('td').forEach(td => {
            // Clean the text content and handle commas
            let text = td.textContent.trim().replace(/,/g, ';').replace(/\n/g, ' ');
            rowData.push('"' + text + '"');
        });
        if (rowData.length > 0 && !rowData[0].includes('No products found')) {
            csv.push(rowData.join(','));
        }
    });
    
    // Download CSV
    const csvContent = csv.join('\n');
    const blob = new Blob([csvContent], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'stock_report_' + new Date().toISOString().split('T')[0] + '.csv';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    window.URL.revokeObjectURL(url);
}
</script>

<?php include INCLUDES_PATH . '/templates/admin_footer.php'; ?>
