<?php
session_start();
include($_SERVER['DOCUMENT_ROOT'] . '/lily_collection/dist/connection/db_connection.php');

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$product_id = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;

if ($product_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid product ID']);
    exit();
}

$bomItems = [];
$bomQuery = $conn->prepare("SELECT pm.*, m.name as material_name, m.material_code 
                            FROM product_materials pm 
                            LEFT JOIN material m ON pm.material_id = m.id 
                            WHERE pm.product_id = ? ORDER BY pm.id ASC");
$bomQuery->bind_param("i", $product_id);
if ($bomQuery->execute()) {
    $bomResult = $bomQuery->get_result();
    while ($b = $bomResult->fetch_assoc()) {
        $bomItems[] = [
            'id' => $b['id'],
            'material_id' => $b['material_id'],
            'material_name' => $b['material_name'],
            'material_code' => $b['material_code'],
            'quantity' => (float)$b['quantity_required']
        ];
    }
    echo json_encode(['success' => true, 'data' => $bomItems]);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
$bomQuery->close();
?>
