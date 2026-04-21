<?php
// The $current_branding variable is expected to be set from header.php,
// which should be included before this file.
$sidebar_logo = $current_branding['logo'] ?? '';
$sidebar_website_name = !empty($current_branding['website_name']) ? $current_branding['website_name'] : ($current_branding['company_name'] ?? 'RMS'); 

// Determine the correct dashboard link based on the user's session role
$dashboard_link = "/breezebay/"; // Default link if no role or unknown role
if (isset($_SESSION['role'])) {
    if ($_SESSION['role'] === 'Waitor') {
        $dashboard_link = "/breezebay/waitor/waitor.php";
    } else { // Admin or Cashier
        $dashboard_link = "/breezebay/dashboard/admin_dashboard.php";
    }
}
?>
<!-- Sidebar -->
<ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion" id="accordionSidebar">

    <!-- Sidebar - Brand -->
    <a class="sidebar-brand d-flex align-items-center justify-content-center" href="<?php echo $dashboard_link; ?>">
        <div class="sidebar-brand-icon <?php if (empty($sidebar_logo)) echo 'rotate-n-15'; ?>">
            <?php if (!empty($sidebar_logo)): ?>
            <img src="/breezebay/branding/uploads/<?php echo htmlspecialchars($sidebar_logo); ?>"
                alt="<?php echo htmlspecialchars($sidebar_website_name); ?>" style="max-height: 45px;">
            <?php else: ?>
            <i class="fas fa-laugh-wink"></i>
            <?php endif; ?>
        </div>
    </a>


    <!-- Divider -->
    <hr class="sidebar-divider my-0">

    <!-- Nav Item - Dashboard -->
    <li class="nav-item">
        <a class="nav-link" href="/breezebay/dashboard/admin_dashboard.php">
            <i class="fas fa-fw fa-tachometer-alt"></i>
            <span>Dashboard</span></a>
    </li>
    
    <!-- Menus -->
    <li class="nav-item">
        <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseMenus" aria-expanded="true"
            aria-controls="collapseMenus">
            <i class="fas fa-fw fa-utensils"></i>
            <span>Menus</span>
        </a>
        <div id="collapseMenus" class="collapse" aria-labelledby="headingMenus" data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <a class="collapse-item" href="/breezebay/Menu/categories/category_add.php">Add
                    Category</a>
                <a class="collapse-item" href="/breezebay/Menu/categories/category_list.php">Category
                    List</a>
            </div>
            <div class="bg-white py-2 collapse-inner rounded">
                <a class="collapse-item" href="/breezebay/Menu/products/product_add.php">Add Products</a>
                <a class="collapse-item" href="/breezebay/Menu/products/product_list.php">Product List</a>
            </div>
        </div>

    </li>

    <!-- reports -->
    <li class="nav-item">
        <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseReports" aria-expanded="true"
            aria-controls="collapseReports">
            <i class="fas fa-fw fa-cog"></i>
            <span>Reports</span>
        </a>
        <div id="collapseReports" class="collapse" aria-labelledby="headingReports" data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <a class="collapse-item" href="/breezebay/reports/income_report.php">Income Report</a>
            </div>
        </div>
    </li>

    <!-- Reservation -->
    <li class="nav-item">
        <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseReservation" aria-expanded="true"
            aria-controls="collapseReservation">
            <i class="fas fa-fw fa-cog"></i>
            <span>Reservation</span>
        </a>
        <div id="collapseReservation" class="collapse" aria-labelledby="headingReservation" data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <a class="collapse-item" href="/breezebay/reservation/reservation.php">Make Reservation</a>
                <a class="collapse-item" href="/breezebay/reservation/reservation_table.php">Reservation List</a>
            </div>
        </div>
    </li>

    <!-- Cart -->
    <li class="nav-item">
        <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseCart" aria-expanded="true"
            aria-controls="collapseCart">
            <i class="fas fa-fw fa-cog"></i>
            <span>Cart</span>
        </a>
        <div id="collapseCart" class="collapse" aria-labelledby="headingCart" data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <a class="collapse-item" href="/breezebay/cart/cart.php">Cart</a>
                
            </div>
        </div>
    </li>

    <!-- Tax 
    <li class="nav-item">
        <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseTax" aria-expanded="true"
            aria-controls="collapseTax">
            <i class="fas fa-fw fa-cog"></i>
            <span>Tax</span>
        </a>
        <div id="collapseTax" class="collapse" aria-labelledby="headingTax" data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <a class="collapse-item" href="/breezebay/tax/tax_add.php">Add Tax</a>
                <a class="collapse-item" href="/breezebay/tax/tax_list.php">Tax Table</a>
            </div>
        </div>
    </li>-->

    <!-- Stock -->
    <li class="nav-item">
        <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseStock" aria-expanded="true"
            aria-controls="collapseStock">
            <i class="fas fa-fw fa-boxes"></i>
            <span>Stock</span>
        </a>
        <div id="collapseStock" class="collapse" aria-labelledby="headingStock" data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <a class="collapse-item" href="/breezebay/stock/stock_list.php">Stock List</a>
            </div>
        </div>
    </li>

    <!--Dinning Tables -->
    <li class="nav-item">
        <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseTable" aria-expanded="true"
            aria-controls="collapseTable">
            <i class="fas fa-fw fa-table"></i>
            <span>Tables</span>
        </a>
        <div id="collapseTable" class="collapse" aria-labelledby="headingTable" data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <a class="collapse-item" href="/breezebay/table/table_add.php">Add Table</a>
                <a class="collapse-item" href="/breezebay/table/table_list.php">Tables</a>
            </div>
        </div>
    </li>

    <!-- Staff -->
    <li class="nav-item">
        <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseStaff" aria-expanded="true"
            aria-controls="collapseStaff">
            <i class="fas fa-fw fa-users"></i>
            <span>Staff</span>
        </a>
        <div id="collapseStaff" class="collapse" aria-labelledby="headingStaff" data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <a class="collapse-item" href="/breezebay/staff/staff_add.php">Add Staff</a>
                <a class="collapse-item" href="/breezebay/staff/staff_list.php">Staff List</a>
            </div>
        </div>
    </li>

    <!-- Nav Item - Branding -->
    <li class="nav-item">
        <a class="nav-link" href="/breezebay/branding/branding.php">
            <i class="fas fa-fw fa-cog"></i>
            <span>branding</span></a>
    </li>

    <!-- Divider -->
    <hr class="sidebar-divider">
    
    <!-- Sidebar Toggler (Sidebar) -->
    <div class="text-center d-none d-md-inline">
        <button class="rounded-circle border-0" id="sidebarToggle"></button>
    </div>

</ul>
<!-- End of Sidebar -->