<?php
session_start();
require_once '../../db/db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $errors = [];

    // Sanitize inputs
    $firstName = $conn->real_escape_string(trim($_POST['firstName'] ?? ''));
    $lastName = $conn->real_escape_string(trim($_POST['lastName'] ?? ''));
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm-password'] ?? '';
    $phone = $conn->real_escape_string(trim($_POST['phone'] ?? ''));

    // Validation
    if (empty($firstName)) $errors[] = "First name is required";
    if (empty($lastName)) $errors[] = "Last name is required";

    if ($email) {
        // Check if email exists
        $stmt = $conn->prepare("SELECT email FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $errors[] = "Email address is already registered";
        }
        $stmt->close();
    } else {
        $errors[] = "Valid email address is required";
    }

    // Password validation
    if (strlen($password) < 8) $errors[] = "Password must be at least 8 characters";
    if (!preg_match('/[A-Z]/', $password)) $errors[] = "Password must contain at least one uppercase letter";
    if (!preg_match('/[0-9]/', $password)) $errors[] = "Password must contain at least one number";
    if ($password !== $confirmPassword) $errors[] = "Passwords do not match";

    // Phone validation
    if (!empty($phone) && !preg_match('/^[0-9+\-\s()]*$/', $phone)) {
        $errors[] = "Phone number contains invalid characters";
    }

    if (!empty($errors)) {
        $_SESSION['signup_errors'] = $errors;
        $_SESSION['form_data'] = [
            'firstName' => $firstName,
            'lastName' => $lastName,
            'email' => $email,
            'phone' => $phone
        ];
        header("Location: ../../view/signup.php");
        exit;
    }

    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $roleId = 2; // Assuming 2 is the patient role in your roles table
    $status = 'active';

    // Insert user
    $stmt = $conn->prepare("INSERT INTO users (role_id, email, password, first_name, last_name, phone, date_created, status) 
                          VALUES (?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP, ?)");
    $stmt->bind_param("issssss", $roleId, $email, $hashedPassword, $firstName, $lastName, $phone, $status);

    if ($stmt->execute()) {
        $_SESSION['signup_success'] = "Your account has been created successfully. Please log in.";
        header("Location: ../../view/login.php");
    } else {
        error_log("Registration error: " . $stmt->error);
        $_SESSION['signup_errors'] = ["Registration failed. Please try again later."];
        header("Location: ../../view/signup.php");
    }
    $stmt->close();
    exit;
} else {
    header("Location: ../../view/signup.php");
    exit;
}
?>