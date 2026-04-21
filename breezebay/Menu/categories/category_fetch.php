<?php
include('../../include/dbconnection.php');

$sql = "SELECT c.ID, c.CategoryName, c.Status, p.ParentCategoryName, c.ParentCategoryID 
        FROM tblcategory as c 
        LEFT JOIN tblparentcategory as p ON c.ParentCategoryID = p.ID";
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