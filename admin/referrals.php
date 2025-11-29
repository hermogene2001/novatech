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

// Fetch all referrals with client information
try {
    $stmt = $pdo->prepare("SELECT r.*, u.first_name as client_first_name, u.last_name as client_last_name, 
                           u.phone_number as client_phone, ref.first_name as ref_first_name, 
                           ref.last_name as ref_last_name, ref.phone_number as ref_phone 
                           FROM referrals r 
                           JOIN users u ON r.client_id = u.id 
                           JOIN users ref ON r.referred_id = ref.id 
                           ORDER BY r.referral_date DESC");
    $stmt->execute();
    $referrals = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error = "Error fetching referrals: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Referral Management - Novatech Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
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
                        <a class="nav-link" href="reports.php">Reports</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="finances.php">Finances</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="referrals.php">Referrals</a>
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
                <h2>Referral Management</h2>
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="row mt-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h4>All Referrals</h4>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Client</th>
                                        <th>Client Phone</th>
                                        <th>Referred User</th>
                                        <th>Referred Phone</th>
                                        <th>Referral Code</th>
                                        <th>Referral Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($referrals) > 0): ?>
                                        <?php foreach ($referrals as $referral): ?>
                                            <tr>
                                                <td><?php echo $referral['id']; ?></td>
                                                <td><?php echo htmlspecialchars($referral['client_first_name'] . ' ' . $referral['client_last_name']); ?></td>
                                                <td><?php echo htmlspecialchars($referral['client_phone']); ?></td>
                                                <td><?php echo htmlspecialchars($referral['ref_first_name'] . ' ' . $referral['ref_last_name']); ?></td>
                                                <td><?php echo htmlspecialchars($referral['ref_phone']); ?></td>
                                                <td><?php echo htmlspecialchars($referral['referral_code']); ?></td>
                                                <td><?php echo date('M d, Y H:i', strtotime($referral['referral_date'])); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="7" class="text-center">No referrals found.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Referral Statistics -->
        <div class="row mt-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h4>Referral Statistics</h4>
                    </div>
                    <div class="card-body">
                        <?php
                        // Calculate referral statistics
                        $total_referrals = count($referrals);
                        
                        // Get unique referrers
                        $unique_referrers = [];
                        foreach ($referrals as $referral) {
                            $unique_referrers[$referral['client_id']] = true;
                        }
                        $total_referrers = count($unique_referrers);
                        ?>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card text-white bg-primary">
                                    <div class="card-body">
                                        <h5 class="card-title">Total Referrals</h5>
                                        <h3><?php echo $total_referrals; ?></h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card text-white bg-success">
                                    <div class="card-body">
                                        <h5 class="card-title">Unique Referrers</h5>
                                        <h3><?php echo $total_referrers; ?></h3>
                                    </div>
                                </div>
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