<?php
require_once 'config.php';
require_once 'functions.php';

// Set content type to JSON
header('Content-Type: application/json');

// Check if file was uploaded
if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode([
        'success' => false,
        'message' => 'No file uploaded or upload error occurred.'
    ]);
    exit;
}

// Get file information
$file = $_FILES['file'];
$filename = $file['name'];
$filesize = $file['size'];
$filetype = $file['type'];
$tmp_path = $file['tmp_name'];

// Check file size (100MB limit)
$max_size = 100 * 1024 * 1024; // 100MB in bytes
if ($filesize > $max_size) {
    echo json_encode([
        'success' => false,
        'message' => 'File size exceeds the maximum limit of 100MB.'
    ]);
    exit;
}

// Get options from POST data
$password_protected = isset($_POST['password_protected']) && $_POST['password_protected'] === '1';
$password = $password_protected ? $_POST['password'] : '';
$download_limit = isset($_POST['download_limit']) ? intval($_POST['download_limit']) : 0;
$expiration_days = isset($_POST['expiration_days']) ? intval($_POST['expiration_days']) : 7;

// Generate a unique file ID
$file_id = generate_unique_id();

// Create a unique filename for storage
$stored_filename = $file_id . '-' . preg_replace('/[^a-zA-Z0-9.-]/', '_', $filename);

// Calculate expiration date
$expiration_date = null;
if ($expiration_days > 0) {
    $expiration_date = date('Y-m-d H:i:s', strtotime("+{$expiration_days} days"));
}

// Ensure upload directory exists
if (!file_exists(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0755, true);
}

// Move uploaded file to storage directory
if (move_uploaded_file($tmp_path, UPLOAD_DIR . '/' . $stored_filename)) {
    // Store file metadata in database
    $conn = get_db_connection();
    
    $stmt = $conn->prepare("INSERT INTO files (id, filename, stored_filename, filesize, filetype, password_protected, password, download_limit, downloads_count, expiration_date, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0, ?, NOW())");
    
    $stmt->bind_param('sssisisis', 
        $file_id, 
        $filename, 
        $stored_filename, 
        $filesize, 
        $filetype, 
        $password_protected_int, 
        $password, 
        $download_limit, 
        $expiration_date
    );
    
    $password_protected_int = $password_protected ? 1 : 0;
    
    if ($stmt->execute()) {
        // Generate the file URL
        $file_url = get_base_url() . '/download.php?id=' . $file_id;
        
        echo json_encode([
            'success' => true,
            'file_id' => $file_id,
            'file_url' => $file_url
        ]);
    } else {
        // Database error
        echo json_encode([
            'success' => false,
            'message' => 'Failed to store file information in database.'
        ]);
        
        // Remove the uploaded file
        @unlink(UPLOAD_DIR . '/' . $stored_filename);
    }
    
    $stmt->close();
    $conn->close();
} else {
    // Failed to move uploaded file
    echo json_encode([
        'success' => false,
        'message' => 'Failed to store the uploaded file.'
    ]);
}

