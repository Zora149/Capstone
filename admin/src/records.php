<?php
require_once 'middleware.php';
require_once '../../connection/db_connect.php';

// Fetch completed and cancelled orders with user information, grouped by order_id
try {
    $stmt = $pdo->prepare("SELECT co.*, u.username 
        FROM completed_orders co
        JOIN users u ON co.user_id = u.id
        WHERE co.status IN ('completed', 'cancelled')
        ORDER BY FIELD(co.status, 'completed', 'cancelled'), co.order_date DESC, co.order_id DESC");
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
                'delivery_date' => $order['delivery_date'] ?? null,
                'delivery_time' => $order['delivery_time'] ?? null,
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

    // Completed first, then latest date/delivery on top (grouped array order is not guaranteed until sorted)
    $groupedList = array_values($groupedOrders);
    usort($groupedList, function ($a, $b) {
        $rank = function ($status) {
            if ($status === 'completed') {
                return 0;
            }
            if ($status === 'cancelled') {
                return 1;
            }
            return 2;
        };
        $cmp = $rank($a['status']) <=> $rank($b['status']);
        if ($cmp !== 0) {
            return $cmp;
        }
        $tsA = strtotime($a['delivery_date'] ?? $a['order_date'] ?? '1970-01-01');
        $tsB = strtotime($b['delivery_date'] ?? $b['order_date'] ?? '1970-01-01');
        if ($tsA !== $tsB) {
            return $tsB <=> $tsA;
        }
        return (int) ($b['order_id'] ?? 0) <=> (int) ($a['order_id'] ?? 0);
    });
    $groupedOrders = $groupedList;
} catch (PDOException $e) {
    error_log("Error fetching order records: " . $e->getMessage());
    $groupedOrders = [];
}
?>

<link rel="stylesheet" href="./css/global.css">
<style>
    /* Modal Styles */
    .modal {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0,0,0,0.5);
    }
    
    .modal-content {
        background-color: #fefefe;
        margin: 5% auto;
        padding: 20px;
        border: 1px solid #888;
        border-radius: 8px;
        width: 90%;
        max-width: 600px;
        max-height: 80vh;
        overflow-y: auto;
    }
    
    .close {
        color: #aaa;
        float: right;
        font-size: 28px;
        font-weight: bold;
        cursor: pointer;
    }
    
    .close:hover,
    .close:focus {
        color: #000;
        text-decoration: none;
    }
    
    .receipt {
        font-family: 'Courier New', monospace;
        max-width: 400px;
        margin: 0 auto;
        border: 2px solid #333;
        padding: 20px;
        background: white;
    }
    
    .receipt-header {
        text-align: center;
        border-bottom: 2px dashed #333;
        padding-bottom: 10px;
        margin-bottom: 15px;
    }
    
    .receipt-title {
        font-size: 20px;
        font-weight: bold;
        margin-bottom: 5px;
    }
    
    .receipt-info {
        margin-bottom: 15px;
        border-bottom: 1px dashed #333;
        padding-bottom: 10px;
    }
    
    .receipt-item {
        display: flex;
        justify-content: space-between;
        margin-bottom: 5px;
    }
    
    .receipt-items {
        margin-bottom: 15px;
        border-bottom: 1px dashed #333;
        padding-bottom: 10px;
    }
    
    .receipt-total {
        border-top: 2px solid #333;
        padding-top: 10px;
        margin-top: 10px;
        font-weight: bold;
        font-size: 18px;
    }
    
    .receipt-footer {
        text-align: center;
        margin-top: 20px;
        border-top: 1px dashed #333;
        padding-top: 10px;
        font-size: 12px;
    }
    
    .product-list {
        margin: 0;
        padding: 0;
    }
    
    .product-list li {
        list-style: none;
        margin-bottom: 5px;
        display: flex;
        justify-content: space-between;
        font-size: 14px;
    }
    
    .product-name {
        flex: 1;
    }
    
    .product-price {
        margin-left: 10px;
    }
    
