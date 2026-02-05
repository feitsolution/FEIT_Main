<?php
// Start session at the very beginning
session_start();

// Check if user is logged in, if not redirect to login page
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    // Clear any existing output buffers
    if (ob_get_level()) {
        ob_end_clean();
    }
    header("Location: /order_management/dist/pages/login.php");
    exit();
}

// Include the database connection file
include($_SERVER['DOCUMENT_ROOT'] . '/order_management/dist/connection/db_connection.php');

// Fetch all categories for parent selection
$categories = [];
try {
    $res = $conn->query("SELECT id, name FROM categories ORDER BY name ASC");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $categories[] = $row;
        }
    }
} catch (Exception $e) {
    error_log("Error fetching categories: " . $e->getMessage());
}

// Function to generate CSRF token
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

include($_SERVER['DOCUMENT_ROOT'] . '/order_management/dist/include/navbar.php');
include($_SERVER['DOCUMENT_ROOT'] . '/order_management/dist/include/sidebar.php');
?>

<!doctype html>
<html lang="en" data-pc-preset="preset-1" data-pc-sidebar-caption="true" data-pc-direction="ltr" dir="ltr" data-pc-theme="light">

<head>
    <title>Order Management Admin Portal - Add Category</title>

    <?php include($_SERVER['DOCUMENT_ROOT'] . '/order_management/dist/include/head.php'); ?>
    
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

        @keyframes slideInRight {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
    </style>
</head>

<body>
    <!-- LOADER -->
    <?php include($_SERVER['DOCUMENT_ROOT'] . '/order_management/dist/include/loader.php'); ?>


    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-spinner">
            <div class="spinner"></div>
            <h5>Processing...</h5>
            <p>Please wait while we add the product</p>
        </div>
    </div>
    
    <div class="pc-container">
        <div class="pc-content">
            <div class="page-header">
                <div class="page-block">
                    <div class="page-header-title">
                        <h5 class="mb-0 font-medium">Add New Category</h5>
                    </div>
                </div>
            </div>

            <div class="main-container">
                <form method="POST" id="addCategoryForm" class="product-form" novalidate>
                    <!-- CSRF Token -->
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    
                    <div class="form-section">
                        <div class="section-content">
                            <div class="form-row">
                                <div class="product-form-group">
                                    <label for="name" class="form-label">
                                        <i class="fas fa-tag"></i> Category Name<span class="required">*</span>
                                    </label>
                                    <input type="text" class="form-control" id="name" name="name"
                                        placeholder="Enter category name" required maxlength="255">
                                    <div class="error-feedback" id="name-error"></div>
                                </div>

                                <div class="product-form-group">
                                    <label for="parent_id" class="form-label">
                                        <i class="fas fa-level-up-alt"></i> Parent Category
                                    </label>
                                    <select class="form-select" id="parent_id" name="parent_id">
                                        <option value="0">None (Top Level)</option>
                                        <?php foreach ($categories as $cat): ?>
                                            <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="error-feedback" id="parent_id-error"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="submit-container">
                        <button type="submit" class="btn btn-primary" id="submitBtn">
                            <i class="fas fa-plus"></i> Add Category
                        </button>
                        <button type="button" class="btn btn-secondary ms-2" onclick="window.location.href='category_list.php'">
                            <i class="fas fa-arrow-left"></i> Back to List
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php include($_SERVER['DOCUMENT_ROOT'] . '/order_management/dist/include/footer.php'); ?>
    <?php include($_SERVER['DOCUMENT_ROOT'] . '/order_management/dist/include/scripts.php'); ?>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>

    <script>
        $(document).ready(function() {
            $('#addCategoryForm').on('submit', function(e) {
                e.preventDefault();
                
                const name = $('#name').val().trim();
                if (name === '') {
                    showError('name', 'Category name is required');
                    return;
                }
                
                const $submitBtn = $('#submitBtn');
                $submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Processing...');
                
                $.ajax({
                    url: 'save_category.php',
                    type: 'POST',
                    data: $(this).serialize(),
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            showNotification(response.message, 'success');
                            $('#addCategoryForm')[0].reset();
                        } else {
                            showNotification(response.message, 'danger');
                        }
                    },
                    error: function() {
                        showNotification('An error occurred. Please try again.', 'danger');
                    },
                    complete: function() {
                        $submitBtn.prop('disabled', false).html('<i class="fas fa-plus"></i> Add Category');
                    }
                });
            });
        });

        function showError(fieldId, message) {
            $('#' + fieldId).addClass('is-invalid');
            $('#' + fieldId + '-error').text(message).show();
        }

        function showNotification(message, type) {
            const id = 'notif_' + Date.now();
            const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
            const icon = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';
            
            const html = `
                <div class="alert ${alertClass} alert-dismissible fade show ajax-notification" id="${id}" role="alert">
                    <div class="d-flex align-items-center">
                        <i class="fas ${icon} me-2"></i>
                        <div>${message}</div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `;
            $('body').append(html);
            setTimeout(() => $('#' + id).fadeOut(() => $('#' + id).remove()), 5000);
        }
    </script>
</body>
</html>
