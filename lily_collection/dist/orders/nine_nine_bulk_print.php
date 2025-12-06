<?php
// -------------------------
// Basic Session + Auth
// -------------------------
if (!session_id()) {
    session_start();
}

if (!isset($_SESSION['logged_in']) && !isset($_SESSION['ClientUserID'])) {
    header("Location: /lily_collection/dist/pages/login.php");
    exit();
}

// DB connection
include($_SERVER['DOCUMENT_ROOT'] . '/lily_collection/dist/connection/db_connection.php');

// -------------------------
// Read Filters
// -------------------------
$date           = isset($_GET['date']) ? trim($_GET['date']) : date("Y-m-d");
$time_from      = trim($_GET['time_from'] ?? "");
$time_to        = trim($_GET['time_to'] ?? "");
$status         = trim($_GET['status_filter'] ?? "all");
$trackingFilter = trim($_GET['tracking_filter'] ?? "with_tracking");
$trackingNumber = trim($_GET['tracking_number'] ?? "");
$limit          = isset($_GET['limit']) ? (int)$_GET['limit'] : 500;

// sanitize
$date           = $conn->real_escape_string($date);
$time_from      = $conn->real_escape_string($time_from);
$time_to        = $conn->real_escape_string($time_to);
$status         = $conn->real_escape_string($status);
$trackingFilter = $conn->real_escape_string($trackingFilter);
$trackingNumber = $conn->real_escape_string($trackingNumber);

// -------------------------
// Build WHERE Conditions
// -------------------------
$where = [];
$where[] = "o.interface IN ('individual','leads')";

if ($date != "")        $where[] = "DATE(o.updated_at) = '$date'";
if ($time_from != "")   $where[] = "TIME(o.updated_at) >= '$time_from'";
if ($time_to != "")     $where[] = "TIME(o.updated_at) <= '$time_to'";

if ($status != "all")   $where[] = "o.status = '$status'";

if ($trackingFilter === "with_tracking") {
    $where[] = "o.tracking_number != ''";
} else if ($trackingFilter === "without_tracking") {
    $where[] = "(o.tracking_number = '' OR o.tracking_number IS NULL)";
} else if ($trackingFilter === "specific_tracking" && $trackingNumber !== "") {
    $where[] = "o.tracking_number LIKE '%$trackingNumber%'";
}

$whereClause = implode(" AND ", $where);

// -------------------------
// Final Query
// -------------------------
$sql = "
 SELECT  
    o.order_id,
    o.tracking_number,
    o.pay_status,
    o.pay_by,
    o.pay_date,
    COALESCE(NULLIF(o.full_name,''), NULLIF(c.name,''), 'Unknown Customer') AS name,
    COALESCE(NULLIF(o.mobile,''), NULLIF(c.phone,''), 'No phone') AS phone,
    NULLIF(o.address_line1,'') AS o_addr1,
    NULLIF(o.address_line2,'') AS o_addr2,
    NULLIF(c.address_line1,'') AS c_addr1,
    NULLIF(c.address_line2,'') AS c_addr2,
    ct.city_name,
    o.total_amount,
    o.currency,
    o.issue_date,
    cr.courier_name,
    IFNULL(GROUP_CONCAT(CONCAT(p.name,' (', oi.quantity, ')') SEPARATOR ', '),'') AS products
 FROM order_header o
 LEFT JOIN customers c ON o.customer_id = c.customer_id
 LEFT JOIN couriers cr ON o.courier_id = cr.courier_id
 LEFT JOIN city_table ct ON c.city_id = ct.city_id
 LEFT JOIN order_items oi ON oi.order_id = o.order_id
 LEFT JOIN products p ON p.id = oi.product_id
 WHERE $whereClause
 GROUP BY o.order_id
 ORDER BY o.updated_at DESC
 LIMIT $limit
";

$res = $conn->query($sql);
$orders = [];
if ($res) while ($row = $res->fetch_assoc()) $orders[] = $row;

