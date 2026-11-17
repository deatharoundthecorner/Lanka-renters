<?php
// partials/sidebar.php
// Lanka Renters Admin Sidebar Navigation Component

$currentPage = basename($_SERVER['PHP_SELF']);
?>
<aside class="sidebar" id="adminSidebar">
    <div class="sidebar-header sidebar-brand">
        <a href="dashboard.php" class="sidebar-brand-link" style="display: flex; align-items: center; gap: 12px; text-decoration: none; color: inherit; width: 100%;">
            <span class="lr-brand-mark lr-brand-mark--image" aria-hidden="true"><img src="../assets/images/brand/lanka-renters-mark.png" alt=""></span>
            <span class="lr-brand-copy">
                <span class="lr-brand-name">Lanka<span>Renters</span></span>
                <span class="lr-workspace-name">Admin workspace</span>
            </span>
        </a>
        <button class="sidebar-close-btn" id="sidebarCloseBtn" aria-label="Close Sidebar">&times;</button>
    </div>

    <div class="sidebar-nav">
        <div class="nav-section-title">ADMINISTRATION</div>
        <ul class="nav-list">
            <li class="nav-item <?php echo ($currentPage == 'dashboard.php' || $currentPage == 'index.php') ? 'active' : ''; ?>">
                <a href="dashboard.php">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/></svg>
                    <span>Dashboard</span>
                </a>
            </li>
            <li class="nav-item <?php echo ($currentPage == 'users.php') ? 'active' : ''; ?>">
                <a href="users.php">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                    <span>Users</span>
                </a>
            </li>
            <li class="nav-item <?php echo ($currentPage == 'customer_verification.php') ? 'active' : ''; ?>">
                <a href="customer_verification.php">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M12 3l8 4v5c0 5-3.5 8-8 9-4.5-1-8-4-8-9V7l8-4z"/><path d="M9 12l2 2 4-4"/></svg>
                    <span>Customer Verification</span>
                </a>
            </li>
            <li class="nav-item <?php echo ($currentPage == 'guarantors.php') ? 'active' : ''; ?>">
                <a href="guarantors.php">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M20 8v6M23 11h-6"/></svg>
                    <span>Customer Guarantors</span>
                </a>
            </li>
            <li class="nav-item <?php echo ($currentPage == 'vehicle_owners.php') ? 'active' : ''; ?>">
                <a href="vehicle_owners.php">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 11l-3-3m0 0l-3 3m3-3v12"/></svg>
                    <span>Vehicle Owners</span>
                </a>
            </li>
            <li class="nav-item <?php echo ($currentPage == 'drivers.php') ? 'active' : ''; ?>">
                <a href="drivers.php">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    <span>Drivers</span>
                </a>
            </li>
            <li class="nav-item <?php echo ($currentPage == 'vehicles.php') ? 'active' : ''; ?>">
                <a href="vehicles.php">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><rect x="3" y="11" width="18" height="7" rx="2"/><circle cx="7" cy="18" r="2"/><circle cx="17" cy="18" r="2"/><path d="M5 11l2-5h10l2 5"/></svg>
                    <span>Vehicles</span>
                </a>
            </li>
            <li class="nav-item <?php echo ($currentPage == 'account_change_requests.php') ? 'active' : ''; ?>">
                <a href="account_change_requests.php">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                    <span>Account Change Requests</span>
                </a>
            </li>
            <li class="nav-section-title">RENTAL OPERATIONS</li>
            <li class="nav-item <?php echo ($currentPage == 'bookings.php') ? 'active' : ''; ?>">
                <a href="bookings.php">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                    <span>Bookings</span>
                </a>
            </li>
            <li class="nav-item <?php echo ($currentPage == 'payments.php') ? 'active' : ''; ?>">
                <a href="payments.php">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
                    <span>Payments</span>
                </a>
            </li>
            <li class="nav-item <?php echo ($currentPage == 'incidents.php') ? 'active' : ''; ?>">
                <a href="incidents.php">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                    <span>Incidents</span>
                </a>
            </li>
            <li class="nav-item <?php echo ($currentPage == 'replacement_requests.php') ? 'active' : ''; ?>">
                <a href="replacement_requests.php">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg>
                    <span>Replacement Requests</span>
                </a>
            </li>
            <li class="nav-item <?php echo ($currentPage == 'driver_changes.php') ? 'active' : ''; ?>">
                <a href="driver_changes.php">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                    <span>Driver Changes</span>
                </a>
            </li>
            <li class="nav-item <?php echo ($currentPage == 'settlements.php') ? 'active' : ''; ?>">
                <a href="settlements.php">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                    <span>Settlements</span>
                </a>
            </li>
            <li class="nav-section-title">COMMUNICATION</li>
            <li class="nav-item <?php echo ($currentPage == 'messages.php') ? 'active' : ''; ?>">
                <a href="messages.php">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                    <span>Messages</span>
                </a>
            </li>
            <li class="nav-item <?php echo ($currentPage == 'announcements.php') ? 'active' : ''; ?>">
                <a href="announcements.php">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                    <span>Announcements</span>
                </a>
            </li>
            <li class="nav-section-title">REPORTS</li>
            <li class="nav-item <?php echo ($currentPage == 'analytics.php') ? 'active' : ''; ?>">
                <a href="analytics.php">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M3 3v18h18"/><path d="M18 17V9"/><path d="M13 17V5"/><path d="M8 17v-3"/></svg>
                    <span>Platform Analytics</span>
                </a>
            </li>
            <li class="nav-item <?php echo ($currentPage == 'reports.php') ? 'active' : ''; ?>">
                <a href="reports.php">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
                    <span>Reports</span>
                </a>
            </li>
            <li class="nav-item <?php echo ($currentPage == 'email_logs.php') ? 'active' : ''; ?>">
                <a href="email_logs.php">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                    <span>Email Logs</span>
                </a>
            </li>
        </ul>
    </div>

    <div class="sidebar-footer">
        <div class="user-profile-summary lr-sidebar-account">
            <div class="profile-avatar-sm lr-account-avatar">A</div>
            <div class="profile-info-sm lr-sidebar-account-copy">
                <span class="lr-sidebar-account-name" style="font-size: 13px; font-weight: 600; color: var(--text-main);">Admin Team</span>
                <span class="lr-sidebar-account-role" style="font-size: 11px; color: var(--text-muted);">Admin workspace</span>
            </div>
        </div>
        <a href="../logout.php" class="btn-logout btn-sidebar-logout">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
            <span>Logout</span>
        </a>
    </div>
</aside>
