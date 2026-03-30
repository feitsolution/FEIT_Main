<?php include 'loader.php'; ?>
<!-- Navbar Start -->
<?php
  $current_page = basename($_SERVER['PHP_SELF']);
  $services_pages = ['erp.php', 'mobile_dev.php', 'web_dev.php', 'seo.php', 'consultancy.php', 'services.php'];
  $products_pages = ['courier_management.php', 'order_management.php'];
?>
<nav class="navbar navbar-expand-lg fixed-top scrolling-navbar">
  <div class="container">
    <a href="index.php" class="navbar-brand">
      <img src="assets/img/FEIT.png" alt="FEIT">
    </a>
    <button class="navbar-toggler collapsed" type="button" data-toggle="collapse" data-target="#navbarCollapse" aria-controls="navbarCollapse" aria-expanded="false" aria-label="Toggle navigation">
      <span class="toggler-icon"></span>
      <span class="toggler-icon"></span>
      <span class="toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarCollapse">
      <ul class="navbar-nav ml-auto">
        <li class="nav-item <?php echo ($current_page == 'home.php' || $current_page == 'index.php') ? 'active' : ''; ?>">
          <a class="nav-link" href="home.php">Home</a>
        </li>
        <li class="nav-item <?php echo ($current_page == 'about.php') ? 'active' : ''; ?>">
          <a class="nav-link" href="about.php">About</a>
        </li>
        <li class="nav-item dropdown <?php echo in_array($current_page, $services_pages) ? 'active' : ''; ?>">
          <a class="nav-link dropdown-toggle" href="#" id="servicesDropdown">Services</a>
          <div class="dropdown-menu mega-style" aria-labelledby="servicesDropdown">
            <a class="dropdown-item d-flex align-items-center" href="erp.php">
              <div class="icon-wrap"><i class="fas fa-microchip"></i></div>
              <div class="text-wrap">
                <span class="d-title">ERP Software</span>
                <p class="d-desc">Custom business management systems.</p>
              </div>
            </a>
            <a class="dropdown-item d-flex align-items-center" href="mobile_dev.php">
              <div class="icon-wrap"><i class="fas fa-mobile-alt"></i></div>
              <div class="text-wrap">
                <span class="d-title">Mobile Apps</span>
                <p class="d-desc">iOS & Android development.</p>
              </div>
            </a>
            <a class="dropdown-item d-flex align-items-center" href="web_dev.php">
              <div class="icon-wrap"><i class="fas fa-code"></i></div>
              <div class="text-wrap">
                <span class="d-title">Web Development</span>
                <p class="d-desc">Modern responsive websites.</p>
              </div>
            </a>
            <a class="dropdown-item d-flex align-items-center" href="seo.php">
              <div class="icon-wrap"><i class="fas fa-search"></i></div>
              <div class="text-wrap">
                <span class="d-title">SEO Services</span>
                <p class="d-desc">Search engine optimization.</p>
              </div>
            </a>
            <a class="dropdown-item d-flex align-items-center" href="consultancy.php">
              <div class="icon-wrap"><i class="fas fa-briefcase"></i></div>
              <div class="text-wrap">
                <span class="d-title">IT Consultancy</span>
                <p class="d-desc">Strategic technology advice.</p>
              </div>
            </a>
          </div>
        </li>
        <li class="nav-item dropdown <?php echo in_array($current_page, $products_pages) ? 'active' : ''; ?>">
          <a class="nav-link dropdown-toggle" href="#" id="productsDropdown">Products</a>
          <div class="dropdown-menu mega-style" aria-labelledby="productsDropdown">
            <a class="dropdown-item d-flex align-items-center" href="courier_management.php">
              <div class="icon-wrap"><i class="fas fa-truck"></i></div>
              <div class="text-wrap">
                <span class="d-title">Courier System</span>
                <p class="d-desc">End-to-end logistics management.</p>
              </div>
            </a>
            <a class="dropdown-item d-flex align-items-center" href="order_management.php">
              <div class="icon-wrap"><i class="fas fa-box"></i></div>
              <div class="text-wrap">
                <span class="d-title">Order Management</span>
                <p class="d-desc">Streamlined sales and ordering.</p>
              </div>
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
$(document).ready(function() {
  // Side Menu Overlay
  if ($('.navbar-overlay').length === 0) {
    $('body').append('<div class="navbar-overlay" style="position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:998;display:none;pointer-events:auto;cursor:pointer;"></div>');
  }

  $('.navbar-toggler').on('click', function() {
    if ($(window).width() <= 991) {
      setTimeout(function() {
        if ($('#navbarCollapse').hasClass('show')) {
          $('.navbar-overlay').fadeIn(300);
          $('body').addClass('menu-open').css('overflow', 'hidden');
        } else {
          $('.navbar-overlay').fadeOut(200);
          $('body').removeClass('menu-open').css('overflow', '');
        }
      }, 50);
    }
  });

  $('.navbar-overlay').on('click', function() {
    closeMobileMenu();
  });

  // Close mobile menu when clicking outside
  $(document).on('click', function(e) {
    if ($(window).width() <= 991) {
      var $menu = $('#navbarCollapse');
      var $toggler = $('.navbar-toggler');
      if ($menu.hasClass('show') && !$menu.is(e.target) && $menu.has(e.target).length === 0 && !$toggler.is(e.target) && $toggler.has(e.target).length === 0) {
        closeMobileMenu();
      }
    }
  });

  function closeMobileMenu() {
    $('#navbarCollapse').removeClass('show');
    $('.navbar-toggler').addClass('collapsed').attr('aria-expanded', 'false');
    $('.navbar-overlay').fadeOut(200);
    $('body').removeClass('menu-open').css('overflow', '');
    // Close any open dropdowns
    $('.dropdown-menu').slideUp(200);
    $('.dropdown').removeClass('show');
  }

  // Enhanced drop-down interaction for mobile
  $('.navbar .dropdown-toggle').on('click', function(e) {
    if ($(window).width() <= 991) {
      e.preventDefault();
      var $parent = $(this).closest('.dropdown');
      var $el = $(this).next('.dropdown-menu');
      var isVisible = $el.is(':visible');
      
      // Close all other dropdowns
      $('.dropdown-menu').not($el).slideUp(300);
      $('.dropdown').not($parent).removeClass('show');
      
      if (!isVisible) {
        $el.slideDown(300);
        $parent.addClass('show');
      } else {
        $el.slideUp(300);
        $parent.removeClass('show');
      }
    } else {
      // On desktop, click-to-navigate for parents if href is defined
      if ($(this).attr('href') !== '#' && $(this).attr('href') !== 'javascript:void(0)') {
        window.location.href = $(this).attr('href');
      }
    }
  });

  // Highlight active link transition
  $(window).on('scroll', function() {
    if ($(window).scrollTop() > 50) {
      $('.navbar').addClass('scrolling-navbar');
    } else {
      $('.navbar').removeClass('scrolling-navbar');
    }
  });
});
</script>