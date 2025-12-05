<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'client') {
    header('Location: ../login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$first_name = $_SESSION['first_name'];

// Initialize variables
$investments = [];
$transactions = [];
$earnings_by_product = [];

// Fetch investment data for charts
try {
    // Get investments with product names
    $stmt = $pdo->prepare("SELECT i.*, p.name as product_name 
                          FROM investments i 
                          JOIN products p ON i.product_id = p.id 
                          WHERE i.user_id = ? AND i.status = 'active' 
                          ORDER BY i.invested_at DESC");
    $stmt->execute([$user_id]);
    $investments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get transaction history for the last 30 days
    $stmt = $pdo->prepare("SELECT DATE(transaction_date) as date, SUM(amount) as amount, transaction_type 
                          FROM transactions 
                          WHERE user_id = ? AND transaction_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                          GROUP BY DATE(transaction_date), transaction_type 
                          ORDER BY transaction_date ASC");
    $stmt->execute([$user_id]);
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get total earnings by product
    $stmt = $pdo->prepare("SELECT p.name as product_name, SUM(t.amount) as total_earnings
                          FROM transactions t
                          JOIN investments i ON t.user_id = i.user_id
                          JOIN products p ON i.product_id = p.id
                          WHERE t.user_id = ? AND t.transaction_type = 'daily_earning'
                          GROUP BY p.name");
    $stmt->execute([$user_id]);
    $earnings_by_product = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    $error = "Error fetching report data: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Investment Reports - Novatech</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="#">Novatech Client</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="products.php">Products</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="transactions.php">Transactions</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="reports.php">Reports</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="referrals.php">Referrals</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="recharge.php">Recharge</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="withdraw.php">Withdraw</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="withdrawal_status.php">Withdrawal Status</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <?php echo htmlspecialchars($first_name); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="profile.php">Profile</a></li>
                            <li><a class="dropdown-item" href="setup_2fa.php">2FA Setup</a></li>
                            <li><a class="dropdown-item" href="logout.php">Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row">
            <div class="col-md-12">
                <h2>Investment Performance Reports</h2>
            </div>
        </div>
        
        <!-- Investment Distribution Chart -->
        <div class="row mt-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h4>Investment Distribution</h4>
                    </div>
                    <div class="card-body">
                        <canvas id="investmentChart" height="100"></canvas>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Earnings by Product Chart -->
        <div class="row mt-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h4>Earnings by Product</h4>
                    </div>
                    <div class="card-body">
                        <canvas id="earningsChart" height="100"></canvas>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Recent Transactions Chart -->
        <div class="row mt-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h4>Recent Transactions (Last 30 Days)</h4>
                    </div>
                    <div class="card-body">
                        <canvas id="transactionsChart" height="100"></canvas>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Investment Details Table -->
        <div class="row mt-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h4>Investment Details</h4>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($investments)): ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Product</th>
                                            <th>Amount</th>
                                            <th>Daily Earning</th>
                                            <th>Invested On</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($investments as $investment): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($investment['product_name']); ?></td>
                                                <td>RWF <?php echo number_format($investment['amount'], 2); ?></td>
                                                <td>RWF <?php echo number_format($investment['daily_profit'], 2); ?></td>
                                                <td><?php echo date('M d, Y', strtotime($investment['invested_at'])); ?></td>
                                                <td><span class="badge bg-success"><?php echo ucfirst($investment['status']); ?></span></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p>You don't have any active investments yet. <a href="products.php">Start investing now</a>.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Prepare data for charts
        <?php
        // Investment distribution data
        $investment_labels = [];
        $investment_data = [];
        foreach ($investments as $investment) {
            $investment_labels[] = $investment['product_name'];
            $investment_data[] = $investment['amount'];
        }
        
        // Earnings by product data
        $earnings_labels = [];
        $earnings_data = [];
        foreach ($earnings_by_product as $earning) {
            $earnings_labels[] = $earning['product_name'];
            $earnings_data[] = $earning['total_earnings'];
        }
        
        // Transactions data
        $transaction_dates = [];
        $transaction_amounts = [];
        foreach ($transactions as $transaction) {
            $transaction_dates[] = $transaction['date'];
            $transaction_amounts[] = $transaction['amount'];
        }
        ?>
        
        // Investment Distribution Chart
        const investmentCtx = document.getElementById('investmentChart').getContext('2d');
        const investmentChart = new Chart(investmentCtx, {
            type: 'pie',
            data: {
                labels: <?php echo json_encode($investment_labels); ?>,
                datasets: [{
                    data: <?php echo json_encode($investment_data); ?>,
                    backgroundColor: [
                        'rgba(255, 99, 132, 0.8)',
                        'rgba(54, 162, 235, 0.8)',
                        'rgba(255, 205, 86, 0.8)',
                        'rgba(75, 192, 192, 0.8)',
                        'rgba(153, 102, 255, 0.8)',
                        'rgba(255, 159, 64, 0.8)'
                    ],
                    borderColor: [
                        'rgba(255, 99, 132, 1)',
                        'rgba(54, 162, 235, 1)',
                        'rgba(255, 205, 86, 1)',
                        'rgba(75, 192, 192, 1)',
                        'rgba(153, 102, 255, 1)',
                        'rgba(255, 159, 64, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    title: {
                        display: true,
                        text: 'Investment Distribution by Product'
                    }
                }
            }
        });
        
        // Earnings by Product Chart
        const earningsCtx = document.getElementById('earningsChart').getContext('2d');
        const earningsChart = new Chart(earningsCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($earnings_labels); ?>,
                datasets: [{
                    label: 'Total Earnings (RWF)',
                    data: <?php echo json_encode($earnings_data); ?>,
                    backgroundColor: 'rgba(54, 162, 235, 0.8)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    },
                    title: {
                        display: true,
                        text: 'Total Earnings by Product'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
        
        // Transactions Chart
        const transactionsCtx = document.getElementById('transactionsChart').getContext('2d');
        const transactionsChart = new Chart(transactionsCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($transaction_dates); ?>,
                datasets: [{
                    label: 'Transaction Amount (RWF)',
                    data: <?php echo json_encode($transaction_amounts); ?>,
                    fill: false,
                    borderColor: 'rgba(75, 192, 192, 1)',
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    },
                    title: {
                        display: true,
                        text: 'Daily Transaction Amounts (Last 30 Days)'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/main.js"></script>
</body>
</html>