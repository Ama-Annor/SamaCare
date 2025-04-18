<?php
//start a session 
session_start();

$host = "localhost";
$dbname = "samacare";
$username = "root";
$password = "";  


// establish connection with database

try{
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
}catch(PDOException $e) {

    // Set a user-friendly error for display
    echo $_SESSION['signup_errors'] = ["Database connection failed. Please try again later."];

    // // Redirect back to the signup form
    header("Location: ../../view/signup.php");
    exit;
}



// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Initialize errors array
    $errors = [];
    
    // Get and sanitize form data
    $firstName = trim(htmlspecialchars($_POST['firstName'] ?? ''));
    $lastName = trim(htmlspecialchars($_POST['lastName'] ?? ''));
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm-password'] ?? '';
    $phone = trim(htmlspecialchars($_POST['phone'] ?? ''));
    
    // Validation
    if (empty($firstName)) {
        $errors[] = "First name is required";
    }
    
    if (empty($lastName)) {
        $errors[] = "Last name is required";
    }
    
    if (!$email) {
        $errors[] = "Valid email address is required";
    } else {
        // Check if email already exists
        $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = "Email address is already registered";
        }
    }
    
    // Password validation
    if (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters long";
    }

    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = "Password must contain at least one uppercase letter";
    }
    
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = "Password must contain at least one number";
    }
    
    if ($password !== $confirmPassword) {
        $errors[] = "Passwords do not match";
    }
    
    // Phone validation (optional field)
    if (!empty($phone) && !preg_match('/^[0-9+\-\s()]*$/', $phone)) {
        $errors[] = "Phone number contains invalid characters";
    }
    
    // If there are errors, redirect back to the signup form with errors
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
    
    // If no errors, proceed with registration
    try {
        // Default role for new users (2 for regular users(patients))
        $roleId = 2;
        
        // Hash the password with a strong algorithm
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // Current date and time
        $dateCreated = date('Y-m-d H:i:s');
        
        // Default status
        $status = 'active';
        
        // Prepare SQL and bind parameters
        $stmt = $conn->prepare("INSERT INTO users (role_id, email, password, first_name, last_name, phone, date_created, status) 
                                VALUES (:role_id, :email, :password, :first_name, :last_name, :phone, :date_created, :status)");
        
        $stmt->bindParam(':role_id', $roleId);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':password', $hashedPassword);
        $stmt->bindParam(':first_name', $firstName);
        $stmt->bindParam(':last_name', $lastName);
        $stmt->bindParam(':phone', $phone);
        $stmt->bindParam(':date_created', $dateCreated);
        $stmt->bindParam(':status', $status);
        
        // Execute statement
        $stmt->execute();
        
        // Set success message
        $_SESSION['signup_success'] = "Your account has been created successfully. Please log in.";
        
        // Redirect to login page
        header("Location: ../../view/login.html");
        exit;
        
    } catch(PDOException $e) {
        // Log the error
        error_log("Registration error: " . $e->getMessage());

        $_SESSION['form_data'] = $_POST;  // This saves the user's input temporarily

        //Set a session variable to display the message in the sign up
        $_SESSION['signup_errors'] = ["Registration failed. Please try again later."];

        header("Location: ../../view/signup.php");
        exit;
    }
} else {
    // If not a POST request, redirect to signup page
    header("Location:../../view/signup.php");
    exit;
}
?>