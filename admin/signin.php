<?php
session_start(); // Start the session at the very beginning
include 'db_connection.php'; // Include the database connection file

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Initialize error message variable
$error_message = "";

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize inputs
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    $remember = isset($_POST['remember']); // Check if "Remember Me" is checked

    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Invalid email format";
    } else {
        // Query to check if user exists with the given email
        $sql = "SELECT * FROM users WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        // Check if user exists
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();

            // Check if user is active
            if ($user['status'] != 'active') {
                $error_message = "Your account is inactive. Please contact support.";
            } else {
                // Verify the hashed password
                if (password_verify($password, $user['password']) || $password == $user['password']) { // Second condition for testing only, remove in production
                    // Password is correct, start session
                    $_SESSION['user'] = $email;
                    $_SESSION['user_id'] = $user['id']; // Storing user ID in session
                    $_SESSION['role_id'] = $user['role_id']; // Storing role ID
                    $_SESSION['name'] = $user['name']; // Storing user name
                    $_SESSION['logged_in'] = true;

                    // Handle "Remember Me" by setting cookies
                    if ($remember) {
                        setcookie("email", $email, time() + (86400 * 30), "/"); // 30 days
                    } else {
                        // Clear cookie if "Remember Me" is unchecked
                        setcookie("email", "", time() - 3600, "/");
                    }

                    // Redirect based on user role
                    switch ($user['role_id']) {
                        case 1: // Superadmin
                            header("Location: index.php");
                            break;
                        case 2: // Regular user
                            header("Location: index.php"); // Fixed missing page name
                            break;
                        case 3: // Other user type
                            header("Location: index.php"); // Fixed missing page name
                            break;
                        default:
                            header("Location: index.php"); // Fixed missing page name
                    }
                    exit();
                } else {
                    $error_message = "Invalid password.";
                }
            }
        } else {
            $error_message = "No user found with that email.";
        }

        $stmt->close();
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
<?php include('header.php'); ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In</title>
    <!-- FAVICON -->
    <link rel="icon" href="img/system/letter-f.png" type="image/png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #0a0e27 0%, #1a1f4e 40%, #0d2137 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
        }

        .signin-container {
            display: flex;
            width: 960px;
            max-width: 100%;
            background-color: #fff;
            border-radius: 16px;
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            animation: fadeInUp 0.6s ease-out;
        }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* LEFT PANEL */
        .signin-left {
            flex: 1;
            background: linear-gradient(160deg, #0b1a3d 0%, #162d6b 50%, #1a3a8f 100%);
            color: #fff;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 50px 40px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .signin-left::before {
            content: '';
            position: absolute;
            top: -60px;
            right: -60px;
            width: 200px;
            height: 200px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.04);
        }

        .signin-left::after {
            content: '';
            position: absolute;
            bottom: -40px;
            left: -40px;
            width: 150px;
            height: 150px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.03);
        }

        .signin-left .deco-dots {
            position: absolute;
            top: 30px;
            left: 30px;
            display: grid;
            grid-template-columns: repeat(4, 6px);
            gap: 10px;
        }

        .signin-left .deco-dots span {
            width: 6px;
            height: 6px;
            background: rgba(255,255,255,0.15);
            border-radius: 50%;
        }

        .signin-left .deco-line {
            position: absolute;
            bottom: 40px;
            right: 40px;
            width: 50px;
            height: 3px;
            background: rgba(255,255,255,0.1);
            border-radius: 2px;
        }

        .hero-image {
            width: 180px;
            height: auto;
            margin-bottom: 28px;
            border-radius: 12px;
            filter: drop-shadow(0 8px 20px rgba(0,0,0,0.3));
            animation: float 3s ease-in-out infinite;
        }

        .hero-image {
            width: 280px;
            height: auto;
            margin-bottom: 28px;
            border-radius: 12px;
            filter: drop-shadow(0 8px 20px rgba(0,0,0,0.3));
        }

        .signin-left h2 {
            font-size: 28px;
            font-weight: 700;
            margin: 0;
            letter-spacing: -0.5px;
        }

        .signin-left p {
            font-size: 15px;
            margin-top: 10px;
            color: rgba(255,255,255,0.7);
            font-weight: 300;
            line-height: 1.5;
        }

        /* RIGHT PANEL */
        .signin-right {
            flex: 1;
            padding: 50px 45px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .signin-right h2 {
            font-size: 26px;
            text-align: left;
            margin: 0 0 6px 0;
            color: #1a1a2e;
            font-weight: 700;
            letter-spacing: -0.5px;
        }

        .signin-right .subtitle {
            font-size: 14px;
            color: #888;
            margin-bottom: 28px;
            text-align: left;
        }

        .input-group {
            margin-bottom: 18px;
        }

        .input-group label {
            font-size: 13px;
            font-weight: 600;
            color: #444;
            margin-bottom: 6px;
            display: block;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .input-icon-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-icon-wrapper i {
            position: absolute;
            left: 14px;
            color: #aaa;
            font-size: 14px;
            transition: color 0.3s;
            z-index: 1;
        }

        .input-icon-wrapper input {
            width: 100%;
            padding: 13px 14px 13px 40px;
            font-size: 15px;
            border: 2px solid #e8e8e8;
            border-radius: 10px;
            transition: all 0.3s ease;
            background: #fafafa;
            font-family: 'Inter', sans-serif;
        }

        .input-icon-wrapper input:focus {
            border-color: #3b5bdb;
            outline: none;
            background: #fff;
            box-shadow: 0 0 0 4px rgba(59, 91, 219, 0.08);
        }

        .input-icon-wrapper input:focus ~ i,
        .input-icon-wrapper input:focus + i {
            color: #3b5bdb;
        }

        /* Password */
        .password-container {
            position: relative;
            display: flex;
            align-items: center;
        }

        .password-container .input-icon-wrapper {
            flex: 1;
        }

        .password-toggle {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #aaa;
            z-index: 2;
            background: none;
            border: none;
            padding: 4px;
            font-size: 15px;
            transition: color 0.3s;
        }

        .password-toggle:hover {
            color: #3b5bdb;
        }

        .password-container input {
            padding-right: 46px;
        }

        .input-group label a {
            font-size: 13px;
            color: #3b5bdb;
            text-decoration: none;
            font-weight: 500;
        }

        .input-group label a:hover {
            text-decoration: underline;
        }

        .input-group.remember-me {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 22px;
        }

        .input-group.remember-me label {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            color: #666;
            font-weight: 400;
            text-transform: none;
            letter-spacing: 0;
            cursor: pointer;
        }

        .input-group.remember-me input[type="checkbox"] {
            width: 16px;
            height: 16px;
            accent-color: #3b5bdb;
            cursor: pointer;
        }

        .input-group.remember-me a {
            font-size: 13px;
            color: #3b5bdb;
            text-decoration: none;
            font-weight: 500;
        }

        .signin-right button {
            width: 100%;
            padding: 14px;
            font-size: 15px;
            font-weight: 600;
            background: linear-gradient(135deg, #3b5bdb, #5c7cfa);
            color: white;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: 'Inter', sans-serif;
            letter-spacing: 0.3px;
            box-shadow: 0 4px 15px rgba(59, 91, 219, 0.3);
        }

        .signin-right button:hover {
            background: linear-gradient(135deg, #364fc7, #4c6ef5);
            box-shadow: 0 6px 20px rgba(59, 91, 219, 0.4);
            transform: translateY(-1px);
        }

        .signin-right button:active {
            transform: translateY(0);
        }

        .signin-right p {
            text-align: center;
            margin-top: 22px;
            font-size: 14px;
            color: #888;
        }

        .signin-right p a {
            color: #3b5bdb;
            text-decoration: none;
            font-weight: 600;
        }

        .error-message {
            color: #e03131;
            background-color: #fff5f5;
            border: 1px solid #ffc9c9;
            border-radius: 10px;
            padding: 12px 16px;
            margin-bottom: 20px;
            text-align: left;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .error-message i {
            font-size: 16px;
            flex-shrink: 0;
        }

        @media (max-width: 768px) {
            .signin-container {
                flex-direction: column;
            }
            .signin-left {
                padding: 35px 25px;
            }
            .signin-right {
                padding: 35px 25px;
            }
        }
    </style>
</head>

<body>
    <div class="signin-container">
        <div class="signin-left">
            <div class="deco-dots">
                <span></span><span></span><span></span><span></span>
                <span></span><span></span><span></span><span></span>
                <span></span><span></span><span></span><span></span>
                <span></span><span></span><span></span><span></span>
            </div>
            <div class="deco-line"></div>
            <img src="img/system/FEIT.png" alt="Logo" class="hero-image">
            <p>Sign in to access your dashboard<br>and manage account.</p>
        </div>
        <div class="signin-right">
            <h2>Sign In</h2>
            <p class="subtitle">Enter your credentials to continue</p>
            <?php if (!empty($error_message)): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">
                <div class="input-group">
                    <label for="email">Email</label>
                    <div class="input-icon-wrapper">
                        <i class="fas fa-envelope"></i>
                        <input type="email" id="email" name="email"
                            value="<?php echo isset($_COOKIE['email']) ? $_COOKIE['email'] : ''; ?>" placeholder="you@example.com" required>
                    </div>
                </div>
                <div class="input-group">
                    <label for="password">Password</label>
                    <div class="password-container">
                        <div class="input-icon-wrapper">
                            <i class="fas fa-lock"></i>
                            <input type="password" id="password" name="password" placeholder="Enter your password" required>
                        </div>
                        <span class="password-toggle" id="togglePassword">
                            <i class="fas fa-eye"></i>
                        </span>
                    </div>
                </div>
                <div class="input-group remember-me">
                    <label>
                        <input type="checkbox" name="remember" <?php echo isset($_COOKIE['email']) ? 'checked' : ''; ?>>
                        Remember me
                    </label>
                    <!-- <a href="forgot_password.php">Forgot Password?</a> -->
                </div>
                <button type="submit">Sign In</button>
            </form>
            <!-- <p>Need an account? <a href="signup.php">Sign up!</a></p> -->
        </div>
    </div>

    <script>
        // JavaScript to toggle password visibility
        document.addEventListener('DOMContentLoaded', function() {
            const togglePassword = document.getElementById('togglePassword');
            const passwordInput = document.getElementById('password');

            togglePassword.addEventListener('click', function() {
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                
                // Toggle the eye icon
                const icon = this.querySelector('i');
                icon.classList.toggle('fa-eye');
                icon.classList.toggle('fa-eye-slash');
            });
        });
    </script>
</body>

</html>