<?php
require_once dirname(dirname(__DIR__)) . '/app/controllers/DriverController.php';
require_once dirname(dirname(__DIR__)) . '/app/helpers/AuthHelper.php';
require_once dirname(dirname(__DIR__)) . '/app/models/AccountChangeRequest.php';

AuthHelper::startSession();

// Centralized role check
AuthHelper::requireRole('driver');

$user = AuthHelper::getCurrentUser();
$driverController = new DriverController();

$error = '';
$success = '';

// Handle credentials update request (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!AuthHelper::validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = "CSRF security verification failed.";
    } else {
        if ($_POST['action'] === 'update_account') {
            $result = $driverController->updateAccount($_POST);
            if ($result['success']) {
                $success = $result['message'];
            } else {
                $error = $result['error'];
            }
        } elseif ($_POST['action'] === 'change_password') {
            $result = $driverController->changePassword($_POST);
            if ($result['success']) {
                $success = $result['message'];
            } else {
                $error = $result['error'];
            }
        }
    }
}

// Fetch current driver details for display
$dashboardResult = $driverController->dashboard();
if (!$dashboardResult['success']) {
    die("Error loading driver account details: " . htmlspecialchars($dashboardResult['error']));
}
$profile = $dashboardResult['profile'];
$stats = $dashboardResult['dashboard_stats'];

// Fetch pending account change requests
$acrModel = new AccountChangeRequest();
$pendingACRs = array_filter($acrModel->getByUserId($user['id']), function($r) { return $r['status'] === 'pending'; });

// Page configs
$pageTitle = "Account & Security - Lanka Renters";
$activePage = "security";

