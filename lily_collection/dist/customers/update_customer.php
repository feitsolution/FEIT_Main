<?php
// Start session at the very beginning
session_start();

// Set JSON content type
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized access. Please login again.',
        'redirect' => '/lily_collection/dist/pages/login.php'
    ]);
    exit();
}

// Include the database connection file
include($_SERVER['DOCUMENT_ROOT'] . '/lily_collection/dist/connection/db_connection.php');

// Check database connection
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed. Please try again later.'
    ]);
    exit();
}

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method.'
    ]);
    exit();
}

// CSRF token validation
if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || 
    $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid security token. Please refresh the page and try again.'
    ]);
    exit();
}

// Function to log user actions
function logUserAction($conn, $userId, $actionType, $targetId, $details = '') {
    try {
        $logSql = "INSERT INTO user_logs (user_id, action_type, inquiry_id, details, created_at) VALUES (?, ?, ?, ?, NOW())";
        $logStmt = $conn->prepare($logSql);
        
        if (!$logStmt) {
            error_log("Failed to prepare user log statement: " . $conn->error);
            return false;
        }
        
        $logStmt->bind_param("isis", $userId, $actionType, $targetId, $details);
        $result = $logStmt->execute();
        
        if (!$result) {
            error_log("Failed to log user action: " . $logStmt->error);
        }
        
        $logStmt->close();
        return $result;
    } catch (Exception $e) {
        error_log("Exception in logUserAction: " . $e->getMessage());
        return false;
    }
}

// Initialize response array
$response = [
    'success' => false,
    'message' => '',
    'errors' => []
];

