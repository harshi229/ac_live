<?php
// Set page metadata
$pageTitle = 'Search Results';
$pageDescription = 'Search results for products and services';
$pageKeywords = 'search, products, services, AC, air conditioning';

require_once __DIR__ . '/../includes/config/init.php';
include INCLUDES_PATH . '/templates/header.php';

// Check database connection
$debug_info = [];
$debug_info['db_connection'] = isset($pdo) ? 'Connected' : 'Not connected';

// Get the search query - simplified approach
$query = '';
$search_type = 'all';

// Get search query from GET parameters
if (isset($_GET['query']) && !empty(trim($_GET['query']))) {
    $query = trim($_GET['query']);
}

// Get search type
if (isset($_GET['type']) && in_array($_GET['type'], ['all', 'products', 'menu'])) {
    $search_type = $_GET['type'];
}

// Debug information
$debug_info['raw_query'] = $query;
$debug_info['query_length'] = strlen($query);
$debug_info['search_type'] = $search_type;
$debug_info['request_uri'] = $_SERVER['REQUEST_URI'] ?? 'Not set';
$debug_info['query_string'] = $_SERVER['QUERY_STRING'] ?? 'Not set';
$debug_info['request_method'] = $_SERVER['REQUEST_METHOD'] ?? 'Not set';
$debug_info['_GET_params'] = $_GET;
$debug_info['query_received'] = $query;

// Define menu items that can be searched
$menu_items = [
    [
        'title' => 'Home',
        'url' => BASE_URL . '/index.php',
        'description' => 'Main homepage with featured products and services',
        'keywords' => 'home, main page, homepage, featured'
    ],
    [
        'title' => 'About Us',
        'url' => PUBLIC_URL . '/pages/about.php',
        'description' => 'Learn about our company history and mission',
        'keywords' => 'about, company, history, mission, team'
    ],
    [
        'title' => 'All Products',
        'url' => USER_URL . '/products/',
        'description' => 'Browse our complete range of air conditioning products',
        'keywords' => 'products, all products, browse, catalog'
    ],
    [
        'title' => 'Residential AC',
        'url' => USER_URL . '/products/?category=1',
        'description' => 'Home air conditioning units for residential use',
        'keywords' => 'residential, home, house, domestic, split ac'
    ],
    [
        'title' => 'Commercial AC',
        'url' => USER_URL . '/products/?category=2',
        'description' => 'Commercial air conditioning systems for businesses',
        'keywords' => 'commercial, business, office, building, hvac'
    ],
    [
        'title' => 'Cassette AC',
        'url' => USER_URL . '/products/?category=3',
        'description' => 'Cassette air conditioning units for modern spaces',
        'keywords' => 'cassette, ceiling, modern, sleek'
    ],
    [
        'title' => 'Services',
        'url' => USER_URL . '/services/',
        'description' => 'Professional AC installation, maintenance, and repair services',
        'keywords' => 'services, installation, maintenance, repair, support'
    ],
    [
        'title' => 'Contact',
        'url' => PUBLIC_URL . '/pages/contact.php',
        'description' => 'Get in touch with our team for inquiries and support',
        'keywords' => 'contact, support, help, inquiry, phone, email'
    ]
];

// Search products if query is provided
$products = [];

