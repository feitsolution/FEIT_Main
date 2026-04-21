<?php
session_start();
include('../include/dbconnection.php');

if (empty($_SESSION['uid'])) {
    header('location:./../auth/login.php');
    exit;
}

// Fetch Categories for Tabs
include('fetch_categories.php');

// Store categories in array for multiple iteration
$cats = [];
foreach ($categories as $cat) {
    $cats[] = $cat;
}

// Fetch Service Charge
include('fetch_service_charge.php');

// Fetch tables for JS
$js_tables = [];
$js_tbl_query = mysqli_query($con, "SELECT * FROM tbltables");
while ($row = mysqli_fetch_assoc($js_tbl_query)) {
    $js_tables[] = $row;
}

// Get initial max ID for polling tblorder_details
$max_query = mysqli_query($con, "SELECT MAX(ID) as max_id FROM tblorder_details");
$max_row = mysqli_fetch_assoc($max_query);
$initial_max_id = $max_row['max_id'] ? $max_row['max_id'] : 0;

// Get current counts for badges
$count_query = mysqli_query($con, "
    SELECT 
        (SELECT COUNT(DISTINCT OrderID, KOT) FROM tblorder_details WHERE order_status = 0) as pending_count,
        (SELECT COUNT(DISTINCT OrderID, KOT) FROM tblorder_details WHERE order_status = 1) as processing_count
");
$counts = mysqli_fetch_assoc($count_query);
$initial_pending = $counts['pending_count'] ? intval($counts['pending_count']) : 0;
$initial_processing = $counts['processing_count'] ? intval($counts['processing_count']) : 0;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    
    <title>RMS - POS</title>
    <?php include_once('../include/header.php'); ?>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        /* Custom POS Styles */
        body { overflow: hidden; } /* Prevent page scroll */
        #wrapper #content-wrapper { background-color: #f8f9fc; }
        .container-fluid { padding: 0; height: calc(100vh - 70px); }
        .pos-row { height: 100%; margin: 0; }
        
        /* Left Panel - Order Summary */
        .left-panel { background: white; border-right: 1px solid #e3e6f0; display: flex; flex-direction: column; height: 100%; padding: 0; }
        .order-header { padding: 15px; border-bottom: 1px solid #e3e6f0; }
        .dine-in-badge { background: #e74a3b; color: white; padding: 5px 15px; border-radius: 20px; font-weight: bold; font-size: 0.9rem; }
        .order-info { font-size: 0.85rem; color: #858796; margin-top: 5px; }
        .order-list-area { flex: 1; overflow-y: auto; padding: 0; }
        .order-item { padding: 10px 15px; border-bottom: 1px solid #eaecf4; cursor: pointer; }
        .order-item:hover { background-color: #f8f9fc; }
        .order-item.active { background-color: #eaecf4; }
        .order-footer { padding: 15px; border-top: 1px solid #e3e6f0; background: #fff; }
        .total-display { font-size: 1.5rem; font-weight: 800; color: #4e73df; text-align: right; }
        .action-buttons .btn { border-radius: 50%; width: 45px; height: 45px; display: inline-flex; align-items: center; justify-content: center; margin: 0 5px; }

        /* Right Panel - Menu & Keypad */
        .right-panel { display: flex; flex-direction: column; height: 100%; padding: 0; }
        
        /* Tabs */
        .menu-tabs { background: #2c3e50; padding: 10px 5px 0 5px; overflow-x: auto; white-space: nowrap; flex-shrink: 0; }
        .menu-tabs::-webkit-scrollbar { height: 5px; }
        .menu-tabs::-webkit-scrollbar-thumb { background: #5a5c69; border-radius: 5px; }
        .nav-pills .nav-link { color: #d1d3e2; border-radius: 10px 10px 0 0; margin-right: 5px; padding: 10px 20px; font-weight: 600; }
        .nav-pills .nav-link.active { background-color: #e74a3b; color: white; }

        /* Parent Categories */
        .parent-tabs { background: #1a252f; padding: 8px 5px 0 5px; overflow-x: auto; white-space: nowrap; flex-shrink: 0; border-bottom: 1px solid #2c3e50; }
        .parent-tabs::-webkit-scrollbar { height: 3px; }
        .parent-tabs::-webkit-scrollbar-thumb { background: #4e73df; border-radius: 3px; }
        .parent-tabs .nav-link { color: #d1d3e2; border-radius: 20px; margin-right: 5px; padding: 5px 15px; font-size: 0.85rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; }
        .parent-tabs .nav-link.active { background-color: #4e73df; color: white; box-shadow: 0 2px 5px rgba(78, 115, 223, 0.4); }

        /* Food Grid */
        .food-grid-container { flex: 1; overflow-y: auto; padding: 15px; background: #eaecf4; }
        .food-card { 
            height: 100px; 
            border-radius: 15px; 
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15); 
            color: white; 
            display: flex; 
            flex-direction: column; 
            justify-content: space-between; 
            padding: 12px; 
            cursor: pointer; 
            transition: all 0.2s;
            border: none;
            width: 100%;
            text-align: left;
            position: relative;
            overflow: hidden;
        }
        .food-card:hover { transform: translateY(-3px); box-shadow: 0 0.5rem 2rem 0 rgba(58, 59, 69, 0.25); }
        .food-card:active { transform: scale(0.95); }
        .item-price { font-weight: bold; background: rgba(0,0,0,0.2); padding: 2px 8px; border-radius: 10px; align-self: flex-end; }
        
        /* Category Colors */
        .cat-color-0 { background: linear-gradient(45deg, #4e73df, #224abe); }
        .cat-color-1 { background: linear-gradient(45deg, #1cc88a, #13855c); }
        .cat-color-2 { background: linear-gradient(45deg, #f6c23e, #dda20a); }
        .cat-color-3 { background: linear-gradient(45deg, #e74a3b, #be2617); }
        .cat-color-4 { background: linear-gradient(45deg, #858796, #60616f); }

        /* Billing Controls & Keypad */
        .billing-section { background: white; padding: 10px; border-top: 1px solid #e3e6f0; height: 35%; display: flex; flex-direction: column; }
        .change-display { font-size: 1.2rem; font-weight: bold; color: #1cc88a; margin-bottom: 5px; display: flex; justify-content: space-between; }
        .keypad-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px; flex: 1; }
        .keypad-btn { 
            border: none; border-radius: 8px; font-weight: bold; font-size: 1.1rem; 
            box-shadow: 0 2px 0 rgba(0,0,0,0.05); transition: all 0.1s;
            display: flex; align-items: center; justify-content: center;
        }
        .keypad-btn:active { transform: translateY(2px); box-shadow: none; }
        .btn-num { background: #f8f9fc; color: #5a5c69; border: 1px solid #d1d3e2; }
        .btn-func { background: #4e73df; color: white; font-size: 0.9rem; }
        .btn-red { background: #e74a3b; color: white; }
        .btn-yellow { background: #f6c23e; color: white; }
        .btn-green { background: #1cc88a; color: white; }
        .btn-big { grid-row: span 2; }
    </style>
</head>

<body id="page-top">

    <!-- Page Wrapper -->
    <div id="wrapper">
        <!-- Content Wrapper -->
        <div id="content-wrapper" class="d-flex flex-column">
            <!-- Main Content -->
            <div id="content">
                <!-- Begin Page Content -->
                <div class="container-fluid">
                    <div class="row pos-row">
                        <!-- Left Panel (Order Summary) -->
                        <div class="col-md-4 left-panel">
                            <div class="order-header">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="dine-in-badge">DINE IN</span>
                                    <div class="dropdown">
                                        <button class="btn btn-outline-secondary dropdown-toggle px-3" type="button" id="tableSelectDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                            Table Select
                                        </button>
                                        <div class="dropdown-menu" id="tableSelectMenu" aria-labelledby="tableSelectDropdown">
                                            <?php
                                            $tbl_query = mysqli_query($con, "SELECT t.ID, t.TableName, o.ID as OrderID FROM tbltables t JOIN tblorder o ON t.ID = o.TableID WHERE o.Status = 'Pending'");
                                            if (mysqli_num_rows($tbl_query) > 0) {
                                                while ($tbl_row = mysqli_fetch_assoc($tbl_query)) {
                                                    echo '<a class="dropdown-item" href="javascript:void(0)" onclick="selectTable(\'' . addslashes($tbl_row['TableName']) . '\', ' . $tbl_row['ID'] . ')">' . htmlspecialchars($tbl_row['TableName']) . ' - Bill #' . $tbl_row['OrderID'] . '</a>';
                                                }
                                            } else {
                                                echo '<span class="dropdown-item text-muted">No pending tables</span>';
                                            }
                                            ?>
                                        </div>
                                    </div>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <button type="button" id="btn-dine-in" class="btn btn-danger active px-3" onclick="setOrderType('Dine-in')">
                                            <i class="fas fa-utensils"></i>
                                        </button>
                                        <button type="button" id="btn-take-away" class="btn btn-outline-secondary px-3" onclick="setOrderType('Take-away')">
                                            <i class="fas fa-shopping-bag"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="d-flex justify-content-between align-items-center order-info">
                                    <span><i class="fas fa-user-circle"></i> <?php echo isset($_SESSION['name']) ? htmlspecialchars($_SESSION['name']) : 'User'; ?></span>
                                    <span id="current-time"></span>
                                </div>
                            </div>

                            <div class="order-list-area" id="order-list">
                                <!-- Order items will be injected here via JS -->
                                <div class="text-center mt-5 text-gray-400">
                                    <i class="fas fa-shopping-basket fa-3x mb-3"></i>
                                    <p>Order is empty</p>
                                </div>
                            </div>

                            <div class="order-footer">
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="text-gray-600">Subtotal</span>
                                    <span class="font-weight-bold" id="subtotal">0.00</span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-gray-600">Items</span>
                                    <span class="font-weight-bold" id="item-count">0</span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-gray-600">Service Charge (<?php echo $service_charge_percentage; ?>%)</span>
                                    <span class="font-weight-bold" id="service-charge">0.00</span>
                                </div>
                                <div class="d-flex justify-content-between mb-2 align-items-center">
                                    <span class="text-gray-600">Discount (<input type="number" id="discount-percent" class="form-control form-control-sm d-inline-block text-right" style="width: 60px; padding: 0 5px;" value="0" min="0" max="100" oninput="renderCart()" onfocus="setActiveInput(this.id)">%)</span>
                                    <span class="font-weight-bold" id="discount">0.00</span>
                                </div>
                                <div class="mb-2">
                                    <span class="text-gray-600">Payment Method</span>
                                    <div class="btn-group w-100 mt-1" role="group">
                                        <button type="button" class="btn btn-primary btn-sm payment-btn active" onclick="setPaymentMethod(this, '0')">Cash</button>
                                        <button type="button" class="btn btn-outline-secondary btn-sm payment-btn" onclick="setPaymentMethod(this, '1')">Card</button>
                                        <button type="button" class="btn btn-outline-secondary btn-sm payment-btn" onclick="setPaymentMethod(this, '2')">Online</button>
                                    </div>
                                    <input type="hidden" id="payment-method" value="0">
                                </div>
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <span class="h4 font-weight-bold text-gray-800">TOTAL</span>
                                    <span class="total-display" id="total-amount">0.00</span>
                                </div>
                                <div class="row mt-2">
                                    <div class="col-6 pr-1">
                                        <button class="btn btn-warning btn-block shadow-sm font-weight-bold position-relative" onclick="viewPendingOrders()">
                                            <i class="fas fa-clock mr-1"></i> Pending KOT
                                            <span class="badge badge-danger position-absolute" id="badge-pending" style="top: -6px; right: -6px; font-size: 0.8rem; <?php echo $initial_pending > 0 ? '' : 'display:none;'; ?> border-radius: 50%; padding: 4px 6px;"><?php echo $initial_pending; ?></span>
                                        </button>
                                    </div>
                                    <div class="col-6 pl-1">
                                        <button class="btn btn-info btn-block shadow-sm font-weight-bold position-relative" onclick="viewOrderStatus()">
                                            <i class="fas fa-check-double mr-1"></i> Processing KOT
                                            <span class="badge badge-danger position-absolute" id="badge-processing" style="top: -6px; right: -6px; font-size: 0.8rem; <?php echo $initial_processing > 0 ? '' : 'display:none;'; ?> border-radius: 50%; padding: 4px 6px;"><?php echo $initial_processing; ?></span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Right Panel (Menu + Keypad) -->
                        <div class="col-md-8 right-panel">
                            <!-- Parent Categories -->
                            <div class="parent-tabs">
                                <ul class="nav nav-pills" id="parent-tabs-list" role="tablist">
                                    <li class='nav-item'>
                                        <a class='nav-link active' id='parent-all-tab' data-toggle='pill' href='javascript:void(0)' onclick="filterSubCategories('all')" role='tab' aria-selected='true'>All</a>
                                    </li>
                                    <?php 
                                    if(isset($parent_categories) && $parent_categories) {
                                        foreach ($parent_categories as $pcat) {
                                            $pid = $pcat['ID'];
                                            $pname = isset($pcat['ParentCategoryName']) ? htmlspecialchars($pcat['ParentCategoryName']) : 'Parent Category';
                                            echo "<li class='nav-item'>
                                                    <a class='nav-link' id='parent-$pid-tab' data-toggle='pill' href='javascript:void(0)' onclick=\"filterSubCategories($pid)\" role='tab' aria-controls='parent-$pid' aria-selected='false'>$pname</a>
                                                  </li>";
                                        }
                                    }
                                    ?>
                                </ul>
                            </div>

                            <!-- Menu Categories (Top Tabs) -->
                            <div class="menu-tabs">
                                <ul class="nav nav-pills" id="pills-tab" role="tablist">
                                    <?php 
                                    $first = true;
                                    if($cats) {
                                        foreach ($cats as $cat) {
                                            $active = $first ? 'active' : '';
                                            $id = $cat['ID'];
                                            $parentId = isset($cat['ParentCategoryID']) ? $cat['ParentCategoryID'] : '';
                                            echo "<li class='nav-item sub-cat-item' data-parent-id='$parentId'>
                                                    <a class='nav-link $active' id='pills-$id-tab' data-toggle='pill' href='#pills-$id' role='tab' aria-controls='pills-$id' aria-selected='$first'>" . htmlspecialchars($cat['CategoryName']) . "</a>
                                                  </li>";
                                            $first = false;
                                        }
                                    }
                                    ?>
                                </ul>
                            </div>
                            

                            <!-- Food Items Grid -->
                            <div class="food-grid-container">
                                <div class="tab-content" id="pills-tabContent">
                                    <?php 
                                    $first = true;
                                    if($cats) {
                                        foreach ($cats as $index => $cat) {
                                            $catId = $cat['ID'];
                                            $active = $first ? 'show active' : '';
                                            $colorClass = 'cat-color-' . ($index % 5); // Cycle through colors
                                            
                                            echo "<div class='tab-pane fade $active' id='pills-$catId' role='tabpanel' aria-labelledby='pills-$catId-tab'>";
                                            echo "<div class='row'>";
                                            
                                            if (isset($products_data[$catId])) {
                                                foreach ($products_data[$catId] as $prod) {
                                                    $pName = htmlspecialchars($prod['ProductName']);
                                                    $pPrice = $prod['Price'];
                                                    $pId = $prod['ID'];
                                                    $pQty = $prod['Quantity'];
                                                    $isOutOfStock = ($prod['Type'] === 'Countable' && $pQty <= 0);
                                                    $clickAction = $isOutOfStock ? "" : "onclick='addToCart($pId, \"$pName\", $pPrice)'";
                                                    $stockLabel = $isOutOfStock ? "<br><small class='badge badge-light text-danger'>Out of Stock</small>" : "";
                                                    $opacity = $isOutOfStock ? "style='opacity: 0.6; cursor: not-allowed;'" : "";

                                                    echo "
                                                    <div class='col-xl-3 col-md-4 col-6 mb-3'>
                                                        <div class='food-card $colorClass' $clickAction $opacity>
                                                            <span class='item-name'>$pName $stockLabel</span>
                                                            <span class='item-price'>$pPrice</span>
                                                        </div>
                                                    </div>";
                                                }
                                            } else {
                                                echo "<div class='col-12 text-center text-gray-500 mt-4'>No items in this category</div>";
                                            }
                                            
                                            echo "</div></div>";
                                            $first = false;
                                        }
                                    }
                                    ?>
                                </div>
                            </div>

                            <!-- Bottom Section (Billing Controls) -->
                            <div class="billing-section">
                                <div class="keypad-grid">
                                    <button class="keypad-btn btn-func" style="grid-column: span 2;" onclick="openNewBill()">Open new bill</button>
                                    <button class="keypad-btn btn-func" style="grid-column: span 2;" onclick="printKOT()">KOT Print</button>
                                    <button class="keypad-btn btn-num" onclick="keypadInput(7)">7</button>
                                    <button class="keypad-btn btn-num" onclick="keypadInput(8)">8</button>
                                    <button class="keypad-btn btn-num" onclick="keypadInput(9)">9</button>
                                    <button class="keypad-btn btn-red btn-big" onclick="clearCart()">CLEAR</button>
                                    
                                    <button class="keypad-btn btn-num" onclick="keypadInput(4)">4</button>
                                    <button class="keypad-btn btn-num" onclick="keypadInput(5)">5</button>
                                    <button class="keypad-btn btn-num" onclick="keypadInput(6)">6</button>
                                    
                                    <button class="keypad-btn btn-num" onclick="keypadInput(1)">1</button>
                                    <button class="keypad-btn btn-num" onclick="keypadInput(2)">2</button>
                                    <button class="keypad-btn btn-num" onclick="keypadInput(3)">3</button>
                                    <button class="keypad-btn btn-green btn-big" onclick="closeBill()">Close the bill</button>
                                    
                                    <button class="keypad-btn btn-num" onclick="keypadInput(0)">0</button>
                                    <button class="keypad-btn btn-num" onclick="keypadInput('00')">00</button>
                                    <button class="keypad-btn btn-num" onclick="keypadInput('.')">.</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- End of Main Content -->
        </div>
        <!-- End of Content Wrapper -->
    </div>
    <!-- End of Page Wrapper -->
    <?php include_once('../include/script.php'); ?>

    <script>
        var current_staff_id = <?php echo intval($_SESSION['uid']); ?>;
        var serviceChargePercentage = <?php echo $service_charge_percentage; ?>;
        // Basic Clock
        function updateTime() {
            const now = new Date();
            document.getElementById('current-time').innerText = now.toLocaleString();
        }
        setInterval(updateTime, 1000);
        updateTime();

        // Cart Logic
        let cart = [];
        let activeInputId = null;

        function setActiveInput(id) {
            activeInputId = id;
        }
        
        function addToCart(id, name, price) {
            // Find an item with the same ID added by the *current cashier*.
            const existingItem = cart.find(item => item.id === id && item.staff_id === current_staff_id);
            if (existingItem) {
                existingItem.qty++;
            } else {
                // If no existing item from this cashier, add a new one.
                cart.push({ id, name, price, qty: 1, staff_id: current_staff_id });
            }
            renderCart();
        }

        function renderCart() {
            const orderList = document.getElementById('order-list');
            let subtotal = 0;
            let count = 0;

            if (cart.length === 0) {
                orderList.innerHTML = `
                    <div class="text-center mt-5 text-gray-400">
                        <i class="fas fa-shopping-basket fa-3x mb-3"></i>
                        <p>Order is empty</p>
                    </div>`;
            } else {
                let html = '';
                cart.forEach((item, index) => {
                    const total = item.price * item.qty;
                    subtotal += total;
                    count += item.qty;
                    html += `
                        <div class="order-item d-flex justify-content-between align-items-center">
                            <div style="flex:1">
                                <div class="font-weight-bold text-gray-800">${item.name}</div>
                                <div class="d-flex align-items-center mt-1">
                                    <small class="text-muted mr-2">${item.price}</small>
                                    <button class="btn btn-sm btn-light border py-0 px-2" onclick="updateItemQty(${index}, -1)">-</button>
                                    <input type="number" id="qty-${index}" class="form-control form-control-sm mx-2 text-center p-0" style="width: 45px; height: 25px;" value="${item.qty}" onchange="setQty(${index}, this.value)" onfocus="setActiveInput(this.id)">
                                    <button class="btn btn-sm btn-light border py-0 px-2" onclick="updateItemQty(${index}, 1)">+</button>
                                </div>
                            </div>
                            <div class="font-weight-bold text-gray-800">${total.toFixed(2)}</div>
                        </div>`;
                });
                orderList.innerHTML = html;
            }

            let serviceCharge = 0;
            if (currentOrderType === 'Dine-in') {
                serviceCharge = subtotal * (serviceChargePercentage / 100);
            }

            let discountPercent = parseFloat(document.getElementById('discount-percent').value) || 0;
            
            // Enforce range 0 - 100
            if (discountPercent > 100) {
                discountPercent = 100;
                document.getElementById('discount-percent').value = 100;
            } else if (discountPercent < 0) {
                discountPercent = 0;
                document.getElementById('discount-percent').value = 0;
            }

            let discountAmount = serviceCharge * (discountPercent / 100);

            let total = subtotal + serviceCharge - discountAmount;
            if (total < 0) total = 0;

            document.getElementById('subtotal').innerText = subtotal.toFixed(2);
            document.getElementById('item-count').innerText = count;
            document.getElementById('service-charge').innerText = serviceCharge.toFixed(2);
            document.getElementById('discount').innerText = discountAmount.toFixed(2);
            document.getElementById('total-amount').innerText = total.toFixed(2);
        }

        function filterSubCategories(parentId) {
            // Update active state of parent tabs
            document.querySelectorAll('#parent-tabs-list .nav-link').forEach(el => {
                el.classList.remove('active');
                el.setAttribute('aria-selected', 'false');
            });
            
            const activeTab = parentId === 'all' 
                ? document.getElementById('parent-all-tab') 
                : document.getElementById('parent-' + parentId + '-tab');
            
            if(activeTab) {
                activeTab.classList.add('active');
                activeTab.setAttribute('aria-selected', 'true');
            }

            // Filter items
            const items = document.querySelectorAll('.sub-cat-item');
            let firstVisible = null;
            let currentActiveIsVisible = false;

            items.forEach(item => {
                const itemParentId = item.getAttribute('data-parent-id');
                if (parentId === 'all' || itemParentId == parentId) {
                    item.style.display = '';
                    if (!firstVisible) firstVisible = item;
                    if (item.querySelector('.nav-link').classList.contains('active')) {
                        currentActiveIsVisible = true;
                    }
                } else {
                    item.style.display = 'none';
                }
            });

            // If current active tab is hidden, switch to first visible
            if (!currentActiveIsVisible && firstVisible) {
                firstVisible.querySelector('.nav-link').click();
            }
        }

        function clearCart() {
            cart = [];
            document.getElementById('discount-percent').value = 0;
            renderCart();
        }

        function keypadInput(val) {
            if (!activeInputId) return;
            let input = document.getElementById(activeInputId);
            if (input) {
                let currentVal = input.value.toString();
                if (val === '.' && currentVal.includes('.')) return;
                
                if (currentVal === '0' && val !== '.') input.value = val;
                else input.value = currentVal + val;
                
                input.dispatchEvent(new Event('input'));
                input.dispatchEvent(new Event('change'));
            }
        }

        function updateItemQty(index, change) {
            if (cart[index]) {
                let newQty = cart[index].qty + change;
                if (newQty <= 0) {
                    if(confirm("Remove item from cart?")) {
                        cart.splice(index, 1);
                    }
                } else {
                    cart[index].qty = newQty;
                }
                renderCart();
            }
        }

        function setQty(index, val) {
            let newQty = parseInt(val);
            if (isNaN(newQty) || newQty <= 0) {
                if(confirm("Remove item from cart?")) {
                    cart.splice(index, 1);
                }
            } else {
                cart[index].qty = newQty;
            }
            renderCart();
        }

        function setPaymentMethod(btn, method) {
            document.getElementById('payment-method').value = method;
            document.querySelectorAll('.payment-btn').forEach(b => {
                b.classList.remove('btn-primary', 'active');
                b.classList.add('btn-outline-secondary');
            });
            btn.classList.remove('btn-outline-secondary');
            btn.classList.add('btn-primary', 'active');
        }

        let selectedTableId = null;
        let currentOrderId = null;
        let currentOrderType = 'Dine-in';

        function selectTable(name, id) {
            document.getElementById('tableSelectDropdown').innerText = name;
            selectedTableId = id;
            
            $.ajax({
                url: 'get_order_details.php',
                type: 'GET',
                data: { table_id: id },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        cart = response.items;
                        currentOrderId = response.order_id;
                        renderCart();
                    } else {
                        cart = [];
                        currentOrderId = null;
                        renderCart();
                    }
                },
                error: function() {
                    console.error("Failed to fetch order details");
                }
            });
        }

        function setOrderType(type) {
            currentOrderType = type;
            
            // Update Badge
            const badge = document.querySelector('.dine-in-badge');
            if(badge) badge.innerText = type === 'Dine-in' ? 'DINE IN' : 'TAKE AWAY';
            
            const btnDine = document.getElementById('btn-dine-in');
            const btnTake = document.getElementById('btn-take-away');
            
            if (type === 'Dine-in') {
                btnDine.classList.add('btn-danger', 'active');
                btnDine.classList.remove('btn-outline-secondary');
                btnTake.classList.remove('btn-danger', 'active');
                btnTake.classList.add('btn-outline-secondary');
                
                if(selectedTableId === null) document.getElementById('tableSelectDropdown').innerText = 'Table Select';
                renderCart();
            } else {
                // Take Away Mode
                btnTake.classList.add('btn-danger', 'active');
                btnTake.classList.remove('btn-outline-secondary');
                btnDine.classList.remove('btn-danger', 'active');
                btnDine.classList.add('btn-outline-secondary');
                
                // Reset for new Take Away order
                clearCart();
                selectedTableId = null;
                currentOrderId = null;
                document.getElementById('tableSelectDropdown').innerText = 'Take Away';
            }
        }

        var allTables = <?php echo json_encode($js_tables); ?>;
        function openNewBill() {
            var available = allTables.filter(t => t.Status == '0');
            var reserved = allTables.filter(t => t.Status == '1');
            
            var html = '<div class="text-left">';
            html += '<h6 class="font-weight-bold text-success">Available</h6><div class="d-flex flex-wrap mb-3">';
            if(available.length === 0) html += '<small class="text-muted w-100">No available tables</small>';
            available.forEach(t => {
                html += `<button class="btn btn-outline-success m-1" onclick="selectAndClose('${t.TableName}', ${t.ID})">${t.TableName}</button>`;
            });
            html += '</div>';
            
            html += '<h6 class="font-weight-bold text-warning">Reserved</h6><div class="d-flex flex-wrap">';
            if(reserved.length === 0) html += '<small class="text-muted w-100">No reserved tables</small>';
            reserved.forEach(t => {
                html += `<button class="btn btn-outline-warning m-1" onclick="selectAndClose('${t.TableName}', ${t.ID})">${t.TableName}</button>`;
            });
            html += '</div></div>';

            Swal.fire({
                title: '<strong>Select Table</strong>',
                html: html,
                showConfirmButton: false,
                showCloseButton: true,
                width: 600
            });
        }

        function selectAndClose(name, id) {
            selectTable(name, id);
            clearCart();
            Swal.close();
        }

        function viewOrderStatus() {
            $.ajax({
                url: 'get_order_status.php',
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        let html = '<div class="table-responsive" style="max-height: 400px; overflow-y: auto;">';
                        html += '<table class="table table-bordered table-striped text-left text-sm">';
                        html += '<thead class="thead-light"><tr><th>KOT No.</th><th>Order #</th><th>Items (Qty)</th><th>Action</th></tr></thead><tbody>';
                        
                        if (response.data.length === 0) {
                            html += '<tr><td colspan="4" class="text-center text-muted py-4">No items are currently processing in the kitchen.</td></tr>';
                        } else {
                            let grouped = {};
                            response.data.forEach(item => {
                                let groupKey = item.OrderID + '-' + item.KOT;
                                if(!grouped[groupKey]) {
                                    grouped[groupKey] = { OrderID: item.OrderID, KOT: item.KOT, ids: [], itemsHTML: '' };
                                }
                                grouped[groupKey].ids.push(item.ID);
                                grouped[groupKey].itemsHTML += `<div><span class="badge badge-secondary mr-2">${item.Qty}x</span> ${item.ProductName}</div>`;
                            });

                            for (let groupKey in grouped) {
                                let order = grouped[groupKey];
                                let idsStr = order.ids.join(',');
                                html += `<tr id="status-row-${groupKey}">
                                    <td class="align-middle font-weight-bold text-gray-800">${order.KOT}</td>
                                    <td class="align-middle text-gray-600">${order.OrderID}</td>
                                    <td class="align-middle">${order.itemsHTML}</td>
                                    <td class="align-middle text-center">
                                        <button class="btn btn-sm btn-success shadow-sm" onclick="markOrderCompleted('${idsStr}', '${groupKey}')">
                                            <i class="fas fa-check-double"></i> Complete All
                                        </button>
                                    </td>
                                </tr>`;
                            }
                        }
                        html += '</tbody></table></div>';

                        Swal.fire({
                            title: 'Kitchen Processing Status',
                            html: html,
                            width: 800,
                            showCloseButton: true,
                            showConfirmButton: false
                        });
                    } else {
                        Swal.fire('Error', 'Failed to fetch status: ' + response.message, 'error');
                    }
                },
                error: function() {
                    Swal.fire('Error', 'Server error while fetching status.', 'error');
                }
            });
        }

        function markOrderCompleted(idsStr, groupKey) {
            $.ajax({
                url: 'update_order_status.php',
                type: 'POST',
                data: { ids: idsStr, status: 2 },
                dataType: 'json',
                success: function(res) {
                    if (res.status === 'success') {
                        let row = document.getElementById('status-row-' + groupKey);
                        if (row) {
                            row.style.transition = "opacity 0.3s ease";
                            row.style.opacity = "0";
                            setTimeout(() => { row.remove(); }, 300);
                        }
                        
                        const Toast = Swal.mixin({
                            toast: true, position: 'top-end', showConfirmButton: false, timer: 1500
                        });
                        Toast.fire({ icon: 'success', title: 'Order marked as Completed!' });
                    } else {
                        Swal.fire('Error', res.message, 'error');
                    }
                },
                error: function() {
                    Swal.fire('Error', 'Server error while updating status.', 'error');
                }
            });
        }

        function viewPendingOrders() {
            $.ajax({
                url: 'get_pending_orders.php',
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        let html = '<div class="table-responsive" style="max-height: 400px; overflow-y: auto;">';
                        html += '<table class="table table-bordered table-striped text-left text-sm">';
                        html += '<thead class="thead-light"><tr><th>KOT No.</th><th>Order #</th><th>Items (Qty)</th><th>Action</th></tr></thead><tbody>';
                        
                        if (response.data.length === 0) {
                            html += '<tr><td colspan="4" class="text-center text-muted py-4">No pending orders.</td></tr>';
                        } else {
                            let grouped = {};
                            response.data.forEach(item => {
                                let groupKey = item.OrderID + '-' + item.KOT;
                                if(!grouped[groupKey]) {
                                    grouped[groupKey] = { OrderID: item.OrderID, KOT: item.KOT, ids: [], itemsHTML: '' };
                                }
                                grouped[groupKey].ids.push(item.ID);
                                grouped[groupKey].itemsHTML += `<div><span class="badge badge-secondary mr-2">${item.Qty}x</span> ${item.ProductName}</div>`;
                            });

                            for (let groupKey in grouped) {
                                let order = grouped[groupKey];
                                let idsStr = order.ids.join(',');
                                html += `<tr id="pending-row-${groupKey}">
                                    <td class="align-middle font-weight-bold text-gray-800">${order.KOT}</td>
                                    <td class="align-middle text-gray-600">${order.OrderID}</td>
                                    <td class="align-middle">${order.itemsHTML}</td>
                                    <td class="align-middle text-center">
                                        <button class="btn btn-sm btn-primary shadow-sm" onclick="markOrderProcessing('${idsStr}', '${groupKey}', '${order.KOT}', ${order.OrderID})">
                                            <i class="fas fa-fire"></i> Start All
                                        </button>
                                    </td>
                                </tr>`;
                            }
                        }
                        html += '</tbody></table></div>';

                        Swal.fire({
                            title: 'Pending Orders',
                            html: html,
                            width: 800,
                            showCloseButton: true,
                            showConfirmButton: false
                        });
                    } else {
                        Swal.fire('Error', 'Failed to fetch pending orders: ' + response.message, 'error');
                    }
                },
                error: function() {
                    Swal.fire('Error', 'Server error while fetching pending orders.', 'error');
                }
            });
        }

        function markOrderProcessing(idsStr, groupKey, kotNum, orderId) {
            $.ajax({
                url: 'update_order_status.php',
                type: 'POST',
                data: { ids: idsStr, status: 1 },
                dataType: 'json',
                success: function(res) {
                    if (res.status === 'success') {
                        let row = document.getElementById('pending-row-' + groupKey);
                        if (row) {
                            row.style.transition = "opacity 0.3s ease";
                            row.style.opacity = "0";
                            setTimeout(() => { row.remove(); }, 300);
                        }
                        
                        const Toast = Swal.mixin({
                            toast: true, position: 'top-end', showConfirmButton: false, timer: 1500
                        });
                        Toast.fire({ icon: 'success', title: 'Order moved to Processing!' });

                        // Print the KOT
                        window.open('../waitor/print_kot.php?order_id=' + orderId + '&ids=' + idsStr + '&kot_num=' + kotNum, 'KOT', 'width=400,height=600');
                    } else {
                        Swal.fire('Error', res.message, 'error');
                    }
                },
                error: function() {
                    Swal.fire('Error', 'Server error while updating status.', 'error');
                }
            });
        }

        function printKOT() {
            if (currentOrderId) {
                // Existing Active Order
                $.ajax({
                    url: 'get_order_ids.php',
                    type: 'POST',
                    data: { order_id: currentOrderId },
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 'success' && response.ids.length > 0) {
                            window.open('../waitor/print_kot.php?order_id=' + currentOrderId + '&ids=' + response.ids.join(',') + '&kot_num=REPRINT', 'KOT', 'width=400,height=600');
                        } else {
                            Swal.fire("Info", "No items found for this order", "info");
                        }
                    },
                    error: function() { Swal.fire("Error", "Failed to fetch order details", "error"); }
                });
            } else if (cart.length > 0) {
                // New Order (Take Away or New Dine-in) -> Save as Pending then Print
                let orderData = {
                    items: cart,
                    total: parseFloat(document.getElementById('total-amount').innerText),
                    serviceCharge: parseFloat(document.getElementById('service-charge').innerText),
                    discount: parseFloat(document.getElementById('discount').innerText),
                    tableId: selectedTableId || 0,
                    orderId: 0,
                    orderType: currentOrderType,
                    paymentMethod: document.getElementById('payment-method').value,
                    orderStatus: 'Pending' // Save as Pending
                };

                $.ajax({
                    url: 'save_pos_order.php',
                    type: 'POST',
                    data: { order_data: JSON.stringify(orderData) },
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 'success') {
                            // Prevent own order from triggering the New Order Alert
                            if (response.ids && response.ids.length > 0) {
                                let maxId = Math.max(...response.ids);
                                if (maxId > lastOrderDetailId) {
                                    lastOrderDetailId = maxId;
                                }
                            }

                            // Update active order ID so subsequent clicks work on this order
                            currentOrderId = response.order_id;
                            
                            var ids = response.ids.join(',');
                            window.open('../waitor/print_kot.php?order_id=' + response.order_id + '&ids=' + ids + '&kot_num=' + response.kot_num, 'KOT', 'width=400,height=600');
                            
                            // Optional: If Dine-in, you might want to reload tables or show success
                            const Toast = Swal.mixin({
                                toast: true, position: 'top-end', showConfirmButton: false, timer: 3000
                            });
                            Toast.fire({ icon: 'success', title: 'Take Away KOT Printed' });
                        } else {
                            Swal.fire("Error", response.message, "error");
                        }
                    },
                    error: function() { Swal.fire("Error", "Failed to save order", "error"); }
                });
            } else {
                Swal.fire("Error", "Cart is empty", "error");
            }
        }

        function closeBill() {
            if (cart.length === 0) {
                Swal.fire({
                    icon: 'error',
                    title: 'Empty Cart',
                    text: 'Please add items to the cart before closing the bill.'
                });
                return;
            }
            
            if (selectedTableId === null && currentOrderType === 'Dine-in') {
                 Swal.fire({
                    icon: 'warning',
                    title: 'No Table Selected',
                    text: 'Please select a table to proceed.'
                });
                return;
            }

            Swal.fire({
                title: 'Close Bill?',
                text: "Confirm to close this bill.",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#1cc88a',
                cancelButtonColor: '#e74a3b',
                confirmButtonText: 'Yes, close it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    let orderData = {
                        items: cart,
                        total: parseFloat(document.getElementById('total-amount').innerText),
                        serviceCharge: parseFloat(document.getElementById('service-charge').innerText),
                        discount: parseFloat(document.getElementById('discount').innerText),
                        tableId: selectedTableId || 0,
                        orderId: currentOrderId || 0,
                        orderType: currentOrderType,
                        paymentMethod: document.getElementById('payment-method').value,
                        orderStatus: 'Paid'
                    };

                    $.ajax({
                        url: 'save_pos_order.php',
                        type: 'POST',
                        data: { order_data: JSON.stringify(orderData) },
                        dataType: 'json',
                        success: function(response) {
                            if (response.status === 'success') {
                                window.open('receipt.php?order_id=' + response.order_id, '_blank', 'width=400,height=600');
                                
                                // If Take Away, also print KOT
                                if (currentOrderType === 'Take-away') {
                                    var ids = response.ids.join(',');
                                    window.open('../waitor/print_kot.php?order_id=' + response.order_id + '&ids=' + ids + '&kot_num=' + response.kot_num, 'KOT', 'width=400,height=600');
                                }

                                Swal.fire({
                                    icon: 'success',
                                    title: 'Bill Closed',
                                    text: 'Printing receipt...',
                                    timer: 1500,
                                    showConfirmButton: false
                                }).then(() => {
                                    location.reload();
                                });
                            } else {
                                Swal.fire('Error', response.message, 'error');
                            }
                        },
                        error: function() {
                            Swal.fire('Error', 'Server Error', 'error');
                        }
                    });
                }
            });
        }

        // --- Real-time Order Polling ---
        let lastOrderDetailId = <?php echo $initial_max_id; ?>;

        // Poll for pending tables every 5 seconds to update the dropdown
        setInterval(function() {
            $.ajax({
                url: 'get_pending_tables.php',
                type: 'GET',
                success: function(data) {
                    $('#tableSelectMenu').html(data);
                }
            });
        }, 5000);

        setInterval(function() {
            $.ajax({
                url: 'check_new_orders.php',
                type: 'GET',
                data: { last_id: lastOrderDetailId },
                dataType: 'json',
                success: function(response) {
                    // Always update the badges first
                    if (response.pending_count !== undefined) {
                        let pendBadge = document.getElementById('badge-pending');
                        pendBadge.innerText = response.pending_count;
                        pendBadge.style.display = response.pending_count > 0 ? 'inline-block' : 'none';
                    }
                    if (response.processing_count !== undefined) {
                        let procBadge = document.getElementById('badge-processing');
                        procBadge.innerText = response.processing_count;
                        procBadge.style.display = response.processing_count > 0 ? 'inline-block' : 'none';
                    }

                    if (response.has_new) {
                        lastOrderDetailId = response.new_max_id;
                        
                        Swal.fire({
                            title: 'New Order Alert!',
                            text: 'Order #' + response.order_id + ' has new items.',
                            icon: 'warning',
                            position: 'top-end',
                            showConfirmButton: true, 
                            confirmButtonText: 'Print KOT',
                            showCloseButton: true,
                            allowOutsideClick: false,
                            backdrop: false,
                            customClass: {
                                popup: 'animated tada'
                            }
                        }).then((result) => {
                            if (result.isConfirmed) {
                                // 1. Update the status in the database to 1 (Processing)
                                $.ajax({
                                    url: 'update_order_status.php',
                                    type: 'POST',
                                    data: { ids: response.ids, status: 1 },
                                    dataType: 'json',
                                    success: function(updateRes) {
                                        // 2. Open the KOT Print Window
                                        window.open('../waitor/print_kot.php?order_id=' + response.order_id + '&ids=' + response.ids + '&kot_num=' + response.kot_num, 'KOT', 'width=400,height=600');
                                    },
                                    error: function() {
                                        console.error("Failed to update status to processing");
                                        // Fallback: still print the KOT even if the update fails
                                        window.open('../waitor/print_kot.php?order_id=' + response.order_id + '&ids=' + response.ids + '&kot_num=' + response.kot_num, 'KOT', 'width=400,height=600');
                                    }
                                });
                            }
                        });
                    }
                },
                error: function(err) {
                    console.error("Polling error: ", err);
                }
            });
        }, 5000);
    </script>
</body>

</html>