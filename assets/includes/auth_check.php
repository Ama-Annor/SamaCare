<?php
/**
 * Authentication Check
 * 
 * Contains functions to check user authentication and authorization
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Checks if a user is logged in
 * 
 * @return bool True if logged in, false otherwise
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Checks if the current user is an admin
 * 
 * @return bool True if admin, false otherwise
 */
function isAdmin() {
    return isLoggedIn() && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

/**
 * Checks if the current user has a specific role
 * 
 * @param string|array $roles Role or array of roles to check
 * @return bool True if user has the role, false otherwise
 */
function hasRole($roles) {
    if (!isLoggedIn() || !isset($_SESSION['user_role'])) {
        return false;
    }
    
    if (is_array($roles)) {
        return in_array($_SESSION['user_role'], $roles);
    }
    
    return $_SESSION['user_role'] === $roles;
}

/**
 * Checks if the current user has a specific permission
 * 
 * @param string $permission Permission to check
 * @return bool True if user has the permission, false otherwise
 */
function hasPermission($permission) {
    if (!isLoggedIn() || !isset($_SESSION['user_permissions'])) {
        return false;
    }
    
    return in_array($permission, $_SESSION['user_permissions']);
}

/**
 * Requires the user to be logged in, redirects to login page if not
 * 
 * @param string $redirect URL to redirect to after login
 */
function requireLogin($redirect = null) {
    if (!isLoggedIn()) {
        if ($redirect) {
            $_SESSION['redirect_after_login'] = $redirect;
        } else {
            $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        }
        
        redirect('../login.php');
    }
}

/**
 * Requires the user to have a specific role
 * 
 * @param string|array $roles Role or array of roles required
 * @param string $redirectUrl URL to redirect to if permission denied (default: dashboard)
 */
function requireRole($roles, $redirectUrl = '../dashboard.php') {
    requireLogin();
    
    if (!hasRole($roles)) {
        setFlashMessage('error', 'You do not have permission to access this page.');
        redirect($redirectUrl);
    }
}

/**
 * Requires the user to have a specific permission
 * 
 * @param string $permission Permission required
 * @param string $redirectUrl URL to redirect to if permission denied (default: dashboard)
 */
function requirePermission($permission, $redirectUrl = '../dashboard.php') {
    requireLogin();
    
    if (!hasPermission($permission)) {
        setFlashMessage('error', 'You do not have permission to perform this action.');
        redirect($redirectUrl);
    }
}

/**
 * Gets the currently logged in user's information
 * 
 * @return array|null User data or null if not logged in
 */
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    global $conn;
    
    $userId = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        // Remove sensitive data
        unset($user['password']);
        return $user;
    }
    
    return null;
}

/**
 * Gets the current user's role name
 * 
 * @return string|null Role name or null if not logged in
 */
function getCurrentUserRole() {
    return isLoggedIn() && isset($_SESSION['user_role']) ? $_SESSION['user_role'] : null;
}

/**
 * Gets the current user's ID
 * 
 * @return int|null User ID or null if not logged in
 */
function getCurrentUserId() {
    return isLoggedIn() ? $_SESSION['user_id'] : null;
}

/**
 * Checks if the current user is the owner of a specific resource
 * 
 * @param string $table Database table to check
 * @param string $idColumn Column name for resource ID
 * @param int|string $resourceId Resource ID to check
 * @param string $userColumn Column name for user ID (default: user_id)
 * @return bool True if owner, false otherwise
 */
function isResourceOwner($table, $idColumn, $resourceId, $userColumn = 'user_id') {
    if (!isLoggedIn()) {
        return false;
    }
    
    global $conn;
    
    $userId = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT 1 FROM $table WHERE $idColumn = ? AND $userColumn = ?");
    $stmt->bind_param("ii", $resourceId, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->num_rows === 1;
}