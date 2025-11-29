<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is agent
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'agent') {
    header('Location: ../login.php');
    exit();
}

$agent_id = $_SESSION['user_id'];
$agent_name = $_SESSION['first_name'];
$message = '';

// Handle approve/reject actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $recharge_id = $_GET['id'];
    
    try {
        if ($action == 'approve') {
            // Get recharge details
            $stmt = $pdo->prepare("SELECT * FROM recharges WHERE id = ?");
            $stmt->execute([$recharge_id]);
            $recharge = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($recharge) {
                // Update recharge status
                $stmt = $pdo->prepare("UPDATE recharges SET status = 'confirmed', agent_id = ? WHERE id = ?");
                $stmt->execute([$agent_id, $recharge_id]);
                
                // Add amount to user's balance
                $stmt = $pdo->prepare("UPDATE users SET balance = balance + ? WHERE id = ?");
                $stmt->execute([$recharge['amount'], $recharge['client_id']]);
                
                // Record transaction
                $stmt = $pdo->prepare("INSERT INTO transactions (user_id, transaction_type, amount, status) 
                                      VALUES (?, 'recharge', ?, 'approved')");
                $stmt->execute([$recharge['client_id'], $recharge['amount']]);
                
                // Get user details for notification
                $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
                $stmt->execute([$recharge['client_id']]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Send notification
                sendTransactionNotification($user, 'recharge', $recharge['amount']);
                
                $message = "Recharge approved successfully.";
            }
        } else if ($action == 'reject') {
            $stmt = $pdo->prepare("UPDATE recharges SET status = 'rejected', agent_id = ? WHERE id = ?");
            $stmt->execute([$agent_id, $recharge_id]);
            $message = "Recharge rejected.";
        }
    } catch(PDOException $e) {
        $message = "Error processing request: " . $e->getMessage();
    }
}

// Fetch all recharges assigned to this agent
try {
    $stmt = $pdo->prepare("SELECT r.*, u.first_name, u.last_name, u.phone_number 
                          FROM recharges r 
                          JOIN users u ON r.client_id = u.id 
                          WHERE r.agent_id = ?
                          ORDER BY r.recharge_time DESC");
    $stmt->execute([$agent_id]);
    $recharges = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error = "Error fetching recharges: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recharges - Novatech Agent</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="#">Novatech Agent</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="#">Recharges</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="withdrawals.php">Withdrawals</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <?php echo htmlspecialchars($agent_name); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="profile.php">Profile</a></li>
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
                <h2>Recharge Requests</h2>
                <?php if ($message): ?>
                    <div class="alert alert-info"><?php echo $message; ?></div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="row mt-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h4>All Recharge Requests</h4>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Client</th>
                                        <th>Phone</th>
                                        <th>Amount</th>
                                        <th>Request Time</th>
                                        <th>Status</th>
                                        <th>Processed By</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recharges as $recharge): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($recharge['first_name'] . ' ' . $recharge['last_name']); ?></td>
                                            <td><?php echo htmlspecialchars($recharge['phone_number']); ?></td>
                                            <td>$<?php echo number_format($recharge['amount'], 2); ?></td>
                                            <td><?php echo date('M d, Y H:i', strtotime($recharge['recharge_time'])); ?></td>
                                            <td>
                                                <span class="badge bg-<?php 
                                                    echo $recharge['status'] == 'confirmed' ? 'success' : 
                                                        ($recharge['status'] == 'rejected' ? 'danger' : 'warning'); ?>">
                                                    <?php echo ucfirst($recharge['status']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo $recharge['agent_id'] ? "Agent #$recharge[agent_id]" : 'Pending'; ?></td>
                                            <td>
                                                <?php if ($recharge['status'] == 'pending'): ?>
                                                    <a href="?action=approve&id=<?php echo $recharge['id']; ?>" class="btn btn-sm btn-success">Approve</a>
                                                    <a href="?action=reject&id=<?php echo $recharge['id']; ?>" class="btn btn-sm btn-danger">Reject</a>
                                                <?php else: ?>
                                                    Processed
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