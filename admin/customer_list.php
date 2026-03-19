<?php
// Start session at the very beginning
session_start();

// Include the database connection file
include 'db_connection.php';
include 'functions.php';

// Get current user's role_id from session
$current_user_role = isset($_SESSION['role_id']) ? (int)$_SESSION['role_id'] : 0;
$canEditRecords = ($current_user_role === 1 || $current_user_role === 3);

// Check if user is logged in, if not redirect to login page
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    if (ob_get_level()) {
        ob_end_clean();
    }
    header("Location: signin.php");
    exit();
}

// Check for success message
$success_message = isset($_SESSION['success_message']) ? $_SESSION['success_message'] : null;
$error_message = isset($_SESSION['error_message']) ? $_SESSION['error_message'] : null;

// Clear the messages from the session
unset($_SESSION['success_message']);
unset($_SESSION['error_message']);

// Initialize search parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Build basic SQL query for counting total rows
$countSql = "SELECT COUNT(*) as total FROM customers";

// Build basic SQL query for fetching customers
$sql = "SELECT * FROM customers";

// Add search condition if search term is provided
if (!empty($search)) {
    $searchTerm = $conn->real_escape_string($search);
    $searchCondition = " WHERE (
                        customer_id LIKE '%$searchTerm%' OR 
                        name LIKE '%$searchTerm%' OR 
                        email LIKE '%$searchTerm%' OR 
                        phone LIKE '%$searchTerm%' OR 
                        address LIKE '%$searchTerm%' OR
                        status LIKE '%$searchTerm%')";
    $countSql .= $searchCondition;
    $sql .= $searchCondition;
}

// Add order by and pagination
$sql .= " ORDER BY customer_id ASC LIMIT $limit OFFSET $offset";

// Execute the count query
$countResult = $conn->query($countSql);
$totalRows = 0;
if ($countResult && $countResult->num_rows > 0) {
    $totalRows = $countResult->fetch_assoc()['total'];
}
$totalPages = ceil($totalRows / $limit);

// Execute the main fetch query
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Customers List</title>
    <!-- FAVICON -->
    <link rel="icon" href="img/system/letter-f.png" type="image/png">
    <link href="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/style.min.css" rel="stylesheet" />
    <link href="css/styles.css" rel="stylesheet" />
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
    <!-- SweetAlert CSS -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.3/dist/sweetalert2.min.css" rel="stylesheet">
    <style>
        .btn-group-compact {
            display: flex;
            flex-direction: row;
            gap: 0.25rem;
        }
        .btn-group-compact .btn {
            padding: 0.2rem 0.4rem;
            font-size: 0.75rem;
        }
        .alert-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1050;
            min-width: 300px;
        }
        /* SweetAlert customizations */
        .swal2-popup {
            font-size: 0.9rem !important;
        }
    </style>
</head>

