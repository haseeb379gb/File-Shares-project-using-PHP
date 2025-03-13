<?php
require_once 'config.php';
require_once 'functions.php';

// Get file ID from URL
$file_id = isset($_GET['id']) ? $_GET['id'] : '';
$error_message = '';
$file_info = null;
$password_required = false;
$password_error = false;

// Check if file ID is provided
if (empty($file_id)) {
    $error_message = 'Invalid file link.';
} else {
    // Get file information
    $file_info = get_file_info($file_id);
    
    if (!$file_info) {
        $error_message = 'File not found or has been removed.';
    } else {
        // Check if file has expired
        if ($file_info['expiration_date'] && strtotime($file_info['expiration_date']) < time()) {
            $error_message = 'This file has expired.';
        }
        
        // Check if download limit has been reached
        if ($file_info['download_limit'] > 0 && $file_info['downloads_count'] >= $file_info['download_limit']) {
            $error_message = 'Download limit has been reached.';
        }
        
        // Check if password is required
        if ($file_info['password_protected']) {
            $password_required = true;
            
            // Check if password was submitted
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
                $submitted_password = $_POST['password'];
                
                // Verify password
                if ($submitted_password === $file_info['password']) {
                    // Password is correct, proceed with download
                    increment_download_count($file_id);
                    
                    // Get file path
                    $file_path = UPLOAD_DIR . '/' . $file_info['stored_filename'];
                    
                    // Check if file exists
                    if (file_exists($file_path)) {
                        // Set headers for download
                        header('Content-Description: File Transfer');
                        header('Content-Type: application/octet-stream');
                        header('Content-Disposition: attachment; filename="' . $file_info['filename'] . '"');
                        header('Expires: 0');
                        header('Cache-Control: must-revalidate');
                        header('Pragma: public');
                        header('Content-Length: ' . filesize($file_path));
                        
                        // Clear output buffer
                        ob_clean();
                        flush();
                        
                        // Read file and output to browser
                        readfile($file_path);
                        exit;
                    } else {
                        $error_message = 'File not found on server.';
                    }
                } else {
                    $password_error = true;
                }
            }
        } else {
            // No password required, check if download button was clicked
            if (isset($_GET['download']) && $_GET['download'] === 'true') {
                // Increment download count
                increment_download_count($file_id);
                
                // Get file path
                $file_path = UPLOAD_DIR . '/' . $file_info['stored_filename'];
                
                // Check if file exists
                if (file_exists($file_path)) {
                    // Set headers for download
                    header('Content-Description: File Transfer');
                    header('Content-Type: application/octet-stream');
                    header('Content-Disposition: attachment; filename="' . $file_info['filename'] . '"');
                    header('Expires: 0');
                    header('Cache-Control: must-revalidate');
                    header('Pragma: public');
                    header('Content-Length: ' . filesize($file_path));
                    
                    // Clear output buffer
                    ob_clean();
                    flush();
                    
                    // Read file and output to browser
                    readfile($file_path);
                    exit;
                } else {
                    $error_message = 'File not found on server.';
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Download File - FileShare</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/custom.css">
</head>
<body>
    <!-- Navigation Bar -->
    <?php include 'navbar.php'; ?>

    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-6 text-center">
                <h1 class="h3 mb-4">Download File</h1>
            </div>
        </div>

        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Download File</h5>
                        <?php if ($password_required): ?>
                            <p class="card-subtitle text-muted">This file is password protected</p>
                        <?php else: ?>
                            <p class="card-subtitle text-muted">Ready to download</p>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <?php if ($error_message): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-exclamation-triangle fa-4x text-warning mb-3"></i>
                                <h5>File Not Available</h5>
                                <p><?php echo $error_message; ?></p>
                            </div>
                        <?php elseif ($file_info): ?>
                            <div class="file-info-box mb-4">
                                <?php
                                // Determine file icon based on file type
                                $file_extension = pathinfo($file_info['filename'], PATHINFO_EXTENSION);
                                $icon_class = 'fa-file';
                                
                                // Map common extensions to icons
                                $image_extensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'svg', 'webp'];
                                $document_extensions = ['pdf', 'doc', 'docx', 'txt', 'rtf', 'odt'];
                                $spreadsheet_extensions = ['xls', 'xlsx', 'csv'];
                                $archive_extensions = ['zip', 'rar', 'tar', 'gz', '7z'];
                                $audio_extensions = ['mp3', 'wav', 'ogg', 'flac', 'aac'];
                                $video_extensions = ['mp4', 'avi', 'mov', 'wmv', 'flv', 'webm'];
                                
                                if (in_array(strtolower($file_extension), $image_extensions)) {
                                    $icon_class = 'fa-file-image';
                                } elseif (in_array(strtolower($file_extension), $document_extensions)) {
                                    $icon_class = 'fa-file-alt';
                                } elseif (in_array(strtolower($file_extension), $spreadsheet_extensions)) {
                                    $icon_class = 'fa-file-excel';
                                } elseif (in_array(strtolower($file_extension), $archive_extensions)) {
                                    $icon_class = 'fa-file-archive';
                                } elseif (in_array(strtolower($file_extension), $audio_extensions)) {
                                    $icon_class = 'fa-file-audio';
                                } elseif (in_array(strtolower($file_extension), $video_extensions)) {
                                    $icon_class = 'fa-file-video';
                                }
                                ?>
                                <i class="fas <?php echo $icon_class; ?> fa-3x me-3"></i>
                                <div>
                                    <h5 class="mb-1"><?php echo htmlspecialchars($file_info['filename']); ?></h5>
                                    <p class="text-muted mb-0"><?php echo format_file_size($file_info['filesize']); ?></p>
                                </div>
                            </div>

                            <!-- Share Link Section -->
                            <div class="mb-4">
                                <label class="form-label">Share this file with others:</label>
                                <div class="input-group">
                                    <input type="text" id="shareLink" class="form-control" value="<?php echo get_base_url() . '/download.php?id=' . $file_id; ?>" readonly>
                                    <button class="btn btn-primary copy-btn" type="button" id="copyLink" data-bs-toggle="tooltip" data-bs-placement="top" title="Copy to clipboard">
                                        <i class="fas fa-copy"></i> Copy
                                    </button>
                                </div>
                                <div class="copy-feedback mt-2 d-none">
                                    <div class="alert alert-info py-2">
                                        <i class="fas fa-check-circle me-2"></i> Link copied to clipboard!
                                    </div>
                                </div>
                            </div>

                            <?php if ($file_info['download_limit'] > 0): ?>
                                <p class="text-muted small">
                                    <i class="fas fa-download me-2"></i>
                                    Downloads remaining: <?php echo $file_info['download_limit'] - $file_info['downloads_count']; ?> of <?php echo $file_info['download_limit']; ?>
                                </p>
                            <?php endif; ?>

                            <?php if ($file_info['expiration_date']): ?>
                                <p class="text-muted small">
                                    <i class="fas fa-clock me-2"></i>
                                    Expires on: <?php echo date('F j, Y', strtotime($file_info['expiration_date'])); ?>
                                </p>
                            <?php endif; ?>

                            <?php if ($password_required): ?>
                                <form method="post" action="download.php?id=<?php echo $file_id; ?>">
                                    <div class="mb-3">
                                        <label for="password" class="form-label">Password</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                            <input type="password" class="form-control <?php echo $password_error ? 'is-invalid' : ''; ?>" id="password" name="password" placeholder="Enter file password" required>
                                            <?php if ($password_error): ?>
                                                <div class="invalid-feedback">
                                                    Incorrect password. Please try again.
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="fas fa-download me-2"></i> Download File
                                    </button>
                                </form>
                            <?php else: ?>
                                <a href="download.php?id=<?php echo $file_id; ?>&download=true" class="btn btn-primary w-100">
                                    <i class="fas fa-download me-2"></i> Download File
                                </a>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <footer class="text-center mt-5 mb-5">
            <p class="text-muted">&copy; <?php echo date('Y'); ?> FileShare. All rights reserved.</p>
        </footer>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script src="assets/js/script.js"></script>
</body>
</html>

