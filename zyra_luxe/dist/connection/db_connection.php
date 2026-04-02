<?php
date_default_timezone_set('Asia/Colombo');
// Database connection
$servername = getenv('DB_HOST');
$username   = getenv('DB_USER');
$password   = getenv('DB_PASS');
$dbname     = 'zyra_luxe';

$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch allow_inventory 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['allow_inventory'])) {
    $res = $conn->query("SELECT allow_inventory FROM branding WHERE active = 1 LIMIT 1");
    if ($res && $row = $res->fetch_assoc()) {
        $_SESSION['allow_inventory'] = (int)$row['allow_inventory'];
    } else {
        $_SESSION['allow_inventory'] = 1; // Default to enabled if not found
    }
}

?>