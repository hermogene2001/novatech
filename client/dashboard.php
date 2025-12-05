<?php
session_start();
require_once '../config/database.php';
require_once '../lib/SocialLinks.php';

// Check if user is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'client') {
    header('Location: ../login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$first_name = $_SESSION['first_name'];

// Initialize SocialLinks class
$socialLinks = new SocialLinks($pdo);
$links = $socialLinks->getAllLinks();
$hasSocialLinks = $socialLinks->hasAnyLinks();

// Fetch user data
try {
    $stmt = $pdo->prepare("SELECT u.*, cf.project_revenue, cf.invitation_income 
                          FROM users u 
                          JOIN clients_finances cf ON u.id = cf.client_id 
                          WHERE u.id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Fetch user products with calculated end dates if needed
    $stmt = $pdo->prepare("SELECT p.*, pur.purchase_date, pur.status, pur.end_datetime, pr.cycle as product_cycle
                          FROM products p 
                          JOIN purchases pur ON p.id = pur.product_id 
                          JOIN products pr ON p.id = pr.id
                          WHERE pur.client_id = ? AND pur.status = 'active'");
    $stmt->execute([$user_id]);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // For products without end_datetime, calculate it based on cycle
    foreach ($products as &$product) {
        if (is_null($product['end_datetime']) && !is_null($product['purchase_date']) && !is_null($product['product_cycle'])) {
            $product['end_datetime'] = date('Y-m-d H:i:s', strtotime($product['purchase_date'] . " +{$product['product_cycle']} days"));
        }
    }
    
    // Fetch recent transactions
    $stmt = $pdo->prepare("SELECT * FROM transactions WHERE user_id = ? ORDER BY transaction_date DESC LIMIT 5");
    $stmt->execute([$user_id]);
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate total daily earnings
    $total_daily_earnings = 0;
    foreach ($products as $product) {
        $total_daily_earnings += $product['daily_earning'];
    }
    
    // Count total investments
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM purchases WHERE client_id = ?");
    $stmt->execute([$user_id]);
    $total_investments = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Count active investments
    $active_investments = count($products);
    
    // Get pending withdrawals
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM withdrawals WHERE client_id = ? AND status = 'pending'");
    $stmt->execute([$user_id]);
    $pending_withdrawals = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Get pending recharges
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM recharges WHERE client_id = ? AND status = 'pending'");
    $stmt->execute([$user_id]);
    $pending_recharges = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Calculate total investment amount
    $total_investment_amount = 0;
    $stmt = $pdo->prepare("SELECT p.price FROM products p JOIN purchases pur ON p.id = pur.product_id WHERE pur.client_id = ?");
    $stmt->execute([$user_id]);
    $investments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($investments as $investment) {
        $total_investment_amount += $investment['price'];
    }
    
    // Count referrals
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM referrals WHERE client_id = ?");
    $stmt->execute([$user_id]);
    $referral_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
} catch(PDOException $e) {
    $error = "Error fetching dashboard data: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Client Dashboard - Novatech Investment Platform</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .stat-card {
            transition: transform 0.3s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .quick-action-btn {
            transition: all 0.3s;
        }
        .quick-action-btn:hover {
            transform: scale(1.05);
        }
        .investment-progress {
            height: 10px;
        }
    </style>
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
                        <a class="nav-link active" href="#">Dashboard</a>
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
                        <a class="nav-link" href="withdraw.php">Withdraw</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="withdrawal_status.php">Withdrawal Status</a>
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
                <h2>Welcome back, <?php echo htmlspecialchars($first_name); ?>!</h2>
                <p class="text-muted">Here's your investment dashboard overview</p>
            </div>
        </div>
        
        <!-- Balance Cards -->
        <div class="row mt-4">
            <div class="col-md-3 mb-4">
                <div class="card stat-card text-white bg-primary">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h5 class="card-title">Main Balance</h5>
                                <h3>RWF <?php echo number_format($user['balance'], 2); ?></h3>
                            </div>
                            <i class="bi bi-wallet fs-1"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="card stat-card text-white bg-success">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h5 class="card-title">Project Revenue</h5>
                                <h3>RWF <?php echo number_format($user['project_revenue'], 2); ?></h3>
                            </div>
                            <i class="bi bi-graph-up fs-1"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="card stat-card text-white bg-info">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h5 class="card-title">Referral Bonus</h5>
                                <h3>RWF <?php echo number_format($user['referral_bonus'], 2); ?></h3>
                            </div>
                            <i class="bi bi-people fs-1"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="card stat-card text-white bg-warning">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h5 class="card-title">Daily Earnings</h5>
                                <h3>RWF <?php echo number_format($total_daily_earnings, 2); ?></h3>
                            </div>
                            <i class="bi bi-currency-exchange fs-1"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Secondary Stats -->
        <div class="row">
            <div class="col-md-3 mb-4">
                <div class="card stat-card text-white bg-danger">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h5 class="card-title">Total Investments</h5>
                                <h3><?php echo $total_investments; ?></h3>
                            </div>
                            <i class="bi bi-box-seam fs-1"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="card stat-card text-white bg-secondary">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h5 class="card-title">Active Investments</h5>
                                <h3><?php echo $active_investments; ?></h3>
                            </div>
                            <i class="bi bi-lightning fs-1"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="card stat-card text-white bg-dark">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h5 class="card-title">Total Invested</h5>
                                <h3>RWF <?php echo number_format($total_investment_amount, 2); ?></h3>
                            </div>
                            <i class="bi bi-currency-exchange fs-1"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="card stat-card text-white bg-primary">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h5 class="card-title">Referrals</h5>
                                <h3><?php echo $referral_count; ?></h3>
                            </div>
                            <i class="bi bi-share fs-1"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Active Investments -->
        <div class="row mt-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h4>Your Active Investments</h4>
                    </div>
                    <div class="card-body">
                        <?php if (count($products) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Product</th>
                                            <th>Investment Amount</th>
                                            <th>Daily Earning</th>
                                            <th>Purchase Date</th>
                                            <th>End Date</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($products as $product): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($product['name']); ?></td>
                                                <td>RWF <?php echo number_format($product['price'], 2); ?></td>
                                                <td>RWF <?php echo number_format($product['daily_earning'], 2); ?></td>
                                                <td><?php echo date('M d, Y', strtotime($product['purchase_date'])); ?></td>
                                                <td><?php echo $product['end_datetime'] ? date('M d, Y', strtotime($product['end_datetime'])) : 'N/A'; ?></td>
                                                <td><span class="badge bg-success"><?php echo ucfirst($product['status']); ?></span></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="mt-3">
                                <a href="products.php" class="btn btn-primary">Browse More Products</a>
                                <a href="reports.php" class="btn btn-info">View Detailed Reports</a>
                            </div>
                        <?php else: ?>
                            <div class="text-center">
                                <p>You don't have any active investments yet.</p>
                                <a href="products.php" class="btn btn-primary btn-lg">Start Investing Now</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="row mt-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h4>Quick Actions</h4>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-2 mb-3">
                                <a href="products.php" class="btn btn-primary quick-action-btn w-100">
                                    <i class="bi bi-box-seam me-2"></i>Invest
                                </a>
                            </div>
                            <div class="col-md-2 mb-3">
                                <a href="recharge.php" class="btn btn-success quick-action-btn w-100">
                                    <i class="bi bi-arrow-up-circle me-2"></i>Recharge
                                </a>
                            </div>
                            <div class="col-md-2 mb-3">
                                <a href="withdraw.php" class="btn btn-warning quick-action-btn w-100">
                                    <i class="bi bi-arrow-down-circle me-2"></i>Withdraw
                                </a>
                            </div>
                            <div class="col-md-2 mb-3">
                                <a href="referrals.php" class="btn btn-info quick-action-btn w-100">
                                    <i class="bi bi-share me-2"></i>Referrals
                                </a>
                            </div>
                            <div class="col-md-2 mb-3">
                                <a href="reports.php" class="btn btn-secondary quick-action-btn w-100">
                                    <i class="bi bi-file-earmark-bar-graph me-2"></i>Reports
                                </a>
                            </div>
                            <div class="col-md-2 mb-3">
                                <a href="profile.php" class="btn btn-dark quick-action-btn w-100">
                                    <i class="bi bi-person me-2"></i>Profile
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Recent Activity -->
        <div class="row mt-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h4>Recent Transactions</h4>
                    </div>
                    <div class="card-body">
                        <?php if (count($transactions) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Type</th>
                                            <th>Amount</th>
                                            <th>Date</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($transactions as $transaction): ?>
                                            <tr>
                                                <td><?php echo ucfirst(str_replace('_', ' ', $transaction['transaction_type'])); ?></td>
                                                <td>RWF <?php echo number_format($transaction['amount'], 2); ?></td>
                                                <td><?php echo date('M d, Y H:i', strtotime($transaction['transaction_date'])); ?></td>
                                                <td><span class="badge bg-<?php echo $transaction['status'] == 'approved' ? 'success' : ($transaction['status'] == 'pending' ? 'warning' : 'danger'); ?>">
                                                    <?php echo ucfirst($transaction['status']); ?>
                                                </span></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="mt-3">
                                <a href="transactions.php" class="btn btn-primary">View All Transactions</a>
                            </div>
                        <?php else: ?>
                            <p>No transactions yet.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h4>Investment Summary</h4>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-3">
                            <div>
                                <p class="mb-1">Total Investment</p>
                                <h4>RWF <?php echo number_format($total_investment_amount, 2); ?></h4>
                            </div>
                            <div>
                                <p class="mb-1">Daily Earnings</p>
                                <h4>RWF <?php echo number_format($total_daily_earnings, 2); ?></h4>
                            </div>
                        </div>
                        <div class="mb-3">
                            <p class="mb-1">Investment Performance</p>
                            <div class="progress investment-progress">
                                <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $total_investment_amount > 0 ? min(100, ($total_daily_earnings * 30 / $total_investment_amount) * 100) : 0; ?>%" aria-valuenow="<?php echo $total_investment_amount > 0 ? ($total_daily_earnings * 30 / $total_investment_amount) * 100 : 0; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                            <small class="text-muted">Monthly ROI: <?php echo $total_investment_amount > 0 ? number_format(($total_daily_earnings * 30 / $total_investment_amount) * 100, 2) : 0; ?>%</small>
                        </div>
                        <div class="mt-4">
                            <p><i class="bi bi-info-circle me-2"></i> Your investments are performing <?php echo $total_investment_amount > 0 && ($total_daily_earnings * 30 / $total_investment_amount) > 0.05 ? 'above' : 'below'; ?> average market rates.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <footer class="footer mt-5 py-4 bg-light">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <p>&copy; 2025 Novatech Investment Platform. All rights reserved.</p>
                </div>
                <div class="col-md-6 text-end">
                    <h5>Connect With Us</h5>
                    <div id="clientSocialLinks">
                        <!-- Social links will be loaded here -->
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <!-- Social Links Popup Modal -->
    <div class="modal fade" id="socialLinksModal" tabindex="-1" aria-labelledby="socialLinksModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="socialLinksModalLabel">Join Our Community</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <p>Stay connected with us and join our community for updates and support:</p>
                    <div class="d-flex flex-wrap justify-content-center">
                        <?php if (!empty($links['whatsapp'])): ?>
                            <a href="<?php echo htmlspecialchars($links['whatsapp']); ?>" target="_blank" class="btn btn-success me-2 mb-2">
                                <i class="bi bi-whatsapp"></i> WhatsApp
                            </a>
                        <?php endif; ?>
                        
                        <?php if (!empty($links['telegram'])): ?>
                            <a href="<?php echo htmlspecialchars($links['telegram']); ?>" target="_blank" class="btn btn-info me-2 mb-2">
                                <i class="bi bi-telegram"></i> Telegram
                            </a>
                        <?php endif; ?>
                        
                        <?php if (!empty($links['facebook'])): ?>
                            <a href="<?php echo htmlspecialchars($links['facebook']); ?>" target="_blank" class="btn btn-primary me-2 mb-2">
                                <i class="bi bi-facebook"></i> Facebook
                            </a>
                        <?php endif; ?>
                        
                        <?php if (!empty($links['twitter'])): ?>
                            <a href="<?php echo htmlspecialchars($links['twitter']); ?>" target="_blank" class="btn btn-dark me-2 mb-2">
                                <i class="bi bi-twitter"></i> Twitter
                            </a>
                        <?php endif; ?>
                    </div>
                    <p class="mt-3"><small>Click on any link above to join our community platforms.</small></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <?php if ($hasSocialLinks): ?>
    <script>
    // Show social links modal when page loads
    document.addEventListener('DOMContentLoaded', function() {
        var socialLinksModal = new bootstrap.Modal(document.getElementById('socialLinksModal'));
        socialLinksModal.show();
    });
    </script>
    <?php endif; ?>
    
    <script>
    // Fetch and display social links in client footer
    document.addEventListener('DOMContentLoaded', function() {
        fetch('../api/social_links.php')
            .then(response => response.json())
            .then(data => {
                if (data.success && data.links) {
                    const links = data.links;
                    let html = '';
                    
                    if (links.whatsapp) {
                        html += '<a href="' + links.whatsapp + '" target="_blank" class="btn btn-success btn-sm me-1">';
                        html += '<i class="bi bi-whatsapp"></i></a>';
                    }
                    
                    if (links.telegram) {
                        html += '<a href="' + links.telegram + '" target="_blank" class="btn btn-info btn-sm me-1">';
                        html += '<i class="bi bi-telegram"></i></a>';
                    }
                    
                    if (links.facebook) {
                        html += '<a href="' + links.facebook + '" target="_blank" class="btn btn-primary btn-sm me-1">';
                        html += '<i class="bi bi-facebook"></i></a>';
                    }
                    
                    if (links.twitter) {
                        html += '<a href="' + links.twitter + '" target="_blank" class="btn btn-dark btn-sm">';
                        html += '<i class="bi bi-twitter"></i></a>';
                    }
                    
                    if (html) {
                        document.getElementById('clientSocialLinks').innerHTML = html;
                    }
                }
            })
            .catch(error => {
                console.error('Error fetching social links:', error);
            });
    });
    </script>
</body>
</html>
