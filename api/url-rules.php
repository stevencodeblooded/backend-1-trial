<?php
/**
 * URL Rules API Endpoint
 * 
 * Manages URL redirection and blocking rules
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
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
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

// Handle different request methods
switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        // Get URL rules
        $rules = getUrlRules($pdo, isset($_GET['all']) ? false : true);
        jsonResponse(['success' => true, 'rules' => $rules]);
        break;
        
    case 'POST':
        // Add URL rule
        $data = getJsonInput();
        
        // Validate required fields
        if (empty($data['pattern']) || empty($data['action'])) {
            jsonResponse(['error' => 'Pattern and action are required'], 400);
        }
        
        // Validate action
        if (!in_array($data['action'], ['redirect', 'block'])) {
            jsonResponse(['error' => 'Invalid action. Must be redirect or block'], 400);
        }
        
        // Validate target URL for redirect action
        if ($data['action'] === 'redirect' && empty($data['target'])) {
            jsonResponse(['error' => 'Target URL is required for redirect action'], 400);
        }
        
        // Add rule
        $ruleId = addUrlRule($data, $pdo);
        
        if ($ruleId) {
            jsonResponse(['success' => true, 'id' => $ruleId]);
        } else {
            jsonResponse(['error' => 'Failed to add URL rule'], 500);
        }
        break;
        
    case 'PUT':
        // Update URL rule
        $data = getJsonInput();
        
        // Check if ID is provided
        if (empty($_GET['id'])) {
            jsonResponse(['error' => 'Rule ID is required'], 400);
        }
        
        // Update rule
        $result = updateUrlRule($_GET['id'], $data, $pdo);
        
        if ($result) {
            jsonResponse(['success' => true]);
        } else {
            jsonResponse(['error' => 'Failed to update URL rule'], 500);
        }
        break;
        
    case 'DELETE':
        // Delete URL rule
        
        // Check if ID is provided
        if (empty($_GET['id'])) {
            jsonResponse(['error' => 'Rule ID is required'], 400);
        }
        
        // Delete rule
        $result = deleteUrlRule($_GET['id'], $pdo);
        
        if ($result) {
            jsonResponse(['success' => true]);
        } else {
            jsonResponse(['error' => 'Failed to delete URL rule'], 500);
        }
        break;
        
    default:
        jsonResponse(['error' => 'Method not allowed'], 405);
}