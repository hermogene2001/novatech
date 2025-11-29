<?php
session_start();
require_once 'config/database.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $phone_number = $_POST['phone_number'];
    $password = $_POST['password'];
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE phone_number = ?");
        $stmt->execute([$phone_number]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($password, $user['password'])) {
            // Check if 2FA is enabled
            if ($user['two_factor_enabled'] && $user['two_factor_secret']) {
                // Store user ID in session for 2FA verification
                $_SESSION['2fa_user_id'] = $user['id'];
                header('Location: login_2fa.php');
                exit();
            } else {
                // No 2FA, complete login
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['first_name'] = $user['first_name'];
                
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
            }
        } else {
            $message = "Invalid phone number or password";
        }
    } catch(PDOException $e) {
        $message = "Login error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Novatech Investment Platform</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="text-center">Login to Novatech</h3>
                    </div>
                    <div class="card-body">
                        <?php if ($message): ?>
                            <div class="alert alert-danger"><?php echo $message; ?></div>
                        <?php endif; ?>
                        
                        <form method="POST">
                            <div class="mb-3">
                                <label for="phone_number" class="form-label">Phone Number</label>
                                <input type="text" class="form-control" id="phone_number" name="phone_number" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">Login</button>
                            </div>
                        </form>
                        
                        <div class="text-center mt-3">
                            <p>Don't have an account? <a href="register.php">Register here</a></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>