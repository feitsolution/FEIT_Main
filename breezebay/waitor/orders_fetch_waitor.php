<?php
// Fetch pending orders for the SweetAlert
$pending_order_buttons = '';
$sql_orders = "SELECT o.ID, o.TableID, t.TableName, o.TotalAmount 
               FROM tblorder o 
               LEFT JOIN tbltables t ON o.TableID = t.ID 
               WHERE o.Status = 'Pending' AND o.TableID > 0 ORDER BY o.ID DESC";
$result_orders = mysqli_query($con, $sql_orders);
if ($result_orders && mysqli_num_rows($result_orders) > 0) {
    while ($row = mysqli_fetch_assoc($result_orders)) {
        $pending_order_buttons .= '<a href="../waitor/waitor_cart.php?order_id=' . $row['ID'] . '&table_id=' . $row['TableID'] . '" class="btn btn-warning m-2 p-3 text-dark" style="min-width: 150px;"><strong>Order #' . $row['ID'] . '</strong><br>' . htmlspecialchars($row['TableName']) . '<br><small>Total: ' . number_format($row['TotalAmount'], 2) . '</small></a>';
    }
} else {
    $pending_order_buttons = '<p class="text-muted">No pending orders found.</p>';
}
?>