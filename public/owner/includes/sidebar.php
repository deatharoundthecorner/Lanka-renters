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
          <span class="nav-icon" aria-hidden="true">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <rect x="3" y="3" width="7" height="7" rx="1.5"></rect>
              <rect x="14" y="3" width="7" height="7" rx="1.5"></rect>
              <rect x="14" y="14" width="7" height="7" rx="1.5"></rect>
              <rect x="3" y="14" width="7" height="7" rx="1.5"></rect>
            </svg>
          </span>
          <span class="nav-label">Dashboard</span>
        </a>
      </li>

      <li class="nav-item<?php echo ownerNavActive('vehicles', $_ownerCurrentPage); ?>">
        <a href="/owner/vehicles.php">
          <span class="nav-icon" aria-hidden="true">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M19 17h2c.6 0 1-.4 1-1v-3c0-.9-.7-1.7-1.5-1.9C18.7 10.6 16 10 16 10s-1.3-1.4-2.2-2.3c-.5-.4-1.1-.7-1.8-.7H7c-.7 0-1.3.3-1.8.7C4.3 8.6 3 10 3 10s-2.7.6-4.5 1.1C.7 11.3 0 12.1 0 13v3c0 .6.4 1 1 1h2"></path>
              <circle cx="7" cy="17" r="2"></circle>
              <circle cx="17" cy="17" r="2"></circle>
            </svg>
          </span>
          <span class="nav-label">Vehicles</span>
        </a>
      </li>

      <li class="nav-item<?php echo ownerNavActive('drivers', $_ownerCurrentPage); ?>">
        <a href="/owner/drivers.php">
          <span class="nav-icon" aria-hidden="true">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
              <circle cx="9" cy="7" r="4"></circle>
              <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
              <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
            </svg>
          </span>
          <span class="nav-label">Drivers</span>
        </a>
      </li>

      <li class="nav-item<?php echo ownerNavActive('bookings', $_ownerCurrentPage); ?>">
        <a href="/owner/bookings.php">
          <span class="nav-icon" aria-hidden="true">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
              <line x1="16" y1="2" x2="16" y2="6"></line>
              <line x1="8" y1="2" x2="8" y2="6"></line>
              <line x1="3" y1="10" x2="21" y2="10"></line>
            </svg>
          </span>
          <span class="nav-label">Bookings</span>
        </a>
      </li>

      <li class="nav-item<?php echo ownerNavActive('earnings', $_ownerCurrentPage); ?>">
        <a href="/owner/earnings.php">
          <span class="nav-icon" aria-hidden="true">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M21 12V7H5a2 2 0 0 1 0-4h14v4"></path>
              <path d="M3 5v14a2 2 0 0 0 2 2h16v-5"></path>
              <path d="M18 12a2 2 0 0 0 0 4h4v-4z"></path>
            </svg>
          </span>
          <span class="nav-label">Earnings</span>
        </a>
      </li>

      <li class="nav-item<?php echo ownerNavActive('profile', $_ownerCurrentPage); ?>">
        <a href="/owner/profile.php">
          <span class="nav-icon" aria-hidden="true">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
              <circle cx="12" cy="7" r="4"></circle>
            </svg>
          </span>
          <span class="nav-label">Profile</span>
        </a>
      </li>
    </ul>
  </nav>

  <div class="sidebar-footer">
    <!-- Logout: POST with CSRF token to prevent cross-site request forgery -->
    <form method="POST" action="/owner/logout.php" style="margin:0;">
      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(AuthHelper::getCsrfToken()); ?>">
      <button type="submit" class="logout-link" style="background:none;border:none;cursor:pointer;padding:12px 16px;width:100%;text-align:left;">
        <span class="logout-icon" aria-hidden="true">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
            <polyline points="16 17 21 12 16 7"></polyline>
            <line x1="21" y1="12" x2="9" y2="12"></line>
          </svg>
        </span>
        <span>Log out</span>
      </button>
    </form>
  </div>
</aside>
