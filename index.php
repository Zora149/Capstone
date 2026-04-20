<?php
session_start();
require_once 'connection/db_connect.php';

// Fetch products from database
try {
    $stmt = $pdo->query("SELECT * FROM products");
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching products: " . $e->getMessage());
    $products = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css"
          integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg=="
          crossorigin="anonymous" referrerpolicy="no-referrer" />

    <link rel="stylesheet" href="./css/style.css">

    <title>EYC TRADING</title>

    <style>
        /* Out of Stock */
        .item.out-of-stock {
            position: relative;
            opacity: 0.6;
        }

        .item.out-of-stock .product-img img {
            filter: grayscale(70%);
        }

        .out-of-stock-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background: #dc3545;
            color: #fff;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            z-index: 2;
            text-transform: uppercase;
        }

        /* Low Stock */
        .item.low-stock {
            position: relative;
            border: 2px solid #ffc107;
        }

        .low-stock-badge {
            position: absolute;
            top: 10px;
            left: 10px;
            background: #ffc107;
            color: #212529;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            z-index: 2;
            text-transform: uppercase;
        }

        /* Disabled Buttons */
        .btn button:disabled {
            background-color: #6c757d !important;
            border-color: #6c757d !important;
            cursor: not-allowed;
            opacity: 0.65;
        }
    </style>
</head>

<body>

<?php include './components/header.php'; ?>

<section class="main-section">
    <div class="image-container">
        <img src="./assets/img/main.png" alt="Centered Image">
    </div>
</section>

<!-- Product Section -->
<section class="product-section">
    <div class="product">
        <h1>Product</h1>

        <div class="container-item">
            <?php if (!empty($products)): ?>
                <?php foreach ($products as $product): ?>

                    <?php
                        $isOutOfStock = $product['quantity'] <= 0;
                        $isLowStock   = $product['quantity'] > 0 && $product['quantity'] <= 5;
                    ?>

                    <div class="item <?= $isOutOfStock ? 'out-of-stock' : ($isLowStock ? 'low-stock' : '') ?>">

                        <?php if ($isOutOfStock): ?>
                            <div class="out-of-stock-badge">
                                <i class="fa-solid fa-ban"></i> Out of Stock
                            </div>
                        <?php elseif ($isLowStock): ?>
                            <div class="low-stock-badge">
                                <i class="fa-solid fa-triangle-exclamation"></i>
                                Only <?= (int)$product['quantity'] ?> left
                            </div>
                        <?php endif; ?>

                        <div class="product-img">
                            <?php if (!empty($product['image_path']) && file_exists($product['image_path'])): ?>
                                <img src="<?= htmlspecialchars($product['image_path']) ?>"
                                     alt="<?= htmlspecialchars($product['product_name']) ?>"
                                     onerror="this.src='assets/img/default_product.png'">
                            <?php else: ?>
                                <img src="assets/img/default_product.png" alt="Default Product Image">
                            <?php endif; ?>
                        </div>

                        <div class="product-info">
                            <h2><?= htmlspecialchars($product['product_name']) ?></h2>
                            <p>Price: ₱<?= number_format($product['price'], 2) ?></p>

                            <div class="btn">
                                <button class="add-to-cart-btn"
                                        data-id="<?= $product['id'] ?>"
                                        <?= $isOutOfStock ? 'disabled title="This item is out of stock"' : '' ?>>
                                    Cart <i class="fa-solid fa-cart-plus"></i>
                                </button>

                                <form action="buy_now.php" method="POST" style="display:inline;">
                                    <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                                    <button type="submit"
                                            <?= $isOutOfStock ? 'disabled title="This item is out of stock"' : '' ?>>
                                        Buy Now
                                    </button>
                                </form>
                            </div>
                        </div>

                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="no-products">No products available at the moment.</p>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Location -->
<section class="location-section">
    <h1>Location</h1>
    <div class="map-container">
        <iframe
            src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d1960.031332517889!2d122.52485247945641!3d10.729650246696693!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x33aefad82604f5c7%3A0x2c359358e032764b!2sQmf%20Subdivision!5e0!3m2!1sen!2sph!4v1739532453879!5m2!1sen!2sph"
            width="100%"
            height="450"
            style="border:0;"
            allowfullscreen
            loading="lazy"
            referrerpolicy="no-referrer-when-downgrade">
        </iframe>
    </div>
</section>

<?php include './components/footer.php'; ?>

<script src="script.js"></script>

</body>
</html>
