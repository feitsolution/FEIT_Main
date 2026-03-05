<?php
// Start session
session_start();

// Set content type for JSON response
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
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

if ($material_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid material ID.']);
    exit();
}

try {
    // Get current status
    $stmt = $conn->prepare("SELECT name, status FROM Material WHERE id = ?");
    $stmt->bind_param("i", $material_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Material not found.']);
        exit();
    }
    
    $material = $result->fetch_assoc();
    $current_status = $material['status'];
    $new_status = ($current_status === 'active') ? 'inactive' : 'active';
    $stmt->close();

    // Update status
    $updateStmt = $conn->prepare("UPDATE Material SET status = ?, updated_at = NOW() WHERE id = ?");
    $updateStmt->bind_param("si", $new_status, $material_id);
    
    if ($updateStmt->execute()) {
        // Log the action
        if (isset($_SESSION['user_id'])) {
            $user_id = $_SESSION['user_id'];
            $action_type = 'material_status_toggle';
            $details = "Material status toggled - Name: {$material['name']}, Old Status: {$current_status}, New Status: {$new_status}";
            
            $logQuery = "INSERT INTO user_logs (user_id, action_type, inquiry_id, details) VALUES (?, ?, ?, ?)";
            $logStmt = $conn->prepare($logQuery);
            if ($logStmt) {
                $logStmt->bind_param("isis", $user_id, $action_type, $material_id, $details);
                $logStmt->execute();
                $logStmt->close();
            }
        }
        
        echo json_encode(['success' => true, 'message' => "Material '{$material['name']}' is now {$new_status}.", 'new_status' => $new_status]);
    } else {
        throw new Exception("Failed to update status: " . $updateStmt->error);
    }
    
    $updateStmt->close();

} catch (Exception $e) {
    error_log("Material status toggle error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred while toggling status.']);
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>
