<?php
require_once dirname(dirname(__DIR__)) . '/app/controllers/DriverController.php';
require_once dirname(dirname(__DIR__)) . '/app/helpers/AuthHelper.php';
require_once dirname(dirname(__DIR__)) . '/app/models/ProfileChangeRequest.php';

AuthHelper::startSession();

// Centralized role check
AuthHelper::requireRole('driver');

$user = AuthHelper::getCurrentUser();

$driverController = new DriverController();

$error = '';
$success = '';

// Handle profile update (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    if (!AuthHelper::validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = "CSRF security verification failed.";
    } else {
        $result = $driverController->updateProfile($_POST);
        if ($result['success']) {
            $success = $result['message'];
        } else {
            $error = $result['error'];
        }
    }
}

// Handle profile deactivation (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'deactivate_profile') {
    if (!AuthHelper::validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = "CSRF security verification failed.";
    } else {
        $result = $driverController->deactivateProfile();
        if ($result['success']) {
            AuthHelper::logout();
            header("Location: login.php?message=" . urlencode($result['message']));
            exit();
        } else {
            $error = $result['error'];
        }
    }
}

// Fetch current profile & stats (Read)
$dashboardResult = $driverController->dashboard();
if (!$dashboardResult['success']) {
    die("Error loading driver profile details: " . htmlspecialchars($dashboardResult['error']));
}

$profile = $dashboardResult['profile'];
$stats = $dashboardResult['dashboard_stats'];

// Fetch pending profile change requests
$pcrModel = new ProfileChangeRequest();
$allPCRs = $pcrModel->getByUserId($user['id']);
$pendingPCRs = array_filter($allPCRs, function($r) { return $r['status'] === 'pending'; });

// Page configs
$pageTitle = "My Profile - Lanka Renters";
$activePage = "profile";

