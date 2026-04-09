<!DOCTYPE html>
<html lang="en">
<head>
  <!-- Required meta tags -->
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  
  <!-- SEO Meta Tags -->
  <title>FE IT Solutions | Software Development Company in Sri Lanka</title>
  <meta name="description" content="FE IT Solutions is a leading software development company in Sri Lanka, offering a comprehensive range of IT services.">
  <meta name="keywords" content="IT Solutions Sri Lanka, ERP Software Colombo, Web Development Sri Lanka IT Consultancy, Custom Software Development">
  
  <!-- Open Graph / Social -->
  <meta property="og:type" content="website">
  <meta property="og:title" content="FE IT Solutions | Software Development Company in Sri Lanka">
  <meta property="og:description" content="FE IT Solutions is a leading software development company in Sri Lanka, offering a comprehensive range of IT services.">
  <meta property="og:image" content="https://feitsolutions.com/assets/img/FEIT.png">
  <meta property="og:url" content="https://feitsolutions.com/">
  <?php include 'header.php'; ?>
</head>

  <header id="header-wrap">
    <?php include 'navbar.php'; ?>

<!-- Hero Area Start -->
<!-- <div id="hero-area" class="hero-area-bg">
<img src="assets/img/hero-vedio2.png" alt="Hero Image" class="hero-video">

  <div class="container">
    <div class="row">
      <div class="contents">
        <h1 class="head-title">Transforming Enterprises <span class="blue-gradient">Through Strategic</span> <span class="green-gradient">Technology Solutions</span></h1>
        <p class="head-wrap">For your unique business requirements</p>
        <div class="header-button">
          <a href="contact.php" class="btn btn-common"> Meet With Us</a>
        </div>
      </div>
    </div>
  </div>
</div> -->
<!-- Hero Area End -->
 
<?php include 'hero_area.php'; ?>
 
</header>