// -------------------------
// Company Info from Branding Table
// -------------------------
$branding = $conn->query("
    SELECT 
        company_name, 
        web_name, 
        address, 
        hotline, 
        email, 
        logo_url 
    FROM branding 
    WHERE active = 1 
    ORDER BY branding_id DESC 
    LIMIT 1
");

if ($branding && $branding->num_rows > 0) {
    $brandingRow = $branding->fetch_assoc();
    $companyName = $brandingRow['company_name'];
    $companyLogo = $brandingRow['logo_url'];
    $billingAddress = $brandingRow['address'];
    $billingHotline = $brandingRow['hotline'];
    $billingEmail = $brandingRow['email'];
    $billingWebsite = $brandingRow['web_name'];
} else {
    // fallback
    $companyName = "FE IT Solutions Pvt (Ltd)";
    $companyLogo = "../assets/images/FEIT.png";
    $billingAddress = "N/A";
    $billingHotline = "-";
    $billingEmail = "-";
    $billingWebsite = "-";
}

// -------------------------
// Helper Functions
// -------------------------
function currencySymbol($c)
{
    return strtolower($c) === "usd" ? "$" : "Rs.";
}
function barcodeImg($d)
{
    return "https://barcodeapi.org/api/code128/" . urlencode($d);
}

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Bulk Print</title>

<style>
@page { margin: 0; }
body { margin:0; font-family: Arial; font-size:12px; }

/* NORMAL PAGE LAYOUT */
.label-box {
    width: 100mm;
    height: 110mm;
    padding: 8px;
    border: 1px solid #ccc;
    float: left;
    margin: 5px;
    box-sizing: border-box;
}
.small { font-size: 10px; color:#555; }

/* PRINT MODE: 1 ORDER PER PAGE */
@media print {
    .label-box {
        page-break-after: always !important;
        float: none !important;
        display: block !important;
    }
}
</style>
</head>

<body onload="window.print()">

<?php if (empty($orders)) { ?>
    <p style="padding:20px;">No orders found.</p>
<?php } ?>

<?php foreach ($orders as $o): ?>

<div class="label-box">

    <!-- Header -->
    <table width="100%">
        <tr>
            <td width="60">
                <img src="<?php echo $companyLogo; ?>" width="50">
            </td>
            <td style="text-align:right;">
                <b>Order: <?php echo $o['order_id']; ?></b><br>
                <span class="small"><?php echo $o['issue_date'] ?: date("Y-m-d"); ?></span><br>
                <span class="small"><?php echo $o['courier_name'] ?: "-"; ?></span>
            </td>
        </tr>
    </table>

    <!-- Billing From Details -->
    <div style="font-size:10px; margin-top:5px;">
        <?php echo $companyName; ?><br>
        <?php echo nl2br($billingAddress); ?><br>
        Hotline: <?php echo $billingHotline; ?><br>
        Email: <?php echo $billingEmail; ?><br>
        <!-- Website: <?php echo $billingWebsite; ?> -->
    </div>

    <hr>

    <!-- Customer -->
    <b><?php echo $o['name']; ?></b><br>
    Phone: <?php echo $o['phone']; ?><br>

    <?php
        $addr = $o['o_addr1'] ?: $o['c_addr1'];
        $addr2 = $o['o_addr2'] ?: $o['c_addr2'];
    ?>
    Address: <?php echo $addr . " " . $addr2; ?><br>
    City: <?php echo $o['city_name']; ?><br>

    <hr>

    <!-- Products -->
    <b>Products:</b><br>
    <span class="small"><?php echo $o['products']; ?></span>

    <hr>

    <b>Total: <?php echo currencySymbol($o['currency']) . " " . number_format($o['total_amount'], 2); ?></b><br>

    <?php if (!empty($o['pay_status']) && $o['pay_status'] === 'paid'): ?>
        <div style="color: green; font-weight: bold; margin-top: 4px;">
            ✔ ALREADY PAID
        </div>
    <?php endif; ?>

    <br><br>

    <!-- Barcode -->
    <?php if (!empty($o['tracking_number'])): ?>
        <img src="<?php echo barcodeImg($o['tracking_number']); ?>" style="width:150px;"><br>
    <?php else: ?>
        <div style="border:1px dashed red; padding:5px; text-align:center;">
            NO TRACKING
        </div>
    <?php endif; ?>

</div>

<?php endforeach; ?>

</body>
</html>

<?php $conn->close(); ?>
