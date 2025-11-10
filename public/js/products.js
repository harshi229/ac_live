/**
 * Products Page JavaScript Module
 * Handles filtering, sorting, pagination, wishlist, and cart functionality
 */

// ============================================================================
// CONFIGURATION AND CONSTANTS
// ============================================================================

// Global variables for AJAX filtering
let currentFilters = {};
let isLoading = false;

// Initialize filters from URL parameters
function initializeFiltersFromURL() {
    const urlParams = new URLSearchParams(window.location.search);
    
    // Check if we're coming from a product page link (has 'from' parameter)
    const isFromProductPage = urlParams.has('from') && urlParams.get('from') === 'product';
    
    // If coming from product page, don't initialize filters from URL
    if (isFromProductPage) {
        currentFilters = {
            page: 1,
            sort: 'newest',
            category: '0',
            subcategory: '0',
            brand: '0',
            search: '',
            min_price: '',
            max_price: '',
            capacity: '',
            inverter: '',
            star_rating: '0',
            warranty: '0',
            amc: '',
            stock: '',
            feature: '0'
        };
        
        // Clean the URL by removing the 'from' parameter
        const cleanUrl = new URL(window.location);
        cleanUrl.searchParams.delete('from');
        window.history.replaceState({}, '', cleanUrl);
    } else {
        // Normal initialization from URL parameters
        currentFilters = {
            page: parseInt(urlParams.get('page')) || 1,
            sort: urlParams.get('sort') || 'newest',
            category: urlParams.get('category') || '0',
            subcategory: urlParams.get('subcategory') || '0',
            brand: urlParams.get('brand') || '0',
            search: urlParams.get('search') || '',
            min_price: urlParams.get('min_price') || '',
            max_price: urlParams.get('max_price') || '',
            capacity: urlParams.get('capacity') || '',
            inverter: urlParams.get('inverter') || '',
            star_rating: urlParams.get('star_rating') || '0',
            warranty: urlParams.get('warranty') || '0',
            amc: urlParams.get('amc') || '',
            stock: urlParams.get('stock') || '',
            feature: urlParams.get('feature') || '0'
        };
    }
    
    // Update the sort dropdown to reflect URL parameter
    const sortSelect = document.getElementById('sortSelect');
    if (sortSelect) {
        sortSelect.value = currentFilters.sort;
    }
    
    // Update filter inputs to reflect URL parameters
    updateFilterInputsFromURL();
}

// Update filter inputs to reflect URL parameters
function updateFilterInputsFromURL() {
    // Update search input
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.value = currentFilters.search || '';
        // Show/hide search clear button
        const clearBtn = document.querySelector('.search-clear');
        if (clearBtn) {
            clearBtn.style.display = currentFilters.search ? 'block' : 'none';
        }
    }
    
    // Update category radio buttons
    const categoryRadios = document.querySelectorAll('input[name="category"]');
    categoryRadios.forEach(radio => {
        radio.checked = radio.value === currentFilters.category;
    });
    
    // Update brand radio buttons
    const brandRadios = document.querySelectorAll('input[name="brand"]');
    brandRadios.forEach(radio => {
        radio.checked = radio.value === currentFilters.brand;
    });
    
    // Update price inputs
    const minPriceInput = document.querySelector('input[name="min_price"]');
    const maxPriceInput = document.querySelector('input[name="max_price"]');
    if (minPriceInput) {
        minPriceInput.value = currentFilters.min_price || '';
    }
    if (maxPriceInput) {
        maxPriceInput.value = currentFilters.max_price || '';
    }
    
    // Update capacity radio buttons
    const capacityRadios = document.querySelectorAll('input[name="capacity"]');
    capacityRadios.forEach(radio => {
        radio.checked = radio.value === currentFilters.capacity;
    });
    
    // Update inverter radio buttons
    const inverterRadios = document.querySelectorAll('input[name="inverter"]');
    inverterRadios.forEach(radio => {
        radio.checked = radio.value === currentFilters.inverter;
    });
    
    // Update rating radio buttons
    const ratingRadios = document.querySelectorAll('input[name="star_rating"]');
    ratingRadios.forEach(radio => {
        radio.checked = radio.value === currentFilters.star_rating;
    });
    
    // Update active filter chips
    updateActiveFilterChips();
}

// Update URL parameters based on current filters
function updateURLFromFilters() {
    const url = new URL(window.location);
    
    // Clear existing parameters
    url.search = '';
    
    // Add non-default parameters
    Object.keys(currentFilters).forEach(key => {
        const value = currentFilters[key];
        if (value && value !== '0' && value !== '' && value !== '1') {
            url.searchParams.set(key, value);
        }
    });
    
    // Always include sort parameter
    if (!url.searchParams.has('sort')) {
        url.searchParams.set('sort', 'newest');
    }
    
    // Update URL without page reload
    window.history.replaceState({}, '', url);
}

// Configuration object - populated from PHP
let config = window.productsConfig || {
    uploadUrl: '',
    imgUrl: '',
    userUrl: '',
    userId: null,
    categories: [],
    brands: [],
    subcategories: []
};

// ============================================================================
// UTILITY FUNCTIONS
// ============================================================================

/**
 * Format number with Indian locale
 */
function formatNumber(num) {
    return num.toLocaleString('en-IN');
}

/**
 * Get user ID from config
 */
function getUserId() {
    return config.userId;
}

/**
 * Show notification (fallback if global function doesn't exist)
 */
