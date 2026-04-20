<?php
session_start();
require_once 'connection/db_connect.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    exit("Unauthorized");
}

$user_id = $_SESSION['user_id'];

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        SELECT c.product_id, c.quantity, p.quantity AS product_stock 
        FROM cart c
        JOIN products p ON c.product_id = p.id
        WHERE c.user_id = ?
    ");
    $stmt->execute([$user_id]);
    $cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($cartItems as $item) {
        if ($item['quantity'] > $item['product_stock']) {
            throw new Exception("Insufficient stock.");
        }

        $updateStock = $pdo->prepare("UPDATE products SET quantity = quantity - ? WHERE id = ?");
        $updateStock->execute([$item['quantity'], $item['product_id']]);
    }

    $clearCart = $pdo->prepare("DELETE FROM cart WHERE user_id = ?");
    $clearCart->execute([$user_id]);

    $pdo->commit();
    http_response_code(200);

} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo "Error: " . $e->getMessage();
}
?>
