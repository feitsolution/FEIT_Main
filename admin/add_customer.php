<?php
// Start session at the very beginning only if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in, if not redirect to login page
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    // Clear any existing output buffers
    if (ob_get_level()) {
        ob_end_clean();
    }
    // Force redirect to login page
    header("Location: signin.php");
    exit(); // Stop execution immediately
}

// Only Admin and Moderator can add customers
if (!isset($_SESSION['role_id']) || !in_array($_SESSION['role_id'], [1, 3])) {
    header("Location: customer_list.php");
    exit();
}

// Include the database connection file
include 'db_connection.php';

include 'functions.php'; // Include helper functions

// Function to generate CSRF token
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Email validation function - enhanced version
function validateEmail($email) {
    // Check if email is empty
    if (empty($email)) {
        return "Email cannot be empty.";
    }
    
    // Convert to lowercase for consistent validation
    $email = strtolower($email);
    
    // Check maximum length
    if (strlen($email) > 254) {
        return "Email address is too long (maximum 254 characters allowed).";
    }
    
    // Basic format validation with filter_var
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return "Please enter a valid email address format (e.g., name@example.com).";
    }
    
    // Advanced structure validation
    $parts = explode('@', $email);
    if (count($parts) !== 2) {
        return "Email must contain exactly one @ symbol.";
    }
    
    $username = $parts[0];
    $domain = $parts[1];
    
    // Username validation
    if (strlen($username) > 64) {
        return "Username part of email is too long (maximum 64 characters allowed).";
    }
    
    if (preg_match('/^\.|\.\.|\.$/', $username)) {
        return "Username cannot start or end with a period or contain consecutive periods.";
    }
    
    // Domain validation
    if (!strpos($domain, '.')) {
        return "Email domain appears to be invalid. Must contain at least one period.";
    }
    
    $domainParts = explode('.', $domain);
    
    // Check domain part before TLD
    if (strlen($domainParts[0]) > 63) {
        return "Email domain name is too long.";
    }
    
    // Check TLD (last part)
    $tld = end($domainParts);
    if (strlen($tld) < 2 || strlen($tld) > 10) {
        return "Email TLD (domain ending) is invalid.";
    }
    
    // Check for invalid domain patterns
    if (preg_match('/^-|-$/', $domain) || preg_match('/^-|-$/', $tld)) {
        return "Domain parts cannot start or end with hyphens.";
    }
    
    // All checks passed
    return "";
}

