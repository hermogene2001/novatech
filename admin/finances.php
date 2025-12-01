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

// Fetch all users with their financial information
try {
    $stmt = $pdo->prepare("SELECT u.*, cf.project_revenue, cf.invitation_income 
                           FROM users u 
                           LEFT JOIN clients_finances cf ON u.id = cf.client_id 
                           WHERE u.role = 'client' 
                           ORDER BY u.created_at DESC");
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error = "Error fetching users: " . $e->getMessage();
}

// Handle balance update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['user_id'])) {
    $user_id = $_POST['user_id'];
    $balance = $_POST['balance'];
    $project_revenue = $_POST['project_revenue'];
    $invitation_income = $_POST['invitation_income'];
    $referral_bonus = $_POST['referral_bonus'];
    
    try {
        // Update user balance
        $stmt = $pdo->prepare("UPDATE users SET balance = ? WHERE id = ?");
        $stmt->execute([$balance, $user_id]);
        
        // Update client finances
        $stmt = $pdo->prepare("INSERT INTO clients_finances (client_id, project_revenue, invitation_income) 
                              VALUES (?, ?, ?) 
                              ON DUPLICATE KEY UPDATE 
                              project_revenue = VALUES(project_revenue), 
                              invitation_income = VALUES(invitation_income)");
        $stmt->execute([$user_id, $project_revenue, $invitation_income]);
        
        // Update referral bonus
        $stmt = $pdo->prepare("UPDATE users SET referral_bonus = ? WHERE id = ?");
        $stmt->execute([$referral_bonus, $user_id]);
        
        $message = "User financial information updated successfully.";
        
        // Refresh users list
        $stmt = $pdo->prepare("SELECT u.*, cf.project_revenue, cf.invitation_income 
                              FROM users u 
                              LEFT JOIN clients_finances cf ON u.id = cf.client_id 
                              WHERE u.role = 'client' 
                              ORDER BY u.created_at DESC");
        $stmt->execute();
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        $message = "Error updating user finances: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Finances - Novatech Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .table th, .table td {
            vertical-align: middle;
        }
        .financial-card {
            transition: transform 0.2s;
        }
        .financial-card:hover {
            transform: translateY(-3px);
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
                        <a class="nav-link" href="reports.php">Reports</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="finances.php">Finances</a>
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
                <h2>User Financial Management</h2>
                <?php if ($message): ?>
                    <div class="alert alert-info alert-dismissible fade show" role="alert">
                        <?php echo $message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="row mt-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h4>All Client Financial Information</h4>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>User</th>
                                        <th>Phone</th>
                                        <th>Main Balance</th>
                                        <th>Project Revenue</th>
                                        <th>Invitation Income</th>
                                        <th>Referral Bonus</th>
                                        <th>Total</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($users) > 0): ?>
                                        <?php foreach ($users as $user): ?>
                                            <tr>
                                                <td><?php echo $user['id']; ?></td>
                                                <td><?php echo htmlspecialchars($user['first_name'] . ' ' . ($user['last_name'] ?? '')); ?></td>
                                                <td><?php echo htmlspecialchars($user['phone_number']); ?></td>
                                                <td>RWF <?php echo number_format($user['balance'], 2); ?></td>
                                                <td>RWF <?php echo number_format($user['project_revenue'] ?? 0, 2); ?></td>
                                                <td>RWF <?php echo number_format($user['invitation_income'] ?? 0, 2); ?></td>
                                                <td>RWF <?php echo number_format($user['referral_bonus'], 2); ?></td>
                                                <td>RWF <?php echo number_format(($user['balance'] + ($user['project_revenue'] ?? 0) + ($user['invitation_income'] ?? 0) + $user['referral_bonus']), 2); ?></td>
                                                <td>
                                                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#editModal<?php echo $user['id']; ?>">
                                                        <i class="bi bi-pencil"></i> Edit
                                                    </button>
                                                </td>
                                            </tr>
                                            
                                            <!-- Edit Modal -->
                                            <div class="modal fade" id="editModal<?php echo $user['id']; ?>" tabindex="-1">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Edit Financial Information</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <form method="POST">
                                                            <div class="modal-body">
                                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                                <div class="mb-3">
                                                                    <label for="balance_<?php echo $user['id']; ?>" class="form-label">Main Balance ($)</label>
                                                                    <input type="number" class="form-control" id="balance_<?php echo $user['id']; ?>" name="balance" step="0.01" value="<?php echo $user['balance']; ?>" required>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label for="project_revenue_<?php echo $user['id']; ?>" class="form-label">Project Revenue ($)</label>
                                                                    <input type="number" class="form-control" id="project_revenue_<?php echo $user['id']; ?>" name="project_revenue" step="0.01" value="<?php echo $user['project_revenue'] ?? 0; ?>" required>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label for="invitation_income_<?php echo $user['id']; ?>" class="form-label">Invitation Income ($)</label>
                                                                    <input type="number" class="form-control" id="invitation_income_<?php echo $user['id']; ?>" name="invitation_income" step="0.01" value="<?php echo $user['invitation_income'] ?? 0; ?>" required>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label for="referral_bonus_<?php echo $user['id']; ?>" class="form-label">Referral Bonus ($)</label>
                                                                    <input type="number" class="form-control" id="referral_bonus_<?php echo $user['id']; ?>" name="referral_bonus" step="0.01" value="<?php echo $user['referral_bonus']; ?>" required>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                                <button type="submit" class="btn btn-primary">Save Changes</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="9" class="text-center">No users found.</td>
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