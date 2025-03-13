<?php
/**
 * Get database connection
 * 
 * @return mysqli Database connection
 */
function get_db_connection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        die("Database connection failed: " . $conn->connect_error);
    }
    
    return $conn;
}

/**
 * Generate a unique ID for files
 * 
 * @return string Unique ID
 */
function generate_unique_id() {
    return bin2hex(random_bytes(16));
}

/**
 * Get base URL of the application
 * 
 * @return string Base URL
 */
function get_base_url() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $script_name = dirname($_SERVER['SCRIPT_NAME']);
    
    // Remove "/upload.php" if present
    $base_path = $script_name === '/upload.php' ? '' : $script_name;
    
    return $protocol . '://' . $host . $base_path;
}

/**
 * Get file information by ID
 * 
 * @param string $file_id File ID
 * @return array|false File information or false if not found
 */
function get_file_info($file_id) {
    $conn = get_db_connection();
    
    $stmt = $conn->prepare("SELECT * FROM files WHERE id = ?");
    $stmt->bind_param('s', $file_id);
    $stmt->execute();
    
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $stmt->close();
        $conn->close();
        return false;
    }
    
    $file_info = $result->fetch_assoc();
    
    $stmt->close();
    $conn->close();
    
    return $file_info;
}

/**
 * Increment download count for a file
 * 
 * @param string $file_id File ID
 * @return bool Success status
 */
function increment_download_count($file_id) {
    $conn = get_db_connection();
    
    $stmt = $conn->prepare("UPDATE files SET downloads_count = downloads_count + 1 WHERE id = ?");
    $stmt->bind_param('s', $file_id);
    $result = $stmt->execute();
    
    $stmt->close();
    $conn->close();
    
    return $result;
}

/**
 * Format file size in human-readable format
 * 
 * @param int $bytes File size in bytes
 * @return string Formatted file size
 */
function format_file_size($bytes) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    
    $bytes /= pow(1024, $pow);
    
    return round($bytes, 2) . ' ' . $units[$pow];
}

