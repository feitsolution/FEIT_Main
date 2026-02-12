<?php
// File: templates/lead_upload.php
// Start output buffering to prevent header issues
ob_start();

// Start session at the very beginning
session_start();

// Check if user is logged in, if not redirect to login page
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    ob_end_clean();
    header("Location: /quick_start/dist/pages/login.php");
    exit();
}

/**
 * Handle Failed Rows CSV Download
 */
if (isset($_GET['download_errors']) && isset($_SESSION['failed_rows_data'])) {
    $failedRows = $_SESSION['failed_rows_data'];
    
    // Set headers for download
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="failed_leads_import_' . date('Y-m-d_H-i-s') . '.csv"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    $output = fopen('php://output', 'w');
    
    // Add UTF-8 BOM for Excel compatibility
    fwrite($output, "\xEF\xBB\xBF");
    
    // Write header (original columns + error reason)
    if (!empty($failedRows)) {
        fputcsv($output, array_keys($failedRows[0]));
        
        foreach ($failedRows as $row) {
            fputcsv($output, $row);
        }
    }
    
    fclose($output);
    exit();
}

// Include the database connection file early
include($_SERVER['DOCUMENT_ROOT'] . '/quick_start/dist/connection/db_connection.php');

// Initialize transaction flag
$transactionStarted = false;

// Function to log user actions
function logUserAction($conn, $user_id, $action_type, $inquiry_id, $details) {
    $logSql = "INSERT INTO user_logs (user_id, action_type, inquiry_id, details) VALUES (?, ?, ?, ?)";
    $logStmt = $conn->prepare($logSql);
    if ($logStmt) {
        $logStmt->bind_param("isis", $user_id, $action_type, $inquiry_id, $details);
        $logStmt->execute();
        $logStmt->close();
    }
}

include($_SERVER['DOCUMENT_ROOT'] . '/quick_start/dist/include/navbar.php');
include($_SERVER['DOCUMENT_ROOT'] . '/quick_start/dist/include/sidebar.php');
?>

<!doctype html>
<html lang="en" data-pc-preset="preset-1" data-pc-sidebar-caption="true" data-pc-direction="ltr" dir="ltr" data-pc-theme="light">

<head>
    <title>Order Management Admin Portal - Lead Upload</title>
    
    <?php include($_SERVER['DOCUMENT_ROOT'] . '/quick_start/dist/include/head.php'); ?>
    
    <link rel="stylesheet" href="../assets/css/style.css" id="main-style-link" />
    <link rel="stylesheet" href="../assets/css/leads.css" id="main-style-link" />
</head>

