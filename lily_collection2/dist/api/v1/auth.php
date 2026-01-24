<?php
/**
 * API Authentication Handler
 * File: /lily_collection/dist/api/v1/auth.php
 * 
 * Business Logic:
 * - Validates API keys from branding table
 * - Prevents unauthorized access
 */

// Prevent direct access
if (!defined('API_ACCESS')) {
    http_response_code(403);
    die(json_encode(['success' => false, 'error' => 'Direct access forbidden']));
}

class ApiAuth {
    private $conn;
    
    public function __construct($db_connection) {
        $this->conn = $db_connection;
    }
    
    /**
     * Get API key from request headers or query parameters
     * 
     * @return string|null
     */
    private function getApiKeyFromRequest() {
        // Check Authorization header (Bearer token)
        $headers = getallheaders();
        if (isset($headers['Authorization'])) {
            $auth_header = $headers['Authorization'];
            if (preg_match('/Bearer\s+(.*)$/i', $auth_header, $matches)) {
                return trim($matches[1]);
            }
        }
        
        // Check query parameter
        if (isset($_GET['api_key'])) {
            return trim($_GET['api_key']);
        }
        
        // Check POST parameter
        if (isset($_POST['api_key'])) {
            return trim($_POST['api_key']);
        }
        
        return null;
    }
    
    /**
     * Validate API Key from request headers or query params
     * 
     * Business Rule: API key must match branding table's api_key field
     * 
     * @return array ['valid' => bool, 'branding_id' => int, 'company_name' => string]
     */
    public function validateApiKey() {
        // Get API key from Authorization header or query parameter
        $api_key = $this->getApiKeyFromRequest();
        
        if (empty($api_key)) {
            return [
                'valid' => false,
                'error' => 'API key missing. Include in Authorization header as "Bearer YOUR_KEY" or ?api_key=YOUR_KEY'
            ];
        }
        
        // Check if API key exists in branding table
        $stmt = $this->conn->prepare("
            SELECT branding_id, company_name, customer_id, delivery_fee 
            FROM branding 
            WHERE api_key = ? AND active = 1
            LIMIT 1
        ");
        
        if (!$stmt) {
            return [
                'valid' => false,
                'error' => 'Database error: ' . $this->conn->error
            ];
        }
        
        $stmt->bind_param("s", $api_key);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            return [
                'valid' => false,
                'error' => 'Invalid API key or account inactive'
            ];
        }
        
        $branding = $result->fetch_assoc();
        $stmt->close();
        
        return [
            'valid' => true,
            'branding_id' => $branding['branding_id'],
            'company_name' => $branding['company_name'],
            'customer_id' => $branding['customer_id'],
            'delivery_fee' => $branding['delivery_fee']
        ];
    }
}
?>