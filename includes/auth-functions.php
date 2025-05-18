<?php
/**
 * Authentication Functions (Fixed)
 * 
 * Contains functions for user authentication and authorization
 */

/**
 * Authenticate user
 * 
 * @param string $username Username
 * @param string $password Password
 * @param PDO $pdo Database connection
 * @return array|bool User data or false if authentication failed
 */
function authenticateUser($username, $password, $pdo) {
    try {
        // Prepare statement
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username");
        
        // Execute statement
        $stmt->execute([':username' => $username]);
        
        // Get user data
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // If user exists and password is correct
        if ($user && password_verify($password, $user['password'])) {
            // Update last login time
            $updateStmt = $pdo->prepare("
                UPDATE users SET last_login = NOW() WHERE id = :id
            ");
            
            $updateStmt->execute([':id' => $user['id']]);
            
            // Remove password from user data
            unset($user['password']);
            
            return $user;
        }
        
        return false;
    } catch (PDOException $e) {
        error_log("Authentication failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Create API token for extension
 * 
 * @param string $extensionId Extension ID
 * @param PDO $pdo Database connection
 * @return string|bool Token or false if failed
 */
function createApiToken($extensionId, $pdo) {
    try {
        // Check if extension already has an active token
        $checkStmt = $pdo->prepare("
            SELECT token FROM api_tokens
            WHERE extension_id = :extension_id 
            AND expires_at > NOW()
            ORDER BY created_at DESC
            LIMIT 1
        ");
        
        $checkStmt->execute([':extension_id' => $extensionId]);
        $existingToken = $checkStmt->fetch(PDO::FETCH_COLUMN);
        
        // If valid token exists, return it
        if ($existingToken) {
            error_log("Returning existing token for extension: " . $extensionId);
            return $existingToken;
        }
        
        // Generate new token
        $token = generateSecureToken(32);
        error_log("Creating new token for extension: " . $extensionId);
        
        // Calculate expiry date (30 days from now)
        $expiryDate = new DateTime();
        $expiryDate->modify('+' . API_TOKEN_EXPIRY . ' days');
        
        // Check if extension exists in extensions table
        $checkExtStmt = $pdo->prepare("
            SELECT id FROM extensions WHERE extension_id = :extension_id
        ");
        
        $checkExtStmt->execute([':extension_id' => $extensionId]);
        
        if (!$checkExtStmt->fetch()) {
            // Extension doesn't exist, create it
            $insertExtStmt = $pdo->prepare("
                INSERT INTO extensions (extension_id, name, version, last_sync, is_active)
                VALUES (:extension_id, :name, :version, NOW(), 1)
            ");
            
            $insertExtStmt->execute([
                ':extension_id' => $extensionId,
                ':name' => 'SemrushToolz Ultimate',
                ':version' => APP_VERSION
            ]);
            
            error_log("Created new extension record for: " . $extensionId);
        } else {
            // Update extension last sync time
            $updateExtStmt = $pdo->prepare("
                UPDATE extensions SET last_sync = NOW(), is_active = 1 
                WHERE extension_id = :extension_id
            ");
            
            $updateExtStmt->execute([':extension_id' => $extensionId]);
            
            error_log("Updated extension record for: " . $extensionId);
        }
        
        // Create new token
        $insertStmt = $pdo->prepare("
            INSERT INTO api_tokens (extension_id, token, expires_at, created_at)
            VALUES (:extension_id, :token, :expires_at, NOW())
        ");
        
        $result = $insertStmt->execute([
            ':extension_id' => $extensionId,
            ':token' => $token,
            ':expires_at' => $expiryDate->format('Y-m-d H:i:s')
        ]);
        
        if ($result) {
            error_log("Token created successfully for extension: " . $extensionId);
            error_log("Token expires at: " . $expiryDate->format('Y-m-d H:i:s'));
            return $token;
        } else {
            error_log("Failed to insert token for extension: " . $extensionId);
            return false;
        }
        
    } catch (PDOException $e) {
        error_log("Token creation failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Verify API token (Fixed version)
 * 
 * @param string $token API token
 * @param PDO $pdo Database connection
 * @return bool Whether the token is valid
 */
function verifyApiToken($token, $pdo) {
    try {
        // Trim the token to remove any whitespace
        $token = trim($token);
        
        // Log the token being verified for debugging
        error_log("Verifying token: " . substr($token, 0, 10) . "... (length: " . strlen($token) . ")");
        
        // Find valid (non-expired) token
        $stmt = $pdo->prepare("
            SELECT t.*, e.extension_id, e.is_active as extension_active
            FROM api_tokens t
            LEFT JOIN extensions e ON t.extension_id = e.extension_id
            WHERE t.token = :token 
            AND t.expires_at > NOW()
            ORDER BY t.created_at DESC
            LIMIT 1
        ");
        
        // Execute statement
        $stmt->execute([':token' => $token]);
        
        // Get token data
        $tokenData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($tokenData) {
            // Check if extension is active
            if ($tokenData['extension_active'] == 0) {
                error_log("Token found but extension is disabled: " . $tokenData['extension_id']);
                return false;
            }
            
            // Update last used time
            $updateStmt = $pdo->prepare("
                UPDATE api_tokens SET last_used_at = NOW() WHERE id = :id
            ");
            $updateStmt->execute([':id' => $tokenData['id']]);
            
            // Update extension last sync time
            $updateExtStmt = $pdo->prepare("
                UPDATE extensions SET last_sync = NOW() WHERE extension_id = :extension_id
            ");
            $updateExtStmt->execute([':extension_id' => $tokenData['extension_id']]);
            
            error_log("Token verified successfully for extension: " . $tokenData['extension_id']);
            return true;
        } else {
            // Check if token exists but is expired
            $expiredStmt = $pdo->prepare("
                SELECT expires_at, extension_id FROM api_tokens WHERE token = :token LIMIT 1
            ");
            $expiredStmt->execute([':token' => $token]);
            $expiredToken = $expiredStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($expiredToken) {
                error_log("Token found but expired. Extension: " . $expiredToken['extension_id'] . ", Expired at: " . $expiredToken['expires_at']);
            } else {
                error_log("No token found matching the provided value");
            }
            
            return false;
        }
        
    } catch (PDOException $e) {
        error_log("Token verification failed: " . $e->getMessage());
        error_log("SQL State: " . $e->getCode());
        return false;
    }
}

/**
 * Clean up expired tokens
 * 
 * @param PDO $pdo Database connection
 * @return int Number of tokens cleaned up
 */
function cleanupExpiredTokens($pdo) {
    try {
        $stmt = $pdo->prepare("DELETE FROM api_tokens WHERE expires_at <= NOW()");
        $stmt->execute();
        
        $deletedCount = $stmt->rowCount();
        if ($deletedCount > 0) {
            error_log("Cleaned up $deletedCount expired tokens");
        }
        
        return $deletedCount;
    } catch (PDOException $e) {
        error_log("Failed to cleanup expired tokens: " . $e->getMessage());
        return 0;
    }
}

/**
 * Check if user is logged in
 * 
 * @return bool Whether the user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Check if user is admin
 * 
 * @return bool Whether the user is admin
 */
function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

/**
 * Require login for a page
 * 
 * @param string $redirectUrl URL to redirect to if not logged in
 */
function requireLogin($redirectUrl = '/admin/login.php') {
    if (!isLoggedIn()) {
        // Set return URL
        $_SESSION['return_url'] = $_SERVER['REQUEST_URI'];
        
        // Redirect to login page
        redirect($redirectUrl);
    }
}

/**
 * Require admin role for a page
 * 
 * @param string $redirectUrl URL to redirect to if not admin
 */
function requireAdmin($redirectUrl = '/admin/index.php') {
    requireLogin();
    
    if (!isAdmin()) {
        // Redirect to dashboard
        redirect($redirectUrl);
    }
}

/**
 * Get current user data
 * 
 * @param PDO $pdo Database connection
 * @return array|bool User data or false if not logged in
 */
function getCurrentUser($pdo) {
    if (!isLoggedIn()) {
        return false;
    }
    
    try {
        // Prepare statement
        $stmt = $pdo->prepare("
            SELECT id, username, email, role, last_login, created_at, updated_at
            FROM users WHERE id = :id
        ");
        
        // Execute statement
        $stmt->execute([':id' => $_SESSION['user_id']]);
        
        // Get user data
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Getting current user failed: " . $e->getMessage());
        return false;
    }
}