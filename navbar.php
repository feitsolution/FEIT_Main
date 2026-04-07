<?php include 'loader.php'; ?>
<style>
  @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');

  .premium-nav {
    font-family: 'Inter', sans-serif !important;
    background: transparent !important;
    transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1) !important;
    padding: 14px 0 !important;
    border-bottom: 1px solid transparent;
    z-index: 1000;
  }

  .premium-nav.top-nav-collapse {
    background: rgba(5, 5, 5, 0.80) !important;
    backdrop-filter: blur(20px) saturate(180%) !important;
    -webkit-backdrop-filter: blur(20px) saturate(180%) !important;
    padding: 14px 0 !important;
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    box-shadow: 0 4px 30px rgba(0, 0, 0, 0.3);
  }

  .premium-nav .nav-link {
    color: rgba(255, 255, 255, 0.75) !important;
    font-size: 14px;
    font-weight: 500;
    letter-spacing: 0.5px;
    padding: 8px 16px !important;
    margin: 0 4px;
    border-radius: 100px;
    transition: all 0.3s ease !important;
    position: relative;
  }

  .premium-nav .nav-link::after {
    content: '';
    position: absolute;
    bottom: 4px;
    left: 16px;
    right: 16px;
    height: 2px;
    background: linear-gradient(90deg, #2500f5, #0fc536);
    transform: scaleX(0);
    transition: transform 0.3s ease;
    transform-origin: center;
  }

  .premium-nav .nav-link:hover::after,
  .premium-nav .nav-item.active .nav-link::after {
    transform: scaleX(1);
  }

  .premium-nav .nav-link:hover,
  .premium-nav .nav-item.active .nav-link {
    color: #ffffff !important;
    background: transparent;
  }

  .premium-nav .navbar-brand img {
    height: 44px;
    transition: all 0.3s ease;
  }

  .premium-nav.top-nav-collapse .navbar-brand img {
    height: 44px;
  }

  /* ===== Hamburger Button ===== */
  .mobile-menu-btn {
    display: none;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    width: 44px;
    height: 44px;
    background: rgba(255, 255, 255, 0.12);
    border: 1px solid rgba(255, 255, 255, 0.25);
    border-radius: 10px;
    cursor: pointer;
    padding: 0;
    gap: 5px;
    z-index: 1001;
    transition: background 0.3s ease;
  }

  .mobile-menu-btn:hover {
    background: rgba(255, 255, 255, 0.2);
  }

  .mobile-menu-btn span {
    display: block;
    width: 20px;
    height: 2px;
    background: #ffffff;
    border-radius: 2px;
    transition: all 0.35s cubic-bezier(0.16, 1, 0.3, 1);
    transform-origin: center;
  }

  /* Animated X state */
  .mobile-menu-btn.open span:nth-child(1) {
    transform: translateY(7px) rotate(45deg);
  }
  .mobile-menu-btn.open span:nth-child(2) {
    opacity: 0;
    transform: scaleX(0);
  }
  .mobile-menu-btn.open span:nth-child(3) {
    transform: translateY(-7px) rotate(-45deg);
  }

  @media (max-width: 991px) {
    .mobile-menu-btn { display: flex; }
    .desktop-nav { display: none !important; }
  }

  /* ===== Mobile Off-Canvas Panel ===== */
  .mobile-nav-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.6);
    backdrop-filter: blur(4px);
    z-index: 1049;
    opacity: 0;
    visibility: hidden;
    transition: all 0.35s ease;
  }

  .mobile-nav-overlay.active {
    opacity: 1;
    visibility: visible;
  }

  .mobile-nav-panel {
    position: fixed;
    top: 0;
    left: 0;
    width: 300px;
    max-width: 85vw;
    height: 100%;
    background: #0a0a0a;
    border-right: 1px solid rgba(255, 255, 255, 0.07);
    z-index: 1050;
    transform: translateX(-100%);
    transition: transform 0.4s cubic-bezier(0.16, 1, 0.3, 1);
    display: flex;
    flex-direction: column;
    padding: 0;
    overflow-y: auto;
  }

  .mobile-nav-panel.active {
    transform: translateX(0);
  }

  /* Panel Header */
  .mobile-nav-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 20px 24px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.06);
  }

  .mobile-nav-header img {
    height: 30px;
  }

  .mobile-nav-close {
    width: 36px;
    height: 36px;
    border-radius: 8px;
    background: rgba(255, 255, 255, 0.06);
    border: 1px solid rgba(255, 255, 255, 0.1);
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    color: #fff;
    font-size: 16px;
    transition: background 0.2s;
  }

  .mobile-nav-close:hover {
    background: rgba(255, 255, 255, 0.12);
  }

  /* Panel Links */
  .mobile-nav-links {
    flex: 1;
    padding: 16px 16px;
    display: flex;
    flex-direction: column;
    gap: 4px;
  }

  .mobile-nav-link {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 14px 16px;
    border-radius: 12px;
    color: rgba(255, 255, 255, 0.75) !important;
    font-size: 15px;
    font-weight: 500;
    font-family: 'Inter', sans-serif;
    text-decoration: none;
    transition: all 0.25s ease;
    border: 1px solid transparent;
  }

  .mobile-nav-link:hover {
    background: rgba(255, 255, 255, 0.05);
    color: #ffffff !important;
    border-color: rgba(255, 255, 255, 0.06);
    text-decoration: none;
  }

  .mobile-nav-link.active-link {
    background: rgba(16, 185, 129, 0.1);
    color: #10B981 !important;
    border-color: rgba(16, 185, 129, 0.2);
  }

  .mobile-nav-link .nav-icon {
    width: 34px;
    height: 34px;
    border-radius: 8px;
    background: rgba(255, 255, 255, 0.05);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    flex-shrink: 0;
    transition: background 0.25s ease;
  }

  .mobile-nav-link.active-link .nav-icon {
    background: rgba(16, 185, 129, 0.15);
    color: #10B981;
  }

  .mobile-nav-divider {
    height: 1px;
    background: rgba(255, 255, 255, 0.05);
    margin: 8px 0;
  }

  /* Panel Footer CTA */
  .mobile-nav-footer {
    padding: 20px 16px;
    border-top: 1px solid rgba(255, 255, 255, 0.06);
  }

  .mobile-nav-cta {
    display: block;
    text-align: center;
    background: #10B981;
    color: #ffffff !important;
    padding: 14px;
    border-radius: 12px;
    font-weight: 600;
    font-size: 14px;
    font-family: 'Inter', sans-serif;
    text-decoration: none;
    transition: all 0.3s ease;
  }

  .mobile-nav-cta:hover {
    background: #059669;
    text-decoration: none;
    transform: translateY(-1px);
    box-shadow: 0 8px 20px rgba(16, 185, 129, 0.25);
  }

  /* Navbar wide layout */
  .premium-nav .container-fluid {
    max-width: 1600px;
  }

  .premium-nav .navbar-brand {
    padding-left: 0;
    margin-right: 32px;
  }

  /* ===== Desktop Dropdown ===== */
  .premium-nav .nav-item.dropdown {
    position: relative;
  }

  .premium-nav .dropdown-toggle {
    position: relative;
    padding-right: 28px !important;
  }

  .premium-nav .dropdown-toggle::before {
    content: '\f107';
    font-family: 'Font Awesome 5 Free';
    font-weight: 900;
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    font-size: 11px;
    transition: transform 0.3s ease;
  }

  .premium-nav .dropdown-menu {
    position: absolute;
    top: calc(100% - 8px);
    left: 50%;
    transform: translateX(-50%) translateY(8px);
    min-width: 260px;
    background: rgba(10, 10, 10, 0.95);
    backdrop-filter: blur(20px) saturate(180%);
    -webkit-backdrop-filter: blur(20px) saturate(180%);
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 16px;
    padding: 18px 10px 10px;
    opacity: 0;
    visibility: hidden;
    transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4);
    display: flex;
    flex-direction: column;
    gap: 4px;
    pointer-events: none;
  }

  .premium-nav .nav-item.dropdown:hover .dropdown-menu {
    opacity: 1;
    visibility: visible;
    transform: translateX(-50%) translateY(0);
    pointer-events: auto;
  }

  .premium-nav .nav-item.dropdown:hover .dropdown-toggle::before {
    transform: translateY(-50%) rotate(180deg);
  }

  .premium-nav .dropdown-menu .dropdown-item {
    display: block;
    padding: 10px 14px;
    border-radius: 10px;
    color: rgba(255, 255, 255, 0.7) !important;
    font-size: 13px;
    font-weight: 500;
    font-family: 'Inter', sans-serif;
    text-decoration: none;
    transition: all 0.2s ease;
  }

  .premium-nav .dropdown-menu .dropdown-item:hover {
    background: rgba(255, 255, 255, 0.06);
    color: #ffffff !important;
    text-decoration: none;
  }

  .premium-nav .dropdown-menu .dropdown-item.active {
    background: rgba(16, 185, 129, 0.12);
    color: #10B981 !important;
  }
  /* ===== Mobile Sub-menu ===== */
  .mobile-submenu {
    max-height: 0;
    overflow: hidden;
    transition: max-height 0.4s cubic-bezier(0.16, 1, 0.3, 1);
  }

  .mobile-submenu.expanded {
    max-height: 500px;
    display: flex;
    flex-direction: column;
    gap: 4px;
    padding-bottom: 8px;
  }

  .mobile-submenu a {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 16px 10px 64px;
    border-radius: 10px;
    color: rgba(255, 255, 255, 0.55) !important;
    font-size: 13px;
    font-weight: 500;
    font-family: 'Inter', sans-serif;
    text-decoration: none;
    transition: all 0.25s ease;
  }

  .mobile-submenu a:hover {
    color: rgba(255, 255, 255, 0.85) !important;
    background: rgba(255, 255, 255, 0.03);
    text-decoration: none;
  }

  .mobile-submenu a.active-link {
    color: #10B981 !important;
    background: rgba(16, 185, 129, 0.08);
  }

  .mobile-submenu a .sub-dot {
    width: 5px;
    height: 5px;
    border-radius: 50%;
    background: currentColor;
    flex-shrink: 0;
  }

  .mobile-services-toggle {
    cursor: pointer;
    position: relative;
  }

  .mobile-services-toggle .toggle-arrow {
    margin-left: auto;
    font-size: 11px;
    transition: transform 0.3s ease;
  }

  .mobile-services-toggle.expanded .toggle-arrow {
    transform: rotate(180deg);
  }
