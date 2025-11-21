<?php
// Include database connection
include($_SERVER['DOCUMENT_ROOT'] . '/order_management/dist/connection/db_connection.php');

// Set content type to JSON
header('Content-Type: application/json');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors in output
ini_set('log_errors', 1);

// Optional: get specific waybill from query parameter
$waybillid = isset($_GET['waybillid']) ? trim($_GET['waybillid']) : '';

// 1. Fetch Koombiyo API key by courier_id
$courierId = 12; // Koombiyo courier ID
$query = $conn->prepare("SELECT api_key, courier_name FROM couriers WHERE courier_id = ? LIMIT 1");
$query->bind_param("i", $courierId);
$query->execute();
$result = $query->get_result();

if ($result->num_rows == 0) {
    http_response_code(500);
    echo json_encode(['error' => 'Courier with ID 12 not found in couriers table']);
    exit;
}

$courierData = $result->fetch_assoc();
$apiKey = $courierData['api_key'];
$courierName = $courierData['courier_name'];
$query->close();

// Check if API key exists
if (empty($apiKey)) {
    http_response_code(500);
    echo json_encode(['error' => 'API key not configured for courier: ' . $courierName]);
    exit;
}

// 2. Fetch all local Koombiyo orders with tracking numbers
if (!empty($waybillid)) {
    $orderQuery = $conn->prepare("SELECT order_id, tracking_number, status as current_status FROM order_header WHERE courier_id = ? AND tracking_number IS NOT NULL AND tracking_number = ?");
    $orderQuery->bind_param("is", $courierId, $waybillid);
} else {
    $orderQuery = $conn->prepare("SELECT order_id, tracking_number, status as current_status FROM order_header WHERE courier_id = ? AND tracking_number IS NOT NULL");
    $orderQuery->bind_param("i", $courierId);
}

$orderQuery->execute();
$orderResult = $orderQuery->get_result();

$orders = [];
while ($row = $orderResult->fetch_assoc()) {
    $orders[] = $row;
}
$orderQuery->close();

if (empty($orders)) {
    echo json_encode([
        'message' => 'No orders found with tracking numbers for courier: ' . $courierName,
        'courier_id' => $courierId
    ]);
    exit;
}

// 3. Status mapping - Koombiyo status to your local status
$status_map = [
    "pickup" => "dispatch",
    "waiting" => "dispatch",
    "transfer" => "dispatch",
    "processing" => "dispatch",
    "dispatched" => "dispatch",
    "hold" => "pending",
    "reschedule" => "pending",
    "date changed" => "pending",
    "rearranged" => "pending",
    "delivered" => "done",
    "return" => "cancel",
    "damaged" => "cancel",
    "return pending" => "cancel",
    "return complete" => "cancel"
];

$updatedOrders = [];
$skippedOrders = [];
$errorOrders = [];

foreach ($orders as $order) {
    $waybill = $order['tracking_number'];
    $orderId = $order['order_id'];
    $currentStatus = $order['current_status'];
    
    // Prepare POST fields for Koombiyo API
    $postFields = http_build_query([
        'apikey' => $apiKey,
        'waybillid' => $waybill,
        'offset' => 1,
        'limit' => 1
    ]);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://application.koombiyodelivery.lk/api/Allorders/users");
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // For local testing

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if (curl_errno($ch)) {
        $errorOrders[] = [
            'order_id' => $orderId,
            'tracking_number' => $waybill,
            'error' => curl_error($ch)
        ];
        error_log("cURL error for $waybill: " . curl_error($ch));
        curl_close($ch);
        continue;
    }
    curl_close($ch);

    $data = json_decode($response, true);

    // Check if API returned valid data
    if (!empty($data['orders'][0])) {
        $orderData = $data['orders'][0];
        $koombiyoStatus = strtolower(trim($orderData['status']));
        $lastScan = !empty($orderData['last_scan_date']) ? $orderData['last_scan_date'] : date('Y-m-d H:i:s');

        // Map Koombiyo status to local status
        $mappedStatus = isset($status_map[$koombiyoStatus]) ? $status_map[$koombiyoStatus] : "pending";

        // Only update if status has changed
        if ($mappedStatus !== $currentStatus) {
            $stmt = $conn->prepare("UPDATE order_header SET status = ?, updated_at = ? WHERE tracking_number = ? AND courier_id = ?");
            $stmt->bind_param("sssi", $mappedStatus, $lastScan, $waybill, $courierId);
            
            if ($stmt->execute() && $stmt->affected_rows > 0) {
                $updatedOrders[] = [
                    'order_id' => $orderId,
                    'tracking_number' => $waybill,
                    'previous_status' => $currentStatus,
                    'koombiyo_status' => $koombiyoStatus,
                    'new_status' => $mappedStatus,
                    'last_scan_date' => $lastScan
                ];
            } else {
                $errorOrders[] = [
                    'order_id' => $orderId,
                    'tracking_number' => $waybill,
                    'error' => 'Update failed: ' . $stmt->error
                ];
            }
            
            $stmt->close();
        } else {
            $skippedOrders[] = [
                'order_id' => $orderId,
                'tracking_number' => $waybill,
                'status' => $currentStatus,
                'reason' => 'Status unchanged'
            ];
        }
    } else {
        $skippedOrders[] = [
            'order_id' => $orderId,
            'tracking_number' => $waybill,
            'reason' => 'No data returned from Koombiyo API',
            'api_response' => $data
        ];
    }
}

// 4. Return comprehensive result
echo json_encode([
    'success' => true,
    'courier_id' => $courierId,
    'courier_name' => $courierName,
    'summary' => [
        'total_orders' => count($orders),
        'updated' => count($updatedOrders),
        'skipped' => count($skippedOrders),
        'errors' => count($errorOrders)
    ],
    'updated_orders' => $updatedOrders,
    'skipped_orders' => $skippedOrders,
    'error_orders' => $errorOrders
], JSON_PRETTY_PRINT);

$conn->close();
?>