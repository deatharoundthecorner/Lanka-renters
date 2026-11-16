<?php
require_once dirname(__DIR__, 2) . '/app/helpers/AuthHelper.php';
require_once dirname(__DIR__, 2) . '/app/models/Notification.php';

$notificationUser = AuthHelper::getCurrentUser();
$notificationUserId = (int)($notificationUser['id'] ?? 0);
$notificationRole = (string)($notificationUser['role'] ?? '');
$notificationAllowedRoles = ['customer', 'owner', 'driver', 'admin'];

if ($notificationUserId <= 0 || !in_array($notificationRole, $notificationAllowedRoles, true)) {
    return;
}

$notificationScript = (string)($_SERVER['SCRIPT_NAME'] ?? '');
$notificationPublicPosition = strpos($notificationScript, '/public/');
$notificationBaseUrl = $notificationPublicPosition === false
    ? ''
    : substr($notificationScript, 0, $notificationPublicPosition + strlen('/public'));

if (!isset($notificationViewAllUrl)) {
    $notificationViewAllUrl = $notificationBaseUrl . '/notifications.php';
}

$notificationUnreadCount = 0;
$notificationRecent = [];
try {
    $notificationModel = new Notification();
    $notificationUnreadCount = $notificationModel->getUnreadCount($notificationUserId);
    $notificationRecent = $notificationModel->getRecentByUserId($notificationUserId, 5);
} catch (Throwable $notificationError) {
    error_log('Notification header load failed: ' . $notificationError->getMessage());
}

$notificationEscape = static fn($value): string => htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
$notificationFormatTime = static function ($value): string {
    $timestamp = strtotime((string)$value);
    return $timestamp ? date('M j, g:i A', $timestamp) : '';
};
?>
<div class="lr-notification-center"
     data-lr-notifications
     data-endpoint="<?php echo $notificationEscape($notificationBaseUrl . '/api/notifications.php'); ?>"
     data-csrf="<?php echo $notificationEscape(AuthHelper::getCsrfToken()); ?>">
    <button type="button"
            class="lr-notification-bell"
            aria-label="Notifications"
            aria-expanded="false"
            aria-controls="lr-notification-dropdown"
            data-lr-notification-toggle>
        <svg width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9"></path>
            <path d="M13.7 21a2 2 0 0 1-3.4 0"></path>
        </svg>
        <span class="lr-notification-dot" data-lr-notification-dot <?php echo $notificationUnreadCount > 0 ? '' : 'hidden'; ?>></span>
    </button>

    <section class="lr-notification-dropdown" id="lr-notification-dropdown" data-lr-notification-panel hidden>
        <div class="lr-notification-dropdown__header">
            <div>
                <strong>Notifications</strong>
                <span data-lr-notification-summary><?php echo $notificationUnreadCount > 0 ? $notificationUnreadCount . ' unread' : 'You are up to date'; ?></span>
            </div>
            <button type="button" class="lr-notification-mark-all" data-lr-notification-mark-all <?php echo $notificationUnreadCount > 0 ? '' : 'hidden'; ?>>Mark all as read</button>
        </div>
        <div class="lr-notification-list" data-lr-notification-list>
            <?php if ($notificationRecent === []): ?>
                <div class="lr-notification-empty" data-lr-notification-empty>
                    <strong>No notifications</strong>
                    <span>Updates for your account will appear here.</span>
                </div>
            <?php else: ?>
                <?php foreach ($notificationRecent as $notification): ?>
                    <button type="button"
                            class="lr-notification-row<?php echo empty($notification['is_read']) ? ' is-unread' : ''; ?>"
                            data-lr-notification-id="<?php echo (int)$notification['id']; ?>">
                        <span class="lr-notification-row__content">
                            <strong><?php echo $notificationEscape($notification['title']); ?></strong>
                            <span><?php echo $notificationEscape($notification['message']); ?></span>
                            <time><?php echo $notificationEscape($notificationFormatTime($notification['created_at'])); ?></time>
                        </span>
                    </button>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <a class="lr-notification-view-all" href="<?php echo $notificationEscape($notificationViewAllUrl); ?>">View all notifications</a>
    </section>
</div>
