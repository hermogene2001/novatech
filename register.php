<?php
require_once 'config/database.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $phone_number = $_POST['phone_number'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $invitation_code = $_POST['invitation_code'];
    
    // Generate a unique referral code
    $referral_code = substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 8);
    
    try {
        $stmt = $pdo->prepare("INSERT INTO users (first_name, last_name, phone_number, email, password, invitation_code, referral_code) 
                              VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$first_name, $last_name, $phone_number, $email, $password, $invitation_code, $referral_code]);
        
        // Create client finances record
        $user_id = $pdo->lastInsertId();
        $stmt = $pdo->prepare("INSERT INTO clients_finances (client_id) VALUES (?)");
        $stmt->execute([$user_id]);
        
        // Handle referral if invitation code is provided
        if (!empty($invitation_code)) {
            // Find the referrer
            $stmt = $pdo->prepare("SELECT id FROM users WHERE referral_code = ?");
            $stmt->execute([$invitation_code]);
            $referrer = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($referrer) {
                // Create referral record
                $stmt = $pdo->prepare("INSERT INTO referrals (client_id, referred_id, referral_code, referral_date) 
                                      VALUES (?, ?, ?, NOW())");
                $stmt->execute([$referrer['id'], $user_id, $invitation_code]);
            }
        }
        
        $message = "Registration successful! Your referral code is: " . $referral_code;
    } catch(PDOException $e) {
        $message = "Registration failed: " . $e->getMessage();
    }
} else if (isset($_GET['ref'])) {
    // Pre-fill invitation code from URL parameter
    $invitation_code = $_GET['ref'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Novatech Investment Platform</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="text-center">Register for Novatech</h3>
                    </div>
                    <div class="card-body">
                        <?php if ($message): ?>
                            <div class="alert alert-info"><?php echo $message; ?></div>
                        <?php endif; ?>
                        
                        <form method="POST">
                            <div class="mb-3">
                                <label for="first_name" class="form-label">First Name</label>
                                <input type="text" class="form-control" id="first_name" name="first_name" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="last_name" class="form-label">Last Name</label>
                                <input type="text" class="form-control" id="last_name" name="last_name" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="phone_number" class="form-label">Phone Number</label>
                                <input type="text" class="form-control" id="phone_number" name="phone_number" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="invitation_code" class="form-label">Invitation Code (Optional)</label>
                                <input type="text" class="form-control" id="invitation_code" name="invitation_code">
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">Register</button>
                            </div>
                        </form>
                        
                        <div class="text-center mt-3">
                            <p>Already have an account? <a href="login.php">Login here</a></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>