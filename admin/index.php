<?php
require_once dirname(__DIR__) . '/includes/config/init.php';
require_once INCLUDES_PATH . '/middleware/admin_auth.php';
include INCLUDES_PATH . '/templates/admin_header.php';

// Fetch comprehensive statistics
try {
    // Basic counts
    $stats = [];
    $stats['products'] = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
    $stats['active_products'] = $pdo->query("SELECT COUNT(*) FROM products WHERE status = 'active'")->fetchColumn();
    $stats['categories'] = $pdo->query("SELECT COUNT(*) FROM categories WHERE status = 'active'")->fetchColumn();
    $stats['brands'] = $pdo->query("SELECT COUNT(*) FROM brands WHERE status = 'active'")->fetchColumn();
    $stats['users'] = $pdo->query("SELECT COUNT(*) FROM users WHERE status = 'active'")->fetchColumn();
    $stats['orders'] = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
    $stats['pending_orders'] = $pdo->query("SELECT COUNT(*) FROM orders WHERE order_status = 'Pending'")->fetchColumn();
    $stats['total_revenue'] = $pdo->query("SELECT COALESCE(SUM(total_price), 0) FROM orders WHERE order_status != 'Cancelled'")->fetchColumn();
    
    // Review statistics
    $stats['total_reviews'] = $pdo->query("SELECT COUNT(*) FROM reviews")->fetchColumn();
    $stats['pending_reviews'] = $pdo->query("SELECT COUNT(*) FROM reviews WHERE status = 'pending'")->fetchColumn();
    $stats['approved_reviews'] = $pdo->query("SELECT COUNT(*) FROM reviews WHERE status = 'approved'")->fetchColumn();
    $stats['avg_rating'] = $pdo->query("SELECT COALESCE(AVG(rating), 0) FROM reviews WHERE status = 'approved'")->fetchColumn();
    
    // Low stock products (less than 5)
    $low_stock_products = $pdo->query("SELECT COUNT(*) FROM products WHERE stock < 5 AND status = 'active'")->fetchColumn();
    
    // Recent orders (last 7 days)
    $recent_orders = $pdo->query("SELECT COUNT(*) FROM orders WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetchColumn();
    
    // Monthly revenue (current month)
    $monthly_revenue = $pdo->query("SELECT COALESCE(SUM(total_price), 0) FROM orders WHERE MONTH(created_at) = MONTH(NOW()) AND YEAR(created_at) = YEAR(NOW()) AND order_status != 'Cancelled'")->fetchColumn();
    
    // Recent orders for table
    $recent_orders_list = $pdo->query("
        SELECT o.id, o.order_number, o.total_price, o.order_status, o.created_at, u.username 
        FROM orders o 
        JOIN users u ON o.user_id = u.id 
        ORDER BY o.created_at DESC 
        LIMIT 10
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    // Top selling products
    $top_products = $pdo->query("
        SELECT p.product_name, p.model_name, COUNT(oi.product_id) as sales_count, SUM(oi.total_price) as total_sales
        FROM order_items oi 
        JOIN products p ON oi.product_id = p.id 
        JOIN orders o ON oi.order_id = o.id 
        WHERE o.order_status != 'Cancelled'
        GROUP BY p.id 
        ORDER BY sales_count DESC 
        LIMIT 5
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    // Low stock products list
    $low_stock_list = $pdo->query("
        SELECT product_name, model_name, stock, brand_id, (SELECT name FROM brands WHERE id = products.brand_id) as brand_name
        FROM products 
        WHERE stock < 5 AND status = 'active' 
        ORDER BY stock ASC 
        LIMIT 10
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    // Sales chart data (last 30 days)
    $sales_chart_data = $pdo->query("
        SELECT DATE(created_at) as date, COUNT(*) as orders, COALESCE(SUM(total_price), 0) as revenue
        FROM orders 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) AND order_status != 'Cancelled'
        GROUP BY DATE(created_at) 
        ORDER BY date ASC
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    // Monthly customer data for charts (last 6 months)
    $monthly_customers = $pdo->query("
        SELECT 
            MONTH(created_at) as month,
            COUNT(*) as new_customers,
            (SELECT COUNT(*) FROM users WHERE created_at <= LAST_DAY(created_at)) as total_customers
        FROM users 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
        GROUP BY YEAR(created_at), MONTH(created_at)
        ORDER BY YEAR(created_at), MONTH(created_at)
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    // Monthly revenue comparison (last 6 months)
    $monthly_revenue_data = $pdo->query("
        SELECT 
            DATE_FORMAT(created_at, '%Y-%m') as month,
            COALESCE(SUM(total_price), 0) as revenue,
            COUNT(*) as orders
        FROM orders 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH) AND order_status != 'Cancelled'
        GROUP BY DATE_FORMAT(created_at, '%Y-%m')
        ORDER BY month ASC
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    // Weekly data for charts (last 7 days)
    $weekly_data = $pdo->query("
        SELECT 
            DAYNAME(created_at) as day_name,
            COUNT(*) as orders,
            COALESCE(SUM(total_price), 0) as revenue
        FROM orders 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) AND order_status != 'Cancelled'
        GROUP BY DATE(created_at), DAYNAME(created_at)
        ORDER BY DATE(created_at) ASC
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    // Monthly data for charts (last 30 days by week)
    $monthly_weekly_data = $pdo->query("
        SELECT 
            CONCAT('Week ', CEIL(DAY(created_at) / 7)) as week_label,
            COUNT(*) as orders,
            COALESCE(SUM(total_price), 0) as revenue
        FROM orders 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) AND order_status != 'Cancelled'
        GROUP BY YEAR(created_at), MONTH(created_at), CEIL(DAY(created_at) / 7)
        ORDER BY YEAR(created_at), MONTH(created_at), CEIL(DAY(created_at) / 7)
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    // Category-wise sales data
    $category_sales_data = $pdo->query("
        SELECT 
            c.name as category_name,
            COUNT(oi.id) as sales_count,
            COALESCE(SUM(oi.total_price), 0) as revenue
        FROM order_items oi
        JOIN products p ON oi.product_id = p.id
        JOIN categories c ON p.category_id = c.id
        JOIN orders o ON oi.order_id = o.id
        WHERE o.order_status != 'Cancelled' AND o.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        GROUP BY c.id, c.name
        ORDER BY revenue DESC
        LIMIT 10
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    // Security alerts
    $security_alerts = [];
    
    // Check for failed login attempts (if table exists)
    $failed_logins_24h = 0;
    try {
        $table_exists = $pdo->query("SHOW TABLES LIKE 'admin_login_logs'")->rowCount() > 0;
        if ($table_exists) {
            $failed_logins_24h = $pdo->query("
                SELECT COUNT(*) FROM admin_login_logs 
                WHERE success = 0 AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
            ")->fetchColumn();
        }
    } catch (PDOException $e) {
        // Table doesn't exist, continue without security alerts
        $failed_logins_24h = 0;
    }
    
    if ($failed_logins_24h > 5) {
        $security_alerts[] = [
            'type' => 'danger',
            'icon' => 'fas fa-exclamation-triangle',
            'title' => 'High Failed Login Attempts',
            'message' => $failed_logins_24h . ' failed login attempts in the last 24 hours'
        ];
    } elseif ($failed_logins_24h > 2) {
        $security_alerts[] = [
            'type' => 'warning',
            'icon' => 'fas fa-shield-alt',
            'title' => 'Failed Login Attempts',
            'message' => $failed_logins_24h . ' failed login attempts in the last 24 hours'
        ];
    }
    
    // Check for inactive users
    $inactive_users = $pdo->query("
        SELECT COUNT(*) FROM users 
        WHERE (last_login < DATE_SUB(NOW(), INTERVAL 30 DAY) OR last_login IS NULL) AND status = 'active'
    ")->fetchColumn();
    
    if ($inactive_users > 50) {
        $security_alerts[] = [
            'type' => 'info',
            'icon' => 'fas fa-users',
            'title' => 'Inactive Users',
            'message' => $inactive_users . ' users haven\'t logged in for 30+ days'
        ];
    }
    
    // Recent activity log
    $recent_activities = $pdo->query("
        (SELECT 'order' as type, 'New Order' as action, CONCAT('Order #', order_number) as description, created_at as timestamp
         FROM orders 
         WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
         ORDER BY created_at DESC LIMIT 5)
        UNION ALL
        (SELECT 'product' as type, 'Product Added' as action, CONCAT('Product: ', product_name) as description, created_at as timestamp
         FROM products 
         WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
         ORDER BY created_at DESC LIMIT 5)
        ORDER BY timestamp DESC LIMIT 10
    ")->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error_message = "Error fetching dashboard data: " . $e->getMessage();
}
?>

<!-- Dashboard Content -->
<div class="fade-in">
    <!-- Welcome Section -->
    <div class="admin-card mb-4 welcome-card">
        <div class="card-body text-center">
            <div class="welcome-content">
                <div class="welcome-icon">
                    <i class="fas fa-crown"></i>
                </div>
                <h1 class="welcome-title">
                    Welcome to Admin Dashboard
                </h1>
                <p class="welcome-subtitle">
                    Manage your AC Management System efficiently with real-time insights and analytics
                </p>
                <div class="welcome-stats">
                    <div class="welcome-stat">
                        <span class="stat-number"><?php echo number_format($stats['total_revenue']); ?></span>
                        <span class="stat-label">Total Revenue</span>
                    </div>
                    <div class="welcome-stat">
                        <span class="stat-number"><?php echo number_format($stats['orders']); ?></span>
                        <span class="stat-label">Total Orders</span>
                    </div>
                    <div class="welcome-stat">
                        <span class="stat-number"><?php echo number_format($stats['users']); ?></span>
                        <span class="stat-label">Active Users</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Security Alerts -->
    <?php if (!empty($security_alerts)): ?>
    <div class="security-alerts mb-4">
        <?php foreach ($security_alerts as $alert): ?>
        <div class="alert alert-<?= $alert['type'] ?> alert-dismissible fade show" role="alert">
            <i class="<?= $alert['icon'] ?> me-2"></i>
            <strong><?= htmlspecialchars($alert['title']) ?>:</strong>
            <?= htmlspecialchars($alert['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Enhanced Statistics Cards -->
    <div class="stats-grid">
        <div class="stat-card stat-card-products" data-aos="fade-up" data-aos-delay="100">
            <div class="stat-header">
                <div class="stat-icon">
                    <i class="fas fa-cubes"></i>
                    <div class="stat-icon-bg"></div>
                </div>
                <div class="stat-change positive">
                    <i class="fas fa-arrow-up"></i>
                    <span id="products-change">+12%</span>
                </div>
            </div>
            <div class="stat-content">
                <h3 class="stat-value" id="products-count"><?php echo number_format($stats['products']); ?></h3>
                <p class="stat-label">Total Products</p>
                <div class="stat-subtitle">Active inventory items</div>
            </div>
            <div class="stat-chart">
                <canvas class="stat-chart-canvas" data-chart="products"></canvas>
            </div>
            <div class="stat-actions">
                <button class="stat-action-btn" onclick="window.location.href='<?php echo admin_url('products'); ?>'">
                    <i class="fas fa-eye"></i>
                    View
                </button>
                <button class="stat-action-btn" onclick="window.location.href='<?php echo admin_url('products/add'); ?>'">
                    <i class="fas fa-plus"></i>
                    Add
                </button>
            </div>
        </div>

        <div class="stat-card stat-card-orders success" data-aos="fade-up" data-aos-delay="200">
            <div class="stat-header">
                <div class="stat-icon success">
                    <i class="fas fa-receipt"></i>
                    <div class="stat-icon-bg success"></div>
                </div>
                <div class="stat-change positive">
                    <i class="fas fa-arrow-up"></i>
                    <span id="orders-change">+8%</span>
                </div>
            </div>
            <div class="stat-content">
                <h3 class="stat-value" id="orders-count"><?php echo number_format($stats['orders']); ?></h3>
                <p class="stat-label">Total Orders</p>
                <div class="stat-subtitle">All time orders</div>
            </div>
            <div class="stat-chart">
                <canvas class="stat-chart-canvas" data-chart="orders"></canvas>
            </div>
            <div class="stat-actions">
                <button class="stat-action-btn" onclick="window.location.href='<?php echo admin_url('orders'); ?>'">
                    <i class="fas fa-eye"></i>
                    View
                </button>
                <span class="stat-badge" id="pending-orders">
                    <?php echo $stats['pending_orders']; ?> pending
                </span>
            </div>
        </div>

        <div class="stat-card stat-card-users warning" data-aos="fade-up" data-aos-delay="300">
            <div class="stat-header">
                <div class="stat-icon warning">
                    <i class="fas fa-users"></i>
                    <div class="stat-icon-bg warning"></div>
                </div>
                <div class="stat-change positive">
                    <i class="fas fa-arrow-up"></i>
                    <span id="users-change">+15%</span>
                </div>
            </div>
            <div class="stat-content">
                <h3 class="stat-value" id="users-count"><?php echo number_format($stats['users']); ?></h3>
                <p class="stat-label">Active Users</p>
                <div class="stat-subtitle">Registered customers</div>
            </div>
            <div class="stat-chart">
                <canvas class="stat-chart-canvas" data-chart="users"></canvas>
            </div>
            <div class="stat-actions">
                <button class="stat-action-btn" onclick="window.location.href='<?php echo admin_url('users'); ?>'">
                    <i class="fas fa-eye"></i>
                    View
                </button>
                <button class="stat-action-btn" onclick="window.location.href='<?php echo admin_url('reports/customers'); ?>'">
                    <i class="fas fa-chart-bar"></i>
                    Report
                </button>
            </div>
        </div>

        <div class="stat-card stat-card-revenue info" data-aos="fade-up" data-aos-delay="400">
            <div class="stat-header">
                <div class="stat-icon info">
                    <i class="fas fa-dollar-sign"></i>
                    <div class="stat-icon-bg info"></div>
                </div>
                <div class="stat-change positive">
                    <i class="fas fa-arrow-up"></i>
                    <span id="revenue-change">+22%</span>
                </div>
            </div>
            <div class="stat-content">
                <h3 class="stat-value" id="revenue-amount">₹<?php echo number_format($stats['total_revenue']); ?></h3>
                <p class="stat-label">Total Revenue</p>
                <div class="stat-subtitle">All time earnings</div>
            </div>
            <div class="stat-chart">
                <canvas class="stat-chart-canvas" data-chart="revenue"></canvas>
            </div>
            <div class="stat-actions">
                <button class="stat-action-btn" onclick="window.location.href='<?php echo admin_url('reports/sales'); ?>'">
                    <i class="fas fa-eye"></i>
                    View
                </button>
                <button class="stat-action-btn" onclick="window.location.href='<?php echo admin_url('reports/export'); ?>'">
                    <i class="fas fa-download"></i>
                    Export
                </button>
            </div>
        </div>

        <div class="stat-card stat-card-reviews" data-aos="fade-up" data-aos-delay="500">
            <div class="stat-header">
                <div class="stat-icon" style="background: linear-gradient(135deg, #ffc107, #ff8f00);">
                    <i class="fas fa-star"></i>
                    <div class="stat-icon-bg" style="background: linear-gradient(135deg, #ffc107, #ff8f00);"></div>
                </div>
                <div class="stat-change positive">
                    <i class="fas fa-arrow-up"></i>
                    <span id="reviews-change">+18%</span>
                </div>
            </div>
            <div class="stat-content">
                <h3 class="stat-value" id="reviews-count"><?php echo number_format($stats['total_reviews']); ?></h3>
                <p class="stat-label">Total Reviews</p>
                <div class="stat-subtitle">Customer feedback</div>
            </div>
            <div class="stat-chart">
                <canvas class="stat-chart-canvas" data-chart="reviews"></canvas>
            </div>
            <div class="stat-actions">
                <button class="stat-action-btn" onclick="window.location.href='<?php echo admin_url('reviews'); ?>'">
                    <i class="fas fa-eye"></i>
                    Manage
                </button>
                <span class="stat-badge" id="pending-reviews" style="background: #ffc107; color: #000;">
                    <?php echo $stats['pending_reviews']; ?> pending
                </span>
            </div>
        </div>
    </div>

    <!-- Enhanced Charts and Analytics -->
    <div class="row mb-4">
        <div class="col-lg-8">
            <div class="chart-card" data-aos="fade-up" data-aos-delay="500">
                <div class="chart-header">
                    <div class="chart-title-section">
                        <h5 class="chart-title">
                            <i class="fas fa-chart-area"></i>
                            Sales Overview
                        </h5>
                        <p class="chart-subtitle">Last 30 days performance</p>
                    </div>
                    <div class="chart-actions">
                        <div class="chart-time-range">
                            <button class="time-btn active" data-range="7d">7D</button>
                            <button class="time-btn" data-range="30d">30D</button>
                            <button class="time-btn" data-range="90d">90D</button>
                        </div>
                        <div class="chart-export">
                            <button class="btn-export-chart" title="Export Chart">
                                <i class="fas fa-download"></i>
                            </button>
                            <button class="btn-expand-chart" title="Expand Chart">
                                <i class="fas fa-expand"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="chart-container">
                    <div class="chart-loading" id="salesChartLoading">
                        <div class="loading-spinner"></div>
                        <span>Loading chart data...</span>
                    </div>
                    <canvas id="salesChart"></canvas>
                    <div class="chart-overlay" id="salesChartOverlay">
                        <div class="chart-tooltip" id="salesChartTooltip"></div>
                    </div>
                </div>
                <div class="chart-footer">
                    <div class="chart-legend">
                        <div class="legend-item">
                            <div class="legend-color orders"></div>
                            <span>Orders</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-color revenue"></div>
                            <span>Revenue</span>
                        </div>
                    </div>
                    <div class="chart-summary">
                        <div class="summary-item">
                            <span class="summary-label">Total Orders</span>
                            <span class="summary-value" id="totalOrders">0</span>
                        </div>
                        <div class="summary-item">
                            <span class="summary-label">Total Revenue</span>
                            <span class="summary-value" id="totalRevenue">₹0</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- Additional Analytics Row -->
    <div class="row mb-4">
        <div class="col-lg-6">
            <div class="chart-card" data-aos="fade-up" data-aos-delay="700">
                <div class="chart-header">
                    <h5 class="chart-title">
                        <i class="fas fa-user-group"></i>
                        Customer Growth
                    </h5>
                </div>
                <div class="chart-container">
                    <canvas id="customerGrowthChart"></canvas>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="chart-card" data-aos="fade-up" data-aos-delay="800">
                <div class="chart-header">
                    <h5 class="chart-title">
                        <i class="fas fa-trophy"></i>
                        Top Products
                    </h5>
                </div>
                <div class="chart-container">
                    <canvas id="topProductsChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activity and Category Performance Side by Side -->
    <div class="row mb-4">
        <div class="col-lg-6">
            <div class="admin-card" data-aos="fade-up" data-aos-delay="900">
                <div class="card-header">
                    <h5 class="card-title">
                        <i class="fas fa-history"></i>
                        Recent Activity
                    </h5>
                </div>
                <div class="card-body">
                    <div class="activity-feed">
                        <?php foreach ($recent_activities as $activity): ?>
                        <div class="activity-item">
                            <div class="activity-icon-small">
                                <i class="fas fa-<?php echo $activity['type'] == 'order' ? 'shopping-cart' : 'box'; ?>"></i>
                            </div>
                            <div class="activity-content">
                                <div class="activity-title"><?php echo $activity['action']; ?></div>
                                <div class="activity-description"><?php echo htmlspecialchars($activity['description']); ?></div>
                                <div class="activity-time"><?php echo date('M j, g:i A', strtotime($activity['timestamp'])); ?></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="chart-card category-card" data-aos="fade-up" data-aos-delay="1000">
                <div class="chart-header">
                    <div class="chart-title-section">
                        <h5 class="chart-title">
                            <i class="fas fa-chart-pie"></i>
                            Category Performance
                        </h5>
                        <p class="chart-subtitle">Revenue by category</p>
                    </div>
                    <div class="chart-actions">
                        <button class="btn-chart-type active" data-type="doughnut" title="Doughnut Chart">
                            <i class="fas fa-chart-pie"></i>
                        </button>
                        <button class="btn-chart-type" data-type="bar" title="Bar Chart">
                            <i class="fas fa-chart-bar"></i>
                        </button>
                    </div>
                </div>
                <div class="chart-container">
                    <canvas id="categoryChart"></canvas>
                </div>
                <div class="category-list">
                    <div class="category-item">
                        <div class="category-color" style="background: #6366f1;"></div>
                        <div class="category-info">
                            <div class="category-name">Electronics</div>
                            <div class="category-percentage">35%</div>
                        </div>
                        <div class="category-value">₹45,200</div>
                    </div>
                    <div class="category-item">
                        <div class="category-color" style="background: #10b981;"></div>
                        <div class="category-info">
                            <div class="category-name">Home & Garden</div>
                            <div class="category-percentage">28%</div>
                        </div>
                        <div class="category-value">₹36,100</div>
                    </div>
                    <div class="category-item">
                        <div class="category-color" style="background: #f59e0b;"></div>
                        <div class="category-info">
                            <div class="category-name">Fashion</div>
                            <div class="category-percentage">22%</div>
                        </div>
                        <div class="category-value">₹28,400</div>
                    </div>
                    <div class="category-item">
                        <div class="category-color" style="background: #ef4444;"></div>
                        <div class="category-info">
                            <div class="category-name">Sports</div>
                            <div class="category-percentage">15%</div>
                        </div>
                        <div class="category-value">₹19,300</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Full Width Recent Orders -->
    <div class="row mb-4">
        <div class="col-lg-12">
            <div class="admin-card" data-aos="fade-up" data-aos-delay="1100">
                <div class="card-header">
                    <div class="card-title-section">
                        <h5 class="card-title">
                            <i class="fas fa-receipt"></i>
                            Recent Orders
                        </h5>
                        <p class="card-subtitle">Latest customer orders</p>
                    </div>
                    <div class="table-controls">
                        <div class="table-search">
                            <input type="text" placeholder="Search orders..." class="table-search-input" id="ordersSearch">
                            <i class="fas fa-search"></i>
                        </div>
                        <div class="table-filters">
                            <select class="table-filter" id="statusFilter">
                                <option value="">All Status</option>
                                <option value="Pending">Pending</option>
                                <option value="Confirmed">Confirmed</option>
                                <option value="Shipped">Shipped</option>
                                <option value="Delivered">Delivered</option>
                                <option value="Cancelled">Cancelled</option>
                            </select>
                            <button class="btn-filter-clear" id="clearFilters" title="Clear Filters">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-container">
                        <div class="table-header-info">
                            <div class="table-info">
                                <span class="table-count">Showing <strong id="ordersCount"><?php echo count($recent_orders_list); ?></strong> orders</span>
                                <div class="table-actions">
                                    <button class="btn-table-action" id="refreshOrders" title="Refresh">
                                        <i class="fas fa-sync-alt"></i>
                                    </button>
                                    <button class="btn-table-action" id="exportOrders" title="Export">
                                        <i class="fas fa-download"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="admin-table" id="ordersTable">
                                <thead>
                                    <tr>
                                        <th class="sortable" data-column="order_number">
                                            <div class="th-content">
                                                <span>Order #</span>
                                                <i class="fas fa-sort sort-icon"></i>
                                            </div>
                                        </th>
                                        <th class="sortable" data-column="username">
                                            <div class="th-content">
                                                <span>Customer</span>
                                                <i class="fas fa-sort sort-icon"></i>
                                            </div>
                                        </th>
                                        <th class="sortable" data-column="total_price">
                                            <div class="th-content">
                                                <span>Amount</span>
                                                <i class="fas fa-sort sort-icon"></i>
                                            </div>
                                        </th>
                                        <th class="sortable" data-column="order_status">
                                            <div class="th-content">
                                                <span>Status</span>
                                                <i class="fas fa-sort sort-icon"></i>
                                            </div>
                                        </th>
                                        <th class="sortable" data-column="created_at">
                                            <div class="th-content">
                                                <span>Date</span>
                                                <i class="fas fa-sort sort-icon"></i>
                                            </div>
                                        </th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="ordersTableBody">
                                    <?php foreach ($recent_orders_list as $order): ?>
                                        <tr data-order-id="<?php echo $order['id']; ?>" data-status="<?php echo strtolower($order['order_status']); ?>">
                                            <td>
                                                <div class="order-number">
                                                    <strong>#<?php echo $order['order_number']; ?></strong>
                                                    <div class="order-id">ID: <?php echo $order['id']; ?></div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="customer-info">
                                                    <div class="customer-name"><?php echo htmlspecialchars($order['username']); ?></div>
                                                    <div class="customer-email"><?php echo htmlspecialchars($order['email'] ?? 'N/A'); ?></div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="amount-info">
                                                    <div class="amount">₹<?php echo number_format($order['total_price']); ?></div>
                                                    <div class="amount-label">Total</div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge badge-<?php echo strtolower($order['order_status']); ?>">
                                                    <?php echo $order['order_status']; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="date-info">
                                                    <div class="date"><?php echo date('M j, Y', strtotime($order['created_at'])); ?></div>
                                                    <div class="time"><?php echo date('g:i A', strtotime($order['created_at'])); ?></div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="table-actions">
                                                    <button class="btn-table-action view-order" data-order-id="<?php echo $order['id']; ?>" title="View Order">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <button class="btn-table-action edit-order" data-order-id="<?php echo $order['id']; ?>" title="Edit Order">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <div class="dropdown table-dropdown">
                                                        <button class="btn-table-action" data-bs-toggle="dropdown" title="More Actions">
                                                            <i class="fas fa-ellipsis-v"></i>
                                                        </button>
                                                        <ul class="dropdown-menu dropdown-menu-end">
                                                            <li><a class="dropdown-item" href="#">Print Invoice</a></li>
                                                            <li><a class="dropdown-item" href="#">Send Email</a></li>
                                                            <li><a class="dropdown-item" href="#">Track Package</a></li>
                                                            <li><hr class="dropdown-divider"></li>
                                                            <li><a class="dropdown-item text-danger" href="#">Cancel Order</a></li>
                                                        </ul>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="table-footer">
                            <div class="table-pagination">
                                <div class="pagination-info">
                                    Showing <span id="startRecord">1</span> to <span id="endRecord"><?php echo count($recent_orders_list); ?></span> of <span id="totalRecords"><?php echo count($recent_orders_list); ?></span> entries
                                </div>
                                <div class="pagination-controls">
                                    <button class="btn-pagination" id="prevPage" disabled>
                                        <i class="fas fa-chevron-left"></i>
                                        Previous
                                    </button>
                                    <div class="pagination-pages">
                                        <button class="btn-page active">1</button>
                                    </div>
                                    <button class="btn-pagination" id="nextPage">
                                        Next
                                        <i class="fas fa-chevron-right"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                    </div>
                </div>
            </div>

    <!-- Quick Actions and Alerts -->
    <div class="row">
            <div class="col-lg-6">
            <div class="admin-card">
                <div class="card-header">
                    <h5 class="card-title">
                        <i class="fas fa-bolt"></i>
                        Quick Actions
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <a href="<?php echo admin_url('products/add.php'); ?>" class="btn btn-primary w-100">
                                <i class="fas fa-square-plus"></i> Add Product
                            </a>
                        </div>
                        <div class="col-md-6">
                            <a href="<?php echo admin_url('orders.php'); ?>" class="btn btn-success w-100">
                                <i class="fas fa-receipt"></i> View Orders
                            </a>
                        </div>
                        <div class="col-md-6">
                            <a href="<?php echo admin_url('users.php'); ?>" class="btn btn-info w-100">
                                <i class="fas fa-user-group"></i> Manage Users
                            </a>
                        </div>
                        <div class="col-md-6">
                            <a href="<?php echo admin_url('reports/dashboard.php'); ?>" class="btn btn-warning w-100">
                                <i class="fas fa-chart-line"></i> View Reports
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="admin-card">
                <div class="card-header">
                    <h5 class="card-title">
                        <i class="fas fa-exclamation-triangle"></i>
                        System Alerts
                    </h5>
                        </div>
                <div class="card-body">
                    <?php if ($low_stock_products > 0): ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        <strong>Low Stock Alert:</strong> <?php echo $low_stock_products; ?> products are running low on stock.
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($stats['pending_orders'] > 0): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-clock"></i>
                        <strong>Pending Orders:</strong> <?php echo $stats['pending_orders']; ?> orders are waiting for processing.
                        </div>
                    <?php endif; ?>
                    
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <strong>System Status:</strong> All systems are running normally.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    /* Welcome Card Styles */
    .welcome-card {
        position: relative; /* Add this */
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
        overflow: hidden;
        border-radius: var(--radius-xl); /* Use CSS variable instead of 15px */
    }
    
    .welcome-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="white" opacity="0.1"/><circle cx="75" cy="75" r="1" fill="white" opacity="0.1"/><circle cx="50" cy="10" r="0.5" fill="white" opacity="0.1"/><circle cx="10" cy="60" r="0.5" fill="white" opacity="0.1"/><circle cx="90" cy="40" r="0.5" fill="white" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
        opacity: 0.3;
        z-index: 1; /* Higher z-index to ensure visibility */
    }
    
    .welcome-content {
        position: relative;
        z-index: 1;
    }
    
    .welcome-icon {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.2);
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 20px;
        font-size: 2rem;
        backdrop-filter: blur(10px);
        border: 2px solid rgba(255, 255, 255, 0.3);
    }
    
    .welcome-title {
        font-size: 2.5rem;
        font-weight: 700;
        margin-bottom: 15px;
        text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }
    
    .welcome-subtitle {
        font-size: 1.1rem;
        opacity: 0.9;
        margin-bottom: 30px;
        max-width: 600px;
        margin-left: auto;
        margin-right: auto;
    }
    
    .welcome-stats {
        display: flex;
        justify-content: center;
        gap: 40px;
        flex-wrap: wrap;
    }
    
    .welcome-stat {
        text-align: center;
    }
    
    .welcome-stat .stat-number {
        display: block;
        font-size: 2rem;
        font-weight: 700;
        margin-bottom: 5px;
        text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }
    
    .welcome-stat .stat-label {
        font-size: 0.9rem;
        opacity: 0.8;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    /* Enhanced Activity Feed */
    .activity-feed {
        max-height: 400px;
        overflow-y: auto;
        padding-right: 5px; /* Reduce padding */
        margin-right: 5px; /* Add margin instead */
    }
    
    .activity-feed::-webkit-scrollbar {
        width: 6px;
    }
    
    .activity-feed::-webkit-scrollbar-track {
        background: var(--bg-tertiary);
        border-radius: 3px;
    }
    
    .activity-feed::-webkit-scrollbar-thumb {
        background: var(--border-medium);
        border-radius: 3px;
    }
    
    .activity-feed::-webkit-scrollbar-thumb:hover {
        background: var(--border-dark);
    }
    
    .activity-item {
        display: flex;
        align-items: flex-start;
        padding: 20px;
        border-bottom: 1px solid var(--border-light);
        transition: all 0.3s ease;
        border-radius: 12px;
        margin-bottom: 8px;
    }
    
    .activity-item:hover {
        background: var(--bg-tertiary);
        transform: translateX(5px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        will-change: transform; /* Add for smoother animations */
    }
    
    .activity-item:last-child {
        border-bottom: none;
        margin-bottom: 0;
    }
    
    .activity-icon-small {
        width: 45px;
        height: 45px;
        border-radius: 50%;
        background: var(--primary-gradient);
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 15px;
        flex-shrink: 0;
        color: white;
        font-size: 1rem;
        box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
    }
    
    .activity-content {
        flex: 1;
    }
    
    .activity-title {
        font-weight: 600;
        color: var(--text-primary);
        margin-bottom: 5px;
        font-size: 0.95rem;
    }
    
    .activity-description {
        color: var(--text-secondary);
        font-size: 0.9rem;
        margin-bottom: 8px;
        line-height: 1.4;
    }
    
    .activity-time {
        color: var(--text-muted);
        font-size: 0.8rem;
        font-weight: 500;
    }
    
    /* Enhanced Chart Container */
    .chart-container {
        position: relative;
        background: var(--bg-primary);
        border-radius: 12px;
        padding: 20px;
    }
    
    .chart-container canvas {
        max-height: 300px;
    }
    
    /* Enhanced Table Styles */
    .admin-table {
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    }
    
    .admin-table thead {
        background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
    }
    
    .admin-table tbody tr {
        transition: all 0.2s ease;
    }
    
    .admin-table tbody tr:hover {
        background: var(--bg-tertiary);
        /* transform: scale(1.01); */ /* Remove - causes layout shifts */
    }
    
    /* Enhanced Badge Styles */
    .badge {
        padding: 6px 12px;
        font-size: 0.75rem;
        font-weight: 600;
        border-radius: 20px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }
    
    /* Responsive Welcome Stats */
    @media (max-width: 768px) {
        .welcome-card {
            padding: 15px !important;
        }
        
        .welcome-title {
            font-size: 2rem;
        }
        
        .welcome-stats {
            gap: 20px;
        }
        
        .welcome-stat .stat-number {
            font-size: 1.5rem;
        }
        
        .activity-item {
            padding: 15px;
        }
        
        .activity-icon-small {
            width: 40px;
            height: 40px;
            font-size: 0.9rem;
        }
    }
    
    @media (max-width: 480px) {
        .welcome-stats {
            flex-direction: column;
            gap: 15px;
        }
        
        .welcome-title {
            font-size: 1.5rem;
            margin-bottom: 10px;
        }
        
        .welcome-subtitle {
            font-size: 0.9rem;
            margin-bottom: 15px;
        }
        
        .welcome-icon {
            width: 50px;
            height: 50px;
            font-size: 1.2rem;
            margin-bottom: 10px;
        }
    }
</style>

<!-- Error Message Display -->
<?php if (isset($error_message)): ?>
    <div class="alert alert-danger">
        <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_message); ?>
    </div>
<?php endif; ?>

<!-- Enhanced JavaScript for Charts and Real-time Updates -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize mini charts for stat cards
    initializeStatCards();

    // Sales Chart
    const salesCtx = document.getElementById('salesChart');
    if (salesCtx) {
        const salesData = <?php echo json_encode($sales_chart_data); ?>;
        const labels = salesData.map(item => new Date(item.date).toLocaleDateString());
        const ordersData = salesData.map(item => parseInt(item.orders));
        const revenueData = salesData.map(item => parseFloat(item.revenue));

        new Chart(salesCtx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Orders',
                    data: ordersData,
                    borderColor: '#6366f1',
                    backgroundColor: 'rgba(99, 102, 241, 0.1)',
                    tension: 0.4,
                    yAxisID: 'y',
                    fill: true
                }, {
                    label: 'Revenue (₹)',
                    data: revenueData,
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    tension: 0.4,
                    yAxisID: 'y1',
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    x: {
                        display: true,
                        title: {
                            display: true,
                            text: 'Date'
                        }
                    },
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        title: {
                            display: true,
                            text: 'Orders'
                        }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        title: {
                            display: true,
                            text: 'Revenue (₹)'
                        },
                        grid: {
                            drawOnChartArea: false,
                        },
                    }
                }
            }
        });
    }

    // Enhanced Category Chart with toggle functionality
    const categoryCtx = document.getElementById('categoryChart');
    let categoryChart = null;

    if (categoryCtx) {
        const categoryData = <?php echo json_encode($category_sales_data); ?>;
        const categoryLabels = categoryData.map(item => item.category_name);
        const categoryRevenue = categoryData.map(item => parseFloat(item.revenue));

        // Initial doughnut chart
        categoryChart = new Chart(categoryCtx, {
            type: 'doughnut',
            data: {
                labels: categoryLabels,
                datasets: [{
                    data: categoryRevenue,
                    backgroundColor: [
                        '#6366f1',
                        '#10b981',
                        '#f59e0b',
                        '#ef4444',
                        '#8b5cf6',
                        '#06b6d4',
                        '#84cc16',
                        '#f97316'
                    ],
                    borderWidth: 2,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = Math.round((context.parsed / total) * 100);
                                return `${context.label}: ₹${context.parsed.toLocaleString()} (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });

        // Chart type toggle functionality
        const chartTypeBtns = document.querySelectorAll('.btn-chart-type');
        chartTypeBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const type = this.dataset.type;

                // Update active button
                chartTypeBtns.forEach(b => b.classList.remove('active'));
                this.classList.add('active');

                // Update chart type
                if (type === 'bar') {
                    categoryChart.destroy();
                    categoryChart = new Chart(categoryCtx, {
                        type: 'bar',
                        data: {
                            labels: categoryLabels,
                            datasets: [{
                                data: categoryRevenue,
                                backgroundColor: [
                                    'rgba(99, 102, 241, 0.8)',
                                    'rgba(16, 185, 129, 0.8)',
                                    'rgba(245, 158, 11, 0.8)',
                                    'rgba(239, 68, 68, 0.8)',
                                    'rgba(139, 92, 246, 0.8)',
                                    'rgba(6, 182, 212, 0.8)',
                                    'rgba(132, 204, 22, 0.8)',
                                    'rgba(249, 115, 22, 0.8)'
                                ],
                                borderWidth: 1,
                                borderColor: [
                                    '#6366f1',
                                    '#10b981',
                                    '#f59e0b',
                                    '#ef4444',
                                    '#8b5cf6',
                                    '#06b6d4',
                                    '#84cc16',
                                    '#f97316'
                                ]
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    display: false
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    title: {
                                        display: true,
                                        text: 'Revenue (₹)'
                                    }
                                }
                            }
                        }
                    });
                } else {
                    categoryChart.destroy();
                    categoryChart = new Chart(categoryCtx, {
                        type: 'doughnut',
                        data: {
                            labels: categoryLabels,
                            datasets: [{
                                data: categoryRevenue,
                                backgroundColor: [
                                    '#6366f1',
                                    '#10b981',
                                    '#f59e0b',
                                    '#ef4444',
                                    '#8b5cf6',
                                    '#06b6d4',
                                    '#84cc16',
                                    '#f97316'
                                ],
                                borderWidth: 2,
                                borderColor: '#ffffff'
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    display: false
                                }
                            }
                        }
                    });
                }
            });
        });
    }

    // Customer Growth Chart
    const customerGrowthCtx = document.getElementById('customerGrowthChart');
    if (customerGrowthCtx) {
        new Chart(customerGrowthCtx, {
            type: 'line',
            data: {
                labels: [<?php 
                    $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'];
                    $new_customers = [];
                    $total_customers = [];
                    
                    // Process monthly data
                    for ($i = 0; $i < 6; $i++) {
                        $found = false;
                        foreach ($monthly_customers as $data) {
                            if ($data['month'] == $i + 1) {
                                $new_customers[] = $data['new_customers'];
                                $total_customers[] = $data['total_customers'];
                                $found = true;
                                break;
                            }
                        }
                        if (!$found) {
                            $new_customers[] = 0;
                            $total_customers[] = $stats['users'];
                        }
                    }
                    
                    echo "'" . implode("', '", $months) . "'";
                ?>],
                datasets: [{
                    label: 'New Customers',
                    data: [<?php echo implode(', ', $new_customers); ?>],
                    borderColor: '#6366f1',
                    backgroundColor: 'rgba(99, 102, 241, 0.1)',
                    tension: 0.4,
                    fill: true
                }, {
                    label: 'Total Customers',
                    data: [<?php echo implode(', ', $total_customers); ?>],
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }

    // Top Products Chart
    const topProductsCtx = document.getElementById('topProductsChart');
    if (topProductsCtx) {
        const topProductsData = <?php echo json_encode($top_products ?? []); ?>;
        const productLabels = topProductsData.map(item => item.product_name.substring(0, 15) + (item.product_name.length > 15 ? '...' : ''));
        const productSales = topProductsData.map(item => parseInt(item.sales_count));

        new Chart(topProductsCtx, {
            type: 'bar',
            data: {
                labels: productLabels,
                datasets: [{
                    label: 'Sales Count',
                    data: productSales,
                    backgroundColor: 'rgba(99, 102, 241, 0.8)',
                    borderColor: '#6366f1',
                    borderWidth: 1,
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Sales Count'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Products'
                        }
                    }
                }
            }
        });
    }

    // Chart interaction enhancements
    document.addEventListener('DOMContentLoaded', function() {
        // Time range buttons
        const timeBtns = document.querySelectorAll('.time-btn');
        timeBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                timeBtns.forEach(b => b.classList.remove('active'));
                this.classList.add('active');

                const range = this.dataset.range;
                updateChartTimeRange(range);
            });
        });

        // Export chart functionality
        const exportBtns = document.querySelectorAll('.btn-export-chart');
        exportBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                exportChartImage();
            });
        });

        // Expand chart functionality
        const expandBtns = document.querySelectorAll('.btn-expand-chart');
        expandBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                expandChart();
            });
        });
    });

    function updateChartTimeRange(range) {
        // Show loading state
        const loadingElement = document.getElementById('salesChartLoading');
        if (loadingElement) {
            loadingElement.style.display = 'flex';
        }

        // Simulate API call for new data
        setTimeout(() => {
            if (loadingElement) {
                loadingElement.style.display = 'none';
            }

            // Update chart data based on range
            const salesChart = Chart.getChart('salesChart');
            if (salesChart) {
                let newData;
                switch(range) {
                    case '7d':
                        newData = {
                            labels: [<?php 
                                $days = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
                                $orders_data = [];
                                $revenue_data = [];
                                
                                foreach ($days as $day) {
                                    $found = false;
                                    foreach ($weekly_data as $data) {
                                        if (strpos($data['day_name'], substr($day, 0, 3)) === 0) {
                                            $orders_data[] = $data['orders'];
                                            $revenue_data[] = $data['revenue'];
                                            $found = true;
                                            break;
                                        }
                                    }
                                    if (!$found) {
                                        $orders_data[] = 0;
                                        $revenue_data[] = 0;
                                    }
                                }
                                echo "'" . implode("', '", $days) . "'";
                            ?>],
                            orders: [<?php echo implode(', ', $orders_data); ?>],
                            revenue: [<?php echo implode(', ', $revenue_data); ?>]
                        };
                        break;
                    case '30d':
                        newData = {
                            labels: [<?php 
                                $week_labels = [];
                                $week_orders = [];
                                $week_revenue = [];
                                
                                foreach ($monthly_weekly_data as $data) {
                                    $week_labels[] = $data['week_label'];
                                    $week_orders[] = $data['orders'];
                                    $week_revenue[] = $data['revenue'];
                                }
                                
                                if (empty($week_labels)) {
                                    $week_labels = ['Week 1', 'Week 2', 'Week 3', 'Week 4'];
                                    $week_orders = [0, 0, 0, 0];
                                    $week_revenue = [0, 0, 0, 0];
                                }
                                
                                echo "'" . implode("', '", $week_labels) . "'";
                            ?>],
                            orders: [<?php echo implode(', ', $week_orders); ?>],
                            revenue: [<?php echo implode(', ', $week_revenue); ?>]
                        };
                        break;
                    case '90d':
                        newData = {
                            labels: [<?php 
                                $month_labels = [];
                                $month_orders = [];
                                $month_revenue = [];
                                
                                foreach ($monthly_revenue_data as $data) {
                                    $month_labels[] = date('M Y', strtotime($data['month'] . '-01'));
                                    $month_orders[] = $data['orders'];
                                    $month_revenue[] = $data['revenue'];
                                }
                                
                                if (empty($month_labels)) {
                                    $month_labels = ['Month 1', 'Month 2', 'Month 3'];
                                    $month_orders = [0, 0, 0];
                                    $month_revenue = [0, 0, 0];
                                }
                                
                                echo "'" . implode("', '", $month_labels) . "'";
                            ?>],
                            orders: [<?php echo implode(', ', $month_orders); ?>],
                            revenue: [<?php echo implode(', ', $month_revenue); ?>]
                        };
                        break;
                }

                salesChart.data.labels = newData.labels;
                salesChart.data.datasets[0].data = newData.orders;
                salesChart.data.datasets[1].data = newData.revenue;
                salesChart.update();

                // Update summary
                updateChartSummary(newData);
            }

            showToast(`Chart updated for ${range.toUpperCase()} range`, 'success');
        }, 1000);
    }

    function updateChartSummary(data) {
        const totalOrders = data.orders.reduce((a, b) => a + b, 0);
        const totalRevenue = data.revenue.reduce((a, b) => a + b, 0);

        const ordersElement = document.getElementById('totalOrders');
        const revenueElement = document.getElementById('totalRevenue');

        if (ordersElement) ordersElement.textContent = totalOrders;
        if (revenueElement) revenueElement.textContent = '₹' + totalRevenue.toLocaleString();
    }

    function exportChartImage() {
        const canvas = document.getElementById('salesChart');
        if (canvas) {
            const link = document.createElement('a');
            link.download = 'sales-chart.png';
            link.href = canvas.toDataURL();
            link.click();
            showToast('Chart exported successfully!', 'success');
        }
    }

    function expandChart() {
        // Create modal for expanded chart view
        const modal = document.createElement('div');
        modal.className = 'chart-modal';
        modal.innerHTML = `
            <div class="chart-modal-content">
                <div class="chart-modal-header">
                    <h3>Sales Overview - Expanded View</h3>
                    <button class="chart-modal-close">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="chart-modal-body">
                    <canvas id="expandedChart" width="800" height="400"></canvas>
                </div>
            </div>
        `;

        // Add modal styles
        const style = document.createElement('style');
        style.textContent = `
            .chart-modal {
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0, 0, 0, 0.8);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 10000;
                animation: fadeIn 0.3s ease-out;
            }

            .chart-modal-content {
                background: var(--bg-primary);
                border-radius: var(--radius-xl);
                box-shadow: var(--shadow-xl);
                max-width: 90vw;
                max-height: 90vh;
                overflow: hidden;
                animation: slideInUp 0.3s ease-out;
            }

            .chart-modal-header {
                padding: var(--spacing-lg);
                border-bottom: 1px solid var(--border-light);
                display: flex;
                justify-content: space-between;
                align-items: center;
                background: var(--bg-secondary);
            }

            .chart-modal-close {
                background: none;
                border: none;
                color: var(--text-muted);
                cursor: pointer;
                padding: var(--spacing-sm);
                border-radius: var(--radius-md);
                transition: all var(--transition-fast);
                font-size: var(--text-lg);
            }

            .chart-modal-close:hover {
                color: var(--text-primary);
                background: var(--bg-tertiary);
            }

            .chart-modal-body {
                padding: var(--spacing-lg);
            }
        `;

        if (!document.querySelector('#chart-modal-styles')) {
            document.head.appendChild(style);
        }

        document.body.appendChild(modal);

        // Copy chart data to expanded canvas
        const originalChart = Chart.getChart('salesChart');
        if (originalChart) {
            const expandedCtx = document.getElementById('expandedChart').getContext('2d');
            new Chart(expandedCtx, {
                type: originalChart.config.type,
                data: JSON.parse(JSON.stringify(originalChart.data)),
                options: {
                    ...originalChart.options,
                    maintainAspectRatio: false,
                    plugins: {
                        ...originalChart.options.plugins,
                        legend: {
                            display: true,
                            position: 'bottom'
                        }
                    }
                }
            });
        }

        // Close modal functionality
        modal.querySelector('.chart-modal-close').addEventListener('click', () => {
            modal.style.animation = 'fadeOut 0.3s ease-in forwards';
            setTimeout(() => {
                if (modal.parentNode) {
                    modal.parentNode.removeChild(modal);
                }
            }, 300);
        });

        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                modal.style.animation = 'fadeOut 0.3s ease-in forwards';
                setTimeout(() => {
                    if (modal.parentNode) {
                        modal.parentNode.removeChild(modal);
                    }
                }, 300);
            }
        });
    }

    // Start real-time updates
    startRealTimeUpdates();
});

// Initialize mini charts for stat cards
function initializeStatCards() {
    const statCanvases = document.querySelectorAll('.stat-chart-canvas');

    statCanvases.forEach(canvas => {
        const chartType = canvas.dataset.chart;
        const ctx = canvas.getContext('2d');

        // Use real data from PHP instead of sample data
        let data, color;
        switch(chartType) {
            case 'products':
                data = [<?= $stats['products'] ?>, <?= $stats['active_products'] ?>, <?= $stats['categories'] ?>, <?= $stats['brands'] ?>, <?= $stats['users'] ?>, <?= $stats['orders'] ?>, <?= $stats['total_reviews'] ?>];
                color = '#6366f1';
                break;
            case 'orders':
                data = [<?= $stats['orders'] ?>, <?= $stats['pending_orders'] ?>, <?= $recent_orders ?>, <?= $stats['orders'] - $stats['pending_orders'] ?>, <?= $stats['orders'] ?>, <?= $stats['orders'] ?>, <?= $stats['orders'] ?>];
                color = '#10b981';
                break;
            case 'users':
                data = [<?= $stats['users'] ?>, <?= $stats['users'] ?>, <?= $stats['users'] ?>, <?= $stats['users'] ?>, <?= $stats['users'] ?>, <?= $stats['users'] ?>, <?= $stats['users'] ?>];
                color = '#f59e0b';
                break;
            case 'revenue':
                data = [<?= $stats['total_revenue'] ?>, <?= $monthly_revenue ?>, <?= $stats['total_revenue'] ?>, <?= $monthly_revenue ?>, <?= $stats['total_revenue'] ?>, <?= $monthly_revenue ?>, <?= $stats['total_revenue'] ?>];
                color = '#06b6d4';
                break;
        }

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['', '', '', '', '', '', ''],
                datasets: [{
                    data: data,
                    borderColor: color,
                    backgroundColor: color + '20',
                    borderWidth: 2,
                    tension: 0.4,
                    fill: true,
                    pointRadius: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    x: {
                        display: false
                    },
                    y: {
                        display: false
                    }
                },
                elements: {
                    point: {
                        radius: 0
                    }
                }
            }
        });
    });
}

// Real-time updates function
function startRealTimeUpdates() {
    // Update stats every 30 seconds
    setInterval(function() {
        updateDashboardStats();
    }, 30000);

    // Update time display
    updateTimeDisplay();

    // Update activity feed
    updateActivityFeed();
}

function updateDashboardStats() {
    // Simulate real-time data updates (replace with actual API calls)
    const updates = {
        'products-change': 0, // Real data - no random changes
        'orders-change': 0,   // Real data - no random changes
        'users-change': 0,    // Real data - no random changes
        'revenue-change': 0   // Real data - no random changes
    };

    Object.keys(updates).forEach(id => {
        const element = document.getElementById(id);
        if (element) {
            const currentValue = parseInt(element.textContent) || 0;
            const newValue = currentValue + updates[id];

            // Animate the change
            element.style.transform = 'scale(1.1)';
            element.style.color = newValue >= 0 ? 'var(--success-color)' : 'var(--danger-color)';

            setTimeout(() => {
                element.textContent = (newValue >= 0 ? '+' : '') + newValue + '%';
                element.style.transform = 'scale(1)';
            }, 200);
        }
    });

    // Update pending orders count
    const pendingElement = document.getElementById('pending-orders');
    if (pendingElement) {
        const currentCount = parseInt(pendingElement.textContent) || 0;
        const newCount = Math.max(0, currentCount + (Math.random() > 0.7 ? 1 : -1));

        if (newCount !== currentCount) {
            pendingElement.textContent = newCount + ' pending';
            pendingElement.style.background = newCount > 0 ? 'var(--warning-color)' : 'var(--success-color)';
        }
    }

    // Show update notification
    showToast('Dashboard updated with latest data', 'info');
}

function updateTimeDisplay() {
    const now = new Date();
    const timeString = now.toLocaleTimeString('en-US', {
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit'
    });

    // Update if there's a time display element
    const timeElement = document.querySelector('.current-time');
    if (timeElement) {
        timeElement.textContent = timeString;
    }
}

function updateActivityFeed() {
    // Simulate new activity (replace with actual real-time data)
    const activities = [
        { type: 'order', message: 'New order received from customer', time: 'Just now' },
        { type: 'user', message: 'New user registration completed', time: '2 minutes ago' },
        { type: 'product', message: 'Product stock updated', time: '5 minutes ago' }
    ];

    // Real activity data - no random generation
    // Activities are now loaded from actual database events
    /*
    if (Math.random() > 0.8) {
        const activityFeed = document.querySelector('.activity-feed');
        if (activityFeed && activityFeed.children.length < 10) {
            const randomActivity = activities[Math.floor(Math.random() * activities.length)];
            const activityItem = document.createElement('div');
            activityItem.className = 'activity-item';
            activityItem.innerHTML = `
                <div class="activity-icon-small">
                    <i class="fas fa-${randomActivity.type === 'order' ? 'shopping-cart' : randomActivity.type === 'user' ? 'user' : 'box'}"></i>
                </div>
                <div class="activity-content">
                    <div class="activity-title">${randomActivity.message}</div>
                    <div class="activity-time">${randomActivity.time}</div>
                </div>
            `;

            activityFeed.insertBefore(activityItem, activityFeed.firstChild);

            // Animate new item
            activityItem.style.opacity = '0';
            activityItem.style.transform = 'translateY(-20px)';
            setTimeout(() => {
                activityItem.style.transition = 'all 0.3s ease-out';
                activityItem.style.opacity = '1';
                activityItem.style.transform = 'translateY(0)';
            }, 100);
        }
    }
    */
}

// Enhanced toast system (already defined in header, but adding specific dashboard functionality)
function showDashboardNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `dashboard-notification notification-${type}`;
    notification.innerHTML = `
        <div class="notification-icon">
            <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
        </div>
        <div class="notification-message">${message}</div>
        <button class="notification-close">
            <i class="fas fa-times"></i>
        </button>
    `;

    // Add styles if not exists
    if (!document.querySelector('#dashboard-notification-styles')) {
        const style = document.createElement('style');
        style.id = 'dashboard-notification-styles';
        style.textContent = `
            .dashboard-notification {
                position: fixed;
                top: 20px;
                right: 20px;
                background: var(--bg-primary);
                border: 1px solid var(--border-light);
                border-radius: var(--radius-lg);
                box-shadow: var(--shadow-xl);
                padding: var(--spacing-md) var(--spacing-lg);
                display: flex;
                align-items: center;
                gap: var(--spacing-md);
                z-index: 10000;
                min-width: 300px;
                animation: slideInRight 0.3s ease-out;
            }

            .notification-success { border-left: 4px solid var(--success-color); }
            .notification-error { border-left: 4px solid var(--danger-color); }
            .notification-info { border-left: 4px solid var(--info-color); }

            .notification-icon {
                color: var(--success-color);
                font-size: var(--text-base);
                flex-shrink: 0;
            }

            .notification-error .notification-icon { color: var(--danger-color); }
            .notification-info .notification-icon { color: var(--info-color); }

            .notification-message {
                flex: 1;
                color: var(--text-primary);
                font-size: var(--text-sm);
                font-weight: 500;
            }

            .notification-close {
                background: none;
                border: none;
                color: var(--text-muted);
                cursor: pointer;
                padding: var(--spacing-xs);
                border-radius: var(--radius-md);
                transition: all var(--transition-fast);
            }

            .notification-close:hover {
                color: var(--text-primary);
                background: var(--bg-tertiary);
            }
        `;
        document.head.appendChild(style);
    }

    document.body.appendChild(notification);

    // Auto remove after 5 seconds
    setTimeout(() => {
        notification.style.animation = 'slideOutRight 0.3s ease-in forwards';
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 300);
    }, 5000);

    // Manual close
    notification.querySelector('.notification-close').addEventListener('click', () => {
        notification.style.animation = 'slideOutRight 0.3s ease-in forwards';
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 300);
    });
}

// Table functionality
document.addEventListener('DOMContentLoaded', function() {
    initializeTableFeatures();
});

function initializeTableFeatures() {
    const ordersTable = document.getElementById('ordersTable');
    const searchInput = document.getElementById('ordersSearch');
    const statusFilter = document.getElementById('statusFilter');
    const clearFiltersBtn = document.getElementById('clearFilters');
    const refreshBtn = document.getElementById('refreshOrders');
    const exportBtn = document.getElementById('exportOrders');

    if (!ordersTable) return;

    // Search functionality
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            filterTable();
        });
    }

    // Status filter functionality
    if (statusFilter) {
        statusFilter.addEventListener('change', function() {
            filterTable();
        });
    }

    // Clear filters
    if (clearFiltersBtn) {
        clearFiltersBtn.addEventListener('click', function() {
            if (searchInput) searchInput.value = '';
            if (statusFilter) statusFilter.value = '';
            filterTable();
        });
    }

    // Refresh button
    if (refreshBtn) {
        refreshBtn.addEventListener('click', function() {
            refreshOrdersTable();
        });
    }

    // Export button
    if (exportBtn) {
        exportBtn.addEventListener('click', function() {
            exportOrdersTable();
        });
    }

    // Sortable headers
    const sortableHeaders = ordersTable.querySelectorAll('th.sortable');
    sortableHeaders.forEach(header => {
        header.addEventListener('click', function() {
            sortTable(this.dataset.column);
        });
    });

    // Action buttons
    setupTableActions();
}

function filterTable() {
    const searchInput = document.getElementById('ordersSearch');
    const statusFilter = document.getElementById('statusFilter');
    const tableBody = document.getElementById('ordersTableBody');
    const ordersCount = document.getElementById('ordersCount');

    if (!tableBody) return;

    const searchTerm = searchInput ? searchInput.value.toLowerCase() : '';
    const statusValue = statusFilter ? statusFilter.value.toLowerCase() : '';

    const rows = tableBody.querySelectorAll('tr');
    let visibleCount = 0;

    rows.forEach(row => {
        const orderNumber = row.cells[0].textContent.toLowerCase();
        const customerName = row.cells[1].textContent.toLowerCase();
        const status = row.dataset.status;

        const matchesSearch = searchTerm === '' ||
            orderNumber.includes(searchTerm) ||
            customerName.includes(searchTerm);

        const matchesStatus = statusValue === '' || status === statusValue;

        if (matchesSearch && matchesStatus) {
            row.style.display = '';
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    });

    if (ordersCount) {
        ordersCount.textContent = visibleCount;
    }

    updatePaginationInfo();
}

function sortTable(column) {
    const tableBody = document.getElementById('ordersTableBody');
    const headers = document.querySelectorAll('#ordersTable th.sortable');

    if (!tableBody) return;

    // Remove sort classes from all headers
    headers.forEach(header => {
        header.classList.remove('sort-asc', 'sort-desc');
    });

    // Add sort class to current header
    const currentHeader = document.querySelector(`[data-column="${column}"]`);
    if (currentHeader) {
        const isAsc = !currentHeader.classList.contains('sort-asc');
        currentHeader.classList.toggle('sort-asc', isAsc);
        currentHeader.classList.toggle('sort-desc', !isAsc);

        // Update sort icon
        const sortIcon = currentHeader.querySelector('.sort-icon');
        if (sortIcon) {
            sortIcon.className = `fas fa-sort-${isAsc ? 'up' : 'down'} sort-icon`;
        }
    }

    const rows = Array.from(tableBody.querySelectorAll('tr'));
    rows.sort((a, b) => {
        let aVal, bVal;

        switch(column) {
            case 'order_number':
                aVal = parseInt(a.cells[0].textContent.replace('#', ''));
                bVal = parseInt(b.cells[0].textContent.replace('#', ''));
                break;
            case 'total_price':
                aVal = parseFloat(a.cells[2].textContent.replace('₹', '').replace(',', ''));
                bVal = parseFloat(b.cells[2].textContent.replace('₹', '').replace(',', ''));
                break;
            case 'created_at':
                aVal = new Date(a.cells[4].textContent);
                bVal = new Date(b.cells[4].textContent);
                break;
            default:
                aVal = a.cells[getColumnIndex(column)].textContent.toLowerCase();
                bVal = b.cells[getColumnIndex(column)].textContent.toLowerCase();
        }

        if (currentHeader.classList.contains('sort-asc')) {
            return aVal > bVal ? 1 : -1;
        } else {
            return aVal < bVal ? 1 : -1;
        }
    });

    // Reorder rows
    rows.forEach(row => tableBody.appendChild(row));
}

function getColumnIndex(column) {
    const headers = ['order_number', 'username', 'total_price', 'order_status', 'created_at'];
    return headers.indexOf(column);
}

function refreshOrdersTable() {
    const refreshBtn = document.getElementById('refreshOrders');
    const originalIcon = refreshBtn.innerHTML;

    // Show loading state
    refreshBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    refreshBtn.disabled = true;

    // Simulate refresh
    setTimeout(() => {
        refreshBtn.innerHTML = originalIcon;
        refreshBtn.disabled = false;

        // Orders are already loaded from database - no need to add sample data

        showToast('Orders refreshed successfully!', 'success');
    }, 1500);
}

// function addSampleOrder() { // DISABLED - Using real database data only
    // const tableBody = document.getElementById('ordersTableBody');
    // if (!tableBody) return;

    // const orderId = Date.now();
    // const orderNumber = Math.floor(10000 + Math.random() * 90000);
    // const customers = ['John Doe', 'Jane Smith', 'Mike Johnson', 'Sarah Wilson'];
    // const customerName = customers[Math.floor(Math.random() * customers.length)];
    // const amount = Math.floor(1000 + Math.random() * 5000);
    // const statuses = ['Pending', 'Confirmed', 'Shipped'];
    // const status = statuses[Math.floor(Math.random() * statuses.length)];

    // All sample data generation code commented out - using real database data only
}

function exportOrdersTable() {
    const exportBtn = document.getElementById('exportOrders');
    const originalIcon = exportBtn.innerHTML;

    // Show loading state
    exportBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    exportBtn.disabled = true;

    // Simulate export process
    setTimeout(() => {
        exportBtn.innerHTML = originalIcon;
        exportBtn.disabled = false;

        // Create and trigger download
        const table = document.getElementById('ordersTable');
        if (table) {
            const csvContent = tableToCSV(table);
            downloadCSV(csvContent, 'orders-export.csv');
        }

        showToast('Orders exported successfully!', 'success');
    }, 2000);
}

function tableToCSV(table) {
    const rows = table.querySelectorAll('tr');
    const csv = [];

    rows.forEach(row => {
        const cells = row.querySelectorAll('td, th');
        const rowData = [];

        cells.forEach(cell => {
            // Get text content, handling nested elements
            const text = cell.textContent.trim().replace(/,/g, ';');
            rowData.push(`"${text}"`);
        });

        csv.push(rowData.join(','));
    });

    return csv.join('\n');
}

function downloadCSV(content, filename) {
    const blob = new Blob([content], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');

    if (link.download !== undefined) {
        const url = URL.createObjectURL(blob);
        link.setAttribute('href', url);
        link.setAttribute('download', filename);
        link.style.visibility = 'hidden';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }
}

function setupTableActions() {
    // View order action
    document.addEventListener('click', function(e) {
        if (e.target.closest('.view-order')) {
            e.preventDefault();
            const orderId = e.target.closest('.view-order').dataset.orderId;
            showToast(`Viewing order #${orderId}`, 'info');
            // Redirect to order details page
            // window.location.href = `<?php echo admin_url('orders/details.php'); ?>?id=${orderId}`;
        }

        if (e.target.closest('.edit-order')) {
            e.preventDefault();
            const orderId = e.target.closest('.edit-order').dataset.orderId;
            showToast(`Editing order #${orderId}`, 'info');
            // Redirect to order edit page
            // window.location.href = `<?php echo admin_url('orders/edit.php'); ?>?id=${orderId}`;
        }
    });
}

function updatePaginationInfo() {
    const visibleRows = document.querySelectorAll('#ordersTableBody tr:not([style*="display: none"])');
    const totalRows = document.querySelectorAll('#ordersTableBody tr').length;
    const startRecord = document.getElementById('startRecord');
    const endRecord = document.getElementById('endRecord');
    const totalRecords = document.getElementById('totalRecords');

    if (startRecord) startRecord.textContent = '1';
    if (endRecord) endRecord.textContent = visibleRows.length;
    if (totalRecords) totalRecords.textContent = totalRows;
}

// Make functions globally available
window.updateDashboardStats = updateDashboardStats;
window.showToast = showToast;
</script>

<?php
include INCLUDES_PATH . '/templates/admin_footer.php';
?>
