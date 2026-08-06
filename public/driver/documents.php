<?php
require_once dirname(dirname(__DIR__)) . '/app/controllers/DriverController.php';
require_once dirname(dirname(__DIR__)) . '/app/helpers/AuthHelper.php';

AuthHelper::startSession();

// Localized driver auth check
if (!AuthHelper::isLoggedIn()) {
    header("Location: login.php");
    exit();
}

$user = AuthHelper::getCurrentUser();
if (($user['role'] ?? '') !== 'driver') {
    AuthHelper::logout();
    header("Location: login.php");
    exit();
}

$driverController = new DriverController();

$error = '';
$success = '';

// Retrieve secure driver profile
$dashboardResult = $driverController->dashboard();
if (!$dashboardResult['success']) {
    $error = "Failed to load driver profile.";
    $driverId = 0;
} else {
    $driverId = $dashboardResult['profile']['id'];
}

// 1. Handle document delete action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_document') {
    if (!AuthHelper::validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = "CSRF security verification failed.";
    } else {
        $documentId = (int)($_POST['document_id'] ?? 0);
        $result = $driverController->deleteDocument($documentId);
        if ($result['success']) {
            $success = $result['message'];
        } else {
            $error = $result['error'];
        }
    }
}

// 2. Handle document upload submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload_document') {
    if (!AuthHelper::validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = "CSRF security verification failed.";
    } else {
        $documentType = $_POST['document_type'] ?? '';
        $documentNumber = trim($_POST['document_number'] ?? '');
        $expiryDate = $_POST['expiry_date'] ?? '';
        
        // Form field validations
        if (empty($documentType) || empty($documentNumber) || empty($expiryDate)) {
            $error = "All document fields are required.";
        } elseif (isset($_FILES['document_file']) && $_FILES['document_file']['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath = $_FILES['document_file']['tmp_name'];
            $fileName = $_FILES['document_file']['name'];
            $fileSize = $_FILES['document_file']['size'];
            $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            
            $allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png'];
            if (!in_array($fileExtension, $allowedExtensions)) {
                $error = "Only PDF, JPG, JPEG, and PNG files are allowed.";
            } elseif ($fileSize > 5 * 1024 * 1024) {
                $error = "Document file size exceeds the maximum limit of 5MB.";
            } else {
                // Verify actual MIME type (requires finfo)
                $mimeValid = true;
                if (function_exists('finfo_open')) {
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $mimeType = finfo_file($finfo, $fileTmpPath);
                    finfo_close($finfo);
                    
                    $allowedMimes = ['image/jpeg', 'image/png', 'application/pdf'];
                    if (!in_array($mimeType, $allowedMimes)) {
                        $mimeValid = false;
                        $error = "Invalid document file type. Only PDF, JPG, JPEG, and PNG are allowed.";
                    }
                }
                
                if ($mimeValid && $driverId > 0) {
                    $uploadBaseDir = dirname(dirname(__DIR__)) . '/public/uploads/';
                    $typeFolderMap = [
                        'nic'             => 'nics',
                        'driving_license' => 'licenses',
                        'police_report'   => 'police_reports'
                    ];
                    
                    $targetSubDir = $typeFolderMap[$documentType] ?? 'misc';
                    $targetDir = $uploadBaseDir . $targetSubDir . '/';
                    
                    if (!is_dir($targetDir)) {
                        mkdir($targetDir, 0755, true);
                    }
                    
                    $newFileName = 'driver_' . $driverId . '_' . time() . '.' . $fileExtension;
                    $destPath = $targetDir . $newFileName;
                    $dbFilePath = 'uploads/' . $targetSubDir . '/' . $newFileName;
                    
                    try {
                        if (move_uploaded_file($fileTmpPath, $destPath)) {
                            $uploadResult = $driverController->uploadDocument([
                                'document_type'   => $documentType,
                                'document_number' => $documentNumber,
                                'expiry_date'     => $expiryDate,
                                'file_path'       => $dbFilePath
                            ]);
                            
                            if ($uploadResult['success']) {
                                $success = $uploadResult['message'];
                            } else {
                                if (file_exists($destPath)) {
                                    unlink($destPath);
                                }
                                $error = $uploadResult['error'];
                            }
                        } else {
                            $error = "There was an error moving the uploaded document file.";
                        }
                    } catch (Exception $e) {
                        $error = "Error: " . $e->getMessage();
                    }
                }
            }
        } else {
            $error = "Please select a valid document file to upload.";
        }
    }
}

