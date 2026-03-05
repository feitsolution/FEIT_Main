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

// Function to sanitize input
function sanitizeInput($input) {
    return trim(htmlspecialchars($input, ENT_QUOTES, 'UTF-8'));
}

try {
    $product_id = intval($_POST['product_id'] ?? 0);
    $quantity_to_produce = intval($_POST['quantity_to_produce'] ?? 0);
    $created_by = $_SESSION['user_id'] ?? 0;

    $handover_date = sanitizeInput($_POST['handover_date'] ?? '');
    $handover_to = intval($_POST['handover_to'] ?? 0);
    $notes = sanitizeInput($_POST['notes'] ?? '');

    if ($product_id <= 0) {
        $response['message'] = 'Please select a product.';
        echo json_encode($response);
        exit();
    }

    if ($quantity_to_produce <= 0) {
        $response['message'] = 'Quantity to produce must be greater than 0.';
        echo json_encode($response);
        exit();
    }

    if (empty($handover_date) || $handover_to <= 0) {
        $response['message'] = 'Handover date and recipient are required.';
        echo json_encode($response);
        exit();
    }

    // Start transaction
    $conn->begin_transaction();

    // Fetch BOM for the product
    $bomStmt = $conn->prepare("SELECT pm.material_id, pm.quantity_required, m.name as material_name 
                                FROM product_materials pm 
                                LEFT JOIN material m ON pm.material_id = m.id 
                                WHERE pm.product_id = ?");
    $bomStmt->bind_param("i", $product_id);
    $bomStmt->execute();
    $bomResult = $bomStmt->get_result();
    
    $bomItems = [];
    while ($b = $bomResult->fetch_assoc()) {
        $bomItems[] = $b;
    }
    $bomStmt->close();

    if (empty($bomItems)) {
        $conn->rollback();
        $response['message'] = 'No BOM defined for this product. Please set up Product materials first.';
        echo json_encode($response);
        exit();
    }

    // Check stock availability and prepare for deduction
    $materialsToDeduct = [];
    foreach ($bomItems as $bom) {
        $totalRequired = $bom['quantity_required'] * $quantity_to_produce;
        $matId = $bom['material_id'];

        // Get current stock from material table with FOR UPDATE to prevent race conditions
        $stockStmt = $conn->prepare("SELECT stock_quantity FROM material WHERE id = ? FOR UPDATE");
        $stockStmt->bind_param("i", $matId);
        $stockStmt->execute();
        $stockResult = $stockStmt->get_result();
        
        if ($stockResult->num_rows === 0) {
            $conn->rollback();
            $response['message'] = "material '{$bom['material_name']}' no longer exists.";
            echo json_encode($response);
            exit();
        }

        $currentStock = (int)$stockResult->fetch_assoc()['stock_quantity'];
        $stockStmt->close();

        if ($totalRequired > $currentStock) {
            $conn->rollback();
            $response['message'] = "Insufficient stock for '{$bom['material_name']}'. Available: {$currentStock}, Required: {$totalRequired}";
            echo json_encode($response);
            exit();
        }

        $materialsToDeduct[] = [
            'id' => $matId,
            'qty' => $totalRequired
        ];
    }

    // Insert Handover Header
    $headerStmt = $conn->prepare("INSERT INTO material_handover_header (handover_date, created_by, handover_to, notes) VALUES (?, ?, ?, ?)");
    $headerStmt->bind_param("siis", $handover_date, $created_by, $handover_to, $notes);
    if (!$headerStmt->execute()) {
        throw new Exception("Failed to create handover header: " . $headerStmt->error);
    }
    $handover_id = $conn->insert_id;
    $headerStmt->close();

    // Insert Handover Items & Deduct Stock
    $itemStmt = $conn->prepare("INSERT INTO material_handover_items (handover_id, material_id, quantity) VALUES (?, ?, ?)");
    $deductStmt = $conn->prepare("UPDATE material SET stock_quantity = stock_quantity - ?, updated_at = NOW() WHERE id = ?");

    foreach ($materialsToDeduct as $mat) {
        // Insert item
        $itemStmt->bind_param("iii", $handover_id, $mat['id'], $mat['qty']);
        if (!$itemStmt->execute()) {
            throw new Exception("Failed to insert handover item: " . $itemStmt->error);
        }

        // Deduct stock
        $deductStmt->bind_param("ii", $mat['qty'], $mat['id']);
        if (!$deductStmt->execute()) {
            throw new Exception("Failed to deduct stock for material ID {$mat['id']}: " . $deductStmt->error);
        }
    }
    $itemStmt->close();
    $deductStmt->close();

    // Insert handover record
    $insertStmt = $conn->prepare("INSERT INTO handover_list (product_id, handover_id, quantity_to_produce, status, produced_quantity, created_by) 
                                   VALUES (?, ?, ?, 'pending', 0, ?)");
    $insertStmt->bind_param("iiii", $product_id, $handover_id, $quantity_to_produce, $created_by);

    if (!$insertStmt->execute()) {
        throw new Exception("Failed to create handover record: " . $insertStmt->error);
    }

    $handover_id = $conn->insert_id;
    $insertStmt->close();

    $conn->commit();

    // Log the action
    if (isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];
        $action_type = 'handover_create';
        $details = "Handover created - Product ID: {$product_id}, Qty: {$quantity_to_produce}";

        $logQuery = "INSERT INTO user_logs (user_id, action_type, inquiry_id, details) VALUES (?, ?, ?, ?)";
        $logStmt = $conn->prepare($logQuery);
        if ($logStmt) {
            $logStmt->bind_param("isis", $user_id, $action_type, $handover_id, $details);
            $logStmt->execute();
            $logStmt->close();
        }
    }

    $response['success'] = true;
    $response['message'] = "Handover created successfully! Qty: {$quantity_to_produce}";
    $response['handover_id'] = $handover_id;

} catch (Exception $e) {
    if (isset($conn) && $conn->ping()) {
        $conn->rollback();
    }
    error_log("Handover creation error: " . $e->getMessage());
    $response['message'] = 'An error occurred while creating the handover.';
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}

echo json_encode($response);
exit();
?>
