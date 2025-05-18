<?php
/**
 * Database Functions
 * 
 * Contains functions for database operations
 */

/**
 * Get all URL rules
 * 
 * @param PDO $pdo Database connection
 * @param bool $activeOnly Whether to get only active rules
 * @return array URL rules
 */
function getUrlRules($pdo, $activeOnly = true) {
    try {
        // Prepare query
        $query = "SELECT * FROM url_rules";
        
        // Add active filter if needed
        if ($activeOnly) {
            $query .= " WHERE is_active = 1";
        }
        
        // Add order
        $query .= " ORDER BY id ASC";
        
        // Execute query
        $stmt = $pdo->query($query);
        
        // Return results
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error getting URL rules: " . $e->getMessage());
        return [];
    }
}

/**
 * Get all CSS rules
 * 
 * @param PDO $pdo Database connection
 * @param bool $activeOnly Whether to get only active rules
 * @return array CSS rules
 */
function getCssRules($pdo, $activeOnly = true) {
    try {
        // Prepare query
        $query = "SELECT * FROM css_rules";
        
        // Add active filter if needed
        if ($activeOnly) {
            $query .= " WHERE is_active = 1";
        }
        
        // Add order
        $query .= " ORDER BY id ASC";
        
        // Execute query
        $stmt = $pdo->query($query);
        
        // Return results
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error getting CSS rules: " . $e->getMessage());
        return [];
    }
}

/**
 * Get all cookie rules
 * 
 * @param PDO $pdo Database connection
 * @param bool $activeOnly Whether to get only active rules
 * @return array Cookie rules
 */
function getCookieRules($pdo, $activeOnly = true) {
    try {
        // Prepare query
        $query = "SELECT * FROM cookie_rules";
        
        // Add active filter if needed
        if ($activeOnly) {
            $query .= " WHERE is_active = 1";
        }
        
        // Add order
        $query .= " ORDER BY id ASC";
        
        // Execute query
        $stmt = $pdo->query($query);
        
        // Return results
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error getting cookie rules: " . $e->getMessage());
        return [];
    }
}

/**
 * Get all rules combined
 * 
 * @param PDO $pdo Database connection
 * @param bool $activeOnly Whether to get only active rules
 * @return array Combined rules
 */
function getAllRules($pdo, $activeOnly = true) {
    return [
        'urlRules' => getUrlRules($pdo, $activeOnly),
        'cssRules' => getCssRules($pdo, $activeOnly),
        'cookieRules' => getCookieRules($pdo, $activeOnly)
    ];
}

/**
 * Add URL rule
 * 
 * @param array $data Rule data
 * @param PDO $pdo Database connection
 * @return int|bool ID of the inserted rule or false if failed
 */
