<?php
require_once dirname(dirname(__DIR__)) . '/app/controllers/DriverController.php';
require_once dirname(dirname(__DIR__)) . '/app/helpers/AuthHelper.php';
require_once dirname(dirname(__DIR__)) . '/app/models/ProfileChangeRequest.php';
require_once dirname(dirname(__DIR__)) . '/app/models/DriverDocument.php';

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
    <div class="welcome-container" style="margin-bottom: 25px;">
        <div>
            <h2 class="welcome-title">My Profile</h2>
            <p class="welcome-subtitle">Review, update, and manage your driver identity, verifications, and contact details.</p>
        </div>
    </div>

    <!-- Alerts -->
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
        <!-- Left Column: Status, Read-Only Info, Danger Zone -->
        <div style="display: flex; flex-direction: column; gap: 25px;">
            
            <!-- Account Status Card -->
            <div class="card" style="margin: 0; padding: 20px;">
                <h3 style="font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.8px; margin-bottom: 12px;">Account Status</h3>
                <?php
                $userStatus = strtoupper($profile['user_status'] ?? 'ACTIVE');
                $statusConfigs = [
                    'ACTIVE' => [
                        'color' => '#065F46', 'bg' => '#ECFDF5', 'border' => '#A7F3D0', 'dot' => '🟢',
                        'title' => 'Active', 'desc' => 'Your driver account is active and available for operations.'
                    ],
                    'INACTIVE' => [
                        'color' => '#334155', 'bg' => '#F1F5F9', 'border' => '#CBD5E1', 'dot' => '⚪',
                        'title' => 'Inactive', 'desc' => 'Your account is currently inactive. You will not receive trip bookings.'
                    ],
                    'PENDING' => [
                        'color' => '#92400E', 'bg' => '#FEF3C7', 'border' => '#FCD34D', 'dot' => '🟡',
                        'title' => 'Pending Approval', 'desc' => 'Your driver profile is awaiting administrative verification.'
                    ],
                    'SUSPENDED' => [
                        'color' => '#991B1B', 'bg' => '#FEF2F2', 'border' => '#FCA5A5', 'dot' => '🔴',
                        'title' => 'Suspended', 'desc' => 'Your account is suspended. Please contact support.'
                    ]
                ];
                $statusConf = $statusConfigs[$userStatus] ?? $statusConfigs['ACTIVE'];
                ?>
                <div style="background-color: <?php echo $statusConf['bg']; ?>; border: 1px solid <?php echo $statusConf['border']; ?>; border-radius: 8px; padding: 15px; display: flex; align-items: flex-start; gap: 12px;">
                    <div style="font-size: 18px; line-height: 1;"><?php echo $statusConf['dot']; ?></div>
                    <div>
                        <div style="font-weight: 700; color: <?php echo $statusConf['color']; ?>; font-size: 13.5px; text-transform: uppercase;"><?php echo htmlspecialchars($statusConf['title']); ?></div>
                        <div style="font-size: 12px; color: <?php echo $statusConf['color']; ?>; margin-top: 4px; line-height: 1.4; opacity: 0.9;"><?php echo htmlspecialchars($statusConf['desc']); ?></div>
                    </div>
                </div>
            </div>

            <!-- Verification Status Card -->
            <div class="card" style="margin: 0; padding: 20px;">
                <?php
                $verificationStatus = strtoupper($stats['verification_status'] ?? 'PENDING');
                $verConfigs = [
                    'APPROVED' => [
                        'color' => '#047857', 'bg' => '#D1FAE5', 'badge' => 'Verified Driver',
                        'desc' => 'All required documents (NIC, License, Police Report) are approved. You are ready to drive!'
                    ],
                    'PENDING' => [
                        'color' => '#D97706', 'bg' => '#FEF3C7', 'badge' => 'Pending Verification',
                        'desc' => 'One or more required documents are missing or pending administrative approval.'
                    ],
                    'REJECTED' => [
                        'color' => '#DC2626', 'bg' => '#FEE2E2', 'badge' => 'Verification Rejected',
                        'desc' => 'One or more of your documents were rejected. Please replace them immediately.'
                    ]
                ];
                $verConf = $verConfigs[$verificationStatus] ?? $verConfigs['PENDING'];
                ?>
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;">
                    <h3 style="font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.8px;">Verification Status</h3>
                    <span class="status-pill status-<?php echo strtolower($verificationStatus); ?>" style="font-size: 10px; font-weight: 700; padding: 2px 6px; border-radius: 4px; text-transform: uppercase;"><?php echo $verConf['badge']; ?></span>
                </div>
                <div style="font-size: 12.5px; color: var(--text-main); line-height: 1.4;">
                    <?php echo $verConf['desc']; ?>
                </div>
                <!-- Checklist -->
                <div style="margin-top: 15px; display: flex; flex-direction: column; gap: 8px;">
                    <?php
                    $docModel = new DriverDocument();
                    $nic = $docModel->getByType($profile['id'], 'nic');
                    $license = $docModel->getByType($profile['id'], 'driving_license');
                    $police = $docModel->getByType($profile['id'], 'police_report');
                    
                    $checklist = [
                        ['name' => 'National Identity Card (NIC)', 'doc' => $nic],
                        ['name' => 'Driving License', 'doc' => $license],
                        ['name' => 'Police Report', 'doc' => $police]
                    ];
                    
                    foreach ($checklist as $item):
                        $d = $item['doc'];
                        $st = $d ? strtoupper($d['status']) : 'MISSING';
                        $stColor = $st === 'APPROVED' ? 'var(--success)' : ($st === 'PENDING' ? 'var(--warning)' : 'var(--danger)');
                        $stIcon = $st === 'APPROVED' ? '✓' : ($st === 'PENDING' ? '⏳' : '✗');
                    ?>
                        <div style="display: flex; align-items: center; justify-content: space-between; font-size: 12px; padding: 6px 0; border-bottom: 1px dashed var(--border);">
                            <span style="color: var(--text-main); font-weight: 500;"><?php echo htmlspecialchars($item['name']); ?></span>
                            <span style="color: <?php echo $stColor; ?>; font-weight: 700; display: flex; align-items: center; gap: 4px;">
                                <?php echo $stIcon; ?> <?php echo $st; ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Driver Information Card -->
            <div class="card" style="margin: 0; padding: 20px;">
                <h3 style="font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.8px; margin-bottom: 15px;">Driver Information</h3>
                <div style="display: flex; flex-direction: column; gap: 12px;">
                    <div>
                        <span style="font-size: 10px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; display: block;">Full Name</span>
                        <span style="font-size: 14px; font-weight: 600; color: var(--text-main);"><?php echo htmlspecialchars($profile['name']); ?></span>
                    </div>
                    <div>
                        <span style="font-size: 10px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; display: block;">Average Rating</span>
                        <span style="font-size: 14px; font-weight: 600; color: var(--text-main); display: flex; align-items: center; gap: 4px; margin-top: 2px;">
                            ⭐ <?php echo number_format($stats['rating'], 2); ?> / 5.00
                        </span>
                    </div>
                    <div>
                        <span style="font-size: 10px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; display: block;">Duty Status</span>
                        <div style="margin-top: 4px;">
                            <span class="status-pill status-<?php echo ($stats['availability_status'] === 'available' ? 'available' : ($stats['availability_status'] === 'busy' ? 'busy' : 'off_duty')); ?>" style="font-size: 10.5px;">
                                <?php 
                                    $availMap = ['available' => 'Available', 'busy' => 'Busy', 'off_duty' => 'Off Duty'];
                                    echo htmlspecialchars($availMap[$stats['availability_status']] ?? $stats['availability_status']); 
                                ?>
                            </span>
                        </div>
                    </div>
                    <div>
                        <span style="font-size: 10px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; display: block;">Member Since</span>
                        <span style="font-size: 14px; font-weight: 600; color: var(--text-main);"><?php echo date('F d, Y', strtotime($profile['created_at'])); ?></span>
                    </div>
                </div>
            </div>

            <!-- Contact Information Card -->
            <div class="card" style="margin: 0; padding: 20px;">
                <h3 style="font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.8px; margin-bottom: 15px;">Contact Information</h3>
                <div style="display: flex; flex-direction: column; gap: 12px;">
                    <div>
                        <span style="font-size: 10px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; display: block;">Email Address</span>
                        <span style="font-size: 14px; font-weight: 600; color: var(--text-main);"><?php echo htmlspecialchars($profile['email']); ?></span>
                    </div>
                    <div>
                        <span style="font-size: 10px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; display: block;">Phone Number</span>
                        <span style="font-size: 14px; font-weight: 600; color: var(--text-main);"><?php echo htmlspecialchars($profile['phone']); ?></span>
                    </div>
                    <div>
                        <span style="font-size: 10px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; display: block;">Permanent Address</span>
                        <span style="font-size: 13.5px; font-weight: 600; color: var(--text-main); line-height: 1.4; display: block; margin-top: 2px;"><?php echo htmlspecialchars($profile['address'] !== '' ? $profile['address'] : '(Not Set)'); ?></span>
                    </div>
                </div>
            </div>

            <!-- Emergency Contact Card -->
            <div class="card" style="margin: 0; padding: 20px;">
                <h3 style="font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.8px; margin-bottom: 15px;">Emergency Contact</h3>
                <div>
                    <span style="font-size: 10px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; display: block;">Contact Details</span>
                    <span style="font-size: 13.5px; font-weight: 600; color: var(--text-main); display: block; margin-top: 2px;"><?php echo htmlspecialchars($profile['emergency_contact'] !== '' ? $profile['emergency_contact'] : '(Not Set)'); ?></span>
                </div>
            </div>

            <!-- Danger Zone Card -->
            <div class="card" style="margin: 0; padding: 20px; border: 1px solid var(--danger);">
                <h3 style="font-size: 11px; font-weight: 700; color: var(--danger); text-transform: uppercase; letter-spacing: 0.8px; margin-bottom: 10px;">Danger Zone</h3>
                <p style="font-size: 12px; color: var(--text-muted); line-height: 1.4; margin-bottom: 15px;">
                    Temporarily deactivate your profile. Your duty status will be set to inactive and you will be logged out instantly.
                </p>
                <form action="" method="POST" onsubmit="return confirm('WARNING: Deactivate your driver profile?');">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(AuthHelper::getCsrfToken()); ?>">
                    <input type="hidden" name="action" value="deactivate_profile">
                    <button type="submit" style="width: 100%; padding: 8px 0; background-color: var(--danger); color: white; border: none; border-radius: 6px; font-weight: 700; cursor: pointer; transition: background-color 0.2s; font-size: 13px;">Deactivate Profile</button>
                </form>
            </div>
        </div>

        <!-- Right Column: Edit Profile & Pending Changes -->
        <div style="display: flex; flex-direction: column; gap: 30px;">
            
            <!-- Edit Profile Form Card -->
            <div class="card" style="margin: 0; padding: 25px;">
                <h3 style="font-size: 16px; font-weight: 700; color: var(--text-main); margin-bottom: 5px;">Edit Profile Contact Details</h3>
                <p style="font-size: 12.5px; color: var(--text-muted); margin-bottom: 20px;">Changes to phone number, address, and emergency contact details require administrative verification before becoming active.</p>
                
                <form action="" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(AuthHelper::getCsrfToken()); ?>">
                    <input type="hidden" name="action" value="update_profile">

                    <div class="form-group">
                        <label for="phone" class="form-label" style="font-weight: 600;">Phone Number</label>
                        <input type="text" name="phone" id="phone" class="form-control" placeholder="Enter phone number" value="<?php echo htmlspecialchars($profile['phone']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="address" class="form-label" style="font-weight: 600;">Permanent Address</label>
                        <textarea name="address" id="address" class="form-control" rows="4" placeholder="Enter permanent address" style="resize: none;"><?php echo htmlspecialchars($profile['address'] ?? ''); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="emergency_contact" class="form-label" style="font-weight: 600;">Emergency Contact details</label>
                        <input type="text" name="emergency_contact" id="emergency_contact" class="form-control" placeholder="e.g. Jane Doe (Wife) - 0771234567" value="<?php echo htmlspecialchars($profile['emergency_contact'] ?? ''); ?>">
                    </div>

                    <button type="submit" class="btn-blue" style="width: 100%; margin-top: 15px; padding: 12px; font-weight: 700; border-radius: 6px;">Submit Changes for Review</button>
                </form>
            </div>

            <!-- Pending Profile Requests Card -->
            <?php if (!empty($pendingPCRs)): ?>
                <div class="card" style="margin: 0; padding: 25px; border-left: 4px solid var(--warning);">
                    <h3 style="font-size: 16px; font-weight: 700; color: var(--text-main); margin-bottom: 15px;">Pending Profile Modifications</h3>
                    <div style="display: flex; flex-direction: column; gap: 15px;">
                        <?php foreach ($pendingPCRs as $req): ?>
                            <div style="padding: 15px; background-color: #FEF3C7; border: 1px solid #FCD34D; border-radius: 8px; font-size: 13px;">
                                <div style="font-weight: 700; color: #92400E; text-transform: uppercase; font-size: 10.5px; letter-spacing: 0.5px;">
                                    Field: <?php echo htmlspecialchars(str_replace('_', ' ', $req['field_name'])); ?>
                                </div>
                                <div style="margin-top: 8px; color: var(--text-main);">
                                    <strong>Current:</strong> <?php echo htmlspecialchars($req['old_value'] !== '' ? $req['old_value'] : '(Empty)'); ?>
                                </div>
                                <div style="margin-top: 4px; color: var(--text-main);">
                                    <strong>Requested:</strong> <?php echo htmlspecialchars($req['requested_value'] !== '' ? $req['requested_value'] : '(Empty)'); ?>
                                </div>
                                <div style="margin-top: 10px; display: flex; align-items: center; justify-content: space-between;">
                                    <span class="status-pill status-pending" style="font-size: 10px; font-weight: 700;">Awaiting Admin Approval</span>
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
