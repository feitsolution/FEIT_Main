<!-- Separate CSS Styling -->
<style>
/* Main Sidebar Styles */
#sidenavAccordion {
    background-color: #212529; /* Dark sidebar background */
    min-height: 100vh;
}

/* Sidebar brand/logo section */
.sb-sidenav-brand {
    background-color: #1b1e23; /* Slightly darker for brand section */
    display: flex;
    align-items: center;
    padding: 1rem 1rem;
}

.sb-sidenav-brand img {
    max-height: 40px;
    margin-right: 0.5rem;
}

.sb-sidenav-brand span {
    color: #ffffff;
    font-weight: 700;
    font-size: 1.1rem;
}

/* Menu headings */
.sb-sidenav-menu-heading {
    color: #a7c7d9;
    font-size: 0.75rem;
    text-transform: uppercase;
    padding: 0.5rem 1rem;
    letter-spacing: 0.05em;
}

/* Links */
.nav-link {
    color: #ffffff;
    padding: 0.5rem 1rem;
    transition: background-color 0.3s, color 0.3s;
    display: flex;
    align-items: center;
}

.nav-link .sb-nav-link-icon {
    margin-right: 0.5rem;
    color: #a7c7d9;
    display: flex;
    align-items: center;
}

/* Hover effect */
.nav-link:hover {
    background-color: #2c3136;
    color: #ffffff;
}

/* Active link */
.nav-link.active {
    background-color: #414244;
    color: #ffffff;
}

/* Parent link when child menu is expanded */
.nav-link.parent-active {
    background-color: #2c3136;
}

/* Collapse arrows */
.sb-sidenav-collapse-arrow i {
    color: #a7c7d9;
    transition: transform 0.3s;
}

.nav-link.collapsed .sb-sidenav-collapse-arrow i {
    transform: rotate(0deg);
}

.nav-link:not(.collapsed) .sb-sidenav-collapse-arrow i {
    transform: rotate(180deg);
}

/* Nested menu links */
.sb-sidenav-menu-nested.nav {
    background-color: #2a2e34; /* Slightly lighter than sidebar */
    padding-left: 1rem;
}

.sb-sidenav-menu-nested.nav .nav-link {
    padding: 0.4rem 1rem;
    font-size: 0.9rem;
}

/* Footer section */
.sb-sidenav-footer {
    background-color: #0a3e57;
    color: #ffffff;
    padding: 1rem;
    font-size: 0.85rem;
}

.sb-sidenav-footer .small {
    color: #a7c7d9;
}

/* Optional: Scrollbar styling */
#sidenavAccordion::-webkit-scrollbar {
    width: 6px;
}

