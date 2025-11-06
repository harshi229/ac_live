<?php
// Set page metadata
$pageTitle = 'My Wishlist';
$pageDescription = 'View and manage your saved air conditioning products';
$pageKeywords = 'wishlist, favorites, saved products, AC wishlist';

require_once __DIR__ . '/../../includes/config/init.php';
include INCLUDES_PATH . '/templates/header.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    // Store current page URL for redirect after login
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    echo "<script>window.location.href='../auth/login.php';</script>";
    exit();
}

$user_id = $_SESSION['user_id'];

// Handle wishlist actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $product_id = intval($_POST['product_id']);

    if ($_POST['action'] === 'remove') {
        $delete_stmt = $pdo->prepare("DELETE FROM wishlist WHERE user_id = ? AND product_id = ?");
        $delete_stmt->execute([$user_id, $product_id]);
    } elseif ($_POST['action'] === 'add_to_cart') {
        // Check stock and add to cart
        $check_stmt = $pdo->prepare("SELECT stock, status FROM products WHERE id = ?");
        $check_stmt->execute([$product_id]);
        $product = $check_stmt->fetch(PDO::FETCH_ASSOC);

        if ($product && $product['status'] === 'active' && $product['stock'] > 0) {
            // Check if already in cart
            $cart_check = $pdo->prepare("SELECT quantity FROM cart WHERE user_id = ? AND product_id = ?");
            $cart_check->execute([$user_id, $product_id]);

            if ($cart_check->rowCount() > 0) {
                $existing = $cart_check->fetch(PDO::FETCH_ASSOC);
                $update_cart = $pdo->prepare("UPDATE cart SET quantity = quantity + 1 WHERE user_id = ? AND product_id = ?");
                $update_cart->execute([$user_id, $product_id]);
            } else {
                $add_to_cart = $pdo->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, 1)");
                $add_to_cart->execute([$user_id, $product_id]);
            }

            // Remove from wishlist after adding to cart
            $delete_stmt = $pdo->prepare("DELETE FROM wishlist WHERE user_id = ? AND product_id = ?");
            $delete_stmt->execute([$user_id, $product_id]);
        }
    }
}

// Fetch wishlist items with product details
$wishlist_query = $pdo->prepare("
    SELECT w.*, p.*, b.name as brand_name, c.name as category_name
    FROM wishlist w
    JOIN products p ON w.product_id = p.id
    LEFT JOIN brands b ON p.brand_id = b.id
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE w.user_id = ? AND p.status = 'active'
    ORDER BY w.created_at DESC
");
$wishlist_query->execute([$user_id]);
$wishlist_items = $wishlist_query->fetchAll(PDO::FETCH_ASSOC);
?>

<style>
/* Wishlist Page Styles */
.wishlist-page {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    min-height: 100vh;
    padding: 100px 0 40px;
}

.wishlist-header {
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
    color: white;
    padding: 60px 0;
    margin-bottom: 40px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
}

.wishlist-header h1 {
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 10px;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
}

.wishlist-header p {
    font-size: 1.1rem;
    opacity: 0.95;
}

.wishlist-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
}

.wishlist-stats {
    background: white;
    border-radius: 15px;
    padding: 25px;
    margin-bottom: 30px;
    box-shadow: 0 2px 15px rgba(0, 0, 0, 0.08);
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 20px;
}

.stat-item {
    text-align: center;
}

.stat-number {
    font-size: 2rem;
    font-weight: 700;
    color: #ef4444;
    display: block;
    margin-bottom: 5px;
}

.stat-label {
    color: #64748b;
    font-size: 0.9rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.wishlist-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 25px;
    margin-bottom: 40px;
}

.wishlist-item {
    background: white;
    border-radius: 15px;
    overflow: hidden;
    box-shadow: 0 2px 15px rgba(0, 0, 0, 0.08);
    transition: all 0.3s ease;
    position: relative;
}

.wishlist-item:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
}

.wishlist-item-image {
    position: relative;
    height: 200px;
    background: #f8f9fa;
    overflow: hidden;
}

.wishlist-item-image img {
    width: 100%;
    height: 100%;
    object-fit: contain;
    padding: 15px;
    transition: transform 0.3s ease;
}

.wishlist-item:hover .wishlist-item-image img {
    transform: scale(1.05);
}

