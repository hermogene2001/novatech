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

// Get search parameters
$search_user = isset($_GET['search_user']) ? trim($_GET['search_user']) : '';
$search_type = isset($_GET['search_type']) ? $_GET['search_type'] : '';
$search_status = isset($_GET['search_status']) ? $_GET['search_status'] : '';
$search_date_from = isset($_GET['search_date_from']) ? $_GET['search_date_from'] : '';
$search_date_to = isset($_GET['search_date_to']) ? $_GET['search_date_to'] : '';

try {
    // Build query with search filters
    $sql = "SELECT t.*, u.first_name, u.last_name, u.phone_number 
            FROM transactions t 
            JOIN users u ON t.user_id = u.id";
    
    $conditions = [];
    $params = [];
    
    // Add search conditions
    if (!empty($search_user)) {
        $conditions[] = "(u.first_name LIKE ? OR u.last_name LIKE ? OR u.phone_number LIKE ?)";
        $params[] = "%$search_user%";
        $params[] = "%$search_user%";
        $params[] = "%$search_user%";
    }
    
    if (!empty($search_type)) {
        $conditions[] = "t.transaction_type = ?";
        $params[] = $search_type;
    }
    
    if (!empty($search_status)) {
        $conditions[] = "t.status = ?";
        $params[] = $search_status;
    }
    
    if (!empty($search_date_from)) {
        $conditions[] = "t.transaction_date >= ?";
        $params[] = $search_date_from;
    }
    
    if (!empty($search_date_to)) {
        $conditions[] = "t.transaction_date <= ?";
        $params[] = $search_date_to . ' 23:59:59';
    }
    
    if (!empty($conditions)) {
        $sql .= " WHERE " . implode(" AND ", $conditions);
    }
    
    $sql .= " ORDER BY t.transaction_date DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
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
        
        <!-- Search Form -->
        <div class="row mt-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h4>Search Transactions</h4>
                    </div>
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-3">
                                <label for="search_user" class="form-label">User (Name/Phone)</label>
                                <input type="text" class="form-control" id="search_user" name="search_user" value="<?php echo htmlspecialchars($search_user); ?>">
                            </div>
                            <div class="col-md-2">
                                <label for="search_type" class="form-label">Type</label>
                                <select class="form-select" id="search_type" name="search_type">
                                    <option value="">All Types</option>
                                    <option value="deposit" <?php echo $search_type == 'deposit' ? 'selected' : ''; ?>>Deposit</option>
                                    <option value="withdrawal" <?php echo $search_type == 'withdrawal' ? 'selected' : ''; ?>>Withdrawal</option>
                                    <option value="compound" <?php echo $search_type == 'compound' ? 'selected' : ''; ?>>Compound</option>
                                    <option value="purchase" <?php echo $search_type == 'purchase' ? 'selected' : ''; ?>>Purchase</option>
                                    <option value="daily_earning" <?php echo $search_type == 'daily_earning' ? 'selected' : ''; ?>>Daily Earning</option>
                                    <option value="investment" <?php echo $search_type == 'investment' ? 'selected' : ''; ?>>Investment</option>
                                    <option value="capital_return" <?php echo $search_type == 'capital_return' ? 'selected' : ''; ?>>Capital Return</option>
                                    <option value="recharge" <?php echo $search_type == 'recharge' ? 'selected' : ''; ?>>Recharge</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="search_status" class="form-label">Status</label>
                                <select class="form-select" id="search_status" name="search_status">
                                    <option value="">All Statuses</option>
                                    <option value="pending" <?php echo $search_status == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="approved" <?php echo $search_status == 'approved' ? 'selected' : ''; ?>>Approved</option>
                                    <option value="rejected" <?php echo $search_status == 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="search_date_from" class="form-label">From Date</label>
                                <input type="date" class="form-control" id="search_date_from" name="search_date_from" value="<?php echo $search_date_from; ?>">
                            </div>
                            <div class="col-md-2">
                                <label for="search_date_to" class="form-label">To Date</label>
                                <input type="date" class="form-control" id="search_date_to" name="search_date_to" value="<?php echo $search_date_to; ?>">
                            </div>
                            <div class="col-md-1 d-flex align-items-end">
                                <div>
                                    <button type="submit" class="btn btn-primary">Search</button>
                                    <?php if (!empty($_GET)): ?>
                                        <a href="transactions.php" class="btn btn-secondary">Clear</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row mt-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h4>All Transactions</h4>
                        <span class="badge bg-primary"><?php echo count($transactions); ?> Results</span>
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
                                                <td>RWF <?php echo number_format($transaction['amount'], 2); ?></td>
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