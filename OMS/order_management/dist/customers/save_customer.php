<?php
// Start session at the very beginning
session_start();

// Set JSON content type
header('Content-Type: application/json');

// Disable displaying errors but log them
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Unauthorized access check
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized access. Please login again.',
        'redirect' => '/OMS/order_management/dist/pages/login.php'
    ]);
    exit();
}

// DB Connection
include($_SERVER['DOCUMENT_ROOT'] . '/OMS/order_management/dist/connection/db_connection.php');

if (!$conn || $conn->connect_error) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed: ' . ($conn->connect_error ?? 'Unknown error')
    ]);
    exit();
}

// Only POST allowed
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method.'
    ]);
    exit();
}

// CSRF check
if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) ||
    $_POST['csrf_token'] !== $_SESSION['csrf_token']) {

    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid security token. Please refresh the page.'
    ]);
    exit();
}

// Function: Log user actions
function logUserAction($conn, $userId, $actionType, $customerId, $details = '')
{
    try {
        $check = $conn->query("SHOW TABLES LIKE 'user_logs'");
        if ($check && $check->num_rows > 0) {
            $sql = "INSERT INTO user_logs (user_id, action_type, customer_id, details, created_at)
                    VALUES (?, ?, ?, ?, NOW())";

            $stmt = $conn->prepare($sql);
            if ($stmt) {
                $stmt->bind_param("isis", $userId, $actionType, $customerId, $details);
                $stmt->execute();
                $stmt->close();
            }
        }
    } catch (Exception $e) {
        error_log("Log error: " . $e->getMessage());
    }
}

// ================================
// INPUTS
// ================================
$currentUserId = $_SESSION['user_id'] ?? null;

$name = trim($_POST['name'] ?? '');
$email = !empty($_POST['email']) ? strtolower(trim($_POST['email'])) : null;
$phone = trim($_POST['phone'] ?? '');
$phone_2 = !empty($_POST['phone_2']) ? trim($_POST['phone_2']) : null;
$status = trim($_POST['status'] ?? 'Active');
$address1 = trim($_POST['address_line1'] ?? '');
$address2 = !empty($_POST['address_line2']) ? trim($_POST['address_line2']) : null;
$city_id = intval($_POST['city_id'] ?? 0);

// ================================
// VALIDATION
// ================================
$errors = [];

// Name
if (empty($name)) $errors['name'] = 'Customer name is required';
elseif (strlen($name) < 2) $errors['name'] = 'Name must be at least 2 characters';
elseif (strlen($name) > 255) $errors['name'] = 'Name cannot exceed 255 characters';

// Email
if (!empty($email)) {
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Invalid email format';
    } else {
        $stmt = $conn->prepare("SELECT customer_id FROM customers WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $exists = $stmt->get_result();

        if ($exists->num_rows > 0) {
            $errors['email'] = 'This email address is already registered';
        }
        $stmt->close();
    }
}

// Phone
$phoneDigits = preg_replace('/\D/', '', $phone);

if (strlen($phoneDigits) !== 10) {
    $errors['phone'] = 'Phone number must be exactly 10 digits';
} else {
    $stmt = $conn->prepare("SELECT customer_id FROM customers WHERE phone = ? OR phone_2 = ?");
    $stmt->bind_param("ss", $phoneDigits, $phoneDigits);
    $stmt->execute();
    $exists = $stmt->get_result();

    if ($exists->num_rows > 0) {
        $errors['phone'] = 'This phone number is already registered';
    }
    $stmt->close();
}

$phone = $phoneDigits;

// Phone 2 (optional)
if (!empty($phone_2)) {
    $phone2Digits = preg_replace('/\D/', '', $phone_2);

    if (strlen($phone2Digits) !== 10) {
        $errors['phone_2'] = 'Secondary phone must be exactly 10 digits';
    } elseif ($phone2Digits === $phone) {
        $errors['phone_2'] = 'Secondary phone must be different from primary';
    } else {
        $stmt = $conn->prepare("SELECT customer_id FROM customers WHERE phone = ? OR phone_2 = ?");
        $stmt->bind_param("ss", $phone2Digits, $phone2Digits);
        $stmt->execute();
        $exists = $stmt->get_result();

        if ($exists->num_rows > 0) {
            $errors['phone_2'] = 'This secondary phone number is already registered';
        }
        $stmt->close();
    }

    $phone_2 = $phone2Digits;
}

// Address
if (empty($address1)) {
    $errors['address_line1'] = 'Address Line 1 is required';
}

// City
if ($city_id <= 0) {
    $errors['city_id'] = 'Please select a valid city';
}

// ================================
// RETURN ERRORS
// ================================
if (!empty($errors)) {
    http_response_code(422);
    echo json_encode([
        'success' => false,
        'message' => 'Please correct the validation errors and try again.',
        'errors' => $errors
    ]);
    exit();
}

// ================================
// INSERT CUSTOMER
// ================================
try {
    $conn->begin_transaction();

    $sql = "INSERT INTO customers 
            (name, email, phone, phone_2, status, address_line1, address_line2, city_id, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        "sssssssi",
        $name,
        $email,
        $phone,
        $phone_2,
        $status,
        $address1,
        $address2,
        $city_id
    );

    if (!$stmt->execute()) {
        throw new Exception("Insert failed: " . $stmt->error);
    }

    $customer_id = $conn->insert_id;
    $stmt->close();

    // Log
    $logDetails = "Customer created: {$name}, {$phone}";
    logUserAction($conn, $currentUserId, "customer_create", $customer_id, $logDetails);

    $conn->commit();

    http_response_code(201);
    echo json_encode([
        'success' => true,
        'message' => "Customer \"$name\" has been successfully added!",
        'customer_id' => $customer_id
    ]);

} catch (Exception $e) {
    $conn->rollback();
    error_log("Customer insert error: " . $e->getMessage());

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'A server error occurred. Please try again.'
    ]);
}

$conn->close();
exit();
?>
