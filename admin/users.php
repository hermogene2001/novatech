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

// Handle user actions (edit/delete/reset password)
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
                } elseif ($action === 'update_role') {
                    // Update user role
                    $new_role = $_POST['new_role'] ?? '';
                    
                    // Validate role
                    $valid_roles = ['client', 'agent'];
                    if (in_array($new_role, $valid_roles)) {
                        $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
                        $stmt->execute([$new_role, $user_id]);
                        $message = "User role updated successfully.";
                    } else {
                        $message = "Invalid role selected.";
                    }
                } elseif ($action === 'reset_password') {
                    // Reset user password to a default value
                    $default_password = '123456'; // Default password
                    $hashed_password = password_hash($default_password, PASSWORD_DEFAULT);
                    
                    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                    $stmt->execute([$hashed_password, $user_id]);
                    
                    $message = "User password reset successfully to default password: " . $default_password;
                }
            } catch(PDOException $e) {
                $pdo->rollback();
                $message = "Error processing request: " . $e->getMessage();
            }
        }
    }
}

// Handle search/filter
$search_term = '';
$role_filter = '';
$status_filter = '';
$sort_by = 'id';
$sort_order = 'DESC';

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    $search_term = $_GET['search'] ?? '';
    $role_filter = $_GET['role'] ?? '';
    $status_filter = $_GET['status'] ?? '';
    $sort_by = $_GET['sort_by'] ?? 'id';
    $sort_order = $_GET['sort_order'] ?? 'DESC';
    
    // Validate sort parameters
    $valid_sort_columns = ['id', 'first_name', 'phone_number', 'email', 'balance', 'referral_bonus', 'created_at'];
    $valid_sort_orders = ['ASC', 'DESC'];
    
    if (!in_array($sort_by, $valid_sort_columns)) {
        $sort_by = 'id';
    }
    
    if (!in_array($sort_order, $valid_sort_orders)) {
        $sort_order = 'DESC';
    }
}

// Build query based on filters
$sql = "SELECT * FROM users WHERE 1=1";
$params = [];

if (!empty($search_term)) {
    $sql .= " AND (first_name LIKE ? OR last_name LIKE ? OR phone_number LIKE ? OR email LIKE ? OR referral_code LIKE ?)";
    $params[] = "%$search_term%";
    $params[] = "%$search_term%";
    $params[] = "%$search_term%";
    $params[] = "%$search_term%";
    $params[] = "%$search_term%";
}

if (!empty($role_filter)) {
    $sql .= " AND role = ?";
    $params[] = $role_filter;
}

if (!empty($status_filter)) {
    $sql .= " AND status = ?";
    $params[] = $status_filter;
}

$sql .= " ORDER BY $sort_by $sort_order";

