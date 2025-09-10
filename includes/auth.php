<?php
/**
 * Authentication Functions
 * Library Management System
 */

require_once dirname(__DIR__) . '/config/database.php';

/**
 * Hash password using PHP's password_hash function
 * @param string $password
 * @return string
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * Verify password against hash
 * @param string $password
 * @param string $hash
 * @return bool
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Login user with email and password
 * @param string $email
 * @param string $password
 * @param string $userType ('user' or 'admin')
 * @return array|false
 */
function loginUser($email, $password, $userType = 'user') {
    try {
        $db = getDB();
        
        if ($userType === 'admin') {
            $sql = "SELECT admin_id as id, username, password, full_name as name, contact_email as email, 'admin' as role 
                    FROM admin WHERE username = :email";
        } else {
            $sql = "SELECT user_id as id, name, email, password, role, status 
                    FROM users WHERE email = :email AND status = 'active'";
        }
        
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        
        $user = $stmt->fetch();
        
        if ($user && verifyPassword($password, $user['password'])) {
            // Create session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $userType === 'admin' ? $user['email'] : $user['email'];
            $_SESSION['user_role'] = $userType === 'admin' ? 'admin' : $user['role'];
            $_SESSION['logged_in'] = true;
            $_SESSION['login_time'] = time();
            
            return $user;
        }
        
        return false;
        
    } catch (Exception $e) {
        error_log("Login error: " . $e->getMessage());
        return false;
    }
}

/**
 * Register new user
 * @param array $userData
 * @return bool
 */
function registerUser($userData) {
    try {
        $db = getDB();
        
        // Check if email already exists
        $checkSql = "SELECT user_id FROM users WHERE email = :email";
        $checkStmt = $db->prepare($checkSql);
        $checkStmt->bindParam(':email', $userData['email']);
        $checkStmt->execute();
        
        if ($checkStmt->rowCount() > 0) {
            return false; // Email already exists
        }
        
        // Insert new user
        $sql = "INSERT INTO users (name, email, password, role, phone, address) 
                VALUES (:name, :email, :password, :role, :phone, :address)";
        
        $stmt = $db->prepare($sql);
        $hashedPassword = hashPassword($userData['password']);
        
        $stmt->bindParam(':name', $userData['name']);
        $stmt->bindParam(':email', $userData['email']);
        $stmt->bindParam(':password', $hashedPassword);
        $stmt->bindParam(':role', $userData['role']);
        $stmt->bindParam(':phone', $userData['phone']);
        $stmt->bindParam(':address', $userData['address']);
        
        return $stmt->execute();
        
    } catch (Exception $e) {
        error_log("Registration error: " . $e->getMessage());
        return false;
    }
}

/**
 * Check if user is logged in
 * @return bool
 */
function isLoggedIn() {
    return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}

/**
 * Check if user has specific role
 * @param string $role
 * @return bool
 */
function hasRole($role) {
    return isLoggedIn() && $_SESSION['user_role'] === $role;
}

/**
 * Check if user is admin
 * @return bool
 */
function isAdmin() {
    return hasRole('admin');
}

/**
 * Check if user is librarian
 * @return bool
 */
function isLibrarian() {
    return hasRole('librarian');
}

/**
 * Check if user is student
 * @return bool
 */
function isStudent() {
    return hasRole('student');
}

/**
 * Logout user
 */
function logout() {
    session_destroy();
    session_start();
    session_regenerate_id(true);
}

/**
 * Check session timeout
 * @return bool
 */
function checkSessionTimeout() {
    if (isset($_SESSION['login_time'])) {
        if (time() - $_SESSION['login_time'] > SESSION_TIMEOUT) {
            logout();
            return true;
        }
        $_SESSION['login_time'] = time(); // Update login time
    }
    return false;
}

/**
 * Require login (redirect if not logged in)
 * @param string $redirectTo
 */
function requireLogin($redirectTo = 'auth.php') {
    if (!isLoggedIn() || checkSessionTimeout()) {
        header("Location: $redirectTo");
        exit();
    }
}

/**
 * Require admin access
 * @param string $redirectTo
 */
function requireAdmin($redirectTo = '../auth.php') {
    requireLogin($redirectTo);
    if (!isAdmin()) {
        header("Location: $redirectTo");
        exit();
    }
}

/**
 * Require librarian or admin access
 * @param string $redirectTo
 */
function requireLibrarianOrAdmin($redirectTo = '../auth.php') {
    requireLogin($redirectTo);
    if (!isLibrarian() && !isAdmin()) {
        header("Location: $redirectTo");
        exit();
    }
}

/**
 * Get current user info
 * @return array|null
 */
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM users WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
        
        if ($user) {
            // Add backward compatibility
            $user['id'] = $user['user_id'];
            return $user;
        }
    } catch (Exception $e) {
        error_log("Get current user error: " . $e->getMessage());
    }
    
    // Fallback to session data
    return [
        'user_id' => $_SESSION['user_id'],
        'id' => $_SESSION['user_id'],
        'name' => $_SESSION['user_name'],
        'email' => $_SESSION['user_email'],
        'role' => $_SESSION['user_role']
    ];
}

/**
 * Generate CSRF token
 * @return string
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 * @param string $token
 * @return bool
 */
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Sanitize input data
 * @param string $data
 * @return string
 */
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * Validate email format
 * @param string $email
 * @return bool
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate password strength
 * @param string $password
 * @return array
 */
function validatePassword($password) {
    $errors = [];
    
    if (strlen($password) < PASSWORD_MIN_LENGTH) {
        $errors[] = "Password must be at least " . PASSWORD_MIN_LENGTH . " characters long";
    }
    
    if (!preg_match('/[A-Za-z]/', $password)) {
        $errors[] = "Password must contain at least one letter";
    }
    
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = "Password must contain at least one number";
    }
    
    return [
        'valid' => empty($errors),
        'errors' => $errors
    ];
}
?>
