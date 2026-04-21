<?php
session_start();
include('../include/dbconnection.php');

if (empty($_SESSION['uid'])) {
    header('location:../auth/login.php');
    exit;
}

// Fetch Categories
include('../cart/fetch_categories.php');

$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

// Convert result set to array for multiple usage (PHP loop + JS JSON)
$categories_list = [];
foreach ($categories as $cat) {
    $categories_list[] = $cat;
}
$categories = $categories_list; 

if (!isset($products_data)) {
    $products_data = [];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    
    <title>RMS - Waitor Cart</title>
    <?php include_once('../include/header.php'); ?>
</head>

<body id="page-top">

    <!-- Page Wrapper -->
    <div id="wrapper">
        <!-- Content Wrapper -->
        <div id="content-wrapper" class="d-flex flex-column">
            <!-- Main Content -->
            <div id="content">
                <?php include_once('../include/top-nav.php'); ?>
                <!-- Begin Page Content -->
                <div class="container-fluid">
                    <!-- Page Heading -->
                    <!--<h1 class="h3 mb-4 text-gray-800">Waiter Cart</h1>-->
                    <div class="row">
                        <!-- Filter and Products Section -->
                        <div class="col-lg-8">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Select Items</h6>
                                </div>
                                <div class="card-body">
                                    <div class="form-row">
                                        <div class="form-group col-md-6">
                                            <label>Parent Category</label>
                                            <select class="form-control" id="parentCategorySelect">
                                                <option value="all">All Categories</option>
                                                <?php 
                                                if(isset($parent_categories) && $parent_categories) {
                                                    foreach ($parent_categories as $pcat) {
                                                        $pid = $pcat['ID'];
                                                        $pname = isset($pcat['ParentCategoryName']) ? htmlspecialchars($pcat['ParentCategoryName']) : 'Category ' . $pid;
                                                        echo '<option value="'.$pid.'">'.$pname.'</option>';
                                                    }
                                                }
                                                ?>
                                            </select>
                                        </div>
                                        <div class="form-group col-md-6">
                                            <label>Sub Category</label>
                                            <select class="form-control" id="subCategorySelect">
                                                <option value="all">All Sub Categories</option>
                                                <?php 
                                                if(isset($categories) && $categories) {
                                                    foreach ($categories as $cat) {
                                                        $cid = $cat['ID'];
                                                        $pid = isset($cat['ParentCategoryID']) ? $cat['ParentCategoryID'] : '';
                                                        $cname = isset($cat['CategoryName']) ? htmlspecialchars($cat['CategoryName']) : 'Sub Category ' . $cid;
                                                        echo '<option value="'.$cid.'" data-parent-id="'.$pid.'">'.$cname.'</option>';
                                                    }
                                                }
                                                ?>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <hr class="sidebar-divider">
                                    
                                    <div id="items-container" class="mt-4"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Order Summary Section -->
                        <div class="col-lg-4">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Current Order</h6>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-sm" id="orderTable" width="100%" cellspacing="0">
                                            <thead>
                                                <tr>
                                                    <th>Item</th>
                                                    <th width="100">Qty</th>
                                                    <th>Price</th>
                                                    <th></th>
                                                </tr>
                                            </thead>
                                            <tbody id="order-items-body">
                                                <!-- Order items will be added here -->
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="d-flex justify-content-between font-weight-bold mt-3">
                                        <h5>Total:</h5>
                                        <h5 id="order-total">0.00</h5>
                                    </div>
                                    <button class="btn btn-success btn-block mt-3" id="place-order-btn">Confirm Order</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- /.container-fluid -->

            </div>
            <!-- End of Main Content -->

            <?php include_once('../include/footer.php'); ?>

        </div>
        <!-- End of Content Wrapper -->

    </div>
    <!-- End of Page Wrapper -->

    <?php include_once('../include/script.php'); ?>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        // Pass PHP data to JavaScript
        var productsData = <?php echo json_encode($products_data); ?>;
        var categoriesList = <?php echo json_encode($categories_list); ?>;

        $(document).ready(function() {
            var $subCategorySelect = $('#subCategorySelect');
            // Store options excluding the "All" option for filtering
            var $allSubOptions = $subCategorySelect.find('option[value!="all"]').clone();

            // Cart Logic
            window.orderItems = [];

            window.addToOrder = function(id, name, price) {
                var existingItem = window.orderItems.find(item => item.id === id);
                if (existingItem) {
                    existingItem.qty++;
                } else {
                    window.orderItems.push({
                        id: id,
                        name: name,
                        price: parseFloat(price),
                        qty: 1
                    });
                }
                renderOrderTable();
            };

            function renderOrderTable() {
                var tbody = $('#order-items-body');
                tbody.empty();
                var total = 0;

                window.orderItems.forEach(function(item, index) {
                    var itemTotal = item.price * item.qty;
                    total += itemTotal;
                    var row = `<tr>
                        <td class="align-middle">${item.name}</td>
                        <td class="align-middle">
                            <div class="input-group input-group-sm">
                                <div class="input-group-prepend">
                                    <button class="btn btn-outline-secondary btn-minus" type="button" data-index="${index}">-</button>
                                </div>
                                <input type="number" class="form-control text-center px-1" value="${item.qty}" min="1" onchange="updateQty(${index}, this.value)">
                                <div class="input-group-append">
                                    <button class="btn btn-outline-secondary btn-plus" type="button" data-index="${index}">+</button>
                                </div>
                            </div>
                        </td>
                        <td class="align-middle">${itemTotal.toFixed(2)}</td>
                        <td class="align-middle text-center"><button class="btn btn-danger btn-sm btn-remove" data-index="${index}"><i class="fas fa-trash"></i></button></td>
                    </tr>`;
                    tbody.append(row);
                });
                $('#order-total').text(total.toFixed(2));
            }

            $('#place-order-btn').on('click', function() {
                if (window.orderItems.length === 0) {
                    Swal.fire("Error", "Cart is empty", "error");
                    return;
                }

                var orderId = <?php echo $order_id; ?>;
                if (orderId === 0) {
                    Swal.fire("Error", "Invalid Order ID. Please select a table first.", "error");
                    return;
                }

                // Disable button to prevent accidental double-submission
                var $btn = $(this);
                $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Placing Order...');

                $.ajax({
                    url: 'place_order.php',
                    type: 'POST',
                    data: {
                        order_id: orderId,
                        items: JSON.stringify(window.orderItems)
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 'success') {
                            var ids = response.ids.join(',');
                            var kot_num = response.kot_num;
                            
                            // Clear the local cart immediately
                            window.orderItems = [];

                            // Print KOT via hidden iframe without opening new window
                            var iframe = document.createElement('iframe');
                            iframe.style.display = 'none';
                            iframe.src = 'print_kot.php?order_id=' + orderId + '&ids=' + ids + '&kot_num=' + kot_num;
                            document.body.appendChild(iframe);

                            // Redirect after print (JS pauses during print dialog in most browsers)
                            setTimeout(function() {
                                window.location.href = 'waitor.php';
                            }, 1000);
                        } else {
                            Swal.fire("Error", "Failed to place order: " + response.message, "error");
                            $btn.prop('disabled', false).text('Confirm Order');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error(xhr.responseText);
                        Swal.fire("Error", "AJAX request failed: " + error + ". Check console for details.", "error");
                    }
                });
            });

            window.updateQty = function(index, val) {
                var newQty = parseInt(val);
                if (isNaN(newQty) || newQty < 1) {
                    newQty = 1;
                }
                window.orderItems[index].qty = newQty;
                renderOrderTable();
            };

            $(document).on('click', '.btn-minus', function() {
                var index = $(this).data('index');
                if (window.orderItems[index].qty > 1) {
                    window.orderItems[index].qty--;
                } else {
                    window.orderItems.splice(index, 1);
                }
                renderOrderTable();
            });

            $(document).on('click', '.btn-plus', function() {
                var index = $(this).data('index');
                window.orderItems[index].qty++;
                renderOrderTable();
            });

            $(document).on('click', '.btn-remove', function() {
                var index = $(this).data('index');
                window.orderItems.splice(index, 1);
                renderOrderTable();
            });

            function renderItems() {
                var selectedParentId = $('#parentCategorySelect').val();
                var selectedSubId = $('#subCategorySelect').val();
                var container = $('#items-container');
                
                container.empty();
                
                var validSubIds = [];
                
                if (selectedSubId !== 'all') {
                    validSubIds.push(selectedSubId);
                } else {
                    // If 'all' subcategories is selected, find valid sub-categories based on parent
                    categoriesList.forEach(function(cat) {
                        if (selectedParentId === 'all' || cat.ParentCategoryID == selectedParentId) {
                            validSubIds.push(cat.ID);
                        }
                    });
                }

                var html = '<div class="row">';
                var hasProducts = false;
                
                validSubIds.forEach(function(subId) {
                    if (productsData[subId]) {
                        productsData[subId].forEach(function(product) {
                            hasProducts = true;
                            var isOutOfStock = (product.Type === 'Countable' && product.Quantity <= 0);
                            var opacity = isOutOfStock ? 'style="opacity: 0.6; cursor: not-allowed;"' : 'style="cursor:pointer;"';
                            var clickHandler = isOutOfStock ? '' : 'onclick="addToOrder(' + product.ID + ', \'' + product.ProductName.replace(/'/g, "\\'") + '\', ' + product.Price + ')"';
                            var stockLabel = isOutOfStock ? '<br><span class="badge badge-danger">Out of Stock</span>' : '';

                            html += '<div class="col-xl-3 col-md-4 col-6 mb-3">';
                            html += '<div class="card shadow h-100 py-2 border-left-primary product-btn" ' + opacity + ' ' + clickHandler + '>';
                            html += '<div class="card-body text-center p-2">';
                            html += '<h6 class="font-weight-bold text-gray-800 mb-1">' + product.ProductName + stockLabel + '</h6>';
                            html += '<div class="h5 mb-0 font-weight-bold text-primary">$' + parseFloat(product.Price).toFixed(2) + '</div>';
                            html += '</div></div></div>';
                        });
                    }
                });
                
                html += '</div>';
                
                if (!hasProducts) {
                    container.html('<div class="alert alert-info text-center">No products found for this selection.</div>');
                } else {
                    container.html(html);
                }
            }

            $('#parentCategorySelect').on('change', function() {
                var parentId = $(this).val();
                
                $subCategorySelect.empty();
                $subCategorySelect.append('<option value="all">All Sub Categories</option>');
                
                $allSubOptions.each(function() {
                    var optionParent = $(this).data('parent-id');
                    if (parentId === 'all' || optionParent == parentId) {
                        $subCategorySelect.append($(this));
                    }
                });
                
                $subCategorySelect.val('all');
                renderItems();
            });

            $('#subCategorySelect').on('change', function() {
                renderItems();
            });

            // Initial render
            renderItems();
        });
    </script>

</body>

</html>