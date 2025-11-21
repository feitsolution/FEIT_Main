<?php
// Disable error reporting for production
error_reporting(0);

// Clear any existing output buffers
while (ob_get_level()) {
    ob_end_clean();
}

// Start a new output buffer
ob_start();

// Include necessary files
require_once 'db_connection.php';
require_once 'functions.php';

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // Validate required fields
        if (empty($_POST['customer_name'])) {
            throw new Exception("Customer name is required.");
        }

        // Check if products are added
        if (empty($_POST['invoice_product'])) {
            throw new Exception("At least one product must be added to the invoice.");
        }

        // Begin transaction
        $conn->begin_transaction();
        
        // Get current user ID from session (default to 1 if not set)
        $user_id = $_SESSION['user_id'] ?? 1;
        
        // Process customer details
        $customer_name = trim($_POST['customer_name']);
        $customer_email = $_POST['customer_email'] ?? '';
        $customer_address = $_POST['customer_address'] ?? 'No. 12, Galle Road, Colombo, Sri Lanka';
        $customer_phone = $_POST['customer_phone'] ?? '+94712345678';
        
        // Find or create customer
        $customer_id = 0;
        $checkCustomerSql = "SELECT customer_id FROM customers WHERE name = ? AND email = ?";
        $stmt = $conn->prepare($checkCustomerSql);
        $stmt->bind_param("ss", $customer_name, $customer_email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $customer = $result->fetch_assoc();
            $customer_id = $customer['customer_id'];
        } else {
            // Insert new customer
            $insertCustomerSql = "INSERT INTO customers (name, email, phone, address, status) 
                                 VALUES (?, ?, ?, ?, 'Active')";
            $stmt = $conn->prepare($insertCustomerSql);
            $stmt->bind_param("ssss", $customer_name, $customer_email, $customer_phone, $customer_address);
            $stmt->execute();
            $customer_id = $conn->insert_id;
        }
        
        // Prepare invoice details
        $invoice_date = date('Y-m-d');
        $due_date = date('Y-m-d', strtotime('+30 days'));
        $notes = "Once the invoice has been verified by the accounts payable team and recorded, the only task left is to send it for approval before releasing the payment";
        
        // Get currency from form input instead of hardcoding it
        $currency = isset($_POST['invoice_currency']) ? strtolower($_POST['invoice_currency']) : 'lkr';
        
        // Get invoice status from form
        $invoice_status = $_POST['invoice_status'] ?? 'Unpaid';
        $pay_status = $invoice_status === 'Paid' ? 'paid' : 'unpaid';
        $pay_date = $invoice_status === 'Paid' ? date('Y-m-d') : null;
        $status = $invoice_status === 'Paid' ? 'done' : 'pending';
        
        // Detailed calculation of totals
        $products = $_POST['invoice_product'];
        $product_prices = $_POST['invoice_product_price'];
        $discounts = $_POST['invoice_product_discount'] ?? [];
        $product_descriptions = $_POST['invoice_product_description'] ?? [];
        $subtotal = 0;
        $total_discount = 0;
        
        // Prepare an array to store invoice items
        $invoice_items = [];
        foreach ($products as $key => $product_id) {
            $price = floatval($product_prices[$key] ?? 0);
            $discount = floatval($discounts[$key] ?? 0);
            $description = $product_descriptions[$key] ?? '';
            
            // Ensure discount doesn't exceed price
            $discount = min($discount, $price);
            
            // Accumulate totals
            $subtotal += $price;
            $total_discount += $discount;
            
            // Store item details for insertion
            $invoice_items[] = [
                'product_id' => $product_id,
                'price' => $price,
                'discount' => $discount,
                'description' => $description
            ];
        }
        
        // Final total calculation
        $total_amount = $subtotal - $total_discount;
        
        // Insert invoice - UPDATED to include created_by column
        $insertInvoiceSql = "INSERT INTO invoices (
            customer_id, user_id, issue_date, due_date, 
            subtotal, discount, total_amount, 
            notes, currency, status, pay_status, pay_date, created_by
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($insertInvoiceSql);
        $stmt->bind_param(
            "iissdddsssssi", 
            $customer_id, $user_id, $invoice_date, $due_date, 
            $subtotal, $total_discount, $total_amount, 
            $notes, $currency, $status, $pay_status, $pay_date, $user_id
        );
        $stmt->execute();
        $invoice_id = $conn->insert_id;
        
        // Invoice items insertion
        $insertItemSql = "INSERT INTO invoice_items (
            invoice_id, product_id, discount, 
            total_amount, pay_status, status, description
        ) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($insertItemSql);
        
        foreach ($invoice_items as $item) {
            $stmt->bind_param(
                "iidssss", 
                $invoice_id, 
                $item['product_id'], 
                $item['discount'], 
                $item['price'], 
                $pay_status, 
                $status, 
                $item['description']
            );
            $stmt->execute();
        }
        
        // If invoice is marked as Paid, insert into payments table
        if ($invoice_status === 'Paid') {
            // Default payment method to 'Cash'
            $payment_method = 'Cash';
            
            // Insert payment record
            $insertPaymentSql = "INSERT INTO payments (
                invoice_id, 
                amount_paid, 
                payment_method, 
                payment_date, 
                pay_by
            ) VALUES (?, ?, ?, ?, ?)";
            
            $stmt = $conn->prepare($insertPaymentSql);
            $stmt->bind_param(
                "idsss", 
                $invoice_id, 
                $total_amount, 
                $payment_method, 
                $pay_date, 
                $user_id
            );
            $stmt->execute();
        }
        
        // Commit transaction
        $conn->commit();
        
        // Clear all output buffers
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        // Set success message
        $_SESSION['invoice_success'] = "Invoice #" . $invoice_id . " created successfully!";
        
        // Redirect to view invoice page
        header("Location: download_invoice.php?id=" . $invoice_id);
        exit();
        
    } catch (Exception $e) {
        // Rollback transaction
        $conn->rollback();
        
        // Clear all output buffers
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        // Set error message in session
        $_SESSION['invoice_error'] = $e->getMessage();
        
        // Redirect back to invoice creation page
        header("Location: invoice_create.php");
        exit();
    }
} else {
    // Not a POST request
    header("Location: invoice_create.php");
    exit();
}
?>