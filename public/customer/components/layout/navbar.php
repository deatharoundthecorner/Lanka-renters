<?php
$customerName = (string) ($viewData['customer']['name'] ?? 'Customer');
$nameParts = preg_split('/\s+/', trim($customerName)) ?: [];
$initials = '';
foreach (array_slice($nameParts, 0, 2) as $namePart) {
    $initials .= strtoupper(substr($namePart, 0, 1));
}
$initials = $initials !== '' ? $initials : 'C';
?>
<header class="customer-navbar">
    <div class="navbar-context">
        <button class="icon-button menu-button" type="button" aria-label="Open navigation" aria-expanded="false" aria-controls="customer-sidebar" data-sidebar-open>
            <?= customer_icon('menu') ?>
        </button>
        <p><span>Lanka Renters</span><span aria-hidden="true">/</span><strong><?= htmlspecialchars((string) ($viewData['page_title'] ?? 'Customer'), ENT_QUOTES, 'UTF-8') ?></strong></p>
    </div>

    <div class="navbar-actions">
        <a class="icon-button notification-button" href="<?= htmlspecialchars(customer_url('notifications.php'), ENT_QUOTES, 'UTF-8') ?>" aria-label="View notifications">
            <?= customer_icon('bell') ?>
            <span class="notification-dot" aria-hidden="true"></span>
        </a>
        <div class="profile-menu" data-profile-menu>
            <button class="profile-menu__trigger" type="button" aria-expanded="false" aria-controls="customer-profile-menu" data-profile-trigger>
                <span class="avatar" aria-hidden="true"><?= htmlspecialchars($initials, ENT_QUOTES, 'UTF-8') ?></span>
                <span class="profile-menu__name"><?= htmlspecialchars($customerName, ENT_QUOTES, 'UTF-8') ?></span>
                <?= customer_icon('chevron-down') ?>
            </button>
            <div class="profile-menu__panel" id="customer-profile-menu" hidden data-profile-panel>
                <p><strong><?= htmlspecialchars($customerName, ENT_QUOTES, 'UTF-8') ?></strong><small>Customer account</small></p>
                <a href="<?= htmlspecialchars(customer_url('profile/index.php'), ENT_QUOTES, 'UTF-8') ?>"><?= customer_icon('user') ?> Profile settings</a>
                <form method="post" action="<?= htmlspecialchars(customer_url('logout.php'), ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="<?= htmlspecialchars((string) $viewData['csrf_field_name'], ENT_QUOTES, 'UTF-8') ?>" value="<?= htmlspecialchars((string) $viewData['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                    <button type="submit"><?= customer_icon('logout') ?> Log out</button>
                </form>
            </div>
        </div>
    </div>
</header>
