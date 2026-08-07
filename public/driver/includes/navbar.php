<?php
require_once dirname(dirname(dirname(__DIR__))) . '/app/models/Notification.php';
$notificationModel = new Notification();
$unreadCount = $notificationModel->getUnreadCount($user['id']);
?>
<header class="top-navbar">
    <div class="navbar-left">
        <button class="menu-toggle" id="menuToggle">
            <!-- Hamburger menu SVG -->
            <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
        </button>
    </div>
    
    <div class="navbar-right">
        <!-- Notification bell -->
        <a href="notifications.php" class="notification-icon-btn">
            <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
            </svg>
            <?php if ($unreadCount > 0): ?>
                <span class="notification-badge"><?php echo $unreadCount; ?></span>
            <?php endif; ?>
        </a>

        <!-- User profile details -->
        <div class="profile-menu">
            <div class="profile-info">
                <span class="profile-name"><?php echo htmlspecialchars($user['name'] ?? 'Driver'); ?></span>
                <span class="profile-role">Driver</span>
            </div>
            <!-- Logout button in top navbar -->
            <form action="dashboard.php" method="POST" style="margin: 0; display: inline-block;">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(AuthHelper::getCsrfToken()); ?>">
                <input type="hidden" name="action" value="logout">
                <button type="submit" class="btn-secondary" style="padding: 6px 12px; font-size: 12px; border-radius: 4px;">Logout</button>
            </form>
        </div>
    </div>
</header>
