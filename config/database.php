<?php
/**
 * Database Configuration
 * 
 * Contains database connection parameters and setup
 */

// Database connection parameters
define('DB_HOST', 'localhost');
define('DB_NAME', 'semrush_toolz_db');
define('DB_USER', 'root');
define('DB_PASS', ''); // Change this to a secure password

/**
 * Get database connection
 * 
 * @return PDO Database connection
 */
function getDbConnection() {
    try {
        // Create PDO connection
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        return $pdo;
    } catch (PDOException $e) {
        // Log error
        error_log("Database connection failed: " . $e->getMessage());
        
        // Return false on failure
        return false;
    }
}

/**
 * Initialize database tables if they don't exist
 * 
 * @param PDO $pdo Database connection
 * @return bool Success status
 */
function initializeDatabase($pdo) {
    try {
        // Create users table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(50) NOT NULL UNIQUE,
                password VARCHAR(255) NOT NULL,
                email VARCHAR(100) NOT NULL UNIQUE,
                role ENUM('admin', 'editor') NOT NULL DEFAULT 'editor',
                last_login DATETIME,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
        
        // Create extensions table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS extensions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                extension_id VARCHAR(255) NOT NULL UNIQUE,
                name VARCHAR(100),
                version VARCHAR(20),
                last_sync DATETIME,
                is_active BOOLEAN DEFAULT TRUE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
        
        // Create URL rules table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS url_rules (
                id INT AUTO_INCREMENT PRIMARY KEY,
                pattern VARCHAR(255) NOT NULL,
                action ENUM('redirect', 'block') NOT NULL,
                target VARCHAR(255),
                description VARCHAR(255),
                is_active BOOLEAN DEFAULT TRUE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
        
        // Create CSS rules table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS css_rules (
                id INT AUTO_INCREMENT PRIMARY KEY,
                url_pattern VARCHAR(255) NOT NULL,
                selector VARCHAR(255) NOT NULL,
                action ENUM('hide', 'modify', 'remove') NOT NULL,
                css_properties TEXT,
                description VARCHAR(255),
                is_active BOOLEAN DEFAULT TRUE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
        
        // Create cookie rules table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS cookie_rules (
                id INT AUTO_INCREMENT PRIMARY KEY,
                domain VARCHAR(255) NOT NULL,
                name VARCHAR(255) NOT NULL,
                action ENUM('preserve', 'delete') NOT NULL,
                description VARCHAR(255),
                is_active BOOLEAN DEFAULT TRUE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
        
        // Create API tokens table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS api_tokens (
                id INT AUTO_INCREMENT PRIMARY KEY,
                extension_id VARCHAR(255) NOT NULL,
                token VARCHAR(255) NOT NULL UNIQUE,
                expires_at DATETIME NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                last_used_at DATETIME
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
        
        // Insert default admin user if none exists
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'admin'");
        $stmt->execute();
        $adminCount = $stmt->fetchColumn();
        
        if ($adminCount == 0) {
            $defaultAdmin = [
                'username' => 'admin',
                'password' => password_hash('admin123', PASSWORD_DEFAULT),
                'email' => 'admin@semrushtoolz.com',
                'role' => 'admin'
            ];
            
            $stmt = $pdo->prepare("
                INSERT INTO users (username, password, email, role)
                VALUES (:username, :password, :email, :role)
            ");
            
            $stmt->execute($defaultAdmin);
        }

        // Add this line at the end of the existing initializeDatabase() function, before the return true;
        // Initialize conflict management tables
        initializeConflictTables($pdo);
        
        return true;
    } catch (PDOException $e) {
        // Log error
        error_log("Database initialization failed: " . $e->getMessage());
        return false;
    }
}


/**
 * Initialize extension conflict management tables
 * 
 * @param PDO $pdo Database connection
 * @return bool Success status
 */
function initializeConflictTables($pdo) {
    try {
        // Create extension conflicts table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS extension_conflicts (
                id INT AUTO_INCREMENT PRIMARY KEY,
                extension_id VARCHAR(255) NOT NULL,
                conflict_extension_id VARCHAR(255) NOT NULL,
                conflict_extension_name VARCHAR(255) NOT NULL,
                detection_method VARCHAR(100) NOT NULL,
                violation_reported BOOLEAN DEFAULT FALSE,
                resolved_at DATETIME NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_extension_id (extension_id),
                INDEX idx_conflict_ext_id (conflict_extension_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
        
        // Create conflicting extensions blacklist table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS conflicting_extensions_blacklist (
                id INT AUTO_INCREMENT PRIMARY KEY,
                extension_id VARCHAR(255) NOT NULL UNIQUE,
                extension_name VARCHAR(255) NOT NULL,
                category ENUM('cookie_manager', 'privacy_tool', 'ad_blocker', 'developer_tool', 'security', 'other') NOT NULL DEFAULT 'other',
                detection_pattern VARCHAR(255),
                severity ENUM('low', 'medium', 'high', 'critical') NOT NULL DEFAULT 'medium',
                action_required ENUM('warn', 'disable', 'block') NOT NULL DEFAULT 'block',
                is_active BOOLEAN DEFAULT TRUE,
                added_by VARCHAR(50) DEFAULT 'system',
                notes TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
        
        // Create violation logs table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS violation_logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                extension_id VARCHAR(255) NOT NULL,
                user_ip VARCHAR(45),
                user_agent TEXT,
                violation_type VARCHAR(100) NOT NULL,
                violation_details JSON,
                conflicts_detected INT DEFAULT 0,
                action_taken VARCHAR(100),
                resolved BOOLEAN DEFAULT FALSE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_extension_id (extension_id),
                INDEX idx_violation_type (violation_type),
                INDEX idx_created_at (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
        
        // Insert default conflicting extensions
        $defaultConflictingExtensions = [
            // Cookie Management
            ['fngmhnnpilhplaeedifhccceomclgfbg', 'EditThisCookie', 'cookie_manager', NULL, 'critical', 'block'],
            ['hhojmcideegghqohhfcidlbnkgchpajgn', 'Cookie Editor', 'cookie_manager', NULL, 'critical', 'block'],
            ['jiaojkejmjfiomnkdommeodcnl', 'Cookie Editor Pro', 'cookie_manager', NULL, 'critical', 'block'],
            ['fhcgjolkccmbidfldomjliifgaodjagh', 'Cookie AutoDelete', 'cookie_manager', NULL, 'high', 'block'],
            ['ldpochfhpslkijphhbnpigkbjgejgnag', 'CookieBlock', 'cookie_manager', NULL, 'high', 'block'],
            
            // Ad Blockers
            ['cjpalhdlnbpafiamejdnhcphjbkeiagm', 'uBlock Origin', 'ad_blocker', NULL, 'high', 'block'],
            ['gighmmpiobklfepjocnamgkkbiglidom', 'AdBlock', 'ad_blocker', NULL, 'high', 'block'],
            ['cfhdojbkjhnklbpkdaibdccddilifddb', 'Adblock Plus', 'ad_blocker', NULL, 'high', 'block'],
            ['aapbdbdomjkkjkaonfhkkikfgjllcleb', 'Ghostery', 'ad_blocker', NULL, 'high', 'block'],
            ['pkehgijcmpdhfbdbbnkijodmdjhbjlgp', 'Privacy Badger', 'privacy_tool', NULL, 'high', 'block'],
            
            // Privacy Tools
            ['jlmpjdjjbgclbocgajdjefcidcncaied', 'ClearURLs', 'privacy_tool', NULL, 'medium', 'block'],
            ['bkdgflcldnnnapblkhphbgpggdiikppg', 'DuckDuckGo Privacy Essentials', 'privacy_tool', NULL, 'medium', 'block'],
            ['ifdepgnnjaidhhpbiacfknaiklleclhp', 'Privacy Cleaner Pro', 'privacy_tool', NULL, 'high', 'block'],
            
            // Developer Tools
            ['bfbmjmiodbnnpllbbbfblcplfjjepjdn', 'Web Developer', 'developer_tool', NULL, 'medium', 'disable'],
            ['ljjemllljcmogpfapbkkighbhhppjdbg', 'Chrome DevTools', 'developer_tool', NULL, 'low', 'warn'],
            
            // Security Extensions
            ['kcnhkahbjbkbpngjhlhmellmfoopdijm', 'Avira Browser Safety', 'security', NULL, 'medium', 'block'],
            ['jlicmakdihplkagblhpjomaknkeojaoa', 'Avast Online Security', 'security', NULL, 'medium', 'block'],
            ['igopjcpkhnlhmbloglbdafciddojeepj', 'Kaspersky Security Cloud', 'security', NULL, 'medium', 'block']
        ];
        
        // Check if default extensions are already inserted
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM conflicting_extensions_blacklist");
        $stmt->execute();
        $count = $stmt->fetchColumn();
        
        if ($count == 0) {
            $insertStmt = $pdo->prepare("
                INSERT INTO conflicting_extensions_blacklist 
                (extension_id, extension_name, category, detection_pattern, severity, action_required)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            foreach ($defaultConflictingExtensions as $ext) {
                $insertStmt->execute($ext);
            }
            
            error_log("Inserted " . count($defaultConflictingExtensions) . " default conflicting extensions");
        }
        
        return true;
    } catch (PDOException $e) {
        error_log("Conflict tables initialization failed: " . $e->getMessage());
        return false;
    }
}