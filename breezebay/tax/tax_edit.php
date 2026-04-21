<?php
session_start();
include('../include/dbconnection.php');

if (empty($_SESSION['uid'])) {
    header('location:../auth/login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    
    <title>RMS - Tax Edit</title>
    <?php include_once('../include/header.php'); ?>
</head>

<body id="page-top">

    <!-- Page Wrapper -->
    <div id="wrapper">

        <?php include_once('../include/sidebar.php'); ?>

        <!-- Content Wrapper -->
        <div id="content-wrapper" class="d-flex flex-column">

            <!-- Main Content -->
            <div id="content">

                <?php include_once('../include/top-nav.php'); ?>

                <!-- Begin Page Content -->
                <div class="container-fluid">

                    <!-- Page Heading -->
                    <h1 class="h3 mb-4 text-gray-800">Tax Edit</h1>

                    <div id="message"></div>

                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Edit Tax</h6>
                        </div>
                        <div class="card-body">
                            <form id="tax-form" method="post">
                                <div class="form-group">
                                    <label for="tax_name">Tax Name</label>
                                    <input type="text" class="form-control" id="tax_name" name="tax_name" required>
                                </div>
                                <div class="form-group">
                                    <label for="tax_percentage">Tax Percentage (%)</label>
                                    <input type="number" class="form-control" id="tax_percentage" name="tax_percentage" step="0.01" min="0" required>
                                </div>                                
                                <button type="submit" class="btn btn-primary">Add Tax</button>
                            </form>
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

    <script>
    $(document).ready(function() {
        $('#tax-form').on('submit', function(e) {
            e.preventDefault();
            var formData = new FormData(this);

            $.ajax({
                url: 'tax_submit.php',
                type: 'POST',
                data: formData,
                contentType: false,
                processData: false,
                success: function(response) {
                    var messageContainer = $('#message');
                    if (response.trim() === 'yes') {
                        messageContainer.html('<div class="alert alert-success">Tax added successfully. Page will reload.</div>');
                        setTimeout(function() {
                            location.reload();
                        }, 1500);
                    } else {
                        messageContainer.html('<div class="alert alert-danger">Update failed: ' + response + '</div>');
                    }
                },
                error: function() {
                    $('#message').html('<div class="alert alert-danger">An error occurred during the update.</div>');
                }
            });
        });
    });
    </script>

</body>

</html>