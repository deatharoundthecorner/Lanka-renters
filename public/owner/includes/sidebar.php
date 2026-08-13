<?php
// Determine current page for active navigation highlighting
$_ownerCurrentPage = basename($_SERVER['PHP_SELF'], '.php');
function ownerNavActive(string $page, string $current): string {
    return $page === $current ? ' active' : '';
}
?>
<aside class="owner-sidebar">
  <div class="sidebar-brand">
    <a href="/owner/dashboard.php">
      <span class="brand-logo" aria-hidden="true">L</span>
      <span class="brand-name">LankaRenters</span>
    </a>
    <span class="role-chip">Vehicle Owner</span>
  </div>

  <nav class="sidebar-nav" aria-label="Owner navigation">
    <ul class="nav-list">
      <li class="nav-item<?php echo ownerNavActive('dashboard', $_ownerCurrentPage); ?>">
        <a href="/owner/dashboard.php">
          <span class="nav-icon" aria-hidden="true">🏠</span>
          <span class="nav-label">Dashboard</span>
        </a>
      </li>
      <li class="nav-item<?php echo ownerNavActive('vehicles', $_ownerCurrentPage); ?>">
        <a href="/owner/vehicles.php">
          <span class="nav-icon" aria-hidden="true">🚗</span>
          <span class="nav-label">Vehicles</span>
        </a>
      </li>
      <li class="nav-item<?php echo ownerNavActive('drivers', $_ownerCurrentPage); ?>">
        <a href="/owner/drivers.php">
          <span class="nav-icon" aria-hidden="true">👤</span>
          <span class="nav-label">Drivers</span>
        </a>
      </li>
      <li class="nav-item<?php echo ownerNavActive('bookings', $_ownerCurrentPage); ?>">
        <a href="/owner/bookings.php">
          <span class="nav-icon" aria-hidden="true">🗓️</span>
          <span class="nav-label">Bookings</span>
        </a>
      </li>
      <li class="nav-item<?php echo ownerNavActive('earnings', $_ownerCurrentPage); ?>">
        <a href="/owner/earnings.php">
          <span class="nav-icon" aria-hidden="true">💰</span>
          <span class="nav-label">Earnings</span>
        </a>
      </li>
      <li class="nav-item<?php echo ownerNavActive('profile', $_ownerCurrentPage); ?>">
        <a href="/owner/profile.php">
          <span class="nav-icon" aria-hidden="true">🧑</span>
          <span class="nav-label">Profile</span>
        </a>
      </li>
    </ul>
  </nav>

  <div class="sidebar-footer">
    <!-- Logout: POST with CSRF token to prevent cross-site request forgery -->
    <form method="POST" action="/owner/logout.php" style="margin:0;">
      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(AuthHelper::getCsrfToken()); ?>">
      <button type="submit" class="logout-link" style="background:none;border:none;cursor:pointer;padding:0;width:100%;text-align:left;">
        <span class="logout-icon" aria-hidden="true">↩️</span>
        <span>Log out</span>
      </button>
    </form>
  </div>
</aside>

