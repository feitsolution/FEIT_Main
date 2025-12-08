<?php
// =========================================================================
// FUNCTION DEFINITION (MUST BE INCLUDED/DEFINED BEFORE THE SIDEBAR HTML)
// =========================================================================
if (!function_exists('get_logo_with_fallback')) {
    /**
     * Fetches logo URL and company name from the branding table with fallbacks.
     * Assumes $conn is a valid mysqli link.
     */
    function get_logo_with_fallback($conn, $default_logo = '../assets/images/lily.jpeg', $default_name = 'order_management') {
        $result = [
            'logo_url' => $default_logo,
            'company_name' => $default_name,
            'debug' => []
        ];
        
        try {
            if (!isset($conn) || !$conn) {
                $result['debug'][] = "No database connection available, using defaults.";
                return $result;
            }
            
            $query = "SELECT logo_url, company_name FROM branding WHERE active = 1 LIMIT 1";
            $db_result = mysqli_query($conn, $query);
            
            if (!$db_result) {
                throw new Exception("Query failed: " . mysqli_error($conn));
            }
            
            $data = mysqli_fetch_assoc($db_result);
            $result['debug'][] = "Query executed successfully.";
            
            if ($data) {
                // Set company name
                if (!empty($data['company_name'])) {
                    $result['company_name'] = trim($data['company_name']);
                    $result['debug'][] = "DB Company name set: " . $result['company_name'];
                }
                
                // Set logo URL with basic validation (more complex path checking removed for simplicity)
                if (!empty($data['logo_url'])) {
                    $logo_path = trim($data['logo_url']);
                    
                    // Simple check if the file path (relative/absolute) or URL is provided
                    if (filter_var($logo_path, FILTER_VALIDATE_URL) || (file_exists($logo_path) || file_exists('../' . $logo_path) || file_exists('../../' . $logo_path))) {
                        $result['logo_url'] = $logo_path;
                        $result['debug'][] = "DB Logo URL set: " . $result['logo_url'];
                    } else {
                        $result['debug'][] = "DB logo path inaccessible or invalid, using default: " . $logo_path;
                    }
                } else {
                    $result['debug'][] = "No logo URL found in database, using default.";
                }
            } else {
                $result['debug'][] = "No active branding data found, using defaults.";
            }
            
            mysqli_free_result($db_result);
            
        } catch (Exception $e) {
            $result['debug'][] = "Error: " . $e->getMessage();
            error_log("Logo fetch error: " . $e->getMessage());
        }
        
        return $result;
    }
}
?>

<nav class="pc-sidebar">
  <div class="navbar-wrapper">
    <div class="m-header flex items-center py-4 px-6 h-header-height">
      <a href="../dashboard/index.php" class="b-brand flex items-center gap-3">
        
        <?php
        // Define default logo/name for the function
        $default_logo_path = '../assets/images/lily.jpeg'; // Default fallback image
        $default_company_name = 'order_management';

        // Assuming $conn is available for database connection
        // Pass $conn, or null if it's not guaranteed to be set, to avoid PHP warnings
        $branding_info = get_logo_with_fallback(
            isset($conn) ? $conn : null, 
            $default_logo_path, 
            $default_company_name
        );
        
        // Sanitize output
        $logo_url = htmlspecialchars($branding_info['logo_url'], ENT_QUOTES, 'UTF-8');
        $company_name = htmlspecialchars($branding_info['company_name'], ENT_QUOTES, 'UTF-8');

        // Output debug info as HTML comments (remove in production by commenting out or removing the check for DEBUG_MODE)
        if (defined('DEBUG_MODE') && DEBUG_MODE) {
            echo "";
        }
        
        // Fallback data URI SVG for the onerror attribute (generic LOGO placeholder)
        $fallback_svg = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAiIGhlaWdodD0iNDAiIHZpZXdCb3g9IjAgMCA0MCA0MCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHJlY3Qgd2lkdGg9IjQwIiBoZWlnaHQ9IjQwIiBmaWxsPSIjMDA3YmZmIi8+Cjx0ZXh0IHg9IjIwIiB5PSIyNSIgZm9udC1mYW1pbHk9IkFyaWFsIiBmb250LXNpemU9IjE0IiBmaWxsPSJ3aGl0ZSIgdGV4dC1hbmNob3I9Im1pZGRsZSI+TE9HTzwvdGV4dD4KPC9zdmc+';
        ?>
        
        <img src="<?php echo $logo_url; ?>" 
          alt="<?php echo $company_name; ?> logo" 
          class="img-fluid logo logo-lg" 
          style="max-height: 40px;" 
          onerror="this.onerror=null; this.src='<?php echo $fallback_svg; ?>';" 
          onload="console.log('Logo loaded successfully: <?php echo addslashes($logo_url); ?>');" />
      </a>
    </div>
    
    <div class="navbar-content h-[calc(100vh_-_74px)] py-2.5">
      <ul class="pc-navbar">
        
        <li class="pc-item pc-caption">
          <label>Navigation</label>
        </li>
        <li class="pc-item">
          <a href="../dashboard/index.php" class="pc-link">
            <span class="pc-micon">
              <i data-feather="home"></i>
            </span>
            <span class="pc-mtext">Dashboard</span>
          </a>
        </li>
        
        <li class="pc-item pc-caption">
          <label>Order Management</label>
          <i data-feather="feather"></i>
        </li>
        
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
            }
        }
        
        if ($is_admin): ?>
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
        <?php else: ?>
        <?php endif; ?>
        
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

        <li class="pc-item pc-caption">
          <label>Lead Management</label>
          <i data-feather="feather"></i>
        </li>
        
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
        
        <li class="pc-item pc-caption">
          <label>Branding</label>
          <i data-feather="monitor"></i>
        </li>
        
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