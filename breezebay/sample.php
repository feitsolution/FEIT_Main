<?php

include('include/dbconnection.php');



$message = '';
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $age = $_POST['age'];

    // Using prepared statements to prevent SQL injection
    $stmt = $con->prepare("INSERT INTO tblperson (Name, Age) VALUES (?, ?)");
    $stmt->bind_param("si", $name, $age);

    if ($stmt->execute()) {
        $message = '<div class="alert alert-success">Data inserted successfully.</div>';
    } else {
        $message = '<div class="alert alert-danger">Something went wrong. Please try again. Error: ' . htmlspecialchars($stmt->error) . '</div>';
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    
    <title>RMS - Sample Page</title>
    <?php include_once('include/header.php'); ?>
</head>

<body id="page-top">

    <!-- Page Wrapper -->
    <div id="wrapper">

        <?php include_once('include/sidebar.php'); ?>

        <!-- Content Wrapper -->
        <div id="content-wrapper" class="d-flex flex-column">

            <!-- Main Content -->
            <div id="content">

                <?php include_once('include/top-nav.php'); ?>

                <!-- Begin Page Content -->
                <div class="container-fluid">

                    <!-- Page Heading -->
                    <h1 class="h3 mb-4 text-gray-800">Name and Age Form</h1>

                    <?php if (!empty($message)) echo $message; ?>

                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Insert Person Details</h6>
                        </div>
                        <div class="card-body">
                            <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>">
                                <div class="form-group">
                                    <label for="name">Name:</label>
                                    <input type="text" class="form-control" id="name" name="name" required>
                                </div>
                                <div class="form-group">
                                    <label for="age">Age:</label>
                                    <input type="number" class="form-control" id="age" name="age" required>
                                </div>
                                <button type="submit" class="btn btn-primary">Submit</button>
                            </form>
                        </div>
                    </div>

                </div>
                <!-- /.container-fluid -->

            </div>
            <!-- End of Main Content -->

            <?php include_once('include/footer.php'); ?>

        </div>
        <!-- End of Content Wrapper -->

    </div>
    <!-- End of Page Wrapper -->

    <?php include_once('include/script.php'); ?>

</body>

</html>