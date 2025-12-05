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

// Fetch user's withdrawal requests
try {
    $stmt = $pdo->prepare("SELECT w.*, u.first_name as agent_first_name, u.last_name as agent_last_name 
                          FROM withdrawals w 
                          LEFT JOIN users u ON w.agent_id = u.id 
                          WHERE w.client_id = ? 
                          ORDER BY w.date DESC");
    $stmt->execute([$user_id]);
    $withdrawals = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error = "Error fetching withdrawal requests: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Withdrawal Status - Novatech Investment Platform</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
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
                        <a class="nav-link" href="reports.php">Reports</a>
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
                        <a class="nav-link active" href="#">Withdrawal Status</a>
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
                <h2>Withdrawal Status</h2>
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="row mt-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h4>Your Withdrawal Requests</h4>
                    </div>
                    <div class="card-body">
                        <?php if (count($withdrawals) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Request Date</th>
                                            <th>Amount</th>
                                            <th>Fee (4%)</th>
                                            <th>Amount After Fee</th>
                                            <th>Source</th>
                                            <th>Status</th>
                                            <th>Processed By</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($withdrawals as $withdrawal): ?>
                                            <tr>
                                                <td><?php echo date('M d, Y H:i', strtotime($withdrawal['date'])); ?></td>
                                                <td>RWF <?php echo number_format($withdrawal['amount'], 2); ?></td>
                                                <td>RWF <?php echo number_format($withdrawal['fee'], 2); ?></td>
                                                <td>RWF <?php echo number_format($withdrawal['amount_after_fee'], 2); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php echo $withdrawal['source'] == 'main' ? 'primary' : 'success'; ?>">
                                                        <?php echo ucfirst($withdrawal['source']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-<?php 
                                                        echo $withdrawal['status'] == 'approved' ? 'success' : 
                                                            ($withdrawal['status'] == 'rejected' ? 'danger' : 'warning'); ?>">
                                                        <?php echo ucfirst($withdrawal['status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if ($withdrawal['agent_id']): ?>
                                                        <?php echo htmlspecialchars($withdrawal['agent_first_name'] . ' ' . $withdrawal['agent_last_name']); ?>
                                                    <?php else: ?>
                                                        Pending assignment
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p>You haven't submitted any withdrawal requests yet.</p>
                            <a href="withdraw.php" class="btn btn-primary">Submit Withdrawal Request</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/main.js"></script>
</body>
</html>