<?php
$current_page = basename($_SERVER['PHP_SELF']);
// Base path for links: use '../' when included from context/ (e.g. Login.php), else './'
if (!isset($base)) {
    $base = (strpos($_SERVER['PHP_SELF'], '/context/') !== false || strpos($_SERVER['PHP_SELF'], '\\context\\') !== false) ? '../' : './';
}
// Ensure session is available for login/logout state (pages should call session_start() before including header)
$is_logged_in = (function_exists('session_status') && session_status() === PHP_SESSION_ACTIVE && isset($_SESSION['username']));
?>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg==" crossorigin="anonymous" referrerpolicy="no-referrer" />

<header class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top">
    <div class="container-fluid">
        <!-- Logo -->
        <a class="navbar-brand" href="<?= htmlspecialchars($base) ?>index.php">
            <img src="<?= htmlspecialchars($base) ?>assets/img/Logo.png" alt="logo" width="100">
        </a>

        <!-- Hamburger Menu -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Main Navigation -->
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav mx-auto">
                <li class="nav-item">
                    <a class="nav-link <?= $current_page == 'index.php' ? 'active' : '' ?>" href="<?= htmlspecialchars($base) ?>index.php">Home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $current_page == 'transaction.php' ? 'active' : '' ?>" href="<?= htmlspecialchars($base) ?>transaction.php">Transaction</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $current_page == 'about.php' ? 'active' : '' ?>" href="<?= htmlspecialchars($base) ?>about.php">About</a>
                </li>
            </ul>

            <!-- Login Section -->
            <div class="d-flex align-items-center">
                <!-- Cart -->
                <div class="position-relative me-3">
                    <a href="<?= htmlspecialchars($base) ?>cart.php" class="text-dark">
                        <i class="fa-brands fa-opencart fs-5"></i>
                        <span id="cart-count" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-warning">0</span>
                    </a>
                </div>

                <!-- User Section -->
                <?php if ($is_logged_in): ?>
                    <div class="dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            Welcome, <?= htmlspecialchars($_SESSION['username']) ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="<?= htmlspecialchars($base) ?>settings.php">Settings</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="<?= htmlspecialchars($base) ?>context/logout.php">Logout</a></li>
                        </ul>
                    </div>
                <?php else: ?>
                    <a href="<?= htmlspecialchars($base) ?>context/Login.php" class="text-dark">
                        <i class="fa-regular fa-user fs-5"></i>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</header>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
