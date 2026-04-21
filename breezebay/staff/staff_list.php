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

     <title>RMS - Staff List</title>
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
                             <h6 class="m-0 font-weight-bold text-primary">Staff Data Table</h6>
                         </div>
                         <div class="card-body">
                             <div class="table-responsive">
                                 <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                     <thead>
                                         <tr>
                                             <th>Staff Name</th>
                                             <th>NIC No</th>
                                             <th>Telephone No</th>
                                             <th>Role</th>
                                             <th>Username</th>
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

    <!-- Edit Staff Modal -->
    <div class="modal fade" id="editStaffModal" tabindex="-1" role="dialog" aria-labelledby="editStaffModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editStaffModalLabel">Edit Staff</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div id="edit-message"></div>
                    <form id="staff-edit-form" method="post">
                        <input type="hidden" name="staff_id" id="edit_staff_id">
                        <div class="form-group">
                            <label for="edit_staff_name">Staff Name</label>
                            <input type="text" class="form-control" id="edit_staff_name" name="staff_name" required>
                        </div>
                        <div class="form-group">
                            <label for="edit_staff_nic">Staff NIC No</label>
                            <input type="text" class="form-control" id="edit_staff_nic" name="staff_nic" required>
                        </div>
                        <div class="form-group">
                            <label for="edit_staff_telephone">Staff Telephone No</label>
                            <input type="tel" class="form-control" id="edit_staff_telephone" name="staff_telephone" required>
                        </div>
                        <div class="form-group">
                            <label for="edit_staff_role">Role</label>
                            <select class="form-control" id="edit_staff_role" name="staff_role" required>
                                <!-- Roles will be populated by JS -->
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="edit_staff_username">Username</label>
                            <input type="text" class="form-control" id="edit_staff_username" name="staff_username" required>
                        </div>
                        <div class="form-group">
                            <label for="edit_staff_password">New Password (leave blank to keep current)</label>
                            <input type="password" class="form-control" id="edit_staff_password" name="staff_password">
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" form="staff-edit-form" class="btn btn-primary">Save changes</button>
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
         // Fetch roles for the edit modal dropdown once
         $.getJSON('get_roles.php', function(data) {
             var rolesOptions = '';
             $.each(data, function(key, role) {
                 rolesOptions += '<option value="' + role.role_name + '">' + role.role_name + '</option>';
             });
             $('#edit_staff_role').html('<option value="">Select Role</option>' + rolesOptions);
         });

         var table = $('#dataTable').DataTable({
             "ajax": {
                 "url": "staff_fetch.php",
                 "dataSrc": "data"
             },
             "columns": [
                 { "data": "StaffName" },
                 { "data": "StaffNIC" },
                 { "data": "StaffTel" },
                 { "data": "StaffRole" },
                 { "data": "UserName" },
                 {
                     "data": "ID",
                     "render": function(data, type, row) {
                         return '<button class="btn btn-primary btn-sm edit-btn" data-id="' + data + '" title="Edit"><i class="fas fa-edit"></i></button> ' +
                             '<button class="btn btn-danger btn-sm delete-btn" data-id="' + data + '" title="Delete"><i class="fas fa-trash-alt"></i></button>';
                     },
                     "orderable": false,
                     "searchable": false
                 }
             ]
         });

         // Edit button click handler
         $('#dataTable tbody').on('click', '.edit-btn', function() {
             var staffId = $(this).data('id');
             $('#edit-message').html(''); // Clear previous messages

             // Fetch staff details
             $.getJSON('staff_get_details.php', { id: staffId }, function(response) {
                 if (response) {
                     $('#edit_staff_id').val(response.ID);
                     $('#edit_staff_name').val(response.StaffName);
                     $('#edit_staff_nic').val(response.StaffNIC);
                     $('#edit_staff_telephone').val(response.StaffTel);
                     $('#edit_staff_role').val(response.StaffRole);
                     $('#edit_staff_username').val(response.UserName);
                     $('#edit_staff_password').val(''); // Clear password field

                     $('#editStaffModal').modal('show');
                 } else {
                     alert('Error: Could not fetch staff details.');
                 }
             });
         });

         // Edit form submission
         $('#staff-edit-form').on('submit', function(e) {
             e.preventDefault();
             var formData = new FormData(this);

             $.ajax({
                 url: 'staff_update.php',
                 type: 'POST',
                 data: formData,
                 contentType: false,
                 processData: false,
                 success: function(response) {
                     if (response.trim() === 'yes') {
                         $('#editStaffModal').modal('hide');
                         table.ajax.reload();
                         alert('Staff updated successfully.');
                     } else {
                         $('#edit-message').html('<div class="alert alert-danger">Update failed: ' + response + '</div>');
                     }
                 },
                 error: function() {
                     $('#edit-message').html('<div class="alert alert-danger">An error occurred during the update.</div>');
                 }
             });
         });

         // Delete functionality
         $('#dataTable tbody').on('click', '.delete-btn', function() {
             var staffId = $(this).data('id');
             if (confirm('Are you sure you want to delete this staff member?')) {
                 $.post('staff_delete.php', { id: staffId }, function(response) {
                     if (response.trim() === 'success') {
                         alert('Staff member deleted successfully.');
                         table.ajax.reload();
                     } else {
                         alert('Failed to delete staff member: ' + response);
                     }
                 });
             }
         });
     });
    </script>
 </body>

 </html>