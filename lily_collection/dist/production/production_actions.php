<?php
session_start();
// Enable error reporting for debugging if needed, but keep it JSON friendly
// error_reporting(E_ALL);
// ini_set('display_errors', 0);
header('Content-Type: application/json');

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

include($_SERVER['DOCUMENT_ROOT'] . '/lily_collection/dist/connection/db_connection.php');

$action = $_POST['action'] ?? '';

if ($action === 'start_production') {
    $product_id = (int)$_POST['product_id'];
    $quantity = (int)$_POST['quantity'];
    $user_id = $_SESSION['user_id'] ?? 0;
    
    if ($product_id <= 0 || $quantity <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid data. Quantity is required.']);
        exit();
    }

    // Fetch product code for reference number
    $p_res = $conn->query("SELECT product_code FROM products WHERE id = $product_id");
    $product_code = 'PROD';
    if($p_res && $p = $p_res->fetch_assoc()) {
        $product_code = $p['product_code'];
    }
    
    // Generate unique reference number: productcode-ymd-his
    $reference_no = $product_code . '-' . date('ymd-His');
    
    $stmt = $conn->prepare("INSERT INTO production_batches (product_id, quantity, reference_no, current_stage, action_by, status) VALUES (?, ?, ?, 'Cutting', ?, 'Active')");
    $stmt->bind_param("iisi", $product_id, $quantity, $reference_no, $user_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'reference_no' => $reference_no]);
    } else {
        echo json_encode(['success' => false, 'message' => $conn->error]);
    }
} 
elseif ($action === 'transition_stage') {
    $batch_id = (int)$_POST['batch_id'];
    $next_stage = $_POST['next_stage'];
    $transfer_qty = (int)$_POST['quantity'];
    $user_id = $_SESSION['user_id'] ?? 0;
    
    $valid_stages = ['Cutting', 'Sewing', 'Finishing', 'Packing'];
    if (!in_array($next_stage, $valid_stages)) {
        echo json_encode(['success' => false, 'message' => 'Invalid stage']);
        exit();
    }

    if ($transfer_qty <= 0) {
        echo json_encode(['success' => false, 'message' => 'Quantity must be greater than zero']);
        exit();
    }
    
    $conn->begin_transaction();
    try {
        // Check current batch
        $res = $conn->query("SELECT product_id, quantity, reference_no, current_stage FROM production_batches WHERE id = $batch_id AND status = 'Active'");
        if ($res && $batch = $res->fetch_assoc()) {
            if ($transfer_qty > $batch['quantity']) {
                throw new Exception("Transfer quantity exceeds current batch quantity");
            }
            
            // Subtract from current batch
            $new_qty = $batch['quantity'] - $transfer_qty;
            if ($new_qty == 0) {
                $conn->query("UPDATE production_batches SET quantity = 0, status = 'Completed' WHERE id = $batch_id");
            } else {
                $conn->query("UPDATE production_batches SET quantity = $new_qty WHERE id = $batch_id");
            }
            
            // Create new batch in next stage
            $stmt = $conn->prepare("INSERT INTO production_batches (product_id, quantity, reference_no, current_stage, action_by, status) VALUES (?, ?, ?, ?, ?, 'Active')");
            $stmt->bind_param("iissi", $batch['product_id'], $transfer_qty, $batch['reference_no'], $next_stage, $user_id);
            $stmt->execute();
            
            $conn->commit();
            echo json_encode(['success' => true]);
        } else {
            throw new Exception("Batch not found or already completed");
        }
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
elseif ($action === 'complete_packing') {
    $batch_id = (int)$_POST['batch_id'];
    $transfer_qty = (int)$_POST['quantity'];
    
    if ($transfer_qty <= 0) {
        echo json_encode(['success' => false, 'message' => 'Quantity must be greater than zero']);
        exit();
    }

    $conn->begin_transaction();
    
    try {
        // Get batch info
        $res = $conn->query("SELECT product_id, quantity FROM production_batches WHERE id = $batch_id AND current_stage = 'Packing' AND status = 'Active'");
        if ($res && $batch = $res->fetch_assoc()) {
            if ($transfer_qty > $batch['quantity']) {
                throw new Exception("Completion quantity exceeds current batch quantity");
            }

            $product_id = $batch['product_id'];
            
            // Update stock
            $conn->query("UPDATE products SET stock_quantity = stock_quantity + $transfer_qty WHERE id = $product_id");
            
            // Record completed quantity in the packing batch
            $conn->query("UPDATE production_batches SET completed_qty = completed_qty + $transfer_qty WHERE id = $batch_id");

            // Subtract from packing batch
            $new_qty = $batch['quantity'] - $transfer_qty;
            if ($new_qty == 0) {
                $conn->query("UPDATE production_batches SET quantity = 0, status = 'Completed' WHERE id = $batch_id");
            } else {
                $conn->query("UPDATE production_batches SET quantity = $new_qty WHERE id = $batch_id");
            }
            
            $conn->commit();
            echo json_encode(['success' => true]);
        } else {
            throw new Exception("Batch not found or already completed");
        }
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
elseif ($action === 'toggle_inactive') {
    $batch_id = (int)$_POST['batch_id'];
    
    $conn->begin_transaction();
    try {
        // Check if the batch exists, is active, and is in Cutting stage
        // We only allow cancellation if no parts of it have transitioned to the next stage
        // To verify this, we could check if there are any other batches with this reference number in a later stage.
        // Assuming reference_no is tracked throughout, we can check if this is the ONLY batch with this reference_no, 
        // OR simply rely on the fact that if it's still fully in Cutting, we can cancel what is in cutting.
        
        $res = $conn->query("SELECT product_id, quantity, reference_no, current_stage FROM production_batches WHERE id = $batch_id AND current_stage = 'Cutting' AND status = 'Active'");
        if ($res && $batch = $res->fetch_assoc()) {
            
            $ref_no = $batch['reference_no'];
            
            // Check if any part of this batch has progressed to subsequent stages
            $check_res = $conn->query("SELECT id FROM production_batches WHERE reference_no = '$ref_no' AND current_stage != 'Cutting'");
            if ($check_res && $check_res->num_rows > 0) {
                throw new Exception("Cannot cancel: Parts of this batch have already been transferred to the other stages.");
            }
            
            // Mark as canceled (Inactive)
            $conn->query("UPDATE production_batches SET status = 'Canceled' WHERE id = $batch_id");
            
            $conn->commit();
            echo json_encode(['success' => true]);
        } else {
            throw new Exception("Batch not found, not in Cutting stage, or already completed/canceled");
        }
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
else {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
