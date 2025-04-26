<?php
session_start();
require_once '../assets/config/db_connect.php';

$email = $password_input = "";
$email_err = $password_err = $login_err = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (empty(trim($_POST["email"]))) {
        $email_err = "Please enter your email.";
    } else {
        $email = trim($_POST["email"]);
    }

    if (empty(trim($_POST["password"]))) {
        $password_err = "Please enter your password.";
    } else {
        $password_input = trim($_POST["password"]);
    }

    if (empty($email_err) && empty($password_err)) {
        $stmt = $conn->prepare("SELECT user_id, role_id, email, password, first_name, last_name, status FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();

            if (password_verify($password_input, $user["password"])) {
                if ($user["status"] == "active") {

                    $update_stmt = $conn->prepare("UPDATE users SET last_login = NOW() WHERE user_id = ?");
                    $update_stmt->bind_param("i", $user["user_id"]);
                    $update_stmt->execute();
                    $update_stmt->close();


                    // Set session variables
                    $_SESSION["loggedin"] = true;
                    $_SESSION["user_id"] = $user["user_id"];
                    $_SESSION["email"] = $user["email"];
                    $_SESSION["role_id"] = $user["role_id"];
                    $_SESSION["first_name"] = $user["first_name"];
                    $_SESSION["last_name"] = $user["last_name"];

                    // Redirect based on role
                    switch ($user["role_id"]) {
                        case 1:
                            header("Location: admin_dashboard.php");
                            break;
                        case 2:
                            header("Location: dashboard.php");
                            break;
                        case 3: // Assuming 3 is doctor role
                            header("Location: doctor_dashboard.php");
                            break;
                        default:
                            header("Location: dashboard.php");
                    }
                    exit;
                } else {
                    $login_err = "Your account is not active. Please contact support.";
                }
            } else {
                $login_err = "Invalid email or password.";
            }
        } else {
            $login_err = "Invalid email or password.";
        }
        $stmt->close();
    }
    $conn->close();
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - SamaCare</title>
    <link rel="stylesheet" href="../assets/css/auth.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
</head>
<body>
    <!-- Top Bar -->
    <div class="top-bar">
        <div class="top-bar-content">
            <div class="contact">
                <span><i class='bx bx-phone'></i> Helpline: +233 26 977 7967</span>
                <span><i class='bx bx-envelope'></i> info@samacare.com</span>
            </div>
            <div class="social-icons">
                <a href="#"><i class='bx bxl-instagram'></i></a>
                <a href="#"><i class='bx bxl-twitter'></i></a>
                <a href="#"><i class='bx bxl-facebook'></i></a>
                <a href="#"><i class='bx bxl-linkedin'></i></a>
            </div> 
        </div>
    </div>

    <!-- Header -->
    <header class="header">
        <div class="nav-container">
            <div class="logo">
                <a href="../index.html">
                    <i class='bx bx-plus-medical'></i>SAMA<span>CARE</span>
                </a>
            </div>
            <nav>
                <a href="../index.html">Home</a>
                <a href="features.html">Features</a>
                <a href="about.html">About</a>
                <a href="faq.html">FAQ</a>
                <a href="contact.html">Contact</a>
            </nav>
            <div class="auth-buttons">
                <a href="login.php" class="login-btn active">Login</a>
                <a href="signup.php" class="signup-btn">Sign Up</a>
            </div>
            <button class="mobile-menu-btn">
                <i class='bx bx-menu'></i>
            </button>
        </div>
    </header>

    <!-- Login Section -->
    <section class="auth-section">
        <div class="auth-container login-container">
            <div class="auth-visual">
                <div class="visual-content">
                    <h2>Welcome Back!</h2>
                    <p>Log in to continue your healthcare journey with SamaCare</p>
                    
                    <div class="visual-illustration">
                        <div class="illustration-container">
                            <div class="illustration-circle circle-1"></div>
                            <div class="illustration-circle circle-2"></div>
                            <div class="illustration-icon">
                                <i class='bx bx-heart-circle'></i>
                            </div>
                        </div>
                        <p class="security-note">Our mission is to make healthcare management simpler and more personal</p>
                    </div>
                    
                    <div class="values-list">
                        <div class="value-item">
                            <i class='bx bx-check-shield'></i>
                            <span>Privacy-focused design</span>
                        </div>
                        <div class="value-item">
                            <i class='bx bx-user-voice'></i>
                            <span>Built with user feedback</span>
                        </div>
                        <div class="value-item">
                            <i class='bx bx-health'></i>
                            <span>Committed to better health outcomes</span>
                        </div>
                    </div>
                </div>
                <div class="visual-shapes">
                    <div class="shape shape-1"></div>
                    <div class="shape shape-2"></div>
                    <div class="shape shape-3"></div>
                </div>
            </div>
            
            <div class="auth-form-container">
                <div class="auth-header">
                    <h1>Log in to SamaCare</h1>
                    <p>Access your health dashboard securely</p>
                </div>
                
                <div class="social-auth">
                    <button class="social-btn google-btn">
                        <i class='bx bxl-google'></i>
                        <span>Continue with Google</span>
                    </button>
                    <button class="social-btn apple-btn">
                        <i class='bx bxl-apple'></i>
                        <span>Continue with Apple</span>
                    </button>
                </div>
                
                <div class="divider">
                    <span>or login with email</span>
                </div>
                
                <?php
                if(!empty($login_err)){
                    echo '<div class="alert alert-danger">' . $login_err . '</div>';
                }
                ?>
                
                <form class="auth-form" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">
                    <div class="form-group <?php echo (!empty($email_err)) ? 'has-error' : ''; ?>">
                        <label for="email">Email Address</label>
                        <div class="input-with-icon">
                            <i class='bx bx-envelope'></i>
                            <input type="email" id="email" name="email" placeholder="Enter your email address" value="<?php echo $email; ?>" required>
                        </div>
                        <span class="error-message"><?php echo $email_err; ?></span>
                    </div>
                    
                    <div class="form-group <?php echo (!empty($password_err)) ? 'has-error' : ''; ?>">
                        <label for="password">Password</label>
                        <div class="input-with-icon">
                            <i class='bx bx-lock-alt'></i>
                            <input type="password" id="password" name="password" placeholder="Enter your password" required>
                            <button type="button" class="toggle-password">
                                <i class='bx bx-hide'></i>
                            </button>
                        </div>
                        <span class="error-message"><?php echo $password_err; ?></span>
                    </div>
                    
                    <div class="form-row remember-forgot">
                        <div class="form-group checkbox-group">
                            <label class="checkbox-container">
                                <input type="checkbox" name="remember">
                                <span class="checkmark"></span>
                                Remember me
                            </label>
                        </div>
                    </div>
                    
                    <button type="submit" class="submit-btn">
                        Log In
                        <i class='bx bx-log-in'></i>
                    </button>
                </form>
                
                <div class="auth-footer">
                    <p>Don't have an account? <a href="signup.php">Sign up</a></p>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-bottom">
                <p>&copy; 2024 SamaCare. All rights reserved. Designed with better healthcare in mind.</p>
            </div>
        </div>
    </footer>

    <script src="../assets/js/auth.js"></script>
</body>
</html>