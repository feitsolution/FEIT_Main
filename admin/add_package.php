<?php
// File name: add_package.php
session_start();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    if (ob_get_level()) {
        ob_end_clean();
    }
    header("Location: signin.php");
    exit();
}

// Only Admin can add packages
if (!isset($_SESSION['role_id']) || $_SESSION['role_id'] !== 1) {
    header("Location: package_list.php");
    exit();
}

// Include database connection
include 'db_connection.php';
include 'functions.php';

$package_added = false;
$error_message = null;

// Fetch all active products for the dropdown
$products = [];
$productSql = "SELECT id, name FROM products WHERE status = 'active' ORDER BY name ASC";
$productResult = $conn->query($productSql);
if ($productResult) {
    while ($row = $productResult->fetch_assoc()) {
        $products[] = $row;
    }
} else {
    $error_message = "Error fetching products: " . $conn->error;
}


// Process form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $conn->real_escape_string($_POST['name']);
    $description = $conn->real_escape_string($_POST['description']);
    $product_id = intval($_POST['product_id']);
    $max_count = isset($_POST['max_count']) && trim($_POST['max_count']) !== '' ? intval($_POST['max_count']) : null;
    $amount = floatval($_POST['amount']);
    $status = $conn->real_escape_string($_POST['status']); // Default to 'active' or take from form

    // Basic validation
    if (empty($name) || empty($description) || empty($product_id) || !isset($amount) || empty($status)) {
        $error_message = "All required fields must be filled.";
    } elseif (!is_numeric($amount) || $amount <= 0) {
        $error_message = "Amount must be a positive number.";
    } elseif (isset($max_count) && (!is_numeric($max_count) || $max_count < 0)) {
        $error_message = "Max Count must be a non-negative number if provided.";
    } else {
        $insertSql = "INSERT INTO packages (name, product_id, description, max_count, amount, status) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($insertSql);
        $stmt->bind_param("sisids", $name, $product_id, $description, $max_count, $amount, $status);
        
        if ($stmt->execute()) {
            $package_id = $conn->insert_id;
            $package_added = true;
            
            // Log the creation
            $user_id = $_SESSION['user_id'];
            $user_name = $_SESSION['username'] ?? 'Administrator';
            $details = "New package ID #$package_id ($name) was added by $user_name.";
            
            $logQuery = "INSERT INTO user_logs (user_id, action_type, inquiry_id, details, created_at) VALUES (?, 'add_package', 0, ?, NOW())";
            $logStmt = $conn->prepare($logQuery);
            $logStmt->bind_param("is", $user_id, $details);
            $logStmt->execute();
            $logStmt->close();

            // Redirect to package list with success message
            $_SESSION['package_success_message'] = "Package '$name' added successfully!";
            header("Location: package_list.php");
            exit();

        } else {
            $error_message = "Error adding package: " . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php include('header.php'); ?>
    <title>Add New Package</title>
    <link href="css/forms.css" rel="stylesheet" />
    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />    
    <style>
        body {
            background-color: #f8fafc;
        }
        .popup-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }
        .popup-content {
            background: white;
            padding: 20px;
            border-radius: 5px;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        /* Select2 Bootstrapped styling for premium UI */
        .select2-container--bootstrap-5 .select2-selection {
            height: calc(2.25rem + 2px);
            line-height: 1.5;
            min-height: 45px;
            border: 1.5px solid #ced4da;
            border-radius: 0.375rem;
            background-color: #fff;
            box-shadow: none;
        }
        .select2-container--bootstrap-5 .select2-selection--single .select2-selection__rendered {
            margin-top: 0.25rem;
        }
        .select2-container--bootstrap-5 .select2-selection--single .select2-selection__arrow {
            top: 0.55rem;
        }
        .select2-container--bootstrap-5.select2-container--focus .select2-selection {
            border-color: #86b7fe;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
        }
    </style>
</head>

<body class="sb-nav-fixed">
    <?php include 'navbar.php'; ?>
    <div id="layoutSidenav">
        <?php include 'sidebar.php'; ?>
        <div id="layoutSidenav_content">
            <main>
                <div class="container-fluid px-4">
                    <h1 class="mt-3 fs-4">Add New Package</h1>
                    
                    <?php if ($error_message): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?= htmlspecialchars($error_message) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <div class="col-12">
                        <div class="premium-form-container">
                            <form method="POST" action="add_package.php" id="addPackageForm">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="premium-section-header">
                                            <i class="fas fa-box-open"></i> Package Details
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="product_id" class="form-label">Product</label>
                                            <select class="form-select" id="product_id" name="product_id" required>
                                                <option value="">Select Product</option>
                                                <?php foreach ($products as $product): ?>
                                                    <option value="<?= $product['id'] ?>"><?= htmlspecialchars($product['name']) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <div class="mb-3">
                                            <label for="name" class="form-label">Package Name</label>
                                            <input type="text" class="form-control" id="name" name="name" 
                                                   value="" required>
                                        </div>

                                        <div class="mb-3">
                                            <label for="description" class="form-label">Description</label>
                                            <input type="text" class="form-control" id="description" name="description" 
                                                   value="" required>
                                        </div>

                                        <div class="mb-3">
                                            <label for="max_count" class="form-label">Max Count</label>
                                            <input type="number" class="form-control" id="max_count" name="max_count" 
                                                   value="" min="1" placeholder="Leave empty for no limit">
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <div class="premium-section-header">
                                            <i class="fas fa-cog"></i> Configuration
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="amount" class="form-label">Amount (LKR)</label>
                                            <input type="number" step="0.01" class="form-control" id="amount" name="amount" 
                                                   value="" required>
                                        </div>

                                        <div class="mb-3">
                                            <label for="status" class="form-label">Status</label>
                                            <select class="form-select" id="status" name="status" required>
                                                <option value="active" selected>Active</option>
                                                <option value="inactive">Inactive</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="row mt-4 pt-3 border-top">
                                    <div class="col-12 d-flex justify-content-end gap-3">
                                        <a href="package_list.php" class="premium-back-btn text-decoration-none d-flex align-items-center">
                                            <i class="fas fa-arrow-left me-2"></i> Cancel
                                        </a>
                                        <button type="submit" class="premium-save-btn">
                                            <i class="fas fa-plus me-2"></i> Add Package
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Success Popup -->
    <div id="successPopup" class="popup-overlay">
        <div class="popup-content">
            <h3>Package Added Successfully!</h3>
            <p>The new package details have been saved.</p>
            <button class="btn btn-primary" onclick="closePopup()">Close</button>
        </div>
    </div>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
    <!-- Select2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        // Initialize Select2
        $(document).ready(function() {
            $('#product_id').select2({
                theme: 'bootstrap-5',
                width: '100%',
                placeholder: 'Select Product',
                allowClear: true
            });

            $('#status').select2({
                theme: 'bootstrap-5',
                width: '100%',
                minimumResultsForSearch: Infinity,
                placeholder: 'Select Status'
            });
        });

        <?php if ($package_added): ?>
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('successPopup').style.display = 'flex';
        });
        <?php endif; ?>

        function closePopup() {
            document.getElementById('successPopup').style.display = 'none';
        }
    </script>
</body>
</html>
<?php
$conn->close();
?>
