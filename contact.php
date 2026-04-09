<?php
require_once 'db_config.php';

function validateInput($data) {
    return htmlspecialchars(stripslashes(trim($data)));
}

$response = ['success' => false, 'message' => ''];

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    try {
        $conn = connectDB();
        $first_name = validateInput($_POST['first-name']);
        $last_name = validateInput($_POST['last-name']);
        $email = validateInput($_POST['email']);
        $company = validateInput($_POST['company']);
        $mesage = validateInput($_POST['mesage']);

        if (empty($first_name) || empty($last_name) || empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Please fill in all required fields correctly.");
        }

        $stmt = $conn->prepare("INSERT INTO user_form_data (first_name, last_name, email, company, mesage) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $first_name, $last_name, $email, $company, $mesage);
        if (!$stmt->execute()) {
            throw new Exception("Database error: " . $stmt->error);
        }
        $stmt->close();
        $response['success'] = true;
        $response['message'] = "Form submitted successfully!";
    } catch (Exception $e) {
        $response['message'] = "Error: " . $e->getMessage();
    } finally {
        if (isset($conn)) $conn->close();
    }
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <!-- SEO Meta Tags -->
  <title>Contact Us | FE IT Solutions</title>
  <meta name="description" content="Get in touch with FE IT Solutions. Our expert team is ready to discuss your business requirements for ERP software, web development, and digital marketing.">
  <meta property="og:title" content="Contact FE IT Solutions">
  <meta property="og:description" content="Reach out today to discuss custom software algorithms, ERP solutions, and web development tailored to your business.">
  <meta property="og:image" content="https://feitsolutions.com/assets/img/FEIT.png">
  <meta property="og:url" content="https://feitsolutions.com/contact.php">
  <?php include 'header.php'; ?>
  <style>
    body {
      font-family: 'Inter', sans-serif;
      background-color: #ffffff;
    }

    /* ===== Section Title ===== */
    .section-header {
      text-align: center;
      margin-bottom: 50px;
    }

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
    /* ===== Contact Section ===== */
    .contact-section {
      padding: 80px 0;
      background: #f9fafb;
    }

    /* ===== Contact Info Cards ===== */
    .contact-card {
      background: #ffffff;
      border-radius: 12px;
      padding: 24px 20px;
      text-align: center;
      height: 100%;
      border: 1px solid #e5e7eb;
      transition: all 0.3s ease;
    }

    .contact-card:hover {
      border-color: #10B981;
      transform: translateY(-5px);
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
    }

    .contact-card-icon {
      width: 48px;
      height: 48px;
      border-radius: 10px;
      background: #f0fdf4;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 12px;
    }

    .contact-card-icon i {
      font-size: 20px;
      color: #10B981;
    }

    .contact-card h5 {
      color: #111827;
      font-size: 15px;
      font-weight: 600;
      margin-bottom: 6px;
    }

    .contact-card p {
      color: #6b7280;
      font-size: 13px;
      margin-bottom: 0;
      line-height: 1.5;
    }

    /* ===== Map ===== */
    .map-wrapper {
      border-radius: 12px;
      overflow: hidden;
      height: 100%;
      min-height: 500px;
      position: relative;
      border: 1px solid #e5e7eb;
    }

    .map-wrapper iframe {
      width: 100%;
      height: 100%;
      display: block;
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
    }

    /* ===== Form ===== */
    .form-wrapper {
      background: #ffffff;
      border: 1px solid #e5e7eb;
      border-radius: 12px;
      padding: 32px 28px;
      height: 100%;
    }

    .form-title {
      color: #111827;
      font-size: 1.5rem;
      font-weight: 600;
      margin-bottom: 8px;
    }

    .form-subtitle {
      color: #6b7280;
      font-size: 14px;
      margin-bottom: 24px;
    }

    .form-label {
      color: #374151;
      font-weight: 500;
      font-size: 13px;
      margin-bottom: 6px;
    }

    .form-control {
      background-color: #ffffff;
      border: 1px solid #e5e7eb;
      border-radius: 10px;
      padding: 14px 16px;
      height: auto;
      color: #111827;
      font-size: 14px;
      transition: all 0.2s ease;
    }

    .form-control:focus {
      background-color: #ffffff;
      border-color: #10B981;
      box-shadow: none;
      color: #111827;
    }

    .form-control::placeholder {
      color: #9ca3af;
    }

    textarea.form-control {
      min-height: 120px;
      resize: vertical;
    }

    .form-field {
      margin-bottom: 16px;
    }

    .required-field::after {
      content: " *";
      color: #ef4444;
    }

    .btn-submit {
      background: #10B981;
      color: #ffffff;
      padding: 16px 32px;
      border: none;
      font-weight: 600;
      font-size: 15px;
      transition: all 0.3s ease;
      border-radius: 10px;
      width: 100%;
      cursor: pointer;
      box-shadow: 0 10px 20px rgba(16, 185, 129, 0.15);
    }

    .btn-submit:hover {
      background: #059669;
      transform: translateY(-2px);
      box-shadow: 0 15px 30px rgba(16, 185, 129, 0.2);
      color: #ffffff;
    }

    .btn-submit:disabled {
      opacity: 0.65;
      cursor: not-allowed;
    }

    /* ===== Responsive ===== */
    @media (max-width: 991px) {
      .map-wrapper {
        min-height: 400px;
      }
    }

    @media (max-width: 768px) {
      .contact-section {
        padding: 60px 0;
      }
      
      .form-wrapper {
        padding: 28px 24px;
      }
      
      .map-wrapper {
        min-height: 300px;
      }
    }
  </style>
</head>

<body>
  <header id="header-wrap">
    <?php include 'navbar.php'; ?>

    <!-- Premium Contact Hero Styling -->
    <style>
      .contact-hero {
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
      
      .contact-hero-img {
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

      .contact-overlay {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: radial-gradient(circle at center, rgba(5, 5, 5, 0.4) 0%, rgba(5, 5, 5, 0.95) 100%);
        z-index: 1;
      }

      .contact-ambient-glow {
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

      .contact-content-wrapper {
        position: relative;
        z-index: 2;
        width: 100%;
        padding: 0 24px;
        margin-top: 40px;
        text-align: center;
      }

      .contact-title {
        font-size: clamp(38px, 5vw, 64px);
        font-weight: 600;
        line-height: 1.1;
        letter-spacing: -0.02em;
        color: #ffffff;
        margin: 0 0 16px;
        animation: fadeInUp 0.8s ease-out backwards;
      }

      .contact-subtitle {
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
    </style>

    <div class="contact-hero">
      <img src="assets/img/cc.png" alt="Hero Image" class="contact-hero-img">
      <div class="contact-ambient-glow"></div>
      <div class="contact-overlay"></div>

      <div class="contact-content-wrapper">
        <div class="container">
          <h1 class="contact-title">Let's Connect</h1>
          <p class="contact-subtitle">Get in touch with our experts to discuss your custom solutions and enterprise requirements.</p>
        </div>
      </div>
    </div>
  </header>

  <!-- Map + Form + Contact Info Section -->
  <section class="contact-section">
    <div class="container">
      <!-- Contact Info Cards -->
    <div class="row g-4 mb-5">
        <div class="col-lg-3 col-md-6">
          <div class="contact-card wow fadeInUp" data-wow-delay="0.1s">
            <div class="contact-card-icon">
              <i class="lni lni-map-marker"></i>
            </div>
            <h5>Our Office</h5>
            <p>No: 04, Wijayamangalarama Road,<br>Kohuwala, Colombo</p>
          </div>
        </div>
        <div class="col-lg-3 col-md-6">
          <div class="contact-card wow fadeInUp" data-wow-delay="0.2s">
            <div class="contact-card-icon">
              <i class="lni lni-phone"></i>
            </div>
            <h5>Phone</h5>
            <p>Tel: 011 28 24 524<br>Mobile: +94 76 020 9157</p>
          </div>
        </div>
        <div class="col-lg-3 col-md-6">
          <div class="contact-card wow fadeInUp" data-wow-delay="0.3s">
            <div class="contact-card-icon">
              <i class="lni lni-envelope"></i>
            </div>
            <h5>Email</h5>
            <p>info@feitsolutions.com</p>
          </div>
        </div>
        <div class="col-lg-3 col-md-6">
          <div class="contact-card wow fadeInUp" data-wow-delay="0.4s">
            <div class="contact-card-icon">
              <i class="lni lni-timer"></i>
            </div>
            <h5>Working Hours</h5>
            <p>Mon - Fri: 9AM - 5PM<br>Sat - Sun: Closed</p>
          </div>
        </div>
      </div>
      
      <!-- Map + Form -->
      <div class="row g-4">
        <div class="col-lg-6">
          <div class="map-wrapper">
            <iframe
              src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3961.213870136491!2d79.8854542!3d6.864954799999999!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3ae25a5079323159%3A0xe8b0ef6875ebadd6!2s4%20Wijayamangalarama%20Rd%2C%20Colombo!5e0!3m2!1sen!2slk!4v1741080735236!5m2!1sen!2slk"
              frameborder="0" 
              allowfullscreen="" 
              loading="lazy" 
              referrerpolicy="no-referrer-when-downgrade">
            </iframe>
          </div>
        </div>
        <div class="col-lg-6">
          <div class="form-wrapper wow fadeInRight" data-wow-delay="0.3s">
            <h3 class="form-title">Send a Message</h3>
            <p class="form-subtitle">Fill out the form and we'll get back to you within 24 hours.</p>

            <form id="contactForm" method="post">
              <div class="row">
                <div class="col-md-6">
                  <div class="form-field">
                    <label for="first-name" class="form-label required-field">First Name</label>
                    <input type="text" class="form-control" id="first-name" name="first-name" placeholder="Your First Name" required>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-field">
                    <label for="last-name" class="form-label required-field">Last Name</label>
                    <input type="text" class="form-control" id="last-name" name="last-name" placeholder="Your Last Name" required>
                  </div>
                </div>
              </div>

              <div class="form-field">
                <label for="email" class="form-label required-field">Business Email</label>
                <input type="email" class="form-control" id="email" name="email" placeholder="Your Email" required>
              </div>

              <div class="form-field">
                <label for="company" class="form-label">Company</label>
                <input type="text" class="form-control" id="company" name="company" placeholder="Your Company Name (Optional)">
              </div>

              <div class="form-field">
                <label for="mesage" class="form-label required-field">Message</label>
                <textarea class="form-control" id="mesage" name="mesage" placeholder="How can we help you?" required></textarea>
              </div>

              <button type="submit" id="submitBtn" class="btn btn-submit">Send Message</button>
            </form>
          </div>
        </div>
      </div>
    </div>
  </section>

  <?php include 'footer.php'; ?>

  <!-- Success Modal -->
  <div class="modal fade" id="successModal" tabindex="-1" aria-labelledby="successModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content" style="background: #ffffff; color: #111827; border: none; border-radius: 16px; box-shadow: 0 20px 60px rgba(0,0,0,0.15);">
        <div class="modal-header" style="border-bottom: 1px solid #f3f4f6; padding: 20px 24px;">
          <h5 class="modal-title" id="successModalLabel" style="font-weight: 600;">Form Submission</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body" style="padding: 24px;">
          <div id="modalMessage"></div>
        </div>
        <div class="modal-footer" style="border-top: 1px solid #f3f4f6; padding: 16px 24px;">
          <button type="button" class="btn btn-submit" style="width: auto; padding: 10px 32px;" data-bs-dismiss="modal">Close</button>
        </div>
      </div>
    </div>
  </div>

  <a href="#" class="back-to-top">
    <i class="fas fa-chevron-up"></i>
  </a>

  <?php include 'script.php'; ?>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
  $(document).ready(function() {
      $("#contactForm").submit(function(e) {
          e.preventDefault();
          $.ajax({
              type: "POST",
              url: "<?php echo $_SERVER['PHP_SELF']; ?>",
              data: $(this).serialize(),
              dataType: "json",
              headers: { 'X-Requested-With': 'XMLHttpRequest' },
              beforeSend: function() {
                  $("#submitBtn").prop("disabled", true).html('Sending...');
              },
              success: function(response) {
                  if (response.success) {
                      $("#contactForm")[0].reset();
                      $("#modalMessage").html('<div class="alert alert-success mb-0" style="border-radius: 10px;">' + response.message + '</div>');
                  } else {
                      $("#modalMessage").html('<div class="alert alert-danger mb-0" style="border-radius: 10px;">' + response.message + '</div>');
                  }
                  var successModal = new bootstrap.Modal(document.getElementById('successModal'));
                  successModal.show();
                  $("#submitBtn").prop("disabled", false).html('Send Message');
              },
              error: function() {
                  $("#modalMessage").html('<div class="alert alert-danger mb-0" style="border-radius: 10px;">An error occurred. Please try again later.</div>');
                  var successModal = new bootstrap.Modal(document.getElementById('successModal'));
                  successModal.show();
                  $("#submitBtn").prop("disabled", false).html('Send Message');
              }
          });
      });
  });
  </script>
</body>
</html>