<?php
session_start();
include('../include/dbconnection.php');

if (empty($_SESSION['uid'])) {
    header('location:../auth/login.php');
    exit;
}

// Fetch roles from the database
$roles_sql = "SELECT role_name FROM tblrole ORDER BY role_name ASC";
$roles_result = mysqli_query($con, $roles_sql);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    
    <title>RMS - Table Add</title>
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
                    <!--<h1 class="h3 mb-4 text-gray-800">Table Add</h1>-->

                    <div id="message"></div>

                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Add New Table</h6>
                        </div>
                        <div class="card-body">
                            <form id="table-form" method="post">
                                <div class="form-group">
                                    <label for="table_name">Table No</label>
                                    <input type="text" class="form-control" id="table_name" name="table_name" required>
                                </div>
                                <div class="form-group">
                                    <label for="table_chairs">Chair Count</label>
                                    <input type="text" class="form-control" id="table_chairs" name="table_chairs" required>
                                </div>                                
                                <button type="submit" class="btn btn-primary">Add Table</button>
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
        $('#table-form').on('submit', function(e) {
            e.preventDefault();
            var formData = new FormData(this);

            $.ajax({
                url: 'table_submit.php',
                type: 'POST',
                data: formData,
                contentType: false,
                processData: false,
                success: function(response) {
                    var messageContainer = $('#message');
                    if (response.trim() === 'yes') {
                        messageContainer.html('<div class="alert alert-success">Table added successfully. Redirecting to table list...</div>');
                        $('#table-form')[0].reset();
                        setTimeout(function() {
                            window.location.href = 'table_list.php';
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