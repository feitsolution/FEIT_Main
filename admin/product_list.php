<?php
// File name: product_list.php
// Start session at the very beginning
session_start();

// Check if user is logged in, if not redirect to login page
// This check must happen before ANY output is sent to the browser
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    // Clear any existing output buffers
    if (ob_get_level()) {
        ob_end_clean();
    }
    // Force redirect to login page
    header("Location: signin.php");
    exit(); // Stop execution immediately
}

// Include the database connection file
include 'db_connection.php';

include 'functions.php'; // Include helper functions

// Get current user's role_id from session
$current_user_role = isset($_SESSION['role_id']) ? (int)$_SESSION['role_id'] : 0;
$canEditRecords = ($current_user_role === 1 || $current_user_role === 3);

// Process status toggle if submitted
if(isset($_POST['toggle_status'])) {
    // Only Admin and Moderator can toggle product status
    if (!in_array($current_user_role, [1, 3])) {
        $_SESSION['error_message'] = "Access denied. Admin or Moderator privileges required.";
        header("Location: product_list.php");
        exit();
    }
    
    $product_id = $_POST['product_id'];
    $new_status = $_POST['new_status'];
    $user_id = $_SESSION['user_id']; // Get the current user's ID from session
    $product_name = ''; // Initialize product name variable
    
    // First, get the product name for the log
    $productQuery = "SELECT name FROM products WHERE id = ?";
    $stmt = $conn->prepare($productQuery);
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $productResult = $stmt->get_result();
    
    if ($productResult->num_rows > 0) {
        $productData = $productResult->fetch_assoc();
        $product_name = $productData['name'];
    }
    $stmt->close();
    
    // Use prepared statement to prevent SQL injection
    $updateQuery = "UPDATE products SET status = ? WHERE id = ?";
    $stmt = $conn->prepare($updateQuery);
    $stmt->bind_param("si", $new_status, $product_id);
    
    if($stmt->execute()) {
        // Set success message based on the new status
        $action = $new_status == 'active' ? 'activated' : 'deactivated';
        $_SESSION['success_message'] = "Product successfully $action!";
        
        // Log the action to user_logs table
        $action_type = $new_status == 'active' ? 'activate_product' : 'deactivate_product';
        $details = "Product ID #$product_id ($product_name) was $action by user ID #$user_id";
        
        $logQuery = "INSERT INTO user_logs (user_id, action_type, inquiry_id, details, created_at) VALUES (?, ?, ?, ?, NOW())";
        $logStmt = $conn->prepare($logQuery);
        $inquiry_id = 0; // Not applicable for product actions
        $logStmt->bind_param("isis", $user_id, $action_type, $inquiry_id, $details);
        $logStmt->execute();
        $logStmt->close();
    } else {
        $_SESSION['error_message'] = "Error updating product status: " . $conn->error;
    }
    
    $stmt->close();
}

// Initialize search parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 10;
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Build basic SQL query
$countSql = "SELECT COUNT(*) as total FROM products";
$sql = "SELECT * FROM products";

// Add search condition if search term is provided
if (!empty($search)) {
    $searchTerm = $conn->real_escape_string($search);
    $searchCondition = " WHERE id LIKE '%$searchTerm%' OR 
                        name LIKE '%$searchTerm%' OR 
                        description LIKE '%$searchTerm%' OR
                        status LIKE '%$searchTerm%'";
    $countSql .= $searchCondition;
    $sql .= $searchCondition;
}

