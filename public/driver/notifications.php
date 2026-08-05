<?php
require_once dirname(dirname(__DIR__)) . '/app/helpers/AuthHelper.php';
require_once dirname(dirname(__DIR__)) . '/app/models/Notification.php';
require_once dirname(dirname(__DIR__)) . '/app/controllers/DriverController.php';

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

$notificationModel = new Notification();
$driverController = new DriverController();

$error = '';
$success = '';

// Handle connection request actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'accept_request') {
        $linkId = (int)($_POST['link_id'] ?? 0);
        $res = $driverController->acceptOwnerRequest($linkId);
        if ($res['success']) {
            $success = $res['message'];
        } else {
            $error = $res['error'];
        }
    } elseif ($_POST['action'] === 'reject_request') {
        $linkId = (int)($_POST['link_id'] ?? 0);
        $res = $driverController->rejectOwnerRequest($linkId);
        if ($res['success']) {
            $success = $res['message'];
        } else {
            $error = $res['error'];
        }
    }
}

// Fetch pending connection requests
$requestsResult = $driverController->viewOwnerRequests();
$requests = $requestsResult['success'] ? $requestsResult['requests'] : [];

// Handle mark as read
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'mark_read') {
    $notificationId = (int)($_POST['notification_id'] ?? 0);
    if ($notificationModel->markAsRead($notificationId, $user['id'])) {
        $success = "Notification marked as read.";
    } else {
        $error = "Failed to update notification status.";
    }
}

// Fetch all notifications
$notifications = $notificationModel->getByUserId($user['id']);

// Page config
$pageTitle = "Notifications - Lanka Renters";
$activePage = "notifications";

include 'includes/header.php';
include 'includes/sidebar.php';
include 'includes/navbar.php';
?>
<main class="main-content">
    <div class="welcome-container">
        <div>
            <h2 class="welcome-title">Notification Center</h2>
            <p class="welcome-subtitle">Stay updated on your document status, leave approvals, and assigned bookings.</p>
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

    <!-- Connection Requests Card -->
    <div class="card" style="margin-bottom: 25px;">
        <h2 class="card-title">Owner Connection Requests</h2>
        <?php if (empty($requests)): ?>
            <p style="font-style: italic; color: var(--text-muted);">No pending owner connection requests.</p>
        <?php else: ?>
            <div style="display: flex; flex-direction: column; gap: 15px;">
                <?php foreach ($requests as $req): ?>
                    <div style="border: 1px solid var(--border); padding: 18px 24px; border-radius: 6px; background-color: #ffffff; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                        <div>
                            <div style="font-weight: 700; font-size: 15px; margin-bottom: 4px; color: var(--text-main);">
                                Request from Owner: <?php echo htmlspecialchars($req['owner_name']); ?>
                            </div>
                            <div style="color: var(--text-muted); font-size: 13px; margin-bottom: 6px;">
                                Owner Vehicles Count: <strong><?php echo $req['vehicle_count']; ?></strong><br>
                                Request Date: <?php echo date('Y-m-d H:i', strtotime($req['created_at'])); ?>
                            </div>
                        </div>
                        <div style="display: flex; gap: 10px;">
                            <form action="" method="POST" style="margin: 0;">
                                <input type="hidden" name="action" value="accept_request">
                                <input type="hidden" name="link_id" value="<?php echo (int)$req['id']; ?>">
                                <button type="submit" class="btn-blue" style="padding: 8px 16px; font-size: 13px; background-color: var(--success); border-color: var(--success);">Accept</button>
                            </form>
                            <form action="" method="POST" style="margin: 0;">
                                <input type="hidden" name="action" value="reject_request">
                                <input type="hidden" name="link_id" value="<?php echo (int)$req['id']; ?>">
                                <button type="submit" class="btn-blue" style="padding: 8px 16px; font-size: 13px; background-color: var(--danger); border-color: var(--danger);">Reject</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- List of Notifications -->
    <div class="card">
        <h2 class="card-title">Your Notification Log</h2>
        <?php if (empty($notifications)): ?>
            <p style="font-style: italic; color: var(--text-muted);">You have no notifications at this time.</p>
        <?php else: ?>
            <div style="display: flex; flex-direction: column; gap: 15px;">
                <?php foreach ($notifications as $notif): 
                    $isUnread = !$notif['is_read'];
                ?>
                    <div style="border: 1px solid var(--border); padding: 18px 24px; border-radius: 6px; background-color: <?php echo $isUnread ? '#f8fafc' : '#ffffff'; ?>; border-left: <?php echo $isUnread ? '4px solid var(--primary)' : '1px solid var(--border)'; ?>; display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <div style="font-weight: 700; font-size: 15px; margin-bottom: 4px; color: var(--text-main);"><?php echo htmlspecialchars($notif['title']); ?></div>
                            <div style="color: var(--text-muted); font-size: 14px; margin-bottom: 6px;"><?php echo htmlspecialchars($notif['message']); ?></div>
                            <div style="font-size: 11px; color: #999;"><?php echo date('Y-m-d H:i', strtotime($notif['created_at'])); ?></div>
                        </div>
                        <div>
                            <?php if ($isUnread): ?>
                                <form action="" method="POST" style="margin: 0;">
                                    <input type="hidden" name="action" value="mark_read">
                                    <input type="hidden" name="notification_id" value="<?php echo (int)$notif['id']; ?>">
                                    <button type="submit" class="btn-blue" style="padding: 6px 12px; font-size: 12px;">Mark as Read</button>
                                </form>
                            <?php else: ?>
                                <span style="font-size: 13px; color: var(--text-muted); font-weight: 600;">Read</span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</main>
<?php
include 'includes/footer.php';
?>
