<?php
// Start session at the very beginning
session_start();

// Set content type for JSON response
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized access. Please log in again.'
    ]);
    exit();
}

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method.'
    ]);
    exit();
}

// Include database connection
include($_SERVER['DOCUMENT_ROOT'] . '/lily_collection/dist/connection/db_connection.php');

// Verify CSRF token
if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    echo json_encode([
        'success' => false,
        'message' => 'Security token mismatch. Please refresh the page and try again.'
    ]);
    exit();
}

// Initialize response array
$response = [
    'success' => false,
    'message' => '',
    'errors' => []
];

// Function to sanitize input
function sanitizeInput($input) {
    return trim(htmlspecialchars($input, ENT_QUOTES, 'UTF-8'));
}

try {
// Get and sanitize form data
    $material_code = sanitizeInput($_POST['material_code'] ?? '');
    $name = sanitizeInput($_POST['name'] ?? '');
    $stock_quantity = intval($_POST['stock_quantity'] ?? 0);
    $low_stock_threshold = isset($_POST['low_stock_threshold']) ? intval($_POST['low_stock_threshold']) : null;
    $status = sanitizeInput($_POST['status'] ?? 'active');

    // Validate low stock threshold (required)
    if ($low_stock_threshold === null || $_POST['low_stock_threshold'] === '') {
        $response['errors']['low_stock_threshold'] = 'Low stock threshold is required';
        echo json_encode($response);
        exit();
    }

    if ($low_stock_threshold < 0) {
        $response['errors']['low_stock_threshold'] = 'Low stock threshold cannot be negative';
        echo json_encode($response);
        exit();
    }

    // Validation
    if (empty($material_code)) {
        $response['message'] = 'Material code is required.';
        echo json_encode($response);
        exit();
    }

    if (empty($name)) {
        $response['message'] = 'Material name is required.';
        echo json_encode($response);
        exit();
    }

    // Check for duplicate material code
    $checkQuery = "SELECT id FROM Material WHERE material_code = ? LIMIT 1";
    $checkStmt = $conn->prepare($checkQuery);
    $checkStmt->bind_param("s", $material_code);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();

    if ($checkResult->num_rows > 0) {
        $response['message'] = 'A material with this code already exists.';
        echo json_encode($response);
        exit();
    }
    $checkStmt->close();

    // Prepare insert query
    $insertQuery = "INSERT INTO Material (material_code, name, stock_quantity, low_stock_threshold, status) VALUES (?, ?, ?, ?, ?)";
    $insertStmt = $conn->prepare($insertQuery);

    if (!$insertStmt) {
        throw new Exception("Database prepare error: " . $conn->error);
    }

    $insertStmt->bind_param("sssis", $material_code, $name, $stock_quantity, $low_stock_threshold, $status);

    if ($insertStmt->execute()) {
        $material_id = $conn->insert_id;

        // Log the action
        if (isset($_SESSION['user_id'])) {
            $user_id = $_SESSION['user_id'];
            $action_type = 'material_create';
            $details = "New material created - Code: {$material_code}, Name: {$name}, Stock: {$stock_quantity}, Threshold: {$low_stock_threshold}, Status: {$status}";

            $logQuery = "INSERT INTO user_logs (user_id, action_type, inquiry_id, details) VALUES (?, ?, ?, ?)";
            $logStmt = $conn->prepare($logQuery);

            if ($logStmt) {
                $logStmt->bind_param("isis", $user_id, $action_type, $material_id, $details);
                $logStmt->execute();
                $logStmt->close();
            }
        }

        $insertStmt->close();

        $response['success'] = true;
        $response['message'] = "Material '{$name}' has been successfully added!";
        $response['material_id'] = $material_id;

    } else {
        throw new Exception("Database execution error: " . $insertStmt->error);
    }

} catch (Exception $e) {
    error_log("Material creation error: " . $e->getMessage());
    $response['success'] = false;
    $response['message'] = 'An error occurred while adding the material. Please try again.';
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}

echo json_encode($response);
exit();
?>
