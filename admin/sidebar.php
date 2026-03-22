<!-- Sidebar Styles -->
<style>
#sidenavAccordion {
    background: linear-gradient(180deg, #1a1a2e 0%, #0f172a 100%);
    min-height: 100vh;
    border-right: 1px solid rgba(255, 255, 255, 0.06);
}

.sb-sidenav-menu-heading {
    color: rgba(148, 163, 184, 0.6);
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 1px;
    padding: 18px 1rem 6px;
}

.sb-sidenav-menu .nav-link {
    color: rgba(255, 255, 255, 0.6);
    padding: 0.6rem 1rem;
    display: flex;
    align-items: center;
    font-size: 13.5px;
    font-weight: 500;
    transition: all 0.15s;
    text-decoration: none;
}

.sb-sidenav-menu .nav-link .sb-nav-link-icon {
    color: rgba(148, 163, 184, 0.5);
    margin-right: 0.75rem;
    font-size: 14px;
}

.sb-sidenav-menu .nav-link:hover {
    background: rgba(255, 255, 255, 0.06);
    color: #fff;
}

.sb-sidenav-menu .nav-link:hover .sb-nav-link-icon {
    color: rgba(255, 255, 255, 0.7);
}

.sb-sidenav-menu .nav-link.active {
    background: rgba(99, 102, 241, 0.15);
    color: #a5b4fc;
    font-weight: 600;
}

.sb-sidenav-menu .nav-link.active .sb-nav-link-icon {
    color: #818cf8;
}

.sb-sidenav-menu .nav-link.parent-active {
    background: rgba(255, 255, 255, 0.04);
    color: rgba(255, 255, 255, 0.8);
}

.sb-sidenav-collapse-arrow i {
    color: rgba(148, 163, 184, 0.4);
    font-size: 11px;
}

.sb-sidenav-menu-nested.nav {
    padding-left: 1rem;
    background: linear-gradient(180deg, #1f1f37ff 0%, #1f1f37ff 100%);
}

.sb-sidenav-menu-nested.nav .nav-link {
    padding: 0.4rem 1rem;
    font-size: 13px;
}

.sb-sidenav-footer {
    margin-top: auto;
    padding: 1rem;
    font-size: 0.8rem;
    background: rgba(0, 0, 0, 0.15);
    border-top: 1px solid rgba(255, 255, 255, 0.06);
    color: rgba(148, 163, 184, 0.6);
}

#sidenavAccordion {
    overflow-y: auto;
    scrollbar-width: thin;
    scrollbar-color: rgba(255,255,255,0.08) transparent;
}

#sidenavAccordion::-webkit-scrollbar {
    width: 5px;
}

#sidenavAccordion::-webkit-scrollbar-track {
    background: transparent;
}

#sidenavAccordion::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.1);
    border-radius: 10px;
    backdrop-filter: blur(4px);
    -webkit-backdrop-filter: blur(4px);
}

#sidenavAccordion::-webkit-scrollbar-thumb:hover {
    background: rgba(255, 255, 255, 0.18);
}
</style>

