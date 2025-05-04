<?php
require_once 'vendor/autoload.php';
require_once 'assets/php_files/process_signup.php';

use PHPUnit\Framework\TestCase;

/**
 * Registration Process Test Case
 * 
 * Tests for the user registration functionality
 */
class RegisterTest extends TestCase
{
    protected $mockConn;
    protected $originalPost;
    protected $originalServer;
    protected $originalSession;
    
    protected function setUp(): void
    {
        // Backup globals
        $this->originalPost = $_POST;
        $this->originalServer = $_SERVER;
        $this->originalSession = $_SESSION ?? [];
        
        // Reset globals
        $_POST = [];
        $_SERVER = [];
        $_SESSION = [];
        
        // Create mock database connection
        $this->mockConn = $this->createMock(mysqli::class);
        
        // Make the mock available globally
        global $conn;
        $conn = $this->mockConn;
    }
    
    protected function tearDown(): void
    {
        // Restore globals
        $_POST = $this->originalPost;
        $_SERVER = $this->originalServer;
        $_SESSION = $this->originalSession;
    }
    
    /**
     * Create a mock statement with configurable behavior
     */
    protected function createMockStatement($numRows = 0, $insertId = 1, $error = null, $executeResult = true)
    {
        $stmt = $this->createMock(mysqli_stmt::class);
        
        // Configure methods
        $stmt->method('bind_param')->willReturn(true);
        $stmt->method('execute')->willReturn($executeResult);
        $stmt->method('store_result')->willReturn(true);
        
        // Set number of rows property
        $stmt->num_rows = $numRows;
        
        // Set error property if provided
        if ($error !== null) {
            $stmt->error = $error;
        }
        
        return $stmt;
    }
    
    /**
     * Test for empty required fields
     */
    public function testRequiredFieldValidation()
    {
        // Set POST data with missing required fields
        $_POST = [
            'firstName' => '',
            'lastName' => '',
            'email' => 'test@example.com',
            'password' => 'Password123',
            'confirm-password' => 'Password123'
        ];
        
        $_SERVER['REQUEST_METHOD'] = 'POST';
        
        // Mock the database methods
        $this->mockConn->method('real_escape_string')->willReturnArgument(0);
        
        // Include the registration script in output buffer to prevent headers
        ob_start();
        include 'controllers/auth/register.php';
        ob_end_clean();
        
        // Check that validation errors were set in session
        $this->assertArrayHasKey('signup_errors', $_SESSION);
        $this->assertContains('First name is required', $_SESSION['signup_errors']);
        $this->assertContains('Last name is required', $_SESSION['signup_errors']);
    }
    
    /**
     * Test for invalid email format
     */
    public function testInvalidEmailFormat()
    {
        // Set POST data with invalid email
        $_POST = [
            'firstName' => 'John',
            'lastName' => 'Doe',
            'email' => 'invalid-email',
            'password' => 'Password123',
            'confirm-password' => 'Password123'
        ];
        
        $_SERVER['REQUEST_METHOD'] = 'POST';
        
        // Mock the database methods
        $this->mockConn->method('real_escape_string')->willReturnArgument(0);
        
        // Include the registration script in output buffer to prevent headers
        ob_start();
        include 'controllers/auth/register.php';
        ob_end_clean();
        
        // Check that validation errors were set in session
        $this->assertArrayHasKey('signup_errors', $_SESSION);
        $this->assertContains('Valid email address is required', $_SESSION['signup_errors']);
    }
    
    /**
     * Test for email already registered
     */
    public function testEmailAlreadyRegistered()
    {
        // Set POST data
        $_POST = [
            'firstName' => 'John',
            'lastName' => 'Doe',
            'email' => 'existing@example.com',
            'password' => 'Password123',
            'confirm-password' => 'Password123'
        ];
        
        $_SERVER['REQUEST_METHOD'] = 'POST';
        
        // Mock the database methods
        $this->mockConn->method('real_escape_string')->willReturnArgument(0);
        
        // Create a statement that returns rows (email exists)
        $stmt = $this->createMockStatement(1);
        $this->mockConn->method('prepare')->willReturn($stmt);
        
        // Include the registration script in output buffer to prevent headers
        ob_start();
        include 'controllers/auth/register.php';
        ob_end_clean();
        
        // Check that validation errors were set in session
        $this->assertArrayHasKey('signup_errors', $_SESSION);
        $this->assertContains('Email address is already registered', $_SESSION['signup_errors']);
    }
    
