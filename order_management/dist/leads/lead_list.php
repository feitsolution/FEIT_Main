<?php
/**
 * Leads Management System
 * This page displays all orders where interface='leads'
 * Includes search, pagination, and modal view functionality
 */

// Start session management
session_start();

// Authentication check - redirect if not logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    // Clear output buffers before redirect
    if (ob_get_level()) {
        ob_end_clean();
    }
    header("Location: /order_management/dist/pages/login.php");
    exit();
}
// Admin accessibility check - Lead management is for admins only (role_id 1)
if (!isset($_SESSION['role_id']) || intval($_SESSION['role_id']) !== 1) {
    // Redirect to dashboard or an unauthorized page
    header("Location: /order_management/dist/dashboard/index.php");
    exit();
}

// Include database connection
include($_SERVER['DOCUMENT_ROOT'] . '/order_management/dist/connection/db_connection.php');

/**
 * SEARCH AND PAGINATION PARAMETERS
 */
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$order_id_filter = isset($_GET['order_id_filter']) ? trim($_GET['order_id_filter']) : '';
$customer_name_filter = isset($_GET['customer_name_filter']) ? trim($_GET['customer_name_filter']) : '';
$phone_filter = isset($_GET['phone_filter']) ? trim($_GET['phone_filter']) : '';
$user_filter = isset($_GET['user_filter']) ? trim($_GET['user_filter']) : '';
$date_from = isset($_GET['date_from']) ? trim($_GET['date_from']) : '';
$date_to = isset($_GET['date_to']) ? trim($_GET['date_to']) : '';
$status_filter = isset($_GET['status_filter']) ? trim($_GET['status_filter']) : '';
$pay_status_filter = isset($_GET['pay_status_filter']) ? trim($_GET['pay_status_filter']) : '';

$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

/**
 * DATABASE QUERIES
 * Main query to fetch leads (orders with interface='leads')
 */

// Base SQL for counting total records - Updated for better user filtering
$countSql = "SELECT COUNT(*) as total FROM order_header i 
             LEFT JOIN customers c ON i.customer_id = c.customer_id
             LEFT JOIN users u ON i.user_id = u.id
             WHERE i.interface = 'leads'";

// Main query with all required joins - Enhanced for user filtering
$sql = "SELECT i.order_id,
               i.customer_id,
               i.user_id,
               i.issue_date,
               i.due_date,
               i.subtotal,
               i.discount,
               i.total_amount,
               i.currency,
               i.status,
               i.pay_status,
               i.pay_by,
               i.pay_date,
               i.slip,
               i.created_at,
               i.updated_at,
               i.notes,
               i.mobile,
               i.full_name,
               i.address_line1,
               i.address_line2,
               c.name as customer_name, 
               c.phone as customer_phone,
               c.email as customer_email,
               u.id as user_id,
               u.name as user_name,
               u.email as user_email,
               u.status as user_status
        FROM order_header i 
        LEFT JOIN customers c ON i.customer_id = c.customer_id
        LEFT JOIN users u ON i.user_id = u.id
        WHERE i.interface = 'leads'";

// Build search conditions
$searchConditions = [];

// General search condition
if (!empty($search)) {
    $searchTerm = $conn->real_escape_string($search);
    $searchConditions[] = "(
                        i.order_id LIKE '%$searchTerm%' OR 
                        c.name LIKE '%$searchTerm%' OR 
                        c.phone LIKE '%$searchTerm%' OR 
                        i.issue_date LIKE '%$searchTerm%' OR 
                        i.due_date LIKE '%$searchTerm%' OR 
                        i.total_amount LIKE '%$searchTerm%' OR
                        i.status LIKE '%$searchTerm%' OR 
                        i.pay_status LIKE '%$searchTerm%' OR
                        u.name LIKE '%$searchTerm%' OR
                        u.email LIKE '%$searchTerm%' OR
                        i.mobile LIKE '%$searchTerm%' OR
                        i.full_name LIKE '%$searchTerm%')";
}

// Specific Order ID filter
if (!empty($order_id_filter)) {
    $orderIdTerm = $conn->real_escape_string($order_id_filter);
    $searchConditions[] = "i.order_id LIKE '%$orderIdTerm%'";
}

// Specific Customer Name filter
if (!empty($customer_name_filter)) {
    $customerNameTerm = $conn->real_escape_string($customer_name_filter);
    $searchConditions[] = "(c.name LIKE '%$customerNameTerm%' OR i.full_name LIKE '%$customerNameTerm%')";
}

// Phone filter - Enhanced to search both customer phone and mobile field
if (!empty($phone_filter)) {
    $phoneTerm = $conn->real_escape_string($phone_filter);
    $searchConditions[] = "(c.phone LIKE '%$phoneTerm%' OR i.mobile LIKE '%$phoneTerm%')";
}

// Simple User filter - Shows only leads assigned to selected user
if (!empty($user_filter)) {
    $userTerm = $conn->real_escape_string($user_filter);
    $searchConditions[] = "i.user_id = '$userTerm'";
}

// Date range filter
if (!empty($date_from)) {
    $dateFromTerm = $conn->real_escape_string($date_from);
    $searchConditions[] = "DATE(i.issue_date) >= '$dateFromTerm'";
}

if (!empty($date_to)) {
    $dateToTerm = $conn->real_escape_string($date_to);
    $searchConditions[] = "DATE(i.issue_date) <= '$dateToTerm'";
}

// Status filter
if (!empty($status_filter)) {
    $statusTerm = $conn->real_escape_string($status_filter);
    $searchConditions[] = "i.status = '$statusTerm'";
}

// Payment Status filter
if (!empty($pay_status_filter)) {
    $payStatusTerm = $conn->real_escape_string($pay_status_filter);
    $searchConditions[] = "i.pay_status = '$payStatusTerm'";
}

