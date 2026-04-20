<?php
session_start();
require_once 'connection/db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$orderId = $data['orderId'] ?? null;

// Debug logging
error_log("Cancel order request - Order ID: " . $orderId . ", User ID: " . ($_SESSION['user_id'] ?? 'not set'));

if (!$orderId) {
    echo json_encode(['success' => false, 'message' => 'Invalid order ID']);
    exit;
}

try {
    // First try to update in orders table
    $stmt = $pdo->prepare("UPDATE orders SET status = 'cancelled' WHERE order_id = ? AND user_id = ?");
    $stmt->execute([$orderId, $_SESSION['user_id']]);
    $ordersUpdated = $stmt->rowCount();
    error_log("Orders table update - Rows affected: " . $ordersUpdated);
    
    // If no rows were affected, try completed_orders table
    if ($ordersUpdated === 0) {
        try {
            $stmt = $pdo->prepare("UPDATE completed_orders SET status = 'cancelled' WHERE order_id = ? AND user_id = ?");
            $stmt->execute([$orderId, $_SESSION['user_id']]);
            $completedOrdersUpdated = $stmt->rowCount();
            error_log("Completed orders table update - Rows affected: " . $completedOrdersUpdated);
            
            if ($completedOrdersUpdated === 0) {
                echo json_encode(['success' => false, 'message' => 'Order not found']);
                exit;
            }
        } catch (PDOException $e) {
            error_log("Completed_orders table not found or error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Order not found']);
            exit;
        }
    }
    
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    error_log("Error cancelling order: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error']);
}