include 'includes/header.php';
include 'includes/sidebar.php';
include 'includes/navbar.php';
?>
<main class="main-content">
    <div class="welcome-container">
        <div>
            <h2 class="welcome-title">My Profile</h2>
            <p class="welcome-subtitle">Manage your personal settings, contact details, and account status.</p>
        </div>
    </div>

    <!-- Alerts -->
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
        <!-- Left Side: Profile Details and Danger Zone -->
        <div style="display: flex; flex-direction: column; gap: 30px;">
            <!-- Profile Details Card (Read) -->
            <div class="card" style="margin: 0;">
                <h2 class="card-title">Driver Profile Information</h2>
                <div style="display: flex; flex-direction: column; gap: 15px; margin-top: 15px;">
                    <div>
                        <span style="font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Full Name</span>
                        <div style="font-size: 15px; font-weight: 600; color: var(--text-main); margin-top: 4px;"><?php echo htmlspecialchars($profile['name']); ?></div>
                    </div>
                    <div>
                        <span style="font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Email Address</span>
                        <div style="font-size: 15px; font-weight: 600; color: var(--text-main); margin-top: 4px;"><?php echo htmlspecialchars($profile['email']); ?></div>
                    </div>
                    <div>
                        <span style="font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Average Rating</span>
                        <div style="font-size: 15px; font-weight: 600; color: var(--text-main); margin-top: 4px; display: flex; align-items: center; gap: 4px;">
                            ⭐ <?php echo number_format($stats['rating'], 2); ?> / 5.00
                        </div>
                    </div>
                    <div>
                        <span style="font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Duty Status</span>
                        <div style="margin-top: 6px;">
                            <span class="status-pill status-<?php echo ($stats['availability_status'] === 'available' ? 'available' : ($stats['availability_status'] === 'busy' ? 'busy' : 'off_duty')); ?>">
                                <?php 
                                    $availMap = ['available' => 'Available', 'busy' => 'Busy', 'off_duty' => 'Off Duty'];
                                    echo htmlspecialchars($availMap[$stats['availability_status']] ?? $stats['availability_status']); 
                                ?>
                            </span>
                        </div>
                    </div>
                    <div>
                        <span style="font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Verification status</span>
                        <div style="margin-top: 6px;">
                            <span class="status-pill status-<?php echo htmlspecialchars($stats['verification_status']); ?>">
                                <?php echo htmlspecialchars(ucfirst($stats['verification_status'])); ?>
                            </span>
                        </div>
                    </div>
                    <div>
                        <span style="font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Member Since</span>
                        <div style="font-size: 15px; font-weight: 600; color: var(--text-main); margin-top: 4px;"><?php echo date('F d, Y', strtotime($profile['created_at'])); ?></div>
                    </div>
                </div>
            </div>

            <!-- Soft Delete / Deactivation Card -->
            <div class="card" style="margin: 0; border: 1px solid var(--danger);">
                <h2 class="card-title" style="color: var(--danger);">Deactivate Profile</h2>
                <p style="font-size: 13px; color: var(--text-muted); line-height: 1.5; margin: 10px 0 20px 0;">
                    Temporarily deactivate your driver profile. You will be logged out instantly and your status will be set to inactive. You can contact administrators to reactivate your profile.
                </p>
                <form action="" method="POST" onsubmit="return confirm('WARNING: Are you sure you want to deactivate your driver profile? You will be logged out immediately.');">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(AuthHelper::getCsrfToken()); ?>">
                    <input type="hidden" name="action" value="deactivate_profile">
                    <button type="submit" style="width: 100%; padding: 10px 0; background-color: var(--danger); color: white; border: none; border-radius: 6px; font-weight: 700; cursor: pointer; transition: background-color 0.2s;">Deactivate Driver Profile</button>
                </form>
            </div>
        </div>

        <!-- Right Side: Edit Form (Update) & Pending Requests List -->
        <div style="display: flex; flex-direction: column; gap: 30px; width: 100%;">
            <div class="card" style="margin: 0;">
                <h2 class="card-title">Edit Profile Details</h2>
                <form action="" method="POST" style="margin-top: 20px;">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(AuthHelper::getCsrfToken()); ?>">
                    <input type="hidden" name="action" value="update_profile">

                    <div class="form-group">
                        <label for="phone" class="form-label">Phone Number</label>
                        <input type="text" name="phone" id="phone" class="form-control" placeholder="Enter phone number" value="<?php echo htmlspecialchars($profile['phone']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="address" class="form-label">Permanent Address</label>
                        <textarea name="address" id="address" class="form-control" rows="4" placeholder="Enter permanent address details..."><?php echo htmlspecialchars($profile['address'] ?? ''); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="emergency_contact" class="form-label">Emergency Contact Details</label>
                        <input type="text" name="emergency_contact" id="emergency_contact" class="form-control" placeholder="e.g. Jane Doe (Wife) - 0771234567" value="<?php echo htmlspecialchars($profile['emergency_contact'] ?? ''); ?>">
                    </div>

                    <button type="submit" class="btn-blue" style="width: 100%; margin-top: 15px;">Save Profile Changes</button>
                </form>
            </div>

            <!-- Pending Approvals Card -->
            <?php if (!empty($pendingPCRs)): ?>
                <div class="card" style="margin: 0; border-left: 4px solid var(--warning);">
                    <h2 class="card-title">Pending Changes Under Review</h2>
                    <div style="display: flex; flex-direction: column; gap: 15px; margin-top: 15px;">
                        <?php foreach ($pendingPCRs as $req): ?>
                            <div style="padding: 15px; background-color: #FEF3C7; border-radius: 8px; font-size: 14px; border: 1px solid #FCD34D;">
                                <div style="font-weight: 700; color: #92400E; text-transform: uppercase; font-size: 11px; letter-spacing: 0.5px;">
                                    Requested Field: <?php echo htmlspecialchars(str_replace('_', ' ', $req['field_name'])); ?>
                                </div>
                                <div style="margin-top: 8px; color: var(--text-main);">
                                    <strong>Current Value:</strong> <?php echo htmlspecialchars($req['old_value'] !== '' ? $req['old_value'] : '(Empty)'); ?>
                                </div>
                                <div style="margin-top: 4px; color: var(--text-main);">
                                    <strong>Requested Value:</strong> <?php echo htmlspecialchars($req['requested_value'] !== '' ? $req['requested_value'] : '(Empty)'); ?>
                                </div>
                                <div style="margin-top: 10px; display: flex; align-items: center; justify-content: space-between;">
                                    <span class="status-pill status-pending" style="font-size: 11px;">Pending Admin Approval</span>
                                    <span style="font-size: 11px; color: var(--text-muted);"><?php echo date('M d, Y', strtotime($req['created_at'])); ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>
<?php
include 'includes/footer.php';
?>