    /**
     * Test for password complexity requirements
     */
    public function testPasswordComplexityRequirements()
    {
        // Test cases for different password issues
        $testCases = [
            [
                'password' => 'short',
                'expectedError' => 'Password must be at least 8 characters'
            ],
            [
                'password' => 'lowercase123',
                'expectedError' => 'Password must contain at least one uppercase letter'
            ],
            [
                'password' => 'UPPERCASE',
                'expectedError' => 'Password must contain at least one number'
            ],
            [
                'password' => 'Password123',
                'confirmPassword' => 'DifferentPass123',
                'expectedError' => 'Passwords do not match'
            ]
        ];
        
        foreach ($testCases as $test) {
            // Reset session errors
            $_SESSION = [];
            
            // Set POST data
            $_POST = [
                'firstName' => 'John',
                'lastName' => 'Doe',
                'email' => 'test@example.com',
                'password' => $test['password'],
                'confirm-password' => $test['confirmPassword'] ?? $test['password']
            ];
            
            $_SERVER['REQUEST_METHOD'] = 'POST';
            
            // Mock the database methods
            $this->mockConn->method('real_escape_string')->willReturnArgument(0);
            
            // Create a statement that returns no rows (email doesn't exist)
            $stmt = $this->createMockStatement(0);
            $this->mockConn->method('prepare')->willReturn($stmt);
            
            // Include the registration script in output buffer to prevent headers
            ob_start();
            include 'controllers/auth/register.php';
            ob_end_clean();
            
            // Check that validation errors were set in session
            $this->assertArrayHasKey('signup_errors', $_SESSION, "Failed to detect: {$test['expectedError']}");
            $this->assertContains($test['expectedError'], $_SESSION['signup_errors']);
        }
    }
    
    /**
     * Test for invalid phone number format
     */
    public function testInvalidPhoneFormat()
    {
        // Set POST data with invalid phone
        $_POST = [
            'firstName' => 'John',
            'lastName' => 'Doe',
            'email' => 'test@example.com',
            'password' => 'Password123',
            'confirm-password' => 'Password123',
            'phone' => 'invalid-phone$'
        ];
        
        $_SERVER['REQUEST_METHOD'] = 'POST';
        
        // Mock the database methods
        $this->mockConn->method('real_escape_string')->willReturnArgument(0);
        
        // Create a statement that returns no rows (email doesn't exist)
        $stmt = $this->createMockStatement(0);
        $this->mockConn->method('prepare')->willReturn($stmt);
        
        // Include the registration script in output buffer to prevent headers
        ob_start();
        include 'controllers/auth/register.php';
        ob_end_clean();
        
        // Check that validation errors were set in session
        $this->assertArrayHasKey('signup_errors', $_SESSION);
        $this->assertContains('Phone number contains invalid characters', $_SESSION['signup_errors']);
    }
    
    /**
     * Test successful patient registration
     */
    public function testSuccessfulPatientRegistration()
    {
        // Set POST data for a regular user (patient)
        $_POST = [
            'firstName' => 'John',
            'lastName' => 'Doe',
            'email' => 'john.doe@example.com',
            'password' => 'Password123',
            'confirm-password' => 'Password123',
            'phone' => '123-456-7890'
        ];
        
        $_SERVER['REQUEST_METHOD'] = 'POST';
        
        // Mock the database methods
        $this->mockConn->method('real_escape_string')->willReturnArgument(0);
        $this->mockConn->method('begin_transaction')->willReturn(true);
        $this->mockConn->method('commit')->willReturn(true);
        $this->mockConn->insert_id = 42; // New user ID
        
        // Mock statements for email check and insert operations
        $emailCheckStmt = $this->createMockStatement(0); // Email doesn't exist
        $userInsertStmt = $this->createMockStatement(0, 42); // Insert user
        $patientInsertStmt = $this->createMockStatement(); // Insert patient
        
        // Make prepare return different statements based on the query
        $this->mockConn->method('prepare')->willReturnCallback(
            function($query) use ($emailCheckStmt, $userInsertStmt, $patientInsertStmt) {
                if (strpos($query, 'SELECT email') === 0) {
                    return $emailCheckStmt;
                } elseif (strpos($query, 'INSERT INTO users') === 0) {
                    return $userInsertStmt;
                } elseif (strpos($query, 'INSERT INTO patients') === 0) {
                    return $patientInsertStmt;
                }
                return $this->createMockStatement();
            }
        );
        
        // Include the registration script in output buffer to prevent headers
        ob_start();
        include 'controllers/auth/register.php';
        ob_end_clean();
        
        // Check for success message
        $this->assertArrayHasKey('signup_success', $_SESSION);
        $this->assertStringContainsString('successfully', $_SESSION['signup_success']);
    }
    
