<?php
// Set page metadata
$pageTitle = 'Review Management';
$pageDescription = 'Manage customer reviews and ratings';
$pageKeywords = 'reviews, ratings, customer feedback, admin';

require_once __DIR__ . '/../../includes/config/init.php';
include INCLUDES_PATH . '/templates/admin_header.php';

// Check admin authentication
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// Handle review actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $review_id = intval($_POST['review_id']);
    $action = $_POST['action'];
    
    try {
        switch ($action) {
            case 'approve':
                $stmt = $pdo->prepare("UPDATE reviews SET status = 'approved' WHERE id = ?");
                $stmt->execute([$review_id]);
                $message = "Review approved successfully!";
                break;
                
            case 'reject':
                $stmt = $pdo->prepare("UPDATE reviews SET status = 'rejected' WHERE id = ?");
                $stmt->execute([$review_id]);
                $message = "Review rejected successfully!";
                break;
                
            case 'delete':
                $stmt = $pdo->prepare("DELETE FROM reviews WHERE id = ?");
                $stmt->execute([$review_id]);
                $message = "Review deleted successfully!";
                break;
                
            default:
                $error = "Invalid action!";
        }
    } catch (PDOException $e) {
        $error = "Error processing review: " . $e->getMessage();
    }
}

// Get filter parameters
$status_filter = $_GET['status'] ?? 'all';
$search = $_GET['search'] ?? '';

// Build query
$where_conditions = [];
$params = [];

if ($status_filter !== 'all') {
    $where_conditions[] = "r.status = ?";
    $params[] = $status_filter;
}

if (!empty($search)) {
    $where_conditions[] = "(p.product_name LIKE ? OR u.username LIKE ? OR r.review_text LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get reviews with pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

$sql = "SELECT r.*, p.product_name, u.username, u.email
        FROM reviews r
        JOIN products p ON r.product_id = p.id
        JOIN users u ON r.user_id = u.id
        $where_clause
        ORDER BY r.created_at DESC
        LIMIT $per_page OFFSET $offset";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get total count for pagination
$count_sql = "SELECT COUNT(*) FROM reviews r
              JOIN products p ON r.product_id = p.id
              JOIN users u ON r.user_id = u.id
              $where_clause";
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_reviews = $count_stmt->fetchColumn();
$total_pages = ceil($total_reviews / $per_page);

// Get review statistics
$stats_sql = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
    SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected,
    AVG(rating) as avg_rating
    FROM reviews";
$stats_stmt = $pdo->query($stats_sql);
$stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
?>

<style>
/* Review Management Styles */
.review-management {
    padding: 20px;
}

.stats-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    text-align: center;
}

.stat-number {
    font-size: 2em;
    font-weight: bold;
    color: #333;
}

.stat-label {
    color: #666;
    margin-top: 5px;
}

.filters {
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    margin-bottom: 20px;
}

.filter-group {
    display: flex;
    gap: 15px;
    align-items: center;
    flex-wrap: wrap;
}

.filter-group label {
    font-weight: 600;
    color: #333;
}

.filter-group select,
.filter-group input {
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
}

.btn-filter {
    background: #007bff;
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 4px;
    cursor: pointer;
}

.btn-filter:hover {
    background: #0056b3;
}

.reviews-table {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    overflow: hidden;
}

.table {
    width: 100%;
    border-collapse: collapse;
}

.table th,
.table td {
    padding: 15px;
    text-align: left;
    border-bottom: 1px solid #eee;
}

.table th {
    background: #f8f9fa;
    font-weight: 600;
    color: #333;
}

.review-content {
    max-width: 300px;
}

.review-text {
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
    line-height: 1.4;
}

.rating-stars {
    color: #ffc107;
    font-size: 16px;
}

.status-badge {
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
}

.status-pending {
    background: #fff3cd;
    color: #856404;
}

.status-approved {
    background: #d4edda;
    color: #155724;
}

.status-rejected {
    background: #f8d7da;
    color: #721c24;
}

.action-buttons {
    display: flex;
    gap: 5px;
}

.btn-action {
    padding: 6px 12px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 12px;
    font-weight: 600;
}

.btn-approve {
    background: #28a745;
    color: white;
}

.btn-reject {
    background: #dc3545;
    color: white;
}

.btn-delete {
    background: #6c757d;
    color: white;
}

.btn-action:hover {
    opacity: 0.8;
}

.pagination {
    display: flex;
    justify-content: center;
    gap: 5px;
    margin-top: 20px;
}

.pagination a,
.pagination span {
    padding: 8px 12px;
    border: 1px solid #ddd;
    text-decoration: none;
    color: #333;
    border-radius: 4px;
}

.pagination .current {
    background: #007bff;
    color: white;
    border-color: #007bff;
}

.pagination a:hover {
    background: #f8f9fa;
}

.alert {
    padding: 12px 16px;
    border-radius: 4px;
    margin-bottom: 20px;
}

