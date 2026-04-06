<?php
/**
 * Official Meta WhatsApp Cloud API Webhook
 * 
 * Handles webhook verification and receives/sends messages.
 */

require_once __DIR__ . '/../connection/db_connection.php';

// ==========================================
// META WHATSAPP CLOUD API CONFIGURATION
// ==========================================
// 1. You set this token in the Meta App Dashboard when setting up the Webhook
$verify_token = "my_custom_secure_verify_token_123"; 

// 2. These come from the Meta App Dashboard (WhatsApp > API Setup)
$access_token = "EAAgeT2q3kHMBROcZC3GGsAfSCLBlFzZA2hlwp6ilJxWqQSf8IJzH8Jxvx9QnVJjCHoqGEocRTnZCaMDUXNlmU6GG4wnl0IKfMuZA5c4uGSbeukiareZAo5MdzWqkqQbwImzhSeR9HgCtB18tMD4mWgZC8vNeLltUgTtEgTH50R969wK3F93fTZC96oaoWDalG9k0zRscXYJNfyMJCwpIVUl4xpmtZC0NQtoECRRYXa7Lw2oLofziImZBxIHUghF6zpBefwsi7RysMJvKYDzftKt7KcZByF";

$phone_number_id = "974652939074897"; 

// ==========================================
// WEBHOOK VERIFICATION (Required by Meta)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (isset($_GET['hub_mode']) && isset($_GET['hub_verify_token'])) {
        if ($_GET['hub_mode'] === 'subscribe' && $_GET['hub_verify_token'] === $verify_token) {
            http_response_code(200);
            echo $_GET['hub_challenge'];
            exit;
        } else {
            http_response_code(403);
            exit;
        }
    }
}

