<?php
/**
 * Extension Management API Endpoint
 * 
 * Handles extension management operations
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
        handleGetRequest($pdo);
        break;
        
    case 'POST':
        handlePostRequest($pdo);
        break;
        
    case 'PUT':
        handlePutRequest($pdo);
        break;
        
    case 'DELETE':
        handleDeleteRequest($pdo);
        break;
        
    default:
        jsonResponse(['error' => 'Method not allowed'], 405);
}

/**
 * Handle GET requests
 */
function handleGetRequest($pdo) {
    $action = $_GET['action'] ?? 'list';
    
    switch ($action) {
        case 'list':
            // Get all managed extensions
            $backendControlledOnly = isset($_GET['backend_controlled_only']) ? (bool)$_GET['backend_controlled_only'] : true;
            $extensions = getManagedExtensions($pdo, $backendControlledOnly);
            
            jsonResponse([
                'success' => true,
                'extensions' => $extensions
            ]);
            break;
            
        case 'get':
            // Get single extension
            $extensionId = $_GET['extension_id'] ?? '';
            
            if (empty($extensionId)) {
                jsonResponse(['error' => 'Extension ID is required'], 400);
            }
            
            $extension = getManagedExtension($extensionId, $pdo);
            
            if ($extension) {
                jsonResponse([
                    'success' => true,
                    'extension' => $extension
                ]);
            } else {
                jsonResponse(['error' => 'Extension not found'], 404);
            }
            break;
            
        case 'stats':
            // Get statistics
            $stats = getExtensionManagementStats($pdo);
            
            jsonResponse([
                'success' => true,
                'stats' => $stats
            ]);
            break;
            
        case 'logs':
            // Get management logs
            $extensionId = $_GET['extension_id'] ?? null;
            $limit = (int)($_GET['limit'] ?? 100);
            
            $logs = getExtensionManagementLogs($pdo, $extensionId, $limit);
            
            jsonResponse([
                'success' => true,
                'logs' => $logs
            ]);
            break;
            
        case 'policies':
            // Get extension policies
            $policies = [];
            $policyNames = ['auto_disable_new_extensions', 'block_extensions_page_access', 'extension_whitelist', 'auto_logout_on_disable'];
            
            foreach ($policyNames as $policyName) {
                $policy = getExtensionPolicy($policyName, $pdo);
                if ($policy) {
                    $policies[$policyName] = $policy;
                }
            }
            
            jsonResponse([
                'success' => true,
                'policies' => $policies
            ]);
            break;
            
        case 'requiring_control':
            // Get extensions that require control (for legacy compatibility)
            $extensions = getManagedExtensions($pdo, true);
            $requiringControl = array_filter($extensions, function($ext) {
                return !$ext['is_enabled']; // Return disabled extensions
            });
            
            jsonResponse([
                'success' => true,
                'extensions' => array_values($requiringControl)
            ]);
            break;
            
        default:
            jsonResponse(['error' => 'Invalid action'], 400);
    }
}

/**
 * Handle POST requests
 */
function handlePostRequest($pdo) {
    $data = getJsonInput();
    $action = $data['action'] ?? 'register';
    
    switch ($action) {
        case 'register':
            // Register extensions
            if (isset($data['extensions']) && is_array($data['extensions'])) {
                // Register multiple extensions
                $result = registerMultipleManagedExtensions($data['extensions'], $pdo);
                
                jsonResponse([
                    'success' => true,
                    'registered' => $result['success_count'],
                    'total' => $result['total_count'],
                    'errors' => $result['errors']
                ]);
            } else {
                // Register single extension
                $requiredFields = ['extension_id', 'extension_name'];
                
                foreach ($requiredFields as $field) {
                    if (empty($data[$field])) {
                        jsonResponse(['error' => ucfirst(str_replace('_', ' ', $field)) . ' is required'], 400);
                    }
                }
                
                $result = registerManagedExtension($data, $pdo);
                
                if ($result) {
                    jsonResponse([
                        'success' => true,
                        'message' => 'Extension registered successfully'
                    ]);
                } else {
                    jsonResponse(['error' => 'Failed to register extension'], 500);
                }
            }
            break;
            
        case 'control_action':
            // Log control action (for logging extension actions)
            $extensionId = $data['extension_id'] ?? '';
            $actionType = $data['action_type'] ?? '';
            $status = $data['status'] ?? '';
            $details = $data['details'] ?? [];
            
            if (empty($extensionId) || empty($actionType)) {
                jsonResponse(['error' => 'Extension ID and action type are required'], 400);
            }
            
            $result = logExtensionManagementAction(
                $extensionId, 
                $actionType, 
                $details['old_state'] ?? null,
                $details['new_state'] ?? null,
                $details['triggered_by'] ?? 'extension', 
                $details['source'] ?? 'extension', 
                $details, 
                $pdo
            );
            
            if ($result) {
                jsonResponse([
                    'success' => true,
                    'message' => 'Action logged successfully'
                ]);
            } else {
                jsonResponse(['error' => 'Failed to log action'], 500);
            }
            break;
            
        default:
            jsonResponse(['error' => 'Invalid action'], 400);
    }
}