if (!empty($query) && ($search_type === 'all' || $search_type === 'products')) {
    try {
        // Check if database connection is available
        if (!isset($pdo)) {
            throw new Exception('Database connection not available');
        }
        
        // First, let's check if we have any products at all
        $count_sql = "SELECT COUNT(*) as total FROM products WHERE status = 'active' AND (show_on_product_page = 1 OR show_on_product_page IS NULL)";
        $count_stmt = $pdo->prepare($count_sql);
        $count_stmt->execute();
        $total_products = $count_stmt->fetch()['total'];
        $debug_info['total_products'] = $total_products;
        
        // Comprehensive product search across all attributes
        $sql = "SELECT products.*, 
                       categories.name as category_name, 
                       brands.name as brand_name,
                       sub_categories.name as sub_category_name
                FROM products 
                JOIN categories ON products.category_id = categories.id 
                JOIN brands ON products.brand_id = brands.id
                LEFT JOIN sub_categories ON products.sub_category_id = sub_categories.id
                WHERE (
                    products.product_name LIKE ? 
                    OR categories.name LIKE ?
                    OR brands.name LIKE ?
                    OR products.model_name LIKE ?
                    OR products.model_number LIKE ?
                    OR products.inverter LIKE ?
                    OR products.capacity LIKE ?
                    OR products.description LIKE ?
                    OR sub_categories.name LIKE ?
                    OR CONCAT(products.star_rating, ' star') LIKE ?
                    OR CONCAT(products.star_rating, 'star') LIKE ?
                    OR CONCAT('warranty ', products.warranty_years, ' year') LIKE ?
                    OR CONCAT('warranty ', products.warranty_years, ' years') LIKE ?
                    OR CONCAT(products.warranty_years, ' year warranty') LIKE ?
                    OR CONCAT(products.warranty_years, ' years warranty') LIKE ?
                    OR CASE 
                        WHEN products.amc_available = 1 THEN 'amc available'
                        ELSE 'no amc'
                       END LIKE ?
                )
                AND products.status = 'active'
                AND (products.show_on_product_page = 1 OR products.show_on_product_page IS NULL)
                ORDER BY products.product_name ASC";
        
        $stmt = $pdo->prepare($sql);
        $search_param = "%$query%";
        $stmt->execute([
            $search_param, $search_param, $search_param, $search_param, $search_param,
            $search_param, $search_param, $search_param, $search_param, $search_param,
            $search_param, $search_param, $search_param, $search_param, $search_param,
            $search_param
        ]);
        $products = $stmt->fetchAll();
        
        $debug_info['query'] = $query;
        $debug_info['products_found'] = count($products);
        
        // If no products found, let's try a broader search
        if (count($products) === 0) {
            $broad_sql = "SELECT products.*, 
                                 categories.name as category_name, 
                                 brands.name as brand_name,
                                 sub_categories.name as sub_category_name
                          FROM products 
                          JOIN categories ON products.category_id = categories.id 
                          JOIN brands ON products.brand_id = brands.id
                          LEFT JOIN sub_categories ON products.sub_category_id = sub_categories.id
                          WHERE products.status = 'active'
                          AND (products.show_on_product_page = 1 OR products.show_on_product_page IS NULL)
                          ORDER BY products.product_name ASC LIMIT 5";
            $broad_stmt = $pdo->prepare($broad_sql);
            $broad_stmt->execute();
            $all_products = $broad_stmt->fetchAll();
            $debug_info['sample_products'] = array_slice($all_products, 0, 3);
        }
        
    } catch (Exception $e) {
        $products = [];
        $debug_info['error'] = $e->getMessage();
    }
}

// Search menu items
$menu_results = [];
if (!empty($query) && ($search_type === 'all' || $search_type === 'menu')) {
    foreach ($menu_items as $item) {
        $search_text = strtolower($item['title'] . ' ' . $item['description'] . ' ' . $item['keywords']);
        if (strpos($search_text, strtolower($query)) !== false) {
            $menu_results[] = $item;
        }
    }
}

// Calculate total results
$total_results = count($products) + count($menu_results);
?>

<style>
/* ================= ENHANCED SEARCH RESULTS PAGE STYLES ================= */
/* Professional, modern design with advanced CSS features */

