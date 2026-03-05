<?php
// Start session at the very beginning
session_start();

// Check if user is logged in, if not redirect to login page
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    // Clear any existing output buffers
    if (ob_get_level()) {
        ob_end_clean();
    }
    header("Location: /lily_collection/dist/pages/login.php");
    exit();
}

// Include the database connection file
include($_SERVER['DOCUMENT_ROOT'] . '/lily_collection/dist/connection/db_connection.php');

$isStep2 = ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['name']) && !isset($_POST['action_type']));
$step = $isStep2 ? 2 : 1;

$p_name = $_POST['name'] ?? '';
$p_status = $_POST['status'] ?? 'active';
$p_lkr_price = $_POST['lkr_price'] ?? '';
$p_product_code = $_POST['product_code'] ?? '';
$p_stock_quantity = $_POST['stock_quantity'] ?? '0';
$p_low_stock_threshold = $_POST['low_stock_threshold'] ?? '10';
$p_description = $_POST['description'] ?? '';

// Fetch all active materials for the autocomplete logic
$materialsQuery = "SELECT id, name, material_code FROM Material WHERE status = 'active' ORDER BY name ASC";
$materialsResult = $conn->query($materialsQuery);
$materialsData = [];
if ($materialsResult && $materialsResult->num_rows > 0) {
    while ($m = $materialsResult->fetch_assoc()) {
        $materialsData[] = $m;
    }
}

// Function to generate CSRF token
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

include($_SERVER['DOCUMENT_ROOT'] . '/lily_collection/dist/include/navbar.php');
include($_SERVER['DOCUMENT_ROOT'] . '/lily_collection/dist/include/sidebar.php');
?>

<!doctype html>
<html lang="en" data-pc-preset="preset-1" data-pc-sidebar-caption="true" data-pc-direction="ltr" dir="ltr" data-pc-theme="light">

