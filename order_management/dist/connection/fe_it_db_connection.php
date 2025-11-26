<?php
$servername = "gator4423.hostgator.com";
$username = "imwijqte_db";
$password = "imwijqte_db2025a";
$dbname = "imwijqte_feit_db";

$fe_conn = new mysqli($fe_servername, $fe_username, $fe_password, $fe_dbname);

// Check connection
if ($fe_conn->connect_error) {
    die("FE IT DB Connection failed: " . $fe_conn->connect_error);
}
?>