</style>

<!-- Mobile Off-Canvas Overlay -->
<div class="mobile-nav-overlay" id="mobileOverlay"></div>

<!-- Mobile Off-Canvas Panel -->
<div class="mobile-nav-panel" id="mobilePanel">
  <div class="mobile-nav-header">
    <a href="index.php"><img src="assets/img/FEIT.png" alt="FEIT Solutions"></a>
    <div class="mobile-nav-close" id="mobileClose">
      <i class="fas fa-times"></i>
    </div>
  </div>

  <nav class="mobile-nav-links">
    <?php
      $current_page = basename($_SERVER['PHP_SELF']);
    ?>
    <a href="index.php" class="mobile-nav-link <?php echo ($current_page == 'index.php') ? 'active-link' : ''; ?>">
      <span class="nav-icon"><i class="fas fa-home"></i></span>
      Home
    </a>
    <a href="about.php" class="mobile-nav-link <?php echo ($current_page == 'about.php') ? 'active-link' : ''; ?>">
      <span class="nav-icon"><i class="fas fa-info-circle"></i></span>
      About
    </a>
    <a href="services.php" class="mobile-nav-link mobile-services-toggle <?php echo ($current_page == 'services.php' || $current_page == 'cloud-services.php' || $current_page == 'managed-it-support.php' || $current_page == 'network-infrastructure.php' || $current_page == 'custom-software.php' || $current_page == 'security-solutions.php') ? 'active-link' : ''; ?>">
      <span class="nav-icon"><i class="fas fa-cogs"></i></span>
      Services
      <span class="toggle-arrow"><i class="fas fa-chevron-down"></i></span>
    </a>
    <div class="mobile-submenu <?php echo ($current_page == 'cloud-services.php' || $current_page == 'managed-it-support.php' || $current_page == 'network-infrastructure.php' || $current_page == 'custom-software.php' || $current_page == 'security-solutions.php') ? 'expanded' : ''; ?>">
      <a href="cloud-services.php" class="<?php echo ($current_page == 'cloud-services.php') ? 'active-link' : ''; ?>"><span class="sub-dot"></span>Cloud Services</a>
      <a href="managed-it-support.php" class="<?php echo ($current_page == 'managed-it-support.php') ? 'active-link' : ''; ?>"><span class="sub-dot"></span>Managed IT Support</a>
      <a href="network-infrastructure.php" class="<?php echo ($current_page == 'network-infrastructure.php') ? 'active-link' : ''; ?>"><span class="sub-dot"></span>Network & Infrastructure</a>
      <a href="custom-software.php" class="<?php echo ($current_page == 'custom-software.php') ? 'active-link' : ''; ?>"><span class="sub-dot"></span>Custom Software</a>
      <a href="security-solutions.php" class="<?php echo ($current_page == 'security-solutions.php') ? 'active-link' : ''; ?>"><span class="sub-dot"></span>Security Solutions</a>
    </div>
    <a href="products.php" class="mobile-nav-link mobile-products-toggle <?php echo ($current_page == 'products.php' || $current_page == 'courier_management.php' || $current_page == 'order_management.php') ? 'active-link' : ''; ?>">
      <span class="nav-icon"><i class="fas fa-box-open"></i></span>
      Products
      <span class="toggle-arrow"><i class="fas fa-chevron-down"></i></span>
    </a>
    <div class="mobile-submenu <?php echo ($current_page == 'courier_management.php' || $current_page == 'order_management.php') ? 'expanded' : ''; ?>">
      <a href="courier_management.php" class="<?php echo ($current_page == 'courier_management.php') ? 'active-link' : ''; ?>"><span class="sub-dot"></span>Courier Management</a>
      <a href="order_management.php" class="<?php echo ($current_page == 'order_management.php') ? 'active-link' : ''; ?>"><span class="sub-dot"></span>Order Management</a>
    </div>
    <div class="mobile-nav-divider"></div>
    <a href="contact.php" class="mobile-nav-link <?php echo ($current_page == 'contact.php') ? 'active-link' : ''; ?>">
      <span class="nav-icon"><i class="fas fa-envelope"></i></span>
      Contact
    </a>
  </nav>
