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

// Fetch all purchases with client and product information
try {
    $stmt = $pdo->prepare("SELECT p.*, u.first_name, u.last_name, u.phone_number, pr.name as product_name, pr.cycle as product_cycle 
                           FROM purchases p 
                           JOIN users u ON p.client_id = u.id 
                           JOIN products pr ON p.product_id = pr.id 
                           ORDER BY p.purchase_date DESC");
    $stmt->execute();
    $purchases = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // For purchases without end_datetime, calculate it based on cycle
    foreach ($purchases as &$purchase) {
        if (is_null($purchase['end_datetime']) && !is_null($purchase['purchase_date']) && !is_null($purchase['product_cycle'])) {
            $purchase['end_datetime'] = date('Y-m-d H:i:s', strtotime($purchase['purchase_date'] . " +{$purchase['product_cycle']} days"));
        }
    }
} catch(PDOException $e) {
    $error = "Error fetching purchases: " . $e->getMessage();
}

// Handle purchase status update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['purchase_id'])) {
    $purchase_id = $_POST['purchase_id'];
    $status = $_POST['status'];
    
    try {
        $stmt = $pdo->prepare("UPDATE purchases SET status = ? WHERE id = ?");
        $stmt->execute([$status, $purchase_id]);
        $message = "Purchase status updated successfully.";
        
        // Refresh purchases list
        $stmt = $pdo->prepare("SELECT p.*, u.first_name, u.last_name, u.phone_number, pr.name as product_name, pr.cycle as product_cycle 
                               FROM purchases p 
                               JOIN users u ON p.client_id = u.id 
                               JOIN products pr ON p.product_id = pr.id 
                               ORDER BY p.purchase_date DESC");
        $stmt->execute();
        $purchases = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // For purchases without end_datetime, calculate it based on cycle
        foreach ($purchases as &$purchase) {
            if (is_null($purchase['end_datetime']) && !is_null($purchase['purchase_date']) && !is_null($purchase['product_cycle'])) {
                $purchase['end_datetime'] = date('Y-m-d H:i:s', strtotime($purchase['purchase_date'] . " +{$purchase['product_cycle']} days"));
            }
        }
    } catch(PDOException $e) {
        $message = "Error updating purchase: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Investment Management - Novatech Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .table th, .table td {
            vertical-align: middle;
        }
        .status-active {
            background-color: #d4edda;
            color: #155724;
        }
        .status-completed {
            background-color: #cce7ff;
            color: #004085;
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
                        <a class="nav-link active" href="investments.php">Investments</a>
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
                <h2>Investment Management</h2>
                <?php if ($message): ?>
                    <div class="alert alert-info alert-dismissible fade show" role="alert">
                        <?php echo $message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="row mt-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h4>All Investment Purchases</h4>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Client</th>
                                        <th>Phone</th>
                                        <th>Product</th>
                                        <th>Purchase Date</th>
                                        <th>Last Earned</th>
                                        <th>End Date</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($purchases) > 0): ?>
                                        <?php foreach ($purchases as $purchase): ?>
                                            <tr>
                                                <td><?php echo $purchase['id']; ?></td>
                                                <td><?php echo htmlspecialchars($purchase['first_name'] . ' ' . $purchase['last_name']); ?></td>
                                                <td><?php echo htmlspecialchars($purchase['phone_number']); ?></td>
                                                <td><?php echo htmlspecialchars($purchase['product_name']); ?></td>
                                                <td><?php echo date('M d, Y H:i', strtotime($purchase['purchase_date'])); ?></td>
                                                <td><?php echo $purchase['last_earned'] ? date('M d, Y H:i', strtotime($purchase['last_earned'])) : 'Never'; ?></td>
                                                <td><?php echo $purchase['end_datetime'] ? date('M d, Y H:i', strtotime($purchase['end_datetime'])) : 'N/A'; ?></td>
                                                <td>
                                                    <span class="badge <?php echo $purchase['status'] == 'active' ? 'bg-success' : 'bg-primary'; ?>">
                                                        <?php echo ucfirst($purchase['status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <!-- Status Update Form -->
                                                    <form method="POST" class="d-inline">
                                                        <input type="hidden" name="purchase_id" value="<?php echo $purchase['id']; ?>">
                                                        <select name="status" class="form-select form-select-sm d-inline-block w-auto" onchange="this.form.submit()">
                                                            <option value="active" <?php echo $purchase['status'] == 'active' ? 'selected' : ''; ?>>Active</option>
                                                            <option value="completed" <?php echo $purchase['status'] == 'completed' ? 'selected' : ''; ?>>Completed</option>
                                                        </select>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="9" class="text-center">No investment purchases found.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Statistics Section -->
        <div class="row mt-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h4>Investment Statistics</h4>
                    </div>
                    <div class="card-body">
                        <?php
                        // Calculate statistics
                        $total_purchases = count($purchases);
                        $active_purchases = 0;
                        $completed_purchases = 0;
                        
                        foreach ($purchases as $purchase) {
                            if ($purchase['status'] == 'active') {
                                $active_purchases++;
                            } elseif ($purchase['status'] == 'completed') {
                                $completed_purchases++;
                            }
                        }
                        ?>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="card text-white bg-primary">
                                    <div class="card-body">
                                        <h5 class="card-title">Total Purchases</h5>
                                        <h3><?php echo $total_purchases; ?></h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card text-white bg-success">
                                    <div class="card-body">
                                        <h5 class="card-title">Active Investments</h5>
                                        <h3><?php echo $active_purchases; ?></h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card text-white bg-info">
                                    <div class="card-body">
                                        <h5 class="card-title">Completed Investments</h5>
                                        <h3><?php echo $completed_purchases; ?></h3>
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