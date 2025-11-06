<?php
require_once __DIR__ . '/../../includes/config/init.php';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Please log in to submit a review.']);
        exit;
    }

    $product_id = intval($_POST['product_id']);
    $user_id = $_SESSION['user_id'];
    $rating = intval($_POST['rating']);
    $review_text = trim($_POST['review_text']);
    $review_title = trim($_POST['review_title'] ?? '');

    // Validate input
    if ($rating < 1 || $rating > 5) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Rating must be between 1 and 5.']);
        exit;
    }

    if (empty($review_text) || strlen($review_text) < 10) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Review text must be at least 10 characters long.']);
        exit;
    }

    if (strlen($review_text) > 1000) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Review text must be less than 1000 characters.']);
        exit;
    }

    try {
        // Check if the product exists
        $product_check_stmt = $pdo->prepare("SELECT id FROM products WHERE id = ? AND status = 'active'");
        $product_check_stmt->execute([$product_id]);
        $product_exists = $product_check_stmt->fetch();

        if (!$product_exists) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Product not found or not available.']);
            exit;
        }

        // Check if user has already reviewed this product
        $existing_review_stmt = $pdo->prepare("SELECT id FROM reviews WHERE product_id = ? AND user_id = ?");
        $existing_review_stmt->execute([$product_id, $user_id]);
        $existing_review = $existing_review_stmt->fetch();

        if ($existing_review) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'You have already reviewed this product.']);
            exit;
        }

        // Insert review into the database
        $stmt = $pdo->prepare("INSERT INTO reviews (product_id, user_id, rating, review_title, review_text, status) 
                               VALUES (?, ?, ?, ?, ?, 'pending')");
        
        $stmt->execute([$product_id, $user_id, $rating, $review_title, $review_text]);

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Review submitted successfully! It will be published after approval.']);
        exit;
        
    } catch (PDOException $e) {
        error_log("Review submission error: " . $e->getMessage());
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Error submitting review. Please try again.']);
        exit;
    }
}

// Check if product ID is set
if (!isset($_GET['product'])) {
    echo "Product ID is missing.";
    exit; // Stop further processing
}

