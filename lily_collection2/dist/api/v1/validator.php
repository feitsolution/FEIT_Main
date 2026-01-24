<?php
/**
 * API Input Validator
 * File: /lily_collection/dist/api/v1/validator.php
 * 
 * Validates incoming API request data
 * ALIGNED WITH CSV IMPORT LOGIC
 * 
 * FIXED: Added validation to prevent phone_2 being same as phone
 */

// Prevent direct access
if (!defined('API_ACCESS')) {
    http_response_code(403);
    die(json_encode(['success' => false, 'error' => 'Direct access forbidden']));
}

class ApiValidator {
    private $errors = [];
    private $conn = null;
    
    /**
     * Constructor - accepts database connection for product validation
     */
    public function __construct($conn = null) {
        $this->conn = $conn;
    }
    
    /**
     * Validate order creation request
     * 
     * Business Rules (MATCHING CSV IMPORT):
     * - Required: customer_name, customer_phone, address_line1, city (name or id), products
     * - Optional: customer_email, customer_phone_2, address_line2, notes
     * - At least one product required
     * - Valid phone number format (10 digits)
     * - Valid email format (if provided)
     * - City can be sent as city_name OR city_id (at least one required)
     * - Products validated against database (like CSV import)
     * - Phone_2 cannot be same as phone
     * 
     * @param array $data Request data
     * @return array ['valid' => bool, 'errors' => array, 'validated_products' => array]
     */
    public function validateOrderRequest($data) {
        $this->errors = [];
        $validated_products = [];
        
        // === REQUIRED FIELDS (matching CSV import) ===
        
        // 1. Customer name (required)
        $this->validateRequired($data, 'customer_name', 'Customer name');
        
        // 2. Phone number (required)
        $this->validatePhone($data, 'customer_phone', true);
        
        // 3. Address Line 1 (required)
        $this->validateRequired($data, 'address_line1', 'Address line 1');
        
        // 4. City - REQUIRED (either city_name OR city_id)
        $this->validateCity($data);
        
        // 5. Products (required - at least one)
        if (empty($data['products']) || !is_array($data['products'])) {
            $this->errors['products'] = 'At least one product is required';
        } else {
            // Validate products against database (CSV import style)
            $product_validation = $this->validateProductsWithDatabase($data['products']);
            if (!$product_validation['valid']) {
                $this->errors = array_merge($this->errors, $product_validation['errors']);
            } else {
                $validated_products = $product_validation['products'];
            }
        }
        
        // === OPTIONAL FIELDS ===
        
        // Email (optional, but must be valid if provided)
        $this->validateEmail($data, 'customer_email', false);
        
        // Phone 2 (optional, but must be valid if provided)
        if (!empty($data['customer_phone_2'])) {
            $this->validatePhone($data, 'customer_phone_2', false);
            
            // NEW VALIDATION: Ensure phone_2 is not the same as phone
            $this->validatePhoneUniqueness($data);
        }
        
        // Date validation (optional)
        $this->validateDate($data, 'order_date', false);
        $this->validateDate($data, 'due_date', false);
        
        // Payment status validation (optional)
        if (isset($data['order_status'])) {
            $this->validateEnum($data, 'order_status', ['Paid', 'Unpaid'], 'Order status');
        }
        
        return [
            'valid' => empty($this->errors),
            'errors' => $this->errors,
            'validated_products' => $validated_products
        ];
    }
    
    /**
     * Validate that phone_2 is not the same as phone
     * NEW METHOD
     */
    private function validatePhoneUniqueness($data) {
        // Skip if either phone is missing
        if (empty($data['customer_phone']) || empty($data['customer_phone_2'])) {
            return;
        }
        
        // Normalize both phone numbers (remove non-numeric characters)
        $phone1 = preg_replace('/[^0-9]/', '', $data['customer_phone']);
        $phone2 = preg_replace('/[^0-9]/', '', $data['customer_phone_2']);
        
        // Compare normalized phone numbers
        if ($phone1 === $phone2) {
            $this->errors['customer_phone_2'] = 'Secondary phone number cannot be the same as primary phone number';
        }
    }
    
