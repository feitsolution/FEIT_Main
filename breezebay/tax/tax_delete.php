<?php
session_start();
include('../include/dbconnection.php');

if (empty($_SESSION['uid'])) {
    header('location:../auth/login.php');
    exit;
}

if (isset($_POST['id'])) {
    $id = $_POST['id'];

    // Using prepared statements to prevent SQL injection
    $stmt = $con->prepare("DELETE FROM tbltax WHERE ID = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        echo 'success';
    } else {
        echo 'Error: ' . htmlspecialchars($stmt->error);
    }
    $stmt->close();
    $con->close();
} else {
    echo 'Invalid request.';
}
?>