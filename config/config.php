<?php
/**
 * Configuration File
 * 
 * Contains application-wide settings and configuration
 */

// Enable error reporting in development
if ($_SERVER['SERVER_NAME'] === 'localhost' || $_SERVER['SERVER_NAME'] === '127.0.0.1') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Set timezone
date_default_timezone_set('UTC');

// Application paths
define('BASE_PATH', dirname(__DIR__));
define('INCLUDES_PATH', BASE_PATH . '/includes');
define('API_PATH', BASE_PATH . '/api');
define('ADMIN_PATH', BASE_PATH . '/admin');

// Application settings
define('APP_NAME', 'SemrushToolz Ultimate');
define('APP_VERSION', '3.0');
define('APP_URL', 'https://semrushtoolz.com');

// API settings
define('API_TOKEN_EXPIRY', 30); // API token expiry in days
define('API_RATE_LIMIT', 100);  // API rate limit per hour

// Session settings
define('SESSION_NAME', 'semrush_toolz_session');
define('SESSION_LIFETIME', 86400); // 24 hours

// Include database configuration
require_once BASE_PATH . '/config/database.php';

// Start session
function startSecureSession() {
    // Set secure session parameters
    ini_set('session.use_only_cookies', 1);
    ini_set('session.use_strict_mode', 1);
    
    // Set custom session name
    session_name(SESSION_NAME);
    
    // Set session cookie parameters
    session_set_cookie_params([
        'lifetime' => SESSION_LIFETIME,
        'path' => '/',
        'domain' => $_SERVER['HTTP_HOST'],
        'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        'httponly' => true,
        'samesite' => 'Strict'
    ]);
    
    // Start session
    session_start();
    
    // Regenerate session ID to prevent session fixation attacks
    if (!isset($_SESSION['last_regeneration'])) {
        session_regenerate_id(true);
        $_SESSION['last_regeneration'] = time();
    } else if (time() - $_SESSION['last_regeneration'] > 1800) {
        // Regenerate session ID every 30 minutes
        session_regenerate_id(true);
        $_SESSION['last_regeneration'] = time();
    }
}

// Initialize the application
function initializeApp() {
    // Start secure session for web interfaces
    if (!defined('API_REQUEST') || !API_REQUEST) {
        startSecureSession();
    }
    
    // Connect to database
    $pdo = getDbConnection();
    
    // If database connection failed, show error
    if (!$pdo) {
        if (defined('API_REQUEST') && API_REQUEST) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Database connection failed']);
            exit;
        } else {
            die("Database connection failed. Please check configuration.");
        }
    }
    
    // Initialize database if needed
    initializeDatabase($pdo);
    
    return $pdo;
}