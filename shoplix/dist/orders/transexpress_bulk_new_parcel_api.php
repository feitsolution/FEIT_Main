<?php
/**
 * Transexpress Bulk New Parcel API Handler - Fixed Version
 * Version: 2.2
 * Date: 2025
 */

session_start();
header('Content-Type: application/json');
ob_start();

// Logging function
function logAction($conn, $user_id, $action, $order_id, $details) {
    $stmt = $conn->prepare("INSERT INTO user_logs (user_id, action_type, inquiry_id, details, created_at) VALUES (?, ?, ?, ?, NOW())");
    if ($stmt) {
        $stmt->bind_param("isis", $user_id, $action, $order_id, $details);
        $stmt->execute();
        $stmt->close();
    }
}

// Call Transexpress bulk API
function callTransexpressBulkApi($apiData, $apiKey) {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => "https://portal.transexpress.lk/api/orders/upload/auto",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($apiData),
        CURLOPT_HTTPHEADER => [
            'Accept: application/json',
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey
        ],
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => false
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) return ['success' => false, 'message' => "Connection error: $error"];
    if ($httpCode !== 200) return ['success' => false, 'message' => "Server error: $httpCode", 'raw_response' => $response];

    $data = json_decode($response, true);
    if (!$data) return ['success' => false, 'message' => 'Invalid JSON response', 'raw_response' => $response];

    return ['success' => true, 'data' => $data, 'raw_response' => $response];
}

// Extract waybills from API response
function extractTransexpressTracking($responseData) {
    $trackingNumbers = [];
    if (!empty($responseData['orders']) && is_array($responseData['orders'])) {
        foreach ($responseData['orders'] as $orderResult) {
            $orderNo = (string)$orderResult['order_no'];
            $waybill = $orderResult['waybill_id'] ?? null;
            if ($waybill) {
                $trackingNumbers[$orderNo] = $waybill;
            }
        }
    }
    return $trackingNumbers;
}

