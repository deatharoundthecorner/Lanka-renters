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

// Fetch current driver dashboard data for header
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
    <div class="welcome-container">
        <div>
            <h2 class="welcome-title">Account & Security</h2>
            <p class="welcome-subtitle">Manage your account credentials, security preferences, and safety validations.</p>
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
        <!-- Left Side: Active Account Stats & Pending Requests -->
        <div style="display: flex; flex-direction: column; gap: 30px;">
            <!-- Active Account Info Card -->
            <div class="card" style="margin: 0;">
                <h2 class="card-title">Account Identity</h2>
                <div style="display: flex; flex-direction: column; gap: 15px; margin-top: 15px;">
                    <div>
                        <span style="font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Active Username</span>
                        <div style="font-size: 15px; font-weight: 600; color: var(--text-main); margin-top: 4px;"><?php echo htmlspecialchars($profile['name']); ?></div>
                    </div>
                    <div>
                        <span style="font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Active Email</span>
                        <div style="font-size: 15px; font-weight: 600; color: var(--text-main); margin-top: 4px;"><?php echo htmlspecialchars($profile['email']); ?></div>
                    </div>
                    <div>
                        <span style="font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Account Status</span>
                        <div style="margin-top: 6px;">
                            <span class="status-pill status-active">Active</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pending Credentials Approvals -->
            <?php if (!empty($pendingACRs)): ?>
                <div class="card" style="margin: 0; border-left: 4px solid var(--warning);">
                    <h2 class="card-title">Pending Credentials Changes</h2>
                    <div style="display: flex; flex-direction: column; gap: 15px; margin-top: 15px;">
                        <?php foreach ($pendingACRs as $req): ?>
                            <div style="padding: 15px; background-color: #FEF3C7; border-radius: 8px; font-size: 14px; border: 1px solid #FCD34D;">
                                <div style="font-weight: 700; color: #92400E; text-transform: uppercase; font-size: 11px; letter-spacing: 0.5px;">
                                    Change Type: <?php echo htmlspecialchars(ucfirst($req['change_type'])); ?>
                                </div>
                                <div style="margin-top: 8px; color: var(--text-main);">
                                    <strong>Current:</strong> <?php echo htmlspecialchars($req['old_value']); ?>
                                </div>
                                <div style="margin-top: 4px; color: var(--text-main);">
                                    <strong>Requested:</strong> <?php echo htmlspecialchars($req['requested_value']); ?>
                                </div>
                                <div style="margin-top: 10px; display: flex; align-items: center; justify-content: space-between;">
                                    <span class="status-pill status-pending" style="font-size: 11px;">Pending Admin Review</span>
                                    <span style="font-size: 11px; color: var(--text-muted);"><?php echo date('M d, Y', strtotime($req['created_at'])); ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Right Side: Change Account Forms (Username/Email & Password) -->
        <div style="display: flex; flex-direction: column; gap: 30px; width: 100%;">
            <!-- Request Username/Email Changes Card -->
            <div class="card" style="margin: 0;">
                <h2 class="card-title">Request Credentials Updates</h2>
                <form action="" method="POST" style="margin-top: 20px;">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(AuthHelper::getCsrfToken()); ?>">
                    <input type="hidden" name="action" value="update_account">

                    <div class="form-group">
                        <label for="change_type" class="form-label">Credentials Field</label>
                        <select name="change_type" id="change_type" class="form-control" required style="background: white;">
                            <option value="username">Username (Display Name)</option>
                            <option value="email">Email Address</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="requested_value" class="form-label">Requested Value</label>
                        <input type="text" name="requested_value" id="requested_value" class="form-control" placeholder="Enter new username or email address" required>
                    </div>

                    <button type="submit" class="btn-blue" style="width: 100%; margin-top: 15px;">Submit Credentials Request</button>
                </form>
            </div>

            <!-- Password Change Form Card -->
            <div class="card" style="margin: 0;">
                <h2 class="card-title">Change Password</h2>
                <form action="" method="POST" style="margin-top: 20px;">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(AuthHelper::getCsrfToken()); ?>">
                    <input type="hidden" name="action" value="change_password">

                    <div class="form-group">
                        <label for="current_password" class="form-label">Current Password</label>
                        <input type="password" name="current_password" id="current_password" class="form-control" placeholder="••••••••" required>
                    </div>

                    <div class="form-group">
                        <label for="new_password" class="form-label">New Password</label>
                        <input type="password" name="new_password" id="new_password" class="form-control" placeholder="••••••••" required>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password" class="form-label">Confirm New Password</label>
                        <input type="password" name="confirm_password" id="confirm_password" class="form-control" placeholder="••••••••" required>
                    </div>

                    <button type="submit" class="btn-blue" style="width: 100%; margin-top: 15px;">Update Password</button>
                </form>
            </div>
        </div>
    </div>
</main>
<?php
include 'includes/footer.php';
?>