// Fetch users with filters
try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        .role-select {
            width: auto !important;
            display: inline-block !important;
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
                <div class="alert alert-warning">
                    <strong>Note:</strong> When resetting a user's password, it will be set to the default password: <strong>123456</strong>. 
                    Advise the user to change their password after logging in for security reasons.
                </div>
            </div>
        </div>
        
        <div class="row mt-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h4>All Users</h4>
                    </div>
                    <div class="card-body">
                        <form method="GET" class="row g-3 mb-4">
                            <div class="col-md-3">
                                <label for="search" class="form-label">Search</label>
                                <input type="text" class="form-control" id="search" name="search" placeholder="Name, Phone, Email, Referral Code" value="<?php echo htmlspecialchars($search_term); ?>">
                            </div>
                            <div class="col-md-2">
                                <label for="role" class="form-label">Role</label>
                                <select class="form-select" id="role" name="role">
                                    <option value="" <?php echo empty($role_filter) ? 'selected' : ''; ?>>All Roles</option>
                                    <option value="client" <?php echo $role_filter === 'client' ? 'selected' : ''; ?>>Client</option>
                                    <option value="agent" <?php echo $role_filter === 'agent' ? 'selected' : ''; ?>>Agent</option>
                                    <option value="admin" <?php echo $role_filter === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="" <?php echo empty($status_filter) ? 'selected' : ''; ?>>All Statuses</option>
                                    <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                                    <option value="inactive" <?php echo $status_filter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="sort_by" class="form-label">Sort By</label>
                                <div class="row g-2">
                                    <div class="col-7">
                                        <select class="form-select" id="sort_by" name="sort_by">
                                            <option value="id" <?php echo $sort_by === 'id' ? 'selected' : ''; ?>>ID</option>
                                            <option value="first_name" <?php echo $sort_by === 'first_name' ? 'selected' : ''; ?>>Name</option>
                                            <option value="phone_number" <?php echo $sort_by === 'phone_number' ? 'selected' : ''; ?>>Phone</option>
                                            <option value="email" <?php echo $sort_by === 'email' ? 'selected' : ''; ?>>Email</option>
                                            <option value="balance" <?php echo $sort_by === 'balance' ? 'selected' : ''; ?>>Balance</option>
                                            <option value="referral_bonus" <?php echo $sort_by === 'referral_bonus' ? 'selected' : ''; ?>>Referral Bonus</option>
                                            <option value="created_at" <?php echo $sort_by === 'created_at' ? 'selected' : ''; ?>>Registration Date</option>
                                        </select>
                                    </div>
                                    <div class="col-5">
                                        <select class="form-select" id="sort_order" name="sort_order">
                                            <option value="ASC" <?php echo $sort_order === 'ASC' ? 'selected' : ''; ?>>Asc</option>
                                            <option value="DESC" <?php echo $sort_order === 'DESC' ? 'selected' : ''; ?>>Desc</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">&nbsp;</label>
                                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                    <button type="submit" class="btn btn-primary me-md-2">Filter</button>
                                    <?php if (!empty($search_term) || !empty($role_filter) || !empty($status_filter)): ?>
                                        <a href="users.php" class="btn btn-outline-secondary">Clear</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="card mt-4">
                    <div class="card-body">
                        <p class="text-muted mb-3">
                            Showing <?php echo count($users); ?> user(s)
                            <?php if (!empty($search_term) || !empty($role_filter) || !empty($status_filter)): ?>
                                <a href="users.php" class="btn btn-sm btn-outline-primary ms-2">Clear Filters</a>
                            <?php endif; ?>
                        </p>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>
                                            <a href="?search=<?php echo urlencode($search_term); ?>&role=<?php echo urlencode($role_filter); ?>&status=<?php echo urlencode($status_filter); ?>&sort_by=id&sort_order=<?php echo $sort_by === 'id' && $sort_order === 'ASC' ? 'DESC' : 'ASC'; ?>" class="text-decoration-none">
                                                ID
                                                <?php if ($sort_by === 'id'): ?>
                                                    <i class="bi bi-arrow-<?php echo $sort_order === 'ASC' ? 'up' : 'down'; ?>"></i>
                                                <?php endif; ?>
                                            </a>
                                        </th>
                                        <th>
                                            <a href="?search=<?php echo urlencode($search_term); ?>&role=<?php echo urlencode($role_filter); ?>&status=<?php echo urlencode($status_filter); ?>&sort_by=first_name&sort_order=<?php echo $sort_by === 'first_name' && $sort_order === 'ASC' ? 'DESC' : 'ASC'; ?>" class="text-decoration-none">
                                                Name
                                                <?php if ($sort_by === 'first_name'): ?>
                                                    <i class="bi bi-arrow-<?php echo $sort_order === 'ASC' ? 'up' : 'down'; ?>"></i>
                                                <?php endif; ?>
                                            </a>
                                        </th>
                                        <th>Phone</th>
                                        <th>
                                            <a href="?search=<?php echo urlencode($search_term); ?>&role=<?php echo urlencode($role_filter); ?>&status=<?php echo urlencode($status_filter); ?>&sort_by=email&sort_order=<?php echo $sort_by === 'email' && $sort_order === 'ASC' ? 'DESC' : 'ASC'; ?>" class="text-decoration-none">
                                                Email
                                                <?php if ($sort_by === 'email'): ?>
                                                    <i class="bi bi-arrow-<?php echo $sort_order === 'ASC' ? 'up' : 'down'; ?>"></i>
                                                <?php endif; ?>
                                            </a>
                                        </th>
                                        <th>Role</th>
                                        <th>
                                            <a href="?search=<?php echo urlencode($search_term); ?>&role=<?php echo urlencode($role_filter); ?>&status=<?php echo urlencode($status_filter); ?>&sort_by=balance&sort_order=<?php echo $sort_by === 'balance' && $sort_order === 'ASC' ? 'DESC' : 'ASC'; ?>" class="text-decoration-none">
                                                Balance
                                                <?php if ($sort_by === 'balance'): ?>
                                                    <i class="bi bi-arrow-<?php echo $sort_order === 'ASC' ? 'up' : 'down'; ?>"></i>
                                                <?php endif; ?>
                                            </a>
                                        </th>
                                        <th>
                                            <a href="?search=<?php echo urlencode($search_term); ?>&role=<?php echo urlencode($role_filter); ?>&status=<?php echo urlencode($status_filter); ?>&sort_by=referral_bonus&sort_order=<?php echo $sort_by === 'referral_bonus' && $sort_order === 'ASC' ? 'DESC' : 'ASC'; ?>" class="text-decoration-none">
                                                Referral Bonus
                                                <?php if ($sort_by === 'referral_bonus'): ?>
                                                    <i class="bi bi-arrow-<?php echo $sort_order === 'ASC' ? 'up' : 'down'; ?>"></i>
                                                <?php endif; ?>
                                            </a>
                                        </th>
                                        <th>Status</th>
                                        <th>
                                            <a href="?search=<?php echo urlencode($search_term); ?>&role=<?php echo urlencode($role_filter); ?>&status=<?php echo urlencode($status_filter); ?>&sort_by=created_at&sort_order=<?php echo $sort_by === 'created_at' && $sort_order === 'ASC' ? 'DESC' : 'ASC'; ?>" class="text-decoration-none">
                                                Created At
                                                <?php if ($sort_by === 'created_at'): ?>
                                                    <i class="bi bi-arrow-<?php echo $sort_order === 'ASC' ? 'up' : 'down'; ?>"></i>
                                                <?php endif; ?>
                                            </a>
                                        </th>
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
                                                    <!-- Role change form -->
                                                    <form method="POST" class="d-inline mb-1">
                                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                        <input type="hidden" name="action" value="update_role">
                                                        <select name="new_role" class="form-select form-select-sm d-inline w-auto" onchange="this.form.submit()">
                                                            <option value="client" <?php echo $user['role'] == 'client' ? 'selected' : ''; ?>>Client</option>
                                                            <option value="agent" <?php echo $user['role'] == 'agent' ? 'selected' : ''; ?>>Agent</option>
                                                        </select>
                                                    </form>
                                                    
                                                    <!-- Edit button -->
                                                    <a href="edit_user.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-primary">Edit</a>
                                                    
                                                    <!-- Reset Password button -->
                                                    <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to reset this user\'s password to the default password (123456)?')">
                                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                        <input type="hidden" name="action" value="reset_password">
                                                        <button type="submit" class="btn btn-sm btn-warning">Reset Password</button>
                                                    </form>
                                                    
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