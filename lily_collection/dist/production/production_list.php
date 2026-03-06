<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: /lily_collection/dist/pages/login.php");
    exit();
}

include($_SERVER['DOCUMENT_ROOT'] . '/lily_collection/dist/connection/db_connection.php');

// Handle filters
$ref_no = isset($_GET['ref_no']) ? trim($_GET['ref_no']) : '';
$product_name = isset($_GET['product_name']) ? trim($_GET['product_name']) : '';
$product_code = isset($_GET['product_code']) ? trim($_GET['product_code']) : '';
$stage_filter = isset($_GET['stage_filter']) ? trim($_GET['stage_filter']) : '';
$status_filter = isset($_GET['status_filter']) ? trim($_GET['status_filter']) : '';
$date_from = isset($_GET['date_from']) ? trim($_GET['date_from']) : '';
$date_to = isset($_GET['date_to']) ? trim($_GET['date_to']) : '';

// Pagination settings
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 15;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Base SQL for counting total records
$countSql = "SELECT COUNT(*) as total 
             FROM production_batches pb 
             JOIN products p ON pb.product_id = p.id 
             LEFT JOIN users u ON pb.action_by = u.id";

// Build SQL
$sql = "SELECT pb.*, p.name as product_name, p.product_code, u.name as user_name 
        FROM production_batches pb 
        JOIN products p ON pb.product_id = p.id 
        LEFT JOIN users u ON pb.action_by = u.id";

$conditions = [];
if (!empty($ref_no)) {
    $conditions[] = "pb.reference_no LIKE '%" . $conn->real_escape_string($ref_no) . "%'";
}
if (!empty($product_name)) {
    $conditions[] = "p.name LIKE '%" . $conn->real_escape_string($product_name) . "%'";
}
if (!empty($product_code)) {
    $conditions[] = "p.product_code LIKE '%" . $conn->real_escape_string($product_code) . "%'";
}
if (!empty($stage_filter)) {
    $conditions[] = "pb.current_stage = '" . $conn->real_escape_string($stage_filter) . "'";
}
if (!empty($status_filter)) {
    $conditions[] = "pb.status = '" . $conn->real_escape_string($status_filter) . "'";
}
if (!empty($date_from)) {
    $conditions[] = "DATE(pb.created_at) >= '" . $conn->real_escape_string($date_from) . "'";
}
if (!empty($date_to)) {
    $conditions[] = "DATE(pb.created_at) <= '" . $conn->real_escape_string($date_to) . "'";
}

if (!empty($conditions)) {
    $finalSearchCondition = " WHERE " . implode(' AND ', $conditions);
    $countSql .= $finalSearchCondition;
    $sql .= $finalSearchCondition;
}

$sql .= " ORDER BY pb.id DESC LIMIT $limit OFFSET $offset";

// Execute queries
$countResult = $conn->query($countSql);
$totalRows = 0;
if ($countResult && $countResult->num_rows > 0) {
    $totalRows = $countResult->fetch_assoc()['total'];
}
$totalPages = ceil($totalRows / $limit);
$result = $conn->query($sql);

include($_SERVER['DOCUMENT_ROOT'] . '/lily_collection/dist/include/navbar.php');
include($_SERVER['DOCUMENT_ROOT'] . '/lily_collection/dist/include/sidebar.php');
?>

<!doctype html>
<html lang="en" data-pc-preset="preset-1" data-pc-sidebar-caption="true" data-pc-direction="ltr" dir="ltr" data-pc-theme="light">
<head>
    <title>Production Overview - Lily Collection</title>
    <?php include($_SERVER['DOCUMENT_ROOT'] . '/lily_collection/dist/include/head.php'); ?>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/orders.css">
    <link rel="stylesheet" href="../assets/css/customers.css">
