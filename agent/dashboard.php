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

// Fetch pending recharges
try {
    // Fetch pending recharges assigned to this agent
    $stmt = $pdo->prepare("SELECT r.*, u.first_name, u.last_name, u.phone_number 
                          FROM recharges r 
                          JOIN users u ON r.client_id = u.id 
                          WHERE r.status = 'pending' AND r.agent_id = ?
                          ORDER BY r.recharge_time ASC");
    $stmt->execute([$agent_id]);
    $pending_recharges = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Fetch pending withdrawals assigned to this agent
    $stmt = $pdo->prepare("SELECT w.*, u.first_name, u.last_name, u.phone_number 
                          FROM withdrawals w 
                          JOIN users u ON w.client_id = u.id 
                          WHERE w.status = 'pending' AND w.agent_id = ?
                          ORDER BY w.date ASC");
    $stmt->execute([$agent_id]);
    $pending_withdrawals = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    $error = "Error fetching data: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agent Dashboard - Novatech Investment Platform</title>
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
                        <a class="nav-link active" href="#">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="recharges.php">Recharges</a>
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
                <h2>Agent Dashboard</h2>
                <?php if ($message): ?>
                    <div class="alert alert-info"><?php echo $message; ?></div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="row mt-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h4>Pending Recharges</h4>
                    </div>
                    <div class="card-body">
                        <?php if (count($pending_recharges) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Client</th>
                                            <th>Phone</th>
                                            <th>Amount</th>
                                            <th>Request Time</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($pending_recharges as $recharge): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($recharge['first_name'] . ' ' . $recharge['last_name']); ?></td>
                                                <td><?php echo htmlspecialchars($recharge['phone_number']); ?></td>
                                                <td>RWF <?php echo number_format($recharge['amount'], 2); ?></td>
                                                <td><?php echo date('M d, Y H:i', strtotime($recharge['recharge_time'])); ?></td>
                                                <td>
                                                    <a href="recharges.php?action=approve&id=<?php echo $recharge['id']; ?>" class="btn btn-sm btn-success">Approve</a>
                                                    <a href="recharges.php?action=reject&id=<?php echo $recharge['id']; ?>" class="btn btn-sm btn-danger">Reject</a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p>No pending recharges.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row mt-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h4>Pending Withdrawals</h4>
                    </div>
                    <div class="card-body">
                        <?php if (count($pending_withdrawals) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Client</th>
                                            <th>Phone</th>
                                            <th>Amount</th>
                                            <th>Request Time</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($pending_withdrawals as $withdrawal): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($withdrawal['first_name'] . ' ' . $withdrawal['last_name']); ?></td>
                                                <td><?php echo htmlspecialchars($withdrawal['phone_number']); ?></td>
                                                <td>$<?php echo number_format($withdrawal['amount'], 2); ?></td>
                                                <td><?php echo date('M d, Y H:i', strtotime($withdrawal['date'])); ?></td>
                                                <td>
                                                    <a href="withdrawals.php?action=approve&id=<?php echo $withdrawal['id']; ?>" class="btn btn-sm btn-success">Approve</a>
                                                    <a href="withdrawals.php?action=reject&id=<?php echo $withdrawal['id']; ?>" class="btn btn-sm btn-danger">Reject</a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p>No pending withdrawals.</p>
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