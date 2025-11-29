<?php
session_start();
require_once 'config/database.php';
require_once 'lib/TwoFactorAuth.php';

// Check if user has passed initial authentication
if (!isset($_SESSION['2fa_user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['2fa_user_id'];
$message = '';

// Fetch user data
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $message = "Error fetching user data: " . $e->getMessage();
}

// Handle 2FA code submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $code = $_POST['code'];
    
    // Verify the code
    if (TwoFactorAuth::verifyCode($user['two_factor_secret'], $code)) {
        // 2FA successful, complete login
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['first_name'] = $user['first_name'];
        
        // Clear 2FA session data
        unset($_SESSION['2fa_user_id']);
        
        // Redirect based on role
        switch ($user['role']) {
            case 'admin':
                header('Location: admin/dashboard.php');
                break;
            case 'agent':
                header('Location: agent/dashboard.php');
                break;
            default:
                header('Location: client/dashboard.php');
        }
        exit();
    } else {
        $message = "Invalid authentication code. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>2FA Verification - Novatech Investment Platform</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="text-center">Two-Factor Authentication</h3>
                    </div>
                    <div class="card-body">
                        <?php if ($message): ?>
                            <div class="alert alert-danger"><?php echo $message; ?></div>
                        <?php endif; ?>
                        
                        <p class="text-center">Enter the 6-digit code from your authenticator app.</p>
                        
                        <form method="POST">
                            <div class="mb-3">
                                <label for="code" class="form-label">Authentication Code</label>
                                <input type="text" class="form-control" id="code" name="code" 
                                       placeholder="123456" maxlength="6" required>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">Verify</button>
                            </div>
                        </form>
                        
                        <div class="text-center mt-3">
                            <p>Having trouble? <a href="login.php">Use a different account</a></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
</body>
</html>