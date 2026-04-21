<?php
session_start();
include('../include/dbconnection.php');

if (empty($_SESSION['uid'])) {
    header('location:../auth/login.php');
    exit;
}
mysqli_set_charset($con, "utf8mb4");

$response = null;
if (isset($_GET['id'])) {
    $id = intval($_GET['id']); // This is ProductID
    
    // Fetch product and stock details
    $stmt = $con->prepare("
        SELECT 
            p.ID as ProductID, 
            p.ProductName, 
            p.Quantity
        FROM 
            tblproducts p
        WHERE 
            p.ID = ? AND p.Type = 'Countable'
    ");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $response = $result->fetch_assoc();
    }
    $stmt->close();
}

header('Content-Type: application/json');
echo json_encode($response);
mysqli_close($con);
?>