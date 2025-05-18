<?php
/**
 * Extension Conflicts API Endpoint
 * 
 * Handles extension conflict detection and reporting
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
    $action = $_GET['action'] ?? 'list_conflicts';
    
    switch ($action) {
        case 'list_conflicts':
            // Get conflicts for specific extension
            $extensionId = $_GET['extension_id'] ?? '';
            
            if (empty($extensionId)) {
                jsonResponse(['error' => 'Extension ID is required'], 400);
            }
            
            $conflicts = getExtensionConflicts($extensionId, $pdo);
            
            jsonResponse([
                'success' => true,
                'conflicts' => $conflicts,
                'has_active_conflicts' => hasActiveConflicts($extensionId, $pdo)
            ]);
            break;
            
        case 'blacklist':
            // Get conflicting extensions blacklist
            $activeOnly = isset($_GET['active_only']) ? (bool)$_GET['active_only'] : true;
            $blacklist = getConflictingExtensionsBlacklist($pdo, $activeOnly);
            
            jsonResponse([
                'success' => true,
                'blacklist' => $blacklist
            ]);
            break;
            
        case 'statistics':
            // Get conflict statistics
            $period = $_GET['period'] ?? 'week';
            $stats = getConflictStatistics($pdo, $period);
            
            jsonResponse([
                'success' => true,
                'statistics' => $stats
            ]);
            break;
            
        case 'violation_logs':
            // Get violation logs
            $extensionId = $_GET['extension_id'] ?? null;
            $limit = (int)($_GET['limit'] ?? 100);
            
            $logs = getViolationLogs($pdo, $extensionId, $limit);
            
            jsonResponse([
                'success' => true,
                'logs' => $logs
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
    $action = $data['action'] ?? 'report_conflict';
    
    switch ($action) {
        case 'report_conflict':
            // Report extension conflicts
            $extensionId = $data['extensionId'] ?? '';
            $conflicts = $data['conflicts'] ?? [];
            
            if (empty($extensionId) || empty($conflicts)) {
                jsonResponse(['error' => 'Extension ID and conflicts are required'], 400);
            }
            
            $conflictIds = [];
            $violationDetails = [];
            
            // Record each conflict
            foreach ($conflicts as $conflict) {
                $conflictData = [
                    'extension_id' => $extensionId,
                    'conflict_extension_id' => $conflict['id'],
                    'conflict_extension_name' => $conflict['name'],
                    'detection_method' => $conflict['detectedAs'] ?? 'automatic',
                    'violation_reported' => 1
                ];
                
                $conflictId = addExtensionConflict($conflictData, $pdo);
                if ($conflictId) {
                    $conflictIds[] = $conflictId;
                }
                
                $violationDetails[] = [
                    'extension_id' => $conflict['id'],
                    'extension_name' => $conflict['name'],
                    'detected_as' => $conflict['detectedAs'] ?? 'unknown'
                ];
            }
            
            // Log violation
            $violationData = [
                'extension_id' => $extensionId,
                'violation_type' => 'conflicting_extensions_detected',
                'violation_details' => $violationDetails,
                'conflicts_detected' => count($conflicts),
                'action_taken' => 'extension_disabled'
            ];
            
            $violationId = logViolation($violationData, $pdo);
            
            // Log activity
            logActivity(
                'conflict_reported', 
                "Extension $extensionId reported " . count($conflicts) . " conflicting extensions", 
                $pdo
            );
            
            jsonResponse([
                'success' => true,
                'message' => 'Conflicts reported successfully',
                'conflict_ids' => $conflictIds,
                'violation_id' => $violationId
            ]);
            break;
            
        case 'violation_detected':
            // Report a violation
            $extensionId = $data['extensionId'] ?? '';
            $conflicts = $data['conflicts'] ?? [];
            
            if (empty($extensionId)) {
                jsonResponse(['error' => 'Extension ID is required'], 400);
            }
            
            $violationData = [
                'extension_id' => $extensionId,
                'violation_type' => 'privacy_policy_violation',
                'violation_details' => [
                    'conflicts' => $conflicts,
                    'timestamp' => $data['timestamp'] ?? date('c'),
                    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                    'ip_address' => getClientIp()
                ],
                'conflicts_detected' => count($conflicts),
                'action_taken' => 'extension_blocked'
            ];
            
            $violationId = logViolation($violationData, $pdo);
            
            jsonResponse([
                'success' => true,
                'message' => 'Violation logged successfully',
                'violation_id' => $violationId
            ]);
            break;
            
        case 'add_blacklist_entry':
            // Add entry to conflicting extensions blacklist
            $requiredFields = ['extension_id', 'extension_name'];
            
            foreach ($requiredFields as $field) {
                if (empty($data[$field])) {
                    jsonResponse(['error' => ucfirst(str_replace('_', ' ', $field)) . ' is required'], 400);
                }
            }
            
            $result = addOrUpdateConflictingExtension($data, $pdo);
            
            if ($result) {
                logActivity('blacklist_entry_added', "Added {$data['extension_name']} to conflicts blacklist", $pdo);
                
                jsonResponse([
                    'success' => true,
                    'message' => 'Blacklist entry added successfully'
                ]);
            } else {
                jsonResponse(['error' => 'Failed to add blacklist entry'], 500);
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
    $action = $data['action'] ?? $_GET['action'] ?? 'resolve_conflicts';
    
    switch ($action) {
        case 'resolve_conflicts':
            // Resolve conflicts for extension
            $extensionId = $data['extension_id'] ?? $_GET['extension_id'] ?? '';
            $conflictIds = $data['conflict_ids'] ?? [];
            
            if (empty($extensionId)) {
                jsonResponse(['error' => 'Extension ID is required'], 400);
            }
            
            $result = resolveExtensionConflicts($extensionId, $conflictIds, $pdo);
            
            if ($result) {
                logActivity('conflicts_resolved', "Resolved conflicts for extension $extensionId", $pdo);
                
                jsonResponse([
                    'success' => true,
                    'message' => 'Conflicts resolved successfully'
                ]);
            } else {
                jsonResponse(['error' => 'Failed to resolve conflicts'], 500);
            }
            break;
            
        case 'toggle_blacklist_status':
            // Toggle blacklist entry status
            $extensionId = $data['extension_id'] ?? $_GET['extension_id'] ?? '';
            $isActive = $data['is_active'] ?? $_GET['is_active'] ?? true;
            
            if (empty($extensionId)) {
                jsonResponse(['error' => 'Extension ID is required'], 400);
            }
            
            $result = toggleConflictingExtensionStatus($extensionId, (bool)$isActive, $pdo);
            
            if ($result) {
                $status = $isActive ? 'activated' : 'deactivated';
                logActivity('blacklist_entry_toggled', "Blacklist entry $extensionId $status", $pdo);
                
                jsonResponse([
                    'success' => true,
                    'message' => "Blacklist entry $status successfully"
                ]);
            } else {
                jsonResponse(['error' => 'Failed to toggle blacklist entry status'], 500);
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
    $action = $_GET['action'] ?? 'remove_blacklist_entry';
    
    switch ($action) {
        case 'remove_blacklist_entry':
            // Remove entry from conflicting extensions blacklist
            $extensionId = $_GET['extension_id'] ?? '';
            
            if (empty($extensionId)) {
                jsonResponse(['error' => 'Extension ID is required'], 400);
            }
            
            $result = removeConflictingExtension($extensionId, $pdo);
            
            if ($result) {
                logActivity('blacklist_entry_removed', "Removed extension $extensionId from blacklist", $pdo);
                
                jsonResponse([
                    'success' => true,
                    'message' => 'Blacklist entry removed successfully'
                ]);
            } else {
                jsonResponse(['error' => 'Failed to remove blacklist entry'], 500);
            }
            break;
            
        default:
            jsonResponse(['error' => 'Invalid action'], 400);
    }
}
?>