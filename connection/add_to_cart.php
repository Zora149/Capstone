<?php
session_start();
require_once 'db_connect.php'; // Make sure to create this file with your database connection

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        echo json_encode([
            'success' => false,
            'redirect' => 'context/Login.php'
        ]);
        exit;
    }

    $data = json_decode(file_get_contents('php://input'), true);
    $productId = $data['product_id'];
    $userId = $_SESSION['user_id'];

    try {
        // Get product price
        $stmt = $pdo->prepare("SELECT price FROM products WHERE id = ?");
        $stmt->execute([$productId]);
        $product = $stmt->fetch();

        if (!$product) {
            throw new Exception('Product not found');
        }

        $price = $product['price'];
        $quantity = 1;
        $totalPrice = $price * $quantity;

        // Check if product already exists in cart
        $stmt = $pdo->prepare("SELECT * FROM cart WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$userId, $productId]);
        $existingItem = $stmt->fetch();

        if ($existingItem) {
            // Update quantity and total price if item exists
            $newQuantity = $existingItem['quantity'] + 1;
            $newTotalPrice = $price * $newQuantity;
            
            $stmt = $pdo->prepare("UPDATE cart SET quantity = ?, total_price = ? WHERE id = ?");
            $stmt->execute([$newQuantity, $newTotalPrice, $existingItem['id']]);
        } else {
            // Insert new item with price and total price
            $stmt = $pdo->prepare("INSERT INTO cart (user_id, product_id, quantity, price, total_price) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$userId, $productId, $quantity, $price, $totalPrice]);
        }

        // Get updated cart count
        $stmt = $pdo->prepare("
            SELECT c.*, p.product_name, p.image_path 
            FROM cart c
            JOIN products p ON c.product_id = p.id
            WHERE c.user_id = ?
        ");
        $stmt->execute([$userId]);
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $totalCount = array_reduce($orders, function($carry, $item) {
            return $carry + $item['quantity'];
        }, 0);

        echo json_encode([
            'success' => true,
            'cart_count' => $totalCount,
            'orders' => $orders
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
} 