<?php
include('../../include/dbconnection.php');

$sql = "SELECT p.ID, p.ProductName, p.Price, p.Type, p.Unit, p.Status, pc.ParentCategoryName, c.CategoryName as SubCategoryName 
        FROM tblproducts p 
        LEFT JOIN tblparentcategory pc ON p.ParentCategoryID = pc.ID 
        LEFT JOIN tblcategory c ON p.SubCategoryID = c.ID";
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