    /**
     * Validate products against database (CSV Import Style)
     * Accepts: product_id, product_code, or product_name
     * 
     * @param array $products Products array
     * @return array ['valid' => bool, 'products' => array, 'errors' => array]
     */
    private function validateProductsWithDatabase($products) {
        $validated = [];
        $errors = [];
        
        if (!$this->conn) {
            return [
                'valid' => false,
                'products' => [],
                'errors' => ['products' => 'Database connection not available for product validation']
            ];
        }
        
        foreach ($products as $index => $product) {
            // Find product in database
            $db_product = $this->findProduct($product, $index);
            
            if (!$db_product) {
                $errors["products.$index"] = $this->getProductNotFoundError($product, $index);
                continue;
            }
            
            // Validate price and discount
            $requested_price = floatval($product['price'] ?? 0);
            $requested_discount = floatval($product['discount'] ?? 0);
            
            if ($requested_price <= 0) {
                $errors["products.$index.price"] = "Price must be greater than 0 for product at index $index";
                continue;
            }
            
            if ($requested_discount < 0) {
                $errors["products.$index.discount"] = "Discount cannot be negative for product at index $index";
                continue;
            }
            
            if ($requested_discount > $requested_price) {
                $errors["products.$index.discount"] = "Discount cannot exceed price for product at index $index (Product: {$db_product['name']})";
                continue;
            }
            
            // Product validated successfully
            $validated[] = [
                'product_id' => $db_product['id'],
                'product_name' => $db_product['name'],
                'product_code' => $db_product['product_code'],
                'db_price' => floatval($db_product['lkr_price']),
                'db_description' => $db_product['description'],
                'requested_price' => $requested_price,
                'requested_discount' => $requested_discount,
                'requested_description' => $product['description'] ?? $db_product['description'],
                'final_price' => $requested_price - $requested_discount,
                'price_match' => (floatval($db_product['lkr_price']) == $requested_price) ? 'Yes' : 'No (Custom pricing)',
            ];
        }
        
        return [
            'valid' => empty($errors),
            'products' => $validated,
            'errors' => $errors
        ];
    }
    
    /**
     * Find product in database by ID, Code, or Name (CSV Import Style)
     * 
     * @param array $product Product data
     * @param int $index Product index
     * @return array|null Database product or null
     */
    private function findProduct($product, $index) {
        // Priority 1: Search by product_id
        if (!empty($product['product_id'])) {
            $stmt = $this->conn->prepare(
                "SELECT id, name, product_code, description, lkr_price, status 
                 FROM products 
                 WHERE id = ? AND status = 'active' 
                 LIMIT 1"
            );
            $stmt->bind_param("i", $product['product_id']);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $db_product = $result->fetch_assoc();
                $stmt->close();
                return $db_product;
            }
            $stmt->close();
        }
        
