<?php
require_once 'middleware.php';
require_once '../../connection/db_connect.php';

// Set headers for Excel file download
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="order_records_' . date('Y-m-d') . '.xls"');
header('Pragma: no-cache');
header('Expires: 0');

// Fetch completed and cancelled orders with user information
try {
    $stmt = $pdo->prepare("SELECT co.*, u.username
        FROM completed_orders co
        JOIN users u ON co.user_id = u.id
        WHERE co.status IN ('completed', 'cancelled')
        ORDER BY co.order_id DESC, co.order_date DESC");
    $stmt->execute();
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Group orders by order_id, same logic as your original script
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
                'status' => $order['status'],
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

    // Output column headers for the Excel file
    echo "Order ID\tCustomer\tDate\tItems\tTotal\tMode of Payment\tReference Number\tStatus\n";

    // Loop through the grouped orders and output data for each row
    if (!empty($groupedOrders)) {
        foreach ($groupedOrders as $order) {
            $orderId = htmlspecialchars($order['order_id']);
            $username = htmlspecialchars($order['username']);
            $orderDate = date('Y-m-d', strtotime($order['order_date']));
            $mop = htmlspecialchars($order['mop']);
            $referenceNumber = htmlspecialchars($order['reference_number']);
            $status = ucfirst($order['status']);
            $total = number_format($order['total'], 2);

            // Format items into a single string for the cell
            $itemsString = '';
            foreach ($order['items'] as $item) {
                $itemsString .= htmlspecialchars($item['product_name']) . " (Qty: " . $item['quantity'] . ")\n";
            }
            $itemsString = trim($itemsString);

            // Output the row data, separated by tabs
            echo "$orderId\t$username\t$orderDate\t\"$itemsString\"\t₱$total\t$mop\t$referenceNumber\t$status\n";
        }
    }
} catch (PDOException $e) {
    error_log("Error exporting order records: " . $e->getMessage());
    echo "Error: Unable to export data.";
}
?>