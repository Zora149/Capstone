<?php
session_start();
require_once 'connection/db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo "<script>alert('Please login first');</script>";
    header('Location: context/Login.php');
    exit;
}

// Function to fetch orders based on status
function fetchOrders($pdo, $userId, $status = null) {
    $sql = "SELECT * FROM orders WHERE user_id = ?";
    $params = [$userId];
    
    if ($status !== null) {
        $sql .= " AND status = ?";
        $params[] = $status;
    }
    
    $sql .= " ORDER BY order_date DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function fetchCompletedOrders($pdo, $userId, $status = null) {
  try {
    $sql = "SELECT * FROM completed_orders WHERE user_id = ?";
    $params = [$userId];
    
    if ($status !== null) {
        $sql .= " AND status = ?";
        $params[] = $status;
    }
    
    $sql .= " ORDER BY order_date DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  } catch (PDOException $e) {
    // If completed_orders table doesn't exist, return empty array
    error_log("Completed_orders table not found or error: " . $e->getMessage());
    return [];
  }
}

// Initialize last check session if not set
if (!isset($_SESSION['last_order_check'])) {
    $_SESSION['last_order_check'] = date('Y-m-d H:i:s');
}

// Fetch all orders for the logged-in user
$userId = $_SESSION['user_id'];
$allOrders = fetchOrders($pdo, $userId);
$completedOrders = fetchCompletedOrders($pdo, $userId);
$allOrdersAndCompleted = array_merge($allOrders, $completedOrders);

?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <title>Order Dashboard</title>
  <!-- Google Font (Optional) -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link
    href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600&display=swap"
    rel="stylesheet">
  <link rel="stylesheet" href="./css/transaction.css" />
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

    /* Cancel link styling */
    .cancel-link {
      color: #dc3545;
      text-decoration: none;
      font-size: 0.9em;
      margin-top: 5px;
      display: inline-block;
    }

    .cancel-link:hover {
      color: #c82333;
      text-decoration: underline;
    }

    /* Loading state */
    .loading {
      opacity: 0.6;
      pointer-events: none;
    }

    /* Notification styles */
    .notification {
      position: fixed;
      top: 20px;
      right: 20px;
      background: #28a745;
      color: white;
      padding: 15px 20px;
      border-radius: 5px;
      z-index: 1050;
      display: none;
    }

    .notification.error {
      background: #dc3545;
    }

    .notification.show {
      display: block;
      animation: slideIn 0.3s ease-in-out;
    }

    @keyframes slideIn {
      from {
        transform: translateX(100%);
      }
      to {
        transform: translateX(0);
      }
    }
  </style>
</head>

