<!-- [ Sidebar Menu ] start -->
<nav class="pc-sidebar">
  <div class="navbar-wrapper">
    <!-- Header section with logo -->
    <div class="m-header flex items-center py-4 px-6 h-header-height">
      <a href="../dashboard/index.php" class="b-brand flex items-center gap-3">
        <?php
        /**
         * PHP Logic to retrieve logo and company name from the database.
         * Default values are used if the database connection fails or no active branding is found.
         */
        
        // Initialize default values
        $default_logo_url = '../assets/images/lily.jpeg'; // Default logo path
        $default_company_name = 'Lily Collection'; // Default company name
        
        $logo_url = $default_logo_url;
        $company_name = $default_company_name;
        
        try {
            // Check if database connection is available
            if (!isset($conn) || !$conn) {
                // Connection not available, use defaults
                throw new Exception("Database connection not available.");
            }
            
            // Query to fetch active branding data
            $branding_query = "SELECT logo_url, company_name FROM branding WHERE active = 1 LIMIT 1";
            $branding_result = mysqli_query($conn, $branding_query);
            
            if (!$branding_result) {
                // Query failed
                throw new Exception("Database query failed: " . mysqli_error($conn));
            }
            
            $branding_data = mysqli_fetch_assoc($branding_result);
            
            if ($branding_data) {
                // Update variables from database data if present
                if (!empty($branding_data['company_name'])) {
                    $company_name = trim($branding_data['company_name']);
                }
                if (!empty($branding_data['logo_url'])) {
                    // Use the logo URL exactly as stored in the DB
                    $logo_url = trim($branding_data['logo_url']);
                }
            } else {
                 // No active branding found
                 // error_log("No active branding found in database. Using defaults.");
            }
            
            // Clean up result
            mysqli_free_result($branding_result);
            
        } catch (Exception $e) {
            // Log error but continue with default logo/name
            error_log("Sidebar Logo Fetch Error: " . $e->getMessage());
        }
        
        // Sanitize output for security
        $logo_url = htmlspecialchars($logo_url, ENT_QUOTES, 'UTF-8');
        $company_name = htmlspecialchars($company_name, ENT_QUOTES, 'UTF-8');
        
        // Final fallback SVG data URI for the HTML onerror attribute
        $svg_fallback = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAiIGhlaWdodD0iNDAiIHZpZXdCb3g9IjAgMCA0MCA0MCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHJlY3Qgd2lkdGg9IjQwIiBoZWlnaHQ9IjQwIiBmaWxsPSIjMDA3YmZmIi8+Cjx0ZXh0IHg9IjIwIiB5PSIyNSIgZm9udC1mYW1pbHk9IkFyaWFsIiBmb250LXNpemU9IjE0IiBmaWxsPSJ3aGl0ZSIgdGV4dC1hbmNob3I9Im1pZGRsZSI+TE9HTzwvdGV4dD4KPC9zdmc+';
        ?>
        
        <img src="<?php echo $logo_url; ?>" 
             alt="<?php echo $company_name; ?>" 
             class="img-fluid logo logo-lg" 
             style="max-height: 40px;" 
             onerror="this.onerror=null; this.src='<?php echo $svg_fallback; ?>';" 
             onload="console.log('Logo source used: <?php echo addslashes($logo_url); ?>');" />
        <span class="text-lg font-semibold text-primary"><?php echo $company_name; ?></span>
      </a>
    </div>
    
    <!-- Main navigation content -->
    <div class="navbar-content h-[calc(100vh_-_74px)] py-2.5">
      <ul class="pc-navbar">
        
        <!-- Navigation Section Header -->
        <li class="pc-item pc-caption">
          <label>Navigation</label>
        </li>
        <!-- Dashboard Link -->
        <li class="pc-item">
          <a href="../dashboard/index.php" class="pc-link">
            <span class="pc-micon">
              <i data-feather="home"></i>
            </span>
            <span class="pc-mtext">Dashboard</span>
          </a>
        </li>
        
        <!-- Order Management Section -->
        <li class="pc-item pc-caption">
          <label>Order Management</label>
          <i data-feather="feather"></i>
        </li>
        
        <!-- Orders Dropdown Menu -->
        <li class="pc-item pc-hasmenu">
          <a href="#!" class="pc-link">
            <span class="pc-micon"> <i data-feather="edit"></i></span>
            <span class="pc-mtext">Orders Management</span>
            <span class="pc-arrow"><i class="ti ti-chevron-right"></i></span>
          </a>
          <ul class="pc-submenu">
            <li class="pc-item"><a class="pc-link" href="../orders/create_order.php">Create Order</a></li>
            <li class="pc-item"><a class="pc-link" href="../orders/order_list.php"> Processed Orders</a></li>
            <li class="pc-item"><a class="pc-link" href="../orders/pending_order_list.php">Pending Orders</a></li>
            <li class="pc-item"><a class="pc-link" href="../orders/dispatch_order_list.php">Dispatch Orders</a></li>
            <li class="pc-item"><a class="pc-link" href="../orders/couriers.php">Courier Management</a></li>
            <li class="pc-item"><a class="pc-link" href="../orders/cancel_order_list.php">Cancel Orders</a></li>
               <li class="pc-item"><a class="pc-link" href="../orders/complete_mark_upload.php">Completed Mark Upload</a></li>
            <li class="pc-item"><a class="pc-link" href="../orders/payment_report.php"> Payment Report</a></li>
            <li class="pc-item"><a class="pc-link" href="../orders/return_csv_upload.php">Return CSV Upload</a></li>
            <li class="pc-item"><a class="pc-link" href="../orders/return_complete_order_list.php">Return Complete Orders</a></li>
            <li class="pc-item"><a class="pc-link" href="../orders/return_handover_order_list.php">Return Handover Orders</a></li>
            <li class="pc-item"><a class="pc-link" href="../orders/label_print.php">Label Print</a></li>
          </ul>
        </li>

        <!-- Tracking Management Dropdown Menu -->
        <li class="pc-item pc-hasmenu">
          <a href="#!" class="pc-link">
            <span class="pc-micon"> <i data-feather="users"></i></span>
            <span class="pc-mtext">Tracking Management</span>
            <span class="pc-arrow"><i class="ti ti-chevron-right"></i></span>
          </a>
          <ul class="pc-submenu">
            <li class="pc-item"><a class="pc-link" href="../tracking/tracking_upload.php">Tracking Upload</a></li>
          </ul>
        </li>
        
        <!-- Users Management Dropdown - Only visible to admin users -->
        <?php 
        // Check if user has admin privileges (multiple possible scenarios)
        $is_admin = false;
        
        // Option 1: Check if role_id exists in session
        if (isset($_SESSION['user_role_id'])) {
            // Admin might be role_id 1, or check role name
            $is_admin = ($_SESSION['user_role_id'] == 1);
        }
        
        // Option 2: If no role in session, check database directly
        if (!$is_admin && isset($_SESSION['user_id']) && isset($conn) && $conn) {
            $user_id = mysqli_real_escape_string($conn, $_SESSION['user_id']);
            $role_check_query = "SELECT u.role_id, r.name as role_name 
                               FROM users u 
                               LEFT JOIN roles r ON u.role_id = r.id 
                               WHERE u.id = '$user_id'";
            $role_result = mysqli_query($conn, $role_check_query);
            
            if ($role_result && $role_data = mysqli_fetch_assoc($role_result)) {
                // Check if role is admin (by ID or name)
                $is_admin = ($role_data['role_id'] == 1 || 
                            strtolower($role_data['role_name']) == 'admin' || 
                            strtolower($role_data['role_name']) == 'administrator' ||
                            strtolower($role_data['role_name']) == 'super admin');
                 mysqli_free_result($role_result); // Clean up
            }
        }
        
        if ($is_admin): ?>
        <!-- DEBUG: User has admin access -->
        <li class="pc-item pc-hasmenu">
          <a href="#!" class="pc-link">
            <span class="pc-micon"> <i data-feather="type"></i></span>
            <span class="pc-mtext">Users</span>
            <span class="pc-arrow"><i class="ti ti-chevron-right"></i></span>
          </a>
          <ul class="pc-submenu">
            <li class="pc-item"><a class="pc-link" href="../users/add_user.php">Add New User</a></li>
            <li class="pc-item"><a class="pc-link" href="../users/users.php">All Users</a></li>
            <li class="pc-item"><a class="pc-link" href="../users/user_logs.php">User Activity Log</a></li>
          </ul>
        </li>
        <?php endif; // End admin check ?>
        
        <!-- Customers Management Dropdown -->
        <li class="pc-item pc-hasmenu">
          <a href="#!" class="pc-link">
            <span class="pc-micon"> <i data-feather="feather"></i></span>
            <span class="pc-mtext">Customers</span>
            <span class="pc-arrow"><i class="ti ti-chevron-right"></i></span>
          </a>
          <ul class="pc-submenu">
            <li class="pc-item"><a class="pc-link" href="../customers/add_customer.php">Add New Customer</a></li>
            <li class="pc-item"><a class="pc-link" href="../customers/customer_list.php">All Customers</a></li>
          </ul>
        </li>
        
        <!-- Products Management Dropdown -->
        <li class="pc-item pc-hasmenu">
          <a href="#!" class="pc-link">
            <span class="pc-micon"> <i data-feather="package"></i></span>
            <span class="pc-mtext">Products</span>
            <span class="pc-arrow"><i class="ti ti-chevron-right"></i></span>
          </a>
          <ul class="pc-submenu">
            <li class="pc-item"><a class="pc-link" href="../products/add_product.php">Add New Product</a></li>
            <li class="pc-item"><a class="pc-link" href="../products/product_list.php">All Products</a></li>
          </ul>
        </li>

        <!-- Leads Management Section -->
        <li class="pc-item pc-caption">
          <label>Lead Management</label>
          <i data-feather="feather"></i>
        </li>
        
        <!-- Leads Dropdown Menu -->
        <li class="pc-item pc-hasmenu">
          <a href="#!" class="pc-link">
            <span class="pc-micon"> <i data-feather="users"></i></span>
            <span class="pc-mtext">Leads</span>
            <span class="pc-arrow"><i class="ti ti-chevron-right"></i></span>
          </a>
          <ul class="pc-submenu">
            <li class="pc-item"><a class="pc-link" href="../leads/lead_upload.php">Lead Upload</a></li>
            <li class="pc-item"><a class="pc-link" href="../leads/lead_list.php">Lead List</a></li>
             <li class="pc-item"><a class="pc-link" href="../leads/my_leads.php">My Leads </a></li>
            <li class="pc-item"><a class="pc-link" href="../leads/city_list.php">City List</a></li>
          </ul>
        </li>
        
        <!-- Branding Section Header -->
        <li class="pc-item pc-caption">
          <label>Branding</label>
          <i data-feather="monitor"></i>
        </li>
        
        <!-- Settings Dropdown -->
        <li class="pc-item pc-hasmenu">
          <a href="#!" class="pc-link">
            <span class="pc-micon"> <i data-feather="settings"></i></span>
            <span class="pc-mtext">Settings</span>
            <span class="pc-arrow"><i class="ti ti-chevron-right"></i></span>
          </a>
          <ul class="pc-submenu">
            <li class="pc-item"><a class="pc-link" href="../settings/branding.php">Edit Branding</a></li>
          </ul>
        </li>
        
      </ul>
    </div>
  </div>
</nav>
<!-- [ Sidebar Menu ] end -->