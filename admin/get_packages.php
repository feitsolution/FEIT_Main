<?php
// get_packages.php
include 'db_connection.php';

$response = [];

if (isset($_GET['product_id'])) {
    $product_id = intval($_GET['product_id']);
    $customer_id = isset($_GET['customer_id']) ? intval($_GET['customer_id']) : null;
    
    if ($customer_id) {
        $sql = "SELECT p.id, p.description, p.amount as default_amount, p.max_count as default_max_count, 
                       cp.amount as custom_amount, cp.max_count as custom_max_count 
                FROM packages p 
                LEFT JOIN customer_packages cp ON p.id = cp.package_id AND cp.customer_id = ?
                WHERE p.product_id = ? AND p.status = 'active' 
                ORDER BY p.id ASC";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $customer_id, $product_id);
    } else {
        $sql = "SELECT id, description, amount as default_amount, max_count as default_max_count FROM packages WHERE product_id = ? AND status = 'active' ORDER BY id ASC";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $product_id);
    }
    
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
