<?php
require_once '../../connection/db_connect.php';

// Get the latest pending order
$stmt = $pdo->query("
    SELECT order_id FROM orders 
    WHERE status NOT IN ('completed', 'cancelled') 
    ORDER BY order_date DESC 
    LIMIT 1
");

$order = $stmt->fetch(PDO::FETCH_ASSOC);

echo json_encode([
    'new' => $order ? true : false,
    'latest_order_id' => $order['order_id'] ?? null
]);
