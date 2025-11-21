<?php
// Database connection
$servername = "gator4423";
$username = "imwijqte_db";
$password = "imwijqte_db2025a"; // Use your actual database password
$dbname = "imwijqte_feit_db"; // Replace with your database name

$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
