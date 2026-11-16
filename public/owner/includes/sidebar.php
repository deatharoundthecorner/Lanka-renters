<?php
$_ownerCurrentPage = basename($_SERVER['PHP_SELF'], '.php');
function ownerNavActive(string $page, string $current): string {
    return $page === $current ? ' active' : '';
}

$_ownerSidebarUser = AuthHelper::getCurrentUser();
$_ownerSidebarName = trim((string)($_ownerSidebarUser['name'] ?? 'Owner'));
$_ownerSidebarNameParts = preg_split('/\s+/', $_ownerSidebarName) ?: [];
$_ownerSidebarInitials = '';
foreach (array_slice($_ownerSidebarNameParts, 0, 2) as $_ownerSidebarNamePart) {
    $_ownerSidebarInitials .= strtoupper(substr($_ownerSidebarNamePart, 0, 1));
}
$_ownerSidebarInitials = $_ownerSidebarInitials !== '' ? $_ownerSidebarInitials : 'O';
?>
<aside class="owner-sidebar sidebar" id="sidebar">
  <div class="sidebar-brand">
    <a href="/owner/dashboard.php" class="lr-owner-brand-link">
      <span class="lr-brand-mark lr-brand-mark--image" aria-hidden="true"><img src="../assets/images/brand/lanka-renters-mark.png" alt=""></span>
      <span class="lr-brand-copy">
        <span class="lr-brand-name">Lanka<span>Renters</span></span>
        <span class="lr-workspace-name">Owner workspace</span>
      </span>
    </a>
  </div>

  <nav class="sidebar-nav" aria-label="Owner navigation">
    <a href="/owner/dashboard.php" class="nav-link<?php echo ownerNavActive('dashboard', $_ownerCurrentPage); ?>">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="7" height="7" rx="1.5"></rect><rect x="14" y="3" width="7" height="7" rx="1.5"></rect><rect x="14" y="14" width="7" height="7" rx="1.5"></rect><rect x="3" y="14" width="7" height="7" rx="1.5"></rect></svg>
      Dashboard
    </a>

    <div class="nav-group-title">Fleet Management</div>
    <a href="/owner/vehicles.php" class="nav-link<?php echo ownerNavActive('vehicles', $_ownerCurrentPage); ?>">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 17h14M6 17v2m12-2v2M4 14l2-6h12l2 6v3H4v-3z"></path><circle cx="7" cy="14" r="1"></circle><circle cx="17" cy="14" r="1"></circle></svg>
      Vehicles
    </a>
    <a href="/owner/vehicle-add.php" class="nav-link<?php echo ownerNavActive('vehicle-add', $_ownerCurrentPage); ?>">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="16"></line><line x1="8" y1="12" x2="16" y2="12"></line></svg>
      Add Vehicle
    </a>
    <a href="/owner/drivers.php" class="nav-link<?php echo ownerNavActive('drivers', $_ownerCurrentPage); ?>">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
      Drivers
    </a>

    <div class="nav-group-title">Rental Operations</div>
    <a href="/owner/bookings.php" class="nav-link<?php echo ownerNavActive('bookings', $_ownerCurrentPage); ?>">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
      Bookings
    </a>
    <a href="/owner/inspection.php" class="nav-link<?php echo ownerNavActive('inspection', $_ownerCurrentPage); ?>">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 11l3 3L22 4"></path><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path></svg>
      Inspections
    </a>
    <a href="/owner/replacement-requests.php" class="nav-link<?php echo ownerNavActive('replacement-requests', $_ownerCurrentPage); ?>">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="23 4 23 10 17 10"></polyline><polyline points="1 20 1 14 7 14"></polyline><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path></svg>
      Replacement Requests
    </a>
    <a href="/owner/driver-changes.php" class="nav-link<?php echo ownerNavActive('driver-changes', $_ownerCurrentPage); ?>">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
      Driver Changes
    </a>

    <div class="nav-group-title">Communication</div>
    <a href="/owner/chat.php" class="nav-link<?php echo ownerNavActive('chat', $_ownerCurrentPage); ?>">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
      Messages
    </a>

    <div class="nav-group-title">Account &amp; Reports</div>
    <a href="/owner/earnings.php" class="nav-link<?php echo ownerNavActive('earnings', $_ownerCurrentPage); ?>">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 2v20M17 6H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
      Earnings
    </a>
    <a href="/owner/profile.php" class="nav-link<?php echo ownerNavActive('profile', $_ownerCurrentPage); ?>">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
      Profile
    </a>
  </nav>

  <div class="sidebar-footer">
    <div class="lr-sidebar-account">
      <span class="lr-account-avatar" aria-hidden="true"><?php echo htmlspecialchars($_ownerSidebarInitials); ?></span>
      <span class="lr-sidebar-account-copy">
        <span class="lr-sidebar-account-name"><?php echo htmlspecialchars($_ownerSidebarName); ?></span>
        <span class="lr-sidebar-account-role">Owner account</span>
      </span>
    </div>
    <form method="POST" action="/owner/logout.php" style="margin:0;">
      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(AuthHelper::getCsrfToken()); ?>">
      <button type="submit" class="logout-link btn-sidebar-logout">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
        <span>Logout</span>
      </button>
    </form>
  </div>
</aside>
<button type="button" class="sidebar-backdrop" id="sidebarBackdrop" aria-label="Close navigation" tabindex="-1"></button>