// 3. Handle document edit/replace submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_document') {
    if (!AuthHelper::validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = "CSRF security verification failed.";
    } else {
        $documentId = (int)($_POST['document_id'] ?? 0);
        $documentNumber = trim($_POST['document_number'] ?? '');
        $expiryDate = $_POST['expiry_date'] ?? '';
        
        if (empty($documentNumber) || empty($expiryDate)) {
            $error = "All document fields are required.";
        } else {
            $updateData = [
                'document_number' => $documentNumber,
                'expiry_date'     => $expiryDate
            ];

            $fileUploaded = false;
            $destPath = '';

            // Check if replacing document file
            if (isset($_FILES['document_file']) && $_FILES['document_file']['error'] === UPLOAD_ERR_OK) {
                $fileTmpPath = $_FILES['document_file']['tmp_name'];
                $fileName = $_FILES['document_file']['name'];
                $fileSize = $_FILES['document_file']['size'];
                $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                
                $allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png'];
                if (!in_array($fileExtension, $allowedExtensions)) {
                    $error = "Only PDF, JPG, JPEG, and PNG files are allowed.";
                } elseif ($fileSize > 5 * 1024 * 1024) {
                    $error = "Document file size exceeds the maximum limit of 5MB.";
                } else {
                    $mimeValid = true;
                    if (function_exists('finfo_open')) {
                        $finfo = finfo_open(FILEINFO_MIME_TYPE);
                        $mimeType = finfo_file($finfo, $fileTmpPath);
                        finfo_close($finfo);
                        
                        $allowedMimes = ['image/jpeg', 'image/png', 'application/pdf'];
                        if (!in_array($mimeType, $allowedMimes)) {
                            $mimeValid = false;
                            $error = "Invalid document file type. Only PDF, JPG, JPEG, and PNG are allowed.";
                        }
                    }
                    
                    if ($mimeValid && $driverId > 0) {
                        $docModel = new DriverDocument();
                        $oldDoc = $docModel->getById($documentId);
                        
                        if ($oldDoc && $oldDoc['driver_id'] === $driverId) {
                            $documentType = $oldDoc['document_type'];
                            $uploadBaseDir = dirname(dirname(__DIR__)) . '/public/uploads/';
                            $typeFolderMap = [
                                'nic'             => 'nics',
                                'driving_license' => 'licenses',
                                'police_report'   => 'police_reports'
                            ];
                            
                            $targetSubDir = $typeFolderMap[$documentType] ?? 'misc';
                            $targetDir = $uploadBaseDir . $targetSubDir . '/';
                            
                            if (!is_dir($targetDir)) {
                                mkdir($targetDir, 0755, true);
                            }
                            
                            $newFileName = 'driver_' . $driverId . '_' . time() . '.' . $fileExtension;
                            $destPath = $targetDir . $newFileName;
                            $dbFilePath = 'uploads/' . $targetSubDir . '/' . $newFileName;
                            
                            if (move_uploaded_file($fileTmpPath, $destPath)) {
                                $updateData['file_path'] = $dbFilePath;
                                $fileUploaded = true;
                                
                                // Delete old physical file if exists
                                if (!empty($oldDoc['file_path'])) {
                                    $oldFullPath = dirname(dirname(__DIR__)) . '/public/' . $oldDoc['file_path'];
                                    if (file_exists($oldFullPath)) {
                                        unlink($oldFullPath);
                                    }
                                }
                            } else {
                                $error = "There was an error moving the uploaded document file.";
                            }
                        } else {
                            $error = "Unauthorized document operation.";
                        }
                    }
                }
            }

            if (empty($error)) {
                $result = $driverController->editDocument($documentId, $updateData);
                if ($result['success']) {
                    $success = $result['message'];
                } else {
                    if ($fileUploaded && !empty($destPath) && file_exists($destPath)) {
                        unlink($destPath);
                    }
                    $error = $result['error'];
                }
            }
        }
    }
}

// 4. Resolve edit document context if GET parameter is specified
$editDoc = null;
if (isset($_GET['edit_id']) && $driverId > 0) {
    $editId = (int)$_GET['edit_id'];
    $docModel = new DriverDocument();
    $fetchedDoc = $docModel->getById($editId);
    if ($fetchedDoc && $fetchedDoc['driver_id'] === $driverId && $fetchedDoc['verification_status'] !== 'approved') {
        $editDoc = $fetchedDoc;
    }
}

// Fetch current list of documents
$docResult = $driverController->viewDocuments();
$documents = $docResult['success'] ? $docResult['documents'] : [];

$pageTitle = "Manage Documents - Lanka Renters";
$activePage = "documents";

