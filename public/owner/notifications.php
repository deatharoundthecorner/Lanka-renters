<?php
require_once dirname(dirname(__DIR__)) . '/app/helpers/AuthHelper.php';
require_once dirname(dirname(__DIR__)) . '/app/models/Notification.php';

AuthHelper::startSession();
AuthHelper::requireRole('owner');

$user = AuthHelper::getCurrentUser();
$notificationModel = new Notification();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'mark_read') {
    if (!AuthHelper::validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = "CSRF security verification failed.";
    } else {
        $notificationId = (int)($_POST['notification_id'] ?? 0);
        if ($notificationModel->markAsRead($notificationId, $user['id'])) {
            $success = "Notification marked as read.";
        } else {
            $error = "Failed to update notification status.";
        }
    }
}

$notifications = $notificationModel->getByUserId($user['id']);
$unreadCount = count(array_filter($notifications, static fn($n) => empty($n['is_read'])));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - LankaRenters Owner</title>
    <meta name="description" content="View system notifications and fleet updates on LankaRenters.">
    <link rel="stylesheet" href="includes/assets/css/owner-style.css">
    <link rel="stylesheet" href="../assets/css/lr-tokens.css">
    <link rel="stylesheet" href="../assets/css/lr-shell.css">
    <link rel="stylesheet" href="../assets/css/lr-components.css">
    <link rel="stylesheet" href="includes/assets/css/owner-canonical.css">
</head>
<body>

<div class="dashboard-layout dashboard-wrapper">
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-wrapper">
        <?php include 'includes/header.php'; ?>

        <main class="main-content">
            <section class="welcome-container" style="margin-bottom: 24px;">
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
                    <div>
                        <h1 style="font-size: 1.5rem; font-weight: 700; color: #0f172a; margin: 0 0 6px;">Notifications</h1>
                        <p style="margin: 0; color: #64748b; font-size: 0.95rem;">Stay informed about booking requests, vehicle status, and driver assignments.</p>
                    </div>
                    <?php if ($unreadCount > 0): ?>
                        <span style="display: inline-flex; align-items: center; gap: 6px; padding: 6px 14px; background: #eff6ff; color: #2563eb; border-radius: 9999px; font-size: 0.85rem; font-weight: 600;">
                            <span style="width: 8px; height: 8px; border-radius: 50%; background: #2563eb;"></span>
                            <?= $unreadCount ?> unread
                        </span>
                    <?php endif; ?>
                </div>
            </section>

            <?php if (!empty($success)): ?>
                <div style="padding: 12px 16px; margin-bottom: 20px; background: #ecfdf5; border: 1px solid #a7f3d0; border-radius: 8px; color: #065f46; font-size: 0.9rem;">
                    <?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($error)): ?>
                <div style="padding: 12px 16px; margin-bottom: 20px; background: #fef2f2; border: 1px solid #fecaca; border-radius: 8px; color: #991b1b; font-size: 0.9rem;">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <section class="requests-list" aria-label="Notifications List" style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden;">
                <?php if (empty($notifications)): ?>
                    <div style="padding: 48px 24px; text-align: center;">
                        <div style="width: 56px; height: 56px; margin: 0 auto 16px; background: #f1f5f9; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #94a3b8;">
                            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                                <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                            </svg>
                        </div>
                        <h2 style="font-size: 1.1rem; font-weight: 600; color: #0f172a; margin: 0 0 8px;">No notifications yet</h2>
                        <p style="margin: 0 auto 20px; max-width: 420px; color: #64748b; font-size: 0.9rem;">You are up to date. You will be notified here whenever bookings, inspections, or driver updates require your attention.</p>
                        <a href="dashboard.php" class="button button-outline" style="display: inline-flex; align-items: center; gap: 8px; padding: 8px 16px; font-size: 0.88rem; text-decoration: none; border: 1px solid #cbd5e1; border-radius: 8px; color: #334155;">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
                            Back to Dashboard
                        </a>
                    </div>
                <?php else: ?>
                    <div style="display: flex; flex-direction: column; divide-y: 1px solid #f1f5f9;">
                        <?php foreach ($notifications as $item): ?>
                            <?php $isUnread = empty($item['is_read']); ?>
                            <div style="padding: 16px 20px; display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; border-bottom: 1px solid #f1f5f9; <?= $isUnread ? 'background: #f8fafc;' : '' ?>">
                                <div style="display: flex; gap: 14px; align-items: flex-start;">
                                    <div style="margin-top: 2px; width: 36px; height: 36px; border-radius: 8px; background: <?= $isUnread ? '#dbeafe' : '#f1f5f9' ?>; color: <?= $isUnread ? '#2563eb' : '#64748b' ?>; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                                            <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                                        </svg>
                                    </div>
                                    <div>
                                        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 4px;">
                                            <h3 style="font-size: 0.95rem; font-weight: 600; color: #0f172a; margin: 0;"><?= htmlspecialchars($item['title'] ?? 'Notification') ?></h3>
                                            <?php if ($isUnread): ?>
                                                <span style="font-size: 0.72rem; padding: 2px 8px; background: #eff6ff; color: #2563eb; border-radius: 9999px; font-weight: 600;">New</span>
                                            <?php endif; ?>
                                        </div>
                                        <p style="margin: 0 0 6px; font-size: 0.88rem; color: #475569;"><?= htmlspecialchars($item['message'] ?? '') ?></p>
                                        <span style="font-size: 0.78rem; color: #94a3b8;"><?= htmlspecialchars(date('M d, Y \a\t h:i A', strtotime($item['created_at'] ?? 'now'))) ?></span>
                                    </div>
                                </div>
                                <?php if ($isUnread): ?>
                                    <form method="POST" action="notifications.php" style="margin: 0; flex-shrink: 0;">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(AuthHelper::getCsrfToken()) ?>">
                                        <input type="hidden" name="action" value="mark_read">
                                        <input type="hidden" name="notification_id" value="<?= (int)$item['id'] ?>">
                                        <button type="submit" style="padding: 6px 12px; font-size: 0.8rem; background: #ffffff; border: 1px solid #cbd5e1; border-radius: 6px; color: #475569; cursor: pointer; transition: background 0.2s;">
                                            Mark as read
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        </main>
    </div>
</div>

</body>
</html>
