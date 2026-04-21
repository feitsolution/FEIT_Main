<?php
session_start();
include('../include/dbconnection.php');

if (empty($_SESSION['uid'])) {
    header('location:../auth/login.php');
    exit;
}

$roles_sql = "SELECT role_name FROM tblrole ORDER BY role_name ASC";
$roles_result = mysqli_query($con, $roles_sql);

$roles = [];
if ($roles_result && mysqli_num_rows($roles_result) > 0) {
    while ($role = mysqli_fetch_assoc($roles_result)) {
        $roles[] = $role;
    }
}

header('Content-Type: application/json');
echo json_encode($roles);
mysqli_close($con);
?>