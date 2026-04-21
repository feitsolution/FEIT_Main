<?php
session_start();
include('../include/dbconnection.php');

if (empty($_SESSION['uid'])) {
    header('location:../auth/login.php');
    exit;
}

include('tables_fetch_waitor.php');
include('orders_fetch_waitor.php');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    
    <title>RMS - Waitor Page</title>
    <?php include_once('../include/header.php'); ?>
</head>

<body id="page-top">

    <!-- Page Wrapper -->
    <div id="wrapper">
        <!-- Content Wrapper -->
        <div id="content-wrapper" class="d-flex flex-column">

            <!-- Main Content -->
            <div id="content">

                <?php include_once('../include/top-nav.php'); ?>

                <!-- Begin Page Content -->
                <div class="container-fluid">

                    

                    <div class="row">
                        <div class="col-xl-3 col-md-6 mb-4">
                            <a href="#" id="openNewBillBtn" class="btn btn-primary btn-lg btn-block py-5 shadow-sm">
                                <i class="fas fa-plus-circle fa-2x mr-2"></i> <span class="h4">Open New Bill</span>
                            </a>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-xl-3 col-md-6 mb-4">
                            <a href="#" id="processingBillsBtn" class="btn btn-primary btn-lg btn-block py-5 shadow-sm">
                                <i class="fas fa-utensils fa-2x mr-2"></i> <span class="h4">Processing Bills</span>
                            </a>
                        </div>
                    </div>

                </div>
                <!-- /.container-fluid -->

            </div>
            <!-- End of Main Content -->

            <?php include_once('../include/footer.php'); ?>

        </div>
        <!-- End of Content Wrapper -->

    </div>
    <!-- End of Page Wrapper -->

    <?php include_once('../include/script.php'); ?>

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        $(document).ready(function() {
            $('#openNewBillBtn').on('click', function(e) {
                e.preventDefault();
                Swal.fire({
                    title: "Select a Table",
                    html: <?php echo json_encode($table_buttons); ?>,
                    showConfirmButton: false,
                    showCancelButton: true,
                    cancelButtonColor: "#d33",
                    cancelButtonText: "Cancel"
                });
            });

            // Processing Bills Button Click
            $('#processingBillsBtn').on('click', function(e) {
                e.preventDefault();
                Swal.fire({
                    title: "Pending Dine-in Orders",
                    html: <?php echo json_encode($pending_order_buttons); ?>,
                    showConfirmButton: false,
                    showCloseButton: true
                });
            });

            // Real-time notification logic for ready orders
            let isNotifying = false;
            let audioPlayer = null;

            function checkForReadyOrders() {
                // Prevent multiple overlapping alerts
                if (isNotifying || Swal.isVisible()) return;

                $.ajax({
                    url: 'check_ready_orders.php',
                    method: 'GET',
                    dataType: 'json',
                    success: function(data) {
                        if (data.ready) {
                            isNotifying = true;

                            // 1. Play the ringtone nonstop (looping)
                            if (data.sound) {
                                audioPlayer = new Audio('../branding/uploads/' + data.sound);
                                audioPlayer.loop = true;
                                audioPlayer.play().catch(e => console.log("Interaction required for audio."));
                            }

                            // 2. Show the SweetAlert
                            Swal.fire({
                                title: 'Order is Ready!',
                                html: `KOT no ${data.kot} and Table no ${data.table_name} order is ready`,
                                icon: 'success',
                                confirmButtonText: 'Receive',
                                allowOutsideClick: false,
                                allowEscapeKey: false
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    // 3. Stop the sound when clicking Receive
                                    if (audioPlayer) {
                                        audioPlayer.pause();
                                        audioPlayer.currentTime = 0;
                                    }

                                    // 4. Update all rows for this KOT to Received (3)
                                    $.post('mark_received.php', { order_id: data.order_id, kot: data.kot }, function() {
                                        isNotifying = false;
                                        location.reload(); // Refresh to update button states
                                    });
                                }
                            });
                        }
                    }
                });
            }

            setInterval(function() {
                checkForReadyOrders();
            }, 5000);

            // Event delegation for dynamically added table buttons
            $(document).on('click', '.table-btn', function() {
                var tableId = $(this).data('id');
                var tableName = $(this).data('name');
                
                // Close the selection modal
                Swal.close();

                // Confirm seating
                Swal.fire({
                    title: "Confirm Seating",
                    text: "Seat customers at " + tableName + "?",
                    icon: "question",
                    showCancelButton: true,
                    confirmButtonColor: "#3085d6",
                    cancelButtonColor: "#d33",
                    confirmButtonText: "Yes, Confirm"
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: '/breezebay/order/create_oder.php',
                            type: 'POST',
                            data: { table_id: tableId },
                            dataType: 'json',
                            success: function(response) {
                                if (response.status === 'success') {
                                    window.location.href = '../waitor/waitor_cart.php?order_id=' + response.order_id + '&table_id=' + tableId;
                                } else {
                                    Swal.fire("Error", "Failed to open order: " + response.message, "error");
                                }
                            },
                            error: function() { Swal.fire("Error", "AJAX request failed", "error"); }
                        });
                    }
                });
            });
        });
    </script>

</body>

</html>