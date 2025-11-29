<?php
/**
 * REST API for Novatech Investment Platform
 * Provides endpoints for mobile app integration
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once '../config/database.php';

// Get the request method and endpoint
$method = $_SERVER['REQUEST_METHOD'];
$request_uri = $_SERVER['REQUEST_URI'];
$endpoint = parse_url($request_uri, PHP_URL_PATH);

// Remove /api/ from the beginning of the endpoint
$endpoint = str_replace('/nova/api', '', $endpoint);
$endpoint = trim($endpoint, '/');

// Route handling
switch ($endpoint) {
    case 'login':
        if ($method === 'POST') {
            handleLogin();
        } else {
            sendResponse(405, ['error' => 'Method not allowed']);
        }
        break;
        
    case 'register':
        if ($method === 'POST') {
            handleRegister();
        } else {
            sendResponse(405, ['error' => 'Method not allowed']);
        }
        break;
        
    case 'products':
        if ($method === 'GET') {
            handleGetProducts();
        } else {
            sendResponse(405, ['error' => 'Method not allowed']);
        }
        break;
        
    case 'investments':
        if ($method === 'GET') {
            handleGetInvestments();
        } else if ($method === 'POST') {
            handleCreateInvestment();
        } else {
            sendResponse(405, ['error' => 'Method not allowed']);
        }
        break;
        
    case 'transactions':
        if ($method === 'GET') {
            handleGetTransactions();
        } else {
            sendResponse(405, ['error' => 'Method not allowed']);
        }
        break;
        
    case 'balance':
        if ($method === 'GET') {
            handleGetBalance();
        } else {
            sendResponse(405, ['error' => 'Method not allowed']);
        }
        break;
        
    case 'recharge':
        if ($method === 'POST') {
            handleCreateRecharge();
        } else if ($method === 'GET') {
            handleGetRecharges();
        } else {
            sendResponse(405, ['error' => 'Method not allowed']);
        }
        break;
        
    default:
        sendResponse(404, ['error' => 'Endpoint not found']);
        break;
}

// Helper function to send JSON response
function sendResponse($statusCode, $data) {
    http_response_code($statusCode);
    echo json_encode($data);
    exit();
}

// Helper function to get JSON input
function getJsonInput() {
    return json_decode(file_get_contents('php://input'), true);
}

// Login handler
function handleLogin() {
    global $pdo;
    
    $input = getJsonInput();
    
    if (!isset($input['phone_number']) || !isset($input['password'])) {
        sendResponse(400, ['error' => 'Phone number and password are required']);
    }
    
    $phone_number = $input['phone_number'];
    $password = $input['password'];
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE phone_number = ?");
        $stmt->execute([$phone_number]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($password, $user['password'])) {
            // Generate a simple token (in production, use JWT or similar)
            $token = base64_encode($user['id'] . ':' . time());
            
            sendResponse(200, [
                'success' => true,
                'token' => $token,
                'user' => [
                    'id' => $user['id'],
                    'first_name' => $user['first_name'],
                    'last_name' => $user['last_name'],
                    'phone_number' => $user['phone_number'],
                    'email' => $user['email'],
                    'role' => $user['role'],
                    'balance' => floatval($user['balance']),
                    'referral_bonus' => floatval($user['referral_bonus']),
                    'referral_code' => $user['referral_code']
                ]
            ]);
        } else {
            sendResponse(401, ['error' => 'Invalid credentials']);
        }
    } catch (PDOException $e) {
        sendResponse(500, ['error' => 'Database error: ' . $e->getMessage()]);
    }
}

// Register handler
function handleRegister() {
    global $pdo;
    
    $input = getJsonInput();
    
    if (!isset($input['first_name']) || !isset($input['last_name']) || 
        !isset($input['phone_number']) || !isset($input['password']) || 
        !isset($input['email'])) {
        sendResponse(400, ['error' => 'All fields are required']);
    }
    
    $first_name = $input['first_name'];
    $last_name = $input['last_name'];
    $phone_number = $input['phone_number'];
    $email = $input['email'];
    $password = password_hash($input['password'], PASSWORD_DEFAULT);
    $invitation_code = isset($input['invitation_code']) ? $input['invitation_code'] : null;
    
    // Generate a unique referral code
    $referral_code = substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 8);
    
    try {
        // Check if phone number already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE phone_number = ?");
        $stmt->execute([$phone_number]);
        
        if ($stmt->fetch()) {
            sendResponse(409, ['error' => 'Phone number already registered']);
        }
        
        // Insert new user
        $stmt = $pdo->prepare("INSERT INTO users (first_name, last_name, phone_number, email, password, invitation_code, referral_code) 
                              VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$first_name, $last_name, $phone_number, $email, $password, $invitation_code, $referral_code]);
        
        // Create client finances record
        $user_id = $pdo->lastInsertId();
        $stmt = $pdo->prepare("INSERT INTO clients_finances (client_id) VALUES (?)");
        $stmt->execute([$user_id]);
        
        sendResponse(201, [
            'success' => true,
            'message' => 'Registration successful',
            'user_id' => $user_id,
            'referral_code' => $referral_code
        ]);
    } catch (PDOException $e) {
        sendResponse(500, ['error' => 'Database error: ' . $e->getMessage()]);
    }
}

// Get products handler
function handleGetProducts() {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM products WHERE status = 'active' ORDER BY id DESC");
        $stmt->execute();
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        sendResponse(200, [
            'success' => true,
            'products' => $products
        ]);
    } catch (PDOException $e) {
        sendResponse(500, ['error' => 'Database error: ' . $e->getMessage()]);
    }
}

// Get investments handler
function handleGetInvestments() {
    global $pdo;
    
    // In a real implementation, you would authenticate the user with a token
    // For now, we'll require user_id as a parameter
    $user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : null;
    
    if (!$user_id) {
        sendResponse(400, ['error' => 'User ID is required']);
    }
    
    try {
        $stmt = $pdo->prepare("SELECT i.*, p.name as product_name, p.daily_earning 
                              FROM investments i 
                              JOIN products p ON i.product_id = p.id 
                              WHERE i.user_id = ? AND i.status = 'active' 
                              ORDER BY i.invested_at DESC");
        $stmt->execute([$user_id]);
        $investments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        sendResponse(200, [
            'success' => true,
            'investments' => $investments
        ]);
    } catch (PDOException $e) {
        sendResponse(500, ['error' => 'Database error: ' . $e->getMessage()]);
    }
}

// Create investment handler
function handleCreateInvestment() {
    global $pdo;
    
    $input = getJsonInput();
    
    if (!isset($input['user_id']) || !isset($input['product_id']) || !isset($input['amount'])) {
        sendResponse(400, ['error' => 'User ID, product ID, and amount are required']);
    }
    
    $user_id = intval($input['user_id']);
    $product_id = intval($input['product_id']);
    $amount = floatval($input['amount']);
    
    try {
        // Get product details
        $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? AND status = 'active'");
        $stmt->execute([$product_id]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$product) {
            sendResponse(404, ['error' => 'Product not found']);
        }
        
        if ($amount < $product['price']) {
            sendResponse(400, ['error' => 'Amount must be at least $' . $product['price']]);
        }
        
        // Check user balance
        $stmt = $pdo->prepare("SELECT balance FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user || $user['balance'] < $amount) {
            sendResponse(400, ['error' => 'Insufficient balance']);
        }
        
        // Deduct amount from user balance
        $new_balance = $user['balance'] - $amount;
        $stmt = $pdo->prepare("UPDATE users SET balance = ? WHERE id = ?");
        $stmt->execute([$new_balance, $user_id]);
        
        // Record the investment
        $stmt = $pdo->prepare("INSERT INTO investments (user_id, product_id, amount, invested_at, status, daily_profit) 
                              VALUES (?, ?, ?, NOW(), 'active', ?)");
        $stmt->execute([$user_id, $product_id, $amount, $product['daily_earning']]);
        
        // Record transaction
        $stmt = $pdo->prepare("INSERT INTO transactions (user_id, transaction_type, amount, status) 
                              VALUES (?, 'investment', ?, 'approved')");
        $stmt->execute([$user_id, $amount]);
        
        sendResponse(201, [
            'success' => true,
            'message' => 'Investment created successfully',
            'investment_id' => $pdo->lastInsertId()
        ]);
    } catch (PDOException $e) {
        sendResponse(500, ['error' => 'Database error: ' . $e->getMessage()]);
    }
}

// Get transactions handler
function handleGetTransactions() {
    global $pdo;
    
    $user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : null;
    
    if (!$user_id) {
        sendResponse(400, ['error' => 'User ID is required']);
    }
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM transactions WHERE user_id = ? ORDER BY transaction_date DESC LIMIT 20");
        $stmt->execute([$user_id]);
        $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        sendResponse(200, [
            'success' => true,
            'transactions' => $transactions
        ]);
    } catch (PDOException $e) {
        sendResponse(500, ['error' => 'Database error: ' . $e->getMessage()]);
    }
}

// Get balance handler
function handleGetBalance() {
    global $pdo;
    
    $user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : null;
    
    if (!$user_id) {
        sendResponse(400, ['error' => 'User ID is required']);
    }
    
    try {
        $stmt = $pdo->prepare("SELECT balance, referral_bonus FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            sendResponse(404, ['error' => 'User not found']);
        }
        
        sendResponse(200, [
            'success' => true,
            'balance' => floatval($user['balance']),
            'referral_bonus' => floatval($user['referral_bonus'])
        ]);
    } catch (PDOException $e) {
        sendResponse(500, ['error' => 'Database error: ' . $e->getMessage()]);
    }
}

// Create recharge handler
function handleCreateRecharge() {
    global $pdo;
    
    $input = getJsonInput();
    
    if (!isset($input['user_id']) || !isset($input['amount'])) {
        sendResponse(400, ['error' => 'User ID and amount are required']);
    }
    
    $user_id = intval($input['user_id']);
    $amount = floatval($input['amount']);
    
    if ($amount <= 0) {
        sendResponse(400, ['error' => 'Amount must be greater than zero']);
    }
    
    try {
        // Create a recharge request
        $stmt = $pdo->prepare("INSERT INTO recharges (client_id, agent_id, amount, recharge_time, status) 
                              VALUES (?, 0, ?, NOW(), 'pending')");
        $stmt->execute([$user_id, $amount]);
        
        sendResponse(201, [
            'success' => true,
            'message' => 'Recharge request created successfully',
            'recharge_id' => $pdo->lastInsertId()
        ]);
    } catch (PDOException $e) {
        sendResponse(500, ['error' => 'Database error: ' . $e->getMessage()]);
    }
}

// Get recharges handler
function handleGetRecharges() {
    global $pdo;
    
    $user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : null;
    
    if (!$user_id) {
        sendResponse(400, ['error' => 'User ID is required']);
    }
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM recharges WHERE client_id = ? ORDER BY recharge_time DESC");
        $stmt->execute([$user_id]);
        $recharges = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        sendResponse(200, [
            'success' => true,
            'recharges' => $recharges
        ]);
    } catch (PDOException $e) {
        sendResponse(500, ['error' => 'Database error: ' . $e->getMessage()]);
    }
}
?>