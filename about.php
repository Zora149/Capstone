<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
</head>

<body class="d-flex flex-column min-vh-100">
    <?php include './components/header.php'; ?>
    
   <main class="container my-5 flex-grow-1 border rounded shadow p-5 bg-white">

        <div class="text-center">
            <img src="./assets/img/Logo.png" alt="Placeholder Image" class="img-fluid rounded-circle mb-4" style="width: 150px; height: 150px;">
            <h1 class="mb-4">About Us</h1>
            <p class="text-start lead">
                EYC Trading. has been serving the Iloilo community since its establishment on June 23, 2009. Founded with a
                commitment to providing high-quality poultry products, our journey began with the sale of fresh eggs.
                However, recognizing the need to diversify and better serve our customers, we soon expanded our offerings.

                Initially facing modest sales with eggs, we adapted and broadened our product line to include a variety of
                chicken products such as chicken feet, chicken heads, and other parts of the chicken. This shift allowed us
                to cater to a wider range of culinary preferences and meet the growing demands of our customers.

                Our success story is built on the foundation of hard work and perseverance. Starting with store-to-store
                sales, we dedicated ourselves to building strong relationships with our customers and ensuring their
                satisfaction. Through this hands-on approach, we have steadily grown and established a reputable presence in
                the local market.

                Today, EYC Trading continues to uphold its tradition of quality and service. We take pride in our journey
                and remain committed to delivering the finest poultry products to our valued customers.
            </p>
        </div>
    </main>

    <?php include './components/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="script.js"></script>
</body>

</html>