// Apply all search conditions
if (!empty($searchConditions)) {
    $finalSearchCondition = " AND (" . implode(' AND ', $searchConditions) . ")";
    $countSql .= $finalSearchCondition;
    $sql .= $finalSearchCondition;
}

// Add ordering and pagination
$sql .= " ORDER BY i.order_id DESC LIMIT $limit OFFSET $offset";

// Execute queries
$countResult = $conn->query($countSql);
$totalRows = 0;
if ($countResult && $countResult->num_rows > 0) {
    $totalRows = $countResult->fetch_assoc()['total'];
}
$totalPages = ceil($totalRows / $limit);
$result = $conn->query($sql);

// Get all users for dropdown filter
$usersQuery = "SELECT id, name, email FROM users WHERE status = 'active' AND id != 1 ORDER BY name ASC";
$usersResult = $conn->query($usersQuery);
$users = [];
if ($usersResult && $usersResult->num_rows > 0) {
    while ($user = $usersResult->fetch_assoc()) {
        $users[] = $user;
    }
}

?>

<!doctype html>
<html lang="en" data-pc-preset="preset-1" data-pc-sidebar-caption="true" data-pc-direction="ltr" dir="ltr" data-pc-theme="light">

<head>
    <title>Leads Management - All Leads</title>
    
    <?php include($_SERVER['DOCUMENT_ROOT'] . '/order_management/dist/include/head.php'); ?>
    
    <!-- Stylesheets -->
    <link rel="stylesheet" href="../assets/css/style.css" id="main-style-link" />
    <link rel="stylesheet" href="../assets/css/orders.css" id="main-style-link" />
    <link rel="stylesheet" href="../assets/css/status-badge-colors.css" id="main-style-link" />
    <link rel="stylesheet" href="../assets/css/message.css" />
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
.print-btn {
    background-color: #28a745;
    color: white;
    border: none;
    padding: 8px 10px;
    border-radius: 4px;
    cursor: pointer;
    font-size: 14px;
    margin-left: 5px;
    transition: background-color 0.3s;
}

.print-btn:hover {
    background-color: #218838;
}

.print-btn:active {
    transform: scale(0.95);
}

.actions {
    white-space: nowrap;
}

.user-info {
    font-size: 13px;
}

.user-name {
    font-weight: 600;
    color: #333;
}

.user-id {
    color: #666;
    font-size: 11px;
}

.user-email {
    color: #888;
    font-size: 11px;
    font-style: italic;
}

.search-helper {
    font-size: 11px;
    color: #666;
    margin-top: 2px;
    font-style: italic;
}

/* User Reassignment Styles */
.user-change-btn {
    padding: 2px 6px;
    font-size: 10px;
    margin-top: 5px;
    cursor: pointer;
    background: #f0f2f5;
    border: 1px solid #dcdfe3;
    border-radius: 3px;
    color: #555;
    transition: all 0.2s;
    display: inline-flex;
    align-items: center;
    gap: 4px;
}

.user-change-btn:hover {
    background: #e4e6e9;
    color: #333;
}

