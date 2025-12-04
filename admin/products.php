<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header('Location: ../login.php');
    exit();
}

// Regenerate session ID for security
session_regenerate_id(true);

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('log_errors', 1);
error_reporting(E_ALL);

// Constants for default values
define('DEFAULT_MIN_WITHDRAW', 3000.00);
define('DEFAULT_REFERRAL_L1_PCT', 3.00);
define('DEFAULT_REFERRAL_L2_PCT', 1.00);
define('UPLOAD_DIR', '../uploads/products/');
define('MAX_FILE_SIZE', 2097152); // 2MB

// Ensure uploads directory exists
if (!is_dir(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0755, true);
}

$admin_name = $_SESSION['first_name'];

// Generate CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Flash message handling
function setFlashMessage($message, $type = 'info') {
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type'] = $type;
}

function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        $type = $_SESSION['flash_type'] ?? 'info';
        unset($_SESSION['flash_message'], $_SESSION['flash_type']);
        return ['message' => $message, 'type' => $type];
    }
    return null;
}

// Function to fetch all products
function fetchProducts($pdo) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM products ORDER BY id DESC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        error_log("Fetching products error: " . $e->getMessage());
        return [];
    }
}

// Function to handle image upload with proper validation
function handleImageUpload($file) {
    if (!isset($file) || $file['error'] !== 0) {
        return ['success' => false, 'image' => null, 'message' => ''];
    }
    
    // Check file size
    if ($file['size'] > MAX_FILE_SIZE) {
        return ['success' => false, 'message' => 'File size exceeds 2MB limit'];
    }
    
    // Validate MIME type using finfo (server-side validation)
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $realMimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    $allowedTypes = ['image/jpeg', 'image/png'];
    if (!in_array($realMimeType, $allowedTypes)) {
        return ['success' => false, 'message' => 'Invalid file type. Only JPG and PNG allowed'];
    }
    
    // Verify it's actually an image using getimagesize
    $imageInfo = getimagesize($file['tmp_name']);
    if ($imageInfo === false) {
        return ['success' => false, 'message' => 'File is not a valid image'];
    }
    
    // Generate unique filename
    $fileExtension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $newFileName = uniqid() . '_' . time() . '.' . strtolower($fileExtension);
    $uploadPath = UPLOAD_DIR . $newFileName;
    
    // Ensure directory exists
    if (!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0755, true);
    }
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
        return ['success' => true, 'image' => $newFileName];
    }
    
    return ['success' => false, 'message' => 'Failed to save uploaded file'];
}

// Function to delete old image
function deleteOldImage($pdo, $productId) {
    try {
        $stmt = $pdo->prepare("SELECT image FROM products WHERE id = ?");
        $stmt->execute([$productId]);
        $oldImage = $stmt->fetchColumn();
        
        if ($oldImage && $oldImage !== 'default.jpg' && file_exists(UPLOAD_DIR . $oldImage)) {
            unlink(UPLOAD_DIR . $oldImage);
        }
    } catch (Exception $e) {
        error_log("Error deleting old image: " . $e->getMessage());
    }
}

// Function to calculate daily earning
function calculateDailyEarning($price, $profitRate) {
    return ($price * $profitRate) / 100;
}

