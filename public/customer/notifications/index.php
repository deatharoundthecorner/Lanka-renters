<?php
require_once dirname(__DIR__) . '/_bootstrap.php';
require_once dirname(__DIR__, 3) . '/app/controllers/CustomerPortalController.php';
$viewData = (new CustomerPortalController())->notificationsPage($_GET, $_POST, strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')));
if (isset($viewData['redirect'])) { header('Location: ' . customer_url($viewData['redirect']), true, 303); exit; }
require dirname(__DIR__) . '/components/layout/feature-start.php';
?>
<?php if (!empty($viewData['errors'])): ?><div class="alert alert--error" role="alert"><p><?= htmlspecialchars(implode(' ', $viewData['errors']), ENT_QUOTES, 'UTF-8') ?></p></div><?php endif; ?>
<div class="feature-toolbar">
    <nav class="filter-tabs" aria-label="Notification filter"><a href="<?= htmlspecialchars(customer_url('notifications/index.php?filter=all'), ENT_QUOTES, 'UTF-8') ?>" <?= $viewData['filter'] === 'all' ? 'aria-current="page"' : '' ?>>All</a><a href="<?= htmlspecialchars(customer_url('notifications/index.php?filter=unread'), ENT_QUOTES, 'UTF-8') ?>" <?= $viewData['filter'] === 'unread' ? 'aria-current="page"' : '' ?>>Unread</a></nav>
    <form method="post" action="<?= htmlspecialchars(customer_url('notifications/index.php?filter=' . rawurlencode($viewData['filter'])), ENT_QUOTES, 'UTF-8') ?>"><?= CustomerCsrf::field() ?><input type="hidden" name="action" value="mark_all"><button class="button button--secondary button--small" type="submit">Mark all as read</button></form>
</div>
<p class="result-count"><?= (int) $viewData['total'] ?> result<?= (int) $viewData['total'] === 1 ? '' : 's' ?></p>
<?php if ($viewData['database_error']): ?><section class="empty-state"><h2>Notifications are temporarily unavailable</h2><p>Please try again later.</p></section>
<?php elseif ($viewData['notifications'] === []): ?><section class="empty-state"><span class="empty-state__icon"><?= customer_icon('bell') ?></span><h2>No notifications found</h2><p>Database notifications assigned to your account will appear here.</p></section>
<?php else: ?><div class="feature-card-list">
    <?php foreach ($viewData['notifications'] as $notification): ?><article class="card notification-card <?= (int) $notification['is_read'] === 0 ? 'is-unread' : '' ?>">
        <div><p class="eyebrow"><?= (int) $notification['is_read'] === 0 ? 'Unread notification' : 'Read notification' ?></p><h2><?= htmlspecialchars($notification['title'], ENT_QUOTES, 'UTF-8') ?></h2><p><?= nl2br(htmlspecialchars($notification['message'], ENT_QUOTES, 'UTF-8')) ?></p><time datetime="<?= htmlspecialchars($notification['created_at'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(date('d M Y, h:i A', strtotime($notification['created_at'])), ENT_QUOTES, 'UTF-8') ?></time></div>
        <div class="stack-actions"><?php if (is_string($notification['related_route'])): ?><a class="button button--secondary button--small" href="<?= htmlspecialchars(customer_url($notification['related_route']), ENT_QUOTES, 'UTF-8') ?>">Open related record</a><?php endif; ?><?php if ((int) $notification['is_read'] === 0): ?><form method="post" action="<?= htmlspecialchars(customer_url('notifications/index.php?filter=' . rawurlencode($viewData['filter'])), ENT_QUOTES, 'UTF-8') ?>"><?= CustomerCsrf::field() ?><input type="hidden" name="action" value="mark_read"><input type="hidden" name="notification_id" value="<?= (int) $notification['id'] ?>"><button class="button button--primary button--small" type="submit">Mark as read</button></form><?php endif; ?></div>
    </article><?php endforeach; ?>
</div><?php endif; ?>
<?php if ($viewData['total_pages'] > 1): ?><nav class="pagination" aria-label="Notification pages"><?php for ($p = 1; $p <= $viewData['total_pages']; $p++): ?><a href="<?= htmlspecialchars(customer_url('notifications/index.php?filter=' . rawurlencode($viewData['filter']) . '&page=' . $p), ENT_QUOTES, 'UTF-8') ?>" <?= $p === $viewData['current_page'] ? 'aria-current="page"' : '' ?>><?= $p ?></a><?php endfor; ?></nav><?php endif; ?>
<?php require dirname(__DIR__) . '/components/layout/feature-end.php'; ?>

