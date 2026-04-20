<?php
require_once __DIR__ . '/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Display the add product form
    echo '
    <form action="/connection/add_product.php" method="POST" enctype="multipart/form-data">
        <label for="productName">Product Name:</label>
        <input type="text" id="productName" name="productName" required>
        
        <label for="productPrice">Price:</label>
        <input type="number" id="productPrice" name="productPrice" step="0.01" required>
        
        <label for="productQuantity">Quantity:</label>
        <input type="number" id="productQuantity" name="productQuantity" required>
        
        <label for="productImage">Upload Image:</label>
        <input type="file" id="productImage" name="productImage" accept="image/*" required>
        
        <button type="submit">Add Product</button>
    </form>
    ';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate inputs
        $productName = filter_input(INPUT_POST, 'productName', FILTER_SANITIZE_STRING);
        $price = filter_input(INPUT_POST, 'productPrice', FILTER_VALIDATE_FLOAT);
        $quantity = filter_input(INPUT_POST, 'productQuantity', FILTER_VALIDATE_INT);
        
        if (!$productName || !$price || !$quantity) {
            throw new Exception("Invalid input data");
        }

        // Handle file upload
        $targetDir = "../upload_images/";
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        // Validate image
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/jpg'];
        $fileInfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($fileInfo, $_FILES['productImage']['tmp_name']);
        finfo_close($fileInfo);

        if (!in_array($mimeType, $allowedTypes)) {
            throw new Exception("Invalid file type. Only JPG, JPEG, PNG, and GIF are allowed.");
        }

        if ($_FILES['productImage']['size'] > 5 * 1024 * 1024) { // 2MB limit
            throw new Exception("File size exceeds 2MB limit.");
        }

        // Generate unique filename
        $extension = pathinfo($_FILES['productImage']['name'], PATHINFO_EXTENSION);
        $filename = uniqid('product_', true) . '.' . $extension;
        $relativePath = "upload_images/" . $filename;
        $absolutePath = $targetDir . $filename;

        if (move_uploaded_file($_FILES['productImage']['tmp_name'], $absolutePath)) {
            // Insert into database
            $stmt = $pdo->prepare("INSERT INTO products (product_name, price, quantity, image_path) VALUES (?, ?, ?, ?)");
            $stmt->execute([$productName, $price, $quantity, $relativePath]);
            
            header("Location: ../admin/src/index.php?success=1");
            exit();
        } else {
            throw new Exception("Error uploading file.");
        }
    } catch (Exception $e) {
        error_log("Error adding product: " . $e->getMessage());
        header("Location: ../admin/src/index.php?error=" . urlencode($e->getMessage()));
        exit();
    }
} else {
    header("Location: ../admin/src/index.php");
    exit();
}
?>