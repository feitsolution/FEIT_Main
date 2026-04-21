<?php
include('../include/dbconnection.php');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['order_data'])) {
    $data = json_decode($_POST['order_data'], true);
    
    $orderId = isset($data['orderId']) ? intval($data['orderId']) : 0;
    $tableId = isset($data['tableId']) ? intval($data['tableId']) : 0;
    $items = $data['items'];
    $total = floatval($data['total']);
    $serviceCharge = isset($data['serviceCharge']) ? floatval($data['serviceCharge']) : 0;
    $discount = isset($data['discount']) ? floatval($data['discount']) : 0;
    $paymentMethod = isset($data['paymentMethod']) ? $data['paymentMethod'] : 'Cash';
    $orderType = isset($data['orderType']) ? $data['orderType'] : 'Dine In';
    $orderStatus = isset($data['orderStatus']) ? $data['orderStatus'] : 'Paid'; 

    if (empty($items)) {
        echo json_encode(['status' => 'error', 'message' => 'Cart is empty']);
        exit;
    }

    mysqli_begin_transaction($con);

    try {
        $currentTime = date('h:i:s A'); // Capture current time
        $today = date('Y-m-d');
        
        // Generate sequential KOT number for today
        $stmt_kot = $con->prepare("SELECT MAX(CAST(KOT AS UNSIGNED)) FROM tblorder_details WHERE OrderDate = ?");
        $stmt_kot->bind_param("s", $today);
        $stmt_kot->execute();
        $stmt_kot->bind_result($max_kot);
        $stmt_kot->fetch();
        $stmt_kot->close();
        $kot_num = $max_kot ? $max_kot + 1 : 1;

        if ($orderId == 0) {
            // Create new order (Take Away or new Dine-in)
            $stmt = $con->prepare("INSERT INTO tblorder (TableID, OrderDate, TotalAmount, ServiceCharge, Discount, Status, Time, PaymentMethod, OrderType) VALUES (?, NOW(), ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("idddssss", $tableId, $total, $serviceCharge, $discount, $orderStatus, $currentTime, $paymentMethod, $orderType);
            if (!$stmt->execute()) {
                throw new Exception("Order creation failed: " . $stmt->error);
            }
            $orderId = $stmt->insert_id;
            $stmt->close();
            
            // If TableID > 0 (Dine-in), ensure table is marked available (since bill is closed/paid)
            if ($tableId > 0) {
                 $tblStatus = ($orderStatus === 'Paid') ? '0' : '2'; // 0=Available, 2=Seated
                 $stmt_tbl = $con->prepare("UPDATE tbltables SET Status=? WHERE ID=?"); 
                 $stmt_tbl->bind_param("si", $tblStatus, $tableId);
                 $stmt_tbl->execute();
                 $stmt_tbl->close();
            }

        } else {
            // Update existing order (Closing an active Dine-in bill)
            $stmt = $con->prepare("UPDATE tblorder SET TotalAmount=?, ServiceCharge=?, Discount=?, Status=?, Time=?, PaymentMethod=?, OrderType=? WHERE ID=?");
            $stmt->bind_param("dddssssi", $total, $serviceCharge, $discount, $orderStatus, $currentTime, $paymentMethod, $orderType, $orderId);
            if (!$stmt->execute()) {
                throw new Exception("Order update failed: " . $stmt->error);
            }
            $stmt->close();

            // Free the table
            if ($tableId > 0) {
                 $tblStatus = ($orderStatus === 'Paid') ? '0' : '2';
                 $stmt_tbl = $con->prepare("UPDATE tbltables SET Status=? WHERE ID=?");
                 $stmt_tbl->bind_param("si", $tblStatus, $tableId);
                 $stmt_tbl->execute();
                 $stmt_tbl->close();
            }
            
            // Only refresh (delete/re-insert) if we are still in the ordering phase (Pending).
            // This prevents status loss when finally closing the bill.
            if ($orderStatus !== 'Paid') {
                $stmt_del = $con->prepare("DELETE FROM tblorder_details WHERE OrderID=?");
                $stmt_del->bind_param("i", $orderId);
                $stmt_del->execute();
                $stmt_del->close();
            }
        }

        // Insert Order Details
        $inserted_ids = [];

        // Determine the status for order details based on the main order status from cart.php
        // 'Paid' -> from closeBill(), items are completed from POS perspective.
        // 'Pending' -> from printKOT(), items should go to processing.
        $details_status = 0; // Default to Pending (e.g., for waiter-added items)
        if ($orderStatus === 'Paid') {
            $details_status = 3; // Served/Completed
        } else if ($orderStatus === 'Pending') {
            $details_status = 1; // Processing
        }

        // If we are closing the bill, we simply update the status of existing rows 
        // and skip the re-insertion loop to preserve original KOT numbers and times.
        if ($orderId > 0 && $orderStatus === 'Paid') {
            $stmt_upd = $con->prepare("UPDATE tblorder_details SET order_status = ? WHERE OrderID = ?");
            $stmt_upd->bind_param("ii", $details_status, $orderId);
            $stmt_upd->execute();
            $stmt_upd->close();
            
            // Jump to commit
            goto finalize;
        }

        $stmt_detail = $con->prepare("INSERT INTO tblorder_details (OrderID, ProductID, Qty, Price, OrderDate, OrderTime, KOT, order_status, staff_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        foreach ($items as $item) {
            $pid = intval($item['id']);
            $qty = intval($item['qty']);
            $price = floatval($item['price']);
            // Use the staff_id from the cart item. If it's a new item added by cashier, it will be set. If it's an old item from waiter, it's preserved.
            $staff_id = isset($item['staff_id']) ? intval($item['staff_id']) : 0;
            $stmt_detail->bind_param("iiidsssii", $orderId, $pid, $qty, $price, $today, $currentTime, $kot_num, $details_status, $staff_id);
            $stmt_detail->execute();

            // Reduce stock if status is Processing (1) or Completed/Paid (2)
            if ($details_status >= 1) {
                $stmt_stock = $con->prepare("UPDATE tblproducts SET Quantity = Quantity - ? WHERE ID = ? AND Type = 'Countable'");
                $stmt_stock->bind_param("ii", $qty, $pid);
                $stmt_stock->execute();
                $stmt_stock->close();
            }

            $inserted_ids[] = $stmt_detail->insert_id;
        }
        $stmt_detail->close();

        finalize:
        mysqli_commit($con);
        echo json_encode(['status' => 'success', 'order_id' => $orderId, 'kot_num' => $kot_num, 'ids' => $inserted_ids]);

    } catch (Exception $e) {
        mysqli_rollback($con);
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    
    $con->close();
}
?>