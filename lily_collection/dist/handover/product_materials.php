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
include($_SERVER['DOCUMENT_ROOT'] . '/lily_collection/dist/include/navbar.php');
include($_SERVER['DOCUMENT_ROOT'] . '/lily_collection/dist/include/sidebar.php');

// Generate CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Handle search and filtering for products
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status_filter = isset($_GET['status_filter']) ? trim($_GET['status_filter']) : 'active';

// Pagination
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Base Query
$countSql = "SELECT COUNT(*) as total FROM products p";
$sql = "SELECT p.id, p.name, p.product_code, p.status, 
               (SELECT COUNT(*) FROM product_materials pm WHERE pm.product_id = p.id) as material_count 
        FROM products p";

$conditions = [];
if (!empty($search)) {
    $searchTerm = $conn->real_escape_string($search);
    $conditions[] = "(p.name LIKE '%$searchTerm%' OR p.product_code LIKE '%$searchTerm%')";
}
if (!empty($status_filter)) {
    $statusTerm = $conn->real_escape_string($status_filter);
    $conditions[] = "p.status = '$statusTerm'";
}

if (!empty($conditions)) {
    $where = " WHERE " . implode(' AND ', $conditions);
    $countSql .= $where;
    $sql .= $where;
}

$sql .= " ORDER BY p.name ASC LIMIT $limit OFFSET $offset";

$countResult = $conn->query($countSql);
$totalRows = $countResult ? $countResult->fetch_assoc()['total'] : 0;
$totalPages = ceil($totalRows / $limit);
$productsResult = $conn->query($sql);

// Fetch all active materials for the autocomplete logic in modal
$materialsQuery = "SELECT id, name, material_code FROM material WHERE status = 'active' ORDER BY name ASC";
$materialsResult = $conn->query($materialsQuery);
$materialsData = [];
if ($materialsResult && $materialsResult->num_rows > 0) {
    while ($m = $materialsResult->fetch_assoc()) {
        $materialsData[] = $m;
    }
}
?>

<!doctype html>
<html lang="en" data-pc-preset="preset-1" data-pc-sidebar-caption="true" data-pc-direction="ltr" dir="ltr" data-pc-theme="light">