:root {
    /* Enhanced Color Palette */
    --primary-blue: #3b82f6;
    --primary-cyan: #06b6d4;
    --primary-gradient: linear-gradient(135deg, #3b82f6 0%, #06b6d4 100%);
    --secondary-gradient: linear-gradient(135deg, #8b5cf6 0%, #ec4899 100%);
    
    /* Background Colors */
    --bg-primary: #ffffff;
    --bg-secondary: #f8fafc;
    --bg-tertiary: #f1f5f9;
    --bg-dark: #1e293b;
    --bg-darker: #0f172a;
    
    /* Text Colors */
    --text-primary: #0f172a;
    --text-secondary: #475569;
    --text-muted: #64748b;
    --text-light: #94a3b8;
    
    /* Border & Shadow */
    --border-light: #e2e8f0;
    --border-medium: #cbd5e1;
    --shadow-xs: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
    --shadow-sm: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
    --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
    --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
    --shadow-2xl: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
    
    /* Transitions */
    --transition-fast: 150ms cubic-bezier(0.4, 0, 0.2, 1);
    --transition-normal: 300ms cubic-bezier(0.4, 0, 0.2, 1);
    --transition-slow: 500ms cubic-bezier(0.4, 0, 0.2, 1);
    
    /* Border Radius */
    --radius-xs: 4px;
    --radius-sm: 6px;
    --radius-md: 8px;
    --radius-lg: 12px;
    --radius-xl: 16px;
    --radius-2xl: 24px;
    --radius-full: 9999px;
    
    /* Spacing */
    --space-1: 0.25rem;
    --space-2: 0.5rem;
    --space-3: 0.75rem;
    --space-4: 1rem;
    --space-5: 1.25rem;
    --space-6: 1.5rem;
    --space-8: 2rem;
    --space-10: 2.5rem;
    --space-12: 3rem;
    --space-16: 4rem;
    --space-20: 5rem;
}

/* ================= MAIN LAYOUT ================= */
.search-results-page {
    background: linear-gradient(135deg, var(--bg-secondary) 0%, var(--bg-tertiary) 100%);
    min-height: 100vh;
    padding: var(--space-20) 0 var(--space-12);
    position: relative;
    overflow-x: hidden;
}

.search-results-page::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: 
        radial-gradient(circle at 20% 80%, rgba(59, 130, 246, 0.03) 0%, transparent 50%),
        radial-gradient(circle at 80% 20%, rgba(6, 182, 212, 0.03) 0%, transparent 50%),
        radial-gradient(circle at 40% 40%, rgba(139, 92, 246, 0.02) 0%, transparent 50%);
    pointer-events: none;
    z-index: 0;
}

.search-results-page > .container {
    position: relative;
    z-index: 1;
}

/* ================= HEADER SECTION ================= */
.search-header {
    background: linear-gradient(135deg, var(--bg-darker) 0%, var(--bg-dark) 100%);
    color: white;
    padding: var(--space-16) 0;
    margin-bottom: var(--space-12);
    position: relative;
    overflow: hidden;
    border-radius: var(--radius-2xl);
    box-shadow: var(--shadow-2xl);
}

.search-header::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: 
        url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="rgba(255,255,255,0.1)"/><circle cx="75" cy="75" r="1" fill="rgba(255,255,255,0.1)"/><circle cx="50" cy="10" r="0.5" fill="rgba(255,255,255,0.05)"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>'),
        linear-gradient(45deg, transparent 30%, rgba(255,255,255,0.02) 50%, transparent 70%);
    opacity: 0.6;
}

.search-header h1 {
    /* font-size: clamp(2rem, 5vw, 3.5rem); */
    font-weight: 500;
    margin-bottom: var(--space-6);
    text-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
    position: relative;
    z-index: 1;
    letter-spacing: -0.025em;
    line-height: 1.1;
}

.search-header h1 i {
    margin-right: var(--space-4);
    color: var(--primary-blue);
    filter: drop-shadow(0 0 10px rgba(59, 130, 246, 0.5));
}

.search-stats {
    color: rgba(255, 255, 255, 0.9);
    font-size: clamp(1rem, 2.5vw, 1.25rem);
    margin-bottom: var(--space-8);
    position: relative;
    z-index: 1;
    font-weight: 500;
    line-height: 1.6;
}

.search-stats strong {
    color: var(--primary-blue);
    font-weight: 700;
    text-shadow: 0 0 10px rgba(59, 130, 246, 0.3);
}

/* ================= SEARCH TABS ================= */
.search-tabs {
    display: flex;
    justify-content: center;
    gap: var(--space-4);
    margin-bottom: var(--space-8);
    flex-wrap: wrap;
    position: relative;
    z-index: 1;
}

.search-tab {
    padding: var(--space-3) var(--space-6);
    background: rgba(255, 255, 255, 0.1);
    border: 2px solid rgba(255, 255, 255, 0.2);
    border-radius: var(--radius-full);
    color: white;
    text-decoration: none;
    transition: all var(--transition-normal);
    font-weight: 600;
    font-size: 0.875rem;
    backdrop-filter: blur(10px);
    position: relative;
    overflow: hidden;
    min-width: 140px;
    text-align: center;
}

.search-tab::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: var(--primary-gradient);
    transition: left var(--transition-normal);
    z-index: -1;
}

.search-tab:hover::before,
.search-tab.active::before {
    left: 0;
}

.search-tab:hover,
.search-tab.active {
    color: white;
    text-decoration: none;
    border-color: var(--primary-blue);
    transform: translateY(-2px) scale(1.02);
    box-shadow: var(--shadow-lg);
}

.search-tab i {
    margin-right: var(--space-2);
    font-size: 0.875rem;
}