// Add order by and pagination
$sql .= " ORDER BY id ASC LIMIT $limit OFFSET $offset";

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
    <title>All Products </title>
    <!-- FAVICON -->
    <link rel="icon" href="img/system/letter-f.png" type="image/png">
    <link href="css/product-list.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <!-- Add SweetAlert2 CSS and JS -->
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
                    <!-- Success Message Display with SweetAlert -->
                    <?php
                    // Check if there's a success message in the session
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
                        
                        // Clear the success message from the session
                        unset($_SESSION['success_message']);
                    }
                    
                    // Check if there's an error message in the session
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
                        
                        // Clear the error message from the session
                        unset($_SESSION['error_message']);
                    }
                    ?>

                    <br>
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h4>All Products</h4>
                    </div>

                    <div class="card product-card mb-4">
                        <div class="card-body">
                            <!-- Premium Filter Bar -->
                            <div class="invoice-filter-bar d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
                                <form method="get" class="d-flex align-items-center gap-2 flex-grow-1">
                                    <div class="position-relative flex-grow-1" style="max-width: 360px;">
                                        <i class="fas fa-search position-absolute" style="top: 50%; left: 12px; transform: translateY(-50%); color: #a0aec0; font-size: 0.85rem;"></i>
                                        <input type="text" name="search" class="form-control ps-4"
                                            placeholder="Search products by name, ID, status..."
                                            value="<?php echo htmlspecialchars($search); ?>">
                                    </div>
                                    <button type="submit" class="btn btn-primary btn-filter">
                                        <i class="fas fa-search me-1"></i> Search
                                    </button>
                                    <?php if (!empty($search)): ?>
                                        <a href="product_list.php" class="btn btn-outline-secondary btn-clear">
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
                                <table class="table table-product" id="productsTable">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Name</th>
                                            <th>Description</th>
                                            <th>Price (LKR)</th>
                                            <th>Price (USD)</th>
                                            <th>Created At</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($row = $result->fetch_assoc()): ?>
                                            <tr>
                                                <td><span class="product-id"><?= htmlspecialchars($row['id']) ?></span></td>
                                                <td><span class="product-name"><?= htmlspecialchars($row['name']) ?></span></td>
                                                <td>
                                                    <span class="product-desc" title="<?= htmlspecialchars($row['description']) ?>">
                                                        <?= htmlspecialchars($row['description']) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php
                                                    if (isset($row['lkr_price'])) {
                                                        echo '<span class="product-price">' . number_format($row['lkr_price'], 2) . '</span><span class="product-currency">LKR</span>';
                                                    } elseif (isset($row['price']) && isset($row['currency']) && $row['currency'] == 'LKR') {
                                                        echo '<span class="product-price">' . number_format($row['price'], 2) . '</span><span class="product-currency">LKR</span>';
                                                    } else {
                                                        echo '<span class="product-currency">N/A</span>';
                                                    }
                                                    ?>
                                                </td>
                                                <td>
                                                    <?php
                                                    if (isset($row['usd_price'])) {
                                                        echo '<span class="product-price">' . number_format($row['usd_price'], 2) . '</span><span class="product-currency">USD</span>';
                                                    } elseif (isset($row['price']) && isset($row['currency']) && $row['currency'] == 'USD') {
                                                        echo '<span class="product-price">' . number_format($row['price'], 2) . '</span><span class="product-currency">USD</span>';
                                                    } else {
                                                        echo '<span class="product-currency">N/A</span>';
                                                    }
                                                    ?>
                                                </td>
                                                <td><span class="product-date"><?= htmlspecialchars($row['created_at']) ?></span></td>
                                                <td>
                                                    <?php
                                                    $status = isset($row['status']) ? $row['status'] : 'active';
                                                    ?>
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
                                                        <a href="edit_product.php?id=<?= htmlspecialchars($row['id']) ?>" class="btn btn-edit" data-bs-toggle="tooltip" data-bs-placement="top" title="Edit Product">
                                                            <i class="fas fa-pen"></i>
                                                        </a>
                                                        
                                                        <?php
                                                        $newStatus = $status == 'active' ? 'inactive' : 'active';
                                                        ?>
                                                        <button type="button" class="btn <?= $status == 'active' ? 'btn-deactivate' : 'btn-activate' ?>"
                                                                data-bs-toggle="tooltip" data-bs-placement="top" title="<?= $status == 'active' ? 'Deactivate' : 'Activate' ?>"
                                                                onclick="confirmStatusChange(<?= $row['id'] ?>, '<?= $newStatus ?>', '<?= htmlspecialchars($row['name']) ?>')">
                                                            <i class="fas <?= $status == 'active' ? 'fa-ban' : 'fa-check' ?>"></i>
                                                        </button>
                                                        
                                                        <form id="toggleForm<?= $row['id'] ?>" action="" method="POST" style="display:none;">
                                                            <input type="hidden" name="product_id" value="<?= $row['id'] ?>">
                                                            <input type="hidden" name="new_status" value="<?= $newStatus ?>">
                                                            <input type="hidden" name="toggle_status" value="1">
                                                        </form>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>

                                            <!-- View Modal -->
                                            <div class="modal fade" id="viewModal<?= $row['id'] ?>" tabindex="-1" aria-labelledby="viewModalLabel" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title" id="viewModalLabel">Product Details</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <p><strong>ID:</strong> <?= htmlspecialchars($row['id']) ?></p>
                                                            <p><strong>Name:</strong> <?= htmlspecialchars($row['name']) ?></p>
                                                            <p><strong>Description:</strong> <?= htmlspecialchars($row['description']) ?></p>
                                                            <p><strong>Price (LKR):</strong> 
                                                                <?php
                                                                if (isset($row['lkr_price'])) {
                                                                    echo number_format($row['lkr_price'], 2) . ' LKR';
                                                                } elseif (isset($row['price']) && isset($row['currency']) && $row['currency'] == 'LKR') {
                                                                    echo number_format($row['price'], 2) . ' LKR';
                                                                } else {
                                                                    echo 'N/A';
                                                                }
                                                                ?>
                                                            </p>
                                                            <p><strong>Price (USD):</strong> 
                                                                <?php
                                                                if (isset($row['usd_price'])) {
                                                                    echo number_format($row['usd_price'], 2) . ' USD';
                                                                } elseif (isset($row['price']) && isset($row['currency']) && $row['currency'] == 'USD') {
                                                                    echo number_format($row['price'], 2) . ' USD';
                                                                } else {
                                                                    echo 'N/A';
                                                                }
                                                                ?>
                                                            </p>
                                                            <p><strong>Created At:</strong> <?= htmlspecialchars($row['created_at']) ?></p>
                                                            <p><strong>Status:</strong> 
                                                                <?php if ($status == 'active'): ?>
                                                                    <span class="badge-soft badge-soft-success">Active</span>
                                                                <?php else: ?>
                                                                    <span class="badge-soft badge-soft-secondary">Inactive</span>
                                                                <?php endif; ?>
                                                            </p>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                            <?php if ($canEditRecords): ?>
                                                            <a href="edit_product.php?id=<?= htmlspecialchars($row['id']) ?>" class="btn btn-primary">
                                                                <i class="fas fa-edit"></i> Edit
                                                            </a>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endwhile; ?>
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
        // Remove Datatable initialization as we moved to server-side pagination
        /*
        window.addEventListener('DOMContentLoaded', event => {
            const datatablesSimple = document.getElementById('productsTable');
            if (datatablesSimple) {
                new simpleDatatables.DataTable(datatablesSimple);
            }
        });
        */
        
        // SweetAlert confirmation function
        function confirmStatusChange(productId, newStatus, productName) {
            const action = newStatus === 'active' ? 'activate' : 'deactivate';
            const actionCapitalized = action.charAt(0).toUpperCase() + action.slice(1);
            
            Swal.fire({
                title: `${actionCapitalized} Product?`,
                text: `Are you sure you want to ${action} "${productName}"?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: newStatus === 'active' ? '#28a745' : '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: `Yes, ${action} it!`,
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    // If confirmed, submit the form
                    document.getElementById(`toggleForm${productId}`).submit();
                    
                    // Show processing message
                    Swal.fire({
                        title: 'Processing...',
                        text: `${actionCapitalized} the product.`,
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