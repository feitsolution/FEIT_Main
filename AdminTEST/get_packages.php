<?php
// get_packages.php
include 'db_connection.php';

$response = [];

if (isset($_GET['product_id'])) {
    $product_id = intval($_GET['product_id']);
    
    $sql = "SELECT id, description, amount FROM packages WHERE product_id = ? AND status = 'active' ORDER BY id ASC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $response[] = $row;
    }
    
    $stmt->close();
}

header('Content-Type: application/json');
echo json_encode($response);

$conn->close();
?>
