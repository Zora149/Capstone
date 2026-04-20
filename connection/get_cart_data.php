<?php
session_start();
require_once 'db_connect.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

try {
    // Fetch cart items for the logged-in user
    $stmt = $pdo->prepare("
        SELECT c.*, p.product_name, p.price, p.image_path 
        FROM cart c
        JOIN products p ON c.product_id = p.id
        WHERE c.user_id = ?
        ORDER BY c.id ASC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate totals
    $total = 0;
    $totalQuantity = 0;
    foreach ($orders as $order) {
        $itemTotal = $order['price'] * $order['quantity'];
        $total += $itemTotal;
        $totalQuantity += $order['quantity'];
    }
    
    echo json_encode([
        'success' => true,
        'orders' => $orders,
        'total' => $total,
        'totalQuantity' => $totalQuantity,
        'itemCount' => count($orders)
    ]);
    
} catch (PDOException $e) {
    error_log("Error fetching cart data: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>
