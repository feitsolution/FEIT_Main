<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>Courier Management System | FE IT Solutions</title>
  <meta name="description" content="Discover FE IT Solutions' Courier Management System - an efficient platform for streamlining parcel deliveries with real-time tracking and management.">
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
  </style>
</head>
<body>

  <header id="header-wrap">
    <?php include 'navbar.php'; ?>
    <div class="services-hero">
      <img src="assets/img/pr.png" alt="Courier Management System" class="services-hero-img">
      <div class="services-ambient-glow"></div>
      <div class="services-overlay"></div>
      <div class="services-content-wrapper">
        <div class="container">
          <h1 class="services-title">Courier Management System</h1>
          <p class="services-subtitle">Courier Management System is an efficient platform designed to streamline parcel deliveries.</p>
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
            <h2>Why Choose Our Courier Management System?</h2>
            <p>Our Courier Management System simplifies your parcel delivery process with its advanced tracking and management capabilities, ensuring speed, reliability, and seamless service. Our system automates logistics tasks, saving you time and reducing errors while providing real-time visibility at every stage. Choose our courier management system to optimize your delivery networks and grow your operations effortlessly.</p>
            <a href="contact.php" class="btn-learn-more">Learn More <i class="fas fa-chevron-right"></i></a>
          </div>
        </div>
        <!-- Right Cards -->
        <div class="col-lg-8">
          <div class="row g-3">
            <div class="col-md-4 wow fadeInUp" data-wow-delay="0.1s">
              <div class="why-choose-card">
                <div class="why-choose-icon"><i class="fas fa-sliders-h"></i></div>
                <h3>Logistics Customization</h3>
                <p>Tailor the system to your specific hub workflows and delivery networks, ensuring a perfect fit for your nationwide courier operations.</p>
              </div>
            </div>
            <div class="col-md-4 wow fadeInUp" data-wow-delay="0.2s">
              <div class="why-choose-card">
                <div class="why-choose-icon"><i class="fas fa-headset"></i></div>
                <h3>Expert Product Support</h3>
                <p>Enjoy peace of mind with 24/7 dedicated customer support, available to assist with any logistics challenges or parcel tracking queries.</p>
              </div>
            </div>
            <div class="col-md-4 wow fadeInUp" data-wow-delay="0.3s">
              <div class="why-choose-card">
                <div class="why-choose-icon"><i class="fas fa-tags"></i></div>
                <h3>Affordable Pricing</h3>
                <p>Access premium logistics features at a cost-effective price, making it a budget-friendly solution for growing delivery businesses.</p>
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
        
        <h2 class="section-title">Key Features of Our Courier Management System</h2>
        <p class="mx-auto text-muted" style="max-width: 800px;">Optimized for speed and efficiency, our system brings a new standard to logistics management:</p>
      </div>
      <div class="row g-4">
        <div class="col-lg-4 col-md-6 wow fadeInUp" data-wow-delay="0.1s">
          <div class="feature-item">
            <div class="feature-icon-wrapper"><i class="fas fa-map-marker-alt"></i></div>
            <h3 class="feature-title">Real-Time Tracking</h3>
            <p class="feature-text">Provides instant GPS parcel location and estimated delivery time updates for complete transparency at every stage of the delivery journey.</p>
          </div>
        </div>
        <div class="col-lg-4 col-md-6 wow fadeInUp" data-wow-delay="0.2s">
          <div class="feature-item">
            <div class="feature-icon-wrapper"><i class="fas fa-network-wired"></i></div>
            <h3 class="feature-title">Multi-Hub Logistics</h3>
            <p class="feature-text">Efficiently manage parcel transfers between pickup, processing, and delivery hubs with a streamlined and automated logistics workflow.</p>
          </div>
        </div>
        <div class="col-lg-4 col-md-6 wow fadeInUp" data-wow-delay="0.3s">
          <div class="feature-item">
            <div class="feature-icon-wrapper"><i class="fas fa-user-cog"></i></div>
            <h3 class="feature-title">Client Dashboard</h3>
            <p class="feature-text">A dedicated portal that empowers your clients to book shipments, track their history, and manage accounts with ease and security.</p>
          </div>
        </div>
        <div class="col-lg-4 col-md-6 wow fadeInUp" data-wow-delay="0.4s">
          <div class="feature-item">
            <div class="feature-icon-wrapper"><i class="fas fa-file-signature"></i></div>
            <h3 class="feature-title">Secure Deliveries (POD)</h3>
            <p class="feature-text">Ensure every parcel is accounted for with integrated Proof of Delivery, including digital signatures and photo evidence from the field.</p>
          </div>
        </div>
        <div class="col-lg-4 col-md-6 wow fadeInUp" data-wow-delay="0.5s">
          <div class="feature-item">
            <div class="feature-icon-wrapper"><i class="fas fa-clipboard-list"></i></div>
            <h3 class="feature-title">Automated Manifesting</h3>
            <p class="feature-text">Instantly generate shipping labels, manifesting reports, and international logistics documentation with a single click.</p>
          </div>
        </div>
        <div class="col-lg-4 col-md-6 wow fadeInUp" data-wow-delay="0.6s">
          <div class="feature-item">
            <div class="feature-icon-wrapper"><i class="fas fa-undo-alt"></i></div>
            <h3 class="feature-title">Return Management</h3>
            <p class="feature-text">Robust and automated workflows for handling undelivered, redirected, or returned parcels to minimize losses and improve efficiency.</p>
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
        <p class="mx-auto text-muted" style="max-width: 600px;">Take a closer look at our intuitive Courier Management System interface.</p>
      </div>
      <div id="product-gallery" class="owl-carousel owl-theme">
        <div class="item">
          <a href="assets/img/slide1.png" class="lightbox">
            <img src="assets/img/slide1.png" alt="Courier System Screenshot 1">
          </a>
        </div>
        <div class="item">
          <a href="assets/img/slide2.png" class="lightbox">
            <img src="assets/img/slide2.png" alt="Courier System Screenshot 2">
          </a>
        </div>
        <div class="item">
          <a href="assets/img/slide3.png" class="lightbox">
            <img src="assets/img/slide3.png" alt="Courier System Screenshot 3">
          </a>
        </div>
        <div class="item">
          <a href="assets/img/slide4.png" class="lightbox">
            <img src="assets/img/slide4.png" alt="Courier System Screenshot 4">
          </a>
        </div>
        <div class="item">
          <a href="assets/img/slide5.png" class="lightbox">
            <img src="assets/img/slide5.png" alt="Courier System Screenshot 5">
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
