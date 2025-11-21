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

// Include helper functions
include 'functions.php';

// Initialize statistics with default values
$stats = [
    'total_users' => 0,
    'all_inquiries' => 0,
    'pending_inquiries' => 0,
    'approved_inquiries' => 0,
    'rejected_inquiries' => 0,
    'total_customers' => 0,
    'total_products' => 0,
    'total_invoices' => 0,
    'complete_invoices' => 0,
    'pending_invoices' => 0
];

// Helper function to safely query the database
function safeQuery($conn, $query)
{
    try {
        $result = $conn->query($query);
        if ($result) {
            $row = $result->fetch_assoc();
            return isset($row['count']) ? $row['count'] : 0;
        }
    } catch (Exception $e) {
        // Table doesn't exist or other error
        return 0;
    }
    return 0;
}

// Safely fetch all statistics
$stats['total_users'] = safeQuery($conn, "SELECT COUNT(*) as count FROM users");

// Check if user_form_data table exists before querying
$tableExists = $conn->query("SHOW TABLES LIKE 'user_form_data'");
if ($tableExists && $tableExists->num_rows > 0) {
    $stats['all_inquiries'] = safeQuery($conn, "SELECT COUNT(*) as count FROM user_form_data");
    $stats['pending_inquiries'] = safeQuery($conn, "SELECT COUNT(*) as count FROM user_form_data WHERE status = 'pending'");
    $stats['approved_inquiries'] = safeQuery($conn, "SELECT COUNT(*) as count FROM user_form_data WHERE status = 'approved'");
    $stats['rejected_inquiries'] = safeQuery($conn, "SELECT COUNT(*) as count FROM user_form_data WHERE status = 'rejected'");
}
// Check if customers table exists
$tableExists = $conn->query("SHOW TABLES LIKE 'customers'");
if ($tableExists && $tableExists->num_rows > 0) {
    $stats['total_customers'] = safeQuery($conn, "SELECT COUNT(*) as count FROM customers");
}

// Check if products table exists
$tableExists = $conn->query("SHOW TABLES LIKE 'products'");
if ($tableExists && $tableExists->num_rows > 0) {
    $stats['total_products'] = safeQuery($conn, "SELECT COUNT(*) as count FROM products");
}

// Check if invoices table exists
$tableExists = $conn->query("SHOW TABLES LIKE 'invoices'");
if ($tableExists && $tableExists->num_rows > 0) {
    $stats['total_invoices'] = safeQuery($conn, "SELECT COUNT(*) as count FROM invoices");
    // Fix: Updated to use 'done' status instead of 'paid' for completed invoices
    $stats['complete_invoices'] = safeQuery($conn, "SELECT COUNT(*) as count FROM invoices WHERE status = 'done'");
    // Added counter for pending invoices
    $stats['pending_invoices'] = safeQuery($conn, "SELECT COUNT(*) as count FROM invoices WHERE status = 'pending'");
      // Added counter for cancel invoices
    $stats['cancel_invoices'] = safeQuery($conn, "SELECT COUNT(*) as count FROM invoices WHERE status = 'cancel'");
}
?>

<!DOCTYPE html>
<html lang="en" dir="ltr" data-nav-layout="vertical" data-theme-mode="light" data-header-styles="light" data-menu-styles="dark" data-toggled="close">

<head>
    <?php
    // Include header.php file which contains all the meta tags, CSS links, and other header elements
    include('header.php');
    ?>
    <!-- TITLE -->
	<title> Admin Dashboard</title>          
    
