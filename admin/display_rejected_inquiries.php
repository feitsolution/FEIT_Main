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

// Only Admin and Moderator can access rejected inquiries page
if (!$canApproveReject) {
    header("Location: display_inquries.php");
    exit();
}


// Fetch rejected inquiries
$sql = "SELECT * FROM user_form_data WHERE status = 'rejected' ORDER BY created_at DESC";
$result = $conn->query($sql);

if (!$result) {
    die("Query failed: " . $conn->error);
}
// Count total rejected inquiries
$count_query = "SELECT COUNT(*) AS total_rejected FROM user_form_data WHERE status = 'rejected'";
$count_result = $conn->query($count_query);
$total_rejected = 0;

if ($count_result) {
    $row = $count_result->fetch_assoc();
    $total_rejected = $row['total_rejected'];
}

?>


<!DOCTYPE html>
<html lang="en">
<head>
    <?php include('header.php'); ?>
    <title>Rejected Inquiry  </title>
    <!-- FAVICON -->
    <link rel="icon" href="img/system/letter-f.png" type="image/png">
    <link href="css/inquiry-list.css" rel="stylesheet" />
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
            <h4>Rejected Inquiries</h4>
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
                                        <div class="message-content" data-bs-toggle="tooltip" data-bs-placement="top"
                                             title="<?= htmlspecialchars($row['mesage']) ?>">
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
    
    <script>
    $(document).ready(function() {
        // Initialize tooltips
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });

        // Show alert function
        function showAlert(message, type = 'success') {
            const alertHtml = `
                <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            `;
            
            $('.alert-container').append(alertHtml);
            
            setTimeout(() => {
                $('.alert').alert('close');
            }, 3000);
        }

        // Handle status update
        $('.action-button').on('click', function() {
            const button = $(this);
            const row = button.closest('tr');
            const inquiryId = button.data('inquiry-id');
            const action = button.data('action');
            
            // Disable both buttons in the row
            row.find('.action-button').prop('disabled', true);
            
            // Show spinner overlay
            $('.spinner-overlay').css('display', 'flex');
            
            $.ajax({
                url: 'update_status.php',
                type: 'POST',
                data: {
                    id: inquiryId,
                    status: action
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        // Update status badge
                        const statusBadge = row.find('.status-badge');
                        statusBadge.removeClass('badge-soft-warning badge-soft-success badge-soft-danger')
                            .addClass(action === 'approved' ? 'badge-soft-success' : 'badge-soft-danger')
                            .text(action === 'approved' ? 'Approved' : 'Rejected');
                        
                        showAlert(`Inquiry successfully ${action}!`);
                    } else {
                        // Re-enable buttons on error
                        row.find('.action-button').prop('disabled', false);
                        showAlert(response.error || 'Failed to update status.', 'danger');
                    }
                },
                error: function(xhr, status, error) {
                    // Re-enable buttons on error
                    row.find('.action-button').prop('disabled', false);
                    showAlert('An error occurred while processing your request.', 'danger');
                },
                complete: function() {
                    // Hide spinner overlay
                    $('.spinner-overlay').hide();
                }
            });
        });
    });
    </script>
    <script src="js/scripts.js"></script>
</body>
</html>

<?php
$conn->close();
?>