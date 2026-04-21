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

     <title>RMS - Reservation List</title>
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
                     <!--<h1 class="h3 mb-2 text-gray-800">Staff List</h1>-->


                     <!-- DataTales Example -->
                     <div class="card shadow mb-4">
                         <div class="card-header py-3">
                             <h6 class="m-0 font-weight-bold text-primary">Reservation Data Table</h6>
                         </div>
                         <div class="card-body">
                             <div class="table-responsive">
                                 <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                     <thead>
                                         <tr>
                                             <th>Customer Name</th>
                                             <th>Contact</th>
                                             <th>Pax</th>
                                             <th>Reservation Date</th>
                                             <th>Table No</th>
                                             <th>Advance Pay</th>
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

    <!-- Edit Reservation Modal -->
    <div class="modal fade" id="editReservationModal" tabindex="-1" role="dialog" aria-labelledby="editReservationModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editReservationModalLabel">Edit Reservation</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div id="edit-message"></div>
                    <form id="reservation-edit-form" method="post">
                        <input type="hidden" name="id" id="edit_id">
                        <div class="form-group">
                            <label for="edit_customer_name">Customer Name</label>
                            <input type="text" class="form-control" id="edit_customer_name" name="customer_name" required>
                        </div>
                        <div class="form-group">
                            <label for="edit_customer_contact">Contact Number</label>
                            <input type="text" class="form-control" id="edit_customer_contact" name="customer_contact" required>
                        </div>
                        <div class="form-group">
                            <label for="edit_pax">Pax</label>
                            <input type="number" class="form-control" id="edit_pax" name="pax" required>
                        </div>
                        <div class="form-group">
                            <label for="edit_pay_amount">Advance Payment</label>
                            <input type="number" class="form-control" id="edit_pay_amount" name="pay_amount" step="0.01" required>
                        </div>
                        <div class="form-group">
                            <label for="edit_reservation_date">Reservation Date</label>
                            <input type="date" class="form-control" id="edit_reservation_date" name="reservation_date" required>
                        </div>
                        <div class="form-group">
                            <label for="edit_table_id">Table ID</label>
                            <input type="number" class="form-control" id="edit_table_id" name="table_id" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" form="reservation-edit-form" class="btn btn-primary">Save changes</button>
                </div>
            </div>
        </div>
    </div>

     <?php include_once('../include/script.php'); ?>

     <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

     <!-- Page level plugins -->
     <script src="/breezebay/vendor/datatables/jquery.dataTables.js"></script>
     <script src="/breezebay/vendor/datatables/dataTables.bootstrap4.min.js"></script>

     <script>
     $(document).ready(function() {
         var table = $('#dataTable').DataTable({
             "ajax": {
                 "url": "reservation_fetch.php",
                 "dataSrc": "data",
                 "data": function(d) {
                     d.filter_date = $('#filter_date').val();
                 }
             },
             "columns": [
                 { "data": "CustomerName" },
                 { "data": "CustomerContact" },
                 { "data": "Pax" },
                 { "data": "ReservationDate" },
                 { "data": "TableName" },
                 { "data": "pay_amount" },
                 { 
                     "data": "Status",
                     "render": function(data, type, row) {
                         if (data === 'Confirmed') {
                             return '<span class="badge badge-success">Confirmed</span>';
                         } else {
                             return '<span class="badge badge-warning">Pending</span>';
                         }
                     }
                 },
                 {
                     "data": "ID",
                     "render": function(data, type, row) {
                         var confirmBtn = '';
                         if (row.Status !== 'Confirmed') {
                             confirmBtn = '<button class="btn btn-success btn-sm confirm-btn" data-id="' + data + '" title="Confirm (Advance Pay)"><i class="fas fa-check"></i></button> ';
                         } else {
                             confirmBtn = '<button class="btn btn-secondary btn-sm" disabled title="Already Confirmed"><i class="fas fa-check-double"></i></button> ';
                         }
                         return confirmBtn +
                             '<button class="btn btn-primary btn-sm edit-btn" data-id="' + data + '" title="Edit"><i class="fas fa-edit"></i></button> ' +
                             '<button class="btn btn-danger btn-sm delete-btn" data-id="' + data + '" title="Delete"><i class="fas fa-trash-alt"></i></button>';
                     },
                     "orderable": false,
                     "searchable": false
                 }
             ],
             "initComplete": function() {
                 var filterHtml = '<label class="mr-2">Filter by Date: <input type="date" id="filter_date" class="form-control form-control-sm" style="display:inline-block; width:auto;"></label>';
                 $("#dataTable_filter").prepend(filterHtml);

                 $('#filter_date').on('change', function() {
                     $('#dataTable').DataTable().ajax.reload();
                 });
             }
         });

         // Edit button click handler
         $('#dataTable tbody').on('click', '.edit-btn', function() {
             var id = $(this).data('id');
             $('#edit-message').html(''); // Clear previous messages

             // Fetch staff details
             $.getJSON('reservation_get_details.php', { id: id }, function(response) {
                 if (response) {
                     $('#edit_id').val(response.ID);
                     $('#edit_customer_name').val(response.CustomerName);
                     $('#edit_customer_contact').val(response.CustomerContact);
                     $('#edit_pax').val(response.Pax);
                     $('#edit_pay_amount').val(response.pay_amount);
                     $('#edit_reservation_date').val(response.ReservationDate);
                     $('#edit_table_id').val(response.TableID);

                     $('#editReservationModal').modal('show');
                 } else {
                     alert('Error: Could not fetch reservation details.');
                 }
             });
         });

         // Edit form submission
         $('#reservation-edit-form').on('submit', function(e) {
             e.preventDefault();
             var formData = new FormData(this);

             $.ajax({
                 url: 'reservation_update.php',
                 type: 'POST',
                 data: formData,
                 contentType: false,
                 processData: false,
                 success: function(response) {
                     if (response.trim() === 'yes') {
                         $('#editReservationModal').modal('hide');
                         table.ajax.reload();
                         alert('Reservation updated successfully.');
                     } else {
                         $('#edit-message').html('<div class="alert alert-danger">Update failed: ' + response + '</div>');
                     }
                 },
                 error: function() {
                     $('#edit-message').html('<div class="alert alert-danger">An error occurred during the update.</div>');
                 }
             });
         });

         // Confirm/Advance Pay button click handler
         $('#dataTable tbody').on('click', '.confirm-btn', function() {
             var id = $(this).data('id');
             Swal.fire({
                 title: 'Enter Advance Amount',
                 input: 'number',
                 inputAttributes: {
                     min: 0,
                     step: 0.01
                 },
                 showCancelButton: true,
                 confirmButtonText: 'Confirm & Pay',
                 showLoaderOnConfirm: true,
                 preConfirm: (amount) => {
                     if (!amount) {
                         Swal.showValidationMessage('Please enter an amount');
                         return false;
                     }
                     return $.post('reservation_status.php', {
                         id: id,
                         status: 'Confirmed',
                         amount: amount
                     }).fail(function() {
                         Swal.showValidationMessage('Request failed');
                     });
                 },
                 allowOutsideClick: () => !Swal.isLoading()
             }).then((result) => {
                 if (result.isConfirmed) {
                     if (result.value.trim() === 'success') {
                         Swal.fire({
                             icon: 'success',
                             title: 'Confirmed!',
                             text: 'Reservation confirmed with advance payment.'
                         });
                         table.ajax.reload();
                     } else {
                         Swal.fire({
                             icon: 'error',
                             title: 'Error',
                             text: 'Failed to update status: ' + result.value
                         });
                     }
                 }
             });
         });

         // Delete functionality
         $('#dataTable tbody').on('click', '.delete-btn', function() {
             var id = $(this).data('id');
             if (confirm('Are you sure you want to delete this reservation?')) {
                 $.post('reservation_delete.php', { id: id }, function(response) {
                     if (response.trim() === 'success') {
                         alert('Reservation deleted successfully.');
                         table.ajax.reload();
                     } else {
                         alert('Failed to delete reservation: ' + response);
                     }
                 });
             }
         });
     });
    </script>
 </body>

 </html>