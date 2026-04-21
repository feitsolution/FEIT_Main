 <?php
session_start();
include('../../include/dbconnection.php');

if (empty($_SESSION['uid'])) {
    header('location:../auth/login.php');
    exit;
}

?>
 <!DOCTYPE html>
 <html lang="en">

 <head>

     <title>RMS - Product List</title>
     <?php include_once('../../include/header.php'); ?>
     
 </head>

 <body id="page-top">

     <!-- Page Wrapper -->
     <div id="wrapper">

         <?php include_once('../../include/sidebar.php'); ?>

         <!-- Content Wrapper -->
         <div id="content-wrapper" class="d-flex flex-column">

             <!-- Main Content -->
             <div id="content">

                 <?php include_once('../../include/top-nav.php'); ?>

                 <!-- Begin Page Content -->


                 <div class="container-fluid">

                     <!-- Page Heading -->
                     <!--<h1 class="h3 mb-2 text-gray-800">Product List</h1>-->


                     <!-- DataTales Example -->
                     <div class="card shadow mb-4">
                         <div class="card-header py-3">
                             <h6 class="m-0 font-weight-bold text-primary">Product Data Table</h6>
                         </div>
                         <div class="card-body">
                             <div class="table-responsive">
                                 <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                     <thead>
                                         <tr>
                                             <th>Parent Category</th>
                                             <th>Sub Category</th>
                                             <th>Product Name</th>
                                             <th>Price</th>
                                             <th>Type</th>
                                             <th>Unit</th>
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

             <?php include_once('../../include/footer.php'); ?>

         </div>
         <!-- End of Content Wrapper -->

     </div>
     <!-- End of Page Wrapper -->

    <!-- Edit Product Modal -->
    <div class="modal fade" id="editProductModal" tabindex="-1" role="dialog" aria-labelledby="editProductModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editProductModalLabel">Edit Product</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div id="edit-message"></div>
                    <form id="product-edit-form" method="post">
                        <input type="hidden" name="product_id" id="edit_product_id">
                        <div class="form-group">
                            <label for="edit_product_name">Product Name</label>
                            <input type="text" class="form-control" id="edit_product_name" name="product_name" required>
                        </div>
                        <div class="form-group">
                            <label for="edit_product_price">Product Price</label>
                            <input type="number" class="form-control" id="edit_product_price" name="product_price" step="0.01" required>
                        </div>
                        <div class="form-group">
                            <label>Countable/Uncountable Product</label>
                            <div class="custom-control custom-switch">
                                <input type="hidden" name="product_countable" value="Uncountable">
                                <input type="checkbox" class="custom-control-input" id="edit_product_countable" name="product_countable" value="Countable">
                                <label class="custom-control-label" for="edit_product_countable">Countable</label>
                            </div>
                        </div>
                        <div class="form-group" id="edit_product_unit_group">
                            <label>Select the product unit</label>
                            <br>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="product_unit" id="edit_unit_bottles" value="Bottles">
                                <label class="form-check-label" for="edit_unit_bottles">Bottles</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="product_unit" id="edit_unit_packets" value="Packets">
                                <label class="form-check-label" for="edit_unit_packets">Packets</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="product_unit" id="edit_unit_grams" value="Grams">
                                <label class="form-check-label" for="edit_unit_grams">Grams(g)</label>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" form="product-edit-form" class="btn btn-primary">Save changes</button>
                </div>
            </div>
        </div>
    </div>

     <?php include_once('../../include/script.php'); ?>

     <!-- Page level plugins -->
     <script src="/breezebay/vendor/datatables/jquery.dataTables.js"></script>
     <script src="/breezebay/vendor/datatables/dataTables.bootstrap4.min.js"></script>

     <script>
     $(document).ready(function() {
         function toggleEditProductUnit() {
             if ($('#edit_product_countable').is(':checked')) {
                 $('#edit_product_unit_group').show();
                 $('input[name="product_unit"]').prop('disabled', false);
             } else {
                 $('#edit_product_unit_group').hide();
                 $('input[name="product_unit"]').prop('disabled', true);
             }
         }
         
         // Initial state and change listener for the edit modal
         toggleEditProductUnit();
         $('#edit_product_countable').change(toggleEditProductUnit);

         var table = $('#dataTable').DataTable({
             "ajax": {
                 "url": "product_fetch.php",
                 "dataSrc": "data"
             },
             "columns": [
                 { "data": "ParentCategoryName", "defaultContent": "<i>Not Set</i>" },
                 { "data": "SubCategoryName", "defaultContent": "<i>Not Set</i>" },
                 { "data": "ProductName" },
                 { "data": "Price" },
                 { "data": "Type" },
                 { "data": "Unit" },
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
             var productId = $(this).data('id');
             $('#edit-message').html(''); // Clear previous messages
             $('#product-edit-form')[0].reset();

             // Fetch staff details
             $.getJSON('product_get_details.php', { id: productId }, function(response) {
                 if (response) {
                     $('#edit_product_id').val(response.ID);
                     $('#edit_product_name').val(response.ProductName);
                     $('#edit_product_price').val(response.Price);
                     
                     if (response.Type === 'Countable') {
                         $('#edit_product_countable').prop('checked', true);
                         // Select the correct radio button
                         $('input[name="product_unit"][value="' + response.Unit + '"]').prop('checked', true);
                     } else {
                         $('#edit_product_countable').prop('checked', false);
                     }
                     
                     toggleEditProductUnit(); // Update UI

                     $('#editProductModal').modal('show');
                 } else {
                     alert('Error: Could not fetch product details.');
                 }
             });
         });

         // Edit form submission
         $('#product-edit-form').on('submit', function(e) {
             e.preventDefault();
             var formData = new FormData(this);

             $.ajax({
                 url: 'product_update.php',
                 type: 'POST',
                 data: formData,
                 contentType: false,
                 processData: false,
                 success: function(response) {
                     if (response.trim() === 'yes') {
                         $('#editProductModal').modal('hide');
                         table.ajax.reload();
                         alert('Product updated successfully.');
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
            var productId = $(this).data('id');
            var currentStatus = $(this).data('status');
            var newStatus = currentStatus == 1 ? 0 : 1;
            var actionText = newStatus == 1 ? 'activate' : 'deactivate';

            if (confirm('Are you sure you want to ' + actionText + ' this product?')) {
                $.post('product_status_update.php', { id: productId, status: newStatus }, function(response) {
                    if (response.trim() === 'success') {
                        alert('Product status updated successfully.');
                        table.ajax.reload(null, false); // reload data without resetting page
                    } else {
                        alert('Failed to update status: ' + response);
                    }
                });
            }
        });

         // Delete functionality
         $('#dataTable tbody').on('click', '.delete-btn', function() {
             var productId = $(this).data('id');
             if (confirm('Are you sure you want to delete this product?')) {
                 $.post('product_delete.php', { id: productId }, function(response) {
                     if (response.trim() === 'success') {
                         alert('Product deleted successfully.');
                         table.ajax.reload();
                     } else {
                         alert('Failed to delete product: ' + response);
                     }
                 });
             }
         });
     });
    </script>
 </body>

 </html>