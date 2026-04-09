<!DOCTYPE html>
<html lang="en">

<head>
  <!-- Required meta tags -->
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <!-- SEO Meta Tags -->
  <title>About Us | FE IT Solutions</title>
  <meta name="description" content="Learn about FE IT Solutions, our mission, vision, and how we deliver cutting-edge IT consultancy and software development to businesses across the globe.">
  <meta property="og:title" content="About FE IT Solutions">
  <meta property="og:description" content="Discover our journey, vision, and how we empower enterprises with strategic technology solutions.">
  <meta property="og:image" content="https://feitsolutions.com/assets/img/FEIT.png">
  <meta property="og:url" content="https://feitsolutions.com/about.php">

  <?php include 'header.php'; ?>
</head>

<header id="header-wrap">
  <?php include 'navbar.php'; ?>

  <style>
    /* Hero Styling */
    .page-header-simple {
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

    .page-header-simple-img {
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

    .page-header-overlay {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: radial-gradient(circle at center, rgba(5, 5, 5, 0.4) 0%, rgba(5, 5, 5, 0.95) 100%);
      z-index: 1;
    }

    .page-header-glow {
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

    .page-header-content {
      position: relative;
      z-index: 2;
      width: 100%;
      padding: 0 24px;
      margin-top: 40px;
      text-align: center;
    }

    .page-header-simple h1 {
      font-size: clamp(38px, 5vw, 64px);
      font-weight: 600;
      line-height: 1.1;
      letter-spacing: -0.02em;
      color: #ffffff;
      margin: 0 0 16px;
      animation: fadeInUp 0.8s ease-out backwards;
    }

    .page-header-simple p {
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
  </style>

  <section class="page-header-simple">
    <img src="assets/img/pr.png" alt="About FE IT Solutions" class="page-header-simple-img">
    <div class="page-header-glow"></div>
    <div class="page-header-overlay"></div>
    <div class="page-header-content">
      <div class="container">
        <h1 class="wow fadeInDown">About Us</h1>
        <p class="wow fadeInUp" data-wow-delay="0.2s">Driving Innovation and Excellence in IT Solutions</p>
      </div>
    </div>
  </section>
  <!-- Hero about End -->
</header>

<style>
  /* Simple Aesthetics for About Page */
  body {
    font-family: 'Inter', sans-serif;
    color: #374151;
  }
  
  .section-padding {
    padding: 80px 0;
  }

  .section-title {
    font-size: clamp(28px, 4vw, 36px);
    font-weight: 700;
    color: #111827;
    margin-bottom: 20px;
  }

  .section-subtitle {
    font-size: 18px;
    color: #4b5563;
    line-height: 1.6;
    max-width: 700px;
    margin: 0 auto;
  }

  .simple-card {
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 16px;
    padding: 32px;
    height: 100%;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
  }

  .simple-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
  }

  .simple-card h3 {
    font-size: 20px;
    font-weight: 600;
    color: #111827;
    margin-bottom: 12px;
  }

  .simple-card p {
    font-size: 15px;
    color: #6b7280;
    line-height: 1.6;
    margin: 0;
  }

  .bg-light {
    background-color: #f2f2f2 !important;
  }

  .btn-common {
    background: #10B981;
    color: #ffffff !important;
    padding: 12px 24px;
    border-radius: 8px;
    font-weight: 500;
    transition: all 0.3s;
    display: inline-block;
    border: none;
  }

  .btn-common:hover {
    background: #059669;
    transform: translateY(-1px);
  }
</style>

<!-- About Content Start -->
<section class="section-padding bg-white">
  <div class="container">
    <div class="row align-items-center mb-5">
      <div class="col-lg-6 col-md-12 wow fadeInLeft" data-wow-delay="0.3s">
        <img class="img-fluid rounded-lg" src="assets/img/about-hero.jpg" alt="About FE IT Solutions">
      </div>
      <div class="col-lg-6 col-md-12 wow fadeInRight" data-wow-delay="0.3s">
        <div class="pl-lg-4">
          <h2 class="section-title">Why Choose Us</h2>
          <p class="mb-3">
            At FE IT Solutions, we combine technical expertise with a deep understanding of business needs. What sets us apart is our dedication to delivering measurable results and building lasting partnerships with every client we serve.
          </p>
          <p class="mb-3">
            We don't just provide solutions we become an extension of your team. From strategy to execution, we ensure transparency, quality, and a relentless focus on your success at every stage of the journey.
          </p>
          <a href="services.php" class="btn btn-common mt-2">See Our Services</a>
        </div>
      </div>
    </div>

    <div class="row g-4 mb-5">
      <div class="col-md-6 col-lg-3">
        <div class="simple-card wow fadeInUp" data-wow-delay="0.1s">
          <h3>01. Discover</h3>
          <p>We analyze your current systems, challenges, and goals to understand exactly what your business needs.</p>
        </div>
      </div>
      <div class="col-md-6 col-lg-3">
        <div class="simple-card wow fadeInUp" data-wow-delay="0.2s">
          <h3>02. Design</h3>
          <p>We craft tailored strategies and solution architectures that align with your objectives and budget.</p>
        </div>
      </div>
      <div class="col-md-6 col-lg-3">
        <div class="simple-card wow fadeInUp" data-wow-delay="0.3s">
          <h3>03. Develop</h3>
          <p>Our expert team builds, tests, and refines your solution using modern technologies and best practices.</p>
        </div>
      </div>
      <div class="col-md-6 col-lg-3">
        <div class="simple-card wow fadeInUp" data-wow-delay="0.4s">
          <h3>04. Deliver</h3>
          <p>We deploy, monitor, and continuously optimize your solution to ensure long-term success and growth.</p>
        </div>
      </div>
    </div>

    <div class="row align-items-center">
      <div class="col-lg-6 col-md-12 order-lg-2 wow fadeInRight" data-wow-delay="0.3s">
        <img class="img-fluid rounded-lg" src="assets/img/about/img-3.jpg" alt="Growth and Value">
      </div>
      <div class="col-lg-6 col-md-12 order-lg-1 wow fadeInLeft" data-wow-delay="0.3s">
        <div class="pr-lg-4">
          <h2 class="section-title">Our Commitment to You</h2>
          <p class="mb-3">
            We are dedicated to your success from day one. By building strong relationships, understanding your unique challenges, and adapting to your evolving needs, we ensure every solution we deliver drives real value.
          </p>
          <p class="mb-0">
            By partnering with us, you'll have access to a dedicated team of expert consultants who will guide you in making the right choices for your digital business transformation. We ensure you receive the right technology and solutions to meet your business objectives.
          </p>
        </div>
      </div>
    </div>
  </div>
</section>
<!-- About Content End -->



<?php include 'call_action.php'; ?>
<?php include 'footer.php'; ?>
<!-- Go to Top Link -->
<a href="#" class="back-to-top">
  <i class="fas fa-chevron-up"></i>
</a>

<!-- End Preloader -->
<?php include 'script.php'; ?>
</body>

</html>