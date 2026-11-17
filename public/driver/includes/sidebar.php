<?php
$activePage = $activePage ?? 'dashboard';
$sidebarUser = AuthHelper::getCurrentUser();
$sidebarName = trim((string)($sidebarUser['name'] ?? 'Driver'));
$sidebarNameParts = preg_split('/\s+/', $sidebarName) ?: [];
$sidebarInitials = '';
foreach (array_slice($sidebarNameParts, 0, 2) as $sidebarNamePart) {
    $sidebarInitials .= strtoupper(substr($sidebarNamePart, 0, 1));
}
$sidebarInitials = $sidebarInitials !== '' ? $sidebarInitials : 'D';
?>
<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <a href="dashboard.php" class="lr-driver-brand-link" style="display: flex; align-items: center; gap: 12px; text-decoration: none; color: inherit; width: 100%;">
            <span class="lr-brand-mark lr-brand-mark--image" aria-hidden="true"><img src="../assets/images/brand/lanka-renters-mark.png" alt=""></span>
            <span class="lr-brand-copy">
                <span class="lr-brand-name">Lanka<span>Renters</span></span>
                <span class="lr-workspace-name">Driver workspace</span>
            </span>
        </a>
    </div>
    <nav class="sidebar-nav">
        <a href="dashboard.php" class="nav-link <?php echo $activePage === 'dashboard' ? 'active' : ''; ?>">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
            </svg>
            Dashboard
        </a>

        <div class="nav-group-title">Driver Management</div>
        
        <a href="profile.php" class="nav-link <?php echo $activePage === 'profile' ? 'active' : ''; ?>">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
            </svg>
            My Profile
        </a>

        <a href="security.php" class="nav-link <?php echo $activePage === 'security' ? 'active' : ''; ?>">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
            </svg>
            Account & Security
        </a>

        <a href="documents.php" class="nav-link <?php echo $activePage === 'documents' ? 'active' : ''; ?>">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            Documents
        </a>

        <a href="owners.php" class="nav-link <?php echo $activePage === 'owners' ? 'active' : ''; ?>">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
            </svg>
            Connected Owners
        </a>

        <a href="availability.php" class="nav-link <?php echo $activePage === 'availability' ? 'active' : ''; ?>">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            Availability
        </a>

        <a href="leave.php" class="nav-link <?php echo $activePage === 'leave' ? 'active' : ''; ?>">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
            Leave Requests
        </a>

        <a href="safety_check.php" class="nav-link <?php echo $activePage === 'safety_check' ? 'active' : ''; ?>">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
            </svg>
            Safety Checklist
        </a>

        <div class="nav-group-title">Operations</div>

        <a href="trips.php" class="nav-link <?php echo $activePage === 'trips' ? 'active' : ''; ?>">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/>
            </svg>
            My Trips & Tracking
        </a>

        <a href="vehicles.php" class="nav-link <?php echo $activePage === 'vehicles' ? 'active' : ''; ?>">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 17h14M6 17v2m12-2v2M4 14l2-6h12l2 6v3H4v-3z"/><circle cx="7" cy="14" r="1"/><circle cx="17" cy="14" r="1"/>
            </svg>
            Assigned Vehicles
        </a>

        <a href="report_incident.php" class="nav-link <?php echo $activePage === 'report_incident' ? 'active' : ''; ?>">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
            Report Incident
        </a>

        <div class="nav-group-title">Communication</div>

        <a href="messages.php" class="nav-link <?php echo $activePage === 'messages' ? 'active' : ''; ?>">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
            </svg>
            Messages
        </a>

        <a href="notifications.php" class="nav-link <?php echo $activePage === 'notifications' ? 'active' : ''; ?>">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
            </svg>
            Notifications
        </a>

        <div class="nav-group-title">Reports</div>

        <a href="performance.php" class="nav-link <?php echo $activePage === 'performance' ? 'active' : ''; ?>">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 002 2h2a2 2 0 002-2z"/>
            </svg>
            Performance
        </a>

        <a href="earnings.php" class="nav-link <?php echo $activePage === 'earnings' ? 'active' : ''; ?>">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            Earnings History
        </a>
    </nav>
    <div class="sidebar-footer">
        <div class="lr-sidebar-account">
            <span class="lr-account-avatar" aria-hidden="true"><?php echo htmlspecialchars($sidebarInitials); ?></span>
            <span class="lr-sidebar-account-copy">
                <span class="lr-sidebar-account-name"><?php echo htmlspecialchars($sidebarName); ?></span>
                <span class="lr-sidebar-account-role">Driver account</span>
            </span>
        </div>
        <form action="dashboard.php" method="POST" style="margin: 0;">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(AuthHelper::getCsrfToken()); ?>">
            <input type="hidden" name="action" value="logout">
            <button type="submit" class="btn-sidebar-logout">
                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/>
                </svg>
                Logout
            </button>
        </form>
    </div>
</aside>
