<?php
session_start();
include('../include/dbconnection.php');

if (empty($_SESSION['uid'])) {
    exit;
}

$tbl_query = mysqli_query($con, "SELECT t.ID, t.TableName, o.ID as OrderID FROM tbltables t JOIN tblorder o ON t.ID = o.TableID WHERE o.Status = 'Pending'");

if (mysqli_num_rows($tbl_query) > 0) {
    while ($tbl_row = mysqli_fetch_assoc($tbl_query)) {
        // Escape single quotes for JS function parameter
        $tableNameJs = addslashes($tbl_row['TableName']);
        echo '<a class="dropdown-item" href="javascript:void(0)" onclick="selectTable(\'' . $tableNameJs . '\', ' . $tbl_row['ID'] . ')">' . htmlspecialchars($tbl_row['TableName']) . ' - Bill #' . $tbl_row['OrderID'] . '</a>';
    }
} else {
    echo '<span class="dropdown-item text-muted">No pending tables</span>';
}
?>