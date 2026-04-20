<?php
require_once 'middleware.php';
require_once '../../connection/db_connect.php';

// Fetch orders with user information, grouped by order_id
try {
    $stmt = $pdo->prepare("
        SELECT o.*, u.username, u.address 
        FROM orders o
        JOIN users u ON o.user_id = u.id
        WHERE o.status NOT IN ('completed', 'cancelled')
        ORDER BY o.order_id DESC, o.order_date DESC
    ");
    $stmt->execute();
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Group orders by order_id
    $groupedOrders = [];
    foreach ($orders as $order) {
        $orderId = $order['order_id'];
        if (!isset($groupedOrders[$orderId])) {
            $groupedOrders[$orderId] = [
                'order_id' => $orderId,
                'username' => $order['username'],
                'order_date' => $order['order_date'],
                'mop' => $order['mop'],
                'reference_number' => $order['reference_number'],
                'delivery_date' => $order['delivery_date'],
                'contact_number' => $order['contact_number'],
                'status' => $order['status'],
                'address' => $order['address'],
                'items' => [],
                'total' => 0
            ];
        }
        $groupedOrders[$orderId]['items'][] = [
            'product_name' => $order['product_name'],
            'quantity' => $order['quantity'],
            'price' => $order['price'],
            'total' => $order['total']
        ];
        $groupedOrders[$orderId]['total'] += $order['total'];
    }
} catch (PDOException $e) {
    error_log("Error fetching orders: " . $e->getMessage());
    $groupedOrders = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Transactions</title>
    <link rel="stylesheet" href="./css/global.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        /* ✅ Scrollable and single-line table fix */
        .orders-container {
            width: 100%;
            overflow-x: auto; /* allow horizontal scroll */
            -webkit-overflow-scrolling: touch; /* smooth on mobile */
            border: 1px solid #dee2e6;
            border-radius: 8px;
            background: #fff;
            padding: 1rem;
        }

        .orders-container table {
            white-space: nowrap; /* prevent line break */
            min-width: max-content; /* expand as needed */
            width: 100%;
        }

        .orders-container th,
        .orders-container td {
            vertical-align: middle;
            text-align: left;
            white-space: nowrap; /* keep text on one line */
            padding: 0.75rem 1rem;
        }

        .orders-container thead {
            background-color: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
        }

        /* Optional: make the scrollbar look modern */
        .orders-container::-webkit-scrollbar {
            height: 8px;
        }

        .orders-container::-webkit-scrollbar-thumb {
            background-color: #adb5bd;
            border-radius: 10px;
        }

        .orders-container::-webkit-scrollbar-thumb:hover {
            background-color: #868e96;
        }
    </style>
</head>
<body>

<!-- ✅ Display success/error messages -->
<?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?php
        switch ($_GET['success']) {
            case 'status_updated':
                echo "Order status updated successfully!";
                break;
            default:
                echo "Operation completed successfully!";
        }
        ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if (isset($_GET['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?php
        switch ($_GET['error']) {
            case 'update_failed':
                echo "Failed to update order status. Please try again.";
                break;
            case 'invalid_request':
                echo "Invalid request. Please try again.";
                break;
            case 'invalid_method':
                echo "Invalid request method.";
                break;
            default:
                echo "An error occurred. Please try again.";
        }
        ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<!-- ✅ Scrollable Table Section -->
<div class="orders-container my-4">
    <h1>Order Transactions</h1>
    <div class="table-responsive">
        <table class="table table-striped table-hover">
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Customer</th>
                    <th>Date</th>
                    <th>Items</th>
                    <th>Total</th>
                    <th>Mode of Payment</th>
                    <th>Reference Number</th>
                    <th>Schedule</th>
                    <th>Contact Number</th>
                    <th>Status</th>
                    <th>Address</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($groupedOrders)): ?>
                    <?php foreach ($groupedOrders as $order): ?>
                        <tr>
                            <td>#<?= htmlspecialchars($order['order_id']) ?></td>
                            <td><?= htmlspecialchars($order['username']) ?></td>
                            <td><?= date('Y-m-d', strtotime($order['order_date'])) ?></td>
                            <td>
                                <ul class="list-unstyled mb-0">
                                    <?php foreach ($order['items'] as $item): ?>
                                        <li><?= htmlspecialchars($item['product_name']) ?> (Qty: <?= $item['quantity'] ?>)</li>
                                    <?php endforeach; ?>
                                </ul>
                            </td>
                            <td>₱<?= number_format($order['total'], 2) ?></td>
                            <td><?= htmlspecialchars($order['mop']) ?></td>
                            <td><?= htmlspecialchars($order['reference_number']) ?></td>
                            <td class="text-uppercase"><?= $order['delivery_date'] ? date('Y-m-d H:i:s', strtotime($order['delivery_date'])) : '' ?></td>
                            <td><?= htmlspecialchars($order['contact_number']) ?></td>
                            <td>
                                <form method="POST" action="../../connection/update_order_status.php" onsubmit="return confirm('Are you sure you want to change the status of this order?');">
                                    <input type="hidden" name="order_id" value="<?= $order['order_id'] ?>">
                                    <select name="status" class="form-select form-select-sm text-uppercase cursor-pointer" onchange="this.form.submit()">
                                        <option value="to-receive" <?= $order['status'] === 'to-receive' ? 'selected' : '' ?>>To Receive</option>
                                        <option value="waiting" <?= $order['status'] === 'waiting' ? 'selected' : '' ?>>Waiting</option>
                                        <option value="completed">Completed</option>
                                        <option value="cancelled">Cancelled</option>
                                    </select>
                                </form>
                            </td>
                            <td><?= htmlspecialchars($order['address']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="11" class="text-center">No orders found</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