/* ================= RESULTS SECTIONS ================= */
.results-section {
    background: var(--bg-primary);
    border-radius: var(--radius-xl);
    padding: var(--space-8);
    margin-bottom: var(--space-8);
    box-shadow: var(--shadow-lg);
    border: 1px solid var(--border-light);
    position: relative;
    overflow: hidden;
}

.results-section::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: var(--primary-gradient);
    border-radius: var(--radius-xl) var(--radius-xl) 0 0;
}

.results-title {
    color: var(--text-primary);
    font-size: clamp(1.5rem, 3vw, 2rem);
    font-weight: 700;
    margin-bottom: var(--space-6);
    display: flex;
    align-items: center;
    gap: var(--space-4);
    position: relative;
    letter-spacing: -0.025em;
}

.results-title i {
    color: var(--primary-blue);
    font-size: 1.5rem;
    filter: drop-shadow(0 2px 4px rgba(59, 130, 246, 0.2));
}

.results-count {
    background: var(--primary-gradient);
    color: white;
    padding: var(--space-2) var(--space-4);
    border-radius: var(--radius-full);
    font-size: 0.75rem;
    font-weight: 700;
    box-shadow: var(--shadow-md);
    letter-spacing: 0.025em;
    text-transform: uppercase;
}

/* ================= MENU ITEMS ================= */
.menu-item {
    background: var(--bg-primary);
    border: 1px solid var(--border-light);
    border-radius: var(--radius-lg);
    padding: var(--space-6);
    margin-bottom: var(--space-5);
    transition: all var(--transition-normal);
    text-decoration: none;
    color: inherit;
    display: block;
    box-shadow: var(--shadow-sm);
    position: relative;
    overflow: hidden;
}

.menu-item::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(59, 130, 246, 0.05), transparent);
    transition: left 0.6s ease;
}

.menu-item:hover::before {
    left: 100%;
}

.menu-item:hover {
    background: var(--bg-secondary);
    border-color: var(--primary-blue);
    transform: translateY(-4px);
    text-decoration: none;
    color: inherit;
    box-shadow: var(--shadow-xl);
}

.menu-item-title {
    color: var(--primary-blue);
    font-size: 1.25rem;
    font-weight: 700;
    margin-bottom: var(--space-3);
    transition: color var(--transition-normal);
    letter-spacing: -0.025em;
}

.menu-item:hover .menu-item-title {
    color: var(--primary-cyan);
}

.menu-item-description {
    color: var(--text-secondary);
    font-size: 0.875rem;
    line-height: 1.6;
    margin: 0;
}

/* ================= PRODUCT CARDS ================= */
.product-card {
    background: var(--bg-primary);
    border: 1px solid var(--border-light);
    border-radius: var(--radius-lg);
    padding: var(--space-6);
    margin-bottom: var(--space-6);
    transition: all var(--transition-normal);
    text-decoration: none;
    color: inherit;
    display: block;
    box-shadow: var(--shadow-sm);
    position: relative;
    overflow: hidden;
}

.product-card-content {
    display: flex;
    align-items: center;
    gap: var(--space-5);
}

.product-image-container {
    position: relative;
    flex-shrink: 0;
}

.image-loading {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    color: var(--text-muted);
    font-size: 1.5rem;
    opacity: 0;
    transition: opacity var(--transition-normal);
    pointer-events: none;
}

.product-image.loading + .image-loading {
    opacity: 1;
}

.product-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(6, 182, 212, 0.05), transparent);
    transition: left 0.6s ease;
}

.product-card:hover::before {
    left: 100%;
}

.product-card:hover {
    background: var(--bg-secondary);
    border-color: var(--primary-cyan);
    transform: translateY(-4px);
    text-decoration: none;
    color: inherit;
    box-shadow: var(--shadow-xl);
}

.product-image {
    width: 120px;
    /* height: 120px; */
    object-fit: cover;
    border-radius: var(--radius-lg);
    border: 2px solid var(--border-light);
    transition: all var(--transition-normal);
    box-shadow: var(--shadow-md);
    background: var(--bg-secondary);
    display: block;
    flex-shrink: 0;
}

.product-card:hover .product-image {
    border-color: var(--primary-cyan);
    transform: scale(1.05);
    box-shadow: var(--shadow-lg);
}

/* Image loading and error states */
.product-image.loading {
    background: linear-gradient(90deg, var(--bg-secondary) 25%, var(--bg-tertiary) 50%, var(--bg-secondary) 75%);
    background-size: 200% 100%;
    animation: shimmer 1.5s infinite;
}

