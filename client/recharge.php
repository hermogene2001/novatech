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
$message = '';
$error = '';
$agent_bank_details = null;

// Handle recharge request
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $amount = floatval($_POST['amount']);
    
    // Validate amount
    if ($amount <= 0) {
        $message = "Please enter a valid amount.";
    } else {
        try {
            // Get all available agents
            $stmt = $pdo->prepare("SELECT id FROM users WHERE role = 'agent' AND status = 'active'");
            $stmt->execute();
            $agents = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            // Randomly assign an agent if any exist, otherwise leave as NULL
            $agent_id = null;
            if (!empty($agents)) {
                $agent_id = $agents[array_rand($agents)];
                
                // Get agent bank details
                $stmt = $pdo->prepare("SELECT u.first_name, u.last_name, ub.bank_name, ub.account_number, ub.account_holder 
                                      FROM users u 
                                      LEFT JOIN user_banks ub ON u.id = ub.user_id 
                                      WHERE u.id = ?");
                $stmt->execute([$agent_id]);
                $agent_bank_details = $stmt->fetch(PDO::FETCH_ASSOC);
            }
            
            // Create a recharge request with assigned agent
            $stmt = $pdo->prepare("INSERT INTO recharges (client_id, agent_id, amount, recharge_time, status) 
                                  VALUES (?, ?, ?, NOW(), 'pending')");
            $stmt->execute([$user_id, $agent_id, $amount]);
            
            $message = "Recharge request submitted successfully. Please transfer the funds to the agent's bank account below.";
        } catch(PDOException $e) {
            $message = "Error processing recharge request: " . $e->getMessage();
        }
    }
}

// Fetch user's pending recharge requests
try {
    $stmt = $pdo->prepare("SELECT * FROM recharges WHERE client_id = ? ORDER BY recharge_time DESC");
    $stmt->execute([$user_id]);
    $recharge_history = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error = "Error fetching recharge history: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recharge Account - Novatech Investment Platform</title>
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
                        <a class="nav-link active" href="recharge.php">Recharge</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="withdraw.php">Withdraw</a>
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
                <h2>Recharge Account</h2>
                <?php if ($message): ?>
                    <div class="alert alert-info"><?php echo $message; ?></div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
            </div>
        </div>
        
        <?php if ($agent_bank_details && isset($_POST['amount'])): ?>
        <div class="row mt-4">
            <div class="col-md-12">
                <div class="card border-success">
                    <div class="card-header bg-success text-white">
                        <h4>Transfer Funds to Agent</h4>
                    </div>
                    <div class="card-body">
                        <p>Please transfer <strong>$<?php echo number_format($_POST['amount'], 2); ?></strong> to the following bank account:</p>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <table class="table">
                                    <tr>
                                        <th>Agent Name:</th>
                                        <td><?php echo htmlspecialchars($agent_bank_details['first_name'] . ' ' . $agent_bank_details['last_name']); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Bank Name:</th>
                                        <td><?php echo htmlspecialchars($agent_bank_details['bank_name']); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Account Number:</th>
                                        <td><?php echo htmlspecialchars($agent_bank_details['account_number']); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Account Holder:</th>
                                        <td><?php echo htmlspecialchars($agent_bank_details['account_holder']); ?></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                        
                        <div class="alert alert-warning">
                            <strong>Important:</strong> After transferring the funds, please wait for the agent to confirm your payment. This usually takes within 24 hours.
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="row mt-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h4>Request Recharge</h4>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label for="amount" class="form-label">Amount ($)</label>
                                <input type="number" class="form-control" id="amount" name="amount" step="0.01" min="1" required>
                                <div class="form-text">Enter the amount you wish to add to your account.</div>
                            </div>
                            
                            <div class="mb-3">
                                <p><strong>Instructions:</strong></p>
                                <ol>
                                    <li>Submit a recharge request using the form above</li>
                                    <li>Transfer the funds to the assigned agent's bank account (details will be provided after submission)</li>
                                    <li>An agent will verify and process your request within 24 hours</li>
                                    <li>Funds will be added to your account once approved</li>
                                </ol>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">Submit Recharge Request</button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h4>Recharge History</h4>
                    </div>
                    <div class="card-body">
                        <?php if (count($recharge_history) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Amount</th>
                                            <th>Date</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recharge_history as $recharge): ?>
                                            <tr>
                                                <td>$<?php echo number_format($recharge['amount'], 2); ?></td>
                                                <td><?php echo date('M d, Y H:i', strtotime($recharge['recharge_time'])); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php 
                                                        echo $recharge['status'] == 'confirmed' ? 'success' : 
                                                            ($recharge['status'] == 'rejected' ? 'danger' : 'warning'); ?>">
                                                        <?php echo ucfirst($recharge['status']); ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p>No recharge requests found.</p>
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