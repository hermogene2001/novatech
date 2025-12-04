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
                
                // Trigger referral commissions processing
                processReferralCommissions($pdo, $recharge_id);
                
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

// Function to process referral commissions when a recharge is approved
function processReferralCommissions($pdo, $recharge_id) {
    try {
        // Get the recharge details
        $stmt = $pdo->prepare("SELECT r.*, u.invitation_code, u.id as user_id
                              FROM recharges r
                              JOIN users u ON r.client_id = u.id
                              WHERE r.id = ? AND r.status = 'confirmed'");
        $stmt->execute([$recharge_id]);
        $recharge = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$recharge) {
            return; // Recharge not found or not confirmed
        }
        
        $user_id = $recharge['client_id'];
        $recharge_amount = $recharge['amount'];
        $invitation_code = $recharge['invitation_code'];
        
        // Process referral commissions if user was referred
        if (!empty($invitation_code)) {
            // Find the referrer
            $stmt = $pdo->prepare("SELECT id FROM users WHERE referral_code = ?");
            $stmt->execute([$invitation_code]);
            $referrer = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($referrer) {
                $referrer_id = $referrer['id'];
                
                // Calculate level 1 commission (30%)
                $level1_commission = $recharge_amount * 0.30;
                
                // Add to referrer's referral bonus
                $stmt = $pdo->prepare("UPDATE users SET referral_bonus = referral_bonus + ? WHERE id = ?");
                $stmt->execute([$level1_commission, $referrer_id]);
                
                // Record transaction
                $stmt = $pdo->prepare("INSERT INTO transactions (user_id, transaction_type, amount, status) 
                                          VALUES (?, 'referral_commission', ?, 'approved')");
                $stmt->execute([$referrer_id, $level1_commission]);
                
                // Record referral earning
                $stmt = $pdo->prepare("INSERT INTO referral_earnings (referrer_id, referred_id, amount, level) 
                                          VALUES (?, ?, ?, 1)");
                $stmt->execute([$referrer_id, $user_id, $level1_commission]);
                
                // Check for level 2 referrals (referrer's referrer)
                $stmt = $pdo->prepare("SELECT u.invitation_code FROM users u WHERE u.id = ?");
                $stmt->execute([$referrer_id]);
                $referrer_data = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($referrer_data && !empty($referrer_data['invitation_code'])) {
                    $referrer_invitation_code = $referrer_data['invitation_code'];
                    
                    // Find the level 2 referrer
                    $stmt = $pdo->prepare("SELECT id FROM users WHERE referral_code = ?");
                    $stmt->execute([$referrer_invitation_code]);
                    $level2_referrer = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($level2_referrer) {
                        $level2_referrer_id = $level2_referrer['id'];
                        
                        // Calculate level 2 commission (4%)
                        $level2_commission = $recharge_amount * 0.04;
                        
                        // Add to level 2 referrer's referral bonus
                        $stmt = $pdo->prepare("UPDATE users SET referral_bonus = referral_bonus + ? WHERE id = ?");
                        $stmt->execute([$level2_commission, $level2_referrer_id]);
                        
                        // Record referral earning
                        $stmt = $pdo->prepare("INSERT INTO referral_earnings (referrer_id, referred_id, amount, level) 
                                                  VALUES (?, ?, ?, 2)");
                        $stmt->execute([$level2_referrer_id, $user_id, $level2_commission]);
                        
                        // Record transaction
                        $stmt = $pdo->prepare("INSERT INTO transactions (user_id, transaction_type, amount, status) 
                                                  VALUES (?, 'referral_commission', ?, 'approved')");
                        $stmt->execute([$level2_referrer_id, $level2_commission]);
                        
                        // Check for level 3 referrals (level 2 referrer's referrer)
                        $stmt = $pdo->prepare("SELECT u.invitation_code FROM users u WHERE u.id = ?");
                        $stmt->execute([$level2_referrer_id]);
                        $level2_referrer_data = $stmt->fetch(PDO::FETCH_ASSOC);
                        
                        if ($level2_referrer_data && !empty($level2_referrer_data['invitation_code'])) {
                            $level3_referrer_invitation_code = $level2_referrer_data['invitation_code'];
                            
                            // Find the level 3 referrer
                            $stmt = $pdo->prepare("SELECT id FROM users WHERE referral_code = ?");
                            $stmt->execute([$level3_referrer_invitation_code]);
                            $level3_referrer = $stmt->fetch(PDO::FETCH_ASSOC);
                            
                            if ($level3_referrer) {
                                $level3_referrer_id = $level3_referrer['id'];
                                
                                // Calculate level 3 commission (1%)
                                $level3_commission = $recharge_amount * 0.01;
                                
                                // Add to level 3 referrer's referral bonus
                                $stmt = $pdo->prepare("UPDATE users SET referral_bonus = referral_bonus + ? WHERE id = ?");
                                $stmt->execute([$level3_commission, $level3_referrer_id]);
                                
                                // Record referral earning
                                $stmt = $pdo->prepare("INSERT INTO referral_earnings (referrer_id, referred_id, amount, level) 
                                                          VALUES (?, ?, ?, 3)");
                                $stmt->execute([$level3_referrer_id, $user_id, $level3_commission]);
                                
                                // Record transaction
                                $stmt = $pdo->prepare("INSERT INTO transactions (user_id, transaction_type, amount, status) 
                                                          VALUES (?, 'referral_commission', ?, 'approved')");
                                $stmt->execute([$level3_referrer_id, $level3_commission]);
                            }
                        }
                    }
                }
            }
        }
        
        // Mark recharge as processed for referrals
        $stmt = $pdo->prepare("UPDATE recharges SET processed_for_referral = 1 WHERE id = ?");
        $stmt->execute([$recharge_id]);
        
    } catch (PDOException $e) {
        error_log("Error processing referral commission for recharge ID $recharge_id: " . $e->getMessage());
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
                                            <td>RWF <?php echo number_format($recharge['amount'], 2); ?></td>
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