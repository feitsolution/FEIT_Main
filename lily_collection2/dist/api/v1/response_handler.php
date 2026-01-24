<?php
/**
 * API Response Handler
 * File: /lily_collection/dist/api/v1/response_handler.php
 * 
 * Provides standardized JSON responses for consistency
 */

// Prevent direct access
if (!defined('API_ACCESS')) {
    http_response_code(403);
    die(json_encode(['success' => false, 'error' => 'Direct access forbidden']));
}

class ApiResponse {
    
    /**
     * Send success response
     * 
     * @param array $data Response data
     * @param string $message Success message
     * @param int $http_code HTTP status code (default: 200)
     */
    public static function success($data = [], $message = 'Success', $http_code = 200) {
        http_response_code($http_code);
        header('Content-Type: application/json');
        
        echo json_encode([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'timestamp' => date('c')
        ], JSON_PRETTY_PRINT);
        
        exit;
    }
    
    /**
     * Send error response
     * 
     * @param string $error Error message
     * @param array $details Additional error details
     * @param int $http_code HTTP status code
     */
    public static function error($error, $details = [], $http_code = 400) {
        http_response_code($http_code);
        header('Content-Type: application/json');
        
        $response = [
            'success' => false,
            'error' => $error,
            'timestamp' => date('c')
        ];
        
        if (!empty($details)) {
            $response['details'] = $details;
        }
        
        echo json_encode($response, JSON_PRETTY_PRINT);
        exit;
    }
    
    /**
     * Send validation error response
     * 
     * @param array $errors Validation errors ['field' => 'error message']
     */
    public static function validationError($errors) {
        self::error('Validation failed', ['validation_errors' => $errors], 422);
    }
    
    /**
     * Send unauthorized response
     */
    public static function unauthorized($message = 'Unauthorized access') {
        self::error($message, [], 401);
    }
    
    /**
     * Send not found response
     */
    public static function notFound($message = 'Resource not found') {
        self::error($message, [], 404);
    }
    
    /**
     * Send server error response
     */
    public static function serverError($message = 'Internal server error') {
        self::error($message, [], 500);
    }
    
    /**
     * Send order created response (specialized for order creation)
     */
    public static function orderCreated($order_data) {
        self::success($order_data, 'Order created successfully', 201);
    }
}
?>