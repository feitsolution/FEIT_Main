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

// Only Admin and Moderator can access complete invoices
$current_user_role = isset($_SESSION['role_id']) ? (int)$_SESSION['role_id'] : 0;
if ($current_user_role !== 1 && $current_user_role !== 3) {
    header("Location: invoice_list.php");
    exit();
}

// Initialize search parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Build basic SQL query with JOIN to customers table and filter by pending status
$countSql = "SELECT COUNT(*) as total FROM invoices i 
             LEFT JOIN customers c ON i.customer_id = c.customer_id
             LEFT JOIN payments p ON i.invoice_id = p.invoice_id
             LEFT JOIN users u ON p.pay_by = u.id
             WHERE i.status = 'done'";
             
$sql = "SELECT i.*, c.name as customer_name, c.business_name as customer_business_name,
               p.payment_id, p.amount_paid, p.payment_method, p.payment_date, p.pay_by,
               u.name as paid_by_name
        FROM invoices i 
        LEFT JOIN customers c ON i.customer_id = c.customer_id
        LEFT JOIN payments p ON i.invoice_id = p.invoice_id
        LEFT JOIN users u ON p.pay_by = u.id
        WHERE i.status = 'done'";

// Add search condition if search term is provided
if (!empty($search)) {
    $searchTerm = $conn->real_escape_string($search);
    $searchCondition = " AND (
                        i.invoice_id LIKE '%$searchTerm%' OR 
                        c.name LIKE '%$searchTerm%' OR 
                        c.business_name LIKE '%$searchTerm%' OR 
                        i.issue_date LIKE '%$searchTerm%' OR 
                        i.due_date LIKE '%$searchTerm%' OR 
                        i.total_amount LIKE '%$searchTerm%' OR
                        i.pay_status LIKE '%$searchTerm%' OR
                        p.payment_method LIKE '%$searchTerm%' OR
                        u.name LIKE '%$searchTerm%')";
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
    <?php include('header.php'); ?>
    <title>Complete Invoices</title>
    <link href="css/invoice-list.css" rel="stylesheet" />
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
                        <h4>Complete Invoices</h4>
                    </div>
                    <div class="card invoice-card">
                        <div class="card-body">
                            <!-- Premium Filter Bar -->
                            <div class="invoice-filter-bar d-flex align-items-center justify-content-between flex-wrap gap-2">
                                <form method="get" class="d-flex align-items-center gap-2 flex-grow-1" id="searchForm">
                                    <div class="position-relative flex-grow-1" style="max-width: 360px;">
                                        <i class="fas fa-search position-absolute" style="top: 50%; left: 12px; transform: translateY(-50%); color: #a0aec0; font-size: 0.85rem;"></i>
                                        <input type="text" name="search" class="form-control ps-4"
                                            placeholder="Search by invoice ID, customer, date..."
                                            value="<?php echo htmlspecialchars($search); ?>">
                                    </div>
                                    <button type="submit" class="btn btn-primary btn-filter">
                                        <i class="fas fa-search me-1"></i> Search
                                    </button>
                                    <?php if (!empty($search)): ?>
                                        <a href="complete_invoice_list.php" class="btn btn-outline-secondary btn-clear">
                                            <i class="fas fa-times me-1"></i> Clear
                                        </a>
                                    <?php endif; ?>
                                    <input type="hidden" name="limit" value="<?php echo $limit; ?>">
                                    <input type="hidden" name="page" value="1">
                                </form>
                                <form method="get" id="limitForm" class="d-flex align-items-center gap-2">
                                    <?php if (!empty($search)): ?>
                                        <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                                    <?php endif; ?>
                                    <input type="hidden" name="page" value="1">
                                    <span class="entries-label">Show</span>
                                    <select name="limit" class="form-select" style="width: 80px;" onchange="document.getElementById('limitForm').submit()">
                                        <option value="10" <?php if ($limit == 10) echo 'selected'; ?>>10</option>
                                        <option value="25" <?php if ($limit == 25) echo 'selected'; ?>>25</option>
                                        <option value="50" <?php if ($limit == 50) echo 'selected'; ?>>50</option>
                                        <option value="100" <?php if ($limit == 100) echo 'selected'; ?>>100</option>
                                    </select>
                                    <span class="entries-label">entries</span>
                                </form>
                            </div>

                            <?php if (!empty($search)): ?>
                                <div class="search-results-alert">
                                    <i class="fas fa-filter me-1"></i>
                                    Showing results for: <strong><?php echo htmlspecialchars($search); ?></strong>
                                    — <strong><?php echo $totalRows; ?></strong> found
                                </div>
                            <?php endif; ?>
                            
                            <div class="table-responsive">
                                <table class="table table-invoice" id="invoice_table">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Invoice ID</th>
                                            <th>Customer</th>
                                            <th>Issue Date</th>
                                            <th>Due Date</th>
                                            <th>Amount</th>
                                            <th>Status</th>
                                            <th>Processed By</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($result && $result->num_rows > 0): ?>
                                            <?php while ($row = $result->fetch_assoc()): ?>
                                                <tr>
                                                    <td><?php echo isset($row['invoice_id']) ? htmlspecialchars($row['invoice_id']) : ''; ?>
                                                    </td>
                                                    <td>
                                                        <?php
                                                        $businessName = isset($row['customer_business_name']) ? htmlspecialchars($row['customer_business_name']) : '';
                                                        $customerName = isset($row['customer_name']) ? htmlspecialchars($row['customer_name']) : 'N/A';
                                                        $customerId = isset($row['customer_id']) ? htmlspecialchars($row['customer_id']) : '';

                                                        if ($businessName) {
                                                            echo '<div class="fw-semibold">' . $businessName . '</div>';
                                                            echo '<div class="text-muted" style="font-size: 0.82rem;">' . $customerName;
                                                            if ($customerId) echo ' <span class="customer-id">(' . $customerId . ')</span>';
                                                            echo '</div>';
                                                        } else {
                                                            echo '<div class="fw-semibold">' . $customerName . '</div>';
                                                            if ($customerId) echo '<div class="customer-id">(' . $customerId . ')</div>';
                                                        }
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
                                                        $payStatus = isset($row['pay_status']) ? $row['pay_status'] : 'unpaid';

                                                        echo '<div class="amount-text">' . $amount . ' <span class="currency-symbol">(' . $currencySymbol . ')</span></div>';

                                                        if ($payStatus == 'paid'): ?>
                                                            <span class="badge-soft badge-soft-success mt-1">Paid</span>
                                                        <?php else: ?>
                                                            <span class="badge-soft badge-soft-danger mt-1">Unpaid</span>
                                                        <?php endif;
                                                        ?>
                                                    </td>
                                                    <td>
                                                        <span class="badge-soft badge-soft-success">Complete</span>
                                                    </td>
                                                    <td>
                                                        <?php 
                                                        if (isset($row['pay_by']) && isset($row['paid_by_name'])) {
                                                            echo htmlspecialchars($row['paid_by_name']) . ' (' . htmlspecialchars($row['pay_by']) . ')';
                                                        } else {
                                                            echo 'N/A';
                                                        }
                                                        ?>
                                                    </td>
                                                    <td>
                                                        <div class="action-btn-group d-flex gap-1">
                                                            <a href="#" class="btn btn-view view-invoice"
                                                                data-bs-toggle="tooltip" data-bs-placement="top" title="View Invoice"
                                                                data-id="<?php echo isset($row['invoice_id']) ? $row['invoice_id'] : ''; ?>"
                                                                data-paystatus="<?php echo $payStatus; ?>">
                                                                <i class="fas fa-eye"></i>
                                                            </a>
                                                            <a href="download_invoice.php?id=<?php echo isset($row['invoice_id']) ? $row['invoice_id'] : ''; ?>"
                                                                class="btn btn-download"
                                                                data-bs-toggle="tooltip" data-bs-placement="top" title="Download Invoice"
                                                                target="_blank">
                                                                <i class="fas fa-download"></i>
                                                            </a>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="8" class="text-center">No complete invoices found</td>
                                            </tr>
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
                                                href="?page=<?php echo ($page - 1); ?>&limit=<?php echo $limit; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">
                                                <i class="fas fa-chevron-left"></i>
                                            </a>
                                        </li>

                                        <?php
                                        // Display a limited number of page links
                                        $maxPagesToShow = 5;
                                        $startPage = max(1, min($page - floor($maxPagesToShow / 2), $totalPages - $maxPagesToShow + 1));
                                        $endPage = min($totalPages, $startPage + $maxPagesToShow - 1);
                                        
                                        // Ensure startPage is at least 1
                                        $startPage = max(1, $startPage);
                                        
                                        // Show "..." before the first page link if needed
                                        if ($startPage > 1): ?>
                                            <li class="page-item">
                                                <a class="page-link"
                                                   href="?page=1&limit=<?php echo $limit; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">1</a>
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
                                                   href="?page=<?php echo $i; ?>&limit=<?php echo $limit; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>"><?php echo $i; ?></a>
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
                                                <a class="page-link"
                                                   href="?page=<?php echo $totalPages; ?>&limit=<?php echo $limit; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>"><?php echo $totalPages; ?></a>
                                            </li>
                                        <?php endif; ?>

                                        <li class="page-item <?php if ($page >= $totalPages) echo 'disabled'; ?>">
                                            <a class="page-link"
                                               href="?page=<?php echo ($page + 1); ?>&limit=<?php echo $limit; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">
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
                <!-- Payment information section -->
                <div class="modal-body border-top" id="paymentInfoSection" style="display:none;">
                    <h5><i class="fas fa-money-bill-wave me-2"></i>Payment Information</h5>
                    <div id="paymentDetails" class="mt-3">
                        <!-- Payment details will be loaded here -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal for Marking Invoice as Paid -->
    <div class="modal fade" id="markPaidModal" tabindex="-1" aria-labelledby="markPaidModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="markPaidModalLabel"> Payment Sheet</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="markPaidForm" enctype="multipart/form-data">
                        <input type="hidden" name="invoice_id" id="invoice_id">

                        <div class="mb-3">
                            <label for="payment_slip" class="form-label">Please Select Your Payment Slip</label>
                            <input type="file" class="form-control" id="payment_slip" name="payment_slip"
                                accept=".jpg,.png,.pdf">
                            <small class="form-text text-muted">Supported File formats: .JPG / .PNG / .PDF (Optional)</small>
                        </div>

                        <div class="text-center mt-4">
                            <button type="submit" class="btn btn-success">Paid</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function () {
            // Initialize Bootstrap tooltips
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });

            // Handle "View" button click
            $('.view-invoice').click(function (e) {
                e.preventDefault(); // Prevent default link behavior

                var invoiceId = $(this).data('id'); // Get the invoice ID
                var payStatus = $(this).data('paystatus'); // Get the payment status

                // Show loading message in the modal
                $('#invoiceDetails').html('Loading...');
                
                // Hide payment info section initially
                $('#paymentInfoSection').hide();
                $('#paymentDetails').html('');

                // Fetch invoice details via AJAX
                $.ajax({
                    url: 'download_invoice.php',
                    type: 'GET',
                    data: { 
                        id: invoiceId,
                        format: 'html' // Request HTML format instead of PDF download
                    },
                    success: function (response) {
                        // Populate the modal with the fetched data
                        $('#invoiceDetails').html(response);

                        // IMPORTANT: Remove the Print Invoice and Open in New Tab buttons
                        $('#invoiceDetails').find('button:contains("Print Invoice")').remove();
                        $('#invoiceDetails').find('button:contains("Open in New Tab")').remove();
                        $('#invoiceDetails').find('button:contains("Download")').remove();
                        
                        // Fetch payment details for this invoice
                        fetchPaymentDetails(invoiceId, payStatus);

                        // Show the modal
                        $('#viewInvoiceModal').modal('show');
                    },
                    error: function () {
                        $('#invoiceDetails').html('Failed to load invoice details.');
                    }
                });
            });
            
            // Function to fetch payment details
            function fetchPaymentDetails(invoiceId, payStatus) {
                if (payStatus === 'paid') {
                    $.ajax({
                        url: 'get_payment_details.php',
                        type: 'GET',
                        data: { invoice_id: invoiceId },
                        success: function(data) {
                            if (data.success) {
                                // Create payment details HTML
                                var html = '<div class="card">' +
                                           '<div class="card-body">' +
                                           '<div class="row">' +
                                           '<div class="col-md-6">' +
                                           '<p><strong>Payment Method:</strong> ' + data.payment_method + '</p>' +
                                           '<p><strong>Amount Paid:</strong> ' + data.amount_paid + '</p>' +
                                           '</div>' +
                                           '<div class="col-md-6">' +
                                           '<p><strong>Payment Date:</strong> ' + data.payment_date + '</p>' +
                                           '<p><strong>Processed By:</strong> ' + data.processed_by + '</p>' +
                                           '</div>' +
                                           '</div>';
                                
                                // Add payment slip if available
                                if (data.slip) {
                                    html += '<div class="text-center mt-3">' +
                                            '<p><strong>Payment Slip:</strong></p>' +
                                            '<a href="uploads/payments/' + data.slip + '" target="_blank">' +
                                            '<img src="uploads/payments/' + data.slip + '" class="img-fluid" style="max-height: 200px;">' +
                                            '</a>' +
                                            '</div>';
                                }
                                
                                html += '</div></div>';
                                
                                // Show the payment section
                                $('#paymentDetails').html(html);
                                $('#paymentInfoSection').show();
                            } else {
                                // If there's an error, show an error message
                                $('#paymentDetails').html('<div class="alert alert-warning">No payment details found for this invoice.</div>');
                                $('#paymentInfoSection').show();
                            }
                        },
                        error: function() {
                            // If AJAX fails, show an error
                            $('#paymentDetails').html('<div class="alert alert-danger">Failed to load payment details.</div>');
                            $('#paymentInfoSection').show();
                        }
                    });
                } else {
                    // If invoice is not paid, show appropriate message
                    $('#paymentDetails').html('<div class="alert alert-info">This invoice has not been paid yet.</div>');
                    $('#paymentInfoSection').show();
                }
            }

            // Handle "Paid" button click
            $('.mark-paid').click(function (e) {
                e.preventDefault(); // Prevent default link behavior

                var invoiceId = $(this).data('id'); // Get the invoice ID

                // Directly set the invoice ID in the form without fetching other details
                $('#invoice_id').val(invoiceId);

                // Show the modal
                $('#markPaidModal').modal('show');
            });

            // Handle form submission
            $('#markPaidForm').submit(function (e) {
                e.preventDefault(); // Prevent default form submission

                var formData = new FormData(this);

                $.ajax({
                    url: 'mark_paid.php',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function (response) {
                        alert('Invoice marked as paid successfully.');
                        $('#markPaidModal').modal('hide');
                        location.reload(); // Reload the page to reflect changes
                    },
                    error: function () {
                        alert('Failed to mark invoice as paid.');
                    }
                });
            });
        });
    </script>
    <script src="js/scripts.js"></script>
</body>

</html>