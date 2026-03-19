<?php
// Start session at the very beginning
session_start();

// Check if user is logged in, if not redirect to login page
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

// Only Admin and Moderator can access pending invoices
$current_user_role = isset($_SESSION['role_id']) ? (int)$_SESSION['role_id'] : 0;
if ($current_user_role !== 1 && $current_user_role !== 3) {
    header("Location: invoice_list.php");
    exit();
}

// Process invoice cancellation
if (isset($_POST['cancel_invoice']) && isset($_POST['invoice_id'])) {
    $invoice_id = $_POST['invoice_id'];
    $user_id = $_SESSION['user_id']; // Current logged-in user ID
    
    // Get invoice details for logging
    $invoice_sql = "SELECT c.name FROM invoices i 
                   LEFT JOIN customers c ON i.customer_id = c.customer_id
                   WHERE i.invoice_id = ?";
    $stmt = $conn->prepare($invoice_sql);
    $stmt->bind_param("s", $invoice_id);
    $stmt->execute();
    $invoice_result = $stmt->get_result();
    $invoice_data = $invoice_result->fetch_assoc();
    $customer_name = isset($invoice_data['name']) ? $invoice_data['name'] : 'Unknown Customer';
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // 1. Update the invoice status to 'cancel'
        $update_invoice_sql = "UPDATE invoices SET status = 'cancel' WHERE invoice_id = ?";
        $stmt = $conn->prepare($update_invoice_sql);
        $stmt->bind_param("s", $invoice_id);
        $stmt->execute();
        
        // 2. Update all related invoice items to 'cancel' status
        $update_items_sql = "UPDATE invoice_items SET status = 'cancel' WHERE invoice_id = ?";
        $stmt_items = $conn->prepare($update_items_sql);
        $stmt_items->bind_param("s", $invoice_id);
        $stmt_items->execute();
        
        // 3. Log the action in user_logs table
        $action_type = "cancel_invoice";
        $details = "Invoice ID #$invoice_id for customer ($customer_name) was canceled by user ID #$user_id";
        
        $log_sql = "INSERT INTO user_logs (user_id, action_type, inquiry_id, details, created_at) 
                   VALUES (?, ?, ?, ?, NOW())";
        $log_stmt = $conn->prepare($log_sql);
        $log_stmt->bind_param("isss", $user_id, $action_type, $invoice_id, $details);
        $log_stmt->execute();
        
        // Commit the transaction
        $conn->commit();
        
        // Set success message
        $_SESSION['message'] = "Invoice #$invoice_id has been canceled successfully.";
        $_SESSION['message_type'] = "success";
    } catch (Exception $e) {
        // Rollback the transaction if something fails
        $conn->rollback();
        
        // Set error message
        $_SESSION['message'] = "Failed to cancel invoice. Error: " . $e->getMessage();
        $_SESSION['message_type'] = "danger";
    }
    
    // Build the redirect URL
    $redirect_url = "pending_invoice_list.php";
    
    // Add search parameter if it exists
    if (isset($_POST['search']) && !empty($_POST['search'])) {
        $redirect_url .= "?search=" . urlencode($_POST['search']);
        
        // Add limit and page if they exist
        if (isset($_POST['limit']) && !empty($_POST['limit'])) {
            $redirect_url .= "&limit=" . (int)$_POST['limit'];
        }
        if (isset($_POST['page']) && !empty($_POST['page'])) {
            $redirect_url .= "&page=" . (int)$_POST['page'];
        }
    } 
    // If no search but we have limit or page
    else if ((isset($_POST['limit']) && !empty($_POST['limit'])) || 
             (isset($_POST['page']) && !empty($_POST['page']))) {
        $redirect_url .= "?";
        if (isset($_POST['limit']) && !empty($_POST['limit'])) {
            $redirect_url .= "limit=" . (int)$_POST['limit'];
        }
        if (isset($_POST['page']) && !empty($_POST['page'])) {
            $redirect_url .= (isset($_POST['limit']) && !empty($_POST['limit']) ? "&" : "") . "page=" . (int)$_POST['page'];
        }
    }
    
    // Make sure we're enforcing the redirect properly
    if (headers_sent()) {
        echo "<script>window.location.href='$redirect_url';</script>";
        echo "<noscript><meta http-equiv='refresh' content='0;url=$redirect_url'></noscript>";
    } else {
        header("Location: $redirect_url");
    }
    exit();
}

