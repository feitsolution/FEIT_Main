<?php
session_start();
include('../include/dbconnection.php');

if (empty($_SESSION['uid'])) {
    die("Access Denied");
}

$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

if ($order_id == 0) {
    die("Invalid Order ID");
}

// Fetch Order Info
$order_query = mysqli_query($con, "SELECT o.*, t.TableName FROM tblorder o LEFT JOIN tbltables t ON o.TableID = t.ID WHERE o.ID = $order_id");
$order = mysqli_fetch_assoc($order_query);

if (!$order) {
    die("Order not found");
}

// Fetch Items
$items_query = mysqli_query($con, "SELECT SUM(d.Qty) as Qty, d.Price, p.ProductName FROM tblorder_details d LEFT JOIN tblproducts p ON d.ProductID = p.ID WHERE d.OrderID = $order_id GROUP BY d.ProductID, d.Price");

// Fetch Branding
$branding_query = mysqli_query($con, "SELECT company_name, logo, phone_no FROM tblbranding LIMIT 1");
$branding = mysqli_fetch_assoc($branding_query);
$company_name = isset($branding['company_name']) ? $branding['company_name'] : 'RMS';
$logo = isset($branding['logo']) ? $branding['logo'] : '';
$phone_no = isset($branding['phone_no']) ? $branding['phone_no'] : '';
?>
<!DOCTYPE html>
<html>
<head>
    <title>Receipt #<?php echo $order_id; ?></title>
    <style>
        @page { size: 80mm 297mm; margin: 0; }
        body { font-family: 'Courier New', Courier, monospace; width: 72mm; margin: 0 auto; padding: 5px; font-size: 12px; background: #fff; color: #000; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-weight-bold { font-weight: bold; }
        .mb-1 { margin-bottom: 5px; }
        .border-bottom { border-bottom: 1px dashed #000; }
        .border-top { border-top: 1px dashed #000; }
        .items-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .items-table th { text-align: left; border-bottom: 1px solid #000; font-size: 12px; padding: 2px 5px; }
        .items-table td { padding: 2px 5px; vertical-align: top; }
        .qty-col { width: 25px; text-align: center; }
        .price-col { width: 50px; text-align: right; }
        .footer { margin-top: 20px; font-size: 11px; text-align: center; }
    </style>
</head>
<body onload="window.focus(); window.print(); setTimeout(window.close, 1000);">
    <div class="text-center mb-1">
        <?php if(!empty($logo) && file_exists("../branding/uploads/" . $logo)): ?>
            <img src="../branding/uploads/<?php echo $logo; ?>" style="max-width: 80px; margin-bottom: 5px;">
        <?php endif; ?>
        <h3 style="margin:0; font-size: 18px;"><?php echo htmlspecialchars($company_name); ?></h3>
        <?php if(!empty($phone_no)): ?>
            <p style="margin:0; font-size: 12px;"><?php echo htmlspecialchars($phone_no); ?></p>
        <?php endif; ?>
        <p style="margin:0; font-size: 10px;">SALES RECEIPT</p>
    </div>
    
    <div class="border-bottom mb-1"></div>
    
    <div style="display:flex; justify-content:space-between; font-size: 11px;">
        <span>Bill No: <?php echo $order_id; ?></span>
        <span>
            <?php echo date('d/m/Y', strtotime($order['OrderDate'])); ?> 
            <?php echo !empty($order['Time']) ? date('h:i A', strtotime($order['Time'])) : date('h:i A', strtotime($order['OrderDate'])); ?>
        </span>
    </div>
    <div style="display:flex; justify-content:space-between; font-size: 11px;">
        <span>
            <?php if($order['TableID'] > 0): ?>
                Table: <?php echo htmlspecialchars($order['TableName']); ?>
            <?php else: ?>
                Order Type: Take Away
            <?php endif; ?>
        </span>
        <span>
            <?php 
            $pm = $order['PaymentMethod'];
            if($pm === '0') $pm = 'Cash'; elseif($pm === '1') $pm = 'Card'; elseif($pm === '2') $pm = 'Online';
            echo 'Pay: ' . htmlspecialchars($pm);
            ?>
        </span>
    </div>

    <table class="items-table">
        <thead>
            <tr>
                <th>Item</th>
                <th class="qty-col">Qty</th>
                <th class="price-col">Total</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $subtotal = 0;
            while ($item = mysqli_fetch_assoc($items_query)): 
                $itemTotal = $item['Qty'] * $item['Price'];
                $subtotal += $itemTotal;
            ?>
            <tr>
                <td><?php echo htmlspecialchars($item['ProductName']); ?></td>
                <td class="qty-col"><?php echo $item['Qty']; ?></td>
                <td class="price-col"><?php echo number_format($itemTotal, 2); ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <div class="border-top" style="margin-top: 10px; padding-top: 5px;">
        <div style="display:flex; justify-content:space-between; font-size: 12px;">
            <span>Subtotal</span>
            <span><?php echo number_format($subtotal, 2); ?></span>
        </div>
        <div style="display:flex; justify-content:space-between; font-size: 12px;">
            <span>SC</span>
            <span><?php echo number_format($order['ServiceCharge'], 2); ?></span>
        </div>
        <?php if($order['Discount'] > 0): 
            $discountPercentage = ($subtotal > 0) ? round(($order['Discount'] / $subtotal) * 100) : 0;
        ?>
        <div style="display:flex; justify-content:space-between; font-size: 12px;">
            <span>Discount (<?php echo $discountPercentage; ?>%)</span>
            <span><?php echo number_format($order['Discount'], 2); ?></span>
        </div>
        <?php endif; ?>
        <div style="display:flex; justify-content:space-between; font-weight:bold; font-size: 14px;">
            <span>TOTAL</span>
            <span><?php echo number_format($order['TotalAmount'], 2); ?></span>
        </div>
    </div>

    <div class="footer">
        <p>Thank you for your visit!</p>
    </div>
</body>
</html>