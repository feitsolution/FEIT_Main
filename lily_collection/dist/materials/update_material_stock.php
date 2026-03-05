<?php
// Start session at the very beginning
session_start();

// Set content type for JSON response
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit();
}

// Include database connection
include($_SERVER['DOCUMENT_ROOT'] . '/lily_collection/dist/connection/db_connection.php');

// Read JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'message' => 'Invalid request data.']);
    exit();
}

$material_id = intval($input['material_id'] ?? 0);
$operation = $input['operation'] ?? '';
$adjustment_value = intval($input['adjustment_value'] ?? 0);

// Validation
if ($material_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid material ID.']);
    exit();
}

if (!in_array($operation, ['increase', 'decrease'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid operation type.']);
    exit();
}

if ($adjustment_value <= 0) {
    echo json_encode(['success' => false, 'message' => 'Adjustment value must be greater than 0.']);
    exit();
}

try {
    // Get current stock
    $stmt = $conn->prepare("SELECT stock_quantity, name FROM material WHERE id = ?");
    $stmt->bind_param("i", $material_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'material not found.']);
        exit();
    }
    
    $material = $result->fetch_assoc();
    $current_stock = (int)$material['stock_quantity'];
    $stmt->close();

    if ($operation === 'decrease' && $adjustment_value > $current_stock) {
        echo json_encode(['success' => false, 'message' => 'Cannot decrease more than current stock (' . $current_stock . ').']);
        exit();
    }

    // Calculate new stock
    $new_stock = ($operation === 'increase') ? $current_stock + $adjustment_value : $current_stock - $adjustment_value;

    // Update stock
    $updateStmt = $conn->prepare("UPDATE material SET stock_quantity = ?, updated_at = NOW() WHERE id = ?");
    $updateStmt->bind_param("ii", $new_stock, $material_id);
    
    if ($updateStmt->execute()) {
        // Log the action
        if (isset($_SESSION['user_id'])) {
            $user_id = $_SESSION['user_id'];
            $action_type = 'material_stock_update';
            $details = "material stock updated - Name: {$material['name']}, Operation: {$operation}, Qty: {$adjustment_value}, Old Stock: {$current_stock}, New Stock: {$new_stock}";
            
            $logQuery = "INSERT INTO user_logs (user_id, action_type, inquiry_id, details) VALUES (?, ?, ?, ?)";
            $logStmt = $conn->prepare($logQuery);
            if ($logStmt) {
                $logStmt->bind_param("isis", $user_id, $action_type, $material_id, $details);
                $logStmt->execute();
                $logStmt->close();
            }
        }
        
        echo json_encode(['success' => true, 'message' => 'Stock updated successfully.', 'new_stock' => $new_stock]);
    } else {
        throw new Exception("Failed to update stock: " . $updateStmt->error);
    }
    
    $updateStmt->close();

} catch (Exception $e) {
    error_log("material stock update error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred while updating stock.']);
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>
