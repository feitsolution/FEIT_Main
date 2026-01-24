<?php
/**
 * Order Creation API Endpoint - FIXED DESCRIPTION HANDLING
 * File: /lily_collection/dist/api/v1/webhook_create_order.php
 * 
 * CRITICAL FIXES:
 * - Fixed description fetching from product_table
 * - NEW customers: Insert phone_2 into customers table ✓
 * - EXISTING customers: Still insert mobile_2 into order_header ✓
 * - Added validation: phone and phone_2 cannot be the same
 * - Fixed order_items with proper description from database
 */

// Security and initialization
define('API_ACCESS', true);
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// CORS headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed. Use POST']);
    exit;
}

// Include dependencies
require_once($_SERVER['DOCUMENT_ROOT'] . '/lily_collection/dist/connection/db_connection.php');
require_once(__DIR__ . '/auth.php');
require_once(__DIR__ . '/response_handler.php');
require_once(__DIR__ . '/validator.php');

// Initialize
$auth = new ApiAuth($conn);
$validator = new ApiValidator($conn);

// Step 1: Authenticate request
$auth_result = $auth->validateApiKey();
if (!$auth_result['valid']) {
    ApiResponse::unauthorized($auth_result['error']);
}

// Step 2: Get and parse JSON input
$json_input = file_get_contents('php://input');
$request_data = json_decode($json_input, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    ApiResponse::error('Invalid JSON format: ' . json_last_error_msg());
}

// Sanitize input
$request_data = ApiValidator::sanitize($request_data);

// Step 3: Validate input (includes database product validation)
$validation = $validator->validateOrderRequest($request_data);
if (!$validation['valid']) {
    ApiResponse::validationError($validation['errors']);
}

// Get validated products from validator
$validated_products = $validation['validated_products'];

// DEBUG: Log validated products structure
error_log("VALIDATED PRODUCTS: " . print_r($validated_products, true));

// Step 4: Begin transaction
$conn->begin_transaction();

