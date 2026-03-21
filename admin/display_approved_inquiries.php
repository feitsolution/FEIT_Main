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

// Only Admin and Moderator can access approved inquiries page
if (!$canApproveReject) {
    header("Location: display_inquries.php");
    exit();
}

// Fetch pending inquiries
$sql = "SELECT * FROM user_form_data WHERE status = 'approved' ORDER BY created_at DESC";
$result = $conn->query($sql);


// Count total approved inquiries
$count_query = "SELECT COUNT(*) AS total_approved FROM user_form_data WHERE status = 'approved'";
$count_result = $conn->query($count_query);
$total_approved = 0;
if ($count_result) {
    $row = $count_result->fetch_assoc();
    $total_approved = $row['total_approved'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<?php include('header.php'); ?>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Approved Inquiry  </title>
    <!-- FAVICON -->
    <link rel="icon" href="img/system/letter-f.png" type="image/png">
    <link href="css/styles.css" rel="stylesheet" />
    <link href="css/inquiry-list.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/style.min.css" rel="stylesheet" />
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
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
                        <h4>Approved Inquiries</h4>
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
                                                    <?php
                                                        $inquiry_id = htmlspecialchars($row['id']);
                                                        $status = htmlspecialchars($row['status']);
                                                        include 'action_buttons.php';
                                                    ?>
                                                </td>
                                            </tr>
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
                document.addEventListener('DOMContentLoaded', function() {
                    // Initialize Bootstrap tooltips
                    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
                    tooltipTriggerList.map(function (tooltipTriggerEl) {
                        return new bootstrap.Tooltip(tooltipTriggerEl);
                    });
                });
            </script>
            <script src="js/scripts.js"></script>
        </body>
    </html>

<?php
$conn->close();
?>