try {
    include($_SERVER['DOCUMENT_ROOT'] . '/shoplix/dist/connection/db_connection.php');

    if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) throw new Exception('Authentication required');
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') throw new Exception('Only POST method allowed');
    if (!isset($_POST['order_ids']) || !isset($_POST['carrier_id'])) throw new Exception('Missing required parameters');

    $orderIds = json_decode($_POST['order_ids'], true);
    $carrierId = (int)$_POST['carrier_id'];
    $dispatchNotes = $_POST['dispatch_notes'] ?? '';
    $userId = $_SESSION['user_id'] ?? 0;

    if (!is_array($orderIds) || empty($orderIds)) throw new Exception('Invalid order IDs');

    $stmt = $conn->prepare("SELECT courier_name, api_key FROM couriers WHERE courier_id = ? AND status='active' AND has_api_new=1");
    $stmt->bind_param("i", $carrierId);
    $stmt->execute();
    $courier = $stmt->get_result()->fetch_assoc();
    if (!$courier || empty($courier['api_key'])) throw new Exception('Invalid courier or missing API credentials');

    // Get pending orders with district information
    $placeholders = str_repeat('?,', count($orderIds) - 1) . '?';
    $stmt = $conn->prepare("
        SELECT oh.order_id, oh.total_amount, oh.pay_status, oh.interface, c.name as customer_name, c.phone as customer_phone, 
               c.address_line1, c.address_line2, ct.city_name        FROM order_header oh
        LEFT JOIN customers c ON oh.customer_id = c.customer_id
        LEFT JOIN city_table ct ON oh.city_id = ct.city_id AND ct.is_active = 1
        WHERE oh.order_id IN ($placeholders) AND oh.status='pending'
    ");
    $stmt->bind_param(str_repeat('i', count($orderIds)), ...$orderIds);
    $stmt->execute();
    $orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    if (empty($orders)) throw new Exception('No valid pending orders found');

    // Prepare API payload with correct field names
    $payload = [];
    $invalidCityOrderIds = [];
    foreach ($orders as $order) {
        if (empty($order['city_name'])) {
            $invalidCityOrderIds[] = $order['order_id'];
            error_log("TransExpress Bulk New - Skipping order {$order['order_id']}: missing city name");
            continue;
        }

        // Map to TransExpress-specific city_id and district_id
        $mapStmt = $conn->prepare("SELECT city_id, district_id FROM city_table_trans WHERE LOWER(city_name) = LOWER(?) AND is_active = 1 LIMIT 1");
        $mapStmt->bind_param("s", $order['city_name']);
        $mapStmt->execute();
        $mapResult = $mapStmt->get_result()->fetch_assoc();
        $mapStmt->close();

        if (!$mapResult) {
            $invalidCityOrderIds[] = $order['order_id'];
            error_log("TransExpress Bulk New - Skipping order {$order['order_id']}: city '{$order['city_name']}' not found in city_table_trans");
            continue;
        }

        $mappedCityId     = (int)$mapResult['city_id'];
        $mappedDistrictId = (int)$mapResult['district_id'];
        error_log("TransExpress Bulk New - Order {$order['order_id']}: City='{$order['city_name']}', Mapped city_id=$mappedCityId, district_id=$mappedDistrictId");
        $apiAmount = ($order['pay_status'] === 'paid') ? 0 : $order['total_amount'];
        $cleanPhone = preg_replace('/[^0-9]/', '', $order['customer_phone']);
        
        // Ensure phone number is properly formatted
        if (strlen($cleanPhone) === 9) {
            $cleanPhone = '0' . $cleanPhone; // Add leading 0 if missing
        }
        
        $payload[] = [
            'order_id' => (string)$order['order_id'], // Convert to string as per API example
            'customer_name' => $order['customer_name'] ?? 'Customer',
            'address' => trim(($order['address_line1'] ?? '') . ' ' . ($order['address_line2'] ?? '')),
            'order_description' => 'Order #' . $order['order_id'],
            'customer_phone' => $cleanPhone,
            'customer_phone2' => '', // Optional field
            'cod_amount' => (float)$apiAmount,
            'district'        => $mappedDistrictId, // Add district field
            'city'            => $mappedCityId,
            'remarks'         => $dispatchNotes // Changed from 'remark' to 'remarks'
        ];
    }

    if (empty($payload)) {
        $msg = 'Invalid delivery city for selected orders';
        if (!empty($invalidCityOrderIds)) {
            $msg .= ' - Order IDs with invalid city: (' . implode(', ', $invalidCityOrderIds) . ')';
        }
        throw new Exception($msg);
    }

    // Log the payload for debugging
    error_log("Transexpress API Payload: " . json_encode($payload));

    // ── Pre-API stock check (sequential reserve) ───────────────────────────────
    // Check orders one by one. Track remaining stock per product as we go.
    // Orders that fit are kept; orders that exceed are removed from the payload.
    $remainingStock = []; // product_id => remaining qty
    $stockFailedOrderIds = [];
    $stockFailedDetails  = [];

    foreach ($orders as $order) {
        if (in_array($order['order_id'], $invalidCityOrderIds)) continue;

        $stockCheckSql = "SELECT oi.product_id, oi.quantity, p.stock_quantity, p.name
                          FROM order_items oi
                          JOIN products p ON oi.product_id = p.id
                          WHERE oi.order_id = ?";
        $preStockStmt = $conn->prepare($stockCheckSql);
        $preStockStmt->bind_param("i", $order['order_id']);
        $preStockStmt->execute();
        $preStockResult = $preStockStmt->get_result();

        $orderCanPass = true;
        $orderProducts = [];

        while ($row = $preStockResult->fetch_assoc()) {
            $pid = $row['product_id'];
            // Initialize remaining stock from DB on first encounter
            if (!isset($remainingStock[$pid])) {
                $remainingStock[$pid] = (int)$row['stock_quantity'];
            }
            $needed = (int)$row['quantity'];
            $orderProducts[] = ['pid' => $pid, 'needed' => $needed, 'name' => $row['name']];

            if ($remainingStock[$pid] < $needed) {
                $orderCanPass = false;
                $stockFailedDetails[] = "Order #{$order['order_id']}: \"{$row['name']}\" (available: {$remainingStock[$pid]}, required: $needed)";
            }
        }
        $preStockStmt->close();

        if ($orderCanPass) {
            // Reserve stock for this order
            foreach ($orderProducts as $op) {
                $remainingStock[$op['pid']] -= $op['needed'];
            }
            error_log("TransExpress Stock Check - Order #{$order['order_id']} PASSED");
        } else {
            // Remove this order from the payload
            $stockFailedOrderIds[] = $order['order_id'];
            error_log("TransExpress Stock Check - Order #{$order['order_id']} FAILED (insufficient stock)");
        }
    }

    // Remove failed orders from payload
    if (!empty($stockFailedOrderIds)) {
        $payload = array_values(array_filter($payload, function($item) use ($stockFailedOrderIds) {
            return !in_array((int)$item['order_id'], $stockFailedOrderIds);
        }));
        // Also filter $orders so we don't process them after API call
        $orders = array_values(array_filter($orders, function($o) use ($stockFailedOrderIds) {
            return !in_array($o['order_id'], $stockFailedOrderIds);
        }));
    }

    // If ALL orders failed stock check, block entirely
    if (empty($payload)) {
        throw new Exception("Insufficient stock for all selected orders: " . implode('. ', $stockFailedDetails));
    }
    // ─────────────────────────────────────────────────────────────────────────

    $apiResult = callTransexpressBulkApi($payload, $courier['api_key']);
    if (!$apiResult['success']) {
        error_log("Transexpress API Error: " . $apiResult['message']);
        if (isset($apiResult['raw_response'])) {
            error_log("Raw Response: " . $apiResult['raw_response']);
        }
        throw new Exception($apiResult['message']);
    }

    $trackingNumbers = extractTransexpressTracking($apiResult['data']);

    $conn->autocommit(false);
    $successCount = 0;
    $failedOrders = [];

    foreach ($orders as $order) {
        $orderId = $order['order_id'];
        if (!isset($trackingNumbers[(string)$orderId])) {
            // Fail order if no waybill returned
            $failedOrders[] = ['order_id' => $orderId, 'error' => 'No waybill returned from Transexpress'];
            logAction($conn, $userId, 'transexpress_bulk_new_dispatch_failed', $orderId, "No waybill returned from API");
            continue;
        }

        $tracking = $trackingNumbers[(string)$orderId];

        try {
            // Stock deduction for dispatched orders
            if (isset($_SESSION['allow_inventory']) && $_SESSION['allow_inventory'] == 1) {
                $stockCheckSql = "SELECT oi.product_id, oi.quantity, p.stock_quantity, p.name 
                                  FROM order_items oi 
                                  JOIN products p ON oi.product_id = p.id 
                                  WHERE oi.order_id = ?";
                $stockStmt = $conn->prepare($stockCheckSql);
                $stockStmt->bind_param("i", $orderId);
                $stockStmt->execute();
                $stockResult = $stockStmt->get_result();
                
                $insufficientStock = false;
                $stockErrorProduct = '';
                
                while ($stockRow = $stockResult->fetch_assoc()) {
                    if ($stockRow['stock_quantity'] < $stockRow['quantity']) {
                        $insufficientStock = true;
                        $stockErrorProduct = $stockRow['name'];
                        break;
                    }
                }
                $stockStmt->close();
                
                if ($insufficientStock) {
                    throw new Exception("Insufficient stock for: $stockErrorProduct");
                }
                
                // Deduct stock
                $deductSql = "UPDATE products p 
                              INNER JOIN order_items oi ON p.id = oi.product_id 
                              SET p.stock_quantity = p.stock_quantity - oi.quantity 
                              WHERE oi.order_id = ?";
                $deductStmt = $conn->prepare($deductSql);
                $deductStmt->bind_param("i", $orderId);
                $deductStmt->execute();
                $deductStmt->close();
            }

            $stmtUpdate = $conn->prepare("UPDATE order_header SET status='dispatch', courier_id=?, tracking_number=?, dispatch_note=?, updated_at=NOW() WHERE order_id=?");
            $stmtUpdate->bind_param("issi", $carrierId, $tracking, $dispatchNotes, $orderId);
            $stmtUpdate->execute();
            $stmtUpdate->close();

            $stmtUpdateItems = $conn->prepare("UPDATE order_items SET status='dispatch' WHERE order_id=?");
            $stmtUpdateItems->bind_param("i", $orderId);
            $stmtUpdateItems->execute();
            $stmtUpdateItems->close();

            logAction($conn, $userId, 'transexpress_bulk_new_dispatch', $orderId, "Order dispatched - Tracking: $tracking");
            $successCount++;
        } catch (Exception $e) {
            $failedOrders[] = ['order_id' => $orderId, 'error' => $e->getMessage()];
            logAction($conn, $userId, 'transexpress_bulk_new_dispatch_failed', $orderId, "Error: " . $e->getMessage());
        }
    }

    if ($successCount > 0) $conn->commit(); else $conn->rollback();
    $conn->autocommit(true);

    $response = [
        'success' => $successCount > 0,
        'processed_count' => $successCount,
        'total_count' => count($orderIds),
        'failed_count' => count($failedOrders),
        'failed_orders' => $failedOrders,
        'message' => "$successCount orders dispatched successfully, " . count($failedOrders) . " failed"
    ];

    ob_clean();
    echo json_encode($response);

} catch (Exception $e) {
    if (isset($conn)) $conn->rollback();
    ob_clean();
    error_log("Transexpress Bulk API Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} finally {
    if (isset($conn)) $conn->autocommit(true);
    ob_end_flush();
}
?>