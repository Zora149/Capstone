<?php
session_start();
require_once 'db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

$orderId = $_GET['order_id'] ?? null;

if (!$orderId) {
    echo json_encode(['success' => false, 'message' => 'Order ID is required']);
    exit;
}

try {
    // First try to get order from orders table
    $stmt = $pdo->prepare("
        SELECT o.*, u.username, u.email 
        FROM orders o 
        LEFT JOIN users u ON o.user_id = u.id 
        WHERE o.order_id = ? AND o.user_id = ?
    ");
    $stmt->execute([$orderId, $_SESSION['user_id']]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // If no orders found in orders table, try completed_orders table
    if (empty($orders)) {
        try {
            $stmt = $pdo->prepare("
                SELECT o.*, u.username, u.email 
                FROM completed_orders o 
                LEFT JOIN users u ON o.user_id = u.id 
                WHERE o.order_id = ? AND o.user_id = ?
            ");
            $stmt->execute([$orderId, $_SESSION['user_id']]);
            $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            // completed_orders table might not exist
            error_log("Completed_orders table not found: " . $e->getMessage());
        }
    }
    
    if (empty($orders)) {
        echo json_encode(['success' => false, 'message' => 'Order not found']);
        exit;
    }
    
    // Calculate totals
    $totalAmount = 0;
    $totalQuantity = 0;
    foreach ($orders as $order) {
        $totalAmount += $order['total'];
        $totalQuantity += $order['quantity'];
    }
    
    // Prepare receipt data
    $receiptData = [
        'success' => true,
        'order_id' => $orderId,
        'order_date' => $orders[0]['order_date'],
        'reference_number' => $orders[0]['reference_number'],
        'payment_method' => $orders[0]['mop'],
        'status' => $orders[0]['status'],
        'customer_name' => $orders[0]['username'] ?? 'Guest',
        'customer_email' => $orders[0]['email'] ?? '',
        'items' => $orders,
        'total_quantity' => $totalQuantity,
        'total_amount' => $totalAmount
    ];
    
    echo json_encode($receiptData);
    
} catch (PDOException $e) {
    error_log("Error fetching receipt data: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>
