<?php
// Start session at the very beginning
session_start();

// Check if user is logged in, if not redirect to login page
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    // Clear any existing output buffers
    if (ob_get_level()) {
        ob_end_clean();
    }
    header("Location: /lily_collection/dist/pages/login.php");
    exit();
}

// Include the database connection file
include($_SERVER['DOCUMENT_ROOT'] . '/lily_collection/dist/connection/db_connection.php');
include($_SERVER['DOCUMENT_ROOT'] . '/lily_collection/dist/include/navbar.php');
include($_SERVER['DOCUMENT_ROOT'] . '/lily_collection/dist/include/sidebar.php');

// Generate CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Fetch all active materials for BOM modal autocomplete
$materialsQuery = "SELECT id, name, material_code FROM material WHERE status = 'active' ORDER BY name ASC";
$materialsResult = $conn->query($materialsQuery);
$materialsData = [];
if ($materialsResult && $materialsResult->num_rows > 0) {
    while ($m = $materialsResult->fetch_assoc()) {
        $materialsData[] = $m;
    }
}

// Handle search and filter parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$product_id_filter = isset($_GET['product_id_filter']) ? trim($_GET['product_id_filter']) : '';
$product_name_filter = isset($_GET['product_name_filter']) ? trim($_GET['product_name_filter']) : '';
$product_code_filter = isset($_GET['product_code_filter']) ? trim($_GET['product_code_filter']) : '';
$description_filter = isset($_GET['description_filter']) ? trim($_GET['description_filter']) : '';
$price_from = isset($_GET['price_from']) ? trim($_GET['price_from']) : '';
$price_to = isset($_GET['price_to']) ? trim($_GET['price_to']) : '';
$status_filter = isset($_GET['status_filter']) ? trim($_GET['status_filter']) : '';
$date_from = isset($_GET['date_from']) ? trim($_GET['date_from']) : '';
$date_to = isset($_GET['date_to']) ? trim($_GET['date_to']) : '';

// Pagination settings
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Base SQL for counting total records
$countSql = "SELECT COUNT(*) as total FROM products";

// Main query
$sql = "SELECT id, name, product_code, description, lkr_price, created_at, status, stock_quantity, low_stock_threshold, 
               (SELECT COUNT(*) FROM product_materials pm WHERE pm.product_id = products.id) as material_count 
        FROM products";

// Build search conditions
$searchConditions = [];

// General search condition - Updated to include product_code and id
if (!empty($search)) {
    $searchTerm = $conn->real_escape_string($search);
    $searchConditions[] = "(
                        id LIKE '%$searchTerm%' OR
                        name LIKE '%$searchTerm%' OR 
                        product_code LIKE '%$searchTerm%' OR 
                        description LIKE '%$searchTerm%' OR 
                        lkr_price LIKE '%$searchTerm%')";
}

// Specific Product ID filter
if (!empty($product_id_filter)) {
    $productIdTerm = $conn->real_escape_string($product_id_filter);
    $searchConditions[] = "id = '$productIdTerm'";
}

// Specific Product Name filter
if (!empty($product_name_filter)) {
    $productNameTerm = $conn->real_escape_string($product_name_filter);
    $searchConditions[] = "name LIKE '%$productNameTerm%'";
}

// Specific Product Code filter
if (!empty($product_code_filter)) {
    $productCodeTerm = $conn->real_escape_string($product_code_filter);
    $searchConditions[] = "product_code LIKE '%$productCodeTerm%'";
}

// Price range filter
if (!empty($price_from)) {
    $priceFromTerm = $conn->real_escape_string($price_from);
    $searchConditions[] = "lkr_price >= '$priceFromTerm'";
}

if (!empty($price_to)) {
    $priceToTerm = $conn->real_escape_string($price_to);
    $searchConditions[] = "lkr_price <= '$priceToTerm'";
}

// Status filter
if (!empty($status_filter)) {
    $statusTerm = $conn->real_escape_string($status_filter);
    $searchConditions[] = "status = '$statusTerm'";
}

// Date range filter
if (!empty($date_from)) {
    $dateFromTerm = $conn->real_escape_string($date_from);
    $searchConditions[] = "DATE(created_at) >= '$dateFromTerm'";
}

if (!empty($date_to)) {
    $dateToTerm = $conn->real_escape_string($date_to);
    $searchConditions[] = "DATE(created_at) <= '$dateToTerm'";
}

// Apply all search conditions
if (!empty($searchConditions)) {
    $finalSearchCondition = " WHERE " . implode(' AND ', $searchConditions);
    $countSql .= $finalSearchCondition;
    $sql .= $finalSearchCondition;
}

// Add ordering and pagination
$sql .= " ORDER BY id DESC LIMIT $limit OFFSET $offset";