<!-- FAVICON -->
    <link rel="icon" href="img/system/letter-f.png" type="image/png">
    	

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
                    <h1 class="mt-4">Dashboard</h1>
                    <ol class="breadcrumb mb-4">
                        <!--<li class="breadcrumb-item active"> Dashboard</li>-->
                    </ol>

                    <div class="stat-grid">
                        <!-- Total Users -->
                        <div class="stat-card">
                            <div class="stat-card-icon bg-primary">
                                <i class="fas fa-users"></i>
                            </div>
                            <div class="stat-card-header">
                                <h3>Total Users</h3>
                                <div class="stat-value"><?= $stats['total_users'] ?></div>
                            </div>
                            <a href="users.php">View Details <i class="fas fa-arrow-right"></i></a>
                        </div>

                        <!-- All Inquiries -->
                        <div class="stat-card">
                            <div class="stat-card-icon bg-info">
                                <i class="fas fa-question-circle"></i>
                            </div>
                            <div class="stat-card-header">
                                <h3>All Inquiries</h3>
                                <div class="stat-value"><?= $stats['all_inquiries'] ?></div>
                            </div>
                            <a href="display_inquries.php">View Details <i class="fas fa-arrow-right"></i></a>
                        </div>

                        <!-- Pending Inquiries -->
                        <div class="stat-card">
                            <div class="stat-card-icon bg-warning">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="stat-card-header">
                                <h3>Pending Inquiries</h3>
                                <div class="stat-value"><?= $stats['pending_inquiries'] ?></div>
                            </div>
                            <a href="display_pending_inquiries.php">View Details <i class="fas fa-arrow-right"></i></a>
                        </div>

                        <!-- Approved Inquiries -->
                        <div class="stat-card">
                            <div class="stat-card-icon bg-success">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div class="stat-card-header">
                                <h3>Approved Inquiries</h3>
                                <div class="stat-value"><?= $stats['approved_inquiries'] ?></div>
                            </div>
                            <a href="display_approved_inquiries.php">View Details <i class="fas fa-arrow-right"></i></a>
                        </div>

                        <!-- Rejected Inquiries -->
                        <div class="stat-card">
                            <div class="stat-card-icon bg-danger">
                                <i class="fas fa-times-circle"></i>
                            </div>
                            <div class="stat-card-header">
                                <h3>Rejected Inquiries</h3>
                                <div class="stat-value"><?= $stats['rejected_inquiries'] ?></div>
                            </div>
                            <a href="display_rejected_inquiries.php">View Details <i class="fas fa-arrow-right"></i></a>
                        </div>

                        <!-- Total Customers -->
                        <div class="stat-card">
                            <div class="stat-card-icon bg-secondary">
                                <i class="fas fa-user-tie"></i>
                            </div>
                            <div class="stat-card-header">
                                <h3>Total Customers</h3>
                                <div class="stat-value"><?= $stats['total_customers'] ?></div>
                            </div>
                            <a href="customer_list.php">View Details <i class="fas fa-arrow-right"></i></a>
                        </div>

                        <!-- Total Products -->
                        <div class="stat-card">
                            <div class="stat-card-icon bg-primary">
                                <i class="fas fa-box"></i>
                            </div>
                            <div class="stat-card-header">
                                <h3>Total Products</h3>
                                <div class="stat-value"><?= $stats['total_products'] ?></div>
                            </div>
                            <a href="product_list.php">View Details <i class="fas fa-arrow-right"></i></a>
                        </div>

                        <!-- Total Invoices -->
                        <div class="stat-card">
                            <div class="stat-card-icon bg-info">
                                <i class="fas fa-file-invoice"></i>
                            </div>
                            <div class="stat-card-header">
                                <h3>Total Invoices</h3>
                                <div class="stat-value"><?= $stats['total_invoices'] ?></div>
                            </div>
                            <a href="invoice_list.php">View Details <i class="fas fa-arrow-right"></i></a>
                        </div>

                        <!-- Complete Invoices -->
                        <div class="stat-card">
                            <div class="stat-card-icon bg-success">
                                <i class="fas fa-receipt"></i>
                            </div>
                            <div class="stat-card-header">
                                <h3>Complete Invoices</h3>
                                <div class="stat-value"><?= $stats['complete_invoices'] ?></div>
                            </div>
                            <a href="complete_invoice_list.php">View Details <i class="fas fa-arrow-right"></i></a>
                        </div>
                        
                        <!-- Pending Invoices - New Card -->
                        <div class="stat-card">
                            <div class="stat-card-icon bg-warning">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="stat-card-header">
                                <h3>Pending Invoices</h3>
                                <div class="stat-value"><?= $stats['pending_invoices'] ?></div>
                            </div>
                            <a href="pending_invoice_list.php">View Details <i class="fas fa-arrow-right"></i></a>
                        </div>
                        <!-- Cancel Invoices - New Card -->
                        <div class="stat-card">
                            <div class="stat-card-icon bg-danger">
                                <i class="fas fa-ban"></i>
                            </div>
                            <div class="stat-card-header">
                                <h3>Cancel Invoices</h3>
                                <div class="stat-value"><?= $stats['cancel_invoices'] ?></div>
                            </div>
                            <a href="cancel_invoice_list.php">View Details <i class="fas fa-arrow-right"></i></a>
                        </div>
                    </div>
                </div>
            </main>
           
        </div>
    </div>

    <!-- Include JavaScript dependencies -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"
        crossorigin="anonymous"></script>
    <script src="js/scripts.js"></script>
</body>

</html>

<?php
// Close database connection
$conn->close();
?>