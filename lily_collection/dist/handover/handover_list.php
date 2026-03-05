<?php
// Start session at the very beginning
session_start();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    if (ob_get_level()) {
        ob_end_clean();
    }
    header("Location: /lily_collection/dist/pages/login.php");
    exit();
}

// Include the database connection file
include($_SERVER['DOCUMENT_ROOT'] . '/lily_collection/dist/connection/db_connection.php');

// Check if user has admin role (role_id = 1)
if (!isset($_SESSION['user_id'])) {
    header("Location: /lily_collection/dist/pages/login.php");
    exit();
}

// Get user's role from database
$user_id = $_SESSION['user_id'];
$role_check_sql = "SELECT u.role_id, r.name as role_name 
                   FROM users u 
                   LEFT JOIN roles r ON u.role_id = r.id 
                   WHERE u.id = ? AND u.status = 'active'";
$role_stmt = $conn->prepare($role_check_sql);
$role_stmt->bind_param("i", $user_id);
$role_stmt->execute();
$role_result = $role_stmt->get_result();

if ($role_result->num_rows === 0) {
    // User not found or inactive
    session_destroy();
    header("Location: /lily_collection/dist/pages/login.php");
    exit();
}

$user_role = $role_result->fetch_assoc();

// Check if user is admin (role_id = 1)
if ($user_role['role_id'] != 1) {
    // User is not admin, redirect to dashboard
    header("Location: /lily_collection/dist/dashboard/index.php");
    exit();
}

include($_SERVER['DOCUMENT_ROOT'] . '/lily_collection/dist/include/navbar.php');
include($_SERVER['DOCUMENT_ROOT'] . '/lily_collection/dist/include/sidebar.php');

// Handle filter parameters
$handover_id_filter = isset($_GET['handover_id_filter']) ? trim($_GET['handover_id_filter']) : '';
$product_filter = isset($_GET['product_filter']) ? trim($_GET['product_filter']) : '';
$status_filter = isset($_GET['status_filter']) ? trim($_GET['status_filter']) : '';
$date_from = isset($_GET['date_from']) ? trim($_GET['date_from']) : '';
$date_to = isset($_GET['date_to']) ? trim($_GET['date_to']) : '';
$created_by_filter = isset($_GET['created_by_filter']) ? trim($_GET['created_by_filter']) : '';
$handover_to_filter = isset($_GET['handover_to_filter']) ? trim($_GET['handover_to_filter']) : '';

// Fetch users for filters
$users_list = [];
$usersResult = $conn->query("SELECT id, name FROM users ORDER BY name ASC");
if ($usersResult) {
    while ($u = $usersResult->fetch_assoc()) {
        $users_list[] = $u;
    }
}

// Pagination settings
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Base SQL
$countSql = "SELECT COUNT(*) as total FROM handover_list hl";
$sql = "SELECT hl.*, p.name as product_name, p.product_code,
               u1.name as created_by_name, u2.name as confirmed_by_name,
               mhh.handover_date, mhh.handover_to, mhh.notes as handover_notes,
               u3.name as handover_to_name
        FROM handover_list hl
        LEFT JOIN products p ON hl.product_id = p.id
        LEFT JOIN users u1 ON hl.created_by = u1.id
        LEFT JOIN users u2 ON hl.confirmed_by = u2.id
        LEFT JOIN material_handover_header mhh ON hl.handover_id = mhh.id
        LEFT JOIN users u3 ON mhh.handover_to = u3.id";

$searchConditions = [];

if (!empty($handover_id_filter)) {
    $idTerm = $conn->real_escape_string($handover_id_filter);
    $searchConditions[] = "hl.id LIKE '%$idTerm%'";
}

if (!empty($product_filter)) {
    $productTerm = $conn->real_escape_string($product_filter);
    $searchConditions[] = "p.name LIKE '%$productTerm%'";
}

if (!empty($status_filter)) {
    $statusTerm = $conn->real_escape_string($status_filter);
    $searchConditions[] = "hl.status = '$statusTerm'";
}

if (!empty($date_from)) {
    $dateFromTerm = $conn->real_escape_string($date_from);
    $searchConditions[] = "DATE(hl.created_at) >= '$dateFromTerm'";
}