// Handle product creation/update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        setFlashMessage('Invalid security token. Please try again.', 'danger');
        header('Location: products.php');
        exit();
    }
    
    // Validate required fields
    if (!isset($_POST['name'], $_POST['price'], $_POST['cycle'], $_POST['profit_rate'], $_POST['status'])) {
        setFlashMessage('Missing required fields.', 'danger');
        header('Location: products.php');
        exit();
    }
    
    $name = trim($_POST['name']);
    $price = floatval($_POST['price']);
    $cycle = intval($_POST['cycle']);
    $profit_rate = floatval($_POST['profit_rate']);
    $status = $_POST['status'];
    
    // Validate data
    if (empty($name) || $price <= 0 || $cycle <= 0 || $profit_rate <= 0) {
        setFlashMessage('Invalid product data. Please check all fields.', 'danger');
        header('Location: products.php');
        exit();
    }
    
    // Validate status
    if (!in_array($status, ['active', 'inactive'])) {
        setFlashMessage('Invalid status value.', 'danger');
        header('Location: products.php');
        exit();
    }
    
    // Calculate daily earning
    $daily_earning = calculateDailyEarning($price, $profit_rate);
    
    // Handle image upload
    $uploadResult = handleImageUpload($_FILES['image'] ?? null);
    
    if (isset($_POST['product_id']) && !empty($_POST['product_id'])) {
        // Update existing product
        $product_id = intval($_POST['product_id']);
        
        try {
            if ($uploadResult['success']) {
                // Delete old image
                deleteOldImage($pdo, $product_id);
                
                // Update with new image
                $stmt = $pdo->prepare("UPDATE products SET name = ?, price = ?, daily_earning = ?, cycle = ?, profit_rate = ?, status = ?, image = ? WHERE id = ?");
                $stmt->execute([$name, $price, $daily_earning, $cycle, $profit_rate, $status, $uploadResult['image'], $product_id]);
            } else {
                // Update without changing image
                $stmt = $pdo->prepare("UPDATE products SET name = ?, price = ?, daily_earning = ?, cycle = ?, profit_rate = ?, status = ? WHERE id = ?");
                $stmt->execute([$name, $price, $daily_earning, $cycle, $profit_rate, $status, $product_id]);
            }
            
            setFlashMessage('Product updated successfully.', 'success');
        } catch(PDOException $e) {
            error_log("Product update error: " . $e->getMessage());
            setFlashMessage('Error updating product: ' . $e->getMessage(), 'danger');
        }
    } else {
        // Create new product
        $image = $uploadResult['success'] ? $uploadResult['image'] : 'default.jpg';
        
        if (!$uploadResult['success'] && !empty($uploadResult['message'])) {
            setFlashMessage($uploadResult['message'], 'warning');
        }
        
        try {
            $stmt = $pdo->prepare("INSERT INTO products (name, image, price, daily_earning, cycle, profit_rate, status, min_withdraw, referral_level1_percentage, referral_level2_percentage) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $name, 
                $image, 
                $price, 
                $daily_earning, 
                $cycle, 
                $profit_rate, 
                $status, 
                DEFAULT_MIN_WITHDRAW, 
                DEFAULT_REFERRAL_L1_PCT, 
                DEFAULT_REFERRAL_L2_PCT
            ]);
            
            setFlashMessage('Product created successfully.', 'success');
        } catch(PDOException $e) {
            error_log("Product creation error: " . $e->getMessage());
            setFlashMessage('Error creating product: ' . $e->getMessage(), 'danger');
        }
    }
    
    // Redirect to prevent form resubmission
    header('Location: products.php');
    exit();
}

// Fetch products for display
$products = fetchProducts($pdo);
$flash = getFlashMessage();

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
        .loading-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 9999;
            justify-content: center;
            align-items: center;
        }
        .loading-overlay.active {
            display: flex;
        }
    </style>
