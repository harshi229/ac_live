<?php
require_once __DIR__ . '/../../includes/config/init.php';
include INCLUDES_PATH . '/templates/header.php';
// Database connection now in init.php

// Get the search query
$query = isset($_GET['query']) ? $_GET['query'] : '';

// Prepare SQL to search for products by name or category
$sql = "SELECT products.*, categories.name as category_name FROM products 
        JOIN categories ON products.category_id = categories.id 
        WHERE products.product_name LIKE :query OR categories.name LIKE :query";

// Prepare and execute the SQL statement
$stmt = $pdo->prepare($sql);
$stmt->execute([':query' => "%$query%"]);

// Fetch all matching products
$products = $stmt->fetchAll();
?>
    <style>
        .section
        {
            width: 1550px;
            height: 100vh;
        }
        .product-section {
            border: 1px solid #ccc;
            border-radius: 5px;
            text-align: center;
            padding: 15px;
            margin: 15px;
            transition: box-shadow 0.3s;   
        }
        .product-section:hover {
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }
        .product-image {
            max-width: 100%;
            height: auto;
        }
        .text-center
        {
            margin-top:50px;
        }
        .text-get_current_user{
            margin-top: 20px;
            color: #343a40;
            font-size: 18px;    
        }
        .btnn
        {
            color:white !important;
        }
    </style>

    <div class="section" style="background: linear-gradient(180deg, #1C232B 0%, #5E7691 20%);">
    <div class="container-fluid	">
        <h1 class="text-center">Search Results for "<?php echo htmlspecialchars($query); ?>"</h1>
        <div class="row">
            <?php if (count($products) > 0): ?>
                <?php foreach ($products as $product): ?>
                    <div class="col-md-4">
                        <div class="product-section">
                            <h3><?php echo htmlspecialchars($product['product_name']); ?></h3>
                            <img src="<?php echo UPLOAD_URL; ?>/<?php echo htmlspecialchars($product['product_image']); ?>" 
                                 alt="<?php echo htmlspecialchars($product['product_name']); ?>" 
                                 class="product-image">
                            <p>Price: $<?php echo number_format($product['price'], 2); ?></p>
                            <p>Category: <?php echo htmlspecialchars($product['category_name']); ?></p>
                            <p>
                                <a href="<?= product_url($product['id']) ?>" class="btnn">View Details</a>
                            </p>
                               
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12">
                    <p class="text-center">No products found matching your search.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    </div>
<?php
include INCLUDES_PATH . '/templates/footer.php';
?>

