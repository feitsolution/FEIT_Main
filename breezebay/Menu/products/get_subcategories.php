<?php
include('../../include/dbconnection.php');

$parent_id = isset($_GET['parent_id']) ? intval($_GET['parent_id']) : 0;
$data = array();

if ($parent_id > 0) {
    // Assuming tblcategory has a column ParentCategoryID
    $stmt = $con->prepare("SELECT ID, CategoryName FROM tblcategory WHERE ParentCategoryID = ? ORDER BY CategoryName ASC"); 
    if ($stmt) {
        $stmt->bind_param("i", $parent_id);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        $stmt->close();
    }
}

echo json_encode($data);
?>