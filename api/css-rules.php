<?php
/**
 * CSS Rules API Endpoint
 * 
 * Manages CSS modification rules
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
        // Get CSS rules
        $rules = getCssRules($pdo, isset($_GET['all']) ? false : true);
        
        // Format rules for response
        foreach ($rules as &$rule) {
            if (isset($rule['css_properties']) && !empty($rule['css_properties'])) {
                $rule['cssProperties'] = json_decode($rule['css_properties'], true);
            } else {
                $rule['cssProperties'] = null;
            }
            unset($rule['css_properties']);
        }
        
        jsonResponse(['success' => true, 'rules' => $rules]);
        break;
        
    case 'POST':
        // Add CSS rule
        $data = getJsonInput();
        
        // Validate required fields
        if (empty($data['url_pattern']) || empty($data['selector']) || empty($data['action'])) {
            jsonResponse(['error' => 'URL pattern, selector, and action are required'], 400);
        }
        
        // Validate action
        if (!in_array($data['action'], ['hide', 'modify', 'remove'])) {
            jsonResponse(['error' => 'Invalid action. Must be hide, modify, or remove'], 400);
        }
        
        // Validate CSS properties for modify action
        if ($data['action'] === 'modify' && empty($data['css_properties'])) {
            jsonResponse(['error' => 'CSS properties are required for modify action'], 400);
        }
        
        // Add rule
        $ruleId = addCssRule($data, $pdo);
        
        if ($ruleId) {
            jsonResponse(['success' => true, 'id' => $ruleId]);
        } else {
            jsonResponse(['error' => 'Failed to add CSS rule'], 500);
        }
        break;
        
    case 'PUT':
        // Update CSS rule
        $data = getJsonInput();
        
        // Check if ID is provided
        if (empty($_GET['id'])) {
            jsonResponse(['error' => 'Rule ID is required'], 400);
        }
        
        // Update rule
        $result = updateCssRule($_GET['id'], $data, $pdo);
        
        if ($result) {
            jsonResponse(['success' => true]);
        } else {
            jsonResponse(['error' => 'Failed to update CSS rule'], 500);
        }
        break;
        
    case 'DELETE':
        // Delete CSS rule
        
        // Check if ID is provided
        if (empty($_GET['id'])) {
            jsonResponse(['error' => 'Rule ID is required'], 400);
        }
        
        // Delete rule
        $result = deleteCssRule($_GET['id'], $pdo);
        
        if ($result) {
            jsonResponse(['success' => true]);
        } else {
            jsonResponse(['error' => 'Failed to delete CSS rule'], 500);
        }
        break;
        
    default:
        jsonResponse(['error' => 'Method not allowed'], 405);
}