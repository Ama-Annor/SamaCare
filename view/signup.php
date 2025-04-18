<?php
session_start();
$errors = $_SESSION['signup_errors'] ?? [];
$formData = $_SESSION['form_data'] ?? [];
unset($_SESSION['signup_errors'], $_SESSION['form_data']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - SamaCare</title>
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
            <a href="doctors.html">Doctors</a>
            <a href="resources.html">Resources</a>
            <a href="faq.html">FAQ</a>
            <a href="contact.html">Contact</a>
        </nav>
        <div class="auth-buttons">
            <a href="login.php" class="login-btn">Login</a>
            <a href="signup.php" class="signup-btn active">Sign Up</a>
        </div>
        <button class="mobile-menu-btn">
            <i class='bx bx-menu'></i>
        </button>
    </div>
</header>

<!-- Signup Section -->
<section class="auth-section">
    <div class="auth-container">
        <div class="auth-visual">
            <!-- ... Keep the visual content the same as before ... -->
        </div>

        <div class="auth-form-container">
            <div class="auth-header">
                <h1>Create Your Account</h1>
                <p>Join our family of users by taking control of your healthcare journey</p>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="error-messages" style="color: red; margin-bottom: 15px;">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?= htmlspecialchars($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form class="auth-form" method="POST" action="../assets/php_files/process_signup.php">
                <div class="form-row">
                    <div class="form-group">
                        <label for="firstName">First Name</label>
                        <div class="input-with-icon">
                            <i class='bx bx-user'></i>
                            <input type="text" id="firstName" name="firstName" placeholder="Enter your first name" required
                                   value="<?= htmlspecialchars($formData['firstName'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="lastName">Last Name</label>
                        <div class="input-with-icon">
                            <i class='bx bx-user'></i>
                            <input type="text" id="lastName" name="lastName" placeholder="Enter your last name" required
                                   value="<?= htmlspecialchars($formData['lastName'] ?? '') ?>">
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="email">Email Address</label>
                    <div class="input-with-icon">
                        <i class='bx bx-envelope'></i>
                        <input type="email" id="email" name="email" placeholder="Enter your email address" required
                               value="<?= htmlspecialchars($formData['email'] ?? '') ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-with-icon">
                        <i class='bx bx-lock-alt'></i>
                        <input type="password" id="password" name="password" placeholder="Create a password" required>
                        <button type="button" class="toggle-password">
                            <i class='bx bx-hide'></i>
                        </button>
                    </div>
                    <div class="password-strength">
                        <div class="strength-meter">
                            <span class="meter-section"></span>
                            <span class="meter-section"></span>
                            <span class="meter-section"></span>
                            <span class="meter-section"></span>
                        </div>
                        <span class="strength-text">Password strength</span>
                    </div>
                </div>

                <div class="form-group">
                    <label for="confirm-password">Confirm Password</label>
                    <div class="input-with-icon">
                        <i class='bx bx-lock-alt'></i>
                        <input type="password" id="confirm-password" name="confirm-password" placeholder="Confirm your password" required>
                        <button type="button" class="toggle-password">
                            <i class='bx bx-hide'></i>
                        </button>
                    </div>
                </div>

                <div class="form-group">
                    <label for="phone">Phone Number (Optional)</label>
                    <div class="input-with-icon">
                        <i class='bx bx-phone'></i>
                        <input type="tel" id="phone" name="phone" placeholder="Enter your phone number"
                               value="<?= htmlspecialchars($formData['phone'] ?? '') ?>">
                    </div>
                </div>

                <div class="form-group checkbox-group">
                    <label class="checkbox-container">
                        <input type="checkbox" required>
                        <span class="checkmark"></span>
                        I agree to the <a href="terms.html">Terms of Service</a> and <a href="privacy.html">Privacy Policy</a>
                    </label>
                </div>

                <button type="submit" class="submit-btn">
                    Create Account
                    <i class='bx bx-right-arrow-alt'></i>
                </button>
            </form>

            <div class="auth-footer">
                <p>Already have an account? <a href="login.php">Log in</a></p>
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