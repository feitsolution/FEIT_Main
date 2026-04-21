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
    
    <title>RMS - Staff Add</title>
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
                    <!--<h1 class="h3 mb-4 text-gray-800">Staff Add</h1>-->

                    <div id="message"></div>

                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Add New Staff</h6>
                        </div>
                        <div class="card-body">
                            <form id="staff-form" method="post">
                                <div class="form-group">
                                    <label for="staff_name">Staff Name</label>
                                    <input type="text" class="form-control" id="staff_name" name="staff_name" required>
                                </div>
                                <div class="form-group">
                                    <label for="staff_nic">Staff NIC No</label>
                                    <input type="text" class="form-control" id="staff_nic" name="staff_nic" required>
                                </div>
                                <div class="form-group">
                                    <label for="staff_telephone">Staff Telephone No</label>
                                    <input type="tel" class="form-control" id="staff_telephone" name="staff_telephone" required>
                                </div>
                                <div class="form-group">
                                    <label for="staff_role">Role</label>
                                    <select class="form-control" id="staff_role" name="staff_role" required>
                                        <option value="">Select Role</option>
                                        <?php
                                        if ($roles_result && mysqli_num_rows($roles_result) > 0) {
                                            while ($role = mysqli_fetch_assoc($roles_result)) {
                                                echo '<option value="' . htmlspecialchars($role['role_name']) . '">' . htmlspecialchars($role['role_name']) . '</option>';
                                            }
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="staff_username">Username</label>
                                    <input type="text" class="form-control" id="staff_username" name="staff_username" required>
                                </div>
                                <div class="form-group">
                                    <label for="staff_password">Password</label>
                                    <input type="password" class="form-control" id="staff_password" name="staff_password" required>
                                </div>
                                <button type="submit" class="btn btn-primary">Add Staff</button>
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
        $('#staff-form').on('submit', function(e) {
            e.preventDefault();
            var formData = new FormData(this);

            $.ajax({
                url: 'staff_submit.php',
                type: 'POST',
                data: formData,
                contentType: false,
                processData: false,
                success: function(response) {
                    var messageContainer = $('#message');
                    if (response.trim() === 'yes') {
                        messageContainer.html('<div class="alert alert-success">Staff added successfully. Redirecting to staff list...</div>');
                        $('#staff-form')[0].reset();
                        setTimeout(function() {
                            window.location.href = '/breezebay/staff/staff_list.php';
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