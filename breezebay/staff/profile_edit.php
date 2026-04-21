<?php
session_start();
include('../include/dbconnection.php');

if (empty($_SESSION['uid'])) {
    header('location:../auth/login.php');
    exit;
}

$message = '';
$staff_id = $_SESSION['uid'];

if (isset($_POST['submit'])) {
    $staff_name = $_POST['staff_name'];
    $staff_nic = $_POST['staff_nic'];
    $staff_telephone = $_POST['staff_telephone'];
    $staff_username = $_POST['staff_username'];
    $staff_password = $_POST['staff_password'];

    // Check for duplicate username excluding current user
    $check_stmt = $con->prepare("SELECT ID FROM tblstaff WHERE UserName = ? AND ID != ?");
    $check_stmt->bind_param("si", $staff_username, $staff_id);
    $check_stmt->execute();
    $check_stmt->store_result();

    if ($check_stmt->num_rows > 0) {
        $message = '<div class="alert alert-danger">Username already exists. Please choose a different one.</div>';
    } else {
        if (!empty($staff_password)) {
            $hashed_password = password_hash($staff_password, PASSWORD_DEFAULT);
            $stmt = $con->prepare("UPDATE tblstaff SET StaffName=?, StaffNIC=?, StaffTel=?, UserName=?, Password=? WHERE ID=?");
            $stmt->bind_param("sssssi", $staff_name, $staff_nic, $staff_telephone, $staff_username, $hashed_password, $staff_id);
        } else {
            $stmt = $con->prepare("UPDATE tblstaff SET StaffName=?, StaffNIC=?, StaffTel=?, UserName=? WHERE ID=?");
            $stmt->bind_param("ssssi", $staff_name, $staff_nic, $staff_telephone, $staff_username, $staff_id);
        }

        if ($stmt->execute()) {
            $message = '<div class="alert alert-success">Profile updated successfully.</div>';
            $_SESSION['name'] = $staff_name;
        } else {
            $message = '<div class="alert alert-danger">Error: ' . htmlspecialchars($stmt->error) . '</div>';
        }
        $stmt->close();
    }
    $check_stmt->close();
}

// Fetch Current Data
$stmt = $con->prepare("SELECT * FROM tblstaff WHERE ID = ?");
$stmt->bind_param("i", $staff_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$stmt->close();

// Fetch roles for display
$roles_sql = "SELECT role_name FROM tblrole ORDER BY role_name ASC";
$roles_result = mysqli_query($con, $roles_sql);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    
    <title>RMS - Profile Edit</title>
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
                    <h1 class="h3 mb-4 text-gray-800">Profile Edit</h1>

                    <?php if (!empty($message)) echo $message; ?>

                    <div class="card shadow mb-4">
                        
                        <div class="card-body">
                            <form method="post">
                                <div class="form-group">
                                    <label for="staff_name">Staff Name</label>
                                    <input type="text" class="form-control" id="staff_name" name="staff_name" value="<?php echo htmlspecialchars($row['StaffName']); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label for="staff_nic">Staff NIC No</label>
                                    <input type="text" class="form-control" id="staff_nic" name="staff_nic" value="<?php echo htmlspecialchars($row['StaffNIC']); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label for="staff_telephone">Staff Telephone No</label>
                                    <input type="tel" class="form-control" id="staff_telephone" name="staff_telephone" value="<?php echo htmlspecialchars($row['StaffTel']); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label for="staff_role">Role</label>
                                    <select class="form-control" id="staff_role" name="staff_role" disabled>
                                        <option value="">Select Role</option>
                                        <?php
                                        if ($roles_result && mysqli_num_rows($roles_result) > 0) {
                                            while ($role = mysqli_fetch_assoc($roles_result)) {
                                                $selected = ($row['StaffRole'] == $role['role_name']) ? 'selected' : '';
                                                echo '<option value="' . htmlspecialchars($role['role_name']) . '" ' . $selected . '>' . htmlspecialchars($role['role_name']) . '</option>';
                                            }
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="staff_username">Username</label>
                                    <input type="text" class="form-control" id="staff_username" name="staff_username" value="<?php echo htmlspecialchars($row['UserName']); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label for="staff_password">New Password (leave blank to keep current)</label>
                                    <input type="password" class="form-control" id="staff_password" name="staff_password">
                                </div>
                                <button type="submit" name="submit" class="btn btn-primary">Update Profile</button>
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

</body>

</html>