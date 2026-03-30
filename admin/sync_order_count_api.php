<?php
/**
 * Admin API: Sync Order Count
 * 
 * Provides endpoint for cron to:
 * 1. Process the sync (calculate suitable package, update count, and log).
 */

require_once 'db_connection.php'; // $conn (fe_it_db)

header('Content-Type: application/json');

// Simple "Secret" or just IP restriction could be added here if needed.
// For now, we assume internal trusted environment as it's localhost.

$action = $_POST['action'] ?? '';
$customer_id = isset($_POST['customer_id']) ? (int)$_POST['customer_id'] : 0;

if (!$customer_id) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid Customer ID']);
    exit;
}


/**
 * Action: process_sync
 * Calculates the best fitting package based on order_count and updates the customer.
 */
if ($action === 'process_sync') {
    $total_orders = isset($_POST['total_orders']) ? (int)$_POST['total_orders'] : 0;

    // Fetch current info
    $info_query = "SELECT product_id, package_id, initial_package_id FROM customers WHERE customer_id = ? LIMIT 1";
    $stmt_info = $conn->prepare($info_query);
    $stmt_info->bind_param("i", $customer_id);
    $stmt_info->execute();
    $current_info = $stmt_info->get_result()->fetch_assoc();

    if (!$current_info) {
        echo json_encode(['status' => 'error', 'message' => 'Customer not found']);
        exit;
    }

    $product_id = (int)$current_info['product_id'];
    $current_package_id = (int)$current_info['package_id'];
    $initial_package_id = (int)$current_info['initial_package_id'];
    $new_package_id = $current_package_id;

    // Fetch packages logic
    $pkg_query = "SELECT id, max_count FROM packages WHERE product_id = ? AND status = 'active' ORDER BY max_count ASC";
    $stmt_pkg = $conn->prepare($pkg_query);
    $stmt_pkg->bind_param("i", $product_id);
    $stmt_pkg->execute();
    $pkg_res = $stmt_pkg->get_result();
    
    $packages = [];
    $initial_max_count = 0;
    while ($p = $pkg_res->fetch_assoc()) {
        $packages[] = $p;
        if ((int)$p['id'] === $initial_package_id) {
            $initial_max_count = (int)$p['max_count'];
        }
    }

    if (!empty($packages)) {
        $found = false;
        foreach ($packages as $pkg) {
            if ($total_orders <= (int)$pkg['max_count']) {
                $candidate_id = (int)$pkg['id'];
                $candidate_max = (int)$pkg['max_count'];
                
                // Protection Logic: Don't downgrade below initial_package_id
                if ($candidate_max < $initial_max_count) {
                    $new_package_id = $initial_package_id;
                } else {
                    $new_package_id = $candidate_id;
                }
                $found = true;
                break;
            }
        }
        if (!$found) {
            $last_pkg = end($packages);
            $new_package_id = (int)$last_pkg['id'];
        }
    }

    // Update customer
    $update_query = "UPDATE customers SET order_count = ?, package_id = ?, last_update_time = NOW() WHERE customer_id = ?";
    $stmt_upd = $conn->prepare($update_query);
    $stmt_upd->bind_param("iii", $total_orders, $new_package_id, $customer_id);
    
    if ($stmt_upd->execute()) {
        // Log the action
        $pkg_sync = ($current_package_id == $new_package_id) ? $new_package_id : "$current_package_id -> $new_package_id";
        $log_msg = "Customer #$customer_id: Orders=$total_orders, Package $pkg_sync (Product ID: $product_id)";
        $stmt_log = $conn->prepare("INSERT INTO user_logs (user_id, action_type, inquiry_id, details, created_at) VALUES (1, 'cron_sync_orders', 0, ?, NOW())");
        $stmt_log->bind_param("s", $log_msg);
        $stmt_log->execute();
        
        echo json_encode([
            'status' => 'success', 
            'message' => 'Sync processed successfully',
            'old_package_id' => $current_package_id,
            'new_package_id' => $new_package_id,
            'total_orders' => $total_orders
        ]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Update failed: ' . $stmt_upd->error]);
    }
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
$conn->close();