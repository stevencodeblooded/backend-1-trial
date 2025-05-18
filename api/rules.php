<?php
/**
 * Rules API Endpoint
 * 
 * Provides rules for the extension
 */

// Set API request flag
define('API_REQUEST', true);

// Include configuration
require_once '../config/config.php';

// Include required files
require_once INCLUDES_PATH . '/functions.php';
require_once INCLUDES_PATH . '/auth-functions.php';
require_once INCLUDES_PATH . '/db-functions.php';

// Initialize database connection
$pdo = initializeApp();

// Set response headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Max-Age: 86400'); // Cache preflight for 24 hours

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Only accept GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

// Check if Authorization header is present
if (!isset($_SERVER['HTTP_AUTHORIZATION']) || empty($_SERVER['HTTP_AUTHORIZATION'])) {
    jsonResponse(['error' => 'Authorization header is required'], 401);
}

// Extract token from Authorization header
$authHeader = $_SERVER['HTTP_AUTHORIZATION'];
$token = str_replace('Bearer ', '', $authHeader);

// Verify token
if (!verifyApiToken($token, $pdo)) {
    jsonResponse(['error' => 'Invalid or expired token'], 401);
}

// Get all rules
$rules = getAllRules($pdo);

// Format CSS rules for extension
foreach ($rules['cssRules'] as &$rule) {
    if (isset($rule['css_properties']) && !empty($rule['css_properties'])) {
        $rule['cssProperties'] = json_decode($rule['css_properties'], true);
    } else {
        $rule['cssProperties'] = null;
    }
    unset($rule['css_properties']);
}

// Return rules
jsonResponse([
    'success' => true,
    'urlRules' => $rules['urlRules'],
    'cssRules' => $rules['cssRules'],
    'cookieRules' => $rules['cookieRules']
]);