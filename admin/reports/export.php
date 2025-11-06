<?php
require_once dirname(dirname(__DIR__)) . '/includes/config/init.php';

// Ensure the admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: ' . admin_url('login'));
    exit();
}

$export_type = $_GET['type'] ?? '';
$format = $_GET['format'] ?? 'csv';

// Validate export type
$allowed_types = ['products', 'orders', 'users', 'sales', 'inventory'];
if (!in_array($export_type, $allowed_types)) {
    die('Invalid export type');
}

// Validate format
$allowed_formats = ['csv', 'excel'];
if (!in_array($format, $allowed_formats)) {
    die('Invalid format');
}

try {
    switch ($export_type) {
        case 'products':
            $data = $pdo->query("
                SELECT 
                    p.id,
                    p.product_name,
                    p.model_name,
                    p.model_number,
                    p.price,
                    p.stock,
                    p.status,
                    p.created_at,
                    b.name as brand_name,
                    c.name as category_name
                FROM products p
                LEFT JOIN brands b ON p.brand_id = b.id
                LEFT JOIN categories c ON p.category_id = c.id
                ORDER BY p.created_at DESC
            ")->fetchAll(PDO::FETCH_ASSOC);
            $filename = 'products_export_' . date('Y-m-d_H-i-s');
            break;
            
        case 'orders':
            $data = $pdo->query("
                SELECT 
                    o.id,
                    o.order_number,
                    o.total_price,
                    o.order_status,
                    o.payment_status,
                    o.payment_method,
                    o.created_at,
                    u.username,
                    u.email,
                    u.phone_number
                FROM orders o
                LEFT JOIN users u ON o.user_id = u.id
                ORDER BY o.created_at DESC
            ")->fetchAll(PDO::FETCH_ASSOC);
            $filename = 'orders_export_' . date('Y-m-d_H-i-s');
            break;
            
        case 'users':
            $data = $pdo->query("
                SELECT 
                    u.id,
                    u.username,
                    u.email,
                    u.phone_number,
                    u.address,
                    u.status,
                    u.created_at,
                    COUNT(o.id) as total_orders,
                    COALESCE(SUM(o.total_price), 0) as total_spent
                FROM users u
                LEFT JOIN orders o ON u.id = o.user_id
                GROUP BY u.id
                ORDER BY u.created_at DESC
            ")->fetchAll(PDO::FETCH_ASSOC);
            $filename = 'users_export_' . date('Y-m-d_H-i-s');
            break;
            
        case 'sales':
            $data = $pdo->query("
                SELECT 
                    DATE(o.created_at) as sale_date,
                    COUNT(o.id) as total_orders,
                    SUM(o.total_price) as total_revenue,
                    AVG(o.total_price) as avg_order_value
                FROM orders o
                WHERE o.order_status != 'Cancelled'
                GROUP BY DATE(o.created_at)
                ORDER BY sale_date DESC
            ")->fetchAll(PDO::FETCH_ASSOC);
            $filename = 'sales_report_' . date('Y-m-d_H-i-s');
            break;
            
        case 'inventory':
            $data = $pdo->query("
                SELECT 
                    p.product_name,
                    p.model_name,
                    p.stock,
                    p.price,
                    p.status,
                    b.name as brand_name,
                    c.name as category_name,
                    CASE 
                        WHEN p.stock = 0 THEN 'Out of Stock'
                        WHEN p.stock < 5 THEN 'Low Stock'
                        WHEN p.stock < 20 THEN 'Medium Stock'
                        ELSE 'High Stock'
                    END as stock_status
                FROM products p
                LEFT JOIN brands b ON p.brand_id = b.id
                LEFT JOIN categories c ON p.category_id = c.id
                ORDER BY p.stock ASC
            ")->fetchAll(PDO::FETCH_ASSOC);
            $filename = 'inventory_report_' . date('Y-m-d_H-i-s');
            break;
    }
    
    if ($format === 'csv') {
        // Set headers for CSV download
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        // Create output stream
        $output = fopen('php://output', 'w');
        
        // Add BOM for UTF-8
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Write headers
        if (!empty($data)) {
            fputcsv($output, array_keys($data[0]));
            
            // Write data
            foreach ($data as $row) {
                fputcsv($output, $row);
            }
        }
        
        fclose($output);
        exit;
        
    } elseif ($format === 'excel') {
        // For Excel format, we'll create a simple HTML table that Excel can open
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="' . $filename . '.xls"');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        echo '<table border="1">';
        
        if (!empty($data)) {
            // Headers
            echo '<tr>';
            foreach (array_keys($data[0]) as $header) {
                echo '<th>' . htmlspecialchars($header) . '</th>';
            }
            echo '</tr>';
            
            // Data
            foreach ($data as $row) {
                echo '<tr>';
                foreach ($row as $cell) {
                    echo '<td>' . htmlspecialchars($cell) . '</td>';
                }
                echo '</tr>';
            }
        }
        
        echo '</table>';
        exit;
    }
    
} catch (PDOException $e) {
    die('Database error: ' . $e->getMessage());
}
?>