<style>
        .alert-info {
            background-color: #d1ecf1;
            border: 1px solid #bee5eb;
            color: #0c5460;
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-radius: 5px;
        }

        .alert-info h4 {
            margin-bottom: 0.5rem;
            color: #0c5460;
        }

        .alert-info ul {
            margin-bottom: 0;
            padding-left: 1.5rem;
        }

        .alert-info li {
            margin-bottom: 0.3rem;
        }

        .alert-warning {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-radius: 5px;
        }

        .error-section {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
            padding: 1rem;
            margin-top: 1rem;
            border-radius: 5px;
        }

        .error-section h5 {
            color: #721c24;
            margin-bottom: 0.5rem;
        }

        .download-errors-btn {
            display: inline-block;
            background-color: #dc3545;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            text-decoration: none;
            font-weight: bold;
            margin-top: 1rem;
            transition: background-color 0.2s;
        }

        .download-errors-btn:hover {
            background-color: #c82333;
            color: white;
        }

        .product-option:hover {
            background-color: #f5f5f5;
        }

        .product-option.active {
            background-color: #e9ecef;
        }

        /* Custom layout for lead upload */
        .upload-grid-row {
            display: flex;
            flex-wrap: wrap;
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .upload-column {
            flex: 1;
            min-width: 300px;
        }

        @media (max-width: 768px) {
            .upload-grid-row {
                flex-direction: column;
                gap: 1.5rem;
            }
        }
</style>

<body>
    <?php include($_SERVER['DOCUMENT_ROOT'] . '/quick_start/dist/include/loader.php'); ?>

    <div class="pc-container">
        <div class="pc-content">
            
            <div class="page-header">
                <div class="page-block">
                    <div class="page-header-title" style="display: flex; justify-content: space-between; align-items: center;">
                        <h5 class="mb-0 font-medium">Lead Management</h5>
                        <a href="/quick_start/dist/templates/generate_template.php" class="choose-file-btn">Download CSV Template</a>
                    </div>
                </div>
            </div>

            <div class="main-content-wrapper">
                <?php if (isset($_SESSION['import_result'])): ?>
                    <div class="alert alert-<?php echo $_SESSION['import_result']['errors'] > 0 ? 'warning' : 'success'; ?>">
                        <h4>Import Results</h4>
                        <p><strong>Successfully imported:</strong> <?php echo $_SESSION['import_result']['success']; ?> records</p>
                        <?php if ($_SESSION['import_result']['errors'] > 0): ?>
                            <p><strong>Failed imports:</strong> <?php echo $_SESSION['import_result']['errors']; ?> records</p>
                            
                            <!-- NEW: Download Failed Rows Button -->
                            <?php if (isset($_SESSION['failed_rows_data'])): ?>
                                <a href="?download_errors=1" class="download-errors-btn">
                                    📥 Download Failed Rows CSV
                                </a>
                                <p style="margin-top: 0.5rem; font-size: 0.9rem;">
                                    <em>Download the CSV file containing only the failed rows with error reasons. Fix the issues and re-upload.</em>
                                </p>
                            <?php endif; ?>
                            
                            <?php if (!empty($_SESSION['import_result']['messages'])): ?>
                                <details style="margin-top: 1rem;">
                                    <summary style="cursor: pointer; font-weight: bold;">View Error Details</summary>
                                    <div class="error-section">
                                        <ul class="mt-2">
                                            <?php foreach ($_SESSION['import_result']['messages'] as $message): ?>
                                                <li><?php echo htmlspecialchars($message); ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                </details>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                    <?php unset($_SESSION['import_result']); ?>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['import_error'])): ?>
                    <div class="alert alert-danger">
                        <strong>Error:</strong> <?php echo htmlspecialchars($_SESSION['import_error']); ?>
                    </div>
                    <?php unset($_SESSION['import_error']); ?>
                <?php endif; ?>

                <div class="lead-upload-container">
                    <form method="POST" enctype="multipart/form-data" id="uploadForm">
                        <div class="upload-grid-row">
                            <!-- Left Column: Product Selection -->
                            <div class="upload-column product-selection-section">
                                <h2 class="section-title">Select Product <span style="color: red;">*</span></h2>
                                
                                <div class="form-group" style="position: relative;">
                                    
                                    <input type="text" id="product_search" class="form-control" placeholder="Type to search product..." autocomplete="off" style="width: 100%; padding: 10px; border: 1px solid #ced4da; border-radius: 4px;">
                                    <input type="hidden" name="product_id" id="product_id" required>
                                    <div id="product_dropdown" style="display: none; position: absolute; background: white; border: 1px solid #ced4da; border-top: none; max-height: 150px; overflow-y: auto; width: 100%; z-index: 1000; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                                        <?php foreach ($products as $prod): ?>
                                            <div class="product-option" data-id="<?php echo $prod['id']; ?>" data-name="<?php echo htmlspecialchars($prod['name']); ?>" data-code="<?php echo htmlspecialchars($prod['product_code']); ?>" style="padding: 10px; cursor: pointer; border-bottom: 1px solid #f0f0f0;">
                                                <strong><?php echo htmlspecialchars($prod['name']); ?></strong> (<?php echo htmlspecialchars($prod['product_code']); ?>)
                                                <?php if (isset($_SESSION['allow_inventory']) && $_SESSION['allow_inventory'] == 1): ?>
                                                    <span style="color: #6c757d; font-size: 0.9em;"> - Stock: <?php echo $prod['stock_quantity']; ?></span>
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                        <div id="no_products_found" style="display: none; padding: 10px; color: #999; text-align: center;">No products found</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Right Column: CSV Upload -->
                            <div class="upload-column file-upload-section" style="margin-bottom: 0; padding-bottom: 0;">
                                <h2 class="section-title">CSV Upload</h2>
                                <div class="file-upload-box" style="margin-top: 0.5rem;">
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <p id="file-name" style="margin-bottom: 0.5rem;">No file selected</p>
                                    <input type="file" id="csv_file" name="csv_file" accept=".csv" style="display: none;">
                                    <button type="button" class="choose-file-btn" onclick="document.getElementById('csv_file').click()">Choose File</button>
                                </div>
                                </div>
                            </div>
                        </div>

                        <hr>

                        <br>

                        <div class="users-section">
                            <h2 class="section-title">Select Users</h2>
                            <p class="text-muted">Choose which users will receive the imported leads</p>
                            
                            <ul class="users-list" id="usersList">
                                <?php if (!empty($users)): ?>
                                    <?php foreach ($users as $user): ?>
                                        <li>
                                            <input type="checkbox" id="user_<?php echo $user['id']; ?>" name="users[]" value="<?php echo $user['id']; ?>">
                                            <label for="user_<?php echo $user['id']; ?>"><?php echo htmlspecialchars($user['name']); ?></label>
                                        </li>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <li class="no-users">No active users found</li>
                                <?php endif; ?>
                            </ul>
                        </div>
                        
                        <?php if (!empty($users)): ?>
                            <button type="button" class="select-all-btn" id="toggleSelectAll">Select All</button>
                        <?php endif; ?>
                        
                        <hr>
                        

                        <div class="action-buttons">
                            <button type="button" class="action-btn reset-btn" id="resetBtn">Reset</button>
                            <button type="submit" class="action-btn import-btn" id="importBtn">
                                 Import Leads
                            </button>
                        </div>
                        
                                
                        <br>


                        <div class="alert alert-info">
                            <h4>📋 Upload Guidelines & Error Handling</h4>
                            <ul>
                                <li><strong>Download template first</strong> - Use the CSV template with all required columns</li>
                                <li><strong>Required fields:</strong> Full Name, Phone Number, City, Address Line 1</li>
                                <li><strong>Note:</strong> Product is selected from the dropdown above</li>
                                <li><strong>Optional fields:</strong> Quantity, Phone Number 2, Email, Address Line 2, Other</li>
                                <li><strong>Quantity Rule:</strong> Defaults to 1 if empty or 0</li>
                                <li><strong>File requirements:</strong> CSV format only, 10MB maximum size</li>
                                <li><strong>Select users</strong> to randomly distribute leads</li>
                                <li><strong>Column order doesn't matter</strong> - Template can have columns in any order</li>
                                <li><strong>Extra columns allowed</strong> - System will ignore extra columns not in template</li>
                                <li><strong>⭐ NEW: Failed rows CSV export</strong> - If any rows fail, download a CSV with only failed rows and error reasons to fix and re-upload</li>
                            </ul>
                            
                            <h5 style="margin-top: 1rem;">🔍 Customer Matching Logic:</h5>
                            <ul>
                                <li><strong>Existing customer check:</strong> System searches by Phone 1, Phone 2, OR Email</li>
                                <li><strong>If ANY match found:</strong> Order created for existing customer (NO customer data update)</li>
                                <li><strong>If NO match found:</strong> New customer created with all CSV data</li>
                                <li><strong>Multiple orders allowed:</strong> Same customer can have multiple orders</li>
                            </ul>
                            
                            <h5 style="margin-top: 1rem;">⚠️ Common Errors & Solutions:</h5>
                            <ul>
                                <li><strong>"Missing required CSV headers"</strong> → Download fresh template, ensure all column headers are present</li>
                                <li><strong>"Full Name is required"</strong> → Ensure Full Name column has data</li>
                                <li><strong>"Phone Number is required"</strong> → Ensure Phone Number column has valid phone</li>
                                <li><strong>"Phone Number must be exactly 10 digits"</strong> → Use format: 0771234567</li>
                                <li><strong>"Invalid email format"</strong> → Check email syntax (or use dash - for empty)</li>
                                <li><strong>"City not found"</strong> → City name must match system database exactly</li>
                                <li><strong>"Address Line 1 is required"</strong> → Ensure Address Line 1 has data</li>
                            </ul>
                            
                            <h5 style="margin-top: 1rem;">💡 Best Practices:</h5>
                            <ul>
                                <li>Test with 2-3 rows first before uploading large batches</li>
                                <li>Phone numbers: System accepts +94771234567, 94771234567, or 0771234567 formats</li>
                                <li>Keep city names consistent with existing database entries</li>
                                <li>Use dash (-) or leave empty for optional fields like Email</li>
                                <li>Check error details if any rows fail - they show specific issues</li>
                                <li>Successful rows are imported even if some rows have errors</li>
                                <li><strong>⭐ If many rows fail:</strong> Download the failed rows CSV, fix issues in Excel, and re-upload only those rows</li>
                            </ul>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <?php include($_SERVER['DOCUMENT_ROOT'] . '/quick_start/dist/include/footer.php'); ?>
    <?php include($_SERVER['DOCUMENT_ROOT'] . '/quick_start/dist/include/scripts.php'); ?>
    
    
    <script>
        // Product Search Autocomplete
        const productSearch = document.getElementById('product_search');
        const productId = document.getElementById('product_id');
        const productDropdown = document.getElementById('product_dropdown');
        const productOptions = document.querySelectorAll('.product-option');
        
        // Show dropdown when input is focused or typed in
        productSearch.addEventListener('focus', function() {
            this.select(); 
            filterProducts(this.value);
            productDropdown.style.display = 'block';
        });
        
        productSearch.addEventListener('input', function() {
            filterProducts(this.value);
            productDropdown.style.display = 'block';
        });
        
        // Filter products based on search term
        function filterProducts(searchTerm) {
            const term = searchTerm.toLowerCase().trim();
            const noProductsFound = document.getElementById('no_products_found');
            let hasVisibleOptions = false;
            
            productOptions.forEach(option => {
                const name = option.dataset.name.toLowerCase();
                const code = option.dataset.code.toLowerCase();
                const combined = (name + ' (' + code + ')').toLowerCase();
                
    
                if (term === '' || name.includes(term) || code.includes(term) || combined === term) {
                    option.style.display = 'block';
                    hasVisibleOptions = true;
                } else {
                    option.style.display = 'none';
                }
            });
            
            if (noProductsFound) {
                noProductsFound.style.display = hasVisibleOptions ? 'none' : 'block';
            }
        }
        
        // Handle product selection
        productOptions.forEach(option => {
            option.addEventListener('click', function() {
                const id = this.dataset.id;
                const name = this.dataset.name;
                const code = this.dataset.code;
                
                productId.value = id;
                productSearch.value = name + ' (' + code + ')';
                productDropdown.style.display = 'none';
            });
        });
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!productSearch.contains(e.target) && !productDropdown.contains(e.target)) {
                productDropdown.style.display = 'none';
            }
        });
        
        // Keyboard navigation
        let activeIndex = -1;
        productSearch.addEventListener('keydown', function(e) {
            const visibleOptions = Array.from(productOptions).filter(opt => opt.style.display !== 'none');
            
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                activeIndex = Math.min(activeIndex + 1, visibleOptions.length - 1);
                updateActiveOption(visibleOptions);
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                activeIndex = Math.max(activeIndex - 1, 0);
                updateActiveOption(visibleOptions);
            } else if (e.key === 'Enter' && activeIndex >= 0) {
                e.preventDefault();
                visibleOptions[activeIndex].click();
            } else if (e.key === 'Escape') {
                productDropdown.style.display = 'none';
            }
        });
        
        function updateActiveOption(visibleOptions) {
            productOptions.forEach(opt => opt.classList.remove('active'));
            if (activeIndex >= 0 && activeIndex < visibleOptions.length) {
                visibleOptions[activeIndex].classList.add('active');
                visibleOptions[activeIndex].scrollIntoView({ block: 'nearest' });
            }
        }
    </script>
    <script>
        document.getElementById('uploadForm').addEventListener('submit', function(e) {
            const fileInput = document.getElementById('csv_file');
            const userCheckboxes = document.querySelectorAll('#usersList input[type="checkbox"]:checked');
            
            if (!fileInput.files.length) {
                alert('Please select a CSV file to upload.');
                e.preventDefault();
                return false;
            }
            
            if (userCheckboxes.length === 0) {
                alert('Please select at least one user to assign the leads to.');
                e.preventDefault();
                return false;
            }
            
            const importBtn = document.getElementById('importBtn');
            importBtn.disabled = true;
            importBtn.innerHTML = 'Importing...';
            
            return true;
        });
        
        const toggleBtn = document.getElementById('toggleSelectAll');
        if (toggleBtn) {
            toggleBtn.addEventListener('click', function() {
                const checkboxes = document.querySelectorAll('#usersList input[type="checkbox"]');
                const allChecked = Array.from(checkboxes).every(checkbox => checkbox.checked);
                
                checkboxes.forEach(checkbox => {
                    checkbox.checked = !allChecked;
                });
                
                this.textContent = allChecked ? 'Select All' : 'Deselect All';
            });
        }
        
        document.getElementById('resetBtn').addEventListener('click', function() {
            if (confirm('Are you sure you want to reset the form?')) {
                document.querySelectorAll('#usersList input[type="checkbox"]').forEach(checkbox => {
                    checkbox.checked = false;
                });
                
                document.getElementById('csv_file').value = '';
                document.getElementById('file-name').textContent = 'No file selected';
                
                if (toggleBtn) {
                    toggleBtn.textContent = 'Select All';
                }
                
                const importBtn = document.getElementById('importBtn');
                importBtn.disabled = false;
                importBtn.innerHTML = 'Import Leads';
            }
        });
        
        document.getElementById('csv_file').addEventListener('change', function() {
            const file = this.files[0];
            const fileNameEl = document.getElementById('file-name');
            
            if (file) {
                const validExtensions = ['.csv'];
                const fileName = file.name.toLowerCase();
                const isValidExtension = validExtensions.some(ext => fileName.endsWith(ext));
                
                if (!isValidExtension) {
                    alert('Please select a valid CSV file.');
                    this.value = '';
                    fileNameEl.textContent = 'No file selected';
                    return;
                }
                
                const maxSize = 10 * 1024 * 1024;
                if (file.size > maxSize) {
                    alert('File size must be less than 10MB.');
                    this.value = '';
                    fileNameEl.textContent = 'No file selected';
                    return;
                }
                
                fileNameEl.textContent = file.name + ' (' + (file.size / 1024).toFixed(1) + ' KB)';
            } else {
                fileNameEl.textContent = 'No file selected';
            }
        });
    </script>
</body>
</html>