// Initialize search parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 10;
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Build basic SQL query with JOIN to customers table and filter by pending status
$countSql = "SELECT COUNT(*) as total FROM invoices i 
             LEFT JOIN customers c ON i.customer_id = c.customer_id
             WHERE (i.status = 'pending' OR i.status IS NULL OR i.status = '')";

$sql = "SELECT i.*, c.name as customer_name, c.business_name as customer_business_name
        FROM invoices i 
        LEFT JOIN customers c ON i.customer_id = c.customer_id
        WHERE (i.status = 'pending' OR i.status IS NULL OR i.status = '')";

// Add search condition if search term is provided
if (!empty($search)) {
    $searchTerm = $conn->real_escape_string($search);
    $searchCondition = " AND (
                        i.invoice_id LIKE '%$searchTerm%' OR 
                        c.name LIKE '%$searchTerm%' OR 
                        c.business_name LIKE '%$searchTerm%' OR 
                        i.issue_date LIKE '%$searchTerm%' OR 
                        i.due_date LIKE '%$searchTerm%' OR 
                        i.total_amount LIKE '%$searchTerm%')";
    $countSql .= $searchCondition;
    $sql .= $searchCondition;
}

// Add order by and pagination
$sql .= " ORDER BY i.invoice_id DESC LIMIT $limit OFFSET $offset";

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
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Pending Invoices</title>
    <!-- FAVICON -->
    <link rel="icon" href="img/system/letter-f.png" type="image/png">
    <link href="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/style.min.css" rel="stylesheet" />
    <link href="css/styles.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.3/dist/sweetalert2.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.3/dist/sweetalert2.all.min.js"></script>
</head>

