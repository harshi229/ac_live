<?php
// Set page metadata
$pageTitle = 'Products';
$pageDescription = 'Browse our complete range of air conditioning products including residential, commercial, and cassette AC units';
$pageKeywords = 'AC products, air conditioner, buy AC online, split AC, window AC, commercial AC, residential AC';

require_once __DIR__ . '/../../includes/config/init.php';
include INCLUDES_PATH . '/templates/header.php';
// Database connection now in init.php

// Add the responsive CSS file
echo '<link rel="stylesheet" type="text/css" href="' . CSS_URL . '/products-responsive.css">';

// Get filter parameters
$category_id = isset($_GET['category']) ? intval($_GET['category']) : 0;
$brand_id = isset($_GET['brand']) ? intval($_GET['brand']) : 0;
$subcategory_id = isset($_GET['subcategory']) ? intval($_GET['subcategory']) : 0;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';
$min_price = isset($_GET['min_price']) ? floatval($_GET['min_price']) : 0;
$max_price = isset($_GET['max_price']) ? floatval($_GET['max_price']) : 0;
$inverter_filter = isset($_GET['inverter']) ? $_GET['inverter'] : '';
$star_rating = isset($_GET['star_rating']) ? intval($_GET['star_rating']) : 0;
$capacity_filter = isset($_GET['capacity']) ? $_GET['capacity'] : '';
$warranty_filter = isset($_GET['warranty']) ? intval($_GET['warranty']) : 0;
$amc_filter = isset($_GET['amc']) ? $_GET['amc'] : '';
$feature_filter = isset($_GET['feature']) ? intval($_GET['feature']) : 0;

// Include ProductQueryBuilder class
require_once INCLUDES_PATH . '/classes/ProductQueryBuilder.php';

// Pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$items_per_page = 12;

try {
    // Create ProductQueryBuilder instance
    $queryBuilder = new ProductQueryBuilder($pdo);
    
    // Build query from filters (this resets the builder, so filters must be added after)
    $queryBuilder->buildFromFilters([
        'category_id' => $category_id,
        'brand_id' => $brand_id,
        'subcategory_id' => $subcategory_id,
        'search' => $search,
        'min_price' => $min_price,
        'max_price' => $max_price,
        'inverter_filter' => $inverter_filter,
        'star_rating' => $star_rating,
        'capacity_filter' => $capacity_filter,
        'warranty_filter' => $warranty_filter,
        'amc_filter' => $amc_filter,
        'feature_filter' => $feature_filter,
        'sort' => $sort,
        'page' => $page,
        'items_per_page' => $items_per_page
    ]);
    
    // Add filter to only show products with show_on_product_page = 1
    // Must be called AFTER buildFromFilters() because buildFromFilters() resets the builder
    $queryBuilder->addProductPageFilter();

    // Get total count and products
    $total_products = $queryBuilder->getTotalCount();
    $total_pages = ceil($total_products / $items_per_page);
    $products = $queryBuilder->getProducts();

    // Get filter options using ProductQueryBuilder
    $filterOptions = $queryBuilder->getFilterOptions();
    $categories = $filterOptions['categories'];
    $brands = $filterOptions['brands'];
    $subcategories = $filterOptions['subcategories'];
    $features = $filterOptions['features'];
    $capacities = $filterOptions['capacities'];
    $warranties = $filterOptions['warranties'];
    $price_range = $filterOptions['price_range'];
    
    // Get all brands for display section (not just filtered ones)
    $all_brands_stmt = $pdo->query("SELECT * FROM brands WHERE status = 'active' ORDER BY name");
    $all_brands = $all_brands_stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error_message = "Error loading products: " . $e->getMessage();
    $products = [];
    $total_products = 0;
    $all_brands = [];
}
?>