<style>
  /* Premium Home Aesthetics */
  body {
    font-family: 'Inter', sans-serif;
  }
  
  .section-padding {
    padding: 50px 0;
  }

  /* Section Title Labels */
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
    font-size: clamp(24px, 3vw, 36px);
    font-weight: 700;
    letter-spacing: -0.02em;
    color: #111827;
    margin-bottom: 24px;
    line-height: 1.2;
  }

  /* Core Services Cards */
  #services .row > div {
    margin-bottom: 30px;
  }
  
  .services-item {
    background: #ffffff;
    border: 1px solid #f3f4f6;
    border-radius: 20px;
    padding: 40px 30px;
    transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
    height: 100%;
    text-align: left;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
  }

  .services-item:hover {
    transform: translateY(-8px);
    box-shadow: 0 20px 40px -10px rgba(0, 0, 0, 0.1);
    border-color: #e5e7eb;
  }

  .services-item .icon {
    width: 60px;
    height: 60px;
    border-radius: 16px;
    background: #f0fdf4;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    color: #10B981;
    margin-bottom: 24px;
    transition: all 0.3s;
  }

  .services-item:hover .icon {
    background: #10B981;
    color: #ffffff;
    box-shadow: 0 10px 20px rgba(16, 185, 129, 0.2);
  }

  .services-content h3 a {
    color: #111827;
    font-size: 17px;
    font-weight: 600;
    text-decoration: none;
    transition: color 0.3s;
  }

  .services-content h3 a:hover {
    color: #10B981;
  }

  .services-content p {
    color: #6b7280;
    font-size: 14px;
    line-height: 1.6;
    margin-top: 12px;
  }

  .btn-com {
    display: inline-flex;
    align-items: center;
    font-size: 14px;
    font-weight: 600;
    color: #111827;
    margin-top: 24px;
    text-decoration: none;
    transition: all 0.3s;
  }

  .btn-com:hover {
    color: #10B981;
  }

  /* About Area */
  .bg-gray {
    background-color: #f5f5f5 !important;
  }

  .about-area .content p {
    font-size: 15px;
    line-height: 1.7;
    color: #4b5563;
  }

  .about-area img {
    border-radius: 24px;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.08);
  }

  .btn-common {
    background: #10B981;
    color: #ffffff !important;
    padding: 10px 24px;
    border-radius: 100px;
    font-weight: 500;
    font-size: 14px;
    transition: all 0.3s;
    border: none;
    display: inline-block;
  }

  .btn-common:hover {
    background: #059669;
    transform: translateY(-2px);
    box-shadow: 0 10px 20px rgba(16, 185, 129, 0.2);
  }

  #features {
    background-color: #050505;
    color: #ffffff;
  }

  #features .premium-label {
    background: rgba(255, 255, 255, 0.05);
    color: #9ca3af;
    border: 1px solid rgba(255, 255, 255, 0.1);
  }

  #features .section-title {
    color: #ffffff;
  }

  .box-item {
    display: flex;
    align-items: flex-start;
    margin-bottom: 40px;
  }

  .box-item .icon {
    width: 64px;
    height: 64px;
    flex-shrink: 0;
    border-radius: 16px;
    background: rgba(255, 255, 255, 0.03);
    border: 1px solid rgba(255, 255, 255, 0.08);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    color: #10B981;
    margin-right: 20px;
    transition: all 0.4s;
  }

  .box-item:hover .icon {
    background: rgba(16, 185, 129, 0.1);
    border-color: rgba(16, 185, 129, 0.3);
    box-shadow: 0 0 20px rgba(16, 185, 129, 0.15);
  }

  .box-item .text h4 {
    color: #ffffff !important;
    font-size: 16px;
    font-weight: 600;
    margin-bottom: 10px;
  }

  .box-item .text p {
    color: #9ca3af;
    font-size: 14px;
    line-height: 1.6;
  }
  /* Portfolio Section Styles */
  .portfolio-section {
    background: #ffffff;
  }
  
  .portfolio-item {
    padding: 60px 0;
    overflow: hidden;
  }
  
  .portfolio-content {
    padding: 20px;
  }
  
  .portfolio-content h3 {
    font-size: 26px;
    font-weight: 700;
    color: #111827;
    margin-bottom: 20px;
  }

  .portfolio-content p {
    font-size: 15px;
    color: #6b7280;
    line-height: 1.7;
    margin-bottom: 30px;
  }
  
  .portfolio-image img {
    border-radius: 20px;
    box-shadow: 0 20px 50px rgba(0, 0, 0, 0.1);
    transition: transform 0.5s ease;
  }
  
  .portfolio-item:hover .portfolio-image img {
    transform: scale(1.02);
  }

  .portfolio-badge {
    color: #10B981;
    font-weight: 700;
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 1px;
    margin-bottom: 12px;
    display: block;
  }
</style>

<!-- About Section start -->
<div class="about-area section-padding bg-gray">
  <div class="container">
    <div class="row align-items-center">
      <div class="col-lg-6 col-md-12 col-xs-12 info pr-lg-5">
        <div class="about-wrapper wow fadeInLeft" data-wow-delay="0.3s">
          <div>
            <div class="site-heading">
              <span class="premium-label"></span>
              <h2 class="section-title">We Prioritize Exceeding Expectations Of Our Clients</h2>
            </div>
            <div class="content">
              <p>
                At FE IT Solutions, our core mission is to deliver unparalleled digital solutions that not only meet
                but exceed the expectations of our diverse clientele. Whether supporting local businesses or global
                enterprises, we specialize in crafting customized, effective strategies in ERP software development,
                SEO, IT consultancy, and structured cabling to drive success and satisfaction.
              </p>
              <a href="about.php" class="btn btn-common mt-3">Read More</a>
            </div>
          </div>
        </div>
      </div>
      <div class="col-lg-6 col-md-12 col-xs-12 wow fadeInRight" data-wow-delay="0.3s">
         <img class="img-fluid" src="assets/img/about/service.jpg" alt="FE IT Solutions Team working on digital transformation">
      </div>
      
    </div>
  </div>
