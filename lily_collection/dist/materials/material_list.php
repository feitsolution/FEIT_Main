<?php
// Start session at the very beginning
session_start();

// Check if user is logged in, if not redirect to login page
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
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

// Handle search and filter parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$material_id_filter = isset($_GET['material_id_filter']) ? trim($_GET['material_id_filter']) : '';
$material_code_filter = isset($_GET['material_code_filter']) ? trim($_GET['material_code_filter']) : '';
$name_filter = isset($_GET['name_filter']) ? trim($_GET['name_filter']) : '';
$status_filter = isset($_GET['status_filter']) ? trim($_GET['status_filter']) : '';
$date_from = isset($_GET['date_from']) ? trim($_GET['date_from']) : '';
$date_to = isset($_GET['date_to']) ? trim($_GET['date_to']) : '';

// Pagination settings
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Base SQL
$countSql = "SELECT COUNT(*) as total FROM material";
$sql = "SELECT id, material_code, name, stock_quantity, low_stock_threshold, status, created_at, updated_at FROM material";

// Build search conditions
$searchConditions = [];

if (!empty($search)) {
    $searchTerm = $conn->real_escape_string($search);
    $searchConditions[] = "(id LIKE '%$searchTerm%' OR name LIKE '%$searchTerm%' OR material_code LIKE '%$searchTerm%')";
}

if (!empty($material_id_filter)) {
    $idTerm = $conn->real_escape_string($material_id_filter);
    $searchConditions[] = "id LIKE '%$idTerm%'";
}

if (!empty($material_code_filter)) {
    $codeTerm = $conn->real_escape_string($material_code_filter);
    $searchConditions[] = "material_code LIKE '%$codeTerm%'";
}

if (!empty($name_filter)) {
    $nameTerm = $conn->real_escape_string($name_filter);
    $searchConditions[] = "name LIKE '%$nameTerm%'";
}