<!-- Sidebar HTML -->
<div id="layoutSidenav_nav">
    <nav class="sb-sidenav accordion sb-sidenav-dark" id="sidenavAccordion">
        <div class="sb-sidenav-menu">
            <div class="nav">
                <?php
                $userRoleId = isset($_SESSION['role_id']) ? (int)$_SESSION['role_id'] : 0;
                $isAdmin = ($userRoleId === 1);
                $isModerator = ($userRoleId === 3);
                $canApproveReject = $isAdmin || $isModerator;
                $canManageUsers = $isAdmin;
                $canEditRecords = $isAdmin || $isModerator;
                ?>

                <div class="sb-sidenav-menu-heading">Main</div>
                <a class="nav-link" href="index.php" id="dashboard-link">
                    <div class="sb-nav-link-icon"><i class="fas fa-chart-pie"></i></div>
                    Dashboard
                </a>

                <div class="sb-sidenav-menu-heading">Business</div>

                <!-- Invoices -->
                <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapseInvoices"
                    aria-expanded="false" aria-controls="collapseInvoices" id="invoices-dropdown">
                    <div class="sb-nav-link-icon"><i class="fas fa-file-invoice-dollar"></i></div>
                    Invoices
                    <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                </a>
                <div class="collapse" id="collapseInvoices" data-bs-parent="#sidenavAccordion">
                    <nav class="sb-sidenav-menu-nested nav">
                        <a class="nav-link" href="invoice_create.php" id="create-invoice-link">Create Invoice</a>
                        <a class="nav-link" href="invoice_list.php" id="all-invoices-link">All Invoices</a>
                        <?php if ($canEditRecords): ?>
                        <a class="nav-link" href="pending_invoice_list.php" id="pending-invoices-link">Pending Invoices</a>
                        <a class="nav-link" href="complete_invoice_list.php" id="complete-invoices-link">Complete Invoices</a>
                        <a class="nav-link" href="cancel_invoice_list.php" id="cancel-invoices-link">Cancel Invoices</a>
                        <?php endif; ?>
                    </nav>
                </div>

                <!-- Customers -->
                <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapseCustomers"
                    aria-expanded="false" aria-controls="collapseCustomers" id="customers-dropdown">
                    <div class="sb-nav-link-icon"><i class="fas fa-users"></i></div>
                    Customers
                    <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                </a>
                <div class="collapse" id="collapseCustomers" data-bs-parent="#sidenavAccordion">
                    <nav class="sb-sidenav-menu-nested nav">
                        <a class="nav-link" href="customer_list.php" id="all-customers-link">All Customers</a>
                        <?php if ($canEditRecords): ?>
                        <a class="nav-link" href="add_customer.php" id="add-customer-link">Add New Customer</a>
                        <?php endif; ?>
                    </nav>
                </div>

                <!-- Inquiries -->
                <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapseInquiries"
                    aria-expanded="false" aria-controls="collapseInquiries" id="inquiries-dropdown">
                    <div class="sb-nav-link-icon"><i class="fas fa-envelope-open-text"></i></div>
                    Inquiries
                    <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                </a>
                <div class="collapse" id="collapseInquiries" data-bs-parent="#sidenavAccordion">
                    <nav class="sb-sidenav-menu-nested nav">
                        <a class="nav-link" href="display_inquries.php" id="all-inquiries-link">All Inquiries</a>
                        <?php if ($canApproveReject): ?>
                        <a class="nav-link" href="display_pending_inquiries.php" id="pending-inquiries-link">Pending Inquiries</a>
                        <a class="nav-link" href="display_approved_inquiries.php" id="approved-inquiries-link">Approved Inquiries</a>
                        <a class="nav-link" href="display_rejected_inquiries.php" id="rejected-inquiries-link">Rejected Inquiries</a>
                        <?php endif; ?>
                    </nav>
                </div>

                <!-- Users (Admin Only) -->
                <?php if ($canManageUsers): ?>
                <div class="sb-sidenav-menu-heading">Administration</div>
                <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapseUsers"
                    aria-expanded="false" aria-controls="collapseUsers" id="users-dropdown">
                    <div class="sb-nav-link-icon"><i class="fas fa-user-shield"></i></div>
                    Users
                    <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                </a>
                <div class="collapse" id="collapseUsers" data-bs-parent="#sidenavAccordion">
                    <nav class="sb-sidenav-menu-nested nav">
                        <a class="nav-link" href="users.php" id="all-users-link">All Users</a>
                        <a class="nav-link" href="add_user.php" id="add-user-link">Add New User</a>
                        <a class="nav-link" href="user_logs.php" id="user-logs-link">Activity Logs</a>
                    </nav>
                </div>
                <?php endif; ?>

                <!-- Products -->
                <div class="sb-sidenav-menu-heading">Catalog</div>
                <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapseProducts"
                    aria-expanded="false" aria-controls="collapseProducts" id="products-dropdown">
                    <div class="sb-nav-link-icon"><i class="fas fa-boxes-stacked"></i></div>
                    Products
                    <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                </a>
                <div class="collapse" id="collapseProducts" data-bs-parent="#sidenavAccordion">
                    <nav class="sb-sidenav-menu-nested nav">
                        <a class="nav-link" href="product_list.php" id="all-products-link">All Products</a>
                        <a class="nav-link" href="package_list.php" id="all-packages-link">Package List</a>
                        <?php if ($canEditRecords): ?>
                        <a class="nav-link" href="add_product.php" id="add-product-link">Add New Product</a>
                        <?php endif; ?>
                    </nav>
                </div>
            </div>
        </div>
    </nav>
</div>

<!-- Active State Script -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const currentPage = window.location.pathname.split('/').pop();
    document.querySelectorAll('.sb-sidenav-menu .nav-link').forEach(link => {
        const href = link.getAttribute('href');
        if (href && href !== '#' && href !== '#!') {
            if (currentPage === href.split('/').pop()) {
                link.classList.add('active');
                const parentCollapse = link.closest('.collapse');
                if (parentCollapse) {
                    new bootstrap.Collapse(parentCollapse, { toggle: false }).show();
                    const toggle = document.querySelector('[data-bs-target="#' + parentCollapse.id + '"]');
                    if (toggle) {
                        toggle.classList.add('parent-active');
                        toggle.classList.remove('collapsed');
                        toggle.setAttribute('aria-expanded', 'true');
                    }
                }
            }
        }
    });
});
</script>