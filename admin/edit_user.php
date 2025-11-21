<?php
// Start session at the very beginning
session_start();

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

// Include necessary files
include 'db_connection.php';
include 'functions.php';

// Check if the user ID is provided in the URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error_message'] = "User ID is required.";
    header("Location: users.php");
    exit();
}

$user_id = intval($_GET['id']);

// Fetch user details from the database
try {
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        // No user found with the given ID
        $_SESSION['error_message'] = "User not found.";
        header("Location: users.php");
        exit();
    }

    $user = $result->fetch_assoc();
    $stmt->close();
} catch (Exception $e) {
    $_SESSION['error_message'] = "Database error: " . $e->getMessage();
    header("Location: users.php");
    exit();
}

// Generate CSRF token if it doesn't exist
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Determine values to display (prioritize GET parameters for form repopulation)
$name = isset($_GET['name']) ? urldecode($_GET['name']) : $user['name'];
$email = isset($_GET['email']) ? urldecode($_GET['email']) : $user['email'];
$mobile = isset($_GET['mobile']) ? urldecode($_GET['mobile']) : $user['mobile'];
$nic = isset($_GET['nic']) ? urldecode($_GET['nic']) : $user['nic'];
$address = isset($_GET['address']) ? urldecode($_GET['address']) : $user['address'];
$status = isset($_GET['status']) ? $_GET['status'] : $user['status'];
$role_id = isset($_GET['role_id']) ? $_GET['role_id'] : $user['role_id'];

// Fetch available roles dynamically
$roles = [];
$roleQuery = "SELECT id, name FROM roles";
$roleResult = $conn->query($roleQuery);

// Collect roles into an array
while ($roleRow = $roleResult->fetch_assoc()) {
    $roles[] = $roleRow;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Edit User</title>
    <!-- FAVICON -->
    <link rel="icon" href="img/system/letter-f.png" type="image/png">
    <link href="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/style.min.css" rel="stylesheet" />
    <link href="css/styles.css" rel="stylesheet" />
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
    <style>
        .form-container {
            padding: 25px;
            background-color: #fff;
            border-radius: 5px;
            margin-bottom: 30px;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15) !important;
        }

        .section-header {
            border-left: 4px solid #1565C0;
            padding-left: 10px;
            margin-bottom: 20px;
            font-size: 18px;
            font-weight: 500;
        }

        .form-floating .form-control {
            height: calc(3.5rem + 2px);
        }

        .save-btn {
            background-color: #1565C0;
            float: right;
            padding: 8px 25px;
        }
        
        .error-feedback {
            color: #dc3545;
            font-size: 0.875em;
            margin-top: 0.25rem;
            display: none;
        }
        
        .is-invalid {
            border-color: #dc3545;
            padding-right: calc(1.5em + 0.75rem);
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 12 12' width='12' height='12' fill='none' stroke='%23dc3545'%3e%3ccircle cx='6' cy='6' r='4.5'/%3e%3cpath stroke-linejoin='round' d='M5.8 3.6h.4L6 6.5z'/%3e%3ccircle cx='6' cy='8.2' r='.6' fill='%23dc3545' stroke='none'/%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right calc(0.375em + 0.1875rem) center;
            background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem);
        }
        
        .form-control:focus, .form-select:focus {
            border-color: #86b7fe;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
        }
        
        .input-group .btn:focus {
            box-shadow: none;
        }
        
        .input-icon {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
        }
        
        .back-btn {
            margin-right: 10px;
        }
        
        .alert {
            border-radius: 5px;
            border-left-width: 5px;
        }
        
        .alert-success {
            border-left-color: #198754;
        }
        
        .alert-danger {
            border-left-color: #dc3545;
        }
        
        .form-floating label {
            opacity: 0.65;
        }
        
        /* Add spacing between section header and first field */
        .section-header {
            margin-bottom: 30px;  /* Increased from 20px to 30px */
        }
    </style>
</head>