// Execute queries
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
    <title>Order Management Admin Portal - Product Management</title>
    
    <?php include($_SERVER['DOCUMENT_ROOT'] . '/lily_collection/dist/include/head.php'); ?>
    
    <!-- Stylesheets -->
    <link rel="stylesheet" href="../assets/css/style.css" id="main-style-link" />
    <link rel="stylesheet" href="../assets/css/orders.css" id="main-style-link" />
    <link rel="stylesheet" href="../assets/css/customers.css" id="main-style-link" />
    <style>
        .autocomplete-wrapper {
            position: relative;
            width: 100%;
        }
        .autocomplete-suggestions {
            position: absolute;
            background: white;
            border: 1px solid #ddd;
            border-top: none;
            max-height: 200px;
            overflow-y: auto;
            width: 100%;
            z-index: 1000;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            display: none;
            border-radius: 0 0 4px 4px;
        }
        .autocomplete-suggestion {
            padding: 8px 12px;
            cursor: pointer;
            border-bottom: 1px solid #f0f0f0;
            font-size: 13px;
        }
        .autocomplete-suggestion:hover {
            background-color: #f8f9fa;
        }
        .autocomplete-suggestion.active {
            background-color: #e9ecef;
        }
        .autocomplete-suggestion:last-child {
            border-bottom: none;
        }
        .no-results {
            padding: 8px 12px;
            color: #999;
            font-size: 13px;
            text-align: center;
        }
        .bom-material-search {
            background-color: #fff !important;
        }
        /* BOM Modal Styles */
        #bomModal .modal-content {
            max-width: 80%;
        }
        .manage-bom-btn {
            width: auto !important;
            padding: 0 16px !important;
            height: 36px;
            font-size: 13px;
            background: #2196F3;
            color: white !important;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
            font-weight: 500;
        }
        .manage-bom-btn:hover {
            background: #1976D2;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(33, 150, 243, 0.4);
        }
        .manage-bom-btn i {
            font-size: 14px;
        }
        #bomModal .modal-body .table-wrapper {
            overflow: visible !important;
        }
        #bomModal .modal-body .orders-table {
            overflow: visible !important;
        }
        .bom-row.active-autocomplete {
            position: relative;
            z-index: 1010 !important;
        }
        .bom-row.active-autocomplete .autocomplete-wrapper {
            z-index: 1011 !important;
        }
        .autocomplete-suggestions {
            z-index: 99999 !important;
        }
    </style>
</head>

