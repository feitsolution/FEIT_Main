<?php
session_start();
include('../include/dbconnection.php');

if (empty($_SESSION['uid']) || !isset($_GET['order_id']) || !isset($_GET['ids'])) {
    die("Access Denied");
}

$order_id = intval($_GET['order_id']);
$ids_str = $_GET['ids'];
$kot_num = isset($_GET['kot_num']) ? $_GET['kot_num'] : '';

// Validate IDs are integers to prevent SQL Injection
$ids_array = explode(',', $ids_str);
$safe_ids = [];
foreach($ids_array as $id) {
    $safe_ids[] = intval($id);
}
$ids_query_part = implode(',', $safe_ids);

if(empty($safe_ids)) die("No items to print");

// Fetch Order Info
$order_query = mysqli_query($con, "SELECT o.ID, t.TableName, o.OrderDate, o.OrderType FROM tblorder o LEFT JOIN tbltables t ON o.TableID = t.ID WHERE o.ID = $order_id");
$order = mysqli_fetch_assoc($order_query);

// Fetch Items
$items_query = mysqli_query($con, "SELECT d.Qty, d.Price, p.ProductName, s.StaffName FROM tblorder_details d LEFT JOIN tblproducts p ON d.ProductID = p.ID LEFT JOIN tblstaff s ON d.staff_id = s.ID WHERE d.ID IN ($ids_query_part)");

$order_items = [];
$waiter_name = 'Staff';

if ($items_query) {
    while ($row = mysqli_fetch_assoc($items_query)) {
        $order_items[] = $row;
        if (!empty($row['StaffName'])) $waiter_name = $row['StaffName'];
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>KOT #<?php echo $order_id; ?></title>
    <style>
        @page { size: 80mm 297mm; margin: 0; }
        body { font-family: 'Courier New', Courier, monospace; width: 72mm; margin: 0 auto; padding: 0 5px 5px 5px; font-size: 14px; background: #fff; }
        .header { text-align: center; margin-bottom: 10px; }
        .header h3 { margin: 0; font-size: 20px; font-weight: bold; border-bottom: 2px solid #000; display: inline-block; padding-bottom: 2px; }
        .meta { display: flex; justify-content: space-between; margin-bottom: 5px; font-size: 13px; border-bottom: 1px dashed #000; padding-bottom: 5px; }
        .items-table { width: 100%; border-collapse: collapse; }
        .items-table th { text-align: left; border-bottom: 1px solid #000; font-size: 13px; padding: 2px 0; }
        .items-table td { padding: 5px 0; vertical-align: top; font-weight: bold; }
        .qty-col { width: 40px; text-align: center; font-size: 16px; }
        .price-col { width: 60px; text-align: right; font-size: 14px; }
        .footer { border-top: 1px dashed #000; margin-top: 15px; padding-top: 5px; text-align: center; font-size: 12px; }
    </style>
</head>
<body onload="window.print();">
    <div class="header">
        <h3>KITCHEN ORDER TICKET (#<?php echo htmlspecialchars($kot_num); ?>)</h3>
    </div>
    <div class="meta">
        <div style="text-align:left;">
            ORD: #<?php echo $order_id; ?><br>
            <?php if ($order['OrderType'] == 'Take-away'): ?>
                Take Away
            <?php else: ?>
                TBL: <?php echo htmlspecialchars($order['TableName']); ?>
            <?php endif; ?>
        </div>
        <div style="text-align:right;">
            <?php echo date('d/m/Y'); ?><br>
            <?php echo date('H:i'); ?>
        </div>
    </div>
    <table class="items-table">
        <thead><tr><th>ITEM</th><th class="qty-col">QTY</th><th class="price-col">PRICE</th></tr></thead>
        <tbody>
        <?php foreach ($order_items as $item): ?>
            <tr>
                <td><?php echo htmlspecialchars($item['ProductName']); ?></td>
                <td class="qty-col"><?php echo $item['Qty']; ?></td>
                <td class="price-col"><?php echo number_format($item['Price'], 2); ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <div class="footer">
        <p>Waitor: <?php echo htmlspecialchars($waiter_name); ?></p>
    </div>
</body>
</html>