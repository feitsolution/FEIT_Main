<?php
include('../include/dbconnection.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['table_id']) && isset($_POST['status'])) {
    $tableId = intval($_POST['table_id']);
    $status = $_POST['status'];

    $stmt = $con->prepare("UPDATE tbltables SET Status = ? WHERE ID = ?");
    $stmt->bind_param("si", $status, $tableId);

    if ($stmt->execute()) {
        echo 'success';
    } else {
        echo 'error: ' . $stmt->error;
    }
    $stmt->close();
    $con->close();
}
?>