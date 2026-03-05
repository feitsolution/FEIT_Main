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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit();
}

// Include database connection
include($_SERVER['DOCUMENT_ROOT'] . '/lily_collection/dist/connection/db_connection.php');

// Verify CSRF token
if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    echo json_encode(['success' => false, 'message' => 'Security token mismatch. Please refresh the page.']);
    exit();
}

$response = ['success' => false, 'message' => '', 'errors' => []];

function sanitizeInput($input) {
    return trim(htmlspecialchars($input, ENT_QUOTES, 'UTF-8'));
}

try {
$material_id = intval($_POST['material_id'] ?? 0);
    $material_code = sanitizeInput($_POST['material_code'] ?? '');
    $name = sanitizeInput($_POST['name'] ?? '');
    $stock_quantity = intval($_POST['stock_quantity'] ?? 0);
    $status = sanitizeInput($_POST['status'] ?? 'active');
    $low_stock_threshold = isset($_POST['low_stock_threshold']) ? intval($_POST['low_stock_threshold']) : null;

    // Validate stock quantity
    if ($stock_quantity < 0) {
        $response['message'] = 'Stock quantity cannot be negative.';
        echo json_encode($response);
        exit();
    }

// Validate low stock threshold
    if ($low_stock_threshold === null || $_POST['low_stock_threshold'] === '') {
        $response['errors']['low_stock_threshold'] = 'Low stock threshold is required.';
        echo json_encode($response);
        exit();
    }

    if ($low_stock_threshold < 0) {
        $response['errors']['low_stock_threshold'] = 'Low stock threshold cannot be negative.';
        echo json_encode($response);
        exit();
    }

    if ($material_id <= 0) {
        $response['message'] = 'Invalid material ID.';
        echo json_encode($response);
        exit();
    }

    // Check if material exists and get original data
    $checkQuery = "SELECT * FROM material WHERE id = ? LIMIT 1";
    $checkStmt = $conn->prepare($checkQuery);
    $checkStmt->bind_param("i", $material_id);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();

    if ($checkResult->num_rows === 0) {
        $response['message'] = 'material not found.';
        echo json_encode($response);
        exit();
    }

    $originalmaterial = $checkResult->fetch_assoc();
    $checkStmt->close();

    if (empty($material_code)) {
        $response['message'] = 'material code is required.';
        echo json_encode($response);
        exit();
    }

    if (empty($name)) {
        $response['message'] = 'material name is required.';
        echo json_encode($response);
        exit();
    }

    // Check for duplicate code
    $checkQuery = "SELECT id FROM material WHERE material_code = ? AND id != ? LIMIT 1";
    $checkStmt = $conn->prepare($checkQuery);
    $checkStmt->bind_param("si", $material_code, $material_id);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();

    if ($checkResult->num_rows > 0) {
        $response['message'] = 'Another material with this code already exists.';
        echo json_encode($response);
        exit();
    }
    $checkStmt->close();

    // Update material
    $updateQuery = "UPDATE material SET material_code = ?, name = ?, stock_quantity = ?, status = ?, low_stock_threshold = ?, updated_at = NOW() WHERE id = ?";
    $updateStmt = $conn->prepare($updateQuery);
    $updateStmt->bind_param("ssisii", $material_code, $name, $stock_quantity, $status, $low_stock_threshold, $material_id);

    if ($updateStmt->execute()) {
        // Log the action
        if (isset($_SESSION['user_id'])) {
            $user_id = $_SESSION['user_id'];
            $action_type = 'material_update';
            $changes = [];

            if ($originalmaterial['material_code'] !== $material_code) {
                $changes[] = "Code: '{$originalmaterial['material_code']}' to '{$material_code}'";
            }
            if ($originalmaterial['name'] !== $name) {
                $changes[] = "Name: '{$originalmaterial['name']}' to '{$name}'";
            }
            if (intval($originalmaterial['stock_quantity']) !== $stock_quantity) {
                $changes[] = "Stock: {$originalmaterial['stock_quantity']} to {$stock_quantity}";
            }
            if ($originalmaterial['status'] !== $status) {
                $changes[] = "Status: '{$originalmaterial['status']}' to '{$status}'";
            }
            if (intval($originalmaterial['low_stock_threshold'] ?? 10) !== $low_stock_threshold) {
                $changes[] = "Threshold: {$originalmaterial['low_stock_threshold']} to {$low_stock_threshold}";
            }

            $details = empty($changes)
                ? "material update attempted (no changes detected)"
                : "material updated - " . implode(', ', $changes);

            $logQuery = "INSERT INTO user_logs (user_id, action_type, inquiry_id, details) VALUES (?, ?, ?, ?)";
            $logStmt = $conn->prepare($logQuery);
            if ($logStmt) {
                $logStmt->bind_param("isis", $user_id, $action_type, $material_id, $details);
                $logStmt->execute();
                $logStmt->close();
            }
        }

        $updateStmt->close();
        $response['success'] = true;
        $response['message'] = "material '{$name}' has been successfully updated!";
    } else {
        throw new Exception("Database execution error: " . $updateStmt->error);
    }

} catch (Exception $e) {
    error_log("material update error: " . $e->getMessage());
    $response['message'] = 'An error occurred while updating the material.';
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}

echo json_encode($response);
exit();
?>
