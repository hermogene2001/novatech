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

// Fetch user data
try {
    $stmt = $pdo->prepare("SELECT balance, referral_bonus FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Fetch user bank details
    $stmt = $pdo->prepare("SELECT * FROM user_banks WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $bank = $stmt->fetch(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error = "Error fetching user data: " . $e->getMessage();
}

// Handle withdrawal request
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $amount = $_POST['amount'];
    $source = $_POST['source'];
    
    // Validate amount
    if ($amount <= 0) {
        $message = "Please enter a valid amount.";
    } else if (($source == 'main' && $amount > $user['balance']) || 
               ($source == 'referral' && $amount > $user['referral_bonus'])) {
        $message = "Insufficient balance for withdrawal.";
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
            }
            
            // Insert withdrawal request with assigned agent
            $stmt = $pdo->prepare("INSERT INTO withdrawals (client_id, amount, transaction_type, source, status, agent_id) VALUES (?, ?, 'withdrawal', ?, 'pending', ?)");
            $stmt->execute([$user_id, $amount, $source, $agent_id]);
            
            $message = "Withdrawal request submitted successfully. It will be processed within 24 hours.";
        } catch(PDOException $e) {
            $message = "Error processing withdrawal: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Withdraw Funds - Novatech Investment Platform</title>
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
                        <a class="nav-link active" href="#">Withdraw</a>
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
                <h2>Withdraw Funds</h2>
                <?php if ($message): ?>
                    <div class="alert alert-info"><?php echo $message; ?></div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="row mt-4">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h4>Your Balances</h4>
                    </div>
                    <div class="card-body">
                        <div class="balance-card bg-light">
                            <h5>Main Balance</h5>
                            <div class="balance-amount text-primary">$<?php echo number_format($user['balance'], 2); ?></div>
                        </div>
                        <div class="balance-card bg-light mt-3">
                            <h5>Referral Bonus</h5>
                            <div class="balance-amount text-success">$<?php echo number_format($user['referral_bonus'], 2); ?></div>
                        </div>
                    </div>
                </div>
                
                <?php if ($bank): ?>
                    <div class="card mt-4">
                        <div class="card-header">
                            <h4>Bank Details</h4>
                        </div>
                        <div class="card-body">
                            <p><strong>Bank Name:</strong> <?php echo htmlspecialchars($bank['bank_name']); ?></p>
                            <p><strong>Account Number:</strong> <?php echo htmlspecialchars($bank['account_number']); ?></p>
                            <p><strong>Account Holder:</strong> <?php echo htmlspecialchars($bank['account_holder']); ?></p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h4>Request Withdrawal</h4>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label for="amount" class="form-label">Amount ($)</label>
                                <input type="number" class="form-control" id="amount" name="amount" step="0.01" min="0" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="source" class="form-label">Source</label>
                                <select class="form-select" id="source" name="source" required>
                                    <option value="main">Main Balance ($<?php echo number_format($user['balance'], 2); ?>)</option>
                                    <option value="referral">Referral Bonus ($<?php echo number_format($user['referral_bonus'], 2); ?>)</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <p><strong>Minimum Withdrawal:</strong> $3000.00</p>
                                <p><strong>Processing Time:</strong> Within 24 hours</p>
                                <p><strong>Fee:</strong> None</p>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">Submit Withdrawal Request</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/main.js"></script>
</body>
</html>