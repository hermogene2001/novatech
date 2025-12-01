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

// Fetch available products
try {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE status = 'active'");
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $message = "Error fetching products: " . $e->getMessage();
}

// Handle product purchase
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['product_id'])) {
    $product_id = $_POST['product_id'];
    $amount = $_POST['amount'];
    
    try {
        // Get product details
        $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? AND status = 'active'");
        $stmt->execute([$product_id]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$product) {
            $message = "Invalid product selected.";
        } else if ($amount < $product['price']) {
            $message = "Amount must be at least RWF " . number_format($product['price'], 2);
        } else {
            // Check user balance
            $stmt = $pdo->prepare("SELECT balance FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user['balance'] < $amount) {
                $message = "Insufficient balance.";
            } else {
                // Deduct amount from user balance
                $new_balance = $user['balance'] - $amount;
                $stmt = $pdo->prepare("UPDATE users SET balance = ? WHERE id = ?");
                $stmt->execute([$new_balance, $user_id]);
                
                // Calculate end date based on product cycle
                $cycle_days = $product['cycle'];
                $end_datetime = date('Y-m-d H:i:s', strtotime("+$cycle_days days"));
                
                // Record the purchase with end date in purchases table (for UI)
                $stmt = $pdo->prepare("INSERT INTO purchases (client_id, product_id, purchase_date, end_datetime, status) VALUES (?, ?, NOW(), ?, 'active')");
                $stmt->execute([$user_id, $product_id, $end_datetime]);
                
                // Also record in investments table (for reports/cron jobs)
                $stmt = $pdo->prepare("INSERT INTO investments (user_id, product_id, amount, invested_at, status, daily_profit) VALUES (?, ?, ?, NOW(), 'active', ?)");
                $stmt->execute([$user_id, $product_id, $amount, $product['daily_earning']]);
                
                // Record transaction
                $stmt = $pdo->prepare("INSERT INTO transactions (user_id, transaction_type, amount, status) VALUES (?, 'purchase', ?, 'approved')");
                $stmt->execute([$user_id, $amount]);
                
                $message = "Product purchased successfully! Investment will end on " . date('M d, Y', strtotime($end_datetime));
            }
        }
    } catch(PDOException $e) {
        $message = "Purchase error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Investment Products - Novatech Investment Platform</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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
                        <a class="nav-link active" href="#">Products</a>
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
                <h2>Investment Products</h2>
                <?php if ($message): ?>
                    <div class="alert alert-info"><?php echo $message; ?></div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="row mt-4">
            <?php foreach ($products as $product): ?>
                <div class="col-md-4 mb-4">
                    <div class="card h-100">
                        <img src="../uploads/products/<?php echo htmlspecialchars($product['image']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($product['name']); ?>" style="height: 200px; object-fit: cover;">
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h5>
                            <p class="card-text flex-grow-1">
                                Price: RWF <?php echo number_format($product['price'], 2); ?><br>
                                Daily Earning: RWF <?php echo number_format($product['daily_earning'], 2); ?><br>
                                Cycle: <?php echo $product['cycle']; ?> days<br>
                                Profit Rate: <?php echo $product['profit_rate']; ?>%
                            </p>
                            <button type="button" class="btn btn-primary mt-auto" data-bs-toggle="modal" data-bs-target="#purchaseModal<?php echo $product['id']; ?>">
                                Purchase
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Purchase Modal -->
                <div class="modal fade" id="purchaseModal<?php echo $product['id']; ?>" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Purchase <?php echo htmlspecialchars($product['name']); ?></h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <form method="POST">
                                <div class="modal-body">
                                    <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                    <div class="mb-3">
                                        <label for="amount" class="form-label">Amount (min $<?php echo number_format($product['price'], 2); ?>)</label>
                                        <input type="number" class="form-control" id="amount" name="amount" min="<?php echo $product['price']; ?>" step="0.01" required>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                    <button type="submit" class="btn btn-primary">Confirm Purchase</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>