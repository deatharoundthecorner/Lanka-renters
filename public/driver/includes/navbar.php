<?php
require_once dirname(dirname(dirname(__DIR__))) . '/app/helpers/AuthHelper.php';

$user = AuthHelper::getCurrentUser();
$driverPageLabels = [
    'dashboard' => 'Dashboard',
    'profile' => 'My Profile',
    'security' => 'Account & Security',
    'documents' => 'Documents',
    'owners' => 'Connected Owners',
    'availability' => 'Availability',
    'leave' => 'Leave Requests',
    'safety_check' => 'Safety Checklist',
    'trips' => 'Trips & Tracking',
    'vehicles' => 'Assigned Vehicles',
    'report_incident' => 'Report Incident',
    'messages' => 'Messages',
    'notifications' => 'Notifications',
    'performance' => 'Performance',
    'earnings' => 'Earnings'
];
$driverPageLabel = $driverPageLabels[$activePage ?? 'dashboard'] ?? 'Driver workspace';
$headerName = trim((string)(($user && isset($user['name'])) ? $user['name'] : 'Driver'));
$headerNameParts = preg_split('/\s+/', $headerName) ?: [];
$headerInitials = '';
foreach (array_slice($headerNameParts, 0, 2) as $headerNamePart) {
    $headerInitials .= strtoupper(substr($headerNamePart, 0, 1));
}
$headerInitials = $headerInitials !== '' ? $headerInitials : 'D';
?>
<header class="top-navbar">
    <div class="navbar-left">
        <button type="button" class="menu-toggle" id="menuToggle" aria-controls="sidebar" aria-expanded="false" aria-label="Open navigation">
            <!-- Hamburger menu SVG -->
            <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
        </button>
        <div class="lr-breadcrumb" aria-label="Page context">
            <span class="lr-breadcrumb-home">Driver</span>
            <span class="lr-breadcrumb-separator" aria-hidden="true">/</span>
            <span class="lr-breadcrumb-current"><?php echo htmlspecialchars($driverPageLabel); ?></span>
        </div>
    </div>
    
    <div class="navbar-right">
        <?php
        $notificationViewAllUrl = 'notifications.php';
        include dirname(__DIR__, 2) . '/includes/notification-dropdown.php';
        ?>

        <!-- User profile details -->
        <div class="profile-menu">
            <span class="lr-header-avatar" aria-hidden="true"><?php echo htmlspecialchars($headerInitials); ?></span>
            <div class="profile-info">
                <span class="profile-name"><?php echo htmlspecialchars(($user && isset($user['name'])) ? $user['name'] : 'Driver'); ?></span>
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
