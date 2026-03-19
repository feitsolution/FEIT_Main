<?php
/**
 * Meta Webhook Endpoint
 * This file handles verification and real-time notifications from Meta.
 */

require_once('meta_config.php');
require_once('../connection/db_connection.php');
require_once('meta_lead_processor.php');

// Handle Meta's Webhook Verification (GET request)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (isset($_GET['hub_mode']) && $_GET['hub_mode'] === 'subscribe' && 
        isset($_GET['hub_verify_token']) && $_GET['hub_verify_token'] === META_VERIFY_TOKEN) {
        
        echo $_GET['hub_challenge'];
        http_response_code(200);
        exit();
    } else {
        http_response_code(403);
        die("Verification failed.");
    }
}

// Handle Real-time Lead Notifications (POST request)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = file_get_contents('php://output');
    $data = json_decode($input, true);

    if (isset($data['object']) && $data['object'] === 'page') {
        foreach ($data['entry'] as $entry) {
            foreach ($entry['changes'] as $change) {
                if ($change['field'] === 'leadgen') {
                    $leadId = $change['value']['leadgen_id'];
                    $pageId = $change['value']['page_id'];
                    $formId = $change['value']['form_id'];

                    // Process the lead logic
                    processMetaLead($conn, $leadId, $formId);
                }
            }
        }
        
        http_response_code(200);
        echo json_encode(['status' => 'success']);
        exit();
    }
}

http_response_code(400);
echo json_encode(['status' => 'invalid_request']);
?>
