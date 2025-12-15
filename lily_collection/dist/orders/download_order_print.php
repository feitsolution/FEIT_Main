<?php
// Start session at the very beginning
session_start();

// Check if user is logged in, if not redirect to login page
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    if (ob_get_level()) {
        ob_end_clean();
    }
    header("Location: /lily_collection/dist/pages/login.php");
    exit();
}

// Include the database connection filea
include($_SERVER['DOCUMENT_ROOT'] . '/lily_collection/dist/connection/db_connection.php');


// =============================================================
//  BRANDING DATA - GET FROM DB
// =============================================================
$branding_sql = "SELECT * FROM branding WHERE active = 1 LIMIT 1";
$branding_result = $conn->query($branding_sql);
$branding = $branding_result->fetch_assoc();

// Branding variables with safe fallbacks
$company_name = !empty($branding['company_name']) ? $branding['company_name'] : "";
$company_address = !empty($branding['address']) ? $branding['address'] : "";
$company_email = !empty($branding['email']) ? $branding['email'] : "";
$company_hotline = !empty($branding['hotline']) ? $branding['hotline'] : "";
$company_logo = "/lily_collection/dist/assets/images/lily.jpeg";


// =============================================================
//  ORDER VALIDATION
// =============================================================
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("Order ID is required");
}

$order_id = $_GET['id'];


// =============================================================
//  ORDER HEADER QUERY
// =============================================================
// ✅ ADDED: pay_status, pay_by, pay_date fields to query
$order_query = "SELECT o.*, c.name as customer_name, c.phone as customer_phone, 
                c.phone_2 as customer_phone_2,
                c.email as customer_email, c.city_id,
                CONCAT_WS(', ', c.address_line1, c.address_line2) as customer_address,
                o.delivery_fee, o.discount, o.total_amount, o.issue_date, o.tracking_number,
                o.pay_status, o.pay_by, o.pay_date,
                cr.courier_name as delivery_service,
                ct.city_name,

                COALESCE(NULLIF(o.full_name, ''), c.name, 'Unknown Customer') as display_name,
                COALESCE(NULLIF(o.mobile, ''), c.phone, 'No phone') as display_mobile,
                c.phone_2 as display_mobile_2,

                COALESCE(
                    NULLIF(CONCAT_WS(', ', NULLIF(o.address_line1, ''), NULLIF(o.address_line2, '')), ''),
                    NULLIF(CONCAT_WS(', ', 
                        NULLIF(c.address_line1, ''), 
                        NULLIF(c.address_line2, ''), 
                        ct.city_name
                    ), ''),
                    'Address not available'
                ) as display_address
                
                FROM order_header o 
                LEFT JOIN customers c ON o.customer_id = c.customer_id
                LEFT JOIN couriers cr ON o.courier_id = cr.courier_id
                LEFT JOIN city_table ct ON c.city_id = ct.city_id AND ct.is_active = 1
                WHERE o.order_id = ?";

$stmt = $conn->prepare($order_query);
$stmt->bind_param("i", $order_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Order not found");
}


$order = $result->fetch_assoc();


// =============================================================
//  ORDER ITEMS QUERY
// =============================================================
$items_query = "SELECT oi.product_id, p.name as product_name, 
                SUM(oi.quantity) as total_quantity
                FROM order_items oi
                JOIN products p ON oi.product_id = p.id
                WHERE oi.order_id = ?
                GROUP BY oi.product_id, p.name
                ORDER BY p.name";

$stmt_items = $conn->prepare($items_query);
$stmt_items->bind_param("i", $order_id);
$stmt_items->execute();
$items_result = $stmt_items->get_result();

$items = [];
while ($item = $items_result->fetch_assoc()) {
    $items[] = $item;
}


// =============================================================
//  TOTALS + CALCULATIONS
// =============================================================
$currency = isset($order['currency']) ? strtolower($order['currency']) : 'lkr';
$currencySymbol = ($currency == 'usd') ? '$' : 'Rs.';

$subtotal = floatval($order['total_amount']) - floatval($order['delivery_fee']) + floatval($order['discount']);
$delivery_fee = floatval($order['delivery_fee']);
$discount = floatval($order['discount']);
$total_payable = floatval($order['total_amount']);

$tracking_number = !empty($order['tracking_number']) ? $order['tracking_number'] : '';
$has_tracking = !empty($tracking_number);

// ✅ ADDED: Payment status check
$is_paid = !empty($order['pay_status']) && $order['pay_status'] === 'paid';

function getBarcodeUrl($data) {
    return "https://barcodeapi.org/api/code128/{$data}";
}

function getQRCodeUrl($data) {
    return "https://api.qrserver.com/v1/create-qr-code/?size=80x80&data=" . urlencode($data);
}

$barcode_url = $has_tracking ? getBarcodeUrl($tracking_number) : '';
$qr_url = $has_tracking ? getQRCodeUrl("Tracking: " . $tracking_number . " | Order: " . $order_id) : '';


$totalPayable = 0;

