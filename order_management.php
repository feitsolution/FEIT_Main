<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>Order Management System | FE IT Solutions</title>
  <meta name="description" content="Discover FE IT Solutions' Order Management System - a smart platform for handling customer orders with real-time tracking and seamless integration.">
  <?php include 'header.php'; ?>
  <style>
    body {
      font-family: 'Inter', sans-serif;
      background-color: #f9fafb;
    }

    /* Premium Hero Styling */
    .services-hero {
      position: relative;
      overflow: hidden;
      height: 50vh;
      min-height: 400px;
      display: flex;
      align-items: center;
      background-color: #050505;
      font-family: 'Inter', sans-serif;
      margin-top: 0;
    }
    
    .services-hero-img {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      object-fit: cover;
      opacity: 0.35;
      filter: grayscale(20%) contrast(110%);
      z-index: 0;
    }

    .services-overlay {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: radial-gradient(circle at center, rgba(5, 5, 5, 0.4) 0%, rgba(5, 5, 5, 0.95) 100%);
      z-index: 1;
    }

    .services-ambient-glow {
      position: absolute;
      top: 50%;
      left: 50%;
      width: 60vw;
      height: 60vw;
      transform: translate(-50%, -50%);
      background: radial-gradient(circle, rgba(16, 185, 129, 0.08) 0%, rgba(0, 0, 0, 0) 70%);
      z-index: 1;
      pointer-events: none;
    }

    .services-content-wrapper {
      position: relative;
      z-index: 2;
      width: 100%;
      padding: 0 24px;
      margin-top: 40px;
      text-align: center;
    }

    .services-title {
      font-size: clamp(38px, 5vw, 64px);
      font-weight: 600;
      line-height: 1.1;
      letter-spacing: -0.02em;
      color: #ffffff;
      margin: 0 0 16px;
      animation: fadeInUp 0.8s ease-out backwards;
    }

    .services-subtitle {
      font-size: clamp(17px, 2vw, 21px);
      font-weight: 400;
      line-height: 1.6;
      color: #d1d5db;
      max-width: 750px;
      margin: 0 auto;
      letter-spacing: -0.01em;
      animation: fadeInUp 0.8s ease-out 0.2s backwards;
    }
    
    @keyframes fadeInUp {
      from { opacity: 0; transform: translateY(30px); }
      to { opacity: 1; transform: translateY(0); }
    }

    /* Common Section Classes */
    .section-padding { padding: 100px 0; }
    .premium-label {
      display: inline-block;
      padding: 6px 16px;
      background: #f3f4f6;
      color: #4b5563;
      font-size: 13px;
      font-weight: 600;
      border-radius: 100px;
      letter-spacing: 0.5px;
      margin-bottom: 24px;
      text-transform: uppercase;
    }
    .section-title {
      font-size: clamp(32px, 4vw, 42px);
      font-weight: 700;
      letter-spacing: -0.02em;
      color: #111827;
      margin-bottom: 24px;
      line-height: 1.2;
    }

    /* Service Block Layout */
    .service-block {
      background: #ffffff;
      border: 1px solid #f3f4f6;
      border-radius: 24px;
      overflow: hidden;
      margin-bottom: 60px;
      box-shadow: 0 20px 40px -10px rgba(0,0,0,0.05);
    }
    .service-block-content {
      padding: 60px 50px;
      display: flex;
      flex-direction: column;
      justify-content: center;
      height: 100%;
    }
    .service-icon-lg {
      width: 80px;
      height: 80px;
      background: #f0fdf4;
      border-radius: 20px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 32px;
      color: #10B981;
      margin-bottom: 30px;
    }
    .service-block-title {
      font-size: 28px;
      font-weight: 700;
      color: #111827;
      margin-bottom: 16px;
      letter-spacing: -0.01em;
    }
    .service-block-desc {
      font-size: 16px;
      line-height: 1.7;
      color: #6b7280;
      margin-bottom: 30px;
    }
    .service-feature-list {
      list-style: none;
      padding: 0;
      margin: 0;
    }
    .service-feature-list li {
      position: relative;
      padding-left: 32px;
      margin-bottom: 14px;
      font-size: 15px;
      color: #4b5563;
    }
    .service-feature-list li::before {
      content: "\f00c";
      font-family: "Font Awesome 5 Free";
      font-weight: 900;
      position: absolute;
      left: 0;
      top: 2px;
      color: #10B981;
      font-size: 14px;
    }
    .service-image-container {
      height: 100%;
      min-height: 400px;
      background-size: cover;
      background-position: center;
    }

    /* Reverse layout for alternating blocks */
    .service-block.reverse .row {
      flex-direction: row-reverse;
    }

    @media (max-width: 991px) {
      .service-block-content { padding: 40px 30px; }
      .service-image-container { min-height: 300px; }
    }
    
    /* Global CTA Styles */
    .btn-common {
      background: #111827;
      color: #ffffff !important;
      padding: 14px 32px;
      border-radius: 100px;
      font-weight: 600;
      font-size: 15px;
      transition: all 0.3s;
      border: none;
      display: inline-block;
    }
    .btn-common:hover {
      background: #10B981;
      transform: translateY(-2px);
      box-shadow: 0 10px 20px rgba(16, 185, 129, 0.2);
    }

    /* Product Gallery Carousel Styling */
    .product-gallery-section {
      background-color: #f1f5f9;
      padding: 100px 0;
    }
    #product-gallery {
      padding: 40px 0;
    }
    #product-gallery .item {
      padding: 10px;
      transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
      opacity: 0.7;
      transform: scale(0.85);
      border-radius: 16px;
      overflow: hidden;
    }
    #product-gallery .owl-item.center .item {
      opacity: 1;
      transform: scale(1.05);
      border: 4px solid #10B981;
      box-shadow: 0 25px 50px -12px rgba(16, 185, 129, 0.25);
    }
    #product-gallery .item img {
      width: 100%;
      height: auto;
      border-radius: 12px;
      display: block;
    }
    /* Dots Styling */
    #product-gallery .owl-dots {
      text-align: center;
      margin-top: 50px !important;
      display: flex;
      justify-content: center;
      align-items: center;
      gap: 12px;
    }
    #product-gallery .owl-dot span {
      width: 10px;
      height: 10px;
      background: #e5e7eb !important;
      margin: 0 !important;
      display: block;
      transition: all 0.3s ease;
      border-radius: 50%;
    }
    #product-gallery .owl-dot.active span {
      background: #10B981 !important;
      transform: scale(1.4);
    }

    /* Custom Carousel Navigation Styles */
    #product-gallery {
      position: relative;
    }
    #product-gallery .owl-nav {
      position: absolute;
      top: 50%;
      width: 100%;
      left: 0;
      display: flex !important;
      justify-content: space-between;
      transform: translateY(-50%);
      pointer-events: none;
      z-index: 10;
    }
    #product-gallery .owl-nav button {
      width: 54px;
      height: 54px;
      background: #ffffff !important;
      border-radius: 50% !important;
      display: flex !important;
      align-items: center !important;
      justify-content: center !important;
      color: #111827 !important;
      font-size: 20px !important;
      box-shadow: 0 10px 30px rgba(0,0,0,0.12) !important;
      pointer-events: auto;
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
      opacity: 1 !important;
      border: 1px solid #f3f4f6 !important;
    }
    #product-gallery .owl-nav button:hover {
      background: #10B981 !important;
      color: #ffffff !important;
      transform: scale(1.1);
      box-shadow: 0 15px 35px rgba(16, 185, 129, 0.3) !important;
      border-color: #10B981 !important;
    }
    #product-gallery .owl-nav .owl-prev {
      margin-left: -27px !important;
    }
    #product-gallery .owl-nav .owl-next {
      margin-right: -27px !important;
    }
    @media (max-width: 1200px) {
      #product-gallery .owl-nav .owl-prev { margin-left: 0 !important; }
      #product-gallery .owl-nav .owl-next { margin-right: 0 !important; }
      #product-gallery .owl-nav button { width: 44px; height: 44px; font-size: 16px; }
    }

    /* Key Features Styling */
    .features-section {
      background-color: #ffffff;
      padding: 100px 0;
    }
    .feature-item {
      background: #ffffff;
      padding: 50px 30px;
      border-radius: 20px;
      text-align: center;
      transition: all 0.3s ease;
      height: 100%;
      border: 1px solid #e5e7eb;
      box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
    }
    .feature-item:hover {
      transform: translateY(-10px);
      box-shadow: 0 20px 40px rgba(0, 0, 0, 0.05);
      border-color: #10B981;
    }
    .feature-icon-wrapper {
      width: 70px;
      height: 70px;
      background: #f0fdf4;
      color: #10B981;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 28px;
      margin: 0 auto 30px;
      transition: all 0.3s ease;
    }
    .feature-item:hover .feature-icon-wrapper {
      background: #10B981;
      color: #ffffff;
    }
    .feature-title {
      font-size: 20px;
      font-weight: 700;
      color: #111827;
      margin-bottom: 15px;
    }
    .feature-text {
      font-size: 15px;
      line-height: 1.6;
      color: #4b5563;
      margin: 0;
    }

    /* Redesigned Why Choose Styling */
    .why-choose-section {
      background-color: #f2f2f2;
      padding: 100px 0;
    }
    .why-choose-content {
      padding-right: 40px;
    }
    .why-choose-content h2 {
      font-size: 38px;
      font-weight: 700;
      color: #111827;
      margin-bottom: 24px;
      line-height: 1.2;
    }
    .why-choose-content p {
      font-size: 16px;
      line-height: 1.7;
      color: #4b5563;
      margin-bottom: 30px;
    }
    .btn-learn-more {
      background: #10B981;
      color: #ffffff !important;
      padding: 12px 28px;
      border-radius: 100px;
      font-weight: 600;
      font-size: 15px;
      transition: all 0.3s;
      border: none;
      display: inline-flex;
      align-items: center;
      gap: 10px;
    }
    .btn-learn-more:hover {
      background: #059669;
      transform: translateX(5px);
      box-shadow: 0 10px 20px rgba(16, 185, 129, 0.2);
    }
    .why-choose-card {
      background: #ffffff;
      border: 1px solid #e5e7eb;
      border-radius: 12px;
      padding: 40px 20px;
      text-align: center;
      height: 100%;
      transition: all 0.3s ease;
      box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
    }
    .why-choose-card:hover {
      border-color: #10B981;
      transform: translateY(-5px);
      box-shadow: 0 15px 30px rgba(0,0,0,0.05);
    }
    .why-choose-icon {
      width: 64px;
      height: 64px;
      background: #f0fdf4;
      color: #10B981;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 24px;
      margin: 0 auto 20px;
    }
    .why-choose-card h3 {
      font-size: 18px;
      font-weight: 700;
      color: #111827;
      margin-bottom: 15px;
    }
    .why-choose-card p {
      font-size: 14px;
      line-height: 1.6;
      color: #4b5563;
      margin: 0;
    }
  </style>