</head>
<body>
    <?php include($_SERVER['DOCUMENT_ROOT'] . '/lily_collection/dist/include/loader.php'); ?>
    <div class="pc-container">
        <div class="pc-content">
            <div class="page-header">
                <div class="page-block">
                    <div class="page-header-title">
                        <h5 class="mb-0 font-medium">Production Overview & History</h5>
                    </div>
                </div>
            </div>
            
            <div class="main-content-wrapper">
                <!-- Filter Section -->
                <div class="tracking-container">
                    <form class="tracking-form" method="GET" action="" style="flex-wrap: wrap; gap: 15px;">
                        <div class="form-group" style="flex: 1; min-width: 120px;">
                            <label>Ref No</label>
                            <input type="text" name="ref_no" value="<?php echo htmlspecialchars($ref_no); ?>" placeholder="Ref No">
                        </div>
                        <div class="form-group" style="flex: 1; min-width: 150px;">
                            <label>Product Name</label>
                            <input type="text" name="product_name" value="<?php echo htmlspecialchars($product_name); ?>" placeholder="Product Name">
                        </div>
                        <div class="form-group" style="flex: 1; min-width: 120px;">
                            <label>Product Code</label>
                            <input type="text" name="product_code" value="<?php echo htmlspecialchars($product_code); ?>" placeholder="Product Code">
                        </div>
                        <div class="form-group" style="flex: 1; min-width: 130px;">
                            <label>Stage</label>
                            <select name="stage_filter">
                                <option value="">All Stages</option>
                                <option value="Cutting" <?php echo ($stage_filter == 'Cutting') ? 'selected' : ''; ?>>Cutting</option>
                                <option value="Sewing" <?php echo ($stage_filter == 'Sewing') ? 'selected' : ''; ?>>Sewing</option>
                                <option value="Finishing" <?php echo ($stage_filter == 'Finishing') ? 'selected' : ''; ?>>Finishing</option>
                                <option value="Packing" <?php echo ($stage_filter == 'Packing') ? 'selected' : ''; ?>>Packing</option>
                            </select>
                        </div>
                        <div class="form-group" style="flex: 1; min-width: 130px;">
                            <label>Status</label>
                            <select name="status_filter">
                                <option value="">All Status</option>
                                <option value="Active" <?php echo ($status_filter == 'Active') ? 'selected' : ''; ?>>Active</option>
                                <option value="Completed" <?php echo ($status_filter == 'Completed') ? 'selected' : ''; ?>>Completed</option>
                                <option value="Canceled" <?php echo ($status_filter == 'Canceled') ? 'selected' : ''; ?>>Canceled</option>
                            </select>
                        </div>
                        <div class="form-group" style="flex: 1; min-width: 130px;">
                            <label>From Date</label>
                            <input type="date" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>">
                        </div>
                        <div class="form-group" style="flex: 1; min-width: 130px;">
                            <label>To Date</label>
                            <input type="date" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>">
                        </div>
                        <div class="form-group" style="display: flex; align-items: flex-end;">
                            <div class="button-group">
                                <button type="submit" class="search-btn">
                                    <i class="fas fa-search"></i>
                                    Search
                                </button>
                                <button type="button" class="search-btn" onclick="window.location.href='production_list.php'" style="background: #6c757d;">
                                    <i class="fas fa-times"></i>
                                    Clear
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <div class="table-wrapper">
                    <table class="orders-table">
                        <thead>
                            <tr>
                                <th>Reference No</th>
                                <th>Product</th>
                                <th>Quantity</th>
                                <th>Current Stage</th>
                                <th>Status</th>
                                <th>Action By</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result && $result->num_rows > 0): ?>
                                <?php while ($row = $result->fetch_assoc()): ?>
                                    <tr>
                                        <td><span class="badge bg-light-primary text-primary" style="font-size: 0.9rem; font-weight: 600; border: 1px solid rgba(var(--bs-primary-rgb), 0.2);"><?php echo htmlspecialchars($row['reference_no']); ?></span></td>
                                        <td><?php echo htmlspecialchars($row['product_name']); ?> (<?php echo htmlspecialchars($row['product_code']); ?>)</td>
                                        <td><?php echo $row['quantity']; ?></td>
                                        <td><span class="badge bg-light-primary"><?php echo $row['current_stage']; ?></span></td>
                                        <td>
                                            <?php 
                                            $statusClass = 'pay-status-unpaid';
                                            if ($row['status'] == 'Completed') $statusClass = 'pay-status-paid';
                                            ?>
                                            <span class="status-badge <?php echo $statusClass; ?>"><?php echo $row['status']; ?></span>
                                        </td>
                                        <td><strong><?php echo htmlspecialchars($row['user_name'] ?? 'System'); ?></strong></td>
                                        <td><?php echo $row['created_at']; ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="7" class="text-center">No production batches found.</td></tr>
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
                            <button class="page-btn" onclick="window.location.href='?page=<?php echo $page - 1; ?>&limit=<?php echo $limit; ?>&ref_no=<?php echo urlencode($ref_no); ?>&product_name=<?php echo urlencode($product_name); ?>&product_code=<?php echo urlencode($product_code); ?>&stage_filter=<?php echo urlencode($stage_filter); ?>&status_filter=<?php echo urlencode($status_filter); ?>&date_from=<?php echo urlencode($date_from); ?>&date_to=<?php echo urlencode($date_to); ?>'">
                                <i class="fas fa-chevron-left"></i>
                            </button>
                        <?php endif; ?>
                        
                        <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                            <button class="page-btn <?php echo ($i == $page) ? 'active' : ''; ?>" 
                                    onclick="window.location.href='?page=<?php echo $i; ?>&limit=<?php echo $limit; ?>&ref_no=<?php echo urlencode($ref_no); ?>&product_name=<?php echo urlencode($product_name); ?>&product_code=<?php echo urlencode($product_code); ?>&stage_filter=<?php echo urlencode($stage_filter); ?>&status_filter=<?php echo urlencode($status_filter); ?>&date_from=<?php echo urlencode($date_from); ?>&date_to=<?php echo urlencode($date_to); ?>'">
                                <?php echo $i; ?>
                            </button>
                        <?php endfor; ?>
                        
                        <?php if ($page < $totalPages): ?>
                            <button class="page-btn" onclick="window.location.href='?page=<?php echo $page + 1; ?>&limit=<?php echo $limit; ?>&ref_no=<?php echo urlencode($ref_no); ?>&product_name=<?php echo urlencode($product_name); ?>&product_code=<?php echo urlencode($product_code); ?>&stage_filter=<?php echo urlencode($stage_filter); ?>&status_filter=<?php echo urlencode($status_filter); ?>&date_from=<?php echo urlencode($date_from); ?>&date_to=<?php echo urlencode($date_to); ?>'">
                                <i class="fas fa-chevron-right"></i>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php include($_SERVER['DOCUMENT_ROOT'] . '/lily_collection/dist/include/footer.php'); ?>
    <?php include($_SERVER['DOCUMENT_ROOT'] . '/lily_collection/dist/include/scripts.php'); ?>
</body>
</html>
