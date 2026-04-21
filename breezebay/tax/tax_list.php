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

    <title>RMS - Tax Table</title>
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
                    <!--<h1 class="h3 mb-2 text-gray-800">Tax Table</h1>-->


                    <!-- DataTales Example -->
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Tax Table</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>Tax Name</th>
                                            <th>Tax Percentage</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    </tbody>
                                </table>
                            </div>
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

    <!-- Edit Tax Modal -->
    <div class="modal fade" id="editTaxModal" tabindex="-1" role="dialog" aria-labelledby="editTaxModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editTaxModalLabel">Edit Tax</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div id="edit-message"></div>
                    <form id="tax-edit-form" method="post">
                        <input type="hidden" name="tax_id" id="edit_tax_id">
                        <div class="form-group">
                            <label for="edit_tax_name">Tax Name</label>
                            <input type="text" class="form-control" id="edit_tax_name" name="tax_name" required>
                        </div>
                        <div class="form-group">
                            <label for="edit_tax_percentage">Tax Percentage (%)</label>
                            <input type="number" class="form-control" id="edit_tax_percentage" name="tax_percentage" step="0.01" min="0" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" form="tax-edit-form" class="btn btn-primary">Save changes</button>
                </div>
            </div>
        </div>
    </div>

    <?php include_once('../include/script.php'); ?>

    <!-- Page level plugins -->
    <script src="/breezebay/vendor/datatables/jquery.dataTables.js"></script>
    <script src="/breezebay/vendor/datatables/dataTables.bootstrap4.min.js"></script>

    <script>
    $(document).ready(function() {
        var table = $('#dataTable').DataTable({
            "ajax": {
                "url": "tax_fetch.php",
                "dataSrc": "data"
            },
            "columns": [
                { "data": "TaxName" },
                { 
                    "data": "TaxPercentage",
                    "render": function(data, type, row) {
                        return parseFloat(data).toFixed(2) + '%';
                    }
                },
                {
                    "data": "Status",
                    "render": function(data, type, row) {
                        return data == 1 ? '<span class="badge badge-success">Active</span>' : '<span class="badge badge-secondary">Inactive</span>';
                    }
                },
                { 
                    "data": "ID",
                    "render": function(data, type, row) {
                        var statusBtnClass = row.Status == 1 ? 'btn-warning' : 'btn-success';
                        var statusIconClass = row.Status == 1 ? 'fa-times-circle' : 'fa-check-circle';
                        var statusTitle = row.Status == 1 ? 'Deactivate' : 'Activate';
                        return '<button class="btn ' + statusBtnClass + ' btn-sm status-btn" data-id="' + data + '" data-status="' + row.Status + '" title="' + statusTitle + '"><i class="fas ' + statusIconClass + '"></i></button> ' +
                            '<button class="btn btn-primary btn-sm edit-btn" data-id="' + data + '" title="Edit"><i class="fas fa-edit"></i></button> ' +
                            '<button class="btn btn-danger btn-sm delete-btn" data-id="' + data + '" title="Delete"><i class="fas fa-trash-alt"></i></button>';
                    },
                    "orderable": false,
                    "searchable": false
                }
            ]
        });

        // Edit button click handler
        $('#dataTable tbody').on('click', '.edit-btn', function() {
            var taxId = $(this).data('id');
            $('#edit-message').html(''); // Clear previous messages

            // Fetch tax details
            $.getJSON('tax_get_details.php', { id: taxId }, function(response) {
                if (response) {
                    $('#edit_tax_id').val(response.ID);
                    $('#edit_tax_name').val(response.TaxName);
                    $('#edit_tax_percentage').val(response.TaxPercentage);
                    $('#editTaxModal').modal('show');
                } else {
                    alert('Error: Could not fetch tax details.');
                }
            });
        });

        // Edit form submission
        $('#tax-edit-form').on('submit', function(e) {
            e.preventDefault();
            var formData = new FormData(this);

            $.ajax({
                url: 'tax_update.php',
                type: 'POST',
                data: formData,
                contentType: false,
                processData: false,
                success: function(response) {
                    if (response.trim() === 'yes') {
                        $('#editTaxModal').modal('hide');
                        table.ajax.reload();
                        alert('Tax updated successfully.');
                    } else {
                        $('#edit-message').html('<div class="alert alert-danger">Update failed: ' + response + '</div>');
                    }
                },
                error: function() {
                    $('#edit-message').html('<div class="alert alert-danger">An error occurred during the update.</div>');
                }
            });
        });

        // Status toggle button click handler
        $('#dataTable tbody').on('click', '.status-btn', function() {
            var taxId = $(this).data('id');
            var currentStatus = $(this).data('status');
            var newStatus = currentStatus == 1 ? 0 : 1;
            var actionText = newStatus == 1 ? 'activate' : 'deactivate';

            if (confirm('Are you sure you want to ' + actionText + ' this tax?')) {
                $.post('tax_status_update.php', { id: taxId, status: newStatus }, function(response) {
                    if (response.trim() === 'success') {
                        alert('Tax status updated successfully.');
                        table.ajax.reload(null, false); // reload data without resetting page
                    } else {
                        alert('Failed to update status: ' + response);
                    }
                });
            }
        });

        // Add delete functionality
        $('#dataTable tbody').on('click', '.delete-btn', function() {
            var taxId = $(this).data('id');
            if (confirm('Are you sure you want to delete this tax?')) {
                $.post('tax_delete.php', { id: taxId }, function(response) {
                    if (response.trim() === 'success') {
                        alert('Tax deleted successfully.');
                        table.ajax.reload();
                    } else {
                        alert('Failed to delete tax: ' + response);
                    }
                });
            }
        });
    });
    </script>
</body>

</html>