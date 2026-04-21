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

     <title>RMS - Table List</title>
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
                     <div class="d-sm-flex align-items-center justify-content-between mb-4">
                         <!--<h1 class="h3 mb-0 text-gray-800">Table List</h1>-->
                         <!--<button class="btn btn-primary" data-toggle="modal" data-target="#addTableModal"><i
                                 class="fas fa-plus"></i> Add Table</button>-->
                     </div>


                     <!-- DataTales Example -->
                     <div class="card shadow mb-4">
                         <div class="card-header py-3">
                             <h6 class="m-0 font-weight-bold text-primary">Table Data Table</h6>
                         </div>
                         <div class="card-body">
                             <div class="table-responsive">
                                 <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                     <thead>
                                         <tr>
                                             <th>Table No</th>
                                             <th>Chair Count</th>
                                             <th>Table Status</th>
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

     <!-- Edit Table Modal -->
     <div class="modal fade" id="editTableModal" tabindex="-1" role="dialog" aria-labelledby="editTableModalLabel"
         aria-hidden="true">
         <div class="modal-dialog" role="document">
             <div class="modal-content">
                 <div class="modal-header">
                     <h5 class="modal-title" id="editTableModalLabel">Edit Table</h5>
                     <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                         <span aria-hidden="true">&times;</span>
                     </button>
                 </div>
                 <div class="modal-body">
                     <div id="edit-message"></div>
                     <form id="table-edit-form" method="post">
                         <input type="hidden" name="table_id" id="edit_table_id">
                         <div class="form-group">
                             <label for="edit_table_name">Table No</label>
                             <input type="text" class="form-control" id="edit_table_name" name="table_name" required>
                         </div>
                         <div class="form-group">
                             <label for="edit_table_chairs">Chair Count</label>
                             <input type="number" class="form-control" id="edit_table_chairs" name="table_chairs"
                                 required>
                         </div>
                     </form>
                 </div>
                 <div class="modal-footer">
                     <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                     <button type="submit" form="table-edit-form" class="btn btn-primary">Save changes</button>
                 </div>
             </div>
         </div>
     </div>

     <!-- Add Table Modal 
     <div class="modal fade" id="addTableModal" tabindex="-1" role="dialog" aria-labelledby="addTableModalLabel"
         aria-hidden="true">
         <div class="modal-dialog" role="document">
             <div class="modal-content">
                 <div class="modal-header">
                     <h5 class="modal-title" id="addTableModalLabel">Add New Table</h5>
                     <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                         <span aria-hidden="true">&times;</span>
                     </button>
                 </div>
                 <div class="modal-body">
                     <div id="add-message"></div>
                     <form id="table-add-form" method="post">
                         <div class="form-group">
                             <label for="add_table_name">Table No</label>
                             <input type="text" class="form-control" id="add_table_name" name="table_name" required>
                         </div>
                         <div class="form-group">
                             <label for="add_table_chairs">Chair Count</label>
                             <input type="number" class="form-control" id="add_table_chairs" name="table_chairs"
                                 required>
                         </div>
                     </form>
                 </div>
                 <div class="modal-footer">
                     <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                     <button type="submit" form="table-add-form" class="btn btn-primary">Add Table</button>
                 </div>
             </div>
         </div>
     </div>-->

     <?php include_once('../include/script.php'); ?>

     <!-- Page level plugins -->
     <script src="/breezebay/vendor/datatables/jquery.dataTables.js"></script>
     <script src="/breezebay/vendor/datatables/dataTables.bootstrap4.min.js"></script>

     <script>
     $(document).ready(function() {
         var table = $('#dataTable').DataTable({
             "ajax": {
                 "url": "table_fetch.php",
                 "dataSrc": "data"
             },
             "columns": [{
                     "data": "TableName",
                     "render": function(data, type, row) {
                         return 'Table ' + data;
                     }
                 },
                 {
                     "data": "ChairCount"
                 },
                 {
                     "data": "Status",
                     "render": function(data, type, row) {
                         // Handle '0' (default DB value) and trim whitespace (e.g. 'Available\n')
                         var status = (data) ? data.toString().trim() : '';
                         if (status == 'Available' || status == '0') {
                             return '<span class="badge badge-success">Available</span>';
                         } else if (status == 'Reserved' || status == '1') {
                             return '<span class="badge badge-warning">Reserved</span>';
                         } else {
                             return '<span class="badge badge-danger">Seated</span>';
                         }
                     }
                 },
                 {
                     "data": "ID",
                     "render": function(data, type, row) {
                         return '<button class="btn btn-primary btn-sm edit-btn" data-id="' +
                             data + '" title="Edit"><i class="fas fa-edit"></i></button> ' +
                             '<button class="btn btn-danger btn-sm delete-btn" data-id="' +
                             data +
                             '" title="Delete"><i class="fas fa-trash-alt"></i></button>';
                     },
                     "orderable": false,
                     "searchable": false
                 }
             ]
         });

         // Edit button click handler
         $('#dataTable tbody').on('click', '.edit-btn', function() {
             var tableId = $(this).data('id');
             $('#edit-message').html(''); // Clear previous messages

             // Fetch table details
             $.getJSON('table_get_details.php', {
                 id: tableId
             }, function(response) {
                 if (response) {
                     $('#edit_table_id').val(response.ID);
                     $('#edit_table_name').val(response.TableName);
                     $('#edit_table_chairs').val(response.ChairCount);

                     $('#editTableModal').modal('show');
                 } else {
                     alert('Error: Could not fetch table details.');
                 }
             });
         });

         // Edit form submission
         $('#table-edit-form').on('submit', function(e) {
             e.preventDefault();
             var formData = new FormData(this);

             $.ajax({
                 url: 'table_update.php',
                 type: 'POST',
                 data: formData,
                 contentType: false,
                 processData: false,
                 success: function(response) {
                     if (response.trim() === 'yes') {
                         $('#editTableModal').modal('hide');
                         table.ajax.reload();
                         alert('Table updated successfully.');
                     } else {
                         $('#edit-message').html(
                             '<div class="alert alert-danger">Update failed: ' +
                             response + '</div>');
                     }
                 },
                 error: function() {
                     $('#edit-message').html(
                         '<div class="alert alert-danger">An error occurred during the update.</div>'
                         );
                 }
             });
         });

         // Add form submission
         $('#table-add-form').on('submit', function(e) {
             e.preventDefault();
             var formData = new FormData(this);

             $.ajax({
                 url: 'table_add.php',
                 type: 'POST',
                 data: formData,
                 contentType: false,
                 processData: false,
                 success: function(response) {
                     if (response.trim() === 'success') {
                         $('#addTableModal').modal('hide');
                         $('#table-add-form')[0].reset();
                         table.ajax.reload();
                         alert('Table added successfully.');
                     } else {
                         $('#add-message').html(
                             '<div class="alert alert-danger">Add failed: ' + response +
                             '</div>');
                     }
                 },
                 error: function() {
                     $('#add-message').html(
                         '<div class="alert alert-danger">An error occurred.</div>');
                 }
             });
         });

         // Delete functionality
         $('#dataTable tbody').on('click', '.delete-btn', function() {
             var tableId = $(this).data('id');
             if (confirm('Are you sure you want to delete this table?')) {
                 $.post('table_delete.php', {
                     id: tableId
                 }, function(response) {
                     if (response.trim() === 'success') {
                         alert('Table deleted successfully.');
                         table.ajax.reload();
                     } else {
                         alert('Failed to delete table: ' + response);
                     }
                 });
             }
         });
     });
     </script>
 </body>

 </html>