.user-assign-select {
    display: none;
    width: 100%;
    margin-top: 5px;
    padding: 4px;
    font-size: 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.assign-confirm-btn {
    display: none;
    margin-top: 4px;
    padding: 2px 8px;
    font-size: 11px;
    background-color: #007bff;
    color: white;
    border: none;
    border-radius: 3px;
    cursor: pointer;
}

.assign-cancel-btn {
    display: none;
    margin-top: 4px;
    padding: 2px 8px;
    font-size: 11px;
    background-color: #6c757d;
    color: white;
    border: none;
    border-radius: 3px;
    cursor: pointer;
    margin-left: 4px;
}

.user-change-btn {
    padding: 4px 10px;
    font-size: 12px;
    margin-top: 5px;
    cursor: pointer;
    background: #e3f2fd;
    border: 1px solid #90caf9;
    border-radius: 4px;
    color: #1565c0;
    transition: all 0.2s;
    display: inline-flex;
    align-items: center;
    gap: 4px;
}

.user-change-btn:hover {
    background: #bbdefb;
    color: #0d47a1;
}


</style>
</head>

<body>
    <!-- Page Loader -->
    <?php include($_SERVER['DOCUMENT_ROOT'] . '/order_management/dist/include/loader.php'); 
    include($_SERVER['DOCUMENT_ROOT'] . '/order_management/dist/include/navbar.php');
    include($_SERVER['DOCUMENT_ROOT'] . '/order_management/dist/include/sidebar.php');?>

    <div class="pc-container">
        <div class="pc-content">
            
            <!-- Page Header -->
            <div class="page-header">
                <div class="page-block">
                    <div class="page-header-title">
                        <h5 class="mb-0 font-medium">All Leads</h5>
                    </div>
                </div>
            </div>

            <div class="main-content-wrapper">
                
                <!-- Leads Filter Section -->
                <div class="tracking-container">
                   
                    <form class="tracking-form" method="GET" action="">
                        <div class="form-group">
                            <label for="order_id_filter">Order ID</label>
                            <input type="text" id="order_id_filter" name="order_id_filter" 
                                   placeholder="Enter order ID" 
                                   value="<?php echo htmlspecialchars($order_id_filter); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="customer_name_filter">Customer Name</label>
                            <input type="text" id="customer_name_filter" name="customer_name_filter" 
                                   placeholder="Enter customer name" 
                                   value="<?php echo htmlspecialchars($customer_name_filter); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="phone_filter">Phone Number</label>
                            <input type="text" id="phone_filter" name="phone_filter" 
                                   placeholder="Enter phone number" 
                                   value="<?php echo htmlspecialchars($phone_filter); ?>">
                        </div>
                        
                        <!-- Simple User Filter Dropdown -->
                        <div class="form-group">
                            <label for="user_filter">Assigned User</label>
                            <select id="user_filter" name="user_filter">
                                <option value="">All Users</option>
                                <?php foreach ($users as $user): ?>
                                    <option value="<?php echo $user['id']; ?>" 
                                            <?php echo ($user_filter == $user['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($user['name']) . ' (ID: ' . $user['id'] . ')'; ?>
                                    </option>
                                <?php endforeach; ?>
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
                            <label for="status_filter">Status</label>
                               <select id="status_filter" name="status_filter">
                                    <option value="">All Status</option>
                                    <option value="waiting" <?php echo ($status_filter == 'waiting') ? 'selected' : ''; ?>>Waiting</option>
                                    <option value="pickup" <?php echo ($status_filter == 'pickup') ? 'selected' : ''; ?>>Pickup</option>
                                    <option value="processing" <?php echo ($status_filter == 'processing') ? 'selected' : ''; ?>>Processing</option>
                                    <option value="dispatch" <?php echo ($status_filter == 'dispatch') ? 'selected' : ''; ?>>Dispatch</option>
                                    <option value="courier dispatch" <?php echo ($status_filter == 'courier dispatch') ? 'selected' : ''; ?>>Courier Dispatch</option>
                                    <option value="rearrange" <?php echo ($status_filter == 'rearrange') ? 'selected' : ''; ?>>Rearrange</option>
                                    <option value="pending to deliver" <?php echo ($status_filter == 'pending to deliver') ? 'selected' : ''; ?>>Pending to Deliver</option>
                                    <option value="delivered" <?php echo ($status_filter == 'delivered') ? 'selected' : ''; ?>>Delivered</option>
                                    <option value="done" <?php echo ($status_filter == 'done') ? 'selected' : ''; ?>>Completed</option>
                                    <option value="pending" <?php echo ($status_filter == 'pending') ? 'selected' : ''; ?>>Pending</option>
                                    <option value="return" <?php echo ($status_filter == 'return') ? 'selected' : ''; ?>>Return</option>
                                    <option value="return pending" <?php echo ($status_filter == 'return pending') ? 'selected' : ''; ?>>Return Pending</option>
                                    <option value="return complete" <?php echo ($status_filter == 'return complete') ? 'selected' : ''; ?>>Return Complete</option>
                                    <option value="return_handover" <?php echo ($status_filter == 'return_handover') ? 'selected' : ''; ?>>Return Handover</option>
                                    <option value="return transfer" <?php echo ($status_filter == 'return transfer') ? 'selected' : ''; ?>>Return Transfer</option>
                                    <option value="transfer" <?php echo ($status_filter == 'transfer') ? 'selected' : ''; ?>>Transfer</option>
                                    <option value="cancel" <?php echo ($status_filter == 'cancel') ? 'selected' : ''; ?>>Cancel</option>
                                    <option value="removed" <?php echo ($status_filter == 'removed') ? 'selected' : ''; ?>>Removed</option>
                                    <option value="damaged" <?php echo ($status_filter == 'damaged') ? 'selected' : ''; ?>>Damaged</option>
                                    <option value="hold" <?php echo ($status_filter == 'hold') ? 'selected' : ''; ?>>Hold</option>
                                </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="pay_status_filter">Payment Status</label>
                            <select id="pay_status_filter" name="pay_status_filter">
                                <option value="">All Payment Status</option>
                                <option value="paid" <?php echo ($pay_status_filter == 'paid') ? 'selected' : ''; ?>>Paid</option>
                                <option value="unpaid" <?php echo ($pay_status_filter == 'unpaid') ? 'selected' : ''; ?>>Unpaid</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <div class="button-group">
                                <button type="submit" class="search-btn">
                                    <i class="fas fa-search"></i>
                                    Search
                                </button>
                                <button type="button" class="search-btn" onclick="clearFilters()" style="background: #6c757d;">
                                    <i class="fas fa-times"></i>
                                    Clear
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Leads Count Display -->
                <div class="order-count-container">
                    <div class="order-count-number"><?php echo number_format($totalRows); ?></div>
                    <div class="order-count-dash">-</div>
                    <div class="order-count-subtitle">Total Leads</div>
                    
                    <?php if (!empty($user_filter)): ?>
                        <?php
                        // Get the user name for display
                        $displayUserName = '';
                        foreach ($users as $user) {
                            if ($user['id'] == $user_filter) {
                                $displayUserName = $user['name'];
                                break;
                            }
                        }
                        ?>
                       
                    <?php endif; ?>
                </div>

                <!-- Leads Table -->
                <div class="table-wrapper">
                    <!-- Bulk Actions Bar -->
                    <div class="bulk-actions-bar" id="bulkActionsBar" style="display: none;">
                        <div class="bulk-actions-left">
                            <span id="selectedCount">0</span> leads selected
                        </div>
                        <div class="bulk-actions-right">
                            <button class="bulk-btn bulk-assign-btn" onclick="openBulkUserModal()">
                                <i class="fas fa-user-edit"></i> Change Assigned User
                            </button>
                            <button class="bulk-btn bulk-clear-btn" onclick="clearBulkSelection()">
                                <i class="fas fa-times"></i> Clear Selection
                            </button>
                        </div>
                    </div>
                    
                    <table class="orders-table">
                        <thead>
                            <tr>
                                <th>
                                    <input type="checkbox" id="selectAll" onchange="toggleSelectAll()">
                                </th>
                                <th>Order ID</th>
                                <th>Customer Name</th>
                                <th>Phone Number</th>
                                <th>Total Amount</th>
                                <th>Pay Status</th>
                                <th>Status</th>
                                <th>Assigned User</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="leadsTableBody">
                            <?php if ($result && $result->num_rows > 0): ?>
                                <?php while ($row = $result->fetch_assoc()): ?>
                                    <tr>
                                        <!-- Bulk Selection Checkbox -->
                                        <td>
                                            <input type="checkbox" class="order-checkbox" 
                                                   value="<?php echo isset($row['order_id']) ? htmlspecialchars($row['order_id']) : ''; ?>"
                                                   onchange="updateBulkSelection()"
                                                   <?php echo (strtolower($row['status']) !== 'pending') ? 'disabled title="Only pending leads can be reassigned"' : ''; ?>>
                                        </td>
                                        
                                        <!-- Order ID -->
                                        <td class="order-id">
                                            <?php echo isset($row['order_id']) ? htmlspecialchars($row['order_id']) : ''; ?>
                                        </td>
                                        
                                        <!-- Customer Name - Enhanced to show both customer table name and order full_name -->
                                    <!-- Customer Name - Prioritize order_header full_name for leads -->
                                            <td class="customer-name">
                                                <?php 
                                                // For leads interface, prioritize full_name from order_header
                                                $customerName = '';
                                                if (!empty($row['full_name'])) {
                                                    // Primary: Use full_name from order_header (leads data)
                                                    $customerName = $row['full_name'];
                                                } elseif (!empty($row['customer_name'])) {
                                                    // Fallback: Use customer table name if available
                                                    $customerName = $row['customer_name'];
                                                } else {
                                                    $customerName = 'N/A';
                                                }
                                                echo htmlspecialchars($customerName);
                                                ?>
                                            </td>
                                        
                                       
                                        <!-- Phone Number - Prioritize order_header mobile for leads -->
                                        <td class="phone-number">
                                            <?php 
                                            // For leads interface, prioritize mobile from order_header
                                            $phoneNumber = '';
                                            if (!empty($row['mobile'])) {
                                                // Primary: Use mobile from order_header (leads data)
                                                $phoneNumber = $row['mobile'];
                                                // If mobile_2 exists, show both
                                                if (!empty($row['mobile_2'])) {
                                                    $phoneNumber .= ' / ' . $row['mobile_2'];
                                                }
                                            } elseif (!empty($row['customer_phone'])) {
                                                // Fallback: Use customer table phone if available
                                                $phoneNumber = $row['customer_phone'];
                                            } else {
                                                $phoneNumber = 'N/A';
                                            }
                                            echo htmlspecialchars($phoneNumber);
                                            ?>
                                        </td>
                                       
                                        <!-- Total Amount with Currency -->
                                        <td class="amount">
                                            <?php
                                            $amount = isset($row['total_amount']) ? (float)$row['total_amount'] : 0;
                                            $currency = isset($row['currency']) ? $row['currency'] : 'lkr';
                                            $currencySymbol = ($currency == 'usd') ? '$' : 'Rs';
                                            echo $currencySymbol . number_format($amount, 2);
                                            ?>
                                        </td>
                                        
                                        <!-- Payment Status Badge -->
                                        <td>
                                            <?php
                                            $payStatus = isset($row['pay_status']) ? $row['pay_status'] : 'unpaid';
                                            if ($payStatus == 'paid'): ?>
                                                <span class="status-badge pay-status-paid">Paid</span>
                                            <?php else: ?>
                                                <span class="status-badge pay-status-unpaid">Unpaid</span>
                                            <?php endif; ?>
                                        </td>
                                        
                                        <!-- Order Status Badge -->
                                        <td>
                                            <?php
                                            $status = isset($row['status']) ? $row['status'] : '';
                                            $statusText = '';
                                            $badgeClass = '';
                                            
                                            switch ($status) {
                                                case 'pending':
                                                    $statusText = 'Pending';
                                                    $badgeClass = 'status-pending';
                                                    break;
                                                case 'waiting':
                                                    $statusText = 'Waiting';
                                                    $badgeClass = 'status-waiting';
                                                    break;
                                                case 'pickup':
                                                    $statusText = 'Pickup';
                                                    $badgeClass = 'status-pickup';
                                                    break;
                                                case 'processing':
                                                    $statusText = 'Processing';
                                                    $badgeClass = 'status-processing';
                                                    break;
                                                case 'dispatch':
                                                    $statusText = 'Dispatched';
                                                    $badgeClass = 'status-dispatched';
                                                    break;
                                                case 'pending to deliver':
                                                case 'reschedule':
                                                case 'date changed':
                                                    $statusText = 'Pending to Deliver';
                                                    $badgeClass = 'status-pending-deliver';
                                                    break;
                                                case 'rearrange':
                                                    $statusText = 'Rearrange';
                                                    $badgeClass = 'status-rearrange';
                                                    break;
                                                case 'return':
                                                    $statusText = 'Return';
                                                    $badgeClass = 'status-return';
                                                    break;
                                                case 'return complete':
                                                    $statusText = 'Return Complete';
                                                    $badgeClass = 'status-return-complete';
                                                    break;
                                                case 'return_handover': 
                                                    $statusText = 'Return Handover';
                                                    $badgeClass = 'status-return-handover';
                                                    break;
                                                case 'delivered':
                                                    $statusText = 'Delivered';
                                                    $badgeClass = 'status-delivered';
                                                    break;
                                                case 'done':
                                                    $statusText = 'Completed';
                                                    $badgeClass = 'status-completed';
                                                    break;
                                                case 'cancel':
                                                    $statusText = 'Cancelled';
                                                    $badgeClass = 'status-cancelled';
                                                    break;
                                                default:
                                                    $statusText = $status;
                                                    $badgeClass = 'status-default';
                                            }
                                            ?>
                                            <span class="status-badge <?php echo $badgeClass; ?>"><?php echo $statusText; ?></span>
                                        </td>
                                        
                                        <!-- Enhanced User Information -->
                                        <td class="user-info" id="user-info-<?php echo htmlspecialchars($row['order_id']); ?>">
                                                <div class="user-display">
                                                    <?php if (isset($row['user_name']) && !empty($row['user_name'])): ?>
                                                        <div class="user-name">
                                                        <?php echo htmlspecialchars($row['user_name']); ?>
                                                        <span class="user-id">(ID: <?php echo htmlspecialchars($row['user_id']); ?>)</span>
                                                    </div>
                                                    <?php else: ?>
                                                        <span style="color: #999;">Unassigned</span>
                                                    <?php endif; ?>
                                                    
                                                    <?php if (strtolower($row['status']) === 'pending'): ?>
                                                        <button type="button" class="user-change-btn" onclick="openUserModal('<?php echo htmlspecialchars($row['order_id']); ?>', '<?php echo htmlspecialchars($row['user_id']); ?>', '<?php echo htmlspecialchars($row['user_name']); ?>')">
                                                            <i class="fas fa-user-edit"></i> Change
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                        </td>
                                        
                                        <!-- Action Buttons -->
                                        <td class="actions">
                                            <button class="action-btn view-btn" title="View Lead Details" 
                                                    onclick="openLeadModal('<?php echo isset($row['order_id']) ? htmlspecialchars($row['order_id']) : ''; ?>')">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <!-- Print Button -->
                                            <button class="action-btn print-btn" title="Print Order" 
                                                    onclick="printOrder('<?php echo isset($row['order_id']) ? htmlspecialchars($row['order_id']) : ''; ?>')">
                                                <i class="fas fa-print"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="9" class="text-center" style="padding: 40px; text-align: center; color: #666;">
                                        <?php if (!empty($user_filter)): ?>
                                            No leads found assigned to the selected user
                                        <?php else: ?>
                                            No leads found
                                        <?php endif; ?>
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
                            <button class="page-btn" onclick="window.location.href='?page=<?php echo $page - 1; ?>&limit=<?php echo $limit; ?>&order_id_filter=<?php echo urlencode($order_id_filter); ?>&customer_name_filter=<?php echo urlencode($customer_name_filter); ?>&phone_filter=<?php echo urlencode($phone_filter); ?>&user_filter=<?php echo urlencode($user_filter); ?>&date_from=<?php echo urlencode($date_from); ?>&date_to=<?php echo urlencode($date_to); ?>&status_filter=<?php echo urlencode($status_filter); ?>&pay_status_filter=<?php echo urlencode($pay_status_filter); ?>&search=<?php echo urlencode($search); ?>'">
                                <i class="fas fa-chevron-left"></i>
                            </button>
                        <?php endif; ?>
                        
                        <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                            <button class="page-btn <?php echo ($i == $page) ? 'active' : ''; ?>" 
                                    onclick="window.location.href='?page=<?php echo $i; ?>&limit=<?php echo $limit; ?>&order_id_filter=<?php echo urlencode($order_id_filter); ?>&customer_name_filter=<?php echo urlencode($customer_name_filter); ?>&phone_filter=<?php echo urlencode($phone_filter); ?>&user_filter=<?php echo urlencode($user_filter); ?>&date_from=<?php echo urlencode($date_from); ?>&date_to=<?php echo urlencode($date_to); ?>&status_filter=<?php echo urlencode($status_filter); ?>&pay_status_filter=<?php echo urlencode($pay_status_filter); ?>&search=<?php echo urlencode($search); ?>'">
                                <?php echo $i; ?>
                            </button>
                        <?php endfor; ?>
                        
                        <?php if ($page < $totalPages): ?>
                            <button class="page-btn" onclick="window.location.href='?page=<?php echo $page + 1; ?>&limit=<?php echo $limit; ?>&order_id_filter=<?php echo urlencode($order_id_filter); ?>&customer_name_filter=<?php echo urlencode($customer_name_filter); ?>&phone_filter=<?php echo urlencode($phone_filter); ?>&user_filter=<?php echo urlencode($user_filter); ?>&date_from=<?php echo urlencode($date_from); ?>&date_to=<?php echo urlencode($date_to); ?>&status_filter=<?php echo urlencode($status_filter); ?>&pay_status_filter=<?php echo urlencode($pay_status_filter); ?>&search=<?php echo urlencode($search); ?>'">
                                <i class="fas fa-chevron-right"></i>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Lead View Modal -->
    <?php include($_SERVER['DOCUMENT_ROOT'] . '/order_management/dist/include/order_view_modal.php'); ?>

    <!-- User Change Modal -->
    <div class="modal-overlay" id="userChangeModal" style="display: none; align-items: center;">
        <div class="modal-container" style="width: 200px; height: 300px; margin: 0 auto;">
            <div class="modal-header">
                <h3 class="modal-title">
                    <i class="fas fa-user-edit"></i>
                    Change Assigned User
                </h3>
                <button class="modal-close" onclick="closeUserModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body" style="padding-bottom: 20px;">
                <div style="font-size: 14px; color: #666; margin-bottom: 10px;">
                    Current User: <strong id="modalCurrentUser">-</strong>
                </div>
                <select class="user-modal-select select2" id="modalUserSelect" style="width: 100%; padding: 10px; font-size: 14px; border: 1px solid #ddd; border-radius: 4px;">
                    <option value="">Select User</option>
                    <?php foreach ($users as $user): ?>
                        <option value="<?php echo $user['id']; ?>">
                            <?php echo htmlspecialchars($user['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="modal-footer">
                <button type="button" class="modal-btn modal-btn-secondary" onclick="closeUserModal()">Cancel</button>
                <button type="button" class="modal-btn modal-btn-primary" id="modalConfirmBtn" onclick="confirmUserChange()">Update</button>
            </div>
        </div>
    </div>

    <!-- Bulk User Assignment Modal -->
    <div class="modal-overlay" id="bulkUserModal" style="display: none; align-items: center;">
        <div class="modal-container" style="width: 400px; margin: 0 auto;">
            <div class="modal-header">
                <h3 class="modal-title">
                    <i class="fas fa-users"></i>
                    Bulk Assign User
                </h3>
                <button class="modal-close" onclick="closeBulkUserModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body" style="padding-bottom: 20px;">
                <div style="font-size: 14px; color: #666; margin-bottom: 15px;">
                    <strong id="bulkSelectedCount">0</strong> leads will be reassigned
                </div>
                <div style="font-size: 12px; color: #e74c3c; margin-bottom: 15px; padding: 10px; background: #fdf2f2; border-radius: 5px;">
                    <i class="fas fa-exclamation-triangle"></i> Only leads with "pending" status can be reassigned.
                </div>
                <label style="display: block; margin-bottom: 8px; font-weight: 500; color: #333;">Select New User:</label>
                <select class="select2" id="bulkUserSelect" style="width: 100%; padding: 10px; font-size: 14px; border: 1px solid #ddd; border-radius: 4px;">
                    <option value="">Select User</option>
                    <?php foreach ($users as $user): ?>
                        <option value="<?php echo $user['id']; ?>">
                            <?php echo htmlspecialchars($user['name']) . ' (ID: ' . $user['id'] . ')'; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="modal-footer">
                <button type="button" class="modal-btn modal-btn-secondary" onclick="closeBulkUserModal()">Cancel</button>
                <button type="button" class="modal-btn modal-btn-primary" id="bulkConfirmBtn" onclick="confirmBulkUserChange()">
                    <i class="fas fa-check"></i> Update All
                </button>
            </div>
        </div>
    </div>

    <script>
    // Toast Message System
    class ToastManager {
        constructor() {
            this.createContainer();
        }

        createContainer() {
            if (!document.getElementById('toast-container')) {
                const container = document.createElement('div');
                container.id = 'toast-container';
                container.className = 'toast-container';
                document.body.appendChild(container);
            }
        }

        show(message, type = 'info', duration = 5000) {
            const container = document.getElementById('toast-container');
            const toast = this.createToast(message, type);
            container.appendChild(toast);
            setTimeout(() => { toast.classList.add('show'); }, 100);
            if (duration > 0) {
                setTimeout(() => { this.remove(toast); }, duration);
            }
            return toast;
        }

        createToast(message, type) {
            const toast = document.createElement('div');
            toast.className = `toast ${type}`;
            const icons = {
                success: 'fas fa-check-circle',
                error: 'fas fa-exclamation-circle',
                warning: 'fas fa-exclamation-triangle',
                info: 'fas fa-info-circle'
            };
            const titles = {
                success: 'Success',
                error: 'Error',
                warning: 'Warning',
                info: 'Information'
            };
            toast.innerHTML = `
                <div class="toast-header">
                    <i class="toast-icon ${icons[type] || icons.info}"></i>
                    <span>${titles[type] || titles.info}</span>
                    <button class="toast-close" onclick="toastManager.remove(this.closest('.toast'))">&times;</button>
                </div>
                <div class="toast-body">${message}</div>
            `;
            return toast;
        }

        remove(toast) {
            if (toast && toast.parentNode) {
                toast.classList.remove('show');
                setTimeout(() => {
                    if (toast.parentNode) toast.parentNode.removeChild(toast);
                }, 300);
            }
        }

        success(message, duration = 5000) { return this.show(message, 'success', duration); }
        error(message, duration = 8000) { return this.show(message, 'error', duration); }
        warning(message, duration = 6000) { return this.show(message, 'warning', duration); }
        info(message, duration = 5000) { return this.show(message, 'info', duration); }
    }

    // Initialize toast manager
    const toastManager = new ToastManager();

    // Lead-specific JavaScript functionality
    let currentLeadId = null;

    // User Modal Functions
    let currentModalOrderId = null;
    let currentUserId = null;
    let currentUserName = null;
    
    // Bulk Selection Variables
    let selectedLeadIds = [];

    function openUserModal(orderId, userId, userName) {
        currentModalOrderId = orderId;
        currentUserId = userId;
        currentUserName = userName || 'Unassigned';
        
        const modal = document.getElementById('userChangeModal');
        const currentUserSpan = document.getElementById('modalCurrentUser');
        const userSelect = document.getElementById('modalUserSelect');
        
        currentUserSpan.textContent = currentUserName;
        userSelect.value = userId || '';
        
        modal.style.display = 'flex';
    }

    function closeUserModal() {
        const modal = document.getElementById('userChangeModal');
        modal.style.display = 'none';
        currentModalOrderId = null;
    }
    
    // ============ BULK SELECTION FUNCTIONS ============
    
    function toggleSelectAll() {
        const selectAllCheckbox = document.getElementById('selectAll');
        const checkboxes = document.querySelectorAll('.order-checkbox:not(:disabled)');
        
        checkboxes.forEach(checkbox => {
            checkbox.checked = selectAllCheckbox.checked;
        });
        
        updateBulkSelection();
    }
    
    function updateBulkSelection() {
        const checkboxes = document.querySelectorAll('.order-checkbox:checked');
        selectedLeadIds = Array.from(checkboxes).map(cb => cb.value);
        
        const bulkActionsBar = document.getElementById('bulkActionsBar');
        const selectedCount = document.getElementById('selectedCount');
        
        if (selectedLeadIds.length > 0) {
            bulkActionsBar.style.display = 'flex';
            selectedCount.textContent = selectedLeadIds.length;
        } else {
            bulkActionsBar.style.display = 'none';
        }
        
        // Update select all checkbox state
        const allCheckboxes = document.querySelectorAll('.order-checkbox:not(:disabled)');
        const selectAllCheckbox = document.getElementById('selectAll');
        if (allCheckboxes.length > 0) {
            selectAllCheckbox.checked = selectedLeadIds.length === allCheckboxes.length;
        }
    }
    
    function clearBulkSelection() {
        const checkboxes = document.querySelectorAll('.order-checkbox');
        checkboxes.forEach(checkbox => {
            checkbox.checked = false;
        });
        
        document.getElementById('selectAll').checked = false;
        selectedLeadIds = [];
        
        const bulkActionsBar = document.getElementById('bulkActionsBar');
        bulkActionsBar.style.display = 'none';
    }
    
    // ============ BULK USER ASSIGNMENT FUNCTIONS ============
    
    function openBulkUserModal() {
        if (selectedLeadIds.length === 0) {
            toastManager.warning('Please select at least one lead to reassign');
            return;
        }
        
        document.getElementById('bulkSelectedCount').textContent = selectedLeadIds.length;
        document.getElementById('bulkUserSelect').value = '';
        
        const modal = document.getElementById('bulkUserModal');
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
        
        // Reinitialize Select2 for bulk modal
        if ($('#bulkUserSelect').data('select2')) {
            $('#bulkUserSelect').select2('destroy');
        }
        $('#bulkUserSelect').select2({
            dropdownParent: $('#bulkUserModal'),
            width: '100%',
            placeholder: 'Select User'
        });
    }
    
    function closeBulkUserModal() {
        const modal = document.getElementById('bulkUserModal');
        modal.style.display = 'none';
        document.body.style.overflow = 'auto';
    }
    
    function confirmBulkUserChange() {
        const userSelect = document.getElementById('bulkUserSelect');
        const newUserId = userSelect.value;
        
        if (!newUserId) {
            toastManager.warning('Please select a user');
            return;
        }
        
        const confirmBtn = document.getElementById('bulkConfirmBtn');
        const originalBtnText = confirmBtn.innerHTML;
        confirmBtn.disabled = true;
        confirmBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating...';

        const formData = new FormData();
        formData.append('order_ids', JSON.stringify(selectedLeadIds));
        formData.append('user_id', newUserId);

        fetch('bulk_update_lead_user.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                toastManager.success(data.message);
                closeBulkUserModal();
                clearBulkSelection();
                
                // Reload page to show updated data
                setTimeout(() => {
                    window.location.reload();
                }, 1500);
            } else {
                toastManager.error('Error: ' + data.message);
            }
            confirmBtn.disabled = false;
            confirmBtn.innerHTML = originalBtnText;
        })
        .catch(error => {
            console.error('Error:', error);
            toastManager.error('An unexpected error occurred');
            confirmBtn.disabled = false;
            confirmBtn.innerHTML = originalBtnText;
        });
    }

    function confirmUserChange() {
        const userSelect = document.getElementById('modalUserSelect');
        const newUserId = userSelect.value;
        
        if (!newUserId) {
            alert('Please select a user');
            return;
        }

        const confirmBtn = document.getElementById('modalConfirmBtn');
        const originalBtnText = confirmBtn.innerText;
        confirmBtn.disabled = true;
        confirmBtn.innerText = 'Updating...';

        const formData = new FormData();
        formData.append('order_id', currentModalOrderId);
        formData.append('user_id', newUserId);

        fetch('update_lead_user.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update the UI
                const userInfoCell = document.getElementById('user-info-' + currentModalOrderId);
                const userDisplay = userInfoCell.querySelector('.user-display');
                
                userDisplay.innerHTML = `
                    <div class="user-name">
                        ${data.new_user_name}
                        <span class="user-id">(ID: ${data.new_user_id})</span>
                    </div>
                    <button type="button" class="user-change-btn" onclick="openUserModal('${currentModalOrderId}', '${data.new_user_id}', '${data.new_user_name}')">
                        <i class="fas fa-user-edit"></i> Change
                    </button>
                `;
                
                closeUserModal();
                toastManager.success('Assigned user updated successfully!');
            } else {
                toastManager.error('Error: ' + data.message);
            }
            confirmBtn.disabled = false;
            confirmBtn.innerText = originalBtnText;
        })
        .catch(error => {
            console.error('Error:', error);
            toastManager.error('An unexpected error occurred');
            confirmBtn.disabled = false;
            confirmBtn.innerText = originalBtnText;
        });
    }

    // Clear all filter inputs
    function clearFilters() {
        document.getElementById('order_id_filter').value = '';
        document.getElementById('customer_name_filter').value = '';
        document.getElementById('phone_filter').value = '';
        document.getElementById('user_filter').value = '';
        document.getElementById('date_from').value = '';
        document.getElementById('date_to').value = '';
        document.getElementById('status_filter').value = '';
        document.getElementById('pay_status_filter').value = '';
        
        window.location.href = window.location.pathname;
    }

    // Open lead modal
    function openLeadModal(leadId) {
        if (!leadId || leadId.trim() === '') {
            alert('Lead ID is required to view lead details.');
            return;
        }
        
        console.log('Opening modal for Lead ID:', leadId);
        
        currentLeadId = leadId.trim();
        
        const modal = document.getElementById('orderModal');
        const modalContent = document.getElementById('modalContent');
        const downloadBtn = document.getElementById('downloadBtn');
        const viewPaymentSlipBtn = document.getElementById('viewPaymentSlipBtn');
        
        // Show modal
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
        
        // Show loading state
        modalContent.innerHTML = `
            <div class="modal-loading">
                <i class="fas fa-spinner fa-spin"></i>
                Loading lead details for Lead ID: ${currentLeadId}...
            </div>
        `;
        downloadBtn.style.display = 'none';
        viewPaymentSlipBtn.style.display = 'none';
        
        // Use leads download PHP file
        const fetchUrl = '../leads/leads_download.php?id=' + encodeURIComponent(currentLeadId);
        
        console.log('Fetching from:', fetchUrl);
        
        fetch(fetchUrl, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            }
        })
        .then(response => {
            console.log('Response status:', response.status);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.text();
        })
        .then(data => {
            console.log('Data received:', data.length, 'characters');
            if (data.trim() === '') {
                throw new Error('No data received from server');
            }
            modalContent.innerHTML = data;
            downloadBtn.style.display = 'inline-flex';
            
            // Check for payment slip availability
            checkPaymentSlipAvailability();
        })
        .catch(error => {
            console.error('Error loading lead details:', error);
            modalContent.innerHTML = `
                <div class="modal-error" style="text-align: center; padding: 20px; color: #dc3545;">
                    <i class="fas fa-exclamation-triangle" style="font-size: 2em; margin-bottom: 10px;"></i>
                    <h4>Error Loading Lead Details</h4>
                    <p>Lead ID: ${currentLeadId}</p>
                    <p>Error: ${error.message}</p>
                    <p>Please check if the leads_download.php file exists and is accessible.</p>
                    <button onclick="retryLoadLead()" class="btn btn-primary" style="margin-top: 10px;">
                        <i class="fas fa-redo"></i> Retry
                    </button>
                </div>
            `;
        });
    }

    // Check payment slip availability
    function checkPaymentSlipAvailability() {
        if (!currentLeadId) return;
        
        // Fetch payment slip information from server
        fetch('get_payment_slip_info.php?order_id=' + encodeURIComponent(currentLeadId), {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const viewPaymentSlipBtn = document.getElementById('viewPaymentSlipBtn');
                
                if (data.pay_status === 'paid') {
                    viewPaymentSlipBtn.style.display = 'inline-flex';
                } else {
                    viewPaymentSlipBtn.style.display = 'none';
                }
            } else {
                console.log('No payment slip information available');
            }
        })
        .catch(error => {
            console.error('Error checking payment slip:', error);
        });
    }

    // View payment slip
    function viewPaymentSlip() {
        if (!currentLeadId) {
            alert('No lead selected.');
            return;
        }
        
        // Construct the payment slip URL
        const slipUrl = '/order_management/dist/uploads/payment_slips/' + encodeURIComponent(currentLeadId) + '.jpg';
        
        // Open payment slip in new tab
        window.open(slipUrl, '_blank');
    }

    // Retry loading lead
    function retryLoadLead() {
        if (currentLeadId) {
            openLeadModal(currentLeadId);
        }
    }

    // Close lead modal
    function closeOrderModal() {
        const modal = document.getElementById('orderModal');
        modal.style.display = 'none';
        document.body.style.overflow = 'auto';
        currentLeadId = null;
    }

    // Download lead
    function downloadOrder() {
        if (!currentLeadId) {
            alert('No lead selected for download.');
            return;
        }
        
        const downloadUrl = '../leads/leads_download.php?id=' + encodeURIComponent(currentLeadId) + '&download=1';
        
        console.log('Downloading from:', downloadUrl);
        window.open(downloadUrl, '_blank');
    }

    // Print order function
    function printOrder(orderId) {
        if (!orderId || orderId.trim() === '') {
            alert('Order ID is required to print order.');
            return;
        }
        
        console.log('Printing Order ID:', orderId);
        
        // Construct the print URL
        const printUrl = '/order_management/dist/orders/download_order_print.php?id=' + encodeURIComponent(orderId.trim());

        // Open print page in new window
        const printWindow = window.open(printUrl, '_blank');
        
        // Optional: Auto-print when page loads (uncomment if needed)
        // printWindow.onload = function() {
        //     printWindow.print();
        // };
    }

    // Close modal when clicking outside
    document.getElementById('orderModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeOrderModal();
        }
    });

    // User modal click outside to close
    document.getElementById('userChangeModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeUserModal();
        }
    });
    
    // Bulk user modal click outside to close
    document.getElementById('bulkUserModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeBulkUserModal();
        }
    });

    // Close modal with Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeOrderModal();
            closeUserModal();
            closeBulkUserModal();
        }
    });

    // Initialize page functionality when DOM is loaded
    document.addEventListener('DOMContentLoaded', function() {
        console.log('Leads page loaded, initializing...');
        
        // Initialize Select2 for user modal
        $('#modalUserSelect').select2({
            dropdownParent: $('#userChangeModal'),
            width: '100%',
            placeholder: 'Select User'
        });
        
        const tableRows = document.querySelectorAll('.orders-table tbody tr');
        tableRows.forEach(row => {
            row.addEventListener('mouseenter', function() {
                this.style.transform = 'translateX(2px)';
            });
            
            row.addEventListener('mouseleave', function() {
                this.style.transform = 'translateX(0)';
            });
        });
        
        const modal = document.getElementById('orderModal');
        const modalContent = document.getElementById('modalContent');
        if (!modal || !modalContent) {
            console.error('Modal elements not found! Check HTML structure.');
        }
    });
    </script>

    <!-- Include Footer and Scripts -->
    <?php include($_SERVER['DOCUMENT_ROOT'] . '/order_management/dist/include/footer.php'); ?>
    <?php include($_SERVER['DOCUMENT_ROOT'] . '/order_management/dist/include/scripts.php'); ?>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

</body>
</html>