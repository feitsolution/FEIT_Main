<?php
// TEMPORARY DEBUG FILE - DELETE AFTER FIXING
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Step-by-step index.php debug</h2>";

// Step 1: Session
echo "<p>1. session_start... ";
session_start();
echo "<b style='color:green'>OK</b></p>";

// Step 2: Session check
echo "<p>2. Logged in: " . (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true ? '<b style="color:green">YES</b>' : '<b style="color:red">NO</b>') . "</p>";

// Step 3: DB Connection
echo "<p>3. db_connection.php... ";
include 'db_connection.php';
echo "<b style='color:green'>OK</b></p>";

// Step 4: Functions
echo "<p>4. functions.php... ";
include 'functions.php';
echo "<b style='color:green'>OK</b></p>";

// Step 5: Role checks
echo "<p>5. Role checks... ";
$current_user_role = isset($_SESSION['role_id']) ? (int)$_SESSION['role_id'] : 0;
$isAdmin = ($current_user_role === 1);
echo "Role: $current_user_role, isAdmin: " . ($isAdmin ? 'YES' : 'NO') . " <b style='color:green'>OK</b></p>";

// Step 6: DB Queries
echo "<p>6. DB queries... ";
try {
    $result = $conn->query("SELECT COUNT(*) as count FROM users");
    if ($result) {
        $row = $result->fetch_assoc();
        echo "Users: " . $row['count'] . " ";
    }
    echo "<b style='color:green'>OK</b></p>";
} catch (Exception $e) {
    echo "<b style='color:red'>FAILED: " . $e->getMessage() . "</b></p>";
}

// Step 7: Header include
echo "<p>7. header.php... ";
ob_start();
include 'header.php';
ob_end_clean();
echo "<b style='color:green'>OK</b></p>";

// Step 8: Navbar include
echo "<p>8. navbar.php... ";
ob_start();
include 'navbar.php';
$navContent = ob_get_clean();
echo "<b style='color:green'>OK</b> (" . strlen($navContent) . " bytes)</p>";

// Step 9: Sidebar include  
echo "<p>9. sidebar.php... ";
ob_start();
include 'sidebar.php';
$sideContent = ob_get_clean();
echo "<b style='color:green'>OK</b> (" . strlen($sideContent) . " bytes)</p>";

// Step 10: Check forms.css
echo "<p>10. css/forms.css exists: " . (file_exists('css/forms.css') ? '<b style="color:green">YES</b>' : '<b style="color:red">NO</b>') . "</p>";

// Step 11: Check PHP memory/limits
echo "<h3>Server Config</h3>";
echo "<p>output_buffering: " . ini_get('output_buffering') . "</p>";
echo "<p>memory_limit: " . ini_get('memory_limit') . "</p>";
echo "<p>max_execution_time: " . ini_get('max_execution_time') . "</p>";
echo "<p>session.save_path: " . ini_get('session.save_path') . "</p>";
echo "<p>session.cookie_path: " . ini_get('session.cookie_path') . "</p>";

echo "<hr><p>All steps passed! If index.php still shows white, the issue is in the HTML output itself.</p>";
echo "<p><i>Delete this file after debugging!</i></p>";

$conn->close();
?>