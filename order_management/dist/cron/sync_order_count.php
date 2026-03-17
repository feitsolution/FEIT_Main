<?php
/**
 * Cron Job: Sync Order Count from order_management to fe_it_db
 * 
 * Logic:
 * 1. Fetch customer_id and billing_date from branding table in order_management DB.
 * 2. If today matches billing_date:
 *    a. Get start date from Admin API.
 *    b. Count ALL orders in order_management.order_header since that date.
 *    c. Submit order_count to Admin API for processing (package logic and DB update).
 *    d. Log the action locally.
 */

// Set time zone
date_default_timezone_set('Asia/Colombo');

// Define connection paths
$base_dir = dirname(__DIR__); // Go up from cron to dist
require_once $base_dir . '/connection/db_connection.php'; // $conn

// Admin API Config
$admin_api_url = "https://feitsolutions.com/admin/sync_order_count_api.php";

// Log File Setup
$log_dir = $base_dir . '/cron/logs';
if (!is_dir($log_dir)) {
    mkdir($log_dir, 0755, true);
}
$log_file = $log_dir . '/sync.log';

function write_log($message) {
    global $log_file;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($log_file, "[$timestamp] $message" . PHP_EOL, FILE_APPEND);
}

/**
 * Helper to call Admin API
 */
function call_admin_api($action, $data) {
    global $admin_api_url;
    $data['action'] = $action;
    
    $ch = curl_init($admin_api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $error = curl_error($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($error) {
        return ['status' => 'error', 'message' => "cURL Error: $error"];
    }
    
    if ($http_code !== 200) {
        return ['status' => 'error', 'message' => "HTTP Error Code: $http_code. Response: $response"];
    }
    
    $result = json_decode($response, true);
    if (!$result) {
        return ['status' => 'error', 'message' => "Invalid API response (JSON decode failed): $response"];
    }
    
    return $result;
}

write_log("Starting Order Count Sync Cron (via API)...");

try {
    //Fetch branding settings
    $branding_query = "SELECT customer_id, billing_date FROM branding WHERE active = 1 LIMIT 1";
    $branding_res = $conn->query($branding_query);

    if (!$branding_res || $branding_res->num_rows === 0) {
        write_log("Error: No active branding settings found.");
        exit("Error: No active branding settings found.\n");
    }

    $branding = $branding_res->fetch_assoc();
    $target_customer_id = (int)$branding['customer_id'];
    $billing_date = (int)$branding['billing_date'];
    $today_day = (int)date('j');

    write_log("Target Customer ID: $target_customer_id, Billing Date: $billing_date, Today: $today_day");

    // Check if it's billing day
    if ($today_day !== $billing_date) {
        write_log("Not billing day. Skipping sync.");
        exit("Not billing day. Skipping sync.\n");
    }

    // Check if it's the correct time window (1:00 AM - 1:59 AM)
    // This ensures order count is synced BEFORE invoice generation (2:00 AM)
    $current_hour = (int)date('G');
    if ($current_hour !== 1) {
        write_log("Not in sync time window (1 AM). Current hour: $current_hour. Skipping.");
        exit("Not in sync time window. Current hour: $current_hour. Skipping.\n");
    }

    // Get start date via Admin API
    write_log("Fetching start date from Admin API...");
    $api_res = call_admin_api('get_start_date', ['customer_id' => $target_customer_id]);
    
    if ($api_res['status'] !== 'success') {
        throw new Exception("API Error (get_start_date): " . $api_res['message']);
    }
    
    $start_date = $api_res['start_date'];
    write_log("Start date determined by API: $start_date");

    // Count orders
    $order_count_query = "SELECT COUNT(*) as total FROM order_header WHERE created_at >= ?";
    $stmt_order = $conn->prepare($order_count_query);
    $stmt_order->bind_param("s", $start_date);
    $stmt_order->execute();
    $order_res = $stmt_order->get_result();
    $order_data = $order_res->fetch_assoc();
    $total_orders = (int)$order_data['total'];

    write_log("Total orders counted locally since $start_date: $total_orders");

    // Submit sync request to Admin API for final processing (DB update & package logic)
    write_log("Submitting sync request to Admin API...");
    $sync_res = call_admin_api('process_sync', [
        'customer_id' => $target_customer_id,
        'total_orders' => $total_orders
    ]);

    if ($sync_res['status'] !== 'success') {
        throw new Exception("API Error (process_sync): " . $sync_res['message']);
    }

    write_log("Successfully processed sync via API.");
    write_log("Package sync results: " . ($sync_res['old_package_id'] ?? 'N/A') . " -> " . ($sync_res['new_package_id'] ?? 'N/A'));
    write_log("Cron completed successfully.");
    echo "Cron completed successfully. Order count: $total_orders\n";

} catch (Exception $e) {
    write_log("CRITICAL ERROR: " . $e->getMessage());
    echo "CRITICAL ERROR: " . $e->getMessage() . "\n";
}

$conn->close();
?>
