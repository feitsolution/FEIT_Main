<?php
session_start();
include('../include/dbconnection.php');

if (empty($_SESSION['uid'])) {
    header('location:../auth/login.php');
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = $_POST['id'];
    $customer_name = $_POST['customer_name'];
    $customer_contact = $_POST['customer_contact'];
    $pax = $_POST['pax'];
    $pay_amount = $_POST['pay_amount'];
    $date = $_POST['reservation_date'];
    $table_id = $_POST['table_id'];

    $stmt = $con->prepare("UPDATE tblreservation SET CustomerName=?, CustomerContact=?, Pax=?, pay_amount=?, ReservationDate=?, TableID=? WHERE ID=?");
    $stmt->bind_param("ssidsii", $customer_name, $customer_contact, $pax, $pay_amount, $date, $table_id, $id);

    if ($stmt->execute()) {
        echo 'yes';
    } else {
        echo 'Error: ' . htmlspecialchars($stmt->error);
    }
    $stmt->close();
    $con->close();
}
?>
