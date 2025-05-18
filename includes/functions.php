<?php
/**
 * General Helper Functions
 * 
 * Contains utility functions used throughout the application
 */

/**
 * Generate a secure random token
 * 
 * @param int $length Length of the token
 * @return string Generated token
 */
function generateSecureToken($length = 32) {
    // Generate random bytes
    $randomBytes = random_bytes($length);
    
    // Convert to hexadecimal
    return bin2hex($randomBytes);
}

/**
 * Get client IP address
 * 
 * @return string Client IP address
 */
function getClientIp() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    
    return $ip;
}

/**
 * Sanitize input data
 * 
 * @param mixed $data Input data to sanitize
 * @return mixed Sanitized data
 */
function sanitizeInput($data) {
    if (is_array($data)) {
        // If it's an array, sanitize each element recursively
        foreach ($data as $key => $value) {
            $data[$key] = sanitizeInput($value);
        }
    } else {
        // Trim whitespace
        $data = trim($data);
        
        // Remove HTML and PHP tags
        $data = strip_tags($data);
        
        // Convert special characters to HTML entities
        $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    }
    
    return $data;
}

/**
 * Get JSON data from request body
 * 
 * @return array JSON data as associative array
 */
function getJsonInput() {
    // Get request body
    $json = file_get_contents('php://input');
    
    // Decode JSON data
    $data = json_decode($json, true);
    
    // Return empty array if JSON is invalid
    if (json_last_error() !== JSON_ERROR_NONE) {
        return [];
    }
    
    // Sanitize input data
    return sanitizeInput($data);
}

/**
 * Send JSON response
 * 
 * @param array $data Response data
 * @param int $statusCode HTTP status code
 */
function jsonResponse($data, $statusCode = 200) {
    // Set HTTP status code
    http_response_code($statusCode);
    
    // Set content type header
    header('Content-Type: application/json');
    
    // Output JSON data
    echo json_encode($data);
    exit;
}

/**
 * Validate URL pattern
 * 
 * @param string $pattern URL pattern to validate
 * @return bool Whether the pattern is valid
 */
function isValidUrlPattern($pattern) {
    // Check if pattern is not empty
    if (empty($pattern)) {
        return false;
    }
    
    // Check if pattern is a valid regex
    try {
        preg_match('/' . str_replace('/', '\/', $pattern) . '/', '');
        return true;
    } catch (Exception $e) {
        // Check if it's a simple wildcard pattern
        if (strpos($pattern, '*') !== false) {
            $regexPattern = str_replace(['*', '.'], ['.*', '\.'], $pattern);
            try {
                preg_match('/' . $regexPattern . '/', '');
                return true;
            } catch (Exception $e) {
                return false;
            }
        }
        
        return false;
    }
}

/**
 * Validate CSS selector
 * 
 * @param string $selector CSS selector to validate
 * @return bool Whether the selector is valid
 */
function isValidCssSelector($selector) {
    // Check if selector is not empty
    if (empty($selector)) {
        return false;
    }
    
    // Check for potentially harmful selectors
    $blacklist = ['script', 'body', 'html', '[src]'];
    
    foreach ($blacklist as $banned) {
        if (stripos($selector, $banned) !== false) {
            return false;
        }
    }
    
    return true;
}

/**
 * Validate domain name
 * 
 * @param string $domain Domain name to validate
 * @return bool Whether the domain is valid
 */
function isValidDomain($domain) {
    // Check if domain is not empty
    if (empty($domain)) {
        return false;
    }
    
    // Basic domain validation
    return (bool) preg_match('/^(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z0-9][a-z0-9-]{0,61}[a-z0-9]$/i', $domain);
}

/**
 * Log activity
 * 
 * @param string $action Action performed
 * @param string $details Action details
 * @param PDO $pdo Database connection
 */
function logActivity($action, $details, $pdo) {
    // Get user ID if logged in
    $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
    
    // Get client IP
    $ip = getClientIp();
    
    // Prepare statement
    $stmt = $pdo->prepare("
        INSERT INTO activity_log (user_id, action, details, ip_address)
        VALUES (:user_id, :action, :details, :ip_address)
    ");
    
    // Execute statement
    $stmt->execute([
        ':user_id' => $userId,
        ':action' => $action,
        ':details' => $details,
        ':ip_address' => $ip
    ]);
}

/**
 * Redirect to a URL
 * 
 * @param string $url URL to redirect to
 */
function redirect($url) {
    header("Location: $url");
    exit;
}

/**
 * Check if request is AJAX
 * 
 * @return bool Whether the request is AJAX
 */
function isAjaxRequest() {
    return (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');
}

/**
 * Format date string
 * 
 * @param string $date Date string
 * @param string $format Date format
 * @return string Formatted date
 */
function formatDate($date, $format = 'Y-m-d H:i:s') {
    $dateObj = new DateTime($date);
    return $dateObj->format($format);
}