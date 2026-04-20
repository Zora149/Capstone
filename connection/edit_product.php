<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $productId = $_POST['productId'];
    $productName = $_POST['productName'];
    $price = $_POST['productPrice'];
    $quantity = $_POST['productQuantity'];

    // Handle file upload if a new image is provided
    if (!empty($_FILES['productImage']['name'])) {
        $targetDir = "../../upload_images/";
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true); // Create the directory if it doesn't exist
        }

        $imagePath = $targetDir . basename($_FILES['productImage']['name']);
        if (move_uploaded_file($_FILES['productImage']['tmp_name'], $imagePath)) {
            // Update product with new image
            $stmt = $pdo->prepare("UPDATE products SET product_name = ?, price = ?, quantity = ?, image_path = ? WHERE id = ?");
            if ($stmt->execute([$productName, $price, $quantity, $imagePath, $productId])) {
                header("Location: ../admin/src/index.php");
                exit();
            } else {
                echo "Error updating product.";
            }
        } else {
            echo "Error uploading file.";
        }
    } else {
        // Update product without changing the image
        $stmt = $pdo->prepare("UPDATE products SET product_name = ?, price = ?, quantity = ? WHERE id = ?");
        if ($stmt->execute([$productName, $price, $quantity, $productId])) {
            header("Location: ../admin/src/index.php");
            exit();
        } else {
            echo "Error updating product.";
        }
    }
} else {
    echo "Invalid request method.";
}
?>