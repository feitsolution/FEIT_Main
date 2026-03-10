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
$date_from = isset($_GET['date_from']) ? trim($_GET['date_from']) : '';
$date_to = isset($_GET['date_to']) ? trim($_GET['date_to']) : '';

// Pagination settings
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 15;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$products = $conn->query("SELECT id, name, product_code FROM products WHERE status = 'active' ORDER BY name ASC");

// Base SQL for counting total records
$countSql = "SELECT COUNT(*) as total 
             FROM production_batches pb 
             JOIN products p ON pb.product_id = p.id 
             WHERE pb.current_stage = 'Cutting' AND pb.status = 'Active' AND pb.quantity > 0";

$sql = "SELECT pb.*, p.name as product_name, p.product_code 
        FROM production_batches pb 
        JOIN products p ON pb.product_id = p.id 
        WHERE pb.current_stage = 'Cutting' AND pb.status = 'Active' AND pb.quantity > 0";

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
    $finalSearchCondition = " AND " . implode(' AND ', $conditions);
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
$cutting_batches = $conn->query($sql);

?>

<!doctype html>
<html lang="en" data-pc-preset="preset-1" data-pc-sidebar-caption="true" data-pc-direction="ltr" dir="ltr" data-pc-theme="light">
<head>
    <title>Cutting Stage - Lily Collection</title>
    <?php include($_SERVER['DOCUMENT_ROOT'] . '/lily_collection/dist/include/head.php'); ?>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/orders.css">
    <link rel="stylesheet" href="../assets/css/customers.css">

    <style>
        .start-btn{
            width: 150px !important;
            padding: 8px 18px;
        }

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
                        <h5 class="mb-0 font-medium">Cutting Stage</h5>
                    </div>
                </div>
            </div>

            <!-- Filter Section -->
            <div class="main-content-wrapper">
                <div class="tracking-container">
                    <form id="startProductionForm" class="tracking-form">
                        <div class="form-group" style="flex: 2; position: relative;">
                            <label>Select Product</label>
                            
                            <input type="text" id="product_search" class="form-control" placeholder="Type to search product..." autocomplete="off" style="width: 100%; border: 1px solid #ced4da; border-radius: 4px;" required>
                            <input type="hidden" name="product_id" id="product_id" required>
                            
                            <div id="product_dropdown" style="display: none; position: absolute; top: 100%; left: 0; background: white; border: 1px solid #ced4da; border-top: none; max-height: 200px; overflow-y: auto; width: 100%; z-index: 1000; box-shadow: 0 4px 6px rgba(0,0,0,0.1); border-radius: 0 0 4px 4px;">
                                <?php while($p = $products->fetch_assoc()): ?>
                                    <div class="product-option" data-id="<?php echo $p['id']; ?>" data-name="<?php echo htmlspecialchars($p['name']); ?>" data-code="<?php echo htmlspecialchars($p['product_code']); ?>" style="padding: 10px; cursor: pointer; border-bottom: 1px solid #f0f0f0;">
                                        <strong><?php echo htmlspecialchars($p['name']); ?></strong> (<?php echo htmlspecialchars($p['product_code']); ?>)
                                    </div>
                                <?php endwhile; ?>
                                <div id="no_products_found" style="display: none; padding: 10px; color: #999; text-align: center;">No products found</div>
                            </div>
                        </div>
                        <div class="form-group" style="flex: 2;">
                            <label>Quantity</label>
                            <input type="number" name="quantity" min="1" required placeholder="Enter quantity" class="form-control" style="width: 100%; border: 1px solid #ced4da; border-radius: 4px;">
                        </div>
                        <button type="submit" class="btn btn-primary start-btn">
                            <i class="fas fa-play"></i> Start Cutting
                        </button>
                    </form>
                </div>
