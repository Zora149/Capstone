<?php
session_start();
require_once 'connection/db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: context/Login.php');
    exit;
}

// Fetch orders for the logged-in user
try {
    $stmt = $pdo->prepare("
        SELECT c.*, p.product_name, p.price, p.image_path 
        FROM cart c
        JOIN products p ON c.product_id = p.id
        WHERE c.user_id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching cart items: " . $e->getMessage());
    $orders = [];
}


// 👇 ADD THIS BLOCK HERE
try {
    $stmt = $pdo->prepare("SELECT mobile FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    $userMobile = $user ? $user['mobile'] : '';
} catch (PDOException $e) {
    error_log("Error fetching user mobile: " . $e->getMessage());
    $userMobile = '';
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cart UI</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="./css/cart.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" />
    <style>
        /* Clean and Simple Schedule Card */
        .delivery-schedule-card {
            background: #ffffff;
            border: 1px solid #e5e7eb !important;
            transition: all 0.2s ease;
        }

        .delivery-schedule-card:hover {
            border-color: #0d6efd !important;
        }

        .schedule-icon-wrapper {
            width: 45px;
            height: 45px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #e7f1ff;
            border-radius: 10px;
        }

        .datetime-input-container {
            position: relative;
        }

        .datetime-input {
            width: 100%;
            padding: 12px 16px;
            font-size: 0.95rem;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            transition: all 0.2s ease;
            background-color: #f9fafb;
        }

        .datetime-input:hover {
            border-color: #cbd5e1;
            background-color: #ffffff;
        }

        .datetime-input:focus {
            outline: none;
            border-color: #0d6efd;
            background-color: #ffffff;
            box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.1);
        }

        .datetime-input.is-invalid {
            border-color: #dc3545;
            background-color: #fff5f5;
        }

        .datetime-input::-webkit-calendar-picker-indicator {
            cursor: pointer;
            padding: 8px;
            margin-left: 8px;
            opacity: 0.5;
            transition: all 0.2s ease;
        }

        .datetime-input::-webkit-calendar-picker-indicator:hover {
            opacity: 1;
            background-color: #e7f1ff;
            border-radius: 6px;
        }

        /* Error message styling */
        .invalid-feedback {
            color: #dc3545;
            font-size: 0.875rem;
            margin-top: 6px;
            display: none;
        }

        .invalid-feedback.d-block {
            display: block !important;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .delivery-schedule-card {
                padding: 1rem !important;
            }

            .schedule-icon-wrapper {
                width: 40px;
                height: 40px;
            }

            .datetime-input {
                padding: 10px 14px;
                font-size: 0.9rem;
            }
        }

        @media (max-width: 576px) {
            .delivery-schedule-card {
                padding: 0.875rem !important;
            }

            .schedule-icon-wrapper {
                width: 38px;
                height: 38px;
            }

            .schedule-icon-wrapper i {
                font-size: 1.1rem !important;
            }

            .datetime-input {
                padding: 10px 12px;
                font-size: 0.875rem;
            }

            .delivery-schedule-card h6 {
                font-size: 0.95rem;
            }

            .delivery-schedule-card small {
                font-size: 0.8rem;
            }
        }
    </style>
</head>

<body>
    <?php include './components/header.php'; ?>
    <div class="container">
        <div class="cart">
            <div class="cart-header">
                <h2>Cart</h2>
                <div class="items-count">&#128722; <?= count($orders) ?> items</div>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Details</th>
                        <th>Quantity</th>
                        <th>Price</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($orders)): ?>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td>
                                    <img src="<?= htmlspecialchars($order['image_path']) ?>"
                                        alt="<?= htmlspecialchars($order['product_name']) ?>"
                                        class="product-img"
                                        onerror="this.src='assets/img/default_product.png'">
                                </td>
                                <td>
                                    <p><?= htmlspecialchars($order['product_name']) ?></p>
                                    <p class="remove" onclick="removeFromCart(<?= $order['id'] ?>)">Remove</p>
                                </td>
                                <td>
                                    <div class="quantity">
                                        <button onclick="decreaseQuantity(this, <?= $order['id'] ?>)">-</button>
                                        <p><?= $order['quantity'] ?></p>
                                        <button onclick="increaseQuantity(this, <?= $order['id'] ?>)">+</button>
                                    </div>
                                </td>
                                <td class="price">₱<?= number_format($order['price'], 2) ?></td>
                                <td class="total-price">₱<?= number_format($order['price'] * $order['quantity'], 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center">Your cart is empty</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="total">
            <h2>Total</h2>
            <div class="total-summary">
                <?php if (!empty($orders)): ?>
                    <?php
                    $total = 0;
                    $totalQuantity = 0;
                    foreach ($orders as $order):
                        $itemTotal = $order['price'] * $order['quantity'];
                        $total += $itemTotal;
                        $totalQuantity += $order['quantity'];
                    ?>
                        <div>
                            <span><?= htmlspecialchars($order['product_name']) ?> (<?= $order['quantity'] ?>x)</span>
                            <span class="cart-Price">₱<?= number_format($itemTotal, 2) ?></span>
                        </div>
                    <?php endforeach; ?>

                    <!-- Delivery or Pick-Up UI -->
                    <div class="alert mt-4 <?= $totalQuantity < 5 ? 'alert-warning' : 'alert-success' ?> d-flex align-items-center gap-3 shadow-sm rounded-3" role="alert">
                        <?php if ($totalQuantity < 5): ?>
                            <i class="fa-solid fa-bag-shopping fs-3 text-warning"></i>
                            <div>
                                <h5 class="mb-1 fw-bold">Pick-Up Order</h5>
                                <p class="mb-0">Your order is less than 5 items and must be picked up at our store location.</p>
                            </div>
                        <?php else: ?>
                            <i class="fa-solid fa-truck fs-3 text-success"></i>
                            <div>
                                <h5 class="mb-1 fw-bold">Can Deliver</h5>
                                <p class="mb-0">You have ordered 5 or more items. Home delivery is available.</p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Enhanced Schedule Setup -->
                    <div class="d-flex flex-column  delivery-schedule-card mt-4 p-3 border rounded-3 bg-white shadow-sm">
                        <div class="d-flex align-items-center">
                            <div class="me-3 p-2">
                                <i class="fa-solid fa-calendar-days text-primary fs-4"></i>
                            </div>
                            <div>
                                <h6 class="mb-0 fw-bold"><?= ($totalQuantity < 5) ? 'Pick-Up Schedule' : 'Delivery Schedule' ?></h6>
                               
                            </div>
                        </div>
                        
                        <div class="datetime-input-container d-flex flex-column gap-3">
                        <small class="text-muted">Select your preferred date & time</small>
                            <input
                                type="datetime-local"
                                class="form-control datetime-input"
                                id="deliveryDate"
                                name="deliveryDate"
                                required
                                placeholder="Select date and time">
                        </div>
                        
                        <div class="mt-3 flex flex-column">
                            <label for="contactNumber" class="form-label fw-bold ">Contact Number</label>
                            <?php if (!empty($userMobile)): ?>
    <!-- If user already has a mobile number -->
    <input
        type="tel"
        class="form-control datetime-input"
        id="contactNumber"
        name="contactNumber"
        value="<?= htmlspecialchars($userMobile) ?>"
        readonly
    >
    <small class="text-muted m-2">Your registered mobile number will be used for delivery updates.</small>
<?php else: ?>
    <!-- If user has no mobile number yet -->
    <input
        type="tel"
        class="form-control datetime-input"
        id="contactNumber"
        name="contactNumber"
        placeholder="Enter your 11-digit contact number"
        maxlength="11"
        pattern="[0-9]{11}"
        required
    >
    <small class="text-danger m-2">No mobile number on file. Please enter your contact number.</small>
<?php endif; ?>

                        </div>
                    </div>

                    <div class="total mt-4">
                        <span>Total</span>
                        <span>₱<?= number_format($total, 2) ?></span>
                    </div>
                <?php else: ?>
                    <div class="total">
                        <span>Total</span>
                        <span>₱0.00</span>
                    </div>
                <?php endif; ?>
            </div>

            <div class="buttons mt-3">
                <button class="pay-now" <?= empty($orders) ? 'disabled' : '' ?> onclick="setDeliverySchedule('pay-now')">Pay Now</button>
                <button class="pay-later" <?= empty($orders) ? 'disabled' : '' ?> onclick="setDeliverySchedule('pay-later')">Pay Later</button>
            </div>
        </div>
    </div>

    <?php include './components/footer.php'; ?>

    <!-- GCash Modal -->
    <div id="gcashModal" class="modal">
        <div class="modal-content">
            <span class="close-btn" onclick="closeModal()">&times;</span>
            <!--<img class="GCash_logo" src="https://upload.wikimedia.org/wikipedia/commons/thumb/5/52/GCash_logo.svg/2560px-GCash_logo.svg.png" alt="GCash Logo">-->
            <img src="assets/img/QR.jpg" alt="QR Code" class="qr-code">
            <!--<p>09919658884</p>-->
            <p>Please scan the QR code to complete your payment.</p>
            <div class="gcash-reference">
                <label for="gcashRef">GCash Reference #:</label>
                <input type="text" id="gcashRef" name="gcashRef" placeholder="Enter your GCash reference number" required>
            </div>
            <button class="done-btn">Payment Done</button>
        </div>
    </div>

    <!-- Pay Later Modal -->
    <div id="payLaterModal" class="modal">
        <div class="modal-content">
            <span class="close-btn" onclick="closePayLaterModal()">&times;</span>
            <h3>Pay Later</h3>
            <p>Please Present to the shop the Reference number to verify your order.</p>
            <div class="reference-number">
                <p>Reference Number: </p>
                <p id="referenceNumber" class="fs-1 fw-bold">ref #12345678</p>
            </div>
            <p>Take a Screenshot</p>
            <p>Get your order within 12hrs or else it will be terminated</p>
            <button class="done-btn" id="orderNowButton">ORDER NOW</button>
        </div>
    </div>

    <!-- Success Modal -->
    <div class="modal fade" id="successModal" tabindex="-1" aria-labelledby="successModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content text-center p-4">
                <h1 class="text-success mb-3"><i class="fa-solid fa-circle-check"></i></h1>
                <h4 class="mb-3">Order Successful!</h4>
                <p>Your order has been placed successfully.</p>
                <button class="btn btn-success mt-2" onclick="window.location.href='index.php'">Go to Home</button>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/5.3.0/js/bootstrap.min.js"></script>
    <script src="./script.js"></script>

    <script>
    function setDeliverySchedule(action) {
        const dateInput = document.getElementById('deliveryDate');
        const container = dateInput.closest('.datetime-input-container');

        if (!dateInput.value) {
            // Show error styling
            dateInput.classList.add('is-invalid');
            
            let errorMsg = container.querySelector('.invalid-feedback');
            if (!errorMsg) {
                errorMsg = document.createElement('div');
                errorMsg.className = 'invalid-feedback';
                errorMsg.innerHTML = '<i class="fa-solid fa-exclamation-circle me-1"></i> Please select a date and time';
                container.appendChild(errorMsg);
            }
            errorMsg.classList.add('d-block');
            
            dateInput.scrollIntoView({ behavior: 'smooth', block: 'center' });
            dateInput.focus();
            
            setTimeout(() => {
                dateInput.classList.remove('is-invalid');
                errorMsg.classList.remove('d-block');
            }, 3000);
            
            return false;
        }

        dateInput.classList.remove('is-invalid');
        const errorMsg = container.querySelector('.invalid-feedback');
        if (errorMsg) errorMsg.classList.remove('d-block');

        const selectedDateTime = dateInput.value;
        localStorage.setItem("deliveryDateTime", selectedDateTime);

        console.log("Schedule Set:", selectedDateTime);

        /* ----------------------------------------------
            🔔 POLICY ALERT BEFORE CHECKOUT
            "They cancel the order unless it's out of stock."
        ------------------------------------------------- */
        const policyMessage =
            "⚠️ IMPORTANT ORDER POLICY:\n\n" +
            "• Orders must be picked up or received within the allowed time.\n" +
            "• The shop has the right to CANCEL the order unless the item is OUT OF STOCK.\n\n" +
            "Do you agree to proceed with this policy?";

        if (!confirm(policyMessage)) {
            return false; // STOP checkout if user declines
        }
        /* ------------------------------------------- */

        // Continue with payment action if policy accepted
        if (action === 'pay-now') {
            document.getElementById('gcashModal').style.display = 'block';
        } else if (action === 'pay-later') {
            document.getElementById('payLaterModal').style.display = 'block';
        }

        return true;
    }

    // Set minimum date to today
    document.addEventListener('DOMContentLoaded', function() {
        const dateInput = document.getElementById('deliveryDate');
        if (dateInput) {
            const now = new Date();
            now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
            dateInput.min = now.toISOString().slice(0, 16);

            dateInput.addEventListener('change', function() {
                this.classList.remove('is-invalid');
                const errorMsg = this.closest('.datetime-input-container').querySelector('.invalid-feedback');
                if (errorMsg) errorMsg.classList.remove('d-block');
            });
        }
    });
</script>

</body>

</html>