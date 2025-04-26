<?php
/**
 * Common Functions
 * 
 * Contains utility functions used throughout the application
 */

/**
 * Sanitizes input data to prevent SQL injection and XSS attacks
 * 
 * @param string $data The input to sanitize
 * @return string Sanitized input
 */
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * Validates date in YYYY-MM-DD format
 * 
 * @param string $date The date string to validate
 * @return bool True if valid, false otherwise
 */
function isValidDate($date) {
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        $parts = explode('-', $date);
        return checkdate($parts[1], $parts[2], $parts[0]);
    }
    return false;
}

/**
 * Validates time in HH:MM:SS or HH:MM format
 * 
 * @param string $time The time string to validate
 * @return bool True if valid, false otherwise
 */
function isValidTime($time) {
    return preg_match('/^([01][0-9]|2[0-3]):([0-5][0-9])(:([0-5][0-9]))?$/', $time);
}

/**
 * Formats a date for display
 * 
 * @param string $date Date in YYYY-MM-DD format
 * @param string $format Format string for date()
 * @return string Formatted date
 */
function formatDate($date, $format = 'M d, Y') {
    return date($format, strtotime($date));
}

/**
 * Formats a time for display
 * 
 * @param string $time Time in HH:MM:SS format
 * @param bool $use12Hour Whether to use 12-hour format with AM/PM
 * @return string Formatted time
 */
function formatTime($time, $use12Hour = true) {
    if ($use12Hour) {
        return date('h:i A', strtotime($time));
    }
    return date('H:i', strtotime($time));
}

/**
 * Generates a random string of specified length
 * 
 * @param int $length Length of the random string
 * @return string Random string
 */
function generateRandomString($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}

/**
 * Logs an error message to error log file
 * 
 * @param string $message Error message
 * @param string $level Error level (ERROR, WARNING, INFO)
 */
function logError($message, $level = 'ERROR') {
    $logFile = '../logs/app_errors.log';
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] [$level] $message" . PHP_EOL;
    
    // Make sure directory exists
    $logDir = dirname($logFile);
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    error_log($logMessage, 3, $logFile);
}

/**
 * Gets pagination information
 * 
 * @param int $totalItems Total number of items
 * @param int $itemsPerPage Number of items per page
 * @param int $currentPage Current page number
 * @return array Pagination information
 */
function getPaginationInfo($totalItems, $itemsPerPage, $currentPage) {
    $totalPages = ceil($totalItems / $itemsPerPage);
    $currentPage = max(1, min($currentPage, $totalPages));
    $offset = ($currentPage - 1) * $itemsPerPage;
    
    return [
        'total_items' => $totalItems,
        'items_per_page' => $itemsPerPage,
        'current_page' => $currentPage,
        'total_pages' => $totalPages,
        'offset' => $offset
    ];
}

/**
 * Gets the current URL
 * 
 * @return string Current URL
 */
function getCurrentUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $uri = $_SERVER['REQUEST_URI'];
    return "$protocol://$host$uri";
}

/**
 * Redirects to a specified URL
 * 
 * @param string $url URL to redirect to
 */
function redirect($url) {
    header("Location: $url");
    exit;
}

/**
 * Sets a flash message to be displayed on the next page load
 * 
 * @param string $type Message type (success, error, warning, info)
 * @param string $message The message content
 */
function setFlashMessage($type, $message) {
    if (!isset($_SESSION)) {
        session_start();
    }
    $_SESSION['flash_message'] = [
        'type' => $type,
        'message' => $message
    ];
}

/**
 * Gets and clears the flash message
 * 
 * @return array|null Flash message or null if none exists
 */
function getFlashMessage() {
    if (!isset($_SESSION)) {
        session_start();
    }
    
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $message;
    }
    
    return null;
}

/**
 * Checks if a string contains some text
 * 
 * @param string $haystack String to search in
 * @param string $needle String to search for
 * @return bool True if found, false otherwise
 */
function stringContains($haystack, $needle) {
    return strpos($haystack, $needle) !== false;
}