<?php
/**
 * Database Configuration
 * Audio Course Platform
 */

// Prevent direct access
if (!defined('APP_ROOT')) {
    die('Direct access not permitted');
}

// Database credentials
// define('DB_HOST', 'localhost');
// define('DB_NAME', 'audio_course_db');
// define('DB_USER', 'root');
// define('DB_PASS', '');
// define('DB_CHARSET', 'utf8mb4');

define('DB_HOST', 'localhost');
define('DB_NAME', 'u314367906_private');
define('DB_USER', 'u314367906_private');
define('DB_PASS', 'Ai7579796@AaA');
define('DB_CHARSET', 'utf8mb4');

// Security settings
define('SECURE_KEY', 'your-secure-random-key-change-this-in-production-' . bin2hex(random_bytes(16)));
define('SESSION_LIFETIME', 31536000); // 1 year in seconds
define('ACCESS_CODE_LIFETIME', 720 * 3600); // 30 days in seconds
define('AUDIO_TOKEN_LIFETIME', 300); // 5 minutes

// Path settings
define('UPLOAD_PATH', APP_ROOT . '/uploads/');
define('AUDIO_PATH', APP_ROOT . '/uploads/audio/');

// PDO connection
class Database {
    private static $instance = null;
    private $pdo;
    
    private function __construct() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
            ];
            $this->pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            die("Database connection failed. Please check configuration.");
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->pdo;
    }
    
    // Prevent cloning
    private function __clone() {}
    
    // Prevent unserialization
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}

// Helper function to get PDO connection
function getDB() {
    return Database::getInstance()->getConnection();
}
