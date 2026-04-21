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
    <title>RMS - Category Add</title>
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
                 <!-- Menu Category Add-->
                <div class="container-fluid">

                    <!-- Page Heading -->
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <!--<h1 class="h3 mb-0 text-gray-800">Menu Category Add</h1>-->
                        <button class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm" data-toggle="modal" data-target="#addParentCategoryModal"><i class="fas fa-plus fa-sm text-white-50"></i> New Parent Category</button>
                    </div>

                    <div id="message"></div>

                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Add New Menu Category</h6>
                        </div>
                        <div class="card-body">
                            <form id="category-form" method="post">
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
                                            <label for="category_name">Sub Category Name</label>
                                            <input type="text" class="form-control" id="category_name" name="category_name" required>
                                        </div>
                                    </div>
                                </div>
                                
                                <button type="submit" class="btn btn-primary">Add Category</button>
                            </form>
                        </div>
                    </div>

                </div>
                <!-- Menu Category Add End-->
                   <!-- Menu Category List-->
                <div class="container-fluid"> 
                <!--?php include_once('../../Menu/categories/category_list.php'); ?>-->
                </div>
                <!-- Menu Category List End-->
                <!-- /.container-fluid -->

            </div>
            <!-- End of Main Content -->

            <?php include_once('../../include/footer.php'); ?>

        </div>
        <!-- End of Content Wrapper -->

    </div>
    <!-- End of Page Wrapper -->

    <!-- Modal for Adding New Parent Category -->
    <div class="modal fade" id="addParentCategoryModal" tabindex="-1" role="dialog" aria-labelledby="parentCategoryTitle" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="parentCategoryTitle">Add Parent Category</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div id="parent-message"></div>
                    <form id="parent-category-form" method="post">
                        <div class="form-group">
                            <label for="parent_category_name">Category Name</label>
                            <input type="text" class="form-control" id="parent_category_name" name="parent_category_name" placeholder="Enter category name" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onclick="$('#parent-category-form').submit();">Save Category</button>
                </div>
            </div>
        </div>
    </div>

    <?php include_once('../../include/script.php'); ?>

    <script>
    $(document).ready(function() {
        function loadParentCategories() {
            $.ajax({
                url: 'parent_category_fetch.php',
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
        }
        loadParentCategories();

        $('#category-form').on('submit', function(e) {
            e.preventDefault();
            var formData = new FormData(this);

            $.ajax({
                url: 'category_submit.php',
                type: 'POST',
                data: formData,
                contentType: false,
                processData: false,
                success: function(response) {
                    var messageContainer = $('#message');
                    if (response.trim() === 'yes') {
                        messageContainer.html('<div class="alert alert-success">Category added successfully. Redirecting to category list...</div>');
                        $('#category-form')[0].reset();
                        setTimeout(function() {
                            window.location.href = '/breezebay/Menu/categories/category_list.php';
                        }, 1500);
                    } else {
                        messageContainer.html('<div class="alert alert-danger">Failed to add category: ' + response + '</div>');
                    }
                },
                error: function() {
                    $('#message').html('<div class="alert alert-danger">An error occurred during the update.</div>');
                }
            });
        });

        // Handle Parent Category Form Submission
        $('#parent-category-form').on('submit', function(e) {
            e.preventDefault();
            var formData = new FormData(this);

            $.ajax({
                url: 'parent_category_submit.php',
                type: 'POST',
                data: formData,
                contentType: false,
                processData: false,
                success: function(response) {
                    var messageContainer = $('#parent-message');
                    if (response.trim() === 'yes') {
                        messageContainer.html('<div class="alert alert-success">Parent Category added successfully.</div>');
                        $('#parent-category-form')[0].reset();
                        loadParentCategories(); // Reload the dropdown
                        setTimeout(function() {
                            $('#addParentCategoryModal').modal('hide');
                            messageContainer.html(''); 
                        }, 1500);
                    } else {
                        messageContainer.html('<div class="alert alert-danger">Failed: ' + response + '</div>');
                    }
                },
                error: function() {
                    $('#parent-message').html('<div class="alert alert-danger">An error occurred.</div>');
                }
            });
        });
    });
    </script>

</body>

</html>