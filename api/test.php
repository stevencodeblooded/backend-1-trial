<?php
/**
 * Enhanced test.php with comprehensive auth header debugging
 */

// Set API request flag
define('API_REQUEST', true);

// Include configuration
require_once '../config/config.php';

// Include required files
require_once INCLUDES_PATH . '/functions.php';
require_once INCLUDES_PATH . '/auth-functions.php';

// Initialize database connection
$pdo = initializeApp();

// Set response headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Add the enhanced auth header detection functions if they don't exist
if (!function_exists('getAuthorizationHeader')) {
    function getAuthorizationHeader() {
        $headers = null;
        
        if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $headers = trim($_SERVER['HTTP_AUTHORIZATION']);
        }
        elseif (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
            $headers = trim($_SERVER['REDIRECT_HTTP_AUTHORIZATION']);
        }
        elseif (function_exists('apache_request_headers')) {
            $requestHeaders = apache_request_headers();
            $requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
            if (isset($requestHeaders['Authorization'])) {
                $headers = trim($requestHeaders['Authorization']);
            }
        }
        elseif (isset($_SERVER['PHP_AUTH_BEARER'])) {
            $headers = trim($_SERVER['PHP_AUTH_BEARER']);
        }
        
        return $headers;
    }
}

if (!function_exists('getBearerToken')) {
    function getBearerToken() {
        $headers = getAuthorizationHeader();
        
        if (!empty($headers)) {
            if (preg_match('/Bearer\s+(.+)/', $headers, $matches)) {
                return $matches[1];
            }
        }
        
        return null;
    }
}

// Get comprehensive auth debug info
$authDebug = [
    'HTTP_AUTHORIZATION' => $_SERVER['HTTP_AUTHORIZATION'] ?? null,
    'REDIRECT_HTTP_AUTHORIZATION' => $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? null,
    'PHP_AUTH_BEARER' => $_SERVER['PHP_AUTH_BEARER'] ?? null,
    'PHP_AUTH_USER' => $_SERVER['PHP_AUTH_USER'] ?? null,
    'detected_auth_header' => getAuthorizationHeader(),
    'detected_bearer_token' => getBearerToken(),
];

// Add apache headers if available
if (function_exists('apache_request_headers')) {
    $authDebug['apache_request_headers'] = apache_request_headers();
}

// Add all headers if available
if (function_exists('getallheaders')) {
    $authDebug['all_headers'] = getallheaders();
}

// Check for Authorization header using enhanced method
$authHeader = getAuthorizationHeader();
$hasToken = !empty($authHeader);
$tokenValid = false;
$extensionId = null;

if ($hasToken && preg_match('/Bearer\s+(.+)/', $authHeader, $matches)) {
    $token = $matches[1];
    
    // Simple check - verify token exists in database
    try {
        $stmt = $pdo->prepare("SELECT extension_id, expires_at FROM api_tokens WHERE token = ? AND expires_at > NOW()");
        $stmt->execute([$token]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $tokenValid = !empty($result);
        $extensionId = $result['extension_id'] ?? null;
    } catch (Exception $e) {
        error_log("Token validation error: " . $e->getMessage());
    }
}

// Get token count
$tokenCount = 0;
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM api_tokens WHERE expires_at > NOW()");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $tokenCount = $result['count'] ?? 0;
} catch (Exception $e) {
    error_log("Token count error: " . $e->getMessage());
}

// Response
jsonResponse([
    'success' => true,
    'message' => 'Database connection successful',
    'token_count' => $tokenCount,
    'php_version' => PHP_VERSION,
    'timestamp' => date('Y-m-d H:i:s'),
    'authentication' => [
        'has_auth_header' => $hasToken,
        'token_valid' => $tokenValid,
        'extension_id' => $extensionId,
        'token_preview' => $hasToken ? substr($matches[1] ?? '', 0, 20) . '...' : null
    ],
    'debug' => $authDebug
]);