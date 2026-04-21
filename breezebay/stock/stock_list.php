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

     <title>RMS - Product Stock List</title>
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
                     <!--<h1 class="h3 mb-2 text-gray-800">Product Stock List</h1>-->


                     <!-- DataTales Example -->
                     <div class="card shadow mb-4">
                         <div class="card-header py-3">
                             <h6 class="m-0 font-weight-bold text-primary">Product Stock Data Table</h6>
                         </div>
                         <div class="card-body">
                             <div class="table-responsive">
                                 <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                     <thead>
                                         <tr>
                                             <th>Product Name</th>
                                             <th>Product Sub category</th>
                                             <th>Product Quantity</th>
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

    <!-- Edit Stock Modal -->
    <div class="modal fade" id="editStockModal" tabindex="-1" role="dialog" aria-labelledby="editStockModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editStockModalLabel">Update Stock</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div id="edit-message"></div>
                    <form id="stock-edit-form" method="post">
                        <input type="hidden" name="product_id" id="edit_product_id">
                        <div class="form-group">
                            <label for="edit_product_name">Product Name</label>
                            <input type="text" class="form-control" id="edit_product_name" name="product_name" readonly>
                        </div>
                        <div class="form-group">
                            <label>Current Stock</label>
                            <input type="text" class="form-control" id="current_stock_display" readonly>
                        </div>
                        <div class="form-group">
                            <label for="edit_stock_qty">Quantity to Add</label>
                            <input type="number" class="form-control" id="edit_stock_qty" name="stock_qty" placeholder="Enter amount to add" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" form="stock-edit-form" class="btn btn-primary">Save changes</button>
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
                 "url": "stock_fetch.php",
                 "dataSrc": "data"
             },
             "columns": [
                 { "data": "ProductName" },
                 { "data": "SubCategoryName", "defaultContent": "<i>Not Set</i>" },
                 { "data": "Quantity", "defaultContent": "0" },
                 {
                     "data": "ProductID",
                     "render": function(data, type, row) {
                         // The button text was 'Edit Stock' or 'Add Stock' before, now it's 'Update'.
                         return '<button class="btn btn-primary btn-sm update-btn" data-id="' + data + '" title="Update Stock"><i class="fas fa-edit"></i> Update</button>';
                     },
                     "orderable": false,
                     "searchable": false
                 }
             ]
         });

         // Update button click handler
         $('#dataTable tbody').on('click', '.update-btn', function() {
             var productId = $(this).data('id');
             $('#edit-message').html('');
             $('#stock-edit-form')[0].reset();

             // Fetch product and stock details to populate the modal
             $.getJSON('stock_get_details.php', { id: productId }, function(response) {
                 if (response) {
                     $('#edit_product_id').val(response.ProductID);
                     $('#edit_product_name').val(response.ProductName);
                     $('#current_stock_display').val(response.Quantity || 0);
                     $('#edit_stock_qty').val('');

                     $('#editStockModal').modal('show');
                 } else {
                     alert('Error: Could not fetch stock details for this product.');
                 }
             });
         });

         // Edit form submission
         $('#stock-edit-form').on('submit', function(e) {
             e.preventDefault();
             var formData = new FormData(this);

             $.ajax({
                 url: 'stock_update.php',
                 type: 'POST',
                 data: formData,
                 contentType: false,
                 processData: false,
                 success: function(response) {
                     if (response.trim() === 'yes') {
                         $('#editStockModal').modal('hide');
                         table.ajax.reload();
                         alert('Stock updated successfully.');
                     } else {
                         $('#edit-message').html('<div class="alert alert-danger">Update failed: ' + response + '</div>');
                     }
                 },
                 error: function() {
                     $('#edit-message').html('<div class="alert alert-danger">An error occurred.</div>');
                 }
             });
         });
     });
     </script>
 </body>

 </html>