<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header('Location: ../login.php');
    exit();
}

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('log_errors', 1);
error_reporting(E_ALL);

// Ensure uploads directory exists
$uploadDir = '../uploads/products/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$admin_name = $_SESSION['first_name'];
$message = '';

// Fetch all products
try {
    $stmt = $pdo->prepare("SELECT * FROM products ORDER BY id DESC");
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error = "Error fetching products: " . $e->getMessage();
    // Log the error for debugging
    error_log("Fetching products error: " . $e->getMessage());
}

// Handle product creation/update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Debug: Log all POST data
    error_log('POST data received: ' . print_r($_POST, true));
    $name = $_POST['name'];
    $price = $_POST['price'];
    $cycle = $_POST['cycle'];
    $profit_rate = $_POST['profit_rate'];
    $status = $_POST['status'];
    
    // Calculate daily earning based on price and profit rate
    // Formula: daily_earning = (price * profit_rate) / 100
    $daily_earning = ($price * $profit_rate) / 100;
    
    if (isset($_POST['product_id']) && !empty($_POST['product_id'])) {
        // Update existing product
        $product_id = $_POST['product_id'];
        
        // Handle image upload for existing product
        $imageUpdated = false;
        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $uploadDir = '../uploads/products/';
            // Create directory if it doesn't exist
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $fileName = $_FILES['image']['name'];
            $fileTmpName = $_FILES['image']['tmp_name'];
            $fileSize = $_FILES['image']['size'];
            $fileType = $_FILES['image']['type'];
            
            // Validate file type
            $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png'];
            if (in_array($fileType, $allowedTypes) && $fileSize <= 2097152) { // 2MB max
                $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);
                $newFileName = uniqid() . '_' . time() . '.' . $fileExtension;
                $uploadPath = $uploadDir . $newFileName;
                
                if (move_uploaded_file($fileTmpName, $uploadPath)) {
                    // Delete old image if it's not the default image
                    try {
                        $stmt = $pdo->prepare("SELECT image FROM products WHERE id = ?");
                        $stmt->execute([$product_id]);
                        $oldImage = $stmt->fetchColumn();
                        
                        if ($oldImage && $oldImage !== 'default.jpg' && file_exists($uploadDir . $oldImage)) {
                            unlink($uploadDir . $oldImage);
                        }
                    } catch (Exception $e) {
                        // Log error but don't stop the update process
                        error_log("Error deleting old image: " . $e->getMessage());
                    }
                    
                    $image = $newFileName;
                    $imageUpdated = true;
                } else {
                    $message = "Error uploading image.";
                }
            } else {
                $message = "Invalid file type or size. Please upload a valid image (JPG, JPEG, PNG) under 2MB.";
            }
        }
        
        try {
            if ($imageUpdated) {
                $stmt = $pdo->prepare("UPDATE products SET name = ?, price = ?, daily_earning = ?, cycle = ?, profit_rate = ?, status = ?, image = ? WHERE id = ?");
                $stmt->execute([$name, $price, $daily_earning, $cycle, $profit_rate, $status, $image, $product_id]);
            } else {
                $stmt = $pdo->prepare("UPDATE products SET name = ?, price = ?, daily_earning = ?, cycle = ?, profit_rate = ?, status = ? WHERE id = ?");
                $stmt->execute([$name, $price, $daily_earning, $cycle, $profit_rate, $status, $product_id]);
            }
            $message = "Product updated successfully.";
        } catch(PDOException $e) {
            $message = "Error updating product: " . $e->getMessage();
            // Log the error for debugging
            error_log("Product update error: " . $e->getMessage());
        }
    } else {
        // Handle image upload for new product
        $image = 'default.jpg'; // Default image
        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $uploadDir = '../uploads/products/';
            // Create directory if it doesn't exist
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $fileName = $_FILES['image']['name'];
            $fileTmpName = $_FILES['image']['tmp_name'];
            $fileSize = $_FILES['image']['size'];
            $fileType = $_FILES['image']['type'];
            
            // Validate file type
            $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png'];
            if (in_array($fileType, $allowedTypes) && $fileSize <= 2097152) { // 2MB max
                $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);
                $newFileName = uniqid() . '_' . time() . '.' . $fileExtension;
                $uploadPath = $uploadDir . $newFileName;
                
                if (move_uploaded_file($fileTmpName, $uploadPath)) {
                    $image = $newFileName;
                } else {
                    $message = "Error uploading image.";
                }
            } else {
                $message = "Invalid file type or size. Please upload a valid image (JPG, JPEG, PNG) under 2MB.";
            }
        }
        
        try {
            // Fixed the INSERT statement to include all required fields with proper default values
            $stmt = $pdo->prepare("INSERT INTO products (name, image, price, daily_earning, cycle, profit_rate, status, min_withdraw, referral_level1_percentage, referral_level2_percentage) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $image, $price, $daily_earning, $cycle, $profit_rate, $status, 3000.00, 3.00, 1.00]);
            $message = "Product created successfully.";
        } catch(PDOException $e) {
            $message = "Error creating product: " . $e->getMessage();
            // Log the error for debugging
            error_log("Product creation error: " . $e->getMessage());
        }
    }
    
    // Refresh products list
    try {
        $stmt = $pdo->prepare("SELECT * FROM products ORDER BY id DESC");
        $stmt->execute();
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        $error = "Error fetching products: " . $e->getMessage();
        // Log the error for debugging
        error_log("Fetching products error: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Management - Novatech Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        .table th, .table td {
            vertical-align: middle;
        }
        .img-thumbnail {
            max-height: 50px;
            width: auto;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="#">Novatech Admin</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="users.php">Users</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="investments.php">Investments</a>
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
                        <a class="nav-link" href="finances.php">Finances</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="referrals.php">Referrals</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <?php echo htmlspecialchars($admin_name); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="settings.php">Settings</a></li>
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
                <h2>Product Management</h2>
                <?php if ($message): ?>
                    <div class="alert alert-info"><?php echo $message; ?></div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="row mt-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h4>All Products</h4>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Image</th>
                                        <th>Name</th>
                                        <th>Price</th>
                                        <th>Daily Earning</th>
                                        <th>Cycle</th>
                                        <th>Profit Rate</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($products as $product): ?>
                                        <tr>
                                            <td><?php echo $product['id']; ?></td>
                                            <td>
                                                <?php if (!empty($product['image']) && $product['image'] !== 'default.jpg'): ?>
                                                    <img src="../uploads/products/<?php echo htmlspecialchars($product['image']); ?>" alt="Product Image" class="img-thumbnail" style="max-height: 50px;">
                                                <?php else: ?>
                                                    <span class="text-muted">No image</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($product['name']); ?></td>
                                            <td>$<?php echo number_format($product['price'], 2); ?></td>
                                            <td>$<?php echo number_format($product['daily_earning'], 2); ?></td>
                                            <td><?php echo $product['cycle']; ?> days</td>
                                            <td><?php echo $product['profit_rate']; ?>%</td>
                                            <td>
                                                <span class="badge bg-<?php echo $product['status'] == 'active' ? 'success' : 'danger'; ?>">
                                                    <?php echo ucfirst($product['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-primary edit-product-btn" data-bs-toggle="modal" data-bs-target="#editModal<?php echo $product['id']; ?>" data-product-id="<?php echo $product['id']; ?>">Edit</button>
                                            </td>
                                        </tr>
                                        
                                        <!-- Edit Modal -->
                                        <div class="modal fade" id="editModal<?php echo $product['id']; ?>" tabindex="-1">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Edit Product</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <form method="POST" id="editProductForm<?php echo $product['id']; ?>" enctype="multipart/form-data">
                                                        <div class="modal-body">
                                                            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                                            <div class="mb-3">
                                                                <label for="edit_name_<?php echo $product['id']; ?>" class="form-label">Name</label>
                                                                <input type="text" class="form-control" id="edit_name_<?php echo $product['id']; ?>" name="name" value="<?php echo htmlspecialchars($product['name']); ?>" required>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label for="edit_price_<?php echo $product['id']; ?>" class="form-label">Price ($)</label>
                                                                <input type="number" class="form-control price-input" id="edit_price_<?php echo $product['id']; ?>" name="price" value="<?php echo $product['price']; ?>" step="0.01" required>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label for="edit_profit_rate_<?php echo $product['id']; ?>" class="form-label">Profit Rate (%)</label>
                                                                <input type="number" class="form-control profit-rate-input" id="edit_profit_rate_<?php echo $product['id']; ?>" name="profit_rate" value="<?php echo $product['profit_rate']; ?>" step="0.01" required>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label for="edit_daily_earning_<?php echo $product['id']; ?>" class="form-label">Daily Earning ($)</label>
                                                                <input type="number" class="form-control daily-earning-output" id="edit_daily_earning_<?php echo $product['id']; ?>" name="daily_earning" value="<?php echo $product['daily_earning']; ?>" step="0.01" readonly>
                                                                <small class="form-text text-muted">Calculated automatically: (Price × Profit Rate) / 100</small>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label for="edit_cycle_<?php echo $product['id']; ?>" class="form-label">Cycle (days)</label>
                                                                <input type="number" class="form-control" id="edit_cycle_<?php echo $product['id']; ?>" name="cycle" value="<?php echo $product['cycle']; ?>" required>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label for="edit_image_<?php echo $product['id']; ?>" class="form-label">Product Image</label>
                                                                <input type="file" class="form-control" id="edit_image_<?php echo $product['id']; ?>" name="image" accept="image/*">
                                                                <?php if (!empty($product['image']) && $product['image'] !== 'default.jpg'): ?>
                                                                    <div class="mt-2">
                                                                        <small class="form-text text-muted">Current image:</small>
                                                                        <img src="../uploads/products/<?php echo htmlspecialchars($product['image']); ?>" alt="Product Image" class="img-thumbnail" style="max-height: 100px;">
                                                                    </div>
                                                                <?php endif; ?>
                                                                <small class="form-text text-muted">Leave empty to keep current image. Allowed formats: JPG, JPEG, PNG. Max size: 2MB</small>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label for="edit_status_<?php echo $product['id']; ?>" class="form-label">Status</label>
                                                                <select class="form-select" id="edit_status_<?php echo $product['id']; ?>" name="status">
                                                                    <option value="active" <?php echo $product['status'] == 'active' ? 'selected' : ''; ?>>Active</option>
                                                                    <option value="inactive" <?php echo $product['status'] == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                                                </select>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                            <button type="submit" class="btn btn-primary">Save Changes</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Add New Product Button -->
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal" id="addProductBtn">Add New Product</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Add Product Modal -->
    <div class="modal fade" id="addModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Product</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="addProductForm" enctype="multipart/form-data">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="add_name" class="form-label">Name</label>
                            <input type="text" class="form-control" id="add_name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="add_price" class="form-label">Price ($)</label>
                            <input type="number" class="form-control price-input" id="add_price" name="price" step="0.01" required>
                        </div>
                        <div class="mb-3">
                            <label for="add_profit_rate" class="form-label">Profit Rate (%)</label>
                            <input type="number" class="form-control profit-rate-input" id="add_profit_rate" name="profit_rate" step="0.01" required>
                        </div>
                        <div class="mb-3">
                            <label for="add_daily_earning" class="form-label">Daily Earning ($)</label>
                            <input type="number" class="form-control daily-earning-output" id="add_daily_earning" name="daily_earning" step="0.01" readonly>
                            <small class="form-text text-muted">Calculated automatically: (Price × Profit Rate) / 100</small>
                        </div>
                        <div class="mb-3">
                            <label for="add_cycle" class="form-label">Cycle (days)</label>
                            <input type="number" class="form-control" id="add_cycle" name="cycle" required>
                        </div>
                        <div class="mb-3">
                            <label for="add_image" class="form-label">Product Image</label>
                            <input type="file" class="form-control" id="add_image" name="image" accept="image/*">
                            <small class="form-text text-muted">Allowed formats: JPG, JPEG, PNG. Max size: 2MB</small>
                        </div>
                        <div class="mb-3">
                            <label for="add_status" class="form-label">Status</label>
                            <select class="form-select" id="add_status" name="status">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Add Product</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/main.js"></script>
    <script>
        // Ensure forms submit correctly and handle automatic calculation
        document.addEventListener('DOMContentLoaded', function() {
            // Function to calculate daily earning
            function calculateDailyEarning(price, profitRate) {
                const priceValue = parseFloat(price);
                const profitRateValue = parseFloat(profitRate);
                
                // Validate inputs
                if (isNaN(priceValue) || isNaN(profitRateValue) || priceValue <= 0 || profitRateValue <= 0) {
                    return 0;
                }
                
                return (priceValue * profitRateValue) / 100;
            }
            
            // Handle add product form
            const addForm = document.getElementById('addProductForm');
            if (addForm) {
                const addPriceInput = document.getElementById('add_price');
                const addProfitRateInput = document.getElementById('add_profit_rate');
                const addDailyEarningOutput = document.getElementById('add_daily_earning');
                
                function updateAddDailyEarning() {
                    const price = addPriceInput.value;
                    const profitRate = addProfitRateInput.value;
                    const dailyEarning = calculateDailyEarning(price, profitRate);
                    addDailyEarningOutput.value = dailyEarning.toFixed(2);
                }
                
                addPriceInput.addEventListener('input', updateAddDailyEarning);
                addProfitRateInput.addEventListener('input', updateAddDailyEarning);
                
                addForm.addEventListener('submit', function(e) {
                    console.log('Add product form submitted');
                    // Ensure daily earning is calculated before submission
                    updateAddDailyEarning();
                });
            }
            
            // Handle edit product forms
            const editForms = document.querySelectorAll('form[method="POST"]:not(#addProductForm)');
            editForms.forEach(function(form) {
                const priceInput = form.querySelector('.price-input');
                const profitRateInput = form.querySelector('.profit-rate-input');
                const dailyEarningOutput = form.querySelector('.daily-earning-output');
                
                if (priceInput && profitRateInput && dailyEarningOutput) {
                    function updateDailyEarning() {
                        const price = priceInput.value;
                        const profitRate = profitRateInput.value;
                        const dailyEarning = calculateDailyEarning(price, profitRate);
                        dailyEarningOutput.value = dailyEarning.toFixed(2);
                    }
                    
                    priceInput.addEventListener('input', updateDailyEarning);
                    profitRateInput.addEventListener('input', updateDailyEarning);
                }
                
                form.addEventListener('submit', function(e) {
                    console.log('Edit product form submitted');
                    // Ensure daily earning is calculated before submission
                    if (priceInput && profitRateInput && dailyEarningOutput) {
                        const price = priceInput.value;
                        const profitRate = profitRateInput.value;
                        const dailyEarning = calculateDailyEarning(price, profitRate);
                        dailyEarningOutput.value = dailyEarning.toFixed(2);
                    }
                });
            });
            
            // Handle modal shown event to initialize calculations
            const editButtons = document.querySelectorAll('.edit-product-btn');
            editButtons.forEach(function(button) {
                button.addEventListener('click', function() {
                    const productId = button.getAttribute('data-product-id');
                    const modal = document.getElementById('editModal' + productId);
                    if (modal) {
                        // Small delay to ensure modal is fully rendered
                        setTimeout(function() {
                            const form = modal.querySelector('form');
                            if (form) {
                                const priceInput = form.querySelector('.price-input');
                                const profitRateInput = form.querySelector('.profit-rate-input');
                                const dailyEarningOutput = form.querySelector('.daily-earning-output');
                                
                                if (priceInput && profitRateInput && dailyEarningOutput) {
                                    const price = priceInput.value;
                                    const profitRate = profitRateInput.value;
                                    const dailyEarning = calculateDailyEarning(price, profitRate);
                                    dailyEarningOutput.value = dailyEarning.toFixed(2);
                                }
                            }
                        }, 100);
                    }
                });
            });
            
            // Handle file input changes to show file name
            const fileInputs = document.querySelectorAll('input[type="file"]');
            fileInputs.forEach(function(input) {
                input.addEventListener('change', function() {
                    const fileName = this.files[0] ? this.files[0].name : '';
                    if (fileName) {
                        console.log('Selected file:', fileName);
                    }
                });
            });
        });
    </script>
</body>
</html>