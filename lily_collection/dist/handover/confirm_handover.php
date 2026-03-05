<?php
// Start session
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit();
}

include($_SERVER['DOCUMENT_ROOT'] . '/lily_collection/dist/connection/db_connection.php');

// Read JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'message' => 'Invalid request data.']);
    exit();
}

$handover_id     = intval($input['handover_id'] ?? 0);
$new_produced    = intval($input['produced_quantity'] ?? 0);
$qty_to_produce  = intval($input['qty_to_produce'] ?? 0);

if ($handover_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid handover ID.']);
    exit();
}

if ($new_produced <= 0) {
    echo json_encode(['success' => false, 'message' => 'Produced quantity must be greater than 0.']);
    exit();
}

$response = ['success' => false, 'message' => ''];

try {
    // Start transaction
    $conn->begin_transaction();

    // Get handover record
    $hlgStmt = $conn->prepare("SELECT hl.*, p.name as product_name 
                                FROM handover_list hl 
                                LEFT JOIN products p ON hl.product_id = p.id 
                                WHERE hl.id = ? FOR UPDATE");
    $hlgStmt->bind_param("i", $handover_id);
    $hlgStmt->execute();
    $hlgResult = $hlgStmt->get_result();

    if ($hlgResult->num_rows === 0) {
        $conn->rollback();
        $response['message'] = 'Handover record not found.';
        echo json_encode($response);
        exit();
    }

    $hlg = $hlgResult->fetch_assoc();
    $hlgStmt->close();

    if ($hlg['status'] === 'completed') {
        $conn->rollback();
        $response['message'] = 'This handover has already been completed.';
        echo json_encode($response);
        exit();
    }

    // Use qty_to_produce from DB if not provided in request (fallback)
    $total_qty_to_produce = ($qty_to_produce > 0) ? $qty_to_produce : (int)$hlg['quantity_to_produce'];

    // Accumulated produced quantity
    $already_produced   = (int)$hlg['produced_quantity'];
    $total_produced_now = $already_produced + $new_produced;

    // Validate: new confirmed total must not exceed qty_to_produce
    if ($total_produced_now > $total_qty_to_produce) {
        $conn->rollback();
        $remaining_balance = $total_qty_to_produce - $already_produced;
        $response['message'] = "Produced Qty ($new_produced) exceeds remaining balance ($remaining_balance). Cannot confirm.";
        echo json_encode($response);
        exit();
    }

    // Determine new status
    $new_status = ($total_produced_now >= $total_qty_to_produce) ? 'completed' : 'remaining';

    $confirmed_by = $_SESSION['user_id'] ?? 0;

    // Update handover record — accumulate produced_quantity
    $updateStmt = $conn->prepare("UPDATE handover_list 
                                   SET produced_quantity = ?, status = ?, 
                                       confirmed_by = ?, confirmed_at = NOW() 
                                   WHERE id = ?");
    $updateStmt->bind_param("isii", $total_produced_now, $new_status, $confirmed_by, $handover_id);

    if (!$updateStmt->execute()) {
        throw new Exception("Failed to update handover record: " . $updateStmt->error);
    }
    $updateStmt->close();

    // Add newly produced quantity to product stock
    $stockStmt = $conn->prepare("UPDATE products SET stock_quantity = stock_quantity + ? WHERE id = ?");
    $stockStmt->bind_param("ii", $new_produced, $hlg['product_id']);

    if (!$stockStmt->execute()) {
        throw new Exception("Failed to update product stock: " . $stockStmt->error);
    }
    $stockStmt->close();

    $conn->commit();

    // Log the action
    if (isset($_SESSION['user_id'])) {
        $user_id     = $_SESSION['user_id'];
        $action_type = 'production_confirmed';
        $remaining   = $total_qty_to_produce - $total_produced_now;
        $details     = "Production confirmed - HLg ID: {$handover_id}, Product: {$hlg['product_name']}, "
                     . "New Produced: {$new_produced}, Total Produced: {$total_produced_now}, Status: {$new_status}";

        $logQuery = "INSERT INTO user_logs (user_id, action_type, inquiry_id, details) VALUES (?, ?, ?, ?)";
        $logStmt = $conn->prepare($logQuery);
        if ($logStmt) {
            $logStmt->bind_param("isis", $user_id, $action_type, $handover_id, $details);
            $logStmt->execute();
            $logStmt->close();
        }
    }

    $response['success'] = true;
    if ($new_status === 'completed') {
        $response['message'] = "Production completed! {$new_produced} units of '{$hlg['product_name']}' confirmed. Total produced: {$total_produced_now}.";
    } else {
        $remaining_balance = $total_qty_to_produce - $total_produced_now;
        $response['message'] = "{$new_produced} units confirmed. Remaining balance: {$remaining_balance} units. Status set to 'Remaining'.";
    }

} catch (Exception $e) {
    if (isset($conn) && $conn->ping()) {
        $conn->rollback();
    }
    error_log("Production confirmation error: " . $e->getMessage());
    $response['message'] = 'An error occurred while confirming production.';
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}

echo json_encode($response);
exit();
?>
