<?php
// Disable error reporting for clean JSON output
error_reporting(0);
ini_set('display_errors', 0);

require_once __DIR__ . '/../../includes/config/init.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['count' => 0]);
    exit();
}

$user_id = $_SESSION['user_id'];

try {
    $count_query = $pdo->prepare("SELECT COUNT(*) as count FROM cart WHERE user_id = ?");
    $count_query->execute([$user_id]);
    $result = $count_query->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode(['count' => (int)$result['count']]);
} catch (Exception $e) {
    echo json_encode(['count' => 0]);
}
?>
