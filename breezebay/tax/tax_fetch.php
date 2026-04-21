<?php
session_start();
include('../include/dbconnection.php');

if (empty($_SESSION['uid'])) {
    header('location:../auth/login.php');
    exit;
}

$sql = "SELECT ID, TaxName, TaxPercentage, Status FROM tbltax";
$result = mysqli_query($con, $sql);

$data = array();
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $data[] = $row;
    }
}

echo json_encode(array('data' => $data));

mysqli_close($con);
?>