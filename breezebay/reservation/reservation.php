<?php
session_start();
include('../include/dbconnection.php');

if (empty($_SESSION['uid'])) {
    header('location:../auth/login.php');
    exit;
}

$message = '';
$available_tables = [];
$search_date = '';
$search_pax = '';

// Check Availability
if (isset($_POST['check'])) {
    $search_date = $_POST['reservation_date'];
    $search_pax = $_POST['n_pax'];

    if (!empty($search_date) && !empty($search_pax)) {
        // Fetch tables that can accommodate the pax
        // In a real scenario, you would also check against existing reservations for this date
        $stmt = $con->prepare("SELECT * FROM tbltables WHERE ChairCount >= ? ORDER BY ChairCount ASC");
        $stmt->bind_param("i", $search_pax);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $available_tables[] = $row;
        }
        $stmt->close();

        if (empty($available_tables)) {
            $message = '<div class="alert alert-warning">No tables found with sufficient capacity.</div>';
        }
    } else {
        $message = '<div class="alert alert-danger">Please fill in all fields.</div>';
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    
    <title>RMS - Reservation</title>
    <?php include_once('../include/header.php'); ?>
</head>

<body id="page-top">

    <!-- Page Wrapper -->
    <div id="wrapper">

        <?php include_once('../include/sidebar.php'); ?>

        <!-- Content Wrapper -->
        <div id="content-wrapper" class="d-flex flex-column">

            <!-- Main Content -->
            <div id="content">

                <?php include_once('../include/top-nav.php'); ?>

                <!-- Begin Page Content -->
                <div class="container-fluid">

                    <!-- Page Heading -->
                    <h1 class="h3 mb-4 text-gray-800">Table Reservation</h1>

                    <?php if (!empty($message)) echo $message; ?>

                    <!-- Check Availability Form -->
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Check Availability</h6>
                        </div>
                        <div class="card-body">
                            <form method="post">
                                <div class="form-row">
                                    <div class="form-group col-md-5">
                                        <label for="reservation_date">Date</label>
                                        <input type="date" class="form-control" id="reservation_date" name="reservation_date" value="<?php echo htmlspecialchars($search_date); ?>" required>
                                    </div>
                                    <div class="form-group col-md-5">
                                        <label for="n_pax">Number of Packs (Pax)</label>
                                        <input type="number" class="form-control" id="n_pax" name="n_pax" min="1" value="<?php echo htmlspecialchars($search_pax); ?>" required>
                                    </div>
                                    <div class="form-group col-md-2 d-flex align-items-end">
                                        <button type="submit" name="check" class="btn btn-primary btn-block">Find Tables</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Available Tables -->
                    <?php if (!empty($available_tables)): ?>
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-success">Available Tables</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <?php foreach ($available_tables as $table): ?>
                                <div class="col-xl-3 col-md-6 mb-4">
                                    <div class="card border-left-success shadow h-100 py-2">
                                        <div class="card-body">
                                            <div class="row no-gutters align-items-center">
                                                <div class="col mr-2">
                                                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                                        <?php echo htmlspecialchars($table['TableName']); ?>
                                                    </div>
                                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                        <?php echo htmlspecialchars($table['ChairCount']); ?> Chairs
                                                    </div>
                                                </div>
                                                <div class="col-auto">
                                                    <i class="fas fa-chair fa-2x text-gray-300"></i>
                                                </div>
                                            </div>
                                            <button class="btn btn-sm btn-success mt-3 btn-block" 
                                                data-toggle="modal" 
                                                data-target="#bookModal" 
                                                data-id="<?php echo $table['ID']; ?>"
                                                data-name="<?php echo htmlspecialchars($table['TableName']); ?>">
                                                Book This Table
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

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

    <!-- Booking Modal -->
    <div class="modal fade" id="bookModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Reservation</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="booking-form" method="post">
                    <div class="modal-body">
                        <div id="booking-message"></div>
                        <input type="hidden" name="res_date" value="<?php echo htmlspecialchars($search_date); ?>">
                        <input type="hidden" name="res_pax" value="<?php echo htmlspecialchars($search_pax); ?>">
                        <input type="hidden" name="table_id" id="modal_table_id">
                        
                        <p>Booking <strong><span id="modal_table_name"></span></strong> for <strong><?php echo htmlspecialchars($search_pax); ?></strong> people on <strong><?php echo htmlspecialchars($search_date); ?></strong>.</p>
                        
                        <div class="form-group">
                            <label>Customer Name</label>
                            <input type="text" class="form-control" name="customer_name" required>
                        </div>
                        <div class="form-group">
                            <label>Contact Number</label>
                            <input type="text" class="form-control" name="customer_contact" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Confirm Booking</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        $('#bookModal').on('show.bs.modal', function (event) {
            var button = $(event.relatedTarget);
            var id = button.data('id');
            var name = button.data('name');
            var modal = $(this);
            modal.find('#modal_table_id').val(id);
            modal.find('#modal_table_name').text(name);
            $('#booking-message').html(''); // Clear previous messages
        });

        $('#booking-form').on('submit', function(e) {
            e.preventDefault();
            var formData = new FormData(this);

            $.ajax({
                url: 'reservation_submit.php',
                type: 'POST',
                data: formData,
                contentType: false,
                processData: false,
                success: function(response) {
                    if (response.trim() === 'yes') {
                        alert('Reservation booked successfully!');
                        window.location.href = 'reservation.php';
                    } else {
                        $('#booking-message').html('<div class="alert alert-danger">' + response + '</div>');
                    }
                }
            });
        });
    </script>

</body>

</html>