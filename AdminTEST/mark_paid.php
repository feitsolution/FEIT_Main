<?php
// Start session
session_start();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

// Include database connection
include 'db_connection.php';

// Check if POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get invoice ID
    $invoice_id = isset($_POST['invoice_id']) ? intval($_POST['invoice_id']) : 0;
    
    if ($invoice_id <= 0) {
        header('HTTP/1.1 400 Bad Request');
        echo json_encode(['error' => 'Invalid invoice ID']);
        exit();
    }
    
    // Check if file was uploaded
    if (!isset($_FILES['payment_slip']) || $_FILES['payment_slip']['error'] !== UPLOAD_ERR_OK) {
        header('HTTP/1.1 400 Bad Request');
        echo json_encode(['error' => 'Payment slip is required']);
        exit();
    }
    
    // Process the uploaded file
    $file = $_FILES['payment_slip'];
    $filename = 'payment_slip_' . $invoice_id . '_' . time() . '.' . pathinfo($file['name'], PATHINFO_EXTENSION);
    $uploadDir = 'uploads/payments/';
    
    // Create directory if it doesn't exist
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $uploadPath = $uploadDir . $filename;
    
    // Get current user ID from session
    $pay_by = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
            throw new Exception('Failed to upload payment slip');
        }
        
        // Get the invoice amount from the invoices table
        $amountQuery = "SELECT total_amount FROM invoices WHERE invoice_id = ?";
        $amountStmt = $conn->prepare($amountQuery);
        $amountStmt->bind_param("i", $invoice_id);
        $amountStmt->execute();
        $amountResult = $amountStmt->get_result();
        
        if ($amountResult->num_rows === 0) {
            throw new Exception('Invoice not found');
        }
        
        $invoiceData = $amountResult->fetch_assoc();
        $amount_paid = $invoiceData['total_amount'];
        
        // Current date and time
        $currentDateTime = date('Y-m-d H:i:s');
        $currentDate = date('Y-m-d');
        
        // Default payment method
        $payment_method = 'Cash'; // You can modify this if you collect payment method from the form
        
        // Update invoice status in the invoices table
        $invoiceStmt = $conn->prepare("UPDATE invoices SET pay_status = 'paid', pay_date = ?, slip = ?, status = 'done', pay_by = ? WHERE invoice_id = ?");
        $invoiceStmt->bind_param("ssis", $currentDate, $filename, $pay_by, $invoice_id);
        if (!$invoiceStmt->execute()) {
            throw new Exception('Failed to update invoice: ' . $conn->error);
        }
        
        // Update invoice_items table
        $itemsStmt = $conn->prepare("UPDATE invoice_items SET status = 'done', pay_status = 'paid' WHERE invoice_id = ?");
        $itemsStmt->bind_param("i", $invoice_id);
        if (!$itemsStmt->execute()) {
            throw new Exception('Failed to update invoice items: ' . $conn->error);
        }
        
        // Insert payment record into payments table
        $paymentStmt = $conn->prepare("INSERT INTO payments (invoice_id, amount_paid, payment_method, payment_date, pay_by) VALUES (?, ?, ?, ?, ?)");
        $paymentStmt->bind_param("idssi", $invoice_id, $amount_paid, $payment_method, $currentDateTime, $pay_by);
        if (!$paymentStmt->execute()) {
            throw new Exception('Failed to record payment: ' . $conn->error);
        }
        
        // Commit transaction
        $conn->commit();
        echo json_encode(['success' => true]);
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        
        header('HTTP/1.1 500 Internal Server Error');
        echo json_encode(['error' => $e->getMessage()]);
    }
    
} else {
    header('HTTP/1.1 405 Method Not Allowed');
    echo json_encode(['error' => 'Method not allowed']);
}
?>