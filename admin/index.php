<?php
// Start session at the very beginning
session_start();
// TEMPORARY: Show errors on host for debugging (REMOVE after fixing)
error_reporting(E_ALL);
ini_set('display_errors', 1);

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
    <link href="css/forms.css" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8fafc;
        }

        /* Greeting */
        .dash-greeting {
            padding: 24px 0 16px;
        }
        .dash-greeting h4 {
            font-size: 26px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 4px;
            letter-spacing: -0.02em;
        }
        .dash-greeting p {
            font-size: 15px;
            color: #64748b;
            margin: 0;
        }

        /* Stat cards */
        .stat-card {
            border: 1px solid rgba(0,0,0,0.05);
            border-radius: 16px;
            box-shadow: var(--premium-card-shadow);
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            background: #ffffff;
            overflow: hidden;
            height: 100%;
        }
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px -4px rgba(0,0,0,0.1);
            border-color: var(--premium-primary-light);
        }
        .stat-card .card-body {
            padding: 18px 20px;
        }
        .stat-card .icon-circle {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            transition: transform 0.3s ease;
        }
        .stat-card:hover .icon-circle {
            transform: scale(1.05) rotate(-3deg);
        }
        .stat-card .stat-label {
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #94a3b8;
            margin-bottom: 4px;
        }
        .stat-card .stat-number {
            font-size: 26px;
            font-weight: 700;
            color: #0f172a;
            line-height: 1.1;
            letter-spacing: -0.01em;
        }
        .stat-card .stat-link {
            font-size: 12px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            margin-top: 12px;
            padding: 4px 10px;
            border-radius: 8px;
            background: #f1f5f9;
            transition: all 0.2s;
        }
        .stat-card .stat-link:hover {
            background: var(--premium-primary-light);
            color: white !important;
        }
        .stat-card .stat-link i {
            font-size: 11px;
            transition: transform 0.2s;
        }
        .stat-card .stat-link:hover i {
            transform: translateX(4px);
        }

        /* Section headings */
        .section-label {
            font-size: 16px;
            font-weight: 700;
            color: #475569;
            margin: 32px 0 16px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .section-label::after {
            content: '';
            flex-grow: 1;
            height: 1px;
            background: #e2e8f0;
        }

        /* Quick actions */
        .quick-action {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 20px;
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            text-decoration: none;
            color: #334155;
            font-weight: 600;
            font-size: 14px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.02);
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .quick-action:hover {
            border-color: var(--premium-primary);
            background: #f8fafc;
            color: var(--premium-primary);
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            transform: translateY(-1px);
        }
        .quick-action .qa-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            flex-shrink: 0;
            transition: all 0.2s;
        }
        .quick-action:hover .qa-icon {
            transform: scale(1.1);
        }

        /* Custom colors for stats */
        .bg-purple { background-color: rgba(124, 58, 237, 0.1) !important; color: #7c3aed !important; }
        .text-purple { color: #7c3aed !important; }
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