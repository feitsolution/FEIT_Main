<?php
session_start();
include('../include/dbconnection.php');

if (empty($_SESSION['uid'])) {
    header('location:../auth/login.php');
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $staff_name = $_POST['staff_name'];
    $staff_nic = $_POST['staff_nic'];
    $staff_telephone = $_POST['staff_telephone'];
    $staff_role = $_POST['staff_role'];
    $staff_username = $_POST['staff_username'];
    $staff_password = $_POST['staff_password'];

    // Basic validation
    if (empty($staff_name) || empty($staff_nic) || empty($staff_telephone) || empty($staff_role) || empty($staff_username) || empty($staff_password)) {
        echo "All fields are required.";
        exit;
    }

    // Check for duplicate username
    $check_stmt = $con->prepare("SELECT ID FROM tblstaff WHERE UserName = ?");
    $check_stmt->bind_param("s", $staff_username);
    $check_stmt->execute();
    $check_stmt->store_result();
    if ($check_stmt->num_rows > 0) {
        echo "Username already exists. Please choose a different one.";
        $check_stmt->close();
        $con->close();
        exit;
    }
    $check_stmt->close();

    // Hash the password for security
    $hashed_password = password_hash($staff_password, PASSWORD_DEFAULT);

    // Using prepared statements to prevent SQL injection
    $stmt = $con->prepare("INSERT INTO tblstaff (StaffName, StaffNIC, StaffTel, StaffRole, UserName, Password) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssss", $staff_name, $staff_nic, $staff_telephone, $staff_role, $staff_username, $hashed_password);

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