<?php
/**
 * Meta (Facebook) Integration Configuration
 */

// Meta App Credentials
define('META_APP_ID', '1591814442111841');
define('META_APP_SECRET', '5f51170ec8f9e2efdb58c8bd12dc626a');

// Page Access Token (Needs leads_retrieval permission)
define('META_PAGE_ACCESS_TOKEN', 'EAAWnvy5e02EBQ1ZCVR8vxJXaAZCtKOZBjdJps1ZA5OnzscAzrDyOO1ZAlpD8hn3dRHXH7nT3KQK7TvDFeqgE2J7aW3pTxX6MOZCYi5KMtFx6qmnTOksA8aiGWyJcbpUIuyT52ZBhljw9LJg4wOB2My0EDEOoJZBTbrKAlonPmMsIkvlcGMQwxdDtRysbiYETPer9yuKh03gqzMU4ZBi4hPeGZAzKGQwgusoqltSf3eY9gMJZAQGw8oVFP6izvEIgqdUizFBEVZC8okNjTHwv');

// Webhook Verification Token (A string you choose in Meta Developer Portal)
define('META_VERIFY_TOKEN', 'FEIT_Order_System_2024');

// API Version
define('META_API_VERSION', 'v19.0');

// Default Product ID for incoming leads (if not specified)
define('META_DEFAULT_PRODUCT_ID', 1); // Change as needed

// Default User ID to assign leads (if no round-robin)
define('META_DEFAULT_USER_ID', 1); // Change as needed
?>