try {
    // Get current user ID for logging
    $currentUserId = $_SESSION['user_id'] ?? null;
    if (!$currentUserId) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'User session not found. Please login again.',
            'redirect' => '/lily_collection/dist/pages/login.php'
        ]);
        exit();
    }

    // Get and sanitize form data
    $customer_id = intval($_POST['customer_id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $phone_2 = trim($_POST['phone_2'] ?? '');
    $status = trim($_POST['status'] ?? 'Active');
    $address_line1 = trim($_POST['address_line1'] ?? '');
    $address_line2 = trim($_POST['address_line2'] ?? '');
    $city_id = intval($_POST['city_id'] ?? 0);

    // Validate customer ID
    if ($customer_id <= 0) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Invalid customer ID.'
        ]);
        exit();
    }

    // Check if customer exists and get all current data including city name for comparison
    $customerCheckStmt = $conn->prepare("
        SELECT c.customer_id, c.name, c.email, c.phone, c.phone_2, c.status, 
               c.address_line1, c.address_line2, c.city_id, ct.city_name 
        FROM customers c
        LEFT JOIN city_table ct ON c.city_id = ct.city_id
        WHERE c.customer_id = ?
    ");
    $customerCheckStmt->bind_param("i", $customer_id);
    $customerCheckStmt->execute();
    $customerCheckResult = $customerCheckStmt->get_result();
    
    if ($customerCheckResult->num_rows === 0) {
        $customerCheckStmt->close();
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Customer not found.'
        ]);
        exit();
    }
    
    $existingCustomer = $customerCheckResult->fetch_assoc();
    $customerCheckStmt->close();

    // Essential server-side validation
    $errors = [];

    // Name validation (required)
    if (empty($name)) {
        $errors['name'] = 'Customer name is required';
    } elseif (strlen($name) < 2) {
        $errors['name'] = 'Name must be at least 2 characters long';
    } elseif (strlen($name) > 255) {
        $errors['name'] = 'Name is too long (maximum 255 characters)';
    } elseif (!preg_match("/^[a-zA-Z\s.\-']+$/", $name)) {
        $errors['name'] = 'Name can only contain letters, spaces, dots, hyphens, and apostrophes';
    }

    // Email validation (OPTIONAL - only validate if provided)
    if (!empty($email)) {
        if (strlen($email) > 100) {
            $errors['email'] = 'Email address is too long (maximum 100 characters)';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Please enter a valid email address';
        }
    }

    // Phone validation (required)
    if (empty($phone)) {
        $errors['phone'] = 'Phone number is required';
    } elseif (strlen($phone) > 20) {
        $errors['phone'] = 'Phone number is too long (maximum 20 characters)';
    } else {
        // Validate Sri Lankan phone format
        $cleanPhone = preg_replace('/\s+/', '', $phone);
        $digitsOnly = preg_replace('/[^0-9]/', '', $cleanPhone);
        
        if (strlen($digitsOnly) !== 10) {
            $errors['phone'] = 'Phone number must be exactly 10 digits';
        } elseif (!preg_match('/^0[1-9][0-9]{8}$/', $cleanPhone) && 
                  !preg_match('/^(\+94|94)[1-9][0-9]{8}$/', $cleanPhone)) {
            $errors['phone'] = 'Please enter a valid Sri Lankan phone number (e.g., 0771234567)';
        }
    }

    // Phone 2 validation (OPTIONAL - only validate if provided)
    if (!empty($phone_2)) {
        if (strlen($phone_2) > 20) {
            $errors['phone_2'] = 'Phone number 2 is too long (maximum 20 characters)';
        } else {
            // Validate Sri Lankan phone format
            $cleanPhone2 = preg_replace('/\s+/', '', $phone_2);
            $digitsOnly2 = preg_replace('/[^0-9]/', '', $cleanPhone2);
            
            if (strlen($digitsOnly2) !== 10) {
                $errors['phone_2'] = 'Phone number 2 must be exactly 10 digits';
            } elseif (!preg_match('/^0[1-9][0-9]{8}$/', $cleanPhone2) && 
                      !preg_match('/^(\+94|94)[1-9][0-9]{8}$/', $cleanPhone2)) {
                $errors['phone_2'] = 'Please enter a valid Sri Lankan phone number (e.g., 0771234567)';
            }
        }
        
        // Check if phone_2 is the same as phone
        if (!empty($phone) && $phone === $phone_2) {
            $errors['phone_2'] = 'Phone number 2 cannot be the same as the primary phone number';
        }
    }

    // Address Line 1 validation (required)
    if (empty($address_line1)) {
        $errors['address_line1'] = 'Address Line 1 is required';
    } elseif (strlen($address_line1) < 3) {
        $errors['address_line1'] = 'Address Line 1 must be at least 3 characters long';
    } elseif (strlen($address_line1) > 255) {
        $errors['address_line1'] = 'Address Line 1 is too long (maximum 255 characters)';
    }

    // Address Line 2 validation (optional but validate if provided)
    if (!empty($address_line2) && strlen($address_line2) > 255) {
        $errors['address_line2'] = 'Address Line 2 is too long (maximum 255 characters)';
    }

    // City validation (required)
    if (empty($city_id) || $city_id <= 0) {
        $errors['city_id'] = 'City selection is required. Please select a city from the dropdown.';
    }

    // Check for duplicate email (only if email is provided and changed)
    if (!empty($email) && $email !== $existingCustomer['email']) {
        $emailCheckStmt = $conn->prepare("SELECT customer_id FROM customers WHERE email = ? AND customer_id != ?");
        $emailCheckStmt->bind_param("si", $email, $customer_id);
        $emailCheckStmt->execute();
        $emailCheckResult = $emailCheckStmt->get_result();
        
        if ($emailCheckResult->num_rows > 0) {
            $errors['email'] = 'Email address already exists. Please use a different email.';
        }
        $emailCheckStmt->close();
    }

    // Check for duplicate phone (excluding current customer)
    if (!empty($phone) && $phone !== $existingCustomer['phone']) {
        $phoneCheckStmt = $conn->prepare("SELECT customer_id FROM customers WHERE phone = ? AND customer_id != ?");
        $phoneCheckStmt->bind_param("si", $phone, $customer_id);
        $phoneCheckStmt->execute();
        $phoneCheckResult = $phoneCheckStmt->get_result();
        
        if ($phoneCheckResult->num_rows > 0) {
            $errors['phone'] = 'Phone number already exists. Please use a different phone number.';
        }
        $phoneCheckStmt->close();
    }

    // Check for duplicate phone_2 (only if provided and changed)
    if (!empty($phone_2) && $phone_2 !== ($existingCustomer['phone_2'] ?? '')) {
        // Check against both phone and phone_2 fields in other customers
        $phone2CheckStmt = $conn->prepare("
            SELECT customer_id 
            FROM customers 
            WHERE (phone = ? OR phone_2 = ?) 
            AND customer_id != ?
        ");
        $phone2CheckStmt->bind_param("ssi", $phone_2, $phone_2, $customer_id);
        $phone2CheckStmt->execute();
        $phone2CheckResult = $phone2CheckStmt->get_result();
        
        if ($phone2CheckResult->num_rows > 0) {
            $errors['phone_2'] = 'Phone number 2 already exists. Please use a different phone number.';
        }
        $phone2CheckStmt->close();
    }

    // Validate city exists and is active - ENHANCED validation for autocomplete
    if ($city_id > 0) {
        $cityCheckStmt = $conn->prepare("
            SELECT city_id, city_name, postal_code, is_active 
            FROM city_table 
            WHERE city_id = ?
        ");
        $cityCheckStmt->bind_param("i", $city_id);
        $cityCheckStmt->execute();
        $cityCheckResult = $cityCheckStmt->get_result();
        
        if ($cityCheckResult->num_rows === 0) {
            $errors['city_id'] = 'Selected city does not exist. Please select a valid city.';
        } else {
            $cityData = $cityCheckResult->fetch_assoc();
            
            // Check if city is active
            if ($cityData['is_active'] != 1) {
                $errors['city_id'] = 'Selected city "' . htmlspecialchars($cityData['city_name']) . '" is inactive. Please select an active city.';
            }
        }
        $cityCheckStmt->close();
    }

    // Validate status (security check)
    if (!in_array($status, ['Active', 'Inactive'])) {
        $status = 'Active'; // Default to Active if invalid
    }

    // If there are validation errors, return them
    if (!empty($errors)) {
        $response['errors'] = $errors;
        $response['message'] = 'Please correct the errors and try again.';
        echo json_encode($response);
        exit();
    }

    // Check if any data has actually changed - ENHANCED to include city name and phone_2
    $hasChanges = false;
    $changes = [];

    if ($name !== $existingCustomer['name']) {
        $hasChanges = true;
        $changes[] = "Name: '{$existingCustomer['name']}' → '{$name}'";
    }
    
    // Handle email change (including empty to value or value to empty)
    $existingEmail = $existingCustomer['email'] ?? '';
    if ($email !== $existingEmail) {
        $hasChanges = true;
        $oldEmailDisplay = empty($existingEmail) ? '(empty)' : $existingEmail;
        $newEmailDisplay = empty($email) ? '(empty)' : $email;
        $changes[] = "Email: '{$oldEmailDisplay}' → '{$newEmailDisplay}'";
    }
    
    if ($phone !== $existingCustomer['phone']) {
        $hasChanges = true;
        $changes[] = "Phone: '{$existingCustomer['phone']}' → '{$phone}'";
    }
    
    // Handle phone_2 change (including empty to value or value to empty)
    $existingPhone2 = $existingCustomer['phone_2'] ?? '';
    if ($phone_2 !== $existingPhone2) {
        $hasChanges = true;
        $oldPhone2Display = empty($existingPhone2) ? '(empty)' : $existingPhone2;
        $newPhone2Display = empty($phone_2) ? '(empty)' : $phone_2;
        $changes[] = "Phone 2: '{$oldPhone2Display}' → '{$newPhone2Display}'";
    }
    
    if ($status !== $existingCustomer['status']) {
        $hasChanges = true;
        $changes[] = "Status: '{$existingCustomer['status']}' → '{$status}'";
    }
    
    if ($address_line1 !== $existingCustomer['address_line1']) {
        $hasChanges = true;
        $changes[] = "Address Line 1: '{$existingCustomer['address_line1']}' → '{$address_line1}'";
    }
    
    // Handle address line 2 change (including empty to value or value to empty)
    $existingAddress2 = $existingCustomer['address_line2'] ?? '';
    if ($address_line2 !== $existingAddress2) {
        $hasChanges = true;
        $oldAddr2Display = empty($existingAddress2) ? '(empty)' : $existingAddress2;
        $newAddr2Display = empty($address_line2) ? '(empty)' : $address_line2;
        $changes[] = "Address Line 2: '{$oldAddr2Display}' → '{$newAddr2Display}'";
    }
    
    if ($city_id != $existingCustomer['city_id']) {
        $hasChanges = true;
        
        // Get new city name for better logging
        $newCityStmt = $conn->prepare("SELECT city_name FROM city_table WHERE city_id = ?");
        $newCityStmt->bind_param("i", $city_id);
        $newCityStmt->execute();
        $newCityResult = $newCityStmt->get_result();
        $newCityName = $newCityResult->fetch_assoc()['city_name'] ?? 'Unknown';
        $newCityStmt->close();
        
        $changes[] = "City: '{$existingCustomer['city_name']}' → '{$newCityName}'";
    }

    // If no changes detected, return early without database update
    if (!$hasChanges) {
        $response['success'] = true;
        $response['message'] = 'No changes were made to the customer.';
        $response['customer_id'] = $customer_id;
        $response['data'] = [
            'id' => $customer_id,
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'phone_2' => $phone_2,
            'status' => $status,
            'city_id' => $city_id
        ];
        echo json_encode($response);
        exit();
    }

    // Start transaction
    $conn->begin_transaction();

    // Prepare values - use NULL if empty (optional field best practice)
    $emailValue = !empty($email) ? $email : null;
    $phone2Value = !empty($phone_2) ? $phone_2 : null;
    $address2Value = !empty($address_line2) ? $address_line2 : null;

    // Prepare and execute customer update
    $updateStmt = $conn->prepare("
        UPDATE customers 
        SET name = ?, 
            email = ?, 
            phone = ?, 
            phone_2 = ?,
            status = ?, 
            address_line1 = ?, 
            address_line2 = ?, 
            city_id = ?, 
            updated_at = NOW()
        WHERE customer_id = ?
    ");

    $updateStmt->bind_param("sssssssii", $name, $emailValue, $phone, $phone2Value, $status, $address_line1, $address2Value, $city_id, $customer_id);

    if ($updateStmt->execute()) {
        // Check if any rows were affected
        if ($updateStmt->affected_rows > 0) {
            // Log customer update action with detailed changes
            $logDetails = "Customer updated - " . implode(', ', $changes);
            
            $logResult = logUserAction($conn, $currentUserId, 'customer_update', $customer_id, $logDetails);
            
            if (!$logResult) {
                error_log("Failed to log customer update action for customer ID: $customer_id");
            }
            
            // Commit transaction
            $conn->commit();
            
            // Get updated city name for response
            $cityNameStmt = $conn->prepare("SELECT city_name FROM city_table WHERE city_id = ?");
            $cityNameStmt->bind_param("i", $city_id);
            $cityNameStmt->execute();
            $cityNameResult = $cityNameStmt->get_result();
            $cityName = $cityNameResult->fetch_assoc()['city_name'] ?? '';
            $cityNameStmt->close();
            
            // Success response
            $response['success'] = true;
            $response['message'] = 'Customer "' . htmlspecialchars($name) . '" has been successfully updated.';
            $response['customer_id'] = $customer_id;
            $response['data'] = [
                'id' => $customer_id,
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'phone_2' => $phone_2,
                'status' => $status,
                'city_id' => $city_id,
                'city_name' => $cityName
            ];
            
            // Log success
            error_log("Customer updated successfully - ID: $customer_id, Name: $name, Email: " . ($email ?: 'empty') . ", Phone 2: " . ($phone_2 ?: 'empty') . ", City ID: $city_id, Updated by User ID: $currentUserId");
        } else {
            // No changes were made (this should not happen since we checked above, but keep as fallback)
            $conn->commit();
            $response['success'] = true;
            $response['message'] = 'No changes were made to the customer.';
            $response['customer_id'] = $customer_id;
        }
        
    } else {
        // Rollback transaction
        $conn->rollback();
        
        // Database error
        error_log("Failed to update customer: " . $updateStmt->error);
        $response['message'] = 'Failed to update customer. Please try again.';
    }

    $updateStmt->close();

} catch (Exception $e) {
    // Rollback transaction if it was started
    if (isset($conn) && method_exists($conn, 'inTransaction') && $conn->inTransaction()) {
        $conn->rollback();
    }
    
    // Log error
    error_log("Error updating customer: " . $e->getMessage());
    
    // Return error response
    $response['message'] = 'An unexpected error occurred. Please try again.';
    http_response_code(500);
    
} finally {
    // Close database connection
    if (isset($conn)) {
        $conn->close();
    }
}

// Return JSON response
echo json_encode($response);
exit();
?>