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

// Fetch inquiries data
$sql = "SELECT * FROM user_form_data ORDER BY created_at DESC";
$result = $conn->query($sql);

// Count total inquiries
$countQuery = "SELECT COUNT(*) as total FROM user_form_data";
$countResult = $conn->query($countQuery);
$totalInquiries = $countResult->fetch_assoc()['total'];
?>

<!DOCTYPE html>
<html lang="en" dir="ltr" data-nav-layout="vertical" data-theme-mode="light" data-header-styles="light"
    data-menu-styles="dark" data-toggled="close">

<head>
<?php include('header.php'); ?>
    <?php
    // Include header.php file which contains all the meta tags, CSS links, and other header elements
    include('header.php');
    ?>
    <!-- TITLE -->
    <title> All Inquiry</title>
    <!-- FAVICON -->
    <link rel="icon" href="img/system/letter-f.png" type="image/png">
    <link href="css/inquiry-list.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
</head>

<body class="sb-nav-fixed">

    <?php
    // Include navbar.php file which contains the top navigation bar
    include 'navbar.php';
    ?>

    <div id="layoutSidenav">
        <?php
        // Include sidebar.php file which contains the side navigation menu
        include 'sidebar.php';
        ?>

        <div id="layoutSidenav_content">
            <main>
                <div class="container-fluid px-4">
                    <br>
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h4>All Inquiries</h4>
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
                                            <?php include 'inquiry_rows.php'; ?>
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
                document.addEventListener('DOMContentLoaded', function() {
                    // Initialize Bootstrap tooltips
                    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
                    tooltipTriggerList.map(function (tooltipTriggerEl) {
                        return new bootstrap.Tooltip(tooltipTriggerEl);
                    });
                });
            </script>
        </div>
        <script src="js/scripts.js"></script>
    </div>
</body>

</html>

<?php
$conn->close();
?>