// Initialize error message variable
$errorMsg = '';
$successMsg = '';

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Check CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $errorMsg = "Invalid request. Please try again.";
    } else {
         // Sanitize and validate inputs
         $name = trim($_POST['name']);
         $email = trim($_POST['email']); // Keep original case for validation
         $phone = trim($_POST['phone']);
         $address = trim($_POST['address']);
         $business_name = trim($_POST['business_name']);
         $status = $_POST['status'];
         $billing_date = !empty($_POST['billing_date']) ? intval($_POST['billing_date']) : null;
         $product_id = !empty($_POST['product_id']) ? intval($_POST['product_id']) : null;
         $package_id = !empty($_POST['package_id']) ? intval($_POST['package_id']) : null;
         $custom_amounts = isset($_POST['custom_amounts']) ? $_POST['custom_amounts'] : [];
         $custom_max_counts = isset($_POST['custom_max_counts']) ? $_POST['custom_max_counts'] : [];

        // Enhanced validation checks
        if (empty($name)) {
            $errorMsg = "Name cannot be empty.";
        } elseif (strlen($name) > 100) {
            $errorMsg = "Name is too long (maximum 100 characters allowed).";
        } else {
            // Validate email with our custom function
            $emailError = validateEmail($email);
            if (!empty($emailError)) {
                $errorMsg = $emailError;
            }
        }
        
         // If no email errors continue with other validations
         if (empty($errorMsg)) {
             if (empty($phone)) {
                 $errorMsg = "Phone number cannot be empty.";
             } elseif (empty($address)) {
                 $errorMsg = "Address cannot be empty.";
             } elseif (strlen($address) > 255) {
                 $errorMsg = "Address is too long (maximum 255 characters allowed).";
             } elseif (!empty($business_name) && strlen($business_name) > 100) {
                 $errorMsg = "Business name is too long (maximum 100 characters allowed).";
             }

            // Updated phone validation - exactly 10 digits
            // Remove all non-digit characters
            $cleanPhone = preg_replace('/[^0-9]/', '', $phone);
            if (!empty($phone) && strlen($cleanPhone) !== 10) {
                $errorMsg = "Phone number must be exactly 10 digits.";
            }
        }

        // If no errors, proceed with database insertion
        if (empty($errorMsg)) {
            // Convert email to lowercase for storage
            $email = strtolower($email);
            
            // Check if email already exists in database
            $checkStmt = $conn->prepare("SELECT COUNT(*) FROM customers WHERE email = ?");
            $checkStmt->bind_param("s", $email);
            $checkStmt->execute();
            $checkStmt->bind_result($emailCount);
            $checkStmt->fetch();
            $checkStmt->close();
            
            if ($emailCount > 0) {
                $errorMsg = "This email address is already registered. Please use a different email.";
            } else {
                // Store only clean 10-digit phone number in database
                $phone = $cleanPhone;
                
                 // Prepare SQL statement to prevent SQL injection
                 $stmt = $conn->prepare("INSERT INTO customers (name, email, phone, address, business_name, product_id, package_id, initial_package_id, billing_date, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                 $stmt->bind_param("sssssiiiis", $name, $email, $phone, $address, $business_name, $product_id, $package_id, $package_id, $billing_date, $status);

                // Execute the statement
                if ($stmt->execute()) {
                    $new_customer_id = $conn->insert_id;

                    // Insert custom pricing and max count for all packages in the table
                    if (!empty($custom_amounts)) {
                        foreach ($custom_amounts as $pkg_id => $amount) {
                            if ($amount !== '') {
                                $pkg_id = intval($pkg_id);
                                $amt = floatval($amount);
                                $max = isset($custom_max_counts[$pkg_id]) && $custom_max_counts[$pkg_id] !== '' ? intval($custom_max_counts[$pkg_id]) : null;
                                $cp_stmt = $conn->prepare("INSERT INTO customer_packages (customer_id, package_id, amount, max_count) VALUES (?, ?, ?, ?)");
                                $cp_stmt->bind_param("iidi", $new_customer_id, $pkg_id, $amt, $max);
                                $cp_stmt->execute();
                                $cp_stmt->close();
                            }
                        }
                    }

                    // Set success message
                    $successMsg = "New customer added successfully!";
                    
                    // Clear form fields after successful submission
                    $name = $email = $phone = $address = $business_name = $product_id = $package_id = $billing_date = '';
                    $custom_amounts = [];
                    $custom_max_counts = [];
                } else {
                    $errorMsg = "Error: " . $stmt->error;
                }

                // Close the statement
                $stmt->close();
            }
        }
    }
}

// Fetch active products for the dropdown
$productQuery = "SELECT id, name FROM products WHERE status = 'active' ORDER BY name ASC";
$productsResult = $conn->query($productQuery);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <?php include('header.php'); ?>
    <title>Add Customer</title>
    <link href="css/forms.css" rel="stylesheet" />
    <style>
        body {
            background-color: #f8fafc;
        }
        
        .form-floating .form-control {
            height: calc(3.5rem + 2px);
        }
        
        .is-valid {
            border-color: #198754;
            padding-right: calc(1.5em + 0.75rem);
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 8 8'%3e%3cpath fill='%23198754' d='M2.3 6.73L.6 4.53c-.4-1.04.46-1.4 1.1-.8l1.1 1.4 3.4-3.8c.6-.63 1.6-.27 1.2.7l-4 4.6c-.43.5-.8.4-1.1.1z'/%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right calc(0.375em + 0.1875rem) center;
            background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem);
        }
        
        /* Select2 Theme Tweaks to match Premium UI */
        .select2-container--bootstrap-5 .select2-selection {
            border: 1.5px solid var(--premium-input-border);
            border-radius: 8px;
            min-height: 45px;
            padding-top: 5px;
            background-color: #fbfcfd;
        }
        .select2-container--bootstrap-5.select2-container--focus .select2-selection {
            border-color: var(--premium-primary-light);
            box-shadow: 0 0 0 4px var(--premium-input-focus);
            background-color: #fff;
        }
        
        /* Email validation specific */
        .email-suggestions {
            margin-top: 4px;
            font-size: 0.875em;
            color: #6c757d;
        }

        /* Select2 Bootstrap 5 Theme Adjustments */
        .select2-container--bootstrap-5 .select2-selection {
            border: 1px solid #ced4da;
            border-radius: 0.375rem;
            min-height: calc(2.25rem + 2px);
        }
        .select2-container--bootstrap-5.select2-container--focus .select2-selection {
            border-color: #86b7fe;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
        }
    </style>
    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
