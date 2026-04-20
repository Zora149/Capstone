<?php
require_once 'middleware.php';
require_once '../../connection/db_connect.php';

// Fetch statistics
try {
    // Total Orders
    $stmt = $pdo->query("SELECT COUNT(DISTINCT order_id) as total_orders FROM completed_orders" );
    $totalOrders = $stmt->fetchColumn();

    // Completed Orders
    $stmt = $pdo->prepare("SELECT COUNT(DISTINCT order_id) as completed_orders FROM completed_orders WHERE status = 'completed'");
    $stmt->execute();
    $completedOrders = $stmt->fetchColumn();

    // Total Users
    $stmt = $pdo->query("SELECT COUNT(*) as total_users FROM users");
    $totalUsers = $stmt->fetchColumn();

    // Daily Income
    $stmt = $pdo->prepare("
        SELECT SUM(total) as daily_income 
        FROM completed_orders 
        WHERE DATE(order_date) = CURDATE()
        AND status = 'completed'
    ");
    $stmt->execute();
    $dailyIncome = $stmt->fetchColumn() ?? 0;

    // Monthly Income
    $stmt = $pdo->prepare("
        SELECT SUM(total) as monthly_income 
        FROM completed_orders 
        WHERE MONTH(order_date) = MONTH(CURDATE()) 
        AND YEAR(order_date) = YEAR(CURDATE())
        AND status = 'completed'
    ");
    $stmt->execute();
    $monthlyIncome = $stmt->fetchColumn() ?? 0;

    // Total Income
    $stmt = $pdo->prepare("
        SELECT SUM(total) as total_income 
        FROM completed_orders 
        WHERE status = 'completed'
    ");
    $stmt->execute();
    $totalIncome = $stmt->fetchColumn() ?? 0;

} catch (PDOException $e) {
    error_log("Error fetching dashboard statistics: " . $e->getMessage());
    $totalOrders = $completedOrders = $totalUsers = $dailyIncome = $monthlyIncome = $totalIncome = 0;
}

// Fetch product distribution data
try {
    $stmt = $pdo->prepare("
        SELECT product_name, SUM(quantity) as total_quantity
        FROM completed_orders
        WHERE status = 'completed'
        GROUP BY product_name
        ORDER BY total_quantity DESC
    ");
    $stmt->execute();
    $productDistribution = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching product distribution: " . $e->getMessage());
    $productDistribution = [];
}

// Fetch income per day for last 90 days
try {
    $stmt = $pdo->prepare("
        SELECT DATE(order_date) as date, SUM(total) as income
        FROM completed_orders
        WHERE status = 'completed'
          AND order_date >= DATE_SUB(CURDATE(), INTERVAL 90 DAY)
        GROUP BY DATE(order_date)
        ORDER BY date ASC
    ");
    $stmt->execute();
    $incomeData = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching cashflow data: " . $e->getMessage());
    $incomeData = [];
}
?>

<div class="container-fluid px-4 py-3">
    <!-- Header -->
    <header class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Chicken Whole Sale Dashboard</h1>
        <div class="d-flex align-items-center">
            <div class="text-muted me-3 ">Welcome, <?= htmlspecialchars($_SESSION['username']) ?>!</div>
            <a href="settings.php" class="btn btn-sm btn-outline-danger m-2">Settings</a>
            <a href="logout.php" class="btn btn-sm btn-outline-danger">Logout</a>

        </div>
    </header>

    <!-- Stats Grid -->
    <div class="row row-cols-1 row-cols-md-6 g-4 mb-4">
        <div class="col"><div class="card h-100 text-center"><div class="card-body"><h3><?= $totalOrders ?></h3><p>Total Orders</p></div></div></div>
        <div class="col"><div class="card h-100 text-center"><div class="card-body"><h3><?= $completedOrders ?></h3><p>Completed Orders</p></div></div></div>
        <div class="col"><div class="card h-100 text-center"><div class="card-body"><h3><?= $totalUsers ?></h3><p>Total Users</p></div></div></div>
        <div class="col"><div class="card h-100 text-center"><div class="card-body"><h3>₱<?= number_format($dailyIncome, 2) ?></h3><p>Daily Income</p></div></div></div>
        <div class="col"><div class="card h-100 text-center"><div class="card-body"><h3>₱<?= number_format($monthlyIncome, 2) ?></h3><p>Monthly Income</p></div></div></div>
        <div class="col"><div class="card h-100 text-center"><div class="card-body"><h3>₱<?= number_format($totalIncome, 2) ?></h3><p>Total Income</p></div></div></div>
    </div>

    <!-- Product Distribution Chart -->
    <div class="card mb-4">
        <div class="card-body">
            <h3 class="card-title text-center">Product Distribution</h3>
            <div style="max-width: 400px; margin: 0 auto;">
                <canvas id="productChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Cashflow Chart -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h3 class="card-title">Sales</h3>
                <select id="daysSelect" class="form-select w-auto">
                    <option value="30">Last 30 Days</option>
                    <option value="60">Last 60 Days</option>
                    <option value="90" selected>Last 90 Days</option>
                </select>
            </div>
            <canvas id="cashflowChart"></canvas>
        </div>
    </div>
</div>

<!-- Bootstrap & Chart.js -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Product distribution data
    const productDistribution = <?= json_encode($productDistribution) ?>;
    const productLabels = productDistribution.map(p => p.product_name);
    const productValues = productDistribution.map(p => p.total_quantity);

    new Chart(document.getElementById('productChart'), {
        type: 'doughnut',
        data: { labels: productLabels, datasets: [{ data: productValues, backgroundColor: ['#ff6384','#36a2eb','#ffcd56','#4bc0c0','#9966ff','#ff9f40'] }] }
    });

    // Cashflow data
    const incomeData = <?= json_encode($incomeData) ?>;
    const cashflowLabels = incomeData.map(i => i.date);
    const cashflowValues = incomeData.map(i => parseFloat(i.income));

    const ctxCashflow = document.getElementById('cashflowChart').getContext('2d');
    const cashflowChart = new Chart(ctxCashflow, {
        type: 'line',
        data: {
            labels: cashflowLabels,
            datasets: [{
                label: 'Income',
                data: cashflowValues,
                fill: true,
                borderColor: '#36a2eb',
                backgroundColor: 'rgba(54,162,235,0.2)',
                tension: 0.3
            }]
        },
        options: {
            responsive: true,
            scales: { x: { title: { display: true, text: 'Date' } }, y: { title: { display: true, text: 'Income (₱)' }, beginAtZero: true } }
        }
    });

    // Dropdown filter
    document.getElementById('daysSelect').addEventListener('change', function() {
        const days = parseInt(this.value);
        const cutoffDate = new Date();
        cutoffDate.setDate(cutoffDate.getDate() - days);

        const filteredLabels = [];
        const filteredValues = [];

        incomeData.forEach(i => {
            const d = new Date(i.date);
            if (d >= cutoffDate) {
                filteredLabels.push(i.date);
                filteredValues.push(parseFloat(i.income));
            }
        });

        cashflowChart.data.labels = filteredLabels;
        cashflowChart.data.datasets[0].data = filteredValues;
        cashflowChart.update();
    });
</script>