</head>
<body>

  <header id="header-wrap">
    <?php include 'navbar.php'; ?>
    <div class="services-hero">
      <img src="assets/img/pr.png" alt="Order Management System" class="services-hero-img">
      <div class="services-ambient-glow"></div>
      <div class="services-overlay"></div>
      <div class="services-content-wrapper">
        <div class="container">
          <h1 class="services-title">Order Management System</h1>
          <p class="services-subtitle">Order Management System is a smart platform that makes handling customer orders easy and efficient.</p>
        </div>
      </div>
    </div>
  </header>

  <section class="why-choose-section">
    <div class="container">
      <div class="row align-items-center">
        <!-- Left Content -->
        <div class="col-lg-4 wow fadeInLeft">
          <div class="why-choose-content">
            <h2>Why Choose Our Order Management System?</h2>
            <p>Our Order Management System simplifies your business operations by streamlining order processing, inventory management, and customer communication all in one platform. With its user-friendly interface, real-time updates, and automation features, it helps you save time, reduce errors, and enhance customer satisfaction. Choose our OMS to optimize your workflows and drive business growth effortlessly.</p>
            <a href="contact.php" class="btn-learn-more">Learn More <i class="fas fa-chevron-right"></i></a>
          </div>
        </div>
        <!-- Right Cards -->
        <div class="col-lg-8">
          <div class="row g-3">
            <div class="col-md-4 wow fadeInUp" data-wow-delay="0.1s">
              <div class="why-choose-card">
                <div class="why-choose-icon"><i class="fas fa-sliders-h"></i></div>
                <h3>Product Customization</h3>
                <p>Tailor our OMS to meet your unique business needs with flexible options for features, workflows, and integrations, ensuring a perfect fit for your operations.</p>
              </div>
            </div>
            <div class="col-md-4 wow fadeInUp" data-wow-delay="0.2s">
              <div class="why-choose-card">
                <div class="why-choose-icon"><i class="fas fa-headset"></i></div>
                <h3>Reliable Product Support</h3>
                <p>Enjoy peace of mind with dedicated customer support, available to assist with any queries or challenges, ensuring smooth and uninterrupted operations.</p>
              </div>
            </div>
            <div class="col-md-4 wow fadeInUp" data-wow-delay="0.3s">
              <div class="why-choose-card">
                <div class="why-choose-icon"><i class="fas fa-tags"></i></div>
                <h3>Affordable Pricing</h3>
                <p>Access premium features at a cost-effective price, making our OMS a budget-friendly solution without compromising on quality or performance.</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Key Features Section -->
  <section class="features-section">
    <div class="container">
      <div class="section-header text-center mb-5 pb-3">
        <h2 class="section-title">Key Features of FE IT SOLUTIONS OMS</h2>
        <p class="mx-auto text-muted" style="max-width: 800px;">Our Order Management System is a powerful platform designed to enhance business operations with a wide range of features:</p>
      </div>
      <div class="row g-4">
        <div class="col-lg-4 col-md-6 wow fadeInUp" data-wow-delay="0.1s">
          <div class="feature-item">
            <div class="feature-icon-wrapper"><i class="fas fa-shopping-cart"></i></div>
            <h3 class="feature-title">Order Management</h3>
            <p class="feature-text">Provides a comprehensive solution for managing orders from start to finish, allowing businesses to track customer orders, process shipments, and handle returns efficiently.</p>
          </div>
        </div>
        <div class="col-lg-4 col-md-6 wow fadeInUp" data-wow-delay="0.2s">
          <div class="feature-item">
            <div class="feature-icon-wrapper"><i class="fas fa-boxes"></i></div>
            <h3 class="feature-title">Inventory Tracking</h3>
            <p class="feature-text">Real-time inventory management ensures stock levels are always updated, preventing overselling and helping businesses manage their products with accuracy.</p>
          </div>
        </div>
        <div class="col-lg-4 col-md-6 wow fadeInUp" data-wow-delay="0.3s">
          <div class="feature-item">
            <div class="feature-icon-wrapper"><i class="fas fa-users"></i></div>
            <h3 class="feature-title">Customer Management</h3>
            <p class="feature-text">Enables businesses to store and manage detailed customer information, helping to personalize communication and improve customer relationships.</p>
          </div>
        </div>
        <div class="col-lg-4 col-md-6 wow fadeInUp" data-wow-delay="0.4s">
          <div class="feature-item">
            <div class="feature-icon-wrapper"><i class="fas fa-credit-card"></i></div>
            <h3 class="feature-title">Payment Integration</h3>
            <p class="feature-text">Seamless integration with various payment gateways allows businesses to accept multiple payment methods, ensuring a smooth and secure checkout process.</p>
          </div>
        </div>
        <div class="col-lg-4 col-md-6 wow fadeInUp" data-wow-delay="0.5s">
          <div class="feature-item">
            <div class="feature-icon-wrapper"><i class="fas fa-chart-pie"></i></div>
            <h3 class="feature-title">Customizable Reporting</h3>
            <p class="feature-text">Offers powerful reporting tools to analyze sales, inventory, and customer data, providing valuable insights for better decision-making.</p>
          </div>
        </div>
        <div class="col-lg-4 col-md-6 wow fadeInUp" data-wow-delay="0.6s">
          <div class="feature-item">
            <div class="feature-icon-wrapper"><i class="fas fa-network-wired"></i></div>
            <h3 class="feature-title">Multi-Channel Support</h3>
            <p class="feature-text">Supports integration with multiple sales channels, allowing businesses to manage orders from various platforms from one central system.</p>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Product Gallery Section -->
  <section class="product-gallery-section section-padding bg-light">
    <div class="container">
      <div class="section-header text-center mb-5 pb-3">
        <h2 class="section-title">Product Gallery</h2>
        <p class="mx-auto text-muted" style="max-width: 600px;">Take a closer look at our intuitive Order Management System interface.</p>
      </div>
      <div id="product-gallery" class="owl-carousel owl-theme">
        <div class="item">
          <a href="assets/img/oms_1.png" class="lightbox">
            <img src="assets/img/oms_1.png" alt="OMS Screenshot 1">
          </a>
        </div>
        <div class="item">
          <a href="assets/img/oms_2.png" class="lightbox">
            <img src="assets/img/oms_2.png" alt="OMS Screenshot 2">
          </a>
        </div>
        <div class="item">
          <a href="assets/img/oms_3.png" class="lightbox">
            <img src="assets/img/oms_3.png" alt="OMS Screenshot 3">
          </a>
        </div>
        <div class="item">
          <a href="assets/img/oms_4.png" class="lightbox">
            <img src="assets/img/oms_4.png" alt="OMS Screenshot 4">
          </a>
        </div>
        <div class="item">
          <a href="assets/img/oms_5.png" class="lightbox">
            <img src="assets/img/oms_5.png" alt="OMS Screenshot 5">
          </a>
        </div>
        <div class="item">
          <a href="assets/img/oms_6.png" class="lightbox">
            <img src="assets/img/oms_6.png" alt="OMS Screenshot 6">
          </a>
        </div>
      </div>
    </div>
  </section>

  <?php include 'footer.php'; ?>
  <?php include 'script.php'; ?>
  <script src="assets/js/jquery.magnific-popup.min.js"></script>

  <script>
    $(document).ready(function() {
      $("#product-gallery").owlCarousel({
        loop: true,
        margin: 30,
        nav: true,
        navText: ["<i class='fas fa-chevron-left'></i>", "<i class='fas fa-chevron-right'></i>"],
        dots: true,
        center: true,
        autoplay: true,
        autoplayTimeout: 5000,
        autoplayHoverPause: true,
        smartSpeed: 800,
        responsive: {
          0: {
            items: 1,
            margin: 10
          },
          768: {
            items: 2,
            margin: 20
          },
          1024: {
            items: 3,
            margin: 30
          }
        }
      });

      $('.lightbox').magnificPopup({
        type: 'image',
        gallery: {
          enabled: true
        }
      });
    });
  </script>
  
  <a href="#" class="back-to-top"><i class="fas fa-chevron-up"></i></a>
</body>
</html>