        // Priority 2: Search by product_code (like CSV import)
        if (!empty($product['product_code'])) {
            $stmt = $this->conn->prepare(
                "SELECT id, name, product_code, description, lkr_price, status 
                 FROM products 
                 WHERE product_code = ? AND status = 'active' 
                 LIMIT 1"
            );
            $stmt->bind_param("s", $product['product_code']);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $db_product = $result->fetch_assoc();
                $stmt->close();
                return $db_product;
            }
            $stmt->close();
        }
        
        // Priority 3: Search by product_name (case-insensitive, like CSV import)
        if (!empty($product['product_name'])) {
            $stmt = $this->conn->prepare(
                "SELECT id, name, product_code, description, lkr_price, status 
                 FROM products 
                 WHERE LOWER(name) = LOWER(?) AND status = 'active' 
                 LIMIT 1"
            );
            $stmt->bind_param("s", $product['product_name']);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $db_product = $result->fetch_assoc();
                $stmt->close();
                return $db_product;
            }
            $stmt->close();
        }
        
        return null;
    }
    
    /**
     * Get appropriate error message for product not found
     */
    private function getProductNotFoundError($product, $index) {
        if (!empty($product['product_id'])) {
            return "Product ID '{$product['product_id']}' not found or inactive (index: $index)";
        }
        if (!empty($product['product_code'])) {
            return "Product Code '{$product['product_code']}' not found or inactive (index: $index)";
        }
        if (!empty($product['product_name'])) {
            return "Product Name '{$product['product_name']}' not found or inactive (index: $index)";
        }
        return "Product identification missing at index $index (provide product_id, product_code, or product_name)";
    }
    
    /**
     * Validate city - REQUIRED (either city_name OR city_id)
     * Matches CSV import requirement: City is required
     */
    private function validateCity($data) {
        $has_city_name = !empty($data['city_name']) && trim($data['city_name']) !== '';
        $has_city_id = !empty($data['city_id']) && is_numeric($data['city_id']);
        
        // At least one must be provided
        if (!$has_city_name && !$has_city_id) {
            $this->errors['city'] = 'City is required (provide either city_name or city_id)';
            return;
        }
        
        // Validate city_name format if provided
        if ($has_city_name && strlen(trim($data['city_name'])) < 2) {
            $this->errors['city_name'] = 'City name must be at least 2 characters';
        }
        
        // Validate city_id format if provided
        if (isset($data['city_id']) && !empty($data['city_id']) && !is_numeric($data['city_id'])) {
            $this->errors['city_id'] = 'City ID must be a number';
        }
    }
    
    /**
     * Validate required field
     */
    private function validateRequired($data, $field, $label) {
        if (empty($data[$field]) || trim($data[$field]) === '') {
            $this->errors[$field] = "$label is required";
        }
    }
    
    /**
     * Validate phone number
     * Format: Must be 10 digits (Sri Lankan format)
     */
    private function validatePhone($data, $field, $required = true) {
        if (!isset($data[$field]) || empty($data[$field])) {
            if ($required) {
                $this->errors[$field] = 'Phone number is required';
            }
            return;
        }
        
        // Remove non-numeric characters for validation
        $phone = preg_replace('/[^0-9]/', '', $data[$field]);
        
        if (strlen($phone) !== 10) {
            $this->errors[$field] = 'Phone number must be 10 digits';
        }
    }
    
    /**
     * Validate email
     */
    private function validateEmail($data, $field, $required = false) {
        if (!isset($data[$field]) || empty($data[$field])) {
            if ($required) {
                $this->errors[$field] = 'Email is required';
            }
            return;
        }
        
        if (!filter_var($data[$field], FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field] = 'Invalid email format';
        }
    }
    
    /**
     * Validate numeric field
     */
    private function validateNumeric($data, $field, $label) {
        if (isset($data[$field]) && !is_numeric($data[$field])) {
            $this->errors[$field] = "$label must be a number";
        }
    }
    
    /**
     * Validate date field
     */
    private function validateDate($data, $field, $required = true) {
        if (!isset($data[$field]) || empty($data[$field])) {
            if ($required) {
                $this->errors[$field] = ucfirst($field) . ' is required';
            }
            return;
        }
        
        $date = DateTime::createFromFormat('Y-m-d', $data[$field]);
        if (!$date || $date->format('Y-m-d') !== $data[$field]) {
            $this->errors[$field] = ucfirst($field) . ' must be in YYYY-MM-DD format';
        }
    }
    
    /**
     * Validate enum field
     */
    private function validateEnum($data, $field, $allowed_values, $label) {
        if (isset($data[$field]) && !in_array($data[$field], $allowed_values)) {
            $this->errors[$field] = "$label must be one of: " . implode(', ', $allowed_values);
        }
    }
    
    /**
     * Sanitize input data
     * Recursively cleans arrays and strings
     */
    public static function sanitize($data) {
        if (is_array($data)) {
            return array_map([self::class, 'sanitize'], $data);
        }
        
        return is_string($data) ? trim(htmlspecialchars($data, ENT_QUOTES, 'UTF-8')) : $data;
    }
}
?>