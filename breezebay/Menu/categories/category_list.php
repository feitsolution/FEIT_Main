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

     <title>RMS - Category List</title>
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
                     <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <!--<h1 class="h3 mb-0 text-gray-800">Category List</h1>-->
                        <button class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm" data-toggle="modal" data-target="#addParentCategoryModal"><i class="fas fa-plus fa-sm text-white-50"></i> New Parent Category</button>
                    </div>


                     <!-- DataTales Example -->
                     <div class="card shadow mb-4">
                         <div class="card-header py-3">
                             <h6 class="m-0 font-weight-bold text-primary">Sub-Category Data Table</h6>
                         </div>
                         <div class="card-body">
                             <div class="table-responsive">
                                 <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                     <thead>
                                         <tr>
                                             <th>Parent Category</th>
                                             <th>Sub Category Name</th>
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

    <!-- Edit Category Modal -->
    <div class="modal fade" id="editCategoryModal" tabindex="-1" role="dialog" aria-labelledby="editCategoryModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editCategoryModalLabel">Edit Category</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div id="edit-message"></div>
                    <form id="category-edit-form" method="post">
                        <input type="hidden" name="category_id" id="edit_category_id">
                        <div class="form-group">
                            <label for="edit_parent_category">Parent Category</label>
                            <select class="form-control" id="edit_parent_category" name="parent_category" required>
                                <option value="">Select Parent Category</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="edit_category_name">Sub Category Name</label>
                            <input type="text" class="form-control" id="edit_category_name" name="category_name" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" form="category-edit-form" class="btn btn-primary">Save changes</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Parent Category Modal -->
    <div class="modal fade" id="addParentCategoryModal" tabindex="-1" role="dialog" aria-labelledby="addParentCategoryModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addParentCategoryModalLabel">Add New Parent Category</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div id="parent-message"></div>
                    <form id="parent-category-form" method="post">
                        <div class="form-group">
                            <label for="parent_category_name">Parent Category Name</label>
                            <input type="text" class="form-control" id="parent_category_name" name="parent_category_name" required>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-primary">Add Parent Category</button>
                        </div>
                    </form>
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
        function loadParentCategories() {
            $.ajax({
                url: 'parent_category_fetch.php',
                type: 'GET',
                dataType: 'json',
                success: function(data) {
                    var options = '<option value="">Select Parent Category</option>';
                    $.each(data, function(index, item) {
                        options += '<option value="' + item.ID + '">' + item.ParentCategoryName + '</option>';
                    });
                    $('#edit_parent_category').html(options);
                }
            });
        }
        loadParentCategories();

         var table = $('#dataTable').DataTable({
             "ajax": {
                 "url": "category_fetch.php", // This file needs to be created
                 "dataSrc": "data"
             },
             "columns": [
                 { "data": "ParentCategoryName", "defaultContent": "<i>Not set</i>" },
                 { "data": "CategoryName" },
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
             var categoryId = $(this).data('id');
             $('#edit-message').html(''); // Clear previous messages

             // Fetch category details
             $.getJSON('category_get_details.php', { id: categoryId }, function(response) {
                 if (response) {
                     $('#edit_category_id').val(response.ID);
                     $('#edit_category_name').val(response.CategoryName);
                     $('#edit_parent_category').val(response.ParentCategoryID);
                     $('#editCategoryModal').modal('show');
                 } else {
                     alert('Error: Could not fetch category details.');
                 }
             });
         });

         // Edit form submission
         $('#category-edit-form').on('submit', function(e) {
             e.preventDefault();
             var formData = new FormData(this);

             $.ajax({
                 url: 'category_update.php',
                 type: 'POST',
                 data: formData,
                 contentType: false,
                 processData: false,
                 success: function(response) {
                     if (response.trim() === 'yes') {
                         $('#editCategoryModal').modal('hide');
                         table.ajax.reload();
                         alert('Category updated successfully.');
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
            var categoryId = $(this).data('id');
            var currentStatus = $(this).data('status');
            var newStatus = currentStatus == 1 ? 0 : 1;
            var actionText = newStatus == 1 ? 'activate' : 'deactivate';

            if (confirm('Are you sure you want to ' + actionText + ' this category?')) {
                $.post('category_status_update.php', { id: categoryId, status: newStatus }, function(response) {
                    if (response.trim() === 'success') {
                        alert('Category status updated successfully.');
                        table.ajax.reload(null, false); // reload data without resetting page
                    } else {
                        alert('Failed to update status: ' + response);
                    }
                });
            }
        });

         // Delete functionality
         $('#dataTable tbody').on('click', '.delete-btn', function() {
             var categoryId = $(this).data('id');
             if (confirm('Are you sure you want to delete this category?')) {
                 $.post('category_delete.php', { id: categoryId }, function(response) {
                     if (response.trim() === 'success') {
                         alert('Category deleted successfully.');
                         table.ajax.reload();
                     } else {
                         alert('Failed to delete category: ' + response);
                     }
                 });
             }
         });

        // Handle Parent Category Form Submission
        $('#parent-category-form').on('submit', function(e) {
            e.preventDefault();
            var formData = new FormData(this);

            $.ajax({
                url: 'parent_category_submit.php',
                type: 'POST',
                data: formData,
                contentType: false,
                processData: false,
                success: function(response) {
                    var messageContainer = $('#parent-message');
                    if (response.trim() === 'yes') {
                        messageContainer.html('<div class="alert alert-success">Parent Category added successfully.</div>');
                        $('#parent-category-form')[0].reset();
                        loadParentCategories(); // Reload the dropdown in the edit modal
                        setTimeout(function() {
                            $('#addParentCategoryModal').modal('hide');
                            messageContainer.html(''); 
                        }, 1500);
                    } else {
                        messageContainer.html('<div class="alert alert-danger">Failed: ' + response + '</div>');
                    }
                }
            });
        });
     });
    </script>
 </body>

 </html>