.product-image.error,
.product-image[src=""],
.product-image:not([src]) {
    background: linear-gradient(45deg, var(--bg-secondary) 25%, transparent 25%), 
                linear-gradient(-45deg, var(--bg-secondary) 25%, transparent 25%), 
                linear-gradient(45deg, transparent 75%, var(--bg-secondary) 75%), 
                linear-gradient(-45deg, transparent 75%, var(--bg-secondary) 75%);
    background-size: 20px 20px;
    background-position: 0 0, 0 10px, 10px -10px, -10px 0px;
    opacity: 0.7;
    position: relative;
}

.product-image.error::after,
.product-image[src=""]::after,
.product-image:not([src])::after {
    content: '📷';
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    font-size: 2rem;
    color: var(--text-muted);
    opacity: 0.5;
}

@keyframes shimmer {
    0% {
        background-position: -200% 0;
    }
    100% {
        background-position: 200% 0;
    }
}

.product-info {
    flex: 1;
    padding-left: var(--space-6);
}

.product-name {
    color: var(--text-primary);
    font-size: 1.25rem;
    font-weight: 700;
    margin-bottom: var(--space-2);
    transition: color var(--transition-normal);
    letter-spacing: -0.025em;
    line-height: 1.3;
}

.product-card:hover .product-name {
    color: var(--primary-cyan);
}

.product-category {
    color: var(--primary-blue);
    font-size: 0.875rem;
    margin-bottom: var(--space-2);
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.025em;
}

.product-specs {
    font-size: 0.8125rem;
    color: var(--text-muted);
    margin: var(--space-2) 0;
    line-height: 1.5;
}

.product-price {
    color: var(--text-primary);
    font-size: 1.5rem;
    font-weight: 800;
    margin-top: var(--space-3);
    background: var(--primary-gradient);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    letter-spacing: -0.025em;
}

/* ================= NO RESULTS ================= */
.no-results {
    text-align: center;
    padding: var(--space-20) var(--space-8);
    color: var(--text-muted);
    background: var(--bg-primary);
    border-radius: var(--radius-xl);
    border: 1px solid var(--border-light);
    box-shadow: var(--shadow-lg);
}

.no-results i {
    font-size: 4rem;
    margin-bottom: var(--space-6);
    color: var(--text-light);
    opacity: 0.7;
}

.no-results h3 {
    font-size: clamp(1.5rem, 3vw, 2rem);
    font-weight: 700;
    margin-bottom: var(--space-4);
    color: var(--text-primary);
    letter-spacing: -0.025em;
}

.no-results p {
    font-size: 1rem;
    line-height: 1.6;
    margin-bottom: var(--space-3);
    max-width: 600px;
    margin-left: auto;
    margin-right: auto;
}

/* ================= DEBUG INFO ================= */
.debug-info {
    background: rgba(15, 23, 42, 0.05);
    border: 1px solid var(--border-light);
    border-radius: var(--radius-lg);
    padding: var(--space-4);
    margin-top: var(--space-4);
    font-size: 0.8125rem;
    font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
    color: var(--text-secondary);
}

.debug-info strong {
    color: var(--text-primary);
    font-weight: 700;
}

/* ================= RESPONSIVE DESIGN ================= */
@media (max-width: 768px) {
    .search-results-page {
        padding: var(--space-16) 0 var(--space-8);
    }
    
    .search-header {
        padding: var(--space-12) 0;
        margin-bottom: var(--space-8);
        border-radius: var(--radius-xl);
    }
    
    .search-tabs {
        flex-direction: column;
        align-items: center;
        gap: var(--space-3);
    }
    
    .search-tab {
        width: 100%;
        max-width: 280px;
        text-align: center;
    }
    
    .results-section {
        padding: var(--space-6);
        margin-bottom: var(--space-6);
        border-radius: var(--radius-lg);
    }
    
    .results-title {
        flex-direction: column;
        text-align: center;
        gap: var(--space-3);
    }
    
    .product-card {
        padding: var(--space-5);
    }
    
    .product-card-content {
        flex-direction: column;
        text-align: center;
        gap: var(--space-4);
    }
    
    .product-image {
        width: 100px;
        height: 100px;
        margin: 0 auto;
    }
    
    .product-info {
        padding-left: 0;
    }
    
    .menu-item {
        padding: var(--space-5);
    }
    
    .no-results {
        padding: var(--space-16) var(--space-4);
    }
}

