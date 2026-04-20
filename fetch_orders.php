<?php
session_start();
require_once '../connection/db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'User not logged in']);
    exit;
}

$status = $_GET['status'] ?? null;
$userId = $_SESSION['user_id'];

try {
    // Fetch from orders table
    $sql = "SELECT * FROM orders WHERE user_id = ?";
    $params = [$userId];
    
    if ($status !== null) {
        $sql .= " AND status = ?";
        $params[] = $status;
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Also fetch from completed_orders table (if it exists)
    $completedOrders = [];
    try {
        $sql2 = "SELECT * FROM completed_orders WHERE user_id = ?";
        $params2 = [$userId];
        
        if ($status !== null) {
            $sql2 .= " AND status = ?";
            $params2[] = $status;
        }
        
        $stmt2 = $pdo->prepare($sql2);
        $stmt2->execute($params2);
        $completedOrders = $stmt2->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // If completed_orders table doesn't exist, just continue with empty array
        error_log("Completed_orders table not found or error: " . $e->getMessage());
    }
    
    // Combine both results
    $allOrders = array_merge($orders, $completedOrders);
    
    echo json_encode($allOrders);
} catch (PDOException $e) {
    error_log("Error fetching orders: " . $e->getMessage());
    echo json_encode(['error' => 'Database error']);
}