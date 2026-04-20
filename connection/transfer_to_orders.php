<?php
session_start();
require_once 'db_connect.php';

// Clean output buffer and set headers
ob_clean();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

$input = file_get_contents('php://input');
$data = json_decode($input, true);

$referenceNumber = $data['reference_number'] ?? '';
$mop = $data['mop'] ?? 'Pay Later';
$orderDate = $data['order_date'] ?? null;
$deliveryDate = $data['delivery_date'] ?? null;
$contactNumber = $data['contact_number'] ?? null;

// Ensure reference number starts with #
if (!empty($referenceNumber) && strpos($referenceNumber, '#') !== 0) {
    $referenceNumber = '#' . $referenceNumber;
}

try {
    $pdo->beginTransaction();

    // Generate a unique 4-digit order_id
    $orderId = str_pad(mt_rand(0, 9999), 4, '0', STR_PAD_LEFT);
    $stmt = $pdo->prepare("SELECT order_id FROM orders WHERE order_id = ?");
    $stmt->execute([$orderId]);
    while ($stmt->fetch()) {
        $orderId = str_pad(mt_rand(0, 9999), 4, '0', STR_PAD_LEFT);
        $stmt->execute([$orderId]);
    }

    // Determine initial status
    $initialStatus = ($mop === 'Gcash') ? 'waiting' : 'to-receive';

    // Fetch cart items with product stock
    $stmt = $pdo->prepare("
        SELECT c.*, p.product_name, p.price, p.quantity AS product_stock 
        FROM cart c
        JOIN products p ON c.product_id = p.id
        WHERE c.user_id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($cartItems)) {
        throw new Exception('No items found in cart');
    }

    // STEP 1: Check stock
    foreach ($cartItems as $item) {
        if ($item['quantity'] > $item['product_stock']) {
            throw new Exception("Insufficient stock for {$item['product_name']}. Available: {$item['product_stock']}, Requested: {$item['quantity']}");
        }
    }

    // STEP 2: Deduct stock
    foreach ($cartItems as $item) {
        $updateStock = $pdo->prepare("UPDATE products SET quantity = quantity - ? WHERE id = ?");
        if (!$updateStock->execute([$item['quantity'], $item['product_id']])) {
            throw new Exception('Failed to update stock for ' . $item['product_name']);
        }
    }

    // STEP 3: Insert orders
    foreach ($cartItems as $item) {
        $total = $item['price'] * $item['quantity'];

        $stmt = $pdo->prepare("
            INSERT INTO orders 
            (order_id, user_id, product_name, quantity, price, total, reference_number, order_date, delivery_date, contact_number, mop, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        if (!$stmt->execute([
            $orderId,
            $_SESSION['user_id'],
            $item['product_name'],
            $item['quantity'],
            $item['price'],
            $total,
            $referenceNumber,
            $orderDate ?? date('Y-m-d H:i:s'),
            $deliveryDate ?? date('Y-m-d H:i:s', strtotime('+1 day')), // Default next day if not provided
            $contactNumber,
            $mop,
            $initialStatus
        ])) {
            throw new Exception('Failed to create order for ' . $item['product_name']);
        }
    }

    // STEP 4: Clear cart
    $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ?");
    if (!$stmt->execute([$_SESSION['user_id']])) {
        throw new Exception('Failed to clear cart');
    }

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Order processed successfully',
        'order_id' => $orderId
    ]);

} catch (PDOException $e) {
    $pdo->rollBack();
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
