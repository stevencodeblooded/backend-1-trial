

/**
 * Get extensions requiring control
 */
function getExtensionsRequiringControl($userExtensionId, $pdo) {
    try {
        $stmt = $pdo->prepare("
            SELECT * FROM user_extensions 
            WHERE user_extension_id = ? AND backend_controlled = 1 AND is_enabled = 1
        ");
        $stmt->execute([$userExtensionId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error getting extensions requiring control: " . $e->getMessage());
        return [];
    }
}

/**
 * Log extension action
 */
function logExtensionAction($userExtensionId, $targetExtensionId, $action, $status, $method = 'backend', $details = [], $pdo) {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO extension_control_logs 
            (user_extension_id, target_extension_id, action, status, method, details, ip_address, user_agent)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $userExtensionId,
            $targetExtensionId,
            $action,
            $status,
            $method,
            json_encode($details),
            getClientIp(),
            $_SERVER['HTTP_USER_AGENT'] ?? ''
        ]);

        return $pdo->lastInsertId();
    } catch (PDOException $e) {
        error_log("Error logging extension action: " . $e->getMessage());
        return false;
    }
}

/**
 * Get extension control statistics
 */
function getExtensionControlStats($userExtensionId, $pdo, $period = 'week') {
    try {
        $dateInterval = match($period) {
            'day' => 'INTERVAL 1 DAY',
            'week' => 'INTERVAL 1 WEEK', 
            'month' => 'INTERVAL 1 MONTH',
            default => 'INTERVAL 1 WEEK'
        };

        // Total extensions managed
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as total_extensions,
                   SUM(CASE WHEN is_enabled = 1 THEN 1 ELSE 0 END) as enabled_extensions,
                   SUM(CASE WHEN backend_controlled = 1 THEN 1 ELSE 0 END) as controlled_extensions
            FROM user_extensions 
            WHERE user_extension_id = ?
        ");
        $stmt->execute([$userExtensionId]);
        $totals = $stmt->fetch(PDO::FETCH_ASSOC);

        // Recent actions
        $stmt = $pdo->prepare("
            SELECT action, COUNT(*) as count
            FROM extension_control_logs 
            WHERE user_extension_id = ? AND created_at >= DATE_SUB(NOW(), $dateInterval)
            GROUP BY action
        ");
        $stmt->execute([$userExtensionId]);
        $recentActions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'totals' => $totals,
            'recent_actions' => $recentActions,
            'period' => $period
        ];
    } catch (PDOException $e) {
        error_log("Error getting extension control stats: " . $e->getMessage());
        return [
            'totals' => ['total_extensions' => 0, 'enabled_extensions' => 0, 'controlled_extensions' => 0],
            'recent_actions' => [],
            'period' => $period
        ];
    }
}

/**
 * Add to whitelist
 */
function addToWhitelist($extensionId, $extensionName, $category = 'approved', $reason = '', $pdo) {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO extension_whitelist (extension_id, extension_name, category, reason)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
            extension_name = VALUES(extension_name),
            category = VALUES(category),
            reason = VALUES(reason)
        ");

        return $stmt->execute([$extensionId, $extensionName, $category, $reason]);
    } catch (PDOException $e) {
        error_log("Error adding to whitelist: " . $e->getMessage());
        return false;
    }
}

/**
 * Remove from whitelist
 */
function removeFromWhitelist($extensionId, $pdo) {
    try {
        $stmt = $pdo->prepare("DELETE FROM extension_whitelist WHERE extension_id = ?");
        return $stmt->execute([$extensionId]);
    } catch (PDOException $e) {
        error_log("Error removing from whitelist: " . $e->getMessage());
        return false;
    }
}