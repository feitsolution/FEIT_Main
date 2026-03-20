<?php
// Start session at the very beginning
session_start();

// Check if user is logged in, if not redirect to login page
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    if (ob_get_level()) {
        ob_end_clean();
    }
    header("Location: signin.php");
    exit();
}

include 'db_connection.php';
include 'functions.php';

// Role checks
$current_user_role = isset($_SESSION['role_id']) ? (int)$_SESSION['role_id'] : 0;
$isAdmin = ($current_user_role === 1);
$isModerator = ($current_user_role === 3);
$currentUserId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;

// Helper function to safely query the database
function safeQuery($conn, $query) {
    try {
        $result = $conn->query($query);
        if ($result) {
            $row = $result->fetch_assoc();
            return isset($row['count']) ? (int)$row['count'] : 0;
        }
    } catch (Exception $e) {
        return 0;
    }
    return 0;
}

// Initialize statistics
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
    'pending_invoices' => 0,
    'cancel_invoices' => 0,
    'my_invoices' => 0
];

if ($isAdmin) {
    $stats['total_users'] = safeQuery($conn, "SELECT COUNT(*) as count FROM users");
}

$tableExists = $conn->query("SHOW TABLES LIKE 'user_form_data'");
if ($tableExists && $tableExists->num_rows > 0) {
    $stats['all_inquiries'] = safeQuery($conn, "SELECT COUNT(*) as count FROM user_form_data");
    if ($isAdmin || $isModerator) {
        $stats['pending_inquiries'] = safeQuery($conn, "SELECT COUNT(*) as count FROM user_form_data WHERE status = 'pending'");
        $stats['approved_inquiries'] = safeQuery($conn, "SELECT COUNT(*) as count FROM user_form_data WHERE status = 'approved'");
        $stats['rejected_inquiries'] = safeQuery($conn, "SELECT COUNT(*) as count FROM user_form_data WHERE status = 'rejected'");
    }
}

$tableExists = $conn->query("SHOW TABLES LIKE 'customers'");
if ($tableExists && $tableExists->num_rows > 0) {
    $stats['total_customers'] = safeQuery($conn, "SELECT COUNT(*) as count FROM customers");
}

$tableExists = $conn->query("SHOW TABLES LIKE 'products'");
if ($tableExists && $tableExists->num_rows > 0) {
    $stats['total_products'] = safeQuery($conn, "SELECT COUNT(*) as count FROM products");
}

$tableExists = $conn->query("SHOW TABLES LIKE 'invoices'");
if ($tableExists && $tableExists->num_rows > 0) {
    if ($isAdmin) {
        $stats['total_invoices'] = safeQuery($conn, "SELECT COUNT(*) as count FROM invoices");
        $stats['complete_invoices'] = safeQuery($conn, "SELECT COUNT(*) as count FROM invoices WHERE status = 'done'");
        $stats['pending_invoices'] = safeQuery($conn, "SELECT COUNT(*) as count FROM invoices WHERE status = 'pending'");
        $stats['cancel_invoices'] = safeQuery($conn, "SELECT COUNT(*) as count FROM invoices WHERE status = 'cancel'");
    } else {
        $stats['my_invoices'] = safeQuery($conn, "SELECT COUNT(*) as count FROM invoices WHERE created_by = $currentUserId");
        $stats['complete_invoices'] = safeQuery($conn, "SELECT COUNT(*) as count FROM invoices WHERE created_by = $currentUserId AND status = 'done'");
        $stats['pending_invoices'] = safeQuery($conn, "SELECT COUNT(*) as count FROM invoices WHERE created_by = $currentUserId AND status = 'pending'");
        $stats['cancel_invoices'] = safeQuery($conn, "SELECT COUNT(*) as count FROM invoices WHERE created_by = $currentUserId AND status = 'cancel'");
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <?php include('header.php'); ?>
    <title>Dashboard - FEIT Admin</title>
    <link rel="icon" href="img/system/letter-f.png" type="image/png">
    <style>
        /* Greeting */
        .dash-greeting {
            padding: 18px 0 6px;
        }
        .dash-greeting h4 {
            font-size: 22px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 2px;
        }
        .dash-greeting p {
            font-size: 14px;
            color: #64748b;
            margin: 0;
        }

        /* Stat cards */
        .stat-card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.06), 0 1px 2px rgba(0,0,0,0.04);
            transition: box-shadow 0.2s, transform 0.2s;
            overflow: hidden;
        }
        .stat-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }
        .stat-card .card-body {
            padding: 20px;
        }
        .stat-card .icon-circle {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }
        .stat-card .stat-label {
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #94a3b8;
            margin-bottom: 4px;
        }
        .stat-card .stat-number {
            font-size: 28px;
            font-weight: 700;
            color: #0f172a;
            line-height: 1.2;
        }
        .stat-card .stat-link {
            font-size: 12px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            margin-top: 10px;
        }
        .stat-card .stat-link i {
            font-size: 10px;
            transition: transform 0.15s;
        }
        .stat-card .stat-link:hover i {
            transform: translateX(3px);
        }

        /* Section headings */
        .section-label {
            font-size: 15px;
            font-weight: 700;
            color: #334155;
            margin: 24px 0 12px;
            padding-left: 2px;
        }

        /* Quick actions */
        .quick-action {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 16px;
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            text-decoration: none;
            color: #334155;
            font-weight: 600;
            font-size: 13px;
            transition: all 0.15s;
        }
        .quick-action:hover {
            border-color: #cbd5e1;
            background: #f8fafc;
            color: #1e293b;
        }
        .quick-action .qa-icon {
            width: 34px;
            height: 34px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            flex-shrink: 0;
        }
    </style>
