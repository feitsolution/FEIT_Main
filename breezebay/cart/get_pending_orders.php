<?php
session_start();
include('../include/dbconnection.php');

header('Content-Type: application/json');

if (empty($_SESSION['uid'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

// Fetch all order details that are currently "Pending" (status = 0)
$query = "SELECT d.ID, d.OrderID, d.KOT, d.Qty, p.ProductName 
          FROM tblorder_details d 
          LEFT JOIN tblproducts p ON d.ProductID = p.ID 
          WHERE d.order_status = 0 
          ORDER BY d.OrderID ASC, d.KOT ASC";

$result = mysqli_query($con, $query);

if (!$result) {
    echo json_encode(['status' => 'error', 'message' => mysqli_error($con)]);
    exit;
}

$items = [];
while($row = mysqli_fetch_assoc($result)) {
    $items[] = $row;
}

echo json_encode(['status' => 'success', 'data' => $items]);
?>
