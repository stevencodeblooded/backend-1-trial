<?php
/**
 * Cookie Rules API Endpoint
 * 
 * Manages cookie handling rules
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
        // Get cookie rules
        $rules = getCookieRules($pdo, isset($_GET['all']) ? false : true);
        jsonResponse(['success' => true, 'rules' => $rules]);
        break;
        
    case 'POST':
        // Add cookie rule
        $data = getJsonInput();
        
        // Validate required fields
        if (empty($data['domain']) || empty($data['name']) || empty($data['action'])) {
            jsonResponse(['error' => 'Domain, name, and action are required'], 400);
        }
        
        // Validate action
        if (!in_array($data['action'], ['preserve', 'delete'])) {
            jsonResponse(['error' => 'Invalid action. Must be preserve or delete'], 400);
        }
        
        // Add rule
        $ruleId = addCookieRule($data, $pdo);
        
        if ($ruleId) {
            jsonResponse(['success' => true, 'id' => $ruleId]);
        } else {
            jsonResponse(['error' => 'Failed to add cookie rule'], 500);
        }
        break;
        
    case 'PUT':
        // Update cookie rule
        $data = getJsonInput();
        
        // Check if ID is provided
        if (empty($_GET['id'])) {
            jsonResponse(['error' => 'Rule ID is required'], 400);
        }
        
        // Update rule
        $result = updateCookieRule($_GET['id'], $data, $pdo);
        
        if ($result) {
            jsonResponse(['success' => true]);
        } else {
            jsonResponse(['error' => 'Failed to update cookie rule'], 500);
        }
        break;
        
    case 'DELETE':
        // Delete cookie rule
        
        // Check if ID is provided
        if (empty($_GET['id'])) {
            jsonResponse(['error' => 'Rule ID is required'], 400);
        }
        
        // Delete rule
        $result = deleteCookieRule($_GET['id'], $pdo);
        
        if ($result) {
            jsonResponse(['success' => true]);
        } else {
            jsonResponse(['error' => 'Failed to delete cookie rule'], 500);
        }
        break;
        
    default:
        jsonResponse(['error' => 'Method not allowed'], 405);
}