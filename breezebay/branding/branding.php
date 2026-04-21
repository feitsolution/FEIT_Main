<?php
session_start();
include('../include/dbconnection.php');

if (empty($_SESSION['uid'])) {
    header('location:../auth/login.php');
    exit;
}



// Fetch current branding details
$sql = "SELECT * FROM tblbranding LIMIT 1";
$result = mysqli_query($con, $sql);
$row = mysqli_fetch_assoc($result);

// Initialize variables with empty strings or fetched data
$company_name = $row['company_name'] ?? '';
$website_name = $row['website_name'] ?? '';
$phone_no = $row['phone_no'] ?? '';
$address = $row['address'] ?? '';
$logo = $row['logo'] ?? '';
$service_charge = $row['service_charge'] ?? '';
$favicon = $row['favicon'] ?? '';
$no_of_pax = $row['pax'] ?? '';
$notification_sound = $row['notification_sound'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    
    <title>RMS - Branding Settings</title>
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
                    <!--<h1 class="h3 mb-4 text-gray-800">Branding Settings</h1>-->

                    <div id="message"></div>

                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Update Branding Details</h6>
                        </div>
                        <div class="card-body">
                            <form id="branding-form" method="post" enctype="multipart/form-data">
                                <div class="form-group">
                                    <label for="company_name">Company Name:</label>
                                    <input type="text" class="form-control" id="company_name" name="company_name" value="<?php echo htmlspecialchars($company_name); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label for="website_name">Website Name:</label>
                                    <input type="text" class="form-control" id="website_name" name="website_name" value="<?php echo htmlspecialchars($website_name); ?>">
                                </div>
                                <div class="form-row">
                                    <div class="form-group col-md-6">
                                        <label for="phone_no">Telephone No:</label>
                                        <input type="text" class="form-control" id="phone_no" name="phone_no" value="<?php echo htmlspecialchars($phone_no); ?>" required>
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label for="service_charge">Service Charge (%):</label>
                                        <input type="number" class="form-control" id="service_charge" name="service_charge" value="<?php echo htmlspecialchars($service_charge); ?>" step="0.01" min="0" required>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="address">Address:</label>
                                    <textarea class="form-control" id="address" name="address" required><?php echo htmlspecialchars($address); ?></textarea>
                                </div>
                                <div class="form-group">
                                    <label for="no_of_pax">No of Pax:</label>
                                    <input type="number" class="form-control" id="no_of_pax" name="no_of_pax" value="<?php echo htmlspecialchars($no_of_pax); ?>" min="1" required>
                                </div>
                                <div class="form-row">
                                    <div class="form-group col-md-6">
                                        <label for="logo">Logo:</label>
                                        <?php if($logo): ?>
                                            <div class="mb-2">
                                                <img src="uploads/<?php echo htmlspecialchars($logo); ?>" alt="Current Logo" style="max-height: 100px;">
                                            </div>
                                        <?php endif; ?>
                                        <input type="file" class="form-control-file" id="logo" name="logo" accept="image/*">
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label for="favicon">Favicon:</label>
                                        <?php if($favicon): ?>
                                            <div class="mb-2">
                                                <img src="uploads/<?php echo htmlspecialchars($favicon); ?>" alt="Current Favicon" style="max-height: 50px;">
                                            </div>
                                        <?php endif; ?>
                                        <input type="file" class="form-control-file" id="favicon" name="favicon" accept="image/*">
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label for="notification_sound">Notification Sound (.mp3):</label>
                                        <?php if($notification_sound): ?>
                                            <div class="mb-2">
                                                <audio controls><source src="uploads/<?php echo htmlspecialchars($notification_sound); ?>" type="audio/mpeg">Your browser does not support the audio element.</audio>
                                            </div>
                                        <?php endif; ?>
                                        <input type="file" class="form-control-file" id="notification_sound" name="notification_sound" accept=".mp3">
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-primary">Update</button>
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
        $('#branding-form').on('submit', function(e) {
            e.preventDefault();
            var formData = new FormData(this);

            $.ajax({
                url: 'branding-update.php',
                type: 'POST',
                data: formData,
                contentType: false,
                processData: false,
                success: function(response) {
                    var messageContainer = $('#message');
                    if (response.trim() === 'yes') {
                        messageContainer.html('<div class="alert alert-success">Branding updated successfully. Page will reload.</div>');
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