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

// Handle driver connection request actions (accept/reject pending)
$actionSuccess = '';
$actionError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!AuthHelper::validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $actionError = "CSRF security verification failed.";
    } else {
        $linkId = (int)($_POST['link_id'] ?? 0);
        if ($_POST['action'] === 'accept_request') {
            $res = $driverController->acceptOwnerRequest($linkId);
            if ($res['success']) $actionSuccess = $res['message']; else $actionError = $res['error'];
        } elseif ($_POST['action'] === 'reject_request') {
            $res = $driverController->rejectOwnerRequest($linkId);
            if ($res['success']) $actionSuccess = $res['message']; else $actionError = $res['error'];
        }
    }
}

// Retrieve connected owners (accepted links)
$connectedRes = $driverController->viewConnectedOwners();
$connectedOwners = $connectedRes['success'] ? $connectedRes['owners'] : [];

// Retrieve pending requests
$pendingRes = $driverController->viewOwnerRequests();
$pendingRequests = $pendingRes['success'] ? $pendingRes['requests'] : [];

$csrfToken = AuthHelper::getCsrfToken();
$activePage = 'owners';
$pageTitle = 'Connected Owners - Lanka Renters';

include 'includes/header.php';
include 'includes/sidebar.php';
include 'includes/navbar.php';
?>
<main class="main-content">
    
    <div class="welcome-container">
        <div>
            <h2 class="welcome-title">Connected Vehicle Owners 🤝</h2>
            <p class="welcome-subtitle">View and manage your linked vehicle owners and connection requests.</p>
        </div>
    </div>

    <?php if (!empty($actionSuccess)): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($actionSuccess); ?></div>
    <?php endif; ?>
    <?php if (!empty($actionError)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($actionError); ?></div>
    <?php endif; ?>

    <!-- Pending Connection Requests Section -->
    <?php if (!empty($pendingRequests)): ?>
        <div class="card" style="margin-bottom: 24px; border-left: 4px solid var(--warning);">
            <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
                <h3 class="card-title" style="color: var(--warning-dark, #b45309);">
                    ⚠️ Pending Connection Requests (<?php echo count($pendingRequests); ?>)
                </h3>
            </div>
            <div style="padding: 16px;">
                <div style="display: grid; gap: 12px;">
                    <?php foreach ($pendingRequests as $req): ?>
                        <div style="display: flex; justify-content: space-between; align-items: center; padding: 14px; background: #fffbeb; border: 1px solid #fef3c7; border-radius: 10px;">
                            <div>
                                <h4 style="margin: 0; font-size: 15px; font-weight: 700; color: #1e293b;"><?php echo htmlspecialchars($req['owner_name']); ?></h4>
                                <div style="font-size: 13px; color: #64748b; margin-top: 4px;">
                                    <span>📧 <?php echo htmlspecialchars($req['owner_email']); ?></span> &bull; 
                                    <span>📞 <?php echo htmlspecialchars($req['owner_phone'] ?? 'N/A'); ?></span> &bull; 
                                    <span>🚗 <?php echo (int)($req['vehicle_count'] ?? 0); ?> listed vehicles</span>
                                </div>
                            </div>
                            <div style="display: flex; gap: 8px;">
                                <form method="POST" action="owners.php" style="margin: 0;">
                                    <input type="hidden" name="action" value="accept_request">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                    <input type="hidden" name="link_id" value="<?php echo (int)$req['id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-primary">Accept</button>
                                </form>
                                <form method="POST" action="owners.php" style="margin: 0;">
                                    <input type="hidden" name="action" value="reject_request">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                    <input type="hidden" name="link_id" value="<?php echo (int)$req['id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-danger">Reject</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Active Connected Owners Section -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Active Owner Connections</h3>
        </div>

        <?php if (empty($connectedOwners)): ?>
            <div style="padding: 40px 20px; text-align: center;">
                <div style="font-size: 48px; margin-bottom: 12px;">🤝</div>
                <h4 style="margin: 0 0 6px; font-weight: 700; color: var(--text-dark);">No connected owners yet</h4>
                <p style="margin: 0; font-size: 14px; color: var(--text-muted);">Vehicle owners who send you connection requests will appear here once accepted.</p>
            </div>
        <?php else: ?>
            <div style="padding: 16px;">
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 16px;">
                    <?php foreach ($connectedOwners as $owner): ?>
                        <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.02);">
                            <div style="display: flex; align-items: center; gap: 14px; margin-bottom: 14px;">
                                <div style="width: 48px; height: 48px; border-radius: 50%; background: #eff6ff; color: #2563eb; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 18px;">
                                    <?php echo strtoupper(substr($owner['owner_name'], 0, 1)); ?>
                                </div>
                                <div>
                                    <h4 style="margin: 0; font-size: 16px; font-weight: 700; color: #0f172a;"><?php echo htmlspecialchars($owner['owner_name']); ?></h4>
                                    <span class="status-pill status-available" style="font-size: 11px; padding: 2px 8px; margin-top: 4px; display: inline-block;">Active Connection</span>
                                </div>
                            </div>

                            <div style="font-size: 13px; color: #475569; display: grid; gap: 8px; border-top: 1px solid #f1f5f9; padding-top: 12px;">
                                <div>
                                    <strong style="color: #1e293b;">Email:</strong> 
                                    <a href="mailto:<?php echo htmlspecialchars($owner['owner_email']); ?>" style="color: #2563eb; text-decoration: none;">
                                        <?php echo htmlspecialchars($owner['owner_email']); ?>
                                    </a>
                                </div>
                                <div>
                                    <strong style="color: #1e293b;">Phone:</strong> 
                                    <a href="tel:<?php echo htmlspecialchars($owner['owner_phone'] ?? ''); ?>" style="color: #2563eb; text-decoration: none;">
                                        <?php echo htmlspecialchars($owner['owner_phone'] ?? 'N/A'); ?>
                                    </a>
                                </div>
                                <div>
                                    <strong style="color: #1e293b;">Vehicles:</strong> 
                                    <span><?php echo (int)($owner['vehicle_count'] ?? 0); ?> Listed Vehicles</span>
                                </div>
                                <?php if (!empty($owner['accepted_at'])): ?>
                                    <div>
                                        <strong style="color: #1e293b;">Connected Since:</strong> 
                                        <span><?php echo date('M d, Y', strtotime($owner['accepted_at'])); ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

</main>
<?php include 'includes/footer.php'; ?>
