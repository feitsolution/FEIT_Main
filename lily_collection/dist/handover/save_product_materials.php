<?php
// Start session
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit();
}

include($_SERVER['DOCUMENT_ROOT'] . '/lily_collection/dist/connection/db_connection.php');

// Verify CSRF token
if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    echo json_encode(['success' => false, 'message' => 'Security token mismatch.']);
    exit();
}

$response = ['success' => false, 'message' => ''];

try {
    $product_id = intval($_POST['product_id'] ?? 0);
    $bomItems = $_POST['bom'] ?? [];

    if ($product_id <= 0) {
        $response['message'] = 'Invalid product ID.';
        echo json_encode($response);
        exit();
    }

    /* 
    if (empty($bomItems)) {
        $response['message'] = 'At least one material is required.';
        echo json_encode($response);
        exit();
    }
    */

    // Validate items
    foreach ($bomItems as $item) {
        $material_id = intval($item['material_id'] ?? 0);
        $qty = intval($item['quantity_required'] ?? 0);
        if ($material_id <= 0 || $qty <= 0) {
            $response['message'] = 'Invalid material or quantity in BOM items.';
            echo json_encode($response);
            exit();
        }
    }

    // Start transaction
    $conn->begin_transaction();

    // 1. Get existing IDs for this product to determine what to delete
    $existingIds = [];
    $getIdStmt = $conn->prepare("SELECT id FROM product_materials WHERE product_id = ?");
    $getIdStmt->bind_param("i", $product_id);
    $getIdStmt->execute();
    $idResult = $getIdStmt->get_result();
    while ($row = $idResult->fetch_assoc()) {
        $existingIds[] = (int)$row['id'];
    }
    $getIdStmt->close();

    $submittedIds = [];
    $insertStmt = $conn->prepare("INSERT INTO product_materials (product_id, material_id, quantity_required) VALUES (?, ?, ?)");
    $updateStmt = $conn->prepare("UPDATE product_materials SET material_id = ?, quantity_required = ? WHERE id = ? AND product_id = ?");

    foreach ($bomItems as $item) {
        $id = intval($item['id'] ?? 0);
        $material_id = intval($item['material_id']);
        $qty = floatval($item['quantity_required']); // Allow decimals for BOM quantity

        if ($id > 0) {
            // Update existing record
            $updateStmt->bind_param("idii", $material_id, $qty, $id, $product_id);
            if (!$updateStmt->execute()) {
                throw new Exception("Failed to update BOM item ID {$id}: " . $updateStmt->error);
            }
            $submittedIds[] = $id;
        } else {
            // Insert new record
            $insertStmt->bind_param("iid", $product_id, $material_id, $qty);
            if (!$insertStmt->execute()) {
                throw new Exception("Failed to insert new BOM item: " . $insertStmt->error);
            }
        }
    }
    $updateStmt->close();
    $insertStmt->close();

    // 2. Delete IDs that were not submitted
    $toDelete = array_diff($existingIds, $submittedIds);
    if (!empty($toDelete)) {
        $idsToDelete = implode(',', array_map('intval', $toDelete));
        $deleteQuery = "DELETE FROM product_materials WHERE id IN ($idsToDelete) AND product_id = ?";
        $deleteStmt = $conn->prepare($deleteQuery);
        $deleteStmt->bind_param("i", $product_id);
        if (!$deleteStmt->execute()) {
            throw new Exception("Failed to delete removed BOM items: " . $deleteStmt->error);
        }
        $deleteStmt->close();
    }

    $conn->commit();

    // Log the action
    if (isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];
        $action_type = 'bom_update';
        $details = "BOM updated for product ID: {$product_id}, Items: " . count($bomItems);

        $logQuery = "INSERT INTO user_logs (user_id, action_type, inquiry_id, details) VALUES (?, ?, ?, ?)";
        $logStmt = $conn->prepare($logQuery);
        if ($logStmt) {
            $logStmt->bind_param("isis", $user_id, $action_type, $product_id, $details);
            $logStmt->execute();
            $logStmt->close();
        }
    }

    $response['success'] = true;
    $response['message'] = 'BOM saved successfully for the selected product!';

} catch (Exception $e) {
    if (isset($conn) && $conn->ping()) {
        $conn->rollback();
    }
    error_log("BOM save error: " . $e->getMessage());
    $response['message'] = 'An error occurred while saving the BOM.';
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}

echo json_encode($response);
exit();
?>