<head>
    <title>Order Management Admin Portal - Product materials (BOM)</title>
    
    <?php include($_SERVER['DOCUMENT_ROOT'] . '/lily_collection/dist/include/head.php'); ?>
    
    <link rel="stylesheet" href="../assets/css/style.css" id="main-style-link" />
    <link rel="stylesheet" href="../assets/css/orders.css" />
    <link rel="stylesheet" href="../assets/css/customers.css" />
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

        /* Modal Styles - Using custom prefix to avoid Bootstrap conflicts */
        .fde-modal {
            display: none;
            position: fixed;
            z-index: 9999;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.6);
            backdrop-filter: blur(4px);
        }
        .fde-modal-content {
            background-color: #fefefe;
            margin: 50px auto;
            padding: 0;
            border: none;
            width: 90%;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.4);
            animation: fdeModalSlideIn 0.3s ease;
        }
        @keyframes fdeModalSlideIn {
            from { transform: translateY(-30px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        .fde-modal-header {
            padding: 5px 24px;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%);
            color: white;
            border-radius: 12px 12px 0 0;
        }
        .fde-modal-header h4 {
            margin: 0;
            color: white;
            font-size: 1.2rem;
            font-weight: 600;
        }
        .fde-close {
            color: white;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            line-height: 1;
            opacity: 0.8;
            transition: 0.2s;
        }
        .fde-close:hover {
            opacity: 1;
        }
        .fde-modal-body {
            padding: 10px;
            background: #fff;
            border-radius: 0 0 12px 12px;
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

        /* Fix for autocomplete suggestions getting cut off in modal table */
        .fde-modal-body .table-wrapper {
            overflow: visible !important;
        }
        .fde-modal-body .orders-table {
            overflow: visible !important;
        }
        .autocomplete-wrapper {
            position: relative;
        }
        .autocomplete-suggestions {
            z-index: 99999 !important;
        }
        
        /* Ensure the active row stays on top when suggestions are open */
        .bom-row.active-autocomplete {
            position: relative;
            z-index: 1010 !important;
        }
        .bom-row.active-autocomplete .autocomplete-wrapper {
            z-index: 1011 !important;
        }
    </style>
</head>

<body>
    <?php include($_SERVER['DOCUMENT_ROOT'] . '/lily_collection/dist/include/loader.php'); ?>

    <div class="pc-container">
        <div class="pc-content">
            
            <div class="page-header">
                <div class="page-block">
                    <div class="page-header-title">
                        <h5 class="mb-0 font-medium">Product materials (BOM)</h5>
                    </div>
                </div>
            </div>

                <!-- Product Filter Section -->
                <div class="tracking-container" style="margin-bottom: 20px;">
                    <form class="tracking-form" method="GET" action="">
                        <div class="form-group">
                            <label for="search">Search Product</label>
                            <input type="text" id="search" name="search" 
                                   placeholder="Search by name or code..." 
                                   value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div class="form-group">
                            <label for="status_filter">Status</label>
                            <select id="status_filter" name="status_filter">
                                <option value="active" <?php echo ($status_filter == 'active') ? 'selected' : ''; ?>>Active</option>
                                <option value="inactive" <?php echo ($status_filter == 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                                <option value="" <?php echo ($status_filter == '') ? 'selected' : ''; ?>>All Status</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <div class="button-group" style="display: flex; gap: 10px; margin-top: 25px;">
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

                <!-- Product Count Display -->
                <div class="order-count-container">
                    <div class="order-count-number"><?php echo number_format($totalRows); ?></div>
                    <div class="order-count-dash">-</div>
                    <div class="order-count-subtitle">Total Products</div>
                </div>

                <!-- Product List Table -->

                    <div class="table-wrapper">
                        <table class="orders-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Product Name</th>
                                    <th>Product Code</th>
                                    <th>BOM Items</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="productsTableBody">
                                <?php if ($productsResult && $productsResult->num_rows > 0): ?>
                                    <?php while ($row = $productsResult->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo $row['id']; ?></td>
                                            <td class="product-name">
                                                <div class="product-info">
                                                    <h6 style="margin: 0; font-size: 14px; font-weight: 600;"><?php echo htmlspecialchars($row['name']); ?></h6>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="product-code" style="font-family: monospace; font-size: 13px; color: #495057; background: #f8f9fa; padding: 4px 8px; border-radius: 4px; display: inline-block;">
                                                    <?php echo htmlspecialchars($row['product_code']); ?>
                                                </div>
                                            </td>
                                            <td>
                                                <?php if ($row['material_count'] > 0): ?>
                                                    <span class="status-badge pay-status-paid"><?php echo $row['material_count']; ?> materials</span>
                                                <?php else: ?>
                                                    <span class="status-badge pay-status-unpaid">No BOM</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="actions">
                                                <button type="button" class="manage-bom-btn" 
                                                        data-id="<?php echo $row['id']; ?>" 
                                                        data-name="<?php echo htmlspecialchars($row['name'], ENT_QUOTES); ?>"
                                                        title="Manage Bill of materials">
                                                    <i class="fas fa-cog"></i> Manage BOM
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center" style="padding: 40px; text-align: center; color: #666;">
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
                            Showing <?php echo $totalRows > 0 ? $offset + 1 : 0; ?> to <?php echo min($offset + $limit, $totalRows); ?> of <?php echo $totalRows; ?> entries
                        </div>
                        <div class="pagination-controls">
                            <?php if ($page > 1): ?>
                                <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&status_filter=<?php echo urlencode($status_filter); ?>" class="page-btn">
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                            <?php endif; ?>
                            
                            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status_filter=<?php echo urlencode($status_filter); ?>" 
                                   class="page-btn <?php echo ($i == $page) ? 'active' : ''; ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>
                            
                            <?php if ($page < $totalPages): ?>
                                <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&status_filter=<?php echo urlencode($status_filter); ?>" class="page-btn">
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
               

    <!-- BOM Management Modal -->
    <div id="bomModal" class="fde-modal">
        <div class="fde-modal-content">
            <div class="fde-modal-header">
                <h4 id="bomModalTitle">Manage Bill of materials</h4>
                <span class="fde-close" onclick="closeBomModal()">&times;</span>
            </div>
            <div class="fde-modal-body">
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

            </div>
        </div>
    </div>

    <?php include($_SERVER['DOCUMENT_ROOT'] . '/lily_collection/dist/include/footer.php'); ?>
    <?php include($_SERVER['DOCUMENT_ROOT'] . '/lily_collection/dist/include/scripts.php'); ?>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const materialsData = <?php echo json_encode($materialsData); ?>;
            let bomRowCounter = 0;

            // Event delegation for action buttons - ensures listener stays after table redraws
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

                fetch(`get_product_bom.php?product_id=${productId}`)
                    .then(response => response.json())
                    .then(data => {
                        tbody.innerHTML = '';
                        if (data.success && data.data.length > 0) {
                            data.data.forEach(item => {
                                addBomRow(item);
                            });
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

            window.clearFilters = function() {
                window.location.href = window.location.pathname;
            };

            window.addBomRow = function(data = null) {
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
                               min="0.001" step="0.001" required
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
                    const idInput = row.querySelector('.bom-material-id');
                    const qtyInput = row.querySelector('.bom-qty-input');
                    const recordIdInput = row.querySelector('.bom-record-id');
                    idInput.name = `bom[${index}][material_id]`;
                    qtyInput.name = `bom[${index}][quantity_required]`;
                    recordIdInput.name = `bom[${index}][id]`;
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
                        const filtered = term.length === 0 
                            ? materialsData 
                            : materialsData.filter(m => 
                                m.name.toLowerCase().includes(term) || 
                                m.material_code.toLowerCase().includes(term)
                            );
                        this.showSuggestions(filtered, suggestionsDiv, searchInput, idInput, row);
                    });

                    searchInput.addEventListener('focus', () => {
                        // Show all suggestions on focus if empty or trigger existing search
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
                            input.value = item.dataset.text;
                            idInput.value = item.dataset.id;
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
                const btn = document.getElementById('saveBomBtn');
                const originalText = btn.innerHTML;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
                btn.disabled = true;
                fetch('save_product_materials.php', { method: 'POST', body: new FormData(this) })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(data.message);
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => alert('Connection error.'))
                .finally(() => {
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                });
            });

            window.onclick = function(event) {
                if (event.target == document.getElementById('bomModal')) closeBomModal();
            };
        });
    </script>
</body>
</html>
