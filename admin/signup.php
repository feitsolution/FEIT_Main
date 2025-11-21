<?php
session_start();
include 'db_connection.php';

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Initialize variables
$error_message = "";
$success_message = "";

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize and validate inputs
    $business_name = trim($_POST['business_name']);
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $phone = trim($_POST['phone']);
    $address_line1 = trim($_POST['address_line1']);
    $address_line2 = trim($_POST['address_line2']);
    
    // Combine address lines
    $full_address = $address_line1;
    if (!empty($address_line2)) {
        $full_address .= ", " . $address_line2;
    }
    
    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Invalid email format.";
    }
    // Validate phone number (Sri Lankan format)
    elseif (!preg_match('/^\+94\d{9}$/', $phone)) {
        $error_message = "Please enter a valid Sri Lankan phone number (+94xxxxxxxxx).";
    }
    // Check if email already exists with active status
    else {
        $check_email_sql = "SELECT email, status FROM customers WHERE email = ?";
        $check_stmt = $conn->prepare($check_email_sql);
        $check_stmt->bind_param("s", $email);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            // Only show error if account exists and is active
            if ($row['status'] === 'Active') {
                $error_message = "An active account with this email already exists.";
            } else {
                // If account exists but is inactive, update it instead of creating new
                $update_sql = "UPDATE customers SET name = ?, phone = ?, address = ?, status = 'Inactive' WHERE email = ?";
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bind_param("ssss", $business_name, $phone, $full_address, $email);
                
                if ($update_stmt->execute()) {
                    $success_message = "Account registration submitted successfully! Your account is pending approval. Please contact the administrator to activate your account.";
                } else {
                    $error_message = "Error updating account: " . $conn->error;
                }
                $update_stmt->close();
            }
        } else {
            // Insert new customer with Inactive status
            $insert_sql = "INSERT INTO customers (name, email, phone, address, status) VALUES (?, ?, ?, ?, 'Inactive')";
            $insert_stmt = $conn->prepare($insert_sql);
            $insert_stmt->bind_param("ssss", $business_name, $email, $phone, $full_address);
            
            if ($insert_stmt->execute()) {
                $success_message = "Account registration submitted successfully! Your account is pending approval. Please contact the administrator to activate your account.";
                
                // Don't auto-login inactive users
                // Remove or comment out these session variables
                /*
                $_SESSION['customer_id'] = $conn->insert_id;
                $_SESSION['customer_name'] = $business_name;
                $_SESSION['customer_email'] = $email;
                $_SESSION['logged_in'] = true;
                */
                
                // Optional: Redirect to signin page with message after success
                // header("Location: signin.php?message=registration_pending");
                // exit();
            } else {
                $error_message = "Error creating account: " . $conn->error;
            }
            $insert_stmt->close();
        }
        $check_stmt->close();
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up for OMS</title>
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"/>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Noto Sans', Helvetica, Arial, sans-serif;
            background: linear-gradient(135deg, #0d1117 0%, #21262d 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .container {
            display: flex;
            max-width: 1200px;
            width: 100%;
            background: #0d1117;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 16px 32px rgba(0, 0, 0, 0.4);
        }

        .left-section {
            flex: 1;
            background: linear-gradient(135deg, #202f53 0%, #000000 100%);
            padding: 60px 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            position: relative;
            overflow: hidden;
        }

        .left-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                linear-gradient(0deg, 
                    rgba(168, 85, 247, 0.4) 0%,
                    rgba(59, 130, 246, 0.3) 30%,
                    rgba(245, 158, 11, 0.2) 60%,
                    transparent 100%
                ),
                radial-gradient(circle at 20% 80%, rgba(168, 85, 247, 0.5) 0%, transparent 40%),
                radial-gradient(circle at 80% 60%, rgba(59, 130, 246, 0.4) 0%, transparent 35%),
                radial-gradient(circle at 60% 90%, rgba(59, 140, 11, 0.3) 0%, transparent 30%);
            opacity: 0.6;
        }

        .logo-section {
            position: absolute;
            top: 40px;
            left: 82px;
            z-index: 1;
            text-align: center;
        }
        /* .logo-section {
    position: absolute;
    top: 13px;
    left: 45px;
    z-index: 1;
} */

        .logo-section .logo {
            width:200px;
            height: 80px;
            object-fit: contain;
        }

        .logo-section h1 {
            font-size: 2rem;
            font-weight: 550;
            color: #ffffff;
            margin-bottom: 1rem;
        }

        .description-text {
            font-size: 0.95rem;
            color: #9ca3af;
            max-width: 400px;
            line-height: 1.6;
            margin-bottom: 20px;
        }

        .show-features-btn {
            background: linear-gradient(135deg, #3b82f6 0%, #059669 100%);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-bottom: 20px;
            z-index: 1;
            position: relative;
        }

        .show-features-btn:hover {
            background: linear-gradient(135deg, #3b82f6 0%, #059669 100%);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }

        .features {
            margin-top: 20px;
            z-index: 1;
            opacity: 0;
            max-height: 0;
            overflow: hidden;
            transition: all 0.5s ease;
        }

        .features.show {
            opacity: 1;
            max-height: 300px;
        }

        .feature-item {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
            color: #d1d5db;
            transform: translateX(-20px);
            transition: all 0.3s ease;
        }

        .features.show .feature-item {
            transform: translateX(0);
        }

        .features.show .feature-item:nth-child(1) { transition-delay: 0.1s; }
        .features.show .feature-item:nth-child(2) { transition-delay: 0.2s; }
        .features.show .feature-item:nth-child(3) { transition-delay: 0.3s; }
        .features.show .feature-item:nth-child(4) { transition-delay: 0.4s; }

        .feature-item i {
            color: #10b981;
            margin-right: 12px;
            font-size: 1.1rem;
        }

        .right-section {
            flex: 1;
            background: #ffffff;
            padding: 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .form-header {
            text-align: center;
            margin-bottom: 30px;
            position: relative;
        }

        .refresh-btn {
            position: absolute;
            top: 0;
            right: 0;
            background: #f3f4f6;
            border: none;
            border-radius: 6px;
            padding: 8px 12px;
            cursor: pointer;
            font-size: 0.8rem;
            color: #6b7280;
            transition: all 0.2s ease;
        }

        .refresh-btn:hover {
            background: #e5e7eb;
            color: #374151;
        }

        .refresh-btn i {
            margin-right: 4px;
        }

        .form-header h2 {
            font-size: 1.75rem;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 8px;
        }

        .form-header p {
            color: #6b7280;
            font-size: 0.95rem;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-row {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-size: 0.875rem;
            font-weight: 600;
            color: #374151;
            margin-bottom: 6px;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 11px 33px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 0.95rem;
            transition: all 0.2s ease;
            background: #ffffff;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .checkbox-group {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            margin-bottom: 25px;
        }

        .checkbox-group input[type="checkbox"] {
            width: auto;
            margin-top: 2px;
            flex-shrink: 0;
        }

        .checkbox-group label {
            font-size: 0.875rem;
            color: #4b5563;
            cursor: pointer;
            margin-bottom: 0;
        }

        .submit-btn {
            width: 100%;
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            color: white;
            border: none;
            padding: 14px 20px;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .submit-btn:hover {
            background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        }

        .signin-link {
            text-align: center;
            margin-top: 20px;
            color: #6b7280;
            font-size: 0.9rem;
        }

        .signin-link a {
            color: #3b82f6;
            text-decoration: none;
            font-weight: 600;
        }

        .signin-link a:hover {
            text-decoration: underline;
        }

        .error-message {
            color: #dc2626;
            background-color: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 20px;
            font-size: 0.875rem;
        }

        .success-message {
            color: #059669;
            background-color: #ecfdf5;
            border: 1px solid #a7f3d0;
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 20px;
            font-size: 0.875rem;
            line-height: 1.5;
        }

        .input-error {
            border-color: #dc2626 !important;
        }

        .error-feedback {
            color: #dc2626;
            font-size: 0.8rem;
            margin-top: 4px;
            display: none;
        }

        @media (max-width: 768px) {
            .container {
                flex-direction: column;
                max-width: 500px;
            }

            .left-section {
                padding: 40px 20px;
            }

            .right-section {
                padding: 30px 20px;
            }

            .form-row {
                flex-direction: column;
                gap: 0;
            }

            .logo-section {
                position: relative;
                top: auto;
                left: auto;
                text-align: center;
                margin-bottom: 20px;
            }

            .logo-section .logo-container {
                justify-content: center;
            }

            .logo-section h1 {
                font-size: 1.8rem;
            }

            .refresh-btn {
                position: static;
                margin-bottom: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="left-section">
            <div class="logo-section">
                <div class="logo-container">
                    <img src="..\admin\img\system\FEIT.png" alt="OMS Logo" class="logo">
                    <h1>Create your OMS account</h1>
                </div>
                <p class="description-text">Streamline your business operations with our comprehensive Order Management System</p>
                
                <button class="show-features-btn" onclick="toggleFeatures()">
                    <i class="fas fa-arrow-down" id="toggleIcon"></i> Show Features
                </button>

                <div class="features" id="featuresList">
                    <div class="feature-item">
                        <i class="fas fa-check-circle"></i>
                        <span>Advanced order tracking</span>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-check-circle"></i>
                        <span>Inventory management</span>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-check-circle"></i>
                        <span>Real-time analytics</span>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-check-circle"></i>
                        <span>Multi-channel integration</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="right-section">
            <div class="form-header">
             
                <h2>Sign up </h2>
                <p>Start managing your orders efficiently today</p>
            </div>

            <?php if (!empty($error_message)): ?>
                <div class="error-message">
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($success_message)): ?>
                <div class="success-message">
                    <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>

            <form id="signupForm" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST" onsubmit="return validateForm()">
                <div class="form-group">
                    <label for="business_name">Customer Name / Business Name</label>
                    <input type="text" id="business_name" name="business_name" placeholder="Enter your name or business name" value="<?php echo isset($_POST['business_name']) ? htmlspecialchars($_POST['business_name']) : ''; ?>" required>
                    <div id="businessNameError" class="error-feedback">Please enter your name or business name.</div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" placeholder="name@company.com" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                        <div id="emailError" class="error-feedback">Please enter a valid email address.</div>
                    </div>
                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="tel" id="phone" name="phone" placeholder="+94 71 234 5678" pattern="^\+94\d{9}$" value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>" required>
                        <div id="phoneError" class="error-feedback">Please enter a valid Sri Lankan phone number (+94xxxxxxxxx).</div>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="address_line1">Address Line 1</label>
                        <input type="text" id="address_line1" name="address_line1" placeholder="Street address" value="<?php echo isset($_POST['address_line1']) ? htmlspecialchars($_POST['address_line1']) : ''; ?>" required>
                        <div id="address1Error" class="error-feedback">Please enter your address.</div>
                    </div>
                    <div class="form-group">
                        <label for="address_line2">Address Line 2</label>
                        <input type="text" id="address_line2" name="address_line2" placeholder="Apartment, suite, etc." value="<?php echo isset($_POST['address_line2']) ? htmlspecialchars($_POST['address_line2']) : ''; ?>">
                    </div>
                </div>

                <div class="checkbox-group">
                    <input type="checkbox" id="terms" name="terms" required>
                    <label for="terms">I agree to all the Terms & Conditions and Privacy Policy</label>
                </div>

                <button type="submit" class="submit-btn">Create account</button>
            </form>

            <div class="signin-link">
                Already have an account? <a href="/order_management/dist/pages/login.php">Sign in</a>
            </div>
        </div>
    </div>

    <script>
        function toggleFeatures() {
            const featuresList = document.getElementById('featuresList');
            const toggleIcon = document.getElementById('toggleIcon');
            const button = document.querySelector('.show-features-btn');
            
            featuresList.classList.toggle('show');
            
            if (featuresList.classList.contains('show')) {
                toggleIcon.classList.remove('fa-arrow-down');
                toggleIcon.classList.add('fa-arrow-up');
                button.innerHTML = '<i class="fas fa-arrow-up" id="toggleIcon"></i> Hide Features';
            } else {
                toggleIcon.classList.remove('fa-arrow-up');
                toggleIcon.classList.add('fa-arrow-down');
                button.innerHTML = '<i class="fas fa-arrow-down" id="toggleIcon"></i> Show Features';
            }
        }

        function clearForm() {
            // Clear all form fields
            document.getElementById('signupForm').reset();
            
            // Clear all error states
            const errorInputs = document.querySelectorAll('.input-error');
            errorInputs.forEach(input => {
                input.classList.remove('input-error');
            });
            
            // Hide all error messages
            const errorMessages = document.querySelectorAll('.error-feedback');
            errorMessages.forEach(error => {
                error.style.display = 'none';
            });
            
            // Hide features if shown
            const featuresList = document.getElementById('featuresList');
            const toggleIcon = document.getElementById('toggleIcon');
            const button = document.querySelector('.show-features-btn');
            
            if (featuresList.classList.contains('show')) {
                featuresList.classList.remove('show');
                toggleIcon.classList.remove('fa-arrow-up');
                toggleIcon.classList.add('fa-arrow-down');
                button.innerHTML = '<i class="fas fa-arrow-down" id="toggleIcon"></i> Show Features';
            }
        }

        // Real-time validation
        document.getElementById('email').addEventListener('input', validateEmailField);
        document.getElementById('phone').addEventListener('input', validatePhoneField);

        function validateEmailField() {
            const email = document.getElementById('email');
            const emailError = document.getElementById('emailError');
            
            if (isValidEmail(email.value)) {
                email.classList.remove('input-error');
                emailError.style.display = 'none';
                return true;
            } else {
                email.classList.add('input-error');
                emailError.style.display = 'block';
                return false;
            }
        }

        function validatePhoneField() {
            const phone = document.getElementById('phone');
            const phoneError = document.getElementById('phoneError');
            const phonePattern = /^\+94\d{9}$/;
            
            if (phonePattern.test(phone.value)) {
                phone.classList.remove('input-error');
                phoneError.style.display = 'none';
                return true;
            } else {
                phone.classList.add('input-error');
                phoneError.style.display = 'block';
                return false;
            }
        }

        function isValidEmail(email) {
            const basicPattern = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
            return basicPattern.test(email);
        }

        function validateForm() {
            let isValid = true;

            // Validate business name
            const businessName = document.getElementById('business_name');
            const businessNameError = document.getElementById('businessNameError');
            if (businessName.value.trim() === '') {
                businessName.classList.add('input-error');
                businessNameError.style.display = 'block';
                isValid = false;
            } else {
                businessName.classList.remove('input-error');
                businessNameError.style.display = 'none';
            }

            // Validate address line 1
            const address1 = document.getElementById('address_line1');
            const address1Error = document.getElementById('address1Error');
            if (address1.value.trim() === '') {
                address1.classList.add('input-error');
                address1Error.style.display = 'block';
                isValid = false;
            } else {
                address1.classList.remove('input-error');
                address1Error.style.display = 'none';
            }

            const isEmailValid = validateEmailField();
            const isPhoneValid = validatePhoneField();

            return isValid && isEmailValid && isPhoneValid;
        }
    </script>
</body>
</html>