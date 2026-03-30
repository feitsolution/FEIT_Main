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
    /* ===== Section Title ===== */
    .section-header {
      text-align: center;
      margin-bottom: 50px;
    }

    .section-header .badge-label {
      display: inline-block;
      background: #eef2ff;
      color: #1a43bf;
      font-size: 13px;
      font-weight: 600;
      padding: 6px 18px;
      border-radius: 50px;
      margin-bottom: 16px;
      letter-spacing: 0.5px;
      text-transform: uppercase;
    }

    .section-header h2 {
      font-size: 2.2rem;
      font-weight: 700;
      color: #111827;
      margin-bottom: 12px;
    }

    .section-header p {
      font-size: 16px;
      color: #6b7280;
      max-width: 520px;
      margin: 0 auto;
      line-height: 1.6;
    }

    /* ===== Contact Info Cards ===== */
    .contact-info-section {
      background: #f8fafc;
      padding: 80px 0 60px;
    }

    .contact-card {
      background: #ffffff;
      border: 1px solid #e5e7eb;
      border-radius: 16px;
      padding: 32px 24px;
      text-align: center;
      transition: all 0.3s ease;
      height: 100%;
    }

    .contact-card:hover {
      border-color: #1a43bf;
      transform: translateY(-6px);
      box-shadow: 0 12px 40px rgba(26, 67, 191, 0.1);
    }

    .contact-card-icon {
      width: 60px;
      height: 60px;
      border-radius: 14px;
      background: #eef2ff;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 18px;
      transition: all 0.3s ease;
    }

    .contact-card:hover .contact-card-icon {
      background: #1a43bf;
    }

    .contact-card-icon i {
      font-size: 26px;
      color: #1a43bf;
      transition: all 0.3s ease;
    }

    .contact-card:hover .contact-card-icon i {
      color: #ffffff;
    }

    .contact-card h5 {
      color: #111827;
      font-size: 17px;
      font-weight: 600;
      margin-bottom: 8px;
    }

    .contact-card p {
      color: #6b7280;
      font-size: 14px;
      margin-bottom: 0;
      line-height: 1.7;
    }

    /* ===== Map + Form Section ===== */
    .map-form-section {
      background: #ffffff;
      padding: 0 0 80px;
    }

    .map-wrapper {
      border-radius: 20px;
      overflow: hidden;
      height: 100%;
      min-height: 560px;
      position: relative;
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

    .form-wrapper {
      background: #ffffff;
      border: 1px solid #e5e7eb;
      border-radius: 20px;
      padding: 44px 36px;
      box-shadow: 0 4px 24px rgba(0, 0, 0, 0.04);
    }

    .form-title {
      color: #111827;
      font-size: 1.75rem;
      font-weight: 700;
      margin-bottom: 6px;
    }

    .form-subtitle {
      color: #9ca3af;
      font-size: 14px;
      margin-bottom: 30px;
      line-height: 1.5;
    }

    .form-label {
      color: #374151;
      font-weight: 500;
      font-size: 13px;
      margin-bottom: 6px;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    .form-control {
      background-color: #f9fafb;
      border: 1px solid #e5e7eb;
      border-radius: 10px;
      padding: 12px 16px;
      height: auto;
      color: #111827;
      font-size: 14px;
      transition: all 0.25s ease;
    }

    .form-control:focus {
      background-color: #ffffff;
      border-color: #1a43bf;
      box-shadow: 0 0 0 3px rgba(26, 67, 191, 0.1);
      color: #111827;
    }

    .form-control::placeholder {
      color: #b0b7c3;
    }

    textarea.form-control {
      min-height: 120px;
      resize: vertical;
    }

    .form-field {
      margin-bottom: 18px;
    }

    .required-field::after {
      content: " *";
      color: #ef4444;
    }

    .btn-submit {
      background: linear-gradient(45deg, #2500f5, #0fc536);
      color: #ffffff;
      padding: 14px 48px;
      border: none;
      font-weight: 600;
      font-size: 15px;
      letter-spacing: 0.5px;
      text-transform: uppercase;
      transition: all 0.3s ease;
      border-radius: 30px;
      width: 100%;
      cursor: pointer;
    }

    .btn-submit:hover {
      background: linear-gradient(45deg, #2500f5, #0fc536);
      transform: translateY(-2px);
      box-shadow: 0 8px 25px rgba(26, 67, 191, 0.3);
      color: #ffffff;
    }

    .btn-submit:disabled {
      opacity: 0.65;
      transform: none;
      cursor: not-allowed;
    }

    /* ===== Responsive ===== */
    @media (max-width: 991px) {
      .map-wrapper {
        min-height: 380px;
      }
      .section-header h2 {
        font-size: 1.8rem;
      }
    }

    @media (max-width: 768px) {
      .form-wrapper {
        padding: 24px 16px;
      }
      .form-title {
        font-size: 1.5rem;
      }
      .contact-card {
        padding: 24px 16px;
      }
      .contact-info-section {
        padding: 40px 0 20px;
      }
      .map-form-section {
        padding: 0 0 40px;
      }
      .map-wrapper {
        min-height: 280px;
      }
      .btn-submit {
        padding: 12px 30px;
        font-size: 14px;
      }
    }
  </style>
</head>

<body>
  <header id="header-wrap">
    <?php include 'navbar.php'; ?>

    <div id="hero-area" class="hero-area-bg">
      <img src="assets/img/cc.png" alt="Hero Image" class="hero-video">
      <div class="container">
        <div class="contents">
          <h2 class="head-title">Contact Us</h2>
          <p class="head-wrap">For your unique business requirements</p>
        </div>
      </div>
    </div>
  </header>

  <!-- Contact Info Cards -->
  <section class="contact-info-section">
    <div class="container">
      <div class="row g-4">
        <div class="col-lg-3 col-md-6">
          <div class="contact-card">
            <div class="contact-card-icon">
              <i class="lni lni-map-marker"></i>
            </div>
            <h5>Our Office</h5>
            <p>No: 04, Wijayamangalarama Road,<br>Kohuwala, Colombo</p>
          </div>
        </div>
        <div class="col-lg-3 col-md-6">
          <div class="contact-card">
            <div class="contact-card-icon">
              <i class="lni lni-phone"></i>
            </div>
            <h5>Phone</h5>
            <p>Tel: 011 28 24 524<br>Mobile: +94 76 020 9157</p>
          </div>
        </div>
        <div class="col-lg-3 col-md-6">
          <div class="contact-card">
            <div class="contact-card-icon">
              <i class="lni lni-envelope"></i>
            </div>
            <h5>Email</h5>
            <p>info@feitsolutions.com</p>
          </div>
        </div>
        <div class="col-lg-3 col-md-6">
          <div class="contact-card">
            <div class="contact-card-icon">
              <i class="lni lni-timer"></i>
            </div>
            <h5>Working Hours</h5>
            <p>Mon - Fri: 9AM - 5PM<br>Sat - Sun: Closed</p>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Map + Form -->
  <section class="map-form-section">
    <div class="container">
      <div class="row g-4 align-items-stretch">
        <div class="col-lg-6 order-2 order-lg-1">
          <div class="map-wrapper">
            <iframe
              src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3961.213870136491!2d79.8854542!3d6.864954799999999!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3ae25a5079323159%3A0xe8b0ef6875ebadd6!2s4%20Wijayamangalarama%20Rd%2C%20Colombo!5e0!3m2!1sen!2slk!4v1741080735236!5m2!1sen!2slk"
              frameborder="0" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
          </div>
        </div>
        <div class="col-lg-6 order-1 order-lg-2">
          <div class="form-wrapper">
            <h3 class="form-title">Send a Message</h3>
            <p class="form-subtitle">Fill out the form and we'll get back to you within 24 hours.</p>

            <form id="contactForm" method="post">
              <div class="row">
                <div class="col-md-6">
                  <div class="form-field">
                    <label for="first-name" class="form-label required-field">First Name</label>
                    <input type="text" class="form-control" id="first-name" name="first-name" placeholder="John" required>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-field">
                    <label for="last-name" class="form-label required-field">Last Name</label>
                    <input type="text" class="form-control" id="last-name" name="last-name" placeholder="Doe" required>
                  </div>
                </div>
              </div>

              <div class="form-field">
                <label for="email" class="form-label required-field">Business Email</label>
                <input type="email" class="form-control" id="email" name="email" placeholder="john@company.com" required>
              </div>

              <div class="form-field">
                <label for="company" class="form-label">Company</label>
                <input type="text" class="form-control" id="company" name="company" placeholder="Your company name">
              </div>

              <div class="form-field">
                <label for="mesage" class="form-label required-field">Message</label>
                <textarea class="form-control" id="mesage" name="mesage" placeholder="Tell us about your project..." required></textarea>
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
    <i class="lni-arrow-up"></i>
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