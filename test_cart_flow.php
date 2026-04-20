<?php
session_start();
require_once 'connection/db_connect.php';

echo "<h2>Testing Cart Flow</h2>";

// Simulate a logged-in user (replace with actual user ID for testing)
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1; // Change this to a real user ID
    echo "Set session user_id to 1 for testing<br>";
}

echo "Testing with user ID: " . $_SESSION['user_id'] . "<br>";

// Step 1: Check current cart
echo "<h3>Step 1: Current Cart</h3>";
try {
    $stmt = $pdo->prepare("
        SELECT c.*, p.product_name, p.price, p.image_path 
        FROM cart c
        JOIN products p ON c.product_id = p.id
        WHERE c.user_id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Current cart items: " . count($cartItems) . "<br>";
    if (count($cartItems) > 0) {
        foreach ($cartItems as $item) {
            echo "- " . $item['product_name'] . " (Qty: " . $item['quantity'] . ")<br>";
        }
    } else {
        echo "Cart is empty. Adding a test item...<br>";
        
        // Add a test item to cart
        $testProductId = 1; // Change this to a real product ID
        $stmt = $pdo->prepare("SELECT id FROM products LIMIT 1");
        $stmt->execute();
        $product = $stmt->fetch();
        
        if ($product) {
            $testProductId = $product['id'];
            $stmt = $pdo->prepare("INSERT INTO cart (user_id, product_id, quantity, price, total_price) VALUES (?, ?, 1, 100, 100)");
            $stmt->execute([$_SESSION['user_id'], $testProductId]);
            echo "Added test item with product ID: " . $testProductId . "<br>";
        } else {
            echo "No products found in database!<br>";
        }
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "<br>";
}

// Step 2: Test the transfer_to_orders logic
echo "<h3>Step 2: Testing Order Transfer Logic</h3>";
try {
    // Simulate the cart query from transfer_to_orders.php
    $stmt = $pdo->prepare("
        SELECT c.*, p.product_name, p.price, p.quantity AS product_stock 
        FROM cart c
        JOIN products p ON c.product_id = p.id
        WHERE c.user_id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Cart items found for order processing: " . count($cartItems) . "<br>";
    
    if (empty($cartItems)) {
        echo "ERROR: No items found in cart for order processing!<br>";
        echo "This is the same error you're seeing.<br>";
    } else {
        echo "SUCCESS: Cart items found for order processing.<br>";
        foreach ($cartItems as $item) {
            echo "- " . $item['product_name'] . " (Qty: " . $item['quantity'] . ", Stock: " . $item['product_stock'] . ")<br>";
        }
    }
} catch (Exception $e) {
    echo "Error in order transfer logic: " . $e->getMessage() . "<br>";
}

// Step 3: Show all cart records
echo "<h3>Step 3: All Cart Records</h3>";
try {
    $stmt = $pdo->query("SELECT * FROM cart WHERE user_id = " . $_SESSION['user_id']);
    $allCarts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "All cart records for user " . $_SESSION['user_id'] . ": " . count($allCarts) . "<br>";
    foreach ($allCarts as $cart) {
        echo "Cart ID: " . $cart['id'] . ", Product ID: " . $cart['product_id'] . ", Qty: " . $cart['quantity'] . "<br>";
    }
} catch (Exception $e) {
    echo "Error fetching cart records: " . $e->getMessage() . "<br>";
}
?>
