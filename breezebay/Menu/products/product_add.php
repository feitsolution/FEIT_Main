<?php
session_start();
include('../../include/dbconnection.php');

if (empty($_SESSION['uid'])) {
    header('location:../auth/login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>

    <title>RMS - Product Add</title>
    <?php include_once('../../include/header.php'); ?>
</head>

<body id="page-top">

    <!-- Page Wrapper -->
    <div id="wrapper">

        <?php include_once('../../include/sidebar.php'); ?>

        <!-- Content Wrapper -->
        <div id="content-wrapper" class="d-flex flex-column">

            <!-- Main Content -->
            <div id="content">

                <?php include_once('../../include/top-nav.php'); ?>

                <!-- Begin Page Content -->
                <div class="container-fluid">

                    <!-- Page Heading -->
                    <!--<h1 class="h3 mb-4 text-gray-800">Product Add</h1>-->

                    <div id="message"></div>

                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Add New Product</h6>
                        </div>
                        <div class="card-body">
                            <form id="product-form" method="post">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="parent_category">Parent Category</label>
                                            <select class="form-control" id="parent_category" name="parent_category" required>
                                                <option value="">Select Parent Category</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="sub_category">Sub Category</label>
                                            <select class="form-control" id="sub_category" name="sub_category" required>
                                                <option value="">Select Sub Category</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>Countable/Uncountable Product</label>
                                    <div class="custom-control custom-switch">
                                        <input type="hidden" name="product_countable" value="Uncountable">
                                        <input type="checkbox" class="custom-control-input" id="product_countable" name="product_countable" value="Countable">
                                        <label class="custom-control-label" for="product_countable">Countable</label>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="product_name">Product Name</label>
                                    <input type="text" class="form-control" id="product_name" name="product_name"
                                        required>
                                </div>
                                <div class="form-group" id="product_unit_group">
                                    <label>Select the product unit</label>
                                    <br>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="product_unit"
                                            id="unit_bottles" value="Bottles" required checked>
                                        <label class="form-check-label" for="unit_bottles">
                                            Bottles
                                        </label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="product_unit"
                                            id="unit_packets" value="Packets" required>
                                        <label class="form-check-label" for="unit_packets">
                                            Packets
                                        </label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="product_unit"
                                            id="unit_grams" value="Grams" required>
                                        <label class="form-check-label" for="unit_grams">
                                            Grams(g)
                                        </label>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="product_price">Product Price <small id="price_comment" class="text-danger" style="display:none;">100g price add</small></label>
                                    <input type="number" class="form-control" id="product_price" name="product_price"
                                        step="0.01" required>
                                </div>                               
                                <button type="submit" class="btn btn-primary">Add Product</button>
                            </form>
                        </div>
                    </div>

                </div>
                <!-- /.container-fluid -->

            </div>
            <!-- End of Main Content -->

            <?php include_once('../../include/footer.php'); ?>

        </div>
        <!-- End of Content Wrapper -->

    </div>
    <!-- End of Page Wrapper -->

    <?php include_once('../../include/script.php'); ?>

    <script>
    $(document).ready(function() {
        function checkGrams() {
            if ($('#product_countable').is(':checked') && $('#unit_grams').is(':checked')) {
                $('#price_comment').show();
            } else {
                $('#price_comment').hide();
            }
        }

        function toggleProductUnit() {
            if ($('#product_countable').is(':checked')) {
                $('#product_unit_group').show();
                $('input[name="product_unit"]').prop('disabled', false);
            } else {
                $('#product_unit_group').hide();
                $('input[name="product_unit"]').prop('disabled', true);
            }
            checkGrams();
        }
        toggleProductUnit();
        $('input[name="product_countable"]').change(toggleProductUnit);
        $('input[name="product_unit"]').change(checkGrams);

        // Fetch Parent Categories
        $.ajax({
            url: '../categories/parent_category_fetch.php',
            type: 'GET',
            dataType: 'json',
            success: function(data) {
                var options = '<option value="">Select Parent Category</option>';
                $.each(data, function(index, item) {
                    options += '<option value="' + item.ID + '">' + item.ParentCategoryName + '</option>';
                });
                $('#parent_category').html(options);
            }
        });

        // Fetch Sub Categories on Parent Change
        $('#parent_category').change(function() {
            var parentId = $(this).val();
            if (parentId) {
                $.ajax({
                    url: 'get_subcategories.php',
                    type: 'GET',
                    data: { parent_id: parentId },
                    dataType: 'json',
                    success: function(data) {
                        var options = '<option value="">Select Sub Category</option>';
                        $.each(data, function(index, item) {
                            options += '<option value="' + item.ID + '">' + item.CategoryName + '</option>';
                        });
                        $('#sub_category').html(options);
                    }
                });
            } else {
                $('#sub_category').html('<option value="">Select Sub Category</option>');
            }
        });

        $('#product-form').on('submit', function(e) {
            e.preventDefault();
            var formData = new FormData(this);

            $.ajax({
                url: 'product_submit.php',
                type: 'POST',
                data: formData,
                contentType: false,
                processData: false,
                success: function(response) {
                    var messageContainer = $('#message');
                    if (response.trim() === 'yes') {
                        messageContainer.html(
                            '<div class="alert alert-success">Product added successfully. Redirecting to product list...</div>'
                        );
                        $('#product-form')[0].reset();
                        toggleProductUnit(); // Also reset the unit visibility
                        setTimeout(function() {
                            window.location.href = 'product_list.php';
                        }, 1500);
                    } else {
                        messageContainer.html('<div class="alert alert-danger">Failed to add product: ' + response + '</div>');
                    }
                },
                error: function() {
                    $('#message').html('<div class="alert alert-danger">An error occurred while adding the product.</div>');
                }
            });
        });
    });
    </script>

</body>

</html>