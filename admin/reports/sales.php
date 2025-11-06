<?php
require_once __DIR__ . '/../../includes/config/init.php';

// Redirect if admin not logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: ' . admin_url('login'));
    exit();
}

include INCLUDES_PATH . '/templates/admin_header.php';
// Handle filters
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date   = $_GET['end_date'] ?? date('Y-m-t');

// Query sales data
$query = $pdo->prepare("
    SELECT 
        o.id AS order_id,
        o.order_number,
        u.username,
        u.phone_number,
        p.product_name,
        oi.quantity,
        oi.unit_price,
        oi.total_price,
        o.payment_method,
        o.order_status,
        o.payment_status,
        o.created_at
    FROM orders o
    JOIN users u ON o.user_id = u.id
    JOIN order_items oi ON o.id = oi.order_id
    JOIN products p ON oi.product_id = p.id
    WHERE DATE(o.created_at) BETWEEN :start_date AND :end_date
    ORDER BY o.created_at DESC
");
$query->execute([
    ':start_date' => $start_date,
    ':end_date'   => $end_date
]);
$sales = $query->fetchAll(PDO::FETCH_ASSOC);

// Summary totals
$summary = $pdo->prepare("
    SELECT 
        COUNT(DISTINCT o.id) AS total_orders,
        SUM(oi.quantity) AS total_items,
        SUM(oi.total_price) AS total_revenue
    FROM orders o
    JOIN order_items oi ON o.id = oi.order_id
    WHERE DATE(o.created_at) BETWEEN :start_date AND :end_date
");
$summary->execute([
    ':start_date' => $start_date,
    ':end_date'   => $end_date
]);
$totals = $summary->fetch(PDO::FETCH_ASSOC);
?>
<main class="container mt-4">
    <h2 class="mb-4">📊 Sales Report</h2>

    <!-- Filter Form -->
    <form class="row g-3 mb-4" method="GET">
        <div class="col-md-4">
            <label class="form-label">Start Date</label>
            <input type="date" class="form-control" name="start_date" value="<?= htmlspecialchars($start_date) ?>">
        </div>
        <div class="col-md-4">
            <label class="form-label">End Date</label>
            <input type="date" class="form-control" name="end_date" value="<?= htmlspecialchars($end_date) ?>">
        </div>
        <div class="col-md-4 align-self-end">
            <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Filter</button>
            <a href="sales_report.php" class="btn btn-secondary">Reset</a>
        </div>
    </form>

    <!-- Summary -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card text-center">
                <div class="card-body">
                    <h5>Total Orders</h5>
                    <h3><?= $totals['total_orders'] ?? 0 ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-center">
                <div class="card-body">
                    <h5>Total Items Sold</h5>
                    <h3><?= $totals['total_items'] ?? 0 ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-center">
                <div class="card-body">
                    <h5>Total Revenue</h5>
                    <h3>₹<?= number_format($totals['total_revenue'] ?? 0, 2) ?></h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Detailed Table -->
    <table class="table table-bordered table-striped">
        <thead class="table-dark">
            <tr>
                <th>Order #</th>
                <th>Customer</th>
                <th>Phone</th>
                <th>Product</th>
                <th>Qty</th>
                <th>Unit Price</th>
                <th>Total</th>
                <th>Payment</th>
                <th>Status</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
        <?php if ($sales): ?>
            <?php foreach ($sales as $row): ?>
                <tr>
                    <td><?= htmlspecialchars($row['order_number']) ?></td>
                    <td><?= htmlspecialchars($row['username']) ?></td>
                    <td><?= htmlspecialchars($row['phone_number']) ?></td>
                    <td><?= htmlspecialchars($row['product_name']) ?></td>
                    <td><?= $row['quantity'] ?></td>
                    <td>₹<?= number_format($row['unit_price'], 2) ?></td>
                    <td>₹<?= number_format($row['total_price'], 2) ?></td>
                    <td><?= htmlspecialchars($row['payment_status']) ?> (<?= htmlspecialchars($row['payment_method']) ?>)</td>
                    <td><?= htmlspecialchars($row['order_status']) ?></td>
                    <td><?= date('d-M-Y', strtotime($row['created_at'])) ?></td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="10" class="text-center">No sales in this period</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</main>

<?php include INCLUDES_PATH . '/templates/admin_footer.php'; ?>

