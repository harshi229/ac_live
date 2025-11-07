<?php
/**
 * ProductQueryBuilder Class
 * Consolidates product query logic for filtering, sorting, and pagination
 */

class ProductQueryBuilder
{
    private $pdo;
    private $whereConditions = [];
    private $params = [];
    private $orderBy = "p.created_at DESC";
    private $limit = 12;
    private $offset = 0;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
        $this->whereConditions = ["p.status = 'active'"];
    }

    /**
     * Add filter for products to show on homepage
     * Only shows products where show_on_homepage = 1
     */
    public function addHomepageFilter()
    {
        $this->whereConditions[] = "p.show_on_homepage = 1";
        return $this;
    }

    /**
     * Add filter for products to show on product page
     * Only shows products where show_on_product_page = 1
     * Products with show_on_product_page = 0 or NULL will not be shown
     * If column doesn't exist, shows all products (backward compatibility)
     */
    public function addProductPageFilter()
    {
        // Check if column exists first, if not, don't add filter (for backward compatibility)
        try {
            $check_column = $this->pdo->query("SHOW COLUMNS FROM products LIKE 'show_on_product_page'");
            if ($check_column->rowCount() > 0) {
                // Column exists, filter by it
                // Only show products with show_on_product_page = 1 (explicitly checked)
                $this->whereConditions[] = "p.show_on_product_page = 1";
            } else {
                // Column doesn't exist yet - don't filter (show all products)
                error_log("show_on_product_page column not found - showing all products");
            }
        } catch (PDOException $e) {
            // Column doesn't exist or error checking - don't filter (show all products)
            error_log("Error checking show_on_product_page column: " . $e->getMessage() . " - showing all products");
        }
        return $this;
    }

    /**
     * Add category filter
     */
    public function addCategoryFilter($categoryId)
    {
        if ($categoryId > 0) {
            $this->whereConditions[] = "p.category_id = :category_id";
            $this->params[':category_id'] = $categoryId;
        }
        return $this;
    }

    /**
     * Add brand filter
     */
    public function addBrandFilter($brandId)
    {
        if ($brandId > 0) {
            $this->whereConditions[] = "p.brand_id = :brand_id";
            $this->params[':brand_id'] = $brandId;
        }
        return $this;
    }

    /**
     * Add subcategory filter
     */
    public function addSubcategoryFilter($subcategoryId)
    {
        if ($subcategoryId > 0) {
            $this->whereConditions[] = "p.sub_category_id = :subcategory_id";
            $this->params[':subcategory_id'] = $subcategoryId;
        }
        return $this;
    }

    /**
     * Add search filter
     */
    public function addSearchFilter($search)
    {
        if (!empty($search)) {
            $this->whereConditions[] = "(p.product_name LIKE :search1 OR p.model_name LIKE :search2 OR p.model_number LIKE :search3 OR p.description LIKE :search4)";
            $this->params[':search1'] = "%$search%";
            $this->params[':search2'] = "%$search%";
            $this->params[':search3'] = "%$search%";
            $this->params[':search4'] = "%$search%";
        }
        return $this;
    }

    /**
     * Add price range filter
     */
    public function addPriceFilter($minPrice, $maxPrice)
    {
        if ($minPrice > 0) {
            $this->whereConditions[] = "p.price >= :min_price";
            $this->params[':min_price'] = $minPrice;
        }

        if ($maxPrice > 0) {
            $this->whereConditions[] = "p.price <= :max_price";
            $this->params[':max_price'] = $maxPrice;
        }
        return $this;
    }

    /**
     * Add inverter filter
     */
    public function addInverterFilter($inverterFilter)
    {
        if ($inverterFilter && in_array($inverterFilter, ['Yes', 'No'])) {
            $this->whereConditions[] = "p.inverter = :inverter";
            $this->params[':inverter'] = $inverterFilter;
        }
        return $this;
    }

    /**
     * Add star rating filter
     */
    public function addStarRatingFilter($starRating)
    {
        if ($starRating > 0) {
            $this->whereConditions[] = "p.star_rating >= :star_rating";
            $this->params[':star_rating'] = $starRating;
        }
        return $this;
    }

    /**
     * Add capacity filter
     */
    public function addCapacityFilter($capacityFilter)
    {
        if ($capacityFilter && in_array($capacityFilter, ['0.5 Ton', '1 Ton', '1.5 Ton', '2 Ton', '3 Ton', '4 Ton', '5 Ton'])) {
            $this->whereConditions[] = "p.capacity = :capacity";
            $this->params[':capacity'] = $capacityFilter;
        }
        return $this;
    }

    /**
     * Add warranty filter
     */
    public function addWarrantyFilter($warrantyFilter)
    {
        if ($warrantyFilter > 0) {
            $this->whereConditions[] = "p.warranty_years >= :warranty";
            $this->params[':warranty'] = $warrantyFilter;
        }
        return $this;
    }

    /**
     * Add AMC filter
     */
    public function addAmcFilter($amcFilter)
    {
        if ($amcFilter && in_array($amcFilter, ['Yes', 'No'])) {
            $this->whereConditions[] = "p.amc_available = :amc";
            $this->params[':amc'] = ($amcFilter == 'Yes') ? 1 : 0;
        }
        return $this;
    }

    /**
     * Add stock filter
     */
    public function addStockFilter($stockFilter)
    {
        if ($stockFilter && in_array($stockFilter, ['in_stock', 'low_stock', 'out_of_stock'])) {
            switch ($stockFilter) {
                case 'in_stock':
                    $this->whereConditions[] = "p.stock > 10";
                    break;
                case 'low_stock':
                    $this->whereConditions[] = "p.stock > 0 AND p.stock <= 10";
                    break;
                case 'out_of_stock':
                    $this->whereConditions[] = "p.stock = 0";
                    break;
            }
        }
        return $this;
    }

    /**
     * Add feature filter
     */
    public function addFeatureFilter($featureFilter)
    {
        if ($featureFilter > 0) {
            $this->whereConditions[] = "EXISTS (SELECT 1 FROM product_features pf WHERE pf.product_id = p.id AND pf.feature_id = :feature)";
            $this->params[':feature'] = $featureFilter;
        }
        return $this;
    }

    /**
     * Set sorting
     */
    public function setSorting($sort)
    {
        switch ($sort) {
            case 'price_low':
                $this->orderBy = "p.price ASC";
                break;
            case 'price_high':
                $this->orderBy = "p.price DESC";
                break;
            case 'name_asc':
                $this->orderBy = "p.product_name ASC";
                break;
            case 'name_desc':
                $this->orderBy = "p.product_name DESC";
                break;
            case 'rating':
                $this->orderBy = "p.star_rating DESC";
                break;
            case 'newest':
            default:
                $this->orderBy = "p.created_at DESC";
                break;
        }
        return $this;
    }

    /**
     * Set pagination
     */
    public function setPagination($page, $itemsPerPage = 12)
    {
        $this->limit = $itemsPerPage;
        $this->offset = ($page - 1) * $itemsPerPage;
        return $this;
    }

    /**
     * Get total count of products matching current filters
     */
    public function getTotalCount()
    {
        $whereClause = implode(' AND ', $this->whereConditions);
        
        $countSql = "SELECT COUNT(DISTINCT p.id) as total 
                    FROM products p
                    LEFT JOIN brands b ON p.brand_id = b.id
                    LEFT JOIN categories c ON p.category_id = c.id
                    LEFT JOIN sub_categories sc ON p.sub_category_id = sc.id
                    WHERE $whereClause";
        
        $countStmt = $this->pdo->prepare($countSql);
        $countStmt->execute($this->params);
        return $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
    }

    /**
     * Get products with current filters
     */
    public function getProducts()
    {
        $whereClause = implode(' AND ', $this->whereConditions);
        
        $sql = "SELECT p.*, 
                       b.name as brand_name,
                       c.name as category_name,
                       sc.name as subcategory_name
                FROM products p
                LEFT JOIN brands b ON p.brand_id = b.id
                LEFT JOIN categories c ON p.category_id = c.id
                LEFT JOIN sub_categories sc ON p.sub_category_id = sc.id
                WHERE $whereClause
                ORDER BY $this->orderBy
                LIMIT :limit OFFSET :offset";
        
        $stmt = $this->pdo->prepare($sql);
        
        // Bind all parameters except limit and offset
        foreach ($this->params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        // Bind limit and offset separately
        $stmt->bindValue(':limit', $this->limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $this->offset, PDO::PARAM_INT);
        
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get filter options (categories, brands, etc.)
     */
    public function getFilterOptions()
    {
        return [
            'categories' => $this->pdo->query("SELECT * FROM categories WHERE status = 'active' ORDER BY name")->fetchAll(PDO::FETCH_ASSOC),
            'brands' => $this->pdo->query("SELECT * FROM brands WHERE status = 'active' ORDER BY name")->fetchAll(PDO::FETCH_ASSOC),
            'subcategories' => $this->pdo->query("SELECT * FROM sub_categories WHERE status = 'active' ORDER BY name")->fetchAll(PDO::FETCH_ASSOC),
            'features' => $this->pdo->query("SELECT * FROM features ORDER BY name")->fetchAll(PDO::FETCH_ASSOC),
            'capacities' => $this->pdo->query("SELECT DISTINCT capacity FROM products WHERE status = 'active' AND capacity IS NOT NULL ORDER BY capacity")->fetchAll(PDO::FETCH_COLUMN),
            'warranties' => $this->pdo->query("SELECT DISTINCT warranty_years FROM products WHERE status = 'active' AND warranty_years IS NOT NULL ORDER BY warranty_years")->fetchAll(PDO::FETCH_COLUMN),
            'price_range' => $this->pdo->query("SELECT MIN(price) as min_price, MAX(price) as max_price FROM products WHERE status = 'active'")->fetch(PDO::FETCH_ASSOC)
        ];
    }

    /**
     * Build query from filter array (convenience method)
     */
    public function buildFromFilters($filters)
    {
        // Reset the query builder first to clear any previous parameters
        $this->reset();
        
        $this->addCategoryFilter($filters['category_id'] ?? 0);
        $this->addBrandFilter($filters['brand_id'] ?? 0);
        $this->addSubcategoryFilter($filters['subcategory_id'] ?? 0);
        $this->addSearchFilter($filters['search'] ?? '');
        $this->addPriceFilter($filters['min_price'] ?? 0, $filters['max_price'] ?? 0);
        $this->addInverterFilter($filters['inverter_filter'] ?? '');
        $this->addStarRatingFilter($filters['star_rating'] ?? 0);
        $this->addCapacityFilter($filters['capacity_filter'] ?? '');
        $this->addWarrantyFilter($filters['warranty_filter'] ?? 0);
        $this->addAmcFilter($filters['amc_filter'] ?? '');
        $this->addStockFilter($filters['stock_filter'] ?? '');
        $this->addFeatureFilter($filters['feature_filter'] ?? 0);
        $this->setSorting($filters['sort'] ?? 'newest');
        $this->setPagination($filters['page'] ?? 1, $filters['items_per_page'] ?? 12);
        
        return $this;
    }

    /**
     * Reset the query builder
     */
    public function reset()
    {
        $this->whereConditions = ["p.status = 'active'"];
        $this->params = [];
        $this->orderBy = "p.created_at DESC";
        $this->limit = 12;
        $this->offset = 0;
        return $this;
    }
}