// ==========================================
// FUNCTION TO SEND MESSAGES VIA META API
// ==========================================
function sendWhatsAppMessage($to, $body) {
    global $access_token, $phone_number_id;

    if ($access_token === "YOUR_META_PERMANENT_ACCESS_TOKEN") {
        error_log("Meta Send Simulated -> $to: $body");
        return;
    }

    $url = "https://graph.facebook.com/v25.0/$phone_number_id/messages";

    $data = [
        "messaging_product" => "whatsapp",
        "to" => $to,
        "type" => "text",
        "text" => [
            "body" => $body
        ]
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer " . $access_token,
        "Content-Type: application/json"
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    error_log("Meta API Response ($http_code): " . $response);
}

// ==========================================
// HANDLE INCOMING MESSAGES
// ==========================================
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

$payload = file_get_contents('php://input');
$data = json_decode($payload, true);

error_log("Meta Webhook Received: " . $payload);

// Meta sends a complex nested JSON structure. We must extract the message.
if (
    isset($data['entry'][0]['changes'][0]['value']['messages'][0])
) {
    $message_object = $data['entry'][0]['changes'][0]['value']['messages'][0];
    
    // Check if it's a text message
    if ($message_object['type'] === 'text') {
        // Formatted as 94771234567 (no + sign)
        $from_number = $message_object['from'];
        $message_body = trim($message_object['text']['body']);
        
        processBotLogic($from_number, $message_body);
    }
}

// Always return 200 OK fast so Meta doesn't retry
http_response_code(200);
echo json_encode(["status" => "success"]);


// ==========================================
// CORE BOT LOGIC (State Machine)
// ==========================================
function processBotLogic($clean_phone, $message_body) {
    global $conn;

    // Step 1: Check session state
    $stmt = $conn->prepare("SELECT * FROM whatsapp_sessions WHERE phone_number = ?");
    $stmt->bind_param("s", $clean_phone);
    $stmt->execute();
    $session = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$session) {
        $stmt = $conn->prepare("INSERT INTO whatsapp_sessions (phone_number, current_step) VALUES (?, 'IDLE')");
        $stmt->bind_param("s", $clean_phone);
        $stmt->execute();
        $stmt->close();
        
        $session = ['phone_number' => $clean_phone, 'current_step' => 'IDLE'];
    }

    $current_step = $session['current_step'];
    $next_step = $current_step;
    $reply_message = "";

    $msg_lower = strtolower($message_body);

    // Allow resetting the bot at any time
    if (in_array($msg_lower, ['hi', 'hello', 'start', 'reset', 'menu'])) {
        $next_step = 'IDLE';
    }

    switch ($next_step) {
        case 'IDLE':
            if ($msg_lower === 'menu' || in_array($msg_lower, ['hi', 'hello', 'start'])) {
                // Fetch live menu
                $menu_msg = "Welcome to our store! 🍔🛍️\nHere is our menu:\n\n";
                $res = $conn->query("SELECT id, name, lkr_price FROM products WHERE status = 'active' LIMIT 10");
                if ($res && $res->num_rows > 0) {
                    while($row = $res->fetch_assoc()) {
                        $menu_msg .= "• {$row['name']} - LKR {$row['lkr_price']}\n";
                    }
                } else {
                    $menu_msg .= "(No active products found)\n";
                }
                
                $menu_msg .= "\nTo order, type 'order [Item Name]' (e.g. order item)";
                $reply_message = $menu_msg;
                $next_step = 'IDLE';
            } else if (strpos($msg_lower, 'order') === 0) {
                // "order Pizza"
                $product_search = trim(substr($message_body, 5));
                if (empty($product_search)) {
                    $reply_message = "Please specify what you want to order. Example: 'order Pizza'";
                } else {
                    // Find product
                    $stmt = $conn->prepare("SELECT id, name, lkr_price FROM products WHERE LOWER(name) LIKE CONCAT('%', ?, '%') AND status = 'active' LIMIT 1");
                    $search_term = strtolower($product_search);
                    $stmt->bind_param("s", $search_term);
                    $stmt->execute();
                    $product = $stmt->get_result()->fetch_assoc();
                    $stmt->close();
                    
                    if ($product) {
                        $stmt = $conn->prepare("UPDATE whatsapp_sessions SET product_id = ?, current_step = 'WAITING_NAME' WHERE phone_number = ?");
                        $stmt->bind_param("is", $product['id'], $clean_phone);
                        $stmt->execute();
                        
                        $reply_message = "Great! You selected *{$product['name']}* (LKR {$product['lkr_price']}).\n\nPlease reply with your *Full Name*.";
                        $next_step = 'WAITING_NAME';
                    } else {
                        $reply_message = "Sorry, we couldn't find a product matching '{$product_search}'. Type 'menu' to see our list.";
                    }
                }
            } else {
                $reply_message = "Welcome! Send 'menu' to see our products, or 'order [item]' to buy something.";
            }
            break;

        case 'WAITING_NAME':
            // Save name, ask for city
            $stmt = $conn->prepare("UPDATE whatsapp_sessions SET customer_name = ?, current_step = 'WAITING_CITY' WHERE phone_number = ?");
            $stmt->bind_param("ss", $message_body, $clean_phone);
            $stmt->execute();
            
            $reply_message = "Thanks, {$message_body}!\n\nPlease reply with your *City Name*.";
            $next_step = 'WAITING_CITY';
            break;

        case 'WAITING_CITY':
            // Find city in DB
            $stmt = $conn->prepare("SELECT city_id, city_name FROM city_table WHERE LOWER(city_name) LIKE CONCAT('%', ?, '%') LIMIT 1");
            $search_city = strtolower($message_body);
            $stmt->bind_param("s", $search_city);
            $stmt->execute();
            $city = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($city) {
                $stmt = $conn->prepare("UPDATE whatsapp_sessions SET customer_city_id = ?, current_step = 'WAITING_ADDRESS' WHERE phone_number = ?");
                $stmt->bind_param("is", $city['city_id'], $clean_phone);
                $stmt->execute();
                
                $reply_message = "Got it! Your city is matched to *{$city['city_name']}*.\n\nFinally, please reply with your *Delivery Address*.";
                $next_step = 'WAITING_ADDRESS';
            } else {
                $reply_message = "We couldn't find city '{$message_body}'. Please try typing it again (e.g., Colombo).";
            }
            break;

        case 'WAITING_ADDRESS':
            // Finalize order
            $address = $message_body;
            
            $stmt = $conn->prepare("UPDATE whatsapp_sessions SET customer_address = ?, current_step = 'COMPLETED' WHERE phone_number = ?");
            $stmt->bind_param("ss", $address, $clean_phone);
            $stmt->execute();
            
            // --- PROCESS ORDER TO MYSQL ---
            $stmt = $conn->prepare("SELECT * FROM whatsapp_sessions WHERE phone_number = ?");
            $stmt->bind_param("s", $clean_phone);
            $stmt->execute();
            $final_session = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            
            // 1. Create or find customer (using the phone number as identifier)
            $customer_id = 0;
            $stmt = $conn->prepare("SELECT customer_id FROM customers WHERE phone = ? LIMIT 1");
            $stmt->bind_param("s", $clean_phone);
            $stmt->execute();
            $c_res = $stmt->get_result()->fetch_assoc();
            if ($c_res) {
                $customer_id = $c_res['customer_id'];
            } else {
                $stmt = $conn->prepare("INSERT INTO customers (name, phone, address_line1, city_id, status) VALUES (?, ?, ?, ?, 'Active')");
                $stmt->bind_param("sssi", $final_session['customer_name'], $clean_phone, $address, $final_session['customer_city_id']);
                $stmt->execute();
                $customer_id = $conn->insert_id;
            }

            // 2. Fetch Product and City Details
            $product_id = $final_session['product_id'];
            $product_price = 0;
            $product_code = '';
            $stmt = $conn->prepare("SELECT lkr_price, product_code FROM products WHERE id = ?");
            $stmt->bind_param("i", $product_id);
            $stmt->execute();
            $p_res = $stmt->get_result()->fetch_assoc();
            if ($p_res) {
                $product_price = $p_res['lkr_price'];
                $product_code = $p_res['product_code'];
            }
            $stmt->close();

            $zone_id = null;
            $district_id = null;
            $stmt = $conn->prepare("SELECT zone_id, district_id FROM city_table WHERE city_id = ?");
            $stmt->bind_param("i", $final_session['customer_city_id']);
            $stmt->execute();
            $city_res = $stmt->get_result()->fetch_assoc();
            if ($city_res) {
                $zone_id = $city_res['zone_id'];
                $district_id = $city_res['district_id'];
            }
            $stmt->close();

            // 3. Create Order Header
            $delivery_fee = 350.00; // Fixed delivery fee
            $net_total = $product_price;
            $gross_total = $net_total + $delivery_fee;
            $order_date = date('Y-m-d H:i:s');
            
            $stmt = $conn->prepare("INSERT INTO order_header (
                customer_id, interface, full_name, mobile, address_line1, city_id, zone_id, district_id, product_code, net_total, delivery_fee, gross_total, status, user_id, order_date
            ) VALUES (?, 'individual', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', 1, ?)");
            
            $stmt->bind_param("isssiiisddds", 
                $customer_id, 
                $final_session['customer_name'], 
                $clean_phone, 
                $address, 
                $final_session['customer_city_id'], 
                $zone_id, 
                $district_id, 
                $product_code,
                $net_total, 
                $delivery_fee, 
                $gross_total, 
                $order_date
            );
            $stmt->execute();
            $order_id = $conn->insert_id;
            $stmt->close();

            // 4. Create Order Item
            if ($order_id) {
                $stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, unit_price, total_price) VALUES (?, ?, 1, ?, ?)");
                $stmt->bind_param("iidd", $order_id, $product_id, $product_price, $product_price);
                $stmt->execute();
                
                $reply_message = "✅ *Order Placed Successfully!*\n\nYour Order ID is *#{$order_id}*.\nWe will process it shortly. Thanks for shopping with us!";
            } else {
                $reply_message = "Something went wrong while saving your order. Please try again later or call support.";
            }
            break;

        default:
            $reply_message = "You are in an unknown state. Send 'start' to restart.";
            break;
    }

    if (!empty($reply_message)) {
        sendWhatsAppMessage($clean_phone, $reply_message);
    }
}
?>