<?php
session_start();
require_once 'connection/db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: context/Login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_id'])) {
    $user_id = $_SESSION['user_id'];
    $product_id = $_POST['product_id'];

    try {
        // Insert the product with quantity 1 (or replace with desired behavior)
        $checkStmt = $pdo->prepare("SELECT * FROM cart WHERE user_id = ? AND product_id = ?");
        $checkStmt->execute([$user_id, $product_id]);
        $existing = $checkStmt->fetch();

        if ($existing) {
            // If already in cart, update quantity to 1 or keep as is
            $stmt = $pdo->prepare("UPDATE cart SET quantity = quantity + 1 WHERE user_id = ? AND product_id = ?");
            $stmt->execute([$user_id, $product_id]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, 1)");
            $stmt->execute([$user_id, $product_id]);
        }

        // Redirect to cart
        header("Location: cart.php");
        exit;
    } catch (PDOException $e) {
        error_log("Buy Now error: " . $e->getMessage());
        echo "Error processing order.";
    }
} else {
    echo "Invalid request.";
}
