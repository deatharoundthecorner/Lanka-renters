<?php
$activeNav = (string) ($viewData['active_nav'] ?? '');
$customerLinks = [
    ['key' => 'dashboard', 'label' => 'Overview', 'path' => 'dashboard.php', 'icon' => 'dashboard'],
    ['key' => 'verification', 'label' => 'Verification', 'path' => 'verification/index.php', 'icon' => 'shield'],
    ['key' => 'vehicles', 'label' => 'Search Vehicles', 'path' => 'vehicles/index.php', 'icon' => 'car'],
    ['key' => 'bookings', 'label' => 'Bookings', 'path' => 'bookings/index.php', 'icon' => 'calendar'],
    ['key' => 'inspection', 'label' => 'Inspection', 'path' => 'inspection/index.php', 'icon' => 'clipboard'],
    ['key' => 'payments', 'label' => 'Payment', 'path' => 'payments/index.php', 'icon' => 'credit-card'],
    ['key' => 'chat', 'label' => 'Chat', 'path' => 'chat/index.php', 'icon' => 'chat'],
    ['key' => 'incidents', 'label' => 'Incidents', 'path' => 'incidents/index.php', 'icon' => 'alert'],
    ['key' => 'driver-change', 'label' => 'Driver Change Request', 'path' => 'driver-change/index.php', 'icon' => 'switch'],
    ['key' => 'return', 'label' => 'Return', 'path' => 'returns/index.php', 'icon' => 'return'],
    ['key' => 'reviews', 'label' => 'Reviews', 'path' => 'reviews/index.php', 'icon' => 'star'],
    ['key' => 'profile', 'label' => 'Profile', 'path' => 'profile/index.php', 'icon' => 'user'],
];
$customerName = (string) ($viewData['customer']['name'] ?? 'Customer');
?>
<aside class="customer-sidebar" id="customer-sidebar" aria-label="Customer navigation" data-sidebar>
    <div class="sidebar-brand-row">
        <a class="sidebar-brand" href="<?= htmlspecialchars(customer_url('dashboard.php'), ENT_QUOTES, 'UTF-8') ?>">
            <span class="sidebar-brand__mark" aria-hidden="true">LR</span>
            <span><strong>Lanka Renters</strong><small>Customer workspace</small></span>
        </a>
        <button class="icon-button sidebar-close" type="button" aria-label="Close navigation" data-sidebar-close>
            <?= customer_icon('close') ?>
        </button>
    </div>

    <nav class="sidebar-nav" aria-label="Main Customer navigation">
        <p class="sidebar-nav__label">Customer</p>
        <ul>
            <?php foreach ($customerLinks as $link): ?>
                <?php $isActive = $activeNav === $link['key']; ?>
                <li>
                    <a class="sidebar-link<?= $isActive ? ' is-active' : '' ?>"
                       href="<?= htmlspecialchars(customer_url($link['path']), ENT_QUOTES, 'UTF-8') ?>"
                       <?= $isActive ? 'aria-current="page"' : '' ?>>
                        <?= customer_icon($link['icon']) ?>
                        <span><?= htmlspecialchars($link['label'], ENT_QUOTES, 'UTF-8') ?></span>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </nav>

    <div class="sidebar-account">
        <span class="avatar avatar--small" aria-hidden="true"><?= htmlspecialchars(strtoupper(substr($customerName, 0, 1)), ENT_QUOTES, 'UTF-8') ?></span>
        <span class="sidebar-account__text">
            <strong><?= htmlspecialchars($customerName, ENT_QUOTES, 'UTF-8') ?></strong>
            <small>Customer workspace</small>
        </span>
        <form method="post" action="<?= htmlspecialchars(customer_url('logout.php'), ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="<?= htmlspecialchars((string) $viewData['csrf_field_name'], ENT_QUOTES, 'UTF-8') ?>" value="<?= htmlspecialchars((string) $viewData['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
            <button class="icon-button" type="submit" aria-label="Log out">
                <?= customer_icon('logout') ?>
            </button>
        </form>
    </div>
</aside>
<button class="sidebar-overlay" type="button" aria-label="Close navigation" tabindex="-1" data-sidebar-overlay></button>
