<?php
include('../../include/dbconnection.php');

$sql = "SELECT ID, ParentCategoryName FROM tblparentcategory ORDER BY ParentCategoryName ASC";
$result = mysqli_query($con, $sql);

$data = array();
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $data[] = $row;
    }
}

header('Content-Type: application/json');
echo json_encode($data);

mysqli_close($con);
?>