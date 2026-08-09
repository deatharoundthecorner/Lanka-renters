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
        <div class="breadcrumb">
            <span>Lanka Renters</span>
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
            <span class="current-page"><?php echo htmlspecialchars($pageTitle); ?></span>
        </div>
    </div>

    <div class="header-right">
        <button class="notification-bell" title="Notifications" onclick="showToast('You have 11 pending items to review.', 'info')">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
            <span class="dot"></span>
        </button>

        <div class="header-user-profile">
            <div class="user-avatar-badge">AU</div>
            <div class="user-name-role">
                <h3>Admin</h3>
                <p>Admin User</p>
            </div>
        </div>
    </div>
</header>
