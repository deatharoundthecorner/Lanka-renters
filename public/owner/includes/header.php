<?php
require_once dirname(dirname(dirname(__DIR__))) . '/app/helpers/AuthHelper.php';
AuthHelper::requireRole('owner');
$_ownerSessionUser = AuthHelper::getCurrentUser();
$_ownerDisplayName = htmlspecialchars($_ownerSessionUser['name'] ?? 'Owner');
$_ownerInitial     = strtoupper(mb_substr($_ownerDisplayName, 0, 1));
?>
<header class="owner-header">
  <div class="header-title">
    <h1>Owner Portal</h1>
    <p>Manage your fleet and bookings.</p>
  </div>

  <div class="header-actions">
    <button type="button" class="notification-button" aria-label="View notifications">
      <span class="notification-icon" aria-hidden="true">🔔</span>
    </button>

    <div class="profile-summary">
      <span class="profile-initial" aria-hidden="true"><?php echo $_ownerInitial; ?></span>
      <div class="profile-text">
        <span class="profile-name"><?php echo $_ownerDisplayName; ?></span>
        <span class="profile-role">Vehicle Owner</span>
      </div>
    </div>
  </div>
</header>