@media (max-width: 480px) {
    .search-header h1 {
        font-size: 2rem;
    }
    
    .search-stats {
        font-size: 1rem;
    }
    
    .product-image {
        width: 80px;
        height: 80px;
    }
    
    .product-name {
        font-size: 1.125rem;
    }
    
    .product-price {
        font-size: 1.25rem;
    }
}

/* ================= ACCESSIBILITY ================= */
@media (prefers-reduced-motion: reduce) {
    * {
        animation-duration: 0.01ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: 0.01ms !important;
    }
}

@media (prefers-color-scheme: dark) {
    :root {
        --bg-primary: #1e293b;
        --bg-secondary: #0f172a;
        --bg-tertiary: #334155;
        --text-primary: #f8fafc;
        --text-secondary: #e2e8f0;
        --text-muted: #94a3b8;
        --border-light: #334155;
    }
}

/* ================= PRINT STYLES ================= */
@media print {
    .search-results-page {
        background: white;
        color: black;
    }
    
    .search-header,
    .results-section {
        background: white;
        border: 1px solid #ccc;
        box-shadow: none;
    }
    
    .product-card,
    .menu-item {
        background: white;
        border: 1px solid #ccc;
        break-inside: avoid;
    }
    
    .search-tabs {
        display: none;
    }
}
</style>

<script>
// Enhanced image loading with better error handling
document.addEventListener('DOMContentLoaded', function() {
    const images = document.querySelectorAll('.product-image');
    
    images.forEach(img => {
        // Add loading class initially
        img.classList.add('loading');
        
        // Handle successful load
        img.addEventListener('load', function() {
            this.classList.remove('loading');
            this.classList.add('loaded');
            this.style.opacity = '1';
            console.log('Image loaded successfully:', this.src);
        });
        
        // Handle error with multiple fallbacks
        img.addEventListener('error', function() {
            console.warn('Image failed to load:', this.src);
            this.classList.remove('loading');
            this.classList.add('error');
            
            // Try fallback images in order
            const fallbacks = [
                '<?php echo IMG_URL; ?>/placeholder-product.png',
                '<?php echo IMG_URL; ?>/no-image.png',
                'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTIwIiBoZWlnaHQ9IjEyMCIgdmlld0JveD0iMCAwIDEyMCAxMjAiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+CjxyZWN0IHdpZHRoPSIxMjAiIGhlaWdodD0iMTIwIiBmaWxsPSIjRjVGNUY1Ii8+CjxwYXRoIGQ9Ik00MCA0MEg4MFY4MEg0MFY0MFoiIGZpbGw9IiNEOUQ5RDkiLz4KPHN2ZyB4PSI0NSIgeT0iNDUiIHdpZHRoPSIzMCIgaGVpZ2h0PSIzMCIgdmlld0JveD0iMCAwIDI0IDI0IiBmaWxsPSJub25lIj4KPHBhdGggZD0iTTEyIDJMMTMuMDkgOC4yNkwyMCA5TDEzLjA5IDE1Ljc0TDEyIDIyTDEwLjkxIDE1Ljc0TDQgOUwxMC45MSA4LjI2TDEyIDJaIiBmaWxsPSIjOTk5OTk5Ii8+Cjwvc3ZnPgo8L3N2Zz4K'
            ];
            
            let fallbackIndex = 0;
            const tryNextFallback = () => {
                if (fallbackIndex < fallbacks.length) {
                    this.src = fallbacks[fallbackIndex];
                    fallbackIndex++;
                } else {
                    this.style.opacity = '0.5';
                    this.style.backgroundColor = '#f5f5f5';
                }
            };
            
            // Try first fallback immediately
            tryNextFallback();
        });
        
        // Set initial opacity
        img.style.opacity = '0';
        img.style.transition = 'opacity 0.3s ease';
        
        // Check if image is already loaded
        if (img.complete && img.naturalHeight !== 0) {
            img.classList.remove('loading');
            img.classList.add('loaded');
            img.style.opacity = '1';
        }
    });
    
    // Add intersection observer for lazy loading
    if ('IntersectionObserver' in window) {
        const imageObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    if (!img.classList.contains('loaded')) {
                        // Force load the image
                        const src = img.src;
                        img.src = '';
                        img.src = src;
                    }
                    imageObserver.unobserve(img);
                }
            });
        }, { rootMargin: '50px' });
        
        images.forEach(img => imageObserver.observe(img));
    }
});
</script>