if (isset($order['pay_status']) && $order['pay_status'] !== 'paid') {
    $totalPayable = $total_payable; // original amount
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Print - <?php echo $order_id; ?></title>
    <link rel="stylesheet" href="../assets/css/print.css" id="main-style-link" />
    <style>
        /* Additional styling for payment badge */
        .payment-badge {
            display: inline-block;
            color: #155724;
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            padding: 2px 8px;
            border-radius: 3px;
            font-size: 9px;
            font-weight: bold;
            margin-top: 2mm;
        }
    </style>
</head>

<body>
    
    <div class="receipt-container">

        <table class="main-table">

            <!-- HEADER SECTION WITH BRANDING -->
            <tr>
                <td class="header-section" colspan="2">

                    <div class="company-logo">
                      <img src="<?php echo htmlspecialchars($company_logo); ?>" alt="Company Logo">
                    </div>
                    

                    <div class="company-name">
                        <?php echo htmlspecialchars($company_name); ?>
                    </div>

                    <div class="company-info">
                        Address: <?php echo htmlspecialchars($company_address); ?>
                    </div>

                    <div class="company-info">
                        Hotline: <?php echo htmlspecialchars($company_hotline); ?>
                        <?php if (!empty($company_email)): ?>
                            | Email: <?php echo htmlspecialchars($company_email); ?>
                        <?php endif; ?>
                    </div>

                </td>

                <!-- ORDER ID + BARCODE -->
                <td class="order-id-cell">

                    <div style="font-weight:bold; ">
                        Order ID: <?php echo str_pad($order_id, 5, '0', STR_PAD_LEFT); ?>
                    </div>

                    <?php if ($is_paid): ?>
                        <div class="payment-badge">
                            ✔ PAID
                        </div>
                    <?php endif; ?>

                    <?php if ($has_tracking): ?>
                        <div class="barcode-section" style="margin-top:2mm;">
                            <img src="<?php echo $barcode_url; ?>" 
                                 alt="Tracking Barcode" 
                                 class="barcode-image"
                                 onerror="this.style.display='none'">
                        </div>
                    <?php else: ?>
                        <div style="color:#dc2626; font-weight:bold; margin-top:2mm;">No Tracking Assigned</div>
                        <div style="border:2px dashed #dc2626; padding:8px; text-align:center; margin-top:2mm;">
                            NO BARCODE<br><span style="font-size:8px;">Tracking not available</span>
                        </div>
                    <?php endif; ?>

                </td>
            </tr>


            <!-- DELIVERY SERVICE -->
            <tr>
                <td class="delivery-service-cell">
                    <strong>Delivery Service:</strong><br>
                    <?php echo htmlspecialchars($order['delivery_service']); ?>
                </td>

                <td class="tracking-cell" colspan="2">
                    <strong>Tracking:</strong> 
                    <?php echo $has_tracking ? htmlspecialchars($tracking_number) : "<span style='color:#dc2626;'>No Tracking Assigned</span>"; ?>
                    <br>
                    <strong>Date:</strong> 
                    <?php echo !empty($order['issue_date']) ? date('Y-m-d', strtotime($order['issue_date'])) : date('Y-m-d'); ?>
                </td>
            </tr>


            <!-- PRODUCT LIST -->
            <tr>
                <td class="product-header" colspan="3">
                    <strong>Products :</strong>
                    <div style="margin-top:1mm; font-size:9px;">
                        <?php 
                        $product_list = [];
                        foreach ($items as $item) {
                            $pname = substr($item['product_name'], 0, 25);
                            if (strlen($item['product_name']) > 25) $pname .= "...";
                            $product_list[] = $item['product_id']." - ".$pname." (".$item['total_quantity'].")";
                        }
                        echo implode(", ", $product_list);
                        ?>
                    </div>
                </td>
            </tr>


            <!-- CUSTOMER + TOTALS -->
            <tr>
                <td class="customer-header">Customer Details</td>
                <td class="totals-header">Summary</td>
                <td class="totals-header">Amount</td>
            </tr>

            <tr>
                <td class="customer-info">
                    <strong>Name:</strong> <?php echo htmlspecialchars(substr($order['display_name'], 0, 20)); ?><br>
                    <strong>Phone 1:</strong> <?php echo htmlspecialchars($order['display_mobile']); ?><br>
                    <?php if (!empty($order['display_mobile_2'])): ?>
                        <strong>Phone 2:</strong> <?php echo htmlspecialchars($order['display_mobile_2']); ?><br>
                    <?php endif; ?>
                    <strong>Address:</strong> 
                    <?php 
                        $addr = $order['display_address'];
                        echo htmlspecialchars(substr($addr, 0, 60)) . (strlen($addr) > 60 ? "..." : "");
                    ?>
                </td>

                <td class="totals-cell">
                    Subtotal:<br>Delivery:<br>Discount:
                </td>

                <td class="totals-cell amount">
                    <?php echo $currencySymbol . " " . number_format($subtotal, 2); ?><br>
                    <?php echo $currencySymbol . " " . number_format($delivery_fee, 2); ?><br>
                    <?php echo $currencySymbol . " " . number_format($discount, 2); ?>
                </td>
            </tr>


            <!-- TOTAL PAYABLE -->
         <tr>
    <td class="total-payable" colspan="2">TOTAL PAYABLE</td>
    <td class="total-payable amount">
        <?php echo $currencySymbol . " " . number_format($totalPayable, 2); ?>
    </td>
</tr>


        </table>

    </div>

    <script>
        window.onload = function() {
            window.print();
        }
    </script>

</body>
</html>

<?php $conn->close(); ?>