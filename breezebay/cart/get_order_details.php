<?php
include('../include/dbconnection.php');

header('Content-Type: application/json');

if (isset($_GET['table_id'])) {
    $table_id = intval($_GET['table_id']);
    
    // Get active order for the table
    // Status 'Pending' is assumed to be the active status for orders on tables
    $query = "SELECT ID FROM tblorder WHERE TableID = ? AND Status = 'Pending' LIMIT 1";
    $stmt = $con->prepare($query);
    $stmt->bind_param("i", $table_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $order_id = $row['ID'];
        
        $details_query = "SELECT d.ProductID as id, p.ProductName as name, d.Price as price, d.Qty as qty, d.staff_id
                          FROM tblorder_details d 
                          JOIN tblproducts p ON d.ProductID = p.ID 
                          WHERE d.OrderID = ?";
        $stmt_d = $con->prepare($details_query);
        $stmt_d->bind_param("i", $order_id);
        $stmt_d->execute();
        $details_result = $stmt_d->get_result();
        
        $items = [];
        while ($item = $details_result->fetch_assoc()) {
            $item['id'] = intval($item['id']);
            $item['price'] = floatval($item['price']);
            $item['qty'] = intval($item['qty']);
            $item['staff_id'] = intval($item['staff_id']);
            $items[] = $item;
        }
        
        echo json_encode(['status' => 'success', 'items' => $items, 'order_id' => $order_id]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'No active order found']);
    }
}
?>