</div>
<!-- About Section End -->
  
<!-- Portfolio Section Start -->
<section id="portfolio" class="portfolio-section section-padding">
  <div class="container">
    <div class="section-header text-center mb-5">
      <h2 class="section-title wow fadeInDown" data-wow-delay="0.3s">Explore FE IT Solution's New Software Innovations</h2>
    </div>

    <!-- Courier Management -->
    <div class="row align-items-center portfolio-item">
      <div class="col-lg-6 col-md-12 wow fadeInLeft" data-wow-delay="0.3s">
        <div class="portfolio-image">
          <img src="assets/img/cm.png" class="img-fluid" alt="Courier Management System">
        </div>
      </div>
      <div class="col-lg-6 col-md-12 wow fadeInRight" data-wow-delay="0.3s">
        <div class="portfolio-content pl-lg-5">
          <span class="portfolio-badge">Logistics & Distribution</span>
          <h3>Courier Management System</h3>
          <p>A comprehensive end-to-end solution for courier and logistics companies. Features include real-time tracking, automated dispatch, manifests generation, and detailed reporting to optimize every stage of delivery.</p>
          <a href="courier_management.php" class="btn btn-common">View Details</a>
        </div>
      </div>
    </div>

    <!-- Order Management -->
    <div class="row align-items-center portfolio-item flex-row-reverse">
      <div class="col-lg-6 col-md-12 wow fadeInRight" data-wow-delay="0.3s">
        <div class="portfolio-image">
          <img src="assets/img/oms.png" class="img-fluid" alt="Order Management System">
        </div>
      </div>
      <div class="col-lg-6 col-md-12 wow fadeInLeft" data-wow-delay="0.3s">
        <div class="portfolio-content pr-lg-5">
          <span class="portfolio-badge">E-commerce & Operations</span>
          <h3>Order Management System (OMS)</h3>
          <p>Streamline your e-commerce operations with our advanced OMS. Centralize orders from multiple channels, manage inventory in real-time, and automate the fulfillment process to deliver a superior customer experience.</p>
          <a href="order_management.php" class="btn btn-common">View Details</a>
        </div>
      </div>
    </div>
  </div>
</section>
<!-- Portfolio Section End -->

<!-- Services Section Start -->
<section id="services" class="section-padding bg-gray">
  <div class="container">
    <div class="section-header text-center mb-5">
      <span class="premium-label">Our Expertise</span>
      <h2 class="section-title wow fadeInDown" data-wow-delay="0.3s">Comprehensive IT Solutions for Modern Enterprises</h2>
    </div>
    <div class="row g-4">
      <!-- ERP & Business Automation -->
      <div class="col-md-6 col-lg-4 col-xs-12">
        <a href="erp.php" class="text-decoration-none">
          <div class="services-item wow fadeInRight" data-wow-delay="0.2s">
            <div class="icon">
              <i class="lni-grid-alt"></i>
            </div>
            <div class="services-content">
              <h3>Custom ERP Systems</h3>
              <p>Streamline your business operations with tailored ERP solutions designed for efficiency and scalability.</p>
            </div>
          </div>
        </a>
      </div>
      <!-- Custom Software Development -->
      <div class="col-md-6 col-lg-4 col-xs-12">
        <a href="custom-software.php" class="text-decoration-none">
          <div class="services-item wow fadeInRight" data-wow-delay="0.3s">
            <div class="icon">
              <i class="lni-code"></i>
            </div>
            <div class="services-content">
              <h3>Software Development</h3>
              <p>Besproke software solutions architecture that solves complex business challenges with modern tech.</p>
            </div>
          </div>
        </a>
      </div>
      <!-- Cloud & Infrastructure -->
      <div class="col-md-6 col-lg-4 col-xs-12">
        <a href="cloud-services.php" class="text-decoration-none">
          <div class="services-item wow fadeInRight" data-wow-delay="0.4s">
            <div class="icon">
              <i class="lni-cloud"></i>
            </div>
            <div class="services-content">
              <h3>Cloud Solutions</h3>
              <p>Accelerate your digital journey with secure, scalable, and high-performance cloud infrastructure.</p>
            </div>
          </div>
        </a>
      </div>
      <!-- Network & Cabling -->
      <div class="col-md-6 col-lg-4 col-xs-12">
        <a href="network-infrastructure.php" class="text-decoration-none">
          <div class="services-item wow fadeInRight" data-wow-delay="0.5s">
            <div class="icon">
              <i class="lni-layers"></i>
            </div>
            <div class="services-content">
              <h3>Network Infrastructure</h3>
              <p>Robust networking and structured cabling solutions to ensure seamless connectivity for your enterprise.</p>
            </div>
          </div>
        </a>
      </div>

      <!-- POS Systems -->
      <div class="col-md-6 col-lg-4 col-xs-12">
        <a href="pos.php" class="text-decoration-none">
          <div class="services-item wow fadeInRight" data-wow-delay="0.6s">
            <div class="icon">
              <i class="lni-cart"></i>
            </div>
            <div class="services-content">
              <h3>POS Systems</h3>
              <p>Modern point-of-sale solutions designed to streamline retail operations and inventory management.</p>
            </div>
          </div>
        </a>
      </div>

      <!-- Managed IT Support -->
      <div class="col-md-6 col-lg-4 col-xs-12">
        <a href="managed-it-support.php" class="text-decoration-none">
          <div class="services-item wow fadeInRight" data-wow-delay="0.7s">
            <div class="icon">
              <i class="lni-support"></i>
            </div>
            <div class="services-content">
              <h3>Managed IT Support</h3>
              <p>24/7 proactive monitoring and expert support to keep your business systems running smoothly.</p>
            </div>
          </div>
        </a>
      </div>
    </div>
  </div>
