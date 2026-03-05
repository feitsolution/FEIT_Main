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

// Function to generate CSRF token
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Get material ID from URL
$material_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($material_id <= 0) {
    header("Location: material_list.php");
    exit();
}

// Fetch existing material data
$material = null;
try {
    $query = "SELECT * FROM Material WHERE id = ? LIMIT 1";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $material_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        header("Location: material_list.php");
        exit();
    }
    
    $material = $result->fetch_assoc();
    $stmt->close();
} catch (Exception $e) {
    header("Location: material_list.php");
    exit();
}

include($_SERVER['DOCUMENT_ROOT'] . '/lily_collection/dist/include/navbar.php');
include($_SERVER['DOCUMENT_ROOT'] . '/lily_collection/dist/include/sidebar.php');
?>

<!doctype html>
<html lang="en" data-pc-preset="preset-1" data-pc-sidebar-caption="true" data-pc-direction="ltr" dir="ltr" data-pc-theme="light">

<head>
    <!-- TITLE -->
    <title>Order Management Admin Portal - Edit Material</title>

    <?php
    include($_SERVER['DOCUMENT_ROOT'] . '/lily_collection/dist/include/head.php');
    ?>
    
    <!-- [Template CSS Files] -->
    <link rel="stylesheet" href="../assets/css/style.css" id="main-style-link" />
    <link rel="stylesheet" href="../assets/css/products.css" id="main-style-link" />
 
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
            <p>Please wait while we update the material</p>
        </div>
    </div>

    <!-- [ Main Content ] start -->
    <div class="pc-container">
        <div class="pc-content">
            <!-- [ breadcrumb ] start -->
            <div class="page-header">
                <div class="page-block">
                    <div class="page-header-title">
                        <h5 class="mb-0 font-medium">Edit Material</h5>
                    </div>
                </div>
            </div>
            <!-- [ breadcrumb ] end -->

            <!-- [ Main Content ] start -->
            <div class="main-container">
                <form method="POST" id="editMaterialForm" class="product-form" novalidate>
                    <!-- CSRF Token -->
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <!-- Material ID -->
                    <input type="hidden" name="material_id" value="<?php echo $material['id']; ?>">
                    
                    <!-- Material Details Section -->
                    <div class="form-section">
                        <div class="section-content">
                            <!-- First Row: Code and Name -->
                            <div class="form-row">
                                <div class="product-form-group">
                                    <label for="name" class="form-label">
                                        <i class="fas fa-layer-group"></i> Material Name<span class="required">*</span>
                                    </label>
                                    <input type="text" class="form-control" id="name" name="name"
                                        placeholder="Enter material name" required maxlength="255"
                                        value="<?php echo htmlspecialchars($material['name']); ?>">
                                    <div class="error-feedback" id="name-error"></div>
                                </div>

                                <div class="product-form-group">
                                    <label for="material_code" class="form-label">
                                        <i class="fas fa-barcode"></i> Material Code<span class="required">*</span>
                                    </label>
                                    <input type="text" class="form-control" id="material_code" name="material_code"
                                        placeholder="Enter material code" required maxlength="50"
                                        value="<?php echo htmlspecialchars($material['material_code']); ?>">
                                    <div class="error-feedback" id="material_code-error"></div>
                                    <div class="code-hint">Unique identifier for the material</div>
                                </div> 
                            </div>

                            <!-- Second Row: Stock Quantity and Status -->
                            <div class="form-row">
                                <div class="product-form-group">
                                    <label for="stock_quantity" class="form-label">
                                        <i class="fas fa-cubes"></i> Stock Quantity<span class="required">*</span>
                                    </label>
                                    <input type="number" class="form-control" id="stock_quantity" name="stock_quantity"
                                        placeholder="0" required min="0" step="1" value="<?php echo (int)$material['stock_quantity']; ?>">
                                    <div class="error-feedback" id="stock_quantity-error"></div>
                                    <div class="code-hint">Current stock level for this material (PCS)</div>
                                </div>

                                <div class="product-form-group">
