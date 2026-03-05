<?php
session_start();
include($_SERVER['DOCUMENT_ROOT'] . '/lily_collection/dist/connection/db_connection.php');

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$handover_id = isset($_GET['handover_id']) ? intval($_GET['handover_id']) : 0;

if ($handover_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid handover ID']);
    exit();
}

$items = [];
$query = $conn->prepare("SELECT mhi.*, m.name as material_name, m.material_code 
                        FROM material_handover_items mhi 
                        LEFT JOIN material m ON mhi.material_id = m.id 
                        WHERE mhi.handover_id = ? ORDER BY m.name ASC");
if ($query) {
    $query->bind_param("i", $handover_id);
    if ($query->execute()) {
        $result = $query->get_result();
        while ($row = $result->fetch_assoc()) {
            $items[] = [
                'material_name' => $row['material_name'] ?? 'Unknown material',
                'material_code' => $row['material_code'] ?? 'N/A',
                'quantity' => (float)$row['quantity']
            ];
        }
        echo json_encode(['success' => true, 'data' => $items]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $query->error]);
    }
    $query->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to prepare query: ' . $conn->error]);
}
?>
