<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>Our Products | FE IT Solutions</title>
  <meta name="description" content="Discover FE IT Solutions' premium products including our advanced Courier Management System and Order Management System.">
  <?php include 'header.php'; ?>
  <style>
    body {
      font-family: 'Inter', sans-serif;
      background-color: #f9fafb;
    }

    /* Premium Products Hero Styling */
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
      max-width: 650px;
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

    /* Simple Product Cards Grid */
    .services-grid {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 32px;
      max-width: 900px;
      margin: 0 auto;
    }
    .service-card {
      background: #ffffff;
      border: 1px solid #f3f4f6;
      border-radius: 20px;
      padding: 40px 32px;
      text-decoration: none;
      transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
      box-shadow: 0 4px 12px rgba(0,0,0,0.03);
      display: flex;
      flex-direction: column;
      height: 100%;
    }
    .service-card:hover {
      transform: translateY(-6px);
      box-shadow: 0 20px 40px rgba(0,0,0,0.08);
      border-color: rgba(16, 185, 129, 0.2);
      text-decoration: none;
    }
    .service-card-icon {
      width: 64px;
      height: 64px;
      background: #f0fdf4;
      border-radius: 16px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 28px;
      color: #10B981;
      margin-bottom: 24px;
      transition: all 0.3s ease;
    }
    .service-card:hover .service-card-icon {
      background: #10B981;
      color: #ffffff;
    }
    .service-card-title {
      font-size: 22px;
      font-weight: 700;
      color: #111827;
      margin-bottom: 12px;
      letter-spacing: -0.01em;
    }
    .service-card-desc {
      font-size: 15px;
      line-height: 1.6;
      color: #6b7280;
      margin-bottom: 24px;
      flex: 1;
    }
    .service-card-arrow {
      font-size: 14px;
      font-weight: 600;
      color: #10B981;
      display: flex;
      align-items: center;
      gap: 6px;
    }
    .service-card-arrow i {
      transition: transform 0.3s ease;
    }
    .service-card:hover .service-card-arrow i {
      transform: translateX(4px);
    }

    @media (max-width: 768px) {
      .services-grid { grid-template-columns: 1fr; }
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
  </style>
</head>
<body>

  <header id="header-wrap">
    <?php include 'navbar.php'; ?>
    <div class="services-hero">
      <img src="assets/img/cc.png" alt="Hero Background" class="services-hero-img">
      <div class="services-ambient-glow"></div>
      <div class="services-overlay"></div>
      <div class="services-content-wrapper">
        <div class="container">
          <h1 class="services-title">Our Products</h1>
          <p class="services-subtitle">Enterprise-Ready logistics and management products designed for operational excellence.</p>
        </div>
      </div>
    </div>
  </header>

  <section class="section-padding">
    <div class="container">
      <div class="section-header text-center mb-5 pb-3">
        <span class="premium-label">Scalable Systems</span>
        <h2 class="section-title">Built for Modern Workflows</h2>
        <p class="mx-auto text-muted" style="max-width: 600px; font-size: 16px; line-height: 1.6;">Leverage our highly optimized standard software products to deploy powerful technical solutions in a fraction of the time.</p>
      </div>

      <div class="services-grid">

        <a href="courier_management.php" class="service-card wow fadeInUp" data-wow-delay="0.1s">
          <div class="service-card-icon"><i class="lni-delivery"></i></div>
          <h3 class="service-card-title">Courier Management System</h3>
          <p class="service-card-desc">End-to-end logistics platform with real-time GPS tracking, automated dispatch, customer portal, and digital proof of delivery.</p>
          <span class="service-card-arrow">Learn More <i class="fas fa-arrow-right"></i></span>
        </a>

        <a href="order_management.php" class="service-card wow fadeInUp" data-wow-delay="0.2s">
          <div class="service-card-icon"><i class="lni-cart-full"></i></div>
          <h3 class="service-card-title">Order Management System</h3>
          <p class="service-card-desc">Unified dashboard for all sales channels with inventory syncing, automated invoicing, and advanced analytics.</p>
          <span class="service-card-arrow">Learn More <i class="fas fa-arrow-right"></i></span>
        </a>

      </div>

      <div class="text-center mt-5 pt-4">
        <p class="text-muted mb-4" style="font-size: 17px;">Ready to integrate one of our systems?</p>
        <a href="contact.php" class="btn-common">Request a Live Demo</a>
      </div>
    </div>
  </section>

  <?php include 'footer.php'; ?>
  <?php include 'script.php'; ?>
  
  <a href="#" class="back-to-top"><i class="fas fa-chevron-up"></i></a>
</body>
</html>
