<?php
session_start();
include('../include/dbconnection.php');

if (empty($_SESSION['uid'])) {
    header('location:../auth/login.php');
    exit;
}
mysqli_set_charset($con, "utf8mb4");

$sql = "SELECT 
            p.ID as ProductID, 
            p.ProductName, 
            p.Quantity, 
            c.CategoryName as SubCategoryName
        FROM 
            tblproducts p
        LEFT JOIN 
            tblcategory c ON p.SubCategoryID = c.ID
        WHERE 
            p.Type = 'Countable'";

$result = mysqli_query($con, $sql);

$data = array();
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $data[] = $row;
    }
}

header('Content-Type: application/json');
echo json_encode(array('data' => $data));

mysqli_close($con);
?>