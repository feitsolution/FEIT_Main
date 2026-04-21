<?php
session_start();
include('../include/dbconnection.php');

header('Content-Type: application/json');

if (empty($_SESSION['uid'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['order_id']) && isset($_POST['items'])) {
    $order_id = intval($_POST['order_id']);
    $items = json_decode($_POST['items'], true);
    $inserted_ids = [];
    $orderTotalToAdd = 0;

    if ($order_id <= 0 || empty($items)) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid data']);
        exit;
    }

    mysqli_begin_transaction($con);

    try {
        // Generate a new sequential KOT number for this specific serving
        $today = date('Y-m-d');
        $currentTime = date('h:i:s A');
        $stmt_kot = $con->prepare("SELECT MAX(CAST(KOT AS UNSIGNED)) FROM tblorder_details WHERE OrderDate = ?");
        $stmt_kot->bind_param("s", $today);
        $stmt_kot->execute();
        $stmt_kot->bind_result($max_kot);
        $stmt_kot->fetch();
        $stmt_kot->close();

        $kot_num = $max_kot ? $max_kot + 1 : 1;

        // Prepare insert statement. 
        // Note: We DO NOT run a DELETE query here. We only append.
        // This keeps existing items and their order_status intact.
        $stmt = $con->prepare("INSERT INTO tblorder_details (OrderID, ProductID, Qty, Price, KOT, OrderDate, OrderTime, order_status, staff_id) VALUES (?, ?, ?, ?, ?, ?, ?, 0, ?)");
        
        $staff_id = intval($_SESSION['uid']);

        foreach ($items as $item) {
            $pid = intval($item['id']);
            $qty = intval($item['qty']);
            $price = floatval($item['price']);
            $total = $price * $qty;

            $stmt->bind_param("iiidsssi", $order_id, $pid, $qty, $price, $kot_num, $today, $currentTime, $staff_id);
            if ($stmt->execute()) {
                $inserted_ids[] = $stmt->insert_id;
                $orderTotalToAdd += $total;
            }
        }
        $stmt->close();

        // Update Main Order Total
        $stmt_update = $con->prepare("UPDATE tblorder SET TotalAmount = TotalAmount + ? WHERE ID = ?");
        $stmt_update->bind_param("di", $orderTotalToAdd, $order_id);
        $stmt_update->execute();
        $stmt_update->close();

        mysqli_commit($con);
        echo json_encode(['status' => 'success', 'ids' => $inserted_ids, 'kot_num' => $kot_num]);
    } catch (Exception $e) {
        mysqli_rollback($con);
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    $con->close();
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
}
?>