    /**
     * Test successful doctor registration
     */
    public function testSuccessfulDoctorRegistration()
    {
        // Set POST data for a doctor user (with .doc@gmail.com)
        $_POST = [
            'firstName' => 'Jane',
            'lastName' => 'Smith',
            'email' => 'jane.smith.doc@gmail.com',
            'password' => 'Password123',
            'confirm-password' => 'Password123',
            'phone' => '123-456-7890'
        ];
        
        $_SERVER['REQUEST_METHOD'] = 'POST';
        
        // Mock the database methods
        $this->mockConn->method('real_escape_string')->willReturnArgument(0);
        $this->mockConn->method('begin_transaction')->willReturn(true);
        $this->mockConn->method('commit')->willReturn(true);
        $this->mockConn->insert_id = 43; // New user ID
        
        // Mock statements for email check and insert operations
        $emailCheckStmt = $this->createMockStatement(0); // Email doesn't exist
        $userInsertStmt = $this->createMockStatement(0, 43); // Insert user
        $doctorInsertStmt = $this->createMockStatement(); // Insert doctor
        
        // Make prepare return different statements based on the query
        $this->mockConn->method('prepare')->willReturnCallback(
            function($query) use ($emailCheckStmt, $userInsertStmt, $doctorInsertStmt) {
                if (strpos($query, 'SELECT email') === 0) {
                    return $emailCheckStmt;
                } elseif (strpos($query, 'INSERT INTO users') === 0) {
                    return $userInsertStmt;
                } elseif (strpos($query, 'INSERT INTO doctors') === 0) {
                    return $doctorInsertStmt;
                }
                return $this->createMockStatement();
            }
        );
        
        // Include the registration script in output buffer to prevent headers
        ob_start();
        include 'controllers/auth/register.php';
        ob_end_clean();
        
        // Check for success message
        $this->assertArrayHasKey('signup_success', $_SESSION);
        $this->assertStringContainsString('successfully', $_SESSION['signup_success']);
    }
    
    /**
     * Test successful admin registration
     */
    public function testSuccessfulAdminRegistration()
    {
        // Set POST data for an admin user (with .admin@gmail.com)
        $_POST = [
            'firstName' => 'Admin',
            'lastName' => 'User',
            'email' => 'admin.user.admin@gmail.com',
            'password' => 'Password123',
            'confirm-password' => 'Password123',
            'phone' => '123-456-7890'
        ];
        
        $_SERVER['REQUEST_METHOD'] = 'POST';
        
        // Mock the database methods
        $this->mockConn->method('real_escape_string')->willReturnArgument(0);
        $this->mockConn->method('begin_transaction')->willReturn(true);
        $this->mockConn->method('commit')->willReturn(true);
        $this->mockConn->insert_id = 44; // New user ID
        
        // Mock statements for email check and insert operations
        $emailCheckStmt = $this->createMockStatement(0); // Email doesn't exist
        $userInsertStmt = $this->createMockStatement(0, 44); // Insert user
        
        // Make prepare return different statements based on the query
        $this->mockConn->method('prepare')->willReturnCallback(
            function($query) use ($emailCheckStmt, $userInsertStmt) {
                if (strpos($query, 'SELECT email') === 0) {
                    return $emailCheckStmt;
                } elseif (strpos($query, 'INSERT INTO users') === 0) {
                    return $userInsertStmt;
                }
                return $this->createMockStatement();
            }
        );
        
        // Include the registration script in output buffer to prevent headers
        ob_start();
        include 'controllers/auth/register.php';
        ob_end_clean();
        
        // Check for success message
        $this->assertArrayHasKey('signup_success', $_SESSION);
        $this->assertStringContainsString('successfully', $_SESSION['signup_success']);
    }
    
    /**
     * Test database error during registration
     */
    public function testDatabaseErrorDuringRegistration()
    {
        // Set POST data
        $_POST = [
            'firstName' => 'John',
            'lastName' => 'Doe',
            'email' => 'john.doe@example.com',
            'password' => 'Password123',
            'confirm-password' => 'Password123',
            'phone' => '123-456-7890'
        ];
        
        $_SERVER['REQUEST_METHOD'] = 'POST';
        
        // Mock the database methods
        $this->mockConn->method('real_escape_string')->willReturnArgument(0);
        $this->mockConn->method('begin_transaction')->willReturn(true);
        $this->mockConn->method('rollback')->willReturn(true);
        
        // Mock statements for email check and insert operations
        $emailCheckStmt = $this->createMockStatement(0); // Email doesn't exist
        $userInsertStmt = $this->createMockStatement(0, 0, "Database error", false); // Insert fails
        
        // Make prepare return different statements based on the query
        $this->mockConn->method('prepare')->willReturnCallback(
            function($query) use ($emailCheckStmt, $userInsertStmt) {
                if (strpos($query, 'SELECT email') === 0) {
                    return $emailCheckStmt;
                } elseif (strpos($query, 'INSERT INTO users') === 0) {
                    return $userInsertStmt;
                }
                return $this->createMockStatement();
            }
        );
        
        // Include the registration script in output buffer to prevent headers
        ob_start();
        include 'controllers/auth/register.php';
        ob_end_clean();
        
        // Check for error message
        $this->assertArrayHasKey('signup_errors', $_SESSION);
        $this->assertStringContainsString('Registration failed', $_SESSION['signup_errors'][0]);
    }
}