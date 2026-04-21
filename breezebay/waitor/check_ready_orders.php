<?php
session_start();
include('../include/dbconnection.php');

if (empty($_SESSION['uid'])) {
    exit;
}

$staff_id = $_SESSION['uid'];

// Fetch the first ready item specifically assigned to this waiter's staff_id
$sql = "SELECT od.ID, od.OrderID, od.KOT, t.TableName, b.notification_sound 
        FROM tblorder_details od 
        JOIN tblorder o ON od.OrderID = o.ID 
        LEFT JOIN tbltables t ON o.TableID = t.ID 
        JOIN tblbranding b ON b.ID = 1
        WHERE od.order_status = 2 AND od.staff_id = '$staff_id' 
        LIMIT 1";

$result = mysqli_query($con, $sql);
if ($row = mysqli_fetch_assoc($result)) {
    echo json_encode([
        'ready' => true,
        'id' => $row['ID'],
        'order_id' => $row['OrderID'],
        'kot' => $row['KOT'],
        'table_name' => $row['TableName'] ?? 'Take-away',
        'sound' => $row['notification_sound']
    ]);
} else {
    echo json_encode(['ready' => false]);
}
?>