function showNotification(message, type = 'info') {
    if (typeof window.showNotification === 'function') {
        window.showNotification(message, type);
    } else {
        // Fallback to alert
        alert(message);
    }
}

// ============================================================================
// FILTER MANAGEMENT
// ============================================================================

/**
 * Initialize filters from URL parameters
 */
function initializeFilters() {
    const urlParams = new URLSearchParams(window.location.search);
    currentFilters = {
        category: urlParams.get('category') || '0',
        brand: urlParams.get('brand') || '0',
        subcategory: urlParams.get('subcategory') || '0',
        search: urlParams.get('search') || '',
        sort: urlParams.get('sort') || 'newest',
        min_price: urlParams.get('min_price') || '',
        max_price: urlParams.get('max_price') || '',
        inverter: urlParams.get('inverter') || '',
        star_rating: urlParams.get('star_rating') || '0',
        capacity: urlParams.get('capacity') || '',
        warranty: urlParams.get('warranty') || '0',
        amc: urlParams.get('amc') || '',
        stock: urlParams.get('stock') || '',
        feature: urlParams.get('feature') || '0',
        page: urlParams.get('page') || '1'
    };
}

/**
 * Toggle filters on mobile
 */
function toggleFilters() {
    const filterForm = document.querySelector('.filter-form');
    const toggleBtn = document.querySelector('.filter-toggle');
    
    filterForm.classList.toggle('show');
    toggleBtn.classList.toggle('active');
}

/**
 * Update filter count badge
 */
function updateFilterCount() {
    const filterCount = document.getElementById('filterCount');
    
    // If filterCount element doesn't exist, skip this function
    if (!filterCount) {
        return;
    }
    
    let count = 0;
    
    // Count active filters
    const searchInput = document.querySelector('.search-input');
    if (searchInput && searchInput.value.trim() !== '') count++;
    
    const selects = document.querySelectorAll('.filter-group select');
    selects.forEach(select => {
        if (select.value && select.value !== '0' && select.value !== '') count++;
    });
    
    const radios = document.querySelectorAll('.filter-group input[type="radio"]:checked');
    radios.forEach(radio => {
        if (radio.value && radio.value !== '') count++;
    });
    
    const priceInputs = document.querySelectorAll('.filter-group input[type="number"]');
    priceInputs.forEach(input => {
        if (input.value && input.value !== '') count++;
    });
    
    filterCount.textContent = count;
    filterCount.style.display = count > 0 ? 'inline-block' : 'none';
}

// Search functionality with debouncing
let searchTimeout = null;

/**
 * Handle search input with debouncing
 */
function handleSearchInput(value) {
    // Clear existing timeout
    if (searchTimeout) {
        clearTimeout(searchTimeout);
    }
    
    // Show/hide clear button
    toggleSearchClear();
    
    // Debounce search - wait 500ms after user stops typing
    searchTimeout = setTimeout(() => {
        applyFilter('search', value.trim());
    }, 500);
}

/**
 * Handle search keypress events
 */
function handleSearchKeypress(event) {
    if (event.key === 'Enter') {
        // Clear timeout and search immediately on Enter
        if (searchTimeout) {
            clearTimeout(searchTimeout);
        }
        
        event.preventDefault();
        const searchInput = document.getElementById('searchInput');
        if (searchInput) {
            applyFilter('search', searchInput.value.trim());
        }
    }
}

/**
 * Clear search input
 */
function clearSearch() {
    const searchInput = document.getElementById('searchInput');
    const clearBtn = document.querySelector('.search-clear');
    
    if (searchInput) {
        searchInput.value = '';
        searchInput.focus();
    }
    
    if (clearBtn) {
        clearBtn.style.display = 'none';
    }
    
    // Clear timeout if exists
    if (searchTimeout) {
        clearTimeout(searchTimeout);
    }
    
    // Apply empty search filter
    applyFilter('search', '');
}

/**
 * Show/hide search clear button
 */
function toggleSearchClear() {
    const searchInput = document.getElementById('searchInput');
    const clearBtn = document.querySelector('.search-clear');
    
    if (searchInput && clearBtn) {
        clearBtn.style.display = searchInput.value ? 'block' : 'none';
    }
}

/**
 * Update subcategories based on selected category
 */
function updateSubcategories() {
    const categorySelect = document.getElementById('categoryFilter');
    const subcategoryGroup = document.getElementById('subcategoryGroup');
    const subcategorySelect = document.getElementById('subcategoryFilter');
    
    const categoryId = categorySelect.value;
    
    if (categoryId === '0') {
        subcategoryGroup.style.display = 'none';
        subcategorySelect.value = '0';
    } else {
        subcategoryGroup.style.display = 'block';
        
        // Clear existing options except first
        subcategorySelect.innerHTML = '<option value="0">All Subcategories</option>';
        
        // Filter subcategories based on selected category
        const filteredSubcategories = config.subcategories.filter(sub => sub.category_id == categoryId);
        
        filteredSubcategories.forEach(sub => {
            const option = document.createElement('option');
            option.value = sub.id;
            option.textContent = sub.name;
            subcategorySelect.appendChild(option);
        });
    }
}

/**
 * Initialize price slider
 */