include 'includes/header.php';
include 'includes/sidebar.php';
include 'includes/navbar.php';
?>
<main class="main-content">
    <div class="welcome-container" style="margin-bottom: 25px;">
        <div>
            <h2 class="welcome-title">Account & Security</h2>
            <p class="welcome-subtitle">Manage your account credentials, security parameters, and credential modification requests.</p>
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
        <!-- Left Side: Active Account Identity & Status -->
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

            <!-- Account Identity Card -->
            <div class="card" style="margin: 0; padding: 20px;">
                <h3 style="font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.8px; margin-bottom: 15px;">Account Identity</h3>
                <div style="display: flex; flex-direction: column; gap: 12px;">
                    <div>
                        <span style="font-size: 10px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; display: block;">Display Name</span>
                        <span style="font-size: 14px; font-weight: 600; color: var(--text-main);"><?php echo htmlspecialchars($profile['name']); ?></span>
                    </div>
                    <div>
                        <span style="font-size: 10px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; display: block;">Username</span>
                        <span style="font-size: 14px; font-weight: 600; color: var(--text-main);"><?php echo htmlspecialchars($profile['username'] !== '' && $profile['username'] !== null ? $profile['username'] : '(Not Set)'); ?></span>
                    </div>
                    <div>
                        <span style="font-size: 10px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; display: block;">Email Address</span>
                        <span style="font-size: 14px; font-weight: 600; color: var(--text-main);"><?php echo htmlspecialchars($profile['email']); ?></span>
                    </div>
                </div>
            </div>

            <!-- Security Info Card -->
            <div class="card" style="margin: 0; padding: 20px; background-color: #F8FAFC;">
                <h3 style="font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.8px; margin-bottom: 10px;">Security Information</h3>
                <p style="font-size: 12px; color: var(--text-muted); line-height: 1.45;">
                    Your account credentials and password protect your sensitive profile data. Administrators review credentials updates for compliance before committing them.
                </p>
            </div>
        </div>

        <!-- Right Side: Credentials & Password Update Forms -->
        <div style="display: flex; flex-direction: column; gap: 30px;">
            
            <!-- Request Account Changes Form Card -->
            <div class="card" style="margin: 0; padding: 25px;">
                <h3 style="font-size: 16px; font-weight: 700; color: var(--text-main); margin-bottom: 5px;">Request Account Changes</h3>
                <p style="font-size: 12.5px; color: var(--text-muted); margin-bottom: 20px;">Changes to your Display Name, Username, or Email are logged as pending and require Admin approval.</p>
                
                <form action="" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(AuthHelper::getCsrfToken()); ?>">
                    <input type="hidden" name="action" value="update_account">

                    <div class="form-group">
                        <label for="change_type" class="form-label" style="font-weight: 600;">Credentials Field</label>
                        <select name="change_type" id="change_type" class="form-control" required style="background: white;">
                            <option value="display_name">Display Name (Full Name)</option>
                            <option value="username">Username</option>
                            <option value="email">Email Address</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="requested_value" class="form-label" style="font-weight: 600;">Requested Value</label>
                        <input type="text" name="requested_value" id="requested_value" class="form-control" placeholder="Enter requested details value" required>
                    </div>

                    <button type="submit" class="btn-blue" style="width: 100%; margin-top: 15px; padding: 12px; font-weight: 700; border-radius: 6px;">Submit Credentials Request</button>
                </form>
            </div>

            <!-- Pending Requests Card -->
            <?php if (!empty($pendingACRs)): ?>
                <div class="card" style="margin: 0; padding: 25px; border-left: 4px solid var(--warning);">
                    <h3 style="font-size: 16px; font-weight: 700; color: var(--text-main); margin-bottom: 15px;">Pending Credentials Modifications</h3>
                    <div style="display: flex; flex-direction: column; gap: 15px;">
                        <?php foreach ($pendingACRs as $req): ?>
                            <div style="padding: 15px; background-color: #FEF3C7; border: 1px solid #FCD34D; border-radius: 8px; font-size: 13px;">
                                <div style="font-weight: 700; color: #92400E; text-transform: uppercase; font-size: 10.5px; letter-spacing: 0.5px;">
                                    Change: <?php 
                                        $labelMap = ['display_name' => 'Display Name', 'username' => 'Username', 'email' => 'Email'];
                                        echo htmlspecialchars($labelMap[$req['change_type']] ?? $req['change_type']); 
                                    ?>
                                </div>
                                <div style="margin-top: 8px; color: var(--text-main);">
                                    <strong>Current:</strong> <?php echo htmlspecialchars($req['old_value'] !== '' && $req['old_value'] !== null ? $req['old_value'] : '(Empty)'); ?>
                                </div>
                                <div style="margin-top: 4px; color: var(--text-main);">
                                    <strong>Requested:</strong> <?php echo htmlspecialchars($req['requested_value']); ?>
                                </div>
                                <div style="margin-top: 10px; display: flex; align-items: center; justify-content: space-between;">
                                    <span class="status-pill status-pending" style="font-size: 10px; font-weight: 700;">Pending Admin Review</span>
                                    <span style="font-size: 11px; color: var(--text-muted);"><?php echo date('M d, Y', strtotime($req['created_at'])); ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Change Password Card -->
            <div class="card" style="margin: 0; padding: 25px;">
                <h3 style="font-size: 16px; font-weight: 700; color: var(--text-main); margin-bottom: 5px;">Change Password</h3>
                <p style="font-size: 12.5px; color: var(--text-muted); margin-bottom: 20px;">Update your password regularly to keep your account secure. Normal password changes do not require administrative approval.</p>
                
                <form action="" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(AuthHelper::getCsrfToken()); ?>">
                    <input type="hidden" name="action" value="change_password">

                    <div class="form-group">
                        <label for="current_password" class="form-label" style="font-weight: 600;">Current Password</label>
                        <input type="password" name="current_password" id="current_password" class="form-control" placeholder="••••••••" required>
                    </div>

                    <div class="form-group">
                        <label for="new_password" class="form-label" style="font-weight: 600;">New Password</label>
                        <input type="password" name="new_password" id="new_password" class="form-control" placeholder="••••••••" required>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password" class="form-label" style="font-weight: 600;">Confirm New Password</label>
                        <input type="password" name="confirm_password" id="confirm_password" class="form-control" placeholder="••••••••" required>
                    </div>

                    <button type="submit" class="btn-blue" style="width: 100%; margin-top: 15px; padding: 12px; font-weight: 700; border-radius: 6px;">Update Password</button>
                </form>
            </div>
        </div>
    </div>
</main>
<?php
include 'includes/footer.php';
?>
