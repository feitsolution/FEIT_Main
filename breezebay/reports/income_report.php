<?php
session_start();
include('../include/dbconnection.php');

if (empty($_SESSION['uid'])) {
    header('location:../auth/login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    
    <title>RMS - Income Report</title>
    <?php include('../include/header.php'); ?>
</head>

<body id="page-top">

    <!-- Page Wrapper -->
    <div id="wrapper">

        <?php include('../include/sidebar.php'); ?>

        <!-- Content Wrapper -->
        <div id="content-wrapper" class="d-flex flex-column">

            <!-- Main Content -->
            <div id="content">

                <?php include('../include/top-nav.php'); ?>

                <!-- Begin Page Content -->
                <div class="container-fluid">

                    <!-- Page Heading -->
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">Income Report</h1>
                        <a href="print_report.php" id="printReportBtn" target="_blank" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
                            <i class="fas fa-print fa-sm text-white-50"></i> Print Report
                        </a>
                    </div>

                    <div class="card shadow mb-4">                       
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover" id="dataTable" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>Order No</th>
                                            <th>Type / Table</th>
                                            <th>Date</th>
                                            <th>Order Close Time</th>                                           
                                            <th>Order Items</th>
                                            <th>Service Chg</th>
                                            <th>Discount</th>
                                            <th>Total Price</th>
                                            <th>Status</th>
                                            <th>Payment</th>
                                            <th>Bill</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        // Fetching all orders with full financial and descriptive details
                                        $query = "SELECT o.*, t.TableName,
                                                  (SELECT GROUP_CONCAT(CONCAT(COALESCE(od.ProductName, p.ProductName), ' (x', od.Qty, ')') SEPARATOR ', ') 
                                                   FROM tblorder_details od 
                                                   LEFT JOIN tblproducts p ON od.ProductID = p.ID 
                                                   WHERE od.OrderID = o.ID) as OrderItems 
                                                  FROM tblorder o 
                                                  LEFT JOIN tbltables t ON o.TableID = t.ID
                                                  ORDER BY o.OrderDate DESC, o.ID DESC";
                                        $ret = mysqli_query($con, $query);
                                        while ($row = mysqli_fetch_array($ret)) {
                                        ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($row['ID']); ?></td>
                                                <td>
                                                    <?php echo htmlspecialchars($row['OrderType']); ?> 
                                                    <?php echo ($row['TableID'] > 0) ? ' - ' . htmlspecialchars($row['TableName']) : ''; ?>
                                                </td>
                                                <td><?php echo date('Y-m-d', strtotime($row['OrderDate'])); ?></td>
                                                <td><?php echo htmlspecialchars($row['Time']); ?></td>
                                                <td><?php echo htmlspecialchars($row['OrderItems']); ?></td>
                                                <td><?php echo number_format($row['ServiceCharge'], 2); ?></td>
                                                <td><?php echo number_format($row['Discount'], 2); ?></td>
                                                <td class="font-weight-bold"><?php echo number_format($row['TotalAmount'], 2); ?></td>
                                                <td>
                                                    <?php 
                                                    $status = $row['Status'];
                                                    $badge = ($status == 'Paid') ? 'success' : 'warning';
                                                    echo "<span class='badge badge-$badge'>$status</span>";
                                                    ?>
                                                </td>
                                                <td>
                                                    <?php 
                                                    $pm = $row['PaymentMethod'];
                                                    if($pm === '0') echo 'Cash';
                                                    elseif($pm === '1') echo 'Card';
                                                    elseif($pm === '2') echo 'Online';
                                                    else echo htmlspecialchars($pm);
                                                    ?>
                                                </td>
                                                <td>
                                                    <a href="../cart/receipt.php?order_id=<?php echo urlencode($row['ID']); ?>" class="btn btn-primary btn-sm">
                                                        <i class="fas fa-eye"></i> View Receipt
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                </div>
                <!-- /.container-fluid -->

            </div>
            <!-- End of Main Content -->

            <?php include('../include/footer.php'); ?>

        </div>
        <!-- End of Content Wrapper -->

    </div>
    <!-- End of Page Wrapper -->

    <?php include('../include/script.php'); ?>

    <!-- Page level plugins -->
    <script src="/breezebay/vendor/datatables/jquery.dataTables.min.js"></script>
    <script src="/breezebay/vendor/datatables/dataTables.bootstrap4.min.js"></script>

    <script>
        $(document).ready(function() {
            var table = $('#dataTable').DataTable({
                "order": [[0, "desc"]], // Sort by the first column (Order No) descending
                "initComplete": function() {
                    // Add the date filter input inside the DataTables filter area
                    var filterHtml = '<label class="mr-3">Start Date: <input type="date" id="filter_start_date" class="form-control form-control-sm" style="display:inline-block; width:auto;"></label>';
                    filterHtml += '<label class="mr-3">End Date: <input type="date" id="filter_end_date" class="form-control form-control-sm" style="display:inline-block; width:auto;"></label>';
                    $("#dataTable_filter").prepend(filterHtml);

                    // Redraw the table whenever the date input changes
                    $('#filter_start_date, #filter_end_date').on('change', function() {
                        table.draw();
                        updatePrintLink();
                    });
                }
            });

            // Custom DataTables filtering logic for the Date column (Index 2)
            $.fn.dataTable.ext.search.push(
                function(settings, data) {
                    var startDate = $('#filter_start_date').val();
                    var endDate = $('#filter_end_date').val();
                    var rowDate = data[2]; // Date column is the 3rd column (index 2)

                    if (startDate && rowDate < startDate) {
                        return false;
                    }
                    if (endDate && rowDate > endDate) {
                        return false;
                    }
                    return true;
                }
            );

            function updatePrintLink() {
                var start = $('#filter_start_date').val();
                var end = $('#filter_end_date').val();
                var url = 'print_report.php?from=' + encodeURIComponent(start) + '&to=' + encodeURIComponent(end);
                $('#printReportBtn').attr('href', url);
            }
            updatePrintLink(); // Initialize on load
        });
    </script>

</body>

</html>