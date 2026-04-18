<?php
/**
 * BULK UPDATE LEAD ASSIGNED USER HANDLER
 * Handles AJAX requests to bulk update the 'user_id' field in order_header
 * File: bulk_update_lead_user.php
 */

session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo json_encode([
        'success' => false,
        'message' => 'Authentication required'
    ]);
    exit();
}

if (!isset($_SESSION['role_id']) || intval($_SESSION['role_id']) !== 1) {
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized: Only admins can reassign leads'
    ]);
    exit();
}

include($_SERVER['DOCUMENT_ROOT'] . '/order_management/dist/connection/db_connection.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
    exit();
}

try {
    $order_ids = isset($_POST['order_ids']) ? json_decode($_POST['order_ids'], true) : [];
    $new_user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : null;
    
    if (empty($order_ids) || !is_array($order_ids)) {
        throw new Exception('No leads selected');
    }
    
    if ($new_user_id === null || $new_user_id <= 0) {
        throw new Exception('Invalid user selection');
    }
    
    $conn->begin_transaction();
    
    try {
        $userCheckSql = "SELECT name FROM users WHERE id = ? AND status = 'active'";
        $userCheckStmt = $conn->prepare($userCheckSql);
        $userCheckStmt->bind_param("i", $new_user_id);
        $userCheckStmt->execute();
        $userResult = $userCheckStmt->get_result();
        
        if ($userResult->num_rows === 0) {
            throw new Exception('Selected user is invalid or inactive');
        }
        
        $newUserName = $userResult->fetch_assoc()['name'];
        $userCheckStmt->close();
        
        $success_count = 0;
        $failed_count = 0;
        $skipped_count = 0;
        $loggedInUserId = $_SESSION['user_id'] ?? 1;
        
        foreach ($order_ids as $order_id) {
            $order_id = trim($order_id);
            if (empty($order_id)) {
                $skipped_count++;
                continue;
            }
            
            $checkSql = "SELECT i.order_id, i.user_id, i.status, u.name as old_user_name 
                         FROM order_header i 
                         LEFT JOIN users u ON i.user_id = u.id 
                         WHERE i.order_id = ? AND i.interface = 'leads'";
            $checkStmt = $conn->prepare($checkSql);
            $checkStmt->bind_param("s", $order_id);
            $checkStmt->execute();
            $result = $checkStmt->get_result();
            
            if ($result->num_rows === 0) {
                $skipped_count++;
                $checkStmt->close();
                continue;
            }
            
            $leadData = $result->fetch_assoc();
            
            if (strtolower($leadData['status']) !== 'pending') {
                $skipped_count++;
                $checkStmt->close();
                continue;
            }
            
            $oldUserId = $leadData['user_id'];
            $oldUserName = $leadData['old_user_name'] ?? 'Unassigned';
            $checkStmt->close();
            
            $updateSql = "UPDATE order_header SET user_id = ?, updated_at = CURRENT_TIMESTAMP WHERE order_id = ? AND interface = 'leads'";
            $updateStmt = $conn->prepare($updateSql);
            $updateStmt->bind_param("is", $new_user_id, $order_id);
            
            if ($updateStmt->execute()) {
                $success_count++;
                
                $log_details = "Bulk reassignment: Order ID:({$order_id}) reassigned from {$oldUserName} (ID: {$oldUserId}) to {$newUserName} (ID: {$new_user_id})";
                
                $logSql = "INSERT INTO user_logs (user_id, action_type, inquiry_id, details, created_at) 
                           VALUES (?, 'bulk_lead_reassignment', ?, ?, CURRENT_TIMESTAMP)";
                $logStmt = $conn->prepare($logSql);
                $logStmt->bind_param("iss", $loggedInUserId, $order_id, $log_details);
                $logStmt->execute();
                $logStmt->close();
            } else {
                $failed_count++;
            }
            
            $updateStmt->close();
        }
        
        $conn->commit();
        
        $message = "Successfully reassigned {$success_count} lead(s) to {$newUserName}";
        if ($skipped_count > 0) {
            $message .= ". {$skipped_count} lead(s) skipped (not pending status or not found)";
        }
        if ($failed_count > 0) {
            $message .= ". {$failed_count} lead(s) failed to update";
        }
        
        echo json_encode([
            'success' => true,
            'message' => $message,
            'processed_count' => $success_count,
            'skipped_count' => $skipped_count,
            'failed_count' => $failed_count,
            'new_user_id' => $new_user_id,
            'new_user_name' => $newUserName
        ]);
        
    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>
