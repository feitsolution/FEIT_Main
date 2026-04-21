<?php
session_start();
include('../include/dbconnection.php');

if (empty($_SESSION['uid'])) {
    header('location:../auth/login.php');
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['id']) && isset($_POST['status'])) {
        $id = intval($_POST['id']);
        $status = $_POST['status'];
        $amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0.00;

        // Validate status if needed
        if ($status !== 'Confirmed' && $status !== 'Pending') {
             echo 'Invalid status';
             exit;
        }

        $stmt = $con->prepare("UPDATE tblreservation SET Status = ?, pay_amount = ? WHERE ID = ?");
        $stmt->bind_param("sdi", $status, $amount, $id);

        if ($stmt->execute()) {
            echo 'success';
        } else {
            echo 'Error: ' . htmlspecialchars($stmt->error);
        }
        $stmt->close();
        $con->close();
    } else {
        echo 'Invalid parameters.';
    }
} else {
    echo 'Invalid request method.';
}
?>