<div class="products-page">
    <!-- Page Header -->
    <div class="page-header">
        <div class="container">
            <h1><i class="fas fa-boxes me-2"></i>Our Products</h1>
            <p>Discover premium air conditioning solutions for every need</p>
        </div>
    </div>

    <!-- Brands Section -->
    <?php if (!empty($all_brands)): ?>
    <section class="brands-section">
        <div class="container">
            <div class="section-header">
                <span class="section-badge">Authorized Dealers</span>
                <h2 class="section-title">All Brands We Carry</h2>
                <p class="section-subtitle">Premium quality from World Famous Brands manufacturers</p>
            </div>
            
            <div class="brands-grid">
                <?php foreach ($all_brands as $brand): ?>
                <div class="brand-card" onclick="applyBrandFilter(<?= $brand['id'] ?>)">
                    <?php if (!empty($brand['logo'])): 
                        $logo_url = BASE_URL . '/public/image.php?file=' . urlencode($brand['logo']);
                    ?>
                        <div class="brand-logo-wrapper">
                            <img src="<?= htmlspecialchars($logo_url) ?>" 
                                 alt="<?= htmlspecialchars($brand['name']) ?> Logo" 
                                 class="brand-logo-image"
                                 loading="lazy"
                                 onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                            <h4 class="brand-name" style="display: none;"><?= htmlspecialchars($brand['name']) ?></h4>
                        </div>
                    <?php else: ?>
                        <h4 class="brand-name"><?= htmlspecialchars($brand['name']) ?></h4>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <div class="container">
        <!-- Filters Toolbar (below hero) -->
        <div class="filters-toolbar">
            <div class="filters-dropdown-container">
                <!-- Search Filter -->
                <div class="filter-dropdown" id="filterSearch">
                    <button class="filter-dropdown-toggle" onclick="toggleFilterDropdown('filterSearch')">
                        <span><i class="fas fa-search"></i> Search</span>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="filter-dropdown-content">
                        <div class="search-input-wrapper">
                            <input type="text" name="search" placeholder="Search by name, model..." value="<?= htmlspecialchars($search) ?>" class="search-input" id="searchInput" oninput="handleSearchInput(this.value)" onkeypress="handleSearchKeypress(event)">
                            <button type="button" class="search-clear" onclick="clearSearch()" style="display: <?= $search ? 'block' : 'none' ?>">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Category Filter -->
                <div class="filter-dropdown" id="filterCategory">
                    <button class="filter-dropdown-toggle" onclick="toggleFilterDropdown('filterCategory')">
                        <span><i class="fas fa-th-large"></i> Category</span>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="filter-dropdown-content">
                        <div class="filter-options">
                            <div class="filter-option">
                                <input type="radio" name="category" value="0" id="category_all_categories" <?= $category_id == 0 ? 'checked' : '' ?> onchange="applyFilter('category', this.value)">
                                <label for="category_all_categories">All Categories</label>
                            </div>
                            <?php foreach ($categories as $cat): ?>
                            <div class="filter-option">
                                <input type="radio" name="category" value="<?= $cat['id'] ?>" id="category_<?= $cat['id'] ?>" <?= $category_id == $cat['id'] ? 'checked' : '' ?> onchange="applyFilter('category', this.value)">
                                <label for="category_<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></label>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Brand Filter -->
                <div class="filter-dropdown" id="filterBrand">
                    <button class="filter-dropdown-toggle" onclick="toggleFilterDropdown('filterBrand')">
                        <span><i class="fas fa-tag"></i> Brand</span>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="filter-dropdown-content">
                        <div class="filter-options">
                            <div class="filter-option">
                                <input type="radio" name="brand" value="0" id="brand_all_brands" <?= $brand_id == 0 ? 'checked' : '' ?> onchange="applyFilter('brand', this.value)">
                                <label for="brand_all_brands">All Brands</label>
                            </div>
                            <?php foreach ($brands as $brand): ?>
                            <div class="filter-option">
                                <input type="radio" name="brand" value="<?= $brand['id'] ?>" id="brand_<?= $brand['id'] ?>" <?= $brand_id == $brand['id'] ? 'checked' : '' ?> onchange="applyFilter('brand', this.value)">
                                <label for="brand_<?= $brand['id'] ?>"><?= htmlspecialchars($brand['name']) ?></label>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Price Range Filter -->
                <div class="filter-dropdown" id="filterPrice">
                    <button class="filter-dropdown-toggle" onclick="toggleFilterDropdown('filterPrice')">
                        <span><i class="fas fa-rupee-sign"></i> Price</span>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="filter-dropdown-content">
                        <div class="price-range-container">
                            <div class="price-inputs">
                                <input type="number" name="min_price" placeholder="Min" value="<?= $min_price > 0 ? $min_price : '' ?>" min="0" max="<?= $price_range['max_price'] ?>" onchange="applyPriceFilter()">
                                <span class="price-separator">-</span>
                                <input type="number" name="max_price" placeholder="Max" value="<?= $max_price > 0 ? $max_price : '' ?>" min="0" max="<?= $price_range['max_price'] ?>" onchange="applyPriceFilter()">
                            </div>
                            <div class="price-labels">
                                <span>₹<?= number_format($price_range['min_price']) ?></span>
                                <span>₹<?= number_format($price_range['max_price']) ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Capacity Filter -->
                <div class="filter-dropdown" id="filterCapacity">
                    <button class="filter-dropdown-toggle" onclick="toggleFilterDropdown('filterCapacity')">
                        <span><i class="fas fa-snowflake"></i> Capacity</span>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="filter-dropdown-content">
                        <div class="filter-options">
                            <div class="filter-option">
                                <input type="radio" name="capacity" value="" id="capacity_all_capacities" <?= $capacity_filter == '' ? 'checked' : '' ?> onchange="applyFilter('capacity', this.value)">
                                <label for="capacity_all_capacities">All Capacities</label>
                            </div>
                            <?php foreach ($capacities as $capacity): ?>
                            <div class="filter-option">
                                <input type="radio" name="capacity" value="<?= htmlspecialchars($capacity) ?>" id="capacity_<?= $capacity ?>" <?= $capacity_filter == $capacity ? 'checked' : '' ?> onchange="applyFilter('capacity', this.value)">
                                <label for="capacity_<?= $capacity ?>"><?= htmlspecialchars($capacity) ?></label>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Type Filter -->
                <div class="filter-dropdown" id="filterType">
                    <button class="filter-dropdown-toggle" onclick="toggleFilterDropdown('filterType')">
                        <span><i class="fas fa-bolt"></i> Type</span>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="filter-dropdown-content">
                        <div class="filter-options">
                            <div class="filter-option">
                                <input type="radio" name="inverter" value="" id="type_all_types" <?= $inverter_filter == '' ? 'checked' : '' ?> onchange="applyFilter('inverter', this.value)">
                                <label for="type_all_types">All Types</label>
                            </div>
                            <div class="filter-option">
                                <input type="radio" name="inverter" value="Yes" id="type_inverter" <?= $inverter_filter == 'Yes' ? 'checked' : '' ?> onchange="applyFilter('inverter', this.value)">
                                <label for="type_inverter">Inverter</label>
                            </div>
                            <div class="filter-option">
                                <input type="radio" name="inverter" value="No" id="type_non_inverter" <?= $inverter_filter == 'No' ? 'checked' : '' ?> onchange="applyFilter('inverter', this.value)">
                                <label for="type_non_inverter">Non-Inverter</label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Rating Filter -->
                <div class="filter-dropdown" id="filterRating">
                    <button class="filter-dropdown-toggle" onclick="toggleFilterDropdown('filterRating')">
                        <span><i class="fas fa-star"></i> Rating</span>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="filter-dropdown-content">
                        <div class="rating-options">
                            <div class="rating-option">
                                <input type="radio" name="star_rating" value="0" id="rating_all_ratings" <?= $star_rating == 0 ? 'checked' : '' ?> onchange="applyFilter('star_rating', this.value)">
                                <label for="rating_all_ratings">All Ratings</label>
                            </div>
                            <?php for ($i = 5; $i >= 1; $i--): ?>
                            <div class="rating-option">
                                <input type="radio" name="star_rating" value="<?= $i ?>" id="rating_<?= $i ?>" <?= $star_rating == $i ? 'checked' : '' ?> onchange="applyFilter('star_rating', this.value)">
                                <label for="rating_<?= $i ?>">
                                    <?php for ($j = 1; $j <= $i; $j++): ?>
                                        <i class="fas fa-star"></i>
                                    <?php endfor; ?>
                                    <?php for ($j = $i + 1; $j <= 5; $j++): ?>
                                        <i class="far fa-star"></i>
                                    <?php endfor; ?>
                                    & Up
                                </label>
                            </div>
                            <?php endfor; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="filter-actions-top">
                <button class="btn-clear-all-filters" onclick="clearAllFilters()">
                    <i class="fas fa-times"></i> Clear All
                </button>
            </div>
        </div>
        
        <!-- Active Filters Bar -->
        <div class="active-filters-bar" id="activeFiltersBar" style="display: none;">
            <span class="active-filters-label">Active Filters:</span>
            <div class="active-filters-chips" id="activeFiltersChips">
                <!-- Dynamically populated filter chips -->
            </div>
        </div>
        
        <!-- Products Header with Sort & View Toggle -->
        <div class="products-header-enhanced">
            <div class="results-info">
                Showing <strong id="productsCount"><?= $total_products ?></strong> product<?= $total_products != 1 ? 's' : '' ?>
            </div>
            <div class="header-controls">
                <div class="view-toggle">
                    <button class="view-btn active" data-view="list" onclick="switchView('list')">
                        <i class="fas fa-list"></i>
                    </button>
                    <button class="view-btn" data-view="grid" onclick="switchView('grid')">
                        <i class="fas fa-th"></i>
                    </button>
                </div>
                <select id="sortSelect" class="sort-select" onchange="applySort(this.value)">
                    <option value="newest" <?= $sort == 'newest' ? 'selected' : '' ?>>Newest First</option>
                    <option value="price_low" <?= $sort == 'price_low' ? 'selected' : '' ?>>Price: Low to High</option>
                    <option value="price_high" <?= $sort == 'price_high' ? 'selected' : '' ?>>Price: High to Low</option>
                    <option value="name_asc" <?= $sort == 'name_asc' ? 'selected' : '' ?>>Name: A to Z</option>
                    <option value="name_desc" <?= $sort == 'name_desc' ? 'selected' : '' ?>>Name: Z to A</option>
                    <option value="rating" <?= $sort == 'rating' ? 'selected' : '' ?>>Highest Rated</option>
                </select>
            </div>
        </div>
        
        <!-- Products List Container -->
        <div class="products-list-container" id="productsContainer">

            <!-- Products List -->
            <?php if (!empty($products)): ?>
            <?php foreach ($products as $product): ?>
            <div class="product-list-item">
                <div class="product-list-image">
                    <img src="<?= BASE_URL ?>/public/image.php?file=<?= urlencode($product['product_image']) ?>" 
                         alt="<?= htmlspecialchars($product['product_name']) ?>"
                         loading="lazy"
                         onerror="this.src='<?= IMG_URL ?>/placeholder-product.png'">
                    
                    <?php if ($product['amc_available']): ?>
                    <span class="product-badge">AMC</span>
                    <?php endif; ?>
                    
                    <button class="wishlist-quick-btn" onclick="toggleWishlist(<?= $product['id'] ?>)">
                        <i class="far fa-heart"></i>
                    </button>
                </div>
                
                <div class="product-list-details">
                    <div class="product-list-header">
                        <span class="product-brand"><?= htmlspecialchars($product['brand_name']) ?></span>
                        <h3 class="product-title">
                            <a href="details.php?id=<?= $product['id'] ?>&from=product">
                                <?= htmlspecialchars($product['product_name']) ?>
                            </a>
                        </h3>
                        <div class="product-rating">
                            <?php for($i=1; $i<=5; $i++): ?>
                                <i class="fas fa-star<?= $i <= $product['star_rating'] ? '' : '-o' ?>"></i>
                            <?php endfor; ?>
                            <span class="rating-text">(<?= $product['star_rating'] ?>/5)</span>
                        </div>
                    </div>
                    
                    <div class="product-list-specs">
                        <span class="spec-item"><i class="fas fa-snowflake"></i> <?= htmlspecialchars($product['capacity']) ?></span>
                        <span class="spec-item"><i class="fas fa-star"></i> <?= htmlspecialchars($product['star_rating']) ?> Star</span>
                        <?php if($product['inverter'] == 'Yes'): ?>
                        <span class="spec-item"><i class="fas fa-bolt"></i> Inverter</span>
                        <?php endif; ?>
                        <span class="spec-item"><i class="fas fa-shield-alt"></i> <?= $product['warranty_years'] ?> Year Warranty</span>
                    </div>
                    
                    <div class="product-list-description">
                        <?= substr($product['description'] ?? '', 0, 150) ?>...
                    </div>
                    
                    <div class="product-list-footer">
                        <div class="product-price-section">
                            <?php if ($product['original_price'] && $product['original_price'] > $product['price']): ?>
                                <!-- Discount pricing display -->
                                <div class="pricing-container">
                                    <div class="price-row">
                                        <span class="current-price">₹<?= number_format($product['price']) ?></span>
                                        <span class="original-price">₹<?= number_format($product['original_price']) ?></span>
                                        <span class="discount-badge"><?= number_format($product['discount_percentage'], 0) ?>% OFF</span>
                                    </div>
                                </div>
                            <?php else: ?>
                                <!-- Regular pricing -->
                                <div class="product-price">₹<?= number_format($product['price']) ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="product-actions">
                            <a href="details.php?id=<?= $product['id'] ?>&from=product" class="btn-view-details">
                                <i class="fas fa-eye"></i> View Details
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php else: ?>
            <!-- Empty State -->
            <div class="empty-state">
                <i class="fas fa-box-open"></i>
                <h3>No Products Found</h3>
                <p>Sorry, we couldn't find any products matching your criteria.</p>
                <?php if (isset($total_products) && $total_products == 0): ?>
                    <p class="text-muted mt-2">
                        <small>
                            <i class="fas fa-info-circle"></i> 
                            Tip: Make sure products have "Show on Product Page" checked in the admin panel.
                        </small>
                    </p>
                <?php endif; ?>
                <a href="<?php echo USER_URL; ?>/products/" class="btn-view-details">
                    <i class="fas fa-arrow-left"></i> View All Products
                </a>
            </div>
            <?php endif; ?>

            <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <div class="pagination-wrapper">
                    <ul class="pagination">
                        <?php if ($page > 1): ?>
                        <li><a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>"><i class="fas fa-chevron-left"></i></a></li>
                        <?php endif; ?>

                        <?php
                        $start_page = max(1, $page - 2);
                        $end_page = min($total_pages, $page + 2);
                        
                        if ($start_page > 1): ?>
                            <li><a href="?<?= http_build_query(array_merge($_GET, ['page' => 1])) ?>">1</a></li>
                            <?php if ($start_page > 2): ?>
                                <li class="disabled"><a>...</a></li>
                            <?php endif; ?>
                        <?php endif; ?>

                        <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                        <li class="<?= $i == $page ? 'active' : '' ?>">
                            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
                        </li>
                        <?php endfor; ?>

                        <?php if ($end_page < $total_pages): ?>
                            <?php if ($end_page < $total_pages - 1): ?>
                                <li class="disabled"><a>...</a></li>
                            <?php endif; ?>
                            <li><a href="?<?= http_build_query(array_merge($_GET, ['page' => $total_pages])) ?>"><?= $total_pages ?></a></li>
                        <?php endif; ?>

                        <?php if ($page < $total_pages): ?>
                        <li><a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>"><i class="fas fa-chevron-right"></i></a></li>
                        <?php endif; ?>
                    </ul>
                </div>
                <?php endif; ?>
        </div>
    </div>
</div>

<!-- Quick View Modal removed as requested -->

<!-- Configuration object for JavaScript -->
<script>
// Pass PHP variables to JavaScript
window.productsConfig = {
    uploadUrl: '<?= UPLOAD_URL ?>',
    imgUrl: '<?= IMG_URL ?>',
    userUrl: '<?= USER_URL ?>',
    userId: <?php
        if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
            echo (int)$_SESSION['user_id'];
        } else {
            echo 'null';
        }
    ?>,
    categories: <?= json_encode($categories) ?>,
    brands: <?= json_encode($brands) ?>,
    subcategories: <?= json_encode($subcategories) ?>
};
</script>

<!-- Load external JavaScript -->
<script src="<?= JS_URL ?>/products.js"></script>

<?php include INCLUDES_PATH . '/templates/footer.php'; ?>

