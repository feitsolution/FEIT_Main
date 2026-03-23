<?php
// TEMPORARY DEBUG FILE - DELETE AFTER FIXING THE ISSUE
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Host Debug Test</h2>";

// 1. PHP Version
echo "<p><b>PHP Version:</b> " . phpversion() . "</p>";

// 2. Session test
session_start();
echo "<p><b>Session Status:</b> " . (session_status() === PHP_SESSION_ACTIVE ? 'Active' : 'NOT Active') . "</p>";
echo "<p><b>Session ID:</b> " . session_id() . "</p>";
echo "<p><b>Session Data:</b></p><pre>" . print_r($_SESSION, true) . "</pre>";

// 3. Check if logged in
$loggedIn = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
echo "<p><b>Logged In:</b> " . ($loggedIn ? 'YES' : 'NO - This is why you see a blank page! index.php redirects you back to signin.php') . "</p>";

// 4. Database connection test
echo "<h3>Database Test</h3>";
include 'db_connection.php';
if ($conn->connect_error) {
    echo "<p style='color:red;'><b>DB Connection FAILED:</b> " . $conn->connect_error . "</p>";
} else {
    echo "<p style='color:green;'><b>DB Connection:</b> OK</p>";
    
    // Check tables
    $tables = ['users', 'roles', 'customers', 'products', 'invoices', 'user_form_data'];
    foreach ($tables as $table) {
        $result = $conn->query("SHOW TABLES LIKE '$table'");
        $exists = ($result && $result->num_rows > 0) ? 'EXISTS' : 'MISSING';
        $color = $exists === 'EXISTS' ? 'green' : 'red';
        echo "<p><b>Table '$table':</b> <span style='color:$color;'>$exists</span></p>";
    }
    $conn->close();
}

// 5. Check key files exist
echo "<h3>File Check</h3>";
$files = ['index.php', 'signin.php', 'navbar.php', 'sidebar.php', 'functions.php', 'header.php', 'loader.php', 'css/styles.css', 'css/forms.css', 'js/scripts.js'];
foreach ($files as $file) {
    $exists = file_exists(__DIR__ . '/' . $file) ? 'OK' : 'MISSING';
    $color = $exists === 'OK' ? 'green' : 'red';
    echo "<p><b>$file:</b> <span style='color:$color;'>$exists</span></p>";
}

echo "<hr><p><i>Delete this file after debugging!</i></p>";
?>