</section>
<!-- Services Section End -->

<!-- Process Section Start -->
<section id="process" class="section-padding">
  <div class="container">
    <div class="section-header text-center mb-5">
      <span class="premium-label">Our Workflow</span>
      <h2 class="section-title wow fadeInDown" data-wow-delay="0.3s">How We Deliver Excellence</h2>
    </div>
    <div class="row text-center">
      <!-- Step 1 -->
      <div class="col-lg-3 col-md-6 col-xs-12 mb-4">
        <div class="process-box wow fadeInUp" data-wow-delay="0.2s">
          <div class="icon mx-auto mb-4" style="width: 70px; height: 70px; background: #ffffff; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 24px; color: #10B981; box-shadow: 0 10px 30px rgba(0,0,0,0.05);">
            <i class="lni-search"></i>
          </div>
          <h4>01. Discovery</h4>
          <p class="mt-3">We deep dive into your business requirements to understand your core challenges and goals.</p>
        </div>
      </div>
      <!-- Step 2 -->
      <div class="col-lg-3 col-md-6 col-xs-12 mb-4">
        <div class="process-box wow fadeInUp" data-wow-delay="0.4s">
          <div class="icon mx-auto mb-4" style="width: 70px; height: 70px; background: #ffffff; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 24px; color: #10B981; box-shadow: 0 10px 30px rgba(0,0,0,0.05);">
            <i class="lni-target"></i>
          </div>
          <h4>02. Strategy</h4>
          <p class="mt-3">Architecting a scalable roadmap and technology stack tailored for your long-term success.</p>
        </div>
      </div>
      <!-- Step 3 -->
      <div class="col-lg-3 col-md-6 col-xs-12 mb-4">
        <div class="process-box wow fadeInUp" data-wow-delay="0.6s">
          <div class="icon mx-auto mb-4" style="width: 70px; height: 70px; background: #ffffff; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 24px; color: #10B981; box-shadow: 0 10px 30px rgba(0,0,0,0.05);">
            <i class="lni-code"></i>
          </div>
          <h4>03. Execution</h4>
          <p class="mt-3">Agile development and meticulous engineering bringing your digital solutions to life.</p>
        </div>
      </div>
      <!-- Step 4 -->
      <div class="col-lg-3 col-md-6 col-xs-12 mb-4">
        <div class="process-box wow fadeInUp" data-wow-delay="0.8s">
          <div class="icon mx-auto mb-4" style="width: 70px; height: 70px; background: #ffffff; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 24px; color: #10B981; box-shadow: 0 10px 30px rgba(0,0,0,0.05);">
            <i class="lni-rocket"></i>
          </div>
          <h4>04. Optimization</h4>
          <p class="mt-3">Continuous monitoring, feedback loops, and optimization for peak performance.</p>
        </div>
      </div>
    </div>
  </div>
