<?php
session_start();
include('../include/dbconnection.php');

if (empty($_SESSION['uid'])) {
    header('location:../auth/login.php');
    exit;
}

if (isset($_POST['id'])) {
    $id = intval($_POST['id']);
    $stmt = $con->prepare("DELETE FROM tblreservation WHERE ID = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        echo 'success';
    } else {
        echo 'Error: ' . htmlspecialchars($stmt->error);
    }
    $stmt->close();
}
?>
