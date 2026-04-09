<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>Cloud Services | FE IT Solutions</title>
  <meta name="description" content="Scalable cloud infrastructure solutions using AWS and Azure. Cloud migration, architecture design, and ongoing management for modern businesses.">
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
    .section-padding { padding: 80px 0; }
    .breadcrumb-nav {
      background: transparent;
      padding: 0;
      margin-bottom: 20px;
    }
    .breadcrumb-nav a, .breadcrumb-nav span {
      color: #9ca3af;
      font-size: 14px;
      text-decoration: none;
    }
    .breadcrumb-nav a:hover {
      color: #10B981;
    }
    .breadcrumb-nav .separator {
      margin: 0 8px;
      color: #6b7280;
    }
    .breadcrumb-nav .current {
      color: #ffffff;
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
    .service-image {
      width: 100%;
      border-radius: 8px;
      margin-top: 32px;
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
          <span class="current">Cloud Services</span>
        </div>
        <h1 class="services-title">Cloud Services</h1>
        <p class="services-subtitle">Scalable, secure cloud infrastructure solutions using AWS and Azure to power your business growth.</p>
      </div>
    </div>
  </header>

  <section class="service-section">
    <div class="container">
      <h2 class="service-title">Cloud Infrastructure & Management</h2>
      <p class="service-text">Cloud computing has become a critical component for modern businesses looking to scale, reduce operational costs, and ensure high availability of their systems. At FE IT Solutions, we design, implement, and manage cloud infrastructures tailored to your business needs.</p>
      <p class="service-text">We help organizations transition from traditional on-premise systems to secure, scalable cloud environments using platforms such as Amazon Web Services (AWS) and Microsoft Azure. Our cloud solutions are built to support business growth, improve performance, and ensure data security while minimizing downtime and infrastructure costs.</p>
      <p class="service-text">Whether you are a startup looking to launch your system or an established business aiming to modernize your infrastructure, we provide end-to-end cloud services from planning to deployment and ongoing management.</p>
      <ul class="feature-list">
        <li>Scalable Infrastructure Design</li>
        <li>High Availability & Load Balancing</li>
        <li>Secure Cloud Architecture</li>
        <li>Automated Backups & Recovery</li>
        <li>Cost Optimization Strategies</li>
        <li>AWS / Azure Setup</li>
        <li>Cloud Migration</li>
      </ul>
    </div>
  </section>

  <section class="cta-section">
    <div class="container">
      <p class="cta-text">Ready to migrate to the cloud?</p>
      <a href="contact.php" class="btn-common">Book Consultation</a>
    </div>
  </section>

  <?php include 'footer.php'; ?>
  <?php include 'script.php'; ?>
  
  <a href="#" class="back-to-top"><i class="fas fa-chevron-up"></i></a>
</body>
</html>