.wishlist-badge {
    position: absolute;
    top: 10px;
    right: 10px;
    background: linear-gradient(135deg, #ef4444, #dc2626);
    color: white;
    padding: 5px 10px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
}

.wishlist-item-content {
    padding: 20px;
}

.wishlist-item-brand {
    color: #ef4444;
    font-size: 0.85rem;
    font-weight: 600;
    text-transform: uppercase;
    margin-bottom: 5px;
}

.wishlist-item-title {
    font-size: 1.1rem;
    font-weight: 600;
    color: #1e293b;
    margin-bottom: 10px;
    line-height: 1.4;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.wishlist-item-specs {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-bottom: 15px;
}

.spec-badge {
    background: #fef2f2;
    color: #dc2626;
    padding: 4px 10px;
    border-radius: 6px;
    font-size: 0.8rem;
    display: flex;
    align-items: center;
    gap: 5px;
    border: 1px solid #fecaca;
}

.wishlist-item-price {
    font-size: 1.5rem;
    font-weight: 700;
    color: #22c55e;
    margin-bottom: 15px;
}

.wishlist-item-actions {
    display: flex;
    gap: 10px;
}

.btn-wishlist-action {
    flex: 1;
    padding: 10px 15px;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    font-size: 0.9rem;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
    text-align: center;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.btn-view-product {
    background: linear-gradient(135deg, #3b82f6, #1d4ed8);
    color: white;
}

.btn-view-product:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
    color: white;
    text-decoration: none;
}

.btn-add-to-cart-wishlist {
    background: linear-gradient(135deg, #22c55e, #16a34a);
    color: white;
}

.btn-add-to-cart-wishlist:hover {
    background: linear-gradient(135deg, #16a34a, #15803d);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(34, 197, 94, 0.4);
    color: white;
    text-decoration: none;
}

.btn-remove-wishlist {
    background: linear-gradient(135deg, #ef4444, #dc2626);
    color: white;
}

.btn-remove-wishlist:hover {
    background: linear-gradient(135deg, #dc2626, #b91c1c);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(239, 68, 68, 0.4);
    color: white;
    text-decoration: none;
}

.empty-wishlist {
    text-align: center;
    padding: 80px 20px;
    background: white;
    border-radius: 15px;
    box-shadow: 0 2px 15px rgba(0, 0, 0, 0.08);
}

.empty-wishlist-icon {
    font-size: 5rem;
    color: #cbd5e1;
    margin-bottom: 30px;
}

.empty-wishlist h3 {
    color: #1e293b;
    margin-bottom: 15px;
    font-size: 1.8rem;
}

.empty-wishlist p {
    color: #64748b;
    margin-bottom: 30px;
    font-size: 1.1rem;
}

.btn-browse-products {
    background: linear-gradient(135deg, #ef4444, #dc2626);
    color: white;
    padding: 15px 30px;
    border: none;
    border-radius: 10px;
    font-weight: 600;
    font-size: 1rem;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 10px;
}

.btn-browse-products:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(239, 68, 68, 0.4);
    color: white;
    text-decoration: none;
}

/* Responsive Design */
@media (max-width: 768px) {
    .wishlist-header h1 {
        font-size: 2rem;
    }

    .wishlist-stats {
        flex-direction: column;
        text-align: center;
        gap: 15px;
    }

    .wishlist-grid {
        grid-template-columns: 1fr;
        gap: 20px;
    }

    .wishlist-item-actions {
        flex-direction: column;
    }

    .btn-wishlist-action {
        font-size: 0.85rem;
        padding: 12px 15px;
    }
}
</style>

<div class="wishlist-page">
    <!-- Page Header -->
    <div class="wishlist-header">
        <div class="container">
            <h1><i class="fas fa-heart me-2"></i>My Wishlist</h1>
            <p>Keep track of your favorite air conditioning products</p>
        </div>
    </div>

    <div class="wishlist-container">
        <?php if (empty($wishlist_items)): ?>
            <!-- Empty Wishlist -->
            <div class="empty-wishlist">
                <div class="empty-wishlist-icon">
                    <i class="far fa-heart"></i>
                </div>
                <h3>Your wishlist is empty</h3>
                <p>Start adding products you're interested in to your wishlist!</p>
                <a href="<?php echo USER_URL; ?>/products/" class="btn-browse-products">
                    <i class="fas fa-shopping-bag"></i> Browse Products
                </a>
            </div>
        <?php else: ?>
            <!-- Wishlist Stats -->
            <div class="wishlist-stats">
                <div class="stat-item">
                    <span class="stat-number"><?php echo count($wishlist_items); ?></span>
                    <span class="stat-label">Items in Wishlist</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number">
                        ₹<?php
                        $total_value = 0;
                        foreach ($wishlist_items as $item) {
                            $total_value += $item['price'];
                        }
                        echo number_format($total_value, 0);
                        ?>
                    </span>
                    <span class="stat-label">Total Value</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number">
                        <?php
                        $available_items = 0;
                        foreach ($wishlist_items as $item) {
                            if ($item['stock'] > 0) $available_items++;
                        }
                        echo $available_items;
                        ?>
                    </span>
                    <span class="stat-label">Available Items</span>
                </div>
            </div>

            <!-- Wishlist Items Grid -->
            <div class="wishlist-grid">
                <?php foreach ($wishlist_items as $item): ?>
                    <div class="wishlist-item">
                        <div class="wishlist-item-image">
                            <img src="<?php echo UPLOAD_URL; ?>/<?= urlencode($item['product_image']) ?>"
                                 alt="<?= htmlspecialchars($item['product_name']) ?>"
                                 onerror="this.src='<?php echo IMG_URL; ?>/placeholder-product.png'">
                            <div class="wishlist-badge">
                                <i class="fas fa-heart"></i> Wishlisted
                            </div>
                        </div>

                        <div class="wishlist-item-content">
                            <div class="wishlist-item-brand"><?= htmlspecialchars($item['brand_name']) ?></div>
                            <h3 class="wishlist-item-title"><?= htmlspecialchars($item['product_name']) ?></h3>

                            <div class="wishlist-item-specs">
                                <span class="spec-badge">
                                    <i class="fas fa-snowflake"></i>
                                    <?= htmlspecialchars($item['capacity']) ?>
                                </span>
                                <span class="spec-badge">
                                    <i class="fas fa-star"></i>
                                    <?= htmlspecialchars($item['star_rating']) ?> Star
                                </span>
                                <?php if ($item['inverter'] == 'Yes'): ?>
                                    <span class="spec-badge">
                                        <i class="fas fa-bolt"></i>
                                        Inverter
                                    </span>
                                <?php endif; ?>
                            </div>

                            <div class="wishlist-item-price">₹<?= number_format($item['price'], 0) ?></div>

                            <div class="wishlist-item-actions">
                                <a href="../products/details.php?id=<?= $item['product_id'] ?>" class="btn-wishlist-action btn-view-product">
                                    <i class="fas fa-eye"></i> View Product
                                </a>

                                <?php if ($item['stock'] > 0): ?>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="product_id" value="<?= $item['product_id'] ?>">
                                        <input type="hidden" name="action" value="add_to_cart">
                                        <button type="submit" class="btn-wishlist-action btn-add-to-cart-wishlist">
                                            <i class="fas fa-shopping-cart"></i> Add to Cart
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <button class="btn-wishlist-action" style="background: #94a3b8; cursor: not-allowed;" disabled>
                                        <i class="fas fa-times"></i> Out of Stock
                                    </button>
                                <?php endif; ?>

                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="product_id" value="<?= $item['product_id'] ?>">
                                    <input type="hidden" name="action" value="remove">
                                    <button type="submit" class="btn-wishlist-action btn-remove-wishlist" onclick="return confirm('Remove this item from your wishlist?')">
                                        <i class="fas fa-trash"></i> Remove
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
// Wishlist page functionality
document.addEventListener('DOMContentLoaded', function() {
    // Add loading states to forms
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function() {
            const submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn) {
                const originalText = submitBtn.innerHTML;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
                submitBtn.disabled = true;

                // Reset after 3 seconds (in case of error)
                setTimeout(() => {
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                }, 3000);
            }
        });
    });

    // Smooth scroll for anchor links
    const links = document.querySelectorAll('a[href^="#"]');
    links.forEach(link => {
        link.addEventListener('click', function(e) {
            const targetId = this.getAttribute('href').substring(1);
            const targetElement = document.getElementById(targetId);

            if (targetElement) {
                e.preventDefault();
                targetElement.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
});
</script>

<?php include INCLUDES_PATH . '/templates/footer.php'; ?>
