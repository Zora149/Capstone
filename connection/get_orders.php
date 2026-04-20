<?php
session_start();
require_once 'db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'User not logged in'
    ]);
    exit;
}

try {
    // Fetch orders with product details
    $stmt = $pdo->prepare("
        SELECT c.*, p.product_name, p.image_path 
        FROM cart c
        JOIN products p ON c.product_id = p.id
        WHERE c.user_id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculate total cart count
    $totalCount = array_reduce($orders, function($carry, $item) {
        return $carry + $item['quantity'];
    }, 0);

    echo json_encode([
        'success' => true,
        'orders' => $orders,
        'total_count' => $totalCount
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
} 