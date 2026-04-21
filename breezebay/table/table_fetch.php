<?php
session_start();
include('../include/dbconnection.php');

if (empty($_SESSION['uid'])) {
    header('location:../auth/login.php');
    exit;
}

$today = date('Y-m-d');

$sql = "SELECT t.ID, t.TableName, t.ChairCount, t.Status,
        (SELECT COUNT(*) FROM tblreservation r WHERE r.TableID = t.ID AND r.ReservationDate = '$today' AND r.Status = 'Confirmed') as ReservedToday
        FROM tbltables t ORDER BY t.ID DESC";

$result = mysqli_query($con, $sql);

$data = array();
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        if ($row['ReservedToday'] > 0 && ($row['Status'] == '0' || $row['Status'] == 'Available')) {
            $row['Status'] = 'Reserved';
        }
        $data[] = $row;
    }
}

header('Content-Type: application/json');
echo json_encode(array('data' => $data));

mysqli_close($con);
?>