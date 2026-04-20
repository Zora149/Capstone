<?php
session_start();
require_once 'db_connect.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $orderId = $data['order_id'];
    $change = $data['change'];

    try {
        // Get current order details
        $stmt = $pdo->prepare("SELECT c.*, p.price FROM cart c JOIN products p ON c.product_id = p.id WHERE c.id = ? AND c.user_id = ?");
        $stmt->execute([$orderId, $_SESSION['user_id']]);
        $order = $stmt->fetch();

        if ($order) {
            $newQuantity = $order['quantity'] + $change;
            if ($newQuantity > 0) {
                // Update quantity and total price
                $newTotalPrice = $order['price'] * $newQuantity;
                $stmt = $pdo->prepare("UPDATE cart SET quantity = ?, total_price = ? WHERE id = ?");
                $stmt->execute([$newQuantity, $newTotalPrice, $orderId]);
            } else {
                // Remove item if quantity becomes 0
                $stmt = $pdo->prepare("DELETE FROM cart WHERE id = ?");
                $stmt->execute([$orderId]);
            }

            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Order not found']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}