<body class="sb-nav-fixed">
    <?php include 'navbar.php'; ?>
    <div id="layoutSidenav">
        <?php include 'sidebar.php'; ?>
        <div id="layoutSidenav_content">
            <main>
                <!-- Alert Container for Dynamic and Session Messages -->
                <div class="alert-container" id="alertContainer">
                    <?php 
                    // Display session success message
                    if ($success_message) {
                        echo '<div class="alert alert-success alert-dismissible fade show" role="alert">' . 
                             htmlspecialchars($success_message) . 
                             '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>' . 
                             '</div>';
                    }

                    // Display session error message
                    if ($error_message) {
                        echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">' . 
                             htmlspecialchars($error_message) . 
                             '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>' . 
                             '</div>';
                    }
                    ?>
                </div>

                <div class="container-fluid px-4">
                    <div class="d-flex justify-content-between align-items-center mt-3 mb-4">
                        <h1>Customers</h1>
                    </div>
                    
                    <div class="card mb-4">
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <form method="get" class="d-flex">
                                        <input type="text" name="search" class="form-control me-2"
                                            placeholder="Search customers..."
                                            value="<?php echo htmlspecialchars($search); ?>">
                                        <button type="submit" class="btn btn-outline-primary">
                                            <i class="fas fa-search"></i>
                                        </button>
                                        <?php if (!empty($search)): ?>
                                            <a href="customer_list.php" class="btn btn-outline-secondary ms-2">
                                                <i class="fas fa-times"></i> Clear
                                            </a>
                                        <?php endif; ?>
                                        <input type="hidden" name="limit" value="<?php echo $limit; ?>">
                                        <input type="hidden" name="page" value="1">
                                    </form>
                                </div>
                                <div class="col-md-6 text-end">
                                    <form method="get" id="limitForm">
                                        <?php if (!empty($search)): ?>
                                            <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                                        <?php endif; ?>
                                        <input type="hidden" name="page" value="1">
                                        <div class="d-inline-block">
                                            <label>Show</label>
                                            <select name="limit" class="form-select d-inline-block w-auto ms-1"
                                                onchange="document.getElementById('limitForm').submit()">
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

                            <div class="alert alert-info py-2 mb-3">
                                <strong>Total Customers:</strong> <?= $totalRows ?>
                                <?php if (!empty($search)): ?>
                                    <span class="ms-2">(Search results for: "<?= htmlspecialchars($search) ?>")</span>
                                <?php endif; ?>
                            </div>

                    <div class="table-container">
                     <div class="table-responsive">
                             <table class="table table-striped table-hover">
                                 <thead class="table-dark">
                                     <tr>
                                         <th>Customer ID<br><small class="text-muted">Created At</small></th>
                                         <th>Business Name</th>
                                         <th>Contact Info</th>
                                         <th>Phone</th>
                                         <th>Address</th>
                                         <th>Status</th>
                                         <th>Actions</th>
                                     </tr>
                                 </thead>
                                 <tbody>
                                     <?php while ($row = $result->fetch_assoc()): ?>
                                         <tr id="customer-row-<?= $row['customer_id'] ?>">
                                             <td>
                                                 <?= htmlspecialchars($row['customer_id']) ?>
                                                 <br>
                                                 <small class="text-muted"><?= htmlspecialchars($row['created_at']) ?></small>
                                             </td>
                                             <td><?= htmlspecialchars($row['business_name']) ?></td>
                                             <td>
                                                 <?= htmlspecialchars($row['name']) ?>
                                                 <br>
                                                 <small class="text-muted"><?= htmlspecialchars($row['email']) ?></small>
                                             </td>
                                             <td><?= htmlspecialchars($row['phone']) ?></td>
                                             <td><?= htmlspecialchars($row['address']) ?></td>
                                             <td>
                                                 <span class="customer-status-badge badge <?= $row['status'] == 'Active' ? 'bg-success' : 'bg-secondary' ?>">
                                                     <?= htmlspecialchars($row['status']) ?>
                                                 </span>
                                             </td>
                                             <td>
                                                  <div class="btn-group-compact">
                                                      <?php if ($canEditRecords): ?>
                                                      <a href="edit_customer.php?id=<?= htmlspecialchars($row['customer_id']) ?>" class="btn btn-info btn-sm">Edit</a>
                                                      <?php endif; ?>
                                                      <button class="btn btn-primary btn-sm view-customer-btn" 
                                                              data-customer-id="<?= $row['customer_id'] ?>" 
                                                              data-customer-name="<?= htmlspecialchars($row['name']) ?>"
                                                              data-customer-email="<?= htmlspecialchars($row['email']) ?>"
                                                              data-customer-phone="<?= htmlspecialchars($row['phone']) ?>"
                                                              data-customer-address="<?= htmlspecialchars($row['address']) ?>"
                                                              data-customer-status="<?= htmlspecialchars($row['status']) ?>"
                                                              data-customer-billing="<?= htmlspecialchars($row['billing_date'] ?? 'Not Set') ?>"
                                                              data-customer-created="<?= htmlspecialchars($row['created_at']) ?>">
                                                          View
                                                      </button>
                                                      <?php if ($canEditRecords): ?>
                                                      <button class="btn btn-<?= $row['status'] == 'Active' ? 'danger' : 'success' ?> btn-sm toggle-status-btn" 
                                                              data-customer-id="<?= $row['customer_id'] ?>"
                                                              data-current-status="<?= $row['status'] ?>"
                                                              data-customer-name="<?= htmlspecialchars($row['name']) ?>">
                                                          <?= $row['status'] == 'Active' ? 'Deactivate' : 'Activate' ?>
                                                      </button>
                                                      <?php endif; ?>
                                                  </div>
                                              </td>
                                         </tr>
                                     <?php endwhile; ?>
                                 </tbody>
                             </table>
                        </div>
                    </div>

                        <div class="row mt-3">
                            <div class="col-md-6">
                                Showing <?php echo ($totalRows > 0) ? ($offset + 1) : 0; ?> to
                                <?php echo min($offset + $limit, $totalRows); ?> of <?php echo $totalRows; ?>
                                entries
                            </div>
                            <div class="col-md-6">
                                <nav aria-label="Page navigation">
                                    <ul class="pagination justify-content-end mb-0">
                                        <li class="page-item <?php if ($page <= 1) echo 'disabled'; ?>">
                                            <a class="page-link"
                                                href="?page=<?php echo $page - 1; ?>&limit=<?php echo $limit; ?>&search=<?php echo urlencode($search); ?>">Previous</a>
                                        </li>

                                        <?php
                                        $maxPagesToShow = 5;
                                        $startPage = max(1, min($page - floor($maxPagesToShow / 2), $totalPages - $maxPagesToShow + 1));
                                        $endPage = min($totalPages, $startPage + $maxPagesToShow - 1);

                                        if ($startPage > 1): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?page=1&limit=<?php echo $limit; ?>&search=<?php echo urlencode($search); ?>">1</a>
                                            </li>
                                            <?php if ($startPage > 2): ?>
                                                <li class="page-item disabled"><span class="page-link">...</span></li>
                                            <?php endif; ?>
                                        <?php endif; ?>

                                        <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                                            <li class="page-item <?php if ($page == $i) echo 'active'; ?>">
                                                <a class="page-link"
                                                    href="?page=<?php echo $i; ?>&limit=<?php echo $limit; ?>&search=<?php echo urlencode($search); ?>"><?php echo $i; ?></a>
                                            </li>
                                        <?php endfor; ?>

                                        <?php if ($endPage < $totalPages): ?>
                                            <?php if ($endPage < $totalPages - 1): ?>
                                                <li class="page-item disabled"><span class="page-link">...</span></li>
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
                    </div> <!-- card-body close -->
                </div> <!-- card close -->
            </div> <!-- container-fluid close -->
        </main>
        </div>
    </div>

    <!-- View Customer Modal -->
    <div class="modal fade" id="viewCustomerModal" tabindex="-1" aria-labelledby="viewCustomerModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewCustomerModalLabel">Customer Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="viewCustomerModalBody">
                    <!-- Dynamic content will be inserted here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
    <!-- SweetAlert JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.3/dist/sweetalert2.all.min.js"></script>
    <script src="js/scripts.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Function to show alert messages
        function showAlert(message, type) {
            const alertContainer = document.getElementById('alertContainer');
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
            alertDiv.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            `;
            
            alertContainer.appendChild(alertDiv);

            // Auto-dismiss after 5 seconds
            setTimeout(() => {
                const bsAlert = bootstrap.Alert.getOrCreateInstance(alertDiv);
                bsAlert.close();
            }, 5000);
        }

        // Toast SweetAlert notification
        function showToast(icon, title) {
            const Toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
                didOpen: (toast) => {
                    toast.addEventListener('mouseenter', Swal.stopTimer)
                    toast.addEventListener('mouseleave', Swal.resumeTimer)
                }
            });
            
            Toast.fire({
                icon: icon,
                title: title
            });
        }

        // View Customer Modal Handling
        const viewButtons = document.querySelectorAll('.view-customer-btn');
        const viewCustomerModal = new bootstrap.Modal(document.getElementById('viewCustomerModal'));
        const viewCustomerModalBody = document.getElementById('viewCustomerModalBody');

        viewButtons.forEach(btn => {
            btn.addEventListener('click', function() {
                const customerData = {
                    id: this.getAttribute('data-customer-id'),
                    name: this.getAttribute('data-customer-name'),
                    email: this.getAttribute('data-customer-email'),
                    phone: this.getAttribute('data-customer-phone'),
                    address: this.getAttribute('data-customer-address'),
                    status: this.getAttribute('data-customer-status'),
                    billing: this.getAttribute('data-customer-billing'),
                    created: this.getAttribute('data-customer-created')
                };

                viewCustomerModalBody.innerHTML = `
                    <p><strong>Customer ID:</strong> ${customerData.id}</p>
                    <p><strong>Name:</strong> ${customerData.name}</p>
                    <p><strong>Email:</strong> ${customerData.email}</p>
                    <p><strong>Phone:</strong> ${customerData.phone}</p>
                    <p><strong>Address:</strong> ${customerData.address}</p>
                    <p><strong>Status:</strong> ${customerData.status}</p>
                    <p><strong>Billing Date:</strong> ${customerData.billing !== 'Not Set' && customerData.billing ? customerData.billing + ' of the month' : 'Not Set'}</p>
                    <p><strong>Created At:</strong> ${customerData.created}</p>
                `;

                viewCustomerModal.show();
            });
        });

        // Status Toggle Button Handling with SweetAlert
        const toggleStatusButtons = document.querySelectorAll('.toggle-status-btn');

        toggleStatusButtons.forEach(btn => {
            btn.addEventListener('click', function() {
                const customerId = this.getAttribute('data-customer-id');
                const currentStatus = this.getAttribute('data-current-status');
                const customerName = this.getAttribute('data-customer-name');
                const newStatus = currentStatus === 'Active' ? 'Inactive' : 'Active';
                const actionText = currentStatus === 'Active' ? 'deactivate' : 'activate';
                const actionColor = currentStatus === 'Active' ? '#d33' : '#28a745';
                
                // SweetAlert confirmation before status change
                Swal.fire({
                    title: `Are you sure?`,
                    html: `You are about to <strong>${actionText}</strong> customer: <br><strong>${customerName}</strong>`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: actionColor,
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: `Yes, ${actionText} customer!`,
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Show loading state
                        Swal.fire({
                            title: 'Processing...',
                            html: `Updating customer status to ${newStatus}`,
                            allowOutsideClick: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });
                        
                        // AJAX call to update status
                        fetch('toggle_customer_status.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: `customer_id=${customerId}&action=${actionText}`
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                // Update button and badge
                                const customerRow = document.getElementById(`customer-row-${customerId}`);
                                const statusBadge = customerRow.querySelector('.customer-status-badge');
                                const toggleButton = customerRow.querySelector('.toggle-status-btn');

                                if (data.new_status === 'Active') {
                                    statusBadge.classList.remove('bg-secondary');
                                    statusBadge.classList.add('bg-success');
                                    toggleButton.classList.remove('btn-success');
                                    toggleButton.classList.add('btn-danger');
                                    toggleButton.textContent = 'Deactivate';
                                } else {
                                    statusBadge.classList.remove('bg-success');
                                    statusBadge.classList.add('bg-secondary');
                                    toggleButton.classList.remove('btn-danger');
                                    toggleButton.classList.add('btn-success');
                                    toggleButton.textContent = 'Activate';
                                }

                                statusBadge.textContent = data.new_status;
                                toggleButton.setAttribute('data-current-status', data.new_status);

                                // Show success message
                                Swal.fire({
                                    title: 'Success!',
                                    text: `Customer ${customerName} has been ${data.new_status === 'Active' ? 'activated' : 'deactivated'} successfully.`,
                                    icon: 'success',
                                    confirmButtonColor: '#4CAF50'
                                });
                            } else {
                                // Show error message
                                Swal.fire({
                                    title: 'Error!',
                                    text: data.message || 'Failed to update customer status',
                                    icon: 'error',
                                    confirmButtonColor: '#d33'
                                });
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            Swal.fire({
                                title: 'Error!',
                                text: 'An error occurred while updating customer status',
                                icon: 'error',
                                confirmButtonColor: '#d33'
                            });
                        });
                    }
                });
            });
        });

        // Optional: Auto-dismiss alerts after 5 seconds
        const alerts = document.querySelectorAll('.alert-dismissible');
        alerts.forEach(alert => {
            setTimeout(() => {
                const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
                bsAlert.close();
            }, 5000);
        });
    });
    </script>
</body>
</html>

<?php
$conn->close();
?>