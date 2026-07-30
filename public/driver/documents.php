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

// Handle document upload submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload_document') {
    $documentType = $_POST['document_type'] ?? '';
    $documentNumber = $_POST['document_number'] ?? '';
    $expiryDate = $_POST['expiry_date'] ?? '';
    
    // File upload processing
    if (isset($_FILES['document_file']) && $_FILES['document_file']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['document_file']['tmp_name'];
        $fileName = $_FILES['document_file']['name'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        
        // Validate file extensions
        $allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png'];
        if (!in_array($fileExtension, $allowedExtensions)) {
            $error = "Only PDF, JPG, JPEG, and PNG files are allowed.";
        } else {
            // Get secure driver context
            $dashboardResult = $driverController->dashboard();
            if ($dashboardResult['success']) {
                $driverId = $dashboardResult['profile']['id'];
                
                // Construct file storage destination directory structures
                $uploadBaseDir = dirname(dirname(__DIR__)) . '/public/uploads/';
                
                // Map document types to respective folders
                $typeFolderMap = [
                    'nic'             => 'nics',
                    'driving_license' => 'licenses',
                    'police_report'   => 'police_reports'
                ];
                
                $targetSubDir = $typeFolderMap[$documentType] ?? 'misc';
                $targetDir = $uploadBaseDir . $targetSubDir . '/';
                
                // Ensure target directory exists
                if (!is_dir($targetDir)) {
                    mkdir($targetDir, 0755, true);
                }
                
                // Construct standardized filename
                $newFileName = 'driver_' . $driverId . '_' . time() . '.' . $fileExtension;
                $destPath = $targetDir . $newFileName;
                
                // File path to store in database (complete relative path from public root)
                $dbFilePath = 'uploads/' . $targetSubDir . '/' . $newFileName;
                
                try {
                    if (move_uploaded_file($fileTmpPath, $destPath)) {
                        // Save metadata record using controller
                        $uploadResult = $driverController->uploadDocument([
                            'document_type'   => $documentType,
                            'document_number' => $documentNumber,
                            'expiry_date'     => $expiryDate,
                            'file_path'       => $dbFilePath
                        ]);
                        
                        if ($uploadResult['success']) {
                            $success = $uploadResult['message'];
                        } else {
                            // Delete physical file if DB insertion failed
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

// Fetch driver info and document history
$dashboardResult = $driverController->dashboard();
$driverName = $dashboardResult['success'] ? $dashboardResult['profile']['name'] : 'Driver';

$docResult = $driverController->viewDocuments();
$documents = $docResult['success'] ? $docResult['documents'] : [];

// Page config
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
        <!-- Upload Form -->
        <div class="card" style="margin: 0;">
            <h2 class="card-title">Upload New Document</h2>
            <form action="" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="upload_document">
                
                <div class="form-group">
                    <label for="document_type" class="form-label">Document Type</label>
                    <select name="document_type" id="document_type" class="form-control" required>
                        <option value="nic">National Identity Card (NIC)</option>
                        <option value="driving_license">Driving License</option>
                        <option value="police_report">Police Report</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="document_number" class="form-label">Document Number</label>
                    <input type="text" name="document_number" id="document_number" class="form-control" placeholder="Enter document number" required>
                </div>

                <div class="form-group">
                    <label for="expiry_date" class="form-label">Expiry Date</label>
                    <input type="date" name="expiry_date" id="expiry_date" class="form-control" required>
                </div>

                <div class="form-group">
                    <label for="document_file" class="form-label">Document File (PDF, JPG, PNG)</label>
                    <input type="file" name="document_file" id="document_file" class="form-control" required>
                </div>

                <button type="submit" class="btn-blue" style="width: 100%; margin-top: 10px;">Upload Document</button>
            </form>
        </div>

        <!-- Current Documents Registry -->
        <div class="card" style="margin: 0;">
            <h2 class="card-title">Your Uploaded Documents</h2>
            <?php if (empty($documents)): ?>
                <p style="font-style: italic; color: var(--text-muted);">No documents uploaded yet.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Document Type</th>
                            <th>Document Number</th>
                            <th>Expiry Date</th>
                            <th>Status</th>
                            <th>Uploaded Date</th>
                            <th>Notes</th>
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
                                <td><?php echo date('Y-m-d', strtotime($doc['uploaded_at'])); ?></td>
                                <td>
                                    <?php 
                                        if ($doc['verification_status'] === 'rejected' && !empty($doc['rejected_reason'])) {
                                            echo '<span style="color: var(--danger); font-weight: bold;">Rejected: ' . htmlspecialchars($doc['rejected_reason']) . '</span>';
                                        } else {
                                            echo '-';
                                        }
                                    ?>
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
