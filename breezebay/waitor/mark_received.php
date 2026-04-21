<?php
session_start();
include('../include/dbconnection.php');

if (empty($_SESSION['uid']) || empty($_POST['order_id']) || empty($_POST['kot'])) {
    exit;
}

$order_id = intval($_POST['order_id']);
$kot = mysqli_real_escape_string($con, $_POST['kot']);

// Update all 'Ready' items in this KOT for this order to Received/Served (3)
$sql = "UPDATE tblorder_details SET order_status = 3 WHERE OrderID = '$order_id' AND KOT = '$kot' AND order_status = 2";
if (mysqli_query($con, $sql)) {
    echo "success";
}
?>