<?php 
// session_start() is now called in the main file before including this file
// Database connection now in init.php // Ensure this file connects to your database

// Fetch admin details if logged in
$admin = null;
if (isset($_SESSION['admin_id'])) {
    $query = $pdo->prepare("SELECT * FROM admins WHERE id = ?");
    $query->execute([$_SESSION['admin_id']]);
    $admin = $query->fetch(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?>Admin Panel</title>
    
    <!-- Bootstrap CSS -->
    <link href="<?php echo CSS_URL; ?>/bootstrap.min.css?v=<?php echo APP_VERSION; ?>" rel="stylesheet">
    
    <!-- Font Awesome CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
          integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw=="
          crossorigin="anonymous" referrerpolicy="no-referrer">
    
    <!-- Chart.js -->
    <script src="<?php echo JS_URL; ?>/chart.min.js?v=<?php echo APP_VERSION; ?>"></script>
    
    <!-- Using system fonts for better performance and CSP compliance -->
    
    <!-- Custom Admin CSS -->
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/admin-modern.css?v=<?php echo APP_VERSION; ?>">
    
    <style>
        /* Additional custom styles for specific components */
        .sidebar-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 999;
            display: none;
        }
        
        .sidebar-overlay.show {
            display: block;
        }
        
        @media (max-width: 1024px) {
            .admin-sidebar {
                transform: translateX(-100%);
            }
            
            .admin-sidebar.show {
                transform: translateX(0);
            }
            
            .admin-main {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
    <!-- Admin Layout -->
    <div class="admin-layout">

    <!-- Sidebar Overlay for Mobile -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- Sidebar -->
    <aside class="admin-sidebar" id="adminSidebar">
                <!-- Sidebar Header -->
                <div class="sidebar-header">
                    <a href="<?php echo admin_url('index.php'); ?>" class="sidebar-logo">
                        <div class="logo-icon">
                            <i class="fas fa-crown"></i>
                        </div>
                        <div class="logo-content">
                            <span class="logo-title">Admin Panel</span>
                            <span class="logo-subtitle">AC Management</span>
                        </div>
                    </a>
                    <button class="sidebar-collapse-btn" id="sidebarCollapseBtn" title="Collapse Sidebar">
                        <i class="fas fa-angle-left"></i>
                    </button>
                </div>

                <!-- Sidebar Navigation -->
                <nav class="sidebar-nav">
                    <!-- Dashboard Section -->
                    <div class="nav-section">
                        <div class="nav-section-header" data-section="main">
                            <div class="nav-section-title">
                                <i class="fas fa-th-large"></i>
                                <span>Main</span>
                            </div>
                            <button class="section-toggle-btn" data-target="main" aria-label="Toggle Main section">
                                <i class="fas fa-chevron-down"></i>
                            </button>
                        </div>
                        <ul class="nav flex-column nav-section-content show" data-section="main">
                            <li class="nav-item">
                                <?php 
                                // Determine if we're on the dashboard - only active on admin/index.php
                                $is_dashboard = (strpos($_SERVER['REQUEST_URI'], '/admin') !== false && 
                                               strpos($_SERVER['REQUEST_URI'], '/products') === false && 
                                               strpos($_SERVER['REQUEST_URI'], '/orders') === false && 
                                               strpos($_SERVER['REQUEST_URI'], '/users') === false && 
                                               strpos($_SERVER['REQUEST_URI'], '/categories') === false && 
                                               strpos($_SERVER['REQUEST_URI'], '/brands') === false && 
                                               strpos($_SERVER['REQUEST_URI'], '/reviews') === false &&
                                               strpos($_SERVER['REQUEST_URI'], '/services') === false && 
                                               strpos($_SERVER['REQUEST_URI'], '/reports') === false && 
                                               strpos($_SERVER['REQUEST_URI'], '/settings') === false &&
                                               strpos($_SERVER['REQUEST_URI'], '/security_monitor') === false &&
                                               strpos($_SERVER['REQUEST_URI'], '/setup_security') === false &&
                                               strpos($_SERVER['REQUEST_URI'], '/register') === false);
                                ?>
                                <a class="nav-link <?php echo $is_dashboard ? 'active' : ''; ?>" href="<?php echo admin_url('index.php'); ?>" aria-label="Dashboard - Overview & Analytics">
                                    <div class="nav-link-icon">
                                        <i class="fas fa-tachometer-alt"></i>
                                    </div>
                                    <div class="nav-link-content">
                                        <span class="nav-link-title">Dashboard</span>
                                        <span class="nav-link-subtitle">Overview & Analytics</span>
                                    </div>
                                    <div class="nav-link-badge">
                                        <span class="pulse-dot" aria-label="Live indicator"></span>
                                    </div>
                                </a>
                            </li>
                        </ul>
                    </div>

                    <!-- Management Section -->
                    <div class="nav-section">
                        <div class="nav-section-header" data-section="management">
                            <div class="nav-section-title">
                                <i class="fas fa-cogs"></i>
                                <span>Management</span>
                            </div>
                            <button class="section-toggle-btn" data-target="management" aria-label="Toggle Management section">
                                <i class="fas fa-chevron-up"></i>
                            </button>
                        </div>
                        <ul class="nav flex-column nav-section-content show" data-section="management">
                            <li class="nav-item">
                                <?php 
                                // Products main page - only active on main products page, not sub-pages
                                $is_products_page = (strpos($_SERVER['REQUEST_URI'], '/products') !== false && 
                                                    strpos($_SERVER['REQUEST_URI'], '/products/add') === false && 
                                                    strpos($_SERVER['REQUEST_URI'], '/products/edit') === false && 
                                                    strpos($_SERVER['REQUEST_URI'], '/products/delete') === false && 
                                                    strpos($_SERVER['REQUEST_URI'], '/products/update') === false);
                                ?>
                                <a class="nav-link <?php echo $is_products_page ? 'active' : ''; ?>" href="<?php echo admin_url('products'); ?>" aria-label="Products - Manage inventory">
                                    <div class="nav-link-icon">
                                        <i class="fas fa-cubes"></i>
                                    </div>
                                    <div class="nav-link-content">
                                        <span class="nav-link-title">Products</span>
                                        <span class="nav-link-subtitle">Manage inventory</span>
                                    </div>
                                    <div class="nav-link-badge">
                                        <?php
                                        try {
                                            $product_count = $pdo->query("SELECT COUNT(*) FROM products WHERE status = 'active'")->fetchColumn();
                                            if ($product_count > 0) {
                                                echo '<span class="count-badge" aria-label="' . $product_count . ' active products">' . $product_count . '</span>';
                                            }
                                        } catch (PDOException $e) {
                                            // Handle error silently
                                        }
                                        ?>
                                    </div>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/products/add') !== false ? 'active' : ''; ?>" href="<?php echo admin_url('products/add'); ?>" aria-label="Add Product - New item">
                                    <div class="nav-link-icon">
                                        <i class="fas fa-plus-circle"></i>
                                    </div>
                                    <div class="nav-link-content">
                                        <span class="nav-link-title">Add Product</span>
                                        <span class="nav-link-subtitle">New item</span>
                                    </div>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/orders') !== false ? 'active' : ''; ?>" href="<?php echo admin_url('orders'); ?>" aria-label="Orders - Customer orders">
                                    <div class="nav-link-icon">
                                        <i class="fas fa-shopping-cart"></i>
                                    </div>
                                    <div class="nav-link-content">
                                        <span class="nav-link-title">Orders</span>
                                        <span class="nav-link-subtitle">Customer orders</span>
                                    </div>
                                    <div class="nav-link-badge">
                                        <?php
                                        // Show pending orders count
                                        try {
                                            $pending_orders = $pdo->query("SELECT COUNT(*) FROM orders WHERE order_status = 'Pending'")->fetchColumn();
                                            if ($pending_orders > 0) {
                                                echo '<span class="count-badge warning" aria-label="' . $pending_orders . ' pending orders">' . $pending_orders . '</span>';
                                            }
                                        } catch (PDOException $e) {
                                            // Handle error silently
                                        }
                                        ?>
                                    </div>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/users') !== false ? 'active' : ''; ?>" href="<?php echo admin_url('users'); ?>" aria-label="Users - Customer management">
                                    <div class="nav-link-icon">
                                        <i class="fas fa-users"></i>
                                    </div>
                                    <div class="nav-link-content">
                                        <span class="nav-link-title">Users</span>
                                        <span class="nav-link-subtitle">Customer management</span>
                                    </div>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/categories') !== false ? 'active' : ''; ?>" href="<?php echo admin_url('categories'); ?>" aria-label="Categories - Product categories">
                                    <div class="nav-link-icon">
                                        <i class="fas fa-tags"></i>
                                    </div>
                                    <div class="nav-link-content">
                                        <span class="nav-link-title">Categories</span>
                                        <span class="nav-link-subtitle">Product categories</span>
                                    </div>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/brands') !== false ? 'active' : ''; ?>" href="<?php echo admin_url('brands'); ?>" aria-label="Brands - Brand management">
                                    <div class="nav-link-icon">
                                        <i class="fas fa-trademark"></i>
                                    </div>
                                    <div class="nav-link-content">
                                        <span class="nav-link-title">Brands</span>
                                        <span class="nav-link-subtitle">Brand management</span>
                                    </div>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/reviews') !== false ? 'active' : ''; ?>" href="<?php echo admin_url('reviews'); ?>" aria-label="Reviews - Customer feedback">
                                    <div class="nav-link-icon">
                                        <i class="fas fa-star"></i>
                                    </div>
                                    <div class="nav-link-content">
                                        <span class="nav-link-title">Reviews</span>
                                        <span class="nav-link-subtitle">Customer feedback</span>
                                    </div>
                                    <div class="nav-link-badge">
                                        <?php
                                        // Show pending reviews count
                                        try {
                                            $pending_reviews = $pdo->query("SELECT COUNT(*) FROM reviews WHERE status = 'pending'")->fetchColumn();
                                            if ($pending_reviews > 0) {
                                                echo '<span class="count-badge warning" aria-label="' . $pending_reviews . ' pending reviews">' . $pending_reviews . '</span>';
                                            }
                                        } catch (PDOException $e) {
                                            // Handle error silently
                                        }
                                        ?>
                                    </div>
                                </a>
                            </li>
                        </ul>
                    </div>

                    <!-- Services Section -->
                    <div class="nav-section">
                        <div class="nav-section-header" data-section="services">
                            <div class="nav-section-title">
                                <i class="fas fa-wrench"></i>
                                <span>Services</span>
                            </div>
                            <button class="section-toggle-btn" data-target="services" aria-label="Toggle Services section">
                                <i class="fas fa-chevron-up"></i>
                            </button>
                        </div>
                        <ul class="nav flex-column nav-section-content show" data-section="services">
                            <li class="nav-item">
                                <a class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/services') !== false && strpos($_SERVER['REQUEST_URI'], '/installations') === false && strpos($_SERVER['REQUEST_URI'], '/amc') === false ? 'active' : ''; ?>" href="<?php echo admin_url('services'); ?>" aria-label="Service Management - Service tracking">
                                    <div class="nav-link-icon">
                                        <i class="fas fa-wrench"></i>
                                    </div>
                                    <div class="nav-link-content">
                                        <span class="nav-link-title">Service Management</span>
                                        <span class="nav-link-subtitle">Service tracking</span>
                                    </div>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/services/installations') !== false ? 'active' : ''; ?>" href="<?php echo admin_url('services/installations'); ?>" aria-label="Installation Schedule - Service appointments">
                                    <div class="nav-link-icon">
                                        <i class="fas fa-calendar-check"></i>
                                    </div>
                                    <div class="nav-link-content">
                                        <span class="nav-link-title">Installation Schedule</span>
                                        <span class="nav-link-subtitle">Service appointments</span>
                                    </div>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/services/amc') !== false ? 'active' : ''; ?>" href="<?php echo admin_url('services/amc'); ?>" aria-label="AMC Management - Annual contracts">
                                    <div class="nav-link-icon">
                                        <i class="fas fa-file-contract"></i>
                                    </div>
                                    <div class="nav-link-content">
                                        <span class="nav-link-title">AMC Management</span>
                                        <span class="nav-link-subtitle">Annual contracts</span>
                                    </div>
                                </a>
                            </li>
                        </ul>
                    </div>

                    <!-- Security Section -->
                    <div class="nav-section">
                        <div class="nav-section-header" data-section="security">
                            <div class="nav-section-title">
                                <i class="fas fa-shield-alt"></i>
                                <span>Security</span>
                            </div>
                            <button class="section-toggle-btn" data-target="security" aria-label="Toggle Security section">
                                <i class="fas fa-chevron-up"></i>
                            </button>
                        </div>
                        <ul class="nav flex-column nav-section-content show" data-section="security">
                            <li class="nav-item">
                                <a class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/security_monitor') !== false ? 'active' : ''; ?>" href="<?php echo admin_url('security_monitor'); ?>" aria-label="Security Monitor - System security overview">
                                    <div class="nav-link-icon">
                                        <i class="fas fa-shield-alt"></i>
                                    </div>
                                    <div class="nav-link-content">
                                        <span class="nav-link-title">Security Monitor</span>
                                        <span class="nav-link-subtitle">System security overview</span>
                                    </div>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/setup_security') !== false ? 'active' : ''; ?>" href="<?php echo admin_url('setup_security'); ?>" aria-label="Setup Security - Configure security tables">
                                    <div class="nav-link-icon">
                                        <i class="fas fa-cog"></i>
                                    </div>
                                    <div class="nav-link-content">
                                        <span class="nav-link-title">Setup Security</span>
                                        <span class="nav-link-subtitle">Configure security tables</span>
                                    </div>
                                </a>
                            </li>
                        </ul>
                    </div>

                    <!-- Reports Section -->
                    <div class="nav-section">
                        <div class="nav-section-header" data-section="reports">
                            <div class="nav-section-title">
                                <i class="fas fa-chart-line"></i>
                                <span>Reports</span>
                            </div>
                            <button class="section-toggle-btn" data-target="reports" aria-label="Toggle Reports section">
                                <i class="fas fa-chevron-up"></i>
                            </button>
                        </div>
                        <ul class="nav flex-column nav-section-content show" data-section="reports">
                            <li class="nav-item">
                                <a class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/reports/sales') !== false ? 'active' : ''; ?>" href="<?php echo admin_url('reports/sales'); ?>" aria-label="Sales Report - Revenue analytics">
                                    <div class="nav-link-icon">
                                        <i class="fas fa-chart-line"></i>
                                    </div>
                                    <div class="nav-link-content">
                                        <span class="nav-link-title">Sales Report</span>
                                        <span class="nav-link-subtitle">Revenue analytics</span>
                                    </div>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/reports/customer') !== false ? 'active' : ''; ?>" href="<?php echo admin_url('reports/customer'); ?>" aria-label="Customer Report - Customer analytics">
                                    <div class="nav-link-icon">
                                        <i class="fas fa-users"></i>
                                    </div>
                                    <div class="nav-link-content">
                                        <span class="nav-link-title">Customer Report</span>
                                        <span class="nav-link-subtitle">Customer analytics</span>
                                    </div>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/reports/dashboard') !== false ? 'active' : ''; ?>" href="<?php echo admin_url('reports/dashboard'); ?>" aria-label="Analytics Dashboard - Business insights">
                                    <div class="nav-link-icon">
                                        <i class="fas fa-chart-pie"></i>
                                    </div>
                                    <div class="nav-link-content">
                                        <span class="nav-link-title">Analytics Dashboard</span>
                                        <span class="nav-link-subtitle">Business insights</span>
                                    </div>
                                </a>
                            </li>
                        </ul>
                    </div>

                    <!-- Settings Section -->
                    <div class="nav-section">
                        <div class="nav-section-header" data-section="settings">
                            <div class="nav-section-title">
                                <i class="fas fa-cog"></i>
                                <span>Settings</span>
                            </div>
                            <button class="section-toggle-btn" data-target="settings" aria-label="Toggle Settings section">
                                <i class="fas fa-chevron-up"></i>
                            </button>
                        </div>
                        <ul class="nav flex-column nav-section-content show" data-section="settings">
                            <li class="nav-item">
                                <a class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/settings') !== false && strpos($_SERVER['REQUEST_URI'], '/profile') === false ? 'active' : ''; ?>" href="<?php echo admin_url('settings'); ?>" aria-label="General Settings - System configuration">
                                    <div class="nav-link-icon">
                                        <i class="fas fa-gear"></i>
                                    </div>
                                    <div class="nav-link-content">
                                        <span class="nav-link-title">General Settings</span>
                                        <span class="nav-link-subtitle">System configuration</span>
                                    </div>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/settings/profile') !== false ? 'active' : ''; ?>" href="<?php echo admin_url('settings/profile'); ?>" aria-label="Edit Profile - Account settings">
                                    <div class="nav-link-icon">
                                        <i class="fas fa-user-gear"></i>
                                    </div>
                                    <div class="nav-link-content">
                                        <span class="nav-link-title">Edit Profile</span>
                                        <span class="nav-link-subtitle">Account settings</span>
                                    </div>
                                </a>
                            </li>
                        </ul>
                    </div>

                    <!-- System Section -->
                    <div class="nav-section">
                        <div class="nav-section-header" data-section="system">
                            <div class="nav-section-title">
                                <i class="fas fa-server"></i>
                                <span>System</span>
                            </div>
                            <button class="section-toggle-btn" data-target="system" aria-label="Toggle System section">
                                <i class="fas fa-chevron-up"></i>
                            </button>
                        </div>
                        <ul class="nav flex-column nav-section-content show" data-section="system">
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo user_url(); ?>" target="_blank" aria-label="View Website - Open in new tab">
                                    <div class="nav-link-icon">
                                        <i class="fas fa-external-link-alt"></i>
                                    </div>
                                    <div class="nav-link-content">
                                        <span class="nav-link-title">View Website</span>
                                        <span class="nav-link-subtitle">Open in new tab</span>
                                    </div>
                                    <div class="nav-link-badge">
                                        <i class="fas fa-external-link-alt"></i>
                                    </div>
                                </a>
                            </li>
                        </ul>
                    </div>
                </nav>

                <!-- Sidebar Footer -->
                <div class="sidebar-footer">
                    <div class="sidebar-version">
                        <span>Version 2.1.0</span>
                    </div>
                    <div class="sidebar-social">
                        <a href="#" class="social-link" title="Documentation">
                            <i class="fas fa-book"></i>
                        </a>
                        <a href="#" class="social-link" title="Support">
                            <i class="fas fa-question-circle"></i>
                        </a>
                    </div>
                </div>
            </aside>
        
        <!-- Main Content -->
        <main class="admin-main" id="adminMain">
            <!-- Header -->
            <header class="admin-header">
                <div class="header-content">
                    <div class="header-left">
                        <button class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle navigation sidebar" aria-expanded="false" aria-controls="adminSidebar">
                            <i class="fas fa-bars"></i>
                        </button>
                        
                        <nav class="breadcrumb" aria-label="Breadcrumb navigation">
                            <a href="<?php echo admin_url('index.php'); ?>" aria-current="page">Dashboard</a>
                            <?php if (isset($page_title)): ?>
                                <span class="breadcrumb-separator" aria-hidden="true">/</span>
                                <span class="breadcrumb-current" aria-current="page"><?php echo $page_title; ?></span>
                            <?php endif; ?>
                        </nav>
                    </div>
                    
                    <div class="header-right">
                        <!-- Enhanced Search -->
                        <div class="header-search">
                            <div class="search-container">
                                <i class="fas fa-search search-icon" aria-hidden="true"></i>
                                <input type="text" placeholder="Search products, orders, users..." class="search-input" id="headerSearch" name="search" aria-label="Search" autocomplete="off" role="searchbox" aria-describedby="search-help">
                                <div class="search-suggestions" id="searchSuggestions" style="display: none;" role="listbox" aria-label="Search suggestions">
                                    <div class="search-loading" aria-live="polite">
                                        <i class="fas fa-spinner fa-spin" aria-hidden="true"></i>
                                        <span>Searching...</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Header Actions -->
                        <div class="header-actions">
                            <!-- Quick Actions -->
                            <div class="quick-actions">
                                <button class="quick-action-btn" title="Add New Product" data-bs-toggle="tooltip">
                                    <i class="fas fa-plus"></i>
                                </button>
                                <button class="quick-action-btn" title="Export Data" data-bs-toggle="tooltip">
                                    <i class="fas fa-download"></i>
                                </button>
                                <button class="quick-action-btn" title="Refresh" data-bs-toggle="tooltip">
                                    <i class="fas fa-sync-alt"></i>
                                </button>
                            </div>

                            <!-- Enhanced Notifications -->
                            <div class="dropdown notification-dropdown-container">
                                <button class="notification-btn" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Notifications" aria-haspopup="true" role="button">
                                    <i class="fas fa-bell" aria-hidden="true"></i>
                                    <?php
                                    // Get unread notification count
                                    $unread_count = 0;
                                    if (isset($_SESSION['admin_id'])) {
                                        try {
                                            $unread_query = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE admin_id = ? AND is_read = 0");
                                            $unread_query->execute([$_SESSION['admin_id']]);
                                            $unread_count = $unread_query->fetchColumn();
                                        } catch (PDOException $e) {
                                            $unread_count = 0;
                                        }
                                    }
                                    ?>
                                    <?php if ($unread_count > 0): ?>
                                        <span class="notification-badge pulse" aria-label="<?php echo $unread_count; ?> unread notifications"><?php echo $unread_count; ?></span>
                                    <span class="notification-dot" aria-hidden="true"></span>
                                    <?php endif; ?>
                                </button>
                                <div class="dropdown-menu dropdown-menu-end notification-dropdown" role="menu" aria-labelledby="notifications-menu">
                                    <div class="notification-header">
                                        <h6 class="dropdown-header" id="notifications-menu">
                                            <i class="fas fa-bell" aria-hidden="true"></i>
                                            Notifications
                                        </h6>
                                        <button class="mark-all-read" title="Mark all as read" aria-label="Mark all notifications as read">
                                            <i class="fas fa-check-double" aria-hidden="true"></i>
                                        </button>
                                    </div>
                                    <div class="notification-list" role="none">
                                        <?php
                                        // Get recent notifications for admin
                                        $recent_notifications = [];
                                        if (isset($_SESSION['admin_id'])) {
                                            try {
                                                $notifications_query = $pdo->prepare("
                                                    SELECT * FROM notifications 
                                                    WHERE admin_id = ? 
                                                    ORDER BY created_at DESC 
                                                    LIMIT 5
                                                ");
                                                $notifications_query->execute([$_SESSION['admin_id']]);
                                                $recent_notifications = $notifications_query->fetchAll(PDO::FETCH_ASSOC);
                                            } catch (PDOException $e) {
                                                $recent_notifications = [];
                                            }
                                        }
                                        
                                        if (!empty($recent_notifications)):
                                            foreach ($recent_notifications as $notification):
                                                $icon_class = '';
                                                $icon_name = '';
                                                
                                                switch($notification['type']) {
                                                    case 'order':
                                                        $icon_class = 'success';
                                                        $icon_name = 'fas fa-receipt';
                                                        break;
                                                    case 'service':
                                                        $icon_class = 'warning';
                                                        $icon_name = 'fas fa-tools';
                                                        break;
                                                    case 'offer':
                                                        $icon_class = 'info';
                                                        $icon_name = 'fas fa-gift';
                                                        break;
                                                    case 'system':
                                                        $icon_class = 'primary';
                                                        $icon_name = 'fas fa-cog';
                                                        break;
                                                    case 'stock':
                                                        $icon_class = 'danger';
                                                        $icon_name = 'fas fa-exclamation-triangle';
                                                        break;
                                                    default:
                                                        $icon_class = 'info';
                                                        $icon_name = 'fas fa-bell';
                                                }
                                                
                                                $time_ago = '';
                                                $created_at = new DateTime($notification['created_at']);
                                                $now = new DateTime();
                                                $diff = $now->diff($created_at);
                                                
                                                if ($diff->days > 0) {
                                                    $time_ago = $diff->days . ' day' . ($diff->days > 1 ? 's' : '') . ' ago';
                                                } elseif ($diff->h > 0) {
                                                    $time_ago = $diff->h . ' hour' . ($diff->h > 1 ? 's' : '') . ' ago';
                                                } elseif ($diff->i > 0) {
                                                    $time_ago = $diff->i . ' minute' . ($diff->i > 1 ? 's' : '') . ' ago';
                                                } else {
                                                    $time_ago = 'Just now';
                                                }
                                        ?>
                                        <a class="dropdown-item notification-item <?php echo $notification['is_read'] ? '' : 'unread'; ?>" 
                                           href="<?php echo admin_url('notifications'); ?>" 
                                           data-notification-id="<?php echo $notification['id']; ?>" 
                                           role="menuitem" tabindex="0">
                                            <div class="notification-icon <?php echo $icon_class; ?>" aria-hidden="true">
                                                <i class="<?php echo $icon_name; ?>"></i>
                                            </div>
                                            <div class="notification-content">
                                                <div class="notification-title"><?php echo htmlspecialchars($notification['title']); ?></div>
                                                <div class="notification-message"><?php echo htmlspecialchars($notification['message']); ?></div>
                                                <div class="notification-time"><?php echo $time_ago; ?></div>
                                            </div>
                                            <?php if (!$notification['is_read']): ?>
                                            <div class="notification-actions">
                                                <button class="btn-mark-read" title="Mark as read" aria-label="Mark this notification as read" data-notification-id="<?php echo $notification['id']; ?>">
                                                    <i class="fas fa-check" aria-hidden="true"></i>
                                                </button>
                                            </div>
                                            <?php endif; ?>
                                        </a>
                                        <?php 
                                            endforeach;
                                        else:
                                        ?>
                                        <div class="dropdown-item text-center text-muted">
                                            <i class="fas fa-bell-slash mb-2"></i>
                                            <div>No notifications</div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="notification-footer">
                                        <a class="dropdown-item text-center view-all-notifications" href="<?php echo admin_url('notifications'); ?>" role="menuitem" tabindex="0">
                                            <i class="fas fa-eye" aria-hidden="true"></i>
                                            View All Notifications
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- Enhanced User Menu -->
                            <div class="dropdown user-menu">
                                <button class="user-avatar" data-bs-toggle="dropdown" aria-expanded="false" aria-label="User menu" aria-haspopup="true" role="button" id="user-menu-button">
                                    <div class="avatar-circle">
                                        <?php echo strtoupper(substr($admin['username'] ?? 'A', 0, 1)); ?>
                                    </div>
                                    <div class="user-status online" aria-label="Online status"></div>
                                </button>
                                <div class="dropdown-menu dropdown-menu-end user-dropdown" role="menu" aria-labelledby="user-menu-button">
                                    <div class="user-info-header">
                                        <div class="user-avatar-large" aria-hidden="true">
                                            <?php echo strtoupper(substr($admin['username'] ?? 'A', 0, 1)); ?>
                                        </div>
                                        <div class="user-details">
                                            <div class="user-name"><?php echo htmlspecialchars($admin['username'] ?? 'Admin'); ?></div>
                                            <div class="user-role">Administrator</div>
                                            <div class="user-email"><?php echo htmlspecialchars($admin['email'] ?? 'aakashjamnagar@gmail.com'); ?></div>
                                        </div>
                                    </div>
                                    <div class="dropdown-divider" role="separator"></div>
                                    <a class="dropdown-item" href="<?php echo admin_url('index.php'); ?>" role="menuitem" tabindex="0">
                                        <div class="dropdown-item-content">
                                            <i class="fas fa-tachometer-alt" aria-hidden="true"></i>
                                            <span>Dashboard</span>
                                            <i class="fas fa-chevron-right shortcut-icon" aria-hidden="true"></i>
                                        </div>
                                    </a>
                                    <a class="dropdown-item" href="<?php echo admin_url('settings/profile'); ?>" role="menuitem" tabindex="0">
                                        <div class="dropdown-item-content">
                                            <i class="fas fa-user-edit" aria-hidden="true"></i>
                                            <span>Edit Profile</span>
                                            <i class="fas fa-chevron-right shortcut-icon" aria-hidden="true"></i>
                                        </div>
                                    </a>
                                    <a class="dropdown-item" href="<?php echo admin_url('settings'); ?>" role="menuitem" tabindex="0">
                                        <div class="dropdown-item-content">
                                            <i class="fas fa-cog" aria-hidden="true"></i>
                                            <span>Settings</span>
                                            <i class="fas fa-chevron-right shortcut-icon" aria-hidden="true"></i>
                                        </div>
                                    </a>
                                    <div class="dropdown-divider" role="separator"></div>
                                    <button class="dropdown-item theme-toggle" id="themeToggle" role="menuitem" tabindex="0" aria-pressed="false">
                                        <div class="dropdown-item-content">
                                            <i class="fas fa-moon" aria-hidden="true"></i>
                                            <span>Dark Mode</span>
                                            <div class="theme-toggle-switch" role="switch" aria-label="Toggle dark mode">
                                                <div class="switch-track">
                                                    <div class="switch-thumb"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </button>
                                    <div class="dropdown-divider" role="separator"></div>
                                    <a class="dropdown-item text-danger" href="<?php echo admin_url('logout'); ?>" role="menuitem" tabindex="0">
                                        <div class="dropdown-item-content">
                                            <i class="fas fa-sign-out-alt" aria-hidden="true"></i>
                                            <span>Logout</span>
                                            <i class="fas fa-chevron-right shortcut-icon" aria-hidden="true"></i>
                                        </div>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Toast notification system -->
            <script>
                function showToast(message, type = 'info') {
                    const toast = document.createElement('div');
                    toast.className = `toast toast-${type}`;
                    toast.innerHTML = `
                        <div class="toast-icon">
                            <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
                        </div>
                        <div class="toast-content">
                            <div class="toast-message">${message}</div>
                        </div>
                        <button class="toast-close">
                            <i class="fas fa-times"></i>
                        </button>
                    `;

                    // Add toast styles if not exists
                    if (!document.querySelector('#toast-styles')) {
                        const style = document.createElement('style');
                        style.id = 'toast-styles';
                        style.textContent = `
                            .toast {
                                position: fixed;
                                top: 100px;
                                right: 20px;
                                background: var(--bg-primary);
                                border: 1px solid var(--border-light);
                                border-radius: var(--radius-lg);
                                box-shadow: var(--shadow-xl);
                                padding: var(--spacing-md) var(--spacing-lg);
                                display: flex;
                                align-items: center;
                                gap: var(--spacing-md);
                                z-index: 9999;
                                min-width: 300px;
                                max-width: 400px;
                                animation: slideInRight 0.3s ease-out;
                            }

                            .toast-success { border-left: 4px solid var(--success-color); }
                            .toast-error { border-left: 4px solid var(--danger-color); }
                            .toast-info { border-left: 4px solid var(--info-color); }

                            .toast-icon {
                                color: var(--success-color);
                                font-size: var(--text-lg);
                                flex-shrink: 0;
                            }

                            .toast-error .toast-icon { color: var(--danger-color); }
                            .toast-info .toast-icon { color: var(--info-color); }

                            .toast-content { flex: 1; }

                            .toast-message {
                                color: var(--text-primary);
                                font-size: var(--text-sm);
                                font-weight: 500;
                                line-height: 1.4;
                            }

                            .toast-close {
                                background: none;
                                border: none;
                                color: var(--text-muted);
                                cursor: pointer;
                                padding: var(--spacing-xs);
                                border-radius: var(--radius-md);
                                transition: all var(--transition-fast);
                            }

                            .toast-close:hover {
                                color: var(--text-primary);
                                background: var(--bg-tertiary);
                            }

                            @keyframes slideInRight {
                                from {
                                    transform: translateX(100%);
                                    opacity: 0;
                                }
                                to {
                                    transform: translateX(0);
                                    opacity: 1;
                                }
                            }

                            @keyframes fadeOut {
                                from {
                                    transform: translateX(0);
                                    opacity: 1;
                                }
                                to {
                                    transform: translateX(100%);
                                    opacity: 0;
                                }
                            }

                            @keyframes slideOutDown {
                                from {
                                    transform: translateX(-50%) translateY(0);
                                    opacity: 1;
                                }
                                to {
                                    transform: translateX(-50%) translateY(100%);
                                    opacity: 0;
                                }
                            }

                            @keyframes slideInUp {
                                from {
                                    transform: translateX(-50%) translateY(100%);
                                    opacity: 0;
                                }
                                to {
                                    transform: translateX(-50%) translateY(0);
                                    opacity: 1;
                                }
                            }
                        `;
                        document.head.appendChild(style);
                    }

                    document.body.appendChild(toast);

                    // Auto remove after 4 seconds
                    setTimeout(() => {
                        toast.style.animation = 'fadeOut 0.3s ease-in forwards';
                        setTimeout(() => {
                            if (toast.parentNode) {
                                toast.parentNode.removeChild(toast);
                            }
                        }, 300);
                    }, 4000);

                    // Manual close
                    toast.querySelector('.toast-close').addEventListener('click', () => {
                        toast.style.animation = 'fadeOut 0.3s ease-in forwards';
                        setTimeout(() => {
                            if (toast.parentNode) {
                                toast.parentNode.removeChild(toast);
                            }
                        }, 300);
                    });
                }

                // Mobile-optimized toast function
                function showMobileToast(message, type = 'info') {
                    const toast = document.createElement('div');
                    toast.className = `toast toast-${type} mobile-toast`;
                    toast.innerHTML = `
                        <div class="toast-icon">
                            <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
                        </div>
                        <div class="toast-content">
                            <div class="toast-message">${message}</div>
                        </div>
                        <button class="toast-close">
                            <i class="fas fa-times"></i>
                        </button>
                    `;

                    // Mobile-specific styles
                    toast.style.cssText = `
                        position: fixed;
                        bottom: 20px;
                        left: 50%;
                        transform: translateX(-50%);
                        background: var(--bg-primary);
                        border: 1px solid var(--border-light);
                        border-radius: var(--radius-lg);
                        box-shadow: var(--shadow-xl);
                        padding: var(--spacing-md);
                        display: flex;
                        align-items: center;
                        gap: var(--spacing-sm);
                        z-index: 9999;
                        min-width: 280px;
                        max-width: calc(100vw - 40px);
                        font-size: 0.9rem;
                        animation: slideInUp 0.3s ease-out;
                    `;

                    document.body.appendChild(toast);

                    // Auto remove after 3 seconds on mobile
                    setTimeout(() => {
                        toast.style.animation = 'slideOutDown 0.3s ease-in forwards';
                        setTimeout(() => {
                            if (toast.parentNode) {
                                toast.parentNode.removeChild(toast);
                            }
                        }, 300);
                    }, 3000);

                    // Manual close
                    toast.querySelector('.toast-close').addEventListener('click', () => {
                        toast.style.animation = 'slideOutDown 0.3s ease-in forwards';
                        setTimeout(() => {
                            if (toast.parentNode) {
                                toast.parentNode.removeChild(toast);
                            }
                        }, 300);
                    });
                }

                // Mobile device detection
                function isMobileDevice() {
                    return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent) || window.innerWidth <= 768;
                }

                // Preserve sidebar scroll position
                function preserveSidebarScroll() {
                    const sidebar = document.querySelector('.admin-sidebar');
                    if (!sidebar) return;

                    // Save scroll position before page unload
                    window.addEventListener('beforeunload', function() {
                        sessionStorage.setItem('sidebarScrollPosition', sidebar.scrollTop);
                    });

                    // Save scroll position on navigation clicks
                    const navLinks = document.querySelectorAll('.admin-sidebar .nav-link');
                    navLinks.forEach(link => {
                        link.addEventListener('click', function() {
                            sessionStorage.setItem('sidebarScrollPosition', sidebar.scrollTop);
                        });
                    });

                    // Restore scroll position after page load
                    function restoreScrollPosition() {
                        const savedScrollPosition = sessionStorage.getItem('sidebarScrollPosition');
                        if (savedScrollPosition !== null) {
                            // Use requestAnimationFrame for smooth restoration
                            requestAnimationFrame(() => {
                                sidebar.scrollTop = parseInt(savedScrollPosition);
                            });
                        }
                    }

                    // Restore on multiple events for better compatibility
                    window.addEventListener('load', restoreScrollPosition);
                    document.addEventListener('DOMContentLoaded', restoreScrollPosition);
                    
                    // Also restore after a short delay to ensure DOM is fully ready
                    setTimeout(restoreScrollPosition, 100);
                }

                // Initialize sidebar scroll preservation
                preserveSidebarScroll();

                // Notification functionality
                function initializeNotifications() {
                    // Mark notification as read
                    document.addEventListener('click', function(e) {
                        if (e.target.closest('.btn-mark-read')) {
                            e.preventDefault();
                            e.stopPropagation();
                            
                            const button = e.target.closest('.btn-mark-read');
                            const notificationId = button.dataset.notificationId;
                            const notificationItem = button.closest('.notification-item');
                            
                            if (notificationId) {
                                markNotificationAsRead(notificationId, notificationItem);
                            }
                        }
                    });

                    // Mark all notifications as read
                    const markAllReadBtn = document.querySelector('.mark-all-read');
                    if (markAllReadBtn) {
                        markAllReadBtn.addEventListener('click', function(e) {
                            e.preventDefault();
                            e.stopPropagation();
                            markAllNotificationsAsRead();
                        });
                    }

                    // Auto-refresh notifications every 30 seconds
                    setInterval(refreshNotifications, 30000);
                }

                function markNotificationAsRead(notificationId, notificationItem) {
                    fetch('<?php echo admin_url('notifications'); ?>', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: 'mark_read=1&notification_id=' + notificationId
                    })
                    .then(response => response.text())
                    .then(data => {
                        if (notificationItem) {
                            notificationItem.classList.remove('unread');
                            const actions = notificationItem.querySelector('.notification-actions');
                            if (actions) {
                                actions.remove();
                            }
                        }
                        updateNotificationBadge();
                        showToast('Notification marked as read', 'success');
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showToast('Error marking notification as read', 'error');
                    });
                }

                function markAllNotificationsAsRead() {
                    fetch('<?php echo admin_url('notifications'); ?>', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: 'mark_all_read=1'
                    })
                    .then(response => response.text())
                    .then(data => {
                        // Remove unread class from all notifications
                        document.querySelectorAll('.notification-item.unread').forEach(item => {
                            item.classList.remove('unread');
                            const actions = item.querySelector('.notification-actions');
                            if (actions) {
                                actions.remove();
                            }
                        });
                        updateNotificationBadge();
                        showToast('All notifications marked as read', 'success');
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showToast('Error marking notifications as read', 'error');
                    });
                }

                function refreshNotifications() {
                    // Only refresh if notifications dropdown is not open
                    const dropdown = document.querySelector('.notification-dropdown-container .dropdown-menu');
                    if (dropdown && !dropdown.classList.contains('show')) {
                        // Reload the page to get fresh notifications
                        // In a real implementation, you'd use AJAX to fetch new notifications
                        // For now, we'll just update the badge count
                        updateNotificationBadge();
                    }
                }

                function updateNotificationBadge() {
                    fetch('<?php echo admin_url('notifications'); ?>?ajax=1')
                    .then(response => response.json())
                    .then(data => {
                        const badge = document.querySelector('.notification-badge');
                        const dot = document.querySelector('.notification-dot');
                        
                        if (data.unread_count > 0) {
                            if (badge) {
                                badge.textContent = data.unread_count;
                                badge.style.display = 'inline-block';
                            }
                            if (dot) {
                                dot.style.display = 'block';
                            }
                        } else {
                            if (badge) {
                                badge.style.display = 'none';
                            }
                            if (dot) {
                                dot.style.display = 'none';
                            }
                        }
                    })
                    .catch(error => {
                        console.error('Error updating notification badge:', error);
                    });
                }

                // Initialize notifications
                initializeNotifications();
            </script>

            <!-- Content Area -->
            <div class="admin-content">