<div class="search-results-page">
    <div class="container">
        <!-- Search Header -->
        <div class="search-header">
            <h1>
                <i class="fas fa-search"></i>
                Search Results
            </h1>
            
            <?php if (!empty($query)): ?>
                <div class="search-stats">
                    Found <strong><?php echo $total_results; ?></strong> results for "<strong><?php echo htmlspecialchars($query); ?></strong>"
                </div>
            <?php endif; ?>
            
            <?php if (!empty($debug_info) && APP_ENV === 'development'): ?>
                    <div class="debug-info">
                        <strong>Debug Info:</strong><br>
                        DB Connection: <?php echo htmlspecialchars($debug_info['db_connection'] ?? 'Unknown'); ?><br>
                        Query Received: "<?php echo htmlspecialchars($debug_info['query_received'] ?? ''); ?>"<br>
                        Query Length: <?php echo $debug_info['query_length'] ?? 0; ?><br>
                        Search Type: <?php echo htmlspecialchars($debug_info['search_type'] ?? ''); ?><br>
                        Total Products in DB: <?php echo $debug_info['total_products'] ?? 'N/A'; ?><br>
                        Products Found: <?php echo $debug_info['products_found'] ?? 0; ?><br>
                        Request URI: <?php echo htmlspecialchars($debug_info['request_uri'] ?? 'Not set'); ?><br>
                        Query String: <?php echo htmlspecialchars($debug_info['query_string'] ?? 'Not set'); ?><br>
                        GET Parameters: <?php echo htmlspecialchars(print_r($debug_info['_GET_params'] ?? [], true)); ?><br>
                        <?php if (isset($debug_info['error'])): ?>
                            Error: <?php echo htmlspecialchars($debug_info['error']); ?><br>
                        <?php endif; ?>
                        <?php if (isset($debug_info['sample_products']) && !empty($debug_info['sample_products'])): ?>
                            Sample Products:<br>
                            <?php foreach ($debug_info['sample_products'] as $sample): ?>
                                - <?php echo htmlspecialchars($sample['product_name'] ?? 'Unknown'); ?><br>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
            <?php endif; ?>
            
            <?php if (!empty($query)): ?>
                <!-- Search Tabs -->
                <div class="search-tabs">
                    <a href="?query=<?php echo urlencode($query); ?>&type=all" 
                       class="search-tab <?php echo $search_type === 'all' ? 'active' : ''; ?>">
                        <i class="fas fa-globe"></i> All Results
                    </a>
                    <a href="?query=<?php echo urlencode($query); ?>&type=products" 
                       class="search-tab <?php echo $search_type === 'products' ? 'active' : ''; ?>">
                        <i class="fas fa-boxes"></i> Products (<?php echo count($products); ?>)
                    </a>
                    <a href="?query=<?php echo urlencode($query); ?>&type=menu" 
                       class="search-tab <?php echo $search_type === 'menu' ? 'active' : ''; ?>">
                        <i class="fas fa-sitemap"></i> Pages (<?php echo count($menu_results); ?>)
                    </a>
                </div>
            <?php else: ?>
                <div class="search-stats">
                    Please enter a search term to find products and pages
                </div>
            <?php endif; ?>
        </div>

            <?php if (empty($query)): ?>
                <!-- No Search Query -->
                <div class="no-results">
                    <i class="fas fa-search"></i>
                    <h3>Start Your Search</h3>
                    <p>Use the search box above to find products and pages on our website.</p>
                    
                    <div style="background: rgba(30, 41, 59, 0.4); padding: 20px; border-radius: 8px; margin-top: 20px;">
                        <h4 style="color: var(--primary-blue); margin-bottom: 15px;">What You Can Search For:</h4>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px;">
                            <div>
                                <strong style="color: var(--text-primary);">Brands:</strong><br>
                                <span style="color: var(--text-muted);">Hitachi, Daikin, O General, Mitsubishi</span>
                            </div>
                            <div>
                                <strong style="color: var(--text-primary);">Categories:</strong><br>
                                <span style="color: var(--text-muted);">Residential AC, Commercial AC, Cassette AC</span>
                            </div>
                            <div>
                                <strong style="color: var(--text-primary);">Subcategories:</strong><br>
                                <span style="color: var(--text-muted);">Split AC, Window AC, Ductless AC</span>
                            </div>
                            <div>
                                <strong style="color: var(--text-primary);">Specifications:</strong><br>
                                <span style="color: var(--text-muted);">1.5 Ton, 2 Ton, 5 Star, Inverter, Non-Inverter</span>
                            </div>
                            <div>
                                <strong style="color: var(--text-primary);">Features:</strong><br>
                                <span style="color: var(--text-muted);">Warranty, AMC Available, Model Numbers</span>
                            </div>
                            <div>
                                <strong style="color: var(--text-primary);">Pages:</strong><br>
                                <span style="color: var(--text-muted);">Home, About, Services, Contact</span>
                            </div>
                        </div>
                    </div>
                </div>
        <?php else: ?>
            <?php if ($search_type === 'all' || $search_type === 'menu'): ?>
                <!-- Menu Results -->
                <?php if (!empty($menu_results)): ?>
                    <div class="results-section">
                        <h2 class="results-title">
                            <i class="fas fa-sitemap"></i>
                            Pages & Navigation
                            <span class="results-count"><?php echo count($menu_results); ?></span>
                        </h2>
                        
                        <?php foreach ($menu_results as $item): ?>
                            <a href="<?php echo $item['url']; ?>" class="menu-item">
                                <div class="menu-item-title"><?php echo htmlspecialchars($item['title']); ?></div>
                                <div class="menu-item-description"><?php echo htmlspecialchars($item['description']); ?></div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <?php if ($search_type === 'all' || $search_type === 'products'): ?>
                <!-- Product Results -->
                <?php if (!empty($products)): ?>
                    <div class="results-section">
                        <h2 class="results-title">
                            <i class="fas fa-boxes"></i>
                            Products
                            <span class="results-count"><?php echo count($products); ?></span>
                        </h2>
                        
                        <?php foreach ($products as $product): ?>
                            <a href="<?= product_url($product['id'], false, true, 'search') ?>" class="product-card">
                                <div class="product-card-content">
                                    <div class="product-image-container">
                                        <img src="<?php echo BASE_URL; ?>/public/image.php?file=<?php echo urlencode($product['product_image']); ?>" 
                                             alt="<?php echo htmlspecialchars($product['product_name']); ?>" 
                                             class="product-image"
                                             loading="lazy"
                                             onerror="this.src='<?php echo IMG_URL; ?>/placeholder-product.png'">
                                        <div class="image-loading">
                                            <i class="fas fa-spinner fa-spin"></i>
                                        </div>
                                    </div>
                                    <div class="product-info">
                                        <div class="product-name"><?php echo htmlspecialchars($product['product_name']); ?></div>
                                        <div class="product-category">
                                            <strong><?php echo htmlspecialchars($product['brand_name'] ?? 'Unknown Brand'); ?></strong> - 
                                            <?php echo htmlspecialchars($product['category_name']); ?>
                                            <?php if (!empty($product['sub_category_name'])): ?>
                                                - <?php echo htmlspecialchars($product['sub_category_name']); ?>
                                            <?php endif; ?>
                                        </div>
                                        <div class="product-specs">
                                            <?php if (!empty($product['model_name'])): ?>
                                                Model: <?php echo htmlspecialchars($product['model_name']); ?> | 
                                            <?php endif; ?>
                                            <?php if (!empty($product['capacity'])): ?>
                                                <?php echo htmlspecialchars($product['capacity']); ?> | 
                                            <?php endif; ?>
                                            <?php if (!empty($product['inverter'])): ?>
                                                <?php echo htmlspecialchars($product['inverter']); ?> Inverter | 
                                            <?php endif; ?>
                                            <?php if (!empty($product['star_rating'])): ?>
                                                <?php echo $product['star_rating']; ?> Star | 
                                            <?php endif; ?>
                                            <?php if (!empty($product['warranty_years'])): ?>
                                                <?php echo $product['warranty_years']; ?> Year Warranty
                                            <?php endif; ?>
                                        </div>
                                        <div class="product-price">$<?php echo number_format($product['price'], 2); ?></div>
                                    </div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <?php if ($total_results === 0): ?>
                <!-- No Results -->
                <div class="no-results">
                    <i class="fas fa-search"></i>
                    <h3>No Results Found</h3>
                    <p>Sorry, we couldn't find anything matching "<strong><?php echo htmlspecialchars($query); ?></strong>"</p>
                    <p>Try different keywords or check the spelling of your search term.</p>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php include INCLUDES_PATH . '/templates/footer.php'; ?>
