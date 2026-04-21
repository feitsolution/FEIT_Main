<?php
session_start();
include('../include/dbconnection.php');

if (empty($_SESSION['uid'])) {
    header('location:../auth/login.php');
    exit;
}

$response = null;
if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $con->prepare("SELECT ID, StaffName, StaffNIC, StaffTel, StaffRole, UserName FROM tblstaff WHERE ID = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $response = $result->fetch_assoc();
    }
    $stmt->close();
}

header('Content-Type: application/json');
echo json_encode($response);
mysqli_close($con);
?>