<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header('Location: ../login.php');
    exit();
}

$admin_name = $_SESSION['first_name'];
$total_users = 0;
$total_investments = 0;
$total_transactions = 0;
$total_products = 0;
$total_revenue = 0;
$pending_withdrawals = 0;
$pending_recharges = 0;
$active_investments = 0;

try {
    // Get total users
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM users");
    $stmt->execute();
    $total_users = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Get total products
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM products");
    $stmt->execute();
    $total_products = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Get total investments
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM purchases");
    $stmt->execute();
    $total_investments = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Get total transactions
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM transactions");
    $stmt->execute();
    $total_transactions = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Get total revenue (sum of all investment prices)
    $stmt = $pdo->prepare("SELECT SUM(p.price) as total FROM purchases pur JOIN products p ON pur.product_id = p.id");
    $stmt->execute();
    $total_revenue = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
    
    // Get pending withdrawals
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM withdrawals WHERE status = 'pending'");
    $stmt->execute();
    $pending_withdrawals = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Get pending recharges
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM recharges WHERE status = 'pending'");
    $stmt->execute();
    $pending_recharges = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Get active investments
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM purchases WHERE status = 'active'");
    $stmt->execute();
    $active_investments = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Get recent activities (last 5 transactions)
    $stmt = $pdo->prepare("SELECT t.*, u.first_name, u.last_name FROM transactions t JOIN users u ON t.user_id = u.id ORDER BY t.transaction_date DESC LIMIT 5");
    $stmt->execute();
    $recent_activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get recent user registrations
    $stmt = $pdo->prepare("SELECT id, first_name, last_name, phone_number, created_at FROM users ORDER BY created_at DESC LIMIT 5");
    $stmt->execute();
    $recent_users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    $error = "Error fetching dashboard data: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Novatech Investment Platform</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .stat-card {
            transition: transform 0.3s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .quick-action-btn {
            transition: all 0.3s;
        }
        .quick-action-btn:hover {
            transform: scale(1.05);
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
                        <a class="nav-link active" href="#">Dashboard</a>
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
                        <a class="nav-link" href="reports.php">Reports</a>
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
                <h2>Admin Dashboard</h2>
                <p class="text-muted">Welcome back, <?php echo htmlspecialchars($admin_name); ?>! Here's what's happening with your investment platform today.</p>
            </div>
        </div>
        
        <!-- Stats Cards -->
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
                                <h5 class="card-title">Total Revenue</h5>
                                <h3>$<?php echo number_format($total_revenue, 2); ?></h3>
                            </div>
                            <i class="bi bi-currency-dollar fs-1"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="card stat-card text-white bg-warning">
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
        
        <!-- Secondary Stats Cards -->
        <div class="row">
            <div class="col-md-3 mb-4">
                <div class="card stat-card text-white bg-danger">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h5 class="card-title">Pending Withdrawals</h5>
                                <h3><?php echo $pending_withdrawals; ?></h3>
                            </div>
                            <i class="bi bi-arrow-down-circle fs-1"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="card stat-card text-white bg-secondary">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h5 class="card-title">Pending Recharges</h5>
                                <h3><?php echo $pending_recharges; ?></h3>
                            </div>
                            <i class="bi bi-arrow-up-circle fs-1"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="card stat-card text-white bg-dark">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h5 class="card-title">Total Investments</h5>
                                <h3><?php echo $total_investments; ?></h3>
                            </div>
                            <i class="bi bi-wallet fs-1"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="card stat-card text-white bg-primary">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h5 class="card-title">Total Transactions</h5>
                                <h3><?php echo $total_transactions; ?></h3>
                            </div>
                            <i class="bi bi-arrow-left-right fs-1"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="row mt-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h4>Quick Actions</h4>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-2 mb-3">
                                <a href="users.php" class="btn btn-primary quick-action-btn w-100">
                                    <i class="bi bi-people me-2"></i>Manage Users
                                </a>
                            </div>
                            <div class="col-md-2 mb-3">
                                <a href="products.php" class="btn btn-success quick-action-btn w-100">
                                    <i class="bi bi-box-seam me-2"></i>Manage Products
                                </a>
                            </div>
                            <div class="col-md-2 mb-3">
                                <a href="investments.php" class="btn btn-info quick-action-btn w-100">
                                    <i class="bi bi-wallet me-2"></i>View Investments
                                </a>
                            </div>
                            <div class="col-md-2 mb-3">
                                <a href="transactions.php" class="btn btn-warning quick-action-btn w-100">
                                    <i class="bi bi-arrow-left-right me-2"></i>View Transactions
                                </a>
                            </div>
                            <div class="col-md-2 mb-3">
                                <a href="finances.php" class="btn btn-danger quick-action-btn w-100">
                                    <i class="bi bi-cash-stack me-2"></i>Manage Finances
                                </a>
                            </div>
                            <div class="col-md-2 mb-3">
                                <a href="settings.php" class="btn btn-secondary quick-action-btn w-100">
                                    <i class="bi bi-gear me-2"></i>System Settings
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Recent Activity and Users -->
        <div class="row mt-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h4>Recent Transactions</h4>
                    </div>
                    <div class="card-body">
                        <?php if (isset($recent_activities) && count($recent_activities) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>User</th>
                                            <th>Type</th>
                                            <th>Amount</th>
                                            <th>Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recent_activities as $activity): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($activity['first_name'] . ' ' . $activity['last_name']); ?></td>
                                                <td><?php echo ucfirst(str_replace('_', ' ', $activity['transaction_type'])); ?></td>
                                                <td>$<?php echo number_format($activity['amount'], 2); ?></td>
                                                <td><?php echo date('M d, Y', strtotime($activity['transaction_date'])); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="text-muted">No recent transactions found.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h4>Recent User Registrations</h4>
                    </div>
                    <div class="card-body">
                        <?php if (isset($recent_users) && count($recent_users) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Phone</th>
                                            <th>Registration Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recent_users as $user): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($user['first_name'] . ' ' . ($user['last_name'] ?? '')); ?></td>
                                                <td><?php echo htmlspecialchars($user['phone_number']); ?></td>
                                                <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="text-muted">No recent user registrations found.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- System Information -->
        <div class="row mt-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h4>System Information</h4>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <p><strong>Server Time:</strong> <?php echo date('Y-m-d H:i:s'); ?></p>
                            </div>
                            <div class="col-md-3">
                                <p><strong>PHP Version:</strong> <?php echo phpversion(); ?></p>
                            </div>
                            <div class="col-md-3">
                                <p><strong>Database Status:</strong> <span class="text-success">Connected</span></p>
                            </div>
                            <div class="col-md-3">
                                <p><strong>Admin Role:</strong> <?php echo htmlspecialchars($_SESSION['user_role']); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>