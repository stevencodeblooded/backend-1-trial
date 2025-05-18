<?php
/**
 * Admin Logout
 * 
 * Handles logging out of the admin panel
 */

// Include configuration
require_once '../config/config.php';

// Include required files
require_once INCLUDES_PATH . '/functions.php';

// Start session
startSecureSession();

// Destroy session
session_destroy();

// Redirect to login page
redirect('/semrush-backend/admin/login.php');