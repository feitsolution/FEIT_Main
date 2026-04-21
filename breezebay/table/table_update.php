<?php
session_start();
include('../include/dbconnection.php');

if (empty($_SESSION['uid'])) {
    header('location:../auth/login.php');
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $table_id = $_POST['table_id'];
    $table_name = $_POST['table_name'];
    $table_chairs = $_POST['table_chairs'];

    // Basic validation
    if (empty($table_id) || empty($table_name) || empty($table_chairs)) {
        echo "All fields are required.";
        exit;
    }

    // Check for duplicate table name, excluding current table
    $check_stmt = $con->prepare("SELECT ID FROM tbltables WHERE TableName = ? AND ID != ?");
    $check_stmt->bind_param("si", $table_name, $table_id);
    $check_stmt->execute();
    $check_stmt->store_result();
    if ($check_stmt->num_rows > 0) {
        echo "Table No already exists. Please choose a different one.";
        $check_stmt->close();
        $con->close();
        exit;
    }
    $check_stmt->close();

    $stmt = $con->prepare("UPDATE tbltables SET TableName=?, ChairCount=? WHERE ID=?");
    $stmt->bind_param("sii", $table_name, $table_chairs, $table_id);

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