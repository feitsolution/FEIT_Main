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
$status_filter = isset($_GET['status']) ? trim($_GET['status']) : '';
$date_from = isset($_GET['date_from']) ? trim($_GET['date_from']) : '';
$date_to = isset($_GET['date_to']) ? trim($_GET['date_to']) : '';

// Pagination settings
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 15;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Base SQL for counting total records
$countSql = "SELECT COUNT(*) as total FROM (
    SELECT pb.reference_no 
    FROM production_batches pb 
    JOIN products p ON pb.product_id = p.id";

// Build SQL to group by reference number, product, and sum quantities in each stage
// Also determine an overall status for the batch
$sql = "SELECT 
            pb.reference_no,
            p.name as product_name,
            p.product_code,
            CASE 
                WHEN MAX(CASE WHEN pb.status = 'Active' THEN 1 ELSE 0 END) = 1 THEN 'Active'
                WHEN MAX(CASE WHEN pb.status = 'Canceled' THEN 1 ELSE 0 END) = 1 AND MAX(CASE WHEN pb.status = 'Completed' THEN 1 ELSE 0 END) = 0 THEN 'Canceled'
                ELSE 'Completed'
            END as overall_status,
            SUM(CASE WHEN pb.current_stage = 'Cutting' THEN pb.quantity ELSE 0 END) as cutting_qty,
            SUM(CASE WHEN pb.current_stage = 'Sewing' THEN pb.quantity ELSE 0 END) as sewing_qty,
            SUM(CASE WHEN pb.current_stage = 'Finishing' THEN pb.quantity ELSE 0 END) as finishing_qty,
            SUM(CASE WHEN pb.current_stage = 'Packing' THEN pb.quantity ELSE 0 END) as packing_qty,
            SUM(pb.completed_qty) as total_completed_qty,
            MIN(pb.created_at) as start_date
        FROM production_batches pb 
        JOIN products p ON pb.product_id = p.id";

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

$havingClause = "";
if (!empty($status_filter)) {
    $status_esc = $conn->real_escape_string($status_filter);
    // Since overall_status is calculated, we filter it in HAVING
    if ($status_esc === 'Active') {
        $havingClause = " HAVING MAX(CASE WHEN pb.status = 'Active' THEN 1 ELSE 0 END) = 1";
    } elseif ($status_esc === 'Canceled') {
        $havingClause = " HAVING MAX(CASE WHEN pb.status = 'Canceled' THEN 1 ELSE 0 END) = 1 AND MAX(CASE WHEN pb.status = 'Completed' THEN 1 ELSE 0 END) = 0 AND MAX(CASE WHEN pb.status = 'Active' THEN 1 ELSE 0 END) = 0";
    } elseif ($status_esc === 'Completed') {
        $havingClause = " HAVING MAX(CASE WHEN pb.status = 'Completed' THEN 1 ELSE 0 END) = 1 AND MAX(CASE WHEN pb.status = 'Active' THEN 1 ELSE 0 END) = 0";
    }
}

$countSql .= " GROUP BY pb.reference_no" . $havingClause . ") as subquery";
$sql .= " GROUP BY pb.reference_no, p.name, p.product_code" . $havingClause . " ORDER BY MIN(pb.id) DESC LIMIT $limit OFFSET $offset";

// Execute queries
$countResult = $conn->query($countSql);
$totalRows = 0;
if ($countResult && $countResult->num_rows > 0) {
    if ($countResult->field_count > 1 || $countResult->num_rows > 1) { // It might return multiple rows if not a simple aggregate
         $totalRows = $countResult->num_rows;
    } else {
        $totalRows = $countResult->fetch_assoc()['total'];
    }
}
$totalPages = ceil($totalRows / $limit);
$result = $conn->query($sql);

?>