<body>
  <?php include './components/header.php'; ?>
  
  <!-- Notification container -->
  <div id="notification" class="notification"></div>

  <!-- TABS -->
  <div class="transaction-container">
    <div class="tabs">
      <button class="tab-button active" data-tab="all">All</button>
      <button class="tab-button" data-tab="waiting">Waiting</button>
      <button class="tab-button" data-tab="to-receive">To Receive</button>
      <button class="tab-button" data-tab="completed">Completed</button>
      <button class="tab-button" data-tab="cancelled">Cancelled</button>
    </div>

    <!-- TAB CONTENT: ALL -->
    <div class="tab-content active" id="all">
      <table class="order-table">
        <thead>
          <tr>
            <th>Product</th>
            <th>Mode of Payment</th>
            <th>Status</th>
            <th>Total</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($allOrdersAndCompleted as $order): ?>
          <tr data-order-id="<?= $order['order_id'] ?>">
            <td class="product-cell">
              <div>
                <p class="product-name"><?= htmlspecialchars($order['product_name']) ?></p>
                <?php if ($order['status'] !== 'completed' && $order['status'] !== 'cancelled'): ?>
                <a href="#" class="cancel-link" data-order-id="<?= $order['order_id'] ?>">Cancel</a>
                <?php endif; ?>
              </div>
            </td>
            <td><?= htmlspecialchars($order['mop']) ?></td>
            <td class="status-cell"><?= ucfirst(htmlspecialchars($order['status'])) ?></td>
            <td>₱<?= number_format($order['total'], 2) ?></td>
            <td><button class="btn btn-primary view-receipt-btn" data-order='<?= json_encode($order) ?>'>View Receipt</button></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <!-- TAB CONTENT: WAITING -->
    <div class="tab-content" id="waiting">
      <table class="order-table">
        <thead>
          <tr>
            <th>Product</th>
            <th>Mode of Payment</th>
            <th>Status</th>
            <th>Total</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php
          $waitingOrders = array_filter($allOrders, function($order) {
            return $order['status'] === 'waiting';
          });
          ?>
          <?php foreach ($waitingOrders as $order): ?>
          <tr data-order-id="<?= $order['order_id'] ?>">
            <td class="product-cell">
              <div>
                <p class="product-name"><?= htmlspecialchars($order['product_name']) ?></p>
                <a href="#" class="cancel-link" data-order-id="<?= $order['order_id'] ?>">Cancel</a>
              </div>
            </td>
            <td><?= htmlspecialchars($order['mop']) ?></td>
            <td class="status-cell"><?= ucfirst(htmlspecialchars($order['status'])) ?></td>
            <td>₱<?= number_format($order['total'], 2) ?></td>
            <td><button class="btn btn-primary view-receipt-btn" data-order='<?= json_encode($order) ?>'>View Receipt</button></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <!-- TAB CONTENT: TO RECEIVE -->
    <div class="tab-content" id="to-receive">
      <table class="order-table">
        <thead>
          <tr>
            <th>Product</th>
            <th>Mode of Payment</th>
            <th>Status</th>
            <th>Total</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php
          $toReceiveOrders = array_filter($allOrders, function($order) {
            return $order['status'] === 'to-receive';
          });
          ?>
          <?php foreach ($toReceiveOrders as $order): ?>
          <tr data-order-id="<?= $order['order_id'] ?>">
            <td class="product-cell">
              <div>
                <p class="product-name"><?= htmlspecialchars($order['product_name']) ?></p>
                <a href="#" class="cancel-link" data-order-id="<?= $order['order_id'] ?>">Cancel</a>
              </div>
            </td>
            <td><?= htmlspecialchars($order['mop']) ?></td>
            <td class="status-cell"><?= ucfirst(htmlspecialchars($order['status'])) ?></td>
            <td>₱<?= number_format($order['total'], 2) ?></td>
            <td><button class="btn btn-primary view-receipt-btn" data-order='<?= json_encode($order) ?>'>View Receipt</button></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <!-- TAB CONTENT: COMPLETED -->
    <div class="tab-content" id="completed">
      <table class="order-table">
        <thead>
          <tr>
            <th>Product</th>
            <th>Mode of Payment</th>
            <th>Status</th>
            <th>Total</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php
          $completedOrdersFiltered = array_filter($completedOrders, function($order) {
            return $order['status'] === 'completed';
          });
          ?>
          <?php foreach ($completedOrdersFiltered as $order): ?>
          <tr data-order-id="<?= $order['order_id'] ?>">
            <td class="product-cell">
              <div>
                <p class="product-name"><?= htmlspecialchars($order['product_name']) ?></p>
              </div>
            </td>
            <td><?= htmlspecialchars($order['mop']) ?></td>
            <td class="status-cell"><?= ucfirst(htmlspecialchars($order['status'])) ?></td>
            <td>₱<?= number_format($order['total'], 2) ?></td>
            <td><button class="btn btn-primary view-receipt-btn" data-order='<?= json_encode($order) ?>'>View Receipt</button></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <!-- TAB CONTENT: CANCELLED -->
    <div class="tab-content" id="cancelled">
      <table class="order-table">
        <thead>
          <tr>
            <th>Product</th>
            <th>Mode of Payment</th>
            <th>Status</th>
            <th>Total</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php
          $cancelledOrders = array_filter($allOrdersAndCompleted, function($order) {
            return $order['status'] === 'cancelled';
          });
          ?>
          <?php foreach ($cancelledOrders as $order): ?>
          <tr data-order-id="<?= $order['order_id'] ?>">
            <td class="product-cell">
              <div>
                <p class="product-name"><?= htmlspecialchars($order['product_name']) ?></p>
              </div>
            </td>
            <td><?= htmlspecialchars($order['mop']) ?></td>
            <td class="status-cell"><?= ucfirst(htmlspecialchars($order['status'])) ?></td>
            <td>₱<?= number_format($order['total'], 2) ?></td>
            <td><button class="btn btn-primary view-receipt-btn" data-order='<?= json_encode($order) ?>'>View Receipt</button></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
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
          <div class="receipt-item">
            <span>Product:</span>
            <span id="receipt-product"></span>
          </div>
          <div class="receipt-item">
            <span>Quantity:</span>
            <span id="receipt-quantity">1</span>
          </div>
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

  <?php include './components/footer.php'; ?>
  
  <script>
    const userId = <?= json_encode($userId) ?>;
    
    // Notification functions
    function showNotification(message, type = 'success') {
      const notification = document.getElementById('notification');
      notification.textContent = message;
      notification.className = `notification ${type} show`;
      
      setTimeout(() => {
        notification.classList.remove('show');
      }, 4000);
    }

    // Modal functionality
    const modal = document.getElementById('receiptModal');
    const closeBtn = document.querySelector('.close');
    
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
      document.getElementById('receipt-order-id').textContent = '#' + order.order_id;
      document.getElementById('receipt-date').textContent = order.delivery_date || new Date().toLocaleDateString();
      document.getElementById('receipt-time').textContent = order.delivery_time || new Date().toLocaleTimeString();
      document.getElementById('receipt-status').textContent = order.status.charAt(0).toUpperCase() + order.status.slice(1);
      document.getElementById('receipt-payment').textContent = order.mop;
      document.getElementById('receipt-reference').textContent = order.reference_number || 'N/A';
      document.getElementById('receipt-product').textContent = order.product_name;
      document.getElementById('receipt-quantity').textContent = order.quantity || 1;
      document.getElementById('receipt-total').textContent = '₱' + parseFloat(order.total).toLocaleString('en-US', {minimumFractionDigits: 2});
      
      modal.style.display = 'block';
    }

    // Cancel order functionality
    function cancelOrder(orderId, linkElement) {
      if (!confirm('Are you sure you want to cancel this order?')) {
        return;
      }

      const row = linkElement.closest('tr');
      row.classList.add('loading');

      fetch('cancel_order.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          order_id: orderId
        })
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          showNotification('Order cancelled successfully');
          // Update the row to show cancelled status
          const statusCell = row.querySelector('.status-cell');
          statusCell.textContent = 'Cancelled';
          // Remove cancel link
          linkElement.remove();
          // Refresh after a short delay to show updated data
          setTimeout(() => {
            location.reload();
          }, 1500);
        } else {
          showNotification('Failed to cancel order: ' + data.message, 'error');
        }
      })
      .catch(error => {
        console.error('Error:', error);
        showNotification('An error occurred while canceling the order', 'error');
      })
      .finally(() => {
        row.classList.remove('loading');
      });
    }

    // Check for new orders periodically
    function checkForNewOrders() {
      fetch('check_new_orders.php')
        .then(response => response.json())
        .then(data => {
          if (data.hasNewOrders) {
            showNotification('New order updates available. Page will refresh shortly.');
            setTimeout(() => {
              location.reload();
            }, 2000);
          }
        })
        .catch(error => {
          console.error('Error checking for updates:', error);
        });
    }

    // Initialize event listeners
    function initializeEventListeners() {
      // View receipt buttons
      document.querySelectorAll('.view-receipt-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
          e.preventDefault();
          const orderData = JSON.parse(this.getAttribute('data-order'));
          showReceipt(orderData);
        });
      });

      // Cancel links
      document.querySelectorAll('.cancel-link').forEach(link => {
        link.addEventListener('click', function(e) {
          e.preventDefault();
          const orderId = this.getAttribute('data-order-id');
          cancelOrder(orderId, this);
        });
      });
    }

    // Initialize when page loads
    document.addEventListener('DOMContentLoaded', function() {
      initializeEventListeners();
      
      // Check for new orders every 30 seconds
      setInterval(checkForNewOrders, 30000);
    });
  </script>
  <script src="transaction.js"></script>
  <script src="script.js"></script>
</body>

</html>