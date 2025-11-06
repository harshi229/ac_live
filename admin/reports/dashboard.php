<?php
require_once dirname(dirname(__DIR__)) . '/includes/config/init.php';

// Ensure the admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: ' . admin_url('login'));
    exit();
}

include INCLUDES_PATH . '/templates/admin_header.php';

// Get date range from URL parameters
$date_from = $_GET['date_from'] ?? date('Y-m-01'); // First day of current month
$date_to = $_GET['date_to'] ?? date('Y-m-d'); // Today

try {
    // Sales Summary
    $sales_summary = $pdo->query("
        SELECT 
            COUNT(*) as total_orders,
            SUM(total_price) as total_revenue,
            AVG(total_price) as avg_order_value,
            COUNT(CASE WHEN order_status = 'Delivered' THEN 1 END) as delivered_orders,
            COUNT(CASE WHEN order_status = 'Pending' THEN 1 END) as pending_orders
        FROM orders 
        WHERE DATE(created_at) BETWEEN '$date_from' AND '$date_to'
    ")->fetch(PDO::FETCH_ASSOC);
    
    // Top Products
    $top_products = $pdo->query("
        SELECT 
            p.product_name,
            p.model_name,
            COUNT(oi.id) as sales_count,
            SUM(oi.total_price) as revenue
        FROM order_items oi
        JOIN products p ON oi.product_id = p.id
        JOIN orders o ON oi.order_id = o.id
        WHERE DATE(o.created_at) BETWEEN '$date_from' AND '$date_to'
        AND o.order_status != 'Cancelled'
        GROUP BY p.id
        ORDER BY sales_count DESC
        LIMIT 10
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    // Daily Sales Data for Chart
    $daily_sales = $pdo->query("
        SELECT 
            DATE(created_at) as date,
            COUNT(*) as orders,
            SUM(total_price) as revenue
        FROM orders 
        WHERE DATE(created_at) BETWEEN '$date_from' AND '$date_to'
        AND order_status != 'Cancelled'
        GROUP BY DATE(created_at)
        ORDER BY date ASC
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    // Customer Analytics
    $customer_analytics = $pdo->query("
        SELECT 
            COUNT(DISTINCT user_id) as total_customers,
            COUNT(DISTINCT CASE WHEN DATE(created_at) >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN user_id END) as new_customers,
            AVG(orders_per_customer) as avg_orders_per_customer
        FROM (
            SELECT 
                user_id,
                COUNT(*) as orders_per_customer
            FROM orders 
            WHERE DATE(created_at) BETWEEN '$date_from' AND '$date_to'
            GROUP BY user_id
        ) as customer_stats
    ")->fetch(PDO::FETCH_ASSOC);
    
    // Category Performance
    $category_performance = $pdo->query("
        SELECT 
            c.name as category_name,
            COUNT(oi.id) as sales_count,
            SUM(oi.total_price) as revenue,
            AVG(oi.total_price) as avg_sale_price
        FROM order_items oi
        JOIN products p ON oi.product_id = p.id
        JOIN categories c ON p.category_id = c.id
        JOIN orders o ON oi.order_id = o.id
        WHERE DATE(o.created_at) BETWEEN '$date_from' AND '$date_to'
        AND o.order_status != 'Cancelled'
        GROUP BY c.id, c.name
        ORDER BY revenue DESC
    ")->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error_message = "Error fetching report data: " . $e->getMessage();
}
?>

<style>
    .reports-container {
        max-width: 1400px;
        margin: 0 auto;
        padding: 20px;
    }
    
    .report-card {
        background: white;
        border-radius: 15px;
        padding: 30px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        margin-bottom: 30px;
    }
    
    .report-header {
        text-align: center;
        margin-bottom: 30px;
        padding-bottom: 20px;
        border-bottom: 2px solid #e9ecef;
    }
    
    .report-icon {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 20px;
        font-size: 2rem;
        color: white;
    }
    
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 25px;
        margin-bottom: 30px;
    }
    
    .stat-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 25px;
        border-radius: 15px;
        text-align: center;
        box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
        transition: transform 0.3s ease;
    }
    
    .stat-card:hover {
        transform: translateY(-5px);
    }
    
    .stat-card.success { background: linear-gradient(135deg, #56ab2f 0%, #a8e6cf 100%); }
    .stat-card.warning { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
    .stat-card.info { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }
    .stat-card.danger { background: linear-gradient(135deg, #ff416c 0%, #ff4b2b 100%); }
    
    .stat-number {
        font-size: 2.5rem;
        font-weight: 700;
        margin-bottom: 10px;
        text-shadow: 2px 2px 4px rgba(0,0,0,0.1);
    }
    
    .stat-label {
        font-size: 1rem;
        opacity: 0.9;
        font-weight: 500;
    }
    
    .chart-container {
        background: #f8f9fa;
        padding: 20px;
        border-radius: 10px;
        margin: 20px 0;
    }
    
    .table-responsive {
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    .table th {
        background: linear-gradient(135deg, #343a40, #495057);
        color: white;
        border: none;
        font-weight: 600;
    }
    
    .export-buttons {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        margin-bottom: 20px;
    }
    
    .btn-export {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border: none;
        color: white;
        padding: 10px 20px;
        border-radius: 25px;
        font-weight: 600;
        transition: all 0.3s ease;
    }
    
    .btn-export:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        color: white;
    }
    
    .date-filter {
        background: #f8f9fa;
        padding: 20px;
        border-radius: 10px;
        margin-bottom: 30px;
    }
</style>

<div class="reports-container">
    <div class="report-card">
        <div class="report-header">
            <div class="report-icon">
                <i class="fas fa-chart-bar"></i>
            </div>
            <h1>Business Analytics Dashboard</h1>
            <p class="text-muted">Comprehensive insights into your AC business performance</p>
        </div>

        <!-- Date Filter -->
        <div class="date-filter">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <label for="date_from" class="form-label">From Date</label>
                    <input type="date" class="form-control" name="date_from" id="date_from" value="<?= $date_from ?>">
                </div>
                <div class="col-md-4">
                    <label for="date_to" class="form-label">To Date</label>
                    <input type="date" class="form-control" name="date_to" id="date_to" value="<?= $date_to ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-filter"></i> Apply Filter
                    </button>
                </div>
            </form>
        </div>

        <!-- Export Buttons -->
        <div class="export-buttons">
            <a href="export.php?type=sales&format=csv&date_from=<?= $date_from ?>&date_to=<?= $date_to ?>" class="btn btn-export">
                <i class="fas fa-download"></i> Export Sales CSV
            </a>
            <a href="export.php?type=orders&format=csv&date_from=<?= $date_from ?>&date_to=<?= $date_to ?>" class="btn btn-export">
                <i class="fas fa-download"></i> Export Orders CSV
            </a>
            <a href="export.php?type=products&format=csv" class="btn btn-export">
                <i class="fas fa-download"></i> Export Products CSV
            </a>
            <a href="export.php?type=inventory&format=csv" class="btn btn-export">
                <i class="fas fa-download"></i> Export Inventory CSV
            </a>
        </div>

        <!-- Key Metrics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?= number_format($sales_summary['total_orders'] ?? 0) ?></div>
                <div class="stat-label">Total Orders</div>
            </div>
            <div class="stat-card success">
                <div class="stat-number">₹<?= number_format($sales_summary['total_revenue'] ?? 0, 0) ?></div>
                <div class="stat-label">Total Revenue</div>
            </div>
            <div class="stat-card info">
                <div class="stat-number">₹<?= number_format($sales_summary['avg_order_value'] ?? 0, 0) ?></div>
                <div class="stat-label">Avg Order Value</div>
            </div>
            <div class="stat-card warning">
                <div class="stat-number"><?= number_format($customer_analytics['total_customers'] ?? 0) ?></div>
                <div class="stat-label">Total Customers</div>
            </div>
        </div>

        <!-- Sales Chart -->
        <div class="chart-container">
            <h4><i class="fas fa-chart-line"></i> Daily Sales Trend</h4>
            <canvas id="salesChart" width="800" height="300"></canvas>
        </div>

        <!-- Top Products and Category Performance -->
        <div class="row">
            <div class="col-lg-6">
                <div class="table-responsive">
                    <h4><i class="fas fa-trophy"></i> Top Selling Products</h4>
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Sales</th>
                                <th>Revenue</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($top_products)): ?>
                                <tr>
                                    <td colspan="3" class="text-center text-muted">No sales data available</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($top_products as $product): ?>
                                    <tr>
                                        <td>
                                            <strong><?= htmlspecialchars($product['product_name']) ?></strong><br>
                                            <small class="text-muted"><?= htmlspecialchars($product['model_name']) ?></small>
                                        </td>
                                        <td><span class="badge bg-primary"><?= $product['sales_count'] ?> units</span></td>
                                        <td>₹<?= number_format($product['revenue'], 2) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="col-lg-6">
                <div class="table-responsive">
                    <h4><i class="fas fa-chart-pie"></i> Category Performance</h4>
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Category</th>
                                <th>Sales</th>
                                <th>Revenue</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($category_performance)): ?>
                                <tr>
                                    <td colspan="3" class="text-center text-muted">No category data available</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($category_performance as $category): ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($category['category_name']) ?></strong></td>
                                        <td><span class="badge bg-info"><?= $category['sales_count'] ?> sales</span></td>
                                        <td>₹<?= number_format($category['revenue'], 2) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Sales Chart
    const salesCtx = document.getElementById('salesChart').getContext('2d');
    const salesData = <?php echo json_encode($daily_sales); ?>;
    
    const salesLabels = salesData.map(item => {
        const date = new Date(item.date);
        return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
    });
    
    const salesRevenue = salesData.map(item => parseFloat(item.revenue));
    const salesOrders = salesData.map(item => parseInt(item.orders));
    
    new Chart(salesCtx, {
        type: 'line',
        data: {
            labels: salesLabels,
            datasets: [{
                label: 'Revenue (₹)',
                data: salesRevenue,
                borderColor: '#667eea',
                backgroundColor: 'rgba(102, 126, 234, 0.1)',
                tension: 0.4,
                yAxisID: 'y'
            }, {
                label: 'Orders',
                data: salesOrders,
                borderColor: '#764ba2',
                backgroundColor: 'rgba(118, 75, 162, 0.1)',
                tension: 0.4,
                yAxisID: 'y1'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    type: 'linear',
                    display: true,
                    position: 'left',
                    title: {
                        display: true,
                        text: 'Revenue (₹)'
                    }
                },
                y1: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    title: {
                        display: true,
                        text: 'Orders'
                    },
                    grid: {
                        drawOnChartArea: false,
                    },
                }
            },
            plugins: {
                legend: {
                    position: 'top',
                }
            }
        }
    });
});
</script>

<?php include INCLUDES_PATH . '/templates/admin_footer.php'; ?>
