<?php
// partials/header.php
// Lanka Renters Admin Header Component

if (!isset($pageTitle)) {
    $pageTitle = "Overview";
}
?>
<header class="top-header">
    <div class="header-left">
        <button class="mobile-nav-toggle" id="mobileNavToggle" aria-label="Open Navigation">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
        </button>
        <div class="breadcrumb lr-breadcrumb">
            <span class="lr-breadcrumb-home">Admin</span>
            <span class="lr-breadcrumb-separator" aria-hidden="true">/</span>
            <span class="current-page lr-breadcrumb-current"><?php echo htmlspecialchars($pageTitle); ?></span>
        </div>
    </div>

    <div class="header-right">
        <?php
        $notificationViewAllUrl = '../notifications.php';
        include dirname(__DIR__, 2) . '/includes/notification-dropdown.php';
        ?>
        <div class="header-user-profile profile-menu">
            <div class="user-avatar-badge lr-header-avatar" aria-hidden="true">AU</div>
            <div class="user-name-role profile-info">
                <h3 class="profile-name">Admin</h3>
                <p class="profile-role">Admin User</p>
            </div>
        </div>
    </div>
</header>
