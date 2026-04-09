<!-- Footer Section Start -->
<style>
  .site-footer {
    font-family: 'Inter', sans-serif;
    background: #111827;
    padding: 80px 0 0;
  }

  .site-footer .footer-logo img {
    height: 55px;
    width: auto;
    margin-bottom: 20px;
    display: block;
  }

  .footer-logo-wrap {
    text-align: right;
    display: flex;
    align-items: flex-end;
    justify-content: flex-end;
  }

  @media (max-width: 991px) {
    .footer-logo-wrap {
      text-align: left;
      justify-content: flex-start;
      margin-top: 16px;
    }
  }

  .site-footer .footer-desc {
    font-size: 13px;
    color: #9ca3af;
    line-height: 1.7;
    margin-bottom: 20px;
  }

  .footer-socials {
    display: flex;
    gap: 8px;
  }

  .footer-socials a {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.06);
    border: 1px solid rgba(255, 255, 255, 0.08);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #9ca3af;
    font-size: 14px;
    text-decoration: none;
    transition: all 0.3s ease;
  }

  .footer-socials a:hover {
    background: #10B981;
    border-color: #10B981;
    color: #ffffff;
  }

  .footer-col-title {
    font-size: 12px;
    font-weight: 700;
    letter-spacing: 1px;
    text-transform: uppercase;
    color: #ffffff;
    margin-bottom: 16px;
  }

  .footer-links {
    list-style: none;
    padding: 0;
    margin: 0;
  }

  .footer-links li {
    margin-bottom: 10px;
    font-size: 13px;
    color: #9ca3af;
  }

  .footer-links a {
    font-size: 13px;
    color: #9ca3af;
    text-decoration: none;
    transition: color 0.2s ease;
  }

  .footer-links a:hover {
    color: #10B981;
  }

  .footer-contact-text {
    font-size: 13px;
    color: #9ca3af;
  }

  .footer-contact-text a {
    color: #9ca3af;
    text-decoration: none;
  }

  .footer-contact-text a:hover {
    color: #10B981;
  }

  .footer-divider {
    border: none;
    border-top: 1px solid rgba(255, 255, 255, 0.07);
    margin: 40px 0 0;
  }

  .footer-bottom {
    padding: 18px 0;
    text-align: center;
  }

  .footer-bottom p {
    font-size: 12px;
    color: #6b7280;
    margin: 0;
  }

  .footer-bottom-logo img {
    height: 36px;
    width: auto;
    opacity: 0.85;
    transition: opacity 0.3s ease;
  }

  .footer-bottom-logo img:hover {
    opacity: 1;
  }

  .footer-bottom .brand-accent {
    color: #10B981;
    font-weight: 600;
  }

  @media (max-width: 767px) {
    .site-footer {
      padding: 40px 0 0;
    }
    .site-footer .container {
      padding-left: 20px;
      padding-right: 20px;
    }
    .site-footer .footer-logo img {
      height: 45px;
      margin-bottom: 12px;
    }
    .footer-col-title {
      font-size: 11px;
      margin-bottom: 12px;
    }
    .footer-links a,
    .footer-contact-text {
      font-size: 12px;
    }
    .footer-socials {
      justify-content: flex-start;
    }
    .footer-bottom {
      text-align: center;
    }
    .footer-bottom p {
      font-size: 11px;
    }
  }
</style>

<footer class="site-footer">
  <div class="container">
    <div class="row">

      <div class="col-lg-4 col-md-6 mb-4 mb-lg-0">
        <div class="footer-logo">
          <a href="index.php"><img src="assets/img/FEIT.png" alt="FEIT Solutions"></a>
        </div>
        <p class="footer-desc">Empowering businesses with innovative IT solutions and digital transformation.</p>
        <div class="footer-socials">
          <a href="https://www.facebook.com/feitsolutions" aria-label="Facebook"><i class="lni-facebook-filled"></i></a>
          <a href="https://www.linkedin.com/company/fe-it-solutions" aria-label="LinkedIN"><i class="lni-linkedin-filled"></i></a>
        </div>
      </div>

      <div class="col-lg-2 col-md-6 mb-4 mb-lg-0">
        <h4 class="footer-col-title">Company</h4>
        <ul class="footer-links">
          <li><a href="index.php">Home</a></li>
          <li><a href="about.php">About</a></li>
          <li><a href="services.php">Services</a></li>
          <li><a href="contact.php">Contact</a></li>
        </ul>
      </div>

      <div class="col-lg-3 col-md-6 mb-4 mb-lg-0">
        <h4 class="footer-col-title">Contact</h4>
        <p class="footer-contact-text">
          Kohuwala, Sri Lanka<br>
          <a href="tel:+94112824524">011-28 24 524</a><br>
          <a href="mailto:info@feitsolutions.com">info@feitsolutions.com</a>
        </p>
      </div>

      <div class="col-lg-3 col-md-6 mb-4 mb-lg-0">
        <h4 class="footer-col-title">Services</h4>
        <ul class="footer-links">
          <li>Cloud Services</li>
          <li>Managed IT Support</li>
          <li>Network & Infrastructure</li>
          <li>Custom Software Development</li>
          <li>Business Automation</li>
        </ul>
      </div>
    </div>
  </div>

  <hr class="footer-divider">

  <div class="footer-bottom">
    <div class="container">
      <p>&copy; <?php echo date('Y'); ?> <span class="brand-accent">FEIT Solutions</span>. All Rights Reserved.</p>
    </div>
  </div>
</footer>

<!-- WhatsApp Floating Button -->
<div class="whatsapp-float-container">
    <div class="whatsapp-pulse"></div>
    <a href="https://wa.me/94767345224?text=Hello!%20I'm%20interested%20in%20your%20services%20and%20would%20like%20to%20know%20more." 
       target="_blank" 
       class="whatsapp-button">
        <div class="wa-notification"></div>
        <span class="wa-tooltip">Chat with us</span>
        <svg viewBox="0 0 24 24">
            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z" />
        </svg>
    </a>
</div>
<!-- Footer Section End -->