function initializePriceSlider() {
    const priceSlider = document.getElementById('priceSlider');
    const minPriceInput = document.querySelector('input[name="min_price"]');
    const maxPriceInput = document.querySelector('input[name="max_price"]');
    
    if (priceSlider && minPriceInput && maxPriceInput) {
        const minPrice = parseInt(priceSlider.min);
        const maxPrice = parseInt(priceSlider.max);
        
        // Update max price input when slider changes
        priceSlider.addEventListener('input', function() {
            maxPriceInput.value = this.value;
        });
        
        // Update slider when max price input changes
        maxPriceInput.addEventListener('input', function() {
            const value = parseInt(this.value);
            if (value >= minPrice && value <= maxPrice) {
                priceSlider.value = value;
            }
        });
        
        // Update slider when min price input changes
        minPriceInput.addEventListener('input', function() {
            const value = parseInt(this.value);
            if (value >= minPrice && value <= maxPrice) {
                priceSlider.min = value;
                if (parseInt(priceSlider.value) < value) {
                    priceSlider.value = value;
                    maxPriceInput.value = value;
                }
            }
        });
    }
}

// ============================================================================
// PRODUCT LOADING AND RENDERING
// ============================================================================

/**
 * Load products via AJAX
 */
function loadProducts(page = 1, showLoading = true) {
    if (isLoading) return;

    if (showLoading) {
        showLoadingState();
    }

    isLoading = true;
    currentFilters.page = page;

    // Build query string
    const params = new URLSearchParams();
    Object.keys(currentFilters).forEach(key => {
        if (currentFilters[key] && currentFilters[key] !== '0' && currentFilters[key] !== '') {
            params.append(key, currentFilters[key]);
        }
    });

    fetch(`ajax_products.php?${params.toString()}`, {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        isLoading = false;
        hideLoadingState();

        if (data.success) {
            renderProducts(data.products, data.total_products, data.total_pages, data.current_page);
            updatePagination(data.total_pages, data.current_page);
            updateResultsInfo(data.total_products);
            updateActiveFilters();
            updateBrowserHistory();
        } else {
            showNotification(data.message || 'Error loading products', 'error');
        }
    })
    .catch(error => {
        isLoading = false;
        hideLoadingState();
        console.error('Error:', error);
        showNotification('An error occurred while loading products', 'error');
    });
}

/**
 * Render products in the grid
 */
function renderProducts(products, totalProducts, totalPages, currentPage) {
    const container = document.getElementById('productsContainer');
    const emptyState = document.querySelector('.empty-state');

    if (!products || products.length === 0) {
        // Show empty state
        if (emptyState) {
            emptyState.style.display = 'block';
        }
        if (container) {
            container.innerHTML = '<div class="empty-state">No products found</div>';
        }
        return;
    }

    // Hide empty state
    if (emptyState) {
        emptyState.style.display = 'none';
    }

    // Render products in list layout
    const productsHtml = products.map(product => `
        <div class="product-list-item">
            <div class="product-list-image">
                <img src="${config.uploadUrl}/${product.product_image}" 
                     alt="${product.product_name}"
                     loading="lazy"
                     onerror="this.src='${config.imgUrl}/placeholder-product.png'">
                ${product.amc_available ? '<span class="product-badge">AMC</span>' : ''}
                <button class="wishlist-quick-btn" onclick="toggleWishlist(${product.id})">
                    <i class="far fa-heart"></i>
                </button>
            </div>
            <div class="product-list-details">
                <div class="product-list-header">
                    <span class="product-brand">${product.brand_name}</span>
                    <h3 class="product-title">
                        <a href="${config.userUrl || ''}/products/details.php?id=${product.id}&from=product">${product.product_name}</a>
                    </h3>
                    <div class="product-rating">
                        ${getStarRating(product.star_rating)}
                        <span class="rating-text">(${product.star_rating}/5)</span>
                    </div>
                </div>
                <div class="product-list-specs">
                    <span class="spec-item"><i class="fas fa-snowflake"></i> ${product.capacity}</span>
                    <span class="spec-item"><i class="fas fa-star"></i> ${product.star_rating} Star</span>
                    ${product.inverter === 'Yes' ? '<span class="spec-item"><i class="fas fa-bolt"></i> Inverter</span>' : ''}
                </div>
                <div class="product-list-description">
                    ${product.description ? product.description.substring(0, 150) + '...' : ''}
                </div>
                <div class="product-list-footer">
                    <div class="product-price-section">
                        <div class="product-price">₹${formatNumber(product.price)}</div>
                    </div>
                    <div class="product-actions">
                        <a href="${config.userUrl || ''}/products/details.php?id=${product.id}&from=product" class="btn-view-details">
                            <i class="fas fa-eye"></i> View Details
                        </a>
                    </div>
                </div>
            </div>
        </div>
    `).join('');
    
    if (container) {
        container.innerHTML = productsHtml;
    }
    
    // Update pagination
    updatePagination(totalPages, currentPage);
    
    // Update active filter chips
    updateActiveFilterChips();
}


/**
 * Get star rating HTML
 */
function getStarRating(rating) {
    let stars = '';
    for (let i = 1; i <= 5; i++) {
        stars += `<i class="fas fa-star${i <= rating ? '' : '-o'}"></i>`;
    }
    return stars;
}

// ============================================================================
// LOADING STATES
// ============================================================================

/**
 * Show loading state with skeleton
 */
