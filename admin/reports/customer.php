<?php
require_once __DIR__ . '/../../includes/config/init.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: ' . admin_url('login'));
    exit();
}

include INCLUDES_PATH . '/templates/admin_header.php';

// Handle filters
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');
$city_filter = $_GET['city'] ?? '';
$status_filter = $_GET['status'] ?? '';

// Build WHERE clause for users (date of user creation)
$where_conditions = ["DATE(u.created_at) BETWEEN ? AND ?"];
$params = [$start_date, $end_date];

if ($city_filter) {
    $where_conditions[] = "u.city LIKE ?";
    $params[] = "%$city_filter%";
}

if ($status_filter) {
    $where_conditions[] = "u.status = ?";
    $params[] = $status_filter;
}

$where_clause = 'WHERE ' . implode(' AND ', $where_conditions);

// Query customer data with order & service statistics filtered by date
$query = $pdo->prepare("
    SELECT 
        u.id,
        u.username,
        u.email,
        u.phone_number,
        u.address,
        u.city,
        u.pincode,
        u.status,
        u.created_at,
        COUNT(DISTINCT o.id) as total_orders,
        COALESCE(SUM(o.total_price), 0) as total_spent,
        MAX(o.created_at) as last_order_date,
        COUNT(DISTINCT CASE WHEN o.order_status = 'Delivered' THEN o.id END) as completed_orders,
        COUNT(DISTINCT s.id) as total_services,
        AVG(CASE WHEN r.rating IS NOT NULL THEN r.rating END) as avg_rating
    FROM users u
    LEFT JOIN orders o 
        ON u.id = o.user_id 
        AND DATE(o.created_at) BETWEEN ? AND ?  -- filter orders by date
    LEFT JOIN services s 
        ON u.id = s.user_id
        AND DATE(s.created_at) BETWEEN ? AND ?  -- filter services by date
    LEFT JOIN reviews r 
        ON u.id = r.user_id
    $where_clause
    GROUP BY u.id
    ORDER BY total_spent DESC, u.created_at DESC
");

// Merge user filter params with order/service date filters
$query->execute(array_merge($params, [$start_date, $end_date, $start_date, $end_date]));
$customers = $query->fetchAll(PDO::FETCH_ASSOC);


// Get cities for filter
$cities = $pdo->query("SELECT DISTINCT city FROM users WHERE city IS NOT NULL AND city != '' ORDER BY city")->fetchAll(PDO::FETCH_COLUMN);

// Calculate summary statistics
$total_customers = count($customers);
$active_customers = count(array_filter($customers, fn($c) => $c['status'] == 'active'));
$customers_with_orders = count(array_filter($customers, fn($c) => $c['total_orders'] > 0));
$total_revenue = array_sum(array_column($customers, 'total_spent'));
$avg_order_value = $customers_with_orders > 0 ? $total_revenue / array_sum(array_column($customers, 'total_orders')) : 0;

// Top customers by spending
$top_customers = array_slice(array_filter($customers, fn($c) => $c['total_spent'] > 0), 0, 5);

// Customer acquisition by month (for the chart)
$monthly_customers = $pdo->prepare("
    SELECT 
        DATE_FORMAT(created_at, '%Y-%m') as month,
        COUNT(*) as new_customers
    FROM users 
    WHERE DATE(created_at) BETWEEN ? AND ?
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY month ASC
");
$monthly_customers->execute([$start_date, $end_date]);
$monthly_data = $monthly_customers->fetchAll(PDO::FETCH_ASSOC);
?>

<style>
    .customer-card {
        border-left: 4px solid #007bff;
        transition: transform 0.2s;
    }
    .customer-card:hover {
        transform: translateY(-2px);
    }
    .high-value {
        border-left-color: #28a745;
    }
    .medium-value {
        border-left-color: #ffc107;
    }
    .low-value {
        border-left-color: #dc3545;
    }
    .customer-badge {
        font-size: 0.75rem;
        padding: 0.25rem 0.5rem;
    }
    .chart-container {
        position: relative;
        height: 300px;
    }
    @media print {
        .no-print { display: none !important; }
    }
</style>
<main class="container mt-4">
    <h2 class="mb-4"><i class="fas fa-users"></i> Customer Report</h2>

    <!-- Filter Form -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-filter"></i> Filters</h5>
        </div>
        <div class="card-body">
            <form class="row g-3" method="GET">
                <div class="col-md-3">
                    <label class="form-label">Start Date</label>
                    <input type="date" class="form-control" name="start_date" value="<?= htmlspecialchars($start_date) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">End Date</label>
                    <input type="date" class="form-control" name="end_date" value="<?= htmlspecialchars($end_date) ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">City</label>
                    <select class="form-select" name="city">
                        <option value="">All Cities</option>
                        <?php foreach($cities as $city): ?>
                            <option value="<?= htmlspecialchars($city) ?>" <?= $city_filter == $city ? 'selected' : '' ?>>
                                <?= htmlspecialchars($city) ?>
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
                    </select>
                </div>
                <div class="col-md-2 align-self-end">
                    <button type="submit" class="btn btn-primary me-2"><i class="fas fa-search"></i> Filter</button>
                    <a href="customer_report.php" class="btn btn-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card customer-card">
                <div class="card-body text-center">
                    <i class="fas fa-users fa-2x text-primary mb-2"></i>
                    <h5>Total Customers</h5>
                    <h3 class="text-primary"><?= $total_customers ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card customer-card">
                <div class="card-body text-center">
                    <i class="fas fa-user-check fa-2x text-success mb-2"></i>
                    <h5>Active Customers</h5>
                    <h3 class="text-success"><?= $active_customers ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card customer-card">
                <div class="card-body text-center">
                    <i class="fas fa-shopping-cart fa-2x text-info mb-2"></i>
                    <h5>Customers with Orders</h5>
                    <h3 class="text-info"><?= $customers_with_orders ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card customer-card">
                <div class="card-body text-center">
                    <i class="fas fa-rupee-sign fa-2x text-warning mb-2"></i>
                    <h5>Total Revenue</h5>
                    <h3 class="text-warning">₹<?= number_format($total_revenue, 2) ?></h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Additional Metrics -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-body text-center">
                    <h5>Average Order Value</h5>
                    <h3 class="text-success">₹<?= number_format($avg_order_value, 2) ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-body text-center">
                    <h5>Conversion Rate</h5>
                    <h3 class="text-info"><?= $total_customers > 0 ? number_format(($customers_with_orders / $total_customers) * 100, 1) : 0 ?>%</h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Top Customers -->
    <?php if (!empty($top_customers)): ?>
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-trophy"></i> Top 5 Customers by Spending</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <?php foreach($top_customers as $index => $customer): ?>
                <div class="col-md-4 mb-3">
                    <div class="card h-100 <?= $index == 0 ? 'high-value' : ($index <= 2 ? 'medium-value' : 'low-value') ?>">
                        <div class="card-body">
                            <h6 class="card-title">
                                <i class="fas fa-medal"></i> #<?= $index + 1 ?> 
                                <?= htmlspecialchars($customer['username']) ?>
                            </h6>
                            <p class="card-text">
                                <strong>Total Spent:</strong> ₹<?= number_format($customer['total_spent'], 2) ?><br>
                                <strong>Orders:</strong> <?= $customer['total_orders'] ?><br>
                                <strong>City:</strong> <?= htmlspecialchars($customer['city'] ?? 'N/A') ?>
                            </p>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Customer Acquisition Chart -->
    <?php if (!empty($monthly_data)): ?>
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-chart-line"></i> Customer Acquisition Trend</h5>
        </div>
        <div class="card-body">
            <div class="chart-container">
                <canvas id="acquisitionChart"></canvas>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Export Options -->
    <div class="mb-3">
        <button onclick="window.print()" class="btn btn-secondary">
            <i class="fas fa-print"></i> Print Report
        </button>
        <button onclick="exportToCSV()" class="btn btn-success">
            <i class="fas fa-file-csv"></i> Export to CSV
        </button>
    </div>

    <!-- Detailed Customer Table -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-list"></i> Detailed Customer Information</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover" id="customerTable">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Contact</th>
                            <th>Location</th>
                            <th>Join Date</th>
                            <th>Total Orders</th>
                            <th>Completed Orders</th>
                            <th>Total Spent</th>
                            <th>Last Order</th>
                            <th>Services</th>
                            <th>Avg Rating</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if ($customers): ?>
                        <?php foreach ($customers as $customer): ?>
                            <tr>
                                <td><?= $customer['id'] ?></td>
                                <td>
                                    <strong><?= htmlspecialchars($customer['username']) ?></strong>
                                    <br><small class="text-muted"><?= htmlspecialchars($customer['email']) ?></small>
                                </td>
                                <td>
                                    <?= htmlspecialchars($customer['phone_number'] ?? 'N/A') ?>
                                </td>
                                <td>
                                    <?= htmlspecialchars($customer['city'] ?? 'N/A') ?>
                                    <?php if ($customer['pincode']): ?>
                                        <br><small class="text-muted"><?= htmlspecialchars($customer['pincode']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?= date('d-M-Y', strtotime($customer['created_at'])) ?></td>
                                <td class="text-center">
                                    <span class="badge bg-primary"><?= $customer['total_orders'] ?></span>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-success"><?= $customer['completed_orders'] ?></span>
                                </td>
                                <td>
                                    <strong>₹<?= number_format($customer['total_spent'], 2) ?></strong>
                                    <?php if ($customer['total_spent'] > 50000): ?>
                                        <br><small class="badge bg-warning customer-badge">VIP</small>
                                    <?php elseif ($customer['total_spent'] > 25000): ?>
                                        <br><small class="badge bg-info customer-badge">Premium</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($customer['last_order_date']): ?>
                                        <?= date('d-M-Y', strtotime($customer['last_order_date'])) ?>
                                    <?php else: ?>
                                        <span class="text-muted">Never</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-info"><?= $customer['total_services'] ?></span>
                                </td>
                                <td class="text-center">
                                    <?php if ($customer['avg_rating']): ?>
                                        <span class="text-warning">
                                            <?php for($i = 1; $i <= 5; $i++): ?>
                                                <i class="fas fa-star<?= $i <= round($customer['avg_rating']) ? '' : '-o' ?>"></i>
                                            <?php endfor; ?>
                                        </span>
                                        <br><small><?= number_format($customer['avg_rating'], 1) ?>/5</small>
                                    <?php else: ?>
                                        <span class="text-muted">No ratings</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge <?= $customer['status'] == 'active' ? 'bg-success' : 'bg-secondary' ?>">
                                        <?= ucfirst($customer['status']) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="12" class="text-center">No customers found matching the selected criteria.</td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<script>
// Customer Acquisition Chart
<?php if (!empty($monthly_data)): ?>
const ctx = document.getElementById('acquisitionChart').getContext('2d');
const chart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: [<?php echo "'" . implode("','", array_column($monthly_data, 'month')) . "'"; ?>],
        datasets: [{
            label: 'New Customers',
            data: [<?php echo implode(',', array_column($monthly_data, 'new_customers')); ?>],
            borderColor: 'rgb(75, 192, 192)',
            backgroundColor: 'rgba(75, 192, 192, 0.2)',
            tension: 0.1,
            fill: true
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            title: {
                display: true,
                text: 'Customer Acquisition Over Time'
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    stepSize: 1
                }
            }
        }
    }
});
<?php endif; ?>

function exportToCSV() {
    const table = document.getElementById('customerTable');
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
        if (rowData.length > 0 && !rowData[0].includes('No customers found')) {
            csv.push(rowData.join(','));
        }
    });
    
    // Download CSV
    const csvContent = csv.join('\n');
    const blob = new Blob([csvContent], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'customer_report_' + new Date().toISOString().split('T')[0] + '.csv';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    window.URL.revokeObjectURL(url);
}
</script>

<?php include INCLUDES_PATH . '/templates/admin_footer.php'; ?>
