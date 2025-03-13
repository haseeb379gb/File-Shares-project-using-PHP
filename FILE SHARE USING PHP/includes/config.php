<?php
/**
 * Configuration file for FileShare application
 */

// Security settings - MUST be set before session_start()
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    ini_set('session.cookie_secure', 1);
}

// Start session
session_start();

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'fileshare');
define('DB_USER', 'root');
define('DB_PASS', '');

// Site configuration
define('SITE_NAME', 'FileShare');
define('SITE_URL', 'http://localhost/fileshare');
define('ADMIN_EMAIL', 'admin@fileshare.com');

// File upload configuration
define('UPLOAD_DIR', __DIR__ . '/../uploads');
define('MAX_FILE_SIZE', 100 * 1024 * 1024); // 100MB

// Create uploads directory if it doesn't exist
if (!file_exists(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0755, true);
}

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Time zone
date_default_timezone_set('UTC');

// Include required files
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

// Clean expired files (run once per day)
$last_cleanup = isset($_SESSION['last_cleanup']) ? $_SESSION['last_cleanup'] : 0;
if (time() - $last_cleanup > 86400) { // 24 hours
    clean_expired_files();
    $_SESSION['last_cleanup'] = time();
}

