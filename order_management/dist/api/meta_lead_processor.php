<?php
/**
 * Meta Lead Processor
 * Fetches lead details from Meta and creates orders in the system.
 */

function processMetaLead($conn, $leadId, $formId) {
    // 1. Fetch lead details from Meta Graph API
    $url = "https://graph.facebook.com/" . META_API_VERSION . "/" . $leadId . "?access_token=" . META_PAGE_ACCESS_TOKEN;
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);

    if (!$response) {
        return false;
    }

    $leadData = json_decode($response, true);
    if (!isset($leadData['field_data'])) {
        return false;
    }

    // 2. Map Meta fields to local variables
    $mappedFields = [];
    foreach ($leadData['field_data'] as $field) {
        $fieldName = $field['name'];
        $fieldValue = $field['values'][0] ?? '';
        $mappedFields[$fieldName] = trim($fieldValue);
    }

    // Standardize field mapping (adjust these based on your Meta form fields)
    $fullName = $mappedFields['full_name'] ?? $mappedFields['name'] ?? '';
    $phoneNumber = $mappedFields['phone_number'] ?? '';
    $city = $mappedFields['city'] ?? '';
    $email = $mappedFields['email'] ?? '';
    $addressLine1 = $mappedFields['address'] ?? $mappedFields['street_address'] ?? '';
    
    // Normalize phone number (handle +94 or 94 prefixes)
    if (strlen($phoneNumber) === 12 && substr($phoneNumber, 0, 3) === '+94') {
        $phoneNumber = '0' . substr($phoneNumber, 3);
    } elseif (strlen($phoneNumber) === 11 && substr($phoneNumber, 0, 2) === '94') {
        $phoneNumber = '0' . substr($phoneNumber, 2);
    } elseif (strlen($phoneNumber) === 9 && ctype_digit($phoneNumber)) {
        $phoneNumber = '0' . $phoneNumber;
    }

    if (empty($fullName) || empty($phoneNumber)) {
        return false; // Required fields missing
    }

    // 3. Database Logic (from lead_upload.php & process_order.php)
    
    // Get city details (zone_id, district_id)
    $cityId = null;
    $zoneId = null;
    $districtId = null;
    if (!empty($city)) {
        $citySql = "SELECT city_id, zone_id, district_id FROM city_table WHERE LOWER(city_name) = LOWER(?) LIMIT 1";
        $cityStmt = $conn->prepare($citySql);
        if ($cityStmt) {
            $cityStmt->bind_param("s", $city);
            $cityStmt->execute();
            $cityResult = $cityStmt->get_result();
            if ($cityResult->num_rows > 0) {
                $cityData = $cityResult->fetch_assoc();
                $cityId = $cityData['city_id'];
                $zoneId = $cityData['zone_id'];
                $districtId = $cityData['district_id'];
            }
            $cityStmt->close();
        }
    }

    // Check/Create Customer
    $customerId = null;
    $customerCheckSql = "SELECT customer_id FROM customers WHERE phone = ? LIMIT 1";
    $customerCheckStmt = $conn->prepare($customerCheckSql);
    $customerCheckStmt->bind_param("s", $phoneNumber);
    $customerCheckStmt->execute();
    $customerCheckResult = $customerCheckStmt->get_result();

    if ($customerCheckResult->num_rows > 0) {
        $customerId = $customerCheckResult->fetch_assoc()['customer_id'];
    } else {
        $customerInsertSql = "INSERT INTO customers (name, email, phone, address_line1, city_id, status) VALUES (?, ?, ?, ?, ?, 'Active')";
        $customerInsertStmt = $conn->prepare($customerInsertSql);
        $customerInsertStmt->bind_param("ssssi", $fullName, $email, $phoneNumber, $addressLine1, $cityId);
        $customerInsertStmt->execute();
        $customerId = $conn->insert_id;
        $customerInsertStmt->close();
    }
    $customerCheckStmt->close();

    // Fetch Branding/Delivery Fee
    $deliveryFee = 0;
    $brandingSql = "SELECT delivery_fee FROM branding WHERE active = 1 ORDER BY branding_id ASC LIMIT 1";
    $brandingResult = $conn->query($brandingSql);
    if ($brandingResult && $brandingData = $brandingResult->fetch_assoc()) {
        $deliveryFee = (float)$brandingData['delivery_fee'];
    }

    // Fetch Product Details
    $productId = META_DEFAULT_PRODUCT_ID;
    $productSql = "SELECT id, lkr_price, product_code FROM products WHERE id = ? AND LOWER(status) = 'active'";
    $productStmt = $conn->prepare($productSql);
    $productStmt->bind_param("i", $productId);
    $productStmt->execute();
    $productResult = $productStmt->get_result();
    
    if ($productResult->num_rows === 0) {
        return false; // Default product not found
    }

    $product = $productResult->fetch_assoc();
    $unitPrice = (float)$product['lkr_price'];
    $productCode = $product['product_code'];
    $subtotal = $unitPrice;
    $totalAmountWithDelivery = $subtotal + $deliveryFee;
    $productStmt->close();

    // Calculate customer condition (success rate)
    $rate = cs_condition_meta($conn, $customerId);

    // Create Order Header
    // Matching the structure from process_order.php (25 parameters)
    $insertOrderSql = "INSERT INTO order_header (
        customer_id, user_id, issue_date, due_date, 
        subtotal, discount, total_amount, delivery_fee,
        notes, currency, status, pay_status, pay_date, created_by,
        product_code, full_name, email, mobile, mobile_2,
        address_line1, address_line2, city_id, zone_id, district_id, `condition`
    ) VALUES (?, ?, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 7 DAY), ?, 0.00, ?, ?, 'Meta Lead', 'lkr', 'pending', 'unpaid', NULL, ?, ?, ?, ?, ?, NULL, ?, NULL, ?, ?, ?, ?)";

    $orderStmt = $conn->prepare($insertOrderSql);
    $defaultUserId = META_DEFAULT_USER_ID;
    
    // product_code in order_header seems to be the product ID or a comma-separated list of IDs
    $finalProductCode = (string)$productId; 

    $orderStmt->bind_param(
        "iiddidissssssiiii", 
        $customerId,        // 1. customer_id
        $defaultUserId,     // 2. user_id
        $subtotal,          // 5. subtotal
        $totalAmountWithDelivery, // 7. total_amount
        $deliveryFee,       // 8. delivery_fee
        $defaultUserId,     // 14. created_by
        $finalProductCode,  // 15. product_code
        $fullName,          // 16. full_name
        $email,             // 17. email
        $phoneNumber,       // 18. mobile
        $addressLine1,      // 20. address_line1
        $cityId,            // 22. city_id
        $zoneId,            // 23. zone_id
        $districtId,        // 24. district_id
        $rate               // 25. condition
    );

    if ($orderStmt->execute()) {
        $orderId = $conn->insert_id;
        
        // Create Order Item
        $itemSql = "INSERT INTO order_items (order_id, product_id, unit_price, discount, total_amount, quantity, pay_status, status, description) 
                    VALUES (?, ?, ?, 0.00, ?, 1, 'unpaid', 'pending', ?)";
        $itemStmt = $conn->prepare($itemSql);
        $description = "Meta Lead - Product: $productCode";
        $itemStmt->bind_param("iiddis", $orderId, $productId, $unitPrice, $unitPrice, $description);
        $itemStmt->execute();
        $itemStmt->close();
        
        return $orderId;
    }


    return false;
}

/**
 * Recalculated CS condition for Meta leads
 */
function cs_condition_meta($conn, $customer_id) {
    if (!$customer_id) return 4;

    $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM order_header WHERE customer_id = ?");
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    $totalOrders = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
    $stmt->close();

    if ($totalOrders == 0) return 4;

    $stmt = $conn->prepare(
        "SELECT COUNT(*) AS failed FROM order_header WHERE customer_id = ? AND status IN ('cancel', 'return', 'return complete', 'return_handover', 'return pending', 'return transfer','removed')"
    );
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    $failedOrders = $stmt->get_result()->fetch_assoc()['failed'] ?? 0;
    $stmt->close();

    if ($failedOrders == 0) return 0;

    $rate = ($failedOrders / $totalOrders) * 100;
    if ($rate <= 25) return 0;
    if ($rate <= 50) return 1;
    if ($rate <= 75) return 2;
    return 3;
}
?>