<body class="sb-nav-fixed">
    <?php include 'navbar.php'; ?>
    <div id="layoutSidenav">
        <?php include 'sidebar.php'; ?>

        <div id="layoutSidenav_content">
            <main>
                <div class="container-fluid px-4">
                    <br>
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h4>Pending Invoices</h4>
                    </div>
                    
                    <!-- Display alert messages if any -->
                    <?php if (isset($_SESSION['message'])): ?>
                        <div class="alert alert-<?php echo $_SESSION['message_type']; ?> alert-dismissible fade show" role="alert">
                            <?php 
                                echo $_SESSION['message']; 
                                unset($_SESSION['message']);
                                unset($_SESSION['message_type']);
                            ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                    
                    <div class="card">
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <form method="get" class="d-flex">
                                        <input type="text" name="search" class="form-control me-2"
                                            placeholder="Search invoices..."
                                            value="<?php echo htmlspecialchars($search); ?>">
                                        <button type="submit" class="btn btn-outline-primary">
                                            <i class="fas fa-search"></i>
                                        </button>
                                        <?php if (!empty($search)): ?>
                                            <a href="pending_invoice_list.php" class="btn btn-outline-secondary ms-2">
                                                <i class="fas fa-times"></i> Clear
                                            </a>
                                        <?php endif; ?>
                                        <!-- Preserve other GET parameters -->
                                        <input type="hidden" name="limit" value="<?php echo $limit; ?>">
                                        <input type="hidden" name="page" value="1"> <!-- Reset to page 1 when searching -->
                                    </form>
                                </div>
                                <div class="col-md-6 text-end">
                                    <form method="get" class="d-inline">
                                        <!-- Preserve search term when changing limit -->
                                        <?php if (!empty($search)): ?>
                                            <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                                        <?php endif; ?>
                                        <div class="d-inline-block">
                                            <label>Show</label>
                                            <select name="limit" class="form-select d-inline-block w-auto ms-1"
                                                onchange="this.form.submit()">
                                                <option value="10" <?php if ($limit == 10) echo 'selected'; ?>>10</option>
                                                <option value="25" <?php if ($limit == 25) echo 'selected'; ?>>25</option>
                                                <option value="50" <?php if ($limit == 50) echo 'selected'; ?>>50</option>
                                                <option value="100" <?php if ($limit == 100) echo 'selected'; ?>>100</option>
                                            </select>
                                            <label>entries</label>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            <h5 class="mb-3">Manage Pending Invoices</h5>
                            <?php if (!empty($search)): ?>
                                <div class="alert alert-info">
                                    Showing search results for: <strong><?php echo htmlspecialchars($search); ?></strong>
                                    (<?php echo $totalRows; ?> results found)
                                </div>
                            <?php endif; ?>
                            
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover" id="invoice_table">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Invoice ID</th>
                                            <th>Business Name</th>
                                            <th>Customer Name</th>
                                            <th>Issue Date</th>
                                            <th>Due Date</th>
                                            <th>Total Amount</th>
                                            <th>Status</th>
                                            <th>Pay Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($result && $result->num_rows > 0): ?>
                                            <?php while ($row = $result->fetch_assoc()): ?>
                                                <tr>
                                                    <td><?php echo isset($row['invoice_id']) ? htmlspecialchars($row['invoice_id']) : ''; ?>
                                                    </td>
                                                    <td><?php echo isset($row['customer_business_name']) ? htmlspecialchars($row['customer_business_name']) : ''; ?></td>
                                                    <td>
                                                        <?php
                                                        $customerName = isset($row['customer_name']) ? htmlspecialchars($row['customer_name']) : 'N/A';
                                                        $customerId = isset($row['customer_id']) ? htmlspecialchars($row['customer_id']) : '';
                                                        echo $customerName . ($customerId ? " ($customerId)" : "");
                                                        ?>
                                                    </td>
                                                    <td><?php echo isset($row['issue_date']) ? htmlspecialchars(date('d/m/Y', strtotime($row['issue_date']))) : ''; ?>
                                                    </td>
                                                    <td><?php echo isset($row['due_date']) ? htmlspecialchars(date('d/m/Y', strtotime($row['due_date']))) : ''; ?>
                                                    </td>
                                                    <td>
                                                        <?php
                                                        $amount = isset($row['total_amount']) ? htmlspecialchars(number_format((float) $row['total_amount'], 2)) : '0.00';
                                                        $currency = isset($row['currency']) ? $row['currency'] : 'lkr';
                                                        $currencySymbol = ($currency == 'usd') ? '$' : 'Rs';
                                                        echo $amount . ' (' . $currencySymbol . ')';
                                                        ?>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-warning">Pending</span>
                                                    </td>
                                                    <td>
                                                        <?php
                                                        $payStatus = isset($row['pay_status']) ? $row['pay_status'] : 'unpaid';
                                                        if ($payStatus == 'paid'): ?>
                                                            <span class="badge bg-success">Paid</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-danger">Unpaid</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <div class="btn-group">
                                                            <a href="#" class="btn btn-sm btn-info text-white view-invoice"
                                                                title="View"
                                                                data-id="<?php echo isset($row['invoice_id']) ? $row['invoice_id'] : ''; ?>">
                                                                <i class="fas fa-eye"></i>
                                                            </a>
                                                            <a href="download_invoice.php?id=<?php echo isset($row['invoice_id']) ? $row['invoice_id'] : ''; ?>"
                                                                class="btn btn-sm btn-secondary" title="Download"
                                                                target="_blank">
                                                                <i class="fas fa-download"></i>
                                                            </a>
                                                            <?php if ($payStatus == 'paid'): ?>

                                                            <?php else: ?>
                                                                <a href="#" class="btn btn-sm btn-primary text-white mark-paid"
                                                                    title="Mark as Paid"
                                                                    data-id="<?php echo isset($row['invoice_id']) ? $row['invoice_id'] : ''; ?>">
                                                                    <i class=""></i> Paid
                                                                </a>
                                                                <button type="button" class="btn btn-sm btn-danger cancel-invoice" title="Cancel Invoice"
                                                                    data-id="<?php echo isset($row['invoice_id']) ? $row['invoice_id'] : ''; ?>"
                                                                    data-customer="<?php echo htmlspecialchars($customerName); ?>"
                                                                    data-bs-toggle="modal" data-bs-target="#cancelInvoiceModal">
                                                                    <i class="fas fa-times-circle"></i>
                                                                </button>
                                                            <?php endif; ?>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="8" class="text-center">No pending invoices found</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <?php if ($result && $result->num_rows > 0): ?>
                                        Showing <?php echo ($offset + 1); ?> to
                                        <?php echo min($offset + $limit, $totalRows); ?> of <?php echo $totalRows; ?>
                                        entries
                                    <?php else: ?>
                                        Showing 0 to 0 of 0 entries
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-6">
                                    <nav aria-label="Page navigation">
                                        <ul class="pagination justify-content-end">
                                            <li class="page-item <?php if ($page <= 1) echo 'disabled'; ?>">
                                                <a class="page-link"
                                                    href="?page=<?php echo $page - 1; ?>&limit=<?php echo $limit; ?>&search=<?php echo urlencode($search); ?>">Previous</a>
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
                                                    href="?page=<?php echo $page + 1; ?>&limit=<?php echo $limit; ?>&search=<?php echo urlencode($search); ?>">Next</a>
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

    <!-- Modal for Viewing Invoice -->
    <div class="modal fade" id="viewInvoiceModal" tabindex="-1" aria-labelledby="viewInvoiceModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewInvoiceModalLabel">Invoice Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="invoiceDetails">
                    <!-- Invoice details will be loaded here -->
                    Loading...
                </div>
            </div>
        </div>
    </div>

    <!-- Modal for Marking Invoice as Paid -->
    <div class="modal fade" id="markPaidModal" tabindex="-1" aria-labelledby="markPaidModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="markPaidModalLabel">Payment
                        Sheet</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="markPaidForm" enctype="multipart/form-data">
                        <input type="hidden" name="invoice_id" id="invoice_id">

                        <div class="mb-4">
                            <div class="text-center mb-3">
                                <i class="fas fa-file-invoice fa-3x text-primary"></i>
                            </div>
                            <div class="alert alert-info" role="alert">
                                <i class="fas fa-info-circle me-2"></i>Please upload your payment slip to mark this
                                invoice as paid.
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="payment_slip" class="form-label fw-bold">
                                <i class="fas fa-upload me-2"></i>Upload Payment Slip (Optional)
                            </label>
                            <input type="file" class="form-control" id="payment_slip" name="payment_slip"
                                accept=".jpg,.jpeg,.png,.pdf">
                            <div class="form-text mt-2">
                                <i class="fas fa-file-alt me-1"></i>Supported formats: JPG, JPEG, PNG, PDF
                            </div>
                            <div class="form-text">
                                <i class="fas fa-exclamation-circle me-1"></i>Maximum file size: 2MB
                            </div>
                        </div>

                        <div class="text-center mt-4">
                            <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">
                                <i class="fas fa-times me-1"></i>Cancel
                            </button>
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-check me-1"></i>Mark as Paid
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal for Cancel Invoice Confirmation -->
    <div class="modal fade" id="cancelInvoiceModal" tabindex="-1" aria-labelledby="cancelInvoiceModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="cancelInvoiceModalLabel">Cancel Invoice</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to cancel this invoice? This action cannot be undone.</p>
                    <p><strong>Invoice ID: </strong><span id="cancel_invoice_id"></span></p>
                    <p><strong>Customer: </strong><span id="cancel_customer_name"></span></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <form method="post" id="cancelInvoiceForm">
                        <input type="hidden" name="invoice_id" id="confirm_cancel_invoice_id">
                        <input type="hidden" name="cancel_invoice" value="1">
                        <!-- Add hidden fields to preserve current page state -->
                        <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                        <input type="hidden" name="limit" value="<?php echo $limit; ?>">
                        <input type="hidden" name="page" value="<?php echo $page; ?>">
                        <button type="submit" class="btn btn-danger">Confirm Cancel</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function () {
            // Handle "View" button click
            $('.view-invoice').click(function (e) {
                e.preventDefault(); // Prevent default link behavior

                var invoiceId = $(this).data('id'); // Get the invoice ID

                // Show loading message in the modal
                $('#invoiceDetails').html('Loading...');

                // Fetch invoice details via AJAX
                $.ajax({
                    url: 'download_invoice.php',
                    type: 'GET',
                    data: { id: invoiceId },
                    success: function (response) {
                        // Populate the modal with the fetched data
                        $('#invoiceDetails').html(response);

                        // IMPORTANT: Remove the Print Invoice and Open in New Tab buttons
                        $('#invoiceDetails').find('button:contains("Print Invoice")').remove();
                        $('#invoiceDetails').find('button:contains("Open in New Tab")').remove();

                        // Show the modal
                        $('#viewInvoiceModal').modal('show');
                    },
                    error: function () {
                        $('#invoiceDetails').html('Failed to load invoice details.');
                    }
                });
            });
        });

        // Updated JavaScript for the payment modal
        $(document).ready(function () {
            // Handle "Paid" button click
            $('.mark-paid').click(function (e) {
                e.preventDefault(); // Prevent default link behavior

                var invoiceId = $(this).data('id'); // Get the invoice ID

                // Get additional information about the invoice
                var invoiceRow = $(this).closest('tr');
                var invoiceAmount = invoiceRow.find('td:eq(4)').text().trim();
                var customerName = invoiceRow.find('td:eq(1)').text().trim();

                // Directly set the invoice ID in the form
                $('#invoice_id').val(invoiceId);

                // Optionally, you could update the modal with invoice details
                $('#markPaidModalLabel').html('<i class="fas fa-money-bill-wave me-2"></i>Payment Sheet - Invoice #' + invoiceId);

                // Show the modal
                $('#markPaidModal').modal('show');
            });

            // Handle form submission with validation
            $('#markPaidForm').submit(function (e) {
                e.preventDefault(); // Prevent default form submission

                // Simple validation
                var fileInput = $('#payment_slip')[0];

                if (fileInput.files.length > 0) {
                    var fileSize = fileInput.files[0].size / 1024 / 1024; // in MB
                    if (fileSize > 2) {
                        alert('File size exceeds 2MB. Please choose a smaller file.');
                        return false;
                    }
                }

                // Show loading state
                var submitBtn = $(this).find('button[type="submit"]');
                var originalText = submitBtn.html();
                submitBtn.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...');
                submitBtn.prop('disabled', true);

                var formData = new FormData(this);

                $.ajax({
                    url: 'mark_paid.php',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function (response) {
                        // Show success message
                        Swal.fire({
                            title: 'Success!',
                            text: 'Invoice has been marked as paid successfully.',
                            icon: 'success',
                            confirmButtonText: 'OK'
                        }).then((result) => {
                            $('#markPaidModal').modal('hide');
                            location.reload(); // Reload the page to reflect changes
                        });
                    },
                    error: function (xhr, status, error) {
                        // Reset button state
                        submitBtn.html(originalText);
                        submitBtn.prop('disabled', false);

                        // Show error message
                        Swal.fire({
                            title: 'Error!',
                            text: 'Failed to mark invoice as paid. Please try again.',
                            icon: 'error',
                            confirmButtonText: 'OK'
                        });
                    }
                });
            });

            $('#markPaidModal').on('hidden.bs.modal', function () {
                $('#markPaidForm')[0].reset();
            });

            // Handle Cancel Invoice button click
            $('.cancel-invoice').click(function() {
                var invoiceId = $(this).data('id');
                var customerName = $(this).data('customer');
                $('#cancel_invoice_id').text(invoiceId);
                $('#cancel_customer_name').text(customerName);
                $('#confirm_cancel_invoice_id').val(invoiceId);
            });
            
            // Fade out alert messages after 5 seconds
            setTimeout(function() {
                $(".alert-dismissible").fadeOut("slow");
            }, 5000);
        });
    </script>
    <script src="js/scripts.js"></script>
</body>

</html>