function showLoadingState() {
    const productsSection = document.querySelector('.col-lg-9');
    const skeletonHtml = `
        <div class="skeleton-grid">
            ${Array(12).fill(0).map(() => `
                <div class="skeleton-card">
                    <div class="skeleton skeleton-image"></div>
                    <div class="skeleton skeleton-title" style="width: 80%;"></div>
                    <div class="skeleton skeleton-title" style="width: 60%;"></div>
                    <div class="skeleton-specs">
                        <div class="skeleton skeleton-spec"></div>
                        <div class="skeleton skeleton-spec"></div>
                        <div class="skeleton skeleton-spec"></div>
                    </div>
                    <div class="skeleton skeleton-price"></div>
                    <div class="skeleton skeleton-button"></div>
                </div>
            `).join('')}
        </div>
    `;

    if (productsSection) {
        productsSection.classList.add('products-loading');
        const existingGrid = productsSection.querySelector('.products-grid');
        if (existingGrid) {
            existingGrid.innerHTML = skeletonHtml;
        }
    }
}

/**
 * Hide loading state
 */
function hideLoadingState() {
    const productsSection = document.querySelector('.col-lg-9');
    if (productsSection) {
        productsSection.classList.remove('products-loading');
    }
}

// ============================================================================
// PAGINATION
// ============================================================================

/**
 * Update pagination controls
 */
function updatePagination(totalPages, currentPage) {
    const paginationWrapper = document.querySelector('.pagination-wrapper');
    if (!paginationWrapper || totalPages <= 1) return;

    const params = new URLSearchParams();
    Object.keys(currentFilters).forEach(key => {
        if (currentFilters[key] && currentFilters[key] !== '0' && currentFilters[key] !== '' && key !== 'page') {
            params.append(key, currentFilters[key]);
        }
    });

    let paginationHtml = '<ul class="pagination">';

    if (currentPage > 1) {
        paginationHtml += `<li><a href="#" onclick="loadProducts(${currentPage - 1}); return false;"><i class="fas fa-chevron-left"></i></a></li>`;
    }

    const startPage = Math.max(1, currentPage - 2);
    const endPage = Math.min(totalPages, currentPage + 2);

    if (startPage > 1) {
        paginationHtml += `<li><a href="#" onclick="loadProducts(1); return false;">1</a></li>`;
        if (startPage > 2) {
            paginationHtml += '<li class="disabled"><a>...</a></li>';
        }
    }

    for (let i = startPage; i <= endPage; i++) {
        paginationHtml += `<li class="${i == currentPage ? 'active' : ''}">
            <a href="#" onclick="loadProducts(${i}); return false;">${i}</a>
        </li>`;
    }

    if (endPage < totalPages) {
        if (endPage < totalPages - 1) {
            paginationHtml += '<li class="disabled"><a>...</a></li>';
        }
        paginationHtml += `<li><a href="#" onclick="loadProducts(${totalPages}); return false;">${totalPages}</a></li>`;
    }

    if (currentPage < totalPages) {
        paginationHtml += `<li><a href="#" onclick="loadProducts(${currentPage + 1}); return false;"><i class="fas fa-chevron-right"></i></a></li>`;
    }

    paginationHtml += '</ul>';

    paginationWrapper.innerHTML = paginationHtml;
}

/**
 * Update results info display
 */
function updateResultsInfo(totalProducts) {
    const resultsInfo = document.querySelector('.results-info');
    if (resultsInfo) {
        const hasFilters = Object.values(currentFilters).some(value =>
            value && value !== '0' && value !== '' && value !== '1'
        );
        resultsInfo.innerHTML = `Showing <strong>${totalProducts}</strong> product${totalProducts !== 1 ? 's' : ''}
            ${hasFilters ? '(filtered results)' : ''}`;
    }
}

// ============================================================================
// ACTIVE FILTERS MANAGEMENT
// ============================================================================

/**
 * Update active filters display
 */
function updateActiveFilters() {
    const activeFiltersContainer = document.querySelector('.active-filters-bar');
    if (!activeFiltersContainer) return;

    const activeFilters = [];

    if (currentFilters.category && currentFilters.category !== '0') {
        const categoryName = getCategoryName(currentFilters.category);
        if (categoryName) activeFilters.push({ label: `Category: ${categoryName}`, param: 'category' });
    }

    if (currentFilters.brand && currentFilters.brand !== '0') {
        const brandName = getBrandName(currentFilters.brand);
        if (brandName) activeFilters.push({ label: `Brand: ${brandName}`, param: 'brand' });
    }

    if (currentFilters.search) {
        activeFilters.push({ label: `Search: ${currentFilters.search}`, param: 'search' });
    }

    if ((currentFilters.min_price && currentFilters.min_price !== '0') || (currentFilters.max_price && currentFilters.max_price !== '0')) {
        let priceLabel = 'Price: ';
        if (currentFilters.min_price && currentFilters.min_price !== '0') priceLabel += `₹${formatNumber(currentFilters.min_price)}`;
        priceLabel += ' - ';
        if (currentFilters.max_price && currentFilters.max_price !== '0') priceLabel += `₹${formatNumber(currentFilters.max_price)}`;
        activeFilters.push({ label: priceLabel, param: 'price' });
    }

    if (currentFilters.inverter && currentFilters.inverter !== '') {
        activeFilters.push({ label: `Type: ${currentFilters.inverter}`, param: 'inverter' });
    }

    if (currentFilters.star_rating && currentFilters.star_rating !== '0') {
        activeFilters.push({ label: `Rating: ${currentFilters.star_rating}+ Stars`, param: 'star_rating' });
    }

    if (activeFilters.length > 0) {
        activeFiltersContainer.style.display = 'block';
        activeFiltersContainer.querySelector('.active-filters-chips').innerHTML = activeFilters.map(filter => `
            <span class="filter-tag">
                ${filter.label}
                <i class="fas fa-times" onclick="removeFilter('${filter.param}')"></i>
            </span>
        `).join('');
    } else {
        activeFiltersContainer.style.display = 'none';
    }
}