<label for="low_stock_threshold" class="form-label">
                                        <i class="fas fa-exclamation-triangle"></i> Low Stock Threshold<span class="required">*</span>
                                    </label>
                                    <input type="number" class="form-control" id="low_stock_threshold" name="low_stock_threshold"
                                        placeholder="10" required min="0" step="1" value="<?php echo (int)($material['low_stock_threshold']); ?>">
                                    <div class="error-feedback" id="low_stock_threshold-error"></div>
                                    <div class="code-hint">Minimum Stock Alert Level</div>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="product-form-group">
                                    <label for="status" class="form-label">
                                        <i class="fas fa-toggle-on"></i> Status<span class="required">*</span>
                                    </label>
                                    <select class="form-select" id="status" name="status" required>
                                        <option value="active" <?php echo $material['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                                        <option value="inactive" <?php echo $material['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                    </select>
                                    <div class="error-feedback" id="status-error"></div>
                                </div>
                            </div>                      

                        </div>
                    </div>

                    <!-- Submit Buttons -->
                    <div class="submit-container">
                        <button type="submit" class="btn btn-primary" id="submitBtn">
                            <i class="fas fa-save"></i> Update Material
                        </button>
                        <button type="button" class="btn btn-secondary ms-2" id="cancelBtn" onclick="window.location.href='material_list.php'">
                            <i class="fas fa-times"></i> Back to Materials
                        </button>
                    </div>
                </form>
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
        // Store original values for reset functionality
        const originalValues = {
            material_code: '<?php echo addslashes($material['material_code']); ?>',
            name: '<?php echo addslashes($material['name']); ?>',
            stock_quantity: '<?php echo (int)$material['stock_quantity']; ?>',
            low_stock_threshold: '<?php echo (int)($material['low_stock_threshold']); ?>',
            status: '<?php echo $material['status']; ?>'
        };

        $(document).ready(function() {
            // Initialize form
            initializeForm();
            
            // AJAX Form submission
            $('#editMaterialForm').on('submit', function(e) {
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
            $submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Updating Material...');
            
            // Prepare form data
            const formData = new FormData($('#editMaterialForm')[0]);
            
            // AJAX request
            $.ajax({
                url: 'update_material.php',
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
                        showSuccessNotification(response.message || 'Material updated successfully!');
                        
                        // Update original values with new values
                        updateOriginalValues();
                    } else {
                        if (response.errors) {
                            // Show field-specific errors
                            showFieldErrors(response.errors);
                        }
                        
                        showErrorNotification(response.message || 'Failed to update material. Please try again.');
                    }
                },
                error: function(xhr, status, error) {
                    hideLoading();
                    $submitBtn.prop('disabled', false).html(originalText);
                    
                    let errorMessage = 'An error occurred while updating the material.';
                    
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
        
        // Update original values after successful update
        function updateOriginalValues() {
            originalValues.material_code = $('#material_code').val();
            originalValues.name = $('#name').val();
            originalValues.stock_quantity = $('#stock_quantity').val();
            originalValues.low_stock_threshold = $('#low_stock_threshold').val();
            originalValues.status = $('#status').val();
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
        }
        
        // Setup real-time validation
        function setupRealTimeValidation() {
            $('#material_code').on('blur', function() {
                const validation = validateMaterialCode($(this).val());
                if (!validation.valid) {
                    showError('material_code', validation.message);
                } else {
                    showSuccess('material_code');
                }
            });

$('#name').on('blur', function() {
                const validation = validateName($(this).val());
                if (!validation.valid) {
                    showError('name', validation.message);
                } else {
                    showSuccess('name');
                }
            });

            $('#low_stock_threshold').on('blur', function() {
                const validation = validateThreshold($(this).val());
                if (!validation.valid) {
                    showError('low_stock_threshold', validation.message);
                } else {
                    showSuccess('low_stock_threshold');
                }
            });
        }
        
        // Setup other event listeners
        function setupEventListeners() {
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

        // Validation functions
        function validateMaterialCode(code) {
            if (code.trim() === '') {
                return { valid: false, message: 'Material code is required' };
            }
            if (code.trim().length < 2) {
                return { valid: false, message: 'Material code must be at least 2 characters long' };
            }
            if (code.length > 50) {
                return { valid: false, message: 'Material code is too long (maximum 50 characters)' };
            }
            if (!/^[a-zA-Z0-9\-_]+$/.test(code.trim())) {
                return { valid: false, message: 'Material code can only contain letters, numbers, hyphens, and underscores' };
            }
            return { valid: true, message: '' };
        }

        function validateName(name) {
            if (name.trim() === '') {
                return { valid: false, message: 'Material name is required' };
            }
            if (name.trim().length < 2) {
                return { valid: false, message: 'Material name must be at least 2 characters long' };
            }
            if (name.length > 255) {
                return { valid: false, message: 'Material name is too long (maximum 255 characters)' };
            }
            return { valid: true, message: '' };
        }

        function validateStock(stock) {
            if (stock === undefined || stock === null || stock.toString().trim() === '' || isNaN(stock)) {
                return { valid: false, message: 'Stock quantity is required and must be a number' };
            }
            if (parseInt(stock) < 0) {
                return { valid: false, message: 'Stock quantity cannot be negative' };
            }
            return { valid: true, message: '' };
        }

        function validateThreshold(threshold) {
            if (threshold === undefined || threshold === null || threshold.toString().trim() === '' || isNaN(threshold)) {
                return { valid: false, message: 'Threshold must be a number' };
            }
            if (parseInt(threshold) < 0) {
                return { valid: false, message: 'Threshold cannot be negative' };
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
            
            const materialCode = $('#material_code').val();
            const name = $('#name').val();
            const stock = $('#stock_quantity').val();
            const threshold = $('#low_stock_threshold').val();
            
            const validations = [
                { field: 'material_code', validator: validateMaterialCode, value: materialCode },
                { field: 'name', validator: validateName, value: name },
                { field: 'stock_quantity', validator: validateStock, value: stock },
                { field: 'low_stock_threshold', validator: validateThreshold, value: threshold }
            ];
            
            validations.forEach(function(validation) {
                const result = validation.validator(validation.value);
                if (!result.valid) {
                    showError(validation.field, result.message);
                    isValid = false;
                } else {
                    showSuccess(validation.field);
                }
            });
            
            return isValid;
        }
    </script>
</body>
</html>
