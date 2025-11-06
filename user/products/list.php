<?php
require_once __DIR__ . '/../../includes/config/init.php';
include INCLUDES_PATH . '/templates/header.php'; 
// Database connection now in init.php

try {
    if (isset($_GET['id'])) {
        // Fetch products based on subcategory
        $sql = "SELECT products.*, categories.name as category_name 
                FROM products 
                JOIN categories ON products.category_id = categories.id 
                WHERE s_category_id = :subcategory_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['subcategory_id' => $_GET['id']]);
        $products = $stmt->fetchAll();
    } elseif (isset($_GET['pid'])) {
        // Fetch products based on category
        $sql = "SELECT products.*, categories.name as category_name 
                FROM products 
                JOIN categories ON products.category_id = categories.id 
                WHERE category_id = :category_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['category_id' => $_GET['pid']]);
        $products = $stmt->fetchAll();
    }
} catch (PDOException $e) {
    // Handle error, log or display user-friendly message
    echo "Error fetching products: " . $e->getMessage();
}
?>

<style>
.sec-1 {
    background-color: #343a40; /* Dark background for card sections */
    color: #fff; /* White text for better contrast */
    padding: 20px; /* Add padding for better spacing */
    border-radius: 8px; /* Rounded corners for a softer look */
}
section
{
    margin-left: 45px;
}
.card {
    border: none;
    border-radius: 8px; /* Rounded corners for card */
    transition: transform 0.2s; /* Smooth scale effect on hover */
  
}

.card:hover {
    transform: scale(1.05); /* Slightly enlarge card on hover */
}

.wrapper {
    display: flex;
    flex-direction: column;
    min-height: 100vh; /* Ensure it covers full viewport height */
}

main {
    flex: 1; /* Allows the main content to grow and take available space */
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.container {
    width: 100%;
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
}

footer {
    background-color: #333;
    color: white;
    text-align: center;
    padding: 10px 0;
    position: relative;
    bottom: 0;
    width: 100%;
}

/* Conditionally set product image height */
.img-height-350 {
    height: 350px !important; /* Set the height for specific product id */
}

.img-fluid {
    max-width: 100%; /* Ensure images are responsive */
    height: auto; /* Maintain aspect ratio */
}

@media (max-width: 768px) {
    .col-lg-4, .col-md-6, .col-sm-12 {
        width: 100%; /* Stack cards on smaller screens */
    }
}

@media (min-width: 768px) {
    .card {
        margin: 15px; /* Space between cards on larger screens */
    }
}

</style>

<!-- Top Image -->

<!-- Main Content -->
<main class="container-fluid my-5">
    <section>
        <h2 class="text-center mb-4">Products</h2>
        <div class="row g-4">
            <!-- Display products dynamically -->
            <?php if (!empty($products)): ?>
                <?php foreach ($products as $product): ?>
                    <div class="col-lg-4 col-md-6 col-sm-12">
                        <div class="card h-100">
                            <!-- Conditionally apply the class to change image height for product id 2 -->
                            <img src="<?php echo UPLOAD_URL; ?>/<?php echo htmlspecialchars($product['product_image']); ?>" 
                                 class="sec-1 text-light <?php echo (isset($_GET['id']) && $_GET['id'] == 2) ? 'img-height-350' : ''; ?>" 
                                 alt="<?php echo htmlspecialchars($product['product_name'] ?? 'Product image'); ?>" 
                                 style="height: 200px; object-fit: cover;">
                            <div class="card-body sec-1">
                                <h2 class="card-title text-light"><?php echo htmlspecialchars($product['product_name']); ?></h2>
                                <p class="text-light"><?php echo htmlspecialchars($product['model_name']); ?></p>
                                <p class="card-text text-light">Price: $<?php echo number_format($product['price'], 2); ?></p>
                                <a href="<?= product_url($product['id']) ?>" class="btn btn-primary">View Details</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12">
                    <p class="text-center">No products found for this category or subcategory.</p>
                </div>
            <?php endif; ?>
        </div>
    </section>
</main>

<?php include INCLUDES_PATH . '/templates/footer.php'; ?>

