<?php
session_start();
include('../include/dbconnection.php');

if (empty($_SESSION['uid'])) {
    header('location:../auth/login.php');
    exit;
}

$from = isset($_GET['from']) ? $_GET['from'] : '';
$to = isset($_GET['to']) ? $_GET['to'] : '';

// Fetch branding for the header
$branding_sql = "SELECT company_name FROM tblbranding LIMIT 1";
$branding_query = mysqli_query($con, $branding_sql);
$branding = mysqli_fetch_assoc($branding_query);
$company_name = $branding['company_name'] ?? 'Restaurant Management System';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Print Income Report - <?php echo htmlspecialchars($company_name); ?></title>
    <style>
        body { font-family: sans-serif; color: #333; line-height: 1.5; padding: 20px; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #333; padding-bottom: 10px; }
        .header h1 { margin: 0; font-size: 24px; }
        .header h2 { margin: 5px 0; font-size: 18px; color: #666; }
        .info { margin-bottom: 20px; font-size: 14px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; font-size: 12px; }
        th { background-color: #f4f4f4; font-weight: bold; }
        .total-section { text-align: right; font-size: 16px; font-weight: bold; margin-top: 20px; }
        @media print {
            .no-print { display: none; }
            body { padding: 0; }
        }
    </style>
</head>
<body onload="window.print()">

    <div class="header">
        <h1><?php echo htmlspecialchars($company_name); ?></h1>
        <h2>Income Report</h2>
    </div>

    <div class="info">
        <strong>Date Range:</strong> <?php echo ($from ? htmlspecialchars($from) : 'All Time') . ' to ' . ($to ? htmlspecialchars($to) : 'All Time'); ?>
        <br>
        <strong>Generated On:</strong> <?php echo date('Y-m-d H:i:s'); ?>
    </div>

    <table>
        <thead>
            <tr>
                <th>Order No</th>
                <th>Type / Table</th>
                <th>Date</th>
                <th>Close Time</th>
                <th>Order Items</th>
                <th>Service Chg</th>
                <th>Discount</th>
                <th>Total Price</th>
                <th>Status</th>
                <th>Payment</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $where = "WHERE 1=1";
            if (!empty($from)) {
                $where .= " AND o.OrderDate >= '" . mysqli_real_escape_string($con, $from) . "'";
            }
            if (!empty($to)) {
                $where .= " AND o.OrderDate <= '" . mysqli_real_escape_string($con, $to) . "'";
            }

            $query = "SELECT o.*, t.TableName,
                      (SELECT GROUP_CONCAT(CONCAT(COALESCE(od.ProductName, p.ProductName), ' (x', od.Qty, ')') SEPARATOR ', ') 
                       FROM tblorder_details od 
                       LEFT JOIN tblproducts p ON od.ProductID = p.ID 
                       WHERE od.OrderID = o.ID) as OrderItems 
                      FROM tblorder o 
                      LEFT JOIN tbltables t ON o.TableID = t.ID
                      $where
                      ORDER BY o.OrderDate DESC, o.ID DESC";
            
            $ret = mysqli_query($con, $query);
            $totalOrders = 0;
            $totalServiceCharge = 0;
            $totalDiscount = 0;
            $grandTotal = 0;
            while ($row = mysqli_fetch_array($ret)) {
                $totalOrders++;
                $totalServiceCharge += $row['ServiceCharge'];
                $totalDiscount += $row['Discount'];
                $grandTotal += $row['TotalAmount'];
            ?>
                <tr>
                    <td><?php echo $row['ID']; ?></td>
                    <td><?php echo htmlspecialchars($row['OrderType']) . ($row['TableID'] > 0 ? ' - ' . htmlspecialchars($row['TableName']) : ''); ?></td>
                    <td><?php echo date('Y-m-d', strtotime($row['OrderDate'])); ?></td>
                    <td><?php echo htmlspecialchars($row['Time']); ?></td>
                    <td><?php echo htmlspecialchars($row['OrderItems']); ?></td>
                    <td><?php echo number_format($row['ServiceCharge'], 2); ?></td>
                    <td><?php echo number_format($row['Discount'], 2); ?></td>
                    <td><?php echo number_format($row['TotalAmount'], 2); ?></td>
                    <td><?php echo htmlspecialchars($row['Status']); ?></td>
                    <td><?php echo ($row['PaymentMethod'] == '0' ? 'Cash' : ($row['PaymentMethod'] == '1' ? 'Card' : ($row['PaymentMethod'] == '2' ? 'Online' : htmlspecialchars($row['PaymentMethod'])))); ?></td>
                </tr>
            <?php } ?>
        </tbody>
    </table>

    <div class="total-section">
        <div>Total Orders: <?php echo $totalOrders; ?></div>
        <div>Total Service Charge: <?php echo number_format($totalServiceCharge, 2); ?></div>
        <div>Total Discount: <?php echo number_format($totalDiscount, 2); ?></div>
        <div style="font-size: 20px; margin-top: 10px;">Total Income: <?php echo number_format($grandTotal, 2); ?></div>
    </div>

    <div class="no-print" style="margin-top: 20px; text-align: center;">
        <button onclick="window.close()">Close Window</button>
    </div>

</body>
</html>