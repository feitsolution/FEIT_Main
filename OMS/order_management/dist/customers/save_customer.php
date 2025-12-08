<?php
// Start session at the very beginning
session_start();

// Set JSON content type
header('Content-Type: application/json');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized access. Please login again.',
        'redirect' => '/OMS/order_management/dist/pages/login.php'
    ]);
    exit();
}

// Include the database connection file
include($_SERVER['DOCUMENT_ROOT'] . '/OMS/order_management/dist/connection/db_connection.php');

// Check database connection
if (!isset($conn) || $conn->connect_error) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed: ' . ($conn->connect_error ?? 'Unknown error')
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
function logUserAction($conn, $userId, $actionType, $inquiryId, $details = '') {
    try {
        $tableCheck = $conn->query("SHOW TABLES LIKE 'user_logs'");
        if ($tableCheck && $tableCheck->num_rows > 0) {
            $logSql = "INSERT INTO user_logs (user_id, action_type, inquiry_id, details, created_at) VALUES (?, ?, ?, ?, NOW())";
            $logStmt = $conn->prepare($logSql);
            
            if ($logStmt) {
                $logStmt->bind_param("isis", $userId, $actionType, $inquiryId, $details);
                $result = $logStmt->execute();
                if (!$result) {
                    error_log("Failed to log user action: " . $logStmt->error);
                }
                $logStmt->close();
                return $result;
            } else {
                error_log("Failed to prepare log statement: " . $conn->error);
            }
        }
        return true;
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
            'redirect' => '/OMS/order_management/dist/pages/login.php'
        ]);
        exit();
    }

    // Get and sanitize form data
    $name = trim($_POST['name'] ?? '');
    $email = !empty($_POST['email']) ? strtolower(trim($_POST['email'])) : null;
    $phone = trim($_POST['phone'] ?? '');
    $status = trim($_POST['status'] ?? 'Active');
    $address_line1 = trim($_POST['address_line1'] ?? '');
    $address_line2 = trim($_POST['address_line2'] ?? '');
    $city_id = isset($_POST['city_id']) ? intval($_POST['city_id']) : 0;

    // Server-side validation
    $errors = [];

    // Validate name
    if (empty($name)) {
        $errors['name'] = 'Customer name is required';
    } elseif (strlen($name) < 2) {
        $errors['name'] = 'Name must be at least 2 characters';
    } elseif (strlen($name) > 255) {
        $errors['name'] = 'Name is too long (max 255 characters)';
    } elseif (!preg_match("/^[a-zA-Z\s.\-']+$/u", $name)) {
        $errors['name'] = 'Name contains invalid characters';
    }

    // Validate email (optional)
    if (!empty($email)) {
        if (strlen($email) > 100) {
            $errors['email'] = 'Email is too long (max 100 characters)';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Invalid email format';
        }
    }

    // Validate phone
    if (empty($phone)) {
        $errors['phone'] = 'Phone number is required';
    } else {
        $phoneDigits = preg_replace('/\D/', '', $phone);
        if (strlen($phoneDigits) !== 10) {
            $errors['phone'] = 'Phone must be exactly 10 digits';
        } elseif (!preg_match('/^0[1-9][0-9]{8}$/', $phoneDigits)) {
            $errors['phone'] = 'Invalid Sri Lankan phone format';
        }
        $phone = $phoneDigits;
    }

    // Validate address
    if (empty($address_line1)) {
        $errors['address_line1'] = 'Address Line 1 is required';
    } elseif (strlen($address_line1) < 3) {
        $errors['address_line1'] = 'Address is too short (minimum 3 characters)';
    } elseif (strlen($address_line1) > 255) {
        $errors['address_line1'] = 'Address is too long (max 255 characters)';
    }

    // Validate address line 2 length if provided
    if (!empty($address_line2) && strlen($address_line2) > 255) {
        $errors['address_line2'] = 'Address Line 2 is too long (max 255 characters)';
    }

    // Validate city
    if (empty($city_id) || $city_id <= 0) {
        $errors['city_id'] = 'Please select a valid city';
    }

    // Validate status
    if (!in_array($status, ['Active', 'Inactive'])) {
        $errors['status'] = 'Invalid status value';
        $status = 'Active';
    }

    // Check for duplicate email (if email is provided)
    if (empty($errors['email']) && !empty($email)) {
        $emailCheckStmt = $conn->prepare("SELECT customer_id FROM customers WHERE email = ?");
        if (!$emailCheckStmt) {
            throw new Exception("Email check prepare failed: " . $conn->error);
        }
        $emailCheckStmt->bind_param("s", $email);
        $emailCheckStmt->execute();
        $emailCheckResult = $emailCheckStmt->get_result();
        
        if ($emailCheckResult->num_rows > 0) {
            $errors['email'] = 'This email address is already registered';
        }
        $emailCheckStmt->close();
    }

    // Check for duplicate phone
    if (empty($errors['phone']) && !empty($phone)) {
        $phoneCheckStmt = $conn->prepare("SELECT customer_id FROM customers WHERE phone = ?");
        if (!$phoneCheckStmt) {
            throw new Exception("Phone check prepare failed: " . $conn->error);
        }
        $phoneCheckStmt->bind_param("s", $phone);
        $phoneCheckStmt->execute();
        $phoneCheckResult = $phoneCheckStmt->get_result();
        
        if ($phoneCheckResult->num_rows > 0) {
            $errors['phone'] = 'This phone number is already registered';
        }
        $phoneCheckStmt->close();
    }

    // Validate city exists and is active
    if (empty($errors['city_id']) && $city_id > 0) {
        $cityCheckStmt = $conn->prepare("SELECT city_id FROM city_table WHERE city_id = ? AND is_active = 1");
        if (!$cityCheckStmt) {
            throw new Exception("City check prepare failed: " . $conn->error);
        }
        $cityCheckStmt->bind_param("i", $city_id);
        $cityCheckStmt->execute();
        $cityCheckResult = $cityCheckStmt->get_result();
        
        if ($cityCheckResult->num_rows === 0) {
            $errors['city_id'] = 'Selected city is not valid or inactive';
        }
        $cityCheckStmt->close();
    }

    // If there are validation errors, return them
    if (!empty($errors)) {
        $response['errors'] = $errors;
        $response['message'] = 'Please correct the validation errors and try again.';
        http_response_code(422);
        echo json_encode($response);
        exit();
    }

    // Start transaction
    $conn->begin_transaction();

    try {
        // Prepare the INSERT statement with proper NULL handling for email
        $sql = "INSERT INTO customers (name, email, phone, status, address_line1, address_line2, city_id, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
        
        $insertStmt = $conn->prepare($sql);
        
        if (!$insertStmt) {
            throw new Exception("Failed to prepare insert statement: " . $conn->error);
        }

        // Bind parameters - handle NULL email properly
        $insertStmt->bind_param("ssssssi", 
            $name, 
            $email,  // This will be NULL if empty
            $phone, 
            $status, 
            $address_line1, 
            $address_line2, 
            $city_id
        );

        if (!$insertStmt->execute()) {
            throw new Exception("Failed to execute insert: " . $insertStmt->error);
        }

        $customer_id = $conn->insert_id;
        
        if ($customer_id <= 0) {
            throw new Exception("Failed to get customer ID after insert");
        }

        $insertStmt->close();

        // Log customer creation action
        $logDetails = "New customer added - Name: {$name}, Email: " . ($email ?? 'N/A') . ", Phone: {$phone}, Status: {$status}";
        logUserAction($conn, $currentUserId, 'customer_create', $customer_id, $logDetails);

        // Commit transaction
        $conn->commit();

        // Success response
        $response['success'] = true;
        $response['message'] = 'Customer "' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '" has been successfully added!';
        $response['customer_id'] = $customer_id;
        $response['data'] = [
            'id' => $customer_id,
            'name' => htmlspecialchars($name, ENT_QUOTES, 'UTF-8'),
            'email' => $email ? htmlspecialchars($email, ENT_QUOTES, 'UTF-8') : '',
            'phone' => $phone,
            'status' => $status
        ];

        error_log("Customer added successfully - ID: $customer_id, Name: $name, Added by User ID: $currentUserId");

        http_response_code(201);

    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }

} catch (Exception $e) {
    error_log("Error adding customer: " . $e->getMessage());
    error_log("File: " . $e->getFile() . " Line: " . $e->getLine());
    error_log("Stack trace: " . $e->getTraceAsString());

    $response['success'] = false;
    $response['message'] = 'Database error: ' . $e->getMessage();
    
    http_response_code(500);

} finally {
    if (isset($conn) && $conn instanceof mysqli) {
        $conn->close();
    }
}

echo json_encode($response);
exit();
?>