.alert-success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.alert-danger {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

@media (max-width: 768px) {
    .filter-group {
        flex-direction: column;
        align-items: stretch;
    }
    
    .table {
        font-size: 14px;
    }
    
    .table th,
    .table td {
        padding: 10px 8px;
    }
    
    .action-buttons {
        flex-direction: column;
    }
}
</style>

<div class="review-management">
    <h1>Review Management</h1>
    
    <?php if (isset($message)): ?>
        <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    
    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    
    <!-- Statistics Cards -->
    <div class="stats-cards">
        <div class="stat-card">
            <div class="stat-number"><?= $stats['total'] ?></div>
            <div class="stat-label">Total Reviews</div>
        </div>
        <div class="stat-card">
            <div class="stat-number" style="color: #ffc107;"><?= $stats['pending'] ?></div>
            <div class="stat-label">Pending</div>
        </div>
        <div class="stat-card">
            <div class="stat-number" style="color: #28a745;"><?= $stats['approved'] ?></div>
            <div class="stat-label">Approved</div>
        </div>
        <div class="stat-card">
            <div class="stat-number" style="color: #dc3545;"><?= $stats['rejected'] ?></div>
            <div class="stat-label">Rejected</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?= number_format($stats['avg_rating'], 1) ?></div>
            <div class="stat-label">Avg Rating</div>
        </div>
    </div>
    
    <!-- Filters -->
    <div class="filters">
        <form method="GET" class="filter-group">
            <label for="status">Status:</label>
            <select name="status" id="status">
                <option value="all" <?= $status_filter === 'all' ? 'selected' : '' ?>>All Reviews</option>
                <option value="pending" <?= $status_filter === 'pending' ? 'selected' : '' ?>>Pending</option>
                <option value="approved" <?= $status_filter === 'approved' ? 'selected' : '' ?>>Approved</option>
                <option value="rejected" <?= $status_filter === 'rejected' ? 'selected' : '' ?>>Rejected</option>
            </select>
            
            <label for="search">Search:</label>
            <input type="text" name="search" id="search" placeholder="Product, user, or review text..." value="<?= htmlspecialchars($search) ?>">
            
            <button type="submit" class="btn-filter">Filter</button>
            <a href="index.php" class="btn-filter" style="text-decoration: none; display: inline-block;">Clear</a>
        </form>
    </div>
    
    <!-- Reviews Table -->
    <div class="reviews-table">
        <table class="table">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>User</th>
                    <th>Rating</th>
                    <th>Review</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($reviews)): ?>
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 40px; color: #666;">
                            No reviews found.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($reviews as $review): ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($review['product_name']) ?></strong>
                            </td>
                            <td>
                                <div><?= htmlspecialchars($review['username']) ?></div>
                                <small style="color: #666;"><?= htmlspecialchars($review['email']) ?></small>
                            </td>
                            <td>
                                <div class="rating-stars">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <?= $i <= $review['rating'] ? '★' : '☆' ?>
                                    <?php endfor; ?>
                                    <span style="margin-left: 5px; color: #333;"><?= $review['rating'] ?>/5</span>
                                </div>
                            </td>
                            <td class="review-content">
                                <?php if (!empty($review['review_title'])): ?>
                                    <div style="font-weight: 600; margin-bottom: 5px;">
                                        <?= htmlspecialchars($review['review_title']) ?>
                                    </div>
                                <?php endif; ?>
                                <div class="review-text">
                                    <?= htmlspecialchars($review['review_text']) ?>
                                </div>
                            </td>
                            <td>
                                <span class="status-badge status-<?= $review['status'] ?>">
                                    <?= ucfirst($review['status']) ?>
                                </span>
                            </td>
                            <td>
                                <?= date('M d, Y', strtotime($review['created_at'])) ?>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <?php if ($review['status'] === 'pending'): ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="review_id" value="<?= $review['id'] ?>">
                                            <input type="hidden" name="action" value="approve">
                                            <button type="submit" class="btn-action btn-approve" 
                                                    onclick="return confirm('Approve this review?')">
                                                Approve
                                            </button>
                                        </form>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="review_id" value="<?= $review['id'] ?>">
                                            <input type="hidden" name="action" value="reject">
                                            <button type="submit" class="btn-action btn-reject"
                                                    onclick="return confirm('Reject this review?')">
                                                Reject
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="review_id" value="<?= $review['id'] ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <button type="submit" class="btn-action btn-delete"
                                                onclick="return confirm('Delete this review permanently?')">
                                            Delete
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="?page=<?= $page - 1 ?>&status=<?= $status_filter ?>&search=<?= urlencode($search) ?>">« Previous</a>
            <?php endif; ?>
            
            <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                <?php if ($i === $page): ?>
                    <span class="current"><?= $i ?></span>
                <?php else: ?>
                    <a href="?page=<?= $i ?>&status=<?= $status_filter ?>&search=<?= urlencode($search) ?>"><?= $i ?></a>
                <?php endif; ?>
            <?php endfor; ?>
            
            <?php if ($page < $total_pages): ?>
                <a href="?page=<?= $page + 1 ?>&status=<?= $status_filter ?>&search=<?= urlencode($search) ?>">Next »</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<?php include INCLUDES_PATH . '/templates/admin_footer.php'; ?>
