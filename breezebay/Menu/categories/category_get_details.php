<?php
include('../../include/dbconnection.php');

$response = null;
if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $con->prepare("SELECT ID, CategoryName, ParentCategoryID FROM tblcategory WHERE ID = ?");
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