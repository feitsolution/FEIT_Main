<?php
// Start session at the very beginning
session_start();

// Check if user is logged in, if not redirect to login page
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: signin.php");
    exit();
}

// Include database connection and functions
include 'db_connection.php';
include 'functions.php';

// Check if invoice ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("Invoice ID is required");
}

$invoice_id = $_GET['id'];
$show_payment_details = isset($_GET['show_payment']) && $_GET['show_payment'] === 'true';
$format = isset($_GET['format']) ? $_GET['format'] : 'view'; // 'view' or 'html' (for modal)
$download = isset($_GET['download']) && $_GET['download'] === 'true'; // Trigger actual download

// Fetch invoice details from database with individual item discounts
$invoice_query = "SELECT i.*, i.pay_status AS invoice_pay_status, c.name as customer_name, 
                c.address as customer_address, c.email as customer_email, c.phone as customer_phone,
                p.payment_id, p.amount_paid, p.payment_method, p.payment_date, p.pay_by,
                r.name as paid_by_name, u.name as user_name
                FROM invoices i 
                LEFT JOIN customers c ON i.customer_id = c.customer_id
                LEFT JOIN payments p ON i.invoice_id = p.invoice_id
                LEFT JOIN roles r ON p.pay_by = r.id
                LEFT JOIN users u ON i.user_id = u.id
                WHERE i.invoice_id = ?";

$stmt = $conn->prepare($invoice_query);
$stmt->bind_param("i", $invoice_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Invoice not found");
}

$invoice = $result->fetch_assoc();

// Get currency from invoice
$currency = isset($invoice['currency']) ? strtolower($invoice['currency']) : 'usd';
$currencySymbol = ($currency == 'usd') ? '$' : 'Rs.';

// Modified item query to include item-level discounts
$itemSql = "SELECT ii.*, ii.pay_status, p.name as product_name, 
            COALESCE(ii.description, p.description) as product_description,
            ii.total_amount as item_price,
            COALESCE(ii.discount, 0) as item_discount
            FROM invoice_items ii
            JOIN products p ON ii.product_id = p.id
            WHERE ii.invoice_id = ?";

$stmt = $conn->prepare($itemSql);
$stmt->bind_param("i", $invoice_id);
$stmt->execute();
$itemsResult = $stmt->get_result();
$items = [];

while ($item = $itemsResult->fetch_assoc()) {
    $items[] = $item;
}

// Determine overall invoice payment status
if (isset($invoice['invoice_pay_status']) && !empty($invoice['invoice_pay_status'])) {
    $invoicePayStatus = strtolower($invoice['invoice_pay_status']);
} else {
    $allItemsPaid = true;
    $anyItemPaid = false;

    foreach ($items as $item) {
        if (strtolower($item['pay_status']) == 'paid') {
            $anyItemPaid = true;
        } else {
            $allItemsPaid = false;
        }
    }

    if ($allItemsPaid && count($items) > 0) {
        $invoicePayStatus = 'paid';
    } elseif ($anyItemPaid) {
        $invoicePayStatus = 'partial';
    } else {
        $invoicePayStatus = 'unpaid';
    }
}

// Company information
$company = [
    'name' => 'FE IT Solutions pvt (Ltd)',
    'address' => 'No: 04, Wijayamangalarama Road, Kohuwala',
    'email' => 'info@feitsolutions.com',
    'phone' => '011-2824524'
];

// Function to get badge class for payment status
function getPaymentStatusBadge($status)
{
    $status = strtolower($status ?? 'unpaid');
    switch ($status) {
        case 'paid':
            return "bg-success";
        case 'partial':
            return "bg-warning";
        case 'unpaid':
        default:
            return "bg-danger";
    }
}

// Calculate total item-level discounts
$total_item_discounts = 0;
foreach ($items as $item) {
    $total_item_discounts += floatval($item['item_discount']);
}