/**
 * Get category name by ID
 */
function getCategoryName(id) {
    const category = config.categories.find(c => c.id == id);
    return category ? category.name : null;
}

/**
 * Get brand name by ID
 */
function getBrandName(id) {
    const brand = config.brands.find(b => b.id == id);
    return brand ? brand.name : null;
}

/**
 * Update browser history
 */
function updateBrowserHistory() {
    const params = new URLSearchParams();
    Object.keys(currentFilters).forEach(key => {
        if (currentFilters[key] && currentFilters[key] !== '0' && currentFilters[key] !== '' && key !== 'page') {
            params.append(key, currentFilters[key]);
        }
    });

    const newUrl = `${window.location.pathname}?${params.toString()}`;
    window.history.pushState(currentFilters, '', newUrl);
}

// ============================================================================
// EVENT HANDLERS
// ============================================================================

/**
 * Apply sorting
 */
function applySort(sortValue) {
    currentFilters.sort = sortValue;
    currentFilters.page = 1;
    
    // Update URL parameters
    updateURLFromFilters();
    
    loadProducts(1);
}

/**
 * Remove specific filter
 */
function removeFilter(filterParam) {
    if (filterParam === 'category') {
        currentFilters.category = '0';
        currentFilters.subcategory = '0';
        // Hide subcategory group
        const subcategoryGroup = document.getElementById('subcategoryGroup');
        if (subcategoryGroup) {
            subcategoryGroup.style.display = 'none';
        }
    } else if (filterParam === 'subcategory') {
        currentFilters.subcategory = '0';
    } else if (filterParam === 'brand') {
        currentFilters.brand = '0';
    } else if (filterParam === 'search') {
        currentFilters.search = '';
        // Clear search input
        const searchInput = document.querySelector('.search-input');
        if (searchInput) {
            searchInput.value = '';
            toggleSearchClear();
        }
    } else if (filterParam === 'price') {
        currentFilters.min_price = '';
        currentFilters.max_price = '';
        // Reset price inputs and slider
        const minPriceInput = document.querySelector('input[name="min_price"]');
        const maxPriceInput = document.querySelector('input[name="max_price"]');
        const priceSlider = document.getElementById('priceSlider');
        if (minPriceInput) minPriceInput.value = '';
        if (maxPriceInput) maxPriceInput.value = '';
        if (priceSlider) {
            priceSlider.value = priceSlider.max;
        }
    } else if (filterParam === 'capacity') {
        currentFilters.capacity = '';
    } else if (filterParam === 'inverter') {
        currentFilters.inverter = '';
    } else if (filterParam === 'star_rating') {
        currentFilters.star_rating = '0';
    } else if (filterParam === 'warranty') {
        currentFilters.warranty = '0';
    } else if (filterParam === 'amc') {
        currentFilters.amc = '';
    } else if (filterParam === 'stock') {
        currentFilters.stock = '';
    } else if (filterParam === 'feature') {
        currentFilters.feature = '0';
    }

    currentFilters.page = 1;
    loadProducts(1);
}

// ============================================================================
// QUICK VIEW MODAL - REMOVED AS REQUESTED
// ============================================================================

// ============================================================================
// WISHLIST FUNCTIONALITY
// ============================================================================

/**
 * Update wishlist count in header
 */
function updateWishlistCount() {
    if (!getUserId()) {
        console.log('User ID is not valid, skipping wishlist count update');
        return;
    }

    const requestData = {
        user_id: getUserId()
    };

    fetch(`${config.userUrl || '../'}/wishlist/ajax_wishlist_count.php`, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify(requestData)
    })
    .then(response => {
        if (!response.ok) {
            return response.text().then(text => {
                throw new Error(`HTTP ${response.status}: ${response.statusText} - ${text}`);
            });
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            // Update wishlist badge in header
            const wishlistBadge = document.querySelector('.dropdown-item[href*="wishlist"] .badge');
            if (wishlistBadge) {
                if (data.count > 0) {
                    wishlistBadge.textContent = data.count;
                    wishlistBadge.style.display = 'inline';
                } else {
                    wishlistBadge.style.display = 'none';
                }
            }
        } else {
            console.error('Wishlist count failed:', data.message);
        }
    })
    .catch(error => {
        console.error('Error updating wishlist count:', error);
        // Don't show notification for count updates to avoid spam
    });
}

/**
 * Toggle wishlist item with improved error handling and race condition prevention
 */
