<?php 
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set default page meta
$pageTitle = $pageTitle ?? 'Home';
$pageDescription = $pageDescription ?? 'Air Conditioning Sales & Service - Quality AC solutions for your comfort';
$pageKeywords = $pageKeywords ?? 'air conditioning, AC, cooling, installation, maintenance, AMC';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <meta name="description" content="<?= htmlspecialchars($pageDescription); ?>">
    <meta name="keywords" content="<?= htmlspecialchars($pageKeywords); ?>">
    
    <title><?= htmlspecialchars($pageTitle); ?> - Akash Enterprise | AC Sales & Service</title>
    
    <!-- Preconnect for performance -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://cdnjs.cloudflare.com">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Stylesheets -->
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/bootstrap.min.css?v=<?php echo APP_VERSION; ?>"> 
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/style.css?v=<?php echo APP_VERSION; ?>">
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/loader-scroller.css?v=<?php echo APP_VERSION; ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" sizes="32x32" href="<?php echo IMG_URL; ?>/favicon-32x32.png">
    
    <style>
        /* Modern Responsive Header Styles */
        :root {
            --header-height: 100px;
            --primary-blue: #3b82f6;
            --bg-dark: #0f172a;
            --text-primary: #f8fafc;
            --border-light: rgba(248, 250, 252, 0.1);
        }

        /* Reset default styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            padding-top: var(--header-height);
        }

        /* Header Container */
        .modern-header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            background: rgba(15, 23, 42, 0.95);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid var(--border-light);
            z-index: 1000;
            transition: all 0.3s ease;
        }

        .modern-header.scrolled {
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
        }

        /* Header Content */
        .header-content {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem;
            min-height: var(--header-height);
            max-width: 1400px;
            margin: 0 auto;
            gap: 1rem;
            position: relative;
            overflow: visible;
        }

        /* Logo */
        .header-logo {
            display: flex;
            align-items: center;
            text-decoration: none;
            flex-shrink: 0;
            margin-right: auto;
            min-width: 180px;
        }

        .header-logo img {
            height: 90px;
            width: auto;
            max-width: 350px;
            object-fit: contain;
            display: block;
            transition: transform 0.3s ease;
        }

        .header-logo:hover img {
            transform: scale(1.05);
        }

        /* Desktop Navigation */
        .desktop-nav {
            display: none;
            align-items: center;
            gap: 0.5rem;
            flex: 1;
            justify-content: center;
            position: relative;
            overflow: visible;
        }

        .nav-link {
            color: #e2e8f0;
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-weight: 500;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            white-space: nowrap;
        }

        .nav-link:hover,
        .nav-link.active {
            color: var(--primary-blue);
            background: rgba(59, 130, 246, 0.1);
        }

        /* Dropdown */
        .nav-dropdown {
            position: relative;
        }

        .dropdown-toggle {
            display: flex;
            align-items: center;
            gap: 0.25rem;
            cursor: pointer;
        }

        .dropdown-menu {
            position: absolute;
            top: 100%;
            left: 0;
            background: rgba(15, 23, 42, 0.98);
            backdrop-filter: blur(20px);
            border: 1px solid var(--border-light);
            border-radius: 12px;
            padding: 0.5rem;
            min-width: 200px;
            opacity: 0;
            visibility: hidden;
            display: block;
            transform: translateY(-10px);
            transition: all 0.3s ease;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            margin-top: 0.5rem;
            z-index: 1001;
            pointer-events: none;
        }

        .nav-dropdown:hover .dropdown-menu,
        .nav-dropdown.open .dropdown-menu {
            opacity: 1 !important;
            visibility: visible !important;
            display: block !important;
            transform: translateY(0) !important;
            pointer-events: auto !important;
        }

        /* Ensure dropdown menu is visible when open class is present */
        .nav-dropdown.open > .dropdown-menu {
            opacity: 1 !important;
            visibility: visible !important;
            display: block !important;
            transform: translateY(0) !important;
            pointer-events: auto !important;
        }

        /* Bridge gap between toggle and menu for smooth hover */
        .nav-dropdown::before {
            content: '';
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            height: 0.5rem;
        }

        .dropdown-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1rem;
            color: #e2e8f0;
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .dropdown-item:hover {
            background: rgba(59, 130, 246, 0.1);
            color: var(--primary-blue);
        }

        /* Header Actions */
        .header-actions {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        /* Search Button */
        .search-toggle {
            background: rgba(30, 41, 59, 0.8);
            border: 1px solid var(--border-light);
            color: #e2e8f0;
            padding: 0.5rem 1rem;
            border-radius: 25px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
        }

        .search-toggle:hover {
            background: rgba(59, 130, 246, 0.2);
            border-color: var(--primary-blue);
        }

        /* User Menu Button */
        .user-menu-btn {
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            border: none;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 25px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 600;
            transition: all 0.3s ease;
            white-space: nowrap;
        }

        .user-menu-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
        }

        /* Auth Buttons */
        .auth-buttons {
            display: flex;
            gap: 0.5rem;
        }

        .btn-login,
        .btn-register {
            padding: 0.5rem 1rem;
            border-radius: 25px;
            border: none;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-login {
            background: rgba(30, 41, 59, 0.8);
            color: #e2e8f0;
            border: 1px solid var(--border-light);
        }

        .btn-login:hover {
            background: rgba(30, 41, 59, 1);
            color: white;
        }

        .btn-register {
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            color: white;
        }

        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
        }

        /* Mobile Menu Toggle */
        .mobile-menu-toggle {
            display: flex;
            flex-direction: column;
            gap: 5px;
            background: none;
            border: none;
            cursor: pointer;
            padding: 0.5rem;
        }

        .mobile-menu-toggle span {
            width: 25px;
            height: 3px;
            background: #e2e8f0;
            border-radius: 3px;
            transition: all 0.3s ease;
        }

        .mobile-menu-toggle.active span:nth-child(1) {
            transform: rotate(45deg) translate(8px, 8px);
        }

        .mobile-menu-toggle.active span:nth-child(2) {
            opacity: 0;
        }

        .mobile-menu-toggle.active span:nth-child(3) {
            transform: rotate(-45deg) translate(7px, -7px);
        }

        /* Mobile Navigation */
        .mobile-nav {
            position: fixed;
            top: var(--header-height);
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(15, 23, 42, 0.98);
            backdrop-filter: blur(20px);
            padding: 2rem 1rem;
            transform: translateX(100%);
            transition: transform 0.3s ease;
            overflow-y: auto;
            z-index: 999;
        }

        .mobile-nav.active {
            transform: translateX(0);
        }

        .mobile-nav-list {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .mobile-nav-item .nav-link {
            display: block;
            padding: 1rem;
            font-size: 1.1rem;
        }

        .mobile-nav-item.has-dropdown > .nav-link::after {
            content: '▼';
            float: right;
            font-size: 0.8rem;
            transition: transform 0.3s ease;
        }

        .mobile-nav-item.has-dropdown.open > .nav-link::after {
            transform: rotate(180deg);
        }

        .mobile-dropdown {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease;
            padding-left: 1rem;
        }

        .mobile-dropdown.open {
            max-height: 500px;
        }

        .mobile-dropdown .dropdown-item {
            padding: 0.75rem 1rem;
            display: block;
        }

        /* Search Overlay */
        .search-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(15, 23, 42, 0.98);
            backdrop-filter: blur(20px);
            z-index: 1001;
            display: flex;
            align-items: flex-start;
            justify-content: center;
            padding-top: 10vh;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }

        .search-overlay.active {
            opacity: 1;
            visibility: visible;
        }

        .search-container {
            width: 90%;
            max-width: 600px;
        }

        .search-box {
            position: relative;
            margin-bottom: 2rem;
        }

        .search-input {
            width: 100%;
            padding: 1.5rem 4rem 1.5rem 2rem;
            background: rgba(30, 41, 59, 0.8);
            border: 2px solid var(--border-light);
            border-radius: 50px;
            color: white;
            font-size: 1.2rem;
            outline: none;
            transition: all 0.3s ease;
        }

        .search-input:focus {
            border-color: var(--primary-blue);
            background: rgba(30, 41, 59, 0.9);
        }

        .search-submit {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            border: none;
            color: white;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }

        .search-submit:hover {
            transform: translateY(-50%) scale(1.1);
        }

        .search-close {
            position: absolute;
            top: 2rem;
            right: 2rem;
            background: rgba(239, 68, 68, 0.2);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #ef4444;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            transition: all 0.3s ease;
        }

        .search-close:hover {
            background: rgba(239, 68, 68, 0.3);
            transform: scale(1.1);
        }

        /* User Dropdown */
        .user-dropdown {
            position: absolute;
            top: 100%;
            right: 0;
            background: rgba(15, 23, 42, 0.98);
            backdrop-filter: blur(20px);
            border: 1px solid var(--border-light);
            border-radius: 12px;
            padding: 0.5rem;
            min-width: 250px;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.3s ease;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            margin-top: 0.5rem;
        }

        .user-menu:hover .user-dropdown {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .user-dropdown-header {
            padding: 1rem;
            border-bottom: 1px solid var(--border-light);
            margin-bottom: 0.5rem;
        }

        .user-dropdown-header h6 {
            margin: 0;
            color: white;
            font-weight: 600;
        }

        .badge {
            background: #ef4444;
            color: white;
            padding: 0.25rem 0.5rem;
            border-radius: 12px;
            font-size: 0.75rem;
            margin-left: 0.5rem;
        }

        .user-dropdown .dropdown-item.logout {
            color: #ef4444;
            border-top: 1px solid var(--border-light);
            margin-top: 0.5rem;
            padding-top: 1rem;
        }

        /* Responsive Breakpoints */
        @media (min-width: 992px) {
            .desktop-nav {
                display: flex;
            }

            .mobile-menu-toggle {
                display: none;
            }

            .mobile-nav {
                display: none;
            }

            .search-toggle span {
                display: inline;
            }
        }

        @media (max-width: 991px) {
            :root {
                --header-height: 90px;
            }
            
            .header-content {
                padding: 0.75rem 1rem;
                min-height: var(--header-height);
            }

            .header-logo img {
                height: 70px;
            }

            .search-toggle span {
                display: none;
            }

            .user-menu-btn span {
                display: none;
            }

            .btn-login span,
            .btn-register span {
                display: none;
            }

            .btn-login,
            .btn-register {
                padding: 0.5rem;
                width: 40px;
                height: 40px;
                justify-content: center;
            }

            .user-menu-btn {
                padding: 0.5rem;
                width: 40px;
                height: 40px;
                justify-content: center;
            }
        }

        @media (max-width: 576px) {
            :root {
                --header-height: 80px;
            }
            
            .header-content {
                gap: 0.5rem;
                padding: 0.75rem 1rem;
                min-height: var(--header-height);
            }

            .header-logo img {
                height: 60px;
            }

            .search-input {
                font-size: 1rem;
                padding: 1.25rem 3.5rem 1.25rem 1.5rem;
            }

            .search-submit {
                width: 45px;
                height: 45px;
            }
        }

        /* Breadcrumbs */
        .breadcrumb-section {
            position: sticky;
            top: var(--header-height);
            background: #ffffff;
            border-bottom: 1px solid #e2e8f0;
            border-bottom-width: 0px;
            padding: 0.875rem 0;
            padding-bottom: 0px;
            margin: 0;
            width: 100%;
            z-index: 999;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }

        .breadcrumb-section .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 1rem;
        }

        .breadcrumb {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            flex-wrap: wrap;
            margin: 0;
            margin-top: 15px;
            padding: 0;
            list-style: none;
            width: 100%;
        }

        .breadcrumb-item {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: #64748b;
            font-size: 0.875rem;
            line-height: 1.5;
        }

        .breadcrumb-item a {
            color: #64748b;
            text-decoration: none;
            transition: all 0.2s ease;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
        }

        .breadcrumb-item a:hover {
            color: var(--primary-blue);
            background: rgba(59, 130, 246, 0.1);
        }

        .breadcrumb-item.active {
            color: #1e293b;
            font-weight: 600;
        }

        .breadcrumb-item::after {
            content: '›';
            margin-left: 0.5rem;
            color: #94a3b8;
            font-weight: normal;
            font-size: 0.875rem;
        }

        .breadcrumb-item:last-child::after {
            display: none;
        }

        /* Responsive Breadcrumb */
        @media (max-width: 768px) {
            .breadcrumb-section {
                padding: 0.75rem 0;
            }
            
            .breadcrumb-item {
                font-size: 0.8125rem;
            }
        }

        /* Loading State */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.7);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 9999;
        }

        .loading-overlay.active {
            display: flex;
        }

        .spinner {
            width: 50px;
            height: 50px;
            border: 4px solid rgba(59, 130, 246, 0.2);
            border-top-color: var(--primary-blue);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <!-- Modern Header -->
    <header class="modern-header">
        <div class="header-content">
            <!-- Logo -->
            <a href="<?php echo BASE_URL; ?>/index.php" class="header-logo" title="Akash Enterprise">
                <img src="<?php echo IMG_URL; ?>/full-logo.png" alt="Akash Enterprise" style="display: block;">
            </a>

            <!-- Desktop Navigation -->
            <nav class="desktop-nav">
                <a href="<?php echo BASE_URL; ?>/index.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
                    Home
                </a>
                <a href="<?php echo PUBLIC_URL; ?>/pages/about.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'about.php' ? 'active' : ''; ?>">
                    About
                </a>
                <div class="nav-dropdown">
                    <div class="nav-link dropdown-toggle <?= strpos($_SERVER['REQUEST_URI'], '/products/') !== false ? 'active' : ''; ?>" role="button" aria-haspopup="true" aria-expanded="false">
                        Products 
                    </div>
                    <div class="dropdown-menu">
                        <a href="<?php echo USER_URL; ?>/products/" class="dropdown-item">
                            <i class="fas fa-th-large"></i> All Products
                        </a>
                        <a href="<?php echo USER_URL; ?>/products/?category=1" class="dropdown-item">
                            <i class="fas fa-home"></i> Residential AC
                        </a>
                        <a href="<?php echo USER_URL; ?>/products/?category=2" class="dropdown-item">
                            <i class="fas fa-building"></i> Commercial AC
                        </a>
                        <a href="<?php echo USER_URL; ?>/products/?category=3" class="dropdown-item">
                            <i class="fas fa-wind"></i> Cassette AC
                        </a>
                    </div>
                </div>
                <a href="<?php echo USER_URL; ?>/services/" class="nav-link <?= strpos($_SERVER['REQUEST_URI'], '/services/') !== false ? 'active' : ''; ?>">
                    Services
                </a>
                <a href="<?php echo PUBLIC_URL; ?>/pages/contact.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'contact.php' ? 'active' : ''; ?>">
                    Contact
                </a>
            </nav>

            <!-- Header Actions -->
            <div class="header-actions">
                <!-- Search Toggle -->
                <button class="search-toggle" onclick="toggleSearch()">
                    <i class="fas fa-search"></i>
                    <span>Search</span>
                </button>

                <!-- User Menu or Auth Buttons -->
                <?php if (isset($_SESSION['user_id'])): ?>
                    <div class="user-menu" style="position: relative;">
                        <button class="user-menu-btn">
                            <i class="fas fa-user-circle"></i>
                            <span><?= htmlspecialchars($_SESSION['username']); ?></span>
                        </button>
                        <div class="user-dropdown">
                            <div class="user-dropdown-header">
                                <h6>My Account</h6>
                            </div>
                            <a href="<?php echo USER_URL; ?>/profile/" class="dropdown-item">
                                <i class="fas fa-user"></i> Profile
                            </a>
                            <a href="<?php echo USER_URL; ?>/orders/history.php" class="dropdown-item">
                                <i class="fas fa-history"></i> Orders
                            </a>
                            <a href="<?php echo USER_URL; ?>/cart/" class="dropdown-item">
                                <i class="fas fa-shopping-cart"></i> Cart
                                <?php
                                if (isset($_SESSION['user_id']) && isset($pdo)) {
                                    try {
                                        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM cart WHERE user_id = ?");
                                        $stmt->execute([$_SESSION['user_id']]);
                                        $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
                                        if ($count > 0) echo '<span class="badge">'.$count.'</span>';
                                    } catch (Exception $e) {}
                                }
                                ?>
                            </a>
                            <a href="<?php echo USER_URL; ?>/wishlist/" class="dropdown-item">
                                <i class="fas fa-heart"></i> Wishlist
                            </a>
                            <a href="<?php echo USER_URL; ?>/auth/logout.php" class="dropdown-item logout">
                                <i class="fas fa-sign-out-alt"></i> Logout
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="auth-buttons">
                        <a href="<?php echo USER_URL; ?>/auth/login.php" class="btn-login">
                            <i class="fas fa-sign-in-alt"></i>
                            <span>Login</span>
                        </a>
                        <a href="<?php echo USER_URL; ?>/auth/register.php" class="btn-register">
                            <i class="fas fa-user-plus"></i>
                            <span>Register</span>
                        </a>
                    </div>
                <?php endif; ?>

                <!-- Mobile Menu Toggle -->
                <button class="mobile-menu-toggle" onclick="toggleMobileMenu()">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
            </div>
        </div>
    </header>

    <!-- Mobile Navigation -->
    <nav class="mobile-nav">
        <ul class="mobile-nav-list">
            <li class="mobile-nav-item">
                <a href="<?php echo BASE_URL; ?>/index.php" class="nav-link">
                    <i class="fas fa-home"></i> Home
                </a>
            </li>
            <li class="mobile-nav-item">
                <a href="<?php echo PUBLIC_URL; ?>/pages/about.php" class="nav-link">
                    <i class="fas fa-info-circle"></i> About
                </a>
            </li>
            <li class="mobile-nav-item has-dropdown">
                <a href="#" class="nav-link" onclick="toggleMobileDropdown(event)">
                    <i class="fas fa-boxes"></i> Products
                </a>
                <div class="mobile-dropdown">
                    <a href="<?php echo USER_URL; ?>/products/" class="dropdown-item">
                        <i class="fas fa-th-large"></i> All Products
                    </a>
                    <a href="<?php echo USER_URL; ?>/products/?category=1" class="dropdown-item">
                        <i class="fas fa-home"></i> Residential AC
                    </a>
                    <a href="<?php echo USER_URL; ?>/products/?category=2" class="dropdown-item">
                        <i class="fas fa-building"></i> Commercial AC
                    </a>
                    <a href="<?php echo USER_URL; ?>/products/?category=3" class="dropdown-item">
                        <i class="fas fa-wind"></i> Cassette AC
                    </a>
                </div>
            </li>
            <li class="mobile-nav-item">
                <a href="<?php echo USER_URL; ?>/services/" class="nav-link">
                    <i class="fas fa-tools"></i> Services
                </a>
            </li>
            <li class="mobile-nav-item">
                <a href="<?php echo PUBLIC_URL; ?>/pages/contact.php" class="nav-link">
                    <i class="fas fa-phone"></i> Contact
                </a>
            </li>
        </ul>
    </nav>

    <!-- Search Overlay -->
    <div class="search-overlay">
        <button class="search-close" onclick="toggleSearch()">
            <i class="fas fa-times"></i>
        </button>
        <div class="search-container">
            <form action="<?php echo USER_URL; ?>/search.php" method="GET" class="search-box">
                <input type="text" 
                       name="query" 
                       class="search-input" 
                       placeholder="Search for products, brands, categories..." 
                       required
                       autofocus>
                <button type="submit" class="search-submit">
                    <i class="fas fa-search"></i>
                </button>
            </form>
        </div>
    </div>

    <!-- Breadcrumbs -->
    <div class="breadcrumb-section">
        <div class="container">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb" itemscope itemtype="https://schema.org/BreadcrumbList">
                    <li class="breadcrumb-item" itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
                        <a href="<?php echo BASE_URL; ?>/index.php" itemprop="item">
                            <span itemprop="name">Home</span>
                        </a>
                        <meta itemprop="position" content="1" />
                    </li>
                    <?php
                    // Enhanced breadcrumb logic
                    $current_page = basename($_SERVER['PHP_SELF']);
                    $request_uri = $_SERVER['REQUEST_URI'];
                    $show_breadcrumb = false;
                    $page_name = '';
                    
                    // Check URL path for products and services (even if filename is index.php)
                    if (strpos($request_uri, '/products/') !== false) {
                        $show_breadcrumb = true;
                        $page_name = 'Products';
                    } elseif (strpos($request_uri, '/services/') !== false) {
                        $show_breadcrumb = true;
                        $page_name = 'Services';
                    } elseif ($current_page != 'index.php') {
                        $show_breadcrumb = true;
                        // Map specific pages to their proper names
                        $page_names = [
                            'contact.php' => 'Contact Us',
                            'about.php' => 'About Us',
                            'products.php' => 'Products',
                            'services.php' => 'Services',
                            'privacy.php' => 'Privacy Policy',
                            'terms.php' => 'Terms & Conditions',
                        ];
                        
                        // Get page name from map or generate from filename
                        if (isset($page_names[$current_page])) {
                            $page_name = $page_names[$current_page];
                        } else {
                            $page_name = ucfirst(str_replace(['.php', '-', '_'], ['', ' ', ' '], $current_page));
                        }
                    }
                    
                    if ($show_breadcrumb && !empty($page_name)) {
                        echo '<li class="breadcrumb-item active" itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem" aria-current="page">';
                        echo '<span itemprop="name">' . htmlspecialchars($page_name) . '</span>';
                        echo '<meta itemprop="position" content="2" />';
                        echo '</li>';
                    }
                    ?>
                </ol>
            </nav>
        </div>
    </div>

    <!-- Loading Overlay -->
    <div class="loading-overlay">
        <div class="spinner"></div>
    </div>

    <!-- Main Content -->
    <main id="main-content">
        
    <script>
        // Header scroll effect
        window.addEventListener('scroll', function() {
            const header = document.querySelector('.modern-header');
            if (window.scrollY > 50) {
                header.classList.add('scrolled');
            } else {
                header.classList.remove('scrolled');
            }
        });

        // Toggle mobile menu
        function toggleMobileMenu() {
            const toggle = document.querySelector('.mobile-menu-toggle');
            const nav = document.querySelector('.mobile-nav');
            toggle.classList.toggle('active');
            nav.classList.toggle('active');
            document.body.style.overflow = nav.classList.contains('active') ? 'hidden' : '';
        }

        // Toggle mobile dropdown
        function toggleMobileDropdown(e) {
            e.preventDefault();
            const item = e.target.closest('.mobile-nav-item');
            const dropdown = item.querySelector('.mobile-dropdown');
            
            item.classList.toggle('open');
            dropdown.classList.toggle('open');
        }

        // Toggle desktop dropdown (click support)
        function toggleDesktopDropdown(e) {
            if (e) {
                e.preventDefault();
                e.stopPropagation();
            }
            
            const dropdown = e ? e.target.closest('.nav-dropdown') : null;
            if (!dropdown) {
                console.warn('toggleDesktopDropdown: No dropdown found');
                return;
            }
            
            const toggle = dropdown.querySelector('.dropdown-toggle');
            const isOpen = dropdown.classList.contains('open');
            
            // Close other dropdowns
            document.querySelectorAll('.nav-dropdown').forEach(item => {
                if (item !== dropdown) {
                    item.classList.remove('open');
                    const otherToggle = item.querySelector('.dropdown-toggle');
                    if (otherToggle) {
                        otherToggle.setAttribute('aria-expanded', 'false');
                    }
                }
            });
            
            // Toggle current dropdown
            if (isOpen) {
                dropdown.classList.remove('open');
                if (toggle) toggle.setAttribute('aria-expanded', 'false');
            } else {
                dropdown.classList.add('open');
                if (toggle) toggle.setAttribute('aria-expanded', 'true');
            }
            
            // Debug: Check if dropdown menu is visible
            const menu = dropdown.querySelector('.dropdown-menu');
            if (menu) {
                console.log('Dropdown menu state:', {
                    hasOpenClass: dropdown.classList.contains('open'),
                    computedDisplay: window.getComputedStyle(menu).display,
                    computedVisibility: window.getComputedStyle(menu).visibility,
                    computedOpacity: window.getComputedStyle(menu).opacity,
                    computedTransform: window.getComputedStyle(menu).transform,
                    zIndex: window.getComputedStyle(menu).zIndex
                });
            }
        }

        // Add event listeners as backup (remove onclick to avoid double-firing)
        document.addEventListener('DOMContentLoaded', function() {
            const dropdownToggles = document.querySelectorAll('.nav-dropdown .dropdown-toggle');
            dropdownToggles.forEach(toggle => {
                // Remove onclick attribute to use addEventListener instead
                toggle.removeAttribute('onclick');
                toggle.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    const dropdown = this.closest('.nav-dropdown');
                    if (dropdown) {
                        toggleDesktopDropdown(e);
                    }
                });
            });
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            // Don't close if clicking anywhere inside the dropdown (toggle or menu)
            const clickedDropdown = e.target.closest('.nav-dropdown');
            if (clickedDropdown) {
                return; // Click is inside a dropdown, don't close
            }
            
            // Close all dropdowns when clicking outside
            document.querySelectorAll('.nav-dropdown').forEach(item => {
                item.classList.remove('open');
            });
        });

        // Toggle search overlay
        function toggleSearch() {
            const overlay = document.querySelector('.search-overlay');
            overlay.classList.toggle('active');
            
            if (overlay.classList.contains('active')) {
                document.body.style.overflow = 'hidden';
                setTimeout(() => {
                    overlay.querySelector('.search-input').focus();
                }, 300);
            } else {
                document.body.style.overflow = '';
            }
        }

        // Close search on escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                const searchOverlay = document.querySelector('.search-overlay');
                const mobileNav = document.querySelector('.mobile-nav');
                
                if (searchOverlay.classList.contains('active')) {
                    toggleSearch();
                }
                
                if (mobileNav.classList.contains('active')) {
                    toggleMobileMenu();
                }
            }
        });

        // Close mobile menu when clicking outside
        document.addEventListener('click', function(e) {
            const mobileNav = document.querySelector('.mobile-nav');
            const toggle = document.querySelector('.mobile-menu-toggle');
            
            if (mobileNav.classList.contains('active') && 
                !mobileNav.contains(e.target) && 
                !toggle.contains(e.target)) {
                toggleMobileMenu();
            }
        });

        // Show/hide loading overlay
        function showLoading() {
            document.querySelector('.loading-overlay').classList.add('active');
        }

        function hideLoading() {
            document.querySelector('.loading-overlay').classList.remove('active');
        }

        // Auto-hide loading on page load
        window.addEventListener('load', function() {
            hideLoading();
        });

        // Show loading on form submit
        document.addEventListener('submit', function() {
            showLoading();
        });
    </script>