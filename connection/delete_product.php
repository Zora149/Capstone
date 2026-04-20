<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $productId = $_POST['productId'];

    // Delete the product from the database
    $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
    if ($stmt->execute([$productId])) {
        header("Location: ../admin/src/index.php");
        exit();
    } else {
        echo "Error deleting product.";
    }
} else {
    echo "Invalid request method.";
}
?>