</section>
<!-- Process Section End -->

<!-- Features Section Start -->
<!-- <section id="features" class="section-padding">
  <div class="container">
    <div class="section-header text-center mb-5">
      <h2 class="section-title wow fadeInDown" data-wow-delay="0.3s">Empowering Innovation through Expertise</h2>
    </div>
    <div class="row align-items-center">
      <div class="col-lg-4 col-md-12 col-sm-12 col-xs-12">
        <div class="content-left">
          <div class="box-item wow fadeInLeft" data-wow-delay="0.3s">
            <span class="icon">
              <i class="lni-rocket"></i>
            </span>
            <div class="text">
              <h4 style="color: #e6e6e6;">Innovative Solutions</h4>

              <p>We deliver tailored ERP and mobile apps to streamline operations and boost productivity.</p>
            </div>
          </div>
          <div class="box-item wow fadeInLeft" data-wow-delay="0.6s">
            <span class="icon">
              <i class="lni-laptop-phone"></i>
            </span>
            <div class="text">
              <h4 style="color: #e6e6e6;">Expert Guidance</h4>
              <p>Our IT consultancy strategically ensures you stay ahead in a rapidly evolving digital world.</p>
            </div>
          </div>
          <div class="box-item wow fadeInLeft" data-wow-delay="0.9s">
            <span class="icon">
              <i class="lni-cog"></i>
            </span>
            <div class="text">
              <h4 style="color: #e6e6e6;"> SEO Success</h4>
              <p>Enhance visibility, drive traffic, and achieve sustainable growth with our SEO expertise.</p>
            </div>
          </div>
        </div>
      </div>
      <div class="col-lg-4 col-md-12 col-sm-12 col-xs-12">
        <div class="show-box wow fadeInUp" data-wow-delay="0.3s">
          <img src="assets/img/feature/intro-mobile3.png" alt="Product interface overview on mobile">
        </div>
      </div>
      <div class="col-lg-4 col-md-12 col-sm-12 col-xs-12">
        <div class="content-right">
          <div class="box-item wow fadeInRight" data-wow-delay="0.3s">
            <span class="icon">
              <i class="lni-leaf"></i>
            </span>
            <div class="text">
              <h4 style="color: #e6e6e6;">Reliable Maintenance</h4>
              <p>Elegant and user-friendly design approach that aligns with current web design trends.</p>
            </div>
          </div>
          <div class="box-item wow fadeInRight" data-wow-delay="0.6s">
            <span class="icon">
              <i class="lni-layers"></i>
            </span>
            <div class="text">
              <h4 style="color: #e6e6e6;">Advanced Security Solutions</h4>
              <p>rotect your business with modern surveillance and smart threat detection systems.</p>
            </div>
          </div>
          <div class="box-item wow fadeInRight" data-wow-delay="0.9s">
            <span class="icon">
              <i class="lni-leaf"></i>
            </span>
            <div class="text">
              <h4 style="color: #e6e6e6;">Client Focus</h4>
              <p>Protect your business with modern surveillance and smart threat detection systems.</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section> -->
<!-- Features Section End -->