</head>

<body class="sb-nav-fixed">

<?php include 'navbar.php'; ?>

<div id="layoutSidenav">
    <?php include 'sidebar.php'; ?>
    <div id="layoutSidenav_content">
        <main>
            <div class="container-fluid px-4">

                <!-- Greeting -->
                <div class="dash-greeting">
                    <h4>Dashboard</h4>
                    <p>Welcome back, <?= htmlspecialchars($_SESSION['name'] ?? 'User') ?>. Here's what's happening today.</p>
                </div>

                <!-- Quick Actions -->
                <div class="row g-2 mb-3">
                    <div class="col-auto">
                        <a href="invoice_create.php" class="quick-action">
                            <span class="qa-icon bg-primary bg-opacity-10 text-primary"><i class="fas fa-plus"></i></span>
                            New Invoice
                        </a>
                    </div>
                    <?php if ($isAdmin || $isModerator): ?>
                    <div class="col-auto">
                        <a href="add_customer.php" class="quick-action">
                            <span class="qa-icon bg-success bg-opacity-10 text-success"><i class="fas fa-user-plus"></i></span>
                            Add Customer
                        </a>
                    </div>
                    <div class="col-auto">
                        <a href="add_product.php" class="quick-action">
                            <span class="qa-icon bg-info bg-opacity-10 text-info"><i class="fas fa-box-open"></i></span>
                            Add Product
                        </a>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Inquiries -->
                <div class="section-label">Inquiries</div>
                <div class="row g-3">
                    <div class="col-xl-3 col-md-6">
                        <div class="card stat-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <div class="stat-label">Total Inquiries</div>
                                        <div class="stat-number"><?= number_format($stats['all_inquiries']) ?></div>
                                    </div>
                                    <div class="icon-circle bg-info bg-opacity-10 text-info"><i class="fas fa-inbox"></i></div>
                                </div>
                                <a href="display_inquries.php" class="stat-link text-info">View all <i class="fas fa-arrow-right"></i></a>
                            </div>
                        </div>
                    </div>

                    <?php if ($isAdmin || $isModerator): ?>
                    <div class="col-xl-3 col-md-6">
                        <div class="card stat-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <div class="stat-label">Pending</div>
                                        <div class="stat-number"><?= number_format($stats['pending_inquiries']) ?></div>
                                    </div>
                                    <div class="icon-circle bg-warning bg-opacity-10 text-warning"><i class="fas fa-clock"></i></div>
                                </div>
                                <a href="display_pending_inquiries.php" class="stat-link text-warning">Review <i class="fas fa-arrow-right"></i></a>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6">
                        <div class="card stat-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <div class="stat-label">Approved</div>
                                        <div class="stat-number"><?= number_format($stats['approved_inquiries']) ?></div>
                                    </div>
                                    <div class="icon-circle bg-success bg-opacity-10 text-success"><i class="fas fa-check-circle"></i></div>
                                </div>
                                <a href="display_approved_inquiries.php" class="stat-link text-success">View <i class="fas fa-arrow-right"></i></a>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6">
                        <div class="card stat-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <div class="stat-label">Rejected</div>
                                        <div class="stat-number"><?= number_format($stats['rejected_inquiries']) ?></div>
                                    </div>
                                    <div class="icon-circle bg-danger bg-opacity-10 text-danger"><i class="fas fa-times-circle"></i></div>
                                </div>
                                <a href="display_rejected_inquiries.php" class="stat-link text-danger">View <i class="fas fa-arrow-right"></i></a>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Invoices -->
                <div class="section-label">Invoices <?php if (!$isAdmin): ?><small class="fw-normal text-muted">(Your invoices)</small><?php endif; ?></div>
                <div class="row g-3">
                    <div class="col-xl-3 col-md-6">
                        <div class="card stat-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <div class="stat-label"><?= $isAdmin ? 'Total Invoices' : 'My Invoices' ?></div>
                                        <div class="stat-number"><?= number_format($isAdmin ? $stats['total_invoices'] : $stats['my_invoices']) ?></div>
                                    </div>
                                    <div class="icon-circle bg-primary bg-opacity-10 text-primary"><i class="fas fa-file-invoice"></i></div>
                                </div>
                                <a href="invoice_list.php" class="stat-link text-primary">View all <i class="fas fa-arrow-right"></i></a>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6">
                        <div class="card stat-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <div class="stat-label">Pending</div>
                                        <div class="stat-number"><?= number_format($stats['pending_invoices']) ?></div>
                                    </div>
                                    <div class="icon-circle bg-warning bg-opacity-10 text-warning"><i class="fas fa-clock"></i></div>
                                </div>
                                <?php if ($isAdmin || $isModerator): ?>
                                <a href="pending_invoice_list.php" class="stat-link text-warning">Review <i class="fas fa-arrow-right"></i></a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6">
                        <div class="card stat-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <div class="stat-label">Completed</div>
                                        <div class="stat-number"><?= number_format($stats['complete_invoices']) ?></div>
                                    </div>
                                    <div class="icon-circle bg-success bg-opacity-10 text-success"><i class="fas fa-check-circle"></i></div>
                                </div>
                                <?php if ($isAdmin || $isModerator): ?>
                                <a href="complete_invoice_list.php" class="stat-link text-success">View <i class="fas fa-arrow-right"></i></a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6">
                        <div class="card stat-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <div class="stat-label">Cancelled</div>
                                        <div class="stat-number"><?= number_format($stats['cancel_invoices']) ?></div>
                                    </div>
                                    <div class="icon-circle bg-danger bg-opacity-10 text-danger"><i class="fas fa-ban"></i></div>
                                </div>
                                <?php if ($isAdmin || $isModerator): ?>
                                <a href="cancel_invoice_list.php" class="stat-link text-danger">View <i class="fas fa-arrow-right"></i></a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Overview -->
                <div class="section-label">Overview</div>
                <div class="row g-3 mb-4">
                    <?php if ($isAdmin): ?>
                    <div class="col-xl-3 col-md-6">
                        <div class="card stat-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <div class="stat-label">Total Users</div>
                                        <div class="stat-number"><?= number_format($stats['total_users']) ?></div>
                                    </div>
                                    <div class="icon-circle bg-purple bg-opacity-10 text-purple"><i class="fas fa-users-cog"></i></div>
                                </div>
                                <a href="users.php" class="stat-link" style="color:#7c3aed;">Manage <i class="fas fa-arrow-right"></i></a>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="col-xl-3 col-md-6">
                        <div class="card stat-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <div class="stat-label">Customers</div>
                                        <div class="stat-number"><?= number_format($stats['total_customers']) ?></div>
                                    </div>
                                    <div class="icon-circle bg-secondary bg-opacity-10 text-secondary"><i class="fas fa-users"></i></div>
                                </div>
                                <a href="customer_list.php" class="stat-link text-secondary">View all <i class="fas fa-arrow-right"></i></a>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6">
                        <div class="card stat-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <div class="stat-label">Products</div>
                                        <div class="stat-number"><?= number_format($stats['total_products']) ?></div>
                                    </div>
                                    <div class="icon-circle bg-info bg-opacity-10 text-info"><i class="fas fa-boxes-stacked"></i></div>
                                </div>
                                <a href="product_list.php" class="stat-link text-info">View all <i class="fas fa-arrow-right"></i></a>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
<script src="js/scripts.js"></script>
</body>

</html>

<?php
$conn->close();
?>