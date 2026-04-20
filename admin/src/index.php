<?php
require_once 'middleware.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="./css/global.css">
    <title>Chicken Whole Sale Dashboard</title>
    <style>
        /* Sidebar styles */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: 250px;
            z-index: 1030;
            transition: transform 0.3s ease-in-out;
        }
        
        .sidebar-hidden {
            transform: translateX(-100%);
        }
        
        .main-content {
            margin-left: 250px;
            transition: margin-left 0.3s ease-in-out;
        }
        
        .main-content-expanded {
            margin-left: 0;
        }
        
        .toggle-btn {
            position: fixed;
            top: 10px;
            left: 10px;
            z-index: 1040;
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 8px 12px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            display: none;
        }
        
        /* Mobile responsive */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .toggle-btn {
                display: block;
            }
            
            .sidebar.show {
                transform: translateX(0);
            }
        }
        
        /* Overlay for mobile */
        .sidebar-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1025;
            display: none;
        }
        
        @media (max-width: 768px) {
            .sidebar-overlay.show {
                display: block;
            }
        }

        /* Receipt Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 2000;
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
</head>

<body>
    <!-- Toggle Button -->
    <button class="toggle-btn" id="toggleSidebar">
        <i class="fa-solid fa-bars"></i>
    </button>
    
    <!-- Sidebar Overlay for mobile -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    
    <!-- Sidebar -->
    <div class="sidebar bg-white shadow-sm d-flex flex-column p-3" id="sidebar">
        <img src="../../assets/img/Logo.png" alt="Logo" class="img-fluid mb-3 mx-auto" style="width: 100px; height: 100px;">
        <a href="#" data-page="dashboard.php" class="nav-link text-dark custom-padding active">Dashboard</a>
        <a href="#" data-page="products.php" class="nav-link text-dark custom-padding">Products</a>
        <a href="#" data-page="orders.php" class="nav-link text-dark custom-padding">Orders</a>
        <a href="#" data-page="records.php" class="nav-link text-dark custom-padding">Records Management</a>
    </div>

    <!-- Main Content -->
    <div class="main-content flex-grow-1 p-4" id="main-content">
        <?php include 'dashboard.php'; ?>
    </div>

    <!-- New Order Alert Modal -->
    <div class="modal fade" id="newOrderModal" tabindex="-1" aria-labelledby="newOrderLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content text-center p-4">
                <h4 class="text-success mb-3"><i class="fa-solid fa-bell"></i> New Order Received!</h4>
                <p>A new order has been placed.</p>
                <button class="btn btn-primary" onclick="location.reload()">View Now</button>
            </div>
        </div>
    </div>

    <!-- Receipt Modal -->
    <div id="receiptModal" class="modal">
        <div class="modal-content">
            <span class="close" id="closeReceiptModal">&times;</span>
            <div class="receipt">
                <div class="receipt-header">
                    <div class="receipt-title">RECEIPT</div>
                    <div>Chicken Wholesale</div>
                    <div>123 Store Address</div>
                    <div>Phone: (123) 456-7890</div>
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
                        <span>Date:</span>
                        <span id="receipt-date"></span>
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
                    <p>Please keep this receipt for your records</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Include Chart.js before your script.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="script.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const toggleBtn = document.getElementById('toggleSidebar');
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('main-content');
            const overlay = document.getElementById('sidebarOverlay');
            
            // Toggle sidebar function
            function toggleSidebar() {
                sidebar.classList.toggle('show');
                overlay.classList.toggle('show');
            }
            
            // Hide sidebar function
            function hideSidebar() {
                sidebar.classList.remove('show');
                overlay.classList.remove('show');
            }
            
            // Toggle button click
            toggleBtn.addEventListener('click', toggleSidebar);
            
            // Overlay click to hide sidebar
            overlay.addEventListener('click', hideSidebar);
            
            // Hide sidebar when clicking on navigation links on mobile
            const navLinks = sidebar.querySelectorAll('.nav-link');
            navLinks.forEach(link => {
                link.addEventListener('click', function() {
                    if (window.innerWidth <= 768) {
                        hideSidebar();
                    }
                });
            });
            
            // Handle window resize
            window.addEventListener('resize', function() {
                if (window.innerWidth > 768) {
                    hideSidebar(); // Hide mobile overlay if switching to desktop
                }
            });
        });
    </script>

</body>

</html>