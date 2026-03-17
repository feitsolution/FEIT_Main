<?php
date_default_timezone_set("Asia/Colombo");
// Database connection
$fe_servername = getenv('DB_HOST');   // Database server
$fe_username   = getenv('DB_USER');        // Database username (default for XAMPP is root)
$fe_password   = getenv('DB_PASS');            // Database password (empty by default in XAMPP)
$fe_dbname     = getenv('DB_NAME_FE_IT_DB');    // Replace with your actual FE IT DB name

$fe_conn = new mysqli($fe_servername, $fe_username, $fe_password, $fe_dbname);

// Check connection
if ($fe_conn->connect_error) {
    die("Connection failed: " . $fe_conn->connect_error);
}
?>