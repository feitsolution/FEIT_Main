<?php
session_start();
include('../include/dbconnection.php');

if (empty($_SESSION['uid'])) {
    header('location:../auth/login.php');
    exit;
}

$filter_date = isset($_GET['filter_date']) ? $_GET['filter_date'] : '';

// Fetch reservations and join with tables to get the table name
$sql = "SELECT 
            r.ID, 
            r.CustomerName, 
            r.CustomerContact, 
            r.Pax, 
            r.ReservationDate, 
            r.TableID, 
            r.Status,
            r.pay_amount,
            t.TableName 
        FROM tblreservation r 
        LEFT JOIN tbltables t ON r.TableID = t.ID";

if (!empty($filter_date)) {
    $filter_date = mysqli_real_escape_string($con, $filter_date);
    $sql .= " WHERE r.ReservationDate = '$filter_date'";
}

$sql .= " ORDER BY r.ReservationDate DESC";

$result = mysqli_query($con, $sql);
$data = array();
while ($row = mysqli_fetch_assoc($result)) {
    $data[] = $row;
}

header('Content-Type: application/json');
echo json_encode(array('data' => $data));
?>
