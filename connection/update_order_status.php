<?php
require_once 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $orderId = $_POST['order_id'] ?? null;
    $newStatus = $_POST['status'] ?? null;

    if ($orderId && $newStatus) {
        try {
            $pdo->beginTransaction();

            // If changing to completed or cancelled, transfer to completed_orders
            if (in_array($newStatus, ['completed', 'cancelled'])) {
                
                // Select the rows to transfer
                $stmtSelect = $pdo->prepare("SELECT * FROM orders WHERE order_id = ?");
                $stmtSelect->execute([$orderId]);
                $rows = $stmtSelect->fetchAll(PDO::FETCH_ASSOC);

                if (!empty($rows)) {
                    // Check if order already exists in completed_orders (prevent duplicates)
                    $duplicateCheckStmt = $pdo->prepare("SELECT COUNT(*) FROM completed_orders WHERE order_id = ?");
                    $duplicateCheckStmt->execute([$orderId]);
                    $duplicateCount = $duplicateCheckStmt->fetchColumn();
                    
                    if ($duplicateCount == 0) {
                        // Prepare insert statement for completed_orders
                        $stmtInsert = $pdo->prepare("INSERT INTO completed_orders (order_id, user_id, product_name, quantity, price, total, mop, reference_number, status, order_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

                        foreach ($rows as $row) {
                            $stmtInsert->execute([
                                $row['order_id'],
                                $row['user_id'],
                                $row['product_name'],
                                $row['quantity'],
                                $row['price'],
                                $row['total'],
                                $row['mop'],
                                $row['reference_number'],
                                $newStatus, // Use the new status
                                $row['order_date'],
                            ]);
                        }
                    }

                    // Delete from original orders table
                    $stmtDelete = $pdo->prepare("DELETE FROM orders WHERE order_id = ?");
                    $stmtDelete->execute([$orderId]);
                }
                
            } else {
                // For non-final statuses (to-receive, waiting), just update the status
                $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE order_id = ?");
                $stmt->execute([$newStatus, $orderId]);
            }

            $pdo->commit();
            
            // Redirect back to the orders page
            header("Location: ../admin/src/index.php?success=status_updated");
            exit();
            
        } catch (PDOException $e) {
            $pdo->rollBack();
            error_log("Error updating order status: " . $e->getMessage());
            header("Location: ../admin/src/index.php?error=update_failed");
            exit();
        }
    } else {
        header("Location: ../admin/src/index.php?error=invalid_request");
        exit();
    }
} else {
    header("Location: ../admin/src/index.php?error=invalid_method");
    exit();
}
?>