if (!empty($status_filter)) {
    $statusTerm = $conn->real_escape_string($status_filter);
    $searchConditions[] = "status = '$statusTerm'";
}

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
    <title>Order Management Admin Portal - material Management</title>
    
    <?php include($_SERVER['DOCUMENT_ROOT'] . '/lily_collection/dist/include/head.php'); ?>
    
    <!-- Stylesheets -->
    <link rel="stylesheet" href="../assets/css/style.css" id="main-style-link" />
    <link rel="stylesheet" href="../assets/css/orders.css" />
    <link rel="stylesheet" href="../assets/css/customers.css" />
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
                        <h5 class="mb-0 font-medium">material Management</h5>
                    </div>
                </div>
            </div>

            <div class="main-content-wrapper">
                
                <!-- Filter Section -->
                <div class="tracking-container">
                    <form class="tracking-form" method="GET" action="">
                        <div class="form-group">
                            <label for="material_id_filter">material ID</label>
                            <input type="text" id="material_id_filter" name="material_id_filter" 
                                   placeholder="Enter material ID" 
                                   value="<?php echo htmlspecialchars($material_id_filter); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="material_code_filter">material Code</label>
                            <input type="text" id="material_code_filter" name="material_code_filter" 
                                   placeholder="Enter material code" 
                                   value="<?php echo htmlspecialchars($material_code_filter); ?>">
                        </div>

                        <div class="form-group">
                            <label for="name_filter">material Name</label>
                            <input type="text" id="name_filter" name="name_filter" 
                                   placeholder="Enter material name" 
                                   value="<?php echo htmlspecialchars($name_filter); ?>">
                        </div>
                        
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

                <!-- Count Display -->
                <div class="order-count-container">
                    <div class="order-count-number"><?php echo number_format($totalRows); ?></div>
                    <div class="order-count-dash">-</div>
                    <div class="order-count-subtitle">Total materials</div>
                </div>

                <!-- materials Table -->
                <div class="table-wrapper">
                    <table class="orders-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>material Name</th>
                                <th>material Code</th>
                                <th>Stock</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="materialsTableBody">
                            <?php if ($result && $result->num_rows > 0): ?>
                                <?php while ($row = $result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo $row['id']; ?></td>
                                        

                                        <td class="product-name">
                                            <div class="product-info">
                                                <h6 style="margin: 0; font-size: 14px; font-weight: 600;"><?php echo htmlspecialchars($row['name']); ?></h6>
                                            </div>
                                        </td>
                                        <td>
                                            <span style="font-family: monospace; font-size: 13px; color: #495057; background: #f8f9fa; padding: 4px 8px; border-radius: 4px; display: inline-block;">
                                                <?php echo htmlspecialchars($row['material_code']); ?>
                                            </span>
                                        </td>
                                        
                                        <td>
                                            <?php 
                                            $stock = (int)$row['stock_quantity'];
                                            $threshold = (int)($row['low_stock_threshold']);
                                            $is_low = $stock <= $threshold;
                                            ?>
                                            <div class="stock-display">
                                                <span style="font-weight: 600; color: <?php echo $is_low ? '#dc3545' : '#28a745'; ?>;">
                                                    <?php echo $stock; ?>
                                                </span>
                                                <?php if ($is_low): ?>
                                                    <i class="fas fa-exclamation-triangle" style="color: #dc3545; font-size: 12px; margin-left: 5px;" title="Low Stock - Threshold: <?php echo $threshold; ?>"></i>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        
                                        <td>
                                            <?php if ($row['status'] === 'active'): ?>
                                                <span class="status-badge pay-status-paid">Active</span>
                                            <?php else: ?>
                                                <span class="status-badge pay-status-unpaid">Inactive</span>
                                            <?php endif; ?>
                                        </td>
                                        
                                        <td class="actions">
                                            <div class="action-buttons-group">
                                                <button type="button" class="action-btn view-btn view-material-btn"
                                                        data-material-id="<?= $row['id'] ?>"
                                                        data-material-name="<?= htmlspecialchars($row['name']) ?>"
                                                        data-material-code="<?= htmlspecialchars($row['material_code']) ?>"
                                                        data-material-stock="<?= htmlspecialchars($row['stock_quantity']) ?>"
                                                        data-material-threshold="<?= htmlspecialchars($row['low_stock_threshold']) ?>"
                                                        data-material-status="<?= htmlspecialchars($row['status']) ?>"
                                                        data-material-created="<?= htmlspecialchars($row['created_at']) ?>"
                                                        title="View material Details">
                                                    <i class="fas fa-eye"></i>
                                                </button>

                                                <button class="action-btn dispatch-btn" title="Edit material" 
                                                        onclick="editmaterial(<?php echo $row['id']; ?>)">
                                                    <i class="fas fa-edit"></i>
                                                </button>

                                                <button type="button" class="action-btn <?php echo ($row['status'] === 'active') ? 'deactivate-btn' : 'activate-btn'; ?> toggle-status-btn" 
                                                        title="<?php echo ($row['status'] === 'active') ? 'Deactivate material' : 'Activate material'; ?>"
                                                        data-material-id="<?php echo $row['id']; ?>"
                                                        data-material-name="<?php echo htmlspecialchars($row['name']); ?>"
                                                        data-current-status="<?php echo $row['status']; ?>"
                                                        onclick="openStatusConfirmationModal(this)">
                                                    <i class="fas <?php echo ($row['status'] === 'active') ? 'fa-toggle-on' : 'fa-toggle-off'; ?>"></i>
                                                </button>

                                                <button type="button" class="action-btn stock-update-btn" 
                                                        style="background: #17a2b8; color: white;"
                                                        title="Update Stock"
                                                        data-material-id="<?= $row['id'] ?>"
                                                        data-material-name="<?= htmlspecialchars($row['name']) ?>"
                                                        data-material-stock="<?= (int)$row['stock_quantity'] ?>"
                                                        onclick="openStockUpdateModal(this)">
                                                    <i class="fas fa-boxes"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center" style="padding: 40px; text-align: center; color: #666;">
                                        <i class="fas fa-cubes" style="font-size: 2rem; margin-bottom: 10px; display: block;"></i>
                                        No materials found
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
                            <button class="page-btn" onclick="window.location.href='?page=<?php echo $page - 1; ?>&limit=<?php echo $limit; ?>&material_id_filter=<?php echo urlencode($material_id_filter); ?>&material_code_filter=<?php echo urlencode($material_code_filter); ?>&name_filter=<?php echo urlencode($name_filter); ?>&status_filter=<?php echo urlencode($status_filter); ?>&date_from=<?php echo urlencode($date_from); ?>&date_to=<?php echo urlencode($date_to); ?>&search=<?php echo urlencode($search); ?>'">
                                <i class="fas fa-chevron-left"></i>
                            </button>
                        <?php endif; ?>
                        
                        <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                            <button class="page-btn <?php echo ($i == $page) ? 'active' : ''; ?>" 
                                    onclick="window.location.href='?page=<?php echo $i; ?>&limit=<?php echo $limit; ?>&material_id_filter=<?php echo urlencode($material_id_filter); ?>&material_code_filter=<?php echo urlencode($material_code_filter); ?>&name_filter=<?php echo urlencode($name_filter); ?>&status_filter=<?php echo urlencode($status_filter); ?>&date_from=<?php echo urlencode($date_from); ?>&date_to=<?php echo urlencode($date_to); ?>&search=<?php echo urlencode($search); ?>'">
                                <?php echo $i; ?>
                            </button>
                        <?php endfor; ?>
                        
                        <?php if ($page < $totalPages): ?>
                            <button class="page-btn" onclick="window.location.href='?page=<?php echo $page + 1; ?>&limit=<?php echo $limit; ?>&material_id_filter=<?php echo urlencode($material_id_filter); ?>&material_code_filter=<?php echo urlencode($material_code_filter); ?>&name_filter=<?php echo urlencode($name_filter); ?>&status_filter=<?php echo urlencode($status_filter); ?>&date_from=<?php echo urlencode($date_from); ?>&date_to=<?php echo urlencode($date_to); ?>&search=<?php echo urlencode($search); ?>'">
                                <i class="fas fa-chevron-right"></i>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- material Details Modal -->
    <div id="materialDetailsModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h4>material Details</h4>
                <span class="close" onclick="closematerialModal()">&times;</span>
            </div>
            <div class="modal-body">
                <div class="customer-detail-row">
                    <span class="detail-label">material ID:</span>
                    <span class="detail-value" id="modal-material-id"></span>
                </div>
                <div class="customer-detail-row">
                    <span class="detail-label">material Name:</span>
                    <span class="detail-value" id="modal-material-name"></span>
                </div>
                <div class="customer-detail-row">
                    <span class="detail-label">material Code:</span>
                    <span class="detail-value" id="modal-material-code" style="font-family: monospace; background: #f8f9fa; padding: 4px 8px; border-radius: 4px; display: inline-block;"></span>
                </div>
                <div class="customer-detail-row">
                    <span class="detail-label">Stock Quantity:</span>
                    <span class="detail-value" id="modal-material-stock"></span>
                </div>
                <div class="customer-detail-row">
                    <span class="detail-label">Low Stock Threshold:</span>
                    <span class="detail-value" id="modal-material-threshold"></span>
                </div>
                <div class="customer-detail-row">
                    <span class="detail-label">Status:</span>
                    <span class="detail-value">
                        <span id="modal-material-status" class="status-badge"></span>
                    </span>
                </div>
                <div class="customer-detail-row">
                    <span class="detail-label">Created:</span>
                    <span class="detail-value" id="modal-material-created"></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Stock Update Modal -->
    <div id="stockUpdateModal" class="modal">
        <div class="modal-content" style="max-width: 400px;">
            <div class="modal-header">
                <h4>Update material Stock</h4>
                <span class="close" onclick="closeStockUpdateModal()">&times;</span>
            </div>
            <div class="modal-body">
                <p style="margin-bottom: 15px;">Updating stock for: <strong id="stock-modal-material-name"></strong></p>
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

    <!-- Status Confirmation Modal -->
    <div id="statusConfirmationModal" class="modal confirmation-modal">
        <div class="modal-content confirmation-modal-content">
            <div class="modal-header">
                <h4>Are you sure?</h4>
                <span class="close" onclick="closeConfirmationModal()">&times;</span>
            </div>
            <div class="modal-body">
                <div class="confirmation-icon">
                    <i class="ti ti-alert-triangle" style="font-size: 3rem; color: #f39c12; margin-bottom: 1rem; display: block;"></i>
                </div>
                <div class="confirmation-text">
                    You are about to <span class="action-highlight" id="action-text"></span> material:
                </div>
                <div class="confirmation-text">
                    <span class="user-name-highlight" id="confirm-material-name"></span>
                </div>
                <div class="modal-buttons">
                    <button class="btn-confirm" id="confirmStatusBtn" style="padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; color: white;">
                        <span id="confirm-button-text">Yes, deactivate material!</span>
                    </button>
                    <button class="btn-cancel" onclick="closeConfirmationModal()" style="padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; background: #95a5a6; color: white;">Cancel</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <?php include($_SERVER['DOCUMENT_ROOT'] . '/lily_collection/dist/include/footer.php'); ?>

    <!-- Scripts -->
    <?php include($_SERVER['DOCUMENT_ROOT'] . '/lily_collection/dist/include/scripts.php'); ?>

    <script>
        function clearFilters() {
            window.location.href = 'material_list.php';
        }

        // material Details Modal Functions
        function openmaterialModal(button) {
            const modal = document.getElementById('materialDetailsModal');
            
            const materialId = button.getAttribute('data-material-id');
            const materialName = button.getAttribute('data-material-name');
            const materialCode = button.getAttribute('data-material-code');
            const materialStock = button.getAttribute('data-material-stock');
            const materialThreshold = button.getAttribute('data-material-threshold');
            const materialStatus = button.getAttribute('data-material-status');
            const materialCreated = button.getAttribute('data-material-created');

            document.getElementById('modal-material-id').textContent = materialId;
            document.getElementById('modal-material-name').textContent = materialName;
            document.getElementById('modal-material-code').textContent = materialCode || 'N/A';
            document.getElementById('modal-material-stock').textContent = materialStock;
            document.getElementById('modal-material-threshold').textContent = materialThreshold;

            const statusElement = document.getElementById('modal-material-status');
            statusElement.textContent = materialStatus.charAt(0).toUpperCase() + materialStatus.slice(1);
            statusElement.className = 'status-badge ' + (materialStatus === 'active' ? 'status-active' : 'status-inactive');

            document.getElementById('modal-material-created').textContent = formatDateTime(materialCreated);

            modal.style.display = 'block';
        }

        function closematerialModal() {
            document.getElementById('materialDetailsModal').style.display = 'none';
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
            const viewButtons = document.querySelectorAll('.view-material-btn');
            viewButtons.forEach(button => {
                button.addEventListener('click', function() {
                    openmaterialModal(this);
                });
            });
        });

        function editmaterial(materialId) {
            window.location.href = 'edit_material.php?id=' + materialId;
        }

        let currentStockValue = 0;

        function openStockUpdateModal(button) {
            const materialId = button.getAttribute('data-material-id');
            const materialName = button.getAttribute('data-material-name');
            const materialStock = button.getAttribute('data-material-stock');
            
            currentStockValue = parseInt(materialStock) || 0;
            
            document.getElementById('stock-modal-material-name').textContent = materialName;
            document.getElementById('stock-modal-current-stock').textContent = materialStock;
            
            const adjustmentInput = document.getElementById('adjustment_value');
            adjustmentInput.value = 1;
            document.querySelector('input[name="stock_operation"][value="increase"]').checked = true;

            const confirmBtn = document.getElementById('confirmStockUpdateBtn');
            confirmBtn.onclick = function() {
                const operation = document.querySelector('input[name="stock_operation"]:checked').value;
                updatematerialStock(materialId, operation, adjustmentInput.value);
            };
            
            document.getElementById('stockUpdateModal').style.display = 'block';
            adjustmentInput.focus();
            adjustmentInput.select();
        }

        function closeStockUpdateModal() {
            document.getElementById('stockUpdateModal').style.display = 'none';
        }

        function updatematerialStock(materialId, operation, adjustmentValue) {
            if (adjustmentValue === '' || isNaN(adjustmentValue) || parseInt(adjustmentValue) <= 0) {
                alert('Please enter a valid quantity greater than 0.');
                return;
            }

            if (operation === 'decrease' && parseInt(adjustmentValue) > currentStockValue) {
                alert('Cannot decrease more than current stock (' + currentStockValue + ').');
                return;
            }

            const btn = document.getElementById('confirmStockUpdateBtn');
            const originalText = btn.textContent;
            btn.textContent = 'Updating...';
            btn.disabled = true;

            fetch('update_material_stock.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    material_id: materialId,
                    operation: operation,
                    adjustment_value: adjustmentValue
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    closeStockUpdateModal();
                    alert('Stock updated successfully!');
                    location.reload();
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

        let materialToToggle = null;

        function openStatusConfirmationModal(button) {
            const materialId = button.getAttribute('data-material-id');
            const materialName = button.getAttribute('data-material-name');
            const currentStatus = button.getAttribute('data-current-status');
            
            materialToToggle = materialId;
            
            const action = currentStatus === 'active' ? 'deactivate' : 'activate';
            const actionText = document.getElementById('action-text');
            const materialNameText = document.getElementById('confirm-material-name');
            const confirmBtnText = document.getElementById('confirm-button-text');
            const confirmBtn = document.getElementById('confirmStatusBtn');
            
            actionText.textContent = action;
            actionText.style.color = action === 'deactivate' ? '#e74c3c' : '#28a745';
            materialNameText.textContent = materialName;
            confirmBtnText.textContent = `Yes, ${action} material!`;
            confirmBtn.style.background = action === 'deactivate' ? '#e74c3c' : '#28a745';
            
            confirmBtn.onclick = function() {
                executeToggleStatus(materialId);
            };
            
            document.getElementById('statusConfirmationModal').style.display = 'block';
        }

        function closeConfirmationModal() {
            document.getElementById('statusConfirmationModal').style.display = 'none';
        }

        function executeToggleStatus(materialId) {
            const btn = document.getElementById('confirmStatusBtn');
            const originalText = btn.innerHTML;
            btn.innerHTML = 'Processing...';
            btn.disabled = true;

            fetch('toggle_material_status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    material_id: materialId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    closeConfirmationModal();
                    alert(data.message);
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while toggling the status.');
                btn.innerHTML = originalText;
                btn.disabled = false;
            });
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const detailsModal = document.getElementById('materialDetailsModal');
            const stockModal = document.getElementById('stockUpdateModal');
            const statusModal = document.getElementById('statusConfirmationModal');
            
            if (event.target === detailsModal) {
                closematerialModal();
            }
            if (event.target === stockModal) {
                closeStockUpdateModal();
            }
            if (event.target === statusModal) {
                closeConfirmationModal();
            }
        }
    </script>
</body>
</html>
