<?php
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
$canApproveReject = ($current_user_role === 1 || $current_user_role === 3);

// Only Admin and Moderator can access pending inquiries page
if (!$canApproveReject) {
    header("Location: display_inquries.php");
    exit();
}

// Fetch pending inquiries
$sql = "SELECT * FROM user_form_data WHERE status = 'pending' ORDER BY created_at DESC";
$result = $conn->query($sql);

// Count total pending inquiries
$pendingCountQuery = "SELECT COUNT(*) as total_pending FROM user_form_data WHERE status = 'pending'";
$pendingCountResult = $conn->query($pendingCountQuery);
$totalPendingInquiries = $pendingCountResult->fetch_assoc()['total_pending'];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Pending Inquiry </title>
    <!-- FAVICON -->
    <link rel="icon" href="img/system/letter-f.png" type="image/png">
    <link href="css/styles.css" rel="stylesheet" />
    <link href="css/inquiry-list.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/style.min.css" rel="stylesheet" />
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
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
                    <h4>Pending Inquiries</h4>
                </div>

                <div class="card inquiry-card">
                    <div class="card-body">
                        <div class="table-responsive" style="position: relative;">
                            <div class="spinner-overlay">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                            </div>
                            <table class="table table-inquiry">
                                <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Message</th>
                                    <th>Company</th>
                                    <th>Created At</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                                </thead>
                                <tbody>
                                    <?php while ($row = $result->fetch_assoc()): ?>
                                        <tr data-inquiry-id="<?= htmlspecialchars($row['id']) ?>">
                                            <td>
                                                <span class="inquiry-name"><?= htmlspecialchars($row['first_name']) . ' ' . htmlspecialchars($row['last_name']) ?></span>
                                            </td>
                                            <td><?= htmlspecialchars($row['email']) ?></td>
                                            <td class="message-cell">
                                                <div class="message-content" data-bs-toggle="tooltip"
                                                    data-bs-placement="top" title="<?= htmlspecialchars($row['mesage']) ?>">
                                                    <?= htmlspecialchars($row['mesage']) ?>
                                                </div>
                                            </td>
                                            <td><?= htmlspecialchars($row['company']) ?></td>
                                            <td><?= htmlspecialchars($row['created_at']) ?></td>
                                            <td>
                                                <?php if ($row['status'] === 'approved'): ?>
                                                    <span class="status-badge badge-soft badge-soft-success">Approved</span>
                                                <?php elseif ($row['status'] === 'rejected'): ?>
                                                    <span class="status-badge badge-soft badge-soft-danger">Rejected</span>
                                                <?php else: ?>
                                                    <span class="status-badge badge-soft badge-soft-warning">Pending</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="inquiry-action-btns d-flex gap-1">
                                                    <button type="button" class="btn btn-view"
                                                        title="View Details"
                                                        data-bs-toggle="modal" data-bs-target="#viewModal<?= $row['id'] ?>">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-approve action-button"
                                                        data-bs-toggle="tooltip" data-bs-placement="top" title="Approve"
                                                        data-action="approved"
                                                        data-inquiry-id="<?= htmlspecialchars($row['id']) ?>"
                                                        <?= $row['status'] === 'approved' || $row['status'] === 'rejected' ? 'disabled' : '' ?>>
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-reject action-button"
                                                        data-bs-toggle="tooltip" data-bs-placement="top" title="Reject"
                                                        data-action="rejected"
                                                        data-inquiry-id="<?= htmlspecialchars($row['id']) ?>"
                                                        <?= $row['status'] === 'approved' || $row['status'] === 'rejected' ? 'disabled' : '' ?>>
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                        <!-- View Modal -->
                                        <?php include 'modal.php'; ?>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <!-- Required scripts -->
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
        <!-- SweetAlert2 JS -->
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>

        <script>
            $(document).ready(function () {
                // Initialize tooltips
                const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
                tooltipTriggerList.map(function (tooltipTriggerEl) {
                    return new bootstrap.Tooltip(tooltipTriggerEl);
                });

                // Replace standard action-button click with SweetAlert2
                $('.action-button').on('click', function () {
                    const button = $(this);
                    const inquiryId = button.data('inquiry-id');
                    const action = button.data('action');
                    const row = button.closest('tr');
                    
                    // Configure SweetAlert based on action type
                    let title, text, confirmButtonText, confirmButtonColor;
                    
                    if (action === 'approved') {
                        title = 'Approve Inquiry';
                        text = 'Are you sure you want to approve this inquiry?';
                        confirmButtonText = 'Yes, Approve it!';
                        confirmButtonColor = '#28a745'; // Green
                    } else {
                        title = 'Reject Inquiry';
                        text = 'Are you sure you want to reject this inquiry?';
                        confirmButtonText = 'Yes, Reject it!';
                        confirmButtonColor = '#dc3545'; // Red
                    }
                    
                    // Show SweetAlert2 confirmation dialog
                    Swal.fire({
                        title: title,
                        text: text,
                        icon: action === 'approved' ? 'success' : 'warning',
                        showCancelButton: true,
                        confirmButtonColor: confirmButtonColor,
                        cancelButtonColor: '#6c757d', // Gray
                        confirmButtonText: confirmButtonText,
                        cancelButtonText: 'Cancel'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            // Show spinner overlay
                            $('.spinner-overlay').css('display', 'flex');
                            
                            // Disable both buttons in the row
                            row.find('.action-button').prop('disabled', true);
                            
                            // Proceed with the AJAX request
                            $.ajax({
                                url: 'update_status.php',
                                type: 'POST',
                                data: {
                                    id: inquiryId,
                                    status: action
                                },
                                dataType: 'json',
                                success: function (response) {
                                    if (response.success) {
                                        // Update status badge
                                        const statusBadge = row.find('.status-badge');
                                        statusBadge.removeClass('badge-soft-warning badge-soft-success badge-soft-danger')
                                            .addClass(action === 'approved' ? 'badge-soft-success' : 'badge-soft-danger')
                                            .text(action === 'approved' ? 'Approved' : 'Rejected');

                                        // Show success message with SweetAlert2
                                        Swal.fire({
                                            title: 'Success!',
                                            text: `Inquiry successfully ${action}!`,
                                            icon: 'success',
                                            timer: 2000,
                                            showConfirmButton: false
                                        });
                                        
                                        // Optional: Remove the row or fade it out after a delay (for pending view)
                                        setTimeout(() => {
                                            row.fadeOut(500, function() {
                                                $(this).remove();
                                                
                                                // Update pending count
                                                const currentCount = parseInt($('.alert-warning strong').text().split(':')[1].trim());
                                                $('.alert-warning strong').text(`Total Pending Inquiries: ${currentCount - 1}`);
                                            });
                                        }, 2000);
                                    } else {
                                        // Re-enable buttons on error
                                        row.find('.action-button').prop('disabled', false);
                                        
                                        // Show error message with SweetAlert2
                                        Swal.fire({
                                            title: 'Error!',
                                            text: response.error || 'Failed to update status.',
                                            icon: 'error'
                                        });
                                    }
                                },
                                error: function (xhr, status, error) {
                                    // Re-enable buttons on error
                                    row.find('.action-button').prop('disabled', false);
                                    
                                    // Show error message with SweetAlert2
                                    Swal.fire({
                                        title: 'Error!',
                                        text: 'An error occurred while processing your request.',
                                        icon: 'error'
                                    });
                                },
                                complete: function () {
                                    // Hide spinner overlay
                                    $('.spinner-overlay').hide();
                                }
                            });
                        }
                    });
                });
            });
        </script>
    </div>
</div>
    <script src="js/scripts.js"></script>
</body>

</html>

<?php
$conn->close();
?>