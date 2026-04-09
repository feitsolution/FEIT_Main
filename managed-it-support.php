<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>Managed IT Support | FE IT Solutions</title>
  <meta name="description" content="24/7 managed IT support services including system monitoring, remote troubleshooting, preventive maintenance, and performance optimization.">
  <?php include 'header.php'; ?>
  <style>
    body {
      font-family: 'Inter', sans-serif;
      background-color: #ffffff;
      color: #111827;
    }
    .services-hero {
      padding: 120px 0 80px;
      background-color: #050505;
      text-align: center;
    }
    .services-title {
      font-size: clamp(36px, 5vw, 56px);
      font-weight: 600;
      line-height: 1.1;
      letter-spacing: -0.02em;
      color: #ffffff;
      margin: 0 0 16px;
    }
    .services-subtitle {
      font-size: clamp(16px, 2vw, 20px);
      font-weight: 400;
      line-height: 1.6;
      color: #9ca3af;
      max-width: 600px;
      margin: 0 auto;
    }
    .breadcrumb-nav {
      margin-bottom: 24px;
    }
    .breadcrumb-nav a, .breadcrumb-nav span {
      color: #6b7280;
      font-size: 14px;
      text-decoration: none;
    }
    .breadcrumb-nav a:hover {
      color: #10B981;
    }
    .breadcrumb-nav .separator {
      margin: 0 8px;
    }
    .breadcrumb-nav .current {
      color: #d1d5db;
    }
    .service-section {
      padding: 60px 0;
      border-bottom: 1px solid #e5e7eb;
    }
    .service-section:last-of-type {
      border-bottom: none;
    }
    .service-title {
      font-size: 26px;
      font-weight: 700;
      color: #111827;
      margin-bottom: 16px;
      letter-spacing: -0.01em;
    }
    .service-text {
      font-size: 16px;
      line-height: 1.8;
      color: #4b5563;
      margin-bottom: 16px;
    }
    .feature-list {
      list-style: none;
      padding: 0;
      margin: 24px 0 0;
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
      gap: 12px;
    }
    .feature-list li {
      position: relative;
      padding-left: 28px;
      font-size: 15px;
      color: #374151;
    }
    .feature-list li::before {
      content: "\f00c";
      font-family: "Font Awesome 5 Free";
      font-weight: 900;
      position: absolute;
      left: 0;
      top: 2px;
      color: #10B981;
      font-size: 13px;
    }
    .cta-section {
      text-align: center;
      padding: 60px 0;
    }
    .cta-text {
      font-size: 18px;
      color: #6b7280;
      margin-bottom: 24px;
    }
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
    @media (max-width: 768px) {
      .service-section { padding: 40px 0; }
      .feature-list { grid-template-columns: 1fr; }
    }
  </style>
</head>
<body>

  <header id="header-wrap">
    <?php include 'navbar.php'; ?>
    <div class="services-hero">
      <div class="container">
        <div class="breadcrumb-nav">
          <a href="index.php">Home</a>
          <span class="separator">/</span>
          <a href="services.php">Services</a>
          <span class="separator">/</span>
          <span class="current">Managed IT Support</span>
        </div>
        <h1 class="services-title">Managed IT Support</h1>
        <p class="services-subtitle">Continuous monitoring, proactive maintenance, and rapid issue resolution for uninterrupted business operations.</p>
      </div>
    </div>
  </header>

  <section class="service-section">
    <div class="container">
      <h2 class="service-title">Proactive IT Management & Support</h2>
      <p class="service-text">Managing IT infrastructure can be complex and time-consuming for businesses. Our Managed IT Support services are designed to provide continuous monitoring, proactive maintenance, and rapid issue resolution to ensure your business operates without interruptions.</p>
      <p class="service-text">We act as your remote IT department, providing technical expertise, system monitoring, and support services that reduce downtime, improve efficiency, and maintain system reliability.</p>
      <p class="service-text">Our services are ideal for businesses that require dependable IT support without the cost of maintaining an in-house team.</p>
      <ul class="feature-list">
        <li>24/7 System Monitoring</li>
        <li>Remote Troubleshooting & Support</li>
        <li>Preventive Maintenance</li>
        <li>Backup & Recovery Management</li>
        <li>Performance Optimization</li>
        <li>24/7 Remote IT Support</li>
      </ul>
    </div>
  </section>

  <section class="cta-section">
    <div class="container">
      <p class="cta-text">Need reliable IT support?</p>
      <a href="contact.php" class="btn-common">Request a Quote</a>
    </div>
  </section>

  <?php include 'footer.php'; ?>
  <?php include 'script.php'; ?>
  
  <a href="#" class="back-to-top"><i class="fas fa-chevron-up"></i></a>
</body>
</html>