<body>
    <!-- Page Loader -->
    <?php include($_SERVER['DOCUMENT_ROOT'] . '/lily_collection/dist/include/loader.php'); ?>

    <div class="pc-container">
        <div class="pc-content">
            
            <!-- Page Header -->
            <div class="page-header">
                <div class="page-block">
                    <div class="page-header-title">
                        <h5 class="mb-0 font-medium">Product Management</h5>
                    </div>
                </div>
            </div>

            <div class="main-content-wrapper">
                
                <!-- Product Filter Section -->
                <div class="tracking-container">
                    <form class="tracking-form" method="GET" action="">
                        <div class="form-group">
                            <label for="product_id_filter">Product ID</label>
                            <input type="number" id="product_id_filter" name="product_id_filter" 
                                   placeholder="Enter product ID" min="1"
                                   value="<?php echo htmlspecialchars($product_id_filter); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="product_name_filter">Product Name</label>
                            <input type="text" id="product_name_filter" name="product_name_filter" 
                                   placeholder="Enter product name" 
                                   value="<?php echo htmlspecialchars($product_name_filter); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="product_code_filter">Product Code</label>
                            <input type="text" id="product_code_filter" name="product_code_filter" 
                                   placeholder="Enter product code" 
                                   value="<?php echo htmlspecialchars($product_code_filter); ?>">
                        </div>
                        
                        <!-- <div class="form-group">
                            <label for="description_filter">Description</label>
                            <input type="text" id="description_filter" name="description_filter" 
                                   placeholder="Enter description" 
                                   value="<?php echo htmlspecialchars($description_filter); ?>">
                        </div> -->
                        
                        <!-- <div class="form-group">
                            <label for="price_from">Price From (LKR)</label>
                            <input type="number" id="price_from" name="price_from" 
                                   placeholder="Min price" step="0.01" min="0"
                                   value="<?php echo htmlspecialchars($price_from); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="price_to">Price To (LKR)</label>
                            <input type="number" id="price_to" name="price_to" 
                                   placeholder="Max price" step="0.01" min="0"
                                   value="<?php echo htmlspecialchars($price_to); ?>">
                        </div> -->
                        
                        <div class="form-group">
                            <label for="status_filter">Status</label>
                            <select id="status_filter" name="status_filter">
                                <option value="">All Status</option>
                                <option value="active" <?php echo ($status_filter == 'active') ? 'selected' : ''; ?>>Active</option>
                                <option value="inactive" <?php echo ($status_filter == 'inactive') ? 'selected' : ''; ?>>Inactive</option>
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

                <!-- Product Count Display -->
                <div class="order-count-container">
                    <div class="order-count-number"><?php echo number_format($totalRows); ?></div>
                    <div class="order-count-dash">-</div>
                    <div class="order-count-subtitle">Total Products</div>
                </div>

                <!-- Products Table -->
                <div class="table-wrapper">
                    <table class="orders-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Product Name</th>
                                <th>Product Code</th>
                                <th>Price (LKR)</th>
                                <?php if (isset($_SESSION['allow_inventory']) && $_SESSION['allow_inventory'] == 1): ?>
                                <th>Stock</th>
                                <?php endif; ?>
                                <th>Status</th>
                                <th>BOM Items</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="productsTableBody">
                            <?php if ($result && $result->num_rows > 0): ?>
                                <?php while ($row = $result->fetch_assoc()): ?>
                                    <tr>
                                        <!-- Product ID -->
                                        <td><?php echo htmlspecialchars($row['id']); ?></td>
                                        
                                        <!-- Product Name -->
                                        <td class="product-name">
                                            <div class="product-info">
                                                <h6 style="margin: 0; font-size: 14px; font-weight: 600;"><?php echo htmlspecialchars($row['name']); ?></h6>
                                            </div>
                                        </td>
                                        
                                        <!-- Product Code -->
                                        <td>
                                            <div class="product-code" style="font-family: monospace; font-size: 13px; color: #495057; background: #f8f9fa; padding: 4px 8px; border-radius: 4px; display: inline-block;">
                                                <?php echo htmlspecialchars($row['product_code'] ?? 'N/A'); ?>
                                            </div>
                                        </td>
                                        
                                        <!-- Price -->
                                        <td>
                                            <div class="price-display">
                                                <span style="font-weight: 600; color: #28a745;">
                                                    LKR <?php echo number_format($row['lkr_price'], 2); ?>
                                                </span>
                                            </div>
                                        </td>
                                        
                                        <?php if (isset($_SESSION['allow_inventory']) && $_SESSION['allow_inventory'] == 1): ?>
                                        <!-- Stock -->
                                        <td>
                                            <div class="stock-display">
                                                <?php 
                                                $stock = (int)$row['stock_quantity'];
                                                $threshold = (int)$row['low_stock_threshold'];
                                                $is_low = $stock <= $threshold;
                                                ?>
                                                <span style="font-weight: 600; color: <?php echo $is_low ? '#dc3545' : '#28a745'; ?>;">
                                                    <?php echo $stock; ?>
                                                </span>
                                                <?php if ($is_low): ?>
                                                    <i class="fas fa-exclamation-triangle" style="color: #dc3545; font-size: 12px;" title="Low Stock"></i>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <?php endif; ?>
                                        
                                        <!-- Status Badge -->
                                        <td>
                                            <?php if ($row['status'] === 'active'): ?>
                                                <span class="status-badge pay-status-paid">Active</span>
                                            <?php else: ?>
                                                <span class="status-badge pay-status-unpaid">Inactive</span>
                                            <?php endif; ?>
                                        </td>

                                        <td id="bom-count-<?= $row['id'] ?>">
                                                <?php if ($row['material_count'] > 0): ?>
                                                    <span class="status-badge pay-status-paid"><?php echo $row['material_count']; ?> materials</span>
                                                <?php else: ?>
                                                    <span class="status-badge pay-status-unpaid">No BOM</span>
                                                <?php endif; ?>
                                            </td>
                                        
                                        <!-- Action Buttons -->
                                        <td class="actions">
                                            <div class="action-buttons-group">
                                                <button type="button" class="action-btn view-btn view-product-btn"
                                                        data-product-id="<?= $row['id'] ?>"
                                                        data-product-name="<?= htmlspecialchars($row['name']) ?>"
                                                        data-product-code="<?= htmlspecialchars($row['product_code'] ?? '') ?>"
                                                        data-product-description="<?= htmlspecialchars($row['description'] ?? '') ?>"
                                                        data-product-price="<?= htmlspecialchars($row['lkr_price']) ?>"
                                                        <?php if (isset($_SESSION['allow_inventory']) && $_SESSION['allow_inventory'] == 1): ?>
                                                        data-product-stock="<?= htmlspecialchars($row['stock_quantity']) ?>"
                                                        data-product-threshold="<?= htmlspecialchars($row['low_stock_threshold']) ?>"
                                                        <?php endif; ?>
                                                        data-product-status="<?= htmlspecialchars($row['status']) ?>"
                                                        data-product-created="<?= htmlspecialchars($row['created_at']) ?>"
                                                        title="View Product Details">
                                                    <i class="fas fa-eye"></i>
                                                </button>

                                                <?php if (isset($_SESSION['allow_inventory']) && $_SESSION['allow_inventory'] == 1): ?>
                                                <button type="button" class="action-btn stock-update-btn" 
                                                        style="background: #17a2b8; color: white;"
                                                        title="Update Stock"
                                                        data-product-id="<?= $row['id'] ?>"
                                                        data-product-name="<?= htmlspecialchars($row['name']) ?>"
                                                        data-product-stock="<?= htmlspecialchars($row['stock_quantity']) ?>"
                                                        onclick="openStockUpdateModal(this)">
                                                    <i class="fas fa-boxes"></i>
                                                </button>
                                                <?php endif; ?>
                                                
                                                <button class="action-btn dispatch-btn" title="Edit Product" 
                                                        onclick="editProduct(<?php echo $row['id']; ?>)">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                          
                                                <!-- Status Toggle Button -->
                                                <button type="button" class="action-btn <?= $row['status'] == 'active' ? 'deactivate-btn' : 'activate-btn' ?> toggle-status-btn"
                                                  data-product-id="<?= $row['id'] ?>"
                                                  data-current-status="<?= $row['status'] ?>"
                                                  data-product-name="<?= htmlspecialchars($row['name']) ?>"
                                                   title="<?= $row['status'] == 'active' ? 'Deactivate Product' : 'Activate Product' ?>"
                                                   data-action="<?= $row['status'] == 'active' ? 'deactivate' : 'activate' ?>">
                                                       <i class="fas <?= $row['status'] == 'active' ? 'fa-toggle-off' : 'fa-toggle-on' ?>"></i>
                                                </button>

                                                <!-- Manage BOM Button -->
                                                <button type="button" class="manage-bom-btn"
                                                        data-id="<?= $row['id'] ?>"
                                                        data-name="<?= htmlspecialchars($row['name'], ENT_QUOTES) ?>"
                                                        title="Manage Bill of materials">
                                                    <i class="fas fa-cog"></i> BOM
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center" style="padding: 40px; text-align: center; color: #666;">
                                        <i class="fas fa-box" style="font-size: 2rem; margin-bottom: 10px; display: block;"></i>
                                        No products found
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
                            <button class="page-btn" onclick="window.location.href='?page=<?php echo $page - 1; ?>&limit=<?php echo $limit; ?>&product_name_filter=<?php echo urlencode($product_name_filter); ?>&product_code_filter=<?php echo urlencode($product_code_filter); ?>&description_filter=<?php echo urlencode($description_filter); ?>&price_from=<?php echo urlencode($price_from); ?>&price_to=<?php echo urlencode($price_to); ?>&status_filter=<?php echo urlencode($status_filter); ?>&date_from=<?php echo urlencode($date_from); ?>&date_to=<?php echo urlencode($date_to); ?>&search=<?php echo urlencode($search); ?>'">
                                <i class="fas fa-chevron-left"></i>
                            </button>
                        <?php endif; ?>
                        
                        <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                            <button class="page-btn <?php echo ($i == $page) ? 'active' : ''; ?>" 
                                    onclick="window.location.href='?page=<?php echo $i; ?>&limit=<?php echo $limit; ?>&product_name_filter=<?php echo urlencode($product_name_filter); ?>&product_code_filter=<?php echo urlencode($product_code_filter); ?>&description_filter=<?php echo urlencode($description_filter); ?>&price_from=<?php echo urlencode($price_from); ?>&price_to=<?php echo urlencode($price_to); ?>&status_filter=<?php echo urlencode($status_filter); ?>&date_from=<?php echo urlencode($date_from); ?>&date_to=<?php echo urlencode($date_to); ?>&search=<?php echo urlencode($search); ?>'">
                                <?php echo $i; ?>
                            </button>
                        <?php endfor; ?>
                        
                        <?php if ($page < $totalPages): ?>
                            <button class="page-btn" onclick="window.location.href='?page=<?php echo $page + 1; ?>&limit=<?php echo $limit; ?>&product_name_filter=<?php echo urlencode($product_name_filter); ?>&product_code_filter=<?php echo urlencode($product_code_filter); ?>&description_filter=<?php echo urlencode($description_filter); ?>&price_from=<?php echo urlencode($price_from); ?>&price_to=<?php echo urlencode($price_to); ?>&status_filter=<?php echo urlencode($status_filter); ?>&date_from=<?php echo urlencode($date_from); ?>&date_to=<?php echo urlencode($date_to); ?>&search=<?php echo urlencode($search); ?>'">
                                <i class="fas fa-chevron-right"></i>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Product Details Modal -->
    <div id="productDetailsModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h4>Product Details</h4>
                <span class="close" onclick="closeProductModal()">&times;</span>
            </div>
            <div class="modal-body">
                <div class="customer-detail-row">
                    <span class="detail-label">Product ID:</span>
                    <span class="detail-value" id="modal-product-id"></span>
                </div>
                <div class="customer-detail-row">
                    <span class="detail-label">Product Name:</span>
                    <span class="detail-value" id="modal-product-name"></span>
                </div>
                <div class="customer-detail-row">
                    <span class="detail-label">Product Code:</span>
                    <span class="detail-value" id="modal-product-code" style="font-family: monospace; background: #f8f9fa; padding: 4px 8px; border-radius: 4px; display: inline-block;"></span>
                </div>
                <div class="customer-detail-row">
                    <span class="detail-label">Description:</span>
                    <span class="detail-value" id="modal-product-description"></span>
                </div>
                <div class="customer-detail-row">
                    <span class="detail-label">Price (LKR):</span>
                    <span class="detail-value" id="modal-product-price"></span>
                </div>
                <?php if (isset($_SESSION['allow_inventory']) && $_SESSION['allow_inventory'] == 1): ?>
                <div class="customer-detail-row">
                    <span class="detail-label">Stock Quantity:</span>
                    <span class="detail-value" id="modal-product-stock"></span>
                </div>
                <div class="customer-detail-row">
                    <span class="detail-label">Low Stock Threshold:</span>
                    <span class="detail-value" id="modal-product-threshold"></span>
                </div>
                <?php endif; ?>
                <div class="customer-detail-row">
                    <span class="detail-label">Status:</span>
                    <span class="detail-value">
                        <span id="modal-product-status" class="status-badge"></span>
                    </span>
                </div>
                <div class="customer-detail-row">
                    <span class="detail-label">Created:</span>
                    <span class="detail-value" id="modal-product-created"></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Status Confirmation Modal -->
    <div id="statusConfirmationModal" class="modal confirmation-modal">
        <div class="modal-content confirmation-modal-content">
            <div class="modal-header">
                <h4>Are you sure?</h4>
                <span class="close" onclick="closeConfirmationModal()">&times;</span>
            </div>
            <div class="modal-body">
                <div class="confirmation-icon">
                    <i class="ti ti-alert-triangle"></i>
                </div>
                <div class="confirmation-text">
                    You are about to <span class="action-highlight" id="action-text"></span> product:
                </div>
                <div class="confirmation-text">
                    <span class="user-name-highlight" id="confirm-product-name"></span>
                </div>
                <div class="modal-buttons">
                    <button class="btn-confirm" id="confirmActionBtn">
                        <span id="confirm-button-text">Yes, deactivate product!</span>
                    </button>
                    <button class="btn-cancel" onclick="closeConfirmationModal()">Cancel</button>
                </div>
            </div>
        </div>
    </div>

    <?php if (isset($_SESSION['allow_inventory']) && $_SESSION['allow_inventory'] == 1): ?>
    <!-- Stock Update Modal -->
    <div id="stockUpdateModal" class="modal">
        <div class="modal-content" style="max-width: 400px;">
            <div class="modal-header">
                <h4>Quick Stock Update</h4>
                <span class="close" onclick="closeStockUpdateModal()">&times;</span>
            </div>
            <div class="modal-body">
                <p style="margin-bottom: 15px;">Updating stock for: <strong id="stock-modal-product-name"></strong></p>
                <p style="margin-bottom: 15px;">Current Stock: <strong id="stock-modal-current-stock"></strong></p>
                
                <div class="form-group" style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 8px; font-weight: 500;">Action Type</label>
                    <div style="display: flex; gap: 20px;">
                        <label style="cursor: pointer; display: flex; align-items: center; gap: 5px;">
                            <input type="radio" name="stock_operation" value="increase" checked> Increase
                        </label>
                        <label style="cursor: pointer; display: flex; align-items: center; gap: 5px;">
                            <input type="radio" name="stock_operation" value="decrease"> Decrease
                        </label>
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 20px;">
                    <label for="adjustment_value" style="display: block; margin-bottom: 8px; font-weight: 500;">Quantity</label>
                    <input type="number" id="adjustment_value" class="form-control" min="1" step="1" value="1" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
                </div>

                <div class="modal-buttons" style="display: flex; gap: 10px; justify-content: flex-end;">
                    <button type="button" class="btn btn-secondary" onclick="closeStockUpdateModal()" style="padding: 8px 16px; border: none; border-radius: 4px; cursor: pointer; background: #6c757d; color: white;">Cancel</button>
                    <button type="button" class="btn btn-primary" id="confirmStockUpdateBtn" style="padding: 8px 16px; border: none; border-radius: 4px; cursor: pointer; background: #17a2b8; color: white;">Update Stock</button>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- BOM Management Modal -->
    <div id="bomModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h4 id="bomModalTitle">Manage Bill of materials</h4>
                <span class="close" onclick="closeBomModal()">&times;</span>
            </div>
            <div class="modal-body">
                <form id="bomForm" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <input type="hidden" name="product_id" id="modal_product_id">

                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                        <h6 style="margin:0; font-weight:600;">materials Required (per 1 unit)</h6>
                        <button type="button" class="search-btn" onclick="addBomRow()" style="padding: 8px 16px; font-size: 13px;">
                            <i class="fas fa-plus"></i> Add material
                        </button>
                    </div>

                    <div class="table-wrapper">
                        <table class="orders-table" id="bomTable">
                            <thead>
                                <tr>
                                    <th style="width: 5%;">#</th>
                                    <th style="width: 45%;">material</th>
                                    <th style="width: 35%;">Qty Required</th>
                                    <th style="width: 15%;">Action</th>
                                </tr>
                            </thead>
                            <tbody id="bomBody">
                                <!-- Loaded via AJAX -->
                            </tbody>
                        </table>
                    </div>

                    <div style="margin-top: 25px; text-align: right; display: flex; justify-content: flex-end; gap: 10px;">
                        <button type="button" class="search-btn" style="background: #6c757d;" onclick="closeBomModal()">Cancel</button>
                        <button type="submit" class="search-btn" id="saveBomBtn">
                            <i class="fas fa-save"></i> Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <?php include($_SERVER['DOCUMENT_ROOT'] . '/lily_collection/dist/include/footer.php'); ?>

    <!-- Scripts -->
    <?php include($_SERVER['DOCUMENT_ROOT'] . '/lily_collection/dist/include/scripts.php'); ?>

    <script>
        function clearFilters() {
            window.location.href = 'product_list.php';
        }

        // Product Details Modal Functions
        function openProductModal(button) {
            const modal = document.getElementById('productDetailsModal');
            
            // Extract data from button attributes
            const productId = button.getAttribute('data-product-id');
            const productName = button.getAttribute('data-product-name');
            const productCode = button.getAttribute('data-product-code');
            const productDescription = button.getAttribute('data-product-description');
            const productPrice = button.getAttribute('data-product-price');
            const productStock = button.getAttribute('data-product-stock');
            const productThreshold = button.getAttribute('data-product-threshold');
            const productStatus = button.getAttribute('data-product-status');
            const productCreated = button.getAttribute('data-product-created');

            // Populate modal fields
            document.getElementById('modal-product-id').textContent = productId;
            document.getElementById('modal-product-name').textContent = productName;
            document.getElementById('modal-product-code').textContent = productCode || 'N/A';
            document.getElementById('modal-product-description').textContent = productDescription || 'N/A';
            document.getElementById('modal-product-price').textContent = 'LKR ' + parseFloat(productPrice).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
            
            if (document.getElementById('modal-product-stock')) {
                document.getElementById('modal-product-stock').textContent = productStock;
                document.getElementById('modal-product-threshold').textContent = productThreshold;
            }
            
            // Set status badge
            const statusElement = document.getElementById('modal-product-status');
            statusElement.textContent = productStatus.charAt(0).toUpperCase() + productStatus.slice(1);
            statusElement.className = 'status-badge ' + (productStatus === 'active' ? 'status-active' : 'status-inactive');
            
            // Format dates
            document.getElementById('modal-product-created').textContent = formatDateTime(productCreated);

            // Show modal
            modal.style.display = 'block';
        }

        function closeProductModal() {
            document.getElementById('productDetailsModal').style.display = 'none';
        }

        function formatDateTime(dateString) {
            if (!dateString) return 'N/A';
            try {
                const date = new Date(dateString);
                return date.toLocaleString();
            } catch (e) {
                return dateString;
            }
        }

        // Event listeners for view buttons
        document.addEventListener('DOMContentLoaded', function() {
            const viewButtons = document.querySelectorAll('.view-product-btn');
            viewButtons.forEach(button => {
                button.addEventListener('click', function() {
                    openProductModal(this);
                });
            });

            // Close modal when clicking outside
            window.onclick = function(event) {
                const modal = document.getElementById('productDetailsModal');
                if (event.target === modal) {
                    closeProductModal();
                }
            }
        });

        function editProduct(productId) {
            window.location.href = 'edit_product.php?id=' + productId;
        }

        function closeConfirmationModal() {
            document.getElementById('statusConfirmationModal').style.display = 'none';
        }

        // Toggle Product Status Functionality
        document.addEventListener('DOMContentLoaded', function() {
            // Add event listeners for toggle status buttons
            const toggleButtons = document.querySelectorAll('.toggle-status-btn');
            toggleButtons.forEach(button => {
                button.addEventListener('click', function() {
                    openStatusConfirmationModal(this);
                });
            });
            
            // Close modal when clicking outside
            window.onclick = function(event) {
                const productModal = document.getElementById('productDetailsModal');
                const statusModal = document.getElementById('statusConfirmationModal');
                
                if (event.target === productModal) {
                    closeProductModal();
                }
                if (event.target === statusModal) {
                    closeConfirmationModal();
                }

                const stockModal = document.getElementById('stockUpdateModal');
                if (event.target === stockModal) {
                    closeStockUpdateModal();
                }
            }
        });

        // Stock Update Functionality
        let currentStockValue = 0;

        function openStockUpdateModal(button) {
            const productId = button.getAttribute('data-product-id');
            const productName = button.getAttribute('data-product-name');
            const productStock = button.getAttribute('data-product-stock');
            
            currentStockValue = parseInt(productStock) || 0;
            
            document.getElementById('stock-modal-product-name').textContent = productName;
            document.getElementById('stock-modal-current-stock').textContent = productStock;
            
            const adjustmentInput = document.getElementById('adjustment_value');
            adjustmentInput.value = 1;
            
            // Explicitly set default operation to increase
            document.querySelector('input[name="stock_operation"][value="increase"]').checked = true;

            const confirmBtn = document.getElementById('confirmStockUpdateBtn');
            confirmBtn.onclick = function() {
                const operation = document.querySelector('input[name="stock_operation"]:checked').value;
                updateProductStock(productId, operation, adjustmentInput.value);
            };
            
            document.getElementById('stockUpdateModal').style.display = 'block';
            adjustmentInput.focus();
            adjustmentInput.select();
        }

        // Validate adjustment value when decreasing stock
        document.addEventListener('DOMContentLoaded', function() {
            const adjustmentInput = document.getElementById('adjustment_value');
            const stockOperationRadios = document.querySelectorAll('input[name="stock_operation"]');
            
            if (adjustmentInput) {
                adjustmentInput.addEventListener('input', function() {
                    const selectedOperation = document.querySelector('input[name="stock_operation"]:checked');
                    if (selectedOperation && selectedOperation.value === 'decrease') {
                        // Check if stock is 0 - can't decrease from 0
                        if (currentStockValue <= 0) {
                            alert('Cannot decrease stock. Current stock is already 0.');
                            this.value = 1;
                            // Switch back to increase
                            document.querySelector('input[name="stock_operation"][value="increase"]').checked = true;
                            return;
                        }
                        const enteredValue = parseInt(this.value) || 0;
                        if (enteredValue > currentStockValue) {
                            this.value = currentStockValue;
                        }
                    }
                });
            }
            
            // Re-validate when switching to decrease operation
            stockOperationRadios.forEach(radio => {
                radio.addEventListener('change', function() {
                    if (this.value === 'decrease') {
                        // Check if stock is 0 - can't decrease from 0
                        if (currentStockValue <= 0) {
                            alert('Cannot decrease stock. Current stock is already 0.');
                            // Switch back to increase
                            document.querySelector('input[name="stock_operation"][value="increase"]').checked = true;
                            return;
                        }
                        const currentValue = parseInt(adjustmentInput.value) || 0;
                        if (currentValue > currentStockValue) {
                            adjustmentInput.value = currentStockValue;
                        }
                    }
                });
            });
        });

        function closeStockUpdateModal() {
            const stockModal = document.getElementById('stockUpdateModal');
            if (stockModal) stockModal.style.display = 'none';
        }

        function updateProductStock(productId, operation, adjustmentValue) {
            if (adjustmentValue === '' || isNaN(adjustmentValue) || parseInt(adjustmentValue) <= 0) {
                alert('Please enter a valid quantity greater than 0.');
                return;
            }

            // Check if trying to decrease when stock is 0
            if (operation === 'decrease' && currentStockValue <= 0) {
                alert('Cannot decrease stock. Current stock is already 0.');
                return;
            }

            const btn = document.getElementById('confirmStockUpdateBtn');
            const originalText = btn.textContent;
            btn.textContent = 'Updating...';
            btn.disabled = true;

            fetch('update_stock_action.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    product_id: productId,
                    operation: operation,
                    adjustment_value: adjustmentValue
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    closeStockUpdateModal();
                    alert('Stock updated successfully!');
                    location.reload(); // Simplest way to reflect all changes including threshold icons
                } else {
                    alert('Error updating stock: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while updating the stock.');
            })
            .finally(() => {
                btn.textContent = originalText;
                btn.disabled = false;
            });
        }

        function openStatusConfirmationModal(button) {
            const productId = button.getAttribute('data-product-id');
            const productName = button.getAttribute('data-product-name');
            const currentStatus = button.getAttribute('data-current-status');
            
            // Determine action based on current status
            const isActive = currentStatus.toLowerCase() === 'active';
            const actionText = isActive ? 'deactivate' : 'activate';
            const buttonText = isActive ? 'Yes, deactivate product!' : 'Yes, activate product!';
            
            // Update modal content
            document.getElementById('action-text').textContent = actionText;
            document.getElementById('confirm-product-name').textContent = productName;
            document.getElementById('confirm-button-text').textContent = buttonText;
            
            // Store data for confirmation
            const confirmBtn = document.getElementById('confirmActionBtn');
            confirmBtn.setAttribute('data-product-id', productId);
            confirmBtn.setAttribute('data-new-status', isActive ? 'inactive' : 'active');
            
            // Add click handler to confirm button
            confirmBtn.onclick = function() {
                toggleProductStatus(productId, isActive ? 'inactive' : 'active');
            };
            
            // Show modal
            document.getElementById('statusConfirmationModal').style.display = 'block';
        }

        function toggleProductStatus(productId, newStatus) {
            fetch('toggle_product_status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    product_id: productId,
                    new_status: newStatus
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Close confirmation modal
                    closeConfirmationModal();
                    
                    // Show success message
                    alert('Product status updated successfully!');
                    
                    // Reload page to reflect changes
                    location.reload();
                } else {
                    alert('Error updating product status: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while updating the product status.');
            });
        }

        // Price range filter validation
        document.addEventListener('DOMContentLoaded', function() {
            const priceFromInput = document.getElementById('price_from');
            const priceToInput = document.getElementById('price_to');
            
            if (priceFromInput && priceToInput) {
                priceFromInput.addEventListener('change', function() {
                    if (this.value && priceToInput.value && parseFloat(this.value) > parseFloat(priceToInput.value)) {
                        alert('From price cannot be greater than To price');
                        this.value = '';
                    }
                });
                
                priceToInput.addEventListener('change', function() {
                    if (this.value && priceFromInput.value && parseFloat(this.value) < parseFloat(priceFromInput.value)) {
                        alert('To price cannot be less than From price');
                        this.value = '';
                    }
                });
            }
        });

        // Date range filter validation
        document.addEventListener('DOMContentLoaded', function() {
            const dateFromInput = document.getElementById('date_from');
            const dateToInput = document.getElementById('date_to');
            
            if (dateFromInput && dateToInput) {
                dateFromInput.addEventListener('change', function() {
                    if (this.value && dateToInput.value && new Date(this.value) > new Date(dateToInput.value)) {
                        alert('From date cannot be later than To date');
                        this.value = '';
                    }
                });
                
                dateToInput.addEventListener('change', function() {
                    if (this.value && dateFromInput.value && new Date(this.value) < new Date(dateFromInput.value)) {
                        alert('To date cannot be earlier than From date');
                        this.value = '';
                    }
                });
            }
        });

        // Enhanced search with debouncing
        let searchTimeout;
        function debounceSearch(func, delay) {
            return function(...args) {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => func.apply(this, args), delay);
            };
        }

        // Auto-submit search form with debouncing
        document.addEventListener('DOMContentLoaded', function() {
            const searchInputs = document.querySelectorAll('#product_name_filter, #description_filter');
            const debouncedSubmit = debounceSearch(function() {
                document.querySelector('.tracking-form').submit();
            }, 500);
            
            searchInputs.forEach(input => {
                input.addEventListener('input', debouncedSubmit);
            });
        });

        // ===================== BOM MODAL LOGIC =====================
        (function() {
            const materialsData = <?php echo json_encode($materialsData); ?>;
            let bomRowCounter = 0;

            // Event delegation for manage-bom-btn clicks
            document.addEventListener('click', function(e) {
                const btn = e.target.closest('.manage-bom-btn');
                if (btn) {
                    const productId = btn.dataset.id;
                    const productName = btn.dataset.name;
                    openBomModal(productId, productName);
                }
            });

            window.openBomModal = function(productId, productName) {
                document.getElementById('bomModalTitle').textContent = `Manage BOM: ${productName}`;
                document.getElementById('modal_product_id').value = productId;
                const tbody = document.getElementById('bomBody');
                tbody.innerHTML = '<tr><td colspan="4" style="text-align: center; padding: 20px;"><i class="fas fa-spinner fa-spin"></i> Loading...</td></tr>';
                document.getElementById('bomModal').style.display = 'block';
                bomRowCounter = 0;

                fetch(`../handover/get_product_bom.php?product_id=${productId}`)
                    .then(response => response.json())
                    .then(data => {
                        tbody.innerHTML = '';
                        if (data.success && data.data.length > 0) {
                            data.data.forEach(item => addBomRow(item));
                        } else {
                            addBomRow();
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching BOM:', error);
                        tbody.innerHTML = '<tr><td colspan="4" style="text-align: center; color: #dc3545; padding: 20px;"><i class="fas fa-exclamation-circle"></i> Failed to load BOM. Please try again.</td></tr>';
                    });
            };

            window.closeBomModal = function() {
                document.getElementById('bomModal').style.display = 'none';
            };

            window.addBomRow = function(data = null) {
                // Validate last row is complete before adding a new one (skip for pre-populated data)
                if (!data) {
                    const existingRows = document.querySelectorAll('#bomBody .bom-row');
                    if (existingRows.length > 0) {
                        const lastRow = existingRows[existingRows.length - 1];
                        const lastmaterialId = lastRow.querySelector('.bom-material-id').value;
                        const lastQty = lastRow.querySelector('.bom-qty-input').value;
                        if (!lastmaterialId || !lastQty) {
                            alert('Please complete the current row (select a material and enter quantity) before adding a new one.');
                            return;
                        }
                    }
                }

                bomRowCounter++;
                const tbody = document.getElementById('bomBody');
                const row = document.createElement('tr');
                row.className = 'bom-row';

                const materialDisplay = data ? `${data.material_name} (${data.material_code})` : '';
                const materialId = data ? data.material_id : '';
                const bomId = data ? data.id : '';
                const quantity = data ? data.quantity : '';

                row.innerHTML = `
                    <td>${bomRowCounter}</td>
                    <td>
                        <div class="autocomplete-wrapper">
                            <input type="text"
                                   class="form-control bom-material-search"
                                   placeholder="Search material..."
                                   value="${materialDisplay}"
                                   autocomplete="off"
                                   required
                                   style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                            <input type="hidden" name="bom[${bomRowCounter - 1}][material_id]"
                                   class="bom-material-id"
                                   value="${materialId}"
                                   required>
                            <div class="autocomplete-suggestions"></div>
                        </div>
                    </td>
                    <td>
                        <input type="number" name="bom[${bomRowCounter - 1}][quantity_required]"
                               min="0" step="1" required
                               class="bom-qty-input" placeholder="Qty"
                               value="${quantity}"
                               style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                        <input type="hidden" name="bom[${bomRowCounter - 1}][id]" value="${bomId}" class="bom-record-id">
                    </td>
                    <td>
                        <button type="button" class="action-btn" style="background: #dc3545; color: white;"
                                onclick="removeBomRow(this)" title="Remove">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                `;
                tbody.appendChild(row);
                materialAutocomplete.initRow(row);
                renumberBomRows();
            };

            window.removeBomRow = function(btn) {
                btn.closest('tr').remove();
                renumberBomRows();
            };

            window.renumberBomRows = function() {
                const rows = document.querySelectorAll('#bomBody .bom-row');
                bomRowCounter = rows.length;
                rows.forEach((row, index) => {
                    row.querySelector('td:first-child').textContent = index + 1;
                    row.querySelector('.bom-material-id').name = `bom[${index}][material_id]`;
                    row.querySelector('.bom-qty-input').name = `bom[${index}][quantity_required]`;
                    row.querySelector('.bom-record-id').name = `bom[${index}][id]`;
                });
            };

            const materialAutocomplete = {
                selectedIndex: -1,
                initRow: function(row) {
                    const searchInput = row.querySelector('.bom-material-search');
                    const idInput = row.querySelector('.bom-material-id');
                    const suggestionsDiv = row.querySelector('.autocomplete-suggestions');

                    searchInput.addEventListener('input', (e) => {
                        const term = e.target.value.trim().toLowerCase();
                        
                        // Get currently selected material IDs from other rows
                        const selectedIds = Array.from(document.querySelectorAll('.bom-material-id'))
                            .map(input => input.value)
                            .filter(val => val !== '' && val !== idInput.value);

                        const filtered = term.length === 0
                            ? materialsData.filter(m => !selectedIds.includes(m.id.toString()))
                            : materialsData.filter(m =>
                                (m.name.toLowerCase().includes(term) || 
                                m.material_code.toLowerCase().includes(term)) &&
                                !selectedIds.includes(m.id.toString())
                            );
                        this.showSuggestions(filtered, suggestionsDiv, searchInput, idInput, row);
                    });

                    searchInput.addEventListener('focus', () => {
                        searchInput.dispatchEvent(new Event('input'));
                    });

                    searchInput.addEventListener('keydown', (e) => {
                        const items = suggestionsDiv.querySelectorAll('.autocomplete-suggestion');
                        if (items.length === 0) return;
                        if (e.key === 'ArrowDown') {
                            e.preventDefault();
                            this.selectedIndex = (this.selectedIndex + 1) % items.length;
                            this.updateSelection(items);
                        } else if (e.key === 'ArrowUp') {
                            e.preventDefault();
                            this.selectedIndex = (this.selectedIndex - 1 + items.length) % items.length;
                            this.updateSelection(items);
                        } else if (e.key === 'Enter') {
                            e.preventDefault();
                            if (this.selectedIndex >= 0) {
                                items[this.selectedIndex].click();
                            } else if (items.length === 1) {
                                items[0].click();
                            }
                        } else if (e.key === 'Escape') {
                            this.hideSuggestions(suggestionsDiv, row);
                        }
                    });

                    searchInput.addEventListener('blur', () => {
                        setTimeout(() => {
                            if (searchInput.value.trim() === '') idInput.value = '';
                            this.hideSuggestions(suggestionsDiv, row);
                        }, 200);
                    });
                },
                showSuggestions: function(filtered, div, input, idInput, row) {
                    if (filtered.length === 0) {
                        div.innerHTML = '<div class="no-results">No results</div>';
                        div.style.display = 'block';
                        row.classList.add('active-autocomplete');
                        return;
                    }
                    div.innerHTML = filtered.map(m => `
                        <div class="autocomplete-suggestion" data-id="${m.id}" data-text="${m.name} (${m.material_code})">
                            <strong>${m.name}</strong> <small>(${m.material_code})</small>
                        </div>
                    `).join('');
                    div.style.display = 'block';
                    row.classList.add('active-autocomplete');
                    this.selectedIndex = -1;
                    div.querySelectorAll('.autocomplete-suggestion').forEach(item => {
                        item.addEventListener('click', () => {
                            const materialId = item.dataset.id;
                            
                            // Double check for duplicates
                            const selectedIds = Array.from(document.querySelectorAll('.bom-material-id'))
                                .map(input => input.value)
                                .filter(val => val !== '' && val !== idInput.value);
                            
                            if (selectedIds.includes(materialId)) {
                                alert('This material is already added to the BOM.');
                                input.value = '';
                                idInput.value = '';
                                this.hideSuggestions(div, row);
                                return;
                            }

                            input.value = item.dataset.text;
                            idInput.value = materialId;
                            this.hideSuggestions(div, row);
                        });
                    });
                },
                hideSuggestions: function(div, row) {
                    div.style.display = 'none';
                    row.classList.remove('active-autocomplete');
                    this.selectedIndex = -1;
                },
                updateSelection: function(items) {
                    items.forEach((item, idx) => idx === this.selectedIndex ? item.classList.add('active') : item.classList.remove('active'));
                }
            };

            document.getElementById('bomForm').addEventListener('submit', function(e) {
                e.preventDefault();
                
                // Final validation for duplicate materials
                const materialIds = Array.from(document.querySelectorAll('.bom-material-id'))
                    .map(input => input.value)
                    .filter(val => val !== '');
                
                if (new Set(materialIds).size !== materialIds.length) {
                    alert('Duplicate materials found in the BOM. Each material can only be added once.');
                    return;
                }

                const btn = document.getElementById('saveBomBtn');
                const originalText = btn.innerHTML;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
                btn.disabled = true;
                fetch('../handover/save_product_materials.php', { method: 'POST', body: new FormData(this) })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(data.message);
                        
                        // Update material count in table
                        const productId = document.getElementById('modal_product_id').value;
                        const rowCount = document.querySelectorAll('#bomBody .bom-row').length;
                        const countTd = document.getElementById('bom-count-' + productId);
                        if (countTd) {
                            if (rowCount > 0) {
                                countTd.innerHTML = `<span class="status-badge pay-status-paid">${rowCount} materials</span>`;
                            } else {
                                countTd.innerHTML = `<span class="status-badge pay-status-unpaid">No BOM</span>`;
                            }
                        }
                        
                        closeBomModal();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(() => alert('Connection error.'))
                .finally(() => {
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                });
            });

            // Close BOM modal on outside click (merged with existing window.onclick)
            const _existingOnClick = window.onclick;
            window.onclick = function(event) {
                if (_existingOnClick) _existingOnClick(event);
                if (event.target == document.getElementById('bomModal')) closeBomModal();
            };
        })();
        // ===================== END BOM MODAL LOGIC =====================
    </script>

</body>
</html>