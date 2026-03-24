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

// Get current user's role_id from session
$current_user_role = isset($_SESSION['role_id']) ? (int)$_SESSION['role_id'] : 0;
$canEditRecords = ($current_user_role === 1 || $current_user_role === 3);

// Process status toggle if submitted
if(isset($_POST['toggle_status'])) {
    // Only Admin and Moderator can toggle package status
    if (!in_array($current_user_role, [1, 3])) {
        $_SESSION['error_message'] = "Access denied. Admin or Moderator privileges required.";
        header("Location: package_list.php");
        exit();
    }
    
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

// Initialize search parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 10;
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Build basic SQL query
$countSql = "SELECT COUNT(*) as total FROM packages p LEFT JOIN products pr ON p.product_id = pr.id";
$sql = "SELECT p.*, pr.name as product_name FROM packages p LEFT JOIN products pr ON p.product_id = pr.id";

// Add search condition if search term is provided
if (!empty($search)) {
    $searchTerm = $conn->real_escape_string($search);
    $searchCondition = " WHERE p.id LIKE '%$searchTerm%' OR 
                        p.description LIKE '%$searchTerm%' OR 
                        pr.name LIKE '%$searchTerm%' OR
                        p.status LIKE '%$searchTerm%'";
    $countSql .= $searchCondition;
    $sql .= $searchCondition;
}

// Add order by and pagination
$sql .= " ORDER BY p.id ASC LIMIT $limit OFFSET $offset";

// Execute the queries
$countResult = $conn->query($countSql);
$totalRows = 0;
if ($countResult && $countResult->num_rows > 0) {
    $totalRows = $countResult->fetch_assoc()['total'];
}
$totalPages = ceil($totalRows / $limit);

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php include('header.php'); ?>
    <title>Package List</title>
    <link href="css/product-list.css" rel="stylesheet" />
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

                <br>
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4>All Packages</h4>
                </div>

                    <div class="card product-card mb-4">
                        <div class="card-body">
                            <!-- Premium Filter Bar -->
                            <div class="invoice-filter-bar d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
                                <form method="get" class="d-flex align-items-center gap-2 flex-grow-1">
                                    <div class="position-relative flex-grow-1" style="max-width: 360px;">
                                        <i class="fas fa-search position-absolute" style="top: 50%; left: 12px; transform: translateY(-50%); color: #a0aec0; font-size: 0.85rem;"></i>
                                        <input type="text" name="search" class="form-control ps-4"
                                            placeholder=" Search packages by name, product, status..."
                                            value="<?php echo htmlspecialchars($search); ?>">
                                    </div>
                                    <button type="submit" class="btn btn-primary btn-filter">
                                        <i class="fas fa-search me-1"></i> Search
                                    </button>
                                    <?php if (!empty($search)): ?>
                                        <a href="package_list.php" class="btn btn-outline-secondary btn-clear">
                                            <i class="fas fa-times me-1"></i> Clear
                                        </a>
                                    <?php endif; ?>
                                    <input type="hidden" name="limit" value="<?php echo $limit; ?>">
                                    <input type="hidden" name="page" value="1">
                                </form>
                                <form method="get" class="d-flex align-items-center gap-2">
                                    <?php if (!empty($search)): ?>
                                        <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                                    <?php endif; ?>
                                    <input type="hidden" name="page" value="1">
                                    <span class="entries-label">Show</span>
                                    <select name="limit" class="form-select" style="width: 80px;" onchange="this.form.submit()">
                                        <option value="10" <?php if ($limit == 10) echo 'selected'; ?>>10</option>
                                        <option value="25" <?php if ($limit == 25) echo 'selected'; ?>>25</option>
                                        <option value="50" <?php if ($limit == 50) echo 'selected'; ?>>50</option>
                                        <option value="100" <?php if ($limit == 100) echo 'selected'; ?>>100</option>
                                    </select>
                                    <span class="entries-label">entries</span>
                                </form>
                            </div>

                            <?php if (!empty($search)): ?>
                                <div class="search-results-alert mb-4">
                                    <i class="fas fa-filter me-1"></i>
                                    Showing results for: <strong><?php echo htmlspecialchars($search); ?></strong>
                                    — <strong><?php echo $totalRows; ?></strong> found
                                </div>
                            <?php endif; ?>

                            <div class="table-responsive">
                            <table class="table table-product" id="packagesTable">
                                <thead>
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
                                                <td><span class="product-id"><?= htmlspecialchars($row['id']) ?></span></td>
                                                <td><span class="product-name"><?= htmlspecialchars($row['product_name'] ?? 'N/A') ?></span></td>
                                                <td>
                                                    <span class="product-desc" title="<?= htmlspecialchars($row['description']) ?>">
                                                        <?= htmlspecialchars($row['description']) ?>
                                                    </span>
                                                </td>
                                                <td><?= htmlspecialchars($row['max_count'] ?? 'No Limit') ?></td>
                                                <td>
                                                    <span class="product-price"><?= number_format($row['amount'], 2) ?></span>
                                                    <span class="product-currency">LKR</span>
                                                </td>
                                                <td>
                                                    <?php $status = $row['status'] ?? 'active'; ?>
                                                    <?php if ($status == 'active'): ?>
                                                        <span class="badge-soft badge-soft-success">Active</span>
                                                    <?php else: ?>
                                                        <span class="badge-soft badge-soft-secondary">Inactive</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div class="product-action-btns d-flex gap-1">
                                                        <button class="btn btn-view" title="View Details" data-bs-toggle="modal" data-bs-target="#viewModal<?= $row['id'] ?>">
                                                            <i class="fas fa-eye"></i>
                                                        </button>

                                                        <?php if ($canEditRecords): ?>
                                                        <a href="edit_package.php?id=<?= $row['id'] ?>" class="btn btn-edit" data-bs-toggle="tooltip" data-bs-placement="top" title="Edit Package">
                                                            <i class="fas fa-pen"></i>
                                                        </a>
                                                        
                                                        <?php
                                                        $newStatus = $status == 'active' ? 'inactive' : 'active';
                                                        ?>
                                                        <button type="button" class="btn <?= $status == 'active' ? 'btn-deactivate' : 'btn-activate' ?>"
                                                                data-bs-toggle="tooltip" data-bs-placement="top" title="<?= $status == 'active' ? 'Deactivate' : 'Activate' ?>"
                                                                onclick="confirmStatusChange(<?= $row['id'] ?>, '<?= $newStatus ?>', '<?= htmlspecialchars($row['description']) ?>')">
                                                            <i class="fas <?= $status == 'active' ? 'fa-ban' : 'fa-check' ?>"></i>
                                                        </button>
                                                        
                                                        <form id="toggleForm<?= $row['id'] ?>" action="" method="POST" style="display:none;">
                                                            <input type="hidden" name="package_id" value="<?= $row['id'] ?>">
                                                            <input type="hidden" name="new_status" value="<?= $newStatus ?>">
                                                            <input type="hidden" name="toggle_status" value="1">
                                                        </form>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>

                                            <!-- View Modal -->
                                            <div class="modal fade" id="viewModal<?= $row['id'] ?>" tabindex="-1" aria-labelledby="viewModalLabel" aria-hidden="true">
                                                <div class="modal-dialog modal-dialog-centered">
                                                    <div class="modal-content">
                                                        <div class="modal-header bg-light">
                                                            <h5 class="modal-title" id="viewModalLabel"><i class="fas fa-cube me-2"></i>Package Details</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <div class="row g-3">
                                                                <div class="col-md-6">
                                                                    <div class="p-3 bg-light rounded">
                                                                        <small class="text-muted text-uppercase fw-semibold">ID</small>
                                                                        <p class="mb-0 mt-1"><?= htmlspecialchars($row['id']) ?></p>
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <div class="p-3 bg-light rounded">
                                                                        <small class="text-muted text-uppercase fw-semibold">Product</small>
                                                                        <p class="mb-0 mt-1"><?= htmlspecialchars($row['product_name'] ?? 'N/A') ?></p>
                                                                    </div>
                                                                </div>
                                                                <div class="col-12">
                                                                    <div class="p-3 bg-light rounded">
                                                                        <small class="text-muted text-uppercase fw-semibold">Description</small>
                                                                        <p class="mb-0 mt-1"><?= htmlspecialchars($row['description']) ?></p>
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <div class="p-3 bg-light rounded">
                                                                        <small class="text-muted text-uppercase fw-semibold">Max Count</small>
                                                                        <p class="mb-0 mt-1"><?= htmlspecialchars($row['max_count'] ?? 'No Limit') ?></p>
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <div class="p-3 bg-light rounded">
                                                                        <small class="text-muted text-uppercase fw-semibold">Amount</small>
                                                                        <p class="mb-0 mt-1"><?= number_format($row['amount'], 2) ?> LKR</p>
                                                                    </div>
                                                                </div>
                                                                <div class="col-12">
                                                                    <div class="p-3 bg-light rounded">
                                                                        <small class="text-muted text-uppercase fw-semibold">Status</small>
                                                                        <p class="mb-0 mt-1">
                                                                            <?php if ($status == 'active'): ?>
                                                                                <span class="badge-soft badge-soft-success">Active</span>
                                                                            <?php else: ?>
                                                                                <span class="badge-soft badge-soft-danger">Inactive</span>
                                                                            <?php endif; ?>
                                                                        </p>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                            <?php if ($canEditRecords): ?>
                                                            <a href="edit_package.php?id=<?= $row['id'] ?>" class="btn btn-primary">
                                                                <i class="fas fa-edit"></i> Edit
                                                            </a>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endwhile; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                            <!-- Premium Pagination -->
                            <div class="pagination-container d-flex justify-content-between align-items-center mt-4">
                                <div class="entries-info">
                                    <?php if ($result && $result->num_rows > 0): ?>
                                        Showing <strong><?php echo ($offset + 1); ?></strong> to
                                        <strong><?php echo min($offset + $limit, $totalRows); ?></strong> of <strong><?php echo $totalRows; ?></strong>
                                        entries
                                    <?php else: ?>
                                        Showing <strong>0</strong> to <strong>0</strong> of <strong>0</strong> entries
                                    <?php endif; ?>
                                </div>
                                <nav aria-label="Page navigation">
                                    <ul class="pagination mb-0">
                                        <li class="page-item <?php if ($page <= 1) echo 'disabled'; ?>">
                                            <a class="page-link"
                                                href="?page=<?php echo $page - 1; ?>&limit=<?php echo $limit; ?>&search=<?php echo urlencode($search); ?>">
                                                <i class="fas fa-chevron-left"></i>
                                            </a>
                                        </li>

                                        <?php
                                        // Display a limited number of page links
                                        $maxPagesToShow = 5;
                                        $startPage = max(1, min($page - floor($maxPagesToShow / 2), $totalPages - $maxPagesToShow + 1));
                                        $endPage = min($totalPages, $startPage + $maxPagesToShow - 1);

                                        // Show "..." before the first page link if needed
                                        if ($startPage > 1): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?page=1&limit=<?php echo $limit; ?>&search=<?php echo urlencode($search); ?>">1</a>
                                            </li>
                                            <?php if ($startPage > 2): ?>
                                                <li class="page-item disabled">
                                                    <span class="page-link">...</span>
                                                </li>
                                            <?php endif; ?>
                                        <?php endif; ?>

                                        <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                                            <li class="page-item <?php if ($page == $i) echo 'active'; ?>">
                                                <a class="page-link"
                                                    href="?page=<?php echo $i; ?>&limit=<?php echo $limit; ?>&search=<?php echo urlencode($search); ?>"><?php echo $i; ?></a>
                                            </li>
                                        <?php endfor; ?>

                                        <?php 
                                        // Show "..." after the last page link if needed
                                        if ($endPage < $totalPages): ?>
                                            <?php if ($endPage < $totalPages - 1): ?>
                                                <li class="page-item disabled">
                                                    <span class="page-link">...</span>
                                                </li>
                                            <?php endif; ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?page=<?php echo $totalPages; ?>&limit=<?php echo $limit; ?>&search=<?php echo urlencode($search); ?>"><?php echo $totalPages; ?></a>
                                            </li>
                                        <?php endif; ?>

                                        <li class="page-item <?php if ($page >= $totalPages) echo 'disabled'; ?>">
                                            <a class="page-link"
                                                href="?page=<?php echo $page + 1; ?>&limit=<?php echo $limit; ?>&search=<?php echo urlencode($search); ?>">
                                                <i class="fas fa-chevron-right"></i>
                                            </a>
                                        </li>
                                    </ul>
                                </nav>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
<script src="js/scripts.js"></script>
<script>
    // Initialize Bootstrap tooltips
    document.addEventListener('DOMContentLoaded', function() {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
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