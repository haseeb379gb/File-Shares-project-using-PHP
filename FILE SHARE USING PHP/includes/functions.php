<?php
/**
 * Functions for FileShare application
 */

// Check if user is logged in
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

// Redirect to a URL
function redirect($url) {
    header("Location: $url");
    exit;
}

// Generate a random token
function generate_token($length = 32) {
    return bin2hex(random_bytes($length / 2));
}

// Format file size
function formatFileSize($bytes) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= (1 << (10 * $pow));
    
    return round($bytes, 2) . ' ' . $units[$pow];
}

// Get file type icon
function getFileTypeIcon($mime_type) {
    $type = explode('/', $mime_type)[0];
    
    switch ($type) {
        case 'image':
            return 'image';
        case 'video':
            return 'video';
        case 'audio':
            return 'audio';
        case 'application':
            if (strpos($mime_type, 'pdf') !== false) {
                return 'document';
            } elseif (strpos($mime_type, 'msword') !== false || strpos($mime_type, 'document') !== false) {
                return 'document';
            } elseif (strpos($mime_type, 'excel') !== false || strpos($mime_type, 'spreadsheet') !== false) {
                return 'document';
            } elseif (strpos($mime_type, 'zip') !== false || strpos($mime_type, 'rar') !== false || strpos($mime_type, 'tar') !== false) {
                return 'archive';
            }
            return 'other';
        default:
            return 'other';
    }
}

// Get file information
function get_file_info($file_id) {
    $db = new Database();
    $file = $db->single("SELECT * FROM files WHERE id = ?", [$file_id]);
    
    if (!$file) {
        return null;
    }
    
    return [
        'id' => $file['id'],
        'filename' => $file['original_filename'],
        'stored_filename' => $file['stored_filename'],
        'filesize' => $file['file_size'],
        'filetype' => $file['file_type'],
        'password_protected' => !empty($file['password']),
        'password' => $file['password'],
        'expiration_date' => $file['expiration_date'],
        'download_limit' => $file['download_limit'],
        'downloads_count' => $file['download_count'],
        'upload_date' => $file['upload_date'],
        'user_id' => $file['user_id']
    ];
}

// Increment download count
function increment_download_count($file_id) {
    $db = new Database();
    $db->update("UPDATE files SET download_count = download_count + 1 WHERE id = ?", [$file_id]);
}

// Get base URL
function get_base_url() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $script_name = dirname($_SERVER['SCRIPT_NAME']);
    $base_url = $protocol . '://' . $host . $script_name;
    
    // Remove trailing slash if present
    if (substr($base_url, -1) === '/') {
        $base_url = substr($base_url, 0, -1);
    }
    
    return $base_url;
}

// Check if file has expired
function has_file_expired($file) {
    if (empty($file['expiration_date'])) {
        return false;
    }
    
    return strtotime($file['expiration_date']) < time();
}

// Check if download limit has been reached
function has_reached_download_limit($file) {
    if ($file['download_limit'] <= 0) {
        return false;
    }
    
    return $file['download_count'] >= $file['download_limit'];
}

// Validate password for file
function validate_file_password($file, $password) {
    if (empty($file['password'])) {
        return true;
    }
    
    return $file['password'] === $password;
}

// Clean expired files
function clean_expired_files() {
    $db = new Database();
    
    // Get expired files - FIXED: changed expiry_date to expiration_date
    $expired_files = $db->query(
        "SELECT * FROM files WHERE expiration_date IS NOT NULL AND expiration_date < NOW()"
    );
    
    foreach ($expired_files as $file) {
        // Delete file from storage
        $file_path = UPLOAD_DIR . '/' . $file['stored_filename'];
        if (file_exists($file_path)) {
            unlink($file_path);
        }
    }
    
    // Delete expired files from database - FIXED: changed expiry_date to expiration_date
    $db->delete("DELETE FROM files WHERE expiration_date IS NOT NULL AND expiration_date < NOW()");
    
    return count($expired_files);
}

// Generate a unique file ID
function generate_file_id() {
    return substr(md5(uniqid(mt_rand(), true)), 0, 10);
}

// Sanitize filename
function sanitize_filename($filename) {
    // Remove any path information
    $filename = basename($filename);
    
    // Replace spaces with underscores
    $filename = str_replace(' ', '_', $filename);
    
    // Remove special characters
    $filename = preg_replace('/[^a-zA-Z0-9_.-]/', '', $filename);
    
    return $filename;
}

// Get file extension
function get_file_extension($filename) {
    return strtolower(pathinfo($filename, PATHINFO_EXTENSION));
}

// Check if file type is allowed
function is_allowed_file_type($filename) {
    $allowed_extensions = [
        'jpg', 'jpeg', 'png', 'gif', 'bmp', 'svg', 'webp',  // Images
        'pdf', 'doc', 'docx', 'txt', 'rtf', 'odt',          // Documents
        'xls', 'xlsx', 'csv',                               // Spreadsheets
        'zip', 'rar', 'tar', 'gz', '7z',                    // Archives
        'mp3', 'wav', 'ogg', 'flac', 'aac',                 // Audio
        'mp4', 'avi', 'mov', 'wmv', 'flv', 'webm'           // Video
    ];
    
    $extension = get_file_extension($filename);
    
    return in_array($extension, $allowed_extensions);
}

// Check if file size is allowed
function is_allowed_file_size($file_size) {
    $max_size = MAX_FILE_SIZE; // Defined in config.php
    
    return $file_size <= $max_size;
}