#sidenavAccordion::-webkit-scrollbar-thumb {
    background-color: #2c3136;
    border-radius: 3px;
}
</style>
<!-- HTML Structure -->
<div id="layoutSidenav_nav">
    <nav class="sb-sidenav accordion sb-sidenav-dark" id="sidenavAccordion">
        <div class="sb-sidenav-menu">
            <div class="nav">
                <div class="sb-sidenav-menu-heading">Navigation</div>
                <a class="nav-link" href="index.php" id="dashboard-link">
                    <div class="sb-nav-link-icon"><i class="fas fa-tachometer-alt"></i></div>
                    Dashboard
                </a>
                
                <div class="sb-sidenav-menu-heading">Operations</div>

                 <!-- Invoices Section -->
                <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapseInvoices"
                    aria-expanded="false" aria-controls="collapseInvoices" id="invoices-dropdown">
                    <div class="sb-nav-link-icon"><i class="fas fa-file-invoice"></i></div>
                    Invoices
                    <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                </a>
                <div class="collapse" id="collapseInvoices" data-bs-parent="#sidenavAccordion">
                    <nav class="sb-sidenav-menu-nested nav">
                        <a class="nav-link" href="invoice_create.php" id="create-invoice-link">Create Invoice</a>
                        <a class="nav-link" href="invoice_list.php" id="all-invoices-link">
                            All Invoices 
                        </a>
                        <!-- <a class="nav-link" href="paid_invoice.php" id="paid-invoices-link">
                            Paid Invoices 
                        </a>
                        <a class="nav-link" href="unpaid_invoice.php" id="unpaid-invoices-link">
                            Unpaid Invoices 
                        </a> -->
                        <a class="nav-link" href="pending_invoice_list.php" id="unpaid-invoices-link">
                            Pending Invoices 
                        </a>
                        <a class="nav-link" href="complete_invoice_list.php" id="unpaid-invoices-link">
                            Complete Invoices 
                        </a>
                        <a class="nav-link" href="cancel_invoice_list.php" id="cancel-invoices-link">
                            Cancel Invoices 
                        </a>
                    </nav>
                </div>
                
                <!-- Customers Section -->
                <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapseCustomers"
                    aria-expanded="false" aria-controls="collapseCustomers" id="customers-dropdown">
                    <div class="sb-nav-link-icon"><i class="fas fa-user-tie"></i></div>
                    Customers
                    <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                </a>
                <div class="collapse" id="collapseCustomers" data-bs-parent="#sidenavAccordion">
                    <nav class="sb-sidenav-menu-nested nav">
                        <a class="nav-link" href="customer_list.php" id="all-customers-link">
                            All Customers 
                        </a>
                        <a class="nav-link" href="add_customer.php" id="add-customer-link">Add New Customer</a>
                    </nav>
                </div>

                                <!-- Inquiries Section -->
                <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapseInquiries"
                    aria-expanded="false" aria-controls="collapseInquiries" id="inquiries-dropdown">
                    <div class="sb-nav-link-icon"><i class="fas fa-columns"></i></div>
                    Inquiries
                    <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                </a>
                <div class="collapse" id="collapseInquiries" data-bs-parent="#sidenavAccordion">
                    <nav class="sb-sidenav-menu-nested nav">
                        <a class="nav-link" href="display_inquries.php" id="all-inquiries-link">
                            All Inquiries 
                        </a>
                        <a class="nav-link" href="display_pending_inquiries.php" id="pending-inquiries-link">
                            Pending Inquiries 
                        </a>
                        <a class="nav-link" href="display_approved_inquiries.php" id="approved-inquiries-link">
                            Approved Inquiries
                        </a>
                        <a class="nav-link" href="display_rejected_inquiries.php" id="rejected-inquiries-link">
                            Rejected Inquiries
                             <!-- <span class="badge bg-danger"><?php echo $rejected_inquiries; ?></span> -->
                        </a>
                    </nav>
                </div>
                
                  <!-- Users Section -->
                <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapseUsers"
                    aria-expanded="false" aria-controls="collapseUsers" id="users-dropdown">
                    <div class="sb-nav-link-icon"><i class="fas fa-users"></i></div>
                    Users
                    <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                </a>
                <div class="collapse" id="collapseUsers" data-bs-parent="#sidenavAccordion">
                    <nav class="sb-sidenav-menu-nested nav">
                        <a class="nav-link" href="users.php" id="all-users-link">
                            All Users 
                        </a>
                         <a class="nav-link" href="user_logs.php" id="user-logs-link">User Activity Logs</a>
                         
                        <a class="nav-link" href="add_user.php" id="add-user-link">Add New User</a>
                    </nav> 
                </div>
                
                <!-- Products Section -->
                <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapseProducts"
                    aria-expanded="false" aria-controls="collapseProducts" id="products-dropdown">
                    <div class="sb-nav-link-icon"><i class="fas fa-box"></i></div>
                    Products
                    <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                </a>
                <div class="collapse" id="collapseProducts" data-bs-parent="#sidenavAccordion">
                    <nav class="sb-sidenav-menu-nested nav">
                        <a class="nav-link" href="product_list.php" id="all-products-link">
                            All Products
                        </a>
                        <a class="nav-link" href="add_product.php" id="add-product-link">Add New Product</a>
                    </nav>
                </div>
            </div>
        </div>
        <!-- <div class="sb-sidenav-footer">
            <div class="small">Logged in as:</div>
            <?php echo isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'Administrator'; ?>
        </div> -->
    </nav>
</div>

<!-- Add JavaScript to handle active states -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Get current page URL
    const currentPage = window.location.pathname.split('/').pop();
    
    // Select all nav links
    const navLinks = document.querySelectorAll('.nav-link');
    
    // Loop through each link to find and mark the active one
    navLinks.forEach(link => {
        // Extract the href attribute
        const href = link.getAttribute('href');
        
        // Skip links with # as href (dropdown toggles)
        if (href && href !== '#') {
            const linkPage = href.split('/').pop();
            
            // If current page matches link's href
            if (currentPage === linkPage) {
                // Add active class to the link
                link.classList.add('active');
                
                // If this is a nested link, expand its parent dropdown
                const parentCollapse = link.closest('.collapse');
                if (parentCollapse) {
                    // Show the collapse
                    const bsCollapse = new bootstrap.Collapse(parentCollapse, {
                        toggle: false
                    });
                    bsCollapse.show();
                    
                    // Add a class to the parent dropdown toggle
                    const parentToggle = document.querySelector(`[data-bs-target="#${parentCollapse.id}"]`);
                    if (parentToggle) {
                        parentToggle.classList.add('parent-active');
                        parentToggle.classList.remove('collapsed');
                        parentToggle.setAttribute('aria-expanded', 'true');
                    }
                }
            }
        }
    });
});
</script>