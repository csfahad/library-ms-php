<?php
/* Database Configuration File - 
 * This file contains database connection settings and configuration
*/

// Database Configuration - Docker Environment Support
define('DB_HOST', $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?: 'mysql');
define('DB_NAME', $_ENV['DB_NAME'] ?? getenv('DB_NAME') ?: 'library_management_system');
define('DB_USER', $_ENV['DB_USER'] ?? getenv('DB_USER') ?: 'lms_user');
define('DB_PASS', $_ENV['DB_PASS'] ?? getenv('DB_PASS') ?: 'lms_password_123');
define('DB_CHARSET', 'utf8mb4');

// System Configuration
define('SITE_NAME', 'Library Management System');
define('SITE_URL', $_ENV['SITE_URL'] ?? getenv('SITE_URL') ?: 'http://localhost:8000');
define('ADMIN_EMAIL', 'admin@library.com');

// Redis Configuration for Sessions (Docker)
define('REDIS_HOST', $_ENV['REDIS_HOST'] ?? getenv('REDIS_HOST') ?: 'redis');
define('REDIS_PORT', $_ENV['REDIS_PORT'] ?? getenv('REDIS_PORT') ?: 6379);

// Security Configuration
define('SESSION_TIMEOUT', 3600); // 1 hour
define('PASSWORD_MIN_LENGTH', 6);
define('MAX_LOGIN_ATTEMPTS', 3);

// Library Configuration
define('MAX_BOOKS_PER_USER', 3);
define('DEFAULT_ISSUE_DAYS', 14);
define('FINE_PER_DAY', 2.00);

// File Upload Configuration
define('MAX_FILE_SIZE', 5242880); // 5MB in bytes
define('UPLOAD_PATH', 'uploads/');

// Environment Detection
define('IS_DOCKER', file_exists('/.dockerenv'));

// Database Connection Class
class Database {
    private $host = DB_HOST;
    private $db_name = DB_NAME;
    private $username = DB_USER;
    private $password = DB_PASS;
    private $charset = DB_CHARSET;
    public $conn;

    /**
     * Get database connection
     * @return PDO|null
     */
    public function getConnection() {
        $this->conn = null;
        
        try {
            $dsn = "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=" . $this->charset;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            
            $this->conn = new PDO($dsn, $this->username, $this->password, $options);
            
        } catch(PDOException $exception) {
            error_log("Connection error: " . $exception->getMessage());
            die("Database connection failed. Please try again later.");
        }
        
        return $this->conn;
    }

    /* Close database connection */
    public function closeConnection() {
        $this->conn = null;
    }
}

// Function to get database instance
function getDB() {
    $database = new Database();
    return $database->getConnection();
}

// Set timezone
date_default_timezone_set('America/New_York');

// Error reporting (set to 0 in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