function toggleWishlist(productId) {
    if (!getUserId()) {
        showNotification('Please login to add items to your wishlist', 'error');
        return;
    }

    const button = event.target.closest('.wishlist-quick-btn, .btn-wishlist');
    
    // Prevent multiple clicks
    if (button.disabled) {
        return;
    }
    
    const icon = button.querySelector('i');

    // Show loading state
    button.disabled = true;
    const originalIcon = icon.className;
    const originalTitle = button.title;
    icon.className = 'fas fa-spinner fa-spin';
    button.title = 'Processing...';

    // Check current wishlist status
    checkWishlistStatus(productId).then(isInWishlist => {
        const action = isInWishlist ? 'remove' : 'add';

        // Perform the wishlist action
        return performWishlistAction(productId, action);
    }).then(result => {
        if (result) {
            // Update UI based on action
            if (result.action === 'added') {
                icon.className = 'fas fa-heart';
                button.classList.add('active');
                button.title = 'Remove from Wishlist';
            } else if (result.action === 'removed') {
                icon.className = 'far fa-heart';
                button.classList.remove('active');
                button.title = 'Add to Wishlist';
            }

            showNotification(result.message, 'success');

            // Update wishlist count in header
            updateWishlistCount();
        }
    }).catch(error => {
        console.error('Wishlist error:', error);

        let errorMessage = 'An error occurred with your wishlist';
        if (error.message) {
            errorMessage += ': ' + error.message;
        } else if (error.status) {
            errorMessage += ' (HTTP ' + error.status + ')';
        }

        showNotification(errorMessage, 'error');
        
        // Reset to original state on error
        icon.className = originalIcon;
        button.title = originalTitle;
    }).finally(() => {
        // Reset button state
        button.disabled = false;
    });
}

/**
 * Check wishlist status
 */
function checkWishlistStatus(productId) {
    return fetch('../user/products/ajax_wishlist.php', {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({
            action: 'check',
            product_id: productId
        })
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('HTTP ' + response.status + ': ' + response.statusText);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            return data.in_wishlist;
        }
        throw new Error(data.message || 'Error checking wishlist status');
    });
}

/**
 * Perform wishlist action
 */
function performWishlistAction(productId, action) {
    return fetch('../user/products/ajax_wishlist.php', {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({
            action: action,
            product_id: productId
        })
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('HTTP ' + response.status + ': ' + response.statusText);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            return data;
        }
        throw new Error(data.message || 'Error updating wishlist');
    });
}

/**
 * Update wishlist buttons on page load
 */
function updateWishlistButtons() {
    if (!getUserId()) return;

    const wishlistButtons = document.querySelectorAll('.btn-wishlist');
    wishlistButtons.forEach(button => {
        const productId = button.onclick.toString().match(/toggleWishlist\((\d+)\)/)[1];
        checkWishlistStatus(productId).then(isInWishlist => {
            const icon = button.querySelector('i');
            if (isInWishlist) {
                icon.className = 'fas fa-heart';
                button.classList.add('active');
                button.title = 'Remove from Wishlist';
            } else {
                icon.className = 'far fa-heart';
                button.classList.remove('active');
                button.title = 'Add to Wishlist';
            }
        }).catch(error => {
            console.error('Error updating wishlist button:', error);
        });
    });
}

// ============================================================================
// CART FUNCTIONALITY
// ============================================================================

/**
 * Add to cart functionality (AJAX)
 */
function addToCartQuick(productId, quantity) {
    if (!getUserId()) {
        showNotification('Please login to add items to your cart', 'error');
        return;
    }

    // Show loading state
    const button = event.target.closest('.btn-add-to-cart, .btn-add-cart-quick');
    const originalText = button.innerHTML;
    button.innerHTML = '<i class="fas fa-spinner"></i> Adding...';
    button.classList.add('loading');
    button.disabled = true;

    // Create form data
    const formData = new FormData();
    formData.append('product_id', productId);
    formData.append('quantity', quantity);
    formData.append('action', 'add_to_cart');

    // Send AJAX request
    const cartUrl = config.userUrl ? `${config.userUrl}/cart/add.php` : '../cart/add.php';
    console.log('Making cart request to:', cartUrl);
    console.log('Form data:', {
        product_id: productId,
        quantity: quantity,
        action: 'add_to_cart'
    });
    
    fetch(cartUrl, {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        credentials: 'same-origin'
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        // Check if response is JSON
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            // Get the actual response text to see what's being returned
            return response.text().then(text => {
                console.error('Server returned non-JSON response:');
                console.error('Status:', response.status);
                console.error('Content-Type:', contentType);
                console.error('Response body:', text);
                throw new Error('Server returned non-JSON response');
            });
        }
        
        return response.json();
    })
    .then(data => {
        // Reset button state
        button.innerHTML = originalText;
        button.classList.remove('loading');
        button.disabled = false;

        if (data.success) {
            // Show success notification
            showNotification(data.message || 'Product added to cart successfully!', 'success');

            // Update cart count in header if function exists
            if (typeof window.updateCartCount === 'function') {
                window.updateCartCount();
            }
        } else {
            // Show error notification
            showNotification(data.message || 'Failed to add product to cart', 'error');
        }
    })
    .catch(error => {
        // Reset button state
        button.innerHTML = originalText;
        button.classList.remove('loading');
        button.disabled = false;

        console.error('Cart AJAX Error:', error);
        console.error('Error message:', error.message);
        console.error('Error stack:', error.stack);
        
        // Show user-friendly error message
        let errorMessage = 'An error occurred while adding to cart';
        if (error.message.includes('non-JSON')) {
            errorMessage = 'Server error - please try again';
        } else if (error.message.includes('HTTP error')) {
            errorMessage = 'Network error - please check your connection';
        } else if (error.message.includes('Failed to fetch')) {
            errorMessage = 'Network error - please check your connection';
        }
        
        showNotification(errorMessage, 'error');
    });
}

// ============================================================================
// INITIALIZATION
// ============================================================================

/**
 * Initialize the products page
 */
