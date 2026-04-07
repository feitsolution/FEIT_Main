<!-- Hero Area Start -->
<style>
  @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');

  #hero-area {
    position: relative;
    overflow: hidden;
    height: 100vh;
    display: flex;
    align-items: center;
    background-color: #050505;
    font-family: 'Inter', sans-serif;
  }
  
  .hero-slider {
    width: 100%;
    height: 100%;
    position: absolute;
    top: 0;
    left: 0;
    z-index: 0;
    opacity: 0.45;
    transition: opacity 1.5s ease-in-out;
  }
  
  .hero-slider .swiper-slide {
    width: 100%;
    height: 100%;
    background-size: cover;
    background-position: center;
    filter: grayscale(10%) contrast(105%);
  }

  /* Refined, spatial overlay */
  .hero-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: radial-gradient(circle at center, rgba(5, 5, 5, 0.4) 0%, rgba(5, 5, 5, 0.95) 100%);
    z-index: 1;
  }
  
  /* Additional ambient glow */
  .hero-ambient-glow {
    position: absolute;
    top: 50%;
    left: 50%;
    width: 60vw;
    height: 60vw;
    transform: translate(-50%, -50%);
    background: radial-gradient(circle, rgba(59, 130, 246, 0.08) 0%, rgba(0, 0, 0, 0) 70%);
    z-index: 1;
    pointer-events: none;
  }

  .hero-content-wrapper {
    position: relative;
    z-index: 2;
    width: 100%;
    padding: 0 24px;
    margin-top: 60px; /* Accounts for navbar */
  }
  
  .hero-content {
    max-width: 900px;
    margin: 0 auto;
    text-align: center;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
  }
  
  .hero-badge {
    display: inline-flex;
    align-items: center;
    padding: 6px 16px;
    background: rgba(255, 255, 255, 0.03);
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 100px;
    font-size: 13px;
    font-weight: 500;
    color: #a0a0a0;
    letter-spacing: 0.5px;
    margin-bottom: 32px;
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    animation: fadeInDown 1s ease-out;
  }
  
  .hero-badge .dot {
    width: 6px;
    height: 6px;
    background-color: #10B981;
    border-radius: 50%;
    margin-right: 8px;
    box-shadow: 0 0 8px #10B981;
  }

  .hero-content h2 {
    font-size: clamp(42px, 6vw, 76px);
    font-weight: 600;
    line-height: 1.1;
    letter-spacing: -0.03em;
    color: #ffffff;
    margin: 0 0 24px;
    animation: fadeInUp 1s ease-out 0.2s backwards;
  }
  
  /* Elegant, subtle text gradients */
  .text-gradient {
    background: linear-gradient(135deg, #ffffff 0%, #a5a5a5 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
  }
  
  .accent-gradient-blue {
    background: linear-gradient(135deg, #60A5FA 0%, #3B82F6 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
  }

  .accent-gradient-green {
    background: linear-gradient(135deg, #34D399 0%, #10B981 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
  }

  .hero-content p {
    font-size: clamp(17px, 2vw, 21px);
    font-weight: 400;
    line-height: 1.6;
    color: #9CA3AF;
    max-width: 650px;
    margin: 0 auto 48px;
    letter-spacing: -0.01em;
    animation: fadeInUp 1s ease-out 0.4s backwards;
  }
  
  .hero-buttons {
    display: flex;
    gap: 16px;
    animation: fadeInUp 1s ease-out 0.6s backwards;
  }

  .btn-premium {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    height: 52px;
    padding: 0 32px;
    font-size: 15px;
    font-weight: 500;
    border-radius: 100px;
    transition: all 0.3s ease;
    text-decoration: none;
    letter-spacing: 0.2px;
  }

  .btn-primary-elegance {
    background: #ffffff;
    color: #000000 !important;
    box-shadow: 0 4px 14px rgba(255, 255, 255, 0.1);
  }

  .btn-primary-elegance:hover {
    background: #f0f0f0;
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(255, 255, 255, 0.15);
  }

  .btn-secondary-elegance {
    background: rgba(255, 255, 255, 0.05);
    color: #ffffff !important;
    border: 1px solid rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(10px);
  }

  .btn-secondary-elegance:hover {
    background: rgba(255, 255, 255, 0.1);
    transform: translateY(-2px);
  }

  @keyframes fadeInUp {
    from {
      opacity: 0;
      transform: translateY(30px);
    }
    to {
      opacity: 1;
      transform: translateY(0);
    }
  }

  @keyframes fadeInDown {
    from {
      opacity: 0;
      transform: translateY(-20px);
    }
    to {
      opacity: 1;
      transform: translateY(0);
    }
  }

  @media (max-width: 768px) {
    .hero-content h2 {
      margin-bottom: 20px;
    }
    
    .hero-buttons {
      flex-direction: column;
      width: 100%;
      max-width: 320px;
    }
    
    .btn-premium {
      width: 100%;
    }
  }
</style>

<div id="hero-area">
  <!-- Hero Slider Background -->
  <div class="swiper hero-slider">
    <div class="swiper-wrapper">
      <div class="swiper-slide" style="background-image: url('assets/img/slider1.png');"></div>
      <div class="swiper-slide" style="background-image: url('assets/img/slider2.png');"></div>
      <div class="swiper-slide" style="background-image: url('assets/img/slider3.png');"></div>
    </div>
  </div>
  
  <!-- Spatial Overlays -->
  <div class="hero-ambient-glow"></div>
  <div class="hero-overlay"></div>

  <!-- Hero Content -->
  <div class="hero-content-wrapper">
    <div class="container">
      <div class="hero-content">
        <h2>
          Transforming Enterprises<br>
          <span class="accent-gradient-blue">Through Strategic</span><br>
          <span class="accent-gradient-green">Technology Solutions</span>
        </h2>
        
        <p>
          Architecting scalable, intelligent, and highly refined digital experiences for your unique business requirements.
        </p>
        
        <div class="hero-buttons">
          <a href="contact.php" class="btn-premium btn-primary-elegance">Meet With Us</a>
          <a href="#services" class="btn-premium btn-secondary-elegance">Explore Capabilities</a>
        </div>
      </div>
    </div>
  </div>
</div>
<!-- Hero Area End -->


<script>
  (function() {
    var initHeroSlider = function() {
      if (typeof Swiper !== 'undefined') {
        new Swiper('.hero-slider', {
          loop: true,
          autoplay: {
            delay: 6000,
            disableOnInteraction: false,
          },
          effect: 'fade',
          fadeEffect: {
            crossFade: true
          },
          speed: 1500, // slower transition for elegance
          simulateTouch: false,
          allowTouchMove: false
        });
      }
    };

    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', initHeroSlider);
    } else {
      initHeroSlider();
    }
  })();
</script>