// Check if there are any discounts at all
$has_any_discount = $total_item_discounts > 0 || floatval($invoice['discount']) > 0;
$column_count = $has_any_discount ? 5 : 4;

// Calculate total before discounts
$total_before_discounts = 0;
foreach ($items as $item) {
    $total_before_discounts += $item['item_price'] ?? 0;
}

// Determine if we should show buttons (only when format is 'view')
$showButtons = ($format === 'view');
$isModalView = ($format === 'html');
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Invoice #<?php echo $invoice_id; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet" />

    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f6f9;
            font-size: 14px;
            color: #333;
        }

        .invoice-container {
            max-width: 100%;
            margin: 0 auto;
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .company-logo {
            display: flex;
            flex-direction: column;
        }

        .company-logo img {
            max-width: 150px;
            margin-bottom: 10px;
        }

        .company-details {
            margin-top: 10px;
            line-height: 1.5;
        }

        .invoice-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
            border-bottom: 1px solid #ddd;
            padding-bottom: 15px;
        }

        .invoice-info {
            text-align: right;
        }

        .invoice-title {
            color: #333;
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .invoice-date {
            margin-top: 5px;
        }

        .billing-details {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
        }

        .billing-block {
            flex: 0 0 48%;
        }

        .billing-title {
            font-weight: bold;
            margin-bottom: 8px;
            color: #555;
        }

        .billing-info {
            line-height: 1.5;
        }

        .product-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 0;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .product-table thead tr {
            background: linear-gradient(to right, #4CAF50, #17a2b8);
        }

        .product-table th {
            color: white;
            text-align: left;
            padding: 10px;
            border: 1px solid #ddd;
            background: transparent;
        }

        .product-table td {
            border: 1px solid #ddd;
            padding: 8px 10px;
        }

        .product-table tbody tr:nth-of-type(odd) {
            background-color: #f9f9f9;
        }

        .product-table tbody tr:hover {
            background-color: #e9ecef;
        }

        .product-table .total-row td {
            font-weight: bold;
            text-align: right;
            padding: 5px 10px;
        }

        .product-table .total-value {
            text-align: right;
        }

        .notes {
            margin-top: 30px;
            border: 1px solid #ddd;
            padding: 15px;
            background-color: #fff;
        }

        .note-title {
            font-weight: bold;
            margin-bottom: 5px;
        }

        .payment-info {
            margin-top: 40px;
            padding-top: 20px;
            display: flex;
            justify-content: space-between;
        }

        .payment-methods h5 {
            font-size: 16px;
            margin-bottom: 10px;
        }

        .signature-line {
            width: 200px;
            border-top: 1px solid #000;
            margin-top: 50px;
            text-align: center;
            padding-top: 5px;
        }

        .control-buttons {
            margin: 20px 0;
            text-align: center;
        }

        .control-buttons button {
            margin: 0 5px;
            padding: 8px 15px;
            cursor: pointer;
        }

        .pay-status {
            font-weight: bold;
            text-align: right;
            margin-top: 5px;
        }

        .payment-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            color: white;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 12px;
        }

        .bg-success {
            background-color: #28a745;
        }

        .bg-warning {
            background-color: #fd7e14;
        }

        .bg-danger {
            background-color: #dc3545;
        }

        @media print {
            * {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                color-adjust: exact !important;
            }

            .product-table thead tr {
                background: linear-gradient(to right, #4CAF50, #17a2b8) !important;
            }

            .product-table th {
                color: white !important;
                background: transparent !important;
            }

            body {
                background-color: white;
                padding: 0;
            }

            .invoice-container {
                box-shadow: none;
                padding: 0;
            }

            .control-buttons,
            .alert {
                display: none !important;
            }
        }
    </style>
</head>

<body>
    <div class="invoice-container">
        <?php if ($showButtons): ?>
            <div class="control-buttons">
                <button class="btn btn-primary" onclick="downloadPDF()">
                    <i class="fas fa-download"></i> Download PDF
                </button>
                <?php if ($show_payment_details && $invoicePayStatus != 'paid'): ?>
                    <button id="markAsPaidBtn" class="btn btn-success">Mark as Paid</button>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="invoice-header">
            <div class="company-logo">
                <img src="img/system/fe_it_logo.png" alt="FE IT Solutions Logo">
            </div>
            <div class="invoice-info">
                <div class="invoice-title">INVOICE : # <?php echo $invoice_id; ?></div>
                <div class="invoice-date">Date Issued: <?php echo date('Y-m-d', strtotime($invoice['issue_date'])); ?>
                </div>
                <div>Due Date: <?php echo date('Y-m-d', strtotime($invoice['due_date'])); ?></div>
                <div>Created Time: <?php echo date('H:i:s', strtotime($invoice['created_at'])); ?></div>
                <div class="pay-status">
                    Pay Status:
                    <span class="payment-badge <?php echo getPaymentStatusBadge($invoicePayStatus); ?>">
                        <?php echo ucfirst($invoicePayStatus); ?>
                    </span>
                </div>
            </div>
        </div>

        <div class="billing-details">
            <div class="billing-block">
                <div class="billing-title">Billing From :</div>
                <div class="billing-info">
                    <div><?php echo htmlspecialchars($company['name']); ?></div>
                    <div>No: 04</div>
                    <div>Wijayamangalarama Road, Kohuwala</div>
                    <div><?php echo htmlspecialchars($company['email']); ?></div>
                    <div><?php echo htmlspecialchars($company['phone']); ?></div>
                </div>
            </div>
            <div class="billing-block">
                <div class="billing-title">Billing To :</div>
                <div class="billing-info">
                    <strong><?php echo htmlspecialchars($invoice['customer_name']); ?></strong><br>
                    <?php echo nl2br(htmlspecialchars($invoice['customer_address'])); ?><br>
                    Email: <?php echo htmlspecialchars($invoice['customer_email']); ?><br>
                    Phone: <?php echo htmlspecialchars($invoice['customer_phone']); ?>
                </div>
            </div>
        </div>

        <table class="product-table">
            <thead>
                <tr>
                    <th width="5%">#</th>
                    <th width="<?php echo $has_any_discount ? '35%' : '40%'; ?>">PRODUCT</th>
                    <th width="<?php echo $has_any_discount ? '30%' : '40%'; ?>">DESCRIPTION</th>
                    <?php if ($has_any_discount): ?>
                        <th width="15%" style="text-align: right;">DISCOUNT</th>
                    <?php endif; ?>
                    <th width="15%" style="text-align: right;">PRICE</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $i = 1;
                if (count($items) > 0):
                    foreach ($items as $item):
                        $item_price = $item['item_price'] ?? 0;
                        $item_discount = $item['item_discount'] ?? 0;
                ?>
                        <tr>
                            <td><?php echo $i++; ?></td>
                            <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                            <td><?php echo htmlspecialchars($item['product_description']); ?></td>
                            <?php if ($has_any_discount): ?>
                                <td style="text-align: right;">
                                    <?php echo $currencySymbol . ' ' . number_format($item_discount, 2); ?>
                                </td>
                            <?php endif; ?>
                            <td style="text-align: right;">
                                <?php echo $currencySymbol . ' ' . number_format($item_price, 2); ?>
                            </td>
                        </tr>
                    <?php endforeach;
                else: ?>
                    <tr>
                        <td colspan="<?php echo $column_count; ?>" style="text-align: center;">No items found for this invoice</td>
                    </tr>
                <?php endif; ?>

                <tr class="total-row">
                    <td colspan="<?php echo $column_count - 1; ?>" style="text-align: right; border-right: none;">Sub Total :</td>
                    <td class="total-value">
                        <?php echo $currencySymbol . ' ' . number_format($total_before_discounts, 2); ?>
                    </td>
                </tr>

                <?php if ($has_any_discount): ?>
                    <tr class="total-row">
                        <td colspan="<?php echo $column_count - 1; ?>" style="text-align: right; border-right: none;">Item Discounts :</td>
                        <td class="total-value">
                            <?php echo $currencySymbol . ' ' . number_format($total_item_discounts, 2); ?>
                        </td>
                    </tr>
                <?php endif; ?>

                <tr class="total-row">
                    <td colspan="<?php echo $column_count - 1; ?>" style="text-align: right; border-right: none;">Total :</td>
                    <td class="total-value">
                        <?php echo $currencySymbol . ' ' . number_format((float) $invoice['total_amount'], 2); ?>
                    </td>
                </tr>
            </tbody>
        </table>

        <div class="notes">
            <div class="note-title">Note:</div>
            <p><?php echo !empty($invoice['notes']) ? nl2br(htmlspecialchars($invoice['notes'])) : 'Once the invoice has been verified by the accounts payable team and recorded, the only task left is to send it for approval before releasing the payment'; ?>
            </p>
        </div>

        <?php if ($invoicePayStatus == 'paid' || $invoicePayStatus == 'partial'): ?>
            <div class="payment-info">
                <div class="payment-details">
                    <h3>Payment Information</h3>
                    <div>Payment Method: <?php echo htmlspecialchars($invoice['payment_method'] ?? 'N/A'); ?></div>
                    <div>Amount Paid:
                        <?php echo $currencySymbol . ' ' . number_format((float) ($invoice['amount_paid'] ?? 0), 2); ?>
                    </div>
                    <div>Payment Date:
                        <?php echo ($invoice['payment_date']) ? date('d/m/Y', strtotime($invoice['payment_date'])) : 'N/A'; ?>
                    </div>
                    <div>Processed By: <?php echo htmlspecialchars($invoice['paid_by_name'] ?? 'N/A'); ?></div>
                </div>
                <div class="signature">
                    <div class="signature-line">
                        Authorized Signature
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="payment-info">
                <div class="payment-methods">
                    <h5>Payment Methods</h5>
                    <p>
                        Account Name: F E IT SOLUTIONS PVT (LTD)<br>
                        Account Number: 100810008655<br>
                        Account Type: LKR Current Account<br>
                        Bank Name: Nations Trust Bank PLC
                    </p>
                </div>
                <div class="signature">
                    <div class="signature-line">
                        Authorized Signature
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    
    <script>
        function downloadPDF() {
            // Hide the buttons before generating PDF
            const buttons = document.querySelector('.control-buttons');
            if (buttons) {
                buttons.style.display = 'none';
            }

            const element = document.querySelector('.invoice-container');
            const opt = {
                margin: 0.5,
                filename: 'Invoice_<?php echo $invoice_id; ?>.pdf',
                image: { type: 'jpeg', quality: 0.98 },
                html2canvas: { 
                    scale: 2,
                    useCORS: true,
                    logging: false
                },
                jsPDF: { 
                    unit: 'in', 
                    format: 'a4', 
                    orientation: 'portrait' 
                }
            };

            // Generate PDF
            html2pdf().set(opt).from(element).save().then(function() {
                // Show buttons again after PDF is generated
                if (buttons) {
                    buttons.style.display = 'block';
                }
            });
        }

        // Handle Mark as Paid button click
        document.addEventListener('DOMContentLoaded', function () {
            const markAsPaidBtn = document.getElementById('markAsPaidBtn');
            if (markAsPaidBtn) {
                markAsPaidBtn.addEventListener('click', function () {
                    const formData = new FormData();
                    formData.append('invoice_id', '<?php echo $invoice_id; ?>');
                    formData.append('pay_status', 'paid');

                    fetch('update_invoice_status.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            window.location.reload();
                        } else {
                            alert('Error updating payment status: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred while updating the payment status.');
                    });
                });
            }
        });
    </script>
</body>

</html>
<?php
$conn->close();
?>