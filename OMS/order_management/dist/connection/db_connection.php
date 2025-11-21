<?php
// Database connection for AWS Elastic Beanstalk using environment variables

$servername = getenv('DB_HOST');
$username   = getenv('DB_USER');
$password   = getenv('DB_PASS');
$dbname     = getenv('DB_NAME');

// Optional: check for missing values
if (!$servername || !$username || !$password || !$dbname) {
    die("Database connection environment variables are not set properly.");
}

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
