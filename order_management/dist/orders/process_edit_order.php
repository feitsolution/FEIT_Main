<?php
// CRITICAL: Start output buffering FIRST
ob_start();

// Start session AFTER output buffering
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Disable error reporting for production
error_reporting(0);
ini_set('display_errors', 0);

// Include the database connection file
include($_SERVER['DOCUMENT_ROOT'] . '/order_management/dist/connection/db_connection.php');

// Function to log user actions
function logUserAction($conn, $user_id, $action_type, $inquiry_id, $details = null) {
    if (!$conn) return false;
    $stmt = $conn->prepare("INSERT INTO user_logs (user_id, action_type, inquiry_id, details) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isis", $user_id, $action_type, $inquiry_id, $details);
    return $stmt->execute();
}

// Function to parse numeric inputs safely
function parse_numeric($value, $default = 0.00) {
    if (is_array($value)) return $default;
    $clean_value = str_replace(',', '', (string)$value);
    return is_numeric($clean_value) ? floatval($clean_value) : $default;
}

// Function to set session message and redirect
function setMessageAndRedirect($type, $message, $redirect_url = null) {
    $_SESSION["order_{$type}"] = $message;
    if (!$redirect_url) {
        $redirect_url = "/order_management/dist/orders/pending_order_list.php";
    }
    if (ob_get_level()) {
        ob_end_clean();
    }
    header("Location: " . $redirect_url);
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        $order_id = $_POST['order_id'] ?? '';
        if (empty($order_id)) {
            throw new Exception("Order ID is missing.");
        }

        if (empty($_POST['customer_name'])) {
            throw new Exception("Customer name is required.");
        }

        if (empty($_POST['order_product'])) {
            throw new Exception("At least one product must be added to the order.");
        }

        // Begin transaction
        $conn->begin_transaction();

        $user_id = $_SESSION['user_id'] ?? 1;
        $customer_id = !empty($_POST['customer_id']) ? intval($_POST['customer_id']) : null;
        $customer_name = trim($_POST['customer_name']);
        $customer_email = trim($_POST['customer_email'] ?? '');
        $customer_phone = trim($_POST['customer_phone'] ?? '');
        $customer_phone_2 = trim($_POST['customer_phone_2'] ?? '');
        $address_line1 = trim($_POST['address_line1'] ?? '');
        $address_line2 = trim($_POST['address_line2'] ?? '');
        $city_id = !empty($_POST['city_id']) ? intval($_POST['city_id']) : null;

        // Get zone and district from city_table
        $zone_id = null;
        $district_id = null;
        if ($city_id) {
            $cityStmt = $conn->prepare("SELECT zone_id, district_id FROM city_table WHERE city_id = ?");
            $cityStmt->bind_param("i", $city_id);
            $cityStmt->execute();
            $cityResult = $cityStmt->get_result();
            if ($row = $cityResult->fetch_assoc()) {
                $zone_id = $row['zone_id'];
                $district_id = $row['district_id'];
            }
            $cityStmt->close();
        }

        $order_date = $_POST['order_date'] ?? date('Y-m-d');
        $due_date = $_POST['due_date'] ?? date('Y-m-d', strtotime('+30 days'));
        $notes = $_POST['notes'] ?? "";
        $order_status = $_POST['order_status'] ?? 'Unpaid';
        $pay_status = $order_status === 'Paid' ? 'paid' : 'unpaid';
        $pay_date = $order_status === 'Paid' ? date('Y-m-d') : null;

        // Product processing
        $products = $_POST['order_product'];
        $product_prices = $_POST['order_product_price'];
        $quantities = $_POST['order_product_quantity'] ?? [];
        $discounts = $_POST['order_product_discount'] ?? [];
        $product_descriptions = $_POST['order_product_description'] ?? [];
        $item_ids = $_POST['order_item_id'] ?? []; // Get existing item IDs

        $subtotal_before_discounts = 0;
        $total_discount = 0;
        $product_codes = [];
        $order_items = [];
        $processed_item_ids = []; // Track which item IDs we've processed

        foreach ($products as $key => $pid) {
            if (empty($pid)) continue;
            
            $price = parse_numeric($product_prices[$key] ?? 0);
            $qty = intval($quantities[$key] ?? 1);
            $disc = parse_numeric($discounts[$key] ?? 0);
            $desc = $product_descriptions[$key] ?? '';
            $item_id = !empty($item_ids[$key]) ? intval($item_ids[$key]) : null;
            
            $line_total = $price * $qty;
            $subtotal_before_discounts += $line_total;
            $total_discount += $disc;
            $product_codes[] = $pid;
            
            $order_items[] = [
                'item_id' => $item_id,
                'product_id' => $pid,
                'price' => $price,
                'qty' => $qty,
                'discount' => $disc,
                'desc' => $desc,
                'total' => $line_total - $disc
            ];
            
            if ($item_id) {
                $processed_item_ids[] = $item_id;
            }
        }

        $product_code_str = implode(',', $product_codes);
        $subtotal_after_discount = $subtotal_before_discounts - $total_discount;

        // Delivery Fee logic (same as create)
        $deliveryFeeSql = "SELECT delivery_fee FROM branding LIMIT 1";
        $deliveryFeeResult = $conn->query($deliveryFeeSql);
        $brandingFee = ($deliveryFeeResult && $row = $deliveryFeeResult->fetch_assoc()) ? floatval($row['delivery_fee']) : 0;
        
        $delivery_fee = $brandingFee;
        $total_amount = $subtotal_after_discount + $delivery_fee;

        // Update order_header
        $updateSql = "UPDATE order_header SET 
                        customer_id = ?, issue_date = ?, due_date = ?, 
                        subtotal = ?, discount = ?, total_amount = ?, delivery_fee = ?, 
                        notes = ?, pay_status = ?, pay_date = ?, 
                        product_code = ?, full_name = ?, mobile = ?, mobile_2 = ?, 
                        address_line1 = ?, address_line2 = ?, city_id = ?, zone_id = ?, district_id = ?
                      WHERE order_id = ? AND status = 'pending'";
        
        $stmt = $conn->prepare($updateSql);
        $stmt->bind_param("issddddsssssssssiiis", 
            $customer_id, $order_date, $due_date, 
            $subtotal_before_discounts, $total_discount, $total_amount, $delivery_fee,
            $notes, $pay_status, $pay_date,
            $product_code_str, $customer_name, $customer_phone, $customer_phone_2,
            $address_line1, $address_line2, $city_id, $zone_id, $district_id,
            $order_id
        );

        if (!$stmt->execute()) {
            throw new Exception("Failed to update order header: " . $stmt->error);
        }
        $stmt->close();

        // Fetch current order items to track what exists
        $currentItemsSql = "SELECT item_id, product_id, quantity FROM order_items WHERE order_id = ?";
        $currentItemsStmt = $conn->prepare($currentItemsSql);
        $currentItemsStmt->bind_param("s", $order_id);
        $currentItemsStmt->execute();
        $currentItemsResult = $currentItemsStmt->get_result();
        
        $current_items_map = [];
        while ($row = $currentItemsResult->fetch_assoc()) {
            $current_items_map[$row['item_id']] = $row;
        }
        $currentItemsStmt->close();

        // Prepare statements for item operations
        $updateItemSql = "UPDATE order_items SET 
                            product_id = ?, unit_price = ?, quantity = ?, 
                            discount = ?, total_amount = ?, pay_status = ?, description = ?
                          WHERE item_id = ? AND order_id = ?";
        $updateStmt = $conn->prepare($updateItemSql);

        $insertItemSql = "INSERT INTO order_items (
            order_id, product_id, unit_price, quantity, discount, 
            total_amount, pay_status, status, description
        ) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', ?)";
        $insertStmt = $conn->prepare($insertItemSql);

        // Process each item
        foreach ($order_items as $item) {
            if ($item['item_id'] && isset($current_items_map[$item['item_id']])) {
                // EXISTING ITEM - UPDATE IT
                $updateStmt->bind_param("ididdssis", 
                    $item['product_id'], $item['price'], $item['qty'], 
                    $item['discount'], $item['total'], $pay_status, $item['desc'],
                    $item['item_id'], $order_id
                );
                $updateStmt->execute();
            } else {
                // NEW ITEM - INSERT IT
                $insertStmt->bind_param("sididsss", 
                    $order_id, $item['product_id'], $item['price'], $item['qty'], 
                    $item['discount'], $item['total'], $pay_status, $item['desc']
                );
                $insertStmt->execute();
            }
        }
        
        // Delete items that were removed from the order
        foreach ($current_items_map as $item_id => $old_item) {
            if (!in_array($item_id, $processed_item_ids)) {
                $deleteStmt = $conn->prepare("DELETE FROM order_items WHERE item_id = ? AND order_id = ?");
                $deleteStmt->bind_param("is", $item_id, $order_id);
                $deleteStmt->execute();
                $deleteStmt->close();
            }
        }
        
        $updateStmt->close();
        $insertStmt->close();

        // Log action
        logUserAction($conn, $user_id, "Updated order", $order_id, "Order details updated via edit interface");

        $conn->commit();
        setMessageAndRedirect("success", "Order #{$order_id} updated successfully.");

    } catch (Exception $e) {
        if ($conn) $conn->rollback();
        setMessageAndRedirect("error", "Error: " . $e->getMessage(), "edit_order.php?id=" . ($_POST['order_id'] ?? ''));
    }
}
?>