<!-- Testimonials Section Start -->
<section id="testimonials-area" class="section-padding" style="background: #0a0a0a; color: #ffffff;">
  <div class="container">
    <div class="section-header text-center mb-5">
      <span class="premium-label" style="background: rgba(255, 255, 255, 0.05); color: #9ca3af;">Success Stories</span>
      <h2 class="section-title text-white wow fadeInDown" data-wow-delay="0.3s">What Our Partners Say</h2>
    </div>
    <div class="row justify-content-center">
      <div class="col-lg-10">
        <style>
          #testimonialCarousel .carousel-indicators .active { opacity: 1 !important; transform: scale(1.2); }
          #testimonialCarousel .carousel-indicators li { transition: all 0.3s ease; cursor: pointer; }
        </style>
        <div id="testimonialCarousel" class="carousel slide wow fadeInUp" data-ride="carousel" data-interval="4000" data-wow-delay="0.6s">
          <!-- Indicators -->
          <ol class="carousel-indicators" style="bottom: -40px;">
            <li data-target="#testimonialCarousel" data-slide-to="0" class="active" style="background-color: #10B981; width: 10px; height: 10px; border-radius: 50%; opacity: 0.4; border: none; margin: 0 6px;"></li>
            <li data-target="#testimonialCarousel" data-slide-to="1" style="background-color: #10B981; width: 10px; height: 10px; border-radius: 50%; opacity: 0.4; border: none; margin: 0 6px;"></li>
            <li data-target="#testimonialCarousel" data-slide-to="2" style="background-color: #10B981; width: 10px; height: 10px; border-radius: 50%; opacity: 0.4; border: none; margin: 0 6px;"></li>
            <li data-target="#testimonialCarousel" data-slide-to="3" style="background-color: #10B981; width: 10px; height: 10px; border-radius: 50%; opacity: 0.4; border: none; margin: 0 6px;"></li>
            <li data-target="#testimonialCarousel" data-slide-to="4" style="background-color: #10B981; width: 10px; height: 10px; border-radius: 50%; opacity: 0.4; border: none; margin: 0 6px;"></li>
          </ol>

          <!-- Wrapper for slides -->
          <div class="carousel-inner">
            <!-- Item 1 - Order Management System -->
            <div class="carousel-item active text-center p-4">
              <div class="quote-icon mb-4" style="font-size: 24px; color: #10B981;">
                <i class="fas fa-quote-left"></i>
              </div>
              <p style="font-size: 16px; line-height: 1.8; color: #d1d5db; font-style: italic; margin-bottom: 30px;">
                "The Order Management System from FE IT Solutions has completely transformed how we handle multi-channel sales. We can now track orders in real-time across all platforms, manage inventory seamlessly, and our fulfillment process is 3x faster. Our customers are happier than ever!"
              </p>
              <div class="author-info">
                <h5 style="color: #ffffff; font-weight: 700; margin-bottom: 5px;">Ruwan Fernando</h5>
                <span style="color: #10B981; font-size: 13px; text-transform: uppercase; letter-spacing: 1px;">Managing Director, QuickShop Lanka</span>
              </div>
            </div>

            <!-- Item 2 - Custom ERP System -->
            <div class="carousel-item text-center p-4">
              <div class="quote-icon mb-4" style="font-size: 24px; color: #10B981;">
                <i class="fas fa-quote-left"></i>
              </div>
              <p style="font-size: 16px; line-height: 1.8; color: #d1d5db; font-style: italic; margin-bottom: 30px;">
                "FE IT Solutions built a custom ERP system that perfectly fits our manufacturing workflows. From procurement to production tracking and financial reporting, everything is now centralized and automated. The ROI we've seen in just six months is incredible."
              </p>
              <div class="author-info">
                <h5 style="color: #ffffff; font-weight: 700; margin-bottom: 5px;">Dilshan Rathnayake</h5>
                <span style="color: #10B981; font-size: 13px; text-transform: uppercase; letter-spacing: 1px;">COO, Ceylon Manufacturing Corp</span>
              </div>
            </div>

            <!-- Item 3 - Cloud Solutions -->
            <div class="carousel-item text-center p-4">
              <div class="quote-icon mb-4" style="font-size: 24px; color: #10B981;">
                <i class="fas fa-quote-left"></i>
              </div>
              <p style="font-size: 16px; line-height: 1.8; color: #d1d5db; font-style: italic; margin-bottom: 30px;">
                "Migrating to the cloud with FE IT Solutions was seamless. They designed a scalable, secure cloud infrastructure that reduced our operational costs by 40% while improving system performance. Their team handled everything from planning to execution flawlessly."
              </p>
              <div class="author-info">
                <h5 style="color: #ffffff; font-weight: 700; margin-bottom: 5px;">Sanjeewa Bandara</h5>
                <span style="color: #10B981; font-size: 13px; text-transform: uppercase; letter-spacing: 1px;">CTO, TechVentures Pvt Ltd</span>
              </div>
            </div>

            <!-- Item 4 - Managed IT Support -->
            <div class="carousel-item text-center p-4">
              <div class="quote-icon mb-4" style="font-size: 24px; color: #10B981;">
                <i class="fas fa-quote-left"></i>
              </div>
              <p style="font-size: 16px; line-height: 1.8; color: #d1d5db; font-style: italic; margin-bottom: 30px;">
                "With FE IT Solutions' 24/7 managed IT support, we no longer worry about system downtime or security vulnerabilities. Their proactive monitoring catches issues before they become problems. It's like having a dedicated IT team that never sleeps."
              </p>
              <div class="author-info">
                <h5 style="color: #ffffff; font-weight: 700; margin-bottom: 5px;">Kavindi Jayasinghe</h5>
                <span style="color: #10B981; font-size: 13px; text-transform: uppercase; letter-spacing: 1px;">Operations Manager, Island Retail Group</span>
              </div>
            </div>

            <!-- Item 5 - Network Infrastructure -->
            <div class="carousel-item text-center p-4">
              <div class="quote-icon mb-4" style="font-size: 24px; color: #10B981;">
                <i class="fas fa-quote-left"></i>
              </div>
              <p style="font-size: 16px; line-height: 1.8; color: #d1d5db; font-style: italic; margin-bottom: 30px;">
                "FE IT Solutions redesigned our entire network infrastructure from structured cabling to enterprise Wi-Fi. The connectivity across our three-floor office is flawless, and the security measures they implemented give us complete peace of mind. Highly professional team!"
              </p>
              <div class="author-info">
                <h5 style="color: #ffffff; font-weight: 700; margin-bottom: 5px;">Pradeep Wijesinghe</h5>
                <span style="color: #10B981; font-size: 13px; text-transform: uppercase; letter-spacing: 1px;">Director, Global Trading Partners</span>
              </div>
            </div>
          </div>
          
          <!-- Controls -->
          <a class="carousel-control-prev" href="#testimonialCarousel" role="button" data-slide="prev" style="width: 50px;">
            <i class="fas fa-chevron-left" style="color: #10B981; font-size: 24px;"></i>
            <span class="sr-only">Previous</span>
          </a>
          <a class="carousel-control-next" href="#testimonialCarousel" role="button" data-slide="next" style="width: 50px;">
            <i class="fas fa-chevron-right" style="color: #10B981; font-size: 24px;"></i>
            <span class="sr-only">Next</span>
          </a>
        </div>
      </div>
    </div>
  </div>
