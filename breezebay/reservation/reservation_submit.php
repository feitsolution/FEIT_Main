<?php
session_start();
include('../include/dbconnection.php');

if (empty($_SESSION['uid'])) {
    header('location:../auth/login.php');
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $table_id = $_POST['table_id'];
    $date = $_POST['res_date'];
    $pax = $_POST['res_pax'];
    $c_name = $_POST['customer_name'];
    $c_contact = $_POST['customer_contact'];

    if (empty($table_id) || empty($date) || empty($pax) || empty($c_name) || empty($c_contact)) {
        echo "All fields are required.";
        exit;
    }

    $stmt = $con->prepare("INSERT INTO tblreservation (TableID, ReservationDate, Pax, CustomerName, CustomerContact) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("isiss", $table_id, $date, $pax, $c_name, $c_contact);
    if ($stmt->execute()) {
        echo 'yes';
    } else {
        echo 'Error: ' . htmlspecialchars($stmt->error);
    }
    $stmt->close();
    $con->close();
} else {
    echo "Invalid request.";
}
?>