function addUrlRule($data, $pdo) {
    try {
        // Validate pattern
        if (!isValidUrlPattern($data['pattern'])) {
            return false;
        }
        
        // Prepare statement
        $stmt = $pdo->prepare("
            INSERT INTO url_rules (pattern, action, target, description, is_active)
            VALUES (:pattern, :action, :target, :description, :is_active)
        ");
        
        // Execute statement
        $stmt->execute([
            ':pattern' => $data['pattern'],
            ':action' => $data['action'],
            ':target' => isset($data['target']) ? $data['target'] : null,
            ':description' => isset($data['description']) ? $data['description'] : null,
            ':is_active' => isset($data['is_active']) ? (int)$data['is_active'] : 1
        ]);
        
        // Return ID of the inserted rule
        return $pdo->lastInsertId();
    } catch (PDOException $e) {
        error_log("Error adding URL rule: " . $e->getMessage());
        return false;
    }
}

/**
 * Update URL rule
 * 
 * @param int $id Rule ID
 * @param array $data Rule data
 * @param PDO $pdo Database connection
 * @return bool Whether the update was successful
 */
function updateUrlRule($id, $data, $pdo) {
    try {
        // Validate pattern
        if (isset($data['pattern']) && !isValidUrlPattern($data['pattern'])) {
            return false;
        }
        
        // Build update query
        $query = "UPDATE url_rules SET ";
        $params = [];
        
        // Add fields to update
        if (isset($data['pattern'])) {
            $query .= "pattern = :pattern, ";
            $params[':pattern'] = $data['pattern'];
        }
        
        if (isset($data['action'])) {
            $query .= "action = :action, ";
            $params[':action'] = $data['action'];
        }
        
        if (isset($data['target'])) {
            $query .= "target = :target, ";
            $params[':target'] = $data['target'];
        }
        
        if (isset($data['description'])) {
            $query .= "description = :description, ";
            $params[':description'] = $data['description'];
        }
        
        if (isset($data['is_active'])) {
            $query .= "is_active = :is_active, ";
            $params[':is_active'] = (int)$data['is_active'];
        }
        
        // Remove trailing comma and add WHERE clause
        $query = rtrim($query, ", ") . " WHERE id = :id";
        $params[':id'] = $id;
        
        // Prepare statement
        $stmt = $pdo->prepare($query);
        
        // Execute statement
        return $stmt->execute($params);
    } catch (PDOException $e) {
        error_log("Error updating URL rule: " . $e->getMessage());
        return false;
    }
}

/**
 * Delete URL rule
 * 
 * @param int $id Rule ID
 * @param PDO $pdo Database connection
 * @return bool Whether the deletion was successful
 */
function deleteUrlRule($id, $pdo) {
    try {
        // Prepare statement
        $stmt = $pdo->prepare("DELETE FROM url_rules WHERE id = :id");
        
        // Execute statement
        return $stmt->execute([':id' => $id]);
    } catch (PDOException $e) {
        error_log("Error deleting URL rule: " . $e->getMessage());
        return false;
    }
}

/**
 * Add CSS rule
 * 
 * @param array $data Rule data
 * @param PDO $pdo Database connection
 * @return int|bool ID of the inserted rule or false if failed
 */
function addCssRule($data, $pdo) {
    try {
        // Validate selector
        if (!isValidCssSelector($data['selector'])) {
            return false;
        }
        
        // Prepare statement
        $stmt = $pdo->prepare("
            INSERT INTO css_rules (url_pattern, selector, action, css_properties, description, is_active)
            VALUES (:url_pattern, :selector, :action, :css_properties, :description, :is_active)
        ");
        
        // Execute statement
        $stmt->execute([
            ':url_pattern' => $data['url_pattern'],
            ':selector' => $data['selector'],
            ':action' => $data['action'],
            ':css_properties' => isset($data['css_properties']) ? json_encode($data['css_properties']) : null,
            ':description' => isset($data['description']) ? $data['description'] : null,
            ':is_active' => isset($data['is_active']) ? (int)$data['is_active'] : 1
        ]);
        
        // Return ID of the inserted rule
        return $pdo->lastInsertId();
    } catch (PDOException $e) {
        error_log("Error adding CSS rule: " . $e->getMessage());
        return false;
    }
}

/**
 * Update CSS rule
 * 
 * @param int $id Rule ID
 * @param array $data Rule data
 * @param PDO $pdo Database connection
 * @return bool Whether the update was successful
 */
function updateCssRule($id, $data, $pdo) {
    try {
        // Validate selector
        if (isset($data['selector']) && !isValidCssSelector($data['selector'])) {
            return false;
        }
        
        // Build update query
        $query = "UPDATE css_rules SET ";
        $params = [];
        
        // Add fields to update
        if (isset($data['url_pattern'])) {
            $query .= "url_pattern = :url_pattern, ";
            $params[':url_pattern'] = $data['url_pattern'];
        }
        
        if (isset($data['selector'])) {
            $query .= "selector = :selector, ";
            $params[':selector'] = $data['selector'];
        }
        
        if (isset($data['action'])) {
            $query .= "action = :action, ";
            $params[':action'] = $data['action'];
        }
        
        if (isset($data['css_properties'])) {
            $query .= "css_properties = :css_properties, ";
            $params[':css_properties'] = json_encode($data['css_properties']);
        }
        
        if (isset($data['description'])) {
            $query .= "description = :description, ";
            $params[':description'] = $data['description'];
        }
        
        if (isset($data['is_active'])) {
            $query .= "is_active = :is_active, ";
            $params[':is_active'] = (int)$data['is_active'];
        }
        
        // Remove trailing comma and add WHERE clause
        $query = rtrim($query, ", ") . " WHERE id = :id";
        $params[':id'] = $id;
        
        // Prepare statement
        $stmt = $pdo->prepare($query);
        
        // Execute statement
        return $stmt->execute($params);
    } catch (PDOException $e) {
        error_log("Error updating CSS rule: " . $e->getMessage());
        return false;
    }
}

/**
 * Delete CSS rule
 * 
 * @param int $id Rule ID
 * @param PDO $pdo Database connection
 * @return bool Whether the deletion was successful
 */
function deleteCssRule($id, $pdo) {
    try {
        // Prepare statement
        $stmt = $pdo->prepare("DELETE FROM css_rules WHERE id = :id");
        
        // Execute statement
        return $stmt->execute([':id' => $id]);
    } catch (PDOException $e) {
        error_log("Error deleting CSS rule: " . $e->getMessage());
        return false;
    }
}

/**
 * Add cookie rule
 * 
 * @param array $data Rule data
 * @param PDO $pdo Database connection
 * @return int|bool ID of the inserted rule or false if failed
 */
function addCookieRule($data, $pdo) {
    try {
        // Validate domain
        if (!isValidDomain($data['domain'])) {
            return false;
        }
        
        // Prepare statement
        $stmt = $pdo->prepare("
            INSERT INTO cookie_rules (domain, name, action, description, is_active)
            VALUES (:domain, :name, :action, :description, :is_active)
        ");
        
        // Execute statement
        $stmt->execute([
            ':domain' => $data['domain'],
            ':name' => $data['name'],
            ':action' => $data['action'],
            ':description' => isset($data['description']) ? $data['description'] : null,
            ':is_active' => isset($data['is_active']) ? (int)$data['is_active'] : 1
        ]);
        
        // Return ID of the inserted rule
        return $pdo->lastInsertId();
    } catch (PDOException $e) {
        error_log("Error adding cookie rule: " . $e->getMessage());
        return false;
    }
}

/**
 * Update cookie rule
 * 
 * @param int $id Rule ID
 * @param array $data Rule data
 * @param PDO $pdo Database connection
 * @return bool Whether the update was successful
 */
function updateCookieRule($id, $data, $pdo) {
    try {
        // Validate domain
        if (isset($data['domain']) && !isValidDomain($data['domain'])) {
            return false;
        }
        
        // Build update query
        $query = "UPDATE cookie_rules SET ";
        $params = [];
        
        // Add fields to update
        if (isset($data['domain'])) {
            $query .= "domain = :domain, ";
            $params[':domain'] = $data['domain'];
        }
        
        if (isset($data['name'])) {
            $query .= "name = :name, ";
            $params[':name'] = $data['name'];
        }
        
        if (isset($data['action'])) {
            $query .= "action = :action, ";
            $params[':action'] = $data['action'];
        }
        
        if (isset($data['description'])) {
            $query .= "description = :description, ";
            $params[':description'] = $data['description'];
        }
        
        if (isset($data['is_active'])) {
            $query .= "is_active = :is_active, ";
            $params[':is_active'] = (int)$data['is_active'];
        }
        
        // Remove trailing comma and add WHERE clause
        $query = rtrim($query, ", ") . " WHERE id = :id";
        $params[':id'] = $id;
        
        // Prepare statement
        $stmt = $pdo->prepare($query);
        
        // Execute statement
        return $stmt->execute($params);
    } catch (PDOException $e) {
        error_log("Error updating cookie rule: " . $e->getMessage());
        return false;
    }
}

/**
 * Delete cookie rule
 * 
 * @param int $id Rule ID
 * @param PDO $pdo Database connection
 * @return bool Whether the deletion was successful
 */
function deleteCookieRule($id, $pdo) {
    try {
        // Prepare statement
        $stmt = $pdo->prepare("DELETE FROM cookie_rules WHERE id = :id");
        
        // Execute statement
        return $stmt->execute([':id' => $id]);
    } catch (PDOException $e) {
        error_log("Error deleting cookie rule: " . $e->getMessage());
        return false;
    }
}

/**
 * Get extensions
 * 
 * @param PDO $pdo Database connection
 * @return array Extensions
 */
function getExtensions($pdo) {
    try {
        // Prepare query
        $query = "SELECT * FROM extensions ORDER BY last_sync DESC";
        
        // Execute query
        $stmt = $pdo->query($query);
        
        // Return results
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error getting extensions: " . $e->getMessage());
        return [];
    }
}

/**
 * Get extension by ID
 * 
 * @param string $extensionId Extension ID
 * @param PDO $pdo Database connection
 * @return array|bool Extension data or false if not found
 */
function getExtensionById($extensionId, $pdo) {
    try {
        // Prepare statement
        $stmt = $pdo->prepare("SELECT * FROM extensions WHERE extension_id = :extension_id");
        
        // Execute statement
        $stmt->execute([':extension_id' => $extensionId]);
        
        // Return extension data
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error getting extension: " . $e->getMessage());
        return false;
    }
}

/**
 * Update extension status
 * 
 * @param string $extensionId Extension ID
 * @param bool $isActive Whether the extension is active
 * @param PDO $pdo Database connection
 * @return bool Whether the update was successful
 */
function updateExtensionStatus($extensionId, $isActive, $pdo) {
    try {
        // Prepare statement
        $stmt = $pdo->prepare("
            UPDATE extensions SET is_active = :is_active WHERE extension_id = :extension_id
        ");
        
        // Execute statement
        return $stmt->execute([
            ':extension_id' => $extensionId,
            ':is_active' => (int)$isActive
        ]);
    } catch (PDOException $e) {
        error_log("Error updating extension status: " . $e->getMessage());
        return false;
    }
}


/**
 * Get conflicting extensions from blacklist
 * 
 * @param PDO $pdo Database connection
 * @param bool $activeOnly Whether to get only active entries
 * @return array Conflicting extensions
 */
function getConflictingExtensionsBlacklist($pdo, $activeOnly = true) {
    try {
        $query = "SELECT * FROM conflicting_extensions_blacklist";
        
        if ($activeOnly) {
            $query .= " WHERE is_active = 1";
        }
        
        $query .= " ORDER BY severity DESC, category ASC";
        
        $stmt = $pdo->query($query);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error getting conflicting extensions blacklist: " . $e->getMessage());
        return [];
    }
}

/**
 * Add extension conflict record
 * 
 * @param array $data Conflict data
 * @param PDO $pdo Database connection
 * @return int|bool ID of the inserted record or false if failed
 */
function addExtensionConflict($data, $pdo) {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO extension_conflicts 
            (extension_id, conflict_extension_id, conflict_extension_name, detection_method, violation_reported)
            VALUES (:extension_id, :conflict_extension_id, :conflict_extension_name, :detection_method, :violation_reported)
        ");
        
        $stmt->execute([
            ':extension_id' => $data['extension_id'],
            ':conflict_extension_id' => $data['conflict_extension_id'],
            ':conflict_extension_name' => $data['conflict_extension_name'],
            ':detection_method' => $data['detection_method'] ?? 'automatic',
            ':violation_reported' => isset($data['violation_reported']) ? (int)$data['violation_reported'] : 0
        ]);
        
        return $pdo->lastInsertId();
    } catch (PDOException $e) {
        error_log("Error adding extension conflict: " . $e->getMessage());
        return false;
    }
}

/**
 * Get extension conflicts
 * 
 * @param string $extensionId Extension ID to get conflicts for
 * @param PDO $pdo Database connection
 * @param bool $unresolvedOnly Whether to get only unresolved conflicts
 * @return array Extension conflicts
 */
function getExtensionConflicts($extensionId, $pdo, $unresolvedOnly = true) {
    try {
        $query = "SELECT * FROM extension_conflicts WHERE extension_id = :extension_id";
        
        if ($unresolvedOnly) {
            $query .= " AND resolved_at IS NULL";
        }
        
        $query .= " ORDER BY created_at DESC";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute([':extension_id' => $extensionId]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error getting extension conflicts: " . $e->getMessage());
        return [];
    }
}

/**
 * Resolve extension conflicts
 * 
 * @param string $extensionId Extension ID
 * @param array $conflictIds Array of conflict IDs to resolve
 * @param PDO $pdo Database connection
 * @return bool Whether the resolution was successful
 */
function resolveExtensionConflicts($extensionId, $conflictIds, $pdo) {
    try {
        if (empty($conflictIds)) {
            // Resolve all conflicts for the extension
            $stmt = $pdo->prepare("
                UPDATE extension_conflicts 
                SET resolved_at = NOW() 
                WHERE extension_id = :extension_id AND resolved_at IS NULL
            ");
            
            $result = $stmt->execute([':extension_id' => $extensionId]);
        } else {
            // Resolve specific conflicts
            $placeholders = str_repeat('?,', count($conflictIds) - 1) . '?';
            $stmt = $pdo->prepare("
                UPDATE extension_conflicts 
                SET resolved_at = NOW() 
                WHERE extension_id = ? AND id IN ($placeholders) AND resolved_at IS NULL
            ");
            
            $params = array_merge([$extensionId], $conflictIds);
            $result = $stmt->execute($params);
        }
        
        return $result;
    } catch (PDOException $e) {
        error_log("Error resolving extension conflicts: " . $e->getMessage());
        return false;
    }
}

/**
 * Log violation
 * 
 * @param array $data Violation data
 * @param PDO $pdo Database connection
 * @return int|bool ID of the inserted record or false if failed
 */
function logViolation($data, $pdo) {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO violation_logs 
            (extension_id, user_ip, user_agent, violation_type, violation_details, conflicts_detected, action_taken)
            VALUES (:extension_id, :user_ip, :user_agent, :violation_type, :violation_details, :conflicts_detected, :action_taken)
        ");
        
        $stmt->execute([
            ':extension_id' => $data['extension_id'],
            ':user_ip' => $data['user_ip'] ?? getClientIp(),
            ':user_agent' => $data['user_agent'] ?? ($_SERVER['HTTP_USER_AGENT'] ?? ''),
            ':violation_type' => $data['violation_type'],
            ':violation_details' => json_encode($data['violation_details'] ?? []),
            ':conflicts_detected' => (int)($data['conflicts_detected'] ?? 0),
            ':action_taken' => $data['action_taken'] ?? 'extension_disabled'
        ]);
        
        return $pdo->lastInsertId();
    } catch (PDOException $e) {
        error_log("Error logging violation: " . $e->getMessage());
        return false;
    }
}

/**
 * Get violation logs
 * 
 * @param PDO $pdo Database connection
 * @param string $extensionId Optional extension ID filter
 * @param int $limit Number of records to retrieve
 * @return array Violation logs
 */
function getViolationLogs($pdo, $extensionId = null, $limit = 100) {
    try {
        $query = "SELECT * FROM violation_logs";
        $params = [];
        
        if ($extensionId) {
            $query .= " WHERE extension_id = :extension_id";
            $params[':extension_id'] = $extensionId;
        }
        
        $query .= " ORDER BY created_at DESC LIMIT :limit";
        
        $stmt = $pdo->prepare($query);
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        
        if ($extensionId) {
            $stmt->bindValue(':extension_id', $extensionId);
        }
        
        $stmt->execute();
        
        $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Decode violation_details JSON
        foreach ($logs as &$log) {
            if (!empty($log['violation_details'])) {
                $log['violation_details'] = json_decode($log['violation_details'], true);
            }
        }
        
        return $logs;
    } catch (PDOException $e) {
        error_log("Error getting violation logs: " . $e->getMessage());
        return [];
    }
}

/**
 * Check if extension has active conflicts
 * 
 * @param string $extensionId Extension ID
 * @param PDO $pdo Database connection
 * @return bool Whether the extension has active conflicts
 */
function hasActiveConflicts($extensionId, $pdo) {
    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM extension_conflicts 
            WHERE extension_id = :extension_id AND resolved_at IS NULL
        ");
        
        $stmt->execute([':extension_id' => $extensionId]);
        $count = $stmt->fetchColumn();
        
        return $count > 0;
    } catch (PDOException $e) {
        error_log("Error checking active conflicts: " . $e->getMessage());
        return false;
    }
}

/**
 * Get extension conflict statistics
 * 
 * @param PDO $pdo Database connection
 * @param string $period Time period (day, week, month, year)
 * @return array Conflict statistics
 */
function getConflictStatistics($pdo, $period = 'week') {
    try {
        $dateInterval = match($period) {
            'day' => 'INTERVAL 1 DAY',
            'week' => 'INTERVAL 1 WEEK',
            'month' => 'INTERVAL 1 MONTH',
            'year' => 'INTERVAL 1 YEAR',
            default => 'INTERVAL 1 WEEK'
        };
        
        // Total conflicts in period
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as total_conflicts
            FROM extension_conflicts 
            WHERE created_at >= DATE_SUB(NOW(), $dateInterval)
        ");
        $stmt->execute();
        $totalConflicts = $stmt->fetchColumn();
        
        // Unique extensions with conflicts
        $stmt = $pdo->prepare("
            SELECT COUNT(DISTINCT extension_id) as affected_extensions
            FROM extension_conflicts 
            WHERE created_at >= DATE_SUB(NOW(), $dateInterval)
        ");
        $stmt->execute();
        $affectedExtensions = $stmt->fetchColumn();
        
        // Top conflicting extensions
        $stmt = $pdo->prepare("
            SELECT conflict_extension_name, COUNT(*) as conflict_count
            FROM extension_conflicts 
            WHERE created_at >= DATE_SUB(NOW(), $dateInterval)
            GROUP BY conflict_extension_id, conflict_extension_name
            ORDER BY conflict_count DESC
            LIMIT 10
        ");
        $stmt->execute();
        $topConflictingExtensions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Violations in period
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as total_violations
            FROM violation_logs 
            WHERE created_at >= DATE_SUB(NOW(), $dateInterval)
        ");
        $stmt->execute();
        $totalViolations = $stmt->fetchColumn();
        
        // Resolved vs unresolved conflicts
        $stmt = $pdo->prepare("
            SELECT 
                SUM(CASE WHEN resolved_at IS NOT NULL THEN 1 ELSE 0 END) as resolved_conflicts,
                SUM(CASE WHEN resolved_at IS NULL THEN 1 ELSE 0 END) as unresolved_conflicts
            FROM extension_conflicts 
            WHERE created_at >= DATE_SUB(NOW(), $dateInterval)
        ");
        $stmt->execute();
        $resolutionStats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return [
            'period' => $period,
            'total_conflicts' => (int)$totalConflicts,
            'affected_extensions' => (int)$affectedExtensions,
            'total_violations' => (int)$totalViolations,
            'resolved_conflicts' => (int)($resolutionStats['resolved_conflicts'] ?? 0),
            'unresolved_conflicts' => (int)($resolutionStats['unresolved_conflicts'] ?? 0),
            'top_conflicting_extensions' => $topConflictingExtensions
        ];
    } catch (PDOException $e) {
        error_log("Error getting conflict statistics: " . $e->getMessage());
        return [
            'period' => $period,
            'total_conflicts' => 0,
            'affected_extensions' => 0,
            'total_violations' => 0,
            'resolved_conflicts' => 0,
            'unresolved_conflicts' => 0,
            'top_conflicting_extensions' => []
        ];
    }
}

/**
 * Add or update conflicting extension in blacklist
 * 
 * @param array $data Blacklist entry data
 * @param PDO $pdo Database connection
 * @return bool Whether the operation was successful
 */
function addOrUpdateConflictingExtension($data, $pdo) {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO conflicting_extensions_blacklist 
            (extension_id, extension_name, category, detection_pattern, severity, action_required, notes)
            VALUES (:extension_id, :extension_name, :category, :detection_pattern, :severity, :action_required, :notes)
            ON DUPLICATE KEY UPDATE
            extension_name = VALUES(extension_name),
            category = VALUES(category),
            detection_pattern = VALUES(detection_pattern),
            severity = VALUES(severity),
            action_required = VALUES(action_required),
            notes = VALUES(notes),
            updated_at = CURRENT_TIMESTAMP
        ");
        
        return $stmt->execute([
            ':extension_id' => $data['extension_id'],
            ':extension_name' => $data['extension_name'],
            ':category' => $data['category'] ?? 'other',
            ':detection_pattern' => $data['detection_pattern'] ?? null,
            ':severity' => $data['severity'] ?? 'medium',
            ':action_required' => $data['action_required'] ?? 'block',
            ':notes' => $data['notes'] ?? null
        ]);
    } catch (PDOException $e) {
        error_log("Error adding/updating conflicting extension: " . $e->getMessage());
        return false;
    }
}

/**
 * Remove conflicting extension from blacklist
 * 
 * @param string $extensionId Extension ID to remove
 * @param PDO $pdo Database connection
 * @return bool Whether the removal was successful
 */
function removeConflictingExtension($extensionId, $pdo) {
    try {
        $stmt = $pdo->prepare("
            DELETE FROM conflicting_extensions_blacklist 
            WHERE extension_id = :extension_id
        ");
        
        return $stmt->execute([':extension_id' => $extensionId]);
    } catch (PDOException $e) {
        error_log("Error removing conflicting extension: " . $e->getMessage());
        return false;
    }
}

/**
 * Toggle conflicting extension active status
 * 
 * @param string $extensionId Extension ID
 * @param bool $isActive Whether the entry should be active
 * @param PDO $pdo Database connection
 * @return bool Whether the update was successful
 */
function toggleConflictingExtensionStatus($extensionId, $isActive, $pdo) {
    try {
        $stmt = $pdo->prepare("
            UPDATE conflicting_extensions_blacklist 
            SET is_active = :is_active 
            WHERE extension_id = :extension_id
        ");
        
        return $stmt->execute([
            ':extension_id' => $extensionId,
            ':is_active' => (int)$isActive
        ]);
    } catch (PDOException $e) {
        error_log("Error toggling conflicting extension status: " . $e->getMessage());
        return false;
    }
}

/**
 * Extension Management Functions
 * 
 * Functions for managing browser extensions
 */

/**
 * Get all managed extensions
 * 
 * @param PDO $pdo Database connection
 * @param bool $backendControlledOnly Get only backend controlled extensions
 * @return array Managed extensions
 */
function getManagedExtensions($pdo, $backendControlledOnly = true) {
    try {
        $query = "SELECT * FROM managed_extensions";
        
        if ($backendControlledOnly) {
            $query .= " WHERE backend_controlled = 1";
        }
        
        $query .= " ORDER BY discovered_at DESC";
        
        $stmt = $pdo->query($query);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error getting managed extensions: " . $e->getMessage());
        return [];
    }
}

/**
 * Get single managed extension by ID
 * 
 * @param string $extensionId Extension ID
 * @param PDO $pdo Database connection
 * @return array|bool Extension data or false if not found
 */
function getManagedExtension($extensionId, $pdo) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM managed_extensions WHERE extension_id = :extension_id");
        $stmt->execute([':extension_id' => $extensionId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error getting managed extension: " . $e->getMessage());
        return false;
    }
}

/**
 * Register new extension
 * 
 * @param array $extensionData Extension data
 * @param PDO $pdo Database connection
 * @return bool Success status
 */
function registerManagedExtension($extensionData, $pdo) {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO managed_extensions 
            (extension_id, extension_name, version, description, install_type, is_enabled, discovery_method)
            VALUES (:extension_id, :extension_name, :version, :description, :install_type, :is_enabled, :discovery_method)
            ON DUPLICATE KEY UPDATE
            extension_name = VALUES(extension_name),
            version = VALUES(version),
            description = VALUES(description),
            install_type = VALUES(install_type),
            is_enabled = VALUES(is_enabled),
            last_sync = CURRENT_TIMESTAMP
        ");
        
        return $stmt->execute([
            ':extension_id' => $extensionData['extension_id'],
            ':extension_name' => $extensionData['extension_name'],
            ':version' => $extensionData['version'] ?? '',
            ':description' => $extensionData['description'] ?? '',
            ':install_type' => $extensionData['install_type'] ?? 'normal',
            ':is_enabled' => (int)($extensionData['is_enabled'] ?? false),
            ':discovery_method' => $extensionData['discovery_method'] ?? 'auto'
        ]);
    } catch (PDOException $e) {
        error_log("Error registering managed extension: " . $e->getMessage());
        return false;
    }
}

/**
 * Register multiple extensions
 * 
 * @param array $extensionsData Array of extension data
 * @param PDO $pdo Database connection
 * @return array Result with success count and errors
 */
function registerMultipleManagedExtensions($extensionsData, $pdo) {
    $successCount = 0;
    $errors = [];
    
    foreach ($extensionsData as $extensionData) {
        if (registerManagedExtension($extensionData, $pdo)) {
            $successCount++;
        } else {
            $errors[] = "Failed to register: " . ($extensionData['extension_name'] ?? $extensionData['extension_id']);
        }
    }
    
    return [
        'success_count' => $successCount,
        'total_count' => count($extensionsData),
        'errors' => $errors
    ];
}

/**
 * Update extension enabled status
 * 
 * @param string $extensionId Extension ID
 * @param bool $isEnabled Whether the extension should be enabled
 * @param PDO $pdo Database connection
 * @param string $triggeredBy Who triggered the change
 * @return bool Success status
 */
function updateManagedExtensionStatus($extensionId, $isEnabled, $pdo, $triggeredBy = 'admin') {
    try {
        // Get current status for logging
        $currentExtension = getManagedExtension($extensionId, $pdo);
        $oldState = $currentExtension ? ($currentExtension['is_enabled'] ? 'enabled' : 'disabled') : null;
        $newState = $isEnabled ? 'enabled' : 'disabled';
        
        // Update status
        $stmt = $pdo->prepare("
            UPDATE managed_extensions 
            SET is_enabled = :is_enabled, last_sync = CURRENT_TIMESTAMP 
            WHERE extension_id = :extension_id
        ");
        
        $result = $stmt->execute([
            ':extension_id' => $extensionId,
            ':is_enabled' => (int)$isEnabled
        ]);
        
        if ($result) {
            // Log the action
            logExtensionManagementAction($extensionId, 'status_change', $oldState, $newState, $triggeredBy, 'backend', null, $pdo);
        }
        
        return $result;
    } catch (PDOException $e) {
        error_log("Error updating managed extension status: " . $e->getMessage());
        return false;
    }
}

/**
 * Set backend control status for extension
 * 
 * @param string $extensionId Extension ID
 * @param bool $backendControlled Whether extension should be backend controlled
 * @param PDO $pdo Database connection
 * @return bool Success status
 */
function setExtensionBackendControl($extensionId, $backendControlled, $pdo) {
    try {
        $stmt = $pdo->prepare("
            UPDATE managed_extensions 
            SET backend_controlled = :backend_controlled, last_sync = CURRENT_TIMESTAMP 
            WHERE extension_id = :extension_id
        ");
        
        return $stmt->execute([
            ':extension_id' => $extensionId,
            ':backend_controlled' => (int)$backendControlled
        ]);
    } catch (PDOException $e) {
        error_log("Error setting extension backend control: " . $e->getMessage());
        return false;
    }
}

/**
 * Delete managed extension
 * 
 * @param string $extensionId Extension ID
 * @param PDO $pdo Database connection
 * @return bool Success status
 */
function deleteManagedExtension($extensionId, $pdo) {
    try {
        $stmt = $pdo->prepare("DELETE FROM managed_extensions WHERE extension_id = :extension_id");
        
        $result = $stmt->execute([':extension_id' => $extensionId]);
        
        if ($result) {
            // Log deletion
            logExtensionManagementAction($extensionId, 'delete', null, null, 'admin', 'backend', null, $pdo);
        }
        
        return $result;
    } catch (PDOException $e) {
        error_log("Error deleting managed extension: " . $e->getMessage());
        return false;
    }
}

/**
 * Log extension management action
 * 
 * @param string $extensionId Extension ID
 * @param string $action Action performed
 * @param string $oldState Previous state
 * @param string $newState New state
 * @param string $triggeredBy Who triggered the action
 * @param string $source Source of action (backend, extension, manual)
 * @param array $details Additional details
 * @param PDO $pdo Database connection
 * @return bool Success status
 */
function logExtensionManagementAction($extensionId, $action, $oldState, $newState, $triggeredBy, $source, $details, $pdo) {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO extension_management_log 
            (extension_id, action, old_state, new_state, triggered_by, source, details, user_ip)
            VALUES (:extension_id, :action, :old_state, :new_state, :triggered_by, :source, :details, :user_ip)
        ");
        
        return $stmt->execute([
            ':extension_id' => $extensionId,
            ':action' => $action,
            ':old_state' => $oldState,
            ':new_state' => $newState,
            ':triggered_by' => $triggeredBy,
            ':source' => $source,
            ':details' => $details ? json_encode($details) : null,
            ':user_ip' => getClientIp()
        ]);
    } catch (PDOException $e) {
        error_log("Error logging extension management action: " . $e->getMessage());
        return false;
    }
}

/**
 * Get extension management logs
 * 
 * @param PDO $pdo Database connection
 * @param string $extensionId Optional filter by extension ID
 * @param int $limit Number of records to return
 * @return array Management logs
 */
function getExtensionManagementLogs($pdo, $extensionId = null, $limit = 100) {
    try {
        $query = "SELECT eml.*, me.extension_name 
                 FROM extension_management_log eml
                 LEFT JOIN managed_extensions me ON eml.extension_id = me.extension_id";
        
        $params = [];
        
        if ($extensionId) {
            $query .= " WHERE eml.extension_id = :extension_id";
            $params[':extension_id'] = $extensionId;
        }
        
        $query .= " ORDER BY eml.created_at DESC LIMIT :limit";
        
        $stmt = $pdo->prepare($query);
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        
        if ($extensionId) {
            $stmt->bindValue(':extension_id', $extensionId);
        }
        
        $stmt->execute();
        
        $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Decode details JSON
        foreach ($logs as &$log) {
            if (!empty($log['details'])) {
                $log['details'] = json_decode($log['details'], true);
            }
        }
        
        return $logs;
    } catch (PDOException $e) {
        error_log("Error getting extension management logs: " . $e->getMessage());
        return [];
    }
}

/**
 * Get extension policy
 * 
 * @param string $policyName Policy name
 * @param PDO $pdo Database connection
 * @return array|bool Policy data or false if not found
 */
function getExtensionPolicy($policyName, $pdo) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM extension_policies WHERE policy_name = :policy_name AND is_active = 1");
        $stmt->execute([':policy_name' => $policyName]);
        
        $policy = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($policy && !empty($policy['policy_value'])) {
            $policy['policy_value'] = json_decode($policy['policy_value'], true);
        }
        
        return $policy;
    } catch (PDOException $e) {
        error_log("Error getting extension policy: " . $e->getMessage());
        return false;
    }
}

/**
 * Update extension policy
 * 
 * @param string $policyName Policy name
 * @param array $policyValue Policy value
 * @param PDO $pdo Database connection
 * @return bool Success status
 */
function updateExtensionPolicy($policyName, $policyValue, $pdo) {
    try {
        $stmt = $pdo->prepare("
            UPDATE extension_policies 
            SET policy_value = :policy_value, updated_at = CURRENT_TIMESTAMP 
            WHERE policy_name = :policy_name
        ");
        
        return $stmt->execute([
            ':policy_name' => $policyName,
            ':policy_value' => json_encode($policyValue)
        ]);
    } catch (PDOException $e) {
        error_log("Error updating extension policy: " . $e->getMessage());
        return false;
    }
}

/**
 * Get extension management statistics
 * 
 * @param PDO $pdo Database connection
 * @return array Statistics
 */
function getExtensionManagementStats($pdo) {
    try {
        // Total managed extensions
        $stmt = $pdo->query("SELECT COUNT(*) FROM managed_extensions WHERE backend_controlled = 1");
        $totalManaged = $stmt->fetchColumn();
        
        // Enabled extensions
        $stmt = $pdo->query("SELECT COUNT(*) FROM managed_extensions WHERE backend_controlled = 1 AND is_enabled = 1");
        $totalEnabled = $stmt->fetchColumn();
        
        // Disabled extensions
        $stmt = $pdo->query("SELECT COUNT(*) FROM managed_extensions WHERE backend_controlled = 1 AND is_enabled = 0");
        $totalDisabled = $stmt->fetchColumn();
        
        // Recent actions (last 24 hours)
        $stmt = $pdo->query("SELECT COUNT(*) FROM extension_management_log WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)");
        $recentActions = $stmt->fetchColumn();
        
        // Most frequently managed extensions
        $stmt = $pdo->query("
            SELECT me.extension_name, COUNT(eml.id) as action_count
            FROM extension_management_log eml
            JOIN managed_extensions me ON eml.extension_id = me.extension_id
            WHERE eml.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            GROUP BY eml.extension_id, me.extension_name
            ORDER BY action_count DESC
            LIMIT 5
        ");
        $topManagedExtensions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'total_managed' => (int)$totalManaged,
            'total_enabled' => (int)$totalEnabled,
            'total_disabled' => (int)$totalDisabled,
            'recent_actions_24h' => (int)$recentActions,
            'top_managed_extensions' => $topManagedExtensions
        ];
    } catch (PDOException $e) {
        error_log("Error getting extension management statistics: " . $e->getMessage());
        return [
            'total_managed' => 0,
            'total_enabled' => 0,
            'total_disabled' => 0,
            'recent_actions_24h' => 0,
            'top_managed_extensions' => []
        ];
    }
}

/**
 * Cleanup old extension management logs
 * 
 * @param PDO $pdo Database connection
 * @param int $daysToKeep Number of days to keep logs
 * @return int Number of deleted records
 */
function cleanupExtensionManagementLogs($pdo, $daysToKeep = 30) {
    try {
        $stmt = $pdo->prepare("
            DELETE FROM extension_management_log 
            WHERE created_at < DATE_SUB(NOW(), INTERVAL :days_to_keep DAY)
        ");
        
        $stmt->execute([':days_to_keep' => $daysToKeep]);
        
        $deletedCount = $stmt->rowCount();
        if ($deletedCount > 0) {
            error_log("Cleaned up $deletedCount old extension management logs");
        }
        
        return $deletedCount;
    } catch (PDOException $e) {
        error_log("Error cleaning up extension management logs: " . $e->getMessage());
        return 0;
    }
}