try {
    // ============================================
    // EXTRACT CUSTOMER DATA FROM REQUEST
    // ============================================
    $customer_name = $request_data['customer_name'];
    $customer_email = $request_data['customer_email'] ?? null;
    $customer_phone = preg_replace('/[^0-9]/', '', $request_data['customer_phone']);
    
    // Handle both "customer_phone2" and "customer_phone_2"
    $customer_phone_2_raw = $request_data['customer_phone_2'] ?? $request_data['customer_phone2'] ?? '';
    $customer_phone_2 = !empty($customer_phone_2_raw) 
        ? preg_replace('/[^0-9]/', '', $customer_phone_2_raw) 
        : null;
    
    $address_line1 = $request_data['address_line1'];
    $address_line2 = $request_data['address_line2'] ?? null;
    
    // DEBUG: Log extracted customer data
    error_log("=== EXTRACTED CUSTOMER DATA ===");
    error_log("Name: '$customer_name'");
    error_log("Email: '$customer_email'");
    error_log("Phone (cleaned): '$customer_phone'");
    error_log("Phone2: '$customer_phone_2'");
    
    // Validate phone is not empty
    if (empty($customer_phone)) {
        throw new Exception("Valid phone number is required");
    }
    
    // ============================================
    // VALIDATION: Phone numbers cannot be the same
    // ============================================
    if ($customer_phone_2 !== null && $customer_phone === $customer_phone_2) {
        error_log("VALIDATION FAILED: Phone numbers are identical");
        ApiResponse::validationError([
            'phone_2' => 'Secondary phone number cannot be the same as primary phone number. Please provide different phone numbers or leave secondary phone empty.'
        ]);
    }
    
    // ============================================
    // HANDLE CITY LOOKUP (city_name OR city_id)
    // ============================================
    $city_id = null;
    $zone_id = null;
    $district_id = null;
    $city_name_provided = null;
    
    error_log("=== CITY LOOKUP START ===");
    
    if (!empty($request_data['city_name'])) {
        $city_name_provided = trim($request_data['city_name']);
        error_log("Looking up city by name: '$city_name_provided'");
        
        $getCityByNameSql = "SELECT city_id, city_name, zone_id, district_id 
                             FROM city_table 
                             WHERE LOWER(city_name) = LOWER(?) 
                             LIMIT 1";
        $cityStmt = $conn->prepare($getCityByNameSql);
        $cityStmt->bind_param("s", $city_name_provided);
        $cityStmt->execute();
        $cityResult = $cityStmt->get_result();
        
        if ($cityResult && $cityResult->num_rows > 0) {
            $cityData = $cityResult->fetch_assoc();
            $city_id = $cityData['city_id'];
            $zone_id = $cityData['zone_id'];
            $district_id = $cityData['district_id'];
            error_log("City found: ID=$city_id");
        } else {
            error_log("ERROR: City '$city_name_provided' not found");
            $cityStmt->close();
            throw new Exception("City '$city_name_provided' not found in database. Please check the city name.");
        }
        $cityStmt->close();
    }
    elseif (!empty($request_data['city_id'])) {
        $city_id = $request_data['city_id'];
        error_log("Looking up city by ID: $city_id");
        
        $getCityDataSql = "SELECT city_name, zone_id, district_id 
                           FROM city_table 
                           WHERE city_id = ?";
        $cityStmt = $conn->prepare($getCityDataSql);
        $cityStmt->bind_param("i", $city_id);
        $cityStmt->execute();
        $cityResult = $cityStmt->get_result();
        
        if ($cityResult && $cityResult->num_rows > 0) {
            $cityData = $cityResult->fetch_assoc();
            $zone_id = $cityData['zone_id'];
            $district_id = $cityData['district_id'];
            $city_name_provided = $cityData['city_name'];
            error_log("City found: ID=$city_id");
        } else {
            error_log("ERROR: City ID '$city_id' not found");
            $cityStmt->close();
            throw new Exception("City ID '$city_id' not found in database.");
        }
        $cityStmt->close();
    } else {
        throw new Exception("City information is required (provide either city_name or city_id)");
    }
    
    if ($city_id === null) {
        throw new Exception("Failed to determine city_id");
    }
    
    error_log("=== CITY LOOKUP COMPLETE: city_id=$city_id ===");
    
    // ============================================
    // CUSTOMER MATCHING & CREATION LOGIC
    // ============================================
    $customer_id = 0;
    $is_new_customer = true;
    $order_mobile_2 = $customer_phone_2;
    
    error_log("=== CUSTOMER MATCHING START ===");
    
    $checkCustomerSql = "SELECT customer_id, name, phone, phone_2, email FROM customers 
                         WHERE ((phone = ? OR phone_2 = ?) OR email = ?) 
                         AND status = 'Active' 
                         LIMIT 1";
    
    $checkStmt = $conn->prepare($checkCustomerSql);
    $phone_check = $customer_phone ?? '';
    $email_check = $customer_email ?? '';
    
    $checkStmt->bind_param("sss", $phone_check, $phone_check, $email_check);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    
    if ($result->num_rows > 0) {
        // EXISTING CUSTOMER
        $existing_customer = $result->fetch_assoc();
        $customer_id = $existing_customer['customer_id'];
        $is_new_customer = false;
        error_log("EXISTING CUSTOMER: ID=$customer_id");
        
    } else {
        // NEW CUSTOMER
        error_log("Creating NEW customer");
        
        $insertCustomerSql = "INSERT INTO customers (name, email, phone, phone_2, address_line1, address_line2, city_id, status) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, 'Active')";
        $insertStmt = $conn->prepare($insertCustomerSql);
        
        if (!$insertStmt) {
            throw new Exception("Failed to prepare customer insert: " . $conn->error);
        }
        
        $insertStmt->bind_param("ssssssi", $customer_name, $customer_email, $customer_phone, $customer_phone_2, 
                         $address_line1, $address_line2, $city_id);
        
        if (!$insertStmt->execute()) {
            $insertStmt->close();
            throw new Exception("Failed to create customer: " . $insertStmt->error);
        }
        
        $customer_id = $conn->insert_id;
        error_log("✓ New customer created: ID=$customer_id");
        $insertStmt->close();
    }
    
    $checkStmt->close();
    
    error_log("=== CUSTOMER PROCESSING COMPLETE: ID=$customer_id ===");
    
    // ============================================
    // PROCESS ORDER DETAILS
    // ============================================
    $order_date = $request_data['order_date'] ?? date('Y-m-d');
    $due_date = $request_data['due_date'] ?? date('Y-m-d', strtotime('+30 days'));
    $notes = $request_data['notes'] ?? "";
    $order_status = $request_data['order_status'] ?? 'Unpaid';
    $pay_status = $order_status === 'Paid' ? 'paid' : 'unpaid';
    $pay_date = $order_status === 'Paid' ? date('Y-m-d') : null;
    $status = 'pending';
    $user_id = 1;
    
    // ============================================
    // FETCH COMPLETE PRODUCT DATA FROM DATABASE
    // ============================================
    $subtotal_before_discounts = 0;
    $total_discount = 0;
    $product_codes = [];
    $order_items = [];
    
    if (empty($validated_products)) {
        throw new Exception("No validated products found. Cannot create order without products.");
    }
    
    error_log("=== FETCHING PRODUCT DETAILS FROM DATABASE ===");
    
    foreach ($validated_products as $index => $product) {
        $product_id = $product['product_id'] ?? $product['id'] ?? null;
        
        if (!$product_id) {
            throw new Exception("Product validation error: Missing product ID at index $index");
        }
        
        // FETCH COMPLETE PRODUCT DATA INCLUDING DESCRIPTION
        $productSql = "SELECT id as product_id, product_code, name as product_name, lkr_price as price, description 
                       FROM products 
                       WHERE id = ? AND status = 'active' 
                       LIMIT 1";
        
        $productStmt = $conn->prepare($productSql);
        $productStmt->bind_param("i", $product_id);
        $productStmt->execute();
        $productResult = $productStmt->get_result();
        
        if ($productResult->num_rows === 0) {
            $productStmt->close();
            throw new Exception("Product ID $product_id not found or inactive");
        }
        
        $db_product = $productResult->fetch_assoc();
        $productStmt->close();
        
        // Use requested price/discount if provided, otherwise use DB values
        $original_price = isset($product['price']) ? floatval($product['price']) : floatval($db_product['price']);
        $discount = isset($product['discount']) ? floatval($product['discount']) : 0;
        
        // ALWAYS use description from database
        $description = $db_product['description'] ?? '';
        
        error_log("Product $index: ID={$db_product['product_id']}, Code={$db_product['product_code']}, Description='$description'");
        
        $subtotal_before_discounts += $original_price;
        $total_discount += $discount;
        $product_codes[] = $db_product['product_code'];
        
        $order_items[] = [
            'product_id' => $db_product['product_id'],
            'product_code' => $db_product['product_code'],
            'product_name' => $db_product['product_name'],
            'original_price' => $original_price,
            'discount' => $discount,
            'description' => $description  // From database
        ];
    }
    
    $final_product_code = implode(',', $product_codes);
    $subtotal_after_discount = $subtotal_before_discounts - $total_discount;
    
    // Get delivery fee
    $delivery_fee = floatval($auth_result['delivery_fee'] ?? 0);
    
    // Free delivery for orders >= 5000
    if ($subtotal_after_discount >= 5000) {
        $delivery_fee = 0.00;
        error_log("Free delivery applied (subtotal >= 5000)");
    }
    
    $total_amount = $subtotal_after_discount + $delivery_fee;
    
    error_log("=== FINANCIAL SUMMARY ===");
    error_log("Subtotal: $subtotal_before_discounts");
    error_log("Discount: $total_discount");
    error_log("Delivery: $delivery_fee");
    error_log("Total: $total_amount");
    
    // ============================================
    // INSERT ORDER_HEADER (with mobile_2)
    // ============================================
    error_log("=== INSERTING ORDER HEADER ===");
    
    $insertOrderSql = "INSERT INTO order_header (
        customer_id, user_id, issue_date, due_date, subtotal, discount, total_amount, delivery_fee,
        notes, currency, status, pay_status, pay_date, created_by, product_code,
        full_name, mobile, mobile_2, address_line1, address_line2, city_id, zone_id, district_id
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'lkr', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $orderStmt = $conn->prepare($insertOrderSql);
    if (!$orderStmt) {
        throw new Exception("Failed to prepare order_header: " . $conn->error);
    }
    
    $orderStmt->bind_param(
        "iissddddsssisssssssiii",
        $customer_id, $user_id, $order_date, $due_date,
        $subtotal_before_discounts, $total_discount, $total_amount, $delivery_fee,
        $notes, $status, $pay_status, $pay_date, $user_id, $final_product_code,
        $customer_name, $customer_phone, $order_mobile_2,
        $address_line1, $address_line2, $city_id, $zone_id, $district_id
    );
    
    if (!$orderStmt->execute()) {
        $orderStmt->close();
        throw new Exception("Failed to create order: " . $orderStmt->error);
    }
    
    $order_id = $conn->insert_id;
    error_log("✓ ORDER HEADER CREATED: order_id=$order_id");
    $orderStmt->close();
    
    // ============================================
    // INSERT ORDER ITEMS WITH DESCRIPTION
    // ============================================
    error_log("=== INSERTING ORDER ITEMS ===");
    
    $insertItemSql = "INSERT INTO order_items (
        order_id, product_id, unit_price, discount, total_amount, 
        pay_status, status, description
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    
    $itemStmt = $conn->prepare($insertItemSql);
    if (!$itemStmt) {
        throw new Exception("Failed to prepare order_items: " . $conn->error);
    }
    
    $items_inserted = 0;
    foreach ($order_items as $index => $item) {
        $item_total = $item['original_price'] - $item['discount'];
        
        error_log("Item $index: product_id={$item['product_id']}, description='{$item['description']}'");
        
        $itemStmt->bind_param(
            "iiddssss",
            $order_id,
            $item['product_id'],
            $item['original_price'],
            $item['discount'],
            $item_total,
            $pay_status,
            $status,
            $item['description']  // Description from product_table
        );
        
        if (!$itemStmt->execute()) {
            $itemStmt->close();
            throw new Exception("Failed to insert order item #$index: " . $itemStmt->error);
        }
        
        $items_inserted++;
        error_log("✓ Item $index inserted with description");
    }
    $itemStmt->close();
    
    error_log("✓ TOTAL ITEMS INSERTED: $items_inserted");
    
    // Verify items
    $verifyStmt = $conn->prepare("SELECT COUNT(*) as count FROM order_items WHERE order_id = ?");
    $verifyStmt->bind_param("i", $order_id);
    $verifyStmt->execute();
    $verifyResult = $verifyStmt->get_result()->fetch_assoc();
    $actual_count = $verifyResult['count'];
    $verifyStmt->close();
    
    error_log("VERIFICATION: Found $actual_count items for order_id $order_id");
    
    if ($actual_count == 0) {
        throw new Exception("ORDER ITEMS INSERTION FAILED!");
    }
    
    // ============================================
    // GET COURIER INFO
    // ============================================
    $tracking_number = null;
    $courier_name = null;
    
    $courierResult = $conn->query("SELECT courier_name FROM couriers WHERE is_default IN (1,2,3) AND status = 'active' ORDER BY is_default ASC LIMIT 1");
    
    if ($courierResult && $courierResult->num_rows > 0) {
        $courier = $courierResult->fetch_assoc();
        $courier_name = $courier['courier_name'];
    }
    
    // ============================================
    // COMMIT TRANSACTION
    // ============================================
    error_log("=== COMMITTING TRANSACTION ===");
    $conn->commit();
    error_log("✓ TRANSACTION COMMITTED SUCCESSFULLY");
    
    // ============================================
    // PREPARE SUCCESS RESPONSE
    // ============================================
    $response_data = [
        'order_id' => $order_id,
        'customer_id' => $customer_id,
        'is_new_customer' => $is_new_customer,
        'order_status' => $status,
        'payment_status' => $pay_status,
        'order_date' => $order_date,
        'due_date' => $due_date,
        'product_code_stored' => $final_product_code,
        'items_inserted_count' => $items_inserted,
        'items_verified_count' => $actual_count,
        
        // Financial summary
        'financial_summary' => [
            'subtotal_before_discount' => number_format($subtotal_before_discounts, 2),
            'total_discount' => number_format($total_discount, 2),
            'subtotal_after_discount' => number_format($subtotal_after_discount, 2),
            'delivery_fee' => number_format($delivery_fee, 2),
            'free_delivery_applied' => ($subtotal_after_discount >= 5000 && $delivery_fee == 0),
            'grand_total' => number_format($total_amount, 2),
            'currency' => 'LKR'
        ],
        
        // Product details
        'products' => array_map(function($p) {
            return [
                'product_id' => $p['product_id'],
                'product_code' => $p['product_code'],
                'product_name' => $p['product_name'],
                'price' => number_format($p['original_price'], 2),
                'discount' => number_format($p['discount'], 2),
                'final_price' => number_format($p['original_price'] - $p['discount'], 2),
                'description' => $p['description']
            ];
        }, $order_items),
        
        // Delivery information
        'delivery_info' => [
            'city_name' => $city_name_provided,
            'city_id' => $city_id,
            'zone_id' => $zone_id,
            'district_id' => $district_id,
            'address_line1' => $address_line1,
            'address_line2' => $address_line2,
            'tracking_number' => $tracking_number,
            'courier' => $courier_name
        ],
        
        // Customer details
        'customer_details' => [
            'customer_id' => $customer_id,
            'name' => $customer_name,
            'phone' => $customer_phone,
            'phone_2' => $order_mobile_2,
            'email' => $customer_email,
            'phone_2_note' => $is_new_customer 
                ? 'phone_2 inserted into both customers and order_header' 
                : 'phone_2 inserted into order_header only (existing customer not updated)'
        ]
    ];
    
    error_log("=== API SUCCESS ===");
    
    ApiResponse::orderCreated($response_data);
    
} catch (Exception $e) {
    error_log("=== EXCEPTION: " . $e->getMessage() . " ===");
    $conn->rollback();
    ApiResponse::serverError($e->getMessage());
}
?>