$product_id = intval($_GET['product']); // Get product ID from URL
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Review</title>
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/style.css">
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/bootstrap.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', Arial, sans-serif;
        }

        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .review-container {
            background: #fff;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 500px;
            position: relative;
        }

        .review-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #667eea, #764ba2);
            border-radius: 12px 12px 0 0;
        }

        h1 {
            text-align: center;
            margin-bottom: 30px;
            color: #333;
            font-size: 28px;
            font-weight: 600;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #555;
            font-size: 14px;
        }

        .rating-input {
            display: flex;
            gap: 5px;
            margin-bottom: 10px;
        }

        .star-input {
            display: none;
        }

        .star-label {
            font-size: 24px;
            color: #ddd;
            cursor: pointer;
            transition: color 0.2s;
        }

        .star-label:hover,
        .star-label.active {
            color: #ffc107;
        }

        input[type="text"],
        textarea {
            padding: 12px;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 16px;
            width: 100%;
            transition: border-color 0.3s;
        }

        input[type="text"]:focus,
        textarea:focus {
            outline: none;
            border-color: #667eea;
        }

        textarea {
            resize: vertical;
            min-height: 120px;
        }

        .char-count {
            text-align: right;
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }

        .btn-submit {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .btn-submit:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .alert {
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #667eea;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
        }

        .back-link:hover {
            text-decoration: underline;
        }

        .rating-description {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="review-container">
        <a href="<?= product_url($product_id) ?>" class="back-link">
            ← Back to Product
        </a>
        
        <h1>Write a Review</h1>
        
        <div id="alert-container"></div>
        
        <form id="reviewForm" method="POST" action="">
            <input type="hidden" name="product_id" value="<?= htmlspecialchars($product_id); ?>">

            <div class="form-group">
                <label for="rating">Rating *</label>
                <div class="rating-input">
                    <input type="radio" name="rating" value="1" id="star1" class="star-input" required>
                    <label for="star1" class="star-label">★</label>
                    
                    <input type="radio" name="rating" value="2" id="star2" class="star-input">
                    <label for="star2" class="star-label">★</label>
                    
                    <input type="radio" name="rating" value="3" id="star3" class="star-input">
                    <label for="star3" class="star-label">★</label>
                    
                    <input type="radio" name="rating" value="4" id="star4" class="star-input">
                    <label for="star4" class="star-label">★</label>
                    
                    <input type="radio" name="rating" value="5" id="star5" class="star-input">
                    <label for="star5" class="star-label">★</label>
                </div>
                <div class="rating-description" id="rating-description">Click a star to rate</div>
            </div>

            <div class="form-group">
                <label for="review_title">Review Title (Optional)</label>
                <input type="text" name="review_title" id="review_title" maxlength="200" placeholder="Summarize your experience...">
            </div>

            <div class="form-group">
                <label for="review_text">Your Review *</label>
                <textarea name="review_text" id="review_text" rows="5" placeholder="Share your experience with this product..." required minlength="10" maxlength="1000"></textarea>
                <div class="char-count">
                    <span id="char-count">0</span>/1000 characters
                </div>
            </div>

            <button type="submit" class="btn-submit" id="submitBtn">
                Submit Review
            </button>
        </form>
    </div>

    <script>
        // Star rating functionality
        const starInputs = document.querySelectorAll('.star-input');
        const starLabels = document.querySelectorAll('.star-label');
        const ratingDescription = document.getElementById('rating-description');
        const reviewText = document.getElementById('review_text');
        const charCount = document.getElementById('char-count');
        const submitBtn = document.getElementById('submitBtn');
        const form = document.getElementById('reviewForm');
        const alertContainer = document.getElementById('alert-container');

        const ratingDescriptions = {
            1: 'Poor - Not satisfied',
            2: 'Fair - Below expectations',
            3: 'Good - Met expectations',
            4: 'Very Good - Exceeded expectations',
            5: 'Excellent - Highly satisfied'
        };

        starInputs.forEach((input, index) => {
            input.addEventListener('change', function() {
                const rating = parseInt(this.value);
                
                // Update star display
                starLabels.forEach((label, labelIndex) => {
                    if (labelIndex < rating) {
                        label.classList.add('active');
                    } else {
                        label.classList.remove('active');
                    }
                });
                
                // Update description
                ratingDescription.textContent = ratingDescriptions[rating];
            });
        });

        // Character count
        reviewText.addEventListener('input', function() {
            const count = this.value.length;
            charCount.textContent = count;
            
            if (count > 1000) {
                charCount.style.color = '#dc3545';
            } else if (count > 800) {
                charCount.style.color = '#ffc107';
            } else {
                charCount.style.color = '#666';
            }
        });

        // Form submission
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            submitBtn.disabled = true;
            submitBtn.textContent = 'Submitting...';
            
            const formData = new FormData(this);
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert(data.message, 'success');
                    form.reset();
                    starLabels.forEach(label => label.classList.remove('active'));
                    ratingDescription.textContent = 'Click a star to rate';
                    charCount.textContent = '0';
                } else {
                    showAlert(data.message, 'danger');
                }
            })
            .catch(error => {
                showAlert('An error occurred. Please try again.', 'danger');
            })
            .finally(() => {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Submit Review';
            });
        });

        function showAlert(message, type) {
            alertContainer.innerHTML = `
                <div class="alert alert-${type}">
                    ${message}
                </div>
            `;
            
            // Auto-hide success messages
            if (type === 'success') {
                setTimeout(() => {
                    alertContainer.innerHTML = '';
                }, 5000);
            }
        }
    </script>
</body>
</html>