/**
 * Handle PUT requests
 */
function handlePutRequest($pdo) {
    $data = getJsonInput();
    $action = $data['action'] ?? $_GET['action'] ?? 'update';
    
    switch ($action) {
        case 'update':
        case 'toggle_status':
            // Update extension status
            $extensionId = $data['extension_id'] ?? $_GET['extension_id'] ?? '';
            $isEnabled = $data['is_enabled'] ?? $_GET['is_enabled'] ?? false;
            $triggeredBy = $data['triggered_by'] ?? 'admin';
            
            if (empty($extensionId)) {
                jsonResponse(['error' => 'Extension ID is required'], 400);
            }
            
            $result = updateManagedExtensionStatus($extensionId, (bool)$isEnabled, $pdo, $triggeredBy);
            
            if ($result) {
                jsonResponse([
                    'success' => true,
                    'message' => 'Extension status updated successfully'
                ]);
            } else {
                jsonResponse(['error' => 'Failed to update extension status'], 500);
            }
            break;
            
        case 'set_backend_control':
            // Set backend control
            $extensionId = $data['extension_id'] ?? '';
            $backendControlled = $data['backend_controlled'] ?? true;
            
            if (empty($extensionId)) {
                jsonResponse(['error' => 'Extension ID is required'], 400);
            }
            
            $result = setExtensionBackendControl($extensionId, (bool)$backendControlled, $pdo);
            
            if ($result) {
                jsonResponse([
                    'success' => true,
                    'message' => 'Backend control status updated successfully'
                ]);
            } else {
                jsonResponse(['error' => 'Failed to update backend control status'], 500);
            }
            break;
            
        case 'update_policy':
            // Update extension policy
            $policyName = $data['policy_name'] ?? '';
            $policyValue = $data['policy_value'] ?? [];
            
            if (empty($policyName)) {
                jsonResponse(['error' => 'Policy name is required'], 400);
            }
            
            $result = updateExtensionPolicy($policyName, $policyValue, $pdo);
            
            if ($result) {
                jsonResponse([
                    'success' => true,
                    'message' => 'Policy updated successfully'
                ]);
            } else {
                jsonResponse(['error' => 'Failed to update policy'], 500);
            }
            break;
            
        default:
            jsonResponse(['error' => 'Invalid action'], 400);
    }
}

/**
 * Handle DELETE requests
 */
function handleDeleteRequest($pdo) {
    $action = $_GET['action'] ?? 'delete';
    
    switch ($action) {
        case 'delete':
            // Delete managed extension
            $extensionId = $_GET['extension_id'] ?? '';
            
            if (empty($extensionId)) {
                jsonResponse(['error' => 'Extension ID is required'], 400);
            }
            
            $result = deleteManagedExtension($extensionId, $pdo);
            
            if ($result) {
                jsonResponse([
                    'success' => true,
                    'message' => 'Extension deleted successfully'
                ]);
            } else {
                jsonResponse(['error' => 'Failed to delete extension'], 500);
            }
            break;
            
        case 'cleanup_logs':
            // Cleanup old logs
            $daysToKeep = (int)($_GET['days_to_keep'] ?? 30);
            
            $deletedCount = cleanupExtensionManagementLogs($pdo, $daysToKeep);
            
            jsonResponse([
                'success' => true,
                'message' => "Cleaned up $deletedCount log entries",
                'deleted_count' => $deletedCount
            ]);
            break;
            
        default:
            jsonResponse(['error' => 'Invalid action'], 400);
    }
}
?>