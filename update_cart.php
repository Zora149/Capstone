<?php
session_start();
require_once 'connection/db_connect.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $cartId = $_POST['cart_id'] ?? 0;
    $userId = $_SESSION['user_id'];

    try {
        if ($action === 'increase') {
            $stmt = $pdo->prepare("UPDATE cart SET quantity = quantity + 1 WHERE id = ? AND user_id = ?");
            $stmt->execute([$cartId, $userId]);
            echo json_encode(['success' => true]);
        } elseif ($action === 'decrease') {
            // Check current quantity to prevent it from going below 1
            $stmt = $pdo->prepare("SELECT quantity FROM cart WHERE id = ? AND user_id = ?");
            $stmt->execute([$cartId, $userId]);
            $item = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($item && $item['quantity'] > 1) {
                $stmt = $pdo->prepare("UPDATE cart SET quantity = quantity - 1 WHERE id = ? AND user_id = ?");
                $stmt->execute([$cartId, $userId]);
                echo json_encode(['success' => true]);
            } else {
                // If quantity is 1, remove the item
                $stmt = $pdo->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
                $stmt->execute([$cartId, $userId]);
                echo json_encode(['success' => true, 'removed' => true]);
            }
        } elseif ($action === 'remove') {
            $stmt = $pdo->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
            $stmt->execute([$cartId, $userId]);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid action.']);
        }
    } catch (PDOException $e) {
        error_log("Error updating cart: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error.']);
    }
}