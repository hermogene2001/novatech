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

// Initialize variables to prevent undefined variable warnings
$user = [];
$direct_referrals = [];
$total_earnings = 0;
$recent_earnings = [];
$referral_stats = [
    'total_referrals' => 0,
    'level1_referrals' => 0,
    'level2_referrals' => 0
];

// Fetch user referral data
try {
    // Get user's referral code
    $stmt = $pdo->prepare("SELECT referral_code FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get direct referrals (level 1)
    $stmt = $pdo->prepare("SELECT u.first_name, u.last_name, u.created_at, u.status 
                          FROM users u 
                          JOIN referrals r ON u.id = r.referred_id 
                          WHERE r.client_id = ? 
                          ORDER BY u.created_at DESC");
    $stmt->execute([$user_id]);
    $direct_referrals = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get referral earnings
    $stmt = $pdo->prepare("SELECT SUM(amount) as total_earnings FROM referral_earnings WHERE referrer_id = ?");
    $stmt->execute([$user_id]);
    $total_earnings = $stmt->fetch(PDO::FETCH_ASSOC)['total_earnings'] ?? 0;
    
    // Get recent referral earnings
    $stmt = $pdo->prepare("SELECT re.*, u.first_name, u.last_name 
                          FROM referral_earnings re 
                          JOIN users u ON re.referred_id = u.id 
                          WHERE re.referrer_id = ? 
                          ORDER BY re.created_at DESC 
                          LIMIT 10");
    $stmt->execute([$user_id]);
    $recent_earnings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get referral statistics
    $stmt = $pdo->prepare("SELECT 
        COUNT(*) as total_referrals,
        COUNT(CASE WHEN level = 1 THEN 1 END) as level1_referrals,
        COUNT(CASE WHEN level = 2 THEN 1 END) as level2_referrals
        FROM referrals r 
        JOIN users u ON r.referred_id = u.id 
        WHERE r.client_id = ?");
    $stmt->execute([$user_id]);
    $referral_stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Ensure referral_stats is an array even if query returns no results
    if (!$referral_stats) {
        $referral_stats = [
            'total_referrals' => 0,
            'level1_referrals' => 0,
            'level2_referrals' => 0
        ];
    }
    
} catch(PDOException $e) {
    $error = "Error fetching referral data: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Referral Program - Novatech</title>
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
                        <a class="nav-link active" href="referrals.php">Referrals</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="recharge.php">Recharge</a>
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
                <h2>Referral Program</h2>
            </div>
        </div>
        
        <!-- Referral Code and Stats -->
        <div class="row mt-4">
            <div class="col-md-4">
                <div class="card text-white bg-primary">
                    <div class="card-body">
                        <h5 class="card-title">Your Referral Code</h5>
                        <h3><?php echo htmlspecialchars($user['referral_code']); ?></h3>
                        <p class="mb-0">Share this code with friends to earn commissions!</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-white bg-success">
                    <div class="card-body">
                        <h5 class="card-title">Total Earnings</h5>
                        <h3>RWF <?php echo number_format($total_earnings, 2); ?></h3>
                        <p class="mb-0">From your referrals</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-white bg-info">
                    <div class="card-body">
                        <h5 class="card-title">Total Referrals</h5>
                        <h3><?php echo $referral_stats['total_referrals']; ?></h3>
                        <p class="mb-0">Direct: <?php echo $referral_stats['level1_referrals']; ?>, 
                        Indirect: <?php echo $referral_stats['level2_referrals']; ?></p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Referral Link Sharing -->
        <div class="row mt-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h4>Share Your Referral Link</h4>
                    </div>
                    <div class="card-body">
                        <div class="input-group">
                            <input type="text" class="form-control" 
                                   value="http://<?php echo $_SERVER['HTTP_HOST']; ?>/nova/register.php?ref=<?php echo htmlspecialchars($user['referral_code']); ?>" 
                                   readonly>
                            <button class="btn btn-outline-secondary" type="button" 
                                    onclick="copyToClipboard(this)">Copy</button>
                        </div>
                        <div class="mt-3">
                            <p>Share this link with friends and earn commissions on their investments!</p>
                            <ul>
                                <li><strong>Level 1:</strong> Earn 30% on your direct referrals' recharges</li>
                                <li><strong>Level 2:</strong> Earn 4% on your indirect referrals' recharges</li>
                                <li><strong>Level 3:</strong> Earn 1% on your third-level referrals' recharges</li>
                            </ul>
                            <div class="alert alert-info mt-3">
                                <h5>Platform Requirements:</h5>
                                <ul class="mb-0">
                                    <li><strong>Minimum Deposit:</strong> RWF 3000</li>
                                    <li><strong>Minimum Withdrawal:</strong> RWF 3000</li>
                                    <li><strong>Withdrawal Hours:</strong> Monday to Saturday, 09:00 - 15:00</li>
                                </ul>
                            </div>
                            <div class="alert alert-warning mt-3">
                                <h5>How It Works:</h5>
                                <p class="mb-0">When your referral requests a recharge and the agent approves it, you will receive your income based on your referral level.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Recent Referral Earnings -->
        <div class="row mt-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h4>Recent Referral Earnings</h4>
                    </div>
                    <div class="card-body">
                        <?php if (count($recent_earnings) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Referred User</th>
                                            <th>Amount</th>
                                            <th>Level</th>
                                            <th>Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recent_earnings as $earning): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($earning['first_name'] . ' ' . $earning['last_name']); ?></td>
                                                <td>RWF <?php echo number_format($earning['amount'], 2); ?></td>
                                                <td>Level <?php echo $earning['level']; ?></td>
                                                <td><?php echo date('M d, Y', strtotime($earning['created_at'])); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p>No referral earnings yet. Start sharing your referral link!</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Direct Referrals -->
        <div class="row mt-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h4>Your Direct Referrals</h4>
                    </div>
                    <div class="card-body">
                        <?php if (count($direct_referrals) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Joined</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($direct_referrals as $referral): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($referral['first_name'] . ' ' . $referral['last_name']); ?></td>
                                                <td><?php echo date('M d, Y', strtotime($referral['created_at'])); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php echo $referral['status'] == 'active' ? 'success' : 'secondary'; ?>">
                                                        <?php echo ucfirst($referral['status']); ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p>You haven't referred any users yet. Start sharing your referral link!</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function copyToClipboard(button) {
            const input = button.previousElementSibling;
            input.select();
            document.execCommand('copy');
            
            // Show feedback
            const originalText = button.textContent;
            button.textContent = 'Copied!';
            setTimeout(() => {
                button.textContent = originalText;
            }, 2000);
        }
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/main.js"></script>
</body>
</html>