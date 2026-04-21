<?php
session_start();
include('../include/dbconnection.php');

if (empty($_SESSION['uid'])) {
    header('location:../auth/login.php');
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $tax_id = $_POST['tax_id'];
    $tax_name = $_POST['tax_name'];
    $tax_percentage = $_POST['tax_percentage'];

    // Basic validation
    if (empty($tax_id) || empty($tax_name) || !is_numeric($tax_percentage) || $tax_percentage < 0) {
        echo "Invalid input.";
        exit;
    }

    // Using prepared statements to prevent SQL injection
    $stmt = $con->prepare("UPDATE tbltax SET TaxName=?, TaxPercentage=? WHERE ID=?");
    $stmt->bind_param("sdi", $tax_name, $tax_percentage, $tax_id);

    if ($stmt->execute()) {
        echo 'yes';
    } else {
        echo 'Something went wrong. Please try again. Error: ' . htmlspecialchars($stmt->error);
    }
    $stmt->close();
    $con->close();
} else {
    echo "Invalid request method.";
}
?>