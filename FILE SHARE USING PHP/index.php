<?php
require_once 'config.php';
require_once 'functions.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FileShare - Secure File Sharing</title>
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
            <div class="col-md-8 text-center">
                <h1 class="display-4 mb-3">Upload & Share Files</h1>
                <p class="lead mb-5">Securely share files with password protection, download limits, and expiration dates</p>
            </div>
        </div>

        <div class="row justify-content-center">
            <div class="col-md-8">
                <ul class="nav nav-tabs" id="myTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="upload-tab" data-bs-toggle="tab" data-bs-target="#upload" type="button" role="tab" aria-controls="upload" aria-selected="true">Upload</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="options-tab" data-bs-toggle="tab" data-bs-target="#options" type="button" role="tab" aria-controls="options" aria-selected="false">Options</button>
                    </li>
                </ul>

                <div class="tab-content" id="myTabContent">
                    <!-- Upload Tab -->
                    <div class="tab-pane fade show active" id="upload" role="tabpanel" aria-labelledby="upload-tab">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title">Upload File</h5>
                                <p class="card-subtitle text-muted">Drag and drop your file or click to browse</p>
                            </div>
                            <div class="card-body">
                                <form id="uploadForm" action="upload.php" method="post" enctype="multipart/form-data">
                                    <div id="dropArea" class="drop-area">
                                        <input type="file" id="fileInput" name="file" class="file-input" />
                                        <div class="drop-message">
                                            <i class="fas fa-cloud-upload-alt fa-3x mb-3"></i>
                                            <p>Drag & drop a file here, or click to select</p>
                                            <p class="small text-muted">Maximum file size: 100MB</p>
                                        </div>
                                        <div id="fileInfo" class="file-info d-none">
                                            <i class="fas fa-file fa-2x me-3"></i>
                                            <div>
                                                <p id="fileName" class="mb-0"></p>
                                                <p id="fileSize" class="small text-muted"></p>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Hidden fields for options -->
                                    <input type="hidden" id="passwordProtected" name="password_protected" value="0">
                                    <input type="hidden" id="passwordField" name="password" value="">
                                    <input type="hidden" id="downloadLimit" name="download_limit" value="0">
                                    <input type="hidden" id="expirationDays" name="expiration_days" value="7">

                                    <div id="uploadProgress" class="progress mt-3 d-none">
                                        <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%"></div>
                                    </div>

                                    <div id="uploadResult" class="mt-3 d-none">
                                        <div class="alert alert-success">
                                            <h5>File uploaded successfully!</h5>
                                            <p>Share this link with others:</p>
                                            <div class="input-group">
                                                <input type="text" id="shareLink" class="form-control" readonly>
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
                                    </div>

                                    <div class="d-flex justify-content-between mt-3">
                                        <button type="button" id="clearButton" class="btn btn-outline-secondary" disabled>Clear</button>
                                        <button type="submit" id="uploadButton" class="btn btn-primary" disabled>Upload</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Options Tab -->
                    <div class="tab-pane fade" id="options" role="tabpanel" aria-labelledby="options-tab">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title">File Options</h5>
                                <p class="card-subtitle text-muted">Configure security and sharing options</p>
                            </div>
                            <div class="card-body">
                                <!-- Password Protection -->
                                <div class="mb-4">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <div>
                                            <label class="form-label">Password Protection</label>
                                            <p class="text-muted small">Protect your file with a password</p>
                                        </div>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="passwordSwitch">
                                        </div>
                                    </div>
                                    <div id="passwordContainer" class="d-none">
                                        <input type="password" class="form-control" id="password" placeholder="Enter a secure password">
                                    </div>
                                </div>

                                <!-- Download Limit -->
                                <div class="mb-4">
                                    <label for="downloadLimitRange" class="form-label d-flex justify-content-between">
                                        <span>Download Limit</span>
                                        <span id="downloadLimitValue">Unlimited</span>
                                    </label>
                                    <input type="range" class="form-range" id="downloadLimitRange" min="0" max="20" step="1" value="0">
                                </div>

                                <!-- Expiration Time -->
                                <div class="mb-4">
                                    <label for="expirationRange" class="form-label d-flex justify-content-between">
                                        <span>Expiration Time</span>
                                        <span id="expirationValue">7 days</span>
                                    </label>
                                    <input type="range" class="form-range" id="expirationRange" min="0" max="30" step="1" value="7">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row justify-content-center mt-5">
            <div class="col-md-8">
                <div class="row">
                    <div class="col-md-4 mb-4">
                        <div class="card feature-card">
                            <div class="card-body text-center">
                                <i class="fas fa-shield-alt fa-3x mb-3"></i>
                                <h5>Secure Storage</h5>
                                <p class="text-muted">Your files are encrypted and stored securely</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-4">
                        <div class="card feature-card">
                            <div class="card-body text-center">
                                <i class="fas fa-link fa-3x mb-3"></i>
                                <h5>Easy Sharing</h5>
                                <p class="text-muted">Share files with a simple link</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-4">
                        <div class="card feature-card">
                            <div class="card-body text-center">
                                <i class="fas fa-clock fa-3x mb-3"></i>
                                <h5>Expiration Control</h5>
                                <p class="text-muted">Set download limits and expiration dates</p>
                            </div>
                        </div>
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

