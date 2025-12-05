<?php
// Database connection
$servername = getenv('DB_HOST');
$username   = getenv('DB_USER');
$password   = getenv('DB_PASS');
$dbname     = getenv('DB_NAME');

$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {

    // Log the real error for developers
    error_log("DB Connection failed: " . $conn->connect_error);

    // Show a safe message to user
    echo "<div style='padding:10px; background:#ffe5e5; color:#b30000; border:1px solid #ffcccc;'>
            System Error: Unable to connect to the database.
          </div>";

    exit(); 
}
?>
