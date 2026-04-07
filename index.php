<!DOCTYPE html>
<html lang="en">
<head>
  <!-- Required meta tags -->
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  
  <!-- SEO Meta Tags -->
  <title>FE IT Solutions | Software Development Company in Sri Lanka</title>
  <meta name="description" content="FE IT Solutions specializes in tailored ERP systems, website development, mobile apps, SEO, and IT consultancy services for businesses in Colombo, Sri Lanka, and globally.">
  <meta name="keywords" content="IT Solutions Sri Lanka, ERP Software Colombo, Web Development Sri Lanka, SEO Agency Colombo, IT Consultancy, Custom Software Development">
  
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
    padding: 100px 0;
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
    font-size: clamp(32px, 4vw, 48px);
    font-weight: 700;
    letter-spacing: -0.02em;
    color: #111827;
    margin-bottom: 24px;
    line-height: 1.2;
  }

  /* Core Services Cards */
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
    font-size: 20px;
    font-weight: 600;
    text-decoration: none;
    transition: color 0.3s;
  }

  .services-content h3 a:hover {
    color: #10B981;
  }

  .services-content p {
    color: #6b7280;
    font-size: 15px;
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
    background-color: #f9fafb !important;
  }

  .about-area .content p {
    font-size: 17px;
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
    padding: 12px 28px;
    border-radius: 100px;
    font-weight: 500;
    font-size: 15px;
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
    font-size: 19px;
    font-weight: 600;
    margin-bottom: 10px;
  }

  .box-item .text p {
    color: #9ca3af;
    font-size: 15px;
    line-height: 1.6;
  }
</style>

<!-- Services Section Start -->
<section id="services" class="section-padding">
  <div class="container">
    <div class="section-header text-center mb-5">
      <h2 class="section-title wow fadeInDown" data-wow-delay="0.3s">We Offer All Kinds of Premium IT Solutions</h2>
    </div>
    <div class="row g-4">
      <!-- Services item -->
      <div class="col-md-6 col-lg-4 col-xs-12">
        <div class="services-item wow fadeInRight" data-wow-delay="0.3s">
          <div class="icon">
            <i class="lni-cog"></i>
          </div>
          <div class="services-content">
            <h3><a href="#"> Customized Solutions for Your Business</a></h3>
            <p>We focus on delivering tailored solutions designed to meet your specific business needs. </p>
            
          </div>
        </div>
      </div>
      <!-- Services item -->
      <div class="col-md-6 col-lg-4 col-xs-12">
        <div class="services-item wow fadeInRight" data-wow-delay="0.6s">
          <div class="icon">
            <i class="lni-stats-up"></i>
          </div>
          <div class="services-content">
            <h3><a href="#">Future-Ready and Dependable Systems</a></h3>
            <p>Our systems are built to grow with your business, ensuring scalability and reliability every step of the
              way. </p>
            
          </div>
        </div>
      </div>
      <!-- Services item -->
      <div class="col-md-6 col-lg-4 col-xs-12">
        <div class="services-item wow fadeInRight" data-wow-delay="0.9s">
          <div class="icon">
            <i class="lni-users"></i>
          </div>
          <div class="services-content">
            <h3><a href="#">Excellence in Every Strategic Endeavor</a></h3>
            <p>With a client-focused approach, we strive to achieve outstanding results and exceed expectations. </p>
            
          </div>
        </div>
      </div>

    </div>
  </div>
</section>
<!-- Services Section End -->

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
         <img class="img-fluid" src="assets/img/about/service.jpg" alt="">
      </div>
      
    </div>
  </div>
</div>
<!-- About Section End -->

<!-- Features Section Start -->
<section id="features" class="section-padding">
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
          <img src="assets/img/feature/intro-mobile3.png" alt="">
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
</section>
<!-- Features Section End -->


<?php include 'call_action.php'; ?>
<?php include 'stack.php'; ?>
<br>
<?php include 'footer.php'; ?>

<!-- Go to Top Link -->
 <a href="#" class="back-to-top">
      <i class="fas fa-chevron-up"></i>
    </a>


<?php include 'script.php'; ?>



</body>

</html>