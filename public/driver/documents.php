<?php
require_once dirname(dirname(__DIR__)) . '/app/controllers/DriverController.php';
require_once dirname(dirname(__DIR__)) . '/app/helpers/AuthHelper.php';
require_once dirname(dirname(__DIR__)) . '/app/models/DriverDocument.php';

AuthHelper::startSession();

// Centralized role check
AuthHelper::requireRole('driver');

$user = AuthHelper::getCurrentUser();
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

// 1. Handle document delete action (Security Constraint: Only allows pending status)
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

// 2. Handle document upload submissions (creates a new version)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload_document') {
    if (!AuthHelper::validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = "CSRF security verification failed.";
    } else {
        $documentType = $_POST['document_type'] ?? '';
        $documentNumber = trim($_POST['document_number'] ?? '');
        $expiryDate = $_POST['expiry_date'] ?? '';
        
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
                                $success = "Document replacement version uploaded successfully and is pending admin approval.";
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

// Fetch current list of documents (ordered by version history)
$docResult = $driverController->viewDocuments();
$documents = $docResult['success'] ? $docResult['documents'] : [];

// Extract active approved current documents
$activeDocs = array_filter($documents, function($d) {
    return $d['status'] === 'approved' && $d['is_current'] == 1;
});

// Page configs
$pageTitle = "Manage Documents - Lanka Renters";
$activePage = "documents";

include 'includes/header.php';
include 'includes/sidebar.php';
include 'includes/navbar.php';
?>
<main class="main-content">
    <div class="welcome-container" style="margin-bottom: 25px;">
        <div>
            <h2 class="welcome-title">Manage Documents</h2>
            <p class="welcome-subtitle">Maintain your licensing verification records, NIC identity cards, and police clearances.</p>
        </div>
    </div>

    <!-- Success/Error Alerts -->
    <?php if (!empty($success)): ?>
        <div class="alert alert-success" style="margin-bottom: 25px; border-radius: 8px;">
            <?php echo htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger" style="margin-bottom: 25px; border-radius: 8px;">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <div style="display: grid; grid-template-columns: 1fr 1.6fr; gap: 30px; align-items: flex-start;">
        <!-- Upload / Replace Form Card -->
        <div class="card" style="margin: 0; padding: 25px;">
            <h3 style="font-size: 16px; font-weight: 700; color: var(--text-main); margin-bottom: 5px;">Replace Document</h3>
            <p style="font-size: 12.5px; color: var(--text-muted); line-height: 1.45; margin-bottom: 20px;">
                Your new document replacement will be logged as pending and reviewed by an administrator before becoming active.
            </p>
            <form action="documents.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(AuthHelper::getCsrfToken()); ?>">
                <input type="hidden" name="action" value="upload_document">
                
                <div class="form-group">
                    <label for="document_type" class="form-label" style="font-weight: 600;">Document Type</label>
                    <select name="document_type" id="document_type" class="form-control" required style="background: white;">
                        <option value="nic">National Identity Card (NIC)</option>
                        <option value="driving_license">Driving License</option>
                        <option value="police_report">Police Report</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="document_number" class="form-label" style="font-weight: 600;">Document Number</label>
                    <input type="text" name="document_number" id="document_number" class="form-control" placeholder="Enter document number" required>
                </div>

                <div class="form-group">
                    <label for="expiry_date" class="form-label" style="font-weight: 600;">Expiry Date</label>
                    <input type="date" name="expiry_date" id="expiry_date" class="form-control" required>
                </div>

                <div class="form-group">
                    <label for="document_file" class="form-label" style="font-weight: 600;">Document File (PDF, JPG, PNG)</label>
                    <input type="file" name="document_file" id="document_file" class="form-control" required>
                </div>

                <button type="submit" class="btn-blue" style="width: 100%; margin-top: 15px; padding: 12px; font-weight: 700; border-radius: 6px;">Upload Version</button>
            </form>
        </div>

        <!-- Right Side: Active Documents & Version History -->
        <div style="display: flex; flex-direction: column; gap: 30px;">
            <!-- Active Approved Cards -->
            <div class="card" style="margin: 0; padding: 25px;">
                <h3 style="font-size: 16px; font-weight: 700; color: var(--text-main); margin-bottom: 15px;">Current Approved Documents</h3>
                <?php if (empty($activeDocs)): ?>
                    <p style="font-style: italic; color: var(--text-muted); font-size: 13px;">No approved documents currently active. Please complete all uploads to verify your driver profile.</p>
                <?php else: ?>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                        <?php foreach ($activeDocs as $doc): ?>
                            <div style="padding: 15px; border: 1px solid var(--border); border-radius: 8px; background: #F8FAFC; display: flex; flex-direction: column; justify-content: space-between; min-height: 120px;">
                                <div>
                                    <div style="font-weight: 700; font-size: 11px; text-transform: uppercase; color: var(--primary);">
                                        <?php echo htmlspecialchars(str_replace('_', ' ', $doc['document_type'])); ?>
                                    </div>
                                    <div style="font-size: 13.5px; font-weight: 600; margin-top: 8px; color: var(--text-main);">
                                        No: <?php echo htmlspecialchars($doc['document_number']); ?>
                                    </div>
                                    <div style="font-size: 11.5px; color: var(--text-muted); margin-top: 2px;">
                                        Expires: <?php echo htmlspecialchars($doc['expiry_date']); ?>
                                    </div>
                                </div>
                                <div style="margin-top: 12px; display: flex; justify-content: space-between; align-items: center;">
                                    <span class="status-pill status-approved" style="font-size: 9px; font-weight: 700; padding: 2px 6px; border-radius: 4px; text-transform: uppercase;">Approved</span>
                                    <a href="../<?php echo htmlspecialchars($doc['file_path']); ?>" target="_blank" style="font-size: 11px; text-decoration: none; color: var(--primary); font-weight: 700;">View File ↗</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Version History Registry -->
            <div class="card" style="margin: 0; padding: 25px;">
                <h3 style="font-size: 16px; font-weight: 700; color: var(--text-main); margin-bottom: 15px;">Submission History</h3>
                <?php if (empty($documents)): ?>
                    <p style="font-style: italic; color: var(--text-muted); font-size: 13px;">No document records found.</p>
                <?php else: ?>
                    <div style="overflow-x: auto;">
                        <table style="width: 100%; border-collapse: collapse; font-size: 13px; min-width: 500px;">
                            <thead>
                                <tr style="border-bottom: 2px solid var(--border); text-align: left; font-weight: 700; color: var(--text-muted);">
                                    <th style="padding: 10px;">Type</th>
                                    <th style="padding: 10px;">Version</th>
                                    <th style="padding: 10px;">No / Expiry</th>
                                    <th style="padding: 10px;">Status</th>
                                    <th style="padding: 10px;">Review / Notes</th>
                                    <th style="padding: 10px; text-align: right;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($documents as $doc): ?>
                                    <tr style="border-bottom: 1px solid var(--border);">
                                        <td style="padding: 12px 10px; text-transform: capitalize; font-weight: 600; color: var(--text-main);">
                                            <?php echo htmlspecialchars(str_replace('_', ' ', $doc['document_type'])); ?>
                                        </td>
                                        <td style="padding: 12px 10px; font-weight: 700;">
                                            v<?php echo htmlspecialchars($doc['version']); ?>
                                            <?php if ($doc['is_current'] == 1): ?>
                                                <span style="font-size: 9px; padding: 2px 4px; background: #DCFCE7; color: #15803D; border-radius: 4px; margin-left: 4px; font-weight: 700; text-transform: uppercase;">Current</span>
                                            <?php endif; ?>
                                        </td>
                                        <td style="padding: 12px 10px;">
                                            <div><?php echo htmlspecialchars($doc['document_number']); ?></div>
                                            <div style="font-size: 10.5px; color: var(--text-muted); margin-top: 1px;">Exp: <?php echo htmlspecialchars($doc['expiry_date']); ?></div>
                                        </td>
                                        <td style="padding: 12px 10px;">
                                            <span class="status-pill status-<?php echo htmlspecialchars($doc['status']); ?>" style="font-size: 9.5px; font-weight: 700; text-transform: uppercase; padding: 2px 5px; border-radius: 4px;">
                                                <?php echo htmlspecialchars($doc['status']); ?>
                                            </span>
                                        </td>
                                        <td style="padding: 12px 10px; font-size: 11px; max-width: 150px; line-height: 1.3;">
                                            <?php 
                                                if ($doc['status'] === 'rejected' && !empty($doc['rejection_reason'])) {
                                                    echo '<span style="color: var(--danger); font-weight: 600;">Reason: ' . htmlspecialchars($doc['rejection_reason']) . '</span>';
                                                } elseif ($doc['status'] === 'superseded') {
                                                    echo '<span style="color: var(--text-muted);">Superseded by newer version</span>';
                                                } elseif ($doc['status'] === 'approved') {
                                                    echo '<span style="color: var(--success); font-weight: 600;">Active approved version</span>';
                                                } else {
                                                    echo '<span style="color: var(--text-muted);">Awaiting review</span>';
                                                }
                                            ?>
                                        </td>
                                        <td style="padding: 12px 10px; text-align: right;">
                                            <div style="display: flex; gap: 6px; align-items: center; justify-content: flex-end;">
                                                <a href="../<?php echo htmlspecialchars($doc['file_path']); ?>" target="_blank" class="btn-secondary" style="padding: 4px 8px; font-size: 11px; text-decoration: none; border-radius: 4px;">View</a>
                                                
                                                <!-- Delete Action (Only permitted for pending state) -->
                                                <?php if ($doc['status'] === 'pending'): ?>
                                                    <form action="" method="POST" style="margin:0;" onsubmit="return confirm('Delete this pending document upload version?');">
                                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(AuthHelper::getCsrfToken()); ?>">
                                                        <input type="hidden" name="action" value="delete_document">
                                                        <input type="hidden" name="document_id" value="<?php echo $doc['id']; ?>">
                                                        <button type="submit" style="padding: 4px 8px; font-size: 11px; background-color: var(--danger); color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: 600;">Delete</button>
                                                    </form>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>
<?php
include 'includes/footer.php';
?>