<!doctype html>
<html lang="en" data-pc-preset="preset-1" data-pc-sidebar-caption="true" data-pc-direction="ltr" dir="ltr" data-pc-theme="light">
<head>
    <title>Production Status - Lily Collection</title>
    <?php include($_SERVER['DOCUMENT_ROOT'] . '/lily_collection/dist/include/head.php'); ?>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/orders.css">
    <link rel="stylesheet" href="../assets/css/customers.css">
    <style>
        .stage-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        .qty-active { background: #e3f2fd; color: #1976d2; }
        .qty-zero { color: #ccc; font-weight: 400; }
        .qty-completed { background: #e8f5e9; color: #2e7d32; }
    </style>
</head>
<body>
    <?php include($_SERVER['DOCUMENT_ROOT'] . '/lily_collection/dist/include/loader.php'); 
    include($_SERVER['DOCUMENT_ROOT'] . '/lily_collection/dist/include/navbar.php');
    include($_SERVER['DOCUMENT_ROOT'] . '/lily_collection/dist/include/sidebar.php');?>
    <div class="pc-container">
        <div class="pc-content">
            <div class="page-header">
                <div class="page-block">
                    <div class="page-header-title">
                        <h5 class="mb-0 font-medium">Production Status Summary</h5>
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
                            <label>Status</label>
                            <select name="status" class="form-control">
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
                                <button type="button" class="search-btn" onclick="window.location.href='production_status.php'" style="background: #6c757d;">
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
                                <th>Status</th>
                                <th class="text-center">Cutting</th>
                                <th class="text-center">Sewing</th>
                                <th class="text-center">Finishing</th>
                                <th class="text-center">Packing</th>
                                <th class="text-center">Completed</th>
                                <th>Start Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result && $result->num_rows > 0): ?>
                                <?php while ($row = $result->fetch_assoc()): ?>
                                    <tr>
                                        <td><span class="badge bg-light-primary text-primary" style="font-size: 0.9rem; font-weight: 600; border: 1px solid rgba(var(--bs-primary-rgb), 0.2);"><?php echo htmlspecialchars($row['reference_no']); ?></span></td>
                                        <td><strong><?php echo htmlspecialchars($row['product_name']); ?></strong><br><small class="text-muted"><?php echo htmlspecialchars($row['product_code']); ?></small></td>
                                        <td>
                                            <?php 
                                            $statusClass = 'pay-status-unpaid'; // Default orange/yellow for Active
                                            if ($row['overall_status'] == 'Completed') $statusClass = 'pay-status-paid'; // Green
                                            if ($row['overall_status'] == 'Canceled') $statusClass = 'status-badge-danger'; // Red/Danger styles
                                            ?>
                                            <span class="status-badge <?php echo $statusClass; ?>"><?php echo $row['overall_status']; ?></span>
                                        </td>
                                        
                                        <td class="text-center">
                                            <span class="stage-badge <?php echo $row['cutting_qty'] > 0 ? 'qty-active' : 'qty-zero'; ?>">
                                                <?php echo $row['cutting_qty']; ?>
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <span class="stage-badge <?php echo $row['sewing_qty'] > 0 ? 'qty-active' : 'qty-zero'; ?>">
                                                <?php echo $row['sewing_qty']; ?>
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <span class="stage-badge <?php echo $row['finishing_qty'] > 0 ? 'qty-active' : 'qty-zero'; ?>">
                                                <?php echo $row['finishing_qty']; ?>
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <span class="stage-badge <?php echo $row['packing_qty'] > 0 ? 'qty-active' : 'qty-zero'; ?>">
                                                <?php echo $row['packing_qty']; ?>
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <span class="stage-badge <?php echo $row['total_completed_qty'] > 0 ? 'qty-completed' : 'qty-zero'; ?>">
                                                <i class="fas fa-check-circle" style="font-size: 0.8rem; margin-right: 2px;"></i> <?php echo $row['total_completed_qty']; ?>
                                            </span>
                                        </td>
                                        
                                        <td><?php echo $row['start_date']; ?></td>
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
                            <button class="page-btn" onclick="window.location.href='?page=<?php echo $page - 1; ?>&limit=<?php echo $limit; ?>&ref_no=<?php echo urlencode($ref_no); ?>&product_name=<?php echo urlencode($product_name); ?>&product_code=<?php echo urlencode($product_code); ?>&status=<?php echo urlencode($status_filter); ?>&date_from=<?php echo urlencode($date_from); ?>&date_to=<?php echo urlencode($date_to); ?>'">
                                <i class="fas fa-chevron-left"></i>
                            </button>
                        <?php endif; ?>
                        
                        <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                            <button class="page-btn <?php echo ($i == $page) ? 'active' : ''; ?>" 
                                    onclick="window.location.href='?page=<?php echo $i; ?>&limit=<?php echo $limit; ?>&ref_no=<?php echo urlencode($ref_no); ?>&product_name=<?php echo urlencode($product_name); ?>&product_code=<?php echo urlencode($product_code); ?>&status=<?php echo urlencode($status_filter); ?>&date_from=<?php echo urlencode($date_from); ?>&date_to=<?php echo urlencode($date_to); ?>'">
                                <?php echo $i; ?>
                            </button>
                        <?php endfor; ?>
                        
                        <?php if ($page < $totalPages): ?>
                            <button class="page-btn" onclick="window.location.href='?page=<?php echo $page + 1; ?>&limit=<?php echo $limit; ?>&ref_no=<?php echo urlencode($ref_no); ?>&product_name=<?php echo urlencode($product_name); ?>&product_code=<?php echo urlencode($product_code); ?>&status=<?php echo urlencode($status_filter); ?>&date_from=<?php echo urlencode($date_from); ?>&date_to=<?php echo urlencode($date_to); ?>'">
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
