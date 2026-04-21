<?php
include('../include/dbconnection.php');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['table_id'])) {
    $tableId = intval($_POST['table_id']);
    
    // Start Transaction
    mysqli_begin_transaction($con);
    
    try {
        // 1. Update Table Status to '2' (Seated)
        $stmt = $con->prepare("UPDATE tbltables SET Status = '2' WHERE ID = ?");
        $stmt->bind_param("i", $tableId);
        $stmt->execute();
        $stmt->close();

        // 2. Create New Order
        $stmt = $con->prepare("INSERT INTO tblorder (TableID, Status, OrderType, OrderDate) VALUES (?, 'Pending', 'Dine In', NOW())");
        $stmt->bind_param("i", $tableId);
        $stmt->execute();
        $orderId = $stmt->insert_id;
        $stmt->close();

        mysqli_commit($con);
        echo json_encode(['status' => 'success', 'order_id' => $orderId]);
    } catch (Exception $e) {
        mysqli_rollback($con);
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    $con->close();
}
?>