<body class="sb-nav-fixed">
    <?php include 'navbar.php'; ?>
    <div id="layoutSidenav">
        <?php include 'sidebar.php'; ?>

        <div id="layoutSidenav_content">
            <main>
                <div class="container-fluid px-4">
                    <h4 class="mt-4 mb-3">Edit User</h4>

                    <!-- Success Message Display -->
                    <?php if (isset($_SESSION['success_message'])): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle me-2"></i>
                            <?php 
                            echo htmlspecialchars($_SESSION['success_message']); 
                            unset($_SESSION['success_message']);
                            ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <!-- Error Message Display -->
                    <?php if (isset($_SESSION['error_message'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <?php 
                            echo htmlspecialchars($_SESSION['error_message']); 
                            unset($_SESSION['error_message']);
                            ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <!-- Breadcrumb Navigation -->
                    <nav aria-label="breadcrumb" class="mb-4">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none"><i class="fas fa-home me-1"></i>Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="users.php" class="text-decoration-none"><i class="fas fa-users me-1"></i>Users</a></li>
                            <li class="breadcrumb-item active" aria-current="page"><i class="fas fa-user-edit me-1"></i>Edit User</li>
                        </ol>
                    </nav>

                    <div class="form-container">
                        <form method="POST" action="update_edit_user.php" id="editUserForm" novalidate>
                            <!-- CSRF Token and User ID -->
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                            <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($user_id); ?>">
                            <input type="hidden" name="edit_mode" value="1">

                            <div class="row">
                                <!-- User Details Section -->
                                <div class="col-md-6">
                                    <div class="section-header mb-2">User Details</div>

                                    <!-- Name Field -->
                                    <div class="mb-3 position-relative">
                                        <label for="name" class="form-label">
                                            <i class="fas fa-user me-1"></i> Full Name
                                        </label>
                                        <input type="text" class="form-control" id="name" name="name"
                                            placeholder="Full Name" 
                                            value="<?php echo htmlspecialchars($name); ?>" required>
                                        <div class="error-feedback" id="name-error"></div>
                                    </div>

                                    <!-- Email Field -->
                                    <div class="mb-3 position-relative">
                                        <label for="email" class="form-label">
                                            <i class="fas fa-envelope me-1"></i> Email Address
                                        </label>
                                        <input type="email" class="form-control" id="email" name="email"
                                            placeholder="name@example.com" 
                                            value="<?php echo htmlspecialchars($email); ?>" required>
                                        <div class="error-feedback" id="email-error"></div>
                                    </div>

                                    <!-- Password Field (Optional for Edit) -->
                                    <div class="mb-3 position-relative">
                                        <label for="password" class="form-label">
                                            <i class="fas fa-lock me-1"></i> Password (Leave blank to keep current)
                                        </label>
                                        <div class="input-group">
                                            <input type="password" class="form-control" id="password"
                                                name="password" placeholder="New Password">
                                            <button class="btn btn-outline-secondary toggle-password"
                                                type="button">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Mobile Field -->
                                    <div class="mb-3 position-relative">
                                        <label for="mobile" class="form-label">
                                            <i class="fas fa-mobile-alt me-1"></i> Mobile Number
                                        </label>
                                        <input type="tel" class="form-control" id="mobile" name="mobile"
                                            placeholder="Enter Mobile Number" 
                                            value="<?php echo htmlspecialchars($mobile); ?>">
                                        <div class="error-feedback" id="mobile-error"></div>
                                    </div>
                                </div>

                                <!-- Additional Details Section -->
                                <div class="col-md-6">
                                    <div class="section-header mb-2">Configuration Details</div>
                                    
                                    <!-- NIC Field -->
                                    <div class="mb-3 position-relative">
                                        <label for="nic" class="form-label">
                                            <i class="fas fa-id-card me-1"></i> NIC Number
                                        </label>
                                        <input type="text" class="form-control" id="nic" name="nic"
                                            placeholder="Enter NIC Number" 
                                            value="<?php echo htmlspecialchars($nic); ?>">
                                        <div class="error-feedback" id="nic-error"></div>
                                    </div>

                                    <!-- Address Field -->
                                    <div class="mb-3">
                                        <label for="address" class="form-label">
                                            <i class="fas fa-map-marker-alt me-1"></i> Address
                                        </label>
                                        <textarea class="form-control" id="address" name="address"
                                            placeholder="Enter Full Address" rows="3"><?php echo htmlspecialchars($address); ?></textarea>
                                    </div>

                                    <!-- Status Field -->
                                    <div class="mb-3">
                                        <label for="status" class="form-label">
                                            <i class="fas fa-toggle-on me-1"></i> Status
                                        </label>
                                        <select class="form-select" id="status" name="status" required>
                                            <option value="active" <?php echo (strtolower($status) == 'active') ? 'selected' : ''; ?>>Active</option>
                                            <option value="inactive" <?php echo (strtolower($status) == 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                                        </select>
                                    </div>

                                    <!-- Role Field - Dynamically Populated -->
                                    <div class="mb-3">
                                        <label for="role_id" class="form-label">
                                            <i class="fas fa-user-tag me-1"></i> Role
                                        </label>
                                        <select class="form-select" id="role_id" name="role_id" required>
                                            <option value="">Select Role...</option>
                                            <?php foreach ($roles as $role): ?>
                                                <option value="<?= htmlspecialchars($role['id']) ?>" 
                                                        <?php echo ($role['id'] == $role_id) ? 'selected' : ''; ?>>
                                                    <?= htmlspecialchars($role['name']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div class="error-feedback" id="role-error"></div>
                                    </div>
                                </div>
                            </div>

                            <!-- Submit Buttons -->
                            <div class="row mt-4">
                                <div class="col-12">
                                    <a href="users.php" class="btn btn-secondary back-btn">
                                        <i class="fas fa-arrow-left me-1"></i> Back to Users
                                    </a>
                                    <button type="submit" class="btn btn-primary save-btn" id="submitBtn">
                                        <i class="fas fa-save me-1"></i> Save Changes
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
    <script src="js/scripts.js"></script>
    <script>
     // Password toggle visibility
document.querySelector('.toggle-password').addEventListener('click', function () {
    const passwordInput = document.getElementById('password');
    const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
    passwordInput.setAttribute('type', type);
    this.querySelector('i').classList.toggle('fa-eye');
    this.querySelector('i').classList.toggle('fa-eye-slash');
});

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
    if (address.trim() === '' && !document.getElementById('address').hasAttribute('required')) {
        return {
            valid: true,
            message: ''
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

// Password validation function
function validatePassword(password) {
    // For edit user, empty password is valid (means no change)
    if (password.trim() === '') {
        return {
            valid: true,
            message: ''
        };
    }
    
    if (password.length < 8) {
        return {
            valid: false,
            message: 'Password must be at least 8 characters long'
        };
    }
    
    return {
        valid: true,
        message: ''
    };
}

// Mobile validation function
function validateMobile(mobile) {
    if (mobile.trim() === '' && !document.getElementById('mobile').hasAttribute('required')) {
        return {
            valid: true,
            message: ''
        };
    }
    
    // Clean the mobile number - remove all non-digit characters
    const digits = mobile.replace(/\D/g, '');
    
    if (digits.length !== 10) {
        return {
            valid: false,
            message: 'Mobile number must be exactly 10 digits'
        };
    }
    
    return {
        valid: true,
        message: ''
    };
}

// NIC validation function
function validateNIC(nic) {
    if (nic.trim() === '' && !document.getElementById('nic').hasAttribute('required')) {
        return {
            valid: true,
            message: ''
        };
    }
    
    const nicRegex = /^([0-9]{9}[vVxX]?|[0-9]{12})$/;
    if (!nicRegex.test(nic)) {
        return {
            valid: false,
            message: 'Please enter a valid NIC number (9 digits + V/X or 12 digits)'
        };
    }
    
    return {
        valid: true,
        message: ''
    };
}

// Role validation function
function validateRole(roleId) {
    if (roleId.trim() === '') {
        return {
            valid: false,
            message: 'Please select a role'
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
    
    if (!inputElement || !errorElement) return () => true;
    
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
        errorElement.textContent = `${inputElement.previousElementSibling ? inputElement.previousElementSibling.textContent.trim() : 'This field'} is required`;
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

// Mobile handling - strip non-digits as user types
const mobileInput = document.getElementById('mobile');
mobileInput.addEventListener('input', function(e) {
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

// Add email suggestion element if it doesn't exist
if (!document.getElementById('email-suggestions')) {
    const emailField = document.getElementById('email');
    const emailError = document.getElementById('email-error');
    if (emailField && emailError) {
        const suggestionElement = document.createElement('div');
        suggestionElement.id = 'email-suggestions';
        suggestionElement.style.fontSize = '0.875em';
        suggestionElement.style.marginTop = '0.25rem';
        emailError.parentNode.insertBefore(suggestionElement, emailError.nextSibling);
    }
}

// Initialize validation functions for each field
const validateEmailField = setupValidation('email', validateEmail, 'email-error', 'email-suggestions');
const validateNameField = setupValidation('name', validateName, 'name-error');
const validatePasswordField = setupValidation('password', validatePassword, 'password-error');
const validateMobileField = setupValidation('mobile', validateMobile, 'mobile-error');
const validateNICField = setupValidation('nic', validateNIC, 'nic-error');
const validateAddressField = setupValidation('address', validateAddress, 'address-error');
const validateRoleField = setupValidation('role_id', validateRole, 'role-error');

// Client-side form validation
document.getElementById('editUserForm').addEventListener('submit', function(event) {
    let isValid = true;
    
    // Validate all fields
    if (!validateNameField()) isValid = false;
    if (!validateEmailField()) isValid = false;
    if (!validatePasswordField()) isValid = false;
    if (!validateMobileField()) isValid = false;
    if (!validateNICField()) isValid = false;
    if (!validateAddressField()) isValid = false;
    if (!validateRoleField()) isValid = false;
    
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

// Add animation to success and error messages
document.addEventListener('DOMContentLoaded', function() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        // Auto-dismiss alerts after 5 seconds
        setTimeout(() => {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });
});
    </script>
</body>
</html>

<?php
// Close the connection at the end of the script
$conn->close();
?>