include 'includes/header.php';
include 'includes/sidebar.php';
include 'includes/navbar.php';
?>
<main class="main-content">
    <div class="welcome-container">
        <div>
            <h2 class="welcome-title">Manage Documents</h2>
            <p class="welcome-subtitle">Upload and maintain your identity and licensing verifications.</p>
        </div>
    </div>

    <!-- Success/Error Alerts -->
    <?php if (!empty($success)): ?>
        <div class="alert alert-success">
            <?php echo htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <div style="display: grid; grid-template-columns: 1.2fr 2fr; gap: 30px; align-items: flex-start;">
        <!-- CRUD Input Card (Create or Update state) -->
        <div class="card" style="margin: 0;">
            <h2 class="card-title"><?php echo $editDoc ? 'Edit / Replace Document' : 'Upload New Document'; ?></h2>
            <form action="documents.php<?php echo $editDoc ? '?edit_id=' . $editDoc['id'] : ''; ?>" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(AuthHelper::getCsrfToken()); ?>">
                <input type="hidden" name="action" value="<?php echo $editDoc ? 'edit_document' : 'upload_document'; ?>">
                <?php if ($editDoc): ?>
                    <input type="hidden" name="document_id" value="<?php echo htmlspecialchars($editDoc['id']); ?>">
                <?php endif; ?>
                
                <div class="form-group">
                    <label for="document_type" class="form-label">Document Type</label>
                    <?php if ($editDoc): ?>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars(strtoupper(str_replace('_', ' ', $editDoc['document_type']))); ?>" readonly disabled>
                        <input type="hidden" name="document_type" value="<?php echo htmlspecialchars($editDoc['document_type']); ?>">
                    <?php else: ?>
                        <select name="document_type" id="document_type" class="form-control" required>
                            <option value="nic">National Identity Card (NIC)</option>
                            <option value="driving_license">Driving License</option>
                            <option value="police_report">Police Report</option>
                        </select>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="document_number" class="form-label">Document Number</label>
                    <input type="text" name="document_number" id="document_number" class="form-control" placeholder="Enter document number" value="<?php echo $editDoc ? htmlspecialchars($editDoc['document_number']) : ''; ?>" required>
                </div>

                <div class="form-group">
                    <label for="expiry_date" class="form-label">Expiry Date</label>
                    <input type="date" name="expiry_date" id="expiry_date" class="form-control" value="<?php echo $editDoc ? htmlspecialchars($editDoc['expiry_date']) : ''; ?>" required>
                </div>

                <div class="form-group">
                    <label for="document_file" class="form-label">Document File (PDF, JPG, PNG) <?php echo $editDoc ? '(Optional, select to replace file)' : ''; ?></label>
                    <input type="file" name="document_file" id="document_file" class="form-control" <?php echo $editDoc ? '' : 'required'; ?>>
                </div>

                <div style="display: flex; gap: 10px;">
                    <button type="submit" class="btn-blue" style="flex: 1.5;"><?php echo $editDoc ? 'Save Changes' : 'Upload Document'; ?></button>
                    <?php if ($editDoc): ?>
                        <a href="documents.php" class="btn-secondary" style="flex: 1; text-align: center; text-decoration: none; padding: 10px 0;">Cancel</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Current Documents Registry (Read & Delete state) -->
        <div class="card" style="margin: 0;">
            <h2 class="card-title">Your Uploaded Documents</h2>
            <?php if (empty($documents)): ?>
                <p style="font-style: italic; color: var(--text-muted);">No documents uploaded yet.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Document Type</th>
                            <th>Number</th>
                            <th>Expiry</th>
                            <th>Status</th>
                            <th>Notes</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($documents as $doc): ?>
                            <tr>
                                <td style="text-transform: capitalize; font-weight: 600;">
                                    <?php echo htmlspecialchars(str_replace('_', ' ', $doc['document_type'])); ?>
                                </td>
                                <td><?php echo htmlspecialchars($doc['document_number']); ?></td>
                                <td><?php echo htmlspecialchars($doc['expiry_date']); ?></td>
                                <td>
                                    <span class="status-pill status-<?php echo htmlspecialchars($doc['verification_status']); ?>">
                                        <?php echo htmlspecialchars($doc['verification_status']); ?>
                                    </span>
                                </td>
                                <td style="font-size: 11px;">
                                    <?php 
                                        if ($doc['verification_status'] === 'rejected' && !empty($doc['rejected_reason'])) {
                                            echo '<span style="color: var(--danger); font-weight: bold;">Rejected: ' . htmlspecialchars($doc['rejected_reason']) . '</span>';
                                        } else {
                                            echo '-';
                                        }
                                    ?>
                                </td>
                                <td>
                                    <div style="display: flex; gap: 8px;">
                                        <!-- Edit Action (Available for pending or rejected statuses) -->
                                        <?php if ($doc['verification_status'] !== 'approved'): ?>
                                            <a href="documents.php?edit_id=<?php echo $doc['id']; ?>" class="btn-secondary" style="padding: 4px 8px; font-size: 11px; text-decoration: none;">Edit</a>
                                        <?php else: ?>
                                            <span style="color: var(--text-muted); font-size: 11px;">Locked</span>
                                        <?php endif; ?>

                                        <!-- Delete Action (Only for pending verification status) -->
                                        <?php if ($doc['verification_status'] === 'pending'): ?>
                                            <form action="" method="POST" style="margin:0;" onsubmit="return confirm('Are you sure you want to delete this document?');">
                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(AuthHelper::getCsrfToken()); ?>">
                                                <input type="hidden" name="action" value="delete_document">
                                                <input type="hidden" name="document_id" value="<?php echo $doc['id']; ?>">
                                                <button type="submit" style="padding: 4px 8px; font-size: 11px; background-color: var(--danger); color: white; border: none; border-radius: 4px; cursor: pointer;">Delete</button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</main>
<?php
include 'includes/footer.php';
?>
