<?php
// update_user.php
// Enable strict error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start output buffering immediately
ob_start();

// Start session
session_start();

// Require database connection and functions
require_once 'db_connection.php';
require_once 'functions.php';

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    // Clear any existing output
    ob_end_clean();
    header("Location: signin.php");
    exit();
}

// Function to handle errors and redirect
function handleError($errors, $user_id = 0) {
    $_SESSION['error_message'] = is_array($errors) ? implode("<br>", $errors) : $errors;
    
    // Clear output buffer
    ob_end_clean();
    
    if ($user_id) {
        header("Location: add_user.php");
    } else {
        header("Location: add_user.php");
    }
    exit();
}

// Validate and sanitize input
try {
    // Validate CSRF Token
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        throw new Exception("Security token validation failed. Please refresh the page and try again.");
    }

    // Unset the current CSRF token to prevent reuse
    unset($_SESSION['csrf_token']);

    // Sanitize and validate inputs
    $name = trim(filter_input(INPUT_POST, 'name', FILTER_SANITIZE_SPECIAL_CHARS));
    $email = trim(filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL));
    $mobile = trim(filter_input(INPUT_POST, 'mobile', FILTER_SANITIZE_SPECIAL_CHARS));
    $nic = trim(filter_input(INPUT_POST, 'nic', FILTER_SANITIZE_SPECIAL_CHARS));
    $address = trim(filter_input(INPUT_POST, 'address', FILTER_SANITIZE_SPECIAL_CHARS));
    $status = filter_input(INPUT_POST, 'status', FILTER_SANITIZE_SPECIAL_CHARS);
    $role_id = filter_input(INPUT_POST, 'role_id', FILTER_VALIDATE_INT) ?: 0;

    // Input validation
    $errors = [];

    // Name validation
    if (empty($name)) {
        $errors[] = "Name is required.";
    }

    // Email validation
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email address.";
    }

    // Mobile validation (optional, adjust regex as needed)
    if (!empty($mobile) && !preg_match('/^[0-9]{10}$/', $mobile)) {
        $errors[] = "Invalid mobile number.";
    }

    // Role validation
    if (empty($role_id)) {
        $errors[] = "Role selection is required.";
    }

    // Password handling 
    $password = $_POST['password'] ?? null;

    if (!empty($password)) {
        if (strlen($password) < 8) {
            $errors[] = "Password must be at least 8 characters long.";
        }
    }

    // Check for duplicate email
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $errors[] = "Email already in use.";
    }
    $stmt->close();

    // If validation errors exist, handle and redirect
    if (!empty($errors)) {
        handleError($errors);
    }

    // Begin database transaction
    $conn->begin_transaction();

    // Prepare insert query
    if (!empty($password)) {
        // Insert with password 
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $conn->prepare("INSERT INTO users (name, email, mobile, nic, address, status, role_id, password, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("ssssssss", 
            $name, $email, $mobile, $nic, $address, 
            $status, $role_id, $hashed_password
        );
    } else {
        // Insert without password
        $stmt = $conn->prepare("INSERT INTO users (name, email, mobile, nic, address, status, role_id, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("ssssssi", 
            $name, $email, $mobile, $nic, $address, 
            $status, $role_id
        );
    }

    // Execute insert
    if ($stmt->execute()) {
        $conn->commit();
        
        // Clear output buffer
        ob_end_clean();
        
        // Set success message
        $_SESSION['success_message'] = "User added successfully.";
        header("Location: users.php");
        exit();
    } else {
        // Rollback transaction on failure
        $conn->rollback();
        handleError("Failed to add user.", 0);
    }

} catch (Exception $e) {
    // Handle any unexpected errors
    handleError($e->getMessage());
} finally {
    // Ensure connection is closed
    $conn->close();
    
    // Ensure output buffer is sent if not already done
    if (ob_get_level() > 0) {
        ob_end_flush();
    }
}
?>