function initializeProductsPage() {
    // Initialize filters from URL parameters
    initializeFiltersFromURL();
    
    // Only load products via AJAX if there are active filters (excluding sort and page)
    const hasActiveFilters = Object.keys(currentFilters).some(key => {
        const value = currentFilters[key];
        return key !== 'sort' && key !== 'page' && value && value !== '0' && value !== '' && value !== '1';
    });
    
    if (hasActiveFilters) {
        loadProducts(parseInt(currentFilters.page), false);
    }

    // Initialize new functionality
    initializePriceSlider();
    
    // Set up search input event listeners
    const searchInput = document.querySelector('.search-input');
    if (searchInput) {
        searchInput.addEventListener('input', toggleSearchClear);
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                const filterForm = document.getElementById('filterForm');
                if (filterForm) {
                    filterForm.dispatchEvent(new Event('submit'));
                }
            }
        });
    }

    // Update wishlist buttons and count
    updateWishlistButtons();
    updateWishlistCount();

    // Handle browser back/forward buttons
    window.addEventListener('popstate', function(event) {
        if (event.state) {
            currentFilters = event.state;
            loadProducts(parseInt(currentFilters.page), false);
        }
    });

    // Show notification if coming from action
    const urlParams = new URLSearchParams(window.location.search);
    const message = urlParams.get('message');
    
    if (message) {
        showNotification(decodeURIComponent(message), 'success');
        
        // Clean URL
        const cleanUrl = window.location.pathname + '?' + 
            Array.from(urlParams.entries())
                .filter(([key]) => key !== 'message')
                .map(([key, val]) => `${key}=${val}`)
                .join('&');
        window.history.replaceState({}, document.title, cleanUrl);
    }
    
    // Smooth scroll for back to top
    window.addEventListener('scroll', function() {
        const scrollButton = document.getElementById('back-to-top');
        if (scrollButton) {
            if (window.pageYOffset > 300) {
                scrollButton.classList.add('show');
            } else {
                scrollButton.classList.remove('show');
            }
        }
    });

    // Add loading state to filter form submission
    const filterForm = document.getElementById('filterForm');
    if (filterForm) {
        filterForm.addEventListener('submit', function(e) {
            e.preventDefault();

            // Update current filters from form
            const formData = new FormData(filterForm);
            currentFilters.category = formData.get('category') || '0';
            currentFilters.subcategory = formData.get('subcategory') || '0';
            currentFilters.brand = formData.get('brand') || '0';
            currentFilters.search = formData.get('search') || '';
            currentFilters.min_price = formData.get('min_price') || '';
            currentFilters.max_price = formData.get('max_price') || '';
            currentFilters.capacity = formData.get('capacity') || '';
            currentFilters.inverter = formData.get('inverter') || '';
            currentFilters.star_rating = formData.get('star_rating') || '0';
            currentFilters.warranty = formData.get('warranty') || '0';
            currentFilters.amc = formData.get('amc') || '';
            currentFilters.stock = formData.get('stock') || '';
            currentFilters.feature = formData.get('feature') || '0';
            currentFilters.page = 1;

            loadProducts(1);
        });
    }
}

// ============================================================================
// EVENT LISTENERS
// ============================================================================

// Initialize filter count on page load
document.addEventListener('DOMContentLoaded', function() {
    updateFilterCount();
    
    // Add event listeners to update count
    const allInputs = document.querySelectorAll('.filter-group input, .filter-group select');
    allInputs.forEach(input => {
        input.addEventListener('change', updateFilterCount);
        input.addEventListener('input', updateFilterCount);
    });
});

// Close modal on escape key - Quick View removed
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        // Quick View functionality removed
    }
});

// Initialize the page when DOM is ready
document.addEventListener('DOMContentLoaded', initializeProductsPage);

// ================= NEW DROPDOWN FILTERS & LIST LAYOUT FUNCTIONS =================

// Filter Dropdown Management
function toggleFilterDropdown(filterId) {
    const dropdown = document.getElementById(filterId);
    const allDropdowns = document.querySelectorAll('.filter-dropdown');
    
    allDropdowns.forEach(d => {
        if (d.id !== filterId) {
            d.classList.remove('active');
        }
    });
    
    dropdown.classList.toggle('active');
    
    // Adjust dropdown position if it's active
    if (dropdown.classList.contains('active')) {
        adjustDropdownPosition(dropdown);
    }
}

// Adjust dropdown position to prevent overflow
function adjustDropdownPosition(dropdown) {
    const content = dropdown.querySelector('.filter-dropdown-content');
    if (!content) return;
    
    const rect = dropdown.getBoundingClientRect();
    const contentRect = content.getBoundingClientRect();
    const viewportWidth = window.innerWidth;
    const viewportHeight = window.innerHeight;
    
    // Reset positioning
    content.style.left = '0';
    content.style.right = 'auto';
    content.style.transform = 'none';
    
    // Check if dropdown overflows to the right
    if (rect.right + contentRect.width > viewportWidth - 20) {
        content.style.left = 'auto';
        content.style.right = '0';
    }
    
    // Check if dropdown overflows to the bottom
    if (rect.bottom + contentRect.height > viewportHeight - 20) {
        content.style.top = 'auto';
        content.style.bottom = 'calc(100% + 8px)';
    }
}

// Close dropdowns when clicking outside
document.addEventListener('click', function(e) {
    if (!e.target.closest('.filter-dropdown')) {
        document.querySelectorAll('.filter-dropdown').forEach(d => {
            d.classList.remove('active');
        });
    }
});

// Adjust dropdown positions on window resize
window.addEventListener('resize', function() {
    document.querySelectorAll('.filter-dropdown.active').forEach(dropdown => {
        adjustDropdownPosition(dropdown);
    });
});

