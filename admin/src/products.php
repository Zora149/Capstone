<?php
require_once 'middleware.php';
require_once '../../connection/db_connect.php';

// Fetch products from the database
try {
    $stmt = $pdo->query("SELECT * FROM products");
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching products: " . $e->getMessage());
    $products = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="./css/product.css">
    <title>Product Management</title>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Product Management</h1>
            <button>Add Product</button>
        </div>
        
        <table>
            <thead>
                <tr>
                    <th>Images</th>
                    <th>Product ID</th>
                    <th>Product Name</th>
                    <th>Price</th>
                    <th>Quantity</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($products)): ?>
                    <?php foreach ($products as $product): ?>
                        <tr>
                            <td>
                                <?php if (file_exists('../../' . $product['image_path'])): ?>
                                    <img src="../../<?= htmlspecialchars($product['image_path']) ?>" 
                                         alt="Product Image" 
                                         class="product-image"
                                         onerror="this.src='../../assets/img/default_product.png'">
                                <?php else: ?>
                                    <img src="../../assets/img/default_product.png" 
                                         alt="Default Product Image"
                                         class="product-image">
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($product['id']) ?></td>
                            <td><?= htmlspecialchars($product['product_name']) ?></td>
                            <td>₱<?= number_format($product['price'], 2) ?></td>
                            <td><?= htmlspecialchars($product['quantity']) ?></td>
                            <td>
                                <div class="action-buttons">
                                    <button class="edit" 
                                            data-id="<?= $product['id'] ?>" 
                                            data-name="<?= htmlspecialchars($product['product_name']) ?>" 
                                            data-price="<?= $product['price'] ?>" 
                                            data-quantity="<?= $product['quantity'] ?>"><i class="fa-solid fa-edit"></i></button>
                                    <button class="delete" data-id="<?= $product['id'] ?>"> <i class="fa-solid fa-trash"></i></button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="no-products">No products found</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Add Product Modal -->
    <div id="addProductModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Add Product</h2>
            <form id="addProductForm" method="POST" action="../../connection/add_product.php" enctype="multipart/form-data">
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
        </div>
    </div>

    <!-- Edit Product Modal -->
    <div id="editProductModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Edit Product</h2>
            <form id="editProductForm" method="POST" action="../../connection/edit_product.php" enctype="multipart/form-data">
                <input type="hidden" id="editProductId" name="productId">
                
                <label for="editProductName">Product Name:</label>
                <input type="text" id="editProductName" name="productName" required>
                
                <label for="editProductPrice">Price:</label>
                <input type="number" id="editProductPrice" name="productPrice" step="0.01" required>
                
                <label for="editProductQuantity">Quantity:</label>
                <input type="number" id="editProductQuantity" name="productQuantity" required>
                
                <label for="editProductImage">Upload Image:</label>
                <input type="file" id="editProductImage" name="productImage" accept="image/*">
                
                <button type="submit">Update Product</button>
            </form>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteProductModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Delete Product</h2>
            <p>Are you sure you want to delete this product?</p>
            <form id="deleteProductForm" method="POST" action="../../connection/delete_product.php">
                <input type="hidden" id="deleteProductId" name="productId">
                <button type="submit" class="confirm-delete">Yes, Delete</button>
            </form>
        </div>
    </div>
</body>
</html>