</style>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Order Records</h1>
        <a href="export_records.php" class="btn btn-success">
            <i class="fas fa-file-excel"></i> Export to Excel
        </a>
    </div>
    <table class="table records-table">
        <thead class="table-light">
            <tr>
                <th>Order ID</th>
                <th>Customer</th>
                <th>Date</th>
                <th>Items</th>
                <th>Total</th>
                <th>Mode of Payment</th>
                <th>Reference Number</th>
                <th>Status</th>
                <th>View Receipt</th>
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
                            <ul class="mb-0">
                                <?php foreach ($order['items'] as $item): ?>
                                    <li>
                                        <?= htmlspecialchars($item['product_name']) ?>
                                        (Qty: <?= $item['quantity'] ?>)
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </td>
                        <td>₱<?= number_format($order['total'], 2) ?></td>
                        <td><?= htmlspecialchars($order['mop']) ?></td>
                        <td><?= htmlspecialchars($order['reference_number']) ?></td>
                        <td>
                            <span class="badge <?= $order['status'] === 'completed' ? 'bg-success' : 'bg-danger' ?>">
                                <?= ucfirst($order['status']) ?>
                            </span>
                        </td>
                        <td>
                            <button class="btn btn-primary view-receipt-btn" data-order='<?= json_encode($order) ?>'>
                                View Receipt
                            </button>
                            <!-- Debug: <?= json_encode($order) ?> -->
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="9" class="text-center">No completed or cancelled orders found</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Receipt Modal -->
<div id="receiptModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <div class="receipt">
            <div class="receipt-header">
                <div class="receipt-title">RECEIPT</div>
                <div>EYC TRADING</div>
                <div>QMF Subd., Mandurriao, Iloilo City</div>
                <div>Phone: 321-1961</div>
            </div>
            
            <div class="receipt-info">
                <div class="receipt-item">
                    <span>Order ID:</span>
                    <span id="receipt-order-id"></span>
                </div>
                <div class="receipt-item">
                    <span>Customer:</span>
                    <span id="receipt-customer"></span>
                </div>
                <div class="receipt-item">
                    <span>Delivery Date:</span>
                    <span id="receipt-date"></span>
                </div>
                <div class="receipt-item">
                    <span>Delivery Time:</span>
                    <span id="receipt-time"></span>
                </div>
                <div class="receipt-item">
                    <span>Status:</span>
                    <span id="receipt-status"></span>
                </div>
                <div class="receipt-item">
                    <span>Payment:</span>
                    <span id="receipt-payment"></span>
                </div>
                <div class="receipt-item">
                    <span>Reference:</span>
                    <span id="receipt-reference"></span>
                </div>
            </div>
            
            <div class="receipt-items">
                <div style="font-weight: bold; margin-bottom: 10px;">Items:</div>
                <ul class="product-list" id="receipt-products">
                    <!-- Products will be populated dynamically -->
                </ul>
            </div>
            
            <div class="receipt-total">
                <div class="receipt-item">
                    <span>TOTAL:</span>
                    <span id="receipt-total"></span>
                </div>
            </div>
            
            <div class="receipt-footer">
                <p>Thank you for your purchase!</p>
                <p>Please wait for the staff to call you about your order within 24hrs.</p>
                <p>Please keep this receipt for your records</p>
            </div>
        </div>
    </div>
</div>

<script>
    // Modal functionality
    const modal = document.getElementById('receiptModal');
    const closeBtn = document.querySelector('.close');
    const viewReceiptBtns = document.querySelectorAll('.view-receipt-btn');
    
    // Add event listeners to all "View Receipt" buttons
    viewReceiptBtns.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const orderData = JSON.parse(this.getAttribute('data-order'));
            showReceipt(orderData);
        });
    });
    
    // Close modal when clicking the X
    closeBtn.addEventListener('click', function() {
        modal.style.display = 'none';
    });
    
    // Close modal when clicking outside of it
    window.addEventListener('click', function(e) {
        if (e.target === modal) {
            modal.style.display = 'none';
        }
    });
    
    // Function to populate and show the receipt modal
    function showReceipt(order) {
        console.log('Order data:', order); // Debug log
        
        // Populate basic order information
        document.getElementById('receipt-order-id').textContent = '#' + order.order_id;
        document.getElementById('receipt-customer').textContent = order.username;
        
        // Handle date and time - check for delivery_date/delivery_time first, then fallback to order_date
        const deliveryDate = order.delivery_date || order.order_date;
        const deliveryTime = order.delivery_time || order.order_date;
        
        document.getElementById('receipt-date').textContent = deliveryDate ? new Date(deliveryDate).toLocaleDateString() : 'N/A';
        document.getElementById('receipt-time').textContent = deliveryTime ? new Date(deliveryTime).toLocaleTimeString() : 'N/A';
        document.getElementById('receipt-status').textContent = order.status.charAt(0).toUpperCase() + order.status.slice(1);
        document.getElementById('receipt-payment').textContent = order.mop;
        document.getElementById('receipt-reference').textContent = order.reference_number || 'N/A';
        document.getElementById('receipt-total').textContent = '₱' + parseFloat(order.total).toLocaleString('en-US', {minimumFractionDigits: 2});
        
        // Populate products list
        const productsList = document.getElementById('receipt-products');
        productsList.innerHTML = ''; // Clear existing items
        
        if (order.items && order.items.length > 0) {
            order.items.forEach(item => {
                const li = document.createElement('li');
                li.innerHTML = `
                    <div class="product-name">${item.product_name}</div>
                    <div class="product-price">
                        ${item.quantity} x ₱${parseFloat(item.price).toFixed(2)} = ₱${parseFloat(item.total).toFixed(2)}
                    </div>
                `;
                productsList.appendChild(li);
            });
        } else {
            // Fallback if no items
            const li = document.createElement('li');
            li.innerHTML = `
                <div class="product-name">No items found</div>
                <div class="product-price">N/A</div>
            `;
            productsList.appendChild(li);
        }
        
        // Show the modal
        modal.style.display = 'block';
    }
</script>