</div>

<!-- Navbar Start -->
<?php if (!isset($current_page)) $current_page = basename($_SERVER['PHP_SELF']); ?>
<nav class="navbar navbar-expand-lg fixed-top scrolling-navbar premium-nav">
  <div class="container-fluid px-3 px-lg-5">
    <a href="index.php" class="navbar-brand">
      <img src="assets/img/FEIT.png" alt="FEIT">
    </a>

    <!-- Mobile Hamburger -->
    <button class="mobile-menu-btn" id="mobileMenuBtn" aria-label="Open menu">
      <span></span>
      <span></span>
      <span></span>
    </button>

    <!-- Desktop Nav -->
    <div class="collapse navbar-collapse desktop-nav" id="navbarCollapse">
      <ul class="navbar-nav ml-auto">
        <li class="nav-item <?php echo ($current_page == 'index.php') ? 'active' : ''; ?>">
          <a class="nav-link" href="index.php">Home</a>
        </li>
        <li class="nav-item <?php echo ($current_page == 'about.php') ? 'active' : ''; ?>">
          <a class="nav-link" href="about.php">About</a>
        </li>
        <li class="nav-item dropdown <?php echo ($current_page == 'services.php' || $current_page == 'cloud-services.php' || $current_page == 'managed-it-support.php' || $current_page == 'network-infrastructure.php' || $current_page == 'custom-software.php' || $current_page == 'security-solutions.php') ? 'active' : ''; ?>">
          <a class="nav-link dropdown-toggle" href="services.php">Services</a>
          <div class="dropdown-menu">
            <a href="cloud-services.php" class="dropdown-item <?php echo ($current_page == 'cloud-services.php') ? 'active' : ''; ?>">
              Cloud Services
            </a>
            <a href="managed-it-support.php" class="dropdown-item <?php echo ($current_page == 'managed-it-support.php') ? 'active' : ''; ?>">
              Managed IT Support
            </a>
            <a href="network-infrastructure.php" class="dropdown-item <?php echo ($current_page == 'network-infrastructure.php') ? 'active' : ''; ?>">
              Network & Infrastructure
            </a>
            <a href="custom-software.php" class="dropdown-item <?php echo ($current_page == 'custom-software.php') ? 'active' : ''; ?>">
              Custom Software
            </a>
            <a href="security-solutions.php" class="dropdown-item <?php echo ($current_page == 'security-solutions.php') ? 'active' : ''; ?>">
              Security Solutions
            </a>
          </div>
        </li>
        <li class="nav-item dropdown <?php echo ($current_page == 'products.php' || $current_page == 'courier_management.php' || $current_page == 'order_management.php') ? 'active' : ''; ?>">
          <a class="nav-link dropdown-toggle" href="products.php">Products</a>
          <div class="dropdown-menu">
            <a href="courier_management.php" class="dropdown-item <?php echo ($current_page == 'courier_management.php') ? 'active' : ''; ?>">
              Courier Management
            </a>
            <a href="order_management.php" class="dropdown-item <?php echo ($current_page == 'order_management.php') ? 'active' : ''; ?>">
              Order Management
            </a>
          </div>
        </li>
        <li class="nav-item <?php echo ($current_page == 'contact.php') ? 'active' : ''; ?>">
          <a class="nav-link" href="contact.php">Contact</a>
        </li>
      </ul>
    </div>
  </div>
