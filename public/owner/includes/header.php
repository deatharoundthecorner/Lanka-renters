<?php
require_once dirname(dirname(dirname(__DIR__))) . '/app/helpers/AuthHelper.php';
AuthHelper::requireRole('owner');
$_ownerSessionUser = AuthHelper::getCurrentUser();
$_ownerDisplayName = htmlspecialchars($_ownerSessionUser['name'] ?? 'Owner');
$_ownerInitial = strtoupper(mb_substr((string)($_ownerSessionUser['name'] ?? 'Owner'), 0, 1));
$_ownerHeaderPage = basename($_SERVER['PHP_SELF'], '.php');
$_ownerHeaderLabels = [
    'dashboard' => 'Dashboard',
    'vehicles' => 'Vehicles',
    'drivers' => 'Drivers',
    'bookings' => 'Bookings',
    'earnings' => 'Earnings',
    'inspection' => 'Inspections',
    'chat' => 'Chat',
    'replacement-requests' => 'Replacement Requests',
    'profile' => 'Profile',
];
$_ownerHeaderLabel = $_ownerHeaderLabels[$_ownerHeaderPage] ?? 'Owner Portal';
?>
<header class="owner-header top-navbar">
  <div class="navbar-left">
    <button type="button" class="menu-toggle" id="menuToggle" aria-label="Open navigation" aria-controls="sidebar" aria-expanded="false">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>
    </button>
    <nav class="lr-breadcrumb" aria-label="Breadcrumb">
      <span class="lr-breadcrumb-home">Owner</span>
      <span class="lr-breadcrumb-separator" aria-hidden="true">/</span>
      <span class="lr-breadcrumb-current"><?php echo htmlspecialchars($_ownerHeaderLabel); ?></span>
    </nav>
  </div>

  <div class="navbar-right">
    <?php
    $notificationViewAllUrl = '../notifications.php';
    include dirname(__DIR__, 2) . '/includes/notification-dropdown.php';
    ?>
    <div class="profile-menu">
      <span class="lr-header-avatar" aria-hidden="true"><?php echo htmlspecialchars($_ownerInitial); ?></span>
      <div class="profile-info">
        <span class="profile-name"><?php echo $_ownerDisplayName; ?></span>
        <span class="profile-role">Vehicle Owner</span>
      </div>
    </div>
  </div>
</header>

<link rel="stylesheet" href="../assets/css/lr-notifications.css">
<script src="../assets/js/lr-sidebar-scroll.js" defer></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
  const menuToggle = document.getElementById('menuToggle');
  const sidebar = document.getElementById('sidebar');
  const sidebarBackdrop = document.getElementById('sidebarBackdrop');

  function setOwnerSidebarOpen(isOpen) {
    if (!sidebar || !menuToggle) return;
    sidebar.classList.toggle('open', isOpen);
    document.body.classList.toggle('lr-sidebar-open', isOpen);
    menuToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    menuToggle.setAttribute('aria-label', isOpen ? 'Close navigation' : 'Open navigation');
  }

  if (menuToggle && sidebar) {
    menuToggle.addEventListener('click', function (event) {
      event.stopPropagation();
      setOwnerSidebarOpen(!sidebar.classList.contains('open'));
    });
    if (sidebarBackdrop) {
      sidebarBackdrop.addEventListener('click', function () { setOwnerSidebarOpen(false); });
    }
    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape' && sidebar.classList.contains('open')) {
        setOwnerSidebarOpen(false);
        menuToggle.focus();
      }
    });
    window.addEventListener('resize', function () {
      if (window.innerWidth > 768 && sidebar.classList.contains('open')) setOwnerSidebarOpen(false);
    });
  }
});
</script>
<script src="../assets/js/lr-notifications.js" defer></script>
