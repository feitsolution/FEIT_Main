<?php
session_start();
include('../include/dbconnection.php');

if (empty($_SESSION['uid'])) {
    header('location:../auth/login.php');
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $staff_id = $_POST['staff_id'];
    $staff_name = $_POST['staff_name'];
    $staff_nic = $_POST['staff_nic'];
    $staff_telephone = $_POST['staff_telephone'];
    $staff_role = $_POST['staff_role'];
    $staff_username = $_POST['staff_username'];
    $staff_password = $_POST['staff_password'];

    // Basic validation
    if (empty($staff_id) || empty($staff_name) || empty($staff_nic) || empty($staff_telephone) || empty($staff_role) || empty($staff_username)) {
        echo "All fields except password are required.";
        exit;
    }

    // Check for duplicate username, excluding the current staff member
    $check_stmt = $con->prepare("SELECT ID FROM tblstaff WHERE UserName = ? AND ID != ?");
    $check_stmt->bind_param("si", $staff_username, $staff_id);
    $check_stmt->execute();
    $check_stmt->store_result();
    if ($check_stmt->num_rows > 0) {
        echo "Username already exists. Please choose a different one.";
        $check_stmt->close();
        $con->close();
        exit;
    }
    $check_stmt->close();

    // Prepare the update query
    if (!empty($staff_password)) {
        // If password is being updated
        $hashed_password = password_hash($staff_password, PASSWORD_DEFAULT);
        $stmt = $con->prepare("UPDATE tblstaff SET StaffName=?, StaffNIC=?, StaffTel=?, StaffRole=?, UserName=?, Password=? WHERE ID=?");
        $stmt->bind_param("ssssssi", $staff_name, $staff_nic, $staff_telephone, $staff_role, $staff_username, $hashed_password, $staff_id);
    } else {
        // If password is not being updated
        $stmt = $con->prepare("UPDATE tblstaff SET StaffName=?, StaffNIC=?, StaffTel=?, StaffRole=?, UserName=? WHERE ID=?");
        $stmt->bind_param("sssssi", $staff_name, $staff_nic, $staff_telephone, $staff_role, $staff_username, $staff_id);
    }

    if ($stmt->execute()) {
        echo 'yes';
    } else {
        echo 'Something went wrong. Please try again. Error: ' . htmlspecialchars($stmt->error);
    }
    $stmt->close();
    $con->close();
} else {
    echo "Invalid request method.";
}
?>