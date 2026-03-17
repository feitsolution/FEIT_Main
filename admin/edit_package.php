<?php
// File name: edit_package.php
session_start();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    if (ob_get_level()) {
        ob_end_clean();
    }
    header("Location: signin.php");
    exit();
}

// Include database connection
include 'db_connection.php';
include 'functions.php';

// Check if the ID parameter exists
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: package_list.php");
    exit();
}

$package_id = intval($_GET['id']);

// Fetch the package data
$sql = "SELECT p.*, pr.name as product_name FROM packages p LEFT JOIN products pr ON p.product_id = pr.id WHERE p.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $package_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    header("Location: package_list.php");
    exit();
}

$package = $result->fetch_assoc();
$original_package = $package;

$package_updated = false;
$error_message = null;

// Process form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $description = $conn->real_escape_string($_POST['description']);
    $max_count = isset($_POST['max_count']) && trim($_POST['max_count']) !== '' ? intval($_POST['max_count']) : null;
    $amount = floatval($_POST['amount']);
    $status = $conn->real_escape_string($_POST['status']);
    
    $updateSql = "UPDATE packages SET description = ?, max_count = ?, amount = ?, status = ? WHERE id = ?";
    $stmt = $conn->prepare($updateSql);
    $stmt->bind_param("sidsi", $description, $max_count, $amount, $status, $package_id);
    
    if ($stmt->execute()) {
        $package_updated = true;
        
        // Refresh package data
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $package_id);
        $stmt->execute();
        $package = $stmt->get_result()->fetch_assoc();
        
        // Log the change
        $user_id = $_SESSION['user_id'];
        $user_name = $_SESSION['username'] ?? 'Administrator';
        
        $changes = [];
        if ($original_package['description'] != $description) $changes[] = "Description changed";
        if ($original_package['max_count'] != $max_count) $changes[] = "Max count changed to " . ($max_count ?? 'No Limit');
        if ($original_package['amount'] != $amount) $changes[] = "Amount changed to $amount";
        if ($original_package['status'] != $status) $changes[] = "Status changed to $status";
        
        $details = "Package ID #$package_id was updated by $user_name. " . implode(", ", $changes);
        
        $logQuery = "INSERT INTO user_logs (user_id, action_type, inquiry_id, details, created_at) VALUES (?, 'edit_package', 0, ?, NOW())";
        $logStmt = $conn->prepare($logQuery);
        $logStmt->bind_param("is", $user_id, $details);
        $logStmt->execute();
        $logStmt->close();
    } else {
        $error_message = "Error updating package: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Edit Package</title>
    <!-- FAVICON -->
    <link rel="icon" href="img/system/letter-f.png" type="image/png">
    <link href="css/styles.css" rel="stylesheet" />
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
    <style>
        .form-container {
            padding: 25px;
            background-color: #fff;
            border-radius: 5px;
            margin-bottom: 30px;
        }
        .section-header {
            border-left: 4px solid #1565C0;
            padding-left: 10px;
            margin-bottom: 20px;
            font-size: 18px;
            font-weight: 500;
        }
        .save-btn {
            background-color: #1565C0;
            float: right;
            padding: 8px 25px;
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
    </style>
</head>

<body class="sb-nav-fixed">
    <?php include 'navbar.php'; ?>
    <div id="layoutSidenav">
        <?php include 'sidebar.php'; ?>
        <div id="layoutSidenav_content">
            <main>
                <div class="container-fluid px-4">
                    <h1 class="mt-3">Edit Package</h1>
                    
                    <?php if ($error_message): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?= htmlspecialchars($error_message) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="package_list.php">Package List</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Edit Package</li>
                        </ol>
                    </nav>

                    <div class="col-12">
                        <div class="form-container shadow">
                            <form method="POST" action="edit_package.php?id=<?= $package_id ?>" id="editPackageForm">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="section-header">Package Details</div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Product (Read-only)</label>
                                            <input type="text" class="form-control" value="<?= htmlspecialchars($package['product_name'] ?? 'N/A') ?>" readonly>
                                        </div>

                                        <div class="mb-3">
                                            <label for="description" class="form-label">Description</label>
                                            <input type="text" class="form-control" id="description" name="description" 
                                                   value="<?= htmlspecialchars($package['description']) ?>" required>
                                        </div>

                                        <div class="mb-3">
                                            <label for="max_count" class="form-label">Max Count</label>
                                            <input type="number" class="form-control" id="max_count" name="max_count" 
                                                   value="<?= htmlspecialchars($package['max_count'] ?? '') ?>" min="1" placeholder="Leave empty for no limit">
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <div class="section-header">Configuration</div>
                                        
                                        <div class="mb-3">
                                            <label for="amount" class="form-label">Amount (LKR)</label>
                                            <input type="number" step="0.01" class="form-control" id="amount" name="amount" 
                                                   value="<?= htmlspecialchars($package['amount']) ?>" required>
                                        </div>

                                        <div class="mb-3">
                                            <label for="status" class="form-label">Status</label>
                                            <select class="form-select" id="status" name="status" required>
                                                <option value="active" <?= ($package['status'] == 'active') ? 'selected' : '' ?>>Active</option>
                                                <option value="inactive" <?= ($package['status'] == 'inactive') ? 'selected' : '' ?>>Inactive</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="row mt-3">
                                    <div class="col-12">
                                        <button type="submit" class="btn btn-primary save-btn">
                                            <i class="fas fa-save"></i> Save Changes
                                        </button>
                                        <a href="package_list.php" class="btn btn-secondary" style="float: right; margin-right: 10px; padding: 8px 25px;">Cancel</a>
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
            <h3>Package Updated Successfully!</h3>
            <p>The package details have been saved.</p>
            <button class="btn btn-primary" onclick="closePopup()">Close</button>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
    <script>
        <?php if ($package_updated): ?>
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