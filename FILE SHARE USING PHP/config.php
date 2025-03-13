<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'fileshare');

// Upload directory
define('UPLOAD_DIR', __DIR__ . '/uploads');

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Set maximum file upload size
ini_set('upload_max_filesize', '100M');
ini_set('post_max_size', '100M');
ini_set('max_execution_time', 300);
ini_set('memory_limit', '256M');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

