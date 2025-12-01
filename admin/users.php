<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header('Location: ../login.php');
    exit();
}

$admin_id = $_SESSION['user_id']; // Get current admin ID
$admin_name = $_SESSION['first_name'];
$message = '';

// Handle user actions (edit/delete)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action']) && isset($_POST['user_id'])) {
        $user_id = $_POST['user_id'];
        $action = $_POST['action'];
        
        // Prevent actions on admin users (including current admin)
        $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && $user['role'] === 'admin') {
            $message = "Admin users cannot be modified.";
        } else {
            try {
                if ($action === 'delete') {
                    // Delete user and related records
                    $pdo->beginTransaction();
                    
                    // Delete referrals
                    $stmt = $pdo->prepare("DELETE FROM referrals WHERE client_id = ? OR referred_id = ?");
                    $stmt->execute([$user_id, $user_id]);
                    
                    // Delete referral earnings
                    $stmt = $pdo->prepare("DELETE FROM referral_earnings WHERE referrer_id = ? OR referred_id = ?");
                    $stmt->execute([$user_id, $user_id]);
                    
                    // Delete purchases
                    $stmt = $pdo->prepare("DELETE FROM purchases WHERE client_id = ?");
                    $stmt->execute([$user_id]);
                    
                    // Delete investments
                    $stmt = $pdo->prepare("DELETE FROM investments WHERE user_id = ?");
                    $stmt->execute([$user_id]);
                    
                    // Delete transactions
                    $stmt = $pdo->prepare("DELETE FROM transactions WHERE user_id = ?");
                    $stmt->execute([$user_id]);
                    
                    // Delete withdrawals
                    $stmt = $pdo->prepare("DELETE FROM withdrawals WHERE client_id = ?");
                    $stmt->execute([$user_id]);
                    
                    // Delete recharges
                    $stmt = $pdo->prepare("DELETE FROM recharges WHERE client_id = ? OR agent_id = ?");
                    $stmt->execute([$user_id, $user_id]);
                    
                    // Delete user banks
                    $stmt = $pdo->prepare("DELETE FROM user_banks WHERE user_id = ?");
                    $stmt->execute([$user_id]);
                    
                    // Delete client finances
                    $stmt = $pdo->prepare("DELETE FROM clients_finances WHERE client_id = ?");
                    $stmt->execute([$user_id]);
                    
                    // Delete user
                    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                    $stmt->execute([$user_id]);
                    
                    $pdo->commit();
                    $message = "User deleted successfully.";
                }
            } catch(PDOException $e) {
                $pdo->rollback();
                $message = "Error processing request: " . $e->getMessage();
            }
        }
    }
}

// Fetch all users
try {
    $stmt = $pdo->prepare("SELECT * FROM users ORDER BY id DESC");
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error = "Error fetching users: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - Novatech Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
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
                        <a class="nav-link active" href="#">Users</a>
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
                <h2>User Management</h2>
                <?php if (isset($message)): ?>
                    <div class="alert alert-info alert-dismissible fade show" role="alert">
                        <?php echo $message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="row mt-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h4>All Users</h4>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Phone</th>
                                        <th>Email</th>
                                        <th>Role</th>
                                        <th>Balance</th>
                                        <th>Referral Bonus</th>
                                        <th>Status</th>
                                        <th>Created At</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($users as $user): ?>
                                        <tr>
                                            <td><?php echo $user['id']; ?></td>
                                            <td><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></td>
                                            <td><?php echo htmlspecialchars($user['phone_number']); ?></td>
                                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                                            <td>
                                                <span class="badge bg-<?php 
                                                    echo $user['role'] == 'admin' ? 'danger' : 
                                                        ($user['role'] == 'agent' ? 'warning' : 'primary'); ?>">
                                                    <?php echo ucfirst($user['role']); ?>
                                                </span>
                                            </td>
                                            <td>RWF <?php echo number_format($user['balance'], 2); ?></td>
                                            <td>RWF <?php echo number_format($user['referral_bonus'], 2); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo $user['status'] == 'active' ? 'success' : 'danger'; ?>">
                                                    <?php echo ucfirst($user['status']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                            <td>
                                                <?php if ($user['role'] !== 'admin'): ?>
                                                    <!-- Edit button -->
                                                    <a href="edit_user.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-primary">Edit</a>
                                                    
                                                    <!-- Delete button with confirmation -->
                                                    <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this user? This action cannot be undone.')">
                                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                        <input type="hidden" name="action" value="delete">
                                                        <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                                    </form>
                                                <?php else: ?>
                                                    <span class="text-muted">Protected</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/main.js"></script>
</body>
</html>