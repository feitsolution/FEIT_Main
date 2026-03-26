<?php
/**
 * Global Cron - Invoice Generation
 * 
 * This script is intended to be run daily (e.g., via a system cron job).
 * it generates pending invoices for customers whose billing_date matches today's day of the month.
 */

// Include database connection
// Using absolute path or relative to script location
$base_dir = __DIR__;
require_once $base_dir . '/db_connection.php';
require_once $base_dir . '/functions.php';

// Get today's day of the month
$today_day = (int)date('j');
$current_month_year = date('F Y');
$issue_date = date('Y-m-d');
$due_date = date('Y-m-d', strtotime('+3 days'));
//$due_date = date('Y-m-t'); // last day of the month
//$due_date = date('Y-m-t', strtotime($issue_date));

try {
    $query = "SELECT c.customer_id, c.name, c.email, c.product_id, c.package_id, c.order_count, cp.amount as custom_amount, p.amount as package_default_amount
              FROM customers c
              JOIN packages p ON c.package_id = p.id
              LEFT JOIN customer_packages cp ON c.customer_id = cp.customer_id AND c.package_id = cp.package_id
              WHERE c.status = 'Active' AND c.billing_date = ? AND c.product_id IS NOT NULL AND c.package_id IS NOT NULL";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $today_day);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $processed_count = 0;
    $skipped_count = 0;
    $error_count = 0;

    while ($customer = $result->fetch_assoc()) {
        $customer_id = $customer['customer_id'];
        $customer_name = $customer['name'];
        $product_id = $customer['product_id'];
        $package_id = $customer['package_id'];
        $order_count = $customer['order_count'];
        
        // Priority: custom amount > default package amount
        $amount = $customer['custom_amount'] ?? $customer['package_default_amount'];
        
        // Check if invoice already exists for this customer in the current month
        // This prevents duplicate generation if the cron runs twice in one day
        $check_query = "SELECT invoice_id FROM invoices 
                       WHERE customer_id = ? 
                       AND issue_date LIKE '$current_month_year-%'";
        $check_stmt = $conn->prepare($check_query);
        $check_stmt->bind_param("i", $customer_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $skipped_count++;
            continue;
        }
        
        // Create Invoice
        $conn->begin_transaction();
        
        try {
            $currency = 'lkr'; 
            $status = 'pending';
            $pay_status = 'unpaid';
            $notes = "Thank you for choosing our services. Please review the invoice details and ensure payment is completed by the due date. If there are any discrepancies, kindly inform us immediately.";
            $user_id = 1; 
            
            $insert_invoice = "INSERT INTO invoices (
                customer_id, user_id, issue_date, due_date, 
                subtotal, discount, total_amount, 
                notes, currency, status, pay_status, created_by
            ) VALUES (?, ?, ?, ?, ?, 0, ?, ?, ?, ?, ?, ?)";
            
            $stmt_inv = $conn->prepare($insert_invoice);
            $stmt_inv->bind_param(
                "iissddssssi",
                $customer_id, $user_id, $issue_date, $due_date,
                $amount, $amount, $notes, $currency, $status, $pay_status, $user_id
            );
            
            if (!$stmt_inv->execute()) {
                throw new Exception("Failed to insert invoice for $customer_name: " . $stmt_inv->error);
            }
            
            $invoice_id = $conn->insert_id;
            
            // Create Invoice Item
            $insert_item = "INSERT INTO invoice_items (
                invoice_id, product_id, discount, 
                total_amount, pay_status, status, description
            ) VALUES (?, ?, 0, ?, ?, ?, ?)";
            
            $stmt_item = $conn->prepare($insert_item);
            $item_desc = "Subscription Fee for {$current_month_year} \n (Orders count: {$order_count})";
            $stmt_item->bind_param(
                "iidsss",
                $invoice_id, $product_id, $amount, $pay_status, $status, $item_desc
            );
            
            if (!$stmt_item->execute()) {
                throw new Exception("Failed to insert invoice item for $customer_name: " . $stmt_item->error);
            }
            
            $conn->commit();
            $processed_count++;
            
        } catch (Exception $e) {
            $conn->rollback();
            $error_count++;
        }
    }
    echo "Cron finished. Processed: $processed_count, Skipped: $skipped_count, Errors: $error_count\n";

} catch (Exception $e) {
    echo "Critical Cron Error: " . $e->getMessage() . "\n";
}

$conn->close();
?>