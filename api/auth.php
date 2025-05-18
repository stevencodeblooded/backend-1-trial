<?php
/**
 * Authentication API Endpoint
 * 
 * Handles authentication for the extension
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
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

// Get JSON input data
$data = getJsonInput();

// Check if extension ID is provided
if (empty($data['extensionId'])) {
    jsonResponse(['error' => 'Extension ID is required'], 400);
}

// Create API token
$token = createApiToken($data['extensionId'], $pdo);

if ($token) {
    // Return token
    jsonResponse([
        'success' => true,
        'token' => $token,
        'expiresIn' => API_TOKEN_EXPIRY * 86400 // Convert days to seconds
    ]);
} else {
    // Return error
    jsonResponse(['error' => 'Failed to create token'], 500);
}