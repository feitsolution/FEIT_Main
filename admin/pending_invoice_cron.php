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

// Ensure logs directory exists
$log_dir = $base_dir . '/logs';
if (!is_dir($log_dir)) {
    mkdir($log_dir, 0755, true);
}

// Set up logging
$log_file = $log_dir . '/cron_invoice_generation.log';
function log_message($message) {
    global $log_file;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($log_file, "[$timestamp] $message" . PHP_EOL, FILE_APPEND);
}

log_message("Starting Cron Process...");

// 1. Get today's day of the month
$today_day = (int)date('j');
$current_month_year = date('Y-m');
$issue_date = date('Y-m-d');
$due_date = date('Y-m-d', strtotime('+7 days')); // 7 days due by default for automated invoices

// Check if it's the correct time window (2:00 AM - 2:59 AM)
// This ensures invoice generation runs AFTER order count sync (1:00 AM)
$current_hour = (int)date('G');
if ($current_hour !== 2) {
    log_message("Not in invoice generation time window (2 AM). Current hour: $current_hour. Skipping.");
    echo "Not in invoice generation time window. Current hour: $current_hour. Skipping.\n";
    exit;
}

log_message("Checking for customers with billing date: $today_day");

try {
    // 2. Find active customers with matching billing date
    // We only process customers who have a product and package assigned
    $query = "SELECT c.customer_id, c.name, c.email, c.product_id, c.package_id, cp.amount as custom_amount, p.amount as package_default_amount
              FROM customers c
              JOIN packages p ON c.package_id = p.id
              LEFT JOIN customer_packages cp ON c.customer_id = cp.customer_id AND c.package_id = cp.package_id
              WHERE c.status = 'active' AND c.billing_date = ? AND c.product_id IS NOT NULL AND c.package_id IS NOT NULL";
    
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
        
        // Priority: custom amount > default package amount
        $amount = $customer['custom_amount'] ?? $customer['package_default_amount'];
        
        // 3. Check if invoice already exists for this customer in the current month
        // This prevents duplicate generation if the cron runs twice in one day
        $check_query = "SELECT invoice_id FROM invoices 
                       WHERE customer_id = ? 
                       AND issue_date LIKE '$current_month_year-%'";
        $check_stmt = $conn->prepare($check_query);
        $check_stmt->bind_param("i", $customer_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            log_message("Skipping Customer #$customer_id ($customer_name) - Invoice already exists for this month.");
            $skipped_count++;
            continue;
        }
        
        // 4. Create Invoice
        $conn->begin_transaction();
        
        try {
            $currency = 'lkr'; // Default to LKR for automated billing
            $status = 'pending';
            $pay_status = 'unpaid';
            $notes = "Automated Monthly Billing for $current_month_year";
            $user_id = 1; // System/Admin user
            
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
            
            // 5. Create Invoice Item
            $insert_item = "INSERT INTO invoice_items (
                invoice_id, product_id, discount, 
                total_amount, pay_status, status, description
            ) VALUES (?, ?, 0, ?, ?, ?, ?)";
            
            $stmt_item = $conn->prepare($insert_item);
            $item_desc = "Recurring Package Fee - " . date('F Y');
            $stmt_item->bind_param(
                "iidsss",
                $invoice_id, $product_id, $amount, $pay_status, $status, $item_desc
            );
            
            if (!$stmt_item->execute()) {
                throw new Exception("Failed to insert invoice item for $customer_name: " . $stmt_item->error);
            }
            
            $conn->commit();
            log_message("Success: Generated Invoice #$invoice_id for $customer_name (Amount: $amount)");
            $processed_count++;
            
        } catch (Exception $e) {
            $conn->rollback();
            log_message("Error processing $customer_name: " . $e->getMessage());
            $error_count++;
        }
    }
    
    log_message("Cron finished. Processed: $processed_count, Skipped: $skipped_count, Errors: $error_count");
    echo "Cron finished. Processed: $processed_count, Skipped: $skipped_count, Errors: $error_count\n";

} catch (Exception $e) {
    log_message("Critical Cron Error: " . $e->getMessage());
    echo "Critical Cron Error: " . $e->getMessage() . "\n";
}

$conn->close();
?>