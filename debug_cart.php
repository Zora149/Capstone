<?php
session_start();
require_once 'connection/db_connect.php';

echo "<h2>Cart Debug Information</h2>";

// Check session
echo "<h3>Session Information:</h3>";
echo "Session ID: " . session_id() . "<br>";
echo "User ID in session: " . ($_SESSION['user_id'] ?? 'NOT SET') . "<br>";
echo "Session data: <pre>" . print_r($_SESSION, true) . "</pre>";

// Check database connection
echo "<h3>Database Connection:</h3>";
try {
    $stmt = $pdo->query("SELECT 1");
    echo "Database connection: OK<br>";
} catch (Exception $e) {
    echo "Database connection: FAILED - " . $e->getMessage() . "<br>";
}

// Check cart table structure
echo "<h3>Cart Table Structure:</h3>";
try {
    $stmt = $pdo->query("DESCRIBE cart");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<table border='1'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    foreach ($columns as $column) {
        echo "<tr>";
        foreach ($column as $value) {
            echo "<td>" . htmlspecialchars($value) . "</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
} catch (Exception $e) {
    echo "Error describing cart table: " . $e->getMessage() . "<br>";
}

// Check cart data
echo "<h3>Cart Data:</h3>";
if (isset($_SESSION['user_id'])) {
    try {
        $stmt = $pdo->prepare("
            SELECT c.*, p.product_name, p.price, p.image_path 
            FROM cart c
            JOIN products p ON c.product_id = p.id
            WHERE c.user_id = ?
        ");
        $stmt->execute([$_SESSION['user_id']]);
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "Found " . count($orders) . " cart items<br>";
        if (count($orders) > 0) {
            echo "<table border='1'>";
            echo "<tr><th>ID</th><th>User ID</th><th>Product ID</th><th>Product Name</th><th>Quantity</th><th>Price</th></tr>";
            foreach ($orders as $order) {
                echo "<tr>";
                echo "<td>" . $order['id'] . "</td>";
                echo "<td>" . $order['user_id'] . "</td>";
                echo "<td>" . $order['product_id'] . "</td>";
                echo "<td>" . $order['product_name'] . "</td>";
                echo "<td>" . $order['quantity'] . "</td>";
                echo "<td>" . $order['price'] . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
    } catch (Exception $e) {
        echo "Error fetching cart data: " . $e->getMessage() . "<br>";
    }
} else {
    echo "No user ID in session<br>";
}

// Check all cart records
echo "<h3>All Cart Records:</h3>";
try {
    $stmt = $pdo->query("SELECT * FROM cart LIMIT 10");
    $allCarts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Found " . count($allCarts) . " total cart records (showing first 10)<br>";
    if (count($allCarts) > 0) {
        echo "<table border='1'>";
        echo "<tr><th>ID</th><th>User ID</th><th>Product ID</th><th>Quantity</th><th>Created At</th></tr>";
        foreach ($allCarts as $cart) {
            echo "<tr>";
            echo "<td>" . $cart['id'] . "</td>";
            echo "<td>" . $cart['user_id'] . "</td>";
            echo "<td>" . $cart['product_id'] . "</td>";
            echo "<td>" . $cart['quantity'] . "</td>";
            echo "<td>" . ($cart['created_at'] ?? 'N/A') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "Error fetching all cart records: " . $e->getMessage() . "<br>";
}
?>