// View Toggle (List/Grid)
function switchView(viewType) {
    const container = document.getElementById('productsContainer');
    const buttons = document.querySelectorAll('.view-btn');
    
    buttons.forEach(btn => {
        btn.classList.toggle('active', btn.dataset.view === viewType);
    });
    
    if (viewType === 'grid') {
        container.classList.remove('products-list-container');
        container.classList.add('products-grid-container');
    } else {
        container.classList.remove('products-grid-container');
        container.classList.add('products-list-container');
    }
    
    // Save preference
    localStorage.setItem('productViewPreference', viewType);
}

// Load saved view preference
document.addEventListener('DOMContentLoaded', function() {
    const savedView = localStorage.getItem('productViewPreference') || 'list';
    switchView(savedView);
});

// Active Filter Chips Management
function updateActiveFilterChips() {
    const chipsContainer = document.getElementById('activeFiltersChips');
    const filtersBar = document.getElementById('activeFiltersBar');
    const activeFilters = [];
    
    // Collect active filters
    Object.keys(currentFilters).forEach(key => {
        if (currentFilters[key] && currentFilters[key] !== '0' && currentFilters[key] !== '' && key !== 'sort' && key !== 'page') {
            let label = '';
            switch(key) {
                case 'category':
                    label = 'Category: ' + getCategoryName(currentFilters[key]);
                    break;
                case 'brand':
                    label = 'Brand: ' + getBrandName(currentFilters[key]);
                    break;
                case 'search':
                    label = 'Search: ' + currentFilters[key];
                    break;
                case 'min_price':
                case 'max_price':
                    if (key === 'min_price' && !activeFilters.find(f => f.key === 'price')) {
                        label = 'Price: ₹' + formatNumber(currentFilters.min_price) + ' - ₹' + formatNumber(currentFilters.max_price || '∞');
                        activeFilters.push({ key: 'price', label: label });
                    }
                    return;
                default:
                    label = key + ': ' + currentFilters[key];
            }
            
            if (label) {
                activeFilters.push({ key: key, label: label });
            }
        }
    });
    
    if (activeFilters.length > 0) {
        chipsContainer.innerHTML = activeFilters.map(filter => `
            <span class="filter-chip" onclick="removeFilterChip('${filter.key}')">
                ${filter.label}
                <i class="fas fa-times"></i>
            </span>
        `).join('');
        filtersBar.style.display = 'flex';
    } else {
        filtersBar.style.display = 'none';
    }
}

function removeFilterChip(filterKey) {
    if (filterKey === 'price') {
        currentFilters.min_price = '';
        currentFilters.max_price = '';
    } else {
        currentFilters[filterKey] = '';
    }
    loadProducts(1);
    updateActiveFilterChips();
}

function clearAllFilters() {
    // Reset all filter inputs
    document.querySelectorAll('input[type="radio"]').forEach(input => {
        if (input.value === '0' || input.value === '') {
            input.checked = true;
        } else {
            input.checked = false;
        }
    });
    
    // Clear search input
    const searchInput = document.querySelector('input[name="search"]');
    if (searchInput) {
        searchInput.value = '';
        // Hide search clear button
        const clearBtn = document.querySelector('.search-clear');
        if (clearBtn) {
            clearBtn.style.display = 'none';
        }
    }
    
    // Clear price inputs
    const minPriceInput = document.querySelector('input[name="min_price"]');
    const maxPriceInput = document.querySelector('input[name="max_price"]');
    if (minPriceInput) minPriceInput.value = '';
    if (maxPriceInput) maxPriceInput.value = '';
    
    // Reset current filters
    currentFilters = {
        page: 1,
        sort: 'newest',
        category: '0',
        subcategory: '0',
        brand: '0',
        search: '',
        min_price: '',
        max_price: '',
        capacity: '',
        inverter: '',
        star_rating: '0',
        warranty: '0',
        amc: '',
        stock: '',
        feature: '0'
    };
    
    // Update URL to remove all parameters except sort=newest
    const newUrl = new URL(window.location);
    newUrl.search = '?sort=newest';
    window.history.replaceState({}, '', newUrl);
    
    // Reload products
    loadProducts(1);
    updateActiveFilterChips();
}

// Apply filter function
function applyFilter(filterType, value) {
    currentFilters[filterType] = value;
    currentFilters.page = 1;
    
    // Update URL parameters
    updateURLFromFilters();
    
    loadProducts(1);
    updateActiveFilterChips();
}

// Apply brand filter from brand card click
function applyBrandFilter(brandId) {
    applyFilter('brand', brandId);
    
    // Update the brand radio button in the filter dropdown
    const brandRadio = document.getElementById('brand_' + brandId);
    if (brandRadio) {
        brandRadio.checked = true;
    }
    
    // Scroll to products section
    const productsContainer = document.getElementById('productsContainer');
    if (productsContainer) {
        productsContainer.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
}

// Apply price filter
function applyPriceFilter() {
    const minPrice = document.querySelector('input[name="min_price"]').value;
    const maxPrice = document.querySelector('input[name="max_price"]').value;
    
    currentFilters.min_price = minPrice;
    currentFilters.max_price = maxPrice;
    currentFilters.page = 1;
    
    // Update URL parameters
    updateURLFromFilters();
    
    loadProducts(1);
    updateActiveFilterChips();
}

// Helper functions for filter chips

function formatNumber(num) {
    return new Intl.NumberFormat('en-IN').format(num);
}
