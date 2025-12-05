<?php
session_start();
require_once '../config/database.php';
require_once '../lib/TwoFactorAuth.php';

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
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error = "Error fetching user data: " . $e->getMessage();
}

// Handle 2FA setup
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['enable_2fa'])) {
    try {
        // Generate a new secret
        $secret = TwoFactorAuth::generateSecret();
        
        // Update user with the secret
        $stmt = $pdo->prepare("UPDATE users SET two_factor_secret = ?, two_factor_enabled = 1 WHERE id = ?");
        $stmt->execute([$secret, $user_id]);
        
        // Update user data in memory
        $user['two_factor_secret'] = $secret;
        $user['two_factor_enabled'] = 1;
        
        $message = "Two-factor authentication has been enabled. Please scan the QR code with your authenticator app.";
    } catch(PDOException $e) {
        $error = "Error enabling 2FA: " . $e->getMessage();
    }
}

// Handle 2FA disable
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['disable_2fa'])) {
    try {
        // Remove the secret and disable 2FA
        $stmt = $pdo->prepare("UPDATE users SET two_factor_secret = NULL, two_factor_enabled = 0 WHERE id = ?");
        $stmt->execute([$user_id]);
        
        // Update user data in memory
        $user['two_factor_secret'] = null;
        $user['two_factor_enabled'] = 0;
        
        $message = "Two-factor authentication has been disabled.";
    } catch(PDOException $e) {
        $error = "Error disabling 2FA: " . $e->getMessage();
    }
}

// Generate QR code URL if 2FA is enabled
$qrCodeUrl = '';
if ($user['two_factor_enabled'] && $user['two_factor_secret']) {
    $qrCodeUrl = TwoFactorAuth::getQRCodeUrl($user['email'] ?: $user['phone_number'], $user['two_factor_secret']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Two-Factor Authentication - Novatech</title>
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
                            <li><a class="dropdown-item active" href="setup_2fa.php">2FA Setup</a></li>
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
                <h2>Two-Factor Authentication</h2>
                <?php if ($message): ?>
                    <div class="alert alert-info"><?php echo $message; ?></div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="row mt-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h4>Security Settings</h4>
                    </div>
                    <div class="card-body">
                        <?php if ($user['two_factor_enabled']): ?>
                            <div class="alert alert-success">
                                <h5><i class="bi bi-shield-check"></i> Two-factor authentication is enabled</h5>
                                <p>You'll need to enter a code from your authenticator app when logging in.</p>
                            </div>
                            
                            <?php if ($qrCodeUrl): ?>
                                <div class="text-center mb-4">
                                    <p>Scan this QR code with your authenticator app:</p>
                                    <img src="<?php echo $qrCodeUrl; ?>" alt="QR Code" class="img-fluid">
                                    <p class="mt-2">Secret: <strong><?php echo $user['two_factor_secret']; ?></strong></p>
                                </div>
                            <?php endif; ?>
                            
                            <form method="POST">
                                <button type="submit" name="disable_2fa" class="btn btn-danger" 
                                        onclick="return confirm('Are you sure you want to disable two-factor authentication?')">Disable 2FA</button>
                            </form>
                        <?php else: ?>
                            <div class="alert alert-warning">
                                <h5><i class="bi bi-shield"></i> Two-factor authentication is not enabled</h5>
                                <p>Enable 2FA to add an extra layer of security to your account.</p>
                            </div>
                            
                            <form method="POST">
                                <button type="submit" name="enable_2fa" class="btn btn-primary">Enable Two-Factor Authentication</button>
                            </form>
                            
                            <div class="mt-4">
                                <h5>How to set up 2FA:</h5>
                                <ol>
                                    <li>Click "Enable Two-Factor Authentication"</li>
                                    <li>Scan the QR code with an authenticator app (Google Authenticator, Authy, etc.)</li>
                                    <li>Enter the code from your app when logging in</li>
                                </ol>
                            </div>
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