if (!empty($date_to)) {
    $dateToTerm = $conn->real_escape_string($date_to);
    $searchConditions[] = "DATE(hl.created_at) <= '$dateToTerm'";
}

if (!empty($created_by_filter)) {
    $cbId = (int)$created_by_filter;
    $searchConditions[] = "hl.created_by = $cbId";
}

if (!empty($handover_to_filter)) {
    $htId = (int)$handover_to_filter;
    $searchConditions[] = "mhh.handover_to = $htId";
}

if (!empty($searchConditions)) {
    $whereClause = " WHERE " . implode(' AND ', $searchConditions);
    $sql .= $whereClause;
    // For count query, include join with material_handover_header as it's used in filters
    $countSql = "SELECT COUNT(*) as total FROM handover_list hl 
                 LEFT JOIN products p ON hl.product_id = p.id
                 LEFT JOIN material_handover_header mhh ON hl.handover_id = mhh.id" . $whereClause;
}

$sql .= " ORDER BY hl.id DESC LIMIT $limit OFFSET $offset";

$countResult = $conn->query($countSql);
$totalRows = 0;
if ($countResult && $countResult->num_rows > 0) {
    $totalRows = $countResult->fetch_assoc()['total'];
}
$totalPages = ceil($totalRows / $limit);
$result = $conn->query($sql);
?>

<!doctype html>
<html lang="en" data-pc-preset="preset-1" data-pc-sidebar-caption="true" data-pc-direction="ltr" dir="ltr" data-pc-theme="light">

<head>
    <title>Order Management Admin Portal - Handover List</title>
    
    <?php include($_SERVER['DOCUMENT_ROOT'] . '/lily_collection/dist/include/head.php'); ?>
    
    <link rel="stylesheet" href="../assets/css/style.css" id="main-style-link" />
    <link rel="stylesheet" href="../assets/css/orders.css" />
    <link rel="stylesheet" href="../assets/css/customers.css" />
</head>

