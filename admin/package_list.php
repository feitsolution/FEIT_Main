<?php
// File name: package_list.php
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

// Process status toggle if submitted
if(isset($_POST['toggle_status'])) {
    $package_id = $_POST['package_id'];
    $new_status = $_POST['new_status'];
    $user_id = $_SESSION['user_id'];
    
    $updateQuery = "UPDATE packages SET status = ? WHERE id = ?";
    $stmt = $conn->prepare($updateQuery);
    $stmt->bind_param("si", $new_status, $package_id);
    
    if($stmt->execute()) {
        $action = $new_status == 'active' ? 'activated' : 'deactivated';
        $_SESSION['success_message'] = "Package successfully $action!";
        
        $action_type = $new_status == 'active' ? 'activate_package' : 'deactivate_package';
        $details = "Package ID #$package_id was $action by user ID #$user_id";
        
        $logQuery = "INSERT INTO user_logs (user_id, action_type, inquiry_id, details, created_at) VALUES (?, ?, ?, ?, NOW())";
        $logStmt = $conn->prepare($logQuery);
        $inquiry_id = 0;
        $logStmt->bind_param("isis", $user_id, $action_type, $inquiry_id, $details);
        $logStmt->execute();
        $logStmt->close();
    } else {
        $_SESSION['error_message'] = "Error updating package status: " . $conn->error;
    }
    $stmt->close();
}

// Fetch packages
$sql = "SELECT p.*, pr.name as product_name 
        FROM packages p 
        LEFT JOIN products pr ON p.product_id = pr.id 
        ORDER BY p.id ASC";
$result = $conn->query($sql);

// Count total packages
$countQuery = "SELECT COUNT(*) as total FROM packages";
$countResult = $conn->query($countQuery);
$totalPackages = $countResult->fetch_assoc()['total'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Package List</title>
    <!-- FAVICON -->
    <link rel="icon" href="img/system/letter-f.png" type="image/png">
    <link href="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/style.min.css" rel="stylesheet" />
    <link href="css/styles.css" rel="stylesheet" />
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
    
    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.12/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.12/dist/sweetalert2.all.min.js"></script>
</head>

<body class="sb-nav-fixed">
<?php include 'navbar.php'; ?>

<div id="layoutSidenav">
    <?php include 'sidebar.php'; ?>
    <div id="layoutSidenav_content">
        <main>
            <div class="container-fluid px-4">
                <?php
                if (isset($_SESSION['success_message'])) {
                    echo '<script>
                        document.addEventListener("DOMContentLoaded", function() {
                            Swal.fire({
                                title: "Success!",
                                text: "' . addslashes($_SESSION['success_message']) . '",
                                icon: "success",
                                confirmButtonColor: "#4CAF50"
                            });
                        });
                    </script>';
                    unset($_SESSION['success_message']);
                }
                
                if (isset($_SESSION['error_message'])) {
                    echo '<script>
                        document.addEventListener("DOMContentLoaded", function() {
                            Swal.fire({
                                title: "Error!",
                                text: "' . addslashes($_SESSION['error_message']) . '",
                                icon: "error",
                                confirmButtonColor: "#dc3545"
                            });
                        });
                    </script>';
                    unset($_SESSION['error_message']);
                }
                ?>

                <h1 class="mt-3">Packages</h1>
                <ol class="breadcrumb mb-4">
                    <div class="alert alert-info">
                        <strong>Total Packages:</strong> <?= $totalPackages ?>
                    </div>
                </ol>
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover" id="packagesTable">
                                <thead class="table-dark">
                                    <tr>
                                        <th>ID</th>
                                        <th>Product</th>
                                        <th>Description</th>
                                        <th>Max Count</th>
                                        <th>Amount (LKR)</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($result && $result->num_rows > 0): ?>
                                        <?php while ($row = $result->fetch_assoc()): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($row['id']) ?></td>
                                                <td><?= htmlspecialchars($row['product_name'] ?? 'N/A') ?></td>
                                                <td><?= htmlspecialchars($row['description']) ?></td>
                                                <td><?= htmlspecialchars($row['max_count'] ?? 'No Limit') ?></td>
                                                <td><?= number_format($row['amount'], 2) ?> LKR</td>
                                                <td>
                                                    <?php
                                                    $status = $row['status'] ?? 'active';
                                                    $statusClass = $status == 'active' ? 'status-active' : 'status-inactive';
                                                    ?>
                                                    <span class="<?= $statusClass ?>"><?= ucfirst($status) ?></span>
                                                </td>
                                                <td>
                                                    <a href="edit_package.php?id=<?= $row['id'] ?>" class="btn btn-primary btn-sm mb-1">
                                                        <i class="fas fa-edit"></i> Edit
                                                    </a>
                                                    
                                                    <?php
                                                    $newStatus = $status == 'active' ? 'inactive' : 'active';
                                                    $btnClass = $status == 'active' ? 'btn-danger' : 'btn-success';
                                                    $btnText = $status == 'active' ? 'Deactivate' : 'Activate';
                                                    $btnIcon = $status == 'active' ? 'fa-ban' : 'fa-check';
                                                    ?>
                                                    <button type="button" class="btn <?= $btnClass ?> btn-sm mb-1" 
                                                            onclick="confirmStatusChange(<?= $row['id'] ?>, '<?= $newStatus ?>', '<?= htmlspecialchars($row['description']) ?>')">
                                                        <i class="fas <?= $btnIcon ?>"></i> <?= $btnText ?>
                                                    </button>
                                                    
                                                    <form id="toggleForm<?= $row['id'] ?>" action="" method="POST" style="display:none;">
                                                        <input type="hidden" name="package_id" value="<?= $row['id'] ?>">
                                                        <input type="hidden" name="new_status" value="<?= $newStatus ?>">
                                                        <input type="hidden" name="toggle_status" value="1">
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
<script src="js/scripts.js"></script>
<script src="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/umd/simple-datatables.min.js" crossorigin="anonymous"></script>
<script>
    window.addEventListener('DOMContentLoaded', event => {
        const datatablesSimple = document.getElementById('packagesTable');
        if (datatablesSimple) {
            new simpleDatatables.DataTable(datatablesSimple);
        }
    });
    
    function confirmStatusChange(packageId, newStatus, packageDesc) {
        const action = newStatus === 'active' ? 'activate' : 'deactivate';
        const actionCapitalized = action.charAt(0).toUpperCase() + action.slice(1);
        
        Swal.fire({
            title: `${actionCapitalized} Package?`,
            text: `Are you sure you want to ${action} "${packageDesc}"?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: newStatus === 'active' ? '#28a745' : '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: `Yes, ${action} it!`,
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById(`toggleForm${packageId}`).submit();
                Swal.fire({
                    title: 'Processing...',
                    text: `${actionCapitalized} the package.`,
                    icon: 'info',
                    showConfirmButton: false,
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
            }
        });
    }
</script>
</body>
</html>
<?php
$conn->close();
?>