<head>
    <!-- TITLE -->
    <title>Order Management Admin Portal - Add Product</title>

    <?php
    include($_SERVER['DOCUMENT_ROOT'] . '/lily_collection/dist/include/head.php');
    ?>
    
    <!-- [Template CSS Files] -->
    <link rel="stylesheet" href="../assets/css/style.css" id="main-style-link" />
    <link rel="stylesheet" href="../assets/css/products.css" id="main-style-link" />
    <link rel="stylesheet" href="../assets/css/bom_styles.css" id="main-style-link" />
 
    <!-- Custom CSS for AJAX notifications -->
    <style>
        .ajax-notification {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            max-width: 400px;
            margin-bottom: 10px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
            border-radius: 8px;
            animation: slideInRight 0.3s ease-out;
            border: 1px solid transparent;
            padding: 1rem 1.5rem;
            border-left: 4px solid;
        }

        /* Enhanced Bootstrap alert colors with gradients and left border */
        .alert-success {
            color: #0f5132;
            background: linear-gradient(135deg, #f8f9fa 0%, #d1e7dd 100%);
            border-left-color: #28a745;
        }

        .alert-danger {
            color: #842029;
            background: linear-gradient(135deg, #f8f9fa 0%, #f8d7da 100%);
            border-left-color: #dc3545;
        }

        .alert-warning {
            color: #664d03;
            background: linear-gradient(135deg, #f8f9fa 0%, #fff3cd 100%);
            border-left-color: #ffc107;
        }

        .alert-info {
            color: #0c5460;
            background: linear-gradient(135deg, #f8f9fa 0%, #d1ecf1 100%);
            border-left-color: #17a2b8;
        }

        .alert .btn-close {
            padding: 0.5rem 0.5rem;
            position: absolute;
            top: 0;
            right: 0;
        }
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 10000;
        }

        .loading-spinner {
            text-align: center;
            color: white;
        }

        .spinner {
            width: 50px;
            height: 50px;
            border: 5px solid rgba(255, 255, 255, 0.3);
            border-top: 5px solid #fff;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        @keyframes slideInRight {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
    </style>
</head>

<body>
    <!-- LOADER -->
    <?php
        include($_SERVER['DOCUMENT_ROOT'] . '/lily_collection/dist/include/loader.php');
    ?>
    <!-- END LOADER -->

    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-spinner">
            <div class="spinner"></div>
            <h5>Processing...</h5>
            <p>Please wait while we add the product</p>
        </div>
    </div>

    <!-- [ Main Content ] start -->
    <div class="pc-container">
        <div class="pc-content">
            <!-- [ breadcrumb ] start -->
            <div class="page-header">
                <div class="page-block">
                    <div class="page-header-title">
                        <h5 class="mb-0 font-medium">Add New Product</h5>
                    </div>
                </div>
            </div>
            <!-- [ breadcrumb ] end -->

            <!-- [ Main Content ] start -->
            <div class="main-container">
                <?php if ($step === 1): ?>
                <form method="POST" id="step1Form" class="product-form" novalidate>
                    <!-- Product Details Section -->
                    <div class="form-section">
                        <div class="section-content">
                            <!-- First Row: Name and Status -->
                            <div class="form-row">
                                <div class="product-form-group">
                                    <label for="name" class="form-label">
                                        <i class="fas fa-box"></i> Product Name<span class="required">*</span>
                                    </label>
                                    <input type="text" class="form-control" id="name" name="name"
                                        placeholder="Enter product name" required maxlength="255" value="<?php echo htmlspecialchars($p_name); ?>">
                                    <div class="error-feedback" id="name-error"></div>
                                </div>

                                <div class="product-form-group">
                                    <label for="status" class="form-label">
                                        <i class="fas fa-toggle-on"></i> Status<span class="required">*</span>
                                    </label>
                                    <select class="form-select" id="status" name="status" required>
                                        <option value="active" <?php echo $p_status === 'active' ? 'selected' : ''; ?>>Active</option>
                                        <option value="inactive" <?php echo $p_status === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                    </select>
                                    <div class="error-feedback" id="status-error"></div>
                                </div>
                            </div>

                            <!-- Second Row: Price and Product Code -->
                            <div class="form-row">
                                <div class="product-form-group">
                                    <label for="lkr_price" class="form-label">
                                        <i class="fas fa-rupee-sign"></i> Price (LKR)<span class="required">*</span>
                                    </label>
                                    <input type="number" class="form-control" id="lkr_price" name="lkr_price"
                                        placeholder="0.00" required min="0" step="0.01" value="<?php echo htmlspecialchars($p_lkr_price); ?>">
                                    <div class="error-feedback" id="lkr_price-error"></div>
                                    <div class="price-hint">Enter price in Sri Lankan Rupees (e.g., 1500.00)</div>
                                </div>

                                <div class="product-form-group">
                                    <label for="product_code" class="form-label">
                                        <i class="fas fa-barcode"></i> Product Code<span class="required">*</span>
                                    </label>
                                    <input type="text" class="form-control" id="product_code" name="product_code"
                                        placeholder="Enter product code" required maxlength="50" value="<?php echo htmlspecialchars($p_product_code); ?>">
                                    <div class="error-feedback" id="product_code-error"></div>
                                    <div class="code-hint">Unique identifier for the product</div>
                                </div>
                            </div>

                            <!-- New Row: Stock Quantity and Low Stock Threshold - only if enabled -->
                            <?php if (isset($_SESSION['allow_inventory']) && $_SESSION['allow_inventory'] == 1): ?>
                            <div class="form-row">
                                <div class="product-form-group">
                                    <label for="stock_quantity" class="form-label">
                                        <i class="fas fa-cubes"></i> Stock Quantity<span class="required">*</span>
                                    </label>
                                    <input type="number" class="form-control" id="stock_quantity" name="stock_quantity"
                                        placeholder="0" required min="0" step="1" value="<?php echo htmlspecialchars($p_stock_quantity); ?>">
                                    <div class="error-feedback" id="stock_quantity-error"></div>
                                    <div class="code-hint">Initial stock level</div>
                                </div>

                                <div class="product-form-group">
                                    <label for="low_stock_threshold" class="form-label">
                                        <i class="fas fa-exclamation-circle"></i> Low Stock Threshold<span class="required">*</span>
                                    </label>
                                    <input type="number" class="form-control" id="low_stock_threshold" name="low_stock_threshold"
                                        placeholder="10" required min="0" step="1" value="<?php echo htmlspecialchars($p_low_stock_threshold); ?>">
                                    <div class="error-feedback" id="low_stock_threshold-error"></div>
                                    <div class="code-hint">Minimum Stock Alert Level</div>
                                </div>
                            </div>
                            <?php else: ?>
                            <input type="hidden" name="stock_quantity" value="0">
                            <input type="hidden" name="low_stock_threshold" value="0">
                            <?php endif; ?>

                            <!-- Third Row: Description -->
                           <div class="form-row">
                            <div class="product-form-group full-width">
                                <label for="description" class="form-label">
                                    <i class="fas fa-align-left"></i> Description <span class="required">*</span>
                                </label>
                                <textarea class="form-control" id="description" name="description" rows="4"
                                    placeholder="Enter product description" required><?php echo htmlspecialchars($p_description); ?></textarea>
                                <div class="error-feedback" id="description-error"></div>
                                <div class="char-counter">
                                    <span id="desc-char-count">0</span> characters
                                </div>
                            </div>
                        </div>

                        </div>
                    </div>

                    <!-- Submit Buttons -->
                    <div class="submit-container">
                        <button type="submit" class="btn btn-primary ms-2" id="nextBtn">
                            Select Materials <i class="fas fa-arrow-right"></i>
                        </button>
                        <button type="button" class="btn btn-secondary ms-2" id="resetBtn">
                            <i class="fas fa-undo"></i> Reset Form
                        </button>
                    </div>
                </form>

                <?php else: ?>
                <!-- Step 2: Materials Assignment -->
                <div class="summary-box">
                    <h6><strong><i class="fas fa-clipboard-check me-2"></i>Product Details</strong></h6>
                    <div class="row">
                        <div class="col-md-3"><strong>Name and Code:</strong> <?php echo htmlspecialchars($p_name); ?> (<?php echo htmlspecialchars($p_product_code); ?>)</div>
                    </div>
                </div>

                <form method="POST" id="addProductForm" class="product-form" novalidate>
                    <!-- CSRF Token -->
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="action_type" value="save">
                    
                    <!-- Hidden fields from Step 1 -->
                    <input type="hidden" id="name" name="name" value="<?php echo htmlspecialchars($p_name); ?>">
                    <input type="hidden" id="status" name="status" value="<?php echo htmlspecialchars($p_status); ?>">
                    <input type="hidden" id="lkr_price" name="lkr_price" value="<?php echo htmlspecialchars($p_lkr_price); ?>">
                    <input type="hidden" id="product_code" name="product_code" value="<?php echo htmlspecialchars($p_product_code); ?>">
                    <input type="hidden" id="stock_quantity" name="stock_quantity" value="<?php echo htmlspecialchars($p_stock_quantity); ?>">
                    <input type="hidden" id="low_stock_threshold" name="low_stock_threshold" value="<?php echo htmlspecialchars($p_low_stock_threshold); ?>">
                    <input type="hidden" id="description" name="description" value="<?php echo htmlspecialchars($p_description); ?>">
                    
                    <!-- Materials (BOM) Section -->
                    <div class="form-section">
                        <div class="section-content">
                            <div class="section-header">
                                <h6><i class="fas fa-microchip me-2"></i>Materials (BOM)</h6>
                                <button type="button" class="add-material-btn" onclick="addBomRow()">
                                    <i class="fas fa-plus me-1"></i> Add Material
                                </button>
                            </div>
                            
                            <div class="bom-table-container">
                                <table class="bom-table" id="bomTable">
                                    <thead>
                                        <tr>
                                            <th style="width: 50px;">#</th>
                                            <th>Material</th>
                                            <th style="width: 200px;">Qty</th>
                                            <th style="width: 60px;">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="bomBody">
                                        <!-- BOM Rows added here -->
                                    </tbody>
                                </table>
                            </div>
                            <div class="code-hint mt-3">Assign materials that are consumed when 1 unit of this product is manufactured.</div>
                        </div>
                    </div>

                    <!-- Submit Buttons -->
                    <div class="submit-container">
                        <button type="submit" class="btn btn-primary" id="submitBtn">
                            <i class="fas fa-plus"></i> Add Product
                        </button>
                        <button type="button" class="btn btn-secondary ms-2" onclick="window.location.href='add_product.php'">
                            <i class="fas fa-arrow-left"></i> Back
                        </button>
                    </div>
                </form>
                <?php endif; ?>
            </div>
            <!-- [ Main Content ] end -->
        </div>
    </div>

    <!-- FOOTER -->
    <?php
    include($_SERVER['DOCUMENT_ROOT'] . '/lily_collection/dist/include/footer.php');
    ?>
    <!-- END FOOTER -->

    <!-- SCRIPTS -->
    <?php
    include($_SERVER['DOCUMENT_ROOT'] . '/lily_collection/dist/include/scripts.php');
    ?>
    <!-- END SCRIPTS -->

    <!-- jQuery (make sure this is loaded before your custom script) -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>

    <script>
        $(document).ready(function() {
            // Initialize form
            initializeForm();
            
            // Step 1 Form submission
            $('#step1Form').on('submit', function(e) {
                // We let it submit normally to transition to Step 2
                // but we can still validate client-side
                if (!validateForm()) {
                    e.preventDefault();
                    scrollToFirstError();
                }
            });

            // AJAX Form submission
            $('#addProductForm').on('submit', function(e) {
                e.preventDefault();
                
                // Clear previous validations
                clearAllValidations();
                
                // Validate form
                if (validateForm()) {
                    submitFormAjax();
                } else {
                    // Scroll to first error
                    scrollToFirstError();
                }
            });
            
            // Reset button
            $('#resetBtn').on('click', function() {
                resetForm();
            });
            
            // Real-time validation
            setupRealTimeValidation();
            
            // Other event listeners
            setupEventListeners();
        });

        // AJAX Form Submission Function
        function submitFormAjax() {
            // Show loading overlay
            showLoading();
            
            // Disable submit button
            const $submitBtn = $('#submitBtn');
            const originalText = $submitBtn.html();
            $submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Adding Product...');
            
            // Prepare form data
            const formData = new FormData($('#addProductForm')[0]);
            
            // AJAX request
            $.ajax({
                url: 'save_product.php', // Your existing save_product.php file
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                timeout: 30000, // 30 seconds timeout
                success: function(response) {
                    hideLoading();
                    $submitBtn.prop('disabled', false).html(originalText);
                    
                    if (response.success) {
                        showSuccessNotification(response.message || 'Product added successfully!');
                        
                        // Redirect to product list after success
                        setTimeout(() => {
                            window.location.href = 'product_list.php';
                        }, 2000);
                    } else {
                        if (response.errors) {
                            // Show field-specific errors
                            showFieldErrors(response.errors);
                        }
                        
                        showErrorNotification(response.message || 'Failed to add product. Please try again.');
                    }
                },
                error: function(xhr, status, error) {
                    hideLoading();
                    $submitBtn.prop('disabled', false).html(originalText);
                    
                    let errorMessage = 'An error occurred while adding the product.';
                    
                    if (status === 'timeout') {
                        errorMessage = 'Request timeout. Please try again.';
                    } else if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    } else if (xhr.status === 500) {
                        errorMessage = 'Server error. Please contact administrator.';
                    } else if (xhr.status === 0) {
                        errorMessage = 'No internet connection. Please check your connection.';
                    }
                    
                    showErrorNotification(errorMessage);
                    console.error('AJAX Error:', {
                        status: xhr.status,
                        statusText: xhr.statusText,
                        responseText: xhr.responseText,
                        error: error
                    });
                }
            });
        }
        
        // Show field-specific errors from server
        function showFieldErrors(errors) {
            $.each(errors, function(field, message) {
                showError(field, message);
            });
        }
        
        // Loading functions
        function showLoading() {
            $('#loadingOverlay').css('display', 'flex');
            $('body').css('overflow', 'hidden');
        }
        
        function hideLoading() {
            $('#loadingOverlay').hide();
            $('body').css('overflow', 'auto');
        }
        
        // Notification functions
        function showSuccessNotification(message) {
            showNotification(message, 'success');
        }
        
        function showErrorNotification(message) {
            showNotification(message, 'danger');
        }
        
        function showWarningNotification(message) {
            showNotification(message, 'warning');
        }
        
        function showNotification(message, type) {
            const notificationId = 'notification_' + Date.now();
            const alertClasses = {
                'success': 'alert-success',
                'danger': 'alert-danger',
                'warning': 'alert-warning'
            };
            
            const iconClass = type === 'success' ? 'fas fa-check-circle' : 
                            type === 'danger' ? 'fas fa-exclamation-circle' : 
                            'fas fa-exclamation-triangle';
            
            const notification = `
                <div class="alert ${alertClasses[type]} alert-dismissible fade show ajax-notification" id="${notificationId}" role="alert">
                    <div class="d-flex align-items-center">
                        <i class="${iconClass} me-2"></i>
                        <div>${message}</div>
                    </div>
                    <button type="button" class="btn-close" onclick="hideNotification('${notificationId}')" aria-label="Close"></button>
                </div>
            `;
            
            $('body').append(notification);
            
            // Auto-hide after 5 seconds
            setTimeout(() => {
                hideNotification(notificationId);
            }, 5000);
        }
        
        function hideNotification(notificationId) {
            const $notification = $('#' + notificationId);
            if ($notification.length) {
                $notification.addClass('hide');
                setTimeout(() => {
                    $notification.remove();
                }, 300);
            }
        }
        
        // Form reset function
        function resetForm() {
            $('#addProductForm')[0].reset();
            clearAllValidations();
            updateCharCount();
            $('#name').focus();
        }
        
        // Clear all validations
        function clearAllValidations() {
            $('.form-control, .form-select').removeClass('is-valid is-invalid field-error field-success');
            $('.error-feedback').hide().text('');
        }
        
        // Scroll to first error
        function scrollToFirstError() {
            const $firstError = $('.is-invalid, .field-error').first();
            if ($firstError.length) {
                $('html, body').animate({
                    scrollTop: $firstError.offset().top - 100
                }, 500);
                $firstError.focus();
            }
        }
        
        // Initialize form
        function initializeForm() {
            $('#name').focus();
            updateCharCount();
        }
        
        // Setup real-time validation
        function setupRealTimeValidation() {
            $('#name').on('blur', function() {
                const validation = validateName($(this).val());
                if (!validation.valid) {
                    showError('name', validation.message);
                } else {
                    showSuccess('name');
                }
            });
            
            $('#lkr_price').on('blur', function() {
                const validation = validatePrice($(this).val());
                if (!validation.valid) {
                    showError('lkr_price', validation.message);
                } else {
                    showSuccess('lkr_price');
                }
            });
            
            $('#product_code').on('blur', function() {
                const validation = validateProductCode($(this).val());
                if (!validation.valid) {
                    showError('product_code', validation.message);
                } else {
                    showSuccess('product_code');
                }
            });
            
            $('#description').on('blur', function() {
                const validation = validateDescription($(this).val());
                if (!validation.valid) {
                    showError('description', validation.message);
                } else if ($(this).val().trim() !== '') {
                    showSuccess('description');
                } else {
                    clearValidation('description');
                }
            });
        }
        
        // Setup other event listeners
        function setupEventListeners() {
            // Character counter for description
            $('#description').on('input', function() {
                updateCharCount();
                if ($(this).hasClass('is-invalid')) {
                    clearValidation('description');
                }
            });

            // Prevent form submission on Enter key in input fields
            $('input:not([type="submit"])').on('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    const $inputs = $('input, select, textarea');
                    const currentIndex = $inputs.index(this);
                    if (currentIndex < $inputs.length - 1) {
                        $inputs.eq(currentIndex + 1).focus();
                    }
                }
            });
        }

        // Character counter for description
        function updateCharCount() {
            const textarea = $('#description');
            const counter = $('#desc-char-count');
            if (textarea.length && counter.length) {
                counter.text(textarea.val().length);
            }
        }

        // Validation functions
        function validateName(name) {
            if (name.trim() === '') {
                return { valid: false, message: 'Product name is required' };
            }
            if (name.trim().length < 2) {
                return { valid: false, message: 'Product name must be at least 2 characters long' };
            }
            if (name.length > 255) {
                return { valid: false, message: 'Product name is too long (maximum 255 characters)' };
            }
            return { valid: true, message: '' };
        }

        function validatePrice(price) {
            if (price.trim() === '' || isNaN(price)) {
                return { valid: false, message: 'Price is required and must be a valid number' };
            }
            
            const numPrice = parseFloat(price);
            
            if (numPrice < 0) {
                return { valid: false, message: 'Price cannot be negative' };
            }
            
            if (numPrice > 99999999.99) {
                return { valid: false, message: 'Price is too high (maximum 99,999,999.99)' };
            }
            
            // Check for too many decimal places
            if (price.includes('.') && price.split('.')[1].length > 2) {
                return { valid: false, message: 'Price can have maximum 2 decimal places' };
            }
            
            return { valid: true, message: '' };
        }

        function validateProductCode(code) {
            if (code.trim() === '') {
                return { valid: false, message: 'Product code is required' };
            }
            
            if (code.trim().length < 2) {
                return { valid: false, message: 'Product code must be at least 2 characters long' };
            }
            
            if (code.length > 50) {
                return { valid: false, message: 'Product code is too long (maximum 50 characters)' };
            }
            
            // Allow alphanumeric, hyphens, underscores
            if (!/^[a-zA-Z0-9\-_]+$/.test(code.trim())) {
                return { valid: false, message: 'Product code can only contain letters, numbers, hyphens, and underscores' };
            }
            
            return { valid: true, message: '' };
        }

       function validateDescription(description) {
            if (description.trim() === '') {
                return { valid: false, message: 'Description is required' };
            }

            if (description.length < 5) {
                return { valid: false, message: 'Description must be at least 5 characters long' };
            }

            if (description.length > 65535) {
                return { valid: false, message: 'Description is too long (maximum 65,535 characters)' };
            }

            return { valid: true, message: '' };
        }

        // Show/hide error functions
        function showError(fieldId, message) {
            const $field = $('#' + fieldId);
            const $errorDiv = $('#' + fieldId + '-error');
            
            if ($field.length && $errorDiv.length) {
                $field.addClass('is-invalid field-error').removeClass('is-valid field-success');
                $errorDiv.text(message).show();
            }
        }

        function showSuccess(fieldId) {
            const $field = $('#' + fieldId);
            const $errorDiv = $('#' + fieldId + '-error');
            
            if ($field.length && $errorDiv.length) {
                $field.addClass('is-valid field-success').removeClass('is-invalid field-error');
                $errorDiv.hide();
            }
        }

        function clearValidation(fieldId) {
            const $field = $('#' + fieldId);
            const $errorDiv = $('#' + fieldId + '-error');
            
            if ($field.length && $errorDiv.length) {
                $field.removeClass('is-valid is-invalid field-error field-success');
                $errorDiv.hide();
            }
        }

        // Form validation
        function validateForm() {
            let isValid = true;
            
            // Get all field values
            const name = $('#name').val();
            const price = $('#lkr_price').val();
            const productCode = $('#product_code').val();
            const description = $('#description').val();
            
            // Validate required fields
            const validations = [
                { field: 'name', validator: validateName, value: name },
                { field: 'lkr_price', validator: validatePrice, value: price },
                { field: 'product_code', validator: validateProductCode, value: productCode },
                { field: 'description', validator: validateDescription, value: description }
            ];
            
            validations.forEach(function(validation) {
                const result = validation.validator(validation.value);
                if (!result.valid) {
                    showError(validation.field, result.message);
                    isValid = false;
                } else if (validation.field === 'description' && validation.value.trim() !== '') {
                    showSuccess(validation.field);
                } else if (validation.field !== 'description') {
                    showSuccess(validation.field);
                }
            });

            // Validate BOM rows if any exist
            $('.bom-row').each(function() {
                const materialId = $(this).find('.bom-material-id').val();
                const qtyInput = $(this).find('.bom-qty-input');
                const qty = qtyInput.val();
                const rowNum = $(this).find('td:first').text();

                if (!materialId) {
                    showErrorNotification(`Please select a valid material for row #${rowNum}`);
                    $(this).find('.bom-material-search').addClass('is-invalid');
                    isValid = false;
                } else {
                    $(this).find('.bom-material-search').removeClass('is-invalid');
                }

                if (!qty || parseFloat(qty) <= 0) {
                    showErrorNotification(`Please enter a quantity greater than 0 for row #${rowNum}`);
                    qtyInput.addClass('is-invalid');
                    isValid = false;
                } else {
                    qtyInput.removeClass('is-invalid');
                }
            });

            // check duplicate materials
            const materialIds = Array.from(document.querySelectorAll('.bom-material-id'))
                .map(input => input.value)
                .filter(val => val !== '');
            
            if (new Set(materialIds).size !== materialIds.length) {
                showErrorNotification('Duplicate materials found in the BOM. Each material can only be added once.');
                isValid = false;
            }
            
            return isValid;
        }
        /* --- BOM & Material --- */
        const materialsData = <?php echo json_encode($materialsData); ?>;
        let bomRowCounter = 0;

        function addBomRow() {
            // Validate last row is complete before adding a new one
            const existingRows = document.querySelectorAll('#bomBody .bom-row');
            if (existingRows.length > 0) {
                const lastRow = existingRows[existingRows.length - 1];
                const lastMaterialId = lastRow.querySelector('.bom-material-id').value;
                const lastQty = lastRow.querySelector('.bom-qty-input').value;
                if (!lastMaterialId || !lastQty) {
                    showErrorNotification('Please complete the current row (select a material and enter quantity) before adding a new one.');
                    return;
                }
            }

            bomRowCounter++;
            const tbody = document.getElementById('bomBody');
            const row = document.createElement('tr');
            row.className = 'bom-row';

            row.innerHTML = `
                <td class="text-center font-weight-bold">${bomRowCounter}</td>
                <td>
                    <div class="autocomplete-wrapper">
                        <input type="text" class="form-control bom-material-search" placeholder="Search material name or code..." autocomplete="off" required>
                        <input type="hidden" name="bom[${bomRowCounter - 1}][material_id]" class="bom-material-id" required>
                        <div class="autocomplete-suggestions"></div>
                    </div>
                </td>
                <td>
                    <input type="number" name="bom[${bomRowCounter - 1}][quantity_required]" class="form-control bom-qty-input" min="0" step="1" placeholder="Required (Per 1 Unit)" required>
                </td>
                <td class="text-center">
                    <button type="button" class="remove-bom-btn" onclick="removeBomRow(this)" title="Remove Material">
                        <i class="fas fa-trash-alt"></i>
                    </button>
                </td>
            `;
            tbody.appendChild(row);
            
            initRowAutocomplete(row);
            renumberBomRows();
        }

        function removeBomRow(btn) {
            btn.closest('tr').remove();
            renumberBomRows();
        }

        function renumberBomRows() {
            const rows = document.querySelectorAll('#bomBody .bom-row');
            bomRowCounter = rows.length;
            rows.forEach((row, index) => {
                row.querySelector('td:first-child').textContent = index + 1;
                const idInput = row.querySelector('.bom-material-id');
                const qtyInput = row.querySelector('.bom-qty-input');
                idInput.name = `bom[${index}][material_id]`;
                qtyInput.name = `bom[${index}][quantity_required]`;
            });
            
            if (rows.length === 0) {
            }
        }
        
        function initRowAutocomplete(row) {
            const searchInput = row.querySelector('.bom-material-search');
            const idInput = row.querySelector('.bom-material-id');
            const suggestionsDiv = row.querySelector('.autocomplete-suggestions');
            let selectedIndex = -1;

            searchInput.addEventListener('input', function() {
                const term = this.value.trim().toLowerCase();
                
                // Get currently selected material IDs from other rows
                const selectedIds = Array.from(document.querySelectorAll('.bom-material-id'))
                    .map(input => input.value)
                    .filter(val => val !== '' && val !== idInput.value);

                // Show most relevant 10 materials (either matching or just top 10)
                const filtered = term.length === 0 
                    ? materialsData.filter(m => !selectedIds.includes(m.id.toString())).slice(0, 10) 
                    : materialsData.filter(m => 
                        (m.name.toLowerCase().includes(term) || 
                        m.material_code.toLowerCase().includes(term)) &&
                        !selectedIds.includes(m.id.toString())
                    ).slice(0, 10);

                showSuggestions(filtered, suggestionsDiv, searchInput, idInput, row);
            });

            // Handle focus to show materials instantly (even before typing)
            searchInput.addEventListener('focus', function() {
                this.dispatchEvent(new Event('input'));
            });

            searchInput.addEventListener('keydown', function(e) {
                const items = suggestionsDiv.querySelectorAll('.autocomplete-suggestion');
                if (items.length === 0) return;

                if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    selectedIndex = (selectedIndex + 1) % items.length;
                    updateSelection(items);
                } else if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    selectedIndex = (selectedIndex - 1 + items.length) % items.length;
                    updateSelection(items);
                } else if (e.key === 'Enter') {
                    e.preventDefault();
                    if (selectedIndex >= 0) {
                        items[selectedIndex].click();
                    } else if (items.length === 1) {
                        items[0].click();
                    }
                } else if (e.key === 'Escape') {
                    suggestionsDiv.style.display = 'none';
                }
            });

            // Re-validate on blur if cleared
            searchInput.addEventListener('blur', function() {
                setTimeout(() => {
                    if (this.value.trim() === '') idInput.value = '';
                    suggestionsDiv.style.display = 'none';
                    row.classList.remove('active-autocomplete');
                }, 200);
            });

            function updateSelection(items) {
                items.forEach((item, idx) => {
                    if (idx === selectedIndex) item.classList.add('active');
                    else item.classList.remove('active');
                });
            }
        }

        function showSuggestions(filtered, div, input, idInput, row) {
            if (filtered.length === 0) {
                const term = input.value.trim();
                div.innerHTML = '<div class="no-results">No materials found</div>';
                div.style.display = 'block';
                row.classList.add('active-autocomplete');
                return;
            }

            div.innerHTML = filtered.map(m => `
                <div class="autocomplete-suggestion" data-id="${m.id}" data-text="${m.name} (${m.material_code})">
                    <strong>${m.name}</strong> <small>(${m.material_code})</small>
                </div>
            `).join('');
            
            div.style.display = 'block';
            row.classList.add('active-autocomplete');
            
            div.querySelectorAll('.autocomplete-suggestion').forEach(item => {
                item.addEventListener('click', function() {
                    const materialId = this.dataset.id;

                    // Double check for duplicates
                    const selectedIds = Array.from(document.querySelectorAll('.bom-material-id'))
                        .map(input => input.value)
                        .filter(val => val !== '' && val !== idInput.value);
                    
                    if (selectedIds.includes(materialId)) {
                        showErrorNotification('This material is already added to the BOM.');
                        input.value = '';
                        idInput.value = '';
                        div.style.display = 'none';
                        row.classList.remove('active-autocomplete');
                        return;
                    }

                    input.value = this.dataset.text;
                    idInput.value = materialId;
                    div.style.display = 'none';
                    row.classList.remove('active-autocomplete');
                    $(input).removeClass('is-invalid');
                });
            });
        }
    </script>
</body>
</html>