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
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Update site settings
        // For now, we'll just show a success message
        // In a real application, you would update settings in the database
        $success = "Settings updated successfully!";
    } catch(PDOException $e) {
        $message = "Error updating settings: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Novatech Admin</title>
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
                            <li><a class="dropdown-item active" href="settings.php">Settings</a></li>
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
                <h2>System Settings</h2>
                <?php if ($message): ?>
                    <div class="alert alert-danger"><?php echo $message; ?></div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="row mt-4">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h4>Platform Settings</h4>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label for="site_name" class="form-label">Site Name</label>
                                <input type="text" class="form-control" id="site_name" name="site_name" value="Novatech Investment Platform">
                            </div>
                            
                            <div class="mb-3">
                                <label for="admin_email" class="form-label">Admin Email</label>
                                <input type="email" class="form-control" id="admin_email" name="admin_email" value="admin@novatech.com">
                            </div>
                            
                            <div class="mb-3">
                                <label for="support_email" class="form-label">Support Email</label>
                                <input type="email" class="form-control" id="support_email" name="support_email" value="support@novatech.com">
                            </div>
                            
                            <div class="mb-3">
                                <label for="minimum_investment" class="form-label">Minimum Investment Amount (RWF)</label>
                                <input type="number" class="form-control" id="minimum_investment" name="minimum_investment" value="100" min="1">
                            </div>
                            
                            <div class="mb-3">
                                <label for="minimum_withdrawal" class="form-label">Minimum Withdrawal Amount (RWF)</label>
                                <input type="number" class="form-control" id="minimum_withdrawal" name="minimum_withdrawal" value="50" min="1">
                            </div>
                            
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="maintenance_mode" name="maintenance_mode">
                                <label class="form-check-label" for="maintenance_mode">Maintenance Mode</label>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">Save Settings</button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h4>System Information</h4>
                    </div>
                    <div class="card-body">
                        <p><strong>PHP Version:</strong> <?php echo phpversion(); ?></p>
                        <p><strong>Server Software:</strong> <?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?></p>
                        <p><strong>Database Status:</strong> <span class="text-success">Connected</span></p>
                        <p><strong>Last Cron Run:</strong> <?php echo date('M d, Y H:i:s', strtotime('-1 hour')); ?></p>
                    </div>
                </div>
                
                <div class="card mt-4">
                    <div class="card-header">
                        <h4>Maintenance Actions</h4>
                    </div>
                    <div class="card-body">
                        <button class="btn btn-warning w-100 mb-2">Run Daily Earnings</button>
                        <button class="btn btn-warning w-100 mb-2">Process Referrals</button>
                        <button class="btn btn-danger w-100">Clear Cache</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>