<body>
    <?php include($_SERVER['DOCUMENT_ROOT'] . '/lily_collection/dist/include/loader.php'); ?>

    <div class="pc-container">
        <div class="pc-content">
            
            <div class="page-header">
                <div class="page-block">
                    <div class="page-header-title">
                        <h5 class="mb-0 font-medium">Handover List</h5>
                    </div>
                </div>
            </div>

            <div class="main-content-wrapper">
                
                <!-- Filter Section -->
                <div class="tracking-container">
                    <form class="tracking-form" method="GET" action="">
                        <div class="form-group">
                            <label for="handover_id_filter">Handover ID</label>
                            <input type="text" id="handover_id_filter" name="handover_id_filter" 
                                   placeholder="Enter handover ID" 
                                   value="<?php echo htmlspecialchars($handover_id_filter); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="product_filter">Product Name</label>
                            <input type="text" id="product_filter" name="product_filter" 
                                   placeholder="Enter product name" 
                                   value="<?php echo htmlspecialchars($product_filter); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="status_filter">Status</label>
                            <select id="status_filter" name="status_filter">
                                <option value="" <?php echo ($status_filter == '') ? 'selected' : ''; ?>>All Status</option>
                                <option value="pending" <?php echo ($status_filter == 'pending') ? 'selected' : ''; ?>>Pending</option>
                                <option value="remaining" <?php echo ($status_filter == 'remaining') ? 'selected' : ''; ?>>Remaining</option>
                                <option value="completed" <?php echo ($status_filter == 'completed') ? 'selected' : ''; ?>>Completed</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="date_from">Date From</label>
                            <input type="date" id="date_from" name="date_from" 
                                   value="<?php echo htmlspecialchars($date_from); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="date_to">Date To</label>
                            <input type="date" id="date_to" name="date_to" 
                                   value="<?php echo htmlspecialchars($date_to); ?>">
                        </div>

                        <div class="form-group">
                            <label for="created_by_filter">Created By</label>
                            <select id="created_by_filter" name="created_by_filter">
                                <option value="">All Users</option>
                                <?php foreach ($users_list as $user): ?>
                                    <option value="<?php echo $user['id']; ?>" <?php echo ($created_by_filter == $user['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($user['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="handover_to_filter">Handover To</label>
                            <select id="handover_to_filter" name="handover_to_filter">
                                <option value="">All Users</option>
                                <?php foreach ($users_list as $user): ?>
                                    <option value="<?php echo $user['id']; ?>" <?php echo ($handover_to_filter == $user['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($user['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <div class="button-group">
                                <button type="submit" class="search-btn">
                                    <i class="fas fa-search"></i> Search
                                </button>
                                <button type="button" class="search-btn" onclick="clearFilters()" style="background: #6c757d;">
                                    <i class="fas fa-times"></i> Clear
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Count Display -->
                <div class="order-count-container">
                    <div class="order-count-number"><?php echo number_format($totalRows); ?></div>
                    <div class="order-count-dash">-</div>
                    <div class="order-count-subtitle">Total Handover Records</div>
                </div>

                <!-- Handover Table -->
                <div class="table-wrapper">
                    <table class="orders-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Product</th>
                                <th>Qty to Produce</th>
                                <th>Produced Qty</th>
                                <th>Remaining Balance</th>
                                <th>Status</th>
                                <th>Created By</th>
                                <th>Created Date</th>
                                <th>Confirmed By</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result && $result->num_rows > 0): ?>
                                <?php while ($row = $result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['id']); ?></td>
                                        <td>
                                            <div class="product-info">
                                                <h6 style="margin: 0; font-size: 14px; font-weight: 600;"><?php echo htmlspecialchars($row['product_name'] ?? 'N/A'); ?></h6>
                                                <small style="color: #6c757d; font-family: segoe ui;"><?php echo htmlspecialchars($row['product_code'] ?? ''); ?></small>
                                            </div>
                                        </td>
                                        <td>
                                            <span style="font-weight: 600; color: #007bff;"><?php echo (int)$row['quantity_to_produce']; ?></span>
                                        </td>
                                        <td>
                                            <span style="font-weight: 600; color: <?php echo ((int)$row['produced_quantity'] > 0) ? '#28a745' : '#6c757d'; ?>;">
                                                <?php echo (int)$row['produced_quantity']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php 
                                                $remaining = (int)$row['quantity_to_produce'] - (int)$row['produced_quantity'];
                                                $color = ($remaining > 0) ? '#dc3545' : '#28a745';
                                            ?>
                                            <span style="font-weight: 600; color: <?php echo $color; ?>;">
                                                <?php echo $remaining; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($row['status'] === 'completed'): ?>
                                                <span class="status-badge pay-status-paid">Completed</span>
                                            <?php elseif ($row['status'] === 'remaining'): ?>
                                                <span class="status-badge" style="background:#fff3cd;color:#856404;border:1px solid #ffc107;">Remaining</span>
                                            <?php else: ?>
                                                <span class="status-badge pay-status-unpaid">Pending</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($row['created_by_name'] ?? 'N/A'); ?></td>
                                        <td>
                                            <div style="font-size: 13px;">
                                                <?php echo date('Y-m-d', strtotime($row['created_at'])); ?>
                                                <br>
                                                <small style="color: #6c757d;"><?php echo date('H:i:s', strtotime($row['created_at'])); ?></small>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if ($row['status'] === 'completed' && !empty($row['confirmed_by_name'])): ?>
                                                <?php echo htmlspecialchars($row['confirmed_by_name']); ?>
                                            <?php else: ?>
                                                <span style="color: #6c757d;">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="actions">
                                            <div class="action-buttons-group">
                                                <?php if ($row['status'] === 'pending' || $row['status'] === 'remaining'): ?>
                                                    <button type="button" class="action-btn" 
                                                            style="background: #28a745; color: white;" 
                                                            title="Confirm Production"
                                                            data-hlg-id="<?= $row['id'] ?>"
                                                            data-product-name="<?= htmlspecialchars($row['product_name'] ?? '') ?>"
                                                            data-qty-to-produce="<?= (int)$row['quantity_to_produce'] ?>"
                                                            data-produced-qty="<?= (int)$row['produced_quantity'] ?>"
                                                            onclick="openConfirmModal(this)">
                                                        <i class="fas fa-check-circle"></i>
                                                    </button>
                                                <?php endif; ?>
                                                <button type="button" class="action-btn view-btn view-product-btn"   
                                                        title="View Handover Details"
                                                        data-hlg-id="<?= $row['id'] ?>"
                                                        data-handover-id="<?= $row['handover_id'] ?>"
                                                        data-product-name="<?= htmlspecialchars($row['product_name'] ?? '') ?>"
                                                        data-product-code="<?= htmlspecialchars($row['product_code'] ?? '') ?>"
                                                        data-qty-to-produce="<?= (int)$row['quantity_to_produce'] ?>"
                                                        data-produced-qty="<?= (int)$row['produced_quantity'] ?>"
                                                        data-handover-date="<?= !empty($row['handover_date']) ? date('Y-m-d', strtotime($row['handover_date'])) : '' ?>"
                                                        data-handover-to="<?= htmlspecialchars($row['handover_to_name'] ?? '') ?>"
                                                        data-handover-notes="<?= htmlspecialchars($row['handover_notes'] ?? '') ?>"
                                                        data-status="<?= htmlspecialchars($row['status']) ?>"
                                                        data-confirmed-by="<?= htmlspecialchars($row['confirmed_by_name'] ?? '') ?>"
                                                        onclick="openHandoverDetailsModal(this)">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                    <tr>
                                        <td colspan="10" class="text-center" style="padding: 40px; text-align: center; color: #666;">
                                            <i class="fas fa-industry" style="font-size: 2rem; margin-bottom: 10px; display: block;"></i>
                                            No Handover records found
                                        </td>
                                    </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination Controls -->
                <div class="pagination">
                    <div class="pagination-info">
                        Showing <?php echo $offset + 1; ?> to <?php echo min($offset + $limit, $totalRows); ?> of <?php echo $totalRows; ?> entries
                    </div>
                    <div class="pagination-controls">
                        <?php if ($page > 1): ?>
                            <button class="page-btn" onclick="window.location.href='?page=<?php echo $page - 1; ?>&limit=<?php echo $limit; ?>&handover_id_filter=<?php echo urlencode($handover_id_filter); ?>&product_filter=<?php echo urlencode($product_filter); ?>&status_filter=<?php echo urlencode($status_filter); ?>&date_from=<?php echo urlencode($date_from); ?>&date_to=<?php echo urlencode($date_to); ?>&created_by_filter=<?php echo urlencode($created_by_filter); ?>&handover_to_filter=<?php echo urlencode($handover_to_filter); ?>'">
                                <i class="fas fa-chevron-left"></i>
                            </button>
                        <?php endif; ?>
                        
                        <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                            <button class="page-btn <?php echo ($i == $page) ? 'active' : ''; ?>" 
                                    onclick="window.location.href='?page=<?php echo $i; ?>&limit=<?php echo $limit; ?>&handover_id_filter=<?php echo urlencode($handover_id_filter); ?>&product_filter=<?php echo urlencode($product_filter); ?>&status_filter=<?php echo urlencode($status_filter); ?>&date_from=<?php echo urlencode($date_from); ?>&date_to=<?php echo urlencode($date_to); ?>&created_by_filter=<?php echo urlencode($created_by_filter); ?>&handover_to_filter=<?php echo urlencode($handover_to_filter); ?>'">
                                <?php echo $i; ?>
                            </button>
                        <?php endfor; ?>
                        
                        <?php if ($page < $totalPages): ?>
                            <button class="page-btn" onclick="window.location.href='?page=<?php echo $page + 1; ?>&limit=<?php echo $limit; ?>&handover_id_filter=<?php echo urlencode($handover_id_filter); ?>&product_filter=<?php echo urlencode($product_filter); ?>&status_filter=<?php echo urlencode($status_filter); ?>&date_from=<?php echo urlencode($date_from); ?>&date_to=<?php echo urlencode($date_to); ?>&created_by_filter=<?php echo urlencode($created_by_filter); ?>&handover_to_filter=<?php echo urlencode($handover_to_filter); ?>'">
                                <i class="fas fa-chevron-right"></i>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Confirm Production Modal -->
    <div id="confirmProductionModal" class="modal">
        <div class="modal-content" style="max-width: 450px;">
            <div class="modal-header">
                <h4>Confirm Production</h4>
                <span class="close" onclick="closeConfirmModal()">&times;</span>
            </div>
            <div class="modal-body">
                <p style="margin-bottom: 10px;">Product: <strong id="confirm-product-name"></strong></p>
                <p style="margin-bottom: 5px;">Qty to Produce: <strong id="confirm-planned-qty"></strong></p>
                <p style="margin-bottom: 15px;">Already Produced: <strong id="confirm-already-produced"></strong></p>
                <p style="margin-bottom: 15px; color: #007bff; font-weight: 600;">Remaining Balance: <strong id="confirm-remaining-balance"></strong></p>

                <div id="confirm-error-msg" style="display:none; color: #dc3545; font-size: 13px; margin-bottom: 10px; font-weight: 500;"></div>

                <div class="form-group" style="margin-bottom: 20px;">
                    <label for="produced_quantity" style="display: block; margin-bottom: 8px; font-weight: 500;">
                        Actual Produced Quantity <span style="color: red;">*</span>
                    </label>
                    <input type="number" id="produced_quantity" min="1" required
                           placeholder="Enter produced quantity"
                           style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
                </div>

                <div class="modal-buttons" style="display: flex; gap: 10px; justify-content: flex-end;">
                    <button type="button" onclick="closeConfirmModal()" 
                            style="padding: 8px 16px; border: none; border-radius: 4px; cursor: pointer; background: #6c757d; color: white;">Cancel</button>
                    <button type="button" id="confirmProductionBtn"
                            style="padding: 8px 16px; border: none; border-radius: 4px; cursor: pointer; background: #28a745; color: white;">Confirm Production</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Handover Details Modal -->
    <div id="handoverDetailsModal" class="modal">
        <div class="modal-content" style="max-width: 800px;">
            <div class="modal-header">
                <h4>Handover Details</h4>
                <span class="close" onclick="closeHandoverDetailsModal()">&times;</span>
            </div>
            <div class="modal-body">
                <div style="display: flex; gap: 30px; align-items: flex-start;">
                    <!-- Left Column: General Info -->
                    <div style="flex: 1;">
                        <h5 style="margin-bottom: 15px; border-bottom: 2px solid #f0f0f0; padding-bottom: 8px; color: #1e40af; font-weight: 600;">Overview</h5>
                        <table style="width: 100%; border-collapse: collapse;">
                            <tr>
                                <td style="padding: 8px 0; font-weight: 500; width: 130px;">Handover ID:</td>
                                <td style="padding: 8px 0;"><strong id="detail-hlg-id"></strong></td>
                            </tr>
                            <tr>
                                <td style="padding: 10px 0; font-weight: 500;">Product:</td>
                                <td style="padding: 10px 0;">
                                    <div>
                                        <strong id="detail-product-name" style="font-size: 14px;margin-right: 6px;"></strong>
                                        <small id="detail-product-code" style="font-weight: bold;"></small>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td style="padding: 8px 0; font-weight: 500;">Qty to Produce:</td>
                                <td style="padding: 8px 0;"><span id="detail-qty-to-produce" style="font-weight: 600; color: #1e40af;"></span> units</td>
                            </tr>
                            <tr>
                                <td style="padding: 8px 0; font-weight: 500;">Produced Qty:</td>
                                <td style="padding: 8px 0;"><span id="detail-produced-qty" style="font-weight: 600; color: #28a745;"></span> units</td>
                            </tr>
                            <tr>
                                <td style="padding: 8px 0; font-weight: 500;">Status:</td>
                                <td style="padding: 8px 0;"><span id="detail-status"></span></td>
                            </tr>
                            <tr>
                                <td style="padding: 8px 0; font-weight: 500;">Handover Date:</td>
                                <td style="padding: 8px 0;"><span id="detail-handover-date"></span></td>
                            </tr>
                            <tr>
                                <td style="padding: 8px 0; font-weight: 500;">Handover To:</td>
                                <td style="padding: 8px 0;"><span id="detail-handover-to" style="font-weight: 600; color: #333;"></span></td>
                            </tr>
                            <tr>
                                <td style="padding: 8px 0; font-weight: 500;">Notes:</td>
                                <td style="padding: 8px 0;"><span id="detail-handover-notes" style="font-style: italic; color: #666; overflow-wrap: break-word; white-space: pre-wrap; display: block; max-width: 250px;"></span></td>
                            </tr>
                            <tr>
                                <td style="padding: 8px 0; font-weight: 500;">Confirmed By:</td>
                                <td style="padding: 8px 0;"><span id="detail-confirmed-by"></span></td>
                            </tr>
                        </table>
                    </div>

                    <!-- Right Column: Materials -->
                    <div style="flex: 1;">
                        <h5 style="margin-bottom: 15px; border-bottom: 2px solid #f0f0f0; padding-bottom: 8px; color: #1e40af; font-weight: 600;">Material Details</h5>
                        <div style="max-height: 420px; overflow-y: auto; border: 1px solid #e9ecef; border-radius: 8px; background: #fff; box-shadow: inset 0 2px 4px rgba(0,0,0,0.02);">
                            <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                                <thead>
                                    <tr style="background: #f1f5f9; position: sticky; top: 0; z-index: 2;">
                                        <th style="padding: 12px; text-align: left; border-bottom: 1px solid #e2e8f0; color: #475569; font-weight: 600;">Material</th>
                                        <th style="padding: 12px; text-align: right; border-bottom: 1px solid #e2e8f0; color: #475569; font-weight: 600;">Qty Used</th>
                                    </tr>
                                </thead>
                                <tbody id="material-details-body">
                                    <tr>
                                        <td colspan="2" style="padding: 25px; text-align: center; color: #6c757d;">
                                            <i class="fas fa-spinner fa-spin" style="margin-bottom: 10px; display: block; font-size: 1.2rem;"></i>
                                            Loading materials...
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <div class="modal-buttons" style="margin-top: 25px; padding-top: 15px; border-top: 1px solid #eee; display: flex; justify-content: flex-end;">
                    <button type="button" onclick="closeHandoverDetailsModal()" 
                            style="padding: 8px 30px; border: none; border-radius: 6px; cursor: pointer; background: #1e40af; color: white; font-weight: 500; transition: all 0.2s;">Close</button>
                </div>
            </div>
        </div>
    </div>

    <?php include($_SERVER['DOCUMENT_ROOT'] . '/lily_collection/dist/include/footer.php'); ?>
    <?php include($_SERVER['DOCUMENT_ROOT'] . '/lily_collection/dist/include/scripts.php'); ?>

    <script>
        function clearFilters() {
            window.location.href = 'handover_list.php';
        }

        let currentHlgId = null;

        let currentQtyToProduce = 0;
        let currentAlreadyProduced = 0;

        function openConfirmModal(button) {
            currentHlgId = button.getAttribute('data-hlg-id');
            const productName = button.getAttribute('data-product-name');
            currentQtyToProduce = parseInt(button.getAttribute('data-qty-to-produce')) || 0;
            currentAlreadyProduced = parseInt(button.getAttribute('data-produced-qty')) || 0;
            const remainingBalance = currentQtyToProduce - currentAlreadyProduced;

            document.getElementById('confirm-product-name').textContent = productName;
            document.getElementById('confirm-planned-qty').textContent = currentQtyToProduce;
            document.getElementById('confirm-already-produced').textContent = currentAlreadyProduced;
            document.getElementById('confirm-remaining-balance').textContent = remainingBalance;
            document.getElementById('produced_quantity').value = remainingBalance;
            document.getElementById('confirm-error-msg').style.display = 'none';
            document.getElementById('confirm-error-msg').textContent = '';

            document.getElementById('confirmProductionBtn').onclick = function() {
                confirmProduction();
            };

            document.getElementById('confirmProductionModal').style.display = 'block';
            document.getElementById('produced_quantity').focus();
            document.getElementById('produced_quantity').select();
        }

        function closeConfirmModal() {
            document.getElementById('confirmProductionModal').style.display = 'none';
            currentHlgId = null;
        }

                function openHandoverDetailsModal(button) {
            document.getElementById('detail-hlg-id').textContent = button.getAttribute('data-hlg-id');
            document.getElementById('detail-product-name').textContent = button.getAttribute('data-product-name');
            document.getElementById('detail-product-code').textContent = button.getAttribute('data-product-code');
            document.getElementById('detail-qty-to-produce').textContent = button.getAttribute('data-qty-to-produce');
            document.getElementById('detail-produced-qty').textContent = button.getAttribute('data-produced-qty');
            
            const status = button.getAttribute('data-status');
            const statusEl = document.getElementById('detail-status');
            if (status === 'completed') {
                statusEl.innerHTML = '<span class="status-badge pay-status-paid">Completed</span>';
            } else if (status === 'remaining') {
                statusEl.innerHTML = '<span class="status-badge" style="background:#fff3cd;color:#856404;border:1px solid #ffc107;">Remaining</span>';
            } else {
                statusEl.innerHTML = '<span class="status-badge pay-status-unpaid">Pending</span>';
            }
            
            document.getElementById('detail-handover-date').textContent = button.getAttribute('data-handover-date') || '-';
            document.getElementById('detail-handover-to').textContent = button.getAttribute('data-handover-to') || '-';
            document.getElementById('detail-handover-notes').textContent = button.getAttribute('data-handover-notes') || '-';
            
            const confirmedBy = button.getAttribute('data-confirmed-by');
            document.getElementById('detail-confirmed-by').textContent = confirmedBy || '-';

            // Fetch materials
            const handoverId = button.getAttribute('data-handover-id');
            const materialsBody = document.getElementById('material-details-body');
            materialsBody.innerHTML = '<tr><td colspan="2" style="padding: 15px; text-align: center; color: #6c757d;"><i class="fas fa-spinner fa-spin"></i> Loading materials...</td></tr>';

            if (handoverId && handoverId !== '0') {
                fetch(`get_handover_materials.php?handover_id=${handoverId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success && data.data.length > 0) {
                            materialsBody.innerHTML = data.data.map(item => `
                                <tr>
                                    <td style="padding: 8px; border-bottom: 1px solid #f0f0f0;">
                                        <strong>${item.material_name} (${item.material_code})</strong><br>
                                    </td>
                                    <td style="padding: 8px; text-align: right; border-bottom: 1px solid #f0f0f0; font-weight: 500;">
                                        ${item.quantity}
                                    </td>
                                </tr>
                            `).join('');
                        } else {
                            materialsBody.innerHTML = '<tr><td colspan="2" style="padding: 15px; text-align: center; color: #6c757d;">No material records found.</td></tr>';
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching materials:', error);
                        materialsBody.innerHTML = '<tr><td colspan="2" style="padding: 15px; text-align: center; color: #dc3545;">Failed to load materials.</td></tr>';
                    });
            } else {
                materialsBody.innerHTML = '<tr><td colspan="2" style="padding: 15px; text-align: center; color: #6c757d;">No handover ID associated.</td></tr>';
            }

            document.getElementById('handoverDetailsModal').style.display = 'block';
        }

        function closeHandoverDetailsModal() {
            document.getElementById('handoverDetailsModal').style.display = 'none';
        }

        function confirmProduction() {
            const producedQty = parseInt(document.getElementById('produced_quantity').value) || 0;
            const remainingBalance = currentQtyToProduce - currentAlreadyProduced;
            const errorEl = document.getElementById('confirm-error-msg');

            if (producedQty <= 0) {
                errorEl.textContent = 'Please enter a valid produced quantity (must be greater than 0).';
                errorEl.style.display = 'block';
                return;
            }

            if (producedQty > remainingBalance) {
                errorEl.textContent = `Produced Qty (${producedQty}) cannot exceed the remaining balance (${remainingBalance}).`;
                errorEl.style.display = 'block';
                return;
            }

            errorEl.style.display = 'none';

            const btn = document.getElementById('confirmProductionBtn');
            const originalText = btn.textContent;
            btn.textContent = 'Processing...';
            btn.disabled = true;

            fetch('confirm_handover.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    handover_id: currentHlgId,
                    produced_quantity: producedQty,
                    qty_to_produce: currentQtyToProduce
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    closeConfirmModal();
                    alert(data.message);
                    location.reload();
                } else {
                    errorEl.textContent = 'Error: ' + data.message;
                    errorEl.style.display = 'block';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                errorEl.textContent = 'An error occurred. Please try again.';
                errorEl.style.display = 'block';
            })
            .finally(() => {
                btn.textContent = originalText;
                btn.disabled = false;
            });
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal1 = document.getElementById('confirmProductionModal');
            const modal2 = document.getElementById('handoverDetailsModal');
            if (event.target === modal1) {
                closeConfirmModal();
            }
            if (event.target === modal2) {
                closeHandoverDetailsModal();
            }
        }
    </script>
</body>
</html>