</nav>
<!-- Navbar End -->

<script>
(function () {
  function init() {
    var btn     = document.getElementById('mobileMenuBtn');
    var panel   = document.getElementById('mobilePanel');
    var overlay = document.getElementById('mobileOverlay');
    var closeBtn = document.getElementById('mobileClose');

    if (!btn || !panel || !overlay) return;

    function openMenu() {
      panel.classList.add('active');
      overlay.classList.add('active');
      btn.classList.add('open');
      document.body.style.overflow = 'hidden';
    }

    function closeMenu() {
      panel.classList.remove('active');
      overlay.classList.remove('active');
      btn.classList.remove('open');
      document.body.style.overflow = '';
    }

    btn.addEventListener('click', function () {
      panel.classList.contains('active') ? closeMenu() : openMenu();
    });

    if (closeBtn) closeBtn.addEventListener('click', closeMenu);
    overlay.addEventListener('click', closeMenu);

    var servicesToggle = document.querySelector('.mobile-services-toggle');
    if (servicesToggle) {
      servicesToggle.addEventListener('click', function (e) {
        e.preventDefault();
        var submenu = this.nextElementSibling;
        this.classList.toggle('expanded');
        submenu.classList.toggle('expanded');
      });
    }

    var productsToggle = document.querySelector('.mobile-products-toggle');
    if (productsToggle) {
      productsToggle.addEventListener('click', function (e) {
        e.preventDefault();
        var submenu = this.nextElementSibling;
        this.classList.toggle('expanded');
        submenu.classList.toggle('expanded');
      });
    }

    // Removed conflicting scrolling-navbar toggle since main.js handles top-nav-collapse
  }

  // Run on DOMContentLoaded or immediately if already loaded
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
</script>