<hr>

                <div class="tracking-container mt-6">
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
                                <button type="button" class="search-btn" onclick="window.location.href='cutting.php'" style="background: #6c757d;">
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
                                <th>ID</th>
                                <th>Reference No</th>
                                <th>Product Name</th>
                                <th>Product Code</th>
                                <th>Actual Qty</th>
                                <th>Transfer Qty</th>
                                <th>Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($cutting_batches && $cutting_batches->num_rows > 0): ?>
                                <?php while ($row = $cutting_batches->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo $row['id']; ?></td>
                                        <td><span class="badge bg-light-primary text-primary" style="font-size: 0.9rem; font-weight: 600; border: 1px solid rgba(var(--bs-primary-rgb), 0.2);"><?php echo htmlspecialchars($row['reference_no']); ?></span></td>
                                        <td><strong><?php echo htmlspecialchars($row['product_name']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($row['product_code']); ?></td>
                                        <td><strong><?php echo $row['quantity']; ?></strong></td>
                                        <td>
                                            <input type="number" id="qty_<?php echo $row['id']; ?>" value="<?php echo $row['quantity']; ?>" min="1" max="<?php echo $row['quantity']; ?>" class="form-control form-control-sm" style="width: 80px; display: inline-block;">
                                        </td>
                                        <td><?php echo $row['created_at']; ?></td>
                                        <td>
                                            <div class="action-buttons-group d-flex gap-2">
                                                <button onclick="transitionStage(<?php echo $row['id']; ?>, 'Sewing', '<?php echo htmlspecialchars($row['reference_no']); ?>')" class="btn btn-sm btn-info" title="Move to Sewing">
                                                    <i class="fas fa-arrow-right"></i> Sewing
                                                </button>
                                                <button onclick="cancelBatch(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars($row['reference_no']); ?>')" class="btn btn-sm btn-danger" title="Cancel/Deactivate Batch">
                                                    <i class="fas fa-times"></i> Cancel
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="8" class="text-center">No batches in cutting stage.</td></tr>
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
                            <button class="page-btn" onclick="window.location.href='?page=<?php echo $page - 1; ?>&limit=<?php echo $limit; ?>&ref_no=<?php echo urlencode($ref_no); ?>&product_name=<?php echo urlencode($product_name); ?>&product_code=<?php echo urlencode($product_code); ?>&date_from=<?php echo urlencode($date_from); ?>&date_to=<?php echo urlencode($date_to); ?>'">
                                <i class="fas fa-chevron-left"></i>
                            </button>
                        <?php endif; ?>
                        
                        <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                            <button class="page-btn <?php echo ($i == $page) ? 'active' : ''; ?>" 
                                    onclick="window.location.href='?page=<?php echo $i; ?>&limit=<?php echo $limit; ?>&ref_no=<?php echo urlencode($ref_no); ?>&product_name=<?php echo urlencode($product_name); ?>&product_code=<?php echo urlencode($product_code); ?>&date_from=<?php echo urlencode($date_from); ?>&date_to=<?php echo urlencode($date_to); ?>'">
                                <?php echo $i; ?>
                            </button>
                        <?php endfor; ?>
                        
                        <?php if ($page < $totalPages): ?>
                            <button class="page-btn" onclick="window.location.href='?page=<?php echo $page + 1; ?>&limit=<?php echo $limit; ?>&ref_no=<?php echo urlencode($ref_no); ?>&product_name=<?php echo urlencode($product_name); ?>&product_code=<?php echo urlencode($product_code); ?>&date_from=<?php echo urlencode($date_from); ?>&date_to=<?php echo urlencode($date_to); ?>'">
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
    
    <!-- jQuery -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script>
        $('#startProductionForm').on('submit', function(e) {
            e.preventDefault();
            $.ajax({
                url: 'production_actions.php',
                type: 'POST',
                data: $(this).serialize() + '&action=start_production',
                dataType: 'json',
                success: function(res) {
                    if(res.success) {
                        alert('Production batch started!');
                        location.reload();
                    } else {
                        alert('Error: ' + res.message);
                    }
                }
            });
        });

        function transitionStage(batchId, nextStage, refNo) {
            const qty = $('#qty_' + batchId).val();
            if(!qty || qty <= 0) {
                alert('Please enter a valid quantity');
                return;
            }

            if(confirm('Move ' + qty + ' units from batch ' + refNo + ' to ' + nextStage + '?')) {
                $.ajax({
                    url: 'production_actions.php',
                    type: 'POST',
                    data: {action: 'transition_stage', batch_id: batchId, next_stage: nextStage, quantity: qty},
                    dataType: 'json',
                    success: function(res) {
                        if(res.success) {
                            location.reload();
                        } else {
                            alert('Error: ' + res.message);
                        }
                    }
                });
            }
        }

        function cancelBatch(batchId, refNo) {
            if(confirm('Are you sure you want to cancel the batch ' + refNo + '? This action cannot be undone and is only possible if no parts of the batch have moved to the next stage.')) {
                $.ajax({
                    url: 'production_actions.php',
                    type: 'POST',
                    data: {action: 'toggle_inactive', batch_id: batchId},
                    dataType: 'json',
                    success: function(res) {
                        if(res.success) {
                            alert('Batch successfully canceled/deactivated.');
                            location.reload();
                        } else {
                            alert('Error: ' + res.message);
                        }
                    }
                });
            }
        }

        // Product Search Autocomplete
        const productSearch = document.getElementById('product_search');
        const productId = document.getElementById('product_id');
        const productDropdown = document.getElementById('product_dropdown');
        const productOptions = document.querySelectorAll('.product-option');
        
        // Show dropdown when input is focused or clicked
        productSearch.addEventListener('focus', function() {
            this.select(); 
            // If empty, show all products
            if (this.value === '') { 
               filterProducts(''); 
            } else {
               filterProducts(this.value);
            }
            productDropdown.style.display = 'block';
        });
        
        productSearch.addEventListener('click', function() {
            this.select(); 
            // If empty, show all products
            if (this.value === '') { 
               filterProducts(''); 
            } else {
               filterProducts(this.value);
            }
            productDropdown.style.display = 'block';
        });
        
        productSearch.addEventListener('input', function() {
            // Unset product_id if the user alters the text manually
            productId.value = '';
            filterProducts(this.value);
            productDropdown.style.display = 'block';
        });

        // Ensure product is actually selected before submit
        $('#startProductionForm').on('submit', function(e) {
            if(!productId.value) {
                e.preventDefault();
                alert('Please select a valid product from the dropdown list.');
                return false;
            }
        });
        
        // Filter products based on search term
        function filterProducts(searchTerm) {
            const term = searchTerm.toLowerCase().trim();
            const noProductsFound = document.getElementById('no_products_found');
            let hasVisibleOptions = false;
            
            productOptions.forEach(option => {
                const name = option.dataset.name.toLowerCase();
                const code = option.dataset.code.toLowerCase();
                const combined = (name + ' (' + code + ')').toLowerCase();
                
                if (term === '' || name.includes(term) || code.includes(term) || combined.includes(term)) {
                    option.style.display = 'block';
                    hasVisibleOptions = true;
                } else {
                    option.style.display = 'none';
                }
            });
            
            if (noProductsFound) {
                noProductsFound.style.display = hasVisibleOptions ? 'none' : 'block';
            }
        }
        
        // Handle product selection
        productOptions.forEach(option => {
            option.addEventListener('click', function() {
                const id = this.dataset.id;
                const name = this.dataset.name;
                const code = this.dataset.code;
                
                productId.value = id;
                productSearch.value = name + ' (' + code + ')';
                productDropdown.style.display = 'none';
            });
        });
        
        // Add hover effect via JS since CSS isn't applied to it specifically
        productOptions.forEach(option => {
            option.addEventListener('mouseover', function() {
                this.style.backgroundColor = '#f5f5f5';
            });
            option.addEventListener('mouseout', function() {
                if(!this.classList.contains('active')) {
                    this.style.backgroundColor = 'transparent';
                } else {
                    this.style.backgroundColor = '#e9ecef';
                }
            });
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!productSearch.contains(e.target) && !productDropdown.contains(e.target)) {
                productDropdown.style.display = 'none';
            }
        });
        
        // Keyboard navigation
        let activeIndex = -1;
        productSearch.addEventListener('keydown', function(e) {
            const visibleOptions = Array.from(productOptions).filter(opt => opt.style.display !== 'none');
            
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                activeIndex = Math.min(activeIndex + 1, visibleOptions.length - 1);
                updateActiveOption(visibleOptions);
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                activeIndex = Math.max(activeIndex - 1, 0);
                updateActiveOption(visibleOptions);
            } else if (e.key === 'Enter' && activeIndex >= 0) {
                e.preventDefault(); // prevent form submission
                visibleOptions[activeIndex].click();
            } else if (e.key === 'Escape') {
                productDropdown.style.display = 'none';
            }
        });
        
        function updateActiveOption(visibleOptions) {
            productOptions.forEach(opt => {
                opt.classList.remove('active');
                opt.style.backgroundColor = 'transparent';
            });
            if (activeIndex >= 0 && activeIndex < visibleOptions.length) {
                visibleOptions[activeIndex].classList.add('active');
                visibleOptions[activeIndex].style.backgroundColor = '#e9ecef';
                visibleOptions[activeIndex].scrollIntoView({ block: 'nearest' });
            }
        }
    </script>
</body>
</html>
