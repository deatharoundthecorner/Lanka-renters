<?php
require_once dirname(__DIR__) . '/app/helpers/AuthHelper.php';
require_once dirname(__DIR__) . '/app/models/Notification.php';

AuthHelper::startSession();
if (!AuthHelper::isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$user = AuthHelper::getCurrentUser();
$userId = (int)($user['id'] ?? 0);
$role = (string)($user['role'] ?? '');
$allowedRoles = ['customer', 'owner', 'driver', 'admin'];
if ($userId <= 0 || !in_array($role, $allowedRoles, true)) {
    AuthHelper::logout();
    header('Location: login.php');
    exit;
}

if ($role === 'customer') {
    header('Location: customer/notifications/index.php');
    exit;
}
if ($role === 'driver') {
    header('Location: driver/notifications.php');
    exit;
}

$roleDashboards = [
    'owner' => 'owner/dashboard.php',
    'admin' => 'admin/dashboard.php',
];
$roleLabels = [
    'owner' => 'Vehicle Owner',
    'admin' => 'Admin',
];
$flash = '';
$notifications = [];
$databaseError = false;

try {
    $model = new Notification();
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
        if (!AuthHelper::validateCsrfToken((string)($_POST['csrf_token'] ?? ''))) {
            $flash = 'Security verification failed. Please refresh and try again.';
        } else {
            $action = (string)($_POST['action'] ?? '');
            if ($action === 'mark_all') {
                $model->markAllAsRead($userId);
                $flash = 'All notifications marked as read.';
            } elseif ($action === 'mark_read') {
                $notificationId = filter_var($_POST['notification_id'] ?? null, FILTER_VALIDATE_INT, [
                    'options' => ['min_range' => 1],
                ]);
                if ($notificationId === false || !$model->markAsRead((int)$notificationId, $userId)) {
                    $flash = 'Notification not found.';
                }
            }
        }
    }
    $notifications = $model->getByUserId($userId);
} catch (Throwable $error) {
    error_log('Notification page error: ' . $error->getMessage());
    $databaseError = true;
}

$escape = static fn($value): string => htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Notifications | Lanka Renters</title>
    <link rel="stylesheet" href="assets/css/lr-tokens.css">
    <link rel="stylesheet" href="assets/css/lr-components.css">
    <style>
        body { margin: 0; color: var(--lr-text); background: var(--lr-bg); font-family: Inter, system-ui, sans-serif; }
        .notification-page { width: min(820px, calc(100% - 32px)); margin: 40px auto; }
        .notification-page__top { display: flex; align-items: center; justify-content: space-between; gap: 16px; margin-bottom: 24px; }
        .notification-page__top h1 { margin: 0 0 5px; font-size: 30px; }
        .notification-page__top p { margin: 0; color: var(--lr-text-muted); }
        .notification-page__list { overflow: hidden; background: var(--lr-surface); border: 1px solid var(--lr-border); border-radius: 10px; }
        .notification-page__row { display: flex; align-items: flex-start; justify-content: space-between; gap: 18px; padding: 17px 18px; border-bottom: 1px solid var(--lr-border); }
        .notification-page__row:last-child { border-bottom: 0; }
        .notification-page__row.is-unread { background: var(--lr-primary-soft); }
        .notification-page__content { display: grid; gap: 5px; }
        .notification-page__content p, .notification-page__content time { margin: 0; color: var(--lr-text-muted); }
        .notification-page__content time { font-size: 12px; }
        .notification-page__empty { padding: 50px 20px; text-align: center; color: var(--lr-text-muted); }
        .notification-page__actions { display: flex; gap: 10px; align-items: center; }
        @media (max-width: 600px) { .notification-page__top, .notification-page__row { align-items: stretch; flex-direction: column; } }
    </style>
</head>
<body>
<main class="notification-page">
    <div class="notification-page__top">
        <div>
            <h1>Notifications</h1>
            <p><?php echo $escape($roleLabels[$role]); ?> workspace updates</p>
        </div>
        <div class="notification-page__actions">
            <a class="btn-secondary" href="<?php echo $escape($roleDashboards[$role]); ?>">Back to dashboard</a>
            <?php if ($notifications !== []): ?>
                <form method="post">
                    <input type="hidden" name="csrf_token" value="<?php echo $escape(AuthHelper::getCsrfToken()); ?>">
                    <input type="hidden" name="action" value="mark_all">
                    <button class="btn-primary" type="submit">Mark all as read</button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($flash !== ''): ?><div class="alert"><?php echo $escape($flash); ?></div><?php endif; ?>
    <?php if ($databaseError): ?><div class="alert alert-danger">Notifications are temporarily unavailable.</div><?php endif; ?>

    <section class="notification-page__list" aria-label="Notification history">
        <?php if (!$databaseError && $notifications === []): ?>
            <div class="notification-page__empty"><strong>No notifications</strong><p>Updates for your account will appear here.</p></div>
        <?php endif; ?>
        <?php foreach ($notifications as $notification): ?>
            <article class="notification-page__row<?php echo empty($notification['is_read']) ? ' is-unread' : ''; ?>">
                <div class="notification-page__content">
                    <strong><?php echo $escape($notification['title']); ?></strong>
                    <p><?php echo $escape($notification['message']); ?></p>
                    <time><?php echo $escape(date('M j, Y, g:i A', strtotime((string)$notification['created_at']))); ?></time>
                </div>
                <?php if (empty($notification['is_read'])): ?>
                    <form method="post">
                        <input type="hidden" name="csrf_token" value="<?php echo $escape(AuthHelper::getCsrfToken()); ?>">
                        <input type="hidden" name="action" value="mark_read">
                        <input type="hidden" name="notification_id" value="<?php echo (int)$notification['id']; ?>">
                        <button class="btn-secondary btn-sm" type="submit">Mark as read</button>
                    </form>
                <?php endif; ?>
            </article>
        <?php endforeach; ?>
    </section>
</main>
</body>
</html>
