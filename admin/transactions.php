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
$transactions = [];

try {
    // Fetch all transactions with user details
    $stmt = $pdo->prepare("SELECT t.*, u.first_name, u.last_name, u.phone_number 
                          FROM transactions t 
                          JOIN users u ON t.user_id = u.id 
                          ORDER BY t.transaction_date DESC");
    $stmt->execute();
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    $message = "Error fetching transactions: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transactions - Novatech Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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
                        <a class="nav-link active" href="transactions.php">Transactions</a>
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
                <h2>Transaction Management</h2>
                <?php if ($message): ?>
                    <div class="alert alert-info"><?php echo $message; ?></div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="row mt-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h4>All Transactions</h4>
                    </div>
                    <div class="card-body">
                        <?php if (count($transactions) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>User</th>
                                            <th>Phone</th>
                                            <th>Type</th>
                                            <th>Amount</th>
                                            <th>Date</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($transactions as $transaction): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($transaction['first_name'] . ' ' . $transaction['last_name']); ?></td>
                                                <td><?php echo htmlspecialchars($transaction['phone_number']); ?></td>
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
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p>No transactions found.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>