</head>

<body class="sb-nav-fixed">
    <?php include 'navbar.php'; ?>
    <div id="layoutSidenav">
        <?php include 'sidebar.php'; ?>

        <div id="layoutSidenav_content">
            <main>
                <div class="container-fluid px-4">
                    <h4 class="mt-3">New Customer</h4>
                    
                    <!-- Success/Error Alert -->
                    <?php if (!empty($successMsg)): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?php echo htmlspecialchars($successMsg); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($errorMsg)): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?php echo htmlspecialchars($errorMsg); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                    
                    <div class="col-12">
                        <div class="premium-form-container">
                            <form method="POST" action="add_customer.php" id="addCustomerForm" novalidate>
                                <!-- CSRF Token -->
                                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                
                                <div class="row">
                                    <!-- Customer Details Section -->
                                    <div class="col-md-6">
                                        <div class="premium-section-header">
                                            <i class="fas fa-id-card"></i> Customer Details
                                        </div>

                                        <!-- Business Name Field -->
                                        <div class="mb-3">
                                            <label for="business_name" class="form-label"><i class="fas fa-building"></i> Business Name</label>
                                            <input type="text" class="form-control" id="business_name" name="business_name"
                                                 placeholder="Business Name" value="<?php echo isset($business_name) ? htmlspecialchars($business_name) : ''; ?>">
                                            <div class="error-feedback" id="business_name-error"></div>
                                        </div>
                                        
                                        <!-- Name Field -->
                                        <div class="mb-3">
                                            <label for="name" class="form-label"><i class="fas fa-user"></i> Full Name</label>
                                            <input type="text" class="form-control" id="name" name="name"
                                                placeholder="Full Name" value="<?php echo isset($name) ? htmlspecialchars($name) : ''; ?>" required>
                                            <div class="error-feedback" id="name-error"></div>
                                        </div>

                                        <!-- Email Field -->
                                        <div class="mb-3">
                                            <label for="email" class="form-label"><i class="fas fa-envelope"></i> Email Address</label>
                                            <input type="email" class="form-control" id="email" name="email"
                                                placeholder="name@example.com" value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>" required>
                                            <div class="error-feedback" id="email-error"></div>
                                            <div class="email-suggestions" id="email-suggestions"></div>
                                        </div>

                                        <!-- Phone Field -->
                                        <div class="mb-3">
                                            <label for="phone" class="form-label"><i class="fas fa-phone"></i> Phone Number</label>
                                            <input type="tel" class="form-control" id="phone" name="phone"
                                                 placeholder="Enter 10-digit phone number" value="<?php echo isset($phone) ? htmlspecialchars($phone) : ''; ?>" required>
                                            <div class="error-feedback" id="phone-error"></div>
                                        </div>

                                        <!-- Address Field -->
                                        <div class="mb-3">
                                            <label for="address" class="form-label"><i class="fas fa-map-marker-alt"></i> Address</label>
                                            <textarea class="form-control" id="address" name="address"
                                                 placeholder="Address" required rows="3"><?php echo isset($address) ? htmlspecialchars($address) : ''; ?></textarea>
                                            <div class="error-feedback" id="address-error"></div>
                                        </div>
                                    </div>
                                    
                                    <!-- Configuration Details Section -->
                                    <div class="col-md-6">
                                        <div class="premium-section-header">
                                            <i class="fas fa-cog"></i> Configuration Details
                                        </div>
                                        
                                        <!-- Status Field -->
                                        <div class="mb-3">
                                            <label for="status" class="form-label"><i class="fas fa-toggle-on"></i>
                                                Status</label>
                                            <select class="form-select" id="status" name="status" required>
                                                <option value="active" <?php echo (isset($status) && $status == 'active') ? 'selected' : ''; ?>>Active</option>
                                                <option value="inactive" <?php echo (isset($status) && $status == 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                                            </select>
                                        </div>

                                        <!-- Billing Date Field -->
                                        <div class="mb-3">
                                            <label for="billing_date" class="form-label"><i class="fas fa-calendar-day"></i> Billing Date</label>
                                            <select class="form-select" id="billing_date" name="billing_date">
                                                <option value="">-- No specific billing date --</option>
                                                <?php for($i = 1; $i <= 31; $i++): ?>
                                                    <option value="<?php echo $i; ?>" <?php echo (isset($billing_date) && $billing_date == $i) ? 'selected' : ''; ?>>
                                                        <?php echo $i; ?>
                                                    </option>
                                                <?php endfor; ?>
                                            </select>
                                        </div>
                                        
                                        <!-- Product Selection Field -->
                                        <div class="mb-3">
                                            <label for="product_id" class="form-label"><i class="fas fa-box"></i> Select Product</label>
                                            <select class="form-select" id="product_id" name="product_id">
                                                <option value="">-- Select Product --</option>
                                                <?php 
                                                if ($productsResult && $productsResult->num_rows > 0):
                                                    while ($product = $productsResult->fetch_assoc()): 
                                                ?>
                                                    <option value="<?php echo $product['id']; ?>" <?php echo (isset($product_id) && $product_id == $product['id']) ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($product['name']); ?>
                                                    </option>
                                                <?php 
                                                    endwhile;
                                                endif;
                                                ?>
                                            </select>
                                        </div>
                                        
                                        <!-- Package Selection Table -->
                                        <div class="mb-3">
                                            <label class="form-label"><i class="fas fa-cubes"></i> Select & Configure Packages</label>
                                            <div id="packages-container" class="table-responsive" style="max-height: 300px; overflow-y: auto; border: 1px solid #dee2e6; border-radius: 5px; padding: 10px;">
                                                <p class="text-muted text-center my-3">Select a product first to see packages.</p>
                                            </div>
                                        </div>
                                        
                                    </div>
                                </div>
                                
                                <div class="row mt-4 pt-3 border-top">
                                    <div class="col-12 d-flex justify-content-end gap-3">
                                        <a href="customer_list.php" class="premium-back-btn text-decoration-none d-flex align-items-center">
                                            <i class="fas fa-arrow-left me-2"></i> Cancel
                                        </a>
                                        <button type="submit" class="premium-save-btn" id="submitBtn">
                                            <i class="fas fa-user-plus"></i> Add Customer
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"
        crossorigin="anonymous"></script>
    <script src="js/scripts.js"></script>
    
    <script>
    /**
     * Enhanced Email Validation Function
     * Performs comprehensive email validation with detailed error messages
     */
    function validateEmail(email) {
        // First check if email is empty
        if (email.trim() === '') {
            return {
                valid: false,
                message: 'Email address cannot be empty'
            };
        }
        
        // Check total length
        if (email.length > 254) {
            return {
                valid: false,
                message: 'Email address is too long (maximum 254 characters allowed)'
            };
        }
        
        // Check if original email contains uppercase letters
        const lowerEmail = email.toLowerCase();
        if (email !== lowerEmail) {
            return {
                valid: false,
                message: 'Email address must be in lowercase only'
            };
        }
        
        // Split email into parts for detailed validation
        const parts = email.split('@');
        if (parts.length !== 2) {
            return {
                valid: false,
                message: 'Email must contain exactly one @ symbol'
            };
        }
        
        const username = parts[0];
        const domain = parts[1];
        
        // Username part validation
        if (username.length === 0) {
            return {
                valid: false,
                message: 'Username part of email cannot be empty'
            };
        }
        
        if (username.length > 64) {
            return {
                valid: false,
                message: 'Username part of email is too long (maximum 64 characters allowed)'
            };
        }
        
        // Check for invalid patterns in username
        if (/^\.|\.$|\.\./.test(username)) {
            return {
                valid: false,
                message: 'Username cannot start or end with a period or contain consecutive periods'
            };
        }
        
        // Check for invalid characters in username
        if (!/^[a-z0-9.!#$%&'*+/=?^_`{|}~-]+$/i.test(username)) {
            return {
                valid: false,
                message: 'Username contains invalid characters'
            };
        }
        
        // Domain part validation
        if (domain.length === 0) {
            return {
                valid: false,
                message: 'Domain part of email cannot be empty'
            };
        }
        
        if (!domain.includes('.')) {
            return {
                valid: false,
                message: 'Email domain must include at least one period'
            };
        }
        
        // Check for invalid patterns in domain
        if (/^-|-$/.test(domain)) {
            return {
                valid: false,
                message: 'Domain cannot start or end with a hyphen'
            };
        }
        
        // Domain parts validation
        const domainParts = domain.split('.');
        
        // Check domain name (part before TLD)
        if (domainParts[0].length > 63) {
            return {
                valid: false,
                message: 'Domain name is too long (maximum 63 characters allowed)'
            };
        }
        
        // Check for invalid characters in domain
        if (!/^[a-z0-9.-]+$/i.test(domain)) {
            return {
                valid: false,
                message: 'Domain contains invalid characters'
            };
        }
        
        // Check TLD (last part)
        const tld = domainParts[domainParts.length - 1];
        if (tld.length < 2 || tld.length > 10) {
            return {
                valid: false,
                message: 'Email TLD (domain ending) is invalid'
            };
        }
        
        // Check if TLD contains only letters (no numbers or special chars)
        if (!/^[a-z]+$/i.test(tld)) {
            return {
                valid: false,
                message: 'TLD can only contain letters'
            };
        }
        
        // Complex email regex pattern for final validation
        const emailRegex = /^[a-z0-9!#$%&'*+/=?^_`{|}~-]+(?:\.[a-z0-9!#$%&'*+/=?^_`{|}~-]+)*@(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+[a-z0-9](?:[a-z0-9-]*[a-z0-9])?$/i;
        if (!emailRegex.test(email)) {
            return {
                valid: false,
                message: 'Please enter a valid email address format (e.g., name@example.com)'
            };
        }

        return {
            valid: true,
            message: ''
        };
    }

    /**
     * Email suggestion function
     * Provides suggestions for common email typos
     */
    function suggestEmail(email) {
        if (!email || email.trim() === '' || !email.includes('@')) {
            return null;
        }
        
        const commonDomains = ['gmail.com', 'yahoo.com', 'outlook.com', 'hotmail.com', 'aol.com', 'icloud.com'];
        const parts = email.split('@');
        const username = parts[0];
        const domain = parts[1];
        
        // Check for common typos in domains
        const typos = {
            'gamil.com': 'gmail.com',
            'gmail.co': 'gmail.com',
            'gmail.cm': 'gmail.com',
            'gmal.com': 'gmail.com',
            'gmail.comm': 'gmail.com',
            'gmail.cpm': 'gmail.com',
            'yahooo.com': 'yahoo.com',
            'yaho.com': 'yahoo.com',
            'yahoo.co': 'yahoo.com',
            'yahoo.cm': 'yahoo.com',
            'hotmai.com': 'hotmail.com',
            'hotmail.co': 'hotmail.com',
            'hotmai.co': 'hotmail.com',
            'hotmail.cm': 'hotmail.com',
            'outlok.com': 'outlook.com',
            'outlook.co': 'outlook.com',
            'outlookcom': 'outlook.com',
            'outlook.cm': 'outlook.com'
        };
        
        // Check for typos
        if (typos[domain]) {
            return username + '@' + typos[domain];
        }
        
        // Check for close matches
        for (const commonDomain of commonDomains) {
            // Simple Levenshtein distance heuristic (very basic)
            if (domain !== commonDomain && 
                (domain.includes(commonDomain.slice(0, -1)) || 
                 commonDomain.includes(domain.slice(0, -1)))) {
                return username + '@' + commonDomain;
            }
        }
        
        return null;
    }

    // Updated phone validation function - strict 10 digits only
    function validatePhone(phone) {
        // Remove all non-digit characters for validation
        const digits = phone.replace(/\D/g, '');
        
        if (digits.length !== 10) {
            return {
                valid: false,
                message: 'Please enter exactly 10 digits for the phone number'
            };
        }
        
        return {
            valid: true,
            message: ''
        };
    }

    // Name validation function
    function validateName(name) {
        if (name.trim() === '') {
            return {
                valid: false,
                message: 'Name cannot be empty'
            };
        }
        
        if (name.length > 100) {
            return {
                valid: false,
                message: 'Name is too long (maximum 100 characters allowed)'
            };
        }
        
        return {
            valid: true,
            message: ''
        };
    }

    // Address validation function
    function validateAddress(address) {
        if (address.trim() === '') {
            return {
                valid: false,
                message: 'Address cannot be empty'
            };
        }
        
        if (address.length > 255) {
            return {
                valid: false,
                message: 'Address is too long (maximum 255 characters allowed)'
            };
        }
        
        return {
            valid: true,
            message: ''
        };
    }

    // Setup validation for input fields with real-time feedback
    function setupValidation(inputId, validationFunction, errorId, suggestionId = null) {
        const inputElement = document.getElementById(inputId);
        const errorElement = document.getElementById(errorId);
        const suggestionElement = suggestionId ? document.getElementById(suggestionId) : null;
        
        // Real-time validation as user types (with a small delay for better UX)
        let typingTimer;
        const doneTypingInterval = 500; // half a second
        
        inputElement.addEventListener('keyup', function() {
            clearTimeout(typingTimer);
            typingTimer = setTimeout(() => {
                validateAndSuggest(inputElement, validationFunction, errorElement, suggestionElement);
            }, doneTypingInterval);
        });
        
        // Immediate validation on blur (when user leaves the field)
        inputElement.addEventListener('blur', function() {
            clearTimeout(typingTimer);
            validateAndSuggest(inputElement, validationFunction, errorElement, suggestionElement);
        });
        
        // Return a function that can be called to validate the field programmatically
        return function() {
            return validateAndSuggest(inputElement, validationFunction, errorElement, suggestionElement);
        };
    }
    
    function validateAndSuggest(inputElement, validationFunction, errorElement, suggestionElement) {
        // Reset validation state
        inputElement.classList.remove('is-invalid');
        inputElement.classList.remove('is-valid');
        errorElement.style.display = 'none';
        
        if (suggestionElement) {
            suggestionElement.textContent = '';
        }
        
        const value = inputElement.value.trim();
        
        // Empty check for required fields
        if (inputElement.hasAttribute('required') && value === '') {
            inputElement.classList.add('is-invalid');
            errorElement.textContent = `${inputElement.previousElementSibling.textContent.trim()} is required`;
            errorElement.style.display = 'block';
            return false;
        }
        
        // Skip further validation if empty and not required
        if (value === '' && !inputElement.hasAttribute('required')) {
            return true;
        }
        
        // Format check
        const validationResult = validationFunction(value);
        if (!validationResult.valid) {
            inputElement.classList.add('is-invalid');
            errorElement.textContent = validationResult.message;
            errorElement.style.display = 'block';
            
            // Add email suggestion if applicable
            if (inputElement.id === 'email' && suggestionElement) {
                const suggestion = suggestEmail(value);
                if (suggestion) {
                    suggestionElement.textContent = `Did you mean: ${suggestion}?`;
                    
                    // Make the suggestion clickable
                    suggestionElement.style.cursor = 'pointer';
                    suggestionElement.style.color = '#0d6efd';
                    suggestionElement.style.textDecoration = 'underline';
                    
                    suggestionElement.onclick = function() {
                        inputElement.value = suggestion;
                        validateAndSuggest(inputElement, validationFunction, errorElement, suggestionElement);
                    };
                }
            }
            
            return false;
        } else {
            // Show valid feedback
            inputElement.classList.add('is-valid');
            return true;
        }
    }

    // Initialize validation functions for each field
    const validateEmailField = setupValidation('email', validateEmail, 'email-error', 'email-suggestions');
    const validatePhoneField = setupValidation('phone', validatePhone, 'phone-error');
    const validateNameField = setupValidation('name', validateName, 'name-error');
    const validateAddressField = setupValidation('address', validateAddress, 'address-error');

    // Auto-convert email to lowercase
    const emailInput = document.getElementById('email');
    emailInput.addEventListener('input', function() {
        // Get cursor position before change
        const start = this.selectionStart;
        const end = this.selectionEnd;
        
        // Convert to lowercase
        this.value = this.value.toLowerCase();
        
        // Restore cursor position
        this.setSelectionRange(start, end);
    });

    // Phone handling - strip non-digits as user types
    const phoneInput = document.getElementById('phone');
    phoneInput.addEventListener('input', function(e) {
        // Get only digits from the input
        let digits = this.value.replace(/\D/g, '');
        
        // Store cursor position
        const cursorPos = this.selectionStart;
        const oldLength = this.value.length;
        
        // Limit to 10 digits
        if (digits.length > 10) {
            digits = digits.substring(0, 10);
        }
        
        // Update the input value with only digits
        this.value = digits;
        
        // Adjust cursor position if text changed
        const newLength = this.value.length;
        const cursorAdjust = newLength - oldLength;
        
        // Only set selection range if the element is focused
        if (document.activeElement === this) {
            let newPos = cursorPos + cursorAdjust;
            if (newPos < 0) newPos = 0;
            if (newPos > this.value.length) newPos = this.value.length;
            this.setSelectionRange(newPos, newPos);
        }
    });

    // Client-side form validation
    document.getElementById('addCustomerForm').addEventListener('submit', function(event) {
        let isValid = true;
        
        // Validate all fields
        if (!validateNameField()) isValid = false;
        if (!validateEmailField()) isValid = false;
        if (!validatePhoneField()) isValid = false;
        if (!validateAddressField()) isValid = false;
        
        if (!isValid) {
            event.preventDefault();
            
            // Scroll to the first error
            const firstError = document.querySelector('.is-invalid');
            if (firstError) {
                firstError.focus();
                firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }
    });

    // Dependent Dropdown for Packages
    const productSelect = document.getElementById('product_id');
    const packagesContainer = document.getElementById('packages-container');

    function loadPackages(productId, selectedPackageId = null) {
        if (!productId) {
            packagesContainer.innerHTML = '<p class="text-muted text-center my-3">Select a product first to see packages.</p>';
            return;
        }

        packagesContainer.innerHTML = '<div class="text-center my-3"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>';
        
        // Fetch packages via AJAX
        fetch(`get_packages.php?product_id=${productId}`)
            .then(response => response.json())
            .then(data => {
                if (data.length === 0) {
                    packagesContainer.innerHTML = '<p class="text-muted text-center my-3">No active packages found for this product.</p>';
                    return;
                }

                let html = `
                    <table class="table table-sm table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 50px;">Select</th>
                                <th>Package Name</th>
                                <th style="width: 150px;">Amount (Rs.)</th>
                                <th style="width: 150px;">Max Count</th>
                            </tr>
                        </thead>
                        <tbody>
                `;

                data.forEach(pkg => {
                    const isChecked = (selectedPackageId && pkg.id == selectedPackageId) ? 'checked' : '';
                    const default_max = pkg.default_max_count !== null ? pkg.default_max_count : '';
                    html += `
                        <tr>
                            <td class="text-center">
                                <input class="form-check-input" type="radio" name="package_id" value="${pkg.id}" id="pkg_${pkg.id}" ${isChecked} required>
                            </td>
                            <td>
                                <label class="form-check-label d-block" for="pkg_${pkg.id}">
                                    ${pkg.name || pkg.description} <br>
                                    <small class="text-muted">Standard: Rs. ${pkg.default_amount} | Max: ${default_max || 'N/A'}</small>
                                </label>
                            </td>
                            <td>
                                <input type="number" step="0.01" class="form-control form-control-sm" name="custom_amounts[${pkg.id}]" value="${pkg.default_amount}" placeholder="${pkg.default_amount}">
                            </td>
                            <td>
                                <input type="number" class="form-control form-control-sm" name="custom_max_counts[${pkg.id}]" value="${default_max}" placeholder="${default_max}">
                            </td>
                        </tr>
                    `;
                });

                html += '</tbody></table>';
                packagesContainer.innerHTML = html;
            })
            .catch(error => {
                console.error('Error fetching packages:', error);
                packagesContainer.innerHTML = '<p class="text-danger text-center my-3">Error loading packages.</p>';
            });
    }

    productSelect.addEventListener('change', function() {
        loadPackages(this.value);
    });

    // Initial load if a product is already selected
    if (productSelect.value) {
        loadPackages(productSelect.value, '<?php echo isset($package_id) ? $package_id : ""; ?>');
    }
    </script>
    
    <!-- jQuery and Select2 JS -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
    $(document).ready(function() {
        $('#product_id').select2({
            theme: 'bootstrap-5',
            placeholder: '-- Select Product --',
            allowClear: true,
            width: '100%'
        });
        
        $('#billing_date').select2({
            theme: 'bootstrap-5',
            placeholder: '-- No specific billing date --',
            allowClear: true,
            width: '100%',
            dropdownCssClass: 'billing-dropdown',
            minimumResultsForSearch: Infinity
        });

        $('#status').select2({
            theme: 'bootstrap-5',
            width: '100%',
            minimumResultsForSearch: Infinity
        });

        // Re-trigger package loading when Select2 changes
        $('#product_id').on('select2:select', function (e) {
            const data = e.params.data;
            loadPackages(data.id);
        });

        $('#product_id').on('select2:unselect', function (e) {
            loadPackages('');
        });
    });
    </script>
    
    <?php
    // Close the connection at the end of the script
    $conn->close();
    ?>
</body>

</html>