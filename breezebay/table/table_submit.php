<?php
session_start();
include('../include/dbconnection.php');

if (empty($_SESSION['uid'])) {
    header('location:../auth/login.php');
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $table_name = $_POST['table_name'];
    $table_chairs = $_POST['table_chairs'];

    // Basic validation
    if (empty($table_name) || empty($table_chairs)) {
        echo "All fields are required.";
        exit;
    }

    // Check for duplicate table name
    $check_stmt = $con->prepare("SELECT ID FROM tbltables WHERE TableName = ?");
    $check_stmt->bind_param("s", $table_name);
    $check_stmt->execute();
    $check_stmt->store_result();
    if ($check_stmt->num_rows > 0) {
        echo "Table No already exists. Please choose a different one.";
        $check_stmt->close();
        $con->close();
        exit;
    }
    $check_stmt->close();

    // Using prepared statements to prevent SQL injection
    $stmt = $con->prepare("INSERT INTO tbltables (TableName, ChairCount) VALUES (?, ?)");
    $stmt->bind_param("si", $table_name, $table_chairs);

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