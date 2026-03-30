<!-- Hero Area Start -->
<style>
  .hero-slider {
    width: 100%;
    height: 100%;
    position: absolute;
    top: 0;
    left: 0;
    z-index: -1;
  }
  
  .hero-slider .swiper-slide {
    width: 100%;
    height: 100%;
    background-size: cover;
    background-position: center;
  }
  
  .hero-slider .swiper-pagination-bullet {
    width: 12px;
    height: 12px;
    background: #fff;
    opacity: 0.5;
  }
  
  .hero-slider .swiper-pagination-bullet-active {
    opacity: 1;
    background: #4CAF50;
  }
  
  .hero-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    z-index: -1;
  }
  
  #hero-area {
    position: relative;
    overflow: hidden;
    height: 85vh;
    display: flex;
    align-items: center;
    padding: 125px 0 0px;
  }
  
  .hero-content {
    position: relative;
    z-index: 1;
    color: white;
    justify-content: center;
    align-items: center;
    height: 100%;
  }
  
  .hero-content h2, .hero-content p {
    color: #fff;
  }
  
  .swiper-button-next, .swiper-button-prev {
    color: #fff;
  }
  
  .hero-video {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
    z-index: -1;
    display: none;
  }
  
  /* Blue and green gradient spans */
  .blue-gradient {
    background: linear-gradient(to right, #00bcd4, #2196f3);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    padding: 0 5px;
  }
  
  .green-gradient {
    background: linear-gradient(to right, #0fb916, #8BC34A);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    padding: 0 5px;
  }
  
  /* For mobile view */
  @media (max-width: 991px) {
    #hero-area {
      height: 70vh;
      padding: 100px 0 30px;
    }
    
    .hero-content h2 {
      font-size: 28px !important;
      line-height: 1.2;
    }
    
    .hero-content p {
      font-size: 15px !important;
      margin-top: 15px;
    }
  }

  @media (max-width: 575px) {
    #hero-area {
      height: auto;
      min-height: 480px;
      padding: 120px 0 60px;
    }

    .hero-content h2 {
      font-size: 24px !important;
    }
  }
</style>

<div id="hero-area" class="hero-area-bg">
  <!-- Hero Slider -->
  <div class="swiper hero-slider">
    <div class="swiper-wrapper">
      <div class="swiper-slide" style="background-image: url('assets/img/slider1.png');"></div>
      <div class="swiper-slide" style="background-image: url('assets/img/slider2.png');"></div>
      <div class="swiper-slide" style="background-image: url('assets/img/slider3.png');"></div>
    </div>
    <!-- Add Pagination -->
    <div class="swiper-pagination"></div>
  </div>
  
  <!-- Overlay -->
  <div class="hero-overlay"></div>

  <div class="container">
    <div class="row">
      <div class="col-md-12">
        <div class="contents hero-content text-center">
          <h2 class="head-title">Transforming Enterprises <span class="blue-gradient">Through Strategic</span> <span class="green-gradient">Technology Solutions</span></h2>
          <p class="head-wrap">For your unique business requirements</p>
          <div class="header-button">
            <a href="contact.php" class="btn btn-common">Meet With Us</a>
          </div>
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
            delay: 5000,
            disableOnInteraction: false,
          },
          pagination: {
            el: '.swiper-pagination',
            clickable: true,
          },
          slidesPerView: 1,
          spaceBetween: 0,
          effect: 'fade',
          fadeEffect: {
            crossFade: true
          },
          observer: true,
          observeParents: true
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