</head>
<body>
    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="spinner-border text-light" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
    </div>

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
                            <?php echo htmlspecialchars($admin_name, ENT_QUOTES, 'UTF-8'); ?>
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
                <?php if ($flash): ?>
                    <div class="alert alert-<?php echo htmlspecialchars($flash['type'], ENT_QUOTES, 'UTF-8'); ?> alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($flash['message'], ENT_QUOTES, 'UTF-8'); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
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
                                            <td><?php echo htmlspecialchars($product['id'], ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td>
                                                <?php if (!empty($product['image']) && $product['image'] !== 'default.jpg'): ?>
                                                    <img src="../uploads/products/<?php echo htmlspecialchars($product['image'], ENT_QUOTES, 'UTF-8'); ?>" alt="Product Image" class="img-thumbnail" style="max-height: 50px;">
                                                <?php else: ?>
                                                    <span class="text-muted">No image</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td>RWF <?php echo number_format($product['price'], 2); ?></td>
                                            <td>RWF <?php echo number_format($product['daily_earning'], 2); ?></td>
                                            <td><?php echo htmlspecialchars($product['cycle'], ENT_QUOTES, 'UTF-8'); ?> days</td>
                                            <td><?php echo htmlspecialchars($product['profit_rate'], ENT_QUOTES, 'UTF-8'); ?>%</td>
                                            <td>
                                                <span class="badge bg-<?php echo $product['status'] == 'active' ? 'success' : 'danger'; ?>">
                                                    <?php echo htmlspecialchars(ucfirst($product['status']), ENT_QUOTES, 'UTF-8'); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-primary edit-btn" 
                                                        data-id="<?php echo htmlspecialchars($product['id'], ENT_QUOTES, 'UTF-8'); ?>"
                                                        data-name="<?php echo htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8'); ?>"
                                                        data-price="<?php echo htmlspecialchars($product['price'], ENT_QUOTES, 'UTF-8'); ?>"
                                                        data-cycle="<?php echo htmlspecialchars($product['cycle'], ENT_QUOTES, 'UTF-8'); ?>"
                                                        data-profit-rate="<?php echo htmlspecialchars($product['profit_rate'], ENT_QUOTES, 'UTF-8'); ?>"
                                                        data-status="<?php echo htmlspecialchars($product['status'], ENT_QUOTES, 'UTF-8'); ?>"
                                                        data-image="<?php echo htmlspecialchars($product['image'], ENT_QUOTES, 'UTF-8'); ?>"
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#editModal">
                                                    Edit
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Add New Product Button -->
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">Add New Product</button>
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
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="add_name" class="form-label">Name *</label>
                            <input type="text" class="form-control" id="add_name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="add_price" class="form-label">Price (RWF) *</label>
                            <input type="number" class="form-control" id="add_price" name="price" step="0.01" min="0.01" required>
                        </div>
                        <div class="mb-3">
                            <label for="add_profit_rate" class="form-label">Profit Rate (%) *</label>
                            <input type="number" class="form-control" id="add_profit_rate" name="profit_rate" step="0.01" min="0.01" required>
                            <small class="form-text text-muted">Daily earning will be calculated as: (Price × Profit Rate) / 100</small>
                        </div>
                        <div class="mb-3">
                            <label for="add_cycle" class="form-label">Cycle (days) *</label>
                            <input type="number" class="form-control" id="add_cycle" name="cycle" min="1" required>
                        </div>
                        <div class="mb-3">
                            <label for="add_image" class="form-label">Product Image</label>
                            <input type="file" class="form-control" id="add_image" name="image" accept="image/jpeg,image/png">
                            <small class="form-text text-muted">Allowed: JPG, PNG. Max: 2MB</small>
                        </div>
                        <div class="mb-3">
                            <label for="add_status" class="form-label">Status *</label>
                            <select class="form-select" id="add_status" name="status" required>
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

    <!-- Edit Product Modal (Single, Dynamic) -->
    <div class="modal fade" id="editModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Product</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="editProductForm" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="product_id" id="edit_product_id">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="edit_name" class="form-label">Name *</label>
                            <input type="text" class="form-control" id="edit_name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_price" class="form-label">Price (RWF) *</label>
                            <input type="number" class="form-control" id="edit_price" name="price" step="0.01" min="0.01" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_profit_rate" class="form-label">Profit Rate (%) *</label>
                            <input type="number" class="form-control" id="edit_profit_rate" name="profit_rate" step="0.01" min="0.01" required>
                            <small class="form-text text-muted">Daily earning will be calculated as: (Price × Profit Rate) / 100</small>
                        </div>
                        <div class="mb-3">
                            <label for="edit_cycle" class="form-label">Cycle (days) *</label>
                            <input type="number" class="form-control" id="edit_cycle" name="cycle" min="1" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_image" class="form-label">Product Image</label>
                            <input type="file" class="form-control" id="edit_image" name="image" accept="image/jpeg,image/png">
                            <div class="mt-2" id="current_image_container" style="display: none;">
                                <small class="form-text text-muted">Current image:</small><br>
                                <img id="current_image" src="" alt="Current Product Image" class="img-thumbnail" style="max-height: 100px;">
                            </div>
                            <small class="form-text text-muted">Leave empty to keep current image. Allowed: JPG, PNG. Max: 2MB</small>
                        </div>
                        <div class="mb-3">
                            <label for="edit_status" class="form-label">Status *</label>
                            <select class="form-select" id="edit_status" name="status" required>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const loadingOverlay = document.getElementById('loadingOverlay');
            const addForm = document.getElementById('addProductForm');
            const editForm = document.getElementById('editProductForm');
            
            // Form validation function
            function validateProductForm(form) {
                const price = parseFloat(form.querySelector('[name="price"]').value);
                const cycle = parseInt(form.querySelector('[name="cycle"]').value);
                const profitRate = parseFloat(form.querySelector('[name="profit_rate"]').value);
                const name = form.querySelector('[name="name"]').value.trim();
                
                if (!name) {
                    alert('Product name is required');
                    return false;
                }
                
                if (isNaN(price) || price <= 0) {
                    alert('Price must be greater than 0');
                    return false;
                }
                
                if (isNaN(cycle) || cycle <= 0) {
                    alert('Cycle must be greater than 0');
                    return false;
                }
                
                if (isNaN(profitRate) || profitRate <= 0) {
                    alert('Profit rate must be greater than 0');
                    return false;
                }
                
                return true;
            }
            
            // Show loading overlay on form submit
            function handleFormSubmit(e) {
                if (!validateProductForm(e.target)) {
                    e.preventDefault();
                    return false;
                }
                loadingOverlay.classList.add('active');
            }
            
            // Add form submission
            if (addForm) {
                addForm.addEventListener('submit', handleFormSubmit);
            }
            
            // Edit form submission
            if (editForm) {
                editForm.addEventListener('submit', handleFormSubmit);
            }
            
            // Populate edit modal with product data
            const editButtons = document.querySelectorAll('.edit-btn');
            editButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const id = this.getAttribute('data-id');
                    const name = this.getAttribute('data-name');
                    const price = this.getAttribute('data-price');
                    const cycle = this.getAttribute('data-cycle');
                    const profitRate = this.getAttribute('data-profit-rate');
                    const status = this.getAttribute('data-status');
                    const image = this.getAttribute('data-image');
                    
                    // Populate form fields
                    document.getElementById('edit_product_id').value = id;
                    document.getElementById('edit_name').value = name;
                    document.getElementById('edit_price').value = price;
                    document.getElementById('edit_cycle').value = cycle;
                    document.getElementById('edit_profit_rate').value = profitRate;
                    document.getElementById('edit_status').value = status;
                    
                    // Show current image if exists
                    const currentImageContainer = document.getElementById('current_image_container');
                    const currentImage = document.getElementById('current_image');
                    
                    if (image && image !== 'default.jpg') {
                        currentImage.src = '../uploads/products/' + image;
                        currentImageContainer.style.display = 'block';
                    } else {
                        currentImageContainer.style.display = 'none';
                    }
                });
            });
            
            // File input preview
            const fileInputs = document.querySelectorAll('input[type="file"]');
            fileInputs.forEach(input => {
                input.addEventListener('change', function() {
                    if (this.files && this.files[0]) {
                        const file = this.files[0];
                        const fileSize = file.size / 1024 / 1024; // Convert to MB
                        
                        if (fileSize > 2) {
                            alert('File size exceeds 2MB. Please choose a smaller file.');
                            this.value = '';
                            return;
                        }
                        
                        const allowedTypes = ['image/jpeg', 'image/png'];
                        if (!allowedTypes.includes(file.type)) {
                            alert('Invalid file type. Only JPG and PNG are allowed.');
                            this.value = '';
                            return;
                        }
                        
                        console.log('Selected file:', file.name);
                    }
                });
            });
            
            // Reset add form when modal is closed
            const addModal = document.getElementById('addModal');
            if (addModal) {
                addModal.addEventListener('hidden.bs.modal', function() {
                    addForm.reset();
                });
            }
        });
    </script>
</body>
</html>