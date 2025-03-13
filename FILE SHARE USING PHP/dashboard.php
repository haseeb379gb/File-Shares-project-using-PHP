<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Check if user is logged in
if (!is_logged_in()) {
    redirect('login.php');
}

// Get user information
$user_id = $_SESSION['user_id'];
$db = new Database();
$user = $db->single("SELECT * FROM users WHERE id = ?", [$user_id]);

// Get user's files
$files = $db->query("SELECT * FROM files WHERE user_id = ? ORDER BY upload_date DESC", [$user_id]);

// Process file deletion
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $file_id = $_GET['delete'];
    
    // Check if file belongs to user
    $file = $db->single("SELECT * FROM files WHERE id = ? AND user_id = ?", [$file_id, $user_id]);
    
    if ($file) {
        // Delete file from storage
        $file_path = UPLOAD_DIR . '/' . $file['stored_filename'];
        if (file_exists($file_path)) {
            unlink($file_path);
        }
        
        // Delete file from database
        $db->delete("DELETE FROM files WHERE id = ?", [$file_id]);
        
        // Redirect to refresh page
        redirect('dashboard.php?deleted=1');
    }
}

include_once 'includes/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-3">
            <div class="card bg-dark text-light mb-4">
                <div class="card-body text-center">
                    <div class="avatar-container mb-3">
                        <img src="assets/images/avatar.jpg" alt="Profile Avatar" class="rounded-circle" width="100" height="100">
                    </div>
                    <h5 class="mb-1"><?php echo htmlspecialchars($user['full_name'] ?: $user['username']); ?></h5>
                    <p class="text-muted mb-3">@<?php echo htmlspecialchars($user['username']); ?></p>
                    <div class="d-grid">
                        <a href="profile.php" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-user-edit me-2"></i> Edit Profile
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="list-group mb-4">
                <a href="dashboard.php" class="list-group-item list-group-item-action active bg-primary border-primary">
                    <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                </a>
                <a href="my-files.php" class="list-group-item list-group-item-action bg-dark text-light border-secondary">
                    <i class="fas fa-file-alt me-2"></i> My Files
                </a>
                <a href="upload.php" class="list-group-item list-group-item-action bg-dark text-light border-secondary">
                    <i class="fas fa-cloud-upload-alt me-2"></i> Upload Files
                </a>
                <a href="settings.php" class="list-group-item list-group-item-action bg-dark text-light border-secondary">
                    <i class="fas fa-cog me-2"></i> Settings
                </a>
                <a href="logout.php" class="list-group-item list-group-item-action bg-dark text-light border-secondary">
                    <i class="fas fa-sign-out-alt me-2"></i> Logout
                </a>
            </div>
            
            <div class="card bg-dark text-light">
                <div class="card-body">
                    <h5 class="card-title">Storage Usage</h5>
                    <div class="storage-progress mb-2">
                        <?php
                        // Calculate storage usage
                        $total_storage = 1024 * 1024 * 1024; // 1GB
                        $used_storage = 0;
                        
                        foreach ($files as $file) {
                            $used_storage += $file['file_size'];
                        }
                        
                        $usage_percentage = min(100, ($used_storage / $total_storage) * 100);
                        ?>
                        <div class="progress bg-secondary">
                            <div class="progress-bar bg-primary" role="progressbar" style="width: <?php echo $usage_percentage; ?>%" aria-valuenow="<?php echo $usage_percentage; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                    </div>
                    <p class="mb-0 small">
                        <span class="text-muted"><?php echo formatFileSize($used_storage); ?> used</span>
                        <span class="float-end text-muted"><?php echo formatFileSize($total_storage); ?> total</span>
                    </p>
                </div>
            </div>
        </div>
        
        <div class="col-md-9">
            <?php if (isset($_GET['deleted']) && $_GET['deleted'] == 1): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i> File has been deleted successfully.
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <div class="card bg-dark text-light mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Dashboard</h5>
                    <a href="upload.php" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus me-2"></i> Upload New File
                    </a>
                </div>
                <div class="card-body">
                    <div class="row g-4">
                        <div class="col-md-4">
                            <div class="card bg-primary bg-opacity-10 border-primary">
                                <div class="card-body text-center">
                                    <i class="fas fa-file-alt fa-3x mb-3 text-primary"></i>
                                    <h5 class="card-title"><?php echo count($files); ?></h5>
                                    <p class="card-text">Total Files</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="card bg-success bg-opacity-10 border-success">
                                <div class="card-body text-center">
                                    <i class="fas fa-download fa-3x mb-3 text-success"></i>
                                    <?php
                                    // Calculate total downloads
                                    $total_downloads = 0;
                                    foreach ($files as $file) {
                                        $total_downloads += $file['download_count'];
                                    }
                                    ?>
                                    <h5 class="card-title"><?php echo $total_downloads; ?></h5>
                                    <p class="card-text">Total Downloads</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="card bg-info bg-opacity-10 border-info">
                                <div class="card-body text-center">
                                    <i class="fas fa-hdd fa-3x mb-3 text-info"></i>
                                    <h5 class="card-title"><?php echo formatFileSize($used_storage); ?></h5>
                                    <p class="card-text">Storage Used</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card bg-dark text-light">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Recent Files</h5>
                    <a href="my-files.php" class="btn btn-outline-primary btn-sm">View All Files</a>
                </div>
                <div class="card-body p-0">
                    <?php if (count($files) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-dark table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>File Name</th>
                                        <th>Size</th>
                                        <th>Uploaded</th>
                                        <th>Downloads</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    // Display only the 5 most recent files
                                    $recent_files = array_slice($files, 0, 5);
                                    foreach ($recent_files as $file): 
                                    ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <?php
                                                // Determine file icon based on file type
                                                $file_extension = pathinfo($file['original_filename'], PATHINFO_EXTENSION);
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
                                                <i class="fas <?php echo $icon_class; ?> me-2"></i>
                                                <span><?php echo htmlspecialchars($file['original_filename']); ?></span>
                                            </div>
                                        </td>
                                        <td><?php echo formatFileSize($file['file_size']); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($file['upload_date'])); ?></td>
                                        <td><?php echo $file['download_count']; ?></td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="download.php?id=<?php echo $file['id']; ?>" class="btn btn-outline-primary" data-bs-toggle="tooltip" title="Download">
                                                    <i class="fas fa-download"></i>
                                                </a>
                                                <button type="button" class="btn btn-outline-info copy-link-btn" data-link="<?php echo SITE_URL . '/download.php?id=' . $file['id']; ?>" data-bs-toggle="tooltip" title="Copy Link">
                                                    <i class="fas fa-link"></i>
                                                </button>
                                                <a href="edit-file.php?id=<?php echo $file['id']; ?>" class="btn btn-outline-warning" data-bs-toggle="tooltip" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="dashboard.php?delete=<?php echo $file['id']; ?>" class="btn btn-outline-danger" onclick="return confirm('Are you sure you want to delete this file?');" data-bs-toggle="tooltip" title="Delete">
                                                    <i class="fas fa-trash-alt"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="fas fa-file-upload fa-4x text-muted mb-3"></i>
                            <h5>No files uploaded yet</h5>
                            <p class="text-muted">Start uploading files to see them here.</p>
                            <a href="upload.php" class="btn btn-primary mt-2">
                                <i class="fas fa-cloud-upload-alt me-2"></i> Upload Files
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    });
    
    // Copy link functionality
    const copyButtons = document.querySelectorAll('.copy-link-btn');
    copyButtons.forEach(button => {
        button.addEventListener('click', function() {
            const link = this.getAttribute('data-link');
            
            // Create temporary input element
            const tempInput = document.createElement('input');
            tempInput.value = link;
            document.body.appendChild(tempInput);
            
            // Select and copy
            tempInput.select();
            document.execCommand('copy');
            document.body.removeChild(tempInput);
            
            // Update tooltip
            const tooltip = bootstrap.Tooltip.getInstance(this);
            if (tooltip) {
                tooltip.hide();
                this.setAttribute('data-bs-original-title', 'Copied!');
                tooltip.show();
                
                // Reset tooltip after 2 seconds
                setTimeout(() => {
                    this.setAttribute('data-bs-original-title', 'Copy Link');
                }, 2000);
            }
        });
    });
});
</script>

<?php include_once 'includes/footer.php'; ?>

