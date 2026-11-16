<?php
$activeNav = (string) ($viewData['active_nav'] ?? '');
$customerGroups = [
    'CUSTOMER' => [
        ['key' => 'dashboard', 'label' => 'Dashboard', 'path' => 'dashboard.php', 'icon' => 'dashboard'],
        ['key' => 'verification', 'label' => 'Verification', 'path' => 'verification/index.php', 'icon' => 'shield'],
    ],
    'RENTAL OPERATIONS' => [
        ['key' => 'vehicles', 'label' => 'Search Vehicles', 'path' => 'vehicles/index.php', 'icon' => 'car'],
        ['key' => 'bookings', 'label' => 'Bookings', 'path' => 'bookings/index.php', 'icon' => 'calendar'],
        ['key' => 'pickup-status', 'label' => 'Pickup Status', 'path' => 'pickup-status/index.php', 'icon' => 'clipboard'],
        ['key' => 'inspection', 'label' => 'Inspection', 'path' => 'inspection/index.php', 'icon' => 'clipboard'],
        ['key' => 'payments', 'label' => 'Payment', 'path' => 'payments/index.php', 'icon' => 'credit-card'],
        ['key' => 'return', 'label' => 'Return', 'path' => 'returns/index.php', 'icon' => 'return'],
    ],
    'SUPPORT & FEEDBACK' => [
        ['key' => 'incidents', 'label' => 'Incidents', 'path' => 'incidents/index.php', 'icon' => 'alert'],
        ['key' => 'driver-change', 'label' => 'Driver Change Request', 'path' => 'driver-change/index.php', 'icon' => 'switch'],
        ['key' => 'reviews', 'label' => 'Reviews', 'path' => 'reviews/index.php', 'icon' => 'star'],
    ],
    'COMMUNICATION' => [
        ['key' => 'chat', 'label' => 'Messages', 'path' => 'chat/index.php', 'icon' => 'chat'],
        ['key' => 'notifications', 'label' => 'Notifications', 'path' => 'notifications/index.php', 'icon' => 'bell'],
    ],
    'ACCOUNT' => [
        ['key' => 'profile', 'label' => 'Profile', 'path' => 'profile/index.php', 'icon' => 'user'],
    ],
];
$customerName = (string) ($viewData['customer']['name'] ?? 'Customer');
?>
<aside class="customer-sidebar sidebar" id="customer-sidebar" aria-label="Customer navigation" data-sidebar>
    <div class="sidebar-brand-row">
        <a class="sidebar-brand" href="<?= htmlspecialchars(customer_url('dashboard.php'), ENT_QUOTES, 'UTF-8') ?>">
            <span class="lr-brand-mark lr-brand-mark--image" aria-hidden="true"><img src="<?= htmlspecialchars(customer_url('../assets/images/brand/lanka-renters-mark.png'), ENT_QUOTES, 'UTF-8') ?>" alt=""></span>
            <span class="lr-brand-copy">
                <span class="lr-brand-name">Lanka<span>Renters</span></span>
                <span class="lr-workspace-name">Customer workspace</span>
            </span>
        </a>
        <button class="icon-button sidebar-close" type="button" aria-label="Close navigation" data-sidebar-close>
            <?= customer_icon('close') ?>
        </button>
    </div>

    <nav class="sidebar-nav" aria-label="Main Customer navigation">
        <?php foreach ($customerGroups as $groupLabel => $links): ?>
            <p class="sidebar-nav__label nav-group-title"><?= htmlspecialchars($groupLabel, ENT_QUOTES, 'UTF-8') ?></p>
            <ul>
                <?php foreach ($links as $link): ?>
                    <?php $isActive = $activeNav === $link['key']; ?>
                    <li>
                        <a class="sidebar-link nav-link<?= $isActive ? ' active' : '' ?>"
                           href="<?= htmlspecialchars(customer_url($link['path']), ENT_QUOTES, 'UTF-8') ?>"
                           <?= $isActive ? 'aria-current="page"' : '' ?>>
                            <?= customer_icon($link['icon']) ?>
                            <span><?= htmlspecialchars($link['label'], ENT_QUOTES, 'UTF-8') ?></span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endforeach; ?>
    </nav>

    <div class="sidebar-account">
        <span class="avatar avatar--small" aria-hidden="true"><?= htmlspecialchars(strtoupper(substr($customerName, 0, 1)), ENT_QUOTES, 'UTF-8') ?></span>
        <span class="sidebar-account__text">
            <strong><?= htmlspecialchars($customerName, ENT_QUOTES, 'UTF-8') ?></strong>
            <small>Customer account</small>
        </span>
    </div>
    <div class="sidebar-logout-row">
        <form method="post" action="<?= htmlspecialchars(customer_url('logout.php'), ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="<?= htmlspecialchars((string) $viewData['csrf_field_name'], ENT_QUOTES, 'UTF-8') ?>" value="<?= htmlspecialchars((string) $viewData['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
            <button class="customer-logout-button" type="submit" aria-label="Logout">
                <?= customer_icon('logout') ?>
                <span>Logout</span>
            </button>
        </form>
    </div>
</aside>
<button class="sidebar-overlay" type="button" aria-label="Close navigation" tabindex="-1" data-sidebar-overlay></button>