</section>
<!-- Testimonials Section End -->

<!-- FAQ Section Start -->
<section id="faq" class="section-padding bg-white">
  <div class="container">
    <div class="section-header text-center mb-5">
      <span class="premium-label">Common Questions</span>
      <h2 class="section-title wow fadeInDown" data-wow-delay="0.3s">Everything You Need to Know</h2>
    </div>
    <div class="row justify-content-center">
      <div class="col-lg-10">
        <div class="accordion" id="faqAccordion">
          <!-- Question 1 -->
          <div class="card border-0 mb-3 shadow-sm" style="border-radius: 12px; overflow: hidden;">
            <div class="card-header bg-white p-0 border-0" id="headingOne">
              <button class="btn btn-block text-left p-4 d-flex align-items-center justify-content-between" type="button" data-toggle="collapse" data-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne" style="font-weight: 600; font-size: 14px; color: #111827; background: transparent; border: none; box-shadow: none;">
                What types of industries do you specialize in?
                <i class="lni-chevron-down"></i>
              </button>
            </div>
            <div id="collapseOne" class="collapse show" aria-labelledby="headingOne" data-parent="#faqAccordion">
              <div class="card-body p-4 pt-0 text-muted" style="line-height: 1.7;">
                While we serve various sectors, we have deep expertise in Logistics, E-commerce, Manufacturing, and Enterprise Operations through our custom Order Management, Courier Management and POS systems.
              </div>
            </div>
          </div>
          <!-- Question 2 -->
          <div class="card border-0 mb-3 shadow-sm" style="border-radius: 12px; overflow: hidden;">
            <div class="card-header bg-white p-0 border-0" id="headingTwo">
              <button class="btn btn-block text-left p-4 d-flex align-items-center justify-content-between collapsed" type="button" data-toggle="collapse" data-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo" style="font-weight: 600; font-size: 14px; color: #111827; background: transparent; border: none; box-shadow: none;">
                Do you offer long-term post-deployment support?
                <i class="lni-chevron-down"></i>
              </button>
            </div>
            <div id="collapseTwo" class="collapse" aria-labelledby="headingTwo" data-parent="#faqAccordion">
              <div class="card-body p-4 pt-0 text-muted" style="line-height: 1.7;">
                Yes, we provide 24/7 managed IT support and maintenance services to ensure your systems remain secure, updated, and high-performing after the initial launch.
              </div>
            </div>
          </div>
          <!-- Question 3 -->
          <div class="card border-0 mb-3 shadow-sm" style="border-radius: 12px; overflow: hidden;">
            <div class="card-header bg-white p-0 border-0" id="headingThree">
              <button class="btn btn-block text-left p-4 d-flex align-items-center justify-content-between collapsed" type="button" data-toggle="collapse" data-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree" style="font-weight: 600; font-size: 14px; color: #111827; background: transparent; border: none; box-shadow: none;">
                What is included in your Managed IT Support services?
                <i class="lni-chevron-down"></i>
              </button>
            </div>
            <div id="collapseThree" class="collapse" aria-labelledby="headingThree" data-parent="#faqAccordion">
              <div class="card-body p-4 pt-0 text-muted" style="line-height: 1.7;">
                Our Managed IT Support provides 24/7 proactive monitoring, rapid issue resolution, system updates, and robust security management. We act as your extended IT department to ensure your business operations run uninterrupted.
              </div>
            </div>
          </div>
          <!-- Question 4 -->
          <div class="card border-0 mb-3 shadow-sm" style="border-radius: 12px; overflow: hidden;">
            <div class="card-header bg-white p-0 border-0" id="headingFour">
              <button class="btn btn-block text-left p-4 d-flex align-items-center justify-content-between collapsed" type="button" data-toggle="collapse" data-target="#collapseFour" aria-expanded="false" aria-controls="collapseFour" style="font-weight: 600; font-size: 14px; color: #111827; background: transparent; border: none; box-shadow: none;">
                Do you offer solutions for new office setups or network upgrades?
                <i class="lni-chevron-down"></i>
              </button>
            </div>
            <div id="collapseFour" class="collapse" aria-labelledby="headingFour" data-parent="#faqAccordion">
              <div class="card-body p-4 pt-0 text-muted" style="line-height: 1.7;">
                Yes, we specialize in complete Network & Infrastructure solutions. From structured cabling and secure Wi-Fi deployment to router/switch configuration and scalable network design, we keep your enterprise securely connected.
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
<!-- FAQ Section End -->
<?php include 'stack.php'; ?>
<?php include 'call_action.php'; ?>
<?php include 'footer.php'; ?>

<!-- Go to Top Link -->
<a href="#" class="back-to-top">
  <i class="fas fa-chevron-up"></i>
</a>
<?php include 'script.php'; ?>
</body>
</html>