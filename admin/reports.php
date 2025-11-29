<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header('Location: ../login.php');
    exit();
}

$admin_name = $_SESSION['first_name'];
$message = '';

// Fetch statistics
try {
    // Total users
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM users");
    $stmt->execute();
    $total_users = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Total products
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM products");
    $stmt->execute();
    $total_products = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Total purchases
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM purchases");
    $stmt->execute();
    $total_purchases = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Total transactions
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM transactions");
    $stmt->execute();
    $total_transactions = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Total investment amount
    $stmt = $pdo->prepare("SELECT SUM(p.price) as total FROM purchases pur JOIN products p ON pur.product_id = p.id");
    $stmt->execute();
    $total_investment = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
    
    // Active investments
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM purchases WHERE status = 'active'");
    $stmt->execute();
    $active_investments = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Recent transactions
    $stmt = $pdo->prepare("SELECT t.*, u.first_name, u.last_name FROM transactions t JOIN users u ON t.user_id = u.id ORDER BY t.transaction_date DESC LIMIT 10");
    $stmt->execute();
    $recent_transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    $error = "Error fetching statistics: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Novatech Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .stat-card {
            transition: transform 0.2s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="#">Novatech Admin</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="users.php">Users</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="products.php">Products</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="investments.php">Investments</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="transactions.php">Transactions</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="reports.php">Reports</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="finances.php">Finances</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="referrals.php">Referrals</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <?php echo htmlspecialchars($admin_name); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="settings.php">Settings</a></li>
                            <li><a class="dropdown-item" href="../client/logout.php">Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row">
            <div class="col-md-12">
                <h2>System Reports</h2>
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Statistics Cards -->
        <div class="row mt-4">
            <div class="col-md-3 mb-4">
                <div class="card stat-card text-white bg-primary">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h5 class="card-title">Total Users</h5>
                                <h3><?php echo $total_users; ?></h3>
                            </div>
                            <i class="bi bi-people fs-1"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="card stat-card text-white bg-success">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h5 class="card-title">Total Products</h5>
                                <h3><?php echo $total_products; ?></h3>
                            </div>
                            <i class="bi bi-box-seam fs-1"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="card stat-card text-white bg-info">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h5 class="card-title">Total Purchases</h5>
                                <h3><?php echo $total_purchases; ?></h3>
                            </div>
                            <i class="bi bi-cart-check fs-1"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="card stat-card text-white bg-warning">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h5 class="card-title">Total Transactions</h5>
                                <h3><?php echo $total_transactions; ?></h3>
                            </div>
                            <i class="bi bi-currency-exchange fs-1"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-6 mb-4">
                <div class="card stat-card text-white bg-secondary">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h5 class="card-title">Total Investment</h5>
                                <h3>$<?php echo number_format($total_investment, 2); ?></h3>
                            </div>
                            <i class="bi bi-cash-stack fs-1"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 mb-4">
                <div class="card stat-card text-white bg-danger">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h5 class="card-title">Active Investments</h5>
                                <h3><?php echo $active_investments; ?></h3>
                            </div>
                            <i class="bi bi-graph-up fs-1"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Recent Transactions -->
        <div class="row mt-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h4>Recent Transactions</h4>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>User</th>
                                        <th>Type</th>
                                        <th>Amount</th>
                                        <th>Date</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($recent_transactions) > 0): ?>
                                        <?php foreach ($recent_transactions as $transaction): ?>
                                            <tr>
                                                <td><?php echo $transaction['id']; ?></td>
                                                <td><?php echo htmlspecialchars($transaction['first_name'] . ' ' . $transaction['last_name']); ?></td>
                                                <td><?php echo ucfirst(str_replace('_', ' ', $transaction['transaction_type'])); ?></td>
                                                <td>$<?php echo number_format($transaction['amount'], 2); ?></td>
                                                <td><?php echo date('M d, Y H:i', strtotime($transaction['transaction_date'])); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php echo $transaction['status'] == 'approved' ? 'success' : ($transaction['status'] == 'pending' ? 'warning' : 'danger'); ?>">
                                                        <?php echo ucfirst($transaction['status']); ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="text-center">No transactions found.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>