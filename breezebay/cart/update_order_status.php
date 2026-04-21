<?php
session_start();
include('../include/dbconnection.php');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['ids']) && isset($_POST['status'])) {
    $ids_str = $_POST['ids'];
    $status = intval($_POST['status']);
    
    // Security check to avoid SQL injection
    $ids_array = explode(',', $ids_str);
    $safe_ids = [];
    foreach($ids_array as $id) {
        if (intval($id) > 0) {
            $safe_ids[] = intval($id);
        }
    }
    
    if (empty($safe_ids)) {
        echo json_encode(['status' => 'error', 'message' => 'No valid IDs provided.']);
        exit;
    }
    
    $ids_query_part = implode(',', $safe_ids);

    // If moving to Processing (1), decrease stock for Countable products
    if ($status == 1) {
        $stock_sql = "UPDATE tblproducts p
                      JOIN tblorder_details od ON p.ID = od.ProductID
                      SET p.Quantity = p.Quantity - od.Qty
                      WHERE od.ID IN ($ids_query_part) 
                      AND od.order_status = 0 
                      AND p.Type = 'Countable'";
        mysqli_query($con, $stock_sql);
    }
    
    // Update the statuses
    $query = "UPDATE tblorder_details SET order_status = $status WHERE ID IN ($ids_query_part)";
    
    if (mysqli_query($con, $query)) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => mysqli_error($con)]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request data.']);
}
?>
